<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="cadastros";
include 'autentica_admin.php';

if (strlen($_GET["finaliza"]) > 0) {
	$finaliza = trim($_GET["finaliza"]);
}

### APOS CLICAR NO BOTÃO GRAVAR
$btn_finalizar = $_POST['btn_finalizar'];
if ($btn_finalizar == '1' ) {

	$arquivo = "";

	### CAMPOS DO FORMULÁRIO
	$assunto                 = trim($_POST["assunto"]);
	$mensagem                = trim($_POST["mensagem"]);
	$remetente               = strtolower(trim($_POST["remetente"]));
	$destinatarios           = trim($_POST["destinatarios"]);
	$destinatario_especifico = trim($_POST["destinatario_especifico"]);
	$comunicado              = trim($_POST["comunicado"]);
	$atendimento_1           = trim($_POST["atendimento_1"]);
	$atendimento_2           = trim($_POST["atendimento_2"]);
	$atendimento_3           = trim($_POST["atendimento_3"]);
	$pede_peca_garantia      = trim($_POST["pede_peca_garantia"]);
	$pede_peca_faturada      = trim($_POST["pede_peca_faturada"]);
	$suframa                 = trim($_POST["suframa"]);

	### GERAÇÃO DE CAMPOS AUXILIARES
	if (strlen($assunto) == 0) {
		$erro = "Favor informar o Assunto.";
	}else{
		$aux_assunto = "'". $assunto ."'";
	}

	if (strlen($remetente) == 0) {
		$erro = "Favor informar o eMail do remetente.";
	}else{
		$aux_remetente = "'". $remetente ."'";
	}

	if (strlen($destinatario_especifico) == 0) {
		$aux_destinatario_especifico = "null";
	}else{
		$aux_destinatario_especifico  = trim ($destinatario_especifico);
		$aux_destinatario_especifico  = str_replace("\r\n\r\n", ";", $aux_destinatario_especifico);
		$aux_destinatario_especifico  = str_replace("\r\n", ";", $aux_destinatario_especifico);
		$negados                      = explode (";" , $aux_destinatario_especifico);
		$qtde_produto                 = array_count_values ($negados);
		$negados_1                    = array_unique($negados);

		for ($k=0; $k < count($negados); $k++) {
			$negados [$k] = trim ($negados_1 [$k]);
			$posto = $negados [$k];

			if (strlen($posto) > 0) {
				if ($k == 0) {
					$codigo      .= "(";
					$codigo_mail .= "(";
				}
				$codigo      .= "\'". $posto ."\'";
				$codigo_mail .= "'". $posto ."'";

				$k1 = $k + 1;

				if (strlen($negados_1 [$k+1]) > 0) {
					$codigo      .= ",";
					$codigo_mail .= ",";
				}else{
					if ($k1 < count($negados)){
						$codigo      .= ",";
						$codigo_mail .= ",";
					}else{
						$codigo      .= ")";
						$codigo_mail .= ")";
					}
				}
			}
		}
		$aux_destinatario_especifico      = "'". $codigo ."'";
		$aux_destinatario_especifico_mail = $codigo_mail;
	}

	for ($z = 0 ; $z < $destinatarios ; $z++){
		$destinatario = $_POST["destinatario_". $z];
		if (strlen($destinatario) > 0) $checa .= "sim";
		else                           $checa .= "nao";
	}

	if ($aux_destinatario_especifico == "null") {
		$pos = substr_count($checa,"sim");

		if ($pos == 0) {
			$erro = "É necessário informar o destinatário específico ou por tipo";
		}
	}

	if (strlen($copia_email) == 0) {
		$aux_copia_email = "null";
	}else{
		$aux_copia_email = "'". $copia_email ."'";
	}

	if ($pede_peca_faturada == "") $aux_pede_peca_faturada = "null";
	else                           $aux_pede_peca_faturada = "'".$pede_peca_faturada."'";

	if ($pede_peca_garantia == "") $aux_pede_peca_garantia = "null";
	else                           $aux_pede_peca_garantia = "'".$pede_peca_garantia."'";
	$aux_atendimento        = "'";
	
	if(strlen($atendimento_1) > 0) $aux_atendimento.= $atendimento_1;
	
	if(strlen($atendimento_2) > 0 ) {
		if (strlen($atendimento_1) > 0) $aux_atendimento.= ",";
		$aux_atendimento.= $atendimento_2;
	}
	
	if(strlen($atendimento_3) > 0) {
		if (strlen($atendimento_1) > 0) {
			$aux_atendimento.= ",";
		}else{
			if (strlen($atendimento_2) > 0) $aux_atendimento.= ",";
		}
		$aux_atendimento.= $atendimento_3;
	}
	
	$aux_atendimento       .= "'";

	if ($suframa == "") $aux_suframa = "null";
	else                $aux_suframa = "'".$suframa."'";
	
	if (strlen($erro) == 0) {
		if (strlen($mensagem) == 0) {
			$erro = "Favor informar a mensagem do comunicado";
		}else{
			$aux_mensagem = "'". $mensagem ."'";
		}
	}

	$resx = pg_exec ($con,"BEGIN TRANSACTION");

	if (strlen($erro) == 0) {
		if ($aux_destinatario_especifico <> "null" and $pos == 0) {
			$aux_destinatario = "null";

			## INCLUSÃO DE LANÇAMENTO
/*			$sql = "INSERT INTO tbl_comunicado (
						descricao                   ,
						remetente_email             ,
						destinatario_especifico     ,
						destinatario                ,
						linha_atendimento           ,
						pede_peca_garantia          ,
						pede_peca_faturada          ,
						suframa                     ,
						mensagem
					) VALUES (
						$aux_assunto                ,
						$aux_remetente              ,
						$aux_destinatario_especifico,
						$aux_destinatario           ,
						$aux_atendimento            ,
						$aux_pede_peca_garantia     ,
						$aux_pede_peca_faturada     ,
						$aux_suframa                ,
						$aux_mensagem
					);"; */ // Este SQL está com a linha
			$sql = "INSERT INTO tbl_comunicado (
						fabrica                     ,
						descricao                   ,
						remetente_email             ,
						destinatario_especifico     ,
						destinatario                ,
						pedido_em_garantia          ,
						pedido_faturado             ,
						suframa                     ,
						mensagem
					) VALUES (
						$login_fabrica              ,
						$aux_assunto                ,
						$aux_remetente              ,
						$aux_destinatario_especifico,
						$aux_destinatario           ,
						$aux_pede_peca_garantia     ,
						$aux_pede_peca_faturada     ,
						$aux_suframa                ,
						$aux_mensagem
					);";
			$res = @pg_exec ($con,$sql);

			if (strlen ( pg_errormessage ($con) ) > 0) {
				$erro .= pg_errormessage ($con) ;
			}

			if (strlen($erro) == 0) {
				if (strlen($comunicado) == 0) {
					## PEGA SEQUÊNCIA DA TABELA DE COMUNICADO
					$res1       = pg_exec ($con,"SELECT currval ('seq_comunicado')");
					$comunicado = pg_result ($res1,0,0);
					
					if (strlen ( pg_errormessage ($con) ) > 0) {
						$erro .= pg_errormessage ($con) ;
					}
				}
			}

			if (strlen($erro) == 0) {
				///////////////////////////////////////////////////
				// Rotina que faz o upload do arquivo
				///////////////////////////////////////////////////
				// Prepara a variável do arquivo 
				$arquivo = (strlen($_FILES["anexo"]["name"]) > 0) ? $_FILES["anexo"] : '';

				// Tamanho máximo do arquivo (em bytes) 
				$config["tamanho"] = 2000000;

				// Formulário postado... executa as ações 
				if (strlen($arquivo) > 0){
					// Verifica o mime-type do arquivo
					if (!preg_match("/\/(pdf|msword|vnd.ms-excel|pjpeg|jpeg|png|gif|bmp)$/", $arquivo["type"])){
						$erro = "Arquivo em formato inválido! " . $arquivo["type"]; 
					} else {
						// Verifica tamanho do arquivo 
						if ($arquivo["size"] > $config["tamanho"]) 
							$erro = "Arquivo em tamanho muito grande! Deve ser de no máximo 2 megabytes. Envie outro arquivo"; 
					}

					if (strlen($erro) == 0) {
						// Pega extensão do arquivo 
						preg_match("/\.(pdf|doc|xls|gif|bmp|png|jpg|jpeg){1}$/i", $arquivo["name"], $ext); 
						
						// Gera um nome único para a imagem 
						$nome_anexo = $comunicado .".". $ext[1];
						
						// Caminho de onde a imagem ficará + extensao
						$imagem_dir = "../comunicados_blackedecker/".$nome_anexo;
						
						// Exclui arquivo anterior
						//@unlink($imagem_dir);
						
						// Faz o upload da imagem 
						if (strlen($erro) == 0) {
							move_uploaded_file($arquivo["tmp_name"], $imagem_dir);

/*							$sql = "UPDATE tbl_comunicado SET anexo = 't'
									WHERE  tbl_comunicado.comunicado = $comunicado;";
							$res = @pg_exec ($con,$sql);

							if (strlen ( pg_errormessage ($con) ) > 0) {
								$erro .= pg_errormessage ($con) ;
							}*/
						}
					}
				}
			}
			
			if (strlen($erro) == 0) {
				$sql = "SELECT  tbl_posto.posto,
								tbl_posto.nome ,
								tbl_posto.email
						FROM    tbl_posto
						JOIN    tbl_posto_fabrica ON tbl_posto.posto          = tbl_posto_fabrica.posto
												 AND tbl_posto_fabrica.fabrica = $login_fabrica
						WHERE   (tbl_posto.email notnull AND length(tbl_posto.email) > 0)
						AND     tbl_posto_fabrica.codigo_posto in $aux_destinatario_especifico_mail
						AND     tbl_posto_fabrica.credenciamento = 'CREDENCIADO' ";

				if (trim($pede_peca_garantia) <> "") {
					$sql .= "AND tbl_posto_fabrica.pedido_em_garantia = '$pede_peca_garantia' ";
				}
				
				if (trim($pede_peca_faturada) <> "") {
					$sql .= "AND tbl_posto_fabrica.pedido_faturado = '$pede_peca_faturada' ";
				}

				if (trim($suframa) <> "") {
					$sql .= "AND tbl_posto.suframa = '$suframa' ";
				}

				if(strlen($atendimento_1) > 0 AND strlen($atendimento_2) == 0 AND strlen($atendimento_3) == 0) {
					$sql .= "AND tbl_posto.lp = 'A' AND tbl_posto.dw = '' ";
				}

				if(strlen($atendimento_1) > 0 AND strlen($atendimento_2) > 0 AND strlen($atendimento_3) == 0) {
					$sql .= "AND (tbl_posto.lp = 'A' AND tbl_posto.dw = '') OR (tbl_posto.lp = 'A' AND tbl_posto.dw = 'S') ";
				}

				if(strlen($atendimento_1) > 0 AND strlen($atendimento_2) == 0 AND strlen($atendimento_3) > 0) {
					$sql .= "AND (tbl_posto.lp = 'A' AND tbl_posto.dw = '') OR (tbl_posto.lp = 'F' AND tbl_posto.dw = 'S') ";
				}

				if(strlen($atendimento_1) > 0 AND strlen($atendimento_2) > 0 AND strlen($atendimento_3) > 0) {
					$sql .= "AND (tbl_posto.lp = 'A' AND tbl_posto.dw = '') OR (tbl_posto.lp = 'A' AND tbl_posto.dw = 'S') OR (tbl_posto.lp = 'F' AND tbl_posto.dw = 'S') ";
				}

				if(strlen($atendimento_1) == 0 AND strlen($atendimento_2) > 0 AND strlen($atendimento_3) == 0) {
					$sql .= "AND tbl_posto.lp = 'A' AND tbl_posto.dw = 'S' ";
				}
				
				if(strlen($atendimento_1) == 0 AND strlen($atendimento_2) > 0 AND strlen($atendimento_3) > 0) {
					$sql .= "AND (tbl_posto.lp = 'A' AND tbl_posto.dw = 'S') OR (tbl_posto.lp = 'F' AND tbl_posto.dw = 'S') ";
				}
				
				if(strlen($atendimento_1) == 0 AND strlen($atendimento_2) == 0 AND strlen($atendimento_3) > 0) {
					$sql .= "AND tbl_posto.lp = 'F' AND tbl_posto.dw = 'S' ";
				}
				$resx = @pg_exec ($con,$sql);

				for ( $i = 0 ; $i < @pg_numrows ($resx) ; $i++ ) {
					$posto       = trim(pg_result($resx,$i,posto));
					$posto_nome  = trim(pg_result($resx,$i,nome));
					$posto_email = trim(strtolower(pg_result($resx,$i,email)));
					
					$corpo_email  = "<table width='600' border='0' cellpadding='2' cellspacing='2' align='center'>\n";
					$corpo_email .= "<tr>\n";
					
					$corpo_email .= "<td width='100%' align='center' bgcolor='#C2DCDC'>\n";
					$corpo_email .= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>\n";
					$corpo_email .= "<b>COMUNICADO BLACK & DECKER</b>\n";
					$corpo_email .= "</font>\n";
					$corpo_email .= "</td>\n";
					
					$corpo_email .= "</tr>\n";
					$corpo_email .= "<tr>\n";
					
					$corpo_email .= "<td width='100%' align='left' bgcolor='#FFFFFF'>\n";
					$corpo_email .= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>\n";
					$corpo_email .= "<b>Prezado(a) $posto_nome,</b>\n";
					$corpo_email .= "</font>\n";
					$corpo_email .= "</td>\n";
					
					$corpo_email .= "</tr>\n";
					$corpo_email .= "<tr>\n";
					
					$corpo_email .= "<td width='100%' align='left' bgcolor='#FFFFFF'>\n";
					$corpo_email .= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>\n";
					$corpo_email .= nl2br($mensagem) ."\n";
					$corpo_email .= "</font>\n";
					$corpo_email .= "</td>\n";
					
					$corpo_email .= "</tr>\n";
					$corpo_email .= "</table>\n";
					
					if (strlen($corpo_email) > 0) {
						if ($nome_foto != "none") {
							$attach = "/var/www/blackedecker/www/comunicados/$nome_foto";
							$file = fopen($attach, "r");
							$contents = fread($file, $anexo_size);
							$encoded_attach = chunk_split(base64_encode($contents));
							fclose($file);
						}
						
						$subject    = $assunto;
						$from_nome  = "Black & Decker";
						$from_email = "$remetente";
						
						$to_nome  = "$posto_nome";
						$to_email = "$posto_email";
						
						$cabecalho  = "From: $from_nome < $from_email >\n";
						$cabecalho .= "To: $to_nome < $to_email >\n";
						$cabecalho .= "Return-Path: < $from_email >\n";
						$cabecalho .= "MIME-version: 1.0\n"; 
						
						if ($nome_foto != "none") {
							$cabecalho .= "Content-type: multipart/mixed; "; 
							$cabecalho .= "boundary=\"Message-Boundary\"\n"; 
							$cabecalho .= "Content-transfer-encoding: 7BIT\n"; 
							$cabecalho .= "X-attachments: $anexo_name"; 
							
							$body_top = "--Message-Boundary\n"; 
							$body_top .= "Content-type: text/html; charset=iso-8859-1\n"; 
							$body_top .= "Content-transfer-encoding: 7BIT\n"; 
							$body_top .= "Content-description: Mail message body\n\n"; 
							
							$corpo_email = $body_top . $corpo_email;
							
							$corpo_email .= "\n\n--Message-Boundary\n"; 
							$corpo_email .= "Content-type: $anexo_type; name=\"$anexo_name\"\n"; 
							$corpo_email .= "Content-Transfer-Encoding: BASE64\n"; 
							$corpo_email .= "Content-disposition: attachment; filename=\"$anexo_name\"\n\n"; 
							$corpo_email .= "$encoded_attach\n"; 
							$corpo_email .= "--Message-Boundary--\n"; 
						}else{
							$cabecalho .= "Content-type: text/html; charset=iso-8859-1\n"; 
						}
						
						mail ("" , stripslashes(utf8_encode($subject)), utf8_encode("$corpo_email") , "$cabecalho");
					}
				}
				
				$corpo_email  = "<table width='600' border='0' cellpadding='2' cellspacing='2' align='center'>\n";
				$corpo_email .= "<tr>\n";
				
				$corpo_email .= "<td width='100%' align='center' bgcolor='#C2DCDC'>\n";
				$corpo_email .= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>\n";
				$corpo_email .= "<b>COMUNICADO BLACK & DECKER</b>\n";
				$corpo_email .= "</font>\n";
				$corpo_email .= "</td>\n";
				
				$corpo_email .= "</tr>\n";
				$corpo_email .= "<tr>\n";
				
				$corpo_email .= "<td width='100%' align='left' bgcolor='#FFFFFF'>\n";
				$corpo_email .= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>\n";
				$corpo_email .= "<b>CÓPIA DE COMUNICADO</b>\n";
				$corpo_email .= "</font>\n";
				$corpo_email .= "</td>\n";
				
				$corpo_email .= "</tr>\n";
				$corpo_email .= "<tr>\n";
				
				$corpo_email .= "<td width='100%' align='left' bgcolor='#FFFFFF'>\n";
				$corpo_email .= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>\n";
				$corpo_email .= nl2br($mensagem) ."\n";
				$corpo_email .= "</font>\n";
				$corpo_email .= "</td>\n";
				
				$corpo_email .= "</tr>\n";
				$corpo_email .= "</table>\n";
				
				if (strlen($corpo_email) > 0 and 1 == 2) {
					if ($nome_foto != "none") {
						$attach = "/var/www/blackedecker/www/comunicados/$nome_foto";
						$file = fopen($attach, "r");
						$contents = fread($file, $anexo_size);
						$encoded_attach = chunk_split(base64_encode($contents));
						fclose($file);
					}
					
					$subject    = $assunto;
					
					$from_nome  = "Black & Decker";
					$from_email = "$remetente";
					
					$to_nome  = "Cópia Comunicado";
					$to_email = "MiPereira@blackedecker.com.br";
					
					$cabecalho  = "From: $from_nome < $from_email >\n";
					$cabecalho .= "To: $to_nome < $to_email >\n";
					$cabecalho .= "Return-Path: < $from_email >\n";
					$cabecalho .= "MIME-version: 1.0\n"; 
					
					if ($nome_foto != "none") {
						$cabecalho .= "Content-type: multipart/mixed; "; 
						$cabecalho .= "boundary=\"Message-Boundary\"\n"; 
						$cabecalho .= "Content-transfer-encoding: 7BIT\n"; 
						$cabecalho .= "X-attachments: $anexo_name"; 
						
						$body_top  = "--Message-Boundary\n"; 
						$body_top .= "Content-type: text/html; charset=iso-8859-1\n"; 
						$body_top .= "Content-transfer-encoding: 7BIT\n"; 
						$body_top .= "Content-description: Mail message body\n\n"; 
						
						$corpo_email = $body_top . $corpo_email;
						
						$corpo_email .= "\n\n--Message-Boundary\n"; 
						$corpo_email .= "Content-type: $anexo_type; name=\"$anexo_name\"\n"; 
						$corpo_email .= "Content-Transfer-Encoding: BASE64\n"; 
						$corpo_email .= "Content-disposition: attachment; filename=\"$anexo_name\"\n\n"; 
						$corpo_email .= "$encoded_attach\n"; 
						$corpo_email .= "--Message-Boundary--\n"; 
					}else{
						$cabecalho .= "Content-type: text/html; charset=iso-8859-1\n"; 
					}
					
					mail ("" , stripslashes(utf8_encode($subject)), utf8_encode("$corpo_email") , "$cabecalho");
				}
				
				$from_nome  = "";
				$from_email = "";
				$to_nome    = "";
				$to_email   = "";
				$cc_nome    = "";
				$cc_email   = "";
				$subject    = "";
				$cabecalho  = "";
			}
		}else{
			for ($z = 0 ; $z < $destinatarios ; $z++){
				$aux_destinatario = $_POST["destinatario_". $z];
				
				if (strlen($aux_destinatario) > 0) {
					## INCLUSÃO DE LANÇAMENTO
					$sql = "INSERT INTO tbl_comunicado (
								descricao              ,
								remetente_email        ,
								destinatario_especifico,
								destinatario           ,
								pedido_em_garantia     ,
								pedido_faturado        ,
								suframa                ,
								mensagem
							) VALUES (
								$aux_assunto                ,
								$aux_remetente              ,
								$aux_destinatario_especifico,
								$aux_destinatario           ,
								$aux_pede_peca_garantia     ,
								$aux_pede_peca_faturada     ,
								$aux_suframa                ,
								$aux_mensagem
							);";
					$res = @pg_exec ($con,$sql);
					if (strlen ( pg_errormessage ($con) ) > 0) $erro .= pg_errormessage ($con) ;

					if (strlen($erro) == 0) {
						## PEGA SEQUÊNCIA DA TABELA DE COMUNICADO
						$res1      = pg_exec ($con,"SELECT currval ('tbl_comunicado_seq')");
						$comunicado = pg_result ($res1,0,0);
						
						if (strlen ( pg_errormessage ($con) ) > 0) $erro .= pg_errormessage ($con) ;
					}
					
					if (strlen($erro) == 0) {
						///////////////////////////////////////////////////
						// Rotina que faz o upload do arquivo
						///////////////////////////////////////////////////
						// Prepara a variável do arquivo 
						if (strlen($arquivo) == 0 ){
							$arquivo = (strlen($_FILES["anexo"]["name"]) > 0) ? $_FILES["anexo"] : '';
						}
						
						// Tamanho máximo do arquivo (em bytes) 
						$config["tamanho"] = 2000000;
						
						// Formulário postado... executa as ações 
						if (strlen($arquivo) > 0){
							// Verifica o mime-type do arquivo
							if (!preg_match("/\/(pdf|msword|vnd.ms-excel|pjpeg|jpeg|png|gif|bmp)$/", $arquivo["type"])){
								$erro = "Arquivo em formato inválido!"; 
							} else {
								// Verifica tamanho do arquivo 
								if ($arquivo["size"] > $config["tamanho"]) 
									$erro = "Arquivo em tamanho muito grande! Deve ser de no máximo 2 megabytes. Envie outro arquivo"; 
							}
							
							if (strlen($erro) == 0) {
								// Pega extensão do arquivo 
								preg_match("/\.(pdf|doc|xls|gif|bmp|png|jpg|jpeg){1}$/i", $arquivo["name"], $ext); 
								
								// Gera um nome único para a imagem 
								$nome_anexo = $comunicado .".". $ext[1];
								$ext        = $ext[1];
								
								// Caminho de onde a imagem ficará + extensao
								$imagem_dir = "../comunicados_blackedecker/".$nome_anexo;
								
								// Exclui arquivo anterior
								//@unlink($imagem_dir);
								
								// Faz o upload da imagem 
								if (strlen($erro) == 0) {
									move_uploaded_file($arquivo["tmp_name"], $imagem_dir);

/*									$sql = "UPDATE tbl_comunicado SET anexo = 't'
											WHERE  tbl_comunicado.comunicado = $comunicado;";
									$res = @pg_exec ($con,$sql);

									if (strlen ( pg_errormessage ($con) ) > 0) {
										$erro .= pg_errormessage ($con) ;
									}*/
								}
							}
						}
					}
					
					$arquivox = "../comunicados_blackedecker/$comunicado.$ext";
					
					if (file_exists($arquivox)){
					
					}else{
						$x = system("cd ../comunicados_blackedecker; cp $comunicado_anterior.$ext $comunicado.$ext");
					}
					$comunicado_anterior = $comunicado;
					
					if (strlen($erro) == 0) {
						$sql = "SELECT  tbl_posto.posto,
										tbl_posto.nome ,
										tbl_posto.email
								FROM    tbl_posto
								WHERE   (tbl_posto.email notnull AND length(tbl_posto.email) > 0)
								AND     tbl_posto.ativo = 't' ";
							
						if (strlen($aux_destinatario) > 0) {
							$sql .= "AND tbl_posto.tipo_posto = $aux_destinatario ";
						}
						
						if (trim($pede_peca_garantia) <> "todos") {
							$sql .= "AND tbl_posto.pede_peca_garantia IS $pede_peca_garantia ";
						}
						
						if (trim($pede_peca_faturada) <> "todos") {
							$sql .= "AND tbl_posto.pede_peca_faturada IS $pede_peca_faturada ";
						}
						
						if (trim($suframa) <> "todos") {
							$sql .= "AND tbl_posto.suframa IS $suframa ";
						}
						
						if(strlen($atendimento_1) > 0 AND strlen($atendimento_2) == 0 AND strlen($atendimento_3) == 0) {
							$sql .= "AND tbl_posto.lp = 'A' AND tbl_posto.dw = '' ";
						}
						
						if(strlen($atendimento_1) > 0 AND strlen($atendimento_2) > 0 AND strlen($atendimento_3) == 0) {
							$sql .= "AND (tbl_posto.lp = 'A' AND tbl_posto.dw = '') OR (tbl_posto.lp = 'A' AND tbl_posto.dw = 'S') ";
						}
						
						if(strlen($atendimento_1) > 0 AND strlen($atendimento_2) == 0 AND strlen($atendimento_3) > 0) {
							$sql .= "AND (tbl_posto.lp = 'A' AND tbl_posto.dw = '') OR (tbl_posto.lp = 'F' AND tbl_posto.dw = 'S') ";
						}
						
						if(strlen($atendimento_1) > 0 AND strlen($atendimento_2) > 0 AND strlen($atendimento_3) > 0) {
							$sql .= "AND (tbl_posto.lp = 'A' AND tbl_posto.dw = '') OR (tbl_posto.lp = 'A' AND tbl_posto.dw = 'S') OR (tbl_posto.lp = 'F' AND tbl_posto.dw = 'S') ";
						}
						
						if(strlen($atendimento_1) == 0 AND strlen($atendimento_2) > 0 AND strlen($atendimento_3) == 0) {
							$sql .= "AND tbl_posto.lp = 'A' AND tbl_posto.dw = 'S' ";
						}
						
						if(strlen($atendimento_1) == 0 AND strlen($atendimento_2) > 0 AND strlen($atendimento_3) > 0) {
							$sql .= "AND (tbl_posto.lp = 'A' AND tbl_posto.dw = 'S') OR (tbl_posto.lp = 'F' AND tbl_posto.dw = 'S') ";
						}
						
						if(strlen($atendimento_1) == 0 AND strlen($atendimento_2) == 0 AND strlen($atendimento_3) > 0) {
							$sql .= "AND tbl_posto.lp = 'F' AND tbl_posto.dw = 'S' ";
						}
						$resx = pg_exec ($con,$sql);

						for ( $i = 0 ; $i < pg_numrows ($resx) ; $i++ ) {
							$posto       = trim(pg_result($resx,$i,posto));
							$posto_nome  = trim(pg_result($resx,$i,nome));
							$posto_email = trim(strtolower(pg_result($resx,$i,email)));
							
							$corpo_email  = "<table width='600' border='0' cellpadding='2' cellspacing='2' align='center'>\n";
							$corpo_email .= "<tr>\n";
							
							$corpo_email .= "<td width='100%' align='center' bgcolor='#C2DCDC'>\n";
							$corpo_email .= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>\n";
							$corpo_email .= "<b>COMUNICADO BLACK & DECKER</b>\n";
							$corpo_email .= "</font>\n";
							$corpo_email .= "</td>\n";
							
							$corpo_email .= "</tr>\n";
							$corpo_email .= "<tr>\n";
							
							$corpo_email .= "<td width='100%' align='left' bgcolor='#FFFFFF'>\n";
							$corpo_email .= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>\n";
							$corpo_email .= "<b>Prezado(a) $posto_nome,</b>\n";
							$corpo_email .= "</font>\n";
							$corpo_email .= "</td>\n";
							
							$corpo_email .= "</tr>\n";
							$corpo_email .= "<tr>\n";
							
							$corpo_email .= "<td width='100%' align='left' bgcolor='#FFFFFF'>\n";
							$corpo_email .= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>\n";
							$corpo_email .= nl2br($mensagem) ."\n";
							$corpo_email .= "</font>\n";
							$corpo_email .= "</td>\n";
							
							$corpo_email .= "</tr>\n";
							$corpo_email .= "</table>\n";
							
							if (strlen($corpo_email) > 0) {
								if ($nome_foto != "none") {
									$attach = "/var/www/blackedecker/www/comunicados/$nome_foto";
									$file = fopen($attach, "r");
									$contents = fread($file, $anexo_size);
									$encoded_attach = chunk_split(base64_encode($contents));
									fclose($file);
								}
								
								$subject    = $assunto;
								$from_nome  = "Black & Decker";
								$from_email = "$remetente";
								//$from_email = "silvania_silva@blackedecker.com.br";
								
								$to_nome  = "$posto_nome";
								$to_email = "$posto_email";
								
								$cabecalho  = "From: $from_nome < $from_email >\n";
								$cabecalho .= "To: $to_nome < $to_email >\n";
								$cabecalho .= "Return-Path: < $from_email >\n";
								$cabecalho .= "MIME-version: 1.0\n"; 
								
								if ($nome_foto != "none") {
									$cabecalho .= "Content-type: multipart/mixed; "; 
									$cabecalho .= "boundary=\"Message-Boundary\"\n"; 
									$cabecalho .= "Content-transfer-encoding: 7BIT\n"; 
									$cabecalho .= "X-attachments: $anexo_name"; 
									
									$body_top = "--Message-Boundary\n"; 
									$body_top .= "Content-type: text/html; charset=iso-8859-1\n"; 
									$body_top .= "Content-transfer-encoding: 7BIT\n"; 
									$body_top .= "Content-description: Mail message body\n\n"; 
									
									$corpo_email = $body_top . $corpo_email;
									
									$corpo_email .= "\n\n--Message-Boundary\n"; 
									$corpo_email .= "Content-type: $anexo_type; name=\"$anexo_name\"\n"; 
									$corpo_email .= "Content-Transfer-Encoding: BASE64\n"; 
									$corpo_email .= "Content-disposition: attachment; filename=\"$anexo_name\"\n\n"; 
									$corpo_email .= "$encoded_attach\n"; 
									$corpo_email .= "--Message-Boundary--\n"; 
								}else{
									$cabecalho .= "Content-type: text/html; charset=iso-8859-1\n"; 
								}
								
								mail ("" , stripslashes(utf8_encode($subject)), utf8_encode("$corpo_email") , "$cabecalho");
							}
						}
						
						$corpo_email  = "<table width='600' border='0' cellpadding='2' cellspacing='2' align='center'>\n";
						$corpo_email .= "<tr>\n";
						
						$corpo_email .= "<td width='100%' align='center' bgcolor='#C2DCDC'>\n";
						$corpo_email .= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>\n";
						$corpo_email .= "<b>COMUNICADO BLACK & DECKER</b>\n";
						$corpo_email .= "</font>\n";
						$corpo_email .= "</td>\n";
						
						$corpo_email .= "</tr>\n";
						$corpo_email .= "<tr>\n";
						
						$corpo_email .= "<td width='100%' align='left' bgcolor='#FFFFFF'>\n";
						$corpo_email .= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>\n";
						$corpo_email .= "<b>CÓPIA DE COMUNICADO</b>\n";
						$corpo_email .= "</font>\n";
						$corpo_email .= "</td>\n";
						
						$corpo_email .= "</tr>\n";
						$corpo_email .= "<tr>\n";
						
						$corpo_email .= "<td width='100%' align='left' bgcolor='#FFFFFF'>\n";
						$corpo_email .= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>\n";
						$corpo_email .= nl2br($mensagem) ."\n";
						$corpo_email .= "</font>\n";
						$corpo_email .= "</td>\n";
						
						$corpo_email .= "</tr>\n";
						$corpo_email .= "</table>\n";
						
						if (strlen($corpo_email) > 0 and 1 == 2) {
							if ($nome_foto != "none") {
								$attach = "/var/www/blackedecker/www/comunicados/$nome_foto";
								$file = fopen($attach, "r");
								$contents = fread($file, $anexo_size);
								$encoded_attach = chunk_split(base64_encode($contents));
								fclose($file);
							}
							
							$subject    = $assunto;
							
							$from_nome  = "Black & Decker";
							$from_email = "$remetente";
							//$from_email = "silvania_silva@blackedecker.com.br";
							
							$to_nome  = "Cópia Comunicado";
							$to_email = "MiPereira@blackedecker";
							
							$cabecalho  = "From: $from_nome < $from_email >\n";
							$cabecalho .= "To: $to_nome < $to_email >\n";
							$cabecalho .= "Return-Path: < $from_email >\n";
							$cabecalho .= "MIME-version: 1.0\n"; 
							
							if ($nome_foto != "none") {
								$cabecalho .= "Content-type: multipart/mixed; "; 
								$cabecalho .= "boundary=\"Message-Boundary\"\n"; 
								$cabecalho .= "Content-transfer-encoding: 7BIT\n"; 
								$cabecalho .= "X-attachments: $anexo_name"; 
								
								$body_top  = "--Message-Boundary\n"; 
								$body_top .= "Content-type: text/html; charset=iso-8859-1\n"; 
								$body_top .= "Content-transfer-encoding: 7BIT\n"; 
								$body_top .= "Content-description: Mail message body\n\n"; 
								
								$corpo_email = $body_top . $corpo_email;
								
								$corpo_email .= "\n\n--Message-Boundary\n"; 
								$corpo_email .= "Content-type: $anexo_type; name=\"$anexo_name\"\n"; 
								$corpo_email .= "Content-Transfer-Encoding: BASE64\n"; 
								$corpo_email .= "Content-disposition: attachment; filename=\"$anexo_name\"\n\n"; 
								$corpo_email .= "$encoded_attach\n"; 
								$corpo_email .= "--Message-Boundary--\n"; 
							}else{
								$cabecalho .= "Content-type: text/html; charset=iso-8859-1\n"; 
							}
							
						
							mail ("" , stripslashes(utf8_encode($subject)), utf8_encode("$corpo_email") , "$cabecalho");
						}
						
						$from_nome  = "";
						$from_email = "";
						$to_nome    = "";
						$to_email   = "";
						$cc_nome    = "";
						$cc_email   = "";
						$subject    = "";
						$cabecalho  = "";
					}
				}
			}
		}

		////////////////////////////////////////////////
		// envia email para pessoal da B&D selecionado
		////////////////////////////////////////////////

		$total_email = 14;
		for ( $i=0 ; $i < $total_email; $i++ ) {
			$email       = trim($_POST['email_interno_'.$i]);
			if ($email == "MiPereira@blackedecker.com.br")        $nome_interno = "Miguel Pereira";
			if ($email == "silvania_silva@blackedecker.com.br")   $nome_interno = "Silvania Alves";
			if ($email == "fascina@blackedecker.com.br")          $nome_interno = "Alexandre Fascina";
			if ($email == "rogerio_berto@blackedecker.com.br")    $nome_interno = "Rogério Berto";
			if ($email == "ureis@blackedecker.com.br")            $nome_interno = "Ulisses Reis";
			if ($email == "llaterza@blackedecker.com.br")         $nome_interno = "Lilian Laterza";
			if ($email == "rfernandes@blackedecker.com.br")       $nome_interno = "Rúbia Lane Fernandes";
			if ($email == "fabiola.oliveira@bdk.com")             $nome_interno = "Fabíola Oliveira";
			if ($email == "cschafer@blackedecker.com.br")         $nome_interno = "Christopher Schafer";
			if ($email == "mclemente@blackedecker.com.br")        $nome_interno = "Michel Clemente";
			if ($email == "jnardo@blackedecker.com.br")           $nome_interno = "Johny Nardo Gonçalves";
			if ($email == "mribeiro@blackedecker.com.br")         $nome_interno = "Marcos Leandro Ribeiro da Silva";
			if ($email == "doliveira@blackedecker.com.br")        $nome_interno = "Diógenes Fred de Oliveira";
			if ($email == "samaral@blackedecker.com.br")          $nome_interno = "Sabrina Amaral";

			if (strlen($email) > 0){
				$corpo_email  = "<table width='600' border='0' cellpadding='2' cellspacing='2' align='center'>\n";
				$corpo_email .= "<tr>\n";
				
				$corpo_email .= "<td width='100%' align='center' bgcolor='#C2DCDC'>\n";
				$corpo_email .= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>\n";
				$corpo_email .= "<b>COMUNICADO BLACK & DECKER</b>\n";
				$corpo_email .= "</font>\n";
				$corpo_email .= "</td>\n";
				
				$corpo_email .= "</tr>\n";
				$corpo_email .= "<tr>\n";
				
				$corpo_email .= "<td width='100%' align='left' bgcolor='#FFFFFF'>\n";
				$corpo_email .= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>\n";
				$corpo_email .= "<b>Prezado(a) $nome_interno,</b>\n";
				$corpo_email .= "</font>\n";
				$corpo_email .= "</td>\n";
				
				$corpo_email .= "</tr>\n";
				$corpo_email .= "<tr>\n";
				
				$corpo_email .= "<td width='100%' align='left' bgcolor='#FFFFFF'>\n";
				$corpo_email .= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>\n";
				$corpo_email .= nl2br($mensagem) ."\n";
				$corpo_email .= "</font>\n";
				$corpo_email .= "</td>\n";
				
				$corpo_email .= "</tr>\n";
				$corpo_email .= "</table>\n";
				
				if (strlen($corpo_email) > 0) {
					if ($nome_foto != "none") {
						$attach = "/var/www/blackedecker/www/comunicados/$nome_foto";
						$file = fopen($attach, "r");
						$contents = fread($file, $anexo_size);
						$encoded_attach = chunk_split(base64_encode($contents));
						fclose($file);
					}
					
					$subject    = $assunto;
					$from_nome  = "Black & Decker";
					$from_email = "$remetente";
					 
					$to_nome  = "$nome_interno";
					$to_email = "$email";
					
					$cabecalho  = "From: $from_nome < $from_email >\n";
					$cabecalho .= "To: $to_nome < $to_email >\n";
					$cabecalho .= "Return-Path: < $from_email >\n";
					$cabecalho .= "MIME-version: 1.0\n"; 
					
					if ($nome_foto != "none") {
						$cabecalho .= "Content-type: multipart/mixed; "; 
						$cabecalho .= "boundary=\"Message-Boundary\"\n"; 
						$cabecalho .= "Content-transfer-encoding: 7BIT\n"; 
						$cabecalho .= "X-attachments: $anexo_name"; 
						
						$body_top = "--Message-Boundary\n"; 
						$body_top .= "Content-type: text/html; charset=iso-8859-1\n"; 
						$body_top .= "Content-transfer-encoding: 7BIT\n"; 
						$body_top .= "Content-description: Mail message body\n\n"; 
						
						$corpo_email = $body_top . $corpo_email;
						
						$corpo_email .= "\n\n--Message-Boundary\n"; 
						$corpo_email .= "Content-type: $anexo_type; name=\"$anexo_name\"\n"; 
						$corpo_email .= "Content-Transfer-Encoding: BASE64\n"; 
						$corpo_email .= "Content-disposition: attachment; filename=\"$anexo_name\"\n\n"; 
						$corpo_email .= "$encoded_attach\n"; 
						$corpo_email .= "--Message-Boundary--\n"; 
					}else{
						$cabecalho .= "Content-type: text/html; charset=iso-8859-1\n"; 
					}
					
					mail ("" , stripslashes(utf8_encode($subject)), utf8_encode("$corpo_email") , "$cabecalho");
				}
			}
		}
		////////////////////////////////////////////////

	}

	if (strlen($erro) == 0) {
		$resx = pg_exec ($con,"COMMIT TRANSACTION");
		header ("Location: $PHP_SELF?finaliza=$comunicado");
		exit;
	}else{
		$resx = pg_exec ($con,"ROLLBACK TRANSACTION");
		
		$msg  = "<b>Foi detectado o seguinte erro: </b><br>";
		$msg .= $erro;
	}
}



