function createRequestObject(){
	var request_;
	var browser = navigator.appName;
	if(browser == "Microsoft Internet Explorer"){
		 request_ = new ActiveXObject("Microsoft.XMLHTTP");
	}else{
		 request_ = new XMLHttpRequest();
	}
	return request_;
}
			
var http_forn = new Array();

	
function liberar_os_item(formulatio) {

	var acao='pega_produto';

	url = "ajax_orcamento_cadastro.php?ajax=sim&acao="+acao;
	for( var i = 0 ; i < formulatio.length; i++ ){
		if (formulatio.elements[i].type=='text' || formulatio.elements[i].type=='hidden'){
			url = url+"&"+formulatio.elements[i].name+"="+escape(formulatio.elements[i].value);
		}
	}

	var com = document.getElementById('dados');
	com.innerHTML = "&nbsp;&nbsp;Aguarde...&nbsp;&nbsp;";

/*	var com3 = document.getElementById('esconde');
	com3.innerHTML = "&nbsp;&nbsp;Verificando...&nbsp;&nbsp;<br><img src='../imagens/carregar2.gif' >";
*/
	var com4 = document.getElementById('lista_basica');

	var curDateTime = new Date();
	http_forn[curDateTime] = createRequestObject();
	http_forn[curDateTime].open('GET',url,true);
	http_forn[curDateTime].onreadystatechange = function(){
		if (http_forn[curDateTime].readyState == 4) 
		{
			if (http_forn[curDateTime].status == 200 || http_forn[curDateTime].status == 304) 
			{
				var response = http_forn[curDateTime].responseText.split("|");
				if (response[0]=="ok"){

					formulatio.produto.value = response[1];
					formulatio.linha.value   = response[2];
					formulatio.familia.value = response[3];
					com.innerHTML  = response[4];
					com4.innerHTML = response[5];
/*					com3.style.visibility = "hidden";*/

				}
				if (response[0]=="1"){
					com.innerHTML = "<i><u> N�o informado</i></u>";
					//com3.style.visibility = "visible";
/*					com3.innerHTML = response[1];*/
				}

			}
		}
	}
	http_forn[curDateTime].send(null);
}

function gravar_os(formulatio) {

	var acao = 'gravar';
	url = "ajax_orcamento_cadastro.php?ajax=sim&acao="+acao;
	for( var i = 0 ; i < formulatio.length; i++ ){
		if (formulatio.elements[i].type !='button'){
			//alert(formulatio.elements[i].name+' = '+formulatio.elements[i].value);
			if(formulatio.elements[i].type=='radio' || formulatio.elements[i].type=='checkbox'){
				
				if(formulatio.elements[i].checked == true){
					alert(formulatio.elements[i].value);
					url = url+"&"+formulatio.elements[i].name+"="+escape(formulatio.elements[i].value);
				}
			}else{
				url = url+"&"+formulatio.elements[i].name+"="+escape(formulatio.elements[i].value);
			}
		}
	}
	var com2 = document.getElementById('erro');
	com2.innerHTML = "&nbsp;&nbsp;Aguarde...&nbsp;&nbsp;<br><img src='../imagens/carregar2.gif' >";

	var saida = document.getElementById('saida');
	var orcamento = document.getElementById('orcamento');
	
	alert(saida);

	var curDateTime = new Date();
	http_forn[curDateTime] = createRequestObject();
	http_forn[curDateTime].open('POST',url,true);
      
	http_forn[curDateTime].setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	http_forn[curDateTime].setRequestHeader("CharSet", "ISO-8859-1");
	http_forn[curDateTime].setRequestHeader("Content-length", url.length);
	http_forn[curDateTime].setRequestHeader("Connection", "close");

	http_forn[curDateTime].onreadystatechange = function(){
		if (http_forn[curDateTime].readyState == 4){
			if (http_forn[curDateTime].status == 200 || http_forn[curDateTime].status == 304){
				alert(http_forn[curDateTime].responseText);
				var response = http_forn[curDateTime].responseText.split("|");
				if (response[0]=="ok"){
					com2.style.visibility = "hidden";
					com2.innerHTML = '';
					orcamento.value = response[1];
					saida.innerHTML = response[2];
					formulatio.btn_acao.value='Gravar';
					document.getElementById('btn_continuar').style.display='inline';
				}else{
					formulatio.btn_acao.value='Gravar';
				}
				if (response[0]=="1"){
					com2.style.visibility = "visible";
					saida.innerHTML = "<font color='#990000'>Ocorreu um erro, verifique acima!</font>";
					com2.innerHTML = response[1];
					formulatio.btn_acao.value='Gravar';
				}
			}
		}
	}
	http_forn[curDateTime].send(null);
}


