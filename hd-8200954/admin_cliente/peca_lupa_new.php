<?php

$areaAdminCliente = preg_match('/\/admin_cliente\//',$_SERVER['PHP_SELF']) > 0 ? true : false;
define('ADMCLI_BACK', ($areaAdminCliente == true)?'../admin/':'../');

include_once '../dbconfig.php';
include_once '../includes/dbconnect-inc.php';

if ($areaAdminCliente == true) {
    include 'autentica_admin.php';
    include_once '../funcoes.php';
} else {
    $admin_privilegios = "gerencia";
    include_once '../includes/funcoes.php';
    include '../autentica_admin.php';
    include "../monitora.php";
}

include_once "../class/tdocs.class.php";

if ($_REQUEST["posicao"]) {
	$posicao   = $_REQUEST["posicao"];
}
$tDocs     = new TDocs($con, $login_fabrica);
$parametro = $_REQUEST["parametro"];
$valor     = utf8_decode(trim(urldecode($_REQUEST["valor"])));
$campo     = trim($_REQUEST["campo"]);

if($_REQUEST['marca']){
	$marca = $_REQUEST['marca'];
}

if ($_REQUEST["preco_peca"]) {
	$preco_peca   = $_REQUEST["preco_peca"];
}

if (empty($preco_peca) and $_REQUEST["preco"]) {
	$preco_peca   = $_REQUEST["preco"];
}

$callcenter = $_REQUEST['callcenter'];

if($_REQUEST['de'] == 'true'){
	$de = true;
}

if($_REQUEST['pecaPara']){
	$pecaPara = $_REQUEST['pecaPara'];
}

if($_REQUEST['pecaAcao']){
	$pecaAcao = $_REQUEST['pecaAcao'];
}

if($_REQUEST['para'] == 'true'){
 	$para = true;
}

if($_REQUEST['de_para'] == 'true'){
 	$de_para = true;
}

if($_REQUEST['tabela']) {
 	$tabela = $_REQUEST['tabela'];
}

if($_REQUEST['revisao']) {
 	$revisao = $_REQUEST['revisao'];
}

if ($_REQUEST["pedido"]) {
	$pedido  = $_REQUEST["pedido"];
}

$tipo_pedido = '';
if ($_REQUEST["tipo-pedido"]) {
    $tipo_pedido = $_REQUEST["tipo-pedido"];
}

if($_REQUEST['catalogo_peca']){
	$catalogo_peca = $_REQUEST['catalogo_peca'];
}

$condicaoItemAparencia = "";
$whereItemAparencia    = "";
if ($_REQUEST["pesquisa_produto_acabado"] || in_array($login_fabrica, array(119,129))) {
	$pesquisa_produto_acabado = true;
}
if(!empty($login_posto)){
	$condicaoItemAparencia = " tbl_posto.posto = $login_posto ";
	$posto = $login_posto;
}else {

	$posto_codigo = $_REQUEST['posto_codigo'];
	$posto_nome   = $_REQUEST['posto_nome'];

	if(!empty($posto_codigo) && !empty($posto_nome)){
		$condicaoItemAparencia = " tbl_posto_fabrica.codigo_posto = '$posto_codigo' AND tbl_posto.nome = '$posto_nome' ";
	}

    if ($pesquisa_produto_acabado !== true and empty($callcenter)) {
        $whereProdutoAcabado = " AND tbl_peca.produto_acabado IS NOT TRUE";
    } else {
        $whereProdutoAcabado = " ";
    }

	$bloquea_bloqueada_venda = true;

	if (!empty($tipo_pedido) and $login_fabrica == 153) {
        $sqlTP = "SELECT * from tbl_tipo_pedido where tipo_pedido = $tipo_pedido AND pedido_faturado";
        $qryTP = pg_query($con, $sqlTP);

        if (pg_num_rows($qryTP) == 0) {
            $bloquea_bloqueada_venda = false;
        }
	}
}


if(strlen($condicaoItemAparencia) > 0) {
	$sql = "SELECT  tbl_posto_fabrica.item_aparencia, desconto,posto
					FROM tbl_posto
					JOIN tbl_posto_fabrica USING(posto)
					WHERE {$condicaoItemAparencia}
					AND   tbl_posto_fabrica.fabrica = $login_fabrica";
	$res = pg_query($con,$sql);

	if (pg_num_rows ($res) > 0) {
		$item_aparencia = pg_fetch_result($res, 0, 'item_aparencia');
		$desconto = pg_fetch_result($res, 0, 'desconto');
		$posto = pg_fetch_result($res, 0, 'posto');
	}
}else{
	$item_aparencia = '';
}



