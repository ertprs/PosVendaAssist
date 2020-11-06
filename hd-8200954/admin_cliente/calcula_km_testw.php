<script type='text/javascript' src='js/jquery.js'></script>


<script src="http://maps.google.com/maps?file=api&amp;v=2&amp;key=ABQIAAAA4k5ZzVjDVAWrCyj3hmFzTxR_fGCUxdSNOqIGjCnpXy7SRGDdcRTb85b5W8d9rUg4N-hhOItnZScQwQ" type="text/javascript"></script>

<script language="javascript">
var map;
function buscaKM(busca_por2) {

var busca_por  = "<?=$_GET['buscar_por']?>";
var cep = "<?=$_GET['cep']?>";
var endereco = "<?=$_GET['endereco']?>";
var numero = "<?=$_GET['numero']?>";
var bairro = "<?=$_GET['bairro']?>";
var consumidor_cidade = "<?=$_GET['cidade']?>";
var consumidor_estado = "<?=$_GET['estado']?>";
var posto = "<?=$_GET['posto']?>";
var cep_posto = "<?=$_GET['cep_posto']?>";
var end_posto = "<?=$_GET['end_posto']?>";

	// Carrega o Google Maps
	if (GBrowserIsCompatible()) {
		map = new GMap2(document.getElementById("mapa"));
		map.setCenter(new GLatLng(-25.429722,-49.271944), 11);
		var dir = new GDirections(map);

		var pt1 = cep_posto;
		var pt2 = cep;

		pt1 = pt1.replace('-','');
		pt2 = pt2.replace('-','');

		if (pt1.length != 8 || pt2.length !=8) {
			busca_por = 'endereco';
		}else{
			pt1 = pt1.substr(0,5) + '-' + pt1.substr(5,3);
			pt2 = pt2.substr(0,5) + '-' + pt2.substr(5,3);
		}

		if (busca_por2 == 'endereco'){
			var pt1 = end_posto;
			var pt2 = endereco+","+numero+" "+bairro+" "+consumidor_cidade+" "+consumidor_estado;
		}

		dir.loadFromWaypoints([pt1,pt2], {locale:"pt-br", getSteps:true});

		GEvent.addListener(dir,"load", function() {
			for (var i=0; i<dir.getNumRoutes(); i++) {
				var route = dir.getRoute(i);
				var dist = route.getDistance()
				var x = dist.meters*2/1000;
				var y = x.toString().replace(".",",");
				var valor_calculado = parseFloat(x);
				if (valor_calculado==0 && busca_por2 != 'endereco'){
					//buscaKm('endereco');
					buscaKM('endereco');
					return false;
				}
			}
			$('#resposta').html(y);
			if (x > 999){
				alert('Kilometragem maior que 999KM, Tem certeza que quer continuar?');
			}
		});
	}
	GEvent.addListener(dir,"error", function() {
			alert('Não calculou a distância devido a um retorno inválido do GOOGLE MAPS. Favor clicar no botão MAPA e tentar localizar manualmente, caso a Kilometragem seja encontrada automaticamente, clique no posto encontrado que será considerado a kilometragem.');
			//buscaKm('endereco');
			//buscaKm('endereco',cep,endereco,numero,bairro, consumidor_cidade,consumidor_estado);
	});
}


$().ready(function() {
	buscaKM();
})

</script>
<div style='display:block' id='resposta'></div>
<div style='display:none' id='mapa'></div>