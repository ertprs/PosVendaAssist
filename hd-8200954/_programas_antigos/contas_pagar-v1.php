<?
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Pragma: no-cache"); // HTTP/1.0

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';



$nosso_ip = include("nosso_ip.php");
if ($ip <> $nosso_ip) { header ("Location: menu_cadastro.php"); exit(); }

$msg_erro = '';
if(strlen($_POST["baixar_sel"]) > 0) {
	$cont_itens= count($_POST["pagar"]);
	$data_baixa= $_POST["data_baixa"];
	if(($cont_itens>0) and (strlen($data_baixa)>0)){
		$data_baixa = "'" . substr ($data_baixa,6,4) . "-" . substr ($data_baixa,3,2) . "-" . substr ($data_baixa,0,2) . "'" ;
		for($i=0 ; $i< $cont_itens; $i++){
			$ct_pagar= "";
			$ct_pagar= $_POST["pagar"][$i];
			//echo "pagar: $ct_pagar";

			if(strlen($ct_pagar)>0){
				$sql = "UPDATE tbl_pagar SET 
							pagamento	= current_timestamp,
							valor_pago	= valor
						WHERE pagar = $ct_pagar;";
				$res = pg_exec($con,$sql);
				//echo "<BR>SQL: $sql";
				$fornecedor      = '';
				$valor           = '';
				$documento       = '';
				$vencimento      = '';
				$valor_pago      = '';
				$obs			 = '';
			}			
		}
	}else{
		echo "erro";
	}
}
?>
<?
$title = "Contas a Pagar";
$layout_menu = "cadastro";
include 'cabecalho.php';
?>

<script type="text/javascript" src="javascripts/prototype.js"></script>
<script type="text/javascript" src="javascripts/autocomplete.js"></script>
<link rel="stylesheet" type="text/css" href="javascripts/autocomplete.css" /> 
<style>
.Conteudo2,.Titulo2 {
		font:12px "Segoe UI", Tahoma;	
}
	h3 {
		font-size:16px;
		font-weight:bold;
	}
</style>
<style type="text/css">
input.botao {
	background:#ced7e7;
	color:#000000;
	border:2px solid #ffffff;
}
.borda {
	border-width: 2px;
	border-style: dotted;
	border-color: #000000;
}
.menu_top {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	border: 0px;
}
.table_line {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	color: #000000;
	border: 0px;
}
.border {
	border: 1px solid #ced7e7;
}

</style>


<script type="text/javascript" src="javascripts/ajax_busca.js"></script>
<script language='javascript' src='ajax.js'></script>
<script language='javascript'>

//SELECIONA O FATURAMENTO RELACIONADO COM O FORNECEDOR
function selecionar(a){
	var nf =document.getElementById('nf').value;
	var doc=document.getElementById('documento').value;
	document.getElementById('doc_final').innerHTML= "<font color='black'>Documento:<b>"+nf+"-"+doc+"</b></font>";
}


function duplo(d){
	//alert();
	document.getElementById('fatDes').fucus;
	document.getElementById('fatDes').dblclick;
}

function retornaExibe2(http,componente, acao) {
	var com = document.getElementById(componente);
	if (http.readyState == 1) {
		com.innerHTML = "&nbsp;&nbsp;Carregando...&nbsp;&nbsp;<br><img src='imagens/carregar_os.gif' >";
		//CHAMA A FUNCAO DE CARREGANDO(LOADING) ENQUANTO NAO EXISTIR O RETORNO
		if(acao=='exibir'){

		}else{
			carregaMsg(50,300,50, 1);
		}		
	}
	if (http.readyState == 4) {
		if (http.status == 200) {
			results = http.responseText.split("|");
			if (typeof (results[0]) != 'undefined') {
				if (results[0] == 'ok') {
					com.innerHTML   = results[1] ; //retorna a "LISTA DE CONTAS A PAGAR"
					document.getElementById('msg').innerHTML= results[2]; //retorna a mensagem de sucesso ou de erro
					if(acao=='exibir'){
						//nao exibe nada
					}else{
						document.getElementById('faturamentoID').value="";
						setTimeout('exibeMsg(50, 300, 50)',1000);
					}
				}else{
					com.innerHTML   = "<h4>Ocorreu um erro</h4>";
					document.getElementById('msg').innerHTML= "<h4>Ocorreu um erro ao carregar</h4>";
				}
			}else{
				alert ('Fechamento nao processado');
			}
		}
	}
}

