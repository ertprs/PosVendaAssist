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
define('DEV_EMAIL', 'marisa.silvana@telecontrol.com.br');

try {

	#include dirname(__FILE__) . '/../../dbconfig_bc_teste.php';
	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';

	// elginautomacao
	$fabrica = 156;
	$fabrica_nome = 'elginautomacao';

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

	$diretorio_origem = '/var/www/cgi-bin/' . $fabrica_nome . '/entrada';
//	$diretorio_origem = '/mnt/home/fabiano/public_html/assist/rotinas/'.$fabrica_nome; //  teste local
	$arquivo_origem   = 'telecontrol-cliente-admin.txt';

	$ftp = '/tmp/' . $fabrica_nome . '/';
	//$ftp = '/home/fabiano/telecontrol'; //  teste local

	if (ENV == 'teste') {
		//$ftp = dirname(__FILE__) . '/../' . $fabrica_nome;
	}

	date_default_timezone_set('America/Sao_Paulo');
	$now = date('Ymd_His');

	$log_dir = '/tmp/' . $fabrica_nome . '/logs';
	$arq_log = $log_dir . '/importa-posto-' . $now . '.log';
	$err_log = $log_dir . '/importa-posto-err-' . $now . '.log';

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
						$fantasia,
						$cnpj,
						$ie,
						$endereco,
						$numero,
						$complemento,
						$bairro,
						$cep,
						$cidade,
						$estado,
						$email,
						$telefone,
						$fax,
						$contato
					) = explode ("\t",$linha);

				$original = array(
									$razao,
									$fantasia,
									$cnpj,
									$ie,
									$endereco,
									$numero,
									$complemento,
									$bairro,
									$cep,
									$cidade,
									$estado,
									$email,
									$telefone,
									$fax,
									$contato
								);

				echo $razao = strtim($razao);
				echo $fantasia = strtim($fantasia);
				echo $cnpj = strtim($cnpj);
				echo $ie = strtim($ie);
				$endereco = strtim($endereco);
				$numero = strtim($numero);
				$complemento = strtim($complemento);
				$bairro = strtim($bairro);
				$cep = strtim($cep);
				$cidade = strtim($cidade);
				$estado = strtim($estado);
				$email = strtim($email);
				$telefone = strtim($telefone);
				$fax = strtim($fax);
				#$fax_array		= explode("-",$fax);
				#$fax_array_1	= str_replace('000','',$fax_array[0]);
				#if($fax_array[1][0] == '0') {
				#	$fax_array_2 = substr($fax_array[1],1);
				#}else {
				#	$fax_array_2 = $fax_array[1];
				#}
				#$fax = $fax_array_1."-".$fax_array_2;

				$contato = strtim($contato);
				$razao = cortaStr($razao, 100);
				$fantasia = cortaStr($fantasia, 50);
				$cnpj = adicionalTrim($cnpj, 14);
				$ie = adicionalTrim($ie);
				$endereco = cortaStr($endereco, 50);
				$numero =adicionalTrim($numero);
				$numero =cortaStr($numero,10);
				$complemento = adicionalTrim($complemento);
				$complemento = cortaStr($complemento,20);
				$bairro = cortaStr($bairro, 20);
				$cep = cortaStr($cep, 8);
				$cidade = cortaStr($cidade, 30);
				$estado = cortaStr($estado, 2);
				$email = strtolower(cortaStr($email, 50));
				$telefone = cortaStr($telefone, 30);
				$fax = cortaStr($fax, 30);
				$contato = cortaStr($contato, 30);
				

				
				$valida_cpnj = pg_query($con, "SELECT fn_valida_cnpj_cpf('$cnpj')");
				if (pg_last_error()) {
					array_push($original, 'erro cnpj');
					$log = implode(";", $original);
					fwrite($nlog, $log . "\n");

					$log_erro = logErro("SELECT fn_valida_cnpj_cpf('$cnpj')", pg_last_error());
					fwrite($elog, $log_erro);
					continue;
				}

				$sql_posto = "SELECT tbl_admin_cliente.cliente_admin FROM tbl_cliente_admin WHERE tbl_cliente_admin.cnpj = '$cnpj'";
				$query_posto = pg_query($con, $sql_posto);

				if (pg_num_rows($query_posto) == 0) {
					echo $sql = "INSERT INTO tbl_cliente_admin (
											nome,
											fantasia,
											codigo,
											cnpj,
											ie,
											endereco,
											numero,
											complemento,
											bairro,
											cep,
											cidade,
											estado,
											email,
											fone,
											contato,
											codigo_representante,
											fabrica
										) VALUES (
											(E'$razao'),
											(E'$fantasia'),
											'$cnpj',
											'$cnpj',
											'$ie',
											'$endereco',
											'$numero',
											'$complemento',
											'$bairro',
											'$cep',
											'$cidade',
											'$estado',
											'$email',
											'$telefone',
											'$contato',
											'$cnpj',
											$fabrica
										)";
					$query = pg_query($con, $sql);

					if (pg_last_error()) {
						array_push($original, 'erro insert');
						$log = implode(";", $original);
						fwrite($nlog, $log . "\n");

						$log_erro = logErro($sql, pg_last_error());
						fwrite($elog, $logErro);
						continue;
					}

					$query_posto_id = pg_query($con, "SELECT currval ('seq_posto') AS seq_posto");
					$posto = pg_fetch_result($query_posto_id, 0, 'seq_posto');

				} else {
					$posto = pg_fetch_result($query_posto, 0, 'posto');
					echo $sql = "UPDATE tbl_cliente_admin SET estado='$estado' WHERE tbl_cliente_admin.cliente_admin = $posto";
					$query = pg_query($con, $sql);
 					if (pg_last_error()) {
                                        	array_push($original, 'erro');
	                                        $log = implode(";", $original);
        	                                fwrite($nlog, $log . "\n");

                	                        $log_erro = logErro($sql, pg_last_error());
                        	                fwrite($elog, $logErro);
                                	        continue;
                                	}
				}

			}
		}

		fclose($nlog);
		fclose($elog);

		if (filesize($arq_log) > 0) {
			$data_arq_enviar = date('dmY');
			$cmds = "cd $log_dir && cp importa-posto-$now.log posto$data_arq_enviar.txt && zip -r posto$data_arq_enviar.zip posto$data_arq_enviar.txt 1>/dev/null";
			system("$cmds", $retorno);

			$joga_ftp = "cd $log_dir && cp posto$data_arq_enviar.txt $ftp/$fabrica_nome-postos-$data_arq_enviar.ret";
			system("$joga_ftp");

			if ($retorno == 0) {

				require_once dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

				$assunto = ucfirst($fabrica_nome) . utf8_decode(': Importação de postos ') . date('d/m/Y');

				$mail = new PHPMailer();
				$mail->IsHTML(true);
				$mail->From = 'helpdesk@telecontrol.com.br';
				//$mail->From = 'fabiano.souza@telecontrol.com.br'; //  teste local
				$mail->FromName = 'Telecontrol';

				if (ENV == 'producao') {
					$mail->AddAddress('marisa.silvana@telecontrol.com.br');
					//$mail->AddAddress('fabiano.souza@telecontrol.com.br'); //  teste local
				} else {
					$mail->AddAddress(DEV_EMAIL);
				}

				$mail->Subject = $assunto;
				$mail->Body = "Segue anexo arquivo de postos importado na rotina...<br/><br/>";
				$mail->AddAttachment($log_dir . '/posto' . $data_arq_enviar . '.zip', 'posto' . $data_arq_enviar . '.zip');

				unlink($log_dir . '/posto' . $data_arq_enviar . '.txt');
				unlink($log_dir . '/posto' . $data_arq_enviar . '.zip');

			} else {
				echo 'Erro ao compactar arquivo de log: ' , $retorno;
			}
		}

		if (filesize($err_log) > 0) {
			system("cd $log_dir && zip -r importa-posto-err-$now.zip importa-posto-err-$now.log 1>/dev/null", $retorno);

			if ($retorno == 0) {

				require_once dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

				$assunto = ucfirst($fabrica_nome) . utf8_decode(': Erros na importação de postos ') . date('d/m/Y');

				$mail = new PHPMailer();
				$mail->IsHTML(true);
				$mail->From = 'helpdesk@telecontrol.com.br';
				//$mail->From = 'fabiano.souza@telecontrol.com.br'; //  teste local
				$mail->FromName = 'Telecontrol';

				if (ENV == 'producao') {
					$mail->AddAddress('marisa.silbvana@telecontrol.com.br');
					//$mail->AddAddress('fabiano.souza@telecontrol.com.br'); //  teste local
				} else {
					$mail->AddAddress(DEV_EMAIL);
				}

				$mail->Subject = $assunto;
				$mail->Body = "Segue anexo log de erro na importação de postos...<br/><br/>";
				$mail->AddAttachment($log_dir . '/importa-posto-err-' . $now . '.zip', 'importa-posto-err-' . $now . '.zip');

				if (!$mail->Send()) {
					echo 'Erro ao enviar email: ' , $mail->ErrorInfo;
				} else {
					unlink($log_dir . '/importa-posto-err-' . $now . '.zip');
				}

			} else {
				echo 'Erro ao compactar arquivo de log de erros: ' , $retorno;
			}
		}

		$data_arq_process = date('Ymd');
		system("mv $arquivo /tmp/$fabrica_nome/posto-$data_arq_process.txt");
		
	}

} catch (Exception $e) {
	echo $e->getMessage();
}
