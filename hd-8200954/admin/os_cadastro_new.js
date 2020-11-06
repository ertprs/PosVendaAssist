$.getScript("plugins/endereco.js");
$.getScript("https://maps.googleapis.com/maps/api/js?v=3.exp&callback=apiLoaded&sensor=false&async=2&language=pt-br&key=AIzaSyC5AsH3NU-IwraXqLAa1qbXxyjklSVP-cQ");


$(document).on('empty','.AutoList',function(){
	$(this).autoListAdd();
});

$(document).on('click','.ClearButton',function(){
	var div = $($(this).parents('.tc_formulario')[0]);
	if(div.find("input[value!=''][name$='][produto]']").length == 0)
		return;
	$(div).find("input[name$='][produto]']").val('').trigger('change');
});

$(document).on('change',"[name$='[peca]']",function(){
	var peca = $(this).val();
	var elementList = $($(this).parents('[list-index]')[0]);
	if(!peca){
		elementList.find("input[name$='[referencia]']").removeAttr("readonly").next().show();
		elementList.find("input[name$='[descricao]']").removeAttr("readonly").next().show();
		elementList.find("select[name$='[defeito]'] option").remove();
		elementList.find("input[name$='[qtde]']").val('');
		return;
	}
	elementList.find("input[name$='[referencia]']").attr({ readonly: "readonly" }).next().hide();
	elementList.find("input[name$='[descricao]']").attr({ readonly: "readonly" }).next().hide();

	var select = elementList.find("select[name$='[defeito]']");
	var value = select.attr('value');
	buscaDefeitoPeca(peca,select);
	if(value)
		select.val(value);
});

$(document).on('change',"[name$='][produto]']",function(){
	var produto = $(this).val();
	var div = $($(this).parents('.tc_formulario')[0]);
	if(!produto){
		$(div).find("input[name$='][referencia]']").removeAttr("readonly").next().show();
		$(div).find("input[name$='][descricao]']").removeAttr("readonly").next().show();
		$(div).find("input[name$='][voltagem]']").removeAttr("readonly");
		$(div).find("input,select,texarea").val('').children('option').remove();
		return;
	}
	$(div).find("input[name$='][referencia]']").attr({ readonly: "readonly" }).next().hide();
	$(div).find("input[name$='][descricao]']").attr({ readonly: "readonly" }).next().hide();
	$(div).find("input[name$='][voltagem]']").attr({ readonly: "readonly" });
	buscaDefeitoConstatado(produto, $(div).find("select[name$='][defeito_constatado]']"));
});

$(document).on('change',"[name$='[0][produto]'][value='']",function(){
	$('#produto-lista-item').autoListReset();
	$('.SubprodutoForm').hide();
	$("input[name='os[os_produto][1][produto]']").val('').trigger('change');
	$('.ProdutoClearButton').hide();
	$('#solucao').val('').children('option').remove();
});

$(document).on('change',"[name$='[0][produto]'][value!='']",function(){
	var produto = $(this).val();
	var select = $('#solucao');
	buscaSolucaoProduto(produto,select);
	$('.ProdutoClearButton').show();
});

$(document).on('change',"[name$='[1][produto]'][value!='']",function(){
	$('.SubprodutoClearButton').show();
});

$(document).on('change',"[name$='[1][produto]'][value='']",function(){
	$('#subproduto-lista-item').autoListReset();
	$('.SubprodutoClearButton').hide();

});

/*$(document).on('change',"[value!=''][name$='[revenda_cnpj]']",function(){
	var div = $($(this).parents('.tc_formulario')[0]);
	div.find("[name$='[revenda_nome]']").attr({ readonly: "readonly" }).next().hide();
	$(this).attr({ readonly: "readonly" }).next().hide();
});

$(document).on('change',"[value=''][name$='[revenda_cnpj]']",function(){
	var div = $($(this).parents('.tc_formulario')[0]);
	div.find("[name$='[revenda_nome]']").removeAttr("readonly").next().show();
	$(this).removeAttr("readonly").next().show();
});*/

