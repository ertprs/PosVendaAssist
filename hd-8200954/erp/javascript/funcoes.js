/*INDICE DE FUNCOES
#0-1 - Retorna Bem
#0-2 - Retorna Empresa
#0-3 - Retorna Transportadora
#0-4 - Retorna Documento
#1 - mostra_oculta
#2 - mostra_filtro
#3 - iconUpDown
#4 - toUpper
#6 - data
#7 - vazio
#8 - validarSenha
#9 - cep
#10 - validaCnpj
#11 - validaCpf
#12 - validarFormulario
#13 - numero
#14 - validarFuncionalidade
#15 - limpar
#16 - mostraCampos
#17 - formataData
#18 - capturaMes
#19 - adicionarAtividades
#20 - removerAtividades
#21 - formataValor
#22 - somaCustoProjeto
#23 - montaTabela
#24 - alterarId
#25 - insereElemento
#26 - recuperaId
#27 - removerElemento
#28 - currency
#29 - formatCurrency
#30 - somaParcela
#31 - incluirEquipamento
#32 - textTamanho
#33 - formataCpf
#34 - formataCnpj
INDICE DE FUNCOES*/


//#0-1 - PEGAR A PK DO BEM PARA TESTAR SE O BEM ESTÁ NA MANUTENCAO!
function getbem(){ 
	location.href = "telamanutencao.php?id=montar&idbem=" + document.frmDados.fkidbemnr.value +"&saida_idmanu=" +document.frmDados.saida_idmanu.value; 
} 


//#0-2 - retorna para teladoc.php a empresa selecionada, através do combobox
function gotomyurl() 
{ 
  location.href = "teladoc_saida.php?id=emp&fkidemprnr=" + document.form1.fkidemprnr.value; 
}

//#0-3 - PEGAR TRANSPORTADORA PARA O DOCUMENTO DE SAIDA
function gettransp(){ 
  location.href = "teladoc_saida.php?id=transp&idtransp=" + document.form2.idtransp.value; 
} 

//#0-4 - retorna para teladoc.php o tipo de documento que será gerado, através do combobox
function getdoc() { 
  location.href = "teladoc_saida.php?id=tipodoc&tipodoc=" + document.form1.tipodoc.value; 
} 
      


/*#1 - MOSTRA E OCULTA OS ELEMENTOS*/
function mostra_oculta(a){
	var cont = 1;
	var elemento = "obj_"+cont;
	while(document.getElementById(elemento) != undefined){
	 	document.getElementById(elemento).style.display = 'none';
		cont++;
		elemento = "obj_"+cont;
	}
	elemento = "obj_"+a;
	document.getElementById(elemento).style.display = '';
}

/*#2 - MOSTRA E OCULTA O FILTRO*/
function mostra_filtro(elemento){
//	alert(elemento);
	if(document.getElementById(elemento).style.display == ""){
		document.getElementById(elemento).style.display = 'none';
	}else{
		document.getElementById(elemento).style.display = '';
	}
}

/*#3 - ALTERA ICONE DO FILTRO*/
function iconUpDown(icone){
	if( icone.src.indexOf("iconeUP.png") > 0 ){
		icone.src = "../imagens/iconeDOWN.png";
	}else{
		icone.src = "../imagens/iconeUP.png";
	}
}	
	
/*#4 - MAXIMIZA A LETRA DO DOCUMENTO*/
function toUpper(campo){
	campo.value = campo.value.toUpperCase();			
}	

/*#5 VALIDAR LOGIN*/
function validarLogin(login){
	if(login == "")
		return true;
	else
		return false;
}
/*#6 VALIDAR DATA */
function data(text){
	if (text == "")
		return true;
	var mascara   = new RegExp(/^(0[1-9]|[12][0-9]|3[01])[-\/.](0[1-9]|1[012])[-\/.](19|20)\d\d$/);
	var resultado = mascara.exec(text);	

	if (resultado == null)
		return true;
	else
		return false;
}
/*#7 VERIFICA SE O CAMPO ESTA VAZIO*/
function vazio(text){
	if(text == "")
		return true;
	else
		return false;
}
/*#8 VALIDAR SENHA*/
function validarSenha(senha,senha1){
	if(senha != senha1){
		senha  = '';
		senha1 = '';
		return true;
	}
	if(senha.length < 6){
		return true;
	}
	return false;
}

/*#9 - VALIDA CEP*/
function cep(text){
	// 81.320-987
	if (text == "")
		return true;
	var mascara   = new RegExp(/^\d{5}-\d{3}$/); ///^\d{2}\.\d{3}-\d{3}$/
	var resultado = mascara.exec(text);	

	if (resultado == null)
		return true;
	return false;
}

/*#10 FUNCAO PARA VALIDAR O CNPJ*/
function validaCnpj(valor) {
		 var erro;
		 if (valor.length < 18) erro = 1;
         if ((valor.charAt(2) != ".") || (valor.charAt(6) != ".") || (valor.charAt(10) != "/") || (valor.charAt(15) != "-")){
         	erro = 1;
         }
         //substituir os caracteres que não são números
         if(document.layers && parseInt(navigator.appVersion) == 4){
            x = valor.substring(0,2);
            x += valor.substring (3,6);
            x += valor.substring (7,10);
            x += valor.substring (11,15);
            x += valor.substring (16,18);
            valor = x;
        } else {
            valor = valor. replace (".","");
            valor = valor. replace (".","");
            valor = valor. replace ("-","");
            valor = valor. replace ("/","");
        }
        var nonNumbers = /\D/;		
		var a = [];
        var b = new Number;
        var c = [6,5,4,3,2,9,8,7,6,5,4,3,2];
        for (j=0; j<12; j++){
        	a[j] = valor.charAt(j);
        	b += a[j] * c[j+1];
 		}
        if ((x = b % 11) < 2) { a[12] = 0 } else { a[12] = 11-x }
        b = 0;
        for (y=0; y<13; y++) {
        	b += (a[y] * c[y]);
        }
        if ((x = b % 11) < 2) { a[13] = 0; } else { a[13] = 11-x; }
        if ((valor.charAt(12) != a[12]) || (valor.charAt(13) != a[13])){
        	erro = 1;
        }
        if (erro == 1){
        	return true;
        } else {
        	return false;
        }	
}

