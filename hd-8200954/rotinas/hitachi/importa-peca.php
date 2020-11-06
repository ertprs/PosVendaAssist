<?php
/**
 *
 * importa-peca.php
 *
 * Importação de peças hitachi
 */

error_reporting(E_ALL ^ E_NOTICE);

define('ENV', 'producao');

try {

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';

	// hitachi
	$fabrica = 147;
	$fabrica_nome = 'hitachi';

	function strtim($var)
	{
		if (!empty($var)) {
			$var = trim($var);
			$var = str_replace("'", "\'", $var);
		}

		return $var;
	}

	if (ENV != 'producao') {
		$diretorio_origem = "/home/william/public_html/treinamento/{$fabrica_nome}/"; //  teste local
		$ftp = "/home/william/public_html/treinamento/{$fabrica_nome}/retorno";
	}else{
		$diretorio_origem = "/home/hitachi/pos-vendas/{$fabrica_nome}-telecontrol/pecas/"; //  teste local
		$ftp = "/tmp/{$fabrica_nome}/telecontrol-{$fabrica_nome}";
	}
	
	$now = date('Ymd_His');

	$log_dir = '/tmp/' . $fabrica_nome .'/logs';
	$arq_log = $log_dir . '/importa-peca-' . $now . '.log';
	$err_log = $log_dir . '/importa-peca-err-' . $now . '.log';

	if (!is_dir($log_dir)) {
		if (!mkdir($log_dir, 0777, true)) {
			throw new Exception("ERRO: Não foi possível criar logs. Falha ao criar diretório: $log_dir");
		}
	}

	foreach (glob("{$diretorio_origem}Peca2*", GLOB_BRACE) as $arquivo) {
		if (!(file_exists($arquivo) and (filesize($arquivo) > 0))) {
			unlink($arquivo);
		} else {
			$conteudo = file_get_contents($arquivo);

			$conteudo = explode("\n", $conteudo);

			
			$nlog = fopen($arq_log, "w");
			$elog = fopen($err_log, "w");

			foreach ($conteudo as $linha) {
				if (!empty($linha)) {


					list (
							$referencia, 
							$descricao, 
							$unidade, 
							$origem_arq, 
							$ipi,
							$ncm,
							$ativo,
							$garantiaDiferenciada,
							$multiplo,
							$peso,
							$devolucaoObrigatoria,
							$blocGarantia,
							$critica,
							$aguarInspe,
							$acessorios
						) = explode ("\t",$linha);
					
					 $original = array(
							$referencia, 
							$descricao, 
							$unidade, 
							$origem_arq, 
							$ipi,
							$ncm,
							$ativo,
							$garantiaDiferenciada,
							$multiplo,
							$peso,
							$devolucaoObrigatoria,
							$blocGarantia,
							$critica,
							$aguarInspe,
							$acessorios
						);

					if (strlen($origem_arq) == 0) {
						$origem_arq = 'NAC';
					}

					$referencia		  	  = strtim($referencia);
					$descricao			  = strtim($descricao);
					$unidade			  = strtim($unidade);
					$origem_arq		  	  = strtim($origem_arq);
					$ipi				  = strtim($ipi);
					$ncm				  = strtim($ncm);
					$ativo				  = strtim($ativo);
					$garantiaDiferenciada = strtim($garantiaDiferenciada);
					$multiplo			  = strtim($multiplo);
					$peso				  = strtim($peso);
					$devolucaoObrigatoria = strtim($devolucaoObrigatoria);
					$blocGarantia		  = strtim($blocGarantia);
					$critica			  = strtim($critica);
					$aguarInspe			  = strtim($aguarInspe);
					$acessorios			  = strtim($acessorios);
					
					if (strlen($ipi)==0){
					   $ipi=0;
					}
					if(!strlen($garantiaDiferenciada)){
						$garantiaDiferenciada = 0;
					}

					if(!strlen($multiplo) or empty($multiplo)) {
						$multiplo = 1;
					}	
					if(!strlen($peso) or empty($peso)) {
						$peso = 'null';
					}

					if(!strlen($devolucaoObrigatoria)){
						$devolucaoObrigatoria = null;
					}	
					if(!strlen($blocGarantia)){
						$blocGarantia = null;
					}	
					if(!strlen($critica)){
						$critica = null;
					}	
					if(!strlen($aguarInspe)){
						$aguarInspe = null;
					}
					if(!strlen($acessorios)){
						$acessorios = false;
					}	

					$descricao = substr($descricao, 0, 50);

					$unidade = str_replace("-", "", $unidade);
					$unidade = str_replace(".", "", $unidade);
					$unidade = str_replace("/", "", $unidade);

					$origem_arq = str_replace("/", "", $origem_arq);
					$origem_arq = substr($origem_arq, 0, 10);

					$ipi = str_replace("/", "", $ipi);

					$sql_peca = "SELECT tbl_peca.peca FROM   tbl_peca
									WHERE  tbl_peca.referencia = '$referencia'
									AND    tbl_peca.fabrica    = $fabrica";
					$query_peca = pg_query($con, $sql_peca);

					if ($unidade) {
						$unidade = "'$unidade'";
					} else {
						$unidade = 'null';
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
										garantia_diferenciada,
										multiplo,
										peso,
										devolucao_obrigatoria,
										bloqueada_garantia,
										peca_critica,
										aguarda_inspecao,
										acessorio
									) VALUES (
										$fabrica,
										'$referencia',
										(E'$descricao'),
										$unidade,
										'$origem_arq',
										$ipi,
										'$ncm',
										'$ativo',
										$garantiaDiferenciada,
										$multiplo,
										$peso,
										'$devolucaoObrigatoria',
										'$blocGarantia',
										'$critica',
										'$aguarInspe',
										'$acessorios'
									) RETURNING peca ";

					} else {
						$peca = pg_fetch_result($query_peca, 0, 'peca');
						$sql = "UPDATE tbl_peca SET
									descricao             = (E'$descricao'),  		
									unidade               = $unidade,		 	
									origem                = '$origem_arq',	 	
									ipi                   = $ipi,
									ncm                   = '$ncm',			 		
									ativo                 = '$ativo',		 		
									garantia_diferenciada = $garantiaDiferenciada,
									multiplo              = $multiplo		 	 ,  
									peso                  = $peso	 			   ,
								 	devolucao_obrigatoria = '$devolucaoObrigatoria' ,
									bloqueada_garantia    = '$blocGarantia'	 	   ,
									peca_critica          = '$critica'		 ,		
									aguarda_inspecao      = '$aguarInspe'	,	 	
									acessorio 		      = '$acessorios'		 	
									WHERE tbl_peca.peca = $peca;";
					}

					$query = pg_query($con, $sql);
						
					if (pg_last_error()) {
						array_push($original, 'erro ao fazer update/insert'.pg_last_error());
						$log = implode(";", $original);
						fwrite($nlog, $log . "\n");

						$erro = "==============================\n\n";
						$erro.= $sql . "\n\n";
						$erro.= pg_last_error();
						$erro.= "\n\n";
						fwrite($elog, $erro);
					}

					$sql_p = "	SELECT produto
								FROM tbl_produto 
								WHERE UPPER(fn_retira_especiais(descricao)) ilike UPPER('acessorios%') 
								AND fabrica_i = {$fabrica}";
					$rest = pg_query($con,$sql_p);
					

					if($acessorio == true || $acessorio == "t"){

						while ($produto = pg_fetch_result($rest, 0, "produto")) {
						
						
						if (pg_last_error()) {
							array_push($original, 'erro produto acessorio nao encontrado');
							$log = implode(";", $original);
							fwrite($nlog, $log . "\n");

							$erro = "==============================\n\n";
							$erro.= $sql_p . "\n\n";
							$erro.= pg_last_error();
							$erro.= "\n\n";
							fwrite($elog, $erro);
						}
						
							if(pg_num_rows($query_peca) == 0){
								
								$peca = pg_fetch_result($query_peca , 0, 'peca');
								$sql = "SELECT peca 
											FROM tbl_lista_basica  
											WHERE peca = {$peca} 
											AND produto = {$produto}
											AND fabrica = {$fabrica}";
								$res_familia = pg_query($con,$sql);

								if(pg_num_rows($res_familia)==0) {

									$sql_l = "INSERT INTO tbl_lista_basica (produto,peca,qtde,fabrica) VALUES ({$produto},{$peca},1,{$fabrica}) ";
									$res = pg_query($con,$sql_l);
									
									if (pg_last_error()) {
										array_push($original, 'erro produto inserir tbl_lista_basica acessorios');
										$log = implode(";", $original);
										fwrite($nlog, $log . "\n");

										$erro = "==============================\n\n";
										$erro.= $sql_l . "\n\n";
										$erro.= pg_last_error();
										$erro.= "\n\n";
										fwrite($elog, $erro);
									}

								}

							}else{

								$peca = pg_fetch_result($query_peca , 0, 'peca');
								$sql = "SELECT peca 
											FROM tbl_lista_basica  
											WHERE peca = {$peca} 
											AND produto = {$produto}
											AND fabrica = {$fabrica}";
								$res_familia = pg_query($con,$sql);

								if(pg_num_rows($res_familia)==0) {

									$sql_l = "INSERT INTO tbl_lista_basica (produto,peca,qtde,fabrica) VALUES ({$produto},{$peca},1,{$fabrica}) ";
									$res = pg_query($con,$sql_l);

									if (pg_last_error()) {
										array_push($original, 'erro ao inserir tbl_lista_basica acessorios');
										$log = implode(";", $original);
										fwrite($nlog, $log . "\n");

										$erro = "==============================\n\n";
										$erro.= $sql_l . "\n\n";
										$erro.= pg_last_error();
										$erro.= "\n\n";
										fwrite($elog, $erro);
									}
								}
							}
						}

					}
					if (pg_last_error()) {
						array_push($original, 'erro ao fazer update/insert');
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
				$cmds = "cd $log_dir && cp importa-peca-$now.log peca$data_arq_enviar.txt && zip -r peca$data_arq_enviar.zip peca$data_arq_enviar.txt 1>/dev/null";
				system("$cmds", $retorno);

				$joga_ftp = "cd $log_dir && cp peca$data_arq_enviar.txt $ftp/$fabrica_nome-pecas$data_arq_enviar.ret";
				system("$joga_ftp");

				if ($retorno == 0) {

					require_once dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

					$assunto = ucfirst($fabrica_nome) . utf8_decode(': Importação de peças ') . date('d/m/Y');

					$mail = new PHPMailer();
					$mail->IsHTML(true);
					$mail->From = 'helpdesk@telecontrol.com.br';
					$mail->FromName = 'Telecontrol';

					if (ENV != 'producao') {
						$mail->AddAddress('helpdesk@telecontrol.com.br');
						$mail->AddAddress('amaral@hitachi-koki.com.br');
					} else {
						$mail->AddAddress(DEV_EMAIL);
					}

					$mail->Subject = $assunto;
					$mail->Body = "Segue anexo arquivo de peças importado na rotina...<br/><br/>";
					$mail->AddAttachment($log_dir . '/peca' . $data_arq_enviar . '.zip', 'peca' . $data_arq_enviar . '.zip');

					if (!$mail->Send()) {
						echo 'Erro ao enviar email: ' , $mail->ErrorInfo;
					} else {
						unlink($log_dir . '/peca' . $data_arq_enviar . '.txt');
						unlink($log_dir . '/peca' . $data_arq_enviar . '.zip');
					}

				} else {
					echo 'Erro ao compactar arquivo de log: ' , $retorno;
				}
			}

			if (filesize($err_log) > 0) {
				system("cd $log_dir && zip -r importa-peca-err-$now.zip importa-peca-err-$now.log 1>/dev/null", $retorno);

				if ($retorno == 0) {

					require_once dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

					$assunto = ucfirst($fabrica_nome) . utf8_decode(': Erros na importação de peças ') . date('d/m/Y');

					$mail = new PHPMailer();
					$mail->IsHTML(true);
					$mail->From = 'helpdesk@telecontrol.com.br';
					$mail->FromName = 'Telecontrol';

					if (ENV != 'producao') {
						$mail->AddAddress('helpdesk@telecontrol.com.br');
						$mail->AddAddress('amaral@hitachi-koki.com.br');
					} else {
						$mail->AddAddress(DEV_EMAIL);
					}

					$mail->Subject = $assunto;
					$mail->Body = "Segue anexo log de erro na importação de peças...<br/><br/>";
					$mail->AddAttachment($log_dir . '/importa-peca-err-' . $now . '.zip', 'importa-peca-err-' . $now . '.zip');

					if (!$mail->Send()) {
						echo 'Erro ao enviar email: ' , $mail->ErrorInfo;
					} else {
						unlink($log_dir . '/importa-peca-err-' . $now . '.zip');
					}

				} else {
					echo 'Erro ao compactar arquivo de log de erros: ' , $retorno;
				}
			}

			$data_arq_process = date('Ymd');
			system("mv $arquivo /tmp/$fabrica_nome/telecontrol-peca-$data_arq_process.txt");

		}
	}

} catch (Exception $e) {
	echo $e->getMessage();
}

