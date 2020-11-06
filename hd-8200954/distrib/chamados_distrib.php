<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

// $abrir =fopen("/www/assist/www/nosso_ip.txt", "r");
//  $teste=fread($abrir, filesize("/www/assist/www/nosso_ip.txt"));

/*$teste = include ("../nosso_ip.php");

if ($ip!=trim($teste)  ){
	header("Location: index.php");
}*/

function converte_data($date)
{
	$date = explode("-", str_replace('/', '-', $date));
	$date2 = ''.$date[2].'/'.$date[1].'/'.$date[0];
	if (sizeof($date)==3)
		return $date2;
	else return false;
}

if (isset($_GET['pegar']) AND strlen($_GET['pegar'])>0){
	header("Content-Type: text/html; charset=ISO-8859-1",true);
	if ($_GET['pegar']=='nota' AND strlen(trim($_GET['valor']))>0 AND strlen(trim($_GET['posto']))>0){
		$valor  = trim($_GET['valor']);
		$posto  = trim($_GET['posto']);
		$fabrica= trim($_GET['fabrica']);
		if(strlen($fabrica) == 0){
			$fabrica = "10";
		}
		$sql="SELECT faturamento,
					TO_CHAR(emissao,'DD/MM/YYYY')  AS emissao
				FROM tbl_faturamento
				JOIN tbl_posto_fabrica USING(posto)
				WHERE tbl_faturamento.nota_fiscal='$valor'
				AND (tbl_faturamento.fabrica = $fabrica OR tbl_faturamento.fabrica = 10)
				AND tbl_posto_fabrica.codigo_posto='$posto'";
		$res = pg_exec ($con,$sql);
		if (@pg_numrows($res) > 0) {
			$faturamento      = trim(pg_result($res,0,faturamento));
			$emissao      = trim(pg_result($res,0,emissao));
			echo "Nota: <a href='../nf_detalhe_britania_fabio_chamado.php?faturamento=$faturamento' target='_blank'>$valor</a>|$emissao";
		}
		else{
			echo "error";
		}
	}
	else{
		echo "error";
	}
	exit;
}