/*#11 FUNCAO PARA VALIDAR O CPF*/
function validaCpf(valor){
 	
	if (valor.length < 14) return true;
    if ((valor.charAt(3) != ".") || (valor.charAt(7) != ".") || (valor.charAt(11) != "-")){
    	return true;
    }
	 //substituir os caracteres que não são números
    if(document.layers && parseInt(navigator.appVersion) == 4){
    	x = valor.substring(0,3);
    	x += valor.substring (4,7);
   		x += valor.substring (8,11);
    	x += valor.substring (12,15);    	
		valor = x;		
    }else{
    	valor = valor.replace (".","");
		valor = valor.replace (".","");
    	valor = valor.replace ("-","");   	
    }		
	var i; 
	s = valor; 
	var c = s.substr(0,9); 
	var dv = s.substr(9,2); 
	var d1 = 0; 
	for (i = 0; i < 9; i++){ 
		d1 += c.charAt(i)*(10-i); 
	} 
	if (d1 == 0){ 
		return true; 
	} 
	d1 = 11 - (d1 % 11); 
	if (d1 > 9) d1 = 0; 
	if (dv.charAt(0) != d1){ 
		return true; 
	}
	d1 *= 2; 
	for (i = 0; i < 9; i++){ 
		d1 += c.charAt(i)*(11-i); 
	} 
	d1 = 11 - (d1 % 11); 
	if (d1 > 9) d1 = 0; 
	if (dv.charAt(1) != d1){ 
		return true; 
	} 
	return false; 
} 

/*#12 FUNCAO UTILIZADA PARA VALIDAR FORMULÁRIOS */
//EX : validar_formulario(array('siglasisvc'),array('vazio'),array('sigla'),arry('0'));
function validarFormulario(campos,funcao,descricao,verifica){
	var total = campos.length;

	for(i=0;i<total;i++){
		 if((verifica[i] == '1' && document.frmDados[campos[i]].value != '') || verifica[i] == '0'){
		 	bool = true;
		 }else{
		 	bool = false
		 }
		 //alert("campos: "+ campos[i]+' funcao- '+funcao[i]+' - '+descricao[i]+' - '+verifica[i]);		 	
		 switch(funcao[i]){		 
		 	case "cep":
				if(cep(document.frmDados[campos[i]].value) && bool){
					alert('É necessário preencher o campo '+descricao[i]+' corretamente!\nO campo '+descricao[i]+' deve estar no seguinte padrão: XXXXX-XXX');
					document.frmDados[campos[i]].focus();
					return false; 
				}		
				break;
			case "vazio":
				if(vazio(document.frmDados[campos[i]].value) && bool){
					alert('É necessário preencher o campo '+descricao[i]+' corretamente!');
					document.frmDados[campos[i]].focus();
					return false; 
				}	
				break;

			case "data":
				if(data(document.frmDados[campos[i]].value) && bool){
					alert('É necessário preencher o campo '+descricao[i]+' corretamente!');
					document.frmDados[campos[i]].focus();
					return false; 
				}	
				break;

			case "validarLogin":
				if(validarLogin(document.frmDados[campos[i]].value) && bool){
					alert('É necessário preencher o campo '+descricao[i]+' corretamente\nO login deve ter no máximo 8 caracteres!');
					document.frmDados[campos[i]].focus();
					return false; 
				}		
				break;

			case "validarSenha":
				if(validarSenha(document.frmDados.senhapessvc.value,document.frmDados.senhapessvc1.value) && bool){
					alert('É necessário preencher o campo '+descricao[i]+' corretamente\n As senhas devem ser iguais e devem ter no mínimo 6 caracteres!');
					document.frmDados[campos[i]].focus();
					return false; 
				}
				break;
				
			case "cnpj":
				if(validaCnpj(document.frmDados[campos[i]].value) && bool){
					alert('É necessário preencher o campo '+descricao[i]+' corretamente!');
					document.frmDados[campos[i]].focus();					
					return false; 
				}	
				break;
				
			case "cpf":
				if(validaCpf(document.frmDados[campos[i]].value) && bool){
					alert('É necessário preencher o campo '+descricao[i]+' corretamente!');
					document.frmDados[campos[i]].focus();					
					return false; 
				}	
				break;	

			case "combo":					
				if(vazio(document.frmDados[campos[i]].options[document.frmDados[campos[i]].selectedIndex].value) && bool){
					alert('É necessário preencher o campo '+descricao[i]+' corretamente!');					
					return false; 
				}
				break;						
		}	
	}
	return true;

}
/*#13- SOMENTE NUMEROS */
function numero(e){	
	if (document.all) // Internet Explorer
		var tecla = e.keyCode;
	else
		var tecla = e.which;	

	if (tecla > 47 && tecla < 58){ // numeros de 0 a 9
			return true;
	}
	else{
		if (tecla == 8 || tecla == 0) // backspace
			return true;
		else
			return false;
	}
}	
/*#14 VALIDA OS CAMPOS PARA PREENCHIMENTO DAS FUNCIONALIDADES*/
function validarFuncionalidade(pkidsisnr,codpermfunnr,descfunnr){
	pkidsisnr=1;
		if(pkidsisnr == ""){
			alert('O Cadastro de funcionalidade não pode ser realizado.\n Motivo: Não foi realizado o cadastro do sistema correspondente!');
			return false;
		}
		if(codpermfunnr == ""){
			alert('O Cadastro de funcionalidade não pode ser realizado.\n Motivo: Preencha o campo CÓDIGO corretamente!');
			return false;
		}
		if(descfunnr == ""){
			alert('O Cadastro de funcionalidade não pode ser realizado.\n Motivo: Preencha o campo DESCRIÇÃO corretamente!');
			return false;
		}
		return true;
}	
/*#15 FUNCAO QUE LIMPA TODOS OS CAMPOS DO FORMULARIO */
function limpar(a){	
	for(i=0;document.forms[a].elements[i] != undefined;i++){
		//alert(document.forms[a].elements[i].type);
		if(document.forms[a].elements[i].type == "password"){
			document.forms[a].elements[i].value='';
		}		
		if(document.forms[a].elements[i].type == "text"){			
			document.forms[a].elements[i].value='';
		}
		if(document.forms[a].elements[i].type == "select-one"){
			document.forms[a].elements[i].selectedIndex = '';
		}
		if(document.forms[a].elements[i].type == "textarea"){
			document.forms[a].elements[i].value='';
		}		
		if(document.forms[a].elements[i].type == "");{
			document.forms[a].elements[i].checked = false;
		}		
	}
}

