<?php
/**
 *
 * importa-produto.php
 *
 * Importação de produtos Roca
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
	

	$prd_in = "/tmp/roca/ftp-pasta-in/prd";
	if (!is_dir($prd_in)) {
		if (!mkdir($prd_in, 0777, true)) {
			throw new Exception("ERRO: Não foi possível criar logs. falha ao criar diretório: $log_dir");
		}
	}
	
	$log_dir = '/tmp/' . $fabrica_nome . '/logs';
	if (!is_dir($log_dir)) {
		if (!mkdir($log_dir, 0777, true)) {
			throw new Exception("ERRO: Não foi possível criar logs. falha ao criar diretório: $log_dir");
		}
	}

	$prd_importados = '/tmp/roca/telecontrol-prd-importados';
	if(!is_dir($prd_importados)){
		if (!mkdir($prd_importados,0777,true)) {
			throw new Exception("ERRO: Não foi possível criar logs. falha ao criar diretório: $prd_importados");
		}
	}

	$local_prd = "$prd_in/";
	$server_file = "in/";
	$arquivos = ftp_nlist($conn_id,"in");
	$log_erro = array();
	$log_success =  array();

	foreach ($arquivos as $key => $value) {
		$pos = strpos( $value, "PRD" );
		if ($pos === false) {
			continue;
		} else {
			if (ftp_get($conn_id, $local_prd.$value, $server_file.$value, FTP_BINARY)){
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

	$diretorio_origem = $local_prd;
	
	date_default_timezone_set('America/Sao_Paulo');
	$now = date('Ymd_His');

	$dir = opendir($diretorio_origem);
	$parametros_adic = array();
	$parametros_adicionais_produto_array = array();
	if ($dir){
		while(false !== ($arquivo = readdir($dir))) {
			$nome_arquivo  = explode(".", $arquivo);
			$nome_arquivo = $nome_arquivo[0];
			if(in_array($arquivo,array('.','..'))) continue;
			$arq_log = $log_dir . '/importa-produto-success-' .$nome_arquivo.'-'. $now . '.log';
			$err_log = $log_dir . '/importa-produto-err-' .$nome_arquivo.'-'. $now . '.log';
			
			unset($log_erro);
			unset($log_success);
			if (file_exists($diretorio_origem.$arquivo) and (filesize($diretorio_origem.$arquivo) > 0)) {
					
				$conteudo = file_get_contents($diretorio_origem.$arquivo);
				$conteudo = explode("\n", $conteudo);

				$log_erro[] = " ==== LOG ERRO INÍCIO: ".date("H:i")." ==== ";
				$log_success[] = " ==== LOG SUCCESS ".date("H:i")." ==== ";
				foreach ($conteudo as $key => $value) {
					
					if (!empty($value)) {
						unset($parametros_adic);
						unset($parametros_adicionais_produto_array);
						
						unset($linha_id);
						unset($familia_id);
						unset($produto);

						list($referencia, $descricao, $codigo_linha, $codigo_familia, $logomarca, $codigo_marca, $origem, $voltagem, $mao_de_obra, $ncm, $nome_comercial, $fora_linha) = explode("|", $value);
						
						$garantia = 0;
						$referencia  	= strtim($referencia);
						$descricao 	 	= strtim($descricao);
						$codigo_linha 	= strtim($codigo_linha);
						$codigo_familia	= strtim($codigo_familia);
						$logomarca 	 	= strtim($logomarca);
						$codigo_marca 	= strtim($codigo_marca);
						$origem 	 	= strtim($origem);
						$voltagem 	 	= strtim($voltagem);
						$mao_de_obra 	= strtim($mao_de_obra); 
						$ncm 		 	= strtim($ncm);
						$garantia 	 	= strtim($garantia);
						$fora_linha	 	= strtim($fora_linha);

						if ($codigo_linha == "01"){
							$garantia = 120;
						}else if ($codigo_linha == "04"){
							$garantia = 120;
						}else if ($codigo_linha == "02"){
							$garantia = 12;
						}else if ($codigo_linha == "06"){
							$garantia = 12;
						}else if ($codigo_linha == "03"){
							$garantia = 12;
						}
						
						if (empty($descricao) OR empty($referencia)){
							$log_erro[] = "ARQUIVO: $nome_arquivo LINHA ".($key + 1).": - PRODUTO SEM REFERENCIA OU DESCRICAO";
							continue;
						}

						if (!empty($descricao)){
							$descricao = utf8_decode($descricao);
						}

						if ($mao_de_obra) {
							$mao_de_obra = str_replace(",", ".", $mao_de_obra);
						} else {
							$mao_de_obra = 0;
						}

						if (!empty($codigo_marca)) {
							$sql = "SELECT marca
									FROM tbl_marca
									WHERE fabrica = $fabrica
									AND codigo_marca = '$codigo_marca'";
							$res = pg_query($con,$sql);
							
							if (pg_last_error()){
								$log_erro[] = "ARQUIVO: $nome_arquivo LINHA ".($key + 1).": - MARCA NÃO CADASTRADA NO TELECONTROL - PRODUTO REF. $referencia  DESC. $descricao COD. MARCA $codigo_marca";
							}else{
								if (pg_num_rows($res)) {
									$id_marca = pg_result($res,0,0);
								} else {
									$id_marca = '';
								}
							}
						} else {
							$id_marca = '';
						}
						
						$sql_linha = "
							SELECT linha FROM tbl_linha
							WHERE tbl_linha.codigo_linha = TRIM('$codigo_linha')
							AND tbl_linha.fabrica = $fabrica LIMIT 1";
						$query_linha = pg_query($con, $sql_linha);
						
						if (pg_last_error()) {
							$log_erro[] = "ARQUIVO: $nome_arquivo LINHA ".($key + 1).": - LINHA NÃO CADASTRADA NO TELECONTROL - PRODUTO REF. $referencia  DESC. $descricao COD. LINHA $codigo_linha";
							continue;
						}

						if (pg_num_rows($query_linha) == 1) {
							$linha_id = pg_fetch_result($query_linha, 0, 'linha');
						} else {
							$log_erro[] = "ARQUIVO: $nome_arquivo LINHA ".($key + 1).": - LINHA NÃO CADASTRADA NO TELECONTROL - PRODUTO REF. $referencia  DESC. $descricao COD. LINHA $codigo_linha";
							continue;
						}
						
						$sql_familia = "SELECT familia FROM tbl_familia
										WHERE tbl_familia.codigo_familia = TRIM('$codigo_familia')
										AND tbl_familia.fabrica = $fabrica LIMIT 1";
						$query_familia = pg_query($con, $sql_familia);

						if (pg_last_error()) {
							$log_erro[] = "ARQUIVO: $nome_arquivo LINHA ".($key + 1).": - FAMILIA NÃO CADASTRADA NO TELECONTROL - PRODUTO REF. $referencia  DESC. $descricao COD. FAMILIA $codigo_familia";
							continue;
						}

						if (pg_num_rows($query_familia) == 1) {
							$familia_id = pg_fetch_result($query_familia, 0, 'familia');
						} else {
							$log_erro[] = "ARQUIVO: $nome_arquivo LINHA ".($key + 1).": - FAMILIA NÃO CADASTRADA NO TELECONTROL - PRODUTO REF. $referencia  DESC. $descricao COD. FAMILIA $codigo_familia";
							continue;
						}
						
						$sql_produto = "
								SELECT tbl_produto.produto, tbl_produto.parametros_adicionais FROM tbl_produto JOIN tbl_linha USING (linha)
								WHERE tbl_produto.referencia = '$referencia' AND tbl_linha.fabrica = $fabrica 
								AND tbl_produto.fabrica_i = $fabrica";
						$query_produto = pg_query($con, $sql_produto);
						
						if (pg_last_error()) {
							$log_erro[] = "ARQUIVO: $nome_arquivo LINHA ".($key + 1).": - PRODUTO NÃO ENCONTRADO NO TELECONTROL - PRODUTO REF. $referencia  DESC. $descricao";
							continue;
						}
						
						if (pg_num_rows($query_produto) == 0) {
							
							if (!empty($id_marca)){
								$parametros_adic["marcas"] = $id_marca;
							}

							if (!empty($fora_linha) AND $fora_linha == 'T'){
								$parametros_adic["fora_linha"] = $fora_linha;
							}

							if (!empty($parametros_adic) AND is_array($parametros_adic)){
								$parametros_adic = json_encode($parametros_adic);
							}
						
							$sql = "INSERT INTO tbl_produto (
										fabrica_i,
										lista_troca,
										linha,
										familia,
										referencia,
										descricao,
										origem,
										voltagem,
										garantia,
										mao_de_obra,
										mao_de_obra_admin
										".((empty($parametros_adic)) ? "" : ", parametros_adicionais")."
									)VALUES(
										$fabrica,
										't',
										".((empty($linha_id)) ? "null" : $linha_id).",
										".((empty($familia_id)) ? "null" : $familia_id).",
										".((empty($referencia)) ? "null" : "'".$referencia."'").",
										".((empty($descricao)) ? "null" : "(E'$descricao')").",
										".((empty($origem)) ? "null" : "'".$origem."'").",
										".((empty($voltagem)) ? "null" : "'".$voltagem."'").",
										".((empty($garantia)) ? "0" : $garantia).",
										".((empty($mao_de_obra)) ? "0" : $mao_de_obra).",
										".((empty($mao_de_obra)) ? "0" : $mao_de_obra)."
										".((empty($parametros_adic)) ? "" : ", '".$parametros_adic."'")."
									)";
							$query = pg_query($con, $sql);

							if (pg_last_error()) {
								$log_erro[] = "ARQUIVO: $nome_arquivo LINHA ".($key + 1).": - ERRO AO CADASTRAR PRODUTO NO TELECONTROL - PRODUTO REF. $referencia  DESC. $descricao COD. LINHA $codigo_linha COD. FAMILIA $codigo_familia";
								continue;
							}else{
								$log_success[] = "ARQUIVO: $nome_arquivo - PRODUTO INSERIDO COM SUCCESSO - PRODUTO REF. $referencia  DESC. $descricao COD. LINHA $codigo_linha COD. FAMILIA $codigo_familia";
							}
						} else {
							$produto = pg_fetch_result($query_produto, 0, 'produto');
							$parametros_adicionais_produto = pg_fetch_result($query_produto, 0, 'parametros_adicionais');
							
							if (!empty($parametros_adicionais_produto)){
								$parametros_adicionais_produto = json_decode($parametros_adicionais_produto, true);
							}

							if (!empty($id_marca)){
								$parametros_adicionais_produto_array["marcas"] = $id_marca;
							}

							if (!empty($fora_linha) AND $fora_linha == "T"){
								$parametros_adicionais_produto_array["fora_linha"] = $fora_linha;
							}

							if (!empty($parametros_adicionais_produto_array) AND is_array($parametros_adicionais_produto_array)){
								$parametros_adic_json = json_encode($parametros_adicionais_produto_array);
								$update_marca = ", parametros_adicionais = '$parametros_adic_json' ";
							}

							$sql = "UPDATE tbl_produto SET
									descricao = ".((empty($descricao)) ? "null" : "(E'$descricao')").",
									origem = ".((empty($origem)) ? "null" : "'".$origem."'").",
									voltagem = ".((empty($voltagem)) ? "null" : "'".$voltagem."'").",
									mao_de_obra = ".((empty($mao_de_obra)) ? "null" : $mao_de_obra).",
									linha = ".((empty($linha_id)) ? "null" : $linha_id).",
									garantia = ".((empty($garantia)) ? "0" : $garantia).",
									familia = ".((empty($familia_id)) ? "null" : $familia_id)."
									$update_marca
									WHERE tbl_produto.produto = $produto";
							$query = pg_query($con, $sql);

							if (pg_last_error()){
								$log_erro[] = "ARQUIVO: $nome_arquivo LINHA ".($key + 1).": - ERRO AO ATUALIZAR PRODUTO NO TELECONTROL- PRODUTO REF. $referencia  DESC. $descricao COD. LINHA $codigo_linha COD. FAMILIA $codigo_familia";
								continue;
							}else{
								$log_success[] = "ARQUIVO: $nome_arquivo - PRODUTO ATUALIZADO COM SUCCESSO - PRODUTO REF. $referencia  DESC. $descricao COD. LINHA $codigo_linha COD. FAMILIA $codigo_familia";
							}
						}


						$sql_peca = "
								SELECT peca
                                FROM tbl_peca
								WHERE fabrica = $fabrica
								AND referencia = '$referencia'";
						$res_peca = pg_query($con, $sql_peca);
						
						if (pg_num_rows($res_peca) == 0){
							$sql_p = "INSERT INTO tbl_peca (
										fabrica,
										referencia,
										descricao,
										origem,
										ipi,
										produto_acabado
									)VALUES(
										$fabrica,
										".((empty($referencia)) ? "null" : "(E'$referencia')").",
										".((empty($descricao)) ? "null" : "(E'$descricao')").",
										".((empty($origem)) ? "'NAC'" : "'".$origem."'").",
										0,
										't'
									)";
							$query = pg_query($con, $sql_p);

							if (pg_last_error()) {
								$log_erro[] = "ARQUIVO: $nome_arquivo LINHA ".($key + 1).": - ERRO AO CADASTRAR PRODUTO COMO PRODUTO ACABADO NO TELECONTROL- PRODUTO REF. $referencia  DESC. $descricao COD. LINHA $codigo_linha COD. FAMILIA $codigo_familia";
							}
						} else {
							$id_peca = pg_fetch_result($res_peca, 0, "peca");

							$sql_up = "
								UPDATE tbl_peca set produto_acabado = 't'
								WHERE tbl_peca.peca = {$id_peca}
								AND tbl_peca.fabrica = {$fabrica} ";
							$res_up = pg_query($con, $sql_up);
						}							 
					}
				}

				ftp_chmod($conn_id, 0777, "in/bkp");
				ftp_put($conn_id, "in/bkp/$now-$arquivo","$local_prd/$arquivo", FTP_BINARY);
				
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
					$cmds = "cp $log_dir/importa-produto-err-$nome_arquivo-$now.log $log_dir/importa-produto-err-$nome_arquivo-$data_arq_enviar.txt";
					system($cmds, $retorno);
					
					if ($retorno == 0){
						$manda_email = true;
						$arquivos_email[] = "$log_dir/importa-produto-err-$nome_arquivo-$data_arq_enviar.txt";
					}
				}else{
					$data_arq_process = date('Ymd');
					ftp_delete($conn_id, "$server_file$arquivo");
				}
				system("mv $diretorio_origem$arquivo /tmp/$fabrica_nome/telecontrol-prd-importados/$nome_arquivo-$data_arq_process-ok.txt");
			}
		}

		if ($manda_email === true){
			$zip = "zip $log_dir/importa-produto-err-$data_arq_enviar.zip ".implode(' ', $arquivos_email)." 1>/dev/null";
			system($zip, $retorno);

			require_once dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';
			$assunto = ucfirst($fabrica_nome) . utf8_decode(': Importação de produtos ') . date('d/m/Y');
			$mail = new PHPMailer();
			$mail->IsHTML(true);
			$mail->From = 'helpdesk@telecontrol.com.br';
			$mail->FromName = 'Telecontrol';

			$mail->AddAddress('guilherme.monteiro@telecontrol.com.br');
			$mail->Subject = $assunto;
			$mail->Body = "Segue anexo arquivo de log erro importado na rotina...<br/><br/>";
			
			if (count($arquivos_email) > 0){
				$mail->AddAttachment("$log_dir/importa-produto-err-$data_arq_enviar.zip", "importa-produto-err-$data_arq_enviar.zip");
			}
			if (!$mail->Send()) {
				echo 'Erro ao enviar email: ' , $mail->ErrorInfo;
			} else {
				unlink("$log_dir/importa-produto-err-$data_arq_enviar.zip");
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