//FUNÇAO USADA PARA ATUALISAR, INSERIR E ALTERAR		
function Exibir2(componente,solicita, documento, acao) {
	var msg = document.getElementById('msg');//criar instancia de msg
	if(acao=='insert'){
		var documento	= document.getElementById('nf').value + '-'+ document.getElementById('documento').value;
		var valor		= document.getElementById('valor').value;
		var fornID		= document.getElementById('fornID').value;
		var vencimento	= document.getElementById('vencimento').value;
		var obs			= document.getElementById('obs').value;
		var fatID		= document.getElementById('faturamentoID').value;
		url = "contas_pagar_retorno_cadastro_ajax?ajax=sim&acao="+escape(acao)+"&documento="+escape(documento)+"&valor="+ escape(valor)+"&fornID="+escape(fornID)+"&vencimento="+vencimento+"&obs="+obs+"&fatID="+fatID;
		//alert(url);
		//return true;
	}else{
		if(acao=='baixar'){
			msg.style.display='inline';
			var conta_pg	= document.getElementById('conta_pagar').value;
			var valor_pago	= document.getElementById('valor_pago').value;
			var pagamento	= document.getElementById('pagamento').value; //data da baixa
			var obs			= document.getElementById('obs').value;
			url = "contas_pagar_retorno_cadastro_ajax?ajax=sim&acao="+escape(acao)+"&conta_pagar="+conta_pg+"&valor_pago="+escape(valor_pago)+"&obs="+(obs)+"&pagamento="+(pagamento);
			//alert(url);
			//return true;
		}else{
			if(acao=='alterar'){
				msg.style.display='inline';
				var documento	= document.getElementById('nf').value + '-'+ document.getElementById('documento').value;
				var fornID		= document.getElementById('fornID').value;
				var valor		= document.getElementById('valor').value;
				var vencimento	= document.getElementById('vencimento').value;
				var pagamento	= document.getElementById('pagamento').value; //data da baixa
				var valor_pago	= document.getElementById('valor_pago').value;
				var conta_pg	= document.getElementById('conta_pagar').value;
				var obs			= document.getElementById('obs').value;
				url = "contas_pagar_retorno_cadastro_ajax?ajax=sim&acao="+escape(acao)+"&documento="+documento+"&fornID="+fornID+"&valor="+valor+"&vencimento="+vencimento+"&pagamento="+pagamento+"&valor_pago="+escape(valor_pago)+"&conta_pagar="+conta_pg+"&obs="+obs;
			}else{
				msg.style.display = 'none';
				url = "contas_pagar_retorno_cadastro_ajax?ajax=sim&programa2="+escape(solicita) ;
			}
		}
	}
	http.open("GET", url , true);
	http.onreadystatechange = function () { retornaExibe2 (http,componente, acao) ; } ;
	http.send(null);
}

//FUNÇAO USADA PARA CARREGAR UMA CONTA_PAGAR DA LISTA DE PENDENTES
function retornaPagar(http,componente) {
	var doc = document.getElementById('documento');
	var forn = document.getElementById('fornID');
	var vlr = document.getElementById('valor');

	if (http.readyState == 1) {
		carregaMsg(50,300,50, 1);
	}
	if (http.readyState == 4) {
		if (http.status == 200) {
			results = http.responseText.split("|");
			if (typeof (results[0]) != 'undefined') {
				if (results[0] == 'ok') {
					document.getElementById('nf').value = results[1];// nota fiscal
					document.getElementById('documento').value = results[2];
					document.getElementById('fornID').value = results[3];
					document.getElementById('fornName').value = results[4];
					document.getElementById('valor').value = results[5];
					document.getElementById('vencimento').value = results[6];
					document.getElementById('obs').value = results[7];
					document.getElementById('conta_pagar').value = results[8];
					
					carregaMsg(50,300,50, 2);
					document.getElementById('bt_cad').style.display = 'none';
					document.getElementById('bt_baixar').style.display = 'inline';
					document.getElementById('bt_alt').style.display = 'inline';
					//document.getElementById('bt_limpar').style.display = 'inline';
					document.getElementById('valor_pago').style.display = 'inline';
					document.getElementById('lab_valor_pago').style.display = 'inline';
					document.getElementById('pagamento').style.display = 'inline';
					document.getElementById('lab_pagamento').style.display = 'inline';
				}else{
					com5.innerHTML   = "<h4>Ocorreu um erro</h4>";
				}
			}else{
				alert ('Fechamento nao processado');
			}
		}
	}
}

