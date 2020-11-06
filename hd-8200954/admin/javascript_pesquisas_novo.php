<script language='javascript'>

function fnc_pesquisa_posto(os, codigo, nome) {
	var codigo = jQuery.trim(codigo.value);
	var nome   = jQuery.trim(nome.value);

	if (codigo.length > 2 || nome.length > 2){
		Shadowbox.open({
			content:	"posto_pesquisa_2_nv.php?os=" + os + "&codigo=" + codigo + "&nome=" + nome,
			player:	"iframe",
			title:		"Pesquisa Posto",
			width:	800,
			height:	500
		});
	}else{
		alert("Preencha toda ou parte da informação para realizar a pesquisa!");
	}
}

function fnc_pesquisa_posto_2(valor, tipo) {
	var valor = jQuery.trim(valor.value);


	if (valor.length > 2){
		Shadowbox.open({
			content	: "posto_pesquisa_2_nv.php?"+ tipo + "="+valor,
			player	: "iframe",
			title	: "Pesquisa Posto",
			width	: 800,
			height	: 500
		});
	}else{
		alert("Preencha toda ou parte da informação para realizar a pesquisa!");
	}
}

function fnc_pesquisa_produto (descricao, referencia, posicao,posto) {
    var descricao   = jQuery.trim(descricao.value);
    var referencia  = jQuery.trim(referencia.value);
    var posto       = jQuery.trim(posto.value);
    var caminho     = "<?=$_SERVER['REQUEST_URI']?>";

	if ((descricao.length > 2 || referencia.length > 2) && posto.length > 0){
		Shadowbox.open({
			content:	"produto_pesquisa_2_nv.php?id_posto="+posto+"&descricao=" + descricao + "&referencia=" + referencia + "&posicao=" + posicao + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>",
			player:	"iframe",
			title:		"Pesquisa Produto",
			width:	800,
			height:	500
		});
	} else if (caminho.indexOf("os_consulta_lite") > 0 && posto.length == 0){
        Shadowbox.open({
			content:	"produto_pesquisa_2_nv.php?id_posto=&descricao=" + descricao + "&referencia=" + referencia + "&posicao=" + posicao + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>",
			player:	"iframe",
			title:		"Pesquisa Produto",
			width:	800,
			height:	500
		});
    } else {
        if (caminho.indexOf("os_consulta_lite") == 0) {
            alert("Preencha o posto ou toda ou parte da informação para realizar a pesquisa!");
        }
	}
}

function fnc_pesquisa_revenda (campo, tipo) {
	if (tipo == "nome") {
		var url = "pesquisa_revenda_nv.php?nome=" + campo;
	}
	if (tipo == "cnpj") {
		var url = "pesquisa_revenda_nv.php?cnpj=" + campo;
	}
	if (campo.length > 2) {
		Shadowbox.open({
			content: url,
			player : "iframe",
			title  : "Pesquisa Revenda",
			width  : 800,
			height : 500
		});
	}
	else {
		alert("Preencha toda ou parte da informação para realizar a pesquisa!");
	}
}

var referencia_pesquisa_peca;
var descricao_pesquisa_peca;

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
		//janela.referencia	= campo;
		referencia_pesquisa_peca = campo;
		//janela.descricao	= campo2;
		descricao_pesquisa_peca = campo2;
		janela.focus();
	}
	else{
		alert("Informe toda ou parte da informação para realizar a pesquisa");
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

function fnc_pesquisa_peca_lista (produto_referencia, peca_referencia, peca_descricao, peca_preco, voltagem, tipo, peca_qtde) {
	var url = "";
	if (tipo == "tudo") {
		url = "<? echo $url;?>.php?<?if (strlen($_GET['os'])>0) echo 'os='.$_GET['os'].'&';?>produto=" + produto_referencia + "&descricao=" + peca_referencia.value + "&tipo=" + tipo + "&voltagem=" + voltagem.value + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>";
	}

	if (tipo == "referencia") {
		url = "<? echo $url;?>.php?<?if (strlen($_GET['os'])>0) echo 'os='.$_GET['os'].'&';?>produto=" + produto_referencia + "&peca=" + peca_referencia.value + "&tipo=" + tipo + "&voltagem=" + voltagem.value + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>";
	}

	if (tipo == "descricao") {
		url = "<? echo $url;?>.php?<?if (strlen($_GET['os'])>0) echo 'os='.$_GET['os'].'&';?>produto=" + produto_referencia + "&descricao=" + peca_descricao.value + "&tipo=" + tipo + "&voltagem=" + voltagem.value + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>";
	}
<? if ($login_fabrica <> 2) { ?>
	if (peca_referencia.value.length >= 3 || peca_descricao.value.length >= 3) {
<? } ?>
		janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=501, height=400, top=18, left=0");
		janela.produto		= produto_referencia;
		janela.referencia	= peca_referencia;
		janela.descricao	= peca_descricao;
		janela.preco		= peca_preco;
		janela.qtde			= peca_qtde;
		janela.focus();
<? if ($login_fabrica <> 2) { ?>
	}else{
		alert("<? if($sistema_lingua == "ES"){
echo "Digite al minus 3 caracters";
}else{
echo "Digite pelo menos 3 caracteres!";
 } ?>");
	}
<? } ?>
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

function gravaDados(name, valor){
		try {
			$("input[name="+name+"]").val(valor);
		} catch(err){
			return false;
		}
}

</script>
