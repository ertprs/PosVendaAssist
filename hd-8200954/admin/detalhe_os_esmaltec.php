<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="financeiro";
include 'autentica_admin.php';



if (strlen($_POST["os"]) > 0) $os = trim($_POST["os"]);
if (strlen($_GET["os"]) > 0)  $os = trim($_GET["os"]);


if (strlen($_GET["defeito"]) > 0)  $defeito = trim($_GET["defeito"]);
if (strlen($_GET["defeito"]) > 0)  $defeito = trim($_GET["defeito"]);
if (strlen($_GET["defeito_constatado_codigo"]) > 0)  $defeito_constatado_codigo = trim($_GET["defeito_constatado_codigo"]);
if (strlen($_GET["defeito_constatado_descricao"]) > 0)  $defeito_constatado_descricao = trim($_GET["defeito_constatado_descricao"]);


//pop-up de defeitos
if(strlen($os)>0 and $defeito=='defeito'){
	echo "<h2 style='font-family:Verdana'>Defeito Constatado</h2>";
	if(strlen(trim($defeito_constatado_codigo))>1 OR strlen(trim($defeito_constatado_descricao))>2){

		$sql = "SELECT  tbl_linha.linha    ,
						tbl_familia.familia
				FROM tbl_os
				JOIN tbl_produto USING(produto)
				JOIN tbl_linha   ON tbl_linha.linha     = tbl_produto.linha
				JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia
				WHERE tbl_os.fabrica = $login_fabrica
				AND   tbl_os.os      = $os";
		$res = pg_exec ($con,$sql) ;
		$produto_linha   = pg_result ($res,0,linha);
		$produto_familia = pg_result ($res,0,familia);
		if(strlen($defeito_constatado_codigo)>0)
		$sql_a1 = " AND  tbl_defeito_constatado.codigo like '%$defeito_constatado_codigo%' ";
		if(strlen($defeito_constatado_descricao)>0)
		$sql_a1 = " AND  upper(tbl_defeito_constatado.descricao) like upper('%$defeito_constatado_descricao%') ";


		$sql = "SELECT DISTINCT 
					(tbl_diagnostico.defeito_constatado),
					tbl_defeito_constatado.descricao,
					tbl_defeito_constatado.codigo
				FROM tbl_diagnostico
				JOIN tbl_defeito_constatado on tbl_diagnostico.defeito_constatado = tbl_defeito_constatado.defeito_constatado
				WHERE tbl_diagnostico.linha   = $produto_linha
				AND   tbl_diagnostico.familia = $produto_familia
				AND   tbl_diagnostico.ativo   = 't'
				$sql_a1
				ORDER BY tbl_defeito_constatado.descricao";
		$res = pg_exec ($con,$sql) ;

		if (pg_numrows($res)>0){
			echo "<table style='font-family:verdana;font-size:12px;'>";
			echo "<tr bgcolor='#336699' style='color:#FFFFFF'>";
			echo "<Th>Código</th>";
			echo "<Th>Descrição</th>";
			echo "</tr>";
			for($i=0;$i<pg_numrows($res);$i++){

				$defeito_constatado = pg_result ($res,$i,defeito_constatado);
				$codigo             = pg_result ($res,$i,codigo);
				$descricao          = pg_result ($res,$i,descricao);
				echo "<tr>";
				echo "<td><a href=\"javascript: defeito_constatado.value='$defeito_constatado';defeito_constatado_codigo.value='$codigo';defeito_constatado_descricao.value='$descricao';this.close();\">$codigo</a></td>";
				echo "<td>$descricao</td>";
				echo "</tr>";

			}
			echo "</table>";
		}else echo "<h4 style='color:#FF0000'>Nenhum defeito com o código: $defeito_constatado_codigo</h4>";
	}else echo "<h4 style='color:#FF0000'>Nenhum defeito com o código: $defeito_constatado_codigo</h4>";
	echo "<br><center><a href='javascript:this.close();'>[Fechar]</a></center>";
	exit;
}




