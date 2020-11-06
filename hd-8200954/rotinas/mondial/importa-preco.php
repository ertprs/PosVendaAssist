<?php
/**
 *
 * importa-produto.php
 *
 * Importação de produtos v8
 *
 */

error_reporting(E_ALL ^ E_NOTICE);

define('ENV', 'producao');
define('DEV_EMAIL', 'marisa.silvana@telecontrol.com.br');

try {

	include dirname(__FILE__) . '/../../dbconfig_bc_teste.php';
	#include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';

	// mondial
	$fabrica = 151;
	$fabrica_nome = 'mondial';

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

	$diretorio_origem = '/www/cgi-bin/' . $fabrica_nome . '/entrada';
	$arquivo_origem = 'telecontrol-preco.txt';

	$ftp = '/tmp/' . $fabrica_nome . '/';

	if (ENV == 'teste') {
		//$ftp = dirname(__FILE__) . '/../' . $fabrica_nome;
	}

	date_default_timezone_set('America/Sao_Paulo');
	$now = date('Ymd_His');

	$log_dir = '/tmp/' . $fabrica_nome . '/logs';
	$arq_log = $log_dir . '/importa-preco-' . $now . '.log';
	$err_log = $log_dir . '/importa-preco-err-' . $now . '.log';

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
						$sigla_tabela,
						$referencia_peca,
						$preco_peca
					) = explode ("\t",$linha);

				$original = array(
									$sigla_tabela,
									$referencia_peca,
									$preco_peca
							);

				$not_null = array($sigla_tabela, $referencia_peca, $preco_peca);
				foreach ($not_null as $value) {
					#$var_dump($value);
					if (!$value) {
						array_push($original, 'erro 1');
						$log = implode("\t", $original);
						fwrite($nlog, $log . "\n");
						continue 2;
					}
				}

				$sigla_tabela = strtim($sigla_tabela);
				$referencia_peca = strtim($referencia_peca);
				$preco_peca = strtim($preco_peca);

				if ($preco_peca == 0 ){
					array_push($original, 'erro-sem preco');
					$log = implode(";", $original);
                                        fwrite($nlog, $log . "\n");

                                        $log_erro = logErro('sem preço', pg_last_error());
                                        fwrite($elog, $log_erro);
                                        continue;
				}

				$sql_tabela = "SELECT tabela FROM tbl_tabela
								WHERE tbl_tabela.sigla_tabela = TRIM('$sigla_tabela')
								AND tbl_tabela.fabrica = $fabrica LIMIT 1";
				$query_tabela = pg_query($con, $sql_tabela);

				if (pg_last_error()) {
					array_push($original, 'erro sem tabela');
					$log = implode(";", $original);
					fwrite($nlog, $log . "\n");

					$log_erro = logErro($sql_tabela, pg_last_error());
					fwrite($elog, $log_erro);
					continue;
				}

				if (pg_num_rows($query_tabela) == 1) {
					$tabela_id = pg_fetch_result($query_tabela, 0, 'tabela');
				} else {
					array_push($original, 'erro 3');
					$log = implode(";", $original);
					fwrite($nlog, $log . "\n");

					continue;
				}

				$sql_peca = "SELECT peca FROM tbl_peca
								WHERE tbl_peca.referencia = TRIM('$referencia_peca')
								AND tbl_peca.fabrica = $fabrica LIMIT 1";
				$query_peca = pg_query($con, $sql_peca);

				if (pg_last_error()) {
					array_push($original, 'erro sem peça cadastrada');
					$log = implode(";", $original);
					fwrite($nlog, $log . "\n");

					$log_erro = logErro($sql_peca, pg_last_error());
					fwrite($elog, $log_erro);
					continue;
				}

				if (pg_num_rows($query_peca) == 1) {
					$peca_id = pg_fetch_result($query_peca, 0, 'peca');
				} else {
					array_push($original, 'erro 5');
					$log = implode(";", $original);
					fwrite($nlog, $log . "\n");

					continue;
				}

				$sql_tabela_item = "SELECT tbl_tabela_item.peca FROM tbl_tabela_item
								WHERE tbl_tabela_item.tabela = $tabela_id AND tbl_tabela_item.peca = $peca_id";
				$query_tabela_item = pg_query($con, $sql_tabela_item);

				if (pg_last_error()) {
					array_push($original, 'erro 6');
					$log = implode(";", $original);
					fwrite($nlog, $log . "\n");

					$log_erro = logErro($sql_tabela_item, pg_last_error());
					fwrite($elog, $log_erro);
					continue;
				}

				print "peca: $peca_id";

				if (pg_num_rows($query_tabela_item) == 0) {
					 $sql = "INSERT INTO tbl_tabela_item (
											tabela,
											peca,
											preco
										)VALUES(
											$tabela_id,
											$peca_id,
											$preco_peca
										)";
				} else {

					$sql = "UPDATE tbl_tabela_item SET
									preco = $preco_peca
								WHERE tbl_tabela_item.tabela = $tabela_id
								AND   tbl_tabela_item.peca   = $peca_id";

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
			$cmds = "cd $log_dir && cp importa-preco-$now.log preco$data_arq_enviar.txt && zip -r preco$data_arq_enviar.zip preco$data_arq_enviar.txt 1>/dev/null";
			system("$cmds", $retorno);

			$joga_ftp = "cd $log_dir && cp preco$data_arq_enviar.txt $ftp/$fabrica_nome-preco$data_arq_enviar.ret";
			system("$joga_ftp");

			if ($retorno == 0) {

				require_once dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

				$assunto = ucfirst($fabrica_nome) . utf8_decode(': Importação de preco ') . date('d/m/Y');

				$mail = new PHPMailer();
				$mail->IsHTML(true);
				$mail->From = 'marisa.silvana@telecontrol.com.br';
				$mail->FromName = 'Telecontrol';

				if (ENV == 'producao') {
					$mail->AddAddress('marisa.silvana@telecontrol.com.br');
				} else {
					$mail->AddAddress(DEV_EMAIL);
				}

				$mail->Subject = $assunto;
				$mail->Body = "Segue anexo arquivo de preco importado na rotina...<br/><br/>";
				$mail->AddAttachment($log_dir . '/preco' . $data_arq_enviar . '.zip', 'preco' . $data_arq_enviar . '.zip');

				unlink($log_dir . '/preco' . $data_arq_enviar . '.txt');
				unlink($log_dir . '/preco' . $data_arq_enviar . '.zip');

			} else {
				echo 'Erro ao compactar arquivo de log: ' , $retorno;
			}
		}

		if (filesize($err_log) > 0) {
			system("cd $log_dir && zip -r importa-preco-err-$now.zip importa-preco-err-$now.log 1>/dev/null", $retorno);

			if ($retorno == 0) {

				require_once dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

				$assunto = ucfirst($fabrica_nome) . utf8_decode(': Erros na Importação de preco ') . date('d/m/Y');

				$mail = new PHPMailer();
				$mail->IsHTML(true);
				$mail->From = 'marisa.silvana@telecontrol.com.br';
				$mail->FromName = 'Telecontrol';

				if (ENV == 'producao') {
					$mail->AddAddress('marisa.silvana@telecontrol.com.br');
				} else {
					$mail->AddAddress(DEV_EMAIL);
				}

				$mail->Subject = $assunto;
				$mail->Body = "Segue anexo log de erro na importação de preços...<br/><br/>";
				$mail->AddAttachment($log_dir . '/importa-preco-err-' . $now . '.zip', 'importa-preco-err-' . $now . '.zip');

				if (!$mail->Send()) {
					echo 'Erro ao enviar email: ' , $mail->ErrorInfo;
				} else {
					unlink($log_dir . '/importa-preco-err-' . $now . '.zip');
				}

			} else {
				echo 'Erro ao compactar arquivo de log de erros: ' , $retorno;
			}
		}

		$data_arq_process = date('Ymd');
		system("mv $arquivo /tmp/$fabrica_nome/telecontrol-preco-$data_arq_process.txt");

	}

} catch (Exception $e) {
	echo $e->getMessage();
}

