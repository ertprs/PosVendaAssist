<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="cadastro, gerencia";
include 'autentica_admin.php';
include 'funcoes.php';

$riMirror = new \Mirrors\Ri\RiMirror($login_fabrica, $login_admin);

if (isset($_POST['gerar_excel'])) {

	$retorno = $riMirror->relatorio([], [
		"dataInicial" 		=> $_GET["data_inicial"],
		"dataFinal"   		=> $_GET["data_final"],
		"codigo"            => $_GET['codigo'],
		"titulo"            => urlencode($_GET['titulo']),
		"familia"           => $_GET['familia'],
		"followup"          => $_GET['followup'],
		"qualidade"         => $_GET['qualidade'],
		"status"            => urlencode($_GET['status']),
		"gerarExcel"        => true
	]);

	$nomeArquivo = "relatorio_informativo_".date("Ymdhis").".csv";

	$caminho   = "/tmp/".$nomeArquivo;
	$linkTdocs = $retorno["link"];

	file_put_contents($caminho, file_get_contents($linkTdocs));

	system("mv /tmp/{$nomeArquivo} xls/{$nomeArquivo}");

	exit("xls/{$nomeArquivo}");

}

try {

	$urlPaginate = "?";
	if (isset($_POST["btn_acao"])) {

		$_POST["data_inicial"] = formata_data($_POST["data_inicial"]);
		$_POST['data_final']   = formata_data($_POST["data_final"]);

		if ((empty($_POST["data_inicial"]) || empty($_POST['data_final']))) {
			throw new \Exception("Informe datas válidas", 400);
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
$title = "Consulta RI";
include 'cabecalho_new.php';

$plugins = array(
   "bootstrap3",
   "shadowbox",
   "dataTableAjax",
   "datepicker",
   "mask",
   "autocomplete"
);

include "plugin_loader.php";

?>
<script>

	var jsonForm = '<?= $jsonForm ?>';

	$(function(){

		$.datepickerLoad(Array("data_final", "data_inicial"));

		$.autocompleteLoad(Array("produto", "peca", "posto"));

		Shadowbox.init();

		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});

		Shadowbox.init();

		var tableElement = $('#listaRi');
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
	          'url':'relatorio_informativo/relatorio_informativo_ajax.php<?= $urlPaginate ?>'
	      },
	      'columns': dataTableColumns,
	      "language": traducaoPtBr
	    });

		$('[data-toggle="popover"]').popover().click();

		setTimeout(function(){
			$('.popover').hide("slow");
		}, 8000);

		$("#listaRi_paginate").click(function () {
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

		$(document).on("click", ".gerar-pdf", function(){

			let ri = $(this).data("ri");

	        $.ajax({
	            url: "relatorio_informativo/relatorio_informativo_ajax.php",
	            type: "POST",
	            data: {
	                gerar_pdf: true,
	                ri: ri
	            },
	            beforeSend: function () {
	                loading("show");
	            },
	            complete: function (data) {
	                window.open(data.responseText, "_blank");

	                loading("hide");
	            }
	        });

		});

	});

