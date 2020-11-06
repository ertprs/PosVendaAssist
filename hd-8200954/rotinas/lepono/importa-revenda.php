<?php
/**
 *
 * importa-posto.php
 *
 * Importação de postos imbera
 *
 */

error_reporting(E_ALL ^ E_NOTICE);

define('ENV', 'producao');
define('DEV_EMAIL', 'ronald.santos@telecontrol.com.br');

try {

	#include dirname(__FILE__) . '/../../dbconfig_bc_teste.php';
	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';

	// cristofoli
	$fabrica = 161;
	$fabrica_nome = 'cristofoli';

	function strtim($var)
	{
		if (!empty($var)) {
			$var = trim($var);
			$var = str_replace("'", "\'", $var);
			$var = str_replace("/", "", $var);
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

	function cortaStr($str, $len)
	{
		return substr($str, 0, $len);
	}

	function adicionalTrim($str, $len = 0)
	{
		$str = str_replace(".", "", $str);
		$str = str_replace("-", "", $str);

		if ($len != 0) {
			$str = cortaStr($str, $len);
		}

		return $str;
	}

	$diretorio_origem = '/home/ronald';
//	$diretorio_origem = '/mnt/home/fabiano/public_html/assist/rotinas/'.$fabrica_nome; //  teste local
	$arquivo_origem   = 'telecontrol-revenda.txt';

	$ftp = '/tmp/' . $fabrica_nome . '/';
	//$ftp = '/home/fabiano/telecontrol'; //  teste local

	if (ENV == 'teste') {
		//$ftp = dirname(__FILE__) . '/../' . $fabrica_nome;
	}

	date_default_timezone_set('America/Sao_Paulo');
	$now = date('Ymd_His');

	$log_dir = '/tmp/' . $fabrica_nome . '/logs';
	$arq_log = $log_dir . '/importa-revenda-' . $now . '.log';
	$err_log = $log_dir . '/importa-revenda-err-' . $now . '.log';

	if (!is_dir($log_dir)) {
		if (!mkdir($log_dir, 0777, true)) {
			throw new Exception("ERRO: Não foi possível criar logs. Falha ao criar diretório: $log_dir");
		}
	}

	$arquivo = $diretorio_origem . '/' . $arquivo_origem;

	if (ENV == 'teste') {
		$arquivo = '../' . $fabrica_nome . '/' . $arquivo_origem;
	}

	if (file_exists($arquivo) and (filesize($arquivo) > 0)) {
		$conteudo = file_get_contents($arquivo);
		$conteudo = explode("\n", $conteudo);

		$nlog = fopen($arq_log, "w");
		$elog = fopen($err_log, "w");

		foreach ($conteudo as $linha) {
			if (!empty($linha)) {
				list (
						$razao,
						$cnpj,
						$endereco,
						$numero,
						$complemento,
						$bairro,
						$cep,
						$cidade,
						$estado,
						$telefone
					) = explode ("\t",$linha);

				$original = array(
									$razao,
									$cnpj,
									$endereco,
									$numero,
									$complemento,
									$bairro,
									$cep,
									$cidade,
									$estado,
									$telefone

								);

				echo $razao = strtim($razao);
				echo $cnpj = strtim($cnpj);
				$endereco = strtim($endereco);
				$numero = strtim($numero);
				$complemento = strtim($complemento);
				$bairro = strtim($bairro);
				$cep = strtim($cep);
				$cidade = strtim($cidade);
				$estado = strtim($estado);
				$telefone = strtim($telefone);

				$razao = cortaStr($razao, 60);
				$cnpj = adicionalTrim($cnpj, 14);
				$endereco = cortaStr($endereco, 50);
				$numero =adicionalTrim($numero);
				$complemento = adicionalTrim($complemento);
				$bairro = cortaStr($bairro, 20);
				$cep = cortaStr($cep, 8);
				$cidade = cortaStr($cidade, 30);
				$estado = cortaStr($estado, 2);
				$telefone = cortaStr($telefone, 30);

				$valida_cpnj = pg_query($con, "SELECT fn_valida_cnpj_cpf('$cnpj')");
				if (pg_last_error()) {
					array_push($original, 'erro cnpj');
					$log = implode(";", $original);
					fwrite($nlog, $log . "\n");

					$log_erro = logErro("SELECT fn_valida_cnpj_cpf('$cnpj')", pg_last_error());
					fwrite($elog, $log_erro);
					continue;
				}

				$sql = "SELECT cidade FROM tbl_cidade WHERE nome = '$cidade' AND estado = '$estado'";
				$res_cidade = pg_query($con,$sql);
				$cidade = pg_fetch_result($res_cidade,0,0);

				$sql_posto = "SELECT tbl_revenda.revenda FROM tbl_revenda WHERE tbl_revenda.cnpj = '$cnpj'";
				$query_posto = pg_query($con, $sql_posto);

				if (pg_num_rows($query_posto) == 0 AND strlen($cidade) > 0) {
					echo $sql = "INSERT INTO tbl_revenda (
											nome,
											cnpj,
											endereco,
											numero,
											complemento,
											bairro,
											cep,
											cidade,
											fone
										) VALUES (
											(E'$razao'),
											'$cnpj',
											'$endereco',
											'$numero',
											'$complemento',
											'$bairro',
											'$cep',
											'$cidade',
											'$telefone'
										)";
					$query = pg_query($con, $sql);

					if (pg_last_error()) {
						echo pg_last_error();
						array_push($original, 'erro insert');
						$log = implode(";", $original);
						fwrite($nlog, $log . "\n");

						$log_erro = logErro($sql, pg_last_error());
						fwrite($elog, $logErro);
						continue;
					}

					$posto = pg_fetch_result($query_posto_id, 0, 'seq_posto');

				}
			}
		}

		fclose($nlog);
		fclose($elog);

		if (filesize($arq_log) > 0) {
			$data_arq_enviar = date('dmY');
			$cmds = "cd $log_dir && cp importa-revenda-$now.log revena$data_arq_enviar.txt && zip -r revenda$data_arq_enviar.zip revenda$data_arq_enviar.txt 1>/dev/null";
			system("$cmds", $retorno);

			$joga_ftp = "cd $log_dir && cp revenda$data_arq_enviar.txt $ftp/$fabrica_nome-revendas-$data_arq_enviar.ret";
			system("$joga_ftp");

			if ($retorno == 0) {

				require_once dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

				$assunto = ucfirst($fabrica_nome) . utf8_decode(': Importação de revendas ') . date('d/m/Y');

				$mail = new PHPMailer();
				$mail->IsHTML(true);
				$mail->From = 'helpdesk@telecontrol.com.br';
				//$mail->From = 'fabiano.souza@telecontrol.com.br'; //  teste local
				$mail->FromName = 'Telecontrol';

				if (ENV == 'producao') {
					$mail->AddAddress('ronald.santos@telecontrol.com.br');
					//$mail->AddAddress('fabiano.souza@telecontrol.com.br'); //  teste local
				} else {
					$mail->AddAddress(DEV_EMAIL);
				}

				$mail->Subject = $assunto;
				$mail->Body = "Segue anexo arquivo de postos importado na rotina...<br/><br/>";
				$mail->AddAttachment($log_dir . '/revenda' . $data_arq_enviar . '.zip', 'revenda' . $data_arq_enviar . '.zip');

				unlink($log_dir . '/revenda' . $data_arq_enviar . '.txt');
				unlink($log_dir . '/revenda' . $data_arq_enviar . '.zip');

			} else {
				echo 'Erro ao compactar arquivo de log: ' , $retorno;
			}
		}

		if (filesize($err_log) > 0) {
			system("cd $log_dir && zip -r importa-revenda-err-$now.zip importa-revenda-err-$now.log 1>/dev/null", $retorno);

			if ($retorno == 0) {

				require_once dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

				$assunto = ucfirst($fabrica_nome) . utf8_decode(': Erros na importação de revendas ') . date('d/m/Y');

				$mail = new PHPMailer();
				$mail->IsHTML(true);
				$mail->From = 'helpdesk@telecontrol.com.br';
				//$mail->From = 'fabiano.souza@telecontrol.com.br'; //  teste local
				$mail->FromName = 'Telecontrol';

				if (ENV == 'producao') {
					$mail->AddAddress('ronald.silbvana@telecontrol.com.br');
					//$mail->AddAddress('fabiano.souza@telecontrol.com.br'); //  teste local
				} else {
					$mail->AddAddress(DEV_EMAIL);
				}

				$mail->Subject = $assunto;
				$mail->Body = "Segue anexo log de erro na importação de revendas...<br/><br/>";
				$mail->AddAttachment($log_dir . '/importa-revenda-err-' . $now . '.zip', 'importa-revenda-err-' . $now . '.zip');

				if (!$mail->Send()) {
					echo 'Erro ao enviar email: ' , $mail->ErrorInfo;
				} else {
					unlink($log_dir . '/importa-revenda-err-' . $now . '.zip');
				}

			} else {
				echo 'Erro ao compactar arquivo de log de erros: ' , $retorno;
			}
		}

		$data_arq_process = date('Ymd');
		system("mv $arquivo /tmp/$fabrica_nome/revenda-$data_arq_process.txt");
		
	}

} catch (Exception $e) {
	echo $e->getMessage();
}
