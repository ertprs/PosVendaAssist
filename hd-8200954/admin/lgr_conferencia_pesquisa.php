<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios = "auditoria";

include "autentica_admin.php";
include "funcoes.php";

if ($_POST["ajax_libera_provisorio"] == true) {
	try {
		$extrato = $_POST["extrato"];

		if (empty($extrato)) {
			throw new Exception("Extrato não informado");
		}

		$sql = "UPDATE tbl_extrato SET admin_lgr = {$login_admin} WHERE fabrica = {$login_fabrica} AND extrato = {$extrato}";
		$res = pg_query($con, $sql);

		if (strlen(pg_last_error()) > 0) {
			throw new Exception("Erro ao liberar extrato provisoriamente");
		}

		$retorno = array("success" => true);
	} catch(Exception $e) {
		$retorno = array("erro" => utf8_encode($e->getMessage()));
	}

	exit(json_encode($retorno));
}

if ($_POST["ajax_bloquear_extrato"] == true) {
	try {
		$extrato = $_POST["extrato"];

		if (empty($extrato)) {
			throw new Exception("Extrato não informado");
		}

		$sql = "UPDATE tbl_extrato SET admin_lgr = NULL WHERE fabrica = {$login_fabrica} AND extrato = {$extrato}";
		$res = pg_query($con, $sql);

		if (strlen(pg_last_error()) > 0) {
			throw new Exception("Erro ao bloquear extrato");
		}

		$retorno = array("success" => true);
	} catch(Exception $e) {
		$retorno = array("erro" => utf8_encode($e->getMessage()));
	}

	exit(json_encode($retorno));
}

