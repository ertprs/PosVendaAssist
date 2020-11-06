<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

$get_script 	= $_GET['script_falha'];
$get_familia 	= $_GET['familia'];
$get_defeito 	= $_GET['defeito_reclamado'];


if (strlen(trim($get_familia)) > 0 AND strlen(trim($get_defeito)) > 0) {
	if(strlen(trim($get_familia)) > 0){
		$familia = $get_familia;
	}else{
		$familia = $_POST['familia'];
	}

	if(strlen(trim($get_defeito)) > 0){
		$defeito_reclamado = $get_defeito;
	}else{
		$defeito_reclamado = $_POST['defeito_reclamado'];
	}


	if(strlen($familia)) {
		$sql = "SELECT familia FROM tbl_familia WHERE fabrica = {$login_fabrica} AND familia = {$familia}";
		$res = pg_query($con ,$sql);

		if (!pg_num_rows($res)) {
			$msg_erro["msg"][]    = "Familia não encontrada";
			$msg_erro["campos"][] = "familia";
		}
	}else{
		$msg_erro["campos"][] = "familia";
	}

	if(strlen($defeito_reclamado)){
		$sql = "SELECT 	tbl_defeito_reclamado.defeito_reclamado,
						tbl_defeito_reclamado.descricao
				FROM 	tbl_defeito_reclamado
				JOIN 	tbl_diagnostico ON tbl_diagnostico.defeito_reclamado = tbl_defeito_reclamado.defeito_reclamado
			 		AND tbl_diagnostico.fabrica = {$login_fabrica}
				WHERE 	tbl_defeito_reclamado.fabrica = {$login_fabrica}
				AND 	tbl_defeito_reclamado.ativo IS TRUE
				AND 	tbl_defeito_reclamado.defeito_reclamado = {$defeito_reclamado}";
		$res = pg_query($con, $sql);
		if (!pg_num_rows($res)) {
			$msg_erro["msg"][]    = "Defeito Reclamado não encontrado";
			$msg_erro["campos"][] = "defeito_reclamado";
		}
	}else{
		$msg_erro["campos"][] = "defeito_reclamado";
	}

	if(strlen(trim($defeito_reclamado == 0)) OR strlen(trim($familia)) == 0){
		$msg_erro["msg"][]    = "Preencha os campos obrigatórios";
	}

	if (!count($msg_erro["msg"])) {
		$sql = "SELECT 	tbl_script_falha.script_falha,
						tbl_script_falha.defeito_reclamado,
						tbl_script_falha.familia,
						tbl_script_falha.json_script,
						tbl_script_falha.json_execucao_script
				FROM tbl_script_falha
				WHERE tbl_script_falha.fabrica = {$login_fabrica}
				AND tbl_script_falha.familia = {$familia}
				AND tbl_script_falha.defeito_reclamado = {$defeito_reclamado}";
		$res = pg_query($con, $sql);

		if(pg_last_error()) {
            $msg_erro["msg"][] = "Erro ao buscar Script de falha.";
        }

        if (pg_num_rows($res) > 0){
        	$script_falha 			= pg_fetch_result($res, 0, 'script_falha');
        	$defeito_reclamado 		= pg_fetch_result($res, 0, 'defeito_reclamado');
        	$familia 				= pg_fetch_result($res, 0, 'familia');
        	$json_do_script 		= pg_fetch_result($res, 0, 'json_script');
        	$json_execucao_script 	= pg_fetch_result($res, 0, 'json_execucao_script');
        }else{
        	$script_falha = "";
        	$msg_info = "Nenhum resultado encontrado.";
        }
    }
}

?>
<link rel="stylesheet" type="text/css" href="../plugins/rappid/build/rappid.min.css">
<link href="../plugins/rappid/css/header.css" rel="stylesheet"/>
<link href="../plugins/rappid/css/toolbar.css" rel="stylesheet"/>
<link href="../plugins/rappid/css/statusbar.css" rel="stylesheet"/>
<link href="../plugins/rappid/css/paper.css" rel="stylesheet"/>
<link href="../plugins/rappid/css/preview.css" rel="stylesheet"/>
<link href="../plugins/rappid/css/tooltip.css" rel="stylesheet"/>
<link href="../plugins/rappid/css/snippet.css" rel="stylesheet"/>
<link href="../plugins/rappid/css/dialog.css" rel="stylesheet"/>
<link href="../plugins/rappid/css/index.css" rel="stylesheet"/>
<style type="text/css">
	body{
		background-color: #ffffff;
	}
	.preview .qad-content{
		position: relative !important;
	}
