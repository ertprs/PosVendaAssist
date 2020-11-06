<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';

include 'cabecalho_pop_produtos.php';
?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title> Busca Produto... </title>
<meta name="Author" content="">
<meta name="Keywords" content="">
<meta name="Description" content="">
<meta http-equiv=pragma content=no-cache>

	<link href="css/estilo_cad_prod.css" rel="stylesheet" type="text/css" />
	<link href="css/posicionamento.css" rel="stylesheet" type="text/css" />

</head>

<body style="margin: 0px 0px 0px 0px;" onblur="setTimeout('window.close()',2500);">


<script language="JavaScript">
//<!--
//function retornox(produto, descricao, referencia, mao_de_obra) {
//	opener.document.frm_excecao.produto.value    = produto;
//	opener.document.frm_excecao.descricao.value  = descricao;
//	opener.document.frm_excecao.referencia.value = referencia;
//	opener.document.frm_excecao.mao_de_obra.value = mao_de_obra;
//	opener.document.frm_excecao.mao_de_obra.focus()
//	window.close();
//}
//// -->
</script>

<br>

<?
$tipo = trim (strtolower ($_GET['tipo']));
if ($tipo == "descricao") {
	$descricao = trim (strtoupper($_GET["campo"]));

	echo "<h4>Buscando por <b>descripción del producto</b>: <i>$descricao</i></h4>";
	echo "<p>";
	
	$sql = "SELECT   *
			FROM     tbl_produto
			JOIN     tbl_linha ON tbl_produto.linha = tbl_linha.linha
			JOIN     tbl_produto_pais ON tbl_produto.produto=tbl_produto_pais.produto
			WHERE    tbl_produto.descricao ilike '%$descricao%'
			AND      tbl_linha.fabrica    = $login_fabrica
			AND      tbl_produto_pais.pais= '$login_pais'
			ORDER BY tbl_produto.descricao;";
	$res = pg_exec ($con,$sql);
	
	if (@pg_numrows ($res) == 0) {
		echo "<h1>Producto '$descricao' no encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}
}

if ($tipo == "referencia") {
	$referencia = trim (strtoupper($_GET["campo"]));
	
	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Buscando por <b>referencia del producto</b>: <i>$referencia</i></font>";
	echo "<p>";
	
	$sql = "SELECT   *
			FROM     tbl_produto
			JOIN     tbl_linha ON tbl_produto.linha = tbl_linha.linha
			JOIN     tbl_produto_pais ON tbl_produto.produto=tbl_produto_pais.produto
			WHERE    tbl_produto.referencia ilike '%$referencia%'
			AND      tbl_linha.fabrica    = $login_fabrica
			AND      tbl_produto_pais.pais='$login_pais'
			ORDER BY tbl_produto.descricao;";
	$res = pg_exec ($con,$sql);
	
	if (@pg_numrows ($res) == 0) {
		echo "<h1>Producto '$referencia' no encontrado</h1>";
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
		$produto    = trim(pg_result($res,$i,produto));
		$descricao  = trim(pg_result($res,$i,descricao));
		$referencia = trim(pg_result($res,$i,referencia));

		$descricao		= str_replace ('"','',$descricao);
		$referencia		= str_replace ('"','',$referencia);
		
		echo "<tr>\n";
		
		echo "<td>\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-2' color='#000000'>$referencia</font>\n";
		echo "</td>\n";

		echo "<td>\n";
		if ($_GET['forma'] == 'reload') {
			echo "<a href=\"javascript: opener.document.location = retorno + '?produto=$produto' ; this.close() ;\" > " ;
		}else{
			echo "<a href=\"javascript: descricao.value = '$descricao' ; referencia.value = '$referencia' ; this.close() ; \" >";
		}

		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>$descricao</font>\n";
		echo "</a>\n";
		echo "</td>\n";
		
		echo "<td>\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>$mao_de_obra</font>\n";
		echo "</td>\n";

		echo "</tr>\n";
	}
	echo "</table>\n";
//}
?>

</body>
</html>