<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_usuario.php';
$vet_ipi = array(94,101,104,105,106,115,116);
include_once "class/tdocs.class.php";
$tDocs = new TDocs($con, $login_fabrica);

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

/*HD 16027 Produto acabado, existia algumas selects sem a validação*/
//hd 202154 makita vai lancar pedido de produto acabado para postos tipo PATAM 236
$sql = "SELECT tipo_posto from tbl_posto_fabrica where fabrica = $login_fabrica and posto = $login_posto";
$res = pg_query($con, $sql);
if (pg_num_rows($res) > 0) {
	$tipo_posto = pg_fetch_result($res,0,0);
}

#include 'cabecalho_pop_pecas.php';
header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");

$linha_i = $_GET["linha_i"];

$apenas_consulta = $_GET['consulta'];
$ped_peca_garantia = $_GET['exibe'];
$peca_lib_garantia = 'f';

if (preg_match("/os_item_new.php/", "/".$ped_peca_garantia."/")) {
   $peca_lib_garantia = 't';
}

$caminho = "imagens_pecas";
if ($login_fabrica <> 10 and $login_fabrica <> 6 and $login_fabrica <> 19) {
	$caminho = $caminho."/".$login_fabrica;
}

$ajax           = $_GET['ajax'];
$ajax_kit       = $_GET['ajax_kit'];
$kit_peca_id    = $_GET['kit_peca_id'];
$kit_peca       = $_GET['kit_peca'];
$versao_produto = trim(strtoupper($_GET['versao_produto']));

$posto_checkbox = $_GET['posto_interno'];

if(strlen($_GET['input_posicao']) > 0){
    $input_posicao = $_GET['input_posicao'];
}else{
    $input_posicao = $_GET['linha_i'];
}

$mostra_item_aparencia = in_array($login_fabrica, array(101,104,105)) or $mallory_posto_TOP;

if (!empty($ajax_kit)) {

	$kit_linha = $_GET['kit_linha'];

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

		$resultado = "<table borde=2>";
		$resultado .="<tr><td colspan='100%'><input type='hidden' name='kit_$kit_peca' id='kit_$kit_peca' value='$kit_peca_id'></td></tr>";

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

		        foreach($retornoFor as $k => $v){
		            $optionFornecedor[$i] .= "<option value='".$v['retorno_fornecedor_peca']."'>".$v['retorno_fornecedor_peca_nome']."</option>";
		        }

				$selectFornecedor = "<td>
									<select name='kit_fornecedor_$peca' id='kit_fornecedor_$peca'>
										<option value='' selected>Selecione um fornecedor</option>
										{$optionFornecedor[$i]}
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
										".$optionDefeito."
									</select>
					                 </td>";
			}

			$resultado .=   "<tr style='font-size: 11px'>".
							"<td>".
                "<input type='".((in_array($login_fabrica,array(3,15,30,91))) ? 'hidden' : 'checkbox')."' name='kit_peca_$peca' value='$peca' CHECKED > ".
						    "<input type='text' name='kit_peca_qtde_$peca' id='kit_peca_qtde_$peca' size='5' value='$qtde_kit' onkeyup=\"re = /\D/g; this.value = this.value.replace(re, '');\" readonly='readonly'> x ".
							pg_fetch_result($res,$i,'referencia').
							"</td>".
							"<td> - ".
							pg_fetch_result($res,$i,'descricao').
							"</td>
	                        $selectFornecedor
							$selectDefeito

							</tr>";
		}
		$resultado .= "<tr><td><input type='button' name='remove_kit' value='X' onclick='limpa_kit($kit_linha)'></td></tr>";

		$resultado .= "</table>";

		echo "ok|$resultado";

	}

	exit;

}

if (strlen($ajax) > 0) {

	$arquivo = $_GET['arquivo'];
	$idpeca  = $_GET['idpeca'];

	echo "<table align='center'>
			<tr>
				<td align='right'>
					<a href='javascript:escondePeca();' style='font-size: 10px color:white;font-weight:bold'>FECHAR</a>
				</td>
			</tr>
			<tr>
				<td align='center'>";
	$xpecas  = $tDocs->getDocumentsByRef($idpeca, "peca");
	if (!empty($xpecas->attachListInfo)) {

		$a = 1;
		foreach ($xpecas->attachListInfo as $kFoto => $vFoto) {
		    $fotoPeca = $vFoto["link"];
		    if ($a == 1){break;}
		}
		echo "<a href=\"javascript:escondePeca();\">
		      <img src='$fotoPeca' border='0'>
		      </a>";
	} else {
		echo "<a href=\"javascript:escondePeca();\"><img src='$caminho/media/$arquivo' border='0'></a>";

	}
	echo "		</td>
			</tr>
		</table>
	";
	exit;

}

if($login_fabrica == 88 OR $login_fabrica == 104){
	$sql = "SELECT desconto FROM tbl_posto_fabrica WHERE posto = $login_posto AND fabrica = $login_fabrica";
	$res = pg_query($con,$sql);
	if(pg_num_rows($res) > 0){
		$desconto = pg_result($res,0,0);
	} else {
		$desconto = 0;
	}
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
		$where_masterfrio = " AND (tbl_peca_defeito_constatado.defeito_constatado IN($defeito_constatado) OR tbl_peca_defeito_constatado.defeito_constatado IS NULL)";
	}
	$cond_masterfrio  = "LEFT JOIN tbl_peca_defeito_constatado ON (tbl_peca_defeito_constatado.peca = tbl_peca.peca)	";

}



/* Comentado //hd_chamado=2881143
if ($login_fabrica == 19 && isset($_GET['defeito_constatado'])) {

	$id_defeitos = trim($_GET['defeito_constatado']);
	$id_defeitos = str_replace('\\','', $id_defeitos);
	$defeitos = $id_defeitos;
	$defeitos = json_decode($id_defeitos,true);

	$defeitos_lorenzetti = array();
	foreach ($defeitos as $value) {
		$defeitos_lorenzetti[] = "'$value'";
	}
	$defeitos_lorenzetti =  implode(',', $defeitos_lorenzetti);

	if ($defeitos_lorenzetti == '') {
		echo "<script>alert('Selecione Defeito Constatado'); setTimeout('self.close();',1000)</script>";
		exit;
	}

	$cond_lorenzetti = "
				JOIN tbl_peca_familia ON tbl_peca_familia.peca = tbl_peca.peca AND tbl_peca_familia.fabrica = $login_fabrica
				JOIN tbl_defeito_constatado_familia_peca ON tbl_defeito_constatado_familia_peca.familia_peca = tbl_peca_familia.familia_peca and tbl_defeito_constatado_familia_peca.fabrica = $login_fabrica
				";
	$where_lorenzetti = " AND tbl_defeito_constatado_familia_peca.defeito_constatado IN ($defeitos_lorenzetti) ";
}
Fim comentario //hd_chamado=2881143 */
$exibe_mensagem = 't';

if (strpos($_GET['exibe'],'pedido') !== false) $exibe_mensagem = 'f';

$faturado = $_GET['faturado']; # se for compra faturada, não precisa de validar.

if ($mostra_item_aparencia) {# verifica se posto pode ver pecas de itens de aparencia

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
	}

	$tabela = $login_fabrica == 101 && $faturado == 'sim' ? pg_fetch_result($res, 0, 'tabela_posto') : pg_fetch_result($res, 0, 'tabela');

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

	if (preg_match("/pedido_cadastro.php/", "/".$faz_pedido."/")) {
		$item_aparencia = 't';
	}

	#Fabio - HD 3921 - Para PA fazer pedido
	if (preg_match("/tabela_precos_tectoy.php/", "/".$faz_pedido."/")) {
		$item_aparencia = 't';
	}

} ?>

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

<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.core.min.js"></script>
<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.widget.min.js"></script>
<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.effect.min.js"></script>
<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.tabs.min.js"></script>

