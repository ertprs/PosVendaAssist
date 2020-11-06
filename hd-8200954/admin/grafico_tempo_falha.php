<?php
$areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;

include __DIR__.'/dbconfig.php';
include __DIR__.'/includes/dbconnect-inc.php';

if ($areaAdmin === true) {
    include __DIR__.'/autentica_admin.php';
} else {
    include __DIR__.'/autentica_usuario.php';
}



$chave_pesquisa    = $_GET["chave_pesquisa"];
$data_inicial      = $_GET["data_inicial"];
$data_final        = $_GET["data_final"];
$rolling           = $_GET["rolling"];
$limiteComponentes = $_GET["limite_componentes"];

if ($tipo_pesquisa == "linha_pd") {

	$descricaoPesquisa = "<strong>(Filtro por PD)</strong><br /> ".$chave_pesquisa;
	$condPesquisa = "AND tbl_produto.nome_comercial = '{$chave_pesquisa}'";

} else if ($tipo_pesquisa == "familia") {

	$sqlDesc = "SELECT descricao
				FROM tbl_familia
				WHERE familia = {$chave_pesquisa}";
	$resDesc = pg_query($con, $sqlDesc);

	$descricaoPesquisa = "<strong>(Filtro por Família)</strong><br /> ".pg_fetch_result($resDesc, 0, 'descricao');
	$condPesquisa = "AND tbl_produto.familia = {$chave_pesquisa}";

} else {

	$sqlDesc = "SELECT descricao
				FROM tbl_produto
				WHERE produto = {$chave_pesquisa}";
	$resDesc = pg_query($con, $sqlDesc);

	$descricaoPesquisa = "<strong>(Filtro por Produto)</strong><br /> ".pg_fetch_result($resDesc, 0, 'descricao');

	$condPesquisa = "AND tbl_produto.produto = $chave_pesquisa";
}

$sqlPecas = "SELECT DISTINCT top.peca
			 FROM (
				SELECT tbl_peca.peca,
					   COUNT(*) as total
				FROM tbl_peca
				JOIN tbl_os_item ON tbl_os_item.peca = tbl_peca.peca
				JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
				JOIN tbl_os ON tbl_os_produto.os = tbl_os.os
				JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
				WHERE data_abertura BETWEEN '{$data_inicial}' AND '{$data_final}'
				{$condPesquisa}
				AND tbl_os.fabrica = {$login_fabrica}
				GROUP BY tbl_peca.peca
				ORDER BY total DESC
				LIMIT {$limiteComponentes}
			 ) top";
$resPecas = pg_query($con, $sqlPecas);

while ($dadosPeca = pg_fetch_object($resPecas)) {
	$pecas[] = $dadosPeca->peca;
}

$sql = "
			SELECT tbl_peca.peca,
				   TO_CHAR(tbl_os.data_abertura, 'mm/yyyy') as mes_ano,
				   COUNT(*) as total
			FROM tbl_peca
			JOIN tbl_os_item ON tbl_os_item.peca = tbl_peca.peca
			JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
			JOIN tbl_os ON tbl_os_produto.os = tbl_os.os
			JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
			WHERE data_abertura BETWEEN '{$data_inicial}' AND '{$data_final}'
			{$condPesquisa}
			AND tbl_os.fabrica = {$login_fabrica}
			AND tbl_peca.peca IN (
				{$sqlPecas}
			)
			GROUP BY tbl_peca.peca,
					 mes_ano
			ORDER BY total DESC";
$res = pg_query($con, $sql);

$qtdeTotalQuebras = 0;
while ($dados = pg_fetch_object($res)) {

	$qtdeTotalQuebras += $dados->total;
	$arrMesPeca[$dados->mes_ano][$dados->peca] = (int) $dados->total;

}

$sqlMes = "SELECT TO_CHAR(meses, 'mm/yyyy') as mes_ano
		FROM generate_series(
		'{$data_inicial}'::date,
		'{$data_final}'::date,
		'1 month'::interval
		) meses";
$resMes = pg_query($con, $sqlMes);

$arrGrafico = [];
foreach ($pecas as $peca) {

	$arrGrafico[] = [
		"name" => $peca,
		"data" => [],
		"zIndex" => 1,
		"tooltip" => [
			"headerFormat" => "<b>{point.x}</b><br/>",
			"pointFormat" => "{series.name}: {point.y}<br/>Total: {point.stackTotal}"
		],
	];

}