function exibirPagar(componente,conta_pagar, documento, acao) {
	var solicita= document.getElementById('documento').value;
	var documento= document.getElementById('documento').value;
	var valor	= document.getElementById('valor').value;
	var fornID= document.getElementById('fornID').value;
	var vencimento= document.getElementById('vencimento').value;
	var obs			= document.getElementById('obs').value;
	url = "contas_pagar_retorno_cadastro_ajax?ajax=mostra&acao="+escape(acao)+"&conta_pagar="+escape(conta_pagar);
	http.open("GET", url , true);
	http.onreadystatechange = function () { retornaPagar (http,componente,solicita) ; } ;
	http.send(null);
}

//FUNÇAO USADA PARA CARREGAR UMA CONTA_PAGAR DA LISTA DE PENDENTES
function retornaFornecedor(http,componente) {
	var f= document.getElementById('f1');
	if (http.readyState == 1) {
		f.innerHTML = "&nbsp;&nbsp;Carregando...&nbsp;&nbsp;<br><img src='imagens/carregar_os.gif' >";
		//carregaMsg(50,300,50, 1);
	}
	if (http.readyState == 4) {
		if (http.status == 200) {
			results = http.responseText.split("|");
			if (typeof (results[0]) != 'undefined') {
				if (results[0] == 'ok') {
					f.innerHTML = results[1];// pagin
				}else{
					f.innerHTML   = "<h4>Ocorreu um erro</h4>"+results[0];
				}
			}else{
				alert ('Fechamento nao processado');
			}
		}
	}
}

function exibirFornecedor(componente,conta_pagar, documento, acao) {
var solicita= document.getElementById('documento').value;
	if(acao=='insert'){
		var documento	= document.getElementById('nf').value + '-'+ document.getElementById('documento').value;
		var valor		= document.getElementById('valor').value;
		var fornID		= document.getElementById('fornID').value;
		var vencimento	= document.getElementById('vencimento').value;
		var obs			= document.getElementById('obs').value;
		var fatID		= document.getElementById('faturamentoID').value;
		url = "contas_pagar_retorno_cadastro_ajax?ajax=sim&acao="+escape(acao)+"&documento="+escape(documento)+"&valor="+ escape(valor)+"&fornID="+escape(fornID)+"&vencimento="+vencimento+"&obs="+obs+"&fatID="+fatID;
	}else{
		msg.style.display = 'none';
		url = "contas_pagar_retorno_cadastro_ajax?ajax=sim&programa2="+escape(solicita) ;
	}
	url = "contas_pagar_retorno_cad_forn_ajax?ajax=mostra&acao="+escape(acao)+"&conta_pagar="+escape(conta_pagar);
	http.open("GET", url , true);
	http.onreadystatechange = function () { retornaFornecedor (http,componente,solicita) ; } ;
	http.send(null);
}

/*
//funçao usada para carregar os faturamentoS
function retornaFat(http,componente) {
	var forn = document.getElementById('fornID');
	var vlr = document.getElementById('valor');
	var com5 = document.getElementById('f2');
	if (http.readyState == 1) {
		com5.style.display='inline';
		com5.innerHTML = "&nbsp;&nbsp;<font color='#0000ff'>Carregando...</font>&nbsp;&nbsp;<br><img src='imagens/carregar_os.gif' >";
	}
	if (http.readyState == 4) {
		if (http.status == 200) {
			results = http.responseText.split("|");
			if (typeof (results[0]) != 'undefined') {
				if (results[0] == 'ok') {
					com5.innerHTML = results[1];

					document.getElementById('msg_fat').innerHTML= "<font color='#ff0000'>existem faturamentos para esse fornecedor</font>";
					document.getElementById('msg_fat').style.display='inline';
				}else{
					com5.innerHTML   = "&nbsp;&nbsp;<font color='#0000ff'>Sem faturamentos para esse fornecedor</font>";
				}
			}else{
				alert ('Fechamento nao processado');
			}
		}
	}
}

function exibirFat(componente,conta_pagar, documento, acao) {
	var solicita= document.getElementById('documento').value;
	var fornID= document.getElementById('fornID').value;
	url = "contas_pagar_retorno_cadastro_ajax?ajax=mostra&acao=faturamento&fornID="+escape(fornID);
	http.open("GET", url , true);
	http.onreadystatechange = function () { retornaFat (http,componente,solicita) ; } ;
	http.send(null);
}
*/

function limparForm(id){
	eval("var formu = document."+id+";")
	for( var i = 0 ; i < formu.length; i++ ){
		if (formu.elements[i].type=='text'){
			formu.elements[i].value="";
		}
	}
}
function exibeFornec(acao){
	if (acao==0){
		document.getElementById('f1').style.display = 'none';
		limparForm('frm_posto');

	}
	if (acao==1){
		document.getElementById('msg').style.display='none';
		document.getElementById('f1').style.display = 'inline';
		document.bt_cad_forn.cnpj.focus();
	}
}		