function listaDefeitos(valor) {
//verifica se o browser tem suporte a ajax
	try {ajax = new ActiveXObject("Microsoft.XMLHTTP");} 
	catch(e) { try {ajax = new ActiveXObject("Msxml2.XMLHTTP");}
		catch(ex) { try {ajax = new XMLHttpRequest();}
				catch(exc) {alert("Esse browser n�o tem recursos para uso do Ajax"); ajax = null;}
		}
	}

//se tiver suporte ajax
	if(ajax) {

	//deixa apenas o elemento 1 no option, os outros s�o exclu�dos
	document.forms[0].defeito_reclamado.options.length = 1;

	//opcoes � o nome do campo combo
	idOpcao  = document.getElementById("opcoes");
	//	 ajax.open("POST", "ajax_produto.php", true);
	ajax.open("GET", "ajax_produto.php?produto_referencia="+valor, true);
	ajax.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
	ajax.onreadystatechange = function() {
		if(ajax.readyState == 1) {idOpcao.innerHTML = "Carregando...!";}//enquanto estiver processando...emite a msg
		if(ajax.readyState == 4 ) {if(ajax.responseXML) { montaCombo(ajax.responseXML);//ap�s ser processado-chama fun
			} else {idOpcao.innerHTML = "Selecione o produto";//caso n�o seja um arquivo XML emite a mensagem abaixo
					}
		}
	}
	//passa o c�digo do produto escolhido
	var params = "produto_referencia="+valor;
	ajax.send(null);
	}
}

function montaCombo(obj){

	var dataArray   = obj.getElementsByTagName("produto");//pega a tag produto
	if(dataArray.length > 0) {//total de elementos contidos na tag cidade
	for(var i = 0 ; i < dataArray.length ; i++) {     //percorre o arquivo XML paara extrair os dados
		 var item = dataArray[i];
		//cont�udo dos campos no arquivo XML
		var codigo    =  item.getElementsByTagName("codigo")[0].firstChild.nodeValue;
		var nome =  item.getElementsByTagName("nome")[0].firstChild.nodeValue;
		idOpcao.innerHTML = "Selecione o defeito";
		//cria um novo option dinamicamente  
		var novo = document.createElement("option");
		novo.setAttribute("id", "opcoes");//atribui um ID a esse elemento
		novo.value = codigo;		//atribui um valor
		novo.text  = nome;//atribui um texto
		document.forms[0].defeito_reclamado.options.add(novo);//adiciona o novo elemento
		}
	} else { idOpcao.innerHTML = "Selecione o defeito";//caso o XML volte vazio, printa a mensagem abaixo
	}
}


