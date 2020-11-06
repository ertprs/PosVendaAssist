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
<img src="imagens/pesquisa_produtos.gif">

<script language="JavaScript">
function retorno(refer,descr){
	opener.document.frm_os.produto_referencia.value = refer;
	opener.document.frm_os.produto_descricao.value  = descr;
	opener.document.frm_os.produto_serie.focus();
	window.close();
}
</script>
<br>

<?

/* inicio da instrucao SQL */
$sql = "SELECT      tbl_produto.referencia,
					tbl_produto.descricao
		FROM        tbl_produto
		JOIN        tbl_linha ON tbl_produto.linha     = tbl_linha.linha
		AND			tbl_linha.fabrica = $login_fabrica ";

if (strlen($_GET["referencia"]) > 0) {

	$referencia = strtoupper($_GET["referencia"]);
	
	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>referência do produto</b>: <i>$referencia</i></font>\n";
	echo "<p>\n";
	
	/* complementação da instrucao SQL com referencia */
	$sql .= "WHERE       (tbl_produto.referencia ilike '%$referencia%' OR tbl_produto.descricao ilike '%$referencia%') ";

	$MostraMensagem = "<h1>Produto '$referencia' não encontrado</h1>\n";

}elseif (strlen($_GET["descricao"]) > 0) {

	$descricao = strtoupper($_GET["descricao"]);
	
	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>descrição do produto</b>: <i>$descricao</i></font>\n";
	echo "<p>\n";

	/* complementação da instrucao SQL com referencia */
	$sql .= "WHERE       (tbl_produto.referencia ilike '%$descricao%' 
						OR tbl_produto.descricao ilike '%$descricao%') ";

	$MostraMensagem = "<h1>Descrição '$descricao' não encontrada</h1>\n";

}else{

	echo "<h1>Produto não encontrado</h1>\n";

}

/* finaliza instrucao SQL */
$sql .= "ORDER BY tbl_produto.descricao";

$res = pg_exec ($con,$sql);
	
if (@pg_numrows ($res) == 0) {

	echo $MostraMensagem;
	echo "<script language='javascript'>\n";
	echo "	setTimeout('window.close()',2500);\n";
	echo "</script>\n";
	exit;

}else{

	echo "<script language='JavaScript'>\n";
	echo "<!--\n";
	echo "	this.focus();\n";
	echo "// -->\n";
	echo "</script>\n";
	
	echo "<table width='100%' border='0'>\n";
	
	for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
		$referencia = trim(pg_result($res,$i,referencia));
		$descricao  = trim(pg_result($res,$i,descricao));
		
		echo "<tr>\n";
		echo "	<td>\n";
		echo "		<font face='Arial, Verdana, Times, Sans' size='-2' color='#000000'>$referencia</font>\n";
		echo "	</td>\n";
		echo "	<td>\n";
		echo "		<a href=\"javascript: retorno('$referencia','$descricao')\">\n";
		echo "		<font face='Arial, Verdana, Times, Sans' size='-2' color='#0000FF'>$descricao</font>\n";
		echo "		</a>\n";
		echo "	</td>\n";
		echo "</tr>\n";
	}
	echo "</table>\n";
}
?>
<br><br>
</body>
</html>