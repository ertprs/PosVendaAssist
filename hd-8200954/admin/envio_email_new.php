<?
## N�o � agendado, envia o e-mail na hora.
## N�o envia com anexo, apenas o contrato.

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';
include 'funcoes.php';

if (strlen($_GET["email_fabrica"]) > 0) {
	$email_fabrica = trim($_GET["email_fabrica"]);
}

if (strlen($_POST["email_fabrica"]) > 0) {
	$email_fabrica = trim($_POST["email_fabrica"]);
}

if (strlen($_POST["btn_acao"]) > 0) {
	$btn_acao = trim($_POST["btn_acao"]);
}

if (strlen($_POST["contrato"]) > 0) {
	$contrato = trim($_POST["contrato"]);
}


if ($btn_acao == "confirmar") {

	if (strlen($_POST["email_remetente"]) > 0) {
		$aux_email_remetente = trim($_POST["email_remetente"]);
	}else{
		$msg_erro = "Informe o email do remetente.";
	}

	if (strlen($_POST["linha"]) > 0) {
		$aux_linha = trim($_POST["linha"]);
		$sql = "SELECT contato_email 
					FROM tbl_posto_fabrica 
					JOIN tbl_posto_linha USING(posto) 
					WHERE fabrica = $login_fabrica 
					AND linha = $aux_linha;";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res) > 0){
			$aux_email_destinatario = '';
			for($i=0;$i<pg_numrows($res);$i++){
				$aux_email_destinatario .= pg_result($res,$i,contato_email);
				$aux_email_destinatario .= " ; ";
			}
		}else{
			$msg_erro = "N�o foi encontrado postos para esta linha na sua rede.";
		}
	}else{
		$aux_linha = 'null';
		if (strlen($_POST["email_destinatario"]) > 0) {
			$aux_email_destinatario = trim($_POST["email_destinatario"]);
		}else{
			$msg_erro = "Informe o email do destinatario.";
		}
	}

