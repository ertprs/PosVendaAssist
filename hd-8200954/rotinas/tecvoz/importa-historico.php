<?php
/**
 *
 * importa-posto.php
 *
 * Importação de postos Elgin
 *
 */

error_reporting(E_ALL ^ E_NOTICE);

define('ENV', 'producao');
define('DEV_EMAIL', 'marisa.silvana@telecontrol.com.br');

try {

	#include dirname(__FILE__) . '/../../dbconfig_bc_teste.php';
	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';

	// tecvoz
	$fabrica = 165;
	$fabrica_nome = 'tecvoz';

	function strtim($var)
	{
		if (!empty($var)) {
			$var = trim($var);
			$var = str_replace("'", "\'", $var);
			$var = str_replace("/", "\/", $var);
			$var = str_replace("\\", "", $var);
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

	$diretorio_origem = '/home/marisa';
//	$diretorio_origem = '/mnt/home/fabiano/public_html/assist/rotinas/'.$fabrica_nome; //  teste local
	$arquivo_origem   = 'historico_ordem_2.csv';

	$ftp = '/tmp/tecvoz/telecontrol-' . $fabrica_nome;
	//$ftp = '/home/fabiano/telecontrol_teste'; //  teste local

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
						$os_tecvoz,
						$cliente_codigo,
						$cliente_razao,
						$produto_codigo,
						$produto_descricao,
						$qtde,
						$unidade,
						$situacao,
						$garantia,
						$doc_auxiliar,
						$data_abertura,
						$data_fechamento,
						$numero_serie,
						$status
					) = explode (";",$linha);

				$original = array(
					
									$os_tecvoz,
									$cliente_codigo,
									$cliente_razao,
									$produto_codigo,
									$produto_descricao,
									$qtde,
									$unidade,
									$situacao,
									$garantia,
									$doc_auxiliar,
									$data_abertura,
									$data_fechamento,
									$numero_serie,
									$status
								);

				echo $os_tecvoz = strtim($os_tecvoz);
				$cliente_codigo = strtim($cliente_codigo);
				$cliente_razao = strtim($cliente_razao);
				$produto_codigo = strtim($produto_codigo);
				$produto_descricao = strtim($produto_descricao);
				$qtde = strtim($qtde);
				$unidade = strtim($unidade);
				$situacao = strtim($situacao);
				$garantia = strtim($garantia);
				$doc_auxiliar = strtim($doc_auxiliar);
				$data_abertura = strtim($data_abertura);
				$data_fechamento = strtim($data_fechamento);
				$numero_serie = strtim($numero_serie);
				$status = strtim($status);

				
				#$sql_posto = "SELECT os_tecvoz FROM tbl_os_tecvoz WHERE os_tecvoz = '$os_tecvoz'";
				#$query_posto = pg_query($con, $sql_posto);

				#if (pg_num_rows($query_posto) == 0) {
					echo $sql = "INSERT INTO tbl_os_tecvoz2 (
											os_tecvoz,
											cliente_codigo,
											cliente_razao,
											produto_codigo,
											produto_descricao,
											qtde,
											unidade,
											situacao,
											garantia,
											doc_auxiliar,
											data_abertura,
											data_fechamento,
											numero_serie,
											status
										) VALUES (
											'$os_tecvoz',
											'$cliente_codigo',
											(E'$cliente_razao'),
											(E'$produto_codigo'),
											(E'$produto_descricao'),
											'$qtde',
											(E'$unidade'),
											(E'$situacao'),
											(E'$garantia'),
											(E'$doc_auxiliar'),
											(E'$data_abertura'),
											(E'$data_fechamento'),
											(E'$numero_serie'),
											(E'$status')
										)";
					$query = pg_query($con, $sql);

					if (pg_last_error()) {
						array_push($original, 'erro');
						$log = implode(";", $original);
						fwrite($nlog, $log . "\n");

						echo $log_erro = logErro($sql, pg_last_error());
						fwrite($elog, $logErro);
						exit;
					}


				#} 

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
				$mail->From = 'marisa.silvana@telecontrol.com.br';
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
				$mail->From = 'marisa.silvana@telecontrol.com.br';
				//$mail->From = 'fabiano.souza@telecontrol.com.br'; //  teste local
				$mail->FromName = 'Telecontrol';

				if (ENV == 'producao') {
					$mail->AddAddress('marisa.silvana@telecontrol.com.br');
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
		#system("cp $arquivo /tmp/$fabrica_nome/posto-$data_arq_process.txt");
		
	}

} catch (Exception $e) {
	echo $e->getMessage();
}
