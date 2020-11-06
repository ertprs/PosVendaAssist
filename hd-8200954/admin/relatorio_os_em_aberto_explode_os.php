<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_admin.php";
include "funcoes.php";

$intervalo     = $_GET["intervalo"];
$tipo_pesquisa = $_GET["tipo_pesquisa"];
$id            = $_GET["id"];
$data_final    = $_GET["data_final"];
$data_inicial  = $_GET["data_inicial"];
$codigo_posto  = $_GET["codigo_posto"];


if(strlen($data_inicial) > 0 && strlen($data_final) > 0){
    list($dia, $mes, $ano) = explode("/", $data_inicial);
    $data_inicio = $ano."-".$mes."-".$dia; 

    list($dia, $mes, $ano) = explode("/", $data_final);
    $data_fim = $ano."-".$mes."-".$dia;
}


?>

<link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
<link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
<link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
<link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />

<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>

<?php 
if (empty($id) || empty($tipo_pesquisa) || empty($intervalo)) { 
?>
	<div class="alert alert-error" >
		<h4>Erro ao carregar informações</h4>
	</div>
<?php 
} else { 

	switch ($tipo_pesquisa) {
		case "posto":
			$where_tipo_pesquisa  = "AND tbl_os.posto = {$id}";
			break;
		
		case "familia":
			$where_tipo_pesquisa  = "AND tbl_familia.familia = {$id}";
			break;
	}

	switch ($intervalo) {
		case "0_1":
			$where = "AND (tbl_os.data_abertura BETWEEN (CURRENT_DATE - INTERVAL '1 days') AND CURRENT_DATE) AND tbl_os.data_conserto IS NULL";
			break;

		case "2_3":
			$where = "AND (tbl_os.data_abertura BETWEEN (CURRENT_DATE - INTERVAL '3 days') AND (CURRENT_DATE - INTERVAL '2 days')) AND tbl_os.data_conserto IS NULL";
			break;

		case "3_mais":
			$where = "AND tbl_os.data_abertura < (CURRENT_DATE - INTERVAL '3 days') AND tbl_os.data_conserto IS NULL";
			break;

		case "0_10":
			$where = "AND (tbl_os.data_abertura BETWEEN (CURRENT_DATE - INTERVAL '10 days') AND CURRENT_DATE) AND tbl_os.data_conserto IS NULL";
			break;
		
		case "11_20":
			$where = "AND (tbl_os.data_abertura BETWEEN (CURRENT_DATE - INTERVAL '20 days') AND (CURRENT_DATE - INTERVAL '11 days')) AND tbl_os.data_conserto IS NULL";
			break;

		case "21_30":
			$where = "AND (tbl_os.data_abertura BETWEEN (CURRENT_DATE - INTERVAL '30 days') AND (CURRENT_DATE - INTERVAL '21 days')) AND tbl_os.data_conserto IS NULL";
			break;

		case "30_mais":
			$where = "AND tbl_os.data_abertura < (CURRENT_DATE - INTERVAL '30 days') AND tbl_os.data_conserto IS NULL";
			break;

		case "os_consertadas":
			$where = "AND tbl_os.data_conserto IS NOT NULL";
			break;
	}

	if(strlen(trim($codigo_posto))>0){
		$where_posto = " AND tbl_posto_fabrica.codigo_posto = '$codigo_posto' ";
	}

	$sql = "SELECT 
				tbl_os.os, 
				tbl_os.sua_os, 
				TO_CHAR(tbl_os.data_abertura, 'DD/MM/YYYY') AS data_abertura,
				(CURRENT_DATE - tbl_os.data_abertura) AS dias_em_aberto,
				tbl_posto.nome AS posto,
				tbl_produto.referencia AS produto_referencia,
				tbl_produto.descricao AS produto_descricao,
				TO_CHAR(tbl_os.data_conserto, 'DD/MM/YYYY') AS data_conserto
			FROM tbl_os
			INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
			INNER JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
			INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i = {$login_fabrica}
			INNER JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia AND tbl_familia.fabrica = {$login_fabrica}
			WHERE tbl_os.fabrica = {$login_fabrica}
			AND tbl_os.finalizada IS NULL
			AND tbl_os.data_fechamento IS NULL
			AND tbl_os.data_abertura BETWEEN '$data_inicio 00:00:00' AND '$data_fim 23:59:59'
			{$where_posto}
			{$where}
			{$where_tipo_pesquisa}";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		$rows = pg_num_rows($res);
		?>

		<div style="height: 100%; overflow-y: auto">
			<table class="table table-striped table-bordered" >
				<thead>
					<tr class="titulo_coluna" >
						<th>OS</th>
						<th>Data de Abertura</th>
						<th>Dias em Aberto</th>
						<?php
						if ($intervalo == "os_consertadas") {
							echo "<th>Data de Conserto</th>";
						}
						?>
						<th>Posto Autorizado</th>
						<th>Produto</th>
					</tr>
				</thead>
				<tbody>
					<?php
					for ($i = 0; $i < $rows; $i++) { 
						$os                 = pg_fetch_result($res, $i, "os");
						$sua_os             = pg_fetch_result($res, $i, "sua_os");
						$data_abertura      = pg_fetch_result($res, $i, "data_abertura");
						$dias_em_aberto     = pg_fetch_result($res, $i, "dias_em_aberto");
						$posto              = pg_fetch_result($res, $i, "posto");
						$produto_referencia = pg_fetch_result($res, $i, "produto_referencia");
						$produto_descricao  = pg_fetch_result($res, $i, "produto_descricao");
						$data_conserto      = pg_fetch_result($res, $i, "data_conserto");
						?>
						<tr>
							<td class="tac" ><a href="os_press.php?os=<?=$os?>" target="_blank"><?=$sua_os?></a></td>
							<td class="tac" ><?=$data_abertura?></td>
							<td class="tac" ><?=$dias_em_aberto?></td>
							<?php
							if ($intervalo == "os_consertadas") {
								echo "<td class='tac' >{$data_conserto}</td>";
							}
							?>
							<td><?=$posto?></td>
							<td><?=$produto_referencia." - ".$produto_descricao?></td>
						</tr>
					<?php
					}
					?>
				</tbody>
			</table>
		</div>
	<?php
	} else {
	?>
		<div class="alert alert-error" >
			<h4>Nenhum resultado encontrado</h4>
		</div>
	<?php
	}
} 
?>