## GRAVAR DADOS
if (isset($_POST['posto_codigo']) AND strlen($_POST['posto_codigo'])>0){
	$fabrica		= trim($_POST['fabrica']);
	$posto_codigo	= trim($_POST['posto_codigo']);
	$posto_nome		= trim($_POST['posto_nome']);
	$data_chamado	= @converte_data(trim($_POST['data_chamado']));
	$nota_fiscal	= trim($_POST['nota_fiscal']);
	$data_emissao	= @converte_data(trim($_POST['data_emissao']));
	$gerar_credito	= trim($_POST['gerarCredito']);
	$valor_credito	= trim($_POST['valor_credito']);
	$valor_credito	= str_replace (",",".",$valor_credito);
	$observacao		= trim($_POST['observacao']);

	if(strlen($fabrica) == 0){
		$fabrica = "10";
	}

	$msg_erro .= (strlen($posto_codigo)==0)?"Informe o código do posto":"";
	$msg_erro .= (strlen($posto_nome)==0)?"Informe o nome do posto":"";
	$msg_erro .= (strlen($data_chamado)==0)?"Informe a data do chamado":"";
	$msg_erro .= (strlen($nota_fiscal)==0)?"Informe nota fiscal":"";
	$msg_erro .= (strlen($data_emissao)==0)?"Informe a data de emissão":"";

	$valor_credito = (strlen($valor_credito)==0)?0:$valor_credito;


	$posto=="";
	if (strlen($posto_codigo)>0){
		$sql_posto="SELECT posto
				FROM tbl_posto
					JOIN tbl_posto_fabrica USING(posto)
				WHERE codigo_posto='$posto_codigo'
					AND fabrica=$fabrica";
		$res_posto = pg_exec ($con,$sql_posto);
		if (@pg_numrows($res_posto) > 0) {
			$posto      = trim(pg_result($res_posto,0,posto));
		}
	}
	if (strlen($posto)==0){
		$msg_erro .="Posto não encontrado!";
	}

	if (strlen($msg_erro)==0){
		$res = @pg_exec($con,"BEGIN TRANSACTION");

		$sql="INSERT INTO tbl_distrib_chamado
				(	fabrica,
					posto,
					data_chamado,
					nota_fiscal,
					data_emissao,
					gerar_credito,
					valor_credito,
					observacao
				)
				VALUES (
					$fabrica,
					$posto,
					'$data_chamado',
					'$nota_fiscal',
					'$data_emissao',
					'$gerar_credito',
					$valor_credito,
					'$observacao'
			)";
		$res = pg_exec ($con,$sql);
		$msg_erro .= pg_errormessage($con);
		if (strlen($msg_erro)==0){
			$sql = "SELECT CURRVAL ('seq_distrib_chamado')";
			$resZ = pg_exec ($con,$sql);
			$chamado = pg_result ($resZ,0,0);
		}

		if (strlen($msg_erro)==0){
			for($i=1;$i<10;$i++){
				$peca		=$_POST["peca_$i"];
				$referencia	=$_POST["peca_ref_$i"];
				$descricao	=$_POST["peca_descricao_$i"];
				$qtde		=$_POST["qtde_$i"];
				$ocorrencia	=$_POST["ocorrencia_$i"];

				if (strlen($referencia)==0 OR strlen($qtde)==0 OR strlen($ocorrencia)==0 ){
					continue;
				}

				if (strlen($peca)==0){
					$sql_peca= "SELECT peca
								FROM tbl_peca
								WHERE referencia = '$referencia'
								AND fabrica		 = $fabrica";
					$res_peca = pg_exec ($con,$sql_peca);
					if (@pg_numrows($res_peca) > 0) {
						$peca      = trim(pg_result($res_peca,0,peca));
					}
				}

				if (strlen($peca)==0){
					$msg_erro .="Peça $referencia não encontrada!";
					break;
				}

				if (strlen($msg_erro)==0){
					$sql="INSERT INTO tbl_distrib_chamado_item
									(distrib_chamado,peca,quantidade,ocorrencia)
							VALUES ($chamado,$peca,$qtde,'$ocorrencia')";
					$res = pg_exec ($con,$sql);
					$msg_erro .= pg_errormessage($con);
				}
				if (strlen($msg_erro)>0){
					break;
				}
			}
		}
		if (strlen($msg_erro)==0){
			$res = @pg_exec ($con,"COMMIT TRANSACTION");
			$msg_erro .= pg_errormessage($con);
			if (strlen($msg_erro)==0){
				header("Location: chamados_distrib_imprimir?chamado=$chamado");
				exit;
			}else{
				$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
			}
		}
		else {
			$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
		}
	}
	$data_chamado	=@converte_data($data_chamado);
	$data_emissao	=@converte_data($data_emissao);
}


?>

<html>
<head>
<title>Controle de Chamados</title>
<link type="text/css" rel="stylesheet" href="css/css.css">

<style type="text/css">
.Tabela{
	border:1px solid #596D9B;
	background-color:#596D9B;
}
.Erro{
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	color:#CC3300;
	font-weight: bold;
	background-color:#FFFFFF;
}
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
.inpu{
	border:1px solid #666;
	font-size:9px;
	height:12px;
}
.botao2{
	border:1px solid #666;
	font-size:9px;
}
.butt{
	border:1px solid #666;
	background-color:#ccc;
	font-size:9px;
	height:16px;
}
.nomes{
	font-family: "Verdana, Arial, Helvetica, sans-serif";
	font-size:11px;
	font-weight:normal;
}
.frm {
	BORDER: 1px solid #888888;
	FONT-WEIGHT: bold;
	FONT-SIZE: 9pt;
	FONT-FAMILY: "Verdana, Arial, Helvetica, sans-serif";
	BACKGROUND-COLOR: #f0f0f0
}
.input {
	BORDER: 1px solid #888888;
	FONT-WEIGHT: bold;
	FONT-SIZE: 9pt;
	FONT-FAMILY: "Verdana, Arial, Helvetica, sans-serif";
	BACKGROUND-COLOR: #f0f0f0
}
.loading
{
	font-size:12px;
	FONT-FAMILY: "Verdana, Arial, Helvetica, sans-serif";
	padding:5px;
}
.loaded
{
	font-size:12px;
	FONT-FAMILY: "Verdana, Arial, Helvetica, sans-serif";
	padding:5px;
}

</style>
</head>

<body>

<? include 'menu.php' ?>

<script language='javascript'>

var ok = false;
function checkaTodos() {
	f = document.frm_estoque_lista;
	if (!ok) {
		for (i=0; i<f.length; i++){
			if (f.elements[i].type == "checkbox"){
				f.elements[i].checked = true;
				ok=true;
			}
		}
	}else{
		for (i=0; i<f.length; i++) {
			if (f.elements[i].type == "checkbox"){
				f.elements[i].checked = false;
				ok=false;
			}
		}
	}
}

