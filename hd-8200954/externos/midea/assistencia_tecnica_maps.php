<?php
require_once('../../admin/dbconfig.php');
require_once('../../admin/includes/dbconnect-inc.php');
require_once('../../admin/funcoes.php');
use Posvenda\TcMaps;

$iconCar = 'img/pin-midea.png';
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
$cod_fabrica  = base64_decode(trim($cod_fabrica));

$nome_fabrica = $_GET['nf'];
$nome_fabrica = base64_decode(trim($nome_fabrica));

if (!empty($_POST['fabrica'])) {
    $sql = "SELECT nome FROM tbl_fabrica WHERE fabrica = ". $_POST['fabrica'];
    $res = pg_query($con,$sql);

    if (pg_num_rows($res) > 0) {
        $cod_fabrica = $_POST['fabrica'];
        $nome_fabrica = pg_fetch_result($res,0,0);
    }
}

$token_comp = base64_encode(trim("telecontrolNetworking".$nome_fabrica."assistenciaTecnica".$cod_fabrica));
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
    $array1 = array( 'á', 'à', 'â', 'ã', 'ä', 'é', 'è', 'ê', 'ë', 'í', 'ì', 'î', 'ï', 'ó', 'ò', 'ô', 'õ', 'ö', 'ú', 'ù', 'û', 'ü', 'ç'
    , 'Á', 'À', 'Â', 'Ã', 'Ä', 'É', 'È', 'Ê', 'Ë', 'Í', 'Ì', 'Î', 'Ï', 'Ó', 'Ò', 'Ô', 'Õ', 'Ö', 'Ú', 'Ù', 'Û', 'Ü', 'Ç' );
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
function getLatLonConsumidor($logradouro = null, $bairro = null, $cidade, $estado, $pais = "BR"){
    global $con, $fabrica;
    $oTcMaps = new TcMaps($fabrica, $con);

    try{
        $retorno = $oTcMaps->geocode($logradouro, null, $bairro, $cidade, $estado, $pais);
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
        $latLonConsumidor = getLatLonConsumidor($endereco, $bairro, $cidade, $estado);
        $parte = explode('@', $latLonConsumidor);

        $from_lat = substr(trim($parte[0]), 0, 7);
        $from_lon = substr(trim($parte[1]), 0, 7);

        $coluna_distancia = ", (111.045 * DEGREES(ACOS(COS(RADIANS({$from_lat})) * COS(RADIANS(tbl_posto_fabrica.latitude)) * COS(RADIANS(tbl_posto_fabrica.longitude) - RADIANS({$from_lon})) + SIN(RADIANS({$from_lat})) * SIN(RADIANS(tbl_posto_fabrica.latitude))))) AS distancia";
        $cond_distancia = "AND (111.045 * DEGREES(ACOS(COS(RADIANS({$from_lat})) * COS(RADIANS(tbl_posto_fabrica.latitude)) * COS(RADIANS(tbl_posto_fabrica.longitude) - RADIANS({$from_lon})) + SIN(RADIANS({$from_lat})) * SIN(RADIANS(tbl_posto_fabrica.latitude))))) < 100";
        $order_by = "distancia ASC";

    } else {
        echo "Nenhuma Assistência Técnica Autorizada localizada para este CEP";
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
                    tbl_posto_fabrica.contato_bairro AS bairro,
                    tbl_posto_fabrica.contato_estado AS uf,
                    tbl_posto_fabrica.contato_complemento AS complemento
                    {$coluna_distancia}
              FROM tbl_posto
              JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = {$fabrica}
              JOIN tbl_posto_linha   ON tbl_posto.posto = tbl_posto_linha.posto AND tbl_posto_linha.linha in ({$linha})
             WHERE tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
                   {$cond_distancia}
               AND tbl_posto_fabrica.posto <> 6359
               AND tbl_posto_fabrica.divulgar_consumidor IS TRUE
               AND tbl_posto_fabrica.senha <> '*'
          ORDER BY  {$order_by}
          LIMIT 10";

    $res = pg_query($con,$sql);

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
                $nome = $data->nome."<br />";
            }else{
                $nome_fantasia = strtoupper(retira_acentos($data->nome));
                $nome = "";
            }

            $data->endereco = mb_detect_encoding($data->endereco, 'UTF-8', true) ? utf8_decode($data->endereco) : $data->endereco;
            $data->bairro = mb_detect_encoding($data->bairro, 'UTF-8', true) ? utf8_decode($data->bairro) : $data->bairro;
            $nome = preg_replace('/(\d{11})/', '', $nome);

            $cor = ($i%2 == 0) ? "#EEF" : "#FFF";

            echo "
                <div class='row row-posto' data-lat='{$data->lat}' data-lng='{$data->lng}' style='display: none;'>
                    <div class='col-md-12'>
                        <p style='border-bottom: 1px solid #CCCCCC; padding-bottom: 20px;'>
                            <br />
                            <strong><img width='10' style='float: left;margin-top: 3px;margin-right: 10px;' src='img/point.png' /> $nome_fantasia</strong> <br />
                            $nome
                            $data->endereco, $data->numero  $data->complemento&nbsp; / &nbsp; CEP: $cep <br />
                            BAIRRO: $data->bairro &nbsp; / &nbsp; $data->cidade - $data->uf <br />
                            <img width='10' style='float: left;margin-top: 3px;margin-right: 10px;' src='img/phone.png' /> $telefone &nbsp; / &nbsp; ".strtolower($data->email)." <br />
                            <button type='button' class='btn btn-default' onclick=\"localizarMap('".$data->lat."', '".$data->lng."')\" style='margin-top: 10px;'><i class='glyphicon glyphicon-search'></i> Localizar</button>
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

        echo "*".$lat_lng."*".json_encode(["consumidor"=>$latLonConsumidor]);
    } else {
        echo 'Nenhuma Assistência Técnica Autorizada localizada para este CEP';
    }

    exit;
}

