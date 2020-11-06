<?php
/**
 *
 * fechamento-os-25-dias.php
 *
 * Fechamento de OS abertas a mais de 25 dias que não estejam em auditoria e que possua itens
 *
 * @author Ronald Santos
 * @version 2013.10.01
 *
*/

error_reporting(E_ALL ^ E_NOTICE);
define('ENV','producao');  // producao / teste

try{

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	include dirname(__FILE__) . '/../funcoes.php';
	include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

	$login_fabrica 	= "24";
	$fabrica_nome  	= "suggar";
	$log_erro 	 	= array();
	$log_sucesso 	= array();
	$data_sistema	= Date('Y-m-d');
	$arquivos = "/tmp";

	$arquivo_err = "{$arquivos}/{$fabrica_nome}/fechamento-os-{$data_sistema}.err";
    $arquivo_log = "{$arquivos}/{$fabrica_nome}/fechamento-os-{$data_sistema}.log";
    system ("mkdir {$arquivos}/{$fabrica_nome}/ 2> /dev/null ; chmod 777 {$arquivos}/{$fabrica_nome}/" ); 

	/* Inicio Processo */
	$phpCron = new PHPCron($login_fabrica, __FILE__);
	$phpCron->inicio();

	$sql = "SELECT DISTINCT tbl_os.os
					FROM tbl_os
					JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os
					WHERE tbl_os.fabrica = $login_fabrica
					AND data_abertura >= '2013-10-01'
					AND (current_date - tbl_os.data_abertura) > 25
					AND tbl_os.posto <> 6359
					AND tbl_os.finalizada isnull
					AND tbl_os.excluida IS NOT TRUE
					AND   NOT (tbl_os.os  IN (
					SELECT interv_reinc.os
					FROM (
					SELECT
					ultima_reinc.os,
					(
					SELECT status_os
					FROM tbl_os_status
					WHERE tbl_os_status.os = ultima_reinc.os
					AND status_os IN (19,64,67,70,68,98,99,101,155,139)
					AND fabrica_status = $login_fabrica
					ORDER BY data DESC LIMIT 1
					) AS ultimo_reinc_status
					FROM (
					SELECT DISTINCT os
					FROM tbl_os_status
					WHERE status_os IN (19,64,67,70,68,98,99,101,155,139)
					AND fabrica_status = $login_fabrica
					) ultima_reinc
					) interv_reinc
					WHERE interv_reinc.ultimo_reinc_status IN (67,68,70,98)
					))
					ORDER BY tbl_os.os";
	$res = pg_query($con,$sql);

	$total = pg_num_rows($res);

	if($total > 0){

		for ($i=0; $i < $total; $i++) { 
			
			$erro = "";
			$os = pg_fetch_result($res, $i, 'os');

			$resX = pg_query($con,"BEGIN");

			$sql = "SELECT fn_finaliza_os($os, $login_fabrica)";
			$resP = pg_query ($con,$sql);
			$erro = pg_errormessage($con);
			if (strlen($erro) > 0){
				$log_erro[] = $os . " - ". $erro;
				$resX = pg_query ($con,"ROLLBACK");
			}else{
				$log_sucesso[] = $os . " - Finalizada com sucesso";
				$resX = pg_query ($con,"COMMIT");
			}

		}

	}

	if(count($log_erro) > 0){
    	$file_log_erro = fopen($arquivo_err,"w+");
        fputs($file_log_erro,implode("\r\n", $log_erro));
        fclose ($file_log_erro);
    }

    if(count($log_sucesso) > 0){
    	$file_log_sucesso = fopen($arquivo_log,"w+");
        fputs($file_log_sucesso,implode("\r\n", $log_sucesso));
        fclose ($file_log_sucesso);
    }

	
}catch(Exception $e){

	$e->getMessage();
    $msg = "Arquivo: ".__FILE__."\r\n<br />Linha: " . $e->getLine() . "\r\n<br />Descrição do erro: " . $e->getMessage() ."<hr /><br /><br />". implode("<br /><br />", $logs);

    Log::envia_email($data,Date('d/m/Y H:i:s')." - Suggar - Fechamento OS mais de 25 dias (fechamento-os-25-dias.php)", $msg);

}