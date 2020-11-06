<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
$title = "Menu";

/* $layout_menu = Determina a aba em destaque do MENU ===== */
$layout_menu = "tecnica";

include "cabecalho.php";

?>



<style>
body{
	margin: 0px;
	padding: 0px;
	color: #727272;
	font-weight: normal;
	background-color: #FFFFFF;
	font-size: 12px;
	font-family: Arial, Helvetica, sans-serif;
}
td {	font-family: Arial, Helvetica, sans-serif;}
h1, h2, h3, h4, form, ul, li, body, td, table {
	margin: 0px;
	padding: 0px;
}
img{	border: none;}
A:link, A:visited { TEXT-DECORATION: none;  color: #727272;}
A:hover { TEXT-DECORATION: underline;color: #33CCFF; }
.fundo {
	background-image: url(http://img.terra.com.br/i/terramagazine/fundo.jpg);
	background-repeat: repeat-x;
}
.chapeu {
	font-size: 10px;
	color: #0099FF;
	padding: 2px;
	margin-bottom: 4px;
	margin-top: 10px;
	background-image: url(http://img.terra.com.br/i/terramagazine/tracejado3.gif);
	background-repeat: repeat-x;
	background-position: bottom;
	font-size: 10px;
}
.menu {
	font-size: 10px;
}
hr{ 
	height: 1px;
	margin: 15px 0;
	padding: 0;
	border: 0 none;
	background: #ccc;
}
</style>
<body>

<table width="700" border="0" cellspacing="0" cellpadding="0" align='center'>
	<tr >
		<td  class="chapeu" >Vistas Explididas</td>
	</tr>
	<tr>
		<td valign='top' class='menu'>
			<dd><b>»</b> <a href='comunicado_mostra.php?tipo=Vista+Explodida'>AD</a> <br>
			<dd><b>»</b> <a href='comunicado_mostra.php?tipo=Vista+Explodida'>Metais</a><br>
			<dd><b>»</b> <a href='comunicado_mostra.php?tipo=Vista+Explodida'>Aquecedores</a> <br>
		</td>
	</tr>
	<tr>
		<td class='chapeu' height='2'></td>
	</tr>
</table>
<table width="700" border="0" cellspacing="0" cellpadding="0" align='center'>
	<tr >
		<td  class="chapeu" >Esquemas Elétricos</td>
	</tr>
	<tr>
		<td valign='top' class='menu'>
			<dd><b>»</b> <a href='comunicado_mostra.php?tipo=Vista+Explodida'>AD</a> <br>
			<dd><b>»</b> <a href='comunicado_mostra.php?tipo=Vista+Explodida'>Metais</a><br>
			<dd><b>»</b> <a href='comunicado_mostra.php?tipo=Vista+Explodida'>Aquecedores</a> <br>
		</td>
	</tr>
	<tr>
		<td class='chapeu' height='2'></td>
	</tr>
</table>
<table width="700" border="0" cellspacing="0" cellpadding="0" align='center'>
	<tr >
		<td  class="chapeu" >Alterações Técnicas</td>
	</tr>
	<tr>
		<td valign='top' class='menu'>
			<dd><b>»</b> <a href='comunicado_mostra.php?tipo=Vista+Explodida'>AD</a> <br>
			<dd><b>»</b> <a href='comunicado_mostra.php?tipo=Vista+Explodida'>Metais</a><br>
			<dd><b>»</b> <a href='comunicado_mostra.php?tipo=Vista+Explodida'>Aquecedores</a> <br>
		</td>
	</tr>
	<tr>
		<td class='chapeu' height='2'></td>
	</tr>
</table>


</body>
</html>
