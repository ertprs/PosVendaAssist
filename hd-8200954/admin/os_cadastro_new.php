<?php
$areaAdmin = preg_match('@/admin/@',$_SERVER['PHP_SELF']) > 0;

if($areaAdmin){
	include __DIR__.'/dbconfig.php';
	include __DIR__.'/includes/dbconnect-inc.php';
	include __DIR__.'/autentica_admin.php';
	include __DIR__.'/funcoes.php';	
}
else{
	include __DIR__.'/../dbconfig.php';
	include __DIR__.'/../includes/dbconnect-inc.php';
	include __DIR__.'/../autentica_usuario.php';
}

$multiplos_produtos = true;

if ($_POST["btn_acao"] == "submit") {
	$msg_erro = tryInsertOS();
}

$layout_menu = "callcenter";
$title       = "CADASTRO DE ORDEM DE SERVIÇO";

if($areaAdmin){
	include __DIR__.'/cabecalho_new.php';	
}
else{
	include __DIR__.'/../cabecalho_new.php';
}

$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"alphanumeric",
	"price_format"
);

include __DIR__.'/plugin_loader.php';

use util\RequestHelper;
use util\ArrayHelper;
use model\ModelHolder;
use rules\exceptions\ValidateException;
use html\HtmlBuilder;

if($_GET['preos'] && is_numeric($_GET['preos'])){
	try{
		$callModel = ModelHolder::init('HdChamado');
		$preOs = $callModel->makePreOs((int)$_GET['preos']);
		if($preOs['fabrica'] != $login_fabrica){
			throw new \Exception('Pré-OS não encontrada');
		}
		if(!empty($login_posto) && $login_posto != $preOs['posto']){
			throw new \Exception('Pré-OS de outro Posto');
		}
		$htmlBuilder = HtmlBuilder::getInstance();
		$htmlBuilder->setValues(array('os'=>$preOs));
	}
	catch(Exception $ex){
		$msg_erro['msg'][] = $ex->getMessage();
	}
}

$campos_fabrica = include("os_cadastro_unico/fabricas/138.php");

?>

<style>

#modelo_produto, #modelo_pecas, #modelo_peca, div[name=div_remover_produto], div[name=div_remover_posto] , #div_km_google{
	display: none;
}

#GoogleMaps {
	height: 300px;
	width: 59%;
	display: inline-block;
}

#GoogleMapsDirection {
	height: 300px;
	width: 39%;
	display: inline-block;
	background-color: white;
	overflow: auto;
}

.googlemapsdirection-error {
	width: 220px;
	padding-left: 5px;
	padding-right: 5px;
	vertical-align: middle;
	margin: 0 auto;
}

#GoogleMaps img { 
	max-width: none; 
}

.Model {
	display: none;
}

.AutoListModel {
	display: none;
}

.adp-text{
	padding-left: 25px;
}

</style>

<script src="os_cadastro_new.js" ></script>
<script>
	$(function () {
		var post = <?php echo json_encode(empty($_POST)?'{}':$_POST);?>;
		var produtos = post.produto?post.produto:{};
		
		var group = $('<div class="control-group"><label class="control-label"><br /></label></div>');
		var row = $('<div class="controls controls-row"></div>');
		var span = $('<div class="span12 tac"></div>');
		var bt = $('<button type="button" class="btn btn-danger btn-mini ListButtonRemove" style="font-weight: bold;" >X</button>')
		group.append(row);
		row.append(span);
		span.append(bt);
		$(".lista-item .AutoListModel").each(function(){
			$(this).find('.row-fluid').first().find('div.span1').first().append(group.clone());
		});
		$(".lista-item [list-index] div.span1:first-child").each(function(){
			if($(this).find("button").length != 0)
				return;
			$(this).append(group.clone());
		});
		$.autoList();

		for(var i in produtos){
			var produto = produtos[i];
			buscaDefeitoConstatado(""+produto.produto,"select[name='produto["+i+"][defeito_constatado]']");
			for(var key in produto){
				if(key == "item")
					continue;
				$("[name='produto["+i+"]["+key+"]']").val(produto[key]);
			}
			var items = produto.item;
			if(!items)
				continue;
			for(var itemIndex in items){
				var item = items[itemIndex];
				for(var key in item){
					$("[name='produto["+i+"][item]["+itemIndex+"]["+key+"]']").val(item[key]);
				}
			}
		}
		

		$(window).load(function(){
			_buscaCEP($('#consumidor_cep').val(), $("#consumidor_endereco"), $("#consumidor_bairro"), $("#consumidor_cidade"), $("#consumidor_estado"));
			_buscaCEP($('#revenda_cep').val(), $("#revenda_endereco"), $("#revenda_bairro"), $("#revenda_cidade"), $("#revenda_estado"));
			if($("#posto").val() != ""){
				$("#posto_nome").attr({ readonly: "readonly" }).next().hide();
				$("#posto_codigo").attr({ readonly: "readonly" }).next().hide();	
				$("div[name=div_remover_posto]").show();
			}
			
		});
	});
