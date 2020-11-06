<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';

include 'cabecalho_pop_produtos.php';
?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title> Pesquisa Série do Produto... </title>
<meta name="Author" content="">
<meta name="Keywords" content="">
<meta name="Description" content="">
<meta http-equiv=pragma content=no-cache>

	<link href="css/estilo_cad_prod.css" rel="stylesheet" type="text/css" />
	<link href="css/posicionamento.css" rel="stylesheet" type="text/css" />

</head>

<body onblur="setTimeout('window.close()',2500);" topmargin='0' leftmargin='0' marginwidth='0' marginheight='0'>

<br>

<?
	$produto_serie = trim($_GET['produto_serie']);
	echo "<h4>Pesquisando por <b>série do produto</b>: <i>$descricao</i></h4>";
	echo "<p>";

	$sql = "SELECT   *
			FROM     tbl_produto
			JOIN     tbl_numero_serie ON tbl_produto.referencia = tbl_numero_serie.referencia_produto
			WHERE    tbl_numero_serie.serie ilike '%$produto_serie%'
			AND      tbl_numero_serie.fabrica = $login_fabrica
			ORDER BY tbl_produto.descricao;";
	$res = pg_exec ($con,$sql);

	if (@pg_numrows ($res) == 0) {
		echo "<h1>Série '$produto_serie' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}




if (pg_numrows($res) == 1) {

	echo "<script language='JavaScript'>\n";
	echo "descricao.value  = '".str_replace ('"','',trim(pg_result($res,0,descricao)))."';";
	echo "referencia.value = '".trim(pg_result($res,0,referencia))."';";
	echo "serie.value = '".trim(pg_result($res,0,serie))."';";
	echo "this.close();";
	echo "</script>\n";
}

	echo "<script language='JavaScript'>\n";
	echo "<!--\n";
	echo "this.focus();\n";
	echo "// -->\n";
	echo "</script>\n";

	echo "<table width='100%' border='0'>\n";

	for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
		$serie      = trim(pg_result($res,$i,serie));
		$descricao  = trim(pg_result($res,$i,descricao));
		$referencia = trim(pg_result($res,$i,referencia));

		$descricao = str_replace ('"','',$descricao);
		$descricao = str_replace("'","",$descricao);
		$descricao = str_replace("''","",$descricao);

		echo "<tr>\n";

		echo "<td>\n";
		echo "<a href=\"javascript: ";
		echo "descricao.value = '$descricao'; ";
		echo "referencia.value = '$referencia'; ";
		echo "serie.value = '$serie'; ";
		echo "this.close() ; \" >";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$referencia</font>\n";
		echo "</a>\n";
		echo "</td>\n";

		echo "<td>\n";
		echo "<a href=\"javascript: ";
		echo "descricao.value = '$descricao'; ";
		echo "referencia.value = '$referencia'; ";
		echo "serie.value = '$serie'; ";
		echo "this.close() ; \" >";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>$descricao</font>\n";
		echo "</a>\n";
		echo "</td>\n";

		echo "<td>\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$serie</font>\n";
		echo "</td>\n";
		echo "</tr>\n";
	}
	echo "</table>\n";
?>

</body>
</html>