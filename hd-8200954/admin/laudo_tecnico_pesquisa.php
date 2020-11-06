<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';

include 'cabecalho_pop_produtos.php';
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

<body style="margin: 0px 0px 0px 0px;" onblur="setTimeout('window.close()',2500);">

<?
if ($_GET['metodo'] == "reload") {
?>
<script language="JavaScript">
<!--
function retornox(produto) {
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
function retornox(produto) {
	opener.document.frm_produto.txt_produto.value = produto;
	opener.document.frm_produto.txt_produto.focus();
	window.close();
}
// -->
</script>

<?
}
?>

<br>

<?
$tipo = trim (strtolower ($_GET['tipo']));
if ($tipo == "descricao") {
	$descricao = trim (strtoupper($_GET["campo"]));

	//echo "<h4>Pesquisando por <b>descrição do produto</b>: <i>$descricao</i></h4>";
	//echo "<p>";
	// HD 17851
	if($login_fabrica == 1){
		$sql_ativo=" AND tbl_produto.ativo IS TRUE ";
	}
	$sql =	"SELECT tbl_produto.produto,
					tbl_produto.descricao,
					tbl_produto.referencia,
					tbl_produto.voltagem,
					tbl_produto.ativo,
					tbl_linha.nome
			FROM    tbl_produto
			JOIN    tbl_linha USING(linha)
			WHERE   (tbl_produto.descricao ILIKE '%$descricao%'
			OR      tbl_produto.nome_comercial ILIKE '%$descricao%')
			AND     tbl_linha.fabrica = $login_fabrica
			$sql_ativo
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
	$referencia = str_replace ("-","",$referencia);
	$referencia = str_replace ("/","",$referencia);
	$referencia = str_replace (" ","",$referencia);
	
	//echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>referência do produto</b>: <i>$referencia</i></font>";
	//echo "<p>";
	if($login_fabrica == 1){
		$sql_ativo="AND tbl_produto.ativo IS TRUE";
	}

	$sql = "SELECT	tbl_produto.produto,
					tbl_produto.descricao,
					tbl_produto.referencia,
					tbl_produto.voltagem,
					tbl_produto.ativo,
					tbl_linha.nome
			FROM     tbl_produto
			JOIN     tbl_linha ON tbl_produto.linha = tbl_linha.linha
			WHERE    tbl_produto.referencia_pesquisa ilike '%$referencia%'
			AND      tbl_linha.fabrica = $login_fabrica
			$sql_ativo
			ORDER BY tbl_produto.descricao;";
	$res = pg_exec($con,$sql);
	
	if (@pg_numrows ($res) == 0) {
		echo "<h1>Produto '$referencia' não encontrado</h1>";
		echo "<script language='javascript'>";
		//echo "setTimeout('opener.window.document.frm_produto.descricao.focus()',2500);";
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
	if($tipo=="descricao")
		echo "<tr class='titulo_tabela'><td colspan='5'><font style='font-size:14px;'>Pesquisando por <b>descrição do produto</b>: $descricao</font></td></tr>";
	if($tipo=="referencia")
		echo "<tr class='titulo_tabela'><td colspan='5'><font style='font-size:14px;'>Pesquisando por <b>referência do produto</b>: $referencia</font></td></tr>";

	echo "<tr class='titulo_coluna'><td>Referência</td><td>Produto</td><td>Voltagem</td><td>Nome</td><td>Status</td>";

	for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
		$produto    = trim(pg_result($res,$i,produto));
		$descricao  = trim(pg_result($res,$i,descricao));
		$referencia = trim(pg_result($res,$i,referencia));
		$voltagem   = trim(pg_result($res,$i,voltagem));
		$nome       = trim(pg_result($res,$i,nome));
		$ativo      = trim(pg_result($res,$i,ativo));

		if ($ativo == 't') {
			$mativo = "Ativo";
		}else if ($ativo == 'f'){
			$mativo = "Inativo";
		}

		$descricao		= str_replace ('"','',$descricao);
		$referencia		= str_replace ('"','',$referencia);
		$linha			= str_replace ('"','',$linha);
		$ativo			= str_replace ('"','',$ativo);
		
		$cor = ($i%2) ? "#F7F5F0" : "#F1F4FA";
		echo "<tr bgcolor='$cor'>\n";
		
		echo "<td>\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$referencia</font>\n";
		echo "</td>\n";


		echo "<td>\n";
		if ($_GET['forma'] == 'reload') {
			echo "<a href=\"javascript: opener.document.location = retorno + '?produto=$produto' ; this.close() ;\" > " ;
		}else{
			echo "<a href=\"javascript:
					opener.document.frm_laudo_tecnico.txt_produto.value = '$referencia';
					opener.document.frm_laudo_tecnico.txt_produto_descricao.value = '$descricao';
					opener.document.frm_laudo_tecnico.cbx_familia.selectedIndex = 0;
					this.close() ; \" >";
		}

		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>$descricao</font>\n";
		echo "</a>\n";
		echo "</td>\n";
		
		echo "<td>\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>$voltagem</font>\n";
		echo "</td>\n";
		
		echo "<td>\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>$nome</font>\n";
		echo "</td>\n";

		echo "<td>\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>$mativo</font>\n";
		echo "</a>\n";
		echo "</td>\n";

		echo "</tr>\n";
	}
	echo "</table>\n";

?>

</body>
</html>