<?php
/**
 *
 * importa-posto.php
 *
 * Importação de posto Roca
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
	

	$pst_in = "/tmp/roca/ftp-pasta-in/pst";
	if (!is_dir($pst_in)) {
		if (!mkdir($pst_in, 0777, true)) {
			throw new Exception("ERRO: Não foi possível criar logs. falha ao criar diretório: $log_dir");
		}
	}
	
	$log_dir = '/tmp/' . $fabrica_nome . '/logs';
	if (!is_dir($log_dir)) {
		if (!mkdir($log_dir, 0777, true)) {
			throw new Exception("ERRO: Não foi possível criar logs. falha ao criar diretório: $log_dir");
		}
	}

	$pst_importados = '/tmp/roca/telecontrol-pst-importados';
	if(!is_dir($pst_importados)){
		if (!mkdir($pst_importados,0777,true)) {
			throw new Exception("ERRO: Não foi possível criar logs. falha ao criar diretório: $pst_importados");
		}
	}

	$local_pst = "$pst_in/";
	$server_file = "in/";
	$arquivos = ftp_nlist($conn_id,"in");
	$log_erro = array();
	$log_div = array();
	$log_success =  array();


	foreach ($arquivos as $key => $value) {
		$pos = strpos( $value, "PST" );
		if ($pos === false) {
			continue;
		} else {
			if (ftp_get($conn_id, $local_pst.$value, $server_file.$value, FTP_BINARY)){
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

	$diretorio_origem = $local_pst;
	
	date_default_timezone_set('America/Sao_Paulo');
	$now = date('Ymd_His');

	$dir = opendir($diretorio_origem);

	if ($dir){
		while(false !== ($arquivo = readdir($dir))) {
			$nome_arquivo  = explode(".", $arquivo);
			$nome_arquivo = $nome_arquivo[0];

			$arq_log = $log_dir . '/importa-posto-success-' .$nome_arquivo.'-'. $now . '.log';
			$err_log = $log_dir . '/importa-posto-err-' .$nome_arquivo.'-'. $now . '.log';
			
			$div_log = $log_dir . '/importa-posto-dados-divergente-' .$nome_arquivo.'-'. $now . '.log';
			unset($log_erro);
			unset($log_success);
			unset($log_div);
			
			if (file_exists($diretorio_origem.$arquivo) and (filesize($diretorio_origem.$arquivo) > 0)) {
				
				$conteudo = file_get_contents($diretorio_origem.$arquivo);
				$conteudo = explode("\n", $conteudo);

				$log_erro[] = " ==== LOG ERRO INÍCIO: ".date("H:i")." ==== ";
				$log_success[] = " ==== LOG SUCCESS ".date("H:i")." ==== ";
				$log_div[] = " ==== LOG DIVERGENCIA INÍCIO: ".date("H:i")." ==== ";
				foreach ($conteudo as $key => $value) {
					if (!empty($value)) {
						unset($posto);
						unset($tipo_posto);
						
						list($codigo_cliente, $razao, $nome_fantasia, $cnpj, $ie, $endereco, $numero, $complemento, $bairro, $cep, $cidade, $estado, $email, $telefone, $fax, $contato, $capital_interior, $tipo_posto, $porcentagem, $codigo_fornecedor) = explode("|", $value);
						
						$codigo_cliente 	= (!empty($codigo_cliente)) ? strtim($codigo_cliente) : $codigo_cliente;
						$razao 				= (!empty($razao)) ? strtim(utf8_decode($razao)) : $razao;
						$nome_fantasia 		= (!empty($nome_fantasia)) ? strtim(utf8_decode($nome_fantasia)) : $nome_fantasia;
						$cnpj 				= (!empty($cnpj)) ? strtim($cnpj) : $cnpj;
						$codigo_posto 		= $cnpj;
						$ie 				= (!empty($ie)) ? strtim($ie) : $ie;
						$endereco 			= (!empty($endereco)) ? strtim(utf8_decode($endereco)) : $endereco;
						$numero 			= (!empty($numero)) ? strtim($numero) : $numero;
						$complemento 		= (!empty($complemento)) ? strtim(utf8_decode($complemento)) : $complemento;
						$bairro 			= (!empty($bairro)) ? strtim(utf8_decode($bairro)) : $bairro;
						$codigo_fornecedor 	= (!empty($codigo_fornecedor)) ? strtim($codigo_fornecedor) : $codigo_fornecedor;
						$cep 				= (!empty($cep)) ? strtim($cep) : $cep;
						$cep 				= preg_replace("/\D/", "", $cep);

						$cidade 			= (!empty($cidade)) ? strtim(utf8_decode($cidade)) : $cidade;
						$estado 			= (!empty($estado)) ? strtim($estado) : $estado;
						$email 				= (!empty($email)) ? strtim($email) : $email;
						$telefone 			= (!empty($telefone)) ? strtim($telefone) : $telefone;
						$fax 				= (!empty($fax)) ? strtim($fax) : $fax;
						$capital_interior 	= (!empty($capital_interior)) ? strtim($capital_interior) : $capital_interior;
						$contato 			= (!empty($contato)) ? strtim(utf8_decode($contato)) : $contato;
						$tipo_posto 		= (!empty($tipo_posto)) ? strtim($tipo_posto) : $tipo_posto;

						#$codigo_cliente 	= adicionalTrim($codigo_cliente, 10);
						$razao 				= cortaStr($razao, 60);
						$nome_fantasia 		= cortaStr($nome_fantasia, 60);
						$cnpj 				= adicionalTrim($cnpj, 14);
						$ie 				= adicionalTrim($ie);
						$endereco 			= cortaStr($endereco, 50);
						$numero 			= adicionalTrim($numero);
						$complemento 		= adicionalTrim($complemento);
						$bairro 			= cortaStr($bairro, 20);
						$cep 				= cortaStr($cep, 8);
						$cidade 			= cortaStr($cidade, 30);
						$estado 			= cortaStr($estado, 2);
						$email 				= strtolower(cortaStr($email, 50));
						$telefone 			= cortaStr($telefone, 30);
						$fax 				= cortaStr($fax, 30);
						$contato 			= cortaStr($contato, 30);

						$valida_cpnj = pg_query($con, "SELECT fn_valida_cnpj_cpf('$cnpj')");

						if (pg_last_error()) {
							$log_erro[] = "ARQUIVO: $nome_arquivo LINHA ".($key + 1).": - ERRO AO VALIDAR CNPJ DO POSTO AUTORIZADO - POSTO COD. $codigo_posto  NOME. $razao";
							continue;
						}
						
						$sql_posto = "SELECT tbl_posto.posto, tbl_posto.nome, tbl_posto.ie FROM tbl_posto WHERE tbl_posto.cnpj = '$cnpj'";
						$query_posto = pg_query($con, $sql_posto);
						
						if (pg_last_error()) {
							$log_erro[] = "ARQUIVO: $nome_arquivo LINHA ".($key + 1).": - POSTO NÃO CADASTRADO NO TELECONTROL - POSTO COD. $codigo_posto  NOME. $razao";
							continue;
						}
						
						if (pg_num_rows($query_posto) == 0) {
							$sql = "INSERT INTO tbl_posto (
										nome,
										nome_fantasia,
										cnpj,
										ie,
										endereco,
										numero,
										complemento,
										bairro,
										cep,
										cidade,
										estado,
										email,
										fone,
										fax,
										contato,
										capital_interior
									)VALUES(
										".((empty($razao)) ? "null" : "(E'$razao')").",
										".((empty($nome_fantasia)) ? "null" : "(E'$nome_fantasia')").",
										".((empty($cnpj)) ? "null" : "'".$cnpj."'").",
										".((empty($ie)) ? "null" : "'".$ie."'").",
										".((empty($endereco)) ? "null" : "(E'$endereco')").",
										".((empty($numero)) ? "null" : "'".$numero."'").",
										".((empty($complemento)) ? "null" : "(E'$complemento')").",
										".((empty($endereco)) ? "null" : "(E'$bairro')").",
										".((empty($cep)) ? "null" : "'".$cep."'").",
										".((empty($cidade)) ? "null" : "'".$cidade."'").",
										".((empty($estado)) ? "null" : "'".$estado."'").",
										".((empty($email)) ? "null" : "'".$email."'").",
										".((empty($telefone)) ? "null" : "'".$telefone."'").",
										".((empty($fax)) ? "null" : "'".$fax."'").",
										".((empty($contato)) ? "null" : "(E'$contato')").",
										".((empty($capital_interior)) ? "null" : "'".$capital_interior."'")."
									)";
							$query = pg_query($con, $sql);

							if (pg_last_error()) {
								$log_erro[] = "ARQUIVO: $nome_arquivo LINHA ".($key + 1).": - ERRO AO INSERIR POSTO AUTORIZADO NO TELECONTROL - POSTO COD. $codigo_posto  NOME. $razao";
								continue;
							}else{
								$log_success[] = "ARQUIVO: $nome_arquivo - POSTO AUTORIZADO INSERIDO COM SUCCESSO - POSTO COD. $codigo_posto  NOME. $razao";
							}

							$query_posto_id = pg_query($con, "SELECT currval ('seq_posto') AS seq_posto");
							$posto = pg_fetch_result($query_posto_id, 0, 'seq_posto');

							$sql_insert = "
								INSERT INTO tbl_posto_linha (posto, tabela, ativo, linha)
								(SELECT $posto, tbl_tabela.tabela, 't', tbl_linha.linha
									FROM tbl_linha 
									JOIN tbl_tabela ON tbl_tabela.fabrica = tbl_linha.fabrica 
									WHERE tbl_linha.fabrica = $fabrica 
									AND tbl_linha.ativo IS TRUE 
									AND tbl_tabela.ativa IS TRUE 
									AND tbl_tabela.sigla_tabela = 'PAD'
								)";
							$res_insert = pg_query($con, $sql_insert);

							if (pg_last_error()) {
								$log_erro[] = "ARQUIVO: $nome_arquivo LINHA ".($key + 1).": - ERRO AO INSERIR LINHAS PARA O POSTO AUTORIZADO - POSTO COD. $codigo_posto  NOME. $razao";
								continue;
							}

						} else {
							$iePosto          = pg_fetch_result($query_posto, 0, ie);
							$razaoSocialPosto = pg_fetch_result($query_posto, 0, nome);
							if ($iePosto != $ie || $razaoSocialPosto != $razao) {
								$log_div[] = "Dados BASE: Razão Social: $razaoSocialPosto IE: $iePosto  <br/> Dados FÁBRICA: Razão Social: $razao IE: $ie";
							}
							$posto = pg_fetch_result($query_posto, 0, 'posto');
						}

						$sql = "SELECT 
								    tbl_posto_fabrica.posto
								FROM tbl_posto_fabrica
								WHERE tbl_posto_fabrica.posto = $posto
								AND tbl_posto_fabrica.fabrica = $fabrica";
						$query = pg_query($con, $sql);

						if (pg_last_error()) {
							$log_erro[] = "ARQUIVO: $nome_arquivo LINHA ".($key + 1).": - POSTO NÃO CADASTRADO NO TELECONTROL - POSTO COD. $codigo_posto  NOME. $razao";
							continue;
						}

						if (pg_num_rows($query) == 0) {
							
							$sql_tipo = "SELECT tipo_posto FROM tbl_tipo_posto WHERE fabrica = $fabrica AND descricao = 'Autorizada'";
							$res_tipo = pg_query($con, $sql_tipo);

							if (pg_last_error()){
								$log_erro[] = "ARQUIVO: $nome_arquivo LINHA ".($key + 1).": - ERRO AO PESQUISAR TIPO DE POSTO AUTORIZADO PARA FÁBRICA - POSTO COD. $codigo_posto  NOME. $razao";
								continue;
							}

							if (pg_num_rows($res_tipo) > 0){
								$tipo_posto = pg_fetch_result($res_tipo, 0, "tipo_posto");
							}

							$sql = "INSERT INTO tbl_posto_fabrica(
										fabrica,
										posto,
										valor_km,
										senha,
										tipo_posto,
										login_provisorio,
										codigo_posto,
										credenciamento,
										pedido_faturado,
										contato_fone_comercial,
										contato_fax,
										contato_endereco ,
										contato_numero,
										contato_complemento,
										contato_bairro,
										contato_cep,
										contato_cidade,
										contato_estado,
										contato_email,
										nome_fantasia,
										contato_nome,
										centro_custo,
										conta_contabil
									)VALUES(
										$fabrica,
										$posto,
										'0.93',
										'*',
										".((empty($tipo_posto)) ? "null" : $tipo_posto).",
										null,
										".((empty($codigo_posto)) ? "null" : "'".$codigo_posto."'").",
										'CREDENCIADO',
										'f',
										".((empty($telefone)) ? "null" : "'".$telefone."'").",
										".((empty($fax)) ? "null" : "'".$fax."'").",
										".((empty($endereco)) ? "null" : "(E'$endereco')").",
										".((empty($numero)) ? "null" : "'".$numero."'").",
										".((empty($complemento)) ? "null" : "'".$complemento."'").",
										".((empty($bairro)) ? "null" : "(E'$bairro')").",
										".((empty($cep)) ? "null" : "'".$cep."'").",
										".((empty($cidade)) ? "null" : "'".$cidade."'").",
										".((empty($estado)) ? "null" : "'".$estado."'").",
										".((empty($email)) ? "null" : "'".$email."'").",
										".((empty($nome_fantasia)) ? "null" : "(E'$nome_fantasia')").",
										".((empty($contato)) ? "null" : "(E'$contato')").",
										".((empty($codigo_cliente)) ? "null" : "'".$codigo_cliente."'").",
										".((empty($codigo_fornecedor)) ? "null" : "'".$codigo_fornecedor."'")."
									)";
							$query = pg_query($con, $sql);
							
							if (pg_last_error()) {
								$log_erro[] = "ARQUIVO: $nome_arquivo LINHA ".($key + 1).": - ERRO AO INSERIR POSTO AUTORIZADO NO TELECONTROL - POSTO COD. $codigo_posto  NOME. $razao";
								continue;
							}
						} else {
							$sql = "UPDATE tbl_posto_fabrica SET
											codigo_posto = '$codigo_posto',
											contato_endereco = (E'$endereco'),
											contato_bairro = (E'$bairro'),
											contato_cep = '$cep',
											contato_cidade = (E'$cidade'),
											contato_estado = '$estado',
											contato_fone_comercial = '$telefone',
											contato_fax = '$fax',
											nome_fantasia = (E'$nome_fantasia'),
											contato_email = '$email',
											centro_custo = '$codigo_cliente',
											conta_contabil = '$codigo_fornecedor'
									WHERE tbl_posto_fabrica.posto = $posto
									AND tbl_posto_fabrica.fabrica = $fabrica";
							$query = pg_query($con, $sql);
							
							if (pg_last_error()) {
								$log_erro[] = "ARQUIVO: $nome_arquivo LINHA ".($key + 1).": - ERRO AO ATUALIZAR POSTO AUTORIZADO PARA FÁBRICA - POSTO COD. $codigo_posto  NOME. $razao";
								continue;
							}
						}
					}
				}

				ftp_chmod($conn_id, 0777, "in/bkp");
				ftp_put($conn_id, "in/bkp/$now-$arquivo","$local_pst/$arquivo", FTP_BINARY);
				
				if (count($log_erro) > 1){
					$elog = fopen($err_log, "w");
					$dados_log_erro = implode("\n", $log_erro);
					fwrite($elog, $dados_log_erro);
					fclose($elog);
				}

				if (count($log_div) > 1){
					$dlog = fopen($div_log, "w");
					$dados_log_div = implode("\n", $log_div);
					fwrite($dlog, $dados_log_div);
					fclose($dlog);
				}

				if (count($log_success) > 1){
					$slog = fopen($arq_log, "w");
					$dados_log_success = implode("\n", $log_success);
					fwrite($slog, $dados_log_success);
					fclose($slog);
				}
				
				if (filesize($div_log) > 0) {
					$data_arq_enviar = date('dmy');

					$cmds = "cp $log_dir/importa-posto-dados-divergente-$nome_arquivo-$now.log $log_dir/importa-posto-dados-divergente-$nome_arquivo-$data_arq_enviar.txt";
					system($cmds, $retorno);
					
					if ($retorno == 0){
						$manda_email = true;
						$arquivos_email_div[] = "$log_dir/importa-posto-dados-divergente-$nome_arquivo-$data_arq_enviar.txt";
					}
				}

				if (filesize($err_log) > 0) {
					$data_arq_enviar = date('dmy');
					$cmds = "cp $log_dir/importa-posto-err-$nome_arquivo-$now.log $log_dir/importa-posto-err-$nome_arquivo-$data_arq_enviar.txt";
					system($cmds, $retorno);
					
					if ($retorno == 0){
						$manda_email = true;
						$arquivos_email[] = "$log_dir/importa-posto-err-$nome_arquivo-$data_arq_enviar.txt";
					}
				}else{
					$data_arq_process = date('Ymd');
					ftp_delete($conn_id, "$server_file$arquivo");
				}
				system("mv $diretorio_origem$arquivo /tmp/$fabrica_nome/telecontrol-pst-importados/$nome_arquivo-$data_arq_process-ok.txt");
			}
		}

		if ($manda_email === true){
			// if (count($arquivos_email_div) > 0){
			// 	$zip_div = "zip $log_dir/importa-posto-dados-divergente-$data_arq_enviar.zip ".implode(' ', $arquivos_email_div)." 1>/dev/null";
			// 	system($zip_div, $retorno);
			// }

			if (count($arquivos_email) > 0){
				$zip = "zip $log_dir/importa-posto-err-$data_arq_enviar.zip ".implode(' ', $arquivos_email)." 1>/dev/null";
				system($zip, $retorno);
			}
			
			require_once dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';
			$assunto = ucfirst($fabrica_nome) . utf8_decode(': Importação de postos ') . date('d/m/Y');
			$mail = new PHPMailer();
			$mail->IsHTML(true);
			$mail->From = 'helpdesk@telecontrol.com.br';
			$mail->FromName = 'Telecontrol';

			$mail->AddAddress('guilherme.monteiro@telecontrol.com.br');
			$mail->Subject = $assunto;
			$mail->Body = "Segue anexo arquivo de log erro importado na rotina...<br/><br/>";
			
			if (count($arquivos_email) > 0){
				$mail->AddAttachment("$log_dir/importa-posto-err-$data_arq_enviar.zip", "importa-posto-err-$data_arq_enviar.zip");
			}
			
			// if (count($arquivos_email_div) > 0){
			// 	$mail->AddAttachment("$log_dir/importa-posto-dados-divergente-$data_arq_enviar.zip", "importa-posto-dados-divergente-$data_arq_enviar.zip");
			// }

			if (!$mail->Send()) {
				echo 'Erro ao enviar email: ' , $mail->ErrorInfo;
			} else {
				if (count($arquivos_email) > 0){
					unlink("$log_dir/importa-posto-err-$data_arq_enviar.zip");
					foreach ($arquivos_email as $key => $value) {
						unlink($value);
					}
				}

				// if (count($arquivos_email_div)){
				// 	unlink("$log_dir/importa-posto-dados-divergente-$data_arq_enviar.zip");
				// 	foreach ($arquivos_email_div as $key => $value) {
				// 		unlink($value);
				// 	}
				// }
			}
		}
	}
	ftp_close($conn_id);
} catch (Exception $e) {
	echo $e->getMessage();
	ftp_close($conn_id);
}
?>
