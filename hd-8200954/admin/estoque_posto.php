<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios = "gerencia, call_center";

include "autentica_admin.php";
include "funcoes.php";

if ($_GET["ajax_atualiza_movimentacao"] == true) {
	$posto = $_GET["posto"];
	$peca  = $_GET["peca"];

	$sql = "SELECT 
				CASE WHEN tbl_estoque_posto_movimento.qtde_entrada IS NOT NULL THEN 'Entrada' ELSE 'Saída' END AS movimento,
				tbl_estoque_posto_movimento.qtde_entrada,
				tbl_estoque_posto_movimento.qtde_saida,
				TO_CHAR(tbl_estoque_posto_movimento.data_digitacao, 'DD/MM/YYYY HH:MM') AS data,
				tbl_admin.nome_completo AS admin
			FROM tbl_estoque_posto_movimento
			LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_estoque_posto_movimento.admin AND tbl_admin.fabrica = {$login_fabrica}
			WHERE tbl_estoque_posto_movimento.fabrica = {$login_fabrica}
			AND tbl_estoque_posto_movimento.posto = {$posto}
			AND tbl_estoque_posto_movimento.peca = {$peca}
			ORDER BY tbl_estoque_posto_movimento.data_digitacao DESC";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		$rows = pg_num_rows($res);

		$movimentacoes = array();

		for ($i = 0; $i < $rows; $i++) { 
			$movimento    = pg_fetch_result($res, $i, "movimento");
			$qtde_entrada = pg_fetch_result($res, $i, "qtde_entrada");
			$qtde_saida   = pg_fetch_result($res, $i, "qtde_saida");
			$data         = pg_fetch_result($res, $i, "data");
			$admin        = pg_fetch_result($res, $i, "admin");

			$class_movimento = ($movimento == "Entrada") ? "success" : "danger";
			$bg_movimento    = ($movimento == "Entrada") ? "#dff0d8" : "#f2dede";
			$qtde            = ($movimento == "Entrada") ? $qtde_entrada : $qtde_saida;

			$movimentacoes[$i] = array(
				"movimento" => utf8_encode($movimento),
				"data"      => utf8_encode($data),
				"qtde"      => $qtde,
				"admin"     => utf8_encode($admin),
				"class"     => $class_movimento,
				"bg"        => utf8_encode($bg_movimento)
			);
		}

		$retorno = array(
			"movimentacoes" => $movimentacoes
		);
	} else {
		$retorno = array("erro" => true);
	}

	exit(json_encode($retorno));
}