/*#16 FUNCAO QUE MOSTRA OS CAMPOS*/
function mostraCampos(campo,indicado){
	var indice = campo+1;
	var i = 1;
	while(document.getElementById(indice)){
		document.getElementById(indice).style.display = 'none';
		indice = campo+i;
		i++;
	}
	document.getElementById(indicado).style.display = '';
}

/*#17 FUNCAO PARA FORMATAR A DATA*/
function formataData(campo,teclapres){
	var tecla = teclapres.keyCode;
	vk = eval(campo);
	vr = vk.value;
	vr = vr.replace( ".", "" );
	vr = vr.replace( "/", "" );
	vr = vr.replace( "/", "" );
	tam = vr.length + 1;
	if ( tecla != 9 && tecla != 8 ){
		if ( tam > 2 && tam < 5 )
			vk.value = vr.substr( 0, tam - 2  ) + '/' + vr.substr( tam - 2, tam );
		if ( tam >= 5 && tam <= 10 )
			vk.value = vr.substr( 0, 2 ) + '/' + vr.substr( 2, 2 ) + '/' + vr.substr( 4, 4 ); 
	}
}


/*# 18 FUNCAO PARA CAPTURAR O MES*/
function capturaMes(mes){																						
	switch(mes){
		case 0:
			return "jan";
		break;
		case 1:
			return "fev";
		break;
		case 2:
			return "mar";
		break;
		case 3:
			return "abr";
		break;
		case 4:
			return "mai";
		break;
		case 5:
			return "jun";
		break;
		case 6:
			return "jul";
		break;
		case 7:
			return "ago";
		break;
		case 8:
			return "set";
		break;
		case 9:
			return "out";
		break;
		case 10:
			return "nov";
		break;
		case 11:
			return "dez";
		break;
	}
}
											
/*# 19 FUNCAO PARA ADICIONAR ATIVIDADES*/											
function adicionarAtividades(nome){	
	if(nome != null && nome != ""){
		var arrayData = new Array();//array de data											
		var data = document.frmDados.dataprojdt.value; //inputs do html utilizados
		var duracao = document.frmDados.duracaoprojnr.value;
		var periodo = document.getElementById('periodo_1').checked;
		if(duracao >= 13 && periodo){//nesese
			alert("Erro: O número de meses não pode ser superior a 12!");
			return false;
		}else{
			if(duracao >= 11 && !periodo){
				alert("Erro: O número de semanas não pode ser superior a 10!");
				return false;
			}
		}		
				
		document.getElementById('duracaoprojnr').readOnly=1;
		document.getElementById('periodo_1').disabled=true;
		document.getElementById('periodo_2').disabled=true;
		
		var linha = "";										
		
		dia = data.substr(0, 2);//captura o dia da data
	
		mes = data.substr(3, 2);//captura o mes da data
	
		ano = data.substr(6, 4);//captura o ano da data
		
		for(var i = 0;i < duracao;i++){//while para adicionar os meses de acordo com a duração
			if(periodo){//se periodo for verdadeiro a duracao será em meses
				( data = new Date(ano , (mes - 1) , dia) ).setMonth( data.getMonth() + i );
				mes1 = (data.getMonth());
				ano1 = (data.getFullYear());												
				arrayData[i] = capturaMes(mes1)+"/"+ano1;//monta as datas
			}else{//a duração é em semanas
				var semana = i * 7;				
				( data = new Date(ano , (mes - 1) , dia)).setDate( data.getDate() + semana );												
				dia1 = (data.getDate())
				mes1 = (data.getMonth()+1);				
				if(dia1 < 10) var fulldia = "0"+dia1; else fulldia = dia1;//captura o mes inteiro ex: 01
				if(mes1 < 10) var fullmes = "0"+mes1; else fullmes = mes1;//captura o mes inteiro ex: 01
				arrayData[i] = fulldia+"/"+fullmes;//monta as datas								
			}
		}	
		document.frmDados.cronogramaprojvc.value = arrayData;
		for(var i = 0;arrayData[i];i++){//monta as colunas da tabela de acordo com os meses
			linha += "<td nowrap><input type='checkbox' name='particativvc["+nome+"][]' value='"+i+"' class='checkbox'><font style='font-size: 10px;'>"+arrayData[i]+"</font></td>";
		}	
		//monta a tabela com o cronograma desejado
		linha1 = "<table cellpadding='0' cellspacing='1' width='100%'><tr><td width='15%' style='border-bottom: 1px solid #000000;'><font style='font-size: 10px;'>"+nome+"</font></td>";
		linha2 = "<td style='border-bottom: 1px solid #000000;'><table cellpadding='0' cellspacing='0' border='0'>";
		linha3 = "<tr>"+linha+"</tr>";
		linha4 = "</table></td></tr></table>";
		document.getElementById('atividade').innerHTML += linha1+linha2+linha3+linha4;
	}																								
}

