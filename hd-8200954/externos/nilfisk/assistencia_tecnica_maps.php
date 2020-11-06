<?php
require_once('../../admin/dbconfig.php');
require_once('../../admin/includes/dbconnect-inc.php');
require_once('../../admin/funcoes.php');
require_once('../../classes/Posvenda/TcMaps.php');


if ($_GET['ajax_familia']) {
    $linha   = (int) $_GET['linha'];
    $fabrica = (int) $_GET['fabrica'];
    
    $sql = "
        SELECT DISTINCT f.familia, f.descricao
        FROM tbl_familia f
        INNER JOIN tbl_produto p ON p.fabrica_i = {$fabrica} AND p.familia = f.familia
        INNER JOIN tbl_linha l ON l.fabrica = {$fabrica} AND l.linha = p.linha AND l.ativo IS TRUE
        WHERE f.fabrica = {$fabrica}
        AND f.ativo IS TRUE
        AND l.linha = {$linha}
    ";
    $res = pg_query($con, $sql);
    
    if (pg_num_rows($res) > 0) {
        $familias = pg_fetch_all($res);
        
        $familias = array_map(function($f) {
            $f['descricao'] = utf8_encode($f['descricao']);
            return $f;
        }, $familias);
        
        exit(json_encode([
            'familias' => $familias
        ]));
    } else {
        exit(json_encode([
            'erro' => utf8_encode('Nenhuma família encontrada')
        ]));
    }
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
$fabrica_geo  = $cod_fabrica;
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
if (trim($token) != trim($token_comp)) {
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


/* Busca os Postos Autorizados */
if (isset($_POST['endereco'])) {
    $linha    = $_POST['linha'];
    $endereco = $_POST['endereco'];
    $fabrica  = $_POST['fabrica'];
    $posto    = $_POST['posto'];

        $res_endereco = explode(":", $endereco);
        $rua          = $res_endereco[0];
        $bairro       = $res_endereco[1];
        $cidade       = $res_endereco[2];
        $estado       = $res_endereco[3];

        $TcMaps       = New \Posvenda\TcMaps();
        $geocode      = $TcMaps->geocode($rua, null, $bairro, $cidade, $estado, null, $cep);

        if (is_array($geocode)) {
            $latitude_consumidor  = $geocode["latitude"];
            $longitude_consumidor = $geocode["longitude"];

            $sql = "SELECT x.* FROM(
                        SELECT DISTINCT
                            tbl_posto.posto ,
                            tbl_posto.nome ,
                            tbl_posto_fabrica.nome_fantasia ,
                            tbl_posto_fabrica.contato_cep            AS cep ,
                            tbl_posto_fabrica.latitude               AS lat ,
                            tbl_posto_fabrica.longitude              AS lng ,
                            tbl_posto_fabrica.contato_fone_comercial AS telefone ,
                            tbl_posto_fabrica.contato_email          AS email ,
                            tbl_posto_fabrica.contato_endereco       AS endereco ,
                            tbl_posto_fabrica.contato_numero         AS numero ,
                            tbl_posto_fabrica.contato_cidade         AS cidade ,
                            tbl_posto_fabrica.contato_bairro         AS bairro,
                            tbl_posto_fabrica.contato_complemento    AS complemento,
                        (111.045 * DEGREES(ACOS(COS(RADIANS({$latitude_consumidor})) * COS(RADIANS(tbl_posto_fabrica.latitude)) * COS(RADIANS(tbl_posto_fabrica.longitude) - RADIANS({$longitude_consumidor})) + SIN(RADIANS({$latitude_consumidor})) * SIN(RADIANS(tbl_posto_fabrica.latitude)))))::integer AS distance
                            
                        FROM tbl_posto
                            INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto       AND tbl_posto_fabrica.fabrica = {$fabrica}
                            INNER JOIN tbl_posto_linha   ON tbl_posto.posto         = tbl_posto_linha.posto AND tbl_posto_linha.linha    = {$linha}
                        WHERE tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
                            AND tbl_posto_fabrica.divulgar_consumidor IS TRUE
                        ) x
                        WHERE x.distance <= 50
                        ORDER BY x.distance ASC, x.nome ASC";

        } else {
            echo "Endereço não encontrado.";
            exit();
        }
            
        
        /* RESULT DA SQL*/
        $res     = pg_query($con,$sql);
        if (pg_num_rows($res) > 0) {
            $cor = "";
            $i   = 0;

            while ($data      = pg_fetch_object($res)) {
                $cep          = maskCep($data->cep);
                $telefone     = maskFone($data->telefone);
                $distancia_km = $data->distance." Km";

                if (strlen(trim($data->nome_fantasia)) > 0 && $data->nome_fantasia != "null") {
                    $nome_fantasia = strtoupper(retira_acentos($data->nome_fantasia));
                    $nome          = $data->nome."<br />";
                }else{
                    $nome_fantasia = strtoupper(retira_acentos($data->nome));
                    $nome          = "";
                }

                $nome = preg_replace('/(\d{11})/', '', $nome);
                $cor  = ($i%2 == 0) ? "#EEF" : "#FFF";

                echo "<div class='row row-posto' data-lat='{$data->lat}' data-lng='{$data->lng}'>
                        <div class='col-md-12'>
                            <p style='border-bottom: 1px solid #CCCCCC; padding-bottom: 20px;'>
                                <br />
                                <strong>$nome_fantasia &nbsp; - &nbsp; <i>(Raio de {$distancia_km})</i></strong>  <br />
                                $nome
                                $data->endereco, $data->numero  $data->complemento&nbsp; / &nbsp; CEP: $cep <br />
                                BAIRRO: $data->bairro &nbsp; / &nbsp; $data->cidade - $uf <br />
                                $telefone &nbsp; / &nbsp; ".strtolower($data->email)." <br />
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
                    "latitude"      => $data->lat,
                    "longitude"     => $data->lng
                );
                $i++;
            }

            $msg_erro_div = "display: none;";
            $lat_lng      = json_encode($lat_lng);
            echo "*".$lat_lng;
        } else {
            echo 'Nenhum Posto Autorizado localizado para esta linha e CEP!';
        }

        exit;
}

