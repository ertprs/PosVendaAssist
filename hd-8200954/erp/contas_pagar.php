<?
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Pragma: no-cache"); // HTTP/1.0

include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
//include 'autentica_usuario_empresa.php';

//PARA BLOQUEAR
//$nosso_ip = include("../nosso_ip.php");
//if ($ip <> $nosso_ip) { header ("Location: index.php"); exit(); }

$msg_erro = '';

if(strlen($_POST["btn_acao"]) > 0 AND $_POST["btn_acao"]=='BAIXAR_LOTE') {
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
				$msg_erro .= pg_errormessage($con);
			}
		}
	}
}
?>
<?
$title = "Contas a Pagar";

//include 'cabecalho_fabio.php';
include 'menu.php';
//ACESSO RESTRITO AO USUARIO MASTER 
if (strpos ($login_privilegios,'financeiro') === false AND strpos ($login_privilegios,'*') === false ) {
		echo "<script>"; 
			echo "window.location.href = 'menu_inicial.php?msg_erro=Você não tem permissão para acessar a tela.'";
		echo "</script>";
	exit;
}

?>

<script type="text/javascript" src="javascript/prototype.js"></script>
<script type="text/javascript" src="javascript/autocomplete.js"></script>



<script type="text/javascript" src='ajax.js'></script>

<link rel="stylesheet" type="text/css" href="javascript/autocomplete.css" /> 
<link rel="stylesheet" href="css/thickbox.css" type="text/css" media="screen" />


<style type="text/css">
.Conteudo2 {
		font:12px "Segoe UI", Tahoma;	
}
h3 {
	font-size:16px;
	font-weight:bold;
}

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
.Titulo2 {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: bold;
	color: #FFFFFF;
	background-color:#6C87B7;
	border: 0px;
}
.Titulo3{
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #000000;
	background-color:#ABBAD6;
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

#boleto .topo{
	font-size:10px;
/*	float:left;
	position:relative;
	font-size:10px; */
}
#boleto .campo{
	font-size:14px;
	font-weight:bold;
	text-align:right;
	float:right;
}
#boleto .campoL{
	font-size:14px;
	font-weight:bold;
}

.bloqueiado {
	border-color:#FFFFFF;
	background-color:#FFFFFF;
	color:#000000;
	font-size:12px;
	font-weight:bold;
}

input {
	BORDER-RIGHT: #888888 1px solid; 
	BORDER-TOP: #888888 1px solid; 
	FONT-WEIGHT: bold; 
	FONT-SIZE: 8pt; 
	BORDER-LEFT: #888888 1px solid; 
	BORDER-BOTTOM: #888888 1px solid; 
	FONT-FAMILY: Verdana, Arial, Helvetica, sans-serif; 
	BACKGROUND-COLOR: #f0f0f0
}
.check_normal{
	border:none;
}
tr.linha td {
	border-bottom: 1px solid #c0c0c0; 
	border-top: none; 
	border-right: none; 
	border-left: none; 
}

</style>

<script language='javascript'>

//SELECIONA O FATURAMENTO RELACIONADO COM O FORNECEDOR
function selecionar(a){
	var nf =document.getElementById('nf').value;
	var doc=document.getElementById('documento').value;
	document.getElementById('doc_final').innerHTML= "<b>"+nf+"-"+doc+"</b>";
}


function duplo(d){
	//alert();
	document.getElementById('fatDes').fucus;
	document.getElementById('fatDes').dblclick;
}


function checarNumero(campo){
	var num = campo.value.replace(",",".");
	campo.value = parseFloat(num).toFixed(2);
	if (campo.value=='NaN') {
		campo.value='';
	}
}

function desmostra(id){
	document.getElementById(id).style.display='none';
	if (id=='bt_baixar') document.getElementById('bt_baixar').value='Baixar';
	if (id=='bt_cad')    document.getElementById('bt_cad').value='Lançar novo pagamento';
	if (id=='bt_alt')    document.getElementById('bt_alt').value='Alterar';
}

function mostra(id){
	document.getElementById(id).style.display='inline';
}

function aletar_boleto(botao){
	if (botao.value=='Alterar'){
		botao.value='Gravar Alteração';
		desbloqueia_campos('frm_pagar');
//		desmostra('bt_baixar');
//		desmostra('bt_limpar');
//		mostra('bt_cancelar');
		botoes_original('alterar');
		return false;
	}
	else{
		bloqueia_campos('frm_pagar');
		return true;
	}
}