if ($_POST["btn_acao"] == "submit") {
	$data_inicial    = $_POST['data_inicial'];
	$data_final      = $_POST['data_final'];
	$codigo_posto    = $_POST['codigo_posto'];
	$descricao_posto = $_POST['descricao_posto'];
	$nota_devolucao  = $_POST["nota_devolucao"];
	$status          = $_POST["status"];

	if ((!strlen($data_inicial) or !strlen($data_final)) && empty($nota_devolucao)) {
		$msg_erro["msg"][]    = "Preencha os campos obrigatórios";
		$msg_erro["campos"][] = "data";
	} else if (!empty($data_inicial) && !empty($data_final)) {
		list($di, $mi, $yi) = explode("/", $data_inicial);
		list($df, $mf, $yf) = explode("/", $data_final);

		if (!checkdate($mi, $di, $yi) or !checkdate($mf, $df, $yf)) {
			$msg_erro["msg"][]    = "Data Inválida";
			$msg_erro["campos"][] = "data";
		} else {
			$aux_data_inicial = "{$yi}-{$mi}-{$di}";
			$aux_data_final   = "{$yf}-{$mf}-{$df}";

			if (strtotime($aux_data_final) < strtotime($aux_data_inicial)) {
				$msg_erro["msg"][]    = "Data de envio final não pode ser menor que a Data de envio inicial";
				$msg_erro["campos"][] = "data";
			}
		}
	}

	if (strlen($codigo_posto) > 0 or strlen($descricao_posto) > 0){
		$sql = "SELECT tbl_posto_fabrica.posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica USING(posto)
				WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
				AND (
					(UPPER(tbl_posto_fabrica.codigo_posto) = UPPER('{$codigo_posto}'))
					OR
					(TO_ASCII(UPPER(tbl_posto.nome), 'LATIN-9') = TO_ASCII(UPPER('{$descricao_posto}'), 'LATIN-9'))
				)";
		$res = pg_query($con ,$sql);

		if (!pg_num_rows($res)) {
			$msg_erro["msg"][]    = "Posto não encontrado";
			$msg_erro["campos"][] = "posto";
		} else {
			$posto = pg_fetch_result($res, 0, "posto");
		}
	}

	if (!count($msg_erro["msg"])) {
		if (!empty($posto)) {
			$wherePosto = "AND tbl_faturamento.distribuidor = {$posto}";
		}

		if (!empty($nota_devolucao)) {
			$whereNotaDevolucao = "AND tbl_faturamento.nota_fiscal = '{$nota_devolucao}'";
		}

		if (!empty($status)) {
			switch ($status) {
				case "nao_conferida":
					$whereStatus = "
						AND tbl_faturamento.conferencia IS NULL
						AND tbl_faturamento.cancelada IS NULL
					";
					$havingStatus = "HAVING SUM(tbl_faturamento_item.qtde_inspecionada) = 0";
					break;
				
				case "conferida_parcial":
					$whereStatus = "
						AND tbl_faturamento.conferencia IS NULL
						AND tbl_faturamento.cancelada IS NULL
					";
					$havingStatus = "HAVING SUM(tbl_faturamento_item.qtde_inspecionada) > 0 AND (SUM(tbl_faturamento_item.qtde_inspecionada) < SUM(tbl_extrato_lgr.qtde_nf))";
					break;

				case "conferida":
					$whereStatus = "
						AND tbl_faturamento.conferencia IS NOT NULL
						AND tbl_faturamento.cancelada IS NULL
					";
					break;

				case "cancelada":
					$whereStatus = "
						AND tbl_faturamento.cancelada IS NOT NULL
					";
					break;
			}
		}

		$sql = "SELECT
					DISTINCT tbl_faturamento.faturamento,
					tbl_posto.nome AS posto,
					tbl_extrato.extrato,
					tbl_faturamento.nota_fiscal,
					tbl_faturamento.total_nota AS valor_nota,
					SUM(lgr.qtde) AS qtde_pecas,
					SUM(tbl_faturamento_item.qtde_inspecionada) AS qtde_pecas_conferidas,
					TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY') AS data_emissao,
					TO_CHAR(tbl_faturamento.conferencia, 'DD/MM/YYYY') AS data_conferencia,
					TO_CHAR(tbl_faturamento.cancelada, 'DD/MM/YYYY') AS data_cancelamento,
					tbl_faturamento.emissao,
					(CASE WHEN tbl_extrato.admin_lgr IS NOT NULL THEN TRUE ELSE FALSE END) AS liberado_provisoriamente,
					tbl_faturamento.devolucao_concluida
				FROM tbl_faturamento_item
				INNER JOIN tbl_faturamento ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
				INNER JOIN tbl_posto ON tbl_posto.posto = tbl_faturamento.distribuidor
				INNER JOIN tbl_extrato ON tbl_extrato.extrato = tbl_faturamento_item.extrato_devolucao OR tbl_extrato.extrato = tbl_faturamento.extrato_devolucao
				INNER JOIN tbl_extrato_lgr ON tbl_extrato_lgr.extrato = tbl_extrato.extrato AND tbl_extrato_lgr.peca = tbl_faturamento_item.peca
				JOIN tbl_faturamento_item lgr ON lgr.faturamento = tbl_extrato_lgr.faturamento and lgr.os_item = tbl_faturamento_item.os_item
				INNER JOIN tbl_fabrica ON tbl_faturamento.fabrica = tbl_fabrica.fabrica AND tbl_fabrica.posto_fabrica = tbl_faturamento.posto
				WHERE tbl_faturamento.fabrica = {$login_fabrica}
				AND tbl_extrato.fabrica = {$login_fabrica}
				AND tbl_faturamento.distribuidor IS NOT NULL
				{$wherePosto}
				{$whereNotaDevolucao}
				{$whereStatus}
				GROUP BY
					tbl_faturamento.faturamento,
					tbl_posto.nome,
					tbl_extrato.extrato,
					tbl_faturamento.nota_fiscal,
					tbl_faturamento.total_nota,
					tbl_faturamento.emissao,
					tbl_faturamento.conferencia,
					tbl_faturamento.cancelada,
					tbl_faturamento.devolucao_concluida,
					tbl_extrato.admin_lgr
				{$havingStatus}
				ORDER BY tbl_faturamento.emissao ASC";
		$resSubmit = pg_query($con, $sql);
	}
}