function listaSolucao(defeito_constatado, produto_linha,defeito_reclamado, produto_familia) {
//verifica se o browser tem suporte a ajax
		try {ajax = new ActiveXObject("Microsoft.XMLHTTP");} 
	catch(e) { try {ajax = new ActiveXObject("Msxml2.XMLHTTP");}
	catch(ex) { try {ajax = new XMLHttpRequest();}
		catch(exc) {alert("Esse browser n�o tem recursos para uso do Ajax"); ajax = null;}
	}
	}
//se tiver suporte ajax
		if(ajax) {
	//deixa apenas o elemento 1 no option, os outros s�o exclu�dos
			document.forms[0].solucao_os.options.length = 1;
	//opcoes � o nome do campo combo
			idOpcao  = document.getElementById("opcoes");
	//	 ajax.open("POST", "ajax_produto.php", true);
	ajax.open("GET", "ajax_solucao.php?defeito_constatado="+defeito_constatado+"&defeito_reclamado="+defeito_reclamado+"&produto_linha="+produto_linha+"&produto_familia="+produto_familia);
	ajax.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
	
	ajax.onreadystatechange = function() {
		if(ajax.readyState == 1) {idOpcao.innerHTML = "Carregando...!";}//enquanto estiver processando...emite a msg
		if(ajax.readyState == 4 ) {if(ajax.responseXML) { montaComboSolucao(ajax.responseXML);//ap�s ser processado-chama fun
		} else {idOpcao.innerHTML = "Selecione o defeito constatado";//caso n�o seja um arquivo XML emite a mensagem abaixo
		}
		}
	}
	//passa o c�digo do produto escolhido
			var params = "defeito_constatado="+defeito_constatado+"&defeito_reclamado="+defeito_reclamado+"&produto_linha="+produto_linha+"&produto_familia="+produto_familia;
	ajax.send(null);
		}
}

function montaComboSolucao(obj){
	var dataArray   = obj.getElementsByTagName("produto");//pega a tag produto
			if(dataArray.length > 0) {//total de elementos contidos na tag cidade
				for(var i = 0 ; i < dataArray.length ; i++) {     //percorre o arquivo XML paara extrair os dados
					var item = dataArray[i];
		//cont�udo dos campos no arquivo XML
				var codigo    =  item.getElementsByTagName("codigo")[0].firstChild.nodeValue;
					var nome =  item.getElementsByTagName("nome")[0].firstChild.nodeValue;
					idOpcao.innerHTML = "";
		//cria um novo option dinamicamente  
				var novo = document.createElement("option");
					novo.setAttribute("id", "opcoes");//atribui um ID a esse elemento
							novo.value = codigo;		//atribui um valor
									novo.text  = nome;//atribui um texto
											document.forms[0].solucao_os.options.add(novo);//adiciona o novo elemento
				}
			} else { idOpcao.innerHTML = "Nenhuma solu��o encontrada";//caso o XML volte vazio, printa a mensagem abaixo
			}
}
function listaConstatado(linha,familia, defeito_reclamado) {
//verifica se o browser tem suporte a ajax
		try {ajax = new ActiveXObject("Microsoft.XMLHTTP");}
	catch(e) { try {ajax = new ActiveXObject("Msxml2.XMLHTTP");}
	catch(ex) { try {ajax = new XMLHttpRequest();}
		catch(exc) {alert("Esse browser n�o tem recursos para uso do Ajax"); ajax = null;}
	}
	}

//se tiver suporte ajax
		if(ajax) {
	//deixa apenas o elemento 1 no option, os outros s� exclu�os
			document.forms[0].defeito_constatado.options.length = 1;
	//opcoes �o nome do campo combo
			idOpcao  = document.getElementById("opcoes2");
	//	 ajax.open("POST", "ajax_produto.php", true);
	
	ajax.open("GET","ajax_defeito_constatado.php?defeito_reclamado="+defeito_reclamado+"&produto_familia="+familia+"&produto_linha="+linha);
	ajax.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

	ajax.onreadystatechange = function() {
		if(ajax.readyState == 1) {idOpcao.innerHTML = "Carregando...!";}//enquanto estiver processando...emite a msg
		if(ajax.readyState == 4 ) {
			if(ajax.responseXML) {
				montaComboConstatado(ajax.responseXML);
			//ap� ser processado-chama fun
			}
			else {
				idOpcao.innerHTML = "Selecione o defeito reclamado";//caso n� seja um arquivo XML emite a mensagem abaixo
			}
		}
	}
	//passa o c�igo do produto escolhido
	//var params ="defeito_reclamado="+defeito_reclamado+"&produto_familia="+familia+"&produto_linha="+linha";
	ajax.send(null);
		}
}

