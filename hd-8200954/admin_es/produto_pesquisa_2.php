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

<body onblur="setTimeout('window.close()',2500);" topmargin='0' leftmargin='0' marginwidth='0' marginheight='0'>
<img src="../imagens/pesquisa_produtos<? if($sistema_lingua == "ES") echo "_es"; ?>.gif">

<br>

<?
$tipo = trim (strtolower ($_GET['tipo']));
if ($tipo == "descricao") {
	$descricao = trim (strtoupper($_GET["campo"]));
	
	echo "<h4>Buscando por <b>descripción de la herramienta</b>: <i>$descricao</i></h4>";
	echo "<p>";
	
	$sql = "SELECT   *
			FROM     tbl_produto
			JOIN     tbl_produto_pais USING (produto)
			JOIN     tbl_linha ON tbl_produto.linha = tbl_linha.linha
			WHERE    (tbl_produto.descricao ilike '%$descricao%' OR tbl_produto.nome_comercial ilike '%$descricao%')
			AND      tbl_linha.fabrica = $login_fabrica
			AND      tbl_produto_pais.pais = '$login_pais'";
//comentado chamado 230 19-06			AND      tbl_produto.ativo";
	if ($login_fabrica <> 14) $sql .= " AND      tbl_produto.produto_principal";
//comentado chamado 230 honorato	if ($login_fabrica == 14) $sql .= " AND tbl_produto.abre_os IS TRUE ";
	$sql .= " ORDER BY tbl_produto.descricao;";
	$res = pg_exec ($con,$sql);

	if (@pg_numrows ($res) == 0) {
		echo "<h1>Producto '$descricao' no encuentrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}
}


if ($tipo == "referencia") {
	$referencia = trim(strtoupper($_GET["campo"]));
	$referencia = str_replace(".","",$referencia);
	$referencia = str_replace(",","",$referencia);
	$referencia = str_replace("-","",$referencia);
	$referencia = str_replace("/","",$referencia);

	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Buscando por <b>referencia de la herramienta</b>: <i>$referencia</i></font>";
	echo "<p>";

	$sql = "SELECT   tbl_produto.*
			FROM     tbl_produto
			JOIN     tbl_produto_pais USING (produto)
 			JOIN     tbl_linha ON tbl_produto.linha = tbl_linha.linha
			WHERE    (tbl_produto.referencia_pesquisa LIKE '%$referencia%' OR tbl_produto.referencia_fabrica LIKE '%$referencia%')
			AND      tbl_linha.fabrica = $login_fabrica
			AND      tbl_produto_pais.pais = '$login_pais'";
//comentado chamado 230 19-06	AND      tbl_produto.ativo is true";
	if ($login_fabrica <> 14) $sql .= " AND      tbl_produto.produto_principal  is true";
//comentado chamado 230 honorato	if ($login_fabrica == 14 AND strlen($_GET['lbm']) == 0) $sql .= " AND tbl_produto.abre_os IS TRUE ";
	$sql .= " ORDER BY tbl_produto.descricao";
	$res = pg_exec ($con,$sql);
//echo $sql;
	if (@pg_numrows ($res) == 0) {
		echo "<h1>Herramienta '$referencia' no encuentrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		
		exit;
	}
}

if (pg_numrows($res) == 1) {
    $produto    = trim(pg_result($res,$i,produto));
    $descricao  = trim(pg_result($res,$i,descricao));
    $sql_idioma = "SELECT * FROM tbl_produto_idioma WHERE produto = '$produto' AND upper(idioma) = '$sistema_lingua'";
		
		$res_idioma = @pg_exec($con,$sql_idioma);
		if (@pg_numrows($res_idioma) >0) {
			$descricao  = trim(@pg_result($res_idioma,0,descricao));
		}

	echo "<script language='JavaScript'>\n";
	if (strlen($_GET['lbm']) > 0) echo "produto.value = '".trim(pg_result($res,0,produto))."';";
	if ($login_fabrica == 1) {
		echo "descricao.value  = '".str_replace ('"','',trim($descricao))." ".trim(pg_result($res,0,voltagem))."';";
	}else{
		echo "descricao.value  = '".str_replace ('"','',trim($descricao))."';";
	}
	if ($_GET["proximo"] == "t") echo "proximo.focus();";
	echo "referencia.value = '".trim(pg_result($res,0,referencia))."';";
	echo "this.close();";
	echo "</script>\n";
}

	echo "<script language='JavaScript'>\n";
	echo "<!--\n";
	echo "this.focus();\n";
	echo "// -->\n";
	echo "</script>\n";
	
	echo "<table width='100%' border='0'>\n";
	
	for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
		$produto            = trim(pg_result($res,$i,produto));
		$linha              = trim(pg_result($res,$i,linha));
		$descricao          = trim(pg_result($res,$i,descricao));
		$voltagem           = trim(pg_result($res,$i,voltagem));
		$referencia         = trim(pg_result($res,$i,referencia));
		$referencia_fabrica = trim(pg_result($res,$i,referencia_fabrica));
		$garantia           = trim(pg_result($res,$i,garantia));
		$mobra              = str_replace(".",",",trim(pg_result($res,$i,mao_de_obra)));
		$ativo              = trim(pg_result($res,$i,ativo));
		$off_line           = trim(pg_result($res,$i,off_line));
	
    $sql_idioma = "SELECT * FROM tbl_produto_idioma WHERE produto = '$produto' AND upper(idioma) = '$sistema_lingua'";
		
		$res_idioma = @pg_exec($con,$sql_idioma);
		if (@pg_numrows($res_idioma) >0) {
			$descricao  = trim(@pg_result($res_idioma,0,descricao));
		}
	
	
		$descricao = str_replace ('"','',$descricao);

		if ($ativo == 't') {
			$mativo = "ACTIVO";
		}else{
			$mativo = "INACTIVO";
		}
		echo "<tr>\n";
		
		echo "<td>\n";
//takashi 06/07/06 chamado 300 helpdesk
		echo "<a href=\"javascript: ";
		if (strlen($_GET['lbm']) > 0) echo "produto.value = '$produto'; ";
		if ($login_fabrica == 1) {
			echo "descricao.value = '$descricao $voltagem'; if (window.voltagem) { voltagem.value = '$voltagem' ; }";
			if ($_GET["voltagem"] == "t") echo "voltagem.value = '$voltagem'; ";
		}else{
			echo "descricao.value = '$descricao'; ";
		}
		echo "referencia.value = '$referencia'; ";
		if ($_GET["proximo"] == "t") echo "proximo.focus(); ";
		echo "this.close() ; \" >";
//takashi 06/07/06 chamado 300 helpdesk

		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$referencia</font>\n";
//takashi 06/07/06 chamado 300 helpdesk
		echo "</a>\n";
//takashi 06/07/06 chamado 300 helpdesk
		echo "</td>\n";

		if($login_fabrica == 20){
			echo "<td>\n";
			if(strlen($referencia_fabrica)>0){
			echo "<font size='1' color='#AAAAAA'>Bare Tool</font><br>";
			}
			echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'> $referencia_fabrica </font>\n";
			echo "</td>\n";
		}
		
		echo "<td>\n";
		echo "<a href=\"javascript: ";
		if (strlen($_GET['lbm']) > 0) echo "produto.value = '$produto'; ";
		if ($login_fabrica == 1) {
			echo "descricao.value = '$descricao $voltagem'; if (window.voltagem) { voltagem.value = '$voltagem' ; }";
			if ($_GET["voltagem"] == "t") echo "voltagem.value = '$voltagem'; ";
		}else{
			echo "descricao.value = '$descricao'; ";
		}
		echo "referencia.value = '$referencia'; ";
		if ($_GET["proximo"] == "t") echo "proximo.focus(); ";
		echo "this.close() ; \" >";
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
