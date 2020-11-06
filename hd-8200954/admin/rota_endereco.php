<?php 


include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="call_center";

include "autentica_admin.php";
include 'funcoes.php';


$os= $_GET['num_os'];

    $sql = "SELECT  PO.nome                  ,
                    PF.contato_endereco      ,
                    PF.contato_numero        ,
                    PF.contato_complemento   ,
                    PF.contato_bairro        ,
                    PF.contato_cidade        ,
                    PF.contato_estado        ,
                    PF.contato_cep           ,
                    OS.consumidor_nome       ,
                    OS.consumidor_endereco   ,
                    OS.consumidor_numero     ,
                    OS.consumidor_complemento,
                    OS.consumidor_bairro     ,
                    OS.consumidor_cidade     ,
                    OS.consumidor_estado     ,
                    OS.consumidor_cep        ,
                    OS.os                    ,
                    OS.sua_os                ,
                    OS.qtde_km
            FROM tbl_os OS
                JOIN tbl_posto         PO ON PO.posto = OS.posto
                JOIN tbl_posto_fabrica PF ON PF.posto = OS.posto AND PF.fabrica = {$login_fabrica}
            WHERE OS.os      = {$os}
            AND   OS.fabrica = {$login_fabrica}";

    $dados = pg_fetch_assoc(pg_query($con, $sql));

    extract($dados);
    $sua_os = (strlen($sua_os) == 0) ? $os : $sua_os;
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
        <style type="text/css">
            #Maps {
                width: 100%;
                height: 500px;
                border: 1px black solid;
                position: relative;
                margin-bottom: 20px;
                float: left;
                padding: 1px;
            }
            .input-km{
                width: 105px;
                text-align: center;
            }
            .img-selecionada{
                border: solid 1px #d90000 !important;
                padding: 11px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="row-fluid"><div class="alert alert-info"><h4><strong>Ordem de serviço: <?=$sua_os; ?></strong></h4></div></div>
            <div class="row-fluid">
                <div class="span12">
                    <table id="table_contato" class='table table-striped table-bordered table-hover table-large'>
                        <thead>
                            <tr class='titulo_tabela'>
                                <th colspan="9" >Posto: <?=$nome;?></th>
                            </tr>
                            <tr class='titulo_coluna' >
                                <th>Endereço</th>
                                <th>Bairro</th>
                                <th>Cidade</th>
                                <th>Estado</th>
                                <th>CEP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr rel='dados_posto'>
                                <td class='tac'><?="{$contato_endereco}, {$contato_numero}"; ?></td>
                                <td class='tac'><?=$contato_bairro;?></td>
                                <td class='tal'><?=$contato_cidade;?></td>
                                <td class='tac'><?=$contato_estado;?></td>
                                <td class='tal'><?=$contato_cep;?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="row-fluid">
                <div class="span12">
                    <table id="table_consumidor" class='table table-striped table-bordered table-hover table-large'>
                        <thead>
                            <tr class='titulo_tabela'>
                                <th colspan="9" >Consumidor: <?=$consumidor_nome;?></th>
                            </tr>
                            <tr class='titulo_coluna' >
                                <th>Endereço</th>
                                <th>Bairro</th>
                                <th>Cidade</th>
                                <th>Estado</th>
                                <th>CEP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr rel='dados_consumidor'>
                                <td class='tac'><?="{$consumidor_endereco}, {$consumidor_numero}";?></td>
                                <td class='tac'><?=$consumidor_bairro;?></td>
                                <td class='tal'><?=$consumidor_cidade;?></td>
                                <td class='tac'><?=$consumidor_estado;?></td>
                                <td class='tal'><?=$consumidor_cep;?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="row-fluid">
                <div class="span12 alert alert-warning">
                    <span><strong>PARA REDEFINIR A POSIÇÃO DO CLIENTE OU DO POSTO AUTORIZADO NO MAPA, EM CASO DE ERRO NA SUA LOCALIZAÇÃO, SELECIONE UMA DAS LEGENDAS LOCALIZADAS A CIMA DO MAPA E EM SEGUIDA CLIQUE NO LOCAL DESEJADO PARA REDEFINIR SUA LOCALIZAÇÃO. APÓS A ALTERAÇÃO CLIQUE NO BOTÃO "RECALCULAR ROTA" PARA UMA NOVA COMPARAÇÃO</strong></span>
                </div>
            </div>
            <div class="row-fluid">
                <div class="span12" style="text-align: right;">
                    <span style="cursor: pointer" id="icon-blue"><img style="margin-bottom: 6px;" src="imagens/Google_Maps_Marker_Blue.png" alt="Icone Cliente" /><strong><?=$consumidor_nome;?></strong></span>
                    <span id="icon-red" style="cursor: pointer"><img style="margin-bottom: 6px;" src="imagens/Google_Maps_Marker_Red.gif" alt="Icone Postos" /><strong><?=$nome;?></strong></span>
                    <button class="btn btn-primary" id="btn-recalcula-rota" style="margin-left: 18px; margin-bottom: 6px;">Recalcular rota</button>
                    <div id='Maps'></div>
                </div>
            </div>
            <div class="row-fluid">
                <div class="span1"></div>
                <div class="span2">
                    <div class='control-group'>
                        <label class='control-label' for=''><strong>Ida</strong></label>
                        <div class='controls controls-row'>
                            <div class="input-append">
                                <input class="input-km" type='text' name='km_ida' id='km_ida' value="" disabled>
                                <span class="add-on" style="background-color: #596d9b; color: white;">+</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="span2">
                    <div class='control-group'>
                        <label class='control-label' for=''><strong>Volta</strong></label>
                        <div class='controls controls-row'>
                            <div class="input-append">
                                <input class="input-km" type='text' name='km_volta' id='km_volta' value="" disabled>
                                <span class="add-on" style="background-color: #596d9b; color: white;">=</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="span2">
                    <div class='control-group'>
                        <label class='control-label' for=''><strong>Total</strong></label>
                        <div class='controls controls-row'>
                            <input class="input-km" type='text' name='total_km' id='total_km' value="" disabled>
                        </div>
                    </div>
                </div>
                <div class="span2">
                    <div class='control-group error'>
                        <label class='control-label' for='km_informado'><strong>Informado</strong></label>
                        <div class='controls controls-row'>
                            <input class="input-km input-append" type='text' name='km_informado' id='km_informado' value="<?=$qtde_km?> km" disabled>
                        </div>
                    </div>
                </div>
                <div class="span2">
                    <div class='control-group'>
                        <label class='control-label' for='diferenca'><strong>Diferença</strong></label>
                        <div class='controls controls-row'>
                            <input class="input-km input-append" type='text' name='diferenca' id='diferenca' value="" disabled>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    <script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
    <script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
    <script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.core.min.js"></script>
    <script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.widget.min.js"></script>
    <script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.effect.min.js"></script>
    <script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.tabs.min.js"></script>
    <script src="bootstrap/js/bootstrap.js"></script>
    <script src="plugins/leaflet/leaflet.js"></script>
    <script src="plugins/leaflet/map.js"></script>
    <script src="plugins/mapbox/geocoder.js"></script>
    <script src="plugins/mapbox/polyline.js"></script>
    <script type="text/javascript">
        var Map, Markers, Router, Geocoder, consumidor_lat, consumidor_lon, contato_lat, contato_lon;
        $(function(){
            Map      = new Map("Maps");
            Markers  = new Markers(Map);
            Router   = new Router(Map);
            Geocoder = new Geocoder();

            Map.load();
            Map.map.on('click', function (elem) {
                if (!$('#icon-blue').hasClass('img-selecionada') && !$('#icon-red').hasClass('img-selecionada')) { return false; }

                Markers.remove();
                Markers.clear();
                if ($('#icon-blue').hasClass('img-selecionada')) {
                    consumidor_lat = elem.latlng.lat.toFixed(7);
                    consumidor_lon = elem.latlng.lng.toFixed(7);
                }else{
                    contato_lat = elem.latlng.lat.toFixed(7);
                    contato_lon = elem.latlng.lng.toFixed(7);
                }
                Markers.add(consumidor_lat, consumidor_lon, "blue", "<?=$consumidor_nome;?>");
                Markers.add(contato_lat, contato_lon, "red", "<?=$nome;?>");
                Markers.render();
                Router.remove();
                Router.clear();
            });

            $('#icon-blue').click(function(){
                if (!$('#icon-blue').hasClass('img-selecionada')) {
                    $('#icon-red').removeClass('img-selecionada');
                    $('#icon-blue').addClass('img-selecionada');
                }
                Map.flyTo(consumidor_lat, consumidor_lon, 18);
            });
            $('#icon-red').click(function(){
                if (!$('#icon-red').hasClass('img-selecionada')) {
                    $('#icon-blue').removeClass('img-selecionada');
                    $('#icon-red').addClass('img-selecionada');
                }
                Map.flyTo(contato_lat, contato_lon, 18);
            });

            $('#btn-recalcula-rota').on('click', function(){
                $('#btn-recalcula-rota').text('Recalculando...').attr('disabled', true);
                calcula_rota(function(){
                    $('#btn-recalcula-rota').text('Recalcular rota').attr('disabled', false);
                });
            });

            try {
                Geocoder.setEndereco({
                    endereco: '<?=$consumidor_endereco?>',
                    numero: '<?=$consumidor_numero?>',
                    bairro: '<?=$consumidor_bairro?>',
                    cidade: '<?=$consumidor_cidade?>',
                    estado: '<?=$consumidor_estado?>',
                    pais: 'BR'
                });

                request = Geocoder.getLatLon();

                request.then(function(resposta) {
                    consumidor_lat  = resposta.latitude;
                    consumidor_lon  = resposta.longitude;

                    Geocoder.setEndereco({
                        endereco: '<?=$contato_endereco?>',
                        numero: '<?=$contato_numero?>',
                        bairro: '<?=$contato_bairro?>',
                        cidade: '<?=$contato_cidade?>',
                        estado: '<?=$contato_estado?>',
                        pais: 'BR'
                    });
                    request = Geocoder.getLatLon();
                    request.then(function(resposta) {
                        contato_lat  = resposta.latitude;
                        contato_lon  = resposta.longitude;

                        Markers.add(consumidor_lat, consumidor_lon, "blue", "<?=$consumidor_nome;?>");
                        Markers.add(contato_lat, contato_lon, "red", "<?=$nome;?>");
                        Markers.render();
                        Markers.focus();
                        calcula_rota();
                    },
                    function(erro) {
                        alert(erro);
                    });
                },
                function(erro) {
                    alert(erro);
                });
            } catch(e) {
                alert(e.message);
            }
        });

        function calcula_rota(callback = null){
            $.ajax({
                url: '../controllers/TcMaps.php',
                method: "GET",
                data: { 'ajax': 'route', 'ida_volta': 'sim', 'destino': consumidor_lat+','+consumidor_lon, 'origem': contato_lat+','+contato_lon },
                timeout: 9000
            }).fail(function(){
                alert('Não foi possível calcular a rota solicitada, tente novamente mais tarde');
            }).done(function(data){
                data = JSON.parse(data);
                $('#km_ida').val(data.km_ida+' km');
                $('#km_volta').val(data.km_volta+' km');
                $('#total_km').val(data.total_km+' km');

                var km_informado = $('#km_informado').val().split(' ');
                var diferenca = km_informado[0] - data.total_km
                $('#diferenca').val(diferenca.toFixed(2)+' km');
                Router.add(Polyline.decode(data.rota.routes[0].geometry));
                Router.render();
                callback();
            });
        }
    </script>
</html>


?>