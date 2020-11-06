<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';
include_once "../class/tdocs.class.php";
$tDocs = new TDocs($con, $login_fabrica);

$vet_ipi = array(94,101,104,105,106,115,116,117,120,121);
$mesNSLatina = array(
					"A" => "01",
					"B" => "02",
					"C" => "03",
					"D" => "04",
					"E" => "05",
					"F" => "07",
					"G" => "08",
					"H" => "09",
					"I" => "10",
					"J" => "11",
					"L" => "12",
				);
#include 'cabecalho_pop_pecas.php';
header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");

$caminho        = "../imagens_pecas";
if ($login_fabrica <> 10 and $login_fabrica <> 6 and $login_fabrica <> 19) {
	$caminho    = $caminho."/".$login_fabrica;
}

$ajax           = $_GET['ajax'];
$ajax_kit       = $_GET['ajax_kit'];
$kit_peca_id    = $_GET['kit_peca_id'];
$kit_peca       = $_GET['kit_peca'];
$os             = $_GET['os'];
$versao_produto = $_GET['versao_produto'];

if(in_array($login_fabrica, [131,134])){
	$codigo_defeitos = $_GET['defeito_constatado'];
	$codigo_defeitos = json_decode(str_replace('\\', "", $codigo_defeitos));
	foreach ($codigo_defeitos as $value) {
		if($value != ""){
			$codigo_defeitos_aux[] = "'".$value."'";
		}
	}
	$codigo_defeitos = implode(',',$codigo_defeitos_aux);
}

if(!empty($os)){
	$sql ="SELECT posto FROM tbl_os where os = $os";
	$res = pg_query($con,$sql);
	$login_posto = (pg_num_rows($res) > 0) ? pg_fetch_result($res,0,0):0;

}
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
		$optionFornecedor = "";
		$optionDefeito = "";

		for ($i = 0; $i < pg_num_rows($res); $i++) {

			$peca     = pg_fetch_result($res, $i, 'peca');
			$qtde_kit = pg_fetch_result($res, $i, 'qtde');

			if ($login_fabrica == 91) {

				 $sqlFor = "SELECT tbl_fornecedor_peca.fornecedor AS retorno_fornecedor_peca, tbl_fornecedor.nome AS retorno_fornecedor_peca_nome
				              FROM tbl_fornecedor_peca
				              JOIN tbl_fornecedor  ON  tbl_fornecedor.fornecedor = tbl_fornecedor_peca.fornecedor AND tbl_fornecedor_peca.peca = {$peca}
				             WHERE tbl_fornecedor_peca.fabrica = $login_fabrica";

	    		$resFor  = pg_query($con, $sqlFor);
				$retornoFor = pg_fetch_all($resFor);
                unset($optionFornecedor);
		        foreach($retornoFor as $k => $v){
		            $optionFornecedor .= "<option value='".$v['retorno_fornecedor_peca']."'>".$v['retorno_fornecedor_peca_nome']."</option>";
		        }

				$selectFornecedor = "<td>
									<select name='kit_fornecedor_$peca' id='kit_fornecedor_$peca'>
										<option value='' selected>Selecione um fornecedor</option>
										{$optionFornecedor}
									</select>
					                 </td>";

				$sqlDef = " SELECT  tbl_defeito.descricao      ,
                                    tbl_defeito.defeito        ,
                                    tbl_defeito.codigo_defeito ,
                                    tbl_peca_defeito.ativo
                            FROM    tbl_peca_defeito
                            JOIN    tbl_defeito ON  tbl_defeito.defeito = tbl_peca_defeito.defeito
                                                AND tbl_defeito.fabrica = {$login_fabrica}
                            JOIN    tbl_peca    ON  tbl_peca.peca       = tbl_peca_defeito.peca
                                                AND tbl_peca.fabrica    = {$login_fabrica}
                                                AND tbl_peca.peca       = $peca
                            WHERE   tbl_peca_defeito.ativo = 't'
                            AND     tbl_defeito.ativo      = 't'
                      ORDER BY      tbl_peca.descricao,
                                    tbl_defeito.descricao";

	    		$resDef  = pg_query($con, $sqlDef);
				$retornoDef = pg_fetch_all($resDef);
                unset($optionDefeito);
		        foreach($retornoDef as $k => $v){
		            $optionDefeito .= "<option value='".$v['defeito']."'>".$v['codigo_defeito']." - ".$v['descricao']."</option>";
		        }

				$selectDefeito = "<td>
									<select name='kit_defeito_$peca' id='kit_defeito_$peca'>
										<option value='' selected>Selecione um defeito</option>
										{$optionDefeito}
									</select>
					                 </td>";
			}

			$resultado .=   "<tr>".
							"<td>".
                "<input type='".(($login_fabrica == 15 || $login_fabrica == 91 || $login_fabrica ==3) ? 'hidden' : 'checkbox')."' name='kit_peca_$peca' value='$peca' CHECKED > ".
						    "<input type='text' name='kit_peca_qtde_$peca' id='kit_peca_qtde_$peca' size='5' value='$qtde_kit' onkeyup=\"re = /\D/g; this.value = this.value.replace(re, '');\" readonly='readonly'> x ".
							pg_fetch_result($res,$i,'referencia').
							"</td>".
							"<td colspan='4'> - ". pg_fetch_result($res,$i,'descricao'). "</td>
							$selectFornecedor
							$selectDefeito
							</tr>
							";
		}

		$resultado .= "</table>";

		echo "ok|$resultado";

	}

	exit;

}

if (strlen($ajax) > 0) {

	$arquivo = $_GET['arquivo'];
	$idpeca = $_GET['idpeca'];



	echo "	<table align='center'>
		<tr>
			<td align='right'>
				<a href='javascript:escondePeca();' style='font-size: 10px color:white;font-weight:bold'>FECHAR</a>
			</td>
		</tr>
		<tr>
			<td align='center'>";


	$xpecas = $tDocs->getDocumentsByRef($idpeca, "peca");
	if (!empty($xpecas->attachListInfo)) {

		$a = 1;
		foreach ($xpecas->attachListInfo as $kFoto => $vFoto) {
		    $fotoPeca = $vFoto["link"];
		    if ($a == 1){break;}
		}
		echo "<a href=\"javascript:escondePeca();\">
		<img src='$fotoPeca' height='50' border='0'>
		</a>";
	} else {
		echo "<a href=\"javascript:escondePeca();\">
		<img src='$caminho/media/$arquivo' border='0'>
		</a>";

	}


echo "
			</td>
		</tr>
	</table>";

	exit;

}

$defeito_constatado = $_GET['defeito_constatado'];

if($login_fabrica == 19){
	$defeito_constatado = str_replace("\"", '', $defeito_constatado);
	$defeito_constatado = str_replace("\\", '', $defeito_constatado);
	$defeito_constatado = json_decode($defeito_constatado, true);
}


if (in_array($login_fabrica, array(19,40)) && isset($_GET['defeito_constatado']) && (is_numeric($defeito_constatado) OR is_array($defeito_constatado))) {

	if ($defeito_constatado == '') {
		echo "<script>alert('Selecione Defeito Constatado'); setTimeout('self.close();',1000)</script>";
		exit;
	}

	if($login_fabrica == 19){
		$defeito_constatado = implode(',', $defeito_constatado);
		$where_masterfrio = " AND (tbl_peca_defeito_constatado.defeito_constatado IN($defeito_constatado))";
	}else{
		$where_masterfrio = " AND (tbl_peca_defeito_constatado.defeito_constatado = $defeito_constatado OR tbl_peca_defeito_constatado.defeito_constatado IS NULL)";
	}

	$cond_masterfrio  = "LEFT JOIN tbl_peca_defeito_constatado ON (tbl_peca_defeito_constatado.peca = tbl_peca.peca)	";

}

