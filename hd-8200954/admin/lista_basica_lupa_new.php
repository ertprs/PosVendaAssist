<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";

if (preg_match("/\/admin\//", $_SERVER["PHP_SELF"])) {
	include 'autentica_admin.php';
	define('APPBACK', '../');
	$areaAdmin = true;
	include_once "../class/tdocs.class.php";
	$tDocs = new TDocs($con, $login_fabrica);
} else {
	define('APPBACK', '');
	include 'autentica_usuario.php';
	include_once "class/tdocs.class.php";
	$tDocs = new TDocs($con, $login_fabrica);
}

$os_id = $_REQUEST["os_id"];

global $fabrica_comunicado;
$fabrica_comunicado = $login_fabrica == 168 ? 151 : $login_fabrica;
$fabrica_comunicado = $login_fabrica == 172 ? 11 : $login_fabrica;

if (in_array($login_fabrica, array(178,183))){
	function get_servico_realizado($tipo_atendimento) {
	    global $con, $login_fabrica;
	    
	    $sql_tipo_atendimento = "
	    	SELECT tipo_atendimento 
	    	FROM tbl_tipo_atendimento 
	    	WHERE fabrica = $login_fabrica 
	    	AND fora_garantia IS TRUE 
	    	AND tipo_atendimento = $tipo_atendimento";
	    $res_tipo_atendimento = pg_query($con, $sql_tipo_atendimento);

	    if (pg_num_rows($res_tipo_atendimento) > 0){
	    	$cond_servico = "AND gera_pedido = 'f'";
	    }

	    $sql_sr = "
	        SELECT
	            servico_realizado,
	            descricao,
	            (CASE WHEN troca_de_peca IS TRUE THEN TRUE ELSE FALSE END) AS troca_de_peca,
	            (CASE WHEN gera_pedido   IS TRUE THEN TRUE ELSE FALSE END) AS gera_pedido,
	            (CASE WHEN peca_estoque  IS TRUE THEN TRUE ELSE FALSE END) AS peca_estoque
	        FROM tbl_servico_realizado
	        WHERE fabrica = $login_fabrica
	        AND ativo IS TRUE
	        $cond_servico
	        UNION SELECT null AS servico_realizado, ' ' AS descricao, false AS troca_de_peca, false AS gera_pedido, false AS peca_estoque
	        ORDER BY descricao;
	    ";
	    return pg_query($con,$sql_sr);
	}

	if ($login_fabrica == 183) {
		function get_defeito_peca (){
			global $con, $login_fabrica;

			$sql_df = "SELECT * FROM tbl_defeito WHERE fabrica = {$login_fabrica} AND ativo IS TRUE";
			return pg_query($con,$sql_df);
		}
	}

	if ($_REQUEST["page"]){//cadastro_os_revenda
		$page = $_REQUEST["page"];
	}

	if ($_REQUEST["info_pecas"]){//cadastro_os_revenda
		$info_pecas = $_REQUEST["info_pecas"];
		$info_pecas = json_decode($info_pecas, true);
	}

	if ($_REQUEST["tipo_atendimento"]){//cadastro_os_revenda
		$tipo_atendimento = $_REQUEST["tipo_atendimento"];
	}
}

if ($_REQUEST["posicao"]) {
	$posicao   = $_REQUEST["posicao"];
}
if ($_REQUEST["xxos"]) {
	$xxos   = $_REQUEST["xxos"];
}

if ($_REQUEST["preco_peca"]) {
	$preco_peca   = $_REQUEST["preco_peca"];
}

if ($_REQUEST["produto"]) {
	$produto   = $_REQUEST["produto"];
}

if ($_REQUEST["versao"]) {
	$versao   = $_REQUEST["versao"];
}

if ($_REQUEST["referencia_descricao"]) {
   $referencia_descricao  = $_REQUEST["referencia_descricao"];
}

if ($_REQUEST["familia"]) {
	$familia   = $_REQUEST["familia"];
}

if ($_REQUEST["subproduto"]) {
	$subproduto = $_REQUEST["subproduto"];
}

if ($_REQUEST["garantia"]) {
	$garantia  = $_REQUEST["garantia"];
}

if ($_REQUEST["pedido"]) {
	$pedido      = $_REQUEST["pedido"];
	$tabela      = $_REQUEST["tabela"];
	$tipo_pedido = $_REQUEST["tipo-pedido"];
}

if ($_REQUEST['orcamento']) {
	$orcamento = $_REQUEST['orcamento'];
}

if(empty($tipo_pedido)){
	$tipo_pedido = $_REQUEST["tipo_pedido"];
}

if($_REQUEST['revisao']) {
 	$revisao = $_REQUEST['revisao'];
}

if($_REQUEST['desconto']) {
 	$desconto = $_REQUEST['desconto'];
}

if ($_REQUEST['defeito_constatado']) {
	$defeito_constatado = $_REQUEST['defeito_constatado'];
}

if ($_REQUEST['vista_explodida']) {
	$vista_explodida = $_REQUEST['vista_explodida'];
	include_once APPBACK.'class/aws/s3_config.php';
	include_once APPBACK.'class/aws/anexaS3.class.php';
}

if (isset($_REQUEST['reposicao']) && $_REQUEST['reposicao'] == true) {
	$peca_reposicao = true;
}else{
	$peca_reposicao = false;
}

$posto = $_REQUEST["posto"];

if ($areaAdmin === true AND $login_fabrica == 183){
	$sql_tipo_posto = "
        SELECT 
            tbl_tipo_posto.codigo 
        FROM tbl_posto_fabrica 
        JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = {$login_fabrica}
        WHERE tbl_posto_fabrica.posto = {$posto}
        AND tbl_posto_fabrica.fabrica = {$login_fabrica}";
    $res_tipo_posto = pg_query($con, $sql_tipo_posto);
    
    if (pg_num_rows($res_tipo_posto) > 0){
        $login_tipo_posto_codigo = pg_fetch_result($res_tipo_posto, 0, "codigo");
    }
}

if ($_REQUEST["produto_referencia"] || $_REQUEST["produto_descricao"]) {
	$produto_referencia = utf8_decode($_REQUEST["produto_referencia"]);
	$produto_referencia_pesquisa = str_replace(array(".", ",", "-", "/", " "), "", $produto_referencia);
	$produto_descricao  = utf8_decode($_REQUEST["produto_descricao"]);

	if (!empty($produto_referencia)) {
		$whereProdutoAdc = "(UPPER(fn_retira_especiais(tbl_produto.referencia_pesquisa)) = UPPER(fn_retira_especiais('{$produto_referencia_pesquisa}')) or (tbl_produto.referencia = '{$produto_referencia}'))";
	} else if (!empty($produto_descricao)) {
		$whereProdutoAdc = "(UPPER(fn_retira_especiais(tbl_produto.descricao)) = UPPER('{$produto_descricao}') OR UPPER(fn_retira_especiais(tbl_produto.nome_comercial)) = UPPER('{$produto_descricao}'))";
	}

	if ($login_fabrica == 183 AND strlen(trim($tipo_pedido)) > 0){
		$cond_ativo = "";
	}else{
		$cond_ativo = " AND tbl_produto.ativo IS TRUE ";
	}

	$sql = "SELECT produto, referencia, descricao
			FROM tbl_produto
			JOIN tbl_linha ON tbl_produto.linha = tbl_linha.linha
			WHERE
			{$whereProdutoAdc}
			AND tbl_linha.fabrica     = {$login_fabrica}
			AND tbl_produto.fabrica_i = {$login_fabrica}
			$cond_ativo";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		$produto            = pg_fetch_result($res, 0, "produto");
		$produto_referencia = pg_fetch_result($res, 0, "referencia");
		$produto_descricao  = pg_fetch_result($res, 0, "descricao");
	} else {
		$msg_erro = "Produto {$produto_referencia} {$produto_descricao} não encontrado";
	}

	unset($whereProdutoAdc);
}

if (isset($_REQUEST['produto_serie'])) {
	$produto_serie = $_REQUEST['produto_serie'];
}

$condicaoItemAparencia = "";

if(in_array($login_fabrica, array(161)) && strlen($posto) > 0){
	$login_posto = $posto;
}

if(!empty($login_posto)){
	$condicaoItemAparencia = " tbl_posto.posto = $login_posto ";
}else {
	$posto_codigo = "";
	$posto_nome   = "";

	if(isset($_REQUEST['posto_codigo']) && isset($_REQUEST['posto_nome'])){
		$posto_codigo = $_REQUEST['posto_codigo'];
		$posto_nome   = $_REQUEST['posto_nome'];
	}

	if (!empty($posto_codigo) && !empty($posto_nome)) {
		$condicaoItemAparencia = " tbl_posto_fabrica.codigo_posto = '$posto_codigo' AND tbl_posto.nome = '$posto_nome' ";
	}

	if ($login_fabrica == 158 && !empty($posto)) {
		$condicaoItemAparencia = " tbl_posto_fabrica.posto = {$posto} ";
	}

	if(!empty($posto_codigo)) {
		$sqlP = "SELECT posto FROM tbl_posto_fabrica where fabrica = $login_fabrica and codigo_posto = '$posto_codigo' ";
		$resP = pg_query($con,$sqlP);
		if(pg_num_rows($resP) > 0) {
			$login_posto = pg_fetch_result($resP,0,'posto');
		}
	}
}

$coluna = "";
$join   = "";

if(in_array($login_fabrica, array(151))){
	$coluna = ", tbl_tipo_posto.tipo_revenda ";
	$join   = " JOIN tbl_tipo_posto USING(tipo_posto) ";
}

if (in_array($login_fabrica, array(158,161))) {
	$coluna .= ", tbl_tipo_posto.posto_interno";
	$join .= " JOIN tbl_tipo_posto USING(tipo_posto) ";
}

if(strlen($condicaoItemAparencia) > 0) {
	$sql = "SELECT  tbl_posto_fabrica.item_aparencia
			{$coluna}
		FROM tbl_posto
		JOIN tbl_posto_fabrica USING(posto)
		{$join}
		WHERE {$condicaoItemAparencia}
		AND tbl_posto_fabrica.fabrica = $login_fabrica";
	$res = pg_query($con,$sql);

	if (pg_num_rows ($res) > 0) {
		$item_aparencia = pg_fetch_result($res, 0, 'item_aparencia');

		if(in_array($login_fabrica, array(151))){
			$tipo_revenda = pg_fetch_result($res, 0, 'tipo_revenda');
		}

		if(in_array($login_fabrica, array(158,161))){
			$posto_interno = pg_fetch_result($res, 0, posto_interno);
		}
	}
}else{
	$item_aparencia = 'f';
}

$parametro = $_REQUEST["parametro"];
$valor     = trim($_REQUEST["valor"]);

//$valor     = utf8_decode(trim($_REQUEST["valor"]));

$campo     = trim($_REQUEST["campo"]);

$selecionaPecas = (!empty($produto) || $pedido == 't');

