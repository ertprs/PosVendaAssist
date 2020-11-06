<?
session_start();
$_SESSION["blackedecker_vista"] = "OK";

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$title = "Comunicados $login_fabrica_nome - Vistas Explodidas";
$layout_menu = "tecnica";

include 'cabecalho.php';
include "javascript_pesquisas.php";

?>

<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

.tipo {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: bold;
	background-color: #D9E2EF
}

.descricao {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	background-color: #FFFFFF
}

.mensagem {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	background-color: #FFFFFF
}

.txt10Normal {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

</style>

<table width="500" align="center">
<tr>
	<td>
		<font face="arial,verdana" color="#000000">
		<p>
		Se você não possui o Acrobat Reader&reg; , instale agora <a href="http://www.adobe.com/products/acrobat/readstep2.html" target="_blank"><img src="http://www.blackdecker.com.br/imagens/acrobat.gif" border="0" align="absmiddle"></a>
		<p>
		Vistas:
		<p>
		<a href='http://www.blackdecker.com.br/vistas_dw.php' target='_blank'><img src='http://www.blackdecker.com.br/imagens/logodw.gif' align='absmiddle' hspace='5' border='0'></a>
		<p>
		<a href='http://www.blackdecker.com.br/vistas_bd.php' target='_blank'><img src='http://www.blackdecker.com.br/imagens/logobd.gif' align='absmiddle' hspace='5' border='0'></a>
		<p>
		<a href='http://www.blackdecker.com.br/vistas_bd_eletro.php' target='_blank'><img src='http://www.blackdecker.com.br/imagens/logobd_eletro.gif' align='absmiddle' hspace='5' border='0'></a>
		<p>
	</td>
</tr>
</table>

<? include "rodape.php"; ?>
