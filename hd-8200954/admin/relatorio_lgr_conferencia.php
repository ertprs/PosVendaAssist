<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios = "auditoria";

include "autentica_admin.php";
include "funcoes.php";

if ($_POST["btn_acao"] == "submit") {
	$data_inicial       = $_POST["data_inicial"];
	$data_final         = $_POST["data_final"];
	$codigo_posto       = trim($_POST["codigo_posto"]);
	$descricao_posto    = trim($_POST["descricao_posto"]);
	$peca_referencia    = trim($_POST["peca_referencia"]);
	$peca_descricao     = trim($_POST["peca_descricao"]);
	$estado             = strtoupper($_POST["estado"]);
	$status_analise_peca       = $_POST["status_analise_peca"];
	$os                 = trim($_POST["os"]);
	$defeito_constatado = $_POST["defeito_constatado"];
	$procedencia        = $_POST["procedencia"];

	if ((!strlen($data_inicial) or !strlen($data_final)) && empty($os)) {
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
				$msg_erro["msg"][]    = "Data final não pode ser menor que a Data inicial";
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

	if (strlen($peca_referencia) > 0 or strlen($peca_descricao) > 0){
		$sql = "SELECT peca
				FROM tbl_peca
				WHERE fabrica = {$login_fabrica}
				AND (
                    (UPPER(referencia) = UPPER('{$peca_referencia}'))
                    OR
                    (UPPER(descricao) = UPPER('{$peca_descricao}'))
                )";
		$res = pg_query($con ,$sql);

		if (!pg_num_rows($res)) {
			$msg_erro["msg"][]    = "Peça não encontrada";
			$msg_erro["campos"][] = "peca";
		} else {
			$peca = pg_fetch_result($res, 0, "peca");
		}
	}

	if (!count($msg_erro["msg"])) {
		if (!empty($posto)) {
			$wherePosto = "AND tbl_posto_fabrica.posto = {$posto}";
		}

		if (!empty($peca)) {
			$wherePeca = "AND tbl_faturamento_lgr.peca = {$peca}";
		}

		if (!empty($estado)) {
			$whereEstado = "AND UPPER(tbl_posto_fabrica.contato_estado) = '{$estado}'";
		}

		if (!empty($status_analise_peca)) {
			$whereAnalisePeca = "AND tbl_faturamento_lgr.status_analise_peca = {$status_analise_peca}";
		}

		if (!empty($defeito_constatado)) {
			$whereDefeitoConstatado = "AND tbl_os_produto.defeito_constatado = {$defeito_constatado}";
		}

		if (!empty($os)) {
			$whereOs = "AND tbl_os.sua_os = '{$os}'";
		} else {
			$whereData = "AND tbl_faturamento_lgr.data_input BETWEEN '{$aux_data_inicial} 00:00:00' AND '{$aux_data_final} 23:59:59'";
		}

		switch ($procedencia) {
			case "true":
				$whereProcedencia = "AND tbl_faturamento_lgr.procedencia IS TRUE";
				break;
			
			case "false":
				$whereProcedencia = "AND tbl_faturamento_lgr.procedencia IS FALSE";
				break;
		}

		$sql = "SELECT
					tbl_os.os,
					tbl_os.sua_os,
					tbl_defeito_constatado.descricao AS defeito_constatado,
					(tbl_peca.referencia || ' - ' || tbl_peca.descricao) AS peca,
					TO_CHAR(tbl_faturamento.conferencia, 'DD/MM/YYYY') AS conferencia,
					tbl_faturamento.nota_fiscal,
					tbl_faturamento_lgr.procedencia,
					tbl_status_analise_peca.descricao AS status_analise_peca,
					TO_CHAR(tbl_faturamento_lgr.data_input, 'DD/MM/YYYY') AS data_analise,
					tbl_faturamento_lgr.observacao
				FROM tbl_faturamento_lgr
				INNER JOIN tbl_peca ON tbl_peca.peca = tbl_faturamento_lgr.peca AND tbl_peca.fabrica = {$login_fabrica}
				INNER JOIN tbl_status_analise_peca ON tbl_status_analise_peca.status_analise_peca = tbl_faturamento_lgr.status_analise_peca AND tbl_status_analise_peca.fabrica = {$login_fabrica}
				INNER JOIN tbl_faturamento_item ON tbl_faturamento_item.faturamento_item = tbl_faturamento_lgr.faturamento_item
				INNER JOIN tbl_faturamento ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento AND tbl_faturamento.fabrica = {$login_fabrica}
				LEFT JOIN tbl_faturamento AS tbl_faturamento_origem ON tbl_faturamento_origem.nota_fiscal = tbl_faturamento_item.nota_fiscal_origem AND tbl_faturamento_origem.fabrica = {$login_fabrica} AND tbl_faturamento_origem.posto = tbl_faturamento.distribuidor
				LEFT JOIN tbl_faturamento_item AS tbl_faturamento_item_origem ON tbl_faturamento_item_origem.faturamento = tbl_faturamento_origem.faturamento AND tbl_faturamento_item_origem.peca = tbl_faturamento_item.peca
				LEFT JOIN tbl_os_item ON tbl_os_item.os_item = tbl_faturamento_item.os_item
				LEFT JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
				LEFT JOIN tbl_os ON tbl_os.os = tbl_os_produto.os AND tbl_os.fabrica = {$login_fabrica}
				LEFT JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_os.defeito_constatado AND tbl_defeito_constatado.fabrica = {$login_fabrica}
				LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
				WHERE tbl_faturamento_lgr.fabrica = {$login_fabrica}
				{$wherePosto}
				{$wherePeca}
				{$whereEstado}
				{$whereAnalisePeca}
				{$whereDefeitoConstatado}
				{$whereOs}
				{$whereData}
				{$whereProcedencia}
				ORDER BY tbl_faturamento_lgr.data_input DESC";
		$resSubmit = pg_query($con, $sql);
		
		if ($_POST["gerar_excel"]) {
			if (pg_num_rows($resSubmit) > 0) {
				$data = date("d-m-Y-H:i");

				$fileName = "relatorio_lgr_conferencia-{$data}.xls";

				$file = fopen("/tmp/{$fileName}", "w");
				$thead = "
					<table border='1'>
						<thead>
							<tr>
								<th colspan='9' bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;' >
									RELATÓRIO DE CONFERÊNCIA DAS NOTAS DE DEVOLUÇÕES 
								</th>
							</tr>
							<tr>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Ordem de Serviço</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Defeito Constatado</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Peça</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Nota Fiscal</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Procede</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Conferencia/th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Análise</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data Análise</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Observação</th>
							</tr>
						</thead>
						<tbody>
				";
				fwrite($file, $thead);

				for ($i = 0; $i < pg_num_rows($resSubmit); $i++) {
					$sua_os              = pg_fetch_result($resSubmit, $i, 'sua_os');
					$defeito_constatado  = pg_fetch_result($resSubmit, $i, 'defeito_constatado');
					$peca                = pg_fetch_result($resSubmit, $i, 'peca');
					$nota_fiscal         = pg_fetch_result($resSubmit, $i, 'nota_fiscal');
					$procedencia         = pg_fetch_result($resSubmit, $i, 'procedencia');
					$status_analise_peca        = pg_fetch_result($resSubmit, $i, 'status_analise_peca');
					$conferencia         = pg_fetch_result($resSubmit, $i, "conferencia");
					$data_analise        = pg_fetch_result($resSubmit, $i, 'data_analise');
					$observacao          = pg_fetch_result($resSubmit, $i, 'observacao');

					$body .="
							<tr>
								<td nowrap valign='top'>{$sua_os}</td>
								<td nowrap valign='top'>{$defeito_constatado}</td>
								<td nowrap valign='top'>{$peca}</td>
								<td nowrap valign='top'>{$nota_fiscal}</td>
								<td nowrap valign='top'>".(($procedencia == "t") ? "Sim" : "Não")."</td>
								<td nowrap valign='top'>{$status_analise_peca}</td>
								<td nowrap valign='top'>{$conferencia}</td>
								<td nowrap valign='top'>{$data_analise}</td>
								<td nowrap valign='top'>{$observacao}</td>
							</tr>";
				}

				fwrite($file, $body);
				fwrite($file, "
							<tr>
								<th colspan='9' bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;' >Total de ".pg_num_rows($resSubmit)." registros</th>
							</tr>
						</tbody>
					</table>
				");

				fclose($file);

				if (file_exists("/tmp/{$fileName}")) {
					system("mv /tmp/{$fileName} xls/{$fileName}");

					echo "xls/{$fileName}";
				}
			}

			exit;
		}
	}
}

$layout_menu = "auditoria";
$title       = "RELATÓRIO DE PARECER TÉCNICO DAS NOTAS DE DEVOLUÇÕES";

include "cabecalho_new.php";

$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable"
);

include "plugin_loader.php";

?>

<script src="js/novo_highcharts.js" ></script>
<script src="js/modules/exporting.js" ></script>
<script>

$(function() {
	$.datepickerLoad(["data_final", "data_inicial"]);
	$.autocompleteLoad(["posto", "peca"]);
	Shadowbox.init();

	$("span[rel=lupa]").click(function () {
		$.lupa($(this));
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

function montaGrafico(data) {
	data = JSON.parse(data);

	var series = [];

	$.each(data, function(key, value) {
		series.push({name: key, y: value});
	});

	$("#grafico").highcharts({
		chart: {
			plotBackgroundColor: null,
			plotBordeWidth: null,
			plotShadow: false
		},
		title: { text: "Procede" },
		tooltip: {
			enabled: true,
			headerFormat: "",
			pointFormat: "{point.name}: {point.y}"
		},
		plotOptions: {
			pie: {
				allowPointSelect: true,
				cursor: "pointer",
				dataLabels: {
					enabled: true,
					format: "<b>{point.name}</b>: {point.percentage:.2f}%",
					style: {
						color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || "black"
					}
				}
			}
		},
		series: [{
			type: "pie",
			data: series
		}]
	});
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
				<label class="control-label" for="data_inicial" >Data inicial</label>
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
				<label class="control-label" for="data_final" >Data final</label>
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
		<div class="span4">
			<div class="control-group <?=(in_array("peca", $msg_erro["campos"])) ? "error" : ""?>" >
				<label class="control-label" for="peca_referencia" >Referência da Peça</label>
				<div class="controls controls-row" >
					<div class="span12 input-append" >
						<input type="text" name="peca_referencia" id="peca_referencia" class="span8" maxlength="20" value="<?=$_POST['peca_referencia']?>" />
						<span class="add-on" rel="lupa" ><i class="icon-search" ></i></span>
						<input type="hidden" name="lupa_config" tipo="peca" parametro="referencia" />
					</div>
				</div>
			</div>
		</div>
		<div class="span4">
			<div class="control-group <?=(in_array("peca", $msg_erro["campos"])) ? "error" : ""?>" >
				<label class="control-label" for="peca_descricao" >Descrição da Peça</label>
				<div class="controls controls-row" >
					<div class="span12 input-append" >
						<input type="text" name="peca_descricao" id="peca_descricao" class="span12" maxlength="150" value="<?=$_POST['peca_descricao']?>" />
						<span class="add-on" rel="lupa" ><i class="icon-search" ></i></span>
						<input type="hidden" name="lupa_config" tipo="peca" parametro="descricao" />
					</div>
				</div>
			</div>
		</div>
		<div class="span2"></div>
	</div>

	<div class="row-fluid">
		<div class="span2"></div>
		<div class="span4">
			<div class="control-group <?=(in_array("estado", $msg_erro["campos"])) ? "error" : ""?>" >
				<label class="control-label" for="estado" >Estado</label>
				<div class="controls controls-row" >
					<div class="span12" >
						<select name="estado" id="estado" >
							<option></option>
							<?php
							foreach ($array_estados() as $sigla => $nome) {
								$selected = ($_POST["estado"] == $sigla) ? "selected" : "";

								echo "<option value='{$sigla}' {$selected} >{$nome}</option>";
							}
							?>
						</select>
					</div>
				</div>
			</div>
		</div>
		<div class="span4">
			<div class="control-group <?=(in_array("status_analise_peca", $msg_erro["campos"])) ? "error" : ""?>" >
				<label class="control-label" for="status_analise_peca" >Análise</label>
				<div class="controls controls-row" >
					<div class="span12" >
						<select name="status_analise_peca" id="status_analise_peca" >
							<option></option>
							<?php
							$sql = "SELECT status_analise_peca, descricao
									FROM tbl_status_analise_peca
									WHERE fabrica = {$login_fabrica}";
							$res = pg_query($con, $sql);

							while ($resAnalisePeca = pg_fetch_object($res)) {
								$selected = ($_POST["status_analise_peca"] == $resAnalisePeca->status_analise_peca) ? "selected" : "";

								echo "<option value='{$resAnalisePeca->status_analise_peca}' {$selected} >{$resAnalisePeca->descricao}</option>";
							}
							?>
						</select>
					</div>
				</div>
			</div>
		</div>
		<div class="span2"></div>
	</div>

	<div class="row-fluid">
		<div class="span2"></div>
		<div class="span4">
			<div class="control-group <?=(in_array("defeito_constatado", $msg_erro["campos"])) ? "error" : ""?>" >
				<label class="control-label" for="defeito_constatado" >Defeito Constatado</label>
				<div class="controls controls-row" >
					<div class="span12" >
						<select name="defeito_constatado" id="defeito_constatado" >
							<option></option>
							<?php
							$sql = "SELECT defeito_constatado, descricao
									FROM tbl_defeito_constatado
									WHERE fabrica = {$login_fabrica}";
							$res = pg_query($con, $sql);

							while ($resDefeitoConstatado = pg_fetch_object($res)) {
								$selected = ($_POST["defeito_constatado"] == $resDefeitoConstatado->defeito_constatado) ? "selected" : "";

								echo "<option value='{$resDefeitoConstatado->defeito_constatado}' {$selected} >{$resDefeitoConstatado->descricao}</option>";
							}
							?>
						</select>
					</div>
				</div>
			</div>
		</div>
		<div class="span4">
			<div class="control-group <?=(in_array("os", $msg_erro["campos"])) ? "error" : ""?>" >
				<label class="control-label" for="os" >Ordem de Serviço</label>
				<div class="controls controls-row" >
					<div class="span12" >
						<input type="text" name="os" id="os" value="<?=$_POST['os']?>" maxlength="20" class="span8" />
					</div>
				</div>
			</div>
		</div>
		<div class="span2"></div>
	</div>

	<div class="row-fluid">
		<div class="span2"></div>
		<div class="span4">
			<div class="control-group <?=(in_array("procedencia", $msg_erro["campos"])) ? "error" : ""?>" >
				<label class="control-label" for="procedencia" >Procede</label>
				<div class="controls controls-row" >
					<div class="span12" >
						<label class="radio" ><input type="radio" name="procedencia" value="" checked />Ambos</label>
						<label class="radio" ><input type="radio" name="procedencia" value="true" <?=($_POST["procedencia"] == "true") ? "checked" : ""?> />Sim</label>
						<label class="radio" ><input type="radio" name="procedencia" value="false" <?=($_POST["procedencia"] == "false") ? "checked" : ""?> />Não</label>
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
		if (empty($_POST["procedencia"])) {
			$arrayGrafico = array("Sim" => 0, utf8_encode("Não") => 0);

			while ($result = pg_fetch_object($resSubmit)) {
				if ($result->procedencia == "t") {
					$arrayGrafico["Sim"] += 1;
				} else {
					$arrayGrafico[utf8_encode("Não")] += 1;
				}
			}
			?>

			<div id="grafico" style="min-width: 310px; height: 400px; max-width: 600px; margin: 0 auto" ></div>
			<script>

			montaGrafico('<?=json_encode($arrayGrafico)?>');

			</script>
		<?php
		}
		?>

		<table id="pesquisa_resultado" class="table table-bordered table-large" style="margin: 0 auto;" >
			<thead>
				<tr class="titulo_coluna" >
					<th>Ordem de Serviço</th>
					<th>Defeito Constatado</th>
					<th>Peça</th>
					<th>Nota Fiscal</th>
					<th>Procede</th>
					<th>Análise</th>
					<th>Conferencia</th>
					<th>Data Análise</th>
					<th>Observação</th>
				</tr>
			</thead>
			<tbody>
				<?php
				pg_result_seek($resSubmit, 0);

				while ($result = pg_fetch_object($resSubmit)) {
				?>
					<tr>
						<td><a href="os_press.php?os=<?=$result->os?>" target="_blank" ><?=$result->sua_os?></a></td>
						<td><?=$result->defeito_constatado?></td>
						<td><?=$result->peca?></td>
						<td><?=$result->nota_fiscal?></td>
						<td><?=($result->procedencia == "t") ? "Sim" : "Não" ?></td>
						<td><?=$result->status_analise_peca?></td>
						<td><?=$result->conferencia?></td>
						<td><?=$result->data_analise?></td>
						<td><?=$result->observacao?></td>
					</tr>
				<?php
				}
				?>
			</tbody>
		</table>

		<?php
		if (pg_num_rows($resSubmit) > 50) {
		?>
			<script>
				$.dataTableLoad({ table: "#pesquisa_resultado" });
			</script>
		<?php
		}
		?>

		<br />

		<?php
		$jsonPOST = excelPostToJson($_POST);
		?>

		<div id="gerar_excel" class="btn_excel">
			<input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
			<span><img src="imagens/excel.png" /></span>
			<span class="txt">Gerar Arquivo Excel</span>
		</div>
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
