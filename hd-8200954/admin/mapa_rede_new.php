<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include_once 'autentica_admin.php';
include_once 'funcoes.php';
include_once '../helpdesk/mlg_funciones.php';

if (!function_exists('anti_injection')) {
    function anti_injection($string) {
        $a_limpa = array("'" => "", "%" => "", '"' => "", "\\" => "");
        return strtr(strip_tags(trim($string)), $a_limpa);
    }
}

$callcenter = anti_injection($_GET['callcenter']);

if (isset($_GET['callcenter']) && strlen($callcenter) > 0) {
    $hd_chamado          = anti_injection($_GET['hd_chamado']);
    $pais                = anti_injection($_GET['pais']);
    $cidade              = anti_injection($_GET['consumidor_cidade']);
    $estado              = anti_injection($_GET['consumidor_estado']);
    $bairro              = anti_injection(utf8_decode($_GET['bairro']));
    $bairro              = retira_acentos($bairro);
    $endereco            = anti_injection($_GET['endereco']);
    $numero              = anti_injection($_GET['numero']);
    $cep                 = preg_replace('/\D/','',anti_injection($_GET['cep']));
    $linha               = anti_injection($_GET['linha']);
    $nome_cliente        = anti_injection($_GET['nome']);
    $endereco_formatado  = anti_injection($_GET['consumidor']);
    $endereco_rota       = anti_injection($_GET['endereco_rota']);
    $endereco_rota_cep   = anti_injection($_GET['endereco_rota']).',cep:'.$cep; //hd_chamado=2752072
    $aux                 = explode(",",$endereco_formatado);
    $endereco_consumidor = $aux[0];

    if($login_fabrica == 189){
        $tipo_cliente = anti_injection($_GET['tipo_cliente']);
        $tipo_posto   = anti_injection($_GET['tipo_posto']);
        $produto      = anti_injection($_GET['produto']);
    }


} else if (isset($_REQUEST['pais'])) {
    $pais    = $_REQUEST['pais'];
    $estado  = (!empty($_REQUEST['mapa_estado'])) ? $_REQUEST['mapa_estado'] : $_REQUEST['estado'];
    $cidade  = $_REQUEST['cidade'];
    $fabrica = $_REQUEST['fabrica'];
    if ($login_fabrica == 117) {
        if (strlen($_REQUEST['linha_elgin'])) {
            $linha = anti_injection($_REQUEST['linha_elgin']);
            $pais  = (empty($pais)) ? 'BR' : $pais;

            $sql_linha = "SELECT DISTINCT
                                tl.linha,
                                tl.nome,
                                tl.ativo as ativo
                            FROM tbl_macro_linha_fabrica AS tmlf
                                JOIN tbl_macro_linha AS tml ON tmlf.macro_linha = tml.macro_linha
                                JOIN tbl_linha AS tl ON tmlf.linha = tl.linha
                            WHERE tmlf.fabrica = $login_fabrica
                                AND tml.ativo IS TRUE
                                AND tl.ativo IS TRUE
                                AND tl.linha = {$linha}
                            ORDER BY nome;";
            $res_linha = pg_query($con,$sql_linha);
            $linha_s_nome = pg_fetch_result($res_linha, 0, 'nome');
        }elseif(strlen($_REQUEST['mapa_linha'])) {
            $linha = anti_injection($_REQUEST['mapa_linha']);
        }else{
            $linha = "";
        }
    }else{
        $linha = "";
    }

    if ($login_fabrica == 52) {
        $consumidor_estado = $_GET['consumidor_estado'];
        $consumidor_cidade = $_GET['consumidor_cidade'];
    }
}

/* DISTÂNCIA DO RAIO PARA BUSCA DE POSTO */
if(in_array($login_fabrica, array(152,156,161,163))){
    $distancia_km = 300;
}elseif(in_array($login_fabrica, array(176,183,189,190))){
    $distancia_km = 3000000000;
}elseif(in_array($login_fabrica, array(186))){
    $distancia_km = 50;
}else{
    $distancia_km = 100;
}

