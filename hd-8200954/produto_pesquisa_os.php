<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_usuario.php';

header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");
?>
<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title> Pesquisa Produto... </title>
<meta name="Author" content="">
<meta name="Keywords" content="">
<meta name="Description" content="">
<meta http-equiv=pragma content=no-cache>

<link href="css/posicionamento.css" rel="stylesheet" type="text/css" />

<style>
.top{
	background-color: #3366FF;
	color: #fff;
	font: 12px arial;
}
</style>
</head>

<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0'>

<br>

<BR>
<?

	$sua_os = trim (strtoupper($_GET["sua_os"]));
		

	$sql = "SELECT os,
				tbl_os.sua_os,
				tbl_os.serie,
				(select descricao from tbl_defeito_constatado where tbl_defeito_constatado.defeito_constatado = tbl_os.defeito_constatado and tbl_defeito_constatado.fabrica = $login_fabrica) AS defeito_constatado
			FROM  tbl_os
			WHERE tbl_os.sua_os = '$sua_os'
			AND tbl_os.fabrica  = $login_fabrica
			AND tbl_os.posto    = $login_posto";

	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) == 0) {
		echo "<h1>OS $sua_os não encontrada</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		
		exit;
	}

	echo "<script language='JavaScript'>\n";
	echo "<!--\n";
	echo "this.focus();\n";
	echo "-->\n";
	echo "</script>\n";
	

	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>OS</b>: ";
	echo "<i>$sua_os</i></font>";
	echo "<p>";

	echo "<table width='100%' border='0'>";

		echo "<tr class='top'>";
			echo "<td>OS</td>";
			echo "<td>Série</td>";
			echo "<td>Defeito Constatado</td>";
		echo "</tr>";
	for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
		$sua_os             = trim(pg_result($res,$i,sua_os));
		$serie              = trim(pg_result($res,$i,serie));
		$defeito_constatado = trim(pg_result($res,$i,defeito_constatado));

		$cor = '#ffffff';
		if ($i % 2 <> 0) $cor = '#EEEEEE';

		echo "<tr bgcolor='$cor'>";
		
		echo "<td><a href=\"javascript: sua_os.value = '$sua_os' ; serie.value = '$serie' ; defeito_constatado.value = '$defeito_constatado'; ";
		echo " sua_os.focus();this.close() ; \" >";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>$sua_os</font>";
		echo "</A></td>";
		echo "<td><font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>$serie</font></td>";
		echo "<td><font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>$defeito_constatado</font></td>";
		echo "</tr>";
	}
	echo "</table>";
?>

</body>
</html>
