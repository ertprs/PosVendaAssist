  /**
  * Script responsável por executar funções básicas
  * 
  * @autor Fábio Jean Camargo <fabiojeancamargo@msn.com>
  * @version 1.0 - 21/05/2008 10:47
  * @package Kernel (Ajax, DOM...)
  *
	*/
	
	//Função para verificar se o valor é numérico
  function VerificaNumero(campo){
		
    ValorCampo = campo.value;
		
		if (ValorCampo == ""){
			alert('Digite a quantidade desejada!');
			campo.value = "";
			campo.focus();	
			return false;
		}
  
		if (ValorCampo != ""){   	
      if (isNaN(parseInt(ValorCampo))){  //Verifica se número é inteiro	  
	  	  alert('Digite apenas números inteiros!');
        campo.value = "";
	    	campo.focus();
				return false;
   	  } else if (ValorCampo <= 0){  //Verifica se número é mair que 0	    	
		    alert('Digite um número maior que zero!');
      	campo.value = "";
				campo.focus();	
				return false;
		  }
    }   
  }
		
  //Função para verificar se o valor é numérico, maior ou igual 0
  function VerificaMoney(campo, aceitarZero){
		
    ValorCampo = campo.value;
		
		if (ValorCampo == ""){
			alert('Digite o valor!');
			campo.value = "";
			campo.focus();	
			return false;
		}
  
		if (ValorCampo != ""){   	
      if (isNaN(parseInt(ValorCampo))){  //Verifica se número é inteiro	  
	  	  alert('Digite apenas números!');
        campo.value = "";
	    	campo.focus();
				return false; 
   	  } else if (ValorCampo < 0 && aceitarZero == 1){
		    alert('Digite um número maior ou igual a zero!');
      	campo.value = "";
				campo.focus();	
				return false;
		  }
    }   
  }		
		
//Função para abrir uma janela popup com confirmação (Se a strURL não conter valor então abre uma nova instância da janela atual)
 function popup(strNome, strURL, strMensagem, intLargura, intAltura, intPosTopo, intPosEsquerda){
		var strAlvo = strNome;
		var intW = intLargura;
		var intH = intAltura;
		var intY = intPosTopo;
		var intX = intPosEsquerda;
		
		var strOpcoes  = 'left=' + intX + ',top=' + intY + ',toolbar=no,titlebar=no,location=no,status=yes,menubar=no,scrollbars=yes,resizable=no,width=' + intW + ',height=' + intH +'';
	
		if (strURL == ""){	   
			strURL = document.URL;
		}	  
			
		if (strMensagem == ""){		
				document.open(strURL, strAlvo, strOpcoes, true);  		  		 
		} else {		
				if (confirm(strMensagem)){			
					document.open(strURL, strAlvo, strOpcoes, true);  		  			
				} 	   
		}
  }

    //Função para extrair valores selecionados num popup (Opener)
	function getIdOpener(fieldDestination, fieldvalue, DivID, DivContent){
	  window.opener.document.getElementById(fieldDestination).value=fieldvalue;
		if (DivID != ''){
			window.opener.document.getElementById(DivID).innerHTML=DivContent;		  
		}
		window.close();
	}

	//Função para enviar dados ao um popup
	function pesquisa_generica (strNomePopUp, strMensagem, intLargura, intAltura, intPosTopo, intPosEsquerda, strCampo, strTipo, strUrlOrigem, strUrlDestino){
		if (strCampo.value != "") {
			strUrlDestino	= strUrlDestino + "?retorno=" + strUrlOrigem + "&forma=''&campo=" + strCampo.value + "&tipo=" + strTipo;
			//Criando o objeto janela instanciando a função popup
			var janela = popup(strNomePopUp, strUrlDestino, strMensagem, intLargura, intAltura, intPosTopo, intPosEsquerda);
			janela.retorno = strUrlOrigem;
			janela.descricao = strCampo.value;
			janela.focus();
		}
	}


	//Função para verificar se determinado input está preenchido
	function validate(form, fields) {
		//Verificar se inputs está preenchido
		var intNumErrors = 0;		
		il=document.getElementById(form.id).getElementsByTagName('input');   
		for(i=0;i<il.length;i++){
			il[i].className='campo_validado';
		}
    }		


/**
* Script responsável por carregar o AJAX num determinado elemento
* 
* @autor Fábio Jean Camargo <fabiojeancamargo@msn.com>
* @version 1.0 - 07/11/2006 10:00
* @package Kernel (Ajax, DOM...)
*
*/

//-------------- Efeitos enquanto o ajax estiver requisitando o arquivo solicitado --------------------

  var efeitoAjax = new Array();

  efeitoAjax[0] = '<div style="color:#000;font-size:9px;font-family:Verdana, Arial, Helvetica, sans-serif;background:#fff;"><br />&nbsp;<img src="loadingAnimation.gif" align="absmiddle"/>&nbsp;Carregando...&nbsp;</strong><br /></br></br></div>';
  efeitoAjax[1] = '<img src="loading.gif" align="absmiddle"/>&nbsp;Enviando...&nbsp;';
  efeitoAjax[2] = 'Qualque imagem, div, o que quiser...';

