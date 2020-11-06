<?php
/**
 * Cadastro de defeito reclamado por produto para o CallCenter.
 * Manuel López
 * 2017-01-03
 * Origem: HD 3176543
 * ELLO
 */
require_once 'dbconfig.php';
require_once 'includes/dbconnect-inc.php';

$admin_privilegios = "cadastros"; // callcenter financeiro gerencia infotecnica auditoria

$fabrica_grava_novo_defeito = in_array($login_fabrica, array('nenhuma'));

require_once 'autentica_admin.php';
require_once 'funcoes.php';
require_once '../class/fn_sql_cmd.php';

header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Content-Type: text/html; charset=iso-8859-15");

/**
 * funções auxiliares
 */
$deleteBtn = function($dpID, $prodID, $ref) {
	return "<button type='button' title='Excluir' class='btn btn-danger btnExcl' data-id='$dpID,$prodID,$ref'><i class='icon icon-white icon-remove'></i></button>";
};

$editButton = function($drID, $prodID, $ref, $desc, $status) {
	return "<button type='button' title='Alterar' class='btn btn-default btnEdit' data-id='".join(';',array_filter(func_get_args()))."'><i class='icon icon-edit'></i></button>";
};

$linkDefeito = function($id, $codigo, $text) {
	if (strlen($codigo))
		$codigo = "<span class='label label-default'>$codigo</span><br />";

	return "<a target='_new' title='consultar ou editar este defeito reclamado' href='defeito_reclamado_cadastro.php?defeito=$id'>$codigo$text</a>";
};

$linkProduto = function($id, $text) {
	/* return "<a target='_new' href='produto_cadastro.php?produto=$id'>$text</a>"; */
	return "<span class='product btn-link' title='Mostrar defeitos para este produto' data-id='$id'>$text</a>";
};

function productTable($data, $produto_referencia) {
	return array2table(array_merge(
		array('attrs' => array(
			'tableAttrs'   => ' class="table table-striped table-bordered table-hover table-fixed" id="resultados"',
			'captionAttrs' => ' class="titulo_tabela"',
			'headerAttrs'  => ' class="titulo_coluna"',
			'rowAttrs'     => ' class="top aligned"'
		)),
		$data), "Defeitos cadastrados para $produto_referencia"
	);
}

function listaDefeitoProduto($produto=null, $ativos=true) {
	global $con, $login_fabrica,
		$linkDefeito, $linkProduto, $deleteBtn, $editButton;

	$where = array( // WHERE
		'DR.ativo' => true,
		'DG.ativo' => $ativos,
		'DP.fabrica' => $login_fabrica
	) ;

	if (!is_null($produto)) {
		$where['DP.produto'] = $produto;
		unset($where['DG.ativo']); // Quando consulta um produto, mostra também os defeitos não ativos
	}

	$sql = sql_cmd(
		array(
			'tbl_diagnostico_produto AS DP',
			'JOIN tbl_diagnostico         AS DG USING(diagnostico, fabrica)',
			'JOIN tbl_defeito_reclamado   AS DR USING(fabrica,defeito_reclamado)',
			'JOIN tbl_produto             AS P  USING(produto)',
		),
		'DP.diagnostico_produto AS id, ' .
			'DR.defeito_reclamado, ' .
			'DR.ativo     AS dr_ativo, ' .
			'P.produto    AS produto, ' .
			'P.referencia AS produto_referencia, ' .
			'P.descricao  AS produto_descricao, ' .
			'DR.descricao AS dr_descricao, ' .
			'DR.codigo    AS dr_codigo',
		$where
	);

	if (DEBUG and $_REQUEST['sql'])
		die($sql);

	$res = pg_query($con, $sql);

	if (!is_resource($res)) {
		return('Não foi possível consultar os defeitos para o produto.');
	}

	if (pg_num_rows($res) == 0) {
		return ('Produto sem defeito reclamado cadastrado.');
	}

	$produto_defeito = pg_fetch_all($res);
	$tabela = array();

	if (DEBUG)
		echo count($produto_defeito) . ' registros</br />';

	foreach ($produto_defeito as $i => $defeito) {
		extract($defeito);

		$tabela[] = array(
			'Ref. Produto'      => $linkProduto($produto, $produto_referencia),
			'Produto'           => $linkProduto($produto, $produto_descricao),
			'Defeito Reclamado' => $linkDefeito($defeito_reclamado, $dr_codigo, $dr_descricao),
			'Ação'              => $editButton($defeito_reclamado, $produto, $produto_referencia, $produto_descricao, $ativo) . '&nbsp;' .
				$deleteBtn($id, $produto, $produto_referencia)
		);
	}
	return $tabela;
}

