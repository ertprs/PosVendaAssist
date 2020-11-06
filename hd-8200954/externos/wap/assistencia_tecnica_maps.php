<?php
require_once('../../admin/dbconfig.php');
require_once('../../admin/includes/dbconnect-inc.php');
require_once('../../admin/funcoes.php');
use Posvenda\TcMaps;

$iconCar = 'img/pin-wap.png';
$iconPerson = 'img/person-wap.png';
if ($_GET['sp'] == 1) {
    $iconCar = 'img/pin-carrrier.png';
    $iconPerson = 'img/person-sp.png';
}


if (isset($_POST['ajax']) && isset($_POST['todospostos'])) {
    $fabrica = $_POST['fabrica'];

    $sql = "SELECT
                tbl_posto_fabrica.nome_fantasia ,
                tbl_posto_fabrica.latitude AS latitude ,
                tbl_posto_fabrica.longitude AS longitude
            FROM tbl_posto
            INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = {$fabrica}
            INNER JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
            WHERE  tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
                AND tbl_posto_fabrica.posto <> 6359
                AND tbl_posto_fabrica.divulgar_consumidor IS TRUE
                AND UPPER(tbl_tipo_posto.descricao) IN ('REDE AUTORIZADA')
                AND tbl_posto_fabrica.fabrica = {$fabrica}
            ORDER BY tbl_posto.cidade;";

    $res = pg_query($con, $sql);
    if (pg_num_rows($res) > 0) {
        $data = pg_fetch_all($res);
        exit("*" . json_encode($data));
    }
}

/* Busca Cidades */
if (isset($_POST['uf']) && isset($_POST['linha'])) {

    $uf      = $_POST['uf'];
    $linha   = $_POST['linha'];
    $fabrica = $_POST['fabrica'];


    $sql = "SELECT DISTINCT UPPER(TRIM(fn_retira_especiais(tbl_posto_fabrica.contato_cidade))) AS contato_cidade
            FROM tbl_posto_fabrica
            JOIN tbl_posto_linha ON tbl_posto_linha.posto = tbl_posto_fabrica.posto AND
                                    tbl_posto_linha.linha = $linha
            WHERE tbl_posto_fabrica.fabrica = {$fabrica}
                AND tbl_posto_fabrica.contato_estado = '$uf'
                AND tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
                AND tbl_posto_fabrica.posto <> 6359
                AND tbl_posto_fabrica.divulgar_consumidor IS TRUE ORDER BY contato_cidade ASC";
    $res = pg_query($con, $sql);
    if (pg_num_rows($res) > 0) {
        echo "<option value=''>Todas</option>\n";
        while ($data = pg_fetch_object($res)) {
            echo "\t<option value='$data->contato_cidade'>".ucwords(mb_strtolower(retira_acentos($data->contato_cidade)))."</option>\n";
        }
    }else{
        //echo "<option value=''>Nenhum Posto Autorizado localizado para este estado</option>";
        echo 'POSTO NAO ENCONTRADO';
    }

    exit;
}

$token        = trim($_GET['tk']);
$token_post   = $_POST['token'];
$cod_fabrica  = $_GET['cf'];
$cod_fabrica  = base64_decode(trim($cod_fabrica));

$nome_fabrica = $_GET['nf'];
$nome_fabrica = base64_decode(trim($nome_fabrica));

if (!empty($_POST['fabrica'])) {
    $sql = "SELECT nome FROM tbl_fabrica WHERE fabrica = " . $_POST['fabrica'];
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {
        $cod_fabrica = $_POST['fabrica'];
        $nome_fabrica = pg_fetch_result($res, 0, 0);
    }
}

$token_comp = base64_encode(trim("telecontrolNetworking" . $nome_fabrica . "assistenciaTecnica" . $cod_fabrica));
if (!empty($token_post)) $token = $token_post;
if ($token != $token_comp) {
    exit;
}

function maskCep($cep)
{
    $num_cep = preg_replace('/\D/', '', $cep);
    return (strlen($cep == 8)) ? preg_replace('/(\d\d)(\d{3})(\d{3})/', '$1.$2-$3', $num_cep) : $cep;
}

function maskFone($telefone)
{
    if (!strstr($telefone, "(")) {
        $telefone = str_replace("-", '', $telefone);
        $inicio   = substr($telefone, 0, 2);
        $meio     = substr($telefone, 2, 4);
        $fim      = substr($telefone, 6, strlen($telefone));
        $telefone = "(" . $inicio . ") " . $meio . "-" . $fim;
    }

    return $telefone;
}

function retira_acentos($texto)
{
    $array1 = array(
        '·', '‡', '‚', '„', '‰', 'È', 'Ë', 'Í', 'Î', 'Ì', 'Ï', 'Ó', 'Ô', 'Û', 'Ú', 'Ù', 'ı', 'ˆ', '˙', '˘', '˚', '¸', 'Á', '¡', '¿', '¬', '√', 'ƒ', '…', '»', ' ', 'À', 'Õ', 'Ã', 'Œ', 'œ', '”', '“', '‘', '’', '÷', '⁄', 'Ÿ', '€', '‹', '«'
    );
    $array2 = array(
        'a', 'a', 'a', 'a', 'a', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'c', 'A', 'A', 'A', 'A', 'A', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'C'
    );
    return str_replace($array1, $array2, $texto);
}
function formatCEP($cepString)
{
    $cepString = str_replace("-", "", $cepString);
    $cepString = str_replace(".", "", $cepString);
    $cepString = str_replace(",", "", $cepString);
    $antes = substr($cepString, 0, 5);
    $depois = substr($cepString, 5);
    $cepString = $antes . "-" . $depois;
    return $cepString;
}
function getLatLonConsumidor($logradouro = null, $bairro = null, $cidade, $estado, $pais = "BR")
{
    global $con, $fabrica;
    $oTcMaps = new TcMaps($fabrica, $con);

    try {
        $retorno = $oTcMaps->geocode($logradouro, null, $bairro, $cidade, $estado, $pais);
        return $retorno['latitude'] . "@" . $retorno['longitude'];
    } catch (Exception $e) {
        return false;
    }
}

