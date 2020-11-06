<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';
include '../helpdesk/mlg_funciones.php';

if (!function_exists('anti_injection')) {
	function anti_injection($string) {
		$a_limpa = array("'" => "", "%" => "", '"' => "", "\\" => "");
		return strtr(strip_tags(trim($string)), $a_limpa);
	}
}

if(isset($_GET['callcenter'])){
	$callcenter = anti_injection($_GET['callcenter']);
	if($callcenter == ""){
		$callcenter = false;
	}else{
		$pais     	= anti_injection($_GET['pais']);
		$cidade     = anti_injection($_GET['cidade']);
		$estado     = anti_injection($_GET['estado']);
		$pais       = anti_injection($_GET['pais']);
		$cep_orig   = anti_injection($_GET['cep']);
		$linha      = anti_injection($_GET['linha']);
		$consumidor = anti_injection($_GET['consumidor']);
		$estado     = ((!$estado or $estado == '00') and $consumidor) ? substr($consumidor, -2) : $estado;
		$nome_cliente = anti_injection($_GET['nome']);
		$endereco_rota = anti_injection($_GET['endereco_rota']);
	}
}else if(isset($_POST['pais'])){
	$pais =  $_POST['pais'];
	$estado = $_POST['estado'];
	$cidade = $_POST['cidade'];
	$fabrica = $_POST['fabrica'];

	if($login_fabrica == 52) {
		$consumidor_estado = $_GET['consumidor_estado'];
		$consumidor_cidade = $_GET['consumidor_cidade'];
	}
}

?>