function apiLoaded () {
	$.getScript("http://google-maps-utility-library-v3.googlecode.com/svn/tags/markermanager/1.1/src/markermanager.js");
}

$(window).load(function () {
	$("#data_abertura").datepicker({ minDate: -6, maxDate: 0 }).mask("99/99/9999").change(function () {
		var data = $(this).datepicker("getDate");

		if (data < $("#data_nf").datepicker("getDate") || $("#data_nf").datepicker("getDate") == null) {
			$("#data_nf").datepicker("destroy");
			$("#data_nf").datepicker({ maxDate: data });
			$("#data_nf").datepicker("refresh");
			$("#data_nf").val("");
		}
	});

	$("#data_nf").datepicker({ maxDate: 0 }).mask("99/99/9999");
	$("#qtde_km").priceFormat({
		prefix: '',
		thousandsSeparator: '',
		centsSeparator: '.'
	});

	Shadowbox.init();
	$("#consumidor_cpf").mask("999.999.999-99");
	$("#consumidor_cep, #revenda_cep").mask("99.999-999");
	$("#revenda_cnpj").mask("99.999.999/9999-99");
	$("#consumidor_fone, #revenda_fone").numeric({ allow: "()- " });

	$("#consumidor_estado").change(function () {
		_buscaCidade($(this).val(), $("#consumidor_cidade"));
	});
	if($("#consumidor_estado").val()){
		_buscaCidade($("#consumidor_estado").val(), $("#consumidor_cidade"));
	}

	$("#revenda_estado").change(function () {
		_buscaCidade($(this).val(), $("#revenda_cidade"));
	});
	if($("#revenda_estado").val()){
		_buscaCidade($("#revenda_estado").val(), $("#revenda_cidade"));
	}

	if($("#posto").val()){
		$("#posto_nome").attr({ readonly: "readonly" }).next().hide();
		$("#posto_codigo").attr({ readonly: "readonly" }).next().hide();

		$("div[name=div_remover_posto]").show();	
	}

	$("#consumidor_cep").blur(function () {
		_buscaCEP($(this).val(), $("#consumidor_endereco"), $("#consumidor_bairro"), $("#consumidor_cidade"), $("#consumidor_estado"));
	});

	$("#revenda_cep").blur(function () {
		_buscaCEP($(this).val(), $("#revenda_endereco"), $("#revenda_bairro"), $("#revenda_cidade"), $("#revenda_estado"));
	});

	$("input[name^='os[os_produto]'][name$='[produto]'][value!='']").each(function(){
		var div = $(this).parents('.tc_formulario')[0];
		var name = $(this).attr('name').replace("[produto]","[defeito_constatado]");
		var produto = $(this).val();
		var value = $(div).find("select[name='"+name+"']").attr('value');
		console.debug($(this));
		buscaDefeitoConstatado(produto,$(div).find("select[name='"+name+"']"));
		$(div).find("select[name='"+name+"']").val(value);
	});

	$("input[type=hidden][name$='[peca]'][value!='']").each(function(){
		var peca = $(this).val();
		var elementList = $(this).parents('[list-index]')[0];
		$(elementList).find("[name$='[referencia]']").attr({ readonly: "readonly" }).next().hide();
		$(elementList).find("[name$='[descricao]']").attr({ readonly: "readonly" }).next().hide();
		var select = $($(elementList).find("select[name$='[defeito]']")[0]);
		var value = select.attr('value');
		buscaDefeitoPeca(peca,select);
		select.val(value);
	});

	if($(".SubprodutoForm input[type=hidden][name$='[produto]'][value!='']").length > 0){
		$(".SubprodutoForm").show();
	}

	$(document).on("click", "span[rel=lupa]", function () {
		$("input[name='lupa_config'][posicao_produto]").each(function(){
			console.debug($(this));
			var posicao_produto =$(this).attr('posicao_produto');
			var produto = $("input[name='os[os_produto]["+posicao_produto+"][produto]']").val();
			$(this).attr('produto',produto);
		});
		$.lupa($(this), ["posicao", "posicao_produto", "ativo", "produto","cnpj_not_null","produtoPai","temSubproduto"]);
	});

	$("#tipo_atendimento").change(function () {

		if ($(this).find("option:selected").attr("km_google") == "true") {

			$('#GoogleMapsDirection').html('<br /> <div class="alert alert-block alert-success" style="width: 80%; margin: 0 auto;">Clique no Botão <strong>Calcular KM</strong> para realizar a Rota</div>');

			$("#div_km_google").show();
			$("#box_calcular_km").show();

			if (map == null) {
				loadGoogleMaps();
			}
		} else {
			$("#div_km_google").hide();
			$("#qtde_km").val("");
		}
	});

	$("#calcular_km").click(function () {
		validaCamposMapa();
		calculaKM();
	});

	$("button[name=remover_posto]").click(function () {
		$("#posto").val("");
		$("#posto_latitude").val("");
		$("#posto_longitude").val("");
		$("#posto_codigo").val("").removeAttr("readonly").next().show();
		$("#posto_nome").val("").removeAttr("readonly").next().show();

		$("div[name=div_remover_posto]").hide();

		if (posto_marker != null) {
			posto_marker.setMap(null);
		}

		if (consumidor_marker != null) {
			consumidor_marker.setMap(null);
		}

		if (directionsRenderer != null) {
			directionsRenderer.setMap(null);
		}

		$('#GoogleMapsDirection').html("");
		$("#qtde_km").val("");
	});

	$(document).on("click", "button[name=lista_basica]", function () {
		var posicao_produto = $(this).attr('produto');
		produto = $("input[name$='["+posicao_produto+"][produto]']").val();
		if(!produto){
			alert("Selecione um produto");
			return;
		}
		Shadowbox.open({
			content: "lista_basica_lupa_new.php?produto="+produto+"&posicao_produto="+posicao_produto,
			player: "iframe",
			width: 850,
			height: 600
		});
	});

	$("[type=hidden]").trigger('change');

});