//MUDA A POSIÇÃO DA MENSAGEM
function carregaMsg(x, w, h,tipo){
	var msg = document.getElementById('msg');
	if(tipo==1){
		msg.style.display= "inline";
		msg.style.top    = (x+'%');
		msg.style.width  = (w+'px');
		msg.style.height = (h+'px');
		msg.innerHTML = "&nbsp;&nbsp;Carregando...&nbsp;&nbsp;<br><img src='imagens/carregar_os.gif' >";

	}else{
		msg.style.display= "none";			
	}
}

function exibeMsg(x, w, h, tipo){
	//1 - mostrar e ocultar(loading)
	//1 - mostrar e movimentar
	if(tipo==1){
		document.getElementById('msg').style.top    = '50%';
		document.getElementById('msg').style.width  = '300px';
		document.getElementById('msg').style.height = '50px';
	}
	document.getElementById('msg').style.top    = (x+'%');
	document.getElementById('msg').style.width  = (w+'px');
	document.getElementById('msg').style.height = (h+'px');
	x = x-1;
	w = w;
	h = h-2;
	if(x > 35){
		setTimeout("exibeMsg("+x+ ","+ w+","+h+",'"+tipo+"')",20);
	}else{
		
		return true;
	}
}
function msg_help(i){
	var msg='';
	
	if(i == 0){
		document.getElementById('msg_fat').style.display = 'none';
	}else{
		if(i == 1){
			
			msg= "<font color='blue'>Para exibir os documentos use a tecla \"ESPAÇO\" ou \"CLIQUE DUPLO\"! \"ENTER\" para selecionar!"
		}
		if(i == 2){
			msg= "<font color='blue'>O nº do documento é gerado através de: (Nota Fiscal + Nº do Boleto)<br>Ex: NF: 001852 - Boleto: 01 <br>Resultado: 001852-01 !"
		}			

		document.getElementById('msg_fat').style.display = 'inline';
		document.getElementById('msg_fat').innerHTML= msg;

		if(i == 3){
			//document.getElementById(id) == null 
			if(document.getElementById('faturamentoID').value== ''){
				//alert('fat eh vazio');
			}else{
				//alert('fat NAO eh vazio'+ document.getElementById('faturamentoID').value);
			}
			document.getElementById('msg_fat').style.display = 'none';
		}			
	
	}
}

//OCULTA O DIV USADO PARA O FATURAMENTO
function ocultar(id){
	document.getElementById('f2').style.display='none';
}

function cad_forn(){
	document.getElementById('f1').style.display = 'inline';
}

/*
function mudar_foco(x){
	if(document.getElementById('fornID').value==''){
		if(se_existe('forn_nao_cad')){
			if(document.getElementById('forn_nao_cad').value==1)
				document.getElementById('msg_fat').style.display = 'inline';
			document.getElementById('msg_fat').innerHTML= "Para cadastrar esse fornecedor <a href='#' onclick = 'exibirForn'>clique aqui!</a>"
		}else{
			setTimeout('mudar_foco('+x+')',100);
		}
		if(x > 8)
			return true;
	}else{
		exibirFat();
		document.getElementById('fornID').click();
	}
	if(x > 8)
		return true;
}
*/

function set_focus(id, x){
	if(se_existe(id)){
		document.getElementById(id).focus();
	}else{
		if(x > 10){
			return false;
		}else{
			x++;
			setTimeout("set_focus('"+id+"',"+ x +");",1000);
		}
	}
}

//TESTA SE EXISTE UM CAMPO COM A ID PASSADA POR PARAMETRO
function se_existe(id){
	if (!document.getElementById(id) || document.getElementById(id) == null || document.getElementById(id) == "undefined"){
		return false;
	}else{
		return true;
	}
}

// LIMPA O FORMA E MOSTRA O BOTAO CADASTRAR
function limpar(){
	document.getElementById('msg').innerHTML="";
	document.getElementById('documento').value	= "";
	document.getElementById('fornID').value		= "";
	document.getElementById('fornName').value	= "";
	document.getElementById('valor').value		= "";
	document.getElementById('vencimento').value = "";
	document.getElementById('obs').value		= "";
	document.getElementById('conta_pagar').value= "";
	document.getElementById('nf').value		= "";
	document.getElementById('obs').value		= "";
	document.getElementById('faturamentoID').value  = "";
	document.getElementById('bt_cad').style.display			= 'inline';
	document.getElementById('bt_baixar').style.display		= 'none';
	document.getElementById('bt_alt').style.display			= 'none';
	//document.getElementById('bt_limpar').style.display		= 'none';
	document.getElementById('valor_pago').style.display		= 'none';
	document.getElementById('lab_valor_pago').style.display = 'none';
	document.getElementById('pagamento').style.display		= 'none';
	document.getElementById('lab_pagamento').style.display	= 'none';
}

