<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

?>
<form name='pedido' action='pedido_novo_cadastro.php'>

<table width='240' border='0' cellpadding='0' cellspacing='2'>
<tr>
	<td colspan='2' bgcolor='#000000' align='center'>
		<font color='#ffcc00'>Cadastramento de Pedidos</font>
	</td>
</tr>
<tr>
	<td>Buscar Cliente</td>
	<td>
		<input type='text' size='20'>
	</td>
</tr>
<tr>
	<td colspan='2' align='center'>Insira no mínimo 3 letras</td>
</tr>

</table>
<br>
<INPUT TYPE="submit" value='Buscar'>

</form>
