<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="call_center";
include 'autentica_admin.php';

$linha = $_GET['linha'];

?>

<html>
<head>
<meta http-equiv=pragma content=no-cache>
<title> Pesquisa Produtos... </title>
<link type="text/css" rel="stylesheet" href="css/css.css">

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

<body>
<div id="menu">
	<img src='imagens_admin/pesquisa_produtos.gif'>
</div>

<script language="JavaScript">
<!--
function retorno(referencia, descricao) {
	opener.window.document.forms[0].produto_referencia_<? echo $linha; ?>.value = referencia;
	opener.window.document.forms[0].produto_descricao_<? echo $linha; ?>.value  = descricao;
	opener.window.document.forms[0].produto_qtde_<? echo $linha; ?>.focus();
	window.close();
}

// -->
</script>

<br>

<?

if (strlen($_GET["referencia"]) > 0) {
	$produto = strtoupper($_GET["referencia"]);
	
	echo "<div class='titulo_tabela'Pesquisando por <b>Código do Produto</b>: $produto</div>";
	
	
	$sql = "SELECT   tbl_produto.referencia AS produto ,
					 tbl_produto.descricao             ,
					 tbl_produto.voltagem              ,
					 tbl_produto.ativo
			FROM     tbl_produto 
			JOIN     tbl_linha ON tbl_produto.linha = tbl_linha.linha
			WHERE    tbl_produto.referencia_pesquisa ILIKE '%$produto%'
			OR       tbl_produto.descricao           ILIKE '%$produto%'
			AND      tbl_linha.fabrica = $login_fabrica
			ORDER BY tbl_produto.descricao;";
	$res = pg_exec ($con,$sql);

	if (@pg_numrows ($res) == 0) {
		echo "<h1>Produto '$produto' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('codigo.value=\"\",descricao.value=\"\"',2500);";
		echo "</script>";
		exit;
	}
}

if (strlen($_GET["nome"]) > 0) {
	$produto = strtoupper($_GET["nome"]);
	
	echo "<div class='titulo_tabela'>Pesquisando por <b>Nome do Produto</b>: $produto</div>";
	
	
	$sql = "SELECT   tbl_produto.referencia AS produto ,
					 tbl_produto.descricao             ,
					 tbl_produto.voltagem              ,
					 tbl_produto.ativo
			FROM     tbl_produto 
			JOIN     tbl_linha ON tbl_produto.linha = tbl_linha.linha
			WHERE    tbl_produto.referencia_pesquisa ILIKE '%$produto%'
			OR       tbl_produto.descricao           ILIKE '%$produto%'
			AND      tbl_linha.fabrica = $login_fabrica
			ORDER BY tbl_produto.descricao;";
	$res = pg_exec ($con,$sql);

	if (@pg_numrows ($res) == 0) {
		echo "<h1>Produto '$produto' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('codigo.value=\"\",descricao.value=\"\"',2500);";
		echo "</script>";
		exit;
	}
}


if (@pg_numrows ($res) == 1 ) {
	$produto   = trim(pg_result($res,0,produto));
	$descricao = trim(pg_result($res,0,descricao));
	
	echo "<script language=\"JavaScript\">\n";
	echo "<!--\n";
//	echo "codigo.value = '$produto'; \n";
//	echo "descricao.value = '$descricao'; \n";
	echo "retorno('$produto','$descricao');";
	echo "window.close();\n";
	echo "// -->\n";
	echo "</script>\n";
}else{
	echo "<script language='JavaScript'>\n";
	echo "<!--\n";
	echo "this.focus();\n";
	echo "// -->\n";
	echo "</script>\n";
	
	echo "<table width='100%' border='0' class='tabela'>\n";
	
	for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
		$produto       = trim(pg_result($res,$i,produto));
		$descricao     = htmlspecialchars(trim(pg_result($res,$i,descricao)));
		$voltagem      = trim(pg_result($res,$i,voltagem));
		$ativo         = trim(pg_result($res,$i,ativo));

		if ($ativo == 't') $ativo = "ATIVO";
		else               $ativo = "INATIVO";

		if($i%2==0) $cor = "#F7F5F0"; else $cor = "#F1F4FA";
		
		echo "<tr bgcolor='$cor'>\n";
		
		echo "<td>\n";
		echo "$produto\n";
		echo "</td>\n";
		
		echo "<td>\n";
		echo "<a href=\"javascript: retorno('$produto','$descricao');\">\n";
		echo "$descricao\n";
		echo "</a>\n";
		echo "</td>\n";
		
		echo "<td>\n";
		echo "$voltagem\n";
		echo "</td>\n";

		echo "<td>\n";
		echo "$ativo\n";
		echo "</td>\n";
		
		echo "</tr>\n";
	}
	echo "</table>\n";
}
?>

</body>
</html>