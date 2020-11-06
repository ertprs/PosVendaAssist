// ************************************
// Criada em 13/04/2004 - Ricardo
// Verificação de tamanho mínimo do campo
// Usar: onClick="javascript:fnc_tamanho_minimo(document.form.campo, 3);"
// ************************************
function fnc_tamanho_minimo(campo, caracteres)
{
	if (campo.value.length < caracteres)
	{
		alert('Para uma busca mais apurada digite, no mínimo, ' + caracteres + ' letras ou números.');
	}
}

// ************************************
// Criada em 13/04/2004 - Ricardo
// Preload em imagens do site
// Usar: onload="fnc_preload()"
// ************************************
function fnc_preload() 
{
	// lupa
	pI('../imagens_admin/btn_lupa.gif');
	pI('../imagens_admin/btn_pesquisar_400.gif');
}

// ************************************
// Criada em 13/04/2004 - Ricardo
// Utilizada pela funcao "fnc_preload()"
// ************************************
function pI()
{
	var d=document;
	if(d.images)
	{
		if(!d.MM_p)
			d.MM_p=new Array();
		var i,j=d.MM_p.length,a=pI.arguments; 
		for(i=0; i<a.length; i++)
			if (a[i].indexOf("#")!=0)
			{
				d.MM_p[j]=new Image;
				d.MM_p[j++].src=a[i];
			}
	}
}

// HD 405970: Permitir mover o cursor livremente no IE
// Lê a posição do cursor
function KEY_getCaretPosition (ctrl) {
	var CaretPos = 0;	// IE Support
	if (document.selection) {
	ctrl.focus ();
		var Sel = document.selection.createRange ();
		Sel.moveStart ('character', -ctrl.value.length);
		CaretPos = Sel.text.length;
	}
	// Firefox support
	else if (ctrl.selectionStart || ctrl.selectionStart == '0')
		CaretPos = ctrl.selectionStart;
	return (CaretPos);
}
// Seta a posição do cursor
function KEY_setCaretPosition(ctrl, pos){
	if(ctrl.setSelectionRange)
	{
		ctrl.focus();
		ctrl.setSelectionRange(pos,pos);
	}
	else if (ctrl.createTextRange) {
		var range = ctrl.createTextRange();
		range.collapse(true);
		range.moveEnd('character', pos);
		range.moveStart('character', pos);
		range.select();
	}
}

//HD 216395: Funcao para retirar caracteres especiais, acentos e converter em maiusculo
function somenteMaiusculaSemAcento(obj) {
	var com_acento = 'áàãâäéèêëíìîïóòõôöúùûüçÁÀÃÂÄÉÈÊËÍÌÎÏÓÒÕÖÔÚÙÛÜÇ';
	var sem_acento = 'aaaaaeeeeiiiiooooouuuucAAAAAEEEEIIIIOOOOOUUUUC';
	var event = (window.event) ? window.event : obj.onkeyup.arguments[0];

	var resultado = new String;
	var oldLength, newLength;

	var kc = event.keyCode;
	var sk = event.shiftKey;
	var ak = (event.altKey || event.altGraphKey || event.metaKey);
	var ck = (event.ctrlKey && !event.altGraphKey);

	//Ctrl+[ACXZ] - CTRL+V não, porque o que for colado tem que ir pra maiúsculo
	if ((ck && kc == 67) ||
		(ck && kc == 88) ||
		(ck && kc == 65)) return true;

	//<Home> <End> Setas, códigos não imprimíveis, <INS> <Del> > 'Z'
	if (kc < 32 || (kc >= 34 && kc <= 40) || kc == 46 || (kc > 90 && kc <106)) return true;

	var curPos = KEY_getCaretPosition(obj);

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
	oldLength = resultado.length; // Se for excluir caracteres, recalcular a posição do cursor.
	obj.value = resultado.replace(re, "");
	newLength = obj.value.length // Se for excluir caracteres, recalcular a posição do cursor.;

	KEY_setCaretPosition(obj, curPos - (oldLength - newLength));
}

function mostrarMensagemBuscaNomes() {
	alert("Para busca de Ordens de Serviço no sistema da Telecontrol, seguir as regras:\n\n Informar o nome sempre a partir do início em maiúsculas e sem acentos. Exemplo: JOSE DA SILVA SANTOS; correto: JOSE DA SILVA; errado: SILVA SANTOS");
}
