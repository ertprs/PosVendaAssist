<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro";
include 'autentica_admin.php';

$title = "MENU FINANCIERO";
$layout_menu = "financeiro";
include 'cabecalho.php';
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


<br/>
<TABLE width="700px" border="0" cellspacing="0" cellpadding="0" bgcolor='#D9E2EF' align = 'center'>
<TR> 
  <TD width='10'><IMG src="imagens/corner_se_laranja.gif"></TD>
  <TD class=cabecalho>ADMINISTRACIÓN DE EXTRACTOS</TD>
  <TD width='10'><IMG src="imagens/corner_sd_laranja.gif"></TD>
</TR>
</TABLE>

<table border='0' width='700px' border='0' cellpadding='0' cellspacing='0' align = 'center'>
<tr bgcolor='#f0f0f0'>
	<td width='25'><img src='imagens/marca25.gif'></td>
	<td nowrap width='260'><a href='extrato_consulta.php' class='menu'>Mantenimiento de extractos</a></td>
	<td class='descricao'>Pantalla de administración de los extractos generados (aprobación, reprobación, acunmular, etc).</td>
</tr>
<!--
<tr bgcolor='#fafafa'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='extrato_avulso.php' class='menu'>Lanzamiento avulsos en Extractos</a></td>
	<td class='descricao'>Permite lanzar valores avulsos a los servicios autorizados.</td>
</tr>
-->
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='relatorio_custo_tempo.php' class='menu'>Reporte de costo tiempo por extracto</a></td>
	<td class='descricao'>Reporte de tiempo - costos de reparación, tomando en cuenta las OS de extractos aprobados</td>
</tr>
<tr bgcolor='#fafafa'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='relatorio_extrato_aprovado.php' class='menu'>Reporte de Tiempo de Análise de Extractos</a></td>
	<td class='descricao'>Esto reporte informa la cantidad de tiempo para analise de extractos</td>
</tr>

<tr bgcolor='#D9E2EF'>
	<td colspan='3'><img src='imagens/spacer.gif' height='3'></td>
</tr>
</table>
<BR>
<TABLE width="700px" border="0" cellspacing="0" cellpadding="0" bgcolor='#D9E2EF' align = 'center'>
<TR> 
  <TD width='10'><IMG src="imagens/corner_se_laranja.gif"></TD>
  <TD class=cabecalho>REPORTES</TD>
  <TD width='10'><IMG src="imagens/corner_sd_laranja.gif"></TD>
</TR>
</TABLE>

<table border='0' width='700px' border='0' cellpadding='0' cellspacing='0' align = 'center'>
<tr bgcolor='#f0f0f0'>
	<td width='25'><img src='imagens/marca25.gif'></td>
	<td nowrap width='260'><a href='extrato_pagamento.php' class='menu'>Valores de extractos</a></td>
	<td class='descricao'>Informa todos los extractos y valores a ser pagos para los servicios.</td>
</tr>
<tr bgcolor='#f0f0f0'>
	<td><img src='imagens/marca25.gif'></td>
	<td><a href='posto_extrato_ano.php' class='menu'>Comparativo anual de promedio de extractos</a></td>
	<td class='descricao'>Valor mensual de los extractos del servicio en un período de 12 meses.</td>
</tr>

<tr bgcolor='#D9E2EF'>
	<td colspan='3'><img src='imagens/spacer.gif' height='3'></td>
</tr>
</table>
<p>

<? include "rodape.php" ?>

<!-- ============================================================================================= -->
</body>
</html>
