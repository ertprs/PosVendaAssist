<?php
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

<section id="app">
    <div id="toolbar" style="display: none;" >
    </div>
    <div id="paper" style="display: none;" ></div>
    <div id="preview" class="preview" style="display: none;">
    </div>
</section>

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
<!-- fim -->

<style type="text/css">
	/*.fechar{
	    font-size: 15px;
	    float: right;
	    margin-right: 5px;
	    margin-top: 122px;
	    z-index: auto;
	    font-weight: bold;
	    color: #c6c6c6;
	    cursor: pointer;
	}*/
	.joint-dialog.joint-theme-modern .titlebar {
	    /*color: #6a6c8a;*/
	    color: #ffffff;
	    text-shadow: none;
	    /*background-color: #efefef;*/
	    background-color: #596d9b;
	    padding: 10px 25px 10px 10px;
	    font-size: medium;
	    text-align: center;
	}
	.qad-answer-header {
	    text-align: center;
	    color: #4b4a67;
	    font-size: large;
	}
	.qad-question-header {
	    text-align: center;
	    color: #4b4a67;
	    font-size: large;
	}
	.joint-dialog.joint-theme-modern .pergunta_resposta {
	    color: #6a6c8a;
	    text-shadow: none;
	    /*background-color: #efefef;*/
	    background-color: #596d9b;
	    padding: 10px 25px 10px 10px;
	    font-size: small;
	    text-align: left;
	}
	.perguntas{
		font-size: small;
		color: #ffffff;
	}
	.respostas{
		font-size: small;
		color: #ffffff;
	}
	.pergunta_result{
		color: #00ff00;
	}
	.resposta_result{
		color: red;
	}
	.cancelar{
		text-align: center;
	}
	.btn_cancelar{
        border: 1px solid #f10009;
	    /*color: #6A6C8B;*/
	    color: #f10009;
	    background-color: transparent;
	    border-radius: 15px;
	    height: 30px;
	    padding: 0 15px;
	    margin-top: 20px;
	    margin-right: 14px;
	    font-size: 10pt;
	    font-family: Helvetica Neue;
	    cursor: pointer;
	    outline: none;
	}
	.btn_finalizar{
		border: 1px solid #0027f1;
	    /*color: #6A6C8B;*/
	    color: #0027f1;
	    background-color: transparent;
	    border-radius: 15px;
	    height: 30px;
	    padding: 0 15px;
	    margin-top: 20px;
	    margin-right: 14px;
	    font-size: 10pt;
	    font-family: Helvetica Neue;
	    cursor: pointer;
	    outline: none;
	}
</style>
<script type="text/javascript">
	var app = app || {};
	window.appView = new app.AppView;

	function executarScript(tab_atual){
		if(tab_atual.length == 0){
			tab_atual = $("#tab_atual").val();
		}
		$('html, body').animate({scrollTop:100}, 'slow');
		$('[rel="'+tab_atual+'"]').parent().removeClass();
		$('[rel="'+tab_atual+'"]').parent().addClass('tabs-disabled');
		var script_falha = $("#script_falha").val();
	    var json_script = JSON.parse($("#json_script").val());
	    var json_execucao_script = JSON.parse($("#json_execucao_script").val());
	    appView.previewDialogCallcenter(json_execucao_script, tab_atual);
	}

	function retorna_resolution(resolution, ultima_pergunta, resposta, instrucao){
		$("#resolution").show();

		$(".result_resolution").html(resolution.join("\n"));
		$("#script_resolution").val(resolution.join("\n"));
		$("#executar_script").prop('disabled',true);

		$("#ultima_pergunta_script").val(ultima_pergunta);
		$("#ultima_resposta_script").val(resposta);
		$("#ultima_instrucao_script").val(instrucao);
	}
</script>
<? ?>