function buscaDefeitoConstatado (produto, defeito_constatado_select) {
	$(defeito_constatado_select).find("option").first().nextAll().remove();

	if (produto.length > 0) {
		$.ajax({
			async: false,
			cache: false,
			url: "os_cadastro_new_ajax.php",
			type: "POST",
			data: { buscaDefeitoConstatado: true, produto: produto },
			beforeSend: function () {
				$(defeito_constatado_select).hide();
				$(defeito_constatado_select).after("<img name='ajax_loading' src='imagens/loading_img.gif' style='width: 20px; height: 20px;' />");
			},
			complete: function (data) {
				data = $.parseJSON(data.responseText);

				if (data.erro) {
					alert(data.erro);
				} else {
					if (data.defeitos) {
						var defeitos = data.defeitos;
						$.each(defeitos, function (key, value) {
							var option = $("<option></option>", { value: key, text: value });

							$(defeito_constatado_select).append(option);
						});

					}
				}

				$("img[name=ajax_loading]").remove();
				$(defeito_constatado_select).show();
			}
		});
	}
}

var buscaSolucaoProduto = function(produto,solucaoSelect){
	$(solucaoSelect).find("option").remove();
	$.ajax({
		url: "os_cadastro_new_ajax.php",
		type : "POST",
		data : {
			buscaSolucao: true,
			produto : produto,
		},
		beforeSend : function(){
			//loading('show');
		},
		error : function(){
			alert("Erro de conexão");
		},
		success : function(data){
			if(data.erro){
				alert(data.erro);
				return;
			}
			for(var key in data.solucoes){
				solucaoSelect.append($("<option value="+key+">"+data.solucoes[key]+"</option>"));
			}
		},
		complete : function(){
			//loading('hide');
		}
	});
};

