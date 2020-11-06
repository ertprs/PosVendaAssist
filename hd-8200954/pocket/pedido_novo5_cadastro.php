<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

?>

<form name='pedido' action='pedido_novo2_cadastro.php'>
<table width='240' border='0' cellpadding='0' cellspacing='2'>
<tr>
	<td colspan='3' bgcolor='#000000' align='center'>
		<font color='#ffcc00'>Produtos</font>
	</td>
</tr>
<tr>
	<tr  bgcolor='#c0c0c0'><td>Produto</a></td><td>Quant.</td><td>Valor</td></tr>
	<tr><td>Paçoca lisa</a></td><td>10</td><td>60,00</td></tr>
	<tr><td>Caseira Grossa</a></td><td>10</td><td>60,00</td></tr>
	<tr><td>Pé Fino</a></td><td>10</td><td>60,00</td></tr>
	<tr><td>Pé Grosso</a></td><td>10</td><td>60,00</td></tr>
	<tr><td>Tubitos</a></td><td>10</td><td>60,00</td></tr>
	<tr><td>Pé crocante</a></td><td>10</td><td>60,00</td></tr>
	<tr><td>Paçoca lisa</a></td><td>10</td><td>60,00</td></tr>
	<tr><td>Caseira Grossa</a></td><td>10</td><td>60,00</td></tr>
	<tr><td>Pé Fino</a></td><td>10</td><td>60,00</td></tr>
	<tr><td>Pé Grosso</a></td><td>10</td><td>60,00</td></tr>
	<tr><td>Tubitos</a></td><td>10</td><td>60,00</td></tr>
	<tr><td>Pé crocante</a></td><td>10</td><td>60,00</td></tr>
	<tr><td>Paçoca lisa</a></td><td>10</td><td>60,00</td></tr>
	<tr><td>Caseira Grossa</a></td><td>10</td><td>60,00</td></tr>
	<tr><td>Pé Fino</a></td><td>10</td><td>60,00</td></tr>
	<tr></td><td>Pé Grosso</a></td><td>10</td><td>60,00</td></tr>
	<tr><td>Tubitos</a></td><td>10</td><td>60,00</td></tr>
</table>

<hr>
<input type='submit' value='Alterar Pedido'>
<br>

<table width='240' border='0' cellpadding='0' cellspacing='2'>
<tr>
	<td>Pagamento</td>
	<td>
	<select name=''>
		<option value='0'>A Vista</option>
		<option value='1'>28 dd</option>
		<option value='2'>30.60.90dd</option>
	</select>
	</td>
</tr>
<tr>
	<td>Frete</td>
	<td>
	<select name=''>
		<option value='0'>CIF</option>
		<option value='1'>FOB</option>
	</select>
	</td>
</tr>

</table>
<input type='submit' value='Confirmar'>
</form>