$cond_versao = null;
if ($usa_versao_produto and strlen($versao_produto)) {
	$cond_versao = " AND (tbl_lista_basica.type IS NULL OR tbl_lista_basica.type = '$versao_produto')";
}

$exibe_mensagem = 't';

if (strpos($_GET['exibe'],'pedido') !== false) $exibe_mensagem = 'f';

$faturado = $_GET['faturado']; # se for compra faturada, n&atilde;o precisa de validar.
if(!empty($login_posto)){
	if ($login_fabrica == 101 or $login_fabrica == 104 or $login_fabrica == 105) {# verifica se posto pode ver pecas de itens de aparencia

		$sql = "SELECT tbl_posto_linha.tabela,
					   tbl_posto_linha.tabela_posto
				  FROM tbl_posto_linha
				  JOIN tbl_linha using(linha)
				 WHERE tbl_posto_linha.posto = $login_posto
				   AND tbl_linha.fabrica     = $login_fabrica";

	} else {

		$sql = "SELECT  tbl_posto_fabrica.item_aparencia,
						tbl_posto_fabrica.tabela
					FROM tbl_posto
					JOIN tbl_posto_fabrica USING(posto)
					WHERE tbl_posto.posto           = $login_posto
					AND   tbl_posto_fabrica.fabrica = $login_fabrica";

	}
	$res = pg_query($con,$sql);

	if (pg_num_rows ($res) > 0) {

		if ($faturado == 'sim') {

			$item_aparencia = 't';

		} else {
			$item_aparencia = pg_fetch_result($res, 0, 'item_aparencia');
			if(!empty($login_admin)) $item_aparencia = 't';
		}

		$tabela = $login_fabrica == 101 && $faturado == 'sim' ? pg_fetch_result($res, 0, 'tabela_posto') : pg_fetch_result($res, 0, 'tabela');

		if (($login_fabrica == 104 or $login_fabrica == 105) && $faturado == 'sim') {
			$tabela = pg_fetch_result($res, 0, 'tabela');
		}

		if ($login_fabrica == 2) $tabela = 236; # HD112438

	}
}
/*Modificado por Fernando
Pedido de Leandro da Tectoy por E-mail. Modifica&ccedil;&atilde;o foi feita para que os postos
que n&atilde;o podem fazer pedido em garantia (OS) de pe&ccedil;as, cadastradas como item aparencia, possa
fazer pedido faturado atravÃ©s da tela "pedido_cadastro.php".
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
<title> Pesquisa Pe&ccedil;as pela Lista B&aacute;sica ... </title>
<meta name="Author" content="">
<meta name="Keywords" content="">
<meta name="Description" content="">
<meta http-equiv=pragma content=no-cache>
<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
<link rel="stylesheet" type="text/css" href="../css/posicionamento.css">
<link rel="stylesheet" type="text/css" href="../js/thickbox.css" media="screen">
<script src="../js/thickbox.js" type="text/javascript"></script>
<script src="../plugins/jquery/jquery.tablesorter.min.js" type="text/javascript"></script>
<link rel="stylesheet" type="text/css" href="../css/lupas/lupas.css">
<?php include "javascript_calendario_new.php"; ?>
</head>

<style>
	body {
		margin: 0;
		font-family: Arial, Verdana, Times, Sans;
		background: #fff;
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

function kitPeca(kit_peca_id,kit_peca,i) {

	var id_defeito = kit_peca.replace('kit_peca_', '');

	$.ajax({
		type: 'GET',
		url: '<?=$PHP_SELF?>',
		data: 'kit_peca_id='+kit_peca_id+'&kit_peca='+kit_peca+'&ajax_kit=sim',
		beforeSend: function(){
			window.parent.$('#'+kit_peca).html(' ');
		},
		complete: function(resposta) {

			resultado = resposta.responseText.split('|');

			if (resultado[0].trim() == 'ok') {

				window.parent.$('#'+kit_peca).append(resultado[1]);
				window.parent.$("input[name=kit_kit_peca_"+i+"]").val(kit_peca_id);

			} else {

				window.parent.$('#'+kit_peca).html(' ');

			}

			window.parent.Shadowbox.close();

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

	$sql   = "SELECT numero_serie_obrigatorio,serie from tbl_os JOIN tbl_produto USING(produto) where os = $os and fabrica = $login_fabrica";
	$res   = @pg_query($con, $sql);
	$numero_serie_obrigatorio = @pg_fetch_result($res,0,numero_serie_obrigatorio);
	$serie = @pg_fetch_result($res,0,serie);

	if (strlen($serie) == 0 AND $numero_serie_obrigatorio == 't') {
		$msg_erro = "Por favor, preencher primeiro o número de série do produto.";
	}

	if (strlen($serie) > 0 AND $numero_serie_obrigatorio == 't') {
		$aux_serie = substr($serie,1,1);
	}
	// 14/01/2010 MLG - HD 189523
	if (!function_exists("is_between")) {
		function is_between($valor,$min,$max) {// BEGIN function is_between
		    // Devolve 'true' se o valor est&aacute; entre ("between") o $min e o $max
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
		    $max_serie_lbm	= (trim($lbm_max_ver)	== "") ? "z" : strtoupper($lbm_max_ver); // O minÃºsculo Ã© proposital...

			if (is_between(strtoupper($serie[1]),$min_serie_prod, $max_serie_prod) or !$usar_serie_produto) {
				if (is_between(strtoupper($serie[1]),$min_serie_lbm, $max_serie_lbm)) {
				    $serie_ok = true;
				}
			}

		}

		return $serie_ok;

	}

}

if (strlen ($produto) > 0) {

	$produto_referencia = trim($_REQUEST['produto']);
	$produto_referencia = str_replace(".","",$produto_referencia);
	$produto_referencia = str_replace(",","",$produto_referencia);
	
	#$produto_referencia = str_replace("-","",$produto_referencia);
	#$produto_referencia = str_replace("/","",$produto_referencia);
	$produto_referencia = str_replace(" ","",$produto_referencia);

	$voltagem = trim(strtoupper($_GET["voltagem"]));
	
	$campo_pesquisa = ($login_fabrica <> 124) ? "referencia_pesquisa" : "referencia";

	$sql = "SELECT tbl_produto.produto, tbl_produto.descricao,lista_troca
			FROM   tbl_produto
			JOIN   tbl_linha USING (linha)
			WHERE  UPPER(tbl_produto.{$campo_pesquisa}) = UPPER('$produto_referencia') ";

	if (strlen($voltagem) > 0 AND $login_fabrica == "1" ) $sql .= " AND UPPER(tbl_produto.voltagem) = UPPER('$voltagem') ";

	$sql .= "AND tbl_linha.fabrica = $login_fabrica ";

	if ($login_fabrica != 3) $sql .= " AND tbl_produto.ativo IS TRUE ";

	$res = pg_query($con,$sql);

	if (pg_num_rows($res) > 0) {

		$produto_descricao = pg_fetch_result($res, 0, 'descricao');
		$produto           = pg_fetch_result($res, 0, 'produto');
		$lista_troca       = pg_fetch_result($res, 0, 'lista_troca');
		if($login_fabrica != 140){
			$join_produto      = " JOIN tbl_lista_basica USING (peca) JOIN tbl_produto USING (produto)";
		}
		$condicao_produto  = " AND tbl_produto.produto = $produto ";

		if ($login_fabrica == 96) {
			$join_produto      = " ";
			$condicao_produto  = " ";
		}

	} else {

		$produto = '';

	}

	if ($login_fabrica == 1) {
        $sqlGarPeca = "
            SELECT  COUNT(1) AS garantia_peca
            FROM    tbl_produto
            WHERE   produto = $produto
            AND     descricao ILIKE 'Garantia de pe%as'
        ";
        $resGarPeca = pg_query($con,$sqlGarPeca);

        $garantia_peca = pg_fetch_result($resGarPeca,0,garantia_peca);
    }

}

$cond_produto =" AND 1 = 1 ";

if ($login_fabrica <> 3 && $login_fabrica <> 140 && !empty($produto)) $cond_produto = " AND tbl_produto.ativo IS TRUE " ;

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
	echo "<form action='".$_SERVER['REQUEST_URI']."' method='POST' name='nova_pesquisa'>";
		echo "<input type='hidden' name='voltagem' value='$voltagem' />";
		echo "<input type='hidden' name='tipo' id='tipo' value='$tipo' />";
		echo "<input type='hidden' name='input_posicao' id='input_posicao' value='$input_posicao'>";
		echo "<input type='hidden' name='produto' id='produto' value='$produto_referencia'>";
		echo "<table cellspacing='1' cellpadding='2' border='0'>";
			echo "<tr>";
				echo "<td>";
					echo "Refer&ecirc;ncia:<input type='text' name='peca' id='peca' value='$peca' onclick='$(\"#tipo\").val(\"referencia\");'>";
				echo "</td>";
				echo "<td>";
					echo "Descri&ccedil;&atilde;o:<input type='text' name='descricao' id='descricao' value='$descricao' onclick='$(\"#tipo\").val(\"descricao\");'>";
				echo "</td>";
					if ($login_fabrica == 14 or $login_fabrica == 66)
					{
						echo "<td>";
							echo "Posi&ccedil;&atilde;o:<input type='text' name='posicao' id='posicao' value='$posicao' onclick='$(\"#tipo\").val(\"posicao\");'>";
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
		echo "Pesquisando pela Lista B&aacute;sica do Produto $produto_descricao";
	echo "</div>";
}


//REMOVER 199776,199777 após testar pois são produtos do banco de teste
$vet_gar    = (in_array($produto, array(199776,199777,200253,200254)) && $login_fabrica == 42);//HD 400603

if ($login_fabrica == 30) {
	$or_busca_referencia = " OR tbl_esmaltec_referencia_antiga.referencia_antiga LIKE '%$referencia%' ";
}

if ((in_array($login_fabrica,array(3,15,24,30,91))) && $produto <> "") {//HD 258901 - KIT

	if ($login_fabrica == 24) {

		$sql = " SELECT tbl_kit_peca.referencia,
						tbl_kit_peca.descricao,
						tbl_kit_peca.kit_peca
				FROM    tbl_kit_peca
				WHERE   tbl_kit_peca.fabrica = $login_fabrica
				AND tbl_kit_peca.produto = $produto";

	} else if (in_array($login_fabrica,array(3,15,30,91))) {
    
		$sql = "SELECT tbl_kit_peca.referencia,
					   tbl_kit_peca.descricao,
					   tbl_kit_peca.kit_peca
				  FROM tbl_kit_peca
				  JOIN tbl_kit_peca_produto ON tbl_kit_peca_produto.kit_peca = tbl_kit_peca.kit_peca
				  
				  WHERE tbl_kit_peca_produto.fabrica = $login_fabrica";

		if (!empty($produto)) {
			$sql .=	" AND tbl_kit_peca_produto.produto = $produto";
		}
				  
	}

	if($login_fabrica == 91) { 
		if (strlen($descricao) > 0)  $sql .= " AND UPPER(TRIM(tbl_kit_peca.descricao))  LIKE UPPER(TRIM('%$descricao%'))";
		if (strlen($referencia) > 0) $sql .= " AND UPPER(TRIM(tbl_kit_peca.referencia)) LIKE UPPER(TRIM('%$referencia%'))";
	}

	$sql .= " ORDER BY tbl_kit_peca.descricao ";

	$res = pg_query($con,$sql);

	if (pg_num_rows($res) > 0) {

		$kit_peca_sim = "sim";
		echo "KIT de Pe&ccedil;as";
		echo "<table width='100%' border='0' cellspacing='1' cellspading='0' class='lp_tabela' id='gridRelatorio'>";

		for ($i = 0; $i < pg_num_rows($res); $i++) {

			$kit_peca_id    = pg_fetch_result($res, $i, 'kit_peca');
			$descricao_kit  = pg_fetch_result($res, $i, 'descricao');
			$referencia_kit = pg_fetch_result($res, $i, 'referencia');

			$cor = ($i % 2 <> 0) ? '#F7F5F0' : '#F1F4FA';

			echo "<tr bgcolor='$cor'>";
				echo "<td onclick='window.parent.retorna_lista_peca(\"\",\"\",\"\",\"$referencia_kit\",\"$descricao_kit\",\"\",\"\",\"\",\"$input_posicao\",\"$kit_peca_id\"); kitPeca(\"$kit_peca_id\",\"$kit_peca\",\"$input_posicao\");'>{$referencia_kit}</td>";
				echo "<td>";
					echo "$descricao_kit";
				echo "</td>";
			echo "</tr>";

		}

		echo "</table>";
		echo "<br />";

	}

}

$res  = pg_query($con, "SELECT COUNT(*) FROM tbl_lista_basica WHERE tbl_lista_basica.fabrica = $login_fabrica");
$qtde = pg_fetch_result($res, 0, 0);


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

	$or_busca_ref='';
	if ($login_fabrica == 88){
		$or_busca_ref=" OR (UPPER(TRIM(tbl_peca.referencia)) LIKE UPPER(TRIM('%referencia%'))) ";
	}
	if (strlen($referencia) > 0) {
		$sql .= " AND ((UPPER(TRIM(tbl_peca.referencia_pesquisa)) LIKE UPPER(TRIM('%$referencia%')) $or_busca_ref $or_busca_referencia )";
	}

	$sql .= " AND tbl_peca.acessorio is $not true";

	if ($login_fabrica == 42) {
		$sql .= " AND tbl_peca.produto_acabado is not true ";
	}

} else if (strlen(trim($descricao)) > 2 || strlen(trim($referencia)) > 2 || strlen(trim($posicao)) > 0 || $tipo == "tudo") {

    if ($tipo == 'descricao') {
        echo "<div class='lp_pesquisando_por'>";
        $texto = ($sistema_lingua == "ES") ? 'Buscando por el nombre de la pieza' : 'Pesquisando por descri&ccedil;&atilde;o da pe&ccedil;a ';
        echo "$texto - $descricao";
        echo "</div>";

    } else if ($tipo == 'referencia') {

        echo "<div class='lp_pesquisando_por'>";
        echo ($sistema_lingua == "ES") ? "Buscando por referencia: " : "Pesquisando por refer&ecirc;ncia da pe&ccedil;a: ";
        echo "$referencia";
        echo "</div>";

    } else if ($tipo == 'posicao') {
        echo "<div class='lp_pesquisando_por'>";
        if ($sistema_lingua == "ES") {
            echo "Buscando por posición: ";
        } else {
            echo "Pesquisando por posi&ccedil;&atilde;o da pe&ccedil;a: ";
        }
        echo "$posicao";
        echo "</div>";

    }

	$sql = "SELECT  DISTINCT z.peca                       ,
					z.referencia       AS peca_referencia ,
					z.descricao        AS peca_descricao  ,
					z.bloqueada_garantia                  ,
					z.type                                ,
					z.posicao                             ,
					z.peca_fora_linha                     ,
					z.de                                  ,
					z.para                                ,
					z.promocao_site                       ,
					z.parametros_adicionais               ,";


	if ($qtde > 0 && strlen($produto) > 0 && !in_array($login_fabrica,array(43,96,140))) {

		$sql .=	"	tbl_lbm.somente_kit                   ,";
	}

	$sql .= "		z.peca_para                           ,
					z.libera_garantia                     , ";

	if ($login_fabrica == 15) {

		$sql .= "   z.serie_inicial                                    ,
					z.serie_final							 		   ,
					z.data_ativa							 		   ,
					tbl_produto.descricao     AS nome_produto          ,
					tbl_produto.serie_inicial AS produto_serie_inicial ,
					tbl_produto.serie_final   AS produto_serie_final   ,";

	}

	$sql .= "       tbl_peca.descricao AS para_descricao
			FROM (
					SELECT  y.peca               ,
							y.referencia         ,
							y.descricao          ,
							y.bloqueada_garantia ,
							y.type               ,
							y.promocao_site      ,
							y.posicao            ,";

	if ($login_fabrica == 15) {

		$sql .= "   y.serie_inicial             ,
		            y.data_ativa             ,
					y.serie_final				,";

	}

	$sql.= "				y.peca_fora_linha    ,
							y.parametros_adicionais               ,
							tbl_depara.de        ,
							tbl_depara.para      ,
							tbl_depara.peca_para,
							y.libera_garantia
					FROM (
							SELECT  x.peca                                      ,
									x.referencia                                ,
									x.descricao                                 ,
									x.bloqueada_garantia                        ,
									x.type                                      ,
									x.posicao                                   ,
									x.promocao_site                             ,
									x.parametros_adicionais               ,";

	if ($login_fabrica == 15) {
		$sql .= "       x.serie_inicial                 ,
				        x.data_ativa                 ,
						x.serie_final					,";
	}

	if($login_fabrica == 134){
		$join_def_constatado = "
		join tbl_peca_defeito_constatado using(peca)
		join tbl_defeito_constatado using(defeito_constatado)";
	}else{
		$join_def_constatado = "";
	}



if($login_fabrica == 134) {
	$tabela_dc = '_constatado';
	$codigo = 'codigo';
	$join_lista_basica = "LEFT JOIN tbl_lista_basica USING (peca)";
}else if ($login_fabrica == 131){
	if (strlen($codProduto) == 0) {
		$sql_cod = "select produto from tbl_produto where referencia = '$produto_referencia' and fabrica_i = $login_fabrica";
		$res = pg_query($con,$sql_cod);
		$codProduto = pg_result($res,0,'produto');
	}
	$codigo = 'codigo_defeito';
	if(!empty($codProduto)){
		$join_lista_basica = " JOIN tbl_lista_basica ON tbl_lista_basica.peca = tbl_peca.peca AND tbl_lista_basica.produto = $codProduto ";
	}
	$join_def =	"JOIN tbl_peca_defeito$tabela_dc ON tbl_peca.peca = tbl_peca_defeito$tabela_dc.peca
					JOIN tbl_defeito$tabela_dc ON tbl_peca_defeito$tabela_dc.defeito$tabela_dc = tbl_defeito$tabela_dc.defeito$tabela_dc
					AND tbl_defeito$tabela_dc.fabrica = $login_fabrica
					AND tbl_defeito$tabela_dc.$codigo IN($codigo_defeitos)";
        $join_def = '';

}else{
	$join_lista_basica = "LEFT JOIN tbl_lista_basica USING (peca)";
	$codigo = 'codigo_defeito';
}






	$sql .= "						tbl_peca_fora_linha.peca AS peca_fora_linha,
									tbl_peca_fora_linha.libera_garantia
							FROM (
									SELECT  tbl_peca.peca				  ,
											tbl_peca.referencia			  ,
											tbl_peca.descricao			  ,
											tbl_peca.bloqueada_garantia	  ,
											tbl_peca.parametros_adicionais,
											tbl_lista_basica.type		  ,
											tbl_lista_basica.posicao      ,
											tbl_lista_basica.serie_inicial,
											tbl_lista_basica.data_ativa,
											tbl_peca.promocao_site		  ,
											tbl_lista_basica.serie_final
									FROM tbl_peca
									$join_lista_basica
									$join_def
									$join_def_constatado
									$join_busca_referencia
									$cond_masterfrio
									LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_lista_basica.produto ";

	if ($login_fabrica == 20 AND $login_pais <> 'BR') {
		$sql .= "JOIN tbl_tabela_item ON tbl_tabela_item.peca = tbl_peca.peca AND tabela = $tabela ";
	}

	/* HD 146619 - ALTERACAO 1 de 2 */
	if ($login_fabrica == 15) {
		$sql .= "LEFT JOIN tbl_depara ON tbl_lista_basica.peca = tbl_depara.peca_para
				 LEFT JOIN tbl_lista_basica AS tbl_lista_basica_de ON tbl_depara.peca_de = tbl_lista_basica_de.peca AND tbl_lista_basica.produto = tbl_lista_basica_de.produto";
	}

	$sql .= " WHERE tbl_peca.fabrica = $login_fabrica";
	if($login_fabrica == 134){
		$sql .= " AND tbl_defeito_constatado.codigo IN($codigo_defeitos) ";
	}
	if (!empty($produto) AND $login_fabrica <> 140) {
		$sql .= " AND tbl_produto.produto = $produto";
	}

	$sql .= ($login_fabrica <> 30 AND $login_fabrica <> 1 ) ? " AND tbl_peca.ativo IS TRUE " : "";
	$sql .= ($login_fabrica == 1 ) ? " AND (tbl_peca.ativo IS TRUE OR  tbl_peca.informacoes = 'INDISPL') " : "";
	$sql .= " $cond_produto $cond_versao
			$where_masterfrio";

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

	if (strlen(trim($referencia)) > 2) {
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
					LEFT JOIN tbl_depara ON tbl_depara.peca_de = y.peca
				) AS z
			LEFT JOIN tbl_peca ON tbl_peca.peca = z.peca_para";

	if ($qtde > 0 && strlen($produto) > 0 && !in_array($login_fabrica,array(43,96,140))) {

		$sql .= " JOIN tbl_lista_basica AS tbl_lbm ON (tbl_lbm.peca = z.peca AND tbl_lbm.produto = $produto)
				  JOIN tbl_produto                 ON (tbl_produto.produto = $produto)
				 ORDER BY z.descricao";

	} elseif ($login_fabrica == 131) {
		$sql .= ' ORDER BY z.descricao ';
	}

    /**
     * - Produto GARANTIA DE PEÇAS
     * para B&D, o SELECT desse produto será ignorado,
     * buscando todas as peças ativas da fábrica
     */
	if ($login_fabrica == 1 && $garantia_peca > 0) {
        $sql = "
            SELECT  tbl_peca.peca            ,
                    tbl_peca.referencia     AS peca_referencia      ,
                    tbl_peca.descricao      AS peca_descricao ,
                    ''                      AS bloqueada_garantia                 ,
                    ''                      AS type                 ,
                    ''                      AS pposicao                 ,
                    ''                      AS peca_fora_linha                 ,
                    ''                      AS de                                    ,
                    ''                      AS para                                 ,
                    ''                      AS promocao_sita                        ,
                    ''                      AS peca_para                        ,
                    ''                      AS libera_garantia                        ,
                    ''                      AS para_descricao
            FROM    tbl_peca
            WHERE   tbl_peca.fabrica = $login_fabrica
            AND     tbl_peca.ativo              IS TRUE
            AND     tbl_peca.produto_acabado    IS NOT TRUE
        ";

        if (strlen(trim($descricao)) > 2) {

            if ($tipo == 'descricao') {
                $sql .= " AND UPPER(TRIM(tbl_peca.descricao)) LIKE UPPER(TRIM('%$descricao%'))";
            } else if ($tipo == 'tudo') {
                $sql .= " AND (UPPER(TRIM(tbl_peca.descricao))  LIKE UPPER(TRIM('%$descricao%')) OR
                            UPPER(TRIM(tbl_peca.referencia)) LIKE UPPER(TRIM('%$descricao%')))";
            }

        }

        if (strlen(trim($referencia)) > 2) {
            $sql .= " AND (UPPER(TRIM(tbl_peca.referencia_pesquisa)) LIKE UPPER(TRIM('%$referencia%')))";
        }
	}
} else {
	$msg_erro = 'Digite toda ou parte de uma informa&ccedil;&atilde;o para pesquisar.';
}