$layout_menu = "auditoria";
$title       = "CONSULTA NOTAS DE DEVOLUÇÃO";

include "cabecalho_new.php";

$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask"
);

include "plugin_loader.php";

?>

<style>

td.cor_legenda {
	width: 10px;
	height: 10px;
	padding: 5px;
}

td.titulo_legenda {
	font-size: 12px;
	font-weight: bold;
	text-align: left;
	padding-left: 2px;
	padding-right: 10px;
}

tr.nao_conferida > td, td.nao_conferida {
	background-color: #D6D6D6;
}

tr.conferida_parcial > td, td.conferida_parcial {
	background-color: #FAFF73;
}

tr.conferida > td, td.conferida {
	background-color: #8DFF70;
}

tr.cancelada > td, td.cancelada {
	background-color: #FF8282;
}

</style>

<script>

$(function() {
	$.datepickerLoad(["data_final", "data_inicial"]);
	$.autocompleteLoad(["posto"]);
	Shadowbox.init();

	$("span[rel=lupa]").click(function () {
		$.lupa($(this));
	});

	$("button[name=conferencia]").click(function() {
		var faturamento = $(this).data("faturamento");

		if (typeof faturamento != "undefined") {
			window.open("lgr_conferencia.php?faturamento="+faturamento);
		}
	});

	$(document).on("click", "button[name=liberar_provisorio]", function() {
		var extrato = $(this).data("extrato");
		var td      = $(this).parents("td");

		if (typeof extrato != "undefined") {
			$.ajax({
				url: "lgr_conferencia_pesquisa.php",
				type: "post",
				data: { ajax_libera_provisorio: true, extrato: extrato },
				beforeSend: function() {
					$(td).find("button").hide();
					$(td).prepend("<div class='alert alert-info' style='margin: 0px;' >Liberando extrato Aguarde...</div>");
				}
			}).always(function(data) {
				if (data.erro) {
					alert(data.erro);
				} else {
					$(td).find("button.btn-success").removeClass("btn-success").addClass("btn-danger").text("Bloquear Extrato").attr({ name: "bloquear_extrato" });
				}

				$(td).find("div.alert-info").remove();
				$(td).find("button").show();
			});
		}
	});

	$(document).on("click", "button[name=bloquear_extrato]", function() {
		var extrato = $(this).data("extrato");
		var td      = $(this).parents("td");

		if (typeof extrato != "undefined") {
			$.ajax({
				url: "lgr_conferencia_pesquisa.php",
				type: "post",
				data: { ajax_bloquear_extrato: true, extrato: extrato },
				beforeSend: function() {
					$(td).find("button").hide();
					$(td).prepend("<div class='alert alert-info' style='margin: 0px;' >Bloqueando extrato Aguarde...</div>");
				}
			}).always(function(data) {
				if (data.erro) {
					alert(data.erro);
				} else {
					$(td).find("button.btn-danger").removeClass("btn-danger").addClass("btn-success").text("Liberar Provisório").attr({ name: "liberar_provisorio" });
				}

				$(td).find("div.alert-info").remove();
				$(td).find("button").show();
			});
		}
	});
});

function retorna_posto(retorno){
    $("#codigo_posto").val(retorno.codigo);
	$("#descricao_posto").val(retorno.nome);
}

</script>

<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error" >
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>

<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>

