<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="cadastros";
include 'autentica_admin.php';
$title = "Comunicados por E-mail";
$layout_menu = "cadastro";
include 'cabecalho.php';

$btn_acao = $_POST["btn_acao"];
if(strlen($btn_acao)>0){
	$para = $_POST["para"]; 
	if(strlen($para)==0){ $msg .= "Insira o destinatário<BR>";}
	
	$remetente = $_POST["remetente"];
	if(strlen($remetente)==0){ $msg .= "Insira o e-mail do remetente<BR>";}
	
	$assunto = $_POST["assunto"];
	if(strlen($assunto)==0){ $msg .= "Insira o assunto<BR>";}

	$mensagem = $_POST["mensagem"];
	if(strlen($mensagem)==0){ $msg .= "Insira a mensagem<BR>";}

	if(strlen($msg)==0){//pega para quem será enviado

/*INSERE NO BANCO*/
	$resx = pg_exec ($con,"BEGIN TRANSACTION");

		$sql = "INSERT into tbl_comunicado( 
											fabrica                          ,
											descricao                        ,
											remetente_email                  ,
											tipo                             ,";
		       if($para<>'todos') $sql .= " tipo_posto                       ,";
								  $sql .= " mensagem                         
											) VALUES (
											$login_fabrica                   ,
											'$assunto'                       ,
											'$remetente'                     ,
											'Por e-mail'                     ,";
		     	if($para<>'todos') $sql .= " $para                           ,";
							      $sql .= " '$mensagem'
											)";
		$res= pg_exec($con, $sql);
			//echo "<BR>INSERE: $sql<BR>";
		if (strlen ( pg_errormessage ($con) ) > 0) {
				$msg .= pg_errormessage ($con) ;
		}
		
		if (strlen($msg) == 0) {
		# PEGA SEQUÊNCIA DA TABELA DE COMUNICADO
			$res1       = pg_exec ($con,"SELECT currval ('seq_comunicado')");
			$comunicado = pg_result ($res1,0,0);

			if (strlen ( pg_errormessage ($con) ) > 0) {
				$msg .= pg_errormessage ($con) ;
			}
		}	
/*INSERE NO BANCO*/
	}

