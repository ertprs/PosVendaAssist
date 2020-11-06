<?php
include_once "dbconfig.php";
include_once "includes/dbconnect-inc.php";
include_once "autentica_admin.php";
include_once "funcoes.php";

if (isset($_POST['atualizar']) && $_POST['atualizar'] == 'sim') {
    $cep       = $_POST['cep'];
    $endereco  = pg_escape_string(utf8_decode($_POST['endereco']));
    $numero    = $_POST['numero'];
    $bairro    = pg_escape_string(utf8_decode($_POST['bairro']));
    $cidade    = $_POST['cidade'];
    $estado    = $_POST['estado'];

    $posto     = $_POST['posto'];
    $latitude  = $_POST['latitude'];
    $longitude = $_POST['longitude'];

    if (!empty($latitude) && !empty($longitude)) {
        $cep = str_replace(array('.','-'), array('',''), $cep);

        $sql = "UPDATE tbl_posto_fabrica SET
                    contato_cep = '$cep',
                    contato_endereco = '$endereco',
                    contato_numero = '$numero',
                    contato_cidade = '$cidade',
                    contato_bairro = '$bairro',
                    contato_estado = '$estado',
                    latitude = $latitude, 
                    longitude = $longitude 
		WHERE posto = $posto
		AND fabrica = $login_fabrica";
        $res = pg_query($con, $sql);
	$msg_erro = pg_last_error();
	if ($login_fabrica == 158 && strlen($msg_erro) == 0) {
		$upTecnico = "
			UPDATE tbl_tecnico SET	
                        	cep              = '{$cep}',
				latitude	 = {$latitude},
				longitude	 = {$longitude},
				estado           = '{$estado}',
                        	cidade           = '{$cidade}',
                        	bairro           = '{$bairro}',
                        	endereco         = '{$endereco}',
                        	numero           = '{$numero}'
                        WHERE posto = {$posto}
			AND fabrica = {$login_fabrica};
		";

		$resTecnico = pg_query($con, $upTecnico);
		$msg_erro = pg_last_error();
	}

        if (empty($msg_erro)) {
            exit(json_encode(array("ok" => "Cadastro atualizado com sucesso")));
        }
    }
    exit(json_encode(array("error" => "Erro ao tentar atualizar o cadastro")));
}

define('BI_BACK', (strpos($_SERVER['PHP_SELF'],'/bi/') == true)?'../':'');

$sql = "SELECT 
            nome_fantasia,
            contato_cep,
            contato_endereco,
            contato_numero,
            contato_cidade,
            contato_bairro,
            contato_estado,
            latitude,
            longitude
        FROM tbl_posto_fabrica WHERE posto = $posto AND fabrica = $login_fabrica";

$res = pg_query($con, $sql);
if (pg_num_rows($res) > 0) {
    $nome_fantasia    = pg_fetch_result($res, 0, "nome_fantasia");
    $contato_cep    = pg_fetch_result($res, 0, "contato_cep");
    $contato_endereco = pg_fetch_result($res, 0, "contato_endereco");
    $contato_numero   = pg_fetch_result($res, 0, "contato_numero");
    $contato_cidade   = pg_fetch_result($res, 0, "contato_cidade");
    $contato_estado   = pg_fetch_result($res, 0, "contato_estado");
    $contato_bairro   = pg_fetch_result($res, 0, "contato_bairro");
    $latitude         = pg_fetch_result($res, 0, "latitude");
    $longitude        = pg_fetch_result($res, 0, "longitude");
}

?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Atualiza localização</title>
        <link type="text/css" rel="stylesheet" media="screen" href="<?=BI_BACK?>bootstrap/css/bootstrap.css" />
        <link type="text/css" rel="stylesheet" media="screen" href="<?=BI_BACK?>bootstrap/css/extra.css" />
        <link type="text/css" rel="stylesheet" media="screen" href="<?=BI_BACK?>css/tc_css.css" />
        <link type="text/css" rel="stylesheet" media="screen" href="<?=BI_BACK?>css/tooltips.css" />
        <link type="text/css" rel="stylesheet" media="screen" href="<?=BI_BACK?>plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css">
        <link type="text/css" rel="stylesheet" media="screen" href="<?=BI_BACK?>bootstrap/css/ajuste.css" />
        <link href="plugins/leaflet/leaflet.css" rel="stylesheet" type="text/css" />
        <style type="text/css">
            .titulo{
                font-size: 18px;
                margin-top: -17px;
            }

            .margem-button{
                margin-bottom: 50px;
            }

            #Maps{
                width: 100%;
                height: 500px;
                border: 1px black solid;
                position: relative;
                margin-top: 20px;
                float: left;
                padding: 1px;
            }            
        </style>
    </head>
