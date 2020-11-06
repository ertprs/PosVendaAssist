<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';

//if (strlen($_GET["form"]) > 0)	$form = trim($_GET["form"]);
?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title> Pesquisa Produto... </title>
<meta name="Author" content="">
<meta name="Keywords" content="">
<meta name="Description" content="">
<meta http-equiv=pragma content=no-cache>

<link href="css/estilo_cad_prod.css" rel="stylesheet" type="text/css" />
<link href="css/posicionamento.css" rel="stylesheet" type="text/css" />

</head>

<body onblur="setTimeout('window.close()',2500);" topmargin='0' leftmargin='0' marginwidth='0' marginheight='0'>

<img src="imagens/pesquisa_produtos.gif">

<br>

<?

$xserie = trim (strtoupper($_GET["campo"]));

$serie = substr($xserie,0,3);

echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>número de série do produto</b>: <i>$serie</i></font>";
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

	echo "<script language='JavaScript'>\n";
	echo "<!--\n";
	echo "this.focus();\n";
	echo "// -->\n";
	echo "</script>\n";

	echo "<table width='100%' border='0'>\n";

	for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
		$referencia = trim(pg_result($res,$i,referencia));
		$descricao  = trim(pg_result($res,$i,descricao));
		$descricao  = str_replace ('"','',$descricao);
		$serie      = trim(pg_result($res,$i,radical_serie));

		echo "<tr>\n";
		
		echo "<td>\n";
//		echo "<a href=\"javascript: Retorno('$referencia', '$descricao', '$serie')\">";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$referencia</font>\n";
//		echo "</a>";
		echo "</td>\n";
		
		echo "<td>\n";
		echo "<a href=\"javascript: descricao.value = '$descricao' ; referencia.value = '$referencia' ; this.close() ; \" >";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>$descricao</font>";
		echo "</a>\n";
		echo "</td>\n";

		echo "<td>\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$serie</font>\n";
		echo "</td>\n";
		
		echo "</tr>\n";
	}
	echo "</table>";

?>

</body>
</html>