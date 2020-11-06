<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="financeiro";
include 'autentica_admin.php';

# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
	$tipo_busca = $_GET["busca"];

	if (strlen($q)>2){
		$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";

		if ($tipo_busca == "codigo"){
			$sql .= " AND tbl_posto_fabrica.codigo_posto = '$q' ";
		}else{
			$sql .= " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";
		}

		$res = pg_exec($con,$sql);
		if (pg_numrows ($res) > 0) {
			for ($i=0; $i<pg_numrows ($res); $i++ ){
				$cnpj			= trim(pg_result($res,$i,cnpj));
				$nome			= trim(pg_result($res,$i,nome));
				$codigo_posto	= trim(pg_result($res,$i,codigo_posto));
				echo "$codigo_posto|$nome|$cnpj";
				echo "\n";
			}
		}
	}
	exit;
}



if($_GET['ajax']=='sim') {
	if(strlen($_GET["data_inicial_01"])==0 and strlen($_GET["data_final_01"])==0 and strlen($_GET['codigo_posto'])==0 and strlen($_GET['extrato']) ==0) {
		$erro = " Por favor, informar parametros para pesquisa.";
	}

	if (strlen($erro) == 0) {
		$data_inicial   = trim($_GET["data_inicial_01"]);
		$fnc            = @pg_exec($con,"SELECT fnc_formata_data('$data_inicial')");

		if (strlen ( pg_errormessage ($con) ) > 0) {
			$erro = pg_errormessage ($con) ;
			if (strlen($erro) > 0) {
				$erro = "Data Inválida";
			}
		}

		if (strlen($erro) == 0) $aux_data_inicial = @pg_result ($fnc,0,0);
	}

	$extrato = trim($_GET['extrato']);
	$cond_1 = " 1=1 ";
	if(strlen($extrato)>0){
		$cond_1 = " tbl_extrato_lancamento.extrato = $extrato ";
	}

	$codigo_posto = trim($_GET["codigo_posto"]);
	$cond_2 = " 1=1 ";
	if(strlen($codigo_posto) > 0){
		$cond_2 = " tbl_posto_fabrica.codigo_posto = '$codigo_posto' ";
	}

	if (strlen($erro) == 0) {

		if (strlen($erro) == 0) {
			$data_final   = trim($_GET["data_final_01"]);
			$fnc            = @pg_exec($con,"SELECT fnc_formata_data('$data_final')");

			if (strlen ( pg_errormessage ($con) ) > 0) {
				$erro = pg_errormessage ($con) ;
				if (strlen($erro) > 0) {
					$erro = "Data Inválida";
				}
			}

			if (strlen($erro) == 0) $aux_data_final = @pg_result ($fnc,0,0);
		}
	}

	if(strlen($aux_data_inicial) > 0 and strlen($aux_data_final) > 0) {
		$cond_3 = " AND tbl_extrato_lancamento.data_lancamento BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59' ";
	}
	
	if (strlen($erro) == 0) {
		if($aux_data_inicial > $aux_data_final)
			$erro = "Data Inválida";
	}
	

	if (strlen($erro) > 0) {
		$data_inicial = trim($_GET["data_inicial_01"]);
		$data_final   = trim($_GET["data_final_01"]);
				
		//$msg  = "<b>Foi(foram) detectado(s) o(s) seguinte(s) erro(s): </b><br>";
		$msg = $erro;


	}else $listar = "ok";
	if ($listar == "ok") {
				$sql = "SELECT tbl_posto_fabrica.codigo_posto                           ,
						tbl_posto.nome                                                  ,
						tbl_extrato.extrato                                             ,
						tbl_extrato.protocolo                                           ,
						tbl_extrato_lancamento.valor                                    ,
						tbl_extrato_lancamento.historico                                ,
						tbl_extrato_lancamento.debito_credito                           ,
						TO_CHAR(tbl_extrato_lancamento.data_lancamento,'DD/MM/YY') AS data_lancamento
					FROM tbl_extrato_lancamento
					LEFT JOIN tbl_extrato USING (extrato)
					JOIN tbl_posto         ON tbl_extrato_lancamento.posto = tbl_posto.posto
					JOIN tbl_posto_fabrica ON tbl_extrato_lancamento.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
					LEFT JOIN tbl_extrato_extra ON tbl_extrato.extrato = tbl_extrato_extra.extrato
					WHERE tbl_extrato_lancamento.fabrica = $login_fabrica
					AND $cond_1
					AND $cond_2
					$cond_3
					ORDER BY tbl_posto_fabrica.codigo_posto";

		$res = pg_exec ($con,$sql);

		if (pg_numrows($res) > 0) {
			$total = 0;

			$resposta  .=  "<br><br>";
			$resposta  .=  "<table border='0' width='100%' cellpadding='2' cellspacing='1' class='tabela'  align='center' >";
			$resposta  .= "<tr class='titulo_tabela'><td colspan='5'>Crédito</td></tr>";
			$resposta  .= "<TR class='titulo_coluna' height='25'>";
			$resposta  .= "<Th><b>Posto</b></Th>";
			$resposta  .= "<th><b>Extrato</b></th>";
			$resposta  .= "<th><b>Data</b></th>";
			$resposta  .= "<th><b>Valor</b></th>";
			$resposta  .= "<th><b>Motivo</b></th>";
			$resposta  .= "</TR>";
			$resposta  .= "<tbody>";
			for ($i=0; $i<pg_numrows($res); $i++){
				$codigo_posto    = trim(pg_result($res,$i,codigo_posto))   ;
				$nome            = trim(pg_result($res,$i,nome))           ;
				$historico       = trim(pg_result($res,$i,historico))      ;
				$valor           = trim(pg_result($res,$i,valor))          ;
				$extrato         = trim(pg_result($res,$i,extrato))        ;
				$protocolo       = trim(pg_result($res,$i,protocolo))      ;
				$data_lancamento = trim(pg_result($res,$i,data_lancamento));
				$debito_credito  = trim(pg_result($res,$i,debito_credito));

				if($cor=="#F1F4FA")$cor = '#F7F5F0';
				else               $cor = '#F1F4FA';
				if($debito_credito =='C') {
					$resposta  .=  "<TR bgcolor='$cor'>";
					$resposta  .=  "<TD align='left'nowrap>$codigo_posto - $nome</TD>";
					$resposta  .=  "<TD align='center' >"; if(strlen($extrato) > 0) {$resposta .= "$extrato"; }else{ $resposta .= "-";} $resposta .= "</TD>";
					$resposta  .=  "<TD align='right'>$data_lancamento</TD>";
					$resposta  .=  "<TD nowrap>R$". number_format($valor,2,",",".") ." </TD>";
					$resposta  .=  "<TD align='left'>$historico</TD>";
					$resposta  .=  "</TR>";
				}

			}
			$resposta .="</tbody>";
			$resposta .= " </TABLE>";

			$resposta .=  "<br>";
			$resposta .=  "<hr width='100%'>";
			$resposta .=  "<br>";

			$resposta  .=  "<table border='0' cellpadding='2' cellspacing='1' class='tabela'  align='center' >";
			$resposta  .= "<tr class='titulo_tabela'><td colspan='5'>Débito</td></tr>";
			$resposta  .= "<TR class='titulo_coluna' height='25'>";
			$resposta  .= "<Th><b>Posto</b></Th>";
			$resposta  .= "<th><b>Extrato</b></th>";
			$resposta  .= "<th><b>Data</b></th>";
			$resposta  .= "<th><b>Valor</b></th>";
			$resposta  .= "<th><b>Motivo</b></th>";
			$resposta  .= "</TR>";
			$resposta  .= "<tbody>";
			for ($i=0; $i<pg_numrows($res); $i++){
				$codigo_posto    = trim(pg_result($res,$i,codigo_posto))   ;
				$nome            = trim(pg_result($res,$i,nome))           ;
				$historico       = trim(pg_result($res,$i,historico))      ;
				$valor           = trim(pg_result($res,$i,valor))          ;
				$extrato         = trim(pg_result($res,$i,extrato))        ;
				$protocolo       = trim(pg_result($res,$i,protocolo))      ;
				$data_lancamento = trim(pg_result($res,$i,data_lancamento));
				$debito_credito  = trim(pg_result($res,$i,debito_credito));

				if($cor=="#F1F4FA")$cor = '#F7F5F0';
				else               $cor = '#F1F4FA';
				if($debito_credito =='D') {
					$resposta  .=  "<TR bgcolor='$cor'>";
					$resposta  .=  "<TD align='left'nowrap>$codigo_posto - $nome</TD>";
					$resposta  .=  "<TD align='center' >"; if(strlen($extrato) > 0) {$resposta .= "$extrato"; }else{ $resposta .= "-";} $resposta .= "</TD>";
					$resposta  .=  "<TD align='right'>$data_lancamento</TD>";
					$resposta  .=  "<TD nowrap>R$ ". number_format($valor,2,",",".") ." </TD>";
					$resposta  .=  "<TD align='left'>$historico</TD>";
					$resposta  .=  "</TR>";
				}
			}

			$resposta .="</tbody>";
			$resposta .= " </TABLE>";
			$resposta .= "<p>";
			flush();
			$data = date ("d/m/Y H:i:s");

			$arquivo_nome     = "relatorio-lancamento-avulso-$login_fabrica.xls";
			$path             = "/www/assist/www/admin/xls/";
			$path_tmp         = "/tmp/";

			$arquivo_completo     = $path.$arquivo_nome;
			$arquivo_completo_tmp = $path_tmp.$arquivo_nome;

			echo `rm $arquivo_completo_tmp `;
			echo `rm $arquivo_completo `;

			$fp = fopen ($arquivo_completo_tmp,"w");

			fputs ($fp,"<html>");
			fputs ($fp,"<head>");
			fputs ($fp,"<title>Relatório de Lançamento Avulso - $data");
			fputs ($fp,"</title>");
			fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
			fputs ($fp,"</head>");
			fputs ($fp,"<body>");
			fputs ($fp,"<table border='0' cellpadding='2' cellspacing='0' class='tabela' align='center' >");
			fputs ($fp,"<tr class='titulo_tabela'><td colspan='5'>Crédito</td></tr>");
			fputs ($fp,"<TR class='titulo_coluna' height='25'>");
			fputs ($fp,"<Th><b>Posto</b></Th>");
			fputs ($fp,"<th><b>Extrato</b></th>");
			fputs ($fp,"<th><b>Data</b></th>");
			fputs ($fp,"<th><b>Valor</b></th>");
			fputs ($fp,"<th><b>Motivo</b></th>");
			fputs ($fp,"</TR>");
			fputs ($fp,"<tbody>");
			for ($i=0; $i<pg_numrows($res); $i++){
				$codigo_posto    = trim(pg_result($res,$i,codigo_posto))   ;
				$nome            = trim(pg_result($res,$i,nome))           ;
				$historico       = trim(pg_result($res,$i,historico))      ;
				$valor           = trim(pg_result($res,$i,valor))          ;
				$extrato         = trim(pg_result($res,$i,extrato))        ;
				$protocolo       = trim(pg_result($res,$i,protocolo))      ;
				$data_lancamento = trim(pg_result($res,$i,data_lancamento));
				$debito_credito  = trim(pg_result($res,$i,debito_credito));

				if($cor=="#F1F4FA")$cor = '#F7F5F0';
				else               $cor = '#F1F4FA';
				if($debito_credito =='C') {
					fputs ($fp, "<TR bgcolor='$cor'class='Conteudo'>");
					fputs ($fp, "<TD align='left'nowrap>$codigo_posto - $nome</TD>");
					fputs ($fp, "<TD align='center' >"); if(strlen($extrato) > 0) {fputs ($fp, "$extrato"); }else{ fputs ($fp, "-");} fputs ($fp, "</TD>");
					fputs ($fp, "<TD align='right'>$data_lancamento</TD>");
					fputs ($fp, "<TD nowrap>R$". number_format($valor,2,",",".") ." </TD>");
					fputs ($fp, "<TD align='left'>$historico</TD>");
					fputs ($fp, "</TR>");
				}

			}
			fputs ($fp,"</tbody>");
			fputs ($fp, " </TABLE>");

			fputs ($fp,  "<br>");
			fputs ($fp,  "<hr width='600'>");
			fputs ($fp,  "<br>");

			fputs ($fp,  "<table border='0' cellpadding='2' cellspacing='1' class='tabela'  align='center' >");
			fputs ($fp, "<tr class='titulo_tabela'><td colspan='5'>Débito</td></tr>");
			fputs ($fp, "<TR class='titulo_coluna' height='25'>");
			fputs ($fp, "<Th><b>Posto</b></Th>");
			fputs ($fp, "<th><b>Extrato</b></th>");
			fputs ($fp, "<th><b>Data</b></th>");
			fputs ($fp, "<th><b>Valor</b></th>");
			fputs ($fp, "<th><b>Motivo</b></th>");
			fputs ($fp, "</TR>");
			fputs ($fp, "<tbody>");
			for ($i=0; $i<pg_numrows($res); $i++){
				$codigo_posto    = trim(pg_result($res,$i,codigo_posto))   ;
				$nome            = trim(pg_result($res,$i,nome))           ;
				$historico       = trim(pg_result($res,$i,historico))      ;
				$valor           = trim(pg_result($res,$i,valor))          ;
				$extrato         = trim(pg_result($res,$i,extrato))        ;
				$protocolo       = trim(pg_result($res,$i,protocolo))      ;
				$data_lancamento = trim(pg_result($res,$i,data_lancamento));
				$debito_credito  = trim(pg_result($res,$i,debito_credito));

				if($cor=="#F1F4FA")$cor = '#F7F5F0';
				else               $cor = '#F1F4FA';
				if($debito_credito =='D') {
					fputs ($fp,  "<TR bgcolor='$cor'class='Conteudo'>");
					fputs ($fp,  "<TD align='left'nowrap>$codigo_posto - $nome</TD>");
					fputs ($fp,  "<TD align='center' >"); if(strlen($extrato) > 0) {fputs ($fp, "$extrato"); }else{ fputs ($fp, "-");} fputs ($fp, "</TD>");
					fputs ($fp,  "<TD align='right'>$data_lancamento</TD>");
					fputs ($fp,  "<TD nowrap>R$". number_format($valor,2,",",".") ." </TD>");
					fputs ($fp,  "<TD align='left'>$historico</TD>");
					fputs ($fp,  "</TR>");
				}
			}

			echo ` cp $arquivo_completo_tmp $path `;
			$data = date("Y-m-d").".".date("H-i-s");

			echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f $arquivo_completo $arquivo_completo_tmp `;
			$resposta .= "<br>";
			$resposta .="<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
			$resposta .="<tr>";
			$resposta .= "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font><a href='xls/$arquivo_nome'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em EXCEL</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</font></td>";
			$resposta .= "</tr>";
			$resposta .= "</table>";
		}else{
			$resposta .=  "<br>";
			$resposta .= "<b>Nenhum resultado encontrado</b>";
		}
		$listar = "";

	}
	if (strlen($erro) > 0) {
		echo "no|".$msg;
	}else{
//		$resposta .=  "$sql";
		echo "ok|".$resposta;
	}
	exit;

	flush();

}

$layout_menu = "financeiro";
$title = "RELATÓRIO DE LANÇAMENTOS AVULSOS DO EXTRATO";

include "cabecalho.php";

?>

<style>
.Titulo {
	text-align: center;
	font-family: Arial;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #485989;
}
.Conteudo {
	font-family: Arial;
	font-size: 10px;
	font-weight: normal;
}
.ConteudoBranco {
	font-family: Arial;
	font-size: 9px;
	color:#FFFFFF;
	font-weight: normal;
}
.Mes{
	font-size: 9px;
}
.Caixa{
	BORDER-RIGHT: #6699CC 1px solid;
	BORDER-TOP: #6699CC 1px solid;
	FONT: 8pt Arial ;
	BORDER-LEFT: #6699CC 1px solid;
	BORDER-BOTTOM: #6699CC 1px solid;
	BACKGROUND-COLOR: #FFFFFF;
}
.Exibe{
	font-family: Arial, Helvetica, sans-serif;
	font-size: 8 px;
	font-weight: none;
	color: #000000;
	text-align: center;
}
.Erro{
	BORDER-RIGHT: #990000 1px solid;
	BORDER-TOP: #990000 1px solid;
	FONT: 10pt Arial ;
	COLOR: #ffffff;
	BORDER-LEFT: #990000 1px solid;
	BORDER-BOTTOM: #990000 1px solid;
	BACKGROUND-COLOR: #FF0000;
}
.Carregando{
	TEXT-ALIGN: center;
	BORDER-RIGHT: #aaa 1px solid;
	BORDER-TOP: #aaa 1px solid;
	FONT: 10pt Arial ;
	COLOR: #000000;
	BORDER-LEFT: #aaa 1px solid;
	BORDER-BOTTOM: #aaa 1px solid;
	BACKGROUND-COLOR: #FFFFFF;
	margin-left:20px;
	margin-right:20px;
}

.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}


.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}



.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
}

.subtitulo{

color: #7092BE
}

table.tabela tr td{
font-family: verdana;
font-size: 11px;
border-collapse: collapse;
border:1px solid #596d9b;
}
</style>




<script language='javascript' src='../ajax.js'></script>
<script language='javascript'>

function retornaPesquisa (http,componente,componente_erro,componente_carregando) {
	var com = document.getElementById(componente);
	var com2 = document.getElementById(componente_erro);
	var com3 = document.getElementById(componente_carregando);
	if (http.readyState == 1) {

		Page.getPageCenterX() ;
		com3.style.top = (Page.top + Page.height/2)-100;
		com3.style.left = Page.width/2-75;
		com3.style.position = "absolute";

		com3.innerHTML   = "&nbsp;&nbsp;Carregando...&nbsp;&nbsp;<br><img src='../imagens/carregar_os.gif' >";
		com3.style.visibility = "visible";
	}
	if (http.readyState == 4) {
		if (http.status == 200) {

			results = http.responseText.split("|");
			if (typeof (results[0]) != 'undefined') {
				if (results[0] == 'ok') {
					com.innerHTML   = " "+results[1];
					com2.innerHTML  = " ";
					com2.style.visibility = "hidden";
					document.getElementById('teste').style.display = "none";
					com3.style.zIndex = "3";
					com3.innerHTML = "<br>&nbsp;&nbsp;Informações carregadas com sucesso!&nbsp;&nbsp;<br>&nbsp;&nbsp;";
					setTimeout('esconde_carregar()',1500);
				}
				if (results[0] == 'no') {
					Page.getPageCenterX() ;
					com2.style.top = (Page.top + Page.height/2)-100;
					com2.style.left = Page.width/2-75;
					com2.style.position = "absolute";

					com2.innerHTML   = " "+results[1];
					com.innerHTML   = " ";
					//com2.style.display = "block";

					$('#teste').html(results[1]);
					document.getElementById('teste').style.display = "block";
					com3.style.visibility = "hidden";
				}

			}else{
				alert ('Fechamento nao processado');
			}
		}
	}
}
function esconde_carregar(componente_carregando) {
	document.getElementById('carregando').style.visibility = "hidden";
}

function Exibir (componente,componente_erro,componente_carregando,fabrica) {
	var var1 = document.frm_relatorio.data_inicial.value;
	var var2 = document.frm_relatorio.data_final.value;
	var var4 = document.frm_relatorio.extrato.value;
	var var5 = document.frm_relatorio.codigo_posto.value;
	
	var data = new Date();

	var parametros = 'data_inicial_01='+var1+'&data_final_01='+var2+'&extrato='+var4+'&ajax=sim'+'&codigo_posto='+var5+'&data='+data.getTime();

	url = "<?=$PHP_SELF?>?"+parametros;

	http.open("GET", url , true);
	http.onreadystatechange = function () { retornaPesquisa (http,componente,componente_erro,componente_carregando) ; } ;
	http.send(null);
}

var Page = new Object();
Page.width;
Page.height;
Page.top;

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

<? include "javascript_pesquisas.php" ?>

<? include "javascript_calendario.php"; //adicionado por Fabio 27-09-2007 ?>

<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial').datePicker({startDate:'01/01/2000'});
		$('#data_final').datePicker({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");
	});
</script>


<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>

<script language="JavaScript">
$().ready(function() {

	function formatItem(row) {
		return row[0] + " - " + row[1];
	}

	function formatResult(row) {
		return row[0];
	}

	/* Busca pelo Código */
	$("#codigo_posto").autocomplete("<?echo $PHP_SELF.'?busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[0];}
	});

	$("#codigo_posto").result(function(event, data, formatted) {
		$("#posto_nome").val(data[1]) ;
	});

	/* Busca pelo Nome */
	$("#posto_nome").autocomplete("<?echo $PHP_SELF.'?busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#posto_nome").result(function(event, data, formatted) {
		$("#codigo_posto").val(data[0]) ;
		//alert(data[2]);
	});

});
</script>


<FORM name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">
<div id='erro' style='display: none' class='msg_erro'></div>
<div id='carregando' style='position: absolute;visibility:hidden;opacity:.90;' class='Carregando'></div>
<table width='700' class='formulario' border='0' cellpadding='5' cellspacing='1' align='center'>
	<tr id="teste" style="display:none;" class="msg_erro">
	<td >
		
	</td></tr>
	<tr>
		<td class='titulo_tabela'>Parâmetros de Pesquisa</td>
	</tr>

	<tr>
		<td valign='bottom'>

			<table width='100%' border='0' cellspacing='1' cellpadding='2' >

				<tr>
					<td width="10">&nbsp;</td>
					<td align='right'><font size='2'>Data Inicial</td>
					<td align='left'>
						<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='frm' value="<? if (strlen($data_inicial) > 0) echo $data_inicial; ?>" >
					</td>
					<td align='right'><font size='2'>Data Final</td>
					<td align='left'>
						<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='frm' value="<? if (strlen($data_final) > 0) echo $data_final;  ?>" >
					</td>
					<td width="10">&nbsp;</td>
				</tr>
				<tr>
					<td width="10">&nbsp;</td>
					<td align='right' nowrap><font size='2'>Código Posto</td>
					<td align='left'>
						<input type="text" name="codigo_posto" id="codigo_posto" size="12"  value="<? echo $codigo_posto ?>" class="frm">
						<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_relatorio.codigo_posto, document.frm_relatorio.posto_nome, 'codigo')">
					</td>
					<td align='right' nowrap><font size='2'>Nome do Posto</td>
					<td align='left'>
						<input type="text" name="posto_nome" id="posto_nome" size="30"  value="<?echo $posto_nome?>" class="frm">
						<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_relatorio.codigo_posto, document.frm_relatorio.posto_nome, 'nome')">
					</td>
					<td width="10">&nbsp;</td>
				</tr>
				<tr>
					<td width="10">&nbsp;</td>
					<td align='right'nowrap><font size='2'>Extrato</td>
					<td align='left'  colspan='3'>
					<input type='text' name='extrato' value='<?echo $extrato;?>' size='10' maxlength='10' class="frm">
					</td>
					<td width="10">&nbsp;</td>
				</tr>
				</table><br>
			<input type='button' onclick="javascript:Exibir('dados','erro','carregando','<?=$login_fabrica?>');" style="cursor:pointer " value='Consultar'>
		</td>
	</tr>
</table>
</FORM>


<?

echo "<div id='dados'></div>";


?>
<p>



<? include "rodape.php" ?>
