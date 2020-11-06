<?php
include_once '../dbconfig.php';
include_once '../includes/dbconnect-inc.php';
$sqlInsertLog = "INSERT INTO tbl_log_conexao(programa) VALUES ('$PHP_SELF')";
$resInsertLog = pg_query($con, $sqlInsertLog);
?>
<div class="mobile-only fullpage map">

   <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCyV4r86G_Jx0_c7_GabOaIRWUA7KyW6GU&sensor=false"></script>

        <script type="text/javascript">
            // When the window has finished loading create our google map below
            google.maps.event.addDomListener(window, 'load', init);

            function init() {

                var mapOptions = {
                    zoom: 14,
                    center: new google.maps.LatLng(-22.230469,-49.926849),
                    panControl: false,
                    zoomControl: false,
                    mapTypeControl: false,
                    scaleControl: false,
                    streetViewControl: false,
                    overviewMapControl: false,
                    scrollwheel: false,
                    styles: [{"featureType": "administrative.province", "elementType": "all", "stylers": [{"visibility": "off"} ] }, {"featureType": "landscape", "elementType": "all", "stylers": [{"saturation": -100 }, {"lightness": 65 }, {"visibility": "on"} ] }, {"featureType": "poi", "elementType": "all", "stylers": [{"saturation": -100 }, {"lightness": 51 }, {"visibility": "simplified"} ] }, {"featureType": "road", "elementType": "all", "stylers": [{"visibility": "on"} ] }, {"featureType": "road", "elementType": "labels", "stylers": [{"visibility": "off"} ] }, {"featureType": "road.highway", "elementType": "all", "stylers": [{"saturation": -100 }, {"visibility": "simplified"} ] }, {"featureType": "road.highway", "elementType": "labels", "stylers": [{"visibility": "off"} ] }, {"featureType": "road.arterial", "elementType": "all", "stylers": [{"saturation": -100 }, {"lightness": 30 }, {"visibility": "on"} ] }, {"featureType": "road.local", "elementType": "all", "stylers": [{"saturation": -100 }, {"lightness": 40 }, {"visibility": "on"} ] }, {"featureType": "transit", "elementType": "all", "stylers": [{"saturation": -100 }, {"visibility": "simplified"} ] }, {"featureType": "water", "elementType": "geometry", "stylers": [{"hue": "#ffff00"}, {"lightness": -25 }, {"saturation": -97 } ] }, {"featureType": "water", "elementType": "labels", "stylers": [{"visibility": "on"}, {"lightness": -25 }, {"saturation": -100 } ] } ]
                };
                var mapElement = document.getElementById('map-canvas');
                var map = new google.maps.Map(mapElement, mapOptions);
                var myLatLng = new google.maps.LatLng(-22.230469,-49.926849);
                var marker = new google.maps.Marker({
                position: myLatLng,
                map: map,
                });
                marker.setMap(map);
            }
   </script>

<div id="map-canvas"></div>

<div class="chegue">
    <div class="table">
        <div class="cell c1">
            <a class="map-close"><i class="fa fa-chevron-left"></i>Voltar</a>
        </div>
        <div class="cell c2">
            <a class="text-right" href="https://www.google.com.br/maps/place/FMC+novas+ideias/@-22.226334,-49.9039422,19z/data=!4m2!3m1!1s0x94bfd09d2bd8d037:0x4b99c8c95d2fb8e3" target="_blank">Traçar uma rota<i class="fa fa-location-arrow"></i></a>
        </div>
    </div>
</div>

</div>
