<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';
include 'funcoes.php';

#include 'cabecalho_pop_pecas.php';
header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");

$ajax        = $_GET['ajax'];
$peca_pedido = $_GET["peca_pedido"];
$tipo_peca   = $_GET["tipo_peca"];

if (strlen($ajax) > 0) {
	include_once "../class/tdocs.class.php";

	$arquivo = $_GET['arquivo'];
	$idpeca = $_GET['idpeca'];
	$tDocs = new TDocs($con, $login_fabrica);
    $xpecas = $tDocs->getDocumentsByRef($idpeca, "peca");
    if (!empty($xpecas->attachListInfo)) {

		$a = 1;
		foreach ($xpecas->attachListInfo as $kFoto => $vFoto) {
		    $fotoPeca = $vFoto["link"];
		    if ($a == 1){break;}
		}
		echo "<table align='center'>";
			echo "<tr>";
				echo "<td align='right'><a href=\"javascript:escondePeca();\"><FONT size='1' color='#FFFFFF'><B>" .  traduz("FECHAR") . "</B></font></a></td>";
			echo "</tr>";
			echo "<tr>";
				echo "<td align='center'>";
					echo "<a href=\"javascript:escondePeca();\">";
						echo "<img src='$fotoPeca' border='0'>";
					echo "</a>";
				echo "</td>";
			echo "</tr>";
		echo "</table>";
    } else {

		echo "<table align='center'>";
			echo "<tr>";
				echo "<td align='right'><a href=\"javascript:escondePeca();\"><FONT size='1' color='#FFFFFF'><B>" .  traduz("FECHAR") . "</B></font></a></td>";
			echo "</tr>";
			echo "<tr>";
				echo "<td align='center'>";
					echo "<a href=\"javascript:escondePeca();\">";
						echo "<img src='../imagens_pecas/media/$arquivo' border='0'>";
					echo "</a>";
				echo "</td>";
			echo "</tr>";
		echo "</table>";
	}
	exit;

}

