<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro,gerencia,call_center";
include 'autentica_admin.php';
include 'funcoes.php';

if (isset($_POST["excluir_parametro"]) AND $_POST["excluir_parametro"]){
	$valor = $_POST["valor"];
	$param = $_POST["param"];
	
	if (!empty($valor) AND !empty($param)){
		$sql = "SELECT parametros_adicionais FROM tbl_fabrica WHERE fabrica = {$login_fabrica}";
		$res = pg_query($con, $sql);
		if (pg_num_rows($res) > 0){

			$parametros_adicionais = pg_fetch_result($res, 0, 'parametros_adicionais');
			$parametros_adicionais = json_decode($parametros_adicionais, true);
			unset($parametros_adicionais["regras_pedido"][$param]);
			$parametros_update = json_encode($parametros_adicionais);

			$update = "UPDATE tbl_fabrica SET parametros_adicionais = '$parametros_update' WHERE fabrica = {$login_fabrica}";
			$res_up = pg_query($con, $update);
		
			if (strlen(pg_last_error()) > 0){
				exit(json_encode(array("error" => "error")));
			}else{
				exit(json_encode(array("success" => "success")));
			}
		}
	}
	exit;
}

if ($_POST["btn_acao"] == "submit") {
	$parametro 		    = utf8_encode($_POST['parametro']);
	$valor     		    = utf8_encode($_POST['valor']);
	$parametro_anterior = utf8_encode($_POST['parametro_anterior']);

	$dados = array();

	if (!empty($parametro) AND empty($valor)){
		$msg_erro["msg"][] = "Favor preencher os campos obrigatórios";
		$msg_erro["campos"][] = "valor";
	}

	if (!empty($valor) AND empty($parametro)){
		$msg_erro["msg"][] = "Favor preencher os campos obrigatórios";
		$msg_erro["campos"][] = "parametro";
	}

	if (empty($valor) AND empty($parametro)){
		$msg_erro["msg"][] = "Favor preencher os campos obrigatórios";
		$msg_erro["campos"][] = "parametro";
		$msg_erro["campos"][] = "valor";
	}

	if (!count($msg_erro["msg"])) {
		$sql = "SELECT parametros_adicionais FROM tbl_fabrica WHERE fabrica = {$login_fabrica}";
		$res = pg_query($con, $sql);
		if (pg_num_rows($res) > 0){
			
			$parametros_adicionais = pg_fetch_result($res, 0, 'parametros_adicionais');

			$parametros_adicionais = json_decode($parametros_adicionais, true);

			$parametros_adicionais["regras_pedido"][$parametro] = explode(',', $valor);

			if (!empty($parametro_anterior)){
				if ($parametro_anterior <> $parametro){
					unset($parametros_adicionais["regras_pedido"][$parametro_anterior]);
				}
			}
			
			$parametros_update = json_encode($parametros_adicionais);

			$update = "UPDATE tbl_fabrica SET parametros_adicionais = '$parametros_update' WHERE fabrica = {$login_fabrica}";
			$res_up = pg_query($con, $update);
		
			if (strlen(pg_last_error()) > 0){
				$msg_erro["msg"][] = "Erro ao cadastrar parâmetro";
			}else{
				$msg_success = "Parâmetros gravado com sucesso";
				unset($valor, $parametro, $parametro_anterior);
			}
		}
	}
}

$layout_menu = "gerencia";
$title = "REGRAS PARÂMETROS PEDIDO";
include 'cabecalho_new.php';


$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable"
);

include("plugin_loader.php");
?>