function buscaDefeitoPeca (peca, defeito_select) {
	$(defeito_select).find("option").first().nextAll().remove();

	if (peca.length > 0) {
		$.ajax({
			async: false,
			cache: false,
			url: "os_cadastro_new_ajax.php",
			type: "POST",
			data: { buscaDefeitoPeca: true, peca: peca },
			beforeSend: function () {
				$(defeito_select).hide();
				$(defeito_select).after("<img name='ajax_loading' src='imagens/loading_img.gif' style='width: 20px; height: 20px;' />");
			},
			complete: function (data) {
				data = $.parseJSON(data.responseText);

				if (data.erro) {
					alert(data.erro);
				} else {
					if (data.defeitos) {
						var defeitos = data.defeitos;

						$.each(defeitos, function (key, value) {
							var option = $("<option></option>", { value: key, text: value });

							$(defeito_select).append(option);
						});
					}
				}

				$("img[name=ajax_loading]").remove();
				$(defeito_select).show();
			}
		});
	}
}

var _buscaCidade = function(estado,select){
	var ajax = {
		async: false,
		url : 'os_cadastro_new_ajax.php',
		method : 'POST',
		data : {
			buscaCidade : true,
			estado : estado
		}
	}

	$(select).fillSelect(ajax);
}

var _buscaCEP = function(cep,endereco,bairro,cidade,estado){
	if(cep.length <=0 )
		return;

	var array = [endereco,bairro,cidade,estado];

	array.forEach(function(element){
		$(element).hide();
		$(element).after($("<img name='ajax_loading' src='imagens/loading_img.gif' style='width: 20px; height: 20px;' />"));
	});

	$.ajax({
		url : 'os_cadastro_new_ajax.php',
		method : 'POST',
		data : {
			buscaCep : true,
			cep : cep
		},
		success : function(data){
			if(!data || data.error)
				return;

			if ($(estado).val() != data.uf) {
				$(estado).val(data.uf);
				_buscaCidade(data.uf, cidade);
			}
			
			$(cidade).val(data.cidade);
			$(endereco).val(data.end);
			$(bairro).val(data.bairro);
		},
		complete : function(){
			$("img[name=ajax_loading]").remove();

			array.forEach(function(element){
				$(element).show();
			});

			if ($(bairro).val().length == 0) {
				$(bairro).focus();
			} else if ($(endereco).val().length == 0) {
				$(endereco).focus();
			} else {
				$(estado).parents("div.tc_formulario").find("input[id$=_numero]").focus();
			}
		}
	});
}

function retorna_revenda (retorno) {
	console.debug(retorno);
	$("#revenda_nome").val(retorno.razao);
	$("#revenda_cnpj").val(retorno.cnpj).trigger('change');

	if ($("#revenda_estado") != retorno.estado) {
		_buscaCidade(retorno.estado, $("#revenda_cidade"));
	}

	$("#revenda_estado").val(retorno.estado);
	$("#revenda_cidade").val(retorno.cidade).attr('value',retorno.cidade);
	$("#revenda_cep").val(retorno.cep);
	$("#revenda_bairro").val(retorno.bairro);
	$("#revenda_endereco").val(retorno.endereco);
	$("#revenda_numero").val(retorno.numero);
	$("#revenda_complemento").val(retorno.complemento);
	$("#revenda_telefone").val(retorno.telefone);
}

