<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="cadastro, gerencia";
include 'autentica_admin.php';
include 'funcoes.php';

$easyBuilderMirror = new \Mirrors\EasyBuilderMirror($login_fabrica, $_POST['categoria_pesquisa'], $login_admin);

if (isset($_POST['gerar_excel'])) {

	$parametrosRequest = [
		"dataInicial" 		=> $_POST["data_inicial"],
		"dataFinal"   		=> $_POST["data_final"],
		"postoId"     		=> $_POST["posto"],
		"categoriaPesquisa" => $_POST["categoria_pesquisa"],
		"gerarExcel"  		=> true,
		"pesquisaId"        => $_POST["pesquisa"]
	];

	if (in_array($login_fabrica, [1])) {

		$parametrosRequest["tipoCsv"] = $_POST['tipo_csv'];

	}

	$retorno = $easyBuilderMirror->getRespostas([], $parametrosRequest);

	$nomeArquivo = "relatorio_pesquisa_satisfacao".date("Ymdhis").".csv";

	$caminho   = "/tmp/".$nomeArquivo;
	$linkTdocs = $retorno["link"];

	file_put_contents($caminho, file_get_contents($linkTdocs));

	system("mv /tmp/{$nomeArquivo} xls/{$nomeArquivo}");

	exit("xls/{$nomeArquivo}");

}

$pesquisa = $_REQUEST["pesquisa"];
$action   = (empty($pesquisa) || !empty($_GET["copy"])) ? "gravar" : "alterar";

try {

	$urlPaginate = "?";
	if (isset($_POST["btn_acao"])) {

		$_POST["data_inicial"] = formata_data($_POST["data_inicial"]);
		$_POST['data_final']   = formata_data($_POST["data_final"]);

		if ((empty($_POST["data_inicial"]) || empty($_POST['data_final'])) && empty($_POST["posto"])) {
			throw new \Exception("Informe datas válidas", 400);
		}

		if (empty($_POST['pesquisa'])) {
			throw new \Exception("Selecione uma pesquisa", 400);
		}

		foreach ($_POST as $key => $val) {
			$urlPaginate .= "{$key}={$val}&";
		}

	}

} catch(\Exception $e){

    $msg_erro["msg"][] = $e->getMessage();

}

if ($_GET["copy"]) {
	$msg_success["msg"][] = "Pesquisa copiada";
}

$layout_menu = "gerencia";
$title = "Questionário de Avaliação do Posto Autorizado";
include 'cabecalho_new.php';

$plugins = array(
   "bootstrap3",
   "shadowbox",
   "telecontrol-easy-form-builder",
   "dataTableAjax",
   "datepicker",
   "mask",
   "autocomplete"
);

include "plugin_loader.php";

?>
<script>

	var jsonForm = '<?= $jsonForm ?>';

	var url_pesquisa = "formulario_telecontrol_easyfb.php";

	<?php
	if ($login_fabrica == 42) { ?>

		url_pesquisa = "avaliacao_tecnica_makita.php";
		
	<?php
	}
	?>

	$(function(){

		$.datepickerLoad(Array("data_final", "data_inicial"));

		$.autocompleteLoad(Array("produto", "peca", "posto"));

		Shadowbox.init();

		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});

		Shadowbox.init();

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
	          'url':'ajax/paginate_resposta.php<?= $urlPaginate ?>'
	      },
	      'columns': dataTableColumns,
	      "language": traducaoPtBr
	    });

		$('[data-toggle="popover"]').popover().click();

		setTimeout(function(){
			$('.popover').hide("slow");
		}, 8000);

		$(document).on("click", ".btn-visualiza-resposta", function(){

			let resposta = $(this).data("resposta");
			let pesquisa = $(this).data("pesquisa");
			let posto    = $(this).data("posto");

			Shadowbox.open({
                content:    url_pesquisa+"?resposta="+resposta+"&pesquisa="+pesquisa+"&posto="+posto,
                player: 	"iframe",
                title:      "Visualizar Resposta",
                width:  1500,
                height: 800
            });

		});

		$(document).on("click", ".btn-altera-resposta", function(){

			let resposta = $(this).data("resposta");
			let pesquisa = $(this).data("pesquisa");
			let posto    = $(this).data("posto");

			Shadowbox.open({
                content:    url_pesquisa+"?resposta="+resposta+"&pesquisa="+pesquisa+"&posto="+posto+"&acao=alterar",
                player: 	"iframe",
                title:      "Avaliação Técnica Makita",
                width:  1500,
                height: 800
            });

		});

		$(document).on("click", ".btn-remove-resposta", function(){

			let that = $(this);

			let resposta = $(that).data("resposta");
			let pesquisa = $(that).data("pesquisa");
			let posto    = $(that).data("posto");

			$.ajax({
				url: url_pesquisa,
				type: "POST",
				dataType: "JSON",
				data: {resposta: resposta, pesquisa: pesquisa, posto: posto, excluirResposta: true},
				beforeSend: function () {
					loading("show");
				},
				success: function (data) {

					if (data.success) {
						alert("Resposta excluída com sucesso");
						$(that).closest("tr").remove();
					} else {
						alert(data.msg);
					}

					loading("hide");

				}
			});
		});

		$("#listaPesquisas_paginate").click(function () {
			setTimeout(function(){
				$(".btn-visualiza-resposta").parent('td').attr('nowrap','nowrap');
			}, 8000);
		})

		$("#gerar_excel_respostas").click(function(){
			if (ajaxAction()) {

				if ($(this).hasClass("gerar_excel")) {
					var json = $.parseJSON($(this).find(".jsonPOST").val());
				} else {
					var json = $.parseJSON($("#jsonPOST").val());
				}

				
				json["gerar_excel"] = true;

    			$.ajax({
    				url: "<?=$_SERVER['PHP_SELF']?>",
    				type: "POST",
    				data: json,
    				beforeSend: function () {
    					loading("show");
    				},
    				complete: function (data) {
    					window.open(data.responseText, "_blank");

    					loading("hide");
    				}
    			});
			}
		});

	});

	function retorna_posto(retorno){
		$("#posto").val(retorno.posto);
        $("#codigo_posto").val(retorno.codigo);
		$("#descricao_posto").val(retorno.nome);
    }