function fnc_pesquisa_posto(campo, campo2, tipo, fabrica) {
	if (tipo == "codigo" ) {
		var xcampo = campo;
	}

	if (tipo == "nome" ) {
		var xcampo = campo2;
	}


	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa_distrib.php?campo=" + xcampo.value + "&tipo=" + tipo + "&fabrica=" + fabrica.value ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.codigo  = campo;
		janela.nome    = campo2;
		janela.focus();
	}
}




function addRowToTable(){
	var tbl = document.getElementById('tbl_pecas');
	var lastRow = tbl.rows.length;
	// if there's no header row in the table, then iteration = lastRow + 1
	var iteration = lastRow;
	var row = tbl.insertRow(lastRow);


	// NOME DO FILHO
	var cellRight1 = row.insertCell(0);

	var el = document.createElement('input');
	el.setAttribute('type', 'hidden');
	el.setAttribute('name', 'peca_' + iteration);
	el.setAttribute('id', 'peca_' + iteration);
	cellRight1.appendChild(el);

	var el = document.createElement('input');
	cellRight1.setAttribute('align', 'left');
	el.setAttribute('class', 'frm');
	el.setAttribute('type', 'text');
	el.setAttribute('name', 'peca_ref_' + iteration);
	el.setAttribute('id', 'peca_ref_' + iteration);
	//el.setAttribute('maxlength', '50');
	el.setAttribute('size', '15');
	el.setAttribute('alt', 2);
	//el.onkeypress=function(){handleEnterPeca(event,'referencia',this)};
	cellRight1.appendChild(el);


	var elim = document.createElement('img');
	elim.setAttribute('align', 'absmiddle');
	elim.setAttribute('alt', iteration);
	elim.setAttribute('src', '../imagens/btn_lupa.gif');
	elim.setAttribute('border', '0');
	elim.setAttribute('alt', iteration);
	elim.onclick=function(){fnc_pesquisa_peca(this,'referencia')};
	cellRight1.appendChild(elim);

	var cellRight1 = row.insertCell(1);
	var el = document.createElement('input');
	cellRight1.setAttribute('align', 'left');
	el.setAttribute('class', 'frm');
	el.setAttribute('type', 'text');
	el.setAttribute('name', 'peca_descricao_' + iteration);
	el.setAttribute('id', 'peca_descricao_' + iteration);
	//el.setAttribute('maxlength', '50');
	el.setAttribute('size', '25');
	el.setAttribute('alt', iteration);
	//el.onkeypress=function(){handleEnterPeca(event,'descricao',this)};
	cellRight1.appendChild(el);

	var elim = document.createElement('img');
	elim.setAttribute('align', 'absmiddle');
	elim.setAttribute('src', '../imagens/btn_lupa.gif');
	elim.setAttribute('border', '0');
	elim.setAttribute('alt', iteration);
	elim.onclick=function(){fnc_pesquisa_peca(this,'descricao')};
	//elim.setAttribute('onclick', 'javascript: )');
	cellRight1.appendChild(elim);

	// NOME DO FILHO
	var cellRight1 = row.insertCell(2);
	var el = document.createElement('input');
	cellRight1.setAttribute('align', 'left');
	el.setAttribute('class', 'frm');
	el.setAttribute('type', 'text');
	el.setAttribute('name', 'qtde_' + iteration);
	el.setAttribute('id', 'qtde_' + iteration);
	//el.setAttribute('maxlength', '50');
	el.setAttribute('size', '3');
	cellRight1.appendChild(el);

	// NOME DO FILHO
	var cellRight1 = row.insertCell(3);
	var el = document.createElement('input');
	cellRight1.setAttribute('align', 'right');
	el.setAttribute('class', 'frm');
	el.setAttribute('type', 'text');
	el.setAttribute('name', 'preco_' + iteration);
	el.setAttribute('id', 'preco_' + iteration);
	//el.setAttribute('maxlength', '50');
	el.setAttribute('size', '4');
	cellRight1.appendChild(el);

	// NOME DO FILHO
	var cellRight1 = row.insertCell(4);
	var el = document.createElement("select");
	cellRight1.setAttribute('align', 'left');
	el.setAttribute('class', 'frm');
	el.setAttribute('name', 'ocorrencia_' + iteration);
	el.setAttribute('id', 'ocorrencia_' + iteration);
	el.setAttribute('title', iteration);
	el.setAttribute('size', '1');
	el.onchange=function() { if (this.value!="") adicionaLinha(this.title)}

	var oOption0 = document.createElement("option");
	var oOption1 = document.createElement("option");
	var oOption2 = document.createElement("option");
	var oOption3 = document.createElement("option");
	var oOption4 = document.createElement("option");
	var oOption5 = document.createElement("option");
	var oOption6 = document.createElement("option");
	var oOption7 = document.createElement("option");
	var oOption8 = document.createElement("option");
	var oOption9 = document.createElement("option");

	var t = document.createTextNode("Ocorrência..");
	oOption0.appendChild(t);
	oOption0.setAttribute("value", "");

	var t = document.createTextNode("Queimada");
	oOption1.appendChild(t);
	oOption1.setAttribute("value", "Queimada");

	var t = document.createTextNode("Quebrada");
	oOption2.appendChild(t);
	oOption2.setAttribute("value", "Quebrada");

	var t = document.createTextNode("Enviada Errada");
	oOption3.appendChild(t);
	oOption3.setAttribute("value", "Enviada Errada");

	var t = document.createTextNode("Riscada");
	oOption4.appendChild(t);
	oOption4.setAttribute("value", "Riscada");

	var t = document.createTextNode("Trincada");
	oOption5.appendChild(t);
	oOption5.setAttribute("value", "Trincada");

	var t = document.createTextNode("Voltagem Errada");
	oOption6.appendChild(t);
	oOption6.setAttribute("value", "Voltagem Errada");

	var t = document.createTextNode("Pedido Errado");
	oOption7.appendChild(t);
	oOption7.setAttribute("value", "Pedido Errado");

	var t = document.createTextNode("Peça Incompleta");
	oOption8.appendChild(t);
	oOption8.setAttribute("value", "Peça Incompleta");

	var t = document.createTextNode("Não Enviado");
	oOption9.appendChild(t);
	oOption9.setAttribute("value", "Não Enviado");

	el.appendChild(oOption0);
	el.appendChild(oOption1);
	el.appendChild(oOption2);
	el.appendChild(oOption3);
	el.appendChild(oOption4);
	el.appendChild(oOption5);
	el.appendChild(oOption6);
	el.appendChild(oOption7);
	el.appendChild(oOption8);
	el.appendChild(oOption9);


	cellRight1.appendChild(el);

	var tmp=document.getElementById("peca_ref_"+iteration);
	if (tmp){
		tmp.focus();
	}
}