function teste_cadastrar(botao){
	if (botao.value=='Lançar novo pagamento'){
		botao.value='Gravar Cadastro';
		desbloqueia_campos('frm_pagar');
		desmostra('bt_baixar');
		desmostra('bt_limpar');
		mostra('bt_cancelar');
		botoes_original('cadastrar');
		limpar();
		return false;
	}
	else{
		if (document.getElementById('documento').value==''){ alert('Informe o número do documento!');return false;}
		if (document.getElementById('documento').value==''){ alert('Informe o número do documento!');return false;}
		if (document.getElementById('fornID').value==''){ alert('Informe o Sacado!');return false;}
		if (document.getElementById('fornName').value==''){ alert('Informe o Sacado!');return false;}
		if (document.getElementById('valor').value==''){ alert('Informe o valor!');return false;}
		if (document.getElementById('vencimento').value==''){ alert('Informe o vencimento!');return false;}


		return true;
	}
}
function teste_baixar(botao){
	if (botao.value=='Baixar'){
		botao.value='Confimar Baixa';
//		desbloqueia_campos('frm_pagar');
//		desmostra('bt_baixar');
//		desmostra('bt_limpar');
//		mostra('bt_cancelar');
		botoes_original('baixar');
		return false;
	}
	else{
		return true;
	}
}


function botoes_original(acao){
	if (acao=='normal'){
		desmostra('bt_baixar');
		desmostra('bt_limpar');
		desmostra('bt_cancelar');
		desmostra('bt_alt');
		desmostra('bt_cad');
		mostra('bt_cad');

		desmostra('valor_pago');
		desmostra('lab_valor_pago');
		desmostra('pagamento');
		desmostra('lab_pagamento');

	}
	if (acao=='ver'){
		desmostra('bt_cad');
		mostra('bt_limpar');
		mostra('bt_alt');
		mostra('bt_baixar');
		desmostra('bt_cancelar');

		desmostra('valor_pago');
		desmostra('lab_valor_pago');
		desmostra('pagamento');
		desmostra('lab_pagamento');
	}

	if (acao=='cadastrar'){
		mostra('bt_cad');
		desmostra('bt_limpar');
		desmostra('bt_alt');
		desmostra('bt_baixar');
		mostra('bt_cancelar');
	}

	if (acao=='alterar'){
		desmostra('bt_cad');
		desmostra('bt_limpar');
		mostra('bt_alt');
		desmostra('bt_baixar');
		mostra('bt_cancelar');
	}

	if (acao=='baixar'){
		desmostra('bt_cad');
		desmostra('bt_alt');
		desmostra('bt_limpar');
		mostra('bt_cancelar');
		mostra('bt_baixar');

		mostra('valor_pago');
		mostra('lab_valor_pago');
		mostra('pagamento');
		mostra('lab_pagamento');

		document.getElementById('pagamento').className='frm';
		document.getElementById('valor_pago').className='frm';

		document.getElementById('pagamento').disabled=false;
		document.getElementById('valor_pago').disabled=false;

		document.getElementById('pagamento').readOnly=false;
		document.getElementById('valor_pago').readOnly=false;
	}


}

function bloqueia_campos(formu){
	eval("var formu = document."+formu+";");
	if (formu){
		for( var i = 0 ; i < formu.length; i++ ){
			if (formu.elements[i].type=='text' || formu.elements[i].type=='textarea'){
				formu.elements[i].className='bloqueiado';
				formu.elements[i].readOnly=true;
			}
			if (formu.elements[i].type=='radio'){
				formu.elements[i].className='bloqueiado';
				formu.elements[i].disabled=true;
			}
			if (formu.elements[i].type=='checkbox'){
				formu.elements[i].className='bloqueiado';
				formu.elements[i].disabled=true;
			}
			if (formu.elements[i].type=='select-one'){
				formu.elements[i].className='bloqueiado';
				formu.elements[i].disabled=true;
			}
		}
	}
}
function desbloqueia_campos(formu){
	eval("var formu = document."+formu+";");
	for( var i = 0 ; i < formu.length; i++ ){
		if (formu.elements[i].type=='text' || formu.elements[i].type=='textarea'){
			formu.elements[i].className='frm';
			formu.elements[i].readOnly=false;
		}
		if (formu.elements[i].type=='radio'){
			formu.elements[i].disabled=false;
		}
		if (formu.elements[i].type=='checkbox'){
			formu.elements[i].disabled=false;
		}
		if (formu.elements[i].type=='select-one'){
			formu.elements[i].disabled=false;
		}
	}
}

