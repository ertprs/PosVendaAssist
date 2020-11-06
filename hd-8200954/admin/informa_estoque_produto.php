<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$atualizarProduto = filter_input(INPUT_POST, 'atualizarProduto', FILTER_VALIDATE_BOOLEAN);
if( $atualizarProduto ){
	$formData = $_POST;

	$produto = $formData['produto'];
	$origem  = $formData['origem'];
	$estado  = $formData['estado'];

	$stmt = $pdo->prepare("SELECT parametros_adicionais FROM tbl_produto WHERE produto = :produto");
	$stmt->bindValue(':produto', $produto);

	if( !$stmt->execute() ){
		exit(json_encode([
			'error' => true,
			'message' => 'Produto não encontrado.'
		]));
	}

	$parametrosAdicionais = $stmt->fetch(PDO::FETCH_ASSOC)['parametros_adicionais'];
	$parametrosAdicionais = json_decode($parametrosAdicionais, true);
	if( !$parametrosAdicionais ){
		exit(json_encode([
			'error' => true,
			'message' => 'Não foi possível realizar esta operação.'
		]));
	}

	if( empty($parametrosAdicionais) ){
		$parametrosAdicionais = [];
		$parametrosAdicionais['consultar_estoque'] = [$origem];
	}else{
		$keys = array_keys($parametrosAdicionais);
		if( in_array('consultar_estoque', $keys) ){
			if( $estado === 'false' ){
				$index = array_search($origem, $parametrosAdicionais['consultar_estoque']);
				unset($parametrosAdicionais['consultar_estoque'][$index]);
			}else if( $estado === 'true' ){
				$parametrosAdicionais['consultar_estoque'][] = $origem;
			}
		}else{
			$parametrosAdicionais['consultar_estoque'] = [$origem];
		}
	}

	$data = json_encode($parametrosAdicionais);
	$stmt = $pdo->query("UPDATE tbl_produto SET parametros_adicionais = '{$data}' WHERE produto = {$produto}");
	if( $stmt->rowCount() == 0){
		exit(json_encode([
			'error' => true,
			'message' => 'Não foi possível atualizar este produto.'
		]));
	}

	exit(json_encode([
		'error' => false,
		'message' => 'Produto atualizado com sucesso.'
	]));
}

$title = "INFORMA PRODUTO ESTOQUE";
include 'cabecalho_new.php';

$plugins = array(
	'jquery3',
	'multiselect',
	'shadowbox',
	'dataTable'
);
include("plugin_loader.php");

function createCsvAndReturnLink($data, $admin){ 
	$path = '../xls/';
	$fileName = "informa_estoque_posto_{$admin}.csv";
	$fullPath = $path . $fileName;
	$delimitador = ';';
	
	$handler = fopen($fullPath, "w+");

	$header = ['REFERÊNCIA', 'DESCRIÇÃO', 'FAMÍLIA', 'LINHA', 'ORIGEM', 'NORDESTE', 'SUL'];
	fputcsv($handler, $header, $delimitador);

	$row = [];
	foreach($data as $item){
		list($produto, $referencia, $descricao, $origem, $estoque, $familia, $linha) = array_values($item);

		$row['referencia'] = $referencia;
		$row['descricao'] = $descricao;
		$row['familia'] = $familia;
		$row['linha'] = $linha;

		if($origem == 'mk_nordeste') $row['origem'] = 'Nordeste';
		if($origem == 'mk_sul') $row['origem'] = 'Sul';

		if($estoque){
			$arrEstoque = json_decode($estoque, true);

			$pos = array_search('mk_nordeste', $arrEstoque);
			$row['nordeste'] = ($pos !== false) ? 'Sim' : 'Não';
	
			$pos = array_search('mk_sul', $arrEstoque);
			$row['sul'] = ($pos !== false) ? 'Sim' : 'Não';
		}else{
			$row['nordeste'] = $row['sul'] = 'Não';
		}

		fputcsv($handler, $row, $delimitador);
		$row = [];
	}

	return $fullPath;
}

function sanitizeArrayFields($array){
	$arraySanitizado = array_map(function($item){
		return trim($item);
	}, $array);

	return $arraySanitizado;
}

