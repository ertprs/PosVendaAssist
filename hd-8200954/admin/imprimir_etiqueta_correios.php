<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "gerencia,call_center";

include 'autentica_admin.php';
include 'funcoes.php';

if($_POST['ajax_busca_etiqueta']){

	$dados["pedido"]          = $_POST["pedido"];
	$dados["codigo_posto"]    = $_POST["codigo_posto"];
	$dados["descricao_posto"] = $_POST["descricao_posto"];

	$correios = new \Posvenda\ImprimirEtiqueta($login_fabrica);

	try{
		$tipos = $correios->buscaEtiquetaBanco($dados,"pedido");
	}catch(Exception $e){
		exit($e->getMessage());	
	}
	
	echo json_encode($tipos);
	exit;
}

$layout_menu = "callcenter";
$title = "IMPRIMIR ETIQUETA E DECLARAÇÃO DE CONTEÚDO";
include "cabecalho_new.php";

$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable"
);

include("plugin_loader.php");
?>

<style>
.flex {
	display: flex;
}

.flex-wrap {
	flex-wrap: wrap;
}

.valor_frete {
	width: 20%;
	padding: 10px;
	margin: 5px;
	background: yellow;
	border: solid 2px #596d9b;
	color: black;
	font-weight: bold;
	text-align: left;
	font-size: 11px;
}

.box-frete {
	max-width: 100%;
	margin: 0 auto;
	border: 1px solid #ccc;
}

.message_upload {
    margin-right: 1%;
    margin-left: 1%;
    width: 90%;
    white-space: normal;
}
</style>