</script>

<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>

<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>

<form name='frm_os' METHOD='POST' ACTION='<?=$PHP_SELF?>' class='form-search form-inline' enctype="multipart/form-data" >
	<?php
	if (!isset($login_posto) && $areaAdmin) {
	?>
		<div rel="informacao_posto" class="tc_formulario">
			<input type="hidden" name="os[posto]" id="posto" value="<?=getValue('os[posto]')?>" />
			<input type="hidden" name="posto_latitude" id="posto_latitude" value="<?=getValue('posto_latitude')?>" disabled="disabled" />
			<input type="hidden" name="posto_longitude" id="posto_longitude" value="<?=getValue('posto_longitude')?>" disabled="disabled" />

			<div class="titulo_tabela ">Informações do Posto Autorizado</div>
			<br />

			<div class="row-fluid">
				<div class="span2"></div>
				<div class="span4">
					<div class="control-group ">
						<label for="posto_codigo" class="control-label">Código</label>
						<div class="controls controls-row">
							<div class="span10  input-append">
								<h5 class="asteristico">*</h5>
								
								<input type="text" maxlength="50" class="span12 " name="posto[codigo]" id="posto_codigo" value="<?=getValue('posto[codigo]')?>" >
								<span rel="lupa" class="add-on"><i class="icon-search"></i></span>
								<input type="hidden" revenda="true" parametro="codigo" tipo="posto" name="lupa_config">
							</div>
						</div>
					</div>
				</div>
				<div class="span4">
					<div class="control-group ">
						<label for="posto_nome" class="control-label">Nome</label>
						<div class="controls controls-row">
							<div class="span10  input-append">
								<h5 class="asteristico">*</h5>
								
								<input type="text"  maxlength="50" class="span12 " name="posto[nome]" id="posto_nome" value="<?=getValue('posto[nome]')?>" >
								<span rel="lupa" class="add-on"><i class="icon-search"></i></span>
								<input type="hidden" revenda="true" parametro="nome" tipo="posto" name="lupa_config">
							</div>
						</div>
					</div>
				</div>
				<div class="span2"></div>
			</div>

			<div class="row-fluid" name="div_remover_posto" >
				<div class="span2"></div>
				<div class="span10">
					<div class="control-group" >
						<div class="controls controls-row">
							<div class="span10">
								<button type="button" name="remover_posto" class="btn btn-danger btn-small" >Remover Posto Autorizado</button>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<br />
	<?php
	}else{

		$modelPosto = ModelHolder::init('PostoFabrica');
		$result = $modelPosto->find(
			array('posto'=>$login_posto,'fabrica'=>$login_fabrica),
			array('latitude','longitude'),
			1
		);
		extract($result[0]);
		$html = array(
			array(
				'class' => 'input[type=hidden][id=posto][value='.$login_posto.']',
			),
			array(
				'class' => 'input[type=hidden][id=posto_latitude]',
				'attr' => array(
					'value' => $latitude
				)
			),
			array(
				'class' => 'input[type=hidden][id=posto_longitude]',
				'attr' => array(
					'value' => $longitude
				)
			)
		);

		$htmlBuilder = HtmlBuilder::getInstance();
		$html = $htmlBuilder->build($html);
		$html->render();
	}

	$html = produtoSubProdutoHtml();
	$htmlBuilder = HtmlBuilder::getInstance();
	$html = $htmlBuilder->build($html);
	$html->render();

