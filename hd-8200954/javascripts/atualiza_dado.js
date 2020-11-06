// JavaScript Document
// Funções para inserir ou atualizar data de nascimento dos usuários do Login Único
// Altera o número de dias de forma dinâmica
function apagaOpcao() { // Apaga o último ítem do SELECT dia_nascimento
  var elSel = document.frm_data.dia_nascimento;
  if (elSel.length > 0)
  {
    elSel.remove(elSel.length - 1);
  }
}

function adicionaOpcao(valor) { // Adiciona um ítem valor='valor' e texto 'valor' ao final
  var elOptNew = document.createElement('option');
  elOptNew.text = valor;
  elOptNew.value = valor;
  var elSel = document.frm_data.dia_nascimento;

  try {
    elSel.add(elOptNew, null); // procedimento estándar, no-IE
  }
  catch(ex) {
    elSel.add(elOptNew); // só para o MSIE
  }
}

function dias_mes() {//  Adiciona ou apaga ítens da lista de dias segundo o nº do mês
	var selDias = document.frm_data.dia_nascimento;
	var mes     = document.frm_data.mes_nascimento.value;
	var max = 31;// Máximo 31, mas...
		if (mes == 2) {max = 29;}   //se for fevereiro, só 29
		if (mes==4 || mes==6 || mes==9 || mes==11) {max = 30;}

	if (selDias.length > max) { // Apagar ítens se sobrarem
		while (selDias.length > max) {
			apagaOpcao();
		}
	}else{
		while (selDias.length < max) {// Adicionar se estão faltando
			adicionaOpcao(selDias.length+1);
		}
	}
}

function Enviar(nome_botao,formulario) { // Enviar o formulário
	var botao = formulario.btn_acao;
	if (nome_botao=="") {nome_botao='gravar2';}
	if (botao.value == '') {
		botao.value=nome_botao;
		formulario.submit();
	} else {
		alert ('Aguarde submissão');
	}
}
