<script language='javascript'>

function fnc_pesquisa_posto(campo, campo2, tipo) {
	if (tipo == "codigo" ) {
		var xcampo = campo;
	}

	if (tipo == "nome" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.codigo  = campo;
		janela.nome    = campo2;
		janela.focus();
	}
}


function fnc_pesquisa_produto (campo, campo2, tipo, voltagem, referencia_pai, descricao_pai, referencia_avo, descricao_avo) {
	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}


	if (xcampo.value != "") {
		var url = "";
		url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.referencia   = campo;
		janela.descricao    = campo2;
		
		if (voltagem != "") {
			janela.voltagem = voltagem;
		}
		if (referencia_pai != "") {
			janela.referencia_pai = referencia_pai;
		}
		if (descricao_pai != "") {
			janela.descricao_pai = descricao_pai;
		}
		if (referencia_avo != "") {
			janela.referencia_avo = referencia_avo;
		}
		if (descricao_avo != "") {
			janela.descricao_avo = descricao_avo;
		}
		janela.focus();
	}
}

function fnc_pesquisa_produto_fabrica (empresa, campo, campo2, tipo, voltagem) {
	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}
	if (xcampo.value != "") {
		var url = "";
		url = "produto_pesquisa_empresa.php?empresa="+empresa.value+"&campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.referencia   = campo;
		janela.descricao    = campo2;

		janela.focus();
	}
}


function fnc_pesquisa_peca (campo, campo2, tipo) {

	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}


	if (xcampo.value != "") {
		var url = "";
		url = "peca_pesquisa.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=18, left=0");
		janela.referencia	= campo;
		janela.descricao	= campo2;
		janela.focus();
	}
}

/*
function fnc_pesquisa_peca_lista (produto_referencia, peca_referencia, peca_descricao, peca_preco, tipo) {
	var url = "";
	if (tipo == "tudo") {
		url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&descricao=" + peca_referencia.value + "&tipo=" + tipo ;
	}

	if (tipo == "referencia") {
		url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&peca=" + peca_referencia.value + "&tipo=" + tipo ;
	}

	if (tipo == "descricao") {
		url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&descricao=" + peca_descricao.value + "&tipo=" + tipo ;
	}
<? if ($login_fabrica <> 2) { ?>
	if (peca_referencia.value.length >= 4 || peca_descricao.value.length >= 4) {
<? } ?>
		janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=501, height=400, top=18, left=0");
		janela.produto		= produto_referencia;
		janela.referencia	= peca_referencia;
		janela.descricao	= peca_descricao;
		janela.preco		= peca_preco;
		janela.focus();
<? if ($login_fabrica <> 2) { ?>
	}else{
		alert("Digite pelo menos 4 caracteres!");
	}
<? } ?>
}

*/
/* ####################################################################### */

function fnc_pesquisa_peca_lista (produto_referencia, peca_referencia, peca_descricao, peca_preco, voltagem, tipo, peca_qtde) {
	var url = "";
	if (tipo == "tudo") {
		url = "peca_pesquisa_lista_assist.php?produto=" + produto_referencia + "&descricao=" + peca_referencia.value + "&voltagem="+voltagem+"&tipo=" + tipo + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>";
	}

	if (tipo == "referencia") {
		url = "peca_pesquisa_lista_assist.php?produto=" + produto_referencia + "&peca=" + peca_referencia.value + "&voltagem="+voltagem+"&tipo=" + tipo + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>";
	}

	if (tipo == "descricao") {
		url = "peca_pesquisa_lista_assist.php?produto=" + produto_referencia + "&descricao=" + peca_descricao.value + "&voltagem="+voltagem+"&tipo=" + tipo + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>";
	}
	if (peca_referencia.value.length >= 3 || peca_descricao.value.length >= 3) {
		janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=501, height=400, top=18, left=0");
		janela.produto		= produto_referencia;
		janela.referencia	= peca_referencia;
		janela.descricao	= peca_descricao;
		janela.preco    	= peca_preco;
		janela.qtde			= peca_qtde;
		janela.focus();
	}else{
		alert("Digite pelo menos 3 caracteres!");
	 }
}
/* ####################################################################### */
/*     PARA EMPRESAS       - FAZ A PESQUISA PARA A EMPRESA SELECIONADA     */
/* ####################################################################### */
function fnc_pesquisa_peca_empresa (empresa, peca_referencia, peca_descricao, peca_preco, peca_estoque,peca_qtde_entrega,peca_data_previsao, tipo) {
	var url = "";
	var tabela_preco='&tabela=';
	var tabela=document.getElementById('tabela');
	if(tabela) {
		tabela_preco = tabela_preco+''+tabela.value;
	}

	if (tipo == "tudo") {
		url = "peca_pesquisa_empresa.php?empresa="+empresa.value+ "&descricao=" + peca_referencia.value + "&tipo=" + tipo + '' + tabela_preco;
	}

	if (tipo == "referencia") {
		url = "peca_pesquisa_empresa.php?empresa="+empresa.value+"&peca=" + peca_referencia.value + "&tipo=" + tipo + '' + tabela_preco;
	}

	if (tipo == "descricao") {
		url = "peca_pesquisa_empresa.php?empresa="+empresa.value+"&descricao=" + peca_descricao.value + "&tipo=" + tipo + '' + tabela_preco;
	}
	if (peca_referencia.value.length >= 3 || peca_descricao.value.length >= 3) {
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no,menubar=no, width=600, height=450, top=18, left=0");
		janela.referencia	= peca_referencia;
		janela.descricao	= peca_descricao;
		janela.preco    	= peca_preco;
		janela.estoque		= peca_estoque;
		janela.quantidade_entregar = peca_qtde_entrega;
		janela.data_entrega = peca_data_previsao;

		janela.focus();
	}

}
/* ####################################################################### */


function fnc_pesquisa_transportadora (xcampo, tipo)
{
	if (xcampo.value != "") {
		var url = "";
		url = "pesquisa_transportadora.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=0, left=0");
		janela.transportadora = document.frm_pedido.transportadora;
		janela.codigo         = document.frm_pedido.transportadora_codigo;
		janela.nome           = document.frm_pedido.transportadora_nome;
		janela.cnpj           = document.frm_pedido.transportadora_cnpj;
		janela.focus();
	}
}
function formata_data(valor_campo, form, campo){
	var mydata = '';
	mydata = mydata + valor_campo;
	myrecord = campo;
	myform = form;

	if (mydata.length == 2){
		mydata = mydata + '/';
		window.document.forms["" + myform + ""].elements[myrecord].value = mydata;
	}
	if (mydata.length == 5){
		mydata = mydata + '/';
		window.document.forms["" + myform + ""].elements[myrecord].value = mydata;
	}

}
//onKeyUp="formata_data(this.value,'frm_os', 'data_abertura')"
</script>
