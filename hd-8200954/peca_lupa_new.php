<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
if (preg_match("/\/admin\//", $_SERVER["PHP_SELF"])) {
	include 'autentica_admin.php';
} else if (isset($_REQUEST["distrib"])) {
	$distrib = "t";
	include 'distrib/autentica_usuario.php';
} else {
	include 'autentica_usuario.php';
}

$plugins = [
    'font_awesome'
];

include 'plugin_loader.php';

$disponibilidade = 'Não';

if($_REQUEST['telapedido']){
	$telapedido = $_REQUEST['telapedido'];
}

if ($_REQUEST["posicao"]) {
	$posicao   = $_REQUEST["posicao"];
}
if ($_REQUEST["preco"]) {
	$peca_preco  = true;
}
if ($_REQUEST["ipi"]) {
	$peca_ipi  = true;
}
$callcenter = $_REQUEST['callcenter'];
if ($_REQUEST["preco_peca"]) {
	$preco_peca   = $_REQUEST["preco_peca"];
}
if ($_REQUEST["referencia_descricao"]) {
       $referencia_descricao  = $_REQUEST["referencia_descricao"];
}
if ($_REQUEST["tipo-pedido"]) {
	$tipo_pedido  = $_REQUEST["tipo-pedido"];
}
if ($_REQUEST["pedido"]) {
	$pedido  = $_REQUEST["pedido"];
}
$parametro = $_REQUEST["parametro"];
if (!$_POST) {
	$valor     = trim($_REQUEST["valor"]);
} else {
	$valor = trim($_POST['valor']);
}
$campo     = trim($_REQUEST["campo"]);
if($_REQUEST['marca']){
	$marca = $_REQUEST['marca'];
}
if($_REQUEST['de'] == 'true'){
	$de = true;
}
if($_REQUEST['pecaPara']){
	$pecaPara = $_REQUEST['pecaPara'];
}
if($_REQUEST['pecaAcao']){
	$pecaAcao = $_REQUEST['pecaAcao'];
}
if($_REQUEST['catalogo_peca']){
	$catalogo_peca = $_REQUEST['catalogo_peca'];
}
if($_REQUEST['para'] == 'true'){
 	$para = true;
}
if ($_REQUEST['produto']) {
	$produto = $_REQUEST['produto'];
}
if($_REQUEST['todas']){
	$todasPecas = $_REQUEST['todas'];	
}else{
	$todasPecas = false;
}

if ($_REQUEST['telapedido'] == true && $login_fabrica == 183 && strlen($_REQUEST['linha']) > 0) {
	$xxlinha   = $_REQUEST['linha'];
	$selLinhaProduto = "tbl_produto.linha,";
	$joinLinhaProduto = "	INNER JOIN tbl_lista_basica ON tbl_lista_basica.peca = tbl_peca.peca AND tbl_lista_basica.fabrica = {$login_fabrica}
					        INNER JOIN tbl_produto ON tbl_produto.produto = tbl_lista_basica.produto AND tbl_produto.fabrica_i = {$login_fabrica}";
	$whereLinhaProduto = "	AND tbl_produto.linha = {$xxlinha} ";
	$distinct = " DISTINCT ON(tbl_peca.peca)";
}



$condicaoItemAparencia = "";
$whereItemAparencia    = "";

