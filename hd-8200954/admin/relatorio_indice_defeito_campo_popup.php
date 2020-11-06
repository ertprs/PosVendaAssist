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

		var qtde = "[" + window.opener.qtdes.join(",") + "]";
		qtde = eval(qtde);
		var idc = "[" + window.opener.idc.join(",") + "]";
		idc = eval(idc);
		var meta = "[" + window.opener.meta.join(",") + "]";
		meta = eval(meta);
		var titulo;
//		qtde_defeitos = eval(qtde_defeitos);
		var familia = $( ".familia_titulo option:selected", window.opener.document ).text();
		var produto = $( ".produto_referencia", window.opener.document ).val();

		if(familia != ""){
			titulo = familia;
		}
		if(produto != ""){
			titulo = produto;
		}
		

	chart = new Highcharts.Chart({
		chart: {
			renderTo: 'container',
			type: 'line'
		},
		title: {
			text: 'IDC -' + titulo
		},
		subtitle: {
			text: ''
		},
		xAxis: {
			categories:  window.opener.meses
		},
		yAxis: {
			title: {
				text: 'Quantidade'
			}
		},
		tooltip: {
			enabled: false,
			formatter: function() {
				return '<b>'+ this.series.name +'</b><br/>'+
					this.x +': '+ this.y +'Â°C';
			}
		},
		plotOptions: {
			line: {
				dataLabels: {
					enabled: true
				},
				enableMouseTracking: false
			}
		},
		series: [{
			name: 'Meta',
			data: meta
		}, {
			name: 'IDC',
			data: idc
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
