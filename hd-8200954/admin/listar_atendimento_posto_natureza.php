<?php

	include "dbconfig.php";
	include "includes/dbconnect-inc.php";
	include 'autentica_admin.php';
	include "funcoes.php";

    if(isset($_GET["gerar_excel"])){
       $params = (object) $_GET;

    }else{
	    $params = json_decode(utf8_encode(str_replace("\\","",$_GET["params"])));
    }

	$reclamado = ($params->categoria == "indicacao_at" || $params->categoria == "indicacao_rev") ? ", tbl_hd_chamado_extra.reclamado " : "";

	if($params->situacao == "Resolvido"){

		$sql = "SELECT DISTINCT
			tbl_hd_chamado_item.hd_chamado,
			TO_CHAR(tbl_hd_chamado.data, 'DD/MM/YYYY') 		AS data_abertura,
			TO_CHAR(tbl_hd_chamado_item.data, 'DD/MM/YYYY') AS data_fechamento,
			tbl_produto.descricao 							AS produto ,
			tbl_posto.nome
			$reclamado
		FROM tbl_hd_chamado
		JOIN tbl_hd_chamado_extra USING(hd_chamado)
		JOIN tbl_posto ON tbl_hd_chamado_extra.posto = tbl_posto.posto
		LEFT JOIN tbl_produto ON tbl_hd_chamado_extra.produto = tbl_produto.produto
		JOIN tbl_hd_chamado_item ON tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado
		JOIN tbl_posto_fabrica ON tbl_hd_chamado_extra.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
		WHERE tbl_hd_chamado.fabrica = {$login_fabrica}
		AND tbl_hd_chamado.data BETWEEN '{$params->data_inicial} 00:00:00' AND '{$params->data_final} 23:59:59'
		AND tbl_hd_chamado_extra.posto = $params->posto
		AND tbl_hd_chamado.categoria = '$params->categoria'
		AND tbl_hd_chamado.status = '$params->situacao'
		AND tbl_hd_chamado_item.status_item = 'Resolvido'";

	}else{

		$sql = "SELECT
			tbl_hd_chamado.hd_chamado,
			tbl_hd_chamado.data 			AS data_abertura,
			tbl_hd_chamado.data_resolvido 	AS data_fechamento,
			tbl_produto.descricao 			AS produto ,
			tbl_posto.nome
			$reclamado
		FROM tbl_hd_chamado
		JOIN tbl_hd_chamado_extra USING(hd_chamado)
		JOIN tbl_posto ON tbl_hd_chamado_extra.posto = tbl_posto.posto
		LEFT JOIN tbl_produto ON tbl_hd_chamado_extra.produto = tbl_produto.produto
		JOIN tbl_posto_fabrica ON tbl_hd_chamado_extra.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
		WHERE tbl_hd_chamado.fabrica = {$login_fabrica}
		AND tbl_hd_chamado.data BETWEEN '{$params->data_inicial} 00:00:00' AND '{$params->data_final} 23:59:59'
		AND tbl_hd_chamado_extra.posto = $params->posto
		AND tbl_hd_chamado.categoria = '$params->categoria'
		AND tbl_hd_chamado.status = '$params->situacao'";

	}

	$res = pg_query($con, $sql);
	$nome_posto = pg_fetch_result($res, 0 , 'nome');
	$rows = pg_num_rows($res);
	
    if($_GET['gerar_excel'] == "true" ){
   			$data = date("d-m-Y-H:i");

			$filename = "relatorio-atendimento-posto-natureza-{$data}.xls";

			$file = fopen("/tmp/{$filename}", "w");

			fwrite($file, "
				<table border='1'>
					<thead>
						<tr>
							<th colspan='5' bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;' >
								RELATÓRIO ATENDIMENTO POSTO NATUREZA
							</th>
						</tr>
						<tr>

							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Chamado</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data Abertura</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data Fechamento</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Produto</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Descrição</th>

						</tr>
					</thead>
					<tbody>
			");

			for ($i = 0; $i < $rows; $i++) {
                $hd_chamado     	= pg_fetch_result($res, $i, "hd_chamado");
				$data_abertura		= pg_fetch_result($res, $i, "data_abertura");
				$data_fechamento	= pg_fetch_result($res, $i, "data_fechamento");
				$produto			= pg_fetch_result($res, $i, "produto");

				if($params->categoria == "indicacao_at" || $params->categoria == "indicacao_rev") {
					$descricao	= pg_fetch_result($res, $i, 'reclamado');
					$td_reclamado = "<td>$descricao</td>";
                }

				fwrite($file, "
					<tr class='tac' style='text-align:center'>

						<td>$hd_chamado</td>
						<td>$data_abertura</td>
						<td>$data_fechamento</td>
						<td>$produto</td>
                        $td_reclamado
					</tr>"
				);
			}

			fwrite($file, "
						<tr>
							<th colspan='5' bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;' >Total de ".$rows." registros</th>
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

					<style>
						h1, h2, h3, h4, h5, h6 {
							display: inline;
						}
					</style>

					<?php

					list($ano, $mes, $dia) 	= explode("-", $params->data_inicial);
					$data_inicial 			= $dia."/".$mes."/".$ano;

					list($ano, $mes, $dia) 	= explode("-", $params->data_final);
					$data_final 			= $dia."/".$mes."/".$ano;

					$natureza				= $params->categoria;
					$situacao				= $params->situacao;

					$naturezas = array(
						"reclamacao_produto" 	=> "Reclamação",
						"reclamacao_empresa" 	=> "Recl. Empresa",
						"reclamacao_at" 		=> "Reclamação A.T.",
						"duvida_produto" 		=> "Dúvida Produto",
						"sugestao" 				=> "Sugestão",
						"procon" 				=> "Procon/Judicial",
						"onde_comprar" 			=> "Onde Comprar",
						"indicacao_rev" 		=> "Indicação Revenda",
						"indicacao_at" 			=> "Indicação A.T"
					);

					?>

					<h5>Data:</h5> <?=$data_inicial;?> à <?=$data_final;?> <br />
					<h5>Posto:</h5> <?=$nome_posto;?> <br />
					<h5>Natureza:</h5> <?=$naturezas[$natureza];?> <br />
					<h5>Situação:</h5> <?=$situacao;?> <br />

					<hr />

					<table id="resultado" class="table table-striped table-bordered table-hover table-lupa" >
						<thead>
							<tr class='titulo_coluna'>
								<th>Chamado</th>
								<th>Data Abertura</th>
								<th>Data Fechamento</th>
								<th>Produto</th>
								<?php echo ($params->categoria == "indicacao_at" || $params->categoria == "indicacao_rev") ? "<th>Descrição</th>" : ""; ?>
							</tr>
						</thead>
						<tbody>
						<?php
						for ($i = 0; $i < $rows; $i++) {
							$hd_chamado     	= pg_fetch_result($res, $i, "hd_chamado");
							$data_abertura   	= pg_fetch_result($res, $i, "data_abertura");
							$data_fechamento    = pg_fetch_result($res, $i, "data_fechamento");
							$produto      		= pg_fetch_result($res, $i, "produto");
							if($params->categoria == "indicacao_at" || $params->categoria == "indicacao_rev") {
								$descricao 	= pg_fetch_result($res, $i, 'reclamado');
								$td_reclamado = "<td>$descricao</td>";
							}

							echo "
							<tr>
								<td><a href='callcenter_interativo_new.php?callcenter={$hd_chamado}' target='_blank'>{$hd_chamado}</a></td>
								<td>{$data_abertura}</td>
								<td>{$data_fechamento}</td>
								<td>{$produto}</td>
								$td_reclamado
							</tr>";
						}
						?>
						</tbody>
					</table>
				</div>

			<?php



            $jsonPOST = excelPostToJson($arrayParams);
            $params = "gerar_excel=true&posto={$params->posto}&nome_posto={$nome_posto}&data_inicial={$params->data_inicial}&data_final={$params->data_final}&categoria={$params->categoria}&situacao={$params->situacao}";

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
