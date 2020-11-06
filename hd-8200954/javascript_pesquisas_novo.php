<script type='text/javascript' language='javascript'>
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
	} else {
		alert("Favor, digitar pelo menos 3 caracteres para a busca");
	}
}

function fnc_pesquisa_produto (descricao, referencia, posicao, linha) {
	var descricao  = jQuery.trim(descricao.value);
	var referencia = jQuery.trim(referencia.value);

<? if(in_array($login_fabrica,[120,201])){ ?>
	var linha = jQuery.trim(linha.value); //hd_chamado=2765193
<? } ?>

	if (descricao.length > 2 || referencia.length > 2){
		Shadowbox.open({
			content:	"produto_pesquisa_2_nv.php?descricao=" + descricao + "&referencia=" + referencia + "&posicao=" + posicao + "&linha_produto=" + linha + "&exibe=<? echo $_SERVER['PHP_SELF']; ?>",
			player:	"iframe",
			title:		"Pesquisa Produto",
			width:	800,
			height:	500
		});
	}else{
		alert("<?=traduz("Preencha toda ou parte da informação para realizar a pesquisa!")?>");
	}
}

function retorna_dados_produto(produto,linha,descricao,nome_comercial,voltagem,referencia,referencia_fabrica,garantia,mobra,ativo,off_line,capacidade,valor_troca,troca_garantia,troca_faturada,referencia_antiga,troca_obrigatoria,posicao){
	if (posicao != "undefined") {
		gravaDados("produto_referencia_"+posicao,referencia);
		gravaDados("produto_descricao_"+posicao,descricao);
	} else {
		gravaDados("produto_referencia", referencia);
		gravaDados("produto_descricao", descricao);
	}
}

function fnc_pesquisa_produto_serie (serie,posicao) {
	var serie = jQuery.trim(serie.value);

	if (serie.length > 2) {
		Shadowbox.open({
			content:	"produto_serie_pesquisa.php?serie=" + serie + "&posicao=" + posicao,
			player:	"iframe",
			title:		"Pesquisa Produto Série",
			width:	800,
			height:	500
		});
	}else{
		alert("Preencha toda ou parte da informação para realizar a pesquisa!");
	}
}

function fnc_pesquisa_revenda (campo, tipo) {
	if (tipo == "nome") {
		var url = "pesquisa_revenda_nv.php?nome=" + campo.value;
	}
	if (tipo == "cnpj") {
		var url = "pesquisa_revenda_nv.php?cnpj=" + campo.value;
	}
	var campo = jQuery.trim(campo.value);
	if (campo.length > 2) {
		Shadowbox.open({
			content:	url,
			player:	"iframe",
			title:		"Pesquisa Revenda",
			width:	800,
			height:	500
		});
	}else{
		alert("Preencha toda ou parte da informação para realizar a pesquisa!");
	}
}

function fnc_pesquisa_peca_lista_sub (subconjunto, posicao, produto_ref) {

	if (subconjunto.length > 2) {
		Shadowbox.open({
			content:	"peca_pesquisa_lista_subconjunto_nv.php?subconjunto=" + subconjunto + "&posicao=" + posicao + "&produto_ref=" + produto_ref,
			player:	"iframe",
			title:		"Pesquisa Peça de Subconjunto",
			width:	800,
			height:	500
		});
	}else{
		alert("Preencha toda ou parte da informação para realizar a pesquisa!");
	}
}

