<?php

    header('Content-Type: text/html; charset=ISO-8859-1'); 
    include "../../dbconfig.php";
    include "/var/www/includes/dbconnect-inc.php";
    include "../../funcoes.php";

    $html_titulo = "Telecontrol - Mapa da Rede Autorizada";

    $fabrica = 30;
    $login_fabrica = 30;

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

    if ($buscaAjax == "estados") {
        $uf = $_POST['uf'];
        foreach ($array_estados() as $uf => $nome) {
            if (mb_check_encoding($nome, 'UTF-8')) {
                $nome = utf8_decode($nome);
            }
            echo "<option value='$uf'>$nome</option>";
        }
        exit;
    }

    if($buscaAjax == "cidades"){
        $estado = $_POST['estado'];

        
        $sql = "SELECT UPPER(nome) AS cidade
                           FROM tbl_cidade
                          WHERE estado = '$estado'
                            AND cod_ibge IS NOT NULL
                       ORDER BY nome;";

        $res = pg_query($con,$sql);
        if (pg_num_rows ($res) > 0) {
            echo "<option value='' selected >Selecione</option>";
            for ($i=0; $i<pg_num_rows ($res); $i++ ){
                $cidade = pg_fetch_result($res,$i,'cidade');

                echo "<option value='$cidade'> $cidade</option>";
            }
        } else {
            echo "<option value='0'> Nenhuma cidade encontrada para este estado.</option>";
        }
        exit;
    }

    if($buscaAjax == "assistencia"){

        $estado     = $_POST['estado'];
        $endereco   = $_POST['endereco'];
        $cep        = $_POST['cep'];
        $cidade     = utf8_decode($_POST['cidade']);
        $pais       = 'Brasil';

        if(strlen(trim($endereco)) > 0 ){
            $endereco = explode(', ', $endereco);
            $estado = trim($endereco[3]);
            $cidade = trim($endereco[2]);
            $bairro = trim($endereco[1]);
            $endereco = trim($endereco[0]);
        }

        $latLon     = $oTCMaps->geocode($endereco, null, $bairro, $cidade, $estado, $pais);
        $lat        = $latLon['latitude'];
        $lon        = $latLon['longitude'];
        $latLonStr  = $lat.'@'.$lon;

        $order = " nome ";
        if(strlen($cep) > 2 or (!empty($lat) and !empty($lon))) {
            if(!empty($lat) and !empty($lon)) {
                $latLon     = $oTCMaps->geocode($endereco, null, $bairro, $cidade, $estado, $pais);
                $lat        = $latLon['latitude'];
                $lon        = $latLon['longitude'];
                $latLonStr  = $lat.'@'.$lon;
	        $campo = ",  (111.045 * DEGREES(ACOS(COS(RADIANS($lat)) * COS(RADIANS(tbl_posto_fabrica.latitude)) * COS(RADIANS(tbl_posto_fabrica.longitude) - RADIANS($lon)) + SIN(RADIANS($lat)) * SIN(RADIANS(tbl_posto_fabrica.latitude))))) AS distance ";
        	$order = " distance";
	    }

            if (strlen($cep) > 0) {
                $limit = "LIMIT 5";
            }else{
                $cond = "AND tbl_posto_fabrica.contato_estado = '$estado' AND upper(tbl_posto_fabrica.contato_cidade) = upper('$cidade')";
            }
        }else{
            $cond = " AND tbl_posto_fabrica.contato_estado = '$estado'
                AND upper(tbl_posto_fabrica.contato_cidade) = upper('$cidade') ";
        }


        $sql = "SELECT DISTINCT UPPER(tbl_posto.nome) AS nome, 
                            tbl_posto.posto, 
                            tbl_posto_fabrica.contato_endereco, 
                            tbl_posto_fabrica.contato_numero,
                            tbl_posto_fabrica.contato_email,
                            tbl_posto_fabrica.contato_bairro, 
                            tbl_posto_fabrica.contato_fone_comercial, 
                            tbl_posto_fabrica.contato_cidade, 
                            tbl_posto_fabrica.obs_conta, 
                            tbl_posto_fabrica.parametros_adicionais, 
                            tbl_posto_fabrica.latitude,
                            tbl_posto_fabrica.longitude
                            $campo
                      FROM tbl_posto_fabrica
                      JOIN tbl_posto        ON tbl_posto_fabrica.posto   = tbl_posto.posto
                       AND tbl_posto_fabrica.fabrica = $fabrica
                     WHERE tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
                       AND tbl_posto_fabrica.tipo_posto NOT IN(163)
                       AND tbl_posto_fabrica.posto      NOT IN(6359,4311) 
                       AND tbl_posto_fabrica.divulgar_consumidor IS TRUE
                       AND tbl_posto_fabrica.posto NOT IN(28332,635289,377569) 
                           $cond
                  ORDER BY $order $limit";
                  //echo nl2br($sql); exit;

        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0) {
            for ($i=0; $i < pg_num_rows($res); $i++ ){
                $nome                   = utf8_encode(pg_fetch_result($res,$i,'nome'));
                $endereco               = utf8_encode(pg_fetch_result($res,$i,'contato_endereco'));
                $numero                 = utf8_encode(pg_fetch_result($res,$i,'contato_numero'));
                $fone                   = utf8_encode(pg_fetch_result($res,$i,'contato_fone_comercial'));
                $bairro                 = utf8_encode(pg_fetch_result($res,$i,'contato_bairro'));
                $latitude               = pg_fetch_result($res,$i,'latitude');
                $longitude              = pg_fetch_result($res,$i,'longitude');
                $obs_conta              = pg_fetch_result($res,$i,'obs_conta');
                $contato_email          = pg_fetch_result($res,$i,'contato_email');
                $cidade_posto           = utf8_encode(pg_fetch_result($res,$i,'contato_cidade'));
                $parametros_adicionais  = pg_fetch_result($res,$i,'parametros_adicionais');

                if(strlen($parametros_adicionais) > 0){
                    $obs = json_decode($parametros_adicionais, true);
                    $obs_conta = (strlen($obs["obs_cadence"]) > 0) ? "<br /> <strong>ObservaÁ„o:</strong> ".utf8_decode($obs["obs_cadence"])."<br />" : "";
                }else{
                    $obs_conta = "";
                }

                $lista = "<span style='margin: 10px 5px'>
                            <b>$nome</b><br />
                            <b>$contato_email</b><br />
                            $endereco, $numero - $bairro<br />
                            $cidade_posto - $estado - $fone
                            $obs_conta
                       </span>";                    

                $posto[] = array('nome_fantasia' => "$nome", 'latitude' => "$latitude", 'longitude' => "$longitude", "endereco" => "$endereco", 'numero' => $numero, "bairro" => "$bairro", "cidade_posto" => "$cidade_posto", "estado" => "$estado", "fone" => "$fone", "email" => "$contato_email");
            }
            echo json_encode(array("posto" => $posto, "consumidor" => $latLonStr));
        }
        exit();
    }
