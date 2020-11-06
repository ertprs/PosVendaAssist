<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="cadastros";
include 'autentica_admin.php';
$title = "Comunicados por E-mail";
$layout_menu = "cadastro";
include 'cabecalho.php';

$btnacao = $_POST['btnacao'];
if(strlen($btnacao)>0){

	$assunto    = $_POST['assunto'];
	$remetente  = $_POST['remetente'];
	$mensagem   = $_POST['mensagem'];
	$comunicado = trim($_POST["comunicado"]);
	
	$suframa    = $_POST['suframa'];
	$faturada   = $_POST['pede_peca_faturada'];
	$garantia   = $_POST['pede_peca_garantia'];



	if(strlen($assunto)==0){
	$erro = "Por favor informe o Assunto";
	}

	if(strlen($remetente)==0){
	$erro = "Por favor informe o Remetente";
	}

	if(strlen($mensagem)==0){
	$erro = "Por favor informe a Mensagem";
	}
	if($faturada=="todos"){
	$faturada="null";
	}else{
		$faturada="'$faturada'";
	}

	if($garantia=="todos"){
	$garantia="null";
	}else{
		$garantia="'$garantia'";
	}

	if($suframa=="todos"){
	$suframa="null";
	}else{
		$suframa="'$suframa'";
	}

//echo "su:$suframa ga: $garantia fa: $faturada"; exit;
	$destinatario_especifico = trim($_POST['destinatario_especifico']);
	if (strlen($destinatario_especifico) == 0) {
			$aux_destinatario_especifico = "null";
	}else{
		$aux_destinatario_especifico  = trim($destinatario_especifico);
		$aux_destinatario_especifico  = str_replace("\r\n\r\n", ";", $aux_destinatario_especifico);
		$aux_destinatario_especifico  = str_replace("\r\n", ";", $aux_destinatario_especifico);
		$negados                      = explode (";" , $aux_destinatario_especifico);
		$qtde_produto                 = array_count_values ($negados);
		$negados_1                    = array_unique($negados);

		for ($k=0; $k < count($negados); $k++) {
			$negados [$k] = trim ($negados_1 [$k]);
			$posto = $negados [$k];

			if (strlen($posto) > 0) {
				// if ($k == 0) {
				// 	$codigo      .= "(";
				// 	$codigo_mail .= "(";
				// }
				$codigo      .= $posto;
				$codigo_mail .= "'". $posto ."'";

				$k1 = $k + 1;

				if (strlen($negados_1 [$k+1]) > 0) {
					$codigo      .= ",";
					$codigo_mail .= ",";
				}else{
					if ($k1 < count($negados)){
						$codigo      .= ",";
						$codigo_mail .= ",";
					}//else{
					// 	$codigo      .= ")";
					// 	$codigo_mail .= ")";
					// }
				}
			}
		}
		$aux_destinatario_especifico      = "'". $codigo ."'";
		$aux_destinatario_especifico_mail = $codigo_mail;
		
	}
	
/*	
echo "assunto: $assunto<BR>msg $mensagem<BR>remetente $remetente<Br>suframa $suframa<BR>faturada $faturada<BR>garantia $garantia<BR>" ;*/
//insere na tabela
  
 
$resx = pg_query($con,"BEGIN TRANSACTION");

if (strlen($erro) == 0) {
## INCLUSÃO DE LANÇAMENTO
			$sql = "INSERT INTO tbl_comunicado (
						fabrica                          ,
						descricao                        ,
						remetente_email                  ,
						destinatario_especifico          ,
						pedido_em_garantia               ,
						pedido_faturado                  ,
						suframa                          ,
						mensagem
					) VALUES (
						$login_fabrica                   ,
						'$assunto'                       ,
						'$remetente'                     ,
						$aux_destinatario_especifico     ,
						$garantia                        ,
						$faturada                        ,
						$suframa                         ,
						'$mensagem'
					);";
			$res = pg_query($con,$sql);
			// echo "$sql"; //exit;
			if (strlen ( pg_last_error($con) ) > 0) {
				$erro .= pg_last_error($con) ;
			}

			if (strlen($erro) == 0) {

				if (strlen($comunicado) == 0) {
## PEGA SEQUÊNCIA DA TABELA DE COMUNICADO
					$res1       = pg_query($con,"SELECT currval ('seq_comunicado')");
					$comunicado = pg_fetch_result($res1,0,0);

					if (strlen ( pg_last_error($con) ) > 0) {
						$erro .= pg_last_error($con) ;
					}
				}
			}
	}
  
 