<form method="post" style="margin: 0 auto;" class="form-search form-inline tc_formulario" >
	<div class="titulo_tabela" >Parâmetros de Pesquisa</div>

	<br/>

	<div class="row-fluid">
		<div class="span2"></div>
		<div class="span4">
			<div class="control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>" >
				<label class="control-label" for="data_inicial" >Data do envio inicial</label>
				<div class="controls controls-row" >
					<div class="span12" >
						<h5 class="asteristico" >*</h5>
						<input type="text" name="data_inicial" id="data_inicial" class="span6" value="<?=$_POST['data_inicial']?>" />
					</div>
				</div>
			</div>
		</div>
		<div class="span4">
			<div class="control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>" >
				<label class="control-label" for="data_final" >Data do envio final</label>
				<div class="controls controls-row" >
					<div class="span12" >
						<h5 class="asteristico" >*</h5>
						<input type="text" name="data_final" id="data_final" class="span6" value="<?=$_POST['data_final']?>" />
					</div>
				</div>
			</div>
		</div>
		<div class="span2"></div>
	</div>

	<div class="row-fluid">
		<div class="span2"></div>
		<div class="span4">
			<div class="control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>" >
				<label class="control-label" for="codigo_posto" >Código do Posto</label>
				<div class="controls controls-row" >
					<div class="span12 input-append" >
						<input type="text" name="codigo_posto" id="codigo_posto" class="span8" maxlength="20" value="<?=$_POST['codigo_posto']?>" />
						<span class="add-on" rel="lupa" ><i class="icon-search" ></i></span>
						<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
					</div>
				</div>
			</div>
		</div>
		<div class="span4">
			<div class="control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>" >
				<label class="control-label" for="descricao_posto" >Nome do Posto</label>
				<div class="controls controls-row" >
					<div class="span12 input-append" >
						<input type="text" name="descricao_posto" id="descricao_posto" class="span12" maxlength="150" value="<?=$_POST['descricao_posto']?>" />
						<span class="add-on" rel="lupa" ><i class="icon-search" ></i></span>
						<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
					</div>
				</div>
			</div>
		</div>
		<div class="span2"></div>
	</div>

	<div class="row-fluid">
		<div class="span2"></div>
		<div class="span8">
			<div class="control-group <?=(in_array("nota_devolucao", $msg_erro["campos"])) ? "error" : ""?>" >
				<label class="control-label" for="nota_devolucao" >Número da Nota</label>
				<div class="controls controls-row" >
					<div class="span12 input-append" >
						<input type="text" name="nota_devolucao" id="nota_devolucao" class="span4" maxlength="20" value="<?=$_POST['nota_devolucao']?>" />
					</div>
				</div>
			</div>
		</div>
		<div class="span2"></div>
	</div>

	<div class="row-fluid">
		<div class="span2"></div>
		<div class="span8">
			<div class="control-group <?=(in_array("status", $msg_erro["campos"])) ? "error" : ""?>" >
				<label class="control-label" >Situação da Nota</label>
				<div class="controls controls-row" >
					<div class="span12" >
						<h5 class="asteristico" >*</h5>
						<label class="radio"><input type="radio" name="status" value="" checked />Todas</label>
						<label class="radio"><input type="radio" name="status" value="nao_conferida" <?=($_POST["status"] == "nao_conferida") ? "checked" : ""?> />Não conferida</label>
						<label class="radio"><input type="radio" name="status" value="conferida_parcial" <?=($_POST["status"] == "conferida_parcial") ? "checked" : ""?> />Conferida parcial</label>
						<label class="radio"><input type="radio" name="status" value="conferida" <?=($_POST["status"] == "conferida") ? "checked" : ""?> />Conferida</label>
						<label class="radio"><input type="radio" name="status" value="cancelada" <?=($_POST["status"] == "cancelada") ? "checked" : ""?> />Cancelada</label>
					</div>
				</div>
			</div>
		</div>
		<div class="span2"></div>
	</div>

	<br />

	<p>
		<button type="button" class="btn" id="btn_acao" onclick="submitForm($(this).parents('form'));" >Pesquisar</button>
		<input type="hidden" id="btn_click" name="btn_acao" />
	</p>

	<br />	
</form>

<!--Fecha div.container-->
</div>

<br />