/* Busca os Postos Autorizados */
if (isset($_POST['linha']) && (isset($_POST['estado']) && isset($_POST['cidade']) || isset($_POST['cep']))) {

    $linha       = $_POST['linha'];
    $cep         = $_POST['cep'];
    $estado      = $_POST['estado'];  
    $cidade      = $_POST['cidade'];  
    $fabrica     = $_POST['fabrica'];
    $endCliente  = $_POST['end_cliente'];

    $cond_estado = "";
    $cond_cidade = "";
    $order_by    = "tbl_posto_fabrica.contato_cidade,tbl_posto_fabrica.nome_fantasia";

    if (!empty($cep)) {

        $local = (strlen($cep) > 0) ? formatCEP($cep) : "";

        list($endereco, $bairro, $cidade, $estado) = explode(":", $endCliente);
        $latLonConsumidor = getLatLonConsumidor($endereco, $bairro, $cidade, $estado);
        $parte = explode('@', $latLonConsumidor);

        $from_lat = substr(trim($parte[0]), 0, 7);
        $from_lon = substr(trim($parte[1]), 0, 7);

        $coluna_distancia = ", (111.045 * DEGREES(ACOS(COS(RADIANS({$from_lat})) * COS(RADIANS(tbl_posto_fabrica.latitude)) * COS(RADIANS(tbl_posto_fabrica.longitude) - RADIANS({$from_lon})) + SIN(RADIANS({$from_lat})) * SIN(RADIANS(tbl_posto_fabrica.latitude))))) AS distancia";
        $cond_distancia = "AND (111.045 * DEGREES(ACOS(COS(RADIANS({$from_lat})) * COS(RADIANS(tbl_posto_fabrica.latitude)) * COS(RADIANS(tbl_posto_fabrica.longitude) - RADIANS({$from_lon})) + SIN(RADIANS({$from_lat})) * SIN(RADIANS(tbl_posto_fabrica.latitude))))) < 100";
        $order_by = "distancia ASC";
    } else {

        if (!empty($cidade)) {
            $cond_cidade = " AND UPPER(TO_ASCII(tbl_posto_fabrica.contato_cidade, 'LATIN9')) = UPPER(TO_ASCII('$cidade', 'LATIN9')) ";
        }

        $cond_estado = " AND tbl_posto_fabrica.contato_estado = '$estado' ";
        $order_by = " tbl_posto_fabrica.contato_cidade ASC ";


        //echo "Nenhuma AssistÍncia TÈcnica Autorizada localizada para este CEP";
        //exit;
    }

    $sql = " SELECT
                    distinct tbl_posto.posto ,
                    tbl_posto.nome ,
                    tbl_posto_fabrica.nome_fantasia ,
                    tbl_posto_fabrica.contato_cep AS cep ,
                    tbl_posto_fabrica.latitude AS lat ,
                    tbl_posto_fabrica.longitude AS lng ,
                    tbl_posto_fabrica.contato_fone_comercial AS telefone ,
                    tbl_posto_fabrica.contato_email AS email ,
                    tbl_posto_fabrica.contato_endereco AS endereco ,
                    tbl_posto_fabrica.contato_numero AS numero ,
                    tbl_posto_fabrica.contato_cidade AS cidade ,
                    tbl_posto_fabrica.contato_bairro AS bairro,
                    tbl_posto_fabrica.contato_complemento AS complemento
                    {$coluna_distancia}
              FROM tbl_posto
              JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = {$fabrica}
              JOIN tbl_posto_linha   ON tbl_posto.posto = tbl_posto_linha.posto AND tbl_posto_linha.linha in ({$linha})
              JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
             WHERE tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
                   {$cond_distancia}
                   {$cond_cidade}
                   {$cond_estado}
               AND tbl_posto_fabrica.posto <> 6359
               AND tbl_posto_fabrica.divulgar_consumidor IS TRUE
               AND tbl_posto_fabrica.senha <> '*'
               AND tbl_posto_fabrica.fabrica = {$fabrica}
               AND UPPER(tbl_tipo_posto.descricao) IN ('REDE AUTORIZADA')
          ORDER BY  {$order_by}
          LIMIT 10";

    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {


        $cor = "";
        $i = 0;

        while ($data = pg_fetch_object($res)) {
            /* Mascara CEP */
            $cep = maskCep($data->cep);

            /* Mascara Telefone */
            $telefone = maskFone($data->telefone);

            if (strlen(trim($data->nome_fantasia)) > 0 && $data->nome_fantasia != "null") {
                $nome_fantasia = strtoupper(retira_acentos($data->nome_fantasia));
                $nome = $data->nome . "<br />";
            } else {
                $nome_fantasia = strtoupper(retira_acentos($data->nome));
                $nome = "";
            }

            $nome = preg_replace('/(\d{11})/', '', $nome);

            $cor = ($i % 2 == 0) ? "#EEF" : "#FFF";

            echo "
                <div class='row row-posto' data-lat='{$data->lat}' data-lng='{$data->lng}' style='display: none;'>
                    <div class='col-md-12'>
                        <p style='border-bottom: 1px solid #CCCCCC; padding-bottom: 20px;'>
                            <br />
                            <strong><img width='10' style='float: left;margin-top: 3px;margin-right: 10px;' src='img/point-wap.png' /> $nome_fantasia</strong> <br />
                            $nome
                            $data->endereco, $data->numero  $data->complemento&nbsp; / &nbsp; CEP: $cep <br />
                            BAIRRO: $data->bairro &nbsp; / &nbsp; $data->cidade - $uf <br />
                            <img width='10' style='float: left;margin-top: 3px;margin-right: 10px;' src='img/phone-wap.png' /> $telefone &nbsp; / &nbsp; " . strtolower($data->email) . " <br />
                            <button type='button' class='btn btn-default' onclick=\"localizarMap('" . $data->lat . "', '" . $data->lng . "')\" style='margin-top: 10px;'><i class='glyphicon glyphicon-search'></i> Localizar</button>
                        </p>
                    </div>
                </div>";

            if (strlen(trim($data->nome_fantasia)) > 0 && $data->nome_fantasia != "null") {
                $nome_fantasia = strtoupper(retira_acentos($data->nome_fantasia));
            } else {
                $nome_fantasia = strtoupper(retira_acentos($data->nome));
            }

            $lat_lng[] = array(
                "nome_fantasia" => utf8_encode($nome_fantasia),
                "latitude" => $data->lat,
                "longitude" => $data->lng
            );

            $i++;
        }

        $lat_lng = json_encode($lat_lng);

        echo "*" . $lat_lng . "*" . json_encode(["consumidor" => $latLonConsumidor]);
    } else {
        echo 'Nenhuma AssistÍncia TÈcnica Autorizada Localizada';
    }

    exit;
}