//echo "$aux_email_destinatario";

	if (strlen($_POST["assunto"]) > 0) {
		$aux_assunto = trim($_POST["assunto"]);
	}else{
		$msg_erro = "Informe o assunto.";
	}

	if (strlen($_POST["mens_corpo"]) > 0) {
		$aux_mens_corpo = trim($_POST["mens_corpo"]);
	}else{
		$msg_erro = "Informe a mensagem.";
	}

	if(strlen($contrato) == 0){
	#SEM CONTRATO
		if(strlen($msg_erro) == 0){
			$config["tamanho"] = 4096000;

			$data_php = date("His");

			for($i = 1; $i < 3; $i++){

				$arquivo                = isset($_FILES["arquivo$i"]) ? $_FILES["arquivo$i"] : FALSE;
				// Formul�rio postado... executa as a��es 
				if (strlen($arquivo["tmp_name"]) > 0 && $arquivo["tmp_name"] != "none"){
					$xposto = $posto;

					// Verifica o MIME-TYPE do arquivo
					if (!preg_match("/\/(pjpeg|jpeg|png|gif|bmp)$/", $arquivo["type"])){
						$msg_erro = "Arquivo em formato inv�lido!";
					} else {
						// Verifica tamanho do arquivo 
						if ($arquivo["size"] > $config["tamanho"]) 
							$msg_erro = "Arquivo em tamanho muito grande! Deve ser de no m�ximo 4MB. Envie outro arquivo.";
					}
					if (strlen($msg_erro) == 0) {
						// Pega extens�o do arquivo
						preg_match("/\.(gif|bmp|png|jpg|jpeg){1}$/i", $arquivo["name"], $ext);
						if($i == 1){
							$nome_anexo = "cabecalho_".$login_fabrica."_".date("His").".".$ext[1];
							$img_cabecalho = $nome_anexo;
						}else{
							$nome_anexo = "rodape_".$login_fabrica."_".date("His").".".$ext[1];
							$img_rodape = $nome_anexo;
						}
						
						// Gera um nome �nico para a imagem
	//					$nome_anexo = $xposto;

						// Caminho de onde a imagem ficar� + extensao
						$imagem_dir = "../admin/imagem_upload/".strtolower($nome_anexo);

						// Exclui anteriores, qquer extensao
						//@unlink($imagem_dir);

						// Faz o upload da imagem
						if (strlen($msg_erro) == 0) {
							move_uploaded_file($arquivo["tmp_name"], $imagem_dir);
	//						if (copy($arquivo["tmp_name"], $imagem_dir)) {

								// resize $_FILES[ 'myUploadedFile' ] widht
	//							$thumbail = new resize( "arquivo$i", 600, 400 );

								// save the resized image to "./TEMP.EXT"
	//							$thumbail -> saveTo("$nome_anexo.".$thumbail -> type,"assist/credenciamento/fotos/" ); 
	//						}
						}
					}
				}
			}
		}


		if(strlen($msg_erro) == 0){

			$email_enviado      = array();
			$email_nao_enviado  = array();
			$email_origem       = "$aux_email_remetente";
			$email_supervisor   = "$aux_email_destinatario";
//			$email_supervisor   = "fernando@telecontrol.com.br; helpdesk@telecontrol.com.br";
			$assunto            = "$aux_assunto";

			$email_array = explode(";",$email_supervisor);

			//print_r($email_array);
			#GERA OS ARQUIVOS DE CONTRATO PDF.

			$controle = count($email_array);

			for($i=0;$i<$controle;$i++){

				$email = trim($email_array[$i]);

				#CABE�ALHO
				if(strlen($img_cabecalho) > 0){
					$corpo = "<img src='imagem_upload/$img_cabecalho'><br>";
				}
				
				#CORPO DA MENSAGEM
				$corpo .= $aux_mens_corpo;
				
				#RODAP�
				if(strlen($img_rodape) > 0){
					$corpo .= "<br><img src='imagem_upload/$img_rodape'>";
				}

				$body_top  = "--Message-Boundary\n";
				$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
				$body_top .= "Content-transfer-encoding: 7BIT\n";
				$body_top .= "Content-description: Mail message body\n\n";

				if(mail($email, stripslashes(utf8_encode($assunto)), utf8_encode($corpo), "From: ".$aux_email_remetente." \n $body_top " )){
					$msg_ok = "Mensagem enviada corretamente!";
					#limpa os campos

				}else{
					$msg_erro = "Mensagem n�o enviada";
				}
			}
		}
	}else{
	#COM CONTRATO
		if(strlen($msg_erro) == 0 AND $login_fabrica == 25){

			$config["tamanho"] = 4096000;

			$data_php = date("His");

			for($i = 1; $i < 3; $i++){

				$arquivo                = isset($_FILES["arquivo$i"]) ? $_FILES["arquivo$i"] : FALSE;
				// Formul�rio postado... executa as a��es 
				if (strlen($arquivo["tmp_name"]) > 0 && $arquivo["tmp_name"] != "none"){
					$xposto = $posto;

					// Verifica o MIME-TYPE do arquivo
					if (!preg_match("/\/(pjpeg|jpeg|png|gif|bmp)$/", $arquivo["type"])){
						$msg_erro = "Arquivo em formato inv�lido!";
					} else {
						// Verifica tamanho do arquivo 
						if ($arquivo["size"] > $config["tamanho"]) 
							$msg_erro = "Arquivo em tamanho muito grande! Deve ser de no m�ximo 4MB. Envie outro arquivo.";
					}
					if (strlen($msg_erro) == 0) {
						// Pega extens�o do arquivo
						preg_match("/\.(gif|bmp|png|jpg|jpeg){1}$/i", $arquivo["name"], $ext);
						if($i == 1){
							$nome_anexo = "cabecalho_".$login_fabrica."_".date("His").".".$ext[1];
							$img_cabecalho = $nome_anexo;
						}else{
							$nome_anexo = "rodape_".$login_fabrica."_".date("His").".".$ext[1];
							$img_rodape = $nome_anexo;
						}
						
						// Gera um nome �nico para a imagem
	//					$nome_anexo = $xposto;

						// Caminho de onde a imagem ficar� + extensao
						$imagem_dir = "../admin/imagem_upload/".strtolower($nome_anexo);

						// Exclui anteriores, qquer extensao
						//@unlink($imagem_dir);

						// Faz o upload da imagem
						if (strlen($msg_erro) == 0) {
							move_uploaded_file($arquivo["tmp_name"], $imagem_dir);
	//						if (copy($arquivo["tmp_name"], $imagem_dir)) {

								// resize $_FILES[ 'myUploadedFile' ] widht
	//							$thumbail = new resize( "arquivo$i", 600, 400 );

								// save the resized image to "./TEMP.EXT"
	//							$thumbail -> saveTo("$nome_anexo.".$thumbail -> type,"assist/credenciamento/fotos/" ); 
	//						}
						}
					}
				}
			}
		}


		if(strlen($msg_erro) == 0){
			$email_enviado      = array();
			$email_nao_enviado  = array();
			$email_origem       = "$aux_email_remetente";
			$email_supervisor   = "$aux_email_destinatario";
//			$email_supervisor   = "fernando@telecontrol.com.br; helpdesk@telecontrol.com.br";
			$assunto            = "$aux_assunto";

			$email_array = explode(";",$email_supervisor);

			//print_r($email_array);
			#GERA OS ARQUIVOS DE CONTRATO PDF.

			$controle = count($email_array);


			for($i=0;$i<$controle;$i++){

				$email = trim($email_array[$i]);

				$sql    = "SELECT DISTINCT  tbl_posto.posto ,
									upper(nome)   as nome   ,
									contato_endereco                ,
									contato_numero                  ,
									contato_complemento             ,
									contato_cidade                  ,
									contato_estado                  ,
									SUBSTR (tbl_posto_fabrica.contato_cep,1,2) || '.' || SUBSTR (tbl_posto_fabrica.contato_cep,3,3) || '-' || SUBSTR (tbl_posto_fabrica.contato_cep,6,3) AS cep ,
									SUBSTR (tbl_posto.cnpj,1,2) || '.' || SUBSTR (tbl_posto.cnpj,3,3) || '.' || SUBSTR (tbl_posto.cnpj,6,3) || '/' || SUBSTR (tbl_posto.cnpj,9,4) || '-' || SUBSTR (tbl_posto.cnpj,13,2) AS cnpj     ,
									tbl_posto.posto         ,
									to_char(current_date,'DD/MM/YYYY') as data_contrato
								FROM tbl_posto JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
								WHERE  contato_email = '$email';";
				$res = pg_exec($con,$sql);
	//$msg_erro = "$sql<br>";
				if(pg_numrows($res)>0){
					$posto_nome     = pg_result($res,0,nome);
					$endereco       = pg_result($res,0,contato_endereco);
					$numero         = pg_result($res,0,contato_numero);
					$complemento    = pg_result($res,0,contato_complemento);
					$cidade         = pg_result($res,0,contato_cidade);
					$estado         = pg_result($res,0,contato_estado);
					$cep            = pg_result($res,0,cep);
					$cnpj           = pg_result($res,0,cnpj);
					$posto          = pg_result($res,0,posto);
					$data_contrato  = pg_result($res,0,data_contrato);


					$conteudo = "<html xmlns:o='urn:schemas-microsoft-com:office:office'
					xmlns:w='urn:schemas-microsoft-com:office:word'
					xmlns:st1='urn:schemas-microsoft-com:office:smarttags'
					xmlns='http://www.w3.org/TR/REC-html40'>

					<head>
					<meta http-equiv=Content-Type content='text/html; charset=windows-1252'>
					<meta name=ProgId content=Word.Document>
					<meta name=Generator content='Microsoft Word 11'>
					<meta name=Originator content='Microsoft Word 11'>
					<link rel=File-List
					href='Contrato%20Credenciamento%20Postos_arquivos/filelist.xml'>
					<link rel=Preview href='Contrato%20Credenciamento%20Postos_arquivos/preview.wmf'>
					<title>CONTRATO DE CREDENCIAMENTO DE ASSIST�NCIA T�CNICA</title>
					<o:SmartTagType namespaceuri='urn:schemas-microsoft-com:office:smarttags'
					 name='PersonName'/>
					<!--[if gte mso 9]><xml>
					 <o:DocumentProperties>
					  <o:Author>Lu�s Rodolfo Creuz</o:Author>
					  <o:LastAuthor>T�lio Oliveira</o:LastAuthor>
					  <o:Revision>2</o:Revision>
					  <o:TotalTime>2</o:TotalTime>
					  <o:LastPrinted>2113-01-01T03:00:00Z</o:LastPrinted>
					  <o:Created>2007-12-03T19:34:00Z</o:Created>
					  <o:LastSaved>2007-12-03T19:34:00Z</o:LastSaved>
					  <o:Pages>1</o:Pages>
					  <o:Words>3941</o:Words>
					  <o:Characters>21282</o:Characters>
					  <o:Company>Telecontrol</o:Company>
					  <o:Lines>177</o:Lines>
					  <o:Paragraphs>50</o:Paragraphs>
					  <o:CharactersWithSpaces>25173</o:CharactersWithSpaces>
					  <o:Version>11.5606</o:Version>
					 </o:DocumentProperties>
					</xml><![endif]--><!--[if gte mso 9]><xml>
					 <w:WordDocument>
					  <w:PunctuationKerning/>
					  <w:DrawingGridHorizontalSpacing>0 pt</w:DrawingGridHorizontalSpacing>
					  <w:DrawingGridVerticalSpacing>0 pt</w:DrawingGridVerticalSpacing>
					  <w:DisplayHorizontalDrawingGridEvery>0</w:DisplayHorizontalDrawingGridEvery>
					  <w:DisplayVerticalDrawingGridEvery>0</w:DisplayVerticalDrawingGridEvery>
					  <w:UseMarginsForDrawingGridOrigin/>
					  <w:ValidateAgainstSchemas/>
					  <w:SaveIfXMLInvalid>false</w:SaveIfXMLInvalid>
					  <w:IgnoreMixedContent>false</w:IgnoreMixedContent>
					  <w:AlwaysShowPlaceholderText>false</w:AlwaysShowPlaceholderText>
					  <w:DrawingGridHorizontalOrigin>0 pt</w:DrawingGridHorizontalOrigin>
					  <w:DrawingGridVerticalOrigin>0 pt</w:DrawingGridVerticalOrigin>
					  <w:Compatibility>
					   <w:SpaceForUL/>
					   <w:BalanceSingleByteDoubleByteWidth/>
					   <w:DoNotLeaveBackslashAlone/>
					   <w:ULTrailSpace/>
					   <w:DoNotExpandShiftReturn/>
					   <w:AdjustLineHeightInTable/>
					   <w:SelectEntireFieldWithStartOrEnd/>
					   <w:UseWord2002TableStyleRules/>
					  </w:Compatibility>
					  <w:BrowserLevel>MicrosoftInternetExplorer4</w:BrowserLevel>
					 </w:WordDocument>
					</xml><![endif]--><!--[if gte mso 9]><xml>
					 <w:LatentStyles DefLockedState='false' LatentStyleCount='156'>
					 </w:LatentStyles>
					</xml><![endif]--><!--[if !mso]><object
					 classid='clsid:38481807-CA0E-42D2-BF39-B33AF135CC4D' id=ieooui></object>
					<style>
					st1\:*{behavior:url(\#ieooui) }
					</style>
					<![endif]-->
					<style>
					<!--
					 /* Font Definitions */
					 @font-face
						{font-family:'New York';
						panose-1:2 4 5 3 6 5 6 2 3 4;
						mso-font-charset:0;
						mso-generic-font-family:roman;
						mso-font-format:other;
						mso-font-pitch:variable;
						mso-font-signature:3 0 0 0 1 0;}
					@font-face
						{font-family:Tahoma;
						panose-1:2 11 6 4 3 5 4 4 2 4;
						mso-font-charset:0;
						mso-generic-font-family:swiss;
						mso-font-pitch:variable;
						mso-font-signature:1627421319 -2147483648 8 0 66047 0;}
					@font-face
						{font-family:Verdana;
						panose-1:2 11 6 4 3 5 4 4 2 4;
						mso-font-charset:0;
						mso-generic-font-family:swiss;
						mso-font-pitch:variable;
						mso-font-signature:536871559 0 0 0 415 0;}
					@font-face
						{font-family:'DejaVu Sans';
						mso-font-charset:0;
						mso-generic-font-family:auto;
						mso-font-pitch:variable;
						mso-font-signature:0 0 0 0 0 0;}
					@font-face
						{font-family:'Lucida Sans Unicode';
						panose-1:2 11 6 2 3 5 4 2 2 4;
						mso-font-charset:0;
						mso-generic-font-family:swiss;
						mso-font-pitch:variable;
						mso-font-signature:-2147476737 14699 0 0 63 0;}
					@font-face
						{font-family:StarSymbol;
						mso-font-alt:'Arial Unicode MS';
						mso-font-charset:128;
						mso-generic-font-family:auto;
						mso-font-pitch:auto;
						mso-font-signature:0 0 0 0 0 0;}
					@font-face
						{font-family:'\@StarSymbol';
						mso-font-charset:128;
						mso-generic-font-family:auto;
						mso-font-pitch:auto;
						mso-font-signature:0 0 0 0 0 0;}
					 /* Style Definitions */
					 p.MsoNormal, li.MsoNormal, div.MsoNormal
						{mso-style-parent:;
						margin:0cm;
						margin-bottom:.0001pt;
						mso-pagination:widow-orphan;
						mso-hyphenate:none;
						font-size:12.0pt;
						font-family:'Times New Roman';
						mso-fareast-font-family:'Times New Roman';
						mso-fareast-language:AR-SA;}
					h1
						{mso-style-next:Normal;
						margin:0cm;
						margin-bottom:.0001pt;
						text-indent:0cm;
						mso-pagination:widow-orphan;
						page-break-after:avoid;
						mso-outline-level:1;
						mso-list:l0 level1 lfo1;
						mso-hyphenate:none;
						tab-stops:list 0cm;
						font-size:12.0pt;
						font-family:'Times New Roman';
						mso-font-kerning:0pt;
						mso-fareast-language:AR-SA;
						font-weight:bold;
						mso-bidi-font-weight:normal;
						font-style:italic;
						mso-bidi-font-style:normal;}
					h3
						{mso-style-next:Normal;
						margin-top:12.0pt;
						margin-right:0cm;
						margin-bottom:3.0pt;
						margin-left:0cm;
						text-indent:0cm;
						mso-pagination:widow-orphan;
						page-break-after:avoid;
						mso-outline-level:3;
						mso-list:l0 level3 lfo1;
						mso-hyphenate:none;
						tab-stops:list 0cm;
						font-size:13.0pt;
						font-family:Arial;
						mso-fareast-language:AR-SA;
						font-weight:bold;}
					p.MsoHeader, li.MsoHeader, div.MsoHeader
						{margin:0cm;
						margin-bottom:.0001pt;
						mso-pagination:widow-orphan;
						mso-hyphenate:none;
						tab-stops:center 212.6pt right 425.2pt;
						font-size:12.0pt;
						font-family:'Times New Roman';
						mso-fareast-font-family:'Times New Roman';
						mso-fareast-language:AR-SA;}
					p.MsoFooter, li.MsoFooter, div.MsoFooter
						{margin:0cm;
						margin-bottom:.0001pt;
						mso-pagination:widow-orphan;
						mso-hyphenate:none;
						tab-stops:center 212.6pt right 425.2pt;
						font-size:12.0pt;
						font-family:'Times New Roman';
						mso-fareast-font-family:'Times New Roman';
						mso-fareast-language:AR-SA;}
					p.MsoList, li.MsoList, div.MsoList
						{mso-style-parent:'Corpo de texto';
						margin-top:0cm;
						margin-right:0cm;
						margin-bottom:6.0pt;
						margin-left:0cm;
						mso-pagination:widow-orphan;
						mso-hyphenate:none;
						font-size:12.0pt;
						font-family:'Times New Roman';
						mso-fareast-font-family:'Times New Roman';
						mso-bidi-font-family:Tahoma;
						mso-fareast-language:AR-SA;}
					p.MsoBodyText, li.MsoBodyText, div.MsoBodyText
						{margin-top:0cm;
						margin-right:0cm;
						margin-bottom:6.0pt;
						margin-left:0cm;
						mso-pagination:widow-orphan;
						mso-hyphenate:none;
						font-size:12.0pt;
						font-family:'Times New Roman';
						mso-fareast-font-family:'Times New Roman';
						mso-fareast-language:AR-SA;}
					p.MsoBodyTextIndent, li.MsoBodyTextIndent, div.MsoBodyTextIndent
						{margin-top:0cm;
						margin-right:0cm;
						margin-bottom:0cm;
						margin-left:35.4pt;
						margin-bottom:.0001pt;
						text-align:justify;
						mso-pagination:widow-orphan;
						mso-hyphenate:none;
						font-size:12.0pt;
						font-family:Verdana;
						mso-fareast-font-family:'Times New Roman';
						mso-bidi-font-family:'Times New Roman';
						mso-fareast-language:AR-SA;}
					a:link, span.MsoHyperlink
						{mso-style-parent:;
						color:navy;
						text-decoration:underline;
						text-underline:single;}
					a:visited, span.MsoHyperlinkFollowed
						{color:purple;
						text-decoration:underline;
						text-underline:single;}
					p
						{margin-top:14.0pt;
						margin-right:0cm;
						margin-bottom:14.0pt;
						margin-left:0cm;
						mso-pagination:widow-orphan;
						mso-hyphenate:none;
						font-size:12.0pt;
						font-family:'Times New Roman';
						mso-fareast-font-family:'Times New Roman';
						mso-fareast-language:AR-SA;}
					span.Absatz-Standardschriftart
						{mso-style-name:Absatz-Standardschriftart;
						mso-style-parent:;}
					span.WW-Absatz-Standardschriftart
						{mso-style-name:WW-Absatz-Standardschriftart;
						mso-style-parent:;}
					span.WW-Absatz-Standardschriftart1
						{mso-style-name:WW-Absatz-Standardschriftart1;
						mso-style-parent:;}
					span.Fontepargpadro1
						{mso-style-name:'Fonte par�g\. padr�o1';
						mso-style-parent:;}
					span.NumberingSymbols
						{mso-style-name:'Numbering Symbols';
						mso-style-parent:;}
					span.Bullets
						{mso-style-name:Bullets;
						mso-style-parent:;
						mso-ansi-font-size:9.0pt;
						mso-bidi-font-size:9.0pt;
						font-family:StarSymbol;
						mso-ascii-font-family:StarSymbol;
						mso-fareast-font-family:StarSymbol;
						mso-hansi-font-family:StarSymbol;
						mso-bidi-font-family:StarSymbol;}
					p.Heading, li.Heading, div.Heading
						{mso-style-name:Heading;
						mso-style-next:'Corpo de texto';
						margin-top:12.0pt;
						margin-right:0cm;
						margin-bottom:6.0pt;
						margin-left:0cm;
						mso-pagination:widow-orphan;
						page-break-after:avoid;
						mso-hyphenate:none;
						font-size:14.0pt;
						font-family:Arial;
						mso-fareast-font-family:'Lucida Sans Unicode';
						mso-bidi-font-family:Tahoma;
						mso-fareast-language:AR-SA;}
					p.Caption, li.Caption, div.Caption
						{mso-style-name:Caption;
						margin-top:6.0pt;
						margin-right:0cm;
						margin-bottom:6.0pt;
						margin-left:0cm;
						mso-pagination:widow-orphan no-line-numbers;
						mso-hyphenate:none;
						font-size:12.0pt;
						font-family:'Times New Roman';
						mso-fareast-font-family:'Times New Roman';
						mso-bidi-font-family:Tahoma;
						mso-fareast-language:AR-SA;
						font-style:italic;}
					p.Index, li.Index, div.Index
						{mso-style-name:Index;
						margin:0cm;
						margin-bottom:.0001pt;
						mso-pagination:widow-orphan no-line-numbers;
						mso-hyphenate:none;
						font-size:12.0pt;
						font-family:'Times New Roman';
						mso-fareast-font-family:'Times New Roman';
						mso-bidi-font-family:Tahoma;
						mso-fareast-language:AR-SA;}
					p.TableContents, li.TableContents, div.TableContents
						{mso-style-name:'Table Contents';
						margin:0cm;
						margin-bottom:.0001pt;
						mso-pagination:widow-orphan no-line-numbers;
						mso-hyphenate:none;
						font-size:12.0pt;
						font-family:'Times New Roman';
						mso-fareast-font-family:'Times New Roman';
						mso-fareast-language:AR-SA;}
					p.TableHeading, li.TableHeading, div.TableHeading
						{mso-style-name:'Table Heading';
						mso-style-parent:'Table Contents';
						margin:0cm;
						margin-bottom:.0001pt;
						text-align:center;
						mso-pagination:widow-orphan no-line-numbers;
						mso-hyphenate:none;
						font-size:12.0pt;
						font-family:'Times New Roman';
						mso-fareast-font-family:'Times New Roman';
						mso-fareast-language:AR-SA;
						font-weight:bold;}
					 /* Page Definitions */
					 @page
						{mso-footnote-position:beneath-text;}
					@page Section1
						{size:595.25pt 841.85pt;
						margin:70.85pt 3.0cm 70.85pt 3.0cm;
						mso-header-margin:36.0pt;
						mso-footer-margin:36.0pt;
						mso-paper-source:0;}
					div.Section1
						{page:Section1;
						mso-footnote-position:beneath-text;}
					 /* List Definitions */
					 @list l0
						{mso-list-id:1;
						mso-list-template-ids:1;}
					@list l0:level1
						{mso-level-number-format:none;
						mso-level-suffix:none;
						mso-level-text:;
						mso-level-tab-stop:0cm;
						mso-level-number-position:left;
						margin-left:0cm;
						text-indent:0cm;}
					@list l0:level2
						{mso-level-number-format:none;
						mso-level-suffix:none;
						mso-level-text:;
						mso-level-tab-stop:0cm;
						mso-level-number-position:left;
						margin-left:0cm;
						text-indent:0cm;}
					@list l0:level3
						{mso-level-number-format:none;
						mso-level-suffix:none;
						mso-level-text:;
						mso-level-tab-stop:0cm;
						mso-level-number-position:left;
						margin-left:0cm;
						text-indent:0cm;}
					@list l0:level4
						{mso-level-number-format:none;
						mso-level-suffix:none;
						mso-level-text:;
						mso-level-tab-stop:0cm;
						mso-level-number-position:left;
						margin-left:0cm;
						text-indent:0cm;}
					@list l0:level5
						{mso-level-number-format:none;
						mso-level-suffix:none;
						mso-level-text:;
						mso-level-tab-stop:0cm;
						mso-level-number-position:left;
						margin-left:0cm;
						text-indent:0cm;}
					@list l0:level6
						{mso-level-number-format:none;
						mso-level-suffix:none;
						mso-level-text:;
						mso-level-tab-stop:0cm;
						mso-level-number-position:left;
						margin-left:0cm;
						text-indent:0cm;}
					@list l0:level7
						{mso-level-number-format:none;
						mso-level-suffix:none;
						mso-level-text:;
						mso-level-tab-stop:0cm;
						mso-level-number-position:left;
						margin-left:0cm;
						text-indent:0cm;}
					@list l0:level8
						{mso-level-number-format:none;
						mso-level-suffix:none;
						mso-level-text:;
						mso-level-tab-stop:0cm;
						mso-level-number-position:left;
						margin-left:0cm;
						text-indent:0cm;}
					@list l0:level9
						{mso-level-number-format:none;
						mso-level-suffix:none;
						mso-level-text:;
						mso-level-tab-stop:0cm;
						mso-level-number-position:left;
						margin-left:0cm;
						text-indent:0cm;}
					ol
						{margin-bottom:0cm;}
					ul
						{margin-bottom:0cm;}
					-->
					</style>
					<!--[if gte mso 10]>
					<style>
					 /* Style Definitions */
					 table.MsoNormalTable
						{mso-style-name:'Tabela normal';
						mso-tstyle-rowband-size:0;
						mso-tstyle-colband-size:0;
						mso-style-noshow:yes;
						mso-style-parent:;
						mso-padding-alt:0cm 5.4pt 0cm 5.4pt;
						mso-para-margin:0cm;
						mso-para-margin-bottom:.0001pt;
						mso-pagination:widow-orphan;
						font-size:10.0pt;
						font-family:'Times New Roman';
						mso-ansi-language:#0400;
						mso-fareast-language:#0400;
						mso-bidi-language:#0400;}
					</style>
					<![endif]-->
					</head>

					<body lang=PT-BR link=navy vlink=purple style='tab-interval:35.4pt'>

					<div class=Section1>

					<p class=MsoNormal align=center style='text-align:center;mso-line-height-alt:
					10.0pt'><b>CONTRATO DE CREDENCIAMENTO DE ASSIST�NCIA T�CNICA<o:p></o:p></b></p>

					<p class=MsoNormal style='mso-line-height-alt:8.0pt'><o:p>&nbsp;</o:p></p>

					<p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'>Pelo
					presente instrumento particular,</p>

					<p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><o:p>&nbsp;</o:p></p>

					<p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><b>HB
					ASSIST�NCIA T�CNICA LTDA</b>., sociedade empresarial com escrit�rio
					administrativo na Av. Yojiro Takaoka, 4.384 - Loja 17 - Conj. 2083 - Alphaville
					- Santana de Parna�ba, SP, CEP 06.541-038, inscrita no CNPJ sob n�
					08.326.458/0001-47, neste ato representada por seu diretor ao final assinado,
					doravante denominada<span style='mso-spacerun:yes'>�
					</span>&quot;HBFLEX S.A&quot;, e</p>


					<p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><b>$posto_nome.</b>, sociedade empresarial com sede na $endereco,
					$numero $complemento, na cidade de $cidade, $estado, CEP $cep, inscrita no CNPJ sob n�
					$cnpj, neste ato representada por seu administrador, ao final
					assinado, doravante denominada &quot;AUTORIZADA&quot;,</p>


					<br><p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><b><span
					style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
					mso-bidi-language:#00FF'>1- OBJETIVOS<o:p></o:p></span></b></p>

					<p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><span
					style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
					mso-bidi-language:#00FF'>1.1. O objetivo do presente contrato � a presta��o, 
					pela AUTORIZADA, em sua sede social, do servi�o de assist�ncia t�cnica aos 
					produtos comercializados pela HBFLEX S.A.<o:p></o:p></span></p>

					<p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><span
					style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
					mso-bidi-language:#00FF'>1.2. Os servi�os que ser�o prestados pela AUTORIZADA, 
					junto aos clientes usu�rios dos produtos comercializados atrav�s da HBFLEX S.A.,
					consistem em manuten��o corretiva e preventiva, seja atrav�s de repara��es a 
					domicilio cujos custos ser�o por conta do consumidor, ou em sua oficina, quando 
					os custos ser�o cobertos pela HBFLEX S.A., atrav�s de taxas de garantia, 
					fornecimento de pe�as e informa��es  t�cnicas.<o:p></o:p></span></p>

					<p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><b><span
					style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
					mso-bidi-language:#00FF'>2- DA EXECU��O DOS SERVI�OS DURANTE A GARANTIA<o:p></o:p>
					</span></b></p>

					<p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><span
					style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
					mso-bidi-language:#00FF'>2.1. O prazo e condi��es de garantia dos produtos 
					comercializados pela HBFLEX S.A., s�o especificados no certificado de garantia, 
					cujo in�cio � contado a partir da data de emiss�o da nota fiscal de compra do 
					produto pelo primeiro usu�rio.<o:p></o:p></span></p>

					<p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><span
					style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
					mso-bidi-language:#00FF'>2.2. Se no per�odo de garantia os equipamentos 
					apresentarem defeitos de fabrica��o, a AUTORIZADA providenciar� o reparo 
					utilizando exclusivamente pe�as originais sem qualquer �nus.<o:p></o:p></span></p>

					<p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><span
					style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
					mso-bidi-language:#00FF'>2.3. Para atendimento em garantia a AUTORIZADA 
					exigir�, do cliente usu�rio, a apresenta��o da NOTA FISCAL DE COMPRA. 
					A ordem de servi�o utilizada pela AUTORIZADA para consumidores, dever� 
					ser preenchida integralmente para ser considerada v�lida. Cada Ordem de 
					Servi�o (O.S.) dever� estar acompanhada da c�pia da Nota Fiscal de compra 
					do produto.<o:p></o:p></span></p>

					<p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><b><span
					style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
					mso-bidi-language:#00FF'>3- PRE�O E CONDI��ES DE PAGAMENTO<o:p></o:p>
					</span></b></p>

					<p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><span
					style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
					mso-bidi-language:#00FF'>3.1. Para consertos efetuados em aparelhos no 
					per�odo de garantia, a HBFLEX. S.A.,  pagar� � AUTORIZADA, os valores 
					discriminados nos itens abaixo, sempre ap�s o envio da Ordem de Servi�o 
					e C�pia da Nota Fiscal de Venda ao Consumidor,  at� o 7� (setimo) dia 
					de cada m�s subseq�ente ao atendimento:<o:p></o:p></span></p>

					<p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><span
					style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
					mso-bidi-language:#00FF'>
					<span style='mso-tab-count:6'>���������� - MP4 Player - Qualquer modelo 
					produzido pela HBFLEX - R$ 10,00 (dez reais), para qualquer reparo ou 
					intermedia��o de troca.<o:p></o:p></span></p>

					<p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><span
					style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
					mso-bidi-language:#00FF'>
					<span style='mso-tab-count:6'>���������� - Ar-Condicionado - Qualquer modelo 
					produzido pela HBFLEX - R$ 40,00 (quarenta reais). Para atendimento em garantia, 
					a dist�ncia que exceder a 60 km ida e volta ser� pago R$0,52 por quilometro 
					rodado desde que previamente aprovado pela administra��o da HBFLEX.<o:p></o:p>
					</span></p>

					<p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><span
					style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
					mso-bidi-language:#00FF'>
					<span style='mso-tab-count:6'>���������� - Coifa - Qualquer modelo produzido 
					pela HBFLEX - R$ 18,00 (dez reais).<o:p></o:p></span></p>

					<p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><span
					style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
					mso-bidi-language:#00FF'>
					<span style='mso-tab-count:6'>���������� - Para instala��o de produtos 
					novos e para atendimento p�s per�odo de garantia a autorizada compromete-se 
					a cumprir a tabela sugerida pela  HBFLEX.<o:p></o:p></span></p>

					<p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><b><span
					style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
					mso-bidi-language:#00FF'>4- DURA��O DO CONTRATO<o:p></o:p>
					</span></b></p>

					<p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><span
					style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
					mso-bidi-language:#00FF'>A validade do presente contrato � por tempo 
					indeterminado e poder� ser rescindido por qualquer das partes, mediante 
					um aviso pr�vio de 30 (trinta) dias, por escrito e protocolado. A autorizada 
					obriga-se, neste prazo do aviso, a dar continuidade aos atendimentos dos 
					produtos em seu poder.<o:p></o:p></span></p>

					<p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><span
					style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
					mso-bidi-language:#00FF'>4.1. O contrato ser� imediatamente rescindido caso 
					seja constatada e comprovada irregularidade na cobran�a dos servi�os e pe�as 
					prestados em equipamentos sob garantia da HBFLEX S.A., transfer�ncia da empresa 
					para novos s�cios, mudan�a de endere�o para �rea fora do interesse da HBFLEX S.A., 
					concordata, fal�ncia, liquida��o judicial ou extrajudicial.<o:p></o:p></span></p>

					<p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><span
					style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
					mso-bidi-language:#00FF'>4.2. No caso de rescis�o contratual, a AUTORIZADA se 
					obriga a devolver � HBFLEX S.A. toda documenta��o t�cnica e administrativa 
					cedida para seu uso enquanto CREDENCIADA.<o:p></o:p></span></p>

					<p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><b><span
					style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
					mso-bidi-language:#00FF'>5- RESPONSABILIDADES<o:p></o:p>
					</span></b></p>

					<p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><span
					style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
					mso-bidi-language:#00FF'>5.1. A AUTORIZADA assume responsabilidade pelo pagamento 
					das remunera��es devidas a seus funcion�rios, pelo recolhimento de todas as 
					contribui��es e tributos incidentes, bem como pelo cumprimento da legisla��o 
					social, trabalhista, previdenci�ria e securit�ria aplic�vel.<o:p></o:p></span></p>

					<p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><span
					style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
					mso-bidi-language:#00FF'>5.2. Em caso de quaisquer infra��es ao presente 
					contrato, que possam implicar em perda de cr�dito,  ou de alguma forma atingir 
					a imagem da HBFLEX S.A. junto ao p�blico consumidor, a AUTORIZADA, seus s�cios, 
					diretores, prepostos, colaboradores ou empregados, poder� ser responsabilizada 
					por meio de procedimento judicial pr�prio, inclusive podendo ser condenada em 
					perdas e danos.<o:p></o:p></span></p>

					<p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><span
					style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
					mso-bidi-language:#00FF'>5.3. Em caso de a��es propostas por consumidores, que 
					reste provada a culpa ou dolo da AUTORIZADA, seus s�cios, diretores, prepostos, 
					colaboradores ou empregados, esta concorda desde j� que dever� assumir e integrar 
					o p�lo passivo das a��es judiciais que venham a ser demandadas contra a HBFLEX S.A., 
					isentando a mesma e ressarcindo quaisquer valores que ela venha a ser condenada pagar 
					e/ou tenha pago.<o:p></o:p></span></p>

					<p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><b><span
					style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
					mso-bidi-language:#00FF'>6- DISPOSI��ES GERAIS<o:p></o:p>
					</span></b></p>

					<p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><span
					style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
					mso-bidi-language:#00FF'>6.1. A AUTORIZADA declara neste ato, estar ciente que 
					dever� manter, por sua conta e risco, seguro contra roubo e inc�ndio cujo valor 
					da ap�lice seja suficiente para cobrir sinistro que possa ocorrer em seu estabelecimento, 
					envolvendo patrim�nio pr�prio e/ou de terceiros. Caso n�o o fa�a assume total responsabilidade 
					e responder� civil e criminalmente pela omiss�o, perante terceiros e a HBFLEX S.A..
					<o:p></o:p></span></p>

					<p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><span
					style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
					mso-bidi-language:#00FF'>6.2. A AUTORIZADA declara conhecer e se compromete a cumprir 
					o disposto no C�digo de Defesa do Consumidor e assume a responsabilidade de &quot;in vigilando&quot; 
					por seus funcion�rios para esta finalidade.<o:p></o:p></span></p>

					<p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><span
					style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
					mso-bidi-language:#00FF'>6.3. Os componentes solicitados para uma determinada O.S. 
					s� poder�o ser usados para ela e dever�o constar na mesma. A aus�ncia dessa O.S. 
					na HBFLEX S.A., decorrido o prazo regular, dar� direito � HBFLEX S.A. de fatur�-los 
					contra a AUTORIZADA. As pe�as utilizadas em garantia dever�o ser mantidas por 90 
					dias antes do descarte.<o:p></o:p></span></p>

					<p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><span
					style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
					mso-bidi-language:#00FF'>6.4. Os d�bitos n�o quitados no vencimento, ser�o 
					descontados do primeiro movimento de ORDENS DE SERVI�O, ap�s esse vencimento, 
					acrescidos de juros de mercado proporcionalmente aos dias de atraso.<o:p></o:p></span></p>

					<p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><span
					style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
					mso-bidi-language:#00FF'>6.5. Este contrato obriga e beneficia as partes signat�rias 
					e seus respectivos sucessores e representantes a qualquer t�tulo. A AUTORIZADA n�o 
					pode transferir ou ceder qualquer dos direitos ou obriga��es aqui estabelecidas sem 
					o pr�vio consentimento por escrito da HBFLEX S.A..<o:p></o:p></span></p>

					<p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><b><span
					style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
					mso-bidi-language:#00FF'>7- FORO<o:p></o:p>
					</span></b></p>

					<p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><span
					style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
					mso-bidi-language:#00FF'>Estando de pleno acordo com todas as cl�usulas e 
					condi��es aqui expostas, elegem as partes contratantes o Foro da Comarca da 
					Cidade de S�o Paulo, para dirimir e resolver toda e qualquer quest�o, proveniente 
					do presente contrato, com expressa renuncia de qualquer outro, por mais 
					privilegiado que seja.<o:p></o:p></span></p>

					<p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><span
					style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
					mso-bidi-language:#00FF'>E, por estarem assim justas e acertadas, firmam o 
					presente instrumento, em duas vias de igual teor e forma, juntamente com as 
					testemunhas abaixo indicadas.<o:p></o:p></span></p>




					<table class=MsoNormalTable border=0 cellspacing=0 cellpadding=0
					 style='margin-left:2.75pt;border-collapse:collapse;mso-padding-alt:2.75pt 2.75pt 2.75pt 2.75pt'>
					 <tr style='mso-yfti-irow:0;mso-yfti-firstrow:yes;mso-yfti-lastrow:yes'>
					  <td width=265 valign=top style='width:198.8pt;padding:2.75pt 2.75pt 2.75pt 2.75pt'>
					  <p class=MsoNormal align=center style='text-align:center;mso-line-height-alt:
					  10.0pt;layout-grid-mode:char'>HB ASSIST�NCIA T�CNICA LTDA.</p>
					  </td>
					  <td width=302 valign=top style='width:226.3pt;padding:2.75pt 2.75pt 2.75pt 2.75pt'>
					  <p class=MsoNormal align=center style='text-align:center;mso-line-height-alt:
					  10.0pt;layout-grid-mode:char'>$posto_nome.</p>
					  </td>
					 </tr>
					</table>

					<p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><o:p>&nbsp;</o:p></p>

					<p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><b
					style='mso-bidi-font-weight:normal'>����� <span style='mso-fareast-font-family:'Lucida Sans Unicode';
					mso-bidi-font-family:Tahoma;mso-fareast-language:#00FF;mso-bidi-language:#00FF'>Testemunhas:<o:p></o:p></span></b></p>

					<br><p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'>��������������� <span
					style='mso-fareast-font-family:'Lucida Sans Unicode';mso-bidi-font-family:Tahoma;
					mso-fareast-language:#00FF;mso-bidi-language:#00FF'>________________________________
					<span style='mso-tab-count:1'>�������������������� </span>_______________________________<o:p></o:p></span></p>

					<p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'>��������������� <span
					style='mso-fareast-font-family:'Lucida Sans Unicode';mso-bidi-font-family:Tahoma;
					mso-fareast-language:#00FF;mso-bidi-language:#00FF'>Nome: <span style='mso-tab-count:
					6'>������������������������������������������������������������������������� </span>Nome:<o:p></o:p></span></p>

					<p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'>��������������� <span
					style='mso-fareast-font-family:'Lucida Sans Unicode';mso-bidi-font-family:Tahoma;
					mso-fareast-language:#00FF;mso-bidi-language:#00FF'>RG: <span style='mso-tab-count:
					6'>������������������������������������������������������������������������������ </span>RG:<o:p></o:p></span></p>

					<p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'>��������������� <span
					style='mso-fareast-font-family:'Lucida Sans Unicode';mso-bidi-font-family:Tahoma;
					mso-fareast-language:#00FF;mso-bidi-language:#00FF'>CPF: <span
					style='mso-tab-count:6'>���������������������������������������������������������������������������� </span>CPF:<o:p></o:p></span></p>

					<br><br><p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'>��������������� <span
					style='mso-fareast-font-family:'Lucida Sans Unicode';mso-bidi-font-family:Tahoma;
					mso-fareast-language:#00FF;mso-bidi-language:#00FF'>CARIMBO CNPJ HBFLEX S.A.: <span
					style='mso-tab-count:6'>������������������������������</span>CARIMBO CNPJ AUTORIZADA:<o:p></o:p></span></p>


					</div>
					";

					echo `mkdir /tmp/hbtech`;
					echo `chmod 777 /tmp/hbtech`;
					echo `rm /tmp/hbtech/contrato_$posto.htm`;
					echo `rm /tmp/hbtech/contrato_$posto.pdf`;
					echo `rm /var/www/assist/www/credenciamento/contrato/contrato_$posto.pdf`;


					if(strlen($msg_erro) == 0){
						$abrir = fopen("/tmp/hbtech/contrato_$posto.htm", "w");
						if (!fwrite($abrir, $conteudo)) {
							$msg_erro = "Erro escrevendo no arquivo ($filename)";
						}
						fclose($abrir); 
					}


					//GERA O PDF
					echo `htmldoc --webpage --no-duplex --no-embedfonts --header ... --permissions no-modify,no-copy --fontsize 8.5 --no-title -f /tmp/hbtech/contrato_$posto.pdf /tmp/hbtech/contrato_$posto.htm`;
					echo `mv  /tmp/hbtech/contrato_$posto.pdf /var/www/assist/www/credenciamento/contrato/contrato_hbtech.pdf`;

					$anexo_nome = "/var/www/assist/www/credenciamento/contrato/contrato_hbtech.pdf";
					$anexo = file_get_contents( $anexo_nome );
					$anexo = chunk_split( base64_encode( $anexo ) );
	//$msg_erro = "$anexo";
					#CABE�ALHO
					if(strlen($img_cabecalho) > 0){
						$corpo = "<img src='imagem_upload/$img_cabecalho'><br>";
					}
					
					#CORPO DA MENSAGEM
					$corpo .= $aux_mens_corpo;
					
					#RODAP�
					if(strlen($img_rodape) > 0){
						$corpo .= "<br><img src='imagem_upload/$img_rodape'>";
					}

					$boundary = "XYZ-" . date("dmYis") . "-ZYX"; 

						$mens = "--$boundary\n";
						$mens .= "Content-Transfer-Encoding: 8bits\n";
						$mens .= "Content-Type: text/html; charset=\"ISO-8859-1\"\n\n"; //plain
						$mens .= "$corpo\n";
						$mens .= "--$boundary\n";
						$mens .= "Content-Type: pdf\n"; 
						$mens .= "Content-Disposition: attachment; filename=\"contrato_hbflex.pdf\"\n"; 
						$mens .= "Content-Transfer-Encoding: base64\n\n"; 
						$mens .= "$anexo\n"; 
						$mens .= "--$boundary--\r\n"; 

					$headers  = "MIME-Version: 1.0\n"; 
					$headers .= "From: $email_origem\r\n"; 
					$headers .= "Content-type: multipart/mixed; boundary=\"$boundary\"\r\n"; 
					$headers .= "$boundary\n";
							  

					if(mail($email, utf8_encode($assunto), utf8_encode($mens), $headers)){
						$msg_ok = "Mensagem enviada corretamente!";
						#limpa os campos
						array_push($email_enviado, $email);

						$sql2 = "UPDATE tbl_posto_fabrica set contrato = current_timestamp where posto = $posto and fabrica = $login_fabrica;";
						$res2 = @pg_exec($con,$sql2);
						$msg_erro = pg_errormessage($con);
					}else{
						$email_nao_enviado = array_push($email);
					}
				}else{
					array_push($email_nao_enviado, $email);
				}
			}
		}
	}
}

$visual_black = "manutencao-admin";
$layout_menu = "gerencia";
$title = "ENVIO DE E-MAIL";
include 'cabecalho.php';

?>

<script language="Javascript1.2">

<!-- 

	_editor_url = "../helpdesk/editor/";

	var win_ie_ver = parseFloat(navigator.appVersion.split("MSIE")[1]);

	if (navigator.userAgent.indexOf('Mac')        >= 0)
		win_ie_ver = 0;

	if (navigator.userAgent.indexOf('Windows CE') >= 0)
		win_ie_ver = 0;

	if (navigator.userAgent.indexOf('Opera')      >= 0)
		win_ie_ver = 0;

	if (win_ie_ver >= 5.5) {
		 document.write('<scr' + 'ipt src="' +_editor_url+ 'editor.php"');
		 document.write(' language="Javascript1.2"></scr' + 'ipt>');  
	} 
	else
	{ 
		document.write('<scr'+'ipt>function editor_generate() { return false; }</scr'+'ipt>');
	}

// -->

</script> 


<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	border: 1px solid;
	color:#596d9b;
	background-color: #d9e2ef
}

.border {
	border: 1px solid #ced7e7;
}

.table_line {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #ffffff
}

input {
	font-size: 10px;
}

.top_list {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color:#596d9b;
	background-color: #d9e2ef
}

.line_list {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: normal;
	color:#596d9b;
	background-color: #ffffff
}

.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}