/*# 20 FUNCAO PARA REMOVER AS ATIVIDADES*/
function removerAtividade(){
	document.getElementById('duracaoprojnr').readOnly=0;
	document.getElementById('periodo_1').disabled=false;
	document.getElementById('periodo_2').disabled=false;
	document.getElementById('atividade').innerHTML = "";//limpa o elemento do HTML
}

/*#21 FUNCAO PARA FORMATAR O VALOR NUMERICO */
function formataValor(campo,tammax,teclapres){
	var tecla = teclapres.keyCode;
	vk = eval(campo);
	vr = vk.value;
	vr = vr.replace( "/", "" );
	vr = vr.replace( "/", "" );
	vr = vr.replace( ",", "" );
	vr = vr.replace( ".", "" );
	vr = vr.replace( ".", "" );
	vr = vr.replace( ".", "" );
	vr = vr.replace( ".", "" );
	tam = vr.length;
	if (tam < tammax && tecla != 8){ tam = vr.length + 1 ; }
	if (tecla == 8 ){	tam = tam - 1 ; }
	if ( tecla == 8 || tecla >= 48 && tecla <= 57 || tecla >= 96 && tecla <= 105 ){
		if ( tam <= 2 ){ 
	 		vk.value = vr ; }
	 	if ( (tam > 2) && (tam <= 5) ){
	 		vk.value = vr.substr( 0, tam - 2 ) + ',' + vr.substr( tam - 2, tam ) ; }
	 	if ( (tam >= 6) && (tam <= 8) ){
	 		vk.value = vr.substr( 0, tam - 5 ) + '.' + vr.substr( tam - 5, 3 ) + ',' + vr.substr( tam - 2, tam ) ; }
	 	if ( (tam >= 9) && (tam <= 11) ){
	 		vk.value = vr.substr( 0, tam - 8 ) + '.' + vr.substr( tam - 8, 3 ) + '.' + vr.substr( tam - 5, 3 ) + ',' + vr.substr( tam - 2, tam ) ; }
	 	if ( (tam >= 12) && (tam <= 14) ){
	 		vk.value = vr.substr( 0, tam - 11 ) + '.' + vr.substr( tam - 11, 3 ) + '.' + vr.substr( tam - 8, 3 ) + '.' + vr.substr( tam - 5, 3 ) + ',' + vr.substr( tam - 2, tam ) ; }
	 	if ( (tam >= 15) && (tam <= 17) ){
	 		vk.value = vr.substr( 0, tam - 14 ) + '.' + vr.substr( tam - 14, 3 ) + '.' + vr.substr( tam - 11, 3 ) + '.' + vr.substr( tam - 8, 3 ) + '.' + vr.substr( tam - 5, 3 ) + ',' + vr.substr( tam - 2, tam ) ;}
	}		
}

/*#22 FUNCAO PARA SOMAR OS CAMPOS QUE COMPÕEM O CUSTO DO PROJETO */
function somaCustoProjeto(){
	soma = 0;
	soma += currency(document.getElementById('custo_0').value);
	soma += currency(document.getElementById('custo_1').value);
	soma += currency(document.getElementById('custo_2').value);
	soma += currency(document.getElementById('custo_3').value);
	document.getElementById('custo_projeto').value = formatCurrency(soma);
}