$res = pg_query($con, $sql);

if (@pg_num_rows($res) == 0 and strlen($kit_peca_sim) == 0) {

	if ($login_fabrica == 1) {
		$msg_erro = "Item '".($tipo == 'descricao' ? $descricao : $referencia)."' n&atilde;o existe <br> para o produto $produto_referencia, <br> consulte a vista explodida atualizada <br> e verifique o código correto";
	} else {

		if ($sistema_lingua == 'ES')
			$msg_erro = "Reupesto '".($tipo == 'descricao' ? $descricao : $referencia)."' no encontrado<br>para el producto $produto_referencia";
		else
			$msg_erro = "Pe&ccedil;a '".($tipo == 'descricao' ? $descricao : $referencia)."' n&atilde;o encontrada<br>para o produto $produto_referencia";
	}

} else if (@pg_num_rows ($res) == 0) {

	if (strlen($posicao) > 0) {

		if ($sistema_lingua == 'ES') $msg_erro = "Pieza '$posicao' no encontrada <br>para el producto $produto_referencia";
		else                         $msg_erro = "Posi&ccedil;&atilde;o '$posicao' n&atilde;o encontrada<br>para o produto $produto_referencia";

	} else {

		if ($sistema_lingua == 'ES') $msg_erro = "No consta lista b&aacute;sica para este producto";
		else 						 $msg_erro = "Nenhuma lista b&aacute;sica de pe&ccedil;as encontrada para este produto";

	}

}