</style>
<script type="text/javascript">
	SVGElement.prototype.getTransformToElement = SVGElement.prototype.getTransformToElement || function (toElement) {
    	return toElement.getScreenCTM().inverse().multiply(this.getScreenCTM());
  	};
	var hora = new Date();
	var engana = hora.getTime();

</script>
<div class='container-fluid'>
	<input type="hidden" name="script_falha" id="script_falha" value="<?=$script_falha?>">

	<input type="hidden" name="json_do_script" id="json_do_script" value='<?=$json_do_script?>'>
	<input type="hidden" name="json_execucao_script" id="json_execucao_script" value='<?=$json_execucao_script?>'>

	<section id="app">
		<div id="toolbar" style="display: none;">
	    	<!--
	    	<button class="btn preview-dialog">Executar Script</button>

	    	<button class="btn execution-script-json">Json de execução do script</button>
	    	<button class="btn script-json">Json do script</button>
			-->
		</div>
	  	<div id="paper" style="display: none;"></div>
	  	<div id="preview" class="preview">
		</div>
	</section>
</div>
<!-- Rappid/JointJS dependencies: -->
	<!--
		/* FAVOR NÃO REMOVER O ARQUIVO plugins/rappid/node_modules/jquery/dist/jquery.js
		 * O MESMO CARREGA A VERSÃO 3.1 DO JQUERY
		 * NECESSARIA PARA UTILIZAÇÃO DO PLUGIN
		 * RAPPID.JS
		*/
	-->
    <script src="../plugins/rappid/node_modules/jquery/dist/jquery.js"></script>
    <script src="../plugins/rappid/node_modules/lodash/index.js"></script>
    <script src="../plugins/rappid/node_modules/backbone/backbone.js"></script>

    <script src="../plugins/rappid/build/rappid.min.js"></script>

    <script src="../plugins/rappid/src/joint.shapes.qad.js"></script>
    <script src="../plugins/rappid/src/selection.js"></script>
    <script src="../plugins/rappid/src/factory.js"></script>
    <script src="../plugins/rappid/src/snippet.js"></script>
    <script src="../plugins/rappid/src/app.js"></script>
    <script src="../plugins/rappid/src/index.js"></script>
    <script>joint.setTheme('modern');</script>
    <script type="text/javascript">
    	var app = app || {};
		window.appView = new app.AppView;

		$(function() {
			appView.previewDialog();
			$(".background").click(function(){
				window.parent.Shadowbox.close();
			});
		});

		var dados = $("#json_do_script").val();
		appView.loadScriptFalha(dados);

		/*
		function teste(){
			var this_g = $("#this_g").val();
			var cellx = $("#cellx").val();
			this_g = JSON.parse(this_g);

			var dialogJSON = teste2(this_g, cellx);

			var $background = $('<div/>').addClass('background').on('click', function() {
	            $('#preview').empty();
	        });

	        $('#preview')
	            .empty()
	            .append([
	                $background,
	                qad.renderDialog(dialogJSON)
	            ])
	            .show();
		}
		function teste2(graph, rootCell){
			var dialog = {
	            root: undefined,
	            nodes: [],
	            links: []
	        };

	        _.each(appView.graph.getCells(), function(cell) {
	        	var o = {
	                id: cell.id,
	                type: cell.get('type')
	            };

	            switch (cell.get('type')) {
	                case 'qad.Question':
	                    o.question = cell.get('question');
	                    o.options = cell.get('options');
	                    dialog.nodes.push(o);
	                    break;
	                case 'qad.Answer':
	                    o.answer = cell.get('answer');
	                    dialog.nodes.push(o);
	                    break;
	                default: // qad.Link
	                    o.source = cell.get('source');
	                    o.target = cell.get('target');
	                    dialog.links.push(o);
	                    break;
	            }
	            if (!cell.isLink() && !appView.graph.getConnectedLinks(cell, { inbound: true }).length) {
	         		dialog.root = cell.id;
	            }
	        });

	        if (rootCell) {
	            dialog.root = rootCell.id;
	        }
	        return dialog;
		}
		*/
    </script>
