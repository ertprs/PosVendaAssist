<?php
require_once('../../admin/dbconfig.php');
require_once('../../admin/includes/dbconnect-inc.php');
///require_once('../../admin/funcoes.php');
use Posvenda\TcMaps;
$oTcMaps = new TcMaps(19, $con);
$markers = "null";

if ($_GET["ajax_rota"]) {

    $origem  = str_replace('"', "", $_GET["origem"]);
    $destino = $_GET["destino"];    
    $resposta = $oTcMaps->route($origem,$destino);

    echo json_encode($resposta);
    exit;
}

if ($_GET["ajax_geo_consumidor"]) {

    $endereco  = $_GET["endereco"];
    $numero  = $_GET["numero"];
    $bairro  = $_GET["bairro"];
    $cidade  = $_GET["cidade"];
    $estado = $_GET["estado"];    
    $resposta = getLatLonConsumidor($endereco, $bairro, $cidade, $estado, $pais = "BR",null,$numero);
    echo json_encode($resposta);
    exit;
}


$iconCar = 'img/pin-lor.png';
$iconPerson = 'img/person-car.png';
if($_GET['sp'] == 1){
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
            WHERE  tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
                AND tbl_posto_fabrica.posto <> 6359
                AND tbl_posto_fabrica.divulgar_consumidor IS TRUE
            ORDER BY tbl_posto.cidade;";

    $res = pg_query($con, $sql);
    if (pg_num_rows($res) > 0) {
        $data = pg_fetch_all($res);
        exit("*".json_encode($data));
    }
}
$token        = trim($_GET['tk']);
$token_post   = $_POST['token'];
$cod_fabrica  = $_GET['cf'];
$fabrica  = base64_decode(trim($cod_fabrica));

$nome_fabrica = $_GET['nf'];
$nome_fabrica = base64_decode(trim($nome_fabrica));

if (!empty($_POST['fabrica'])) {
    $sql = "SELECT nome FROM tbl_fabrica WHERE fabrica = ". $_POST['fabrica'];
    $res = pg_query($con,$sql);

    if (pg_num_rows($res) > 0) {
        $fabrica = $_POST['fabrica'];
        $nome_fabrica = pg_fetch_result($res,0,0);
    }
}

$token_comp = base64_encode(trim("telecontrolNetworking".$nome_fabrica."assistenciaTecnica".$fabrica));
if (!empty($token_post)) $token = $token_post;
if ($token != $token_comp) {
    exit;
}

function maskCep($cep) {
    $num_cep = preg_replace('/\D/', '', $cep);
    return (strlen($cep == 8)) ? preg_replace('/(\d\d)(\d{3})(\d{3})/', '$1.$2-$3', $num_cep) : $cep;
}

function maskFone($telefone) {
    if (!strstr($telefone, "(")) {
        $telefone = str_replace("-", '', $telefone);
        $inicio   = substr($telefone, 0, 2);
        $meio     = substr($telefone, 2, 4);
        $fim      = substr($telefone, 6, strlen($telefone));
        $telefone = "(".$inicio.") ".$meio."-".$fim;
    }

    return $telefone;
}