if ($_POST["ajax_lanca_movimentacao"] == true) {
	$posto     = $_POST["posto"];
	$peca      = $_POST["peca"];
	$qtde      = $_POST["qtde"];
	$movimento = $_POST["movimento"];

	/**
	 * Validações
	 */
	if (empty($posto)) {
		$retorno = array("erro" => utf8_encode("Posto Autorizado não informado"));
	} else if (empty($peca)) {
		$retorno = array("erro" => utf8_encode("Peça não informada"));
	} else if (empty($qtde) || $qtde < 0) {
		$retorno = array("erro" => utf8_encode("Quantidade não informada"));
	} else if (empty($movimento)) {
		$retorno = array("erro" => utf8_encode("Tipo de movimento não informado"));
	} else {
		$sql_posto = "SELECT posto FROM tbl_posto_fabrica WHERE fabrica = {$login_fabrica} AND posto = {$posto}";
		$res_posto = pg_query($con, $sql_posto);

		$sql_peca = "SELECT peca FROM tbl_peca WHERE fabrica = {$login_fabrica} AND peca = {$peca}";
		$res_peca = pg_query($con, $sql_peca);

		if (!pg_num_rows($res_posto)) {
			$retorno = array("erro" => utf8_encode("Posto Autorizado não encontrado"));
		} else if (!pg_num_rows($res_peca)) {
			$retorno = array("erro" => utf8_encode("Peça não encontrada"));
		}
	}
	/**
	 * Fim validação
	 */

	if (!isset($retorno["erro"])) {
		$sql = "SELECT qtde FROM tbl_estoque_posto WHERE fabrica = {$login_fabrica} AND posto = {$posto} AND peca = {$peca}";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			$estoque = pg_fetch_result($res, 0, "qtde");
		} else {
			$sql = "INSERT INTO tbl_estoque_posto
					(fabrica, posto, peca, qtde)
					VALUES
					({$login_fabrica}, {$posto}, {$peca}, 0)";
			$res = pg_query($con, $sql);

			$estoque = 0;
		}

		if ($movimento == "saida") {
			$estoque -= $qtde;
		} else {
			$estoque += $qtde;
		}

		if ($estoque < 0) {
			$retorno = array("erro" => utf8_encode("Quantidade lançada maior que a disponível"));
		} else {
			$campo_movimento = ($movimento == "entrada") ? "qtde_entrada" : "qtde_saida";

			$sql = "INSERT INTO tbl_estoque_posto_movimento
					(fabrica, posto, peca, {$campo_movimento}, admin)
					VALUES
					({$login_fabrica}, {$posto}, {$peca}, {$qtde}, {$login_admin})";
			$res = pg_query($con, $sql);

			if (strlen(pg_last_error()) > 0) {
				$retorno = array("erro" => utf8_encode("Erro ao lançar movimentação"));
			} else {
				$sql = "UPDATE tbl_estoque_posto
						SET qtde = {$estoque}
						WHERE fabrica = {$login_fabrica}
						AND posto = {$posto}
						AND peca = {$peca}";
				$res = pg_query($con, $sql);

				if (strlen(pg_last_error()) > 0) {
					$retorno = array("erro" => utf8_encode("Erro ao lançar movimentação"));
				} else {
					$retorno = array("ok" => true, "estoque" => $estoque);
				}
			}
		}
	}

	exit(json_encode($retorno));
}

if ($_GET["btn_acao"] == "submit") {
	$codigo_posto    = $_GET["codigo_posto"];
	$descricao_posto = $_GET["descricao_posto"];
	$peca_referencia = $_GET["peca_referencia"];
	$peca_descricao  = $_GET["peca_descricao"];

	if (strlen($codigo_posto) > 0 && strlen($descricao_posto) > 0) {
		$sql = "SELECT tbl_posto_fabrica.posto
				FROM tbl_posto
				INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
				WHERE (
					UPPER(tbl_posto_fabrica.codigo_posto) = UPPER('{$codigo_posto}')
					AND
					TO_ASCII(UPPER(tbl_posto.nome), 'LATIN-9') = TO_ASCII(UPPER('{$descricao_posto}'), 'LATIN-9')
				)";
		$res = pg_query($con, $sql);

		if (!pg_num_rows($res)) {
			$msg_erro["msg"][]    = "Posto Autorizado não encontrado";
			$msg_erro["campos"][] = "posto";
		} else {
			$posto = pg_fetch_result($res, 0, "posto");
		}
	} else {
		$msg_erro["msg"][]    = "Informe o Posto Autorizado";
		$msg_erro["campos"][] = "posto";
	}

	if (strlen($peca_referencia) > 0 && strlen($peca_descricao) > 0) {
		$sql = "SELECT tbl_peca.peca
				FROM tbl_peca
				WHERE (
					UPPER(tbl_peca.referencia) = UPPER('{$peca_referencia}')
					AND
					TO_ASCII(UPPER(tbl_peca.descricao), 'LATIN-9') = TO_ASCII(UPPER('{$peca_descricao}'), 'LATIN-9')
				)";
		$res = pg_query($con, $sql);

		if (!pg_num_rows($res)) {
			$msg_erro["msg"][]    = "Peça não encontrada";
			$msg_erro["campos"][] = "peca";
		} else {
			$peca = pg_fetch_result($res, 0, "peca");
		}
	} else {
		unset($peca_referencia, $peca_descricao);
	}
}

