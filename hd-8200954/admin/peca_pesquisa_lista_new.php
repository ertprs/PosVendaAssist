<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_admin.php";
include_once "../class/tdocs.class.php";
$tDocs = new TDocs($con, $login_fabrica);

	$caminho = "imagens_pecas";
	if($login_fabrica<>10){
	$caminho = $caminho."/".$login_fabrica;

	}

header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");
$ajax = $_GET['ajax'];
if(strlen($ajax)>0){

	$arquivo = $_GET['arquivo'];
	
	$idpeca = $_GET['idpeca'];
	$xpecas = $tDocs->getDocumentsByRef($idpeca, "peca");
	echo "<table align='center'>";
	echo "<tr>";
	echo "<td align='right'><a href=\"javascript:escondePeca();\"><FONT size='1' color='#FFFFFF'><B>FECHAR</B></font></a></td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td align='center'>";
	echo "<a href=\"javascript:escondePeca();\">";
	if (!empty($xpecas->attachListInfo)) {

		$a = 1;
		foreach ($xpecas->attachListInfo as $kFoto => $vFoto) {
		    $fotoPeca = $vFoto["link"];
		    if ($a == 1){break;}
		}
		echo "<img src='$fotoPeca' border='0'>";
	} else {
		echo "<img src='../$caminho/media/$arquivo' border='0'>";
	}
	echo "</a>";
	echo "</td>";
	echo "</tr>";
	echo "</table>";
	exit;

}
?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title> Pesquisa Peças pela Lista Básica ... </title>
<meta name="Author" content="">
<meta name="Keywords" content="">
<meta name="Description" content="">
<meta http-equiv=pragma content=no-cache>
<link href="css/estilo_cad_prod.css" rel="stylesheet" type="text/css">
<link href="css/posicionamento.css" rel="stylesheet" type="text/css">
<style type="text/css">
body {
	margin-left: 0px;
	margin-right: 0px;
}
</style>
<script>
function onoff(id) {
var el = document.getElementById(id);
el.style.display = (el.style.display=="") ? "none" : "";
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
//alert(arquivo);
var el = document.getElementById('div_peca');
	el.style.display = (el.style.display=="") ? "none" : "";
	imprimePeca(arquivo,peca);

}
var http3 = new Array();
function imprimePeca(arquivo,peca){

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
			campo.innerHTML = "<font size='1' face='verdana'>Aguarde..</font>";
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
</script>
</head>

<body onblur="setTimeout('window.close()',5500);">

<br>

<img src="imagens/pesquisa_pecas.gif">

<?
$tipo = trim (strtolower ($_GET['tipo']));

if (strlen($_GET['produto']) > 0) {
	$produto_referencia = trim($_GET['produto']);
	$produto_referencia = str_replace(".","",$produto_referencia);
	$produto_referencia = str_replace(",","",$produto_referencia);
	$produto_referencia = str_replace("-","",$produto_referencia);
	$produto_referencia = str_replace("/","",$produto_referencia);
	$produto_referencia = str_replace(" ","",$produto_referencia);

	$sql = "SELECT tbl_produto.produto, tbl_produto.descricao
			FROM   tbl_produto
			JOIN   tbl_linha USING (linha)
			WHERE  UPPER(tbl_produto.referencia_pesquisa) = UPPER('$produto_referencia') ";

	if (strlen(trim($_GET["voltagem"])) > 0) $sql .= " AND UPPER(tbl_produto.voltagem) = UPPER('".trim($_GET["voltagem"])."') ";

	$sql .=	" AND    tbl_linha.fabrica = $login_fabrica ";

	if($login_fabrica <> 3 ) $sql .=	"AND tbl_produto.ativo IS TRUE " ;

	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0) {
		$produto_descricao = pg_result ($res,0,descricao);
		$produto = pg_result ($res,0,produto);
	}else{
		$produto = '';
	}
}
/*HD: 79762 03/03/2009 DEIXAR APENAS A BUSCA PELA LISTA BÁSICA MESMO QUANDO O PRODUTOS ESTIVER INATIVO*/
$cond_produto =" 1=1 ";
if($login_fabrica <> 3 ) $cond_produto = " tbl_produto.ativo IS TRUE " ;