function fnc_pesquisa_peca_lista_intel (produto_referencia, peca_referencia, peca_descricao, peca_posicao, tipo, input_posicao) {

	peca_referencia = $.trim(peca_referencia);
	peca_descricao  = $.trim(peca_descricao);
	peca_posicao    = $.trim(peca_posicao);

	if (tipo ==	"referencia") {
		url	= "peca_pesquisa_lista_nv.php?produto=" + produto_referencia +	"&peca=" + peca_referencia + "&tipo="	+ tipo + "&input_posicao=" + input_posicao;
	}
	if (tipo ==	"descricao") {
		url	= "peca_pesquisa_lista_nv.php?produto=" + produto_referencia +	"&descricao=" +	peca_descricao + "&tipo="	+ tipo + "&input_posicao=" + input_posicao;
	}
	if (tipo ==	"posicao") {
		url	= "peca_pesquisa_lista_nv.php?produto=" + produto_referencia +	"&posicao="	+ peca_posicao + "&tipo="	+ tipo + "&input_posicao=" + input_posicao;
	}
	if ($.trim(peca_referencia).length > 2 || $.trim(peca_descricao).length > 2 || $.trim(peca_posicao).length > 0) {
		Shadowbox.open({
			content:	url,
			player:	"iframe",
			title:		"Pesquisa Peça",
			width:	800,
			height:	500
		});
	}else{
		alert("Preencha toda ou parte da informação para realizar a pesquisa!");
	}
}

<?php
	if ($login_fabrica == 1){ $url = "peca_pesquisa_blackedecker";
	}elseif($login_fabrica==3 or $login_fabrica==35 or $login_fabrica == 45 or $login_fabrica==5 or $login_fabrica == 6 or $login_fabrica == 30){
		if($login_fabrica == 30 and $gambiara_esmaltec == 'waldir/samuel'){
			$url = "peca_pesquisa_lista_nv";
		}else{
			$url = "peca_pesquisa_lista_new";
		}
	}else{
		$url = "peca_pesquisa_lista_nv";
	}
?>


function pesquisaPeca(campo1,campo2,tipo,posicao,peca_pedido,tipo_pedido,linha){
    var campo1   = jQuery.trim(campo1.value);
    var campo2   = jQuery.trim(campo2.value);
    tipo_pedido  = tipo_pedido.value;
<? if(in_array($login_fabrica,[120,201])){ ?>
	var linha    = jQuery.trim(linha.value); //hd_chamado=2765193
<? } ?>
   	if (peca_pedido == undefined) {
		peca_pedido = "f";
    }else{
        if(peca_pedido == 't'){

        }else if(peca_pedido == 'f'){

        }else{
            peca_pedido  = peca_pedido.value;
        }
    }

    var params = '';

    <?php if ( in_array($login_fabrica, array(11,172)) ): ?>
    var insumos = $("#insumos").val();

    if (insumos !== undefined) {
        params = "&insumos=" + insumos;
    }
    <?php endif ?>

    if (tipo == 'lista_basica'){
		if (campo1.length >= 1){
	        Shadowbox.open({
        	   content :   "peca_pesquisa_lista_nv.php?peca_pedido="+peca_pedido+"&produto="+campo1+"&"+tipo+"="+campo2+"&tipo="+tipo+"&input_posicao="+posicao+"&faturado=sim&tipo_pedido="+tipo_pedido+"&linha_produto="+linha+params,
	           player  :   "iframe",
        	   title   :   "<?php fecho('pesquisa.de.peca', $con, $cook_idioma);?>",
	           width   :   800,
        	   height  :   500
	           });
		}else{
            alert("<?php fecho('informar.toda.parte.informacao.para.realizar.pesquisa', $con, $cook_idioma);?>");
	    }
   }else{
	    if (campo2.length >= 2){
        	Shadowbox.open({
	            content :   "peca_pesquisa_lista_nv.php?peca_pedido="+peca_pedido+"&produto="+campo1+"&"+tipo+"="+campo2+"&tipo="+tipo+"&input_posicao="+posicao+"&faturado=sim&tipo_pedido="+tipo_pedido+"&linha_produto="+linha+params,
        	    player  :   "iframe",
	            title   :   "<?php fecho('pesquisa.de.peca', $con, $cook_idioma);?>",
        	    width   :   800,
	            height  :   500
	        });
	    }else
        	alert("<?php fecho('informar.toda.parte.informacao.para.realizar.pesquisa', $con, $cook_idioma);?>");
    }
}

