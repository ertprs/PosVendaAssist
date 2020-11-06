<?
header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
?>
<html>
<body>

<table width='100%' height='30' border='0' cellpadding='0' cellspacing='0' bgcolor='#000000'>
<tr>
	<td align='center'><font color='ffcc00' face='arial' size='2' align='center'><b><? echo $login_nome ?></b></font></td>
<tr>

</table>

<?

switch (trim ($login_fabrica_nome)) {
	case "Dynacom":
		include "news_dynacom.php";
	break;
	
	case "Britania":
		include "news_britania.php";
		echo "<script language='javascript'>window.open ('britania_informativo.html','popup','toolbar=no, location=no, status=nos, scrollbars=no, directories=no, width=300, height=300, top=50, left=100') ; </script>";
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

	case "LENOXX":
		include "news_lenoxx.php";
	break;
	
}

?>
