<?php
include "../../dbconfig.php";
include "/var/www/includes/dbconnect-inc.php";

use Posvenda\TcMaps;
$oTCMaps = new TcMaps(117, $con);

if (isset($_POST['ajax']) && strtolower($_POST['ajax']) == 'sim') {
    if (strtolower($_POST['action']) == 'consulta_macro_familia') {
        $sql_linha = "SELECT DISTINCT
                        tbl_linha.linha,
                        tbl_linha.nome
                    FROM tbl_linha
                        JOIN tbl_macro_linha_fabrica USING(linha)
                        JOIN tbl_macro_linha USING(macro_linha)
                    WHERE tbl_linha.fabrica = 117 AND tbl_linha.ativo IS TRUE
                    ORDER BY tbl_linha.nome";

        $res_linha = pg_query($con, $sql_linha);
        $options = '';
        for ($i = 0; $i < pg_num_rows($res_linha); $i++) {
            $options .= "<option value='".pg_fetch_result($res_linha, $i, "linha")."'>".pg_fetch_result($res_linha, $i, "nome")."</option>";
        }
        exit(json_encode(array("ok" => utf8_encode($options))));
    }elseif ($_POST['action'] == 'consulta_cidade') {
        $estado = $_POST['estado'];
        $macro_familia = $_POST['macro_familia'];

        $sql = "
            SELECT DISTINCT UPPER(tbl_posto_fabrica.contato_cidade) AS cidade, tbl_posto_fabrica.contato_cidade
            FROM tbl_posto_fabrica
                JOIN tbl_posto_linha ON tbl_posto_linha.posto = tbl_posto_fabrica.posto AND tbl_posto_linha.linha = {$macro_familia}
                JOIN tbl_produto ON tbl_produto.linha = {$macro_familia}
            WHERE tbl_posto_fabrica.fabrica = 117
                AND tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
                AND tbl_posto_fabrica.contato_estado = '{$estado}'
                AND tbl_posto_fabrica.divulgar_consumidor IS TRUE
            ORDER BY tbl_posto_fabrica.contato_cidade ASC";

        $res = pg_exec($con,$sql);
        if (pg_numrows ($res) > 0) {
            $options = "<option value='' selected >Selecione</option>";
            for ($i=0; $i<pg_numrows ($res); $i++ ){
                $cidade = pg_result($res,$i,'cidade');

                $options .= "<option value='$cidade'> $cidade</option>";
            }
        }else{
            $options = "<option value='0'> Nenhuma cidade encontrada para este estado.</option>";
        }
        exit(json_encode(array("ok" => utf8_encode($options))));
    }elseif ($_POST['action'] == 'assistencia') {
        $estado        = $_POST['estado'];
        $endereco      = $_POST['endereco'];
        $cep           = $_POST['cep'];
        $cidade        = utf8_decode($_POST['cidade']);
        $pais          = 'Brasil';
        $macro_familia = $_POST['macro_familia'];

        if(strlen(trim($endereco)) > 0 ){

            $endereco = explode(', ', $endereco);
            $estado = (!empty($endereco[3])) ? trim($endereco[3]): $estado;
            $cidade = (!empty($endereco[2])) ? trim($endereco[2]) : $cidade;
            $bairro = (!empty($endereco[1])) ? trim($endereco[1]) : $bairro;
            $endereco = (!empty($endereco[0])) ? trim($endereco[0]) : $endereco;
            $latLon = $oTCMaps->geocode($endereco, null, $bairro, $cidade, $estado, $pais);
            $lat = $latLon['latitude'];
            $lon = $latLon['longitude'];
        }
        $latLonStr = $lat.'@'.$lon;
        $order = " nome ";
        if(strlen($cep) > 2 or (!empty($lat) and !empty($lon))) {
            if(!empty($lat) and !empty($lon)) {
                $latLon = $oTCMaps->geocode($endereco, null, $bairro, $cidade, $estado, $pais);
                $lat = $latLon['latitude'];
                $lon = $latLon['longitude'];
                $latLonStr = $lat.'@'.$lon;
                $lat = substr(trim($lat),0,7);
                $lon = substr(trim($lon),0,7);

                $campo = ",  (111.045 * DEGREES(ACOS(COS(RADIANS($lat)) * COS(RADIANS(tbl_posto_fabrica.latitude)) * COS(RADIANS(tbl_posto_fabrica.longitude) - RADIANS($lon)) + SIN(RADIANS($lat)) * SIN(RADIANS(tbl_posto_fabrica.latitude))))) AS distance ";
                $order = " distance";
            }
            if (strlen($cep) > 0) {
                $limit = "LIMIT 8";
            }else{
                $cond = "AND tbl_posto_fabrica.contato_estado = '$estado' AND upper(tbl_posto_fabrica.contato_cidade) = upper('$cidade')";
            }
        }else{
            $cond = " AND tbl_posto_fabrica.contato_estado = '$estado'
                AND upper(tbl_posto_fabrica.contato_cidade) = upper('$cidade') ";
            $order = " nome ";
        }

        $sql = "
            SELECT DISTINCT
                CASE WHEN tbl_posto_fabrica.nome_fantasia <> '' THEN UPPER(tbl_posto_fabrica.nome_fantasia) ELSE tbl_posto.nome END AS nome,
                tbl_posto.posto,
                tbl_posto_fabrica.contato_endereco,
                tbl_posto_fabrica.contato_numero,
                tbl_posto_fabrica.contato_bairro,
                tbl_posto_fabrica.contato_fone_comercial,
                tbl_posto_fabrica.contato_cidade,
                tbl_posto_fabrica.obs_conta,
                tbl_posto_fabrica.parametros_adicionais, 
                tbl_posto_fabrica.latitude,
                tbl_posto_fabrica.longitude
                $campo
            FROM tbl_posto_fabrica
                JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = 117
                JOIN tbl_posto_linha ON tbl_posto_linha.posto = tbl_posto_fabrica.posto AND tbl_posto_linha.ativo IS TRUE
                JOIN tbl_linha ON tbl_linha.linha = tbl_posto_linha.linha AND tbl_linha.fabrica = 117 AND tbl_linha.ativo IS TRUE
                JOIN tbl_produto ON tbl_produto.linha = tbl_linha.linha AND tbl_linha.linha = {$macro_familia}
            WHERE tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
                AND tbl_posto_fabrica.divulgar_consumidor IS TRUE
                AND tbl_posto_fabrica.posto NOT IN(6359)
                $cond
            ORDER BY $order $limit";

        $res = pg_query($con, $sql);
        $num_rows = pg_num_rows($res);
        if ($num_rows > 0) {
            for ($i=0; $i < $num_rows; $i++){
                $nome     = utf8_encode(pg_result($res,$i,'nome'));
                $endereco = utf8_encode(pg_result($res,$i,'contato_endereco'));
                $numero   = utf8_encode(pg_result($res,$i,'contato_numero'));
                $fone     = utf8_encode(pg_result($res,$i,'contato_fone_comercial'));
                $bairro   = utf8_encode(pg_result($res,$i,'contato_bairro'));
                $latitude   = pg_result($res,$i,'latitude');
                $longitude   = pg_result($res,$i,'longitude');
                $obs_conta   = pg_result($res,$i,'obs_conta');
                $cidade_posto = utf8_encode(pg_result($res,$i,'contato_cidade'));

                $parametros_adicionais   = pg_result($res,$i,'parametros_adicionais');

                if(strlen($parametros_adicionais) > 0){
                    $obs = json_decode($parametros_adicionais, true);
                    $obs_conta = (strlen($obs["obs_cadence"]) > 0) ? "<br /> <strong>Observação:</strong> ".utf8_decode($obs["obs_cadence"])."<br />" : "";
                }else{
                    $obs_conta = "";
                }

                $lista = "<span style='margin: 10px 5px'>
                            <b>$nome</b><br />
                            $endereco, $numero - $bairro<br />
                            $cidade_posto - $estado - $fone
                            $obs_conta
                       </span>";                    

                    $posto[] = array('nome_fantasia' => "$nome", 'latitude' => "$latitude", 'longitude' => "$longitude", "endereco" => "$endereco", 'numero' => $numero, "bairro" => "$bairro", "cidade_posto" => "$cidade_posto", "estado" => "$estado", "fone" => "$fone");
            
            }
            exit(json_encode(array("posto" => $posto, "consumidor" => $latLonStr)));
        }
        exit;
    }elseif ($_POST['action'] == 'retorna_rota') {
        $oTcMaps = new TcMaps($login_fabrica, $con);
        $from_latlng = $_POST['from_latlng'];
        $to_latlng   = $_POST['to_latlng'];

        $response = $oTcMaps->route("{$from_latlng}", "{$to_latlng}");
        exit(json_encode(array("cost" => $response["total_km"], "route" => $response["rota"])));
    }
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
    <head>
        <title>Mapa da Rede Elgin</title>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <link type="text/css" rel="stylesheet" href="../../plugins/leaflet/leaflet.css"/>
        <link type="text/css" rel="stylesheet" media="screen" href="../../bootstrap/css/bootstrap.css" />
        <style type="text/css">
            #map_canvas{
                height: 610px;
                margin-top: 20px;
                border: 1px solid #CCCCCC;
                display: none;
            }
            #assistencia{
                width: 100%;
                height: 300px;
                overflow: auto;
            }
            #box_mapa{
                margin-bottom: 30px;
            }
            #consultar{
                width: 114px;
            }
            span.localizar, span.localizar-todos{
                cursor: pointer;
                color: #3a87ad;
                text-decoration: underline;
                display: inline;
            }
            span.localizar-todos{
                display: none;
            }
            span.rota{
                cursor: pointer;
                color: #3a87ad;
                text-decoration: underline;
                display: inline;
            }
        </style>
    </head>
    <body>
        <div class="container-fluid">
            <h2>Assistência</h2>
        </div>
        <div class="container">
            <div class="row-fluid">
                <div class="span6">
                    <div class="row-fluid">
                        <div class="span12">
                            <label for="familia">Escolha a linha de produtos:</label>
                            <select class="span12" name="macro-familia" id="macro-familia">
                                <option value=""></option>
                            </select>
                        </div>
                    </div>
                    <div class="row-fluid">
                        <div class="alert alert-info">Para realizar a rota até um Posto Autorizado é necessário informar um CEP</div>
                    </div>
                    <div class="row-fluid">
                        <div class="span12">
                            <label for="cep">Digite seu CEP:</label>
                            <div class="input-append">
                                <input type="hidden" name="endereco_cep" id="endereco_cep">
                                <input class="span12" id="cep" name="cep" type="text" placeholder="Opcional" value=""><span class="add-on" style="display: none;" id='loading-cep'><img style="width: 20px;" src="../../imagens/ajax-loader.gif"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row-fluid">
                        <div class="span12">
                            <label for="estado">Escolha o Estado:</label>
                            <select class="span12" id="estado" name="estado">
                                <option value="">Selecione a linha</option>
                            </select>                            
                        </div>
                    </div>
                    <div class="row-fluid">
                        <div class="span12">
                            <label for='cidade'>Escolha o Cidade:</label>
                            <input type="hidden" name="cidade_cep" id="cidade_cep">
                            <select class="span12" name="cidade" id="cidade">
                                <option value="">Selecione um estado</option>
                            </select>
                            <div style="text-align: right;">
                                <button class="btn btn-info" id="consultar" type="button">Consultar</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="span6">
                    <div class="row-fluid">
                        <div class="span12">
                            <label for="postos">Postos para assistência:</label>
                            <div id="assistencia"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row-fluid">
                <div class="span6"></div>
                <div class="span6" style="text-align: right;">
                    <span class="localizar-todos" onclick="markers.focus(true);">Visualizar Todos os Postos</span>
                </div>
            </div>
            <div class="row-fluid">
                <div class="span12">
                    <div id="box_mapa">
                        <div id="map_canvas"></div>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
