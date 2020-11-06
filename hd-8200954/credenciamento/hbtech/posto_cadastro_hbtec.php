<?

include "/www/assist/www/dbconfig.php";
include "/www/assist/www/includes/dbconnect-inc.php";


$btn_acao = $_POST['cadastrar'];

if($btn_acao == 'Cadastrar'){
	echo "entrou";

	$email        = trim($_POST['email']);
	$fabricantes  = trim($_POST['fabricantes']);
	$descricao    = trim($_POST['descricao']);

	if(strlen($email) == 0){
		$msg_erro = "Digite o e-mail!";
	}

	if(strlen($descricao) == 0){
		$descricao = "Sem descrição";
	}

	$sql = "SELECT posto FROM tbl_posto WHERE email = '$email'; ";
	$res = pg_exec($con, $sql);

	if(pg_numrows($res) > 0){
		$posto = pg_result($res, 0, posto);
		echo "$posto";
		$sql = "UPDATE tbl_posto_extra SET fabricantes = '$fabricantes', descricao = '$descricao' where posto = '$posto'; ";
		$res = pg_exec($con, $sql);

	}else{
		$msg_erro = "Posto não encontrado, verifique o e-mail se está correto.";
	}
}

if(strlen($msg_erro) > 0){
	echo "$msg_erro";
}

?>



<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<HTML>
<HEAD>
<TITLE> Cadastro Assistências </TITLE>
</HEAD>

<BODY>
<br><br>

<FORM METHOD=POST ACTION="<? $PHP_SELF ?>">

<TABLE align='center' style='font-family: verdana; font-size: 12px;'>
<TR>
	<TD align= 'center' style='font-size: 16px'><b>Cadastro de Assistências Técnicas</b></TD>
</TR>
<TR>
	<TD>
		E-mail:<br>
		<INPUT TYPE="text" size='50' NAME="email">
	</TD>
</TR>
<TR>
	<TD>
		Outros Fabricantes<br>
		<INPUT TYPE="text" size='50' NAME="fabricantes">
	</TD>
</TR>
<TR>
	<TD>
		Descrição<br>
		<TEXTAREA NAME="descricao" ROWS="3" COLS="38"></TEXTAREA>
	</TD>
</TR>
<TR>
	<TD align='center'>
		<INPUT TYPE="submit" name='cadastrar' value='Cadastrar'>
	</TD>
</TR>
</TABLE>
</FORM>





</BODY>
</HTML>
