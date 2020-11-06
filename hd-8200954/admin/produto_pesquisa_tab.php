<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';

include 'cabecalho_pop_produtos.php';

if (strlen($_GET["linha"]) > 0) {
	$linha = "_".trim($_GET["linha"]);
}

if (strlen($_GET["formulario"]) > 0) {
	$formulario = trim($_GET["formulario"]);
}
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

<script language="JavaScript">
<!--
function retorno(referencia, descricao) {
	f = opener.window.document.<? echo $formulario; ?>;
	f.produto_referencia<? echo $linha; ?>.value = referencia;
	f.produto_descricao<? echo $linha; ?>.value  = descricao;
	window.close();
}
// -->
</script>

</head>

<body onblur="setTimeout('window.close()',2500);" topmargin='0' leftmargin='0' marginwidth='0' marginheight='0'>

<br>

<?
$tipo = trim (strtolower ($_GET['tipo']));
if ($tipo == "descricao") {
	$descricao = trim (strtoupper($_GET["campo"]));
	
	echo "<h4>Pesquisando por <b>descrição do produto</b>: <i>$descricao</i></h4>";
	echo "<p>";
	
	$sql = "SELECT   *
			FROM     tbl_produto
			JOIN     tbl_produto_pais USING (produto)
			JOIN     tbl_linha ON tbl_produto.linha = tbl_linha.linha
			WHERE    (tbl_produto.descricao ilike '%$descricao%' OR tbl_produto.nome_comercial ilike '%$descricao%')
			AND      tbl_linha.fabrica = $login_fabrica
			AND      tbl_produto.ativo
			AND      tbl_produto_pais.pais = '$login_pais'
			AND      tbl_produto.produto_principal
			ORDER BY tbl_produto.descricao;";
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

	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>referência do produto</b>: <i>$referencia</i></font>";
	echo "<p>";

	$sql = "SELECT   tbl_produto.*
			FROM     tbl_produto
			JOIN     tbl_produto_pais USING (produto)
			JOIN     tbl_linha ON tbl_produto.linha = tbl_linha.linha
			WHERE    tbl_produto.referencia_pesquisa ILIKE '%$referencia%'
			AND      tbl_linha.fabrica     = $login_fabrica
			AND      tbl_produto_pais.pais = '$login_pais'
			AND      tbl_produto.ativo
			AND      tbl_produto.produto_principal
			ORDER BY tbl_produto.descricao";
	$res = pg_exec ($con,$sql);
	if (@pg_numrows ($res) == 0) {
		echo "<h1>Produto '$referencia' não encontrado</h1>";
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
	echo "opener.window.document.$formulario.produto_referencia$linha.value = '$referencia'; \n";
	echo "opener.window.document.$formulario.produto_descricao$linha.value  = '$descricao';   \n";
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