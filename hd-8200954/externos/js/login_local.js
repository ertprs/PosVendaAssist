/*
 * Este procedimento de login requer os seguintes arquivo para ser executado corretamente!!!
 *
 *       <script type='text/javascript' src='http://code.jquery.com/jquery-latest.min.js'></script>
 *       <script type='text/javascript' src='http://www.telecontrol.com.br/login/bootstrap.js'></script>
 *
 * 	Éderson Sandre <ederson.sandre@telecontrol.com.br>
 * 	Telecontrol - 05 de outubro de 2012
 */

$('document').ready(function(){
	auth();
});

function auth(){
	// Quando clicar no botão executa
	$("#autentica").bind('click', function(){
		login();
	});
}

function getHost(){
	var host 	= window.location.host;
	var server  = '';

	switch(host){
		case 'ww2.telecontrol.com.br':
			server = "http://ww2.telecontrol.com.br/assist/";
			break;

		case 'jacto.telecontrol.com.br':
			server = "http://jacto.telecontrol.com.br/";
			break;

		case '192.168.0.199':
		case 'urano.telecontrol.com.br' :
			server = '';
			break;

		default:
			server = 'http://posvenda.telecontrol.com.br/assist/';
			break;
	}

	server = server + (window.location.pathname.indexOf('externos')) ? '../' : '';
	return server;
}

function login(){
	$("#errologin").fadeOut();
	$("#brw").fadeOut();
	var box_login = $('#box_login').html();
	var url_local = getHost();
 
	var user = checkEntries();
	
  	if(user){
        var server = url_local+'index.php?acao=validar&ajax=sim';
        var params = user;

  		$.ajax({
  			url: server,
  			type: "POST",
  			data: {login:user['login'], senha:user['senha'],loginAcacia:user['loginAcacia'],btnAcao:'entrar'},
  			cache: false,
  			success: function(data) {
  				if(data){
  					var response = data.split('|');

  					if (response[1] && response[1].length > 0){
  						var codigo 		= response[0];
  						var mensagem 	= response[1];
						var admin       = (response[2] == undefined) ? '':response[2];
  						validaError(box_login, codigo, mensagem, admin);
  					}
  				}
  			}
  		});
  	}
}

function checkEntries(){
	var login = $("#login").val();
	var senha 	= $("#senha").val();
	var loginAcacia = $("#loginAcacia").val();
	var error 	= '';

  if(login.length === 0){
    $("#msg").html("Usu&aacute;rio Inv&aacute;lido!");
		$("#errologin").css('display','block');
		fadeError();

		return false;
	}

  if(senha.length === 0){
    $("#msg").html("Senha Inv&aacute;lida!");
		$("#errologin").css('display','block');
		fadeError();

		return false;
	}

	var user = new Array();
	user['login'] = login;
	user['senha'] = senha;
	user['loginAcacia'] = loginAcacia;

	//console.log(user);
	return user;

}

function fadeError(time){
	var tempo = time ? time : '1000' ;
	$("#errologin").delay('5000').fadeOut(tempo);
}

function loginDestroyLogged(admin) {
        var url_local = getHost();

  if (admin !== '') {
		var server = url_local+"login_destroy_logged.php";
		var post= "admin="+admin;
  		$.ajax({
  			url: server,
  			type: "POST",
  			data: post,
  			cache: false,
  			success: function(data) {
  				login();
  			}
  		});
	}
}

function validaError(box_login, codigo, mensagem, admin){
	var url_local = getHost();

	switch(codigo){
		case 'ko':
			$('#msg').html(mensagem);
			$("#errologin").css('display','block');
			fadeError();
			break;

		case '1':
			$('#msg').html(mensagem);
			$("#errologin").css('display','block');
			fadeError();
			break;

		case 'ambiguous':
			$('#msg').html(mensagem);
			$("#errologin").css('display','block');
			fadeError(10000);
			break;

		case 'time':
			url_local = 'http://ww2.telecontrol.com.br/';
			//no-break.... :)
		case 'ok':
			window.parent.location = url_local+mensagem;
			break;

		default:
			$('#msg').html(mensagem);
			$("#errologin").css('display','block');
			fadeError();
			break;
	}
}