?>
<!DOCTYPE html>
<html>
    <head>
        <title>Telecontrol - Mapa da Rede Autorizada</title>
        <link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/bootstrap.css" />
        <link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/extra.css" />
        <link type="text/css" rel="stylesheet" media="screen" href="css/tc_css.css" />
        <link type="text/css" rel="stylesheet" media="screen" href="css/tooltips.css" />
        <link type="text/css" rel="stylesheet" media="screen" href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css">
        <link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/ajuste.css" />
        <link href="plugins/leaflet/leaflet.css" rel="stylesheet" type="text/css" />
        <style type="text/css">
            #Maps {
                width: 75%;
                height: 500px;
                border: 1px black solid;
                margin: 0 auto;
            }
            .img-selecionada{
                border: solid 1px #d90000 !important;
                padding: 11px;
            }
            #grid_postos{
                margin: 0 auto;
                display: table;
            }
        </style>
    </head>
    <body>
        <div style="width: 100%; margin-top: 20px; margin-bottom: 5px;">
            <div id="Maps"></div>
            <div style="margin: 0 auto; display: table; margin-top: 5px;">
            <?php if(isset($_GET['callcenter'])){
                if (!in_array($login_fabrica, array(169, 170))) { ?>
            <a id="help_localizacao" href="#" data-toggle="tooltip" title="Para alterar a localização do cliente/posto autorizado, em caso de erro na sua localização, selecione um dos icones ao lado (no caso do posto selecione qual posto será alterado na lista ao lado do icone) e clique na localização desejada. Após a alteração, será necessário clicar na opção 'Rota' localizado ao final da linha do posto modificado para recalcular a distancia dos pontos.">
                <strong>Alterar localização no mapa?</strong>
            </a>
            <?php } ?>
            <span style="cursor: pointer" id="icon-blue">
                <img src="https://www.google.com/intl/en_us/mapfiles/ms/micons/blue-dot.png" alt="Icone Cliente" />
                <strong>Cliente</strong>
            </span>
            <?php } ?>
            <span id="icon-red" style="cursor: pointer">
                <img src="imagens/Google_Maps_Marker_Red.gif" alt="Icone Postos" />
                <strong>Postos Autorizados</strong>
                <?php if(isset($_GET['callcenter']) && !in_array($login_fabrica, array(169, 170))){ ?>
                <select id="postos-autorizados" style="margin-top: 8px;">
                    <option value=""></option>
                </select>
                <?php } ?>
            </span>
            </div>
            <?php
            if(isset($_GET['callcenter'])) {
            ?>
                <p>
                    <h4>Endereço localizado: <span class="endereco-localizado" ></span>.</h4>
                </p>
            <?php
            }
            ?>
            <?php if (in_array($login_fabrica, array(169,170))){ ?>
                <div class="container">
                <div style="height: 18px; width: 15px; background-color: red; float: left;"></div><span><b>Posto com bloqueio de agendamento</b></span>
                </div>
            <?php } ?>
            <div id="div_table">
                <table id="grid_postos" class='table table-striped table-bordered table-hover table-large' style="margin-top: 15px;">
                    <thead>
                        <tr class='titulo_coluna'>
                            <th>Nome do Posto</th>
                            <th>Nome Fantasia</th>
                            <?php
                            if (in_array($login_fabrica, [30])) { ?>
                                <th>CNPJ/CPF</th>
                            <?php
                            } ?>
                            <th>Endereço</th>
                            <?php if (!$callcenter) { ?>
                                <th>Bairro</th>
                                <th>Cidade</th>
                                <th>UF</th>
                            <?php } ?>
                            <th>CEP</th>
                            <th>Email</th>
                            <?if($login_fabrica == 151){?>
                                <th>Telefone</th>
                                <th>Telefone2</th>
                                <th>Telefone3</th>
                                <th>Celular</th>
                            <?}else{?>
                                <th>Telefone</th>
                            <?}?>
                            <? echo ($callcenter == true) ? "<th>Distância (KM)</th>" : ""; ?>
                            <th>Localizar</th>
                            <? echo ($callcenter == true) ? "<th>Rota</th>" : ""; ?>
                            <?php if (in_array($login_fabrica, array(169,170))){ ?>
                            <th>Datas não atendidas</th>
                            <?php } ?>
                        </tr>
                    </thead>
                    <tbody class="tbody">
                        <tr>
                            <th colspan="<?=($callcenter == true) ? 12 : 10?>" >Carregando Informações Aguarde...</th>
                        </tr>
                    </tbody>
                </table>
            </div>
            <?php
                // Inicializa o arquivo XLS
                if(in_array($login_fabrica, array(86, 81, 114))){
                    $caminho = "xls/relatorio-mapa-rede-$login_fabrica.xls";
            ?>
                    <div id='gerar_excel' style="margin: 0 auto; display: table; margin-top: 10px; margin-bottom: 10px;" class="btn_excel">
                        <span>
                            <img src='imagens/excel.png' />
                        </span>
                        <span class="txt">Gerar Arquivo Excel</span>
                    </div>
            <?php
                }
            ?>
        </div>
    </body>
    <script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
    <script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
    <script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.core.min.js"></script>
    <script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.widget.min.js"></script>
    <script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.effect.min.js"></script>
    <script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.tabs.min.js"></script>
    <script src="bootstrap/js/bootstrap.js"></script>
    <script src="plugins/leaflet/leaflet.js" ></script>
    <script src="plugins/leaflet/map.js" ></script>
    <script src="plugins/mapbox/geocoder.js?time=<?=time()?>"></script>
    <script src="plugins/mapbox/polyline.js"></script>
    <?php
    $plugins = array("datatable_responsive");
    include("plugin_loader.php"); 
    ?>
    <script type="text/javascript">
        var geocoder, latlon, c_lat, c_lon, bounds, map, markers_map;

        var markers            = [];
        var rotas              = [];
        var fabrica            = '<?=$login_fabrica?>';
        var callcenter         = '<?=$callcenter?>';
        var linha              = '<?=$linha;?>';
        var numeroConsumidor   = "<?=$numero;?>";
        var enderecoConsumidor = "<?=$endereco;?>";
        var cidadeConsumidor   = "<?=$consumidor_cidade;?>";
        var estadoConsumidor   = '<?=$consumidor_estado;?>';
        var bairroConsumidor   = '<?=$bairro;?>';
        var paisConsumidor     = '<?=$pais;?>';
        var cep_consumidor     = '<?=$_GET['cep']?>';
        var nome_consumidor    = '<?=$_REQUEST['nome']?>';
        var estado             = '<?=$estado; ?>';
        var cidade             = '<?=$cidade; ?>';
        var Map                = new Map("Maps");
        var Markers            = new Markers(Map);
        var Router             = new Router(Map);
        var Geocoder           = new Geocoder();
        var hd_chamado         = '<?=$hd_chamado?>';
        var produto            = '<?=$produto?>';
        var tipo_cliente       = '<?=$tipo_cliente?>';
        var tipo_posto         = '<?=$tipo_posto?>';

        $(function(){
            <?php if ($callcenter && !in_array($login_fabrica, array(169, 170))) { ?>
                $('#icon-blue').click(function(){
                    if (!$('#icon-blue').hasClass('img-selecionada')) {
                        $('#icon-red').removeClass('img-selecionada');
                        $('#icon-blue').addClass('img-selecionada');
                    }
                    Map.flyTo(c_lat, c_lon, 18);
                });
                $('#icon-red').click(function(){
                    if (!$('#icon-red').hasClass('img-selecionada')) {
                        $('#icon-blue').removeClass('img-selecionada');
                        $('#icon-red').addClass('img-selecionada');
                    }
                    $('#postos-autorizados').trigger('change');
                });
                $('#postos-autorizados').change(function(){
                    $('#grid_postos tr').each(function(){
                        $(this).removeClass('posto-selecionado');
                    });

                    if ($(this).val() == '') {
                        Markers.focus();
                    }else{
                        var lat = $('#'+$('#postos-autorizados :selected').val()).find('input[name=lat]').val();
                        var lng = $('#'+$('#postos-autorizados :selected').val()).find('input[name=lng]').val();

                        $('#'+$('#postos-autorizados :selected').val()).addClass('posto-selecionado');
                        Map.flyTo(lat, lng, 18);
                    }
                });
            <?php } ?>
            loadMaps();

            $('#gerar_excel').on('click', function(){
                window.open('<?=$caminho?>', '_blank');
            });
            $('#help_localizacao').tooltip();
        });

        function geocodeLatLon () {
            try {
                Geocoder.setEndereco({
                    endereco: enderecoConsumidor,
                    numero: numeroConsumidor,
                    bairro: bairroConsumidor,
                    cidade: cidadeConsumidor,
                    estado: estadoConsumidor,
                    pais: paisConsumidor,
                    cep: cep_consumidor
                });

                request = Geocoder.getLatLon();

                request.then(
                    function(resposta) {
                        c_lat  = resposta.latitude;
                        c_lon  = resposta.longitude;
                        latlon = c_lat+", "+c_lon;

                        var endereco = [];

                        if (typeof resposta.request_information.street != "undefined" && resposta.request_information.street.length > 0) {
                            endereco.push(resposta.request_information.street);
                        }

                        if (typeof resposta.request_information.neighborhood != "undefined" && resposta.request_information.neighborhood.length > 0) {
                            endereco.push(resposta.request_information.neighborhood);
                        }

                        if (typeof resposta.request_information.city != "undefined" && resposta.request_information.city.length > 0) {
                            endereco.push(resposta.request_information.city);
                        }

                        if (typeof resposta.request_information.region != "undefined" && resposta.request_information.region.length > 0) {
                            endereco.push(resposta.request_information.region);
                        }

                        $(".endereco-localizado").attr("link", Geocoder.last_url).text(endereco.join(", "));

                        geraRotaConsumidor();
                    },
                    function(erro) {
                        $('#div_table').html("<div class='alert alert-error'><h4>Erro ao buscar posto autorizado</h4></div>");
                    }
                );
            } catch(e) {
                $('#div_table').html("<div class='alert alert-error'><h4>Erro ao tentar inicializar o mapa da rede</h4></div>");
            }
        }

        function carrega_mapa_rede(dataAjax){
            $.ajax({
                url: 'mapa_rede_ajax.php',
                type: 'get',
                data: dataAjax,
                timeout: 14000
            }).fail(function(){
            }).done(function(data){
                data = data.split('*');
                
                if (data[1] !== undefined) {
                    /* Lista Informações dos Postos */
                    if(data[1].length > 0) {
                        $(".tbody").html(data[1]).find("tr.posto").each(function() {
                            var lat        = $(this).find("input[name=lat]").val();
                            var lng        = $(this).find("input[name=lng]").val();
                            var nome_posto = ($(this).find("td[rel=nome_posto]:last").text()).trim();
                            var id         = $(this).attr("id");

                            if (nome_posto == '') {
                                nome_posto = ($(this).find("td[rel=nome_posto]:first").text()).trim();
                            }

                            Markers.add(lat, lng, "red", nome_posto);
                            $('#postos-autorizados').append('<option value="'+id+'">'+nome_posto+'</option>');

                            markers.push([lat, lng, nome_posto]);
                        });

                        $('#grid_postos').DataTable({
                            responsive: true,
                            columnDefs: [
                                { responsivePriority: 1, targets: 0 },
                                { responsivePriority: 2, targets: 6 },
                                { responsivePriority: 3, targets: 7 },
                                { responsivePriority: 4, targets: 8 }
                            ],
                            ordering: false,
                            paging: false,
                            searching: false,
                            info: false,
                            language: { "zeroRecords": "Nenhum resultado encontrado" }
                        });

                        Markers.add(c_lat, c_lon, "blue", "Cliente");
                        Markers.render();
                        Markers.focus();

                        rotas = JSON.parse(data[2]);

                        var posto = $(".tbody").find("tr.posto:first").find("td[rel=rota]").data("id");
                        rota(posto,false);
                    }else{
                        if($.inArray(fabrica, ['169', '170']) !== -1 && dataAjax.extra == undefined){
                            dataAjax.extra = 'true';
                            carrega_mapa_rede(dataAjax);
                        }else{
				if (window.parent.produtosTrocaDireta != undefined && window.parent.produtosTrocaDireta.length > 0) {
                                	let counter = 0;
                                	let prodDescs = [];
                                	$.each(window.parent.produtosTrocaDireta, function (index, element) {
                                    		if (element.troca_direta == "t")
                                        		prodDescs.push(element.descricao);
                                	});

                                	if (prodDescs.length > 0) {
                                    		let messageProds = prodDescs.join(", ");
                                    		let mensagem = "";

                                    		if (prodDescs.length == 1) 
                                        		mensagem = `O produto ${messageProds} deverá ser recolhido para reparo ou proceder com a troca direta`;
                                    		else
                                        		mensagem = `Os produtos ${messageProds} deverão ser recolhidos para reparo ou proceder com a troca direta`;

                                    		$('#div_table').html(`<div class='alert alert-error'><h5>${mensagem}</h5></div>`);
                                	} else {
                                    		$('#div_table').html("<div class='alert alert-error'><h4>Nenhum posto localizado com o Endereço/CEP informado</h4></div>");
                                	}
                            } else {
                                $('#div_table').html("<div class='alert alert-error'><h5>Nenhum posto localizado com o Endereço/CEP informado</h5></div>");
                            }
                            
                            if (fabrica == 151) {
                                $("#Maps").slideUp(200);
                                $("#Maps").next().fadeOut(300);
                            }

                        }
                    }
                }else if(data !== undefined && !callcenter){
                    $(".tbody").html(data).find("tr.posto").each(function() {
                        var lat        = $(this).find("input[name=lat]").val();
                        var lng        = $(this).find("input[name=lng]").val();
                        var nome_posto = ($(this).find("td[rel=nome_posto]:last").text()).trim();
                        var id         = $(this).attr("id");

                        if (nome_posto == '') {
                            nome_posto = ($(this).find("td[rel=nome_posto]:first").text()).trim();
                        }

                        Markers.add(lat, lng, "red", nome_posto);
                    });
                    Markers.render();
                    Markers.focus();
                }else{
                    $('#div_table').html("<div class='alert alert-error'><h4>Erro ao buscar posto autorizado</h4></div>");
                }
            });
        }

        function geraRotaConsumidor() {
            if($.inArray(fabrica, ['52', '74']) !== -1){
                var dataAjax = {
                    latlon: latlon,
                    callcenter: callcenter,
                    linha: linha,
                    consumidor_estado: estadoConsumidor,
                    consumidor_cidade: cidadeConsumidor,
                    consumidor_bairro: bairroConsumidor,
                    km_format: false,
                };
            }else if ($.inArray(fabrica, ['169', '170']) !== -1){
                var dataAjax = {
                    latlon: latlon,
                    callcenter: callcenter,
                    linha: linha,
                    cep: cep_consumidor,
                    km_format: false,
                    hd_chamado: hd_chamado,
                };
            }else if ($.inArray(fabrica, ['189']) !== -1) {
                var dataAjax = {
                    latlon: latlon,
                    callcenter: callcenter,
                    linha: linha,
                    tipo_cliente: tipo_cliente,
                    tipo_posto: tipo_posto,
                    produto: produto,
                    cep: cep_consumidor,
                    km_format: false,
                };
            }else{
                var dataAjax = {
                    latlon: latlon,
                    callcenter: callcenter,
                    linha: linha,
                    cep: cep_consumidor,
                    km_format: false,
                };
            }

            Map.load();

            Map.map.on('click', function (elem) {
                if (!$('#icon-blue').hasClass('img-selecionada') && !$('#icon-red').hasClass('img-selecionada')) { return false; }

                Router.remove();
                Router.clear();

                if ($('#icon-blue').hasClass('img-selecionada')) {
                    c_lat = elem.latlng.lat.toFixed(7);
                    c_lon = elem.latlng.lng.toFixed(7);
                }else{
                    var posto_selecionado = '';
                    if ($('#postos-autorizados').val() !== '') {
                        posto_selecionado = $('#postos-autorizados option:selected').text();
                        var endereco = $('#'+$('#postos-autorizados').val()).find('td[rel=endereco]').text();
                    }
                }
                Markers.remove();
                Markers.clear();
                for (var i = 0; i < markers.length; i++) {
                    if (posto_selecionado == markers[i][2] && $('#postos-autorizados').val() !== '') {
                        markers[i][0] = elem.latlng.lat.toFixed(7);
                        markers[i][1] = elem.latlng.lng.toFixed(7);

                        $('#'+$('#postos-autorizados').val()).find('td a').each(function(){
                            if ($(this).text() == 'Localizar') {
                                $(this).attr('href', "javascript: localizar('"+markers[i][0]+"', '"+markers[i][1]+"', '"+endereco+"')");
                            }
                        });
                        $('#'+$('#postos-autorizados').val()).find('input[name=lat]').val(markers[i][0]);
                        $('#'+$('#postos-autorizados').val()).find('input[name=lng]').val(markers[i][1]);
                    }
                    Markers.add(markers[i][0], markers[i][1], "red", markers[i][2]);
                }
                Markers.add(c_lat, c_lon, "blue", nome_consumidor);
                Markers.render();
            });
            
            carrega_mapa_rede(dataAjax);
        }

        function loadMaps() {
            if (callcenter) {
                geocodeLatLon();
            }else{
                var dataAjax = "pais="+paisConsumidor+"&estado="+estado+"&cidade="+cidade;
                if (fabrica == 117) {
                    dataAjax += "&linha="+linha;
                }
                Map.load();

                carrega_mapa_rede(dataAjax);
            }
        }

        function rota(tr_id,altera_km=true){
            Router.remove();
            Router.clear();

            var lat_posto = $('#'+tr_id).find('input[name=lat]').val();
            var lng_posto = $('#'+tr_id).find('input[name=lng]').val();

            $.ajax({
                url: '../controllers/TcMaps.php',
                method: "GET",
                data: { 'ajax': 'route', 'ida_volta': 'sim', 'destino': lat_posto+','+lng_posto, 'origem': c_lat+','+c_lon },
                timeout: 9000
            }).fail(function(){
                alert('Não foi possível calcular a rota solicitada, tente novamente mais tarde');
            }).done(function(data){
                data = JSON.parse(data);

                $('#'+tr_id+' td[rel=nome_posto] a').attr('onclick', 'window.parent.informacoesPosto('+tr_id+', null, "", "", "", "",'+data.total_km+');window.parent.Shadowbox.close();');
                Router.add(Polyline.decode(data.rota.routes[0].geometry));
                Router.render();
            });
        }

        /* Localizar */
        function localizar (lat, lng, endereco, id){
            Map.flyTo(lat, lng, 18);
        }

        function visualizarTodos() {
            Markers.focus();
        }
    </script>
</html>