//gravar alterações
if ($btn_acao == "gravar") {
	$res = pg_exec ($con,"BEGIN TRANSACTION");

	//Rotina de vários defeitos para uma única OS.
	if($login_fabrica==30){
		$numero_vezes = 100;
		$array_integridade = array();
		for ($i=0;$i<$numero_vezes;$i++){
			$int_constatado = trim($_POST["integridade_defeito_constatado_$i"]);
			if (!isset($_POST["integridade_defeito_constatado_$i"])) continue;
			if (strlen($int_constatado)==0) continue;

			$aux_defeito_constatado = $int_constatado;
			array_push($array_integridade,$aux_defeito_constatado);

			$sql = "SELECT defeito_constatado_reclamado
					FROM tbl_os_defeito_reclamado_constatado
					WHERE os = $os
					AND   defeito_constatado = $aux_defeito_constatado";
			$res = @pg_exec ($con,$sql);
			$msg_erro .= pg_errormessage($con);
			
			if(@pg_numrows($res)==0){
				$sql = "INSERT INTO tbl_os_defeito_reclamado_constatado(
							os,
							defeito_constatado
						)VALUES(
							$os,
							$aux_defeito_constatado
						)
				";
				$res = @pg_exec ($con,$sql);
				$msg_erro .= pg_errormessage($con);
			}
		}

		$lista_defeitos = implode($array_integridade,",");
		if(strlen($lista_defeitos)>0){
			$sql = "DELETE FROM tbl_os_defeito_reclamado_constatado 
					WHERE os = $os
					AND   defeito_constatado NOT IN ($lista_defeitos) ";
			$res = @pg_exec ($con,$sql);
			if(strlen(pg_errormessage($con))>0){
				$msg_erro .= "<br>É necessário clicar no botão Adicionar Defeito!<br>";
			}
		}else{
			$sql = "DELETE FROM tbl_os_defeito_reclamado_constatado 
					WHERE os = $os";
			$res = @pg_exec ($con,$sql);
			$msg_erro ="É necessário clicar no botão Adicionar Defeito!";
		}

		//o defeito constatado recebe o primeiro defeito constatado.
		$defeito_constatado = $aux_defeito_constatado;
	}

	if (strlen ($defeito_constatado) > 0) {
		$sql = "UPDATE tbl_os SET defeito_constatado = $defeito_constatado
				WHERE  tbl_os.os    = $os;";
		$res = @pg_exec ($con,$sql);
		$msg_erro .= pg_errormessage($con);
	}

	//chama função pois ela valida se tem defeito constatado e faz o cálculo da OS
	if (strlen ($msg_erro) == 0) {
		$sql = "SELECT fn_valida_os_item($os, $login_fabrica)";
		$res      = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
	}

	//recalcula o extrato
	if (strlen ($msg_erro) == 0) {
		$sql      = "SELECT extrato 
					 FROM tbl_os_extra 
					 WHERE os = $os
					 AND extrato IS NOT NULL";
		$res      = pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);

		if (pg_numrows($res)>0) {
			$extrato = pg_result($res,0,0);
		} else {
			$msg_erro = "Nenhum extrato não encontrado para esta OS";
		}

		if (strlen ($msg_erro) == 0) {
			$sql = "SELECT extrato
					FROM tbl_extrato
					WHERE extrato = $extrato
					AND aprovado IS NULL;";
			$res      = pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);

			if (pg_numrows($res)==0) {
				$msg_erro = "Extrato $extrato já aprovado, OS não pode ser alterada";
			}
		}

		if (strlen ($msg_erro) == 0) {
			$sql      = "SELECT fn_calcula_extrato($login_fabrica, $extrato)";
			$res      = @pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
		}
	}
	
	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

























$titulo = "Esmaltec - Ordem de Serviço";

?>

<html>
<head>
<title><?echo $titulo?></title>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #000000;
	background-color: #D9E2EF;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
.textarea{
	border-width: 1px;
	border-style: solid;
	border-color: #8c8a79;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
}
a:link {
color:#535353;
text-decoration:none;
}
a:visited {
color:#535353;
text-decoration:none;
}
a:hover {
color:#FF3333;
text-decoration:underline;
}
a:active {
color:#535353;
text-decoration:underline;
background-color:#000000;
}
</style>

<? if ($btn_acao == 'gravar') {
		echo "<script language='JavaScript'>";
		echo "if (window.opener){window.opener.location.href = window.opener.location.href;} "; 
		echo "</script>";
	}
?>

<script type="text/javascript">


