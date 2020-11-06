/*	Javascript file */
function verifica_login_unico() {

	var verifica =0;
	var login = $("#campo_login").val();
	if(!login){
		$("#campo_login").css('border-color','#C6322B');
		$("#campo_login").css('border-width','1px');
		$(".login_fabricante").css('color','#C6322B');
		verifica ='1';
	}else{
		$("#campo_login").css('border-color','#CCC');
		$("#campo_login").css('border-width','1px');
		$(".login_fabricante").css('color','#535252');
	}

	
	var verifica = 0;
	var senha = $("#campo_senha").val();
	if(!senha){
		$("#campo_senha").css('border-color','#C6322B');
		$("#campo_senha").css('border-width','1px');
		$(".login_senha").css('color','#C6322B');
		verifica ='1';
	}else{
		$("#campo_senha").css('border-color','#CCC');
		$("#campo_senha").css('border-width','1px');
		$(".login_senha").css('color','#535252');
	}
	

	//alert(verifica);
	if(verifica =='1') {
		//alert("ERRO");
		$("#mensagem_envio").html('');
		$("#mensagem_envio").show();
		$("#mensagem_envio").css('display','block');
		$("#mensagem_envio").html('<label class="erro_campos_obrigatorios">* Por favor, verifique os campos marcados em vermelho.</label>');
		return false;
	}else{
		//alert("SUCESSO");
		$("#mensagem_envio").html('');
		$('#lu').submit();//EXECUTA O SUBMIT
		return true;
	}
	//login_senha
	
}

function email_validacao() {

	var verifica =0;
	var email = $("#email").val();
	if(!email){
		$("#email").css('border-color','#C6322B');
		$("#email").css('border-width','1px');
		$(".email").css('color','#C6322B');
		verifica ='1';
	}else{
		$("#email").css('border-color','#CCC');
		$("#email").css('border-width','1px');
		$(".email").css('color','#535252');
	}


	if(verifica =='1') {
		//alert("ERRO");
		$("#mensagem_envio").show();
		$("#mensagem_envio").css('display','block');
		$("#mensagem_envio").html('<label class="erro_campos_obrigatorios">* Por favor, verifique os campos marcados em vermelho.</label>');
		return false;
	}else{
		//alert("SUCESSO");
		if ((email.length != 0) && ((email.indexOf("@") < 1) || (email.indexOf('.') < 7))) {
			$("#mensagem_envio").html('<label class="erro_campos_obrigatorios">* E-mail Inválido.</label>');
			$("#email").css('border-color','#C6322B');
			$("#email").css('border-width','1px');
			$(".email").css('color','#C6322B');
		}else{
			$('#frm_os').submit();//EXECUTA O SUBMIT
			return true;
		}
	}

}



function login_unico_envia_email() {
	setTimeout(function(){
		$("#mensagem_envio").html('');
		$("#email").val('');
	}, 3000); 
}

// JavaScript Document
function logar_erro_acesso(numero,erro,parametros_pagina) {
	var url = "/logar_erro_acesso.php";
	var parametros = "local="+numero+"&erro="+erro+"&"+parametros_pagina;
	jQuery.post(url, parametros, false);
}

function valida_senha(dados_login,texto) {
	var url = "/assist/index.php?ajax=sim&acao=validar";
	var parametros = jQuery('form#acessar').serialize();//  Prepara os campos do formulário para enviar por POST
	jQuery('#btnAcao').attr('disabled','disabled');		//  Deshabilita o botão de login enquanto está conferindo o usuário

	var erro  = jQuery('#errologin');
	var carga = jQuery('#entrando');

	erro.html('').hide().css('visibility',"visible");
	carga.html('').hide().css('visibility',"visible");
// 	carga.css('visibility','visible')
// 		 .html("&nbsp;&nbsp;"+texto+"&nbsp;&nbsp;");

	jQuery.post(url, parametros+'&btnAcao=entrar',
		function (data) {
		var resposta= data.split("|");
		var codigo  = resposta[0];
		var	texto   = resposta[1];

		if (data.length > 0) {
				if (codigo=="debug") {
					alert(data);
				}
				else if (codigo=="ok"){
					erro.hide();
					carga.html("Entrando...");
					carga.show('fast')
						 .delay(1000)
						 .queue(function() {
							window.location = '/assist/'+texto;
							jQuery(this).dequeue();
						 })
						 .hide('fast');
				}
				else if (codigo=="time"){
					carga.html("Entrando...");
					carga.fadeIn('fast')
						 .delay(600).fadeOut('fast');
					erro.hide();
					window.location = '/'+texto;
				}
				else if (codigo=="1"){
				    texto = texto+"<p>Se você esqueceu sua senha, <a href='esqueci_senha.php' style='color:#733;'><b>clique aqui!</b></a></p>";
					erro.html(texto);
					erro.fadeIn('fast')
						.delay(5000).fadeOut('fast');
					jQuery('#btnAcao').val('entrar').removeAttr('disabled');    // Ativa de novo o botão 'Entrar'
				}
				else if (codigo=="81_no_lu"){
					erro.html(texto);
					erro.fadeIn('fast')
						.delay(5000).fadeOut('fast');
					jQuery('#btnAcao').val('entrar').removeAttr('disabled');    // Ativa de novo o botão 'Entrar'
				}
				else if (codigo=="81_lu"){
					erro.html(texto);
					erro.fadeIn('fast')
						.delay(5000).fadeOut('fast');
					jQuery('#btnAcao').val('entrar').removeAttr('disabled');    // Ativa de novo o botão 'Entrar'
				}
				else {
// 					alert(data);
					texto = "O sistema passará por manutenção técnica!\n<b>";
					texto+= "Dentro de algumas horas será restabelecido.\n</b>Agradecemos a compreensão!";
					erro.html(texto);
					erro.fadeIn('fast')
						.delay(7000).fadeOut('fast');
					jQuery('#btnAcao').val('entrar').removeAttr('disabled');    // Ativa de novo o botão 'Entrar'
					logar_erro_acesso('1', data, parametros);
					//alert(http_forn[curDateTime].responseText);
				}
			}else{
				texto = "Erro no acesso.<br><b>Tente novamente.</b>";
					erro.html(texto);
					erro.fadeIn('fast')
						.delay(5000).fadeOut('fast');
				jQuery('#btnAcao').val('entrar').removeAttr('disabled');    // Ativa de novo o botão 'Entrar'
				logar_erro_acesso('2', data, parametros);
			}
	});
}