// FUNÇÃO PARA FORMATAR O NUMERO PARA DECIMAL COM A QTD DE CASAS DESEJADA
function format_number(pnumber,decimals){ 
    if (isNaN(pnumber)) { return 0}; 
    if (pnumber=='') { return 0}; 
     
    var snum = new String(pnumber); 
    var sec = snum.split('.'); 
    var whole = parseFloat(sec[0]); 
    var result = ''; 
     
    if(sec.length > 1){ 
        var dec = new String(sec[1]); 
        dec = String(parseFloat(sec[1])/Math.pow(10,(dec.length - decimals))); 
        dec = String(whole + Math.round(parseFloat(dec))/Math.pow(10,decimals)); 
        var dot = dec.indexOf('.'); 
        if(dot == -1){ 
            dec += '.'; 
            dot = dec.indexOf('.'); 
        } 
        while(dec.length <= dot + decimals) { dec += '0'; } 
        result = dec; 
    } else{ 
        var dot; 
        var dec = new String(whole); 
        dec += '.'; 
        dot = dec.indexOf('.');         
        while(dec.length <= dot + decimals) { dec += '0'; } 
        result = dec.replace(".", ","); 
    }     
    return result; 	
} 

//FUNÇÃO PARA CALCULAR O TOTAL SELECIONADO 
function calcula_total_selecionado(tot){
	//alert('passou aqui');
	var forn=0, lenPr = 0, len=0, soma = 0.0, somap = 0,testav=0, testap=0, conti=0;
	var cont_itens= document.getElementById('cont_itens').value;
	//alert(cont_itens);
	for (f=0; f<cont_itens;f++) { 
		if(document.getElementById('pagar_'+f).value==''){
			
		}else{
			if(document.getElementById('pagar'+f).checked == true){
				valor= parseFloat(document.getElementById('pagar_'+f).value);
				//SOMA VALOR 
				soma += valor; //format_number(valor,2);
			}
		}
	}
	soma = format_number(soma,2);
	soma = soma.toString().replace( ".", "," );
	document.getElementById('resultado').value= soma;
}

function createRequestObject(){
	var request_;
	var browser = navigator.appName;
	if(browser == "Microsoft Internet Explorer"){
		 request_ = new ActiveXObject("Microsoft.XMLHTTP");
	}else{
		 request_ = new XMLHttpRequest();
	}
	return request_;
}
			
var http_forn = new Array();

//FUNÇAO USADA PARA ATUALISAR, INSERIR E ALTERAR		
function gravar_fonecedor(formulatio) {
//	ref = trim(ref);
	var acao='cadastrar';

	url = "contas_pagar_retorno_cad_forn_ajax.php?ajax=sim&acao="+acao;
	for( var i = 0 ; i < formulatio.length; i++ ){
		if (formulatio.elements[i].type=='text' || formulatio.elements[i].type=='select-one'){
			url = url+"&"+formulatio.elements[i].name+"="+escape(formulatio.elements[i].value);
		}
	}
	var curDateTime = new Date();
	http_forn[curDateTime] = createRequestObject();
	http_forn[curDateTime].open('GET',url,true);
	http_forn[curDateTime].onreadystatechange = function(){
		if (http_forn[curDateTime].readyState == 4) 
		{
			if (http_forn[curDateTime].status == 200 || http_forn[curDateTime].status == 304) 
			{
				var response = http_forn[curDateTime].responseText.split("|");
				if (response[0]=="ok"){
					alert(response[1]);
					exibeFornec(0);
					formulatio.bt_cad_forn.value='Gravar';
				}
				if (response[0]=="0"){
					// posto ja cadastrado
					alert(response[1]);
					formulatio.bt_cad_forn.value='Gravar';
				}
				if (response[0]=="1"){
					// dados incompletos
					alert("Campos incompletos:\n\n"+response[1]);
					formulatio.bt_cad_forn.value='Gravar';
				}
				if (response[0]=="2"){
					// erro inesperado
					alert("Ocorreu um erro inesperado no momento da gravação:\n\n"+response[1]);
					formulatio.bt_cad_forn.value='Gravar';
				}
			}
		}
	}
	http_forn[curDateTime].send(null);
}
</script>