function adicionaIntegridade() {
	if(document.getElementById('defeito_constatado').value==""){
		alert('Selecione o defeito constatado');
		return false;
	}

	var tbl = document.getElementById('tbl_integridade');
	var lastRow = tbl.rows.length;
	var iteration = lastRow;

	if (iteration>0){
		document.getElementById('tbl_integridade').style.display = "";
	}

	var linha = document.createElement('tr');
	linha.style.cssText = 'color: #000000; text-align: left; font-size:10px';

	// COLUNA 1 - LINHA
	var celula = criaCelula(document.getElementById('defeito_constatado_codigo').value + '-'+document.getElementById('defeito_constatado_descricao').value);
	celula.style.cssText = 'text-align: left; color: #000000;font-size:10px';

	var el = document.createElement('input');
	el.setAttribute('type', 'hidden');
	el.setAttribute('name', 'integridade_defeito_constatado_' + iteration);
	el.setAttribute('id', 'integridade_defeito_constatado_' + iteration);
	el.setAttribute('value',document.getElementById('defeito_constatado').value);
	celula.appendChild(el);

	linha.appendChild(celula);

	// coluna 6 - botacao
	var celula = document.createElement('td');
	celula.style.cssText = 'text-align: right; color: #000000;font-size:10px';

	var el = document.createElement('input');
	el.setAttribute('type', 'button');
	el.setAttribute('value','Excluir');
	el.setAttribute('style','width: 120px;');
	el.onclick=function(){removerIntegridade(this);};
	celula.appendChild(el);
	linha.appendChild(celula);

	// finaliza linha da tabela
	var tbody = document.createElement('TBODY');
	tbody.appendChild(linha);
	tbl.appendChild(tbody);
}

function criaCelula(texto) {
	var celula = document.createElement('td');
	var textoNode = document.createTextNode(texto);
	celula.appendChild(textoNode);
	return celula;
}

function removerIntegridade(iidd){
	var tbl = document.getElementById('tbl_integridade');
	tbl.deleteRow(iidd.parentNode.parentNode.rowIndex);
}

function fnc_pesquisa_dc (os, defeito_constatado, defeito_constatado_codigo, defeito_constatado_descricao) {
	var url = "";
	if (defeito_constatado != '') {
		url = "<?$PHP_SELF?>?defeito=defeito&os=" + os+"&defeito_constatado_codigo="+defeito_constatado_codigo.value+"&defeito_constatado_descricao="+defeito_constatado_descricao.value;

		janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=501, height=400, top=18, left=0");
		janela.defeito_constatado           = defeito_constatado          ;
		janela.defeito_constatado_codigo    = defeito_constatado_codigo   ;
		janela.defeito_constatado_descricao = defeito_constatado_descricao;
		janela.focus();
	}
}


