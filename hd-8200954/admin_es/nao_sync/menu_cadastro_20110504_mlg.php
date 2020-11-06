<?

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios='cadastros';
include 'autentica_admin.php';

include 'funcoes.php';

$title = "Catastros del sistema";
$layout_menu = "cadastro";
include 'cabecalho.php';

echo $login_master;
?>

<style type="text/css">

body {
	text-align: center;

}

.cabecalho {

	color: black;
	border-bottom: 2px dotted WHITE;
	font-size: 12px;
	font-weight: bold;
}

.descricao {
	padding: 5px;
	color: black;
	font-size: 10px;
	font-weight: normal;
	text-align: justify;
}


/*========================== MENU ===================================*/

a:link.menu {
	padding: 3px;
	display:block;
	font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
	color: navy;
	font-size: 12px;
	font-weight: bold;
	text-align: left;
	text-decoration: none;
}

a:visited.menu {
	padding: 3px;
	display:block;
	font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
	color: navy;
	font-size: 12px;
	font-weight: bold;
	text-align: left;
	text-decoration: none;
}

a:hover.menu {
	padding: 3px;
	display:block;
	font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
	color: black;
	font-size: 12px;
	font-weight: bold;
	text-align: left;
	text-decoration: none;
	background-color: #ced7e7;
}
</style>

<!-- ========================================================================= -->
<br />
<TABLE width="700px" border="0" cellspacing="0" cellpadding="0" bgcolor='#D9E2EF' align = 'center'>
<TR>
  <TD width='10'><IMG src="imagens/corner_se_laranja.gif"></TD>
  <TD class=cabecalho>CATASTROS DE USUARIOS Y OTROS</TD>
  <TD width='10'><IMG src="imagens/corner_sd_laranja.gif"></TD>
</TR>
</TABLE>


<table border='0' width='700px' border='0' cellpadding='0' cellspacing='0' align = 'center'>
<!-- ================================================================== -->
<tr bgcolor='#f0f0f0'>
	<td width='25'><img src='imagens/pasta25.gif'></td>
	<td nowrap width='260'><a href='posto_cadastro.php' class='menu'>Servicios Autorizados</a></td>
	<td nowrap class='descricao'>Catastro de servicios autorizados</td>
</tr>
<!-- ================================================================== -->
<tr bgcolor='#fafafa'>
	<td width='25'><img src='imagens/pasta25.gif'></td>
	<td nowrap width='260'><a href='credenciamento.php' class='menu'>Administración de Servicios</a></td>
	<td nowrap class='descricao'>Administración de Servicios</td>
</tr>
<!-- ================================================================== -->

<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='revenda_cadastro.php' class='menu'>Distribuidor</a></td>
	<td class='descricao'>Catastro de Distribuidores</td>
</tr>
<? if($login_pais == 'CL') { //HD 17542 ?>
<tr bgcolor='#fafafa'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='revenda_cadastro.php' class='menu'>Excepción de Mano de Obra</a></td>
	<td class='descricao'>Catastro de excepciones de mano de obra</td>
</tr>
<? } ?>

<!-- ================================================================== -->
<!-- ================================================================== -->


<!-- ================================================================== -->
<tr bgcolor='#D9E2EF'>
	<td colspan='3'><img src='imagens/spacer.gif' height='3'></td>
</tr>


</table>
<br>

<? include "rodape.php" ?>

</body>
</html>
