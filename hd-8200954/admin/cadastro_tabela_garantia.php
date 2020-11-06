<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro,gerencia,cadastro";
include 'autentica_admin.php';
include 'funcoes.php';

if ($_POST["btn_acao"] == "submit") {
	$empty = false;

	if(strlen($_POST["dados_defeitos"]) == 0){
		$empty = true;
		$msg_erro["campos"][] = "defeito";
	}
	
	if($empty){
		$msg_erro["msg"][] = "Preencha todos os campos obrigatórios";
	}

	if (empty($msg_erro["msg"])) {
		$dados_defeitos = json_decode("[".$_POST["dados_defeitos"]."]");
		
		if (strlen($tabela_garantia_id) > 0) {
			
		} else {
			foreach ($dados_defeitos as $key => $defeito) {

				$lista_defeito = explode(",", $defeito->defeito_reclamado);

				foreach($lista_defeito as $key => $defeito_reclamado) {
					$sql = "SELECT tbl_tabela_garantia.tabela_garantia,
							tbl_cliente_admin.nome
						FROM tbl_tabela_garantia 
							JOIN tbl_cliente_admin ON tbl_cliente_admin.cliente_admin = tbl_tabela_garantia.cliente_admin
								AND tbl_cliente_admin.fabrica = {$login_fabrica}
						WHERE tbl_tabela_garantia.fabrica     	  = {$login_fabrica}
							AND tbl_tabela_garantia.cliente_admin = {$defeito->cliente_admin}
							AND defeito_reclamado                 = {$defeito_reclamado}";
					$res = pg_query($con, $sql);

					if(pg_num_rows($res) == 0){

						pg_query($con, "BEGIN");

						$sql = "INSERT INTO tbl_tabela_garantia (
								fabrica,
								ano_fabricacao,
								mao_de_obra,
								pecas,
								defeito_reclamado,
								cliente_admin
							) VALUES (
								{$login_fabrica},
								{$defeito->ano_fabricacao},
								{$defeito->mao_de_obra},
								{$defeito->pecas},
								{$defeito_reclamado},
								{$defeito->cliente_admin}
							)";
						$res = pg_query($con, $sql);

						if (strlen(pg_last_error()) > 0) {
							pg_query($con, "ROLLBACK");
							$msg_erro["msg"][] = "Erro ao salvar a tabela de garantia";
						} else {
							pg_query($con, "COMMIT");
							$msg_success = "success";
						}
					} else {
						$tabela_garantia = pg_fetch_result($res, 0, tabela_garantia);

						pg_query($con, "BEGIN");

						$sql = "UPDATE tbl_tabela_garantia SET 
								cliente_admin     = {$defeito->cliente_admin},
								defeito_reclamado = {$defeito_reclamado},
								ano_fabricacao    = {$defeito->ano_fabricacao},
								mao_de_obra       = {$defeito->mao_de_obra},
								pecas             = {$defeito->pecas}
							WHERE fabrica = $login_fabrica AND tabela_garantia = $tabela_garantia";
						$res = pg_query($con, $sql);

						if (strlen(pg_last_error()) > 0) {
							pg_query($con, "ROLLBACK");
							$nome              = pg_fetch_result($res, 0, nome);
							$msg_erro["msg"][] = "Erro ao salvar o o defeito para o cliente {$nome}";
						} else {
							pg_query($con, "COMMIT");
							$msg_success = "success";
						}
					}
				}
			}
		}
	}
}

if ($_POST["excluirTabela"]) {
	$tabela_garantia_id = $_POST["tabela_garantia_id"];
	
	if (strlen($tabela_garantia_id) > 0) {
		pg_query($con, "BEGIN");

		$sql = "DELETE FROM tbl_tabela_garantia WHERE fabrica = $login_fabrica AND tabela_garantia = $tabela_garantia_id";
		$res = pg_query($con, $sql);

		if (strlen(pg_last_error()) > 0) {
			pg_query($con, "ROLLBACK");
			echo json_encode(array(
				"success" => false,
				"message" => "Erro ao excluir a tabela de garantia!"
			));
		} else {
			pg_query($con, "COMMIT");
			echo json_encode(array(
				"success" => true,
				"message" => utf8_encode("Tabela de garantia excluída com sucesso!")
			));
		}
	}
	exit;
}