//PEGA ANEXO
	if (strlen($msg) == 0) {
		///////////////////////////////////////////////////
		// Rotina que faz o upload do arquivo
		///////////////////////////////////////////////////
		// Prepara a variável do arquivo
		$arquivo = (strlen($_FILES["anexo"]["name"]) > 0) ? $_FILES["anexo"] : '';

		if (is_array($arquivo)) {
			$s3 = new anexaS3('co', (int) $login_fabrica);

			if (!$s3->uploadFileS3($comunicado, $arquivo)) {
				$msg_erro = $s3->_erro;
			} else {
				$attach_filename = basename($s3->attachList[0]);
				$ext = pathinfo($attach_filename, PATHINFO_EXTENSION);

				if ($ext != '' and $attach_filename) {
					$attach = $_FILES["anexo"]['tmp_name'];
					$encoded_attach  = chunk_split(base64_encode(file_get_contents($attach)));
					$attach_size     = filesize($attach);
					$attach_type     = mime_content_type($attach);
					//$attach_filename = "$comunicado.$ext";
				}

				$sql =	"UPDATE tbl_comunicado
							SET extensao   = '$ext'
						  WHERE comunicado = $comunicado
							AND fabrica    = $login_fabrica";
				$res = @pg_query($con,$sql);
				$msg_erro = pg_last_error($con);

				if (strlen ( pg_last_error($con) ) > 0) {
					$erro .= pg_last_error($con) ;
				}
			}
		}
	}//FIM PEGA ANEXO

	if (strlen($msg) == 0) {
		$resx = pg_exec ($con,"COMMIT TRANSACTION");
	}else{
		$s3->excluiArquivoS3($s3->attachList[0]);  // Exlui o arquivo porque não deu certo cadastrar o comunicado
		$resx = pg_exec ($con,"ROLLBACK TRANSACTION");
	}


	if(strlen($msg)==0){//pega para quem será enviado
		$sql = "SELECT 	tbl_posto.nome, 
						tbl_posto.email 
				FROM tbl_posto 
				JOIN tbl_posto_fabrica on tbl_posto.posto =  tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica = $login_fabrica 
				WHERE tbl_posto_fabrica.fabrica=$login_fabrica
				AND tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
				AND tbl_posto.email NOTNULL";
		if($para<>'todos') $sql .=" AND tbl_posto_fabrica.tipo_posto = $para";
		//echo "$sql";
		$res = pg_exec($con, $sql);
		
		if(pg_numrows($res)>0){
			for($i=0;$i<pg_numrows($res);$i++){
				$nome_posto = pg_result($res,$i,nome);
				$email = pg_result($res,$i,email);
			//	$enviado_para .= "$nome_posto - $email<BR>";
/*MANDA O EMAIL*/
				$corpo_email  = "<table width='600' border='0' cellpadding='2' cellspacing='2' align='center'>\n";
					$corpo_email .= "<tr>\n";
				
					$corpo_email .= "<td width='100%' align='left' bgcolor='#FFFFFF'>\n";
					$corpo_email .= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>\n";
					$corpo_email .= "<b>COMUNICADO INTELBRAS</b>\n";
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

					$subject    = $assunto;
				
					$from_nome  = "Intelbras";
					$from_email = "$remetente";
				
					$to_nome  = "$nome_posto";
				// 	$to_email = "takashi@telecontrol.com.br";
					$to_email = "$email";
				// echo "<BR>copia para $to_email<BR>";
					$cabecalho  = "From: $from_nome < $from_email >\n";
					$cabecalho .= "To: $to_nome < $to_email >\n";
					$cabecalho .= "Return-Path: < $from_email >\n";
					$cabecalho .= "MIME-version: 1.0\n";
				
					if ($aux_extensao != '' ) {
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
					if ( @mail ("" , stripslashes($subject), "$corpo_email" , "$cabecalho") ) {
					}else{$msg .= "E-mail não enviado"; }
/*MANDA O EMAIL*/
			}
		}
		if(strlen($msg)==0){$msg .= "E-mail enviado com sucesso!";}
		
	}


	

}

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
input { 
background-color: #ededed; 
font: 12px verdana;
color:#363738;
border:1px solid #969696;
}
</style>


		
<?
if (strlen($msg) > 0) { 
	echo "<table width='650' border='0' cellpadding='2' cellspacing='2' align='center'>";
	echo "<tr>";
	echo "<td align='center' class='error' nowrap><b>$msg</b></td>";
	echo "</tr>";
	echo "</table>";
	echo "<br>";
}
//echo $enviado_para ;
echo "<form enctype = 'multipart/form-data' name='frmcomunicado' method='post' action='$PHP_SELF'>";
//echo "<input type='hidden' name='comunicado' value='$comunicado'>";
	echo "<BR><table width='600' border='1' cellpadding='2' cellspacing='1' align='center'>";
	echo "<tr>";
	echo "<td class='table_line2'>";
		echo "<BR><table width='600' border='0' cellpadding='3' cellspacing='1' align='center'>";
		echo "<tr>";
		echo "<td class='table_line'><b>Para: </b></td>";
		echo "<td class='table_line2'>";
		echo "<select name='para'  style='width: 450px;'>";
		echo "<option value='todos'>Todos os postos</option>";
		$sql =	"SELECT tipo_posto,
						descricao
			FROM     tbl_tipo_posto
			WHERE    fabrica = $login_fabrica and tipo_posto <> 40
			ORDER BY descricao;";
		$res = pg_exec ($con,$sql);
	
		if (pg_numrows($res) > 0) {
		
			for ($x = 0 ; $x < pg_numrows($res) ; $x++){
				$tipo_posto = trim(pg_result($res,$x,tipo_posto));
				$descricao  = trim(pg_result($res,$x,descricao));
				echo "<option value='$tipo_posto'>Postos $descricao</option>";
			}
		}
		echo "</select>";
		echo "</td>";
		echo "</tr>";	
		echo "<tr>";
		echo "<td class='table_line'><b>E-mail do Remetente: </b></td>";
		echo "<td class='table_line2'><input type='text' name='remetente' size='20' maxlength='50' value='$remetente' style='width:450px' class='frm'></td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td class='table_line'><b>Assunto: </b></td>";
		echo "<td class='table_line2'><input type='text' name='assunto' size = '20' maxlength='50' value='$assunto' style='width:450px' class='frm'></td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td class='table_line'><b>Mensagem: </b></td>";
		echo "<td class='table_line2'><textarea name='mensagem' cols='50' rows='10' style='width:450px' class='frm'>$mensagem</textarea></TD>";
		echo "</tr>";
		echo "<tr>";
		echo "<td class='table_line'><b>Anexo: </b></td>";
		echo "<td class='table_line2'><input type='file' name='anexo' size='40' class='frm'></td>";
		echo "</tr>";
		echo "</table><bR>";
	echo "</td>";
	echo "</tr>";
	echo "</table>";
echo "<br>";
//<!-- ============================ Botoes de Acao ========================= -->
echo "<center>";
echo "<input type='hidden' name='btn_acao' value=''>";
echo "<img border='0' src='imagens_admin/btn_enviar.gif' onclick=\"javascript: if (document.frmcomunicado.btn_acao.value == '' ) { document.frmcomunicado.btn_acao.value='enviar' ; document.frmcomunicado.submit() } else { alert ('Aguarde submissão') }\" alt='Enviar e-mail' style='cursor: pointer;'>";
echo "</center>";

echo "</form>";


include 'rodape.php';
?>
