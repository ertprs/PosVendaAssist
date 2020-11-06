// JavaScript Document
function logar_erro_acesso(numero,erro,parametros_pagina) {
	var url = "/logar_erro_acesso.php";
	var parametros = "local="+numero+"&erro="+erro+"&"+parametros_pagina;
	jQuery.post(url, parametros, false);
}

function valida_senha(dados_login,texto) {
	var url = "../index.php?ajax=sim&acao=validar";
	var parametros = jQuery('form#acessar').serialize();//  Prepara os campos do formulário para enviar por POST
	jQuery('#btnAcao').attr('disabled','disabled');		//  Deshabilita o botão de login enquanto está conferindo o usuário

	var erro  = jQuery('#errologin');
	var carga = jQuery('#entrando');

	erro.html('').hide().css('visibility',"visible");
	carga.html('').hide().css('visibility',"visible");
// 	carga.css('visibility','visible')
// 		 .html("&nbsp;&nbsp;"+texto+"&nbsp;&nbsp;");

	jQuery.post(url, parametros+"&btnAcao=enviar",
		function (data) {
		var resposta= data.split("|");
		var codigo  = resposta[0].replace(/.*\s(\w+)$/g, '');
		var	texto   = resposta[1];

		if (data.length > 0) {
				if (codigo=="debug") {
					alert(data);
				}
				else if (codigo=="ok"){
					/*erro.hide();
					carga.html("Entrando...");
					carga.show('fast')
						 .delay(1000)
						 .queue(function() {*/
							window.parent.location = 'http://posvenda.telecontrol.com.br/assist/'+texto;
					/*		jQuery(this).dequeue();
						 })
						 .hide('fast');*/
				}
				else if (codigo=="time"){
					carga.html("Entrando...");
					carga.fadeIn('fast')
						 .delay(600).fadeOut('fast');
					erro.hide();
					window.location = '/'+texto;
				}
				else if (codigo=="1"){
					erro.html(texto);
					erro.fadeIn('fast')
						.delay(3000).fadeOut('fast');
					jQuery('input[name=senha]').val('');
					jQuery('#btnAcao').val('entrar').removeAttr('disabled');    // Ativa de novo o botão 'Entrar'
				}
				else if (codigo=="81_no_lu"){
					erro.html(texto);
					erro.fadeIn('fast')
						.delay(3000).fadeOut('fast');
					jQuery('#btnAcao').val('entrar').removeAttr('disabled');    // Ativa de novo o botão 'Entrar'
				}
				else if (codigo=="81_lu"){
					erro.html(texto);
					erro.fadeIn('fast')
						.delay(4000).fadeOut('fast');
					jQuery('#btnAcao').val('entrar').removeAttr('disabled');    // Ativa de novo o botão 'Entrar'
				}
				else {
// 					alert(data);
					texto = "O sistema passará por manutenção técnica!\n<b>";
					texto+= "Dentro de algumas horas será restabelecido.\n</b>Agradecemos a compreensão!";
					erro.html(texto);
					erro.fadeIn('fast')
						.delay(4000).fadeOut('fast');
					jQuery('#btnAcao').val('entrar').removeAttr('disabled');    // Ativa de novo o botão 'Entrar'
					logar_erro_acesso('1', data, parametros);
					//alert(http_forn[curDateTime].responseText);
				}
			}else{
				texto = "Erro no acesso.<br><b>Tente novamente.</b>";
					erro.html(texto);
					erro.fadeIn('fast')
						.delay(3000).fadeOut('fast');
				jQuery('#btnAcao').val('entrar').removeAttr('disabled');    // Ativa de novo o botão 'Entrar'
				logar_erro_acesso('2', data, parametros);
			}
	});
}