function pesquisaPecaDefeito(campo1,campo2,posicao,peca_pedido,tipo){
    var campo1   = jQuery.trim(campo1.value);
    var campo2   = jQuery.trim(campo2.value);


    var codigo_defeitos = $("#defeito_constatado_hidden").val();
    if(codigo_defeitos == ""){
    	alert("<?php fecho('informar.defeitos.constatados', $con, $cook_idioma);?>");
    }else{
	    if (peca_pedido == undefined) {
			peca_pedido = "f";
		}

	    // if (tipo == 'lista_basica'){
			if (campo1.length > 2 || campo2.length > 2 || tipo == 'lista'){
			        Shadowbox.open({
		        	 content :   "peca_defeito_pesquisa_lista.php?referencia="+campo1+"&descricao="+campo2+"&input_posicao="+posicao+"&produto="+peca_pedido+"&codigo_defeitos="+codigo_defeitos+"&tipo="+tipo,
			           player  :   "iframe",
		        	   title   :   "<?php fecho('pesquisa.de.peca', $con, $cook_idioma);?>",
			           width   :   800,
		        	   height  :   500
			           });
			}else
		                alert("<?php fecho('informar.toda.parte.informacao.para.realizar.pesquisa', $con, $cook_idioma);?>");
    }






}



function retorna_lista_peca(referencia_antiga,posicao,codigo_linha,peca_referencia,peca_descricao,preco,peca,type,input_posicao,qtde_demanda,prevEntrega,disponibilidade){
	<?php
	if($login_fabrica == 45){
		?>
			$.ajax({
				url: "peca_pesquisa_lista_nv.php",
				type: "GET",
				data: "peca_nks="+peca,
				success: function(data){
					if(data == 0){
						$('#peca_referencia_'+input_posicao).val("");

						$('#recado_peca_nks').show();

						setTimeout(function(){
							$('#recado_peca_nks').slideUp();
						}, 5000);

						// alert('A peça '+peca_descricao+' indisponível, por favor entre contato com Fabricante!');
					}else{
						$('#peca_referencia_'+input_posicao).blur();
					    gravaDados("peca_referencia_"+input_posicao,peca_referencia);
					    gravaDados("peca_descricao_"+input_posicao,peca_descricao);
					    gravaDados("preco_"+input_posicao,preco);
					}
				}
			});

		<?php
	}else{
		?>
		    $('#peca_referencia_'+input_posicao).blur();
		    gravaDados("peca_referencia_"+input_posicao,peca_referencia);
		    gravaDados("peca_descricao_"+input_posicao,peca_descricao);
		    gravaDados("preco_"+input_posicao,preco);
		<?php
		if (in_array($login_fabrica, [11,104,172])) {
?>
            gravaDados("qtde_demanda_"+input_posicao,qtde_demanda);
<?php
		}

		if ($login_fabrica == 123) {
?>
			gravaDados("prevEntrega_"+input_posicao,prevEntrega);
			gravaDados("disponibilidade_"+input_posicao,disponibilidade);
			verificaDispPeca(input_posicao);
<?php
		}
	}
	?>

}

function gravaDados(name, valor){
     try {
        $("input[name="+name+"]").val(valor);
    } catch(err){
        return false;
    }
}

function fnc_pesquisa_lista_basica (produto_referencia,	tipo, input_posicao)	{
	if (produto_referencia != "")
	{
		Shadowbox.open({
			content: "peca_pesquisa_lista_nv.php?produto=" + produto_referencia + "&tipo=" + tipo + "&input posicao=" + input_posicao,
			player:	"iframe",
			title:		"Pesquisa Peça",
			width:	800,
			height:	500
		});
	}
	else
	{
		alert("Selecione um produto para pesquisar pela lista básica.");
	}
}