<!DOCTYPE html>
<html>
	<head>
		<title>Telecontrol - Mapa da Rede Autorizada</title>
		<link type="text/css" rel="stylesheet" href="css/css.css" />
		<link type="text/css" rel="stylesheet" href="../plugins/jquery/tablesorter/themes/telecontrol/style.css" />
		<style>
			html {
				font-size: 12px;
			}

			#header {
				width: 1024px;
				height: 80px;
				position: relative;
				margin: auto;
				text-align: center;
			}

			#header #logo {
				float: left;
			}

			#header #mapa {
				float: left;
				width: 90px;
				margin-left: 30px;
			}

			#header h1 {
				float: left;
				margin-top: 20px;
				margin-left: 20px;
				color: #363A60;
			}

			#body {
				width: 1024px;
				position: relative;
				margin: auto;
				text-align: center
			}

			#body ul {
				margin-left: -20px;
			}

			#body ul li {
				text-align: left;
			}

			#footer {
				width: 1024px;
				position: relative;
				margin: auto;
			}

			#GoogleMaps{
				<?php
					$med = (isset($_GET['callcenter'])) ? "70%" : "1010px";
				?>
				width: <?=$med?>;
				height: 400px;
				border: 1px black solid;
				position: relative;
				margin-top: 20px;
				float: left;
				padding: 1px;
			}

			#direction{
				width: 29.2%;
				padding: 1px;
				height: 400px;
				border-top: 1px black solid;
				border-right: 1px black solid;
				border-bottom: 1px black solid;
				position: relative;
				margin-top: 20px;
				float: left;
				overflow: auto;
			}

			table {
				border-collapse: collapse;
				padding: 4px;
				font: 12px arial;
			}

			tr.posto:nth-child(2n) {
				background-color: #EEF;
			}

			td, th {
				text-align: center;
				font: 12px arial;
			}

			.titulo_tabela th{
				text-align: center;
				font: 12px arial;
				padding: 5px;
			}

			td {
				border-bottom: 1px #999 solid;
				font: 10px arial;
				padding-left: 5px;
			}

			tbody td {
				border-bottom: 1px #000 solid;
				font: 10px arial;
				padding: 5px;
				text-align: left;
			}

		</style>
		<script src="../js/jquery-1.6.2.js"></script>

		<!-- CSS e JavaScript Google Maps -->
		<link href="https://developers.google.com/maps/documentation/javascript/examples/default.css" rel="stylesheet">
		<script src="https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false&language=pt-br&key=AIzaSyC5AsH3NU-IwraXqLAa1qbXxyjklSVP-cQ"></script>

		<!-- <script src="https://maps.google.com/maps/api/js?key=AIzaSyBic82OzoO6ZfW0OJad500DnbH8LA9ljUs&sensor=false&language=pt-br"></script> -->
		<script src="http://google-maps-utility-library-v3.googlecode.com/svn/tags/markermanager/1.0/src/markermanager.js"></script>

		<script>

			function siglaEstado(sigla){

				switch(sigla){
					case "AC" : sigla = "Acre"; break;
					case "AL" : sigla = "Alagoas"; break;
					case "AP" : sigla = "Amapá"; break;
					case "AM" : sigla = "Amazonas"; break;
					case "BA" : sigla = "Bahia"; break;
					case "CE" : sigla = "Ceará"; break;
					case "DF" : sigla = "Distrito Federal"; break;
					case "ES" : sigla = "Espírito Santo"; break;
					case "GO" : sigla = "Goiás"; break;
					case "MA" : sigla = "Maranhão"; break;
					case "MT" : sigla = "Mato Grosso"; break;
					case "MS" : sigla = "Mato Grosso do Sul"; break;
					case "MG" : sigla = "Minas Gerais"; break;
					case "PA" : sigla = "Pará"; break;
					case "PB" : sigla = "Paraíba"; break;
					case "PR" : sigla = "Paraná"; break;
					case "PE" : sigla = "Pernambuco"; break;
					case "PI" : sigla = "Piauí"; break;
					case "RJ" : sigla = "Rio de Janeiro"; break;
					case "RN" : sigla = "Rio Grande do Norte"; break;
					case "RS" : sigla = "Rio Grande do Sul"; break;
					case "RO" : sigla = "Rondônia"; break;
					case "RR" : sigla = "Roraima"; break;
					case "SC" : sigla = "Santa Catarina"; break;
					case "SP" : sigla = "São Paulo"; break;
					case "SE" : sigla = "Sergipe"; break;
					case "TO" : sigla = "Tocantins"; break;
				}

				return sigla;

			}

			function retiraAcentos(palavra){

    			var com_acento = 'áàãâäéèêëíìîïóòõôöúùûüçÁÀÃÂÄÉÈÊËÍÌÎÏÓÒÕÖÔÚÙÛÜÇ';
    			var sem_acento = 'aaaaaeeeeiiiiooooouuuucAAAAAEEEEIIIIOOOOOUUUUC';
			    var newPalavra = "";

			    for(i = 0; i < palavra.length; i++) {
			    	if (com_acento.search(palavra.substr(i,1)) >= 0) {
			      		newPalavra += sem_acento.substr(com_acento.search(palavra.substr(i,1)),1);
			      	}
			      	else{
			       		newPalavra += palavra.substr(i,1);
			    	}
			    }

			    return newPalavra.toUpperCase();
			}

			/* - - - - - - - - - - - - - - - - - - - - - - - - - - */

			var table_excel;
			var pagina = "";

			var latlon;
			var latlonCliente = "";
			var marker;
			var markerClick;
			var map;
			var geocoder;
			var object;
			var marks    = new Array();
			var posto    = new Array();
			var infotype = new Array("nome_posto", "endereco", "cidade", "estado", "email", "telefone");

			<?php echo (isset($_GET['callcenter'])) ? '' : 'var bounds = new google.maps.LatLngBounds();'; ?>

			var directionsService;
			var directionsRenderer;
			var directionsDisplay;

			var qtdRotas = 0;

			var callcenter = '<?php echo $callcenter; ?>';
			var linha = '<?php echo $linha; ?>';

			function getText(posto, rel) {
				switch (rel) {
					case "nome_posto":
						var title = "<b>Nome do Posto:</b> ";
					break;

					case "endereco":
						var title = "<b>Endereço:</b> ";
					break;

					case "cidade":
						var title = "<b>Cidade:</b> ";
					break;

					case "estado":
						var title = "<b>Estado:</b> ";
					break;

					case "email":
						var title = "<b>Email:</b> ";
					break;

					case "telefone":
						var title = "<b>Telefone:</b> ";
					break;
				}

				return title + $.trim($("#"+posto).find("td[rel="+rel+"]").text());
			}

			function getInfo(posto){
				var content = new Array();

				for (var i in infotype) {
					content.push(getText(posto, infotype[i]));
				}

				return "<div style='text-align: left;'>" + content.join("<br />") + "</div>";
			}

			function loadGoogleMaps () {

				directionsDisplay = new google.maps.DirectionsRenderer();

				var latlng    = new google.maps.LatLng(-15.78014820, -47.92916980);
				var myOptions = {
				    zoom     : 4,
			    	center   : latlng,
			    	mapTypeId: google.maps.MapTypeId.ROADMAP
			    };
				map           = new google.maps.Map(document.getElementById("GoogleMaps"), myOptions);
				geocoder      = new google.maps.Geocoder();
				infowindow    = new google.maps.InfoWindow();

				/* Busca latitude e longitude do posto */

				var address = new Array();

				address[0] = ['-30.109058734777214, -51.17675765000001'];
				address[1] = ['-28.72046375870158, -49.37021089999996'];
				address[2] = ['-10.260079174978678, -48.34723435000001'];
				address[3] = ['-11.71302029605275, -49.06277620000003'];
				address[4] = ['-26.47368040790853, -49.08663330000002'];
				address[5] = ['-27.006315132807238, -48.61559340000002'];
				address[6] = ['-26.894698208054706, -48.673861199999976'];
				address[7] = ['-22.343474432020894, -49.05273199999999'];
				address[8] = ['-20.268522155864375, -40.29811625000002'];
				address[9] = ['-31.7635180098322, -52.34526925'];
				address[10] = ['-10.192518302855161, -48.317058549999956'];
				address[11] = ['-24.950028207388254, -53.44132539999998'];
				address[12] = ['-21.197146656158637, -47.770567349999965'];
				address[13] = ['-21.298827706191076, -50.34106970000005'];
				address[14] = ['-22.91245660671217, -43.202912399999946'];
				address[15] = ['-25.441046157554485, -49.26616364999995'];
				address[16] = ['-23.551525356921946, -46.61658554999997'];
				address[17] = ['-29.170813658864574, -51.147832549999976'];
				address[18] = ['-30.246945959259968, -54.92019005000003'];
				address[19] = ['-29.168597803395265, -51.190665249999995'];
				address[20] = ['-25.515633157579842, -54.58685009999999'];					
				address[21] = ['-21.784990674419184, -48.17846759999997'];
				address[22] = ['-30.005666059170572, -51.20704324999997'];
				address[23] = ['-27.601081608302366, -48.61937899999998'];
				address[24] = ['-26.351535707866315, -52.83667609999998'];
				address[25] = ['-22.827098606684284, -43.0591584'];
				address[26] = ['-23.42091875687891, -51.946205099999986'];
				address[27] = ['-23.66375030695898, -52.60163560000001'];
				address[28] = ['-24.950548307388438, -53.451003900000046'];
				address[29] = ['-23.30610235684113, -51.15516119999995'];
				address[30] = ['-26.082146657773546, -53.04796435000003'];
				address[31] = ['-12.695715103577525, -38.32910879999997'];
				address[32] = ['-12.884674008083362, -38.29614164999998'];
				address[33] = ['-26.915145708061807, -49.080724799999984'];
				address[34] = ['-22.932374606718664, -47.070112050000034'];

				for(var i = 0; i < address.length; i++){

					// console.log(i+" - "+address[i]);

					var latlon = address[i];
					latlon = latlon.toString();

					var parte = latlon.split(',');
					var lat = parte[0];
					var lng = parte[1];

					var latlng0 = new google.maps.LatLng(lat, lng);
					var marker0 = new google.maps.Marker({
						map: map,
						position: latlng0
					});
					var infowindow0 = new google.maps.InfoWindow({
						content: "<p align='left'><strong>Cliente:</strong> <?=$nome_cliente?> <br /> <strong>Endereço:</strong> <?=$consumidor?> </p>"
					});
					google.maps.event.addListener(marker0, 'click', function() {
						infowindow0.open(map,marker0);
					});

				}

			}

			function geraMarkers(lat, lng){

				var local = new google.maps.LatLng(lat, lng);

		    	marks.push(new google.maps.Marker({
					position: local,
					clickable: true,
					draggable: false,
					flat: false,
					id: posto
				}));

		    	bounds.extend(local);

			}

			/* Rota */

			function rota(from, to, enderecoPosto){

				var endCliete = "<?php echo $endereco_rota; ?>";
				var endPosto = enderecoPosto;

				/* Limpa as informações a cada Rota */

				$('#direction').html('');

				titleDirections();

				if(qtdRotas == 0){

					latlonCliente = from;

					directionsService = new google.maps.DirectionsService();

					directionsRenderer = new google.maps.DirectionsRenderer({suppressMarkers: true, zomm: 5});
					directionsRenderer.setMap(null);
					directionsRenderer.setMap(map);

					directionsDisplay.setPanel(document.getElementById('direction'));

					var request = {
						origin: from,
						destination: to,
						travelMode: google.maps.DirectionsTravelMode.DRIVING
					};

					directionsService.route(request, function(response, status){
						if (status == google.maps.DirectionsStatus.OK) {
							directionsRenderer.setDirections(response);
							directionsDisplay.setDirections(response);

							endPosto = $("tr.posto:eq(0) > td[rel=endereco] > span").attr("title");
							endPosto += ", "+$("tr.posto:eq(0) > td[rel=bairro]").text();
							endPosto += ", "+$("tr.posto:eq(0) > td[rel=cidade]").text();
							endPosto += ", "+$("tr.posto:eq(0) > td[rel=estado]").text();
							
							setTimeout(function(){
								$('.adp-placemark:eq(0)').html('');
								$('.adp-placemark:eq(1)').html('');
								$('.adp-placemark:eq(0)').html('<table><tr><td><img src="http://www.google.com/intl/en_us/mapfiles/ms/micons/blue-dot.png" /></td><td>'+endCliete+'</td></tr></table>');
								$('.adp-placemark:eq(1)').html('<table><tr><td><img src="imagens/Google_Maps_Marker_Red.gif" /></td><td>'+endPosto+'</td></tr></table>');
							}, 200);

						}else{
							alert("Não foi possível encontrar um posto num raio de 100km");
						}
					});

				}else{

					to = latlonCliente;

					loadGoogleMaps();

					directionsService = new google.maps.DirectionsService();

					directionsRenderer = new google.maps.DirectionsRenderer({suppressMarkers: true, zomm: 5});
					directionsRenderer.setMap(null);
					directionsRenderer.setMap(map);

					directionsDisplay.setPanel(document.getElementById('direction'));

					var request = {
						origin: to,
						destination: from,
						travelMode: google.maps.DirectionsTravelMode.DRIVING
					};

					directionsService.route(request, function(response, status){
						if (status == google.maps.DirectionsStatus.OK) {
							directionsRenderer.setDirections(response);
							directionsDisplay.setDirections(response);
							
							setTimeout(function(){
								$('.adp-placemark:eq(0)').html('');
								$('.adp-placemark:eq(1)').html('');
								$('.adp-placemark:eq(0)').html('<table><tr><td><img src="http://www.google.com/intl/en_us/mapfiles/ms/micons/blue-dot.png" /></td><td>'+endCliete+'</td></tr></table>');
								$('.adp-placemark:eq(1)').html('<table><tr><td><img src="imagens/Google_Maps_Marker_Red.gif" /></td><td>'+endPosto+'</td></tr></table>');
							}, 200);

						}
					});

				}

			}

			/* Localizar */
			function localizar (lat, lng, endereco, id){
				if (lat.length == 0 || lng.length == 0) {
					geocoder.geocode({ 'address': endereco }, function (result, status) {
						if (status == "OK") {

							var lat = null;
							var lng = null;

							lat = result[0].geometry.location.lat();
							lng = result[0].geometry.location.lng();

							if (lat && lng) {

								var location = new google.maps.LatLng(lat, lng);

								var marker = new google.maps.Marker({
									position: location,
									map: map
								});

								google.maps.event.addListener(marker, 'click', function(){
									infowindow.setContent(getInfo(id));
									infowindow.open(map,marker);
								});

								map.setCenter(new google.maps.LatLng(lat, lng));
								map.setZoom(15);

							}
						}else{
							alert('Endereço não Localizado! Verifique se os dados do Posto estão preenchidos e/ou corretos.');
						}
					});
				} else {

					var from = latlon;
					var to = lat+', '+lng;

					// rota(from, to);

					map.setCenter(new google.maps.LatLng(lat, lng));
					map.setZoom(15);
				}
			}

			/* titulo SetDirections */
			function titleDirections(){
				$('#direction').html('<h1 align="center">Guia de Direções</h1>');
			}

			/* Chama a Function de Loading do Maps quando o elemento window termina de carregar */
			google.maps.event.addDomListener(window, 'load', loadGoogleMaps);

		</script>
	</head>
	<body>
		<div id="header" >
			<img id="logo" src="../logos/logo_telecontrol_2013.png" />
			<h1>Mapa da Rede Autorizada</h1>
			<img id="mapa" src="../externos/mapa_rede/imagens/mapa_azul.gif" />
		</div>
		<div id="body" >
			<ul>
				<li>
					Podem haver postos que não apareçam no mapa, por estarem com o endereço incorreto.
				</li>
				<li>
					A localização dos postos não é exata, podendo haver margem de erro.
				</li>
				<li>
					Caso ele encontre o endereço, mas não consiga realizar a rota, tente remover o número da residência, o Google não mantem atualizado.
				</li>
			</ul>

			<br />

			<p align="center">
					<strong>Clique sobre os icones<img src="imagens/Google_Maps_Marker_Red.gif" style="margin-bottom: -10px;" alt="Icone Postos" />para ver informações detalhadas do posto</strong>
			</p>

			<div id="GoogleMaps" ></div>
			<?php echo (isset($_GET['callcenter'])) ? '<div id="direction" ></div>' : ''; ?>

			<div style="clear: both;"></div>

			<p align="left">

			<!-- Lengenda -->

			<?php echo (isset($_GET['callcenter'])) ? '<span id="icon-blue"><img src="http://www.google.com/intl/en_us/mapfiles/ms/micons/blue-dot.png" style="margin-bottom: -10px;" alt="Icone Cliente" /><strong>Cliente</strong></span> &nbsp; ' : ''; ?>
			<span id="icon-red"><img src="imagens/Google_Maps_Marker_Red.gif" style="margin-bottom: -10px;" alt="Icone Postos" /><strong>Postos Autorizados</strong><span>

			</p>

			<br />

			<table id="grid_postos" border="0" style="width: 100%;" >
				<thead>
					<tr class="titulo_tabela" >
						<th align="center">
							Nome do Posto
						</th>
						<th align="center">
							Endereço
						</th>
						<th align="center">
							Bairro
						</th>
						<th align="center">
							Cidade
						</th>
						<th align="center">
							UF
						</th>
						<th align="center">
							CEP
						</th>
						<th align="center">
							Email
						</th>
						<th align="center">
							Telefone
						</th>
						<?php

						// echo ($callcenter == true) ? "<th>DistÃ¢ncia</th>" : "";

						?>
						<th align="center">
							Localizar
						</th>
						<? echo ($callcenter == true) ? "<th align=\"center\">Rota</th>" : ""; ?>
					</tr>
				</thead>
				<tbody class="tbody"><!-- Contudo --></tbody>
			</table>
		</div>
		<div id="footer" >
		</div>

		<!-- Excel -->

		<?php

			// Fábricas que geram o excel (HD-936214)
			$fabricas_geram_excel = array(86, 81, 114);

			// Inicializa o arquivo XLS
			if(in_array($login_fabrica, $fabricas_geram_excel)){

				$caminho = "xls/relatorio-mapa-rede-$login_fabrica.xls";

				echo "<a href=\"$caminho\" target=\"_blank\" style=\"text-decoration: none; \"><img src=\"imagens/excel.png\" height=\"30px\" width=\"30px\" align=\"absmiddle\"> Gerar Arquivo Excel</a> <br /> <br />";
				//echo "<a href=\"#\" style=\"text-decoration: none; \" onclick=\"gerarExcel()\"><img src=\"imagens/excel.png\" height=\"30px\" width=\"30px\" align=\"absmiddle\"> Gerar Arquivo Excel</a> <span id=\"carregando_excel\"></span> <br /> <br />";

			}else{
				echo "<br /> <br />";
			}

		?>

		<!-- Fim Excel -->

	</body>
</html>
