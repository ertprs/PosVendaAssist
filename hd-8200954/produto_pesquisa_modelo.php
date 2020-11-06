<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_usuario.php';

if (strlen($_GET["form"]) > 0)	$form = trim($_GET["form"]);

?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title> Pesquisa Produto... </title>
<meta name="Author" content="">
<meta name="Keywords" content="">
<meta name="Description" content="">
<meta http-equiv=pragma content=no-cache>
<style type="text/css">

table.tabela tr td{
    font-family: verdana !important;
    font-size: 11px !important;
    border-collapse: collapse !important;
    border:1px solid #596d9b !important;
}

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

</style>
<script language="JavaScript">
<!--
function retorno(referencia, descricao,referencia_fabrica) {
	f = opener.window.document.<? echo $form; ?>;
	f.produto_referencia.value = referencia;
	f.produto_descricao.value  = descricao;
	f.referencia_fabrica.value = referencia_fabrica;
	window.close();
}
// -->
</script>

</head>



<img src="imagens/pesquisa_produto.gif">

<br>

<?

$xreferencia_fabrica = trim($_GET["campo"]);

echo "<h4>Pesquisando por <b>modelo do produto</b>: <i>$serie</i></h4>";
echo "<p>";

$sql = "SELECT   referencia,descricao,referencia_fabrica
		FROM     tbl_produto
		JOIN     tbl_linha ON tbl_produto.linha = tbl_linha.linha
		WHERE    tbl_linha.fabrica = $login_fabrica
		AND      tbl_produto.ativo
		AND      tbl_produto.referencia_fabrica ilike '$xreferencia_fabrica%'
		ORDER BY tbl_produto.descricao;";
$res = pg_exec ($con,$sql);

if (@pg_numrows ($res) == 0) {
	echo "<h1>Produto de modelo:'$xreferencia_fabrica' não encontrado</h1>";
	echo "<script language='javascript'>";
	echo "setTimeout('window.close()',2500);";
	echo "</script>";
	exit;
}

if (pg_numrows ($res) == 1 ) {
	$referencia = trim(pg_result($res,0,referencia));
	$descricao  = trim(pg_result($res,0,descricao));
	$descricao  = str_replace ('"','',$descricao);
	
	echo "<script language=\"JavaScript\">\n";
	echo "<!--\n";
	echo "opener.window.document.$form.produto_referencia.value = '$referencia'; \n";
	echo "opener.window.document.$form.produto_descricao.value  = '$descricao';  \n";
	echo "opener.window.document.$form.referencia_fabrica.value      = '$xreferencia_fabrica';     \n";
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
	?>
	<table width='100%' border='0' class='tabela' cellpadding='1' cellspacing='1'>
		<tr class="titulo_coluna">
			<td>Referência</td>
			<td>Modelo</td>
			<td>Descrição</td>
		</tr>
	<?
	for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
		$referencia = trim(pg_result($res,$i,'referencia'));
		$descricao  = trim(pg_result($res,$i,'descricao'));
		$descricao  = str_replace ('"','',$descricao);
		$referencia_fabrica      = trim(pg_result($res,$i,'referencia_fabrica'));
		
		$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
		
		echo "<tr bgcolor='$cor'>";
		
			echo "<td>";
			echo "<a href=\"javascript: retorno('$referencia', '$descricao', '$referencia_fabrica')\">";
			echo "<font size='-1'>$referencia</font>";
			echo "</a>";
			echo "</td>";
		
			echo "<td>";
			echo "<a href=\"javascript: retorno('$referencia', '$descricao', '$referencia_fabrica')\">";
			echo "<font size='-1'>$referencia_fabrica</font>";
			echo "</a>";
			echo "</td>";
		
			echo "<td>";
			echo "<a href=\"javascript: retorno('$referencia', '$descricao', '$referencia_fabrica')\">";
			echo "<font size='-1'>$descricao</font>";
			echo "</a>";
			echo "</td>";

		echo "</tr>";
	}

	echo "</table>";
}

?>

</body>
</html>