function retornaExibe2(http,componente, acao) {
	var com = document.getElementById(componente);
	if (http.readyState == 1) {

		com.innerHTML = "&nbsp;&nbsp;Carregando...&nbsp;&nbsp;<br><img src='imagens/carregar_os.gif' >";
		//CHAMA A FUNCAO DE CARREGANDO(LOADING) ENQUANTO NAO EXISTIR O RETORNO
		if(acao=='exibir'){
			bloqueia_campos('frm_pagar');

		}else{
			carregaMsg(50,300,50, 1);
//			carregaMsg(70,400,200, 1);
		}		
	}
	if (http.readyState == 4) {
		if (http.status == 200) {
			//alert(http.responseText);
			var results = http.responseText;
			results = results.split('|');
			if (typeof (results[0]) != 'undefined') {
				
				if (results[0] == 'ok') {
					com.innerHTML   = results[1] ; //retorna a "LISTA DE CONTAS A PAGAR"
					document.getElementById('msg').innerHTML= results[2]; //retorna a mensagem de sucesso ou de erro
					if(acao=='exibir'){
						botoes_original('normal');
					}else{
						limparForm('frm_posto');
						limparForm('frm_pagar');
						botoes_original('normal');
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

	var conta_pg	= document.getElementById('conta_pagar').value;
	var documento	= document.getElementById('nf').value + '-'+ document.getElementById('documento').value;
	var valor		= document.getElementById('valor').value;
	var fornID		= document.getElementById('fornID').value;
	var vencimento	= document.getElementById('vencimento').value;
	var obs			= document.getElementById('obs').value;
	var fatID		= document.getElementById('faturamentoID').value;
	var valor_pago	= document.getElementById('valor_pago').value;
	var pagamento	= document.getElementById('pagamento').value; //data da baixa


	var multa_p				= document.getElementById('multa_p').value;
	var multa_valor			= document.getElementById('multa_valor').value;
	var juros_mora_p		= document.getElementById('juros_mora_p').value;
	var juros_mora_valor	= document.getElementById('juros_mora_valor').value;
	var desconto			= document.getElementById('desconto').value;
	var desconto_p			= document.getElementById('desconto_p').value;
	var desconto_pontualidade= document.getElementById('desconto_pontualidade').checked;
	var protestar			= document.getElementById('protestar').value;
	var valor_custas_cartorio= document.getElementById('valor_custas_cartorio').value;
	var valor_reajustado	= document.getElementById('valor_reajustado').value;


	msg.style.display='inline';

	if(acao=='insert'){
		url = "contas_pagar_retorno_cadastro_ajax.php?ajax=sim&acao="+escape(acao)+"&documento="+escape(documento)+"&valor="+ escape(valor)+"&fornID="+escape(fornID)+"&vencimento="+vencimento+"&obs="+obs+"&fatID="+fatID+"&multa_p="+multa_p+"&multa_valor="+multa_valor+"&juros_mora_p="+juros_mora_p+"&juros_mora_valor="+juros_mora_valor+"&desconto="+desconto+"&desconto_p="+desconto_p+"&protestar="+protestar+"&desconto_pontualidade="+desconto_pontualidade+"&valor_custas_cartorio="+valor_custas_cartorio;
	}else{
		if(acao=='baixar'){

			valor = valor.replace(",",".");
			valor_pago = valor_pago.replace(",",".");

			valor = valor.replace(",",".");
			valor_pago = valor_pago.replace(",",".");
		
			if (isNaN(valor_pago)) { alert('Valor pago inválido!');msg.style.display='none'; return 0}; 
			if (valor_pago=='') { alert('Informe o valor!');msg.style.display='none'; return 0}; 
		
		
			if (parseFloat(valor_reajustado) > parseFloat(valor_pago)){
				if (confirm('O valor calculado para pagar hoje é de R$ '+valor_reajustado+'\nO valor que você informou para pagar é de R$ '+valor_pago+'\n\nO valor é inferior ao que o sistema calculou.\n\nDeseja quitar este pagamento?')){
					url = "contas_pagar_retorno_cadastro_ajax.php?ajax=sim&acao="+escape(acao)+"&conta_pagar="+conta_pg+"&valor_pago="+escape(valor_pago)+"&obs="+(obs)+"&pagamento="+(pagamento)+"&quitar=sim";
				}else{
					if (confirm('Você optou por não quitar o pagamento.\n\nDeseja quitar este pagamento e inserir um outro pagamento com o valor restante?')){
						url = "contas_pagar_retorno_cadastro_ajax.php?ajax=sim&acao="+escape(acao)+"&conta_pagar="+conta_pg+"&valor_pago="+escape(valor_pago)+"&obs="+(obs)+"&pagamento="+(pagamento)+"&quitar=nao&dividir=sim";
						
					}
					else{
						alert('Operação de baixa cancelado');
						document.getElementById('msg').innerHTML= '&nbsp;';
						setTimeout('exibeMsg(50, 300, 50)',1000);
						msg.style.display='none';
						return false;
					}
				}
			}
			else{
				if (parseFloat(valor_reajustado) < parseFloat(valor_pago)){
					alert('Atenção, o valor que o sistema calculou para o pagamento hoje é inferior ao valor que você vai pagar.');
					alert('Valor Calculado: R$ '+valor_reajustado);
					document.getElementById('msg').innerHTML= '&nbsp;';
					setTimeout('exibeMsg(50, 300, 50)',1000);
					msg.style.display='none';
					return false;
				}else{
					if (confirm('Deseja efetuar a baixa deste documento?')){
						url = "contas_pagar_retorno_cadastro_ajax.php?ajax=sim&acao="+escape(acao)+"&conta_pagar="+conta_pg+"&valor_pago="+escape(valor_pago)+"&obs="+(obs)+"&pagamento="+(pagamento)+"&quitar=nao&dividir=sim";
						
					}else{
						document.getElementById('msg').innerHTML= '&nbsp;';
						setTimeout('exibeMsg(50, 300, 50)',1000);
						msg.style.display='none';
						return false;
					}
				}
			}

		}else{
			if(acao=='alterar'){
				desbloqueia_campos('frm_pagar');
				msg.style.display='inline';
				url = "contas_pagar_retorno_cadastro_ajax.php?ajax=sim&acao="+escape(acao)+"&documento="+documento+"&fornID="+fornID+"&valor="+valor+"&vencimento="+vencimento+"&pagamento="+pagamento+"&valor_pago="+escape(valor_pago)+"&conta_pagar="+conta_pg+"&obs="+obs+"&multa_p="+multa_p+"&multa_valor="+multa_valor+"&juros_mora_p="+juros_mora_p+"&juros_mora_valor="+juros_mora_valor+"&desconto="+desconto+"&desconto_p="+desconto_p+"&protestar="+protestar+"&desconto_pontualidade="+desconto_pontualidade+"&valor_custas_cartorio="+valor_custas_cartorio+"&fatID="+fatID;


			}else{
				msg.style.display = 'none';
				url = "contas_pagar_retorno_cadastro_ajax.php?ajax=sim&programa2="+escape(solicita) ;
			}
		}
	}
	http.open("GET", url , true);
	http.onreadystatechange = function () { retornaExibe2 (http,componente, acao) ; } ;
	http.send(null);
}

//FUNÇAO USADA PARA CARREGAR UMA CONTA_PAGAR DA LISTA DE PENDENTES
function retornaPagar(http,componente) {
	var doc		= document.getElementById('documento');
	var forn	= document.getElementById('fornID');
	var vlr		= document.getElementById('valor');

	if (http.readyState == 1) {
//		carregaMsg(70,400,200, 1);
		carregaMsg(50,300,50, 1);
	}
	if (http.readyState == 4) {
		if (http.status == 200) {
//			alert(http.responseText);
			var results = http.responseText.split("|");
			if (typeof (results[0]) != 'undefined') {
				if (results[0] == 'ok') {
					document.getElementById('nf').value = results[1];// nota fiscal
					document.getElementById('documento').value = results[2];

					document.getElementById('doc_final').innerHTML= "<font color='black'><b>"+results[1]+"-"+results[2]+"</b></font>";

					document.getElementById('fornID').value		= results[3];
					document.getElementById('fornName').value	= results[4];
					document.getElementById('valor').value		= results[5];
					document.getElementById('vencimento').value	= results[6];
					document.getElementById('obs').value		= results[7];
					document.getElementById('conta_pagar').value= results[8];

					document.getElementById('multa_valor').value= results[9];
					document.getElementById('juros_mora_valor').value = results[10];
					document.getElementById('desconto').value	= results[11];
					document.getElementById('descontos_abatimentos').innerHTML = results[11];

					if (results[12]=='t'){
						document.getElementById('desconto_pontualidade').checked=true;
					}else{
						document.getElementById('desconto_pontualidade').checked=false;
					}

					if (results[13]>10)results[13]=10;
					if (results[13]<0) results[13]=0;
					document.getElementById('protestar').selectedIndex = results[13];

					document.getElementById('valor_custas_cartorio').value = results[14];

					document.getElementById('valor_reajustado').value = results[15];
					document.getElementById('valor_cobrado').innerHTML = results[15];
					

					document.getElementById('data_digitacao').innerHTML = ' '+results[16];
					document.getElementById('data_digitacao2').innerHTML = ' '+results[16];

					document.getElementById('mora_multa').innerHTML = results[17];

					carregaMsg(50,300,50, 2);

					//document.getElementById('bt_cad').style.display = 'none';
					//document.getElementById('bt_baixar').style.display = 'inline';
					//document.getElementById('bt_alt').style.display = 'inline';
					//document.getElementById('bt_limpar').style.display = 'inline';

					//document.getElementById('valor_pago').style.display = 'inline';
					//document.getElementById('lab_valor_pago').style.display = 'inline';
					//document.getElementById('pagamento').style.display = 'inline';
					//document.getElementById('lab_pagamento').style.display = 'inline';

					botoes_original('ver');
					bloqueia_campos('frm_pagar')

				}else{
					alert("Ocorreu um erro ao carregar");
				}
			}else{
				alert ('Fechamento nao processado');
			}
		}
	}
}

function exibirPagar(componente,conta_pagar, documento, acao) {
	var solicita	= document.getElementById('documento').value;
	var documento	= document.getElementById('documento').value;
	var valor		= document.getElementById('valor').value;
	var fornID		= document.getElementById('fornID').value;
	var vencimento	= document.getElementById('vencimento').value;
	var obs			= document.getElementById('obs').value;
	url = "contas_pagar_retorno_cadastro_ajax.php?ajax=mostra&acao="+escape(acao)+"&conta_pagar="+escape(conta_pagar);
	http.open("GET", url , true);
	http.onreadystatechange = function () { retornaPagar (http,componente) ; } ;
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
			var results = http.responseText.split("|");
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
		url = "contas_pagar_retorno_cadastro_ajax.php?ajax=sim&acao="+escape(acao)+"&documento="+escape(documento)+"&valor="+ escape(valor)+"&fornID="+escape(fornID)+"&vencimento="+vencimento+"&obs="+obs+"&fatID="+fatID;
	}else{
		msg.style.display = 'none';
		url = "contas_pagar_retorno_cadastro_ajax.php?ajax=sim&programa2="+escape(solicita) ;
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
	eval("var formu = document."+id+";");
			for( var i = 0 ; i < formu.length; i++ ){
				if (formu.elements[i].type=='text'){
					formu.elements[i].value="";
				}
				if (formu.elements[i].type=='select-one'){
					formu.elements[i].selectedIndex=0;
				}
				if (formu.elements[i].type=='checkbox'){
					formu.elements[i].checked=false;
				}
			}
		
	botoes_original('normal');
	bloqueia_campos('frm_pagar');


	document.getElementById('mora_multa').innerHTML = "&nbsp;";
	document.getElementById('valor_cobrado').innerHTML = "&nbsp;";
	document.getElementById('data_digitacao').innerHTML = "&nbsp;";
	document.getElementById('descontos_abatimentos').innerHTML = "&nbsp;";
	document.getElementById('data_digitacao2').innerHTML = "&nbsp;";
	document.getElementById('data_digitacao').innerHTML = "&nbsp;";
	document.getElementById('doc_final').innerHTML = "&nbsp;";
	document.getElementById('valor_pago_tela').innerHTML = "&nbsp;";
}
function exibeFornec(acao){
	if (acao=='0'){
		limparForm('frm_posto');
		document.getElementById('f1').style.display = 'none';
	}
	if (acao=='1'){
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
		msg.style.left   = (45+'%');
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

function selecionarLinha(id,cor){
	var com = document.getElementById('pagar'+id);
	var lin = document.getElementById('linha_'+id);
	if (com){
		if (com.checked==true){
			com.checked=false;
			lin.bgColor = cor;
		}
		else{
			com.checked=true;
			lin.bgColor = "#D3E9FE";
		}
	}
	calcula_total_selecionado(999);
}

// LIMPA O FORMA E MOSTRA O BOTAO CADASTRAR
function limpar(){
	document.getElementById('msg').innerHTML="";
	document.getElementById('msg').style.display		= 'none';
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
//	document.getElementById('bt_cad').style.display			= 'inline';
//	document.getElementById('bt_baixar').style.display		= 'none';
//	document.getElementById('bt_alt').style.display			= 'none';
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
					exibeFornec('0');
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

<br><br>

<div name='blabla' id='f1' style='padding:10px; background-color:#ffffff; border-color:#cccccc; border:1px solid #bbbbbb; display:none; width:650px; height:350px; margin-left:-325px; margin-top:20px; position:absolute;'>

<form name="frm_posto" id="frm_posto" method="post" action="<? echo $PHP_SELF ?>">
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
	</tr>
	<tr class="table_line" align='center'>
		<td><input type="text" name="cnpj" id="cnpj" size="15" maxlength="20" value="<? echo $cnpj ?>"></td>
		<td><input type="text" name="ie" id="ie" size="20" maxlength="20" value="<? echo $ie ?>"></td>
		<td><input type="text" name="fone" id="fone" size="10" maxlength="20" value="<? echo $fone ?>"></td>
		<td><input type="text" name="fax" id="fax" size="10" maxlength="20" value="<? echo $fax ?>"></td>
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
	</tr>
	<tr class="table_line" align='center'>
		<td>
			<input type="text" name="nome_fantasia" size="30" maxlength="40" value="<? echo $nome_fantasia ?>" >
		</td>
		<td>
			<input type="text" name="email" size="30" maxlength="50" value="<? echo $email ?>">
		</td>
	</tr>
</table>
<center>
<INPUT TYPE='button' name='bt_cad_forn' id='bt_cad_forn' value='Gravar' onClick="if (this.value=='Gravando...'){ alert('Aguarde');}else {this.value='Gravando...'; gravar_fonecedor(this.form);}">
<INPUT TYPE='button' name='bt_fecha_forn' id='bt_fecha_forn' value='Fechar' onClick="exibeFornec('0')">
</center>
</form>
</div>


<FORM METHOD=POST ACTION="<? $PHP_SELF ?>" NAME='frm_pagar'  id='frm_pagar'>
<input type='hidden' id='conta_pagar' name='conta_pagar' value='<? echo $conta_pagar; ?>'>
<table id='boleto' border="1" cellspacing="0" width="700" align='center' style="border-collapse: collapse; border: 1px solid #000000;">

 <tr>
  <td colspan="2" width="472">
	<strong><big>Boleto / Documento</big></strong>
  </td>

  <td colspan='5'>
  			
  </td>
 </tr>

 <tr>
  <td colspan="6" width="472">
	<span class='topo'>Local de Pagamento</span><br>
	<span class='campoL'>Pagável no local que consta no Boleto</span>
  </td>
  <td width="168">
	<span class='topo'>Vencimento</span><br>
	<span class='campo'>
		<input type='text' id='vencimento' name='vencimento' value="" size='12' maxlength='10' class="frm"> 
	</span>
 </td>
 </tr>

 <tr>
  <td width="472" colspan="6">
	<span class='topo'>Cedente</span><br>
				<span class='campoL'>
				<input id="fornID" name="fornID" type="hidden" value=''>
				<input type="text" id="fornName" name="fornName" value='' size="40" class='frm' >
				<script type="text/javascript">
					new CAPXOUS.AutoComplete("fornName", function() {
						return "contas_pagar_retorna_forn_ajax.php?typing=" + this.text.value;
					});
				</script>
				

			</span>

  </td>
  <td width="168">
	<span class='topo'>Agência/Código Cedente</span><br>
	<span class='campo'>&nbsp;</span>
  </td>
 </tr>
 <tr>
  <td width="95">
	<span class='topo'>Data Documento</span><br>
	<span class='campo'>&nbsp;<span id='data_digitacao'></span>
  </td>
  <td width="134" colspan="2">
	<span class='topo'>Documento/Nota Fiscal</span><br>
	<span class='campo'>
				<input type="text" id="nf" name="nf" value='' size="15" maxlength='7' class='frm'  onkeyup="selecionar(event);">
				
				<script type="text/javascript">
					new CAPXOUS.AutoComplete("nf", function() {

						return "contas_pagar_retorna_nf_ajax.php?fornID=" + document.getElementById('fornID').value +"&typing=" + this.text.value;
					});
				</script>	
				
	</span>
  </td>
  <td width="80">
	<span class='topo'>Boleto</span><br>
	<span class='campo'>
			<input type='text' name='documento' id='documento' value='' size='8' maxlength='20' class="frm" onkeyup="selecionar(event);"> 	
	</span>
  </td>
  <td width="38">
	<span class='topo'>Aceite</span><br>
	<span class='campo'>&nbsp;</span>
  </td>
  <td width="109">
	<span class='topo'>Data Processamento</span><br>
	<span class='campo'>&nbsp;<span id='data_digitacao2'></span></span>
  </td>
  <td width="168">
	<span class='topo'>Nosso Número</span><br>
	<span class='campo'>
		<div id='doc_final' style='padding:4px; background-color:#ffffff; width:200px; height:20px;'></div>
	</span>
  </td>
 </tr>
 <tr>
  <td width="95">
	<span class='topo'>Uso do Banco</span><br>
	<span class='campo'>&nbsp;</span>
  </td>
  <td width="85">
	<span class='topo'>Carteira</span><BR>
	<span class='campo'></span>
  </td>
  <td width="29">
	<span class='topo'>Espécie</span><br>
	<span class='campo'>R$</span>
  </td>
  <td width="90" colspan="2">
	<span class='topo'>Quantidade</span><br>
	<span class='campo'>&nbsp;</span>
  </td>
  <td width="115">
	<span class='topo'>(x) Valor</span><br>
	<span class='campo'>&nbsp;</span>
  </td>
  <td width="168">
	<span class='topo'>(=) Valor do Documento</span><br>
	<span class='campo'>
		<input type='text' name='valor' id='valor' value="" size='12' maxlength='30' class="frm" onblur="javascript:checarNumero(this)"> 
	</span>
  </td>
 </tr>
 <tr>
  <td width="472" colspan="6" rowspan="6" valign="top">
	<span class='topo'>Instruções (texto de responsabilidade do cedente)</span><br>


<table cellspacing="5" cellpadding='5' style='font-size:10px'>
<tr>
<td valign='top'>
	<div style='font-weight:normal;border-bottom:1px solid #D8D8D8;width:100%'>Multa</div>
	<label name='tipo_multa'>
	<input type='radio' name='tipo_multa' onclick="document.getElementById('multa_valor').disabled=true; document.getElementById('multa_p').disabled=false" >  %  &nbsp;</label>
	<input type='text' name='multa_p' id='multa_p' value="" size='6' class="frm" maxlength='6' onkeyup="javascript: document.getElementById('multa_valor').value=this.value.replace(',','.') * document.getElementById('valor').value.replace(',','.') /100; "  disabled>
	<br>
	<label name='tipo_multa'>
	<input type='radio' name='tipo_multa' onclick="document.getElementById('multa_valor').disabled=false; document.getElementById('multa_p').disabled=true" checked> R$ </label><input type='text' name='multa_valor' id='multa_valor' value="" size='6' maxlength='20' class="frm" onblur="javascript:checarNumero(this)"> 
	<!--  <br><i style='font-size:9px;color:gray'>Pagamento após o vencimento</i> -->
</td>

<td valign='top'>
	<div style='font-weight:normal;border-bottom:1px solid #D8D8D8;width:100%'>Juros Mora ao Dia</div>
	<label name='tipo_juros'>
	<input type='radio' name='tipo_juros' onclick="document.getElementById('juros_mora_p').disabled=false; document.getElementById('juros_mora_valor').disabled=true" > %  &nbsp;</label>
	<input type='text' id='juros_mora_p' name='juros_mora_p' value="" size='6' maxlength='6' class="frm" onkeyup="javascript: document.getElementById('juros_mora_valor').value=this.value.replace(',','.') * document.getElementById('valor').value.replace(',','.') /100; " disabled>
	<br>
	<label name='tipo_juros'>
	<input type='radio' name='tipo_juros' onclick="document.getElementById('juros_mora_valor').disabled=false; document.getElementById('juros_mora_p').disabled=true" checked> R$ </label><input type='text' id='juros_mora_valor' name='juros_mora_valor' value="" size='6' maxlength='20' class="frm" onblur="javascript:checarNumero(this)">
	<!--  <br><i style='font-size:9px;color:gray'>Pagamento após o vencimento</i> -->
</td>

<td valign='top'>
	<div style='font-weight:normal;border-bottom:1px solid #D8D8D8;width:100%'>Desconto</div>
	<label name='tipo_desconto'>
	</label>
	<label name='tipo_desconto'>
	<input type='radio' name='tipo_desconto' onclick="document.getElementById('desconto').disabled=true; document.getElementById('desconto_p').disabled=false" > %  &nbsp;</label><input type='text' id='desconto_p' name='desconto_p' value="" size='6' maxlength='6' class="frm" onkeyup="javascript: document.getElementById('desconto').value=this.value.replace(',','.') * document.getElementById('valor').value.replace(',','.') /100; " disabled>

	<br>
	<label name='tipo_desconto'>
	<input type='radio' name='tipo_desconto' onclick="document.getElementById('desconto_p').disabled=true; document.getElementById('desconto').disabled=false" checked> R$ </label><input type='text' id='desconto' name='desconto' value="" size='6' maxlength='20' class="frm" onblur="javascript:checarNumero(this)"><br>
	<input type='checkbox' name='desconto_pontualidade' value='t' id='desconto_pontualidade'><b style='font-size:10px;font-weight:normal' >Desconto Pontualidade</b>
	<!-- <br><i style='font-size:9px;color:gray'>Pgto antes do vencimento</i>  -->
</td>

</tr>

<tr>
<td>
	<div style='font-weight:normal;border-bottom:1px solid #D8D8D8;width:100%'>Protesto</div>
				<select name='protestar' id='protestar' onchange="javascript:if(this.value==0) { document.getElementById('valor_custas_cartorio').value=''; document.getElementById('valor_custas_cartorio').disabled=true;} else{document.getElementById('valor_custas_cartorio').disabled=false;}">
					<option value='0' selected>-</option>
					<option value='1'>1 dias</option>
					<option value='2'>2 dias</option>
					<option value='3'>3 dias</option>
					<option value='4'>4 dias</option>
					<option value='5'>5 dias</option>
					<option value='6'>6 dias</option>
					<option value='7'>7 dias</option>
					<option value='8'>8 dias</option>
					<option value='9'>9 dias</option>
					<option value='10'>10 dias</option>
				</select>
</td>
<td>
	<div style='font-weight:normal;border-bottom:1px solid #D8D8D8;width:100%'>Custos Cartório</div>
				R$  <input type='text' id='valor_custas_cartorio' name='valor_custas_cartorio' value="" size='10' maxlength='20' class="frm" onblur="javascript:checarNumero(this)">
</td>
<td>
</td>
</tr>
<tr>
<td colspan='3'>
				Observações<br>
				<TEXTAREA type='text' id='obs' COLS='60' ROWS='3' NAME="obs" value=''></TEXTAREA>
</td>
</tr>
</table>


  </td>
  <td width="168">
	<span class='topo'>(-) Descontos/Abatimentos</span><br>
	<span class='campo' id='descontos_abatimentos'>&nbsp;</span>
  </td>
 </tr>
 <tr>
  <td width="168">
	<span class='topo'>(-) Outras Deduções</span><br>
	<span class='campo'>&nbsp;</span>
  </td>
 </tr>
 <tr>
  <td width="168">
	<span class='topo'>(+) Mora/Multa</span><br>
	<span class='campo'  id='mora_multa'>&nbsp;</span>
  </td>
 </tr>
 <tr>
  <td width="168">
	<span class='topo'>(+) Outros Acréscimos</span><br>
	<span class='campo'>&nbsp;</span>
  </td>
 </tr>
 <tr>
  <td width="168">
	<span class='topo'>(=) Valor Cobrado</span><br>
	<span class='campo' id='valor_cobrado'>&nbsp;</span>
  </td>
 </tr>
 <tr>
  <td width="168">
	<span class='topo'>(=) Valor Pago</span><br>
	<span class='campo' id='valor_pago_tela'>&nbsp;</span>
  </td>
 </tr>

 <tr>
  <td width="640" colspan="7">

<input type='hidden' id='faturamentoID' value='' >

<table border="0" cellpadding="0" cellspacing="0" width="100%">
   <tr>
    <td width="8%" valign='top'><span class='topo'>Sacado</span></td>
    <td width="28%" colspan="2">
	<span class='campoL'><? echo $login_loja_nome ?></span>
	</td>
    <td width="34%" colspan="2"><span class='campoL'>-</span></td>
   </tr>
   <tr>
    <td width="3%"></td>
    <td width="28%" colspan="2"><span class='campoL'></span></td>
    <td width="22%"><span class='campoL'></span></td>
    <td width="32%"></td>
   </tr>
   <tr>
    <td width="2%"></td>
    <td width="10%"><span class='campoL'></span></td>
    <td width="38%"><span class='campoL'></span></td>
    <td width="22%"><span class='campoL'></span></td>
    <td width="30%" nowrap></td>
   </tr>
   <tr>
    <td width="1%" colspan="2"><span class='topo'></span></td>
    <td width="38%"></td>
    <td width="22%"></td>
    <td width="32%"><span class='topo'></span></td>
   </tr>
  </table>

  </td>
 </tr>
</table>

	<?
				$bt_baixar_display=	"style='display:none;'";
				$bt_cad_display=	"style='display:none;'";

				if(strlen($conta_pagar)>0){
					$bt_baixar_display=	"style='display:inline;'";
				}else{
					$bt_cad_display=	"style='display:none;'";
				}

				echo "<br><center>";
				echo "<div style='text-align:left;padding:3px 8px;width:700px;border:1px solid gray'><b>Ações:</b>";


					if(strlen($conta_pagar)>0){
						$baixar_display=	"style='display:inline;'";
					}else{
						$baixar_display=	"style='display:none;'";
					}

				echo "<label $baixar_display id='lab_pagamento'>";
				echo "&nbsp;<b>Data da Baixa</b></label>";
				echo "&nbsp;<input type='text' id='pagamento' name='pagamento'  $baixar_display value='' size='11' maxlength='10' class='frm'>";

				echo "<label $baixar_display id='lab_valor_pago'>";
				echo "&nbsp;&nbsp;<b>Valor Pago&nbsp;</b></label>";
				echo "<input $baixar_display type='text' name='valor_pago' id='valor_pago' value='' size='8' maxlength='20' class='frm' onblur=\"javascript:checarNumero(this)\">";
				echo "<input type='hidden' name='valor_reajustado' id='valor_reajustado' value=''>";

				echo "<INPUT TYPE='button' $bt_baixar_display  name='bt_alt'    id='bt_alt'    value='Alterar' onClick=\"if (aletar_boleto(this))Exibir2('dados','','','alterar')\">";
				echo "&nbsp;&nbsp;";
				echo "<INPUT TYPE='button' $bt_baixar_display  name='bt_baixar' id='bt_baixar' value='Baixar'  onClick=\"if (teste_baixar(this))Exibir2('dados','','','baixar')\">";
				echo "&nbsp;&nbsp;";
				echo "<INPUT TYPE='button' $bt_cad_display     name='bt_cad'    id='bt_cad'    value='Lançar novo pagamento' onClick=\"if (teste_cadastrar(this))Exibir2('dados','','','insert')\">";
				echo "&nbsp;&nbsp;";
				echo "<INPUT TYPE='button' $bt_cad_display name='bt_limpar' id='bt_limpar' value='Limpar'    onClick=\"limparForm('frm_pagar');limpar();\">";
				echo "&nbsp;&nbsp;";
				echo "<INPUT TYPE='button' $bt_cad_display name='bt_cancelar' id='bt_cancelar' value='Cancelar'    onClick=\"limparForm('frm_pagar');limpar();\">";

				echo "</div>";
				echo "</center>";
				//echo "<INPUT TYPE='button' name='bt_li' id='bt_lim' value='teste' onClick=\"exibeFornec('1');\">";
			?>


</form>


				<div name='msg_fat' id='msg_fat' style='padding:10px; overflow:auto;scrolling:auto; background-color:#ffffff; filter: alpha(opacity=60); opacity: .70 border-color:#3333dd; border:1px solid #3333dd; display:none; width:100px; height:60px; position:absolute;'></div>
		
				<div name='f2' id='f2' style='padding:4px; background-color:#ffffff; filter: alpha(opacity=70); opacity: .70 border-color:#cccccc; border:1px solid #bbbbbb; display:none; width:120px; height:80px; position:absolute;'></div>
			
				
				<div id="msg" style="width: 300px; height:50px; position: absolute; top: 50%; left: 50%; margin-left: -150px; margin-top:-150px; border: #D6D6D6 1px solid; background-color:#FFFFFF; display:inline;"></div>
		


<?echo "<br><br>
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