/*if ($login_fabrica == 175 AND !empty($produto_serie)){
	$ordem_producao = substr($produto_serie, 0, 6);
	$sql_producao = "
		SELECT xxx.* 
		FROM (
			SELECT DISTINCT ON(xx.ordem_producao) xx.ordem_producao::float, CASE WHEN xx.proxima_ordem IS NULL THEN float8'+infinity' ELSE xx.proxima_ordem - 1 END AS proxima_ordem
			FROM (
				SELECT x.ordem_producao, lt.ordem_producao::integer AS proxima_ordem FROM(
					SELECT DISTINCT ordem_producao::integer
					FROM tbl_lista_basica
					WHERE fabrica = {$login_fabrica}
					AND produto = {$produto}
					ORDER BY ordem_producao ASC
				) x
				LEFT JOIN tbl_lista_basica lt ON lt.ordem_producao::integer > x.ordem_producao AND lt.fabrica = {$login_fabrica} AND lt.produto = {$produto}
				ORDER BY x.ordem_producao ASC, proxima_ordem ASC
			) xx
			ORDER BY xx.ordem_producao, xx.proxima_ordem ASC
		) xxx
		WHERE '{$ordem_producao}'::float BETWEEN xxx.ordem_producao AND xxx.proxima_ordem
	";
	$res_producao = pg_query($con, $sql_producao);

	if (pg_num_rows($res_producao) > 0){
		$op_pesquisa = pg_fetch_result($res_producao, 0, 'ordem_producao');
		
		if (preg_match("/^0{1,}/", $ordem_producao, $zero_op)) {
			$op_pesquisa = $zero_op[0].$op_pesquisa;
		}
	}
}*/

if(in_array($login_fabrica, array(169,170)) && !empty($produto) && !empty($produto_serie)) {

	$sqlPst = "SELECT codigo_posto FROM tbl_posto_fabrica WHERE posto = {$login_posto} AND codigo_posto = UPPER('{$produto_serie}') AND fabrica = {$login_fabrica};";
	$resPst = pg_query($con, $sqlPst);

	if (!empty($produto_serie) && pg_num_rows($resPst) == 0) {

		$sql = "
			SELECT
				tbl_numero_serie.numero_serie,
				tbl_numero_serie.serie,
				tbl_produto.referencia
			FROM tbl_numero_serie
			JOIN tbl_produto ON tbl_produto.produto = tbl_numero_serie.produto AND tbl_produto.fabrica_i = {$login_fabrica}
			LEFT JOIN tbl_numero_serie_peca ON tbl_numero_serie_peca.numero_serie = tbl_numero_serie.numero_serie AND tbl_numero_serie_peca.fabrica = {$login_fabrica}
			WHERE tbl_numero_serie.fabrica = {$login_fabrica}
			AND tbl_numero_serie.serie = UPPER('{$produto_serie}')
			AND tbl_numero_serie.produto = {$produto}
			AND tbl_produto.linha NOT IN (1059,1060);
		";

		$res = pg_query($con,$sql);

		if (pg_num_rows($res) > 0) {

			$referencia = str_replace('YY', '-', pg_fetch_result($res,0,'referencia'));
			$numero_serie = pg_fetch_result($res,0,'numero_serie');

			if ($serverEnvironment == 'development') {
				$urlWSDL = "http://ws.carrieronline.com.br/qa6/PSA_WebService/telecontrol.asmx?WSDL";
			} else {
				#$urlWSDL = "http://ws.carrieronline.com.br/wsPSATeleControl/telecontrol.asmx?WSDL";
				$urlWSDL = "http://ws.carrieronline.com.br/wsPSATeleControl/PSA.asmx?WSDL";
			}

			$client = new SoapClient($urlWSDL);
		
			/*$params = new SoapVar(
				"<ns1:oXml>
					<Z_CB_TC_PECAS_SUBSTITUICAO xmlns='http://ws.carrieronline.com.br/PSA_WebService'>
						<PV_MATNR>{$referencia}</PV_MATNR>
						<SERIES>
							<PT_SERNR>{$produto_serie}</PT_SERNR>
						</SERIES>
					</Z_CB_TC_PECAS_SUBSTITUICAO>
				</ns1:oXml>", XSD_ANYXML
			);
		
			$request   = array('oXml' => $params);
			$result    = $client->Z_CB_TC_PECAS_SUBSTITUICAO($request);

			$dados_xml = $result->Z_CB_TC_PECAS_SUBSTITUICAOResult->any;*/

			$params = new SoapVar(
				"<ns1:xmlDoc><criterios><PV_MATNR>{$referencia}</PV_MATNR><PV_SERNR>{$produto_serie}</PV_SERNR ></criterios></ns1:xmlDoc>", XSD_ANYXML
			);

			$request   = array('xmlDoc' => $params);
			$result    = $client->PesquisaSubstituicao($request);

			$dados_xml = $result->PesquisaSubstituicaoResult->any;
			$xml       = simplexml_load_string($dados_xml);
			$xml       = json_decode(json_encode((array)$xml), TRUE);

			if (!isset($xml['NewDataSet']['ZCBSM_MENSAGEMTABLE'])) {

				if (isset($xml['NewDataSet']["ZCBSM_MATERIAIS_EQUIPAMENTOTABLE"]["MATNR"])) {
					$xml['NewDataSet']["ZCBSM_MATERIAIS_EQUIPAMENTOTABLE"] = [$xml['NewDataSet']["ZCBSM_MATERIAIS_EQUIPAMENTOTABLE"]];
				}

				foreach ($xml['NewDataSet']['ZCBSM_MATERIAIS_EQUIPAMENTOTABLE'] as $ponteiro => $pecas) {

					$pecaDescricao = utf8_decode(trim($pecas['MAKTX']));
					$pecaUnidade   = trim($pecas['MEINS']);
					$qtde          = trim($pecas['MNGKO']);
					$codigo        = trim($pecas['MATNR']);

					$nova_peca = false;
					$sql = "SELECT peca FROM tbl_peca WHERE fabrica = {$login_fabrica} AND referencia = '{$codigo}'";
					$res_peca = pg_query($con,$sql);

			 		if (pg_num_rows($res_peca) == 0) {
						$nova_peca = true;
						$produto_acabado = (strtolower($codigo) == strtolower($referencia)) ? 'true' : 'false';

						$pecaDescricao = str_replace("'","\\'",$pecaDescricao);
						$sql = "
							INSERT INTO tbl_peca(
								fabrica,
								referencia,
								descricao,
								origem,
								unidade,
								produto_acabado
							)VALUES(
								{$login_fabrica},
								'{$codigo}',
								E'{$pecaDescricao}',
								'NAC',
								'{$pecaUnidade}',
								{$produto_acabado}
							)RETURNING peca;
						";
						$res_peca = pg_query($con, $sql);
						
						if (strlen(pg_last_error()) > 0) {
							$msg_erro = "Erro ao tentar cadastrar a peça $codigo - $pecaDescricao";
						}
			 		}

					$peca = pg_fetch_result($res_peca, 0, 'peca');

					if($nova_peca === false){
						$sql = "SELECT peca FROM tbl_numero_serie_peca WHERE peca = {$peca} AND numero_serie = {$numero_serie} AND fabrica = {$login_fabrica}";
						$res_nova_peca = pg_query($con,$sql);

						if(pg_num_rows($res_nova_peca) > 0) continue;
			 		}

					if(preg_match('/\D/',$qtde)) $qtde = 1;
			 		$sql = "
						INSERT INTO tbl_numero_serie_peca(
							fabrica,
							serie_peca,
							referencia_peca,
							numero_serie,
							peca,
							qtde
						)VALUES(
							{$login_fabrica},
							'SEM SERIE',
							'{$codigo}',
							{$numero_serie},
							{$peca},
							{$qtde}
						) ON CONFLICT DO NOTHING;
					";
					
					pg_query($con,$sql);

					if (strlen(pg_last_error()) > 0) {
						$msg_erro = "Erro ao tentar cadastrar a série da peça $codigo - $pecaDescricao";
					}
				}
			}
		}
	}
}