include 'cabecalho_pop_pecas.php';?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
	<title><?php echo traduz("Pesquisa Peças..."); ?></title>
	<meta http-equiv=pragma content=no-cache>
	<link href="css/estilo_cad_prod.css" rel="stylesheet" type="text/css" />
	<link href="css/posicionamento.css" rel="stylesheet" type="text/css" />
	<script type="text/javascript">
		function onoff(id) {
			var el = document.getElementById(id);
			el.style.display = (el.style.display=="") ? "none" : "";
		}

		function createRequestObject(){

			var request_;
			var browser = navigator.appName;

			if (browser == "Microsoft Internet Explorer") {
				 request_ = new ActiveXObject("Microsoft.XMLHTTP");
			} else {
				 request_ = new XMLHttpRequest();
			}

			return request_;

		}

		function escondePeca() {

			if (document.getElementById('div_peca')) {

				var style2 = document.getElementById('div_peca');
				if (style2 == false) return;
				if (style2.style.display=="block") {
					style2.style.display = "none";
				} else {
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

		function imprimePeca(arquivo,peca){

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
					campo.innerHTML = "<font size='1' face='verdana'><?php echo traduz("Aguarde.."); ?></font>";
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
			document.getElementById('div_peca').innerHTML = '';
		}

		Page.getPageCenterX = function() {
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
			font-size: 11px;
			border-collapse: collapse;
			border:1px solid #596d9b;
		}

		body {
			margin: 0px;
		}

	</style>
</head>

<body>
<br /><?php

$cond_parametros_adicionais = '';

if ($login_fabrica == 11) {
    $tipo_pedido_desc = (!empty($_GET['tipo_pedido'])) ? $_GET['tipo_pedido'] : '';
    $insumo = (!empty($_GET['insumo'])) ? $_GET['insumo'] : '';
    $posto_cnpj = (!empty($_GET['posto'])) ? $_GET['posto'] : '';

    if ($tipo_pedido_desc == 'Insumo' and $insumo == 'embalagens') {
        $nao_encontrou = false;

        if (empty($posto_cnpj)) {
            $nao_encontrou = true;
        } else {
            $cnpj = preg_replace(array('/\./', '/-/', '/\//'), '', $posto_cnpj);

            $sql_pa = "SELECT atendimento FROM tbl_posto_fabrica 
                JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto
                  AND tbl_posto_fabrica.fabrica = $login_fabrica
                  WHERE cnpj = '$cnpj'";
            $qry_pa = pg_query($con, $sql_pa);

            $atendimento = pg_fetch_result($qry_pa, 0, 'atendimento');

            if ($atendimento <> "t") {
                $nao_encontrou = true;
            }
        }

        if (true === $nao_encontrou) {
            echo "<h1>" . traduz("Peça '{$_GET["campo"]}' não encontrada") . "</h1>";
            echo "<script language='javascript'>";
            echo "setTimeout('window.close()',2500);";
            echo "</script>";
            exit;
        }

        $cond_parametros_adicionais = ' AND tbl_peca.parametros_adicionais LIKE \'%"embalagens":"t"%\' ';
    }
}

//6913254
if(in_array($login_fabrica,array(74,140)) AND $_GET['peca_pedido'] == "t"){
	$cond_ativo = " AND tbl_peca.ativo IS TRUE ";
}


$tipo = trim(strtolower($_GET['tipo']));
$tabela = trim(strtolower($_GET['tabela']));

if($login_fabrica == 1){
	$tabela = 1053;
}

if($login_fabrica == 50){
	$tipo_pedido = $_GET['tipo_pedido'];
	if(!empty($tipo_pedido)) {
		$cond_tipo_pedido = ($tipo_pedido == 129) ? " AND tbl_peca.devolucao_obrigatoria IS TRUE " : " AND tbl_peca.devolucao_obrigatoria IS NOT TRUE ";
	}
}
$prod_referencia = (!empty($_REQUEST['prod_referencia'])) ? trim($_REQUEST['prod_referencia']) : '';
$prod = trim($_GET['prod']);

if (!empty($prod_referencia) and trim($prod_referencia) <>'undefined'){
	$cond_1 = " AND tbl_produto.referencia = '$prod_referencia' " ;
}

if (strlen($prod) > 0) {
	$sql_prod   = " AND tbl_produto.referencia = '$prod'";
	$join_prod  = " JOIN tbl_lista_basica ON tbl_lista_basica.peca    = tbl_peca.peca ";
	$join_prod .= " JOIN tbl_produto      ON tbl_lista_basica.produto = tbl_produto.produto";
}

if ($login_fabrica == 91) {
    $sql_fora_linha  = ",tbl_peca_fora_linha.peca_fora_linha";
    $join_fora_linha = " LEFT JOIN tbl_peca_fora_linha ON(tbl_peca_fora_linha.peca = tbl_peca.peca AND tbl_peca_fora_linha.fabrica = $login_fabrica)";
    $where_fora_linha = ' AND tbl_peca_fora_linha.peca_fora_linha IS NULL';
}

if ($peca_pedido == "t") {
	$sql_depara_columns = ", tbl_depara.de, tbl_depara.para, tbl_depara.peca_de, tbl_depara.peca_para ";
	if($login_fabrica == 74) {
		$sql_depara_join = " LEFT JOIN tbl_depara ON tbl_depara.peca_de = tbl_peca.peca AND tbl_depara.fabrica = {$login_fabrica} AND  expira isnull ";
	}else{
		$sql_depara_join = " LEFT JOIN tbl_depara ON tbl_depara.peca_de = tbl_peca.peca AND tbl_depara.fabrica = {$login_fabrica} ";
	}
}

if ($tipo == "descricao" or $tipo == "descricao_pai") {
	 
	$descricao = trim(strtoupper($_GET["campo"]));
	//echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>descrição da peça</b>: <i>$descricao</i></font>";
	//echo "<p>";

	if ($tipo == "descricao_pai") {
		$sql_add = "AND tbl_peca.peca_pai is true";
	}

	 $sql = "SELECT  tbl_peca.peca,
                                        tbl_peca.referencia,
                                        tbl_peca.descricao,
                                        tbl_peca.ipi,
                                        tbl_peca.origem,
                                        tbl_peca.estoque,
                                        tbl_peca.unidade,
                                        tbl_peca.ativo,
                                        tbl_peca.referencia_fabrica,
                                        tbl_peca.parametros_adicionais
                                        {$sql_depara_columns}
                        FROM    tbl_peca
                        JOIN    tbl_fabrica ON tbl_fabrica.fabrica = tbl_peca.fabrica
                        $join_prod
                        {$sql_depara_join}
                        WHERE   tbl_peca.descricao ilike '%$descricao%'
                        AND     tbl_peca.fabrica = $login_fabrica
                        ".(($login_fabrica == 91 && $peca_pedido == "t") ? " AND tbl_peca.peca_critica is not true " : "")."
                        ".(($login_fabrica == 104 && $tipo_peca == "t") ? " AND tbl_peca.acessorio IS true " : " ")."
                        $sql_add
                        $sql_prod
			$cond_tipo_pedido
			$cond_ativo
            $cond_parametros_adicionais
                        ORDER BY tbl_peca.descricao;";

	$res = pg_query($con,$sql);

	if (pg_numrows ($res) == 0) {

        $sql = "SELECT  DISTINCT
                        tbl_peca.referencia,
                        tbl_peca.peca,
                        tbl_peca.descricao,
                        tbl_peca.ipi,
                        tbl_peca.origem,
                        tbl_peca.estoque,
                        tbl_peca.unidade,
                        tbl_peca.ativo,
                        tbl_peca.referencia_fabrica,
                        tbl_peca.parametros_adicionais
                        {$sql_depara_columns}
                FROM    tbl_peca
                JOIN    tbl_lista_basica using (peca)
                JOIN    tbl_produto ON tbl_produto.produto = tbl_lista_basica.produto and tbl_produto.fabrica_i = $login_fabrica
                        {$sql_depara_join}
                WHERE   tbl_lista_basica.fabrica = $login_fabrica
                AND     tbl_peca.descricao ilike '%$descricao%'
                ".(($login_fabrica == 91 && $peca_pedido == "t") ? " AND tbl_peca.peca_critica is not true " : "")."
                $cond_tipo_pedido
                $cond_1
                $cond_ativo
                $cond_parametros_adicionais
        ";

		$res = pg_query($con,$sql);

		if (pg_numrows ($res) == 0) {
			echo "<h1>Peça '$descricao' não encontrada</h1>";
			echo "<script language='javascript'>";
				echo "setTimeout('window.close()',2500);";
			echo "</script>";
			exit;
		}
	}

}

if ($tipo == "referencia" or $tipo == "referencia_pai") {
	

	if ($login_fabrica == 5) {

		if ($tipo == "referencia_pai") {
			$sql_add = "AND tbl_peca.peca_pai is true";
		}

	}

	$referencia = trim(strtoupper($_GET["campo"]));
	$referencia = str_replace(".","",$referencia);
	$referencia = str_replace("-","",$referencia);
	$referencia = str_replace("/","",$referencia);
	$referencia = str_replace(" ","",$referencia);

	//hd-3625122 - fputti
	$condReferenciaFabrica = "";
	if ($login_fabrica == 171) {
		$condReferenciaFabrica = " OR tbl_peca.referencia_fabrica ILIKE '%$referencia%'";
	}

	//echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>referência da peça</b>: <i>$referencia</i></font>";
	//echo "<p>";

	$sql = "SELECT  tbl_peca.peca,
                    tbl_peca.referencia,
                    tbl_peca.descricao,
                    tbl_peca.ipi,
                    tbl_peca.origem,
                    tbl_peca.estoque,
                    tbl_peca.unidade,
                    tbl_peca.ativo,
                    tbl_peca.ativo,
                    tbl_peca.referencia_fabrica,
                    tbl_peca.parametros_adicionais
                    {$sql_depara_columns}
                    {$sql_fora_linha}
            FROM    tbl_peca
            JOIN    tbl_fabrica ON tbl_fabrica.fabrica = tbl_peca.fabrica
            $join_prod
            {$join_fora_linha}
            {$sql_depara_join}
            WHERE   (tbl_peca.referencia ilike '%$referencia%' or tbl_peca.referencia_pesquisa ilike '%$referencia%' $condReferenciaFabrica)
            AND     tbl_peca.fabrica = $login_fabrica
            ".(($login_fabrica == 91 && $peca_pedido == "t") ? " AND tbl_peca.peca_critica is not true " : "")."
            ".(($login_fabrica == 104 && $tipo_peca == "t") ? " AND tbl_peca.acessorio IS true " : " ")."
            {$where_fora_linha}
            $sql_add
            $sql_prod
            $cond_tipo_pedido
            $cond_ativo
            $cond_parametros_adicionais
      ORDER BY      tbl_peca.descricao;";

 	//exit(nl2br($sql));
	$res = pg_query($con,$sql);

	if (@pg_numrows ($res) == 0) {
	
        $sql = "SELECT  DISTINCT
                        tbl_peca.referencia,
                        tbl_peca.peca,
                        tbl_peca.descricao,
                        tbl_peca.ipi,
                        tbl_peca.origem,
                        tbl_peca.estoque,
                        tbl_peca.unidade,
                        tbl_peca.ativo,
                        tbl_peca.referencia_fabrica,
                        tbl_peca.parametros_adicionais
                        {$sql_depara_columns}
                        {$sql_fora_linha}
                from tbl_peca
                JOIN tbl_lista_basica using (peca)
                JOIN tbl_produto ON tbl_produto.produto = tbl_lista_basica.produto and tbl_produto.fabrica_i = $login_fabrica
                {$sql_depara_join}
                {$join_fora_linha}
                where tbl_lista_basica.fabrica = $login_fabrica
                AND (tbl_peca.referencia ilike '%$referencia%' or tbl_peca.referencia_pesquisa ilike '%$referencia%')
                ".(($login_fabrica == 91 && $peca_pedido == "t") ? " AND tbl_peca.peca_critica is not true " : "")."
                ".(($login_fabrica == 104 && $tipo_peca == "t") ? " AND tbl_peca.acessorio IS true " : " ")."
                {$where_fora_linha}
                $cond_tipo_pedido
                $cond_1
                $cond_ativo
                $cond_parametros_adicionais
        ";
		$res = pg_query($con,$sql);

		if (@pg_numrows ($res) == 0) {

			echo "<h1>".traduz("Peça '$referencia' não encontrada") . " </h1>";
			echo "<script language='javascript'>";
				echo "setTimeout('window.close()',2500);";
			echo "</script>";
			exit;
		}
	}

}
if (pg_numrows ($res) == 1) {
	
	
	$peca       = trim(pg_result($res, 0, 'peca'));
	$referencia = trim(pg_result($res, 0, 'referencia'));
	$descricao  = trim(pg_result($res, 0, 'descricao'));
	$ipi        = trim(pg_result($res, 0, 'ipi'));
	$origem     = trim(pg_result($res, 0, 'origem'));
	$estoque    = trim(pg_result($res, 0, 'estoque'));
	$unidade    = trim(pg_result($res, 0, 'unidade'));
	$parametros_adicionais       = trim(pg_result($res, 0, 'parametros_adicionais'));
	$descricao     = str_replace('"','',$descricao);
	$descricao_lnk = str_replace("'", "\'", $descricao);
	if ($peca_pedido == "t") {
		$peca_para = pg_fetch_result($res, 0, "peca_para");

		if (strlen($peca_para) > 0) {
			 $sql_peca_para = "SELECT referencia, descricao FROM tbl_peca WHERE fabrica = {$login_fabrica} AND peca = {$peca_para}";
			 $res_peca_para = pg_query($con, $sql_peca_para);

			 $peca       = $peca_para;
			 $referencia = pg_fetch_result($res_peca_para, 0, "referencia");
			 $descricao_lnk  = str_replace("'", "\'", str_replace('"', '', pg_fetch_result($res_peca_para, 0, "descricao")));
		}
	}

	if($login_fabrica == 30 ){
		if(!empty($parametros_adicionais)){
			$parametros_adicionais = json_decode($parametros_adicionais);
			foreach ($parametros_adicionais AS $key => $value){
				$$key = $value;
			}
		}
	}else{
		$uso_interno = 't';
	}

	if(pg_fetch_result($res,0,'ativo') == 'f' and $uso_interno != 't'){
		echo "<h1>" . traduz("Peça '$referencia' inativa") . "</h1>";
		echo "<script language='javascript'>";
			echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}

    if ((strlen($peca) > 0) and (strlen($tabela) > 0)){
        $sql_preco = "SELECT preco from tbl_tabela_item where peca = $peca and tabela = $tabela";
        $res_preco = pg_query($con,$sql_preco);

        if (pg_num_rows($res_preco)>0){
            $preco = pg_fetch_result($res_preco, 0, 'preco');
            $preco = (in_array($login_fabrica, array(81,122,123,125,114,128)))?number_format($preco, 4, ',', ''):number_format($preco, 2, ',', '');

        }
    }
    if (!empty($prod_referencia)){
        $sql = "SELECT tbl_produto.produto
                from tbl_produto join tbl_linha using(linha)
                where tbl_linha.fabrica = $login_fabrica
                and tbl_produto.referencia = '$prod_referencia'";
    }


	echo "<script language='javascript'>";

	if ($_GET['forma'] == 'reload') {
		echo "opener.document.location = retorno + '?peca=$peca' ; this.close() ; " ;
	} else {
        if(!empty($tabela)) {

            if($login_fabrica == 88){

                $desconto = trim($_GET["desconto"]);

                if(strlen($desconto) > 0 && $desconto > 0){

                    $preco = str_replace(".", "", $preco);
                    $preco = str_replace(",", ".", $preco);

                    $preco = number_format($preco - (($preco / 100) * $desconto), 2, ",", ".");

                }

            }

            $preco_js = "if(typeof window.opener.preco != 'undefined'){window.opener.preco.value = '$preco' ;} ";
            $preco_js = ($login_fabrica == 42) ? "" : $preco_js;
        }
        echo "window.opener.peca_descricao.value = '$descricao_lnk' ; window.opener.peca_referencia.value = '$referencia'; $preco_js this.close() ";
// echo "console.log(window.opener);";
	}

	echo "</script>";
	exit;

}
//hd-3625122 - fputti
$referencia_fabrica_tl = "";
$referencia_fabrica = "";
$colspan = 6;
if ($login_fabrica == 171) {
	$referencia_fabrica_tl =  "<td>" . traduz("Referência Fábrica") . "</td>";
	$colspan = 8;
}

echo "<script language='JavaScript'>\n";
echo "<!--\n";
echo "this.focus();\n";
echo "// -->\n";
echo "</script>\n";

echo "<div id='div_peca' style='display:none; Position:absolute; border: 1px solid #949494;background-color: #b8b7af;width:410px; heigth:400px'>";
echo "</div>";

echo "<table width='100%' border='0' fabrica='$login_fabrica' class='tabela' cellspacing='1'>\n";
	//if ($ip == '192.168.0.66') echo $sql."<br />";
	if ($tipo == "descricao" or $tipo == "descricao_pai")
		echo "<tr class='titulo_tabela'><td colspan='$colspan'><font style='font-size:14px;'>" . traduz("Pesquisando por <b>descrição do produto</b>:") . "$descricao</b>: $nome</font></td></tr>";
	if ($tipo == "referencia" or $tipo == "referencia_pai")
		echo "<tr class='titulo_tabela'><td colspan='$colspan'><font style='font-size:14px;'>" . traduz("Pesquisando por <b>referência do produto</b>:") . "$referencia</font></td></tr>";

	echo "<tr class='titulo_coluna'>{$referencia_fabrica_tl}<td>" . traduz("Código") . "</td><td>" . traduz("Nome") . "</td><td>" .traduz("IPI") . "</td><td>". traduz("Origem") . "</td><td>" . traduz("Status") . "</td><td>" . traduz("Unidade") . "</td></tr>";

	//  Coloca num array todos os arquivos de paças, para não ter que ler o diretório a cada loop do for depois.

	// if ($login_fabrica != 3 or $login_fabrica == 11) {

	// 	$basedir   = "../imagens_pecas/$login_fabrica";
	// 	$thumb_dir = opendir("$basedir/pequena");
	// 	$a_thumb_dir= array();

	// 	while (false !== ($thumb_filename = readdir($thumb_dir))) {

	// 		if ($thumb_filename != "." && $thumb_filename != "..") {

	// 			$codigo_peca = substr($thumb_filename, 0, strrpos($thumb_filename, (strpos($thumb_filename,"-">0)?"-":".")));
	// 			$a_thumb_dir[$codigo_peca] = $thumb_filename;

	// 		}

	// 	}

	// 	closedir($thumb_dir);
	// 	unset($thumb_dir, $thumb_filename, $codigo_peca);    // $basedir vai usar depois...

	// }

	for ($i = 0; $i < pg_numrows($res); $i++) {
		

		$peca       = trim(pg_result($res, $i, 'peca'));
		$referencia = trim(pg_result($res, $i, 'referencia'));
		$xreferencia = $referencia;
		$descricao  = retira_acentos(trim(pg_result($res, $i, 'descricao')));
		$ipi        = trim(pg_result($res, $i, 'ipi'));
		$origem     = trim(pg_result($res, $i, 'origem'));
		$estoque    = trim(pg_result($res, $i, 'estoque'));
		$unidade    = trim(pg_result($res, $i, 'unidade'));
		$parametros_adicionais    = trim(pg_result($res, $i, 'parametros_adicionais'));
		$ativo      = pg_result($res, $i, 'ativo');
		$ativo      = ($ativo =='t') ? 'Ativo' : 'Inativo';

		$descricao = str_replace('"','',$descricao);
		$descricao_lnk = str_replace("'", "\'", $descricao);

		//hd-3625122 - fputti
		if ($login_fabrica == 171) {
			$referencia_fabrica = "<td>".trim(pg_result($res, $i, 'referencia_fabrica'))."</td>\n";
		}

		if ($peca_pedido == "t") {
			$peca_para = pg_fetch_result($res, $i, "peca_para");

			if (strlen($peca_para) > 0) {
                if ($login_fabrica == 91) {
                    $sql_peca_para = "SELECT tbl_peca.referencia, tbl_peca.descricao FROM tbl_peca LEFT JOIN tbl_peca_fora_linha ON(tbl_peca_fora_linha.peca = tbl_peca.peca) WHERE tbl_peca.fabrica = {$login_fabrica} AND tbl_peca.peca = {$peca_para} AND tbl_peca_fora_linha.peca_fora_linha IS NULL;";
                }else{
				    $sql_peca_para = "SELECT referencia, descricao FROM tbl_peca WHERE fabrica = {$login_fabrica} AND peca = {$peca_para}";
                }

				 $res_peca_para = pg_query($con, $sql_peca_para);
                if ($login_fabrica == 91 && pg_num_rows($res_peca_para) == 0)
                    continue;

				 $peca       = $peca_para;
				 $referencia = pg_fetch_result($res_peca_para, 0, "referencia");
				 $descricao_lnk  = str_replace("'", "\'", str_replace('"', '', pg_fetch_result($res_peca_para, 0, "descricao")));
			}
		}

		if ($i % 2 == 0) $cor = "#F7F5F0"; else $cor = "#F1F4FA";

        if ((strlen($peca) > 0) and (strlen($tabela) > 0)){
            $preco = "";
            $sql_preco = "SELECT preco from tbl_tabela_item where peca = $peca and tabela = $tabela";
            $res_preco = pg_query($con,$sql_preco);

            if (pg_num_rows($res_preco)>0){
                $preco = pg_fetch_result($res_preco, 0, 'preco');

                $preco = (in_array($login_fabrica, array(81,122,123,125,114,128)))?number_format($preco, 4, ',', ''):number_format($preco, 2, ',', '');

            }
        }

		if($login_fabrica == 30 ){
			if(!empty($parametros_adicionais)){
				$parametros_adicionais = json_decode($parametros_adicionais);
				foreach ($parametros_adicionais AS $key => $value){
					$$key = $value;
				}
			}
		}else{
			$uso_interno = 't';
		}

		if ($peca_pedido == "t") {
			$peca_para = pg_fetch_result($res, $i, "peca_para");

			if (strlen($peca_para) > 0) {
				$sql_peca_para = "SELECT referencia, descricao FROM tbl_peca WHERE fabrica = {$login_fabrica} AND peca = {$peca_para}";
				$res_peca_para = pg_query($con, $sql_peca_para);

				$xreferencia = $referencia_para = pg_fetch_result($res_peca_para, 0, "referencia");
				$descricao_lnk  = str_replace("'", "\'", str_replace('"','',pg_fetch_result($res_peca_para, 0, "descricao")));
			}
		}


		if($uso_interno == 't'){
			echo "<tr bgcolor='$cor'>\n";
				echo $referencia_fabrica;	
				echo "<td>\n";
				echo "$referencia\n";
				echo "</td>\n";

				echo "<td>\n";

				if ($_GET['forma'] == 'reload') {

					echo "<a href=\"javascript: opener.document.location = retorno + '?peca=$peca' ; this.close() ;\" > " ;

				} else {

                    if(!empty($tabela) && $login_fabrica != 42) {
                        $preco_js = "if(typeof window.opener.preco != 'undefined'){window.opener.preco.value = '$preco' ;} ";
                    }
                    echo "<a href=\"javascript: window.opener.peca_descricao.value = '{$descricao_lnk}' ; window.opener.peca_referencia.value = '{$xreferencia}'; $preco_js this.close() ; \" >";
				}

				echo "$descricao";
				echo "</a>";
				echo "</td>";

				echo "<td>";
				echo "$ipi";
				echo "</td>";

				echo "<td>";
				echo "$origem";
				echo "</td>";

				echo "<td>";
				echo "$ativo";
				echo "</td>";

				echo "<td>";
				echo "$unidade";
				echo "</td>";

				if ($peca_pedido == "t") {
					echo "<td>";
						if (strlen($peca_para) > 0) {
							echo traduz("Mudou Para:") . "<span style='color: red'>{$referencia_para}</span>";
                        }
					echo "</td>";
				}


				//HD 113942 - Adicionar Lenoxx às fábricas que tem a imagem das peças na lista básica (cadastro, pesquisa e consulta)
				if ($login_fabrica != 3 && $login_fabrica != 171 or $login_fabrica == 11) {  // Fábricas com imagens de peças
					echo "<td nowrap>";
						$tDocs = new TDocs($con, $login_fabrica);
					    $xpecas = $tDocs->getDocumentsByRef($peca, "peca");
					    if (!empty($xpecas->attachListInfo)) {

							$a = 1;
							foreach ($xpecas->attachListInfo as $kFoto => $vFoto) {
							    $fotoPeca = $vFoto["link"];
							    if ($a == 1){break;}
							}
									echo "<a href=\"javascript:mostraPeca('$fotoPeca','$peca')\">";
									echo "<img src='$fotoPeca' width='50' border='0'>";
									echo "</a>";
					    } else {
							foreach ($a_thumb_dir as $imagem) {
								if ($peca == substr($imagem,0,strlen($peca))) {
									echo "<a href=\"javascript:mostraPeca('$basedir/media/$imagem','$peca')\">";
									echo "<img src='$basedir/pequena/$imagem' border='0'>";
									echo "</a>";
								}
							}
						}
					echo "</td>\n";
				}

			echo "</tr>\n";
		}

	}

echo "</table>\n";?>

</body>
</html>
