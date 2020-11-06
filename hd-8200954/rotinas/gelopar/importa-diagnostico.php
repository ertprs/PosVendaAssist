<?php
/**
 *
 * importa-produto.php
 *
 * Importação de produtos Vonder/DWT
 *
 * @author  Francisco Ambrozio
 * @version 2012.02.22
 *
 */

error_reporting(E_ALL ^ E_NOTICE);

define('ENV', 'producao');

try {

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require dirname(__FILE__) . '/../funcoes.php';

	$fabrica = 85;
	$fabrica_nome = 'gelopar';
	$data_arq_process = date('Ymd');
	
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

	$diretorio_origem = '/home/' . $fabrica_nome . '/gelopar-telecontrol';
	$arquivo_origem = 'mao-de-obra.txt';

	$log_dir = '/tmp/' . $fabrica_nome . '/logs';
	$arq_log = $log_dir . '/importa-mo-' . $data_arq_process . '.log';
	$err_log = $log_dir . '/importa-mo-err-' . $data_arq_process . '.log';

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
			$erro = "";
			if (!empty($linha)) {
				list (
						$codigo_linha,
						$codigo_familia, 
						$codigo_dc,
						$solucao, 
						$mo, 
						$mor
					) = explode ("\t",$linha);

				$original = array(
						$codigo_linha,
						$codigo_familia, 
						$codigo_dc,
						$solucao, 
						$mo, 
						$mor
							);

				$not_null = array(
						$codigo_linha,
						$codigo_familia, 
						$codigo_dc,
						$solucao, 
						$mo, 
						$mor
							);

				foreach ($not_null as $value) {
					if (!$value) {
						array_push($original, 'erro 1');
						$log = implode("\t", $original);
						fwrite($nlog, $log . "\n");
						continue 2;
					}
				}

				if ($mo) {
					$mo = str_replace(",", ".", $mo);
				} else {
					$mo = 0;
				}

				if ($mo == 0){
					array_push($original, 'erro-sem mo');
					$log = implode(";", $original);
                                        fwrite($nlog, $log . "\n");

                                        $log_erro = logErro('sem mão-de-obra', pg_last_error());
                                        fwrite($elog, $log_erro);
                                        continue;
				}

				if ($mor) {
					$mor = str_replace(",", ".", $mor);
				} else {
					$mor = 0;
				}

				if ($mor == 0){
					array_push($original, 'erro-sem mo');
					$log = implode(";", $original);
                                        fwrite($nlog, $log . "\n");

                                        $log_erro = logErro('sem mão-de-obra', pg_last_error());
                                        fwrite($elog, $log_erro);
                                        continue;
				}

				$sql_linha = "SELECT linha FROM tbl_linha
								WHERE tbl_linha.codigo_linha = TRIM('$codigo_linha')
								AND tbl_linha.fabrica = $fabrica LIMIT 1";
				$query_linha = pg_query($con, $sql_linha);

				if (pg_last_error()) {
					array_push($original, 'erro sem linha');
					$log = implode(";", $original);
					fwrite($nlog, $log . "\n");

					$log_erro = logErro($sql_linha, pg_last_error());
					fwrite($elog, $log_erro);
					continue;
				}

				if (pg_num_rows($query_linha) == 1) {
					$linha_id = pg_fetch_result($query_linha, 0, 'linha');
				} else {
					array_push($original, 'erro 3');
					$log = implode(";", $original);
					fwrite($nlog, $log . "\n");

					continue;
				}

				$sql_familia = "SELECT familia FROM tbl_familia
								WHERE tbl_familia.codigo_familia = TRIM('$codigo_familia')
								AND tbl_familia.fabrica = $fabrica LIMIT 1";
				$query_familia = pg_query($con, $sql_familia);

				if (pg_last_error()) {
					array_push($original, 'erro sem familia');
					$log = implode(";", $original);
					fwrite($nlog, $log . "\n");

					$log_erro = logErro($sql_familia, pg_last_error());
					fwrite($elog, $log_erro);
					continue;
				}

				if (pg_num_rows($query_familia) == 1) {
					$familia_id = pg_fetch_result($query_familia, 0, 'familia');
				} else {
					array_push($original, 'erro 5');
					$log = implode(";", $original);
					fwrite($nlog, $log . "\n");

					continue;
				}

				$sql_defeito_constatado = "SELECT defeito_constatado FROM tbl_defeito_constatado
								WHERE tbl_defeito_constatado.codigo = TRIM('$codigo_dc')
								AND tbl_defeito_constatado.fabrica = $fabrica LIMIT 1";
				$query_defeito_constatado = pg_query($con, $sql_defeito_constatado);

				if (pg_last_error()) {
					array_push($original, 'erro sem defeito_constatado');
					$log = implode(";", $original);
					fwrite($nlog, $log . "\n");

					$log_erro = logErro($sql_defeito_constatado, pg_last_error());
					fwrite($elog, $log_erro);
					continue;
				}

				if (pg_num_rows($query_defeito_constatado) == 1) {
					$defeito_constatado_id = pg_fetch_result($query_defeito_constatado, 0, 'defeito_constatado');
				} else {
					array_push($original, 'erro 5');
					$log = implode(";", $original);
					fwrite($nlog, $log . "\n");

					continue;
				}

				$sql_solucao = "SELECT solucao FROM tbl_solucao
								WHERE tbl_solucao.solucao = $solucao
								AND tbl_solucao.fabrica = $fabrica LIMIT 1";
				$query_solucao = pg_query($con, $sql_solucao);

				if (pg_last_error()) {
					array_push($original, 'erro sem solucao');
					$log = implode(";", $original);
					fwrite($nlog, $log . "\n");

					$log_erro = logErro($sql_solucao, pg_last_error());
					fwrite($elog, $log_erro);
					continue;
				}

				if (pg_num_rows($query_solucao) == 1) {
					$solucao_id = pg_fetch_result($query_solucao, 0, 'solucao');
				} else {
					array_push($original, 'erro 5');
					$log = implode(";", $original);
					fwrite($nlog, $log . "\n");

					continue;
				}

				$sql_diagnostico = "SELECT diagnostico
						FROM tbl_diagnostico
						WHERE linha = $linha_id
						AND   familia = $familia_id
						AND   defeito_constatado = $defeito_constatado_id
						AND   solucao = $solucao_id
						AND   fabrica = $fabrica";
				$query_diagnostico = pg_query($con, $sql_diagnostico);

				if (pg_last_error()) {
					array_push($original, 'erro 6');
					$log = implode(";", $original);
					fwrite($nlog, $log . "\n");

					$log_erro = logErro($sql_diagnostico, pg_last_error());
					fwrite($elog, $log_erro);
					continue;
				}
				
				$res = @pg_query($con,"BEGIN TRANSACTION");
				
				if (pg_num_rows($query_diagnostico) == 0) {
					$sql = "INSERT INTO tbl_diagnostico (
							fabrica,
							linha,
							familia,
							defeito_constatado,
							solucao,
							mao_de_obra,
							mao_de_obra_revenda
						)VALUES(
							$fabrica,
							$linha_id,
							$familia_id,
							$defeito_constatado_id,
							$solucao_id,
							$mo,
							$mor
										)";
				} else {

					$sql = "UPDATE tbl_diagnostico SET
							mao_de_obra = $mo,
							mao_de_obra_revenda = $mor

								WHERE tbl_diagnostico.linha = $linha_id
								AND   tbl_diagnostico.familia   = $familia_id
								AND   tbl_diagnostico.defeito_constatado   = $defeito_constatado_id
								AND   tbl_diagnostico.solucao   = $solucao_id
								AND   tbl_diagnostico.fabrica = $fabrica ";

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

				if(empty($erro)) {
					$res = pg_query($con,"COMMIT TRANSACTION");
				}else{
					$res = @pg_query ($con,"ROLLBACK TRANSACTION");
				}


			}
		}

		fclose($nlog);
		fclose($elog);

		if (filesize($arq_log) > 0) {
			$data_arq_enviar = date('dmy');
			$cmds = "cd $log_dir && cp importa-mo-$data_arq_process.log mo$data_arq_enviar.txt && zip -r mo$data_arq_enviar.zip mo$data_arq_enviar.txt 1>/dev/null";
			system("$cmds", $retorno);

			$joga_ftp = "cd $log_dir && cp mo$data_arq_enviar.txt $ftp/$fabrica_nome-mo$data_arq_enviar.ret";
			system("$joga_ftp");

			if ($retorno == 0) {

				require_once dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

				$assunto = ucfirst($fabrica_nome) . utf8_decode(': Importação de mo ') . date('d/m/Y');

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
				$mail->Body = "Segue anexo arquivo de mo importado na rotina...<br/><br/>";
				$mail->AddAttachment($log_dir . '/mo' . $data_arq_enviar . '.zip', 'mo' . $data_arq_enviar . '.zip');

				unlink($log_dir . '/mo' . $data_arq_enviar . '.txt');
				unlink($log_dir . '/mo' . $data_arq_enviar . '.zip');

			} else {
				echo 'Erro ao compactar arquivo de log: ' , $retorno;
			}
		}

		if (filesize($err_log) > 0) {
			system("cd $log_dir && zip -r importa-mo-err-$data_arq_process.zip importa-mo-err-$data_arq_process.log 1>/dev/null", $retorno);

			if ($retorno == 0) {

				require_once dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

				$assunto = utf8_decode('Gelopar: Erros na importação de mão-de-obra ') . date('d/m/Y');

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
				$mail->Body = "Segue anexo log de erro na importação de preços...<br/><br/>";
				$mail->AddAttachment($log_dir . '/importa-mo-err-' . $data_arq_process . '.zip', 'importa-mo-err-' . $data_arq_process . '.zip');

				if (!$mail->Send()) {
					echo 'Erro ao enviar email: ' , $mail->ErrorInfo;
				} else {
					unlink($log_dir . '/importa-mo-err-' . $data_arq_process . '.zip');
				}

			} else {
				echo 'Erro ao compactar arquivo de log de erros: ' , $retorno;
			}
		}

		system("mv $arquivo /tmp/$fabrica_nome/mao-de-obra-$data_arq_process.txt");

	} elseif (filesize($arquivo) == 0) {
		$to = "sidney.sanches@gelopar.com.br";
		$subj = "Telecontrol: Arquivo de integração de mão-de-obra vazio";
		$mssg = "O arquivo mao-de-obra.txt estava vazio.";
		mail($to, $subj, $mssg);
	}
	
	$phpCron->termino();

} catch (Exception $e) {
	echo $e->getMessage();
}

