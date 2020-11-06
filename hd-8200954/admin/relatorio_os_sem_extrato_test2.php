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

	if (strlen($_GET["data_inicial_01"]) == 0)$erro .= "Favor informar a data inicial para pesquisa<br>";
	if ($_GET["data_inicial_01"] == 'dd/mm/aaaa') $erro .= "Favor informar a data inicial para pesquisa<br>";
	if ($_GET["data_final_01"] == 'dd/mm/aaaa')   $erro .= "Favor informar a data final para pesquisa<br>";

	if (strlen($erro) == 0) {
		$data_inicial   = trim($_GET["data_inicial_01"]);
		$fnc            = @pg_exec($con,"SELECT fnc_formata_data('$data_inicial')");

		if (strlen ( pg_errormessage ($con) ) > 0) {
			$erro = pg_errormessage ($con) ;
		}

		if (strlen($erro) == 0) $aux_data_inicial = @pg_result ($fnc,0,0);
	}

	$codigo_posto = $_GET["codigo_posto"];
	$cond_1 = " 1=1 ";
	if(strlen($codigo_posto) > 0){
		$cond_1 = " tbl_posto_fabrica.codigo_posto = '$codigo_posto' ";
	}

	if (strlen($erro) == 0) {
		if (strlen($_GET["data_final_01"]) == 0) $erro .= "Favor informar a data final para pesquisa<br>";
		if (strlen($erro) == 0) {
			$data_final   = trim($_GET["data_final_01"]);
			$fnc            = @pg_exec($con,"SELECT fnc_formata_data('$data_final')");

			if (strlen ( pg_errormessage ($con) ) > 0) {
				$erro = pg_errormessage ($con) ;
			}

			if (strlen($erro) == 0) $aux_data_final = @pg_result ($fnc,0,0);
		}
	}



	if (strlen($erro) > 0) {
		$data_inicial = trim($_GET["data_inicial_01"]);
		$data_final   = trim($_GET["data_final_01"]);

		$msg  = "<b>Foi(foram) detectado(s) o(s) seguinte(s) erro(s): </b><br>";
		$msg .= $erro;


	}else $listar = "ok";
	if ($listar == "ok") {


		$sql = "SELECT DISTINCT tbl_os.sua_os,
				tbl_posto.nome,
				to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura,
				to_char(tbl_os.data_digitacao,'DD/MM/YYYY') AS data_digitacao,
				to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento,
				to_char(tbl_os.finalizada,'DD/MM/YYYY') AS finalizada
				FROM tbl_os
				JOIN tbl_posto using(posto)
				JOIN tbl_os_extra USING(os)
				LEFT JOIN tbl_os_produto USING(os)
				LEFT JOIN tbl_os_item USING(os_produto)
				LEFT JOIN tbl_pedido USING(pedido)
				WHERE tbl_os.fabrica = $login_fabrica
				AND tbl_os_extra.extrato IS NULL
				AND tbl_os.data_fechamento IS NOT NULL
				AND tbl_os.finalizada IS NOT NULL
				AND tbl_os.mao_de_obra IS NOT NULL
				AND tbl_os.pecas IS NOT NULL
				AND (tbl_os.excluida IS FALSE OR tbl_os.excluida IS NULL)
				AND (tbl_os_item.pedido is NULL)
				AND tbl_os.os NOT IN (
				SELECT interv_reinc.os
				FROM (
				SELECT 
				ultima_reinc.os, 
				(SELECT status_os FROM tbl_os_status WHERE tbl_os_status.os = ultima_reinc.os AND status_os IN (13,19,67,68,70) ORDER BY data DESC LIMIT 1) AS ultimo_reinc_status
				FROM (SELECT DISTINCT os FROM tbl_os_status WHERE status_os IN (13,19,67,68,70) ) ultima_reinc
				) interv_reinc
				WHERE interv_reinc.ultimo_reinc_status IN (13,67,68,70)
				) 
				AND tbl_os.finalizada BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'
				AND $cond_1 
				order by tbl_posto.nome
				";
		//echo "no|".nl2br($sql);
		//exit;
		$res = pg_exec ($con,$sql);

		if (pg_numrows($res) > 0) {
			$total = 0;
			$resposta  .= "<b>Resultado de pesquisa entre os dias $data_inicial e $data_final </b>";
			$resposta  .=  "<br><br>";
			$resposta  .=  "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc'  align='center'>";
			$resposta  .=  "<TR class='Titulo' background='imagens_admin/azul.gif' height='25'>";
			$resposta  .=  "<TD><b>OS</b></TD>";
			$resposta  .=  "<TD><b>Posto</b></TD>";
			$resposta  .=  "<TD><b>Data Abertura</b></TD>";
			$resposta  .=  "<TD><b>Data Digitação</b></TD>";
			$resposta  .=  "<TD><b>Data Fechamento</b></TD>";
			$resposta  .=  "<TD><b>Data Finalizada</b></TD>";
			$resposta  .=  "</TR>";
			$num_os = pg_numrows($res);
			for ($i=0; $i<pg_numrows($res); $i++){
				$sua_os          = trim(pg_result($res,$i,sua_os))                  ;
				$nome			 = trim(pg_result($res,$i,nome))                    ;
				$data_abertura   = trim(pg_result($res,$i,data_abertura))           ;
				$data_digitacao  = trim(pg_result($res,$i,data_digitacao))          ;
				$data_fechamento = trim(pg_result($res,$i,data_fechamento))         ;
				$finalizada      = trim(pg_result($res,$i,finalizada))              ;

				if($cor=="#F1F4FA")$cor = '#F7F5F0';
				else               $cor = '#F1F4FA';

				$resposta  .=  "<TR bgcolor='$cor'class='Conteudo'>";
				$resposta  .=  "<TD align='center' nowrap>$sua_os</TD>";
				$resposta  .=  "<TD align='center' nowrap>$nome</TD>";
				$resposta  .=  "<TD align='center' nowrap>$data_abertura</TD>";
				$resposta  .=  "<TD align='center' nowrap>$data_digitacao</TD>";
				$resposta  .=  "<TD align='center' nowrap>$data_fechamento</TD>";
				$resposta  .=  "<TD align='center' nowrap>$finalizada</TD>";
				$resposta  .=  "</TR>";

			}
			$resposta  .=  "<TR bgcolor='$cor'class='Conteudo'>";
			$resposta  .=  "<TD align='center' nowrap colspan=4>Total</TD>";
			$resposta  .=  "<TD align='center' nowrap>$num_os</TD>";
			$resposta  .=  "</TR>";
			$resposta .= " </TABLE>";

			$resposta .=  "<br>";
			$resposta .=  "<hr width='600'>";
			$resposta .=  "<br>";

			// monta URL
			$data_inicial = trim($_POST["data_inicial_01"]);
			$data_final   = trim($_POST["data_final_01"]);

		}else{
			$resposta .=  "<br>";
			$resposta .= "<b>Nenhum resultado encontrado entre $data_inicial e $data_final $mostraMsgLinha $mostraMsgEstado</b>";
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
$title = "RELATÓRIO DAS OSs SEM EXTRATO";

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
</style>



<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
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
					com2.style.visibility = "visible";
					com3.style.visibility = "hidden";
				}
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
	var var3 = document.frm_relatorio.codigo_posto.value;

	var parametros = 'data_inicial_01='+var1+'&data_final_01='+var2+'&ajax=sim'+'&codigo_posto='+var3;

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
	});

});
</script>


