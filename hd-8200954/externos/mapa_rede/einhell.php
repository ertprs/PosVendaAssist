<?
    include "../../dbconfig.php";
    include "/var/www/includes/dbconnect-inc.php";

    // if ($_GET["ajax_rota"]) {
    //     include "../../funcoes.php";

    //     $origem  = $_GET["origem"];
    //     $destino = $_GET["destino"];

    //     $rota = googleMapsGeraRota($origem, $destino);

    //     exit(json_encode($rota));
    // }

    $html_titulo = "Telecontrol - Mapa da Rede Autorizada";

    $fabrica = 160;
    $login_fabrica = 160;

    use Posvenda\TcMaps;
    $oTCMaps = new TcMaps($login_fabrica,$con);

    if ($_GET["ajax_rota"]) {

        $origem  = $_GET["origem"];
        $destino = $_GET["destino"];

        $resposta = $oTCMaps->route($origem,$destino);

        echo json_encode($resposta);
        exit;
    }

    $buscaAjax = $_POST['buscaAjax'];

    $estados = "'AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'";

    if($buscaAjax == "estados"){

        $familia = $_POST['familia'];
        $sql = "
            SELECT DISTINCT UPPER(tbl_posto_fabrica.contato_estado) AS estado
            FROM tbl_produto
            JOIN tbl_posto_linha   ON tbl_posto_linha.linha     = tbl_produto.linha
            JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto_linha.posto AND tbl_posto_fabrica.fabrica  = $fabrica
            WHERE tbl_produto.familia = $familia
            AND   tbl_produto.fabrica_i = $fabrica
            AND   tbl_produto.ativo
            AND tbl_posto_linha.linha != 901
            AND tbl_posto_fabrica.credenciamento <> 'DESCREDENCIADO'
            AND tbl_posto_fabrica.contato_estado IS NOT NULL
            ORDER BY estado;";

        $res = pg_exec($con,$sql);
        if (pg_numrows ($res) > 0) {
            echo "<option value='' selected >Selecione</option>";
            for ($i=0; $i<pg_numrows ($res); $i++ ){
                $estado = pg_result($res,$i,'estado');

                echo "<option value='$estado'> $estado</option>";
            }
        }else{
            echo "<option value='0'> Nenhum estado encontrado para esta família.</option>";
        }
        exit;
    }

    if($buscaAjax == "cidades"){
        $estado = $_POST['estado'];
        $familia = $_POST['familia'];

        $sql = "
            SELECT DISTINCT UPPER(tbl_posto_fabrica.contato_cidade) AS cidade, tbl_posto_fabrica.contato_cidade
            FROM tbl_posto_fabrica
            INNER JOIN tbl_linha ON tbl_linha.fabrica = $login_fabrica AND tbl_linha.linha != 901
            INNER JOIN tbl_posto_linha ON tbl_posto_linha.posto = tbl_posto_fabrica.posto AND tbl_posto_linha.linha = tbl_linha.linha
            INNER JOIN tbl_produto ON tbl_produto.linha = tbl_linha.linha AND tbl_produto.fabrica_i = $login_fabrica AND tbl_produto.familia = $familia
            WHERE tbl_posto_fabrica.fabrica = $login_fabrica
            AND tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
            AND tbl_posto_fabrica.contato_estado = '$estado'
            ORDER BY tbl_posto_fabrica.contato_cidade ASC";
        $res = pg_exec($con,$sql);
        if (pg_numrows ($res) > 0) {
            echo "<option value='' selected >Selecione</option>";
            for ($i=0; $i<pg_numrows ($res); $i++ ){
                $cidade = pg_result($res,$i,'cidade');

                echo "<option value='$cidade'> $cidade</option>";
            }
        }else{
            echo "<option value='0'> Nenhuma cidade encontrada para este estado.</option>";
        }
        exit;
    }

    // function getLatLonConsumidor($address,$cep){

    //     $geocode = file_get_contents('http://maps.googleapis.com/maps/api/geocode/json?address='.$address.',brasil&sensor=false');

    //     $output = json_decode($geocode);
    //     $resultado = (array) $output;
    //     foreach ($resultado as $key => $value){
    //         $resultado[$key] = (array)$value;
    //         foreach ($value as $key2 => $value2){
    //             $value[$key2] = (array)$value2;
    //         }
    //         $resultado[$key] = (array)$value;
    //     }
    //     $cidade = $resultado[results][0][address_components][3]->short_name;
    //     if(strlen(trim($cep))>0 and $cep != "undefined"){
    //         if(strpos($address,$cidade) ===false) {
    //             $geocode = file_get_contents('http://maps.googleapis.com/maps/api/geocode/json?address='.$cep.',brasil&sensor=false');
    //             $output = json_decode($geocode);
    //         }
    //     }

    //     $lat = $output->results[0]->geometry->location->lat;
    //     $lon = $output->results[0]->geometry->location->lng;

    //     $latLon = $lat."@".$lon;
    //     return $latLon;

    // }

    if($buscaAjax == "assistencia"){

        $estado     = $_POST['estado'];
        $endereco   = $_POST['endereco'];
        $cep        = str_replace("-","", $_POST['cep']);
        $cidade     = utf8_decode($_POST['cidade']);
        $familia    = $_POST['familia'];
        if (strlen($familia) > 0 and $familia != 0) {
            $join_familia =" AND tbl_produto.familia       = $familia";
        }
        $pais = 'Brasil';

        if(strlen(trim($endereco)) > 0 ){

            $endereco = explode(', ', $endereco);
            $estado = trim($endereco[3]);
            $cidade = trim($endereco[2]);
            $bairro = trim($endereco[1]);
            $endereco = trim($endereco[0]);
            $latLon = $oTCMaps->geocode($endereco, null, $bairro, $cidade, $estado, $pais);
            $lat = $latLon['latitude'];
            $lon = $latLon['longitude'];
        }
        //$latLon = getLatLonConsumidor($endereco, $cep);
        
        if(empty($lat) || empty($lon)){
            $lat_lon = $oTCMaps->tcGeocodePostCode($cep);
            $lat = $lat_lon['latitude'];
            $lon = $lat_lon['longitude'];
        }
        
        // $parte = explode('@', $latLon);
        // $lat = $parte[0];
        // $lon = $parte[1];
        $latLonStr = $lat.'@'.$lon;

        $order = " nome ";

        if(strlen($cep) > 2 or (!empty($lat) and !empty($lon))) {
            $campo = ",(
                        111.045 * DEGREES(
                            ACOS(
                                COS(RADIANS({$lat}))
                                * COS(RADIANS(tbl_posto_fabrica.latitude))
                                * COS(RADIANS(tbl_posto_fabrica.longitude) - RADIANS({$lon}))
                                + SIN(RADIANS({$lat}))
                                * SIN(RADIANS(tbl_posto_fabrica.latitude))
                            )
                        )
                    ) AS distance";
            $order = 'distance';
            $limit = 'limit 8';
            // if(!empty($lat) and !empty($lon)) {
            //     //$latLon = getLatLonConsumidor($endereco, $cep);
            //     //$latLon = $oTCMaps->geocode(null, null, null, $cidade, $estado, $pais);
            //     //echo $endereco." == endereco ".$bairro." == bairro ".$cidade." == cidade ".$estado." == estado".$pais." == pais";exit;
            //     $latLon = $oTCMaps->geocode($endereco, null, $bairro, $cidade, $estado, $pais);

            //     //$parte = explode('@', $latLon);
            //     // $lat = $parte[0];
            //     // $lon = $parte[1];
            //     $lat = $latLon['latitude'];
            //     $lon = $latLon['longitude'];
            //     $latLonStr = $lat.'@'.$lon;
            //     $lat = substr(trim($lat),0,7);
            //     $lon = substr(trim($lon),0,7);

            //     $campo = ",  (111.045 * DEGREES(ACOS(COS(RADIANS($lat)) * COS(RADIANS(tbl_posto_fabrica.latitude)) * COS(RADIANS(tbl_posto_fabrica.longitude) - RADIANS($lon)) + SIN(RADIANS($lat)) * SIN(RADIANS(tbl_posto_fabrica.latitude))))) AS distance ";
            //     $order = " distance";
            // }

            // if (strlen($cep) > 0) {
            //     $limit = "LIMIT 8";
            // }else{
            //     $cond = "AND tbl_posto_fabrica.contato_estado = '$estado' AND upper(tbl_posto_fabrica.contato_cidade) = upper('$cidade')";
            // }
        }else{
            $cond = " AND tbl_posto_fabrica.contato_estado = '$estado'
                AND upper(tbl_posto_fabrica.contato_cidade) = upper('$cidade') ";
            $order = " nome ";
        }

        $sql = "
            SELECT DISTINCT UPPER(tbl_posto.nome) AS nome, tbl_posto_fabrica.nome_fantasia, tbl_posto.posto, tbl_posto_fabrica.contato_endereco, tbl_posto_fabrica.contato_numero,
                   tbl_posto_fabrica.contato_bairro, tbl_posto_fabrica.contato_fone_comercial, tbl_posto_fabrica.contato_cidade, tbl_posto_fabrica.obs_conta, tbl_posto_fabrica.parametros_adicionais,
                        tbl_posto_fabrica.latitude,
                        tbl_posto_fabrica.longitude
                    $campo

              FROM tbl_posto_fabrica
              JOIN tbl_posto        ON tbl_posto_fabrica.posto   = tbl_posto.posto
                                   AND tbl_posto_fabrica.fabrica = $fabrica
              JOIN tbl_posto_linha  ON tbl_posto_linha.posto     = tbl_posto_fabrica.posto
                                   AND tbl_posto_linha.ativo    IS TRUE
              JOIN tbl_linha        ON tbl_linha.linha           = tbl_posto_linha.linha
                                   AND tbl_linha.fabrica         = $fabrica
                                   AND tbl_linha.ativo          IS TRUE
              JOIN tbl_produto      ON tbl_produto.linha         = tbl_linha.linha
                                   $join_familia
             WHERE tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
         AND tbl_posto_fabrica.tipo_posto NOT IN(163)
         AND tbl_posto_fabrica.divulgar_consumidor IS TRUE
            AND tbl_posto_linha.linha != 901
               AND tbl_posto_fabrica.posto      NOT IN(6359,4311)
               $cond
             ORDER BY $order $limit";

        $res = pg_exec($con,$sql);
        //echo $sql;

        if (pg_numrows ($res) > 0) {
            for ($i=0; $i < pg_numrows ($res); $i++ ){
                $nome     = utf8_encode(pg_result($res,$i,'nome'));
                $nome_fantasia = utf8_encode(pg_result($res,$i,'nome_fantasia'));
                $endereco = utf8_encode(pg_result($res,$i,'contato_endereco'));
                $numero   = utf8_encode(pg_result($res,$i,'contato_numero'));
                $fone     = utf8_encode(pg_result($res,$i,'contato_fone_comercial'));
                $bairro   = utf8_encode(pg_result($res,$i,'contato_bairro'));
                $latitude   = pg_result($res,$i,'latitude');
                $longitude   = pg_result($res,$i,'longitude');
                $obs_conta   = pg_result($res,$i,'obs_conta');
                $cidade_posto = utf8_encode(pg_result($res,$i,'contato_cidade'));

                // $obs_conta = (strlen($obs_conta) > 0) ? "<br /> <strong>Observação:</strong> <br /> $obs_conta <br />" : "";

                $parametros_adicionais   = pg_result($res,$i,'parametros_adicionais');

                if(strlen($parametros_adicionais) > 0){
                    $obs = json_decode($parametros_adicionais, true);
                    $obs_conta = (strlen($obs["obs_cadence"]) > 0) ? str_replace("\\", "", $obs["obs_cadence"]) : "";
                }else{
                    $obs_conta = "N/I";
                }

                $lista = "<span style='margin: 10px 5px'>
                            <b>$nome</b><br />
                            <b>$nome_fantasia</b><br />
                            $endereco, $numero - $bairro<br />
                            $cidade_posto - $estado - $fone
                            $obs_conta
                       </span>";

                    $posto[] = array('nome_fantasia' => "$nome_fantasia", "nome" => $nome, 'latitude' => "$latitude", 'longitude' => "$longitude", "endereco" => "$endereco", 'numero' => $numero, "bairro" => "$bairro", "cidade_posto" => "$cidade_posto", "estado" => "$estado", "fone" => "$fone", "obs" => "$obs_conta");

            }

            echo json_encode(array("posto" => $posto, "consumidor" => $latLonStr));

        }
        exit;
    }

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
    <html>
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
            <style>
                html, body, #wrap { height:100%; }
                body { background:#ffffff; color:black; font-family:'MyriadProRegular', Verdana, Geneva, sans-serif; font-size:13px; }
                p { line-height:18px; }
                p a { color:#fa8e07; text-decoration:none; }
                h2 { margin-bottom:5px; float:left; font-size:18px; color:white; font-family:'MyriadProSemibold', Verdana, Geneva, sans-serif; width:100%; }
                h1 { color:#9ba6a6; font-size:32px; font-family:'MyriadProBold',Verdana, Geneva, sans-serif; padding:20px 20px 10px; }
                .box_content { padding:0 20px 20px; width:789px; }

                .clear { clear:both; }
                h1 { color:white; font-size:32px; font-family:'MyriadProBold',Verdana, Geneva, sans-serif;  padding: 10px; margin: 0;}
                .box_content { margin: 0 auto; padding:0 20px 20px; width:789px; }
                #cep{
                    border:1px solid #bdc4c4;
                    width:214px;
                    height:23px;
                    margin:5px 0 15px;
                    color:#a2acac;
                    font-family:'MyriadProRegular', Verdana, Geneva, sans-serif;
                    padding:2px;
                }

                .text_os{
                    border:1px solid #bdc4c4;
                    width:214px;
                    height:23px;
                    margin:5px 0 15px;
                    color:black;
                    font-family:'MyriadProRegular', Verdana, Geneva, sans-serif;
                    padding:2px;
                }

                #reCaptcha {
                    color:#a2acac;
                    margin-top: 5px;
                    margin-bottom: 5px;
                }

                #formAssistencia { float:left; width:336px; margin:25px 40px 0 0; }
                #formAssistencia label { width:100%; font-size:18px; color: black; font-family:'MyriadProSemibold', Verdana, Geneva, sans-serif;font-weight: bolder; }
                #formAssistencia select { width:334px; height:40px; margin:5px 0 15px; color:; font-family:'MyriadProRegular', Verdana, Geneva, sans-serif; padding:2px; }
                #formOS { float:left; width:336px; margin:25px 40px 0 0; }
                #formOS label { width:100%; font-size:18px; color: #333; font-family:'MyriadProSemibold', Verdana, Geneva, sans-serif;font-weight: bolder; }
                #formOS select { width:334px; height:40px; margin:5px 0 15px; color:; font-family:'MyriadProRegular', Verdana, Geneva, sans-serif; padding:2px; }
                #resultado { float:left; width:410px; margin-top:25px; }
                #resultado h2 { color:black }
                #consultar{
                    border-radius: 4px;
                    cursor: pointer;
                    border: solid #e43416 2px;
                    width:107px;
                    height:29px;
                    margin:5px 0 15px;
                    color:white;
                    font-family:'MyriadProRegular', Verdana, Geneva, sans-serif;
                    padding:2px;
                    background-color: #e43416;
                }

                #consultar_os{
                    border-radius: 4px;
                    cursor: pointer;
                    border: solid #e43416 2px;
                    width:107px;
                    height:29px;
                    margin:5px 0 15px;
                    color:white;
                    font-family:'MyriadProRegular', Verdana, Geneva, sans-serif;
                    padding:2px;
                    background-color: #e43416;
                }
                #resultado span { margin:10px 0;
                    display: block;
                    /*color: #FCFCFC;*/
                }

                div.alert {
                    background-color: #d9edf7;
                    color: #31708f;
                    font-weight: bold;
                    border: 1px solid #bce8f1;
                    padding: 15px;
                    border-radius: 4px;
                    font-size: 12px;
                    margin-bottom: 20px;
                }

                div.calculo-rota {
                    margin-top: 20px;
                    display: none;
                }

                .btn-instrucao-rota {
                    position: absolute;
                    margin-top: 10px;
                    margin-left: 10px;
                    cursor: pointer;
                    z-index: 1;
                }

                div.instrucao-rota {
                    position: absolute;
                    display: none;
                    z-index: 2;
                    background-color: #fff;
                    overflow-y: scroll;
                }

                .btn-fechar-instrucao {
                    float: right;
                    display: block;
                    margin-top: -30px;
                }

                span.localizar, span.rota {
                    cursor: pointer;
                    color: white;
                    width: 180px;
                    height: 25px;
                    background-color: #e43416;
                    font-weight: bolder;
                    text-align: center;
                    border-radius: 4px;
                    padding-top:8px;
                }

                span.localizar-todos {
                    cursor: pointer;
                    color: white;
                    display: inline;
                    width: 180px;
                    height: 30px !important;
                    background-color: #e43416;
                    font-weight: bolder;
                    text-align: center;
                    border-radius: 4px;
                    padding:8px;
                }

                .posto-resultado{
                    text-transform: uppercase;
                }

            </style>
            <link rel="stylesheet" type="text/css" href="../einhell/css/einhell.css" />
        </head>
        <body>

            <div class="box_content">
                <form id="formAssistencia">
                    <label for="familia">Escolha a família de produtos:</label>
                    <select id="familia" name="familia" onchange="buscaEstado(this.value)">
                    <?php
                        $sql = "SELECT
                                DISTINCT tbl_familia.familia,
                                tbl_familia.descricao
                            FROM tbl_familia
                            JOIN tbl_produto USING(familia)
                            WHERE tbl_familia.fabrica = $login_fabrica
                            AND tbl_familia.ativo IS TRUE
                            ORDER BY descricao ASC;";
                        $res = pg_exec($con,$sql);

                        if(pg_numrows($res) == 0){
                            echo "<option selected='selected'> Nenhuma família encontrada</option>";
                        }else{
                            echo "<option value='0' selected='selected'>Selecione</option>";
                            for ($i=0; $i<pg_numrows ($res); $i++ ){
                                $codigo = pg_result($res,$i,'familia');
                                $descricao = pg_result($res,$i,'descricao');

                                echo "<option value='$codigo'>$descricao</option>";
                            }
                        }
                    ?>
                    </select>

                    <div class="alert" >Para realizar a rota até um Posto Autorizado é necessário informar um CEP</div>
                    <label for="cep">Digite seu CEP:</label>
                    <br>
                    <input id="cep" name="cep" type="text" value=""/>
                    <!--<input type="button" class='btn' value="Consultar" ONCLICK="javascript: document.frm_lancamento.submit();" ALT="Consultar CEP" border='0' >-->
                     <button id="consultar" type="button">Consultar</button>
                    <br>
                    <br>
                    <label for="estado">Escolha o Estado:</label>
                    <select id="estado" name="estado" onchange="buscaCidade(this.value)">
                        <option></option>
                    </select>
                    <label for='cidade'>Escolha o Cidade:</label>
                    <select name="cidade" id="cidade" onchange="buscaAssistencia(this.value)">
                        <option></option>
                    </select>
                </form>

                <div id="resultado" >
                    <h2>Postos para assistência:</h2>
                    <div id="assistencia" style="width:100%; height:300px; overflow:auto;"></div>
                </div>


                <div class="clear">&nbsp;</div>
                <div id="box_mapa">
                    <div style="text-align: right;" ><span class="localizar-todos" onclick="markers.focus(true);" >Visualizar Todos os Postos</span></div>
                    <div class="alert calculo-rota" >Calculando rota aguarde...</div>
                    <div id="map_canvas" style="height: 610px; margin-top: 20px; border: 1px solid #CCCCCC;"></div>
                </div>
            </div>
        <script language="JavaScript" src="../../js/jquery-1.3.2.js"></script>
        <script src="../../plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
        <script src='../../plugins/jquery.maskedinput_new.js'></script>

        <!-- plugin para o MapTC -->
        <link href="../../plugins/leaflet/leaflet.css" rel="stylesheet" type="text/css" />
        <script src="../../plugins/leaflet/leaflet.js" ></script>
        <script src="../../plugins/leaflet/map.js?v=<?php echo date('YmdHis');?>" ></script>
        <script>
            var MapaTelecontrol = Map;
        </script>
        <script src="../../plugins/mapbox/geocoder.js"></script>
        <script src="../../plugins/mapbox/polyline.js"></script>


            <script language="JavaScript">
                $(function(){
                    $("#cep").mask("99999-999");
                    $("#box_mapa").hide();
                    /**
                    * Evento para buscar o endereço do cep digitado
                    */
                    $("#consultar").click(function() {
                        busca_cep($("#cep").val(), $('#familia').val());
                    });

                    $(document).on("click", "span.localizar", function() {
                        var lat = $(this).data("lat");
                        var lng = $(this).data("lng");

                        MapaTele.setView(lat, lng, 25);
                        MapaTele.scrollToMap();

                    });

                    $(document).on("click", "span.rota", function() {
                        var lat = $(this).data("lat");
                        var lng = $(this).data("lng");
                        var c   = $(this).data("consumidor");

                        rota(lat, lng, c);

                    });

                    $(document).on("click", ".btn-instrucao-rota", function() {
                        $("div.instrucao-rota").show();
                        $("div.instrucao-rota").width($("#map_canvas").width());
                        $("div.instrucao-rota").height($("#map_canvas").height());
                    });

                    $(document).on("click", ".btn-fechar-instrucao", function() {
                        $("div.instrucao-rota").hide();
                    });

                    $("#cep").on("change", function() {
                        var v = $(this) .val();

                        if (v.length == 0) {
                            endereco = {};
                        }
                    });
                });


                //TcMaps
                //var geocoder, latlon, c_lat, c_lon;
                var rotas      = [];
                var MapaTele, markers, router;
                var mapRend = false;
                function initialize(markersIni) {



                    $("#box_mapa").show();

                    if (mapRend == false) {
                        MapaTele      = new MapaTelecontrol("map_canvas");
                        MapaTele.load();
                        markers  = new Markers(MapaTele);
                        router   = new Router(MapaTele);
                        mapRend = true;
                    }


                    markersIni.forEach(function(v, k) {
                        markers.add(v.latitude,v.longitude,'red',v.title);
                    });

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

                function rota (lat, lng, c) {
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
                                markers.add(c[0],c[1],'blue','Cliente');
                                geraMapaRota(rotas[p]);
                            }
                        });
                    } else {
                        geraMapaRota(rotas[p]);
                    }
                }

                function geraMapaRota(rota) {
                    //console.log(rota);
                    markers.clear();
                    markers.render();

                    router.remove();
                    router.clear();
                    router.add(rota);
                    router.render();

                    MapaTele.scrollToMap();

                }

                //Fim TcMaps


                var endereco = {};

                /**
                * Função que faz um ajax para buscar o cep nos correios
                */
                function busca_cep(cep, familia, method) {
                    if (cep.length > 0) {
                        if(familia != 0){
                            if (typeof method == "undefined") {
                                method = "webservice";
                            }

                            $("#consultar").prop({ disabled: true }).text("Aguarde...");
                            $("#estado, #cidade").prop({ disabled: true });

                            endereco = {};
                            rotas = [];

                            $.ajax({
                                async: true,
                                timeout: 60000,
                                url: "../../admin/ajax_cep.php",
                                type: "get",
                                data: { method: method, cep: cep }
                            }).fail(function(r) {
                                if (method == "webservice") {
                                    busca_cep(cep, familia, "database");
                                } else {
                                    alert("Erro ao consultar CEP, tempo limite esgotado");
                                    $("#consultar").prop({ disabled: false }).text("Consultar");
                                    $("#estado, #cidade").prop({ disabled: false });
                                }
                            }).done(function(r) {
                                data = r.split(";");

                                if (data[0] != "ok" && method == "webservice") {
                                    busca_cep(cep, familia, "database");
                                } else if (data[0] != "ok") {
                                    if (data[0].length > 0) {
                                        alert(data[0]);
                                    } else {
                                        alert("Erro ao buscar CEP");
                                    }
                                } else {
                                    var estado, cidade, end, bairro;

                                    if (data[4] != undefined) estado = data[4];
                                    if (data[3] != undefined) cidade = data[3];
                                    if (data[1] != undefined && data[1].length > 0) end = data[1];
                                    if (data[2] != undefined && data[2].length > 0) bairro = data[2];

                                    endereco.estado   = estado;
                                    endereco.cidade   = cidade;
                                    endereco.endereco = end;
                                    endereco.bairro   = bairro;

                                    buscaEstado(familia, function() {
                                        $("#estado").val(estado);

                                        buscaCidade(estado, function() {
                                            $("#cidade").val(retiraAcentos(cidade).toUpperCase());

                                            buscaAssistencia(retiraAcentos(cidade).toUpperCase());
                                        });
                                    });
                                }

                                $("#consultar").prop({ disabled: false }).text("Consultar");
                            });
                        } else {
                            alert('Selecione uma Família de Produtos para realizar a consulta do CEP');
                        }
                    }
                }

                /**
                 * Função para retirar a acentuação
                 */
                function retiraAcentos(palavra){
                    var com_acento = 'áàãâäéèêëíìîïóòõôöúùûüçÁÀÃÂÄÉÈÊËÍÌÎÏÓÒÕÖÔÚÙÛÜÇ';
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

                function buscaEstado(familia, callback) {
                    if(familia != 0){
                        $("#estado").html("\
                            <option value='' >Carregando Estados...</option>\
                        ");
                        $("#estado").prop({ disabled: true });

                        $.ajax({
                            type: "POST",
                            url:  "einhell.php",
                            data: "familia="+familia+"&buscaAjax=estados",
                            async: true,
                            timeout: 60000
                        }).fail(function(r) {
                            alert("Erro ao buscar estados, tempo limite esgotado");
                            $("#estado").html("<option value='' ></option>");
                            $("#estado").prop({ disabled: false });
                        }).done(function(r) {
                            $("#estado").html(r);
                            $("#estado").prop({ disabled: false });
                            $("#cidade").html("<option value='' >Selecione um Estado</option>");
                            $("#cidade").prop({ disabled: true });
                            $("#assistencia").html("");
                            $("#box_mapa").hide();

                            if (typeof callback != "undefined") {
                                callback();
                            }
                        });
                    } else {
                        $("#estado").html("\
                            <option value='' >Selecione uma Família</option>\
                        ");
                        $("#estado").prop({ disabled: true });
                        $("#cidade").html("\
                            <option value='' >Selecione um Estado</option>\
                        ");
                        $("#cidade").prop({ disabled: true });
                    }
                }

                function buscaCidade(estado, callback) {
                    var familia = $("#familia").val();

                    if(estado.length > 0 && familia.length > 0){
                        $("#cidade").html("\
                            <option value='' >Carregando Cidades...</option>\
                        ");

                        $("#cidade").prop({ disabled: true });
                        $.ajax({
                            type: "POST",
                            url:  "einhell.php",
                            data: "estado="+estado+"&familia="+familia+"&buscaAjax=cidades",
                            async: true,
                            timeout: 60000
                        }).fail(function(r) {
                            alert("Erro ao buscar cidades, tempo limite esgotado");
                            $("#cidade").html("<option value='' ></option>");
                            $("#cidade").prop({ disabled: false });
                        }).done(function(r) {
                            $("#cidade").html(r);
                            $("#cidade").prop({ disabled: false });
                            $("#assistencia").html("");
                            $("#box_mapa").hide();

                            if (typeof callback != "undefined") {
                                callback();
                            }
                        });
                    } else {
                        $("#cidade").html("\
                            <option value='' >Selecione um Estado</option>\
                        ");
                        $("#cidade").prop({ disabled: true });
                    }
                }

                function buscaAssistencia(cidade) {
                    var estado   = $("#estado").val();
                    var familia  = $("#familia").val();
                    var cep      = $("#cep").val();

                    var lista = "";

                    if(cidade.length > 0 && estado.length > 0 && familia.length > 0){
                        if (cep.length == 0) {
                            endereco = {};
                        }

                        var end = [];

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

                        console.log(end);

                        $("#assistencia").html("Pesquisando...");

                        $.ajax({
                            async: true,
                            timeout: 60000,
                            type: "POST",
                            url:  "einhell.php",
                            data: "estado="+estado+"&cidade="+cidade+"&cep="+cep+"&endereco="+end+"&familia="+familia+"&buscaAjax=assistencia",
                            success: function(resposta){
                                if(resposta.length > 0){
                                    var dados = $.parseJSON(resposta);
                                    console.log(dados);
                                    $.each(dados.posto, function(key, value) {
                                        var rota = "";
                                        if (markers != null) {
                                            markers.remove();
                                            markers.clear();
                                        }
                                        if (router != null) {
                                            router.remove();
                                            router.clear();
                                        }
                                        if (dados.consumidor.length > 0 && dados.consumidor != "@") {
                                            rota = "<span class='rota' data-lat='"+value.latitude+"' data-lng='"+value.longitude+"' data-consumidor='"+dados.consumidor+"' >Realizar Rota</span>";
                                        }

                                        lista += "\
                                            <span class='posto-resultado' style='margin: 10px 5px' data-fantasia='"+value.nome_fantasia+"' data-lat='"+value.latitude+"' data-lng='"+value.longitude+"' >\
                                                <b style='color: black'>"+value.nome_fantasia+"</b><br />\
                                                <b>"+value.nome+"</b><br />\
                                                "+value.endereco+", "+value.numero+" - "+value.bairro+"<br />\
                                                "+value.cidade_posto+" - "+value.estado+" - "+value.fone+" <br />\
                                                <strong>Observação:</strong> <br /> "+value.obs+"\
                                            </span>\
                                            <span class='localizar' data-lat='"+value.latitude+"' data-lng='"+value.longitude+"' >Localizar no Mapa</span>\
                                            "+rota+"\
                                            <hr />\
                                        ";
                                    });

                                    $("#assistencia").html(lista);
                                    addMap();

                                    endereco = {};
                                    $("#cep").val("");
                                }else{
                                    alert("Nehum Posto Autorizado encontrado");
                                    $("#assistencia").html("");
                                    $("#box_mapa").hide();
                                    endereco = {};
                                    $("#cep").val("");
                                }
                            }
                        }).fail(function(r) {
                            alert("Erro ao buscar Postos Autorizado, tempo limite esgotado");
                            $("#assistencia").html("");
                            $("#box_mapa").hide();
                            endereco = {};
                            $("#cep").val("");
                        });
                    }
                }
            </script>
            <!-- HD - 4105971 -->
            <script src="https://www.google.com/recaptcha/api.js?hl=pt-BR&onload=showRecaptcha&render=explicit"></script>
            
            <script type="text/javascript">
                var showRecaptcha= function() {
                    grecaptcha.render('reCaptcha', {
                      'sitekey' : '6LckVVIUAAAAAEQpRdiIbRSbs_ePTTrQY0L4959J'
                    });
                };

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
                                            "<li class='list-group-item panel-heading>"+
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

                        $("#lista_posto").html("");
                        $("#lista_posto").html(resultado);
                        $("#lista_posto").show();
                    });
                };

                function pegaIp(){
                    var ip = '';
                    $.ajax({
                        url : ".././institucional/pega_ip.php",
                        async:false,
                        dataType : "json",
                        success : function(data){
                            ip = data.ip;
                       }
                    });
                    return ip;
                }

                $(document).ready( function() {
                    $('#consultar_os').on('click', function(){
                        $('#consultar_os').text('Aguarde...');
                        $('#consultar_os').prop('disabled', true);
                        var msgErro = [];
                        var data = {};
                        var inputOS = $('#numero_os');
                        var inputCpfCnpj = $('#cpf_cnpj');
                        var ip = pegaIp();
                        var url_local = window.location.href;
                        data.userIpAddress = ip;
                        data.os = inputOS.val();
                        data.cpf_cnpj = inputCpfCnpj.val();
                        data.recaptcha_response_field = grecaptcha.getResponse();
                        
                        if (url_local.match(/devel/)) {
                            data.token_fabrica = "311daa1ab7b2f931401f25a647c6ecc80691ac8b";
                        } else {
                            data.token_fabrica = "fb437fd071df4534afec1a4cae5df3159af4863e";
                        }


                        if (data.os.length == 0) {
                            msgErro.push("Informe o número da ordem de serviço");
                        }

                        if (data.recaptcha_response_field.length == 0){
                            msgErro.push("Preencha o ReCaptcha");
                        }

                        if (data.cpf_cnpj.length == 0) {
                            msgErro.push("Informe o número do CPF/CNPJ");
                        }
                        
                        if( data.cpf_cnpj.length < 0 &&
                            !data.cpf_cnpj.match(/^[0-9]{3}\.[0-9]{3}\.[0-9]{3}-[0-9]{2}$/) &&
                            !data.cpf_cnpj.match(/^[0-9]{2}\.[0-9]{3}\.[0-9]{3}\/[0-9]{4}-[0-9]{2}$/)){
                            msgErro.push('CPF/CNPJ Inválido');
                        }
                        if(msgErro.length > 0){
                            $("#msgErro").html(msgErro.join("<br />")).show();
                            $('#consultar_os').text('Consultar');
                            $('#consultar_os').prop('disabled', false);
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
                                    $('#consultar_os').text('Consultar');
                                    $('#consultar_os').prop('disabled', false);
                                    grecaptcha.reset();
                                }
                            });
                        }
                    })
                });
            </script>

            <div class="box_content">
                <form id="formOS">
                    <div class="alert" id="msgErro"><b>Consulta de Ordem de Serviço</b></div>
                    <label for="numero_os">N. da Ordem de Serviço</label>
                    <br>
                    <input class='text_os' id="numero_os" name="numero_os" type="text" value=""/>
                    <br>
                    <label for="numero_os">CPF / CNPJ</label>
                    <br>
                    <input class='text_os' id="cpf_cnpj" name="cpf_cnpj" type="text" value=""/>
                    <div class="row">
                        <div id="reCaptcha"></div>
                    </div>
                    <button id="consultar_os" type="button">Consultar</button>
                <div id="lista_posto"></div>
                </form>
            </div>
            <br>
        </body>
    </html>