$layout_menu = "callcenter";
$title = "ESTOQUE DO POSTO AUTORIZADO";

include "cabecalho_new.php";

$plugins = array(
	"autocomplete",
	"shadowbox"
);

include "plugin_loader.php";

?>

<script>

$(function() {
	Shadowbox.init();
	$.autocompleteLoad(["posto", "peca"]);

	$("span[rel=lupa]").click(function() {
		$.lupa($(this));
	});

	$("button.btn_lanca_movimentacao").click(function() {
		var posto     = $("input[name=posto]").val();
		var peca      = $("input[name=peca]").val();
		var qtde      = $("input[name=quantidade]").val();
		var movimento = $(this).attr("rel");

		if (typeof qtde != "undefined" && qtde > 0) {
			$.ajax({
				url: "estoque_posto.php",
				type: "post",
				data: {
					ajax_lanca_movimentacao: true,
					posto: posto,
					peca: peca,
					qtde: qtde,
					movimento: movimento
				},
				beforeSend: function() {
					$("button.btn_lanca_movimentacao").hide();
					$("button.btn_lanca_movimentacao").first().before("<div class='alert alert-info'><h4>Lançando movimentação, aguarde...</h4></div>");
				}
			}).always(function(data) {
				data = $.parseJSON(data);

				if (data.erro) {
					alert(data.erro);
				} else {
					$("#estoque_atual").text(data.estoque);
					$("input[name=quantidade]").val("");

					var bg_movimento = (movimento == "entrada") ? "#DFF0D8" : "#F2DEDE";

					$("#estoque_atual").css({ "background-color": bg_movimento });

					setTimeout(function() {
						$("#estoque_atual").css({ "background-color": "#FFFFFF" });
					}, 3000);

					atualizaMovimentacao(posto, peca);
				}

				$("button.btn_lanca_movimentacao").first().prev("div.alert-info").remove();
				$("button.btn_lanca_movimentacao").show();
			});
		} else {
			alert("Informe a quantidade a ser lançada");
		}
	});
});

function retorna_posto(retorno) {
    $("#codigo_posto").val(retorno.codigo);
	$("#descricao_posto").val(retorno.nome);
}

function retorna_peca(retorno) {
	$("#peca_referencia").val(retorno.referencia);
	$("#peca_descricao").val(retorno.descricao);
}

function atualizaMovimentacao(posto, peca) {
	$.ajax({
		url: "estoque_posto.php",
		type: "get",
		data: { ajax_atualiza_movimentacao: true, posto: posto, peca: peca },
		beforeSend: function() {
			$("#estoque_movimentacao > tbody").html("<tr><th colspan='4'><div class='alert alert-info'><h4>Atualizando movimentação</h4></div></th></tr>");
		}
	}).always(function(data) {
		data = $.parseJSON(data);

		if (data.erro) {
			$("#estoque_movimentacao > tbody").html("<tr><th colspan='4'><div class='alert alert-danger'><h4>Erro ao atualizar movimentação</h4></div></th></tr>");
		} else {
			$("#estoque_movimentacao > tbody").html("");

			$.each(data.movimentacoes, function(key, movimentacao) {
				$("#estoque_movimentacao > tbody").append("\
					<tr>\
						<td class='alert alert-"+movimentacao.class+" tac' style='background-color: "+movimentacao.bg+";' >\
							<b>"+movimentacao.movimento+"</b>\
						</td>\
						<td class='tac' >"+movimentacao.data+"</td>\
						<td class='tac' >"+movimentacao.qtde+"</td>\
						<td>"+movimentacao.admin+"</td>\
					</tr>\
				");
			});
		}
	});
}

</script>

<?php if (count($msg_erro["msg"]) > 0) { ?>
    <div class="alert alert-error" >
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php } ?>