</script>
<style>

	#listaPesquisas tbody td:last-of-type {
		width: 250px;
	}

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
	<div class='titulo_tabela'>Questionário de Avaliação</div>
	<br />
	<div id="questionario_pesquisa"></div>
	<div class='row-fluid'>
		<div class='col-md-2'></div>
		<div class='col-md-4'>
			<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='data_inicial'>Data Inicial</label>
				<div class='controls controls-row'>
					<div class='col-md-4' style="padding-left: 0px !important;">
						<h5 class='asteristico'>*</h5>
							<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value="<?= mostra_data($_POST['data_inicial']) ?>">
					</div>
				</div>
			</div>
		</div>
		<div class='col-md-4'>
			<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='data_final'>Data Final</label>
				<div class='controls controls-row'>
					<div class='col-md-4' style="padding-left: 0px !important;">
						<h5 class='asteristico'>*</h5>
							<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?= mostra_data($_POST['data_final']) ?>" >
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<div class='row-fluid'>
		<div class='col-md-2'></div>
		<div class='col-md-4'>
			<div class='control-group'>
				<label class='control-label' for='codigo_posto'>Tipo Pesquisa</label>
				<div class='controls controls-row'>
					<div class='col-md-7 input-append' style="padding-left: 0px !important;">
						<h5 class='asteristico'>*</h5>
						<select name="categoria_pesquisa" class="form-control">
							<?php
							foreach ($easyBuilderMirror->_tiposPesquisaFabrica[$login_fabrica] as $codigo => $dados) { 

								$selected = ($_POST["categoria_pesquisa"] == $codigo) ? "selected" : "";

							?>
								<option value="<?= $codigo ?>" <?= $selected ?>><?= $dados["descricao"] ?></option>
							<?php
							} ?>
						</select>
					</div>
				</div>
			</div>
		</div>
		<div class='col-md-4'>
			<div class='control-group'>
				<label class='control-label' for='codigo_posto'>Pesquisa</label>
				<div class='controls controls-row'>
					<div class='col-md-7 input-append' style="padding-left: 0px !important;">
						<h5 class='asteristico'>*</h5>
						<select name="pesquisa" class="form-control">
							<option value="">Selecione uma Pesquisa</option>
							<?php
							$sqlPesquisa = "SELECT pesquisa, descricao
                                            FROM tbl_pesquisa
                                            WHERE fabrica = {$login_fabrica}
                                            AND (
                                                   SELECT resposta
                                                   FROM tbl_resposta
                                                   WHERE tbl_resposta.pesquisa = tbl_pesquisa.pesquisa
                                                   LIMIT 1
                                            ) IS NOT NULL
                                            AND tbl_pesquisa.data_input > '2020-01-01 00:00:00'
                                            AND tbl_pesquisa.categoria IN ('".implode("','", array_keys($easyBuilderMirror->_tiposPesquisaFabrica[$login_fabrica]))."')";
	                        $resPesquisa = pg_query($con, $sqlPesquisa);

	                        while ($dados = pg_fetch_object($resPesquisa)) { 

	                               $selected = ($_POST["pesquisa"] == $dados->pesquisa) ? "selected" : "";

	                               ?>
	                               <option value="<?= $dados->pesquisa ?>" <?= $selected ?>><?= $dados->descricao ?></option>
	                        <?php
	                        }
							?>
						</select>
					</div>
				</div>
			</div>
		</div>
	</div>
	<br />
	<input type="hidden" name="posto" id="posto" size="12" maxlength="10" class='span12' value="<?= $_POST['posto'] ?>" >
	<div class='row-fluid'>
		<div class='col-md-2'></div>
		<div class='col-md-4'>
			<div class='control-group'>
				<label class='control-label' for='codigo_posto'>Código Posto</label>
				<div class='controls controls-row'>
					<div class='col-md-7 input-append' style="padding-left: 0px !important;">
						<input type="text" name="codigo_posto" id="codigo_posto" class='col-md-12' value="<? echo $codigo_posto ?>" >
						<span class='add-on' rel="lupa" style="height: 30px;"><i class='icon-search' ></i></span>
						<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
					</div>
				</div>
			</div>
		</div>
		<div class='col-md-4'>
			<div class='control-group'>
				<label class='control-label' for='descricao_posto'>Nome Posto</label>
				<div class='controls controls-row'>
					<div class='col-md-12 input-append' style="padding-left: 0px !important;">
						<input type="text" name="descricao_posto" id="descricao_posto" class='col-md-12' value="<? echo $descricao_posto ?>" >&nbsp;
						<span class='add-on' rel="lupa" style="height: 30px;"><i class='icon-search' ></i></span>
						<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<?php
	if (in_array($login_fabrica, [1])) { ?>
		<div class='row-fluid'>
			<div class='col-md-2'></div>
			<div class='col-md-6'>
				<div class='control-group'>
					<label class='control-label' for='codigo_posto'>Tipo CSV:</label>
					<div class='controls controls-row'>
						<label>
							<input type="radio" name="tipo_csv" value="respondidos" <?= !isset($_POST['tipo_csv']) || $_POST['tipo_csv'] == "respondidos" ? "checked" : "" ?> /> Somente Respondidas
						</label>
						<br />
						<label>
							<input type="radio" name="tipo_csv" value="nao_respondidos" <?= $_POST['tipo_csv'] == "nao_respondidos" ? "checked" : "" ?> /> Somente Não Respondidas
						</label>
						<br />
						<label>
							<input type="radio" name="tipo_csv" value="ambos" <?= $_POST['tipo_csv'] == "ambos" ? "checked" : "" ?> /> Ambos 
						</label>
					</div>
				</div>
			</div>
		</div>
	<?php
	} ?>
    <br /><br />
	<input type="submit" class='btn btn-default' name="btn_acao" value="Pesquisar" />
	<br/><br />
