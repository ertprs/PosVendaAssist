<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="cadastros";
include 'autentica_admin.php';
$title = "COMUNICADOS POR EMAIL";
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
	
/*	
echo "assunto: $assunto<BR>msg $mensagem<BR>remetente $remetente<Br>suframa $suframa<BR>faturada $faturada<BR>garantia $garantia<BR>" ;*/
//insere na tabela
  
 
$resx = pg_exec ($con,"BEGIN TRANSACTION");

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
			$res = pg_exec ($con,$sql);
			//echo "$sql"; //exit;
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
}
  
 
//fim insere na tabela 

//PEGA ANEXO
if (strlen($erro) == 0) {
				///////////////////////////////////////////////////
				// Rotina que faz o upload do arquivo
				///////////////////////////////////////////////////
				// Prepara a variável do arquivo
				$arquivo = (strlen($_FILES["anexo"]["name"]) > 0) ? $_FILES["anexo"] : '';

				// Tamanho máximo do arquivo (em bytes)
				$config["tamanho"] = 2000000;

				// Formulário postado... executa as ações
				//if (strlen($arquivo) > 0){
				if (strlen($arquivo["name"]) > 4){
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

						$aux_extensao = "'".$ext[1]."'";

						$nome_foto = $nome_anexo;

						// Caminho de onde a imagem ficará + extensao
						$imagem_dir = "../comunicados/".$nome_anexo;

						// Exclui arquivo anterior
						//@unlink($imagem_dir);

						// Faz o upload da imagem
						if (strlen($erro) == 0) {

							if (copy($arquivo["tmp_name"], $imagem_dir)) {
							//echo "anexo enviado";
								$sql =	"UPDATE tbl_comunicado SET
											extensao  = $aux_extensao
										WHERE comunicado = $comunicado
										AND   fabrica    = $login_fabrica";
								$res = @pg_exec ($con,$sql);
								$msg_erro = pg_errormessage($con);

								if (strlen ( pg_errormessage ($con) ) > 0) {
										$erro .= pg_errormessage ($con) ;
								}
							}
						}else $erro="Anexo não foi enviado!";//alterado por raphael
					}
				}
}//FIM PEGA ANEXO

  
	if (strlen($erro) == 0) {
		$resx = pg_exec ($con,"COMMIT TRANSACTION");
	}else{
		$resx = pg_exec ($con,"ROLLBACK TRANSACTION");
		$erro  = "<b>Foi detectado o seguinte erro: </b><br>";
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
	$corpo_email .= nl2br($mensagem) ."\n";
	$corpo_email .= "</font>\n";
	$corpo_email .= "</td>\n";

	$corpo_email .= "</tr>\n";
	$corpo_email .= "</table>\n"; 
 
#########verifica o anexo
 	if ($aux_extensao != '') {
		$attach = "/var/www/assist/www/comunicados/$nome_foto";
		$file = fopen($attach, "r");
		$contents = fread($file, $anexo_size);
		$encoded_attach = chunk_split(base64_encode($contents));
		fclose($file);
	}
#########verifica o anexo
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

	if ($aux_extensao != '' ) {
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
	mail ("" , stripslashes($subject), "$corpo_email" , "$cabecalho");
//FIM ENVIO DE EMAIL PRA MI
##############FIM  todos emails vao com copia para MI
 
###########mandar para email interno 0 a 15
	$qtde_interno=16;
	for($z=0; $z<$qtde_interno; $z++){
		$email_interno = trim($_POST["email_interno_".$z]);
		if(strlen($email_interno)>0){
			/*
			if ($email_interno == "helpdesk@telecontrol.com.br")        $nome_interno = "Suporte";
			if ($email_interno == "fernando@telecontrol.com.br")   $nome_interno = "Fernando";
			if ($email_interno == "takashi@telecontrol.com.br")         $nome_interno = "Takashi";
			if ($email_interno == "wellington@telecontrol.com.br")    $nome_interno = "wellington";
			*/





			if ($email_interno == "MiPereira@blackedecker.com.br")        $nome_interno = "Miguel Pereira";
			if ($email_interno == "salves@blackedecker.com.br")           $nome_interno = "Silvania Alves";
			if ($email_interno == "pmachado@blackedecker.com.br")         $nome_interno = "Patrícia Machado";
			if ($email_interno == "rberto@blackedecker.com.br")    $nome_interno = "Rogério Berto";
			if ($email_interno == "ureis@blackedecker.com.br")            $nome_interno = "Ulisses Reis";
			if ($email_interno == "llaterza@blackedecker.com.br")         $nome_interno = "Lilian Laterza";
			if ($email_interno == "rfernandes@blackedecker.com.br")       $nome_interno = "Rúbia Lane Fernandes";
			if ($email_interno == "fabiola.oliveira@bdk.com")             $nome_interno = "Fabíola Oliveira";
			if ($email_interno == "jreinaldo@blackedecker.com.br")        $nome_interno = "José Reinaldo";
			if ($email_interno == "mclemente@blackedecker.com.br")        $nome_interno = "Michel Clemente";
			if ($email_interno == "acamilo@blackedecker.com.br")          $nome_interno = "Anderson Camilo";
			if ($email_interno == "mribeiro@blackedecker.com.br")         $nome_interno = "Marcos Leandro Ribeiro da Silva";
			if ($email_interno == "doliveira@blackedecker.com.br")        $nome_interno = "Diógenes Fred de Oliveira";
			if ($email_interno == "samaral@blackedecker.com.br")          $nome_interno = "Sabrina Amaral";
//			if ($email_interno == "drocha@blackedecker.com.br")           $nome_interno = "Diogo Rocha";
			if ($email_interno == "fernanda_silva@blackedecker.com.br")           $nome_interno = "Fernanda Silva";

			if ($email_interno == "marcos_vinicius@blackedecker.com.br")  $nome_interno = "Marcos Vinícius";
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
 
#########verifica o anexo
			if ($aux_extensao != '') {
				$attach = "/var/www/assist/www/comunicados/$nome_foto";
				$file = fopen($attach, "r");
				$contents = fread($file, $anexo_size);
				$encoded_attach = chunk_split(base64_encode($contents));
				fclose($file);
			}
#########verifica o anexo
			$subject    = $assunto;
		
			$from_nome  = "Black & Decker";
			$from_email = "$remetente";
		
			$to_nome  = "$nome_interno";
			$to_email = "$email_interno";
		
			$cabecalho  = "From: $from_nome < $from_email >\n";
			$cabecalho .= "To: $to_nome < $to_email >\n";
			$cabecalho .= "Return-Path: < $from_email >\n";
			$cabecalho .= "MIME-version: 1.0\n";

			if ($aux_extensao != '' ) {
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
			mail ("" , stripslashes($subject), "$corpo_email" , "$cabecalho");
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
						tbl_posto.email, 
						tbl_posto_fabrica.pedido_em_garantia, 
						tbl_posto_fabrica.pedido_faturado, 
						tbl_posto.suframa, 
						tbl_posto_fabrica.tipo_posto 
				FROM tbl_posto 
				JOIN tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto 
				AND tbl_posto_fabrica.fabrica = $login_fabrica 
				AND tbl_posto_fabrica.tipo_posto= $destinatario 
				AND tbl_posto_fabrica.credenciamento <> 'DESCREDENCIADO'
				WHERE tbl_posto.email notnull 
				AND length(tbl_posto.email) > 0";
				if($garantia<>"null"){ $sql .=" AND tbl_posto_fabrica.pedido_em_garantia = $garantia"; }
				if($faturada<>"null"){ $sql .=" AND tbl_posto_fabrica.pedido_faturado = $faturada"; }
				if($suframa<>"null"){ $sql .=" AND tbl_posto.suframa = $suframa"; }
				
			$resx = pg_exec ($con,$sql);
//echo "tipo ------------<BR>$sql";
			for ( $i = 0 ; $i < pg_numrows ($resx) ; $i++ ) {
				$posto       = trim(pg_result($resx,$i,posto));
				$posto_nome  = trim(pg_result($resx,$i,nome));
				$posto_email = trim(strtolower(pg_result($resx,$i,email)));
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
			
#########verifica o anexo
				if ($aux_extensao != '') {
					$attach = "/var/www/assist/www/comunicados/$nome_foto";
					$file = fopen($attach, "r");
					$contents = fread($file, $anexo_size);
					$encoded_attach = chunk_split(base64_encode($contents));
					fclose($file);
				}
						#########verifica o anexo
				$subject    = $assunto;
			
				$from_nome  = "Black & Decker";
				$from_email = "$remetente";
			
				$to_nome  = "$posto_nome";
				$to_email = "$posto_email";
			
				$cabecalho  = "From: $from_nome < $from_email >\n";
				$cabecalho .= "To: $to_nome < $to_email >\n";
				$cabecalho .= "Return-Path: < $from_email >\n";
				$cabecalho .= "MIME-version: 1.0\n";
			
				if ($aux_extensao != '' ) {
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
				mail ("" , stripslashes($subject), "$corpo_email" , "$cabecalho");
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
					$resx = pg_exec ($con,$sql);
  //echo "REGIAO---------<br>$sql";
				for ( $i = 0 ; $i < pg_numrows ($resx) ; $i++ ) {
					$posto       = trim(pg_result($resx,$i,posto));
					$posto_nome  = trim(pg_result($resx,$i,nome));
					$posto_estado  = trim(pg_result($resx,$i,estado));
					$posto_regiao  = trim(pg_result($resx,$i,regiao));
					$posto_email = trim(strtolower(pg_result($resx,$i,email)));
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
				
		#########verifica o anexo
					if ($aux_extensao != '') {
						$attach = "/var/www/assist/www/comunicados/$nome_foto";
						$file = fopen($attach, "r");
						$contents = fread($file, $anexo_size);
						$encoded_attach = chunk_split(base64_encode($contents));
						fclose($file);
					}
		#########verifica o anexo
					$subject    = $assunto;
				
					$from_nome  = "Black & Decker";
					$from_email = "$remetente";
				
					$to_nome  = "$posto_nome";
					$to_email = "$posto_email";
				
					$cabecalho  = "From: $from_nome < $from_email >\n";
					$cabecalho .= "To: $to_nome < $to_email >\n";
					$cabecalho .= "Return-Path: < $from_email >\n";
					$cabecalho .= "MIME-version: 1.0\n";
				
					if ($aux_extensao != '' ) {
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
						// 		echo "2";
					}
					mail ("" , stripslashes($subject), "$corpo_email" , "$cabecalho");
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
				$resx = pg_exec ($con,$sql);
  				//	echo "<bR><br><br>------------------------MG -----------------";
				for ( $i = 0 ; $i < pg_numrows ($resx) ; $i++ ) {
					$posto       = trim(pg_result($resx,$i,posto));
					$posto_nome  = trim(pg_result($resx,$i,nome));
					$posto_estado  = trim(pg_result($resx,$i,estado));
					$posto_regiao  = trim(pg_result($resx,$i,regiao));
					$posto_email = trim(strtolower(pg_result($resx,$i,email)));
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
						
#########verifica o anexo
					if ($aux_extensao != '') {
						$attach = "/var/www/assist/www/comunicados/$nome_foto";
						$file = fopen($attach, "r");
						$contents = fread($file, $anexo_size);
						$encoded_attach = chunk_split(base64_encode($contents));
						fclose($file);
					}
#########verifica o anexo
					$subject    = $assunto;
				
					$from_nome  = "Black & Decker";
					$from_email = "$remetente";
				
					$to_nome  = "$posto_nome";
					$to_email = "$posto_email";
				
					$cabecalho  = "From: $from_nome < $from_email >\n";
					$cabecalho .= "To: $to_nome < $to_email >\n";
					$cabecalho .= "Return-Path: < $from_email >\n";
					$cabecalho .= "MIME-version: 1.0\n";

					if ($aux_extensao != '' ) {
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
					mail ("" , stripslashes($subject), "$corpo_email" , "$cabecalho");
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
					tbl_posto.email 
				FROM tbl_posto  
				JOIN tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto 
				AND tbl_posto_fabrica.fabrica = $login_fabrica 
				WHERE codigo_posto in $aux_destinatario_especifico_mail 
				AND tbl_posto.email notnull 
				AND tbl_posto_fabrica.credenciamento <> 'DESCREDENCIADO'
				AND length(tbl_posto.email) > 0";
				if($garantia<>"null"){ $sql .=" AND tbl_posto_fabrica.pedido_em_garantia = $garantia"; }
				if($faturada<>"null"){ $sql .=" AND tbl_posto_fabrica.pedido_faturado = $faturada"; }
				if($suframa<>"null"){ $sql .=" AND tbl_posto.suframa = $suframa"; }
		$resx = pg_exec ($con,$sql);
 		//echo "ESPECIFICO ==========<br>$sql";
// 		echo "<BR>por posto<BR>";
		for ( $i = 0 ; $i < pg_numrows ($resx) ; $i++ ) {
			$posto       = trim(pg_result($resx,$i,posto));
			$posto_nome  = trim(pg_result($resx,$i,nome));
			$posto_email = trim(strtolower(pg_result($resx,$i,email)));
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
		
#########verifica o anexo
			if ($aux_extensao != '') {
				$attach = "/var/www/assist/www/comunicados/$nome_foto";
				$file = fopen($attach, "r");
				$contents = fread($file, $anexo_size);
				$encoded_attach = chunk_split(base64_encode($contents));
				fclose($file);
			}
#########verifica o anexo
			$subject    = $assunto;
		
			$from_nome  = "Black & Decker";
			$from_email = "$remetente";
		
			$to_nome  = "$posto_nome";
			$to_email = "$posto_email";
		
			$cabecalho  = "From: $from_nome < $from_email >\n";
			$cabecalho .= "To: $to_nome < $to_email >\n";
			$cabecalho .= "Return-Path: < $from_email >\n";
			$cabecalho .= "MIME-version: 1.0\n";

			if ($aux_extensao != '' ) {
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
			mail ("" , stripslashes($subject), "$corpo_email" , "$cabecalho");
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
margin: 0 auto;
}
.texto_avulso{
       font: 14px Arial; color: rgb(89, 109, 155);
       background-color: #d9e2ef;
       text-align: center;
       width:700px;
       margin: 0 auto;
}
.formulario{
background-color:#D9E2EF;
font:11px Arial;
}

.subtitulo{
background-color: #7092BE;
font:bold 11px Arial;
color: #FFFFFF;
}

table.tabela tr td{
font-family: verdana;
font-size: 11px;
border-collapse: collapse;
border:1px solid #596d9b;
}

.msg_sucesso{
background-color: green;
font: bold 16px "Arial";
color: #FFFFFF;
text-align:center;
width: 700px;
}
</style>
<script type='text/javascript' src='js/jquery.js' ></script>
<script type='text/javascript'>

	$( document ).ready( function(){
			$( '#dest_int tr:even' ).css('background-color' , '#F7F5F0');
			$( '#dest_int tr:odd' ).css('background-color' , '#F1F4FA');
			$( '#dest_int tr:even' ).css('text-align' , 'left');
			$( '#dest_int tr:odd' ).css('text-align' , 'left');
			
			$( '#dest_tipo tr:even' ).css('background-color' , '#F7F5F0');
			$( '#dest_tipo tr:odd' ).css('background-color' , '#F1F4FA');
			$( '#dest_tipo tr:even' ).css('text-align' , 'left');
			$( '#dest_tipo tr:odd' ).css('text-align' , 'left');
			
			$( '#dest_reg tr:even' ).css('background-color' , '#F7F5F0');
			$( '#dest_reg tr:odd' ).css('background-color' , '#F1F4FA');
			$( '#dest_reg tr:even' ).css('text-align' , 'left');
			$( '#dest_reg tr:odd' ).css('text-align' , 'left');
			
			$( '#tab_avulsa tr:even' ).css('background-color' , '#F7F5F0');
			$( '#tab_avulsa tr:odd' ).css('background-color' , '#F1F4FA');
			$( '#tab_avulsa tr:even' ).css('text-align' , 'left');
			$( '#tab_avulsa tr:odd' ).css('text-align' , 'left');
			
		} );

</script>
<?
if (strlen($msg) > 0) { 
	echo "<table width='700px' border='0' cellpadding='2' cellspacing='1' align='center'>";
		echo "<tr>";
		echo "<td align='center' class='msg_erro' nowrap>$erro</td>";
		echo "</tr>";
	echo "</table>";
		echo "<br>";
}
echo "<form enctype = 'multipart/form-data' name='frmcomunicado' method='post' action='$PHP_SELF'>";
echo "<input type='hidden' name='comunicado' value='$comunicado'>";
echo "<table width='700px' class='formulario' border='0' cellpadding='3' cellspacing='1' align='center'>";
	echo "<tr>";
	echo "<td>Assunto</td>";
	echo "<td><input type='text' name='assunto' size = '20' maxlength='50' value='$assunto' style='width:450px' class='frm'></td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td>Anexo</td>";
	echo "<td<input type='file' name='anexo' size='56' class='frm'></td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td>Mensagem</td>";
	echo "<td><textarea name='mensagem' cols='50' rows='10' style='width:450px' class='frm'>$mensagem</textarea></TD>";
	echo "</tr>";
	echo "<tr>";
	echo "<td>E-mail do Remetente</b></td>";
	echo "<td><input type='text' name='remetente' size='20' maxlength='50' value='$remetente' style='width:450px' class='frm'></td>";
	echo "</tr>";
echo "</table>";

echo "<br>";
//DESTINATARIOS INTERNOS
//DESTINATARIOS INTERNOS
echo "<table width='700px' border='0' cellpadding='3' class='formulario' cellspacing='1' align='center' id='dest_int'>";
	echo "<tr>";
	echo "<td colspan='2' class='texto_avulso' >Destinatários internos<br>(Selecione os <b>destinatários</b> para receberem cópia do comunicado)</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td  nowrap><input type='checkbox' name='email_interno_0' value='MiPereira@blackedecker.com.br' class='frm'> Miguel Pereira</td>";
	echo "<td  nowrap><input type='checkbox' name='email_interno_1' value='salves@blackedecker.com.br' class='frm'> Silvania Alves</td>";
 
//  	echo "<td  nowrap><input type='checkbox' name='email_interno_0' value='fernando@telecontrol.com.br' class='frm'> Fernando</td>";
// 	echo "<td  nowrap><input type='checkbox' name='email_interno_1' value='takashi@telecontrol.com.br' class='frm'> Takashi</td>";
 
 
	echo "</tr>";
	echo "<tr>";
	echo "<td  nowrap><input type='checkbox' name='email_interno_2' value='pmachado@blackedecker.com.br' class='frm'> Patrícia Machado </td>";
	echo "<td  nowrap><input type='checkbox' name='email_interno_3' value='rberto@blackedecker.com.br' class='frm'> Rogério Berto</td>";
//  	echo "<td  nowrap><input type='checkbox' name='email_interno_2' value='helpdesk@telecontrol.com.br' class='frm'> Suporte</td>";
// 	echo "<td  nowrap><input type='checkbox' name='email_interno_3' value='wellington@telecontrol.com.br' class='frm'> Wellington</td>"; 
 
 
	echo "</tr>";
	echo "<tr>";
	echo "<td  nowrap><input type='checkbox' name='email_interno_4' value='ureis@blackedecker.com.br' class='frm'> Ulisses Reis</td>";
	echo "<td  nowrap><input type='checkbox' name='email_interno_5' value='llaterza@blackedecker.com.br' class='frm'> Lilian Laterza</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td  nowrap><input type='checkbox' name='email_interno_6' value='rfernandes@blackedecker.com.br'  class='frm'> Rúbia Lane Fernandes</td>";
	echo "<td  nowrap><input type='checkbox' name='email_interno_7' value='fabiola.oliveira@bdk.com' class='frm'> Fabíola Oliveira</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td  nowrap><input type='checkbox' name='email_interno_8' value='mclemente@blackedecker.com.br' class='frm'> Michel Clemente</td>";
	echo "<td  nowrap><input type='checkbox' name='email_interno_9' value='marcos_vinicius@blackedecker.com.br' class='frm'> Marcos Vinícius</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td  nowrap><input type='checkbox' name='email_interno_10' value='acamilo@blackedecker.com.br' class='frm'> Anderson Camilo</td>";
	echo "<td  nowrap><input type='checkbox' name='email_interno_11' value='mribeiro@blackedecker.com.br' class='frm'> Marcos Leandro Ribeiro da Silva</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td  nowrap><input type='checkbox' name='email_interno_12' value='doliveira@blackedecker.com.br' class='frm'> Diógenes Fred de Oliveira</td>";
	echo "<td  nowrap><input type='checkbox' name='email_interno_13' value='samaral@blackedecker.com.br' class='frm'> Sabrina Amaral</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td  nowrap><input type='checkbox' name='email_interno_14' value='jreinaldo@blackedecker.com.br'  class='frm'> José Reinaldo</td>";
	echo "<td  nowrap><input type='checkbox' name='email_interno_15' value='fernanda_silva@blackedecker.com.br' class='frm'> Fernanda Silva</td>";
	echo "</tr>";
echo "</table>";
//FIM DESTINATARIOS INTERNOS
//FIM DESTINATARIOS INTERNOS


//DESTINATARIOS ESPECIFICO DIGITADO
//DESTINATARIOS ESPECIFICO DIGITADO
		
		
echo "<br>";
echo "<table width='700px' class='formulario' border='0' cellpadding='3' cellspacing='1' align='center'>";
	echo "<tr>";
	echo "<td class='texto_avulso'><center><b>Destinatários específicos</b><br>(Utilize o <b>código do posto</b> um em cada linha)<br><hr>Esta opção mostrará o comunicado para o posto independente das condições abaixo.<br><hr>Caso utilize esta opção e mais as abaixo o sistema mostrará o comunicado para o(s) posto(s) específico(s) e para para o(s) posto(s) em que as regras informadas abaixo sejam válidas.</center></td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td ><textarea name='destinatario_especifico' cols='74' rows='5' class='frm'> $destinatario_especifico</textarea>";
	echo "</td>";
	echo "</tr>";
	echo "</table>";
//DESTINATARIOS ESPECIFICO DIGITADO
//DESTINATARIOS ESPECIFICO DIGITADO

		
	echo "<br>";

//DESTINATARIOS POR TIPO
//DESTINATARIOS POR TIPO	
	echo "<table width='700px' class='formulario' border='0' cellpadding='3' cellspacing='1' align='center' id='dest_tipo'>";
	echo "<tr>";
	echo "<td class='titulo_coluna' colspan='2'>Destinatário por Tipo</td>";
	echo "</tr>";

$sql =	"SELECT  tipo_posto,
				descricao
		FROM     tbl_tipo_posto
		WHERE    fabrica = $login_fabrica and tipo_posto <> 40
		ORDER BY descricao;";
$res = pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {
	$coluna = 0;

for ($x = 0 ; $x < pg_numrows($res) ; $x++){
if (strlen($msg) > 0) $destinatario = $_POST["destinatario_". $x];
$coluna++;
$aux_tipo_posto = trim(pg_result($res,$x,tipo_posto));
$aux_descricao  = trim(pg_result($res,$x,descricao));

if ($coluna == 1) echo "<tr>\n";
if ($coluna == 3) {
	echo "</tr>\n";
	$coluna = 1;
}
echo "<td >\n";
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

echo "<table width='700px' border='0' cellpadding='3' cellspacing='1' class='formulario' align='center' id='dest_reg'>";
	echo "<tr>";
	echo "<td  colspan='2' class='titulo_coluna'>Destinatário por Região</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td ><input type='checkbox' name='regiao_1' value='CENTRO-OESTE' class='frm'> Centro-Oeste</td>";
	echo "<td ><input type='checkbox' name='regiao_2' value='NORDESTE' class='frm'> Nordeste</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td ><input type='checkbox' name='regiao_3' value='NORTE' class='frm'> Norte</td>";
	echo "<td ><input type='checkbox' name='regiao_4' value='SUL' class='frm'> Sul</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td ><input type='checkbox' name='regiao_5' value='SUDESTE' class='frm'> Sudeste</td>";
	echo "<td ><input type='checkbox' name='regiao_6' value='MG' class='frm'> Minas Gerais</td>";
	echo "</tr>";
echo "</table>";


//FIM DESTINATARIOS POR REGIAO
//FIM DESTINATARIOS POR REGIAO


//SULFAMA
//DESTINATARIOS POR REGIAO

echo "<br /><table width='700px' border='0' cellpadding='3' cellspacing='1' class='formulario' align='center' id='tab_avulsa'>";
	echo "<tr>";
	echo "<td  width='100' nowrap>Pede Peça em garantia</td>";
	echo "<td >";
	echo "<select name='pede_peca_garantia' class='frm'>";
	echo "<option value='todos'"; if ($pede_peca_garantia == 'todos') echo "selected"; echo ">Todos</option>";
	echo "<option value='t'"; if ($pede_peca_garantia == 't') echo "selected"; echo ">Sim</option>";
	echo "<option value='f'"; if ($pede_peca_garantia == 'f') echo "selected"; echo ">Não</option>";
	echo "</select>";
	echo "</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td >Pede Peça Faturada</td>";
	echo "<td >";
	echo "<select name='pede_peca_faturada' class='frm'>";
	echo "<option value='todos'"; if ($pede_peca_faturada == 'todos') echo "selected"; echo ">Todos</option>";
	echo "<option value='t'"; if ($pede_peca_faturada == 't') echo "selected"; echo ">Sim</option>";
	echo "<option value='f'"; if ($pede_peca_faturada == 'f') echo "selected"; echo ">Não</option>";
	echo "</select>";
	echo "</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td >Suframa</td>";
	echo "<td >";
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