<script type="text/javascript">
	
	$(function(){
		$(".btn_alterar").on("click", function() {
			let valor = $(this).data("valor");
			let param = $(this).data("key");
			
			$("#parametro_anterior").val("");
			$("#parametro").val(param);
			$("#valor").val(valor);
			$("#parametro_anterior").val(param);
		});

		$(".btn_excluir").on("click", function() {
			let btn         = $(this);
			let valor = $(this).data("valor");
			let param = $(this).data("key");
			let linha = $(this).data("linha");

			$(btn).prop({ disabled: true });
		
		    var data_ajax = {
		        excluir_parametro: true,
		        valor: valor,
		        param: param
		    };
		   
		    $.ajax({
		        url: "regras_parametros_pedido.php",
		        type: "post",
		        data: data_ajax,
		        beforeSend: function() {
		            $(btn).prop({ disabled: true }).text("Excluindo...");
		        },
		        async: false,
		        timeout: 10000
		    }).fail(function(res) {
		        alert("Erro ao tentar excluir.. tente novamente");
		        $(btn).prop({ disabled: false }).text("Excluir");
		    }).done(function(res) {
		        res = JSON.parse(res);
		        
		        if (res.retorno == "error") {
		            alert("Erro ao tentar excluir.. tente novamente");
		            $(btn).prop({ disabled: false }).text("Excluir");
		        } else {
		            alert("Registro excluido com sucesso");
		            $("#"+linha).remove();
		        }
		    });
		});
	});
</script>

<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}

if (!empty($msg_success)){
?>
	<div class="alert alert-success">
		<h4><?=$msg_success?></h4>
    </div>
<?php
}
?>

<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario'>
		<div class='titulo_tabela '>Parâmetros de Pesquisa</div>
		<br/>

		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("parametro", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='parametro'>Parâmetro</label>
					<div class='controls controls-row'>
						<div class='span12'>
							<h5 class='asteristico'>*</h5>
							<input type="text" id="parametro" name="parametro" class='span12' maxlength="20" value="<? echo $parametro ?>" >
							<input type="hidden" id="parametro_anterior" name="parametro_anterior" value="">
						</div>
					</div>
				</div>
			</div>
			<div class='span5'>
				<div class='control-group <?=(in_array("valor", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='valor'>Valor</label> <span class="label label-important"> Obs* Se tiver mais de um valor seperar por virgula</span>
					<div class='controls controls-row'>
						<div class='span12'>
							<h5 class='asteristico'>*</h5>
							<input type="text" id="valor" name="valor" class='span12' value="<? echo $valor?>" >
						</div>
					</div>
				</div>
			</div>
			<div class='span1'></div>
		</div>
		
		<p><br/>
			<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Gravar</button>
			<input type='hidden' id="btn_click" name='btn_acao' value='' />
		</p><br/>
</form>

<?php

$sql = "SELECT parametros_adicionais FROM tbl_fabrica WHERE fabrica = {$login_fabrica}";
$res = pg_query($con, $sql);

if (pg_num_rows($res) > 0) { ?>

	<table id="parametros_pedido" class='table table-striped table-bordered table-hover table-fixed' >
		<thead>
			<tr class='titulo_tabela'>
				<th colspan="3">Regras Pedidos</th>
			</tr>
			<tr class='titulo_coluna' >
				<th>Parâmetros</th>
				<th>Valor</th>
				<th>Ações</th>
            </tr>
		</thead>
		<tbody>
			<?php
				$parametros_adicionais = pg_fetch_result($res, 0, 'parametros_adicionais');
				$parametros_adicionais = json_decode($parametros_adicionais, true);

				$count = 0;
				foreach ($parametros_adicionais["regras_pedido"] as $key => $value) {
					$key = utf8_decode($key);
					$value = array_map_recursive('utf8_decode', $value);
					$valores = implode(", ", $value);
			?>
				<tr id='<?=$count?>'>
					<td><?=$key?></td>
					<td><?=$valores?></td>
					<td class='tac'>
						<button type='button' data-linha='<?=$count?>' data-key='<?=$key?>' data-valor='<?=$valores?>' class='btn btn-primary btn_alterar btn-small'>Alterar</button>
						<button type='button' data-linha='<?=$count?>' data-key='<?=$key?>' data-valor='<?=$valores?>' class='btn btn-danger btn_excluir btn-small'>Excluir</button>
					</td>
				</tr>

			<?php		
					$count++;
				}
			?>
		</tbody>
	</table>

<?php
}else{
	echo '
	<div class="container">
	<div class="alert">
		    <h4>Nenhum resultado encontrado</h4>
	</div>
	</div>';
}
	



include 'rodape.php';?>
