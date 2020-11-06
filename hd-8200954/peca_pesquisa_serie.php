<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_usuario.php';

#include 'cabecalho_pop_pecas.php';
header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");


?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title> Pesquisa Peças por série... </title>
<meta name="Author" content="">
<meta name="Keywords" content="">
<meta name="Description" content="">
<meta http-equiv=pragma content=no-cache>


<link href="css/css.css" rel="stylesheet" type="text/css" />
<!-- 	<link href="css/posicionamento.css" rel="stylesheet" type="text/css" /> -->

</head>

<body onblur="setTimeout('window.close()',2500);">

<br>

<img src="imagens/pesquisa_pecas.gif">

<?
$serie = trim (strtoupper ($_GET['serie']));

echo "<h4>Pesquisando por <b>série de produção da peça</b>: <i>$serie</i></h4>";
echo "<p>";
	
$sql = "SELECT   tbl_peca.peca, 
				 tbl_peca.descricao,
				 tbl_peca.referencia
		FROM     tbl_peca
		JOIN     tbl_numero_serie on tbl_numero_serie.referencia_produto = tbl_peca.referencia
		WHERE    upper(tbl_numero_serie.serie) = '$serie'
		AND      tbl_peca.fabrica = $login_fabrica
		ORDER BY tbl_peca.descricao;";
if($login_fabrica ==3){
	$sql = "SELECT   tbl_peca.peca, 
					 tbl_peca.descricao,
					 tbl_peca.referencia
			FROM     tbl_peca
			JOIN     tbl_numero_serie_peca on tbl_numero_serie_peca.referencia_peca= tbl_peca.referencia and tbl_numero_serie_peca.fabrica = $login_fabrica
			WHERE    upper(tbl_numero_serie_peca.serie_peca) = '$serie'
			AND      tbl_peca.fabrica = $login_fabrica
			ORDER BY tbl_peca.descricao;";
}
$res = pg_exec ($con,$sql);
//echo $sql;
	
if (@pg_numrows ($res) == 0) {
	echo "<h1>O número de série '$serie' da Peça não foi encontrada</h1>";
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
	$peca       = trim(pg_result($res,$i,peca));
	$descricao  = trim(pg_result($res,$i,descricao));
	$referencia = trim(pg_result($res,$i,referencia));	

	$descricao = str_replace ('"','',$descricao);

	echo "<tr>\n";
	
	echo "<td>\n";
	echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$referencia</font>\n";
	echo "</td>\n";
	
	echo "<td>\n";
	echo "<a href=\"javascript: descricao.value = '$descricao' ; referencia.value = '$referencia' ; this.close() ; \" >";
	echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>$descricao</font>\n";
	echo "</a>\n";
	echo "</td>\n";
	
	echo "</tr>\n";
}
echo "</table>\n";
?>

</body>
</html>