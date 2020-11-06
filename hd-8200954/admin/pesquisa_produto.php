<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_admin.php";

?>

<html>
<head>
<title> Pesquisa Produtos... </title>
<meta http-equiv=pragma content=no-cache>
</head>

<body>

<script language="JavaScript">
<!--
function retorno(produto,descr) {
	codigo.value=produto;
	descricao.value=descr;
	window.close();
}
// -->
</script>

<br>

<?

if (strlen($_GET["produto"]) > 0) {
	$produto = strtoupper($_GET["produto"]);
	
	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>código do produto</b>: <i>$produto</i></font>";
	echo "<p>";
	
	$sql = "SELECT   tbl_produto.referencia AS produto,
					 tbl_produto.descricao
			FROM     tbl_produto 
			JOIN     tbl_linha ON tbl_produto.linha = tbl_linha.linha
			WHERE    tbl_produto.referencia_pesquisa ilike '%$produto%'
			OR       tbl_produto.descricao  ilike '%$produto%'
			AND      tbl_linha.fabrica = $login_fabrica
			ORDER BY tbl_produto.descricao;";
	$res = pg_exec ($con,$sql);

	if (@pg_numrows ($res) == 0) {
		echo "<h1>Produto '$produto' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('codigo.value=\"\",descricao.value=\"\"',2500);";
		echo "</script>";
		exit;
	}
}


if (@pg_numrows ($res) == 1 ) {
	$produto   = trim(pg_result($res,0,produto));
	$descricao = trim(pg_result($res,0,descricao));
	
	echo "<script language=\"JavaScript\">\n";
	echo "<!--\n";
	echo "codigo.value = '$produto'; \n";
	echo "descricao.value = '$descricao'; \n";
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
		$produto       = trim(pg_result($res,$i,produto));
		$descricao     = trim(pg_result($res,$i,descricao));
		
		echo "<tr>\n";
		
		echo "<td>\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-2' color='#000000'>$produto</font>\n";
		echo "</td>\n";
		
		echo "<td>\n";
		echo "<a href=\"javascript: retorno('$produto','$descricao')\">\n";
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