?>
<!DOCTYPE html />
<html>
	<head>
		<meta http-equiv=pragma content=no-cache>
		<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/bootstrap.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/extra.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="css/tc_css.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/ajuste.css" />
		<link type="text/css" rel="stylesheet" href="plugins/dataTable.css" />

		<style>
			.depara_para {
				font-weight: bold;
				color: #FF0000;
			}

			td.cursor_lupa {
				vertical-align: middle;
			}
		</style>

		<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
		<script src="bootstrap/js/bootstrap.js"></script>
		<script src="plugins/dataTable.js"></script>
		<script src="plugins/resize.js"></script>
		<script src="plugins/shadowbox_lupa/lupa.js"></script>
		<script src='plugins/jquery.alphanumeric.js'></script>
		<? if ($imagemPeca) { ?>
			<script type='text/javascript' src='<?=APPBACK?>js/FancyZoom.js'></script>
			<script type='text/javascript' src='<?=APPBACK?>js/FancyZoomHTML.js'></script>
		<? } ?>
		<script>
			$(function () {
				$.dataTableLupa();
				<? if ($imagemPeca) { ?>
					setupZoom();
				<? } ?>
			});
		</script>
	</head>

	<body>
		<div id="container_lupa" style="overflow-y:auto;">
			<div id="topo">
				<img class="espaco" src="imagens/logo_new_telecontrol.png">
				<img class="lupa_img pull-right" src="imagens/lupa_new.png">
			</div>
			<br /><hr />
			<div class="row-fluid">
				<? if (!empty($msg_erro)) { ?>
					<div class="alert alert-danger alert_shadowbox">
						<h4><?=$msg_erro?></h4>
					</div>

				<? exit;
				} else if (isset($pedido) && ($login_fabrica != 157 && !empty($produto_referencia))) { ?>
					<div class="alert alert-info alert_shadowbox">
						<h4><?= traduz('Lista básica do produto') ?> <?=$produto_referencia?> - <?=$produto_descricao?></h4>
					</div>

					<div class="alert">
						<h5><?= traduz('Para ver toda a lista básica do produto apague a descrição ou referência e clique em pesquisar') ?></h5>
					</div>
				<? } ?>
				<?php if (in_array($login_fabrica, array(178,183)) AND $page == "cadastro_os_revenda"){ ?>
				<div class="alert alert-danger alert_shadowbox alert_pecas" style="display: none;"></div>
				<?php } ?>
				<form action="<?=$_SERVER['PHP_SELF']?>" method='POST' >
					<div class="span1"></div>
					<div class="span4">
						<input type="hidden" name="posicao" class="span12" value='<?=$posicao?>' />
						<input type="hidden" name="posicao_produto" class="span12" value='<?=$posicao_produto?>' />
						<input type="hidden" name="produto" class="span12" value='<?=$produto?>' />
						<? if (isset($posto_codigo) && strlen($posto_codigo) > 0) { ?>
							<input type="hidden" name="produto_codigo" class="span12" value='<?=$produto_codigo?>' />
							<input type="hidden" name="produto_nome" class="span12" value='<?=$produto_nome?>' />
							<? if (isset($posto_codigo) && strlen($posto_codigo) > 0) { ?>
								<input type="hidden" name="tipo_revenda" class="span12" value='<?=$tipo_revenda?>' />
							<? }
						}

						if (isset($posto_codigo) && strlen($posto_codigo) > 0) { ?>
							<input type="hidden" name="posto_codigo" class="span12" value='<?=$posto_codigo?>' />
							<input type="hidden" name="posto_nome" class="span12" value='<?=$posto_nome?>' />
						<?
						}

						if (in_array($login_fabrica, array(178,183))){ ?>
							<input type="hidden" name="page" value="<?=$page?>">
						<? }

						if (isset($subproduto)) { ?>
							<input type="hidden" name="subproduto" class="span12" value='<?=$subproduto?>' />
						<? }

						if (isset($familia)) { ?>
							<input type="hidden" name="familia" class="span12" value='<?=$familia?>' />
						<? }

						if (isset($revisao)) { ?>
							<input type="hidden" name="revisao" class="span12" value='<?=$revisao?>' />
						<? }

						if (isset($orcamento)) { ?>
							<input type="hidden" name="orcamento" class="span12" value='<?=$orcamento?>' />
						<? }

						if (isset($garantia)) { ?>
							<input type="hidden" name="garantia" class="span12" value='<?=$garantia?>' />
						<? }

						if (isset($defeito_constatado)) { ?>
							<input type="hidden" name="defeito_constatado" class="span12" value='<?= $defeito_constatado; ?>' />
						<? }

						if (isset($versao)) { ?>
							<input type="hidden" name="versao" class="span12" value='<?=$versao?>' />
						<? }

						if (isset($pedido)) { ?>
							<input type="hidden" name="pedido" class="span12" value='<?=$pedido?>' />
							<input type="hidden" name="tabela" class="span12" value='<?=$tabela?>' />
							<input type="hidden" name="tipo_pedido" class="span12" value='<?=$tipo_pedido?>' />
						<? }

						if (isset($produto_referencia) || isset($produto_descricao)) { ?>
							<input type="hidden" name="produto_referencia" class="span12" value='<?=$produto_referencia?>' />
							<input type="hidden" name="produto_descricao" class="span12" value='<?=$produto_descricao?>' />
						<? }

						if (isset($posto)) { ?>
							<input type="hidden" name="posto" value="<?=$posto?>" />
						<? }
						if (isset($xxos)) { ?>
							<input type="hidden" name="xxos" value="<?=$xxos?>" />
						<? }
						if (isset($preco_peca)) { ?>
                            <input type="hidden" name="tabela" class="span12" value='<?=$tabela?>' />
							<input type="hidden" name="preco_peca" value="<?=$preco_peca?>" />
						<? } ?>

						<select name="parametro" >
							<? if ($login_fabrica == 151) { ?>
								<option value="referencia_descricao" <?=($parametro == "referencia_descricao") ? "SELECTED" : ""?> ><?= traduz('Referência/Descrição') ?></option>
							<? } ?>
							<option value="referencia" <?= ($parametro == "referencia") ? "SELECTED" : ""; ?>><?php echo traduz('Referência') ?></option>
							<option value="descricao" <?= ($parametro == "descricao") ? "SELECTED" : ""; ?>><?php echo traduz('Descrição') ?></option>
						</select>
					</div>
					<div class="span4">
						<input type="text" name="valor" class="span12" value="<?=$valor?>" />
					</div>
					<div class="span2">
						<button type="button" class="btn pull-right" onclick="$(this).parents('form').submit();">Pesquisar</button>
					</div>
					<div class="span1"></div>
				</form>
			</div>
			<?
			if (!empty($produto) || !empty($familia) || ($login_fabrica == 157 && empty($produto))) {

				//hd-3625122 - fputti
				$condReferenciaFabrica = "";
				if ($login_fabrica == 171) {
					$condReferenciaFabrica = " OR UPPER(tbl_peca.referencia_fabrica) LIKE UPPER('%'||fn_retira_especiais('{$valor}')||'%')";
				}
				switch ($parametro) {
					case 'referencia':
						$valor = str_replace(array(".", ",", "-", "/", " ", "(", ")"), "", $valor);

						$whereAdc = " AND (UPPER(tbl_peca.referencia_pesquisa) LIKE UPPER('%'||fn_retira_especiais('{$valor}')||'%') $condReferenciaFabrica)";
						break;

					case 'descricao':
						$whereAdc = " AND UPPER(fn_retira_especiais(tbl_peca.descricao)) LIKE UPPER('%'||fn_retira_especiais('{$valor}')||'%') ";
						break;

					case 'referencia_descricao':
						$whereAdc = "AND (UPPER(fn_retira_especiais(tbl_peca.referencia)) LIKE UPPER('%'||fn_retira_especiais('{$valor}')||'%') OR UPPER(fn_retira_especiais(tbl_peca.descricao)) LIKE UPPER('%'||fn_retira_especiais('{$valor}')||'%') $condReferenciaFabrica)";
						break;
				}

				if (isset($referencia_descricao)) {
				        $distinct = "DISTINCT ";
				        $whereAdc = " AND (UPPER(fn_retira_especiais(tbl_peca.referencia)) LIKE UPPER('%'||fn_retira_especiais('{$valor}')||'%') OR UPPER(tbl_peca.descricao) LIKE UPPER('%'||fn_retira_especiais('{$valor}')||'%')) ";
				}

				if(in_array($login_posto, array(152,180,181,182))) {
					$whereAdc = " AND UPPER(tbl_peca.referencia_pesquisa) LIKE UPPER('%'||fn_retira_especiais('{$valor}')||'%') ";
				}

				if (!isset($pedido)) {
					if(isset($versao)){
						if ($login_fabrica == 151) { //hd_chamado=3132843
							#$whereVer = " AND (tbl_lista_basica.type ILIKE '%{$versao}') ";
							$whereVer = "AND (tbl_lista_basica.type = '000' OR tbl_lista_basica.type ILIKE '%{$versao}' OR tbl_lista_basica.type IS NULL)";

						} else {
							$whereVer = " AND ( UPPER(tbl_lista_basica.type) = UPPER('{$versao}') ) ";
						}
					}else{
						if($login_fabrica != 153){
							$whereVer = " AND (tbl_lista_basica.type IS NULL OR length(tbl_lista_basica.type) = 0 or tbl_lista_basica.type='000') ";
						}
					}
				}

				if (!isset($pedido)) {
					if ($login_fabrica == 153) {
						if (!$areaAdmin) {
							if (!isset($orcamento)) {
								$whereBloqueadaGarantia = "AND tbl_peca.bloqueada_garantia IS NOT TRUE";
							}
							$whereBloqueadaGarantiadp = "AND tbl_peca_para.bloqueada_garantia IS NOT TRUE";
						}
					} else {
						if (!isset($orcamento) and !in_array($login_fabrica, [35,186])) {
							$whereBloqueadaGarantia = "AND tbl_peca.bloqueada_garantia IS NOT TRUE";
						}
						$whereBloqueadaGarantiadp = "AND tbl_peca_para.bloqueada_garantia IS NOT TRUE";
					}
				}
				if(($login_fabrica == 160 or $replica_einhell) and $garantia == false){
					$whereBloqueadaVenda = "AND tbl_peca.bloqueada_venda IS NOT TRUE";
				}

				if (isset($revisao) || isset($preco_peca)) {
					$distinctOn = "DISTINCT ON (tbl_peca.peca)";
				}

				if ($login_fabrica == 190 && strlen($xxos) > 0) {
					$sqlTipo = "SELECT tbl_tipo_atendimento.codigo
                                                      FROM tbl_os 
                                                      JOIN tbl_tipo_atendimento ON  tbl_os.tipo_atendimento = tbl_tipo_atendimento.tipo_atendimento AND tbl_tipo_atendimento.fabrica={$login_fabrica} 
                                                     WHERE tbl_os.os={$xxos} AND tbl_os.fabrica={$login_fabrica}";
					$resTipo = pg_query($con, $sqlTipo);

					if (pg_num_rows($resTipo) > 0) {
						$tipo_atendimento_codigo = pg_fetch_result($resTipo,0,'codigo');
					}
					$sqlContratoOS = "SELECT tbl_tipo_contrato.consumiveis 
					                    FROM tbl_contrato 
					                    JOIN tbl_contrato_os USING(contrato)
					                    JOIN tbl_tipo_contrato  USING(tipo_contrato, fabrica)
					                   WHERE tbl_contrato_os.os = {$xxos}
					                     AND tbl_tipo_contrato.consumiveis IS TRUE";
					$resContrato = pg_query($con, $sqlContratoOS);

//exit(pg_num_rows($resContrato));
					if (pg_num_rows($resContrato) == 0 && $tipo_atendimento_codigo != "110") {

					//	if ($garantia == true ) {
						$whereConsumiveis = " AND (JSON_FIELD('consumiveis', tbl_peca.parametros_adicionais) != 't' OR tbl_peca.parametros_adicionais IS NULL)";
//						}
					}


//exit($whereConsumiveis );

				}

				if (isset($garantia) || isset($pedido)){
					$joinFora = "	LEFT JOIN tbl_peca_fora_linha ON tbl_peca_fora_linha.peca = tbl_peca.peca
									LEFT JOIN tbl_peca_fora_linha AS tbl_peca_para_fora_linha ON tbl_peca_para_fora_linha.peca = tbl_peca_para.peca 	";

					if ($login_fabrica == 35) {
						$campoForaLinha = " tbl_peca_fora_linha.peca_fora_linha AS peca_fora_de_linha, ";
					}

					$condFora = "	 AND (
										tbl_peca_fora_linha.peca_fora_linha IS NULL
										OR tbl_peca_fora_linha.libera_garantia IS TRUE
										OR (	tbl_peca_para.peca IS NOT NULL
											AND (	tbl_peca_para_fora_linha.peca_fora_linha IS NULL
												OR 	tbl_peca_para_fora_linha.libera_garantia IS TRUE
												)
											)
							)";
				}

				if (!empty($produto)) {
					$whereProduto = "AND tbl_lista_basica.produto = $produto";
				} else if (!empty($familia)) {
					$distinctOn = "DISTINCT ON (tbl_peca.peca)";
					$joinFamilia = "INNER JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia AND tbl_familia.fabrica = {$login_fabrica}";
					$whereFamilia = "AND tbl_lista_basica.produto = tbl_produto.produto AND tbl_familia.familia = {$familia}";
				}

				if ($login_fabrica == 151 && (isset($pedido) || $tipo_revenda == 't')) {
					$distinct = "DISTINCT ON (tbl_peca.peca)";
					$order_by = "ORDER BY tbl_peca.peca";
				} else {
					$order_by = "ORDER BY tbl_peca.referencia";
				}

				$whereItemAparencia = "";
				if ($item_aparencia == 'f'){
					if(in_array($login_fabrica, array(151))){
						if($tipo_revenda == 'f'){
							$whereItemAparencia = "AND tbl_peca.item_aparencia IS FALSE";
						}

					}else{
						$whereItemAparencia = "AND tbl_peca.item_aparencia IS FALSE";
					}
				}

				if (in_array($login_fabrica, [169,170])) {
					$whereLCP = "AND tbl_peca.intervencao_carteira IS TRUE";
				}

				$sqlPst = "SELECT codigo_posto FROM tbl_posto_fabrica WHERE posto = {$login_posto} AND codigo_posto = UPPER('{$produto_serie}') AND fabrica = {$login_fabrica};";
				$resPst = pg_query($con, $sqlPst);

				if (in_array($login_fabrica, array(169,170)) && strlen($produto_serie) > 0 && pg_num_rows($resPst) == 0) {
					$sqlNumSeriePeca = "
						SELECT
							{$distinct}
							tbl_peca.peca,
							tbl_peca.referencia,
							tbl_peca.descricao,
							tbl_peca.referencia_fabrica,
							tbl_peca.bloqueada_garantia AS peca_bloqueio_garantia,
							tbl_numero_serie_peca.qtde,
							tbl_peca_para.peca AS peca_para,
							tbl_peca_para.bloqueada_garantia AS peca_para_bloqueio_garantia,
							tbl_peca_para.referencia AS referencia_para,
							tbl_peca_para.descricao AS descricao_para,
							tbl_peca.parametros_adicionais,
							tbl_peca.multiplo
						FROM tbl_numero_serie_peca
						JOIN tbl_numero_serie ON tbl_numero_serie.numero_serie = tbl_numero_serie_peca.numero_serie AND tbl_numero_serie.fabrica = {$login_fabrica}
						JOIN tbl_produto ON tbl_produto.produto = tbl_numero_serie.produto AND tbl_produto.fabrica_i = {$login_fabrica}
						JOIN tbl_peca ON tbl_peca.peca = tbl_numero_serie_peca.peca AND tbl_peca.fabrica = {$login_fabrica}
						LEFT JOIN tbl_depara ON tbl_depara.peca_de = tbl_peca.peca AND (tbl_depara.expira > current_date OR tbl_depara.expira IS NULL) AND tbl_depara.fabrica = {$login_fabrica}
						LEFT JOIN tbl_peca AS tbl_peca_para ON tbl_peca_para.peca = tbl_depara.peca_para {$whereBloqueadaGarantiadp} AND tbl_peca_para.fabrica = {$login_fabrica}
						{$joinFora}
						WHERE tbl_numero_serie_peca.fabrica = {$login_fabrica}
						AND tbl_peca.ativo IS TRUE
						AND tbl_peca.produto_acabado IS NOT TRUE
						AND (tbl_numero_serie.serie = UPPER('{$produto_serie}')
						OR tbl_numero_serie.serie = UPPER('S{$produto_serie}'))
						{$whereBloqueadaGarantia}
						{$whereBloqueadaVenda}
						{$condFora}
						{$whereAdc}
						{$whereItemAparencia}
						{$whereConsumiveis}
						{$whereLCP};
					";

					$resNumSeriePeca = pg_query($con, $sqlNumSeriePeca);
				}

				$sql = "";
				if (!pg_num_rows($resNumSeriePeca)) {
					if (in_array($login_fabrica, array(143,145,148,190))) {

						$sql = "SELECT {$distinct}
								tbl_peca.peca,
								tbl_peca.referencia,
								tbl_peca.descricao,
								tbl_peca.bloqueada_garantia AS peca_bloqueio_garantia,
								0 AS qtde,
								tbl_peca_para.peca AS peca_para,
								tbl_peca_para.bloqueada_garantia AS peca_para_bloqueio_garantia,
								tbl_peca_para.referencia AS referencia_para,
								tbl_peca_para.descricao AS descricao_para,
								tbl_peca.parametros_adicionais,
								tbl_peca.multiplo
							FROM tbl_peca
							LEFT JOIN tbl_depara ON tbl_depara.fabrica = $login_fabrica AND tbl_depara.peca_de = tbl_peca.peca AND (tbl_depara.expira > current_date OR tbl_depara.expira IS NULL)
							LEFT JOIN tbl_peca AS tbl_peca_para ON tbl_peca_para.fabrica = $login_fabrica AND tbl_peca_para.peca = tbl_depara.peca_para $whereBloqueadaGarantiadp
							{$joinFora}
							WHERE tbl_peca.fabrica = $login_fabrica
							AND tbl_peca.ativo IS TRUE
							{$whereBloqueadaGarantia}
							{$condFora}
							{$whereItemAparencia}
							{$whereConsumiveis}
							AND tbl_peca.produto_acabado IS NOT TRUE
							$whereAdc";
					} else if (isset($preco_peca)) {
						if (!empty($posto)) {
							$joinPostoLinhaWhere = " AND tbl_posto_linha.posto = {$posto} ";
						}

						if(in_array($login_fabrica, array(161,167,177,186,203))){
                            if (($login_fabrica == 161 && $posto_interno != 't') || in_array($login_fabrica, [167,177,186,203])) {
                                if ($login_fabrica == 161) {
                                	$condTabelaVenda = " AND upper(tbl_tabela.descricao) ILIKE '%VENDA' ";	
                                }else{
                                	$condTabelaVenda = " AND upper(tbl_tabela.descricao) = 'VENDA' ";
                                }
                                $campo_tabela = "tabela_posto";

                                if($login_fabrica == 177 and $cadastro_os == true){
                                	$campo_tabela	= "tabela";
                                	$condTabelaVenda = " AND upper(tbl_tabela.descricao) ILIKE '%GARANTIA' ";
                                }

                                $leftJoinTabela = " LEFT JOIN tbl_tabela ON tbl_tabela.tabela = tbl_posto_linha.$campo_tabela AND tbl_tabela.fabrica = {$login_fabrica} {$condTabelaVenda}";
                            } else {
                                $condTabelaVenda = " AND tbl_tabela.tabela = $tabela";
                                $campo_tabela = "tabela";

                                $leftJoinTabela = " LEFT JOIN tbl_tabela ON tbl_tabela.fabrica = tbl_peca.fabrica AND tbl_tabela.tabela = $tabela";
                            }

							$condPreco = "AND tbl_tabela_item.preco > 0";
							// HD-7660244
                            if ($cadastro_os == true && in_array($login_fabrica, [186])) {
                            	$condPreco = "";
                            }

						}else{
							$campo_tabela = "tabela";
							$leftJoinTabela = "LEFT JOIN tbl_tabela ON tbl_tabela.tabela = tbl_posto_linha.$campo_tabela AND tbl_tabela.fabrica = {$login_fabrica}";
						}

						$sql = "SELECT {$distinctOn} tbl_peca.peca,
								tbl_peca.referencia,
								tbl_peca.descricao,
								tbl_peca.bloqueada_garantia AS peca_bloqueio_garantia,
								tbl_lista_basica.qtde,
								tbl_lista_basica.parametros_adicionais as lbm_parametros_adicionais,
								tbl_tabela_item.preco,
								tbl_peca_para.peca AS peca_para,
								tbl_peca_para.bloqueada_garantia AS peca_para_bloqueio_garantia,
								tbl_peca_para.referencia AS referencia_para,
								tbl_peca_para.descricao AS descricao_para,
								tbl_peca.parametros_adicionais,
								tbl_peca.referencia_fabrica,
								tbl_peca.multiplo,
								tbl_peca.estoque
							FROM tbl_lista_basica
							INNER JOIN tbl_produto ON tbl_produto.fabrica_i = $login_fabrica AND tbl_produto.produto = tbl_lista_basica.produto
							INNER JOIN tbl_peca ON tbl_peca.fabrica = $login_fabrica AND tbl_peca.peca = tbl_lista_basica.peca
							LEFT JOIN tbl_depara ON tbl_depara.fabrica = $login_fabrica AND tbl_depara.peca_de = tbl_peca.peca AND (tbl_depara.expira > current_date OR tbl_depara.expira IS NULL)
							LEFT JOIN tbl_peca AS tbl_peca_para ON tbl_peca_para.fabrica = $login_fabrica AND tbl_peca_para.peca = tbl_depara.peca_para $whereBloqueadaGarantiadp
							LEFT JOIN tbl_posto_linha ON tbl_posto_linha.linha = tbl_produto.linha {$joinPostoLinhaWhere}
							$leftJoinTabela
							LEFT JOIN tbl_tabela_item ON tbl_tabela_item.tabela = tbl_tabela.tabela AND tbl_tabela_item.peca = tbl_peca.peca
							{$joinFora}
							{$joinFamilia}
							WHERE tbl_lista_basica.fabrica = $login_fabrica
							{$whereProduto}
							AND tbl_peca.ativo IS TRUE
							{$whereBloqueadaGarantia}
							{$whereFamilia}
							{$condFora}
							AND tbl_peca.produto_acabado IS NOT TRUE
							{$whereAdc}
							{$condPreco}
							{$whereItemAparencia}
							{$whereConsumiveis}
							ORDER BY tbl_peca.peca";
					} else {

						if($login_fabrica == 151 && isset($_REQUEST["produto"]) && !isset($pedido) && $tipo_revenda == 'f' && $areaAdmin === false){

							$join_defeito_constatado_familia_peca = "
								JOIN tbl_peca_familia ON tbl_peca_familia.peca = tbl_peca.peca
								JOIN tbl_defeito_constatado_familia_peca ON tbl_defeito_constatado_familia_peca.familia_peca = tbl_peca_familia.familia_peca AND tbl_defeito_constatado_familia_peca.fabrica = {$login_fabrica}
								JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_defeito_constatado_familia_peca.defeito_constatado AND tbl_defeito_constatado.defeito_constatado = {$defeito_constatado}";
						}

						if (in_array($login_fabrica, array(52,157))) {
							if (in_array($login_fabrica, array(157))) {
								if (strlen($tipo_pedido) > 0) {
									$distinct = "DISTINCT";
									$joinLinha = "LEFT JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha AND tbl_linha.fabrica = {$login_fabrica}";

									$sql = "SELECT fn_retira_especiais(descricao) AS descricao FROM tbl_tipo_pedido WHERE fabrica = $login_fabrica AND tipo_pedido = $tipo_pedido";
									$res = pg_query($con,$sql);

									if(pg_num_rows($res) > 0){
										$descricao_tipo_pedido = pg_fetch_result($res, 0, "descricao");
									}else{
										$descricao_tipo_pedido = $tipo_pedido;
									}

									if (strtoupper($descricao_tipo_pedido) == "PEDIDO PECAS WAP") {
										$whereLinha = "AND (tbl_linha.nome IS NULL OR UPPER(fn_retira_especiais(tbl_linha.nome)) != 'VENTILADORES')";
									} else if (strtoupper($descricao_tipo_pedido) == "PEDIDO PECAS VENTILACAO") {
										$whereLinha = "AND UPPER(fn_retira_especiais(tbl_linha.nome)) = 'VENTILADORES'";
									}

								}
							} 
							$distinct = "DISTINCT";
						}

						if ($login_fabrica == 153) {
							$campos_devolucao_obrig = " tbl_peca.devolucao_obrigatoria, ";
							$bloqueada_venda 		= " AND tbl_peca.bloqueada_venda IS NOT TRUE ";
						}

						if ($login_fabrica == 158 && !empty($posto) && empty($pedido)) {
							$column_estoque = "
								, COALESCE(tbl_estoque_posto.qtde, 0) AS estoque
								, COALESCE(estoque_peca_para.qtde, 0) AS estoque_peca_para
							";

							if ($posto_interno == 't') {
								$left_estoque = "";
							} else {
								$left_estoque = "LEFT";
							}

							$join_estoque   = "
								{$left_estoque} JOIN tbl_estoque_posto ON tbl_estoque_posto.peca = tbl_peca.peca AND tbl_estoque_posto.posto = {$posto} AND tbl_estoque_posto.fabrica = {$login_fabrica} AND tbl_estoque_posto.qtde > 0
								LEFT JOIN tbl_estoque_posto AS estoque_peca_para ON estoque_peca_para.peca = tbl_depara.peca_para AND estoque_peca_para.posto = {$posto} AND estoque_peca_para.fabrica = {$login_fabrica}
							";
						} else if ($login_fabrica == 161) {
	                        $column_estoque = ", tbl_peca.estoque";
						}

	                    $bloquea_bloqueada_venda = true;

	                    if (!empty($tipo_pedido) and $login_fabrica == 153) {
	                        $sqlTP = "SELECT * from tbl_tipo_pedido where tipo_pedido = $tipo_pedido AND pedido_faturado";
	                        $qryTP = pg_query($con, $sqlTP);

	                        if (pg_num_rows($qryTP) == 0) {
	                            $bloquea_bloqueada_venda = false;
	                        }
	                    }

	                    if (in_array($login_fabrica, array(157)) && !empty($defeito_constatado)) {

	                    	$joinPecaDefeito = " 
	                    	JOIN tbl_peca_defeito_constatado
	                    	ON tbl_peca_defeito_constatado.peca =  (
	                    		CASE
	                    			WHEN tbl_peca_para.peca IS NOT NULL
	                    			THEN tbl_peca_para.peca
	                    			ELSE tbl_peca.peca
	                    		END 																	
	                    	) 
	                    	AND tbl_peca_defeito_constatado.defeito_constatado IN({$defeito_constatado}) 
	                    	AND tbl_peca_defeito_constatado.fabrica = {$login_fabrica}";

	                    	if (!empty($os_id)) {

	                    		$sqlCorteDefeito = "SELECT os
	                    							FROM tbl_os
	                    							WHERE os = {$os_id}
	                    							AND fabrica = {$login_fabrica}
	                    							AND data_digitacao::date <= '2019-12-18'::date";
	                    		$resCorteDefeito = pg_query($sqlCorteDefeito);

	                    		if (pg_num_rows($resCorteDefeito) > 0) {
	                    			$joinPecaDefeito = "";
	                    		}

	                    	}

	                    }

	                    if ($login_fabrica == 157 and isset($pedido)) {
	                    	$sql = "SELECT DISTINCT
									tbl_peca.peca,
									tbl_peca.ipi,
									tbl_peca.referencia,
									tbl_peca.descricao,
									tbl_lista_basica.qtde,
									tbl_lista_basica.posicao,
									tbl_peca.item_aparencia,
									tbl_peca.bloqueada_venda,
									tbl_peca.bloqueada_garantia AS peca_bloqueio_garantia,
									tbl_peca_para.peca AS peca_para,
									tbl_peca_para.bloqueada_garantia AS peca_para_bloqueio_garantia,
									tbl_peca_para.referencia AS referencia_para,
									tbl_peca_para.descricao AS descricao_para,
									tbl_peca.parametros_adicionais,
									tbl_peca.multiplo
									FROM tbl_peca
									LEFT JOIN tbl_lista_basica ON tbl_lista_basica.peca = tbl_peca.peca
									LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_lista_basica.produto {$whereProduto}
									LEFT JOIN tbl_depara ON tbl_depara.fabrica = $login_fabrica AND tbl_depara.peca_de = tbl_peca.peca AND (tbl_depara.expira > current_date OR tbl_depara.expira IS NULL)
									LEFT JOIN tbl_peca AS tbl_peca_para ON tbl_peca_para.fabrica = $login_fabrica AND tbl_peca_para.peca = tbl_depara.peca_para $whereBloqueadaGarantiadp
									{$joinFora}
									{$joinLinha}
									WHERE
									tbl_peca.fabrica = $login_fabrica
									AND tbl_peca.ativo IS TRUE
									{$condFora}
									AND tbl_peca.produto_acabado IS NOT TRUE
									{$whereAdc}
									{$whereItemAparencia}
									{$whereLinha}
									ORDER BY tbl_peca.peca;";
	                    } else {

	                    	if($login_fabrica == 35){
	                    		$promocao_site = " tbl_peca.promocao_site, ";

								if ($areaAdmin) {
									// HD 4332722 - admin pode lançar peça mesmo que bloqueada em garantia
									$campo_bloqueada_garantia = " 'f' AS bloqueada_garantia,  ";
									$whereBloqueadaGarantiadp = '';
									$whereBloqueadaGarantia = '';
								}
	                    	}

	                    	// LOFRA, porem está parametrizado
                    		if (in_array($login_fabrica, array(176))){
                    			$column_ordem = ", tbl_lista_basica.ordem";
                    		}else{
                    			$column_ordem = "";
                    		}

                    		if ($login_fabrica == 175){
                    			$colunas_ibra = "tbl_peca.reducao,
											tbl_peca.numero_serie_peca,
											tbl_peca.unidade, ";
								/*if (!empty($op_pesquisa)){
									$cond_producao = "AND tbl_lista_basica.ordem_producao = '$op_pesquisa'";
								}*/
                    		}

	                    	if (in_array($login_fabrica, array(158))) {

	                    		$tipo_atendimento = trim($_GET["tipo_atendimento_desc"]);

	                    		if (strtolower($tipo_atendimento) == "piso") {

	                    			$join_defeito_constatado_familia_peca = "
                                     INNER JOIN tbl_peca_familia ON tbl_peca_familia.peca = tbl_peca.peca
									 INNER JOIN tbl_defeito_constatado_familia_peca 
									 	ON	tbl_defeito_constatado_familia_peca.familia_peca = tbl_peca_familia.familia_peca
										AND tbl_defeito_constatado_familia_peca.fabrica = {$login_fabrica}
										AND tbl_defeito_constatado_familia_peca.tipo_atendimento = 2
									";

	                    		}

							}
							
							if (in_array($login_fabrica, [35]) and $areaAdmin) {
								$bloqueio_garantia = "'f' AS peca_bloqueio_garantia,";
							} else {
								$bloqueio_garantia = "tbl_peca.bloqueada_garantia AS peca_bloqueio_garantia,";
							}

							if (in_array($login_fabrica, [169,170])) {
								$whereLCP = "AND tbl_peca.intervencao_carteira IS TRUE";
							}

							$distinct = ($login_fabrica == 151) ? "DISTINCT" : $distinct;


							if(in_array($login_fabrica, [169,170]) and $pedido == 't'){
								$sql_peca_original = " and tbl_peca.parametros_adicionais::JSON->>'peca_original' = 't' ";
							}

							if ($login_fabrica != 178){
								$cond_acabado = " AND tbl_peca.produto_acabado IS NOT TRUE ";
							}

							if ($login_fabrica == 194){
								$column_peca_alt = ",tbl_peca_alternativa.peca_alternativa, ref_peca_alt.referencia AS peca_ref_alt, ref_peca_alt.descricao AS peca_desc_alt";
								$join_peca_alt = "
									LEFT JOIN tbl_peca_alternativa ON tbl_peca_alternativa.peca_para = tbl_peca.peca AND tbl_peca_alternativa.fabrica = $login_fabrica AND tbl_peca_alternativa.status IS TRUE
									LEFT JOIN tbl_peca AS ref_peca_alt ON ref_peca_alt.peca = tbl_peca_alternativa.peca_de AND ref_peca_alt.fabrica = $login_fabrica";
							}

							$sql = "
								SELECT {$distinct}
									tbl_peca.peca,
									tbl_peca.ipi,
									$promocao_site
									tbl_peca.referencia,
									tbl_peca.descricao,
									tbl_peca.referencia_fabrica,
									$campos_devolucao_obrig
									tbl_lista_basica.qtde,
									tbl_lista_basica.posicao,
									tbl_lista_basica.parametros_adicionais AS lbm_parametros_adicionais,
									tbl_peca.item_aparencia,
									$colunas_ibra
									$campoForaLinha
									tbl_peca.bloqueada_venda,
									$bloqueio_garantia
									$campo_bloqueada_garantia
									tbl_peca_para.peca AS peca_para,
									tbl_produto.linha AS linha_produto,
									tbl_peca_para.referencia AS referencia_para,
									tbl_peca_para.descricao AS descricao_para,
									tbl_peca_para.bloqueada_garantia AS peca_para_bloqueio_garantia,
									tbl_peca.parametros_adicionais,
									tbl_peca.multiplo
									{$column_ordem}
									{$column_estoque}
									{$column_peca_alt}
								FROM tbl_lista_basica
                          		INNER JOIN tbl_produto
                                	ON tbl_produto.fabrica_i = tbl_lista_basica.fabrica
                                	AND tbl_produto.produto   = tbl_lista_basica.produto
                          		INNER JOIN tbl_peca
                                	ON tbl_peca.fabrica = tbl_lista_basica.fabrica
                                	AND tbl_peca.peca = tbl_lista_basica.peca
								{$join_defeito_constatado_familia_peca}
                           		LEFT JOIN tbl_depara ON tbl_depara.fabrica = tbl_peca.fabrica
                                	AND tbl_depara.peca_de = tbl_peca.peca
                                	AND (tbl_depara.expira > current_date OR tbl_depara.expira IS NULL)
								{$join_estoque}
								{$join_peca_alt}
                           		LEFT JOIN tbl_peca AS tbl_peca_para
                                	ON tbl_peca_para.fabrica = tbl_lista_basica.fabrica
                                	AND tbl_peca_para.peca = tbl_depara.peca_para
								{$joinFora}
								{$joinLinha}
								{$joinPecaDefeito}
								WHERE tbl_lista_basica.fabrica = $login_fabrica
								{$whereProduto}
								AND tbl_peca.ativo IS TRUE
								{$whereBloqueadaVenda}
								{$condFora}
								{$cond_acabado}
								{$whereAdc}
								{$whereItemAparencia}
								{$whereVer}
								{$whereLinha}
								{$cond_producao}
								{$whereLCP}
								{$sql_peca_original}
								{$whereConsumiveis}
								{$order_by}
							";

						}
					}
				}
				
				if (pg_num_rows($resNumSeriePeca) > 0) {
					$res = $resNumSeriePeca;
				} else {
					$res = pg_query($con,$sql);
				}				
				$rows = pg_num_rows($res);

				/*if ($login_fabrica == 175 AND empty($op_pesquisa)){
					$rows = 0;
					unset($selecionaPecas);
				}*/

				if ($rows > 0) { ?>
					<div id="border_table">
						<?php if ($login_fabrica == 194){ ?>
							<table>
								<tr>
									<td style="background-color:#91C8FF; width: 12px;"></td>
									<td><b>Peça Alternativa</b></td>
								</tr>
							</table>
						<?php } ?>
						<table class="table table-striped table-bordered table-hover table-lupa" style="margin-bottom:60px !important;">
							<thead>
								<tr class='titulo_coluna'>
								<?php if (in_array($login_fabrica, [184])) { ?>
									<th>Posição</th>
								<?php } ?>
								
								<? if ($imagemPeca) { ?>
									<th><?= traduz('Imagem') ?></th>
								<? } ?>
									<?php if(in_array($login_fabrica, array(160,163,176)) or $replica_einhell){
										echo "<th>".traduz('Posição')."</th>";
									}

									if (in_array($login_fabrica, array(176)))
									{
										echo "<th>".traduz('Ordem')."</th>";
									}

									//hd-3625122 - fputti
                                    if ($login_fabrica == 171) {
                                    	echo "<th>".traduz('Referência Fábrica')."</th>";
                                    }
                                    ?>
									<th><?= traduz('Referência') ?></th>
									<th><?= traduz('Descrição') ?></th>
									<? if (!isset($pedido) && !isset($preco_peca)) { 
										if (!in_array($login_fabrica, array(176,175,179)))
										{
										?>
											<th><?= traduz('Quantidade') ?></th>
									<?php
										}
									} else { ?>
										<th><?= traduz('Preço') ?></th>
										<?php if ($login_fabrica == 183 AND in_array($login_tipo_posto_codigo, array("Rev", "Rep"))){ ?>
											<th><?= traduz('Quantidade') ?></th>
										<?php } ?>
									<? } ?>
									<th><?= traduz('Mudou para') ?></th>
									<? if ($login_fabrica == 158 && !empty($posto) && empty($pedido)) { ?>
										<th><?= traduz('Estoque') ?></th>
									<? }

									if (in_array($login_fabrica, array(178,183)) AND $page == "cadastro_os_revenda"){ ?>
										<th><?= traduz('Quantidade Lançada') ?></th>
										<th><?= traduz('Serviço Realizado') ?></th>		
										<?php if ($login_fabrica == 183){ ?>
										<th><?= traduz('Defeito Peça') ?></th>
										<?php } ?>
									<?php 
									}
									if ($selecionaPecas) { ?>
										<th><?= traduz('Lançar') ?></th>
									<? } ?>
								</tr>
							</thead>
							<tbody>
								<? for ($i = 0; $i < $rows; $i++) {
									$peca                  = pg_fetch_result($res, $i, 'peca');
									$referencia            = pg_fetch_result($res, $i, 'referencia');

									if($login_fabrica == 35){
										$promocao_site     = pg_fetch_result($res, $i, 'promocao_site');
										$bloqueada_garantia     = pg_fetch_result($res, $i, 'bloqueada_garantia');
 									}
 									
									if(in_array($login_fabrica, array(160,163,176,184)) or $replica_einhell){
										$posicao_coluna    = pg_fetch_result($res, $i, 'posicao');
									}

									if ($login_fabrica == 194){
										$peca_alternativa = pg_fetch_result($res, $i, "peca_alternativa");
										$peca_ref_alt = pg_fetch_result($res, $i, "peca_ref_alt");
										$peca_desc_alt = pg_fetch_result($res, $i, "peca_desc_alt");
									}

									if(in_array($login_fabrica, array(176))){
										$ordem_coluna      = pg_fetch_result($res, $i, 'ordem');
									}
									$descricao             		= pg_fetch_result($res, $i, 'descricao');
									$ipi                   		= pg_fetch_result($res, $i, 'ipi');
									$qtde                  		= pg_fetch_result($res, $i, 'qtde');
									$peca_para             		= pg_fetch_result($res, $i, "peca_para");
									$referencia_para       		= pg_fetch_result($res, $i, "referencia_para");
									$descricao_para       	 	= pg_fetch_result($res, $i, "descricao_para");
									$parametros_adicionais 		= json_decode(pg_fetch_result($res, $i, "parametros_adicionais"), true);
									$lbm_parametros_adicionais	= json_decode(pg_fetch_result($res, $i, "lbm_parametros_adicionais"), true);

									if ($login_fabrica == 195) {
										$data_de = $lbm_parametros_adicionais["data_de"];
										$data_ate = $lbm_parametros_adicionais["data_ate"];


										list($dia, $mes, $ano) = explode("/",$_GET["data_fabricacao"]);
										$xdata_fabricacao = "$ano-$mes-$dia";

										if (strlen($data_de) > 0 && strlen($data_ate) > 0) {

											if (strtotime($xdata_fabricacao) < strtotime($data_de) && strtotime($xdata_fabricacao) > strtotime($data_ate)) {
												continue;
											} elseif (strtotime($xdata_fabricacao) < strtotime($data_de)) {

												
												continue;
											} 

										} elseif (strlen($data_de) > 0 && strlen($data_ate) == 0) {
											if (strtotime($xdata_fabricacao) < strtotime($data_de)) {
												
												continue;
											}
										}
									}


									$multiplo              		= pg_fetch_result($res, $i, "multiplo");
									$bloqueada_venda       		= pg_fetch_result($res, $i, "bloqueada_venda");
									$ordem                 		= pg_fetch_result($res, $i, 'ordem');
									$peca_fora_de_linha    		= pg_fetch_result($res, $i, 'peca_fora_de_linha');
									$peca_bloqueio_garantia		= pg_fetch_result($res, $i, "peca_bloqueio_garantia");
									$peca_para_bloqueio_garantia = pg_fetch_result($res, $i, "peca_para_bloqueio_garantia");
									$linha_produto  			= pg_fetch_result($res, $i, "linha_produto");

									if ($login_fabrica == 175){
										$reducao	 		= pg_fetch_result($res, $i, 'reducao');
										$numero_serie_peca 	= pg_fetch_result($res, $i, 'numero_serie_peca');
										$unidade            = pg_fetch_result($res, $i, "unidade");
									}	

                                    if (false === $bloquea_bloqueada_venda) {
                                        $bloqueada_venda = 'f';
                                    }

									if ($login_fabrica == 158 && isset($join_estoque)) {
										$estoque = pg_fetch_result($res, $i, "estoque");
										$estoque_peca_para = pg_fetch_result($res, $i, "estoque_peca_para");
									}
									//hd-3625122 - fputti
									if ($login_fabrica == 171) {
										$referencia_fabrica = trim(pg_fetch_result($res, $i, 'referencia_fabrica'));
									}


									$descricao = str_replace("'","",$descricao);

									if(in_array($login_fabrica, array(148,156,161,163,167, 177,186,203))){
										
										if (in_array($login_fabrica, [177])) {
											$preco = number_format(pg_fetch_result($res, $i, 'preco'), 2,',','.');
										} else {
											$preco = number_format(pg_fetch_result($res, $i, 'preco'), 2);
										}
										
										if ($login_fabrica == 161) {
                                            $estoque = pg_fetch_result($res,$i,estoque);
										}
									}


									$r = array(
										"produto"    => $produto,
										"peca"       => $peca,
										"descricao"  => utf8_encode($descricao),
										"referencia" => $referencia,
										"referencia_fabrica" => utf8_encode($referencia_fabrica)
									);

									if($login_fabrica == 35){
										$r['promocao_site'] = $promocao_site;
									}

									if (strlen($peca_para) > 0) {
										$r = array(
											"peca"       => $peca_para,
											"descricao"  => utf8_encode($descricao_para),
											"referencia" => $referencia_para,
											"referencia_fabrica" => utf8_encode($referencia_fabrica)
										);
									}

									if(in_array($login_fabrica, array(153))){
										$devolucao_obrigatoria = pg_fetch_result($res, $i, 'devolucao_obrigatoria');
										$r["devolucao_obrigatoria"] = $devolucao_obrigatoria;
									}

									if (strlen($posicao) > 0) {
										$r["posicao"] = $posicao;
									}

									if (strlen($posicao_produto) > 0) {
										$r["posicao_produto"] = $posicao_produto;
									}

									if (isset($subproduto)) {
										$r["subproduto"] = $subproduto;
									}

									if ($login_fabrica == 175){
										$r["reducao"] 			= $reducao;
										$r["numero_serie_peca"] = $numero_serie_peca;
									}

									if (isset($anexo_peca_os) && $parametros_adicionais["anexo_os"]) {
										$r["anexo_os"]    = $parametros_adicionais["anexo_os"];
										$r['qtde_anexos'] = $parametros_adicionais['qtde_anexos'];
									}

									if ($login_fabrica == 177){
										if ($parametros_adicionais["lote"] == 't') {
											$r["lote"] = $parametros_adicionais["lote"];
										}
									}

									if (isset($revisao)) {
										$r["revisao"] = $revisao;
									}

									if(in_array($login_fabrica, array(148)) || isset($preco_peca)){
										$r["qtde"]	= $qtde;
										$r["preco"] = (strlen($preco) == 0) ? "0.00" : $preco;
									}

									if(in_array($login_fabrica, array(147,149,153,156,157,160,161,165)) OR $usa_calculo_ipi or $replica_einhell){
										$r["ipi"] = $ipi;
										if ($login_fabrica == 161) {
                                            $r["estoque"] = $estoque;
										}
									}

									if ($login_fabrica == 158){
										if (isset($estoque)) {
											if (strlen($peca_para) > 0) {
												$r["estoque"] = $estoque_peca_para;
											} else {
												$r["estoque"] = $estoque;
											}
										}

										if(isset($lbm_parametros_adicionais)){
											$r["lbm_parametros_adicionais"] = $lbm_parametros_adicionais;
										}
									}

									if (in_array($login_fabrica, [175])) {
										$r["unidade"] = $unidade;
									}

									if (!isset($pedido) && !isset($preco_peca)) {

										if (in_array($login_fabrica, [148])) {

											$sqlTabelaPadrao = "SELECT preco
																FROM tbl_tabela_item
																JOIN tbl_tabela USING(tabela)
																WHERE fabrica = {$login_fabrica}
																AND peca = {$peca}
																AND sigla_tabela = 'TABPADRAO'";
											$resTabelaPadrao = pg_query($con, $sqlTabelaPadrao);

											if (pg_num_rows($resTabelaPadrao) > 0) {
												if (pg_fetch_result($resTabelaPadrao,0,"preco") < 10000) {
													$r["preco"] = number_format(pg_fetch_result($resTabelaPadrao,0,"preco"),2,',','.');
												}
											}

										}

										unset($preco);
									} else {
										if (!empty($tabela)) {
											if(strlen($peca_para) > 0){ //hd_chamado=2574626
												$sqlT = "SELECT preco FROM tbl_tabela_item WHERE tabela = $tabela AND peca = $peca_para";
												$resT = pg_query($con,$sqlT);
												$preco_para = (pg_num_rows($resT) == 1) ? number_format(pg_fetch_result($resT,0,0),2,',','.') : '';
											}

											$sqlT = "SELECT preco FROM tbl_tabela_item WHERE tabela = $tabela AND peca = $peca";
											$resT = pg_query($con,$sqlT);

						 					if (pg_num_rows($resT) == 1) {
						 						if ($login_fabrica == 156) {
						 							$preco = number_format(pg_fetch_result($resT,0,0),2,'.','');
						 						} else if ($login_fabrica != 147) {
						 							$preco = number_format(pg_fetch_result($resT,0,0),2,',','.');
						 						} else {
						 							$preco = pg_fetch_result($resT,0,0);
						 						}
					 						} else {
					 							$preco = '0.00';
					 						}
										} else {
											if(!empty($tipo_pedido)) {
												$sql = "SELECT uso_consumo, pedido_em_garantia FROM tbl_tipo_pedido WHERE fabrica = $login_fabrica AND tipo_pedido = $tipo_pedido";
												$res_tipo = pg_query($con,$sql);

												$uso_consumo = pg_fetch_result($res_tipo, 0, "uso_consumo");
												$pedido_em_garantia = pg_fetch_result($res_tipo, 0, "pedido_em_garantia");

												if($uso_consumo<>"t"){
													$coluna_tabela_preco = "tabela_posto";
												}else{
													$coluna_tabela_preco = "tabela_bonificacao";
												}

												if ($login_fabrica == 183 AND $pedido_em_garantia == "t"){
													$coluna_tabela_preco = "tabela";
													$cond_posto_linha = " AND tbl_posto_linha.linha = {$linha_produto} ";
												}
											}

											if($login_fabrica == 161 or empty($coluna_tabela_preco)) $coluna_tabela_preco = "tabela_posto";


                                            						if(in_array($login_fabrica, array(35,168))){
												$coluna_tabela_preco = "tabela";
											}

											if($login_fabrica == 177 and $cadastro_os == true){
												$coluna_tabela_preco = "tabela";
											}


											if ($login_fabrica == 195 && strlen($xdata_fabricacao) > 0) {
												$sqlT = "
												SELECT  tabela
												FROM    tbl_tabela
												WHERE   data_vigencia::DATE <= '{$xdata_fabricacao}'
												AND   termino_vigencia::DATE >= '{$xdata_fabricacao}'
												AND     fabrica = $login_fabrica
												LIMIT   1
											    ";
											    $resT = pg_query($con,$sqlT);

											    if (pg_num_rows($resT) > 0) {
													$tabela = pg_fetch_result($resT, 0, 'tabela');
												}

											} else {
											    $sqlT = "
												SELECT  $coluna_tabela_preco AS tabela
												FROM    tbl_posto_linha
												JOIN    tbl_linha using(linha)
												WHERE   posto = $login_posto
												AND     fabrica = $login_fabrica
												{$cond_posto_linha}
												LIMIT   1
											    ";
											    $resT = pg_query($con,$sqlT);
												if (pg_num_rows($resT) > 0) {
													$tabela = pg_fetch_result($resT, 0, 0);
												}
											}
											if (strlen($tabela) > 0){
												if(strlen($peca_para) > 0){
													$sqlT = "SELECT preco FROM tbl_tabela_item WHERE tabela = $tabela AND peca = $peca_para";
													$resT = pg_query($con,$sqlT);
													$preco_para = (pg_num_rows($resT) == 1) ? number_format(pg_fetch_result($resT,0,0),2,',','.') : '';
												}

												$sqlT = "SELECT preco FROM tbl_tabela_item WHERE tabela = $tabela AND peca = $peca";
												$resT = pg_query($con,$sqlT);
												
												if (pg_num_rows($resT) == 1) {
													if ($login_fabrica == 156) {
														$preco = number_format(pg_fetch_result($resT,0,0),2,'.','');
													} else if ($login_fabrica != 147) {
														$preco = number_format(pg_fetch_result($resT,0,0),2,',','.');
													} else {
														$preco = pg_fetch_result($resT,0,0);
													}
						 						} else {
						 							$preco = '0.00';
						 						}
											}


										}
										/*
										 * Adicionado para não gerar erro quando fábrica retorna o preço da peça no cadastro_os.php
										 * Estava trazendo um NaN no calculo do valor total quando OS de Orçamento da Elgin Automação
										 * Acontece quando a peça não tem preço cadastrado
										 */
										if (strlen($preco) == 0) {
											$preco = "0.00";
										}

										if(strlen($peca_para) > 0){//hd_chamado=2574626
											$r["preco"] = $preco_para;
											$preco = ($preco_para > 0) ? $preco_para : $preco;
										}else{
											$r["preco"] = $preco;
										}

										$r['multiplo'] = $multiplo;
									}

									if($bloqueada_venda == 't' and isset($pedido)){
										$complemento_descricao = " Peça bloqueada para venda ";
										$classPeca = " ";
									}else{
										$complemento_descricao = "";
										$classPeca = " peca "; 
										if ((!in_array($login_fabrica, [35,186]) || (in_array($login_fabrica, [186]) && !isset($orcamento))) && ($peca_bloqueio_garantia == "t" || $peca_para_bloqueio_garantia == "t")) {
											$classPeca = " ";
										}
									}

									if ($login_fabrica == 157) { 
										$classPeca = " peca ";
									}

									if($login_fabrica == 35 and $peca_bloqueio_garantia == 't'){
										if($pedido != 't'){
											$r = array();
											$classPeca = "";
											$complemento_descricao .= "<br />Peça não atendida na garantia";
										}
									}else{
										$complemento_descricao = "";
									}

									if ($login_fabrica == 35 && !empty($peca_fora_de_linha) && $peca_bloqueio_garantia != 't') {
										$complemento_descricao .= "<br />Peça fora de linha, sujeito à análise do fabricante";
									}

									$style 		= "style='";
									$attr 		= "";
									$attr_check = "";
									if($login_fabrica == 191){
										unset($r['qtde']);
									}

									if ((!in_array($login_fabrica, [157, 186]) || (in_array($login_fabrica, [186]) && !isset($orcamento))) and empty($pedido)) { 
										if ($peca_bloqueio_garantia == "t" || $peca_para_bloqueio_garantia == "t") {
											$style 		.= "background-color: rgba(255, 246, 193, 4);";
											$attr_check .= "disabled";
											
											if ($peca_bloqueio_garantia == "t") {
												$complemento_descricao = "<br />Peça bloqueada para garantia";
											} elseif ($peca_para_bloqueio_garantia == "t") {
												$complemento_descricao = "<br />Peça \"PARA\" bloqueada para garantia";
											}
										}
									}

									if ($login_fabrica == 194){
										if (strlen($peca_alternativa) > 0){
											$style .= "background-color: #91C8FF";
											$complemento_descricao .= "<br/>Alternativa da Peça: Ref: $peca_ref_alt Desc: $peca_desc_alt";
										}else{
											$style = '';
										}
									}
									$style .= "'";

									if($selecionaPecas){
										echo "<tr json='",json_encode($r),"' $attr $style>";
									}else{
										echo "<tr $attr $style>";
									}

									if (in_array($login_fabrica, [184])) {
										echo "<td $style class='cursor_lupa tac $classPeca '>$posicao_coluna</td>";
									}
									
									if ($imagemPeca) {

										$xpecas  = $tDocs->getDocumentsByRef($peca, "peca");
										if (!empty($xpecas->attachListInfo)) {

											$a = 1;
											foreach ($xpecas->attachListInfo as $kFoto => $vFoto) {
											    $fotoPeca = $vFoto["link"];
											    if ($a == 1){break;}
											}
											echo "<td class='cursor_lupa' $style>
											       	<a href='".$fotoPeca."'><img src='".$fotoPeca."'></a>
												  </td>";
										} else {

											$local_jpg  = APPBACK."imagens_pecas/{$login_fabrica}/pequena/$peca.jpg";
											$local_jpeg = APPBACK."imagens_pecas/{$login_fabrica}/pequena/$peca.jpeg";
											if(file_exists($local_jpg)){
												if( in_array($login_fabrica, array(172)) ){
													echo "<td $style class='cursor_lupa'><a href='".APPBACK."imagens_pecas/11/media/$peca.jpg'><img src='".APPBACK."imagens_pecas/11/pequena/$peca.jpg'></a></td>";
												}else{
													echo "<td $style class='cursor_lupa'><a href='".APPBACK."imagens_pecas/{$login_fabrica}/media/$peca.jpg'><img src='".APPBACK."imagens_pecas/{$login_fabrica}/pequena/$peca.jpg'></a></td>";
												}
											}elseif(file_exists($local_jpeg)){
												if( in_array($login_fabrica, array(172)) ){
													echo "<td $style class='cursor_lupa'><a href='".APPBACK."imagens_pecas/11/media/$peca.jpg'><img src='".APPBACK."imagens_pecas/11/pequena/$peca.jpeg'></a></td>";
												}else{
													echo "<td $style class='cursor_lupa'><a href='".APPBACK."imagens_pecas/{$login_fabrica}/media/$peca.jpg'><img src='".APPBACK."imagens_pecas/{$login_fabrica}/pequena/$peca.jpeg'></a></td>";
												}
											}else{
												echo "<td $style class='cursor_lupa'>&nbsp</td>";
											}
										}

									}

									if(in_array($login_fabrica, array(160,163,176)) or $replica_einhell){
										echo "<td $style class='cursor_lupa tac $classPeca '>$posicao_coluna</td>";
									}
									if(in_array($login_fabrica, array(176))){
										echo "<td $style class='cursor_lupa tac $classPeca '>$ordem_coluna</td>";
									}
                                    //hd-3625122 - fputti
                                    if ($login_fabrica == 171) {
                                    	echo "<td $style class='cursor_lupa tac'>{$referencia_fabrica}</td>";
                                    }

									echo "<td $style class='cursor_lupa $classPeca'>{$referencia}</td>";
									echo "<td $style class='cursor_lupa $classPeca'>{$descricao} <span style='color:red;'>{$complemento_descricao}</span></td>";

									if (!isset($pedido) && !isset($preco_peca)) {
										if (!in_array($login_fabrica, array(176,175,179)))
										{
											echo "<td $style class='cursor_lupa tac $classPeca '>
												{$qtde}
												<input type='hidden' name='qtde_lista' class='qtde_lista' value='$qtde'>
											</td>";	
										}										
									} else {
										echo "<td $style class='cursor_lupa tac'>{$preco}</td>";
										if ($login_fabrica == 183 AND in_array($login_tipo_posto_codigo, array("Rev", "Rep"))){
											echo "<td $style class='cursor_lupa tac'>$qtde</td>";
										}
									}

									echo "<td $style class='cursor_lupa depara_para'>{$referencia_para}&nbsp;</td>";

									if ($login_fabrica == 158 && !empty($posto) && empty($pedido)) {
										if (strlen($peca_para) > 0) {
											echo "<td $style class='cursor_lupa tac' >$estoque_peca_para</td>";
										} else {
                                        	echo "<td $style class='cursor_lupa tac' >$estoque</td>";
                                        }
                                    }

                                    if (in_array($login_fabrica, array(178,183)) AND $page == "cadastro_os_revenda"){
                                    	if (empty($login_posto)){
                                    		$login_posto = $posto;
                                    	}

                                		$checked = "";
                                		$servico_realizado_lancado = "";
                                		$qtde_lancada = "";
                                		$defeito_peca = "";
										foreach ($info_pecas as $key_p => $value_p) {
											if ($peca == $value_p["id_peca"]){
												$checked = "checked";
												$servico_realizado_lancado = $value_p["servico_realizado"];
												$qtde_lancada = $value_p["qtde_lancada"];
												$defeito_peca = $value_p["defeito_peca"];
											}
										}
										
                                    	$res_sr = get_servico_realizado($tipo_atendimento);
                                    	$rows_sr = pg_num_rows($res_sr);
                                    	echo "<td class='cursor_lupa tac'>
	                                    		<input type='text' name='qtde_lancada' class='qtde_lancada numeric' value='$qtde_lancada' style='width: 55px;' >
                                    			<input type='hidden' name='posicao_parent' class='posicao_parent' value='$posicao' >
	                                    	</td>";
	                                	echo "<td class='cursor_lupa tac'>
	                                			<select style='width:100px;' name='servico_realizado' class='servico_realizado'>";
	                                				for($sr = 0; $sr < $rows_sr; $sr++) {
		                                                $servico_realizado = pg_fetch_result($res_sr, $sr, "servico_realizado");
		                                                $descricao         = pg_fetch_result($res_sr, $sr, "descricao");
		                                                $troca_de_peca     = pg_fetch_result($res_sr, $sr, "troca_de_peca");
		                                                $gera_pedido       = pg_fetch_result($res_sr, $sr, "gera_pedido");
		                                                $peca_estoque      = pg_fetch_result($res_sr, $sr, "peca_estoque");
		                                                $selected = '';
		                                                
		                                                if ($servico_realizado == $servico_realizado_lancado){
		                                                	$selected = "selected";
		                                                }else{
		                                                	$selected ="";
		                                                }

		                                                $sql_estoque = "
	                                                        SELECT qtde 
	                                                        FROM tbl_estoque_posto 
	                                                        WHERE fabrica = $login_fabrica 
	                                                        AND posto = $login_posto 
	                                                        AND peca = {$peca}";
	                                                    $res_estoque = pg_query($con, $sql_estoque);
	                                                    $disabled_estoque = "";
	                                                    if (pg_num_rows($res_estoque) > 0){
	                                                        if ($peca_estoque == 'f'){
	                                                            $disabled_estoque = "disabled";
	                                                        }
	                                                    }else if ($peca_estoque == 't'){
	                                                        $disabled_estoque = "disabled";
	                                                    }

	                                                    if (strtolower($descricao) == "troca de produto" OR strtolower($descricao) == "cancelado"){
	                                                        continue;
	                                                    }
		                                                echo "<option {$disabled_estoque} {$selected} value='{$servico_realizado}' troca_de_peca='{$troca_de_peca}' gera_pedido='{$gera_pedido}' peca_estoque='{$peca_estoque}' >".$descricao."</option>";
		                                            }
                                		echo"   </select>
	                                		</td>
	                                		";

	                                	if ($login_fabrica == 183){
	                                	?>
	                                		<td class='cursor_lupa tac'>
	                                			<select style='width:100px;' name='defeito_peca' class='defeito_peca'>
	                                				<option value="">Selecione</option>
	                                			<?php
	                                				$res_df = get_defeito_peca();
                                					
	                                				for($sr = 0; $sr < pg_num_rows($res_df); $sr++) {
														
														$defeito   = pg_fetch_result($res_df, $sr, "defeito");
		                                                $descricao = pg_fetch_result($res_df, $sr, "descricao");
		                                                $selected = '';
		                                                
		                                                if ($defeito_peca == $defeito){
		                                                	$selected = "selected";
		                                                }else{
		                                                	$selected ="";
		                                                }

		                                                echo "<option {$selected} value='{$defeito}'>".$descricao."</option>";
		                                            }
	                                            ?>
                                				</select>
	                                		</td>
		                                <?php
	                                	}
	                                }
									
									if ($selecionaPecas and $bloqueada_venda == 't' and isset($pedido)){
										echo "<td $style class='cursor_lupa tac' ><input type='hidden' name='lancar_pecas' value='t' /></td>";
									} else if ($selecionaPecas) {
										if($login_fabrica == 35){
											if($peca_bloqueio_garantia == "f" || $pedido == 't'){
												echo "<td $style class='cursor_lupa tac' ><input type='checkbox' name='lancar_pecas' value='t' $attr_check/></td>";
											}else{
												echo "<td $style class='cursor_lupa tac' ></td>";
											}

										}else{
											echo "<td $style class='cursor_lupa tac' ><input type='checkbox' class='checkbox_lancar' name='lancar_pecas' value='t' $checked $attr_check/></td>";
										}
										
									}
								} ?>
							</tbody>
						</table>
					</div>
				<? } else {
					echo '<br /><br /><br /><br /><div class="alert alert_shadowbox">
					    <h4>'.traduz('Nenhum resultado encontrado').'</h4>
					</div>';
				}
			} else {
				echo '<div class="alert alert_shadowbox">
					<h4>'.traduz('Informe um produto para pesquisar a lista básica!').'</h4>
				</div>';
			} ?>
		</div>
