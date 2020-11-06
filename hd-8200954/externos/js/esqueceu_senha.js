
function verifica_esqueceu_senha() {

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

	if ($("#login_unico:checked").length == 0){
		var fabrica = $("#fabrica").val();
		if(!fabrica){
			$("#fabrica").css('border-color','#C6322B');
			$("#fabrica").css('border-width','1px');
			$(".fabrica").css('color','#C6322B');
			verifica ='1';
		}else{
			$("#fabrica").css('border-color','#CCC');
			$("#fabrica").css('border-width','1px');
			$(".fabrica").css('color','#535252');
		}
	}

	if(verifica =='1') {
		//alert("ERRO");
		$("#mensagem_envio").show();
		$("#mensagem_envio").css('display','block');
		$("#mensagem_envio").html('Por favor, verifique os campos marcados em vermelho');

		return false;
	}else{
		//if ((email.length != 0) && ((email.indexOf("@") < 1) || (email.indexOf('.') < 7))) {
		if (!email.match(/^[A-Za-z0-9._%-]+@([A-Za-z0-9.-]+){1,2}([.][A-Za-z]{2,4}){1,2}$/)) {
			$("#mensagem_envio").show();
			$("#mensagem_envio").html('E-mail Inválido.');
			$("#email").css('border-color','#C6322B');
			$("#email").css('border-width','1px');
			$(".email").css('color','#C6322B');
		}else{
			$('#frm_es').submit();//EXECUTA O SUBMIT
			return true;
		}
	}

}

function verifica_nova_senha(){
	var verifica =0;

	var senha = $("#senha_nova").val();
	if(!senha){
		$("#senha_nova").css('border-color','#C6322B');
		$("#senha_nova").css('border-width','1px');
		$(".senha_nova").css('color','#C6322B');

		$("#senha_nova_confirma").css('border-color','#C6322B');
		$("#senha_nova_confirma").css('border-width','1px');
		$(".senha_nova_confirma").css('color','#C6322B');
		verifica ='1';
	}

	var senha_confirma = $("#senha_nova_confirma").val();
	if(!senha_confirma){
		$("#senha_nova_confirma").css('border-color','#C6322B');
		$("#senha_nova_confirma").css('border-width','1px');
		$(".senha_nova_confirma").css('color','#C6322B');
		verifica ='1';
	}

	if(senha !== senha_confirma){
		$("#mensagem_envio").show();
		$("#mensagem_envio").css('display','block');
		$("#mensagem_envio").html('Senhas não Coincidem');

		return false;
	}

	if(verifica =='1') {
		$("#mensagem_envio").show();
		$("#mensagem_envio").css('display','block');
		$("#mensagem_envio").html('Por favor, verifique os campos marcados em vermelho');

		return false;
	}else{
		$('#frm_es').submit();//EXECUTA O SUBMIT
		return true;
	}
}


function verificaSenhasRecupera(){

	var verifica =0;

	var nova_senha = $("#nova_senha").val();
	var erro = "";

	if(!nova_senha){

		$("#nova_senha").css('border-color','#C6322B');
		$("#nova_senha").css('border-width','1px');
		$(".nova_senha").css('color','#C6322B');
		verifica ='1';

		erro = "* Preencha os campos obrigatórios";


	}else{

		$("#nova_senha").css('border-color','#CCC');
		$("#nova_senha").css('border-width','1px');
		$(".nova_senha").css('color','#535252');

	}

	var conf_nova_senha = $("#conf_nova_senha").val();

	if(!conf_nova_senha){

		$("#conf_nova_senha").css('border-color','#C6322B');
		$("#conf_nova_senha").css('border-width','1px');
		$(".conf_nova_senha").css('color','#C6322B');
		verifica ='1';

		erro = "* Preencha os campos obrigatórios";

	}else{

		if (conf_nova_senha == $('#nova_senha').val()){

			$("#conf_nova_senha").css('border-color','#CCC');
			$("#conf_nova_senha").css('border-width','1px');
			$(".conf_nova_senha").css('color','#535252');

		}else{

			$("#conf_nova_senha").css('border-color','#C6322B');
			$("#conf_nova_senha").css('border-width','1px');
			$(".conf_nova_senha").css('color','#C6322B');
			verifica ='1';
			var erro = erro + "* A senha de confirmação está diferente da nova senha. Favor corrigir.";

		}

	}

	if(verifica =='1') {
		//alert("ERRO");
		$("#mensagem_envio").show();
		$("#mensagem_envio").css('display','block');
		$("#mensagem_envio").html(erro);
		return false;
	}else{

		$('#frm_es').submit();//EXECUTA O SUBMIT
		return true;

	}

}


function limpa_campo_esqueci_senha() {
	setTimeout(function(){
		$("#mensagem_envio").html('');
		$("#email").val('');
		$("#nome").val('');
		$("#fabrica").val('');
	}, 3000);
}
