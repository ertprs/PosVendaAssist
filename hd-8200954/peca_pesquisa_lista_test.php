<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_usuario.php';

/*HD 16027 Produto acabado, existia algumas selects sem a validação*/

//hd 202154 makita vai lancar pedido de produto acabado para postos tipo PATAM 236
$sql = "SELECT tipo_posto from tbl_posto_fabrica where fabrica = $login_fabrica and posto = $login_posto";
$res = pg_query($con,$sql);
if (pg_num_rows($res)>0) {
	$tipo_posto = pg_fetch_result($res,0,0);
}

#include 'cabecalho_pop_pecas.php';
header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");

$caminho = "imagens_pecas";
if ($login_fabrica <> 10 and $login_fabrica <> 6 and $login_fabrica <> 19) {
	$caminho = $caminho."/".$login_fabrica;
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

	$res = pg_query($con,$sql);
	$resultado = "";

	if (pg_num_rows($res) > 0) {

		$resultado = "<table borde=1>";
		$resultado .="<tr><td colspan='100%'><input type='hidden' name='kit_$kit_peca' value='$kit_peca_id'></td></tr>";

		for ($i = 0; $i < pg_num_rows($res); $i++) {

			$peca     = pg_fetch_result($res,$i,'peca');
			$qtde_kit = pg_fetch_result($res,$i,'qtde');

			$resultado .=   "<tr style='font-size: 11px'>".
							"<td>".
							"<input type='checkbox' name='kit_peca_$peca' value='$peca' CHECKED> ".
						    "<input type='text' name='kit_peca_qtde_$peca' id='kit_peca_qtde_$peca' size='5' value='$qtde_kit' onkeyup=\"re = /\D/g; this.value = this.value.replace(re, '');\"> x ".
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

	$arquivo = $_GET['arquivo'];?>

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
	</table><?php

	exit;

}

$exibe_mensagem = 't';

if (strpos($_GET['exibe'],'pedido') !== false) $exibe_mensagem = 'f';

# se for compra faturada, não precisa de validar.
$faturado = $_GET['faturado'];
# verifica se posto pode ver pecas de itens de aparencia
$sql = "SELECT   tbl_posto_fabrica.item_aparencia,
	         tbl_posto_fabrica.tabela
	FROM     tbl_posto
	JOIN     tbl_posto_fabrica USING(posto)
	WHERE    tbl_posto.posto           = $login_posto
	AND      tbl_posto_fabrica.fabrica = $login_fabrica";
$res = pg_query($con,$sql);

if (pg_num_rows ($res) > 0) {
	if($faturado == 'sim'){
		$item_aparencia = 't';
	}else{
		$item_aparencia = pg_fetch_result($res,0,item_aparencia);
	}
	$tabela         = pg_fetch_result($res,0,tabela);
	if($login_fabrica == 2) $tabela = 236; # HD112438
}

/*Modificado por Fernando
Pedido de Leandro da Tectoy por E-mail. Modificação foi feita para que os postos
que não podem fazer pedido em garantia (OS) de peças, cadastradas como item aparencia, possa
fazer pedido faturado através da tela "pedido_cadastro.php".
*/
##### INICIO ######
if($login_fabrica == 6){
	$faz_pedido = $_GET['exibe'];

	if(preg_match("pedido_cadastro.php", $faz_pedido)){
		$item_aparencia = 't';
	}

	#Fabio - HD 3921 - Para PA fazer pedido
	if(preg_match("tabela_precos_tectoy.php", $faz_pedido)){
		$item_aparencia = 't';
	}
}
##### FIM ######
?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<? if ($sistema_lingua=='ES') { ?>
	<title> Busca repuesto por la lista básica ... </title>
<? } else { ?>
	<title> Pesquisa Peças pela Lista Básica ... </title>
<? } ?>
<meta name="Author" content="">
<meta name="Keywords" content="">
<meta name="Description" content="">
<meta http-equiv=pragma content=no-cache>
<link href="css/posicionamento.css" rel="stylesheet" type="text/css" />
<?php include "javascript_calendario_new.php"; ?>
</head>

<style>
	.Div{
		BORDER-RIGHT:     #6699CC 1px solid;
		BORDER-TOP:       #6699CC 1px solid;
		BORDER-LEFT:      #6699CC 1px solid;
		BORDER-BOTTOM:    #6699CC 1px solid;
		FONT:             10pt Arial ;
		COLOR:            #000;
		BACKGROUND-COLOR: #FfFfFF;
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

function mostraPeca(arquivo) {
	var el = document.getElementById('div_peca');
	el.style.display = (el.style.display=="") ? "none" : "";
	imprimePeca(arquivo);
}

var http3 = new Array();
function imprimePeca(arquivo){
	var curDateTime = new Date();
	http3[curDateTime] = createRequestObject();

	url = "peca_pesquisa_lista_new.php?ajax=true&arquivo="+ arquivo;
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
<? $onBlur = "onblur=\"setTimeout('window.close()',10000);\""; ?>
<body leftmargin="0" <?// if($login_fabrica<>11) echo  $onBlur; ?>>
<br>
<img src="imagens/pesquisa_pecas<? if($sistema_lingua == "ES") echo "_es"; ?>.gif"><?php

$tipo = trim (strtolower ($_GET['tipo']));

$produto = $_GET['produto'];

if ($login_fabrica == 6) {
	$os= $_GET['os'];
	if(strlen($os)>0){
		$sql   = "SELECT serie from tbl_os where os = $os and fabrica = $login_fabrica";
		$res   = @pg_query($con,$sql);
		$serie = @pg_fetch_result($res,0,serie);
	}
}

if ($login_fabrica == 15 ) {

	$os    = $_GET['os'];
	$serie = trim($_GET['serie']);

	$sql   = "SELECT numero_serie_obrigatorio from tbl_os JOIN tbl_produto USING(produto) where os = $os and fabrica = $login_fabrica";
	$res   = @pg_query($con,$sql);
	$numero_serie_obrigatorio = @pg_fetch_result($res,0,numero_serie_obrigatorio);

	if(strlen($serie) == 0 AND $numero_serie_obrigatorio == 't') {
		echo "<div style='font-size: 35px; color: #FF0000;'>Por favor, preencher primeiro o número de série do produto.</div>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',10000);";
		echo "</script>";
		exit;
	}

	if(strlen($serie) > 0 AND $numero_serie_obrigatorio == 't') {
		$aux_serie = substr($serie,1,1);
	}

	// 14/01/2010 MLG - HD 189523
	if (!function_exists("is_between")) {
		function is_between($valor,$min,$max) {   // BEGIN function is_between
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

if (strlen ($produto) > 0) {

	$produto_referencia = trim($_GET['produto']);
	$produto_referencia = str_replace(".","",$produto_referencia);
	$produto_referencia = str_replace(",","",$produto_referencia);
	$produto_referencia = str_replace("-","",$produto_referencia);
	$produto_referencia = str_replace("/","",$produto_referencia);
	$produto_referencia = str_replace(" ","",$produto_referencia);

	$voltagem = trim(strtoupper($_GET["voltagem"]));

	$sql = "SELECT tbl_produto.produto, tbl_produto.descricao,lista_troca
			FROM   tbl_produto
			JOIN   tbl_linha USING (linha)
			WHERE  UPPER(tbl_produto.referencia_pesquisa) = UPPER('$produto_referencia') ";

	if (strlen($voltagem) > 0 AND $login_fabrica == "1" ) $sql .= " AND UPPER(tbl_produto.voltagem) = UPPER('$voltagem') ";

	$sql .= "AND tbl_linha.fabrica = $login_fabrica ";

	if ($login_fabrica <> 3)	$sql .= " AND tbl_produto.ativo IS TRUE ";

	$res = pg_query($con,$sql);

	if (pg_num_rows($res) > 0) {

		$produto_descricao = pg_fetch_result($res, 0, 'descricao');
		$produto           = pg_fetch_result($res, 0, 'produto');
		$lista_troca       = pg_fetch_result($res, 0, 'lista_troca');
		$join_produto      = " JOIN tbl_lista_basica USING (peca) JOIN tbl_produto USING (produto)";
		$condicao_produto  = " AND tbl_produto.produto = $produto ";

	} else {

		$produto = '';

	}

}

$cond_produto =" 1 = 1 ";

if ($login_fabrica <> 3) $cond_produto = " tbl_produto.ativo IS TRUE " ;
	echo "<div id='div_peca' style='display:none; Position:absolute; border: 1px solid #949494;background-color: #b8b7af;width:410px; heigth:400px'>";
	echo "</div>";

if ($tipo == "tudo") {

	$descricao = trim(strtoupper($_GET["descricao"]));

	if ($sistema_lnigua == 'ES') echo "<h4>Buscando toda la lista básica de la herramienta: <br><i>$produto_referencia - $produto_descricao</i></h4>";
	else echo "<h4>Pesquisando toda a lista básica do produto: <br><i>$produto_referencia - $produto_descricao</i></h4>";
	echo "<br><br>";

	$res = pg_query($con,"SELECT COUNT(*) FROM tbl_lista_basica WHERE tbl_lista_basica.fabrica = $login_fabrica");
	$qtde = pg_fetch_result($res,0,0);

	if ($qtde > 0 AND strlen($produto) > 0 AND $login_fabrica <> 43) {
		$sql =	"SELECT DISTINCT z.peca                                ,
						z.referencia       AS peca_referencia ,
						z.descricao        AS peca_descricao  ,
						z.bloqueada_garantia                  ,
						z.type                                ,
						z.posicao                             ,
						z.peca_fora_linha                     ,
						z.de                                  ,
						z.para                                ,
						z.peca_para                           ,";
		if ($login_fabrica == 15) $sql.="        z.serie_inicial          ,
						z.serie_final									  ,
				        tbl_produto.descricao AS nome_produto			  ,
				        tbl_produto.serie_inicial AS produto_serie_inicial,
				        tbl_produto.serie_final AS produto_serie_final    ,
";
		$sql.= "					tbl_peca.descricao AS para_descricao
				FROM (
						SELECT  y.peca               ,
								y.referencia         ,
								y.descricao          ,
								y.bloqueada_garantia ,
								y.type               ,
								y.posicao            ,";
		if ($login_fabrica == 15) $sql.="        y.serie_inicial                 ,
				        y.serie_final									,";
		$sql.= "				y.peca_fora_linha    ,
								tbl_depara.de        ,
								tbl_depara.para      ,
								tbl_depara.peca_para
						FROM (
								SELECT  x.peca                                      ,
										x.referencia                                ,
										x.descricao                                 ,
										x.bloqueada_garantia                        ,
										x.type                                      ,
										x.posicao                                   ,
";
		if ($login_fabrica == 15) $sql.="        x.serie_inicial                 ,
				       					x.serie_final					,";
		$sql.= "						tbl_peca_fora_linha.peca AS peca_fora_linha
								FROM (
										SELECT  tbl_peca.peca				  ,
												tbl_peca.referencia			  ,
												tbl_peca.descricao			  ,
												tbl_peca.bloqueada_garantia	  ,
												tbl_lista_basica.type		  ,
												tbl_lista_basica.posicao      ,
							                    tbl_lista_basica.serie_inicial,
							                    tbl_lista_basica.serie_final
										FROM tbl_peca
										JOIN tbl_lista_basica USING (peca)
										JOIN tbl_produto      USING (produto) ";
										if ($login_fabrica == 20 AND $login_pais <> 'BR') $sql .= "JOIN tbl_tabela_item ON tbl_tabela_item.peca = tbl_peca.peca AND tabela = $tabela ";
										$sql .= " WHERE tbl_peca.fabrica = $login_fabrica
										AND   tbl_produto.produto = $produto
										AND   tbl_peca.ativo IS TRUE
										AND   $cond_produto";
										//hd 202154 makita vai lancar pedido de produto acabado para postos tipo PATAM 236
										if ($tipo_posto <> 236) { 
											$sql .= " AND   tbl_peca.produto_acabado IS NOT TRUE ";
										}
										if ($login_fabrica == 14 or $login_fabrica == 50) $sql .= " AND tbl_lista_basica.ativo IS NOT FALSE";
										if (strlen($descricao) > 0) $sql .= " AND ( UPPER(TRIM(tbl_peca.descricao)) ILIKE UPPER(TRIM('%$descricao%')) OR UPPER(TRIM(tbl_peca.referencia)) ILIKE UPPER(TRIM('%$descricao%')) )";
										if ($login_fabrica == 2)    $sql .= " AND tbl_peca.bloqueada_venda IS FALSE";
										if ($item_aparencia == 'f') $sql .= " AND tbl_peca.item_aparencia IS FALSE";
										if ($login_fabrica == 6 and strlen($serie) > 0) {
											$sql .= " and tbl_lista_basica.serie_inicial < '$serie'
													  and tbl_lista_basica.serie_final > '$serie'";
										}
										$sql .= "					) AS x
								LEFT JOIN tbl_peca_fora_linha ON tbl_peca_fora_linha.peca = x.peca
							) AS y
						LEFT JOIN tbl_depara ON tbl_depara.peca_de = y.peca
					) AS z
				LEFT JOIN tbl_peca ON tbl_peca.peca = z.peca_para
			    JOIN tbl_lista_basica AS tbl_lbm ON (tbl_lbm.peca = z.peca AND tbl_lbm.produto = $produto)
			    JOIN tbl_produto                 ON (tbl_produto.produto = $produto)
				ORDER BY z.descricao";
	} else {
		$sql = "SELECT z.peca                                ,
						z.referencia       AS peca_referencia ,
						z.descricao        AS peca_descricao  ,
						z.bloqueada_garantia                  ,
						z.peca_fora_linha                     ,
						z.de                                  ,
						z.para                                ,
						z.peca_para                           ,
						tbl_peca.descricao AS para_descricao
				FROM (
						SELECT  y.peca               ,
								y.referencia         ,
								y.descricao          ,
								y.bloqueada_garantia ,
								y.peca_fora_linha    ,
								tbl_depara.de        ,
								tbl_depara.para      ,
								tbl_depara.peca_para
						FROM (
								SELECT  x.peca                                      ,
										x.referencia                                ,
										x.descricao                                 ,
										x.bloqueada_garantia                        ,
										tbl_peca_fora_linha.peca AS peca_fora_linha
								FROM (
										SELECT  tbl_peca.peca       ,
												tbl_peca.referencia ,
												tbl_peca.descricao  ,
												tbl_peca.bloqueada_garantia
										FROM tbl_peca ";
										if ($login_fabrica == 20 AND $login_pais <>'BR') $sql .= "JOIN tbl_tabela_item ON tbl_tabela_item.peca = tbl_peca.peca AND tabela = $tabela ";
										$sql .= " WHERE fabrica = $login_fabrica
										AND ativo IS TRUE";
										//hd 202154 makita vai lancar pedido de produto acabado para postos tipo PATAM 236
										if ($tipo_posto <> 236) { 
											$sql .= " AND   tbl_peca.produto_acabado IS NOT TRUE ";
										}
										if ($login_fabrica == 2) $sql .= " AND tbl_peca.bloqueada_venda IS FALSE";
		if (strlen($descricao) > 0) $sql .= " AND ( UPPER(TRIM(descricao)) ILIKE UPPER(TRIM('%$descricao%')) OR UPPER(TRIM(referencia)) ILIKE UPPER(TRIM('%$descricao%')) )";
		if ($item_aparencia == 'f') $sql .= " AND item_aparencia IS FALSE";
		$sql .= "					) AS x
								LEFT JOIN tbl_peca_fora_linha ON tbl_peca_fora_linha.peca = x.peca
							) AS y
						LEFT JOIN tbl_depara ON tbl_depara.peca_de = y.peca
					) AS z
				LEFT JOIN tbl_peca ON tbl_peca.peca = z.peca_para
				ORDER BY z.descricao";
	}

	$res = pg_query($con,$sql);

	if (@pg_num_rows ($res) == 0) {
		if ($sistema_lingua=='ES') echo "<h1>No consta lista básica para este producto</h1>";
		else echo "<h1>Nenhuma lista básica de peças encontrada para este produto</h1>";

		echo "<script language='javascript'>";
			echo "setTimeout('window.close()',10000);";
		echo "</script>";
		exit;
	}

}

if ($tipo == "descricao") {

	$descricao = trim(strtoupper($_GET["descricao"]));
	$texto = ($sistema_lingua == "ES") ? 'Buscando por el <b>nombre del repuesto</b>' : 'Pesquisando por <b>descrição da peça</b>';?>
	<p style='font-family:Arial, Verdana, Times, Sans;font-size: 12px'><?=$texto?>
	<h4><i><? echo $descricao ?></i></h4>
	</p><?php

	if ($login_fabrica == 15 || $login_fabrica == 24) {//HD 258901

		$sql = " SELECT tbl_kit_peca.referencia,
						tbl_kit_peca.descricao,
						tbl_kit_peca.kit_peca
				FROM    tbl_kit_peca
				WHERE   tbl_kit_peca.fabrica = $login_fabrica
				AND     tbl_kit_peca.produto = $produto";

		if (strlen($descricao) > 0) $sql .= " AND UPPER(TRIM(tbl_kit_peca.descricao)) LIKE UPPER(TRIM('%$descricao%'))";
		$sql .= " ORDER BY tbl_kit_peca.descricao ";
		$res = pg_query($con,$sql);

		if (pg_num_rows($res) > 0) {

			$kit_peca_sim = "sim";
			echo "KIT de Peças";
			echo "<table width='100%' border='1'>";

			for ($i = 0; $i < pg_num_rows($res); $i++) {

				$kit_peca_id    = pg_fetch_result($res,$i,'kit_peca');
				$descricao_kit  = pg_fetch_result($res,$i,'descricao');
				$referencia_kit = pg_fetch_result($res,$i,'referencia');

				echo "<tr>";
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

	$res = pg_query($con,"SELECT COUNT(*) FROM tbl_lista_basica WHERE tbl_lista_basica.fabrica = $login_fabrica");
	$qtde = pg_fetch_result($res,0,0);

	if ($qtde > 0 AND strlen($produto) > 0  AND $login_fabrica <> 43) {
		$sql =	"SELECT DISTINCT z.peca                                ,
						z.referencia       AS peca_referencia ,
						z.descricao        AS peca_descricao  ,
						z.bloqueada_garantia                  ,
						z.type                                ,
						z.posicao                             ,
						z.peca_fora_linha                     ,
						z.de                                  ,
						z.para                                ,
						z.peca_para                           ,";
		if ($login_fabrica == 15) $sql.="        z.serie_inicial		,
				        z.serie_final									,
				        tbl_produto.descricao AS nome_produto			,
				        tbl_produto.serie_inicial AS produto_serie_inicial,
				        tbl_produto.serie_final AS produto_serie_final	,";
		$sql.= "						tbl_peca.descricao AS para_descricao
				FROM (
						SELECT  y.peca               ,
								y.referencia         ,
								y.descricao          ,
								y.bloqueada_garantia ,
								y.type               ,
								y.posicao            ,";
		if ($login_fabrica == 15) $sql.="        y.serie_inicial                 ,
				        y.serie_final									,";
		$sql.= "
								y.peca_fora_linha    ,
								tbl_depara.de        ,
								tbl_depara.para      ,
								tbl_depara.peca_para
						FROM (
								SELECT  x.peca                                      ,
										x.referencia                                ,
										x.descricao                                 ,
										x.bloqueada_garantia                        ,
										x.type                                      ,
										x.posicao                                   ,";
		if ($login_fabrica == 15) $sql.="        x.serie_inicial                 ,
				        x.serie_final									,";
		$sql.= "
										tbl_peca_fora_linha.peca AS peca_fora_linha
								FROM (
										SELECT  tbl_peca.peca              ,
												tbl_peca.referencia        ,
												tbl_peca.descricao         ,
												tbl_peca.bloqueada_garantia,
												tbl_lista_basica.type      ,
												tbl_lista_basica.posicao   ,
							                    tbl_lista_basica.serie_inicial,
							                    tbl_lista_basica.serie_final
										FROM tbl_peca
										JOIN tbl_lista_basica USING (peca)
										JOIN tbl_produto      USING (produto) ";
										if ($login_fabrica == 20 AND $login_pais <>'BR') $sql .= "JOIN tbl_tabela_item ON tbl_tabela_item.peca = tbl_peca.peca AND tabela = $tabela ";
										/* HD 146619 - ALTERACAO 1 de 2 */
										if ($login_fabrica == 15) {
											$sql .= "
											LEFT JOIN tbl_depara ON tbl_lista_basica.peca=tbl_depara.peca_para
											LEFT JOIN tbl_lista_basica AS tbl_lista_basica_de ON tbl_depara.peca_de=tbl_lista_basica_de.peca AND tbl_lista_basica.produto=tbl_lista_basica_de.produto";
										}
										$sql .= "
										WHERE tbl_peca.fabrica = $login_fabrica
										AND   tbl_produto.produto = $produto
										AND   tbl_peca.ativo IS TRUE";
										//hd 202154 makita vai lancar pedido de produto acabado para postos tipo PATAM 236
										if ($tipo_posto <> 236) { 
											$sql .= " AND   tbl_peca.produto_acabado IS NOT TRUE ";
										}

										$sql .= "AND   $cond_produto ";
										if ($login_fabrica == 14 or $login_fabrica==50) $sql .= " AND tbl_lista_basica.ativo IS NOT FALSE";
										if (strlen($descricao) > 0) $sql .= " AND UPPER(TRIM(tbl_peca.descricao)) ILIKE UPPER(TRIM('%$descricao%'))";
										if ($login_fabrica == 2)    $sql .= " AND tbl_peca.bloqueada_venda IS FALSE";
										if ($item_aparencia == 'f') $sql .= " AND tbl_peca.item_aparencia IS FALSE";
										if ($login_fabrica == 6 and strlen($serie) > 0) {
											$sql .= " and tbl_lista_basica.serie_inicial < '$serie'
													  and tbl_lista_basica.serie_final > '$serie'";
										}
										$sql .= "					) AS x
								LEFT JOIN tbl_peca_fora_linha ON tbl_peca_fora_linha.peca = x.peca
							) AS y
						LEFT JOIN tbl_depara ON tbl_depara.peca_de = y.peca
					) AS z
				LEFT JOIN tbl_peca ON tbl_peca.peca = z.peca_para
			    JOIN tbl_lista_basica AS tbl_lbm ON (tbl_lbm.peca = z.peca AND tbl_lbm.produto = $produto)
			    JOIN tbl_produto                 ON (tbl_produto.produto = $produto)
				ORDER BY z.descricao";
	} else {
		$sql =	"SELECT z.peca                                ,
						z.referencia       AS peca_referencia ,
						z.descricao        AS peca_descricao  ,
						z.bloqueada_garantia                  ,
						z.peca_fora_linha                     ,
						z.de                                  ,
						z.para                                ,
						z.peca_para                           ,
						tbl_peca.descricao AS para_descricao
				FROM (
						SELECT  y.peca               ,
								y.referencia         ,
								y.descricao          ,
								y.bloqueada_garantia ,
								y.peca_fora_linha    ,
								tbl_depara.de        ,
								tbl_depara.para      ,
								tbl_depara.peca_para
						FROM (
								SELECT  x.peca                                      ,
										x.referencia                                ,
										x.descricao                                 ,
										x.bloqueada_garantia                        ,
										tbl_peca_fora_linha.peca AS peca_fora_linha
								FROM (
										SELECT  tbl_peca.peca       ,
												tbl_peca.referencia ,
												tbl_peca.descricao  ,
												tbl_peca.bloqueada_garantia
										FROM tbl_peca
										$join_produto ";
										if($login_fabrica == 20 AND $login_pais <>'BR') $sql .= "JOIN tbl_tabela_item ON tbl_tabela_item.peca = tbl_peca.peca AND tabela = $tabela ";
										$sql .= " WHERE tbl_peca.fabrica = $login_fabrica
										AND tbl_peca.ativo IS TRUE";
										//hd 202154 makita vai lancar pedido de produto acabado para postos tipo PATAM 236
										if ($tipo_posto <> 236) { 
											$sql .= " AND   tbl_peca.produto_acabado IS NOT TRUE";
										}
										$sql .= " $condicao_produto ";
										if ($login_fabrica == 2) $sql .= " AND tbl_peca.bloqueada_venda IS FALSE";
		if (strlen($descricao) > 0) $sql .= " AND UPPER(TRIM(tbl_peca.descricao)) ILIKE UPPER(TRIM('%$descricao%'))";
		if ($item_aparencia == 'f') $sql .= " AND item_aparencia IS FALSE";
		$sql .= "					) AS x
								LEFT JOIN tbl_peca_fora_linha ON tbl_peca_fora_linha.peca = x.peca
							) AS y
						LEFT JOIN tbl_depara ON tbl_depara.peca_de = y.peca
					) AS z
				LEFT JOIN tbl_peca ON tbl_peca.peca = z.peca_para
				ORDER BY z.descricao";
	}

	$res = pg_query($con,$sql);

	if (@pg_num_rows($res) == 0 and strlen($kit_peca_sim) == 0) {
		if ($login_fabrica == 1) {
			echo "<h2>Item '$descricao' não existe <br> para o produto $produto_referencia, <br> consulte a vista explodida atualizada <br> e verifique o código correto</h2>";
		} else {
			if ($sistema_lingua == "ES") echo "Reupesto '$descricao' no encontrado <br>para el producto $produto_referencia";
			else                         echo "<h1>Peça '$descricao' não encontrada<br>para o produto $produto_referencia</h1>";
		}

		echo "<script language='javascript'>";
			echo "setTimeout('window.close()',10000);";
		echo "</script>";
		exit;

	}

}

if ($tipo == "referencia") {

	$referencia = trim(strtoupper($_GET["peca"]));
	$referencia = str_replace(".","",$referencia);
	$referencia = str_replace(",","",$referencia);
	$referencia = str_replace("-","",$referencia);
	$referencia = str_replace("/","",$referencia);
	$referencia = str_replace(" ","",$referencia);

	echo "<p style='font-family:Arial, Verdana, Times, Sans;font-size:12px'>";
	echo ($sistema_lingua == "ES") ? "Buscando por <b>referencia</b>: " : "Pesquisando por <b>referência da peça</b>: ";
	echo "<i>$referencia</i></p>";
	echo "<br>";

	if ($login_fabrica == 15 || $login_fabrica == 24) {//HD 258901

		$sql = " SELECT tbl_kit_peca.referencia,
						tbl_kit_peca.descricao,
						tbl_kit_peca.kit_peca
				FROM    tbl_kit_peca
				WHERE   tbl_kit_peca.fabrica= $login_fabrica
				AND     tbl_kit_peca.produto = $produto";
		if (strlen($referencia) > 0) $sql .= " AND UPPER(TRIM(tbl_kit_peca.referencia)) LIKE UPPER(TRIM('%$referencia%'))";
		$sql .= " ORDER BY tbl_kit_peca.descricao ";

		$res = pg_query($con,$sql);

		if (pg_num_rows($res) > 0) {
			$kit_peca_sim = "sim";
			echo "KIT de Peças";
			echo "<table width='100%' border='1'>";
			for ($i = 0; $i < pg_num_rows($res); $i++) {
				$kit_peca_id    = pg_fetch_result($res,0,'kit_peca');
				$descricao_kit  = pg_fetch_result($res,0,'descricao');
				$referencia_kit = pg_fetch_result($res,0,'referencia');
				echo "<tr>";
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
		}
	}

	$res = pg_query($con,"SELECT COUNT(*) FROM tbl_lista_basica WHERE tbl_lista_basica.fabrica = $login_fabrica");
	$qtde = pg_fetch_result($res,0,0);

	if ($qtde > 0 and strlen($produto) > 0 AND $login_fabrica <> 43) {
		$sql =	"SELECT DISTINCT z.peca                       ,
						z.referencia       AS peca_referencia ,
						z.descricao        AS peca_descricao  ,
						z.bloqueada_garantia                  ,
						z.type                                ,
						z.posicao                             ,
						z.peca_fora_linha                     ,
						z.de                                  ,
						z.para                                ,
						z.peca_para                           ,";
		if ($login_fabrica == 15) $sql.="        z.serie_inicial                 ,
				        z.serie_final									,
				        tbl_produto.descricao AS nome_produto			,
				        tbl_produto.serie_inicial AS produto_serie_inicial,
				        tbl_produto.serie_final AS produto_serie_final,";
		$sql.= "				tbl_peca.descricao AS para_descricao
				FROM (
						SELECT  y.peca               ,
								y.referencia         ,
								y.descricao          ,
								y.bloqueada_garantia ,
								y.type               ,
								y.posicao            ,";
		if ($login_fabrica == 15) $sql.="        y.serie_inicial                 ,
				        y.serie_final									,";
		$sql.= "				y.peca_fora_linha    ,
								tbl_depara.de        ,
								tbl_depara.para      ,
								tbl_depara.peca_para
						FROM (
								SELECT  x.peca                                      ,
										x.referencia                                ,
										x.descricao                                 ,
										x.bloqueada_garantia                        ,
										x.type                                      ,
										x.posicao                                   ,";
		if ($login_fabrica == 15) $sql.="        x.serie_inicial                 ,
				        x.serie_final									,";
		$sql.= "
										tbl_peca_fora_linha.peca AS peca_fora_linha
								FROM (
										SELECT  tbl_peca.peca				,
												tbl_peca.referencia			,
												tbl_peca.descricao			,
												tbl_peca.bloqueada_garantia	,
												tbl_lista_basica.type		,
												tbl_lista_basica.posicao	,
							                    tbl_lista_basica.serie_inicial,
							                    tbl_lista_basica.serie_final
										FROM tbl_peca
										JOIN tbl_lista_basica USING (peca)
										JOIN tbl_produto      USING (produto) ";
		if ($login_fabrica == 20 AND $login_pais <> 'BR')
			$sql .= "JOIN tbl_tabela_item ON tbl_tabela_item.peca = tbl_peca.peca AND tabela = $tabela ";
		if ($login_fabrica == 15) {
			$sql .= "
				LEFT JOIN tbl_depara ON tbl_lista_basica.peca=tbl_depara.peca_para
				LEFT JOIN tbl_lista_basica AS tbl_lista_basica_de ON tbl_depara.peca_de=tbl_lista_basica_de.peca AND tbl_lista_basica.produto=tbl_lista_basica_de.produto
			";
		}
		$sql .= "					WHERE tbl_peca.fabrica = $login_fabrica
									AND   tbl_produto.produto = $produto
									AND   tbl_peca.ativo IS TRUE ";
									//hd 202154 makita vai lancar pedido de produto acabado para postos tipo PATAM 236
									if ($tipo_posto <> 236) { 
											$sql .= " AND   tbl_peca.produto_acabado IS NOT TRUE ";
										}
									$sql .= "AND   $cond_produto ";
		if ($login_fabrica == 14 or $login_fabrica == 50) $sql .= " AND tbl_lista_basica.ativo IS NOT FALSE";
		if (strlen($referencia) > 0) $sql .= " AND UPPER(TRIM(tbl_peca.referencia_pesquisa)) ILIKE UPPER(TRIM('%$referencia%'))";
		if ($login_fabrica == 2) $sql .= " AND tbl_peca.bloqueada_venda IS FALSE";
		if ($item_aparencia == 'f') $sql .= " AND tbl_peca.item_aparencia IS FALSE";
		if ($login_fabrica == 6 and strlen($serie) > 0) {
			$sql .= " and tbl_lista_basica.serie_inicial < '$serie'
					  and tbl_lista_basica.serie_final > '$serie'";
		}
		$sql .= "					) AS x
								LEFT JOIN tbl_peca_fora_linha ON tbl_peca_fora_linha.peca = x.peca
							) AS y
						LEFT JOIN tbl_depara ON tbl_depara.peca_de = y.peca
					) AS z
				LEFT JOIN tbl_peca ON tbl_peca.peca = z.peca_para
			    JOIN tbl_lista_basica AS tbl_lbm ON (tbl_lbm.peca = z.peca AND tbl_lbm.produto = $produto)
			    JOIN tbl_produto                 ON (tbl_produto.produto = $produto)
				ORDER BY z.descricao";
	} else {
		$sql =	"SELECT z.peca                                ,
						z.referencia       AS peca_referencia ,
						z.descricao        AS peca_descricao  ,
						z.bloqueada_garantia                  ,
						z.peca_fora_linha                     ,
						z.de                                  ,
						z.para                                ,
						z.peca_para                           ,
						tbl_peca.descricao AS para_descricao
				FROM (
						SELECT  y.peca               ,
								y.referencia         ,
								y.descricao          ,
								y.bloqueada_garantia ,
								y.peca_fora_linha    ,
								tbl_depara.de        ,
								tbl_depara.para      ,
								tbl_depara.peca_para
						FROM (
								SELECT  x.peca                                      ,
										x.referencia                                ,
										x.descricao                                 ,
										x.bloqueada_garantia                        ,
										tbl_peca_fora_linha.peca AS peca_fora_linha
								FROM (
										SELECT  tbl_peca.peca              ,
												tbl_peca.referencia        ,
												tbl_peca.descricao         ,
												tbl_peca.bloqueada_garantia
										FROM tbl_peca
										$join_produto ";
		if($login_fabrica == 20 AND $login_pais <>'BR')
			$sql .= "					JOIN tbl_tabela_item using(peca) AND tabela = $tabela ";
		$sql .= "						WHERE tbl_peca.fabrica = $login_fabrica
										AND   tbl_peca.ativo IS TRUE";
										//hd 202154 makita vai lancar pedido de produto acabado para postos tipo PATAM 236
										if ($tipo_posto <> 236) { 
											$sql .= " AND   tbl_peca.produto_acabado IS NOT TRUE ";
										}
										$sql .= "$condicao_produto ";
		if (strlen($referencia) > 0) $sql .= " AND UPPER(TRIM(tbl_peca.referencia_pesquisa)) ILIKE UPPER(TRIM('%$referencia%'))";
		if($login_fabrica == 2) $sql .= " AND tbl_peca.bloqueada_venda IS FALSE";
		if ($item_aparencia == 'f') $sql .= " AND item_aparencia IS FALSE";
		$sql .= "					) AS x
								LEFT JOIN tbl_peca_fora_linha ON tbl_peca_fora_linha.peca = x.peca
							) AS y
						LEFT JOIN tbl_depara ON tbl_depara.peca_de = y.peca
					) AS z
				LEFT JOIN tbl_peca ON tbl_peca.peca = z.peca_para
				ORDER BY z.descricao";
	}

	$res = @pg_query($con,$sql);

	if (@pg_num_rows($res) == 0) {
		if ($login_fabrica == 1) {
			echo "<h2>Item '$referencia' não existe <br> para o produto $produto_referencia, <br> consulte a vista explodida atualizada <br> e verifique o código correto</h2>";
		}else{
			if($sistema_lingua == "ES") echo "<h1>Repuesto '$referencia' no encontrado <br>para el producto $produto_referencia</h1>";
			else  echo "<h1>Peça '$referencia' não encontrada<br>para o produto $produto_referencia</h1>";
		}
		echo "<script language='JavaScript'>";
		echo "setTimeout('window.close()',10000);";
		echo "</script>";
		exit;
	}


}


if($tipo == 'posicao'){
	$posicao = trim(strtoupper($_GET["posicao"]));
	$posicao = str_replace(".","",$posicao);
	$posicao = str_replace(",","",$posicao);
	$posicao = str_replace("-","",$posicao);
	$posicao = str_replace("/","",$posicao);
	$posicao = str_replace(" ","",$posicao);

	if($sistema_lingua == "ES") {
		echo "<p style='font-family:Arial, Verdana, Times, Sans;font-size:12px'>Buscando por <b>posición</b>: ";
	}else{
		echo "<p style='font-family:Arial, Verdana, Times, Sans;font-size:12px'>Pesquisando por <b>posição da peça</b>: ";
	}
	echo "<i>$posicao</i></p>";
	echo "<br>";

	$res = pg_query($con,"SELECT COUNT(*) FROM tbl_lista_basica WHERE tbl_lista_basica.fabrica = $login_fabrica");
	$qtde = pg_fetch_result($res,0,0);

	if ($qtde > 0 and strlen($produto) > 0) {
		$sql =	"SELECT z.peca                                ,
						z.referencia       AS peca_referencia ,
						z.descricao        AS peca_descricao  ,
						z.bloqueada_garantia                  ,
						z.type                                ,
						z.posicao                             ,
						z.peca_fora_linha                     ,
						z.de                                  ,
						z.para                                ,
						z.peca_para                           ,
						tbl_peca.descricao AS para_descricao
				FROM (
						SELECT  y.peca               ,
								y.referencia         ,
								y.descricao          ,
								y.bloqueada_garantia ,
								y.type               ,
								y.posicao            ,
								y.peca_fora_linha    ,
								tbl_depara.de        ,
								tbl_depara.para      ,
								tbl_depara.peca_para
						FROM (
								SELECT  x.peca                                      ,
										x.referencia                                ,
										x.descricao                                 ,
										x.bloqueada_garantia                        ,
										x.type                                      ,
										x.posicao                                   ,
										tbl_peca_fora_linha.peca AS peca_fora_linha
								FROM (
										SELECT  tbl_peca.peca              ,
												tbl_peca.referencia        ,
												tbl_peca.descricao         ,
												tbl_peca.bloqueada_garantia,
												tbl_lista_basica.type      ,
												tbl_lista_basica.posicao
										FROM tbl_peca
										JOIN tbl_lista_basica USING (peca)
										JOIN tbl_produto      USING (produto)
										WHERE tbl_peca.fabrica = $login_fabrica
										AND   tbl_produto.produto = $produto
										AND   tbl_peca.ativo IS TRUE";
										//hd 202154 makita vai lancar pedido de produto acabado para postos tipo PATAM 236
										if ($tipo_posto <> 236) { 
											$sql .= " AND   tbl_peca.produto_acabado IS NOT TRUE ";
										}
										$sql .= " AND   $cond_produto
										 AND tbl_lista_basica.ativo IS NOT FALSE";
										if (strlen($posicao) > 0) $sql .= " AND UPPER(TRIM(tbl_lista_basica.posicao)) ILIKE UPPER(TRIM('%$posicao%'))";
										if($login_fabrica == 2) $sql .= " AND tbl_peca.bloqueada_venda IS FALSE";
										if ($item_aparencia == 'f') $sql .= " AND tbl_peca.item_aparencia IS FALSE";
										$sql .= "					) AS x
								LEFT JOIN tbl_peca_fora_linha ON tbl_peca_fora_linha.peca = x.peca
							) AS y
						LEFT JOIN tbl_depara ON tbl_depara.peca_de = y.peca
					) AS z
				LEFT JOIN tbl_peca ON tbl_peca.peca = z.peca_para
				ORDER BY z.descricao";
	}

	$res = @pg_query($con,$sql);
	if (@pg_num_rows($res) == 0) {
		if($sistema_lingua == "ES") echo "<h1>Pieza '$posicao' no encontrada <br>para el producto $produto_referencia</h1>";
		else  echo "<h1>Posição '$posicao' não encontrada<br>para o produto $produto_referencia</h1>";
		echo "<script language='JavaScript'>";
		echo "setTimeout('window.close()',10000);";
		echo "</script>";
		exit;
	}
}

echo "<script language='JavaScript'>\n";
echo "<!--\n";
echo "this.focus();\n";
echo "// -->\n";
echo "</script>\n";

$contador = 999;

$num_pecas = pg_num_rows($res);
$gambiara = 0;
for ( $i = 0 ; $i < $num_pecas; $i++ ) {
	$peca				= trim(@pg_fetch_result($res,$i,peca));
	//echo $peca;
	$peca_referencia	= trim(@pg_fetch_result($res,$i,peca_referencia));
	$peca_descricao		= trim(@pg_fetch_result($res,$i,peca_descricao));
	$peca_descricao_js	= strtr($peca_descricao, array('"'=>'&rdquo;', "'"=>'&rsquo;'));  //07/05/2010 MLG - HD 235753
	$type				= trim(@pg_fetch_result($res,$i,type));
	$posicao			= trim(@pg_fetch_result($res,$i,posicao));
	$peca_fora_linha	= trim(@pg_fetch_result($res,$i,peca_fora_linha));
	$peca_para			= trim(@pg_fetch_result($res,$i,peca_para));
	$para				= trim(@pg_fetch_result($res,$i,para));
	$para_descricao		= trim(@pg_fetch_result($res,$i,para_descricao));
	$bloqueada_garantia	= trim(@pg_fetch_result($res,$i,bloqueada_garantia));

//  HD 189523 - MLG - Latinatec filtra a Lista Básica usando o 2º caractere do nº de série para controlar a versão
	if ($login_fabrica==15 and $serie and $produto) {
        $l_serie_inicial	= trim(pg_fetch_result($res,$i,serie_inicial));
        $l_serie_final		= trim(pg_fetch_result($res,$i,serie_final));
        $p_serie_inicial	= trim(pg_fetch_result($res,$i,produto_serie_inicial));
        $p_serie_final		= trim(pg_fetch_result($res,$i,produto_serie_final));
	    if (!valida_serie_latinatec($serie,$p_serie_inicial,$p_serie_final,$l_serie_inicial,$l_serie_final)){ 
			// HD 270590
			if($gambiara == 0){
				echo "Se não retornar nenhuma peça, provavelmente o número de série esteja errado ou a peça não pertence a este modelo de produto !";
				$gambiara = 1;
			}
			continue;
		}
	}

	$sql_idioma = "SELECT descricao AS peca_descricao FROM tbl_peca_idioma WHERE peca = $peca AND upper(idioma) = '$sistema_lingua'";
	
	$res_idioma = @pg_query($con,$sql_idioma);
	if (@pg_num_rows($res_idioma) >0) {
		$peca_descricao    = pg_fetch_result($res_idioma,0,peca_descricao);
		$peca_descricao_js = strtr($peca_descricao, array('"'=>'&rdquo;', "'"=>'&rsquo;'));  //07/05/2010 MLG - HD 235753
	}

/*	if ($login_fabrica == 3 && getenv("REMOTE_ADDR") == "201.0.9.216") {
		$x_referencia_pesquisa = str_replace(".","",$peca_referencia);
		$x_referencia_pesquisa = str_replace("-","",$x_referencia_pesquisa);
		$x_referencia_pesquisa = str_replace(" ","",$x_referencia_pesquisa);
		$x_sql =	"SELECT COUNT(tbl_produto.produto)
					FROM tbl_produto
					JOIN tbl_linha USING (linha)
					WHERE tbl_linha.fabrica = $login_fabrica
					AND   tbl_produto.referencia_pesquisa = '$x_referencia_pesquisa';";
		$x_res = pg_query($con,$x_sql);
		echo nl2br($x_sql)."<br>".pg_num_rows($x_res);
		$produto_peca = false;
		if (pg_num_rows($x_res) == 1) {
			$produto_peca = true;
		}

		if (isset($produto_peca)) {
		exit;
		}
	}*/

	$resT = @pg_query($con,"SELECT tabela FROM tbl_tabela WHERE fabrica = $login_fabrica AND tbl_tabela.ativa IS TRUE");

	if($login_fabrica == 6) {
		$resT = pg_query($con,"SELECT tabela FROM tbl_posto_linha JOIN tbl_linha using(linha) WHERE posto = $login_posto AND fabrica = $login_fabrica limit 1");
		if(pg_num_rows($resT) <> 1){
			$resT = pg_query($con,"SELECT tabela FROM tbl_tabela WHERE fabrica = $login_fabrica AND tbl_tabela.ativa IS TRUE");
		}
	}
	if($login_fabrica == 72 or $login_fabrica > 84) {
		$resT = pg_query($con,"SELECT tabela FROM tbl_posto_linha JOIN tbl_linha using(linha) WHERE posto = $login_posto AND fabrica = $login_fabrica limit 1");
		if(pg_num_rows($resT) <> 1){
			$resT = pg_query($con,"SELECT tabela FROM tbl_tabela WHERE fabrica = $login_fabrica AND tbl_tabela.ativa IS TRUE");
		}
	}

	if($login_fabrica == 40) {
		$resT = pg_query($con,"SELECT tabela_posto as tabela FROM tbl_posto_linha JOIN tbl_linha using(linha) WHERE posto = $login_posto AND fabrica = $login_fabrica limit 1");
	}

	if ($login_fabrica == 2 or $login_fabrica == 50) {
		if (@pg_num_rows($resT) >= 1) {
			if($login_fabrica == 2){
				$tabela = 236;
			}
			if($login_fabrica == 50){
				$tabela = 213;
			}
			if (strlen($para) > 0) {
				$sqlT = "SELECT preco FROM tbl_tabela_item WHERE tabela = $tabela AND peca = $peca_para";
				$peca_ipi = $peca_para;
			}else{
				$sqlT = "SELECT preco FROM tbl_tabela_item WHERE tabela = $tabela AND peca = $peca";
				$peca_ipi = $peca;
			}
			$resT = pg_query($con,$sqlT);
			if (pg_num_rows($resT) == 1) {
				$sqlipi = "SELECT ipi FROM tbl_peca WHERE peca = $peca_ipi";
				$resipi = pg_query($con,$sqlipi);
				if(pg_num_rows($resipi)>0){
					$ipi_peca = pg_fetch_result($resipi,0,0);
					if(strlen($ipi_peca)>0 and $ipi_peca <> 0){
						$preco = number_format ((pg_fetch_result($resT,0,0)*(1+($ipi_peca/100))),2,",",".");
					}else{
						$preco = number_format (pg_fetch_result($resT,0,0),2,",",".");
					}
				}
			}else{
				$preco = "";
			}
		}else{
			$preco = "";
		}
	} else {
		if (pg_num_rows($resT) == 1) {
		$tabela = pg_fetch_result($resT,0,0);
		if (strlen($para) > 0) {
			$sqlT = "SELECT preco FROM tbl_tabela_item WHERE tabela = $tabela AND peca = $peca_para";
		}else{
			$sqlT = "SELECT preco FROM tbl_tabela_item WHERE tabela = $tabela AND peca = $peca";
		}
		$resT = pg_query($con,$sqlT);
		$preco = (pg_num_rows($resT) == 1) ? number_format (pg_fetch_result($resT,0,0),2,",",".") : "";
		}else{
			$preco = "";
		}
	}

	if ($contador > 50) {
		$contador = 0 ;
		echo "</table><table width='100%' border='1'>\n";
		flush();
	}
	$contador++;
	$cor = (strlen($peca_fora_linha) > 0) ? '#FFEEEE' : '#ffffff';

	echo "<tr bgcolor='$cor'>\n";
// 	    echo "<td>$l_serie_inicial/$l_serie_final</td>";

	if ($login_fabrica == 14 or $login_fabrica == 24 or $login_fabrica == 66) {
		echo "<td nowrap style='font-family:Arial, Verdana, Times, Sans;font-size:10px;color:black'>$posicao</td>\n";
	}

	if ($login_fabrica == 3) {
		$sql = "SELECT tbl_linha.codigo_linha FROM tbl_linha WHERE linha = (SELECT tbl_produto.linha FROM tbl_produto JOIN tbl_lista_basica ON tbl_produto.produto = tbl_lista_basica.produto WHERE tbl_lista_basica.peca = $peca LIMIT 1)";
		$resX = pg_query($con,$sql);
		$codigo_linha = @pg_fetch_result($resX,0,0);

		if (strlen ($codigo_linha) == 0) $codigo_linha = "&nbsp;";

		echo "<td nowrap style='font-family:Arial, Verdana, Times, Sans;font-size:10px;color:#999999'>$codigo_linha</td>\n";
	}

	echo "<td nowrap style='font-family:Arial, Verdana, Times, Sans;font-size:10px;color:black'>$peca_referencia</td>\n";

	echo "<td nowrap style='font-family:Arial, Verdana, Times, Sans;font-size:10px;color:black'>";

	if (strlen($peca_fora_linha) > 0 OR strlen($para) > 0) {
		echo $peca_descricao;
		//HD102404 waldir
		if ($login_fabrica == 30 or $login_fabrica == 85){
			$sql_tabela  = "SELECT tabela
							FROM tbl_posto_linha
							JOIN tbl_linha ON tbl_linha.linha = tbl_posto_linha.linha AND tbl_linha.fabrica = $login_fabrica
							WHERE tbl_posto_linha.posto = $login_posto LIMIT 1;";
			$res_tabela  = @pg_query($con,$sql_tabela);
			$tabela      = trim(@pg_fetch_result($res_tabela,0,tabela));

			$sql_preco = "SELECT preco FROM tbl_tabela_item WHERE peca=$peca_para AND tabela=$tabela";
			$res_preco = @pg_query($con,$sql_preco);
			$preco     = trim(@pg_fetch_result($res_preco,0,preco));

		}
	}else{
		//HD92435 - paulo
		if ($login_fabrica == 30 or $login_fabrica == 85){
			$sql_tabela  = "SELECT tabela
							FROM tbl_posto_linha
							JOIN tbl_linha ON tbl_linha.linha = tbl_posto_linha.linha AND tbl_linha.fabrica = $login_fabrica
							WHERE tbl_posto_linha.posto = $login_posto LIMIT 1;";
			$res_tabela  = @pg_query($con,$sql_tabela);

			$tabela      = trim(@pg_fetch_result($res_tabela,0,tabela));

			$sql_preco = "SELECT preco FROM tbl_tabela_item WHERE peca=$peca AND tabela=$tabela";

			$res_preco = @pg_query($con,$sql_preco);
			$preco     = trim(@pg_fetch_result($res_preco,0,preco));

		}
		//somente para pedido de pecas faturadas
		if ($login_fabrica == 30 && strlen($preco)==0 && strlen($os==0)) {
			echo "<a href=\"javascript: alert('Peça Bloqueada \\n \\r Em caso de dúvidas referente a código de peças que o Sistema não está aceitando, favor entrar em contato com a Fábrica através do Fone: (85) 3299-8992 ou por e-mail: pedidos.at@esmaltec.com.br.')\" ".
				 "style='font-family:Arial, Verdana, Times, Sans-Serif;size:10px;color:red'>$peca_descricao</a>";
		}else{
			if($login_fabrica==19 AND strlen($linha)>0){//HD 100696
				$caminho = $caminho."/".$login_fabrica;
				$diretorio_verifica=$caminho."/pequena/";
				$peca_reposicao = $_GET['peca_reposicao'];
				if($peca_reposicao == 't' ){
					echo "<a href='produto_pesquisa_lista.php?peca=$peca&tipo=referencia' ".
						 "target='_blank' ".
						 "style='font-family:Arial, Verdana, Times, Sans-Serif;size:10px;color:red'>".
						 $peca_descricao.
						 "</a>";
				}else{
					if(is_dir($diretorio_verifica) == true){
						if ($dh = opendir($caminho."/pequena/")) {
						$contador=0;
							while (false !== ($filename = readdir($dh))) {
								if($contador == 1) break;
								if (strpos($filename,$peca) !== false){
									$contador++;
									$po = strlen($peca);
									if(substr($filename, 0,$po)==$peca){
										$img = explode(".", $filename);
											echo "<a href='$caminho/pequena/$img[0].pdf' target='_blank' ".
												 "style='font-family:Arial, Verdana, Times, Sans-Serif;size:10px;color:red'>".
												 $peca_descricao.
												 "</a>";
									}
								}
							}
						}
						if($contador == 0){
							if ($dh = opendir($caminho."/pequena/")) {
								$contador=0;
								while (false !== ($filename = readdir($dh))) {
									if($contador == 1) break;
									if (strpos($filename,$peca_referencia) !== false){
										$contador++;
										$po = strlen($peca_referencia);
										if(substr($filename, 0,$po)==$peca_referencia){
											$img = explode(".", $filename);
											echo "<a href='$caminho/pequena/$img[0].pdf' target='_blank' ".
												 "style='font-family:Arial, Verdana, Times, Sans-Serif;size:10px;color:red'>".
												 $peca_descricao.
												 "</a>";
										}
									}
								}
							}
						}
					}else{
						echo "<span style='font-family:Arial, Verdana, Times, Sans-Serif;size:10px;color:red'>$peca_descricao</a>";
					}
				}
			} else {
				echo '<a href="javascript: ';
// 				$peca_descricao = addslashes($peca_descricao);
				echo "referencia.value='$peca_referencia';descricao.value='$peca_descricao_js '; ";
				if ($login_fabrica == 14 or $login_fabrica == 24 or $login_fabrica == 5) {
					echo " posicao.value='$posicao';";
				} else {
					echo " preco.value='$preco';";
				}
				if ($login_fabrica == 30) {
					echo " if (qtde) {qtde.value='1';} ";
				}
				if (strlen($kit_peca) > 0) {
					echo " window.opener.$('#$kit_peca').html('');";
				}
				echo ' window.close();"';

				echo " style='font-family:Arial, Verdana, Times, Sans-Serif;size:10px;color:blue'>$peca_descricao</a>";
			}
		}
	}
	echo "</td>\n";

	if ($login_fabrica == 1) {
		echo "<td nowrap style='font-family:Arial, Verdana, Times, Sans-Serif;size:10px;color:black'>$type</td>\n";
	}

	$sqlX =	"SELECT DISTINCT referencia, to_char(tbl_peca.previsao_entrega,'DD/MM/YYYY') AS previsao_entrega
			FROM tbl_peca
			WHERE referencia_pesquisa = UPPER('$peca_referencia')
			AND   fabrica = $login_fabrica
			AND   previsao_entrega NOTNULL;";
	$resX = pg_query($con,$sqlX);

	if (pg_num_rows($resX) == 0) {
		echo "<td nowrap>";
		if (strlen($peca_fora_linha) > 0) {
			echo "<span style='font-family:Arial, Verdana, Times, Sans-Serif;size:10px;color:black;font-weight:bold'>";
			if ($login_fabrica == 1) echo "É obsoleta,<br>não é mais fornecida";
			else {
				echo ($sistema_lingua=='ES') ? "Obsoleta" : "Fora de linha";
			}
			echo "</span>";
		}else{
			/* HD  152192 a Esmaltec não faz pedido pela OS, somente informa troca com peça do posto
			e geralmente o posto comprou com o codigo da peça antiga, por isto não vai passar no de-para */
			if (strlen($para) > 0 ) {
				if($login_fabrica == 30){
					echo "<a href=\"javascript:";
					echo "referencia.value='$peca_referencia'; descricao.value='$peca_descricao';";
					echo "preco.value='$preco';";
					echo "if (qtde) {qtde.value='1';} ";
					echo "window.opener.$('#$kit_peca').html(''); window.close();";
					echo " \" style='font-family:Arial, Verdana, Times, Sans-Serif;size:10px;color:red'>$peca_descricao</a>";
				}else{
					if(strlen($peca_descricao)>0){ #HD 228968
						$sql_idioma = "SELECT tbl_peca_idioma.descricao AS peca_descricao FROM tbl_peca_idioma JOIN tbl_peca USING(peca) WHERE descricao = '$peca_descricao' AND upper(idioma) = '$sistema_lingua'";

						$res_idioma = @pg_query($con,$sql_idioma);
						if (@pg_num_rows($res_idioma) >0) {
							$peca_descricao  = preg_replace('/([\"|\'])/','\\$1',(pg_fetch_result($res_idioma,0,peca_descricao)));
						}
					}

					echo "<span style='font-family:Arial, Verdana, Times, Sans-Serif;size:10px;color:black;font-weight:bold'>Mudou Para</span>";
					echo " <a href=\"javascript: ";
					echo " referencia.value='$para'; descricao.value='$peca_descricao'; preco.value='$preco'; ";
					if ($login_fabrica == 30) {
						echo " if (qtde) {qtde.value='1';} ";
					}
					if(strlen($kit_peca) > 0) {
						echo "window.opener.$(\"#$kit_peca\").html(\"\");";
					}
					echo " window.close();";
					echo "\"style='font-family:Arial, Verdana, Times, Sans-Serif;size:10px;color:red'>$para</a>";
				}
			}else{
				echo "&nbsp;";
			}
		}
		echo "</td>\n";
		echo "<td nowrap align='right'>";
/*if ($handle = opendir('imagens_pecas/pequena/.')) {
			while (false !== ($file = readdir($handle))) {
				$contador++;
				if($contador == 1) break;
				$posicao = strpos($file, $peca_referencia);
				if ($file != "." && $file != ".." ) {

					<a href="#" onclick="onoff('teste<? echo $contador; ?>')">
					<img src="<?echo $caminho; ?>/pequena/<? echo $file;?>">
					</a>
					<div id="teste<? echo $contador;?>" style="display:none">
					<img src="<?echo $caminho; ?>/media/<? echo $file;?>">
					</div><br>
					<?
				}
			}
	closedir($handle);
}		*/
		$diretorio_verifica=$caminho."/pequena/";
		if(is_dir($diretorio_verifica) == true){
			if ($dh = opendir($caminho."/pequena/")) {
			$contador=0;
				while (false !== ($filename = readdir($dh))) {
					if($contador == 1) break;
					if (strpos($filename,$peca) !== false){
						$contador++;
						$po = strlen($peca);
						if(substr($filename, 0,$po)==$peca){
							echo "<a href=\"javascript:mostraPeca('$filename')\">";
							echo "<img src='$caminho/pequena/$filename' border='0'>";
							echo "</a>";
						}
					}
				}
			}
			if($contador == 0){
				if ($dh = opendir($caminho."/pequena/")) {
					$contador=0;
					while (false !== ($filename = readdir($dh))) {

						if($contador == 1) break;
						if (strpos($filename,$peca_referencia) !== false){
							$contador++;
							$po = strlen($peca_referencia);
							if(substr($filename, 0,$po)==$peca_referencia){
								echo "<a href=\"javascript:mostraPeca('$filename')\">";
								echo "<img src='$caminho/pequena/$filename' border='0'>";
								echo "</a>";
							}
						}
					}
				}
			}
		}

		echo "</td>\n";

		if($login_fabrica == 3 AND $peca == '526199' ){
			echo "</tr>";
			echo "<tr>";
			echo "<td colspan='4'align='center'><img src='imagens_pecas/526199.gif' class='Div' >";
			echo "</td>\n";
		}
	}else{
		echo "</tr>\n";
		echo "<tr>\n";
		$peca_previsao    = pg_fetch_result($resX,0,0);
		$previsao_entrega = pg_fetch_result($resX,0,1);

		$data_atual         = date("Ymd");
		$x_previsao_entrega = substr($previsao_entrega,6,4) . substr($previsao_entrega,3,2) . substr($previsao_entrega,0,2);
		echo "<td colspan='2' style='font-family:Arial, Verdana, Times, Sans;font-size:10px;color:black;font-weight:bold'>\n";
		if ($data_atual < $x_previsao_entrega) {
		if ($sistema_lingua=='ES') echo "Este repuesto estará disponible en: $previsao_entrega";
		else echo "Esta peça estará disponível em $previsao_entrega";
		echo "<br>";
		if ($sistema_lingua=='ES') echo "Para repuestos con plazo de entrega superior a 25 días, el fabricante tomará las medidas necesarias para atender al consumidor";
		else echo "Para as peças com prazo de fornecimento superior a 25 dias, a fábrica tomará as medidas necessárias para atendimento do consumidor";
		}
		echo "</td>\n";
	}

	echo "</tr>\n";

	if ($exibe_mensagem == 't' AND $bloqueada_garantia == 't' and $login_fabrica == 3){
		echo "<tr>\n";
		echo "<td colspan='4' style='font-family:Arial, Verdana, Times, Sans;font-size:10px;color:black;font-weight:bold'>\n";
		//echo "A peça $referencia necessita de autorização da Britânia para atendimento em garantia. Para liberação desta peça, favor enviar e-mail para <a href=\"mailto:assistenciatecnica@britania.com.br\">assistenciatecnica@britania.com.br</A>, informando a OS e a justificativa.";
		echo "A peça $referencia necessita de autorização da Britânia para atendimento em garantia.";
		echo "</td>\n";
		echo "</tr>\n";
	}
	
	if (@pg_num_rows ($res) == 1 ) {
		echo "<script language='JavaScript'>\n";
		echo "referencia.value='$peca_referencia';";
		echo " descricao.value='$peca_descricao';";
		if ($login_fabrica == 14 or $login_fabrica == 24 or $login_fabrica==5) {
			echo " posicao.value='$posicao';";
		}else{
			echo " preco.value='$preco';";
		}
		echo "window.close();";
		echo "</script>\n";
	}

}

echo "</table>\n";
?>

</body>
</html>
