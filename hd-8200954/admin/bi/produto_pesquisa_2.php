<?php
include "../dbconfig.php";
include "../includes/dbconnect-inc.php";
include 'autentica_admin.php';

include '../cabecalho_pop_produtos.php';
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

<body onblur="setTimeout('window.close()',2500);" topmargin='0' leftmargin='0' marginwidth='0' marginheight='0'>

<br>

<?
$tipo = trim (strtolower ($_GET['tipo']));
if ($tipo == "descricao") {
	$descricao = trim (strtoupper($_GET["campo"]));
	
	//echo "<h4>Pesquisando por <b>descrição do produto</b>: <i>$descricao</i></h4>";
	//echo "<p>";
	
	$sql = "SELECT   *
			FROM     tbl_produto
			JOIN     tbl_linha ON tbl_produto.linha = tbl_linha.linha
			WHERE    (tbl_produto.descricao ilike '%$descricao%' OR tbl_produto.nome_comercial ilike '%$descricao%')
			AND      tbl_linha.fabrica = $login_fabrica";
//comentado chamado 230 19-06			AND      tbl_produto.ativo";
	if ($login_fabrica <> 14) $sql .= " AND      tbl_produto.produto_principal";
//comentado chamado 230 honorato	if ($login_fabrica == 14) $sql .= " AND tbl_produto.abre_os IS TRUE ";
	$sql .= " ORDER BY tbl_produto.descricao;";
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
	$referencia = trim(strtoupper($_GET["campo"]));
	$referencia = str_replace(".","",$referencia);
	$referencia = str_replace(",","",$referencia);
	$referencia = str_replace("'","",$referencia);
	$referencia = str_replace("''","",$referencia);
	$referencia = str_replace("-","",$referencia);
	$referencia = str_replace("/","",$referencia);

	//echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>referência do produto</b>: <i>$referencia</i></font>";
	//echo "<p>";

	$sql = "SELECT   tbl_produto.*
			FROM     tbl_produto
 			JOIN     tbl_linha ON tbl_produto.linha = tbl_linha.linha
			WHERE    tbl_produto.referencia_pesquisa ILIKE '%$referencia%'
			AND      tbl_linha.fabrica = $login_fabrica";
//comentado chamado 230 19-06	AND      tbl_produto.ativo is true";
	if ($login_fabrica <> 14) $sql .= " AND      tbl_produto.produto_principal  is true";
//comentado chamado 230 honorato	if ($login_fabrica == 14 AND strlen($_GET['lbm']) == 0) $sql .= " AND tbl_produto.abre_os IS TRUE ";
	$sql .= " ORDER BY tbl_produto.descricao";
	$res = pg_exec ($con,$sql);
//echo $sql;
if($ip=="201.76.85.4") echo $sql;
	if (@pg_numrows ($res) == 0) {
		echo "<h1>Produto '$referencia' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		
		exit;
	}
}

if (pg_numrows($res) == 1) {
	echo "<script language='JavaScript'>\n";
	if (strlen($_GET['lbm']) > 0) echo "produto.value = '".trim(pg_result($res,0,produto))."';";
	if ($login_fabrica == 1) {
		echo "descricao.value  = '".str_replace ('"','',trim(pg_result($res,0,descricao)))." ".trim(pg_result($res,0,voltagem))."';";
	}else{
		echo "descricao.value  = '".str_replace ('"','',trim(pg_result($res,0,descricao)))."';";
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
	
	echo "<table width='100%' border='0' class='tabela' cellspacing='1'>\n";
	if($tipo=="descricao")
		echo "<tr class='titulo_tabela'><td colspan='4'><font style='font-size:14px;'>Pesquisando por <b>descrição do produto</b>: $descricao</b>: $nome</font></td></tr>";
	if($tipo=="referencia")
		echo "<tr class='titulo_tabela'><td colspan='4'><font style='font-size:14px;'>Pesquisando por <b>referência do produto</b>: $referencia</font></td></tr>";

echo "<tr class='titulo_coluna'><td>Código</td><td>Nome</td><td>Voltagem</td><td>&nbsp;</td>";
	
	for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
		$produto    = trim(pg_result($res,$i,produto));
		$linha      = trim(pg_result($res,$i,linha));
		$descricao  = trim(pg_result($res,$i,descricao));
		$voltagem   = trim(pg_result($res,$i,voltagem));
		$referencia = trim(pg_result($res,$i,referencia));
		$garantia   = trim(pg_result($res,$i,garantia));
		$mobra      = str_replace(".",",",trim(pg_result($res,$i,mao_de_obra)));
		$ativo      = trim(pg_result($res,$i,ativo));
		$off_line   = trim(pg_result($res,$i,off_line));
		
		$descricao = str_replace ('"','',$descricao);
		$descricao = str_replace("'","",$descricao);
		$descricao = str_replace("''","",$descricao);

		if ($ativo == 't') {
			$mativo = "ATIVO";
		}else{
			$mativo = "INATIVO";
		}
		if($i%2==0) $cor = "#F7F5F0"; else $cor = "#F1F4FA";
		
		echo "<tr bgcolor='$cor'>\n";
		
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

		echo "$referencia";
//takashi 06/07/06 chamado 300 helpdesk
		echo "</a>\n";
//takashi 06/07/06 chamado 300 helpdesk


		echo "</td>\n";
		
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
		echo "$descricao";
		echo "</a>\n";
		echo "</td>\n";
		
		echo "<td>\n";
		echo "$voltagem";
		echo "</td>\n";
		
		echo "<td>\n";
		echo "$mativo";
		echo "</td>\n";
		
		echo "</tr>\n";
	}
	echo "</table>\n";
?>

</body>
</html>