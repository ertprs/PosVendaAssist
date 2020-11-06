<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

use html\HtmlBuilder;
use model\ModelHolder;

if(array_key_exists('action',$_POST) && $_POST['action'] == 'save'){
	$pecas = $_POST['pedido']['pecas'];
	$id = fazPedido($pecas);
	header(sprintf('Location: pedido_finalizado?pedido=%d',$id));
	die();
}


$layout_menu = "pedido";
$title       = "Pedido";

include 'cabecalho_new.php';

$plugins = array(
	"shadowbox",
);

require __DIR__.'/admin/plugin_loader.php';

$htmlBuilder = HtmlBuilder::getInstance();

if(array_key_exists('pedido',$_POST)){
	$htmlBuilder->setValues(array('pedido'=>$_POST['pedido']));
}

$tabelaPecas = array(
	'class' => 'AutoList',
	'attr' => array(
		'id' => 'pedido-pecas',
		'list-wildcard' => '*',
		'name' => 'pedido[pecas]'
	),
	'content' => array(
		array(
			'class' => 'BootstrapRows',
			'content' => array(
				'@marginSize' => 2,
				'pedido[pecas][*][peca]' => array(
					'type' => 'input/hidden',
				),
				'pedido[pecas][*][referencia]' => array(
					'type' => 'input/text',
					'label' => 'Código',
					'span' => 1,
					'width' => 12,
					'extra' => array('readonly'=>'readonly')
				),
				'pedido[pecas][*][descricao]' => array(
					'type' => 'input/text',
					'label' => 'Descrição',
					'span' => 4,
					'width' => 12,
					'extra' => array('readonly'=>'readonly')
				),
				'pedido[pecas][*][quantidade]' => array(
					'type' => 'input/text',
					'label' => 'Qtd',
					'span' => 1,
					'width' => 12,
				),
				'pedido[pecas][*][preco]' => array(
					'type' => 'input/text',
					'label' => 'Preço',
					'span' => 1,
					'width' => 12,
					'extra' => array('readonly'=>'readonly')
				),
				'pedido[pecas][*][total]' => array(
					'type' => 'input/text',
					'label' => 'Total',
					'span' => 1,
					'width' => 12,
					'extra' => array('readonly'=>'readonly')
				),
			)
		)
	)
);

$tabelaPecas = $htmlBuilder->build($tabelaPecas);
$precos = getPrecoPecas();
$tipoPedido = getTipoPedido();

?>
<style>
	.AutoListModel {
		display : none;
	}
</style>
<script type="text/javascript" src="js/ExplodeView.js"></script>
<link type="text/css" href="js/pikachoose/css/css3.css" rel="stylesheet" />		
<script type="text/javascript" src="js/pikachoose/js/jquery.jcarousel.min.js"></script>
<script type="text/javascript" src="js/pikachoose/js/jquery.touchwipe.min.js"></script>
<script type="text/javascript" src="js/pikachoose/js/jquery.pikachoose.js"></script>
<link href="js/imgareaselect/css/imgareaselect-default.css" rel="stylesheet" type="text/css"/>
<link href="js/imgareaselect/css/imgareaselect-animated.css" rel="stylesheet" type="text/css"/>		
<script type="text/javascript" src="js/imgareaselect/js/jquery.imgareaselect.js"></script>		
<script type="text/javascript" src="js/jquery.form.js"></script>
<script type="text/javascript">

	$(function(){

		Shadowbox.init();

		$.autoList();

		$(document).on("click", "span[rel=lupa]", function () {
			$.lupa($(this));
		});


		$(document).on("blur","input[name$='[quantidade]']",function(){
			refreshRow($(this));
		});

		$(document).on("change","input[name$='[total]']",function(){
			refreshTotal();
		});

		//$(document).on("change","input[name$='[quantidade]']",function(){
		//	refreshRow($(this));
		//});

		window.refreshTotal = function(){
			var total = 0;
			$("input[name$='[total]']").each(function(){
				var value = $(this).val();
				if(!value)
					return;
				total += (parseFloat(value));
			});
			$('#total').val(total.toFixed(2));
		};

		window.refreshRow = function(inputQtd){
			var val = $(inputQtd).val();
			val = val.replace(/[^0-9]/g,"");
			val = parseInt(val);
			if(!val){
				$(inputQtd).parents("[list-index]").remove();
			}
			$(inputQtd).val(val);
			var line = $(inputQtd).parents("[list-index]");
			var price = line.find("[name$='[preco]']").val();
			if(!price){
				return;
			}
			price = parseFloat(price);
			var total = line.find("[name$='[total]']");
			total.val(val*price);
			total.trigger('change');
		};


	});

	window.retorna_peca = function(peca){
		peca.quantidade = 1;
		peca.preco = window.precos[peca.peca];
		addPedidoPeca(peca);
		$('#peca-codigo,#peca-descricao').val('');
	};

	window.retorna_produto = function(produto){
		console.debug(produto);		
		$('#produto-pesquisa').val(produto.produto);
		$("#produto-codigo,[name='produto-codigo']").val(produto.referencia);
		$("#produto-descricao,[name='produto-descricao']").val(produto.descricao);
		$('#produto-pesquisa').parents('form').submit();
		window.loading("show");
	};

	window.addPedidoPeca = function(peca){
		if(!peca.quantidade){
			peca.quantidade = 1;
		}
		if(peca.ipi){
			peca.preco *= (1 + peca.ipi/100.0);
		}
		var line = $("#pedido-pecas input[name$='[peca]'][value='"+peca.peca+"']");
		if(line.length == 0){
			$('#pedido-pecas').autoListAdd(peca);
			refreshRow($('#pedido-pecas').find("[list-index]:last input[name$='[quantidade]']"));
			return;
		}
		console.debug(peca);
		var inputQtd = line.parents('[list-index]').find("input[name$='[quantidade]']");
		inputQtd.val(parseInt(inputQtd.val()) + 1);
		inputQtd.trigger('blur');
	};

	window.explodeViewClick = function(event){
		console.debug(event);
		var peca = $.parseJSON($(event.area).attr('value'));
		peca.descricao = event.area.title;
		addPedidoPeca(peca);
	};

