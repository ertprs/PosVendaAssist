<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_usuario.php';

if (strlen($_GET["linha"]) > 0)			$linha      = trim($_GET["linha"]);
if (strlen($_GET["formulario"]) > 0)	$formulario = trim($_GET["formulario"]);

?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title> Pesquisa Peça... </title>
<meta name="Author" content="">
<meta name="Keywords" content="">
<meta name="Description" content="">
<meta http-equiv=pragma content=no-cache>

<script language="JavaScript">
<!--
function retorno(referencia, descricao) {
	f = opener.window.document.<? echo $formulario; ?>;
	f.referencia_<? echo $linha; ?>.value = referencia;
	f.descricao_<? echo $linha; ?>.value  = descricao;
	window.close();
}
// -->
</script>

</head>

<body onblur="setTimeout('window.close()',2500);" topmargin='0' leftmargin='0' marginwidth='0' marginheight='0'>

<img src="imagens/pesquisa_peca.gif">

<br>

<?
$tipo = trim (strtolower ($_GET['tipo']));
if ($tipo == "descricao") {
	$descricao = trim (strtoupper($_GET["campo"]));
	
	echo "<h4>Pesquisando por <b>descrição do peça</b>: <i>$descricao</i></h4>";
	echo "<p>";
	
	$sql = "SELECT   tbl_peca.referencia AS peca,
					 tbl_peca.descricao
			FROM     tbl_peca
			WHERE    tbl_peca.descricao ilike '%$peca%'
			AND      tbl_peca.fabrica = $login_fabrica
			ORDER BY tbl_peca.descricao";
	$res = pg_exec ($con,$sql);
	
	if (@pg_numrows ($res) == 0) {
		echo "<h1>Peça '$descricao' não encontrado</h1>";
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
	
	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>referência da peca</b>: <i>$referencia</i></font>";
	echo "<p>";
	
	$sql = "SELECT   tbl_peca.referencia,
					 tbl_peca.descricao
			FROM     tbl_peca
			WHERE    tbl_peca.referencia_pesquisa ilike '%$referencia%'
			AND      tbl_peca.fabrica = $login_fabrica
			ORDER BY tbl_peca.descricao";
	$res = pg_exec ($con,$sql);
	if (@pg_numrows ($res) == 0) {
		echo "<h1>Pela '$referencia' não encontrada</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
	}
}

if (pg_numrows ($res) == 1 ) {
	$referencia = trim(pg_result($res,0,referencia));
	$descricao  = trim(pg_result($res,0,descricao));
	$descricao  = str_replace ('"','',$descricao);
	
	echo "<script language=\"JavaScript\">\n";
	echo "<!--\n";
	echo "opener.window.document.$formulario.referencia_$linha.value = '$referencia'; \n";
	echo "opener.window.document.$formulario.descricao_$linha.value  = '$descricao';   \n";
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
		
		echo "<tr>";
		
		echo "<td>";
		echo "<a href=\"javascript: retorno('$referencia', '$descricao')\">";
		echo "<font size='-1'>$referencia</font>";
		echo "</a>";
		echo "</td>";
		
		echo "<td>";
		echo "<font size='-1'>$descricao</font>";
		echo "</td>";
		
		echo "</tr>";
	}

	echo "</table>";
}

?>

</body>
</html>