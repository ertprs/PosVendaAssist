<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_usuario.php';

include_once 'helpdesk/mlg_funciones.php';
include_once "class/tdocs.class.php";
$tDocs = new TDocs($con, $login_fabrica);
// $peca_pedido = $_GET["peca_pedido"]; // Para testes
$peca_pedido = (!in_array($login_fabrica, array(91,104))) ? "f" : $_REQUEST["peca_pedido"];

if ($login_fabrica == 104 && ($peca_pedido == 'undefined' || empty($peca_pedido))) {
	$msg_erro = "Erro ao identificar o \"Tipo de Peça\" (\"Peça\" ou \"Acessório\")";
}

$tipo_pedido = $_REQUEST["tipo_pedido"];

$linha_produto = $_REQUEST["linha_produto"]; //hd_chamado=2765193

$vet_ipi = array(94,101,104,105,106,115,116,117,121,122,123,125,124,126,127,128,129,131,134,136,140,141,144);

/*HD 16027 Produto acabado, existia algumas selects sem a validação*/
//hd 202154 makita vai lancar pedido de produto acabado para postos tipo PATAM 236
$sql = "SELECT tipo_posto from tbl_posto_fabrica where fabrica = $login_fabrica and posto = $login_posto";
$res = pg_query($con, $sql);
if (pg_num_rows($res) > 0) {
	$tipo_posto = pg_fetch_result($res,0,0);
}

$tipo_pedido_descricao = '';

if(!empty($tipo_pedido)){
	$sql = "SELECT descricao, uso_consumo, pedido_em_garantia FROM tbl_tipo_pedido WHERE fabrica = $login_fabrica AND tipo_pedido = $tipo_pedido";
	$res = pg_query($con,$sql);

	$tipo_pedido_descricao = pg_fetch_result($res, 0, "descricao");
	$uso_consumo = pg_fetch_result($res, 0, "uso_consumo");
	$pedido_em_garantia = pg_fetch_result($res, 0, "pedido_em_garantia");
}

#include 'cabecalho_pop_pecas.php';
header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");

if($login_fabrica == 45 && isset($_GET['peca_nks'])){

	$peca_nks = $_GET['peca_nks'];
	$sql = "
		SELECT tbl_produto.produto
		FROM tbl_produto
		JOIN tbl_lista_basica ON tbl_lista_basica.produto = tbl_produto.produto
		AND tbl_lista_basica.fabrica = $login_fabrica
		WHERE tbl_produto.troca_obrigatoria IS NOT TRUE
		AND tbl_lista_basica.peca = $peca_nks
	";
	$res = pg_query($con, $sql);
	if(pg_num_rows($res) > 0){
		echo "1";
	}else{
		echo "0";
	}
	exit;
}

$caminho = "imagens_pecas";
if ( !in_array($login_fabrica, array(6,10,19)) ) {
	$caminho = (in_array($login_fabrica, array(172))) ? $caminho."/11" : $caminho."/".$login_fabrica;
}
$ajax        = $_GET['ajax'];
$ajax_kit    = $_GET['ajax_kit'];
$kit_peca_id = $_GET['kit_peca_id'];
$kit_peca    = $_GET['kit_peca'];

if (!empty($ajax_kit)) {

	$sql = " SELECT tbl_peca.peca      ,
					tbl_peca.referencia,
					tbl_peca.descricao,
					tbl_kit_peca_peca.qtde
			FROM    tbl_kit_peca_peca
			JOIN    tbl_peca USING(peca)
			WHERE   fabrica = $login_fabrica
			AND     kit_peca = $kit_peca_id
			ORDER BY tbl_peca.peca";

	$res = pg_query($con, $sql);
	$resultado = "";

	if (pg_num_rows($res) > 0) {

		$resultado = "<table width='100%' border='0' cellspacing='1' cellspading='0' class='lp_tabela' id='gridRelatorio'>";
		$resultado .="<tr><td><input type='hidden' name='kit_$kit_peca' id='kit_$kit_peca' value='$kit_peca_id'></td></tr>";

		for ($i = 0; $i < pg_num_rows($res); $i++) {

			$peca     = pg_fetch_result($res, $i, 'peca');
			$qtde_kit = pg_fetch_result($res, $i, 'qtde');

			$resultado .=   "<tr>".
							"<td>".
							"<input type='".($login_fabrica == 15 ? 'hidden' : 'checkbox')."' name='kit_peca_$peca' value='$peca' CHECKED > ".
							"<input type='text' name='kit_peca_qtde_$peca' id='kit_peca_qtde_$peca' size='5' value='$qtde_kit' onkeyup=\"re = /\D/g; this.value = this.value.replace(re, '');\" readonly='readonly'> x ".
							pg_fetch_result($res,$i,'referencia').
							"</td>".
							"<td> - ".
							pg_fetch_result($res,$i,'descricao').
							"</td>".
							"</tr>";
		}

		$resultado .= "</table>";

		echo "ok|$resultado";

	}

	exit;

}

if (strlen($ajax) > 0) {

	$arquivo = $_GET['arquivo'];
	$idpeca = $_GET['idpeca'];

	$caminho = (in_array($login_fabrica, array(172))) ? "imagens_pecas/11" : $caminho;


	$xpecas  = $tDocs->getDocumentsByRef($idpeca, "peca");
	if (!empty($xpecas->attachListInfo)) {

		$a = 1;
		foreach ($xpecas->attachListInfo as $kFoto => $vFoto) {
		    $fotoPeca = $vFoto["link"];
		    if ($a == 1){break;}
		}
		echo "<table align='center'>
		<tr>
			<td align='right'>
				<a href='javascript:escondePeca();' style='font-size: 10px color:white;font-weight:bold'>FECHAR</a>
			</td>
		</tr>
		<tr>
			<td align='center'>
				<a href=\"javascript:escondePeca();\"><img src='$fotoPeca'  height='50' border='0'></a>
			</td>
		</tr>
	</table>";
	} else {



	?>

	<table align='center'>
		<tr>
			<td align='right'>
				<a href='javascript:escondePeca();' style='font-size: 10px color:white;font-weight:bold'>FECHAR</a>
			</td>
		</tr>
		<tr>
			<td align='center'>
				<a href=\"javascript:escondePeca();\"><img src='$caminho/media/$arquivo' border='0'></a>
			</td>
		</tr>
	</table>
<?php
	}
	exit;

}

$defeito_constatado = $_GET['defeito_constatado'];

if ($login_fabrica == 40 && isset($_GET['defeito_constatado'])) {

	if ($defeito_constatado == '') {
		echo "<script>alert('Selecione Defeito Constatado'); setTimeout('self.close();',1000)</script>";
		exit;
	}

	$cond_masterfrio  = "LEFT JOIN tbl_peca_defeito_constatado ON (tbl_peca_defeito_constatado.peca = tbl_peca.peca)	";
	$where_masterfrio = " AND (tbl_peca_defeito_constatado.defeito_constatado = $defeito_constatado OR tbl_peca_defeito_constatado.defeito_constatado IS NULL)";

}

$exibe_mensagem = 't';

$sql = "SELECT desconto FROM tbl_posto_fabrica WHERE posto = $login_posto AND fabrica = $login_fabrica";
$res = pg_query($con,$sql);
if(pg_num_rows($res) > 0){
		$desconto = pg_result($res,0,0);
} else {
		$desconto = 0;
}

if (strpos($_GET['exibe'],'pedido') !== false) $exibe_mensagem = 'f';

$faturado = $_GET['faturado']; # se for compra faturada, não precisa de validar.

$sql = "SELECT  distinct tbl_posto_fabrica.item_aparencia,
				tbl_posto_linha.tabela,
				tabela_posto
		FROM tbl_posto_linha
		JOIN tbl_posto_fabrica USING(posto)
		JOIN tbl_linha USING(linha)
		WHERE tbl_posto_linha.posto           = $login_posto
		AND   tbl_posto_fabrica.fabrica = $login_fabrica
		AND   tbl_linha.fabrica = $login_fabrica";
$res = pg_query($con,$sql);

if (pg_num_rows ($res) > 0) {

	if ($faturado == 'sim') {

		$item_aparencia = 't';
		if($login_fabrica == 94) {
			$item_aparencia = ($login_posto_interno) ? 't' : 'f';
		}
	} else {
		$item_aparencia = pg_fetch_result($res, 0, 'item_aparencia');
	}

	$tabela = (in_array($login_fabrica,array(101,115,116,122,140)) && $faturado == 'sim') ? pg_fetch_result($res, 0, 'tabela_posto') : pg_fetch_result($res, 0, 'tabela');

	if (($login_fabrica == 104 or $login_fabrica == 105) && $faturado == 'sim') {
		$tabela = pg_fetch_result($res, 0, 'tabela');
	}
	if ($login_fabrica == 2) $tabela = 236; # HD112438


}

/*Modificado por Fernando
Pedido de Leandro da Tectoy por E-mail. Modificação foi feita para que os postos
que não podem fazer pedido em garantia (OS) de peças, cadastradas como item aparencia, possa
fazer pedido faturado através da tela "pedido_cadastro.php".
*/

if ($login_fabrica == 6) {

	$faz_pedido = $_GET['exibe'];

	if (preg_match("pedido_cadastro.php", $faz_pedido)) {
		$item_aparencia = 't';
	}

	#Fabio - HD 3921 - Para PA fazer pedido
	if (preg_match("tabela_precos_tectoy.php", $faz_pedido)) {
		$item_aparencia = 't';
	}

} ?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title> Pesquisa Peças pela Lista Básica ... </title>
<meta name="Author" content="">
<meta name="Keywords" content="">
<meta name="Description" content="">
<meta http-equiv=pragma content=no-cache>
<script type="text/javascript" src="js/jquery-1.4.2.js"></script>
<link rel="stylesheet" type="text/css" href="css/posicionamento.css">
<link rel="stylesheet" type="text/css" href="js/thickbox.css" media="screen">
<script src="js/thickbox.js" type="text/javascript"></script>
<script src="plugins/jquery/jquery.tablesorter.min.js" type="text/javascript"></script>
<link rel="stylesheet" type="text/css" href="css/lupas/lupas.css">
<?php include "javascript_calendario_new.php"; ?>
</head>