function removeRowFromTable()
{
	var tbl = document.getElementById('tbl_pecas');
	var lastRow = tbl.rows.length;
	if (lastRow > 2) tbl.deleteRow(lastRow - 1);
}


function adicionaLinha(linha){

	var tbl = document.getElementById('tbl_pecas');
	var lastRow = tbl.rows.length;


	if (linha!=lastRow-1) return false;

/*	var tmp1 = document.getElementById("peca_ref_"+linha).value;
	var tmp2 = document.getElementById("qtde_"+linha).value;

	if (tmp1=="") return false;
	if (tmp2=="") return false*/;

// 	alert('Linha '+linha);
// 	alert('Last '+lastRow);

	addRowToTable()
}

function fnc_pesquisa_peca (id, tipo) {
	if (id.value=='') return;

	var url = "";
	var ide=id.alt;

	var var1    =document.getElementById("peca_"+ide);
	var var2    =document.getElementById("peca_ref_"+ide);
	var var3    =document.getElementById("peca_descricao_"+ide);
	var var4    =document.getElementById("preco_"+ide);
	var var5    =document.getElementById("qtde_"+ide);
	var fabrica =document.getElementById("fabrica");

	url = "peca_pesquisa_fabio.php?campo1=" +var2.value+"&campo2=" +var3.value+"&tipo="+tipo+"&fabrica="+fabrica.value ;

	//if (var2.value.length > 2 || var3.value.length > 2) {
		janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=501, height=400, top=18, left=0");
		janela.peca		= var1;
		janela.referencia	= var2;
		janela.descricao		= var3;
		janela.preco		= var4;
		janela.qtde			= var5;
		janela.focus();
	//}else{
		//alert("Digite pelo menos 3 caracteres!");
	//}
}

/* ############################################################## */
var Ajax = new Object();