//fim insere na tabela 

	//PEGA ANEXO
	if (strlen($erro) == 0) {
		///////////////////////////////////////////////////
		// Rotina que faz o upload do arquivo
		///////////////////////////////////////////////////
		// Prepara a variável do arquivo
		$arquivo = (strlen($_FILES["anexo"]["name"]) > 0) ? $_FILES["anexo"] : '';

		if (is_array($arquivo)) {

			include __DIR__ . "/plugins/fileuploader/TdocsMirror.php";

			$dataAtual = date('Y-m-d');

			$s3Tdocs = new TdocsMirror();
			$tdocsId = $s3Tdocs->post($arquivo['tmp_name']);
			$tdocsId = array_values($tdocsId[0]);
        	$tdocsId = $tdocsId[0]['unique_id'];

            try {
				$sql_anexo = "INSERT INTO tbl_tdocs(tdocs_id, fabrica, contexto, situacao, referencia, referencia_id) 
	                    VALUES('$tdocsId', $login_fabrica, 'blackedecker', 'ativo', 'comunicado_blackedecker', $comunicado) RETURNING tdocs_id";
				$res_anexo = pg_query($con, $sql_anexo);

				$link    = $s3Tdocs->get($tdocsId);
				$link = $link["link"];    


				if (strlen(pg_last_error()) == 0) {
					$erro = pg_last_error();
	        	} 
			} catch (Exception $e) {
				if (preg_match("/\\u/", $e->getMessage())) {
					$erro = utf8_decode($e->getMessage());
				} else {
					$erro = $e->getMessage();
				}
			}
			// include_once S3CLASS;

			// $s3 = new anexaS3('co', (int) $login_fabrica);

			// if (!$s3->uploadFileS3($comunicado, $arquivo)) {
			// 	$msg_erro = $s3->_erro;
			// } else {
				
			// 	$attach_filename = basename($s3->attachList[0]);
			// 	$ext = pathinfo($attach_filename, PATHINFO_EXTENSION);

			// 	if (($ext == '') and (strlen($attach_filename) == 0)) {
			// 		$attach = $_FILES["anexo"]['tmp_name'];
			// 		$encoded_attach  = chunk_split(base64_encode(file_get_contents($attach)));
			// 		$attach_size     = filesize($attach);
			// 		$attach_type     = mime_content_type($attach);
			// 		$attach_filename = $_FILES['anexo']['name'];
			// 	}

			// 	$sql =	"UPDATE tbl_comunicado
			// 				SET extensao   = '$ext'
			// 			  WHERE comunicado = $comunicado
			// 				AND fabrica    = $login_fabrica";
			// 	$res = @pg_query($con,$sql);
			// 	$msg_erro = pg_last_error($con);

			// 	if (strlen ( pg_last_error($con) ) > 0) {
			// 		$erro .= pg_last_error($con) ;
			// 	}
			// }
			if(strlen($link) > 0){
				$anexo = "<br><br><br><a href=".$link." target='_black'>Clique para baixar o anexo</a>";
			}
		}
	}//FIM PEGA ANEXO

  
	if (strlen($erro) == 0) {
		$resx = pg_query($con,"COMMIT TRANSACTION");
	}else{
		if($s3->attachList[0] !== null){
			if (!$s3->excluiArquivoS3($s3->attachList[0])){
				$msg_erro = $s3->_erro;
			}
			$resx = pg_query($con,"ROLLBACK TRANSACTION");
			$erro  = "<b>Foi detectado o seguinte erro: </b><br>";
		}
	}
 
