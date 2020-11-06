<?php


	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	require dirname(__FILE__) . '/../funcoes.php';
	include dirname(__FILE__) . '/../../classes/Posvenda/Extrato.php';

	/*
	* Definições
	*/
    #define('ENV', 'producao');
    define('ENV', 'dev');
	$fabrica 		= 50;
	$dia_mes     	= date('d');
	$dia_extrato 	= date('Y-m-d H:i:s');
    try{
        /*
         * Cron Class
         */
        $phpCron = new PHPCron($fabrica, __FILE__);
        $phpCron->inicio();

        /*
         * Log Class
         */
        $logClass = new Log2();
        $logClass->adicionaLog(array("titulo" => "Log de erro - Geração de Extrato Colormaq")); // Titulo
        #$logClass->adicionaEmail("helpdesk@telecontrol.com.br");
        $logClass->adicionaEmail("otavio.arruda@telecontrol.com.br");

        if($dia_mes != 1 && ENV == 'producao'){
            throw new Exception("Dia Mês diferente de 1");
        }

        /*
         * Resgata a quantidade de OS por Posto
         */
        $sql = "SELECT  posto, COUNT(1) AS qtde
            FROM tbl_os
            JOIN tbl_os_extra ON tbl_os.os = tbl_os_extra.os AND tbl_os_extra.i_fabrica = $fabrica
            WHERE tbl_os.fabrica = $fabrica
            AND   tbl_os_extra.extrato IS NULL
            AND   tbl_os.excluida      IS NOT TRUE
            AND   tbl_os.finalizada    < '$dia_extrato'
            AND   tbl_os.finalizada::date <> current_date
            AND   NOT(tbl_os.posto = 6359)
            GROUP BY posto
            ORDER BY posto ";

        $result = pg_query($con,$sql);

        if(strlen(pg_last_error($con)) > 0 ){
            throw new Exception("Erro ao selecionar quantidade de OS por posto.");
        }
    }catch(Exception $ex){
        enviaEmail($logClass, $ex->getMessage());
    }
    $arrMsgErro = array();

    $arrData = pg_fetch_all($result);
    foreach($arrData as $row){

        try {
            $posto = $row['posto'];
            $qtde = $row['qtde'];

            $sql = 'BEGIN TRANSACTION';
            $res = pg_query($con, $sql);

            if(pg_last_error($con)){
                $msg_erro .= 'Erro ao iniciar transação';
            }

            $devolver_pecas = '';
            $sqlParametrosAdicionais = "SELECT parametros_adicionais FROM tbl_posto_fabrica WHERE posto = {$posto} AND fabrica = {$fabrica}";
            $res = pg_query($con, $sqlParametrosAdicionais);
            $parametros_adicionais = pg_fetch_result($res, 0, 'parametros_adicionais');
            if(!empty($parametros_adicionais)){
                $parametros_adicionais = json_decode($parametros_adicionais,true);
                if(is_array($parametros_adicionais)){
                    if(array_key_exists('devolver_pecas',$parametros_adicionais)){
                        $devolver_pecas = $parametros_adicionais['devolver_pecas'];
                    }
                }
            }
            #cria extrato para posto
            $sql = "INSERT INTO tbl_extrato (posto, fabrica, avulso, total) VALUES ($posto,$fabrica, 0, 0) RETURNING extrato";
            $res = pg_query($con, $sql);

            if(strlen(pg_last_error($con)) > 0){
                $msg_erro .= 'Erro ao criar extrato';
            }

            $extrato = pg_fetch_row($res,0);
            $extrato = $extrato[0];

            #Seta o número do extrato em que as OS pertencem.
            $sql = "SELECT tbl_os.os,
                ARRAY(select status_os from tbl_os_status where tbl_os_status.os = tbl_os.os and status_os in (98,99,100) order by os_status desc) as status_km,
                ARRAY(select status_os from tbl_os_status where tbl_os_status.os = tbl_os.os and status_os in (13,19,67,68,70,115,118,139,187) order by os_status desc) as status_reinc,
				ARRAY(select status_os from tbl_os_status where tbl_os_status.os = tbl_os.os and status_os in (102,103,104) order by os_status desc) as serie_status,
				ARRAY(select status_os from tbl_os_status where tbl_os_status.os = tbl_os.os and status_os in (81) order by os_status desc) as mo_zerada
                INTO TEMP colormaq_extrato_$posto
                FROM  tbl_os
                JOIN tbl_os_extra ON tbl_os.os = tbl_os_extra.os AND tbl_os_extra.i_fabrica=$fabrica
                LEFT JOIN tbl_os_campo_extra ON tbl_os.os = tbl_os_campo_extra.os
                WHERE tbl_os.posto   = $posto
                AND   tbl_os.fabrica = $fabrica
                AND   tbl_os.os      = tbl_os_extra.os
                AND   tbl_os_extra.extrato IS NULL
                AND   tbl_os.excluida      IS NOT TRUE
                AND   tbl_os_campo_extra.os_bloqueada IS NOT TRUE
                AND   tbl_os.finalizada    < '$dia_extrato'
                AND   tbl_os.finalizada::date <> current_date;

            UPDATE tbl_os_extra SET extrato = $extrato
                FROM  colormaq_extrato_$posto
                WHERE colormaq_extrato_$posto.os = tbl_os_extra.os
                AND   (status_km[1] IS NULL or status_km[1] in (99,100))
				AND   (status_reinc[1] IS NULL or status_reinc[1] in (19,139,187))
				AND   (mo_zerada[1] IS NULL)
                AND   (serie_status[1] IS NULL or serie_status[1] in (103));
            ";


            $res = pg_query($con, $sql);

            if(pg_last_error($con)){

                $msg_erro .= "Erro ao vincular extrato na OS";
            }

            #Seta o número do extrato nos lançamentos avulsos
            $sql = "UPDATE tbl_extrato_lancamento SET extrato = $extrato
                WHERE tbl_extrato_lancamento.posto = $posto
                AND tbl_extrato_lancamento.fabrica = $fabrica
                AND tbl_extrato_lancamento.data_lancamento      < '$dia_extrato'
                AND ((tbl_extrato_lancamento.competencia_futura < '$dia_extrato') OR (tbl_extrato_lancamento.competencia_futura IS NULL))
                AND tbl_extrato_lancamento.extrato IS NULL
                ";
            pg_query($con, $sql);

            if(pg_last_error($con)){
                $msg_erro .= 'Erro ao atualizar avulso';
            }


            #Aqui é feito o cálculo de mão de obra e de peças do extrato em si
            $sql = "SELECT fn_calcula_extrato ($fabrica,$extrato)";
            pg_query($con, $sql);

            if(pg_last_error($con)){
                $msg_erro .= 'Erro ao calcular mão de obra e peças';
            }

            #atualizada mao de obra null
            $sql = "UPDATE tbl_extrato SET mao_de_obra =0
                WHERE extrato = $extrato AND mao_de_obra IS NULL";
            pg_query($con, $sql);

            if(pg_last_error($con)){
                $msg_erro .= 'Erro ao atualizar mão de obra';
            }

            $sql = "UPDATE tbl_extrato SET pecas =0
                WHERE extrato = $extrato AND pecas IS NULL";
            pg_query($con,$sql);

            if(pg_last_error($con)){
                $msg_erro .= 'Erro ao atualizar peca no extrato';
            }

            $sql = "UPDATE tbl_extrato SET avulso =0
                WHERE extrato = $extrato AND avulso IS NULL";
            pg_query($con,$sql);

            if(pg_last_error($con)){
                $msg_erro .= 'Erro ao atualizar campo avulso no extrato no extrato';
            }

            $sql = "UPDATE tbl_extrato SET deslocamento =0
                WHERE extrato = $extrato AND deslocamento IS NULL";

            pg_query($con,$sql);
            if(pg_last_error($con)){
                $msg_erro .= 'Erro ao atualizar deslocamento no extrato';
            }

            #Aqui é feito o calculo do total do extrato, onde se pega o valor de mão de obra e de peças
            $sql = "UPDATE tbl_extrato SET total = mao_de_obra + pecas + avulso + deslocamento
                WHERE extrato = $extrato";

            pg_query($con,$sql);
            if(pg_last_error($con)){
                $msg_erro .= 'Erro ao calcular total do extrato';
            }

            $sql = "SELECT ('$dia_extrato'::date - INTERVAL '1 month' + INTERVAL '19 days')::date";
            $res = pg_query($con,$sql);

            $data_15 = pg_fetch_row($res,0);
            $data_15 = $data_15[0];

            #HD 36983 - 48024
            $sql = "SELECT controla_estoque FROM tbl_posto_fabrica WHERE posto = $posto AND fabrica = $fabrica";
            $res = pg_query($con, $sql);

            $controla_estoque = pg_fetch_row($res,0);
            $controla_estoque = $controla_estoque[0];

            $sql_os = "SELECT colormaq_extrato_$posto.os, Tbl_faturamento_item.faturamento
                FROM colormaq_extrato_$posto
                INNER JOIN tbl_os_extra ON colormaq_extrato_$posto.os = tbl_os_extra.os
                INNER JOIN tbl_faturamento_item on tbl_faturamento_item.os = colormaq_extrato_$posto.os
                WHERE
                (status_km[1] IS NULL or status_km[1] in (99,100))
                AND   (status_reinc[1] IS NULL or status_reinc[1] in (19,139,187))
                AND   (serie_status[1] IS NULL or serie_status[1] in (103))
                GROUP BY colormaq_extrato_$posto.os, Tbl_faturamento_item.faturamento ";
            $res_os = pg_query($con, $sql_os);
            for($a=0; $a<pg_num_rows($res_os); $a++){
                $faturamento = pg_fetch_result($res_os, $a, 'faturamento');
                $os = pg_fetch_result($res_os, $a, 'os');

                $sql_upd_fat = "UPDATE tbl_faturamento_item SET extrato_devolucao = '$extrato' where faturamento = $faturamento AND tbl_faturamento_item.os = $os ";
                $res_upd_fat = pg_query($con, $sql_upd_fat);

                if(strlen(pg_last_error($con))>0){
                    $msg_erro = pg_last_error($con);
                }
            }

            if(strlen($msg_erro)){
                throw new Excepion($msg_erro);
            }
            pg_query($con,"COMMIT TRANSACTION");
        }catch(Exception $ex){

            pg_query($con,"ROLLBACK TRANSACTION");
            $arrMsgErro[$posto] = $ex->getMessage();
        }
    }

    if(count($arrMsgErro) > 0 ){
        enviaEmailLog($logClass, $arrMsgErro);
    }

/*
 * Cron Término
 */
$phpCron->termino();

function enviaEmailLog($logClass, $arrMsgErro){

    $logClass->adicionaLog($arrMsgErro);

    if($logClass->enviaEmails() == "200"){
        echo "Log de erro enviado com Sucesso!";
    }else{
        echo $logClass->enviaEmails();
    }

    $fp = fopen("tmp/{$fabrica_nome}/extratos/log-erro.text", "a");
    fwrite($fp, "Data Log: " . date("d/m/Y") . "\n");
    fwrite($fp, $msg_erro_arq . "\n \n");
    fclose($fp);
}
