// Pesquisa de posto por código ou nome
// function fnc_pesquisa_posto (campo, tipo) {

// 	var value = campo.val();

// 	if (value.length >= 3) {
// 		Shadowbox.open({
// 			content:	"posto_pesquisa_2_nv.php?" + tipo + "="+value+"&tipo="+tipo,
// 			player:	    "iframe",
// 			title:		"Pesquisa Posto",
// 			width:	    800,
// 			height:	    500
// 		});
// 	} else
// 		alert("Informar pelo menos 3 caracteres para realizar a pesquisa!");
// }

function gravaDados(name, valor){
    try {
        $("input[name="+name+"]").val(valor);
    } catch(err){
        return false;
    }
}

// function retorna_posto(codigo_posto,posto,nome,cnpj,cidade,estado,credenciamento,num_posto,cep, endereco, numero, bairro){
// 	gravaDados('codigo_posto',posto);
// 	gravaDados('posto',codigo_posto);
// 	gravaDados('descricao_posto',nome);
// }

function retorna_posto(retorno){	
	$("#descricao_posto").val(retorno.nome);	
	$("#codigo_posto").val(retorno.codigo);	
	console.debug(retorno);
 }

 function retorna_produto(produto){
 	for(var k in produto){
		$('[name=produto_'+k+']').val(produto[k]);
 	}
 	console.debug(produto);
 }

 function buscaOS(evt){
 	$('#loading').fadeIn();
 	var ok = false;
 	var inputValues = {
 		'os' : null,
 		'nome_consumidor' : '',
 		'defeito_constatado': ''
 	};
 	var response = $.extend({},inputValues);
 	$.ajax({
 		'url':'cadastro_advertencia_bo_ajax.php',
 		'async' : true,
 		'method':'GET',
 		'data' : {
 			'ordem_servico' : $("#ordem_servico").val()
 		},
 		success : function(data){
 			ok = !data?false:true;
 			if(data)
 				response = $.extend(response,data);
 		},
 		error: function(){
 			ok = false;
 		},
 		complete : function(){
		 	for(var key in response){
		 		var element = $('[name='+key+']');
		 		element.val(response[key]);
		 	}
		 	$('#loading').fadeOut();
			var holder = $('[name=ordem_servico]').parents('div.control-group');
		 	if(!ok){
		 		alert('Ordem de Serviço não Encontrada');
		 		holder.addClass('error');
		 	}
		 	else{
		 		holder.removeClass('error');
		 	}

 		}
 	});
 }


$(function() {
	
	Shadowbox.init();

	$("span[rel=lupa]").click(function () {
		$.lupa($(this));
	});

	$("#ordem_servico").change(buscaOS);
	$("#ordem_servico + span > i").click(buscaOS);

	
	$(".advertencia").hide();
	$(".boletim_ocorrencia").hide();
	$(".cadastroForm").hide();
	$("#codigo_posto").mask("9999999999");

	$("#tipo_cadastro").change(function() {

		var option_selected = $(this).find("option:selected");

		if(option_selected.val() == "advertencia") {
			$(".cadastroForm").show();
			$(".advertencia").show();
			$(".boletim_ocorrencia").hide();

			$("#tipo_ocorrencia").removeAttr("required");
			$("#hd_chamado").removeAttr("required");

		} else if(option_selected.val() == "boletim_ocorrencia") {
			$(".cadastroForm").show();
			$(".advertencia").hide();
			$(".boletim_ocorrencia").show();

			$("#tipo_ocorrencia").attr("required", "true");
			$("#hd_chamado").attr("required", "true");
		} else {
			$(".cadastroForm").hide();
			$(".advertencia").hide();
			$(".boletim_ocorrencia").hide();
		}
	});

	// $("#pesquisa_posto_codigo").click(function() {
	// 	fnc_pesquisa_posto($('#posto'), 'codigo');
	// });

	// $("#pesquisa_posto_descricao").click(function() {
	// 	fnc_pesquisa_posto($('#descricao_posto'), 'nome');
	// });

	$(document).on("submit", "form[name=cadastro_advertencia_bo]", function() {
		if(!$("[name=produto_referencia]").val()){
			$("[name=produto_produto]").val('');
		}
		if($("#codigo_posto").val() == '') {
			$("#posto").val('');
			return false;
		}
		var form = $(this);
		$.ajax({
			'url':form.attr('action'),
			'method':form.attr('method'),
			'data':form.serialize(),
			'error': function(){
				alert('');
			},
			'success':function(data){
				$('.error').removeClass('error');
				console.debug(data);
				if(data && data.success){
					$('form input[type!=hidden][type!=submit],select,textarea').val('');
					$('form select').trigger('change');
					var msg = $('<div class="alert alert-success" ></div>');
					msg.append('<h4>'+data.message+'</h4>');
					$('#ajax-message').append(msg);
					msg.focus();
					setTimeout(function(){msg.fadeOut();},3000);
				}
				else{
					var msg = $('<div class="alert alert-error" ></div>');
					msg.append('<h4>'+data.message+'</h4>');
					$('#ajax-message').append(msg);
					msg.focus();
					for(var field in data.error){
						$('[name='+data.error[field]+']').parents('div.control-group').addClass('error');
					}
					setTimeout(function(){msg.fadeOut();},3000);	
				}
			},
			'complete':function(){

			}
		});
		return false;
	});
});