Ajax.Request = function(url,callbackMethod){

	Page.getPageCenterX();
	Ajax.request = Ajax.createRequestObject();
	Ajax.request.onreadystatechange = callbackMethod;
	Ajax.request.open("POST", url, true);
	Ajax.request.send(url);
}

Ajax.Response = function (){
	if(Ajax.CheckReadyState(Ajax.request))	{
		var	response2 = Ajax.request.responseText;
		var temp= document.getElementById('nota_fiscal_link');

		if (response2=="error"){
			document.getElementById('loading').innerHTML ="";
			document.getElementById('loading').innerHTML = "<table border=0 cellpadding=0 cellspacing=1 width=200 bgcolor=gray><tr><td align=center class=loaded height=45 bgcolor=#ffffff style='color:red;font-weigth:bold'>Nota Fiscal Não Encontrada!!</td></tr></table>";
			setTimeout('Page.loadOut()',3000);
			//document.getElementById('nota_fiscal_link').innerHTML = "Nenhuma nota encontrada!";
			temp.innerHTML = "&nbsp;";
		}
		else{
			response = response2.split('|');
			temp.innerHTML = response[0];
			var temp2= document.getElementById('data_emissao');
			temp2.value = response[1];
			//temp2.readonly = "readonly";

		}
	}
}

Ajax.createRequestObject = function(){
	var obj;
	if(window.XMLHttpRequest)	{
		obj = new XMLHttpRequest();
	}
	else if(window.ActiveXObject)	{
		obj = new ActiveXObject("MSXML2.XMLHTTP");
	}
	return obj;
}

Ajax.CheckReadyState = function(obj){
	if(obj.readyState < 4) {
		document.getElementById('loading').style.top = (Page.top + Page.height/2)-100;
		document.getElementById('loading').style.left = Page.width/2-75;
		document.getElementById('loading').style.position = "absolute";
		document.getElementById('loading').innerHTML = "<table border=0 cellpadding=0 cellspacing=1 width=200 bgcolor=#AAA><tr><td align=center class=loading height=45 bgcolor=#FFFFFF>Aguarde.....<br><br><img src='../imagens/carregar_os' ></td></tr></table>";
	}
	if(obj.readyState == 4)	{
		if(obj.status == 200){
			document.getElementById('loading').innerHTML = "<table border=0 cellpadding=0 cellspacing=1 width=200 bgcolor=gray><tr><td align=center class=loaded height=45 bgcolor=#ffffff>Informações carregadas com sucesso!</td></tr></table>";
			setTimeout('Page.loadOut()',1000);
			return true;
		}
		else{
			document.getElementById('loading').innerHTML = "HTTP " + obj.status;
		}
	}
}

var Page = new Object();
Page.width;
Page.height;
Page.top;

Page.loadOut = function (){
	document.getElementById('loading').innerHTML ='';
}
Page.getPageCenterX = function (){
	var fWidth;
	var fHeight;
	//For old IE browsers
	if(document.all) {
		fWidth = document.body.clientWidth;
		fHeight = document.body.clientHeight;
	}
	//For DOM1 browsers
	else if(document.getElementById &&!document.all){
			fWidth = innerWidth;
			fHeight = innerHeight;
		}
		else if(document.getElementById) {
				fWidth = innerWidth;
				fHeight = innerHeight;
			}
			//For Opera
			else if (is.op) {
					fWidth = innerWidth;
					fHeight = innerHeight;
				}
				//For old Netscape
				else if (document.layers) {
						fWidth = window.innerWidth;
						fHeight = window.innerHeight;
					}
	Page.width = fWidth;
	Page.height = fHeight;
	Page.top = window.document.body.scrollTop;
}
/* ############################################################## */

function handleEnter(evento,onde) {

  var keyCode= (evento.keyCode?evento.keyCode:evento.charCode);
       //var chr= String.fromCharCode(evt);

 if (keyCode == 9){
	fnc_pesquisa_posto (document.frm_consulta.posto_codigo,document.frm_consulta.posto_nome,onde);
}
 else	 keyCode;

}
function handleTAB(evento,onde) {


 //var keyCode = evento.keyCode ? evento.keyCode : evento.which ? evento.which : evento.charCode;
var keyCode = (evento.keyCode?evento.keyCode:evento.charCode);
 //var chr= String.fromCharCode(evt);

 if (keyCode == 9){
	if (document.frm_consulta.nota_fiscal.value=='') return;
	Ajax.Request('<?=$PHP_SELF?>?pegar=nota&valor='+document.frm_consulta.nota_fiscal.value+'&posto='+document.frm_consulta.posto_codigo.value+'&fabrica='+document.frm_consulta.fabrica.value, Ajax.Response);
}
 else	 keyCode;

}