include 'rodape.php';

function produtoSubProdutoHtml(){
	global $login_fabrica;
	$camposFabrica = include("os_cadastro_unico/fabricas/138.php");
	$usaSubproduto = in_array($login_fabrica,array(138));
	$html = array();
	$html[] = 
		array(//Informações OS
			'class' => 'div.tc_formulario',
			'content' => array(
				array(
					'class' => 'div.titulo_tabela',
					'content' => 'Informações da OS'
				),
				array(
					'class' => 'br'
				),
				array(
					'class' => 'BootstrapRows',
					'content' => $camposFabrica['os']
				),
			)
		);
	$html[] = 
		array(
			'class' => 'br',
		);
	$html[] = 		
		array(//Informações Consumidor
			'class' => 'div.tc_formulario',
			'content' => array(
				array(
					'class' => 'div.titulo_tabela',
					'content' => 'Informações do Consumidor'
				),
				array(
					'class' => 'br'
				),
				array(
					'class' => 'BootstrapRows',
					'content' => $camposFabrica['consumidor']
				),
				array(
					'renderer' => 'div[id=box_calcular_km][style=padding-bottom:10px;display: none;].tac',
					'content' => array(
						array(
							'renderer' => 'span[id=box_ida_volta]'
						),
						array(
							'renderer' => 'label[for=qtde_km][style=margin-right: 5px;]',
							'content' => '<strong>Distância</strong>'
						),
						array(
							'renderer' => 'input[type=text][id=qtde_km][name=qtde_km][style=margin-right: 10px;].input-small'
						),
						array(
							'renderer' => 'input[type=hidden][id=qtde_km_hidden][name=qtde_km_hidden]'
						),
						array(
							'renderer' => 'button[type=button][id=calcular_km].btn.btn-primary.btn-small',
							'content' => 'Calcular KM'
						)
					)
				)
			)
		);
	$html[] = 
		array(
			'class' => 'div[id=div_km_google].tc_formulario',
			'content' => array(
				array(
					'class' => 'div.titulo_tabela',
					'content' => 'Informações de Deslocamento'
				),
				array(
					'class' => 'div[id=GoogleMaps]'
				),
				array(
					'class' => 'div[id=GoogleMapsDirection]'
				)
			)
		);
	$html[] = 
		array(
			'class' => 'br',
		);
	$html[] =
		array(//Informações Revenda
			'class' => 'div.tc_formulario',
			'content' => array(
				array(
					'class' => 'div.titulo_tabela',
					'content' => 'Informações da Revenda'
				),
				array(
					'class' => 'br'
				),
				array(
					'class' => 'BootstrapRows',
					'content' => $camposFabrica['revenda']
				),
			)
		);
	$html[] = 
		array(
			'class' => 'br',
		);
	$html[] = 
		array(//Informações do Produto
			'class' => 'div.tc_formulario',
			'content' => array(
				array(
					'class' => 'div.titulo_tabela',
					'content' => 'Informações do Produto'
				),
				array(
					'class' => 'br'
				),
				array(
					'class' => 'BootstrapRows',
					'attr' => array(
						'id' => 'produto'
					),
					'content' => $camposFabrica['produto']
				),
				array(
					'class' => 'br'
				),
				array(
					'renderer' => 'div.row-fluid.ProdutoClearButton',
					'attr' => array(
						'style' => 'display:none;'
					),
					'content' => array(
						array(
							'renderer' => 'div.span1'
						),
						array(
							'renderer' => 'div.span10>div.control-group>div.controls.controls-row>div.span10>button[type=button].btn.btn-small.btn-danger.ClearButton',
							'content' => 'Remover Produto'
						)
					),
				),
			)
		);
	$html[] = array(
		'class' => 'br',
	);
	if($usaSubproduto){
		$html[] = 
		array(//Informações do SubProduto
			'class' => 'div.tc_formulario.SubprodutoForm',
			'attr' => array(
				'style' => 'display:none',
				'id' => 'subproduto'
			),
			'content' => array(
				array(
					'class' => 'div.titulo_tabela',
					'content' => 'Informações do Subconjunto'
				),
				array(
					'class' => 'br'
				),
				array(
					'class' => 'BootstrapRows',
					'content' => $camposFabrica['subproduto']
				),
				array(
					'class' => 'br'
				),
				array(
					'renderer' => 'div.row-fluid.SubprodutoClearButton',
					'attr' => array(
						'style' => 'display:none;'
					),
					'content' => array(
						array(
							'renderer' => 'div.span1'
						),
						array(
							'renderer' => 'div.span10>div.control-group>div.controls.controls-row>div.span10>button[type=button].btn.btn-small.btn-danger.ClearButton',
							'content' => 'Remover Subconjunto'
						)
					),
				),
			)
		);	
		$html[] = 
		array(
			'class' => 'br',
		);
	}
	$html[] = 
		array(//Soluçao
			'class' => 'div.tc_formulario',
			'content' => array(
				array(
					'class' => 'div.titulo_tabela',
					'content' => 'Solução'
				),
				/*array(
					'class' => 'br',
				),*/
				array(
					'class' => 'BootstrapRows',
					'attr' => array(
						'class' => array('tac')
					),
					'content' => $camposFabrica['solucao']
				),
			)
		);
	$html[] = 
		array(
			'class' => 'br',
		);
	$html[] = 
		array(
			'class' => 'div',
			'content' => array(
				array(
					'class' => 'div.tc_formulario',
					'content' => array(
						array(
							'class' => 'div.titulo_tabela',
							'content' => 'Peças do Produto'
						),
						array(
							'class' => 'br'
						),
						array(
							'renderer' => 'div.tac>button[produto=0][type=button][name=lista_basica].btn.btn-small',
							'content' => 'Lista Básica'
						),
						array(
							'class' => 'br'
						),
						array(
							'class'=>'AutoList.lista-item',
							'attr' => array(
								'id' => 'produto-lista-item',
								'list-wildcard' => '*item*',
								'name' => 'os[osProduto][0][os_item]',
								'list-min-size' => 0,
								'list-start-size' => 3,
							),
							'content'=>array(
								array(
									'class' => 'BootstrapRows',
									'content' => $camposFabrica['produto_item'],
								),
								array(
									'class' => 'br'
								),
							),
						),
					),
				),
				array(
					'renderer' => 'button.btn.btn-success.btn-small.ListButtonAdd',
					'attr' => array(
						'type' => 'button',
						'list-target' => 'produto-lista-item'
					),
					'content' => 'Adiciona Peça'
				),
				array(
					'class' => 'br',
				),
				array(
					'class' => 'br',
				)	
			)
		);
	if($usaSubproduto){
		$html[] = 
		array(
			'class' => 'div.SubprodutoForm',
			'attr' => array(
				'style' => 'display:none'
			),
			'content' => array(
				array(
					'class' => 'div.tc_formulario',
					'content' => array(
						array(
							'class' => 'div.titulo_tabela',
							'content' => 'Peças do Subconjunto'
						),
						array(
							'class' => 'br'
						),
						array(
							'renderer' => 'div.tac>button[produto=1][type=button][name=lista_basica].btn.btn-small',
							'content' => 'Lista Básica'
						),
						array(
							'class' => 'br'
						),
						array(
							'class'=>'AutoList.lista-item',
							'attr' => array(
								'id' => 'subproduto-lista-item',
								'list-wildcard' => '*item*',
								'name' => 'os[os_produto][1][os_item]',
								'list-min-size' => 0,
								'list-start-size' => 3,
							),
							'content'=>array(
								array(
									'class' => 'BootstrapRows',
									'content' => $camposFabrica['subproduto_item'],
								),
								array(
									'class' => 'br'
								),
							),
						),
					),
				),
				array(
					'class' => 'button.btn.btn-success.btn-small.ListButtonAdd',
					'attr' => array(
						'type' => 'button',
						'list-target' => 'subproduto-lista-item'
					),
					'content' => 'Adiciona Peça'
				),
				array(
					'class' => 'br',
				),
			)
		);	
		$html[] = 
		array(
			'class' => 'br',
		);
	}
	$html[] = 
		array(//Observações da OS
			'class' => 'div.tc_formulario',
			'content' => array(
				array(
					'class' => 'div.titulo_tabela',
					'content' => 'Observações da OS'
				),
				array(
					'class' => 'br'
				),
				array(
					'class' => 'BootstrapRows',
					'content' => $camposFabrica['obs']
				),
			)
		);
	$html[] = 
		array(
			'class' => 'br',
		);
			$html[] = 
		array(//Observações da OS
			'class' => 'div.tc_formulario',
			'content' => array(
				array(
					'class' => 'div.titulo_tabela',
					'content' => 'Anexo de Nota Fiscal'
				),
				array(
					'class' => 'br'
				),
				array(
					'class' => 'BootstrapRows',
					'content' => $camposFabrica['anexo_nf']
				),
			)
		);
	$html[] = 
		array(
			'class' => 'br',
		);
	$html[] = 
		array(
			'class' => 'input[type=hidden][value=submit][name=btn_acao]',
		);
	$html[] = 
		array(
			'class' => 'div.tac>button.btn.btn-large',
			'content' => 'Gravar'
		);
	return $html;
}

