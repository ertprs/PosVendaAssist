<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios = "call_center";
include "autentica_admin.php";
include "funcoes.php";

$ajax = (array_key_exists("ajax_refresh_content", $_POST)) ? true : false;

$sql_technical = "
    SELECT 
        tbl_tecnico.tecnico AS id,
        tbl_tecnico.latitude,
        tbl_tecnico.longitude,
        tbl_tecnico.nome AS name,
        tbl_tecnico.qtde_atendimento AS maximum_amount,
        os.os,
        os.client_name,
        os.status,
        os.status_color,
        (
            CASE WHEN os.status = 'Em Deslocamento' THEN
                2
            WHEN os.status = 'Em Execução' THEN
                1
            ELSE
                0
            END
        ) AS os_order
    FROM tbl_posto_fabrica
    INNER JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.tecnico_proprio IS TRUE
    INNER JOIN tbl_tecnico ON tbl_tecnico.posto = tbl_posto_fabrica.posto AND tbl_tecnico.fabrica = {$login_fabrica}
    LEFT JOIN (
        SELECT 
            tbl_os.os,
            tbl_os.consumidor_nome AS client_name,
            tbl_status_checkpoint.descricao AS status,
            tbl_status_checkpoint.cor AS status_color,
            tbl_tecnico_agenda.tecnico
        FROM tbl_tecnico_agenda
        INNER JOIN tbl_os ON tbl_tecnico_agenda.os = tbl_os.os AND tbl_os.fabrica = {$login_fabrica}
        INNER JOIN tbl_status_checkpoint ON tbl_status_checkpoint.status_checkpoint = tbl_os.status_checkpoint AND tbl_status_checkpoint.descricao IN('Em Execução', 'Em Deslocamento')
        WHERE tbl_tecnico_agenda.fabrica = {$login_fabrica}
    ) AS os ON os.tecnico IS NOT NULL AND os.tecnico = tbl_tecnico.tecnico
    WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
    AND (
        tbl_tecnico.latitude IS NOT NULL
        AND tbl_tecnico.longitude IS NOT NULL
    )
    ORDER BY os_order DESC
";
$res_technical = pg_query($con, $sql_technical);

if ($ajax && !pg_num_rows($res_technical)) {
    exit(json_encode(array(
        "error" => utf8_encode("Erro ao buscar técnicos")
    )));
}

$technical_array = array_map(function($r) use($ajax) {
    if ($ajax) {
        $r["name"]   = utf8_encode($r["name"]);

        switch ($r["status"]) {
            case "Em Deslocamento":
                $r["status_class"] = "status-displacement";
                break;

            case "Em Execução":
                $r["status_class"] = "status-working";
                break;
            
            default:
                $r["status_class"] = "status-without-service";
                $r["status"]       = "Sem Atendimento";
                break;
        }
    }

    $r["status"] = utf8_encode($r["status"]);

    return $r;
}, pg_fetch_all($res_technical));

$technical_json = json_encode($technical_array);

if ($ajax) {
    exit($technical_json);
}

$title = "Monitor de Técnicos";
$layout_menu = "callcenter";

include "cabecalho_new.php";

$plugins = array(
    "shadowbox",
    "dataTable"
);

include __DIR__.'/plugin_loader.php';

?>

<link href="plugins/leaflet/leaflet.css" rel="stylesheet" type="text/css" />
<script src="plugins/leaflet/leaflet.js" ></script>		
<script src="plugins/leaflet/map.js" ></script>

<style>

#map {
    height: 300px;
    width: 100%;
}

td.status-displacement {
    font-weight: bold;
    color: #4675FF;
}

td.status-working {
    font-weight: bold;
    color: #676767;
}

td.status-without-service {
    font-weight: bold;
    color: #FF4343;
}

#sb-container {
    z-index: 9999;
}

.icon-refresh {
    transform: rotate(0deg);
}

.icon-refresh-animate {
    transform: rotate(360deg);
    transition: transform 1s linear;
}