if (strlen($erro) == 0) {
//  echo "mail $aux_destinatario_especifico_mail<BR>";
//  echo "assunto: $assunto<BR>remetente: $remetente<BR> msg: $mensagem<BR>anexo $nome_foto";
##############COMECA A ENVIAR EMAIL A PARTIR DAQUI###################
##############todos emails vao com copia para MI
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
	$corpo_email .= nl2br($mensagem) ."\n" . $anexo;
	$corpo_email .= "</font>\n";
	$corpo_email .= "</td>\n";

	$corpo_email .= "</tr>\n";
	$corpo_email .= "</table>\n"; 
 
	$subject    = $assunto;

	$from_nome  = "Black & Decker Cópia";
	$from_email = "$remetente";

	$to_nome  = "Cópia Comunicado";
// 	$to_email = "takashi@telecontrol.com.br";
	$to_email = "MiPereira@blackedecker.com.br";
// echo "<BR>copia para $to_email<BR>";
	$cabecalho  = "From: $from_nome < $from_email >\n";
	$cabecalho .= "To: $to_nome < $to_email >\n";
	$cabecalho .= "Return-Path: < $from_email >\n";
	$cabecalho .= "MIME-version: 1.0\n";

	if ($ext != '' ) {
		$cabecalho .= "Content-type: multipart/mixed; ";
		$cabecalho .= "boundary=\"Message-Boundary\"\n";
		$cabecalho .= "Content-transfer-encoding: 7BIT\n";
		$cabecalho .= "X-attachments: $attach_filename";

		$body_top  = "--Message-Boundary\n";
		$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
		$body_top .= "Content-transfer-encoding: 7BIT\n";
		$body_top .= "Content-description: Mail message body\n\n";

		$corpo_email = $body_top . $corpo_email;

		$corpo_email .= "\n\n--Message-Boundary\n";
		$corpo_email .= "Content-type: $attach_type; name=\"$attach_filename\"\n";
		$corpo_email .= "Content-Transfer-Encoding: BASE64\n";
		$corpo_email .= "Content-disposition: attachment; filename=\"$attach_filename\"\n\n";
		$corpo_email .= "$encoded_attach\n";
		$corpo_email .= "--Message-Boundary--\n";
	}else{
		$cabecalho .= "Content-type: text/html; charset=iso-8859-1\n";
	}
	mail ("" , stripslashes(utf8_encode($subject)), utf8_encode("$corpo_email") , "$cabecalho");
//FIM ENVIO DE EMAIL PRA MI
##############FIM  todos emails vao com copia para MI
 
###########mandar para email interno 0 a 15
	$qtde_interno=$_POST['qtde_interno'];
	for($z=0; $z<$qtde_interno; $z++){
		$email_interno = trim($_POST["email_interno_".$z]);
		$nome_interno  = trim($_POST["nome_interno_".$z]);
		if(strlen($email_interno)>0){
// 	echo "$z - $email_interno - $nome_interno<BR>";
//ROTINA DE ENVIO DE EMAIL
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
			$corpo_email .= "Remetente:  ".$remetente. "<br>    <b>Prezado(a) $nome_interno,</b>\n";
			$corpo_email .= "</font>\n";
			$corpo_email .= "</td>\n";
		
			$corpo_email .= "</tr>\n";
			$corpo_email .= "<tr>\n";
		
			$corpo_email .= "<td width='100%' align='left' bgcolor='#FFFFFF'>\n";
			$corpo_email .= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>\n";
			$corpo_email .= nl2br($mensagem) ."\n" . $anexo;
			$corpo_email .= "</font>\n";
			$corpo_email .= "</td>\n";
		
			$corpo_email .= "</tr>\n";
			$corpo_email .= "</table>\n"; 
 
			$subject    = $assunto;
		
			$from_nome  = "Black & Decker";
			$from_email = "$remetente";
		
			$to_nome  = "$nome_interno";
			$to_email = "$email_interno";
		
			$cabecalho  = "From: $from_nome < $from_email >\n";
			$cabecalho .= "To: $to_nome < $to_email >\n";
			$cabecalho .= "Return-Path: < $from_email >\n";
			$cabecalho .= "MIME-version: 1.0\n";

			if ($ext != '' ) {
				$cabecalho .= "Content-type: multipart/mixed; ";
				$cabecalho .= "boundary=\"Message-Boundary\"\n";
				$cabecalho .= "Content-transfer-encoding: 7BIT\n";
				$cabecalho .= "X-attachments: $attach_filename";
		
				$body_top  = "--Message-Boundary\n";
				$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
				$body_top .= "Content-transfer-encoding: 7BIT\n";
				$body_top .= "Content-description: Mail message body\n\n";
		
				$corpo_email = $body_top . $corpo_email;
		
				$corpo_email .= "\n\n--Message-Boundary\n";
				$corpo_email .= "Content-type: $attach_type; name=\"$attach_filename\"\n";
				$corpo_email .= "Content-Transfer-Encoding: BASE64\n";
				$corpo_email .= "Content-disposition: attachment; filename=\"$attach_filename\"\n\n";
				$corpo_email .= "$encoded_attach\n";
				$corpo_email .= "--Message-Boundary--\n";
		
			}else{
				$cabecalho .= "Content-type: text/html; charset=iso-8859-1\n";
		 
			}
			mail ("" , stripslashes(utf8_encode($subject)), utf8_encode("$corpo_email") , "$cabecalho");
//FIM ROTINA DE ENVIO DE EMAIL INTERNO
//FIM ROTINA DE ENVIO DE EMAIL
 		}//email interno
	}//for
###########FIM mandar para email interno 0 a 15


			  
			 
###########envio por tipo
###########envio por tipo
	$destinatarios = $_POST['destinatarios'];
	for($x=0; $x<$destinatarios; $x++){
// 		destinarios por regiao
// 		qtde em $destinatarios
		$destinatario = $_POST["destinatario_".$x];
		if(strlen($destinatario)>0){
//			ROTINA DE ENVIO DE EMAIL
//			pega posto por tipo e envia
//			echo "$x : $destinatario<BR>";
			$sql="SELECT 
						tbl_posto.posto, 
						tbl_posto.nome, 
						tbl_posto_fabrica.contato_email, 
						tbl_posto_fabrica.pedido_em_garantia, 
						tbl_posto_fabrica.pedido_faturado, 
						tbl_posto.suframa, 
						tbl_posto_fabrica.tipo_posto 
				FROM tbl_posto 
				JOIN tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto 
				AND tbl_posto_fabrica.fabrica = $login_fabrica 
				AND tbl_posto_fabrica.tipo_posto= $destinatario 
				AND tbl_posto_fabrica.credenciamento <> 'DESCREDENCIADO'
				WHERE tbl_posto_fabrica.contato_email notnull 
				AND length(tbl_posto_fabrica.contato_email) > 0";
				if($garantia<>"null"){ $sql .=" AND tbl_posto_fabrica.pedido_em_garantia = $garantia"; }
				if($faturada<>"null"){ $sql .=" AND tbl_posto_fabrica.pedido_faturado = $faturada"; }
				if($suframa<>"null"){ $sql .=" AND tbl_posto.suframa = $suframa"; }
				
			$resx = pg_query($con,$sql);
//echo "tipo ------------<BR>$sql";
			for ( $i = 0 ; $i < pg_num_rows($resx) ; $i++ ) {
				$posto       = trim(pg_fetch_result($resx,$i,posto));
				$posto_nome  = trim(pg_fetch_result($resx,$i,nome));
				$posto_email = trim(strtolower(pg_fetch_result($resx,$i,contato_email)));
//echo "$posto - $posto_nome - $posto_email<BR>";
//ROTINA DE ENVIO DE EMAIL
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
				$corpo_email .= "Remetente:  ".$remetente. "<br>    <b>Prezado(a) $posto_nome,</b>\n";
				$corpo_email .= "</font>\n";
				$corpo_email .= "</td>\n";
			
				$corpo_email .= "</tr>\n";
				$corpo_email .= "<tr>\n";
			
				$corpo_email .= "<td width='100%' align='left' bgcolor='#FFFFFF'>\n";
				$corpo_email .= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>\n";
				$corpo_email .= nl2br($mensagem) ."\n" . $anexo;
				$corpo_email .= "</font>\n";
				$corpo_email .= "</td>\n";
			
				$corpo_email .= "</tr>\n";
				$corpo_email .= "</table>\n"; 
			
				$subject    = $assunto;
			
				$from_nome  = "Black & Decker";
				$from_email = "$remetente";
			
				$to_nome  = "$posto_nome";
				$to_email = "$posto_email";
			
				$cabecalho  = "From: $from_nome < $from_email >\n";
				$cabecalho .= "To: $to_nome < $to_email >\n";
				$cabecalho .= "Return-Path: < $from_email >\n";
				$cabecalho .= "MIME-version: 1.0\n";
			
				if (($ext == '') and (strlen($attach_filename) == 0)) {
					$cabecalho .= "Content-type: multipart/mixed; ";
					$cabecalho .= "boundary=\"Message-Boundary\"\n";
					$cabecalho .= "Content-transfer-encoding: 7BIT\n";
					$cabecalho .= "X-attachments: $attach_filename";
			
					$body_top  = "--Message-Boundary\n";
					$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
					$body_top .= "Content-transfer-encoding: 7BIT\n";
					$body_top .= "Content-description: Mail message body\n\n";
			
					$corpo_email = $body_top . $corpo_email;
			
					$corpo_email .= "\n\n--Message-Boundary\n";
					$corpo_email .= "Content-type: $attach_type; name=\"$attach_filename\"\n";
					$corpo_email .= "Content-Transfer-Encoding: BASE64\n";
					$corpo_email .= "Content-disposition: attachment; filename=\"$attach_filename\"\n\n";
					$corpo_email .= "$encoded_attach\n";
					$corpo_email .= "--Message-Boundary--\n";
				}else{
					$cabecalho .= "Content-type: text/html; charset=iso-8859-1\n";
				}
				mail ("" , stripslashes(utf8_encode($subject)), utf8_encode("$corpo_email") , "$cabecalho");
			//FIM ROTINA DE ENVIO DE EMAIL INTERNO
			//FIM ROTINA DE ENVIO DE EMAIL 
			}//fim do for que envia
		}//fim destinatario>0
	}//fim for de destinatarios
###########FIM envio por tipo
###########FIM envio por tipo

 
###########mandar email por regiao
###########1-6 
	$qtde_regiao=7;
	for($y=1; $y<$qtde_regiao; $y++){
		$regiao = trim($_POST["regiao_".$y]);
		if(strlen($regiao)>0){
			
		//ROTINA DE ENVIO DE EMAIL
		//ROTINA DE ENVIO DE EMAIL
		//fazer select de toda regiao e enviar
// 				echo "$y : $regiao<BR>";
			if($regiao<>"MG"){
				$sql="SELECT 
						tbl_posto.posto, 
						tbl_posto.nome, 
						tbl_posto.estado,
						tbl_estado.regiao,  
						tbl_posto.email 
					FROM tbl_posto 
					JOIN tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto 
					AND tbl_posto_fabrica.fabrica = $login_fabrica 
					JOIN tbl_estado on tbl_posto.estado = tbl_estado.estado 
					AND tbl_estado.regiao = '$regiao'
					AND tbl_posto_fabrica.credenciamento <> 'DESCREDENCIADO'";
					if($garantia<>"null"){ $sql .=" AND tbl_posto_fabrica.pedido_em_garantia = $garantia"; }
					if($faturada<>"null"){ $sql .=" AND tbl_posto_fabrica.pedido_faturado = $faturada"; }
					if($suframa<>"null"){ $sql .=" AND tbl_posto.suframa = $suframa"; }
					$resx = pg_query($con,$sql);
  //echo "REGIAO---------<br>$sql";
				for ( $i = 0 ; $i < pg_num_rows($resx) ; $i++ ) {
					$posto        = trim(pg_fetch_result($resx,$i,posto));
					$posto_nome   = trim(pg_fetch_result($resx,$i,nome));
					$posto_estado = trim(pg_fetch_result($resx,$i,estado));
					$posto_regiao = trim(pg_fetch_result($resx,$i,regiao));
					$posto_email  = trim(strtolower(pg_fetch_result($resx,$i,email)));
// 	echo "$posto - $posto_nome - $posto_email - $posto_estado - $posto_regiao <BR>";
//ROTINA DE ENVIO DE EMAIL
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
					$corpo_email .= "Remetente:  ".$remetente. "<br>    <b>Prezado(a) $posto_nome,</b>\n";
					$corpo_email .= "</font>\n";
					$corpo_email .= "</td>\n";
				
					$corpo_email .= "</tr>\n";
					$corpo_email .= "<tr>\n";
				
					$corpo_email .= "<td width='100%' align='left' bgcolor='#FFFFFF'>\n";
					$corpo_email .= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>\n";
					$corpo_email .= nl2br($mensagem) ."\n" . $anexo;
					$corpo_email .= "</font>\n";
					$corpo_email .= "</td>\n";
				
					$corpo_email .= "</tr>\n";
					$corpo_email .= "</table>\n"; 
				
					$subject    = $assunto;
				
					$from_nome  = "Black & Decker";
					$from_email = "$remetente";
				
					$to_nome  = "$posto_nome";
					$to_email = "$posto_email";
				
					$cabecalho  = "From: $from_nome < $from_email >\n";
					$cabecalho .= "To: $to_nome < $to_email >\n";
					$cabecalho .= "Return-Path: < $from_email >\n";
					$cabecalho .= "MIME-version: 1.0\n";
				
					if (($ext == '') and (strlen($attach_filename) == 0)) {
						$cabecalho .= "Content-type: multipart/mixed; ";
						$cabecalho .= "boundary=\"Message-Boundary\"\n";
						$cabecalho .= "Content-transfer-encoding: 7BIT\n";
						$cabecalho .= "X-attachments: $attach_filename";
				
						$body_top  = "--Message-Boundary\n";
						$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
						$body_top .= "Content-transfer-encoding: 7BIT\n";
						$body_top .= "Content-description: Mail message body\n\n";
				
						$corpo_email = $body_top . $corpo_email;
				
						$corpo_email .= "\n\n--Message-Boundary\n";
						$corpo_email .= "Content-type: $attach_type; name=\"$attach_filename\"\n";
						$corpo_email .= "Content-Transfer-Encoding: BASE64\n";
						$corpo_email .= "Content-disposition: attachment; filename=\"$attach_filename\"\n\n";
						$corpo_email .= "$encoded_attach\n";
						$corpo_email .= "--Message-Boundary--\n";

					}else{
						$cabecalho .= "Content-type: text/html; charset=iso-8859-1\n";
						// 		echo "2";
					}
					mail ("" , stripslashes(utf8_encode($subject)), utf8_encode("$corpo_email") , "$cabecalho");
						//FIM ROTINA DE ENVIO DE EMAIL
						//FIM ROTINA DE ENVIO DE EMAIL 
			 	}//FIM DO FOR QUE ENVIA PARA REGIOES SELECIONADAS DIFERENTE DE MG
			}else{//ENVIA PARA POSTOS DE MG
				$sql="SELECT 
						tbl_posto.posto, 
						tbl_posto.nome, 
						tbl_posto.estado,
						tbl_estado.regiao,  
						tbl_posto.email 
					FROM tbl_posto 
					JOIN tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto 
					AND tbl_posto_fabrica.fabrica = $login_fabrica 
					JOIN tbl_estado on tbl_posto.estado = tbl_estado.estado 
					AND tbl_estado.estado = '$regiao'
					AND tbl_posto_fabrica.credenciamento <> 'DESCREDENCIADO'";
				if($garantia<>"null"){ $sql .=" AND tbl_posto_fabrica.pedido_em_garantia = $garantia"; }
				if($faturada<>"null"){ $sql .=" AND tbl_posto_fabrica.pedido_faturado = $faturada"; }
				if($suframa<>"null"){ $sql .=" AND tbl_posto.suframa = $suframa"; }
				$resx = pg_query($con,$sql);
  				//	echo "<bR><br><br>------------------------MG -----------------";
				for ( $i = 0 ; $i < pg_num_rows($resx) ; $i++ ) {
					$posto        = trim(pg_fetch_result($resx,$i,posto));
					$posto_nome   = trim(pg_fetch_result($resx,$i,nome));
					$posto_estado = trim(pg_fetch_result($resx,$i,estado));
					$posto_regiao = trim(pg_fetch_result($resx,$i,regiao));
					$posto_email  = trim(strtolower(pg_fetch_result($resx,$i,email)));
// echo "$posto - $posto_nome - $posto_email - $posto_estado - $posto_regiao <BR>";
//ROTINA DE ENVIO DE EMAIL
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
					$corpo_email .= "Remetente:  ".$remetente. "<br>    <b>Prezado(a) $posto_nome,</b>\n";
					$corpo_email .= "</font>\n";
					$corpo_email .= "</td>\n";
				
					$corpo_email .= "</tr>\n";
					$corpo_email .= "<tr>\n";
				
					$corpo_email .= "<td width='100%' align='left' bgcolor='#FFFFFF'>\n";
					$corpo_email .= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>\n";
					$corpo_email .= nl2br($mensagem) ."\n" . $anexo;
					$corpo_email .= "</font>\n";
					$corpo_email .= "</td>\n";
				
					$corpo_email .= "</tr>\n";
					$corpo_email .= "</table>\n"; 
						
					$subject    = $assunto;
				
					$from_nome  = "Black & Decker";
					$from_email = "$remetente";
				
					$to_nome  = "$posto_nome";
					$to_email = "$posto_email";
				
					$cabecalho  = "From: $from_nome < $from_email >\n";
					$cabecalho .= "To: $to_nome < $to_email >\n";
					$cabecalho .= "Return-Path: < $from_email >\n";
					$cabecalho .= "MIME-version: 1.0\n";

					if (($ext == '') and (strlen($attach_filename) == 0)) {
						$cabecalho .= "Content-type: multipart/mixed; ";
						$cabecalho .= "boundary=\"Message-Boundary\"\n";
						$cabecalho .= "Content-transfer-encoding: 7BIT\n";
						$cabecalho .= "X-attachments: $attach_filename";
				
						$body_top  = "--Message-Boundary\n";
						$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
						$body_top .= "Content-transfer-encoding: 7BIT\n";
						$body_top .= "Content-description: Mail message body\n\n";
				
						$corpo_email = $body_top . $corpo_email;
				
						$corpo_email .= "\n\n--Message-Boundary\n";
						$corpo_email .= "Content-type: $attach_type; name=\"$attach_filename\"\n";
						$corpo_email .= "Content-Transfer-Encoding: BASE64\n";
						$corpo_email .= "Content-disposition: attachment; filename=\"$attach_filename\"\n\n";
						$corpo_email .= "$encoded_attach\n";
						$corpo_email .= "--Message-Boundary--\n";
					}else{	
						$cabecalho .= "Content-type: text/html; charset=iso-8859-1\n";
					}
					mail ("" , stripslashes(utf8_encode($subject)), utf8_encode("$corpo_email") , "$cabecalho");
						//FIM ROTINA DE ENVIO DE EMAIL
						//FIM ROTINA DE ENVIO DE EMAIL 
				}//FIM DO FOR QUE ENVIA PARA POSTOS DE MG
			}//FIM DO ELSE QUE VERIFICA SE É MG
				
		}//FIM DO VERIFICA SE TEM REGIAO SELECIONADA
	}//FIM DO FOR QUE PEGA AS REGIOES
###########mandar email por regiao

##envio por codigo especifico
	if(strlen($aux_destinatario_especifico_mail)>0){
		$sql = "SELECT
					tbl_posto.posto,
					tbl_posto.nome, 
					tbl_posto_fabrica.contato_email AS email 
				FROM tbl_posto  
				JOIN tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto 
				AND tbl_posto_fabrica.fabrica = $login_fabrica 
				WHERE codigo_posto like $aux_destinatario_especifico_mail 
				AND tbl_posto.email notnull 
				AND tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
				AND length(tbl_posto.email) > 0";
				if($garantia<>"null"){ $sql .=" AND tbl_posto_fabrica.pedido_em_garantia = $garantia"; }
				if($faturada<>"null"){ $sql .=" AND tbl_posto_fabrica.pedido_faturado = $faturada"; }
				if($suframa<>"null"){ $sql .=" AND tbl_posto.suframa = $suframa"; }
		$resx = pg_query($con,$sql);
 		//echo "ESPECIFICO ==========<br>$sql";
// 		echo "<BR>por posto<BR>";
		for ( $i = 0 ; $i < pg_num_rows($resx) ; $i++ ) {
			$posto       = trim(pg_fetch_result($resx,$i,posto));
			$posto_nome  = trim(pg_fetch_result($resx,$i,nome));
			$posto_email = trim(strtolower(pg_fetch_result($resx,$i,email)));
// 							echo "$posto - $posto_nome - $posto_email<BR>";
			 				//ROTINA DE ENVIO DE EMAIL
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
			$corpo_email .= "Remetente:  ".$remetente. "<br>    <b>Prezado(a) $posto_nome,</b>\n";
			$corpo_email .= "</font>\n";
			$corpo_email .= "</td>\n";
		
			$corpo_email .= "</tr>\n";
			$corpo_email .= "<tr>\n";
		
			$corpo_email .= "<td width='100%' align='left' bgcolor='#FFFFFF'>\n";
			$corpo_email .= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>\n";
			$corpo_email .= nl2br($mensagem) ."\n" . $anexo;
			$corpo_email .= "</font>\n";
			$corpo_email .= "</td>\n";
		
			$corpo_email .= "</tr>\n";
			$corpo_email .= "</table>\n"; 
		
			$subject    = $assunto;
		
			$from_nome  = "Black & Decker";
			$from_email = "$remetente";
		
			$to_nome  = "$posto_nome";
			$to_email = "$posto_email";
		
			$cabecalho  = "From: $from_nome < $from_email >\n";
			$cabecalho .= "To: $to_nome < $to_email >\n";
			$cabecalho .= "Return-Path: < $from_email >\n";
			$cabecalho .= "MIME-version: 1.0\n";

			if (($ext == '') and (strlen($attach_filename) == 0)) {
				$cabecalho .= "Content-type: multipart/mixed; ";
				$cabecalho .= "boundary=\"Message-Boundary\"\n";
				$cabecalho .= "Content-transfer-encoding: 7BIT\n";
				$cabecalho .= "X-attachments: $attach_filename";
		
				$body_top  = "--Message-Boundary\n";
				$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
				$body_top .= "Content-transfer-encoding: 7BIT\n";
				$body_top .= "Content-description: Mail message body\n\n";
		
				$corpo_email = $body_top . $corpo_email;
		
				$corpo_email .= "\n\n--Message-Boundary\n";
				$corpo_email .= "Content-type: $attach_type; name=\"$attach_filename\"\n";
				$corpo_email .= "Content-Transfer-Encoding: BASE64\n";
				$corpo_email .= "Content-disposition: attachment; filename=\"$attach_filename\"\n\n";
				$corpo_email .= "$encoded_attach\n";
				$corpo_email .= "--Message-Boundary--\n";
			}else{	
				$cabecalho .= "Content-type: text/html; charset=iso-8859-1\n";
			}
			mail ("" , stripslashes(utf8_encode($subject)), utf8_encode("$corpo_email") , "$cabecalho");
						//FIM ROTINA DE ENVIO DE EMAIL
						//FIM ROTINA DE ENVIO DE EMAIL 
		 
		}//FIM DO FOR DE ENVIO PARA ESPECIFICOS
// 				echo "$aux_destinatario_especifico_mail";
	} //FIM DO VERIFICA SE TEM EMAIL ESPECIFICO

	} //fim se nao tiver erro envia

}//FIM BOTAO ENVIAR

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
.sucess {
    color: white;
    text-align: center;
    font: bold 16px Verdana, Arial, Helvetica, sans-serif;
    background-color: green;
}
</style>
<?
if((strlen($erro) == 0) && (strlen($btnacao)>0)){
	echo "<table width='650' border='0' cellpadding='2' cellspacing='2' align='center'>";
		echo "<tr>";
		echo "<td align='center' class='sucess' nowrap><b>E-mail enviado com sucesso</b></td>";
		echo "</tr>";
	echo "</table>";
	echo "<br>";
}

