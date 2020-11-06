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
	<link href="css/estilo_cad_prod.css" rel="stylesheet" type="text/css">
	<link href="css/posicionamento.css" rel="stylesheet" type="text/css">
	<link rel="stylesheet" type="text/css" href="js/thickbox.css" media="screen">
	<script src="js/jquery-1.3.2.js"	type="text/javascript"></script>
	<script src="js/thickbox.js"		type="text/javascript"></script>
</head>
<body onblur="setTimeout('window.close()',2500);" topmargin='0' leftmargin='0' marginwidth='0' marginheight='0'>
<br>

<?
if($login_fabrica == 1){
	$programa_troca = $_GET['exibe'];
	if(preg_match("os_cadastro_troca_black.php", $programa_troca)){
		$troca_valor = 't';
	}
}

$mapa_linha = trim (strtolower ($_GET['mapa_linha']));
$tipo       = trim (strtolower ($_GET['tipo']));
if ($tipo == "descricao") {
	$descricao = trim (strtoupper($_GET["campo"]));
	echo "<h4>Pesquisando por <b>descrição do produto</b>: <i>$descricao</i></h4>";
	echo "<p>";
	$sql = "SELECT   *
			FROM     tbl_produto
			JOIN     tbl_linha ON tbl_produto.linha = tbl_linha.linha
			WHERE    (tbl_produto.descricao ilike '%$descricao%' OR tbl_produto.nome_comercial ilike '%$descricao%')
			AND      tbl_linha.fabrica = $login_fabrica";
	//comentado chamado 230 19-06			AND      tbl_produto.ativo";
	if ($login_fabrica == 1 or $login_fabrica==7 or $login_fabrica ==59) {
		$sql .=  " AND      tbl_produto.ativo"; //hd 14501 22/2/2008 - HD 35014
	}
	if ($login_fabrica <> 14 and $login_fabrica <>59) {
		$sql .= " AND      tbl_produto.produto_principal";
	}
	//comentado chamado 230 honorato	if ($login_fabrica == 14) $sql .= " AND tbl_produto.abre_os IS TRUE ";
	$sql .= " ORDER BY tbl_produto.descricao;";
	//if($ip=='189.64.225.10') echo $sql;
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

	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>referência do produto</b>: <i>$referencia</i></font>";
	echo "<p>";

	$sql = "SELECT   tbl_produto.*
			FROM     tbl_produto
 			JOIN     tbl_linha ON tbl_produto.linha = tbl_linha.linha
			WHERE    tbl_produto.referencia_pesquisa ILIKE '%$referencia%'
			AND      tbl_linha.fabrica = $login_fabrica";
	//comentado chamado 230 19-06	AND      tbl_produto.ativo is true"; 
	if ($login_fabrica == 1 or $login_fabrica==7 or $login_fabrica == 59) {
		$sql .=  " AND      tbl_produto.ativo is true"; //hd 14501 22/2/2008 - HD 35014
	}
	if ($login_fabrica <> 14 and $login_fabrica <>59) {
		$sql .= " AND      tbl_produto.produto_principal  is true";
	}
	//comentado chamado 230 honorato	if ($login_fabrica == 14 AND strlen($_GET['lbm']) == 0) $sql .= " AND tbl_produto.abre_os IS TRUE ";
	$sql .= " ORDER BY";
	if ($login_fabrica == 45) {
		$sql .= " tbl_produto.referencia, ";
	}
	$sql .= " tbl_produto.descricao;";
	$res = pg_exec ($con,$sql);
	//echo $sql;
	//if($ip=="201.76.85.4") echo $sql;
	if (@pg_numrows ($res) == 0) {
		echo "<h1>Produto '$referencia' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}
}

if (pg_numrows($res) == 1) {
	$valor_troca = trim(pg_result($res,0,valor_troca));
	$ipi         = trim(pg_result($res,0,ipi));
	$capacidade  = trim(pg_result($res,0,capacidade));
	$voltagem    = trim(pg_result($res,0,voltagem));

	if (strlen($ipi)>0 AND $ipi != "0"){
		$valor_troca = $valor_troca * (1 + ($ipi /100));
	}

	echo "<script language='JavaScript'>\n";
	if (strlen($_GET['lbm']) > 0) {
		echo "produto.value = '".trim(pg_result($res,0,produto))."';";
	}
	if ($login_fabrica == 1) {
		echo "descricao.value  = '".str_replace ('"','',trim(pg_result($res,0,descricao)))." $voltagem';";
		echo " if (window.voltagem) { voltagem.value = '$voltagem' ; }";
		if ($_GET["voltagem"] == "t") {
			echo "voltagem.value = '$voltagem'; ";
		}
	}else{
		echo "descricao.value  = '".str_replace ('"','',trim(pg_result($res,0,descricao)))."';";
	}
	if ($login_fabrica == 59 && $_GET["voltagem"] == "t") {
		echo " voltagem.value = '$voltagem'; ";
	}
	if ($troca_valor=='t') {
		echo "valor_troca.value='$valor_troca' ; ";
	}

	if ($_GET["proximo"] == "t") {
		echo "proximo.focus();";
	}
	echo "referencia.value = '".trim(pg_result($res,0,referencia))."';";
	if ($login_fabrica==7){
		echo " if (window.capacidade){ capacidade.value = '$capacidade';}; ";
	}

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
	$produto    = trim(pg_result($res,$i,produto));
	$linha      = trim(pg_result($res,$i,linha));
	$descricao  = trim(pg_result($res,$i,descricao));
	$voltagem   = trim(pg_result($res,$i,voltagem));
	$referencia = trim(pg_result($res,$i,referencia));
	$garantia   = trim(pg_result($res,$i,garantia));
	$mobra      = str_replace(".",",",trim(pg_result($res,$i,mao_de_obra)));
	$ativo      = trim(pg_result($res,$i,ativo));
	$off_line   = trim(pg_result($res,$i,off_line));
	$capacidade = trim(pg_result($res,$i,capacidade));
	
	$descricao = str_replace ('"','',$descricao);
	$descricao = str_replace("'","",$descricao);
	$descricao = str_replace("''","",$descricao);

	$valor_troca = trim(pg_result($res,$i,valor_troca));
	$ipi         = trim(pg_result($res,$i,ipi));

	if (strlen($ipi)>0 AND $ipi != "0") {
		$valor_troca = $valor_troca * (1 + ($ipi /100));
	}

	if ($ativo == 't') {
		$mativo = "ATIVO";
	}else{
		$mativo = "INATIVO";
	}
	echo "<tr>\n";
	
	echo "<td>\n";
	//takashi 06/07/06 chamado 300 helpdesk
	echo "<a href=\"javascript: ";
	if (strlen($_GET['lbm']) > 0) {
		echo "produto.value = '$produto'; ";
	}
	if ($login_fabrica == 1) {
		echo "descricao.value = '$descricao $voltagem'; if (window.voltagem) { voltagem.value = '$voltagem' ; }";
		if ($_GET["voltagem"] == "t") echo "voltagem.value = '$voltagem'; ";
	}else{
		echo "descricao.value = '$descricao'; ";
	}
	echo "referencia.value = '$referencia'; ";
	if($mapa_linha =='t'){
		echo " mapa_linha.value = $linha; ";
	}
	if ($login_fabrica == 59 && $_GET["voltagem"] == "t") {
		echo " voltagem.value = '$voltagem'; ";
	}
	if ($_GET["proximo"] == "t") {
		echo "proximo.focus(); ";
	}
	if ($login_fabrica==7){
		echo " if (window.capacidade){ capacidade.value = '$capacidade';}; ";
	}

		echo "this.close() ; \" >";
	//takashi 06/07/06 chamado 300 helpdesk

	echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$referencia</font>\n";
	//takashi 06/07/06 chamado 300 helpdesk
	echo "</a>\n";
	//takashi 06/07/06 chamado 300 helpdesk


	echo "</td>\n";
		
	echo "<td>\n";
	echo "<a href=\"javascript: ";
	if (strlen($_GET['lbm']) > 0) {
		echo "produto.value = '$produto'; ";
	}
	if ($login_fabrica == 1) {
		echo "descricao.value = '$descricao $voltagem'; if (window.voltagem) { voltagem.value = '$voltagem' ; }";
		if ($_GET["voltagem"] == "t") echo "voltagem.value = '$voltagem'; ";
	}else{
		echo "descricao.value = '$descricao'; ";
	}
	echo "referencia.value = '$referencia'; ";
	if($mapa_linha =='t'){
		echo " mapa_linha.value = $linha; ";
	}
	if ($troca_valor=='t') {
		echo "valor_troca.value='$valor_troca' ; ";
	}
	if ($login_fabrica == 59 && $_GET["voltagem"] == "t") {
		echo " voltagem.value = '$voltagem'; ";
	}
	if ($_GET["proximo"] == "t") {
		echo "proximo.focus(); ";
	}
	if ($login_fabrica==7){
		echo " if (window.capacidade){ capacidade.value = '$capacidade';}; ";
	}

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
	if ($login_fabrica==3 or $login_fabrica==11) {
		$imagem = "imagens_produtos/$login_fabrica/pequena/$produto.jpg";
		echo "<td title='$imagem' bgcolor='#FFFFFF' align='center'>\n";
		if (file_exists("/var/www/assist/www/$imagem")) {
			$tag_imagem = "<A href='../".str_replace("pequena", "media", $imagem)."' class='thickbox'>\n";
			$tag_imagem.= "<IMG src='../$imagem' valign='middle' style='border: 2px solid #FFCC00' class='thickbox' height='40'></A>\n";
			echo $tag_imagem;
		}
			echo "</td>\n";
	}
	echo "</tr>\n";
}
echo "</table>\n";
?>

</body>
</html>
