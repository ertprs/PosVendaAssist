
$("#msgErro").hide();
var urlProducao = "http://api2.telecontrol.com.br/";
var lastInfoWindow = null;
var maps = null;
mapsInit.travelMode = "DRIVING";
loadingProvider.setMessage("Carregando...");
getMacroLinha = function (fnCallback) {

    loadingProvider.show();

    $("#msgErro").html("").hide();
    $.ajax({
        url: 'crossDomainProxy.php',
        data: {
            apiLink: urlProducao + "institucional/linha/fabrica/117"
        },
        type: "GET",
        async: false
    }).success(function (response) {
        loadingProvider.hide();
        fnCallback(response);
    });

}

var getCidadesAtendidas = function () {
    var estado = $("#estado").val();
    var macroLinha = $("#macro_linha").val();
    $("#msgErro").html("").hide();
    if (estado != '' && macroLinha != '') {
        loadingProvider.show();
        $.ajax({
            url: 'crossDomainProxy.php',
            data: {
                apiLink: urlProducao + "institucional/cidadespostoatende/estado/" + estado + "/macrolinha/" + macroLinha + "/fabrica/117/token/churrus"
            },
            type: "GET",
            timeout: 10000,
            async: false
        }).success(function (response) {

            loadingProvider.hide();
            if (response != null) {

                var combo = $("#cidades");
                combo.html('');
                $(response).each(function (i, cidade) {
                    var option = $("<option>").attr({
                        value: cidade.ibge
                    }).html(cidade.cidade);
                    combo.append(option);

                });

            } else {
                alert("Não foi encontrado nenhuma cidade que atenda esta linha neste estado");
            }
        }).error(function (response) {
            loadingProvider.hide();
            $("#cidades").html("<option>Selecione uma cidade </option>");
            $("#map_container").hide();
            $("#list-postos-container").hide();
            $("#msgErro").html(response.responseJSON.message).show();
        });

    }
}

loadEstados = function () {
    $(mapsInit.estados).each(function (i, estado) {
        var combo = $("#estado");
        var option = $("<option>").attr({
            value: estado.value
        }).html(estado.descricao);
        combo.append(option);

    });
}
var getPostos = function () {

    $("#msgErro").html("").hide();

    $("#list-postos-container").hide();

    loadingProvider.show();
    var cidade = $("#cidades").val();
    var macroLinha = $("#macro_linha").val();

    $.ajax({
        url: 'crossDomainProxy.php',
        data: {
            apiLink: urlProducao + "institucional/postosatendem/ibge/" + cidade + "/macrolinha/" + macroLinha + "/fabrica/117/token/churrus"
        },
        type: "GET",
        timeout: 10000
    }).success(function (response) {
        loadingProvider.hide();
        var maps = mapsInit.getMap();
        $("#map_container").show();
        
        $("#list-postos-container").html("");
        var bounds = new google.maps.LatLngBounds();

        $(response).each(function (i, dadosPosto) {

            var markerLatLng = new google.maps.LatLng(dadosPosto.localizacao.longitude, dadosPosto.localizacao.latitude);
            bounds.extend(markerLatLng);

            var marker = new google.maps.Marker({
                position: markerLatLng,
                map: maps,
                title: dadosPosto.posto
            });
            if (infowindow != null) {
                infowindow.close();
            }

            var infowindow = new google.maps.InfoWindow({
                content: mapsInit.getInfoWindowContent(dadosPosto)
            });

            google.maps.event.addListener(marker, 'click', function () {

                if (lastInfoWindow != null) {
                    lastInfoWindow.close()
                }
                lastInfoWindow = infowindow;
                infowindow.open(maps, this);
                mapsInit.destination = marker.position;
            });
            maps.controls[google.maps.ControlPosition.TOP_RIGHT].push(document.getElementById("inputs_container"));

            $("#list-postos-container").append(mapsInit.getListContent(dadosPosto));
            $("#list-postos-container").show();
        });
        $("#list-postos-container").show()
        $("#directionsPanel").hide();
        submited = true;
        maps.fitBounds(bounds);
    }).error(function (response) {

        $("#msgErro").html(response.responseJSON.message).show();
    });

}
$(function () {
    $("#maparede_form").submit(function () {
        return false;
    });
    loadEstados();
    var macroLinhas = getMacroLinha(function (macroLinhas) {
        $(macroLinhas).each(function (i, macroLinha) {
            var combo = $("#macro_linha");
            var option = $("<option>").attr({
                value: macroLinha.macro_linha
            }).html(macroLinha.descricao);
            combo.append(option);
        });
    });


});


