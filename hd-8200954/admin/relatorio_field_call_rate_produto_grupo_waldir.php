
<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$body_onload = "initIt()";

$admin_privilegios="gerencia";

	include "autentica_admin.php";




include "funcoes.php";

$msg_erro = "";

$meses = array (1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

if (strlen($_POST["btn_acao"]) > 0) $btn_acao = strtoupper($_POST["btn_acao"]);

if (strlen($btn_acao) > 0) {
	if (strlen(trim($_POST["data_inicial"])) > 0) $x_data_inicial = trim($_POST["data_inicial"]);
	if (strlen(trim($_GET["data_inicial"])) > 0) $x_data_inicial = trim($_GET["data_inicial"]);

	if (strlen(trim($_POST["data_final"])) > 0) $x_data_final = trim($_POST["data_final"]);
	if (strlen(trim($_GET["data_final"])) > 0) $x_data_final = trim($_GET["data_final"]);

	$x_data_inicial_aux = $x_data_inicial;
	$x_data_final_aux   = $x_data_final;

	if (strlen(trim($_POST["produtos_pesquisa"])) > 0) $produtos_pesquisa = trim($_POST["produtos_pesquisa"]);
	if (strlen(trim($_GET["produtos_pesquisa"])) > 0) $produtos_pesquisa = trim($_GET["produtos_pesquisa"]);

	if (strlen(trim($_POST["tipo_os"])) > 0) $tipo_os = trim($_POST["tipo_os"]);
	if (strlen(trim($_GET["tipo_os"])) > 0) $tipo_os = trim($_GET["tipo_os"]);


	#$produtos_pesquisa_aux = str_replace(" ","#",$produtos_pesquisa);
	$produtos_pesquisa = str_replace('$',"' , '",$produtos_pesquisa);
	$produtos_pesquisa = "'" . $produtos_pesquisa . "'";
	$produto_referencia = $produtos_pesquisa;
	$cond_5 = "1=1";
	if (strlen ($tipo_os)  > 0) $cond_5 = " tbl_os.consumidor_revenda = '$tipo_os' ";
	
	if ($x_data_inicial != "dd/mm/aaaa" && $x_data_final != "dd/mm/aaaa") {

		if (strlen($x_data_inicial) > 0) {
			$x_data_inicial = fnc_formata_data_pg($x_data_inicial);
			$x_data_inicial = str_replace("'", "", $x_data_inicial);
			$dia_inicial    = substr($x_data_inicial, 8, 2);
			$mes_inicial    = substr($x_data_inicial, 5, 2);
			$ano_inicial    = substr($x_data_inicial, 0, 4);
			$data_inicial = date("01/m/Y H:i:s", mktime(0, 0, 0, $mes_inicial, $dia_inicial, $ano_inicial));
		}else{
			$msg_erro .= " Preencha o campo Data Inicial para realizar a pesquisa. ";
		}

		if (strlen($x_data_final) > 0) {
			$x_data_final = fnc_formata_data_pg($x_data_final);
			$x_data_final = str_replace("'", "", $x_data_final);
			$dia_final    = substr($x_data_final, 8, 2);
			$mes_final    = substr($x_data_final, 5, 2);
			$ano_final    = substr($x_data_final, 0, 4);
			$data_final   = date("t/m/Y H:i:s", mktime(23, 59, 59, $mes_final, $dia_final, $ano_final));
		}else{
			$msg_erro .= " Preencha o campo Data Final para realizar a pesquisa. ";
		}
	}else{
		$msg_erro .= " Informe as datas corretas para realizar a pesquisa. ";
	}
	
	$sql = "SELECT '$x_data_inicial'::date >='$x_data_final'::date - interval '18 months'";
	$res = pg_query($con,$sql);
	if(pg_num_rows($res) > 0){
		if(pg_fetch_result($res,0,0) == 'f') {
			$msg_erro = "O intervalo entre data inicial e final tem que ser dentro de 18 meses";
		}
	}
}

$layout_menu = "gerencia";
$title = "RELATÓRIO - FIELD CALL-RATE 2 : LINHA DE PRODUTO";

include "cabecalho.php";
?>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}