<?

		if (!empty($vista_explodida)) {

			if (in_array($login_fabrica, array(169,170))){
				if(strlen(trim($produto_serie)) > 0){
		            $cond_serie = " AND tbl_comunicado.serie = '$produto_serie' ";
		            $cond_s_serie = " AND tbl_comunicado.serie IS NULL ";
		        }else{
		            $cond_serie = " AND tbl_comunicado.serie IS NULL ";
		            $cond_s_serie = " AND tbl_comunicado.serie IS NULL ";
		        }
			}

			$sem_ve = true;
		    if (!empty($produto)) {
				$sql = "
					SELECT DISTINCT comunicado, fabrica, tbl_produto.referencia,
						   tbl_comunicado.produto, extensao, tipo
					  FROM tbl_comunicado
					  JOIN tbl_produto ON fabrica_i = fabrica
					   AND tbl_produto.produto = tbl_comunicado.produto
					 WHERE fabrica = $fabrica_comunicado
					   AND tipo = 'Vista Explodida'
					   AND tbl_produto.produto = '$produto'
					   AND tbl_comunicado.ativo IS TRUE
					   $cond_serie
	        	";
				$res = pg_query($con,$sql);
				if (!pg_num_rows($res)) {
		            $sql = "
					SELECT DISTINCT comunicado, fabrica, tbl_produto.referencia,
						   tbl_comunicado.produto, extensao, tipo
					  FROM tbl_comunicado
					  JOIN tbl_produto ON fabrica_i = fabrica
					   AND tbl_produto.produto = tbl_comunicado.produto
					 WHERE fabrica = $fabrica_comunicado
					   AND tipo = 'Vista Explodida'
					   AND tbl_produto.produto = '$produto'
					   AND tbl_comunicado.ativo IS TRUE
					   $cond_s_serie
            			AND versao = '000';
        			";
		            $res = pg_query($con,$sql);
		        }

		        if (pg_num_rows($res) > 0) {
					$comunicado = pg_fetch_result($res, 0, comunicado);

					$anexaS3 = new anexaS3("ve", (int) $fabrica_comunicado);

					if ($anexaS3->temAnexos($comunicado)) {
						$link = $anexaS3->url;
						$sem_ve = false;
					} else {
						$sem_ve = true;
					}
				}
			}
		}

		if($selecionaPecas) { ?>
			<div style="position:fixed;bottom:0px;width:100%;height:50px;">
				<div class="tac tc_formulario" style="padding:10px">
					<button type="button" class="btn btn-success" onclick="retornaSelecionadas()">
						<?= traduz('Lançar peças Selecionadas') ?>
					</button>
					<?php
					if (!empty($vista_explodida) && $sem_ve == false) {
					?>
						<a id="vista_explodida" href="<?= $link; ?>" class="btn btn-info" target="_blank"><?= traduz('Vista Explodida') ?></a>
					<?php
					}
					?>
				</div>
			</div>
			<script type="text/javascript">
				<?php if (in_array($login_fabrica, array(178,183))){ ?>
					$(function(){
						$(".numeric").numeric();
					});
					$(".checkbox_lancar").click(function(){
						if ($(this).is(":checked") == false){
							$(this).parents("tr").find(".qtde_lancada").val("");
							$(this).parents("tr").find(".servico_realizado").val("");
							$(this).parents("tr").find(".defeito_peca").val("");
						}
					});
					
					var retornaSelecionadas = function(){
						
						var pecas = [];
						var qtde_servico = {};
						var dados_pecas = "";
						var error = "";
						var text_error = [];
						var posicao = $(".posicao_parent").val();
					
						$("#border_table table input[type=checkbox]:checked").each(function(){

							var peca = $($(this).parents("tr[json]")[0]).attr("json");
							var qtde_lancada = $($(this).parents("tr[json]")[0]).find(".qtde_lancada").val();
							var qtde_lista = $($(this).parents("tr[json]")[0]).find(".qtde_lista").val();
							var servico_realizado = $($(this).parents("tr[json]")[0]).find(".servico_realizado").val();
							
							var defeito_peca = $($(this).parents("tr[json]")[0]).find(".defeito_peca").val();

							<?php if ($page == "cadastro_os_revenda"){ ?>
								if (qtde_lancada != "" && qtde_lancada != undefined && qtde_lancada > qtde_lista){
									error = "true";
									text_error.push("Exitem peças lançadas com quantidade superior a lista básica");
									return;
								}

								if (qtde_lancada == "" || qtde_lancada == undefined){
									error = "true";
									text_error.push("Exitem peças selecionadas sem informar a quantidade lançada");
									return;
								}

								if (servico_realizado == "" || servico_realizado == undefined){
									error = "true";
									text_error.push("Exitem peças selecionadas sem informar o serviço realizado");
									return;
								}

								<?php if ($login_fabrica == 183){ ?>
									if (defeito_peca == "" || defeito_peca == undefined){
										error = "true";
										text_error.push("Exitem peças selecionadas sem informar o defeito");
										return;
									}
									qtde_servico["defeito_peca"] = defeito_peca;
								<?php } ?>
								qtde_servico["qtde_lancada"] = qtde_lancada;
								qtde_servico["servico_realizado"] = servico_realizado;
							<?php } ?>

							peca = $.parseJSON(peca);
							var dados_pecas = $.extend( peca, qtde_servico );
							pecas.push(dados_pecas);
						});

						if (error == "true"){
							var text_error = Array.from(new Set(text_error))
							text_error = text_error.join("<br/>");

							$(".alert_pecas").append("<h4>"+text_error+"</h4>");
							$(".alert_pecas").attr("style", "display:block").delay(3000).fadeTo("slow","0").queue(function(){
								$(".alert_pecas").find("h4").remove();
								$(".alert_pecas").attr("style", "display:none").dequeue();
							});
							return;
						}
						window.parent.retorna_pecas(pecas, posicao);
						window.parent.Shadowbox.close();
					}
					<?php if ($page != "cadastro_os_revenda"){ ?>
						$(function(){
							$(".peca").click(function(){
								$(this).parent("tr").find("input[type=checkbox]").attr({checked: "checked"});
								$(".btn-success").click();
							});
						});
					<?php } ?>
				<?php }else{ ?>
					var retornaSelecionadas = function(){
						var pecas = [];
						$("#border_table table input[type=checkbox]:checked").each(function(){
							var peca = $($(this).parents("tr[json]")[0]).attr("json");
							pecas.push($.parseJSON(peca));
						});
						window.parent.retorna_pecas(pecas);
						window.parent.Shadowbox.close();
					};

					$(function(){
						$(".peca").click(function(){
							$(this).parent("tr").find("input[type=checkbox]").attr({checked: "checked"});
							$(".btn-success").click();
						});
					});
				<?php } ?>
			</script>
		<? } ?>
	</body>
</html>
