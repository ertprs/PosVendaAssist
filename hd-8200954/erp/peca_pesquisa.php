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
<title> Pesquisa Peças... </title>
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
$tipo = trim (strtolower ($_GET['tipo']));
if ($tipo == "descricao") {
	$descricao = trim (strtoupper($_GET["campo"]));
	
	echo "<h4>Pesquisando por <b>descrição da peça</b>: <i>$descricao</i></h4>";
	echo "<p>";
	
	$sql = "SELECT   *
			FROM     tbl_peca
			WHERE    tbl_peca.descricao ilike '%$descricao%'
			AND      tbl_peca.fabrica = $login_fabrica
			ORDER BY tbl_peca.descricao;";
	$res = pg_exec ($con,$sql);
	
	if (@pg_numrows ($res) == 0) {
		echo "<h1>Peça '$descricao' não encontrada</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}
}


if ($tipo == "referencia") {
	$referencia = trim (strtoupper($_GET["campo"]));
	$referencia = str_replace (".","",$referencia);
	$referencia = str_replace (",","",$referencia);
	$referencia = str_replace ("-","",$referencia);
	$referencia = str_replace ("/","",$referencia);
	$referencia = str_replace (" ","",$referencia);

	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>referência da peça</b>: <i>$referencia</i></font>";
	echo "<p>";
//FOI ADICIONADO  			AND      tlb_peca.ativo IS TRUE POIS SÓ PODE EXIBIR PEÇAS ATIVAS
	$sql = "SELECT   *
			FROM     tbl_peca
			WHERE    tbl_peca.referencia_pesquisa ILIKE '%$referencia%'
			AND      tbl_peca.fabrica = $login_fabrica
			AND      tbl_peca.ativo IS TRUE
			ORDER BY tbl_peca.descricao;";
	$res = pg_exec ($con,$sql);
	
	if (@pg_numrows ($res) == 0) {
		echo "<h1>Peça '$referencia' não encontrada</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		
		exit;
	}
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