function retorna_produto (retorno) {
	console.debug(retorno);
	var div = $("#produto,#subproduto");
	if(retorno.posicao == 0){
		if(retorno.temSubproduto){
			$('.SubprodutoForm [type=hidden]').trigger('change');
			$('.SubprodutoForm').show();
			$('[produtoPai]').attr('produtoPai',retorno.produto);
		}
		else{
			$('.SubprodutoForm').hide();
		}
	}

	$(div).find("input[name='os[os_produto]["+retorno.posicao+"][produto]']").val(retorno.produto).trigger('change');
	$(div).find("input[name='os[os_produto]["+retorno.posicao+"][referencia]']").val(retorno.referencia).attr({ readonly: "readonly" }).next().hide();
	$(div).find("input[name='os[os_produto]["+retorno.posicao+"][descricao]']").val(retorno.descricao).attr({ readonly: "readonly" }).next().hide();
	$(div).find("input[name='os[os_produto]["+retorno.posicao+"][voltagem]']").val(retorno.voltagem).attr({ readonly: "readonly" });

	buscaDefeitoConstatado(retorno.produto, $(div).find("select[name='os[os_produto]["+retorno.posicao+"][defeito_constatado]']"));

	$("#produto-lista-item [list-index="+retorno.posicao+"] .lista-pecas [name$='[produto]']").val(retorno.produto);
	$("#produto-lista-item [list-index="+retorno.posicao+"] .lista-pecas [name=lupa_config]").attr({ produto: retorno.produto });

}

function retorna_peca (retorno) {
	console.debug(retorno);
	var elementList = $("#produto-lista-item.AutoList,#subproduto-lista-item.AutoList [list-index="+retorno.posicao+"]");
	elementList.find("input[name$='["+retorno.posicao_produto+"][os_item]["+retorno.posicao+"][peca]']").val(retorno.peca).trigger('change');
	elementList.find("input[name$='["+retorno.posicao_produto+"][os_item]["+retorno.posicao+"][referencia]']").val(retorno.referencia);
	elementList.find("input[name$='["+retorno.posicao_produto+"][os_item]["+retorno.posicao+"][descricao]']").val(retorno.descricao);
	elementList.find("input[name$='["+retorno.posicao_produto+"][os_item]["+retorno.posicao+"][qtde]']").val(1);
}

function retorna_pecas (retorno) {
	if(retorno.length == 0)
		return;
	console.debug(retorno);
	retorno.forEach(function(peca){
		if(!peca.qtde)
			peca.qtde = 1;
		var id;
		if(peca.posicao_produto == "0"){
			id = 'produto-lista-item';
		}
		else{
			id = 'subproduto-lista-item';
		}
		var empty = $("#"+id+" [list-index] input[name$='[peca]'][value='']");
		if(empty.length ==0)
			$('#'+id).autoListAdd(peca);
		else
			$('#'+id).autoListFill(peca,$($(empty).parents('[list-index]').last()).attr('list-index'));
	});
	$('select').css('visibility','');
}

function retorna_posto (retorno) {
	$("#posto").val(retorno.posto).trigger('change');
	$("#posto_latitude").val(retorno.latitude);
	$("#posto_longitude").val(retorno.longitude);
	$("#posto_nome").val(retorno.nome).attr({ readonly: "readonly" }).next().hide();
	$("#posto_codigo").val(retorno.codigo).attr({ readonly: "readonly" }).next().hide();

	$("div[name=div_remover_posto]").show();
}

var directionsService;
var directionsRenderer;
var map;
var geocoder;
var marks = [];
var postoLatLng;
var listaEnderecos;
var consumidor_marker;
var posto_marker;

function loadGoogleMaps () {
	var latlng        = new google.maps.LatLng(-15.78014820, -47.92916980);
	var myOptions     = { zoom: 2, center: latlng, mapTypeId: google.maps.MapTypeId.HYBRID, mapTypeControlOptions: { style: google.maps.MapTypeControlStyle.DROPDOWN_MENU }, zoomControlOptions: { style: google.maps.ZoomControlStyle.SMALL } };
	map               = new google.maps.Map(document.getElementById("GoogleMaps"), myOptions);
}

function myCallback (data, i, consumidorLatLng) {
	if (i > listaEnderecos.length) {
		$('#GoogleMapsDirection').html("<br /><div class='alert alert-block alert-error' style='width: 80%; margin: 0 auto;'><strong>Não foi possível realizar a rota</strong></div>");
		$('#qtde_km').val("");
	} else {
		if (data == true) {
			rota(consumidorLatLng);
		} else {
			geocodeLatLon(i, myCallback);
		}
	}
}

