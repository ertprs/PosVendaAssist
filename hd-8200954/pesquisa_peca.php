<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";
?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title> Pesquisa Peças... </title>
<meta http-equiv=pragma content=no-cache>
</head>


<body style="margin: 0px 0px 0px 0px;">
<img src="imagens/pesquisa_peca.gif">

<script language="JavaScript">
<!--
function retorno(referencia) {
	peca.value = referencia;
	window.close();
}
// -->
</script>

<br>

<?
/*
# verifica se posto pode ver pecas de itens de aparencia
$sql = "SELECT   item_aparencia
		FROM     tbl_posto
		WHERE    posto   = $login_posto
		AND      fabrica = $login_fabrica";
$res = pg_exec ($con,$sql);

if (pg_numrows ($res) == 0) {
	$item_aparencia = pg_result($res,0,item_aparencia);
}
*/
if (strlen($HTTP_GET_VARS["peca"]) > 0) {
	$peca = strtoupper($HTTP_GET_VARS["peca"]);
	
	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>código da peca</b>: <i>$peca</i></font>";
	echo "<p>";
	
	$sql = "SELECT   tbl_peca.referencia AS peca,
					 tbl_peca.descricao
			FROM     tbl_peca
			WHERE    (tbl_peca.referencia_pesquisa ilike '%$peca%' OR tbl_peca.descricao ilike '%$peca%')
			AND      tbl_peca.fabrica = $login_fabrica";
//	if ($item_aparencia == 'f') $sql .= " AND tbl_peca.item_aparencia <> 'f' ";
	$sql .= " ORDER BY tbl_peca.descricao";
	$res = pg_exec ($con,$sql);
	
	if (@pg_numrows ($res) == 0) {
		echo "<h1>Peça '$peca' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('peca.value=\"\"',2500);";
		echo "</script>";
		exit;
	}
}


if (@pg_numrows ($res) == 1 ) {
	$peca   = trim(pg_result($res,0,peca));
	$descricao = trim(pg_result($res,0,descricao));
	
	echo "<script language=\"JavaScript\">\n";
	echo "<!--\n";
	echo "peca.value = '$peca'; \n";
	echo "window.close();\n";
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
		$peca      = trim(pg_result($res,$i,peca));
		$descricao = trim(pg_result($res,$i,descricao));
		
		echo "<tr>\n";
		
		echo "<td>\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-2' color='#000000'>$peca</font>\n";
		echo "</td>\n";
		
		echo "<td>\n";
		echo "<a href=\"javascript: retorno('$peca')\">\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-2' color='#0000FF'>$descricao</font>\n";
		echo "</a>\n";
		echo "</td>\n";
		
		echo "</tr>\n";
	}
	echo "</table>\n";
}
?>

</body>
</html>