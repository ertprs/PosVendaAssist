<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

?>

<form name='pedido' action='pedido_novo4_cadastro.php'>
<table width='240' border='0' cellpadding='0' cellspacing='2'>
<tr>
	<td colspan='3' bgcolor='#000000' align='center'>
		<font color='#ffcc00'>Produtos</font>
	</td>
</tr>
<tr>
	<tr  bgcolor='#c0c0c0'><td>Produto</a></td><td>Quant.</td><td>Valor</td></tr>
	<tr><td>Paçoca lisa</a></td><td><input type='text' size='04'></td><td><input type='text' size='6'></td></tr>
	<tr><td>Caseira Grossa</a></td><td><input type='text' size='04'></td><td><input type='text' size='6'></td></tr>
	<tr><td>Pé Fino</a></td><td><input type='text' size='04'></td><td><input type='text' size='6'></td></tr>
	<tr><td>Pé Grosso</a></td><td><input type='text' size='04'></td><td><input type='text' size='6'></td></tr>
	<tr><td>Tubitos</a></td><td><input type='text' size='04'></td><td><input type='text' size='6'></td></tr>
	<tr><td>Pé crocante</a></td><td><input type='text' size='04'></td><td><input type='text' size='6'></td></tr>
</table>

<hr>
<input type='submit' value='Registrar Pedido'>
</form>
