<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";
?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title>Pesquisa Produtos... </title>
<meta http-equiv=pragma content=no-cache>
</head>


<body style="margin: 0px 0px 0px 0px;">
<img src="imagens/pesquisa_produto.gif">



<script language="JavaScript">
<!--
function retorno(produto, referencia, descricao) {
	opener.document.frm_os.codproduto.value         = produto;
	opener.document.frm_os.produto_referencia.value = referencia;
	opener.document.frm_os.produto_descricao.value  = descricao;
	opener.document.frm_os.produto_serie.focus();
	window.close();
}
// -->
</script>

<br>

<?

if (strlen($HTTP_GET_VARS["produto"]) > 0) {
	$produto = strtoupper($HTTP_GET_VARS["produto"]);
	
	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>código do produto</b>: <i>$produto</i></font>";
	echo "<p>";
	
	$sql = "SELECT      tbl_produto.produto   ,
						tbl_produto.referencia,
						tbl_produto.descricao
			FROM        tbl_produto
			JOIN        tbl_linha ON tbl_produto.linha = tbl_linha.linha
			WHERE       tbl_produto.referencia_pesquisa ilike '%$produto%'
			OR          tbl_produto.descricao  ilike '%$produto%'
			AND         tbl_linha.fabrica = $login_fabrica
			ORDER BY    tbl_produto.descricao;";
	$res = pg_exec ($con,$sql);
	
	if (@pg_numrows ($res) == 0) {
		echo "<h1>Produto '$produto' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('produto.value=\"\",codigo.value=\"\",descricao.value=\"\"',2500);";
		echo "</script>";
		exit;
	}
}


if (@pg_numrows ($res) == 1 ) {
	$produto    = trim(pg_result($res,0,produto));
	$referencia = trim(pg_result($res,0,referencia));
	$descricao  = trim(pg_result($res,0,descricao));
	
	echo "<script language=\"JavaScript\">\n";
	echo "<!--\n";
	echo "opener.document.frm_os.codproduto.value         = '$produto';\n";
	echo "opener.document.frm_os.produto_referencia.value = '$referencia';\n";
	echo "opener.document.frm_os.produto_descricao.value  = '$descricao';\n";
	echo "opener.document.frm_os.produto_serie.focus();\n";
	echo "window.close();\n";
	echo "// -->\n";
	echo "</script>\n";
}else{
	echo "<script language='JavaScript'>\n";
	echo "<!--\n";
	echo "this.focus();\n";
	echo "// -->\n";
	echo "</script>\n";
	
	echo "<table width='100%' border='0'>\n";
	
	for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
		$produto    = trim(pg_result($res,$i,produto));
		$referencia = trim(pg_result($res,$i,referencia));
		$descricao  = trim(pg_result($res,$i,descricao));
		
		echo "<tr>\n";
		
		echo "<td>\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-2' color='#000000'>$referencia</font>\n";
		echo "</td>\n";
		
		echo "<td>\n";
		echo "<a href=\"javascript: retorno('$produto','$referencia','$descricao')\">\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-2' color='#0000FF'>$descricao</font>\n";
		echo "</a>\n";
		echo "</td>\n";
		
		echo "</tr>\n";
	}
	echo "</table>\n";
}
?>

</body>
</html>