function retira_acentos($texto) {
    $array1 = array( '·', '‡', '‚', '„', '‰', 'È', 'Ë', 'Í', 'Î', 'Ì', 'Ï', 'Ó', 'Ô', 'Û', 'Ú', 'Ù', 'ı', 'ˆ', '˙', '˘', '˚', '¸', 'Á'
    , '¡', '¿', '¬', '√', 'ƒ', '…', '»', ' ', 'À', 'Õ', 'Ã', 'Œ', 'œ', '”', '“', '‘', '’', '÷', '⁄', 'Ÿ', '€', '‹', '«' );
    $array2 = array( 'a', 'a', 'a', 'a', 'a', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'c'
    , 'A', 'A', 'A', 'A', 'A', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'C' );
    return str_replace( $array1, $array2, $texto);
}
function formatCEP($cepString){
    $cepString = str_replace("-", "", $cepString);
    $cepString = str_replace(".", "", $cepString);
    $cepString = str_replace(",", "", $cepString);
    $antes = substr($cepString, 0, 5);
    $depois = substr($cepString, 5);
    $cepString = $antes."-".$depois;
    return $cepString;
}
function getLatLonConsumidor($logradouro = null, $bairro = null, $cidade, $estado, $pais = "BR", $cep, $numero = null){
    global $con, $fabrica, $oTcMaps;
    try{
        $retorno = $oTcMaps->geocode($logradouro, $numero, $bairro, $cidade, $estado, $pais, $cep);
        return $retorno['latitude']."@".$retorno['longitude'];
    }catch(Exception $e){
        return false;
    }
}
/* Busca os Postos Autorizados */
if (isset($_POST['linha']) && isset($_POST['cep'])) {

    $linha       = $_POST['linha'];
    $cep         = $_POST['cep'];
    $fabrica     = $_POST['fabrica'];
    $endCliente  = $_POST['end_cliente'];

    $cond_uf     = "";
    $cond_cidade = "";
    $order_by    = "tbl_posto_fabrica.contato_cidade,tbl_posto_fabrica.nome_fantasia";

    if (!empty($cep)) {

        $local = (strlen($cep) > 0) ? formatCEP($cep) : "";

        list ($endereco, $bairro, $cidade, $estado) = explode(":", $endCliente);
        $latLonConsumidor = getLatLonConsumidor($endereco, $bairro, $cidade, $estado,'BR',$cep);
        $parte = explode('@', $latLonConsumidor);
        $from_lat = substr(trim($parte[0]), 0, 7);
        $from_lon = substr(trim($parte[1]), 0, 7);

        $coluna_distancia = ", (111.045 * DEGREES(ACOS(COS(RADIANS({$from_lat})) * COS(RADIANS(tbl_posto_fabrica.latitude)) * COS(RADIANS(tbl_posto_fabrica.longitude) - RADIANS({$from_lon})) + SIN(RADIANS({$from_lat})) * SIN(RADIANS(tbl_posto_fabrica.latitude))))) AS distancia";
        $cond_distancia = "AND (111.045 * DEGREES(ACOS(COS(RADIANS({$from_lat})) * COS(RADIANS(tbl_posto_fabrica.latitude)) * COS(RADIANS(tbl_posto_fabrica.longitude) - RADIANS({$from_lon})) + SIN(RADIANS({$from_lat})) * SIN(RADIANS(tbl_posto_fabrica.latitude))))) < 100";
        $order_by = "distancia ASC";

    } else {
        echo "Nenhuma AssistÍncia TÈcnica Autorizada localizada para este CEP";
        exit;
    }

    $sql =" SELECT
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
                    tbl_posto_fabrica.contato_estado AS uf ,
                    tbl_posto_fabrica.contato_bairro AS bairro,
                    tbl_posto_fabrica.contato_complemento AS complemento
                    {$coluna_distancia}
              FROM tbl_posto
              JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = {$fabrica}
              JOIN tbl_posto_linha   ON tbl_posto.posto = tbl_posto_linha.posto AND tbl_posto_linha.linha  = {$linha}
             WHERE tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
                   {$cond_distancia}
               AND tbl_posto_fabrica.posto <> 6359
               AND tbl_posto_fabrica.divulgar_consumidor IS TRUE
               AND tbl_posto_fabrica.senha <> '*'
          ORDER BY  {$order_by}
          LIMIT 25";
    $res = pg_query($con,$sql);
    if (pg_num_rows($res) > 0) {

        $cor = "";
        $i = 0;

        while ($data = pg_fetch_object($res)) {
            /* Mascara CEP */
            $cep = maskCep($data->cep);

            /* Mascara Telefone */
            //$telefone = maskFone($data->telefone);
            $telefone = $data->telefone;

            if (strlen(trim($data->nome_fantasia)) > 0 && $data->nome_fantasia != "null") {
                $nome_fantasia = strtoupper(retira_acentos($data->nome_fantasia));
                $nome = $data->nome."<br />";
            }else{
                $nome_fantasia = strtoupper(retira_acentos($data->nome));
                $nome = "";
            }

            $nome = preg_replace('/(\d{11})/', '', $nome);

            $cor = ($i%2 == 0) ? "#EEF" : "#FFF";
            $fone =  "";
            if (!empty(trim($telefone))) {
                $fone = "<i class='glyphicon glyphicon-earphone' style='color:#D90000; font-size:11px;'></i> $telefone &nbsp; / &nbsp; ";
            }

            echo "
                <div class='row row-posto' data-lat='{$data->lat}' data-lng='{$data->lng}' style='display: none;'>
                    <div class='col-md-12'>
                        <div style='border-bottom: 1px solid #CCCCCC; padding-bottom: 20px;'>
                            <strong><img width='10' style='float: left;margin-top: 3px;margin-right: 10px;' src='img/pin-lor.png' /> $nome_fantasia</strong> <br />
                            $data->endereco, $data->numero  $data->complemento&nbsp; / &nbsp; CEP: $cep <br />
                            BAIRRO: $data->bairro &nbsp; / &nbsp; $data->cidade - $data->uf<br />
                            $fone ".strtolower($data->email)." <br />
                            <div  class='label-totais-km totais-km-".$i."'></div>
                            <button type='button' class='btn btn-default' onclick=\"localizarMap('".$data->lat."', '".$data->lng."')\" style='margin-top: 10px;'><i class='glyphicon glyphicon-search'></i> Localizar</button>
                            <button type='button' style='margin-top:9px' class='btn btn-default rota' data-lat='".$data->lat."' data-lng='".$data->lng."' data-toggle='modal' data-target='#modal-rota' data-posicao='".$i."' data-consumidor='".$latLonConsumidor."' data-posto='".$nome_fantasia."'><i class='glyphicon glyphicon-map-marker'></i> Realizar Rota</button>
                        </div>
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

        echo "*".$lat_lng."*".json_encode(["consumidor"=>$latLonConsumidor]);
    } else {
        echo 'Nenhuma AssistÍncia TÈcnica Autorizada localizada para este CEP';
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
        <title><?=$titulo_mapa_rede?></title>
        <link href="../bootstrap3/css/bootstrap.min.css" type="text/css" rel="stylesheet" media="screen" />
        <link href="css/default.css?v=<?php echo date('YmdHis');?>" type="text/css" rel="stylesheet" media="screen" />
        <link href="css/fonts.css" type="text/css" rel="stylesheet" media="screen" />
        <!--[if lt IE 10]>
        <link rel="stylesheet" type="text/css" media="screen" href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-ie.css" />
        <link rel='stylesheet' type='text/css' href="bootstrap/css/ajuste_ie.css">
        <![endif]-->

        <script type="text/javascript" src="../../admin/plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
        <script src="../institucional/lib/mask/mask.min.js" ></script>
        <!-- MAPBOX -->
       <link href="../../plugins/leaflet/leaflet.css" rel="stylesheet" type="text/css" />
       <script src="../../plugins/leaflet/leaflet.js" ></script>
       <script src="../../plugins/leaflet/map.js?v=<?php echo date('YmdHis');?>" ></script>
       <script>
        var MapaTelecontrol = Map;
       </script>
       <script src="../../plugins/mapbox/geocoder.js"></script>
       <script src="../../plugins/mapbox/polyline.js"></script>
       <!--  <script src="https://www.google.com/recaptcha/api.js?hl=pt-BR&onload=showRecaptcha&render=explicit" async defer></script> -->
        <script src="../bootstrap3/js/bootstrap.min.js" type="text/javascript"></script>

        <style type="text/css">
            body{
                font-size: 15px !important;
                font-family: 'arial' !important;
            }
           

            table {
                margin-top: 40px;
                width: 100%;
            }

            table > thead > tr > td {
                padding: 10px;
                font-size: 12px;
            }

            table > tbody > tr > td {
                padding: 10px;
                border-bottom: 1px solid #CCCCCC;
                font-size: 12px;
            }
            .asterisco {
                margin-left: -22px !important;
                margin-top: 4px !important;
            }
                        
            .modal-header{
                background: #C43438;
                color: #fff;
                font-weight: bold;
                padding: 10px;
                width: 100%;
                margin:0px;
            }
            .well_tc{
                background: #eee;
                padding: 10px;
                margin:0px;
            }
            .modal-footer{
                background: #eee;
            }
            
        </style>

        <script type="text/javascript">

            function busca_cep(cep,method,callback){
                var img = $("<img />", { src: "../../imagens/loading_img.gif", css: { width: "30px", height: "30px" } });
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
                        $("#btn_acao").prop("disabled","disabled");
                        $("#btn_acao").html("Buscando...");
                    },
                    success: function(data) {
                        results = data.split(";");

                        if (results[0] != "ok") {
                            alert(results[0]);
                        } else {
                            var ender = results[1].split(",");

                            $("#end_cliente").val(ender[0]+":"+results[2]+":"+results[3]+":"+results[4]);
                        }

                        $.ajaxSetup({
                            timeout: 0
                        });
                        $("#btn_acao").prop("disabled","");
                        $("#btn_acao").html("Buscar");
                        callback(data);
                    },
                    error: function(xhr, status, error) {
                        busca_cep(cep, "database",callback);
                    }
                });
            }

            var showRecaptcha= function() {
                grecaptcha.render('reCaptcha', {
                  'sitekey' : '6LckVVIUAAAAAEQpRdiIbRSbs_ePTTrQY0L4959J'
                });
            };

            /* INICIO - MAPBOX */
            var MapaTele, Router, Geocoder, Markers;
            var rotas      = [];

            function addMap(data,extraPoint) {                
                var locations = $.parseJSON(data);

                if (typeof MapaTelecontrol !== 'object') {
                    MapaTele.load();
                }


                var personIcon = L.icon({
                    iconUrl: '<?=$iconPerson?>',
                    shadowUrl: 'img/person-shadow.png',
                    iconSize:     [25, 55], // size of the icon
                    shadowSize:   [70, 20], // size of the shadow
                    iconAnchor:   [30, 32], // point of the icon which will correspond to marker's location
                    shadowAnchor: [22, 0],  // the same for the shadow
                    popupAnchor:  [-20, -30]
                });

                var pointIcon = L.icon({
                    iconUrl: '<?=$iconCar?>',
                    shadowUrl: 'img/point-shadow.png',
                    iconSize:     [40, 55], // size of the icon
                    shadowSize:   [60, 22], // size of the shadow
                    iconAnchor:   [30, 32], // point of the icon which will correspond to marker's location
                    shadowAnchor: [10, 0],  // the same for the shadow
                    popupAnchor:  [-3, -76]
                });

                Markers.remove();
                Markers.clear();


                Markers.add(extraPoint[0],extraPoint[1],'blue_midea',"Consumidor","",{
                    icon: personIcon
                });

                $.each(locations, function(key, value) {
                    var lat = value.latitude;
                    var lng = value.longitude;

                    if (lat == null || lng == null) {
                        return true;
                    }                

                    Markers.add(lat, lng, "blue_midea", value.nome_fantasia,"",{
                        icon: pointIcon
                    });
                
                });
                Markers.render();
                Markers.focus();
            }

            function localizarMap(lat, lng) {
                Router.clear();

                MapaTele.setView(lat, lng, 15);
                scrollPostMessage();
            }

            function setZoomAllMarkers(){
                $('#btn_acao').click();
            }
            /* FIM - MAPBOX */
          
            var scroll = 760;
            var scroll_xs = 750;

            function scrollPostMessage(scroll_p) {
                if (scroll_p != 0) {
                    scroll_xs = 0;
                    scroll    = 0;
                }
                if ($("div.scroll-xs").is(":visible")) {
                    $(window).scrollTop(scroll_xs);
                } else {
                    $(window).scrollTop(scroll);
                }

                window.parent.postMessage("scroll", "*");
            }

                      
            function carregaMapa(){
                var fabrica = <?=$fabrica;?>;
                $.ajax({
                    url: window.location.pathname,
                    data: {ajax: "sim", todospostos: "sim", fabrica: fabrica},
                    method: "POST"
                }).done(function(data){
                    info = data.split("*");
                    var dados = info[1];

                    $('#box_mapa').show();
                    addMap(dados);
                });
            }

            function zoomAll(){
                Markers.focus();    
            }

            function load_mapbox(){
                
                Map      = new Map("Maps");
                Router   = new Router(Map);
                Geocoder = new Geocoder();
                Markers = new Markers(Map);
                
                Map.load();
            }

            var map = null;

            function initialize() {

                let markers = <?=$markers?>;

                if (markers !== undefined && markers !== null) {
                    markers.forEach(function(l, i) {
                        [lat, lng] = l;                 
                        Markers.add(lat, lng, "red", "Posto");
                        
                        pLatLng = lat+","+lng;
                    });
                    Markers.render();
                    Markers.focus();    
                }
            }

            function zoomMap(lat, lng){
                var target_offset = $("#parametros").offset();
                var target_top = target_offset.top;
                $('html, body').animate({ scrollTop: target_top }, 100);

                Map.setView(lat, lng,15);           
            }

            function retiraAcentos(palavra){
                if (!palavra) {
                    return "";
                }

                var com_acento = '·‡„‚‰ÈËÍÎÌÏÓÔÛÚıÙˆ˙˘˚¸Á¡¿√¬ƒ…» ÀÕÃŒœ”“’÷‘⁄Ÿ€‹«';
                var sem_acento = 'aaaaaeeeeiiiiooooouuuucAAAAAEEEEIIIIOOOOOUUUUC';
                var newPalavra = "";

                for(i = 0; i < palavra.length; i++) {
                    if (com_acento.search(palavra.substr(i, 1)) >= 0) {
                        newPalavra += sem_acento.substr(com_acento.search(palavra.substr(i, 1)), 1);
                    } else {
                        newPalavra += palavra.substr(i, 1);
                    }
                }

                return newPalavra.toUpperCase();
            }


            $('document').ready(function() {

                if (typeof MapaTelecontrol !== 'object') {
                    MapaTele = new MapaTelecontrol("map_canvas");
                    Router   = new Router(MapaTele);
                    Geocoder = new Geocoder();
                    Markers = new Markers(MapaTele);

                    MapaTele.load();
                }

                $("#cpf_cnpj").focus(function(){
                    $(this).unmask();
                    $(this).mask("99999999999999");
                });
                $("#cpf_cnpj").blur(function(){
                    var el = $(this);
                    el.unmask();
                    if(el.val().length > 11){
                        el.mask("99.999.999/9999-99");
                    }


                    if(el.val().length <= 11){
                        el.mask("999.999.999-99");
                    }
                });

                $('#linha').blur(function() {
                    var id = "linha";
                    var linha = $("#linha option:selected").text();
                    var iframe_linha = $("#iframe_linha").val();

                    if(linha != iframe_linha){
                        $("div.trigger").removeClass("open");
                        $("ul.options").removeClass("open");
                        $("#iframe_linha").val(linha);
                    }
                });

                /* Busca Postos Autorizados */

                $("#page-posto-right").click(function(){
                   
                    page = parseInt($("#pag-posto").val());
                    page = page + 1;

                    var len = parseInt($("#lista_posto").find(".row-posto").length);
                    if(len == null || len == undefined || len == 0){
                        len = 0;
                    }
                    
                    if(page >= len/5){
                        return;
                    }
                    $("#lista_posto").find(".row-posto").fadeOut(200);
                    pageSlice = page * 5;


                    totalPagina = len/5;
                    if(totalPagina == 1.5){
                        //Para retirar possÌveis decimais
                        totalPagina = 5;
                    }
                     if(totalPagina <= 1){
                        //Para retirar possÌveis decimais
                        totalPagina = 1;
                    }
                    $("#pag-posto-label").html("P·gina "+(page+1)+" de "+Math.round(totalPagina));

                    
                    $("#lista_posto").find(".row-posto").slice(pageSlice,pageSlice+5).fadeIn(500);
                    $("#pag-posto").val(page);                        
                });
                $("#page-posto-left").click(function(){

                    
                    page = parseInt($("#pag-posto").val());
                    page = page - 1;
                    if(page < 0){
                        // page  = 0
                        return;
                    }
                    $("#lista_posto").find(".row-posto").fadeOut(200);
                    pageSlice = page * 5;


                    var len = parseInt($("#lista_posto").find(".row-posto").length);
                    if(len == null || len == undefined || len == 0){
                        len = 0;
                    }

                    totalPagina = len/5;

                    if(totalPagina == 1.5){
                        //Para retirar possÌveis decimais
                        totalPagina = 5;
                    }
                    if(totalPagina <= 1){
                        //Para retirar possÌveis decimais
                        totalPagina = 1;
                    }

                    $("#pag-posto-label").html("P·gina "+(page+1)+" de "+(Math.round(totalPagina)));

                    $("#lista_posto").find(".row-posto").slice(pageSlice,pageSlice+5).fadeIn(500);
                    $("#pag-posto").val(page);    
                });

                $('#btn_acao').click(function() {

                    Markers.clear();
                    Router.remove();
                    Router.clear();

                    if ($('#linha').val() == "") {
                            $('#linha-group').addClass('danger');
                            $("#msgErro").text('Selecione uma Produto!').show();
                            return;
                    } else {
                        closeMessageError();
                    }

                    if ($('#cep').val() == "") {
                        $('#cep-group').addClass('danger');
                        $("#msgErro").text('Preencha o CEP que deseja realizar a busca!').show();
                        return;
                    } else {
                        closeMessageError();
                    }

                    $("#btn_acao").html("Buscando..");
                    busca_cep($("#cep").val(),"",function(response){
                        $('#box_mapa').hide();
                        $('#box_resultado_posto').hide();
                        $('#lista_posto').html("");

                        
                        var linha   = $('#linha').val();
                        var cep  = $('#cep').val();
                        var end_cliente  = $('#end_cliente').val();
                        var fabrica = <?=$fabrica;?>;
                        
                        $.ajax({
                            url: window.location.pathname,
                            type: "POST",
                            dataType: "JSON",
                            async: false,
                            data:
                            {
                                linha   : linha,
                                cep  : cep,
                                end_cliente  : end_cliente,
                                fabrica : fabrica,
                                token   : '<?=$token?>'
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
                                    var cliente  = info[2];

                                    if(cliente != ""){
                                        cliente = JSON.parse(cliente);
                                        cliente.consumidor = cliente.consumidor.split("@");                                        
                                    }

                                    

                                    if (dados.length > 0) {
                                        $('#box_mapa').show();
                                        $('#box_resultado_posto').show();
                                        addMap(dados,cliente.consumidor);

                                        if (JSON.parse(dados).length < 2)
                                            $("#show_all").hide();
                                        else
                                            $("#show_all").show();
                                    }

                                    a = info[0];

                                    $('#lista_posto').html(info[0]);

                                    $("#lista_posto").find(".row-posto").slice(0,5).fadeIn(500);
                                    $("#pag-posto").val(0);

                                    var len = parseInt($("#lista_posto").find(".row-posto").length);
                                    if(len == null || len == undefined || len == 0){
                                        len = 0;
                                    }

                                    totalPagina = len/5;
                                    if(totalPagina == 1.5){
                                        //Para evitar decimais
                                        totalPagina = 5;
                                    }
                                     if(totalPagina <= 1){
                                        //Para retirar possÌveis decimais
                                        totalPagina = 1;
                                    }
                                    $("#pag-posto-label").html("P·gina 1 de "+(Math.round(totalPagina)));

                                } else{
                                    $("#msgErro").text("Nenhuma AssistÍncia TÈcnica Autorizada localizada para este CEP").show();
                                    $('#box_mapa').show();
                                }
                            }
                        });

                    });

                    window.parent.postMessage($(document).height()+100, "*");
                    scrollPostMessage();
                });
                
                $(".btn-realiza-rota").click(function() {
                    if ($("input[name=endereco]").val() == '') {
                        $("input[name=endereco]").focus();
                        return false;
                    } else if ($("input[name=numero]").val() == '') {
                        $("input[name=numero]").focus();
                        return false;
                    } else if ($("input[name=bairro]").val() == '') {
                        $("input[name=bairro]").focus();
                        return false;
                    } else if ($("input[name=cidade]").val() == '') {
                        $("input[name=cidade]").focus();
                        return false;
                    } else if ($("input[name=estado]").val() == '') {
                        $("input[name=estado]").focus();
                        return false;
                    } else {

                        Markers.clear();
                        Router.remove();
                        Router.clear();

                        var geocoder, latlon;

                        var lat2  = $("input[name=latitude]").val();
                        var lng2  = $("input[name=longitude]").val();

                        var pLatLng = lat2+','+lng2;

                        var posicao = $("input[name=posicao]").val();
                        var enderecoConsumidor = $("input[name=endereco]").val();
                        var numeroConsumidor   = $("input[name=numero]").val();
                        var bairroConsumidor   = $("input[name=bairro]").val();
                        var cidadeConsumidor   = $("input[name=cidade]").val();
                        var estadoConsumidor   = $("input[name=estado]").val();
                        var paisConsumidor     = "Brasil";

                        if (enderecoConsumidor == "") {
                            alert('Digite o endereÁo para pesquisa!');
                            $('#end_cliente_rota').focus();
                            return
                        }
                        if (numeroConsumidor == "") {
                            alert('Digite o n˙mero do endereÁo informado!');
                            $('#numero_cliente_rota').focus();
                            return
                        }
                        if (cidadeConsumidor == "") {
                            alert('Digite a cidade para pesquisa!');
                            $('#cidade_cliente_rota').focus();
                            return
                        }
                        if (estadoConsumidor == "") {
                            alert('Digite o estado do endereÁo informado!');
                            $('#uf_cliente_rota').focus();
                            return
                        }

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

                                    var latlgn = [];

                                    cLatLng = resposta.latlon;

                                    if (cLatLng == pLatLng) {
                                        alert('EndereÁo informado È o mesmo do posto!');
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

                                            var totaiskm = "<p><b>Dist‚ncia:</b> "+parseFloat(data.km_ida).toFixed(2)+"km </p>";
                                            $('#lista_posto').each(function(index, el) {
                                                $(el).find(".label-totais-km").html('');
                                            });

                                            $('.totais-km-'+posicao).html(totaiskm);


                                            geometry = data.rota.routes[0].geometry;
                                            var kmtotal = parseFloat(data.total_km).toFixed(2);

                                            /* Marcar pontos no mapa */
                                            cLatLngA = cLatLng.split(",");
                                            pLatLngA = pLatLng.split(",");

                                            var personIcon = L.icon({
                                                iconUrl: '<?=$iconPerson?>',
                                                shadowUrl: 'img/person-shadow.png',
                                                iconSize:     [25, 55], // size of the icon
                                                shadowSize:   [70, 20], // size of the shadow
                                                iconAnchor:   [30, 32], // point of the icon which will correspond to marker's location
                                                shadowAnchor: [22, 0],  // the same for the shadow
                                                popupAnchor:  [-20, -30]
                                            });

                                            var pointIcon = L.icon({
                                                iconUrl: '<?=$iconCar?>',
                                                shadowUrl: 'img/point-shadow.png',
                                                iconSize:     [40, 55], // size of the icon
                                                shadowSize:   [60, 22], // size of the shadow
                                                iconAnchor:   [30, 32], // point of the icon which will correspond to marker's location
                                                shadowAnchor: [10, 0],  // the same for the shadow
                                                popupAnchor:  [-3, -76]
                                            });

					                       var nome_fantasia = $("input[name=nome_fantasia]").val();

                                            //Markers.remove();
                                            //Markers.clear();
                                            Markers.add(cLatLngA[0], cLatLngA[1], "blue", "Consumidor","",{
                                                icon: personIcon
                                            });
                                            Markers.add(pLatLngA[0], pLatLngA[1], "red",nome_fantasia,"",{
                                                icon: pointIcon});
                                            Markers.render();
                                            Markers.focus();

                                            Router.remove();
                                            Router.clear();
                                            Router.add(Polyline.decode(geometry));
                                            Router.render();

                                            $('#qtde_km').val(kmtotal);
                                            $('#loading').hide();

                                            $("input[name=latitude]").val('');
                                            $("input[name=longitude]").val('');
                                            $("input[name=endereco]").val('');
                                            $("input[name=numero]").val('');
                                            $("input[name=bairro]").val('');
                                            $("input[name=cidade]").val('');
                                            $("input[name=estado]").val('');
                                            $("#modal-rota").modal("hide");

                                        }).fail(function(){
                                            $('#loading-map').hide();
                                            alert('Erro ao tentar calcular a rota!');
                                        });
                                    }
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
                    }
                });

                $("input[name=xcep]").change(function(){

                    var cep = $(this).val();
                    var method;

                    if (cep.length > 0) {
                        var img = $("<img />", { src: "../../imagens/loading_img.gif", css: { width: "30px", height: "30px" } });

                        if (typeof method == "undefined" || method.length == 0) {
                            method = "webservice";

                            $.ajaxSetup({
                                timeout: 3000
                            });
                        } else {
                            $.ajaxSetup({
                                timeout: 5000
                            });
                        }

                        $.ajax({
                            async: true,
                            url: "../../ajax_cep.php",
                            type: "GET",
                            data: { ajax: true, cep: cep, method: method },
                            beforeSend: function() {
                                $("input[name=endereco]").next("img").remove();
                                $("input[name=bairro]").next("img").remove();
                                $("input[name=cidade]").next("img").remove();
                                $("input[name=estado]").next("img").remove();

                                $("input[name=endereco]").hide().after(img.clone());
                                $("input[name=bairro]").hide().after(img.clone());
                                $("input[name=cidade]").hide().after(img.clone());
                                $("input[name=estado]").hide().after(img.clone());
                            },
                            error: function(xhr, status, error) {
                                busca_cep(cep, consumidor_revenda, "database");
                            },
                            success: function(data) {
                                results = data.split(";");

                                if (results[0] != "ok") {
                                    alert(results[0]);
                                    $("input[name=cidade]").show().next().remove();
                                } else {
                                    $("input[name=estado]").val(results[4]);

                                    results[3] = results[3].replace(/[()]/g, '');

                                    $("input[name=cidade]").val(retiraAcentos(results[3]).toUpperCase());

                                    if (results[2].length > 0) {
                                        $("input[name=bairro]").val(results[2]);
                                    }

                                    if (results[1].length > 0) {
                                        $("input[name=endereco]").val(results[1]);
                                    }
                                }

                                $("input[name=cidade]").show().next().remove();
                                $("input[name=estado]").show().next().remove();
                                $("input[name=bairro]").show().next().remove();
                                $("input[name=endereco]").show().next().remove();

                                if ($("input[name=bairro]").val().length == 0) {
                                    $("input[name=bairro]").focus();
                                } else if ($("input[name=endereco]").val().length == 0) {
                                    $("input[name=endereco]").focus();
                                } else if ($("input[name=numero]").val().length == 0) {
                                    $("input[name=numero]").focus();
                                }

                                $.ajaxSetup({
                                    timeout: 0
                                });
                            }
                        });
                    }
                });

                $(document).on("click", "button.rota", function() {
                    //$("#modal-rota").modal("show");
                    var enderecos = $("#end_cliente").val().split(':');
                    $("input[name=endereco]").val(enderecos[0]);
                    $("#numero").focus();
                    $("input[name=bairro]").val(enderecos[1]);
                    $("input[name=cidade]").val(enderecos[2]);
                    $("input[name=estado]").val(enderecos[3]);
                    $("input[name=posicao]").val('');
                    $("input[name=posicao]").val($(this).data("posicao"));
                    $("input[name=xcep]").val($("#cep").val());
                        var lat  = $(this).data("lat");
                        var lng  = $(this).data("lng");
                        $("input[name=latitude]").val("");
                        $("input[name=longitude]").val("");
                        setTimeout(function(){
                            $("input[name=latitude]").val(lat);
                            $("input[name=longitude]").val(lng);
                        }, 1000);
			
			        $("input[name=nome_fantasia]").val($(this).data("posto"));
                    
                });
                $('#modal-rota').on('shown.bs.modal', function() {
                  $('#numero').focus();
                })
                function pegaIp(){
                    var ip = '';
                    $.ajax({
                        url : "../institucional/pega_ip.php",
                        async:false,
                        dataType : "json",
                        success : function(data){
                            ip = data.ip;
                       }
                    });
                    return ip;
                }

                /* Busca Produtos */
                $("#linha-group").on('change.fs', function() {
                    $("#estado").trigger('change.fs');
                });
            });
    
            /* Loading Imagem */
            function loading(e) {
                if (e == "show") {
                    $('#loading').html('<img src="../imagens/loading.gif" />');
                }else{
                    $('#loading').html('');
                }
            }

            function messageError() {
                $('.alert').show();
            }

            function closeMessageError(e) {
                $('#'+e+'-group').removeClass('danger');
                $('.alert').hide();
            }

            function rota (lat, lng, c) {
                Router.remove();
                Router.clear();

                    var p = lat+","+lng;
                    c = c.replace("@", ",");

                    if (typeof rotas[p] == "undefined") {
                        $.ajax({
                            async: true,
                            timeout: 60000,
                            url: window.location,
                            type: "get",
                            data: {
                                ajax_rota: true,
                                origem: c,
                                destino: p
                            },
                            beforeSend: function() {
                                $("div.calculo-rota").show();
                            }
                        }).fail(function(r) {
                            alert("Erro ao gerar Rota");
                            $("div.calculo-rota").hide();
                        }).done(function(r) {
                            r = JSON.parse(r);

                            $("div.calculo-rota").hide();

                            if (r.exception) {
                                alert("Erro ao gerar Rota");
                            
                            } else {
                                //console.log(r["routes"][0]["geometry"]);

                                rotas[p] = Polyline.decode(r.rota.routes[0].geometry);
                                c = c.split(',');
                                Router.add(c[0],c[1],'blue','Cliente');
                                geraMapaRota(rotas[p]);
                            }
                        });
                    } else {
                        geraMapaRota(rotas[p]);
                    }
            }

            function geraMapaRota(rota) {
                //console.log(rota);
                Markers.clear();
                Markers.render();

                Router.remove();
                Router.clear();
                Router.add(rota);
                Router.render();

                MapaTele.scrollToMap();

            }
            window.onmessage = function(event) {
                event.source.postMessage($(document).height()+100, event.origin);
            };

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
    </head>

    <body>
        <div class="modal fade" id="modal-rota" tabindex="-1" role="dialog">
          <div class="modal-dialog" role="document">
            <div class="modal-content">
              <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Digite seu endereÁo completo para realizar a Rota</h4>
              </div>
              <div class="modal-body well_tc">
                    <div class="row" style="margin-left:0px;margin-right:0px;">
                        <div class="col-sm-3">
                            <label for="">CEP</label>
                            <input type="text" name="xcep" class="form-control input-sm">
                        </div>
                        <div class="col-sm-6">
                            <label for="">EndereÁo</label>
                            <input type="text" name="endereco" class="form-control input-sm">
			                 <input type="hidden" name="nome_fantasia">
                            <input type="hidden" name="latitude" class="">
                            <input type="hidden" name="longitude" class="">
                            <input type="hidden" name="posicao" class="">
                        </div>
                        <div class="col-sm-3">
                            <label for="">N˙mero</label>
                            <input type="text" id="numero" name="numero" class="form-control input-sm">
                        </div>
                    </div><br/>
                    <div class="row" style="margin-left:0px;margin-right:0px;">
                        <div class="col-sm-4">
                            <label for="">Bairro</label>
                            <input type="text" name="bairro" class="form-control input-sm">
                        </div>
                        <div class="col-sm-5">
                            <label for="">Cidade</label>
                            <input type="text" name="cidade" class="form-control input-sm">
                        </div>
                        <div class="col-sm-3">
                            <label for="">Estado</label>
                            <input type="text" name="estado" maxlength="2" class="form-control input-sm">
                        </div>
                    </div><br/>
                </div>

              <div class="modal-footer">
                <button type="button" data-lat2="" data-lng2="" class="btn btn-primary btn-realiza-rota">Realizar Rota</button>
              </div>
            </div>
          </div>
        </div>
        <div class="container-fluid">
            <div class='row'>
                <div class='col-xs-12 col-sm-12'>
                    <h2 style="text-align: left;font-size: 28px !important;font-weight: bold;">Mapa de autorizadas <button title="Fechar Janela" type="button" style="float: right;margin-top:10px;" class='btn btn-danger btn-sm' onclick="window.close();"><i class="glyphicon glyphicon-remove-sign"></i></button></h2>
                    <p style="text-align: left;font-size: 20px;">Encontre aqui a assistÍncia tÈcnica mais prÛxima de vocÍ.</p>
                </div>
            </div>
        </div>

      
        <br />
        <input type="hidden" id="iframe_linha" value="">
        <input type="hidden" id="iframe_estado" value="">
        <input type="hidden" id="iframe_cidade" value="">

        <div class="container-fluid">
            <div class="alert alert-danger" id='msgErro' role="alert" style="display: none;" >
                <strong>Preencha os campos obrigatÛrios</strong>
            </div>
        </div>

        <!-- Corpo -->
        <div class="container-fluid">
            
            <div class='row'>                
                <div id="box_mapa" class="col-xs-12 col-sm-8" style=" text-align: center;" >

                    <div style="background: #C43438;padding: 10px 20px 0px 20px;    border-radius: 18px;">
                        <div class="row">
                            <div class="col-sm-12 col-md-3"><img src="img/topo.png" width="100%" alt=""></div>
                            <div class="col-sm-12 col-md-4" style="text-align: left">
                                <span class="obrigatorio" style="font-size: 11px;color:#f6f6f6;">* Campos obrigatÛrios</span>
                                <div class="form-group" id="linha-group">
                                    <div class="controls controls-row">
                                        <label class="control-label" style="color:#fff;font-weight: bold;" for="linha">Linha de Produtos</label>
                                        <div class="asterisco" style="color: #fff;font-size: 28px;">*</div>
                                        <select name="linha" id="linha" autofocus required>
                                            <option value=""></option>
                                            <?php
                                                $array_linhas = [
                                                    [
                                                        "linha" => 265,
                                                        "nome" => "AQUECEDORES A G¡S",
                                                    ],
                                                    [
                                                        "linha" => 260,
                                                        "nome" => "AQUECEDORES EL…TRICOS",
                                                    ],
                                                    [
                                                        "linha" => 260,
                                                        "nome" => "DUCHAS E CHUVEIROS",
                                                    ],
                                                    [
                                                        "linha" => 928,
                                                        "nome" => "LOU«AS SANIT¡RIAS",
                                                    ],
                                                    [
                                                        "linha" => 261,
                                                        "nome" => "METAIS SANIT¡RIOS",
                                                    ],
                                                    [
                                                        "linha" => 603,
                                                        "nome" => "PL¡STICOS",
                                                    ],
                                                    [
                                                        "linha" => 265,
                                                        "nome" => "PRESSURIZADORES",
                                                    ],
                                                    [
                                                        "linha" => 263,
                                                        "nome" => "PURIFICADORES E FILTROS",
                                                    ],
                                                    [
                                                        "linha" => 263,
                                                        "nome" => "TORNEIRAS EL…TRICAS",
                                                    ],
                                                    [
                                                        "linha" => 327,
                                                        "nome" => "VALVULAS",
                                                    ],
                                                ];

                                                foreach ($array_linhas as $k => $linhas) {
                                                    echo "<option value='".$linhas['linha']."'>".$linhas['nome']."</option>";
                                                }
                                                
                                            ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-8 col-md-3" style="text-align: left">
                                <span class="obrigatorio" style="font-size: 11px;color:#f6f6f6;">&nbsp;</span>
                                <div class="form-group" id="cep-group">
                                    <label class="control-label"  style="color:#fff;font-weight: bold;" for="cep">CEP</label>
                                    <div class="asterisco" style="color: #fff;font-size: 28px;">*</div>
                                    <div class="controls controls-row">
                                        <input type="text" name="cep" id="cep" >
                                        <input id="end_cliente" name="end_cliente" type="hidden" value="" />
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-4 col-md-1" style="text-align: right;">

                                <label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label>
                                <button style="margin-top: 17px;" class="btn btn-default" id="btn_acao" type="button">Buscar</button> &nbsp; <span id="loading"></span>
                            </div>
                        </div>
                    </div>
                    <div class="alert calculo-rota" style="display: none;">Calculando rota aguarde...</div>
                    <div id="map_canvas" style="height: 630px; margin-top: 20px; border: 1px solid #CCCCCC;"></div>
                    <div class="text-right">
                        <br />
                        <button type="button" style="display: none;" id="show_all" class="btn btn-default" onclick="setZoomAllMarkers()"><i class="glyphicon glyphicon-map-marker"></i> Mostrar todos os Postos</button>
                    </div>
                </div>
                <div id="box_resultado_posto" class="col-sm-4" style="display: none">
                    <div class="col-xs-12 col-sm-12" id="lista_posto" style="padding-bottom: 59px;"></div>
                    <div class="col-xs-12 col-sm-12 text-center" id="paginacao" style="padding-bottom: 100px;">
                        <button class="btn btn-danger" type="button" id="page-posto-left" style="float: left"><i class="glyphicon glyphicon-arrow-left"></i></button>
                        <span id="pag-posto-label"></span>
                        <button class="btn btn-danger" type="button" id="page-posto-right" style="float: right"><i class="glyphicon glyphicon-arrow-right"></i></button>
                    </div>
                    <input type="hidden" name="pag-posto" id='pag-posto' >
                </div>
                
            </div>
            
        </div>
        <div style="clear: both;"></div>
        <div id="box_mapa" class="col-xs-12 col-sm-10 col-sm-offset-1  col-md-8 col-md-offset-2 col-lg-8 col-lg-offset-2" style="display: none;text-align: center;z-index: 1;" >
            <div id="map_canvas" style="height: 410px; margin-top: 50px; border: 1px solid #CCCCCC;"></div>
            <div class="text-right">
                <br />
                <button type="button" id="show_all" class="btn btn-default" onclick="setZoomAllMarkers()"><i class="glyphicon glyphicon-map-marker"></i> Mostrar todos os Postos</button>
            </div>
        </div>
        <div style="clear: both;"></div>
        <!-- <div class="col-xs-12 col-sm-12" id="lista_posto" style="padding-bottom: 100px;"></div> -->
        <div class="scroll-xs visible-xs-block" ></div>
    </body>
</html>
