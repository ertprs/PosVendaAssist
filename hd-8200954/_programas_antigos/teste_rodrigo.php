<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$btn_acao = strtolower ($_POST['gravar']);

if ($btn_acao == "gravar") {

$nome		= $_POST['nome'];
$cpf		= $_POST['cpf'];
$nasc		= $_POST['nasc'];
$salario    = $_POST['salario'];

			$sql = "INSERT INTO tbl_teste_funcionario (
						nome,
						cpf,
						data_nascimento,
						salario
						) VALUES (
						'$nome',
						'$cpf',
						'$nasc',
						'$salario'
					)";

					$res = pg_exec ($con,$sql);

		$msg_erro = pg_errormessage($con);
		$msg_erro = substr($msg_erro,6);
	
		if (strlen ($msg_erro) == 0) {
			header ("teste_rodrigo.php");
			exit;
		}
}
?>

<html>
<body>
<? 
if (strlen ($msg_erro) > 0) {
?>
<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" width = '730'>
<tr>
	<td valign="middle" align="center" class='error'>
		<? echo $msg_erro ?>
	</td>
</tr>
</table>
<? } ?>

<FORM name="frm" METHOD="post" ACTION="teste_rodrigo.php">
<TABLE>
<TR>
	<TD>Nome:</TD>
	<TD><input name="nome" TYPE="text" size = 50></TD>
</TR>
<TR>
	<TD>CPF:</TD>
	<TD><input name="Cpf" TYPE="text" size= 15><br></TD>
</TR>
<TR>
	<TD>Nasc:</TD>
	<TD><input name="nasc" TYPE="text" size=15></TD>
</TR>
<TR>
	<TD>Salário:</TD>
	<TD><input Name="salario" TYPE="text"size=15><br></TD>
</TR>
</TABLE>

 
 <INPUT TYPE="submit" name="gravar" value="gravar">
</form>

</body>

</html>