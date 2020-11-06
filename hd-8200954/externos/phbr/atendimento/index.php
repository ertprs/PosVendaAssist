<?php
/*******************************************************
* 20/03/09 MLG - Práticamente reescrevi o programa...  *
*******************************************************/

/*******************************************************************
* 20/06/13 Guilherme Silva - Práticamente reescrevi o programa...  *
********************************************************************/

/*******************************************************************
* 23/02/17 Guilherme Curcio - Práticamente reescrevi o programa...  *
********************************************************************/

/*******************************************************************
* 28/03/17 Vitor Espósito - Práticamente reescrevi o programa...  *
********************************************************************/

/*******************************************************************
* 01/04/17 Anderson Luciano - Práticamente reescrevi o programa...  *
********************************************************************/

$ip = getenv ("REMOTE_ADDR");
include '../../../dbconfig.php';
include '../../../includes/dbconnect-inc.php';
include '../../../funcoes.php';

if ($_GET["ajax_rota"]) {
	$origem  = $_GET["origem"];
	$destino = $_GET["destino"];

	$rota = googleMapsGeraRota($origem, $destino);

	exit(json_encode($rota));
}

function anti_injection($string) {
	$a_limpa = array("'" => "", "%" => "", '"' => "", "\\" => "");
	return strtr(strip_tags(trim($string)), $a_limpa);
}

// Recebe
if(isset($_POST['familia'])){
	$familia = $_POST['familia'];
	list($linha, $familia) = explode(",", $familia);
	$uf = $_POST['uf'];
	$cidade = $_POST['cidade'];
	$bairro = $_POST['bairro'];

	$sqlCidade = ($cidade == "all") ? "" : " AND upper(TRIM(TO_ASCII(tbl_posto_fabrica.contato_cidade,'LATIN9'))) = ('$cidade') ";
	$sqlBairro = ($bairro == "all") ? "" : " AND upper(trim(tbl_posto_fabrica.contato_bairro)) = upper('$bairro') ";

}


/* Busca a cidade do estado referente */
if(isset($_POST['uf_ajax'])){
	$uf = $_POST['uf_ajax']; 
	$linha = $_POST['linha'];
	if(!empty($linha)) {
		$sql = "SELECT DISTINCT TRIM(UPPER((tbl_posto_fabrica.contato_cidade))) AS contato_cidade
				FROM tbl_posto_fabrica
				JOIN tbl_posto_linha ON tbl_posto_fabrica.posto = tbl_posto_linha.posto
				AND tbl_posto_linha.linha = $linha
				WHERE tbl_posto_fabrica.fabrica = 3
				AND tbl_posto_fabrica.contato_estado = '$uf'
				AND tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
				AND tbl_posto_fabrica.codigo_posto <> '1122'
				AND tbl_posto_fabrica.tipo_posto IN (1, 2)
				AND      tbl_posto_fabrica.divulgar_consumidor IS TRUE
				AND 	 tbl_posto_fabrica.latitude IS NOT NULL
				AND 	 tbl_posto_fabrica.longitude IS NOT NULL
				ORDER BY contato_cidade ASC";
		$res = pg_query($con, $sql);

		if(pg_numrows($res) > 0){
			$selectedT = ($cidade == 'all') ? 'selected' : '';
			$dados = pg_fetch_all($res);
			foreach($dados as $v => $i) {
				$cidades[]= mb_detect_encoding($i['contato_cidade'],'UTF-8',true) ? retira_acentos(utf8_decode($i['contato_cidade'])) : retira_acentos($i['contato_cidade']);
			}
			$cidades = array_unique($cidades);
			echo "<option value='' $selectedT></option>";
			echo "<option value='all'>Todas</option>";
			foreach($cidades as $city) {
				echo "<option value='$city'>$city</option>";
			}	
		}else{
			echo "<option value=''></option>";
		}
	}
	exit;
}

/* Busca o bairro da cidade referente */
if(isset($_POST['cidade_ajax'])){
	$cidade = $_POST['cidade_ajax']; 
	$linha = $_POST['linha'];

	$sql = "
		select 
			distinct trim(tbl_posto_fabrica.contato_bairro) as contato_bairro
		from 
			tbl_posto_fabrica 
		JOIN 
			tbl_posto_linha ON tbl_posto_fabrica.posto = tbl_posto_linha.posto AND tbl_posto_linha.linha = $linha 
		where 
			tbl_posto_fabrica.fabrica = 3 
			and upper(trim(TO_ASCII(tbl_posto_fabrica.contato_cidade,'LATIN9'))) = upper('$cidade')
			and tbl_posto_fabrica.credenciamento = 'CREDENCIADO' 
			and tbl_posto_fabrica.tipo_posto IN (1,2) 
			and tbl_posto_fabrica.codigo_posto <> '1122' 
			AND      tbl_posto_fabrica.divulgar_consumidor IS TRUE
			AND 	 tbl_posto_fabrica.latitude IS NOT NULL
			AND 	 tbl_posto_fabrica.longitude IS NOT NULL
			oRDER BY 1 ASC";

	$res = pg_query($con, $sql);

	if(pg_numrows($res) > 0){
		echo "<option value=''></option>";
		echo "<option value='all'>Todas</option>";
		while($data = pg_fetch_object($res)){
			echo "<option value='$data->contato_bairro'>$data->contato_bairro</option>";
		}
	}else{
		echo "<option value=''></option>";
		echo "<option value='all'>Todos</option>";
	}

	exit;
}

$sqlInsertLog = "INSERT INTO tbl_log_conexao(programa) VALUES ('$PHP_SELF')";
$resInsertLog = pg_query($con, $sqlInsertLog);

$markers = "null";