// Preparando variáveis para parametrização do HTML/CSS/JS
$titulo_mapa_rede       = 'Assistência Técnica - ' . $nome_fabrica;
$style_container_titulo = 'background-color: #f5f5f5; border-bottom: 1px solid #cccccc;';

if ($_GET["xcf"] == 'true')
    $xcf = "-".$_GET['cf'];
?>
<!DOCTYPE html>
<html lang='en'>
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?=$titulo_mapa_rede?></title>
        <link type="text/css" rel="stylesheet" media="screen" href="../bootstrap3/css/bootstrap.min.css" />
        <link type="text/css" rel="stylesheet" media="screen" href="../bootstrap3/css/bootstrap-theme.min.css" />
        <link type="text/css" rel="stylesheet" media="screen" href="../../plugins/select2/select2.css" />

        <script type="text/javascript" src="../../admin/plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
        <script type="text/javascript" src="../cssmap_brazil_v4_4/jquery.cssmap.js"></script>
        <script src="https://www.google.com/recaptcha/api.js?hl=pt-BR&onload=showRecaptcha&render=explicit" async defer></script>
        <script src="../institucional/lib/mask/mask.min.js" ></script>
        <link href="../../plugins/leaflet/leaflet.css" rel="stylesheet" type="text/css" />
        <script src="../../plugins/leaflet/leaflet.js" ></script>
        <script src="../../plugins/leaflet/map.js?v=<?php echo date('YmdHis');?>" ></script>
        <link href="../../plugins/font_awesome/css/font-awesome.css" rel="stylesheet" type="text/css" />
        <script>
            var MapaTelecontrol = Map;
        </script>
        <script src="../../plugins/mapbox/geocoder.js"></script>
        <script src="../../plugins/mapbox/polyline.js"></script>

        <script src="../../plugins/select2/select2.js"></script>
        <style type="text/css">
            <?php if ($cod_fabrica == '124'): ?>
            @font-face {
                font-family: "Segoe";
                src: url("institucional/fonts/segoe/segoeuib.ttf");
            }

            body { font-family: "Segoe", serif }
            <?php endif ?>

            .titulo{
                border-bottom: 1px solid #cccccc;
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

            .obrigatorio{
                color: #ff0000;
            }

            .asterisco{
                color: #ff0000;
                position: absolute;
                z-index: 1000;
                margin-left: -12px;
                margin-top: 10px;
            }

            .container {
                width: 750px;
            }

            .glyphicon{
                top: 2px;
            }

            /* Bootstrap 3 seta o box-sizing para border-box, isso desloca e corta os objetos do mapa */
            .texto_cidade{
                font-size: 12px;
                font-weight: normal;
                color:#ff0000;
            }
            #reCaptcha{
                margin-top: 5px;
                margin-bottom: 5px;
                margin-left: 14px;
            }
            #btn_os{
                margin-left: 15px;
            }
        </style>

        <style type="text/css">
            .select2-container--default .select2-selection--single{
                width: 420px;
                min-width: 200px;
                border-radius: 3px;
                position: relative;
            }
            .select2-search--dropdown .select2-search__field {
                padding: 4px;
                box-sizing: border-box;
                width: 400px;
                min-width: 200px;
            }
            .select2-dropdown .select2-dropdown--below{
                width: 405px !important;
            }
            .select2-dropdown {
                width: 405px !important;
            }
        </style>
        
        <script type="text/javascript">
            var showRecaptcha= function() {
                grecaptcha.render('reCaptcha', {
                  'sitekey' : '6LckVVIUAAAAAEQpRdiIbRSbs_ePTTrQY0L4959J'
                });
            };

            var MapaTele, Router, Geocoder, Markers;

            function addMap(data) {
                var locations = $.parseJSON(data);

                if (typeof MapaTele !== 'object') {
                    MapaTele = new MapaTelecontrol("map_canvas");
                    Router   = new Router(MapaTele);
                    Geocoder = new Geocoder();
                    Markers = new Markers(MapaTele);

                    MapaTele.load();
                }

                Markers.remove();
                Markers.clear();
                $.each(locations, function(key, value) {
                    var lat = value.latitude;
                    var lng = value.longitude;

                    if (lat == null || lng == null) {
                        return true;
                    }

                    Markers.add(lat, lng, "red", value.nome_fantasia);
                });
                Markers.render();
                Markers.focus();
            }

            function localizarMap(lat, lng) {
                MapaTele.setView(lat, lng, 15);
                MapaTele.scrollToMap();
            }

            function setZoomAllMarkers(){
                Markers.focus();
            }

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

            <?php
            if ($_GET['xcf'] == 'true') {
            ?>
                $(window).load(function () {
                    less.modifyVars({'@map_340':'transparent url(\'br-340<?='-'.$cf?>.png\') no-repeat -970px 0'});
                });
            <?php
            }
            ?>
            
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

            function busca_cep(cep,method){
                return new Promise(function (resolve, reject) {
                    var img = $("<div id='loading'><i class='fa-spinner fa fa-spin'></i></div>");

                    $("#cep").hide();
                    $("#cep").parents(".controls").append(img);
                
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
                            $("#cep").show();
                            $("#loading").remove();
                            resolve();
                        },
                        error: function(xhr, status, error) {
                            reject();
                        }
                    });
                });
            }
            
            const fabrica = <?=$cod_fabrica?>;

            $(function() {
                $('#box_mapa').hide();
                $("select").select2();
                
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
                
                $('#linha').on('change', e => {
                    const icon = $('<i></i>', {
                        class: 'fa fa-spinner fa-pulse'
                    });
                    
                    var xlinhax = $("#linha").val();

                    if (xlinhax != ""){
                        $('#familia').next('span').hide();
                        $('#familia').find('option:first').nextAll().remove();
                        $('#familia').val('').trigger("change");
                        $('#familia').before(icon);
                        $.ajax({
                            async: true,
                            timeout: 60000,
                            url: window.location,
                            type: 'get',
                            data: {
                                ajax_familia: true,
                                linha: e.target.value,
                                fabrica: fabrica
                            }
                        }).fail(res => {
                            alert('Erro ao carregar Famílias');
                            $(icon).remove();
                            $('#familia').next('span').fadeIn(300);
                        }).done((res, req) => {
                            if (req === 'success') {
                                res = JSON.parse(res);
                                
                                if (res.erro) {
                                    alert(res.erro);
                                } else {
                                    res.familias.forEach((familia, i) => {
                                        const option = $('<option></option>', {
                                            value: familia.familia,
                                            text: familia.descricao
                                        });
                                        $('#familia').append(option);
                                    });
                                }
                            } else {
                                alert('Erro ao carregar Famílias');
                            }
                            
                            $(icon).remove();
                            $('#familia').next('span').fadeIn(300);
                            $('#familia').select2();
                        });

                    }
                });
                
                /* Busca Postos Autorizados */
                $('#btn_acao').click(function() {
                    $('#lista_posto').html("");

                    if ($('#familia').val() == "") {
                        $('#familia-group').addClass('danger');
                        $("#msgErro").text('Selecione uma familia de produto!').show();
                        return;
                    } else {
                        closeMessageError();
                    }

                    if ($('#linha').val() == "") {
                        $('#linha-group').addClass('danger');
                        $("#msgErro").text('Selecione uma linha de produto!').show();
                        return;
                    } else {
                        closeMessageError();
                    }

                    var cep = $(this).parents(".col-lg-4").find("#cep").val();
                    
                    (new Promise(function (resolve, reject) {
                        busca_cep(cep).then(function () {
                            resolve();
                        }).catch(function () {
                            busca_cep(cep, "database").then(function () {
                                resolve();
                            }).catch(function () {
                                reject();
                            });
                        });
                    })).then(function (resolve) {
                        var linha    = $('#linha').val();
                        var endereco = $('#end_cliente').val();
                        var fabrica  = <?=$cod_fabrica;?>;

                        $.ajax({
                            url:  window.location,
                            type: "POST",
                            dataType: "JSON",
                            async: false,
                            data:
                            {
                                linha     : linha,
                                fabrica   : fabrica,
                                cep: cep,
                                endereco  : endereco,
                                token     : '<?=$token?>'
                            },
                            beforeSend: function() {
                                loading("show");
                                $("#msgErro").text('').hide();
                            },
                            complete: function(data) {
                                loading("hide");

                                data = data.responseText;
                                if (data != "Nenhum Posto Autorizado localizado para esta linha e CEP!") {
                                    info = data.split("*");
                                    var dados = info[1];

                                    if (dados.length > 0) {
                                        $('#box_mapa').show();
                                        addMap(dados);

                                        if (JSON.parse(dados).length < 2)
                                            $("#show_all").hide();
                                        else
                                            $("#show_all").show();
                                    }

                                    $('#lista_posto').html(info[0]);
                                }else{
                                    if (data == "Nenhum Posto Autorizado localizado para esta linha e CEP!") {
                                        //$("#msgErro").html('<center>' + data + '<br /><br /> <button class="btn btn-primary" id="solicitar_asstec">Solicitar assistência técnica</button>');
                                        $("#msgErro").html('Não encontramos nenhuma assistência próxima de sua residência, favor entrar em contato através dos canais:<br /><br /><strong>Fone:</strong> (11) 3959-0300');
                                    } else {
                                        $("#msgErro").html('<center>' + data + '</center>');
                                    }
                                    $("#msgErro").show();
                                    $('#box_mapa').hide();
                                }
                            }
                        });

                        window.parent.postMessage($(document).height()+100, "*");
                        scrollPostMessage();
                    }).catch(function (reject) {
                        alert("Não foi possível encontrar o CEP");
                    });
                });
                
                $("#btn_pesquisa_os").click(function(){
                    $("#consulta_os").show();
                    $("#consulta_mapa_rede").hide();
                    $("#lista_posto").html("");
                    $("#cep").val("");

                    $('#familia').val("").trigger('change');
                    $("#linha").val("").trigger('change');
                    $('#familia').find('option:first').nextAll().remove();
                });

                $("#btn_pesquisa_posto").click(function(){
                    $("#consulta_os").hide();
                    $("#consulta_mapa_rede").show();
                    $("#lista_os_pesquisa").html("");
                    $("#lista_os_pesquisa").hide();
                    $("#os").val("");
                    $("#cpf_cnpj").val("");
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
                        var situacao = value.entity.situacao;
                        switch(situacao) {
                            case "1":
                                msg_situacao = "SEU APARELHO ESTÁ PRONTO PARA RETIRADA.";
                                break;
                            case "2":
                                msg_situacao = "O REPARO DO SEU APARELHO ESTÁ EM ANDAMENTO.";
                                break;
                            case "3":
                                msg_situacao = "O REPARO DO SEU APARELHO ESTÁ EM ANDAMENTO. ENTRE EM CONTATO COM O POSTO AUTORIZADO PARA SABER A DATA PARA RETIRADA.";
                                break;
                            case "4":
                                msg_situacao = "O REPARO DO SEU APARELHO ESTÁ EM ANDAMENTO. QUALQUER DÚVIDA ENTRE EM CONTATO CONOSCO.";
                                break;
                            case "5":
                                msg_situacao = "POR FAVOR ENTRE EM CONTATO CONOSCO PARA MAIS INFORMAÇÕES SOBRE O REPARO DO SEU APARELHO.";
                                break;
                        }

                        var resultado = "<br>"+
                                        "<ul class='list-group' style='margin-bottom: 0px;'>"+
                                            "<li class='list-group-item panel-heading' style='background-color: #428bca; border-color: #428bca'>"+
                                                "<h3 style='margin-top:0;margin-bottom:0;font-size:16px;color:inherit'><b>Ordem de serviço: "+ value.sua_os+ "</b>"+
                                                "</h3>"+
                                            "</li>"+
                                            "<li class='list-group-item' > "+((value.entity.consumidor_revenda == "R") ? "<b>Revenda</b>" : "<b>Consumidor</b>")+": "+((value.entity.consumidor_revenda == "R") ? value.entity.revenda_nome : value.entity.consumidor_nome) +
                                            "</li>"+
                                            "<li class='list-group-item' ><b>Produto:</b> "+ value.entity.descricao_produto+
                                            "</li>"+
                                            "<li class='list-group-item'><b>"+descricao+":</b> "+msg_situacao+
                                            "</li>"+
                                            "<li class='list-group-item'><b>Informações do Posto</b></li>"+
                                            "<li class='list-group-item'><b>Nome:</b> "+value.entity.posto_autorizado+
                                            "</li>"+
                                            "<li class='list-group-item'><b>Endereço:</b>: "+value.entity.endereco + " " + value.entity.numero + " - " + value.entity.cidade + "</li>"+
                                            "<li class='list-group-item'><b>Telefone:</b> "+fone +
                                            "</li>"+
                                        "</ul>"+
                                        "<br>";
                        $("#lista_os_pesquisa").html("");
                        $("#lista_os_pesquisa").html(resultado);
                        $("#lista_os_pesquisa").show();
                        scrollPostMessage();
                    });
                };

                $("#linha, #familia, #cep").on("change blur", function(){

                    let familia = $("#familia").val();
                    let linha   = $("#linha").val();
                    let cep     = $("#cep").val();

                    if (familia != "" && linha != "" && cep != "") {
                        $("#btn_acao").prop("disabled", false);
                    }


                });

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
                        $("#msgErro").html(msgErro.join("<br />")).show();
                        $('#btn_os').text('Consultar');
                        $('#btn_os').prop('disabled', false);
                        scrollPostMessage(1);
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

                        $("#msgErro").html("").hide();
                        $("#result").hide();
                        $.ajax({
                            url : '../institucional/crossDomainProxy.php',
                            data : {
                                'apiLink' : url
                            },
                            method : 'POST',
                            success : function(data){
                                if(data.exception){
                                    $("#msgErro").text(data.message).show();
                                }else{
                                    showOs(data);
                                }
                            },
                            error : function(data){
                                data = JSON.parse(data.responseText);
                                if (data.message.match("caracteres da imagem")) {
                                    alert(data.message);
                                }else{
                                    $("#msgErro").text(data.message).show();
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
            });
            /* Loading Imagem */
            function loading(e) {
                if (e == "show") {
                    $('#loading').html('<img src="imagens/loading.gif" />');
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

    <body <?=$body_style?> >
        <div class="container-fluid" <?=$style_container_titulo?> >
            <div class='row'>
                <div class='col-xs-12 col-sm-10 col-sm-offset-1 col-md-8 col-md-offset-2 col-lg-8 col-lg-offset-2 text-center titulo'>
                    <h3 style="text-align: center;" >
                        Assistência Técnica - <?=$nome_fabrica?>
                    </h3>
                </div>
            </div>
        </div>

        <br />
        <input type="hidden" id="iframe_linha" value="">
        <!-- Corpo -->
        <div class="container-fluid" id="consulta_mapa_rede">
            <div class='row'>
                <div class='col-lg-4 col-md-4'>
                    <span class="obrigatorio">* Campos obrigatórios</span>
                    <br /><br />
                    <div class="form-group col-lg-12 col-md-12" id="linha-group">
                        <div class="controls controls-row">
                            <label class="control-label" for="linha">Linha</label>
                            <div class="asterisco">*</div>
                            <div class="controls controls-row">
                                <select name="linha" id="linha" autofocus required>
                                    <option value="">Selecione</option>
                                    <?php
                                        $sql = "SELECT DISTINCT
                                                    tbl_linha.nome,
                                                    tbl_linha.linha
                                                FROM tbl_linha
                                                WHERE tbl_linha.fabrica = {$cod_fabrica}
                                                AND tbl_linha.ativo IS TRUE
                                                ORDER BY tbl_linha.nome";
                                        $res = pg_query($con, $sql);
                                        $rows = pg_num_rows($res);

                                        for ($i = 0; $i < $rows; $i++) {
                                            $linha = pg_fetch_result($res, $i, 'linha');
                                            $nome  = pg_fetch_result($res, $i, "nome");
                                            $refs  = array();
                                            echo "<option value='{$linha}'>{$nome}</option>";
                                        }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group col-lg-12 col-md-12" id="familia-group">
                        <div class="controls controls-row">
                            <label class="control-label" for="familia">Família</label>
                            <div class="asterisco">*</div>
                            <div class="controls controls-row">
                                <select name="familia" id="familia" required>
                                    <option value="">Selecione</option>
                                    <?php
                                        /*$sql = "SELECT familia, descricao
                                                FROM tbl_familia
                                                WHERE fabrica = $cod_fabrica
                                                AND ativo IS TRUE";
                                        $res = pg_query($con, $sql);
                                        $rows = pg_num_rows($res);

                                        for ($i = 0; $i < $rows; $i++) {

                                            $familia = pg_fetch_result($res, $i, 'familia');
                                            $nome  = pg_fetch_result($res, $i, "descricao");

                                            echo "<option value='{$familia}'>{$nome}</option>";
                                        }*/
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group col-lg-12 col-md-12" id="cep-group">
                        <div class="controls controls-row">
                            <label class="control-label" for="cep">CEP</label>
                            <div class="asterisco">*</div>
                            <input type="text"  id="cep" name="cep" class="form-control" maxlength="8" style="width:36.5%" required />
                            <input id="end_cliente" name="end_cliente" type="hidden" value="" />
                        </div>
                    </div>
                    <div class="form-group col-lg-12 col-md-12">
                        <button class="btn btn-primary" id="btn_acao" type="button">Pesquisar Assistência</button> &nbsp; <span id="loading"></span>
                        
                        <!-- <button class="btn btn-default btn-sm" id="btn_pesquisa_os">Pesquisar Ordem de Serviço</button> -->
                    </div>
                </div>
                
                <div id="box_mapa" class="col-md-8 col-lg-8 " style="display: none;text-align: left; z-index: 1;" >
                    <div id="map_canvas" style="height: 450px; margin-top: 50px; border: 1px solid #CCCCCC;"></div>
                    <div class="text-right">
                        <br />
                        <button type="button" id="show_all" class="btn btn-default" onclick="setZoomAllMarkers()"><i class="glyphicon glyphicon-map-marker"></i> Mostrar todos os Postos</button>
                    </div>

                    <div style="clear: both;"></div>
                    <div class="col-md-8 col-lg-8" id="lista_posto" style="padding-bottom: 100px;"></div>
                </div>
            </div>
        </div>

        <div class="container-fluid" id='consulta_os' style="display: none;">
            <div class='row'>
                <div class='col-lg-6 col-md-6 col-sm-6 well'>
                    <label class="control-label">Consulte o andamento do serviço</label> <br /><br />
                    <div class="row">
                        <div class="col-lg-6 col-sm-6 col-xs-6">
                            <label for="os">N. da Ordem de Serviço</label>
                            <input type="text"  id="os" name="os" class="form-control" />
                        </div>
                        <div class="col-lg-6 col-sm-6 col-xs-6">
                            <label for="cpf_cnpj">CPF / CNPJ</label>
                            <input type="text"  name="cpf_cnpj" id="cpf_cnpj" class="form-control"/>
                        </div>
                    </div>
                    <div class="row">
                        <div id="reCaptcha">Carrengado reCaptcha</div>
                    </div>
                    <div class="row">
                        <div class="">
                            <button class="btn btn-primary" id='btn_os' data-loading-text="Consultando...">Consultar</button>
                            <button class="btn btn-default btn-sm" type='button' id='btn_pesquisa_posto'">Pesquisar Assistência</button>
                        </div>
                    </div>
                </div>

                <div style="clear: both;"></div>
                <div class="col-md-8 col-lg-8" id="lista_os_pesquisa" style="padding-bottom: 100px; display: none;"></div>
            </div>
        </div>

        <div class="container-fluid">
            <div class="row">
                <div class="alert alert-danger col-lg-3 col-md-3 col-sm-8 col-xs-12 col-10" id='msgErro' role="alert" style="display: none;" >
                    <strong>Preencha os campos obrigatórios</strong>
                </div>
            <div>
        </div>

        <div style="clear: both;"></div>

        <!-- NOT FOUND -->
        <div class="container-fluid">
            <div class="alert alert-danger col-xs-12 col-sm-10 col-sm-offset-1 col-md-8 col-md-offset-2 col-lg-8 col-lg-offset-2" id='msgErroFound' role="alert" style="<?= (empty($msg_erro_div)) ? 'display: none;' : $msg_erro_div; ?>" >
                <?php echo $msg_erro; ?>
            </div>
        </div>
        <div class="scroll-xs visible-xs-block" ></div>
    </body>
</html>
