<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

// $abrir =fopen("/www/assist/www/nosso_ip.txt", "r");
//  $teste=fread($abrir, filesize("/www/assist/www/nosso_ip.txt"));

//$teste = include ("../nosso_ip.php");

//if ($ip!=trim($teste)){
//	header("Location: index.php");
//}

function converte_data($date)
{
	$date = explode("-", str_replace('/', '-', $date));
	$date2 = ''.$date[2].'/'.$date[1].'/'.$date[0];
	if (sizeof($date)==3)
		return $date2;
	else return false;
}


if (isset($_GET['gerar']) AND strlen($_GET['gerar'])>0){
	$chamado=$_GET['gerar'];
	$res = @pg_exec($con,"BEGIN TRANSACTION");

	$sql =  "SELECT tbl_posto.posto                    AS posto,
					tbl_distrib_chamado.nota_fiscal    AS nota_fiscal,
					tbl_distrib_chamado.valor_credito  AS valor_credito
			FROM tbl_distrib_chamado
			JOIN tbl_posto USING(posto)
			LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = tbl_distrib_chamado.fabrica
			WHERE tbl_distrib_chamado.distrib_chamado=$chamado";
	$res = pg_exec($con,$sql);
	$resultados = pg_numrows($res);

	if ($resultados!=1){
		$msg_erro .="Chamado não encontrado!<br>";
	}

	if (strlen($msg_erro)==0){
		$posto		= trim(pg_result($res,$i,posto));
		$nota_fiscal	= trim(pg_result($res,$i,nota_fiscal));
		$valor_credito	= trim(pg_result($res,$i,valor_credito));
		$nota_fiscal	= str_replace (".","",$nota_fiscal);
		$nota_fiscal	= str_replace (" ","",$nota_fiscal);
		$nota_fiscal	= str_replace ("-","",$nota_fiscal);
		$nota_fiscal	= str_replace (",","",$nota_fiscal);

		$sql=" UPDATE tbl_distrib_chamado
				SET data_credito = NOW()
				WHERE distrib_chamado = $chamado";
		$res = pg_exec ($con,$sql);
		$msg_erro .= pg_errormessage($con);
	}

	if (strlen($msg_erro)==0){
		$valor_credito = str_replace (",",".",$valor_credito);
		$emissao = date("Y-m-d");
		$sql = "INSERT INTO tbl_distrib_devolucao (distribuidor, posto, nota_fiscal, emissao, total) VALUES ($login_posto, $posto, LPAD (TRIM ('$nota_fiscal'),6,'0'),'$emissao',$valor_credito)";
		$res = pg_exec ($con,$sql);
		$msg_erro .= pg_errormessage($con);
	}

	if (strlen($msg_erro)==0){
		$res = @pg_exec ($con,"COMMIT TRANSACTION");
		//$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
		header("Location: $PHP_SELF");
	}
	else {
		$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
		$msg_erro = "Erro ao inserir data da geração de crédito. Verificar com Fábio.";
	}

}

?>

<html>
<head>
<title>Chamados Consulta</title>
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
	font-size: 12px;
	font-weight: bold;
	color: white;
	background-color: #0099CC;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
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

function fnc_pesquisa_posto(campo, campo2, tipo) {
	if (tipo == "codigo" ) {
		var xcampo = campo;
	}

	if (tipo == "nome" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa_fabio.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.codigo  = campo;
		janela.nome    = campo2;
		janela.focus();
	}
}




