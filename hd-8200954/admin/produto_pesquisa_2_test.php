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

<script language="javascript">
//var descricao = null;
//var referencia = null;
</script>


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

table.tabela tr td {
	font-family: verdana;
	font: bold 11px "Arial";
	border-collapse: collapse;
	border:1px solid #596d9b;
}


</style>
</head>
<!-- onblur="setTimeout('window.close()', 500);"  -->
<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0'>
<br>

<?
if($login_fabrica == 1){
	$programa_troca = $_GET['exibe'];
	if(preg_match("os_cadastro_troca_black.php", $programa_troca)){
		$troca_valor = 't';
	}
	$mostra_inativo =(preg_match("lbm_cadastro.php",$programa_troca)) ? "t" : "f";
}

$mapa_linha = trim (strtolower ($_GET['mapa_linha']));
$tipo       = trim (strtolower ($_GET['tipo']));

//hd 285292 adicionei este bloco para pegar a marca do admin
if ($login_fabrica == 30) {

	$sql_om = "SELECT substr(tbl_marca.nome,0,6) as marca from tbl_admin join tbl_cliente_admin ON tbl_cliente_admin.cliente_admin = tbl_admin.cliente_admin join tbl_marca ON tbl_marca.marca = tbl_cliente_admin.marca where tbl_admin.admin = $login_admin";

	$res_om = pg_exec($con,$sql_om);

	if (pg_num_rows($res_om)>0) {
		$marca = pg_result($res_om,0,0);
	}
	
	if ($marca=='AMBEV') {
		$sql = "select marca from tbl_produto
				JOIN tbl_marca using(marca)
				WHERE  substr(nome,1,5) = '$marca'";
		
		$res = pg_exec($con,$sql);

		$array_marca = array();

		for ($i=0;$i<pg_num_rows($res);$i++) {
			$array_marca[$i] .= pg_result($res,$i,0);
		}

		$marcas = implode(',',$array_marca);
	}
}

//hd 285292 troque *from pelos campos
if ($tipo == "descricao") {
	$descricao = trim (strtoupper($_GET["campo"]));
	//echo "<h4>Pesquisando por <b>descrição do produto</b>: $descricao</h4>";
	//echo "<p>";
	$sql = "SELECT  
					produto,
					tbl_produto.linha,
					referencia,
					descricao,
					garantia,
					mao_de_obra,
					tbl_produto.ativo,
					off_line,
					valor_troca,
					ipi,
					capacidade,
					voltagem
			FROM     tbl_produto
			JOIN     tbl_linha ON tbl_produto.linha = tbl_linha.linha ";
	if($login_fabrica == 30 and strlen($login_cliente_admin)>0 ){
		$sql .= " JOIN     tbl_cliente_admin ON tbl_cliente_admin.marca = tbl_produto.marca and tbl_cliente_admin.cliente_admin = (select cliente_admin from tbl_admin where admin = $login_admin) ";
	}
	$sql .= " WHERE    (	tbl_produto.descricao ilike '%$descricao%' OR 
						tbl_produto.nome_comercial ilike '%$descricao%'
						)
			AND      tbl_linha.fabrica = $login_fabrica";
	//comentado chamado 230 19-06			AND      tbl_produto.ativo";
	if (($login_fabrica == 1 AND $mostra_inativo <>"t") or $login_fabrica==7 or $login_fabrica ==59) {
		$sql .=  " AND      tbl_produto.ativo"; //hd 14501 22/2/2008 - HD 35014
	}
	if ($login_fabrica <> 14 and $login_fabrica <>59) {
		$sql .= " AND      tbl_produto.produto_principal ";
	}
	//comentado chamado 230 honorato	if ($login_fabrica == 14) $sql .= " AND tbl_produto.abre_os IS TRUE ";
	//hd 285292 adicinei este union
	if ($login_fabrica == 30 and $marca == 'AMBEV') {
		$sql .= " UNION
					SELECT 
					produto,
					tbl_produto.linha,
					referencia,
					descricao,
					garantia,
					mao_de_obra,
					tbl_produto.ativo,
					off_line,
					valor_troca,
					ipi,
					capacidade,
					voltagem
					FROM tbl_produto where tbl_produto.marca in ($marcas) and (tbl_produto.descricao ilike '%$descricao%' or tbl_produto.nome_comercial ilike '%$descricao%') ";
	}

	
	$sql .= " ORDER BY 4 ";


	//echo nl2br($sql);
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
//hd 285292 troque *from pelos campos
	$sql = "SELECT 
					produto,
					tbl_produto.linha,
					referencia,
					descricao,
					garantia,
					mao_de_obra,
					tbl_produto.ativo,
					off_line,
					valor_troca,
					ipi,
					capacidade,
					voltagem
			FROM     tbl_produto
 			JOIN     tbl_linha ON tbl_produto.linha = tbl_linha.linha ";
	if($login_fabrica == 30 and strlen($login_cliente_admin)>0 ){
		$sql .= " JOIN     tbl_cliente_admin ON tbl_cliente_admin.marca = tbl_produto.marca and tbl_cliente_admin.cliente_admin = (select cliente_admin from tbl_admin where admin = $login_admin) ";
	}
	$sql .= " WHERE    tbl_produto.referencia_pesquisa ILIKE '%$referencia%'
			AND      tbl_linha.fabrica = $login_fabrica";
	if (($login_fabrica == 1 AND $mostra_inativo <>"t") or $login_fabrica==7 or $login_fabrica == 59) {
		$sql .=  " AND      tbl_produto.ativo is true"; //hd 14501 22/2/2008 - HD 35014
	}
	if ($login_fabrica <> 14 and $login_fabrica <>59) {
		$sql .= " AND      tbl_produto.produto_principal  is true";
	}
	//hd 285292 adicionei o union
	if ($login_fabrica == 30 and $marca == 'AMBEV') {
		$sql .= " UNION
					SELECT 
					produto,
					tbl_produto.linha,
					referencia,
					descricao,
					garantia,
					mao_de_obra,
					tbl_produto.ativo,
					off_line,
					valor_troca,
					ipi,
					capacidade,
					voltagem
					FROM tbl_produto where tbl_produto.marca in ($marcas)  and tbl_produto.referencia_pesquisa ilike '%$referencia%'";
	}

	$sql .= " ORDER BY";
	if ($login_fabrica == 45) {
		$sql .= " 3, ";
	}

	$sql .= " 4 ";
	$res = pg_exec ($con,$sql);

	if (@pg_numrows ($res) == 0) {
		echo "<h1>Produto '$referencia' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}
}