### CASO EXISTA ERROS RECARREGA FORM
if (strlen($erro) > 0) {
	$comunicado             = trim($_POST["comunicado"]);
	$assunto                = trim($_POST["assunto"]);
	$remetente              = trim($_POST["remetente"]);
	$destinatario_especifco = trim($_POST["destinatario_especifco"]);
	$destinatario           = trim($_POST["destinatario"]);
	$atendimento_1          = trim($_POST["atendimento_1"]);
	$atendimento_2          = trim($_POST["atendimento_2"]);
	$atendimento_3          = trim($_POST["atendimento_3"]);
	$pede_peca_faturada     = trim($_POST["pede_peca_faturada"]);
	$pede_peca_garantia     = trim($_POST["pede_peca_garantia"]);
	$suframa                = trim($_POST["suframa"]);
	$anexo                  = trim($_POST["anexo"]);
	$mensagem               = trim($_POST["mensagem"]);
}


if (strlen($comunicado) == 0 AND strlen($finaliza) == 0) {
	$body_onload = "onload='javascript: document.frmcomunicado.assunto.focus()';";
}

$title = "Comunicados por E-mail";
$layout_menu = "cadastro";
include 'cabecalho.php';
?>

<style type="text/css">
.table_line {
	text-align: right;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #f5f5f5
}
.table_line2 {
	text-align: left;
	background-color: #fcfcfc
}
</style>