.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}



.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}

.subtitulo{

	background-color: #7092BE;
	font:bold 11px Arial;
	color: #FFFFFF;
	text-align:center;
}

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}

.texto_avulso{
	font: 14px Arial; color: rgb(89, 109, 155);
	background-color: #d9e2ef;
	text-align: center;
	width:700px;
	margin: 0 auto;
}
</style>

<? if(strlen($msg_erro) > 0){?>

<table width='700px' align='center' border='0' bgcolor='#FFFFFF' cellspacing="1" cellpadding="0">
<tr class="msg_erro">
	<td>
		<? echo $msg_erro; ?>
	</td>
</tr>
</table>
<?	} ?> 

<? if($login_fabrica == 25) {?>
<table width='700px' align='center' border='0' bgcolor='#FFFFFF' cellspacing="1" cellpadding="0">
<tr align='center'>
	<td>
		<br><p style='font-size: 10px; color: #5C5C5C'><b>*Quando tiver mais de um destinat�rio, estes dever�o ser separados por ";". <br>Ex.: helpdesk@telecontrol.com.br; suporte2@telecontrol.com.br;</b></p>
		<p style='font-size: 10px; color: #5C5C5C'><b>*CUIDADO: Se escolher a linha ser� enviado e-mail para todos os postos cadastrados na linha escolhida.</b></p>
	</td>
</tr>
</table>
<? } ?>

