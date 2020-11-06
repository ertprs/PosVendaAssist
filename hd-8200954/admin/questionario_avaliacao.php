<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="cadastro, gerencia";
include 'autentica_admin.php';
include 'funcoes.php';

$pesquisa = $_REQUEST["pesquisa"];
$action = (empty($pesquisa) || !empty($_GET["copy"])) ? "gravar" : "alterar";

try {

	$categoriaPesquisa = $_POST["categoria_pesquisa"];

	$easyBuilderMirror = new \Mirrors\EasyBuilderMirror($login_fabrica, $categoriaPesquisa, $login_admin);

	if (isset($_POST["btn_acao"])) {

		$jsonForm = $_POST['easybuilder'];

		if ($action == "gravar") {

			$retorno = $easyBuilderMirror->post($_POST);

		} else {

			$retorno = $easyBuilderMirror->put($_POST);

		}

		$msg_success["msg"][] = "O questionário de avaliação foi ".($action == "gravar" ? "gravado" : "alterado")." com sucesso!";

		$pesquisa = $retorno["id_pesquisa"];
		$action   = "alterar";

	}

	if (empty($_GET['delete'])) {

		if (!empty($pesquisa)) {

			$dadosPesquisa = $easyBuilderMirror->get($pesquisa);

			$categoria = getCategoriaPesquisa($pesquisa);

			$jsonForm = $dadosPesquisa["campos"][0]["formulario"];
			$ativo    = $dadosPesquisa["campos"][0]["ativo"];

			if ($_GET["copy"]) {
				unset($pesquisa);
			}

		}

	} else {

		$easyBuilderMirror->delete($pesquisa);

		$msg_success["msg"][] = "Pesquisa excluída com sucesso";

		unset($pesquisa);
		$action = "gravar";

	}

} catch(\Exception $e){

    $msg_erro["msg"][] = utf8_decode($e->getMessage());

}

if ($_GET["copy"]) {
	$msg_success["msg"][] = "Pesquisa copiada";
}

$layout_menu = "gerencia";
$title = "Questionário de Avaliação do Posto Autorizado";
include 'cabecalho_new.php';

$plugins = array(
   "bootstrap3",
   "telecontrol-easy-form-builder",
   "dataTableAjax"
);

include "plugin_loader.php";

$jsonForm = str_replace("\\n", "\\\\n", $jsonForm);
$jsonForm = str_replace("\\t", "\\\\t", $jsonForm);
$jsonForm = str_replace("\\r", "\\\\r", $jsonForm);

?>
<script>

	var jsonForm = '<?= $jsonForm ?>';
	var fabrica  = <?= $login_fabrica ?>;
	var settings = {};

	$(function(){

		if (fabrica == 42) {

			settings = {
				acaoInserirRadio: gerarInputRadioMakita
			};

		}

		$("#questionario_pesquisa").easyFormBuilder(settings, jsonForm);

		$("#ativo").click(function(){

			if ($(this).is(":checked")) {

				if (!confirm("Atenção:\n\n Ao ativar esta pesquisa, a pesquisa atual será inativada pois só é possível manter uma pesquisa de cada tipo ativa!\n\n Confirma a alteração?")) {
					$(this).prop("checked", false);
				}

			}

		});

		$("#categoria_pesquisa").change(function(){

			let informativo = $(this).find("option:selected").data("informativo");

			if (informativo != "") {

				$(".texto-informativo > .alert-info").html("<h5>"+informativo+"</h5>");
				$(".texto-informativo").show();

			} else {

				$(".texto-informativo > .alert-info").html("");
				$(".texto-informativo").hide();				

			}

		});

		$("#categoria_pesquisa").change();

		var tableElement = $('#listaPesquisas');
		var dataTableColumns = [];

		$(tableElement).find("thead th").each(function(){

			let column = $(this).data("column") === undefined ? "unorderable" : $(this).data("column");

      		dataTableColumns.push({
      			data: column.replace(".","-")
      		});

      	});

		$(tableElement).DataTable({
	      'processing': true,
	      'serverSide': true,
	      'serverMethod': 'POST',
	      'ajax': {
	          'url':'ajax/paginate_pesquisa.php'
	      },
	      'columns': dataTableColumns,
	      "language": traducaoPtBr
	    });

		$('[data-toggle="popover"]').popover().click();

		setTimeout(function(){
			$('.popover').hide("slow");
		}, 8000);

	});

	var gerarInputRadioMakita = function(index, indexRadio, formInput) {

		var inputGroupInput = $("<div>", {
			class: "form-group",
			css: {
				width: "70%"
			}
		});

		var inputGroupQuestoes = $("<div>", {
			class: "form-group",
			css: {
				width: "100%"
			}
		});

		if (jsonForm != "") {
			var jsonFormMakita = JSON.parse(jsonForm);
			if (jsonFormMakita.formulario[index] != undefined) {
				if (jsonFormMakita.formulario[index].perguntas[indexRadio] != undefined) {
					var parametros_adicionais = jsonFormMakita.formulario[index].perguntas[indexRadio].parametrosAdicionais;
				}
			}
		}

		let indexAdicionais = 0;
		$(formInput).find(".desc-marcacao").each(function(){

			if ($(this).val() != "") {

				inputGroupQuestoes.append($("<label>", {
					text: $(this).val(),
					css: {
						"font-size": "14px"
					}
				}));

				inputGroupQuestoes.append($("<input>", {
					class: "form-control",
					css: {
						"margin-left": "5px",
						"margin-right": "5px"
					},
					type: "radio",
					disabled: true
				}));

				inputGroupQuestoes.append($("<input>", {
					type: "hidden",
					name: "easybuilder[formulario]["+index+"][perguntas]["+indexRadio+"][radio][options][]",
					value: $(this).val()
				}));

				let valorPontos = 0;
				if (jsonFormMakita != undefined && parametros_adicionais != undefined) {
					valorPontos = parametros_adicionais.pontos[indexAdicionais];
				}

				inputGroupQuestoes.append($("<input>", {
					type: "number",
					min: 0,
					class: "valor-pontos",
					name: "easybuilder[formulario]["+index+"][perguntas]["+indexRadio+"][parametros_adicionais][pontos][]",
					value: valorPontos,
					css: {
						width: "60px",
						"margin-right": "20px"
					},
				}));

				indexAdicionais++;

			}
			
		});

		inputGroupInput.append(inputGroupQuestoes);

		return inputGroupInput;

	};

