<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';

if (strlen($_GET["form"]) > 0)	$form = trim($_GET["form"]);

?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title> Pesquisa Produto... </title>
<meta name="Author" content="">
<meta name="Keywords" content="">
<meta name="Description" content="">
<meta http-equiv=pragma content=no-cache>

<script language="JavaScript">
<!--
function retorno(referencia, descricao, serie) {
	f = opener.window.document.<? echo $form; ?>;
	f.produto_referencia.value = referencia;
	f.produto_descricao.value  = descricao;
//	f.produto_serie.value      = serie;
	window.close();
}
// -->
</script>

</head>

<body onblur="setTimeout('window.close()',2500);" topmargin='0' leftmargin='0' marginwidth='0' marginheight='0'>

<img src="imagens/pesquisa_produto.gif">

<br>

<?

$xserie = trim (strtoupper($_GET["campo"]));
$serie = substr($xserie,0,3);

echo "<h4>Pesquisando por <b>número de série do produto</b>: <i>$serie</i></h4>";
echo "<p>";

$sql = "SELECT   *
		FROM     tbl_produto
		JOIN     tbl_linha ON tbl_produto.linha = tbl_linha.linha
		WHERE    tbl_linha.fabrica = $login_fabrica
		AND      tbl_produto.ativo
		AND      tbl_produto.radical_serie ilike '$serie%'
		ORDER BY tbl_produto.descricao;";
$res = pg_exec ($con,$sql);

if (@pg_numrows ($res) == 0) {
	echo "<h1>Produto '$descricao' não encontrado</h1>";
	echo "<script language='javascript'>";
	echo "setTimeout('window.close()',2500);";
	echo "</script>";
	exit;
}

if (pg_numrows ($res) == 1 ) {
	$referencia = trim(pg_result($res,0,referencia));
	$descricao  = trim(pg_result($res,0,descricao));
	$descricao  = str_replace ('"','',$descricao);
	
	echo "<script language=\"JavaScript\">\n";
	echo "<!--\n";
	echo "opener.window.document.$form.produto_referencia.value = '$referencia'; \n";
	echo "opener.window.document.$form.produto_descricao.value  = '$descricao';  \n";
	echo "opener.window.document.$form.produto_serie.value      = '$xserie';     \n";
	echo "window.close();\n";
	echo "// -->\n";
	echo "</script>\n";
}else{
	echo "<script language='JavaScript'>\n";
	echo "<!--\n";
	echo "window.moveTo (100,100);";
	echo "this.focus();\n";
	echo "// -->\n";
	echo "</script>\n";
	
	echo "<table width='100%' border='0'>";

	for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
		$referencia = trim(pg_result($res,$i,referencia));
		$descricao  = trim(pg_result($res,$i,descricao));
		$descricao  = str_replace ('"','',$descricao);
		$serie      = trim(pg_result($res,$i,radical_serie));

		echo "<tr>";
		
		echo "<td>";
		echo "<a href=\"javascript: retorno('$referencia', '$descricao', '$serie')\">";
		echo "<font size='-1'>$referencia</font>";
		echo "</a>";
		echo "</td>";
		
		echo "<td>";
		echo "<font size='-1'>$descricao</font>";
		echo "</td>";

		echo "<td>";
		echo "<font size='-1'>$serie</font>";
		echo "</td>";
		
		echo "</tr>";
	}

	echo "</table>";
}

?>

</body>
</html>
