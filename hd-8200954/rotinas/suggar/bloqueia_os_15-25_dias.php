<?php
/**
 *
 * bloqueia_os_15-25_dias.php
 *
 * Bloqueioi de OS abertas entre 15 a 25 dias sem lançamento de peças.
 * Bloqueioi de OS abertas a mais de 25 dias com ou sem lançamento de peças
 *
 * @author Thiago Tobias
 * @version 05.05.2015
 *
*/

error_reporting(E_ALL ^ E_NOTICE);
define('ENV','teste');  // producao / teste

try{

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	include dirname(__FILE__) . '/../funcoes.php';
	//include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

	$login_fabrica 	= "24";
	$fabrica_nome  	= "suggar";
	$log_erro 	 	= array();
	$log_sucesso 	= array();
	$data_sistema	= Date('Y-m-d');
	$arquivos = "/tmp";

	$arquivo_err = "{$arquivos}/{$fabrica_nome}/bloqueia-os-{$data_sistema}.err";
    $arquivo_log = "{$arquivos}/{$fabrica_nome}/bloqueia-os-{$data_sistema}.log";
    system ("mkdir {$arquivos}/{$fabrica_nome}/ 2> /dev/null ; chmod 777 {$arquivos}/{$fabrica_nome}/" ); 

	/* Inicio Processo */
	$phpCron = new PHPCron($login_fabrica, __FILE__);
	$phpCron->inicio();
	// - OS abertas entre 15 a 25 dias sem lançamento de peças.
	$sql = "SELECT tbl_os.os
				FROM tbl_os
				LEFT JOIN tbl_os_produto on tbl_os.os = tbl_os_produto.os 
				WHERE fabrica = $login_fabrica
				AND posto <> 6359
				AND excluida IS NOT TRUE
				AND data_fechamento IS NULL
				AND finalizada IS NULL
				AND tbl_os_produto.os_produto IS NULL
				AND (CURRENT_DATE - data_abertura) BETWEEN 15 and 25 ;";
	$res = pg_query($con,$sql);

	$total = pg_num_rows($res);

	if($total > 0){

		for ($i=0; $i < $total; $i++) { 
			
			$erro = "";
			$os = pg_fetch_result($res, $i, 'os');

			$resX = pg_query($con,"BEGIN");

			$sql = "UPDATE tbl_os SET off_line_reservada = TRUE WHERE os = $os";
			$resP = pg_query ($con,$sql);
			$erro = pg_errormessage($con);
			if (strlen($erro) > 0){
				$log_erro[] = $os . " - ". $erro;
				$resX = pg_query ($con,"ROLLBACK");
			}else{
				$log_sucesso[] = $os . " - Bloqueada com sucesso";
				$resX = pg_query ($con,"COMMIT");
			}

		}

	}

	// - OSs abertas a mais de 25 dias com ou sem lançamento de peças
	$sql = "SELECT os
				FROM tbl_os
				WHERE fabrica = $login_fabrica
				AND posto <> 6359
				AND excluida IS NOT TRUE
				AND data_fechamento IS NULL
				AND finalizada IS NULL
				AND (CURRENT_DATE - data_abertura) > 25";
	$res = pg_query($con,$sql);

	$total = pg_num_rows($res);

	if($total > 0){

		for ($i=0; $i < $total; $i++) { 
			
			$erro = "";
			$os = pg_fetch_result($res, $i, 'os');

			$resX = pg_query($con,"BEGIN");

			$sql = "UPDATE tbl_os SET off_line_reservada = TRUE WHERE os = $os";
			$resP = pg_query ($con,$sql);
			$erro = pg_errormessage($con);
			if (strlen($erro) > 0){
				$log_erro[] = $os . " - ". $erro;
				$resX = pg_query ($con,"ROLLBACK");
			}else{
				$log_sucesso[] = $os . " - Bloqueada com sucesso";
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
    //vai mandar e-mail para alguem ?
   // Log::envia_email($data,Date('d/m/Y H:i:s')." - Suggar - Bloqueio de OS automatico (bloqueia_os_15-25_dias.php)", $msg);

}