</script>
<style>
	#listaPesquisas th {
		color: white;
		background-color: #596d9b;
	}

	#listaPesquisas td {
		text-align: center;
	}

	#info_tela {
		border-radius: 90px;
		width: 30px;
		height: 30px;
		margin-bottom: 10px;
	}
	.popover-title {
		color: white;
		background-color: #596d9b;
		font-weight: bolder;
	}
</style>
<?php
if (count($msg_erro["msg"]) > 0) { ?>
    <div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
} else if (count($msg_success["msg"]) > 0) { ?>
	<div class="alert alert-success">
		<h4><?=implode("<br />", $msg_success["msg"])?></h4>
    </div>
<?php
} ?>
<div class="row">
	<button style="background-color: white;border: none;box-shadow: none;" class="btn" type="button" id="info_tela" data-toggle="popover" title="Integração Disponível!" data-content="Esta tela possuí uma API e pode ser integrada com outros sistemas. Entre em contato com nosso suporte para liberação do acesso.">
		<span class="glyphicon glyphicon-info-sign"></span>
	</button>
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>

<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario'>

	<input type="hidden" name="pesquisa" value="<?= $pesquisa ?>" />

	<div class='titulo_tabela'>Questionário de Avaliação</div>

	<div id="questionario_pesquisa"></div>
	<hr />
	<div class="row-fluid texto-informativo" hidden>
		<div class="alert alert-info col-sm-10 col-sm-offset-1">
			<h4></h4>
		</div>
	</div>
	<div class="row-fluid">
		<div class="col-md-3 col-sm-3"></div>	
  		<div class="col-md-4 col-sm-4">
	        <div class="form-group">
	          	<label>Tipo Pesquisa <span class="obrigatorio">*</span></label><br />
	          	<div class="input-group">
		  			<select name="categoria_pesquisa" id="categoria_pesquisa" class="form-control">
						<?php
						foreach ($easyBuilderMirror->_tiposPesquisaFabrica[$login_fabrica] as $codigo => $dados) { 

							$selected = ($categoriaPesquisa == $codigo) ? "selected" : "";

						?>
							<option value="<?= $codigo ?>" data-informativo="<?= $dados["informativo"] ?>" <?= $selected ?>>
								<?= $dados["descricao"] ?>
							</option>

						<?php
						} ?>
					</select>
	          	</div>
	        </div>
  		</div>
  		<div class="col-md-4 col-sm-4">
	        <div class="form-group">
	          	<label for="ativo" style="margin-bottom: 0 !important;">Ativo</label> &nbsp; 
			  	<input type="checkbox" name="ativo" class="form-control" id="ativo" value="t" <?= ($ativo) ? "checked" : "" ?> />
	        </div>
  		</div>	
      	<div class="col-md-1 col-sm-1"></div>
    </div>
    <br /><br />
	<input type="submit" class='btn btn-default' name="btn_acao" value="<?= ($action == "alterar") ? "Alterar Pesquisa" : "Gravar Pesquisa" ?>" />
	<br/><br />
</form>

<table id='listaPesquisas' class='display dataTable table'>
  <thead>
    <tr>
      <th data-column="tbl_pesquisa.descricao">Título</th>
      <th data-column="tbl_admin.nome_completo">Admin</th>
      <th data-column="tbl_pesquisa.data_input">Data Criação</th>
      <th data-column="tbl_pesquisa.ativo">Ativo</th>
      <th>Ações</th>
    </tr>
  </thead>
</table>
<?php
include "rodape.php";
?>