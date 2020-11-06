<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="gerencia";
include 'autentica_admin.php';

if ($login_fabrica == 189) {
	$sqlTempo = 'minutes';
	$sqlPrazo = 'horas';
	$divisor = '60';
} else {
	$sqlTempo = 'hours';
	$sqlPrazo = 'dias';
	$divisor = '24';
}

$sql = "SELECT tbl_admin.admin,tbl_admin.login,
			(SELECT data FROM tbl_hd_chamado_item WHERE tbl_hd_chamado_item.hd_chamado = hd_chamado AND hd_motivo_ligacao IS NOT NULL ORDER BY hd_chamado_item DESC LIMIT 1) AS data_cadastro_providencia,
			COUNT(1) FILTER(WHERE tbl_hd_chamado.data_providencia < CURRENT_TIMESTAMP) AS em_atraso,
			COUNT(1) FILTER(WHERE tbl_hd_chamado.data_providencia > CURRENT_TIMESTAMP AND CURRENT_TIMESTAMP < tbl_hd_chamado.data_providencia - INTERVAL '1 {$sqlTempo}' * tbl_hd_motivo_ligacao.prazo_{$sqlPrazo} * {$divisor} * 0.5) AS no_prazo,
			COUNT(1) FILTER(WHERE tbl_hd_chamado.data_providencia > CURRENT_TIMESTAMP AND CURRENT_TIMESTAMP >= tbl_hd_chamado.data_providencia - INTERVAL '1 {$sqlTempo}' * tbl_hd_motivo_ligacao.prazo_{$sqlPrazo} * {$divisor} * 0.5 AND CURRENT_TIMESTAMP < tbl_hd_chamado.data_providencia - INTERVAL '1 {$sqlTempo}' * tbl_hd_motivo_ligacao.prazo_{$sqlPrazo} * {$divisor} * 0.2) AS prazo_50,
			COUNT(1) FILTER(WHERE tbl_hd_chamado.data_providencia > CURRENT_TIMESTAMP AND CURRENT_TIMESTAMP >= tbl_hd_chamado.data_providencia - INTERVAL '1 {$sqlTempo}' * tbl_hd_motivo_ligacao.prazo_{$sqlPrazo} * {$divisor} * 0.2) AS prazo_20
	INTO TEMP tmp_prazo_atendimentos
	FROM tbl_hd_chamado 
	JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
	JOIN tbl_hd_motivo_ligacao ON tbl_hd_motivo_ligacao.hd_motivo_ligacao = tbl_hd_chamado_extra.hd_motivo_ligacao AND tbl_hd_motivo_ligacao.fabrica = {$login_fabrica} AND tbl_hd_motivo_ligacao.prazo_{$sqlPrazo} IS NOT NULL
	JOIN tbl_admin ON tbl_hd_chamado.atendente = tbl_admin.admin AND tbl_admin.fabrica = {$login_fabrica}
	WHERE tbl_hd_chamado.fabrica_responsavel = {$login_fabrica}
	AND tbl_hd_chamado.status NOT IN('Cancelado','Resolvido')
	GROUP BY tbl_admin.admin,tbl_admin.login;

	SELECT * FROM tmp_prazo_atendimentos;";
$resSubmit = pg_query($con,$sql);

$layout_menu = "gerencia";

$title = "RELATÓRIO DE ACOMPANHAMENTO ATENDIMENTO CALLCENTER";

include "cabecalho_new.php";

$plugins = array(
    "autocomplete",
    "datepicker",
    "shadowbox",
    "mask",
    "dataTable",
    "highcharts"
);

include("plugin_loader.php");

