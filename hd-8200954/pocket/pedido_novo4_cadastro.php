<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

?>

<form name='pedido' action='pedido_novo2_cadastro.php'>
<table width='240' border='0' cellpadding='0' cellspacing='2'>
<tr>
	<td colspan='4' bgcolor='#000000' align='center'>
		<font color='#ffcc00'>Produtos</font>
	</td>
</tr>
<tr>
	<tr  bgcolor='#c0c0c0'><td colspan='2'>Produto</a></td><td>Quant.</td><td>Valor</td></tr>
	<tr><td><INPUT TYPE="checkbox" NAME=""></td><td>Paçoca lisa</a></td><td>10</td><td>60,00</td></tr>
	<tr><td><INPUT TYPE="checkbox" NAME=""></td><td>Caseira Grossa</a></td><td>10</td><td>60,00</td></tr>
	<tr><td><INPUT TYPE="checkbox" NAME=""></td><td>Pé Fino</a></td><td>10</td><td>60,00</td></tr>
	<tr><td><INPUT TYPE="checkbox" NAME=""></td><td>Pé Grosso</a></td><td>10</td><td>60,00</td></tr>
	<tr><td><INPUT TYPE="checkbox" NAME=""></td><td>Tubitos</a></td><td>10</td><td>60,00</td></tr>
	<tr><td><INPUT TYPE="checkbox" NAME=""></td><td>Pé crocante</a></td><td>10</td><td>60,00</td></tr>
</table>

<hr>
<input type='submit' value='Excluir Selecionados'>
<br>
<input type='submit' value='Continuar'>
</form>