if ($item_aparencia == 'f'){
	$whereItemAparencia = "AND tbl_peca.item_aparencia IS FALSE";
}

//pega desconto do tipo posto, se o descontro do posto for zero.
if($login_fabrica == 160 or $replica_einhell){
	if(strlen(trim($desconto))<=0 or $desconto == 0){
		$sql_desc_tipo_posto = " select tbl_tipo_posto.descontos[1], tbl_tipo_posto.descricao from tbl_posto_fabrica inner join tbl_tipo_posto on tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto  where tbl_posto_fabrica.fabrica = $login_fabrica and tbl_posto_fabrica.codigo_posto = '$posto_codigo' ";
		$res_desc_tipo_posto = pg_query($con, $sql_desc_tipo_posto);
		if(pg_num_rows($res_desc_tipo_posto)>0){
			$desconto = pg_fetch_result($res_desc_tipo_posto, 0, $descontos);
		}
	}
}

if ($imagemPeca) {

    if (preg_match("/\/admin\//", $_SERVER["PHP_SELF"])) {
        $caminho_img_peca = "../";
    }else{
        $caminho_img_peca = "";
    }
}

?>
<!DOCTYPE html />
<html>
	<head>
		<meta http-equiv=pragma content=no-cache>
		<link href="../admin/bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
    	<link href="../admin/bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
    	<link href="../admin/css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
    	<link href="../admin/bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />
		<link href="../admin/plugins/dataTable.css" type="text/css" rel="stylesheet" />
		<link href="../admin/plugins/font_awesome/css/font-awesome.css" type="text/css" rel="stylesheet" />
		<link href='../admin/plugins/select2/select2.css' type='text/css' rel='stylesheet' />

		<script src="../admin/plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
		<script src="../admin/bootstrap/js/bootstrap.js"></script>
		<script src="../admin/plugins/dataTable.js"></script>
		<?php
		if (!$catalogo_peca) {
		?>
			<script src="../admin/plugins/resize.js"></script>
			<script src="../admin/plugins/shadowbox_lupa/lupa.js"></script>
		<?php
		}
		?>
		<script src='../admin/plugins/select2/select2.js'></script>
        <? if ($imagemPeca) { ?>
            <script type='text/javascript' src='<?=$caminho_img_peca?>js/FancyZoom.js'></script>
            <script type='text/javascript' src='<?=$caminho_img_peca?>js/FancyZoomHTML.js'></script>
        <? } ?>
        <script>
            $(function () {
				$.dataTableLupa();
				
				<?php 
				if ($imagemPeca) { 
				?>
                    setupZoom();
				<?php
				} 
				
				if ($catalogo_peca) {
				?>
					$('select[name=produto]').select2();
					
					$('input[type=number]').on('keyup', function() {
						let v = $(this).val().replace(/\D/g, '');
						$(this).val(v);
					});
					
					$('button.retorna-peca').on('click', function() {
						let json = $(this).data('json');
						json = json.replace(/\\\"/g, '\"');
						json = JSON.parse(json);
						let qtde = parseInt($(this).prevAll('.div-preco').find('input[type=number]').val());
						
						if (isNaN(qtde) || qtde == 0) {
							qtde = 1;
						}
						
						json.qtde = qtde;
						
						window.parent.retorna_peca(json, true);
					});
				<?php
				}
				?>
            });
		</script>
	</head>

	<body>
		<div id="container_lupa" style="overflow-y:auto;">
			<?php
			if ($catalogo_peca) {
			?>
				<div class="page-header">
				  <h2><small>Filtros</small></h2>
				</div>
			<?php
			} else {
			?>
				<div id="topo">
					<img class="espaco" src="../admin/imagens/logo_new_telecontrol.png">
					<img class="lupa_img pull-right" src="../admin/imagens/lupa_new.png">
				</div>
				<br /><hr />
			<?php	
			}
			?>
			<form action="<?=$_SERVER['PHP_SELF']?>" method='POST' >
				<div class="row-fluid">
					<?php
					if ($catalogo_peca) {
					?>
						<div class="span1"><h3><small>Peça:</small></h3></div>
						<div class="span2">
					<?php
					} else {
					?>
						<div class="span1"></div>
						<div class="span4">
					<?php
					}
					?>
						<input type="hidden" name="posicao" class="span12" value='<?=$posicao?>' />
						<?
						if(isset($pecaAcao)){
							echo "<input type='hidden' name='pecaAcao' value='<?=$pecaAcao?>' />";
						}

						if(isset($pecaPara)){
							echo "<input type='hidden' name='pecaPara' value='<?=$pecaPara?>' />";
						}
						
						if(isset($preco_peca)){
							echo "<input type='hidden' name='preco' value='".$preco."' />";
						}
						
						if(isset($tipo_pedido)){
							echo "<input type='hidden' name='tipo_pedido' value='".$tipo_pedido ."' />";
						}
						
						if(isset($catalogo_peca)){
							echo "<input type='hidden' name='catalogo_peca' value='".$catalogo_peca ."' />";
						}
						
						if(isset($tabela)){
							echo "<input type='hidden' name='tabela' value='".$tabela."' />";
						}

						if(isset($revisao)){
							echo "<input type='hidden' name='revisao' value='<?=$revisao?>' />";
						}

						if(isset($pedido)){
							echo "<input type='hidden' name='pedido' value='<?=$pedido?>' />";
						}
						if ($pesquisa_produto_acabado === true) {
						?>
							<input type='hidden' name='pesquisa_produto_acabado' value='<?=$pesquisa_produto_acabado?>' />
						<?php
						}
						?>

						<?php 
						if(isset($posto_codigo)){
						?>
							<input type="hidden" name="posto_codigo"  value='<?=$posto_codigo?>' />
							<input type="hidden" name="posto_nome"  value='<?=$posto_nome?>' />
						<?php
						}
						?>

						<select name="parametro" >
							<option value="referencia" <?=($parametro == "referencia") ? "SELECTED" : ""?> >Referência</option>
							<option value="descricao" <?=($parametro == "descricao") ? "SELECTED" : ""?> >Descrição</option>
							<?php if ($login_fabrica == 175){ ?>
							<option value="serie_peca" <?=($parametro == "serie_peca") ? "SELECTED" : ""?> >Número de série</option>
							<?php } ?>
						</select>
					</div>
					<div class="span4">
						<input type="text" name="valor" class="span12" value="<?=$valor?>" />
					</div>
					<?php
					if ($catalogo_peca) {
					?>
						</div>
						<div class='row-fluid'>
							<div class='span1'><h3><small>Lista básica:</small></h3></div>
							<div class='span6'>
								<select name='produto' class='span12'>
									<option value=''>Produto</option>
									<?php
									$sqlProduto = "
										SELECT p.produto, p.referencia, p.descricao, p.voltagem 
										FROM tbl_produto p
										INNER JOIN tbl_linha l ON l.linha = p.linha AND l.fabrica = {$login_fabrica} AND l.ativo IS TRUE
										INNER JOIN tbl_posto_linha pl ON pl.linha = l.linha AND pl.posto = {$posto}
										WHERE p.fabrica_i = {$login_fabrica}
										AND p.ativo IS TRUE
										ORDER BY p.descricao ASC
									";
									$resProduto = pg_query($con, $sqlProduto);
									
									if (pg_num_rows($resProduto) > 0) {
										while ($rowProduto = pg_fetch_object($resProduto)) {
											$selected = ($rowProduto->produto == $produto) ? 'selected' : null;
											echo "<option value='{$rowProduto->produto}' {$selected} >{$rowProduto->referencia} - {$rowProduto->descricao} {$rowProduto->voltagem}</option>";
										}
									}
									?>
								</select>
							</div>
						</div>
						<div class='row-fluid'>
							<div class='span1'></div>
							<div class='span2'>
								<button type='button' class='btn btn-primary btn-block' onclick="$(this).parents('form').submit();"><i class='fa fa-search'></i>Pesquisar</button>
							</div>
							<div class='span2'>
								<button type='button' class='btn btn-warning btn-block' onclick="$('select[name=produto]').val(''); $('input[name=valor]').val(''); $(this).parents('form').submit();"><i class='fa fa-times'></i>Limpar filtros</button>
							</div>
						</div>
					<?php
					} else {
					?>
							<div class="span2">
								<button type="button" class="btn pull-right" onclick="$(this).parents('form').submit();">Pesquisar</button>
							</div>
						</div>
					<?php
					}
					?>
				
			</form>
			<?
			if (strlen($valor) >= 3 || $catalogo_peca) {
				if (strlen($valor) >= 3) {
					$valor = strtoupper($valor);
					$valor = retira_acentos($valor);
					$valor = str_replace(array(".", ",", "-", "/", " "), "", $valor);

					//hd-3625122 - fputti
					$condReferenciaFabrica = "";
					if ($login_fabrica == 171) {
						$condReferenciaFabrica = " OR tbl_peca.referencia_fabrica ILIKE '%$valor%'";
					}

					switch ($parametro) {
						case 'referencia':
							$whereAdc = "AND ( UPPER(tbl_peca.referencia_pesquisa) LIKE '%{$valor}%' $condReferenciaFabrica)";
							break;

						case 'descricao':
								$whereAdc = "AND UPPER(fn_retira_especiais(tbl_peca.descricao)) LIKE '%{$valor}%'";
							break;
						case 'referencia_descricao':
							$whereAdc = "AND UPPER(tbl_peca.descricao) ILIKE UPPER('%' || fn_retira_especiais('{$valor}') || '%') OR UPPER(tbl_peca.referencia_pesquisa) ILIKE UPPER('%' || fn_retira_especiais('{$valor}') || '%')  ";
							break;
					}
					
				}
				
				if ($login_fabrica == 175 AND $parametro == "serie_peca"){
					$joinSeriePeca = " JOIN tbl_numero_serie_peca ON tbl_numero_serie_peca.peca = tbl_peca.peca AND tbl_numero_serie_peca.fabrica = {$login_fabrica} ";
					$whereAdc = "AND tbl_numero_serie_peca.serie_peca = '{$valor}' ";
				}

				if(!empty($pedido)) {
					$joinPed = " JOIN tbl_pedido_item ON tbl_peca.peca = tbl_pedido_item.peca and tbl_pedido_item.pedido = $pedido ";
				}

				if($versao){
					$joinVer = "INNER JOIN tbl_lista_basica ON tbl_lista_basica.peca = tbl_peca.peca AND tbl_lista_basica.fabrica = {$login_fabrica} ";
					$whereVer = " AND UPPER(tbl_lista_basica.type) = UPPER('{$versao}') ";
				}

				if(strlen($marca)>0){
					$selMarca = ", tbl_marca.nome as marca_nome";
					$joinMarca = "	INNER JOIN tbl_lista_basica ON tbl_lista_basica.peca = tbl_peca.peca AND tbl_lista_basica.fabrica = {$login_fabrica}
									INNER JOIN tbl_produto ON tbl_produto.produto = tbl_lista_basica.produto AND tbl_produto.fabrica_i = {$login_fabrica}
									INNER JOIN tbl_marca ON tbl_marca.marca = tbl_produto.marca AND tbl_marca.fabrica = {$login_fabrica} ";
					$condMarca = "	AND tbl_produto.marca = {$marca} ";
				}
				
				if(strlen($preco_peca)>0 && !$catalogo_peca){
					$selPreco = ", tbl_tabela_item.preco ";
					if (!empty($posto)) {
						$joinPostoLinhaWhere = " AND tbl_posto_linha.posto = {$posto} ";
					}
					$campo_tabela = ($login_fabrica == 161) ? "tabela_posto":"tabela";
					$join_lista = (in_array($login_fabrica, array(119,153))) ? " LEFT " : " INNER ";

					if(strlen($marca) == 0 ) {
						$join_lb = "$join_lista JOIN tbl_lista_basica ON tbl_lista_basica.peca = tbl_peca.peca AND tbl_lista_basica.fabrica = {$login_fabrica}
                                    $join_lista JOIN tbl_produto ON tbl_produto.produto = tbl_lista_basica.produto AND tbl_produto.fabrica_i = {$login_fabrica} ";
					}

					$joinPreco = " $join_lb
							$join_lista JOIN tbl_posto_linha ON tbl_posto_linha.linha = tbl_produto.linha {$joinPostoLinhaWhere}
						    $join_lista JOIN tbl_tabela ON tbl_tabela.tabela = tbl_posto_linha.$campo_tabela AND tbl_tabela.fabrica = {$login_fabrica}
							LEFT JOIN tbl_tabela_item ON tbl_tabela_item.tabela = tbl_tabela.tabela AND tbl_tabela_item.peca = tbl_peca.peca ";
				}
				if (!empty($produto)) {
					$distinct = "DISTINCT";
					$joinLb = "INNER JOIN tbl_lista_basica ON tbl_lista_basica.peca = tbl_peca.peca AND tbl_lista_basica.produto = {$produto} AND tbl_lista_basica.fabrica = {$login_fabrica}";
				}

				if ((isset($whereAdc) && strlen($valor) >= 3) || $catalogo_peca) {
					if(!isset($_REQUEST["sem-de-para"])){
						$selDepara = ", tbl_depara.peca_para,
									tbl_peca_para.referencia AS para,
									tbl_peca_para.descricao AS para_descricao";
						$lefDepara = "LEFT JOIN tbl_depara ON tbl_depara.fabrica = $login_fabrica AND tbl_depara.peca_de = tbl_peca.peca AND (tbl_depara.expira > current_date OR tbl_depara.expira IS NULL)
								LEFT JOIN tbl_peca AS tbl_peca_para ON tbl_peca_para.fabrica = $login_fabrica AND tbl_peca_para.peca = tbl_depara.peca_para";
					}

					if ($pesquisa_produto_acabado !== true && empty($callcenter)) {
						$whereProdutoAcabado = "AND tbl_peca.produto_acabado IS NOT TRUE";
					}
					if ($login_fabrica == 1) {
						$condAtivo = "AND (tbl_peca.ativo is true OR tbl_peca.informacoes = 'INDISPL')";
					}else{
						$condAtivo = "AND tbl_peca.ativo is true";
					}
					
					if ($catalogo_peca) {
						$whereBloqueadaVenda = "AND tbl_peca.bloqueada_venda IS NOT TRUE";
					}

					$sql = "SELECT
								DISTINCT
								tbl_peca.peca,
								tbl_peca.referencia_fabrica,
								tbl_peca.referencia,
								fn_retira_especiais(tbl_peca.descricao) AS descricao,
								tbl_peca.ipi,
								tbl_peca.origem,
								tbl_peca.estoque,
								tbl_peca.multiplo,
								tbl_peca.bloqueada_venda,
								tbl_peca.unidade,
								tbl_peca.produto_acabado,
								tbl_peca.ativo
								{$selDepara}
								{$selMarca}
								{$selPreco}
							FROM tbl_peca
							JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_peca.fabrica
							{$lefDepara}
							{$joinMarca}
							{$joinVer}
							{$joinPreco}
							{$joinSeriePeca}
							{$joinLb}
							$joinPed
							WHERE tbl_peca.fabrica = {$login_fabrica}
							AND tbl_peca.ativo is true
							{$whereAdc}
							{$whereVer}
							{$condMarca}
							{$whereItemAparencia}
							{$whereProdutoAcabado}
							{$condAtivo}
							{$whereBloqueadaVenda}";
					$res = pg_query($con, $sql);
					$rows = pg_num_rows($res);
					if ($rows > 0) {
						if ($catalogo_peca) {
						?>
							<div class='row-fluid'>
						<?php
						} else {
						?>
						<div id="border_table">
							<table class="table table-striped table-bordered table-hover table-lupa" >
								<thead>
									<tr class='titulo_coluna'>
										<? if ($imagemPeca) { ?>
											<th>Imagem</th>
										<? } 

										//hd-3625122 - fputti
										if ($login_fabrica == 171) {
											echo "<th>Referência Fábrica</th>";
										}

										?>

										<th>Referência</th>
										<th>Descrição</th>
										<? if(isset($preco_peca) or $tabela){ echo "<th>Preço</th>";}?>
										<? if(strlen($marca)){ echo "<th>Marca</th>";}?>
									</tr>
								</thead>
								<tbody>
						<?php
						}
						
						$col = 0;
						
						for ($i = 0 ; $i <$rows; $i++) {
							$peca            = pg_fetch_result($res, $i, 'peca');
							$referencia      = pg_fetch_result($res, $i, 'referencia');
							$descricao       = pg_fetch_result($res, $i, 'descricao');
							$ipi             = pg_fetch_result($res, $i, 'ipi');
							$peca_para       = trim(pg_fetch_result($res,$i,'peca_para'));
							$para            = trim(pg_fetch_result($res,$i,'para'));
							$para_descricao  = trim(pg_fetch_result($res,$i,'para_descricao'));
							$multiplo        = pg_fetch_result($res, $i, 'multiplo');
							$origem          = pg_fetch_result($res, $i, 'origem');
							$estoque         = pg_fetch_result($res, $i, 'estoque');
							$unidade         = pg_fetch_result($res, $i, 'unidade');
							$ativo           = pg_fetch_result($res, $i, 'ativo');
							$produto_acabado = pg_fetch_result($res, $i, 'produto_acabado');
							$bloqueada_venda = pg_fetch_result($res, $i, 'bloqueada_venda');

							//hd-3625122 - fputti
							$referencia_fabrica = trim(pg_fetch_result($res, $i, 'referencia_fabrica'));

							if (false === $bloquea_bloqueada_venda) {
								$bloqueada_venda = 'f';
							}

							if(strlen($marca)){
								$marca_nome     = pg_fetch_result($res, $i, 'marca_nome');
							}
							if(isset($preco_peca)){
								$preco = pg_fetch_result($res, $i, 'preco');
							}

							if ($tabela) {
								if (empty($para) || $catalogo_peca) {
									$sqlT = "SELECT preco FROM tbl_tabela_item WHERE tabela = $tabela AND peca = $peca";
									$resT = pg_query($con,$sqlT);

									$preco = (pg_num_rows($resT) == 1) ? pg_fetch_result($resT,0,0) :  '';
								}else{
									$sqlT = "SELECT preco FROM tbl_tabela_item WHERE tabela = $tabela AND peca = $peca_para";
									$resT = pg_query($con,$sqlT);

									$preco = (pg_num_rows($resT) == 1) ? pg_fetch_result($resT,0,0) : '';
								}
							}

							if ($login_fabrica == 91) {

								if (empty($para)) {
									$xdescricao = str_replace("'", " ", $descricao);
									$r = array(
										"peca"      => $peca                    ,
										"descricao" => utf8_encode($xdescricao)  ,
										"referencia" => utf8_encode($referencia)
									);
								}else{
									$xdescricao = str_replace("'", " ", $para_descricao);
									$r = array(
										"peca"      => $peca_para                    ,
										"descricao" => utf8_encode($xdescricao)  ,
										"referencia" => utf8_encode($para)
									);
								}


							}else{
								if (empty($para)) {
									$r = array(
										"peca"      => $peca                    ,
										"descricao" => utf8_encode($descricao)  ,
										"referencia" => utf8_encode($referencia),
										"referencia_fabrica" => utf8_encode($referencia_fabrica)
									);
								}else{
									$r = array(
										"peca"      => $peca_para                    ,
										"descricao" => utf8_encode($para_descricao)  ,
										"referencia" => utf8_encode($para),
										"referencia_fabrica" => utf8_encode($referencia_fabrica)
									);
								}

							}

							if (strlen($posicao) > 0) {
								$r["posicao"] = $posicao;
							}

							if(strlen($campo) > 0){
								$r["campo"] = $campo;
							}

							if(isset($pecaAcao)){
								$r["pecaAcao"] = $pecaAcao;
							}

							if(isset($pecaPara)){
								$r["pecaPara"] = $pecaPara;
							}

							if($de == true){
								$r['de'] = $de;
							}

							if(strlen($multiplo)){
								$r['multiplo'] = $multiplo;
							}

							if(strlen($ipi)){
								$r['ipi'] = $ipi;
							}

							if($para == true){
								$r['para'] = $para;
							}

							if($de_para == true){
								$r['de_para'] = $de_para;
							}

							if(isset($preco_peca) || isset($tabela)) {
								$r['preco'] = number_format($preco,2,",",".");
							}


							if (isset($revisao)) {
								$r["revisao"] = $revisao;
							}

							$r["estoque"] = $estoque;

							$r["produto_acabado"] = $produto_acabado;

							if ($catalogo_peca) {
							?>
								<div class='span3' style='border: 2px solid #ECECEC; border-radius: 8px; padding: 2px; margin-bottom: 10px;' >
									<div style='text-align: center; width: 100%; height: 200px;'>
										<?php
										if ($imagemPeca) {
											$xpecas  = $tDocs->getDocumentsByRef($peca, "peca");
											
											if (!empty($xpecas->attachListInfo)) {
												$a = 1;
												foreach ($xpecas->attachListInfo as $kFoto => $vFoto) {
													$fotoPeca = $vFoto["link"];
													if ($a == 1){break;}
												}
												
												if (!empty($fotoPeca)) {
													echo "<a href='".$fotoPeca."'><img src='".$fotoPeca."' style='max-height: 200px;' class='img-polaroid'></a>";
												} else {
													echo "<img src='../imagens/sem_imagem.jpg' style='max-height: 200px;' class='img-polaroid'>";
												}
											} else {
												echo "<img src='../imagens/sem_imagem.jpg' style='max-height: 200px;' class='img-polaroid'>";
											}
										}
										?>
									</div>
									<br />
									<?php
									$label_peca = "{$referencia} - {$descricao}";
									
									if(strlen($peca_para) > 0){
										$title_peca = $label_peca;
										
										if (strlen($label_peca) >= 60) {
											$label_peca = substr($label_peca, 0, 60).'...';
										}
										
										$label_peca_para = "{$para} - {$para_descricao}";
										$title_peca_para = $label_peca_para;
										
										if (strlen($label_peca_para) >= 60) {
											$label_peca_para = substr($label_peca_para, 0, 60).'...';
										}
										?>
										<div style='text-align: center; height: 40px; text-decoration: line-through;' title='<?=$title_peca?>'><?=$label_peca?></div>
										<div style='text-align: center; height: 80px;' title='<?=$title_peca_para?>'><b class='text-error'>Mudou para:</b>&nbsp;<?=$label_peca_para?></div>
									<?php
									} else {
										$title_peca = $label_peca;
										
										if (strlen($label_peca) >= 80) {
											$label_peca = substr($label_peca, 0, 80).'...';
										}
										?>
										<div style='text-align: center; height: 80px;' title='<?=$title_peca?>'><?=$label_peca?></div>
									<?php
									}
									
									if(strlen($peca_para) > 0){
									?>
										<div style='text-align: center; text-decoration: line-through;'><h4><?=number_format((double) $preco, 2, ',', '.')?></h4></div>
										<button type='button' disabled class='btn btn-success btn-block' style='cursor: not-allowed;' ><i class='fa fa-cart-plus'></i> Adicionar ao carrinho</button>
									<?php
									} else {
									?>
										<div class='div-preco' style='text-align: center; height: 40px;'>
											<input type='number' class='span3' value='1' />
										</div>
										<div style='text-align: center;'><h4><?=number_format((double) $preco, 2, ',', '.')?></h4></div>
										<?php
										if ((double) $preco > 0) {
										?>
											<button type='button' class='btn btn-success btn-block retorna-peca' data-json='<?=addslashes(json_encode($r))?>' ><i class='fa fa-cart-plus'></i> Adicionar ao carrinho</button>
										<?php
										} else {
										?>
											<button type='button' class='btn btn-success btn-block' onclick='alert("Não é possível adicionar peças sem preço ao carrinho");' ><i class='fa fa-cart-plus'></i> Adicionar ao carrinho</button>
										<?php
										}
									}
									?>
								</div>
								<?php
								$col++;
								
								if ($col == 4) {
									$col = 0;
									?>
									</div><div class='row-fluid'>
								<?php
								}
							} else {
								if (empty($para)) {
									if($bloqueada_venda == 't' and isset($telapedido)){
										$complemento_descricao = " Peça bloqueada para venda ";
										echo "<tr >";
									}else{
										$complemento_descricao = "";
										echo "<tr onclick='window.parent.retorna_peca(".json_encode($r)."); window.parent.Shadowbox.close();' >";
									}

									if ($imagemPeca) {

										$xpecas = $tDocs->getDocumentsByRef($peca, "peca");
										if (!empty($xpecas->attachListInfo)) {

											$a = 1;
											foreach ($xpecas->attachListInfo as $kFoto => $vFoto) {
												$fotoPeca = $vFoto["link"];
												if ($a == 1){break;}
											}
											echo "<td class='cursor_lupa'><a href='".$fotoPeca."'><img src='".$fotoPeca."' height='50'></a></td>";

										} else {

											$local_jpg= $caminho_img_peca."imagens_pecas/{$login_fabrica}/pequena/$peca.jpg";
											$local_jpeg= $caminho_img_peca."imagens_pecas/{$login_fabrica}/pequena/$peca.jpeg";
											if (file_exists($local_jpg)) {
												echo "<td class='cursor_lupa'><a href='".$caminho_img_peca."imagens_pecas/{$login_fabrica}/media/$peca.jpg'><img src='".$caminho_img_peca."imagens_pecas/{$login_fabrica}/pequena/$peca.jpg'></a></td>";
											} else if (file_exists($local_jpeg)){
												echo "<td class='cursor_lupa'><a href='".$caminho_img_peca."imagens_pecas/{$login_fabrica}/media/$peca.jpg'><img src='".$caminho_img_peca."imagens_pecas/{$login_fabrica}/pequena/$peca.jpeg'></a></td>";
											} else {
												echo "<td class='cursor_lupa'>&nbsp</td>";
											}

										}
									}

									//hd-3625122 - fputti
									if ($login_fabrica == 171) {
										echo "<td class='cursor_lupa'>{$referencia_fabrica}</td>";
									}
									echo "<td class='cursor_lupa'>{$referencia}</td>";
									echo "<td class='cursor_lupa'>{$descricao} <span style='color:red;'>{$complemento_descricao}</span></td>";
									if($tabela or $preco_peca){ echo "<td class='cursor_lupa'>".number_format($preco,2,",",".")."</td>";}
									if(strlen($marca)){ echo "<td class='cursor_lupa'>{$marca_nome}</td>";}
									echo "</tr>";
								}else{
									if($bloqueada_venda == 't' and isset($pedido)){
										$complemento_descricao = " Peça bloqueada para venda ";
										echo "<tr >";
									}else{
										echo "<tr onclick='window.parent.retorna_peca(".json_encode($r)."); window.parent.Shadowbox.close();' >";
									}
									if ($imagemPeca) {


										$xpecas = $tDocs->getDocumentsByRef($peca, "peca");
										if (!empty($xpecas->attachListInfo)) {

											$a = 1;
											foreach ($xpecas->attachListInfo as $kFoto => $vFoto) {
												$fotoPeca = $vFoto["link"];
												if ($a == 1){break;}
											}
											echo "<td class='cursor_lupa'><a href='".$fotoPeca."'><img src='".$fotoPeca."' height='50'></a></td>";

										} else {

											$local_jpg= $caminho_img_peca."imagens_pecas/{$login_fabrica}/pequena/$peca.jpg";
											$local_jpeg= $caminho_img_peca."imagens_pecas/{$login_fabrica}/pequena/$peca.jpeg";
											if (file_exists($local_jpg)) {
												echo "<td class='cursor_lupa'><a href='".$caminho_img_peca."imagens_pecas/{$login_fabrica}/media/$peca.jpg'><img src='".$caminho_img_peca."imagens_pecas/{$login_fabrica}/pequena/$peca.jpg'></a></td>";
											} else if (file_exists($local_jpeg)){
												echo "<td class='cursor_lupa'><a href='".$caminho_img_peca."imagens_pecas/{$login_fabrica}/media/$peca.jpg'><img src='".$caminho_img_peca."imagens_pecas/{$login_fabrica}/pequena/$peca.jpeg'></a></td>";
											} else {
												echo "<td class='cursor_lupa'>&nbsp</td>";
											}
										}

									}

										echo "<td class='cursor_lupa'>{$referencia}</td>";
										echo "<td class='cursor_lupa'>{$descricao} <br ><strong class='text-error'>Mudou Para: </strong><span style='font-family:Arial, Verdana, Times, Sans-Serif;size:10px;color:black;font-weight:bold'>{$para} - {$para_descricao}  </span> <span style='color:red;'>{$complemento_descricao}</span></td>";

										if($tabela or $preco_peca){ echo "<td class='cursor_lupa'>".number_format($preco,2,",",".")."</td>";}

										if(strlen($marca)){
											echo "<td class='cursor_lupa'>{$marca_nome}</td>";
										}
									echo "</tr>";

								}
							}
						}
						
						if (!$catalogo_peca) {
						?>
							</tbody>
						</table>
						<?php
						}
						?>
						</div>
					<?php
					} else {
						echo '
					<div class="alert alert_shadobox">
					    <h4>Nenhum resultado encontrado</h4>
					</div>';
					}
				}
			} else {
				echo '
					<div class="alert alert_shadobox">
					    <h4>Informe toda ou parte da informação para pesquisar!</h4>
					</div>';
			}
			?>
	</div>
	</body>
</html>