<body>
    <div id="loading-block" style="width:100%;height:100%;position:fixed;left:0px;top:0px;text-align:center;vertical-align: middle;background-color:#000;opacity:0.3;display:none;z-index:10" >
    </div>
    <div id="loading">
        <img src="imagens/loading_img.gif" style="z-index:11" />
        <input type="hidden" id="loading_action" value="f" />
        <div style="position:fixed;top:0;left:0;width:100%;height:100%;z-index:10000;"></div>
    </div>
    <div class="container-fluid">
        <h3><?=$nome_fantasia; ?></h3>
        <p class="pull-left" style="margin-bottom: -20px;">*Para uma localização mais precisa, clique no mapa para marcar o ponto exato do posto</p>
    </div>
    <div class="container-fluid margem-button">
        <div class="row-fluid">
            <div class="span6">
                <div id="Maps"></div>
            </div>
            <div class="span6">
                <div class="well" style="margin-top: 20px;">
                    <legend class="titulo">Latitude e Longitude</legend>
                    <form class="form-search">
                        <div class="row-fluid">
                            <div class="span5">
                                <input type="text" id="latitude" placeholder="Latitude" class="span12" value="<?=$latitude; ?>">
                            </div>
                            <div class="span5">
                                <input type="text" id="longitude" placeholder="Longitude" class="span12" value="<?=$longitude; ?>">
                            </div>
                            <div class="span2">
                                <button type="button" id="btn_buscarLoc" class="btn">Buscar</button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="well">
                    <legend class="titulo">Endereço</legend>
                    <form class="form-search">
                        <div class="row-fluid">
                            <div class="span3">
                                CEP<input type="text" id="cep" placeholder="CEP" class="span12" value="<?=$contato_cep; ?>">
                            </div>                        
                            <div class="span7">
                                Endereço<input type="text" id="contato_endereco" placeholder="Endereço" class="span12" value="<?=$contato_endereco; ?>">
                            </div>
                            <div class="span2">
                                Número<input type="text" id="contato_numero" placeholder="Número" class="span12" value="<?=$contato_numero; ?>">
                            </div>
                        </div>
                        <div class="row-fluid">
                            <div class="span3">
                                Bairro<input type="text" id="contato_bairro" placeholder="Bairro" class="span12" value="<?=$contato_bairro; ?>">
                            </div>
                            <div class="span5">
                                Cidade<input type="text" id="contato_cidade" placeholder="Cidade" class="span12" value="<?=$contato_cidade; ?>">
                            </div>
                            <div class="span2">
                                Estado<select id="contato_estado" class="span12">
                                    <option value=""></option>
                                    <?php 
                                    foreach ($array_estados() as $key => $value) {
                                        $selected = ($key == $contato_estado) ? 'selected' : '';
                                        echo "<option value='$key' $selected>$key</option>";
                                    } 
                                    ?>
                                </select>
                            </div>
                            <div class="span2" style="margin-top: 20px;">
                                <button type="button" id="btn_buscar" class="btn">Buscar</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <br />
        <div id="msg" class="alert alert-error fade in" style="display: none;">            
        </div>        
        <hr>
        <div class="row-fluid">
            <div class="span12" style="text-align: center;"><button type="button" id="btn_atualizar" class="btn btn-success btn-large">Atualizar localização</button></div>
        </div>
    </div>
