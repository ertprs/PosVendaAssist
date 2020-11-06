var InstitucionalController = angular.module("InstitucionalController", ["Auth", "GoogleMaps", "ModalLoading"])

.config(function($httpProvider) {
    $httpProvider.defaults.useXDomain = true;
    delete $httpProvider.defaults.headers.common['X-Requested-With'];
});

InstitucionalController.controller("StatusOsController", function($scope, $http, token , ModalLoadingProvider) {

    $("#msgErro, #result").hide();

    $scope.submit = function () {
        $("#msgErro, #result").hide();

        var msgErro = [];

        if ($scope.data == undefined || $scope.data.os == undefined || $scope.data.os.length == 0) {
            msgErro.push("Informe o número da ordem de serviço");
        }

        if ($scope.data != undefined && $scope.data.cpf_cnpj != undefined && $scope.data.cpf_cnpj.length > 0) {
            var tipo_pessoa = ($("#cpf_cnpj").val().length <= 14) ? "cpf" : "cnpj";
            console.debug($("#cpf_cnpj").val(), $scope.data.cpf_cnpj);
            
            if (tipo_pessoa == "cpf") {
                var regex = /[0-9]{3}\.[0-9]{3}\.[0-9]{3}\-[0-9]{2}/;
            } else {
                var regex = /[0-9]{2}\.[0-9]{3}\.[0-9]{3}\/[0-9]{4}\-[0-9]{2}/;
            }

            if (!$("#cpf_cnpj").val().match(regex)) {
                msgErro.push(angular.uppercase(tipo_pessoa)+" inválido");
            }

            var cpfEmpty = false;
        } else {
            var cpfEmpty = true;
        }

        if ($scope.data != undefined && $scope.data.email != undefined && $scope.data.email.length > 0) {
            if (!$scope.data.email.match(/^[^@]+\@([a-zA-Z0-9-]+\.)+[a-zA-Z0-9-]+$/)) {
                msgErro.push("Email inválido");
            }

            var emailEmpty = false;
        } else {
            var emailEmpty = true;
        }

        if (cpfEmpty === true && emailEmpty === true) {
            msgErro.push("Informe o CPF ou Email");
        }

        $scope.data.recaptcha_response_field  = grecaptcha.getResponse();

        if (data.recaptcha_response_field.length == 0){
            msgErro.push("Resolva o reCaptcha");
        }

        if (msgErro.length > 0) {
            $("#msgErro").html(msgErro.join("<br />")).show();
        } else {
            
            $("button").button("loading");

            var data = [];

            $.each($scope.data, function(key, value) {
                if (value.length > 0) {
                    if (key == "cpf_cnpj") {
                        value = value.replace(/\.|\-|\//g, "");
                    }
                    data.push(key+"/"+value);
                    //data.push(key+"/"+encodeURIComponent(value));
                }
            });

            data.push("token/"+token);
        ModalLoadingProvider.showModal();
        var urlCrossDomainProxy = "http://devel.telecontrol.com.br/~otavio/institucional-frontend/crossDomainProxy.php";
        var apiLink = "http://api2.telecontrol.com.br/institucional/statusos/"+data.join("/")
            urlCrossDomainProxy += "?apiLink="+apiLink;
            $http({
                url: urlCrossDomainProxy,
                method: "GET",
                timeout: 10000
            })
            .success(function(response) {

                ModalLoadingProvider.hideModal();
                if(response.exception != undefined){
                    if (response.message.match(/reCAPTCHA/g)) {
                        response.message = "reCaptcha incorreto";

                    } 
                    $("#msgErro").text(response.message).show();
                    
                    $("button").button("reset");
                    Recaptcha.reload();

                }else{
                    $("#result").find("h3").text("Ordem de serviço: "+response.os);
                    $("#result").find("li[rel=status]").html("<b>Status</b>: "+response.status);
                    $("#result").find("li[rel=posto]").html("<b>Posto autorizado</b>: "+response.entity.posto_autorizado);
                    $("#result").find("li[rel=consumidor_revenda]").html(((response.entity.consumidor_revenda == "R") ? "<b>Revenda</b>" : "<b>Consumidor</b>")+": "+((response.entity.consumidor_revenda == "R") ? response.entity.revenda_nome : response.entity.consumidor_nome));
                    $("#result").find("li[rel=produto]").html("<b>Produto</b>: "+response.entity.descricao_produto);
                    $("#result").show();
                    $("button").button("reset");

                    Recaptcha.reload();
                }
            })
            .error(function(data, status, header, config) {

                ModalLoadingProvider.hideModal();
                if (data != undefined && data.exception) {
                    if (data.message.match(/reCAPTCHA/g)) {
                        data.message = "reCaptcha incorreto";
                    }

                    $("#msgErro").text(data.message).show();
                } else {
                    $("#msgErro").text("Erro ao consultar Os").show();                    
                }


                $("button").button("reset");
                Recaptcha.reload();
            });
        }

    };
});

InstitucionalController.controller("MapaRedeController", function($scope, $http,$compile, mapsInit, ModalLoadingProvider) {
    $("#msgErro").hide();


    $scope.verifica = function(){
console.log($scope.cidade);    
    }
    var urlProducao = "http://api2.telecontrol.com.br/";
    var lastInfoWindow = null;
        var maps=null;
        $scope.mapsInit = mapsInit;
        $scope.mapsInit.travelMode = "DRIVING";
       ModalLoadingProvider.setMessage("Carregando..."); 
        $scope.getMacroLinha = function(){

            ModalLoadingProvider.showModal();

            $("#msgErro").html("").hide();
            $http({
                url: urlProducao+"posvenda/macrolinha/",
                method: "GET",  
                timeout: 10000
            }).success(function(response){
                $scope.macroLinhas = response;           
                ModalLoadingProvider.hideModal();
            });

        }

        $scope.getCidadesAtendidas = function(){
            
            
            $("#msgErro").html("").hide();
            if($scope.estado != null && $scope.macroLinha != null){
                $scope.cidades = $scope.mapsInit.getCidades($http, $scope.estado, $scope.macroLinha);
console.log($scope.cidades);
            }
        }

        $scope.submit = function(){

            $("#msgErro").html("").hide();
            ModalLoadingProvider.showModal();
            $http({
                url: urlProducao+"institucional/postosatendem/ibge/"+$scope.cidade+"/macrolinha/"+$scope.macroLinha+"/fabrica/117/token/churrus",
                method: "GET",
                timeout: 10000
            }).success(function(response){
                ModalLoadingProvider.hideModal();
                maps = $scope.mapsInit.getMap();

                var bounds = new google.maps.LatLngBounds();                   

                angular.forEach(response,function(dadosPosto, key){

                    var markerLatLng = new google.maps.LatLng(   dadosPosto.localizacao.longitude, dadosPosto.localizacao.latitude);
                    bounds.extend(markerLatLng);

                    var marker = new google.maps.Marker({

                        position: markerLatLng,
                        map: maps,
                        title: dadosPosto.posto
                    });
                    if(infowindow != null){
                        infowindow.close();
                    }
                    
                    var infowindow = new google.maps.InfoWindow({
                        content: $scope.mapsInit.getInfoWindowContent(dadosPosto) 
                    });
                    google.maps.event.addListener(infowindow, 'domready' , function(){
                        $(document).on("click", ".calc", function(){

                            $scope.mapsInit.calcRoute(null,null);

                        });
                    });
                    google.maps.event.addListener(marker, 'click', function() {

                        if(lastInfoWindow != null){
                            lastInfoWindow.close()
                        }
                        lastInfoWindow = infowindow;
                        infowindow.open(maps, this);
                        $scope.mapsInit.destination = marker.position;
                    }); 
                    maps.controls[google.maps.ControlPosition.TOP_RIGHT].push(document.getElementById("inputs_container"));
                })
                
                $scope.submited = true;    
                maps.fitBounds(bounds);
            }).error(function(response){
                
                $("#msgErro").html(response.responseJSON.message).show();
            });

        }
});
