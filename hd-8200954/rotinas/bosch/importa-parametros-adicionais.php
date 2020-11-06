<?php

/**
 *
 * gera-pedido-pulmao.php
 *
 * Atualiza parametros adicionais Posto Fabrica
 *
 * @author  Guilherme Monteiro
 *
*/

error_reporting(E_ALL ^ E_NOTICE);
define('ENV','teste');  // producao / teste

try{

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	require dirname(__FILE__) . '/../funcoes.php';

	$data['login_fabrica'] 	= 20;
    $data['fabrica_nome'] 	= 'bosch';
    $data['arquivo_log'] 	= 'importa-parametros-adicionais';
    $data['log'] 			= 2;
    $data['arquivos'] 		= "teste";
    $data['data_sistema'] 	= Date('Y-m-d');
    $logs 					= array();
    $logs_erro				= array();
    $logs_cliente			= array();
    $pedido_pecas			= array();
    $erro 					= false;
    $fabrica = 20;
    $fabrica_nome = "Bosch";
    extract($data);

	$phpCron = new PHPCron($login_fabrica, __FILE__);
	$phpCron->inicio();


    $arquivo_err = "{$arquivos}/{$arquivo_log}-{$data_sistema}.err";
    $arquivo_log = "{$arquivos}/{$arquivo_log}-{$data_sistema}.log";

    $arq_peca = $arquivos."/arquivo.txt";

    if (file_exists($arq_peca) and (filesize($arq_peca) > 0)){

		$conteudo = file_get_contents($arq_peca);
		$conteudo_array = explode("\n",$conteudo);

		foreach ($conteudo_array as $codigo_posto){

			if(strlen(trim($codigo_posto)) > 0){
				pg_query($con, 'BEGIN');
				$sql = "SELECT posto, codigo_posto, parametros_adicionais FROM tbl_posto_fabrica WHERE fabrica = {$fabrica} AND codigo_posto = '{$codigo_posto}'";
				$res = pg_query($con,$sql);

				if(pg_last_error($con)){
					$logs[] = $msg_erro = Date("Y-m-d H:i:s")." - Erro SQL: 'Selecionar Posto Codigo:'.$codigo_posto";
					$logs_erro[] = $sql;
					$logs[] = pg_last_error($con);
					$erro = "*";
				}else{
					if(pg_num_rows($res) > 0){

						$posto = pg_fetch_result($res, 0, 'posto');
						$cod_posto = pg_fetch_result($res, 0, 'codigo_posto');
						$parametros_adicionais = pg_fetch_result($res, 0, 'parametros_adicionais');

						$adicionais = json_decode($parametros_adicionais, true);

						$adicionais['foto_serie_produto'] = "t";

						$posto_parametros_adicionais = json_encode($adicionais);

						$update = "UPDATE tbl_posto_fabrica SET parametros_adicionais = '{$posto_parametros_adicionais}' WHERE fabrica = $fabrica AND posto = {$posto}";
						$res_update = pg_query($con, $update);

						if(pg_last_error($con)){
							$logs[] = $msg_erro = Date("Y-m-d H:i:s")." - Erro SQL: 'Atualiza parametros adicionais do Posto Codigo: '.$cod_posto ";
							$logs_erro[] = $update;
							$logs[] = pg_last_error($con);
							$erro = "*";
						}

					}
				}

				if($erro == "*"){
					pg_query($con, 'ROLLBACK');
				}else{
					pg_query($con, 'COMMIT');
				}
			}
		}
	}else{
		$logs[] = $msg_erro = Date("Y-m-d H:i:s")." - Erro SQL: 'Arquivo não encontrado'";
		$logs_erro[] = "Arquivo não encontrado";
		$logs[] = pg_last_error($con);
		$erro = "*";
	}

	/* Grava os Logs */
	if(count($logs) > 0){
    	$file_log = fopen($arquivo_log,"w+");
        	fputs($file_log,implode("\r\n", $logs));
        fclose ($file_log);
    }

    //envia email para HelpDESK

    if(count($logs_erro) > 0){
    	$file_log = fopen($arquivo_err,"w+");
        	fputs($file_log,implode("\r\n", $logs));
        	if(count($logs_erro) > 0){
        		fputs($file_log,"\r\n ####################### SQL ####################### \r\n");
        		fputs($file_log,implode("\r\n", $logs_erro));
        	}
        fclose ($file_log);

	    require_once dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

		$assunto = ucfirst($fabrica_nome) . utf8_decode(': Importa Parametros Adicionais ') . date('d/m/Y');

		$mail = new PHPMailer();
		$mail->IsHTML(true);
		$mail->From = 'guilherme.monteiro@telecontrol.com.br';
		$mail->FromName = 'Telecontrol';

		if (ENV == 'producao') {
			$mail->AddAddress('guilherme.monteiro@telecontrol.com.br');
		} else {
			$mail->AddAddress('guilherme.monteiro@telecontrol.com.br');
		}

		$mail->Subject = $assunto;
		$mail->Body = "Segue anexo arquivo de log Erro do Importa Parametros Adicionais...<br/><br/>";
		$mail->AddAttachment($arquivo_err);


		if (!$mail->Send()) {
			echo 'Erro ao enviar email: ' , $mail->ErrorInfo;
		}
	}

    $phpCron->termino();

}catch (Exception $e) {
	$e->getMessage();
    $msg = "Arquivo: ".__FILE__."\r\n<br />Linha: " . $e->getLine() . "\r\n<br />Descrição do erro: " . $e->getMessage() ."<hr /><br /><br />". implode("<br /><br />", $logs);
    Log::envia_email($data,Date('d/m/Y H:i:s')." - BOSCH - Erro na atualização dos parametros adicionais(importa_parametros_adicionais.php)", $msg);
}

?>
