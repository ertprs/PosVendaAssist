<?php 
	$dados_grafico = $_GET['dados'];	
    $totalGeral = $_GET['total'];    
	$dados_grafico = json_decode($dados_grafico, true);

    //$textVertical = utf8_decode('Pontuação');


	foreach($dados_grafico as $chave => $valor){
		$categories .= "'$chave'".",";

        foreach($valor as $statusApp){
            $total[$chave] += $statusApp;
        } 

		/*$agendado .= $dados_grafico["$chave"]['Agendado']."  ,";
		$realizado .= $dados_grafico["$chave"]['Realizado']."  ,";
		$cancelado .= $dados_grafico["$chave"]['Cancelado']."  ,";*/

        $agendado[] = $dados_grafico["$chave"]['Agendado']; 
        $realizado[] = $dados_grafico["$chave"]['Realizado']; 
        $cancelado[] = $dados_grafico["$chave"]['Cancelado']; 

        $nps[] = number_format((($total[$chave] * 100)/ $totalGeral), 2, '.', '');

	}

?>


<script type="text/javascript" src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<!--highCharts-->
<script src="https://code.highcharts.com/highcharts.js"></script>
<script src="https://code.highcharts.com/modules/series-label.js"></script>
<script src="https://code.highcharts.com/modules/exporting.js"></script>
<script src="https://code.highcharts.com/modules/export-data.js"></script>
<script src="https://code.highcharts.com/modules/accessibility.js"></script>


<script >

    $(function(){

        Highcharts.setOptions({
            colors: ['#058DC7', '#50B432', '#FF0000', '#FF8000']
        });

        Highcharts.chart('grafico2', {
        chart: {
            type: 'column'
        },
        title: {
            text: 'RESULTADO POR TIPO DE ATENDIMENTO'
        },
        xAxis: {
            categories: [<?=$categories?>]
        },
        yAxis: {
            min: 0,
            title: {
                text: '<?=$textVertical?>'
            }
        },
        tooltip: {
            pointFormat: '<span style="color:{series.color}">{series.name}</span>: <b>{point.y}</b> ({point.percentage:.0f}%)<br/>',
            shared: true
        },
        plotOptions: {
            column: {
                stacking: 'percent'
            }
        },
        series: [{
            name: 'Agendado',
            data: [<? echo implode(",", $agendado); ?>]
        }, {
            name: 'Realizado',
            data: [<? echo implode(",", $realizado); ?>]
        }, {
            name: 'Cancelado',
            data: [<? echo implode(",", $cancelado)?>]
        },{
            name: 'NPS',
            type: 'spline',
            data: [<? echo implode(",", $nps) ?>],
            tooltip: {
                valueSuffix: '%'
            }
        }]
    });
});

</script>
<link rel="stylesheet" type="text/css" href="highcharts_2.css" media="screen" />



<figure class="highcharts-figure">
    <div id="grafico2"></div>
   
    <input type="hidden" name="categories" id="categories" value='<?=$categories?>'>
</figure>