</script>
<div>
<?php

	if($_REQUEST['produto'] && is_numeric($_REQUEST['produto'])):
	$produto = (int)$_REQUEST['produto'];
	$model = ModelHolder::init('Produto');
	$explodeViews = $model->getExplodeViewImages($produto);
	$model = ModelHolder::init('ListaBasica');
	$basicLists = $model->find(array('produto'=>$produto));
	$model = ModelHolder::init('Peca');
?>
<div id="explodeView" class="ExplodeView" listener="explodeViewClick">
	<?php foreach($explodeViews as $index => $explodeView):  ?>
		<img explode-view="<?php echo $index;?>" src="<?php echo $explodeView ?>" />
	<?php endforeach; ?>
	<?php if(empty($explodeViews)): ?>
	<br /><br />
	<?php endif; ?>	
	<?php foreach($basicLists as $basicList): ?>
		<?php
			$coords = array('vista'=>'1','x1'=>'0','x2'=>'0','y1'=>'0','y2'=>'0');
			$coordenadas = json_decode($basicList['coordenadas'],true);
			if(!is_array($coordenadas)){
				$coordenadas = array();
			}
			$coords = array_merge($coords,$coordenadas);
			$basicList['peca'] = $model->select($basicList['peca']);
			$basicList['peca']['preco'] = $precos[$basicList['peca']['peca']];
		?>
		<input
			type="hidden"
			title="<?php echo $basicList['peca']['descricao'] ?>"
			href="#basic-list-<?php echo $basicList['listaBasica']; ?>"
			basic-list="<?php echo $basicList['listaBasica']; ?>"
			explode-view="<?php echo $coords['vista'] ?>"
			x1="<?php echo $coords['x1'] ?>"
			x2="<?php echo $coords['x2'] ?>"
			y1="<?php echo $coords['y1'] ?>"
			y2="<?php echo $coords['y2'] ?>"
			value="<?php echo htmlentities(json_encode($basicList['peca'])); ?>"
		 />
	<?php endforeach; ?>
	</div>
</div>
<?php endif; ?>
<br />
<div class="tc_formulario">
	<div class="titulo_tabela">
		Pesquisa Produto
	</div>
	<br />
	<div>
	</div>
	<div class="row-fluid" >
		<span class="span2"></span>
		<div class="span2">
			<div class="control-group ">
				<label for="produto-codigo" class="control-label">Código</label>
				<div class="controls controls-row">
					<div class="span10  input-append">
						<input type="text" maxlength="50" class="span12 " name="produto[codigo]" id="produto-codigo" value="<?=getValue('produto-codigo')?>" >
						<span rel="lupa" class="add-on"><i class="icon-search"></i></span>
						<input type="hidden" parametro="codigo" tipo="produto" name="lupa_config">
					</div>
				</div>
			</div>
		</div>
		<div class="span6">
			<div class="control-group ">
				<label for="produto-descricao" class="control-label">Descricão</label>
				<div class="controls controls-row">
					<div class="span11  input-append">
						<input type="text"  maxlength="50" class="span12 " name="produto[descricao]" id="produto-descricao" value="<?=getValue('produto-descricao')?>" >
						<span rel="lupa" class="add-on"><i class="icon-search"></i></span>
						<input type="hidden" parametro="descricao" tipo="produto" name="lupa_config">
					</div>
				</div>
			</div>
		</div>
		<span class="span2"></span>
	</div>
	<br />
