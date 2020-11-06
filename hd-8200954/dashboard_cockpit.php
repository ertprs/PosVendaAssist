<?php 
	$dados_grafico = $_GET['dados'];	
	$dados_grafico = json_decode($dados_grafico, true);

	foreach($dados_grafico as $chave => $valor){
		$categories .= "'$chave'".",";

		$agendado .= $dados_grafico["$chave"]['Agendado']."  ,";
		$realizado .= $dados_grafico["$chave"]['Realizado']."  ,";
		$cancelado .= $dados_grafico["$chave"]['Cancelado']."  ,";
	}

	$series .= "{
	            name: 'Agendado',
	            data: [$agendado]
	        },{
	            name: 'Realizado',
	            data: [$realizado]
	        },{
	            name: 'Cancelado',
	            data: [$cancelado]
	        },";



?>


<script type="text/javascript" src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<!--highCharts-->
<script src="https://code.highcharts.com/highcharts.js"></script>
<script src="https://code.highcharts.com/modules/exporting.js"></script>
<script src="https://code.highcharts.com/modules/export-data.js"></script>
<script src="https://code.highcharts.com/modules/accessibility.js"></script>

<script >
	

	$(function(){

    var categories = "";

    Highcharts.setOptions({
        colors: ['#058DC7', '#50B432', '#FF0000']
    });

    Highcharts.chart('grafico1', {

        chart: {
            type: 'bar'
        },
        title: {
            text: 'TIPO DE ATENDIMENTO x STATUS'
        },
        subtitle: {
            text: ''
        },
        xAxis: {
            labels:{
                style:{
                    // color:'red',
                    fontSize:"13px",
                }
            },
            categories: [<?=$categories?>],
            title: {
                text: null
            }
        },
        yAxis: {
            min: 0,
            title: {
                text: 'Quantidade',
                align: 'high'
            },
            labels: {
                overflow: 'justify',
                style:{
                    //color:'green',
                    fontSize:"11px",
                }
            }
        },
        tooltip: {
            valueSuffix: ' '
        },
        plotOptions: {
            column: {
                
            },
            bar: {
                dataLabels: {
                    enabled: true,
                }
                
            },
            series: {
                dataLabels: {
                    enabled: true,
                    style: {
                        fontWeight: 'bold',
                        color: 'blue',
                        fontSize:"10px"
                    }
                }
            }
        },
        legend: {
            layout: 'vertical',
            align: 'right',
            verticalAlign: 'top',
            x: -40,
            y: -10,
            floating: true,
            borderWidth: 1,
            backgroundColor:
                Highcharts.defaultOptions.legend.backgroundColor || '#FFFFFF',
            shadow: true
        },
        credits: {
            enabled: false
        },
        series: [<?=$series?>]
    });


});


</script>
<link rel="stylesheet" type="text/css" href="highcharts.css" media="screen" />



<figure class="highcharts-figure">
    <div id="grafico1"></div>
   
    <input type="hidden" name="categories" id="categories" value='<?=$categories?>'>
</figure>