if(!empty($login_posto)){
	$condicaoItemAparencia = " tbl_posto.posto = $login_posto ";
	$posto = $login_posto;
}else {
	$posto_codigo = $_REQUEST['posto_codigo'];
	$posto_nome   = $_REQUEST['posto_nome'];
	if(!empty($posto_codigo) && !empty($posto_nome)){
		$condicaoItemAparencia = " tbl_posto_fabrica.codigo_posto = '$posto_codigo' AND tbl_posto.nome = '$posto_nome' ";
	}
}
if (empty($callcenter)) {
    $condProdAcabado = " AND tbl_peca.produto_acabado IS NOT TRUE";
} else {
    $condProdAcabado = " ";
}
if(strlen($condicaoItemAparencia) > 0) {
	$sql = "SELECT  tbl_posto_fabrica.item_aparencia,desconto,posto
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
	$item_aparencia = 'f';
}
if ($item_aparencia == 'f'){
	$whereItemAparencia = "AND tbl_peca.item_aparencia IS FALSE";
}
	//pega desconto do tipo posto, se o descontro do posto for zero.
	if($login_fabrica == 160 or $replica_einhell){
		if(strlen(trim($desconto))<=0 or $desconto == 0){
			$sql_desc_tipo_posto = " select tbl_tipo_posto.descontos[1], tbl_tipo_posto.descricao from tbl_posto_fabrica inner join tbl_tipo_posto on tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto  where tbl_posto_fabrica.fabrica = $login_fabrica and tbl_posto_fabrica.posto = $login_posto ";
			$res_desc_tipo_posto = pg_query($con, $sql_desc_tipo_posto);
			if(pg_num_rows($res_desc_tipo_posto)>0){
				$desconto = pg_fetch_result($res_desc_tipo_posto, 0, $descontos);
			}
		}
	}

