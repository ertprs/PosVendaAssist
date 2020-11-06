<?php
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include 'autentica_usuario_empresa.php';

//include ("bdtc.php");

header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");
?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title> Pesquisa Produto... </title>
<meta name="Author" content="">
<meta name="Keywords" content="">
<meta name="Description" content="">
<meta http-equiv=pragma content=no-cache>

<link href="css/posicionamento.css" rel="stylesheet" type="text/css" />

</head>

<!--
<body onblur="javascript: setTimeout('window.close()',2500);" topmargin='0' leftmargin='0' marginwidth='0' marginheight='0'>
-->

<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0'>
<br>

<img src="imagens/pesquisa_produtos.gif">
<?
//conexao com o banco
//$conexao = new bdtc();

$tipo = trim (strtolower ($_GET['tipo']));

if ($tipo == "descricao") {
	$descricao = trim (strtoupper($_GET["campo"]));
	
	echo "<h4>Pesquisando por <b>descrição do produto</b>: <i>$descricao</i></h4>";
	echo "<p>";

	$sql = "SELECT 
				tbl_peca.peca			,
				tbl_peca.descricao		,
				tbl_peca.referencia		,
				tbl_peca_item.status	
			FROM tbl_peca
			JOIN tbl_peca_item using(peca)
			WHERE fabrica = $login_empresa
			  AND tbl_peca.descricao ILIKE '%$descricao%'";

	$res= pg_exec($con, $sql);
	//echo "sql: $sql";
	
	if (@pg_numrows ($res) == 0) {
		echo "<h1>Produto '$descricao' não encontrado..</h1>";
		print_r ($res);
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

	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>referência do produto</b>: <i>$referencia</i></font>";
	echo "<p>";
	
	$sql = "SELECT
				tbl_peca.peca			,
				tbl_peca.descricao		,
				tbl_peca.referencia		,
				tbl_peca_item.status	
			FROM tbl_peca
			JOIN tbl_peca_item using(peca)
			WHERE fabrica = $login_empresa
				AND tbl_peca.referencia ILIKE '%$referencia%'";
	$res = pg_exec ($con,$sql);
	//echo "sql: $sql";

	if (@pg_numrows($res) == 0) {
		echo "<h1>Produto '$referencia' não encontrado.. </h1>";	
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
	
	echo "	<table class='table_line' width='100%' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#D2E4FC'>\n";

	for ( $i = 0 ; $i < @pg_numrows ($res) ; $i++ ) {
		$peca			= trim(pg_result($res,$i,peca));
		$descricao		= trim(pg_result($res,$i,descricao));
		$referencia		= trim(pg_result($res,$i,referencia));
		$status			= trim(pg_result($res,$i,status));

		echo "<tr>\n";
		
		echo "<td>\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$referencia</font>\n";
		echo "</td>\n";
		
		echo "<td>\n";
		echo "<a href=\"javascript: descricao.value = '$descricao' ; referencia.value = '$referencia' ; this.close() ; \" >";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>$descricao</font>\n";
		echo "</a>\n";
		echo "</td>\n";
		echo "<td>\n";
		if($status == "inativo"){
			echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#ff0000'>Bloqueado para Compra</font>\n";	
		}else{
			echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'> </font>\n";	
		}
		echo "</td>\n";
		
		echo "</tr>\n";
	}
	echo "</table>\n";
?>
</body>
</html>