</style>

<table class="table table-fixed table-bordered" style="table-layout: fixed;" >
    <thead>
        <tr class="titulo_coluna" >
            <th colspan="4">Status (clique para filtrar)</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <th class="status_filter" data-filter="all" style="background-color: #FFFFFF; color: #000000;" ><i class="icon-map-marker"></i> Todos</th>
            <th class="status_filter" data-filter="on_move" style="background-color: #4675FF; color: #FFFFFF;" ><i class="icon-map-marker icon-white"></i> Em Deslocamento</th>
            <th class="status_filter" data-filter="running" style="background-color: #676767; color: #FFFFFF" ><i class="icon-map-marker icon-white"></i> Em Execução</th>
            <th class="status_filter" data-filter="without_service" style="background-color: #FF4343; color: #FFFFFF;" ><i class="icon-map-marker icon-white"></i> Sem Atendimento</th>
        </tr>
    </tbody>
</table>

<div id="map" ></div>

<hr />

<table id="list" class="table table-bordered table-striped table-fixed" >
    <thead>
        <tr class="titulo_coluna" >
            <th colspan="5" >
                <button type="button" class="btn btn-success btn-small view-all-technicals-on-map pull-right">
                    <i class="icon-map-marker icon-white" ></i> Ver todos os técnicos
                </button>

                <button type="button" class="btn btn-primary btn-small content-refresh pull-right" style="margin-right: 10px;" title="Atualizar conteúdo" >
                    <i class="icon-refresh icon-white" ></i>
                </button>
            </th>
        </tr>
        <tr class="titulo_coluna" >
            <th>Técnico</th>
            <th>OS</th>
            <th>Cliente</th>
            <th>Status</th>
            <th>&nbsp;</th>
        </tr>
    </thead>
    <tbody>
        <?php
        foreach ($technical_array as $technical) {
            $technical["status"] = utf8_decode($technical["status"]);

            switch ($technical["status"]) {
                case "Em Deslocamento":
                    $status_class = "status-displacement";
                    break;

                case "Em Execução":
                    $status_class = "status-working";
                    break;
                
                default:
                    $status_class = "status-without-service";
                    $technical["status"] = "Sem Atendimento";
                    break;
            }
            ?>
            <tr data-technical-id="<?=$technical["id"]?>" data-maximum-amount="<?=$technical["maximum_amount"]?>" data-latitude="<?=$technical["latitude"]?>" data-longitude="<?=$technical["longitude"]?>" >
                <td><?=$technical["name"]?></td>
                <td><?=$technical["os"]?></td>
                <td><?=$technical["client_name"]?></td>
                <td class="<?=$status_class?>" ><?=$technical["status"]?></td>
                <td class="tac" nowrap >
                    <button type="button" class="btn btn-success btn-small view-on-map" title="Visualizar no mapa" ><i class="icon-map-marker icon-white" ></i></button>
                    <button type="button" class="btn btn-primary btn-small view-schedule" title="Ver agenda do técnico" ><i class="icon-calendar icon-white" ></i></button>
                    <button type="button" class="btn btn-info btn-small view-history-route" title="Visualizar histórico de rota do técnico" ><i class="icon-road icon-white" ></i></button>
                </td>
            </tr>
        <?php
        }
        ?>
    </tbody>
</table>

<script>

var markers_on_move         = [];
var markers_running         = [];
var markers_without_service = [];

/**
 * merge markers array and add to markers class
 * @param {array} status with strings: on_move, running or without_service
 */
function mergeAndShowMarkers(status) {
    markers.clear();
    markers.remove();

    var markers_array = [];

    if ($.inArray("on_move", status) != -1) {
        markers_array = markers_array.concat(markers_on_move);
    }

    if ($.inArray("running", status) != -1) {
        markers_array = markers_array.concat(markers_running);
    }

    if ($.inArray("without_service", status) != -1) {
        markers_array = markers_array.concat(markers_without_service);
    }

    markers_array.forEach(function(data, i) {
        markers.add(
            data.latitude, 
            data.longitude, 
            data.name, 
            data.description, 
            data.size, 
            data.color, 
            data.extra_properties
        );
    });
}

