<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';

$title = "MENU GESTIÓN";
$layout_menu = "gerencia";

include 'cabecalho.php';

?>



<!--
<br>
<center>
<img src='../imagens/embratel_logo.gif' valign='absmiddle'>
<br>
<font color='#330066'><b>Concluída migração para EMBRATEL</b>.</font>
<br>
<font size='-1'>
A <b>Telecontrol</b> agradece sua compreensão. 
<br>Agora com a migração para o iDC EMBRATEL teremos 
<br>um site mais veloz, robusto e confiável.
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
  <TD class=cabecalho>CONSULTAS</TD>
  <TD width='10'><IMG src="imagens/corner_sd_laranja.gif"></TD>
</TR>
</TABLE>


<table border='0' width='700px' border='0' cellpadding='0' cellspacing='0' align = 'center'>
<!-- ================================================================== -->
<tr bgcolor='#f0f0f0'>
	<td width='25'><img src='imagens/tela25.gif'></td>
	<td nowrap width='260'><a href='os_consulta_lite.php' class='menu'>Consulta Órdenes de Servicio</a></td>
	<td nowrap class='descricao'>Consulta OS registradas</td>
</tr>
<!-- ================================================================== -->
<tr bgcolor='#D9E2EF'>
	<td colspan='3'><img src='imagens/spacer.gif' height='3'></td>
</tr>
</table>

<br>

<TABLE width="700px" border="0" cellspacing="0" cellpadding="0" bgcolor='#D9E2EF' align = 'center'>
<TR>
  <TD width='10'><IMG src="imagens/corner_se_laranja.gif"></TD>
  <TD class=cabecalho>REPORTES</TD>
  <TD width='10'><IMG src="imagens/corner_sd_laranja.gif"></TD>
</TR>
</TABLE>

<table border='0' width='700px' border='0' cellpadding='0' cellspacing='0' align = 'center'>
<!-- ================================================================== -->
<tr bgcolor='#fafafa'>
	<td><img src='imagens/rel25.gif'></td>
	<td><a href='relatorio_field_call_rate_produto.php' class='menu'>Field Call-Rate - Herramientas</a></td>
	<td class='descricao'>Reporte de fallas de herramientas, tomando en cuenta la fecha de creación del extracto aprobado.</td>
</tr>
<!-- ================================================================== -->
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/rel25.gif'></td>
	<td><a href='relatorio_field_call_rate_pecas_defeitos.php' class='menu'>Field Call-Rate - Piezas</a></td>
	<td class='descricao'>Reporte de fallas de piezas, tomando en cuenta la fecha de creación del extracto aprobado.</td>
</tr>
<!-- ================================================================== -->

<tr bgcolor='#fafafa'>
	<td><img src='imagens/rel25.gif'></td>
	<td><a href='relatorio_quantidade_os.php' class='menu'>Reporte de Cantidad de OS aprobadas por LÍNEA</a></td>
	<td class='descricao'>Reporte de cantidad de OS aprobadas por líneas de productos. </td>
</tr>

<!-- ================================================================== -->
<tr bgcolor='#f0f0f0'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='relatorio_percentual_defeitos.php' class='menu'>Porcentaje de Fallas</a></td>
	<td class='descricao'>Porcentaje de fallas de herramientas por período.</td>
</tr>
<!-- ================================================================== -->
<tr bgcolor='#fafafa'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='relatorio_tempo_conserto.php' class='menu'>Tiempo de reparación</a></td>
	<td class='descricao'>Reporte de tiempo promedio de reparaciones en garantía.</td>
</tr>
<!-- ================================================================== -->
<tr bgcolor='#f0f0f0'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='produtos_mais_demandados.php' class='menu'>Herramientas más demandadas</a></td>
	<td class='descricao'>Reporte de las herramientas más demandadas en OS de los últimos meses.</td>
</tr>

<!-- ================================================================== -->

<tr bgcolor='#fafafa'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='custo_por_os.php' class='menu'>Costo por OS</a></td>
	<td class='descricao'>Costo promedio de reparación en garantía de extractos para cada servicio.</td>
</tr>
<!-- ================================================================== -->
<tr bgcolor='#f0f0f0'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='relatorio_quebra_familia.php' class='menu'>Reporte de fallas por Familia</a></td>
	<td class='descricao'>Reporte de cantidad de fallas durante los últimos 12 meses llevando em cuenta el cierre del extracto de cada mes.</td>
</tr>


<!-- ================================================================== -->
<tr bgcolor='#fafafa'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='relatorio_field_call_rate_os_sem_peca.php' class='menu'>Reporte de OS sin pieza</a></td>
	<td class='descricao'>Reporte de OS sin piezas y sus respectivos defectos reclamados, constatados y solución.</td>
</tr>
<tr bgcolor='#fafafa'>
	<td width='25'><img src='imagens/rel25.gif'></td>
	<td nowrap width='260'><a href='relatorio_posto_peca.php' class='menu'>Reporte de repuesto por servicio</a></td>
	<td class='descricao'>Reporte de acuerdo con la fecha en que la OS fue finalizada.</td>
</tr>
<!-- ================================================================== -->
<tr bgcolor='#D9E2EF'>
	<td colspan='3'><img src='imagens/spacer.gif' height='3'></td>
</tr>
</table>




<br>
<TABLE width="700px" border="0" cellspacing="0" cellpadding="0" bgcolor='#D9E2EF' align = 'center'>
<TR> 
  <TD width='10'><IMG src="imagens/corner_se_laranja.gif"></TD>
  <TD class=cabecalho>TAREAS ADMINISTRATIVAS</TD>
  <TD width='10'><IMG src="imagens/corner_sd_laranja.gif"></TD>
</TR>
</TABLE>

<table border='0' width='700px' border='0' cellpadding='0' cellspacing='0' align = 'center'>
<!-- ================================================================== -->
<tr bgcolor='#f0f0f0'>
	<td width='25'><img src='imagens/tela25.gif'></td>
	<td nowrap width='260'><a href='envio_email.php' class='menu'>Envío de e-mail</a></td>
	<td class='descricao'>Envía mensajes vía e-mail a los servicios</td>
</tr>
<tr bgcolor='#D9E2EF'>
	<td colspan='3'><img src='imagens/spacer.gif' height='3'></td>
</tr>
</table>



<? include "rodape.php" ?>

</body>
</html>
