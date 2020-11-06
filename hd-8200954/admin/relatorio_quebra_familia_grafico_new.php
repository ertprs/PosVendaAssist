<?

$mesesChart = json_encode($mes);

foreach ($arrayChart as $serie) {
            $chartData[] = array("name" => $serie['name'], "data" => $serie['data']);
}

//var_dump($chartData);

$seriesChart = json_encode($chartData);

$dadosArray = count($chartData);

if ($dadosArray <= 10) {
            $divStyle = 'style="height: 500px;"';
} else if ($dadosArray > 10) {
            $divStyle = 'style="height: 1000px;"';
} else if ($dadosArray > 20) {
            $divStyle = 'style="height: 1500px;"';
} else if ($dadosArray > 30) {
            $divStyle = 'style="height: 2500px;"';
}

?>

<script>

$(function () {
            $('#relatorio_quebra_familia_grafico').highcharts({
                        chart: {
                                    type: 'bar',
                                    zoomType: 'x'
                        },
                        title: {
                                    text: 'Gráfico - Quebra Por Familia (Últimos 12 meses)'
                        },
                        xAxis: {
                                    categories: <?= $mesesChart ?>
                        },
                        yAxis: {
                                    title: {
                                                text: 'Quantidade de Produtos Quebrados'
                                    }
                        },
                        series: eval(<?= $seriesChart; ?>),
            });
});

</script>

<div id="relatorio_quebra_familia_grafico" class="container" <?= $divStyle; ?>></div>