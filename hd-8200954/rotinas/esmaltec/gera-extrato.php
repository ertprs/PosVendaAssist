<?php
/**
* @author - William Ap. Brandino
* @since - 10/10/2013
*/

include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require_once dirname(__FILE__) . '/../funcoes.php';

$fabrica     = 30;

try {
    $vet['fabrica'] = 'esmaltec';
    $vet['tipo']    = 'extrato';
    $vet['dest']    = 'helpdesk@telecontrol.com.br';
    $vet['log']     = 2;

    $phpCron = new PHPCron($fabrica, __FILE__);
    $phpCron->inicio();

    $sql = "SELECT posto FROM tbl_posto_fabrica WHERE fabrica = {$fabrica} AND tipo_posto IN(SELECT tipo_posto FROM tbl_tipo_posto WHERE descricao = 'SAC' AND fabrica = {$fabrica})";

    $res = pg_query($con, $sql);
    $lista_posto_teste = '';
    for ($i=0; $i < pg_num_rows($res); $i++) {
        $lista_posto_teste .= ','.pg_fetch_result($res, $i, 'posto');
	}       
	if(strlen($lista_posto_teste) > 1) {	
		$sql_posto_teste = " AND tbl_posto_fabrica.posto NOT IN (".substr($lista_posto_teste, 1).")";    
	}

    $sql = "SELECT  DISTINCT
                    tbl_posto_fabrica.posto
            FROM    tbl_posto_fabrica
            WHERE tbl_posto_fabrica.fabrica    = $fabrica
            {$sql_posto_teste}
            AND     tbl_posto_fabrica.credenciamento    IN ('CREDENCIADO','EM DESCREDENCIAMENTO')
    ";
    $res = pg_query($con,$sql);
    $msg_erro .= pg_last_error($con);

    if (pg_num_rows($res) > 0 && strlen($msg_erro) == 0) {


        $conta = pg_num_rows($res);

        for ($i = 0; $i < $conta; $i++) {
            $msg_erro = null;
            $posto          = pg_fetch_result($res, $i, 'posto');

            $sql = "SELECT  DISTINCT
                tbl_os.posto,
                tbl_os.os,
                tbl_os.qtde_km
                FROM    tbl_os
                JOIN    tbl_os_extra ON tbl_os.os = tbl_os_extra.os
                AND tbl_os.fabrica = tbl_os_extra.i_fabrica
                AND tbl_os_extra.extrato IS NULL
                WHERE   tbl_os.fabrica                      = $fabrica
                AND     tbl_os.excluida                     IS NOT TRUE
                AND     tbl_os.posto                        = $posto";
            $resF = pg_query($con,$sql);

            if(pg_num_rows($resF) > 0){
                $resP = pg_query($con,"BEGIN TRANSACTION");

                /* Recalcula KM da os quando possuir for realizado visita - hd-3264913 */
                if (pg_num_rows($resF) > 0 and 1==2) {
					for($k = 0 ; $k<pg_num_rows($resF);$k++) {
						$os      = pg_fetch_result($resF, $k, "os");
						$sql_visita = "SELECT count(os_visita) AS qtde_visita FROM tbl_os_visita WHERE os = {$os} AND hora_chegada_cliente IS NOT NULL";
						$res_visita = pg_query($con, $sql_visita);
						$visitas_realizadas = pg_fetch_result($res_visita, 0, "qtde_visita");

						if ($visitas_realizadas >= 1) {
							$qtde_km_final = $qtde_km * $visitas_realizadas;
						}else{ /* Se não for marcado nenhuma visita, será zerado o KM da OS */
							$qtde_km_final = '0';
						}
						$sql_update = "UPDATE tbl_os SET qtde_km = {$qtde_km_final} WHERE os = {$os}";
						$res_update = pg_query($con, $sql_update);
						if (strlen(pg_last_error()) > 0) {
							$msg_erro .= pg_last_error();
							$resR = pg_query($con,"ROLLBACK TRANSACTION");
							continue;
						}
					}
                }

                $data_limite    = date('Y-m-d');

                $sql_extrato = "SELECT fn_fechamento_extrato ($posto, $fabrica, '$data_limite');";

                $res_extrato = pg_query($con,$sql_extrato);
                if (pg_last_error($con)) {
                    $msg_erro .= pg_errormessage($con);
                }
                $sql_extrato = "SELECT  extrato
                        FROM    tbl_extrato
                        WHERE   fabrica             = $fabrica
                        AND     posto               = $posto
                        AND     data_geracao::date  = CURRENT_DATE
                ";

                $res_extrato = pg_query($con,$sql_extrato);
                if (pg_last_error($con)) {
                    $msg_erro .= pg_errormessage($con);
                }
                if(empty($msg_erro)){
                    $extrato = pg_fetch_result($res_extrato, 0, 'extrato');
                    $extrato_interacao[] = $extrato;

                    $sql_extrato = "SELECT fn_calcula_extrato($fabrica, $extrato);";

                    $res_extrato = pg_query($con,$sql_extrato);
                    if (pg_last_error($con)) {
                        $msg_erro .= pg_errormessage($con);
                    }
                    if(empty($msg_erro)){
                        $sql_extrato = "SELECT fn_extrato_recompra($fabrica, $extrato);";

                        $res_extrato = pg_query($con,$sql_extrato);
                        if (pg_last_error($con)) {
                            $msg_erro .= pg_errormessage($con);
                        }
                    }

                    $sql_extrato = "UPDATE  tbl_posto_fabrica
                                    SET     extrato_programado = NULL
                                    WHERE   fabrica = $fabrica
                                    AND     posto   = $posto;
                    ";

                    $res_extrato = pg_query($con,$sql_extrato);
                    if (pg_last_error($con)) {
                        $msg_erro .= pg_errormessage($con);
                    }
                }

                /**
                 * - HD-1259064 - Verificação de extratos com valor menor de R$100,00
                 */
                $sqlMenor = "SELECT tbl_extrato.extrato AS extrato_menor
                    FROM   tbl_extrato
                    WHERE  tbl_extrato.fabrica             = $fabrica
                    AND    tbl_extrato.posto               = $posto
                    AND    tbl_extrato.data_geracao::date  = CURRENT_DATE
                    AND    tbl_extrato.total < 100
                ";
                $resMenor = pg_query($con,$sqlMenor);
                $msg_erro .= pg_errormessage($con);

                $conta_menor = pg_num_rows($resMenor);
                if ($conta_menor > 0 && strlen($msg_erro) == 0) {
                    $extrato_menor  = pg_fetch_result($resMenor, 0, 'extrato_menor');
                    $extrato_menor_interacao[] = $extrato_menor;
                    $sql_extrato_menor  = "SELECT fn_acumula_extrato($fabrica, $extrato_menor);";
                    $res_extrato_menor  = pg_query($con,$sql_extrato_menor);
                    if (pg_last_error($con)) {
                        $msg_erro           .= pg_errormessage($con);
                    }
                    $mensagem_comunicado = "O extrato foi acumulado para o próximo mês, pois o total foi inferior ao valor de R$100,00.";

                    $sqlIns = "INSERT INTO tbl_comunicado  (
                                        mensagem,
                                        tipo    ,
                                        fabrica ,
                                        posto   ,
                                        obrigatorio_site,
                                        ativo
                                ) VALUES (
                                        '$mensagem_comunicado',
                                        'Acúmulo de extrato',
                                        $fabrica            ,
                                        $posto              ,
                                        TRUE                ,
                                        TRUE
                                )";
                    $resIns      = pg_query($con,$sqlIns);
                    if (pg_last_error($con)) {
                        $msg_erro .= pg_errormessage($con);
                    }
                }

                if (strlen($msg_erro) > 0) {
                    echo $msg_erro;
                    $resP = pg_query('ROLLBACK;');
                    $enviaErro .= $msg_erro;
                    Log::log2($vet, $enviaErro);
                } else {
                    $resP = pg_query('COMMIT;');
                }
            }
        }
        /**
         * - Rotina para gravar na interação das OS's
         * que houve geração de extrato
         */
        $extrato_gravar_interacao = array_diff($extrato_interacao,$extrato_menor_interacao);
        foreach($extrato_gravar_interacao AS $ext){
            $sqlOs = "
                SELECT  tbl_os_extra.os
                FROM    tbl_os_extra
                WHERE   extrato = $ext
            ";
            $resOs = pg_query($con,$sqlOs);
            $extOs = pg_fetch_all_columns($resOs,0);
            foreach($extOs as $os){
                $res = pg_query($con,"BEGIN TRANSACTION");

                $sqlInt = " INSERT INTO tbl_os_interacao (
                                os,
                                data,
                                comentario,
                                interno,
                                fabrica
                            ) VALUES (
                                $os,
                                CURRENT_TIMESTAMP,
                                'A OS $os entrou no extrato $ext',
                                TRUE,
                                $fabrica
                            )
                ";
                $resInt = pg_query($con,$sqlInt);
                if(!pg_last_error($con)){
                    $resR = pg_query($con,"COMMIT TRANSACTION");
                }else{
                    echo pg_last_error($con);
                    $resR = pg_query($con,"ROLLBACK TRANSACTION");
                }
            }
        }
    }
    $phpCron->termino();
}catch (Exception $e){
    Log::envia_email($data,Date('d/m/Y H:i:s')." - Esmaltec - Erro na geração de extrato(gera-extrato.php)", $e->getMessage());
}
?>