/*#23 FUNCAO PARA MONTAR DINAMICAMENTE AS TABELAS DO CONSULTOR E PARA PAGAMENTOS */
function montaTabela(cont,i,tipo){
	var valor1 = "";
	var valor2 = "";
	var valor3 = "";
	if(tipo == 1){
		campo1 = "vencimento";
		campo2 = "pagamento";
		campo3 = "status";
		valor3=0;
		if(document.getElementById(campo1+'_'+i))
			valor1 = document.getElementById(campo1+'_'+i).value;
		if(document.getElementById(campo2+'_'+i))
			valor2 = document.getElementById(campo2+'_'+i).value;
		if(document.getElementById(campo3+'_'+i))
			valor3 = document.getElementById(campo3+'_'+i).value;
		linha = "<table width='100%' cellpadding='0' cellspacing='1'>";
		linha += "<tr align='left'>";
		linha += "<td width='20%'>parcela "+cont+"</td>";
		linha += "<td width='20%'><input type='hidden' name='statuspaganr[]' id='"+campo3+"_"+i+"' value='"+valor3+"'> <input type='text' name='vencpagadt[]' id='"+campo1+"_"+i+"' value='"+valor1+"' size='10' maxlength='10' onKeyDown='formataData(this,event);'></td>";
		linha += "<td width='40%'><input type='text' name='valorpaganr[]' id='"+campo2+"_"+i+"' value='"+valor2+"' onKeyDown='formataValor(this,13,event);' onChange='somaParcela(\"valorpaganr[]\",\"total_parcela\");'></td>";															
		linha += "<td align='center'><a href='#' onClick='removerElemento(\"parcela_"+i+"\"); somaParcela(\"valorpaganr[]\",\"total_parcela\"); return false;'>Remover</a></td>"
		linha += "</tr>";
		linha += "</table>";
	}
	if(tipo == 2){
		campo1 = "nome";
		campo2 = "valor";
		campo3 = "fkidpessnr";
		if(document.getElementById(campo1+'_'+i))
			valor1 = document.getElementById(campo1+'_'+i).innerHTML;
		if(document.getElementById(campo2+'_'+i))
			valor2 = document.getElementById(campo2+'_'+i).value;
		if(document.getElementById(campo3+'_'+i))
			valor3 = document.getElementById(campo3+'_'+i).value;
		
		linha = "<table width='100%' cellpadding='0' cellspacing='1'>";
		linha += "<tr>";
		linha += "<td width='40%'><span id='"+campo1+"_"+i+"'>"+valor1+"</span> <input type='hidden' name='fkidpessnr[]' id='"+campo3+"_"+i+"' value='"+valor3+"'>";
		linha += "<img src='../imagens/view.png' onClick='alterarId(\""+campo1+"_"+i+"\",\""+campo3+"_"+i+"\"); window.open(\"poppessoa.php\", \"_blank\", \"width=530,height=380,statusbar=0,resizable=no,scrollbars=no\");' style='cursor: pointer;'></td>";
		linha += "<td width='40%'><input name='valorppronr[]' type='text' id='"+campo2+"_"+i+"' value='"+valor2+"' onKeyDown='formataValor(this,13,event);' onChange='somaParcela(\"valorppronr[]\",\"custo_0\"); somaCustoProjeto()'></td>";
		linha += "<td align='center'><a onClick='removerElemento(\"consultor_"+i+"\"); somaParcela(\"valorppronr[]\",\"custo_0\"); somaCustoProjeto()'>Remover</a></td>";
		linha += "</tr>";
		linha += "</table>";												
	}												
	return linha;   
}

/*#24 FUNCAO PARA ALTERAR O ID EM TEMPO DE EXECUÇÃO PARA SER POSSÍVEL ALTERAR O CONSULTOR*/
function alterarId(nomepessvc,fkidpessnr){
	var campos = new Array(nomepessvc,fkidpessnr);
	var ultimo = document.frmDados.pessoa.value.split(",");
	if(document.frmDados.pessoa.value == ""){
		document.getElementById(nomepessvc).id = "nomepessvc";
		document.getElementById(fkidpessnr).id = "fkidpessnr";
	}else{
		document.getElementById('nomepessvc').id = ultimo[0];
		document.getElementById('fkidpessnr').id = ultimo[1];
		document.getElementById(nomepessvc).id = "nomepessvc";
		document.getElementById(fkidpessnr).id = "fkidpessnr";
	}												
	document.frmDados.pessoa.value = campos;												
}

/*#25 FUNCAO UTILIZADA PARA INSERIR PAGAMENTOS E CONSULTORES*/
function insereElemento(campo,parcelas,tabela){
	var i=0;
	var cont=1;
	indice = campo+"_"+i;
	var linha = '';
	recuperaId();
	if(parcelas == 0){
		while(document.getElementById(indice)){														
			linha += "<span id='"+campo+"_"+i+"'>";
			if(document.getElementById(indice).innerHTML != ''){
					linha += montaTabela(cont,i,tabela);
					cont++;																
			}
			linha += "</span>";																																								
			i++;
			indice = campo+"_"+i;													
		}												
		linha += "<span id='"+campo+"_"+i+"'>";
		linha += montaTabela(cont,i,tabela);													
		linha += "</span>";
	}else{
		for(var i=0 ;i < parseInt(parcelas); i++){																											
			linha += "<span id='"+campo+"_"+i+"'>";
			linha += montaTabela(cont,i,tabela);
			linha += "</span>";
			cont++;
		}
	}
	document.getElementById(campo).innerHTML = linha;												
}

/*#26 FUNCAO UTILIZADA PARA RECUPERAÇÃO DO ULTIMO ELEMENTO ALTERADO NA TROCA DE CONSULTORES*/
function recuperaId(){
	/*******************RECUPERA O ULTIMO ELEMENTO**************/
	//esse if é utilizao para na inserção recupar o valor do último elemento 
	//que foi alterado												
	if(document.frmDados.pessoa.value != ""){
		var ultimo = document.frmDados.pessoa.value.split(",");
		document.getElementById('nomepessvc').id = ultimo[0];
		document.getElementById('fkidpessnr').id = ultimo[1];
		document.frmDados.pessoa.value = "";
	}
	/*******************RECUPERA O ULTIMO ELEMENTO**************/
}

/*#27 FUNCAO UTILIZADO PARA REMOVER ELEMENTOS EX: CONSULTOR E PAGAMENTO*/
function removerElemento(elemento){
	recuperaId();												
	document.getElementById(elemento).innerHTML = '';
}

