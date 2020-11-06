<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';
?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title>Pesquisa Revendedores.. </title>
<meta http-equiv=pragma content=no-cache>
</head>

<body style="margin: 0px 0px 0px 0px;">
<img src="imagens/pesquisa_revenda<? if($sistema_lingua == "ES") echo "_es"; ?>.gif">

<?
$mapa_cidade = strtoupper (trim ($HTTP_GET_VARS["mapa_cidade"]));
	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>Cidade</b>: <i>$mapa_cidade</i></font>";
	echo "<p>";
	

	$sql = "SELECT      DISTINCT tbl_posto.cidade
			FROM        tbl_posto_fabrica
			JOIN tbl_posto using(posto)
			WHERE       tbl_posto_fabrica.fabrica = $login_fabrica
			AND         tbl_posto.cidade LIKE '%$mapa_cidade%'
			ORDER BY    tbl_posto.cidade";

	$res = pg_exec ($con,$sql);
	
	if (pg_numrows ($res) == 0) {
		echo "<h1>'$cidade' não encontrada</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
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
		$mapa_cidade      = trim(pg_result($res,$i,cidade));

		echo "<tr>\n";
		
		echo "<td>\n";
		echo "<a href=\"javascript: nome.value='mapa_cidade'; ";
		if ($_GET["proximo"] == "t" ) { echo "proximo.focus(); "; }
		echo "this.close(); \">\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>$mapa_cidade</font>\n";
		echo "</a>\n";
		echo "</td>\n";



		
		echo "</tr>\n";
	}
	echo "</table>\n";
}

?>
</body>
</html>