<script language="JavaScript">
nextfield = "assunto"; // coloque o nome do primeiro campo do form
netscape = "";
ver = navigator.appVersion; len = ver.length;
for(iln = 0; iln < len; iln++) if (ver.charAt(iln) == "(") break;
netscape = (ver.charAt(iln+1).toUpperCase() != "C");

function keyDown(DnEvents) {
	// ve quando e o netscape ou IE
	k = (netscape) ? DnEvents.which : window.event.keyCode;
	if (k == 13) { // preciona tecla enter
		if (nextfield == 'done') {
			return true; // envia quando termina os campos 
		} else {
			// se existem mais campos vai para o proximo
			eval('document.frmcomunicado.' + nextfield + '.focus()'); 
			return false; 
		}
	}
}

document.onkeydown = keyDown; // work together to analyze keystrokes 
if (netscape) document.captureEvents(Event.KEYDOWN|Event.KEYUP); 

</script>

<? if (strlen($finaliza) == 0) { ?>

<form enctype = "multipart/form-data" name="frmcomunicado" method="post" action="<? echo $PHP_SELF ?>">
<input type='hidden' name='comunicado' value='<? echo $comunicado ?>'>

<? if (strlen($msg) > 0) { ?>
<table width="650" border="0" cellpadding="2" cellspacing="2" align="center">
<tr>
	<td align="center" class="error" nowrap>
		<b>
		<? echo $msg; ?>
		</b>
	</td>
</tr>
</table>
<br>
<? } ?>

<table width="600" border="0" cellpadding="5" cellspacing="3" align="center">
<tr>
	<td class='table_line'>
		<b>Assunto</b>
	</td>
	<td class='table_line2'>
		<input type="text" name="assunto" size = "20" maxlength="100" value="<? echo $assunto ?>" style="width:450px" onFocus="nextfield ='anexo';" class="frm">
	</td>
</tr>
<tr>
	<td class='table_line'>
		<b>Anexo</b>
	</td>
	<td class='table_line2'>
		<input type='file' name='anexo' size='60' onFocus="nextfield ='mensagem';" class="frm">
	</td>
</tr>
<tr>
	<td class='table_line'>
		<b>Mensagem</b>
	</td>
	<td class='table_line2'>
		<textarea name="mensagem" cols="50" rows="10" style="width:450px" onFocus="nextfield='remetente';" class="frm"><?echo $mensagem?></textarea>
	</td>
</tr>
<tr>
	<td class='table_line'>
		<b>E-mail do Remetente</b>
	</td>
	<td class='table_line2'>
		<input type="text" name="remetente" size="20" maxlength="" value="<? echo $remetente ?>" style="width:450px" onFocus="nextfield ='email_interno_0';" class="frm">
	</td>
</tr>
</table>

<br>

<?
$sql =	"SELECT email_gerente
		FROM tbl_fabrica
		WHERE fabrica = $login_fabrica;";
$res = pg_exec($con,$sql);

if (pg_numrows($res) > 0) {
	echo "<table width='600' border='0' cellpadding='5' cellspacing='3' align='center'>\n";
	echo "<tr>\n";
	echo "<td class='table_line' colspan='2'>\n";
	echo "<center><b>Destinatários internos</b><br>";
	echo "(Selecione os <b>destinatários</b> para receberem cópia do comunicado)</center>\n";
	echo "</td>\n";
	echo "</tr>\n";

	$emails = explode (";", pg_result($res,0,email_gerente) );

	$coluna = 0;
	$prox   = 0;

	if (count($emails) > 0) {
		echo "<tr>\n";

		for ($x = 0 ; $x < count($emails) ; $x++) {

			if (strlen($msg) > 0) $email_interno = $_POST["email_interno_". $x];

			$coluna++;
			$prox = $x + 1;

			if ($coluna == 3) {
				echo "</tr>\n";
				echo "<tr>\n";
				$coluna = 1;
			}

			echo "<td class='table_line2' nowrap>";
			echo "<input type='checkbox' name='email_interno_$x' value='" . $emails[$x] . "'";
			if ($emails[$x] == $email_interno) echo " checked";
			if ($prox < count($emails)) {
				echo " onFocus=\"nextfield ='email_interno_$prox'\"";
			}else{
				echo " onFocus=\"nextfield ='destinatario_especifico'\"";
			}
			echo " class='frm'> " . $emails[$x] . "</td>\n";
		}
		echo "</tr>\n";
	}

	echo "</table>\n";
	echo "<br>\n";
}
?>

<table width="600" border="0" cellpadding="5" cellspacing="3" align="center">
<tr>
	<td class='table_line'>
		<center><b>Destinatários específicos</b><br>
		(Utilize o <b>código do posto</b> um em cada linha)<br>
		<hr>
		Esta opção mostrará o comunicado para o posto independente das condições abaixo.<br>
		<hr>
		Caso utilize esta opção e mais as abaixo o sistema mostrará o comunicado para o(s) posto(s) específico(s) e para para o(s) posto(s) em que as regras informadas abaixo sejam válidas.</center>
	</td>
</tr>
<tr>
	<td class='table_line2'>
		<textarea name="destinatario_especifico" cols="94" rows="5" onFocus="nextfield ='done';" class="frm"><? echo $destinatario_especifico ?></textarea>
	</td>
</tr>
</table>

<br>

<?
$sql =	"SELECT  tipo_posto ,
				 descricao  
		FROM     tbl_tipo_posto
		WHERE    fabrica = $login_fabrica
		ORDER BY descricao;";
$res = pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {
	$coluna = 0;
	$prox   = 0;

	echo "<table width='600' border='0' cellpadding='5' cellspacing='3' align='center'>\n";
	echo "<tr>\n";
	echo "<td class='table_line' colspan='2'>\n";
	echo "<center><b>Destinatário por TIPO</b></center>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";

	for ($x = 0 ; $x < pg_numrows($res) ; $x++){

		if (strlen($msg) > 0) $destinatario = $_POST["destinatario_". $x];

		$coluna++;
		$prox = $x + 1;

		$x_tipo_posto = trim(pg_result($res,$x,tipo_posto));
		$x_descricao  = trim(pg_result($res,$x,descricao));

		if ($coluna == 3) {
			echo "</tr>\n";
			echo "<tr>\n";
			$coluna = 1;
		}

		echo "<td class='table_line2'>\n";
		echo "<input type='checkbox' name='destinatario_$x' value='$x_tipo_posto'";
		if ($destinatario == $x_tipo_posto) echo " checked";
		if ($prox < pg_numrows($res)) {
			echo " onFocus=\"nextfield ='destinatario_$prox'\"";
		}else{
			echo " onFocus=\"nextfield ='linha_0'\"";
		}
		echo " class='frm'> $x_descricao";
		echo "</td>\n";
	}
	echo "</tr>\n";
	echo "<input type='hidden' name='destinatarios' value='$x'>\n";
	echo "</table>\n";
	echo "<br>\n";
}

$sql =	"SELECT linha, codigo_linha, nome
		FROM tbl_linha
		WHERE fabrica = $login_fabrica;";
$res = pg_exec($con,$sql);

if (pg_numrows($res) > 0) {
	$coluna = 0;
	$prox   = 0;

	echo "<table width='600' border='0' cellpadding='5' cellspacing='3' align='center'>\n";
	echo "<tr>\n";
	echo "<td class='table_line' colspan='2'>\n";
	echo "<center><b>Linha de Atendimento</b></center>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";

	for ($x = 0 ; $x < pg_numrows($res) ; $x++) {

		if (strlen($msg) > 0) $linha = $_POST["linha_". $x];

		$x_linha        = trim(pg_result($res,$x,linha));
		$x_codigo_linha = trim(pg_result($res,$x,codigo_linha));
		$x_nome         = trim(pg_result($res,$x,nome));

		$coluna++;
		$prox = $x + 1;

		if ($coluna == 3) {
			echo "</tr>\n";
			echo "<tr>\n";
			$coluna = 1;
		}

		echo "<td class='table_line2'>";
		echo "<input type='checkbox' name='linha_$x' value='$x_linha'";
		if ($linha == $x_linha) echo " checked";
		if ($prox < pg_numrows($res)) {
			echo " onFocus=\"nextfield ='linha_$prox'\"";
		}else{
			echo " onFocus=\"nextfield ='pede_peca_garantia'\"";
		}
		echo " class='frm'> $x_nome";
		echo "</td>\n";
	}
	echo "</tr>\n";
	echo "</table>\n";
	echo "<br>\n";
}
?>

<table width="600" border="0" cellpadding="5" cellspacing="3" align="center">
<tr>
	<td class='table_line' width='150'>
		<b>Pede Peça em Garantia</b>
	</td>
	<td class='table_line2'>
		<select name='pede_peca_garantia' onFocus="nextfield ='pede_peca_faturada';" class='frm'>
			<option value="" <? if ($pede_peca_garantia == "") echo "selected" ?>>Todos</option>
			<option value="t" <? if ($pede_peca_garantia == "t") echo "selected" ?>>Sim</option>
			<option value="f" <? if ($pede_peca_garantia == "f") echo "selected" ?>>Não</option>
		</select>
	</td>
</tr>
<tr>
	<td class='table_line'>
		<b>Pede Peça Faturada</b>
	</td>
	<td class='table_line2'>
		<select name='pede_peca_faturada' onFocus="nextfield ='suframa';" class='frm'>
			<option value="" <? if ($pede_peca_faturada == "") echo "selected" ?>>Todos</option>
			<option value="t" <? if ($pede_peca_faturada == "t") echo "selected" ?>>Sim</option>
			<option value="f" <? if ($pede_peca_faturada == "f") echo "selected" ?>>Não</option>
		</select>
	</td>
</tr>
<tr>
	<td class='table_line'>
		<b>Suframa</b>
	</td>
	<td class='table_line2'>
		<select name='suframa' onFocus="nextfield ='done';" class='frm'>
			<option value="" <? if ($suframa == "") echo "selected" ?>>Todos</option>
			<option value="t" <? if ($suframa == "t") echo "selected" ?>>Sim</option>
			<option value="f" <? if ($suframa == "f") echo "selected" ?>>Não</option>
		</select>
	</td>
</tr>
</table>

<br>

<!-- ============================ Botoes de Acao ========================= -->

<input type='hidden' name='btn_finalizar' value='0'>

<center>
<input type='submit' name='btnacao' class='btnrel' value='Enviar...' onclick="javascript: if ( document.frmcomunicado.btn_finalizar.value == '0' ) { document.frmcomunicado.btn_finalizar.value='1'; document.frmcomunicado.submit() ; } else { alert ('Aguarde submissão da OS...'); }">
</center>

</form>

<?
}else{
	echo "<br><br>";
	echo "<center>";
	echo "<font face = 'arial, verdana, times, sans' size='3' color='#000000'>Comunicado enviado com sucesso.</font>";
	echo "<br><br>";
	echo "<font face = 'arial, verdana, times, sans' size='3' color='#000000'>Para efetuar outro lançamento, <a href='$PHP_SELF'>clique aqui</a>.</font>";
	echo "</center>";
}

include "rodape.php";
?>