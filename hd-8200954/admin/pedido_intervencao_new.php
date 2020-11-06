<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="gerencia,auditoria";

include "autentica_admin.php";
include "funcoes.php";
if ($_POST["ajax_aprova_pedido"] == true) {
	$pedido = $_POST["pedido"];

	if (empty($pedido)) {
		$retorno = array("error" => utf8_encode("Pedido não informado"));
	} else {
		$sql = "SELECT pedido FROM tbl_pedido WHERE fabrica = {$login_fabrica} AND pedido = {$pedido}";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) == 0) {
			$retorno = array("error" => utf8_encode("Pedido não encontrado"));
		} else {
			$res = pg_query($con, "BEGIN TRANSACTION");

			$sql = "UPDATE tbl_pedido SET status_pedido = 1 WHERE fabrica = {$login_fabrica} AND pedido = {$pedido}";
			$res = pg_query($con, $sql);

			if (strlen(pg_last_error()) > 0) {
				$retorno = array("error" => utf8_encode("Erro ao aprovar pedido"));
			} else {
				$sql = "SELECT posto FROM tbl_pedido WHERE fabrica = {$login_fabrica} AND pedido = {$pedido}";
    			$res = pg_query($con, $sql);

    			$posto = pg_fetch_result($res, 0, "posto");

    			if (strlen($posto) == 0) {
    				$retorno = array("error" => utf8_encode("Posto não encontrado"));
    			} else {
    				$aux_sql = "
    					O pedido <a href='pedido_finalizado.php?pedido=$pedido' target='_blank' >$pedido</a> foi aprovado pela Fábrica
    				";
    				$aux_sql = pg_escape_string($con, $aux_sql);

    				$sql = "INSERT INTO tbl_comunicado (
								fabrica,
								posto,
								obrigatorio_site,
								tipo,
								ativo,
								descricao,
								mensagem
							) VALUES (
								{$login_fabrica},
								{$posto},
								true,
								'Com. Unico Posto',
								true,
								'Pedido $pedido aprovado',
								'{$aux_sql}'
							)";
					$res = pg_query($con, $sql);

					$sql = "
						INSERT INTO tbl_pedido_status (
							pedido,
							data,
							status,
							admin
						) VALUES (
							{$pedido},
							CURRENT_TIMESTAMP,
							1,
							{$login_admin}
						)";
					$res = pg_query($con, $sql);

					if (strlen(pg_last_error()) > 0) {
						$res = pg_query($con, "ROLLBACK TRANSACTION");
						$retorno = array("error" => utf8_encode("Erro ao aprovar pedido"));
					} else {
						$res = pg_query($con, "COMMIT TRANSACTION");
						$retorno = array("success" => true);
					}
    			}
			}
		}
	}

	exit(json_encode($retorno));
}

if ($_POST["ajax_recusa_pedido"] == true) {
	$pedido = $_POST["pedido"];
	$motivo = trim($_POST["motivo"]);

	if (empty($pedido)) {
		$retorno = array("error" => utf8_encode("Pedido não informado"));
	} else if (!strlen($motivo)) {
		$retorno = array("error" => utf8_encode("Informe o motivo da recusa"));
	} else {
		$sql = "SELECT pedido FROM tbl_pedido WHERE fabrica = {$login_fabrica} AND pedido = {$pedido}";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) == 0) {
			$retorno = array("error" => utf8_encode("Pedido não encontrado"));
		} else {
			$res = pg_query($con, "BEGIN TRANSACTION");

			$sql = "UPDATE tbl_pedido SET status_pedido = 17 WHERE fabrica = {$login_fabrica} AND pedido = {$pedido}";
			$res = pg_query($con, $sql);

			if (strlen(pg_last_error()) > 0) {
				$retorno = array("error" => utf8_encode("Erro ao recusar pedido"));
			} else {
    			$sql = "SELECT posto FROM tbl_pedido WHERE fabrica = {$login_fabrica} AND pedido = {$pedido}";
    			$res = pg_query($con, $sql);

    			$posto = pg_fetch_result($res, 0, "posto");

    			if (strlen($posto) == 0) {
    				$retorno = array("error" => utf8_encode("Posto não encontrado"));
    			} else {
    				$aux_sql = "
    					O pedido <a href='pedido_finalizado.php?pedido=$pedido' target='_blank' >$pedido</a> foi recusado pela Fábrica, o comprovante de pagamento foi deletado, por favor anexe um novo comprovante de pagamento.<br />Motivo da recusa: {$motivo}
    				";
    				$aux_sql = pg_escape_string($con, $aux_sql);

    				$sql = "INSERT INTO tbl_comunicado (
								fabrica,
								posto,
								obrigatorio_site,
								tipo,
								ativo,
								descricao,
								mensagem
							) VALUES (
								{$login_fabrica},
								{$posto},
								true,
								'Com. Unico Posto',
								true,
								'Pedido $pedido recusado',
								'{$aux_sql}'
							)";
					$res = pg_query($con, $sql);
					$sql = "
						INSERT INTO tbl_pedido_status (
							pedido,
							data,
							status,
							admin
						) VALUES (
							{$pedido},
							CURRENT_TIMESTAMP,
							17,
							{$login_admin}
						)";
					$res = pg_query($con, $sql);

					if (strlen(pg_last_error()) > 0) {
						$res = pg_query($con, "ROLLBACK TRANSACTION");
						$retorno = array("error" => utf8_encode("Erro ao recusar pedido"));
					} else {
						$res = pg_query($con, "COMMIT TRANSACTION");
						include_once S3CLASS;
	    				$s3 = new AmazonTC("pedido", $login_fabrica);

						$comprovante_pagamento = $s3->getObjectList("{$login_fabrica}_{$posto}_{$pedido}");
	    				$comprovante_pagamento = basename($comprovante_pagamento[0]);

						$s3->deleteObject($comprovante_pagamento);

						$retorno = array("success" => true);
					}
    			}			
			}
		}
	}

	exit(json_encode($retorno));
}

