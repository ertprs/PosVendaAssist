<?php
include "../dbconfig.php";
include "../includes/dbconnect-inc.php";
include 'autentica_usuario_empresa.php';

#include 'cabecalho_pop_produtos.php';

header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");

?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<? if ($sistema_lingua=='ES') { ?>
	<title> Busca producto... </title>
<? } else { ?>
	<title> Pesquisa Produto... </title>
<? } ?>
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

<img src="imagens/pesquisa_produtos<? if($sistema_lingua == "ES") echo "_es"; ?>.gif">
<BR>
<?
$tipo = trim (strtolower ($_GET['tipo']));
if ($tipo == "descricao") {
	$descricao = trim (strtoupper($_GET["campo"]));

	if($sistema_lingua == "ES") { 
		echo "<h4>Buscando por <B>referencia del producto</b>:";
	}else{ 
		echo "<h4>Pesquisando por <b>descrição do produto</b>:";
	}
	echo "<i>$descricao</i></h4>";

	echo "<p>";
	$descricao = strtoupper($descricao);

	$sql = "SELECT   tbl_peca.referencia,
					tbl_peca.descricao,
					tbl_peca.ativo
			FROM    tbl_peca
			JOIN    tbl_peca_item USING(peca)
			JOIN    tbl_linha USING(linha)
			JOIN    tbl_familia USING(familia)
			JOIN    tbl_modelo ON tbl_modelo.modelo = tbl_peca_item.modelo
			JOIN    tbl_marca ON tbl_marca.marca = tbl_peca_item.marca
			WHERE   tbl_peca.fabrica = $login_empresa
			AND     tbl_peca.descricao ilike '%$descricao%'";
	$res = pg_exec ($con,$sql);

	if (@pg_numrows ($res) == 0) {
		echo "<h1>Produto '$descricao' não encontrado</h1>";
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

	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>descrição do produto</b>:";

	echo "<i>$referencia</i></font>";
	echo "<p>";

	
	$sql = "SELECT   tbl_peca.referencia,
					tbl_peca.descricao,
					tbl_peca.ativo
			FROM    tbl_peca
			JOIN    tbl_peca_item USING(peca)
			JOIN    tbl_linha USING(linha)
			JOIN    tbl_familia USING(familia)
			JOIN    tbl_modelo ON tbl_modelo.modelo = tbl_peca_item.modelo
			JOIN    tbl_marca ON tbl_marca.marca = tbl_peca_item.marca
			WHERE   tbl_peca.fabrica = $login_empresa
			AND     tbl_peca.referencia ilike '%$referencia%'";
	$res = pg_exec ($con,$sql);

	if (@pg_numrows ($res) == 0) {
		echo "<h1>Produto '$referencia' não encontrado</h1>";
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

		$referencia    = trim(pg_result($res,0,referencia));
		$descricao     = trim(pg_result($res,0,descricao));
		$ativo         = trim(pg_result($res,0,ativo));
		
		$descricao = str_replace ('"','',$descricao);
		$descricao = str_replace ("'","",$descricao);

		if ($ativo == 't') {
			$mativo = "ATIVO";
		}else{
			$mativo = "INATIVO";
		}

		$cor = '#ffffff';
		if ($i % 2 <> 0) $cor = '#EEEEEE';

		echo "<tr bgcolor='$cor'>\n";
		
		echo "<td>\n";
		echo "<a href=\"javascript: descricao.value = '$descricao' ; referencia.value = '$referencia' ;  descricao.focus(); this.close() ; \" >";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$referencia</font>\n";
		echo "</A>";
		echo "</td>\n";
		
		echo "<td>\n";
		echo "<a href=\"javascript: descricao.value = '$descricao' ; referencia.value = '$referencia'; descricao.focus(); this.close();\" >";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>$descricao</font>\n";
		echo "</a>\n";
		echo "</td>\n";
	
		echo "<td>\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$voltagem</font>\n";
		echo "</td>\n";
		
		echo "<td>\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$mativo</font>\n";
		echo "</td>\n";
		
		echo "</tr>\n";

	}
	echo "</table>\n";
?>

</body>
</html>
