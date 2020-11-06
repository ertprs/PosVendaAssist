<?php
/**
 *
 * importa-lista-basica.php
 *
 * Importação de lista básica Roca
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
	

	$lst_in = "/tmp/roca/ftp-pasta-in/lst";
	if (!is_dir($lst_in)) {
		if (!mkdir($lst_in, 0777, true)) {
			throw new Exception("ERRO: Não foi possível criar logs. falha ao criar diretório: $log_dir");
		}
	}
	
	$log_dir = '/tmp/' . $fabrica_nome . '/logs';
	if (!is_dir($log_dir)) {
		if (!mkdir($log_dir, 0777, true)) {
			throw new Exception("ERRO: Não foi possível criar logs. falha ao criar diretório: $log_dir");
		}
	}

	$lst_importados = '/tmp/roca/telecontrol-lst-importados';
	if(!is_dir($lst_importados)){
		if (!mkdir($lst_importados,0777,true)) {
			throw new Exception("ERRO: Não foi possível criar logs. falha ao criar diretório: $lst_importados");
		}
	}

	$local_lst = "$lst_in/";
	$server_file = "in/";
	$arquivos = ftp_nlist($conn_id,"in");
	$log_erro = array();
	$log_success =  array();


	foreach ($arquivos as $key => $value) {
		$pos = strpos( $value, "LST" );
		if ($pos === false) {
			continue;
		} else {
			if (ftp_get($conn_id, $local_lst.$value, $server_file.$value, FTP_BINARY)){
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

	$diretorio_origem = $local_lst;
	
	date_default_timezone_set('America/Sao_Paulo');
	$now = date('Ymd_His');

	$dir = opendir($diretorio_origem);

	if ($dir){
		while(false !== ($arquivo = readdir($dir))) {
			$nome_arquivo  = explode(".", $arquivo);
			$nome_arquivo = $nome_arquivo[0];
			if(in_array($arquivo,array('.','..'))) continue;
		    $arq_log = $log_dir . '/importa-lista-basica-success-' .$nome_arquivo.'-'. $now . '.log';
			$err_log = $log_dir . '/importa-lista-basica-err-' .$nome_arquivo.'-'. $now . '.log';
			
			unset($log_erro);
			unset($log_success);
			if (file_exists($diretorio_origem.$arquivo) and (filesize($diretorio_origem.$arquivo) > 0)) {
				
				$conteudo = file_get_contents($diretorio_origem.$arquivo);
				$conteudo = explode("\n", $conteudo);

				$log_erro[] = " ==== LOG ERRO INÍCIO: ".date("H:i")." ==== ";
				$log_success[] = " ==== LOG SUCCESS ".date("H:i")." ==== ";
				foreach ($conteudo as $key => $value) {
					if (!empty($value)) {
						unset($peca_id);
						unset($produto_id);

						list($referencia_produto, $referencia_peca, $qtde) = explode("|", $value);
						
						$referencia_produto = strtim($referencia_produto);
						$referencia_peca 	= strtim($referencia_peca);
						$qtde 		 		= str_replace(",",".",$qtde);
						
						$sql_produto = "
								SELECT tbl_produto.produto FROM tbl_produto JOIN tbl_linha USING (linha)
								WHERE tbl_produto.referencia = '$referencia_produto' AND tbl_linha.fabrica = $fabrica 
								AND tbl_produto.fabrica_i = $fabrica";
						$query_produto = pg_query($con, $sql_produto);

						if (pg_last_error()) {
							$log_erro[] = "ARQUIVO: $nome_arquivo LINHA ".($key + 1).": - PRODUTO NÃO ENCONTRADO NO TELECONTROL - PRODUTO REF. $referencia_produto  REF. PEÇA: $referencia_peca";
							continue;
						}
						
						if (pg_num_rows($query_produto) == 1) {
							$produto_id = pg_fetch_result($query_produto, 0, 'produto');
						}else{
							$log_erro[] = "ARQUIVO: $nome_arquivo LINHA ".($key + 1).": - PRODUTO NÃO ENCONTRADO NO TELECONTROL - PRODUTO REF. $referencia_produto REF. PEÇA: $referencia_peca";
							continue;
						}

						$sql_peca = "SELECT peca FROM tbl_peca
							     WHERE tbl_peca.referencia = TRIM('$referencia_peca')
						             AND tbl_peca.fabrica = $fabrica LIMIT 1";
						$query_peca = pg_query($con, $sql_peca);

						if (pg_last_error()) {
							$log_erro[] = "ARQUIVO: $nome_arquivo LINHA ".($key + 1).": - PEÇA NÃO ENCONTRADA NO TELECONTROL - PRODUTO REF. $referencia_produto  REF. PEÇA: $referencia_peca";
							continue;
						}

						if (pg_num_rows($query_peca) == 1) {
							$peca_id = pg_fetch_result($query_peca, 0, 'peca');
						}else{
							$log_erro[] = "ARQUIVO: $nome_arquivo LINHA ".($key + 1).": - PEÇA NÃO ENCONTRADA NO TELECONTROL -  PRODUTO REF. $referencia_produto PEÇA REF. $referencia_peca";
							continue;
						}

						$sql_lista_basica = "
							SELECT tbl_lista_basica.produto,tbl_lista_basica.peca FROM tbl_lista_basica
							WHERE tbl_lista_basica.produto = $produto_id AND tbl_lista_basica.peca = $peca_id
							AND tbl_lista_basica.fabrica = $fabrica";
						$query_lista_basica = pg_query($con, $sql_lista_basica);

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
							$query = pg_query($con, $sql);

							if (pg_last_error()) {
								$log_erro[] = "ARQUIVO: $nome_arquivo LINHA ".($key + 1).": - ERRO AO CADASTRAR LISTA BÁSICA NO TELECONTROL - PRODUTO REF. $referencia_produto  PEÇA REF. $referencia_peca";
								continue;
							}else{
								$log_success[] = "ARQUIVO: $nome_arquivo - LISTA BÁSICA INSERIDA COM SUCCESSO - PRODUTO REF. $referencia_produto  PEÇA REF. $referencia_peca";
							}
						} else {

							$sql = "UPDATE tbl_lista_basica SET
										qtde = $qtde
									WHERE tbl_lista_basica.produto = $produto_id
									AND tbl_lista_basica.peca = $peca_id
									AND tbl_lista_basica.fabrica = $fabrica";
							$query = pg_query($con, $sql);

							if (pg_last_error()) {
								$log_erro[] = "ARQUIVO: $nome_arquivo LINHA ".($key + 1).": - ERRO AO ATUALIZAR LISTA BÁSICA NO TELECONTROL - PRODUTO REF. $referencia_produto  PEÇA REF. $referencia_peca";
								continue;
							}else{
								$log_success[] = "ARQUIVO: $nome_arquivo - LISTA BÁSICA ATUALIZADA COM SUCCESSO - PRODUTO REF. $referencia_produto  PEÇA REF. $referencia_peca";
							}
						}
					}
				}

				ftp_chmod($conn_id, 0777, "in/bkp");
				ftp_put($conn_id, "in/bkp/$now-$arquivo","$local_lst/$arquivo", FTP_BINARY);
				
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
					$cmds = "cp $log_dir/importa-lista-basica-err-$nome_arquivo-$now.log $log_dir/importa-lista-basica-err-$nome_arquivo-$data_arq_enviar.txt";
					system($cmds, $retorno);
					
					if ($retorno == 0){
						$manda_email = true;
						$arquivos_email[] = "$log_dir/importa-lista-basica-err-$nome_arquivo-$data_arq_enviar.txt";
					}
				}else{
					$data_arq_process = date('Ymd');
					ftp_delete($conn_id, "$server_file$arquivo");
				}
				system("mv $diretorio_origem$arquivo /tmp/$fabrica_nome/telecontrol-lst-importados/$nome_arquivo-$data_arq_process-ok.txt");
			}
		}
		
		if ($manda_email === true){
			$zip = "zip $log_dir/importa-lista-basica-err-$data_arq_enviar.zip ".implode(' ', $arquivos_email)." 1>/dev/null";
			system($zip, $retorno);

			require_once dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';
			$assunto = ucfirst($fabrica_nome) . utf8_decode(': Importação de lista básica ') . date('d/m/Y');
			$mail = new PHPMailer();
			$mail->IsHTML(true);
			$mail->From = 'helpdesk@telecontrol.com.br';
			$mail->FromName = 'Telecontrol';

			$mail->AddAddress('guilherme.monteiro@telecontrol.com.br');
			$mail->Subject = $assunto;
			$mail->Body = "Segue anexo arquivo de log erro importado na rotina...<br/><br/>";
			
			if (count($arquivos_email) > 0){
				$mail->AddAttachment("$log_dir/importa-lista-basica-err-$data_arq_enviar.zip", "importa-lista-basica-err-$data_arq_enviar.zip");
			}
			if (!$mail->Send()) {
				echo 'Erro ao enviar email: ' , $mail->ErrorInfo;
			} else {
				unlink("$log_dir/importa-lista-basica-err-$data_arq_enviar.zip");
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