<div class="row" >
	<b class="obrigatorio pull-right" >  * Campos obrigatórios </b>
</div>

<form name="frm_relatorio_oss_em_aberto" method="get" class="form-search form-inline tc_formulario" >
	<div class="titulo_tabela" >Parâmetros de Pesquisa</div>
	<br />

	<div class="row-fluid" >
		<div class="span2" ></div>

		<div class="span4" >
			<div class="control-group <?=(in_array('posto', $msg_erro['campos'])) ? 'error' : ''?>" >
				<label class="control-label" for="codigo_posto" >Código Posto</label>

				<div class="controls controls-row" >
					<div class="span7 input-append" >
						<h5 class='asteristico'>*</h5>
						<input type="text" name="codigo_posto" id="codigo_posto" class="span12" value="<? echo $codigo_posto ?>" />
						<span class="add-on" rel="lupa" ><i class="icon-search" ></i></span>
						<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
					</div>
				</div>
			</div>
		</div>

		<div class="span4" >
			<div class="control-group <?=(in_array('posto', $msg_erro['campos'])) ? 'error' : ''?>" >
				<label class="control-label" for="descricao_posto" >Nome Posto</label>

				<div class="controls controls-row" >
					<div class="span12 input-append" >
						<h5 class='asteristico'>*</h5>
						<input type="text" name="descricao_posto" id="descricao_posto" class="span12" value="<? echo $descricao_posto ?>" />
						<span class="add-on" rel="lupa" ><i class="icon-search" ></i></span>
						<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
					</div>
				</div>
			</div>
		</div>

		<div class="span2" ></div>
	</div>

	<div class="row-fluid" >
		<div class="span2" ></div>

		<div class="span4" >
			<div class="control-group <?=(in_array('peca', $msg_erro['campos'])) ? 'error' : ''?>" >
				<label class="control-label" for="peca_referencia" >Referência Peça</label>

				<div class="controls controls-row" >
					<div class="span7 input-append" >
						<input type="text" name="peca_referencia" id="peca_referencia" class="span12" value="<? echo $peca_referencia ?>" />
						<span class="add-on" rel="lupa" ><i class="icon-search" ></i></span>
						<input type="hidden" name="lupa_config" tipo="peca" parametro="referencia" />
					</div>
				</div>
			</div>
		</div>

		<div class="span4" >
			<div class="control-group <?=(in_array('peca', $msg_erro['campos'])) ? 'error' : ''?>" >
				<label class="control-label" for="peca_descricao" >Descrição Peça</label>

				<div class="controls controls-row" >
					<div class="span12 input-append" >
						<input type="text" name="peca_descricao" id="peca_descricao" class="span12" value="<? echo $peca_descricao ?>" />
						<span class="add-on" rel="lupa" ><i class="icon-search" ></i></span>
						<input type="hidden" name="lupa_config" tipo="peca" parametro="descricao" />
					</div>
				</div>
			</div>
		</div>

		<div class="span2" ></div>
	</div>

	<p>
		<br/>
		<button class="btn" id="btn_acao" type="button" onclick="submitForm($(this).parents('form'));" >Pesquisar</button>
		<input type="hidden" id="btn_click" name="btn_acao" />
	</p>

	<br/>
</form>

<?php

