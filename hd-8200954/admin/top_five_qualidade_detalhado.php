<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';
include 'funcoes.php';

$familia 	= $_REQUEST['familia'];
$mes_ano 	= $_REQUEST['mes_ano'];

if (!empty($familia) AND !empty($mes_ano)) {
	$origem   = $_REQUEST["origem"];
	$linha 	  = $_REQUEST["linha"];
	$pesquisa = $_REQUEST["pesquisa"];

	// Familia //
	$sql = "SELECT familia FROM tbl_familia WHERE fabrica = {$login_fabrica} 
			AND UPPER(fn_retira_especiais(descricao)) ILIKE UPPER(fn_retira_especiais(trim('$familia')))
			LIMIT 1";
	$res = pg_query($con, $sql);
	if (pg_num_rows($res) > 0){
		$familia = pg_fetch_result($res, 0, 'familia');
		$cond_familia = " AND f.familia IN ({$familia}) ";
	}

	// Mes/Ano
	if ($pesquisa == "mes_ano"){
		$mes_ano = explode("/", $mes_ano);
		$mes 	 = $mes_ano[0];
	    $ano 	 = $mes_ano[1];
	
	    $cond_ano = " AND DATE_PART('year', o.data_abertura) IN ($ano) ";
		$cond_mes = " AND DATE_PART('month', o.data_abertura) IN ($mes) ";
	}else{
		$ano = $mes_ano;
		$cond_ano = " AND DATE_PART('year', o.data_abertura) IN ($ano) ";
	}
	
	if (!empty($linha)){
		$cond_linha = " AND p.nome_comercial IN ({$linha}) ";
	}

	if (!empty($origem)){
		$cond_origem = " AND p.origem IN ({$origem}) ";
	}

	$sql = "
		SELECT
			qtde_os,
			produto_referencia,
			produto_descricao,
			familia_descricao
		FROM(
			SELECT
				ROW_NUMBER() OVER (PARTITION BY familia_descricao ORDER BY qtde_os DESC) AS r,
				qtde_os,
				produto_referencia,
				produto_descricao,
				familia_descricao
			FROM(
				SELECT
					COUNT(o.os) AS qtde_os,
					p.referencia AS produto_referencia,
					p.descricao AS produto_descricao,
					f.descricao AS familia_descricao
				FROM tbl_os o
				JOIN tbl_os_produto op USING(os)
				JOIN tbl_produto p ON p.produto = op.produto AND p.fabrica_i = {$login_fabrica}
				JOIN(
						SELECT DISTINCT
							COUNT(o.os) AS qtde_os,
							f.familia,
							f.descricao
						FROM tbl_os o
						JOIN tbl_os_produto op USING(os)
						JOIN tbl_produto p ON p.produto = op.produto AND p.fabrica_i = {$login_fabrica}
						JOIN tbl_familia f ON f.familia = p.familia AND f.fabrica = {$login_fabrica}
						WHERE o.fabrica = {$login_fabrica}
						AND o.excluida IS NOT TRUE
						{$cond_familia}
						{$cond_mes}
						{$cond_ano}
						{$cond_linha}
						{$cond_origem}
						GROUP BY f.familia, f.descricao
						ORDER BY qtde_os DESC
					)f ON f.familia = p.familia
				WHERE o.fabrica = {$login_fabrica}
				AND o.excluida IS NOT TRUE
				{$cond_familia}
				{$cond_mes}
				{$cond_ano}
				{$cond_linha}
				{$cond_origem}
				GROUP BY p.referencia, p.descricao, f.familia, f.descricao
				ORDER BY qtde_os DESC
			) x
		) xx
		ORDER BY familia_descricao, qtde_os; ";
	$res = pg_query($con, $sql);
    
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
		$("#qualidade_detalhado").DataTable({
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
				<table id="qualidade_detalhado" class="display nowrap" cellspacing="0" width="100%" >
					<thead>
						<tr class='titulo_coluna' >
							<th>Ref. Produto</th>
							<th>Desc. Produto</th>
							<th>Familia</th>
							<th>Qtde OS</th>
	                    </tr>
					</thead>
					<tbody>
						<?php for ($i=0; $i < pg_num_rows($res); $i++) {
							$qtde_os 			= pg_fetch_result($res, $i, 'qtde_os');
							$produto_referencia	= pg_fetch_result($res, $i, 'produto_referencia');
							$produto_descricao 	= pg_fetch_result($res, $i, 'produto_descricao');
							$familia_descricao 	= pg_fetch_result($res, $i, 'familia_descricao');
						?>
							<tr>
								<td><?=$produto_referencia?></td>
								<td><?=$produto_descricao?></td>
								<td><?=$familia_descricao?></td>
								<td><?=$qtde_os?></td>
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
