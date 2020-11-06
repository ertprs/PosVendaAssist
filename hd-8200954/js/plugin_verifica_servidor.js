
switch (idioma_verifica_servidor) {
	case 'en':
		var mensage_success = "Processing... Please, wait !";
		var mensage_error   = "Awaiting Server <br> Try again later";
	break;

	case 'es':
		var mensage_success = "Procesando, por favor, espere !";
		var mensage_error   = "Servidor en espera <br> Inténtelo de nuevo";
	break;
	default:
		var mensage_success = "PROCESSANDO AGUARDE !";
		var mensage_error   = "SERVIDOR EM ESPERA <BR> TENTE NOVAMENTE";
}

$(document).ready(function() {

	var botao;
	$('.verifica_servidor').click(function() {

	    var valor = 1;
	    var erro_form = "";
	    var fabrica = $('.fabrica_hidden').val();
	    if(fabrica == "74" && document.URL.match(/os\_item\_new\.php/gi) && typeof ajax_estoque != "undefined"){
			var ajax_ok = true;
			var serie = $.trim($("#produto_serie").val());

			if (serie.length > 0) {
				var msg = "Com a Solução SOLICITAÇÃO DE PEÇAS será aberto uma nova OS para pedido de peças. Deseja continuar?";

				if (confirm(msg)) {

					$.each(ajax_estoque, function(key, value){
			    		if(value == true){
			    			ajax_ok = false;
			    			return false;
			    		}
						if(value == "cancela"){
							ajax_ok = "cancela";
							return false;
						}
			    	});

					if(ajax_ok == "cancela"){
						return false;
					}

			    	if(ajax_ok == false){
			    		alert("Por favor espere terminar o processo de atualização do estoque.");
			    		return false;
			    	}else{
			    		if(document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='gravar' ; }
			    	}
			    } else {
			        return false;
                }
			}else{
				var msg = "A OS irá para intervenção da fábrica, caso queira inserir o número de série, favor clicar no botão Cancelar, caso contrário, favor clicar em OK";
				
				if (confirm(msg)) {
					$.each(ajax_estoque, function(key, value){
			    		if(value == true){
			    			ajax_ok = false;
			    			return false;
			    		}
						if(value == "cancela"){
							ajax_ok = "cancela";
							return false;
						}
			    	});

					if(ajax_ok == "cancela"){
						return false;
					}

			    	if(ajax_ok == false){
			    		alert("Por favor espere terminar o processo de atualização do estoque.");
			    		return false;
			    	}else{
			    		if(document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='gravar' ; }
			    	}
				} else {
                	return false;
                }

			}
		};

	    	

	    if(fabrica == "24"){
		    $('#tablemostrar > tbody > tr').each(function(){

		        valor++;

		        if($('input[name=descricao_'+valor+']').val() != "" || $('input[name=peca_'+valor+']').val() != ""){

		            if($('input[name=peca_'+valor+']').val() == ""){
		                alert("Por favor informe a Peça");
		                $('input[name=peca_'+valor+']').focus();
		                erro_form = "on";
		                return;
		            }

		            if($('input[name=descricao_'+valor+']').val() == ""){
		                alert("Por favor informe a Descrição da Peça");
		                $('input[name=descricao_'+valor+']').focus();
		                erro_form = "on";
		                return;
		            } 

		            if($('input[name=qtde_'+valor+']').val() == ""){
		                alert("Por favor informe a Quantidade de Peça");
		                $('input[name=qtde_'+valor+']').focus();
		                erro_form = "on";
		                return;
		            }

		            if($('select[name=defeito_'+valor+']').val() == ""){
		                alert("Por favor informe o Defeito sa Peça");
		                $('select[name=defeito_'+valor+']').focus();
		                erro_form = "on";
		                return;
		            } 

		            if($('select[name=servico_'+valor+']').val() == ""){
		                alert("Por favor informe o Serviço realizado na Peça");
		                $('select[name=servico_'+valor+']').focus();
		                erro_form = "on";
		                return;
		            }  
		        } 
		    });
		}

		if(erro_form != "on"){

			$.unblockUI();
			botao	  = $(this);
			var name_img  = botao.attr('type');
			if(name_img == '' || name_img == undefined) {
				var name_form = botao.attr('name');
				var id_form   = name_form.replace('nome_', '');
			}else {
				var id_form = botao.attr('rel');
			}

			$.ajax({
				type: 'POST',
				timeout: 5000,
				url: 'verifica_servidor.php',
				async: false,
				dataType: "text",
					
				success: function(response) { //RESPOSTA OK DO SERVIDOR
					if(response == '1') {	// SE A RESPOSTA FOR 1 O BANCO ESTA ONLINE
						//botao.hide();
						//botao.css('display','none');

						$.blockUI({ 
							message: '<h1><div style="font-size:14px;">'+mensage_success+'</div></h1>', 
							timeout: 120000 
						});

						$('#'+id_form).submit();//EXECUTA O SUBMIT

						/*setTimeout(function(){
							botao.show();
							botao.css('display','block');
						}, 120000);*/
						
					}else { // O BANCO ESTA FORA DO AR
						$.blockUI({ 
							message: '<h1><div style="color:red;font-size:14px;">'+mensage_error+'</div></h1>', 
							timeout: 4000 
						});
						setTimeout(function(){
							//botao.show();
							//botao.css('display','block');
						}, 4000);
						return false;
					}
				},
				error: function(response) {	//RESPOSTA DE ERRO DO SERVIDOR
					$.blockUI({ 
						message: '<h1><div style="color:red;font-size:14px;">'+mensage_error+'</div></h1>', 
						timeout: 4000 
					});
					setTimeout(function(){
						//botao.show();
						//botao.css('display','block');
					}, 4000);	
					return false;
				}
			});

		}

	});


$('button[name=btn_gravar_elgin]').bind('verifica_servidor',function() {
	    var valor = 1;
	    var erro_form = "";
	    var fabrica = $('.fabrica_hidden').val();

	    if(fabrica == "74" && document.URL.match(/os\_item\_new\.php/gi) && ajax_estoque != undefined){
			var ajax_ok = true;

	    	$.each(ajax_estoque, function(key, value){
	    		if(value == true){
	    			ajax_ok = false;
	    			return false;
	    		}
	    	});

	    	if(ajax_ok == false){
	    		alert("Por favor espere terminar o processo de atualização do estoque.");
	    		return false;
	    	}else{
	    		if(document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='gravar' ; }
	    	}

		}

	    if(fabrica == "24"){
		    $('#tablemostrar > tbody > tr').each(function(){

		        valor++;

		        if($('input[name=descricao_'+valor+']').val() != "" || $('input[name=peca_'+valor+']').val() != ""){

		            if($('input[name=peca_'+valor+']').val() == ""){
		                alert("Por favor informe a Peça");
		                $('input[name=peca_'+valor+']').focus();
		                erro_form = "on";
		                return;
		            }

		            if($('input[name=descricao_'+valor+']').val() == ""){
		                alert("Por favor informe a Descrição da Peça");
		                $('input[name=descricao_'+valor+']').focus();
		                erro_form = "on";
		                return;
		            } 

		            if($('input[name=qtde_'+valor+']').val() == ""){
		                alert("Por favor informe a Quantidade de Peça");
		                $('input[name=qtde_'+valor+']').focus();
		                erro_form = "on";
		                return;
		            }

		            if($('select[name=defeito_'+valor+']').val() == ""){
		                alert("Por favor informe o Defeito sa Peça");
		                $('select[name=defeito_'+valor+']').focus();
		                erro_form = "on";
		                return;
		            } 

		            if($('select[name=servico_'+valor+']').val() == ""){
		                alert("Por favor informe o Serviço realizado na Peça");
		                $('select[name=servico_'+valor+']').focus();
		                erro_form = "on";
		                return;
		            }  
		        } 
		    });
		}

		if(erro_form != "on"){

			$.unblockUI();
			botao	  = $(this);
			var name_img  = botao.attr('type');
			if(name_img == '' || name_img == undefined) {
				var name_form = botao.attr('name');
				var id_form   = name_form.replace('nome_', '');
			}else {
				var id_form = botao.attr('rel');
			}

			$.ajax({
				type: 'POST',
				timeout: 5000,
				url: 'verifica_servidor.php',
				async: false,
				dataType: "text",
					
				success: function(response) { //RESPOSTA OK DO SERVIDOR
					if(response == '1') {	// SE A RESPOSTA FOR 1 O BANCO ESTA ONLINE
						//botao.hide();
						//botao.css('display','none');

						$.blockUI({ 
							message: '<h1><div style="font-size:14px;">'+mensage_success+'</div></h1>', 
							timeout: 120000 
						});

						$('#'+id_form).submit();//EXECUTA O SUBMIT

						/*setTimeout(function(){
							botao.show();
							botao.css('display','block');
						}, 120000);*/
						
					}else { // O BANCO ESTA FORA DO AR
						$.blockUI({ 
							message: '<h1><div style="color:red;font-size:14px;">'+mensage_error+'</div></h1>', 
							timeout: 4000 
						});
						setTimeout(function(){
							//botao.show();
							//botao.css('display','block');
						}, 4000);
						return false;
					}
				},
				error: function(response) {	//RESPOSTA DE ERRO DO SERVIDOR
					$.blockUI({ 
						message: '<h1><div style="color:red;font-size:14px;">'+mensage_error+'</div></h1>', 
						timeout: 4000 
					});
					setTimeout(function(){
						//botao.show();
						//botao.css('display','block');
					}, 4000);	
					return false;
				}
			});

		}

	});

	$(document).keyup(function(e) {
		if (e.keyCode == 27) {
			if(!botao){
				//botao.show();
				//botao.css('display','block');
			}
			$.unblockUI();
		}
	});

});