function handleEnterPeca(evento,onde,campo) {

	//var keyCode = evento.keyCode ? evento.keyCode : evento.which ? evento.which : evento.charCode;
	var keyCode = (evento.keyCode?evento.keyCode:evento.charCode);
	//var chr= String.fromCharCode(evt);
	if (keyCode == 9){
		fnc_pesquisa_peca (campo,onde);
	}
	else	 keyCode;

}

function calcula_credito(){
	var total=0

	var tbl = document.getElementById('tbl_pecas');
	var lastRow = tbl.rows.length;

	for (i=1;i<lastRow;i++){
		var tmp_valor = document.getElementById("preco_"+i).value;
		var tmp_qtde = document.getElementById("qtde_"+i).value;
		if (tmp_qtde>0 && tmp_valor>0)
			total += tmp_valor * tmp_qtde;

	}
	document.getElementById("valor_credito").value = total.toFixed(2);
}

</script>

<center><h1>Entrada de Chamados</h1></center>

<p>
<div id='loading'></div>
<center>


<?php

if(strlen($msg_erro)>0){
	echo "<br><table class='Tabela' width='700' cellspacing='0'  cellpadding='0' align='center'>";
	echo "<tr >";
	echo "<td bgcolor='FFFFFF' width='60'class='Erro'><img src='../imagens/proibido2.jpg' align='middle'></td><td  class='Erro' bgcolor='FFFFFF' align='left'> $msg_erro</td>";
	echo "</tr>";
	echo "</table><br>";
}

if(strlen($msg)>0){
	echo "<br><center><b style='font-size:12px;border:1px solid #999;padding:10px;background-color:#dfdfdf'>$msg</b></center><br>";
}

echo "<center><a href='chamados_consulta.php'><h2 style='padding:3px;text-align:center;font-size:13px;color:white;background-color:#0099CC;width:330px;cursor:hand'>>> Listar Todos Chamados << </h2></a></center>\n";
?>

<form name='frm_consulta' method='post' action='<? echo $PHP_SELF ?>'>

	<p align='center'>Escolha a fábrica
		<select name="fabrica" id='fabrica'>
			<option value='3'>Britânia</option>
			<option value='51'>Gama Italy</option>
<?	/*	Adicionada Fábrica BestWay. MLG 08/02/2010	*/	?>
			<option value='81'>BestWay</option>
		</select>
	</p>


	<table width="650px" border="0" cellspacing="5" cellpadding="0" >

	<tr>

		<td nowrap>
			<b class='nomes'>Código do Posto</b>
			<br>
			<input class="frm" type="text" name="posto_codigo" size="15" value="<? echo $posto_codigo ?>" onkeypress="handleEnter(event,'codigo')">&nbsp;<img src='../imagens/btn_buscar5.gif' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_posto (document.frm_consulta.posto_codigo,document.frm_consulta.posto_nome,'codigo',document.frm_consulta.fabrica)">
		</td>

		<td nowrap>
			<b class='nomes'>Nome do Posto</b>
			<br>
			<input class="frm" type="text" name="posto_nome" size="40" value="<? echo $posto_nome ?>" onKeyPress="handleEnter(event,'nome')">&nbsp;<img src='../imagens/btn_buscar5.gif' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto (document.frm_consulta.posto_codigo,document.frm_consulta.posto_nome,'nome',document.frm_consulta.fabrica)" style="cursor:pointer;">
		</td>
		<td nowrap>
			<b class='nomes'>Data do Chamado</b>
			<br>
			<input class="frm" type="text" name="data_chamado" size="15" maxlength='10' value="<?php echo date("d/m/Y")?>" >
		</td>
	</tr>

	<tr>

		<td nowrap>
			<b class='nomes'>Número da NF</b>
			<br>
			<input class="frm" type="text" name="nota_fiscal" size="15" maxlength="10" onKeyPress="handleTAB(event,'nota')"  value="<? echo $nota_fiscal ?>" >
			<img src='../imagens/btn_buscar5.gif' border='0' align='absmiddle' style="cursor:pointer" onClick="javascript:Ajax.Request('<?=$PHP_SELF?>?pegar=nota&valor='+document.frm_consulta.nota_fiscal.value+'&posto='+document.frm_consulta.posto_codigo.value+'&fabrica='+document.frm_consulta.fabrica.value, Ajax.Response);"><br>
			<div id='nota_fiscal_link' style='font-size:12px'>&nbsp;</div>
		</td>

		<td nowrap>
			<b class='nomes'>Data de Emissão</b>
			<br>
			<input class="frm" type="text" name="data_emissao" id="data_emissao" size="15" maxlength="10"  value="<? echo $data_emissao ?>" >
			<br><br>
		</td>
		<td nowrap>
		</td>
	</tr>
	<tr>
		<td colspan='3'><br>
			<div style='width:650px;font-size:12px;border-bottom:2px solid #ccc;font-weight:bold;color:#333'>Peças</div>
		</td>
	</tr>