</div>
<br />
<form class="tc_formulario" method="POST" >
	<input id="produto-pesquisa" type="hidden" name="produto" value="<?=getValue('produto')?>" />
	<input type="hidden" name="produto-descricao" value="<?=getValue('produto-descricao')?>" />
	<input type="hidden" name="produto-codigo" value="<?=getValue('produto-codigo')?>" />
	<div class="titulo_tabela">
		Peças do Pedido		
	</div>
	<br />
	<?php $tabelaPecas->render(); ?>
	<br />
	<div class="row-fluid" >
		<span class="span2"></span>
		<div class="span2">
			<div class="control-group ">
				<label for="peca-codigo" class="control-label">Código</label>
				<div class="controls controls-row">
					<div class="span10  input-append">
						<h5 class="asteristico">*</h5>
						<input type="text" maxlength="50" class="span12 " name="peca[codigo]" id="peca-codigo" value="<?=getValue('posto[codigo]')?>" >
						<span rel="lupa" class="add-on"><i class="icon-search"></i></span>
						<input type="hidden" parametro="codigo" tipo="peca" name="lupa_config">
					</div>
				</div>
			</div>
		</div>
		<div class="span4">
			<div class="control-group">
				<label for="posto_nome" class="control-label">Descricão</label>
				<div class="controls controls-row">
					<div class="span11  input-append">
						<h5 class="asteristico">*</h5>
						
						<input type="text"  maxlength="50" class="span12 " name="posto[nome]" id="peca-descricao" value="<?=getValue('posto[nome]')?>" >
						<span rel="lupa" class="add-on"><i class="icon-search"></i></span>
						<input type="hidden" parametro="descricao" tipo="posto" name="lupa_config">
					</div>
				</div>
			</div>
		</div>
		<div class="span2">
			<div class="control-group">
				<label>Totalização</label>
				<div class="span12">
					<input id="total" type="text" class="span12" readonly="readonly" />
				</div>
			</div>
		</div>
		<span class="span2"></span>
	</div>
	<br />
	<button type="submit" class="btn btn-success" name="action" value="save" onclick="window.loading('show')">
		Enviar
	</button>		
	<br />
	<br />
</form>
<script type="text/javascript">
	window.precos = <?php echo json_encode($precos) ?>;
</script>
<?php

include "rodape.php";


function getTipoPedido(){
	global $pdo,$login_fabrica,$login_posto;
	$sql = 'SELECT tbl_tipo_pedido.tipo_pedido
			FROM tbl_tipo_pedido
			WHERE tbl_tipo_pedido.fabrica = ?
			AND tbl_tipo_pedido.pedido_faturado IS NOT FALSE
			AND tbl_tipo_pedido.pedido_em_garantia IS NOT TRUE
			LIMIT 1';
	$params = array($login_fabrica);
	$stmt = $pdo->prepare($sql);
	$stmt->execute($params);
	$result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
	return $result[0]['tipo_pedido'];
}

function getPrecoPecas(){
	global $pdo,$login_fabrica,$login_posto;
	$sql = 'SELECT tbl_tabela_item.peca,tbl_tabela_item.preco
			FROM tbl_tabela
			INNER JOIN tbl_posto_linha
				ON (tbl_tabela.tabela = tbl_posto_linha.tabela)
			INNER JOIN tbl_linha
				ON (tbl_linha.linha = tbl_posto_linha.linha)
			INNER JOIN tbl_tabela_item
				ON (tbl_tabela.tabela = tbl_tabela_item.tabela)
			WHERE tbl_tabela.ativa IS NOT FALSE
			AND tbl_tabela.tabela_garantia IS NOT TRUE
			AND tbl_posto_linha.ativo IS NOT FALSE
			AND tbl_posto_linha.posto = ?
			AND tbl_linha.fabrica = ?
			AND tbl_tabela.fabrica =  ?';
	$params = array($login_posto,$login_fabrica,$login_fabrica);
	$stmt = $pdo->prepare($sql);
	$stmt->execute($params);
	$result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
	$prices = array();
	foreach($result as $line){
		$prices[$line['peca']] = $line['preco'];
	}
	return $prices;
}

function fazPedido($pecas){
	$precos = getPrecoPecas();
	global $login_fabrica,$login_posto;
	$modelPedido = ModelHolder::init('Pedido');
	$pedido = array();

	$pedido['fabrica'] = $login_fabrica;
	$pedido['posto'] = $login_posto;
	$pedido['itens'] = array();
	$total = 0;
	foreach ($pecas as $peca) {
		$item = array();
		$precoPeca = $precos[$peca['peca']];
		$precoItem = $precoPeca * $peca['quantidade'];
		$item['peca'] = $peca['peca'];
		$item['qtde'] = $peca['quantidade'];
		$item['preco'] = $precoPeca;
		$item['total_item'] = $precoItem;
		$total += $precoItem;
		$pedido['itens'][] = $item;
	}
	$pedido['total'] = $total;
	$pedido['tipoPedido'] = getTipoPedido();
	return $modelPedido->insert($pedido);
}