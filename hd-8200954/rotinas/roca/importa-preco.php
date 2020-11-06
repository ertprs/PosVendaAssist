<?php
/**
 *
 * importa-preço.php
 *
 * Importação de preço Roca
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
	

	$pre_in = "/tmp/roca/ftp-pasta-in/pre";
	if (!is_dir($pre_in)) {
		if (!mkdir($pre_in, 0777, true)) {
			throw new Exception("ERRO: Não foi possível criar logs. falha ao criar diretório: $log_dir");
		}
	}
	
	$log_dir = '/tmp/' . $fabrica_nome . '/logs';
	if (!is_dir($log_dir)) {
		if (!mkdir($log_dir, 0777, true)) {
			throw new Exception("ERRO: Não foi possível criar logs. falha ao criar diretório: $log_dir");
		}
	}

	$pre_importados = '/tmp/roca/telecontrol-pre-importados';
	if(!is_dir($pre_importados)){
		if (!mkdir($pre_importados,0777,true)) {
			throw new Exception("ERRO: Não foi possível criar logs. falha ao criar diretório: $pre_importados");
		}
	}

	$local_pre = "$pre_in/";
	$server_file = "in/";
	$arquivos = ftp_nlist($conn_id,"in");
	$log_erro = array();
	$log_success =  array();


	foreach ($arquivos as $key => $value) {
		$pos = strpos( $value, "PRE" );
		if ($pos === false) {
			continue;
		} else {
			if (ftp_get($conn_id, $local_pre.$value, $server_file.$value, FTP_BINARY)){
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

	$diretorio_origem = $local_pre;
	
	date_default_timezone_set('America/Sao_Paulo');
	$now = date('Ymd_His');

	$dir = opendir($diretorio_origem);

	if ($dir){
		while(false !== ($arquivo = readdir($dir))) {
			$nome_arquivo  = explode(".", $arquivo);
			$nome_arquivo = $nome_arquivo[0];

			$arq_log = $log_dir . '/importa-preco-success-' .$nome_arquivo.'-'. $now . '.log';
			$err_log = $log_dir . '/importa-preco-err-' .$nome_arquivo.'-'. $now . '.log';
			
			unset($log_erro);
			unset($log_success);
			if (file_exists($diretorio_origem.$arquivo) and (filesize($diretorio_origem.$arquivo) > 0)) {
				
				$conteudo = file_get_contents($diretorio_origem.$arquivo);
				$conteudo = explode("\n", $conteudo);

				$log_erro[] = " ==== LOG ERRO INÍCIO: ".date("H:i")." ==== ";
				$log_success[] = " ==== LOG SUCCESS ".date("H:i")." ==== ";

				foreach ($conteudo as $key => $value) {
					$update_produto = false;
					if (!empty($value)) {
						unset($tabela_id);
						unset($peca_id);
						
						list($referencia_peca, $cliente, $preco_peca) = explode("|", $value);
						
						$referencia_peca = strtim($referencia_peca);
						$cliente 		 = strtim($cliente);
						$preco_peca		 = strtim($preco_peca);

						if (empty($referencia_peca)){
							$log_erro[] = "ARQUIVO: $nome_arquivo LINHA ".($key + 1).": - CAMPO REFERENCIA PEÇA ESTÁ VAZIO - PEÇA REF. $referencia_peca  PREÇO. $preco_peca";
							continue;
						}

						if (empty($preco_peca)){
							$log_erro[] = "ARQUIVO: $nome_arquivo LINHA ".($key + 1).": - CAMPO PREÇO PEÇA ESTÁ VAZIO - PEÇA REF. $referencia_peca  PREÇO. $preco_peca";
							continue;
						}

						$preco_peca = str_replace(",", ".", $preco_peca);
						$sigla_tabela = "PAD";

						$sql_tabela = "
								SELECT tabela FROM tbl_tabela
								WHERE tbl_tabela.sigla_tabela = TRIM('$sigla_tabela')
								AND tbl_tabela.fabrica = $fabrica LIMIT 1";
						$query_tabela = pg_query($con, $sql_tabela);

						if (pg_last_error()) {
							$log_erro[] = "ARQUIVO: $nome_arquivo LINHA ".($key + 1).": - TABELA PREÇO NÃO CADASTRADA NO TELECONTROL - PEÇA REF. $referencia_peca  PREÇO. $preco_peca COD. TABELA: $sigla_tabela";
							continue;
						}

						if (pg_num_rows($query_tabela) == 1) {
							$tabela_id = pg_fetch_result($query_tabela, 0, 'tabela');
						}else{
							$log_erro[] = "ARQUIVO: $nome_arquivo LINHA ".($key + 1).": - TABELA PREÇO NÃO CADASTRADA NO TELECONTROL - PEÇA REF. $referencia_peca  PREÇO. $preco_peca COD. TABELA: $sigla_tabela";
							continue;
						}

						// Atualiza preço produto
						$sql_produto = "SELECT produto FROM tbl_produto WHERE tbl_produto.referencia = TRIM('$referencia_peca') AND tbl_produto.fabrica_i = $fabrica";
						$res_produto = pg_query($con, $sql_produto);

						if (pg_last_error()){
							continue;
						}

						if (pg_num_rows($res_produto) == 1){
							$produto_id = pg_fetch_result($res_produto, 0, 'produto');

							$sql_update_produto = "UPDATE tbl_produto set preco = $preco_peca WHERE produto = $produto_id AND fabrica_i = $fabrica";
							$res_update_produto = pg_query($con, $sql_update_produto);
						
							if (pg_last_error()){
								continue;
							}
							$update_produto = true;
						}

						$sql_peca = "
								SELECT peca FROM tbl_peca
								WHERE tbl_peca.referencia = TRIM('$referencia_peca')
								AND tbl_peca.fabrica = $fabrica LIMIT 1";
						$query_peca = pg_query($con, $sql_peca);

						if (pg_last_error()) {
							$log_erro[] = "ARQUIVO: $nome_arquivo LINHA ".($key + 1).": - PEÇA NÃO ENCONTRADA NO TELECONTROL - PEÇA REF. $referencia_peca  PREÇO. $preco_peca";
							continue;
						}

						if (pg_num_rows($query_peca) == 1) {
							$peca_id = pg_fetch_result($query_peca, 0, 'peca');
						}else{
							if ($update_produto == false){
								$log_erro[] = "ARQUIVO: $nome_arquivo LINHA ".($key + 1).": PEÇA NÃO ENCONTRADA NO TELECONTROL - PEÇA REF. $referencia_peca  PREÇO. $preco_peca";
								continue;
							}
						}

						$sql_tabela_item = "
								SELECT tbl_tabela_item.peca FROM tbl_tabela_item
								WHERE tbl_tabela_item.tabela = $tabela_id AND tbl_tabela_item.peca = $peca_id";
						$query_tabela_item = pg_query($con, $sql_tabela_item);

						if (pg_last_error()){
							if ($update_produto == false){
								$log_erro[] = "ARQUIVO: $nome_arquivo LINHA ".($key + 1).": - ERRO AO GRAVAR PREÇO DA PEÇA NO TELECONTROL - PEÇA REF. $referencia_peca  PREÇO. $preco_peca";
								continue;
							}
						}

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
							$query = pg_query($con, $sql);

							if (pg_last_error()) {
								$log_erro[] = "ARQUIVO: $nome_arquivo LINHA ".($key + 1).": - ERRO AO GRAVAR PREÇO DA PEÇA NO TELECONTROL - PEÇA REF. $referencia_peca  PREÇO. $preco_peca";
								continue;
							}else{
								$log_success[] = "ARQUIVO: $nome_arquivo - PREÇO DA PEÇA INSERIDO COM SUCESSO - PEÇA REF. $referencia_peca  PREÇO. $preco_peca";
							}
						} else {
							$sql = "UPDATE tbl_tabela_item SET
										preco = $preco_peca
									WHERE tbl_tabela_item.tabela = $tabela_id
									AND   tbl_tabela_item.peca   = $peca_id";
							$query = pg_query($con, $sql);
							
							if (pg_last_error()) {
								$log_erro[] = "ARQUIVO: $nome_arquivo LINHA ".($key + 1).": - ERRO AO ATUALIZAR PREÇO DA PEÇA NO TELECONTROL - PEÇA REF. $referencia_peca  PREÇO. $preco_peca";
								continue;
							}
						}
					}
				}

				ftp_chmod($conn_id, 0777, "in/bkp");
				ftp_put($conn_id, "in/bkp/$now-$arquivo","$local_pre/$arquivo", FTP_BINARY);
				
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
					$cmds = "cp $log_dir/importa-preco-err-$nome_arquivo-$now.log $log_dir/importa-preco-err-$nome_arquivo-$data_arq_enviar.txt";
					system($cmds, $retorno);
					
					if ($retorno == 0){
						$manda_email = true;
						$arquivos_email[] = "$log_dir/importa-preco-err-$nome_arquivo-$data_arq_enviar.txt";
					}
				}else{
					$data_arq_process = date('Ymd');
					ftp_delete($conn_id, "$server_file$arquivo");
				}
				system("mv $diretorio_origem$arquivo /tmp/$fabrica_nome/telecontrol-pre-importados/$nome_arquivo-$data_arq_process-ok.txt");
			}
		}

		if ($manda_email === true){
			$zip = "zip $log_dir/importa-preco-err-$data_arq_enviar.zip ".implode(' ', $arquivos_email)." 1>/dev/null";
			system($zip, $retorno);

			require_once dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';
			$assunto = ucfirst($fabrica_nome) . utf8_decode(': Importação de preços ') . date('d/m/Y');
			$mail = new PHPMailer();
			$mail->IsHTML(true);
			$mail->From = 'helpdesk@telecontrol.com.br';
			$mail->FromName = 'Telecontrol';

			$mail->AddAddress('guilherme.monteiro@telecontrol.com.br');
			$mail->Subject = $assunto;
			$mail->Body = "Segue anexo arquivo de log erro importado na rotina...<br/><br/>";
			
			if (count($arquivos_email) > 0){
				$mail->AddAttachment("$log_dir/importa-preco-err-$data_arq_enviar.zip", "importa-preco-err-$data_arq_enviar.zip");
			}
			if (!$mail->Send()) {
				echo 'Erro ao enviar email: ' , $mail->ErrorInfo;
			} else {
				unlink("$log_dir/importa-preco-err-$data_arq_enviar.zip");
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
