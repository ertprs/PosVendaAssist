<?php 
    
define('APP','Gera Pedido  - Cadence'); // Nome da rotina, para ser enviado por e-mail
define('ENV','dev'); // Alterar para produção ou algo assim

try {

    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require dirname(__FILE__) . '/../funcoes.php';

    $fabrica     = 35;
    $data        = date('d-m-Y');

    $phpCron = new PHPCron($fabrica, __FILE__); 
    $phpCron->inicio();

    $vet['fabrica'] = 'cadence';
    $vet['tipo']    = 'pedido';
    $vet['log']     = 1;
    $data = date("Y-m-d-H-m-s");
    $dir                = "/tmp/cadence";
    $file_erro          = "gera_pedido_os_$data.err";
    $file_log           = "gera_pedido_os_$data.log";
    $file_log_email     = "gera_pedido_os_erro_email_$data.err";

    $sql = "SET DateStyle TO 'SQL,EUROPEAN'";
    $result = pg_query($con, $sql);

    if (strlen(pg_last_error($con)) > 0) {
        $msg_erro .= $sql;
        $msg_erro .= "\n";
        $msg_erro .= pg_last_error($con);
        $msg_erro .= "\n\n\n";
    }

    $sqlAuditoria = "
                                        SELECT interv_4pecas.os into temp tmp_cadence_os
                                        FROM (
                                            SELECT
                                            ultima_4pecas.os,
                                            (SELECT status_os FROM tbl_os_status WHERE tbl_os_status.fabrica_status = $fabrica AND tbl_os_status.os = ultima_4pecas.os AND status_os IN (13,19,62,127,64) ORDER BY data DESC LIMIT 1) AS ultimo_4pecas_status
                                            FROM (SELECT DISTINCT os FROM tbl_os_status WHERE tbl_os_status.fabrica_status = $fabrica AND status_os IN (13,19,62,127,64) ) ultima_4pecas
                                            ) interv_4pecas
                                        WHERE interv_4pecas.ultimo_4pecas_status IN (13, 62, 127)                                      ;";
    $resAuditoria = pg_query($con, $sqlAuditoria);

    $sql = "SELECT  tbl_os.posto,
                tbl_os_item.os_item,
                tbl_os_item.peca    ,
                tbl_os_item.qtde,
                tbl_os.os
        INTO    TEMP tmp_pedido_cadence
        FROM    tbl_os_item
        JOIN    tbl_servico_realizado  ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado AND
tbl_servico_realizado.fabrica = tbl_os_item.fabrica_i
        JOIN    tbl_os_produto USING (os_produto)
        JOIN    tbl_os         USING (os)
        JOIN    tbl_posto_fabrica    ON tbl_posto_fabrica.posto   = tbl_os.posto
                                    AND tbl_posto_fabrica.fabrica = tbl_os.fabrica
        WHERE   tbl_os_item.pedido IS NULL
        AND     tbl_os.excluida    IS NOT TRUE
        AND     tbl_os.validada    IS NOT NULL
        AND     tbl_os.posto       <> 6359
        AND     tbl_servico_realizado.gera_pedido
        AND     tbl_os.fabrica      = $fabrica
        AND     tbl_os_item.fabrica_i = $fabrica
        AND     (tbl_posto_fabrica.credenciamento = 'CREDENCIADO' OR tbl_posto_fabrica.credenciamento = 'EM DESCREDENCIAMENTO' )
        AND     tbl_os.troca_garantia       IS NULL
		AND     tbl_os.troca_garantia_admin IS NULL
		AND		tbl_os.os not in (select os from tmp_cadence_os) ;

    SELECT DISTINCT posto,os  FROM tmp_pedido_cadence ; ";
    
    $result = pg_query($con, $sql);

    if (strlen(pg_last_error($con)) > 0) {
        $msg_erro .= $sql;
        $msg_erro .= "\n";
        $msg_erro .= pg_last_error($con);
        $msg_erro .= "\n\n\n";
    }else{
	if(pg_num_rows($result) == 0){
		$msg_erro_email[] = "No momento não há Ordens de Serviço aptas para a gerção de pedidos";
	}
    }

    for($i=0; $i<pg_num_rows($result); $i++){
        $posto  = pg_fetch_result($result, $i, 'posto');
        $os     = pg_fetch_result($result, $i, 'os');

		$sql_auditoria = "SELECT auditoria_os
				FROM tbl_auditoria_os 
				WHERE os = $os
				AND (bloqueio_pedido IS TRUE OR cancelada IS NOT NULL OR reprovada IS NOT NULL)";
		$res_auditoria = pg_query($con, $sql_auditoria);

	if (pg_num_rows($res_auditoria) > 0) {
		$msg_erro_email[] = "OS $os não gerou pedido, pois encontra-se em auditoria";
		continue;
	}

        $erro = " ";
        $sql = "BEGIN TRANSACTION";
        $resultX = pg_query($con, $sql);

        #HD 56418 Os com intervenção de 4 peças não gera pedido
        $sql = "SELECT  os_item ,
                        peca    ,
                        qtde
                FROM   tmp_pedido_cadence
                WHERE  os = $os ";
        $result2 = pg_query($con, $sql);

        if (strlen(pg_last_error($con)) > 0) {
            $msg_erro .= $sql;
            $msg_erro .= "\n";
            $msg_erro .= pg_last_error($con);
            $msg_erro .= "\n\n\n";
            $msg_erro .= "\n Sql 1\n";
            $erro .= "*";
        }

        #Garantia
        $condicao = "960";
        $tipo_pedido = "113";

        $sql = "INSERT INTO tbl_pedido (
                    posto        ,
                    fabrica      ,
                    condicao     ,
                    tipo_pedido  ,
                    status_pedido
                ) VALUES (
                    $posto      ,
                    $fabrica    ,
                    $condicao   ,
                    $tipo_pedido,
                    1
                ) RETURNING pedido;";
        $resultX = pg_query($con, $sql);
        $log .= $sql;
        $log .= "\n";

        if (strlen(pg_last_error($con)) > 0) {
            $msg_erro .= $sql;
            $msg_erro .= "\n";
            $msg_erro .= pg_last_error($con);
            $msg_erro .= "\n\n\n";
            $msg_erro .= "\n Insert 1\n";
            $erro .= "*";
        }

        $pedido = pg_fetch_result($resultX, 0, pedido);

        for($b=0; $b<pg_num_rows($result2); $b++){
            $os_item = pg_fetch_result($result2, $b, os_item);
            $peca    = pg_fetch_result($result2, $b, peca);
            $qtde    = pg_fetch_result($result2, $b, qtde);

            $sql = "INSERT INTO tbl_pedido_item (
                    pedido,
                    peca  ,
                    qtde  ,
                    qtde_faturada,
                    qtde_cancelada
                ) VALUES (
                    $pedido,
                    $peca  ,
                    $qtde  ,
                    0      ,
                    0      ) RETURNING pedido_item";
            $resultX = pg_query($con, $sql);
            $log .= $sql;
            $log .= "\n";

            if (strlen(pg_last_error($con)) > 0) {
                $msg_erro .= $sql;
                $msg_erro .= "\n";
                $msg_erro .= pg_last_error($con);
                $msg_erro .= "\n\n\n";
                $msg_erro .= "\n Insert 2\n";
                $erro .= "*";
            }

            $pedido_item = pg_fetch_result($resultX, 0, 'pedido_item');

            $sql = "SELECT fn_atualiza_os_item_pedido_item ($os_item,$pedido,$pedido_item,$fabrica)";
            $resultX = pg_query($con, $sql);
            if (strlen(pg_last_error($con)) > 0) {
                $msg_erro .= $sql;
                $msg_erro .= "\n";
                $msg_erro .= pg_last_error($con);
                $msg_erro .= "\n\n\n";
                $msg_erro .= "\n Update 1\n";
                $erro .= "*";
            }

        }

        $sql = "SELECT fn_pedido_finaliza ($pedido,$fabrica)";
        $resultX = pg_query($con, $sql);

        $sql_hd = "SELECT hd_chamado FROM tbl_hd_chamado_extra WHERE os = $os ORDER BY hd_chamado DESC LIMIT 1";
        $res_hd = pg_query($con, $sql_hd);

        if(pg_num_rows($res_hd) > 0){
            $hd_chamado = pg_fetch_result($res_hd,0,'hd_chamado');
            $sql_update = "UPDATE tbl_hd_chamado_extra SET pedido = $pedido WHERE hd_chamado = $hd_chamado";
            $res_update = pg_query($con, $sql_update);
        } 

        if (strlen(pg_last_error($con)) > 0) {
            $msg_erro .= $sql;
            $msg_erro .= "\n";
            $msg_erro .= pg_last_error($con);
            $msg_erro .= "\n\n\n";
            $msg_erro .= "\n Função 1\n$sql\n";

            $erro = "*";
           
        }

        if ($erro == "*") {
            $sql = "ROLLBACK TRANSACTION";
            $resultX = pg_query($con, $sql);
        }else{
            $sql = "COMMIT TRANSACTION";
            $resultX = pg_query($con, $sql);
        }
    }

    if(strlen(trim($msg_erro))>0){
        $msg = "Alguns pedidos não foram criados a partir de suas OS, e serão gerados automaticamente assim que os problemas forem solucionados.\n <br><br>\n <b>Verifique tabelas de preços, cadastro de peças, etc.</b>\n $msg_erro"; 
        $msg_erro = str_replace("ERROR: ", "", $msg);
        $arquivo_msg = file_put_contents($dir.'/'.$file_erro, $msg_erro);
        $vet["dest"] = "helpdesk@telecontrol.com.br";
        Log::envia_email($vet,APP, $msg_erro, true, "erro");
    }

    if(strlen(trim($msg_erro_email))>0){
        $msg = "Alguns pedidos não foram criados a partir de suas OS, e serão gerados automaticamente assim que os problemas forem solucionados.\n
        <br><br>\n
        <b>Verifique tabelas de preços, cadastro de peças, etc.</b>\n $msg_erro_email"; 
        $msg_erro = str_replace("ERROR: ", "", $msg);
        $arquivo_msg = file_put_contents($dir.'/'.$file_log_email, $msg_erro_email);
        $vet["dest"][0] = "helpdesk@telecontrol.com.br";
        $vet["dest"][1] = "renata@cadence.com.br";
        $vet["dest"][2] = "priscila@cadence.com.br";
        Log::envia_email($vet,APP, $msg_erro, true, "erro");
    }

    $phpCron->termino();

} catch (Exception $e) {
    
    if (!empty($msg_erro)) {
        $msg .= $msg_erro;
    }
    $vet["dest"] = "lucas.carlos@telecontrol.com.br";
    $msg = 'Script: '.__FILE__.'<br />Erro na linha ' . $e->getLine() . ':<br />' . $e->getMessage();
    Log::envia_email($vet,APP, $msg, true );

}
?>

