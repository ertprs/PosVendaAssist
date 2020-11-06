<script language='javascript'>

function fnc_pesquisa_posto(os, codigo, nome) {
	var codigo = jQuery.trim(codigo.value);
	var nome   = jQuery.trim(nome.value);

	if (codigo.length > 2 || nome.length > 2){
		Shadowbox.open({
			content:	"posto_pesquisa_2_nv.php?os=" + os + "&codigo=" + codigo + "&nome=" + nome,
			player:	"iframe",
			title:	"Pesquisa Posto",
			width:	800,
			height:	500
		});
	}else{
		alert("Preencha toda ou parte da informação para realizar a pesquisa!");
	}
}

function fnc_pesquisa_produto (descricao, referencia, posicao, posto) {
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

var referencia_pesquisa_peca;
var descricao_pesquisa_peca;
 
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

function retorna_posto(codigo_posto, posto, nome, cnpj, cidade, estado, credenciamento){
	// console.log(codigo_posto, posto, nome, cnpj, cidade, estado, credenciamento);

    gravaDados("codigo_posto", codigo_posto);
    gravaDados("nome_posto", nome);
}

function retorna_dados_produto(produto,linha,nome_comercial,voltagem,referencia,descricao,referencia_fabrica,garantia,ativo,valor_troca,troca_garantia,troca_faturada,mobra,off_line,capacidade,ipi,troca_obrigatoria,posicao){

        gravaDados("referencia_produto",referencia);
        gravaDados("descricao_produto",descricao);
}

function gravaDados(name, valor){
	$("input[name=" + name + "]").val(valor);
}

</script>