</table>

<table width="650px" border="0" cellspacing="5" cellpadding="0" id='tbl_pecas'>
	<tr>
		<td nowrap>
			<b class='nomes'>Referência</b>
		</td>

		<td nowrap>
			<b class='nomes'>Descrição</b>
		</td>

		<td nowrap>
			<b class='nomes'>Qtde</b>
		</td>
		<td nowrap>
			<b class='nomes'>Preço</b>
		</td>
		<td nowrap>
			<b class='nomes'>Ocorrência</b>
		</td>
	</tr>
	<tr>
		<td nowrap>
			<input type='hidden' name='peca_1' id='peca_1' size='15' value=''>
			<input class='frm' type='text' name='peca_ref_1' id='peca_ref_1' size='15' value='' onKeyPress="handleEnterPeca(event,'referencia',this)" alt='1'>
			<img src='../imagens/btn_lupa.gif' border='0' align='absmiddle' alt='1' onclick="javascript: fnc_pesquisa_peca(this,'referencia')" alt='1' style='cursor:pointer;' >
		</td>
		<td nowrap>
			<input class='frm' type='text' name='peca_descricao_1' id='peca_descricao_1' size='25' value='' onKeyPress="handleEnterPeca(event,'descricao',this)" alt='1'>
			<img src='../imagens/btn_lupa.gif' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_peca(this,'descricao')" alt='1' style='cursor:pointer;' >
		</td>
		<td nowrap>
			<input class='frm' type='text' name='qtde_1' id='qtde_1' size='3' value=''>
		</td>
		<td nowrap>
			<input class='frm' type='text' name='preco_1' id='preco_1' size='4' value=''>
		</td>
		<td nowrap>
			<select name="ocorrencia_1" id='ocorrencia_1' size="1" class="frm" onchange='javascript: if (this.value!="") adicionaLinha(1)'>
				<option value='' selected>Ocorrência..</option>
				<option value='Queimada'>Queimada</option>
				<option value='Quebrada'>Quebrada</option>
				<option value='Enviada errada'>Enviada errada</option>
				<option value='Riscada'>Riscada</option>
				<option value='Trincada'>Trincada</option>
				<option value='Voltagem Errada'>Voltagem Errada</option>
				<option value='Pedido Errado'>Pedido Errado</option>
				<option value='Peça Incompleta'>Peça Incompleta</option>
				<option value='Peça Incompleta'>Não Enviado</option>
			</select>
		</td>
	</tr>
</table>
<table width="650px" border="0" cellspacing="5" cellpadding="0">
	<tr >
		<td nowrap colspan='2'><br>
			<div style='width:650px;font-size:12px;border-bottom:2px solid #ccc;font-weight:bold;color:#333;algin:center'></div>
		</td>
	</tr>
	<tr>
		<td nowrap  class='nomes'>Coletar PAC
		<label name='gerarCredito' style='padding-left:30px'>Sim</label> <input type='radio' name='gerarCredito' value='t'>
	<!-- onclick="javascript:if (this.checked) { document.frm_consulta.valor_credito.disabled=false; calcula_credito(); }else { document.frm_consulta.valor_credito.disabled=true;document.frm_consulta.valor_credito.value=''; }" -->
		<label name='gerarCredito' style='padding-left:30px'>Não</label> <input type='radio' name='gerarCredito' value='f'>
	<!-- checked onclick="javascript:if (this.checked) { document.frm_consulta.valor_credito.disabled=true;document.frm_consulta.valor_credito.value=''} else document.frm_consulta.valor_credito.disabled=false;" -->
		</td>
		<td  nowrap class='nomes'><label>Valor do Crédito:</label> <input class='frm' type='text' name='valor_credito' id='valor_credito'  size='10' value='<? echo $valor_credito ?>' maxlength="10">
		</td>
	</tr>
	<tr>
		<td colspan='2' align='center' class='nomes'><br>Observação<br>
				<textarea cols=50 rows=5 name="observacao" class='frm'><? echo $observacao ?></textarea>
		</td>
	</tr>
	<tr>
		<td colspan='2' align='center' class='nomes'><br><br>


			<img alt='' src='../imagens/btn_gravar.gif' onclick="javascript: if (this.alt== '' ) { this.alt='gravar' ; document.frm_consulta.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar itens da Ordem de Serviço" border='0' style="cursor:pointer;">
		</td>
	</tr>