<script type="text/javascript">
	$(function(){
		$.autocompleteLoad(["posto"]);
		Shadowbox.init();

		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});

		$("#pedido").on("keypress", function(){
			if(window.event.keyCode==13){
				$("#btn_adicionar_pedido").click();
			} else {
				$("#pedido").focus();
			}
		});

		$("#btn_adicionar_pedido").on("click", function(){
			var pedido          = $("#pedido").val();
			var codigo_posto    = $("#codigo_posto").val();
			var descricao_posto = $("#descricao_posto").val();
			limpaCampos();

			if(validaCampo(pedido)){
				showMensagem("Informe um pedido ou código de rastreio", "alert-warning");
			} if(validaCampo(codigo_posto) || validaCampo(descricao_posto)){
				showMensagem("Posto não informado", "alert-warning");
			} else {
				$.ajax({
					url: "imprimir_etiqueta_correios.php",
					type: "POST",
					data: {
						ajax_busca_etiqueta : true,
						pedido 				: pedido,
						codigo_posto		: codigo_posto,
						descricao_posto		: descricao_posto
					}
				}).done(function(data){
					data = JSON.parse(data);

					if(data.resultado){
						var etiquetas_lancadas = $("#etiquetas_lancadas").val();

						if(validaCampo(etiquetas_lancadas)){
							etiquetas_lancadas = "";
						} else {
							etiquetas_lancadas += ",";
						}

						etiquetas_lancadas += data.etiqueta;

						if(verificarEtiquetaLancada(data.etiqueta)){
							showMensagem("Pedido já lançado para impressão da etiqueta!", "alert-error");
						} else {
							$("#etiquetas_lancadas").val(etiquetas_lancadas);

							$("#div_pedido_adicionado").show();
							$("#table_pedido > tbody").append(adiciona_etiqueta_table(data.pedido, data.etiqueta));
							$("#pedido").val("");
						}

						$("#pedido").focus();

					} else {
						showMensagem(data.mensagem, "alert-error");
					}

				}).fail(function(data){
					showMensagem(data, "alert-error");
				});
			}
		});
		
		$("#btn_imprimir").on("click", function(){
			var count = $("#table_pedido > tbody > tr").length;
			disable_button_loading("#" + this.id, "Imprimindo...", false);

			if(count == 0){
				showMensagem("Nenhuma etiqueta informada!", "alert-error");
				disable_button_loading("#" + this.id, "Imprimir Etiqueta", false);
			} else {
				var lista_etiqueta = getEtiquetaTable();

				if(lista_etiqueta != ""){
					window.open("gerar_pdf_etiqueta.php?lista_etiqueta="+lista_etiqueta+"", "_blank");

				} else {
					showMensagem("Erro ao imprimir etiqueta", "alert-error");
				}
				disable_button_loading("#" + this.id, "Imprimir Etiqueta", false);
			}
		});

		$("#btn_declaracao_conteudo").on("click", function(){
			var count = $("#table_pedido > tbody > tr").length;
			disable_button_loading("#" + this.id, "Imprimindo...", false);

			if(count == 0){
				showMensagem("Nenhuma etiqueta informada!", "alert-error");
				disable_button_loading("#" + this.id, "Imprimir Declaração de Conteúdo", false);
			} else {
				var lista_etiqueta = getEtiquetaTable();

				if(lista_etiqueta != ""){
					window.open("gerar_declaracao_conteudo.php?lista_etiqueta="+lista_etiqueta+"", "_blank");

				} else {
					showMensagem("Erro ao imprimir Declaração de Conteúdo", "alert-error");
				}
				disable_button_loading("#" + this.id, "Imprimir Declaração de Conteúdo", false);
			}
		});
	});

	$(document).on("click", "input[id^=btn_excluir_]", function(){
		var tr_etiqueta = this.id.replace("btn_excluir_","");
		var btn_remover = "#" + this.id;

		limpaCampos();
        disable_button_loading(btn_remover, "Excluindo...", true);

    	if (confirm('Deseja realmente excluir a etiqueta da lista de impressão?') == true) {
			var etiqueta           = $("#etiqueta_" + tr_etiqueta).text();
			var etiquetas_lancadas = $("#etiquetas_lancadas").val();
			etiquetas_lancadas     = etiquetas_lancadas.replace(etiqueta, "").replace(",,","");
			$("#etiquetas_lancadas").val(etiquetas_lancadas);

    		disable_button_loading(btn_remover, "Excluir", false);
            $("#tr_etiqueta_" + etiqueta).remove();
    	} else {
    		disable_button_loading(btn_remover, "Excluir", false);
    	}
	});


	function verificarEtiquetaLancada(etiqueta){
		var etiquetas_lancadas = $("#etiquetas_lancadas").val();
		etiquetas_lancadas     = etiquetas_lancadas.split(",");
		var lancada            = false;

		$.each(etiquetas_lancadas, function(key, value){
			if(etiqueta == value){
				lancada = true;
			}
		});

		return lancada;
	}

	function getEtiquetaTable(){
		var lista_etiqueta = "";
		$.each($("#table_pedido > tbody > tr"), function(key, value){
			var etiqueta = value.id.replace("tr_etiqueta_","");
			if(lista_etiqueta != ""){
				lista_etiqueta += ",";
			}
			lista_etiqueta += etiqueta;
		});
		return lista_etiqueta;
	}

	function adiciona_etiqueta_table(pedido, etiqueta){
		var count = $("#table_pedido > tbody > tr").length;

		var tr = "<tr id='tr_etiqueta_" + etiqueta + "'>";
		tr += "<td id='pedido_"   + count + "' class='valign-center tac'>" + gerarUl(pedido)   + "</td>";
		tr += "<td id='etiqueta_" + count + "' class='valign-center tac'>" + etiqueta + "</td>";
		tr += "<td class='valign-center'><input type='button' class='btn btn-error' id='btn_excluir_" + count + "' value='Excluir'/></td>";
		tr += "</tr>";

		return tr;
	}

	function gerarUl(lista_pedidos){
		var ul = "<ul>";
		if(lista_pedidos != ""){

			lista_pedidos = lista_pedidos.split(",");

			$.each(lista_pedidos, function(key, value){
				ul += "<li>" + value + "</li>";
			});
		}
		ul += "</ul>";
		return ul;
	}

    function disable_button_loading(btn_name, text, disable){
        $(btn_name).text(text).attr("disabled", disable);
    }

    function validaCampo(campo){
    	if(campo == "" || campo == undefined || campo == " "){
    		return true;
    	} else {
    		return false;
    	}
    }

	function limpaCampos(){
		$("#retorno_mensagem").removeClass('alert-error alert-success').html("").hide();
	}

	function showMensagem(mensagem, tipo_mensagem){
		$("#retorno_mensagem").addClass(tipo_mensagem).html("<h4>" + mensagem + "</h4>").show();
	}

	function retorna_posto(retorno){
		$("#codigo_posto").val(retorno.codigo);
		$("#descricao_posto").val(retorno.nome);
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
<div class="alert" id="retorno_mensagem" style="display: none;"></div>
<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>

<form name='frm_imprimir_etiqueta_correios' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
	<div class='titulo_tabela '>Parâmetros de Pesquisa</div>
	<br/>
	<div class='row-fluid'>
		<div class='span2'>
			<input type="hidden" id="etiquetas_lancadas" name="etiquetas_lancadas" value="" />
		</div>
		<div class='span4'>
			<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='codigo_posto'>C&oacute;digo Posto</label>
				<div class='controls controls-row'>
					<div class='span7 input-append'>
						<h5 class='asteristico'>*</h5>
						<input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<? echo $codigo_posto ?>">
						<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
						<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
					</div>
				</div>
			</div>
		</div>
		<div class='span4'>
			<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='descricao_posto'>Nome Posto</label>
				<div class='controls controls-row'>
					<div class='span12 input-append'>
						<h5 class='asteristico'>*</h5>
						<input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<? echo $descricao_posto ?>" >&nbsp;
						<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
						<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span6'>
			<div class='control-group <?=(in_array("pedido", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='pedido'>Informe o número do pedido ou o código de rastreio</label>
				<div class='controls controls-row'>
					<div class='span7 input-append'>
						<h5 class='asteristico'>*</h5>
						<input type="text" name="pedido" id="pedido" class='span12' value="<? echo $pedido ?>" >
						<button type="button" class="btn btn-info" id="btn_adicionar_pedido">+</button>
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<div class='row-fluid' id="div_pedido_adicionado" style="display: none;">
		<div class='span2'></div>
		<div class='span8'>
			<table id="table_pedido" class='table table-striped table-bordered table-hover'>
				<thead>
					<tr class='titulo_coluna' >
						<th>Pedido</th>
						<th>Código de Rastreio</th>
						<th>Ação</th>
					</tr>
				</thead>
				<tbody>
				</tbody>
			</table>
		</div>
		<div class='span2'></div>
	</div>
	<p><br />
		<button type="button" class='btn btn-success' id="btn_imprimir">Imprimir Etiqueta</button>
		<button type="button" class='btn' id="btn_declaracao_conteudo">Imprimir Declaração de Conteúdo</button>
		<input type='hidden' id="btn_click" name='btn_acao' value='' />
	</p><br />		
</form>
<?php include "rodape.php"; ?>