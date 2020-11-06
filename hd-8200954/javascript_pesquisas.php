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

function fnc_pesquisa_produto(campo, campo2, tipo, voltagem, referencia_pai, descricao_pai, referencia_avo, descricao_avo, limpa = null) {


	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo + "&limpa=" + limpa + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>";

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
		janela.referencia	= campo;
		janela.descricao	= campo2;
		janela.focus();
	}


}
/* ####################################################################### */
function trim(str) {
	        return str.replace(/^\s+|\s+$/g,"");
}

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
		if($login_fabrica == 131){
			$url = "peca_defeito_pesquisa_lista";
		}else{
			$url = "peca_pesquisa_lista";
		}

	}
?>
var produto;
var referencia;
var descricao;
var preco;
var qtde;
var qtde_fotos;
var serial_lcd;
var qtde_estoque;
var tela_pedido = false;

function fnc_pesquisa_peca_lista(produto_referencia, peca_referencia, peca_descricao, peca_preco, voltagem, tipo, peca_qtde, qtde_estoque_item, qtde_fotos_item, serial_lcd_item, linha_i) {
	var url = "";

	var tipo_pedido;

	<?php
    if($login_fabrica == 140){
        ?>
        alert('Favor confirmar se a peça selecionada consta na vista explodida do produto!');
        <?php
    }
    ?>

	if(document.frm_pedido){
		tela_pedido = true;
		if(document.frm_pedido.tipo_pedido){
			tipo_pedido = document.frm_pedido.tipo_pedido.value ;
		}
	}
	 if(document.getElementById('tipo_pedido')) {
		tipo_pedido = document.getElementById('tipo_pedido').value;
	}

	if (tipo == "tudo") {
		url = "<? echo $url;?>.php?<?if (strlen($_GET['os'])>0) echo 'os='.$_GET['os'].'&';?>produto=" + produto_referencia + "&descricao=" + peca_referencia.value + "&tipo=" + tipo + "&voltagem=" + voltagem.value + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>&faturado=sim&linha_i="+linha_i;
	}

	if (tipo == "referencia") {
		<?php
		if($login_fabrica == 131){
			?>
			url = "<? echo $url;?>.php?<?if (strlen($_GET['os'])>0) echo 'os='.$_GET['os'].'&';?>produto=" + produto_referencia + "&referencia=" + peca_referencia.value + "&tipo=" + tipo + "&tipo_pedido=" + tipo_pedido + "&voltagem=" + voltagem.value + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>&faturado=sim&linha_i="+linha_i;
			<?php
		}else{
			?>
			url = "<? echo $url;?>.php?<?if (strlen($_GET['os'])>0) echo 'os='.$_GET['os'].'&';?>produto=" + produto_referencia + "&peca=" + peca_referencia.value + "&tipo=" + tipo + "&tipo_pedido=" + tipo_pedido + "&voltagem=" + voltagem.value + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>&faturado=sim&linha_i="+linha_i;
			<?php
		}
		?>

	}

	if (tipo == "descricao") {
		url = "<? echo $url;?>.php?<?if (strlen($_GET['os'])>0) echo 'os='.$_GET['os'].'&';?>produto=" + produto_referencia + "&descricao=" + peca_descricao.value + "&tipo=" + tipo + "&tipo_pedido=" + tipo_pedido + "&voltagem=" + voltagem.value + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>&faturado=sim&linha_i="+linha_i;
	}

	<?php
	if($login_fabrica == 131){
		?>

		var codigo_defeitos = $("#defeito_constatado_hidden").val();

		//url = "<? echo $url;?>.php?<?if (strlen($_GET['os'])>0) echo 'os='.$_GET['os'].'&';?>produto=" + produto_referencia + "&descricao=" + peca_descricao.value + "&tipo=" + tipo + "&tipo_pedido=" + tipo_pedido + "&voltagem=" + voltagem.value + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>&faturado=sim&linha_i="+linha_i;

		url = url+"&codigo_defeitos="+codigo_defeitos;
		<?php
	}
	?>

    // Adiciona o valor se existe o elemento input no formulário
    if (document.frm_os && typeof document.frm_os.versao_produto !== 'undefined') {
        url += '&versao_produto='+document.frm_os.versao_produto.value;
    }

	<?php

	if ($login_fabrica <> 2) {

	?>

	if (trim(peca_referencia.value).length >= 3 || trim(peca_descricao.value).length >= 3) {

	<?php

	}

	?>
		janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=501, height=400, top=18, left=0");

		produto    = produto_referencia;
		referencia = peca_referencia;
		descricao  = peca_descricao;
		preco      = peca_preco;
		qtde       = peca_qtde;

		console.log(qtde_fotos_item);
		<?php
		if ($login_fabrica == 3) {
		?>
			qtde_fotos  = qtde_fotos_item;
			serial_lcd = serial_lcd_item;
		<?php
		}

		if (in_array($login_fabrica,[120,201])) {
		?>
			qtde_estoque = qtde_estoque_item;
		<?php
			}
		?>

		janela.focus();

	<?php

	if ($login_fabrica <> 2) {

	?>
	} else {//ELSE JS
			if (!document.getElementById('controle_blur')) {//HD 254266
				alert("<?=($sistema_lingua == 'ES') ? 'Escriba al menos 3 caracteres' : 'Digite pelo menos 3 caracteres!' ?>");
			} else {
				if (document.getElementById('controle_blur').value == 1) {
					alert("<?=($sistema_lingua == 'ES') ? 'Escriba al menos 3 caracteres' : 'Digite pelo menos 3 caracteres!' ?>");
				}
			}
		}
	<?php
	}
	?>
}
/* ####################################################################### */

function fnc_pesquisa_transportadora (xcampo, tipo)
{
	if (tipo == 'cnpj') {
		if ($.trim(xcampo.value).length < 8){
			alert('Por favor, digite 8 números do CNPJ para fazer a pesquisa');
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

</script>
