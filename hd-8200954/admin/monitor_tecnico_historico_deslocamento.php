<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include __DIR__.'/funcoes.php';

if ($_GET["ajax_load_historical_displacement"]) {
    try {
        $technical = $_GET["technical"];
        $date = date("Y-m-d", strtotime($_GET["date"]));

        if (!strlen($technical)) {
            throw new Exception("Técnico não informado");
        }

        if (empty($date)) {
            throw new Exception("Data inválida");
        }

        $coordinates = array();

        $sql = "
            SELECT DISTINCT
                latitude,
                longitude,
                data_input AS date,
                data_input
            FROM tbl_tecnico_monitoramento
            WHERE fabrica = {$login_fabrica}
            AND tecnico = {$technical}
            AND data_input BETWEEN '{$date} 00:00:00' AND '{$date} 23:59:59'
            ORDER BY data_input ASC
        ";
        $res = pg_query($con, $sql);

        if (strlen(pg_last_error()) > 0) {
            throw new Exception("Erro ao carregar histórico de deslocamento");
        }

        if (!pg_num_rows($res)) {
            throw new Exception("Sem histórico de deslocamento para a data escolhida");
        }

        while ($row = pg_fetch_object($res)) {
            $coordinates[] = array(
                "latitude"  => $row->latitude,
                "longitude" => $row->longitude,
                "date"      => $row->date
            );
        }

        $sql = "
            SELECT
                os.os AS order_number,
                os.consumidor_nome AS client_name,
                osce.campos_adicionais AS extra,
                TO_CHAR(ta.data_agendamento, 'DD/MM/YYYY HH24:MI') AS scheduled,
                TO_CHAR(ta.hora_inicio_trabalho, 'DD/MM/YYYY HH24:MI') AS start,
                TO_CHAR(ta.hora_fim_trabalho, 'DD/MM/YYYY HH24:MI') AS \"end\"
            FROM tbl_tecnico_agenda AS ta
            INNER JOIN tbl_os AS os ON os.os = ta.os AND os.fabrica = {$login_fabrica}
            INNER JOIN tbl_os_campo_extra AS osce ON osce.os = os.os
            WHERE ta.fabrica = {$login_fabrica}
            AND ta.tecnico = {$technical}
            AND ta.hora_inicio_trabalho BETWEEN '{$date} 00:00:00' AND '{$date} 23:59:59'
        ";
        $res = pg_query($con, $sql);

        if (strlen(pg_last_error()) > 0) {
            throw new Exception("Erro ao carregar histórico de deslocamento");
        }

        $os = array();

        if (pg_num_rows($res) > 0) {
            while ($row = pg_fetch_object($res)) {
                $extra = json_decode($row->extra, true);

                $latitude  = $extra["cliente_latitude"];
                $longitude = $extra["cliente_longitude"];

                $os[] = array(
                    "order_number" => $row->order_number,
                    "client_name"  => utf8_encode($row->client_name),
                    "scheduled"    => $row->scheduled,
                    "start"        => (empty($row->start)) ? "" : $row->start,
                    "end"          => (empty($row->end)) ? "" : $row->end,
                    "latitude"     => $latitude,
                    "longitude"    => $longitude
                );
            }
        }

        exit(json_encode(array(
            "coordinates" => $coordinates,
            "os" => $os
        )));
    } catch (Exception $e) {
        exit(json_encode(array("error" => utf8_encode($e->getMessage()))));
    }
}

$technical_id = $_GET["technical_id"];

?>

<link type="text/css" rel="stylesheet" media="screen" href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css" />
<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/ajuste.css" />
<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/extra.css" />

<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js" ></script>
<script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js" ></script>
<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.datepicker.min.js" ></script>

<link href="plugins/leaflet/leaflet.css" rel="stylesheet" type="text/css" />
<script src="plugins/leaflet/leaflet.js" ></script>		
<script src="plugins/leaflet/map.js" ></script>

<?php
$plugins = array(
    "bootstrap3"
);

