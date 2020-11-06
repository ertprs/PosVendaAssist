<?php

	include "dbconfig.php";
	include "includes/dbconnect-inc.php";
	include 'autentica_admin.php';
	include "funcoes.php";

	$data_reparo = $_GET['data'];
	$posto       = $_GET['posto'];
	$tipo_data   = $_GET["tipo_data"];
	$linha       = $_GET["linha"];
	$familia     = $_GET["familia"];
	$produto     = $_GET["produto"];
	$tecnico     = $_GET["tecnico"];

	if (!empty($posto)) {
		$cond = " AND tbl_os.posto = $posto ";
	}

	if ($tipo_data == "a") {
		$cond .= " AND date_part('year', tbl_os.data_abertura) = date_part('year', date '$data_reparo') AND date_part('month', tbl_os.data_abertura) = date_part('month', date '$data_reparo') ";
	} else {
		$cond .= " AND date_part('year', tbl_os.data_conserto) = date_part('year', date '$data_reparo') AND date_part('month', tbl_os.data_conserto) = date_part('month', date '$data_reparo') ";
	}

	if(!empty($linha)){
		$cond .= " AND tbl_produto.linha = $linha ";
	}

	if(!empty($familia)){
		$cond .= " AND tbl_produto.familia = $familia ";
	}

	if (!empty($produto)) {
		$cond .= " AND tbl_os.produto = $produto ";
	}

	if (!empty($tecnico)) {
		$cond .= " AND tbl_tecnico.tecnico = $tecnico ";
		$joinTecnico    = "INNER JOIN tbl_tecnico ON tbl_tecnico.tecnico = tbl_os.tecnico AND tbl_tecnico.fabrica = {$login_fabrica} AND tbl_tecnico.posto = {$posto}";
	}

  	if($data_reparo){
		$sql = "SELECT 	tbl_os.os,
				tbl_os.sua_os,
				tbl_os.troca_garantia,
				to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura,
				to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento,
				tbl_produto.descricao,
				tbl_produto.referencia,
				CASE WHEN tbl_os.consumidor_revenda = 'C' THEN
					tbl_os.consumidor_nome
				ELSE
					tbl_os.revenda_nome
				END AS consumidor,
				tbl_defeito_constatado.descricao AS defeito
			FROM tbl_os
			JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i = $login_fabrica
			LEFT JOIN tbl_defeito_constatado ON tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado
			$joinTecnico
			WHERE tbl_os.fabrica = $login_fabrica
			AND tbl_os.excluida IS NOT TRUE
			AND tbl_os.data_conserto IS NOT NULL
			{$cond}
			AND tbl_os.data_conserto BETWEEN '$data_reparo 00:00:00' and '$data_reparo 23:59:59'
			ORDER BY tbl_os.troca_garantia";
		$res = pg_query($con,$sql);
		$rows = pg_num_rows($res);
  	}



    if($_GET['gerar_excel'] == "true" ){
   			$data = date("d-m-Y-H:i");

			$filename = "relatorio-produtividade-os-reparo-{$data}.xls";

			$file = fopen("/tmp/{$filename}", "w");

			fwrite($file, "
				<table border='1'>
					<thead>
						<tr>
							<th colspan='7' bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;' >
								RELATÓRIO DE OSs COM REPARO EM $data_reparo
							</th>
						</tr>
						<tr>

							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>OS</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data Abertura</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data Fechamento</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Produto</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Consumidor</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Defeito</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Troca</th>

						</tr>
					</thead>
					<tbody>
			");

			for ($i = 0; $i < $rows; $i++) {
                $sua_os     	= pg_fetch_result($res, $i, "sua_os");
                $os_troca     	= pg_fetch_result($res, $i, "troca_garantia");
				$data_abertura		= pg_fetch_result($res, $i, "data_abertura");
				$data_fechamento	= pg_fetch_result($res, $i, "data_fechamento");
				$consumidor			= pg_fetch_result($res, $i, "consumidor");
				$defeito_constatado	= pg_fetch_result($res, $i, "defeito");
				$produto			= pg_fetch_result($res, $i, "referencia")." - ".pg_fetch_result($res, $i, "descricao");

    			$troca = ($os_troca == "t") ? "Sim" : "Não";

				fwrite($file, "
					<tr class='tac' style='text-align:center'>

						<td>$sua_os</td>
						<td>$data_abertura</td>
						<td>$data_fechamento</td>
						<td>$produto</td>
						<td>$consumidor</td>
						<td>$defeito_constatado</td>
						<td>$troca</td>
					</tr>"
				);
			}

			fwrite($file, "
						<tr>
							<th colspan='7' bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;' >Total de ".$rows." registros</th>
						</tr>
					</tbody>
				</table>
			");

		    fwrite($file, $conteudo);

			fclose($file);

			if (file_exists("/tmp/{$filename}")) {

				system("mv /tmp/{$filename} xls/{$filename}");

				header("Location: xls/{$filename}");
			}

			exit;


    }
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
				$.dataTableLoad({ table: "#resultado" });

			});
		</script>

	</head>

	<body>
		<div id="container_lupa" style="overflow-y:auto;">
			<div id="topo">
				<img class="espaco" src="../imagens/logo_new_telecontrol.png">
			</div>
			<br /><hr />
			<?
			if ($rows > 0) {
			?>
				<div id="border_table">


					<table id="resultado" class="table table-striped table-bordered table-hover table-lupa" >
						<thead>
							<tr class='titulo_coluna'>
								<th>OS</th>
								<th>Data Abertura</th>
								<th>Data Fechamento</th>
								<th>Produto</th>
								<th>Consumidor</th>
								<th>Defeito</th>
								<th>Troca</th>
							</tr>
						</thead>
						<tbody>
						<?php
						for ($i = 0; $i < $rows; $i++) {
							$os     			= pg_fetch_result($res, $i, "os");
							$sua_os     		= pg_fetch_result($res, $i, "sua_os");
							$os_troca     		= pg_fetch_result($res, $i, "troca_garantia");
							$data_abertura   	= pg_fetch_result($res, $i, "data_abertura");
							$data_fechamento    = pg_fetch_result($res, $i, "data_fechamento");
							$consumidor			= pg_fetch_result($res, $i, "consumidor");
							$defeito_constatado	= pg_fetch_result($res, $i, "defeito");
							$produto			= pg_fetch_result($res, $i, "referencia")." - ".pg_fetch_result($res, $i, "descricao");

							$troca = ($os_troca == "t") ? "Sim" : "Não";

							echo "
							<tr>
								<td><a href='os_press.php?os={$os}' target='_blank'>{$sua_os}</a></td>
								<td>{$data_abertura}</td>
								<td>{$data_fechamento}</td>
								<td>{$produto}</td>
								<td>{$consumidor}</td>
								<td>{$defeito_constatado}</td>
								<td>{$troca}</td>
							</tr>";
						}
						?>
						</tbody>
					</table>
				</div>

			<?php



            $jsonPOST = excelPostToJson($arrayParams);
            $params = "gerar_excel=true&data={$data_reparo}";

		?>

				<br />

            <div id='gerar_excel' class="btn_excel">
				<span><img src='imagens/excel.png' /></span>
				<a href="<?=$PHP_SELF.'?'.$params?>"><span class="txt">Gerar Arquivo Excel</span></a>
			</div>

		  <?php

			    echo "</div>";

			} else {
				echo '<div class="alert alert_shadowbox">
				    <h4>Nenhum resultado encontrado</h4>
				</div>';
			}
			?>
		</div>
	</body>
</html>
