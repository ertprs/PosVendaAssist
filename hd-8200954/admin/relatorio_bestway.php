<?php
include_once "dbconfig.php";
include_once "includes/dbconnect-inc.php";

if ($_POST) {
	$data_inicial = $_POST['data_inicial'];
	$data_final   = $_POST['data_final'];

	if (!strlen($data_inicial) or !strlen($data_final)) {
		$msg_erro["msg"][]    = "Preencha as datas";
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

	if (!count($msg_erro["msg"])) {
		$sql = "SELECT 
					tbl_hd_chamado.hd_chamado,
					tbl_produto.referencia,
					tbl_produto.descricao,
					TO_CHAR(tbl_hd_chamado_extra.data_nf, 'DD/MM/YYYY') AS data_nf,
					tbl_hd_chamado_extra.reclamado
				FROM tbl_hd_chamado
				JOIN tbl_hd_chamado_extra USING(hd_chamado)
				JOIN tbl_produto ON tbl_produto.produto = tbl_hd_chamado_extra.produto
				WHERE tbl_hd_chamado.fabrica = 81
				AND tbl_produto.produto = 214115
				AND tbl_hd_chamado.data BETWEEN '{$aux_data_inicial} 00:00:00' AND '{$aux_data_final} 23:59:59'";
		$res = pg_query($con, $sql);

		$rows = pg_num_rows($res);
	}
}
?>
<link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
<link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
<link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
<link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />
<link href="plugins/dataTable.css" type="text/css" rel="stylesheet" />
<link href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css" type="text/css" rel="stylesheet" media="screen">

<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.core.min.js"></script>
<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.widget.min.js"></script>
<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.effect.min.js"></script>
<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.datepicker.min.js"></script>
<script src="bootstrap/js/bootstrap.js"></script>
<script src="plugins/dataTable.js"></script>
<script src="plugins/jquery.mask.js"></script>

<script>
$(function() {
	$.datepickerLoad(Array("data_final", "data_inicial"));
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
?>

<div class="tc_container">
	<form method='POST' class='form-search form-inline tc_formulario' >
		<div class='titulo_tabela '>Relatório Produto IPL6000</div>

		<br />

		<div class='row-fluid'>
			<div class='span2'></div>
				<div class='span4'>
					<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='data_inicial'>Data Inicial</label>
						<div class='controls controls-row'>
							<div class='span4'>
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
							<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?=$data_final?>" >
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>

		<p><br/>
			<button class='btn' id="btn_acao" type="button"  onclick="$('form').submit();">Pesquisar</button>
		</p><br/>
	</form>
</div>

<br />

<?php
if ($rows > 0) {
	$file = fopen("/tmp/relatorio_bestway_ipl6000.xls", "w");

	$thead = "<table border='1'>
		<thead>
			<tr>
				<th colspan='5' bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;' >
					RELATÓRIO PRODUTO IPL6000
				</th>
			</tr>
			<tr>
				<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Atendimento</th>
				<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Referência</th>
				<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Produto</th>
				<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data NF</th>
				<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Descrição</th>
			</tr>
		</thead>
		<tbody>";
	fwrite($file, $thead);

	for ($i = 0; $i < $rows; $i++) {
		$hd_chamado = pg_fetch_result($res, $i, "hd_chamado");
		$referencia = pg_fetch_result($res, $i, "referencia");
		$descricao  = pg_fetch_result($res, $i, "descricao");
		$data_nf    = pg_fetch_result($res, $i, "data_nf");
		$reclamado  = pg_fetch_result($res, $i, "reclamado");

		$body .= "<tr>
			<td nowrap valign='top'>{$hd_chamado}&nbsp;</td>
			<td nowrap valign='top'>{$referencia}&nbsp;</td>
			<td nowrap valign='top'>{$descricao}&nbsp;</td>
			<td nowrap valign='top'>{$data_nf}&nbsp;</td>
			<td valign='top'>{$reclamado}&nbsp;</td>
		</tr>";
	}

	fwrite($file, $body);

	fwrite($file, "<tr>
				<th colspan='5' bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;' >Total de {$rows} registros</th>
			</tr>
		</tbody>
	</table>
	");

	fclose($file);

	if (file_exists("/tmp/relatorio_bestway_ipl6000.xls")) {
		system("mv /tmp/relatorio_bestway_ipl6000.xls xls/relatorio_bestway_ipl6000.xls");
		?>

		<button style="display: block; margin: 0 auto;" type="button" onclick="window.open('xls/relatorio_bestway_ipl6000.xls');" >Download</button>
		<br />
	<?php
	}
?>
	<table id="resultado" class='table table-striped table-bordered table-hover' >
		<thead>
			<tr class='titulo_coluna' >
				<th>Atendimento</th>
				<th>Referência</th>
				<th>Produto</th>
				<th>Data NF</th>
				<th>Descrição</th>
			</tr>
		</thead>
		<tbody>
			<?php
			for ($i = 0; $i < $rows; $i++) {
				$hd_chamado = pg_fetch_result($res, $i, "hd_chamado");
				$referencia = pg_fetch_result($res, $i, "referencia");
				$descricao  = pg_fetch_result($res, $i, "descricao");
				$data_nf    = pg_fetch_result($res, $i, "data_nf");
				$reclamado  = pg_fetch_result($res, $i, "reclamado");

				echo "<tr>
					<td nowrap>{$hd_chamado}&nbsp;</td>
					<td nowrap>{$referencia}&nbsp;</td>
					<td nowrap>{$descricao}&nbsp;</td>
					<td nowrap>{$data_nf}&nbsp;</td>
					<td>{$reclamado}&nbsp;</td>
				</tr>";
			}
			?>
		</tbody>
	</table>

	<script>
		$.dataTableLoad({
			table: "#resultado",
			type: "custom",
			config: [ "info" ],
			aoColumns:[null,null,null,{"sType":"date"},null]
		});
	</script>
<?php
}
?>