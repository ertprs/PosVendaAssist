<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="auditoria";
include 'autentica_admin.php';

$title = "MENU AUDITORIA";
$layout_menu = "auditoria";
include 'cabecalho.php';
?>




<!--
<br>
<center>
<img src='../imagens/embratel_logo.gif' valign='absmiddle'>
<br>
<font color='#330066'><b>Conclu�da migra��o para EMBRATEL</b>.</font>
<br>
<font size='-1'>
A <b>Telecontrol</b> agradece sua compreens�o. 
<br>Agora com a migra��o para o iDC EMBRATEL teremos 
<br>um site mais veloz, robusto e confi�vel.
</font>
<p>
</center>
-->




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


<br />
<TABLE width="700px" border="0" cellspacing="0" cellpadding="0" bgcolor='#D9E2EF' align = 'center'>
<TR> 
  <TD width='10'><IMG src="imagens/corner_se_laranja.gif"></TD>
  <TD class=cabecalho>AUDITORIA</TD>
  <TD width='10'><IMG src="imagens/corner_sd_laranja.gif"></TD>
</TR>
</TABLE>


<table border='0' width='700px' border='0' cellpadding='0' cellspacing='0' align = 'center'>
<!-- ================================================================== -->
<!-- <tr bgcolor='#f0f0f0'>
	<td width='25'><img src='imagens/pasta25.gif'></td>
	<td nowrap width='260'><a href='linha_cadastro.php' class='menu'>Linhas de Produtos</a></td>
	<td nowrap class='descricao'>Consulta - Inclus�o - Exclus�o de Linha de Produtos.</td>
</tr> -->
<!-- ================================================================== -->
<!-- <tr bgcolor='#fafafa'>
	<td><img src='imagens/pasta25.gif'></td>
	<td><a href='familia_cadastro.php' class='menu'>Fam�lia de Produtos</a></td>
	<td class='descricao'>Consulta - Inclus�o - Exclus�o de Fam�lia de Produtos.</td>
</tr> -->

<tr bgcolor='#D9E2EF'>
	<td colspan='3'><img src='imagens/spacer.gif' height='3'></td>
</tr>
</table>


<table border='0' width='700px' border='0' cellpadding='0' cellspacing='0' align = 'center'>
<!-- ================================================================== -->

<tr bgcolor='#F0F0F0'>
	<td width='25'><img src='imagens/tela25.gif'></td>
	<td nowrap width='260'><a href='postos_usando.php' class='menu'>Servicios Utilizando</a></td>
	<td class='descricao'>Consulta de los servicios que utilizan actualmente el sistema.</td>
</tr>

<!-- ================================================================== -->
<!--<tr bgcolor='#FAFAFA'>
	<td width='25'><img src='imagens/tela25.gif'></td>
	<td nowrap width='260'><a href='postos_nao_usando.php' class='menu'>Servicios NO Utilizando</a></td>
	<td class='descricao'>Consulta de los servicios que NO utilizan actualmente el sistema.</td>
</tr>-->
<!-- ================================================================== -->

<tr bgcolor='#FAFAFA'>
	<td width='25'><img src='imagens/pasta25.gif'></td>
	<td nowrap width='260'><a href='relatorio_os_por_posto_peca.php' class='menu'>OS digitadas</a></td>
	<td class='descricao'>Relaci�n de las OS registradas en el sistema por per�odo.</td>
</tr>
<!-- ================================================================== -->
<tr bgcolor='#F0F0F0'>
	<td width='25'><img src='imagens/pasta25.gif'></td>
	<td nowrap width='260'><a href='gasto_por_posto.php' class='menu'>Gastos por Servicio</a></td>
	<td class='descricao'>Reporte de costos por servicio (mayores y menores costos).</td>
</tr>
<!-- ================================================================== -->
<tr bgcolor='#FAFAFA'>
	<td width='25'><img src='imagens/tela25.gif'></td>
	<td nowrap width='260'><a href='os_mais_tres_pecas.php' class='menu'>OS con 3 piezas o m�s</a></td>
	<td class='descricao'>Reporte de auditoria de OS registradas con 3 piezas o m�s.</td>
</tr>
<!-- ================================================================== -->

<tr bgcolor='#F0F0F0'>
	<td width='25'><img src='imagens/tela25.gif'></td>
	<td nowrap width='260'><a href='posto_login.php' class='menu'>Logar como Servicio</a></td>
	<td class='descricao'>Permite registrarse en el sistema como se fuera el servicio autorizado.</td>
</tr>
<? if($login_fabrica == 20){?>
<tr bgcolor='#FAFAFA'>
	<td width='25'><img src='imagens/tela25.gif'></td>
	<td nowrap width='260'><a href='relatorio_reporte_os.php' class='menu'>Reporte de OS</a></td>
	<td class='descricao'>Reporte de OS digitada, valor de repuesto y mano de obra</td>
</tr>
<? } ?>

<tr bgcolor='#F0F0F0'>
	<td width='25'><img src='imagens/tela25.gif'></td>
	<td nowrap width='260'><a href='aprova_troca_os.php' class='menu'>Aprueba OS de Cambio</a></td>
	<td class='descricao'>Aprobaci�n de las OS de Cambio por los Promotores.</td>
</tr>

<? if($login_fabrica == 20){?>
<tr bgcolor='#FAFAFA'>
	<td width='25'><img src='imagens/tela25.gif'></td>
	<td nowrap width='260'><a href='relatorio_os_fora_garantia.php' class='menu'>Informe Fuera de garant�a Analytics OS</a></td>
	<td class='descricao'>Informe Fuera de garant�a Analytics OS</td>
</tr>
<tr bgcolor='#FAFAFA'>
	<td width='25'><img src='imagens/tela25.gif'></td>
	<td nowrap width='260'><a href='relatorio_os_fora_garantia_sintetico.php' class='menu'>Informe fuera de garant�a el sint�tico</a></td>
	<td class='descricao'>Informe fuera de garant�a el sint�tico</td>
</tr>
<? } ?>
<!--
<tr bgcolor='#FAFAFA'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='posto_linha.php' class='menu'>Relaci�n de Servicios y L�neas</a></td>
	<td class='descricao'>Relaci�n de los servicios autorizados y sus respectivas l�neas de productos trabajadas</td>
</tr>-->
<!-- ================================================================== -->

<tr bgcolor='#D9E2EF'>
	<td colspan='3'><img src='imagens/spacer.gif' height='3'></td>
</tr>
</table>

<br><br>

<? include "rodape.php" ?>

<!-- ============================================================================================= -->
</body>
</html>