if ($_GET["btn_acao"] == "submit" && !count($msg_erro["msg"])) {
	$sql_posto = "SELECT tbl_posto_fabrica.posto AS posto_id, tbl_posto.nome AS posto_nome
				  FROM tbl_posto_fabrica
				  INNER JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
				  WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
				  AND tbl_posto_fabrica.posto = {$posto}";
	$res_posto = pg_query($con, $sql_posto);

	$posto = array(
		"id"   => pg_fetch_result($res_posto, 0, "posto_id"),
		"nome" => pg_fetch_result($res_posto, 0, "posto_nome")
	);

	if (!empty($peca)) {
		$sql_peca = "SELECT tbl_peca.peca AS peca_id, tbl_peca.referencia AS peca_referencia, tbl_peca.descricao AS peca_descricao
					 FROM tbl_peca
					 WHERE tbl_peca.fabrica = {$login_fabrica}
					 AND tbl_peca.peca = {$peca}";
		$res_peca = pg_query($con, $sql_peca);

		$peca = array(
			"id"         => pg_fetch_result($res_peca, 0, "peca_id"),
			"referencia" => pg_fetch_result($res_peca, 0, "peca_referencia"),
			"descricao"  => pg_fetch_result($res_peca, 0, "peca_descricao")
		);

		$sql_estoque = "SELECT qtde 
						FROM tbl_estoque_posto 
						WHERE fabrica = {$login_fabrica} 
						AND posto = {$posto['id']} 
						AND peca = {$peca['id']}";
		$res_estoque = pg_query($con, $sql_estoque);

		if (pg_num_rows($res_estoque) > 0) {
			$estoque_atual = pg_fetch_result($res_estoque, 0, "qtde");
		} else {
			$estoque_atual = 0;
		}
		?>

		<table class="table table-striped table-bordered" >
			<thead>
				<tr>
					<th class="titulo_coluna" >Posto Autorizado</th>
					<th class="tal" ><?=$posto["nome"]?></th>
				</tr>
				<tr>
					<th class="titulo_coluna" >Peça</th>
					<th class="tal" ><?="{$peca['referencia']} - {$peca['descricao']}"?></th>
				</tr>
				<tr>
					<th class="titulo_coluna" >Estoque</th>
					<th class="tal" id="estoque_atual" ><?=$estoque_atual?></th>
				</tr>
			</thead>
		</table>

		<div class="titulo_tabela" >Lançar Movimentação</div>
		<div class="row-fluid" >
			<div class="span12" >
				<div class="control-group tac" >
					<label class="control-label" for="quantidade" >Quantidade</label>

					<div class="controls controls-row" >
						<div class="span12 tac" >
							<input type="hidden" name="posto" value="<?=$posto['id']?>" />
							<input type="hidden" name="peca" value="<?=$peca['id']?>" />
							<input type="text" name="quantidade" id="quantidade" class="span1" />
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row-fluid" >
			<div class="span12" >
				<div class="control-group" >
					<div class="controls controls-row" >
						<div class="span12 tac" >
							<button type="button" rel="entrada" class="btn btn-success btn_lanca_movimentacao" ><i class="icon-plus icon-white" ></i> Lançar Entrada</button>
							<button type="button" rel="saida" class="btn btn-danger btn_lanca_movimentacao" ><i class="icon-minus icon-white" ></i> Lançar Saída</button>
						</div>
					</div>
				</div>
			</div>
		</div>

		<br />

		<table id="estoque_movimentacao" class="table table-striped table-bordered" style="table-layout: fixed;" >
			<thead>
				<tr class="titulo_coluna" >
					<th colspan="4" >Movimentação</th>
				</tr>
				<tr class="titulo_coluna" >
					<th>Saida/Entrada</th>
					<th>Data</th>
					<th>Quantidade</th>
					<th>Admin</th>
				</tr>
			</thead>
			<tbody>
				<?php

				$sql = "SELECT 
							CASE WHEN tbl_estoque_posto_movimento.qtde_entrada IS NOT NULL THEN 'Entrada' ELSE 'Saída' END AS movimento,
							tbl_estoque_posto_movimento.qtde_entrada,
							tbl_estoque_posto_movimento.qtde_saida,
							TO_CHAR(tbl_estoque_posto_movimento.data_digitacao, 'DD/MM/YYYY HH:MM') AS data,
							tbl_admin.nome_completo AS admin
						FROM tbl_estoque_posto_movimento
						LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_estoque_posto_movimento.admin AND tbl_admin.fabrica = {$login_fabrica}
						WHERE tbl_estoque_posto_movimento.fabrica = {$login_fabrica}
						AND tbl_estoque_posto_movimento.posto = {$posto['id']}
						AND tbl_estoque_posto_movimento.peca = {$peca['id']}
						ORDER BY tbl_estoque_posto_movimento.data_digitacao DESC";
				$res = pg_query($con, $sql);

				if (pg_num_rows($res) > 0) {
					$rows = pg_num_rows($res);

					for ($i = 0; $i < $rows; $i++) { 
						$movimento    = pg_fetch_result($res, $i, "movimento");
						$qtde_entrada = pg_fetch_result($res, $i, "qtde_entrada");
						$qtde_saida   = pg_fetch_result($res, $i, "qtde_saida");
						$data         = pg_fetch_result($res, $i, "data");
						$admin        = pg_fetch_result($res, $i, "admin");

						$class_movimento = ($movimento == "Entrada") ? "success" : "danger";
						$bg_movimento = ($movimento == "Entrada") ? "#dff0d8" : "#f2dede";
						?>

						<tr>
							<td class="alert alert-<?=$class_movimento?> tac" style="background-color: <?=$bg_movimento?>;" >
								<b><?=$movimento?></b>
							</td>
							<td class="tac" ><?=$data?></td>
							<td class="tac" >
								<?php

								echo $qtde = ($movimento == "Entrada") ? $qtde_entrada : $qtde_saida;

								?>
							</td>
							<td><?=$admin?></td>
						</tr>

					<?php
					}
				} else {
				?>
					<tr>
						<th colspan="4" >
							<div class="alert alert-danger" style="margin-bottom: 0px;" >
								<h4>Nenhuma movimentação registrada</h4>
							</div>
						</th>
					</tr>
				<?php
				}
				?>
			</tbody>
		</table>
	<?php
	} else {
		$sql = "SELECT 
					tbl_peca.referencia AS peca_referencia, 
					tbl_peca.descricao AS peca_descricao,
					tbl_estoque_posto.qtde
				FROM tbl_estoque_posto
				INNER JOIN tbl_peca ON tbl_peca.peca = tbl_estoque_posto.peca AND tbl_peca.fabrica = {$login_fabrica}
				WHERE tbl_estoque_posto.fabrica = {$login_fabrica}
				AND tbl_estoque_posto.posto = {$posto['id']}";
		$res = pg_query($con, $sql);
		?>

		<table id="estoque_movimentacao" class="table table-striped table-bordered" >
			<thead>
				<tr class="titulo_coluna" >
					<th colspan="2" ><?=$posto['nome']?></th>
				</tr>
				<tr class="titulo_coluna" >
					<th>Peça</th>
					<th>Estoque</th>
				</tr>
			</thead>
			<tbody>
				<?php
				if (pg_num_rows($res) > 0) {
					$rows = pg_num_rows($res);

					for ($i = 0; $i < $rows; $i++) { 
						$peca_referencia = pg_fetch_result($res, $i, "peca_referencia");
						$peca_descricao  = pg_fetch_result($res, $i, "peca_descricao");
						$qtde            = pg_fetch_result($res, $i, "qtde");

						$url = "estoque_posto.php?btn_acao=submit&codigo_posto={$codigo_posto}&descricao_posto={$descricao_posto}&peca_referencia={$peca_referencia}&peca_descricao={$peca_descricao}";
						?>

						<tr>
							<td><a href="<?=$url?>" ><?="{$peca_referencia} - {$peca_descricao}"?></a></td>
							<td class="tac" ><?=$qtde?></td>
						</tr>

					<?php
					}
				} else {
				?>
					<tr>
						<th colspan="2" >
							<div class="alert alert-danger" style="margin-bottom: 0px;" >
								<h4>Nenhuma peça com estoque registrada</h4>
							</div>
						</th>
					</tr>
				<?php
				}
				?>
			</tbody>
		</table>
	<?php
	}
}

include "rodape.php";

?>