<?php
if (isset($resSubmit)) {
	if (pg_num_rows($resSubmit) > 0) {
	?>
		<table class="table table-bordered table-large" style="margin: 0 auto;" >
			<thead>
				<?php
				if (empty($status)) {
				?>
					<tr>
						<th colspan="10" >
							<table>
								<!--Não conferida-->
								<td class="cor_legenda nao_conferida" >&nbsp;</td>
								<td class="titulo_legenda" >Não conferida</td>

								<!--Conferida parcial-->
								<td class="cor_legenda conferida_parcial" >&nbsp;</td>
								<td class="titulo_legenda" >Conferida parcial</td>

								<!--Conferida-->
								<td class="cor_legenda conferida" >&nbsp;</td>
								<td class="titulo_legenda" >Conferida</td>

								<!--Cancelada-->
								<td class="cor_legenda cancelada" >&nbsp;</td>
								<td class="titulo_legenda" >Cancelada</td>
							</table>
						</th>
					</tr>
				<?php
				}
				?>
				<tr class="titulo_coluna" >
					<th>Posto</th>
					<th>Extrato</th>
					<th>Nota Fiscal</th>
					<th>Valor da Nota</th>
					<th>Qtde Peças</th>
					<th>Qtde Peças Conferidas</th>
					<th>Data Emissão</th>
					<th>Data Conferência</th>
					<th>Data Cancelamento</th>
					<th>Ação</th>
				</tr>
			</thead>
			<tbody>
				<?php
				while ($result = pg_fetch_object($resSubmit)) {
					$conferida = false;

					if (empty($status)) {
						if (empty($result->data_conferencia) && empty($result->data_cancelamento) && $result->qtde_pecas_conferidas == 0) {
							$rowClass = "nao_conferida";
						} else if (empty($result->data_conferencia) && $result->qtde_pecas_conferidas < $result->qtde_pecas && empty($result->data_cancelamento)) {
							$rowClass = "conferida_parcial";
						} else if (!empty($result->data_conferencia) && empty($result->data_cancelamento)) {
							$rowClass = "conferida";
						} else if (!empty($result->data_cancelamento)) {
							$rowClass = "cancelada";
						}
					}
					?>
					<tr class="<?=$rowClass?>" >
						<td><?=$result->posto?></td>
						<td><?=$result->extrato?></td>
						<td><?=$result->nota_fiscal?></td>
						<td class="tar" ><?=number_format($result->valor_nota, 2, ",", ".")?></td>
						<td class="tar" ><?=$result->qtde_pecas?></td>
						<td class="tar" ><?=$result->qtde_pecas_conferidas?></td>
						<td class="tar" ><?=$result->data_emissao?></td>
						<td class="tar" ><?=$result->data_conferencia?></td>
						<td class="tar" ><?=$result->data_cancelamento?></td>
						<td style="vertical-align: middle;" nowrap >
							<?php
							if (empty($data_cancelamento) && $result->devolucao_concluida != "t") {
								if (empty($result->data_conferencia)) {
								?>
									<button type="button" data-faturamento="<?=$result->faturamento?>" name="conferencia" class="btn btn-primary btn-small">Conferência</button>
								<?php
								}

								if ($result->liberado_provisoriamente == "t") {
								?>
									<button type="button" data-extrato="<?=$result->extrato?>" name="bloquear_extrato" class="btn btn-danger btn-small" >Bloquear Extrato</button>
								<?php
								} else {
								?>
									<button type="button" data-extrato="<?=$result->extrato?>" name="liberar_provisorio" class="btn btn-success btn-small" >Liberar Provisório</button>
								<?php
								}
							}
							?>
						</td>
					</tr>
				<?php
				}
				?>
			</tbody>
		</table>
	<?php
	} else {
	?>
		<div class="container" >
			<div class="alert alert-danger" >
		    	<h4>Nenhum resultado encontrado</h4>
			</div>
		</div>
	<?php
	}
}
?>

<br />

<?php

include "rodape.php";

?>
