<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";

if (preg_match("/\/admin\//", $_SERVER["PHP_SELF"])) {
	include 'autentica_admin.php';
} else {
	include 'autentica_usuario.php';
}

if (isset($_REQUEST["faturamento"])) {
	$conhecimento = $_REQUEST["conhecimento"];
}

$sql = "SELECT * from tbl_faturamento_correio 
		WHERE fabrica = $login_fabrica and conhecimento = '$conhecimento' order by data desc";
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

				$('#resultados').on('click', '.produto-item', function() {
					var info = JSON.parse($(this).attr('data-produto'));
					if (typeof(info) == 'object') {
						window.parent.retorna_produto(info);
						window.parent.Shadowbox.close();
					}
				});
			});
		</script>
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
							<th>#</th>
							<th>Data</th>
							<th>Local</th>
							<th>Situação</th>
							<th>Observação</th>
						</tr>
					</thead>
					<tbody>
						<?php						
							for($i=0; $i<pg_num_rows($res); $i++){

								$data_hora 		= pg_fetch_result($res, $i, data);
								$local 		= pg_fetch_result($res, $i, local);
								$situacao 	= utf8_decode(pg_fetch_result($res, $i, situacao));
								$observacao = pg_fetch_result($res, $i, obs);

								$data = substr($data_hora, 0, 10);
								$hora = substr($data_hora, 11, 5);

								list($y, $m, $d) 	= explode("-", $data);
								$data 				= $d."/".$m."/".$y;

								echo "
								<tr class='produto-item' data-produto='".json_encode($r)."'>".
									 "<td class='cursor_lupa'>".($i+1)."</td>".
									 "<td class='cursor_lupa'>{$data} {$hora}</td>".
									 "<td class='cursor_lupa'>{$local}</td>".
									 "<td class='cursor_lupa'>{$situacao}</td>".
									 "<td class='cursor_lupa'>{$observacao}</td>".
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