$titulo_mapa_rede = 'AssistÍncia TÈcnica';
$titulo_mapa_rede .= ' - ' . $nome_fabrica;

?>
<!DOCTYPE html>
<html lang='en'>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $titulo_mapa_rede ?></title>
    <link href="../bootstrap3/css/bootstrap.min.css" type="text/css" rel="stylesheet" media="screen" />
    <link href="css/default.css?v=<?php echo date('YmdHis'); ?>" type="text/css" rel="stylesheet" media="screen" />
    <link href="css/fonts.css" type="text/css" rel="stylesheet" media="screen" />
    <!--[if lt IE 10]>
        <link rel="stylesheet" type="text/css" media="screen" href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-ie.css" />
        <link rel='stylesheet' type='text/css' href="bootstrap/css/ajuste_ie.css">
        <![endif]-->

    <script type="text/javascript" src="../../admin/plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
    <!-- MAPBOX -->
    <link href="../../plugins/leaflet/leaflet.css" rel="stylesheet" type="text/css" />
    <script src="../../plugins/leaflet/leaflet.js"></script>
    <script src="../../plugins/leaflet/map.js?v=<?php echo date('YmdHis'); ?>"></script>
    <script>
        var MapaTelecontrol = Map;
    </script>
    <script src="../../plugins/mapbox/geocoder.js"></script>
    <script src="../../plugins/mapbox/polyline.js"></script>
    <script src="https://www.google.com/recaptcha/api.js?hl=pt-BR&onload=showRecaptcha&render=explicit" async defer></script>
    <script src="../institucional/lib/mask/mask.min.js"></script>

    <style type="text/css">

        @import "https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700";

        body {
            font-size: 15px !important;
            background-color: #ffffff;
            font-family: 'Arial', sans-serif;
        }

        table {
            margin-top: 40px;
            width: 100%;
        }

        table>thead>tr>td {
            padding: 10px;
            font-size: 12px;
        }

        table>tbody>tr>td {
            padding: 10px;
            border-bottom: 1px solid #CCCCCC;
            font-size: 12px;
        }
        .btn-info.wap {
            background-color: #f7a30f;
            border-color: #f7a30f;
        }

        .box_resultado_posto{
            border: 1px solid #0000002b;
            margin-top: 20px;
            padding-bottom: 35px;
        }

        .span-info{
            line-height: 25px;
        }

        .info-head{
            font-family: 'Poppins', sans-serif;
            font-size: 1.1em;
            font-weight: 300;
            line-height: 1.7em;
            margin-bottom: 20px;
            text-align: justify;
            text-align:center;

        }

        .info-status{
            text-align: left;
            font-size: 15px;
            max-width: 600px;
            text-align: justify;
        }

        .info-posto{
            border-bottom: 1px solid #CCCCCC; 
            padding-bottom: 20px;
            font-size: 12px;
            font-family: Arial;
        }

        #sidebar {
            min-width: 250px;
            min-height: 430px;
            transition: all 0.3s;
            border: 1px solid #0000001f;
        }

        #sidebar.active {
            margin-left: -250px;
        }

        .form-group{
            padding: 20px 20px 0 20px;
        }

        #sidebar .sidebar-header {
            padding: 20px;
            background: #6d7fcc;
        }

        #sidebar ul p {
            color: #fff;
            padding: 10px;
        }

        #sidebar ul li a {
            padding: 10px;
            font-size: 1.1em;
            display: block;
        }
        #sidebar ul li a:hover {
            color: #7386D5;
            background: #fff;
        }

        #sidebar ul li.active > a, a[aria-expanded="true"] {
            color: #fff;
            background: #6d7fcc;
        }

        #btn_acao{
            background-color: #222;
            margin-left: 20px;
            color: #fff;
            margin-top: 20px;
        }

        ul ul a {
            font-size: 0.9em !important;
            padding-left: 30px !important;
            background: #6d7fcc;
        }

         @media (max-width: 768px) {
            #box_sidebar {
                width: 40% !important;
            }
            #box_mapa {
                width: 60% !important;
            }

             #box_resultado_posto{
                width: 40% !important;
                max-height: unset !important;
            }

            .text-right > button{
                font-size: 12px !important;
                margin-bottom:20px !important;
            }

            .text-right{
                text-align: unset !important;
            }

            .status-os{
                margin-top: 100px !important;
            }
        }

         @media (max-width: 600px) {
            #box_sidebar {
                width: 100% !important;
            }
            #box_mapa {
                width: 100% !important;
            }

            #box_resultado_posto{
                width: 100% !important;
            }

            .text-right > button{
                font-size: 10px !important;
                margin-bottom:20px !important;
            }

            .text-right{
                text-align: unset !important;
            }

            .status-os{
                margin-top: 100px !important;
            }

        }


    </style>


    <script type="text/javascript">
        function busca_cep(cep, method, callback) {

            var img = $("<img />", {
                src: "../../imagens/loading_img.gif",
                css: {
                    width: "30px",
                    height: "30px"
                }
            });
            if (typeof method == "undefined" || method.length == 0) {
                method = "webservice";
                $.ajaxSetup({
                    timeout: 10000
                });
            } else {
                $.ajaxSetup({
                    timeout: 10000
                });
            }

            $.ajax({
                async: true,
                url: "../ajax_cep.php",
                type: "GET",
                data: {
                    cep: cep,
                    method: method
                },
                beforeSend: function() {
                    $("#btn_acao").prop("disabled", "disabled");
                    $("#btn_acao").html("Buscando...");
                    $("#estado").prop('readyonly', true);
                    $("#cidade").prop('readyonly', true);
                },
                success: function(data) {

                    $("#estado").prop('readyonly', false);
                    $("#cidade").prop('readyonly', false);

                    if(data == 'CEP NAO ENCONTRADO'){
                        alert("O cep informado n„o foi encontrado, por favor informe outro CEP e tente novamente.");
                        $("#cep").val('');
                        $("#estado").val('');
                        $("#cidade").val('');
                        $("#cep").focus();
                    }

                    else if(data == 'CEP INV√ÅLIDO'){
                        alert("O cep informado È inv·lido, por favor informe outro CEP e tente novamente.");
                        $("#cep").val('');
                        $("#estado").val('');
                        $("#cidade").val('');
                        $("#cep").focus();
                    }

                    else if(data){

                        results = data.split(";");

                        if (results[0] == "ok") {
                            $("#end_cliente").val(results[1] + ":" + results[2] + ":" + results[3] + ":" + results[4]);
                        } 

                        $.ajaxSetup({
                            timeout: 0
                        });
                      
                        callback(data);

                    }else{
                        alert("Nenhuma serviÁo autorizado encontrado para a localizaÁ„o informada.");
                    }

                    $("#btn_acao").prop("disabled", "");
                    $("#btn_acao").html("Buscar");
                   
                },
                error: function(xhr, status, error) {

                    alert("Nenhuma serviÁo autorizado encontrado para a localizaÁ„o informada.");
                    $("#btn_acao").html("Buscar");
                    busca_cep(cep, "database", callback);
                }
            });
        }



        var showRecaptcha = function() {
            grecaptcha.render('reCaptcha', {
                'sitekey': '6LckVVIUAAAAAEQpRdiIbRSbs_ePTTrQY0L4959J'
            });
        };

        /* INICIO - MAPBOX */
        var MapaTele, Router, Geocoder, Markers;

        function addMap(data, extraPoint) {
            var locations = $.parseJSON(data);

            if (typeof MapaTelecontrol !== 'object') {
                MapaTele.load();
            }

            var personIcon = L.icon({
                iconUrl: '<?= $iconPerson ?>',
                shadowUrl: 'img/person-shadow.png',
                iconSize: [25, 55], // size of the icon
                shadowSize: [70, 20], // size of the shadow
                iconAnchor: [30, 32], // point of the icon which will correspond to marker's location
                shadowAnchor: [22, 0], // the same for the shadow
                popupAnchor: [-20, -30]
            });

            var pointIcon = L.icon({
                iconUrl: '<?= $iconCar ?>',
                shadowUrl: 'img/point-shadow.png',
                iconSize: [40, 55], // size of the icon
                shadowSize: [60, 22], // size of the shadow
                iconAnchor: [30, 32], // point of the icon which will correspond to marker's location
                shadowAnchor: [10, 0], // the same for the shadow
                popupAnchor: [-3, -76]
            });

            Markers.remove();
            Markers.clear();

            if(extraPoint){
                Markers.add(extraPoint[0], extraPoint[1], 'yellow_wap', "Consumidor", "", {
                    icon: personIcon
                });
            }
    
            $.each(locations, function(key, value) {
                var lat = value.latitude;
                var lng = value.longitude;

                if (lat == null || lng == null) {
                    return true;
                }

                Markers.add(lat, lng, "yellow_wap", value.nome_fantasia, "", {
                    icon: pointIcon
                });

            });
            Markers.render();
            Markers.focus();
        }

        function localizarMap(lat, lng) {
            MapaTele.setView(lat, lng, 15);
            scrollPostMessage();
        }

        function setZoomAllMarkers() {
            Markers.focus();
        }
        /* FIM - MAPBOX */

        var scroll = 760;
        var scroll_xs = 750;

        function scrollPostMessage(scroll_p) {
            if (scroll_p != 0) {
                scroll_xs = 0;
                scroll = 0;
            }
            if ($("div.scroll-xs").is(":visible")) {
                $(window).scrollTop(scroll_xs);
            } else {
                $(window).scrollTop(scroll);
            }

            window.parent.postMessage("scroll", "*");
        }

        /* Google Maps */
        function initialize_antigo(markers) {
            var width = parseInt($("#map_canvas").width() / 2);
            var height = parseInt($("#map_canvas").height() / 2);

            var url = "https://maps.googleapis.com/maps/api/staticmap?scale=2&size=" + width + "x" + height + "&maptype=roadmap&" + markers.join('&') + "&key=AIzaSyC5AsH3NU-IwraXqLAa1qbXxyjklSVP-cQ";

            $("#map_canvas").html("<img src='" + url + "' style='width: 100%; height: 100%;' />");
        }

        function addMap_antigo(data) {
            var locations = $.parseJSON(data);
            var markers = [];

            $.each(locations, function(key, value) {
                var lat = value.latitude;
                var lng = value.longitude;

                if (lat == null || lng == null) {
                    return true;
                }

                markers.push("markers=color:red%7C" + lat + "," + lng);
            });

            initialize(markers);
        }

        function localizarMap_antigo(lat, lng) {

            scrollPostMessage();

            var width = parseInt($("#map_canvas").width() / 2);
            var height = parseInt($("#map_canvas").height() / 2);

            var url = "https://maps.googleapis.com/maps/api/staticmap?center=" + lat + "," + lng + "&zoom=15&scale=2&size=" + width + "x" + height + "&maptype=roadmap&markers=color:red%7C" + lat + "," + lng + "&key=AIzaSyC5AsH3NU-IwraXqLAa1qbXxyjklSVP-cQ";
            $("#map_canvas").html("<img src='" + url + "' style='width: 100%; height: 100%;' />");

        }

        function setZoomAllMarkers_antigo() {
            scrollPostMessage();

            var markers = [];

            $("div.row-posto").each(function() {
                var lat = $(this).data("lat");
                var lng = $(this).data("lng");

                if (lat == null || lng == null) {
                    return true;
                }

                markers.push("markers=color:red%7C" + lat + "," + lng);
            });

            initialize(markers);
        }
        /* Fim - Google Maps */


        function carregaMapa() {
            var fabrica = <?= $cod_fabrica; ?>;
            $.ajax({
                url: window.location.pathname,
                data: {
                    ajax: "sim",
                    todospostos: "sim",
                    fabrica: fabrica
                },
                method: "POST"
            }).done(function(data) {
                info = data.split("*");
                var dados = info[1];

                $('#box_mapa').show();
                addMap(dados);
            });
        }

        $('document').ready(function() {
            // $("#cep").blur(function() {
            //     busca_cep($(this).val(),"");
            // });
            if (typeof MapaTelecontrol !== 'object') {
                MapaTele = new MapaTelecontrol("map_canvas");
                Router = new Router(MapaTele);
                Geocoder = new Geocoder();
                Markers = new Markers(MapaTele);

                MapaTele.load();
                
                $('.leaflet-control-zoom-in')[0].click();
            }
           
            $("#cep").mask("99.999-999");

            $("#cep").on("keyup", function(){
                if($(this).val().length == 10){
                    $(this).blur();
                }
            });
            $("#cpf_cnpj").focus(function() {
                $(this).unmask();
                $(this).mask("99999999999999");
            });
            $("#cpf_cnpj").blur(function() {
                var el = $(this);
                el.unmask();
                if (el.val().length > 11) {
                    el.mask("99.999.999/9999-99");
                }


                if (el.val().length <= 11) {
                    el.mask("999.999.999-99");
                }
            });

            $('#linha').blur(function() {
                var id = "linha";
                var linha = $("#linha option:selected").text();
                var iframe_linha = $("#iframe_linha").val();

                if (linha != iframe_linha) {
                    $("div.trigger").removeClass("open");
                    $("ul.options").removeClass("open");
                    $("#iframe_linha").val(linha);
                }
            });

            $("#linha").on('change', function() {
                $('#cep').removeAttr('disabled');
                $('#estado').removeAttr('disabled');
                $("#cep").val("");
                $("#estado").val("");
                $("#cidade").val("");
                $("#cidade").attr("disabled", true);
                $("#estado").trigger('change');
            });

           $("#cep").blur(function() {

                if($(this).val() != ""){

                    busca_cep($(this).val(), "", function(data){
 
                        if(data){

                            var result = data.split(";");

                            if (result[0] == "ok") {

                                $("#estado").val(results[4]);
                                $("#estado").prop('disabled', true);
                                $("#cidade").prop('disabled', true);
                           
                                $("#estado").data("cidade", results[3]);
                                $("#estado").trigger('change');
                            }

                        }

                        
                    });
                }else{
                    $("#estado").data('cidade', '');
                    $("#estado").prop('disabled', false);
                    $("#cidade").prop('disabled', false);
                    $("#estado").val("");
                    $("#cidade").val("");
                }
            });

            $("#pesquisar_novamente").click(function(){
                location.reload();
            });

            /* Busca Postos Autorizados */

            $("#page-posto-right").click(function() {

                page = parseInt($("#pag-posto").val());
                page = page + 1;

                var len = parseInt($("#lista_posto").find(".row-posto").length);
                if (len == null || len == undefined || len == 0) {
                    len = 0;
                }

                if (page >= len / 2) {
                    return;
                }
                $("#lista_posto").find(".row-posto").hide();
                pageSlice = page * 2;


                totalPagina = len / 2;
                if (totalPagina == 1.5) {
                    //Para retirar possÌveis decimais
                    totalPagina = 2;
                }
                $("#pag-posto-label").html("P·gina " + (page + 1) + " de " + totalPagina);


                $("#lista_posto").find(".row-posto").slice(pageSlice, pageSlice + 2).fadeIn(500);
                $("#pag-posto").val(page);
            });
            $("#page-posto-left").click(function() {


                page = parseInt($("#pag-posto").val());
                page = page - 1;
                if (page < 0) {
                    // page  = 0
                    return;
                }
                $("#lista_posto").find(".row-posto").hide();
                pageSlice = page * 2;


                var len = parseInt($("#lista_posto").find(".row-posto").length);
                if (len == null || len == undefined || len == 0) {
                    len = 0;
                }

                totalPagina = len / 2;
                console.log(totalPagina);
                if (totalPagina == 1.5) {
                    //Para retirar possÌveis decimais
                    totalPagina = 2;
                }
                $("#pag-posto-label").html("P·gina " + (page + 1) + " de " + (totalPagina));

                $("#lista_posto").find(".row-posto").slice(pageSlice, pageSlice + 2).fadeIn(500);
                $("#pag-posto").val(page);
            });

            $('#btn_acao').click(function() {

                if ($('#linha').val() == "") {
                    $('#linha-group').addClass('danger');
                    $("#msgErro").text('Selecione um produto!').show();
                    return;
                } else {
                    closeMessageError();
                }

               if ($('#estado').val() == "") {
                    $('#cep-group').addClass('danger');
                    $("#msgErro").text('Preencha o estado que deseja realizar a busca!').show();
                    return;
                } else {
                    closeMessageError();
                }

                $("#btn_acao").html("Buscando..");
               // busca_cep($("#cep").val(), "", function(response) {
                    $('#box_mapa').hide();
                    $('#box_resultado_posto').hide();
                    $('#lista_posto').html("");


                    var linha = $('#linha').val();
                    var cep = $('#cep').val();
                    var estado = $("#estado").val();
                    var cidade = $("#cidade").val();
                    var end_cliente = $('#end_cliente').val();
                    var fabrica = <?= $cod_fabrica; ?>;

                    $.ajax({
                        url: window.location.pathname,
                        type: "POST",
                        dataType: "JSON",
                        async: false,
                        data: {
                            linha: linha,
                            cep: cep,
                            estado : estado,
                            cidade : cidade,
                            end_cliente: end_cliente,
                            fabrica: fabrica,
                            token: '<?= $token ?>'
                        },
                        beforeSend: function() {
                            loading("show");
                            $("#msgErro").text('').hide();
                        },
                        complete: function(data) {
                            loading("hide");

                            data = data.responseText;
      
                            if (data != "Nenhuma AssistÍncia TÈcnica Autorizada localizada para este CEP") {
                                info = data.split("*");
                                var dados = info[1];
                                var cliente = info[2];

                                if (cliente != "") {
                                    cliente = JSON.parse(cliente);

                                    if(cliente.consumidor != null){
                                        cliente.consumidor = cliente.consumidor.split("@");
                                    }
                                    
                                }

                                if (dados.length > 0) {
                                    $('#box_mapa').show();
                                    $('#box_resultado_posto').show();
                                    addMap(dados, cliente.consumidor);

                                    if (JSON.parse(dados).length < 2)
                                        $("#show_all").hide();
                                    else
                                        $("#show_all").show();
                                }


                                a = info[0];
                                $('#lista_posto').html(info[0]);
                                
                                $("#lista_posto").find(".row-posto").slice(0, 2).fadeIn(500);
                                $("#pag-posto").val(0);

                                var len = parseInt($("#lista_posto").find(".row-posto").length);
                                if (len == null || len == undefined || len == 0) {
                                    len = 0;
                                }

                                totalPagina = len / 2;
                                if (totalPagina == 1.5) {
                                    //Para evitar decimais
                                    totalPagina = 2;
                                }
                                $("#pag-posto-label").html("P·gina 1 de " + (totalPagina));

                                $("#box_sidebar").hide();
                                $("#box_mapa").css("width", "50%");
                                $("#box_resultado_posto").css("width", "50%");
                                $("#pesquisar_novamente").show();

                            } else {
                                $("#msgErro").text("Nenhuma AssistÍncia TÈcnica Autorizada localizada para este CEP").show();
                            }
                        }
                    });

              //  });


                window.parent.postMessage($(document).height() + 100, "*");
                scrollPostMessage();
            });


            function pegaIp() {
                var ip = '';
                $.ajax({
                    url: "../institucional/pega_ip.php",
                    async: false,
                    dataType: "json",
                    success: function(data) {
                        ip = data.ip;
                    }
                });
                return ip;
            }

            var showOs = function(ret) {
                var qtde = ret.length;
                var msg_situacao = "";

                $.each(ret, function(key, value) {

                    if (typeof value != 'object' || typeof value.entity == 'undefined') {
                        return;
                    }

                    var descricao;
                    var marca = value.entity.marca;
                    var fone = value.entity.contato_fone_comercial;
                    fone = fone.replace(/^([0-9][0-9])-/g, "(\$1) ");
                    descricao = 'SituaÁ„o';
                    var situacao = value.entity.status_checkpoint;
                    switch (situacao) {
                        case "4":
                        case "9":
                            msg_situacao = "SEU APARELHO EST¡ PRONTO PARA RETIRADA.";
                            break;
                        case "2":
                        case "8":
                        case "14":
                            msg_situacao = "O REPARO DO SEU APARELHO EST¡ EM ANDAMENTO.";
                            break;
                        case "3":
                        case "30":
                            msg_situacao = "O REPARO DO SEU APARELHO EST¡ EM ANDAMENTO. ENTRE EM CONTATO COM O POSTO AUTORIZADO PARA SABER A DATA PARA RETIRADA.";
                            break;
                        case "0":
                        case "1":
                            msg_situacao = "O REPARO DO SEU APARELHO EST¡ EM ANDAMENTO. QUALQUER D⁄VIDA ENTRE EM CONTATO CONOSCO.";
                            break;

                    }

                    var resultado = "<ul class='list-group' style='margin-bottom: 0px;'>" +
                        "<li class='list-group-item'><b>" + descricao + ":</b> " + msg_situacao +
                        "</li>" +
                        "<li class='list-group-item panel-heading' style='background-color: #428bca; border-color: #428bca'>" +
                        "<h3 style='margin-top:0;margin-bottom:0;font-size:16px;color:inherit'><b>Ordem de serviÁo: " + value.sua_os + "</b>" +
                        "</h3>" +
                        "</li>" +
                        "<li class='list-group-item' > " + ((value.entity.consumidor_revenda == "R") ? "<b>Revenda</b>" : "<b>Consumidor</b>") + ": " + ((value.entity.consumidor_revenda == "R") ? value.entity.revenda_nome : value.entity.consumidor_nome) +
                        "</li>" +
                        "<li class='list-group-item' ><b>Produto:</b> " + value.entity.descricao_produto +
                        "</li>" +

                        "<li class='list-group-item' style='background: #e2e2e2;'><b>InformaÁıes da AssistÍncia TÈcnica Autorizada</b></li>" +
                        "<li class='list-group-item'><b>Nome:</b> " + value.entity.posto_autorizado +
                        "</li>" +
                        "<li class='list-group-item'><b>EndereÁo:</b>: " + value.entity.endereco + " " + value.entity.numero + " - " + value.entity.cidade + "</li>" +
                        "<li class='list-group-item'><b>Telefone:</b> " + fone +
                        "</li>" +
                        "</ul>" +
                        "<br>";

                    $("#res_os").html("");
                    $("#res_os").html(resultado);
                    $("#res_os").show();
                    //scrollPostMessage();
                });
            };

            $('#btn_os').on('click', function() {
                $('#btn_os').text('Aguarde...');
                $('#btn_os').prop('disabled', true);
                var msgErro = [];
                var data = {};
                var inputOS = $('#os');
                var inputCpfCnpj = $('#cpf_cnpj');
                var ip = pegaIp();
                data.userIpAddress = ip;
                data.os = inputOS.val();
                data.cpf_cnpj = inputCpfCnpj.val();
                data.recaptcha_response_field = grecaptcha.getResponse();
                data.token_fabrica = "2ade4b7e60491f28e76f7f0f6c5aa47a";

                if (data.os.length == 0) {
                    msgErro.push("Informe o n˙mero da ordem de serviÁo");
                }

                if (data.recaptcha_response_field.length == 0) {
                    msgErro.push("Preencha o ReCaptcha");
                }

                if (data.cpf_cnpj.length == 0) {
                    msgErro.push("Informe o n˙mero do CPF/CNPJ");
                }

                if (data.cpf_cnpj.length > 0 &&
                    !data.cpf_cnpj.match(/^[0-9]{3}\.[0-9]{3}\.[0-9]{3}-[0-9]{2}$/) &&
                    !data.cpf_cnpj.match(/^[0-9]{2}\.[0-9]{3}\.[0-9]{3}\/[0-9]{4}-[0-9]{2}$/)) {
                    msgErro.push('CPF/CNPJ Inv·lido');
                }
                if (msgErro.length > 0) {
                    $("#msgErroOs").html(msgErro.join("<br />")).show();
                    $('#btn_os').text('Consultar');
                    $('#btn_os').prop('disabled', false);
                } else {
                    data.cpf_cnpj = data.cpf_cnpj.replace(/[./-]+/gi, '');
                    var urlSuffix = '';
                    for (var index in data) {
                        var value = data[index];
                        if (value == undefined || value.length == 0)
                            continue;
                        var value = data[index].replace(" ", "");
                        urlSuffix += index + '/' + value + '/';
                    }

                    var apiLink = 'https://api2.telecontrol.com.br/institucional/statusos/';
                    var url = apiLink + urlSuffix;

                    $("#msgErroOs").html("").hide();
                    $("#result").hide();
                    $.ajax({
                        url: '../institucional/crossDomainProxy.php',
                        data: {
                            'apiLink': url
                        },
                        method: 'POST',
                        success: function(data) {
                            console.log(data);
                            if (data.exception) {
                                $("#msgErroOs").text(data.message).show();
                            } else {
                                showOs(data);
                            }
                        },
                        error: function(data) {
                            console.log(data);
                            data = JSON.parse(data.responseText);
                            if (data.message.match("caracteres da imagem")) {
                                alert(data.message);
                            } else {
                                $("#msgErroOs").text(data.message).show();
                            }
                        },
                        complete: function(data) {
                            console.log(data);
                            $('#btn_os').text('Consultar');
                            $('#btn_os').prop('disabled', false);
                            grecaptcha.reset();
                        }
                    });
                }
            });

            $('#estado').on('change', function() {
        
                var callback = $(this).data("callback");
                var callbackParam = $(this).data("callback-param");
                $('#cidade').find("option").remove();
                $("#cidade").val("");

                var uf  = $('#estado').val();
                var linha = $('#linha').val();

                if(uf != ""){
                    $('#cidade').removeAttr('disabled');
                }

                if (linha != "" && uf != "") {

                    var fabrica = <?=$cod_fabrica;?>;

                    $.ajax({
                        url:      window.location.pathname,
                        type:     'POST',
                        dataType: "JSON",
                        data:      {
                            uf:      uf,
                            linha:   linha,
                            fabrica: fabrica,
                            token:   '<?=$token?>'
                        },
                        beforeSend : function(){
                            $("#btn_acao").prop("disabled", "disabled");
                            $("#btn_acao").html("Buscando...");
                            $("#estado").prop('readyonly', true);
                            $("#cidade").prop('readyonly', true);
                        },
                        complete: function(data) {

                            $("#estado").prop('readyonly', false);
                            $("#cidade").prop('readyonly', false);

                            data = data.responseText;

                            if(data == 'POSTO NAO ENCONTRADO'){
                                alert("Nenhum posto encontrado para o estado informado.");

                                $("#cep").val('');
                                $("#estado").val('')
                                $("#estado").focus();
                            }else{

                                $('#cidade').append(data);

                                var cidade = $("#estado").data('cidade');
      
                                if(cidade != '' && cidade != undefined){
                                    $("#cidade").val(cidade);
                                    $("#cidade").trigger('change');
                                    $("#cidade").prop("disabled", true);
                                   $("#estado").data('cidade', '');
                                }
                            }

                            $("#btn_acao").prop("disabled", "");
                            $("#btn_acao").html("Buscar");
                   
                        }
                    });
                }
            });
        });

        /* Loading Imagem */
        function loading(e) {
            if (e == "show") {
                $('#loading').html('<img src="../imagens/loading.gif" />');
            } else {
                $('#loading').html('');
            }
        }

        function messageError() {
            $('.alert').show();
        }

        function closeMessageError(e) {
            $('#' + e + '-group').removeClass('danger');
            $('.alert').hide();
        }

        window.onmessage = function(event) {
            event.source.postMessage($(document).height() + 100, event.origin);
        };
    </script>