function tryUpdateRevenda($revenda){
	unset($revenda['estado']);
	$revenda['cnpj'] = preg_replace('@[^0-9]*@','',$revenda['cnpj']);
	$revenda['cep'] = preg_replace('@[^0-9]*@','',$revenda['cep']);
	$model = ModelHolder::init('Revenda');
	$revendaOld = $model->select(array('cnpj'=>$revenda['cnpj']));
	if(empty($revendaOld)){
		$model->insert($revenda);
		return;
	}
	foreach($revenda as $key => $value){
		if(empty($value) && !array_key_exists($key,$revendaOld))
			continue;
		$revendaOld[$key] = $value;
	}
	foreach ($revendaOld as $key => $value) {
		if(empty($value))
			unset($revendaOld[$key]);
	}
	unset($revendaOld['cnpj']);
	$model->update($revendaOld,array('revenda'=>$revendaOld['revenda']));
}

function tryInsertOS(){
	$post =RequestHelper::preparePostParameters();
	$os = $post['os'];
	$os['anexoNf'] = $_FILES['anexo_nf']['name'];
	$produtos = $os['produto'];
	$revenda = $os['revenda'];
	$revenda['cnpj'] = $os['revendaCnpj'];
	$revenda['nome'] = $os['revendaNome'];

	try{
		tryUpdateRevenda($revenda);	
	}
	catch(ValidateException $ex){
		$msg_erro = $ex->toMsgErro('os[revenda_%]');
		$htmlBuilder = HtmlBuilder::getInstance();
		foreach ($msg_erro['campos'] as $campo) {
			$htmlBuilder->addError($campo);
		}
		return $msg_erro;
	}
	

	unset($os['revenda']);
	unset($os['btnAcao']);
	unset($os['lupaConfig']);
	$itens = ArrayHelper::groupArray($itens,'produto');
	foreach ($os['osProduto'] as &$osProduto) {
		unset($osProduto['referencia']);
		unset($osProduto['descricao']);
		unset($osProduto['voltagem']);
	}
	// error_reporting(E_ALL);
	/* echo '<pre>';
	var_dump($_POST);
	var_dump($os);
	echo '</pre>'; */
	try{
		$model 	= ModelHolder::init('OS');
		$os 	= $model->insert($os);

		header('Location: os_press?os='.$os);
	}
	catch(ValidateException $ex){
		$htmlBuilder = HtmlBuilder::getInstance();
		$htmlBuilder->fillError('os',$ex);
		return $ex->toMsgErro();
	}
	catch(Exception $ex){
		return array(
			'msg' => array($ex->getMessage()),
			'campos' =>array()
		);
	}

}