<script src="../../plugins/leaflet/leaflet.js" ></script>
<script src="../../plugins/leaflet/map.js" ></script>
<script src="../../plugins/mapbox/geocoder.js"></script>
<script src="../../plugins/mapbox/polyline.js"></script>
<script src="../../plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script src='../../plugins/jquery.maskedinput_new.js'></script>
<script src="../../bootstrap/js/bootstrap.js"></script>
<script type="text/javascript">
    function carrega_macro_familia(){
        $.ajax({
            url: window.location.href,
            method: 'POST',
            data: { 'ajax': 'sim', 'action': 'consulta_macro_familia' },
            timeout: 8000
        }).fail(function(){
            alert('Não foi possível carregar as macro-famílias, tente novamente mais tarde');
        }).done(function(data){
            data = JSON.parse(data);
            $('#macro-familia').append(data.ok);
        });
    }

    var carrega_cep = 0;
    var endereco = {};

    function busca_cep(cep = null, macro_familia = 0, method = "webservice") {
        if (cep !== null && macro_familia !== 0) {
            $("#estado, #cidade").prop({ disabled: true }).html('<option>Pesquisando...</option>');
            $('#cep').width('417px');
            $('#loading-cep').show();
            $('.localizar-todos').hide();
            $.ajax({
                url: '../../admin/ajax_cep.php',
                method: 'GET',
                data: { method: method, cep: cep },
                timeout: 60000
            }).fail(function(){
                if (method == "webservice") {
                    busca_cep(cep, macro_familia, "database");
                }else{
                    alert('Não foi possível encontrar o CEP informado, tente novamente mais tarde');
                    $("#estado, #cidade").prop({ disabled: false });
                    $('#cep').width('447px');
                    $('#loading-cep').hide();
                }
            }).done(function(data){
                data = data.split(";");
                if (data[0] != "ok") {
                    if (method == "webservice") {
                        busca_cep(cep, macro_familia, "database");
                    }else{
                        alert("Não foi possível encontrar o CEP informado, verifique se o mesmo esta correto e consulte novamente");
                        $("#estado, #cidade").prop({ disabled: false });
                        $('#cep').width('447px');
                        $('#loading-cep').hide();
                        carrega_cep = 0;
                    }
                }else {
                    var end    = data[1];
                    var bairro = data[2];
                    var cidade = data[3];
                    var estado = data[4];

                    endereco.estado   = estado;
                    endereco.cidade   = cidade;
                    endereco.endereco = end;
                    endereco.bairro   = bairro;

                    $('#macro-familia').trigger('change');
                    $("#estado").val(estado).trigger('change');
                    $('#cidade_cep').val(cidade);
                    $("#estado, #cidade").prop({ disabled: false });
                    $('#cep').width('447px');
                    $('#loading-cep').hide();
                    carrega_cep = 0;
                }
            });
        }else{
            alert('É necessário informar um CEP e uma macro-família para a pesquisa de uma assistencia técnica');
            $("#estado, #cidade").prop({ disabled: false });
            $('#cep').width('447px');
            $('#loading-cep').hide();
            carrega_cep = 0;
        }
    }

    var map, markers, router;
    var rotas   = [];
    var mapRend = false;
    function initialize(markersIni) {
        $('#map_canvas').show();
        if (mapRend == false) {
            map      = new Map("map_canvas");
            map.load();
            markers  = new Markers(map);
            router   = new Router(map);
            Geocoder = new Geocoder();
            mapRend  = true;
        }

        markers.clear();
        markers.remove();

        markersIni.forEach(function(v, k) {
            markers.add(v.latitude,v.longitude,'red',v.title);
        });

        if ($("span.rota").data('consumidor') !== null && $("span.rota").data('consumidor').indexOf('@')) {
            var rota = $("span.rota").data('consumidor').split('@');
            markers.add(rota[0], rota[1],'blue', 'CLIENTE');
        }

        markers.render();
        markers.focus();
    }

    function addMap() {
        var markersMap = [];

        $("span.posto-resultado").each(function() {
            var lat = $(this).data("lat");
            var lng = $(this).data("lng");
            var fantasia = $(this).data("fantasia");

            if (lat == null || lng == null) {
                return true;
            }

            markersMap.push({latitude:lat,longitude:lng,title:fantasia});
        });

        initialize(markersMap);
    }

    function buscaAssistencia(cidade) {
        var estado = $("#estado").val();
        var cep    = $("#cep").val();
        var cidade = cidade;
        var lista  = "";

        var end = [];

        $("#assistencia").html("<div class='alert alert-warning'>Pesquisando...</div>");
        $("#consultar").prop({ disabled: true }).text("Consultando...");

        if (mapRend) {
            markers.clear();
            markers.remove();
        }

        if (typeof endereco.endereco != "undefined" && endereco.endereco.length > 0) {
            end.push(endereco.endereco);
        }

        if (typeof endereco.bairro != "undefined" && endereco.bairro.length > 0) {
            end.push(endereco.bairro);
        }

        if (typeof endereco.cidade != "undefined" && endereco.cidade.length > 0) {
            end.push(endereco.cidade);
        }

        if (typeof endereco.estado != "undefined" && endereco.estado.length > 0) {
            end.push(endereco.estado);
        }

        end = end.join(", ");
        $.ajax({
            url: window.location.href,
            method: 'POST',
            data: { 'ajax': 'sim', 'action': 'assistencia', 'estado': estado, 'cep': cep, 'cidade': cidade, 'endereco': end, 'macro_familia': $('#macro-familia').val() }
        }).fail(function(res){
            alert('Ocorreu um erro ao tentar buscar uma assistência técnica, tente novamente mais tarde');
            $("#consultar").prop({ disabled: false }).text("Consultar");
        }).done(function(dados){
            if(dados.length > 0){
                dados = JSON.parse(dados);
                $.each(dados.posto, function(key, value) {
                    var rota = "";
                    if (dados.consumidor.length > 0 && dados.consumidor != "@" && $('#cep').val() !== '') {
                        rota = "<span class='rota' data-lat='"+value.latitude+"' data-lng='"+value.longitude+"' data-consumidor='"+dados.consumidor+"' >Realizar Rota</span>";
                    }
                    lista += "\
                        <span class='posto-resultado' style='margin: 10px 5px' data-fantasia='"+value.nome_fantasia+"' data-lat='"+value.latitude+"' data-lng='"+value.longitude+"' >\
                            <b>"+value.nome_fantasia+"</b><br />\
                            "+value.endereco+", "+value.numero+" - "+value.bairro+"<br />\
                            "+value.cidade_posto+" - "+value.estado+" - "+value.fone+"\
                        </span><br />\
                        <span class='localizar' data-lat='"+value.latitude+"' data-lng='"+value.longitude+"' >Localizar no Mapa</span>\
                        "+rota+"\
                        <hr />\
                    ";
                });
                $("#assistencia").html(lista);
                $(".localizar-todos").show();
                addMap();
            }else{
                alert("Não foi possível encontrar uma assistência técnica com estas informações");
                $("#assistencia").html("");
                $(".localizar-todos").hide();
            }
            $("#consultar").prop({ disabled: false }).text("Consultar");
            $("#estado, #cidade").prop({ disabled: false });
        });
    }

    $(function(){
        carrega_macro_familia();
        $('#consultar').on('click', function(){
            if (carrega_cep == 0 || $('#cep').val() == '') {
                if ($('#cep').val() == '') {
                    endereco.estado   = $("#estado").val();
                    endereco.cidade   = $("#cidade").val();
                    endereco.endereco = '';
                    endereco.bairro   = '';
                }
                buscaAssistencia($('#cidade').val());
            }else{
                alert('Aguarde a pesquisa do endereço por CEP concluir e tente novamente');
            }
        });

        $('#cep').blur(function(){
            carrega_cep = 1;
            var macro_familia = $('#macro-familia').val();
            var cep = $('#cep').val();
            if (cep == '') { return false; }
            if (macro_familia == '') { alert('Selecione a linha do produto'); $('#macro-familia').focus(); return false; }
            busca_cep(cep, macro_familia);            
        });

        $('#estado').change(function(){
            var macro_familia = $('#macro-familia').val();
            var estado = $(this).val();

            $.ajax({
                url: window.location.href,
                method: 'POST',
                data: { 'ajax': 'sim', 'action': 'consulta_cidade', 'macro_familia': macro_familia, 'estado': estado }
            }).fail(function(){
            }).done(function(data){
                data = JSON.parse(data);
                $('#cidade').html(data.ok);
                if ($('#cidade_cep').val() !== '') { $('#cidade').val($('#cidade_cep').val()); }
            });
        });

        $('#macro-familia').change(function(){
            if ($(this).val() !== '') {
                $('#estado').html("<option value=''></option>");
                var array_estados = {
                    'AC': 'Acre',             'AL': 'Alagoas',             'AM': 'Amazonas',
                    'AP': 'Amapá',            'BA': 'Bahia',               'CE': 'Ceará',
                    'DF': 'Distrito Federal', 'ES': 'Espírito Santo',      'GO': 'Goiás',
                    'MA': 'Maranhão',         'MG': 'Minas Gerais',        'MS': 'Mato Grosso do Sul',
                    'MT': 'Mato Grosso',      'PA': 'Pará',                'PB': 'Paraíba',
                    'PE': 'Pernambuco',       'PI': 'Piauí',               'PR': 'Paraná',
                    'RJ': 'Rio de Janeiro',   'RN': 'Rio Grande do Norte', 'RO': 'Rondônia',
                    'RR': 'Roraima',          'RS': 'Rio Grande do Sul',   'SC': 'Santa Catarina',
                    'SE': 'Sergipe',          'SP': 'São Paulo',           'TO': 'Tocantins'
                };
                $.each(array_estados, function(i, estados){
                    $('#estado').append("<option value='"+i+"'>"+estados+"</option>");
                });
            }else{
                $('#estado').html("<option value=''>Selecione a linha</option>");
            }
        });

        $(document).on('click', '.localizar', function(){
            map.setView($(this).data('lat'), $(this).data('lng'), 25);
            map.scrollToMap();
        });

        $(document).on('click', '.rota', function(){
            router.clear();
            router.remove();

            var latlng_consumidor = $(this).data('consumidor').split('@');

            $.ajax({
                url: window.location.href,
                method: 'POST',
                data:{ 'ajax': 'sim', 'action': 'retorna_rota', 'from_latlng': latlng_consumidor[0]+','+latlng_consumidor[1], 'to_latlng': $(this).data('lat')+','+$(this).data('lng') },
                timeout: 9000
            }).fail(function(){
            }).done(function(data){
                data = JSON.parse(data);
                router.add(Polyline.decode(data.route.routes[0].geometry));
                router.render();
            });
        });        
    });
</script>