$acaoPesquisar = filter_input(INPUT_POST, 'pesquisar', FILTER_VALIDATE_BOOLEAN);
if( $acaoPesquisar ){
	$formData = $_POST;

	if (!empty($formData['produtos'])) {
		$produtos = [];
		foreach ($formData['produtos'] as $p) {
			$produtos[] = explode('//', $p);
		}
	}

	if($formData['linha'] AND $formData['familia'] ){
		$codigosFamiliaTratados = sanitizeArrayFields($formData['familia']);
		$codigosFamilia = implode(',', $codigosFamiliaTratados);

		$codigosLinhaTratados = sanitizeArrayFields($formData['linha']);
		$codigosLinha = implode(',', $codigosLinhaTratados);

		$condicaoSeLinhaFamilia = " AND (tbl_linha.codigo_linha =  any(string_to_array('{$codigosLinha}', ',')) OR tbl_familia.codigo_familia = any(string_to_array('{$codigosFamilia}', ',')))";
	}else{
		if ($formData['linha']) {
			$codigosLinhaTratados = sanitizeArrayFields($formData['linha']);
			$codigos = implode(',', $codigosLinhaTratados);
			
			$condicaoSeLinha = " AND tbl_linha.codigo_linha =  any(string_to_array('{$codigos}', ','))";
		}
		
		if ($formData['familia']) {
			$codigosFamiliaTratados = sanitizeArrayFields($formData['familia']);
			$codigos = implode(',', $codigosFamiliaTratados);

			$condicaoSeFamilia = " AND tbl_familia.codigo_familia = any(string_to_array('{$codigos}', ','))";
		}
	}

	if ($formData['origem']) {
		$condicaoSeOrigem = " AND tbl_produto.parametros_adicionais::jsonb->>'centro_distribuicao' = '{$formData['origem']}'";
	}

	if ($formData['produtos']) {
		$codigos = [];
		foreach ($formData['produtos'] as $p) {
			$codigos[] = explode('//', $p)[0];
		}
		$codigos = implode(',', $codigos);
		$condicaoSeProduto = "AND trim(tbl_produto.referencia) = any(string_to_array('{$codigos}', ','))";

		unset($condicaoSeLinhaFamilia);
		unset($condicaoSeLinha);
		unset($condicaoSeFamilia);
		unset($condicaoSeOrigem);
	}

	$sql = "SELECT tbl_produto.produto,
				   tbl_produto.referencia,
				   tbl_produto.descricao,
				   tbl_produto.parametros_adicionais::jsonb->>'centro_distribuicao' AS origem,
				   tbl_produto.parametros_adicionais::jsonb->>'consultar_estoque' AS estoque,
				   tbl_familia.descricao AS familia,
				   tbl_linha.nome AS linha
			 FROM tbl_produto
	   INNER JOIN tbl_linha USING(linha)
	   INNER JOIN tbl_familia USING(familia)
			WHERE tbl_produto.fabrica_i = {$login_fabrica}
				  $condicaoSeLinhaFamilia
			      $condicaoSeLinha
			      $condicaoSeFamilia
			      $condicaoSeOrigem
				  $condicaoSeProduto";

	$stmt = $pdo->query($sql);
	$listaDeProdutos = $stmt->fetchAll(PDO::FETCH_ASSOC);

	if( $formData['excel'] == 't' ){
		$linkDownload = createCsvAndReturnLink($listaDeProdutos, $login_admin);
	}
}

// Linhas atentidas
$stmt = $pdo->prepare("SELECT * FROM tbl_linha WHERE fabrica = :fabrica AND ativo IS TRUE");
$stmt->bindValue(':fabrica', $login_fabrica);

$linhas = [];
if ($stmt->execute()) {
	$linhas = $stmt->fetchAll();
}

// Familias atentidas
$stmt = $pdo->prepare("SELECT * FROM tbl_familia WHERE fabrica = :fabrica AND ativo IS TRUE");
$stmt->bindValue(':fabrica', $login_fabrica);

$familias = [];
if ($stmt->execute()) {
	$familias = $stmt->fetchAll();
}

// Origem
$origens = [];
$origens[] = ['origem' => 'mk_nordeste', 'descricao' => 'Nordeste'];
$origens[] = ['origem' => 'mk_sul',      'descricao' => 'Sul'];
?>

<style>
	.formulario-container {
		background-color: #D9E2EF;
		padding-bottom: 10px;
	}

	.formulario-titulo {
		color: white;
		background-color: #596d9b;
		text-align: center;
		margin: 0;
		padding: 2px;
		font-size: 16px;
	}

	.formulario-row {
		display: flex;
		justify-content: space-between;
		padding: 5px;
	}

	.formulario-item {
		width: 100%;
		padding: 3px;
	}

	.formulario-item select,
	.formulario-item input {
		width: 100%;
	}

	.produto-group {
		display: flex;
		justify-content: center
	}

	.produto-item {
		margin-right: 20px;
	}
</style>