if (strlen($erro) > 0) { 
	echo "<table width='650' border='0' cellpadding='2' cellspacing='2' align='center'>";
		echo "<tr>";
		echo "<td align='center' class='error' nowrap><b>$erro</b></td>";
		echo "</tr>";
	echo "</table>";
		echo "<br>";
}
echo "<form enctype = 'multipart/form-data' name='frmcomunicado' method='post' action='$PHP_SELF'>";
echo "<input type='hidden' name='comunicado' value='$comunicado'>";
echo "<table width='600' border='0' cellpadding='5' cellspacing='3' align='center'>";
	echo "<tr>";
	echo "<td class='table_line'><b>Assunto</b></td>";
	echo "<td class='table_line2'><input type='text' name='assunto' size = '20' maxlength='50' value='$assunto' style='width:450px' class='frm'></td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td class='table_line'><b>Anexo</b></td>";
	echo "<td class='table_line2'><input type='file' name='anexo' size='60' class='frm'></td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td class='table_line'><b>Mensagem</b></td>";
	echo "<td class='table_line2'><textarea name='mensagem' cols='50' rows='10' style='width:450px' class='frm'>$mensagem</textarea></TD>";
	echo "</tr>";
	echo "<tr>";
	echo "<td class='table_line'><b>E-mail do Remetente</b></td>";
	echo "<td class='table_line2'><input type='text' name='remetente' size='20' maxlength='50' value='$remetente' style='width:450px' class='frm'></td>";
	echo "</tr>";
