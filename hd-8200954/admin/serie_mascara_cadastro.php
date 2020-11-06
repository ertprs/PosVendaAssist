<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios='info_tecnica,gerencia,call_center';
include 'autentica_admin.php';
include 'funcoes.php';

$layout_menu = 'cadastro';
$title = 'CADASTRO DE MÁSCARAS DE NÚMEROS DE SÉRIE';

$filtros = array();

if ($_POST["pesquisar"] || $_POST["gravar"]) {
	try {
		$filtros = array();

		if (!empty($_POST["pesquisa_linha"])) {
			$filtros["linha"] = $_POST["pesquisa_linha"];
		}

		if (!empty($_POST["pesquisa_familia"])) {
			$filtros["familia"] = $_POST["pesquisa_familia"];
		}

		if (!empty($_POST["pesquisa_origem"])) {
			$filtros["origem"] = $_POST["pesquisa_origem"];
		}

		if (!empty($_POST["pesquisa_produto_referencia"]) || !empty($_POST["pesquisa_produto_descricao"])) {
			$filtros["produto_referencia"] = $_POST["pesquisa_produto_referencia"];
			$filtros["produto_descricao"] = $_POST["pesquisa_produto_descricao"];
		}

		if (!count($filtros)) {
			throw new Exception("Selecione pelo menos um filtro para realizar a pesquisa");
		}

		$filtros["listar_produtos"] = $_POST["pesquisa_listar_produtos"];

		$wherePesquisaLinha   = (isset($filtros["linha"])) ? "AND tbl_produto.linha = {$filtros['linha']}" : "";
		$wherePesquisaFamilia = (isset($filtros["familia"])) ? "AND tbl_produto.familia = {$filtros['familia']}" : "";
		$wherePesquisaOrigem  = (isset($filtros["origem"])) ? "AND tbl_produto.origem = '{$filtros['origem']}'" : "";

		if (isset($filtros["produto_referencia"])) {
			$wherePesquisaProduto = "AND (tbl_produto.referencia = '{$filtros['produto_referencia']}' OR tbl_produto.descricao = '{$filtros['produto_descricao']}')";
		}
	} catch (Exception $e) {
		$msg_erro["msg"][] = $e->getMessage();
	}
}

if ($_POST["gravar"]) {
	try {
		pg_query($con, "BEGIN");
		
		if ($usaProdutoGenerico) {
			$whereProdutoGenerico = "AND tbl_produto.produto_principal IS TRUE";
		}

		$delete = "
			DELETE FROM tbl_produto_valida_serie WHERE produto IN (
				SELECT tbl_produto.produto
				FROM tbl_produto
				INNER JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha AND tbl_linha.fabrica = {$login_fabrica} AND tbl_linha.ativo IS TRUE
				INNER JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia AND tbl_familia.fabrica = {$login_fabrica} AND tbl_familia.ativo IS TRUE
				WHERE tbl_produto.fabrica_i = {$login_fabrica}
				AND tbl_produto.ativo IS TRUE
				{$whereProdutoGenerico}
				{$wherePesquisaLinha}
				{$wherePesquisaFamilia}
				{$wherePesquisaOrigem}
				{$wherePesquisaProduto}
			)
		";
		$res = pg_query($con, $delete);

		if (strlen(pg_last_error()) > 0) {
			throw new Exception("Erro ao gravar máscara de número de série");
		}

		$mascaras = $_POST["mascara"];

		if ($usa_versao_produto) {
			$posicoes = $_POST["posicao_versao"];
		}

		if (isset($filtros["produto_referencia"]) || isset($filtros["listar_produtos"])) {
			foreach ($mascaras as $produto => $mascaras_array) {
				foreach ($mascaras_array as $mascara_key => $mascara_value) {
					if ($usa_versao_produto) {
						$posicao = $posicoes[$produto][$mascara_key];
					}

					if (empty($mascara_value)) {
						continue;
					}

					$insert = "INSERT INTO tbl_produto_valida_serie 
							   (fabrica, produto, mascara, posicao_versao)
							   VALUES
							   ({$login_fabrica}, {$produto}, '{$mascara_value}', '{$posicao}')";
					$res = pg_query($con, $insert);

					if (strlen(pg_last_error()) > 0) {
						throw new Exception("Erro ao gravar máscara de número de série");
					}
				}
			}
		} else {
			foreach ($mascaras as $linha => $linha_array) {
				foreach ($linha_array as $familia => $familia_array) {
					foreach ($familia_array as $origem => $mascaras_array) {
						foreach ($mascaras_array as $mascara_key => $mascara_value) {
							if ($usa_versao_produto) {
								$posicao = $posicoes[$linha][$familia][$origem][$mascara_key];
							}

							if (empty($mascara_value)) {
								continue;
							}

							$insert = "INSERT INTO tbl_produto_valida_serie 
									   (fabrica, produto, mascara, posicao_versao)
									   SELECT {$login_fabrica}, tbl_produto.produto, '{$mascara_value}', '{$posicao}'
									   FROM tbl_produto
									   LEFT JOIN tbl_produto_valida_serie ON tbl_produto_valida_serie.produto = tbl_produto.produto AND tbl_produto_valida_serie.fabrica = {$login_fabrica} AND tbl_produto_valida_serie.mascara = '{$mascara_value}'
									   WHERE tbl_produto.fabrica_i = {$login_fabrica}
									   AND tbl_produto.ativo IS TRUE
									   {$whereProdutoGenerico}
									   AND tbl_produto.linha = {$linha}
									   AND tbl_produto.familia = {$familia}
									   AND tbl_produto.origem = '{$origem}'
									   AND tbl_produto_valida_serie.mascara IS NULL";
							$res = pg_query($con, $insert);

							if (strlen(pg_last_error()) > 0) {
								throw new Exception("Erro ao gravar máscara de número de série");
							}
						}
					}
				}
			}
		}

		pg_query($con, "COMMIT");
		$msg_sucesso = "Máscaras gravadas com sucesso";
	} catch (Exception $e) {
		pg_query($con, "ROLLBACK");
		$msg_erro["msg"][] = $e->getMessage();
	}
}