$titulo_mapa_rede = 'Assistência Técnica';
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
        <script src="https://www.google.com/recaptcha/api.js?hl=pt-BR&onload=showRecaptcha&render=explicit" async defer></script>

        <style type="text/css">
            body{
                font-size: 15px !important;
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
                $("#end_cliente").val(results[1]+":"+results[2]+":"+results[3]+":"+results[4]);
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

                    
                    // Markers.add(lat, lng, pointIcon, value.nome_fantasia);

                    // M = L.marker([lat,lng], {icon: pointIcon})
                    //addTo(MapaTele.map);

                    // Markers.add(lat, lng, "blue_midea", value.nome_fantasia);

                    Markers.add(lat, lng, "blue_midea", value.nome_fantasia,"",{
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

            function setZoomAllMarkers(){
                Markers.focus();
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

            /* Google Maps */
            function initialize_antigo(markers) {
                var width = parseInt($("#map_canvas").width() / 2);
                var height = parseInt($("#map_canvas").height() / 2);

                var url = "https://maps.googleapis.com/maps/api/staticmap?scale=2&size="+width+"x"+height+"&maptype=roadmap&"+markers.join('&')+"&key=AIzaSyC5AsH3NU-IwraXqLAa1qbXxyjklSVP-cQ";

                $("#map_canvas").html("<img src='"+url+"' style='width: 100%; height: 100%;' />");
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

                    markers.push("markers=color:red%7C"+lat+","+lng);
                });

                initialize(markers);
            }

            function localizarMap_antigo(lat, lng) {
                
                scrollPostMessage();

                var width = parseInt($("#map_canvas").width() / 2);
                var height = parseInt($("#map_canvas").height() / 2);

                var url = "https://maps.googleapis.com/maps/api/staticmap?center="+lat+","+lng+"&zoom=15&scale=2&size="+width+"x"+height+"&maptype=roadmap&markers=color:red%7C"+lat+","+lng+"&key=AIzaSyC5AsH3NU-IwraXqLAa1qbXxyjklSVP-cQ";
                $("#map_canvas").html("<img src='"+url+"' style='width: 100%; height: 100%;' />");

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

                    markers.push("markers=color:red%7C"+lat+","+lng);
                });

                initialize(markers);
            }
            /* Fim - Google Maps */

            
            function carregaMapa(){
                var fabrica = <?=$cod_fabrica;?>;
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

            $('document').ready(function() {
                // $("#cep").blur(function() {
                //     busca_cep($(this).val(),"");
                // });
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
                    
                    if(page >= len/2){
                        return;
                    }
                    $("#lista_posto").find(".row-posto").fadeOut(200);
                    pageSlice = page * 2;


                    totalPagina = len/2;
                    if(totalPagina == 1.5){
                        //Para retirar possíveis decimais
                        totalPagina = 2;
                    }
                    $("#pag-posto-label").html("Página "+(page+1)+" de "+totalPagina);

                    
                    $("#lista_posto").find(".row-posto").slice(pageSlice,pageSlice+2).fadeIn(500);
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
                    pageSlice = page * 2;


                    var len = parseInt($("#lista_posto").find(".row-posto").length);
                    if(len == null || len == undefined || len == 0){
                        len = 0;
                    }

                    totalPagina = len/2;
                    console.log(totalPagina);
                    if(totalPagina == 1.5){
                        //Para retirar possíveis decimais
                        totalPagina = 2;
                    }
                    $("#pag-posto-label").html("Página "+(page+1)+" de "+(totalPagina));

                    $("#lista_posto").find(".row-posto").slice(pageSlice,pageSlice+2).fadeIn(500);
                    $("#pag-posto").val(page);    
                });

                $('#btn_acao').click(function() {

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
                        var fabrica = <?=$cod_fabrica;?>;



                        
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
                                
                                if (data != "Nenhuma Assistência Técnica Autorizada localizada para este CEP") {
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

                                    $("#lista_posto").find(".row-posto").slice(0,2).fadeIn(500);
                                    $("#pag-posto").val(0);

                                    var len = parseInt($("#lista_posto").find(".row-posto").length);
                                    if(len == null || len == undefined || len == 0){
                                        len = 0;
                                    }

                                    totalPagina = len/2;
                                    if(totalPagina == 1.5){
                                        //Para evitar decimais
                                        totalPagina = 2;
                                    }
                                    $("#pag-posto-label").html("Página 1 de "+(totalPagina));

                                } else{
                                    $("#msgErro").text("Nenhuma Assistência Técnica Autorizada localizada para este CEP").show();
                                }
                            }
                        });

                    });

                    window.parent.postMessage($(document).height()+100, "*");
                    scrollPostMessage();
                });


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

                var showOs = function(ret){
                    var qtde = ret.length;
                    var msg_situacao = "";

                    $.each(ret,function(key,value){

                        if (typeof value != 'object' || typeof value.entity == 'undefined') {
                            return;
                        }

                        var descricao;
                        var marca = value.entity.marca;
                        var fone = value.entity.contato_fone_comercial;
                        fone = fone.replace(/^([0-9][0-9])-/g, "(\$1) ");
                        descricao = 'Situação';
                        var situacao = value.entity.status_checkpoint;
                        switch(situacao) {
                            case "4":
                            case "9":
			    case "48":
			    case "49":
			    case "50":
                                msg_situacao = "SEU APARELHO ESTÁ PRONTO PARA RETIRADA.";
                                break;
                            case "2":
                            case "8":
                            case "14":
                                msg_situacao = "O REPARO DO SEU APARELHO ESTÁ EM ANDAMENTO.";
                                break;
                            case "3":
                            case "30":
                                msg_situacao = "O REPARO DO SEU APARELHO ESTÁ EM ANDAMENTO. ENTRE EM CONTATO COM O POSTO AUTORIZADO PARA SABER A DATA PARA RETIRADA.";
                                break;
                            case "0":
                            case "1":
			    case "45":
			    case "46":
			    case "47":
                                msg_situacao = "O REPARO DO SEU APARELHO ESTÁ EM ANDAMENTO. QUALQUER DÚVIDA ENTRE EM CONTATO CONOSCO.";
                                break;
                           
                        }

                        var resultado = "<ul class='list-group' style='margin-bottom: 0px;'>"+
                                            "<li class='list-group-item'><b>"+descricao+":</b> "+msg_situacao+
                                            "</li>"+
                                            "<li class='list-group-item panel-heading' style='background-color: #428bca; border-color: #428bca'>"+
                                                "<h3 style='margin-top:0;margin-bottom:0;font-size:16px;color:inherit'><b>Ordem de serviço: "+ value.sua_os+ "</b>"+
                                                "</h3>"+
                                            "</li>"+
                                            "<li class='list-group-item' > "+((value.entity.consumidor_revenda == "R") ? "<b>Revenda</b>" : "<b>Consumidor</b>")+": "+((value.entity.consumidor_revenda == "R") ? value.entity.revenda_nome : value.entity.consumidor_nome) +
                                            "</li>"+
                                            "<li class='list-group-item' ><b>Produto:</b> "+ value.entity.descricao_produto+
                                            "</li>"+
                                            
                                            "<li class='list-group-item' style='background: #e2e2e2;'><b>Informações da Assistência Técnica Autorizada</b></li>"+
                                            "<li class='list-group-item'><b>Nome:</b> "+value.entity.posto_autorizado+
                                            "</li>"+
                                            "<li class='list-group-item'><b>Endereço:</b>: "+value.entity.endereco + " " + value.entity.numero + " - " + value.entity.cidade + "</li>"+
                                            "<li class='list-group-item'><b>Telefone:</b> "+fone +
                                            "</li>"+
                                        "</ul>"+
                                        "<br>";

                        $("#res_os").html("");
                        $("#res_os").html(resultado);
                        $("#res_os").show();
                        //scrollPostMessage();
                    });
                };

                $('#btn_os').on('click', function(){
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
                        msgErro.push("Informe o número da ordem de serviço");
                    }

                    if (data.recaptcha_response_field.length == 0){
                        msgErro.push("Preencha o ReCaptcha");
                    }

                    if (data.cpf_cnpj.length == 0) {
                        msgErro.push("Informe o número do CPF/CNPJ");
                    }

                    if( data.cpf_cnpj.length > 0 &&
                        !data.cpf_cnpj.match(/^[0-9]{3}\.[0-9]{3}\.[0-9]{3}-[0-9]{2}$/) &&
                        !data.cpf_cnpj.match(/^[0-9]{2}\.[0-9]{3}\.[0-9]{3}\/[0-9]{4}-[0-9]{2}$/)){
                        msgErro.push('CPF/CNPJ Inválido');
                    }
                    if(msgErro.length > 0){
                        $("#msgErroOs").html(msgErro.join("<br />")).show();
                        $('#btn_os').text('Consultar');
                        $('#btn_os').prop('disabled', false);
                    }else{
                        data.cpf_cnpj = data.cpf_cnpj.replace(/[./-]+/gi,'');
                        var urlSuffix = '';
                        for(var index in data){
                            var value = data[index];
                            if(value == undefined || value.length == 0)
                                continue;
                            var value = data[index].replace(" ","");
                            urlSuffix += index +'/'+value+'/';
                        }

                        var apiLink = 'https://api2.telecontrol.com.br/institucional/statusos/';
                        var url = apiLink + urlSuffix;

                        $("#msgErroOs").html("").hide();
                        $("#result").hide();
                        $.ajax({
                            url : '../institucional/crossDomainProxy.php',
                            data : {
                                'apiLink' : url
                            },
                            method : 'POST',
                            success : function(data){
                                if(data.exception){
                                    $("#msgErroOs").text(data.message).show();
                                }else{
                                    showOs(data);
                                }
                            },
                            error : function(data){
                                data = JSON.parse(data.responseText);
                                if (data.message.match("caracteres da imagem")) {
                                    alert(data.message);
                                }else{
                                    $("#msgErroOs").text(data.message).show();
                                }
                            },
                            complete : function(data){
                                $('#btn_os').text('Consultar');
                                $('#btn_os').prop('disabled', false);
                                grecaptcha.reset();
                            }
                        });
                    }
                });

                /* Busca Produtos */
                $('#estado').on('change.fs', function() {
                    $('#cidade').find("option").remove();
                    $("#cidade").val("");

                    var uf = $('#estado').val();
                    var linha = $('#linha').val();

                    $('ul.brazil > li.active-region').removeClass('active-region');

                    if (uf) {
                        $('ul.brazil li#'+uf).addClass('active-region');
                    }

                    if (linha != "") {
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
                            complete: function(data) {
                                data = data.responseText;
                                if (data.match('Não há um posto próximo')) {
                                    $('#sacWurth').show();
                                }else{
                                    if ($('#sacWurth').length > 0) {
                                        $('#sacWurth').hide();
                                    }
                                }
                                $('#cidade').append(data).trigger('update.fs');
                            }
                        });
                    }
                });

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

            window.onmessage = function(event) {
                event.source.postMessage($(document).height()+100, event.origin);
            };
        </script>
    </head>

    <body>
        <div class="container-fluid">
            <div class='row'>
                <div class='col-xs-12 col-sm-12'>
                    <h2 style="text-align: left;font-size: 28px !important;font-family: 'neuron_boldregular';">Mapa de autorizadas</h2>
                    <p style="text-align: left;font-size: 20px;font-family: 'neuronitalic';">Encontre aqui a assistência técnica mais próxima de você.</p>
                </div>
            </div>
        </div>

      
        <br />
        <input type="hidden" id="iframe_linha" value="">
        <input type="hidden" id="iframe_estado" value="">
        <input type="hidden" id="iframe_cidade" value="">

        <div class="container-fluid">
            <div class="alert alert-danger" id='msgErro' role="alert" style="display: none;" >
                <strong>Preencha os campos obrigatórios</strong>
            </div>
        </div>

        <!-- Corpo -->
        <div class="container-fluid">
            <div class="row">
                <div class="col-xs-12 col-sm-12"><span class="obrigatorio" style="font-size: 10px">* Campos obrigatórios</span></div>
                <div class="col-xs-6 col-sm-6">
                    <div class="form-group" id="linha-group">
                        <div class="controls controls-row">
                            <label class="control-label" for="linha">Produto</label>
                            <div class="asterisco">*</div>
                            <select name="linha" id="linha" autofocus required>
                                <option value=""></option>
                                <?php
                                    $sql = "SELECT DISTINCT
                                                tbl_linha.descricao_site,
                                                tbl_linha.nome,
                                                tbl_linha.linha
                                            FROM tbl_linha
                                            WHERE tbl_linha.fabrica = $cod_fabrica
                                            AND tbl_linha.ativo IS TRUE
                                            order by tbl_linha.nome";
                                    $res = pg_query($con, $sql);
                                    $rows = pg_fetch_all($res);
                                    $descricao_site  = array();
                                    $arry_ids  = array();
                                    foreach ($rows as $key => $ln) {
                                        $descricao_site[$ln["linha"]] = $ln["descricao_site"];
                                    }   
                                    foreach ($rows as $key => $ln) {

                                        if (in_array($ln["descricao_site"], $descricao_site)) {
                                            $arry_ids[$ln["descricao_site"]][$ln["linha"]] = $ln["descricao_site"];
                                        } 
                                    }   
                                    foreach ($arry_ids as $descricao => $linhas) {
                                        $xlinhas = implode(",", array_keys($linhas));
                                        echo "<option value='{$xlinhas}'>".$descricao."</option>";
                                    }
                                    
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-xs-6 col-sm-5">
                    <div class="form-group" id="cep-group">
                        <label class="control-label" for="cep">CEP</label>
                        <div class="asterisco">*</div>
                        <div class="controls controls-row">
                            <input type="text" name="cep" id="cep" >
                            <input id="end_cliente" name="end_cliente" type="hidden" value="" />
                        </div>
                    </div>
                </div>
                <div class="col-xs-1 col-sm-1" style="text-align: right;">
                    <label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label>
                    <button class="btn btn-default" id="btn_acao" type="button">Buscar</button> &nbsp; <span id="loading"></span>
                </div>
            </div>
            <div class='row'>                
                <div id="box_mapa" class="col-xs-12 col-sm-6" style=" text-align: center;" >
                    <div id="map_canvas" style="height: 430px; margin-top: 20px; border: 1px solid #CCCCCC;"></div>
                    <div class="text-right">
                        <br />
                        <button type="button" style="display: none;" id="show_all" class="btn btn-default" onclick="setZoomAllMarkers()"><i class="glyphicon glyphicon-map-marker"></i> Mostrar todos os Postos</button>
                    </div>
                </div>
                <div id="box_resultado_posto" class="col-sm-6" style="display: none">
                    <div class="col-xs-12 col-sm-12" id="lista_posto" style="padding-bottom: 89px;"></div>
                    <div class="col-xs-12 col-sm-12 text-center" id="paginacao" style="padding-bottom: 100px;">
                        <button class="btn btn-info" type="button" id="page-posto-left" style="float: left"><i class="glyphicon glyphicon-arrow-left"></i></button>
                        <span id="pag-posto-label"></span>
                        <button class="btn btn-info" type="button" id="page-posto-right" style="float: right"><i class="glyphicon glyphicon-arrow-right"></i></button>
                    </div>
                    <input type="hidden" name="pag-posto" id='pag-posto' >
                </div>
            </div>
            <div class="row">
                <div class="col-sm-12 text-left">
                    <hr>
                    <label class="control-label" style="font-size: 20px;text-align: center">Status de Serviço</label>
                    <p style="text-align: left;font-size: 20px;font-family: 'neuronitalic';">Após o atendimento, o status da sua solicitação pode ser acompanhado aqui.</p>
                </div>
                <div class="col-xs-12 col-sm-12">
		    <div class="alert alert-danger" id='msgErroOs' role="alert" style="display: none;" >
                	<strong>Preencha os campos obrigatórios</strong>
            	    </div>
        	</div>
		<div class='col-xs-12 col-sm-6'>
                    <div class="well" style="height: 383px;">
                            <div class="row">
                                <div class="col-sm-6">
                                    <label for="os">N. da Ordem de Serviço</label>
                                    <div class="asterisco">*</div><input type="text" id="os" name="os" />
                                </div>
                                <div class="col-sm-6">
                                    <label for="cpf_cnpj">CPF / CNPJ</label>
                                    <div class="asterisco">*</div><input type="text" name="cpf_cnpj" id="cpf_cnpj" />
                                </div>
                           </div>
                            <div class="row">
                               <div class="col-sm-12">
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
