<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

include 'autentica_admin.php';
include 'funcoes.php';

$os     = $_REQUEST["os"];

$sql = "SELECT tbl_peca.referencia || ' - ' || tbl_peca.descricao as peca, 
				tbl_defeito.descricao as defeito_descricao,
				tbl_servico_realizado.descricao as servico_descricao
		FROM tbl_os_produto
		JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
		AND tbl_os_item.fabrica_i = {$login_fabrica}
		JOIN tbl_servico_realizado ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
		AND tbl_servico_realizado.fabrica = {$login_fabrica}
		JOIN tbl_peca ON tbl_os_item.peca = tbl_peca.peca AND tbl_peca.fabrica = {$login_fabrica}
		JOIN tbl_defeito ON tbl_defeito.defeito = tbl_os_item.defeito AND tbl_defeito.fabrica = {$login_fabrica}
		WHERE tbl_os_produto.os = {$os}
				";
$resConsulta = pg_query($con, $sql);

?>
<html>
	<head>
		<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/bootstrap.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/extra.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="css/tc_css.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="css/tooltips.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css">
		<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/ajuste.css" />
		<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
		<script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
		<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.core.min.js"></script>
		<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.widget.min.js"></script>
		<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.effect.min.js"></script>
		<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.tabs.min.js"></script>
		<script src="https://code.highcharts.com/highcharts.js"></script>
		<script src="https://code.highcharts.com/modules/exporting.js"></script>
		<script src="https://code.highcharts.com/modules/export-data.js"></script>
	</head>
	<body>
		<br /><br />
		<table id="resultado_os_atendimento" class='table table-striped table-bordered table-hover table-fixed' >
			<thead>
				<tr>
					<th class="titulo_tabela" colspan="3">
						Peças da OS <?= $os ?>
					</th>
				</tr>
				<tr class='titulo_coluna'>
					<th>Peça</th>
					<th>Defeito</th>
					<th>Serviço Realizado</th>
				</tr>
			</thead>
			<tbody>
				<?php

				for ($i = 0; $i < pg_num_rows($resConsulta); $i++) {
					$peca  				= pg_fetch_result($resConsulta, $i, 'peca');
					$servico_realizado  = pg_fetch_result($resConsulta, $i, 'servico_descricao');
					$defeito    		= pg_fetch_result($resConsulta, $i, 'defeito_descricao');

				?>
				<tr>
					<td class="tac"><?= $peca ?></td>
					<td class="tac"><?= $defeito ?></td>
					<td class="tac"><?= $servico_realizado ?></td>
				</tr>
				<?php
				}
				?>
			</tbody>
		</table>
	</body>
</html>