<body>

<div name='blabla' id='f1' style='padding:10px; background-color:#ffffff; filter:alpha(opacity=90); opacity: .90 border-color:#cccccc; border:1px solid #bbbbbb; display:none; width:650px; height:350px; margin-left:-325px; margin-top:20px; position:absolute;'>
<form name="frm_posto" method="post" action="<? echo $PHP_SELF ?>">
<table class="border" width='660' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr>
		<td colspan="5" class="menu_top" align='center'>
			<font color='#36425C'><? echo "INFORMAÇÕES CADASTRAIS";?>
		</td>
	</tr>
	<tr bgcolor='#d9e2ef' class="table_line" align='center'>
		<td>CNPJ/CPF</td>
		<td>I.E.</td>
		<td>FONE</td>
		<td>FAX</td>
		<td>CONTATO</td>
	</tr>
	<tr class="table_line" align='center'>
		<td><input type="text" name="cnpj" id="cnpj" size="15" maxlength="20" value="<? echo $cnpj ?>"></td>
		<td><input type="text" name="ie" id="ie" size="20" maxlength="20" value="<? echo $ie ?>"></td>
		<td><input type="text" name="fone" id="fone" size="10" maxlength="20" value="<? echo $fone ?>"></td>
		<td><input type="text" name="fax" id="fax" size="10" maxlength="20" value="<? echo $fax ?>"></td>
		<td><input type="text" name="contato" id="contato" size="20" maxlength="30" value="<? echo $contato ?>" style="width:100px"></td>
	</tr>
	<tr bgcolor='#d9e2ef' class="table_line" align='center'>
		<td colspan="2"><? echo "CÓDIGO";?></td>
		<td colspan="3"><? echo "NOME (RAZÃO SOCIAL)";?></td>
	</tr>
	<tr class="table_line" align='center'>
		<td colspan="2"><input type="text" name="codigo" id="codigo" value="<? echo $codigo ?>"></td>		
		<td colspan="3"><input type="text" name="nome" id="nome" value="<? echo $nome ?>"></td>
	</tr>
</table>

<br>

<table class="border" width='660' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr bgcolor='#d9e2ef' class="table_line" align='center'>
		<td colspan="2">ENDEREÇO</td>
		<td>NÚMERO</td>
		<td colspan="2">COMPLEMENTO</td>
	</tr>
	<tr class="table_line" align='center'>
		<td colspan="2"><input type="text" name="endereco" size="30" maxlength="49" value="<? echo $endereco ?>"></td>
		<td><input type="text" name="numero" size="10" maxlength="10" value="<? echo $numero ?>"></td>
		<td colspan="2"><input type="text" name="complemento" size="20" maxlength="20" value="<? echo $complemento ?>"></td>
	</tr>
	<tr bgcolor='#d9e2ef' class="table_line" align='center'>
		<td colspan="2">BAIRRO</td>
		<td>CEP</td>
		<td>CIDADE</td>
		<td>ESTADO</td>
	</tr>
	<tr class="table_line" align='center'>
		<td colspan="2"><input type="text" name="bairro" size="30" maxlength="30" value="<? echo $bairro ?>"></td>
		<td><input type="text" name="cep" size="8" maxlength="8" value="<? echo $cep ?>"></td>
		<td><input type="text" name="cidade" size="10" maxlength="30" value="<? echo $cidade ?>"></td>
		<td><input type="text" name="estado" size="2" maxlength="2" value="<? echo $estado ?>"></td>
	</tr>
</table>
<br>
<table class="border" width='660' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr bgcolor='#d9e2ef' class="table_line" align='center'>
		<td>NOME FANTASIA</td>
		<td>E-MAIL</td>
		<td>CAPITAL/INTERIOR</td>
	</tr>
	<tr class="table_line" align='center'>
		<td>
			<input type="text" name="nome_fantasia" size="30" maxlength="40" value="<? echo $nome_fantasia ?>" >
		</td>
		<td>
			<input type="text" name="email" size="30" maxlength="50" value="<? echo $email ?>">
		</td>
		<td>
			<select name='capital_interior' size='1'>
				<option value='CAPITAL' <? if ($capital_interior == 'CAPITAL') echo ' selected ' ?> ><? if($sistema_lingua) echo "CAPITAL";else echo "Capital";?></option>
				<option value='INTERIOR' <? if ($capital_interior == 'INTERIOR') echo ' selected ' ?> ><? if($sistema_lingua) echo "PROVINCIA";else echo "Interior";?></option>
			</select>
		</td>
	</tr>
