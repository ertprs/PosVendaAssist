<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";

if (preg_match("/\/admin\//", $_SERVER["PHP_SELF"])) {
	include 'autentica_admin.php';
} else {
	include 'autentica_usuario.php';
}

if (isset($_GET["extrato"])) {
	$extrato = (int)$_GET["extrato"];
}

$sql = "SELECT 	distinct tbl_extrato_lgr.extrato,
				tbl_extrato_lgr.qtde,
				tbl_peca.peca,
				tbl_peca.referencia,
				tbl_peca.descricao
		FROM  tbl_extrato_lgr
		INNER JOIN tbl_peca ON tbl_peca.peca = tbl_extrato_lgr.peca
		AND tbl_peca.fabrica = $login_fabrica
		WHERE tbl_extrato_lgr.extrato = $extrato";
$res = pg_query($con, $sql);

?>

<!DOCTYPE html />
<html>
	<head>
		<meta http-equiv=pragma content=no-cache>
		<link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
    	<link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
    	<link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
    	<link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />
		<link href="plugins/dataTable.css" type="text/css" rel="stylesheet" />


		<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
		<script src="bootstrap/js/bootstrap.js"></script>
		<script src="plugins/dataTable.js"></script>
		<script src="plugins/resize.js"></script>
		<script src="plugins/shadowbox_lupa/lupa.js"></script>

		<script>
			$(function () {
				$.dataTableLupa();
			});
		</script>

		<style type="text/css">
		.lista{
			text-align: center !important
		}
		</style>
	</head>

	<body>
		<div id="container_lupa" style="overflow-y:auto;">
			<div id="topo">
				<img class="espaco" src="imagens/logo_new_telecontrol.png">
				<img class="lupa_img pull-right" src="imagens/lupa_new.png">
			</div>
			<br />
			<hr/>

			<div id="border_table">
				<?php if(pg_num_rows($res)>0){ ?>
				<table id="resultados" class="table table-striped table-bordered table-hover table-lupa" >
					<thead>
						<tr class='titulo_coluna'>
							<th>Referência</th>
							<th>Descrição</th>
							<?
							echo ($login_fabrica == 94) ? "<th>OSs</th>" : "";
							?>
							<th>Qtde</th>
						</tr>
					</thead>
					<tbody>
						<?php
							for($i=0; $i<pg_num_rows($res); $i++){
								$referencia 	= pg_fetch_result($res, $i, "referencia");
								$descricao 		= pg_fetch_result($res, $i, "descricao");
								$qtde 			= pg_fetch_result($res, $i, "qtde");
								$peca 			= pg_fetch_result($res, $i, "peca");

								if($login_fabrica == 94) {
									$sqlos = "SELECT array_to_string(array_agg(tbl_os.sua_os), ',')
											FROM tbl_faturamento_item
											JOIN tbl_os_item USING(pedido,peca)
											JOIN tbl_os_produto USING(os_produto)
											JOIN tbl_os ON tbl_os.os = tbl_os_produto.os
											WHERE peca = $peca
											AND extrato_devolucao = $extrato ";
									$resos = pg_query($con,$sqlos);
									if(pg_num_rows($resos) > 0) {
										$oss = pg_fetch_result($resos,0,0);
									}else{
										$oss = "";
									}
								}
								echo "
								<tr>".
									 "<td class='cursor_lupa lista'>{$referencia}</td>".
									 "<td class='cursor_lupa lista'>{$descricao}</td>";
									 echo ($login_fabrica == 94) ? "<td class='cursor_lupa lista'>{$oss}</td>" : "";
									 echo "<td class='cursor_lupa lista'>{$qtde}</td>".
								'</tr>';
							}
						echo "
					</tbody>";
						echo "
				</table>";
			} else {
				echo '<div class="alert alert_shadobox"><h4>Nenhum resultado encontrado</h4></div>';
			}

		?>
			</div>
		</div>
	</body>
</html>