<? 
	if(strlen($msg_ok) == 0){
?>
<p>
<FORM enctype = "multipart/form-data" NAME = "frm_email" METHOD = "post" ACTION = "<? echo $PHP_SELF; ?>">
<INPUT TYPE="hidden" name="email_fabrica" value="<? echo $email_fabrica; ?>">

<center>
<TABLE width='700' align='center' border='0' cellspacing = '1' cellpadding='0' class="formulario">
<TR class="titulo_tabela"><td>Preencha os Dados para o Envio</td></tr>
<tr><td>
<TABLE width='100%' align='center' border='0' cellspacing = '2' cellpadding='3' class="formulario">
<TR>
	<TD width="100">&nbsp;</TD>
	<TD>Linha</TD>
	<TD colspan="2" >E-mail Remetente</TD>
</TR>
<TR>
	<TD width="100">&nbsp;</TD>
	<TD>
	<?
		$sql = "SELECT * 
				FROM tbl_linha 
				WHERE fabrica = $login_fabrica";
		$res = pg_exec ($con,$sql);

		if (pg_numrows ($res) > 0) {
			echo "<select class='frm' name='linha' size='1' class='frm'>";
			echo "<option value=''>NENHUMA</option>";
			
			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
				echo "<option value='" . pg_result ($res,$i,linha) . "' ";
				if ($linha == pg_result ($res,$i,linha) ) echo " selected ";
				echo ">";
				echo pg_result ($res,$i,nome);
				echo "</option>";
			}
			echo "</select>";
		}
		?>
	</TD>
	<TD >
	<INPUT type="text" name="email_remetente" size="35" value="<? echo $email_remetente ?>" class="frm">
	</TD>
