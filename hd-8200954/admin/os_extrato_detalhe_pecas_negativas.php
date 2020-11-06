<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="financeiro";
include 'autentica_admin.php';

if ($login_fabrica <> 1) {
	header ("Location: menu_financeiro.php");
	exit;
}

header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
$ajax = $_GET['ajax'];
if(strlen($ajax)>0){
if($ajax=="estoque"){
	$peca         = $_GET['peca'];
	$posto        = $_GET['posto'];
	$data_inicial = date("Y-m-d", mktime(0, 0, 0, date("n"), 1,  date("Y")));
	$data_final   = date("Y-m-t", mktime(0, 0, 0, date("n"), 1,  date("Y")));
	$sql = "SELECT 	tbl_estoque_posto_movimento.peca                              , 
						tbl_peca.referencia                                           ,
						tbl_peca.descricao as peca_descricao                          ,
						tbl_os.sua_os                                                 ,
						tbl_estoque_posto_movimento.os                                , 
						to_char(tbl_estoque_posto_movimento.data,'DD/MM/YYYY') as data,
						tbl_estoque_posto_movimento.qtde_entrada                      , 
						tbl_estoque_posto_movimento.qtde_saida                        , 
						tbl_estoque_posto_movimento.admin                             ,
						tbl_estoque_posto_movimento.pedido                            , 
						tbl_estoque_posto_movimento.obs
				FROM  tbl_estoque_posto_movimento 
				JOIN  tbl_peca on tbl_peca.peca =  tbl_estoque_posto_movimento.peca
				AND   tbl_peca.fabrica = $login_fabrica
				LEFT  JOIN tbl_os on tbl_estoque_posto_movimento.os = tbl_os.os 
				AND   tbl_os.fabrica = $login_fabrica
				WHERE tbl_estoque_posto_movimento.posto   = $posto 
				AND   tbl_estoque_posto_movimento.peca    = $peca
				AND   tbl_estoque_posto_movimento.fabrica = $login_fabrica 
				ORDER BY tbl_peca.descricao,
				tbl_estoque_posto_movimento.data,
				tbl_estoque_posto_movimento.qtde_saida,
				tbl_estoque_posto_movimento.os";

//	AND   tbl_estoque_posto_movimento.data between '$data_inicial' and '$data_final' 
	$res = pg_exec($con,$sql);
	if(pg_numrows($res)>0){
		echo "<table border='0' width='100%' cellpadding='4' cellspacing='1' align='rigth' style='font-family: verdana; font-size: 9px'><tr><td width='95%'>&nbsp;</td><td align='right' bgcolor='#FFFFFF'> <a href='javascript:escondeEstoque();'> <B>Fechar</b></a></td></tr></table>";
		echo "<table border='0' cellpadding='4' cellspacing='1' bgcolor='#596D9B' align='center' style='font-family: verdana; font-size: 9px'>";

		echo "<thead>";
		echo "<tr>";
		echo "<th><font color='#FFFFFF'><B>Movimen.</B></FONT></th>";
		echo "<th><font color='#FFFFFF'><B>Data</B></FONT></th>";
		echo "<th><font color='#FFFFFF'><B>Peça</B></FONT></th>";
		echo "<th><font color='#FFFFFF'><B>Entrada</B></FONT></th>";
		echo "<th><font color='#FFFFFF'><B>Saida</B></FONT></th>";
		echo "<th><font color='#FFFFFF'><B>Pedido</B></FONT></th>";
		echo "<th><font color='#FFFFFF'><B>OS</B></FONT></th>";
		echo "<th><font color='#FFFFFF'><B>Observação</B></FONT></th>";
		echo "</tr>";
		echo "</thead>";
		echo "<tbody>";
		for($i=0; pg_numrows($res)>$i;$i++){
		
			$os             = pg_result ($res,$i,os);
			$sua_os         = pg_result ($res,$i,sua_os);
			$referencia     = pg_result ($res,$i,referencia);
			$peca_descricao = pg_result ($res,$i,peca_descricao);
			$data           = pg_result ($res,$i,data);
			$qtde_entrada   = pg_result ($res,$i,qtde_entrada);
			$qtde_saida     = pg_result ($res,$i,qtde_saida);
			$admin          = pg_result ($res,$i,admin);
			$obs            = pg_result ($res,$i,obs);
			$pedido         = pg_result ($res,$i,pedido);
		//	$obs            = pg_result ($res,$i,obs);
			
			$saida_total  = $saida_total + $qtde_saida;
			$entrada_total = $entrada_total + $qtde_entrada;
			
			/*if(strlen($obs) > 0 and strlen($qtde_saida) == 0){ 
				$obs = "OS recusada, pe?a volta para estoque";
			}else{ 
				$obs = "";
			}*/

			if($qtde_entrada>0){
				$movimentacao = "<font color='#35532f'>Entrada</font>";
			}else{
				$movimentacao = "<font color='#f31f1f'>Saida</font>";
			}
			
			$cor = "#efeeea"; 
			if ($i % 2 == 0) $cor = '#d2d7e1';

			echo "<tr bgcolor='$cor'>";
			echo "<td align='center'>$movimentacao</td>";
			echo "<td align='center'>$data</td>";
			echo "<td align='left'>$referencia</td>";
			echo "<td align='center'>$qtde_entrada</td>";
			echo "<td align='center'>$qtde_saida</td>";
			echo "<td align='center'><a href='pedido_finalizado.php?pedido=$pedido' target='_blank'>$pedido</a></td>";
			echo "<td><a href='os_press.php?os=$os' target='_blank'>$sua_os</a></td>";
			echo "<td align='left'>$obs</td>";
			echo "</td>";
			echo "</tr>";
		}
		$total = $entrada_total - $saida_total;
		echo "</tbody>";
		echo "<tfoot>";
		echo "<tr bgcolor='#FFFFFF'>";
		echo "<td colspan='3' align='center'><font color='#2f67cd'><B>SALDO</B></FONT></td>";
		echo "<td colspan='2' align='center'><font color='#2f67cd'><B>"; echo $total; echo "</B></FONT></td>";
		echo "<td>&nbsp;</td>";
		echo "<td>&nbsp;</td>";
		echo "<td>&nbsp;</td>";
		echo "</tr>";
		echo "</tfoot>";
		echo "</table><BR>";

		}else{
			echo "<BR><center><font color='#FFFFFF'>Nenhum resultado encontrado</font></center><BR>";
		}
	}

	if($ajax=="autoriza"){
		$peca         = $_GET['peca'];
		$os_item      = $_GET['os_item'];
		$sql = "SELECT 	tbl_os_produto.os, 
						tbl_os_item.peca_sem_estoque,
						tbl_peca.referencia, 
						tbl_peca.descricao
				FROM tbl_os_item
				JOIN tbl_os_produto using(os_produto)
				JOIN tbl_peca on tbl_os_item.peca = tbl_peca.peca and tbl_peca.fabrica = $login_fabrica
				WHERE tbl_os_item.os_item = $os_item
				and tbl_os_item.peca = $peca 
				and peca_sem_estoque is true";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			$peca_referencia = pg_result($res,0,referencia);
			$peca_descricao  = pg_result($res,0,descricao);
			echo "<table border='0' width='100%' cellpadding='4' cellspacing='1' align='rigth' style='font-family: verdana; font-size: 9px'><tr><td width='95%'>&nbsp;</td><td align='right' bgcolor='#FFFFFF'> <a href='javascript:escondeEstoque();'> <B>Fechar</b></a></td></tr></table>";
			echo "<table border='0' cellpadding='4' cellspacing='1' bgcolor='#596D9B' align='center' style='font-family: verdana; font-size: 9px' width='350'>";
			echo "<tr>";
			echo "<td align='center'><b><font color='#FFFFFF'>Aceitar peca: $peca_referencia</FONT></b></td>";
			echo "</tr>";	
			echo "<tr>";
			echo "<td align='center' bgcolor='#efeeea'><b>Atenção</b><BR>";
			echo "O estoque do posto para a <BR>peça $peca_descricao esta negativa.<BR> Para autorizar a utilização da peça informe o motivo<BR>";
			echo "<TEXTAREA NAME='autorizacao_texto' ID='autorizacao_texto' ROWS='5' COLS='30' class='textarea'></TEXTAREA>";
			echo "<input type='hidden' name='peca' id='peca' value='$peca'>";
			echo "<input type='hidden' name='os_item' id='os_item' value='$os_item'>";
			echo "<BR><BR><img src='imagens_admin/btn_confirmar.gif' border='0' style='cursor:pointer;' onClick='gravaAutorizao();'></td>";
			echo "</tr>";	
			echo "</table><BR>";
		}
	}
	if($ajax=="gravar"){
		$peca         = $_GET['peca'];
		$os_item      = $_GET['os_item'];
		$autorizacao_texto     = $_GET['autorizacao_texto'];
	/*echo "peca $peca<BR>";
	echo "os_item $os_item<BR>";
	echo "autorizacao_texto $autorizacao_texto";*/
		$sql = "select 	tbl_os.posto      , 
						tbl_os_item.qtde  , 
						tbl_os.os
				from tbl_os
				JOIN tbl_os_produto using(os)
				join tbl_os_item using(os_produto)
				where tbl_os.fabrica = $login_fabrica
				and  tbl_os_item.os_item = $os_item
				and  tbl_os_item.peca = $peca
				and   tbl_os_item.peca_sem_estoque is true";
		$res = pg_exec($con,$sql);
		$msg_erro .= pg_errormessage($con);
	//echo $sql."<BR>";
		if(pg_numrows($res)>0 and strlen($msg_erro)==0){
			$posto = pg_result($res,0,posto);
			$qtde = pg_result($res,0,qtde);
			$os = pg_result($res,0,os);
			$sql = "INSERT INTO tbl_estoque_posto_movimento(
						fabrica      , 
						posto        , 
						os           ,
						peca         , 
						qtde_entrada   ,
						data, 
						os_item, 
						obs,
						admin
						)values(
						$login_fabrica,
						$posto        ,
						$os           , 
						$peca         ,
						$qtde         ,
						current_date  ,
						$os_item       ,
						'$autorizacao_texto',
						$login_admin
				)";
			$res = pg_exec($con,$sql);
			$msg_erro .= pg_errormessage($con);
			
			if(strlen($msg_erro)==0){
				$sql = "SELECT peca 
						FROM tbl_estoque_posto 
						WHERE peca = $peca 
						AND posto = $posto 
						AND fabrica = $login_fabrica;";
				$res = pg_exec($con,$sql);
				if(pg_numrows($res)>0){
					$sql = "UPDATE tbl_estoque_posto set 
							qtde = qtde + $qtde
							WHERE peca  = $peca
							AND posto   = $posto
							AND fabrica = $login_fabrica;";
					$res = pg_exec($con,$sql);
					$msg_erro .= pg_errormessage($con);
				}else{
					$sql = "INSERT into tbl_estoque_posto(fabrica, posto, peca, qtde)
							values($login_fabrica,$posto,$peca,$qtde)";
					$res = pg_exec($con,$sql);
					$msg_erro .= pg_errormessage($con);
				}
			}
		}
		if(strlen($msg_erro)>0){
			echo "Ocorreu um erro";
		}else{
			echo "<table border='0' cellpadding='4' cellspacing='1' bgcolor='#FFFFFF' align='center' style='font-family: verdana; font-size: 9px' width='100%'>";
			echo "<tr>";
			echo "<td align='center'><b><font color='#000000'>Atualizado com sucesso!!</FONT></b><br><a href='javascript:escondeEstoque();'> <B>Clique aqui para Fechar</b></a></td>";
			echo "</tr>";	
			echo "</table>";
		echo "<META HTTP-EQUIV='Refresh' CONTENT='';URL=$PHP_SELF'>";
		}
	}