</table>
<center>
<INPUT TYPE='button' name='bt_cad_forn' id='bt_cad_forn' value='Gravar' onClick="if (this.value=='Gravando...'){ alert('Aguarde');}else {this.value='Gravando...'; gravar_fonecedor(this.form);}">
<INPUT TYPE='button' name='bt_fecha_forn' id='bt_fecha_forn' value='Fechar' onClick="exibeFornec(0)">
</center>
</form>
</div>

<BR>
<FORM METHOD=POST ACTION="<? $PHP_SELF ?>" NAME='frm_pagar'>
<table align='center' width='500' class='table_line' border='1' cellspacing='1' cellpadding='1'>
  <tr>
	<td>
	  <table align='center' width='100%' border='0' cellspacing='1' cellpadding='1'>
		<tr>
			<td nowrap colspan='3' align='right'>&nbsp;</td>
		</tr>
		<tr bgcolor='#596D9B'>
			<td nowrap colspan='3' class='menu_top' align='left'>
				<font size='3'>Contas a Pagar</font>
				<?
				echo "<input type='hidden' id='conta_pagar' name='conta_pagar' value='$conta_pagar'>";
				?>
			</td>
		</tr>
		<tr>
			<td nowrap colspan='1' align='center'>&nbsp;</td>
			<td nowrap width='150' colspan='2' align='center'>&nbsp;</td>
		</tr>
		<tr bgcolor='#ced7e7'>
			<td nowrap align='center' colspan='4'>
				<font size='2'>FORNECEDOR</font>
				<input type='hidden' id='faturamentoID' value='' >

			</td>
		</tr>
		<tr>
			<td colspan='3' align='center' align='center'>
				<input id="fornID" name="fornID" type="hidden" value=''>
				<input type="text" id="fornName" name="fornName" value='' size="70" class='frm' >
				<script type="text/javascript">
					new CAPXOUS.AutoComplete("fornName", function() {
						return "contas_pagar_retorna_forn_ajax.php?typing=" + this.text.value;
					});
				</script>
			</td>
			<td>
				<div name='msg_fat' id='msg_fat' style='padding:10px; background-color:#ffffff; filter: alpha(opacity=60); opacity: .70 border-color:#3333dd; border:1px solid #3333dd; display:none; width:100px; height:60px; position:absolute;'></div>
			</td>
		</tr>
		<tr>
			<td nowrap colspan='1' ></td>
			<td nowrap colspan='1' ></td>
			<td nowrap width='150' colspan='1' align='center'>
				<div name='f2' id='f2' style='padding:4px; background-color:#ffffff; filter: alpha(opacity=70); opacity: .70 border-color:#cccccc; border:1px solid #bbbbbb; display:none; width:120px; height:80px; position:absolute;'></div>
			</td>
		</tr>
		<tr bgcolor='#ced7e7'>
			<td nowrap align='center' colspan='3'>
				<font size='2'>NÚMERO DA NOTA FISCAL-BOLETO</font>
			</td>
		</tr>
		<tr>
			<td colspan='3' align='center' nowrap>
			<TABLE>
			<TR>
				<td colspan='1' align='center' nowrap> 
					Nota Fiscal<br>
				<input type="text" id="nf" name="nf" value='' size="15" maxlength='7' class='frm' onfocus="msg_help(1);" onblur="msg_help(0);" onkeyup="selecionar(event);">
				<script type="text/javascript">
					new CAPXOUS.AutoComplete("nf", function() {

						return "contas_pagar_retorna_nf_ajax.php?fornID=" + document.getElementById('fornID').value +"&typing=" + this.text.value;
					});
				</script>
				</td>
				<td colspan='1' align='center' nowrap width='10'><br>&nbsp;-&nbsp;
				</td>
				<td colspan='1' align='center' nowrap >
					Boleto<br>
					<input type='text' name='documento' id='documento' value='' size='8' maxlength='20' class="frm" onfocus='msg_help(2);' onblur='msg_help(0);' onkeyup="selecionar(event);"> 
				</td>

				<td align='center' >Documento<br>
					<div id='doc_final' style='padding:4px; background-color:#ffffff; width:200px; height:20px;'></div>

					<div name='f2' id='f2' style='padding:4px; background-color:#ffffff; filter: alpha(opacity=70); opacity: .70 border-color:#cccccc; border:1px solid #bbbbbb; display:none; width:120px; height:80px; position:absolute;'></div>
				</TD>
				</TR>
			</TABLE>
			</td>
		</tr>
		<tr>
			<td colspan='4' align='center'>
				<? /* <input type="text" id="faturamento" name="faturamento" value='<?echo $faturamento;?>' size="60" class='frm'>*/ ?>
			<div id="msg" style="width: 300px; height:50px; position: absolute; top: 50%; left: 50%; margin-left: -150px; margin-top:-150px; border: #D6D6D6 1px solid; background-color:#FFFFFF; filter: alpha(opacity=90); opacity: .90; display:inline;">
			</div>
			</td>
		</tr>
		<tr bgcolor='#9e97e1'>
			<td nowrap colspan='3' align='left'>
			<?
				if(strlen($conta_pagar)>0){
					$baixar_display=	"style='display:inline;'";
				}else{
					$baixar_display=	"style='display:none;'";
				}
			?>
			</td>
		</tr>
		<tr bgcolor='#ced7e7'>
			<td nowrap align='center'>
				<font size='2'>VALOR</font>
			</td>
			<td nowrap align='center'>
				<table width='100%' border='0'>
				<tr >
					<td align = 'center'><font size='2'>VENCIMENTO</font></td>
					<td align = 'center'>
						<?	// data da baixa eh o campo: pagamento
							echo "<label $baixar_display id='lab_pagamento'><font size='2'>DATA DA BAIXA</font></label>";
						?>
					</td>
				</tr>
				</table>						
			</td>
			<td nowrap align='center'>
				<?
				echo "<label $baixar_display id='lab_valor_pago'><font size='2'>VALOR PAGO</font></label>";
				?>
			</td>
		</tr>
		<tr bgcolor='#fafafa'>
			<td nowrap align='center'>
				<input type='text' name='valor' id='valor' value="" size='12' maxlength='30' class="frm"> 
			</td>
			<td nowrap align='center'>
				<table width='100%' border='0'>
				<tr>
					<td  align = 'center'>
						<input type='text' id='vencimento' name='vencimento' value="" size='12' maxlength='30' class="frm"> 
					</td>
					<td  align = 'center'>
						<input type='text' id='pagamento' name='pagamento'  <? echo $baixar_display;?> value="" size='12' maxlength='30' class="frm"> 
					</td>
				</tr>
				</table>
			</td>
			<td nowrap align='center'>
			<?
				echo "<input $baixar_display type='text' name='valor_pago' id='valor_pago' value='' size='12' maxlength='30' class='frm'>";
			?>
			</td>
		</tr>
		<tr bgcolor='#ced7e7'>
			<td nowrap colspan='4' align='center'>
				<font size='3'>OBSERVAÇÃO</font>
			</td>
		</tr>
		<tr bgcolor='#fafafa'>
			<td nowrap colspan='3' align='center'>
				<TEXTAREA type='text' id='obs' COLS='60' ROWS='3' NAME="obs" value=''></TEXTAREA>
			</td>
		</tr>
		<tr>
			<td nowrap colspan='3' align='center'>
				<?
				$bt_baixar_display=	"style='display:none;'";
				$bt_cad_display=	"style='display:none;'";

				if(strlen($conta_pagar)>0){
					$bt_baixar_display=	"style='display:inline;'";
				}else{
					$bt_cad_display=	"style='display:inline;'";
				}

				echo "<INPUT TYPE='button' $bt_baixar_display  name='bt_alt'    id='bt_alt'    value='Alterar' onClick=\"Exibir2('dados','','','alterar')\">";
				echo "<INPUT TYPE='button' $bt_baixar_display  name='bt_baixar' id='bt_baixar' value='Baixar'  onClick=\"Exibir2('dados','','','baixar')\">";
				echo "<INPUT TYPE='button' $bt_cad_display     name='bt_cad'    id='bt_cad'    value='Cadastrar' onClick=\"Exibir2('dados','','','insert')\">";
				echo "<INPUT TYPE='button' name='bt_limpar' id='bt_limpar' value='Limpar'    onClick=\"limpar()\">";
				//echo "<INPUT TYPE='button' name='bt_li' id='bt_lim' value='teste' onClick=\"exibeFornec(1);\">";
			?>
			</td>
		</tr>
	  </table>
	</td>
  </tr>
</table>
<br>

<?echo "<br>
		<DIV class='exibe' id='dados' value='1' align='center'>\n
			<font size='1'>Por favor aguarde um momento, carregando os dados...</font>\n
				<br>
				<img src='imagens/carregar_os.gif'>\n
			</DIV>\n";

echo "<script language='javascript'>Exibir2('dados','','','exibir');</script>\n";
?>
</form>
</BODY>
</HTML>