</head>

<body>
    <div class="container-fluid" style="margin-top: 20px;">
        <div class='row'>
            <div class='col-xs-12 col-sm-12'>
                <p class="info-head">Escolha a categoria do produto e digite seu CEP para encontrar o ServiÁo Autorizado WAP mais prÛximo de vocÍ.</p>
            </div>
        </div>
    </div>
    <br />
    <input type="hidden" id="iframe_linha" value="">
    <input type="hidden" id="iframe_estado" value="">
    <input type="hidden" id="iframe_cidade" value="">

    <div class="container-fluid">
        <div class="alert alert-danger" id='msgErro' role="alert" style="display: none;">
            <strong>Preencha os campos obrigatÛrios</strong>
        </div>
    </div>

    <!-- Corpo -->
    <div class="container-fluid">
        <div class='row'>
            <div class="col-xs-3 col-md-4 col-sm-4" id="box_sidebar">
                <nav id="sidebar">
                    <ul class="list-unstyled components">
                        <div class="col-xs-12 col-sm-12" style="padding-top: 10px;padding-bottom: 10px;  margin-bottom: 5px;">
                            <span class="obrigatorio" style="font-size: 10px">* Campos obrigatÛrios</span>
                        </div>
                        <li>
                            <form>
                                <div class="form-group" id="linha-grupo">   
                                    <label class="control-label" for="linha">Produto</label>
                                    <div class="asterisco" style="margin-bottom: 0;margin-top: 6px;">*</div>
                                    <select class="form-control" name="linha" id="linha" autofocus required style="padding: 5px;">
                                        <option value=""></option>
                                        <?php
                                        $sql = "SELECT DISTINCT
                                            tbl_linha.descricao_site,
                                            tbl_linha.nome,
                                            tbl_linha.linha
                                        FROM tbl_linha
                                        WHERE tbl_linha.fabrica = $cod_fabrica
                                        AND tbl_linha.ativo IS TRUE
                                        AND UPPER(fn_retira_especiais(tbl_linha.nome)) NOT IN ('FILTROS DE AGUA', 'ROBO LIMPA VIDROS')
                                        ORDER BY tbl_linha.nome";
                                        $res = pg_query($con, $sql);
                                        $rows = pg_fetch_all($res);
                                        $descricao_site  = array();
                                        $arry_ids  = array();

                                        foreach ($rows as $key => $ln) {
                                            $descricao_site[$ln["linha"]] = $ln["nome"];
                                        }
                                        foreach ($rows as $key => $ln) {
                                            if (in_array($ln["nome"], $descricao_site)) {
                                                $arry_ids[$ln["nome"]][$ln["linha"]] = $ln["nome"];
                                            }
                                        }
                                        foreach ($arry_ids as $descricao => $linhas) {
                                            $xlinhas = implode(",", array_keys($linhas));
                                            echo "<option value='{$xlinhas}'>" . $descricao . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <!--<div class="form-group">
                                    <label for="tipo_busca">Buscar por:</label>
                                    <select class="form-control" id="tipo_busca">
                                        <option selected>CEP</option>
                                        <option>Estado</option>
                                        <option>Cidade</option>
                                    </select>
                                </div>-->
                                <div class="form-group" id="cep-group">
                                    <label class="control-label" for="cep">CEP</label>
                                    <div class="controls controls-row">
                                        <input class="form-control" type="text" name="cep" id="cep" disabled style="padding: 5px;">
                                        <input id="end_cliente" name="end_cliente" type="hidden" value="" />
                                    </div>
                                </div>
                                <div class="form-group" id="estado-group">
                                    <label class="control-label" for="linha">Estado</label>
                                    <div class="asterisco">*</div>
                                    <select class="form-control" name="estado" disabled id="estado" class="col-md-11">
                                        <option value=""></option>
                                        <option value='AC'>Acre</option>
                                        <option value='AL'>Alagoas</option>
                                        <option value='AM'>Amazonas</option>
                                        <option value='AP'>Amap·</option>
                                        <option value='BA'>Bahia</option>
                                        <option value='CE'>Cear·</option>
                                        <option value='DF'>Distrito Federal</option>
                                        <option value='ES'>EspÌrito Santo</option>
                                        <option value='GO'>Goi·s</option>
                                        <option value='MA'>Maranh„o</option>
                                        <option value='MG'>Minas Gerais</option>
                                        <option value='MS'>Mato Grosso do Sul</option>
                                        <option value='MT'>Mato Grosso</option>
                                        <option value='PA'>Par·</option>
                                        <option value='PB'>ParaÌba</option>
                                        <option value='PE'>Pernambuco</option>
                                        <option value='PI'>PiauÌ</option>
                                        <option value='PR'>Paran·</option>
                                        <option value='RJ'>Rio de Janeiro</option>
                                        <option value='RN'>Rio Grande do Norte</option>
                                        <option value='RO'>RondÙnia</option>
                                        <option value='RR'>Roraima</option>
                                        <option value='RS'>Rio Grande do Sul</option>
                                        <option value='SC'>Santa Catarina</option>
                                        <option value='SE'>Sergipe</option>
                                        <option value='SP'>S„o Paulo</option>
                                        <option value='TO'>Tocantins</option>
                                    </select>
                                </div>
                                <div class="form-group" id="cidade-group">
                                    <label class="control-label" for="cidade">Cidade</label>
                                        <select class="form-control" name="cidade" id="cidade" class="col-md-11" disabled style="width:200px;">
                                            <option value=""></option>
                                        </select>
                                </div>
                                <div class="btn-wrapper" style="margin-bottom: 15px; margin-top: 18px;">
                                    <button class="btn btn-default" id="btn_acao" type="button" style="background-color: #333;color: #fff;border-color: #000;">Buscar</button> &nbsp; <span id="loading"></span>
                                </div>
                            </form>
                        </li>
                    </ul>
                </nav>
            </div>
            <div id="box_mapa" class="col-xs-5 col-md-8 col-sm-8" style=" text-align: center;">
                <div id="map_canvas" style="height: 430px; border: 1px solid #CCCCCC;"></div>
                <div class="text-right">
                    <br />
                     <button type="button" style="display: none;" id="pesquisar_novamente" class="btn btn-default"><i class="glyphicon glyphicon-search"></i> Nova Pesquisa</button>
                    <button type="button" style="display: none;" id="show_all" class="btn btn-default" onclick="setZoomAllMarkers()"><i class="glyphicon glyphicon-map-marker"></i> Mostrar todos os Postos</button>
                </div>
            </div>
            <div id="box_resultado_posto" class="col-xs-5 col-md-4 col-sm-4" style="display: none; border: 1px solid #0000002b; padding-bottom: 25px;max-height: 430px;min-height: 430px;">
                <div class="col-xs-12 col-sm-12" id="lista_posto" style="padding-bottom: 30px;"></div>
                <div class="col-xs-12 col-sm-12 text-center" id="paginacao">
                    <button class="btn btn-info wap" type="button" id="page-posto-left" style="float: left"><i class="glyphicon glyphicon-arrow-left"></i></button>
                    <span id="pag-posto-label"></span>
                    <button class="btn btn-info wap" type="button" id="page-posto-right" style="float: right"><i class="glyphicon glyphicon-arrow-right"></i></button>
                </div>
                <input type="hidden" name="pag-posto" id='pag-posto'>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12 text-left status-os" style="margin-top:40px; border-top: 1px solid #00000024;margin-left:20px">
                <hr>
                <label class="control-label" style="font-size: 20px;">Acompanhe o status da sua ordem de serviÁo.</label>
                <p class="info-status">Preencha os campos abaixo e confira o status do seu atendimento. Este serviÁo est· disponÌvel apenas para clientes que j· abriram uma ordem de serviÁo em nossa rede de ServiÁo Autorizado WAP.</p>
            </div>
            <div class="col-xs-12 col-sm-12">
                <div class="alert alert-danger" id='msgErroOs' role="alert" style="display: none;">
                    <strong>Preencha os campos obrigatÛrios</strong>
                </div>
            </div>
            <div class='col-xs-12 col-sm-6'>
                <div class="well" style="height: 383px;margin-top: 30px;">
                    <div class="row">
                        <div class="col-sm-6">
                            <label for="os">N. da Ordem de ServiÁo</label>
                            <div class="asterisco" style="margin-bottom: 0;margin-top: 6px;">*</div><input type="text" id="os" name="os" />
                        </div>
                        <div class="col-sm-6">
                            <label for="cpf_cnpj">CPF / CNPJ</label>
                           <div class="asterisco" style="margin-bottom: 0;margin-top: 6px;">*</div><input type="text" name="cpf_cnpj" id="cpf_cnpj" />
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-8">
                            <div id="reCaptcha"></div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-1">
                            <label>&nbsp;&nbsp;&nbsp;&nbsp;</label>
                            <button class="btn btn-default" id='btn_os' data-loading-text="Consultando...">Consultar</button>
                        </div>
                    </div>
                </div>
            </div>
            <div id="res_os" class="col-sm-6">

            </div>
        </div>
    </div>
    <div style="clear: both;"></div>
    <div id="box_mapa" class="col-xs-12 col-sm-10 col-sm-offset-1  col-md-8 col-md-offset-2 col-lg-8 col-lg-offset-2" style="display: none;text-align: center;z-index: 1;">
        <div id="map_canvas" style="height: 410px; margin-top: 50px; border: 1px solid #CCCCCC;"></div>
        <div class="text-right">
            <br />
            <button type="button" id="show_all" class="btn btn-default" onclick="setZoomAllMarkers()"><i class="glyphicon glyphicon-map-marker"></i> Mostrar todos os Postos</button>
        </div>
    </div>
    <div style="clear: both;"></div>
    <!-- <div class="col-xs-12 col-sm-12" id="lista_posto" style="padding-bottom: 100px;"></div> -->
    <div class="scroll-xs visible-xs-block"></div>
</body>

</html>