if ($_POST["pesquisar"] || count($filtros) > 0) {
	try {
		if ($usaProdutoGenerico) {
			$whereProdutoGenerico = "AND tbl_produto.produto_principal IS TRUE";
		}
		
		if ($filtros["listar_produtos"] == "t" || isset($wherePesquisaProduto)) {
			$select = "
				SELECT 
					tbl_produto.produto AS produto_id,
					tbl_produto.referencia AS produto_referencia,
					tbl_produto.descricao AS produto_descricao,
					tbl_linha.linha AS linha_id,
					tbl_linha.nome AS linha_nome,
					tbl_familia.familia AS familia_id,
					tbl_familia.descricao AS familia_nome,
					tbl_produto.origem
				FROM tbl_produto
				INNER JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha AND tbl_linha.fabrica = {$login_fabrica} AND tbl_linha.ativo IS TRUE
				INNER JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia AND tbl_familia.fabrica = {$login_fabrica} AND tbl_familia.ativo IS TRUE
				WHERE tbl_produto.fabrica_i = {$login_fabrica}
				AND tbl_produto.ativo IS TRUE
				{$whereProdutoGenerico}
				{$wherePesquisaLinha}
				{$wherePesquisaFamilia}
				{$wherePesquisaOrigem}
				{$wherePesquisaProduto}
			";
		} else {
			$select = "
				SELECT 
					COUNT(tbl_produto.produto) AS qtde_produtos,
					tbl_linha.linha AS linha_id,
					tbl_linha.nome AS linha_nome,
					tbl_familia.familia AS familia_id,
					tbl_familia.descricao AS familia_nome,
					tbl_produto.origem,
					tbl_produto_valida_serie.mascara,
					tbl_produto_valida_serie.posicao_versao
				FROM tbl_produto
				INNER JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha AND tbl_linha.fabrica = {$login_fabrica} AND tbl_linha.ativo IS TRUE
				INNER JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia AND tbl_familia.fabrica = {$login_fabrica} AND tbl_familia.ativo IS TRUE
				LEFT JOIN tbl_produto_valida_serie ON tbl_produto_valida_serie.produto = tbl_produto.produto AND tbl_produto_valida_serie.fabrica = {$login_fabrica}
				WHERE tbl_produto.fabrica_i = {$login_fabrica}
				AND tbl_produto.ativo IS TRUE
				{$whereProdutoGenerico}
				{$wherePesquisaLinha}
				{$wherePesquisaFamilia}
				{$wherePesquisaOrigem}
				GROUP BY
					linha_id,
					linha_nome,
					familia_id,
					familia_nome,
					tbl_produto.origem,
					tbl_produto_valida_serie.mascara,
					tbl_produto_valida_serie.posicao_versao
				ORDER BY linha_nome ASC, familia_nome ASC, tbl_produto.origem ASC
			";
		}

		$resPesquisa = pg_query($con, $select);

		if (!pg_num_rows($resPesquisa)) {
			throw new Exception("Nenhum resultado encontrado");
		}
	} catch (Exception $e) {
		$msg_erro["msg"][] = $e->getMessage();
	}
}

