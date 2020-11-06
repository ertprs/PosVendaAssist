<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include_once 'autentica_admin.php';
include_once 'funcoes.php';
include_once '../helpdesk/mlg_funciones.php';

$proximo = filter_input(INPUT_GET,"proximo");
$cliente = filter_input(INPUT_GET,"cliente");
$codPosto = filter_input(INPUT_GET,"codPosto");
$nomePosto = filter_input(INPUT_GET,"nomePosto");
$kmProximo = filter_input(INPUT_GET,"kmProximo");


$sql = "
    SELECT  tbl_posto_fabrica.contato_endereco,
            tbl_posto_fabrica.contato_numero,
            tbl_posto_fabrica.contato_bairro,
            tbl_posto_fabrica.contato_cidade,
            tbl_posto_fabrica.contato_estado
    FROM    tbl_posto_fabrica
    WHERE   fabrica = $login_fabrica
    AND     tbl_posto_fabrica.codigo_posto = '$codPosto';

";
$res = pg_query($con,$sql);
?>
<!DOCTYPE html>
<html>
<head>
<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/bootstrap.css" />
<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/extra.css" />
<link type="text/css" rel="stylesheet" media="screen" href="css/tc_css.css" />
<link type="text/css" rel="stylesheet" media="screen" href="css/tooltips.css" />
<link type="text/css" rel="stylesheet" media="screen" href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css">
<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/ajuste.css" />
<link href="plugins/leaflet/leaflet.css" rel="stylesheet" type="text/css" />

<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.core.min.js"></script>
<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.widget.min.js"></script>
<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.effect.min.js"></script>
<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.tabs.min.js"></script>
<script src="bootstrap/js/bootstrap.js"></script>
<script src="plugins/leaflet/leaflet.js" ></script>
<script src="plugins/leaflet/map.js" ></script>
<script src="plugins/mapbox/geocoder.js"></script>
<script src="plugins/mapbox/polyline.js"></script>

<?php
include("plugin_loader.php");
?>
<style type="text/css">
    #Maps {
        width: 75%;
        height: 500px;
        border: 1px black solid;
        margin: 0 auto;
    }
    .img-selecionada{
        border: solid 1px #d90000 !important;
        padding: 11px;
    }
    #div_table{
        text-align:center;
    }
    #grid_postos{
        margin: 0 auto;
        display: table;
    }
    p {
        margin-left: 15px;
        margin-right: 15px;
    }
</style>

</head>
<body>
    <div style="width: 100%; margin-top: 20px; margin-bottom: 5px;">
        <p>
            <strong>Mensagem importante</strong>: Temos um posto autorizado mais próximo do endereço do consumidor com o deslocamento de <?=number_format($kmProximo,3,',','')?>Km.
            <br />Favor, solicitar ao consumidor para procurar a assistência abaixo ou continue o atendimento, porém com o deslocamento do posto autorizado em questão.
        </p>
    </div>
    <div id="div_table">
        <table id="grid_postos" class='table table-striped table-bordered table-hover table-large' style="margin-top: 15px;">
            <thead>
                <tr class='titulo_coluna'>
                    <th>Nome do Posto</th>
                    <th>Endereço</th>
                    <th>Bairro</th>
                    <th>Cidade</th>
                    <th>UF</th>
                </tr>
            </thead>
            <tbody class="tbody">
<?php
while ($results = pg_fetch_object($res)) {
    $endereco   = $results->contato_endereco;
    $numero     = $results->contato_numero;
    $bairro     = $results->contato_bairro;
    $cidade     = $results->contato_cidade;
    $estado     = $results->contato_estado
?>
                <tr>
                    <td><?=$nomePosto?></td>
                    <td><?=$endereco?>, <?=$numero?></td>
                    <td><?=$bairro?></td>
                    <td><?=$cidade?></td>
                    <td><?=$estado?></td>
                </tr>
<?php
}
?>
            </tbody>
        </table>
        <button class="btn btn-success" name="ok" id="btn_ok">Ok</button>
        <button class="btn btn-danger" name="cancelar" id="btn_cancelar">Cancelar</button>
    </div>
    <br />
    <div id="Maps"></div>

</body>
<script type="text/javascript">

var Map                = new Map("Maps");
var Markers            = new Markers(Map);
var Router             = new Router(Map);
var Geocoder           = new Geocoder();
var geometry;
var km_ida;
var km_volta;
var kmtotal;

function calcRouteAjax(LatLngPosto, latlon)
{
    $.ajax({
        url: "controllers/TcMaps.php",
        type: "POST",
        dataType:"JSON",
        data: {
            ajax: "route",
            origem: LatLngPosto,
            destino: latlon,
            ida_volta: "sim"
        },
        timeout: 60000
    })
    .done(function(data){
        kmtotal = data.total_km.toFixed(2);
        geometry    = data.rota.routes[0].geometry;
        km_ida      = data.km_ida;
        km_volta    = data.km_volta;

        LatLngP = latLongProximo.split(',');
        LatLngC = latLongCliente.split(',');

        Markers.remove();
        Markers.clear();
        Markers.add(LatLngC[0], LatLngC[1], "blue", "Cliente");
        Markers.add(LatLngP[0], LatLngP[1], "red", "Posto");
        Markers.render();
        Markers.focus();

        Router.remove();
        Router.clear();
        Router.add(Polyline.decode(geometry));
        Router.render();
    }).fail(function(){
        alert('Erro ao tentar calcular a rota!');
    });
}

$(function(){
    Map.load();
    latLongProximo = "<?=$proximo?>";
    latLongCliente = "<?=$cliente?>";

    calcRouteAjax(latLongProximo,latLongCliente);

    $("#btn_cancelar").click(function(e){
        e.preventDefault();

        window.parent.location.reload();
    });

    $("#btn_ok").click(function(e){
        e.preventDefault();

        var endereco = "<?=$endereco." ".$numero." ".$bairro. " ".$cidade." ".$estado?>";

        window.parent.recebeDados(km_ida,km_volta,kmtotal,<?=$codPosto?>,latLongProximo,endereco);
    });
});
</script>
</html>