include __DIR__.'/plugin_loader.php';
?>

<style>

#map {
   height: 60%;
}

</style>

<div class="alert alert-info col-xs-12 col-sm-12 col-md-12 col-lg-12" ><strong>Selecione um dia para carregar o histórico do técnico</strong></div>
<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12" >
    <div style="width: 190px; margin: 0 auto;" >
        <div id="datepicker" ></div>
    </div>

    <hr />
</div>

<div id="map" class="col-xs-12 col-sm-12 col-md-12 col-lg-12" ></div>

<script>

var technical = <?=$_GET["technical_id"]?>;

function loadHistoricalDisplacement(date) {
    $.ajax({
        url: "monitor_tecnico_historico_deslocamento.php",
        type: "get",
        contentType: "application/json",
        dataType: "json",
        data: {
            ajax_load_historical_displacement: true,
            technical: technical,
            date: (date.getFullYear()+"-"+(date.getMonth() + 1)+"-"+date.getDate())
        },
        beforeSend: function() {
            $("#map").hide();
            $("#map").after("<div class='alert alert-warning col-lg-12 col-md-12 col-sm-12 col-xs-12' ><strong>Carregando histórico aguarde...</strong></div>");
            $("#datepicker").datepicker("option", "disabled", true);
            historicalDisplacement.clear();
            historicalDisplacement.remove();
            markers.clear();
            markers.remove();
        }
    }).fail(function() {
        alert("Erro ao carregar histórico de deslocamento");

        $("#datepicker").datepicker("option", "disabled", false);
        $("#map").next("div.alert").remove();
        $("#map").show();
    }).done(function(response) {
        if (response.error) {
            alert(response.error);

            $("#datepicker").datepicker("option", "disabled", false);
            $("#map").next("div.alert").remove();
            $("#map").show();
        } else {
            //Initial point
            markers.add(
                response.coordinates[0].latitude, 
                response.coordinates[0].longitude, 
                "Ponto Inicial", 
                "<strong>"+response.coordinates[0].date+"</strong>", 
                "medium", 
                "#5CB85C"
            );

            //End Point
            markers.add(
                response.coordinates[(response.coordinates.length - 1)].latitude,
                response.coordinates[(response.coordinates.length - 1)].longitude,
                "Ponto Final",
                "<strong>"+response.coordinates[(response.coordinates.length - 1)].date+"</strong>",
                "medium",
                "#D9534F"
            );

            //Service Orders
            if (response.os.length > 0) {
                response.os.forEach(function(os, i) {
                    var description = "\
                        <strong>Cliente</strong> "+os.client_name+"<br />\
                        <strong>Agendado</strong> "+os.scheduled+"<br />\
                        <strong>Iniciado</strong> "+os.start+"<br />\
                        <strong>Finalizado</strong> "+os.end+"\
                    ";

                    markers.add(
                        os.latitude,
                        os.longitude,
                        "OS "+os.order_number,
                        description,
                        "large",
                        "#337AB7"
                    );
                });
            }

            //Coordinates
            var coordinates = [];

            response.coordinates.forEach(function(coordinate, i) {
                coordinates.push([coordinate.longitude, coordinate.latitude]);
            });

            historicalDisplacement.add(coordinates);

            $("#datepicker").datepicker("option", "disabled", false);
            $("#map").next("div.alert").remove();
            $("#map").show();

            //Load on Map
            markers.render();
            markers.focus();
            historicalDisplacement.render();
        }
    });
}

$.fn.datepickerPTBR();
$("#datepicker").datepicker({
    showOtherMonths: true,
    selectOtherMonths: true,
    maxDate: 0,
    dateFormat: "dd/mm/yy",
    onSelect: function(dateText, inst) {
        loadHistoricalDisplacement($(this).datepicker("getDate"));
    }
});

var map                    = new Map("map");
var markers                = new Markers(map);
var historicalDisplacement = new Router(map);

map.load();

</script>