<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';

include 'cabecalho_pop_subprodutos.php';
?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title> Pesquisa Equipamento... </title>
<meta name="Author" content="">
<meta name="Keywords" content="">
<meta name="Description" content="">
<meta http-equiv=pragma content=no-cache>
	<link href="css/estilo_cad_prod.css" rel="stylesheet" type="text/css" />
	<link href="css/posicionamento.css" rel="stylesheet" type="text/css" />

<style type="text/css">
.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}


.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}

table.tabela tr td{
	font-family: verdana;
	font: bold 11px "Arial";
	border-collapse: collapse;
	border:1px solid #596d9b;
}
</style>
</head>

<!-- <body> -->

<body style="margin: 0px 0px 0px 0px;" onblur="setTimeout('window.close()',2500);">

<?
if ($_GET['metodo'] == "reload") {
?>
<script language="JavaScript">
<!--
function retornox(produto, linha, familia, descricao, voltagem, referencia, garantia, mao_de_obra, ativo, off_line) {
	opener.document.location = "<? echo $_GET['voltar'] ?>" + "?produto=" + produto;
	window.close();
}
// -->
</script>

<?
}else{
?>
<script language="JavaScript">
<!--
function retornox(produto, referencia, descricao) {
	opener.document.frm_subproduto.produto_<? echo $controle ?>.value    = produto;
	opener.document.frm_subproduto.referencia_<? echo $controle ?>.value = referencia;
	opener.document.frm_subproduto.descricao_<? echo $controle ?>.value  = descricao;
	opener.document.frm_subproduto.descricao_<? echo $controle ?>.focus()
	window.close();
}
// -->
</script>

<br>

<?
$tipo = trim(strtolower($_GET['tipo']));

if($tipo == "descricao"){
	$descricao = trim(strtoupper($_GET['campo']));
	
	//echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>descrição do equipamento</b>: <i>$descricao</i></font>";
	//echo "<p>";
	
	$sql = "SELECT   *
			FROM     tbl_produto
			JOIN     tbl_linha ON tbl_produto.linha = tbl_linha.linha
			WHERE    tbl_produto.descricao ilike '%$descricao%'
			AND      tbl_linha.fabrica = $login_fabrica
			ORDER BY tbl_produto.descricao;";
	$res = pg_exec ($con,$sql);
	
	if (@pg_numrows ($res) == 0) {
		echo "<h1>Equipamento '$descricao' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}
}

if($tipo == "referencia"){
	$referencia = trim(strtoupper($_GET['campo']));
	$referencia = str_replace (".","",$referencia);
	$referencia = str_replace ("-","",$referencia);
	$referencia = str_replace ("/","",$referencia);
	$referencia = str_replace (" ","",$referencia);
		
	//echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>referência do equipamento</b>: <i>$referencia</i></font>";
	//echo "<p>";
	
	$sql = "SELECT   *
			FROM     tbl_produto
			JOIN     tbl_linha ON tbl_produto.linha = tbl_linha.linha
			WHERE    tbl_produto.referencia_pesquisa ilike '%$referencia%'
			AND      tbl_linha.fabrica = $login_fabrica
			ORDER BY tbl_produto.descricao;";
	$res = pg_exec ($con,$sql);
	
	if (@pg_numrows ($res) == 0) {
		echo "<h1>Equipamento '$referencia' não encontrado</h1>";
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
	
	echo "<table width='100%' border='0' class='tabela' cellspacing='1'>\n";
	if($tipo=="descricao")
		echo "<tr class='titulo_tabela'><td colspan='2'><font style='font-size:14px;'>Pesquisando por <b>Descrição do Equipamento</b>: $descricao</font></td></tr>";
	if($tipo=="referencia")
		echo "<tr class='titulo_tabela'><td colspan='2'><font style='font-size:14px;'>Pesquisando por <b>Referência do Equipamento</b>: $referencia</b>: $codigo_posto</font></td></tr>";
	echo "<tr class='titulo_coluna'>";
	echo "<td>Código</td><td>Nome</td>";
	for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
		$produto    = trim(pg_result($res,$i,produto));
		$referencia = trim(pg_result($res,$i,referencia));
		$descricao  = trim(pg_result($res,$i,descricao));
		
		if($i%2==0) $cor = "#F7F5F0"; else $cor = "#F1F4FA";
		echo "<tr bgcolor='$cor'>\n";
		
		echo "<td>\n";
		echo "$referencia\n";
		echo "</td>\n";

		echo "<td>\n";
		if ($_GET['forma'] == 'reload') {
			echo "<a href=\"javascript: opener.document.location = retorno + '?subproduto=$produto' ;\" > " ;
		}else{
			echo "<a href=\"javascript: retornox('$produto', '$referencia', '$descricao') ; this.close() ; \" >";
		}

		echo "$descricao\n";
		echo "</a>\n";
		echo "</td>\n";
		
		echo "</tr>\n";
	}
	echo "</table>\n";
}
?>

</body>
</html>