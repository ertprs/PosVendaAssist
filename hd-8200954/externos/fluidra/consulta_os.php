<!DOCTYPE html>
<html xmlns:ng="http://angularjs.org" >
    <head>
        <meta charset="iso-8859-1" />
        <title>Telecontrol Institucional</title>
        <!-- jQuery -->
        <script type="text/javascript"  src="../institucional/lib/jquery/jquery.min.js"></script>

        <!-- Bootstrap -->
        <link rel="stylesheet" type="text/css" href="../institucional/lib/bootstrap/css/bootstrap.min.css" />
        <script src="../institucional/lib/bootstrap/js/bootstrap.min.js"></script>

        <style>
            #loading{
               height: 35px;
               width: 200px;
            }
            #spanContainer{
                width:100px;
                height: 40px;
                display:none;
            }
            #map {
                height:600px;
                width:600px;
            }
            .infoWindowContent {
                font-size:  14px !important;
                border-top: 1px solid #ccc;
                padding-top: 10px;
            }
            h2 {
                margin-bottom:0;
                margin-top: 0;
            }
            #link, #link a{
                color:#ffffff;
            }
            @keyframes spin {
                0% {
                    -webkit-transform: rotate(0deg);
                    transform: rotate(0deg);
                }
                100% {
                    -webkit-transform: rotate(359deg);
                    transform: rotate(359deg);
                }
            }
            .spin{
                -webkit-animation: spin 1000ms infinite linear;
                animation: spin 1000ms infinite linear;
            }
            .txt_titulo_principal{
                color:#056f7d; 
                font-size:28px; 
                text-align: center;
            }
            .txt_subtitulo_principal{
                color: #989898;
                font-size: 16px;
                text-align: center;
                padding-bottom: 40px;
            }
            .btn_pesquisar{
                border: solid 1px #2f2f2f;
                background: #056f7d;
                color: #ffffff;
                font-size: 13px;
                font-weight: bold;
                padding: 10px 35px;
                text-align: center;
                cursor: pointer;
                border-radius: 5px;
            }
            .btn_pesquisar:hover{
                border: solid 1px #056f7d;
                background: #2f2f2f;
                color: #ffffff;
                font-size: 13px;
                font-weight: bold;
                padding: 10px 35px;
                text-align: center;
                cursor: pointer;
                border-radius: 5px;
            }
            .txt_label{
                color: #989898;
                font-size: 16px;
                font-weight: normal;
            }
            button i {
                color: #ffffff;
            }
        </style>

        <script src="../institucional/js/auth.js"></script>
    </head>
    <body>
        <div class="container" id="ng-app"  >
            <div class='col-md-12'>
                <div class='row'>
                    <br/>
                    <div id="msgErro" class="alert alert-danger" style="display: none;" ></div>
                </div>
            </div>
            <div>
                <script src='https://www.google.com/recaptcha/api.js?hl=pt-BR&onload=showRecaptcha&render=explicit' async defer></script>
                <script src="../institucional/lib/mask/mask.min.js" ></script>

                <script type="text/javascript">

                    var showRecaptcha = function() {
                        grecaptcha.render('reCaptcha', {
                            'sitekey' : '6LckVVIUAAAAAEQpRdiIbRSbs_ePTTrQY0L4959J'
                        });
                    };

                    $(function () {
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
                        //showRecaptcha();
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

                    function timeButton(){
                        $("button").button("loading").html("<i class='glyphicon glyphicon-refresh spin'></i> Consultando...");
                        var a = setTimeout(function(){
                            if(retorno_ajax == "true"){
                                clearInterval(b);
                                clearInterval(c);
                                return false;
                            }
                            $("button").button("loading").html("<i class='glyphicon glyphicon-refresh spin'></i> Consultando...");

                        }, 5000);

                        var b = setTimeout(function(){
                            if(retorno_ajax == "true"){
                                clearInterval(c);
                                return false;
                            }
                            $("button").button("loading").html("<i class='glyphicon glyphicon-refresh spin'></i> Ainda consultando....");

                        }, 10000);

                        var c = setTimeout(function(){
                            if(retorno_ajax == "true"){
                                return false;
                            }
                            $("button").button("loading").html("<i class='glyphicon glyphicon-refresh spin'></i> Continua consultando.....");
                        }, 20000);
                    }

                    var consulta = function(){
                        var msgErro = [];
                        var data = {};
                        var inputOS = $('#os');
                        var inputCpfCnpj = $('#cpf_cnpj');
                        var ip = pegaIp();
                        data.userIpAddress = ip;
                        data.os = inputOS.val();
                        data.cpf_cnpj = inputCpfCnpj.val();
                        data.recaptcha_response_field = grecaptcha.getResponse();

                        if (data.os.length == 0) {
                            msgErro.push("Insira o número da Ordem de Serviço.");
                        }

                        if (data.cpf_cnpj.length == 0) {
                            msgErro.push("Insira o CPF/CNPJ.");
                        }

                        if (data.recaptcha_response_field.length == 0) {
                            msgErro.push("Preencha o ReCaptcha");
                        }

                        if (data.cpf_cnpj.length > 0 &&
                            !data.cpf_cnpj.match(/^[0-9]{3}\.[0-9]{3}\.[0-9]{3}-[0-9]{2}$/) &&
                            !data.cpf_cnpj.match(/^[0-9]{2}\.[0-9]{3}\.[0-9]{3}\/[0-9]{4}-[0-9]{2}$/)) {
                            msgErro.push('CPF/CNPJ Inválido');
                        }

                        if (msgErro.length > 0) {
                            $("#msgErro").html(msgErro.join("<br />")).show().focus();
                            return;
                        }

                        data.cpf_cnpj = data.cpf_cnpj.replace(/[./-]+/gi,'');

                        var documento = "";
                        var os = "";

                        if(data.cpf_cnpj.length > 0){
                            var documento = "/documento/"+data.cpf_cnpj;
                        }
                        if(data.os.length > 0){
                            var os = "/os/"+data.os;
                        }

                        var url = 'consultaOs'+documento+os+'/recaptcha_response_field/'+data.recaptcha_response_field+'/ip_address/'+data.userIpAddress;

                        retorno_ajax = 'false';

                        //$("button").button("loading");
                        $("#msgErro").html("").hide();
                        $("#result").hide();

                        $.ajax({
                            url : '../institucional/request.php',
                            data : {
                                'fabrica' : 'fluidra',
                                'url' : url
                            },
                            method : 'POST',
                            beforeSend: function() {
                                timeButton();
                            },
                            success : function(data){
                                retorno_ajax = 'true';

                                data = JSON.parse(data);
                                if(data.exception){
                                    $("#msgErro").text(data.message).show();
                                    return;
                                }
                                showOs(data);
                            },
                            error : function(data){
                                $("#msgErro").text(data.responseJSON.message).show();
                            },
                            complete : function(data){
                                $("button").button("reset");
                                grecaptcha.reset();
                                //Recaptcha.reload();
                            }
                        });
                    };

                    var showOs = function(data){
                        //data = JSON.parse(data);
                        $("#result").html('');
                        $.each(data, function(key, value) {

                            console.log(value)
                            if (value.consumidor_revenda == 'R') {
                                var c_revenda = "<b>Revenda: </b>"+value.revenda_nome;
                            } else {
                                var c_revenda = "<b>Consumidor: </b>"+value.consumidor_nome;
                            }

                            var linha = $("#clone").clone();
                            $(linha).find("h3").text("Ordem de serviço: "+ value.sua_os);
                            $(linha).attr('rel',value.os);
                            var xsolucao = (value.solucao == "" || value.solucao == undefined) ? "" : value.solucao;
                            var xserie = (value.serie == "" || value.serie == undefined) ? "" : value.serie;
                            $(linha).find("li[rel=status]").html("<b>Status</b>: "+ value.status_checkpoint_desc);
                            $(linha).find("li[rel=solucao]").html("<b>Solução</b>: "+ xsolucao);
                            $(linha).find("li[rel=posto]").html("<b>Posto autorizado</b>: "+value.posto_autorizado);
                            $(linha).find("li[rel=consumidor_revenda]").html(((value.consumidor_revenda == "R") ? "<b>Revenda</b>" : "<b>Consumidor</b>")+": "+((value.consumidor_revenda == "R") ? value.revenda_nome : value.consumidor_nome));
                            $(linha).find("li[rel=produto]").html("<b>Produto:</b> "+value.referencia_produto+" - "+value.descricao_produto+" <b>Número de Série</b>: "+xserie);

                            $("#result").show();
                            $("#result").append($(linha).html());
                        });
                    };
                </script>

                    <div class='container'>
                    <h1 class="txt_titulo_principal">Consultar Ordem de Serviço</h1>
                    <p class="txt_subtitulo_principal">Aqui você pode acompanhar o andamento de sua solicitação/ordem de serviço aberta para o seu produto em nossa rede de Serviço Técnico Autorizado.<br/>
                    <div class="container">
                        <form name="statusos_form" role="form" novalidate >
                            <div class="row">
                                <div class="col-xs-12 col-sm-12 col-md-3">
                                    <div class="form-group row">
                                        <label for="os" class="col-xs-12 col-sm-12 col-md-12 txt_label" > Número da Ordem de Serviço</label>
                                        <div style="margin-left: -29px;margin-top: 10px;" class="col-xs-1 col-sm-1 col-md-1">
                                            <span class='asterisco'>*</span>
                                        </div>
                                        <div class="col-xs-11 col-sm-11 col-md-3">
                                            <input type="text"  id="os" name="os" class="form-control" style="width:200px" maxlength="9" />
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xs-12 col-sm-12 col-md-3">
                                    <div class="form-group row">
                                            <label class="col-xs-12 col-sm-12 col-md-12 txt_label" for="cpf_cnpj" >
                                                CPF / CNPJ
                                            </label>
                                        <div style="margin-top: 10px;margin-left: -29px;" class="col-xs-1 col-sm-1 col-md-1"><span class='asterisco'>*</span></div>
                                        <div class="col-xs-11 col-sm-11 col-md-3">
                                            <input type="text"  name="cpf_cnpj" id="cpf_cnpj" class="form-control"  style="width:200px"/>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xs-12 col-sm-12 col-md-12">

                                    <div class="form-group row">
                                        <div class='container' id='reCaptcha'>
                                        </div>
                                    </div>
                                     <button onClick="consulta()" class="btn_pesquisar" type="button" data-loading-text="Consultando..." >
                                        Consultar
                                    </button>
                                </div>
                            </form>
                        </div>
                        <div id='clone' style="display: none;">
                            <div class="panel panel-primary">
                                <div class="panel-heading">
                                    <h3 class="panel-title"></h3>
                                    <h5><a href='#' Onclick="fnc_contatoFabricante()" id="link"></a></h5>
                                </div>
                                <div class="panel-body" style="padding: 0px;">
                                    <ul class="list-group" style="margin-bottom: 0px;">
                                        <li class="list-group-item" rel="status"></li>
                                        <li class="list-group-item" rel="solucao"></li>
                                        <li class="list-group-item" rel="posto"></li>
                                        <li class="list-group-item" rel="consumidor_revenda"></li>
                                        <li class="list-group-item" rel="produto"></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="" >
                            <div class="row">
                                <div class="col-md-12">
                                    <div id="resultado"></div>
                                    <div id="result" class="" style="display: none; margin-top: 10px; margin-bottom:0px; width:625px">

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
        </div>
    </body>
</html>
