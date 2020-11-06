<?php

global $title;
$title = "Cadastro de Subprodutos";

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

use html\HtmlBuilder;
use model\ModelHolder;
use util\NameHelper;

$htmlBuilder = HtmlBuilder::getInstance();
$produtoPai = null;
$subprodutos = null;

if(!empty($_REQUEST['subproduto']) && $_REQUEST['action'] == 'insert'){
	header('Content-Type: application/json');
	$subproduto = NameHelper::prepareArray($_REQUEST['subproduto']);
	$subproduto2 = NameHelper::prepareArray($_REQUEST['subproduto2']);
	$model = ModelHolder::init('Subproduto');
	try{
	/*	$teste = $model->select($subproduto2);
		print_r($teste);
		exit;*/
		if($model->select($subproduto)){
			throw new Exception('Este subproduto já está cadastrado para esse produto');
		}

		if($login_fabrica == 138){
			if($subproduto2['produtoPai'] == $subproduto2['produtoFilho']){ //HD-3158226
				throw new Exception('Este subproduto já está cadastrado como produto pai deste produto');
			}
		}else{
			if($model->select($subproduto2)){
				throw new Exception('Este subproduto já está cadastrado como produto pai deste produto');
			}
		}

		$subproduto['subproduto'] = $model->insert($subproduto);
		$model = ModelHolder::init('Produto');
		$produto = $model->select($subproduto['produtoFilho']);
		$subproduto['descricao'] = $produto['descricao'];
		$subproduto['referencia'] = $produto['referencia'];
		echo json_encode(array(
				'type' => 'success',
				'element' => $subproduto
			));
	}
	catch(Exception $ex){
		echo json_encode(array(
				'type' => 'error',
				'message' => utf8_encode($ex->getMessage())
			));
	}
	die();
}

if(!empty($_REQUEST['subproduto']) && $_REQUEST['action'] == 'remove'){
	header('Content-Type: application/json');
	$model = ModelHolder::init('Subproduto');
	$subproduto = NameHelper::prepareArray($_REQUEST['subproduto']);
	try{
		$model->delete($subproduto);
		echo json_encode(array(
			'type' => 'success',
		));
	}
	catch(Exception $ex){
		echo json_encode(array(
			'type' => 'error',
			'message' => $ex->getMessage()
		));
	}
	die();
}

if(!empty($_REQUEST['produto']['produto'])){
	$produtoPai = $_REQUEST['produto']['produto'];
	$produtoModel = ModelHolder::init('Produto');
	$produtoPai = $produtoModel->select(
		array('produto'=>$produtoPai,'fabrica_i'=>$produtoModel->getFactory())
	);
}

include 'cabecalho_new.php';

$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"alphanumeric",
	"price_format"
);


include("plugin_loader.php");

?>
<style type="text/css">
	.AutoListModel{
		display : none;
	}
</style>
<script type="text/javascript">

	$(function(){
		$.autoList();
		Shadowbox.init();
		$('#btAddSubproduto').click(function(){
			var subproduto = $("input[name='subproduto[produto]']").val();
			if(!subproduto){
				alert('Busque um subproduto');
				return;
			}
			addSubproduto(subproduto);
		});
	});

	$(document).on('click','span[rel=lupa]',function(){
		$.lupa($(this), ["ativo", "produto","posicao"]);
	});

	$(document).on('click','.btRemoveSubproduto[produto_pai][produto_filho]',function(){
		var produtoPai = $(this).attr('produto_pai');
		var produtoFilho = $(this).attr('produto_filho');

		var line = $(this).parents('tr')[0];
		var params = {
			"subproduto" :{
				'produto_pai' : produtoPai,
				'produto_filho' : produtoFilho
			},
			'action' : 'remove'
		};
		loading('show');
		$.ajax({
			data : params,
			success : function(data){
				if(data.type=="error"){
					alert(data.message);
					return;
				}
				$(line).remove();
			},
			error : function(error){
				alert('Erro de Conexão');
			},
			complete : function(){
				loading('hide');
			}
		});

	});

	var retorna_produto = function(produto){
		console.debug(produto);
		eval('window.'+produto.posicao+'(produto);');
	};

	var retornaProduto = function(produto){
		$("input[name='produto[produto]']").val(produto.produto);
		$("input[name='produto[referencia]']").val(produto.referencia);
		$("input[name='produto[descricao]']").val(produto.descricao);
		$("input[name='produto[produto]']").parents('form').submit();
	};

	var retornaSubproduto = function(produto){
		$("input[name='subproduto[produto]']").val(produto.produto);
		$("input[name='subproduto[referencia]']").val(produto.referencia);
		$("input[name='subproduto[descricao]']").val(produto.descricao);
	};


	var addSubproduto = function(produto){
		var produtoPai = $("input[name='produto[produto]']").val();
		var produtoFilho = $("input[name='subproduto[produto]']").val();
		var params = {
			"subproduto" : {
				"produto_pai" : produtoPai,
				"produto_filho" : produtoFilho
			},
			"subproduto2" : {
				"produto_pai" : produtoFilho,
				"produto_filho" : produtoPai
			},
			"action" : "insert"
		};
		loading('show');

		var addSubprodutoOnTable = function(subproduto){
			var line = $('<tr></tr>');
			line.append($('<td>'+subproduto.referencia+'</td>'));
			line.append($('<td>'+subproduto.descricao+'</td>'));
			line.append(
			$('<td><div class="tac"><button class="btRemoveSubproduto btn btn-small btn-danger" produto_pai="'+subproduto.produtoPai+'" produto_filho="'+subproduto.produtoFilho+'" >Remover</button></div></td>'));
			$('#tableBodySubprodutos').append(line);
		};

		$.ajax({
			data : params,
			success : function(data){
				if(data.type == "error"){
					alert(data.message);
					return;
				}
				addSubprodutoOnTable(data.element);
				$("input[name='subproduto[produto]']").val('');
				$("input[name='subproduto[referencia]']").val('');
				$("input[name='subproduto[descricao]']").val('');
			},
			error : function(){
				alert('Erro de Conexão');
			},
			complete : function(){
				loading('hide')
			}
		});



	};