function geocodeLatLon (i, callback) {
	geocoder = new google.maps.Geocoder();

	geocoder.geocode( { 'address': listaEnderecos[i] }, function(results, status) {
		if (status == google.maps.GeocoderStatus.OK) {
			/* Endereço retornado pelo Google */
			var destino = results[0].address_components;
			var estadoConsumidor = $("#consumidor_estado").val();
			var cidadeConsumidor = $("#consumidor_cidade [selected]").text();

			var estadoComp = '';
			var cidadeComp = '';
			var bairro = '';
			var endereco = '';
			var consumidorLatLng = '';

			$.each(destino, function (key, value) {
				if ($.inArray("administrative_area_level_1", value.types) != -1) {
					estadoComp = value.short_name;
				} else if ($.inArray("administrative_area_level_2", value.types) != -1 || $.inArray("locality", value.types) != -1) {
					cidadeComp = value.long_name;
				} else if ($.inArray("neighborhood", value.types) != -1) {
					bairro = value.long_name;
				} else if ($.inArray("route", value.types) != -1) {
					endereco = value.long_name;
				}
			});

			var cidadesIguais = false;
			var estadosIguais = false;

			/* Reescreve a Sigla do estado para o nome completo */
			var estadoConsumidor2 = estadoConsumidor;

			var comp1 	= [];
			var comp2 	= [];

			var seq 	= 0;

			if (cidadeComp.length > 0) {
				cidadeComp       = retiraAcentos(cidadeComp);
				cidadeConsumidor = retiraAcentos(cidadeConsumidor);

				if (cidadeComp == cidadeConsumidor) {
					cidadesIguais = true;
				}
			}

			if (estadoComp.length > 0) {
				estadoComp       = retiraAcentos(estadoComp);
				estadoConsumidor = retiraAcentos(estadoConsumidor);

				if (estadoComp == estadoConsumidor || estadoComp == estadoConsumidor2) {
					estadosIguais = true;
				}
			}

			if (cidadesIguais == true && estadosIguais == true) {
				consumidorLatLng = results[0].geometry.location;
				consumidorLatLng = consumidorLatLng.toString();

				callback(true, null, consumidorLatLng);
			} else {
				callback(false, ++i);
			}
		} else {
			$('#GoogleMapsDirection').html("<br /><div class='alert alert-block alert-error' style='width: 80%; margin: 0 auto;'><strong>Não foi possível realizar a rota</strong></div>");
			$('#qtde_km').val("");
		}
	});
}

function calculaKM () {
	if ($("#posto").val().length == 0) {
		alert("Selecione um Posto Autorizado");
		$('#qtde_km').val("");
		return;
	}

	if ($("#posto_latitude").val().length == 0 && $("#posto_longitude").val().length == 0) {
		alert("Posto Autorizado sem latitude e longitude");
		$('#qtde_km').val("");
		return;
	}

	if ($("#consumidor_cep").val() == "" && $("#consumidor_endereco").val() == "" && $("#consumidor_cidade").val() == "" && $("#consumidor_estado").val() == "") {
		alert("Digite as informações do consumidor para calcular o KM");
		$('#qtde_km').val("");
		return;
	}

	var c = [
		$("#consumidor_endereco").val(),
		$("#consumidor_numero").val(),
		$("#consumidor_bairro").val(),
		$("#consumidor_cidade [selected]").text(),
		$("#consumidor_estado").val()
	];

	var consumidorEndereco = c.join(", ");

	delete(c[2]);
	var consumidorEnderecoSemBairro = c.join(" ,");

	var consumidorCep = "cep " + $("#consumidor_cep").val();

	delete c[0];
	delete c[1];
	var consumidorCidade = c.join(", ");

	listaEnderecos = [consumidorEndereco, consumidorEnderecoSemBairro, consumidorCep, consumidorCidade];

	geocodeLatLon(0, myCallback);

	
}

