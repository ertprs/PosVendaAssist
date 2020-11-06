<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';

$layout_menu = "cadastro";
$title = "Cadastramento de Preços de Mercadorias";
include 'cabecalho.php';
?>

<style type='text/css'>
.texto {
	font-family: arial;
	font-size: 12px;
	text-align: left;
}

a {
	font-family: arial;
	font-size: 12px;
	text-align: left;
}
</style>

<table width='700' border='2' bordercolor='#d9e2ef'>
<tr>
	<td>
		<table width='300' border='0' cellpadding='0' cellspacing='0'>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td class='texto' bgcolor='#D9E8F9'>Descrição:&nbsp;<input type='text' size='25'></td>
		</tr>
		<tr>
			<td align='left' class='texto'><INPUT TYPE="checkbox" NAME="&nbsp;">Listar Peças sem atribuição valor</td>
		</tr>
		<tr>
			<td align='left' class='texto'><INPUT TYPE="checkbox" NAME="&nbsp;">Listar Peças que não constam em tabelas</td>
		</tr>
		<tr>
			<td align='left' class='texto'><INPUT TYPE="checkbox" NAME="&nbsp;">Listar Todas as Peças</td>
		</tr>
		<tr>
			<td><INPUT TYPE="submit" value='Pesquisar'></td>
		</tr>
		</table>
	</td>
</tr>
</table>

<table width='700' border='2' cellpadding='3' cellspacing='0' bordercolor='#d9e2ef'>
<tr bgcolor='#d9e2ef' class='texto' style='font=weight: bold;'>
	<td>CÓDIGO</td>
	<td width='100%'>DESCRIÇÃO</td>
	<td>TABELA 01</td>
	<td>TABELA 02</td>
	<td>TABELA 03</td>
	<td>TABELA 04</td>
</tr>
<tr class='texto'>
	<td>12121212</td>
	<td>Transistor BD540c</td>
	<td><input type='text' size='10'></td>
	<td><input type='text' size='10'></td>
	<td><input type='text' size='10'></td>
	<td><input type='text' size='10'></td>
</tr>
<tr class='texto'>
	<td>12121212</td>
	<td>Transistor BD540c</td>
	<td><input type='text' size='10'></td>
	<td><input type='text' size='10'></td>
	<td><input type='text' size='10'></td>
	<td><input type='text' size='10'></td>
</tr>
<tr class='texto'>
	<td>12121212</td>
	<td>Transistor BD540c</td>
	<td><input type='text' size='10'></td>
	<td><input type='text' size='10'></td>
	<td><input type='text' size='10'></td>
	<td><input type='text' size='10'></td>
</tr>
<tr class='texto'>
	<td>12121212</td>
	<td>Transistor BD540c</td>
	<td><input type='text' size='10'></td>
	<td><input type='text' size='10'></td>
	<td><input type='text' size='10'></td>
	<td><input type='text' size='10'></td>
</tr>
<tr class='texto'>
	<td>12121212</td>
	<td>Transistor BD540c</td>
	<td><input type='text' size='10'></td>
	<td><input type='text' size='10'></td>
	<td><input type='text' size='10'></td>
	<td><input type='text' size='10'></td>
</tr>
<tr class='texto'>
	<td>12121212</td>
	<td>Transistor BD540c</td>
	<td><input type='text' size='10'></td>
	<td><input type='text' size='10'></td>
	<td><input type='text' size='10'></td>
	<td><input type='text' size='10'></td>
</tr>
<tr class='texto'>
	<td>12121212</td>
	<td>Transistor BD540c</td>
	<td><input type='text' size='10'></td>
	<td><input type='text' size='10'></td>
	<td><input type='text' size='10'></td>
	<td><input type='text' size='10'></td>
</tr>
<tr class='texto'>
	<td>12121212</td>
	<td>Transistor BD540c</td>
	<td><input type='text' size='10'></td>
	<td><input type='text' size='10'></td>
	<td><input type='text' size='10'></td>
	<td><input type='text' size='10'></td>
</tr>
<tr class='texto'>
	<td>12121212</td>
	<td>Transistor BD540c</td>
	<td><input type='text' size='10'></td>
	<td><input type='text' size='10'></td>
	<td><input type='text' size='10'></td>
	<td><input type='text' size='10'></td>
</tr>
<tr class='texto'>
	<td>12121212</td>
	<td>Transistor BD540c</td>
	<td><input type='text' size='10'></td>
	<td><input type='text' size='10'></td>
	<td><input type='text' size='10'></td>
	<td><input type='text' size='10'></td>
</tr>
<tr class='texto'>
	<td>12121212</td>
	<td>Transistor BD540c</td>
	<td><input type='text' size='10'></td>
	<td><input type='text' size='10'></td>
	<td><input type='text' size='10'></td>
	<td><input type='text' size='10'></td>
</tr>
<table>
<br />
<table width='700' border='0' cellpadding='0' cellspacing='0'>
<tr>
	<td>&nbsp;</td>
	<td><input type='submit' value='Gravar Alterações'></td>
	<td></td>
	<td><a href='#'>Anterior</a></td>
	<td><a href='#'>01</a> | <a href='#'>02</a> |<a href='#'>03</a></td>
	<td><a href='#'>Próximo</a></td>
</tr>
</table>
<br />
<? include "rodape.php"; ?>

</body>
</html>