if (strlen($posto) == 0 ) { // Se não há um GET com o posto, mostra a lista...
	if (strlen ($familia) > 0 and strlen ($uf) > 0 ) {  // ...se foi informada a família e a UF
		if ($linha == 2){ // HD 59226
			$sqlCondP = "AND tbl_posto.posto <> 595";
		}
		$sql = "
			SELECT  tbl_posto.posto                                      ,
					tbl_posto.nome                                       ,
					tbl_posto_fabrica.contato_endereco      AS endereco  ,
					TRIM(UPPER(tbl_posto_fabrica.contato_cidade))      AS cidade      ,
					tbl_posto_fabrica.contato_bairro      AS bairro      ,
					tbl_posto_fabrica.longitude                          ,
					tbl_posto_fabrica.latitude 
			FROM tbl_posto
			JOIN     tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = 3
			JOIN     tbl_posto_linha   ON tbl_posto.posto = tbl_posto_linha.posto AND tbl_posto_linha.linha = $linha
			WHERE    tbl_posto_fabrica.contato_estado = '$uf'
			$sqlCidade
			$sqlBairro
			AND      tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
			AND      tbl_posto_fabrica.tipo_posto IN (1,2)
			AND      tbl_posto_fabrica.codigo_posto <> '1122'
			AND      tbl_posto_fabrica.divulgar_consumidor IS TRUE
			AND 	 tbl_posto_fabrica.latitude IS NOT NULL
			AND 	 tbl_posto_fabrica.longitude IS NOT NULL
			$sqlCondP
			ORDER BY tbl_posto.cidade";
		$res = pg_query($con,$sql);
		$num_postos = pg_numrows($res);

		if ($num_postos > 0) {
			$y = 0;

			$markers = array();

            while($y < $num_postos) {
                list ($postoMarker,$nomeMarker,$enderecoMarker, $cidadeMarker,$bairroMarker, $latMarker, $lngMarker) = pg_fetch_array($res,$y,PGSQL_NUM);

				$cidadeMarker= mb_detect_encoding($cidadeMarker,'UTF-8',true) ? retira_acentos(utf8_decode($cidadeMarker)) : retira_acentos($cidadeMarker);
                if(strlen($latMarker) > 0 && strlen($lngMarker) > 0){
					$markers[] = array($lngMarker,$latMarker);
            	}

				$y++;
            }

			$markers = json_encode($markers);
		}
	}
}else{
	$sql = "SELECT  tbl_posto.nome ,
			tbl_posto_fabrica.contato_fone_comercial  AS fone    ,
			tbl_posto_fabrica.contato_fax             AS fax     ,
			tbl_posto_fabrica.contato_endereco    AS endereco    ,
			tbl_posto_fabrica.contato_numero      AS numero      ,
			tbl_posto_fabrica.contato_complemento AS complemento ,
			tbl_posto_fabrica.contato_bairro      AS bairro      ,
			TRIM(UPPER(TO_ASCII(tbl_posto_fabrica.contato_cidade, 'LATIN9')))      AS cidade      ,
			tbl_posto_fabrica.contato_estado      AS estado      ,
			tbl_posto_fabrica.contato_cep         AS cep         ,
			tbl_posto_fabrica.latitude					  AS lat         ,
			tbl_posto_fabrica.longitude					  AS lng
	FROM tbl_posto_fabrica
	JOIN tbl_posto USING(posto)
	WHERE tbl_posto.posto   = $posto
	AND tbl_posto_fabrica.fabrica = 3 
	$sqlCidade $sqlBairro";
	$res = pg_query($con,$sql);
	$num_postos = pg_numrows($res);
	if($num_postos == 1){
		$markers = array();

		while($result = pg_fetch_object($res)){
            if(strlen($result->lat) > 0 && strlen($result->lng) > 0){
				$markers[] = array($result->lat,$result->lng);
        	}
 		}

		$markers = json_encode($markers);
	}
}
?>
<HTML>
	<HEAD>
	<TITLE>Britânia - Atendimento</TITLE>

	<script src="../../../js/jquery-1.6.2.js"></script>
	

	<link href="../../../plugins/leaflet/leaflet.css" rel="stylesheet" type="text/css" />
	<script src="../../../plugins/leaflet/leaflet.js" ></script>		
	<script src="../../../plugins/leaflet/map.js" ></script>
	<script src="../../../plugins/mapbox/geocoder.js"></script>
	<script src="../../../plugins/mapbox/polyline.js"></script>
	<!-- CSS e JavaScript Google Maps -->
	<!-- <link href="https://developers.google.com/maps/documentation/javascript/examples/default.css" rel="stylesheet"> 
	<script src="https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false&language=pt-br&key=AIzaSyC5AsH3NU-IwraXqLAa1qbXxyjklSVP-cQ"></script> -->

	<script type="text/javascript">

		$(document).ready(function(){

			$('.rota').click(function(){
				$('#formRota').toggle();
			});

			<?php
	    	if(!isset($_GET['posto'])){
	    	?>
				//selectCidade();
			<?php
			}
			?>

			$('#familia').change(function(){
				$('#uf').val('');
				$('#cidade').val('');
				$('#bairro').val('');
				$('#tabela_dados').html('');
				$('#aviso_informatica').css({'display' : 'none'});
				$('#aviso_telefonia').css({'display' : 'none'});
				resetMap();
			});

		});

		function zoomAll(){
    		Markers.focus();    
		}

		function load_mapbox(){
			//mapbox
			
			Map      = new Map("Maps");
			Router   = new Router(Map);
			Geocoder = new Geocoder();
			Markers = new Markers(Map);
			
			// Polyline = new Polyline();
			// $("#Maps").hide();
			
			Map.load();
		}

	    /* Inicio Google Maps */
		var map = null;
        var markersMap = [];
	    var bounds;
	    function initialize() {
	    	//$("#GoogleMaps").addClass('GoogleMaps');

	    	let markers = <?=$markers?>;

	    	<?php
	    	if(!isset($_GET['posto'])){
	    	?>
	    		//let url = "https://maps.googleapis.com/maps/api/staticmap?scale=2&size=300x250&maptype=roadmap&markers=color:red%7C"+markers.join("|")+"&key=AIzaSyC5AsH3NU-IwraXqLAa1qbXxyjklSVP-cQ";
			<?php
			} else {
			?>
	    		//let url = "https://maps.googleapis.com/maps/api/staticmap?center="+markers[0]+"&zoom=15&scale=2&size=300x250&maptype=roadmap&markers=color:red%7C"+markers[0]+"&key=AIzaSyC5AsH3NU-IwraXqLAa1qbXxyjklSVP-cQ";
	    	<?php
			}
			?>
			if (markers !== undefined && markers !== null) {
				
				// bounds = new L.LatLngBounds(markers);

				// map = L.map('Maps');
				// L.tileLayer('http://maps.telecontrol.com.br/tile/{z}/{x}/{y}.png', {
				// 	attribution: '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors'
				// }).addTo(map);

				
              	// Markers.add(cLat, cLng, "blue", "Cliente");
              	// Markers.add(pLat, pLng, "red", "Posto");
              	// Markers.clear();    
              	
              	
				markers.forEach(function(l, i) {
					[lat, lng] = l;					
					Markers.add(lat, lng, "red", "Posto");
					
					pLatLng = lat+","+lng;
				});
				Markers.render();
              	Markers.focus();    

				// map.fitBounds(bounds);
			}
    		//L.marker([51.5, -0.09], {icon: greenIcon}).addTo(map);

	    	//$("#GoogleMaps").html("<img src='"+url+"' style='width: 100%; height: 100%;' />");
	    }

	    function clearMap() {
	        for (i in map._layers) {
	            if (map._layers[i]._path != undefined) {
	                try {
	                    map.removeLayer(map._layers[i]);
	                }
	                catch (e) {
	                    console.log("problem with " + e + map._layers[i]);
	                }
	            }
	        }
	    }

		function zoomMap(lat, lng){
			var target_offset = $("#parametros").offset();
        	var target_top = target_offset.top;
        	$('html, body').animate({ scrollTop: target_top }, 100);

        	Map.setView(lat, lng,15);        	
		}		

	    function realizaRota(){

            // if (markersMap.length > 0) {
            //     markersMap.forEach(function(valor,chave){
            //         map.removeLayer(valor);
            //     });
            //     markersMap = [];
            // }

            Markers.clear();
            Router.remove();
            Router.clear();

			var geocoder, latlon;

			var numeroConsumidor   = $('#numero_cliente_rota').val();
			var enderecoConsumidor = $('#end_cliente_rota').val();
			var bairroConsumidor   = $('#bairro_cliente_rota').val();
			var cidadeConsumidor   = $('#cidade_cliente_rota').val();
			var estadoConsumidor   = $('#uf_cliente_rota').val();
			var paisConsumidor     = "Brasil";

			if (enderecoConsumidor == "") {
				alert('Digite o endereço para pesquisa!');
				$('#end_cliente_rota').focus();
				return
			}
			if (numeroConsumidor == "") {
				alert('Digite o número do endereço informado!');
				$('#numero_cliente_rota').focus();
				return
			}
			if (cidadeConsumidor == "") {
				alert('Digite a cidade para pesquisa!');
				$('#cidade_cliente_rota').focus();
				return
			}
			if (estadoConsumidor == "") {
				alert('Digite o estado do endereço informado!');
				$('#uf_cliente_rota').focus();
				return
			}

			$('#loading').show();
			try {
				Geocoder.setEndereco({
					endereco: enderecoConsumidor,
					numero: numeroConsumidor,
					bairro: bairroConsumidor,
					cidade: cidadeConsumidor,
					estado: estadoConsumidor,
					pais: paisConsumidor
				});

				request = Geocoder.getLatLon();

				request.then(
					function(resposta) {
						// markersMap.push( L.marker([resposta.latitude, resposta.longitude],{icon: greenIcon}) );

			            var latlgn = [];

			            cLatLng = resposta.latlon;

			            if (cLatLng == pLatLng) {
							alert('Endereço informado é o mesmo do posto!');
							cLatLngA = cLatLng.split(",");

							Router.remove();
							Router.clear();

		              		Markers.remove();
		              		Markers.clear();
							Markers.add(cLatLngA[0], cLatLngA[1], "blue", "Cliente/Posto");
		              		Markers.render();
							Markers.focus();

		                    $('#qtde_km').val(0);
		                    $('#loading').hide();
			            }else{
				            $.ajax({
			                    url: "controllers/TcMaps.php",
			                    type: "POST",
			                    data: {ajax: "route", origem: cLatLng, destino: pLatLng, ida_volta: 'sim'},
			                    timeout: 60000
			                }).done(function(data){
			                    data = JSON.parse(data);

			                    geometry = data.rota.routes[0].geometry;
			                    var kmtotal = parseFloat(data.total_km).toFixed(2);

								/* Marcar pontos no mapa */
								cLatLngA = cLatLng.split(",");
								pLatLngA = pLatLng.split(",");



								Markers.remove();
								Markers.clear();
								Markers.add(cLatLngA[0], cLatLngA[1], "blue", "Cliente");
								Markers.add(pLatLngA[0], pLatLngA[1], "red", "Posto");
								Markers.render();
								Markers.focus();

								Router.remove();
								Router.clear();
								Router.add(Polyline.decode(geometry));
								Router.render();

			                    $('#qtde_km').val(kmtotal);
			                    $('#loading').hide();
			                }).fail(function(){
			                    $('#loading-map').hide();
			                    alert('Erro ao tentar calcular a rota!');
			                });
			            }



						// $.ajax({
						// 	url: "http://api2.telecontrol.com.br/maps/route/location/"+resposta.latitude+"," + resposta.longitude+"/destiny/"+$("#posto_latitude").val()+","+$("#posto_longitude").val(),
						// 	type: "GET",
						// 	timeout: 60000
						// }).done(function (response) {
						// 	if (response.exception == undefined) {
						// 		$(response[0]).each(function (idx, elem) {
						// 			var coordinates = elem.st_astext;

						// 			coordinates = coordinates.substring(coordinates.indexOf('(') + 1);
						// 			coordinates = coordinates.substring(0, (coordinates.length - 1));

						// 			coordinates = coordinates.split(",");
			   //                      $(coordinates).each(function (idx1, elem1) {
			   //                          elem1 = elem1.split(" ");
			   //                          latlgn.push([elem1[1], elem1[0]]);
			   //                      });
						// 		});

						// 		latlgn.unshift([resposta.latitude,resposta.longitude]);
					 //            markersMap.forEach(function(valor,chave){
					 //                map.addLayer(valor);
					 //            });

						// 		var polyline = L.polyline(latlgn, {color: 'blue'}).addTo(map);
					 //            map.fitBounds(polyline.getBounds());
					 //            $('#loading').hide();

						// 		var target_offset = $("#Maps").offset();
						// 		var target_top = target_offset.top;
					 //        	$('html, body').animate({ scrollTop: target_top }, 100);	            
						// 	}
			   //          }).fail(function(){
						// 	alert('Não foi possível realizar a rota!');
			   //          });
					},
					function(erro) {
						alert(erro);
						$('#loading').hide();
					}
				);
			} catch(e) {
				alert(e.message);
				$('#loading').hide();
			}

			/*$.ajax("http://api2.telecontrol.com.br/maps/geocoding/query/address" + endCliente)
			.done(function (response) {
				if (response[0].features[0] !== undefined) {
					a1Lat = response[0].features[0].geometry.coordinates[1];
					a1Lng = response[0].features[0].geometry.coordinates[0];

	                L.marker([a1Lat, a1Lng],{icon: blueIcon}).addTo(map);

		            var latlgn = [];
					$.ajax("http://api2.telecontrol.com.br/maps/route/location/"+$("#posto_latitude").val()+","+$("#posto_longitude").val()+"/destiny/"+a1Lat+"," + a1Lng)
					.done(function (response) {
						if (response.exception == undefined) {
							$(response[0]).each(function (idx, elem) {
								var coordinates = elem.st_astext;

								coordinates = coordinates.substring(coordinates.indexOf('(') + 1);
								coordinates = coordinates.substring(0, (coordinates.length - 1));

								coordinates = coordinates.split(",");
		                        $(coordinates).each(function (idx1, elem1) {
		                            elem1 = elem1.split(" ");
		                            latlgn.push([elem1[1], elem1[0]]);
		                        });
							});
							var polyline = L.polyline(latlgn, {color: 'blue'}).addTo(map);
				            map.fitBounds(polyline.getBounds());
				            $('#loading').hide();
						}
		            });
				}else{
					$('#loading').hide();
					alert('Não foi possível encontrar um rota para o endereço informado!');
				}
            });*/

		    /*var postoLatLng  = $("#posto_latitude").val()+","+$("#posto_longitude").val();

			var geocoder = new google.maps.Geocoder();

			geocoder.geocode( { 'address': endCliente }, function(results, status) {
			    if (status == google.maps.GeocoderStatus.OK) {
			        var consumidorLatLng = results[0].geometry.location;
			        consumidorLatLng = consumidorLatLng.toString();

			        consumidorLatLng = consumidorLatLng.replace("(", "");
					consumidorLatLng = consumidorLatLng.replace(")", "");

					var parte = consumidorLatLng.split(',');
					var lat = parte[0];
					var lng = parte[1];

					$.ajax({
						url: window.location,
						type: "get",
						data: {
							ajax_rota: true,
							origem: lat+","+lng,
							destino: postoLatLng
						}
					}).fail(function(r) {
						alert("Erro ao gerar Rota");
					}).done(function(r) {
						r = JSON.parse(r);

						if (r.exception) {
							alert("Erro ao gerar Rota");
						} else if (r.status != "OK") {
							alert("Erro ao gerar Rota");
						} else {
							var instrucoes = "Instruções<br /><hr />";

							r.routes[0].legs[0].steps.forEach(function(v, k) {
								instrucoes += v.html_instructions+"<br />";
							});

							var rota = r.routes[0].overview_polyline.points;

							let url = "https://maps.googleapis.com/maps/api/staticmap?scale=2&size=300x250&markers=color:red%7C"+postoLatLng+"&markers=color:blue%7C"+lat+","+lng+"&maptype=roadmap&path=weight:2%7Cenc:"+rota+"&key=AIzaSyC5AsH3NU-IwraXqLAa1qbXxyjklSVP-cQ";

							$("#GoogleMaps").html("<img src='"+url+"' style='width: 100%; height: 100%;' />");

							$('#direction').html(instrucoes);
			          		$('#setaRota').css({'display' : 'block'});
			          		$('#setaRota').html('<img src="../../img/arrow1.png" height="15px" style="margin-bottom: -3px;" /> <strong>Descrição da Rota</strong>');
						}
					});
			    }
			});*/
	    }

	    function abreFechaMap(){
	    	$('#direction').toggle();
	    	if($('#direction').is(':visible')){
	    		$('#setaRota').css({'margin-left' : '303px', 'border-left' : '0px'});
				$('#setaRota').html('<img src="../../img/arrow2.png" height="15px" style="margin-bottom: -3px;" /> <strong>Fechar</strong>');
	    	}else{
	    		$('#setaRota').css({'margin-left' : '303px', 'border-left' : '1px solid #999'});
	    		$('#setaRota').css({'margin-left' : '0'});
				$('#setaRota').html('<img src="../../img/arrow1.png" height="15px" style="margin-bottom: -3px;" /> <strong>Descrição da Rota</strong>');
	    	}
	    	
	    }

	    function selectCidade(){
	    	var uf = $('#uf').val();
	    	var linha = $('#familia').val();
	    	linha = linha.split(",");
	    	linha = linha[0];
	    	$.ajax({
	    		url : '<? echo $_SERVER["PHP_SELF"]; ?>',
	    		type: 'post',
	    		data: 'uf_ajax='+uf+"&linha="+linha,
	    		success: function(data){
	    			$('#cidade').html(data);
	    		}
	    	});
	    }

	    function selectBairro(){
	    	var cidade = $('#cidade').val();
	    	var linha = $('#familia').val();
	    	linha = linha.split(",");
	    	linha = linha[0];
	    	$.ajax({
	    		url : '<? echo $_SERVER["PHP_SELF"]; ?>',
	    		type: 'post',
	    		data: 'cidade_ajax='+cidade+"&linha="+linha,
	    		success: function(data){
	    			$('#bairro').html(data);
	    		}
	    	});
	    }

	    function resetMap(){
	    	//$('#GoogleMaps').html('').removeClass("GoogleMaps");
	    }

	    /*-----------------------------------------------------------------------------*/

		function envia_formulario() {

			var linha, familia1, familia, uf;
			var fam_lin		= Array();
			var frm			= window.document.frm_autorizado;
			var o_aviso		= document.getElementById('aviso_informatica');
			var o_tbl_lista = document.getElementById('tabela_dados');
			var o_aviso_t	= document.getElementById('aviso_telefonia');

			var cidade = "";

			if (frm.familia.value != "") {
				fam_lin = frm.familia.value.split(",");
				linha 	= fam_lin[0];
				familia1= fam_lin[1];
				familia = familia1.replace(/\x20/, "");
			}else{
				linha   = "";
				familia = "";
			}
			uf     = frm.uf.value; // .replace(/\x20/, "")
			cidade = frm.cidade.value;

			o_aviso.style.display = (linha == 528)?'block':'none';
			o_aviso_t.style.display = (linha == 789)?'block':'none';
			o_tbl_lista.style.display = (linha == 528)?'none':'block';
			o_tbl_lista.style.display = (linha == 789)?'none':'block';


			if (familia == "" || uf == "") {
				document.getElementById('tabela_dados').innerHTML = "";
			}
			if (linha != 528 && linha != 789 && familia != "" && uf != "" && cidade != "") {
				frm.submit();
			}


		}

		/* Start Maps */
	    $(function(){			
			<?php if ($markers != "null") {?>
				load_mapbox();
				initialize();
			<?php }else{
				?>
				$("#Maps").hide();
				<?php
			}?>
	    });
	</script>

	<style type="text/css">
	    body, html{
	        margin: 0;
	        padding: 0; 
	        text-align: center;
	        background: #FCFCFC;
	        font-family: Verdana, Arial, Helvetica, sans-serif;
			font-size: 12px;
	        color: #666;

	    }

	    body *{
	        text-align: left;
	    }

	    body a{
	        color: #000;
	        text-decoration: none;

	    }

	    /* #parametros{
	        width: 600px;
	        margin: 20px auto;
	        border: 1px solid #999;
	        background: #FCFCFC;
	    } */

	    #parametros label{
	        display: block;
	        font-size: 12px;
	    }

	    #parametros select {
	        background-color:#F0F0F0;
	        border:1px solid #888888;
	        font-family:Verdana;
	        font-size:8pt;
	        font-weight:bold;
	    }
	    #parametros th{
	        background-color:#999;
	        font: bold 14px "Arial";
	        color:#FFFFFF;
	        text-align:center;
	        padding: 5px;
	        text-shadow: 1px 1px 1px #000;
	    }
	    
	    .tbl_data_posto{
			font-family: Verdana, Arial, Helvetica, sans-serif;
			font-size: 12px;
			text-align: left;
			color: #666;
	        margin: 0 auto;
	        width: 600px;
	        background:#666;
	    }

	    .tbl_data_posto th{
	        background-color:#999;
	        font: bold 13px "Arial";
	        color:#FFFFFF;
	        text-align:left;
	        padding: 3px;
	    }

	    .tbl_data_posto td{
	        background-color: #fff;
	        font: bold 12px "Arial";
	        color:#666;
	        text-align:left;
	        padding: 3px;
	        text-transform: UPPERCASE;
	    }

		.tbl_data {
			font-family: Verdana, Arial, Helvetica, sans-serif;
			font-size: 12px;
			text-align: left;
			color: #666;
	        margin: 0 auto;
	        width: 600px;
	        background-color:#999;
	    }

	    /* .tbl_data th{
	        background-color:#999;
	        font: bold 14px "Arial";
	        color:#FFFFFF;
	        text-align:center;
	        padding: 3px;
	        text-shadow: 1px 1px 1px #000;
	    } */

		.tbl_data tr:nth-child(odd) {
	        background-color: #EEE;
		}

		.tbl_data tr:nth-child(even) {
	        background-color: #FCFCFC;
		}

		.topo {
			color:			#000;
			background:		#f5f5f5;
			margin:			0px;
			font-family:	Arial, Helvetica, sans-serif;
			padding:	    10px;
			border-bottom: 1px solid #ccc; 
		}

	   #aviso_informatica{
	        margin: 0 auto;
	        width: 600px;
	        text-align: center !important;
	    }

	   #aviso_telefonia {
	        margin: 0 auto;
	        width: 600px;
	        text-align: center !important;
	    }

	    /* Maps */

	    #Maps{
	    	width: 600px;
	    	height: 500px;
	    	border: 1px solid #999;
	    	margin: 0 auto;
	    	padding: 1px;
	    	/*display: none;*/
	    }

    	#direction{
    		background-color: #fff;
    		font: 12px arial !important;
			width: 300px;
			height: 500px;
			border: 1px #999 solid;
			overflow: auto;
			display: none;
			position: absolute;
			z-index: 100;
			margin-top: -504px;
			padding: 1px;
		}

		#setaRota{
    		background-color: #fff;
    		font: 12px arial !important;
    		padding: 5px;
			border: 1px solid #999;
			position: absolute;
			z-index: 100;
			margin-top: -130px;
			display: none;
		}

		#setaRota:hover{
			cursor: pointer;
		}

		.adp-placemark{
			font: 12px arial !important;
		}

		.adp-directions{
			font: 12px arial !important;
		}

		/* Maps */

	    #lupaMap:hover{
	    	cursor: pointer;
	    }

	    .rota{
	    	color: #1F6EB6;
	    }

	    .rota:hover{
	    	cursor: pointer;
	    	color: #3C9CD2;
	    }

	    .buttonRota{
	    	color: #fff;
	    	padding: 3px 20px;
			border: 0px;
			background-color: #1F6EB6;
			border-radius: 5px;
			-moz-border-radius: 5px;
			-webkit-border-radius: 5px;
	    }

	    .buttonRota:hover{
	    	cursor: pointer;
	    	background-color: #3C9CD2;
	    }

	    .bodyData{
	    	border: 1px solid #ccc;
	    	padding: 10px;
	    	width: 610px;
	    	margin: 0 auto;
	    	margin-top: 20px;
	    }

	    .bodyData select{
	    	padding: 5px;
	    }

	    #boxPosto{
	    	border: 1px solid #999;
	    	background-color: #f5f5f5;
	 		padding: 10px;
	 		margin-bottom: 20px;
	 		font: 14px arial;
	    }

	    #boxPosto h1{
	    	font-size: 15px;
	    	margin: 0px;
	    	padding: 0px;
	    }

	   	#formRota{
	    	border: 1px solid #999;
	    	background-color: #f5f5f5;
	 		padding: 10px;
	 		margin-bottom: 20px;
	 		font: 14px arial;
	    }

	    #formRota input{
	    	border: 1px solid #999;
	    	padding: 5px;
	    }

	   	#formRota button{
	    	padding: 5px 20px;
	    }

	    .fontRed{
	    	color: #ff0000;
	    	font-size: 14px;
	    }

	    .fontRed:hover{
	    	cursor: pointer;
	    }

	</style>