function pesquisaSerie(campo){
	var campo = campo.value;

	var revenda_fixo_url = "";

	if (jQuery.trim(campo).length > 5){
		Shadowbox.open({
			content:	"pesquisa_numero_serie_nv.php?produto_serie="+campo,
			player:	"iframe",
			title:		"Pesquisa de Número de Série",
			width:	800,
			height:	500
		});
	}else
		alert("Informar mais que 5 digitos para realizar esta pesquisa!");

}

function fnc_pesquisa_consumidor (campo, tipo) {
    var url = "";
    if (tipo == "nome") {
        url = "pesquisa_consumidor_nv.php?nome=" + campo.value + "&tipo=nome";
    }
    if (tipo == "cpf") {
        url = "pesquisa_consumidor_nv.php?cpf=" + campo.value + "&tipo=cpf";
    }
    if (tipo == "fone") {
        url = "pesquisa_consumidor_nv.php?fone=" + campo.value + "&tipo=fone";
    }
	if (campo.value.length > 2) {
		Shadowbox.open({
			content:	url,
			player:	"iframe",
			title:		"Pesquisa Consumidor",
			width:	800,
			height:	500
		});
	}else{
		alert("Preencha toda ou parte da informação para realizar a pesquisa!");
	}
}

function fnc_pesquisa_numero_serie (campo, tipo) {

    var url = "";
    var revenda_fixo_url = "";
    if (document.getElementById('revenda_fixo')){
        revenda_fixo_url = "&revenda_fixo=1"
    }
    if (tipo == "produto_serie") {
        url = "pesquisa_numero_serie_nv<?=$ns_suffix?>.php?produto_serie=" + campo.value + "&tipo=produto_serie"+revenda_fixo_url;
    }

	if (campo.value.length > 2) {
		Shadowbox.open({
			content:	url,
			player:	"iframe",
			title:		"Pesquisa Número Série",
			width:	800,
			height:	500
		});
	}else{
		alert("Preencha toda ou parte da informação para realizar a pesquisa!");
	}
}

 function fnc_pesquisa_produto_modelo (campo,form) {
	 if (campo.value.length > 2) {
		Shadowbox.open({
			content:	"produto_pesquisa_modelo_nv.php?campo=" + campo.value + "&form=" + form,
			player:	"iframe",
			title:		"Pesquisa Produto Modelo",
			width:	800,
			height:	500
		});
	}else{
		alert("Preencha toda ou parte da informação para realizar a pesquisa!");
	}
}

function fnc_pesquisa_serie(campo) {

	var valida = /^\d{10}[A-Z]\d{3}[A-Z]$/;

	if (campo.value.match(valida)) {
		Shadowbox.open({
			content:	"produto_serie_pesquisa_britania_nv.php?serie=" + campo.value,
			player:	"iframe",
			title:		"Pesquisa Número Série",
			width:	800,
			height:	500
		});
	}
	else {
		alert("A pesquisa válida somente para o serial com 15 caracteres no formato NNNNNNNNNNLNNNL");
	}
}

function fnc_pesquisa_serie_atlas (serie, referencia, descricao) {
	if (serie.value.length > 2) {
		Shadowbox.open({
			content:	"produto_pesquisa_new_atlas_nv.php?serie=" + serie.value + "&form=frm_os",
			player:	"iframe",
			title:		"Pesquisa Número Série",
			width:	800,
			height:	500
		});
	}else{
		alert("Preencha toda ou parte da informação para realizar a pesquisa!");
	}
}

var referencia_pesquisa_peca;
var descricao_pesquisa_peca;