echo "</table>";

echo "<br>";
//DESTINATARIOS INTERNOS
//DESTINATARIOS INTERNOS

$sql_destinatarios = "SELECT nome_completo, email from tbl_admin where fabrica = 1 and ativo = 't' order by nome_completo ASC";
$res_destinatarios =  pg_query($con, $sql_destinatarios);
echo "<table width='600' border='0' cellpadding='5' cellspacing='3' align='center'>";
	
	echo "<tr>";
	echo "<td class='table_line' colspan='2'><p align='center'><b>Destinatários internos</b><br>(Selecione os <b>destinatários</b> para receberem cópia do comunicado)</p></td>";
	echo "</tr>";
	if(pg_num_rows($res_destinatarios) > 0){
		$novo = 0;
		for($i = 0; $i < pg_num_rows($res_destinatarios); $i++){
			$coluna++;
			$email = pg_fetch_result($res_destinatarios, $i, 'email');
			$nome_completo = pg_fetch_result($res_destinatarios, $i, 'nome_completo');

			if ($coluna == 1) echo "<tr>\n";
			if ($coluna == 3) {
				echo "</tr>\n";
				$coluna = 1;
			}
			
			echo "<td class='table_line2' nowrap><input type='checkbox' name='email_interno_$i' value='$email' class='frm'>
			<input type='hidden' name='nome_interno_$i' value='$nome_completo' class='frm'> $nome_completo</td>";
		}
		echo "<input type='hidden' name='qtde_interno' value='$i'>";
	}
