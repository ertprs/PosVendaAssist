<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

?>

<form name='pedido' action='pedido_novo4_cadastro.php'>
<table width='240' border='0' cellpadding='0' cellspacing='2'>
<tr>
	<td colspan='2' bgcolor='#000000' align='center'>
		<font color='#ffcc00'>Linhas de Produtos</font>
	</td>
</tr>
<tr>
	<tr><td><a href='pedido_novo3_cadastro.php'>Doce de Leite</a></td></tr>
	<tr><td><a href='pedido_novo3_cadastro.php'>Foundants</a></td></tr>
	<tr><td><a href='pedido_novo3_cadastro.php'>Gelatinas</a></td></tr>
	<tr><td><a href='pedido_novo3_cadastro.php'>Tops</a></td></tr>
	<tr><td><a href='pedido_novo3_cadastro.php'>Confeitos</a></td></tr>
	<tr><td><a href='pedido_novo3_cadastro.php'>Doces de Amendoim</a></td></tr>
</table>
<hr>
<input type='submit' value='Ver Pedido'>
</form>


<form name='fechamento' action='pedido_novo5_cadastro.php'>
<table width='240' border='0' cellpadding='0' cellspacing='2'>
<tr>
	<td colspan='2' bgcolor='#000000' align='center'>
		<font color='#ffcc00'>Resumo do Pedido</font>
	</td>
</tr>
<tr>
	<tr><td>Vlr Pedido:</td><td>1.200,00</td></tr>
	<tr><td>Qtd Caixas:</td><td>120</td></tr>
</table>

<hr>

<br>
<input type='submit' value='Confirmar Pedido'>
</form>