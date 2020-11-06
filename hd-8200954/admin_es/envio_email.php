<?
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

if ($btn_acao == "confirmar") {

	if (strlen($_POST["email_remetente"]) > 0) {
		$aux_email_remetente = "'". trim($_POST["email_remetente"]) ."'";
	}else{
		$msg_erro = "Informe el correo del remitente.";
	}
	
	if (strlen($_POST["assunto"]) > 0) {
		$aux_assunto = "'". trim($_POST["assunto"]) ."'";
	}else{
		$msg_erro = "Informe el asunto.";
	}

	if (strlen($_POST["mens_corpo"]) > 0) {
		$aux_mens_corpo = "'". trim($_POST["mens_corpo"]) ."'";
	}else{
		$msg_erro = "Informe la mensaje.";
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
			$arquivo_name = "'".$arquivo_name."'";
		
		if (strlen($email) == 0) {
			###INSERE NOVO REGISTRO
			$sql = "INSERT INTO tbl_email_fabrica (
						fabrica   ,
						linha     ,
						de        ,
						assunto   ,
						mensagem  ,
						nome_anexo,
						pais
					) VALUES (
						$login_fabrica          ,
						$aux_linha              ,
						$aux_email_remetente    ,
						$aux_assunto            ,
						$aux_mens_corpo         ,
						$arquivo_name           ,
						'$login_pais'
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
					AND   tbl_posto.pais = '$login_pais'
					AND   tbl_posto_fabrica.credenciamento = 'CREDENCIADO' ";
		}else{
			$sql = "SELECT tbl_posto.email
					FROM tbl_posto
					JOIN tbl_posto_fabrica USING (posto)
					JOIN tbl_posto_linha ON tbl_posto_linha.posto = tbl_posto_fabrica.posto
					WHERE tbl_posto_fabrica.fabrica = $login_fabrica
					AND   tbl_posto.pais = '$login_pais'
					AND   tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
					AND   tbl_posto_linha.linha     = $linha";
		}
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
		
		if(strlen($msg_erro)==0){
			if ($arquivo <> "none") {
				copy($arquivo, "/www/assist/www/email_upload/$email_fabrica"); 
			}
		}
	}
	
	if (strlen ($msg_erro) == 0) {
		###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		
		header ("Location: $PHP_SELF?msg_ok= Correo enviado con éxito");
		exit;
	}else{
		###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS
		
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}//fim msg_erro
}
?>

<?
	$visual_black = "manutencao-admin";
	$layout_menu = "gerencia";
	$title = "Envío de Correo";
	include 'cabecalho.php';

?>

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

</style>

<? 
	if(strlen($msg_erro) > 0){
?>

<table width='700px' align='center' border='0' bgcolor='#FFFFFF' cellspacing="1" cellpadding="0">
<tr align='center'>
	<td class='error'>
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
<!---
<TABLE  width='650px' border='0' align='center' cellspacing = '3' cellpadding='1' bgcolor='#FFFFFF'>
	<TR>
		<TD>
			<a href='mensagem_enviada.php'>Mensajes Enviadas</a>
		</TD>
		<TD>
			<a href='mensagem_agendada.php'>Mensajes Programadas</a>
		</TD>
	</TR>
</TABLE>
-->
<center>
<TABLE width='350px' align='center' border='0' bdcolor='#000000' cellspacing = '1' cellpadding='0' bgcolor='#d9e2ef'>
<tr><td>
<TABLE width='350px' align='center' border='0' cellspacing = '2' cellpadding='3' bgcolor='#FFFFFF'>
<TR class="menu_top" align = center>
	<TD  class="menu_top">LÍNEA</TD>
	<TD colspan="2" class="menu_top">CORREO REMITENTE</TD>
</TR>
<TR class="table_line">
	<TD>
	<?
		$sql = "SELECT * 
				FROM tbl_linha 
				WHERE fabrica = $login_fabrica";
		$res = pg_exec ($con,$sql);

		if (pg_numrows ($res) > 0) {
			echo "<select class='frm' name='linha' size='1'>";
			echo "<option value=''>TODOS</option>";
			
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
	<TD align='center'>
	<INPUT type="text" name="email_remetente" size="35" value="<? echo $email_remetente ?>" class="frm">
	</TD>
</TR>

<TR class="menu_top">
	<TD colspan="2" class="menu_top">
		TEMA
	</TD>
</TR>
<TR class="table_line">
	<TD colspan="2"  align='center'>
		<INPUT type="text" name="assunto" size="35" value="<? echo $assunto ?>" class="frm">
	</TD>
</TR>
<TR>
	<TD colspan="2" class="menu_top">
		MENSAJE
	</TD>
	</TR>
<TR class="table_line">
	<TD colspan='2' align = 'center'>
		<TEXTAREA NAME="mens_corpo" ROWS="7" COLS="60" value = "<? echo $mens_corpo ?>" class="frm" ></TEXTAREA>
	</TD>
</TR>

<TR>
	<TD colspan="2" class="menu_top">
		ARCHIVO
	</TD>
	</TR>
<TR class="table_line">
	<TD colspan="2" align='center'>
		<INPUT TYPE="file" NAME="arquivo" size='60' class='frm'>
	</TD>
</TR> 

<TR class="table_line">
	<TD colspan="2">
		<input type='hidden' name='btn_acao' value=''>
		<img src="imagens_admin/btn_confirmar.gif" onclick="javascript: if (document.frm_email.btn_acao.value == '' ) { document.frm_email.btn_acao.value='confirmar' ; document.frm_email.submit() } else { alert ('Aguarde submisión') }" ALT="Confirmar formulário" border='0' style="cursor:pointer;" onclick="javascript: document.frm_email.btn_acao.value='confirmar' ; document.frm_email.submit() ;" >
		<img src="imagens_admin/btn_limpar.gif" onclick="javascript: if (document.frm_email.btn_acao.value == '' ) { document.frm_email.btn_acao.value='reset' ; window.location='<? echo $PHP_SELF ?>' } else { alert ('Aguarde submisión') }" ALT="Borrar campos" border='0' style="cursor:pointer;">

	</TD>
</TR>
</TABLE>
</td></tr>
</table>

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