</table>
</form>
</center>

</body>
</html>

<html>
<head>
<title>Untitled Document</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
</head>
<script>
/*
 Autor = Marcos Regis;
 Data = Fev 2005;
 Assunto = Controlar Teclas;
 email = marcos_regis@hotmail.com;
 Pode ser usado e alterado desde que mantidos estes
 comentários em respeite ao autor.
 Descrição =
 A função recebe um evento e um parâmetro que permite caracteres
adicionais indicados no evento onKeyPress.
*/

   function soNums(e,args)
   {
   // Função que permite apenas teclas numéricas e
   // todos os caracteres que estiverem na lista
   // de argumentos.
   // Deve ser chamada no evento onKeyPress desta forma
   //  onKeyPress ="return (soNums(event,'0'));"
   // caso queira apenas permitir caracters como por exemplo um campo que só aceite valores em Hexadecimal (de 0 a F) usamos
   //  onKeyPress ="return (soNums(event,'AaBbCcDdEeFf'));"

/* Esta parte comentada é a que testei exaustivamente e garanto que funciona em praticamente todos os browsers
       var evt='';// devido a um warning gerado pelo Console de Javascript que "enxergava" uma redeclaração de "evt" decidi declará-la uma vez e alterar ser valor posteriormente

       if (document.all){evt=event.keyCode;} // caso seja IE
       else{evt = e.charCode;}    // do contrário deve ser Mozilla
O código a seguir teste apenas em FireFox e Internet Explorer 6 e funcionou perfeitamente. Caso vc tenha algum problema com esta função por favor entre em contato
*/
       var evt= (e.keyCode?e.keyCode:e.charCode);
       var chr= String.fromCharCode(evt);    // pegando a tecla digitada
       // Se o código for menor que 20 é porque deve ser caracteres de controle
       // ex.: <ENTER>, <TAB>, <BACKSPACE> portanto devemos permitir
       // as teclas numéricas vão de 48 a 57
       return (evt <20 || (evt >47 && evt<58) || (args.indexOf(chr)>-1 ) );
   }

/*
   outra variação só que mais rígida. Não permite nenhum caracter que não esteja na lista de permissão.
   Aconselhável para algumas situações como por exemplo testes de digitação ou coisas do tipo. Também deve ser chamado da forma anterior
   //  onKeyPress ="return (soNums(event,'0'));"

*/
   function soNums(e,args)
   {

/* Esta parte comentada é a que testei exaustivamente e garanto que funciona em praticamente todos os browsers
       var evt='';// devido a um warning gerado pelo Console de Javascript que "enxergava" uma redeclaração de "evt" decidi declará-la uma vez e alterar ser valor posteriormente
       if (document.all){evt=event.keyCode;} // caso seja IE
       else{evt = e.charCode;}    // do contrário deve ser Mozilla
O código a seguir teste apenas em FireFox e Internet Explorer 6 e funcionou perfeitamente. Caso vc tenha algum problema com esta função por favor entre em contato
*/
       var evt= (e.keyCode?e.keyCode:e.charCode);
       var valid_chars = '0123456789'+args;    // criando a lista de teclas permitidas
       var chr= String.fromCharCode(evt);    // pegando a tecla digitada
       if (valid_chars.indexOf(chr)>-1 ){return true;}    // se a tecla estiver na lista de permissão permite-a
       // para permitir teclas como <BACKSPACE> adicionamos uma permissão para
       // códigos de tecla menores que 09 por exemplo (geralmente uso menores que 20)
       return (valid_chars.indexOf(chr)>-1 || evt < 9);    // se a tecla estiver na lista de permissão permite-a
       // do contrário nega
   }
</script>
<body>
<form name="form1" method="post" action="">
 <input type="text" name="textfield" onKeyPress ="return (soNums(event,'0'));">
</form>
</body>
</html>