Shadowbox.init();

var map     = new Map("map");
var markers = new Markers(map);

map.load("map");

function populateMarkersArray(technical_json) {
    var technical_array = technical_json;

    markers_on_move         = [];
    markers_running         = [];
    markers_without_service = [];

    technical_array.forEach(function(t, i) {
        if (t.os == null) {
            var description = "Sem atendimento";
        } else {
            var description = "OS "+t.os+" "+t.status+"<br />"+t.client_name;
        }

        if (t.status != null && t.status.length > 0 && t.status != "Sem Atendimento") {
            var status_color = t.status_color;
        } else {
            var status_color = "#FF4343";
        }

        var data = {
            latitude: t.latitude, 
            longitude: t.longitude, 
            name: t.name, 
            description: description, 
            size: "medium", 
            color: status_color, 
            extra_properties: { 
                "technical-id": t.id,
                "technical-name": t.name
            }
        };

        switch (t.status) {
            case "Em Deslocamento":
                markers_on_move.push(data);
                break;

            case "Em Execução":
                markers_running.push(data);
                break;

            default:
                markers_without_service.push(data);
                break;
        }
    });
}

populateMarkersArray(<?=$technical_json?>);

mergeAndShowMarkers(["on_move", "running", "without_service"]);

markers.render();

markers.onClick(function(e) {
    var name = e.layer.feature.properties["technical-name"];

    map.setView(e.latlng.lat, e.latlng.lng, 15);

    $("div.dataTables_filter input").val(name).keyup();
});

markers.focus();

$("button.view-all-technicals-on-map").on("click", function() {
    markers.focus();
    $("div.dataTables_filter input").val("").keyup();
});

$(document).on("click", "button.view-on-map", function() {
    var tr = $(this).parents("tr");

    var latitude  = parseFloat($(tr).data("latitude"));
    var longitude = parseFloat($(tr).data("longitude"));

    map.setView(latitude, longitude, 15);
});

$(document).on("click", "button.view-schedule", function() {
    var tr = $(this).parents("tr");

    var technical_id   = $(tr).data("technical-id");
    var maximum_amount = $(tr).data("maximum-amount");

    Shadowbox.open({
        content: "monitor_tecnico_agenda.php?technical_id="+technical_id+"&maximum_amount="+maximum_amount,
        player: "iframe"
    });
});

$(document).on("click", "button.view-history-route", function() {
    var tr = $(this).parents("tr");

    var technical_id = $(tr).data("technical-id");

    Shadowbox.open({
        content: "monitor_tecnico_historico_deslocamento.php?technical_id="+technical_id,
        player: "iframe"
    });
});

$.dataTableLoad({
    table: "#list",
    type: "custom",
    config: ["pesquisa"]
});

$("th.status_filter").on("click", function() {
    var filter = $(this).data("filter");

    if (filter == "all") {
        mergeAndShowMarkers(["on_move", "running", "without_service"]);
        $("div.dataTables_filter input").val("").keyup();
    } else {
        mergeAndShowMarkers([filter]);

        switch (filter) {
            case "on_move":
                var datatable_filter = "Em Deslocamento";
                break;

            case "running":
                var datatable_filter = "Em Execução";
                break;

            case "without_service":
                var datatable_filter = "Sem Atendimento";
                break;
        }

        $("div.dataTables_filter input").val(datatable_filter).keyup();
    }

    markers.render();
    markers.focus();
});