include 'cabecalho_new.php';

$plugins = array(
	"shadowbox",
	"datepicker",
	"select2"
);

include 'plugin_loader.php';

?>

<style>

input.span_mascara {
	width: 180px;
}

input.span_posicao {
	width: 50px;
}

</style>

<script>

$(function() {

	Shadowbox.init();
	$("select").select2();

	$("span[rel=lupa]").click(function () {
		$.lupa($(this));
	});

	$("label[id^=grupo_produto_]").on("click",function(){
		var i       = this.id.replace(/\D/g, "");
		var linha   = $("#linha_"+i).val();
		var familia = $("#familia_"+i).val();
		var origem  = $("#origem_"+i).val();

		Shadowbox.open({
            content: "iframe_serie_mascara.php?linha="+linha+"&familia="+familia+"&origem="+origem,
            player: "iframe", 
            width: 900, 
            height: 500,

            options: {
                enableKeys: false
            }
        });
	});

	$("button.adicionar_nova_mascara_global, button.copiar_para_todos, button.copiar_para_vazio, button.limpar_mascaras").tooltip();

	$("button.adicionar_nova_mascara").on("click", function() {
		var tr = $(this).parents("tr");

		var posicao = parseInt($(tr).find("td.td_input_mascara div").last().data("posicao")) + 1;

		var input_mascara = $(tr).find("td.td_input_mascara input").first().clone();

		$(input_mascara).val("");
		$(tr).find("td.td_input_mascara div").last().after("<div data-posicao='"+posicao+"' style='margin-top: 5px;' ></div>");
		$(tr).find("td.td_input_mascara div").last().html(input_mascara);

		<?php
		if ($usa_versao_produto) {
		?>
			var input_posicao_versao = $(tr).find("td.td_input_posicao_versao input").first().clone();

			$(input_posicao_versao).val("");
			$(tr).find("td.td_input_posicao_versao div").last().after("<div data-posicao='"+posicao+"' style='margin-top: 5px;' ></div>");
			$(tr).find("td.td_input_posicao_versao div").last().html(input_posicao_versao);
		<?php
		}
		?>

		$(tr).find("td.td_button_adicionar_nova_mascara div").last().after("<div class='tac' data-posicao='"+posicao+"' style='margin-top: 5px;' ></div>");
		$(tr).find("td.td_button_adicionar_nova_mascara div").last().html("<button type='button' class='remover_nova_mascara btn btn-danger' ><i class='icon-minus icon-white' ></i></button>");
	});

	$("button.adicionar_nova_mascara_global").on("click", function() {
		$("button.adicionar_nova_mascara").click();
	});

	$(document).on("click", "button.remover_nova_mascara", function() {
		var posicao = $(this).parent().data("posicao");

		var div_input_mascara = $(this).parents("tr").find("td.td_input_mascara").find("div").filter(function() {
			return $(this).data("posicao") == posicao;
		});

		$(div_input_mascara).remove();
		
		<?php
		if ($usa_versao_produto) {
		?>
			var div_input_posicao_versao = $(this).parents("tr").find("td.td_input_posicao_versao").find("div").filter(function() {
				return $(this).data("posicao") == posicao;
			});

			$(div_input_posicao_versao).remove();
		<?php
		}
		?>

		$(this).parent().remove();
	});


	<? if($login_fabrica == 35) : ?>
		$(document).on("keyup", "input.span_mascara", function() {
			$(this).val($(this).val().toUpperCase().replace(/[^NLX]/g, ""));
		});
	<? else: ?>
		$(document).on("keyup", "input.span_mascara", function() {
			$(this).val($(this).val().toUpperCase().replace(/[^NL]/g, ""));
		});
	<? endif; ?>

	$(document).on("keyup", "input.span_posicao", function() {
		$(this).val($(this).val().toUpperCase().replace(/[^0-9-,]/g, ""));
	});

	$("button.copiar_para_todos").on("click", function() {
		var mascara = $("input.input_mascara_global").val();

		if (mascara.length > 0) {
			$("table.table-mascaras input.span_mascara").val(mascara);
		}

		<?php
		if ($usa_versao_produto) {
		?>
			var posicao_versao = $("input.input_posicao_versao_global").val();

			if (posicao_versao.length > 0) {
				$("table.table-mascaras input.span_posicao").val(posicao_versao);
			}
		<?php
		}
		?>
	});

	$("button.copiar_para_vazio").on("click", function() {
		var mascara = $("input.input_mascara_global").val();

		if (mascara.length > 0) {
			$("table.table-mascaras input.span_mascara").filter(function() { return $(this).val().length == 0; }).val(mascara);
		}

		<?php
		if ($usa_versao_produto) {
		?>
			var posicao_versao = $("input.input_posicao_versao_global").val();

			if (posicao_versao.length > 0) {
				$("table.table-mascaras input.span_posicao").filter(function() { return $(this).val().length == 0; }).val(posicao_versao);
			}
		<?php
		}
		?>
	});

	$("button.limpar_mascaras").on("click", function() {
		$("table.table-mascaras input.span_mascara").filter(function() { return $(this).val().length > 0; }).val("");

		<?php
		if ($usa_versao_produto) {
		?>
			$("table.table-mascaras input.span_posicao").filter(function() { return $(this).val().length > 0; }).val("");
		<?php
		}
		?>
	});
});

