<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';
?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title>Pesquisa Contrato.. </title>
<meta http-equiv=pragma content=no-cache>
</head>

<body style="margin: 0px 0px 0px 0px;">
<img src="imagens/pesquisa_consumidor.gif">

<?

if (strlen($_GET["contrato_descricao"]) > 0) {
	$contrato_descricao = strtoupper (trim ($_GET["contrato_descricao"]));

	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>nome do contrato</b>: <i>$contrato_descricao</i></font>";
	echo "<p>";
	$sql = "SELECT  tbl_contrato.contrato          ,
					tbl_contrato.numero_contrato   ,
					tbl_contrato.descricao
			FROM  tbl_contrato
			WHERE  tbl_contrato.descricao     ILIKE '%$contrato_descricao%'
			AND   tbl_contrato.fabrica = $login_fabrica
			ORDER BY tbl_contrato.descricao";

	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) == 0) {
		echo "<h1>Contrato '$contrato_descricao' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}
}elseif (strlen($_GET["numero_contrato"]) > 0) {
	$numero_contrato = strtoupper (trim ($_GET["numero_contrato"]));

	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>Número do contrato</b>: <i>$numero_contrato</i></font>";
	echo "<p>";
	$sql = "SELECT  tbl_contrato.contrato          ,
					tbl_contrato.numero_contrato   ,
					tbl_contrato.descricao
			FROM  tbl_contrato
			WHERE  tbl_contrato.numero_contrato      ILIKE '%$numero_contrato%'
			AND   tbl_contrato.fabrica = $login_fabrica
			ORDER BY tbl_contrato.descricao";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) == 0) {
		echo "<h1>Número '$numero_contrato' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}
}

if (pg_numrows($res) == 1 ) {
	echo "<script language='javascript'>";
	echo "contrato.value     ='".pg_result($res,0,contrato)."'; ";
	echo "numero_contrato.value        ='".str_replace("'","",pg_result($res,0,numero_contrato))."'; ";
	echo "contrato_descricao.value         ='".pg_result($res,0,descricao)."'; ";
	echo "this.close(); ";
	echo "</script>";
	exit;
}

if (pg_numrows ($res) > 0 ) {
	echo "<script language='JavaScript'>";
	echo "<!--\n";
	echo "this.focus();\n";
	echo "// -->\n";
	echo "</script>\n";

	echo "<table width='100%' border='0'>\n";

	for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
		$contrato          = trim(pg_result($res,$i,contrato));
		$numero_contrato   = trim(pg_result($res,$i,numero_contrato));
		$descricao         = trim(pg_result($res,$i,descricao));

		echo "<tr>\n";

		echo "<td>\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$numero_contrato</font>\n";
		echo "</td>\n";

		echo "<td>\n";
		echo "<a href=\"javascript: contrato.value='$contrato' ;  numero_contrato.value='$numero_contrato' ; contrato_descricao.value='$descricao' ; ";
		echo "this.close(); \">\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>$descricao</font>\n";
		echo "</a>\n";
		echo "</td>\n";
		echo "</tr>\n";
	}
	echo "</table>\n";
}

?>


</body>
</html>