//-------------------------------------------------------------------------------------------------------

  /* ::: --- Função para criar o objeto XMLHTTPRequest --- :::
  Autor: Fábio Jean Camargo <fabiojeancamargo@msn.com>
  Criado em: 30-11-06, modificado em: 30-11-06 */  
  function objXMLHttp(){
		if (window.XMLHttpRequest) { // Mozilla, Safari, ...
			var objetoXMLHttp = new XMLHttpRequest();
			return objetoXMLHttp;
		} else if (window.ActiveXObject) { // IE			
			var versoes = ["MSXML2.XMLHTTP.6.0", "MSXML2.XMLHttp.5.0",
							"MSXML2.XMLHttp.4.0", "MSXML2.XMLHttp.3.0",
							"MSXML2.XMLHttp", "Microsoft.XMLHttp"];		
			for (var i = 0; i < versoes.length; i++) {
				try {
					var objetoXMLHttp = new ActiveXObject(versoes[i]);
						return objetoXMLHttp;
					} catch (ex) {
						//nada aqui
				}
			}
		}
		return false;
  }

  /* ::: --- Função para usar o ajax --- :::
  Autor: Fábio Jean Camargo <fabiojeancamargo@msn.com>
  Criado em: 30-11-06, modificado em: 30-11-06 */  
  function carregarX(urlID, elementID){
    var ajax = false;
    ajax = objXMLHttp();
    //se tiver suporte ajax
    if(ajax){     
  	  var conteudo=document.getElementById(elementID);
      conteudo.innerHTML=efeitoAjax[0]; //Aqui escolho qual efeito usar enquanto espero retorno do objXMLHttp()
      //Abre a URL
      ajax.open("GET",urlID,true);
      ajax.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
      //Executada quando o navegador obtiver o código
      ajax.onreadystatechange=function(){		
        // se readyState é 4, lê a resposta do servidor  
				if (ajax.readyState == 4 || ajax.readyState == 0){ //Se leu completamente      
					// continua somente se o status for 200 (OK)
					if (ajax.status == 200){   
						//Lê a resposta
						var resposta=ajax.responseText;
						// erro no servidor?
						if (resposta.indexOf("ERRNO") >= 0 || resposta.indexOf("error") >= 0 || resposta.length == 0) { // mostrar a mensagem de erro
						  alert(resposta.length == 0 ? "Erro no servidor." : resposta);
						  //Aborta e sai da função
							ajax.abort();
						  return;
						}
					  else
					  { //Desfaz o urlencode
							resposta=resposta.replace(/\+/g," ");
							resposta=unescape(resposta);				
						  //Exibe o texto no elemento especificado
						  var conteudo=document.getElementById(elementID);
						  conteudo.innerHTML=resposta;		
				    }
					} 
        }	  
      }    
	    ajax.send(null);
    }    
  }
	

  /* ::: --- Função para enviar dados de formulário via ajax --- :::
  Autor: Fábio Jean Camargo <fabiojeancamargo@msn.com>
  Criado em: 30-11-06, modificado em: 30-11-06 */  
  function dd_sendFormAjax(form, fields, url, setedFields, elementID) {			  
		//Verificar se inputs está preenchido
		var intNumErrors = 0;		
    il=document.getElementById(form.id).getElementsByTagName('input');   
    for(i=0;i<il.length;i++){
   	  il[i].className='campo_validado';		 
    }		

    for (i=0;i<fields.length;i++) {	
  	  inputSel=document.getElementById(fields[i]);
  		if (inputSel.value == ""){		  
  			 intNumErrors++;
				 if (intNumErrors == 1){
				   inputFirst = inputSel;
				 }
  		   inputSel.className='campo_requerido';				   		  
	    } 	
		}
		if (intNumErrors >= 1){
			alert('Por favor, preencha os campos assinalados em vermelho');
			inputFirst.focus();
			return false;
			
		} else {
			var ajax = false;
			ajax = objXMLHttp();
			//se tiver suporte ajax
			if(ajax){     
				var elementID=document.getElementById(elementID);
				elementID.innerHTML = efeitoAjax[1];      
				//Executada quando o navegador obtiver o código
				ajax.onreadystatechange=function(){		
					// se readyState é 4, lê a resposta do servidor  
					if (ajax.readyState == 4 || ajax.readyState == 0){ //Se leu completamente      
						// continua somente se o status for 200 (OK)
						if (ajax.status == 200){   
							//Lê a resposta
							var resposta=ajax.responseText;
							// erro no servidor?
							if (resposta.indexOf("ERRNO") >= 0 || resposta.indexOf("error") >= 0 || resposta.length == 0) { // mostrar a mensagem de erro
								alert(resposta.length == 0 ? "Erro no servidor." : resposta);
								//Aborta e sai da função
								ajax.abort();
								return;
							}
							else							
							{ //Desfaz o urlencode							
								resposta=resposta.replace(/\+/g," ");
								resposta=unescape(resposta);				
								//Exibe o texto no elemento especificado						  
								elementID.innerHTML="Redirecionando...";								
								//Fazendo o tratamento sobre a resposta								
								if (resposta != ""){									 
								  window.location.href=resposta;
							  }							
							}
						} 
					}	  
				}		
				ajax.open('POST', url+'?'+setedFields, true); //Abre a página que receberá os campos do formulário
				ajax.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
				ajax.send(setedFields); //Envia o formulário com dados da variável 'campos' (passado por parâmetro)
			}    
		}
	}