<?php
/**
 *
 * importa-posto-linha.php
 *
 * Importação de postos Vonder/DWT
 *
 * @author  Francisco Ambrozio
 * @version 2012.01.04
 *
 */

error_reporting(E_ALL ^ E_NOTICE);

define('ENV', 'producao');
define('DEV_EMAIL', 'francisco@telecontrol.com.br');

try {

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require dirname(__FILE__) . '/../funcoes.php';

	if (!empty($argv[1])) {
		$argumento = strtolower($argv[1]);

		if ($argumento == 'dwt') {
			$vonder_dwt = 1;
		} else {
			$vonder_dwt = 0;
		}
	} else {
		$vonder_dwt = 0;
	}

	switch ($vonder_dwt) {
		case 0:
			// Vonder
			$fabrica = 104;
			$fabrica_nome = 'vonder';
			$ovd_dwt = 'ovd';
			break;
		case 1:
			// DWT
			$fabrica = 105;
			$fabrica_nome = 'dwt';
			$ovd_dwt = 'dwt';
			break;
		default:
			throw new Exception('Falha na passagem de parâmetros.');
	}
	
	$phpCron = new PHPCron($fabrica, __FILE__); 
	$phpCron->inicio();

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


	$diretorio_origem = '/www/cgi-bin/' . $fabrica_nome . '/entrada';
	$arquivo_origem = 'telecontrol-posto-linha.txt';

	$ftp = '/home/vonder/telecontrol-' . $fabrica_nome;

	if (ENV == 'teste') {
		$ftp = '../' . $fabrica_nome;
	}

	date_default_timezone_set('America/Sao_Paulo');
	$now = date('Ymd_His');

	$log_dir = '/tmp/' . $fabrica_nome . '/logs';
	$arq_log = $log_dir . '/importa-posto-linha-' . $now . '.log';
	$err_log = $log_dir . '/importa-posto-linha-err-' . $now . '.log';

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
				list ($codigo, $linha_posto, $sigla_tabela) = explode (";",$linha);

				$original = array($codigo, $linha_posto, $sigla_tabela);

				$codigo = strtim($codigo);
				$linha_posto = strtim($linha_posto);
				$sigla_tabela = strtim($sigla_tabela);

				$codigo = str_replace('.', '', $codigo);
				$codigo = str_replace('-', '', $codigo);

				$sql_posto = "SELECT tbl_posto_fabrica.posto FROM tbl_posto_fabrica WHERE codigo_posto = '$codigo' AND fabrica = $fabrica";
				$query_posto = pg_query($con, $sql_posto);

				if (pg_num_rows($query_posto) == 0) {
					array_push($original, 'erro');
					$log = implode(";", $original);
					fwrite($nlog, $log . "\n");

					fwrite($elog, "Posto não encontrado: " . $codigo . "\n");
					continue;
				} else {
					$posto = pg_fetch_result($query_posto, 0, 'posto');
				}

				$sql_linha = "SELECT tbl_linha.linha FROM tbl_linha WHERE codigo_linha = '$linha_posto' AND fabrica = $fabrica";
				$query_linha = pg_query($con, $sql_linha);

				if (pg_last_error()) {
					$erro = logErro($sql_linha, pg_last_error());
					fwrite($elog, $erro);
				}

				if (is_resource($query_linha)) {
					$linha_id = pg_fetch_result($query_linha, 0, 'linha');
				} else {
					array_push($original, 'erro');
					$log = implode(";", $original);
					fwrite($nlog, $log . "\n");

					continue;
				}

				$sql_tabela = "SELECT tbl_tabela.tabela FROM tbl_tabela	WHERE sigla_tabela = '$sigla_tabela' AND fabrica = $fabrica";
				$query_tabela = pg_query($con, $sql_tabela);

				if (pg_last_error()) {
					$erro = logErro($sql_linha, pg_last_error());
					fwrite($elog, $erro);
				}

				if (is_resource($query_tabela)) {
					$tabela = pg_fetch_result($query_tabela, 0, 'tabela');
				} else {
					array_push($original, 'erro');
					$log = implode(";", $original);
					fwrite($nlog, $log . "\n");

					continue;
				}

				$sql_posto_linha = "SELECT posto, linha FROM tbl_posto_linha WHERE posto = $posto and linha = $linha_id";
				$query_linha = pg_query($con, $sql_posto_linha);

				if (pg_num_rows($query_linha) == 0) {
					$sql = "INSERT INTO tbl_posto_linha (
												linha,
												posto,
												tabela
										) VALUES (
												$linha_id,
												$posto,
												$tabela
										)";
					$query = pg_query($con, $sql);

					if (pg_last_error()) {
						array_push($original, 'erro');
						$log = implode(";", $original);
						fwrite($nlog, $log . "\n");

						$erro = logErro($sql, pg_last_error());
						fwrite($elog, $erro);
					} else {
						array_push($original, 'ok');
						$log = implode(";", $original);
						fwrite($nlog, $log . "\n");
					}

				} else {
					array_push($original, 'erro');
					$log = implode(";", $original);
					fwrite($nlog, $log . "\n");
				}
			}

		}

		fclose($nlog);
		fclose($elog);

		if (filesize($arq_log) > 0) {
			$data_arq_enviar = date('dmY');
			$cmds = "cd $log_dir && cp importa-posto-linha-$now.log posto-linha$data_arq_enviar.txt && zip -r posto-linha$data_arq_enviar.zip posto-linha$data_arq_enviar.txt 1>/dev/null";
			system("$cmds", $retorno);

			$joga_ftp = "cd $log_dir && cp posto-linha$data_arq_enviar.txt $ftp/$ovd_dwt-posto-linha-$data_arq_enviar.ret";
			system("$joga_ftp");

			if ($retorno == 0) {

				require_once dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

				$assunto = ucfirst($fabrica_nome) . utf8_decode(': Importação postos/linha ') . date('d/m/Y');

				$mail = new PHPMailer();
				$mail->IsHTML(true);
				$mail->From = 'helpdesk@telecontrol.com.br';
				$mail->FromName = 'Telecontrol';

				if (ENV == 'producao') {
					$mail->AddAddress('tiago.pacheco@ovd.com.br');
				} else {
					$mail->AddAddress(DEV_EMAIL);
				}

				$mail->Subject = $assunto;
				$mail->Body = "Segue anexo arquivo de postos/linha importado na rotina...<br/><br/>";
				$mail->AddAttachment($log_dir . '/posto-linha' . $data_arq_enviar . '.zip', 'posto-linha' . $data_arq_enviar . '.zip');

				if (!$mail->Send()) {
					echo 'Erro ao enviar email: ' , $mail->ErrorInfo;
				} else {
					unlink($log_dir . '/posto-linha' . $data_arq_enviar . '.txt');
					unlink($log_dir . '/posto-linha' . $data_arq_enviar . '.zip');
				}

			} else {
				echo 'Erro ao compactar arquivo de log: ' , $retorno;
			}
		}

		if (filesize($err_log) > 0) {
			system("cd $log_dir && zip -r importa-posto-linha-err-$now.zip importa-posto-linha-err-$now.log 1>/dev/null", $retorno);

			if ($retorno == 0) {

				require_once dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

				$assunto = ucfirst($fabrica_nome) . utf8_decode(': Erros na importação de postos/linha ') . date('d/m/Y');

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
				$mail->Body = "Segue anexo log de erro na importação de postos...<br/><br/>";
				$mail->AddAttachment($log_dir . '/importa-posto-linha-err-' . $now . '.zip', 'importa-posto-linha-err-' . $now . '.zip');

				if (!$mail->Send()) {
					echo 'Erro ao enviar email: ' , $mail->ErrorInfo;
				} else {
					unlink($log_dir . '/importa-posto-linha-err-' . $now . '.zip');
				}

			} else {
				echo 'Erro ao compactar arquivo de log de erros: ' , $retorno;
			}
		}

		$data_arq_process = date('Ymd');
		system("mv $arquivo /tmp/$fabrica_nome/posto-linha-$data_arq_process.txt");
	}

    $phpCron->termino();
    
} catch (Exception $e) {
	echo $e->getMessage();
}

