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


	<link href="../css/css.css" rel="stylesheet" type="text/css" />
<!-- 	<link href="css/posicionamento.css" rel="stylesheet" type="text/css" /> -->

</head>

<body onblur="setTimeout('window.close()',2500);">

<br>

<img src="../imagens/pesquisa_pecas.gif">

<?
$tipo = trim (strtolower ($_GET['tipo']));
if(strlen(trim (strtolower ($_GET['fabrica']))) > 0){
	$fabrica = trim (strtolower ($_GET['fabrica']));
}

if(strlen($fabrica) == 0){
	$fabrica = $login_fabrica;
}

if ($tipo == "descricao") {
	$descricao = trim (strtoupper($_GET["campo2"]));
	
	echo "<h4>Pesquisando por <b>descrição da peça</b>: <i>$descricao</i></h4>";
	echo "<p>";
	
	$sql = "SELECT DISTINCT  tbl_peca.*,
				(SELECT tbl_tabela_item.preco FROM tbl_tabela_item WHERE tbl_tabela_item.peca = tbl_peca.peca AND tbl_tabela_item.tabela IN (SELECT tabela FROM tbl_posto_linha WHERE posto = $login_posto) ORDER BY preco DESC LIMIT 1) AS preco
			FROM     tbl_peca
			WHERE    tbl_peca.descricao ilike '%$descricao%'
			AND      tbl_peca.fabrica = $fabrica
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
	$referencia = trim (strtoupper($_GET["campo1"]));
	$referencia = str_replace (".","",$referencia);
	$referencia = str_replace (",","",$referencia);
	$referencia = str_replace ("-","",$referencia);
	$referencia = str_replace ("/","",$referencia);
	$referencia = str_replace (" ","",$referencia);

	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>referência da peça</b>: <i>$referencia</i></font>";
	echo "<p>";
//FOI ADICIONADO  			AND      tlb_peca.ativo IS TRUE POIS SÓ PODE EXIBIR PEÇAS ATIVAS
	$sql = "SELECT   tbl_peca.*,
				(SELECT tbl_tabela_item.preco FROM tbl_tabela_item WHERE tbl_tabela_item.peca = tbl_peca.peca AND tbl_tabela_item.tabela IN (SELECT tabela FROM tbl_posto_linha WHERE posto = $login_posto) ORDER BY preco DESC LIMIT 1) AS preco
			FROM     tbl_peca
			WHERE    tbl_peca.referencia_pesquisa ILIKE '%$referencia%'
			AND      tbl_peca.fabrica = $fabrica
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


if (pg_numrows($res) == 1) {
	echo "<script language='javascript'>";
	echo "referencia.value   = '".str_replace ('"','',trim(pg_result($res,0,referencia)))."';";
	echo "descricao.value = '".trim(pg_result($res,0,descricao))."';";

	$precoTotal =number_format (pg_result ($res,$i,preco) * (1 + (pg_result ($res,$i,ipi) / 100)),2,".",".");

	echo "preco.value = '$precoTotal';";
	echo "this.close();";
	echo "qtde.focus();";
	echo "</script>";
	exit;
}

	echo "<script language='JavaScript'>\n";
	echo "this.focus();\n";
	echo "</script>\n";
	
	echo "<table width='100%' border='0'>\n";
	
	for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
		$peca       = trim(pg_result($res,$i,peca));
		$descricao  = trim(pg_result($res,$i,descricao));
		$referencia = trim(pg_result($res,$i,referencia));
		$preco = trim(pg_result($res,$i,preco));

		$descricao = str_replace ('"','',$descricao);

		echo "<tr>\n";
		
		echo "<td>\n";
		echo "<a href=\"javascript: peca.value='$peca'; referencia.value = '$referencia'; descricao.value = '$descricao'; preco.value = '$preco'; this.close(); qtde.focus();  \" style='color:black;font-size:12px'>";
		echo "$referencia</a>\n";
		echo "</td>\n";

		echo "<td>\n";
		echo "<a href=\"javascript: peca.value='$peca'; referencia.value = '$referencia'; descricao.value = '$descricao'; preco.value = '$preco'; this.close(); qtde.focus();  \" style='color:blue;font-size:12px'>";
		echo "$descricao</a>\n";
		echo "</td>\n";
		
		echo "</tr>\n";
	}
	echo "</table>\n";
?>

</body>
</html>