if ($tipo == "tudo") {
	$descricao = trim(strtoupper($_GET["descricao"]));
	
	echo "<BR><h4>Pesquisando toda a lista básica do produto: <br><i>$produto_referencia - $produto_descricao</i></h4>";

	echo "<br><br>";
	
	$res = pg_exec ($con,"SELECT COUNT(*) FROM tbl_lista_basica WHERE tbl_lista_basica.fabrica = $login_fabrica");
	$qtde = pg_result($res,0,0);
	
	if ($qtde > 0 AND strlen($produto) > 0) {
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
						tbl_peca.descricao AS para_descricao  ,
						z.libera_garantia
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
								tbl_depara.peca_para ,
								y.libera_garantia
						FROM (
								SELECT  x.peca                                      ,
										x.referencia                                ,
										x.descricao                                 ,
										x.bloqueada_garantia                        ,
										x.type                                      ,
										x.posicao                                   ,
										tbl_peca_fora_linha.peca AS peca_fora_linha ,
										tbl_peca_fora_linha.libera_garantia
								FROM (
										SELECT  tbl_peca.peca            ,
												tbl_peca.referencia      ,
												tbl_peca.descricao       ,
												tbl_peca.bloqueada_garantia,
												tbl_lista_basica.type    ,
												tbl_lista_basica.posicao 
										FROM tbl_peca
										JOIN tbl_lista_basica USING (peca)
										JOIN tbl_produto      USING (produto)
										WHERE tbl_peca.fabrica = $login_fabrica
										AND   tbl_produto.produto = $produto
										AND   tbl_peca.ativo IS TRUE
										AND   $cond_produto ";
		if (strlen($descricao) > 0) $sql .= " AND ( UPPER(TRIM(tbl_peca.descricao)) ILIKE UPPER(TRIM('%$descricao%')) OR UPPER(TRIM(tbl_peca.referencia)) ILIKE UPPER(TRIM('%$descricao%')) )";
		$sql .= "					) AS x
								LEFT JOIN tbl_peca_fora_linha ON tbl_peca_fora_linha.peca = x.peca
							) AS y
						LEFT JOIN tbl_depara ON tbl_depara.peca_de = y.peca
					) AS z
				LEFT JOIN tbl_peca ON tbl_peca.peca = z.peca_para AND tbl_peca.fabrica = $login_fabrica
				ORDER BY z.descricao";
	}else{
		$sql = "SELECT z.peca                                ,
						z.referencia       AS peca_referencia ,
						z.descricao        AS peca_descricao  ,
						z.bloqueada_garantia                  ,
						z.peca_fora_linha                     ,
						z.de                                  ,
						z.para                                ,
						z.peca_para                           ,
						tbl_peca.descricao AS para_descricao  ,
						z.libera_garantia
				FROM (
						SELECT  y.peca               ,
								y.referencia         ,
								y.descricao          ,
								y.bloqueada_garantia ,
								y.peca_fora_linha    ,
								tbl_depara.de        ,
								tbl_depara.para      ,
								tbl_depara.peca_para ,
								y.libera_garantia
						FROM (
								SELECT  x.peca                                      ,
										x.referencia                                ,
										x.descricao                                 ,
										x.bloqueada_garantia                        ,
										tbl_peca_fora_linha.peca AS peca_fora_linha ,
										tbl_peca_fora_linha.libera_garantia
								FROM (
										SELECT  tbl_peca.peca       ,
												tbl_peca.referencia ,
												tbl_peca.descricao  ,
												tbl_peca.bloqueada_garantia
										FROM tbl_peca
										WHERE fabrica = $login_fabrica
										AND ativo IS TRUE";
		if (strlen($descricao) > 0) $sql .= " AND ( UPPER(TRIM(descricao)) ILIKE UPPER(TRIM('%$descricao%')) OR UPPER(TRIM(referencia)) ILIKE UPPER(TRIM('%$descricao%')) )";
		$sql .= "					) AS x
								LEFT JOIN tbl_peca_fora_linha ON tbl_peca_fora_linha.peca = x.peca
							) AS y
						LEFT JOIN tbl_depara ON tbl_depara.peca_de = y.peca
					) AS z
				LEFT JOIN tbl_peca ON tbl_peca.peca = z.peca_para AND tbl_peca.fabrica = $login_fabrica
				ORDER BY z.descricao";
	}
	$res = pg_exec($con,$sql);
	
	if (@pg_numrows($res) == 0) {
		echo "<h1>Nenhuma lista básica de peças encontrada para este produto</h1>";
		echo "<script language='JavaScript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}
}

if ($tipo == "descricao") {
	$descricao = trim(strtoupper($_GET["descricao"]));

	echo "<BR><h4>Pesquisando por <b>descrição da peça</b>: <i>$descricao</i></h4>";
	echo "<p>";

	$res = pg_exec ($con,"SELECT COUNT(*) FROM tbl_lista_basica WHERE tbl_lista_basica.fabrica = $login_fabrica");
	$qtde = pg_result ($res,0,0);

	if ($qtde > 0 AND strlen($produto) > 0 ) {
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
						tbl_peca.descricao AS para_descricao  ,
						z.libera_garantia
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
								tbl_depara.peca_para ,
								y.libera_garantia
						FROM (
								SELECT  x.peca                                      ,
										x.referencia                                ,
										x.descricao                                 ,
										x.bloqueada_garantia                        ,
										x.type                                      ,
										x.posicao                                   ,
										tbl_peca_fora_linha.peca AS peca_fora_linha ,
										tbl_peca_fora_linha.libera_garantia
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
										AND   tbl_peca.ativo IS TRUE
										AND   $cond_produto ";
		if (strlen($descricao) > 0) $sql .= " AND UPPER(TRIM(tbl_peca.descricao)) ILIKE UPPER(TRIM('%$descricao%'))";
		$sql .= "					) AS x
								LEFT JOIN tbl_peca_fora_linha ON tbl_peca_fora_linha.peca = x.peca
							) AS y
						LEFT JOIN tbl_depara ON tbl_depara.peca_de = y.peca
					) AS z
				LEFT JOIN tbl_peca ON tbl_peca.peca = z.peca_para AND tbl_peca.fabrica = $login_fabrica
				ORDER BY z.descricao";
	}else{
		$sql =	"SELECT z.peca                                ,
						z.referencia       AS peca_referencia ,
						z.descricao        AS peca_descricao  ,
						z.bloqueada_garantia                  ,
						z.peca_fora_linha                     ,
						z.de                                  ,
						z.para                                ,
						z.peca_para                           ,
						tbl_peca.descricao AS para_descricao  ,
						z.libera_garantia
				FROM (
						SELECT  y.peca               ,
								y.referencia         ,
								y.descricao          ,
								y.bloqueada_garantia ,
								y.peca_fora_linha    ,
								tbl_depara.de        ,
								tbl_depara.para      ,
								tbl_depara.peca_para ,
								y.libera_garantia
						FROM (
								SELECT  x.peca                                      ,
										x.referencia                                ,
										x.descricao                                 ,
										x.bloqueada_garantia                        ,
										tbl_peca_fora_linha.peca AS peca_fora_linha ,
										tbl_peca_fora_linha.libera_garantia
								FROM (
										SELECT  tbl_peca.peca              ,
												tbl_peca.referencia        ,
												tbl_peca.descricao         ,
												tbl_peca.bloqueada_garantia
										FROM tbl_peca
										WHERE fabrica = $login_fabrica
										AND ativo IS TRUE";
		if (strlen($descricao) > 0) $sql .= " AND UPPER(TRIM(descricao)) ILIKE UPPER(TRIM('%$descricao%'))";
		$sql .= "					) AS x
								LEFT JOIN tbl_peca_fora_linha ON tbl_peca_fora_linha.peca = x.peca
							) AS y
						LEFT JOIN tbl_depara ON tbl_depara.peca_de = y.peca
					) AS z
				LEFT JOIN tbl_peca ON tbl_peca.peca = z.peca_para AND tbl_peca.fabrica = $login_fabrica
				ORDER BY z.descricao";
	}
	$res = pg_exec($con,$sql);
	
	if (@pg_numrows($res) == 0) {
		echo "<center>";
		if ($login_fabrica == 1) {
			echo "<h2>Item '$descricao' não existe <br> para o produto $produto_referencia, <br> consulte a vista explodida atualizada <br> e verifique o código correto</h2>";
		}else{
			echo "<h1>Peça '$descricao' não encontrada<br>para o produto $produto_referencia</h1>";
		}
		echo "</center>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}
}

//echo "QTD: ".strlen($qtde)." <br> Produto: <br>".$produto." e ".strlen($produto)."<br>"; exit;

if ($tipo == "referencia") {
	$referencia = trim(strtoupper($_GET["peca"]));
	$referencia = str_replace(".","",$referencia);
	$referencia = str_replace(",","",$referencia);
	$referencia = str_replace("-","",$referencia);
	$referencia = str_replace("/","",$referencia);
	$referencia = str_replace(" ","",$referencia);

	echo "<BR><font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>referência da peça</b>: <i>$referencia</i></font>";

	echo "<br><br>";

	$res = pg_exec ($con,"SELECT COUNT(*) FROM tbl_lista_basica WHERE tbl_lista_basica.fabrica = $login_fabrica");
	$qtde = pg_result($res,0,0);

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
						tbl_peca.descricao AS para_descricao  ,
						z.libera_garantia
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
								tbl_depara.peca_para ,
								y.libera_garantia
						FROM (
								SELECT  x.peca                                      ,
										x.referencia                                ,
										x.descricao                                 ,
										x.bloqueada_garantia                        ,
										x.type                                      ,
										x.posicao                                   ,
										tbl_peca_fora_linha.peca AS peca_fora_linha ,
										tbl_peca_fora_linha.libera_garantia
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
										AND   tbl_peca.ativo IS TRUE
										AND   $cond_produto ";
		if (strlen($referencia) > 0) $sql .= " AND UPPER(TRIM(tbl_peca.referencia_pesquisa)) ILIKE UPPER(TRIM('%$referencia%'))";
		$sql .= "					) AS x
								LEFT JOIN tbl_peca_fora_linha ON tbl_peca_fora_linha.peca = x.peca
							) AS y
						LEFT JOIN tbl_depara ON tbl_depara.peca_de = y.peca
					) AS z
				LEFT JOIN tbl_peca ON tbl_peca.peca = z.peca_para AND tbl_peca.fabrica = $login_fabrica
				ORDER BY";
				if($login_fabrica == 45)$sql .= " z.referencia,";//14613 25/2/2008
				$sql .= " z.descricao";
	}else{
		$sql =	"SELECT z.peca                                ,
						z.referencia       AS peca_referencia ,
						z.descricao        AS peca_descricao  ,
						z.bloqueada_garantia                  ,
						z.peca_fora_linha                     ,
						z.de                                  ,
						z.para                                ,
						z.peca_para                           ,
						tbl_peca.descricao AS para_descricao  ,
						z.libera_garantia
				FROM (
						SELECT  y.peca               ,
								y.referencia         ,
								y.descricao          ,
								y.bloqueada_garantia ,
								y.peca_fora_linha    ,
								tbl_depara.de        ,
								tbl_depara.para      ,
								tbl_depara.peca_para ,
								y.libera_garantia
						FROM (
								SELECT  x.peca                                      ,
										x.referencia                                ,
										x.descricao                                 ,
										x.bloqueada_garantia                        ,
										tbl_peca_fora_linha.peca AS peca_fora_linha ,
										tbl_peca_fora_linha.libera_garantia
								FROM (
										SELECT  tbl_peca.peca              ,
												tbl_peca.referencia        ,
												tbl_peca.descricao         ,
												tbl_peca.bloqueada_garantia
										FROM tbl_peca
										WHERE fabrica = $login_fabrica
										AND ativo IS TRUE";
		if (strlen($referencia) > 0) $sql .= " AND UPPER(TRIM(referencia_pesquisa)) ILIKE UPPER(TRIM('%$referencia%'))";
		$sql .= "					) AS x
								LEFT JOIN tbl_peca_fora_linha ON tbl_peca_fora_linha.peca = x.peca
							) AS y
						LEFT JOIN tbl_depara ON tbl_depara.peca_de = y.peca
					) AS z
				LEFT JOIN tbl_peca ON tbl_peca.peca = z.peca_para AND tbl_peca.fabrica = $login_fabrica
				ORDER BY";
				if($login_fabrica == 45)$sql .= " z.referencia,";//14613 25/2/2008
				$sql .= " z.descricao";
	}
	$res = pg_exec($con,$sql);
	
	if (@pg_numrows($res) == 0) {
		echo "<center>";
		if ($login_fabrica == 1) {
			echo "<h2>Item '$referencia' não existe <br> para o produto $produto_referencia, <br> consulte a vista explodida atualizada <br> e verifique o código correto</h2>";
		}else{
			echo "<h1>Peça '$referencia' não encontrada<br>para o produto $produto_referencia</h1>";
		}
		echo "</center>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}
}
 	echo "<div id='div_peca' style='display:none; Position:absolute; border: 1px solid #949494;background-color: #b8b7af;width:410px; heigth:400px'>";

 	echo "</div>";
echo "<script language='JavaScript'>\n";
echo "<!--\n";
echo "this.focus();\n";
echo "// -->\n";
echo "</script>\n";

echo "<table width='100%' border='1'>\n";

for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
	$peca                = trim(@pg_result($res,$i,peca));
	$peca_referencia     = trim(@pg_result($res,$i,peca_referencia));
	$peca_descricao      = trim(@pg_result($res,$i,peca_descricao));
	$peca_descricao      = str_replace('"','',$peca_descricao);
	$type                = trim(@pg_result($res,$i,type));
	$posicao             = trim(@pg_result($res,$i,posicao));
	$peca_fora_linha     = trim(@pg_result($res,$i,peca_fora_linha));
	$peca_para           = trim(@pg_result($res,$i,peca_para));
	$para                = trim(@pg_result($res,$i,para));
	$para_descricao      = trim(@pg_result($res,$i,para_descricao));
	$bloqueada_garantia  = trim(@pg_result($res,$i,bloqueada_garantia));
	$libera_garantia     = trim(@pg_result($res,$i,libera_garantia));

	$descricao = str_replace ('"','',$descricao);
	
	$resT = pg_exec($con,"SELECT tabela FROM tbl_tabela WHERE fabrica = $login_fabrica");
	if (pg_numrows($resT) == 1) {
		$tabela = pg_result ($resT,0,0);
		if (strlen($para) > 0) {
			$sqlT = "SELECT preco FROM tbl_tabela_item WHERE tabela = $tabela AND peca = $peca_para";
		}else{
			$sqlT = "SELECT preco FROM tbl_tabela_item WHERE tabela = $tabela AND peca = $peca";
		}
		$resT = pg_exec($con,$sqlT);
		if (pg_numrows($resT) == 1) {
			$preco = number_format (pg_result ($resT,0,0),2,",",".");
		}else{
			$preco = "";
		}
	}else{
		$preco = "";
	}
	
	echo "<tr>\n";

	if ($login_fabrica == 14) {
		echo "<td nowrap>";
		echo "<font face='Arial, Verdana, Times, Sans' size='1' color='#000000'>$posicao</font>";
		echo "</td>\n";
	}

	echo "<td nowrap>";
	echo "<font face='Arial, Verdana, Times, Sans' size='1' color='#000000'>$peca_referencia</font>";
	echo "</td>\n";

	echo "<td nowrap>";
	/*
	if (strlen($peca_fora_linha) > 0 OR strlen($para) > 0) {
		echo "<font face='Arial, Verdana, Times, Sans' size='1' color='#000000'>$peca_descricao</font>";
	}else{
		echo "<a href=\"javascript: referencia.value='$peca_referencia'; descricao.value='$peca_descricao';";
		if ($login_fabrica == 14) echo " posicao.value='$posicao';";
		else                      echo " preco.value='$preco';";
		if ($login_fabrica == 5) echo " qtde.focus();";
		echo " this.close();\"><font face='Arial, Verdana, Times, Sans' size='1' color='#0000FF'>$peca_descricao</font></a>";
	}*/
	if (strlen($peca_fora_linha) > 0 OR strlen($para) > 0) {
		if (strlen($para) > 0) {
			echo "<font face='Arial, Verdana, Times, Sans' size='1' color='#000000'>$peca_descricao</font>";
		}
		if (strlen($peca_fora_linha) > 0) {
			if($libera_garantia<>"t"){
				echo "<font face='Arial, Verdana, Times, Sans' size='1' color='#000000'>$peca_descricao</font>";
			}else{
				echo "<a href=\"javascript: referencia.value='$peca_referencia'; descricao.value='$peca_descricao';";
				if ($login_fabrica == 14) {
					echo " posicao.value='$posicao';";
				}else{
					echo " preco.value='$preco';";
				}
				if ($login_fabrica == 5) {
					echo " qtde.focus();";
				}
				echo " this.close();\"><font face='Arial, Verdana, Times, Sans' size='1' color='#0000FF'>$peca_descricao</font></a>";
			}
		}
	}else{
		echo "<a href=\"javascript: referencia.value='$peca_referencia'; descricao.value='$peca_descricao';";
		if ($login_fabrica == 14) {
			echo " posicao.value='$posicao';";
		}else{
			echo " preco.value='$preco';";
		}
		echo " this.close();\"><font face='Arial, Verdana, Times, Sans' size='1' color='#0000FF'>$peca_descricao</font></a>";
	}

	echo "</td>\n";

	if ($login_fabrica == 1) {
		echo "<td nowrap>";
		echo "<font face='Arial, Verdana, Times, Sans' size='1' color='#000000'>$type</font>";
		echo "</td>\n";
	}
	

	echo "<td nowrap>";
/*if ($handle = opendir('imagens_pecas/pequena/.')) {
			while (false !== ($file = readdir($handle))) {
				$contador++;
				if($contador == 1) break;
				$posicao = strpos($file, $peca_referencia);
				if ($file != "." && $file != ".." ) {
					?>
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
	$xpecas = $tDocs->getDocumentsByRef($peca, "peca");

	if (!empty($xpecas->attachListInfo)) {

		$a = 1;
		foreach ($xpecas->attachListInfo as $kFoto => $vFoto) {
		    $fotoPeca = $vFoto["link"];
		    if ($a == 1){break;}
		}
		echo "<a href=\"javascript:mostraPeca('$fotoPeca', '$peca')\">";
		echo "<img src='$fotoPeca' border='0'>";
		echo "</a>";
	} else {
		if ($dh = opendir("../".$caminho."/pequena/")) {
			$contador=0;
			while (false !== ($filename = readdir($dh))) {
				if($contador == 1) break;
				if (strpos($filename,$peca) !== false){
					$contador++;
					$po = strlen($peca);
					if(substr($filename, 0,$po)==$peca){
						echo "<a href=\"javascript:mostraPeca('$filename', '$peca')\">";
						echo "<img src='../$caminho/pequena/$filename' border='0'>";
						echo "</a>";
					}
				}
			}
			if($contador == 0){
				if ($dh = opendir("../".$caminho."/pequena/")) {
					$contador=0;
					while (false !== ($filename = readdir($dh))) {
						if($contador == 1) break;
						if (strpos($filename,$peca_referencia) !== false){
							$contador++;
							$po = strlen($peca_referencia);
							if(substr($filename, 0,$po)==$peca_referencia){
								echo "<a href=\"javascript:mostraPeca('$filename', '$peca')\">";
								echo "<img src='../$caminho/pequena/$filename' border='0'>";
								echo "</a>";
							}
						}
					}
				}
			}
		}
	}


	echo "</td>\n";




	$sqlX =	"SELECT referencia, to_char(tbl_peca.previsao_entrega,'DD/MM/YYYY') AS previsao_entrega
			FROM tbl_peca
			WHERE referencia_pesquisa = UPPER('$peca_referencia')
			AND   fabrica = $login_fabrica
			AND   previsao_entrega > current_date ; ";
//			AND   previsao_entrega > date(current_date + INTERVAL '20 days')
//modificado pois quando tem um depara ele não encontra.
	$resX = pg_exec($con,$sqlX);
	if (pg_numrows($resX) == 0) {
		echo "<td nowrap>";
		if (strlen($peca_fora_linha) > 0) {
			echo "<font face='Arial, Verdana, Times, Sans' size='1' color='#000000'><b>";
			
			if ($login_fabrica == 1)
                echo "É obsoleta, não é mais fornecida";
            else{
                if($login_fabrica==3 AND $libera_garantia=='t')
                    echo "Disponível somente para garantia.";
                else
                    echo "Fora de linha";
                
            }
			echo "</b></font>";
		}else{
			if (strlen($para) > 0) {
				echo "<font face='Arial, Verdana, Times, Sans' size='1' color='#000000'><b>Mudou Para:</b></font>";
				echo " <a href=\"javascript: referencia.value='$para'; descricao.value='$para_descricao'; preco.value='$preco'; this.close();\"><font face='Arial, Verdana, Times, Sans' size='1' color='#0000FF'>$para</font></a>";
			}else{
				echo "&nbsp;";
			}
		}
		echo "</td>\n";
	}else{
		echo "</tr>\n";
		echo "<tr>\n";
		$peca_previsao    = pg_result($resX,0,0);
		$previsao_entrega = pg_result($resX,0,1);

		$data_atual         = date("Ymd");
		$x_previsao_entrega = substr($previsao_entrega,6,4) . substr($previsao_entrega,3,2) . substr($previsao_entrega,0,2);
		echo "<td colspan='2'>\n";
		if ($data_atual < $x_previsao_entrega) {
			echo "<font face='Arial, Verdana, Times, Sans' size='1' color='#000000'><b>";
//			echo "Não há previsão de chegada da Peça Código $peca_previsao.<br>Favor encaminhar e-mail para <a href='mailto:assistenciatecnica@britania.com.br'>assistenciatecnica@britania.com.br</a>, informando o número da Ordem de Serviço e o código do Posto Autorizado.<br>Somente serão aceitas requisições via email! NÃO utilizar o 0800.";
			echo "Esta peça estará disponível em $previsao_entrega";
			echo "<br>";
			echo "Para as peças com prazo de fornecimento superior a 25 dias, a fábrica tomará as medidas necessárias para atendimento do consumidor";
			echo "</b></font>";
		}
		echo "</td>\n";
	}

	echo "</tr>\n";
}
echo "</table>\n";
?>

</body>
</html>