if(isset($_POST["carregarDefeito"])){
	$sql = "SELECT defeito_reclamado, descricao 
		FROM tbl_defeito_reclamado 
			WHERE fabrica = {$login_fabrica} AND ativo IS TRUE
		ORDER BY tbl_defeito_reclamado.descricao";
	$res = pg_query($con, $sql);

	$total_rows = pg_num_rows($res);

	if($total_rows > 0){
		for($i=0; $i<$total_rows; $i++){
			$defeitos[] = array(
				"key"   => pg_fetch_result($res, $i, defeito_reclamado), 
				"value" => utf8_encode(pg_fetch_result($res, $i, descricao)
			));
		}

		echo json_encode(array(
			"success"  => true,
			"defeitos" => $defeitos
		));

	} else {
		echo json_encode(array(
			"success" => false,
			"message" => "Não foi encontrado nenhuma defeito"
		));
	}
	exit;
}

if(isset($_POST["carregarClienteAdmin"])){
	$sql = "SELECT cliente_admin, nome 
		FROM tbl_cliente_admin 
			WHERE fabrica = {$login_fabrica} ORDER BY nome";
	$res = pg_query($con, $sql);

	$total_rows = pg_num_rows($res);

	if($total_rows > 0){
		for($i=0; $i<$total_rows; $i++){
			$clientes[] = array(
				"key"   => pg_fetch_result($res, $i, cliente_admin), 
				"value" => utf8_encode(pg_fetch_result($res, $i, nome)
			));
		}

		echo json_encode(array(
			"success"  => true,
			"clientes" => $clientes
		));

	} else {
		echo json_encode(array(
			"success" => false,
			"message" => "Não foi encontrado nenhum cliente"
		));
	}
	exit;
}

$layout_menu = "cadastro";
$title       = "Cadastro de Tabela de Garantia";
include      'cabecalho_new.php';

$plugins = array("dataTable", "select2");

include("plugin_loader.php");
?>