<?php if($acaoPesquisar AND !$listaDeProdutos): ?>
	<div class="alert alert-warning"> 
		<h4> Nenhum resultado encontrado. </h4>
	</div>
<?php endif; ?>

<form class="formulario-container" name="formularioPesquisa" method="POST" action="">

	<h5 class="formulario-titulo"> Informações da pesquisa </h5>

	<div class="formulario-row">
		<div class="formulario-item">
			<label>Linha</label>
			<select name="linha[]" id="linha" multiple>

				<?php foreach ($linhas as $linha) : ?>
					<?php foreach ($formData['linha'] as $l) : ?>
						<?php if ($l == $linha['codigo_linha']) : ?>
							<option value="<?= $linha['codigo_linha'] ?>" selected>
								<?= $linha['nome'] ?>
							</option>

							<?php continue 2; ?>
						<?php endif; ?>
					<?php endforeach; ?>

					<option value="<?= $linha['codigo_linha'] ?>">
						<?= $linha['nome'] ?>
					</option>
				<?php endforeach; ?>

			</select>
		</div>

		<div class="formulario-item">
			<label>Família</label>
			<select name="familia[]" id="familia" multiple>

				<?php foreach ($familias as $familia) : ?>
					<?php foreach ($formData['familia'] as $f) : ?>
						<?php if ($f == $familia['codigo_familia']) : ?>
							<option value="<?= $familia['codigo_familia'] ?>" selected>
								<?= $familia['descricao'] ?>
							</option>

							<?php continue 2; ?>
						<?php endif; ?>
					<?php endforeach; ?>

					<option value="<?= $familia['codigo_familia'] ?>">
						<?= $familia['descricao'] ?>
					</option>
				<?php endforeach; ?>

			</select>
		</div>

		<div class="formulario-item">
			<label>Origem</label>
			<select name="origem" id="origem">
				<option value=""> Todas </option>

				<?php foreach ($origens as $origem) : ?>
					<option value="<?= $origem['origem'] ?>" <?= $formData['origem'] == $origem['origem'] ? 'selected' : null ?>>
						<?= $origem['descricao'] ?>
					</option>
				<?php endforeach; ?>

			</select>
		</div>
	</div>

	<hr>

	<div class="produto-group">
		<div class="produto-item">
			<label>Ref. Produto</label>
			<div class="controls">
				<div class="input-append">
					<input type="text" id="produto_referencia" maxlength="20">
					<span class="add-on" onclick="openShadowPesquisa('referencia')">
						<i class="icon-search"></i>
					</span>
				</div>
			</div>
		</div>

		<div class="produto-item">
			<label>Descrição Produto</label>
			<div class="controls">
				<div class="input-append">
					<input type="text" id="produto_descricao" maxlength="20">
					<span class="add-on" onclick="openShadowPesquisa('descricao')">
						<i class="icon-search"></i>
					</span>
				</div>
			</div>
		</div>

	</div>

	<div style="text-align: center"> <strong> ( Selecione o produto ) </strong> </span>

		<div style="padding: 10px">
			<select name="produtos[]" id="produtos" multiple style="width: 100%">
				<?php foreach ($produtos as $produto) : ?>
					<option value="<?= $produto[0] ?>//<?= $produto[1] ?>"> <?= $produto[0] ?> - <?= $produto[1] ?> </option>
				<?php endforeach; ?>
			</select>
		</div>

		<div style="text-align: center">
			<button class="btn btn-danger" type="button" onclick="removerProduto()"> Remover </button>
		</div>

		<hr>

		<?php if( !empty($linkDownload) ): ?>
			<a href="<?=$linkDownload?>" download target="_blank">
				<h5> Baixar Excel </h5> 
			</a>
		<?php endif; ?>

		<div style="margin-bottom: 15px; display: flex; justify-content: center">
			<h5 style="margin-right: 15px;"> Gerar Excel </h5>
			<div style="display: flex; justify-content: center">
				<div style="margin-right: 10px; display: flex; align-items: center;">
					<label for="excel_t" style="margin-right: 3px;"> Sim </label>
					<input type="radio" value="t" name="excel" id="excel_t" <?= $formData['excel'] == 't' ? 'checked' : null ?>>
				</div>
				<div style="display: flex; align-items: center;">
					<label for="excel_f" style="margin-right: 3px;"> Não </label>
					<input type="radio" value="f" name="excel" id="excel_f" <?= $formData['excel'] != 't' ? 'checked' : null ?>>
				</div>
			</div>
		</div>

		<input type="hidden" name="pesquisar" value="true">
		<div style="text-align: center">
			<button class="btn" type="button" onclick="submitPesquisa()"> Pesquisar </button>
		</div>
	</div>