</TR>

<TR >
	<TD width="100">&nbsp;</TD>
	<TD colspan="2" >
		E-mail Destinat�rio
	</TD>
</TR>
<TR >
	<TD width="100">&nbsp;</TD>
	<TD colspan="2"  >
		<TEXTAREA NAME="email_destinatario" ROWS="5" COLS="70" class='frm'><? echo $email_destinatario ?></TEXTAREA>
	</TD>
</TR>

<TR >
	<TD width="100">&nbsp;</TD>
	<TD colspan="2">
		Assunto
	</TD>
</TR>
<TR>
	<TD width="100">&nbsp;</TD>
	<TD colspan="2"  >
		<INPUT type="text" name="assunto" size="70" value="<? echo $assunto ?>" class="frm">
	</TD>
</TR>
<TR>
	<TD width="100">&nbsp;</TD>
	<TD colspan="2" >
		Mensagem
	</TD>
	</TR>
<TR>
	<TD width="100">&nbsp;</TD>
	<TD colspan='2'>
		<TEXTAREA NAME="mens_corpo" ROWS="7" COLS="70" value = "<? echo $mens_corpo ?>" class="frm" ></TEXTAREA>
		<script language="JavaScript1.2">editor_generate('mens_corpo');</script>
	</TD>
</TR>
<TR>
	<TD width="100">&nbsp;</TD>
	<TD width="100" colspan="2">Cabe�alho</TD>
	
