// JavaScript Document
// Biblioteca Javascript

function imprime(url){
	window.open(url,'Imprimir','width=450,height=400,scrollbars=yes');
}

function enviaAmigo(from){
	window.open('php/enviar_receita.php?f='+from,'Enviar','width=500,height=400,scrollbars=yes');
}

function enviaFaleConosco(formulario){
    var form = formulario;

    if(form.assunto.value == ""){
        alert("Preencha o campo Assunto.\n");
        form.assunto.focus();
        return false;
    }
    if(form.nome.value == ""){
        alert("Preencha o campo Nome.\n");
        form.nome.focus();
        return false;
    }
    /*if(form.sexo.value == ""){
        alert("Preencha o campo Sexo.\n");
        form.sexo.focus();
        return false;
    }*/
    if(form.email.value == ""){
        alert("Preencha o campo E-mail.\n");
        form.email.focus();
        return false;
    }
    if(form.endereco.value == ""){
        alert("Preencha o campo EndereÁo.\n");
        form.endereco.focus();
        return false;
    }
    if(form.cidade.value == ""){
        alert("Preencha o campo Cidade.\n");
        form.cidade.focus();
        return false;
    }
    /*if(form.estado.value == ""){
        alert("Preencha o campo Estado.\n");
        form.estado.focus();
        return false;
    }
    if(form.cep.value == ""){
        alert("Preencha o campo CEP.\n");
        form.cep.focus();
        return false;
    }*/
    if(form.mensagem.value == ""){
        alert("Preencha o campo Mensagem.\n");
        form.mensagem.focus();
        return false;
    }
    if(form.telefone.value == ""){
        alert("Preencha o campo Mensagem.\n");
        form.telefone.focus();
        return false;
    }

    form.submit();
}

var _interval = null;

function somenteMaiusculaSemAcento(obj) {


    clearInterval(_interval);

    // Adicionado o intervalo pois estava dando problema na vers„o mobile
    _interval = setInterval(function(){

        com_acento = '·‡„‚‰ÈËÍÎÌÏÓÔÛÚıÙˆ˙˘˚¸Á¡¿√¬ƒ…» ÀÕÃŒœ”“’÷‘⁄Ÿ€‹«';
        sem_acento = 'aaaaaeeeeiiiiooooouuuucAAAAAEEEEIIIIOOOOOUUUUC';

        resultado='';

        for(i=0; i<obj.value.length; i++) {
            if (com_acento.indexOf(obj.value.substr(i,1))>=0) {
                resultado += sem_acento.substr(com_acento.indexOf(obj.value.substr(i,1)),1);
            }
            else {
                resultado += obj.value.substr(i,1);
            }
        }

        resultado = resultado.toUpperCase();

        re = /[^\w|\s]/g;
        obj.value = resultado.replace(re, "");

    },1200)
	
}