<script type="text/javascript">
	$("select").select2();

	$(function() {
		$("#defeito_reclamado").select2();
	});

	$(document).ready(function(){

		$.ajax ({
            url: "cadastro_tabela_garantia.php",
            type: "POST",
            data: {
                carregarDefeito: true
            }
        }).done(function(data) {
        	data = JSON.parse(data);

        	$.each(data.defeitos, function(key, value){
        		$("#defeito_reclamado").append($('<option>', { value: value.key, text: value.value } ));
        	});
        });

		$.ajax ({
            url: "cadastro_tabela_garantia.php",
            type: "POST",
            data: {
                carregarClienteAdmin: true
            }
        }).done(function(data) {
        	data = JSON.parse(data);
        	$("#cliente_admin").append($('<option>', { value: "", text: "Selecione um cliente" } ));

        	$.each(data.clientes, function(key, value){
        		$("#cliente_admin").append($('<option>', { value: value.key, text: value.value } ));
        	});
        });

        $("input[type=number]").on("keyup", function(e){
        	var value = $(this).val().replace(/[^\d^]+/g,'');
        	$(this).val(value);
        });

	    $(document).on("click","button.btnEditarCliente", function(){
	    	$(this).attr("disabled", true).html("Editando...");
			var idbtn              = this.id.replace(/[^\d]+/g,'');

			var tabela_garantia_id = $("#tabela_garantia_id_" + idbtn).val();
			var cliente_admin      = $("#result_cliente_admin_" + idbtn).val();
			var defeito_reclamado  = $("#result_defeito_reclamado_" + idbtn).val();

			var ano_fabricacao    = $("#tr_tabela_garantia_" + idbtn).find("td[data-ano_fabricacao]").data("ano_fabricacao");
			var mao_de_obra       = $("#tr_tabela_garantia_" + idbtn).find("td[data-mao_de_obra]").data("mao_de_obra");
			var pecas             = $("#tr_tabela_garantia_" + idbtn).find("td[data-pecas]").data("pecas");

			limpa_campo();

			$('#defeito_reclamado').val(defeito_reclamado.split(","));
			$('#defeito_reclamado').trigger('change');

			$("#cliente_admin option").removeAttr('selected').filter('[value='+ cliente_admin +']').prop('selected', true);
			$("#mao_de_obra").val(mao_de_obra);
			$("#ano_fabricacao").val(ano_fabricacao);
			$("#pecas").val(pecas);
			$("#idcount_defeito").val("");

			$("#defeito_reclamado").focus()
			$(this).attr("disabled", false).html("Editar");
	    });

	    $("#btn_adicionar").on("click", function(){
			var defeito_reclamado   = get_value_select2();
			var value_cliente_admin = $("#cliente_admin").val();
			var cliente_admin       = $("#cliente_admin option:selected").text();
			var mao_de_obra         = $("#mao_de_obra").val();
			var ano_fabricacao      = $("#ano_fabricacao").val();
			var pecas               = $("#pecas").val();
			var idcount_defeito     = $("#idcount_defeito").val();
			var erro                = false;

			if(verificar_vazio(defeito_reclamado)){
				erro = true;
				adiciona_classe_erro("defeito_reclamado");
			}
			if(verificar_vazio(value_cliente_admin)){
				erro = true;
				adiciona_classe_erro("cliente_admin");
			}
			if(verificar_vazio(mao_de_obra)){
				erro = true;
				adiciona_classe_erro("mao_de_obra");
			}
			if(verificar_vazio(ano_fabricacao)){
				erro = true;
				adiciona_classe_erro("ano_fabricacao");
			}
			if(verificar_vazio(pecas)){
				erro = true;
				adiciona_classe_erro("pecas");
			}

			if(!erro){
				var count         = $("#table_cliente_admin > tbody > tr").length + 1;
				var nova_garantia = "<tr id='tr_defeito_" + count + "'>";

				if(!verificar_vazio(idcount_defeito)){
					count = idcount_defeito;
					nova_garantia = "";
				}

				var input     = adiciona_input_hidden("hidden_defeito_reclamado", count, defeito_reclamado);
				input         += adiciona_input_hidden("hidden_cliente_admin", count, value_cliente_admin);
				nova_garantia += adiciona_linha_table("cliente_admin", count, value_cliente_admin, input + cliente_admin);
				nova_garantia += adiciona_linha_table("defeito_reclamado", count, defeito_reclamado, gerar_linhas_selecionadas());
				nova_garantia += adiciona_linha_table("ano_fabricacao", count, ano_fabricacao);
				nova_garantia += adiciona_linha_table("mao_de_obra", count, mao_de_obra);
				nova_garantia += adiciona_linha_table("pecas", count, pecas);
				nova_garantia += adiciona_botoes(count);

				if(!verificar_vazio(idcount_defeito)){
					$("#tr_defeito_" + count).html(nova_garantia);
				} else {
					nova_garantia += "</tr>";
					$("#table_cliente_admin > tbody").append(nova_garantia);
				}
				limpa_campo();
			}
	    });

	    $(document).on("click","button.btnEditarDefeito", function(){
			$(this).attr("disabled", true).html("Editando...");
			var id = this.id.replace(/[^\d]+/g,'');

			var defeito_reclamado = $("#hidden_defeito_reclamado_" + id).val();
			var cliente_admin     = $("#hidden_cliente_admin_" + id).val();
			var ano_fabricacao    = $("#tr_defeito_" + id).find("td[data-ano_fabricacao]").data("ano_fabricacao");
			var mao_de_obra       = $("#tr_defeito_" + id).find("td[data-mao_de_obra]").data("mao_de_obra");
			var pecas             = $("#tr_defeito_" + id).find("td[data-pecas]").data("pecas");

			limpa_campo();

			$('#defeito_reclamado').val(defeito_reclamado.split(","));
			$('#defeito_reclamado').trigger('change');

			$("#cliente_admin option").removeAttr('selected').filter('[value='+ cliente_admin +']').prop('selected', true);
			$("#mao_de_obra").val(mao_de_obra);
			$("#ano_fabricacao").val(ano_fabricacao);
			$("#pecas").val(pecas);
			$("#idcount_defeito").val(id);

			$("#defeito_reclamado").focus();
			$(this).attr("disabled", false).html("Editar");
	    });

	    $(document).on("click","button.btnExcluirDefeito", function(){
			$(this).attr("disabled", true).html("Excluir...");
			var id = this.id.replace(/[^\d]+/g,'');

			if(id != ""){
				if (confirm('Deseja confirmar a exclusão?')) {
					$("#tr_defeito_" + id).remove();
				} else {
					$(this).attr("disabled", false).html("Excluir");
				}
			} else {
				$(this).attr("disabled", false).html("Excluir");
			}
	    });

		$("#btn_acao").on("click", function(){
	    	var arr_posto = [];
    		$(this).attr("disabled", true).html("Gravando...");

			$("#table_cliente_admin").find('tr').each(function(idx, element){
				if (element != undefined && $(element).find("td[data-defeito_reclamado]").data("defeito_reclamado") != undefined){
					var defeito_reclamado = $(element).find("td[data-defeito_reclamado]").data("defeito_reclamado")
					var cliente_admin     = $(element).find("td[data-cliente_admin]").data("cliente_admin")
					var ano_fabricacao    = $(element).find("td[data-ano_fabricacao]").data("ano_fabricacao")
					var mao_de_obra       = $(element).find("td[data-mao_de_obra]").data("mao_de_obra")
					var pecas             = $(element).find("td[data-pecas]").data("pecas")
					arr_posto.push('{ "defeito_reclamado": "' + defeito_reclamado + '","cliente_admin": "' + cliente_admin + '","ano_fabricacao": "' + ano_fabricacao + '","mao_de_obra": "' + mao_de_obra + '","pecas": "' + pecas + '"}');
				}
			});

			$("#dados_defeitos").val(arr_posto);
			submitForm($(this).parents('form'));
		});
	});

	function adiciona_input_hidden(id, count, value){
		return '<input type="hidden" id="' + id + '_' + count + '" value="' + value + '" />';
	}

	function adiciona_linha_table(idcoluna, count, value, text = null){
		if(text == null){
			text = value;
		}
		return "<td id='" + idcoluna + "_" + count + "' data-" + idcoluna + "='" + value + "' >" + text + "</td>";
	}

	function adiciona_botoes(count){
		var botoes = "<td>";
		botoes += "<button type='button' class='btn btn-info btnEditarDefeito' id='btnEditarDefeito_"  + count + "'>Editar</button>";
		botoes += "</td>";
		botoes += "<td>";
		botoes += "<button type='button' class='btn btn-info btnExcluirDefeito' id='btnExcluirDefeito_" + count + "'>Excluir</button>";
		botoes += "</td>";
		return botoes;
	}

    function gerar_linhas_selecionadas(){
    	var linhas_selecionadas = $("#defeito_reclamado :selected").get();

    	if(verificar_vazio(linhas_selecionadas)){
    		return "";
    	} else {
    		var ul_li = "<ul>";

    		$.each(linhas_selecionadas, function(key, linha){
    			ul_li += "<li>" + linha.text + "</li>";
    		});
    		
    		ul_li += "</ul>";
    		return ul_li;
    	}
    }

	function limpa_campo(){
		$("#defeito_reclamado").val(null).trigger('change');
		$("#cliente_admin").val("");
		$("#mao_de_obra").val("");
		$("#ano_fabricacao").val("");
		$("#pecas").val("");
		$("#idcount_defeito").val("");

		$(".group_defeito_reclamado").removeClass("error");
		$(".group_cliente_admin").removeClass("error");
		$(".group_mao_de_obra").removeClass("error");
		$(".group_ano_fabricacao").removeClass("error");
		$(".group_pecas").removeClass("error");
	}

	function adiciona_classe_erro(campo){
		$(".group_" + campo).addClass("error");
	}

	function excluirTabela(tabela_garantia_id) {
		if(confirm("Deseja realmente excluir o registro?")){
			$.ajax ({
	            url: "cadastro_tabela_garantia.php",
	            type: "POST",
	            data: {
	                excluirTabela: true,
	                tabela_garantia_id: tabela_garantia_id
	            }
	        }).done(function(data) {
	        	data = JSON.parse(data);

	        	if(data.success){
	        		$("#tr_tabela_garantia_" + tabela_garantia_id).remove();
	        	}
        		alert(data.message);
	        });
		}
	}

	function verificar_vazio(campo){
        if (campo == "" || campo == " " || campo == null || campo.length == 0){
            return true;
        } else {
            return false;
        }
    }

    function get_value_select2(){
		var defeito_selecionadas = $("#defeito_reclamado :selected").get();
		var defeitos             = "";

	    if(!verificar_vazio(defeito_selecionadas)){
	        $.each(defeito_selecionadas, function(key, defeito){
	            defeitos == "" ? defeitos = defeito.value : defeitos += "," + defeito.value;
	        });
	    }

        return defeitos;
    }
