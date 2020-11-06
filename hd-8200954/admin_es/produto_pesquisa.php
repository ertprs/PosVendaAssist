<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';

include 'cabecalho_pop_produtos.php';
?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title> Buscando Producto... </title>
<meta name="Author" content="">
<meta name="Keywords" content="">
<meta name="Description" content="">
<meta http-equiv=pragma content=no-cache>

	<link href="css/estilo_cad_prod.css" rel="stylesheet" type="text/css" />
	<link href="css/posicionamento.css" rel="stylesheet" type="text/css" />

</head>

<body style="margin: 0px 0px 0px 0px;" onblur="setTimeout('window.close()',2500);">
<img src="../imagens/pesquisa_produtos<? if($sistema_lingua == "ES") echo "_es"; ?>.gif">
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
function retornox(produto, linha, familia, descricao, voltagem, referencia, garantia, mao_de_obra, ativo, off_line) {
	opener.document.frm_produto.produto.value    = produto;
	opener.document.frm_produto.linha.value      = linha;
	opener.document.frm_produto.familia.value    = familia;
	opener.document.frm_produto.descricao.value  = descricao;
	opener.document.frm_produto.voltagem.value   = voltagem;
	opener.document.frm_produto.referencia.value = referencia;
	opener.document.frm_produto.garantia.value   = garantia;
	opener.document.frm_produto.mao_de_obra.value      = mao_de_obra;
	opener.document.frm_produto.ativo.value      = ativo;
	opener.document.frm_produto.off_line.value   = off_line;
	opener.document.frm_produto.linha.focus()
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

	echo "<h4>Buscando por <b>descripción de la herramienta</b>: <i>$descricao</i></h4>";
	echo "<p>";
	
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
			ORDER BY tbl_produto.descricao;";
	$res = pg_exec ($con,$sql);

	if (@pg_numrows ($res) == 0) {
		echo "<h1>Herramienta '$descricao' no encuentrado</h1>";
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
	
	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Buscando por <b>referencia de la herramienta</b>: <i>$referencia</i></font>";
	echo "<p>";
	
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
			ORDER BY tbl_produto.descricao;";
	$res = pg_exec($con,$sql);
	
	if (@pg_numrows ($res) == 0) {
		echo "<h1>Herramienta '$referencia' no encuentrado</h1>";
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
		echo "<tr>\n";
		
		echo "<td>\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$referencia</font>\n";
		echo "</td>\n";


		echo "<td>\n";
		if ($_GET['forma'] == 'reload') {
			echo "<a href=\"javascript: opener.document.location = retorno + '?produto=$produto' ; this.close() ;\" > " ;
		}else{
			if ($login_fabrica == 1) {
				echo "<a href=\"javascript: referencia.value = '$referencia' ; voltagem.value = '$voltagem' ; this.close() ; \" >";
			}else{
				echo "<a href=\"javascript: descricao.value = '$descricao' ; referencia.value = '$referencia' ; this.close() ; \" >";
			}
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