</script>
<style>

	#listaRi tbody td:last-of-type {
		width: 250px;
	}

	#menu_sidebar, #menu_sidebar2 {
	    margin-left: 1000px !important;
	}

	#listaRi th {
		color: white;
		background-color: #596d9b;
	}

	#listaRi td {
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
	<div class='titulo_tabela'>Relatório Informativo</div>
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
			<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='data_inicial'>Código</label>
				<div class='controls controls-row'>
					<div class='col-md-12' style="padding-left: 0px !important;">
							<input type="text" name="codigo" id="codigo" class="form-control" value="<?= $_POST['codigo'] ?>">
					</div>
				</div>
			</div>
		</div>
		<div class='col-md-4'>
			<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='data_final'>Título</label>
				<div class='controls controls-row'>
					<div class='col-md-12' style="padding-left: 0px !important;">
							<input type="text" name="titulo" id="titulo" class="form-control" value="<?= $_POST['titulo'] ?>" >
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<div class='row-fluid'>
		<div class='col-md-2'></div>
		<div class='col-md-4'>
			<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='data_inicial'>Família</label>
				<div class='controls controls-row'>
					<div class='col-md-10' style="padding-left: 0px !important;">
						<select class="form-control obrigatorio" style="width: 75% !important;" type="text" id="familia" name="familia">
                            <option value="">Selecione a Família</option>
                            <?php
                            $sqlFamilia = "SELECT familia, descricao
                                           FROM tbl_familia
                                           WHERE fabrica = {$login_fabrica}
                                           ORDER BY descricao ASC";
                            $resFamilia = pg_query($con, $sqlFamilia);

                            while ($dados = pg_fetch_object($resFamilia)) { 

                                $selected = ($_POST['familia'] == $dados->familia) ? "selected" : "";

                            ?>

                                <option value="<?= $dados->familia ?>" <?= $selected ?>><?= $dados->descricao ?></option>

                            <?php
                            }
                            ?>
                        </select>
					</div>
				</div>
			</div>
		</div>
		<div class='col-md-4'>
			<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='data_final'>Follow Up</label>
				<div class='controls controls-row'>
					<div class='col-md-12' style="padding-left: 0px !important;">
						<select class="form-control obrigatorio" type="text" id="followup" name="followup">
                            <option value="">Selecione o Followup</option>
                            <?php
		                    $sqlFollowUp = "SELECT ri_followup, nome
		                                    FROM tbl_ri_followup
		                                    WHERE fabrica = {$login_fabrica}
		                                    AND ativo";
		                    $resFollowUp = pg_query($con, $sqlFollowUp);

		                    while ($dadosFollow = pg_fetch_object($resFollowUp)) {

		                        $selected = $_POST['followup'] == $dadosFollow->ri_followup ? "selected" : "";

		                    ?>
		                        <option value="<?= $dadosFollow->ri_followup ?>" <?= $selected ?>><?= $dadosFollow->nome ?></option>
		                    <?php
		                    } ?>
                        </select>
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<div class='row-fluid'>
		<div class='col-md-2'></div>
		<div class='col-md-4'>
			<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='data_inicial'>Qualidade</label>
				<div class='controls controls-row'>
					<div class='col-md-12' style="padding-left: 0px !important;">
						<select class="form-control" name="qualidade" id="qualidade" style="width: 210px;">
                            <option value="">Selecione uma Opção</option>
                            <option value="BSS" <?= $_POST['qualidade'] == "BSS" ? "selected" : "" ?>>BSS</option>
                            <option value="FG" <?= $_POST['qualidade'] == "FG" ? "selected" : "" ?>>FG</option>
                            <option value="Manaus" <?= $_POST['qualidade'] == "Manaus" ? "selected" : "" ?>>Manaus</option>
                        </select>
					</div>
				</div>
			</div>
		</div>
		<div class='col-md-4'>
			<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='data_final'>Status</label>
				<div class='controls controls-row'>
					<div class='col-md-12' style="padding-left: 0px !important;">
						<select class="form-control" id="status" name="status" style="width: 210px;">
							<option value="">Todos</option>
		                    <option value="Aberto" <?= ($_POST['status'] == "Aberto") ? "selected" : "" ?>>Aberto</option>
		                    <option value="Aguardando Produto" <?= ($_POST['status'] == "Aguardando Produto") ? "selected" : "" ?>>Aguardando Produto</option>
		                    <option value="Finalizado" <?= ($_POST['status'] == "Finalizado") ? "selected" : "" ?>>Finalizado</option>
		                </select>
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
    <br /><br />
	<input type="submit" class='btn btn-default' name="btn_acao" value="Pesquisar" />
	<br/><br />
</form>

<table id='listaRi' class='display dataTable table'>
  <thead>
    <tr class="titulo_coluna">
      <th data-column="tbl_ri.ri">RI</th>
      <th data-column="tbl_ri.codigo">Código</th>
      <th data-column="tbl_familia.nome">Família</th>
      <th data-column="tbl_ri.qualidade">Qualidade</th>
      <th data-column="tbl_ri.titulo">Título</th>
      <th data-column="tbl_ri.data_abertura">Data de Abertura</th>
      <th data-column="tbl_ri.data_chegada">Data de Chegada</th>
      <th data-column="tbl_admin.nome_completo">Aberto Por</th>
      <th data-column="dias_uteis_aberto">Dias Úteis Aberto</th>
      <th data-column="tbl_ri_transferencia.status">Status</th>
      <th data-column="tbl_ri_followup.nome">Follow Up</th>
      <th data-column="total_produtos">Total Produtos</th>
      <th data-column="unorderable">Ações</th>
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