/*#28 FORMATA UM VALOR NUMERICO EX: 2.500,00 PARA 2500.00*/
function currency(valor){
	var reg = /\./gi;
	valor = valor.replace(reg, '');
	var reg2 = /,/gi;
	valor = valor.replace(reg2, '.');
	valor = parseFloat(valor);
	alert(valor);
	return valor;
}

/*#29 FORMATA UM VALOR NUMERICO EX: 2500.00 PARA 2.500,00*/
function formatCurrency(num){ 												
	var sign, cents; 												
	num = num.toString().replace(/\$|\,/g,''); 												
	if(isNaN(num)) 												
		num = "0"; 												
	sign = (num == (num = Math.abs(num))); 												
	num = Math.floor(num*100+0.50000000001); 												
	cents = num%100; 												
	num = Math.floor(num/100).toString(); 												
	if(cents<10) 												
		cents = "0" + cents; 												
	for (var i = 0; i < Math.floor((num.length-(1+i))/3); i++) 												
		num = num.substring(0,num.length-(4*i+3))+'.'+ 												
	num.substring(num.length-(4*i+3)); 												
	return (((sign)?'':'-') + num + ',' + cents); 												
}

/*#29 FUNCAO UTILIZADA PARA SOMAR AS PARCELAS DO PROJETO*/
function somaParcela(campo,destino){
	soma = 0;
	if(document.forms["frmDados"].elements[campo]){
		if(document.forms["frmDados"].elements[campo].length == undefined)
			document.forms["frmDados"].elements[destino].value = document.forms["frmDados"].elements[campo].value;
		else{
			for (i=0; i<document.forms["frmDados"].elements[campo].length; i++)
			{
				soma += currency(document.forms["frmDados"].elements[campo][i].value);
				
			}
			document.getElementById(destino).value = formatCurrency(soma);
		}
	}else{
		document.forms["frmDados"].elements[destino].value = '';
	}												
}

/*#30 FUNCAO UTILIZADA PARA INCLUIR EQUIPAMENTOS NO PROJETO*/
function incluirEquipamento(campo,equipamento){
	if(equipamento != "" && equipamento != null){
		var i=0;
		var cont=1;
		var indice = campo+i;
		var linha = 0;
		var color = "#F9F9F9";
		while(document.getElementById(indice)){													
			i++;
			indice = campo+i;												
		}																																					
		linha = "<span id='equipamento_"+i+"'>";
		linha += "<table width='100%' cellpadding='0' cellspacing='2' onMouseOver='this.bgColor=\"#efefef\";' onMouseOut='this.bgColor=\"#ffffff\"'>";
		linha += "<tr>";
		linha += "<td width='80%'><input type='hidden' name='nomeequivc[]' value='"+equipamento+"'>"+equipamento+"</td>";
		linha += "<td align='center'><a href='#' onClick='removerElemento(\"equipamento_"+i+"\"); return false;'>Remover</a></td>";
		linha += "</tr>";
		linha += "</table>"
		linha += "</span>";									
		
		document.getElementById('equipamento').innerHTML += linha;
	}												
}

// #31 FUNCAO PARA CONTAR O TAMANHO DOS TEXTAREA
function textTamanho(text,campo){
	var contador = 1000 - parseInt(text.value.length,10);
	if(contador < 0){
		contador = 0;
		text.value = text.value.substring(0,1000);
		return false;
	}										
	document.getElementById(campo).innerHTML = contador;
}

// #32 FUNCAO PARA FORMATAR DATA
function formataHora(campo,teclapres) {
	var tecla = teclapres.keyCode;
	vk = eval(campo);
	vr = vk.value;
	tam = vr.length + 1;
	if ( tecla != 9 && tecla != 8 ){
		if ( tam > 2 && tam < 5 ){
			vk.value = vr.substr( 0, tam - 1  ) + ':' + vr.substr( tam - 1, tam );
		}
	}

}	

//# 33 FUNCAO PARA FORMATAR CPF
function formataCpf(campo,teclapres){
	var tecla = teclapres.keyCode;
	vk = eval(campo);
	vr = vk.value;
	vr = vr.replace( ".", "" );
	vr = vr.replace( ".", "" );
	vr = vr.replace( "-", "" );
	tam = vr.length + 1;
	if ( tecla != 9 && tecla != 8 ){
		if ( tam <= 2 ) 
	 		vk.value = vr ; 
		if ( tam >= 3 && tam <= 5 )
			vk.value = vr.substr( 0, tam - 2 ) + '-' + vr.substr(tam - 2, tam);
		if ( tam >= 6 && tam <= 8)
			vk.value = vr.substr( 0, tam - 5 ) + '.' + vr.substr( tam - 5, 3 ) + '-' + vr.substr( tam - 2, tam ) ; 
		if ( tam >= 9 && tam <= 11 )
			vk.value = vr.substr( 0, tam - 8 ) + '.' + vr.substr( tam - 8, 3 ) + '.' + vr.substr ( tam - 5, 3) + '-' + vr.substr ( tam - 2, tam);
	}
}