#############################TUDO#################################
if ($tipo == "tudo") {
	$campo = trim(strtoupper($_GET["campo"]));
	$campo = str_replace(".","",$campo);
	$campo = str_replace(",","",$campo);
	$campo = str_replace("'","",$campo);
	$campo = str_replace("''","",$campo);
	$campo = str_replace("-","",$campo);
	$campo = str_replace("/","",$campo);

	//echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>referência do produto</b>: <i>$referencia</i></font>";
	//echo "<p>";
//hd 285292 troque *from pelos campos
	$sql = "SELECT 
					produto,
					tbl_produto.linha,
					referencia,
					descricao,
					garantia,
					mao_de_obra,
					tbl_produto.ativo,
					off_line,
					valor_troca,
					ipi,
					capacidade,
					voltagem
			FROM     tbl_produto
 			JOIN     tbl_linha ON tbl_produto.linha = tbl_linha.linha ";
	if($login_fabrica == 30 and strlen($login_cliente_admin)>0 ){
		$sql .= " JOIN     tbl_cliente_admin ON tbl_cliente_admin.marca = tbl_produto.marca and tbl_cliente_admin.cliente_admin = (select cliente_admin from tbl_admin where admin = $login_admin) ";
	}
	$sql .= " WHERE    ( tbl_produto.referencia_pesquisa ILIKE '%$campo%' OR tbl_produto.descricao ILIKE '%$campo%')
			AND      tbl_linha.fabrica = $login_fabrica";
	if (($login_fabrica == 1 AND $mostra_inativo <>"t") or $login_fabrica==7 or $login_fabrica == 59) {
		$sql .=  " AND      tbl_produto.ativo is true"; //hd 14501 22/2/2008 - HD 35014
	}
	if ($login_fabrica <> 14 and $login_fabrica <>59) {
		$sql .= " AND      tbl_produto.produto_principal  is true";
	}
	//hd 285292 adicionei o union
	if ($login_fabrica == 30 and $marca == 'AMBEV') {
		$sql .= " UNION
					SELECT 
					produto,
					tbl_produto.linha,
					referencia,
					descricao,
					garantia,
					mao_de_obra,
					tbl_produto.ativo,
					off_line,
					valor_troca,
					ipi,
					capacidade,
					voltagem
					FROM tbl_produto where tbl_produto.marca in ($marcas)  and (tbl_produto.referencia_pesquisa ilike '%$campo%' OR tbl_produto.descricao ILIKE '%$campo%')";
	}
	$sql .= " ORDER BY";
	if ($login_fabrica == 45) {
		$sql .= " 3, ";
	}

	$sql .= " 4 ";
	$res = pg_exec ($con,$sql);

	if (@pg_numrows ($res) == 0) {
		echo "<h1>Produto '$campo' não encontrado</h1>";
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
	$produto    = trim(pg_result($res,0,produto));

	if (strlen($ipi)>0 AND $ipi != "0"){
		$valor_troca = $valor_troca * (1 + ($ipi /100));
	}
	$tipo = $_GET[ 'tipo' ];
	echo "<script language='JavaScript'>\n";
	if ($tipo == 'tudo') {
			echo "descricao.value  = '". $referencia .' - '.$descricao . "';";
			echo "produto.value ='" . $produto . "' ;";
	}else{

	echo ($login_fabrica == 59 || $_GET["voltagem"] == "t") ? " voltagem.value = '$voltagem'; " : "";
	echo ($troca_valor=='t') ? "valor_troca.value='$valor_troca' ; " : "";
	echo ($_GET["proximo"] == "t") ? "proximo.focus();" : "";
	echo "referencia.value = '".trim(pg_result($res,0,referencia))."';";
	echo "descricao.value  = '". str_replace ('"','',trim(pg_result($res,0,descricao))) . "';";
	echo ($login_fabrica==7) ? " if (window.capacidade){ capacidade.value = '$capacidade';}; " : "";
	echo "this.close();";
	echo "</script>\n";
}
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
	$capacidade = trim(pg_result($res,$i,capacidade));
	
	$descricao = str_replace ('"','',$descricao);
	$descricao = str_replace("'","",$descricao);
	$descricao = str_replace("''","",$descricao);

	$valor_troca = trim(pg_result($res,$i,valor_troca));
	$ipi         = trim(pg_result($res,$i,ipi));

	if (strlen($ipi)>0 AND $ipi != "0") {
		$valor_troca = $valor_troca * (1 + ($ipi /100));
	}

	$mativo = ($ativo == 't') ? "ATIVO" : "INATIVO";

	if($i%2==0) $cor = "#F7F5F0"; else $cor = "#F1F4FA";
		echo "<tr bgcolor='$cor'>\n";
	
	echo "<td>\n";

	echo "<a href=\"javascript: ";
	if (strlen($_GET['lbm']) > 0) {
		echo "produto.value = '$produto'; ";
	}
	
		echo "descricao.value = '$descricao'; ";
		echo "referencia.value = '" .$referencia ."'; ";
	if($mapa_linha =='t'){
		echo " mapa_linha.value = $linha; ";
	}
	if ($login_fabrica == 59 || $_GET["voltagem"] == "t") {
		echo " voltagem.value = '$voltagem'; ";
	}
	if ($_GET["proximo"] == "t") {
		echo "proximo.focus(); ";
	}
	if ($login_fabrica==7){
		echo " if (window.capacidade){ capacidade.value = '$capacidade';}; ";
	}

  echo "this.close() ; \" >";
	echo "$referencia\n";
	echo "</a>\n";
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
	if ($login_fabrica == 59 || $_GET["voltagem"] == "t") {
		echo " voltagem.value = '$voltagem'; ";
	}
	if ($_GET["proximo"] == "t") {
		echo "proximo.focus(); ";
	}
	if ($login_fabrica==7){
		echo " if (window.capacidade){ capacidade.value = '$capacidade';}; ";
	}

	if( $tipo == 'tudo' )
	{
		echo "descricao.value  = '".$referencia. ' - ' . $descricao . "';";
		echo "produto.value ='" . $produto . "' ;";
	}
	echo "this.close() ; \" >";
	echo "$descricao\n";
	echo "</a>\n";
	echo "</td>\n";
		
	echo "<td>\n";
	echo "$voltagem\n";
	echo "</td>\n";
		
	echo "<td>\n";
	echo "$mativo\n";
	echo "</td>\n";
	if ($login_fabrica==3) {
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