if ($_POST["btn_acao"] == "submit") {
	$pedido             = $_POST["pedido"];
	$data_inicial       = $_POST["data_inicial"];
	$data_final         = $_POST["data_final"];
	$codigo_posto       = $_POST["codigo_posto"];
	$descricao_posto    = $_POST["descricao_posto"];
	$status             = $_POST["status"];

	if (empty($status)) {
		$msg_erro["msg"][] = "Selecione o status";
		$msg_erro["campos"][] = "status";
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

	if (empty($pedido)) {
		if (!strlen($data_inicial) or !strlen($data_final)) {
			$msg_erro["msg"][]    = "Preencha os campos obrigatórios";
			$msg_erro["campos"][] = "data";
		} else {
			list($di, $mi, $yi) = explode("/", $data_inicial);
			list($df, $mf, $yf) = explode("/", $data_final);

			if (!checkdate($mi, $di, $yi) or !checkdate($mf, $df, $yf)) {
				$msg_erro["msg"][]    = "Data Inválida";
				$msg_erro["campos"][] = "data";
			} else {
				$aux_data_inicial = "{$yi}-{$mi}-{$di}";
				$aux_data_final   = "{$yf}-{$mf}-{$df}";

				if (strtotime($aux_data_final) < strtotime($aux_data_inicial)) {
					$msg_erro["msg"][]    = "Data Final não pode ser menor que a Data Inicial";
					$msg_erro["campos"][] = "data";
				}
			}
		}
	}

	if (empty($msg_erro["msg"])) {
		if (empty($pedido)) {
			$whereData = " AND tbl_pedido.data BETWEEN '{$aux_data_inicial} 00:00:00' AND '{$aux_data_final} 23:59:59' ";
		} else {
			$wherePedido = " AND tbl_pedido.pedido = {$pedido} ";
		}

		if (!empty($posto)) {
			$wherePosto = " AND tbl_pedido.posto = {$posto} ";
		}

		$sql = "SELECT
					tbl_pedido.pedido,
					TO_CHAR(tbl_pedido.data, 'DD/MM/YYYY') AS data,
					tbl_posto.nome AS posto,
					(SELECT SUM(tbl_pedido_item.qtde) FROM tbl_pedido_item WHERE tbl_pedido_item.pedido = tbl_pedido.pedido) AS qtde_pecas,
					tbl_pedido.status_pedido
				FROM tbl_pedido
				INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.fabrica = {$login_fabrica} AND tbl_posto_fabrica.posto = tbl_pedido.posto
				INNER JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
				INNER JOIN tbl_tipo_pedido ON tbl_tipo_pedido.tipo_pedido = tbl_pedido.tipo_pedido AND tbl_tipo_pedido.pedido_faturado IS TRUE
				WHERE tbl_pedido.status_pedido = {$status}
				{$whereData}
				{$wherePedido}
				{$wherePosto}
				ORDER BY tbl_pedido.data ASC";
		$resSubmit = pg_query($con, $sql);
	}
}

$layout_menu = "auditoria";
$title = "PEDIDOS COM INTERVENÇÃO DA FÁBRICA";
include 'cabecalho_new.php';


$plugins = array(
	"autocomplete",
	"datepicker",
	"mask"
);

include("plugin_loader.php");
?>

<script src='plugins/shadowbox/shadowbox.js'></script>
<link rel='stylesheet' type='text/css' href='plugins/shadowbox/shadowbox.css' />

<script>

$(function() {
	$.datepickerLoad(["data_final", "data_inicial"]);
	$.autocompleteLoad(["posto"]);
	Shadowbox.init();

	$("span[rel=lupa]").click(function () {
		$.lupa($(this));
	});

	$("button.btn_revisar").click(function() {
		var pedido = $(this).attr("rel");

		if (typeof pedido != "undefined") {
			Shadowbox.open({
				content: "peca_valor_frete.php?pedido="+pedido,
				player: "iframe",
				width: 800,
				height: 600
			});
		}
	});

	$("button.btn_cancelar").click(function() {
		var pedido = $(this).attr("rel");

		if (typeof pedido != "undefined") {

			Shadowbox.open({
				content: "pedido_peca_cancela.php?pedido="+pedido,
				player: "iframe",
				width: 800,
				height: 600
			});

		}
	});

	$("button.btn_aprovar").click(function() {
		var td = $(this).parent("td");
		var pedido = $(this).attr("rel");

		if (typeof pedido != "undefined") {
			$.ajax({
				async: true,
				url: "pedido_intervencao_new.php",
				type: "post",
				data: { ajax_aprova_pedido: true, pedido: pedido },
				beforeSend: function() {
					$(td).find("button").hide();
					$(td).prepend("<div class='alert alert-info' style='margin-bottom: 0px;'>Aprovando pedido, aguarde...</div>");
				}
			}).always(function(data) {
				data = $.parseJSON(data);

				$(td).find("div.alert-info").remove();

				if (data.error) {
					alert(data.error);
					$(td).find("button").show();
				} else {
					$(td).html("<div class='alert alert-success' style='margin-bottom: 0px;'>Pedido aprovado</div>");
				}
			});
		}
	});

	var td_btn_recusa;
	var pedido_recusa;

	$("button.btn_recusar").click(function() {
		td_btn_recusa = $(this).parent("td");
		pedido_recusa = $(this).attr("rel");

		Shadowbox.open({
			content: "\
			<div><br />\
				<b>Informe o motivo da recusa:</b><br />\
				<textarea id='motivo_recusa' style='width: 300px;'></textarea><br />\
				<button type='button' class='btn btn-danger btn-block btn_recusar_continua'>Recusar</button>\
			</div>\
			",
			player: "html",
			width: 320,
			height: 150,
			options: {
				enableKeys: false
			}
		});
	});

	$(document).on("click", "button.btn_recusar_continua", function() {
		var motivo = $.trim($("#motivo_recusa").val());

		if (typeof motivo == "undefined" || motivo.length == 0) {
			alert("Informe o motivo de recusa");
		} else {
			$.ajax({
				async: true,
				url: "pedido_intervencao_new.php",
				type: "post",
				data: { ajax_recusa_pedido: true, pedido: pedido_recusa, motivo: motivo },
				beforeSend: function() {
					$(td_btn_recusa).find("button").hide();
					$(td_btn_recusa).prepend("<div class='alert alert-info' style='margin-bottom: 0px;'>Recusando pedido, aguarde...</div>");
				}
			}).always(function(data) {
				data = $.parseJSON(data);

				$(td_btn_recusa).find("div.alert-info").remove();

				if (data.error) {
					alert(data.error);
					$(td_btn_recusa).find("button").show();
				} else {
					$(td_btn_recusa).html("<div class='alert alert-error' style='margin-bottom: 0px;'>Pedido recusado</div>");
					Shadowbox.close();
				}
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
    <div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>

<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>

<form method="post" class='form-search form-inline tc_formulario' >
	<div class='titulo_tabela '>Parâmetros de Pesquisa</div>
	<br/>

	<div class='row-fluid'>
		<div class='span2'></div>

		<div class='span10'>
			<div class='control-group <?=(in_array("pedido", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='pedido'>Número do Pedido</label>
				<div class='controls controls-row'>
					<div class='span12'>
						<input type="text" name="pedido" id="pedido" class='span2' value= "<?=$pedido?>">
						<span style="color: #B94A48;" >Caso informe o número de pedido os campos de datas não são obrigatórios</span>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class='row-fluid'>
		<div class='span2'></div>

		<div class='span4'>
			<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='data_inicial'>Data Inicial</label>
				<div class='controls controls-row'>
					<div class='span4'>
						<h5 class='asteristico'>*</h5>
						<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=$data_inicial?>">
					</div>
				</div>
			</div>
		</div>

		<div class='span4'>
			<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='data_final'>Data Final</label>
				<div class='controls controls-row'>
					<div class='span4'>
						<h5 class='asteristico'>*</h5>
						<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?=$data_final?>" >
					</div>
				</div>
			</div>
		</div>

		<div class='span2'></div>
	</div>

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

	<div class='row-fluid'>
		<div class='span2'></div>

		<div class='span8'>
			<div class='control-group <?=(in_array("status", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='codigo_posto'>Status</label>
				<div class='controls controls-row'>
					<div class='span7 input-append'>
						<h5 class='asteristico'>*</h5>

						<?php if ($login_fabrica == 138) { ?>
					    	<label class="radio inline">
						    	<input type="radio" name="status" value="18" <?=($status == 18) ? 'checked' : ''?> > Aguardando Revisão
						    </label>
						<?php } ?>

					    <label class="radio inline">
					    	<input type="radio" name="status" value="<?=($login_fabrica == 138) ? '20' : '18'?>" <?=(($login_fabrica == 138 && $status == 20) || ($login_fabrica != 138 && $status == 18)) ? 'checked' : ''?> > Aguardando aprovação
					    </label>
					</div>
				</div>
			</div>
		</div>
		
		<div class='span2'></div>
	</div>

	<p><br/>
		<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
		<input type='hidden' id="btn_click" name='btn_acao' value='' />
	</p><br/>
</form>

<?php
if (isset($resSubmit)) {
	$rows = pg_num_rows($resSubmit);

	if ($rows > 0) {
	?>
		<table class="table table-bordered">
			<thead>
				<tr class="titulo_coluna" >
					<th>Pedido</th>
					<th>Data</th>
					<th>Posto</th>
					<th>Qtde Peças</th>
					<th>Ações</th>
				</tr>
			</thead>
			<tbody>
				<?php
				for ($i = 0; $i < $rows; $i++) { 
					$pedido        = pg_fetch_result($resSubmit, $i, "pedido");
					$data          = pg_fetch_result($resSubmit, $i, "data");
					$posto         = pg_fetch_result($resSubmit, $i, "posto");
					$qtde_pecas    = pg_fetch_result($resSubmit, $i, "qtde_pecas");
					$status_pedido = pg_fetch_result($resSubmit, $i, "status_pedido");
					?>

					<tr id="pedido_<?=$pedido?>" >
						<td class="tac"><a href="pedido_admin_consulta.php?pedido=<?=$pedido?>" target="_blank" ><?=$pedido?></a></td>
						<td class="tac"><?=$data?></td>
						<td><?=$posto?></td>
						<td class="tac"><?=$qtde_pecas?></td>
						<td class="tac" nowrap>
							<?php if ($login_fabrica == 138) { ?>
                                <?php if (in_array($status_pedido, array(18, 20))): ?>
                                <a href="cadastro_pedido.php?pedido=<?php echo $pedido ?>" target="_blank">
                                    <button type="button" class="btn btn-small btn-primary" >Alterar Pedido</button>
                                </a>
                                <?php endif ?>
								<?php if ($status_pedido == 18) { ?>
									<button type="button" class="btn btn-small btn-success btn_revisar" rel="<?=$pedido?>" >Revisar Valores</button>
									<button type="button" class="btn btn-small btn-danger btn_cancelar" rel="<?=$pedido?>" >Cancelar Peças/Pedido</button>
								<?php } else { ?>
									<button type="button" class="btn btn-small btn-success btn_aprovar" rel="<?=$pedido?>" >Aprovar Pedido</button>
									<button type="button" class="btn btn-small btn-danger btn_recusar" rel="<?=$pedido?>" >Recusar Pedido</button>
								<?php } ?>
							<?php } else { ?>
								<button type="button" class="btn btn-small btn-success btn_aprovar" rel="<?=$pedido?>" >Aprovar Pedido</button>
								<button type="button" class="btn btn-small btn-danger btn_cancelar" rel="<?=$pedido?>" >Cancelar Pedido</button>
							<?php } ?>
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
		<div class="alert alert-error"><h4>Nenhum resultado encontrado</h4></div>
	<?php
	}
}

include "rodape.php";
?>
