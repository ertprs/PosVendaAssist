<?php

include 'dbconfig.php';
include 'dbconnect-inc.php';
$admin_privilegios = "call_center";
include 'autentica_admin.php';

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
		<link type="text/css" rel="stylesheet" href="../admin/css/css.css" />
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

				<?php if(isset($_GET['callcenter'])){ ?>

				/* Busca latitude e longitude do posto */

				var address = '<?php echo $consumidor; ?>';

				geocoder.geocode( { 'address': address}, function(results, status) {
					if (status == google.maps.GeocoderStatus.OK) {

						/* Endereço retornado pelo Google */
						var destino = results[0].formatted_address;
						var cidadeConsumidor = '<?=$consumidor_cidade;?>';
						var estadoConsumidor = '<?=$consumidor_estado;?>';

						var cidadesIguais = 0;
						var estadosIguais = 0;

						/* Reescreve a Sigla do estado para o nome completo */
						estadoConsumidor = siglaEstado(estadoConsumidor);

						var comp1 	= new Array();
						var comp2 	= new Array();
						var seq 	= 0;

						destino = destino.replace(/\d{5}-\d{3},/g,'');
						destino = destino.replace(/-/g,',');
						comp1 = destino.split(",");
						var c1 = comp1.length;

						var cidadeComp = comp1[c1-3];
						var estadoComp = comp1[c1-2];

						if(cidadeComp.length > 0){
							cidadeComp = retiraAcentos(cidadeComp);
							cidadeConsumidor = retiraAcentos(cidadeConsumidor);
						}

						if(estadoComp.length > 0){
							estadoComp = retiraAcentos(estadoComp);
							estadoConsumidor = retiraAcentos(estadoConsumidor);
						}

						/* Compara se a cidade e o estado estão corretos */
						if(estadoComp.length > 0){
							if(estadoComp.trim() == estadoConsumidor.trim() || estadoComp.trim() == cidadeConsumidor.trim()){
								cidadesIguais++;
								estadosIguais++;
							}
						}

						if(cidadesIguais == 0 && estadosIguais == 0){
							if(cidadeComp.trim().length > 0){
								if(cidadeComp.trim() == cidadeConsumidor.trim()){
									cidadesIguais++;
								}
							}
							if(estadoComp.trim() == estadoConsumidor.trim()){
								estadosIguais++;
							}
						}


						if(cidadesIguais != 0 && estadosIguais != 0){

							// O Google localizou o endereço na cidade solicitada

							latlon = results[0].geometry.location;
							latlon = latlon.toString();

							$.ajax({
								url: 'mapa_rede_ajax.php',
								type: 'get',
								data: "latlon="+latlon+"&callcenter="+callcenter+"&linha="+linha<?php echo ($login_fabrica == 52) ? "+\"&consumidor_estado=\"+consumidor_estado+\"&consumidor_cidade=\"+consumidor_cidade" : ""; ?>,
								success: function(data){

									/* Marker Cliente */

									latlon = latlon.replace("(", "");
									latlon = latlon.replace(")", "");

									var parte = latlon.split(',');
									var lat = parte[0];
									var lng = parte[1];

									var latlng0 = new google.maps.LatLng(lat, lng);
									var marker0 = new google.maps.Marker({
										icon: 'http://www.google.com/intl/en_us/mapfiles/ms/micons/blue-dot.png',
										map: map,
										position: latlng0
									});
									var infowindow0 = new google.maps.InfoWindow({
										content: "<p align='left'><strong>Cliente:</strong> <?=$nome_cliente?> <br /> <strong>EndereÃ§o:</strong> <?=$consumidor?> </p>"
									});
									google.maps.event.addListener(marker0, 'click', function() {
										infowindow0.open(map,marker0);
									});
									/* Fim Marker Cliente */

									data = data.split('*');

									/* Realiza a Rota do posto ao cliente mais proximo */

									var from = latlon;
									var to = data[0];

									if(qtdRotas == 0){
										rota(from, to);
									}

									qtdRotas = 1;

									/* Lista InformaÃ§Ãµes dos Postos */
									$('.tbody').html(data[1]);
									table_excel = data[1];

									/*  Gera Markers*/
									geraMarkers();
								}
							});

						}else{

							/* Se o Google não retornou o endereço certo na cidade, o mesmo busca novamente, porem apenas com a cidade e estado como paramentros */

							var address = cidadeConsumidor+", "+estadoConsumidor+", Brasil";
							geocoder.geocode( { 'address': address}, function(results, status) {
								if (status == google.maps.GeocoderStatus.OK) {
									latlon = results[0].geometry.location;
									latlon = latlon.toString();

									$.ajax({
										url: 'mapa_rede_ajax.php',
										type: 'get',
										data: "latlon="+latlon+"&callcenter="+callcenter+"&linha="+linha<?php echo ($login_fabrica == 52) ? "+\"&consumidor_estado=\"+consumidor_estado+\"&consumidor_cidade=\"+consumidor_cidade" : ""; ?>,
										success: function(data){

											/* Marker Cliente */

											latlon = latlon.replace("(", "");
											latlon = latlon.replace(")", "");

											var parte = latlon.split(',');
											var lat = parte[0];
											var lng = parte[1];

											var latlng0 = new google.maps.LatLng(lat, lng);
											var marker0 = new google.maps.Marker({
												icon: 'http://www.google.com/intl/en_us/mapfiles/ms/micons/blue-dot.png',
												map: map,
												position: latlng0
											});
											var infowindow0 = new google.maps.InfoWindow({
												content: "<p align='left'><strong>Cliente:</strong> <?=$nome_cliente?> <br /> <strong>EndereÃ§o:</strong> <?=$consumidor?> </p>"
											});
											google.maps.event.addListener(marker0, 'click', function() {
												infowindow0.open(map,marker0);
											});
											/* Fim Marker Cliente */

											data = data.split('*');

											/* Realiza a Rota do posto ao cliente mais proximo */

											var from = latlon;
											var to = data[0];

											if(qtdRotas == 0){
												rota(from, to);
											}

											qtdRotas = 1;

											/* Lista InformaÃ§Ãµes dos Postos */
											$('.tbody').html(data[1]);
											table_excel = data[1];

											/*  Gera Markers*/
											geraMarkers();
										}
									});

								}
							});

						}

					}else{
						// alert('Geocode was not successful for the following reason: ' + status);
						$('#direction').html('<center><br /><br /><strong>Sem Rota a Realizar</strong></center>');
						$('.tbody').html("<td colspan='10'><br /><h1 align='center'>Nenhum Posto Autorizado localizado</h1><br /></td>");
					}
				});

				<?php } else { ?>

					var pais = '<?php echo $pais; ?>';
					var estado = '<?php echo $estado; ?>';
					var cidade = '<?php echo $cidade; ?>';

					$.ajax({
						url: 'mapa_rede_ajax.php',
						type: 'get',
						data: "pais="+pais+"&estado="+estado+"&cidade="+cidade,
						success: function(data){
							if(data != ""){
								/* Lista Informaçoes dos Postos */
								$('.tbody').html(data);

								/*  Gera Markers*/
								geraMarkers();
							}else{
								$('.tbody').html("<td colspan='9'><br /><h1 align='center'>Nenhum Posto localizado com o Endereço/CEP Informado</h1><br /></td>");
							}

						}
					});

				<?php } ?>

				/* Fim */
			}

			/* Gera Markers */

			function geraMarkers(){

				var i = 0;

			    $("tr.posto").each(function () {
					var lat = $(this).find("input[name=lat]").val();
					var lng = $(this).find("input[name=lng]").val();
					var dist = $(this).find("input[name=distacia_cliente]").val();

					if (lat.length == 0 || lng.length == 0) {
						return;
					}

					if(callcenter == true){
						/* Menor que 100km*/
						if(dist < 100){
							var posto = $(this).attr("id");

					    	marks.push(new google.maps.Marker({
								position: new google.maps.LatLng(lat, lng),
								clickable: true,
								draggable: false,
								flat: false,
								id: posto
							}));

						}
					}else{
						var posto = $(this).attr("id");

						var local = new google.maps.LatLng(lat, lng);

				    	marks.push(new google.maps.Marker({
							position: local,
							clickable: true,
							draggable: false,
							flat: false,
							id: posto
						}));

				    	<?php echo (isset($_GET['callcenter'])) ? '' : 'bounds.extend(local);'; ?>

					}

					i++;
			    });

			    <?php echo (isset($_GET['callcenter'])) ? '' : 'map.fitBounds(bounds);'; ?>

			    marker = new MarkerManager(map, {trackMarkers: false, maxZoom: 15});

			    google.maps.event.addListener(marker, 'loaded', function(){
			     	marker.addMarkers(marks, 0);
			    	marker.refresh();

			    	for (var i in marks) {
						google.maps.event.addListener(marks[i], 'click', function (e) {
							infowindow.setContent(getInfo(this.id));
							this.setMap(map);
							this.setPosition(e.latLng);
							infowindow.open(map, this);
						});
					}

				});

				<?php

			    if(!isset($_GET['callcenter'])){
			    	echo "
			    		if(i == 1){
			    			map.setZoom(17);
			    		}
			    	";
			    }

			    ?>

			}

			/* Rota */

			function rota(from, to, enderecoPosto){

				var endCliete = "<?php echo $consumidor; ?>";
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
								$('.adp-placemark:eq(1)').html('<table><tr><td><img src="../admin/imagens/Google_Maps_Marker_Red.gif" /></td><td>'+endPosto+'</td></tr></table>');
							}, 200);

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
					<strong>Clique sobre os icones<img src="../admin/imagens/Google_Maps_Marker_Red.gif" style="margin-bottom: -10px;" alt="Icone Postos" />para ver informações detalhadas do posto</strong>
			</p>

			<div id="GoogleMaps" ></div>
			<?php echo (isset($_GET['callcenter'])) ? '<div id="direction" ></div>' : ''; ?>

			<div style="clear: both;"></div>

			<p align="left">

			<!-- Lengenda -->

			<?php echo (isset($_GET['callcenter'])) ? '<span id="icon-blue"><img src="http://www.google.com/intl/en_us/mapfiles/ms/micons/blue-dot.png" style="margin-bottom: -10px;" alt="Icone Cliente" /><strong>Cliente</strong></span> &nbsp; ' : ''; ?>
			<span id="icon-red"><img src="../admin/imagens/Google_Maps_Marker_Red.gif" style="margin-bottom: -10px;" alt="Icone Postos" /><strong>Postos Autorizados</strong><span>

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

				echo "<a href=\"$caminho\" target=\"_blank\" style=\"text-decoration: none; \"><img src=\"../admin/imagens/excel.png\" height=\"30px\" width=\"30px\" align=\"absmiddle\"> Gerar Arquivo Excel</a> <br /> <br />";
				//echo "<a href=\"#\" style=\"text-decoration: none; \" onclick=\"gerarExcel()\"><img src=\"imagens/excel.png\" height=\"30px\" width=\"30px\" align=\"absmiddle\"> Gerar Arquivo Excel</a> <span id=\"carregando_excel\"></span> <br /> <br />";

			}else{
				echo "<br /> <br />";
			}

		?>

		<!-- Fim Excel -->

	</body>
</html>