function montaComboConstatado(obj){
	var dataArray   = obj.getElementsByTagName("produto");//pega a tag produto
			if(dataArray.length > 0) {//total de elementos contidos na tag cidade
				for(var i = 0 ; i < dataArray.length ; i++) {     //percorre o arquivo XML paara extrair os dados
					var item = dataArray[i];
		//cont�do dos campos no arquivo XML
				var codigo    =  item.getElementsByTagName("codigo")[0].firstChild.nodeValue;
					var nome =  item.getElementsByTagName("nome")[0].firstChild.nodeValue;
					idOpcao.innerHTML = "Selecione o defeito";
		//cria um novo option dinamicamente  
				var novo = document.createElement("option");
					novo.setAttribute("id", "opcoes2");//atribui um ID a esse elemento
							novo.value = codigo;		//atribui um valor
									novo.text  = nome;//atribui um texto
											document.forms[0].defeito_constatado.options.add(novo);//adiciona
//onovo elemento
				}
			} else { idOpcao.innerHTML = "Selecione o defeito";//caso o XML volte vazio, printa a mensagem abaixo
			}
}




function Integridade (linha,familia,defeito_reclamado) {
	var com_integridade = document.getElementById('integrigade');
	var img_integridade = document.getElementById('img_inte');

	if(com_integridade.style.visibility == 'hidden'){

		img_integridade.src='imagens/menos.gif';
		com_integridade.style.visibility = "visible";
		com_integridade.innerHTML = "Aguarde...";
		var acao = 'integridade';
	
		url = "ajax_orcamento_cadastro.php?ajax=sim&acao="+acao+"&linha=" +linha+"&familia="+familia+"&defeito_reclamado="+defeito_reclamado ;
	
		var curDateTime = new Date();
		http_forn[curDateTime] = createRequestObject();
		http_forn[curDateTime].open('GET',url,true);
		http_forn[curDateTime].onreadystatechange = function(){
			if (http_forn[curDateTime].readyState == 4){
				if (http_forn[curDateTime].status == 200 || http_forn[curDateTime].status == 304){
					var response = http_forn[curDateTime].responseText.split("|");
					if (response[0]=="ok"){
						com_integridade.innerHTML = response[1];
					}
				}
			}
		}
		http_forn[curDateTime].send(null);
	}else{
		com_integridade.style.visibility = "hidden";
		img_integridade.src='imagens/mais.gif';
	}
}


/* ============= Fun��o PESQUISA DE CONSUMIDOR POR NOME ====================
Nome da Fun��o : fnc_pesquisa_consumidor_nome (nome, cpf)
=================================================================*/
function fnc_pesquisa_consumidor (campo, tipo) {
	var url = "";
	if (tipo == "nome") {
		url = "pesquisa_consumidor_orcamento.php?nome=" + campo.value + "&tipo=nome";
	}
	if (tipo == "cpf") {
		url = "pesquisa_consumidor_orcamento.php?cpf=" + campo.value + "&tipo=cpf";
	}
	janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0");
	janela.cliente		= document.frm_os.consumidor_cliente;
	janela.nome			= document.frm_os.consumidor_nome;
	janela.cpf			= document.frm_os.consumidor_cpf;
	janela.rg			= document.frm_os.consumidor_rg;
	janela.cidade		= document.frm_os.consumidor_cidade;
	janela.estado		= document.frm_os.consumidor_estado;
	janela.fone			= document.frm_os.consumidor_fone;
	janela.endereco		= document.frm_os.consumidor_endereco;
	janela.numero		= document.frm_os.consumidor_numero;
	janela.complemento	= document.frm_os.consumidor_complemento;
	janela.bairro		= document.frm_os.consumidor_bairro;
	janela.cep			= document.frm_os.consumidor_cep;
	janela.email		= document.frm_os.consumidor_email;
	janela.focus();
}