<style>
	body {
		margin: 0;
		font-family: Arial, Verdana, Times, Sans;
		background: #fff;
	}
	.fora_linha{
		color:red;
	}
</style>

<script>
function onoff(id) {
	var el = document.getElementById(id);
	el.style.display = (el.style.display=="") ? "none" : "";
}

function addslashes(str) {
	str=str.replace(/([\\|\"|\'|0])/g,'\\$1');
	return str;
}

function createRequestObject(){
	var request_;
	var browser = navigator.appName;
	if(browser == "Microsoft Internet Explorer"){
		 request_ = new ActiveXObject("Microsoft.XMLHTTP");
	}else{
		 request_ = new XMLHttpRequest();
	}
	return request_;
}

function escondePeca(){
	if (document.getElementById('div_peca')){
		var style2 = document.getElementById('div_peca');
		if (style2==false) return;
		if (style2.style.display=="block"){
			style2.style.display = "none";
		}else{
			style2.style.display = "block";
		}
	}
}

function mostraPeca(arquivo, peca) {
	var el = document.getElementById('div_peca');
	el.style.display = (el.style.display=="") ? "none" : "";
	imprimePeca(arquivo, peca);
}

var http3 = new Array();
function imprimePeca(arquivo, peca){
	var curDateTime = new Date();
	http3[curDateTime] = createRequestObject();

	url = "peca_pesquisa_lista_new.php?ajax=true&idpeca="+peca+"&arquivo="+ arquivo;
	http3[curDateTime].open('get',url);
	var campo = document.getElementById('div_peca');
	Page.getPageCenterX();
	campo.style.top = (Page.top + Page.height/2)-160;
	campo.style.left = Page.width/2-220;
	http3[curDateTime].onreadystatechange = function(){
		if(http3[curDateTime].readyState == 1) {
			campo.innerHTML = "<span style='font-familiy:Verdana,Arial,Sans-Serif;font-size:10px'>Aguarde..</span>";
		}
		if (http3[curDateTime].readyState == 4){
			if (http3[curDateTime].status == 200 || http3[curDateTime].status == 304){

				var results = http3[curDateTime].responseText;
				campo.innerHTML   = results;
			}else {
				campo.innerHTML = "Erro";
			}
		}
	}
	http3[curDateTime].send(null);
}

var Page = new Object();
Page.width;
Page.height;
Page.top;

Page.loadOut = function (){
	document.getElementById('div_peca').innerHTML ='';
}
Page.getPageCenterX = function (){
	var fWidth;
	var fHeight;
	//For old IE browsers
	if(document.all) {
		fWidth = document.body.clientWidth;
		fHeight = document.body.clientHeight;
	}
	//For DOM1 browsers
	else if(document.getElementById &&!document.all){
			fWidth = innerWidth;
			fHeight = innerHeight;
		}
		else if(document.getElementById) {
				fWidth = innerWidth;
				fHeight = innerHeight;
			}
			//For Opera
			else if (is.op) {
					fWidth = innerWidth;
					fHeight = innerHeight;
				}
				//For old Netscape
				else if (document.layers) {
						fWidth = window.innerWidth;
						fHeight = window.innerHeight;
					}
	Page.width = fWidth;
	Page.height = fHeight;
	Page.top = window.document.body.scrollTop;
}

function kitPeca(kit_peca_id,kit_peca) {

	var id_defeito = kit_peca.replace('kit_peca_', '');

	$.ajax({
		type: 'GET',
		url: '<?=$PHP_SELF?>',
		data: 'kit_peca_id='+kit_peca_id+'&kit_peca='+kit_peca+'&ajax_kit=sim',
		beforeSend: function(){
			window.opener.$('#'+kit_peca).html(' ');
		},
		complete: function(resposta) {

			resultado = resposta.responseText.split('|');

			if (resultado[0] == 'ok') {

				window.opener.$('#'+kit_peca).append(resultado[1]);

			} else {

				window.opener.$('#'+kit_peca).html(' ');

			}

			window.close();

		}
	});
}
</script>
<body>
<div class="lp_header">
		<a href='javascript:window.parent.Shadowbox.close();' style='border: 0;'>
			<img src='css/modal/excluir.png' alt='Fechar' class='lp_btn_fechar' />
		</a>
</div>

<?php

$input_posicao = $_REQUEST['input_posicao'];
$tipo    = trim(strtolower($_REQUEST['tipo']));
$produto = $_REQUEST['produto'];

if (empty($produto) && !strlen($_REQUEST["descricao"]) && !strlen($_REQUEST["peca"])) {
	$msg_erro = "Digite uma referência ou descrição";
}

if($login_fabrica == 45 && !empty($produto)){
	$sql = "
		SELECT produto
		FROM tbl_produto
		WHERE referencia = '$produto'
		AND troca_obrigatoria IS TRUE;
	";
	$res = pg_query($con, $sql);
	if(pg_num_rows($res) > 0){
		echo "<br /> <div style='width: 100%; background-color: #FF0000; font: bold 16px Arial; color: #FFFFFF; text-align: center;'>A peça indisponível, por favor entre contato com Fabricante!</div>";
		exit;
	}
}

if (in_array($login_fabrica, array(6,42))) {

	$os = $_GET['os'];

	if (strlen($os) > 0) {

		$sql              = "SELECT serie, tipo_atendimento from tbl_os where os = $os and fabrica = $login_fabrica";
		$res              = @pg_query($con,$sql);
		$serie            = @pg_fetch_result($res, 0, 'serie');
		$tipo_atendimento = @pg_fetch_result($res, 0, 'tipo_atendimento');

	}

}

if ($login_fabrica == 15) {

	$os    = $_GET['os'];
	$serie = trim($_GET['serie']);

	$sql   = "SELECT numero_serie_obrigatorio from tbl_os JOIN tbl_produto USING(produto) where os = $os and fabrica = $login_fabrica";
	$res   = @pg_query($con, $sql);
	$numero_serie_obrigatorio = @pg_fetch_result($res,0,numero_serie_obrigatorio);

	if (strlen($serie) == 0 AND $numero_serie_obrigatorio == 't') {
		$msg_erro = "Por favor, preencher primeiro o número de série do produto.";
	}

	if (strlen($serie) > 0 AND $numero_serie_obrigatorio == 't') {
		$aux_serie = substr($serie,1,1);
	}

	// 14/01/2010 MLG - HD 189523
	if (!function_exists("is_between")) {
		function is_between($valor,$min,$max) {// BEGIN function is_between
			// Devolve 'true' se o valor está entre ("between") o $min e o $max
			//echo ($valor >= $min AND $valor <= $max);
			return ($valor >= $min AND $valor <= $max);
		}
	}

	function valida_serie_latinatec($serie,$prod_min_ver,$prod_max_ver,$lbm_min_ver,$lbm_max_ver) {

		if (strlen(trim($serie)) < 3) return true;

		$serie_ok = false;
		$usar_serie_produto	= ($prod_min_ver != "" or $prod_max_ver != "");
		$usar_serie_lbm		= ($lbm_min_ver != "" or $lbm_max_ver != "");

		if (!$usar_serie_lbm and !$usar_serie_produto) $serie_ok = true;

		if (!$serie_ok) {
			$min_serie_prod = (trim($prod_min_ver)	== "") ? " " : strtoupper($prod_min_ver);
			$max_serie_prod = (trim($prod_max_ver)	== "") ? "z" : strtoupper($prod_max_ver);
			$min_serie_lbm	= (trim($lbm_min_ver)	== "") ? " " : strtoupper($lbm_min_ver);
			$max_serie_lbm	= (trim($lbm_max_ver)	== "") ? "z" : strtoupper($lbm_max_ver); // O minúsculo é proposital...

			if (is_between(strtoupper($serie[1]),$min_serie_prod, $max_serie_prod) or !$usar_serie_produto) {
				if (is_between(strtoupper($serie[1]),$min_serie_lbm, $max_serie_lbm)) {
					$serie_ok = true;
				}
			}

		}

		return $serie_ok;

	}

}
if($login_fabrica == 120 or $login_fabrica == 201){
	if(strlen(trim($linha_produto)) > 0){ //hd_chamado=2765193
		$cond_linha = " AND tbl_produto.linha = $linha_produto ";
	}else{
		$cond_linha = "";
	}
}


if (strlen ($produto) > 0) {

	$produto_referencia = preg_replace('/\W/', '', $_REQUEST['produto']);
	$produto_original  =  $_REQUEST['produto'];

	$voltagem = trim(strtoupper($_GET["voltagem"]));

	$sql = "SELECT tbl_produto.produto, tbl_produto.descricao,lista_troca
			FROM   tbl_produto
			JOIN   tbl_linha USING (linha)
			WHERE  referencia = '$produto_original' and fabrica = $login_fabrica";

	if (strlen($voltagem) > 0 AND $login_fabrica == "1" ) $sql .= " AND UPPER(tbl_produto.voltagem) = UPPER('$voltagem') ";

	$sql .= "AND tbl_linha.fabrica = $login_fabrica ";

	if ($login_fabrica <> 3) $sql .= " AND tbl_produto.ativo IS TRUE ";

	$res = pg_query($con,$sql);

	if (pg_num_rows($res) > 0) {

		$produto_descricao = pg_fetch_result($res, 0, 'descricao');
		$produto           = (int) pg_fetch_result($res, 0, 'produto');
		$lista_troca       = pg_fetch_result($res, 0, 'lista_troca');
		$join_produto      = " JOIN tbl_lista_basica USING (peca) JOIN tbl_produto USING (produto)";
		$condicao_produto  = " AND tbl_produto.produto = $produto ";

		if ($login_fabrica == 96) {
			$join_produto      = " ";
			$condicao_produto  = " ";
		}

	} else {
		$produto_referencia = preg_replace('/\W/', '', $_REQUEST['produto']);

		$voltagem = trim(strtoupper($_GET["voltagem"]));

		$sql = "SELECT tbl_produto.produto, tbl_produto.descricao,lista_troca
			FROM   tbl_produto
			JOIN   tbl_linha USING (linha)
			WHERE  UPPER(tbl_produto.referencia_pesquisa) = UPPER('$produto_referencia') ";

		if (strlen($voltagem) > 0 AND $login_fabrica == "1" ) $sql .= " AND UPPER(tbl_produto.voltagem) = UPPER('$voltagem') ";

		$sql .= "AND tbl_linha.fabrica = $login_fabrica ";

		if ($login_fabrica <> 3) $sql .= " AND tbl_produto.ativo IS TRUE ";

		$res = pg_query($con,$sql);

		if (pg_num_rows($res) > 0) {

			$produto_descricao = pg_fetch_result($res, 0, 'descricao');
			$produto           = (int) pg_fetch_result($res, 0, 'produto');
			$lista_troca       = pg_fetch_result($res, 0, 'lista_troca');
			$join_produto      = " JOIN tbl_lista_basica USING (peca) JOIN tbl_produto USING (produto)";
			$condicao_produto  = " AND tbl_produto.produto = $produto ";

			if ($login_fabrica == 96) {
				$join_produto      = " ";
				$condicao_produto  = " ";
			}

		}else{
				$msg_erro = "Digite uma referência ou descrição";
		}

	}

}

/* $cond_produto =" AND 1 = 1 "; */

if ($login_fabrica <> 3 && !empty($produto)) $cond_produto = " AND tbl_produto.ativo IS TRUE " ;

echo "<div id='div_peca' style='display:none; Position:absolute; border: 1px solid #949494;background-color: #b8b7af;width:410px; heigth:400px'>";
echo "</div>";

if ($login_fabrica == 30) {
	$join_busca_referencia = 'LEFT JOIN tbl_esmaltec_referencia_antiga ON (tbl_peca.referencia = tbl_esmaltec_referencia_antiga.referencia) ';
}

$descricao  = trim(strtoupper($_REQUEST["descricao"]));

$referencia = trim(strtoupper($_REQUEST["peca"]));
$referencia = str_replace(".","",$referencia);
$referencia = str_replace(",","",$referencia);
$referencia = str_replace("-","",$referencia);
$referencia = str_replace("/","",$referencia);
$referencia = str_replace(" ","",$referencia);

$posicao    = trim(strtoupper($_REQUEST["posicao"]));
$posicao    = str_replace(".","",$posicao);
$posicao    = str_replace(",","",$posicao);
$posicao    = str_replace("-","",$posicao);
$posicao    = str_replace("/","",$posicao);
$posicao    = str_replace(" ","",$posicao);


if ($tipo <> "lista_basica")
{
echo "<div class='lp_nova_pesquisa'>";
	echo "<form action='$PHP_SELF' method='POST' name='nova_pesquisa'>";
		echo "<input type='hidden' name='voltagem' value='$voltagem' />";
		echo "<input type='hidden' name='tipo' id='tipo' value='$tipo' />";
		echo "<input type='hidden' name='peca_pedido'  value='$peca_pedido' />";
		echo "<input type='hidden' name='input_posicao' id='input_posicao' value='$input_posicao'>";
		echo "<input type='hidden' name='produto' id='produto' value='$produto_original'>";
		echo "<input type='hidden' name='linha_produto' value='$linha_produto' />"; //hd_chamado=2765193

		if (!empty($insumos)) {
			echo "<input type='hidden' name='tipo_pedido' id='tipo_pedido' value='$tipo_pedido'>";
			echo "<input type='hidden' name='insumos' id='insumos' value='$insumos'>";
		}

		echo "<table cellspacing='1' cellpadding='2' border='0'>";
			echo "<tr>";
				echo "<td>";
					echo "Referência:<input type='text' name='peca' id='peca' value='$peca' onclick='$(\"#tipo\").val(\"referencia\");'>";
				echo "</td>";
				echo "<td>";
					echo "Descrição:<input type='text' name='descricao' id='descricao' value='$descricao' onclick='$(\"#tipo\").val(\"descricao\");'>";
				echo "</td>";
					if ($login_fabrica == 14 or $login_fabrica == 66){
						echo "<td>";
							echo "Posição:<input type='text' name='posicao' id='posicao' value='$posicao' onclick='$(\"#tipo\").val(\"posicao\");'>";
						echo "</td>";
					}
				echo "<td class='btn_acao' valign='bottom'>";
					echo "<input type='submit' name='btn_acao' value='Pesquisar Novamente'>";
				echo "</td>";
			echo "</tr>";
		echo "</table>";
	echo "</form>";
echo "</div>";
}
else
{
	echo "<div class='lp_pesquisando_por'>";
		echo "Pesquisando pela Lista Básica do Produto $produto_descricao";
	echo "</div>";
}


//REMOVER 199776,199777 após testar pois são produtos do banco de teste
$vet_gar    = (in_array($produto, array(199776,199777,200253,200254)) && $login_fabrica == 42);//HD 400603

if ($login_fabrica == 30) {
	$or_busca_referencia = " OR tbl_esmaltec_referencia_antiga.referencia_antiga LIKE '%$referencia%' ";
}

if ($login_fabrica == 15 or $login_fabrica == 24 and $produto <> "") {//HD 258901 - KIT

	if ($login_fabrica == 24) {

		$sql = " SELECT tbl_kit_peca.referencia,
						tbl_kit_peca.descricao,
						tbl_kit_peca.kit_peca
				FROM    tbl_kit_peca
				WHERE   tbl_kit_peca.fabrica = $login_fabrica
				AND tbl_kit_peca_produto.produto = $produto";

	} else if ($login_fabrica == 15) {

		$sql = "SELECT tbl_kit_peca.referencia,
					   tbl_kit_peca.descricao,
					   tbl_kit_peca.kit_peca
				  FROM tbl_kit_peca
				  JOIN tbl_kit_peca_produto ON tbl_kit_peca_produto.kit_peca = tbl_kit_peca.kit_peca
				 WHERE tbl_kit_peca_produto.fabrica = $login_fabrica
				 AND tbl_kit_peca_produto.produto = $produto";

	}

	$sql .= " ORDER BY tbl_kit_peca.descricao ";

	$res = pg_query($con,$sql);

	if (pg_num_rows($res) > 0) {

		$kit_peca_sim = "sim";
		echo "KIT de Peças";
		echo "<table width='100%' border='0' cellspacing='1' cellspading='0' class='lp_tabela' id='gridRelatorio'>";

		for ($i = 0; $i < pg_num_rows($res); $i++) {

			$kit_peca_id    = pg_fetch_result($res, $i, 'kit_peca');
			$descricao_kit  = pg_fetch_result($res, $i, 'descricao');
			$referencia_kit = pg_fetch_result($res, $i, 'referencia');

			$cor = ($i % 2 <> 0) ? '#F7F5F0' : '#F1F4FA';

			echo "<tr bgcolor='$cor'>";
				echo "<td>$referencia_kit</td>";
				echo "<td>";
					echo "<a href=\"javascript: ";
					echo " referencia.value='$referencia_kit'; descricao.value='$peca_descricao'; ";
					echo " preco.value='';";
					echo "kitPeca('$kit_peca_id','$kit_peca'); \">$descricao_kit</a>";
				echo "</td>";
			echo "</tr>";

		}

		echo "</table>";
		echo "<br />";

	}

}
if ($vet_gar) {


	$sql = "SELECT tbl_peca.peca,
				   tbl_peca.referencia AS peca_referencia,
				   tbl_peca.descricao  AS peca_descricao,
				   peca_fora_linha,
			       de, 
			       para,
			       peca_para
			  FROM tbl_peca
			  LEFT JOIN tbl_depara ON tbl_peca.peca = tbl_depara.peca_de
			  LEFT JOIN tbl_peca_fora_linha ON tbl_peca.peca = tbl_peca_fora_linha.peca
			  ";

	if (in_array($produto, array(199776,200253)) && $login_fabrica == 42) {
		$not = " not ";
	}

	$sql .= " WHERE tbl_peca.fabrica = $login_fabrica ";

	if (strlen($descricao) > 0) {

		if ($tipo == 'descricao') {
			$sql .= " AND UPPER(TRIM(tbl_peca.descricao)) LIKE UPPER(TRIM('%$descricao%'))";
		} else if ($tipo == 'tudo') {
			$sql .= " AND (UPPER(TRIM(tbl_peca.descricao))  LIKE UPPER(TRIM('%$descricao%')) OR
						   UPPER(TRIM(tbl_peca.referencia)) LIKE UPPER(TRIM('%$descricao%')))";
		}

	}

	if (strlen($referencia) > 0) {
		$sql .= " AND (UPPER(TRIM(tbl_peca.referencia_pesquisa)) LIKE UPPER(TRIM('%$referencia%')) $or_busca_referencia )";
	}


	if ($login_fabrica == 42) {
		$sql .= " AND tbl_peca.acessorio is $not true";
		$sql .= " AND tbl_peca.produto_acabado is not true ";
	}

} else if (strlen(trim($descricao)) > 2 or strlen(trim($referencia)) >= 2 or strlen(trim($posicao)) > 0 or $tipo =='lista_basica') {

 if ($tipo == 'descricao') {
	echo "<div class='lp_pesquisando_por'>";
	$texto = ($sistema_lingua == "ES") ? 'Buscando por el nombre del repuesto ' : 'Pesquisando por descrição da peça ';
	echo "$texto - $descricao";
	echo "</div>";

} else if ($tipo == 'referencia') {

	echo "<div class='lp_pesquisando_por'>";
	echo ($sistema_lingua == "ES") ? "Buscando por referencia: " : "Pesquisando por referência da peça: ";
	echo "$referencia";
	echo "</div>";

} else if ($tipo == 'posicao') {
	echo "<div class='lp_pesquisando_por'>";
	if ($sistema_lingua == "ES") {
		echo "Buscando por posición: ";
	} else {
		echo "Pesquisando por posição da peça: ";
	}
	echo "$posicao";
	echo "</div>";

}
	if($login_fabrica == 74) {
		$cond_depara = " AND expira ISNULL ";
	}

	$insumos = '';
	$into_temp = '';
	$cond_parametros_adicionais = '';

	if ( in_array($login_fabrica, array(11,172)) ) {
		$insumos = $_REQUEST["insumos"];

		if ($insumos != 'embalagens') {
			$cond_parametros_adicionais = " AND COALESCE(JSON_FIELD('embalagens', tbl_peca.parametros_adicionais), '') <> 't' ";
		}

		if ($tipo_pedido_descricao == 'Insumo') {
			$into_temp = "\n INTO TEMP tmp_pesquisa_peca  \n";
			$cond_parametros_adicionais = '';
		}
	}

	$leftjoin = " LEFT JOIN ";
	$condPostoLinha = "";

	if ($login_fabrica == 139 && strlen($login_posto) > 0 && (strlen(trim($descricao)) > 2 or strlen(trim($referencia)) >= 2) && $peca_pedido == "t") {
		$condPostoLinha = " JOIN tbl_posto_linha ON tbl_produto.linha=tbl_posto_linha.linha AND tbl_posto_linha.posto = {$login_posto} AND tbl_posto_linha.ativo IS TRUE";
		$leftjoin = " JOIN ";
	}

	/*if(in_array($login_fabrica, [11,172])){
		$JoinLenox = " left join tbl_posto_estoque on tbl_posto_estoque.peca = z.peca and tbl_posto_estoque.posto = 4311 ";
		$campo_estoque = " tbl_posto_estoque.qtde as qtde_estoque, "; 
	}*/


	$sql = "SELECT  DISTINCT z.peca                       ,
					z.referencia       AS peca_referencia ,
					z.descricao        AS peca_descricao  ,
					z.bloqueada_garantia                  ,
					z.bloqueada_venda,
					z.parametros_adicionais,
					z.type                                ,
					z.posicao                             ,
					z.peca_fora_linha                     ,
					z.de                                  ,
					z.localizacao  						  ,
					z.para                                ,";

	if (strlen($produto) > 0 AND $login_fabrica <> 43 and $login_fabrica <> 96 and is_int($produto)) {
		$sql .=	"	tbl_lista_basica.somente_kit                   ,";
	}

	$sql .= "		z.peca_para                           ,
					z.libera_garantia                     , ";

	if ($login_fabrica == 15) {

		$sql .= "   z.serie_inicial                                    ,
					z.serie_final							 		   ,
					tbl_produto.descricao     AS nome_produto          ,
					tbl_produto.serie_inicial AS produto_serie_inicial ,
					tbl_produto.serie_final   AS produto_serie_final   ,";

	}

	$sql .= "       tbl_peca.descricao AS para_descricao
			$into_temp
			FROM (
					SELECT  y.peca               ,
							y.referencia         ,
							y.descricao          ,
							y.bloqueada_garantia ,
							y.bloqueada_venda,
							y.parametros_adicionais,
							y.type               ,
							y.localizacao        ,
							y.posicao            ,";

	if ($login_fabrica == 15) {

		$sql .= "   y.serie_inicial             ,
					y.serie_final				,";

	}

	$sql.= "				y.peca_fora_linha    ,
							tbl_depara.de        ,
							tbl_depara.para      ,
							tbl_depara.peca_para,
							y.libera_garantia
					FROM (
							SELECT  x.peca                                      ,
									x.referencia                                ,
									x.descricao                                 ,
									x.bloqueada_garantia                        ,
									x.bloqueada_venda,
									x.parametros_adicionais,
									x.localizacao 								,
									x.type                                      ,
									x.posicao                                   ,";

	if ($login_fabrica == 15) {
		$sql .= "       x.serie_inicial                 ,
						x.serie_final					,";
	}

	$sql .= "						tbl_peca_fora_linha.peca AS peca_fora_linha,
									tbl_peca_fora_linha.libera_garantia
							FROM (
									SELECT  tbl_peca.peca				  ,
											tbl_peca.referencia			  ,
											tbl_peca.descricao			  ,
											tbl_peca.localizacao      ,
											tbl_peca.bloqueada_garantia	  ,
											tbl_peca.bloqueada_venda,
											tbl_peca.parametros_adicionais,
											coalesce(tbl_lista_basica.type,' ') as type		  ,
											coalesce(tbl_lista_basica.posicao,' ') as posicao     ,
											tbl_lista_basica.serie_inicial,
											tbl_lista_basica.serie_final
									FROM tbl_peca
									$leftjoin tbl_lista_basica USING (peca)
									$join_busca_referencia
									$cond_masterfrio
									$leftjoin tbl_produto ON tbl_produto.produto = tbl_lista_basica.produto
									$condPostoLinha ";


	if ($login_fabrica == 20 AND $login_pais <> 'BR') {
		$sql .= "JOIN tbl_tabela_item ON tbl_tabela_item.peca = tbl_peca.peca AND tabela = $tabela ";
	}

	/* HD 146619 - ALTERACAO 1 de 2 */
	if ($login_fabrica == 15) {
		$sql .= "LEFT JOIN tbl_depara ON tbl_lista_basica.peca = tbl_depara.peca_para
				 LEFT JOIN tbl_lista_basica AS tbl_lista_basica_de ON tbl_depara.peca_de = tbl_lista_basica_de.peca AND tbl_lista_basica.produto = tbl_lista_basica_de.produto";
	}

	$sql .= " WHERE tbl_peca.fabrica = $login_fabrica";

	if ($login_fabrica == 91 && $peca_pedido == "t") {
		$sql .= " AND tbl_peca.peca_critica IS FALSE ";
	}
	if ($login_fabrica == 104 && $peca_pedido == "t") {
		$sql .= " AND tbl_peca.acessorio is true ";
	}
	if ($login_fabrica == 104 && $peca_pedido == "f") {
		$sql .= " AND tbl_peca.acessorio is not true ";
	}

	if (!empty($produto) and is_int($produto)) {
		$sql .= " AND tbl_produto.produto = $produto";
	}

	$sql .= " AND tbl_peca.ativo IS TRUE
			$cond_produto
			$cond_linha
			$where_masterfrio
			$cond_parametros_adicionais";


	if ($tipo_posto <> 236) {//HD 202154 makita vai lancar pedido de produto acabado para postos tipo PATAM 236
		$sql .= " AND   tbl_peca.produto_acabado IS NOT TRUE ";
	}

	if ($login_fabrica == 14 or $login_fabrica == 50) {
		$sql .= " AND tbl_lista_basica.ativo IS NOT FALSE";
	}

	if (strlen(trim($descricao)) > 2) {

		if ($tipo == 'descricao') {
			$sql .= " AND UPPER(TRIM(tbl_peca.descricao)) LIKE UPPER(TRIM('%$descricao%'))";
		} else if ($tipo == 'tudo') {
			$sql .= " AND (UPPER(TRIM(tbl_peca.descricao))  LIKE UPPER(TRIM('%$descricao%')) OR
						   UPPER(TRIM(tbl_peca.referencia)) LIKE UPPER(TRIM('%$descricao%')))";
		}

	} 

	if (strlen(trim($referencia)) >= 2) {
		$sql .= " AND (UPPER(TRIM(tbl_peca.referencia_pesquisa)) LIKE UPPER(TRIM('%$referencia%')) $or_busca_referencia )";
	}

	if (strlen(trim($posicao)) > 0) {
		$sql .= " AND UPPER(TRIM(tbl_lista_basica.posicao)) ILIKE UPPER(TRIM('%$posicao%'))";
	}

	if ($login_fabrica == 2)    $sql .= " AND tbl_peca.bloqueada_venda IS FALSE";
	if ($item_aparencia == 'f') $sql .= " AND tbl_peca.item_aparencia  IS FALSE";

	if ($login_fabrica == 6 and strlen($serie) > 0) {
		$sql .= " AND tbl_lista_basica.serie_inicial < '$serie'
				  AND tbl_lista_basica.serie_final > '$serie'";
	}

	$sql .= "					) AS x
							LEFT JOIN tbl_peca_fora_linha ON tbl_peca_fora_linha.peca = x.peca
						) AS y
					LEFT JOIN tbl_depara ON tbl_depara.peca_de = y.peca $cond_depara
				) AS z
			LEFT JOIN tbl_peca ON tbl_peca.peca = z.peca_para
			";


    if ($login_fabrica == 91) {
        $cond_fi = " WHERE z.peca_fora_linha IS NULL ";
    }

	if (is_int($produto) AND $login_fabrica <> 43 and $login_fabrica <> 96) {

		$joinLinha = "";

		if (strlen($produto) && strlen($login_posto) > 0 && $login_fabrica == 139) {

			$joinLinha = " JOIN tbl_posto_linha ON tbl_produto.linha=tbl_posto_linha.linha AND tbl_posto_linha.posto = {$login_posto} AND tbl_posto_linha.ativo IS TRUE";

		}

		$sql .= " JOIN tbl_lista_basica ON (tbl_lista_basica.peca = z.peca AND tbl_lista_basica.produto = $produto)
				JOIN tbl_produto  ON (tbl_produto.produto = tbl_lista_basica.produto AND tbl_produto.produto = $produto)
				$joinLinha
				$cond_fi

				 ORDER BY z.descricao";

	}

} else {
	$msg_erro = 'Digite toda ou parte de uma informação para pesquisar.';
}

/*echo "\n\n<pre>$sql</pre>\n"; */
//echo nl2br($sql);
$res = pg_query($con, $sql);

if (strlen($into_temp)) {
	$sql = 'SELECT * FROM tmp_pesquisa_peca WHERE parametros_adicionais LIKE \'%"embalagens":"t"%\'';
	$sql = "SELECT * FROM tmp_pesquisa_peca WHERE COALESCE(JSON_FIELD('embalagens', parametros_adicionais), '') = 't' ";

	/* echo array2table(pg_query($con, 'SELECT * FROM tmp_pesquisa_peca LIMIT 10', 'TEMP TABLE')); */

	if ($insumos != 'embalagens')
		$sql = str_replace('=', '<>', $sql);

	/* pecho($sql); */
//
	$res = pg_query($con, $sql);
}

// if (pg_last_error($con)) die(PHP_EOL . pg_last_error($con));

if (@pg_num_rows($res) == 0 and strlen($kit_peca_sim) == 0) {

	$texto_produto = (strlen($produto_referencia) == 0) ? '' : "<br />para o produto $produto_referencia";
	if ($texto_produto and ($sistema_lingua == 'ES' or $cook_idioma == 'es'))
		$texto_produto = str_replace('ra o', 'ra el', $texto_produto);

	if ($login_fabrica == 1) {
		$msg_erro = "Item '".($tipo == 'descricao' ? $descricao : $referencia)."' não existe $texto_produto, <br> consulte a vista explodida atualizada <br> e verifique o código correto";
	} else {

		if ($sistema_lingua == 'ES')
			$msg_erro = "Repuesto '".($tipo == 'descricao' ? $descricao : $referencia)."' no encontrado$texto_produto";
		else if ($tipo == "descricao" || $tipo == "peca") {
			$msg_erro = "Peça '".($tipo == 'descricao' ? $descricao : $referencia)."' não encontrada$texto_produto.";
			if($login_fabrica == 120 or $login_fabrica == 201){
				$msg_erro .= "<br/>Verifique se a peça pertence a linha selecionada.";
			}
		} else {
			$msg_erro = "Nenhuma peça encontrada na lista básica do produto $produto_referencia";
		}
	}

} else if (@pg_num_rows ($res) == 0) {

	if (strlen($posicao) > 0) {

		if ($sistema_lingua == 'ES') $msg_erro = "Pieza '$posicao' no encontrada <br>para el producto $produto_referencia";
		else                         $msg_erro = "Posição '$posicao' não encontrada<br>para o produto $produto_referencia";

	} else {

		if ($sistema_lingua == 'ES') $msg_erro = "No consta lista básica para este producto";
		else 						 $msg_erro = "Nenhuma lista básica de peças encontrada para este produto";

	}

}

if (!is_numeric(pg_num_rows($res))) {
	$msg_erro = "Peça não encontrada";
}

if (!empty($msg_erro)){
	echo "<div class='lp_msg_erro'>$msg_erro</div>";
}
else
{
$contador  = 999;

$num_pecas = pg_num_rows($res);
$gambiara  = 0;
echo "<center><font color='red' size='-1'>Clique na referência da peça para transferir os dados para o formulário</font></center>";
for ($i = 0; $i < $num_pecas; $i++) {

	$peca_referencia = trim(@pg_fetch_result($res, $i, 'peca_referencia'));
	if (trim(@pg_fetch_result($res, $i, 'peca_referencia')) == trim(@pg_fetch_result($res, $i+1, 'peca_referencia'))) {
		continue;
	}

	if ($login_fabrica == 30) {
		$sql_ref = "SELECT referencia_antiga FROM tbl_esmaltec_referencia_antiga WHERE referencia = '$peca_referencia'";
		$res_ref = @pg_query($con,$sql_ref);
		$referencia_antiga = trim(@pg_fetch_result($res_ref, 0, 'referencia_antiga'));
	}

	$peca				= trim(@pg_fetch_result($res, $i, 'peca'));
	$peca_descricao		= trim(@pg_fetch_result($res, $i, 'peca_descricao'));
	$peca_descricao_js	= strtr($peca_descricao, array('"'=>'&rdquo;', "'"=>'&rsquo;')); //07/05/2010 MLG - HD 235753
	$type				= trim(@pg_fetch_result($res, $i, 'type'));
	$posicao			= trim(@pg_fetch_result($res, $i, 'posicao'));
	$somente_kit		= trim(@pg_fetch_result($res, $i, 'somente_kit'));//HD 335675
	$peca_fora_linha	= trim(@pg_fetch_result($res, $i, 'peca_fora_linha'));
	$peca_para			= trim(@pg_fetch_result($res, $i, 'peca_para'));
	$para				= trim(@pg_fetch_result($res, $i, 'para'));
	$para_descricao		= trim(@pg_fetch_result($res, $i, 'para_descricao'));
	$bloqueada_garantia	= trim(@pg_fetch_result($res, $i, 'bloqueada_garantia'));
	$bloqueada_venda = pg_fetch_result($res, $i, 'bloqueada_venda');
	$libera_garantia    = trim(@pg_fetch_result($res, $i, 'libera_garantia'));
	$estoque = trim(pg_fetch_result($res, $i, 'localizacao'));

	$qtde_estoque = 0 ;
	if(in_array($login_fabrica, [11,172])){
		$sql_estoque = "SELECT tbl_posto_estoque.qtde as qtde_estoque FROM tbl_peca 
				JOIN tbl_posto_estoque on tbl_posto_estoque.peca = tbl_peca.peca
				WHERE tbl_peca.referencia = '$peca_referencia' and tbl_peca.fabrica in (11,172) and tbl_posto_estoque.qtde > 0 ";
		$res_estoque = pg_query($con, $sql_estoque);
		if(pg_num_rows($res_estoque)>0){
			$qtde_estoque = pg_fetch_result($res_estoque, 0, 'qtde_estoque');
		}
	}

	//HD 189523 - MLG - Latinatec filtra a Lista Básica usando o 2º caractere do nº de série para controlar a versão
	if ($login_fabrica == 15 and $serie and $produto) {

		$l_serie_inicial = trim(pg_fetch_result($res, $i, 'serie_inicial'));
		$l_serie_final   = trim(pg_fetch_result($res, $i, 'serie_final'));
		$p_serie_inicial = trim(pg_fetch_result($res, $i, 'produto_serie_inicial'));
		$p_serie_final   = trim(pg_fetch_result($res, $i, 'produto_serie_final'));

		if (!valida_serie_latinatec($serie,$p_serie_inicial,$p_serie_final,$l_serie_inicial,$l_serie_final)) {

			if ($gambiara == 0) {//HD 270590
				echo "Se não retornar nenhuma peça, provavelmente o número de série esteja errado ou a peça não pertence a este modelo de produto !";
				$gambiara = 1;
			}

			continue;

		}

	}

    if (in_array($login_fabrica, [11,104,172]) ) {
        $parametros_adicionais = pg_fetch_result($res,$i,parametros_adicionais);
        $aux = json_decode($parametros_adicionais,TRUE);
        $qtde_demanda = $aux['qtde_demanda'];
    }

    if ($login_fabrica == 123) {
    	$parametros_adicionais = pg_fetch_result($res,$i,'parametros_adicionais');
    }

	$sql_idioma = "SELECT descricao AS peca_descricao FROM tbl_peca_idioma WHERE peca = $peca AND upper(idioma) = '$sistema_lingua'";
	$res_idioma = @pg_query($con,$sql_idioma);

	if (@pg_num_rows($res_idioma) > 0) {
		$peca_descricao    = pg_fetch_result($res_idioma,0,peca_descricao);
		$peca_descricao_js = strtr($peca_descricao, array('"'=>'&rdquo;', "'"=>'&rsquo;'));  //07/05/2010 MLG - HD 235753
	}
	if(empty($tabela)) {
	$resT = @pg_query($con,"SELECT tabela FROM tbl_tabela WHERE fabrica = $login_fabrica AND tbl_tabela.ativa IS TRUE");
	}
	if ($login_fabrica == 74) {
		$resT = pg_query($con,"SELECT tabela FROM tbl_posto_linha JOIN tbl_linha USING(linha) WHERE fabrica=$login_fabrica AND posto=$login_posto");
	}

	if ($login_fabrica == 6) {

		$resT = pg_query($con,"SELECT tabela FROM tbl_posto_linha JOIN tbl_linha using(linha) WHERE posto = $login_posto AND fabrica = $login_fabrica limit 1");

		if (pg_num_rows($resT) <> 1) {
			$resT = pg_query($con,"SELECT tabela FROM tbl_tabela WHERE fabrica = $login_fabrica AND tbl_tabela.ativa IS TRUE");
		}

	}

	if ($login_fabrica == 72 or $login_fabrica > 80) {

		if($login_fabrica == 104){
			if($peca_pedido == "t"){
				$resT = pg_query($con,"SELECT tabela_bonificacao FROM tbl_posto_linha JOIN tbl_linha using(linha) WHERE posto = $login_posto AND fabrica = $login_fabrica limit 1");
				$tabela = pg_fetch_result($resT, 0, 0);
			}else{
				$resT = pg_query($con,"SELECT tabela FROM tbl_posto_linha JOIN tbl_linha using(linha) WHERE posto = $login_posto AND fabrica = $login_fabrica limit 1");
			}
		}else{
			$resT = pg_query($con,"SELECT tabela FROM tbl_posto_linha JOIN tbl_linha using(linha) WHERE posto = $login_posto AND fabrica = $login_fabrica limit 1");
		}


		if (pg_num_rows($resT) <> 1) {
			$resT = pg_query($con,"SELECT tabela FROM tbl_tabela WHERE fabrica = $login_fabrica AND tbl_tabela.ativa IS TRUE");
		}

	}

	if (in_array($login_fabrica, array(40,115,116,121,122,123,124,125,128,129,131,136,138,140,142,145,146)) || isset($novaTelaOs)) {
		$campos = "tabela_posto";

		if ($pedido_em_garantia == "t") {
			$campos = "tabela";
		}

		if ($login_fabrica == 143) {
			$campos = "tabela";
		}

		if ($login_fabrica == 138 && $uso_consumo == "t") {
			$campos = "tabela_bonificacao";
		}

		$resT = pg_query($con,"SELECT {$campos} AS tabela FROM tbl_posto_linha JOIN tbl_linha using(linha) WHERE posto = $login_posto AND fabrica = $login_fabrica and $campos IS NOT NULL limit 1");

	}

	if ($login_fabrica == 2 or $login_fabrica == 50) {

		if (@pg_num_rows($resT) >= 1) {

			if ($login_fabrica == 2) {
				$tabela = 236;
			}

			if ($login_fabrica == 50) {
				$tabela = 213;
			}

			if (strlen($para) > 0) {

				$sqlT = "SELECT preco FROM tbl_tabela_item WHERE tabela = $tabela AND peca = $peca_para";
				$peca_ipi = $peca_para;

			} else {

				$sqlT = "SELECT preco FROM tbl_tabela_item WHERE tabela = $tabela AND peca = $peca";
				$peca_ipi = $peca;

			}

			$resT = pg_query($con,$sqlT);

			if (pg_num_rows($resT) == 1) {

				$sqlipi = "SELECT ipi FROM tbl_peca WHERE peca = $peca_ipi";
				$resipi = pg_query($con,$sqlipi);

				if (pg_num_rows($resipi) > 0) {

					$ipi_peca = pg_fetch_result($resipi, 0, 0);

					if (strlen($ipi_peca)>0 and $ipi_peca <> 0) {
						$preco = (pg_fetch_result($resT,0,0)*(1+($ipi_peca/100)));
					} else {
						$preco = pg_fetch_result($resT,0,0);
					}

				}

			} else {

				$preco = "";

			}

		} else {

			$preco = "";

		}

	} else {

		/*HD-4125402*/
		if ($login_fabrica == 104 && (strlen($tabela) == 0) || empty($tabela)) {
			$resT = pg_query($con,"SELECT tabela_bonificacao FROM tbl_posto_linha JOIN tbl_linha using(linha) WHERE posto = $login_posto AND fabrica = $login_fabrica limit 1");
			$tabela   = pg_fetch_result($resT, 0, 0);
		}

		if (@pg_num_rows($resT) >= 1 or !empty($tabela)) {

			if (!in_array($login_fabrica, array(101, 104)) and pg_num_rows($resT) > 0 ) {//HD 677442
				$tabela = pg_fetch_result($resT, 0, 0);
			}

			if (strlen($para) > 0 and !empty($tabela)) {

				if (in_array($login_fabrica, $vet_ipi)) {//HD 677442 - Valor com IPI
					$sqlP = "SELECT (preco*(1 + (tbl_peca.ipi / 100))) as preco FROM tbl_tabela_item JOIN tbl_peca USING(peca) WHERE tabela = $tabela AND peca = $peca_para";
				} else {
					$sqlP = "SELECT preco FROM tbl_tabela_item WHERE tabela = $tabela AND peca = $peca_para";
				}

			} elseif(!empty($tabela)) {

				if (in_array($login_fabrica, $vet_ipi)) {//HD 677442 - Valor com IPI
					$sqlP = "SELECT (preco*(1 + (tbl_peca.ipi / 100))) as preco FROM tbl_tabela_item JOIN tbl_peca USING(peca) WHERE tabela = $tabela AND peca = $peca";
				} else {
					$sqlP = "SELECT preco FROM tbl_tabela_item WHERE tabela = $tabela AND peca = $peca";
				}

			}

			$resP = pg_query($con,$sqlP);


			$preco = (pg_num_rows($resP) == 1) ? pg_fetch_result($resP,0,0) : '';

			/*HD-4125402*/
			if ($login_fabrica == 104 && (strlen($preco) == 0 || empty($preco))) {
				$resP = pg_query($con, "SELECT (preco*(1 + (tbl_peca.ipi / 100))) as preco FROM tbl_tabela_item JOIN tbl_peca USING(peca) WHERE tabela = $tabela AND peca = $peca");
				$preco = pg_fetch_result($resP, 0, 0);
			}

		} else {

			$preco = '';

		}

	}
	if ($contador > 50) {
		$contador = 0 ;
		echo "<table width='100%' border='0' cellspacing='1' cellspading='0' class='lp_tabela' id='gridRelatorio'>";
		echo "<thead>";
			echo "<tr>";
				if ($login_fabrica == 30) {
					echo "<th>Ref. Antiga</th>";
				}
				#if ($login_fabrica == 14 or $login_fabrica == 24 or $login_fabrica == 66) {
					echo "<th nowrap>Posição</th>";
				#}
				if ($login_fabrica == 3) {
					echo "<th nowrap>Codigo Linha</th>";
				}
				echo "<th nowrap>Peça Referência</th>";
				echo "<th nowrap>Peça Descrição</th>";
				if ($login_fabrica == 1) {
					echo "<th nowrap>Tipo</th>";
				}

				if ($login_fabrica == 123) {
					echo "<th nowrap>Disponibilidade</th>";
				}

				echo "<th nowrap colspan='2'>Outras Informações</th>";
				if( in_array($login_fabrica, array(11,172)) ){
					echo "<th nowrap colspan='2'>Estoque</th>";
				}
			echo "</tr>";
		echo "</thead>";
		echo "<tbody>";
		flush();
	}

	$contador++;
	$cor = (strlen($peca_fora_linha) > 0) ? '#FFEEEE' : '#ffffff';

	echo "<tr bgcolor='$cor'>\n";

	if ($login_fabrica == 30) {
		echo "<td>$referencia_antiga</td>";
	}

	#if ($login_fabrica == 14 or $login_fabrica == 24 or $login_fabrica == 66) {
		echo "<td nowrap>$posicao</td>";
	#}

	if ($login_fabrica == 3) {

		$sql  = "SELECT tbl_linha.codigo_linha FROM tbl_linha WHERE linha = (SELECT tbl_produto.linha FROM tbl_produto JOIN tbl_lista_basica ON tbl_produto.produto = tbl_lista_basica.produto WHERE tbl_lista_basica.peca = $peca LIMIT 1)";
		$resX = pg_query($con,$sql);
		$codigo_linha = @pg_fetch_result($resX, 0, 0);

		if (strlen ($codigo_linha) == 0) $codigo_linha = "&nbsp;";

		echo "<td nowrap>$codigo_linha</td>";

	}

	if($login_fabrica == 91){
		if(strlen(trim($peca_fora_linha))>0)  {
			$fora_linha = true;
			$msg_peca_fora_linha = "Peça $peca_referencia - $peca_descricao Obsoleta. Não é mais fornecida.";
		}else{
			$fora_linha = false;
		}
	}

	if ($login_fabrica == 91 and $bloqueada_venda == "t") {
		echo '<td>' . $peca_referencia . '</td>';
	}elseif($login_fabrica == 91 and $fora_linha == true){
		echo "<td>$peca_referencia</td>";
	} else {
		echo "<td nowrap id='onclick_$i' align='center'><u style='color: blue;!important'>$peca_referencia</u></td>";
	}
	echo "<td nowrap>";

	if (strlen($peca_fora_linha) > 0 OR strlen($para) > 0) {

		 if ($login_fabrica == 3 && $libera_garantia == 't') {

			echo '<a href="javascript: ';

			if (strlen($kit_peca) > 0) {
				echo "window.opener.$('#$kit_peca').html('');";
			}

			echo ">$peca_descricao</a>";

		} else {
			if($login_fabrica == 91 and $fora_linha == TRUE){
				echo $peca_descricao . " ". "<br><span class='fora_linha'>$msg_peca_fora_linha</span>";
			}else{
				echo $peca_descricao;
			}
		}

		if ($login_fabrica == 30 or $login_fabrica == 85) {//HD102404 waldir

			$sql_tabela  = "SELECT tabela
							FROM tbl_posto_linha
							JOIN tbl_linha ON tbl_linha.linha = tbl_posto_linha.linha AND tbl_linha.fabrica = $login_fabrica
							WHERE tbl_posto_linha.posto = $login_posto LIMIT 1;";

			$res_tabela  = @pg_query($con, $sql_tabela);
			$tabela      = trim(@pg_fetch_result($res_tabela, 0, 'tabela'));

			if(strlen(trim($peca_para))>0){
				$sql_preco = "SELECT preco FROM tbl_tabela_item WHERE peca = $peca_para AND tabela = $tabela";
				$res_preco = @pg_query($con, $sql_preco);
				$preco     = trim(@pg_fetch_result($res_preco, 0, 'preco'));
			}
		}

	} else {
		//HD92435 - paulo
		if ($login_fabrica == 30 or $login_fabrica == 85) {

			$sql_tabela  = "SELECT tabela
							FROM tbl_posto_linha
							JOIN tbl_linha ON tbl_linha.linha = tbl_posto_linha.linha AND tbl_linha.fabrica = $login_fabrica
							WHERE tbl_posto_linha.posto = $login_posto LIMIT 1;";

			$res_tabela = @pg_query($con, $sql_tabela);
			$tabela     = trim(@pg_fetch_result($res_tabela, 0, 'tabela'));

			if(strlen(trim($peca))>0){
				$sql_preco = "SELECT preco FROM tbl_tabela_item WHERE peca = $peca AND tabela = $tabela";
				$res_preco = @pg_query($con, $sql_preco);
				$preco     = trim(@pg_fetch_result($res_preco, 0, 'preco'));
			}
		}

		if($login_fabrica == 30 && strlen($os) == 0 && ($peca == 1378800 OR $peca_referencia == "9850002216")){ //hd_chamado=2682154
			echo "<a href=\"javascript: alert('Peça não substituível. \\n \\r Por favor enviar laudo de troca ao Inspetor responsável para análise.')\" " . "style='font-family:Arial, Verdana, Times, Sans-Serif;size:10px;color:#0000FF'>$peca_descricao</a>";
			exit;
		}

		//somente para pedido de pecas faturadas
		if ($login_fabrica == 30 && strlen($preco) == 0 && strlen($os) == 0) {
			echo "<a href=\"javascript: alert('Peça Bloqueada \\n \\r Em caso de dúvidas referente a código de peças que o Sistema não está aceitando, favor entrar em contato com a Fábrica através do Fone: (85) 3299-8992 ou por e-mail: pedidos.at@esmaltec.com.br.')\" " . "style='color:red'>$peca_descricao</a>";
			exit;
		} else {
			if ($login_fabrica == 19 AND strlen($linha) > 0) {//HD 100696

				$caminho            = (in_array($login_fabrica, array(172))) ? $caminho."/11": $caminho."/".$login_fabrica;
				$diretorio_verifica = $caminho."/pequena/";
				$peca_reposicao     = $_GET['peca_reposicao'];

				if ($peca_reposicao == 't') {

					echo "<a href='produto_pesquisa_lista_nv.php?peca=$peca&tipo=referencia' " . "target='_blank' " . "style='color:red'>" . $peca_descricao . "</a>";

				} else {

					if (is_dir($diretorio_verifica) == true) {

						if ($dh = opendir($caminho."/pequena/")) {

							$contador = 0;

							while (false !== ($filename = readdir($dh))) {

								if ($contador == 1) break;

								if (strpos($filename,$peca) !== false) {

									$contador++;
									$po = strlen($peca);

									if (substr($filename, 0,$po) == $peca) {

										$img = explode(".", $filename);
										echo "<a href='$caminho/pequena/$img[0].pdf' target='_blank' " . "style='color:red'>" .	$peca_descricao . "</a>";
									}

								}

							}

						}

						if ($contador == 0) {

							if ($dh = opendir($caminho."/pequena/")) {

								$contador = 0;

								while (false !== ($filename = readdir($dh))) {

									if ($contador == 1) break;

									if (strpos($filename,$peca_referencia) !== false) {

										$contador++;
										$po = strlen($peca_referencia);

										if (substr($filename, 0, $po) == $peca_referencia) {

											$img = explode(".", $filename);
											echo "<a href='$caminho/pequena/$img[0].pdf' target='_blank' " . "style='color:red'>" . $peca_descricao . "</a>";
										}

									}

								}

							}

						}

					} else {
						echo "<span style='color:red'>$peca_descricao</a>";
					}

				}

			} else {

				if ($login_fabrica == 94 AND $faturado == "sim") {

					if (!empty($produto)) {
						$cond = " AND tbl_lista_basica.produto = $produto ";
					}

					$sqlLinha = "SELECT linha
									FROM tbl_produto
									JOIN tbl_lista_basica ON tbl_lista_basica.produto = tbl_produto.produto AND tbl_lista_basica.fabrica = $login_fabrica
									WHERE tbl_lista_basica.peca = $peca
									$cond";

					$resLinha = pg_query($con,$sqlLinha);

					if (pg_numrows($resLinha) > 0) {

						$linha = pg_result($resLinha, 0, 0);

						if ($linha == 624) {

							$sqlPeca = "SELECT tbl_peca.descricao
										  FROM tbl_lista_basica
										  JOIN tbl_peca using(peca)
										 WHERE tbl_lista_basica.peca = $peca
										 $cond
										   AND tbl_peca.descricao like 'COMPRESSOR%'";

							$resPeca = pg_query($con, $sqlPeca);

							if (pg_numrows($resPeca) == 0) {
								//$preco = $preco - ($preco * 0.45);
							}

						}

					}

				}

				if ($somente_kit == 't' and $peca_pedido <> 't') {//HD 335675
					echo '<span';
					$color = 'black';
				} else {
					echo '<a href="javascript: ';
					if ($login_fabrica == 30) {
						echo "if (qtde) {qtde.value='1';}";
					}
					if (strlen($kit_peca) > 0) {
						echo "window.opener.$('#$kit_peca').html('');";
					}
					$color = 'blue';
				}

				echo '">'.$peca_descricao;

				if ($somente_kit == 't' and $peca_pedido <> 't') {//HD 335675
					echo '</span>';
				} else {
					echo '</a>';
				}
			}
		}

	}

	echo "</td>\n";

	if ($login_fabrica == 1) {
		echo "<td nowrap>ds$type</td>";
	}

	if ($login_fabrica == 123) {

		$sqlEstoque = " SELECT tbl_posto_estoque.peca,
							sum(tbl_posto_estoque.qtde) as qtde
						FROM tbl_posto_estoque
						INNER JOIN tbl_peca ON tbl_peca.peca = tbl_posto_estoque.peca  AND tbl_peca.fabrica = $login_fabrica
						WHERE qtde > 0
						AND tbl_posto_estoque.peca = $peca
						GROUP BY 1";						
		$resEstoque = pg_query($con, $sqlEstoque);
		if (pg_num_rows($resEstoque) > 0) {
			$disponibilidadeLabel = "Disponível";
		} else {
			$disponibilidadeLabel = "Indisponível";
		}

		echo "<td nowrap>$disponibilidadeLabel</td>";
	}

	$sqlX =	"SELECT   DISTINCT referencia, to_char(tbl_peca.previsao_entrega,'DD/MM/YYYY') AS previsao_entrega
			FROM  tbl_peca
			WHERE referencia_pesquisa = UPPER('$peca_referencia')
			AND   fabrica = $login_fabrica
			AND   previsao_entrega NOTNULL;";

	$resX = pg_query($con, $sqlX);

	if (pg_num_rows($resX) == 0) {

		echo "<td nowrap>";

		if ($login_fabrica == 91 and $bloqueada_venda == 't') {
			echo '<strong style="color: #FF0000;">Peça Critica, contatar a fábrica pelo 0800 para possível liberação da mesma em seu pedido de compra</strong>';
		}

		if (strlen($peca_fora_linha) > 0) {

			echo "<span>";

			if ($login_fabrica == 1) {
				echo "É obsoleta,não é mais fornecida";
			} else {

				if ($login_fabrica == 3 AND $libera_garantia == 't') {
					echo "Disponível somente para garantia.<br /> Caso necessário, favor contatar a Assistência Técnica Britânia";
				} else {
					echo "Fora de linha";
				}

			}

			echo "</span>";

		} else {

			/* HD  152192 a Esmaltec não faz pedido pela OS, somente informa troca com peça do posto
			e geralmente o posto comprou com o codigo da peça antiga, por isto não vai passar no de-para */
			if (strlen($para) > 0 ) {

				if ($login_fabrica == 30) {

					echo "<a href=\"javascript: ";
					echo "if (qtde) {qtde.value='1';} ";
					echo "window.opener.$('#$kit_peca').html('');";
					echo " \" style='color:red'>$peca_descricao</a>";

				} else {

					if (strlen($peca_descricao) > 0) {#HD 228968

						$sql_idioma = "SELECT tbl_peca_idioma.descricao AS peca_descricao FROM tbl_peca_idioma JOIN tbl_peca USING(peca) WHERE tbl_peca.descricao = '$peca_descricao' AND upper(idioma) = '$sistema_lingua'";

						$res_idioma = @pg_query($con, $sql_idioma);

						if (@pg_num_rows($res_idioma) > 0) {
							$peca_descricao  = preg_replace('/([\"|\'])/','\\$1',(pg_fetch_result($res_idioma, 0, 'peca_descricao')));
						}

					}

					if($login_fabrica == 91){
						if (strlen($para) > 0) {

							$sql_para = "SELECT bloqueada_venda FROM tbl_peca WHERE referencia = '$para'";
	
							$res_para = @pg_query($con, $sql_para);
	
							if (@pg_num_rows($res_para) > 0) {
								$bloqueada_venda  = pg_fetch_result($res_para, 0, 'bloqueada_venda');

								if ($bloqueada_venda == 't') {
									$critica = '<br><strong style="color: #FF0000;">Peça Critica, contatar a fábrica pelo 0800 para possível liberação da mesma em seu pedido de compra</strong>';
								}
							}
						}
					}

					echo "<span>Mudou Para</span>";
					echo " <a href=\"javascript: ";

					if ($login_fabrica == 30) {
						echo " if (qtde) {qtde.value='1';} ";
					}

					if (strlen($kit_peca) > 0) {
						echo "window.opener.$(\"#$kit_peca\").html(\"\");";
					}
					echo "\"style='color:red'>$para</a>".$critica;

				}

			} else {
				echo "&nbsp;";
			}

			if ($somente_kit == 't' and $peca_pedido <> 't') {//HD 335675

				$sql_kit = "SELECT tbl_kit_peca.referencia,
								   tbl_kit_peca.descricao,
								   tbl_kit_peca.kit_peca
							  FROM tbl_kit_peca_peca
							  JOIN tbl_peca              ON tbl_peca.peca                 = tbl_kit_peca_peca.peca
							  JOIN tbl_kit_peca          ON tbl_kit_peca.kit_peca         = tbl_kit_peca_peca.kit_peca
							  JOIN tbl_kit_peca_produto  ON tbl_kit_peca_produto.kit_peca = tbl_kit_peca.kit_peca
							  JOIN tbl_lista_basica      ON tbl_lista_basica.peca         = tbl_peca.peca
							 WHERE tbl_kit_peca_produto.fabrica = $login_fabrica
							   AND tbl_kit_peca_produto.produto = $produto
							   AND tbl_lista_basica.produto     = $produto
							   AND tbl_kit_peca_peca.peca       = $peca;";

				$res_kit = pg_query($con, $sql_kit);
				$tot_kit = pg_num_rows($res_kit);

				for ($yy = 0; $yy < $tot_kit; $yy++) {

					$kit_peca_kit = @pg_result($res_kit, $yy, 'kit_peca');
					$ref_kit      = @pg_result($res_kit, $yy, 'referencia');
					$des_kit      = @pg_result($res_kit, $yy, 'descricao');

					echo "<a href=\"javascript: ";
					echo "kitPeca('$kit_peca_kit','$kit_peca'); \">$des_kit</a>";

				}

			}

		}

		echo "</td>\n";

		echo "<td nowrap align='right'>";


		$xpecas  = $tDocs->getDocumentsByRef($peca, "peca");
		if (!empty($xpecas->attachListInfo)) {

			$a = 1;
			foreach ($xpecas->attachListInfo as $kFoto => $vFoto) {
			    $fotoPeca = $vFoto["link"];
			    if ($a == 1){break;}
			}
			echo "<a href=\"javascript:mostraPeca('$fotoPeca','$peca')\">";
			echo "<img src='$fotoPeca' border='0'  height='50'>";
			echo "</a>";
		} else {


			$diretorio_verifica = $caminho."/pequena/";

			if (is_dir($diretorio_verifica) == true) {

				if ($dh = opendir($caminho."/pequena/")) {

					$contador = 0;

					while (false !== ($filename = readdir($dh))) {

						$peca_img = $peca;

						if($contador == 1) break;

						if(in_array($login_fabrica, array(172))){

							$sql_peca_img = "SELECT peca FROM tbl_peca WHERE referencia = '{$peca_referencia}' AND fabrica = 11 ";
							$res_peca_img = pg_query($con, $sql_peca_img);

							if(pg_num_rows($res_peca_img) > 0){
								$peca_img = pg_fetch_result($res_peca_img, 0, "peca");
							}

						}

						if (strpos($filename, $peca_img) !== false) {

							$contador++;
							$po = strlen($peca_img);

							if (substr($filename, 0, $po) == $peca_img) {
								echo "<a href=\"javascript:mostraPeca('$filename','$peca')\">";
								echo "<img src='$caminho/pequena/$filename' border='0'>";
								echo "</a>";
							}

						}

					}

				}

				if ($contador == 0) {

					if ($dh = opendir($caminho."/pequena/")) {

						$contador = 0;

						while (false !== ($filename = readdir($dh))) {

							if ($contador == 1) break;

							if (strpos($filename, $peca_referencia) !== false) {

								$contador++;
								$po = strlen($peca_referencia);

								if (substr($filename, 0, $po) == $peca_referencia) {
									echo "<a href=\"javascript:mostraPeca('$filename','$peca')\">";
									echo "<img src='$caminho/pequena/$filename' border='0'>";
									echo "</a>";
								}

							}

						}

					}

				}

			}
		}
		// HD-7774631
		if ($login_fabrica == 123 && $disponibilidadeLabel == "Indisponível" && 1==2) {
			if (!empty($parametros_adicionais)) {
				$parametros_adicionais = json_decode($parametros_adicionais, true);
				if (isset($parametros_adicionais["previsaoEntrega"])) {
					if (strtotime(date("Y-m-d")) > strtotime($parametros_adicionais["previsaoEntrega"])) {
						$prev = "Previsão de Recebimento dessa Peça em 90 dias, Sujeito a Alteração";
					} else {
						$dataPrev = date("d/m/Y", strtotime($parametros_adicionais["previsaoEntrega"]));
						$prev = $dataPrev;
					}

				} else {
					$prev = "Previsão de Recebimento dessa Peça em 90 dias, Sujeito a Alteração";
				}
			} else {
				$prev = "Previsão de Recebimento dessa Peça em 90 dias, Sujeito a Alteração";
			}
			echo $prev;
		} else if ($login_fabrica == 123) {
			$prev = "";
		}

		echo "</td>\n";

		if ($login_fabrica == 3 AND $peca == '526199') {
			echo "</tr>";
			echo "<tr>";
			echo "<td colspan='4'align='center'><img src='imagens_pecas/526199.gif' class='Div'>";
			echo "</td>\n";
		}

		if( in_array($login_fabrica, array(11,172)) ){
			if($qtde_estoque > 0){
				$estoque = "<font color='green'>DISPONÍVEL</font>";
			}else{
				$estoque = "<font color='red'>INDISPONÍVEL</font>";
			}

			echo "<td>".$estoque."</td>";
		}

	} else {

		echo "</tr>\n";
		echo "<tr>\n";

		$peca_previsao      = pg_fetch_result($resX,0,0);
		$previsao_entrega   = pg_fetch_result($resX,0,1);
		$data_atual         = date("Ymd");
		$x_previsao_entrega = substr($previsao_entrega,6,4) . substr($previsao_entrega,3,2) . substr($previsao_entrega,0,2);

		echo "<td colspan='2'>";

		if ($data_atual < $x_previsao_entrega) {
			if ($sistema_lingua == 'ES')
				echo "Este repuesto estará disponible en: $previsao_entrega";
			else
				echo "Esta peça estará disponível em $previsao_entrega";
			echo "<br />";
			if ($sistema_lingua == 'ES')
				echo "Para repuestos con plazo de entrega superior a 25 días, el fabricante tomará las medidas necesarias para atender al consumidor";
			else
				 echo "Para as peças com prazo de fornecimento superior a 25 dias, a fábrica tomará as medidas necessárias para atendimento do consumidor";
		}

		echo "</td>\n";

	}
	echo "</tr>\n";

	if ($exibe_mensagem == 't' AND $bloqueada_garantia == 't' and $login_fabrica == 3) {
		echo "<tr>\n";
		echo "<td colspan='4'>\n";
		echo "A peça $referencia necessita de autorização da Britânia para atendimento em garantia.";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if($login_fabrica == 104 and $peca_pedido =='t') {
		$desconto = 0 ;
	}

	if ($desconto > 0) {
		$preco = $preco - ( ($preco * $desconto) /100 );
	}

	if ($login_fabrica == 94) {
		$preco = number_format($preco,3,",",".");
	}elseif(in_array($login_fabrica,array(115,116,121,122,81,123,125,114,128))) {
		$preco = number_format($preco,4,",",".");
	}
	else {
		$preco = number_format($preco,2,",",".");
	}

	$OCreferencia_antiga = $referencia_antiga;
	$OCposicao = $posicao;
	$OCcodigo_linha = $codigo_linha;
	$OCpeca_referencia = $peca_referencia;
	$OCpeca_descricao = $peca_descricao;
	$OCpreco = $preco;
	$OCpeca = $peca;
	$OCtype = $type;
	$OCqtde_demanda = $qtde_demanda;

	if ($login_fabrica == 123) {
		$OCprevEntrega = $prev;
		$OCdisponibilidade = $disponibilidadeLabel;
	} else {
		$OCprevEntrega = '';
		$OCdisponibilidade = '';
	}

	if (strlen($peca_fora_linha) > 0 OR strlen($para) > 0) {
		if ($login_fabrica == 3 && $libera_garantia == 't') {
			$OCpeca_descricao = $peca_descricao_js;
		}
	}
	if ($login_fabrica == 30 && strlen($preco) == 0 && strlen($os) == 0)
	{}
	else
	{
		if ($login_fabrica == 19 AND strlen($linha) > 0)
		{}
		else
		{
			if ($somente_kit <> 't') {
				$OCpeca_descricao = $peca_descricao_js;
			}
		}
	}
	if (pg_num_rows($resX) == 0)
	{
		if (strlen($peca_fora_linha) == 0)
		{
			if (strlen($para) > 0) {
				if ($login_fabrica <> 30)
				{
					$OCpeca_referencia = $para;
				}
			}
		}
		if (strlen($peca_fora_linha) == 0)
		{
			if ($somente_kit == 't' and $peca_pedido <> 't') {
				$OCpeca_referencia = $ref_kit;
				$OCpeca_descricao = '';
				$OCpreco = '';
			}
		}
	}


	$conteudo_onclick = "'$OCreferencia_antiga','$OCposicao','$OCcodigo_linha','$OCpeca_referencia','$OCpeca_descricao','$OCpreco','$OCpeca','$OCtype','$input_posicao','$OCqtde_demanda','$OCprevEntrega','$OCdisponibilidade'";

	echo "<script> $('#onclick_$i').click(function(){
		window.parent.retorna_lista_peca($conteudo_onclick);
		window.parent.Shadowbox.close();
	}); </script>";


}

echo "</tbody></table>\n";
}
?>

</body>
</html>
