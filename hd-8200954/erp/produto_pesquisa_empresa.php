<?php
include "../dbconfig.php";
include "../includes/dbconnect-inc.php";
include 'autentica_usuario_empresa.php';

#include 'cabecalho_pop_produtos.php';

header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");

?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title> Pesquisa Produto... </title>

<meta name="Author" content="">
<meta name="Keywords" content="">
<meta name="Description" content="">
<meta http-equiv=pragma content=no-cache>

<link href="css/posicionamento.css" rel="stylesheet" type="text/css" />

</head>

<!--
<body onblur="javascript: setTimeout('window.close()',2500);" topmargin='0' leftmargin='0' marginwidth='0' marginheight='0'>
-->

<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0'>


<br>

<img src="imagens/pesquisa_produtos.gif">
<BR>
<?
$tipo = trim (strtolower ($_GET['tipo']));
$empresa = trim (strtolower ($_GET['empresa']));

if (strlen($empresa)>0){
	$sql2 = "SELECT marca,fabrica,nome
			FROM   tbl_marca
			WHERE marca=$empresa";
	$res2 = pg_exec ($con,$sql2) ;
	if (pg_numrows($res2)>0) {
		$empresa   = trim(pg_result ($res2,0,fabrica));
		if ($empresa==0) $empresa="NULL";
	}
}

if ($tipo == "descricao") {
	$descricao = trim (strtoupper($_GET["campo"]));

	echo "<h4>Pesquisando por <b>descrição do produto</b>:";

	echo "<i>$descricao</i></h4>";

	echo "<p>";
	$descricao = strtoupper($descricao);

	if (strlen($empresa)==0){
		$empresa = "NULL";
	}


	$sql = "SELECT   tbl_peca.referencia,
					tbl_peca.descricao,
					tbl_peca.ativo,
					tbl_peca_item.valor_venda,
					tbl_estoque.qtde as estoque,
					tbl_estoque_extra.quantidade_entregar,
					to_char(tbl_estoque_extra.data_atualizacao,'DD/MM/YYYY') as data_atualizacao,
					tbl_peca.fabrica
			FROM    tbl_peca
			LEFT JOIN    tbl_peca_item ON tbl_peca_item.peca=tbl_peca.peca
			LEFT JOIN tbl_estoque ON tbl_estoque.peca=tbl_peca.peca
			LEFT JOIN tbl_estoque_extra ON tbl_estoque_extra.peca = tbl_peca.peca
			WHERE   (tbl_peca.fabrica = $login_empresa OR tbl_peca.fabrica = $empresa)
			AND     upper(tbl_peca.descricao) like '%$descricao%'
			ORDER BY descricao";
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

	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>descrição do produto</b>:";

	echo "<i>$referencia</i></font>";
	echo "<p>";

	if (strlen($empresa)==0){
		$empresa = "NULL";
	}

	
	$sql = "SELECT 	tbl_peca.referencia,
					tbl_peca.descricao,
					tbl_peca.ativo,
					tbl_peca_item.valor_venda,
					tbl_estoque.qtde as estoque,
					tbl_estoque_extra.quantidade_entregar,
					to_char(tbl_estoque_extra.data_atualizacao,'DD/MM/YYYY') as data_atualizacao
			FROM    tbl_peca
			JOIN    tbl_peca_item ON tbl_peca_item.peca=tbl_peca.peca
			LEFT JOIN tbl_estoque ON tbl_estoque.peca=tbl_peca.peca
			LEFT JOIN tbl_estoque_extra ON tbl_estoque_extra.peca = tbl_peca.peca
			WHERE   (tbl_peca.fabrica = $login_empresa OR tbl_peca.fabrica = $empresa)
			AND     tbl_peca.referencia = '$referencia'
			ORDER BY descricao";
	if (strlen($empresa)>0){
		$sql .=" AND tbl_linha.fabrica = $empresa ";
	}
	$res = pg_exec ($con,$sql);

	if (@pg_numrows ($res) == 0) {
		echo "<h1>Produto '$referencia' não encontrado</h1>";
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

		$referencia    = trim(pg_result($res,$i,referencia));
		$descricao     = trim(pg_result($res,$i,descricao));
		$ativo         = trim(pg_result($res,$i,ativo));
		
		$descricao = str_replace ('"','',$descricao);
		$descricao = str_replace ("'","",$descricao);

		if ($ativo == 't') {
			$mativo = "ATIVO";
		}else{
			$mativo = "INATIVO";
		}

		$cor = '#ffffff';
		if ($i % 2 <> 0) $cor = '#EEEEEE';

		echo "<tr bgcolor='$cor'>\n";
		
		echo "<td>\n";
		echo "<a href=\"javascript: descricao.value = '$descricao' ; referencia.value = '$referencia' ;  descricao.focus(); this.close() ; \" >";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$referencia</font>\n";
		echo "</A>";
		echo "</td>\n";
		
		echo "<td>\n";
		echo "<a href=\"javascript: descricao.value = '$descricao' ; referencia.value = '$referencia'; descricao.focus(); this.close();\" >";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>$descricao</font>\n";
		echo "</a>\n";
		echo "</td>\n";
	
		echo "<td>\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$voltagem</font>\n";
		echo "</td>\n";
		
		echo "<td>\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$mativo</font>\n";
		echo "</td>\n";
		
		echo "</tr>\n";

	}
	echo "</table>\n";
?>

</body>
</html>
