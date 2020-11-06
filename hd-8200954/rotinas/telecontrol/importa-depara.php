<?php
/**
 *
 * importa-produto.php
 *
 * Importação de produtos Lavor
 *
 */

error_reporting(E_ALL ^ E_NOTICE);

define('ENV', 'producao');
define('DEV_EMAIL', 'william.lopes@telecontrol.com.br');

try {

	#include dirname(__FILE__) . '/../../dbconfig_bc_teste.php';
	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';

	// LAVOR
	$login_fabrica = $argv[1];
	$fabrica_nome = $argv[2];

    $origem_is_tmp = 0;

	function strtim($var)
	{
		if (!empty($var)) {
			$var = trim($var);
			$var = str_replace("'", "\'", $var);
			#$var = str_replace("/", "", $var);
		}

		return $var;
	}

	function logErro($sql, $error_msg)
	{
		$err = "==============================\n\n";
		$err.= $sql . "\n\n";
		$err.= $error_msg . "\n\n";

		return $err;
	}

    $diretorio_origem = '/tmp/' . $fabrica_nome . '/importa-depara';
	
	#$diretorio_origem = '/home/ronald/perl/'.$fabrica_nome.'/entrada';
	$arquivo_origem = 'telecontrol-depara.txt';

	$ftp = '/tmp/'.$fabrica_nome.'/telecontrol-' . $fabrica_nome;

	

	date_default_timezone_set('America/Sao_Paulo');
	$now = date('Ymd_His');

	$log_dir = '/tmp/' . $fabrica_nome . '/logs';
	$arq_log = $log_dir . '/importa-depara-' . $now . '.log';
	$err_log = $log_dir . '/importa-depara-err-' . $now . '.log';

	if (!is_dir($log_dir)) {
		if (!mkdir($log_dir, 0777, true)) {
			throw new Exception("ERRO: Não foi possível criar logs. Falha ao criar diretório: $log_dir");
		}
	}

	$arquivo = $diretorio_origem . '/' . $arquivo_origem;
	if (file_exists($arquivo) and (filesize($arquivo) > 0)) {

		$conteudo = file_get_contents($arquivo);
		$conteudo = explode("\n", $conteudo);

		$nlog = fopen($arq_log, "w");
		$elog = fopen($err_log, "w");

		foreach ($conteudo as $linha) {
			if (!empty($linha)) {
				list (
						$dePeca,
						$paraPeca,
						$expira
					) = explode ("\t",$linha);

				$original = array(
									$dePeca,
									$paraPeca,
									$expira
							);
				$not_null = array($dePeca, $paraPeca);
				
				foreach ($not_null as $value) {
					#$var_dump($value);
					if (!$value) {
						array_push($original, 'erro 1');
						$log = implode("\t", $original);
						fwrite($nlog, $log . "\n");
						continue 2;
					}
				}
				
				$dePeca   = strtim($dePeca);
				$paraPeca = strtim($paraPeca);
				$expira   = strtim($expira);

				$sql = "SELECT *
						FROM   tbl_peca
						WHERE  upper(trim(tbl_peca.referencia)) = upper(trim('{$dePeca}'))
						AND    tbl_peca.fabrica = $login_fabrica;";
				$res = pg_query ($con,$sql);
				
				if(pg_num_rows($res) == 1){
					$idDePeca = pg_fetch_result($res, 0, 'peca');
				}


				if (pg_last_error()) {
					array_push($original, 'erro peca não encontrada');
					$log = implode(";", $original);
					fwrite($nlog, $log . "\n");
					$log_erro = logErro($sql_tabela, pg_last_error());
					fwrite($elog, $log_erro);
					continue;
				}

				$sql = "SELECT *
						FROM   tbl_peca
						WHERE  upper(trim(tbl_peca.referencia)) = upper(trim('{$paraPeca}'))
						AND    tbl_peca.fabrica = $login_fabrica;";
				$res = pg_query ($con,$sql);
				
				if(pg_num_rows($res) == 1){
					$idParaPeca = pg_fetch_result($res, 0, 'peca');
				}
				
				if (pg_last_error()) {
					array_push($original, 'erro peca não encontrada');
					$log = implode(";", $original);
					fwrite($nlog, $log . "\n");

					$log_erro = logErro($sql_tabela, pg_last_error());
					fwrite($elog, $log_erro);
					continue;
				}

				if($idDePeca == $idParaPeca) {
					array_push($original, 'erro peças iguais');
					$log = implode(";", $original);
					fwrite($nlog, $log . "\n");

					$log_erro = logErro($sql_tabela, pg_last_error());
					fwrite($elog, $log_erro);
					continue;
				}

				$sql = " SELECT depara
						FROM tbl_depara
						WHERE peca_para = '{$dePeca}'
						AND   peca_de = '{$paraPeca}'";
				$res = pg_query($con,$sql);
				if(pg_num_rows($res) > 0){
					$id_de_para = pg_fetch_result($res, 0, 'depara');
				}

				if (strlen($expira)==0){
					$expira = "NULL";
				}else{
					$dat = explode ("/", $expira );//tira a barra
					$d = $dat[0];
					$m = $dat[1];
					$y = $dat[2];
					if(!checkdate($m,$d,$y)) $msg_erro .= "Data Inválida";
					if (strlen($msg_erro) == 0) {
						$aux = formata_data($expira);
						$expira_aux = $aux;
						$expira = "'".$aux."'";
					}
				}

				if (strlen($id_de_para) == 0) {
					$sql = "INSERT INTO tbl_depara (
										fabrica     ,
										de          ,
										para        ,
										expira      
									) VALUES (
										{$login_fabrica},
										'{$dePeca}',
										'{$paraPeca}',
										{$expira} 
									)";
				} else {

					$sql = "UPDATE  tbl_depara
		                    SET     de          = '{$dePeca}'  ,
									para        = '{$paraPeca}',
									expira      = {$expira}
							WHERE   tbl_depara.depara = {$id_de_para}
							AND     tbl_depara.fabrica = {$login_fabrica} ";

				}

				$query = pg_query($con, $sql);

				if (pg_last_error()) {
					array_push($original, 'erro');
					$log = implode(";", $original);
					fwrite($nlog, $log . "\n");

					$erro = "==============================\n\n";
					$erro.= $sql . "\n\n";
					$erro.= pg_last_error();
					$erro.= "\n\n";
					fwrite($elog, $erro);
				} else {
					array_push($original, 'ok');
					$log = implode(";", $original);
					fwrite($nlog, $log . "\n");
				}

			}
		}

		fclose($nlog);
		fclose($elog);

		if (filesize($arq_log) > 0) {
			$data_arq_enviar = date('dmy');
			$cmds = "cd $log_dir && cp importa-depara-$now.log depara$data_arq_enviar.txt && zip -r depara$data_arq_enviar.zip depara$data_arq_enviar.txt 1>/dev/null";
			system("$cmds", $retorno);

			$joga_ftp = "cd $log_dir && cp depara$data_arq_enviar.txt $ftp/$fabrica_nome-depara$data_arq_enviar.ret";
			system("$joga_ftp");

			if ($retorno == 0) {

				require_once dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

				$assunto = ucfirst($fabrica_nome) . utf8_decode(': Importação de depara ') . date('d/m/Y');

				$mail = new PHPMailer();
				$mail->IsHTML(true);
				$mail->From = 'william.lopes@telecontrol.com.br';
				$mail->FromName = 'Telecontrol';

				if (ENV == 'producao') {
					$mail->AddAddress('william.lopes@telecontrol.com.br');
				} else {
					$mail->AddAddress(DEV_EMAIL);
				}

				$mail->Subject = $assunto;
				$mail->Body = "Segue anexo arquivo de depara importado na rotina...<br/><br/>";
				$mail->AddAttachment($log_dir . '/depara' . $data_arq_enviar . '.zip', 'depara' . $data_arq_enviar . '.zip');

				unlink($log_dir . '/depara' . $data_arq_enviar . '.txt');
				unlink($log_dir . '/depara' . $data_arq_enviar . '.zip');

			} else {
				echo 'Erro ao compactar arquivo de log: ' , $retorno;
			}
		}

		if (filesize($err_log) > 0) {
			system("cd $log_dir && zip -r importa-depara-err-$now.zip importa-depara-err-$now.log 1>/dev/null", $retorno);

			if ($retorno == 0) {

				require_once dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

				$assunto = ucfirst($fabrica_nome) . utf8_decode(': Erros na Importação de depara ') . date('d/m/Y');

				$mail = new PHPMailer();
				$mail->IsHTML(true);
				$mail->From = 'william.lopes@telecontrol.com.br';
				$mail->FromName = 'Telecontrol';

				if (ENV == 'producao') {
					$mail->AddAddress('william.lopes@telecontrol.com.br');
				} else {
					$mail->AddAddress(DEV_EMAIL);
				}

				$mail->Subject = $assunto;
				$mail->Body = "Segue anexo log de erro na importação de preços...<br/><br/>";
				$mail->AddAttachment($log_dir . '/importa-depara-err-' . $now . '.zip', 'importa-depara-err-' . $now . '.zip');

				if (!$mail->Send()) {
					echo 'Erro ao enviar email: ' , $mail->ErrorInfo;
				} else {
					unlink($log_dir . '/importa-depara-err-' . $now . '.zip');
				}

			} else {
				echo 'Erro ao compactar arquivo de log de erros: ' , $retorno;
			}
		}

		$data_arq_process = date('Ymd');
		system("mv $arquivo /tmp/$fabrica_nome/telecontrol-depara-$data_arq_process.txt");

    }

} catch (Exception $e) {
	echo $e->getMessage();
}

