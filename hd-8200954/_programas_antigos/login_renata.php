<?
header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';


if (strlen($_COOKIE["ComunicadoBritania"]) == 0 AND $login_fabrica == 3) {
	header("Location: comunicado_britania_20050719.php");
	exit;
}

/* PASSA PAR�METRO PARA O CABE�ALHO (n�o esquecer ===========*/

/* $title = Aparece no sub-menu e no t�tulo do Browser ===== */
$title = "Telecontrol ASSIST - Gerenciamento de Assist�ncia T�cnica";

/* $layout_menu = Determina a aba em destaque do MENU ===== */
$layout_menu = 'os';

include 'cabecalho_login.php';
?>

<hr>
<h1><? echo $login_nome ?></h1>
<!-- AQUI VAI INSERIDO OS RELAT�RIOS E OS FORMS -->



<!--
<br>
<center>
<img src='imagens/embratel_logo.gif' valign='absmiddle'>
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


<div id="container"><h2><IMG SRC="imagens/bemVindo<? echo $login_fabrica_nome ?>.gif" ALT="Bem Vindo!!!"></h2></div>
<? 


switch (trim ($login_fabrica_nome)) {
	
	

	case "Dynacom":
		include "news_dynacom.php";
	break;
	
	case "Britania":
		$sql = "SELECT COUNT(*) FROM tbl_posto JOIN tbl_posto_linha ON tbl_posto.posto = tbl_posto_linha.posto AND tbl_posto_linha.linha IN (2,4) WHERE tbl_posto.estado = 'SP' AND tbl_posto.posto = $login_posto";
		$res = pg_exec ($con,$sql);
		$qtde = pg_result ($res,0,0);
		if ($qtde > 0) {
			echo "<font face='arial' size='+1'>A <b>TELECONTROL</b> � seu novo Distribuidor de Pe�as BRIT�NIA <br>para as linhas de Eletro Port�teis e Linha Branca</font>";
			echo "<p>";
			echo "<font face='arial' size='-1'>Entre em contato conosco pelo email <a href='mailto:distribuidor@telecontrol.com.br'>distribuidor@telecontrol.com.br</a> <br>ou pelo MSN, usando este mesmo endere�o de email. <br> Telefone (14) 3433-9009 </font>";

			echo "<p>";
		}
		include "news_britania.php";
#		echo "<script language='javascript'>window.open ('britania_informativo_2.html','popup2','toolbar=no, location=no, status=nos, scrollbars=no, directories=no, width=300, height=300, top=50, left=100') ; </script>";
	break;

	case "Meteor":
		include "news_meteor.php";
	break;

	case "Mondial":
		include "news_mondial.php";
	break;

	case "Tectoy":
		include "news_tectoy.php";
	break;

	case "Ibratele":
		include "news_ibratele.php";
	break;

	case "Filizola":
		include "news_filizola.php";
	break;

	case "Telecontrol":
		include "news_telecontrol.php";
	break;

	case "Lenoxx":
		include "news_lenoxx.php";
	break;

	case "Intelbras":
		include "news_intelbras.php";
	break;
	
	case "BlackeDecker":
		include "news_blackdecker-new.php";
	break;

	case "Latina":
		include "news_latina.php";
	break;
}

include "rodape.php";
?>
