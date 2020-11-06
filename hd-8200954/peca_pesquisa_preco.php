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
<title> Pesquisa Peças pela Lista Básica ... </title>
<meta name="Author" content="">
<meta name="Keywords" content="">
<meta name="Description" content="">
<meta http-equiv=pragma content=no-cache>


	<link href="css/posicionamento.css" rel="stylesheet" type="text/css" />

</head>

<body onblur="setTimeout('window.close()',5500);">

<br>

<img src="imagens/pesquisa_pecas.gif">

<?
$tipo = trim (strtolower ($_GET['tipo']));
$produto_referencia = trim (strtoupper ($_GET['produto']));
$produto_referencia = str_replace (".","",$produto_referencia);
$produto_referencia = str_replace (",","",$produto_referencia);
$produto_referencia = str_replace ("-","",$produto_referencia);
$produto_referencia = str_replace ("/","",$produto_referencia);
$produto_referencia = str_replace (" ","",$produto_referencia);


$sql = "SELECT tbl_produto.produto, tbl_produto.descricao
		FROM   tbl_produto
		JOIN   tbl_linha USING (linha)
		WHERE  UPPER (tbl_produto.referencia_pesquisa) = '$produto_referencia'
		AND    tbl_linha.fabrica = $login_fabrica
		AND    tbl_produto.ativo IS TRUE";
$res = pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {
	$produto_descricao = pg_result ($res,0,descricao);
	$produto = pg_result ($res,0,produto);
}

if ($tipo == "tudo") {
	echo "<h4>Pesquisando toda a lista básica do produto: <br><i>$produto_referencia - $produto_descricao</i></h4>";
	echo "<p>";

	$res = pg_exec ($con,"SELECT COUNT(*) FROM tbl_lista_basica WHERE tbl_lista_basica.fabrica = $login_fabrica");
	$qtde = pg_result ($res,0,0);

	if ($qtde > 0) {
		$sql = "SELECT      tbl_peca.peca       ,
							tbl_peca.referencia ,
							tbl_peca.descricao
				FROM        tbl_peca
				JOIN        tbl_lista_basica USING (peca)
				JOIN        tbl_produto      USING (produto)
				WHERE       tbl_peca.fabrica    = $login_fabrica
				AND         tbl_produto.produto = $produto
				AND         tbl_peca.ativo    IS TRUE
				AND         tbl_produto.ativo IS TRUE
				ORDER BY    tbl_peca.descricao;";
	}else{
		$sql = "SELECT      tbl_peca.peca       ,
							tbl_peca.referencia ,
							tbl_peca.descricao
				FROM        tbl_peca
				WHERE       tbl_peca.fabrica = $login_fabrica
				AND         tbl_peca.ativo   IS TRUE
				ORDER BY    tbl_peca.descricao;";
	}
	$res = pg_exec ($con,$sql);
	
	if (@pg_numrows ($res) == 0) {
		echo "<h1>Nenhuma lista básica de peças encontrada para este produto</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}
}


if ($tipo == "descricao") {
	$descricao = trim (strtoupper($_GET["descricao"]));

	echo "<h4>Pesquisando por <b>descrição da peça</b>: <i>$descricao</i></h4>";
	echo "<p>";

	$res = pg_exec ($con,"SELECT COUNT(*) FROM tbl_lista_basica WHERE tbl_lista_basica.fabrica = $login_fabrica");
	$qtde = pg_result ($res,0,0);

	if ($qtde > 0 AND strlen ($produto) > 0 ) {
		$sql = "SELECT      tbl_peca.peca       ,
							tbl_peca.referencia ,
							tbl_peca.descricao
				FROM        tbl_peca
				JOIN        tbl_lista_basica USING (peca)
				JOIN        tbl_produto      USING (produto)
				WHERE       tbl_peca.fabrica    = $login_fabrica
				AND         tbl_produto.produto = $produto
				AND         tbl_peca.ativo    IS TRUE
				AND         tbl_produto.ativo IS TRUE ";
		if (strlen($descricao) > 0) $sql .= "AND (trim (upper (tbl_peca.descricao)) ILIKE '%$descricao%' OR trim (upper (tbl_peca.referencia)) ILIKE '%$descricao%') ";
		$sql .= "ORDER BY    tbl_peca.descricao;";
	}else{
		$sql = "SELECT      tbl_peca.peca       ,
							tbl_peca.referencia ,
							tbl_peca.descricao
				FROM        tbl_peca
				WHERE       tbl_peca.fabrica = $login_fabrica
				AND         tbl_peca.ativo    IS TRUE ";
		if (strlen($descricao) > 0) $sql .= "AND (trim (upper (tbl_peca.descricao)) ILIKE '%$descricao%' OR trim (upper (tbl_peca.referencia)) ILIKE '%$descricao%') ";
		$sql .= "ORDER BY    tbl_peca.descricao;";
	}
	$res = pg_exec ($con,$sql);
	
	if (@pg_numrows ($res) == 0) {
		echo "<h1>Peça '$descricao' não encontrada<br>para o produto $produto_referencia</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}
}


if ($tipo == "referencia") {
	$referencia = trim (strtoupper($_GET["peca"]));
	$referencia = str_replace (".","",$referencia);
	$referencia = str_replace (",","",$referencia);
	$referencia = str_replace ("-","",$referencia);
	$referencia = str_replace ("/","",$referencia);
	$referencia = str_replace (" ","",$referencia);

	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>referência da peça</b>: <i>$referencia</i></font>";
	echo "<p>";

	$res = pg_exec ($con,"SELECT COUNT(*) FROM tbl_lista_basica WHERE tbl_lista_basica.fabrica = $login_fabrica");
	$qtde = pg_result ($res,0,0);

	if ($qtde > 0) {
		$sql = "SELECT      tbl_peca.peca       ,
							tbl_peca.referencia ,
							tbl_peca.descricao
				FROM        tbl_peca
				JOIN        tbl_lista_basica USING (peca)
				JOIN        tbl_produto      USING (produto)
				WHERE       tbl_peca.fabrica    = $login_fabrica
				AND         tbl_produto.produto = $produto
				AND         tbl_peca.ativo    IS TRUE
				AND         tbl_produto.ativo IS TRUE ";
		if (strlen($referencia) > 0) $sql .= "AND tbl_peca.referencia_pesquisa ILIKE '%$referencia%' ";
		$sql .= "ORDER BY tbl_peca.descricao;";
	}else{
		$sql = "SELECT      tbl_peca.peca       ,
							tbl_peca.referencia ,
							tbl_peca.descricao
				FROM        tbl_peca
				WHERE       tbl_peca.fabrica = $login_fabrica
				AND         tbl_peca.ativo   IS TRUE ";
		if (strlen($referencia) > 0) $sql .= "AND tbl_peca.referencia_pesquisa ILIKE '%$referencia%' ";
		$sql .= "ORDER BY    tbl_peca.descricao;";
	}
	$res = pg_exec ($con,$sql);

	if (@pg_numrows ($res) == 0) {
		echo "<h1>Peça '$referencia' não encontrada<br>para o produto $produto_referencia</h1>";
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