function MostraEsconde(dados)
{
	if (document.getElementById)
	{
		var style2 = document.getElementById(dados);
		if (style2==false) return;
		if (style2.style.display=="block"){
			style2.style.display = "none";
			}
		else{
			style2.style.display = "block";
		}
	}
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
function escondeEstoque(){
	if (document.getElementById('div_estoque')){
		var style2 = document.getElementById('div_estoque'); 
		if (style2==false) return; 
		if (style2.style.display=="block"){
			style2.style.display = "none";
		}else{
			style2.style.display = "block";
		}
	}
}
var http3 = new Array();
function gravaAutorizao(){
	var os_item           = document.getElementById('os_item');
	var peca              = document.getElementById('peca');
	var autorizacao_texto = document.getElementById('autorizacao_texto');
	
		var curDateTime = new Date();
		http3[curDateTime] = createRequestObject();
	
		url = "detalhe_ordem_servico.php?ajax=gravar&peca="+peca.value+"&os_item="+os_item.value+"&autorizacao_texto="+autorizacao_texto.value;
		http3[curDateTime].open('get',url);
		var campo = document.getElementById('div_estoque');
		Page.getPageCenterX();
		campo.style.top = (Page.top + Page.height/2)-160;
		campo.style.left = Page.width/2-220;
	
		http3[curDateTime].onreadystatechange = function(){
			if(http3[curDateTime].readyState == 1) {
				campo.innerHTML = "<font size='1' face='verdana'>Aguarde..</font>";
			}
			if (http3[curDateTime].readyState == 4){
				if (http3[curDateTime].status == 200 || http3[curDateTime].status == 304){
	
					var results = http3[curDateTime].responseText;
					campo.innerHTML   = results;
				}else {
					campo.innerHTML = "Erro";
				}
			}
		}
		http3[curDateTime].send(null);


}


function aceitarPeca(os_item,peca){
	var div = document.getElementById('div_estoque');
	div.style.display = (div.style.display=="") ? "none" : "";
	autorizarPeca(os_item,peca);

}


function autorizarPeca(os_item,peca){

	var curDateTime = new Date();
	http3[curDateTime] = createRequestObject();

	url = "detalhe_ordem_servico.php?ajax=autoriza&peca="+peca+"&os_item="+os_item;
	http3[curDateTime].open('get',url);
	var campo = document.getElementById('div_estoque');
	Page.getPageCenterX();
	campo.style.top = (Page.top + Page.height/2)-160;
	campo.style.left = Page.width/2-220;

	http3[curDateTime].onreadystatechange = function(){
		if(http3[curDateTime].readyState == 1) {
			campo.innerHTML = "<font size='1' face='verdana'>Aguarde..</font>";
		}
		if (http3[curDateTime].readyState == 4){
			if (http3[curDateTime].status == 200 || http3[curDateTime].status == 304){

				var results = http3[curDateTime].responseText;
				campo.innerHTML   = results;
			}else {
				campo.innerHTML = "Erro";
			}
		}
	}
	http3[curDateTime].send(null);

}
function verificarEstoque(posto,peca){
	var div = document.getElementById('div_estoque');
	div.style.display = (div.style.display=="") ? "none" : "";
	mostraEstoque(posto,peca);
}
function mostraEstoque(posto,peca){

	var curDateTime = new Date();
	http3[curDateTime] = createRequestObject();

	url = "detalhe_ordem_servico.php?ajax=estoque&peca="+peca+"&posto="+posto;
	http3[curDateTime].open('get',url);
	var campo = document.getElementById('div_estoque');
	Page.getPageCenterX();
	campo.style.top = (Page.top + Page.height/2)-160;
	campo.style.left = Page.width/2-220;

	http3[curDateTime].onreadystatechange = function(){
		if(http3[curDateTime].readyState == 1) {
			campo.innerHTML = "<font size='1' face='verdana'>Aguarde..</font>";
		}
		if (http3[curDateTime].readyState == 4){
			if (http3[curDateTime].status == 200 || http3[curDateTime].status == 304){

				var results = http3[curDateTime].responseText;
				campo.innerHTML   = results;
			}else {
				campo.innerHTML = "Erro";
			}
		}
	}
	http3[curDateTime].send(null);

}
var Page = new Object();
Page.width;
Page.height;
Page.top;

Page.loadOut = function (){
	document.getElementById('div_estoque').innerHTML ='';	
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
</script>

</head>

<body>
<?
if(strlen($msg_erro) > 0){
?>
	<TABLE border='0' width='500' align='center'>
	<TR style='font-family: verdana; font-weight: bold; font-size: 14px; color:#FFFFFF' bgcolor='#FF3300'>
		<TD align='center'><? echo $msg_erro; ?></TD>
	</TR>
	</TABLE>
<?}?>

<?
if (strlen($os) > 0) {
	$sql = "SELECT  tbl_os.os                                                        ,
					tbl_os.posto                                                     ,
					tbl_os.sua_os                                                    ,
					tbl_posto.nome                                                   ,
					tbl_posto_fabrica.codigo_posto                                   ,
					tbl_posto_fabrica.tipo_posto                                     ,
					tbl_produto.referencia                        AS referencia      ,
					tbl_produto.descricao                         AS nome_equipamento,
					to_char(tbl_os.data_abertura, 'DD/MM/YYYY')   AS abertura        ,
					to_char(tbl_os.data_fechamento, 'DD/MM/YYYY') AS fechamento      ,
					to_char(tbl_os.finalizada, 'DD/MM/YYYY')      AS finalizada      ,
					tbl_os.serie                                  AS serie           ,
					tbl_os.consumidor_nome                        AS nome_cli        ,
					tbl_os.consumidor_cidade                                         ,
					tbl_os.consumidor_endereco                    AS cliente_endereco,
					tbl_os.consumidor_cep                         AS cliente_cep     ,
					tbl_os.consumidor_numero                      AS cliente_numero  ,
					tbl_os.consumidor_complemento                 AS cliente_complemento          ,
					tbl_os.consumidor_bairro                      AS cliente_bairro               ,
					tbl_os.consumidor_estado                                                      ,
					tbl_os.consumidor_fone                        AS fone                         ,
					tbl_os.nota_fiscal                                                            ,
					to_char(tbl_os.data_nf, 'DD/MM/YYYY')         AS data_nf                      ,
					tbl_os.revenda_nome                           AS loja                         ,
					tbl_os.revenda_fone                           AS loja_fone                    ,
					tbl_os.revenda_cnpj                           AS cnpj                         ,
					tbl_os.obs                                                                    ,
					tbl_os.defeito_reclamado_descricao                                            ,
					tbl_solucao.descricao                         AS solucao_os                   
			FROM tbl_os
			JOIN tbl_posto                ON tbl_os.posto                                 = tbl_posto.posto
			JOIN tbl_posto_fabrica        ON tbl_posto_fabrica.posto                      = tbl_posto.posto
										 AND tbl_posto_fabrica.fabrica                    = $login_fabrica
			JOIN tbl_produto              ON tbl_os.produto                               = tbl_produto.produto
			LEFT JOIN tbl_solucao            ON tbl_solucao.solucao                       = tbl_os.solucao_os
			WHERE tbl_os.os      = $os 
			AND   tbl_os.fabrica = $login_fabrica ";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows($res) > 0) {
		$tipo_posto = pg_result ($res,0,tipo_posto);
		$posto      = pg_result ($res,0,posto);

		echo "<form name='frmos' method='post' action='$PHP_SELF'>";
		//echo "<div id='div_estoque' style='display:none; Position:absolute; border: 1px solid #949494;background-color: #b8b7af;width:450px;'></div>";

		echo "<table border='0' cellpadding='2' cellspacing='2' width='100%' align='center'>";
		echo "<tr class='Titulo'>";
		echo "<td width='15%'>OS</td>";
		echo "<td width='55%'>Posto</td>";
		echo "<td width='10%'>Abertura</td>";
		echo "<td width='10%'>Fechamento</td>";
		echo "<td width='10%'>Finalizada</td>";
		echo "</tr>";
		echo "<tr class='Conteudo'>";
		echo "<td width='15%'>" . pg_result($res,0,sua_os) . "</td>";
		echo "<td width='55%'>" . pg_result($res,0,codigo_posto) ." - ". pg_result($res,0,nome) . "</td>";
		echo "<td width='10%'>" . pg_result($res,0,abertura) . "</td>";
		echo "<td width='10%'>" . pg_result($res,0,fechamento) . "</td>";
		echo "<td width='10%'>" . pg_result($res,0,finalizada) . "</td>";
		echo "</tr>";
		echo "</table>";
		
		echo "<table border='0' cellpadding='2' cellspacing='2' width='100%' align='center'>";
		echo "<tr class='Titulo'>";
		echo "<td width='70%'>Consumidor</td>";
		echo "<td width='30%'>Telefone</td>";
		echo "</tr>";
		echo "<tr class='Conteudo'>";
		echo "<td width='70%'>" . pg_result($res,0,nome_cli) . "</td>";
		echo "<td width='30%'>" . pg_result($res,0,fone) . "</td>";
		echo "</tr>";
		echo "</table>";
		
		echo "<table border='0' cellpadding='2' cellspacing='2' width='100%' align='center'>";
		echo "<tr class='Titulo'>";
		echo "<td width='45%'>Endereço</td>";
		echo "<td width='45%'>Cidade</td>";
		echo "<td width='10%'>CEP</td>";
		echo "</tr>";
		echo "<tr class='Conteudo'>";
		
		$cliente_endereco    = @pg_result($res,0,cliente_endereco);
		$cliente_cep         = @pg_result($res,0,cliente_cep);
		$cliente_numero      = @pg_result($res,0,cliente_numero);
		$cliente_complemento = @pg_result($res,0,cliente_complemento);
		
		echo "<td width='45%'>$cliente_endereco , $cliente_numero $cliente_complemento</td>";
		echo "<td width='45%'>" . pg_result($res,0,consumidor_cidade) . " - " . pg_result($res,0,consumidor_estado) . "</td>";
		echo "<td width='10%'>$cliente_cep</td>";
		echo "</tr>";
		echo "</table>";
		
		echo "<table border='0' cellpadding='2' cellspacing='2' width='100%' align='center'>";
		echo "<tr class='Titulo'>";
		echo "<td width='34%'>CNPJ</td>";
		echo "<td width='33%'>Nota Fiscal</td>";
		echo "<td width='33%'>Data NF</td>";
		echo "</tr>";
		echo "<tr class='Conteudo'>";
		echo "<td width='34%'>" . pg_result($res,0,cnpj) . "</td>";
		echo "<td width='33%'>" . pg_result($res,0,nota_fiscal) . "</td>";
		echo "<td width='33%'>" . pg_result($res,0,data_nf) . "</td>";
		echo "</tr>";
		echo "</table>";
		
		echo "<table border='0' cellpadding='2' cellspacing='2' width='100%' align='center'>";
		echo "<td width='100%' class='Titulo'>Revenda</td>";
		echo "</tr>";
		echo "<tr class='Conteudo'>";
		echo "<td width='100%'>" . pg_result($res,0,loja) . "</td>";
		echo "</tr>";
		echo "</table>";
		
		echo "<table border='0' cellpadding='2' cellspacing='2' width='100%' align='center'>";
		echo "<tr class='Titulo'>";
		echo "<td width='100%'>Observações</td>";
		echo "</tr>";
		echo "<tr class='Conteudo'>";
		echo "<td width='100%' >" . pg_result($res,0,obs) . "<br></td>";
		echo "</table>";
		
		echo "<table border='0' cellpadding='2' cellspacing='2' width='100%' align='center'>";
		echo "<tr class='Titulo'>";
		echo "<td width='40%'>Produto</td>";
		echo "<td width='20%'>Série</td>";
		echo "</tr>";
		echo "<tr class='Conteudo'>";
		echo "<td>" . pg_result($res,0,referencia) ." - ". pg_result($res,0,nome_equipamento) . "</td>";
		echo "<td>" . pg_result($res,0,serie) . "</td>";
		echo "</tr>";
		echo "</table>";
		
		echo "<table border='0' cellpadding='2' cellspacing='2' width='100%' align='center'>";
		echo "<tr class='Titulo'>";
		echo "<td width='50%'>Defeito Reclamado</td>";
		echo "<td width='50%'>Solução</td>";
		echo "</tr>";
		echo "<tr class='Conteudo'>";
		echo "<td width='50%'>" . pg_result($res,0,defeito_reclamado_descricao) . "</td>";
		echo "<td width='50%'>" . pg_result($res,0,solucao_os) . "</td>";
		echo "</tr>";
		echo "</table>";
	}

	$sql = "SELECT  tbl_peca.referencia                                                    ,
					tbl_peca.descricao                                                     ,
					tbl_os_item.qtde                                                       ,
					tbl_defeito.descricao                   AS defeito                     ,
					tbl_servico_realizado.descricao         AS servico_realizado_descricao 
			FROM	tbl_os_item
			JOIN	tbl_os_produto         ON tbl_os_produto.os_produto               = tbl_os_item.os_produto
			JOIN	tbl_os                 ON tbl_os.os                               = tbl_os_produto.os
			JOIN	tbl_peca               ON tbl_os_item.peca                        = tbl_peca.peca
			JOIN	tbl_defeito            ON tbl_defeito.defeito                     = tbl_os_item.defeito
			JOIN	tbl_servico_realizado  ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado
			WHERE	tbl_os.os      = $os
			AND		tbl_os.fabrica = $login_fabrica 
			ORDER BY tbl_peca.referencia;";
	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		echo "<br><table border='0' cellpadding='2' cellspacing='2' width='100%' align='center'>";
		echo "<tr class='Titulo'>";
		echo "<td width='40%'>Peça</td>";
		echo "<td>Defeito</td>";
		echo "<td>Serviço</td>";
		echo "<td width='10%'>Qtde</td>";
		echo "</tr>";
		
		for ($x = 0; $x < pg_numrows($res); $x++) {
			$referencia                  = trim(pg_result($res,$x,referencia));
			$nome                        = trim(pg_result($res,$x,descricao));
			$qtde                        = trim(pg_result($res,$x,qtde));
			$defeito                     = trim(pg_result($res,$x,defeito));
			$servico_realizado_descricao = trim(pg_result($res,$x,servico_realizado_descricao));

			if(($x % 2) == 0) $bg = '#E6EFFF';
			else $bg = '#F9FBFF';

			echo "<tr class='Conteudo' bgcolor='$bg'>";
				echo "<td  >" . $referencia . " - " . $nome . "</td>";
				echo "<td  align='center'>" . $defeito . "</td>";
				echo "<td align='center'>" . $servico_realizado_descricao . "</td>";
				echo "<td align='center'>" . $qtde . "</td>";
			echo "</tr>";

		}
		echo "</table>";
	}

	##############################################################################3


	$sql_cons = "SELECT defeito_constatado, 
						descricao ,
						codigo
				FROM tbl_defeito_constatado 
				WHERE defeito_constatado = $defeito_constatado 
				AND fabrica = $login_fabrica; ";
	$res_cons = @pg_exec($con, $sql_cons);
	if(@pg_numrows($res_cons) > 0){
		$defeito_constatado_descricao = pg_result($res_cons,0,descricao);
		$defeito_constatado_codigo    = pg_result($res_cons,0,codigo);
		$defeito_constatado_id        = pg_result($res_cons,0,defeito_constatado);
	}

	echo "<br><table style=' border:#485989 1px solid; background-color: #e6eef7;font-size:12px;display:none' align='center' width='600' border='0' id='tbl_integridade' cellspacing='3' cellpadding='3'>";
		echo "<thead>";
			echo "<tr bgcolor='#596D9B' style='color:#FFFFFF;'>";
				echo "<td align='center'><b>Defeito Constatado</b></td>";
				echo "<td align='center'><b>Ações</b></td>";
			echo "</tr>";
			
			echo "<tr>";
				echo "<td align='center'>";
					echo "<input type='hidden' name='defeito_constatado' id='defeito_constatado' value='$defeito_constatado_id'>";
					echo "<input type='text' name='defeito_constatado_codigo' id='defeito_constatado_codigo' size='6' onblur=\" pega_dc('$os','defeito_constatado','defeito_constatado_codigo','defeito_constatado_descricao'); \"><img src='imagens/btn_lupa.gif' onclick='fnc_pesquisa_dc(\"$os\",document.frmos.defeito_constatado,document.frmos.defeito_constatado_codigo,document.frmos.defeito_constatado_descricao)'>&nbsp;";
					echo "<input type='text' name='defeito_constatado_descricao' id='defeito_constatado_descricao' size='50'><img src='imagens/btn_lupa.gif' onclick='fnc_pesquisa_dc(\"$os\",document.frmos.defeito_constatado,document.frmos.defeito_constatado_codigo,document.frmos.defeito_constatado_descricao)'>&nbsp;";
				echo "</td>";
				echo "<td align='center'>";
					echo "<input type='button' onclick=\"javascript: adicionaIntegridade()\" value='Adicionar Defeito' name='btn_adicionar'><br>";
				echo "</td>";
			echo "</tr>";
	
			echo "<tr>";
				echo "<td colspan='2'>";
					echo "<hr>";
				echo "</td>";
			echo "</tr>";
		echo "</thead>";
			
		$sql_cons = "SELECT tbl_defeito_constatado.defeito_constatado,
							tbl_defeito_constatado.descricao         ,
							tbl_defeito_constatado.codigo
				FROM tbl_os_defeito_reclamado_constatado
				JOIN tbl_defeito_constatado USING(defeito_constatado)
				WHERE os = $os";
		$res_dc = pg_exec($con, $sql_cons);
		if(pg_numrows($res_dc) > 0){
			echo "<tbody>";
				for($x=0;$x<pg_numrows($res_dc);$x++){
					$dc_defeito_constatado = pg_result($res_dc,$x,defeito_constatado);
					$dc_descricao = pg_result($res_dc,$x,descricao);
					$dc_codigo = pg_result($res_dc,$x,codigo);
					$aa = $x+1;
					echo "<tr>";
					echo "<td><font size='1'><input type='hidden' name='integridade_defeito_constatado_$aa' value='$dc_defeito_constatado'>$dc_codigo-$dc_descricao</font></td>";
					echo "<td align='right'><input type='button' style='width: 120px;' onclick='removerIntegridade(this);' value='Excluir'></td>";
					echo "</tr>";
				}
			echo "</tbody>";
		}
	echo "</table>";
	if(pg_numrows($res_dc) > 0){
			echo "<script>document.getElementById('tbl_integridade').style.display = '';</script>";
	}
##############################################################################

	$pecas              = 0;
	$mao_de_obra        = 0;
	$tabela             = 0;
	$desconto           = 0;
	$desconto_acessorio = 0;

	$sql = "SELECT mao_de_obra
			FROM tbl_produto_defeito_constatado
			WHERE produto = (
				SELECT produto
				FROM tbl_os
				WHERE os = $os
			) 
			AND defeito_constatado = (
				SELECT defeito_constatado 
				FROM tbl_os
				WHERE os = $os
			)";
	$res = pg_exec ($con,$sql);
	if (pg_numrows ($res) == 1) {
		$mao_de_obra = pg_result ($res,0,mao_de_obra);
	}

	$sql = "SELECT  tbl_posto_fabrica.tabela,
					tbl_posto_fabrica.desconto,
					tbl_posto_fabrica.desconto_acessorio
			FROM  tbl_posto_fabrica
			JOIN  tbl_os USING(posto)
			WHERE tbl_posto_fabrica.fabrica = $login_fabrica
			AND   tbl_os.os = $os";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) == 1) {
		$tabela             = pg_result ($res,0,tabela)            ;
		$desconto           = pg_result ($res,0,desconto)          ;
		$desconto_acessorio = pg_result ($res,0,desconto_acessorio);
	}

	if (strlen ($desconto) == 0) $desconto = "0";

	if (strlen ($tabela) > 0) {
		$sql = "SELECT SUM (tbl_tabela_item.preco * tbl_os_item.qtde) AS total
				FROM tbl_os
				JOIN tbl_os_produto USING (os)
				JOIN tbl_os_item    USING (os_produto)
				JOIN tbl_tabela_item ON tbl_os_item.peca = tbl_tabela_item.peca AND tbl_tabela_item.tabela = $tabela
				WHERE tbl_os.os = $os";
		$res = pg_exec ($con,$sql);

		if (pg_numrows ($res) == 1) {
			$pecas = pg_result ($res,0,0);
		}
	}else{
		$pecas = "0";
	}

	echo "<br><table cellpadding='10' cellspacing='0' border='1' align='center' style='border-collapse: collapse' bordercolor='#485989'>";
	echo "<tr style='font-size: 12px ; color:#53607F ' >";

	$sql = "SELECT tbl_os.qtde_km_calculada 
			FROM tbl_os 
			LEFT JOIN tbl_os_extra USING(os) 
			WHERE tbl_os.os = $os 
				AND tbl_os.fabrica = $login_fabrica";
	$res = pg_exec ($con,$sql);
	$qte_km_vd = pg_result ($res,0,qtde_km_calculada);

	if ($qte_km_vd<>0){
		echo "<td align='center' bgcolor='#E1EAF1'><b>";
		echo "Valor Deslocamento";
		echo "</b></td>";
	}
	
	echo "<td align='center' bgcolor='#E1EAF1'><b>";
	echo "Valor das Peças";
	echo "</b></td>";

	echo "<td align='center' colspan='2' bgcolor='#E1EAF1'><b>Mão-de-Obra</b></td>";
	echo "<td align='center' bgcolor='#E1EAF1'><b>Total</b></td>";
	echo "</tr>";

	$valor_liquido = 0;

	if ($desconto > 0 and $pecas <> 0) {
		$valor_desconto = round ( (round ($pecas,2) * $desconto / 100) ,2);
		$valor_liquido = $pecas - $valor_desconto ;
	}

	$acrescimo = 0;

	$sql = "select pecas,mao_de_obra  from tbl_os where os=$os";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) == 1) {
		$valor_liquido = pg_result ($res,0,pecas);
		$mao_de_obra   = pg_result ($res,0,mao_de_obra);
	}

	$valor_km = 0;
	$sql = "SELECT	tbl_os.mao_de_obra, 
					tbl_os.qtde_km_calculada, 
					tbl_os_extra.extrato
			FROM tbl_os 
			LEFT JOIN tbl_os_extra USING(os)
			WHERE tbl_os.os = $os 
			AND   tbl_os.fabrica = $login_fabrica";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) == 1) {
		$mao_de_obra   = pg_result ($res,0,mao_de_obra);
		$valor_km      = pg_result ($res,0,qtde_km_calculada);
		$extrato       = pg_result ($res,0,extrato);
	}

	$total = $valor_liquido + $mao_de_obra + $acrescimo + $valor_km;

	$total          = number_format ($total,2,",",".")         ;
	$mao_de_obra    = number_format ($mao_de_obra ,2,",",".")  ;
	$acrescimo      = number_format ($acrescimo ,2,",",".")    ;
	$valor_desconto = number_format ($valor_desconto,2,",",".");
	$valor_liquido  = number_format ($valor_liquido ,2,",",".");
	$valor_km       = number_format ($valor_km ,2,",",".");

	echo "<tr style='font-size: 12px ; color:#000000 '>";
	if ($valor_km<>0){
		echo "<td align='right'><font color='#333377'><b>$valor_km</b></td>";
	}
	echo "<td align='right'><font color='#333377'><b>$valor_liquido</b></td>" ;
	echo "<td align='center' colspan='2'>$mao_de_obra</td>";
	echo "<td align='center' bgcolor='#E1EAF1'><font size='3' color='FF0000'><b>$total</b></td>";
	echo "</tr>";

	echo "</table>";






	
	echo "<p align='center'>";
	
	echo "<table border='0' cellpadding='2' cellspacing='2' width='95%' align='center'>";
		echo "<tr>";
			echo "<td width='100%' bgcolor='#FFFFFF' align='center'>";
				echo "<font face='Verdana, Arial, Helvetica, sans' color='#0000FF' size='2'>";
				echo "<input type='hidden' name='btn_acao' value=''>";
				echo "<input type='hidden' name='os' value='$os'>";
				echo "<img border='0' src='imagens/btn_gravar.gif' alt='Gravar alterações' onclick=\"javascript: if (document.frmos.btn_acao.value == '' ) { document.frmos.btn_acao.value='gravar' ; document.frmos.submit() } else { alert ('Aguarde submissão') }\" ></a>";
				echo "</font>";
			echo "</td>";
		echo "</tr>";
	echo "</table>";

	echo "</form>";
}
?>
</body>
</html>