?>
<!DOCTYPE html>
    <html>
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8">

            <style>
                @import url(http://fonts.googleapis.com/css?family=Montserrat:400,700);
                html, body, #wrap { height:100%; margin: 0px;padding: 0px;}
                body { color:#a2acac; font-family:'MyriadProRegular', Verdana, Geneva, sans-serif; font-size:13px; }
                p { line-height:18px; }
                p a { color:#fa8e07; text-decoration:none; }
                h2 { margin-bottom:5px; float:left; font-size:18px; color:#f98a05; font-family:'MyriadProSemibold', Verdana, Geneva, sans-serif; width:100%; }
                h1 { color:#9ba6a6; font-size:32px; font-family:'Montserrat',Verdana, Geneva, sans-serif;}

                .clear { clear:both; }
                h1 { color:#9ba6a6; font-family:'Montserrat',Verdana, Geneva, sans-serif; }
                .box_content { padding:20px;}
                #cep{
                    --background:url(http://cadence.morphy.com.br/img/bg_input.jpg) repeat-x; 
                    border:1px solid #bdc4c4; 
                    width:214px; 
                    height:23px; 
                    margin:5px 0 15px; 
                    color:#a2acac; 
                    font-family:'MyriadProRegular', Verdana, Geneva, sans-serif; 
                    padding:2px; 
                }

                #formAssistencia { float:left; padding-bottom: 20px;}
                #resultado h2 { color: #0052a2}
                #consultar{
                 border:1px solid #bdc4c4; 
                    width:107px; 
                    height:29px; 
                    margin:5px 0 15px; 
                    color:#a2acac; 
                    font-family:'MyriadProRegular', Verdana, Geneva, sans-serif; 
                    padding:2px;    
                }
                #resultado {width: 95%;} 

                div.alert {
                    background-color: #fff;
                    color: #0052a2;
                    font-weight: bold;
                    border: 1px solid;
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

                span.localizar{
                    cursor: pointer;
                    color: #0052a2;
                    text-decoration: underline;
                    display: inline;
                }
                
                span.rota{
                    cursor: pointer;
                    color: #0052a2;
                    text-decoration: underline;
                    display: inline;
                }
                .selecForm{
                    background: #0052a2;
                    color: #fff;
                    padding: 5px !important;
                    width: 99% !important;
                    border: 1px solid #c2c2c2 !important;
                    margin-bottom: 10px !important;
                    font-size: 15px;
                }
                .inputForm{    
                    padding: 3px !important;
                    width: 59% !important;
                    border: 1px solid #0052a2 !important;
                    margin-bottom: 10px !important;
                    font-size: 11px;
                    color: #222222 !important;
                }


                .btnconsultar 
                { 
                  background: #5aa6f2;
                  color: #fff !important;
                  border: 1px solid #bfbfbf;
                  padding: 5px 25px;
                  font-family: 'Poppins', sans-serif !important;
                }
                .btnconsultar:hover{
                  cursor: pointer;
                }
                .btnconsultar:active{
                  position:relative;
                  top:1px;
                }
                .box-postos{
                    background: transparent;
                    border: solid 1px #d9d9d9;
                    margin: 0 0 35px 0;
                    padding: 15px;
                    color: #535353;
                    line-height: 20px;
                }
                .box-titulo{
                    color: #535353;
                }
                #box_mapa{
                    width: 95%;

                }
            </style>
        <body>
        <div style="color: #717900;width: 100%;float: left;">
            <div style="border: 1px solid #c9c9c9;width: 30%;float: left;margin: 20PX;">
                <div class="box_content">
                    <h1 style="color:#0052a2; font-size:25px; font-family:'Montserrat', sans-serif !important; ">PESQUISE POR LOCALIDADE</h1>
                    <form id="formAssistencia">
                        <div class="alert" >Para realizar a rota atÈ um Posto Autorizado È necess·rio informar um CEP</div>
                        <select id="estado" class="selecForm" name="estado" onchange="buscaCidade(this.value)">
                            <option>Escolha o Estado</option>
                            <?php
                            foreach ($array_estados() as $uf => $nome) {
                                if (mb_check_encoding($nome, 'UTF-8')) {
                                    $nome = utf8_decode($nome);
                                }
                                echo "<option value='$uf'>$nome</option>";
                            }
                            ?>
                        </select>
                        <select name="cidade" class="selecForm" id="cidade" onchange="buscaAssistencia(this.value)">
                            <option>Escolha o Cidade</option>
                        </select>
                        <input id="cep" name="cep" class="inputForm" placeholder="Digite seu CEP" type="text" value=""/>
                        <button id="consultar" class="btnconsultar" type="button">Consultar</button> 
                    </form>

                </div>
            </div>
            <div style="width: 60%;float: right;">
                    <div id="resultado" >
                        <div id="map-brazil">

                        <ul class="brazil">
                            <li id="AC" class="br1"><a href="#acre">Acre</a></li>
                            <li id="AL" class="br2"><a href="#alagoas">Alagoas</a></li>
                            <li id="AP" class="br3"><a href="#amapa">Amap·</a></li>
                            <li id="AM" class="br4"><a href="#amazonas">Amazonas</a></li>
                            <li id="BA" class="br5"><a href="#bahia">Bahia</a></li>
                            <li id="CE" class="br6"><a href="#ceara">Cear·</a></li>
                            <li id="DF" class="br7"><a href="#distrito-federal">Distrito Federal</a></li>
                            <li id="ES" class="br8"><a href="#espirito-santo">EspÌrito Santo</a></li>
                            <li id="GO" class="br9"><a href="#goias">Goi·s</a></li>
                            <li id="MA" class="br10"><a href="#maranhao">Maranh„o</a></li>
                            <li id="MT" class="br11"><a href="#mato-grosso">Mato Grosso</a></li>
                            <li id="MS" class="br12"><a href="#mato-grosso-do-sul">Mato Grosso do Sul</a></li>
                            <li id="MG" class="br13"><a href="#minas-gerais">Minas Gerais</a></li>
                            <li id="PA" class="br14"><a href="#para">Par·</a></li>
                            <li id="PB" class="br15"><a href="#paraiba">ParaÌba</a></li>
                            <li id="PR" class="br16"><a href="#parana">Paran·</a></li>
                            <li id="PE" class="br17"><a href="#pernambuco">Pernambuco</a></li>
                            <li id="PI" class="br18"><a href="#piaui">PiauÌ</a></li>
                            <li id="RJ" class="br19"><a href="#rio-de-janeiro">Rio de Janeiro</a></li>
                            <li id="RN" class="br20"><a href="#rio-grande-do-norte">Rio Grande do Norte</a></li>
                            <li id="RS" class="br21"><a href="#rio-grande-do-sul">Rio Grande do Sul</a></li>
                            <li id="RO" class="br22"><a href="#rondonia">RondÙnia</a></li>
                            <li id="RR" class="br23"><a href="#roraima">Roraima</a></li>
                            <li id="SC" class="br24"><a href="#santa-catarina">Santa Catarina</a></li>
                            <li id="SP" class="br25"><a href="#sao-paulo">S„o Paulo</a></li>
                            <li id="SE" class="br26"><a href="#sergipe">Sergipe</a></li>
                            <li id="TO" class="br27"><a href="#tocantins">Tocantins</a></li>
                        </ul>

                    </div>
                        <div align="center" style="display: none;margin: 0 auto;top:80%;position: absolute;width: 50%;" id="loading_assistencia">
                            <img src="img/loading_img.gif" alt="">
                        </div>

                        <div id="aviso_estados" style="background-color: #fff; padding: 10px; display: none;">

                        Pensando sempre na satisfaÁ„o dos nossos consumidores, a Esmaltec est· inovando a forma de realizaÁ„o de atendimento aos clientes, onde uma equipe especializada estar· fornecendo 
                        todo o apoio necess·rio para a abertura, acompanhamento e encerramento dos atendimentos, com esse intuito informamos que as solicitaÁıes de atendimento em garantia dever„o ser feitas por 
                        contato do cliente na central de atendimentos Esmaltec, atravÈs do <strong>0800 275 1414</strong> ou <strong>0800 275 07 07</strong>.<br /><br />
                        Os postos autorizados que ser„o indicados posteriormente sÛ devem ser contatados em casos de atendimentos fora de garantia, ou seja, atendimentos particulares.
                        </div>

                        <h2 class="titulo_posto" style="display: none;">Postos para assistÍncia:</h2>
                        <div id="assistencia" style="width:100%; height:300px; overflow:auto;">
                        </div>
                    </div>


                    <div class="clear">&nbsp;</div>                
                    <div id="box_mapa">
                        <div style="text-align: right;" ><a href="#" onclick="addMap();" >Visualizar Todos os Postos</a></div>
                        <div class="alert calculo-rota" >Calculando rota aguarde...</div>
                        <div id="map_canvas" style="height: 610px; margin-top: 20px; border: 1px solid #CCCCCC;"></div>
                    </div>
            
            </div>
        </div>
        <script language="JavaScript" src="../../js/jquery-1.3.2.js"></script>
        <script src="../../plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
        <link rel="stylesheet/less" type="text/css" media="screen,projection" href="../cssmap_brazil_v4_4/cssmap-brazil/cssmap-brasil.less" />

        <script src="../cssmap_brazil_v4_4/cssmap-brazil/less-1.3.0.min.js"></script>
        <script type="text/javascript" src="../cssmap_brazil_v4_4/jquery.cssmap.js"></script>
        
        <script src='../../plugins/jquery.maskedinput_new.js'></script>
        
        
        <!-- plugin para o MapTC -->
        <link href="../../plugins/leaflet/leaflet.css" rel="stylesheet" type="text/css" />
        <script src="../../plugins/leaflet/leaflet.js" ></script>		
        <script src="../../plugins/leaflet/map.js" ></script>
        <script src="../../plugins/mapbox/geocoder.js"></script>
        <script src="../../plugins/mapbox/polyline.js"></script>


        <script language="JavaScript">
            $(window).load(function () {
                less.modifyVars({'@map_500':'transparent url(\'../cssmap-brazil/br-500-ESMALTEC_NOVO.png\') no-repeat -1010px 0'});
            });
            $(function(){
                $("#cep").mask("99999-999");
                $("#box_mapa").hide();
                /**
                * Evento para buscar o endereÁo do cep digitado
                */
                $("#consultar").click(function() {
                    busca_cep($("#cep").val(), $('#familia').val());                        
                });

                $(document).on("click", "span.localizar", function() {
                    var lat = $(this).data("lat");
                    var lng = $(this).data("lng");

                    map.setView(lat, lng, 25);
                    map.scrollToMap();

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


                $('#map-brazil').cssMap({
                    'size' :  500,
                    onClick : function(e){
                        var uf = e[0].id;
                        $('#estado').val(uf);
                        $('#estado').change();
                    },
                });


            });


            //TcMaps
            //var geocoder, latlon, c_lat, c_lon;
            var rotas      = [];
            var map, markers, router;
            var mapRend = false;
            function initialize(markersIni) {

                
                
                $("#box_mapa").show();

                if (mapRend == false) {
                    map      = new Map("map_canvas");
                    map.load();
                    markers  = new Markers(map);
                    router   = new Router(map);
                    mapRend = true;
                }
                

                markers.clear();
                markers.remove();
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
                markers.clear();
                markers.render();

                router.remove();
                router.clear();
                router.add(rota);
                router.render();

                map.scrollToMap();

            }
            
            //Fim TcMaps               


            var endereco = {};

            /**
            * FunÁ„o que faz um ajax para buscar o cep nos correios
            */
            function busca_cep(cep, familia, method) {                  
                if (cep.length > 0) {
                    $("#loading_assistencia").show();
                    if(familia != 0){
                        if (typeof method == "undefined") {
                            method = "webservice";
                        }

                        $("#consultar").prop({ disabled: true }).text("Consultando...");
                        $("#estado, #cidade").prop({ disabled: true });

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
                                $("#loading_assistencia").hide();
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
                                    $("#loading_assistencia").hide();
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


                                buscaEstado(endereco.estado, function() {
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
                        $("#loading_assistencia").hide();
                        alert('Selecione uma FamÌlia de Produtos para realizar a consulta do CEP');
                    }
                }
            }

            /**
             * FunÁ„o para retirar a acentuaÁ„o
             */
            function retiraAcentos(palavra){
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

            function buscaEstado(uf, callback) {
                if(uf != 0){
                    $("#estado").html("\
                        <option value='' >Carregando Estados...</option>\
                    ");
                    $("#estado").prop({ disabled: true });

                    $.ajax({
                        type: "POST",
                        url:  "mapa_rede.php",
                        data: "uf="+uf+"&buscaAjax=estados",
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
                        <option value='' >Selecione um Estado</option>\
                    ");
                    $("#estado").prop({ disabled: true });
                    $("#cidade").html("\
                        <option value='' >Selecione um Estado</option>\
                    ");
                    $("#cidade").prop({ disabled: true });
                }
            }

            function buscaCidade(estado, callback) {

                $("#aviso_estados").hide();

                if(estado.length > 0){
                    $("#cidade").html("\
                        <option value='' >Carregando Cidades...</option>\
                    ");

                    $("#cidade").prop({ disabled: true });
                    $.ajax({
                        type: "POST",
                        url:  "mapa_rede.php",
                        data: "estado="+estado+"&buscaAjax=cidades",
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

                $("#aviso_estados").hide();

                var estado   = $("#estado").val();
                var cep      = $("#cep").val();

                var lista = "";

                if (cidade.length > 0 && estado.length > 0) {
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
                    $("#loading_assistencia").show();

                    $.ajax({
                        async: true,
                        timeout: 60000,
                        type: "POST",
                        url:  "mapa_rede.php",
                        data: "estado="+estado+"&cidade="+cidade+"&cep="+cep+"&endereco="+end+"&buscaAjax=assistencia",
                        success: function(resposta){

                            // CE, BA, RN, MA, SE, PI, PB, AL, PE

                            var lista_estados = ['CE','BA','RN','MA','SE','PI','PB','AL','PE','AC','AM','AP','PA','RO','RR','TO','DF','GO','MS','MT','PR','RS','SC'];

                            if($.inArray(estado, lista_estados) != -1) {
                                $("#aviso_estados").show();
                            }

                            if(resposta.length > 0){

                                var dados = $.parseJSON(resposta);

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
                                    if (dados.consumidor.length > 0 && dados.consumidor != "@" && cep.length > 0) {
                                        rota = "<span class='rota' data-lat='"+value.latitude+"' data-lng='"+value.longitude+"' data-consumidor='"+dados.consumidor+"' >Realizar Rota</span>";
                                    }
                                    $(".titulo_posto").show()

                                    lista += "\
                                    <div class='box-postos'>\
                                        <span class='posto-resultado' style='margin: 10px 5px' data-fantasia='"+value.nome_fantasia+"' data-lat='"+value.latitude+"' data-lng='"+value.longitude+"' >\
                                            <b class='box-titulo'>"+value.nome_fantasia+"</b><br />\
                                            E-mail: "+value.email+"<br />\
                                            "+value.endereco+", "+value.numero+" - "+value.bairro+"<br />\
                                            "+value.cidade_posto+" - "+value.estado+" - "+value.fone+"\
                                        </span><br />\
                                        <span class='localizar' data-lat='"+value.latitude+"' data-lng='"+value.longitude+"' >Localizar no Mapa</span>\
                                        "+rota+"\
                                    </div>\
                                    ";
                                });

                                $("#assistencia").html(lista);
                                addMap();

                                endereco = {};
                                $("#cep").val("");
                                $("#loading_assistencia").hide();
                            }else{
                                alert("Nehum Posto Autorizado encontrado");
                                $("#assistencia").html("");
                                $("#loading_assistencia").hide();
                                $("#box_mapa").hide();
                                endereco = {};
                                $("#cep").val("");
                            }
                        }
                    }).fail(function(r) {
                        alert("Erro ao buscar Postos Autorizado, tempo limite esgotado");
                        $("#assistencia").html("");
                        $("#box_mapa").hide();
                        $("#loading_assistencia").hide();
                        endereco = {};
                        $("#cep").val("");
                    });
                }
            }
        </script>
        </body>
    </html>