/* *** AJAX *** */
if ($_REQUEST['ajax'] == 'sim') {

	if ($_REQUEST['tabela'] == 'defeitos') {
		$produto = getPost('produto') ? : null;
		$ativos  = getPost('lista_inativos') ? false : true;

		if (strlen($produto) and !is_numeric($produto)) {
			die('Parâmetro de consulta inválido!');
		}

		if (!$produto_referencia)
			$produto_referencia = 'TODOS OS PRODUTOS';

		die(productTable(listaDefeitoProduto($produto), $produto_referencia));

	}

	if (strlen($_REQUEST['delDP'])) {
		list($diagnostico_produto, $produto, $produto_referencia) = explode(',', $_REQUEST['delDP']);

		if (!is_numeric($diagnostico_produto)) {
			die('Parâmetro de consulta inválido!');
		}

		$sql = sql_cmd('tbl_diagnostico_produto', 'delete', array(
			'diagnostico_produto' => $diagnostico_produto,
			'produto' => $produto,
			'fabrica' => $login_fabrica
		));

		pg_begin();
		$res = pg_query($con, $sql);

		if (!is_resource($res))
			die ('Houve um problema ao excluir o relacionamento de Defeito Reclamado e o Produto.');

		if (!pg_affected_rows($res)) {
			pg_rollBack();
			die ('Não foi possível excluir o relacionamento de Defeito Reclamado e o Produto.');
		}

		// Por enquanto está excluíndo apenas um por vez. Revisar quando pedirem para excluir vários
		// de uma só vez.
		if (pg_affected_rows($res) > 1) {
			pg_rollBack();
			die('Erro ao excluir o defeito para o produto, mais de uma coincidência.');
		}

		pg_commit();

		die('Relacinoamento excluído!|' . productTable(listaDefeitoProduto($produto), $produto_referencia));
	}

	if ($_REQUEST['btn_acao'] == 'gravar') {

		$produto = getPost('produto');
		$ativo   = (getPost('ativo') == 't');     // TRUE se <=> 't'
		$defeito = getPost('defeito_reclamado');
		$prodref = getPost('produto_referencia');


		if (!is_numeric($produto)){
			die ('Informações do produto incorretas! Selecione um produto e tente novamente.');
		}

		$msg_erro = "";
	
		if(!is_array($defeito)) {
			die('Nenhum Defeito Reclamado selecionado.|' . productTable(listaDefeitoProduto($produto), $produto_referencia));
		}

 		foreach($defeito as $i => $def) {

			$sql = sql_cmd(
				array(
					'tbl_diagnostico_produto',
					'JOIN tbl_diagnostico USING(fabrica, diagnostico)',
					'JOIN tbl_defeito_reclamado USING(fabrica, defeito_reclamado)'
				),
				array('diagnostico_produto','descricao'),
				array(
					'fabrica' => $login_fabrica,
					'produto' => $produto,
					'defeito_reclamado' => $def
				)
			);

			$res=pg_query($con, $sql);
			if (pg_num_rows($res)) {

				//Já existe o diadgnostico_produto, não há a necessidade de inserir novamente.
				continue;

				/*if (DEBUG and $_REQUEST['sql']) pecho($sql);
				die('Defeito Reclamado já cadatrado para o produto '.$prodref." Sendo o Nro. $def");*/
			}

			$sql = sql_cmd(
					'tbl_diagnostico',
					'diagnostico',
					array(
						'fabrica'           => $login_fabrica,
						'defeito_reclamado' => $def
					)
				);

			$res = pg_query($con, $sql);

			if (!pg_num_rows($res)) {
				// Se não existe 'diagnóstico' do defeito reclamado, grava ele
				$ins = sql_cmd(
					'tbl_diagnostico',
					array(
						'fabrica'           => $login_fabrica,
						'defeito_reclamado' => $def
					)
				) . ' RETURNING diagnostico';

				$res = pg_query($con, $ins);
				
				if (!is_resource($res)) {
					$msg_erro = "Erro ao inserir o diagnóstico para o produto ".$prodref;
					continue;
				}
			}

			$diagnostico = pg_fetch_result($res, 0, 0);

			$newDP = sql_cmd(
				'tbl_diagnostico_produto',
				array(
					'diagnostico' => $diagnostico,
					'fabrica'     => $login_fabrica,
					'produto'     => $produto
				)
			);

		
			$res = pg_query($con, $newDP);

			if (!pg_affected_rows($res)) {
				$txtProbema = "Problemas ao associar o diagnóstico para o produto ".$prodref;
			}

		}

		if (strlen($txtProbema)>0 ) {
			die($txtProbema . '|' . productTable(listaDefeitoProduto($produto), $prodref));
		}else{
			die("Defeito(s) Reclamado(s) associado(s) corretamente ao produto $prodref!|".
				productTable(listaDefeitoProduto($produto), $prodref));
		}
	}
}

/**
 * Tela
 */
$title = 'CADASTRO DE DEFEITO RECLAMADO POR PRODUTO';

include_once 'cabecalho_new.php';

// Plugins JS
$plugins = array('autocomplete', 'shadowbox', 'dataTable',"multiselect");
include_once 'plugin_loader.php';


