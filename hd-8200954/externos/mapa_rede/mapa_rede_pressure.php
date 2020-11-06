<?
    include "../../dbconfig.php";
    include "/var/www/includes/dbconnect-inc.php";

    $html_titulo = "Telecontrol - Mapa da Rede Autorizada";

    $fabrica = 131;
    $login_fabrica = 131;

    use Posvenda\TcMaps;
    $oTCMaps = new TcMaps($login_fabrica, $con);

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

        $linha = filter_input(INPUT_POST, 'linha');

        $sql = "
            SELECT DISTINCT UPPER(tbl_posto_fabrica.contato_estado) AS estado
            FROM tbl_posto_linha
            INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto_linha.posto AND tbl_posto_fabrica.fabrica  = :fabrica
            WHERE tbl_posto_linha.linha = :linha
            AND tbl_posto_fabrica.credenciamento <> 'DESCREDENCIADO'
            AND tbl_posto_fabrica.contato_estado IS NOT NULL
            AND tbl_posto_fabrica.divulgar_consumidor = 't'
            ORDER BY estado";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':linha', $linha);
        $stmt->bindValue(':fabrica', $login_fabrica);

        if( $stmt->execute() AND $stmt->rowCount() > 0 ){
            $response = "<option value='' selected >Selecione</option>";

            $listaDeEstados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach( $listaDeEstados as $estado ){
                $response .= "<option value='{$estado['estado']}'> {$estado['estado']}</option>";
            }

            echo $response;
        }else{
            echo "<option value='0'> Nenhum estado encontrado para esta linha.</option>";
        }

        exit;
    }

    if($buscaAjax == "cidades"){
        $estado = filter_input(INPUT_POST, 'estado');
        $linha  = filter_input(INPUT_POST, 'linha');

        $sql = "SELECT DISTINCT UPPER(tbl_posto_fabrica.contato_cidade) AS cidade, tbl_posto_fabrica.contato_cidade
                FROM tbl_posto_fabrica
                INNER JOIN tbl_linha ON tbl_linha.fabrica = :fabrica
                INNER JOIN tbl_posto_linha ON tbl_posto_linha.posto = tbl_posto_fabrica.posto AND tbl_posto_linha.linha = tbl_linha.linha AND tbl_linha.linha = :linha
                WHERE tbl_posto_fabrica.fabrica = :fabrica
                AND tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
                AND tbl_posto_fabrica.contato_estado = :estado
                AND tbl_linha.linha = :linha
                AND tbl_posto_fabrica.divulgar_consumidor = 't'
                ORDER BY tbl_posto_fabrica.contato_cidade ASC";


        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':fabrica', $login_fabrica);
        $stmt->bindValue(':estado', $estado);
        $stmt->bindValue(':linha', $linha);

        if( $stmt->execute() AND $stmt->rowCount() > 0 ){
            $response = "<option value='' selected >Selecione</option>";

            $listaDeCidades = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach( $listaDeCidades as $cidade ){
                $response .= "<option value='{$cidade['cidade']}'> {$cidade['cidade']}</option>";
            }

            echo $response;
        }

        exit;
    }

    if($buscaAjax == "assistencia"){
        $estado   = filter_input(INPUT_POST, 'estado');
        $endereco = filter_input(INPUT_POST, 'endereco');
        $cep      = filter_input(INPUT_POST, 'cep');
        $cidade   = utf8_decode(filter_input(INPUT_POST, 'cidade'));
        $linha    = filter_input(INPUT_POST, 'linha'); 

        $pais = 'Brasil';

        if(strlen(trim($endereco)) > 0 or (strlen(trim($endereco)) == 0 and strlen($cidade) > 0) ){

            $endereco = explode(', ', $endereco);
            $contax = count($endereco);
            #$estado = trim($endereco[3]);
            #$cidade = trim($endereco[2]);
            $bairro = trim($endereco[1]);
            $endereco = trim($endereco[0]);
            if($contax == 2) {
                $cidade  = $endereco;
                $estado = $bairro;
            }
            $latLon = $oTCMaps->geocode($endereco, null, $bairro, $cidade, $estado, $pais, $cep);

            $lat = $latLon['latitude'];
            $lon = $latLon['longitude'];
        }

        //$latLon = getLatLonConsumidor($endereco, $cep);

        

        // $parte = explode('@', $latLon);
        // $lat = $parte[0];
        // $lon = $parte[1];
        $latLonStr = $lat.'@'.$lon;

        $order = " nome ";
        if(strlen($cep) > 2 or (!empty($lat) and !empty($lon))) {
            if(!empty($lat) and !empty($lon)) {
                //$latLon = getLatLonConsumidor($endereco, $cep);
                //$latLon = $oTCMaps->geocode(null, null, null, $cidade, $estado, $pais);
                //echo $endereco." == endereco ".$bairro." == bairro ".$cidade." == cidade ".$estado." == estado".$pais." == pais";exit;
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
            SELECT DISTINCT UPPER(tbl_posto.nome) AS nome, 
            tbl_posto_fabrica.nome_fantasia, 
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
            JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $fabrica
            JOIN tbl_posto_linha ON tbl_posto_linha.posto = tbl_posto_fabrica.posto AND tbl_posto_linha.ativo IS TRUE
            JOIN tbl_linha ON tbl_linha.linha = tbl_posto_linha.linha AND tbl_linha.fabrica = $fabrica AND tbl_linha.ativo IS TRUE
            JOIN tbl_produto ON tbl_produto.linha = tbl_linha.linha
            WHERE tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
            AND tbl_posto_fabrica.divulgar_consumidor IS TRUE
            
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
                    $parametros_adicionais = str_replace("\\n", "<br>", $parametros_adicionais);
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
                body { background:#fff; color:#a2acac; font-family:'MyriadProRegular', Verdana, Geneva, sans-serif; font-size:13px; }
                p { line-height:18px; }
                p a { color:#fa8e07; text-decoration:none; }
                h2 { margin-bottom:5px; float:left; font-size:18px; color:#f98a05; font-family:'MyriadProSemibold', Verdana, Geneva, sans-serif; width:100%; }
                h1 { color:#9ba6a6; font-size:32px; font-family:'MyriadProBold',Verdana, Geneva, sans-serif; padding:20px 20px 10px; }
                .box_content { padding:0 20px 20px; width:789px; }

                .clear { clear:both; }
                h1 { color:#9ba6a6; font-size:32px; font-family:'MyriadProBold',Verdana, Geneva, sans-serif;  padding: 10px; margin: 0;}
                .box_content { margin: 0 auto; padding:0 20px 20px; width:80%; }
        
                #formAssistencia { float:left; width:336px; margin:25px 40px 0 0; }
                #cep {
                    width: 220px;
                }
                select, input {  
                    border: 1px solid #ACACAC; 
                    width: 334px; 
                    color: #a2acac; 
                    margin-top: 5px;
                    margin-bottom: 25px;
                    padding: 10px; 
                    font-size: 13px;
                    background-color: #ffffff;
                    border-radius: 5px;
                }
                #resultado { float:left; width:100%; margin-top:25px; }
                #resultado h2 { color:#828f8f }
                #consultar{
                 border:1px solid #bdc4c4; 
                    width:107px; 
                    height:29px; 
                    margin:5px 0 15px; 
                    color:#a2acac; 
                    font-family:'MyriadProRegular', Verdana, Geneva, sans-serif; 
                    padding:2px;    
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

                span.localizar, span.localizar-todos{
                    cursor: pointer;
                    color: #a2acac;
                    text-decoration: underline;
                    display: inline;
                }
                
                span.rota{
                    cursor: pointer;
                    color: #a2acac;
                    text-decoration: underline;
                    display: inline;
                }

                .cor_hr{
                    border-color: #eeeeee;
                    
                }
                .asterisco{
                    color:#F17E25; 
                }
                .txt_titulo_principal{
                    color:#F17E25; 
                    font-size:28px; 
                    font-family:'MyriadProBold',Verdana, Geneva, sans-serif;
                    text-align: center;
                }
                .txt_subtitulo_principal{
                    color: #989898;
                    font-size: 16px;
                    text-align: center;
                }
                .txt_campos_obg{
                    color:#F17E25; 
                    font-size:16px; 
                    font-weight: bold;
                    display: block;
                    width: 100%;
                    margin-bottom: 20px
                }
                .txt_label{
                    color: #989898;
                    font-size: 16px;
                }
                
                .box_all{
                    width: 100%;
                    margin: 0 auto;
                }
                
                .btn_pesquisar{
                    border: solid 1px #E57622;
                    background: #F17E25;
                    color: #ffffff;
                    font-size: 13px;
                    font-weight: bold;
                    padding: 10px 35px;
                    text-align: center;
                    cursor: pointer;
                    border-radius: 5px;
                }
                .btn_pesquisar:hover{
                    border: solid 1px #F17E25;
                    background: #E57622;
                    color: #ffffff;
                    font-size: 13px;
                    font-weight: bold;
                    padding: 10px 35px;
                    text-align: center;
                    cursor: pointer;
                    border-radius: 5px;
                }
                .box_50_left{
                    width: 70%;
                    float: left;
                }
                .box_50_right {
                    width: 30%;
                    float: right;
                }
            </style>
        <body>

        <h1 class="txt_titulo_principal">Serviço Técnico Autorizado</h1>
        <p class="txt_subtitulo_principal">Encontre a assistência técnica Pressure mais próxima de você procurando nos campos abaixo.<br/>
        <div class="box_content">
            <div class="box_all">
                <div class="box_50_left">

                    <div id="map-brazil">

                        <ul class="brazil">
                            <li id="AC" class="br1"><a href="#acre">Acre</a></li>
                            <li id="AL" class="br2"><a href="#alagoas">Alagoas</a></li>
                            <li id="AP" class="br3"><a href="#amapa">Amapá</a></li>
                            <li id="AM" class="br4"><a href="#amazonas">Amazonas</a></li>
                            <li id="BA" class="br5"><a href="#bahia">Bahia</a></li>
                            <li id="CE" class="br6"><a href="#ceara">Ceará</a></li>
                            <li id="DF" class="br7"><a href="#distrito-federal">Distrito Federal</a></li>
                            <li id="ES" class="br8"><a href="#espirito-santo">Espírito Santo</a></li>
                            <li id="GO" class="br9"><a href="#goias">Goiás</a></li>
                            <li id="MA" class="br10"><a href="#maranhao">Maranhão</a></li>
                            <li id="MT" class="br11"><a href="#mato-grosso">Mato Grosso</a></li>
                            <li id="MS" class="br12"><a href="#mato-grosso-do-sul">Mato Grosso do Sul</a></li>
                            <li id="MG" class="br13"><a href="#minas-gerais">Minas Gerais</a></li>
                            <li id="PA" class="br14"><a href="#para">Pará</a></li>
                            <li id="PB" class="br15"><a href="#paraiba">Paraíba</a></li>
                            <li id="PR" class="br16"><a href="#parana">Paraná</a></li>
                            <li id="PE" class="br17"><a href="#pernambuco">Pernambuco</a></li>
                            <li id="PI" class="br18"><a href="#piaui">Piauí</a></li>
                            <li id="RJ" class="br19"><a href="#rio-de-janeiro">Rio de Janeiro</a></li>
                            <li id="RN" class="br20"><a href="#rio-grande-do-norte">Rio Grande do Norte</a></li>
                            <li id="RS" class="br21"><a href="#rio-grande-do-sul">Rio Grande do Sul</a></li>
                            <li id="RO" class="br22"><a href="#rondonia">Rondônia</a></li>
                            <li id="RR" class="br23"><a href="#roraima">Roraima</a></li>
                            <li id="SC" class="br24"><a href="#santa-catarina">Santa Catarina</a></li>
                            <li id="SP" class="br25"><a href="#sao-paulo">São Paulo</a></li>
                            <li id="SE" class="br26"><a href="#sergipe">Sergipe</a></li>
                            <li id="TO" class="br27"><a href="#tocantins">Tocantins</a></li>
                        </ul>

                    </div>
                </div>
                <div class="box_50_right">
                    <form id="formAssistencia">

                        <label class="txt_campos_obg">* Campos obrigatórios</label>
                        <label class="txt_label" for="linha"><span class='asterisco'>*</span> Linha:</label>
                        
                        <select id="linha" name="linha" onchange="buscaEstado(this.value)">
                            <?php
                                $sql = "SELECT nome, linha FROM tbl_linha WHERE fabrica = {$login_fabrica}";
                                $pg_res = pg_query($con, $sql);
                                $listaDeLinhas = pg_fetch_all($pg_res);
                            ?>

                            <?php if( !empty($listaDeLinhas) ) { ?>
                                 <option value='0' selected='selected'>Selecione</option>
                                    <?php foreach( $listaDeLinhas as $linha ) { ?>
                                        <option value='<?= $linha['linha'] ?>'><?= $linha['nome'] ?></option>
                                    <?php } ?>    
                            <?php } else { ?> 
                                <option selected='selected'> Nenhuma Linha encontrada</option>
                            <?php } ?>
                        </select>

                        <label class="txt_label" for="cep">CEP:</label>
                        <br>
                        <input id="cep" name="cep" type="text" value=""/>
                        <br>
                        <label class="txt_label" for="estado"><span class='asterisco'>*</span> Estado:</label>
                        <select id="estado" name="estado" onchange="buscaCidade(this.value)">
                            <option></option>
                        </select>
                        <label class="txt_label" for='cidade'><span class='asterisco'>*</span> Cidade:</label>
                        <select name="cidade" id="cidade">
                            <option></option>
                        </select>
                        <br />
                        <button type="button"  onclick="buscaAssistencia($('#cidade').val())" class="btn_pesquisar">
                            <i class="fa fa-search"></i> Pesquisar</button>
                    </form>
                </div>
            </div>

            <div class="clear">&nbsp;</div>                
            <div id="box_mapa">
                <div class="alert calculo-rota" >Calculando rota aguarde...</div>
                <div id="map_canvas" style="height: 610px; margin-top: 20px; border: 1px solid #CCCCCC;"></div>
                <div style="text-align: right;margin-top: 20px" >
                    <button type="button" class="btn_pesquisar localizar-todos" onclick="markers.focus(true);" ><i class="fa fa-map-marker"></i> Mostrar todos os Postos</button>
                </div>
            </div>
            <div id="resultado" >
                
                <div id="assistencia" style="width:100%; height:300px; overflow:auto;"></div>
            </div>
            </div>
        </div>

        <script src="https://use.fontawesome.com/a1911bb13f.js"></script>

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
                    less.modifyVars({'@map_500':'transparent url(\'../cssmap-brazil/br-500-cadence-laranja.png\') no-repeat -1010px 0'});
                });
                $(function(){
                    $("#cep").mask("99999-999");
                    $("#box_mapa").hide();
                    /**
                    * Evento para buscar o endereço do cep digitado
                    */
                    $("#cep").blur(function() {
                        busca_cep($("#cep").val(), $('#linha').val());                        
                    });

                    $(document).on("click", "button.localizar", function() {
                        var lat = $(this).data("lat");
                        var lng = $(this).data("lng");

                        map.setView(lat, lng, 25);
                        map.scrollToMap();

                    });

                    $(document).on("click", "button.rota", function() {
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

                    map.scrollToMap();

                }

                var endereco = {};

                /**
                * Função que faz um ajax para buscar o cep nos correios
                */
                function busca_cep(cep, linha, method) {                  
                    if (cep.length > 0) {
                        if(linha != 0){
                            if (typeof method == "undefined") {
                                method = "webservice";
                            }
                            
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
                                    busca_cep(cep, linha, "database");
                                } else {
                                    alert("Erro ao consultar CEP, tempo limite esgotado");
                                    $("#consultar").prop({ disabled: false }).text("Consultar");
                                    $("#estado, #cidade").prop({ disabled: false });
                                }
                            }).done(function(r) {
                                data = r.split(";");

                                if (data[0] != "ok" && method == "webservice") {
                                    busca_cep(cep, linha, "database");
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

                                    buscaEstado(linha, function() {
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

                function buscaEstado(linha, callback) {
                    if(linha != 0){
                        $("#estado").html("\
                            <option value='' >Carregando Estados...</option>\
                        ");
                        $("#estado").prop({ disabled: true });

                        $.ajax({
                            type: "POST",
                            url:  "mapa_rede_pressure.php",
                            data: "linha="+linha+"&buscaAjax=estados",
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
                            <option value='' >Selecione uma Linha</option>\
                        ");
                        $("#estado").prop({ disabled: true });
                        $("#cidade").html("\
                            <option value='' >Selecione um Estado</option>\
                        ");
                        $("#cidade").prop({ disabled: true });
                    }
                }

                function buscaCidade(estado, callback) {
                    var linha = $("#linha").val();

                    if(linha.length > 0 && linha.length > 0){
                        $("#cidade").html("\
                            <option value='' >Carregando Cidades...</option>\
                        ");

                        $("#cidade").prop({ disabled: true });
                        $.ajax({
                            type: "POST",
                            url:  "mapa_rede_pressure.php",
                            data: "estado=" + estado + "&linha=" + linha + "&buscaAjax=cidades",
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
                    let estado = $("#estado").val();
                    let linha  = $("#linha").val();
                    let cep    = $("#cep").val();

                    let lista = "";

                    if(cidade.length > 0 && estado.length > 0 && linha.length > 0){
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
                            url:  "mapa_rede_pressure.php",
                            data: "estado=" + estado +
                                  "&cidade=" + cidade + 
                                  "&cep=" + cep +
                                  "&endereco=" + end +
                                  "&linha=" + linha +
                                  "&buscaAjax=assistencia",
                            success: function(resposta){
                                if(resposta.length > 0){
                                    var dados = $.parseJSON(resposta);
                                    console.log(dados);
                                    //lista += "<h2>Postos para assistência:</h2>";
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
                                            rota = "<button type='button' class='btn_pesquisar rota' data-lat='"+value.latitude+"' data-lng='"+value.longitude+"' data-consumidor='"+dados.consumidor+"' ><i class='fa fa-map-marker'></i> Realizar Rota</button>";
                                        }

                                        lista += "\
                                            <span class='posto-resultado' style='margin: 10px 5px' data-fantasia='"+value.nome_fantasia+"' data-lat='"+value.latitude+"' data-lng='"+value.longitude+"' >\
                                                <b style='color: black'>"+value.nome_fantasia+"</b><br />\
                                                <b>"+value.nome+"</b><br />\
                                                "+value.endereco+", "+value.numero+" - "+value.bairro+"<br />\
                                                "+value.cidade_posto+" - "+value.estado+" - "+value.fone+" <br />\
                                                <strong>Observação:</strong> <br /> "+value.obs+"\
                                            </span>\
                                            <button type='button' class='btn_pesquisar localizar' data-lat='"+value.latitude+"' data-lng='"+value.longitude+"' ><i class='fa fa-search'></i> Localizar</button>\
                                            "+rota+"\
                                            <br /><br /><hr class='cor_hr' /><br /><br />\
                                        ";
                                    });

                                    $("#assistencia").html(lista);
                                    addMap();

                                    endereco = {};
                                    $("#cep").val("");
                                }else{
                                    /*alert("Prezado Consumidor, não encontramos um posto autorizado na sua região, por favor, entrar em contato com o nosso Serviço de Atendimento ao Consumidor através do contato sac@jcsbrasil.com.br ou telefone 0800 644 6442 de segunda a sexta-feira das 08:00 as 17:00 horas (exceto feriados nacionais).");*/
                                    alert("Importante: não encontramos um posto autorizado em sua região.\nPor favor entrar em contato com o nosso Serviço de Atendimento ao Consumidor para maiores informações.\nContatos: sac@pressure.com.br ou através do telefone +55 (44) 3218 8500.");
                                    $("#assistencia").html("");
                                    $("#box_mapa").hide();
                                    endereco = {};
                                    $("#cep").val("");
                                }
                            }
                        }).fail(function(r) {
                            alert("Erro ao buscar Postos Autorizados, tempo limite esgotado");
                            $("#assistencia").html("");
                            $("#box_mapa").hide();
                            endereco = {};
                            $("#cep").val("");
                        });
                    }
                }
            </script>
        </body>
    </html>