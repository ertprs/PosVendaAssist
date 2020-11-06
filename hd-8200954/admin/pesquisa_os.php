<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_admin.php";

?>

<html>
<head>
<title> Pesquisa Ordem de Serviço... </title>
<meta http-equiv=pragma content=no-cache>
</head>

<body>

<script language="JavaScript">
function retorno(sua_os,data_abertura) {
	opener.window.document.frm_callcenter.sua_os.value        = sua_os;
	opener.window.document.frm_callcenter.data_abertura.value = data_abertura;
	window.close();
}
</script>

<br>

<?

if (strlen($_GET["sua_os"]) > 0) {
	$produto = strtoupper($_GET["sua_os"]);
	
	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>Número de OS</b>: <i>$sua_os</i></font>";
	echo "<p>";
	
	$sql = "SELECT   sua_os       ,
					 to_char(data_abertura, 'DD/MM/YYYY') AS data_abertura
			FROM     tbl_os
			WHERE    fabrica = $login_fabrica
			AND      sua_os = '$sua_os'";
	$res = pg_exec ($con,$sql);

	if (@pg_numrows ($res) == 0) {
		echo "<h1>OS '$sua_os' não encontrada</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('sua_os.value=\"\",data_abertura.value=\"\"',2500);";
		echo "</script>";
		exit;
	}
}


if (@pg_numrows ($res) == 1 ) {
	$sua_os        = trim(pg_result($res,0,sua_os));
	$data_abertura = trim(pg_result($res,0,data_abertura));
	
	echo "<script language=\"JavaScript\">\n";
	echo "<!--\n";
	echo "retorno('$sua_os','$data_abertura')";
	echo "// -->\n";
	echo "</script>\n";
}else{

	echo "<script language='JavaScript'>\n";
	echo "<!--\n";
	echo "this.focus();\n";
	echo "// -->\n";
	echo "</script>\n";
	
	echo "<table width='100%' border='0'>\n";
	
	for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
		$sua_os        = trim(pg_result($res,$i,sua_os));
		$data_abertura = trim(pg_result($res,$i,data_abertura));
		
		echo "<tr>\n";
		
		echo "<td>\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-2' color='#000000'>$sua_os</font>\n";
		echo "</td>\n";
		
		echo "<td>\n";
		echo "<a href=\"javascript: retorno('$sua_os','$data_abertura')\">\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-2' color='#0000FF'>$data_abertura</font>\n";
		echo "</a>\n";
		echo "</td>\n";
		
		echo "</tr>\n";
	}
	echo "</table>\n";
}
?>

</body>
</html>