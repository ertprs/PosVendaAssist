<?php
/**
 *
 * importa-produto.php
 *
 * Importação de produtos saintgobain
 *
 * @author  Ronald Santos
 * @version 2013.08.05
 *
 */

error_reporting(E_ALL ^ E_NOTICE);

define('ENV', 'producao');
define('DEV_EMAIL', 'marisa.silvana@telecontrol.com.br');

try {

	#include dirname(__FILE__) . '/../../dbconfig_bc_teste.php';
	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';

	// saintgobain
	$fabrica = 125;
	$fabrica_nome = 'saintgobain';

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
	$arquivo_origem = 'telecontrol-produto.txt';

	$ftp = '/tmp/saintgobain/telecontrol-' . $fabrica_nome;

	if (ENV == 'teste') {
		//$ftp = dirname(__FILE__) . '/../' . $fabrica_nome;
	}

	date_default_timezone_set('America/Sao_Paulo');
	$now = date('Ymd_His');

	$log_dir = '/tmp/' . $fabrica_nome . '/logs';
	$arq_log = $log_dir . '/importa-produto-' . $now . '.log';
	$err_log = $log_dir . '/importa-produto-err-' . $now . '.log';

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
						$referencia,
						$descricao,
						$codigo_linha,
						$codigo_familia,
						$linha_aux,
						$origem,
						$voltagem,
						$garantia,
						$mao_de_obra,
						$mao_de_obra_admin,
						$numero_serie_obrigatorio
					) = explode ("\t",$linha);

				$original = array(
									$referencia,
									$descricao,
									$codigo_linha,
									$codigo_familia,
									$linha_aux,
									$origem,
									$voltagem,
									$garantia,
									$mao_de_obra,
									$mao_de_obra_admin,
									$numero_serie_obrigatorio
							);

				if ($origem == 0) {
                                        $origem='1';
                                }

				$not_null = array($referencia, $descricao, $codigo_linha, $codigo_familia, $garantia);
				foreach ($not_null as $value) {
					if (!$value) {
						array_push($original, 'erro');
						$log = implode("\t", $original);
						fwrite($nlog, $log . "\n");
						continue 2;
					}
				}

				$referencia = strtim($referencia);
				$descricao = strtim($descricao);
				$codigo_linha = strtim($codigo_linha);
				$codigo_familia = strtim($codigo_familia);
				$linha_aux = strtim($linha_aux);
				$origem = strtim($origem);
				$voltagem = strtim($voltagem);
				$garantia = strtim($garantia);
				$mao_de_obra = strtim($mao_de_obra);
				$mao_de_obra_admin = strtim($mao_de_obra_admin);
				$numero_serie_obrigatorio = strtim($numero_serie_obrigatorio);

				if ($mao_de_obra) {
					$mao_de_obra = str_replace(",", ".", $mao_de_obra);
				} else {
					$mao_de_obra = 0;
				}

				if ($mao_de_obra_admin) {
					$mao_de_obra_admin = str_replace(",", ".", $mao_de_obra_admin);
				} else {
					$mao_de_obra_admin = 0;
				}

				if ($numero_serie_obrigatorio == 't') {
					$numero_serie_obrigatorio = "true";
				} else {
					$numero_serie_obrigatorio = "false";
				}

				if (!empty($linha_aux)) { // Marcas.. HD 806096

					$sql = "SELECT marca
							FROM tbl_marca
							WHERE fabrica = $fabrica
							AND codigo_marca::integer = '$linha_aux'";

					$res = pg_query($con,$sql);

					if (pg_num_rows($res)) {
						$id_marca = pg_result($res,0,0);
					} else {
						$id_marca = 'null';
					}

				} else {
					$id_marca = 'null';
				}

				$sql_linha = "SELECT linha FROM tbl_linha
								WHERE tbl_linha.codigo_linha = TRIM('$codigo_linha')
								AND tbl_linha.fabrica = $fabrica LIMIT 1";
				$query_linha = pg_query($con, $sql_linha);

				if (pg_last_error()) {
					array_push($original, 'erro');
					$log = implode(";", $original);
					fwrite($nlog, $log . "\n");

					$log_erro = logErro($sql_linha, pg_last_error());
					fwrite($elog, $log_erro);
					continue;
				}

				if (pg_num_rows($query_linha) == 1) {
					$linha_id = pg_fetch_result($query_linha, 0, 'linha');
				} else {
					array_push($original, 'erro');
					$log = implode(";", $original);
					fwrite($nlog, $log . "\n");

					continue;
				}

				$sql_familia = "SELECT familia FROM tbl_familia
								WHERE tbl_familia.codigo_familia = TRIM('$codigo_familia')
								AND tbl_familia.fabrica = $fabrica LIMIT 1";
				$query_familia = pg_query($con, $sql_familia);

				if (pg_last_error()) {
					array_push($original, 'erro');
					$log = implode(";", $original);
					fwrite($nlog, $log . "\n");

					$log_erro = logErro($sql_familia, pg_last_error());
					fwrite($elog, $log_erro);
					continue;
				}

				if (pg_num_rows($query_familia) == 1) {
					$familia_id = pg_fetch_result($query_familia, 0, 'familia');
				} else {
					array_push($original, 'erro');
					$log = implode(";", $original);
					fwrite($nlog, $log . "\n");

					continue;
				}

				$sql_produto = "SELECT tbl_produto.produto FROM tbl_produto JOIN tbl_linha USING (linha)
								WHERE tbl_produto.referencia = '$referencia' AND tbl_linha.fabrica = $fabrica";
				$query_produto = pg_query($con, $sql_produto);

				if (pg_last_error()) {
					array_push($original, 'erro');
					$log = implode(";", $original);
					fwrite($nlog, $log . "\n");

					$log_erro = logErro($sql_produto, pg_last_error());
					fwrite($elog, $log_erro);
					continue;
				}

				if (pg_num_rows($query_produto) == 0) {
					$sql = "INSERT INTO tbl_produto (
											linha,
											familia,
											referencia,
											descricao,
											origem,
											voltagem,
											garantia,
											mao_de_obra,
											mao_de_obra_admin,
											numero_serie_obrigatorio,
											marca
										)VALUES(
											$linha_id,
											$familia_id,
											'$referencia',
											(E'$descricao'),
											'$origem',
											'$voltagem',
											$garantia,
											$mao_de_obra,
											$mao_de_obra_admin,
											$numero_serie_obrigatorio,
											$id_marca
										)";
				} else {
					$produto = pg_fetch_result($query_produto, 0, 'produto');

					$sql = "UPDATE tbl_produto SET
									descricao                = (E'$descricao'),
									garantia                 = '$garantia',
									numero_serie_obrigatorio = $numero_serie_obrigatorio,
									marca 					 = $id_marca
								WHERE tbl_produto.produto    = $produto";

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
			$cmds = "cd $log_dir && cp importa-produto-$now.log produto$data_arq_enviar.txt && zip -r produto$data_arq_enviar.zip produto$data_arq_enviar.txt 1>/dev/null";
			system("$cmds", $retorno);

			$joga_ftp = "cd $log_dir && cp produto$data_arq_enviar.txt $ftp/$fabrica_nome-produtos$data_arq_enviar.ret";
			system("$joga_ftp");

			if ($retorno == 0) {

				require_once dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

				$assunto = ucfirst($fabrica_nome) . utf8_decode(': Importação de produtos ') . date('d/m/Y');

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
				$mail->Body = "Segue anexo arquivo de produtos importado na rotina...<br/><br/>";
				$mail->AddAttachment($log_dir . '/produto' . $data_arq_enviar . '.zip', 'produto' . $data_arq_enviar . '.zip');

				unlink($log_dir . '/produto' . $data_arq_enviar . '.txt');
				unlink($log_dir . '/produto' . $data_arq_enviar . '.zip');

			} else {
				echo 'Erro ao compactar arquivo de log: ' , $retorno;
			}
		}

		if (filesize($err_log) > 0) {
			system("cd $log_dir && zip -r importa-produto-err-$now.zip importa-produto-err-$now.log 1>/dev/null", $retorno);

			if ($retorno == 0) {

				require_once dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

				$assunto = utf8_decode('Vonder: Erros na importação de produtos ') . date('d/m/Y');

				$mail = new PHPMailer();
				$mail->IsHTML(true);
				$mail->From = 'helpdesk@telecontrol.com.br';
				$mail->FromName = 'Telecontrol';

				if (ENV == 'producao') {
					$mail->AddAddress('helpdesk@telecontrol.com.br');
				} else {
					$mail->AddAddress(DEV_EMAIL);
				}

				$mail->Subject = $assunto;
				$mail->Body = "Segue anexo log de erro na importação de produtos...<br/><br/>";
				$mail->AddAttachment($log_dir . '/importa-produto-err-' . $now . '.zip', 'importa-produto-err-' . $now . '.zip');

				if (!$mail->Send()) {
					echo 'Erro ao enviar email: ' , $mail->ErrorInfo;
				} else {
					unlink($log_dir . '/importa-produto-err-' . $now . '.zip');
				}

			} else {
				echo 'Erro ao compactar arquivo de log de erros: ' , $retorno;
			}
		}

		$data_arq_process = date('Ymd');
		system("mv $arquivo /tmp/$fabrica_nome/telecontrol-produto-$data_arq_process.txt");

	}

} catch (Exception $e) {
	echo $e->getMessage();
}