</script>
<?php

$html = array();

$html[] = array(
	'class' => 'form[method=POST]>div.tc_formulario',
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
			'content' => buscaProdutoHtml()
		),
		array(
			'class' => 'br'
		),
	)
);


$html[] = array('class'=>'br');

if($produtoPai){

	$html[] = array(
		'class' => 'div.tc_formulario',
		'content' => array(
			array(
				'class' => 'div.titulo_tabela',
				'content' => 'Subprodutos'
			),
			array(
				'class'=>'table.table.table-striped.table-bordered.table-hover.table-large',
				'content' => array(
					array(
						'class' => 'thead>tr.titulo_coluna',
						'content' => array(
							array(
								'class' => 'th',
								'content' => 'Referência'
							),
							array(
								'class' => 'th',
								'content' => 'Descrição'
							),
							array(
								'class' => 'th',
								'content' => 'Opções'
							),
						),
					),
					array(
						'class' => 'tbody',
						'attr' => array(
							'id' => 'tableBodySubprodutos'
						),
						'content' => subprodutosHtml($produtoPai['produto'])
					),
					array(
						'class' => 'tfoot'
					),
				)
			),
			array(
				'class' => 'br'
			),
			array(
				'class' => 'BootstrapRows',
				'content' =>buscaSubprodutoHtml()
			),
			array(
				'class' => 'br'
			),
			array(
				'class' => 'button[type=button].btn.btn-success.btn-small',
				'attr' => array(
					'id' => 'btAddSubproduto',
				),
				'content' => 'Adicionar Subproduto'
			),
			array(
				'class' => 'br'
			),
			array(
				'class' => 'br'
			),
		)
	);
}



$html = $htmlBuilder->build($html);
$html->render();



include 'rodape.php';

function subprodutosHtml($produtoPai){
	$model = ModelHolder::init('Produto');
	$sql =
		'SELECT tbl_produto.*
		 FROM tbl_produto
		 INNER JOIN tbl_subproduto
		 	ON (tbl_subproduto.produto_filho = tbl_produto.produto)
		 WHERE tbl_produto.fabrica_i = :fabrica AND tbl_subproduto.produto_pai = :produto_pai ';
	$params = array(':fabrica' => $model->getFactory(),':produto_pai'=>$produtoPai);
	$produtos = $model->executeSql($sql,$params);
	$tableLines = array();
	foreach ($produtos as $subproduto) {
		$tableLines[] = array(
			'class' => 'tr',
			'content' => array(
				array(
					'class' => 'td',
					'content'=>$subproduto['referencia']
				),
				array(
					'class' => 'td',
					'content'=>$subproduto['descricao']
				),
				array(
					'class' => 'td>div.tac>button.btn.btn-danger.btn-small.btRemoveSubproduto',
					'attr' => array(
						'produto_pai' => $produtoPai,
						'produto_filho' => $subproduto['produto'],
					),
					'content'=> 'Remover'
				),
			),
		);
	}
	return $tableLines;
}

function buscaProdutoHtml(){
	return
	array(
		"produto[produto]" => array(
			"id"        => "",
			"type"      => "input/hidden",
			"required"  => true,
		),
		"produto[referencia]" => array(
			"id"        => "",
			"span"      => 4,
			"label"     => "Referência",
			"type"      => "input/text",
			"width"     => 10,
			"lupa" => array(
	            "name" => "lupa_config",
	            "tipo" => "produto",
	            "parametro" => "referencia",
	            "extra" => array(
	            	"posicao" => "retornaProduto",
	                "ativo" => true,
	            )
	        )
		),
		"produto[descricao]" => array(
			"id"        => "",
			"span"      => 6,
			"label"     => "Descrição",
			"type"      => "input/text",
			"width"     => 10,
			"lupa" => array(
	            "name" => "lupa_config",
	            "tipo" => "produto",
	            "parametro" => "descricao",
	            "extra" => array(
	            	"posicao" => "retornaProduto",
	                "ativo" => true,
	            )
	        )
		)
	);
}

function buscaSubprodutoHtml(){
	return
	array(
		"subproduto[produto]" => array(
			"id"        => "",
			"type"      => "input/hidden",
			"required"  => true,
		),
		"subproduto[referencia]" => array(
			"id"        => "",
			"span"      => 4,
			"label"     => "Referência",
			"type"      => "input/text",
			"width"     => 10,
			"lupa" => array(
	            "name" => "lupa_config",
	            "tipo" => "produto",
	            "parametro" => "referencia",
	            "extra" => array(
	                "ativo" => true,
	                "posicao" => "retornaSubproduto"
	            )
	        )
		),
		"subproduto[descricao]" => array(
			"id"        => "",
			"span"      => 6,
			"label"     => "Descrição",
			"type"      => "input/text",
			"width"     => 10,
			"lupa" => array(
	            "name" => "lupa_config",
	            "tipo" => "produto",
	            "parametro" => "descricao",
	            "extra" => array(
	                "ativo" => true,
	                "posicao" => "retornaSubproduto"
	            )
	        )
		)
	);
}

