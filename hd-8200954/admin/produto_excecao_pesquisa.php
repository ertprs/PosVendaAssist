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
<style>
	.titulo_tabela {
				background-color: #D9E2EF;
				font: bold 14px "Arial";
				color: #596d9b;
				text-align: center;
				margin: 10px;
				padding: 5px;
	}
	.titulo_tr{
		background-color: #596d9b;
		font: bold 14px "Arial";
		color: #FFFFFF;
		text-align: center;
		padding: 5px;
	}
	.atencao{
			font: bold 14px "Arial";
			text-align: center;
			margin: 10px;
			padding: 5px;
			color: #8a6d3b;
			background-color: #fcf8e3;
			border-color: #faebcc;
			border: 1px solid;

	}
</style>
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

	echo "<h4 class='titulo_tabela'>Pesquisando por <b>descrição do produto</b>: <i>$descricao</i></h4>";
	echo "<p>";
	
	$sql = "SELECT   *
			FROM     tbl_produto
			JOIN     tbl_linha ON tbl_produto.linha = tbl_linha.linha
			WHERE    tbl_produto.descricao ilike '%$descricao%'
			AND      tbl_linha.fabrica = $login_fabrica
			ORDER BY tbl_produto.descricao;";
	$res = pg_exec ($con,$sql);
	
	if (@pg_numrows ($res) == 0) {
		echo "<h4 class='atencao'>Produto '$descricao' não encontrado</h4>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}
}

if ($tipo == "referencia") {
	$referencia = trim (strtoupper($_GET["campo"]));
	
	echo "<h4 class='titulo_tabela'>Pesquisando por <b>referência do produto</b>: <i>$referencia</i></h4>";
	echo "<p>";

	$condFab = "";
	if ($login_fabrica == 171) {
		$condFab = " OR tbl_produto.referencia_fabrica ilike '%$referencia%'";
	}	

	$sql = "SELECT   *
			FROM     tbl_produto
			JOIN     tbl_linha ON tbl_produto.linha = tbl_linha.linha
			WHERE    (tbl_produto.referencia ilike '%$referencia%' $condFab)
			AND      tbl_linha.fabrica = $login_fabrica
			ORDER BY tbl_produto.descricao;";
	$res = pg_exec ($con,$sql);
	
	if (@pg_numrows ($res) == 0) {
		echo "<h1 class='atencao'>Produto '$referencia' não encontrado</h1>";
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
	
	echo "<table width='100%' align='center' border='1' cellpadding='2' cellspacing='1'>\n";
		echo "<tr class='titulo_tr'>\n";
		if ($login_fabrica == 171) {
			echo "<td>Referência Fábrica</td>\n";
		}
		echo "<td>Referência</td>\n";
		echo "<td>Descrição</td>\n";
		echo "<td>Mão de Obra</td>\n";

		echo "</tr>\n";
	
	for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
		$produto    = trim(pg_result($res,$i,produto));
		$descricao  = trim(pg_result($res,$i,descricao));
		$referencia = trim(pg_result($res,$i,referencia));
		$referencia_fabrica = trim(pg_result($res,$i,referencia_fabrica));

		$descricao		= str_replace ('"','',$descricao);
		$referencia		= str_replace ('"','',$referencia);
		
		if ($login_fabrica == 171) {
			echo "<td>\n";
			echo "<font face='Arial, Verdana, Times, Sans' size='-2' color='#000000'>$referencia_fabrica</font>\n";
			echo "</td>\n";
		}
		
		
		echo "<td>\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-2' color='#000000'>$referencia</font>\n";
		echo "</td>\n";

		echo "<td>\n";
		if ($_GET['forma'] == 'reload') {
			echo "<a href=\"javascript: opener.document.location = retorno + '?produto=$produto' ; this.close() ;\" > " ;
		}else{
			echo "<a href=\"javascript: descricao.value = '$descricao' ; referencia.value = '$referencia' ;produto.value = $produto; this.close() ; \" >";
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