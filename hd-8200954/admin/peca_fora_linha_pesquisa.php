<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';
include_once "../class/tdocs.class.php";
$tDocs = new TDocs($con, $login_fabrica);


header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");

	$caminho = "imagens_pecas";
	if( $login_fabrica<>10){
	$caminho = $caminho."/".$login_fabrica;

	}

$ajax = $_GET['ajax'];
if(strlen($ajax)>0){
	$arquivo = $_GET['arquivo'];
	$idpeca  = $_GET['idpeca'];
	echo "<table align='center'>";
	echo "<tr>";
	echo "<td align='right'><a href=\"javascript:escondePeca();\"><FONT size='1' color='#FFFFFF'><B>FECHAR</B></font></a></td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td align='center'>";
	echo "<a href=\"javascript:escondePeca();\">";
	$xpecas  = $tDocs->getDocumentsByRef($idpeca, "peca");
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
include 'cabecalho_pop_pecas_fora_linha.php';
?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title> Pesquisa Pe�as... </title>
<meta name="Author" content="">
<meta name="Keywords" content="">
<meta name="Description" content="">
<meta http-equiv=pragma content=no-cache>

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

<body>

<script language="JavaScript">
<!--
function retornox(peca, referencia, descricao) {
	opener.document.frm_peca_fora_linha.referencia.value = referencia;
	opener.document.frm_peca_fora_linha.descricao.value  = descricao;
	opener.document.frm_peca_fora_linha.descricao.focus()
	window.close();
}
// -->
</script>
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
	imprimePeca(arquivo, peca);

}
var http3 = new Array();
function imprimePeca(arquivo, peca){

	var curDateTime = new Date();
	http3[curDateTime] = createRequestObject();

	url = "<?$PHP_SELF?>?ajax=true&idpeca="+peca+"&arquivo="+ arquivo;
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

<br>

<?

$tipo = trim(strtolower($_GET['tipo']));

if (strlen(trim($_GET['campo'])) < 4) {
		echo "<h1>Favor digitar pelo menos 3 caracteres para pesquisa</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
}

if($tipo == "descricao"){
	$descricao = trim(strtoupper($_GET['campo']));

	//echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>descri��o da pe�a</b>: <i>$descricao</i></font>";
	//echo "<p>";

	$sql = "SELECT   *
			FROM     tbl_peca
			WHERE    trim(tbl_peca.descricao) ilike '%$descricao%'
			AND      tbl_peca.fabrica = $login_fabrica
			ORDER BY tbl_peca.descricao;";
	$res = pg_exec ($con,$sql);

	if (@pg_numrows ($res) == 0) {
		echo "<h1>Pe�a '$descricao' n�o encontrada</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}
}

if($tipo == "referencia"){
	$referencia = trim(strtoupper($_GET['campo']));

	//echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>refer�ncia da pe�a</b>: <i>$referencia</i></font>";
	//echo "<p>";

	$sql = "SELECT   *
			FROM     tbl_peca
			WHERE    trim(tbl_peca.referencia) ilike '%$referencia%'
			AND      tbl_peca.fabrica = $login_fabrica
			ORDER BY tbl_peca.descricao;";
	$res = pg_exec ($con,$sql);

	if (@pg_numrows ($res) == 0) {
		echo "<h1>Pe�a '$referencia' n�o encontrada</h1>";
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

	echo "<div id='div_peca' style='display:none; Position:absolute; border: 1px solid #949494;background-color: #b8b7af;width:410px; heigth:400px'>";

 	echo "</div>";

	echo "<table width='100%' border='0' cellspacing='1' class='tabela'>\n";
	if($tipo == "descricao" or $tipo == "descricao_pai")
		echo "<tr class='titulo_tabela'><td colspan='7'><font style='font-size:14px;'>Pesquisando por <b>Descri��o da Pe�a</b>: $descricao</font></td></tr>";
	if($tipo == "referencia" or $tipo == "referencia_pai")
		echo "<tr class='titulo_tabela'><td colspan='7'><font style='font-size:14px;'>Pesquisando por <b>Refer�ncia da Pe�a</b>: $referencia</font></td></tr>";
	echo "<tr class='titulo_coluna'><td>C�digo</td><td>Nome</td>";
	for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
		$peca       = trim(pg_result($res,$i,peca));
		$referencia = trim(pg_result($res,$i,referencia));
		$descricao  = trim(pg_result($res,$i,descricao));

		$descricao		= str_replace ('"','',$descricao);
		$referencia		= str_replace ('"','',$referencia);

		if($i%2==0) $cor = "#F7F5F0"; else $cor = "#F1F4FA";
	
		echo "<tr bgcolor='$cor'>\n";

		echo "<td>\n";
		echo "$referencia";
		echo "</td>\n";

		echo "<td>\n";
		if ($_GET['forma'] == 'reload') {
			echo "<a href=\"javascript: opener.document.location = retorno + '?peca_fora_linha=$peca' ;\" > " ;
		}else{
			echo "<a href=\"javascript: retornox('$peca', '$referencia', '$descricao') ; this.close() ; \" >";
		}

		echo "$descricao";
		echo "</a>\n";
		echo "</td>\n";

		if ($login_fabrica == 3 or $login_fabrica==35) {
			echo "<td nowrap>";

			$xpecas  = $tDocs->getDocumentsByRef($peca, "peca");
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

				$contador=0;
				if ($dh = opendir("../".$caminho."/pequena/")) {
					while (false !== ($filename = readdir($dh))) {
						if($contador == 1) break;
						$xpeca = $peca.'.';
						if (strpos($filename,$peca) !== false){
							$po = strlen($xpeca);
							if(substr($filename, 0,$po)==$xpeca){
								$contador++;
								echo "<a href=\"javascript:mostraPeca('$filename', '$peca')\">";
								echo "<img src='../$caminho/pequena/$filename' border='0'>";
								echo "</a>";
							}
						}
					}

					if($contador == 0){
						if ($dh = opendir("../".$caminho."/pequena/")) {
							$Xreferencia = str_replace(" ", "_",$referencia);
							while (false !== ($filename = readdir($dh))) {
								if($contador == 1) break;
								if (strpos($filename,$Xreferencia) !== false){

									//$peca_referencia = ntval($peca_referencia);
									$po = strlen($Xreferencia);
									if(substr($filename, 0,$po)==$Xreferencia){
										$contador++;
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
		}
		echo "</tr>\n";
	}
	echo "</table>\n";
//}
?>

</body>
</html>