</script>

<style type="text/css">
	select {
		width: 100%;
	}

	.btn_adicionar {
		margin-top: 2.5%;
	}

	tbody {
		text-align: center;
	}

	.th_result {
		padding-left: 0px !important;
		padding-right: 0px !important;
		width: 10%;
	}
</style>

<?php if (count($msg_erro["msg"]) > 0) { ?>
    <div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php } if ($msg_success["msg"] != "") { ?>
    <div class="alert alert-success">
		<h4>Registros salvos com sucesso</h4>
    </div>
<?php } ?>

<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario'>
	<div class='titulo_tabela'>Cadastro</div>
	<br/>
	<input type="hidden" name="tabela_garantia" value="<?=$tabela_garantia;?>">
	<input type="hidden" id="dados_defeitos" name="dados_defeitos" value="<?=$dados_defeitos;?>">
	<input type="hidden" id="idcount_defeito" name="idcount_defeito" value="">

	<div class='row-fluid'>
		<div class='span1'></div>
		<div class='span5'>
			<div class='control-group group_defeito_reclamado <?=(in_array("defeito", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='defeito_reclamado'>Defeito</label>
				<div class='controls controls-row'>
					<div class='span11'>
						<h5 class='asteristico'>*</h5>
						<select id="defeito_reclamado" name="defeito_reclamado" multiple="multiple"></select>
					</div>
				</div>
			</div>
		</div>
		<div class='span5'>
			<div class='control-group group_cliente_admin <?=(in_array("cliente_admin", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='cliente_admin'>Cliente</label>
				<div class='controls controls-row'>
					<div class='span11'>
						<h5 class='asteristico'>*</h5>
						<select id="cliente_admin" name="cliente_admin"></select>
					</div>
				</div>
			</div>
		</div>
		<div class='span1'></div>
	</div>
	<div class='row-fluid'>
		<div class='span1'></div>
		<div class='span5'>
			<div class='control-group group_mao_de_obra <?=(in_array("mao_de_obra", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='mao_de_obra'>Mão de obra (meses)</label>
				<div class='controls controls-row'>
					<div class='span4'>
						<h5 class='asteristico'>*</h5>
						<input type="number" name="mao_de_obra" id="mao_de_obra" size="12" class='span12' value="<?=$mao_de_obra?>"min="0">
					</div>
				</div>
			</div>
		</div>
		<div class='span4'>
			<div class='control-group group_pecas <?=(in_array("pecas", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='pecas'>Peças (meses)</label>
				<div class='controls controls-row'>
					<div class='span5'>
						<h5 class='asteristico'>*</h5>
						<input type="number" name="pecas" id="pecas" size="12" maxlength="2" class='span12' value="<?=$pecas?>" min="0">
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class='row-fluid'>
		<div class='span1'></div>
		<div class='span5'>
			<div class='control-group group_ano_fabricacao <?=(in_array("ano_fabricacao", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='ano_fabricacao'>Ano de Fabricação</label>
				<div class='controls controls-row'>
					<div class='span4'>
						<h5 class='asteristico'>*</h5>
						<input type="number" name="ano_fabricacao" id="ano_fabricacao" class='span12' value="<?=$ano_fabricacao?>" min="0">
					</div>
				</div>
			</div>
		</div>
		<div class='span3 btn_adicionar'>
			<div class='controls controls-row'>
				<div class='span7'>
					<button type="button" class='btn btn-info' id="btn_adicionar">Adicionar +</button>
				</div>
			</div>
		</div>
		<div class='span8'></div>
	</div>
	<div class='row-fluid'>
		<div class='span1'></div>
		<div class='span10'>
			<div class='control-group <?=(in_array("ano_fabricacao", $msg_erro["campos"])) ? "error" : ""?>'>
				<div class='controls controls-row'>
					<div class='span12'>
						<table id="table_cliente_admin" class='table table-striped table-bordered table-fixed'>
							<thead>
								<tr class='titulo_coluna'>
									<th>Cliente</th>
									<th>Defeito</th>
									<th>Ano de Fabricação</th>
									<th>Mão de obra (meses)</th>
									<th>Peças (meses)</th>
									<th colspan="2">Ações</th>
								</tr>
							</thead>
							<tbody>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
	<p><br/>
		<button type="button" class='btn btn-success' id="btn_acao">Gravar</button>
		<input type='hidden' id="btn_click" name='btn_acao' value='' />
	</p><br/>
