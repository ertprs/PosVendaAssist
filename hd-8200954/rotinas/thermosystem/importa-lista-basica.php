<?php
/**
 *
 * importa-produto.php
 *
 * Importação de produtos ThermoSystem
 */

error_reporting(E_ALL ^ E_NOTICE);

define('ENV', 'producao');
define('DEV_EMAIL', 'helpdesk@telecontrol.com.br');

try {

	#include dirname(__FILE__) . '/../../dbconfig_bc_teste.php';
	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';

	// THERMOSYSTEM
	$fabrica = 134;
	$fabrica_nome = 'thermosystem';

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

	$diretorio_origem = '/www/cgi-bin/' . $fabrica_nome . '/entrada';
	$arquivo_origem = 'telecontrol-lista-basica.txt';

	$ftp = '/tmp/thermosystem/telecontrol-' . $fabrica_nome;

	if (ENV == 'teste') {
		//$ftp = dirname(__FILE__) . '/../' . $fabrica_nome;
	}

	date_default_timezone_set('America/Sao_Paulo');
	$now = date('Ymd_His');

	$log_dir = '/tmp/' . $fabrica_nome . '/logs';
	$arq_log = $log_dir . '/importa-lista-basica-' . $now . '.log';
	$err_log = $log_dir . '/importa-lista-basica-err-' . $now . '.log';

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
						$referencia_produto,
						$referencia_peca,
						$qtde
					) = explode ("\t",$linha);

				$original = array(
									$referencia_produto,
									$referencia_peca,
									$qtde
							);

				$not_null = array($referencia_produto, $referencia_peca, $qtde);
				
				foreach ($not_null as $value) {
					#var_dump($value);
					if (!$value) {
						array_push($original, 'errox');
						$log = implode("\t", $original);
						fwrite($nlog, $log . "\n");
						continue 2;
					}
				}

				$referencia_produto = strtim($referencia_produto);
				$referencia_peca = strtim($referencia_peca);
				$qtde = str_replace(",",".",$qtde);


				$sql_produto = "SELECT produto FROM tbl_produto
								WHERE tbl_produto.referencia = TRIM('$referencia_produto')
								AND tbl_produto.fabrica_i = $fabrica LIMIT 1";
				$query_produto = pg_query($con, $sql_produto);

				if (pg_last_error()) {
					array_push($original, 'erroy');
					$log = implode(";", $original);
					fwrite($nlog, $log . "\n");

					$log_erro = logErro($sql_produto, pg_last_error());
					fwrite($elog, $log_erro);
					continue;
				}

				if (pg_num_rows($query_produto) == 1) {
					$produto_id = pg_fetch_result($query_produto, 0, 'produto');
				} else {
					array_push($original, 'erroa');
					$log = implode(";", $original);
					fwrite($nlog, $log . "\n");

					continue;
				}

				$sql_peca = "SELECT peca FROM tbl_peca
								WHERE tbl_peca.referencia = TRIM('$referencia_peca')
								AND tbl_peca.fabrica = $fabrica LIMIT 1";
				$query_peca = pg_query($con, $sql_peca);

				if (pg_last_error()) {
					array_push($original, 'erroz');
					$log = implode(";", $original);
					fwrite($nlog, $log . "\n");

					$log_erro = logErro($sql_peca, pg_last_error());
					fwrite($elog, $log_erro);
					continue;
				}

				if (pg_num_rows($query_peca) == 1) {
					$peca_id = pg_fetch_result($query_peca, 0, 'peca');
				} else {
					array_push($original, 'errob');
					$log = implode(";", $original);
					fwrite($nlog, $log . "\n");

					continue;
				}

				$sql_lista_basica = "SELECT tbl_lista_basica.produto,tbl_lista_basica.peca FROM tbl_lista_basica
								WHERE tbl_lista_basica.produto = $produto_id AND tbl_lista_basica.peca = $peca_id
									AND tbl_lista_basica.fabrica = $fabrica";
				$query_lista_basica = pg_query($con, $sql_lista_basica);

				if (pg_last_error()) {
					array_push($original, 'erroc');
					$log = implode(";", $original);
					fwrite($nlog, $log . "\n");

					$log_erro = logErro($sql_lista_basica, pg_last_error());
					fwrite($elog, $log_erro);
					continue;
				}

				if (pg_num_rows($query_lista_basica) == 0) {
					$sql = "INSERT INTO tbl_lista_basica (
											fabrica,
											produto,
											peca,
											qtde
										)VALUES(
											$fabrica,
											$produto_id,
											$peca_id,
											$qtde
										)";
				} else {

					$sql = "UPDATE tbl_lista_basica SET
									qtde = $qtde
								WHERE tbl_lista_basica.produto = $produto_id
								AND   tbl_lista_basica.peca    = $peca_id";

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
			$cmds = "cd $log_dir && cp importa-lista-basica-$now.log lista-basica$data_arq_enviar.txt && zip -r lista-basica$data_arq_enviar.zip produto$data_arq_enviar.txt 1>/dev/null";
			system("$cmds", $retorno);

			$joga_ftp = "cd $log_dir && cp lista-basica$data_arq_enviar.txt $ftp/$fabrica_nome-lista-basica$data_arq_enviar.ret";
			system("$joga_ftp");

			if ($retorno == 0) {

				require_once dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

				$assunto = ucfirst($fabrica_nome) . utf8_decode(': Importação de lista basica ') . date('d/m/Y');

				$mail = new PHPMailer();
				$mail->IsHTML(true);
				$mail->From = 'helpdesk@telecontrol.com.br';
				$mail->FromName = 'Telecontrol';

				if (ENV == 'producao') {
					$mail->AddAddress('marisa.silvana@telecontrol.com.br');
				} else {
					$mail->AddAddress(DEV_EMAIL);
				}

				$mail->Subject = $assunto;
				$mail->Body = "Segue anexo arquivo de lista basica importado na rotina...<br/><br/>";
				$mail->AddAttachment($log_dir . '/lista-basica' . $data_arq_enviar . '.zip', 'lista-basica' . $data_arq_enviar . '.zip');

				unlink($log_dir . '/lista-basica' . $data_arq_enviar . '.txt');
				unlink($log_dir . '/lista-basica' . $data_arq_enviar . '.zip');

			} else {
				echo 'Erro ao compactar arquivo de log: ' , $retorno;
			}
		}

		if (filesize($err_log) > 0) {
			system("cd $log_dir && zip -r importa-lista-basica-err-$now.zip importa-lista-basica-err-$now.log 1>/dev/null", $retorno);

			if ($retorno == 0) {

				require_once dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

				$assunto = ucfirst($fabrica_nome) . utf8_decode(': Erros na importação de lista basica ') . date('d/m/Y');

				$mail = new PHPMailer();
				$mail->IsHTML(true);
				$mail->From = 'helpdesk@telecontrol.com.br';
				$mail->FromName = 'Telecontrol';

				if (ENV == 'producao') {
					$mail->AddAddress('marisa.silvana@telecontrol.com.br');
				} else {
					$mail->AddAddress(DEV_EMAIL);
				}

				$mail->Subject = $assunto;
				$mail->Body = "Segue anexo log de erro na importação de lista basica...<br/><br/>";
				$mail->AddAttachment($log_dir . '/importa-lista-basica-err-' . $now . '.zip', 'importa-lista-basica-err-' . $now . '.zip');

				if (!$mail->Send()) {
					echo 'Erro ao enviar email: ' , $mail->ErrorInfo;
				} else {
					unlink($log_dir . '/importa-lista-basica-err-' . $now . '.zip');
				}

			} else {
				echo 'Erro ao compactar arquivo de log de erros: ' , $retorno;
			}
		}

		$data_arq_process = date('Ymd');
		system("mv $arquivo /tmp/$fabrica_nome/telecontrol-lista-basica-$data_arq_process.txt");

	}

} catch (Exception $e) {
	echo $e->getMessage();
}