//#34 FUNCAO PARA FORMATAR CNPJ
function formataCnpj(campo,teclapres){
	var tecla = teclapres.keyCode;
	vk = eval(campo);
	vr = vk.value;
	vr = vr.replace( ".", "" );
	vr = vr.replace( ".", "" );
	vr = vr.replace( "-", "" );
	vr = vr.replace( "/", "");
	tam = vr.length + 1;
	if ( tecla != 9 && tecla != 8 ){
		if ( tam <= 2 ) 
	 		vk.value = vr ;
		if ( tam >= 3 && tam <= 6)
			vk.value = vr.substr( 0, tam - 2 ) + '-' + vr.substr(tam - 2, tam);
		if ( tam >= 7 && tam <= 9)
			vk.value = vr.substr( 0, tam - 6 ) + '/' + vr.substr( tam - 6, 4 ) + '-' + vr.substr( tam - 2, tam ) ; 
		if ( tam >= 10 && tam <= 12 )
			vk.value = vr.substr( 0, tam - 9 ) + '.' + vr.substr( tam - 9, 3 ) + '/' + vr.substr( tam - 6, 4 ) + '-' + vr.substr( tam - 2, tam ) ;
		if ( tam >= 13 && tam <= 14 )
			vk.value = vr.substr( 0, tam - 12) + '.' + vr.substr( tam - 12, 3 ) + '.' + vr.substr( tam - 9, 3 ) + '/' + vr.substr( tam - 6, 4 ) + '-' + vr.substr( tam - 2, tam ) ;
	}
}

//#35 FUNCAO PARA VERIFICAR SE A HORA INICIAL E MENOR QUE A FINAL
function validaHoraIniFim(hi, hf){
   var hih = parseInt(hi.value.substring(0,2));
   var him = parseInt(hi.value.substring(3,5));
   var hfh = parseInt(hf.value.substring(0,2));
   var hfm = parseInt(hf.value.substring(3,5));
   var di = new Date();
   di.setHours(hih);
   di.setMinutes(him);
   var df = new Date();
   df.setHours(hfh);
   df.setMinutes(hfm);
   if(di.getTime() < df.getTime())
      return true;
   else{
   		alert('Verifique o horário: Horário de início > Horário de término.');
      	return false;
	}
}
// #36 FUNCAO PARA VERIFICAR SE A CARGA HORARIA CONFERE COM OS HORARIOS INFORMADOS
function validaCargaHoraria(h1,h2,h3,h4,ch){
   var h1h = parseInt(h1.value.substring(0,2)*60);
   var h1m = parseInt(h1.value.substring(3,5));
   var h2h = parseInt(h2.value.substring(0,2))*60;
   var h2m = parseInt(h2.value.substring(3,5));
   var h3h
   var h3m
   var h4h
   var h4m
   if(h3.value == ""){
   		h3h = 0;
		h3m = 0;
	}else{
		h3h = parseInt(h3.value.substring(0,2))*60;
		h3m = parseInt(h3.value.substring(3,5));
	}
   if(h4.value == ""){
   		h4h = 0;
		h4m = 0;
	}else{
		h4h = parseInt(h4.value.substring(0,2))*60;
		h4m = parseInt(h4.value.substring(3,5));
	}
   var h1 = (h2h+h2m) - (h1h+h1m);
   var h2 = (h4h+h4m) - (h3h+h3m);
   var h3 = (h1+h2)/60;
   var carga = ch.value;
   switch (carga){
	   case "1":
	   carga = 4;
	   break
	   case "2":
	   carga = 8;
	   break
	   case "5":
	   carga = 6;
	   break
	   default:
	   alert('Carga horária inválida');
	   return false;
	}		
   if(h3==carga)
      return true;
   else{
   		alert("Verifica horário: horário diferente da carga horária");
      	return false;
	}
}

//#37 FUNCAO PARA FORMATAR TELEFONE
function formataTelefone(campo,teclapres){
	var tecla = teclapres.keyCode;
	vk = eval(campo);
	vr = vk.value;
	vr = vr.replace( "-", "" );
	tam = vr.length + 1;
	if ( tecla != 9 && tecla != 8 ){
		if ( tam >= 5 && tam <= 9 )
			vk.value = vr.substr( 0, 4 ) + '-' + vr.substr( 4, 4 ); 
	}
}

//#38 FUNCAO PARA FORMATAR O CEP
function formataCep(campo,teclapres){
	var tecla = teclapres.keyCode;
	vk = eval(campo);
	vr = vk.value;
	vr = vr.replace( "-", "" );
	tam = vr.length + 1;
	if ( tecla != 9 && tecla != 8 ){
		if ( tam >= 6 && tam <= 9 )
			vk.value = vr.substr( 0, 5 ) + '-' + vr.substr( 5, 3 ); 
	}
}
  //#39 FUNCAO PARA ADICIONAR CAMPO \
		function addcampo(){ 	// cria uma referencia para os objetos da tela 	
			x = document.getElementById("fkidempnr"); 	
//			origem = eval(x);
//			alert(origem);
//			return(origem);
//			y = document.getElementById("destino"); 	
//			destino = eval  ;  	
		var indice = document.form1.fkidemprnr.value  	
		alert(indice);
		
		alert("location.href='?id=emp&fkidempnr=" + indice+"'");
		return("'?id=emp&fkidempnr=" + indice+"'")
			/*if (indice == 0){ 	
			}else{ 		// verifica a quantidade de objs ja setados em destino 		
				for( j = 0; destino.options[j] != null; j++ ); // o for nao tem corpo, pois ele so conta 	 	
				// busca as opcoes selecionadas no campo origem 		
				for( i = 0; origem.options[i] != null; i++ ){ 			
					if( origem.options[i].selected ){ 				
						var opc = new Option( origem.options[i].text, origem.options[i].value, false, false ); 				
						destino.options[j] = opc; 				
						j++; 			
					} 		
				} 	
			} */
			
		}  
		//#40 FUNCAO PARA EXCLUIR CAMPO SELECIONADO 
		function deletecampo(){ 	
			var destino = document.getElementById( "destino" ); 	
			for( i = 0; destino.options[i] != null; i++ ){ 		
				if( destino.options[i].selected ){ 			
				destino.options[i] = null; 		
				} 	
			} 
		}  
		
