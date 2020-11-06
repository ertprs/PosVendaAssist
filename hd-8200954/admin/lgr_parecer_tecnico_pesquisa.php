<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios = "auditoria";

include "autentica_admin.php";
include "funcoes.php";

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
				case "sem_analise":
					$havingStatus = "
						HAVING (
							SELECT COUNT(tbl_faturamento_lgr.peca)
							FROM tbl_faturamento_lgr
							WHERE tbl_faturamento_lgr.extrato = tbl_extrato.extrato
						) = 0
					";
					break;
				
				case "analise_parcial":
					$whereStatus = "
						AND tbl_faturamento.devolucao_concluida IS NOT TRUE
					";
					$havingStatus = "
						HAVING (
							SELECT COUNT(tbl_faturamento_lgr.peca)
							FROM tbl_faturamento_lgr
							WHERE tbl_faturamento_lgr.extrato = tbl_extrato.extrato
						) > 0
					";
					break;

				case "analise_completa":
					$whereStatus = "
						AND tbl_faturamento.devolucao_concluida IS TRUE
					";
					break;
			}
		}

		$sql = "SELECT
					DISTINCT tbl_faturamento.faturamento,
					tbl_faturamento.nota_fiscal,
					tbl_faturamento.total_nota AS valor_nota,
					TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY') AS data_emissao,
					TO_CHAR(tbl_faturamento.conferencia, 'DD/MM/YYYY') AS data_conferencia,
					tbl_faturamento.devolucao_concluida AS conferida,
					tbl_faturamento.emissao,
					tbl_posto.nome AS posto,
					tbl_extrato.extrato,
					SUM(tbl_faturamento_item.qtde_inspecionada) AS qtde_pecas_conferidas,
					(
						SELECT COUNT(tbl_faturamento_lgr.peca)
						FROM tbl_faturamento_lgr
						WHERE tbl_faturamento_lgr.extrato = tbl_extrato.extrato
					) AS qtde_pecas_analisadas
				FROM tbl_faturamento
				INNER JOIN tbl_posto ON tbl_posto.posto = tbl_faturamento.distribuidor
				INNER JOIN tbl_faturamento_item ON tbl_faturamento_item.faturamento = tbl_faturamento.faturamento
				INNER JOIN tbl_extrato ON tbl_extrato.extrato = tbl_faturamento_item.extrato_devolucao
				WHERE tbl_faturamento.fabrica = {$login_fabrica}
				AND tbl_faturamento.distribuidor IS NOT NULL
				AND tbl_faturamento.cancelada IS NULL
				AND tbl_faturamento.conferencia IS NOT NULL
				{$whereStatus}
				{$wherePosto}
				{$whereNotaDevolucao}
				GROUP BY
					tbl_faturamento.faturamento,
					tbl_faturamento.nota_fiscal,
					tbl_faturamento.total_nota,
					tbl_faturamento.emissao,
					tbl_faturamento.conferencia,
					tbl_faturamento.devolucao_concluida,
					tbl_faturamento.emissao,
					tbl_posto.nome,
					tbl_extrato.extrato
				{$havingStatus}
				ORDER BY tbl_faturamento.emissao ASC";
		$resSubmit = pg_query($con, $sql);
	}
}

$layout_menu = "auditoria";
$title       = "PARECER TÉCNICO";

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

tr.sem_analise > td, td.sem_analise {
	background-color: #D6D6D6;
}

tr.analise_parcial > td, td.analise_parcial {
	background-color: #FAFF73;
}

tr.analise_completa > td, td.analise_completa {
	background-color: #8DFF70;
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

	$("button[name=analise]").click(function() {
		var faturamento = $(this).data("faturamento");

		if (typeof faturamento != "undefined") {
			window.open("lgr_parecer_tecnico.php?faturamento="+faturamento);
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
						<label class="radio"><input type="radio" name="status" value="sem_analise" <?=($_POST["status"] == "sem_analise") ? "checked" : ""?> />Sem Análise</label>
						<label class="radio"><input type="radio" name="status" value="analise_parcial" <?=($_POST["status"] == "analise_parcial") ? "checked" : ""?> />Análise Parcial</label>
						<label class="radio"><input type="radio" name="status" value="analise_completa" <?=($_POST["status"] == "analise_completa") ? "checked" : ""?> />Análise Completa</label>
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
								<td class="cor_legenda sem_analise" >&nbsp;</td>
								<td class="titulo_legenda" >Sem Análise</td>

								<!--Conferida parcial-->
								<td class="cor_legenda analise_parcial" >&nbsp;</td>
								<td class="titulo_legenda" >Análise Parcial</td>

								<!--Conferida-->
								<td class="cor_legenda analise_completa" >&nbsp;</td>
								<td class="titulo_legenda" >Análise Completa</td>
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
					<th>Qtde Conferidas</th>
					<th>Qtde Analisadas</th>
					<th>Data Emissão</th>
					<th>Data Conferência</th>
					<?php
					if ($status != "analise_completa") {
					?>
						<th>Ação</th>
					<?php
					}
					?>
				</tr>
			</thead>
			<tbody>
				<?php
				while ($result = pg_fetch_object($resSubmit)) {
					if (empty($status)) {
						if (empty($result->qtde_pecas_analisadas)) {
							$rowClass = "sem_analise";
						} else if ($result->qtde_pecas_analisadas > 0 && ($result->qtde_pecas_analisadas < $result->qtde_pecas_conferidas) ) {
							$rowClass = "analise_parcial";
						} else if ($result->conferida == "t") {
							$rowClass = "analise_completa";
						}
					}
					?>
					<tr class="<?=$rowClass?>" >
						<td><?=$result->posto?></td>
						<td><?=$result->extrato?></td>
						<td><?=$result->nota_fiscal?></td>
						<td class="tar" ><?=number_format($result->valor_nota, 2, ",", ".")?></td>
						<td class="tar" ><?=$result->qtde_pecas_conferidas?></td>
						<td class="tar" ><?=$result->qtde_pecas_analisadas?></td>
						<td class="tar" ><?=$result->data_emissao?></td>
						<td class="tar" ><?=$result->data_conferencia?></td>
						<?php
						if ($status != "analise_completa") {
						?>
							<td style="vertical-align: middle;" nowrap >
								<?php
								if (($result->qtde_pecas_analisadas < $result->qtde_pecas_conferidas)) {
								?>
									<button type="button" data-faturamento="<?=$result->faturamento?>" name="analise" class="btn btn-primary btn-small">Análise</button>
								<?php
								}
								?>
							</td>
						<?php
						}
						?>
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