<?php if($login_fabrica == 94){ ?>
<script type="text/javascript">
	var pecas = [];
  function setPeca(referencia, descricao){
    var cont = 0;
    var status = "";
    while(cont < pecas.length){
      if(pecas[cont] == referencia+'|'+descricao){
        status = "existe";
      }
      cont++;
    }
    if(status != "existe"){
      pecas.push(referencia+'|'+descricao);
    }else{
      cont = 0;
      while(cont < pecas.length){
        if(pecas[cont] == referencia+'|'+descricao){
          pecas[cont] = "";
        }
        cont++;
      }
    }
  }

  function sendPecas(){
    window.opener.getPecas(pecas);
    window.close();
  }
</script>
<?php } ?>
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
		console.log(kit_peca_id);
    	console.log(kit_peca);
        console.log(i);
	var id_defeito = kit_peca.replace('kit_peca_', '');
	var login_fabrica = "<?=$login_fabrica?>";
    if(kit_peca == ""){
        kit_peca = "kit_peca_"+i;
    }
	$.ajax({
		type: 'GET',
		url: '<?=$PHP_SELF?>',
		data: 'kit_peca_id='+kit_peca_id+'&kit_peca='+kit_peca+'&ajax_kit=sim&kit_linha='+i+'',
		beforeSend: function(){
			window.opener.$('#'+kit_peca).html(' ');
		},
		complete: function(resposta) {

			resultado = resposta.responseText.split('|');

			if (resultado[0] == 'ok') {

				window.opener.$('#'+kit_peca).append(resultado[1]);

				if (login_fabrica == 91 || login_fabrica == 3 || login_fabrica == 30) {
					window.opener.$("input[name=kit_kit_peca_"+i+"]").val(kit_peca_id);
				}
                console.log(resultado[1]);
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

$tipo    = trim(strtolower($_GET['tipo']));
$produto = $_GET['produto'];

if (empty($produto) && strpos($exibe, "os_item_new.php")) {
	echo "<h1>Produto não especificado!</h1>";
	echo "<script language='javascript'>";
		echo "setTimeout('window.close()',10000);";
	echo "</script>";
	exit;
}

if ($login_fabrica == 30 && trim($_GET["popup"]) == "aviso_condicao") { /*HD - 4397677*/ ?>
	<div>
		<h2>Importante!</h2>
		<p style='text-align: left; padding: 3px !important;'>
			Para pedido de venda com a condição de pagamento <b>À VISTA</b> deve ser efetuado o depósito em uma das contas da Esmaltec no valor total do pedido e enviado o comprovante via e-mail para <u style='color: blue;'>sae@esmaltec.com.br</u>.
			<br><br>
			Qualquer dúvida entrar em contato através do Help Desk ou do e-mail <u style='color: blue;'>sae@esmaltec.com.br</u>
		</p>
	</div>
<?php	exit;
}

if (!strlen(trim($_GET["peca"])) && !strlen(trim($_GET["descricao"])) && empty($produto)) {
	echo "<h1>Informe a referência ou descrição!</h1>";
	echo "<script language='javascript'>";
		echo "setTimeout('window.close()',10000);";
	echo "</script>";
	exit;
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
		echo "<div style='font-size: 35px; color: #FF0000;'>Por favor, preencher primeiro o número de série do produto.</div>";
		echo "<script language='javascript'>";
			echo "setTimeout('window.close()',10000);";
		echo "</script>";
		exit;
	}

	/*
	   HD 882634 - Latinatec
	   Consultar serie_reoperado com data_abertura de 2 anos atrás.
	*/
	if( $login_fabrica == 15 and !empty($serie) ){

		$sql_serie = "SELECT
					      tbl_os.serie
					  FROM
					      tbl_os
					  JOIN tbl_fabrica
					      ON (
							     tbl_os.fabrica = tbl_fabrica.fabrica
						     )
					   WHERE tbl_os.fabrica           = ".$login_fabrica."
					       AND tbl_os.serie_reoperado = '".$serie."'   /* Exemplo pra testes: 1DAP55602 */
					       AND tbl_os.data_abertura > current_date - interval '2 years';";

		$res_serie = pg_query($con, $sql_serie);

		if( pg_num_rows($res_serie) > 0 ){

			$serie = pg_fetch_result($res_serie, 0, serie); /* sobrescrevendo $serie que veio por GET */
			$serie_reoperado = $serie; /* será utilizado no hidden do envio do formulário de gravação */

		}
	}
	/* HD 882634 - fim */

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

	function valida_kit_latinatec($serie,$in,$out) { //hd_chamado=2552862

		if (strlen(trim($serie)) < 3) return true;

		$serie_ok = false;
		$usar_kit_produto	= ($in != "" or $out != "");

		if (!$in and !$out) $serie_ok = true;

		if (!$serie_ok) {
			$in = (trim($in)	== "") ? " " : strtoupper($in);
			$out = (trim($out)	== "") ? "Z" : strtoupper($out);
			
			$l = substr($serie, 6, 1);
			$mes = substr($serie, 9, 1);
			$dia = substr($serie, 10, 2);
			
			if ( ($l == 'L' || $l == 'R') && ($mes >= 'A' && $mes <= 'L') && ($dia >= 1 && $dia <= 31) ) {
				$serie = substr($serie, -8);
			} else {
				$serie = $serie[1];
			}
			if (is_between(strtoupper($serie),$in, $out) or !$usar_kit_produto) {
				$serie_ok = true;
			}

		}

		return $serie_ok;

	}

}

if (strlen ($produto) > 0) {

	$produto_referencia = trim($_GET['produto']);

	if (!in_array($login_fabrica, [124])) {
		$produto_referencia = str_replace(["-"," ","/",".",","],"",$produto_referencia);
	}

	$voltagem = trim(strtoupper($_GET["voltagem"]));


	$sql = "SELECT tbl_produto.produto, tbl_produto.descricao,lista_troca
			FROM   tbl_produto
			JOIN   tbl_linha USING (linha)
			WHERE  tbl_produto.referencia_pesquisa = UPPER('$produto_referencia') ";

	if (strlen($voltagem) > 0 AND $login_fabrica == "1" ) $sql .= " AND UPPER(tbl_produto.voltagem) = UPPER('$voltagem') ";

	$sql .= "AND tbl_linha.fabrica = $login_fabrica ";

	if ($login_fabrica <> 3 and $login_fabrica <> 96) $sql .= " AND tbl_produto.ativo IS TRUE ";

	if($login_fabrica == 45){ $sql .= " ORDER BY tbl_produto.produto ASC "; }

	$res = pg_query($con,$sql);

	if (pg_num_rows($res) > 0) {

		$produto_descricao = pg_fetch_result($res, 0, 'descricao');
		$produto           = pg_fetch_result($res, 0, 'produto');
		$lista_troca       = pg_fetch_result($res, 0, 'lista_troca');
		$join_produto      = " JOIN tbl_lista_basica USING (peca) JOIN tbl_produto USING (produto)";
		$condicao_produto  = " AND tbl_produto.produto = $produto ";

		if ($login_fabrica == 96) {
			$join_produto      = " ";
			$condicao_produto  = " ";
		}

	} else {

		$produto = '';

	}

}
	$os = $_GET['os'];

	if (strlen($os) > 0) {

		$sql              = "SELECT serie, tipo_atendimento,produto from tbl_os where os = $os and fabrica = $login_fabrica";
		$res              = @pg_query($con,$sql);
		$serie            = @pg_fetch_result($res, 0, 'serie');
		$tipo_atendimento = @pg_fetch_result($res, 0, 'tipo_atendimento');
		$produto          = @pg_fetch_result($res, 0, 'produto');

		$condicao_produto  = " AND tbl_produto.produto = $produto ";
	}


if ($login_fabrica <> 3 && $login_fabrica <> 96 && !empty($produto)) $cond_produto = " AND tbl_produto.ativo IS TRUE " ;

echo "<div id='div_peca' style='display:none; Position:absolute; border: 1px solid #949494;background-color: #b8b7af;width:410px; heigth:400px'>";
echo "</div>";

if ($login_fabrica == 30) {
	$join_busca_referencia = 'LEFT JOIN tbl_esmaltec_referencia_antiga ON (tbl_peca.referencia = tbl_esmaltec_referencia_antiga.referencia) ';
}

$descricao  = trim(strtoupper($_GET["descricao"]));

$referencia = trim(strtoupper($_GET["peca"]));

if (!in_array($login_fabrica, [124])) {
	$referencia = str_replace(["-"," ","/",".",","],"",$referencia);
}

$posicao    = trim(strtoupper($_GET["posicao"]));
$posicao    = str_replace(".","",$posicao);
$posicao    = str_replace(",","",$posicao);
$posicao    = str_replace("-","",$posicao);
$posicao    = str_replace("/","",$posicao);
$posicao    = str_replace(" ","",$posicao);


//REMOVER 199776,199777 após testar pois são produtos do banco de teste
$vet_gar    = (in_array($produto, array(199776,199777,200253,200254)) && $login_fabrica == 42);//HD 400603

if ($tipo == 'tudo') {

	if ($sistema_lnigua == 'ES') echo "<h4>Buscando toda la lista básica de la herramienta: <br><i>$produto_referencia - $produto_descricao</i></h4>";
	else echo "<h4>Pesquisando toda a lista básica do produto: <br><i>$produto_referencia - $produto_descricao</i></h4>";
	echo "<br /><br />";

} else if ($tipo == 'descricao') {

	$texto = ($sistema_lingua == "ES") ? 'Buscando por el <b>nombre del repuesto</b>' : 'Pesquisando por <b>descrição da peça</b>';?>
	<p style='font-family:Arial, Verdana, Times, Sans;font-size: 12px'><?=$texto?><h4><i><?=$descricao?></i></h4></p><?php

} else if ($tipo == 'referencia') {

	echo "<p style='font-family:Arial, Verdana, Times, Sans;font-size:12px'>";
	echo ($sistema_lingua == "ES") ? "Buscando por <b>referencia</b>: " : "Pesquisando por <b>referência da peça</b>: ";
	echo "<i>$referencia</i></p>";
	echo "<br />";

} else if ($tipo == 'posicao') {

	if ($sistema_lingua == "ES") {
		echo "<p style='font-family:Arial, Verdana, Times, Sans;font-size:12px'>Buscando por <b>posición</b>: ";
	} else {
		echo "<p style='font-family:Arial, Verdana, Times, Sans;font-size:12px'>Pesquisando por <b>posição da peça</b>: ";
	}
	echo "<i>$posicao</i></p>";
	echo "<br />";

}

if ($login_fabrica == 30) {
	$or_busca_referencia = " OR tbl_esmaltec_referencia_antiga.referencia_antiga LIKE '%$referencia%' ";
}

if (in_array($login_fabrica,array(3,15,24,30,91))) {//HD 258901 - KIT
	if ($login_fabrica == 24 ) {

		$sql = " SELECT tbl_kit_peca.referencia,
						tbl_kit_peca.descricao,
						tbl_kit_peca.kit_peca
				FROM    tbl_kit_peca
				WHERE   tbl_kit_peca.fabrica = $login_fabrica ";

		if (!empty($produto)) {
			$sql .=	" AND tbl_kit_peca.produto = $produto";
		}

	} else if (in_array($login_fabrica,array(3,15,30,91))) {

		$sql = "SELECT tbl_kit_peca.referencia,
					   tbl_kit_peca.descricao,
					   tbl_kit_peca.kit_peca,
					   tbl_kit_peca_produto.serie_inicial,
					   tbl_kit_peca_produto.serie_final
				  FROM tbl_kit_peca
				  JOIN tbl_kit_peca_produto ON tbl_kit_peca_produto.kit_peca = tbl_kit_peca.kit_peca
				 WHERE tbl_kit_peca.fabrica = $login_fabrica ";

		if (!empty($produto)) {
			$sql .=	" AND tbl_kit_peca_produto.produto = $produto";
		}

	}

	if($login_fabrica <> 15){ //hd_chamado=2552862
		if (strlen($descricao) > 0)  $sql .= " AND UPPER(TRIM(tbl_kit_peca.descricao))  LIKE UPPER(TRIM('%$descricao%'))";
		if (strlen($referencia) > 0) $sql .= " AND UPPER(TRIM(tbl_kit_peca.referencia)) LIKE UPPER(TRIM('%$referencia%'))";
	}
	$sql .= " ORDER BY tbl_kit_peca.descricao ";
#echo nl2br($sql);exit;
	$res = pg_query($con,$sql);
	if (pg_num_rows($res) > 0) {
		$kit_peca_sim = "sim";
		echo "KIT de Peças";

        if($login_fabrica == 3){

            $kit_peca ='kit_peca_'.$linha_i;
        }
		echo "<table width='100%' border='1'>";

		for ($i = 0; $i < pg_num_rows($res); $i++) {

			$kit_peca_id    = pg_fetch_result($res, $i, 'kit_peca');
			$descricao_kit  = pg_fetch_result($res, $i, 'descricao');
			$referencia_kit = pg_fetch_result($res, $i, 'referencia');

			if($login_fabrica == 15){ //hd_chamado=2552862
				$serie_in = pg_fetch_result($res, $i, 'serie_inicial');
				$serie_out = pg_fetch_result($res, $i, 'serie_final');

				if (!valida_kit_latinatec($serie,$serie_in,$serie_out,$produto)) {
					continue;
				}
			}

			echo "<tr>";
				echo "<td>$referencia_kit</td>";
				echo "<td>";
					echo "<a href=\"javascript: ";
					echo " window.opener.referencia.value='$referencia_kit'; window.opener.descricao.value='$descricao_kit'; ";
					echo " window.opener.preco.value='';";
					echo "kitPeca('$kit_peca_id','$kit_peca','$input_posicao');";
            				if($login_fabrica == 91){
				                echo "window.opener.carregaFornecedor('$input_posicao','$referencia_kit','kit');";
				        }
					echo "\">$descricao_kit</a>";
				echo "</td>";
			echo "</tr>";

		}

		echo "</table>";
		echo "<br />";

	}

}

if($login_fabrica == 94 AND $posto_checkbox == 't'){
	$cond_produto = " AND tbl_produto.uso_interno_ativo is true ";
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
		$sql .= " AND (tbl_peca.referencia_pesquisa LIKE UPPER(TRIM('%$referencia%')) $or_busca_referencia )";
	}

	$sql .= " AND tbl_peca.acessorio is $not true";

	if ($login_fabrica == 42) {
		$sql .= " AND tbl_peca.produto_acabado is not true AND tbl_peca.ativo IS TRUE";
	}

} else {

	if(in_array($login_fabrica, array(11,172))){

		$cond_peca_bloqueda = " AND tbl_peca.bloqueada_garantia = false ";

	}

    $sql = "SELECT  DISTINCT
                    z.peca                       ,
                    z.referencia       AS peca_referencia ,
                    z.descricao        AS peca_descricao  ,
                    z.bloqueada_garantia                  ,";
	if($login_fabrica == 50){
        $sql .= "   z.devolucao_obrigatoria, ";
	}

	if($login_fabrica <> 140 OR ($login_fabrica == 140 && empty($referencia))){
		$sql .="
                    z.type                                ,
                    z.posicao                             ,
                    z.ordem                             ,";
	}
	$sql .="
                    z.peca_fora_linha                     ,
                    z.promocao_site                     ,
                    z.de                                  ,
                    z.para                                ,";

	if (strlen($produto) > 0 AND $login_fabrica <> 43 and $login_fabrica <> 96 and ($login_fabrica <> 140 OR ($login_fabrica == 140 && empty($referencia)))) {
		$sql .=	"	tbl_lbm.somente_kit                   ,";
	}

    $sql .= "       z.peca_para                           ,
                    z.libera_garantia                     , ";

	if ($login_fabrica == 15) {
		$sql .= "z.serie_inicial                               ,
		  	 z.serie_final	   			       ,
		  	 z.data_ativa	   			       ,
			 z.produto_serie_inicial ,
			 z.produto_serie_final   ,";
	}


	$sql .= "       tbl_peca.descricao AS para_descricao,
					z.peca_critica,
					z.troca_obrigatoria ";

	if($login_fabrica == 120 or $login_fabrica == 201){
		$sql .= ", tbl_estoque_posto.qtde AS qtde_estoque ";
	}
		$sql .="FROM (
					SELECT  y.peca               ,
						y.referencia         ,
						y.descricao          ,
						y.bloqueada_garantia ,";
	if($login_fabrica <> 140 OR ($login_fabrica == 140 && empty($referencia))){
		$sql .="
						y.type               ,
						y.ordem               ,
						y.posicao            ,";
	}

	if ($login_fabrica == 15) {
		$sql .= "y.serie_inicial             ,
			 y.serie_final	             ,
			 y.data_ativa	             ,
                         y.produto_serie_inicial ,
                         y.produto_serie_final   ,";

	}
	if($login_fabrica == 50){
		$sql .= " y.devolucao_obrigatoria, ";
	}

	$sql.= "			y.peca_fora_linha    ,
						y.promocao_site                     ,
					tbl_depara.de        ,
					tbl_depara.para      ,
					tbl_depara.peca_para,
					y.libera_garantia,
					y.peca_critica,
					y.troca_obrigatoria
					FROM (
							SELECT  x.peca                                      ,
								x.referencia                                ,
								x.descricao                                 ,
								x.bloqueada_garantia                        ,";
	if($login_fabrica <> 140 OR ($login_fabrica == 140 && empty($referencia))){
		$sql .="
								x.type                                      ,
								x.ordem                                      ,
								x.posicao                                   ,";
	}
	if ($login_fabrica == 15) {
		$sql .= "       x.serie_inicial                 ,
				x.serie_final			,
				x.data_ativa			,
                     		x.produto_serie_inicial ,
                         	x.produto_serie_final   ,";
	}

	if($login_fabrica == 50){
		$sql .= " x.devolucao_obrigatoria, ";
	}

	$sql .= "					tbl_peca_fora_linha.peca AS peca_fora_linha,
								x.promocao_site                     ,
							tbl_peca_fora_linha.libera_garantia,
							x.peca_critica,
							x.troca_obrigatoria
							FROM (
								SELECT  DISTINCT tbl_peca.peca				  ,
									tbl_peca.referencia			  ,
									tbl_peca.descricao			  ,
									tbl_peca.bloqueada_garantia	  ,";
	if($login_fabrica <> 140 OR ($login_fabrica == 140 && empty($referencia))){
	$sql .="
									tbl_lista_basica.type		  ,
									tbl_lista_basica.posicao      ,
									tbl_lista_basica.ordem      ,";
	}
	if($login_fabrica == 50){
		$sql .= " tbl_peca.devolucao_obrigatoria, ";
	}

	$sql .= "
									tbl_peca.promocao_site                     ,
									tbl_peca.peca_critica,
									tbl_peca.troca_obrigatoria ";
						if($login_fabrica == 15) {
							$sql .= " ,	tbl_lista_basica.serie_inicial,
										tbl_lista_basica.serie_final  ,
										tbl_lista_basica.data_ativa  ,
										tbl_produto.serie_inicial AS produto_serie_inicial ,
										tbl_produto.serie_final   AS produto_serie_final ";
						}
								$sql .= " FROM tbl_peca";
	if($login_fabrica == 19){ //hd_chamado=2881143
		$sql .= "$cond_lorenzetti $where_lorenzetti";
	}
	if($login_fabrica <> 140 OR ($login_fabrica == 140 && empty($referencia))){
		$sql .="
									LEFT JOIN tbl_lista_basica ON tbl_lista_basica.peca = tbl_peca.peca AND tbl_lista_basica.fabrica=$login_fabrica
									$join_busca_referencia
									$cond_masterfrio
									LEFT JOIN tbl_produto ON tbl_produto.produto=tbl_lista_basica.produto AND tbl_produto.fabrica_i=$login_fabrica";
	}
	if ($login_fabrica == 20 AND $login_pais <> 'BR') {
		$sql .= "JOIN tbl_tabela_item ON tbl_tabela_item.peca = tbl_peca.peca AND tabela = $tabela ";
	}

	/* HD 146619 - ALTERACAO 1 de 2 */
	if ($login_fabrica == 15) {
		$sql .= "LEFT JOIN tbl_depara ON tbl_lista_basica.peca = tbl_depara.peca_para
				 LEFT JOIN tbl_lista_basica AS tbl_lista_basica_de ON tbl_depara.peca_de = tbl_lista_basica_de.peca AND tbl_lista_basica.produto = tbl_lista_basica_de.produto";
	}

	if ($login_fabrica != 42) {
		$sql .= " WHERE tbl_peca.fabrica = $login_fabrica {$cond_peca_bloqueda} ";
	}

	if (!empty($produto) and $login_fabrica <> 96 and ($login_fabrica <> 140 OR ($login_fabrica == 140 && empty($referencia)))) {
		$sql .= " AND tbl_produto.produto = $produto";
	}

	if($login_fabrica <> 140 OR ($login_fabrica == 140 && empty($referencia))){
		$sql .= " 
				$cond_produto
				$where_masterfrio";
	}

	if ($login_fabrica == 42) {
		$sql .= " WHERE tbl_peca.fabrica = $login_fabrica {$cond_peca_bloqueda} ";
	}

	if ($tipo_posto <> 236) {//HD 202154 makita vai lancar pedido de produto acabado para postos tipo PATAM 236
		$sql .= " AND   tbl_peca.produto_acabado IS NOT TRUE ";
	}

    /*
     * - Próprio para EVEREST:
     * Mostrar para POSTO INTERNO APENAS
     * as peças com ITEM APARENCIA Marcada
     */
	if ($tipo_posto != 329 and $login_fabrica == 94 ) {
        $sql .= "   AND tbl_peca.item_aparencia IS NOT TRUE";
	}

	if ($login_fabrica == 14 or $login_fabrica == 50) {
		$sql .= " AND tbl_lista_basica.ativo IS NOT FALSE";
	}

	if ($login_fabrica == 20 and $produto == 20567) {
		$sql .= " AND tbl_peca.acessorio";
	}

	if (strlen($descricao) > 0) {

		if ($tipo == 'descricao') {
			$sql .= " AND UPPER(TRIM(tbl_peca.descricao)) LIKE UPPER(TRIM('%$descricao%'))";
		} else if ($tipo == 'tudo') {
			$sql .= " AND (UPPER(TRIM(tbl_peca.descricao))  LIKE UPPER(TRIM('%$descricao%')) OR
						   UPPER(TRIM(tbl_peca.referencia)) LIKE UPPER(TRIM('%$descricao%')))";
		}

	}

	if (strlen($referencia) > 0) {
		if($login_fabrica == 140 OR ($login_fabrica == 140 && empty($referencia))){
			$sql .= " AND (tbl_peca.referencia_pesquisa = UPPER(TRIM('$referencia')))";
		}else{
			$sql .= " AND (tbl_peca.referencia_pesquisa LIKE UPPER(TRIM('%$referencia%')) $or_busca_referencia )";
		}
	}

	if (strlen($posicao) > 0) {
		$sql .= " AND UPPER(TRIM(tbl_lista_basica.posicao)) ILIKE UPPER(TRIM('%$posicao%'))";
	}

	if ($login_fabrica == 2)    $sql .= " AND tbl_peca.bloqueada_venda IS FALSE";

	if ($login_fabrica == 15  && $login_posto == 393942) {

        $sql .= "";

	} else {

		if ($item_aparencia == 'f') $sql .= " AND tbl_peca.item_aparencia  IS FALSE";

	}



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

	if($login_fabrica == 120 or $login_fabrica == 201){
		$sql .= " LEFT JOIN tbl_estoque_posto ON tbl_estoque_posto.peca = z.peca AND posto = $login_posto ";
	}

	if (strlen($produto) > 0 AND $login_fabrica <> 43 and $login_fabrica <> 96 and ($login_fabrica <> 140 OR ($login_fabrica == 140 && empty($referencia)))) {

		if ($usa_versao_produto and strlen($versao_produto)) {
			$cond_versao = " AND (tbl_lbm.type IS NULL OR tbl_lbm.type = '$versao_produto')";
		}

		if ($login_fabrica == 42) {
			$sql .= " JOIN tbl_lista_basica AS tbl_lbm ON (((tbl_lbm.peca = z.peca_para) OR (tbl_lbm.peca = z.peca)) AND tbl_lbm.produto = $produto{$cond_versao})
				JOIN tbl_produto ON (tbl_produto.produto = tbl_lbm.produto AND tbl_produto.produto = $produto)
			";
		} else {
			$sql .= " JOIN tbl_lista_basica AS tbl_lbm ON (tbl_lbm.peca = z.peca AND tbl_lbm.produto = $produto{$cond_versao})
				JOIN tbl_produto ON (tbl_produto.produto = tbl_lbm.produto AND tbl_produto.produto = $produto)
			";
		}

		$sql .= ($login_fabrica == 45) ? " ORDER BY z.ordem ASC " : " ORDER BY z.descricao ";
	}

}
//echo "<pre>".print_r($sql, 1)."</pre>";exit;
$res = pg_query($con, $sql);

if (@pg_num_rows($res) == 0 and strlen($kit_peca_sim) == 0) {

	if ($login_fabrica == 1) {
		echo "<h2>Item '".($tipo == 'descricao' ? $descricao : $referencia)."' não existe <br> para o produto $produto_referencia, <br> consulte a vista explodida atualizada <br> e verifique o código correto</h2>";
	} else {

		if ($sistema_lingua == 'ES') echo "Repuesto '".($tipo == 'descricao' ? $descricao : $referencia)."' no encontrado <br>para el producto $produto_referencia";
		else                         echo "<h1>Peça '".($tipo == 'descricao' ? $descricao : $referencia)."' não encontrada<br>para o produto $produto_referencia</h1>";

	}
	echo "<script language='javascript'>";
		echo "setTimeout('window.close()',10000);";
	echo "</script>";
	exit;

} else if (@pg_num_rows ($res) == 0) {

	if (strlen($posicao) > 0) {

		if ($sistema_lingua == 'ES') echo "<h1>Repuesto '$posicao' no encontrado <br>para el producto $produto_referencia</h1>";
		else                         echo "<h1>Posição '$posicao' não encontrada<br>para o produto $produto_referencia</h1>";

	} else {

		if ($sistema_lingua == 'ES') echo "<h1>No consta lista básica para este producto</h1>";
		else 						 echo "<h1>Nenhuma lista básica de peças encontrada para este produto</h1>";

	}

	echo "<script language='javascript'>";
		echo "setTimeout('window.close()',10000);";
	echo "</script>";

	exit;

}

echo <<<JSFocus
<script language='JavaScript'>
  this.focus();
</script>
JSFocus;

$contador  = 999;

if (!is_numeric(pg_num_rows($res))) {
	echo <<<SemPECA
<h1>Peça não encontrada!</h1>
<script language='javascript'>
	setTimeout('window.close()',10000);
</script>
SemPECA;
	exit;
}

$num_pecas = pg_num_rows($res);
$gambiara  = 0;

if ($login_fabrica == 15) {
	$Xsql    = "SELECT familia FROM tbl_produto WHERE fabrica_i = $login_fabrica AND referencia = '$produto_referencia'";
	$Xres    = pg_query($con, $Xsql);

	$familia = pg_fetch_result($Xres, 0, "familia");
}

if ($login_fabrica == 120 or $login_fabrica == 201) $num_pecas_bloqueadas = 0;

for ($i = 0; $i < $num_pecas; $i++) {

	$peca_referencia       = trim(@pg_fetch_result($res, $i, 'peca_referencia'));
	$devolucao_obrigatoria = trim(@pg_fetch_result($res, $i, 'devolucao_obrigatoria'));
	$devolucao_obrigatoria = ($devolucao_obrigatoria == 't')? "sim" : 'nao';

	if ($login_fabrica == 30) {
		$sql_ref = "SELECT referencia_antiga FROM tbl_esmaltec_referencia_antiga WHERE referencia = '$peca_referencia'";
		$res_ref = @pg_query($con,$sql_ref);
		$referencia_antiga = trim(@pg_fetch_result($res_ref, 0, 'referencia_antiga'));
	}

	$peca				= trim(@pg_fetch_result($res, $i, 'peca'));
	if ($login_fabrica == 42) {
		if ($peca_antes == $peca) {
			continue;
		}
		$peca_antes = $peca;
	}
	$peca_descricao		= trim(@pg_fetch_result($res, $i, 'peca_descricao'));
	$peca_descricao_js	= strtr($peca_descricao, array('"'=>'&rdquo;', "'"=>'&rsquo;')); //07/05/2010 MLG - HD 235753
	$type				= trim(@pg_fetch_result($res, $i, 'type'));
	$posicao			= trim(@pg_fetch_result($res, $i, 'posicao'));
	$ordem				= trim(@pg_fetch_result($res, $i, 'ordem'));
	$somente_kit		= trim(@pg_fetch_result($res, $i, 'somente_kit'));//HD 335675
	$peca_fora_linha	= trim(@pg_fetch_result($res, $i, 'peca_fora_linha'));
	$peca_para			= trim(@pg_fetch_result($res, $i, 'peca_para'));
	$para				= trim(@pg_fetch_result($res, $i, 'para'));
	$garantia_para = "";

	if ($login_fabrica == 42 && !empty($peca_para)) {
		$peca_fora_linha = "";
	}

	if (pg_fetch_result($res, $i, 'de') != "" && $para != "") {
		$sql_garantia_para = "	SELECT bloqueada_garantia AS bloqueada_garantia_para 
								FROM tbl_peca 
								WHERE peca = $peca_para 
								AND fabrica = $login_fabrica";
		$res_garantia_para = pg_query($con, $sql_garantia_para);
		$garantia_para = pg_fetch_result($res_garantia_para, 0, 'bloqueada_garantia_para');
	}

	/*HD - 4292944*/
	if ($login_fabrica == 120 or $login_fabrica == 201) {
		$aux_sql = "SELECT serie, produto FROM tbl_os WHERE os = $os LIMIT 1";
		$aux_res = pg_query($con, $aux_sql);
		$ser_pro = (int) pg_fetch_result($aux_res, 0, 'serie');

		if (!empty($ser_pro) && $ser_pro > 0) {
			$aux_pro = pg_fetch_result($aux_res, 0, 'produto');

			$aux_sql = "SELECT serie_inicial, serie_final FROM tbl_lista_basica WHERE produto = $aux_pro AND peca = $peca AND fabrica = $login_fabrica";
			$aux_res = pg_query($con, $aux_sql);

			if (pg_num_rows($aux_res) > 0) {
				$serie_inicial = (int) pg_fetch_result($aux_res, 0, 'serie_inicial');
				$serie_final   = (int) pg_fetch_result($aux_res, 0, 'serie_final');

				if ($serie_final > 0) {
                    if (!($ser_pro >= $serie_inicial && $ser_pro <= $serie_final)) {
                        $num_pecas_bloqueadas ++;
                        $aux_msg[] = "A peça " .$_GET["peca"]. " está indisponível para o número de série $ser_pro";
                        continue;
                    }
                } else if ($serie_inicial > 0 && $serie_final <= 0) {
                    if (!($ser_pro >= $serie_inicial)) {
                        $num_pecas_bloqueadas ++;
                        $aux_msg[] = "A peça " .$_GET["peca"]. " está indisponível para o número de série $ser_pro";
                        continue;
                    }
                }
			}
		}
	}

	if($login_fabrica == 35){
		$po_peca = trim(@pg_fetch_result($res, $i, 'promocao_site'));
	}

	$para_descricao		= trim(@pg_fetch_result($res, $i, 'para_descricao'));
	$bloqueada_garantia	= trim(@pg_fetch_result($res, $i, 'bloqueada_garantia'));
	$libera_garantia    = trim(@pg_fetch_result($res, $i, 'libera_garantia'));


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

	if($login_fabrica == 120 or $login_fabrica == 201){
		$qtde_estoque    = trim(@pg_fetch_result($res, $i, 'qtde_estoque'));
	}

	if ($login_fabrica == 3) {
		$sqlPA = "SELECT parametros_adicionais FROM tbl_peca WHERE fabrica = $login_fabrica AND peca = $peca";
		$resPA = pg_query($con, $sqlPA);

		unset($parametros_adicionais);
		unset($qtde_fotos);
		unset($serial_lcd);

		if (pg_num_rows($resPA) > 0) {
			$parametros_adicionais = pg_fetch_result($resPA, 0, "parametros_adicionais");

			$json = json_decode($parametros_adicionais, true);

			if ($json["qtde_fotos"] > 0) {
				$qtde_fotos = $json["qtde_fotos"];
			} else {
				$qtde_fotos = 0;
			}

			if (strlen($json["serial_lcd"]) > 0) {
				$serial_lcd = $json["serial_lcd"];
			} else {
				$serial_lcd = "f";
			}
		}
	}

	//HD 189523 - MLG - Latinatec filtra a Lista Básica usando o 2º caractere do nº de série para controlar a versão
	if ($login_fabrica == 15 and $serie and $produto) {

        $l_serie_inicial = trim(pg_fetch_result($res, $i, 'serie_inicial'));
        $l_serie_final   = trim(pg_fetch_result($res, $i, 'serie_final'));
        $l_data_ativa    = trim(pg_fetch_result($res, $i, 'data_ativa'));
        $p_serie_inicial = trim(pg_fetch_result($res, $i, 'produto_serie_inicial'));
        $p_serie_final   = trim(pg_fetch_result($res, $i, 'produto_serie_final'));
		if (strlen($serie) < 20) {
		    if (!valida_serie_latinatec($serie,$p_serie_inicial,$p_serie_final,$l_serie_inicial,$l_serie_final)) {
		   		if ($gambiara == 0) {//HD 270590
					echo "Se não retornar nenhuma peça, provavelmente o número de série esteja errado ou a peça não pertence a este modelo de produto !";
					$gambiara = 1;
				}

				continue;
			}
		} else {
		
			$ns_referecia_produto  	= substr($serie, 0, 6);
			$ns_tipo_produto  		= substr($serie, 6, 1);
			$ns_ano_fabricacao 	= "20".substr($serie, 7, 2);
			$ns_mes_fabricacao 		= $mesNSLatina[substr($serie, 9, 1)];
			$ns_dia_fabricacao 		= substr($serie, 10, 2);
			$ns_data_fabricacao 	= trim("{$ns_ano_fabricacao}-{$ns_mes_fabricacao}-{$ns_dia_fabricacao}");
			$ns_quantidade      	= intval(substr($serie, 12, 8));

			if (!checaNSLatina()) {
				continue;
			}

		}

	}

	if ($login_fabrica == 15 and $peca == 1424754) {
		if ((!in_array($familia, array(3930, 3932)) or in_array($familia, array(3930, 3932))) and $defeito_constatado <> 20906 AND !in_array($produto,array(207157,207158,207159))) {
			continue;
		}
	}

	if ($login_fabrica == 15 and $peca == 1370632 and $defeito_constatado == 20906 and in_array($familia, array(3930, 3932))) {
	 	continue;
	}

	if ($login_fabrica == 15 and $peca == 1370632 and in_array($familia, array(3930, 3932)) and $defeito_constatado == 20906) {
		continue;
	}

	if($login_fabrica == 51){
		$peca_critica = trim(pg_fetch_result($res, $i, 'peca_critica'));
		$troca_obrigatoria = trim(pg_fetch_result($res, $i, 'troca_obrigatoria'));

		$tela = strpos($_SERVER['REQUEST_URI'],'pedido_cadastro.php');

		if(($peca_critica == "t" OR $troca_obrigatoria == "t") AND !empty($tela)){
			echo "<script>alert('Peça indisponível no momento, qualquer dúvida entrar em contato com o SAC 08007244262'); window.close();</script>";
			break;
		}
	}

	if($login_fabrica == 125){
		$peca_critica = trim(pg_fetch_result($res, $i, 'peca_critica'));
	}
	if(!empty($sistema_lingua)) {
		$sql_idioma = "SELECT descricao AS peca_descricao FROM tbl_peca_idioma WHERE peca = $peca AND upper(idioma) = '$sistema_lingua'";
		$res_idioma = @pg_query($con,$sql_idioma);

		if (@pg_num_rows($res_idioma) > 0) {
			$peca_descricao    = pg_fetch_result($res_idioma,0,peca_descricao);
			$peca_descricao_js = strtr($peca_descricao, array('"'=>'&rdquo;', "'"=>'&rsquo;'));  //07/05/2010 MLG - HD 235753
		}
	}
	$resT = @pg_query($con,"SELECT tabela FROM tbl_tabela WHERE fabrica = $login_fabrica AND tbl_tabela.ativa IS TRUE");

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

	if ($login_fabrica == 40 OR $login_fabrica == 115 OR $login_fabrica == 116) {

		$resT = pg_query($con,"SELECT tabela_posto as tabela FROM tbl_posto_linha JOIN tbl_linha using(linha) WHERE posto = $login_posto AND fabrica = $login_fabrica limit 1");

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

			if(!empty($tabela)) {
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
					$preco = (pg_num_rows($resT) >= 1) ? number_format(pg_fetch_result($resT,0,0),2,",",".") : '';
				}

			}
		} else {

			$preco = '';

		}

	}

	?>
	<style>
		table tr th{
			border: 1px solid #ccc;
			padding: 5px;
			background-color: #e6e6e6;
		}
		table tr td{
			border: 1px solid #ccc;
			padding: 5px;
		}
	</style>
	<?php

	if ($contador > 50) {
		if((!empty($apenas_consulta) && $primeira_vez == 0) || empty($apenas_consulta)){
			$primeira_vez = 1;
			$contador = 0 ;
			echo "</table><table width='97%' cellspacing='0px' cellpadding='0px' style='font: 13px arial; border: 1px solid #ccc;' align='center'> \n";
			if($login_fabrica == 45){
				echo "
					<tr>
						<th align='left'>Ordem</th>
						<th align='left'>Código</th>
						<th align='left'>Descrição</th>
						<th align='left'>De &raquo; Para</th>
						<th align='left'>Foto</th>
					</tr>
				";
			}
			flush();
		}
	}

	$contador++;
	$cor = (strlen($peca_fora_linha) > 0) ? '#FFEEEE' : '#ffffff';
	$id_click = "id='onclick_$i'";
	$texto_mudou = "";
	
	$pecaBloqueadaGarantia = false;
	if (($bloqueada_garantia == 't' || $garantia_para == 't') && ($login_fabrica == 15 && !in_array($login_posto, [343192, 118825, 393942]) || $login_fabrica <> 15)) {
		$cor = '#FFF6C1';
		$id_click = "";
		$texto_mudou = "<br /> <p style= 'color:#ff0000'>Peça bloqueada para garantia</p>";
		$pecaBloqueadaGarantia = true;
	} 

//	echo "<tr bgcolor='$cor' id='$produto'>\n";

echo "<tr bgcolor='$cor' id='teste_$i'>\n";


	if($login_fabrica == 45){
		echo "<td>".$ordem."</td>";
	}

	if ($login_fabrica == 30) {
		echo "<td><font size='1'>Ref. Ant.: $referencia_antiga</font></td>\n";
	}

	#if ($login_fabrica == 14 or $login_fabrica == 24 or $login_fabrica == 66) {
		echo "<td nowrap style='font-family:Arial, Verdana, Times, Sans;font-size:10px;color:black'>$posicao</td>\n";
	#}

	if ($login_fabrica == 3) {

		$sql  = "SELECT tbl_linha.codigo_linha FROM tbl_linha WHERE linha = (SELECT tbl_produto.linha FROM tbl_produto JOIN tbl_lista_basica ON tbl_produto.produto = tbl_lista_basica.produto WHERE tbl_lista_basica.peca = $peca LIMIT 1)";
		$resX = pg_query($con,$sql);
		$codigo_linha = @pg_fetch_result($resX, 0, 0);

		if (strlen ($codigo_linha) == 0) $codigo_linha = "&nbsp;";

		echo "<td nowrap style='font-family:Arial, Verdana, Times, Sans;font-size:10px;color:#999999'>$codigo_linha</td>\n";

	}

	echo "<td nowrap style='font-family:Arial, Verdana, Times, Sans;font-size:10px;color:black'>$peca_referencia</td>\n";
	echo "<td nowrap style='font-family:Arial, Verdana, Times, Sans;font-size:10px;color:black'>";

	if (strlen($peca_fora_linha) > 0 OR strlen($para) > 0 || $de_para_manual == true) {

		 if ($libera_garantia == 't' AND $peca_lib_garantia == 't') {

		 	echo '<a href="javascript: ' . "window.opener.referencia.value='$peca_referencia'; window.opener.descricao.value='$peca_descricao_js';window.opener.preco.value='$preco';";

			if (strlen($kit_peca) > 0) {
				echo " window.opener.$('#$kit_peca').html('');";
			}

			if (in_array($login_fabrica,array(3,30,91))) {
				echo "window.parent.$('input[name=kit_kit_peca_".$input_posicao."]').val('');";
				if($login_fabrica == 91){
                    echo "window.opener.carregaFornecedor('$input_posicao','$peca_referencia','peca');";
				}
			}
			if ($login_fabrica == 3) {
				echo "if(window.opener.qtde_fotos != undefined){window.opener.qtde_fotos.value = '$qtde_fotos';}";
				echo "if(window.opener.serial_lcd != undefined){window.opener.serial_lcd.value = '$serial_lcd';}";
				echo "window.opener.verificaPA($linha_i);";
			}
			echo ' window.close();"';

			echo " style='font-family:Arial, Verdana, Times, Sans-Serif;size:10px;color:blue'>$peca_descricao</a>$texto_mudou";

		} else {
			echo $peca_descricao.$texto_mudou;
		}

		if ($login_fabrica == 30 or $login_fabrica == 85) {//HD102404 waldir

			$sql_tabela  = "SELECT tabela
							FROM tbl_posto_linha
							JOIN tbl_linha ON tbl_linha.linha = tbl_posto_linha.linha AND tbl_linha.fabrica = $login_fabrica
							WHERE tbl_posto_linha.posto = $login_posto LIMIT 1;";

			$res_tabela  = @pg_query($con, $sql_tabela);
			$tabela      = trim(@pg_fetch_result($res_tabela, 0, 'tabela'));

			if ((strlen($tabela) > 0) and (strlen($peca_para) > 0)){
				$sql_preco = "SELECT preco FROM tbl_tabela_item WHERE peca = $peca_para AND tabela = $tabela";
				$res_preco = @pg_query($con, $sql_preco);
				$preco     = trim(@pg_fetch_result($res_preco, 0, 'preco'));
			}
		}

	}elseif($login_fabrica == 35 and $bloqueada_garantia == 't'){
		echo $peca_descricao;
	}else {
		//HD92435 - paulo
		if ($login_fabrica == 30 or $login_fabrica == 85) {

			$sql_tabela  = "SELECT tabela
							FROM tbl_posto_linha
							JOIN tbl_linha ON tbl_linha.linha = tbl_posto_linha.linha AND tbl_linha.fabrica = $login_fabrica
							WHERE tbl_posto_linha.posto = $login_posto LIMIT 1;";

			$res_tabela = @pg_query($con, $sql_tabela);
			$tabela     = trim(@pg_fetch_result($res_tabela, 0, 'tabela'));

			if ((strlen($tabela) > 0) and (strlen($peca) > 0)){
				$sql_preco = "SELECT preco FROM tbl_tabela_item WHERE peca = $peca AND tabela = $tabela";
				$res_preco = @pg_query($con, $sql_preco);
				$preco     = trim(@pg_fetch_result($res_preco, 0, 'preco'));
			}
		}

		if($login_fabrica == 30 && strlen($os) == 0 && ($peca == 1378800 OR $peca_referencia == "9850002216")){ //hd_chamado=2682154
            echo "<a href=\"javascript: alert('Peça não substituível. \\n \\r Por favor enviar laudo de troca ao Inspetor responsável para análise.')\" " . "style='font-family:Arial, Verdana, Times, Sans-Serif;size:10px;color:red'>$peca_descricao</a>";
            exit;
        }
		//somente para pedido de pecas faturadas
		if ($login_fabrica == 30 && strlen($preco) == 0 && strlen($os) == 0) {
			// HD-2306611 echo "<a href=\"javascript: alert('Peça Bloqueada \\n \\r Em caso de dúvidas referente a código de peças que o Sistema não está aceitando, favor entrar em contato com a Fábrica através do Fone: (85) 3299-8992 ou por e-mail: pedidos.at@esmaltec.com.br.')\" " . "style='font-family:Arial, Verdana, Times, Sans-Serif;size:10px;color:red'>$peca_descricao</a>";
			echo "<a href=\"javascript: alert('Peça Bloqueada \\n \\r Em caso de dúvidas referente a código de peças que o Sistema não está aceitando, favor entrar em contato com a Fábrica. E-mail: sae@esmaltec.com.br')\" " . "style='font-family:Arial, Verdana, Times, Sans-Serif;size:10px;color:red'>$peca_descricao</a>";
			exit;
		} else {

			if ($login_fabrica == 19 AND strlen($linha) > 0) {//HD 100696

				$caminho            = $caminho."/".$login_fabrica;
				$diretorio_verifica = $caminho."/pequena/";
				$peca_reposicao     = $_GET['peca_reposicao'];

				if ($peca_reposicao == 't') {

					echo "<a href='produto_pesquisa_lista.php?peca=$peca&tipo=referencia' " . "target='_blank' " . "style='font-family:Arial, Verdana, Times, Sans-Serif;size:10px;color:red'>" . $peca_descricao . "</a>";

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
										echo "<a href='$caminho/pequena/$img[0].pdf' target='_blank' " . "style='font-family:Arial, Verdana, Times, Sans-Serif;size:10px;color:red'>" .	$peca_descricao . "</a>";
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
											echo "<a href='$caminho/pequena/$img[0].pdf' target='_blank' " . "style='font-family:Arial, Verdana, Times, Sans-Serif;size:10px;color:red'>" . $peca_descricao . "</a>";
										}

									}

								}

							}

						}

					} else {
						echo "<span style='font-family:Arial, Verdana, Times, Sans-Serif;size:10px;color:red'>$peca_descricao</a>";
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
								$preco = $preco - ($preco * 0.45);
								$preco = number_format($preco,2,'.',',');
								$preco = str_replace('.',',',$preco);
							}

						}

					}

				}

				if($login_fabrica == 91){
					$nome_base = basename($_SERVER['HTTP_REFERER']);
					if(strstr($nome_base,'os_item_new.php') || strstr($exibe,'os_item_new.php')){
						$verifica_kit = "t";
					}else{
						$verifica_kit = "f";
					}
				}else{
					$verifica_kit = "t";
				}

				if($login_fabrica == 15){
					if (!valida_kit_latinatec($serie,$serie_in,$serie_out)) {
						$somente_kit = 'f';
					}
				}

			 	if ($somente_kit == 't' AND $verifica_kit == "t") {//HD 335675
					echo '<span';
					$color = 'black';
				} else {

					if($login_fabrica == 88 OR $login_fabrica == 104){
						if($login_fabrica == 104 AND $desconto == 0){
						  $sqlDesconto = "SELECT desconto
								  FROM tbl_posto_linha
								  JOIN tbl_tabela ON tbl_posto_linha.tabela = tbl_tabela.tabela AND tbl_tabela.fabrica = $login_fabrica
								  WHERE tbl_posto_linha.posto = $login_posto
								  AND tbl_posto_linha.tabela = $tabela
								  AND tbl_posto_linha.desconto > 0
								  LIMIT 1";
						  $resDesconto = pg_query($con,$sqlDesconto);
						  if(pg_num_rows($resDesconto) > 0){
						    $desconto = pg_result($resDesconto,0,'desconto');
						  }else{
						    $desconto = 0;
						  }
						}

						$preco = str_replace(",",".",$preco);
						$preco = $preco - ( ($preco * $desconto) /100 );
						$preco = number_format($preco,2,",",".");
					}
					if(empty($apenas_consulta)){
					echo '<a href="javascript: ';
					if($login_fabrica == 125 AND $peca_critica != "t"){
							echo "window.opener.imgPecasCriticas({$linha_i});";
						}
					if (in_array($login_fabrica, array(85)) && strstr($exibe,'os_cadastro.php')) {
						echo "window.opener.peca.value={$peca};window.opener.descricao_last.value='{$peca_descricao_js}';";
					}
					echo " window.opener.referencia.value='$peca_referencia';window.opener.descricao.value='$peca_descricao_js'; window.opener.referencia.setAttribute('rel', '$devolucao_obrigatoria'); ";
						
						if ($login_fabrica == 14 or $login_fabrica == 24 or $login_fabrica == 5) {
							if(strpos($_SERVER['REQUEST_URI'],'pedido_cadastro.php') === false){
								echo " window.opener.posicao.value='$posicao';";
							}
						} else {
							echo "window.opener.preco.value='$preco';";
						}
						/* HD 882634 */
						if( $login_fabrica == 15 ){
							echo "if(window.opener.serie_reoperado != undefined){window.opener.serie_reoperado.value='$serie_reoperado';}";
						}

						if( $login_fabrica == 120 or $login_fabrica == 201 ){
							echo "window.opener.qtde_estoque.value='$qtde_estoque';";
						}

						if ($login_fabrica == 30) {
							echo " if(window.opener.qtde ){ window.opener.qtde.value='1'; } ";
							echo 'window.opener.referencia.focus();';
						}
						if (strlen($kit_peca) > 0) {
							echo " window.opener.$('#$kit_peca').html(''); ";
						}

						if (in_array($login_fabrica,array(3,30,91))) {
							echo "window.parent.$('input[name=kit_kit_peca_".$input_posicao."]').val('');";
							if($login_fabrica == 91){
                                echo "window.opener.carregaFornecedor('$input_posicao','$peca_referencia','peca');";
                            }
						}

					if ($login_fabrica == 3) {
						echo "if(window.opener.qtde_fotos != undefined){window.opener.qtde_fotos.value = '$qtde_fotos';}";
                                		echo "if(window.opener.serial_lcd != undefined){window.opener.serial_lcd.value = '$serial_lcd';}";
						echo "window.opener.verificaPA($linha_i);";
					}

					if($login_fabrica == 35){
	                    echo " if(window.opener.tela_pedido == false){window.opener.verificaPOPeca('$po_peca', $linha_i);} ";
	                }

					echo 'window.close();"';
					$color = 'blue';
					}
				}
				if (!empty($apenas_consulta)) {
					echo "<a";
				}
				echo " style='font-family:Arial, Verdana, Times, Sans-Serif; size:10px; color:$color'>$peca_descricao.$texto_mudou";

			 	if ($somente_kit == 't') {//HD 335675
					echo '</span>';
				} else {
					echo '</a>';
				}

			}

		}

	}

	echo "</td>\n";

	if ($login_fabrica == 1) {
		echo "<td nowrap style='font-family:Arial, Verdana, Times, Sans-Serif;size:10px;color:black'>ds$type</td>\n";
	}

	$sqlX =	"SELECT   DISTINCT referencia, to_char(tbl_peca.previsao_entrega,'DD/MM/YYYY') AS previsao_entrega
				FROM  tbl_peca
				WHERE referencia_pesquisa = UPPER('$peca_referencia')
				AND   fabrica = $login_fabrica
				AND   previsao_entrega NOTNULL;";

	$resX = pg_query($con, $sqlX);

	if (pg_num_rows($resX) == 0) {

		echo "<td nowrap> ";
		if($login_fabrica == 35 and $bloqueada_garantia == 't'){
			echo "<font color='red'> Peça não atendida na garantia</font>";
		}

		if (strlen($peca_fora_linha) > 0) {

			echo "<span style='font-family:Arial, Verdana, Times, Sans-Serif;font-size:10px;color:black;'>";

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

			if (strlen($para) > 0 || $de_para_manual == true) {

				if ($login_fabrica == 30) {

					$sql_depara = "SELECT para 
									FROM tbl_depara
									WHERE fabrica = {$login_fabrica}
									AND peca_de = {$peca}";

					$res_depara = pg_query($con, $sql_depara);

					$para = pg_fetch_result($res_depara, 0, 'para');

					echo "A Peça mudou para&nbsp;<a href=\"javascript:";
					echo "window.opener.referencia.value='$para'; window.opener.descricao.value='$peca_descricao';";
					echo "window.opener.preco.value='$preco';";
					echo "if (window.opener.qtde) {window.opener.qtde.value='1';} ";
					echo "window.opener.$('#".$kit_peca."').html(''); window.close();";
					echo " \" style='font-family:Arial, Verdana, Times, Sans-Serif;size:10px;color:red'>$para</a>";

				} else {

					if (strlen($peca_descricao) > 0) {#HD 228968
						if(!empty($sistema_lingua)) {
							$peca_descricao  = preg_replace('/([\"|\'])/','\\$1',$peca_descricao);
							$sql_idioma = "SELECT tbl_peca_idioma.descricao AS peca_descricao FROM tbl_peca_idioma JOIN tbl_peca USING(peca) WHERE tbl_peca_idioma.descricao='$peca_descricao' AND upper(idioma)='$sistema_lingua'";

							$res_idioma = @pg_query($con, $sql_idioma);

							if (@pg_num_rows($res_idioma) > 0) {
								$peca_descricao  = preg_replace('/([\"|\'])/','\\$1',(pg_fetch_result($res_idioma, 0, 'peca_descricao')));
							}
						}
					}

					if (strlen($para_descricao) > 0) {
						$para_descricao  = preg_replace('/([\"|\'])/',"\\'",$para_descricao);
						if(!empty($sistema_lingua)) {
							$sql_idioma = "SELECT tbl_peca_idioma.descricao AS peca_descricao FROM tbl_peca_idioma JOIN tbl_peca USING(peca) WHERE tbl_peca_idioma.descricao='$para_descricao' AND upper(idioma)='$sistema_lingua'";

							$res_idioma = @pg_query($con, $sql_idioma);

							if (@pg_num_rows($res_idioma) > 0) {
								$para_descricao  = preg_replace('/([\"|\'])/','\\$1',(pg_fetch_result($res_idioma, 0, 'peca_descricao')));
							}
						}
					}

					if (in_array($login_fabrica,array(3,30,91))) {
						if (isset($produto)) {
                            if (strlen($produto)>0 ) {
                                $query_prod = " AND tbl_kit_peca_produto.produto = $produto ";
                                $query_prod2 = " AND tbl_lista_basica.produto     = $produto ";
                            }else{
                                $query_prod2 = "";
                                $query_prod = "";
                            }
                        }else{
                                $query_prod2 = "";
                                $query_prod = "";
                            }

						$sql_kit = "SELECT tbl_kit_peca.referencia,
									   tbl_kit_peca.descricao,
									   tbl_kit_peca.kit_peca
								  FROM tbl_kit_peca_peca
								  JOIN tbl_peca              ON tbl_peca.peca                 = tbl_kit_peca_peca.peca
								  JOIN tbl_kit_peca          ON tbl_kit_peca.kit_peca         = tbl_kit_peca_peca.kit_peca
								  JOIN tbl_kit_peca_produto  ON tbl_kit_peca_produto.kit_peca = tbl_kit_peca.kit_peca
								  JOIN tbl_lista_basica      ON tbl_lista_basica.peca         = tbl_peca.peca
								 WHERE tbl_kit_peca_produto.fabrica = $login_fabrica
								   $query_prod2
                                   $query_prod
								   AND tbl_peca.referencia          = '$para'
								   AND tbl_lista_basica.somente_kit is true;";

						$res_kit = pg_query($con, $sql_kit);
						$tot_kit = pg_num_rows($res_kit);
						if ($tot_kit > 0) {
							for ($yy = 0; $yy < $tot_kit; $yy++) {
								$kit_peca_kit = @pg_result($res_kit, $yy, 'kit_peca');
								$ref_kit      = @pg_result($res_kit, $yy, 'referencia');
								$des_kit      = @pg_result($res_kit, $yy, 'descricao');

								echo "<a href=\"javascript: ";
								echo " window.opener.referencia.value='$ref_kit'; window.opener.descricao.value=''; ";
								echo " window.opener.preco.value='';";
								echo "kitPeca('$kit_peca_kit','$kit_peca','$input_posicao');";
                                				if($login_fabrica == 91){
                                    					echo "window.opener.carregaFornecedor('$input_posicao','$ref_kit','kit')";
                                				}
								echo "\">Mudou para: $para - $des_kit</a><br />";
							}

						}else{

							echo "<span style='font-family:Arial, Verdana, Times, Sans-Serif;size:10px;color:black;font-weight:bold'>Mudou Para</span>";
							echo " <a href=\"javascript: ";
							echo " window.opener.referencia.value='$para'; window.opener.descricao.value='$para_descricao'; window.opener.preco.value='$preco'; ";

							if($login_fabrica == 125){
								echo "window.opener.peca_critica.value='$peca_critica';";
							}

							if ($login_fabrica == 30) {
								echo " if (window.opener.qtde) {window.opener.qtde.value='1';} ";
							}

							if (strlen($kit_peca) > 0) {
								echo "window.opener.$('#$kit_peca').html('');";
							}

							if (in_array($login_fabrica,array(3,30,91))) {
								echo "window.parent.$('input[name=kit_kit_peca_".$input_posicao."]').val('');";
								if($login_fabrica == 91){
                                    echo "window.opener.carregaFornecedor('$input_posicao','$peca_referencia','peca');";
                                }
							}

							if ($login_fabrica == 3) {
								echo "if(window.opener.qtde_fotos != undefined){window.opener.qtde_fotos.value = '$qtde_fotos';}";
				                                echo "if(window.opener.serial_lcd != undefined){window.opener.serial_lcd.value = '$serial_lcd';}";
								echo "window.opener.verificaPA($linha_i);";
							}
							echo " window.close();";
							echo "\"style='font-family:Arial, Verdana, Times, Sans-Serif;size:10px;color:red'>$para</a>";
						}

					}else{

						if ($de_para_manual == true) {
							$sql_de_para_manual = "SELECT referencia, descricao FROM tbl_peca WHERE fabrica = $login_fabrica AND peca = $de_para_manual_peca";
							$res_de_para_manual = pg_query($con, $sql_de_para_manual);

							$peca_descricao = pg_fetch_result($res_de_para_manual, 0, "descricao");
							$para      = pg_fetch_result($res_de_para_manual, 0, "referencia");
						}

						echo "<span style='font-family:Arial, Verdana, Times, Sans-Serif;size:10px;color:black;font-weight:bold'>Mudou Para</span>";
						if (empty($apenas_consulta) && $garantia_para != "t") {
						echo " <a href=\"javascript: ";
						echo " window.opener.referencia.value='$para'; window.opener.descricao.value='$para_descricao'; window.opener.preco.value='$preco'; ";

						if ($login_fabrica == 30) {
							echo " if (window.opener.qtde) {window.opener.qtde.value='1';} ";
						}

						if (strlen($kit_peca) > 0) {
							echo "window.opener.$('#$kit_peca').html('');";
						}

						if (in_array($login_fabrica,array(3,30,91))) {
							echo "window.parent.$('input[name=kit_kit_peca_".$input_posicao."]').val('');";
                            if($login_fabrica == 91){
                                echo "window.opener.carregaFornecedor('$input_posicao','$peca_referencia','peca');";
                            }
						}
						if ($login_fabrica == 3) {
							echo "if(window.opener.qtde_fotos != undefined){window.opener.qtde_fotos.value = '$qtde_fotos';}";
			                                echo "if(window.opener.serial_lcd != undefined){window.opener.serial_lcd.value = '$serial_lcd';}";
							echo "window.opener.verificaPA($linha_i);";
						}
						echo " window.close();";
						}else{
							echo " <a";
						}
						echo "\"style='font-family:Arial, Verdana, Times, Sans-Serif;size:10px;color:red'>$para</a>";
					}

				}

			} else {
				echo "&nbsp;";
			}

			if ($somente_kit == 't' AND $verifica_kit == "t") {//HD 335675

				$sql_kit = "SELECT tbl_kit_peca.referencia,
								   tbl_kit_peca.descricao,
								   tbl_kit_peca.kit_peca
							  FROM tbl_kit_peca_peca
							  JOIN tbl_peca              ON tbl_peca.peca                 = tbl_kit_peca_peca.peca
							  JOIN tbl_kit_peca          ON tbl_kit_peca.kit_peca         = tbl_kit_peca_peca.kit_peca AND tbl_kit_peca.fabrica = $login_fabrica
							  JOIN tbl_kit_peca_produto  ON tbl_kit_peca_produto.kit_peca = tbl_kit_peca.kit_peca
							  JOIN tbl_lista_basica      ON tbl_lista_basica.peca         = tbl_peca.peca AND tbl_lista_basica.fabrica = $login_fabrica
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
					echo " window.opener.referencia.value='$ref_kit'; window.opener.descricao.value=''; ";
					echo " window.opener.preco.value='';";
					echo "kitPeca('$kit_peca_kit','$kit_peca','$input_posicao'); ";
                    			if($login_fabrica == 91){
			                        echo "window.opener.carregaFornecedor('$input_posicao','$ref_kit','kit')";
					}
					echo "\">$des_kit</a><br />";
				}

			}

		}

		echo "</td>\n";
		echo "<td nowrap align='center'>";

		$diretorio_verifica = $caminho."/pequena/";



        $xpecas = $tDocs->getDocumentsByRef($peca, "peca");
        if (!empty($xpecas->attachListInfo)) {

          $a = 1;
          foreach ($xpecas->attachListInfo as $kFoto => $vFoto) {
              $fotoPeca = $vFoto["link"];
              if ($a == 1){break;}
          }
		echo "<a href=\"javascript:mostraPeca('$fotoPeca','$peca')\">";
		echo "<img src='$fotoPeca' border='0' height='200'>";
		echo "</a>";
        } else {

			if (is_dir($diretorio_verifica) == true) {

				if ($dh = opendir($caminho."/pequena/")) {

					$contador = 0;

					while (false !== ($filename = readdir($dh))) {

						if($contador == 1) break;

						if (strpos($filename, $peca) !== false) {

							$contador++;
							$po = strlen($peca);

							if (substr($filename, 0, $po) == $peca) {
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

			}else{
				if($login_fabrica == 45){
					echo "<em>Sem Imagem</em>";
				}
			}
		}

		echo "</td>\n";

		if(strlen($posto_checkbox) > 0 AND $login_fabrica == 94){
      if(strlen($para) > 0 ){
        $peca_referencia = $para;
      }else{
        $peca_referencia = $peca_referencia;
      }

      echo "<td align='center'>";
        echo "<input type='checkbox' onclick='setPeca(\"{$peca_referencia}\", \"{$peca_descricao}\")'>";
      echo "</td>";
    }

		if ($login_fabrica == 3 AND $peca == '526199') {
			echo "</tr>";
			echo "<tr>";
			echo "<td colspan='4'align='center'><img src='imagens_pecas/526199.gif' class='Div'>";
			echo "</td>\n";
		}

	} else {


		echo "<tr>\n";

		$peca_previsao      = pg_fetch_result($resX,0,0);
		$previsao_entrega   = pg_fetch_result($resX,0,1);
		$data_atual         = date("Ymd");
		$x_previsao_entrega = substr($previsao_entrega,6,4) . substr($previsao_entrega,3,2) . substr($previsao_entrega,0,2);

		echo "<td colspan='2' style='font-family:Arial, Verdana, Times, Sans;font-size:10px;color:black;font-weight:bold'>\n";

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

	if ($login_fabrica == 72) {
		echo (!$pecaBloqueadaGarantia) ? "<td><input type='checkbox' class='pecas_multiplas' data-referencia='{$peca_referencia}' data-descricao='{$peca_descricao}' data-preco='{$preco}' /></td>" : "<td></td>";
	}

	echo "</tr>\n";

	if ($exibe_mensagem == 't' AND $bloqueada_garantia == 't' and $login_fabrica == 3) {
		echo "<tr>\n";
		echo "<td colspan='4' style='font-family:Arial, Verdana, Times, Sans;font-size:10px;color:black;font-weight:bold'>\n";
		echo "A peça $referencia necessita de autorização da Britânia para atendimento em garantia.";
		echo "</td>\n";
		echo "</tr>\n";
	}

	

	if (@pg_num_rows ($res) == 1 && strlen($para) == 0) {
		if ($libera_garantia == 't' AND $peca_lib_garantia == 't') {
			echo "<script language='JavaScript'>\n";
			echo "window.opener.referencia.value='$peca_referencia';";
			echo "window.opener.descricao.value='$peca_descricao';";
			if ($login_fabrica == 14 or $login_fabrica == 24 or $login_fabrica == 5) {
				echo " window.opener.posicao.value='$posicao';";
			} else {
				echo " window.opener.preco.value='$preco';";
			}

			if ($login_fabrica == 3) {
				echo "if(window.opener.qtde_fotos != undefined){window.opener.qtde_fotos.value = '$qtde_fotos';}";
                                echo "if(window.opener.serial_lcd != undefined){window.opener.serial_lcd.value = '$serial_lcd';}";
				echo "window.opener.verificaPA($linha_i);";
			}

		    if($login_fabrica == 35){
                echo " if(window.opener.tela_pedido == false){window.opener.verificaPOPeca('$po_peca', $linha_i);} ";
            }

			echo "window.close();";
			echo "</script>\n";
		}
	}
}

/*HD - 4292944*/
if ($login_fabrica == 120 or $login_fabrica == 201) {
	if ($num_pecas_bloqueadas == $num_pecas) {
		echo "<h1>". implode("<br>", $aux_msg) ."</h1>";
	}
}

if(strlen($posto_checkbox) > 0 AND $login_fabrica == 94){
  echo "</tr>\n"; echo '<tr><td colspan="6" align="center"><input type="button" value="Cadastrar peças selecionadas" onclick="sendPecas()"></td></tr>';
}
echo "</table>\n";?>
<br /><br />
<input type="hidden" name="serie_reoperado" id="serie_reoperado" value="<?php echo $serie_reoperado ?>"><!-- HD 882634 -->
<?php
if (in_array($login_fabrica, [72])) {
?>
<div style="
	position:fixed;
	top: 90%;
	text-align: center;
	width: 100%;
	height: 15%;
	background-color: lightblue;
	border-top: gray 1px solid;
	padding-top: 8px;
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
		var linhai = <?= (empty($linha_i)) ? 0 : $linha_i ?>;

		$("#lancar_pecas_multiplas").click(function(){

			$(".pecas_multiplas:checked").each(function(){

				let referencia = $(this).data("referencia");
				let descricao  = $(this).data("descricao");

				$("#peca_"+linhai, window.opener.document).val(referencia);
				$("#descricao_"+linhai, window.opener.document).val(descricao);

				linhai += 1;

			});

			window.close();

		});
	<?php
	}
	?>
</script>

</body>
</html>

<?php
function checaNSLatina() {
	global $peca_referencia, $ns_quantidade, $l_serie_inicial, $l_serie_final, $l_data_ativa, $ns_data_fabricacao;

	if (is_between($ns_quantidade, $l_serie_inicial, $l_serie_final)  OR (empty($l_serie_inicial) && empty($l_serie_final)) ) {
		return true;
	} else {
		return false;
	}
}
// Fechando explicitamente a conexão com o BD
if (is_resource($con)) {
    pg_close($con);
}
?>