$("button.content-refresh").on("click", function() {
    var btn   = $(this);
    var icon  = $(this).find("i.icon-refresh");
    var table = $(this).parents("#list");
    var tbody = $(table).find("tbody");

    if ($(table).data("refreshing-content")) {
        return false;
    }

    $(table).data({ "refreshing-content": true });

    var animate = setInterval(function() {
        var p = new Promise(function(resolve, reject) { 
            $(btn).prop({ disabled: true });
            $(icon).addClass("icon-refresh-animate");

            setTimeout(function() { 
                resolve(true); 
            }, 1000); 
        }).then(function(r) {
            $(icon).removeClass("icon-refresh-animate");
        });
    }, 1100);

    var stopAnimate = function(success) {
        clearInterval(animate);
        $(icon).removeClass("icon-refresh-animate");
        $(table).data({ "refreshing-content": false });
        $(btn).prop({ disabled: false });

        if (success) {
            $(btn).parents("tr").find("th").append("<span class='label label-success pull-right' style='margin-right: 10px;'>Atualizado</label>");

            setTimeout(function() {
                $(btn).parents("tr").find("span.label-success").remove();
            }, 3000);
        }
    };

    $.ajax({
        url: "monitor_tecnico.php",
        type: "post",
        data: { 
            ajax_refresh_content: true
        },
        timeout: 60000,
        beforeSend: function() {
            $(btn).parents("tr").find("span.label-success").remove();
        }
    }).fail(function(response) {
        alert("Ocorreu um erro ao atualizar o conteúdo");
        stopAnimate();
    }).done(function(response) {
        response = JSON.parse(response);

        if (response.error) {
            alert(response.error);
            stopAnimate();
        } else {
            var dataTable;

            dataTable = new Promise(function(resolve, reject) {
                dataTableGlobal.fnDestroy(false);
                $(tbody).html("");

                resolve(true);
            }).then(function(res) {
                var populateTable = new Promise(function(resolve, reject) {
                    [].forEach.call(response, function(technical, i) {
                        if (technical.os == null) {
                            technical.os = "";
                        }

                        if (technical.client_name == null) {
                            technical.client_name = "";
                        }

                        if (technical.maximum_amount == null) {
                            technical.maximum_amount = "";
                        }

                        if (technical.status_color == null) {
                            technical.status_color = "";
                        }

                        response[i] = technical;
                    });

                    $.each(response, function(i, technical) {
                        $(tbody).append("\
                            <tr data-technical-id='"+technical.id+"' data-maximum-amount='"+technical.maximum_amount+"' data-latitude='"+technical.latitude+"' data-longitude='"+technical.longitude+"' >\
                                <td>"+technical.name+"</td>\
                                <td>"+technical.os+"</td>\
                                <td>"+technical.client_name+"</td>\
                                <td class='"+technical.status_class+"' >"+technical.status+"</td>\
                                <td class='tac' nowrap >\
                                    <button type='button' class='btn btn-success btn-small view-on-map' title='Visualizar no mapa' ><i class='icon-map-marker icon-white' ></i></button>\
                                    <button type='button' class='btn btn-primary btn-small view-schedule' title='Ver agenda do técnico' ><i class='icon-calendar icon-white' ></i></button>\
                                    <button type='button' class='btn btn-info btn-small view-history-route' title='Visualizar histórico de rota do técnico' ><i class='icon-road icon-white' ></i></button>\
                                </td>\
                            </tr>\
                        ");
                    });

                    populateMarkersArray(response);

                    resolve(true);
                }).then(function(res) {
                    $.dataTableLoad({
                        table: "#list",
                        type: "custom",
                        config: ["pesquisa"]
                    });

                    mergeAndShowMarkers(["on_move", "running", "without_service"]);

                    markers.render();

                    markers.onClick(function(e) {
                        var name = e.layer.feature.properties["technical-name"];

                        map.setView(e.latlng.lat, e.latlng.lng, 15);

                        $("div.dataTables_filter input").val(name).keyup();
                    });

                    var scrollToMap = false;
                    markers.focus(scrollToMap);

                    stopAnimate(true);
                });
            });
        }
    });
});

setInterval(function() {
    $("button.content-refresh").trigger("click");
}, 300000);

</script>

<?php

include "rodape.php"; 

?>