</TR>
<TR>
	<TD width="100">&nbsp;</TD>
	<TD colspan="2"><input type='file' name='arquivo1' size='50' class="frm"></TD>
</TR>
<TR>
	<TD width="100">&nbsp;</TD>
	<TD width="100" colspan="2">Rodap�</TD> 
</TR>
<TR>
	<TD width="100">&nbsp;</TD>
	<TD colspan="2"><input type='file' name='arquivo2' size='50' class="frm"></TD>
</TR>

<TR>
	<TD colspan='3'>&nbsp;</TD> 
</TR>
<?if($login_fabrica == 25){?>
	<TR>
		<TD width="100">&nbsp;</TD>
		<TD colspan='2' align='left'>&nbsp;&nbsp;&nbsp;<INPUT TYPE="checkbox" NAME="contrato" value='contrato'><b>Enviar Contrato</b></TD>
	</TR>

	<TR>
		<TD width="100">&nbsp;</TD>
		<TD colspan='2'>*S� ser� enviado o contrato se o PA estiver cadastrado no sistema. A verifica��o � feita atrav�s do e-mail.</TD> 
	</TR>

	<TR>
		<TD colspan='3'>&nbsp;</TD> 
	</TR>
<?}?>
<TR>
	<TD colspan="3" align="center">
		<input type='hidden' name='btn_acao' value=''>
		<input type="button" style='background:url(imagens_admin/btn_enviar.gif); width:75px; cursor:pointer;' value="&nbsp;" onclick="javascript: if (document.frm_email.btn_acao.value == '' ) { document.frm_email.btn_acao.value='confirmar' ; document.frm_email.submit() } else { alert ('Aguarde submiss�o') }" ALT="Confirmar formul�rio" border='0'  onclick="javascript: document.frm_email.btn_acao.value='confirmar' ; document.frm_email.submit() ;" >
		<input type="button" style="background:url(imagens_admin/btn_limpar.gif); width:75px; cursor:pointer;" value="&nbsp;" onclick="javascript: if (document.frm_email.btn_acao.value == '' ) { document.frm_email.btn_acao.value='reset' ; window.location='<? echo $PHP_SELF ?>' } else { alert ('Aguarde submiss�o') }" ALT="Limpar campos" border='0' >
	</TD>
</TR>
</TABLE>
</td></tr>
</table>

</FORM>
</center>

<?

}else{
	if(strlen($contrato) > 0){
		echo "<br><br>";
		echo "<font size='2'><b>Contratos enviados para: <br></b></font>";
		for($i=0;$i<count($email_enviado);$i++){
			echo "$email_enviado[$i];<br>";
		}
		echo "<br><br>";
		echo "<font size='2'><b>Contratos N�O enviados para: <br></b></font>";
		for($i=0;$i<count($email_nao_enviado);$i++){
			echo "$email_nao_enviado[$i];<br>";
		}
	}else{
		echo "<br><br>";
		echo "<font size='2'><b>$msg_ok</b></font>";
	}
	if($login_fabrica == 25 OR $login_fabrica == 10 OR $login_fabrica == 51){
		echo "<a href='envio_email_new.php'>";
	}else{
		echo "<a href='envio_email.php'>";
	}
	echo "<br><br>";
	echo "<br><br>";
	echo "<img src='imagens/btn_voltar.gif'></a>";
	echo "<br><br>";
	echo "<br><br>";
}
?>

<?
	include "rodape.php";
?>