/*ADICIONA BEM*/
function add(){
	// cria uma refeência para os objetos da tela
	var origem  = document.getElementById( "origem" );
	var destino = document.getElementById( "destino" );
	
	// verifica a quantidade de objs já setados em destino
	for( j = 0; destino.options[j] != null; j++ ); // o for nao tem corpo, pois ele so conta
	
	// busca as opções selecionadas no campo origem
	for( i = 0; origem.options[i] != null; i++ ){
		if( origem.options[i].selected ){
			alert( origem.options[i].text );
			var opc = new Option( origem.options[i].text, origem.options[i].value, false, false );
			destino.options[j] = opc;
			j++;
		}
	}
}

function del(){
	var destino = document.getElementById( "destino" );
	for( i = 0; destino.options[i] != null; i++ ){
		if( destino.options[i].selected ){
			destino.options[i] = null;
		}
	}
}


/*FUNCOES DO MENU*/

var menuwidth='165px' //default menu width
var menubgcolor='#FFFFFF'  //menu bgcolor
var disappeardelay=250  //menu disappear speed onMouseout (in miliseconds)
var hidemenu_onclick="yes" //hide menu when user clicks within menu?

/////No further editting needed

var ie4=document.all
var ns6=document.getElementById&&!document.all

if (ie4||ns6)
document.write('<div id="dropmenudiv" style="visibility:hidden;width:'+menuwidth+';background-color:'+menubgcolor+'" onMouseover="clearhidemenu()" onMouseout="dynamichide(event)"></div>')

function getposOffset(what, offsettype){
var totaloffset=(offsettype=="left")? what.offsetLeft : what.offsetTop;
var parentEl=what.offsetParent;
while (parentEl!=null){
totaloffset=(offsettype=="left")? totaloffset+parentEl.offsetLeft : totaloffset+parentEl.offsetTop;
parentEl=parentEl.offsetParent;
}
return totaloffset;
}


function showhide(obj, e, visible, hidden, menuwidth){
if (ie4||ns6)
dropmenuobj.style.left=dropmenuobj.style.top=-500
if (menuwidth!=""){
dropmenuobj.widthobj=dropmenuobj.style
dropmenuobj.widthobj.width=menuwidth
}
if (e.type=="click" && obj.visibility==hidden || e.type=="mouseover")
obj.visibility=visible
else if (e.type=="click")
obj.visibility=hidden
}

function iecompattest(){
return (document.compatMode && document.compatMode!="BackCompat")? document.documentElement : document.body
}

function clearbrowseredge(obj, whichedge){
var edgeoffset=0
if (whichedge=="rightedge"){
var windowedge=ie4 && !window.opera? iecompattest().scrollLeft+iecompattest().clientWidth-15 : window.pageXOffset+window.innerWidth-15
dropmenuobj.contentmeasure=dropmenuobj.offsetWidth
if (windowedge-dropmenuobj.x < dropmenuobj.contentmeasure)
edgeoffset=dropmenuobj.contentmeasure-obj.offsetWidth
}
else{
var windowedge=ie4 && !window.opera? iecompattest().scrollTop+iecompattest().clientHeight-15 : window.pageYOffset+window.innerHeight-18
dropmenuobj.contentmeasure=dropmenuobj.offsetHeight
if (windowedge-dropmenuobj.y < dropmenuobj.contentmeasure)
edgeoffset=dropmenuobj.contentmeasure+obj.offsetHeight
}
return edgeoffset
}

function populatemenu(what){
if (ie4||ns6)
dropmenuobj.innerHTML=what.join("")
}


function dropdownmenu(obj, e, menucontents, menuwidth){
if (window.event) event.cancelBubble=true
else if (e.stopPropagation) e.stopPropagation()
clearhidemenu()
dropmenuobj=document.getElementById? document.getElementById("dropmenudiv") : dropmenudiv
populatemenu(menucontents)

if (ie4||ns6){
showhide(dropmenuobj.style, e, "visible", "hidden", menuwidth)
dropmenuobj.x=getposOffset(obj, "left")
dropmenuobj.y=getposOffset(obj, "top")
dropmenuobj.style.left=dropmenuobj.x-clearbrowseredge(obj, "rightedge")+"px"
dropmenuobj.style.top=dropmenuobj.y-clearbrowseredge(obj, "bottomedge")+obj.offsetHeight+"px"
}

return clickreturnvalue()
}

function clickreturnvalue(){
if (ie4||ns6) return false
else return true
}

function contains_ns6(a, b) {
while (b.parentNode)
if ((b = b.parentNode) == a)
return true;
return false;
}

function dynamichide(e){
if (ie4&&!dropmenuobj.contains(e.toElement))
delayhidemenu()
else if (ns6&&e.currentTarget!= e.relatedTarget&& !contains_ns6(e.currentTarget, e.relatedTarget))
delayhidemenu()
}

function hidemenu(e){
if (typeof dropmenuobj!="undefined"){
if (ie4||ns6)
dropmenuobj.style.visibility="hidden"
}
}

function delayhidemenu(){
if (ie4||ns6)
delayhide=setTimeout("hidemenu()",disappeardelay)
}

function clearhidemenu(){
if (typeof delayhide!="undefined")
clearTimeout(delayhide)
}

if (hidemenu_onclick=="yes")
document.onclick=hidemenu

		