function fnc_pesquisa_peca(campo, campo2, tipo) {

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
		//janela.referencia	= campo;
		referencia_pesquisa_peca = campo;
		//janela.descricao	= campo2;
		descricao_pesquisa_peca = campo2;
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

<?
	if ($login_fabrica == 1){ $url = "peca_pesquisa_blackedecker";
	}elseif($login_fabrica==3 or $login_fabrica==35 or $login_fabrica == 45 or $login_fabrica==5 or $login_fabrica == 6 or $login_fabrica == 30){
		if($login_fabrica == 30 and $gambiara_esmaltec == 'waldir/samuel'){
			/* Gambiara para chamar o programa que não funciona o de-para HD 173822 */
			$url = "peca_pesquisa_lista";
		}else{
			$url = "peca_pesquisa_lista_new";
		}
	}else{
		$url = "peca_pesquisa_lista";
	}
?>

var produto;
var referencia;
var descricao;
var preco;
var qtde;
var posicao;

function fnc_pesquisa_peca_lista(produto_referencia, peca_referencia, peca_descricao, peca_preco, voltagem, tipo, peca_qtde) {
	var url = "";

	if (tipo == "tudo") {
		url = "<? echo $url;?>.php?<?if (strlen($_GET['os'])>0) echo 'os='.$_GET['os'].'&';?>produto=" + produto_referencia + "&descricao=" + peca_referencia.value + "&tipo=" + tipo + "&voltagem=" + voltagem.value + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>&faturado=sim";
	}

	if (tipo == "referencia") {
		url = "<? echo $url;?>.php?<?if (strlen($_GET['os'])>0) echo 'os='.$_GET['os'].'&';?>produto=" + produto_referencia + "&peca=" + peca_referencia.value + "&tipo=" + tipo + "&voltagem=" + voltagem.value + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>&faturado=sim";
	}

	if (tipo == "descricao") {
		url = "<? echo $url;?>.php?<?if (strlen($_GET['os'])>0) echo 'os='.$_GET['os'].'&';?>produto=" + produto_referencia + "&descricao=" + peca_descricao.value + "&tipo=" + tipo + "&voltagem=" + voltagem.value + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>&faturado=sim";
	}<?php

	if ($login_fabrica <> 2) {?>
		if (peca_referencia.value.length >= 3 || peca_descricao.value.length >= 3) {<?php
	}?>
		janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=501, height=400, top=18, left=0");
		produto		= produto_referencia;
		referencia	= peca_referencia;
		descricao	= peca_descricao;
		preco		= peca_preco;
		qtde		= peca_qtde;
		 <?php
	if ($login_fabrica <> 2) {?>
		} else {//ELSE JS
			if (!document.getElementById('controle_blur')) {//HD 254266
				alert("<?=($sistema_lingua == 'ES') ? 'Digite al minus 3 caracters' : 'Digite pelo menos 3 caracteres!' ?>");
			} else {
				if (document.getElementById('controle_blur').value == 1) {
					alert("<?=($sistema_lingua == 'ES') ? 'Digite al minus 3 caracters' : 'Digite pelo menos 3 caracteres!' ?>");
				}
			}
		}<?php
	}?>
}
/* ####################################################################### */

function fnc_pesquisa_transportadora (xcampo, tipo)
{
	if (tipo == 'cnpj') {
		if ($.trim(xcampo.value).length < 8){
			alert('Por favor, digite 8 numero da CNPJ para fazer a pesquisa');
			return false;
		}
	}
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

function gravaDados(name, valor){
		try {
			$("input[name="+name+"]").val(valor);
		} catch(err){
			return false;
		}
}

function atualizaCausas(){
	var codigo_defeitos = $("#defeito_constatado_hidden").val();
	var auxOptions = "";

	$.ajax({
		url: "<?php echo $php_self ?>",
		data: {
			"defeitos": codigo_defeitos,
			"ajax_causa_defeito": "1"
		},
		complete: function(resp){
			var causas = JSON.parse(resp.responseText);

			$('#causa_defeito').html("<option value=''>Selecione uma opção</option>");

			$.each(causas, function(i,item) {
				$('#causa_defeito').append("<option value='"+item.codigo+"'>"+item.descricao+"</option>");
			});
		}
	});
}

window.onload = function(){
	$(function(){

	});
};







</script>