echo "</table>";
//FIM DESTINATARIOS INTERNOS
//FIM DESTINATARIOS INTERNOS


//DESTINATARIOS ESPECIFICO DIGITADO
//DESTINATARIOS ESPECIFICO DIGITADO
		
		
echo "<br>";
echo "<table width='600' border='0' cellpadding='5' cellspacing='3' align='center'>";
	echo "<tr>";
	echo "<td class='table_line'><center><b>Destinatários específicos</b><br>(Utilize o <b>código do posto</b> um em cada linha)<br><hr>Esta opção mostrará o comunicado para o posto independente das condições abaixo.<br><hr>Caso utilize esta opção e mais as abaixo o sistema mostrará o comunicado para o(s) posto(s) específico(s) e para para o(s) posto(s) em que as regras informadas abaixo sejam válidas.</center></td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td class='table_line2'><textarea name='destinatario_especifico' cols='94' rows='5' class='frm'> $destinatario_especifico</textarea>";
	echo "</td>";
	echo "</tr>";
	echo "</table>";
//DESTINATARIOS ESPECIFICO DIGITADO
//DESTINATARIOS ESPECIFICO DIGITADO

		
	echo "<br>";

//DESTINATARIOS POR TIPO
//DESTINATARIOS POR TIPO	
	echo "<table width='600' border='0' cellpadding='5' cellspacing='3' align='center'>";
	echo "<tr>";
	echo "<td class='table_line' colspan='2'><p align='center'><b>Destinatário por TIPO</b></p></td>";
	echo "</tr>";