function rota (consumidorLatLng) {
	if (posto_marker != null) {
		posto_marker.setMap(null);
	}

	if (consumidor_marker != null) {
		consumidor_marker.setMap(null);
	}

	if (directionsRenderer != null) {
		directionsRenderer.setMap(null);
	}

	postoLatLng      = new google.maps.LatLng($("#posto_latitude").val(), $("#posto_longitude").val());
	consumidorLatLng = consumidorLatLng.replace("(", "");
	consumidorLatLng = consumidorLatLng.replace(")", "");

	var parte = consumidorLatLng.split(',');
	var lat = parte[0];
	var lng = parte[1];

	var consumidorLatLng = new google.maps.LatLng(lat, lng);

	consumidor_marker = new google.maps.Marker({
		icon: 'https://mts.googleapis.com/vt/icon/name=icons/spotlight/spotlight-waypoint-b.png&text=B&psize=16&font=fonts/Roboto-Regular.ttf&color=ff333333&ax=44&ay=48&scale=1',
		map: map,
		position: consumidorLatLng
	});

	posto_marker = new google.maps.Marker({
		icon: 'https://mts.googleapis.com/vt/icon/name=icons/spotlight/spotlight-waypoint-a.png&text=A&psize=16&font=fonts/Roboto-Regular.ttf&color=ff333333&ax=44&ay=48&scale=1',
		position: postoLatLng,
		map: map
	});

	directionsService  = new google.maps.DirectionsService();
	directionsRenderer = new google.maps.DirectionsRenderer({ suppressMarkers: true, zoom: 5 });
	directionsRenderer.setMap(map);
	directionsRenderer.setPanel(document.getElementById("GoogleMapsDirection"));

	directionsService.route({ origin: postoLatLng, destination: consumidorLatLng, travelMode: google.maps.DirectionsTravelMode.DRIVING }, function(response, status){
		if (status == google.maps.DirectionsStatus.OK) {
			directionsRenderer.setDirections(response);
			var km1 = response.routes[0].legs[0].distance.value;
			var km2 = 0;

			var directionsService  = new google.maps.DirectionsService();
			directionsService.route({ origin: consumidorLatLng, destination: postoLatLng, travelMode: google.maps.DirectionsTravelMode.DRIVING }, function(response, status){
				km2 = response.routes[0].legs[0].distance.value;

				var ida = parseFloat(km1 / 1000).toFixed(2);
				var volta = parseFloat(km2 / 1000).toFixed(2);

				$("#box_ida_volta").html("Ida: "+ida+" / Volta: "+volta+" &raquo; ");
				$("#qtde_km").val(((km1 + km2) / 1000).toFixed(2));
				$("#qtde_km_hidden").val(((km1 + km2) / 1000).toFixed(2));
			});
		} else {
			$('#GoogleMapsDirection').html("<br /><div class='alert alert-block alert-error' style='width: 80%; margin: 0 auto;'><strong>Não foi possível realizar a rota</strong></div>");
			$('#qtde_km').val("");
		}
	});
}

function validaCamposMapa(){

	$('#GoogleMapsDirection').html("");
	$('#box_ida_volta').html("");

	if($('#consumidor_cep').val() == ""){
		alert('Por favor insira um estado');
		$('#consumidor_cep').focus();
		return;
	}

	if($('#consumidor_estado').val() == ""){
		alert('Por favor insira um estado');
		$('#consumidor_estado').focus();
		return;
	}

	if($('#consumidor_cidade').val() == ""){
		alert('Por favor insira uma cidade');
		$(this).focus();
		return;
	}

	if($('#consumidor_bairro').val() == ""){
		alert('Por favor insira um bairro');
		$('#consumidor_bairro').focus();
		return;
	}

	if($('#consumidor_endereco').val() == ""){
		alert('Por favor insira um endereço');
		$('#consumidor_endereco').focus();
		return;
	}

	if($('#consumidor_numero').val() == ""){
		alert('Por favor insira um número');
		$('#consumidor_numero').focus();
		return;
	}

}

function select_visibility(){
	('select').css('visibiliy','');
}