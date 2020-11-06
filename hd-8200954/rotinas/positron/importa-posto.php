<?php
/**
 *
 * importa-posto.php
 *
 * Importação de postos positron
 *
 */

error_reporting(E_ALL ^ E_NOTICE);

define('ENV', 'producao');
define('DEV_EMAIL', 'marisa.silvana@telecontrol.com.br');

try {

	#include dirname(__FILE__) . '/../../dbconfig_bc_teste.php';
	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';

	// positron
	$fabrica = 153;
	$fabrica_nome = 'positron';

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
	$arquivo_origem   = 'telecontrol-posto.txt';

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
						$codigo_posto,
						$razao,
						$nome_fantasia,
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
						$contato,
						$interior_capital
					) = explode ("\t",$linha);

				$original = array(
									$codigo_posto,
									$razao,
									$nome_fantasia,
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
									$contato,
									$interior_capital
								);

				echo $codigo_posto = strtim($codigo_posto);
				echo $razao = strtim($razao);
				echo $nome_fantasia = strtim($nome_fantasia);
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

				$capital_interior    = strtim($capital_interior);
				$contato = strtim($contato);
				$codigo_posto = adicionalTrim($codigo_posto);
				$razao = cortaStr($razao, 60);
				$nome_fantasia = cortaStr($nome_fantasia, 60);
				$cnpj = adicionalTrim($cnpj, 14);
				$ie = adicionalTrim($ie);
				$endereco = cortaStr($endereco, 50);
				$numero =adicionalTrim($numero);
				$complemento = adicionalTrim($complemento);
				$bairro = cortaStr($bairro, 20);
				$cep = cortaStr($cep, 8);
				$cidade = cortaStr($cidade, 30);
				$estado = cortaStr($estado, 2);
				$email = strtolower(cortaStr($email, 50));
				$telefone = cortaStr($telefone, 30);
				$fax = cortaStr($fax, 30);
				$contato = cortaStr($contato, 30);
				

				
				if ($cnpj =='Capital'){
					echo 'CNPJ INVALIDO';
					echo $cnpj;
					echo $razao;
					echo $codigo_posto;
					exit;
				};
	
				$valida_cpnj = pg_query($con, "SELECT fn_valida_cnpj_cpf('$cnpj')");
				if (pg_last_error()) {
					array_push($original, 'erro cnpj');
					$log = implode(";", $original);
					fwrite($nlog, $log . "\n");

					$log_erro = logErro("SELECT fn_valida_cnpj_cpf('$cnpj')", pg_last_error());
					fwrite($elog, $log_erro);
					continue;
				}

				$sql_posto = "SELECT tbl_posto.posto FROM tbl_posto WHERE tbl_posto.cnpj = '$cnpj'";
				$query_posto = pg_query($con, $sql_posto);

				if (pg_num_rows($query_posto) == 0) {
					$sql = "INSERT INTO tbl_posto (
											nome,
											nome_fantasia,
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
											fax,
											contato,
											capital_interior
										) VALUES (
											(E'$razao'),
											(E'$nome_fantasia'),
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
											'$fax',
											'$contato',
											'$capital_interior'
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
					$sql = "UPDATE tbl_posto SET estado='$estado' WHERE tbl_posto.posto = $posto";
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

				$sql = "SELECT 
						    tbl_posto_fabrica.posto
						FROM   tbl_posto_fabrica
						WHERE  tbl_posto_fabrica.posto   = $posto
						AND    tbl_posto_fabrica.fabrica = $fabrica";
				$query = pg_query($con, $sql);

				if (pg_last_error()) {
					array_push($original, 'erro posto_fabrica');
					$log = implode(";", $original);
					fwrite($nlog, $log . "\n");

					$log_erro = logErro($sql, pg_last_error());
					fwrite($elog, $logErro);
					continue;
				}

				if (pg_num_rows($query) == 0) {
					$sql = "INSERT INTO tbl_posto_fabrica (
												posto,
												fabrica,
												senha,
												tipo_posto,
												login_provisorio,
												codigo_posto,
												credenciamento,
												contato_fone_comercial,
												contato_fax,
												contato_endereco ,
												contato_numero,
												contato_complemento,
												contato_bairro,
												contato_cep,
												contato_cidade,
												contato_estado,
												contato_email,
												nome_fantasia,
												contato_nome
											) VALUES (
												$posto,
												$fabrica,
												'',
												500,
												null,
												'$codigo_posto',
												'DESCREDENCIADO',
												'$telefone',
												'$fax',
												'$endereco',
												'$numero',
												'$complemento',
												(E'$bairro'),
												'$cep',
												(E'$cidade'),
												'$estado',
												'$email',
												(E'$nome_fantasia'),
												(E'$contato')
											)";
				} else {
					$sql = "UPDATE tbl_posto_fabrica SET
										codigo_posto = '$codigo_posto',
										contato_endereco = '$endereco',
										contato_bairro = (E'$bairro'),
										contato_cep = '$cep',
										contato_cidade = (E'$cidade'),
										contato_estado = '$estado',
										contato_fone_comercial = '$telefone',
										contato_fax = '$fax',
										nome_fantasia = (E'$nome_fantasia'),
										contato_email = '$email'
								WHERE tbl_posto_fabrica.posto = $posto
								AND tbl_posto_fabrica.fabrica = $fabrica";
				}

				$query = pg_query($con, $sql);

				if (pg_last_error()) {
					array_push($original, 'erro insert/update posto_fabrica');
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
