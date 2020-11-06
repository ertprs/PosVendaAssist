<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';
include 'funcoes.php';
$visual_black = "manutencao-admin";
$layout_menu = "gerencia";
$title = traduz("ENVIO DE E-MAIL");
include 'cabecalho.php';


if (strlen($_GET["email_fabrica"]) > 0) {
	$email_fabrica = trim($_GET["email_fabrica"]);
}

if (strlen($_POST["email_fabrica"]) > 0) {
	$email_fabrica = trim($_POST["email_fabrica"]);
}

if (strlen($_POST["btn_acao"]) > 0) {
	$btn_acao = trim($_POST["btn_acao"]);
}

if ($btn_acao == "confirmar") {

	$arquivo = $_FILES['arquivo'];

	if (strlen($_POST["email_remetente"]) > 0) {
		$aux_email_remetente = "'". trim($_POST["email_remetente"]) ."'";
	}else{
		$msg_erro = traduz("Informe o email do remetente.");
	}
	
	if (strlen($_POST["assunto"]) > 0) {
		$aux_assunto = "'". trim($_POST["assunto"]) ."'";
	}else{
		$msg_erro = traduz("Informe o assunto.");
	}

	if (strlen($_POST["mens_corpo"]) > 0) {
		$aux_mens_corpo = $_POST["mens_corpo"];
		$mens_corpo =  $_POST["mens_corpo"];
	}else{
		$msg_erro = traduz("Informe a mensagem.");
	}

	if (strlen($_POST["linha"]) > 0) {
		$aux_linha = "'". trim($_POST["linha"]) ."'";
	}else{
		$aux_linha = 'null';
	}
	if (strlen($msg_erro) == 0) {
		$res = pg_exec ($con,"BEGIN TRANSACTION");

		if ($arquivo == "none") 
			$arquivo_name = 'null';
		else
			$arquivo_name = "'".$arquivo['name']."'";
		
		if (strlen($email) == 0) {
			###INSERE NOVO REGISTRO
			$sql = "INSERT INTO tbl_email_fabrica (
						fabrica   ,
						linha     ,
						de        ,
						assunto   ,
						mensagem  ,
						nome_anexo
					) VALUES (
						$login_fabrica          ,
						$aux_linha              ,
						$aux_email_remetente    ,
						$aux_assunto            ,
						'$aux_mens_corpo'         ,
						$arquivo_name           
					)";
		}

		$res = pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
	}

	if (strlen($msg_erro) == 0){

		$res = pg_exec ($con,"SELECT currval('seq_email_fabrica')");
		$email_fabrica = pg_result ($res,0,0);

		if ($aux_linha == 'null'){
			$sql = "SELECT tbl_posto.email
					FROM tbl_posto
					JOIN tbl_posto_fabrica USING (posto)
					WHERE tbl_posto_fabrica.fabrica = $login_fabrica
					AND   tbl_posto_fabrica.credenciamento = 'CREDENCIADO' ";
		}else{
			$sql = "SELECT tbl_posto.email
					FROM tbl_posto
					JOIN tbl_posto_fabrica USING (posto)
					JOIN tbl_posto_linha ON tbl_posto_linha.posto = tbl_posto_fabrica.posto
					WHERE tbl_posto_fabrica.fabrica = $login_fabrica
					AND   tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
					AND   tbl_posto_linha.linha     = $linha";
		}
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
		
		if(strlen($msg_erro)==0){
			if ($arquivo <> "none") {
				copy($arquivo['tmp_name'], "/www/assist/www/email_upload/$email_fabrica"); 
			}
		}
	}
	
	if (strlen ($msg_erro) == 0) {
		###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		$msg_ok = traduz("Email agendado com sucesso");
		
		//header ("Location: $PHP_SELF?msg_ok= Email agendado com sucesso");
		//exit;
	}else{
		###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS
		
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}//fim msg_erro
}else{
	?>

	<script type="text/javascript" src="js/fckeditor/fckeditor.js"></script>
	<script language="JavaScript">
		window.onload = function(){
		var oFCKeditor = new FCKeditor( 'mens_corpo',780,400 ) ;
		oFCKeditor.BasePath = "js/fckeditor/" ;
		oFCKeditor.ToolbarSet = 'Basico' ;
		// oFCKeditor.ToolbarSet = 'Chamado' ;
		oFCKeditor.ReplaceTextarea() ;
	}
	</script>
<?}?>
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

<? 
	if(strlen($msg_erro) > 0){
?>

<table width='700px' align='center' border='0' class='msg_erro' cellspacing="1" cellpadding="0">
<tr align='center'>
	<td>
		<? echo $msg_erro; ?>
	</td>
</tr>
</table>
<?	} ?> 

