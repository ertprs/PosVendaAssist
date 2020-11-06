//--------------------------------------------------------------------------------------------------------------------------
// Chamar a fun��o requisicaoHTTP passando os parametros:
// 1) tipo        => que pode ser GET ou POST
// 2) URL         => com o caminho completo do programa a ser chamado, incluindo os campos se for GET
// 3) assinc      => true para m�todo ass�ncrono, ou false para s�ncrono (usamos sempre true)
// 4) trata_dados => Opcional. Nome da fun��o que far� a tratativa do resultado
// 5) id          => Opcional. Nome do objeto HTML que receber� o conte�do
// 6) nome_do_form=> Opcional. Nome do objeto HTML FORM que ter� todos seus campos enviados pelo m�todo POST
//--------------------------------------------------------------------------------------------------------------------------

var ajax;
var dadosUsuario;

// ---------- cria o objeto e faz a requisicao ----------
function requisicaoHTTP(tipo, url, assinc, trata_dados, id, nome_do_form){
	if(window.XMLHttpRequest){
		ajax = new XMLHttpRequest();
	}else if(window.ActiveXObject){
		ajax = new ActiveXObject("Msxml2.XMLHTTP");
		if(!ajax){
			ajax = new ActiveXObject("Microsoft.XMLHTTP");
		}
	}
	if(ajax){
		iniciaRequisicao(tipo, url, assinc, trata_dados, id, nome_do_form);
	}else{
		alert("Seu navegador n�o possui suporte a essa aplica��o!");
	}
}

// ---------- Inicializa o objeto criado e envia os dados (se existirem) --------
function iniciaRequisicao(tipo, url, bool, trata_dados, id, nome_do_form){
//	ajax.onreadystatechange = trataResposta;
	ajax.onreadystatechange = function () {
		if(ajax.readyState == 4){
			if(ajax.status == 200){
				ajax_resultado = ajax.responseText;
				ajax_resultado = ajax_resultado.replace ("'","");
				ajax_resultado = ajax_resultado.replace ('"',"");
				ajax_resultado = ajax_resultado.replace ("\n","");
				if (! trata_dados){
					trataDados();   // criar essa fun��o no seu programa
				}else{
					if (trata_dados != 'null'){
						if (! id && id != 0){
							eval(trata_dados+"('" + ajax_resultado + "')")
						}else{
							eval(trata_dados+"('" + ajax_resultado + "'," + id + ")")
						}
					}
				}
			}else{
				alert("Problema na comunica��o com o objeto XMLHttpRequest.");
			}
		}
	}
	ajax.open(tipo, url, bool);
	ajax.setRequestHeader("Content-Type", "application/x-www-form-urlencoded; charset=UTF-8");

	if (typeof (nome_do_form) != "undefined"){
		criaQueryString(nome_do_form);
	}
	ajax.send(dadosUsuario);
}

// ---------- Cria a string a ser enviada, formato campo1=valor1&campo2=valor2
function criaQueryString(nome_do_form){
	dadosUsuario="";
	var frm = nome_do_form;
	var numElementos = frm.elements.length;
	for (var i = 0 ; i < numElementos ; i++){
		if(i < numElementos - 1){
			dadosUsuario += frm.elements[i].name + "=" + encodeURIComponent(frm.elements[i].value) + "&";
		}else{
			dadosUsuario += frm.elements[i].name + "=" + encodeURIComponent(frm.elements[i].value);
		}
	}
}

//---------- Trata a resposta do servidor ----------
// parei de usar (tulio)
function trataResposta(){
	if(ajax.readyState == 4){
		if(ajax.status == 200){
			trataDados();		// criar essa fun��o no seu programa
		}else{
			alert("Problema na comunica��o com o objeto XMLHttpRequest.");
		}
	}
}
