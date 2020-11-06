<?php
require_once('../../admin/dbconfig.php');
require_once('../../admin/includes/dbconnect-inc.php');
require_once('../../admin/funcoes.php');

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

            
            const fabrica = <?=$cod_fabrica?>;

            $(function() {
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
                
                $("#btn_pesquisa_os").click(function(){
                    $("#consulta_os").show();
                    $("#consulta_mapa_rede").hide();
                    $("#lista_posto").html("");
                    $("#cep").val("");

                    $('#familia').val("").trigger('change');
                    $("#linha").val("").trigger('change');
                    $('#familia').find('option:first').nextAll().remove();
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
        
        <div class="container-fluid" id='consulta_os'>
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
                            <!-- <button class="btn btn-default btn-sm" type='button' id='btn_pesquisa_posto'">Pesquisar Assistência</button> -->
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