function fnc_pesquisa_peca (id, tipo) {

	var url = "";
	var ide=id.alt;

	var var1=document.getElementById("peca_"+ide);
	var var2=document.getElementById("peca_ref_"+ide);
	var var3=document.getElementById("peca_descricao_"+ide);

	url = "peca_pesquisa_fabio.php?campo1=" +var2.value+"&campo2=" +var3.value+"&tipo="+tipo ;

	janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=501, height=400, top=18, left=0");
	if (var2.value.length >= 3 || var3.value.length >= 3) {
		janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=501, height=400, top=18, left=0");
		janela.peca		= var1;
		janela.referencia	= var2;
		janela.descricao		= var3;
		janela.focus();
	}else{
		alert("Digite pelo menos 3 caracteres!");
	}
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
			temp.innerHTML = response2;

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
		document.getElementById('loading').innerHTML = "<table border=0 cellpadding=0 cellspacing=1 width=200 bgcolor=#AAA><tr><td align=center class=loading height=45 bgcolor=#FFFFFF>Aguarde...Carregando....<br><img src='../imagens/carregar_os' ></td></tr></table>";
	}
	if(obj.readyState == 4)	{
		if(obj.status == 200){
			document.getElementById('loading').innerHTML = "<table border=0 cellpadding=0 cellspacing=1 width=200 bgcolor=gray><tr><td align=center class=loaded height=45 bgcolor=#ffffff>Informações carregadas com sucesso!</td></tr></table>";
			setTimeout('Page.loadOut()',1500);
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

</script>

<center><h1>Entrada de Chamados</h1></center>

<p>
<div id='loading'></div>
<center>


<?
if(strlen($msg_erro)>0){
	echo "<table class='Tabela' width='700' cellspacing='0'  cellpadding='0' align='center'>";
	echo "<tr >";
	echo "<td bgcolor='FFFFFF' width='60'class='Erro'><img src='imagens/proibido2.jpg' align='middle'></td><td  class='Erro' bgcolor='FFFFFF' align='left'> $msg_erro</td>";
	echo "</tr>";
	echo "</table><br>";
}

if(strlen($msg)>0){
	echo "<center><b style='font-size:12px;border:1px solid #999;padding:10px;background-color:#dfdfdf'>$msg</b></center><br>";
}

echo "<center><a href='chamados_distrib.php'><h2 style='padding:3px;text-align:center;font-size:13px;color:white;background-color:#0099CC;width:330px;cursor:hand'>>> Entrada de Chamado <<</h2></a></center>\n";

	// OS não excluída
		$sql =  "SELECT		tbl_posto_fabrica.codigo_posto      AS codigo_posto,
							tbl_posto.nome                      AS nome,
							tbl_distrib_chamado.distrib_chamado AS distrib_chamado,
							tbl_distrib_chamado.nota_fiscal     AS nota_fiscal,
							TO_CHAR(tbl_distrib_chamado.data_emissao,'DD/MM/YYYY')  AS data_emissao,
							TO_CHAR(tbl_distrib_chamado.data_chamado,'DD/MM/YYYY')  AS data_chamado,
							tbl_distrib_chamado.valor_credito   AS valor_credito,
							tbl_distrib_chamado.gerar_credito   AS gerar_credito,
							tbl_distrib_chamado.observacao      AS observacao,
							TO_CHAR(tbl_distrib_chamado.data_credito,'DD/MM/YYYY')  AS data_credito
				FROM tbl_distrib_chamado
				JOIN tbl_posto USING(posto)
				LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = tbl_distrib_chamado.fabrica
				ORDER BY tbl_distrib_chamado.distrib_chamado DESC";
		$res = pg_exec($con,$sql);
		$resultados = pg_numrows($res);

		echo "<br>";
		echo "<center><table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#485989'  >";
		echo "<tr class='Titulo' height='25'>";
		echo "<td>Chamado</td>";
		echo "<td>Data</td>";
		echo "<td>Posto</td>";
		echo "<td>NF</td>";
		echo "<td>Data Emissão</td>";
		echo "<td>Coleta</td>";
		echo "<td>Valor</td>";
		echo "<td>Gerado</td>";
		echo "<td nowrap width='100px' >Imprimir</td>";
		echo "</tr>";

		$total=pg_numrows($res);
		$achou=0;

		for ($i = 0 ; $i < $total ; $i++) {

			$chamado		= trim(pg_result($res,$i,distrib_chamado));
			$posto			= trim(pg_result($res,$i,codigo_posto));
			$nome_posto		= trim(pg_result($res,$i,nome));
			$data_chamado	= trim(pg_result($res,$i,data_chamado));
			$nota_fiscal	= trim(pg_result($res,$i,nota_fiscal));
			$data_emissao	= trim(pg_result($res,$i,data_emissao));
			$observacao		= trim(pg_result($res,$i,observacao));
			$gerar_credito	= trim(pg_result($res,$i,gerar_credito));
			$valor_credito	= trim(pg_result($res,$i,valor_credito));
			$data_credito	= trim(pg_result($res,$i,data_credito));

			if ($i % 2 == 0) 	$cor   = "#F1F4FA";
			else				$cor   = "#F7F5F0";

			$gerar_credito = ($gerar_credito=='t')?"COLETA PAC":"";
			$valor_credito = ($valor_credito>0)?number_format($valor_credito,2,",","."):"";

			if (strlen($valor_credito)>0){
				$data_credito = (strlen($data_credito)==0)?"<a href='$PHP_SELF?gerar=$chamado' onclick='alert(\"ATENÇÃO: Agora o crédito será automaticamente lançado para este posto após confirmação!\"); if (confirm(\"Deseja continuar?\")) return true; else; return false;'>Gerar</a>":"$data_credito";
			}

			echo "<tr class='Conteudo' style='cursor:pointer' height='15' bgcolor='$cor' align='left'  onclick=\"MostraEsconde('dados_$i')\">\n";
			echo "<td nowrap align='center'><b style='font-size:14px'>$chamado</b></td>\n";
			echo "<td nowrap align='center'>$data_chamado</td>\n";
			echo "<td nowrap title='$posto - $nome_posto'>".substr($posto." - ".$nome_posto,0,35)."</td>\n";
			echo "<td nowrap align='center'>$nota_fiscal</td>\n";
			echo "<td nowrap align='center'>$data_emissao</td>\n";
			echo "<td nowrap align='center'>$gerar_credito</td>\n";
			echo "<td nowrap align='right' style='padding-right:5px'>$valor_credito</td>\n";
			echo "<td nowrap align='center'>$data_credito</td>\n";
			echo "<td align='center'><a href='chamados_distrib_imprimir.php?chamado=$chamado' target='_blank' ><img src='../imagens/btn_imprimir.gif' border='0'></a></td>\n";
			echo "</tr>\n";

			$pecas="";
			$sql_peca = "SELECT tbl_peca.referencia AS referencia,
							tbl_peca.descricao AS nome,
							tbl_distrib_chamado_item.quantidade AS quantidade,
							tbl_distrib_chamado_item.ocorrencia AS ocorrencia
						FROM tbl_distrib_chamado_item
						JOIN tbl_peca USING(peca)
						WHERE tbl_distrib_chamado_item.distrib_chamado='$chamado'";
			$res_peca = pg_exec($con,$sql_peca);
			$resultado = pg_numrows($res_peca);
			if ($resultado>0){
				echo "<tr class='Conteudo' bgcolor='$cor' align='right' height='0' >\n";
				echo "<td colspan='9'>";
				echo "<div style='display:none' id='dados_$i'>\n";
				echo "<table border='0' cellpadding='2' cellspacing='0' heigth='0' align='right'>\n";
					echo "<tr class='Conteudo' bgcolor='$cor' align='right'>\n";
					echo "<td width='200px' align='center'><b>Referência</b></td>\n";
					echo "<td width='200px' align='left'><b>Peça</b></td>\n";
					echo "<td width='50px' align='center'><b>Qtde</b></td>\n";
					echo "<td width='200px' align='left'><b>Ocorrência</b></td>\n";
					echo "</tr>\n";
				for($j=0;$j<$resultado;$j++){
					$referencia       = trim(pg_result($res_peca,$j,referencia));
					$nome       = trim(pg_result($res_peca,$j,nome));
					$qtde       = trim(pg_result($res_peca,$j,quantidade));
					$ocorrencia       = trim(pg_result($res_peca,$j,ocorrencia));
					echo "<tr class='Conteudo' bgcolor='$cor' align='right'>\n";
					echo "<td align='center'>$referencia</td>\n";
					echo "<td align='left'>$nome</td>\n";
					echo "<td align='center'>$qtde</td>\n";
					echo "<td align='left'>$ocorrencia</td>\n";
					echo "</tr>\n";
				}
				if (strlen($observacao)>0){
					echo "<tr class='Conteudo' bgcolor='$cor' align='right'>\n";
					echo "<td colspan='4'><b>Observação:</b> $observacao</td>\n";
					echo "</tr>\n";
				}


				echo "</table><br><br>\n";
				echo "</div>\n";
				echo "</td>";
				echo "</tr>\n";
			}
		}
		echo "</table>";


/*	##### PAGINAÇÃO - INÍCIO #####
	echo "<br>";
	echo "<div>";

	if($pagina < $max_links) $paginacao = pagina + 1;
	else                     $paginacao = pagina;

	// pega todos os links e define que 'Próxima' e 'Anterior' serão exibidos como texto plano
	$todos_links		= $mult_pag->Construir_Links("strings", "sim");

	// função que limita a quantidade de links no rodape
	$links_limitados	= $mult_pag->Mostrar_Parte($todos_links, $coluna, $max_links);

	for ($n = 0; $n < count($links_limitados); $n++) {
		echo "<font color='#DDDDDD'>".$links_limitados[$n]."</font>&nbsp;&nbsp;";
	}

	echo "</div>";

	$resultado_inicial = ($pagina * $max_res) + 1;
	$resultado_final   = $max_res + ( $pagina * $max_res);
	$registros         = $mult_pag->Retorna_Resultado();

	$valor_pagina   = $pagina + 1;
	$numero_paginas = intval(($registros / $max_res) + 1);

	if ($valor_pagina == $numero_paginas) $resultado_final = $registros;

	if ($registros > 0){
		echo "<br>";
		echo "<div>";
		echo "Resultados de <b>$resultado_inicial</b> a <b>$resultado_final</b> do total de <b>$registros</b> registros.";
		echo "<font color='#cccccc' size='1'>";
		echo " (Página <b>$valor_pagina</b> de <b>$numero_paginas</b>)";
		echo "</font>";
		echo "</div>";
	}
	##### PAGINAÇÃO - FIM #####*/

?>
<br><br><br>
</center>

</body>
</html>
