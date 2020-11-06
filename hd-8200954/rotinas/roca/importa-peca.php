<?php
/**
 *
 * importa-peca.php
 *
 * Importação de peças Roca
 *
 * @author  Guilherme Monteiro
 * @version 2019.03.19
 *
 */

error_reporting(E_ALL ^ E_NOTICE);

try {
	include 'connect-ftp.php';
	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';

	$fabrica = 178;
	$fabrica_nome = 'roca';
	
	$pec_in = "/tmp/roca/ftp-pasta-in/pec";
	if (!is_dir($pec_in)) {
		if (!mkdir($pec_in, 0777, true)) {
			throw new Exception("ERRO: Não foi possível criar logs. falha ao criar diretório: $log_dir");
		}
	}
	
	$log_dir = '/tmp/' . $fabrica_nome . '/logs';
	if (!is_dir($log_dir)) {
		if (!mkdir($log_dir, 0777, true)) {
			throw new Exception("ERRO: Não foi possível criar logs. falha ao criar diretório: $log_dir");
		}
	}

	$pec_importados = '/tmp/roca/telecontrol-pec-importados';
	if(!is_dir($pec_importados)){
		if (!mkdir($pec_importados,0777,true)) {
			throw new Exception("ERRO: Não foi possível criar logs. falha ao criar diretório: $pec_importados");
		}
	}

	$local_pec = "$pec_in/";
	$server_file = "in/";
	$arquivos = ftp_nlist($conn_id,"in");
	$log_erro = array();
	$log_success =  array();

	foreach ($arquivos as $key => $value) {
		$pos = strpos( $value, "PEC" );
		if ($pos === false) {
			continue;
		} else {
			if (ftp_get($conn_id, $local_pec.$value, $server_file.$value, FTP_BINARY)){
				#ftp_delete($conn_id, "$server_file$value");
			} 
		}
	}
	
	function strtim($var)
	{
		if (!empty($var)) {
			$var = trim($var);
			$var = str_replace("'", "\'", $var);
			$var = str_replace("/", "", $var);
		}
		return $var;
	}

	$diretorio_origem = $local_pec;
	
	date_default_timezone_set('America/Sao_Paulo');
	$now = date('Ymd_His');

	$dir = opendir($diretorio_origem);

	if ($dir){
		while(false !== ($arquivo = readdir($dir))) {
			$nome_arquivo  = explode(".", $arquivo);
			$nome_arquivo = $nome_arquivo[0];
				
			$arq_log = $log_dir . '/importa-peca-success-' .$nome_arquivo.'-'. $now . '.log';
			$err_log = $log_dir . '/importa-peca-err-' .$nome_arquivo.'-'. $now . '.log';
			
			unset($log_erro);
			unset($log_success);
			if (file_exists($diretorio_origem.$arquivo) and (filesize($diretorio_origem.$arquivo) > 0)) {
				
				$conteudo = file_get_contents($diretorio_origem.$arquivo);
				$conteudo = explode("\n", $conteudo);

				$log_erro[] = " ==== LOG ERRO INÍCIO: ".date("H:i")." ==== ";
				$log_success[] = " ==== LOG SUCCESS ".date("H:i")." ==== ";
				foreach ($conteudo as $key => $value) {
					if (!empty($value)) {
						unset($peca);
						
						list($referencia, $descricao, $unidade_medida, $origem, $ipi, $ncm, $ativo, $garantia, $multiplo, $wrkst) = explode("|", $value);
						
						$referencia  	= strtim($referencia);
						$descricao 	 	= strtim($descricao);
						$unidade_medida = strtim($unidade_medida);
						$origem			= strtim($origem);
						$ipi 	 		= strtim($ipi);
						$ncm 			= strtim($ncm);
						$ativo 	 		= strtim($ativo);
						$garantia 	 	= strtim($garantia);
						$multiplo 		= strtim($multiplo);
						$wrkst 		 	= strtim($wrkst);
						
						if (!empty($descricao)){
							$descricao = utf8_decode($descricao);
						}
						
						if (empty($origem)){
							$log_erro[] = "ARQUIVO: $nome_arquivo LINHA ".($key + 1).": - ERRO AO CADASTRAR PEÇA (PEÇA SEM ORIGEM)- PEÇA REF. $referencia  DESC. $descricao";
							continue;	
						}

						$sql_peca = "
							SELECT tbl_peca.peca FROM tbl_peca
							WHERE tbl_peca.referencia = '$referencia'
							AND tbl_peca.fabrica = $fabrica";
						$query_peca = pg_query($con, $sql_peca);;

						if (pg_last_error()) {
							$log_erro[] = "ARQUIVO: $nome_arquivo LINHA ".($key + 1).": - PEÇA NÃO CADASTRADA NO TELECONTROL - PEÇA REF. $referencia  DESC. $descricao";
							continue;
						}
						
						if (!empty($ativo)){
							$ativo = true;
						}

						if (pg_num_rows($query_peca) == 0) {
							
							$sql = "INSERT INTO tbl_peca (
										fabrica,
										referencia,
										descricao,
										unidade,
										origem,
										ipi,
										ncm,
										ativo,
										multiplo
									)VALUES(
										$fabrica,
										".((empty($referencia)) ? "null" : "(E'$referencia')").",
										".((empty($descricao)) ? "null" : "(E'$descricao')").",
										".((empty($unidade_medida)) ? "null" : "'".$unidade_medida."'").",
										".((empty($origem)) ? "null" : "'".$origem."'").",
										".((empty($ipi)) ? "null" : "'".$ipi."'").",
										".((empty($ncm)) ? "null" : "'".$ncm."'").",
										".((empty($ativo)) ? "false" : "'".$ativo."'").",				
										".((empty($multiplo)) ? "0" : $multiplo)."
									)";
							$query = pg_query($con, $sql);

							if (pg_last_error()) {
								$log_erro[] = "ARQUIVO: $nome_arquivo LINHA ".($key + 1).": - ERRO AO CADASTRAR PEÇA NO TELECONTROL- PEÇA REF. $referencia  DESC. $descricao";
								continue;
							}else{
								$log_success[] = "ARQUIVO: $nome_arquivo - PEÇA INSERIDA COM SUCCESSO - PEÇA REF. $referencia  DESC. $descricao";
							}
						} else {
							$peca = pg_fetch_result($query_peca, 0, 'peca');

							$sql = "UPDATE tbl_peca SET
									descricao = ".((empty($descricao)) ? "null" : "(E'$descricao')").",
									origem = ".((empty($origem)) ? "null" : "'".$origem."'").",
									unidade = ".((empty($unidade_medida)) ? "null" : "'".$unidade_medida."'").",
									ipi = ".((empty($ipi)) ? "null" : $ipi)."
									WHERE tbl_peca.peca = $peca";
							$query = pg_query($con, $sql);

							if (pg_last_error()){
								$log_erro[] = "ARQUIVO: $nome_arquivo LINHA ".($key + 1).": - ERRO AO ATUALIZAR PEÇA NO TELECONTROL - PEÇA REF. $referencia  DESC. $descricao";
								continue;
							}else{
								$log_success[] = "ARQUIVO: $nome_arquivo - PEÇA ATUALIZADO COM SUCCESSO - PEÇA REF. $referencia  DESC. $descricao";
							}
						}
					}
				}
				
				ftp_chmod($conn_id, 0777, "in/bkp");
				ftp_put($conn_id, "in/bkp/$now-$arquivo","$local_pec/$arquivo", FTP_BINARY);
				
				if (count($log_erro) > 1){
					$elog = fopen($err_log, "w");
					$dados_log_erro = implode("\n", $log_erro);
					fwrite($elog, $dados_log_erro);
					fclose($elog);
				}

				if (count($log_success) > 1){
					$slog = fopen($arq_log, "w");
					$dados_log_success = implode("\n", $log_success);
					fwrite($slog, $dados_log_success);
					fclose($slog);
				}
				
				if (filesize($err_log) > 0) {
					$data_arq_enviar = date('dmy');
					$cmds = "cp $log_dir/importa-peca-err-$nome_arquivo-$now.log $log_dir/importa-peca-err-$nome_arquivo-$data_arq_enviar.txt";
					system($cmds, $retorno);
					
					if ($retorno == 0){
						$manda_email = true;
						$arquivos_email[] = "$log_dir/importa-peca-err-$nome_arquivo-$data_arq_enviar.txt";
					}
				}else{
					$data_arq_process = date('Ymd');
					ftp_delete($conn_id, "$server_file$arquivo");
				}
				system("mv $diretorio_origem$arquivo /tmp/$fabrica_nome/telecontrol-pec-importados/$nome_arquivo-$data_arq_process-ok.txt");
			}
		}

		if ($manda_email === true){
			$zip = "zip $log_dir/importa-peca-err-$data_arq_enviar.zip ".implode(' ', $arquivos_email)." 1>/dev/null";
			system($zip, $retorno);

			require_once dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';
			$assunto = ucfirst($fabrica_nome) . utf8_decode(': Importação de peças ') . date('d/m/Y');
			$mail = new PHPMailer();
			$mail->IsHTML(true);
			$mail->From = 'helpdesk@telecontrol.com.br';
			$mail->FromName = 'Telecontrol';

			$mail->AddAddress('guilherme.monteiro@telecontrol.com.br');
			$mail->Subject = $assunto;
			$mail->Body = "Segue anexo arquivo de log erro importado na rotina...<br/><br/>";
			
			if (count($arquivos_email) > 0){
				$mail->AddAttachment("$log_dir/importa-peca-err-$data_arq_enviar.zip", "importa-peca-err-$data_arq_enviar.zip");
			}
			if (!$mail->Send()) {
				echo 'Erro ao enviar email: ' , $mail->ErrorInfo;
			} else {
				unlink("$log_dir/importa-peca-err-$data_arq_enviar.zip");
				foreach ($arquivos_email as $key => $value) {
					unlink($value);
				}
			}
		}
	}
	ftp_close($conn_id);
} catch (Exception $e) {
	echo $e->getMessage();
	ftp_close($conn_id);
}
?>
