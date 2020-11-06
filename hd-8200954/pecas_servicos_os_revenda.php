<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";

if (preg_match("/\/admin\//", $_SERVER["PHP_SELF"])) {
	include 'autentica_admin.php';
	define('APPBACK', '../');
	$areaAdmin = true;
} else {
	define('APPBACK', '');
	include 'autentica_usuario.php';
}

if ($_REQUEST["info_pecas"]) {
	$info_pecas = $_REQUEST["info_pecas"];
}

?>
<!DOCTYPE html />
<html>
	<head>
		<meta http-equiv=pragma content=no-cache>
		<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/bootstrap.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/extra.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="css/tc_css.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/ajuste.css" />
		<link type="text/css" rel="stylesheet" href="plugins/dataTable.css" />

		<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
		<script src="bootstrap/js/bootstrap.js"></script>
		<script src="plugins/dataTable.js"></script>
		<script src="plugins/resize.js"></script>
		<script src="plugins/shadowbox_lupa/lupa.js"></script>
		<script src='plugins/jquery.alphanumeric.js'></script>
	</head>

	<body>
		<div id="border_table">
			<div class="alert alert-info alert-block">
				<!-- <button type="button" class="close" data-dismiss="alert">&times;</button> -->
				<h4>Atenção!</h4>
				<strong>Peças e Serviços lançados em lote.</strong><br/>Qualquer PEÇA/SERVIÇO lançada individualmente na OS PRODUTO não vai ser mostrada nessa tela.
			</div>
			<table class="table table-striped table-bordered table-hover table-lupa" style="margin-bottom:60px !important;">
				<thead>
					<tr class='titulo_tabela'>
						<th colspan="4">PEÇAS / SERVIÇOS REALIZADOS</th>
					</tr>
					<tr class='titulo_coluna'>
						<th>Referência</th>
						<th>Descrição</th>
						<th>Qtde</th>
						<th>Serviço Realizado</th>
					</tr>
				</thead>
				<tbody>
					<?php 
						if (!empty($info_pecas)){
							$info_pecas = json_decode($info_pecas, true);
							foreach ($info_pecas as $key => $value) {
								$peca    			= $value["id_peca"];
								$servico_realizado 	= $value["servico_realizado"];
								$qtde 				= $value["qtde_lancada"];
							
								$sql_servico = "SELECT descricao FROM tbl_servico_realizado WHERE fabrica = $login_fabrica AND servico_realizado = $servico_realizado";
								$res_servico = pg_query($con, $sql_servico);
								
								if (pg_num_rows($res_servico) > 0){
									$desc_servico = pg_fetch_result($res_servico, 0, "descricao");
								}

								$sql_peca = "SELECT referencia, descricao FROM tbl_peca WHERE fabrica = $login_fabrica AND peca = $peca";
								$res_peca = pg_query($con, $sql_peca);
								if (pg_num_rows($res_peca) > 0){
									$ref_peca = pg_fetch_result($res_peca, 0, "referencia");
									$desc_peca = pg_fetch_result($res_peca, 0, "descricao");
								}
								echo "<tr>";
								echo "<td class='tac'>$ref_peca</td>";
								echo "<td>$desc_peca</td>";
								echo "<td class='tac'>$qtde</td>";
								echo "<td>$desc_servico</td>";
								echo "</tr>";
							}
						}
					?>
				</tbody>
			</table>
		</div>
	</body>
</html>
