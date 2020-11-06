// JavaScript Document
// Fun��es para inserir ou atualizar data de nascimento dos usu�rios do Login �nico
// Altera o n�mero de dias de forma din�mica
function apagaOpcao() { // Apaga o �ltimo �tem do SELECT dia_nascimento
  var elSel = document.frm_data.dia_nascimento;
  if (elSel.length > 0)
  {
    elSel.remove(elSel.length - 1);
  }
}

function adicionaOpcao(valor) { // Adiciona um �tem valor='valor' e texto 'valor' ao final
  var elOptNew = document.createElement('option');
  elOptNew.text = valor;
  elOptNew.value = valor;
  var elSel = document.frm_data.dia_nascimento;

  try {
    elSel.add(elOptNew, null); // procedimento est�ndar, no-IE
  }
  catch(ex) {
    elSel.add(elOptNew); // s� para o MSIE
  }
}

function dias_mes() {//  Adiciona ou apaga �tens da lista de dias segundo o n� do m�s
	var selDias = document.frm_data.dia_nascimento;
	var mes     = document.frm_data.mes_nascimento.value;
	var max = 31;// M�ximo 31, mas...
		if (mes == 2) {max = 29;}   //se for fevereiro, s� 29
		if (mes==4 || mes==6 || mes==9 || mes==11) {max = 30;}

	if (selDias.length > max) { // Apagar �tens se sobrarem
		while (selDias.length > max) {
			apagaOpcao();
		}
	}else{
		while (selDias.length < max) {// Adicionar se est�o faltando
			adicionaOpcao(selDias.length+1);
		}
	}
}

function Enviar(nome_botao,formulario) { // Enviar o formul�rio
	var botao = formulario.btn_acao;
	if (nome_botao=="") {nome_botao='gravar2';}
	if (botao.value == '') {
		botao.value=nome_botao;
		formulario.submit();
	} else {
		alert ('Aguarde submiss�o');
	}
}