function retorna_produto(data) {
	$("#pesquisa_produto_referencia").val(data.referencia);
	$("#pesquisa_produto_descricao").val(data.descricao);
}

</script>

<?php
if (count($msg_erro["msg"]) > 0) {
?>
	<br />
	<div class="alert alert-error"><h4><?=implode("<br />", $msg_erro["msg"])?></h4></div>
<?php
}

if (!empty($msg_sucesso)) {
?>
	<br />
	<div class="alert alert-success"><h4><?=$msg_sucesso?></h4></div>
<?php
}
?>

<form method="POST" class="form-search form-inline" >
	<div class="tc_formulario" >
		<div class="titulo_tabela">Parâmetros de Pesquisa</div>

		<br />

		<div class='row-fluid'>
			<div class="span1"></div>

			<div class="span4">
				<div class='control-group'>
					<label class="control-label" for="pesquisa_linha">Linha</label>
					<div class="controls controls-row">
						<div class="span12">
							<select id="pesquisa_linha" name="pesquisa_linha" class="span12" >
								<option value="" >Selecione</option>
								<?php
								$sqlLinha = "SELECT linha, nome FROM tbl_linha WHERE fabrica = {$login_fabrica} AND ativo IS TRUE ORDER BY nome ASC";
								$resLinha = pg_query($con, $sqlLinha);

								if (pg_num_rows($resLinha) > 0) {
									while ($linha = pg_fetch_object($resLinha)) {
										$selected = (getValue("pesquisa_linha") == $linha->linha) ? "selected" : "";

										echo "<option value='{$linha->linha}' {$selected} >{$linha->nome}</option>";
									}
								}
								?>
							</select>
						</div>
					</div>
				</div>
			</div>

			<div class="span4">
				<div class='control-group'>
					<label class="control-label" for="pesquisa_familia">Família</label>
					<div class="controls controls-row">
						<div class="span12">
							<select id="pesquisa_familia" name="pesquisa_familia" class="span12" >
								<option value="" >Selecione</option>
								<?php
								$sqlFamilia = "SELECT familia, descricao FROM tbl_familia WHERE fabrica = {$login_fabrica} AND ativo IS TRUE ORDER BY descricao ASC";
								$resFamilia = pg_query($con, $sqlFamilia);

								if (pg_num_rows($resFamilia) > 0) {
									while ($familia = pg_fetch_object($resFamilia)) {
										$selected = (getValue("pesquisa_familia") == $familia->familia) ? "selected" : "";

										echo "<option value='{$familia->familia}' {$selected} >{$familia->descricao}</option>";
									}
								}
								?>
							</select>
						</div>
					</div>
				</div>
			</div>

			<div class="span2">
				<div class='control-group'>
					<label class="control-label" for="pesquisa_origem">Origem</label>
					<div class="controls controls-row">
						<div class="span12">
							<select id="pesquisa_origem" name="pesquisa_origem" class="span12" >
								<option value="" >Selecione</option>
								<?php
								$sqlOrigem = "SELECT DISTINCT origem FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND ativo IS TRUE ORDER BY origem ASC";
								$resOrigem = pg_query($con, $sqlOrigem);

								if (pg_num_rows($resOrigem) > 0) {
									while ($origem = pg_fetch_object($resOrigem)) {
										$selected = (getValue("pesquisa_origem") == $origem->origem) ? "selected" : "";

										echo "<option value='{$origem->origem}' {$selected} >{$origem->origem}</option>";
									}
								}
								?>
							</select>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class="row-fluid">
			<div class="span1"></div>

			<div class="span4">
				<div class="control-group">
					<label class="control-label" for="pesquisa_produto_referencia">Referência Produto</label>
					<div class="controls controls-row">
						<div class="span11 input-append">
							<input type="text" id="pesquisa_produto_referencia" name="pesquisa_produto_referencia" class="span12" value="<?=getValue('pesquisa_produto_referencia')?>" />
							<span class="add-on" rel="lupa" ><i class="icon-search"></i></span>
							<input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" disabled />
						</div>
					</div>
				</div>
			</div>

			<div class="span4">
				<div class="control-group">
					<label class="control-label" for="pesquisa_produto_descricao">Descrição Produto</label>
					<div class="controls controls-row">
						<div class="span11 input-append">
							<input type="text" id="pesquisa_produto_descricao" name="pesquisa_produto_descricao" class="span12" value="<?=getValue('pesquisa_produto_descricao')?>" />
							<span class="add-on" rel="lupa" ><i class="icon-search" ></i></span>
							<input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" disabled />
						</div>
					</div>
				</div>
			</div>

			<div class="span2" >
				<label class="control-label" >&nbsp;</label>
					<div class="controls controls-row">
						<div class="span12">
							<label class="checkbox label label-info" style="line-height: 23px;" >
								<input type="checkbox" id="pesquisa_listar_produtos" name="pesquisa_listar_produtos" value="t" <?=(getValue("pesquisa_listar_produtos") == "t") ? "checked" : ""?> /> Listar Produtos?
							</label>
						</div>
					</div>
			</div>
		</div>

		<br />

		<button type="submit" name="pesquisar" class="btn" value="pesquisar" >Pesquisar</button>

		<?php
		if (count($filtros) > 0) {
		?>
			<button type="button" class="btn btn-warning" onclick="window.location = '<?=$_SERVER["PHP_SELF"]?>';" >Limpar</button>
		<?php
		}
		?>

		<br />
		<br />
	</div>

	<?php
	if (count($filtros) > 0 && pg_num_rows($resPesquisa) > 0) {
	?>
		<br />

			<div class="row-fluid">
				<div class="offset2 span1">
					<div style="background-color: lightpink; color: darkred; text-align: center; font-weight: bold; padding-top: 5px; padding-bottom: 5px;" class="legenda">L</div>
					<div style="background-color: lightblue; color: darkblue; text-align: center; font-weight: bold; padding-top: 5px; padding-bottom: 5px;" class="legenda">N</div>

					<?php if($login_fabrica == 35) : ?>
						<div style="background-color: lightgreen; color: darkgreen; text-align: center; font-weight: bold; padding-top: 5px; padding-bottom: 5px;" class="legenda">X</div>
					<?php endif; ?>
					
				</div>
				<div class="span9">
					<h5 class='success'>Letras</h5>
					<h5>Números</h5>
					<?php if($login_fabrica == 35) : ?>
						<h5>Letras ou Números</h5>
					<?php endif; ?>
				</div>
			</div>
		<?php
		if ($usa_versao_produto) {
		?>
			<div class="row-fluid">
				<div class='alert alert-info' style='text-align:left'>
					<h4> Definir posição:</h4>
					<ul>
						<li><b>número:</b> <span class="label label-info" style="margin-left: 10px; width: 25px; text-align: center;" >5</span> &mdash; define essa única posição como a versão do produto.</li>
						<li><b>números:</b> <span class="label label-info" style="margin-left: 3px; width: 25px; text-align: center;" >3,5,7</span> &mdash; define a concatenação dessas posições como a versão do produto.</li>
						<li><b>intervalo:</b> <span class="label label-info" style="width: 25px; text-align: center;" >2-4</span> &mdash; define esse intervalo (da posição inicial até a final, <strong>inclusive</strong>) como a versão do produto.</li>
					</ul>
				</div>
			</div>
		<?php
		}
		?>

		<table class="table table-striped table-bordered" >
			<tbody>
				<tr>
					<td style="vertical-align: top;" nowrap >
						Máscara de série para todos os produtos listados:
					</td>
					<td style="vertical-align: top;">
						<input type="text" class="span_mascara input_mascara_global" placeholder="Máscara" />
					</td>
					<?php
					if ($usa_versao_produto) {
					?>
						<td style="vertical-align: top;" >
							<input type="text" class="span_posicao input_posicao_versao_global" placeholder="Posição" />
						</td>
					<?php
					}
					?>
					<td style="vertical-align: top;" >
						<label>
							<button type="button" class="btn btn-warning copiar_para_todos span3" data-toggle="tooltip" title="Copiar máscara para todos os produtos, irá sobrescrever as existentes" ><i class="icon-edit icon-white"></i> Copiar para todos</button>
						</label>
						<label>
							<button type="button" class="btn btn-info copiar_para_vazio span3" data-toggle="tooltip" title="Copiar máscara para todos os produtos, irá copiar somente para os campos em branco " ><i class="icon-pencil icon-white"></i> Copiar para vazio</button>
						</label>
						<label>
							<button type="button" class="btn btn-danger limpar_mascaras span3" data-toggle="tooltip" title="Limpar as máscaras de todos os produtos" ><i class="icon-remove icon-white"></i> Limpar Máscaras</button>
						</label>
					</td>
				</tr>
			</tbody>
		</table>

		<br />

		<p class="tac" >
			<button type="submit" name="gravar" class="btn btn-success" value="gravar" data-loading-text="Gravando..." onclick="$(this).button('loading');" >Gravar</button>
		</p>

		<br />

		<table class="table table-striped table-bordered table-mascaras" style="margin: 0 auto;" >
			<thead>
				<tr class="titulo_coluna" >
					<?php
					if (isset($filtros["produto_referencia"]) || isset($filtros["listar_produtos"])) {
					?>
						<th>Produto</th>
					<?php
					} else {
					?>
						<th>Qtde. Produtos</th>
					<?php
					}
					?>
					<th>Linha</th>
					<th>Família</th>
					<th>Origem</th>
					<th>Máscara</th>
					<?php
					if ($usa_versao_produto) {
					?>
						<th>Posição Versão</th>
					<?php
					}
					?>
					<th>
						<button type="button" class="adicionar_nova_mascara_global btn btn-primary" data-toggle="tooltip" title="Adicionar nova máscara para todos os produtos" ><i class="icon-plus icon-white" ></i></button>
					</th>
				</tr>
			</thead>
			<tbody>
				<?php
				if (!isset($filtros["produto_referencia"]) && !isset($filtros["listar_produtos"])) {
					$count_linha = 0;
				}
				while ($produto = pg_fetch_object($resPesquisa)) {
					if (isset($filtros["produto_referencia"]) || isset($filtros["listar_produtos"])) {
						$input_mascara_nome        = "mascara[{$produto->produto_id}][]";
						$input_posicao_versao_nome = "posicao_versao[{$produto->produto_id}][]";
					} else {
						$input_mascara_nome        = "mascara[{$produto->linha_id}][{$produto->familia_id}][{$produto->origem}][]";
						$input_posicao_versao_nome = "posicao_versao[{$produto->linha_id}][{$produto->familia_id}][{$produto->origem}][]";
					}
					?>
					<tr class="tr_produtos" >
						<?php
						if (isset($filtros["produto_referencia"]) || isset($filtros["listar_produtos"])) {
						?>
							<td><a target="_blank" href="produto_cadastro.php?produto=<?=$produto->produto_id?>"><?=$produto->produto_referencia?> - <?=$produto->produto_descricao?></a></td>
						<?php
						} else {
						?>
							<td class="tac" >
								<label class="label" id="grupo_produto_<?=$count_linha?>"><?=$produto->qtde_produtos?></label>
								<input type="hidden" id="linha_<?=$count_linha?>" value="<?=$produto->linha_id?>"/>
								<input type="hidden" id="familia_<?=$count_linha?>" value="<?=$produto->familia_id?>"/>
								<input type="hidden" id="origem_<?=$count_linha?>" value="<?=$produto->origem?>"/>
							</td>
						<?php
							$count_linha++;
						}
						?>
						<td><?=$produto->linha_nome?></td>
						<td><?=$produto->familia_nome?></td>
						<td class="tac" ><?=$produto->origem?></td>
						<?php
						if (count($msg_erro["msg"]) > 0) {
							if (isset($filtros["produto_referencia"]) || isset($filtros["listar_produtos"])) {
								$mascaras = $_POST["mascara"][$produto->produto_id];

								if ($usa_versao_produto) {
									$posicoes = $_POST["posicao_versao"][$produto->produto_id];
								}
							} else {
								$mascaras = $_POST["mascara"][$produto->linha_id][$produto->familia_id][$produto->origem];

								if ($usa_versao_produto) {
									$posicoes = $_POST["posicao_versao"][$produto->linha_id][$produto->familia_id][$produto->origem];
								}
							}
							?>
							<td class="td_input_mascara" >
								<?php
								$k = 1;

								foreach ($mascaras as $mascara) {
								?>
									<div style='margin-top: 5px;' data-posicao="<?=$k?>"  >
										<input type="text" name="<?=$input_mascara_nome?>" class="span_mascara" value="<?=$mascara?>" />
									</div>
								<?php
									$k++;
								}
								?>
							</td>
							<?php
							if ($usa_versao_produto) {
							?>
								<td class="td_input_posicao_versao" >
									<?php
									$k = 1;

									foreach ($posicoes as $posicao) {
									?>
										<div style='margin-top: 5px;' data-posicao="<?=$k?>"  >
											<input type="text" name="<?=$input_posicao_versao_nome?>" class="span_posicao" value="<?=$posicao?>" />
										</div>
									<?php
										$k++;
									}
									?>
								</td>
							<?php
							}
							?>
							<td class="td_button_adicionar_nova_mascara" >
								<?php
								for ($i = 1; $i < $k ; $i++) {
									if ($i == 1) {
									?>
										<div class="tac" style='margin-top: 5px;' data-posicao="<?=$i?>" >
											<button type="button" class="adicionar_nova_mascara btn btn-primary" ><i class="icon-plus icon-white" ></i></button>
										</div>
									<?php
									} else {
									?>
										<div class="tac" style='margin-top: 5px;' data-posicao="<?=$i?>" >
											<button type="button" class="remover_nova_mascara btn btn-danger" ><i class="icon-minus icon-white" ></i></button>
										</div>
									<?php
									}
								}
								?>
							</td>
						<?php
						} else {
							if ($filtros["listar_produtos"] == "t" || isset($wherePesquisaProduto)) {
								if ($usaProdutoGenerico) {
									$whereProdutoGenerico = "AND tbl_produto.produto_principal IS TRUE";
								}
								
								$select = "
									SELECT
										tbl_produto_valida_serie.mascara,
										tbl_produto_valida_serie.posicao_versao
									FROM tbl_produto
									INNER JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha AND tbl_linha.fabrica = {$login_fabrica} AND tbl_linha.ativo IS TRUE
									INNER JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia AND tbl_familia.fabrica = {$login_fabrica} AND tbl_familia.ativo IS TRUE
									LEFT JOIN tbl_produto_valida_serie ON tbl_produto_valida_serie.produto = tbl_produto.produto AND tbl_produto_valida_serie.fabrica = {$login_fabrica}
									WHERE tbl_produto.fabrica_i = {$login_fabrica}
									AND tbl_produto.ativo IS TRUE
									AND tbl_produto.produto = {$produto->produto_id}
									{$whereProdutoGenerico}
								";
								$res = pg_query($con, $select);

								if (pg_num_rows($res) > 0) {
									$mascaras = pg_fetch_all($res);
									?>
									<td class="td_input_mascara" >
										<?php
										$k = 1;

										foreach ($mascaras as $row => $row_data) {
										?>
											<div style='margin-top: 5px;' data-posicao="<?=$k?>"  >
												<input type="text" name="<?=$input_mascara_nome?>" class="span_mascara" value="<?=$row_data['mascara']?>" />
											</div>
										<?php
											$k++;
										}
										?>
									</td>
									<?php
									if ($usa_versao_produto) {
									?>
										<td class="td_input_posicao_versao" >
											<?php
											$k = 1;

											foreach ($mascaras as $row => $row_data) {
											?>
												<div style='margin-top: 5px;' data-posicao="<?=$k?>"  >
													<input type="text" name="<?=$input_posicao_versao_nome?>" class="span_posicao" value="<?=$row_data['posicao_versao']?>" />
												</div>
											<?php
												$k++;
											}
											?>
										</td>
									<?php
									}
									?>
									<td class="td_button_adicionar_nova_mascara" >
										<?php
										for ($i = 1; $i < $k ; $i++) {
											if ($i == 1) {
											?>
												<div class="tac" style='margin-top: 5px;' data-posicao="<?=$i?>" >
													<button type="button" class="adicionar_nova_mascara btn btn-primary" ><i class="icon-plus icon-white" ></i></button>
												</div>
											<?php
											} else {
											?>
												<div class="tac" style='margin-top: 5px;' data-posicao="<?=$i?>" >
													<button type="button" class="remover_nova_mascara btn btn-danger" ><i class="icon-minus icon-white" ></i></button>
												</div>
											<?php
											}
										}
										?>
									</td>
								<?php
								} else {
								?>
									<td class="td_input_mascara" >
										<div style='margin-top: 5px;' data-posicao="1"  >
											<input type="text" name="<?=$input_mascara_nome?>" class="span_mascara" />
										</div>
									</td>
									<?php
									if ($usa_versao_produto) {
									?>
										<td class="td_input_posicao_versao" >
											<div style='margin-top: 5px;' data-posicao="1" >
												<input type="text" name="<?=$input_posicao_versao_nome?>" class="span_posicao" />
											</div>
										</td>
									<?php
									}
									?>
									<td class="td_button_adicionar_nova_mascara" >
										<div class="tac" style='margin-top: 5px;' data-posicao="1" >
											<button type="button" class="adicionar_nova_mascara btn btn-primary" ><i class="icon-plus icon-white" ></i></button>
										</div>
									</td>
								<?php
								}
							} else {
							?>
								<td class="td_input_mascara" >
									<div style='margin-top: 5px;' data-posicao="1"  >
										<input type="text" name="<?=$input_mascara_nome?>" class="span_mascara" value="<?=$produto->mascara?>" />
									</div>
								</td>
								<?php
								if ($usa_versao_produto) {
								?>
									<td class="td_input_posicao_versao" >
										<div style='margin-top: 5px;' data-posicao="1" >
											<input type="text" name="<?=$input_posicao_versao_nome?>" class="span_posicao" value="<?=$produto->posicao_versao?>" />
										</div>
									</td>
								<?php
								}
								?>
								<td class="td_button_adicionar_nova_mascara" >
									<div class="tac" style='margin-top: 5px;' data-posicao="1" >
										<button type="button" class="adicionar_nova_mascara btn btn-primary" ><i class="icon-plus icon-white" ></i></button>
									</div>
								</td>
							<?php
							}
						}
						?>
					</tr>
				<?php
				}
				?>
			</tbody>
		</table>

		<br />

		<p class="tac" >
			<button type="submit" name="gravar" class="btn btn-success" value="gravar" data-loading-text="Gravando..." onclick="$(this).button('loading');" >Gravar</button>
		</p>
	<?php
	}
	?>
</form>

<?php

include 'rodape.php';