</HEAD>

<body>
    <div class='topo'>
    	<h1 style="margin: 0px; padding: 0px;">Assist&ecirc;ncia T&eacute;cnica</h1>
    	Encontre a Assistência Técnica mais perto de você.
    </div>

    <div class="bodyData">

    	<?php
    	if(!isset($_GET['posto'])){
    	?>

			<!-- Aqui está a seleção de linha e UF (estado) -->
			<FORM method='POST' action='<?=$PHP_SELF?>' name='frm_autorizado'>

				<table width="100%" id='parametros' align="center" cellpadding="0" cellspacing="0" border='0px'>

			        <tr>
			            <td colspan='2' style="padding-bottom: 10px;"><strong>Parâmetros de Pesquisa</strong></td>
			        </tr>
					<tr>
						<td style="padding-bottom: 10px;">
							<label for='familia'>Escolha a fam&iacute;lia de produtos</label>
			                <select name="familia" id="familia" class="form_newsletter" style="width: 250px;">
			                    <option value=""></option>
			                        <?
			                        $sql = "SELECT DISTINCT tbl_familia.familia,
			                                    tbl_familia.descricao,
			                                    (select linha from tbl_produto where tbl_produto.familia = tbl_familia.familia and linha notnull limit 1) as linha
			                                FROM tbl_linha
			                                    JOIN tbl_produto USING (linha)
			                                    JOIN tbl_familia USING (familia)
											WHERE tbl_linha.fabrica = 3
											AND tbl_familia.ativo
											AND tbl_produto.ativo IS TRUE
			                                ORDER BY tbl_familia.descricao";
			                        $res = pg_query($con,$sql);
			                        for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			                            echo "<OPTION ";
			                            if ($familia == pg_result ($res,$i,familia)) echo " SELECTED ";
			                            echo " value='" . pg_result ($res,$i,linha) . "," .
			                                              pg_result($res,$i,familia) . "'>" .
			                                              pg_result ($res,$i,descricao) . "</OPTION>";
			                        } ?>			
			                </select>
						</td>
						<td align="left">
			                <label for='uf'>Escolha o estado</label>
							<select name="uf" id='uf' class="form_newsletter" onchange="selectCidade();" style="width: 250px;">
								<OPTION <?echo ($uf == "") ? "SELECTED":"";?> value=""></OPTION>
			                    <?php				
			                        /*
			                        $estados = array("AC", "AL", "AM", "AP", "BA", "CE", "DF", "ES", "GO",
												 "MA", "MG", "MS", "MT", "PA", "PB", "PE", "PI", "PR",
												 "RJ", "RN", "RO", "RR", "RS", "SC", "SE", "SP", "TO");
			                        */
			                        $estados = array("AC"=>"AC - Acre","AL"=>"AL - Alagoas","AM"=>"AM - Amazonas",
			                          "AP"=>"AP - Amapá", "BA"=>"BA - Bahia", "CE"=>"CE - Ceará","DF"=>"DF - Distrito Federal",
			                          "ES"=>"ES - Espírito Santo", "GO"=>"GO - Goiás","MA"=>"MA - Maranhão","MG"=>"MG - Minas Gerais",
			                          "MS"=>"MS - Mato Grosso do Sul","MT"=>"MT - Mato Grosso", "PA"=>"PA - Pará","PB"=>"PB - Paraíba",
			                          "PE"=>"PE - Pernambuco","PI"=>"PI - Piauí","PR"=>"PR - Paraná","RJ"=>"RJ - Rio de Janeiro",
			                          "RN"=>"RN - Rio Grande do Norte","RO"=>"RO - Rondônia","RR"=>"RR - Roraima",
			                          "RS"=>"RS - Rio Grande do Sul", "SC"=>"SC - Santa Catarina","SE"=>"SE - Sergipe",
			                          "SP"=>"SP - São Paulo","TO"=>"TO - Tocantins");
					            foreach ($estados as $value => $key) {
					                echo "<OPTION";
					                echo ($uf == $value) ? " SELECTED" : "";
					                echo " value='$value'>$key</OPTION>";
								}?>
							</select>
						</td>
					</tr>
					<tr>
						<td align="left">
							<label for="cidade">Escolha a cidade <?php echo $cidade;?></label>
							<select name="cidade" id="cidade" class="form_newsletter" onchange="selectBairro();" style="width: 250px;">
								<?php

									if (!empty($linha) && !empty($uf)) {
										$sql = "SELECT DISTINCT TRIM(UPPER(TO_ASCII(tbl_posto_fabrica.contato_cidade, 'LATIN9'))) AS contato_cidade
												FROM tbl_posto_fabrica
												JOIN tbl_posto_linha ON tbl_posto_fabrica.posto = tbl_posto_linha.posto
												AND tbl_posto_linha.linha = $linha
												WHERE tbl_posto_fabrica.fabrica = 3
												AND tbl_posto_fabrica.contato_estado = '$uf'
												AND tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
												AND tbl_posto_fabrica.codigo_posto <> '1122'
												AND tbl_posto_fabrica.tipo_posto IN (1, 2)
												AND tbl_posto_fabrica.divulgar_consumidor IS TRUE
												ORDER BY contato_cidade ASC";
										$res = pg_query($con, $sql);

										if(pg_num_rows($res) > 0){
											$selectedT = ($cidade == 'all') ? 'selected' : '';
											echo "<option value=''></option>";
											echo "<option value='all' $selectedT>Todas</option>";
											while($data = pg_fetch_object($res)){
												$data->contato_cidade= mb_detect_encoding($data->contato_cidade,'UTF-8',true) ? retira_acentos(utf8_decode($data->contato_cidade)) : retira_acentos($data->contato_cidade);
												$selected = ($cidade == $data->contato_cidade) ? 'selected' : '';
												echo "<option value='$data->contato_cidade' $selected>$data->contato_cidade</option>";
											}
										}else{
											echo "<option value=''></option>";
										}
									} else {
										echo '<option value=""></option>';
									}
								?>
		
							</select>
						</td>
						<td align="left">
							<label for="cidade">Escolha o Bairro</label>
							<select name="bairro" id="bairro" class="form_newsletter" onchange="envia_formulario()" style="width: 250px;">
								<?php
									if (!empty($linha) && !empty($cidade)) {

										$sql = "
											select 
												distinct trim (tbl_posto_fabrica.contato_bairro)  as contato_bairro
											from 
												tbl_posto_fabrica 
											JOIN 
												tbl_posto_linha ON tbl_posto_fabrica.posto = tbl_posto_linha.posto AND tbl_posto_linha.linha = $linha 
											where 
												tbl_posto_fabrica.fabrica = 3 
												and upper(trim(TO_ASCII(tbl_posto_fabrica.contato_cidade,'LATIN9'))) = upper('$cidade')
												and tbl_posto_fabrica.credenciamento = 'CREDENCIADO' 
												and tbl_posto_fabrica.tipo_posto IN (1,2) 
												and tbl_posto_fabrica.codigo_posto <> '1122' 
												AND      tbl_posto_fabrica.divulgar_consumidor IS TRUE
												AND 	 tbl_posto_fabrica.latitude IS NOT NULL
												AND 	 tbl_posto_fabrica.longitude IS NOT NULL
												and tbl_posto_fabrica.divulgar_consumidor IS TRUE ORDER BY 1 ASC";

										$res = pg_query($con, $sql);

										if(pg_num_rows($res) > 0){
											$selectedT = ($bairro == 'all') ? 'selected' : '';
											echo "<option value=''></option>";
											echo "<option value='all' $selectedT>Todas</option>";
											while($data = pg_fetch_object($res)){
												$selected = ($bairro == $data->contato_bairro) ? 'selected' : '';
												echo "<option value='$data->contato_bairro' $selected>$data->contato_bairro</option>";
											}
										} else {
											echo "<option value=''></option>";
										}
									} else {
										echo '<option value=""></option>';
									}
								?>
							</select>
						</td>
					</tr>
				</table>

			</form>

		<?php
		}
		?>

		<table align="center">
			<tr>
				<td>
					<div id="Maps" style="width: 600;height: 500"></div>
					<div id="direction"></div>
					<div id="setaRota" onclick="abreFechaMap()"></div>
				</td>
			</tr>
		</table>

		<br>

			<DIV id='aviso_informatica' style='display:none;'>
				<BR>
				Para Localização de Postos Autorizados de Informática,<br/>
				favor entrar em contato com o <B>SAC (47) 3431-0499</B>
				<br/>
				<br/>
				Anote os seguintes dados:
				<ul style='list-style-type:disc; margin-left: 100px;'>
					<li>Número de série do Notebook</li>
					<li>Número da NF de Compra</li>
					<li>Data da emissão da NF de Compra</li>
				</ul>
		        <strong style='color: #000'>Para agilizar o atendimento, tenha em mãos o seu notebook</strong><br/>
			</DIV>

			<DIV id='aviso_telefonia' style='display:none;'>
				<BR>
			Para dificuldades com produtos da Linha de Telefonia entrar em contato em uns dos nossos canais de atendimento localizado em: <br> <a href='https://comvoce.philco.com.br/contato/'><b>https://comvoce.philco.com.br/contato/</b></a>
				<br/>
				<br/>
				Tenha em mãos os seguintes dados:
				<ul style='list-style-type:disc; margin-left: 100px;'>
					<li>Número de série do Telefone</li>
					<li>Número da NF de Compra</li>
					<li>Data da emissão da NF de Compra</li>
				</ul>
		        <strong style='color: #000'>Para agilizar o atendimento, tenha em mãos o seu Telefone</strong><br/>
			</DIV>

			<DIV id='tabela_dados'>
			<?
				if (strlen($posto) == 0 ) { // Se não há um GET com o posto, mostra a lista...
					# HD 30448 - aqui o original era linha e não familia
					if (strlen ($familia) > 0 and strlen ($uf) > 0 ) {  // ...se foi informada a família e a UF
						if ($linha == 2){ // HD 59226
							$sqlCondP = "AND tbl_posto.posto <> 595";
						}
						$sql = "
							SELECT  tbl_posto.posto                                      ,
									tbl_posto.nome                                       ,
									tbl_posto_fabrica.contato_fone_comercial  AS fone    ,
									tbl_posto_fabrica.contato_endereco    AS endereco  	 ,
									tbl_posto_fabrica.contato_numero      AS numero  	 ,
									tbl_posto_fabrica.contato_bairro      AS bairro      ,
									TRIM(UPPER(tbl_posto_fabrica.contato_cidade))      AS cidade      ,
									tbl_posto_fabrica.contato_estado      AS estado      ,
									tbl_posto_fabrica.latitude									 ,
									tbl_posto_fabrica.longitude                                   
							FROM tbl_posto
							JOIN     tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = 3
							JOIN     tbl_posto_linha   ON tbl_posto.posto = tbl_posto_linha.posto AND tbl_posto_linha.linha = $linha
							WHERE    tbl_posto_fabrica.contato_estado = '$uf'
							$sqlCidade
							$sqlBairro
							AND      tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
							AND      tbl_posto_fabrica.tipo_posto IN (1,2)
							AND      tbl_posto_fabrica.codigo_posto <> '1122'
							AND      tbl_posto_fabrica.divulgar_consumidor IS TRUE
							AND 	 tbl_posto_fabrica.latitude IS NOT NULL
							AND 	 tbl_posto_fabrica.longitude IS NOT NULL
							$sqlCondP
							ORDER BY tbl_posto.cidade";
						$res = pg_query($con,$sql);

						$num_postos = pg_numrows($res);
				        switch ($num_postos) {
				            case 0:
					            $msg = "Não existem postos no estado de $uf";
					            $msg .=	($cidade !== 'todos') ? " para a cidade de $cidade" : "";
								$msg .= ($bairro !== 'all') ? " e para o bairro $bairro" : "";
								$msg .= " para esta linha!";
				            	break;
				            case 1:
					            $msg = "Foi encontrado apenas um posto autorizado!";
				            	break;
							default:
								$msg = "Foram encontrados " . $num_postos . " postos autorizados!";
								$msg .= "<span class='fontRed' onclick='zoomAll();' style='font-size: 12px; margin-left: 120px;'>Mostrar Todas os Postos no Mapa</span>";
				        	break;
				        }?>
						<?php
						echo " &#9658; $msg <br /> <br />";
						if ($num_postos > 0) {
				                for ($x=0; $x < $num_postos; $x++) {
				                    list ($posto, $nome, $fone, $endereco, $numero, $bairro, $cidade, $estado, $lat, $lng) = pg_fetch_array($res,$x,PGSQL_NUM);
									$cidade= mb_detect_encoding($cidade,'UTF-8',true) ? retira_acentos(utf8_decode($cidade)) : retira_acentos($cidade);
				                    $url = "$PHP_SELF?posto=$posto&familia=$familia&uf=$uf";
				                    echo "<div id='boxPosto'>";
				                        echo "<h1>$nome</h1>";
				                        echo "$fone <br />";
				                        echo "$endereco, $numero <br />";
				                        echo "$bairro <br />";
				                        echo "$cidade - $uf <br /> <br />";
				                        echo "<span style='margin-left: 350px;' onclick='zoomMap($lat, $lng)' class='fontRed'>Localização<img src='../../../externos/img/lupa_rota.png' alt='Lupa' id='lupaMap' style='margin-bottom: -4px;' /></span>";
				                        echo "<a href='$url' target='_blank'><span class='fontRed' style='margin-left: 30px;'>Realizar Rota <img src='../../../externos/img/arrow1.png' alt='Lupa' id='lupaMap' style='margin-bottom: -4px;' height='17px' /></span></a>";
				                    echo "</div>";
				                }
						}
					}
				}else{

					$sql = "SELECT  tbl_posto.nome                                       ,
									tbl_posto_fabrica.contato_fone_comercial  AS fone    ,
									tbl_posto_fabrica.contato_fax             AS fax     ,
									tbl_posto_fabrica.contato_endereco    AS endereco    ,
									tbl_posto_fabrica.contato_numero      AS numero      ,
									tbl_posto_fabrica.contato_complemento AS complemento ,
									tbl_posto_fabrica.contato_bairro      AS bairro      ,
									TRIM(UPPER(tbl_posto_fabrica.contato_cidade))      AS cidade      ,
									tbl_posto_fabrica.contato_estado      AS estado      ,
									tbl_posto_fabrica.contato_cep         AS cep         ,
									tbl_posto_fabrica.longitude					  AS lng         ,
									tbl_posto_fabrica.latitude					  AS lat
							FROM tbl_posto_fabrica
							JOIN tbl_posto USING(posto)
							WHERE tbl_posto.posto   = $posto
							AND tbl_posto_fabrica.fabrica = 3
							AND tbl_posto_fabrica.latitude IS NOT NULL
							AND tbl_posto_fabrica.longitude IS NOT NULL
							$sqlCidade $sqlBairro";

					$res = pg_query($con,$sql);
					$num_postos = pg_numrows($res);
					if (pg_numrows($res) == 1) {

						while ($data = pg_fetch_object($res)) {	
							$data->cidade= mb_detect_encoding($data->cidade,'UTF-8',true) ? retira_acentos(utf8_decode($data->cidade)) : retira_acentos($data->cidade);
							?>
							<div id="formRota">
								<input type="hidden" name="latLngPosto" id="latLngPosto" value='<?php echo trim($data->endereco).", ".trim($data->numero).",".trim($data->cidade)."-".trim($data->estado); ?>' />
								<strong>REALIZAR ROTA ATÉ O POSTO AUTORIZADO</strong> > <span class="fontRed" style="font-size: 12px;"><?php echo $data->nome; ?></span><br /> <br />
								<sreong>Logradouro</strong> <br />
								<input style="margin-bottom: 3px;" type="text" name="end_cliente_rota" id="end_cliente_rota" size="50" /><br />
								<sreong>Número</strong> <br />
								<input style="margin-bottom: 3px;" type="text" name="numero_cliente_rota" id="numero_cliente_rota" size="50" /><br />
								<sreong>Bairro</strong> <br />
								<input style="margin-bottom: 3px;" type="text" name="bairro_cliente_rota" id="bairro_cliente_rota" size="50" /><br />
								<sreong>Cidade</strong> <br />
								<input style="margin-bottom: 3px;" type="text" name="cidade_cliente_rota" id="cidade_cliente_rota" size="50" /><br />
								<sreong>Estado</strong> <br />
								<input type="text" name="uf_cliente_rota" id="uf_cliente_rota" size="50" />
								<!-- <input type="text" name="end_cliente_rota" id="end_cliente_rota" size="50" placeholder="Endereço, Numero, Cidade e Estado..." /> -->
								<button type="button" class="buttonRota" onclick="realizaRota()">Realizar Rota</button>
								<img id="loading" src="../../../imagens/ajax-loader.gif" class="anexo_loading" style="width: 25px; height: 25px; display: none;" />
							</div>

							<div id='boxPosto'>
		                        <h1><?php echo $data->nome; ?></h1>
		                        <?php echo $data->fone; ?> <br />
		                        <?php echo $data->endereco.", ".$data->numero; ?> <br />
		                        <?php echo $data->bairro; ?> <br />
		                        <?php echo $data->cidade." - ".$uf; ?> <br />
		                        <?php echo "CEP: ". $data->cep; ?> <br /> <br />
		                        <?php echo "
		                        		<input type='hidden' name='posto_nome' id='posto_nome' value='".$data->nome."'>
		                        		<input type='hidden' name='posto_endereco' id='posto_endereco' value='".$data->endereco.", ".$data->numero."'>
		                        		<input type='hidden' name='posto_fone' id='posto_fone' value='".$data->fone."'>
		                        		<input type='hidden' name='posto_bairro' id='posto_bairro' value='".$data->bairro."'>
		                        		<input type='hidden' name='posto_cidade' id='posto_cidade' value='".$data->cidade." - ".$uf."'>
										<input type='hidden' name='posto_cep' id='posto_cep' value='".$data->cep."'>
		                        		<input type='hidden' name='posto_latitude' id='posto_latitude' value='".$data->lat."'> 
		                        		<input type='hidden' name='posto_longitude' id='posto_longitude' value='".$data->lng."'>" ?>
		                    </div>
							<?php
						}		

					}
				}
			?>
			</DIV>

			<br />

			</CENTER>

		</div> <!-- bodyData -->

		<br /> <br />

	</BODY>
</HTML>