if (isset($resSubmit)) {
    if (pg_num_rows($resSubmit) > 0) {
        echo "<br />";
        $count = pg_num_rows($resSubmit);
?>

<script>
	
	$(function(){
		
		Shadowbox.init();
		setTimeout(function(){ location.reload(); }, 180000);
	});

	function listaAtendimentos(admin,situacao,login){
		
		Shadowbox.open({
		    content: 'listagem_atendimentos_abertos.php?atendente='+admin+'&situacao='+situacao+'&login='+login,
		    player: 'iframe',
		    width: 1000,
		    height: 600
		});

	}
</script>

<table class='table table-striped table-bordered table-hover table-fixed' >
        <thead>
        <TR class='titulo_coluna' style='color:black !important;'>
            <th>Nome</th>
            <th style="background-color:red !important;">Em Atraso</th>
	    <th style="background-color:yellow !important;">20% do prazo restante</th>
	    <th>50% do prazo restante</th>
	    <th style="background-color:green !important;">No prazo</th>
        </TR>
    </thead>
    <tbody>
<?php

	for($i=0; $i < $count; $i++){
		
		$nome      = pg_fetch_result($resSubmit,$i,'login');
		$admin     = pg_fetch_result($resSubmit,$i,'admin');
		$em_atraso = pg_fetch_result($resSubmit,$i,'em_atraso');
		$no_prazo  = pg_fetch_result($resSubmit,$i,'no_prazo');
		$prazo_50  = pg_fetch_result($resSubmit,$i,'prazo_50');
		$prazo_20  = pg_fetch_result($resSubmit,$i,'prazo_20');

		echo "<tr>";
		echo "<td>{$nome}</td>";
		echo "<td class='tac'><a href='#' onclick='listaAtendimentos($admin,\"em_atraso\",\"{$nome}\")'>{$em_atraso}</a></td>";
		echo "<td class='tac'><a href='#' onclick='listaAtendimentos($admin,\"prazo_20\",\"{$nome}\")'>{$prazo_20}</a></td>";
		echo "<td class='tac'><a href='#' onclick='listaAtendimentos($admin,\"prazo_50\",\"{$nome}\")'>{$prazo_50}</a></td>";
		echo "<td class='tac'><a href='#' onclick='listaAtendimentos($admin,\"no_prazo\",\"{$nome}\")'>{$no_prazo}</a></td>";
		echo "</tr>";

	}
?>

    </tbody>
</table>

<?php
}
	$sql = "SELECT 	SUM(em_atraso) AS total_atraso,
			SUM(no_prazo)  AS total_prazo,
			SUM(prazo_50)  AS total_prazo50,
			SUM(prazo_20)  AS total_prazo20,
			SUM(coalesce(em_atraso,0) + coalesce(no_prazo,0) + coalesce(prazo_50,0) + coalesce(prazo_20,0) ) AS total_atendimentos
		FROM tmp_prazo_atendimentos";
	$res = pg_query($con,$sql);
	
	$total = (int) pg_fetch_result($res,0,'total_atendimentos');
	$total_atraso = (float) (pg_fetch_result($res,0,'total_atraso') * $total) / 100;
	$total_prazo  = (float) (pg_fetch_result($res,0,'total_prazo') * $total) / 100;
	$total_prazo_50 = (float) (pg_fetch_result($res,0,'total_prazo50') * $total) / 100;
	$total_prazo_20 = (float) (pg_fetch_result($res,0,'total_prazo20') * $total) / 100;

	$grafico = json_encode([
					["Em atraso", $total_atraso],
					["20% do prazo restante", $total_prazo_20],
					["50% do prazo restante", $total_prazo_50],
					["No prazo",$total_prazo]
                          ]);

?>
	<br>
	<div id="grafico_atendimentos"></div>
	<script>

		if ($("#grafico_atendimentos").length > 0) {
			$("#grafico_atendimentos").highcharts({
			    chart: {
				plotBackgroundColor: null,
				plotBorderWidth: null,
				plotShadow: false,
				type: "pie"
			    },
			    title: {
				text: "Atendimentos abertos: <?=$total?>"
			    },
			    plotOptions: {
				pie: {
				    allowPointSelect: true,
				    cursor: "pointer",
				    dataLabels: {
					enabled: false
				    },
				    showInLegend: true
				},
				series: {
				    point: {
					events: {
					    click: function() {
						var status = this.name;

						var filtros = "";
						switch (status) {

						    case "Em atraso":
							filtros = "situacao=em_atraso";
							break;

						    case "20% do prazo restante":
							filtros = "situacao=prazo_20";
							break;

						    case "50% do prazo restante":
							filtros = "situacao=prazo_50";
							break;

						    case "No prazo":
							filtros = "situacao=no_prazo";
							break; 									
						}

						//window.open("listagem_atendimentos_abertos.php?"+filtros);
						Shadowbox.open({
						    content: 'listagem_atendimentos_abertos.php?'+filtros,
						    player: 'iframe',
						    width: 1000,
						    height: 600
						});
					       
					    }
					}
				    }
				}
			    },
				series: [{
				name: "Status",
				colorByPoint: true,
				tooltip: {
				    pointFormat: "{name}<br />{point.y} Atendimentos",
				    useHTML: true
				},
				data: <?=$grafico?>,
				dataLabels: {
				    enabled: true,
				    formatter: function() {
					return this.point.name + " - " + Highcharts.numberFormat(parseFloat(this.percentage), 2, ",", ".") + "%";
				    }
				}
			    }],
				colors: ['red', 'yellow', 'blue', 'green']
			});
		    }		
	</script>
<?php

}

include "rodape.php";
?>