$sql =	"SELECT  tipo_posto,
				descricao
		FROM     tbl_tipo_posto
		WHERE    fabrica = $login_fabrica and tipo_posto <> 40
		ORDER BY descricao;";
$res = pg_query($con,$sql);

if (pg_num_rows($res) > 0) {
	$coluna = 0;

for ($x = 0 ; $x < pg_num_rows($res) ; $x++){
if (strlen($msg) > 0) $destinatario = $_POST["destinatario_". $x];
$coluna++;
$aux_tipo_posto = trim(pg_fetch_result($res,$x,tipo_posto));
$aux_descricao  = trim(pg_fetch_result($res,$x,descricao));

if ($coluna == 1) echo "<tr>\n";
if ($coluna == 3) {
	echo "</tr>\n";
	$coluna = 1;
}
echo "<td class='table_line2'>\n";
echo "<input type='checkbox' name='destinatario_$x' value='$aux_tipo_posto'"; if ($destinatario == $aux_tipo_posto) echo " CHECKED "; echo " class='frm'> $aux_descricao\n";
echo "</td>\n";
}
echo "<input type='hidden' name='destinatarios' value='$x'>\n";
}
echo "</table>";
//FIM DESTINATARIOS POR TIPO
//FIM DESTINATARIOS POR TIPO