if (!empty($msg_erro))
{
	echo "<div class='lp_msg_erro'>$msg_erro</div>";
}
else
{
$contador  = 999;

$num_pecas = pg_num_rows($res);
$gambiara  = 0;

echo "<center><font color='red' size='-1'>Clique na refer&ecirc;ncia da pe&ccedil;a para transferir os dados para o formul&aacute;rio</font></center>";
for ($i = 0; $i < $num_pecas; $i++) {

	$peca_referencia = trim(@pg_fetch_result($res, $i, 'peca_referencia'));

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
	$libera_garantia    = trim(@pg_fetch_result($res, $i, 'libera_garantia'));
	$parametros_adicionais    = trim(pg_fetch_result($res,$i,'parametros_adicionais'));
	$garantia_para = "";

	// HD-6574162
	/*if (pg_fetch_result($res, $i, 'de') != "" && $para != "") {
		$sql_garantia_para = "	SELECT bloqueada_garantia AS bloqueada_garantia_para 
								FROM tbl_peca 
								WHERE peca = $peca_para 
								AND fabrica = $login_fabrica";
		$res_garantia_para = pg_query($con, $sql_garantia_para);
		$garantia_para = pg_fetch_result($res_garantia_para, 0, 'bloqueada_garantia_para');
	}*/

	if($login_fabrica == 35){
		$po_peca = trim(pg_fetch_result($res, $i, 'promocao_site'));
	}
	//HD 189523 - MLG - Latinatec filtra a Lista B&aacute;sica usando o 2Âº caractere do nÂº de sÃ©rie para controlar a vers&atilde;o

	if ($login_fabrica == 15 and $serie and $produto) {

        $l_serie_inicial = trim(pg_fetch_result($res, $i, 'serie_inicial'));
        $l_serie_final   = trim(pg_fetch_result($res, $i, 'serie_final'));
        $p_serie_inicial = trim(pg_fetch_result($res, $i, 'produto_serie_inicial'));
        $p_serie_final   = trim(pg_fetch_result($res, $i, 'produto_serie_final'));
        $l_data_ativa    = trim(pg_fetch_result($res, $i, 'data_ativa'));

		if (strlen($serie) < 20) {

		    if (!valida_serie_latinatec($serie,$p_serie_inicial,$p_serie_final,$l_serie_inicial,$l_serie_final)) {

				if ($gambiara == 0) {//HD 270590
					echo "Se n&atilde;o retornar nenhuma pe&ccedil;a, provavelmente o número de série esteja errado ou a pe&ccedil;a n&atilde;o pertence a este modelo de produto !";
					$gambiara = 1;
				}

				continue;
			}

		} else {

			$ns_referecia_produto  	= substr($serie, 0, 6);
			$ns_tipo_produto  		= substr($serie, 6, 1);
			$ns_ano_fabricacao 		= "20".substr($serie, 7, 2);
			$ns_mes_fabricacao 		= $mesNSLatina[substr($serie, 9, 1)];
			$ns_dia_fabricacao 		= substr($serie, 10, 2);
			$ns_data_fabricacao 	= trim("{$ns_ano_fabricacao}-{$ns_mes_fabricacao}-{$ns_dia_fabricacao}");
			$ns_quantidade      	= intval(substr($serie, 12, 8));

			if (!checaNSLatina()) {
				continue;
			}
		}
	}

	if($login_fabrica == 30 ){
		if(!empty($parametros_adicionais)){
			$parametros_adicionais = json_decode($parametros_adicionais);
			foreach ($parametros_adicionais AS $key => $value){
				$$key = $value;
			}

			if(empty($uso_interno)){
				$uso_interno = "t";
			}
		}else{
			$uso_interno = 't';
		}
	}else{
		$uso_interno = 't';
	}

	#hd 1855061 ====================================================================

	#07250.001 -> 077.1.600
	#07250.002 -> 077.1.601
	#07250.003 -> 077.1.602
	#077.1.350
	#077.1.351
	#077.1.352

	$de_para_manual = false;

	if ($login_fabrica == 50 && in_array($produto, array(34333, 212885, 212884, 34914, 214793)) && in_array($peca, array(1466423, 864884, 854989, 1501803, 1539430, 1539441, 1547942, 1547941, 1547940))) {
		$sql_os_serie = "SELECT data_fabricacao
						 FROM tbl_os
						 JOIN tbl_numero_serie ON tbl_numero_serie.produto = tbl_os.produto AND tbl_numero_serie.serie = tbl_os.serie AND tbl_numero_serie.fabrica = $login_fabrica
						 WHERE tbl_os.os = $os
						 AND tbl_os.fabrica = $login_fabrica";
		$res_os_serie = pg_query($con, $sql_os_serie);

		if (pg_num_rows($res_os_serie) > 0) {
			$serie_data_fabricacao = pg_fetch_result($res_os_serie, 0, "data_fabricacao");

			if (strtotime($serie_data_fabricacao) < strtotime('2014-03-01')) {
				if (in_array($peca, array(1501803, 1539430, 1539441))) {
					continue;
				} else if (in_array($peca, array(1466423, 864884, 854989))) {
					$de_para_manual = true;

					#1547940 | 077.1.600
					#1547941 | 077.1.601
					#1547942 | 077.1.602

					#1466423 | 07250.003
  					#864884 | 07250.002
  					#854989 | 07250.001

					switch ($peca) {
						case 1466423:
							$de_para_manual_peca = 1547942;
							break;

						case 864884:
							$de_para_manual_peca = 1547941;
							break;

						case 854989:
							$de_para_manual_peca = 1547940;
							break;
					}
				}
			} else {
				if (in_array($peca, array(1466423, 864884, 854989, 1547942, 1547941, 1547940))) {
					continue;
				}
			}
		}
	}

	#hd 1855061 ====================================================================

	$sql_idioma = "SELECT descricao AS peca_descricao FROM tbl_peca_idioma WHERE peca = $peca AND upper(idioma) = '$sistema_lingua'";
	$res_idioma = @pg_query($con,$sql_idioma);

	if (@pg_num_rows($res_idioma) > 0) {
		$peca_descricao    = pg_fetch_result($res_idioma,0,peca_descricao);
		$peca_descricao_js = strtr($peca_descricao, array('"'=>'&rdquo;', "'"=>'&rsquo;'));  //07/05/2010 MLG - HD 235753
	}

	$resT = @pg_query($con,"SELECT tabela FROM tbl_tabela WHERE fabrica = $login_fabrica AND tbl_tabela.ativa IS TRUE");

	if (strlen($login_posto) > 0){
		if ($login_fabrica == 74) {
			$resT = pg_query($con,"SELECT tabela FROM tbl_posto_linha JOIN tbl_linha USING(linha) WHERE fabrica=$login_fabrica AND posto=$login_posto");
		}

		if ($login_fabrica == 6) {

			$resT = pg_query($con,"SELECT tabela FROM tbl_posto_linha JOIN tbl_linha using(linha) WHERE posto = $login_posto AND fabrica = $login_fabrica limit 1");

			if (pg_num_rows($resT) <> 1) {
				$resT = pg_query($con,"SELECT tabela FROM tbl_tabela WHERE fabrica = $login_fabrica AND tbl_tabela.ativa IS TRUE");
			}
		}

		if ($login_fabrica == 72 or $login_fabrica > 84) {

			$resT = pg_query($con,"SELECT tabela FROM tbl_posto_linha JOIN tbl_linha using(linha) WHERE posto = $login_posto AND fabrica = $login_fabrica limit 1");

			if (pg_num_rows($resT) <> 1) {
				$resT = pg_query($con,"SELECT tabela FROM tbl_tabela WHERE fabrica = $login_fabrica AND tbl_tabela.ativa IS TRUE");
			}

		}

		if ($login_fabrica == 40) {

			$resT = pg_query($con,"SELECT tabela_posto as tabela FROM tbl_posto_linha JOIN tbl_linha using(linha) WHERE posto = $login_posto AND fabrica = $login_fabrica limit 1");

		}
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
						$preco = number_format ((pg_fetch_result($resT,0,0)*(1+($ipi_peca/100))),2,",",".");
					} else {
						$preco = number_format (pg_fetch_result($resT,0,0),2,",",".");
					}

				}

			} else {

				$preco = "";

			}

		} else {

			$preco = "";

		}

	} else {

		if (@pg_num_rows($resT) >= 1) {

			if ($login_fabrica != 101) {//HD 677442
				$tabela = pg_fetch_result($resT, 0, 0);
			}

			if (strlen($para) > 0) {

				if (in_array($login_fabrica, $vet_ipi)) {//HD 677442 - Valor com IPI
					$sqlT = "SELECT (preco*(1 + (tbl_peca.ipi / 100))) as preco FROM tbl_tabela_item JOIN tbl_peca USING(peca) WHERE tabela = $tabela AND peca = $peca_para";
				} else {
					$sqlT = "SELECT preco FROM tbl_tabela_item WHERE tabela = $tabela AND peca = $peca_para";
				}

			} else {

				if (in_array($login_fabrica, $vet_ipi)) {//HD 677442 - Valor com IPI
					$sqlT = "SELECT (preco*(1 + (tbl_peca.ipi / 100))) as preco FROM tbl_tabela_item JOIN tbl_peca USING(peca) WHERE tabela = $tabela AND peca = $peca";
				} else {
					$sqlT = "SELECT preco FROM tbl_tabela_item WHERE tabela = $tabela AND peca = $peca";
				}

			}

			$resT = pg_query($con,$sqlT);

			if ($login_fabrica == 94) {
				$preco = (pg_num_rows($resT) == 1) ? number_format(pg_fetch_result($resT,0,0),3,",",".") : '';
			} else {
				$preco = (pg_num_rows($resT) == 1) ? number_format(pg_fetch_result($resT,0,0),2,",",".") : '';
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
				if ($login_fabrica == 14 or $login_fabrica == 24 or $login_fabrica == 66 or $login_fabrica == 74) {
					echo "<th nowrap>Posi&ccedil;&atilde;o</th>";
				}
				if ($login_fabrica == 3) {
					echo "<th nowrap>Codigo Linha</th>";
				}
				echo "<th nowrap>Pe&ccedil;a Refer&ecirc;ncia</th>";
				echo "<th nowrap>Pe&ccedil;a Descri&ccedil;&atilde;o</th>";
				if ($login_fabrica == 1) {
					echo "<th nowrap>Tipo</th>";
				}
				if(!in_array($login_fabrica,array(127))){
					echo "<th nowrap colspan='2'>Outras Informa&ccedil;&otilde;es</th>";
				}
			echo "</tr>";
		echo "</thead>";
		echo "<tbody>";
		flush();
	}

	$contador++;
	$cor = (strlen($peca_fora_linha) > 0) ? '#FFEEEE' : '#ffffff';
	$id_click = "id='onclick_$i'";
	$texto_mudou = "";
	// HD-6574162
	/*if (($bloqueada_garantia == 't' || $garantia_para == 't') && !in_array($login_fabrica, [3,72])) {
		$cor = '#FFF6C1';
		$id_click = "";
		$texto_mudou = "<br /> <p style= 'color:#ff0000'>Peça bloqueada para garantia</p>";
	} */

    if($uso_interno == 't'){
        echo "<tr bgcolor='$cor'>\n";

        if ($login_fabrica == 30) {
            echo "<td>$referencia_antiga</td>";
        }

        if ($login_fabrica == 14 or $login_fabrica == 24 or $login_fabrica == 66 or $login_fabrica == 74) {
            echo "<td nowrap>$posicao</td>";
        }

        if ($login_fabrica == 3) {

            $sql  = "SELECT tbl_linha.codigo_linha FROM tbl_linha WHERE linha = (SELECT tbl_produto.linha FROM tbl_produto JOIN tbl_lista_basica ON tbl_produto.produto = tbl_lista_basica.produto WHERE tbl_lista_basica.peca = $peca LIMIT 1)";
            $resX = pg_query($con,$sql);
            $codigo_linha = @pg_fetch_result($resX, 0, 0);

            if (strlen ($codigo_linha) == 0) $codigo_linha = "&nbsp;";

            echo "<td nowrap>$codigo_linha</td>";

        }

       	echo "<td nowrap $id_click align='center'><u style='color: blue;!important'>$peca_referencia</u></td>";	



        echo "<td nowrap>";

        if (strlen($peca_fora_linha) > 0 OR strlen($para) > 0 || $de_para_manual == true) {

            if ($login_fabrica == 3 && $libera_garantia == 't') {

                if (strlen($kit_peca) > 0) {
                    echo '<a href="javascript: ';

                    echo "window.opener.$('#$kit_peca').html('');\">";
                }

                echo "$peca_descricao";

                if (strlen($kit_peca) > 0) {
                    echo "</a>$texto_mudou";
                }
            } else {
                echo $peca_descricao.$texto_mudou;
            }

            if (in_array($login_fabrica,array(30,85)) and !empty($login_posto)) {//HD102404 waldir

                $sql_tabela  = "SELECT tabela
                                FROM tbl_posto_linha
                                JOIN tbl_linha ON tbl_linha.linha = tbl_posto_linha.linha AND tbl_linha.fabrica = $login_fabrica
                                WHERE tbl_posto_linha.posto = $login_posto LIMIT 1;";

                $res_tabela  = @pg_query($con, $sql_tabela);
                $tabela      = trim(@pg_fetch_result($res_tabela, 0, 'tabela'));

				if(!empty($peca_para)) {
					$sql_preco = "SELECT preco FROM tbl_tabela_item WHERE peca = $peca_para AND tabela = $tabela";
					$res_preco = @pg_query($con, $sql_preco);
					$preco     = trim(@pg_fetch_result($res_preco, 0, 'preco'));
				}
            }

        } else {
            //HD92435 - paulo
            if (in_array($login_fabrica,array(30,85)) and !empty($login_posto) and !empty($peca)) {//HD102404 waldir

                $sql_tabela  = "SELECT tabela
                                FROM tbl_posto_linha
                                JOIN tbl_linha ON tbl_linha.linha = tbl_posto_linha.linha AND tbl_linha.fabrica = $login_fabrica
                                WHERE tbl_posto_linha.posto = $login_posto LIMIT 1;";

                $res_tabela = @pg_query($con, $sql_tabela);
                $tabela     = trim(@pg_fetch_result($res_tabela, 0, 'tabela'));

                $sql_preco = "SELECT preco FROM tbl_tabela_item WHERE peca = $peca AND tabela = $tabela";
                $res_preco = @pg_query($con, $sql_preco);
                $preco     = trim(@pg_fetch_result($res_preco, 0, 'preco'));
            }

            //somente para pedido de pecas faturadas
            if ($login_fabrica == 30 && strlen($preco) == 0 && strlen($os) == 0) {
                echo "<a href=\"javascript: alert('Pe&ccedil;a Bloqueada \\n \\r Em caso de dÃºvidas referente a cÃ³digo de pe&ccedil;as que o Sistema n&atilde;o est&aacute; aceitando, favor entrar em contato com a F&aacute;brica atravÃ©s do Fone: (85) 3299-8992 ou por e-mail: pedidos.at@esmaltec.com.br.')\" " . "style='color:red'>$peca_descricao</a>$texto_mudou";
            } else {
                if ($login_fabrica == 19 AND strlen($linha) > 0) {//HD 100696

                    $caminho            = $caminho."/".$login_fabrica;
                    $diretorio_verifica = $caminho."/pequena/";
                    $peca_reposicao     = $_GET['peca_reposicao'];

                    if ($peca_reposicao == 't') {

                        echo "<a href='produto_pesquisa_lista_nv.php?peca=$peca&tipo=referencia' " . "target='_blank' " . "style='color:red'>" . $peca_descricao . "</a>$texto_mudou";

                    } else {

			            $xpecas = $tDocs->getDocumentsByRef($peca, "peca");
			            if (!empty($xpecas->attachListInfo)) {

							$a = 1;
							foreach ($xpecas->attachListInfo as $kFoto => $vFoto) {
							    $fotoPeca = $vFoto["link"];
							    if ($a == 1){break;}
							}
							echo "<a href='$fotoPeca' target='_blank' " . "style='color:red'>" .	$peca_descricao . "</a>$texto_mudou";
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
	                                            echo "<a href='$caminho/pequena/$img[0].pdf' target='_blank' " . "style='color:red'>" .	$peca_descricao . "</a>$texto_mudou";
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
		                                                echo "<a href='$caminho/pequena/$img[0].pdf' target='_blank' " . "style='color:red'>" . $peca_descricao . "</a>$texto_mudou";
		                                            }

		                                        }

		                                    }

		                                }
	                            }

	                        } else {
	                            echo "<span style='color:red'>$peca_descricao</a>$texto_mudou";
	                        }
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
                                    $preco = str_replace(',','.',$preco);
                                    //$preco = $preco - ($preco * 0.45);
                                    $preco = number_format($preco,2,'.',',');
                                    $preco = str_replace('.',',',$preco);
                                }

                            }

                        }

                    }

                    if ($somente_kit == 't') {//HD 335675
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

                    if ($somente_kit == 't') {//HD 335675
                        echo '</span>';
                    } else {
                        echo "</a>$texto_mudou";
                    }
                }
            }

        }
        echo "</td>\n";

        if ($login_fabrica == 1) {
            echo "<td nowrap>$type</td>";
        }

        if (in_array($login_fabrica, array(1, 3, 4, 5, 11, 30, 35, 45, 50, 51, 74, 85, 91))) {

            echo "<td nowrap>";

            if (strlen($peca_fora_linha) > 0) {

                echo "<span>";

                if ($login_fabrica == 1) {
                    echo "Ã‰ obsoleta,n&atilde;o Ã© mais fornecida";
                } else {

                    if ($login_fabrica == 3 AND $libera_garantia == 't') {
                        echo "DisponÃ­vel somente para garantia.<br /> Caso necess&aacute;rio, favor contatar a Assist&ecirc;ncia TÃ©cnica BritÃ¢nia";
                    } else {
                        echo "Fora de linha";
                    }

                }

                echo "</span>";

            } else {

                /* HD  152192 a Esmaltec n&atilde;o faz pedido pela OS, somente informa troca com pe&ccedil;a do posto
                e geralmente o posto comprou com o codigo da pe&ccedil;a antiga, por isto n&atilde;o vai passar no de-para */

                if (strlen($para) > 0 || $de_para_manual == true) {

                    if ($login_fabrica != 30) {

                    	if ($de_para_manual == true) {
							$sql_de_para_manual = "SELECT referencia, descricao FROM tbl_peca WHERE fabrica = $login_fabrica AND peca = $de_para_manual_peca";
							$res_de_para_manual = pg_query($con, $sql_de_para_manual);

							$para_descricao = pg_fetch_result($res_de_para_manual, 0, "descricao");
							$para           = pg_fetch_result($res_de_para_manual, 0, "referencia");
						}

                        if (strlen($peca_descricao) > 0) {#HD 228968

                            $sql_idioma = "SELECT tbl_peca_idioma.descricao AS peca_descricao FROM tbl_peca_idioma JOIN tbl_peca USING(peca) WHERE tbl_peca.descricao = '$peca_descricao' AND upper(idioma) = '$sistema_lingua'";

                            $res_idioma = @pg_query($con, $sql_idioma);

                            if (@pg_num_rows($res_idioma) > 0) {
                                $peca_descricao  = preg_replace('/([\"|\'])/','\\$1',(pg_fetch_result($res_idioma, 0, 'peca_descricao')));
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
                        echo "\"style='color:red'>$para</a>";

                    }

                } else {
                    echo "&nbsp;";
                }

                if ($somente_kit == 't') {//HD 335675

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
					$kit_produto = 'f';
                    for ($yy = 0; $yy < $tot_kit; $yy++) {
						$kit_produto = 't';
                        $kit_peca_kit = @pg_result($res_kit, $yy, 'kit_peca');
                        $ref_kit      = @pg_result($res_kit, $yy, 'referencia');
                        $des_kit      = @pg_result($res_kit, $yy, 'descricao');

                        echo "<a href=\"javascript: ";
                        echo "kitPeca('$kit_peca_kit','$kit_peca'); \">$des_kit</a>";

                    }

                }

            }
            if($login_fabrica == 30){
            	if(!empty($para)){
	    	        echo "<span>A Peça mudou para</span>";
	                echo " <a href=\"javascript: ";

	                if ($login_fabrica == 30) {
	                    echo " if (qtde) {qtde.value='1';} ";
	                }

	                echo "\"style='color:red'>$para</a>";                
	            }
            }
            echo "</td>\n";

            if($login_fabrica != 127){
            echo "<td nowrap align='right'>";

            $diretorio_verifica = $caminho."/pequena/";
 			$xpecas = $tDocs->getDocumentsByRef($peca, "peca");
            if (!empty($xpecas->attachListInfo)) {

				$a = 1;
				foreach ($xpecas->attachListInfo as $kFoto => $vFoto) {
				    $fotoPeca = $vFoto["link"];
				    if ($a == 1){break;}
				}
                echo "<a href=\"javascript:mostraPeca('$fotoPeca', '$peca')\">";
                echo "<img src='$fotoPeca' height='50' border='0'>";
                echo "</a>";
            } else {
	            if (is_dir($diretorio_verifica)) {
	                if ($dh = opendir($caminho."/pequena/")) {

	                    $contador = 0;

	                    while (false !== ($filename = readdir($dh))) {

	                        if($contador == 1) break;

	                        if (strpos($filename, $peca) !== false) {

	                            $contador++;
	                            $po = strlen($peca);

	                            if (substr($filename, 0, $po) == $peca) {
	                                echo "<a href=\"javascript:mostraPeca('$filename', '$peca')\">";
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
	                                    echo "<a href=\"javascript:mostraPeca('$filename', '$peca')\">";
	                                    echo "<img src='$caminho/pequena/$filename' border='0'>";
	                                    echo "</a>";
	                                }

	                            }

	                        }

	                    }

	                }

	            }
            }
            echo "</td>\n";
            }
            if ($login_fabrica == 3 AND $peca == '526199') {
                echo "</tr>";
                echo "<tr>";
                echo "<td colspan='4'align='center'><img src='imagens_pecas/526199.gif' class='Div'>";
                echo "</td>\n";
            }

        }else{        	
        	echo "<td></td>";
        }


        $OCreferencia_antiga = $referencia_antiga;
        $OCposicao = $posicao;
        $OCcodigo_linha = $codigo_linha;
        $OCpeca_referencia = $peca_referencia;
        $OCpeca_descricao = $peca_descricao;
        $OCpreco = $preco;
        $OCpeca = $peca;
        $OCtype = $type;

        if ($login_fabrica == 72) {
			echo (!$pecaBloqueadaGarantia) ? "<td><center><input data-referencia-antiga='$OCreferencia_antiga' data-posicao='$OCposicao' data-codigo-linha='$OCcodigo_linha' data-referencia='$OCpeca_referencia' data-descricao='$OCpeca_descricao' data-preco='$OCpreco' data-peca='$OCpeca' data-type='$OCtype' data-input-posicao='$input_posicao' data-posicao='$posicao' type='checkbox' class='pecas_multiplas' /></center></td>" : "<td></td>";
		}

        $sqlX =	"SELECT   DISTINCT referencia, to_char(tbl_peca.previsao_entrega,'DD/MM/YYYY') AS previsao_entrega
                    FROM  tbl_peca
                    WHERE referencia_pesquisa = UPPER('$peca_referencia')
                    AND   fabrica = $login_fabrica
                    AND   previsao_entrega NOTNULL;";

        $resX = pg_query($con, $sqlX);

        if(pg_num_rows($resX) > 0){
            echo "</tr>\n";
            echo "<tr>\n";

            $peca_previsao      = pg_fetch_result($resX,0,0);
            $previsao_entrega   = pg_fetch_result($resX,0,1);
            $data_atual         = date("Ymd");
            $x_previsao_entrega = substr($previsao_entrega,6,4) . substr($previsao_entrega,3,2) . substr($previsao_entrega,0,2);

            echo "<td colspan='2'>";

            if ($data_atual < $x_previsao_entrega) {
                if ($sistema_lingua == 'ES')
                    echo "Este repuesto estar&aacute; disponible en: $previsao_entrega";
                else
                    echo "Esta pe&ccedil;a estar&aacute; disponÃ­vel em $previsao_entrega";
                echo "<br />";
                if ($sistema_lingua == 'ES')
                    echo "Para repuestos con plazo de entrega superior a 25 dÃ­as, el fabricante tomar&aacute; las medidas necesarias para atender al consumidor";
                else
                    echo "Para as pe&ccedil;as com prazo de fornecimento superior a 25 dias, a f&aacute;brica tomar&aacute; as medidas necess&aacute;rias para atendimento do consumidor";
            }

            echo "</td>\n";

        }
        echo "</tr>\n";
        if ($exibe_mensagem == 't' AND $bloqueada_garantia == 't' and $login_fabrica == 3) {
            echo "<tr>\n";
            echo "<td colspan='4'>\n";
            echo "A pe&ccedil;a $referencia necessita de autoriza&ccedil;&atilde;o da BritÃ¢nia para atendimento em garantia.";
            echo "</td>\n";
            echo "</tr>\n";
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

        if ($de_para_manual == true || strlen($para_descricao) > 0) {
        	$OCpeca_referencia = $para;
       		$OCpeca_descricao = $para_descricao;
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
                if ($somente_kit == 't' and $kit_produto == 't') {
                    $OCpeca_referencia = $ref_kit;
                    $OCpeca_descricao = '';
                    $OCpreco = '';
                }
            }
        }


        $conteudo_onclick = "'$OCreferencia_antiga','$OCposicao','$OCcodigo_linha','$OCpeca_referencia','$OCpeca_descricao','$OCpreco','$OCpeca','$OCtype','$input_posicao','$posicao',''";

        if($login_fabrica == 35){
            $funcao_po_peca = ($po_peca == "t") ? "window.parent.verificaPOPeca('$po_peca', $input_posicao);" : "";
        }

        echo "<script> $('#onclick_$i').click(function(){
            window.parent.retorna_lista_peca($conteudo_onclick);
            window.parent.$('input[name=kit_kit_peca_".$input_posicao."]').val('');
            $funcao_po_peca
            window.parent.Shadowbox.close();
        }); </script>";


    }
}
echo "</tbody></table>\n";
}
function checaNSLatina() {
	global $peca_referencia, $ns_quantidade, $l_serie_inicial, $l_serie_final, $l_data_ativa, $ns_data_fabricacao;

	if (is_between($ns_quantidade, $l_serie_inicial, $l_serie_final) OR (empty($l_serie_inicial) && empty($l_serie_final)) ) {
		return true;
	} else {
		return false;
	}
}
?>
<?php
if (in_array($login_fabrica, [72])) {
?>
<br /><br />
<div style="
	position:fixed;
	top: 90%;
	text-align: center;
	width: 100%;
">
	<button style="cursor: pointer;" id="lancar_pecas_multiplas">
		Lançar peças selecionadas
	</button>
</div>
<?php
}
?>
<script>
	<?php
	if (in_array($login_fabrica, [72])) {
	?>
		var linhai = <?= (empty($input_posicao)) ? 0 : $input_posicao ?>;

		$("#lancar_pecas_multiplas").click(function(){

			$(".pecas_multiplas:checked").each(function(){

				let referenciaAntiga = $(this).attr("data-referencia-antiga");
				let dataPosicao      = $(this).attr("data-posicao");
				let codigoLinha      = $(this).attr("data-codigo-linha");
				let referencia 		 = $(this).attr("data-referencia");
				let descricao  		 = $(this).attr("data-descricao");
				let peca             = $(this).attr("data-peca");
				let preco            = $(this).attr("data-preco");
				let type             = $(this).attr("data-type");
				let inputPosicao     = $(this).attr("data-input-posicao");

				linhai += 1;

				window.parent.retorna_lista_peca(referenciaAntiga,dataPosicao,codigoLinha,referencia,descricao,preco,peca,type,linhai,dataPosicao,'');

			});

			window.parent.Shadowbox.close();

		});
	<?php
	}
	?>
</script>
</body>
</html>