.table_line {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #596D9B
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	background-color: #596D9B
}
.table_line3 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	background-color: #D9E2EF
}
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: normal;
}
.conteudo10 {
	color: #000000;
	font:12px Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

.bgTRConteudo1{
	background-color: #FEFEFF;
}

.bgTRConteudo2{
	background-color: #F9FCFF;
}
.bgTRConteudo3{
	background-color: #FFCCCC;
}

</style>

<? include "javascript_pesquisas.php"; ?>

<!-- Retirei - Fabio 11/10/2007 - Adicionado outro calendario
<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
-->


<script language="JavaScript">

var singleSelect = true;  // Allows an item to be selected once only
var sortSelect = true;  // Only effective if above flag set to true
var sortPick = true;  // Will order the picklist in sort sequence

// Initialise - invoked on load
function initIt() {
  var selectList = document.getElementById("SelectList");
  var selectOptions = selectList.options;
  var selectIndex = selectList.selectedIndex;
  var pickList = document.getElementById("PickList");
  var pickOptions = pickList.options;
  pickOptions[0] = null;  // Remove initial entry from picklist (was only used to set default width)
  if (!(selectIndex > -1)) {
    selectOptions[0].selected = true;  // Set first selected on load
    selectOptions[0].defaultSelected = true;  // In case of reset/reload
  }
  selectList.focus();  // Set focus on the selectlist
}

// Adds a selected item into the picklist
function addIt() {
  var selectList = document.getElementById("SelectList");
  var selectIndex = selectList.selectedIndex;
  var selectOptions = selectList.options;
  var pickList = document.getElementById("PickList");
  var pickOptions = pickList.options;
  var pickOLength = pickOptions.length;
  // An item must be selected
  while (selectIndex > -1) {
    pickOptions[pickOLength] = new Option(selectList[selectIndex].text);
    pickOptions[pickOLength].value = selectList[selectIndex].value;
    // If single selection, remove the item from the select list
    if (singleSelect) {
      selectOptions[selectIndex] = null;
    }
    if (sortPick) {
      var tempText;
      var tempValue;
      // Sort the pick list
      while (pickOLength > 0 && pickOptions[pickOLength].value < pickOptions[pickOLength-1].value) {
        tempText = pickOptions[pickOLength-1].text;
        tempValue = pickOptions[pickOLength-1].value;
        pickOptions[pickOLength-1].text = pickOptions[pickOLength].text;
        pickOptions[pickOLength-1].value = pickOptions[pickOLength].value;
        pickOptions[pickOLength].text = tempText;
        pickOptions[pickOLength].value = tempValue;
        pickOLength = pickOLength - 1;
      }
    }
    selectIndex = selectList.selectedIndex;
    pickOLength = pickOptions.length;
  }
  selectOptions[0].selected = true;
}

// Deletes an item from the picklist
function delIt() {
  var selectList = document.getElementById("SelectList");
  var selectOptions = selectList.options;
  var selectOLength = selectOptions.length;
  var pickList = document.getElementById("PickList");
  var pickIndex = pickList.selectedIndex;
  var pickOptions = pickList.options;
  while (pickIndex > -1) {
    // If single selection, replace the item in the select list
    if (singleSelect) {
      selectOptions[selectOLength] = new Option(pickList[pickIndex].text);
      selectOptions[selectOLength].value = pickList[pickIndex].value;
    }
    pickOptions[pickIndex] = null;
    if (singleSelect && sortSelect) {
      var tempText;
      var tempValue;
      // Re-sort the select list
      while (selectOLength > 0 && selectOptions[selectOLength].value < selectOptions[selectOLength-1].value) {
        tempText = selectOptions[selectOLength-1].text;
        tempValue = selectOptions[selectOLength-1].value;
        selectOptions[selectOLength-1].text = selectOptions[selectOLength].text;
        selectOptions[selectOLength-1].value = selectOptions[selectOLength].value;
        selectOptions[selectOLength].text = tempText;
        selectOptions[selectOLength].value = tempValue;
        selectOLength = selectOLength - 1;
      }
    }
    pickIndex = pickList.selectedIndex;
    selectOLength = selectOptions.length;
  }
}

// Selection - invoked on submit
function selIt(btn) {
  var pickList = document.getElementById("PickList");
  var pickOptions = pickList.options;
  var pickOLength = pickOptions.length;
  if (pickOLength < 1) {
	alert("É necessário selecionar pelo menos um produto");
	return false;
  }
  for (var i = 0; i < pickOLength; i++) {
	pickOptions[i].selected = true;
  }
  return true;
}

//-->
function conta_produtos(){
  var produtos='';
  var pickList = document.getElementById("PickList");
  var pickOptions = pickList.options;
  var pickOLength = pickOptions.length;
  for (var i = 0; i < pickOLength; i++) {
      produtos += pickOptions[i].value+"$";
  }
document.frm_relatorio.produtos_pesquisa.value = produtos;
//alert(document.frm_relatorio.produtos_pesquisa.value);
}
function AbrePeca(produto,data_inicial,data_final,tipo){
//alert(tipo);
	janela = window.open("relatorio_field_call_rate_pecas_grupo2.php?produto=" + produto + "&data_inicial=" + data_inicial + "&data_final=" + data_final + "&tipo=" +tipo  + "&tipo_os=<?echo $tipo_os;?>" ,"produto",'resizable=1,scrollbars=yes,width=750,height=550,top=0,left=0');
	janela.focus();
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


</script>

<? include "javascript_calendario_new.php"; //adicionado por Fabio 27-09-2007 ?>

<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial').datePicker({startDate:'01/01/2000'});
		$('#data_final').datePicker({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");
	});
</script>
<link rel="stylesheet" href="js/blue/style.css" type="text/css" id="" media="print, projection, screen" />
<script type="text/javascript" src="js/jquery.tablesorter.pack.js"></script> 
<script>
$(document).ready(function(){
	$.tablesorter.defaults.widgets = ['zebra'];
	$("#relatorio").tablesorter();

});
</script>
<body onLoad="initIt();">
<br>

<? if (strlen($msg_erro) > 0) { ?>
<table width="730" border="0" cellpadding="2" cellspacing="2" align="center" class="error">
	<tr>
		<td><?echo $msg_erro?></td>
	</tr>
</table>
<br>
<? } ?>

<form name="frm_relatorio" ID="frm_relatorio" method="post" action="<? echo $PHP_SELF ?>" onSubmit="conta_produtos();return selIt();">
<input type="hidden" name="acao">
<input type="hidden" name="produtos_pesquisa">

<table width="420" border="0" cellspacing="0" cellpadding="2" align="center" class='PesquisaTabela'>
	<caption>Pesquisa</caption>

<tbody>
	<tr>
		<td colspan='3'><center>Este relatório considera a data de geração do extrato aprovado.</center></td>
	</tr>
	<tr>
		<td>
			Data Inicial<br>
			<input type="text" name="data_inicial" id="data_inicial" size="13" maxlength="10" value="<? if (strlen($data_inicial) > 0) echo substr($data_inicial,0,10); ?>" class="frm">
		</td>
		<td></td>
		<td>
			Data Final<br>
			<input type="text" name="data_final" id="data_final" size="13" maxlength="10" value="<? if (strlen($data_final) > 0) echo substr($data_final,0,10);  ?>" class="frm">
		</td>
	</tr>
	<tr>
		<td colspan="3">&nbsp;</td>
	</tr>
	<tr>
		<td>Referência do Produto</td>
		<td></td>
		<td>Produto(s) Selecionado(s)</td>
	</tr>
	<tr>
		<td>

		<select multiple="multiple" size="4" ID="SelectList" NAME="SelectList" style='width:120px'>
			<?  
			$sql = "SELECT tbl_produto.referencia_fabrica
					FROM tbl_produto
					JOIN tbl_familia using(familia)
					WHERE fabrica = $login_fabrica
					AND tbl_produto.ativo='t' 
					GROUP BY tbl_produto.referencia_fabrica
					ORDER by tbl_produto.referencia_fabrica";

			$res = pg_query($con,$sql);
			if(pg_num_rows($res)>0){
				for($x;pg_num_rows($res)>$x;$x++){
					$xreferencia_fabrica = pg_fetch_result($res,$x,referencia_fabrica);
					
					echo "<option value='$xreferencia_fabrica'>$xreferencia_fabrica</option>";
				}
			}
			?>
			</select>
		</td>
		<td width="10" align='center'>
			<input TYPE="BUTTON" VALUE="->" ONCLICK="addIt();"></input>
			<br>
			<input TYPE="BUTTON" VALUE="<-" ONCLICK="delIt();"></input>
		</td>
		<td>
			<select NAME="PickList" ID="PickList" SIZE="4" multiple="multiple" style="width: 120px">
			</select>
		</td>
	</tr>

<? if($login_fabrica==24){ ?>
	<tr>
		<TD colspan = '3'> <center>Por tipo</center></TD>
	</tr>
	<tr>
		<TD colspan = '3' >
			<center>
			<select name="tipo_os" size="1"  style='width:200px'>
			<option value=""></option>
			<option value="C">Consumidor</option>
			<option value="R">Revenda</option>
			</select>
			</center>
		</TD>
  </TR>

<? } ?>



	<tr>
		<td colspan="3">&nbsp;</td>
	</tr>
	<tr>
		<td colspan="3" align='center'>
		<input type='submit' name='btn_acao' value='Pesquisar'>
		<!--<img src="imagens_admin/btn_pesquisar_400.gif" onclick="javascript: document.frm_relatorio.acao.value='PESQUISAR'; document.frm_relatorio.submit();" style="cursor:pointer " alt="Clique AQUI para pesquisar">-->
		</td>
	</tr>
</tbody>
</table>
</form>

<br>

<?
if (strlen($_POST["btn_acao"]) > 0) $btn_acao = strtoupper($_POST["btn_acao"]);

if (strlen($btn_acao) > 0 and empty($msg_erro)) {
	##### Pesquisa entre datas #####

	$sql = "SELECT tbl_extrato.extrato,liberado,total
				INTO TEMP TABLE tmp_relacao_extrato
			FROM tbl_extrato
			WHERE tbl_extrato.data_geracao BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'
			AND tbl_extrato.fabrica = $login_fabrica;
			

			CREATE INDEX tmp_relacao_extrato_extrato ON tmp_relacao_extrato(extrato);

			SELECT tbl_os_extra.os
			INTO TEMP TABLE tmp_relacao_extrato2
			FROM tmp_relacao_extrato
			JOIN tbl_os_extra USING(extrato)
			WHERE tmp_relacao_extrato.liberado is not null
			AND   tmp_relacao_extrato.total > 0;

			CREATE INDEX tmp_relacao_extrato2_os ON tmp_relacao_extrato2(os);

			SELECT tmp_relacao_extrato2.os, 
				tbl_os.produto
			INTO TEMP TABLE tmp_relacao_extrato3
			FROM tmp_relacao_extrato2
			JOIN tbl_os USING(os)
			WHERE tbl_os.fabrica = $login_fabrica
			AND tbl_os.finalizada IS NOT NULL
			AND data_fechamento IS NOT NULL
			and $cond_5
			AND excluida IS NOT TRUE
			AND tbl_os.produto IN(SELECT produto FROM tbl_produto JOIN tbl_linha using(linha) WHERE tbl_linha.fabrica = $login_fabrica AND referencia_fabrica IN($produto_referencia));

			CREATE INDEX tmp_relacao_extrato3_os ON tmp_relacao_extrato3(os);

			SELECT COUNT(os) AS ocorrencia, tbl_produto.referencia_fabrica
			FROM tmp_relacao_extrato3
			JOIN tbl_produto USING(produto)
			WHERE 1=1
			GROUP BY referencia_fabrica;";
	//echo "RELATORIO =".$sql;
	echo nl2br($sql);
	$res = pg_query ($con,$sql);

	echo "<p><b>Relatório gerado em ".date("d/m/Y")." às ".date("H:i")."</b></p>";

	if (pg_num_rows($res) > 0) {
		$total = 0;
		echo "<br>";
		
		echo "<b>Resultado de pesquisa entre ".$x_data_inicial_aux." e ". $x_data_final_aux."</b>";
		
		echo "<br><br>";

		echo "<center><div style='width:750px;'><TABLE width='600' border='0' cellspacing='2' cellpadding='2' align='center' name='relatorio' id='relatorio' class='tablesorter' style=' border:#485989 1px solid; background-color: #e6eef7 '>";
		echo "<thead>";
		echo "<TR>";
		echo "<TD width='5%' height='15' >Referência única</TD>";
		echo "<TD width='30%' height='15' >Referência</TD>";
		echo "<TD width='10%' height='15' ALIGN='CENTER'>Ocorrência</TD>";
		echo "<TD width='05%' height='15'  ALIGN='CENTER'>%</TD>";
		echo "</TR>";
		echo "</thead>";
		
		for ($x = 0; $x < pg_num_rows($res); $x++) {
			$total_ocorrencia = $total_ocorrencia + pg_fetch_result($res,$x,ocorrencia);
		}

		echo "<tbody>";
		for ($i=0; $i<pg_num_rows($res); $i++){
			$referencia_fabrica = trim(pg_fetch_result($res,$i,referencia_fabrica));
			$ocorrencia = trim(pg_fetch_result($res,$i,ocorrencia));

			if ($total_ocorrencia > 0) $porcentagem = (($ocorrencia * 100) / $total_ocorrencia);

			echo "<TR >";
			echo "<TD  align='left'><B><a href='javascript:AbrePeca(\"$referencia_fabrica\",\"$x_data_inicial\",\"$x_data_final\",\"grupo\");'>$referencia_fabrica</a></b></td>";
			echo "<TD align='left'>";
				$sql = "SELECT 
						tbl_produto.referencia , tbl_produto.produto
					FROM  tbl_produto 
					JOIN  tbl_familia using(familia)
					WHERE tbl_familia.fabrica = $login_fabrica
					AND   referencia_fabrica  = '$referencia_fabrica'
					AND   tbl_produto.ativo   IS TRUE";
				$xres = pg_query($con,$sql);

				if(pg_num_rows($xres)>0){
					for($y=0;pg_num_rows($xres)>$y;$y++){
						$ref_produto = pg_fetch_result($xres,$y,referencia);
						$produto = pg_fetch_result($xres,$y,produto);
						echo "<a href='javascript:AbrePeca(\"$produto\",\"$x_data_inicial\",\"$x_data_final\",\"produto\");'>$ref_produto</a> ";
					}
				}

			echo "</TD>";
			echo "<TD align='center' nowrap>$ocorrencia</TD>";
			echo "<TD align='right' nowrap title='%'>". number_format($porcentagem,2,".",".") ."</TD>";
			echo "</TR>";
			
			$total = $ocorrencia + $total;

		}
		echo "</tbody>";
		echo "<tr><td colspan='2'><font size='2'><b><CENTER>TOTAL DE PRODUTOS COM DEFEITOS</b></td><td  ALIGN='CENTER'><font size='2' color='009900'><b>$total</b></td><TD></TD></tr>";
		echo " </TABLE></div>";
		
	}






}

include "rodape.php";
?>
