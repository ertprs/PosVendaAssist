<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";
?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title>Pesquisa Consumidores.. </title>
<meta http-equiv=pragma content=no-cache>
</head>

<body style="margin: 0px 0px 0px 0px;">
<img src="imagens/pesquisa_consumidor.gif">

<?

if (strlen($_GET["nome"]) > 0) {
	$nome = strtoupper (trim ($_GET["nome"]));
	
	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>nome do consumidor</b>: <i>$nome</i></font>";
	echo "<p>";
	
	$sql = "SELECT      nome, cpf
			FROM        tbl_cliente
			WHERE       nome ILIKE '%$nome%'
			ORDER BY    nome";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows ($res) == 0) {
		echo "<h1>Consumidor '$nome' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('nome.value=\"\",cpf.value=\"\",',2500);";
		echo "</script>";
		exit;
	}
}


if (strlen($_GET["cpf"]) > 0) {
	$nome = strtoupper (trim ($_GET["cpf"]));
	
	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>CPF do consumidor</b>: <i>$cpf</i></font>";
	echo "<p>";
	
	$sql = "SELECT      *
			FROM        tbl_cliente 
			WHERE       cpf ILIKE '%$cpf%'
			ORDER BY    nome";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows ($res) == 0) {
		echo "<h1>C.P.F. '$nome' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('nome.value=\"\",cpf.value=\"\",',2500);";
		echo "</script>";
		exit;
	}
}


if (pg_numrows ($res) > 0 ) {
	echo "<script language='JavaScript'>";
	echo "<!--\n";
	echo "this.focus();\n";
	echo "// -->\n";
	echo "</script>\n";
	
	echo "<table width='100%' border='0'>\n";
	
	for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
		$nome       = trim(pg_result($res,$i,nome));
		$cpf        = trim(pg_result($res,$i,cpf));
		
		echo "<tr>\n";
		echo "<td>\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$cpf</font>\n";
		echo "</td>\n";
		echo "<td>\n";
		echo "<a href=\"javascript: nome.value='" . pg_result ($res,$i,nome) . "' ; cpf.value = '" . pg_result ($res,$i,cpf) . "'; this.close(); \">\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>$nome</font>\n";
		echo "</a>\n";
		echo "</td>\n";
		echo "</tr>\n";
	}
	echo "</table>\n";
}
?>

</body>
</html>