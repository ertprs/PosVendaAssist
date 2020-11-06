<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';
include 'funcoes.php';

$categoria 	= $_REQUEST['categoria'];
$legenda 	= $_REQUEST['legenda'];

if (!empty($legenda) AND !empty($categoria)) {
		
	$aux_data_inicial 	= $_REQUEST["data_inicial"];
	$aux_data_final 	= $_REQUEST["data_final"];

	$status 		= $_REQUEST["status"];
	$origem 		= $_REQUEST["origem"];
	$linha_producao = $_REQUEST["linha_producao"];
	$familia_sap 	= $_REQUEST["familia_sap"];
	$pd 			= $_REQUEST["pd"];

	$categoria 			= explode("/", $categoria);
    $mes 				= $categoria[0];
    $ano 				= $categoria[1];

    if (!empty($status)) {
        $whereStatus = "AND o.status_checkpoint IN ({$status})";
    }

    if (!empty($origem)) {
        $whereOrigem = "AND prt.origem IN ({$origem})";
    }

    if (!empty($linha_producao)) {
        $whereLinhaProducao = "AND prt.nome_comercial IN ({$linha_producao})";
    }

    if (!empty($familia_sap)) {
        $whereFamiliaSap = "AND JSON_FIELD('familia_desc', prt.parametros_adicionais) IN ({$familia_sap})";
    }

    if (!empty($pd)) {
        $wherePd = "AND JSON_FIELD('pd', prt.parametros_adicionais) IN ({$pd})";
    }

    if ($legenda == "sem_ns"){
        $sql = "SELECT
				o.sua_os,
                o.os,
                TO_CHAR(o.data_abertura, 'DD/MM/YYYY') AS data_abertura,
                o.consumidor_nome,
                sc.descricao AS status_os,
                p.nome AS posto_nome
			FROM (
				SELECT
					DATE_PART('month', ns.data_venda) AS mes,
					DATE_PART('year', ns.data_venda) AS ano
				FROM tbl_numero_serie ns
				LEFT JOIN tbl_produto prt ON prt.produto = ns.produto AND prt.fabrica_i = {$login_fabrica}
				WHERE ns.fabrica = {$login_fabrica}
				AND ns.data_venda BETWEEN '$aux_data_inicial' AND '$aux_data_final'
				AND DATE_PART('month', ns.data_venda) = $mes AND DATE_PART('year', ns.data_venda) = $ano
				AND ns.data_venda IS NOT NULL
				{$whereOrigem}
                {$whereLinhaProducao}
                {$whereFamiliaSap}
                {$wherePd}
				GROUP BY mes, ano
			) x
			JOIN tbl_os o ON o.fabrica = {$login_fabrica} AND DATE_PART('year', o.data_abertura) = x.ano AND DATE_PART('month', o.data_abertura) = x.mes
			JOIN tbl_posto p ON p.posto = o.posto
            JOIN tbl_posto_fabrica pf ON pf.posto = p.posto AND pf.fabrica = {$login_fabrica}
            LEFT JOIN tbl_os_produto op ON op.os = o.os
            LEFT JOIN tbl_produto prt ON prt.produto = op.produto AND prt.fabrica_i = {$login_fabrica}
            LEFT JOIN tbl_status_checkpoint sc ON sc.status_checkpoint = o.status_checkpoint
			WHERE o.excluida IS NOT TRUE
			AND o.os NOT IN (
				SELECT
					o.os
				FROM tbl_os o
				JOIN tbl_numero_serie ns ON ns.serie = o.serie AND ns.produto = o.produto AND ns.fabrica = {$login_fabrica}
				LEFT JOIN tbl_os_produto op ON op.os = o.os
            	LEFT JOIN tbl_produto prt ON prt.produto = op.produto AND prt.fabrica_i = {$login_fabrica}
            	WHERE o.fabrica = {$login_fabrica}
				AND o.data_abertura BETWEEN '$aux_data_inicial' AND '$aux_data_final'
				AND o.excluida IS NOT TRUE
				AND ns.data_venda IS NOT NULL
				$whereOrigem
				$whereLinhaProducao
				$whereFamiliaSap
				$wherePd
			)
			$whereStatus
			$whereOrigem
			$whereLinhaProducao
			$whereFamiliaSap
			$wherePd
			GROUP BY o.sua_os,o.os,data_abertura,consumidor_nome,status_os,posto_nome";
		$res = pg_query($con, $sql);
    }else{
    	$sql = "SELECT
                    o.sua_os,
                    o.os,
                    TO_CHAR(o.data_abertura, 'DD/MM/YYYY') AS data_abertura,
                    o.consumidor_nome,
                    sc.descricao AS status_os,
                    p.nome AS posto_nome
                FROM tbl_os o
                JOIN tbl_os_produto op USING(os)
                JOIN tbl_numero_serie ns ON ns.serie = o.serie AND ns.produto = o.produto AND ns.fabrica = {$login_fabrica}
                JOIN tbl_produto prt ON prt.produto = op.produto AND prt.fabrica_i = {$login_fabrica}
                JOIN tbl_posto p ON p.posto = o.posto
                JOIN tbl_posto_fabrica pf ON pf.posto = p.posto AND pf.fabrica = {$login_fabrica}
                LEFT JOIN tbl_status_checkpoint sc ON sc.status_checkpoint = o.status_checkpoint
                WHERE o.fabrica = {$login_fabrica}
                AND DATE_PART('month', o.data_abertura) = {$mes} AND DATE_PART('year', o.data_abertura) = {$ano}
                AND DATE_PART('year', ns.data_venda) = {$legenda}
                AND o.excluida IS NOT TRUE
                AND ns.data_venda IS NOT NULL
                $whereStatus
				$whereOrigem
				$whereLinhaProducao
				$whereFamiliaSap
				$wherePd";
		$res = pg_query($con, $sql);
    }
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

		<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
		<script src="bootstrap/js/bootstrap.js"></script>
<!--
		<link href="plugins/datatable_responsive/css/jquery.dataTables.min.css" type="text/css" rel="stylesheet" />
		<link href="plugins/datatable_responsive/css/responsive.dataTables.min.css" type="text/css" rel="stylesheet" />

		<script src="plugins/datatable_responsive/js/jquery.dataTables.min.js"></script>
		<script src="plugins/datatable_responsive/js/dataTables.responsive.min.js"></script>
-->
		<script src="plugins/resize.js"></script>
<?php
	$plugins = array(
		"datatable_responsive"
	);
	include("plugin_loader.php");
?>

<script type="text/javascript">
	$(function() {
		$("#detalhes_tma").DataTable({
			responsive: true,
			"language": {
	            "lengthMenu": "Qtde _MENU_  por página",
	            "search": "Buscar",
	            "zeroRecords": "Nenhum resultado encontrado",
	            "info": "Visualizando página _PAGE_ de _PAGES_",
	            "infoEmpty": "Nenhum resultado encontrado",
	            "infoFiltered": "(busca feita pelo total de _MAX_ registros)",
	            'paginate': {
			    	'previous': '<span class="prev-icon"></span>',
			    	'next': '<span class="next-icon"></span>'
			    }
	        }
		});
	});
</script>
	</head>
	<body>
		<div id="" style="overflow-y:auto;z-index:1">
			<?php if(pg_num_rows($res) > 0){ ?>
				<table id="detalhes_tma" class="display nowrap" cellspacing="0" width="100%" >
					<thead>
						<tr class='titulo_coluna' >
							<th>OS</th>
							<th>Data Abertura</th>
							<th>Status</th>
							<th>Posto</th>
							<th>Consumidor</th>
	                    </tr>
					</thead>
					<tbody>
						<?php for ($i=0; $i < pg_num_rows($res); $i++) {
							$os 				= pg_fetch_result($res, $i, 'os');
							$sua_os				= pg_fetch_result($res, $i, 'sua_os');
							$data_abertura 		= pg_fetch_result($res, $i, 'data_abertura');
							$consumidor_nome 	= pg_fetch_result($res, $i, 'consumidor_nome');
							$status_os			= pg_fetch_result($res, $i, 'status_os');
							$posto_nome 		= pg_fetch_result($res, $i, 'posto_nome');
						?>
							<tr>
								<td><a href="os_press.php?os=<?=$os?>" target="_blank"><?=$sua_os?></a></td>
								<td><?=$data_abertura?></td>
								<td><?=$status_os?></td>
								<td><?=$posto_nome?></td>
								<td><?=$consumidor_nome?></td>
							</tr>
						<?php
						}
						?>
					</tbody>
			<?php }else{ ?>
				<div class="alert alert-warning">
					<h4>Nenhum resultado encontrado.</h4>
			    </div>
			<?}?>
		</div>
	</body>
</html>