</body>
<script src="<?=BI_BACK?>plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script src="<?=BI_BACK?>plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
<script src="<?=BI_BACK?>plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.core.min.js"></script>
<script src="<?=BI_BACK?>plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.widget.min.js"></script>
<script src="<?=BI_BACK?>plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.effect.min.js"></script>
<script src="<?=BI_BACK?>plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.tabs.min.js"></script>
<script src="<?=BI_BACK?>bootstrap/js/bootstrap.js"></script>
<script src="plugins/leaflet/leaflet.js" ></script>		
<script src="plugins/leaflet/map.js" ></script>
<script src="plugins/mapbox/geocoder.js?<?=time()?>"></script>
<script src="plugins/mapbox/polyline.js"></script>
<script src="js/jquery.mask.js"></script>
<script>
    var Map, Markers, Route, Geocoder;
    $(function(){
        $('html, body').animate({ scrollTop: 0}, 1000, 'linear');

        $("#cep").mask("99.999-999");

        Map      = new Map("Maps");
        Markers  = new Markers(Map);
        Router   = new Router(Map);
        Geocoder = new Geocoder();

        var posto     = <?=$posto; ?>;
        var latitude  = <?php echo ($latitude) ? $latitude : 'null'; ?>;
        var longitude = <?php echo ($longitude) ? $longitude : 'null'; ?>;
        var Fantasia  = '<?=$nome_fantasia; ?>';

        Map.load();
        if (latitude !== null && longitude !== null) {
            Markers.add(latitude, longitude, "blue", Fantasia);
            Markers.render();
            Map.setView(latitude, longitude, 15);
        }

        Map.map.on('click', function (elem) {
            $('#latitude').val(elem.latlng.lat.toFixed(7));
            $('#longitude').val(elem.latlng.lng.toFixed(7));
            Markers.remove();
            Markers.clear();
            Markers.add($('#latitude').val(), $('#longitude').val(), "blue", Fantasia);
            Markers.render();
        });

        $('#btn_buscarLoc').on('click', function(){
            Markers.remove();
            Markers.clear();
            Markers.add($('#latitude').val(), $('#longitude').val(), "blue", Fantasia);
            Markers.render();
            Map.setView($('#latitude').val(), $('#longitude').val(), 15);
        });

        $('#btn_buscar').on('click', function(){
            $('#loading-block').show();
            $('#loading').show();
            try {
                Geocoder.setEndereco({
                    endereco: $('#contato_endereco').val(),
                    numero: $('#contato_numero').val(),
                    bairro: null,
                    cidade: $('#contato_cidade').val(),
                    estado: $('#contato_estado').val(),
                    pais: 'Brazil',
		    cep: $('#cep').val()
                });

                request = Geocoder.getLatLon();

                request.then(
                    function(resposta) {
                        c_lat  = resposta.latitude;
                        c_lon  = resposta.longitude;
                        latlon = c_lat+", "+c_lon;

                        Markers.remove();
                        Markers.clear();
                        Markers.add(c_lat, c_lon, "blue", Fantasia);
                        Markers.render();
                        Map.setView(c_lat, c_lon, 15);

                        $('#latitude').val(c_lat);
                        $('#longitude').val(c_lon);

                        $('#loading-block').hide();
                        $('#loading').hide();
                    },
                    function(erro) {
                        $('#loading-block').hide();
                        $('#loading').hide();                        
                        alert(erro);
                    }
                );
            } catch(e) {
                $('#loading-block').hide();
                $('#loading').hide();                
                alert(e.message);
            }
        });

        $('#btn_atualizar').on('click', function(){
            $('#msg').html('<button type="button" class="close" data-dismiss="alert">×</button>').hide();
            if (confirm('Deseja realmente atualizar a localização deste posto?') == true) {
                $.ajax({
                    url: window.location,
                    method: 'POST',
                    data: { atualizar: 'sim', posto: posto, latitude: $('#latitude').val(), longitude: $('#longitude').val(), endereco: $('#contato_endereco').val(), numero: $('#contato_numero').val(), bairro: $('#contato_bairro').val(), cidade: $('#contato_cidade').val(),estado: $('#contato_estado').val(), cep: $('#cep').val() },
                    timeout: 8000
                }).done(function(data){
                    data = JSON.parse(data);
                    
                    if (data.ok !== undefined) {
                        $('#msg').addClass('alert-success');
                        $('#msg').removeClass('alert-error');                        
                        $('#msg').append("<strong>"+data.ok+"</strong>").show();
                    }else{
                        $('#msg').append("<strong>"+data.error+"</strong>").show();
                    }
                }).fail(function(){
                    $('#msg').append("<strong>Erro ao tentar atualizar o cadastro</strong>").show();
                });
            }
        });

        function buscaCEP(cep, callback, method = null){
            if (typeof cep != "undefined" && cep.length > 0) {
                if (typeof method == "undefined" || method == null || method.length == 0) {
                    method = "webservice";

                    $.ajaxSetup({
                        timeout: 10000
                    });
                } else {
                    $.ajaxSetup({
                        timeout: 5000
                    });
                }

                $.ajax({
                    url: "ajax_cep.php",
                    type: "GET",
                    data: { cep: cep, method: method },
                    error: function(xhr, status, error) {
                        buscaCEP(cep, callback, "database");
                    },
                    success: function(data) {
                        results = data.split(";");

                        if(typeof callback == "function"){
                            callback(results);
                        }
                    }
                });
            }
        }

        $('#cep').on('blur', function(){
            $('#loading-block').show();
            $('#loading').show();
            buscaCEP($('#cep').val(), function(results){
                $('#contato_endereco').val(results[1]);
                $('#contato_bairro').val(results[2]);
                $('#contato_cidade').val(results[3]);
                $('#contato_estado').val(results[4]);
                $('#contato_numero').val('').focus();

                $('#loading-block').hide();
                $('#loading').hide();
            });
        });
    });
</script>
</html>
