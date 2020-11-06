<?php
require_once('../admin/dbconfig.php');
require_once('../admin/includes/dbconnect-inc.php');
require_once('../admin/funcoes.php');
$sqlInsertLog = "INSERT INTO tbl_log_conexao(programa) VALUES ('$PHP_SELF')";
$resInsertLog = pg_query($con, $sqlInsertLog);
?>
<html>

	<head>

		<!-- Google Maps -->
		<link href="http://code.google.com/apis/maps/documentation/javascript/examples/default.css" rel="stylesheet" type="text/css" />
		<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>
		<script src="http://google-maps-utility-library-v3.googlecode.com/svn/trunk/routeboxer/src/RouteBoxer.js" type="text/javascript"></script>

		<script type="text/javascript">
				
			var geocoder;
			var map;
		
			function initialize(){
			
				geocoder = new google.maps.Geocoder();
				var myLatlng = new google.maps.LatLng(-15, -55);
				var myOptions = {
					zoom: 14,
					center: myLatlng,
					mapTypeId: google.maps.MapTypeId.ROADMAP
				}
				var map = new google.maps.Map(document.getElementById("mapa"), myOptions);
				
				var latlng = new google.maps.LatLng(-22.2302649, -49.9268523);	

				var marker = new google.maps.Marker({
					map: map, 
					position: latlng
				}); 

				var infowindow = new google.maps.InfoWindow({
					content: "<div style='text-align: center; padding: 10px;'><p align='center'><img src='http://www.telecontrol.com.br/wp-content/uploads/2012/02/logo_tc_2009_texto.png' /></p> <br /> Av. Carlos Artêncio, 420-B - Marília / SP <br /> (11) 4063-4230 / (14) 3402-6588 <br /> <strong>www.telecontrol.com.br</strong> </div>"
				});

				map.setZoom(15);
				map.setCenter(latlng);

				google.maps.event.addListener(marker, 'click', function() {
					infowindow.open(map,marker);
				});

			}
			
			google.maps.event.addDomListener(window, 'load', initialize);
			
		</script>

	</head>

	<body>
		<div id="mapa" style="width: 550px; height: 310px;"></div>
	</body>

</html>