<? 
	if(strlen($msg_ok) == 0){
?>
<p>
<FORM enctype = "multipart/form-data" NAME = "frm_email" METHOD = "post" ACTION = "<? echo $PHP_SELF; ?>">
<INPUT TYPE="hidden" name="email_fabrica" value="<? echo $email_fabrica; ?>">

<TABLE  width='800px' class='formulario' align='center' border='0' cellspacing = '1' cellpadding='3'>
	<tr>
		<td colspan="3" class="titulo_tabela"><?php echo traduz("E-Mail");?></td>
	</tr>
	<TR>
		<TD>
			&nbsp;
		</TD>
		<TD>
			<button type="button" onclick="window.location='mensagem_enviada.php'"><?php echo traduz("Exibir Mensagens Enviadas"); ?></button>
		</TD>
		<TD>
			<button type="button" onclick="window.location='mensagem_agendada.php'"><?php echo traduz("Exibir Mensagens Agendadas");?></button>
		</TD>
	</TR>
</table>
<br>
<TABLE  width='800px' class='formulario' align='center' border='0' cellspacing = '1' cellpadding='3'>

	<TR>
		<TD colspan="4" align='center'><font color='red'><b><?php echo traduz("A T E N Ç Ã O"); ?></b></font></TD>
	</TR>
	<TR>
		<TD colspan="4" align='center'><font color='red'><b><?php echo traduz("Os emails cadastrados nesta tela serão agendados para serem disparados durante a madrugada a partir da 1h.");?></b></font></TD>
	</TR>
	<br>
	<TR>
		<td width='10%'>&nbsp;</td>
		<TD align='left'><?=($login_fabrica == 117)? traduz("Macro - Família"): traduz("Linha") ?></TD>
		<TD colspan="2" align='left'><?php echo traduz("E-Mail remetente");?></TD>
	</TR>
	<TR align='left'>
	<td width='10%'>&nbsp;</td>
		<TD>
		<?
        if ($login_fabrica == 117) {
                $sql = "SELECT DISTINCT tbl_linha.linha,
               tbl_linha.nome
            FROM tbl_linha
                JOIN tbl_macro_linha_fabrica ON tbl_linha.linha = tbl_macro_linha_fabrica.linha
                JOIN tbl_macro_linha ON tbl_macro_linha_fabrica.macro_linha = tbl_macro_linha.macro_linha
            WHERE tbl_macro_linha_fabrica.fabrica = $login_fabrica
                AND     tbl_linha.ativo = TRUE
            ORDER BY tbl_linha.nome;";
        } else {		
			$sql = "SELECT * 
					FROM tbl_linha 
					WHERE fabrica = $login_fabrica";
		}
			$res = pg_exec ($con,$sql);

			if (pg_numrows ($res) > 0) {
				echo "<select class='frm' name='linha' size='1'>";
				echo "<option value=''>Todos</option>";
				
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
		<TD align='left'>
		<INPUT type="text" name="email_remetente" size="40" value="<? echo $email_remetente ?>" class="frm">
		</TD>
	</TR>

	<TR align='left'>
	<td width='10%'>&nbsp;</td>
		<TD colspan="2">
			<?php echo traduz("Assunto");?>
		</TD>
	</TR>
	<TR >
	<td width='10%'>&nbsp;</td>
		<TD colspan="2"  align='left'>
			<INPUT type="text" name="assunto" style='width: 87%' value="<? echo $assunto ?>" class="frm">
		</TD>
	</TR>
	<TR>
	<td width='10%'>&nbsp;</td>
		<TD colspan="2" >
			<?php echo traduz("Mensagem");?>
		</TD>
		</TR>
	<TR >
	<td width='10%'>&nbsp;</td>
		<TD colspan='2' align='left'>
			<TEXTAREA NAME="mens_corpo"  ID="mens_corpo"  ><? echo ($mens_corpo)?></TEXTAREA>
		</TD>
	</TR>
	<TR>
	<td width='10%'>&nbsp;</td>
		<TD>
			<?php echo traduz("Arquivo");?>
		</TD>
		<TD align='left'>
			<INPUT TYPE="file" NAME="arquivo" size='65' class='frm' >
		</TD>
	</TR> 
	<tr><td width='10%'>&nbsp;</td></tr>
	<TR >
	<td width='10%'>&nbsp;</td>
		<TD colspan="4" align='center'>
			<input type='hidden' name='btn_acao' value=''>
			<input type="button" style='background:url(imagens_admin/btn_confirmar.gif); width:95px; cursor:pointer;' value="&nbsp;" onclick="javascript: if (document.frm_email.btn_acao.value == '' ) { document.frm_email.btn_acao.value='confirmar' ; document.frm_email.submit() } else { alert ('<?php echo traduz("Aguarde submissão");?>') }" ALT="Confirmar formulário" border='0' style="cursor:pointer;" onclick="javascript: document.frm_email.btn_acao.value='confirmar' ; document.frm_email.submit() ;" >
			<input type="button" style="background:url(imagens_admin/btn_limpar.gif); width:75px; cursor:pointer;" value="&nbsp;" onclick="javascript: if (document.frm_email.btn_acao.value == '' ) { document.frm_email.btn_acao.value='reset' ; window.location='<? echo $PHP_SELF ?>' } else { alert (<?php echo traduz("Aguarde submissão");?>) }" ALT="Limpar campos" border='0' style="cursor:pointer;">
		</TD>
	</TR>
</TABLE>

</FORM>
</center>

<?

}else{
	echo "<br><br>";
	echo "<font size='2'><b>$msg_ok</b></font>";
	echo "<a href='envio_email.php'>";
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
