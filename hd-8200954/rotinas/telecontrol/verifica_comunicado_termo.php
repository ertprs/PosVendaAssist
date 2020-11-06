<?php
include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require dirname(__FILE__) . '/../funcoes.php';
include dirname(__FILE__) . '/../../class/ComunicatorMirror.php';

try {
	
	$fabrica = $argv[1];		
    $msg_erro = array();
   
    /*
    * Cron Class
    */
    $phpCron = new PHPCron($fabrica, __FILE__);
    $phpCron->inicio();

    $comunicatorMirror = new ComunicatorMirror();

    $sql = "SELECT 	TO_CHAR(tbl_comunicado.data, 'yyyy-mm-dd') AS data_comunicado,
    				tbl_comunicado.posto,
    				tbl_comunicado.parametros_adicionais::jsonb->>'os_termo' AS os
    		FROM tbl_comunicado
    		LEFT JOIN tbl_comunicado_posto_blackedecker USING(comunicado)
    		WHERE tbl_comunicado_posto_blackedecker.data_confirmacao IS NULL 
    		AND tbl_comunicado.fabrica = $fabrica
    		AND upper(tbl_comunicado.descricao) = 'OS REPROVADA EM AUDITORIA DE TERMO'
    		AND tbl_comunicado.parametros_adicionais::jsonb->>'os_termo' NOTNULL
    		ORDER BY data_comunicado DESC";
    $res = pg_query($con, $sql);
   

    if (pg_num_rows($res) > 0 ){
    	$oss = [];
    	for ($i=0; $i < pg_num_rows($res); $i++){
            $data_comunicado    = pg_fetch_result($res, $i, 'data_comunicado');
            $posto        		= pg_fetch_result($res, $i, 'posto');
            $os             	= pg_fetch_result($res, $i, 'os');
            
            if (in_array($os, $oss)) {
            	continue;
            }

            $oss[] = $os;
			$data_sete_dias = date('Y-m-d', strtotime('+ 7 days', strtotime($data_comunicado)));

			$Data_hj = new DateTime();
        	$Data_sete = new DateTime($data_sete_dias);
        	
        	if ($Data_sete < $Data_hj) {
        		$sql_auditoria_termo = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao) VALUES ($os, 6, 'Auditoria de Termo')";
				$res_auditoria_termo = pg_query($con, $sql_auditoria_termo);
				if (pg_last_error()) {
					$msg_erro["msg"][] = 'Erro no insert de auditoria OS - $os';					
				}
        	}
        }

        if (count($msg_erro["msg"]) > 0){
            if (count($msg_erro["msg"]) > 0) {
                $msg_email_erro = implode("<br />", $msg_erro["msg"]);
                try {
                    $comunicatorMirror->post('gaspar.lucas@telecontrol.com.br', utf8_encode("ERRO NA ROTINA VERIFICA COMUNICADO TERMO"), utf8_encode("$msg_email_erro"), "smtp@posvenda");
                } catch (\Exception $e) {
                }        
            }
        }
    }

} catch (Exception $e) {
    echo $e->getMessage();
}

/**
 * Cron Término
 */
$phpCron->termino();