<FORM name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">
<div id='erro' style='position: absolute; top: 150px; left: 80px;visibility:hidden;opacity:.85;' class='Erro'></div>
<div id='carregando' style='position: absolute;visibility:hidden;opacity:.90;' class='Carregando'></div>
<table width='600' class='Conteudo' style='background-color: #485989' border='0' cellpadding='5' cellspacing='1' align='center'>
	<tr>
		<td class='Titulo' background='imagens_admin/azul.gif'>Relatório das OSs sem extrato</td>
	</tr>

	<tr>
		<td bgcolor='#DBE5F5' valign='bottom'>

			<table width='100%' border='0' cellspacing='1' cellpadding='2' >

				<tr class="Conteudo" bgcolor="#D9E2EF">
					<td width="10">&nbsp;</td>
					<td align='right'><font size='2'>Data Inicial</td>
					<td align='left'>
						<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='Caixa' value="<? if (strlen($data_inicial) > 0) echo $data_inicial; ?>" >
					</td>
					<td align='right'><font size='2'>Data Final</td>
					<td align='left'>
						<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='Caixa' value="<? if (strlen($data_final) > 0) echo $data_final;  ?>" >
					</td>
					<td width="10">&nbsp;</td>
				</tr>
				<tr>
					<td width="10">&nbsp;</td>
					<td align='right' nowrap><font size='2'>Código Posto</td>
					<td align='left'>
						<input type="text" name="codigo_posto" id="codigo_posto" size="12"  value="<? echo $codigo_posto ?>" class="Caixa">
						<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_relatorio.codigo_posto, document.frm_relatorio.posto_nome, 'codigo')">
					</td>
					<td align='right' nowrap><font size='2'>Nome do Posto</td>
					<td align='left'>
						<input type="text" name="posto_nome" id="posto_nome" size="30"  value="<?echo $posto_nome?>" class="Caixa">
						<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_relatorio.codigo_posto, document.frm_relatorio.posto_nome, 'nome')">
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