</form>

<?php if ($listaDeProdutos) : ?>
	<table id="tabela" class="table table-striped" style="width: 100%">
		<thead>
			<tr style="background-color: #596d9b; font-weight: bold; padding: 25px; color: white">
				<th>Referência</th>
				<th>Descrição</th>
				<th>Família</th>
				<th>Linha</th>
				<th>Origem</th>
				<th>Nordeste</th>
				<th>Sul</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($listaDeProdutos as $produto) : ?>
				<?php $estoque = json_decode($produto['estoque'], true); ?>
				<tr>
					<td><?= $produto['referencia'] ?></td>
					<td><?= $produto['descricao'] ?></td>
					<td style="text-align:center"><?= $produto['familia'] ?></td>
					<td style="text-align:center"><?= $produto['linha'] ?></td>
					<td style="text-align:center">
						<?= $produto['origem'] == 'mk_nordeste' ? 'Nordeste' : 'Sul' ?>
					</td>
					<td style="text-align:center">
						<input type="checkbox" <?=  in_array('mk_nordeste', $estoque) ? 'checked' : null ?> 
							   onclick="atualizarOrigem(this)"
							   data-origem="mk_nordeste"
							   data-produto="<?=$produto['produto']?>">
					</td>
					<td style="text-align:center">
						<input type="checkbox" <?= in_array('mk_sul', $estoque) ? 'checked' : null ?>
							   onclick="atualizarOrigem(this)"
							   data-origem="mk_sul"
							   data-produto="<?=$produto['produto']?>">
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

<?php endif; ?>

<script>
	$(function() {
		Shadowbox.init();
		
		$('#tabela').DataTable({
            aaSorting: [[0, 'desc']],
            "oLanguage": {
                "sLengthMenu": "Mostrar <select>" +
                                '<option value="10"> 10 </option>' +
                                '<option value="50"> 50 </option>' +
                                '<option value="100"> 100 </option>' +
                                '<option value="150"> 150 </option>' +
                                '<option value="200"> 200 </option>' +
                                '<option value="-1"> Tudo </option>' +
                                '</select> resultados',
                "sSearch": "Procurar:",
                "sInfo": "Mostrando de _START_ até _END_ de um total de _TOTAL_ registros",
                "oPaginate": {
                    "sFirst": "Primeira página",
                    "sLast": "Última página",
                    "sNext": "Próximo",
                    "sPrevious": "Anterior"
                }
            }
        });
		
	});

	$("#linha").multiselect({
		selectedText: "selecionados # de #"
	});
	$("#familia").multiselect({
		selectedText: "selecionados # de #"
	});

	function removerProduto() {
		const selectProduto = document.getElementById('produtos');
		const produtosSelecionados = selectProduto.selectedOptions;

		Array.from(produtosSelecionados).forEach(item => item.remove());
	}

	function openShadowPesquisa(tipoPesquisa) {
		var urlParaPesquisa = '';

		if (tipoPesquisa === 'referencia') {
			const inputReferencia = document.getElementById('produto_referencia');
			urlParaPesquisa = "produto_lupa_new.php?parametro=referencia&valor=" + inputReferencia.value;
		} else if (tipoPesquisa === 'descricao') {
			const inputDescricao = document.getElementById('produto_descricao');
			urlParaPesquisa = "produto_lupa_new.php?parametro=descricao&valor=" + inputDescricao.value;
		}

		Shadowbox.open({
			content: urlParaPesquisa,
			player: "iframe",
			width: 1000,
			height: 500
		});
	}

	function retorna_produto(infoProduto) {
		const option = document.createElement('option');
		option.innerText = infoProduto.referencia + ' - ' + infoProduto.descricao
		option.value = infoProduto.referencia + '//' + infoProduto.descricao;

		const produtos = document.getElementById('produtos');
		produtos.appendChild(option);

		document.getElementById('produto_referencia').value = '';
		document.getElementById('produto_descricao').value = '';
	}

	function submitPesquisa() {
		const selectProduto = document.getElementById('produtos');
		Array.from(selectProduto).forEach(item => item.selected = true);

		document.querySelector('form[name=formularioPesquisa]').submit();
	}

	async function atualizarOrigem(refElement) {
		const formData = {
			atualizarProduto: true,
			produto: refElement.dataset.produto,
			origem: refElement.dataset.origem,
			estado: refElement.checked
		};
		
		try {
			const response = await $.post(window.location.href, formData);
			if( response.error == true ){
				alert(response.message);
				return;
			}
		} catch (error) {
			alert('Não foi possível realizar esta operação. Tente novamente em instantes');
		}
	}
</script>