echo "<br>";


//DESTINATARIOS POR REGIAO
//DESTINATARIOS POR REGIAO

echo "<table width='600' border='0' cellpadding='5' cellspacing='3' align='center'>";
	echo "<tr>";
	echo "<td class='table_line' colspan='2'><p align='center'><b>Destinatário por REGIÃO</b></p></td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td class='table_line2'><input type='checkbox' name='regiao_1' value='CENTRO-OESTE' class='frm'> Centro-Oeste</td>";
	echo "<td class='table_line2'><input type='checkbox' name='regiao_2' value='NORDESTE' class='frm'> Nordeste</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td class='table_line2'><input type='checkbox' name='regiao_3' value='NORTE' class='frm'> Norte</td>";
	echo "<td class='table_line2'><input type='checkbox' name='regiao_4' value='SUL' class='frm'> Sul</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td class='table_line2'><input type='checkbox' name='regiao_5' value='SUDESTE' class='frm'> Sudeste</td>";
	echo "<td class='table_line2'><input type='checkbox' name='regiao_6' value='MG' class='frm'> Minas Gerais</td>";
	echo "</tr>";
echo "</table>";


//FIM DESTINATARIOS POR REGIAO
//FIM DESTINATARIOS POR REGIAO


//SULFAMA
//DESTINATARIOS POR REGIAO

echo "<table width='600' border='0' cellpadding='5' cellspacing='3' align='center'>";
	echo "<tr>";
	echo "<td class='table_line' width='100'><b>Pede<br>Peça em garantia</b></td>";
	echo "<td class='table_line2'>";
	echo "<select name='pede_peca_garantia' class='frm'>";
	echo "<option value='todos'"; if ($pede_peca_garantia == 'todos') echo "selected"; echo ">Todos</option>";
	echo "<option value='t'"; if ($pede_peca_garantia == 't') echo "selected"; echo ">Sim</option>";
	echo "<option value='f'"; if ($pede_peca_garantia == 'f') echo "selected"; echo ">Não</option>";
	echo "</select>";
	echo "</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td class='table_line'><b>Pede<br>Peça faturada</b></td>";
	echo "<td class='table_line2'>";
	echo "<select name='pede_peca_faturada' class='frm'>";
	echo "<option value='todos'"; if ($pede_peca_faturada == 'todos') echo "selected"; echo ">Todos</option>";
	echo "<option value='t'"; if ($pede_peca_faturada == 't') echo "selected"; echo ">Sim</option>";
	echo "<option value='f'"; if ($pede_peca_faturada == 'f') echo "selected"; echo ">Não</option>";
	echo "</select>";
	echo "</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td class='table_line'><b>Suframa</b></td>";
	echo "<td class='table_line2'>";
	echo "<select name='suframa' class='frm'>";
	echo "<option value='todos'"; if ($suframa == 'todos') echo "selected"; echo ">Todos</option>";
	echo "<option value='t'"; if ($suframa == 't') echo "selected"; echo ">Sim</option>";
	echo "<option value='f'"; if ($suframa == 'f') echo "selected"; echo ">Não</option>";
	echo "</select>";
	echo "</td>";
	echo "</tr>";
echo "</table>";

echo "<br>";

//<!-- ============================ Botoes de Acao ========================= -->

echo "<center>";
echo "<input type='submit' name='btnacao' class='btnrel' value='Enviar...' onclick=\"javascript: if(document.frmcomunicado.btn_finalizar.value == '0' ) { document.frmcomunicado.btn_finalizar.value='1'; document.frmcomunicado.submit() ; } else { alert ('Aguarde submissão da OS...'); }\">";
echo "</center>";

echo "</form>";


include 'rodape.php';
?>
