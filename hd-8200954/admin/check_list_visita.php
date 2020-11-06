<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "auditoria";
include 'autentica_admin.php';

include 'funcoes.php';

$layout_menu = "auditoria";
$title = "CADASTRO DE CHECKLIST DE VISITA";

include 'cabecalho_new.php';

$plugins = array(
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable"
);

include "plugin_loader.php";


?>
<script>
	$(function() {
		Shadowbox.init();

		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});
	});

	function retorna_posto(retorno){
        $("#codigo_posto").val(retorno.codigo);
		$("#descricao_posto").val(retorno.nome);
		$("#id_posto").val(retorno.posto);
    }
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
<form name='frm_checklist' METHOD='POST' ACTION='checklist_print.php' align='center' class='form-search form-inline'>
	<div id="lupa_posto" class="tc_formulario">
		<div class='titulo_tabela '>Selecione o Posto Autorizado</div>
		<br />
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='codigo_posto'>Código Posto</label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<? echo $codigo_posto ?>" >
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
						</div>
					</div>
				</div>
			</div>
			<input type="hidden" name="posto" id="id_posto" />
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='descricao_posto'>Nome Posto</label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<? echo $descricao_posto ?>" >&nbsp;
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<br />
		<div class='row-fluid tac'>
			<button class="btn btn-primary">Cadastrar novo check list</button>
		</div>
	</div>
	<br />
</form>
<?php
include "rodape.php"; ?>