</form>

<table id='listaPesquisas' class='display dataTable table'>
  <thead>
    <tr>
      <th data-column="tbl_pesquisa.categoria">Tipo Pesquisa</th>
      <th data-column="tbl_pesquisa.descricao">Título Pesquisa</th>
      <th data-column="tbl_posto_fabrica.codigo_posto">Código Posto</th>
      <th data-column="tbl_posto.nome">Nome Posto</th>
      <?php
      if (in_array($login_fabrica, [42])) {?>

      	<th data-column="tbl_resposta.campos_adicionais+pontuacaoTotal">Pontos</th>

      <?php
  	  }?>
      <th data-column="tbl_admin.nome_completo">Admin</th>
      <th data-column="tbl_tecnico.nome">Técnico</th>
      <th data-column="tbl_resposta.data_input">Data Resposta</th>
      <th>Detalhes</th>
    </tr>
  </thead>
</table>

<?php
if (isset($_POST['btn_acao']) && count($msg_erro["msg"]) == 0) {
	$jsonPOST = excelPostToJson($_POST);
?>

	<div id='gerar_excel_respostas' class="btn_excel">
		<input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
		<span><img src='imagens/excel.png' /></span>
		<span class="txt">Gerar Arquivo CSV</span>
	</div>

<?php
} else {?>
	<div class="alert alert-info">
		<h5>Para gerar o arquivo excel, realize uma pesquisa.</h5>
	</div>
<?php
}
include "rodape.php";
?>