exit;
}
$layout_menu = "financeiro";
$title = "Black & Decker - Peças Negativas";
?>

<html>

<head>
<title><? echo $title ?></title>
<meta http-equiv="content-Type"  content="text/html; charset=iso-8859-1">
<meta http-equiv="Expires"       content="0">
<meta http-equiv="Pragma"        content="no-cache, public">
<meta http-equiv="Cache-control" content="no-cache, public, must-revalidate, post-check=0, pre-check=0">
<link type="text/css" rel="stylesheet" href="css/css_press.css">

<style>
/*******************************
 ELEMENTOS DE COR FONTE EXTRATO 
*******************************/
.TdBold   {font-weight: bold;}
.TdNormal {font-weight: normal;}
.TdCompres{background-color: #cbcbcb;}
</style>
<script>
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
var http3 = new Array();
function gravaAutorizao(){
	var os_item           = document.getElementById('os_item');
	var peca              = document.getElementById('peca');
	var autorizacao_texto = document.getElementById('autorizacao_texto');
	
		var curDateTime = new Date();
		http3[curDateTime] = createRequestObject();
	
		url = "<?echo $PHP_SELF;?>?ajax=gravar&peca="+peca.value+"&os_item="+os_item.value+"&autorizacao_texto="+autorizacao_texto.value;
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

	url = "<?echo $PHP_SELF;?>?ajax=autoriza&peca="+peca+"&os_item="+os_item;
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
function verificarEstoque(posto,peca){
	var div = document.getElementById('div_estoque');
	div.style.display = (div.style.display=="") ? "none" : "";
	mostraEstoque(posto,peca);
}

function mostraEstoque(posto,peca){

	var curDateTime = new Date();
	http3[curDateTime] = createRequestObject();

	url = "<?echo $PHP_SELF;?>?ajax=estoque&peca="+peca+"&posto="+posto;
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
<TABLE width="600px" border="0" cellspacing="1" cellpadding="0">
<TR>
	<TD><IMG SRC="logos/cabecalho_print_<? echo strtolower ($login_fabrica_nome) ?>.gif" ALT="ORDEM DE SERVIÇO"></TD>
</TR>
</TABLE>

<br>

<?
if (strlen($extrato) > 0) {
	$data_atual = date("d/m/Y");

	$sql = "SELECT  to_char(min(tbl_os.data_fechamento),'DD/MM/YYYY') AS inicio,
					to_char(max(tbl_os.data_fechamento),'DD/MM/YYYY') AS final
			FROM    tbl_os
			JOIN    tbl_os_extra USING (os)
			WHERE   tbl_os_extra.extrato = $extrato;";
//if ($ip == "201.43.246.49") echo $sql;
	$res = pg_exec($con,$sql);
	
	if (pg_numrows($res) > 0) {
		$inicio_extrato = trim(pg_result($res,0,'inicio'));
		$final_extrato  = trim(pg_result($res,0,'final'));
	}

	if (strlen($inicio_extrato) == 0 AND strlen($final_extrato) == 0) {
		$sql = "SELECT  to_char(min(tbl_extrato.data_geracao),'DD/MM/YYYY') AS inicio,
						to_char(max(tbl_extrato.data_geracao),'DD/MM/YYYY') AS final
				FROM    tbl_extrato
				WHERE   tbl_extrato.extrato = $extrato";
//if ($ip == "201.43.246.49") echo $sql;
		$res = pg_exec ($con,$sql);

		if (pg_numrows($res) > 0) {
			$inicio_extrato = trim(pg_result($res,0,'inicio'));
			$final_extrato  = trim(pg_result($res,0,'final'));
		}
	}

	$sql = "SELECT  tbl_posto_fabrica.codigo_posto                                          ,
					tbl_posto.posto                                         ,
					tbl_posto.nome                                          ,
					tbl_posto_fabrica.contato_endereco AS endereco          ,
					tbl_posto_fabrica.contato_cidade   AS cidade            ,
					tbl_posto_fabrica.contato_estado   AS estado            ,
					tbl_posto_fabrica.contato_cep      AS cep               ,
					tbl_posto.fone                                          ,
					tbl_posto.fax                                           ,
					tbl_posto.contato                                       ,
					tbl_posto_fabrica.contato_email    AS email             ,
					tbl_posto.cnpj                                          ,
					tbl_posto.ie                                            ,
					tbl_posto_fabrica.banco                                 ,
					tbl_posto_fabrica.agencia                               ,
					tbl_posto_fabrica.conta                                 ,
					tbl_extrato.protocolo                                   ,
					to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data 
			FROM    tbl_posto
			JOIN    tbl_posto_fabrica ON  tbl_posto.posto           = tbl_posto_fabrica.posto
									  AND tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN    tbl_extrato ON tbl_extrato.posto = tbl_posto.posto
			WHERE   tbl_extrato.extrato = $extrato;";
//if ($ip == "201.43.246.49") echo $sql;
	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		$codigo        = trim(pg_result($res,0,codigo_posto));
		$posto         = trim(pg_result($res,0,posto));
		$nome          = trim(pg_result($res,0,nome));
		$endereco      = trim(pg_result($res,0,endereco));
		$cidade        = trim(pg_result($res,0,cidade));
		$estado        = trim(pg_result($res,0,estado));
		$cep           = substr(pg_result($res,0,cep),0,2) .".". substr(pg_result($res,0,cep),2,3) ."-". substr(pg_result($res,0,cep),5,3);
		$fone          = trim(pg_result($res,0,fone));
		$fax           = trim(pg_result($res,0,fax));
		$contato       = trim(pg_result($res,0,contato));
		$email         = trim(pg_result($res,0,email));
		$cnpj          = trim(pg_result($res,0,cnpj));
		$ie            = trim(pg_result($res,0,ie));
		$banco         = trim(pg_result($res,0,banco));
		$agencia       = trim(pg_result($res,0,agencia));
		$conta         = trim(pg_result($res,0,conta));
		$data_extrato  = trim(pg_result($res,0,data));
		$protocolo     = trim(pg_result($res,0,protocolo));
		echo "<div id='div_estoque' style='display:none; Position:absolute; border: 1px solid #949494;background-color: #b8b7af;width:450px;'></div>";
		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		
		echo "<td bgcolor='#FFFFFF' width='100%' align='left' colspan='2'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'><b>BLACK & DECKER DO BRASIL LTDA</b></font>\n";
		echo "</td>\n";
		
		echo "</tr>\n";
		echo "<tr>\n";
		
		echo "<td nowrap bgcolor='#FFFFFF' width='100%' colspan=2 align='left'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'><b>End.</b> Rod. BR 050 S/N KM 167-LOTE 5 QVI &nbsp;&nbsp;-&nbsp;&nbsp; <b>Bairro:</b> DI II</font>\n";
		echo "</td>\n";
		
		echo "</tr>\n";
		echo "<tr>\n";
		
		echo "<td nowrap bgcolor='#FFFFFF' width='100%' colspan=2 align='left'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'><b>Cidade:</b> Uberaba &nbsp;&nbsp;-&nbsp;&nbsp; <b>Estado:</b> MG &nbsp;&nbsp;-&nbsp;&nbsp; <b>Cep:</b> 38064-750</font>\n";
		echo "</td>\n";
		
		echo "</tr>\n";
		echo "<tr>\n";
		
		echo "<td bgcolor='#FFFFFF' width='50%' align='left'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'>Inscrição CNPJ: 53.296.273/0001-91</font>\n";
		echo "</td>\n";
		
		echo "<td bgcolor='#FFFFFF' width='50%' align='left'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'>Inscrição Estadual: 701.948.711.00-98</font>\n";
		echo "</td>\n";

		echo "</tr>\n";
		echo "</table>\n";
		
		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		
		echo "<td bgcolor='#FFFFFF' width='100%' align='left'>\n";
		echo "<hr>\n";
		echo "</td>\n";
		
		echo "</tr>\n";
		echo "</table>\n";
		
		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		
		echo "<td bgcolor='#FFFFFF' nowrap align='left'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'><b>EXTRATO DE SERVIÇOS $data_extrato</b></font>\n";
		echo "</td>\n";

		echo "<td bgcolor='#FFFFFF' nowrap align='right' >\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'><b>$protocolo</b></font>\n";
		echo "</td>\n";

		echo "</tr>\n";
		echo "</table>\n";
		
		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		
		echo "<td bgcolor='#FFFFFF' width='100%' align='left'>\n";
		echo "<hr>\n";
		echo "</td>\n";
		
		echo "</tr>\n";
		echo "</table>\n";

		
		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		
		echo "<td bgcolor='#FFFFFF'  nowrap align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='70' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Período:</b></font>\n";
		echo "</td>\n";
		
		echo "<td bgcolor='#FFFFFF' nowrap  align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='100' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$inicio_extrato</font>\n";
		echo "</td>\n";
		
		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='40' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Até:</b></font>\n";
		echo "</td>\n";
		
		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='120' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$final_extrato</font>\n";
		echo "</td>\n";
		
		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='40' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Data:</b></font>\n";
		echo "</td>\n";

		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='230' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$data_atual</font>\n";
		echo "</td>\n";
		
		echo "</tr>\n";
		echo "</table>\n";
		
		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		
		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='70' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Código:</b></font>\n";
		echo "</td>\n";
		
		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='530' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$codigo</font>\n";
		echo "</td>\n";

		echo "</tr>\n";
		echo "</table>\n";

		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		
		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='70' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Posto:</b></font>\n";
		echo "</td>\n";
		
		echo "<td bgcolor='#FFFFFF' align='left' nowrap>\n";
		echo "<img src='imagens/pixel.gif' width='530' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$nome</font>\n";
		echo "</td>\n";
		
		echo "</tr>\n";
		echo "</table>\n";
		
		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		
		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='70' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Endereço:</b></font>\n";
		echo "</td>\n";

		echo "<td bgcolor='#FFFFFF' align='left' nowrap>\n";
		echo "<img src='imagens/pixel.gif' width='530' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$endereco</font>\n";
		echo "</td>\n";
		
		echo "</tr>\n";
		echo "</table>\n";
		
		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		
		echo "<td bgcolor='#FFFFFF' align='left' width='70'>\n";
		echo "<img src='imagens/pixel.gif' width='70' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Cidade:</b></font>\n";
		echo "</td>\n";
		
		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='530' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$cidade - $estado - $cep</font>\n";
		echo "</td>\n";
		
		echo "</tr>\n";
		echo "</table>\n";
		
		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";

		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='70' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Telefone:</b></font>\n";
		echo "</td>\n";

		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='100' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$fone</font>\n";
		echo "</td>\n";

		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='40' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Fax:</b></font>\n";
		echo "</td>\n";

		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='100' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$fax</font>\n";
		echo "</td>\n";

		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='40' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>E-mail:</b></font>\n";
		echo "</td>\n";
		
		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='250' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$email</font>\n";
		echo "</td>\n";
		
		echo "</tr>\n";
		echo "</table>\n";
		
		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		
		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='70' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>CNPJ:</b></font>\n";
		echo "</td>\n";
		
		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='130' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$cnpj</font>\n";
		echo "</td>\n";
		
		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='30' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>IE:</b></font>\n";
		echo "</td>\n";
		
		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='370' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$ie</font>\n";
		echo "</td>\n";
		
		echo "</tr>\n";
		echo "</table>\n";
	}
	
	echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
	echo "<tr>\n";
	
	echo "<td bgcolor='#FFFFFF' width='100%' align='left'>\n";
	echo "<hr>\n";
	echo "</td>\n";
	
	echo "</tr>\n";
	echo "</table>\n";
	
	$xtotal = 0;
	
	### OS NORMAL
//			AND   (length(tbl_os.obs) = 0 OR tbl_os.obs isnull)

	$sql =	"SELECT os_item, 
					tbl_produto.referencia as produto_referencia,
					Y.os, 
					Y.sua_os, 
					Y.abertura, 
					Y.fechamento, 
					tbl_peca.peca,
					tbl_peca.referencia as peca_referencia,
					tbl_peca.descricao as peca_descricao,
					tbl_os_item.qtde,
					tbl_estoque_posto.qtde as qtde_estoque
			FROM  tbl_os_item
			JOIN (
					SELECT	os_produto , 
							tbl_os.os,
							tbl_os.sua_os, 
							tbl_os.produto, 
							to_char(tbl_os.data_abertura,'DD/MM/YYYY') as abertura,
							to_char(tbl_os.data_fechamento,'DD/MM/YYYY') as fechamento
					FROM tbl_os_produto 
					JOIN (
						SELECT os 
						FROM tbl_os_extra 
						where extrato = $extrato
					) as X on X.os = tbl_os_produto.os
					JOIN tbl_os on tbl_os.os = tbl_os_produto.os
			) as Y on Y.os_produto = tbl_os_item.os_produto
			JOIN tbl_produto on tbl_produto.produto = Y.produto
			JOIN tbl_peca on tbl_peca.peca = tbl_os_item.peca
			JOIN tbl_estoque_posto on tbl_peca.peca = tbl_estoque_posto.peca 
			AND tbl_estoque_posto.posto   = $posto
			AND tbl_estoque_posto.fabrica = $login_fabrica

			AND tbl_os_item.peca_sem_estoque is true ;";
//echo nl2br($sql);
	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {

		echo "<table border='0' cellpadding='2' cellspacing='0' width='400' align='center'>\n";
		echo "<tr>\n";
		echo "<td bgcolor='#FF0000'>&nbsp;&nbsp;</td>";
		echo "<td ><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'> Estoque com mais de 20 peças negativas</font></td>";
		echo "</tr>";
		echo "</table><BR>";

		echo "<table border='1' cellpadding='2' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		
		echo "<td bgcolor='#FFFFFF' width='20%' nowrap align='center'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>OS</b></font>\n";
		echo "</td>\n";

		/*takashi 22-05-07 hd 2432*/
		echo "<td bgcolor='#FFFFFF' width='10%' nowrap align='center'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Produto</b></font>\n";
		echo "</td>\n";
/*takashi 22-05-07 hd 2432*/
		echo "<td bgcolor='#FFFFFF' width='15%' nowrap  align='center'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Abertura</b></font>\n";
		echo "</td>\n";

		echo "<td bgcolor='#FFFFFF'  nowrap width='15%' align='center'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Fechamento</b></font>\n";
		echo "</td>\n";

		echo "<td bgcolor='#FFFFFF' width='5%' align='center'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Peça</b></font>\n";
		echo "</td>\n";

		echo "<td bgcolor='#FFFFFF' width='10%' align='center'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Qtde</b></font>\n";
		echo "</td>\n";

		echo "<td bgcolor='#FFFFFF' width='15%' align='center'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Qtde Estoque</b></font>\n";
		echo "</td>\n";

		echo "<td bgcolor='#FFFFFF' width='15%' align='center'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Ação</b></font>\n";
		echo "</td>\n";
		
		echo "</tr>\n";

		
		// monta array da tela
		for ($x = 0; $x < pg_numrows($res); $x++) {
			$sua_os    = trim(pg_result($res,$x,sua_os));
			$os_item    = trim(pg_result($res,$x,os_item));
			$os        = trim(pg_result($res,$x,os));
			$peca        = trim(pg_result($res,$x,peca));
			$peca_referencia = trim(pg_result($res,$x,peca_referencia));
			$data_abertura   = trim(pg_result($res,$x,abertura));
			$data_fechamento = trim(pg_result($res,$x,fechamento));
			$qtde            = trim(pg_result($res,$x,qtde));
			$peca_descricao  = trim(pg_result($res,$x,peca_descricao));
			$produto_referencia  = trim(pg_result($res,$x,produto_referencia));
			$produto_referencia = substr($produto_referencia,0,8);
			$peca_descricao = substr($peca_descricao,0,8);
			$qtde_estoque    = trim(pg_result($res,$x,qtde_estoque));
		
			echo "<tr class='$bold'>\n";
			
			echo "<td align='center'  nowrap bgcolor='$cor_compress'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>&nbsp;</font> ";
			echo " <a href='os_press.php?os=$os' target='_blank'><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'> $codigo$sua_os</font></a>\n";
			echo "</td>\n";
			
/*takashi 22-05-07 hd 2432*/
			echo "<td align='center' nowrap bgcolor='$cor_compress'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$produto_referencia</a></font>\n";
			echo "</td>\n";
/*takashi 22-05-07 hd 2432*/
			echo "<td align='center' nowrap bgcolor='$cor_compress'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$data_abertura</a></font>\n";
			echo "</td>\n";

			echo "<td align='center' nowrap  bgcolor='$cor_compress'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$data_fechamento</a></font>\n";
			echo "</td>\n";

			echo "<td align='center' nowrap  bgcolor='$cor_compress'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$peca_referencia - $peca_descricao</font>\n";
			echo "</td>\n";

			echo "<td align='center' nowrap bgcolor='$cor_compress'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$qtde</font>\n";
			echo "</td>\n";

			echo "<td align='center'  nowrap bgcolor='$cor_compress'>\n";
			if($qtde_estoque < -20){
				$qtde_estoque = "<B><font face='Verdana, Arial, Helvetica, sans' color='#FF0000' size='-2'>$qtde_estoque</font></b>";
			}else{
				$qtde_estoque = "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$qtde_estoque</font>";
			}
			echo "$qtde_estoque\n";
			echo "</td>\n";
			echo "<td align='center' bgcolor='$cor_compress' nowrap>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>
			<a href=\"javascript:verificarEstoque($posto,$peca);\"><B><font color='#5c340e'>Verificar estoque</font></b></a>
			<a href=\"javascript:aceitarPeca($os_item,$peca);\"><b><font color='#2d6b0b'>Aceitar</font></b></a> 
			</font>\n";
			echo "</td>\n";

			echo "</tr>\n";
		}
	echo "</table>\n";
}
}
?>

<br>

</body>

</html>
<SCRIPT LANGUAGE="JavaScript">
<!--
//window.print();
//-->
</SCRIPT>
