<?php
/**
 *
 * os_congeladas_45_dias.php
 *
 * Gongelamento de OS abertas a mais de 45 dias sem fechamento ou sem lançamento de peça
 *
 * @author Guilherme Monteiro
 * @version 11.11.2015
 *
*/

error_reporting(E_ALL ^ E_NOTICE);
define('ENV','teste');  // producao / teste

try{

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	include dirname(__FILE__) . '/../funcoes.php';
	include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

	$login_fabrica 	= "74";
	$fabrica_nome  	= "atlas";
	$log_erro 	 	= array();
	$log_sucesso 	= array();
	$data_sistema	= Date('Y-m-d');
	$arquivos = "/tmp";

	$arquivo_err = "{$arquivos}/{$fabrica_nome}/os_congeladas-{$data_sistema}.err";
	$arquivo_log = "{$arquivos}/{$fabrica_nome}/os_congeladas-{$data_sistema}.log";
	system ("mkdir {$arquivos}/{$fabrica_nome}/ 2> /dev/null ; chmod 777 {$arquivos}/{$fabrica_nome}/" );



	/* Inicio Processo */
	$phpCron = new PHPCron($login_fabrica, __FILE__);
	$phpCron->inicio();

	$sql = "SELECT tbl_os.os
			FROM tbl_os
			LEFT JOIN tbl_os_campo_extra ON tbl_os.os = tbl_os_campo_extra.os
            INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i = {$login_fabrica}
            INNER JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha AND tbl_linha.fabrica = {$login_fabrica}
			WHERE tbl_os.fabrica = $login_fabrica
            AND UPPER(tbl_linha.nome) != 'FOGO'
			AND tbl_os.data_fechamento IS NULL
			AND tbl_os.finalizada IS NULL
			AND CURRENT_DATE - tbl_os.data_abertura > 45
			AND tbl_os.excluida IS NOT TRUE
			AND tbl_os.cancelada IS NOT TRUE 
			AND (tbl_os_campo_extra.os_bloqueada IS NOT TRUE OR tbl_os_campo_extra.os IS NULL)";
	$res = pg_query($con,$sql);
	$total = pg_num_rows($res);

	if($total > 0){

		for ($i=0; $i < $total; $i++) {

			$erro = "";
			$os = pg_fetch_result($res, $i, 'os');

			if(empty($os)) continue;

			$resX = pg_query($con,"BEGIN");

			$sql = "SELECT os_bloqueada FROM tbl_os_campo_extra WHERE fabrica = {$login_fabrica} AND os = {$os}";
			$resee = pg_query($con,$sql);

			if(pg_num_rows($resee) > 0){
				$sql = "UPDATE tbl_os_campo_extra SET os_bloqueada = true WHERE os = $os";
			}else{

				$sql = "INSERT INTO tbl_os_campo_extra(os,fabrica,os_bloqueada) VALUES({$os},{$login_fabrica},TRUE)";
			}
			
			$resP = pg_query ($con,$sql);
			$erro = pg_errormessage($con);
			if (strlen($erro) > 0){
				$log_erro[] = "OS: ".$os. " - ". $erro;
				$resX = pg_query ($con,"ROLLBACK");
			}else{
				$log_sucesso[] = "OS: ".$os . " - Congelada com sucesso";
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

      // $email = "guilherme.monteiro@telecontrol.com.br";
      // $mailer = new PHPMailer();
      // $mailer->IsHTML();
      // $mailer->AddReplyTo("suporte@telecontrol.com.br", "Suporte Telecontrol");
      // $mailer->AddAddress($email);
      // $mailer->AddAttachment("{$arquivo_log}");
      // $mensagem .= "Logs 'Congelamento OS 45 Dias'<br>";
      // $mensagem .= "Mensagem segue em anexo!<br><br>";
      // $mensagem .= "<br><br>Att.<br>Telecontrol Networking";
      // $mailer->Body = $mensagem;
      // if(!$mailer->Send())
      // throw new Exception ($mailer->ErrorInfo);
   }


}catch(Exception $e){

	$e->getMessage();
    $msg = "Arquivo: ".__FILE__."\r\n<br />Linha: " . $e->getLine() . "\r\n<br />Descrição do erro: " . $e->getMessage() ."<hr /><br /><br />". implode("<br /><br />", $logs);

    Log::envia_email($data,Date('d/m/Y H:i:s')." - Atlas - Congelamento de OS autometico 45 dias (os_congeladas_45_dias.php)", $msg);

}