while ($dadosMes = pg_fetch_object($resMes)) {

	$arrGraficoMes[] = $dadosMes->mes_ano;
	$valorTotalMes   = 0;

	foreach ($pecas as $peca) {

		$valorTotal = 0;
		if (isset($arrMesPeca[$dadosMes->mes_ano][$peca])) {
			$valorTotal = $arrMesPeca[$dadosMes->mes_ano][$peca];
		}
		
		foreach ($arrGrafico as $key => $value) {

			if ($value["name"] == $peca) {

				$arrGrafico[$key]["data"][] = $valorTotal;

			}

		}

		$valorTotalMes += $valorTotal;

	}

	$porcentagemTotal = (int) number_format((($valorTotalMes * 100) / $qtdeTotalQuebras), 2);

	$porcentagemMes[] = $porcentagemTotal;
	$porcentagemMesTabela[$dadosMes->mes_ano] = $porcentagemTotal;

}

$arrGrafico[] = [
	"name" => "Porcentagem",
	"data" => $porcentagemMes,
	"yAxis" => 1,
	"type"  => "spline",
	"zIndex" => 10,
	"tooltip" => [
		"headerFormat" => "<b>{point.x}</b><br/>",
		"pointFormat" => "{point.y}%"
	],
];

foreach ($arrGrafico as $key => $val) {

	$sqlDesPeca = "SELECT descricao
				   FROM tbl_peca
				   WHERE peca = ".$val['name'];
	$resDesPeca = pg_query($con, $sqlDesPeca);

	$arrGrafico[$key]["name"] = utf8_encode(pg_fetch_result($resDesPeca, 0, 'descricao'));

}

$jsonGraficoMes = json_encode($arrGraficoMes);
$jsonGrafico    = json_encode($arrGrafico);

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
		<script src="bootstrap/js/bootstrap.js"></script>
		<script type="text/javascript" src="plugins/shadowbox/shadowbox.js"></script>
		<link type="text/css" href="plugins/shadowbox/shadowbox.css" rel="stylesheet" media="all">
		<script src="https://code.highcharts.com/highcharts.js"></script>
		<script src="https://code.highcharts.com/modules/exporting.js"></script>
		<script src="https://code.highcharts.com/modules/export-data.js"></script>
		<style>

			* {
				text-align: center;
			}

			.btn-pergunta {
				width: 90%;
			}
			
		</style>

	</head>
	<body>
		<div id="grafico" style="min-width: 600px; height: 500px; margin: 0 auto;"></div>
		<table class="table table-bordered">
	        <tr class="titulo_coluna">
	            <th>Mês/Ano</th>
	            <?php
	            $arrPd = [];
	            foreach ($pecas as $peca) {

	            	$sqlDesPeca = "SELECT descricao
								   FROM tbl_peca
								   WHERE peca = {$peca}";
					$resDesPeca = pg_query($con, $sqlDesPeca); ?>
					<th><?= pg_fetch_result($resDesPeca, 0, 'descricao') ?></th>
				<?php
	            }
	            ?>
	            <th>%</th>
	        </tr>
	        <?php
	        $resMes = pg_query($con, $sqlMes);
	        while ($dadosMes = pg_fetch_object($resMes)) { ?>
	        	<tr>
	        		<td class="tac" style="background-color: lightgray;font-weight: bolder;"><?= $dadosMes->mes_ano ?></td>
	        		<?php
	        		foreach ($pecas as $peca) { ?>
	        			<td class="tac"><?= (int) $arrMesPeca[$dadosMes->mes_ano][$peca] ?></td>
	        		<?php
	        		} ?>
	        		<td class="tac"><?= $porcentagemMesTabela[$dadosMes->mes_ano] ?>%</td>
	        	</tr>
	        <?php
	    	}
	        ?>
	    </table>
		<script>
			Highcharts.chart('grafico', {
		    chart: {
		        type: 'column',
		        marginBottom: 130
		    },
		    title: {
		        text: '<?= $descricaoPesquisa ?> Top <?= $limiteComponentes ?> & Time Fail - <?= $rolling ?>m Rolling data'
		    },
		    xAxis: {
		        categories: <?= $jsonGraficoMes ?>
		    },
		    yAxis: [{
		        min: 0,
		        title: {
		            text: 'Total Quebras'
		        },
		        stackLabels: {
		            enabled: true,
		            style: {
		                fontWeight: 'bold',
		                color: ( // theme
		                    Highcharts.defaultOptions.title.style &&
		                    Highcharts.defaultOptions.title.style.color
		                ) || 'gray'
		            }
		        }
		    },{ // Secondary yAxis
                title: {
                    text: '% Month Fail'
                },
                labels: {
                    format: '{value} %'
                },
                opposite: true
            }],
		    legend: {
		        align: 'center',
		        x: 0,
		        verticalAlign: 'bottom',
		        y: 0,
		        floating: true,
		        backgroundColor:
		            Highcharts.defaultOptions.legend.backgroundColor || 'white',
		        borderColor: '#CCC',
		        borderWidth: 1,
		        shadow: false,
		        width: 800
		    },
		    plotOptions: {
		        column: {
		            stacking: 'normal',
		            dataLabels: {
		                enabled: true
		            }
		        }
		    },
		    series: <?= $jsonGrafico ?>
		});
		</script>
	</body>
</html>