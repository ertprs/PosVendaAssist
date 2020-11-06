
switch (idioma_verifica_servidor) {
	case 'es':
		var mensage_success = "Processing... Please, wait !";
		var mensage_error   = "Awaiting Server <br> Try again later";
	break;

	case 'en':
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
	var fabrica = $('.fabrica_hidden').val();
	if(fabrica == 90){
		if(document.getElementById('confirmaEmail').value == 'nao'){
			return false;
		}
	}
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
					botao.hide();
					botao.css('display','none');
					$('#'+id_form).submit();//EXECUTA O SUBMIT 
					//alert("TESTE");
					$.blockUI({ 
						message: '<h1><div style="font-size:14px;">'+mensage_success+'</div></h1>', 
						timeout: 120000 
					});
					setTimeout(function(){
						botao.show();
						botao.css('display','block');
					}, 120000);
					
				}else { // O BANCO ESTA FORA DO AR
					$.blockUI({ 
						message: '<h1><div style="color:red;font-size:14px;">'+mensage_error+'</div></h1>', 
						timeout: 4000 
					});
					setTimeout(function(){
						botao.show();
						botao.css('display','block');
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
					botao.show();
					botao.css('display','block');
				}, 4000);	
				return false;
			}
		});

	});




	$(document).keyup(function(e) {
		if (e.keyCode == 27) {
			botao.show();
			botao.css('display','block');
		}
	});
});