// Formulário
// Define o formulário com o array
$hiddenFields = array('produto_id');
$inputFields  = array(
    'referencia' => array(
        'id' => 'produto_referencia',
        'type' => 'input/text',
        'label' => 'Referência',
        'span' => 4,
        'width' =>6,
        'maxlength' => 20,
        'lupa' => array(
            'name' => 'lupa_config',
            'tipo' => 'produto',
            'parametro' => 'referencia',
            'extra' => array(
                'produto_id' => 'true'
            )
        ),
		'required' => true,
		'extra' => array('autofocus' => true)
    ),
    'descricao' => array(
        'id' => 'produto_descricao',
        'type' => 'input/text',
        'label' => 'Descrição',
        'span' => 4,
        'maxlength' => 80,
        'lupa' => array(
            'name' => 'lupa_config',
            'tipo' => 'produto',
            'parametro' => 'descricao',
            'extra' => array(
                'produtoId' => 'true'
            )
        ),
    ),
	/*
	'ativo' => array(
		'label'    => '',
		'type'     => 'checkbox',
		'checks'   => array('t' => 'Ativo'),
		'required' => true,
		'span'     => 4,
		'extra'    => array(
			'id'       => 'defeito_ativo'
		)
	),
	*/
	'defeito_reclamado' => array(
		'label'   		=> 'Defeito Reclamado',
		'type'    		=> 'select',
		'extra' 		=> array('multiple'=>'multiple'),
		'options' 		=> pg_fetch_pairs(
			$con,
			sql_cmd(
				'tbl_defeito_reclamado',
				'defeito_reclamado, descricao',
				array('ativo'=>true,'fabrica'=>$login_fabrica)
			)
		),
		'width'   => 12,
		'span'    => 4
	),
);

// Mostra o formulário
?>
	<form name='frm_condicao' method='POST' class='form-search form-inline tc_formulario' >
		<div class='titulo_tabela '><?=$title?></div>
		<br/>
		<?=montaForm($inputFields, $hiddenFields)?>
		<p>
		<br/>
			<button id="listar" class="btn btn-info" type="button">Listar Defeitos</button>
			<button id="gravar" class='btn' type='button'>Gravar</button>
			<button id="limpar" class='btn btn-warning' type='button'>Limpar</button>
		</p>
		<br/>
	</form>

	<div id="tabela" class="container"></div>
	<script>
	function defeitos_do_produto(id, ref) {
		var refProd = (typeof ref !== 'undefined') ? '&produto_referencia='+ref : '';

		$("#tabela").load(
			window.location.pathname,
			"ajax=sim&tabela=defeitos&produto=" + id + refProd
		);
		var table = {
			table: '#tabela table',
			type: 'basic'
		};
		$.dataTableLoad(table);
	}

	function retorna_produto(json){
		console.log(json);
		if (json.produto !== undefined) {
			$("#produto_id").val(json.produto);
			$("#produto_referencia").val(json.referencia);
			$("#produto_descricao").val(json.descricao);
			defeitos_do_produto(json.produto);
		}
	}

	$(function() {
		Shadowbox.init();
		$.autocompleteLoad(["produto"], ['produto']);
		$("span[rel^=lupa]").click(function () {
			$.lupa($(this), ['produtoId'] );
		});

		$("#defeito_reclamado").multiselect({
        	selectedText: "selecionados # de #"
        });

		$("#gravar").click(function() {
			$.post(
				document.location.pathname, {
					ajax: 'sim',
					btn_acao: 'gravar',
					produto: $("#produto_id").val(),
					/* ativo: $("[name=''ativo[]']").eq(0).is('checked'), */
					defeito_reclamado: $('#defeito_reclamado').val(),
					produto_referencia: $('#produto_referencia').val()
				}, function(response) {
					info = response.split('|');
					if (info.length === 2) {
						$("#tabela").html(info[1]);
					}
					alert(info[0]);
			});
		});

		$("#limpar").click(function() {
			$("input,select").val('');
		});

		$("#listar").click(function() {
			defeitos_do_produto($("[name=produto_id]").val(), $("#produto_referencia").val());
		});

		$('#tabela').on('click', '.product.btn-link', function() {
			var id = $(this).data('id');
			defeitos_do_produto(id, $(this).text());
		});

		$('#tabela').on('click', '.btnEdit', function() {
			var data = $(this).data('id').split(';');
			$("#produto_id").val(data[1]);
			$("#defeito_reclamado").val(data[0]);
			$("#produto_referencia").val(data[2]);
			$("#produto_descricao").val(data[3]);
			/* $("#defeito_ativo").check(); */
			document.getElementsByName('frm_condicao')[0].scrollIntoView();
		});

		$('#tabela').on('click', '.btnExcl', function() {
			var id = $(this).data('id');
			$.post(
				document.location.pathname,
				{ajax: 'sim', delDP: id},
				function(ret) {
					var info = ret.split('|');
					if (info.length === 2) {
						$("#tabela").html(info[1]);
					}
					alert(info[0]);
			});
		});
	});
	</script>
	<style>
	#resultados td:last-of-type {
	  text-align: right;
	}
	</style>
<?

include_once 'rodape.php';

