<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="gerencia";
include 'autentica_admin.php';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<!-- 1. Add these JavaScript inclusions in the head of your page -->
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.6.1/jquery.min.js"></script>
<script type="text/javascript" src="js/grafico/highcharts.js"></script>
<!-- 2. Add the JavaScript to initialize the chart on document ready -->
<style type="text/css">
.container{
	 max-width: 1400px; 
	 height: 630px; 
	 margin-top: 20px;
	 margin-left: 5px;
}
@media screen{
  .screen_info {
  font-family:verdana,sans-serif;
  font-size:14px;
  width: 480px;
  height: 16px;
  padding: 4px;
  border: solid 2px #4572a7;
  border-radius: 8px 8px 8px 8px;
  }
}
@media print {
   .screen_info{
   display: none;
  }
 }
</style>
<script type="text/javascript">
var chart;
$(document).ready(function(){
		//Realmente, parece gambiarra, e é! Agradeçam a Microsof Ebano. Sem isto não funciona no IE
		var qtde_defeitos = "[" + window.opener.qtdes.join(",") + "]";
		qtde_defeitos = eval(qtde_defeitos);
		var periodo = $( "#grafico_mes_ano", window.opener.document ).val();
		var linha = $( "#produto_linha option:selected", window.opener.document ).text();
		linha = linha != "Selecione" ? " Linha "+linha : "";
		parts = periodo.split("-");
		periodo = parts[1]+"/"+parts[0];
		chart = new Highcharts.Chart({
			chart: {
				renderTo: 'container',
				defaultSeriesType: 'column'
					//margin: [ 10, 10, 20, 40]
			},
			title: {
				text: 'Relatório de Índice de Ocorrência Mensal '+ periodo + linha
			},
			xAxis: {
				categories: window.opener.defeitos,
				labels: {
					rotation: -90,
					align: 'right',
					style: {
						 font: 'normal 11px Verdana, sans-serif'
					}
				}
			},
			yAxis: {
				min: 0,
				title: {
					text: 'Ocorrências '+ periodo
				}
			},
			legend: {
				enabled: true
			},
			tooltip: {
				formatter: function() {
					return '<b>'+ this.x +'</b><br/>'+
						 'Ocorrências: '+ Highcharts.numberFormat(this.y, 1) +
						 ' casos';
				}
			},
		        series: [{
				name: 'Quantidade de Ocorrências',
				data: qtde_defeitos, //[window.opener.qtdes[1], 2, 3, 4, 5], //window.opener.qtdes,
				dataLabels: {
					enabled: true,
					rotation: 0,
					color: '#000000',
					align: 'left',
					x: -1,
					y: -2,
					formatter: function() {
						return this.y;
					},
					style: {
						font: 'normal 11px Verdana, sans-serif'
					}
				}			
			}]
		});

});

</script>
</head>
<body>
<div class='screen_info'>Para imprimir este gráfico utilize o papel na orientação paisagem.</div>
<div id='container' class='container'></div>
</body>
</html>
