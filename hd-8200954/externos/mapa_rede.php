<?php

include "../dbconfig.php";
include "../includes/dbconnect-inc.php";
include '../helpdesk/mlg_funciones.php';

if (!function_exists('anti_injection')) {
	function anti_injection($string) {
		$a_limpa = array("'" => "", "%" => "", '"' => "", "\\" => "");
		return strtr(strip_tags(trim($string)), $a_limpa);
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
				height: 600px;
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
		<script src="https://google-maps-utility-library-v3.googlecode.com/svn/tags/markermanager/1.0/src/markermanager.js"></script>

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

			var bounds = new google.maps.LatLngBounds(); 

			var directionsService;
			var directionsRenderer;
			var directionsDisplay;

			var qtdRotas = 0;

			var callcenter       = '<?=$callcenter?>';
			var linha            = '<?=$linha;?>';
			var cidadeConsumidor = '<?=$consumidor_cidade;?>';
			var estadoConsumidor = '<?=$consumidor_estado;?>';

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

			var list_address = ['<?=$endereco_formatado?>', '<?=$endereco_rota?>', 'cep <?=$cep?>'];
			var list_address2 = ['<?=$endereco_rota?>', '<?=$endereco_rota?>', 'cep <?=$cep?>'];

			function myCallback (data, i) {
				if (i > list_address.length) {
					$('#direction').html('<center><br /><br /><strong>Sem Rota a Realizar</strong></center>');
					$('.tbody').html("<td colspan='10'><br /><h1 align='center'>Nenhum Posto Autorizado localizado</h1><br /></td>");
				} else {
					if (data == true) {
						geraRotaConsumidor();
					} else {
						geocodeLatLon(i, myCallback);
					}
				}
			}

			function geocodeLatLon (i, callback) {
				geocoder = new google.maps.Geocoder();

				geocoder.geocode( { 'address': list_address[i] }, function(results, status) {
					if (status == google.maps.GeocoderStatus.OK) {
						/* Endereço retornado pelo Google */
						var destino = results[0].address_components;

						var estadoComp;
						var cidadeComp;
						var bairro;
						var endereco;

						$.each(destino, function (key, value) {
							if ($.inArray("administrative_area_level_1", value.types) != -1) {
								estadoComp = value.short_name;
							} else if ($.inArray("administrative_area_level_2", value.types) != -1 || $.inArray("locality", value.types) != -1) {
								cidadeComp = value.long_name;
							} else if ($.inArray("neighborhood", value.types) != -1) {
								bairro = value.long_name;
							} else if ($.inArray("route", value.types) != -1) {
								endereco = value.long_name;
							}
						});

						var cidadesIguais = false;
						var estadosIguais = false;

						/* Reescreve a Sigla do estado para o nome completo */
						var estadoConsumidor2 = retiraAcentos(siglaEstado(estadoConsumidor));

						var comp1 	= [];
						var comp2 	= [];

						var seq 	= 0;

						if (cidadeComp.length > 0) {
							cidadeComp       = retiraAcentos(cidadeComp);
							cidadeConsumidor = retiraAcentos(cidadeConsumidor);

							if (cidadeComp == cidadeConsumidor) {
								cidadesIguais = true;
							}
						}

						if (estadoComp.length > 0) {
							estadoComp       = retiraAcentos(estadoComp);
							estadoConsumidor = retiraAcentos(estadoConsumidor);

							if (estadoComp == estadoConsumidor || estadoComp == estadoConsumidor2) {
								estadosIguais = true;
							}
						}

						if (cidadesIguais == true && estadosIguais == true) {
							latlon = results[0].geometry.location;
							latlon = latlon.toString();

							callback(true);
						} else {
							callback(false, ++i);
						}
					} else {
							geocoder.geocode( { 'address': list_address2[i] }, function(results, status) {
									if (status == google.maps.GeocoderStatus.OK) {
										/* Endereço retornado pelo Google */
										var destino = results[0].address_components;

										var estadoComp;
										var cidadeComp;
										var bairro;
										var endereco;

										$.each(destino, function (key, value) {
											if ($.inArray("administrative_area_level_1", value.types) != -1) {
												estadoComp = value.short_name;
											} else if ($.inArray("administrative_area_level_2", value.types) != -1 || $.inArray("locality", value.types) != -1) {
												cidadeComp = value.long_name;
											} else if ($.inArray("neighborhood", value.types) != -1) {
												bairro = value.long_name;
											} else if ($.inArray("route", value.types) != -1) {
												endereco = value.long_name;
											}
										});

										var cidadesIguais = false;
										var estadosIguais = false;

										/* Reescreve a Sigla do estado para o nome completo */
										var estadoConsumidor2 = retiraAcentos(siglaEstado(estadoConsumidor));

										var comp1 	= [];
										var comp2 	= [];

										var seq 	= 0;

										if (cidadeComp.length > 0) {
											cidadeComp       = retiraAcentos(cidadeComp);
											cidadeConsumidor = retiraAcentos(cidadeConsumidor);

											if (cidadeComp == cidadeConsumidor) {
												cidadesIguais = true;
											}
										}

										if (estadoComp.length > 0) {
											estadoComp       = retiraAcentos(estadoComp);
											estadoConsumidor = retiraAcentos(estadoConsumidor);

											if (estadoComp == estadoConsumidor || estadoComp == estadoConsumidor2) {
												estadosIguais = true;
											}
										}

										if (cidadesIguais == true && estadosIguais == true) {
											latlon = results[0].geometry.location;
											latlon = latlon.toString();

											callback(true);
										} else {
											callback(false, ++i);
										}
									} else {
										$('#direction').html('<center><br /><br /><strong>Sem Rota a Realizar</strong></center>');
										$('.tbody').html("<td colspan='10'><br /><h1 align='center'>Nenhum Posto Autorizado localizado</h1><br /></td>");
									}
								});
					}
				});
			}

			function geraRotaConsumidor () {
				<?php
				if ($login_fabrica == 52) {
				?>
					var dataAjax = {
						latlon: latlon,
						callcenter: callcenter,
						linha: linha,
						consumidor_estado: estadoConsumidor,
						consumidor_cidade: cidadeConsumidor
					};
				<?php
				} else {
				?>
					var dataAjax = {
						latlon: latlon,
						callcenter: callcenter,
						linha: linha
					};
				<?php
				}
				?>

				$.ajax({
					url: 'mapa_rede_ajax.php',
					type: 'get',
					data: dataAjax,
					success: function(data){

						<?php
						if(strlen($consumidor) == 0){
							$consumidor = $endereco_rota;
						}
						?>

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
							content: "<p align='left'><strong>Cliente:</strong> <?=$nome_cliente?> <br /> <strong>Endereço:</strong> <?=$consumidor?> </p>"
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

			function loadGoogleMaps () {
				var pais               = '<?=pais?>';
				var cidade             = '<?=consumidor_cidade?>';
				var estado             = '<?=consumidor_estado?>';
				var cep                = '<?=cep?>';
				var endereco_formatado = '<?=endereco_formatado?>';

				directionsDisplay = new google.maps.DirectionsRenderer();

				var latlng    = new google.maps.LatLng(-15.78014820, -47.92916980);
				var myOptions = {
				    zoom     : 4,
			    	center   : latlng,
			    	mapTypeId: google.maps.MapTypeId.ROADMAP
			    };

				map           = new google.maps.Map(document.getElementById("GoogleMaps"), myOptions);
				infowindow    = new google.maps.InfoWindow();

				<?php 
				if (isset($_GET['callcenter'])) { 
				?>
					/* Busca latitude e longitude do posto */
					geocodeLatLon(0, myCallback);
				<?php 
				} else { 
				?>

					var pais = '<?php echo $pais; ?>';
					var estado = '<?php echo $estado; ?>';
					var cidade = '<?php echo $cidade; ?>';

					$.ajax({
						url: 'mapa_rede_ajax.php',
						type: 'get',
						data: {opcao : 'todos'},
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

				<?php 
				} 
				?>

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
		<!-- <div id="header" >
			<img id="logo" src="../logos/logo_telecontrol_2013.png" />
			<h1>Mapa da Rede Autorizada</h1>
			<img id="mapa" src="../externos/mapa_rede/imagens/mapa_azul.gif" />
		</div> -->
		<div id="body" >
			<!-- <ul>
				<li>
					Podem haver postos que não apareçam no mapa, por estarem com o endereço incorreto.
				</li>
				<li>
					A localização dos postos não é exata, podendo haver margem de erro.
				</li>
				<li>
					Caso ele encontre o endereço, mas não consiga realizar a rota, tente remover o número da residência, o Google não mantem atualizado.
				</li>
			</ul> -->

			<br />

			<div id="GoogleMaps" ></div>
			<?php // echo (isset($_GET['callcenter'])) ? '<div id="direction" ></div>' : ''; ?>

			<div style="clear: both;"></div>

			<p align="left">

			<!-- Lengenda -->

			<?php // echo (isset($_GET['callcenter'])) ? '<span id="icon-blue"><img src="http://www.google.com/intl/en_us/mapfiles/ms/micons/blue-dot.png" style="margin-bottom: -10px;" alt="Icone Cliente" /><strong>Cliente</strong></span> &nbsp; ' : ''; ?>

			<table id="grid_postos" border="0" style="width: 100%;" >
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