</form>

<?php 

$sql = "SELECT 
		tbl_tabela_garantia.tabela_garantia,
		tbl_tabela_garantia.cliente_admin,
		tbl_tabela_garantia.defeito_reclamado,
		tbl_tabela_garantia.ano_fabricacao,
		tbl_tabela_garantia.mao_de_obra,
		tbl_tabela_garantia.pecas,
		tbl_defeito_reclamado.descricao,
		tbl_cliente_admin.nome
	FROM tbl_tabela_garantia 
		INNER JOIN tbl_defeito_reclamado ON tbl_defeito_reclamado.defeito_reclamado = tbl_tabela_garantia.defeito_reclamado
			AND tbl_defeito_reclamado.fabrica = {$login_fabrica}
		INNER JOIN tbl_cliente_admin ON tbl_cliente_admin.cliente_admin = tbl_tabela_garantia.cliente_admin
			AND tbl_cliente_admin.fabrica = {$login_fabrica}
		WHERE tbl_tabela_garantia.fabrica = {$login_fabrica}
		ORDER BY defeito_reclamado, ano_fabricacao";
$res = pg_query($con, $sql);

$row = pg_num_rows($res);

if ($row > 0) {
?>
<table id="resultado_tabela_garantia" class='table table-striped table-bordered table-fixed'>
	<tr class='titulo_coluna'>
		<th>Defeito</th>
		<th>Cliente</th>
		<th class="th_result">Ano de Fabricação</th>
		<th class="th_result">Mão de obra (meses)</th>
		<th class="th_result">Peças (meses)</th>
		<th colspan="2">Ações</th>
	</tr>
		<?php 

		for ($i = 0; $i < $row; $i++) { 
			$tabela_garantia_id = pg_fetch_result($res, $i, 'tabela_garantia');
			$defeito_reclamado  = pg_fetch_result($res, $i, 'defeito_reclamado');
			$ano_fabricacao     = pg_fetch_result($res, $i, 'ano_fabricacao');
			$mao_de_obra        = pg_fetch_result($res, $i, 'mao_de_obra');
			$pecas              = pg_fetch_result($res, $i, 'pecas');
			$cliente_admin      = pg_fetch_result($res, $i, 'cliente_admin');
			$nome               = pg_fetch_result($res, $i, 'nome');
			$descricao          = pg_fetch_result($res, $i, 'descricao');
			?>
			<tr id="tr_tabela_garantia_<?=$tabela_garantia_id?>">
				<td id="defeito_<?=$tabela_garantia_id?>">
					<?=$descricao;?>
					<input type="hidden" id="tabela_garantia_id_<?=$tabela_garantia_id?>" value="<?=$tabela_garantia_id?>">
					<input type="hidden" id="result_cliente_admin_<?=$tabela_garantia_id?>" value="<?=$cliente_admin?>">
					<input type="hidden" id="result_defeito_reclamado_<?=$tabela_garantia_id?>" value="<?=$defeito_reclamado?>">
				</td>
				<td id="cliente_admin_<?=$tabela_garantia_id?>"  class="tac"><?=$nome;?></td>
				<td id="ano_fabricacao_<?=$tabela_garantia_id?>" data-ano_fabricacao="<?=$ano_fabricacao?>" class="tac"><?=$ano_fabricacao;?></td>
				<td id="mao_de_obra_<?=$tabela_garantia_id?>"    data-mao_de_obra="<?=$mao_de_obra?>" class="tac"><?=$mao_de_obra;?></td>
				<td id="pecas_<?=$tabela_garantia_id?>" 			data-pecas="<?=$pecas?>" class="tac"><?=$pecas;?></td>
				<td colspan="2" class="tac">
					<button class="btn btn-small btn-warning btnEditarCliente" id="btnEditarClienteAdmin_<?=$tabela_garantia_id?>" >Editar</button>
					<button class="btn btn-small btn-danger btnExcluirCliente" id="btnExcluirClienteAdmin_<?=$tabela_garantia_id?>" onclick="excluirTabela('<?=$tabela_garantia_id;?>');">Excluir</button>
				</td>
			</tr>
		<?php 
		} ?>
</table>
<br>
<?php }

include 'rodape.php' ?>