if ($imagemPeca) {
	include "anexaNFDevolucao_inc.php";
	$tDocs = new TDocs($con, $login_fabrica);
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
		<link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
    	<link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
    	<link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
    	<link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />
		<link href="plugins/dataTable.css" type="text/css" rel="stylesheet" />
		<link href="plugins/font_awesome/css/font-awesome.css" type="text/css" rel="stylesheet" />
		<link href='plugins/select2/select2.css' type='text/css' rel='stylesheet' />

		<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
		<script src="bootstrap/js/bootstrap.js"></script>
		<script src="plugins/dataTable.js"></script>
		<?php if (!$catalogo_peca) { ?>
			<script src="plugins/resize.js"></script>
			<script src="plugins/shadowbox_lupa/lupa.js"></script>
		<?php } ?>
		<script src='plugins/select2/select2.js'></script>
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
						<?php if (in_array($login_fabrica, [175])) { ?>
							let tipoUnidade = ['c', 'm', 'mm', 'cm', 'dm', 'mt', 'dam', 'hm', 'km'];
							let json = $(this).parent().parent().find('.retorna-peca').data('json');
								
							if (json) {
								json = json.replace(/\\\"/g, '\"');
								json = JSON.parse(json);

								if ($.inArray(json.unidade, tipoUnidade) === -1 ) {
									let v = $(this).val().replace(/\D/g, '');
									$(this).val(v);
								}
							} else {
								let v = $(this).val().replace(/\D/g, '');
								$(this).val(v);
							}
					<?php } else { ?>
							let v = $(this).val().replace(/\D/g, '');
							$(this).val(v);
					<?php } ?>
					});
					
					$('button.retorna-peca').on('click', function() {
						let json = $(this).data('json');
						json = json.replace(/\\\"/g, '\"');
						json = JSON.parse(json);
						let qtde = parseInt($(this).prevAll('.div-preco').find('input[type=number]').val());
						
						<?php if (in_array($login_fabrica, [175])) { ?>
						let tipoUnidade = ['c', 'm', 'mm', 'cm', 'dm', 'mt', 'dam', 'hm', 'km'];
						
						if ($.inArray(json.unidade, tipoUnidade) === -1 ) {
							qtde = parseInt($(this).prevAll('.div-preco').find('.span3').val());
						} else {
							qtde = parseFloat($(this).prevAll('.div-preco').find('.span3').val());
						}
						<?php } ?>

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
		<div id="container_lupa" style="overflow-y:auto; height: auto !important;">
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
					<img class="espaco" src="imagens/logo_new_telecontrol.png">
					<img class="lupa_img pull-right" src="imagens/lupa_new.png">
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
						<?php
						if ($_REQUEST['telapedido'] == true && $login_fabrica == 183 && strlen($_REQUEST['linha']) > 0) {
							echo "<input type='hidden' name='telapedido' value='".$_REQUEST['telapedido']."' />";
							echo "<input type='hidden' name='linha' value='".$_REQUEST['linha']."' />";
						}
						if(isset($pecaAcao)){
							echo "<input type='hidden' name='pecaAcao' value='".$pecaAcao."' />";
						}
						if(isset($pecaPara)){
							echo "<input type='hidden' name='pecaPara' value='".$pecaPara."' />";
						}
						
						if(isset($peca_preco) || isset($preco_peca)){
							echo "<input type='hidden' name='preco' value='".$preco."' />";
						}
						
						if(isset($tipo_pedido)){
							echo "<input type='hidden' name='tipo_pedido' value='".$tipo_pedido ."' />";
						}

						if(isset($telapedido)){
							echo "<input type='hidden' name='telapedido' value='".$telapedido ."' />";	
						}
						
						if(isset($catalogo_peca)){
							echo "<input type='hidden' name='catalogo_peca' value='".$catalogo_peca ."' />";
						}
						
						if(isset($posto_codigo) && empty($posto_codigo) > 0){
						?>
							<input type="hidden" name="produto_codigo" class="span12" value='<?=$produto_codigo?>' />
							<input type="hidden" name="produto_nome" class="span12" value='<?=$produto_nome?>' />
						<?php
						}
						?>

						<select name="parametro" class='span12' >
							<option value="referencia" <?=($parametro == "referencia") ? "SELECTED" : ""?> ><?= traduz('Referência') ?></option>
							<option value="descricao" <?=($parametro == "descricao") ? "SELECTED" : ""?> ><?= traduz('Descrição') ?></option>
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
							<button type='button' class='btn btn-primary btn-block' onclick="$(this).parents('form').submit();"><i class='fa fa-search'></i> <?=traduz('Pesquisar')?></button>
						</div>
						<div class='span2'>
							<button type='button' class='btn btn-warning btn-block' onclick="$('select[name=produto]').val(''); $('input[name=valor]').val(''); $(this).parents('form').submit();"><i class='fa fa-times'></i> <?=traduz('Limpar filtros')?></button>
						</div>
					</div>
				<?php
				} else {
				?>	
					<input type="hidden" name="pedido" value="<?=$_REQUEST['pedido']?>">
						<div class="span2">
							<button type="button" class="btn pull-right" onclick="$(this).parents('form').submit();"><?= traduz('Pesquisar') ?></button>
						</div>
					</div>
				<?php
				}
				?>
			</form>
			<?php
			
			if (strlen($valor) >= 3 || $catalogo_peca) {
				if (strlen($valor) >= 3) {
					switch ($parametro) {
						case 'referencia':

							$valor = str_replace(array(".", ",", "-", "/", " "), "", $valor);
							$whereAdc = "AND UPPER(tbl_peca.referencia_pesquisa) ILIKE UPPER('%{$valor}%') ";

							break;
						case 'descricao':
							$whereAdc = "AND UPPER(fn_retira_especiais(tbl_peca.descricao)) ILIKE UPPER('%' || fn_retira_especiais('{$valor}') || '%') ";
							break;
						case 'referencia_descricao':
							$whereAdc = "
								AND tbl_peca.peca IN (
									SELECT tbl_peca.peca
									FROM tbl_peca
									WHERE (
										UPPER(tbl_peca.descricao) ILIKE UPPER('%$valor%')
									OR UPPER(tbl_peca.referencia_pesquisa) ILIKE UPPER('%$valor%')
										)
										AND tbl_peca.fabrica = $login_fabrica
									)
							";
							break;
					}
					
					if($referencia_descricao){
						$whereAdc = "AND UPPER(tbl_peca.descricao) ILIKE UPPER('%{$valor}%') OR UPPER(tbl_peca.referencia_pesquisa) ILIKE UPPER('%{$valor}%')  ";
					}
				}
				
				if($versao){
					$joinVer = "INNER JOIN tbl_lista_basica ON tbl_lista_basica.peca = tbl_peca.peca AND tbl_lista_basica.fabrica = {$login_fabrica} ";
					$whereVer = " AND UPPER(tbl_lista_basica.type) = UPPER('{$versao}') ";
				}
				
				if (!empty($produto)) {
					$distinct = "DISTINCT";
					$joinLb = "INNER JOIN tbl_lista_basica ON tbl_lista_basica.peca = tbl_peca.peca AND tbl_lista_basica.produto = {$produto} AND tbl_lista_basica.fabrica = {$login_fabrica}";
				}
				if(strlen($marca)>0){
					$selMarca = ", tbl_marca.nome as marca_nome";
					$joinMarca = "	INNER JOIN tbl_lista_basica ON tbl_lista_basica.peca = tbl_peca.peca AND tbl_lista_basica.fabrica = {$login_fabrica}
									INNER JOIN tbl_produto ON tbl_produto.produto = tbl_lista_basica.produto AND tbl_produto.fabrica_i = {$login_fabrica}
									INNER JOIN tbl_marca ON tbl_marca.marca = tbl_produto.marca AND tbl_marca.fabrica = {$login_fabrica} ";
					$condMarca = "	AND tbl_produto.marca = {$marca} ";
				}
				
				if(strlen($preco_peca)>0){
					$selPreco = ", tbl_tabela_item.preco ";
					
					if (!empty($posto)) {
						$joinPostoLinhaWhere = " AND tbl_posto_linha.posto = {$posto} ";
					}
					
					$joinPreco = "
							LEFT JOIN tbl_posto_linha ON tbl_posto_linha.linha = tbl_produto.linha {$joinPostoLinhaWhere}
							LEFT JOIN tbl_tabela ON tbl_tabela.tabela = tbl_posto_linha.tabela AND tbl_tabela.fabrica = {$login_fabrica}
							LEFT JOIN tbl_tabela_item ON tbl_tabela_item.tabela = tbl_tabela.tabela AND tbl_tabela_item.peca = tbl_peca.peca ";
				}
				$joinPedido = "";
				$wherePedido = "";
				$selqtde = "";
				if (in_array($login_fabrica, [35]) && !empty($pedido)){
					$joinPedido = " JOIN tbl_pedido_item ON tbl_peca.peca = tbl_pedido_item.peca ";
					$wherePedido = " AND tbl_pedido_item.pedido = {$pedido}";
					$selqtde = ", tbl_pedido_item.qtde AS qtde_maximo";
				}

				if ((isset($whereAdc) && strlen($valor) >= 3) || $catalogo_peca) {
					$selDepara = ", tbl_depara.peca_para,
							tbl_peca_para.referencia AS referencia_para,
							tbl_peca_para.descricao AS descricao_para";
					$lefDepara = "LEFT JOIN tbl_depara ON tbl_depara.fabrica = $login_fabrica AND tbl_depara.peca_de = tbl_peca.peca AND (tbl_depara.expira > current_date OR tbl_depara.expira IS NULL)
						LEFT JOIN tbl_peca AS tbl_peca_para ON tbl_peca_para.fabrica = $login_fabrica AND tbl_peca_para.peca = tbl_depara.peca_para";
						
					if ($distrib == 't') {
						$fabricas_distrib = array($telecontrol_distrib);
						$sql = "SELECT peca,fabrica,referencia,descricao
								FROM tbl_peca
								WHERE (referencia ILIKE '%$valor%' AND fabrica IN (".implode(",", $fabricas_distrib)."))
								OR
								(referencia_pesquisa ILIKE '%$valor%' AND fabrica IN (".implode(",", $fabricas_distrib)."))
								ORDER BY fabrica";
					} else {
						// SQL MODIFICADA NO hd_chamado=2574626
						if ($catalogo_peca) {
							$whereBloqueadaVenda = "AND tbl_peca.bloqueada_venda IS NOT TRUE";
						}

						if(in_array($login_fabrica, [169,170]) and $telapedido){
							$sql_peca_original = " and tbl_peca.parametros_adicionais::JSON->>'peca_original' = 't' ";
						}


						$sql = "SELECT {$distinct}
									tbl_peca.peca,
									tbl_peca.referencia,
									fn_retira_especiais(tbl_peca.descricao) AS descricao,
									tbl_peca.ipi,
									tbl_peca.origem,
									tbl_peca.estoque,
									tbl_peca.multiplo,
									tbl_peca.bloqueada_venda,
									tbl_peca.unidade,
									{$selLinhaProduto}
									tbl_peca.ativo,
									tbl_peca.parametros_adicionais
									{$selDepara}
									{$selMarca}
									{$selPreco}
									{$selqtde}
								FROM tbl_peca
								JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_peca.fabrica
								{$joinLinhaProduto}
								{$lefDepara}
								{$joinMarca}
								{$joinVer}
								{$joinPreco}
								{$joinLb}
								{$joinPedido}
								WHERE tbl_peca.fabrica = {$login_fabrica}
								AND tbl_peca.ativo is true
								{$whereLinhaProduto}
								{$whereAdc}
								{$whereVer}
								{$condMarca}
								{$whereItemAparencia}
								{$condProdAcabado}
								{$whereBloqueadaVenda}
								{$wherePedido}
								{$sql_peca_original}
								";
					}
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
                                        <th><?= traduz('Imagem') ?></th>
                                    <? } ?>
									<th><?= traduz('Referência') ?></th>
									<th><?= traduz('Descrição') ?></th>
									<?php
									if( strlen($peca_preco)>0 || strlen($preco_peca)>0){
										echo "<th>".traduz('Preço')."</th>";
									}
									if($login_fabrica == 168 AND $todasPecas == true){
										echo "<th>IPI</th>";
									}
									if(strlen($marca)){
										echo "<th>Marca</th>";
									}
									?>
								</tr>
							</thead>
							<tbody>
						<?php
						}
						
						$col = 0;
						
						for ($i = 0; $i < $rows; $i++) {
							$peca       = pg_fetch_result($res, $i, 'peca');
							if (in_array($login_fabrica, [35]) && !empty($pedido)) {
								$sqlPecaHD = "	
											SELECT fabrica, pedido, peca_faltante, hd_chamado
											FROM tbl_hd_chamado_posto 
												JOIN tbl_hd_chamado USING (hd_chamado) 
												JOIN tbl_hd_chamado_extra USING(hd_chamado) 
											WHERE 
												fabrica = {$login_fabrica}
												AND tbl_hd_chamado.status NOT IN('Cancelado','Finalizado','Obsoleto')
												AND pedido = {$pedido}
												AND peca_faltante like '%{$peca}=%' ";
								$resPecaHD = pg_query($con, $sqlPecaHD);

								if (pg_num_rows($resPecaHD) > 0) {
									continue;
								}
							}
							$referencia = pg_fetch_result($res, $i, 'referencia');
							$descricao  = pg_fetch_result($res, $i, 'descricao');
							$ipi        = pg_fetch_result($res, $i, 'ipi');
							$multiplo   = pg_fetch_result($res, $i, 'multiplo');
							$origem     = pg_fetch_result($res, $i, 'origem');
							$estoque    = pg_fetch_result($res, $i, 'estoque');
							$unidade    = pg_fetch_result($res, $i, 'unidade');
							$ativo      = pg_fetch_result($res, $i, 'ativo');
							$ativo      = pg_fetch_result($res, $i, 'ativo');
							$bloqueada_venda = pg_fetch_result($res, $i, 'bloqueada_venda');

							$parametros_adicionais = pg_fetch_result($res, $i, 'parametros_adicionais');
							$parametros_adicionais = json_decode($parametros_adicionais, true);

							if (in_array($login_fabrica, [35]) && !is_null($pedido)) {
								$qtde_maximo = pg_fetch_result($res, $i, 'qtde_maximo');

							}
							//if($login_fabrica == 138){ //hd_chamado=2574626
								$peca_para             = pg_fetch_result($res, $i, "peca_para");
								$referencia_para       = pg_fetch_result($res, $i, "referencia_para");
								$descricao_para        = pg_fetch_result($res, $i, "descricao_para");
							//}
							if (strlen($marca) > 0) {
								$marca_nome = pg_fetch_result($res, $i, 'marca_nome');
							}
							if($preco_peca || $peca_preco){
								$preco = pg_fetch_result($res, $i, 'preco');
							}
							if ($peca_preco == true ) {
								$sql = "SELECT uso_consumo FROM tbl_tipo_pedido WHERE fabrica = $login_fabrica AND tipo_pedido = $tipo_pedido";
								$res_tipo = pg_query($con, $sql);
								$uso_consumo = pg_fetch_result($res_tipo, 0, "uso_consumo");
								if ($uso_consumo != "t") {
									$coluna_tabela_preco = (in_array($login_fabrica, array(35,144,168))) ? "tabela":"tabela_posto";
								} else {
									$coluna_tabela_preco = "tabela_bonificacao";
									if ($login_fabrica == 143 || $login_fabrica == 161) {
										$coluna_tabela_preco = "tabela_posto";
									}
								}
								if ($login_fabrica == 151) {
									$sql_pedido_fat = "SELECT * FROM tbl_tipo_pedido
										WHERE tipo_pedido = $tipo_pedido
										AND pedido_faturado IS TRUE";
									$res_pedido_fat = pg_query($con, $sql_pedido_fat);
									$coluna_tabela_preco = 'tabela';
									if (pg_num_rows($res_pedido_fat) > 0) {
										$coluna_tabela_preco = 'tabela_posto';
									}
								}
								$sqlT = "SELECT $coluna_tabela_preco AS tabela
									FROM tbl_posto_linha
									JOIN tbl_linha using(linha)
									WHERE posto = $login_posto
									AND fabrica = $login_fabrica
									AND tbl_linha.ativo IS TRUE
									LIMIT 1";
								$resT = pg_query($con, $sqlT);
								if (pg_num_rows($resT) > 0) {
									$tabela = pg_fetch_result($resT, 0, 0);
									
									if ($catalogo_peca && empty($tabela)) {
										continue;
									}
									$sqlT = "SELECT preco FROM tbl_tabela_item WHERE tabela = $tabela AND peca = $peca";
									if(strlen($peca_para) > 0){
											$sqlT = "SELECT preco FROM tbl_tabela_item WHERE tabela = $tabela AND peca = $peca_para";
									}
									$resT = pg_query($con, $sqlT);
									$preco = (pg_num_rows($resT) == 1) ? pg_fetch_result($resT, 0, 0) : "";
									$preco = ($login_fabrica == 168) ? $preco  + (($preco * $ipi)/100) : $preco;
									$preco = number_format($preco,2,",",".");
								}
							}

							$r = array(
								"peca"      => $peca                    ,
								"descricao" => utf8_encode($descricao)  ,
								"referencia" => $referencia
							);
							//if($login_fabrica == 138){ //hd_chamado=2574626
								if (strlen($peca_para) > 0) {
									$r = array(
										"peca"       => $peca_para,
										"descricao"  => utf8_encode($descricao_para),
										"referencia" => $referencia_para
									);
								}
							//}

							if (in_array($login_fabrica, [177])) {
								$r["lote"] = $parametros_adicionais["lote"];
							}

							if (strlen($qtde_maximo) > 0) {
								$r["qtde_maximo"] = $qtde_maximo;
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
							if($para == true){
								$r['para'] = $para;
							}
							if(strlen($multiplo)){
								$r['multiplo'] = $multiplo;
							}
							if(isset($peca_preco)) {
								$r['preco'] = $preco;
							}
							if($peca_ipi==true) {
								$r['ipi'] = $ipi;
							}
							if (in_array($login_fabrica, [175])) {
								$r["unidade"] = $unidade;
							}
							if($bloqueada_venda == 't' and isset($telapedido)){
								$complemento_descricao = " Peça bloqueada para venda ";
							}else{
								$complemento_descricao = "";
							}							
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
													echo "<img src='imagens/sem_imagem.jpg' style='max-height: 200px;' class='img-polaroid'>";
												}
											} else {
												echo "<img src='imagens/sem_imagem.jpg' style='max-height: 200px;' class='img-polaroid'>";
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
										
										$label_peca_para = "{$referencia_para} - {$descricao_para}";
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
										<div style='text-align: center; text-decoration: line-through;'><h4><?= $preco; ?></h4></div>
										<button type='button' disabled class='btn btn-success btn-block' style='cursor: not-allowed;' ><i class='fa fa-cart-plus'></i> Adicionar ao carrinho</button>
									<?php
									} else {
									?>
										<div class='div-preco' style='text-align: center; height: 40px;'>
											<input type='number' class='span3' value='1' <?= (in_array($login_fabrica, [175])) ? "step='any'" : "" ?> />
										</div>
										<div style='text-align: center;'><h4><?= $preco; ?></h4></div>
										<?php
										if (str_replace(",",".",$preco) > 0) {
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
								if (!empty($peca_para)) {
									echo "<tr onclick='window.parent.retorna_peca(".json_encode($r)."); window.parent.Shadowbox.close();' >";
								} else {
									echo "<tr>";
								}
								
								if ($imagemPeca) {
									$xpecas  = $tDocs->getDocumentsByRef($peca, "peca");
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
								//if($login_fabrica == 138 AND strlen($peca_para) > 0){
								if(strlen($peca_para) > 0){
									echo "<td class='cursor_lupa'>{$referencia}</td>";
									echo "<td class='cursor_lupa'>{$descricao} <br > <strong class='text-error'>Mudou Para: </strong><span style='font-family:Arial, Verdana, Times, Sans-Serif;size:10px;color:black;font-weight:bold'>{$referencia_para} - {$descricao_para}   </span> <span style='color:red;'>{$complemento_descricao}</span></td>";
									// echo "<td class='cursor_lupa'>Ref: <strong>{$referencia} </strong><br/>mudou para <br/><strong class='text-error'>{$referencia_para}</strong></td>";
									// echo "<td class='cursor_lupa'>Desc: <strong>".utf8_encode($descricao)."</strong> <br/> mudou para <br/> <strong class='text-error'>".utf8_encode($descricao_para)."</strong></td>";
								}else{
									if ($catalogoPedido && $telapedido && (double) str_replace(",",".",$preco) == 0) {
										echo "<td class='cursor_lupa' onclick='alert(\"Não é possível adicionar peças sem preço ao carrinho\");' >{$referencia}</td>";
										echo "<td class='cursor_lupa' onclick='alert(\"Não é possível adicionar peças sem preço ao carrinho\");' >".utf8_encode($descricao)." <span style='color:red;'>{$complemento_descricao}</span></td>";
									} else {
										echo "<td class='cursor_lupa'".(($bloqueada_venda == 't' && isset($telapedido)) ? " " : "onclick='window.parent.retorna_peca(".json_encode($r)."); window.parent.Shadowbox.close();'").">{$referencia}</td>";
										echo "<td class='cursor_lupa'".(($bloqueada_venda == 't' && isset($telapedido)) ? " " : "onclick='window.parent.retorna_peca(".json_encode($r)."); window.parent.Shadowbox.close();'").">".utf8_encode($descricao)." <span style='color:red;'>{$complemento_descricao}</span></td>";
									}
								}
									
								if(isset($preco_peca) || isset($peca_preco)) {
									echo "<td class='cursor_lupa'>{$preco}</td>";
								}
								if($login_fabrica == 168 AND $todasPecas == true){
									echo "<td class='cursor_lupa'>{$ipi}</td>";	
								}
								if(strlen($marca)) {
									echo "<td class='cursor_lupa'>{$marca_nome}</td>";
								}
								echo "</tr>";
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
					    <h4>'.traduz('Nenhum resultado encontrado').'</h4>
					</div>';
					}
				}
			} else {
				echo '
					<div class="alert alert_shadobox">
					    <h4>'.traduz('Informe toda ou parte da informação para pesquisar!').'</h4>
					</div>';
			}
			?>
	</div>
	</body>
</html>
<?php if (in_array($login_fabrica, [35]) || $catalogo_peca) {
	$hd_chamado = pg_fetch_result($resPecaHD, 0, 'hd_chamado'); ?>
	<script type="text/javascript">
	$(function () {
		<?php if ($login_fabrica == 35) { ?>
			trs = $('[id^=DataTables_Table_]').find('tbody tr .cursor_lupa').length;
			if (trs == 0) {
				hd = '<?=$hd_chamado?>';
				str = '<div class="alert alert_shadobox"><h4>HelpDesk ' + hd + ' já está aberto com esta peça! </h4></div>';
       				$('[id^=DataTables_Table_]').attr('style', 'display: none');
       				$('#border_table').append(str);
       			}
		<?php }
		if ($catalogo_peca) { ?>
			window.parent.$("#loading").hide();
		<?php } ?>
	
	});
	</script>
<?php } ?>
