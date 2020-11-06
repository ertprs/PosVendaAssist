<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$body_onload = "initIt();initIt2()";

$admin_privilegios="gerencia";
include "autentica_admin.php";

include "funcoes.php";

$msg_erro = "";



$meses = array (1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");



$layout_menu = "gerencia";
$title = "RELATÓRIO - FIELD CALL-RATE : CUSTO MÃO-DE-OBRA PRODUTO E PEÇA";

include "cabecalho.php";


if (strlen($_POST["acao"]) > 0) $acao = strtoupper($_POST["acao"]);

if (strlen($acao) > 0) {
flush();
	##### Pesquisa entre datas #####
    $data_inicial = $_REQUEST["data_inicial"];
    $data_final = $_REQUEST["data_final"];	

	$produtos_pesquisa  = trim($_POST['produtos_pesquisa']);
	$pecas_pesquisa     = trim($_POST['pecas_pesquisa']);

	if(strlen($produtos_pesquisa)==0)$msg_erro .= " Escolha o produto. ";
	if(strlen($pecas_pesquisa)==0)$msg_erro .= " Escolha a peça. ";

	$produtos_pesquisa_aux = str_replace(" ","#",$produtos_pesquisa);
	$produtos_pesquisa = str_replace(" ",",",$produtos_pesquisa);
//	$produtos_pesquisa = "'" . $produtos_pesquisa . "'";
	$produto_referencia = $produtos_pesquisa;

	$pecas_pesquisa_aux = str_replace(" ","#",$pecas_pesquisa);
	$pecas_pesquisa = str_replace(" ",",",$pecas_pesquisa);
//	$pecas_pesquisa = "'" . $pecas_pesquisa . "'";
	$pecas_referencia = $pecas_pesquisa;


	$tipo_os = $_POST['tipo_os'];
	$cond_5 = "1=1";
	if (strlen ($tipo_os)  > 0) $cond_5 = " tbl_os.consumidor_revenda = '$tipo_os' ";
	
	if(empty($data_inicial) OR empty($data_final)){
        $msg_erro = "Data Inválida";
    }
	
	if(strlen($msg_erro)==0){
        list($di, $mi, $yi) = explode("/", $data_inicial);
        if(!checkdate($mi,$di,$yi)) 
            $msg_erro = "Data Inválida";
    }
	
    if(strlen($msg_erro)==0){
        list($df, $mf, $yf) = explode("/", $data_final);
        if(!checkdate($mf,$df,$yf)) 
            $msg_erro = "Data Inválida";
    }

    if(strlen($msg_erro)==0){
        $aux_data_inicial = "$yi-$mi-$di";
        $aux_data_final = "$yf-$mf-$df";
    }
    if(strlen($msg_erro)==0){
        if(strtotime($aux_data_final) < strtotime($aux_data_inicial) 
        or strtotime($aux_data_final) > strtotime('today')){
            $msg_erro = "Data Inválida.";
        }
    }
	
	


	$sql = "SELECT referencia from tbl_produto where produto in($produto_referencia)";
	$res = pg_exec($con,$sql);
	if(pg_numrows($res)>0){
		for($i=0;pg_numrows($res)>$i;$i++){
			$xreferencia_produto .= " ". pg_result($res,$i,referencia);
		}
	}else{
		$msg_erro = "Referência de Produto Invalida";
	}
	$sql = "SELECT referencia from tbl_peca where peca in($pecas_pesquisa)";
	$res = pg_exec($con,$sql);
	if(pg_numrows($res)>0){
		for($i=0;pg_numrows($res)>$i;$i++){
			$xreferencia_pecas .= " ". pg_result($res,$i,referencia);
		}
	}
}
?>



<? include "javascript_pesquisas.php"; ?>

<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>

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
  var pickList2 = document.getElementById("PickList2");

  var pickOptions = pickList.options;
  var pickOptions2 = pickList2.options;

  var pickOLength = pickOptions.length;
  var pickOLength2 = pickOptions2.length;

  if (pickOLength < 1) {
    alert("É necessário selecionar pelo menos um produto");
    return false;
  }
  if (pickOLength2 < 1) {
    alert("É necessário selecionar pelo menos uma peça");
    return false;
  }
  for (var i = 0; i < pickOLength; i++) {
    pickOptions[i].selected = true;
  }
  
  for (var i = 0; i < pickOLength2; i++) {
    pickOptions2[i].selected = true;
  }
  return true;
}
function conta_produtos(){
  var produtos='';
  var pickList = document.getElementById("PickList");
  var pickOptions = pickList.options;
  var pickOLength = pickOptions.length;
  for (var i = 0; i < pickOLength; i++) {
      produtos += pickOptions[i].value+" ";
  }
document.frm_relatorio.produtos_pesquisa.value = produtos;
//alert(document.frm_relatorio.produtos_pesquisa.value);
}
//-NOVO


// Initialise - invoked on load
function initIt2() {
  var selectList = document.getElementById("SelectList2");
  var selectOptions = selectList.options;
  var selectIndex = selectList.selectedIndex;
  var pickList = document.getElementById("PickList2");
  var pickOptions = pickList.options;
  pickOptions[0] = null;  // Remove initial entry from picklist (was only used to set default width)
  if (!(selectIndex > -1)) {
    selectOptions[0].selected = true;  // Set first selected on load
    selectOptions[0].defaultSelected = true;  // In case of reset/reload
  }
  selectList.focus();  // Set focus on the selectlist
}

// Adds a selected item into the picklist
function addIt2() {
  var selectList = document.getElementById("SelectList2");
  var selectIndex = selectList.selectedIndex;
  var selectOptions = selectList.options;
  var pickList = document.getElementById("PickList2");
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
function delIt2() {
  var selectList = document.getElementById("SelectList2");
  var selectOptions = selectList.options;
  var selectOLength = selectOptions.length;
  var pickList = document.getElementById("PickList2");
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
function selIt2(btn) {
  var pickList = document.getElementById("PickList2");
  var pickOptions = pickList.options;
  var pickOLength = pickOptions.length;
  if (pickOLength < 1) {
    alert("É necessário selecionar pelo menos uma peça");
    return false;
  }
  for (var i = 0; i < pickOLength; i++) {
    pickOptions[i].selected = true;
  }
  return true;
}


//-->
function conta_produtos2(){
  var produtos='';
  var pickList = document.getElementById("PickList2");
  var pickOptions = pickList.options;
  var pickOLength = pickOptions.length;
  for (var i = 0; i < pickOLength; i++) {
      produtos += pickOptions[i].value+" ";
  }
document.frm_relatorio.pecas_pesquisa.value = produtos;
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




<style type="text/css">

.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;
}

.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial" !important;
    color:#FFFFFF;
    text-align:center;
}

.titulo_coluna{
    background-color:#596d9b;
    font: bold 11px "Arial";
    color:#FFFFFF;
    text-align:center;
}

table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}


.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.texto_avulso{
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: center;
    width:700px;
    margin: 0 auto;
    border-collapse: collapse;
    border:1px solid #596d9b;
}
</style>


<link rel="stylesheet" href="js/blue/style.css" type="text/css" id="" media="print, projection, screen" />
<script type="text/javascript" src="js/jquery-1.1.4.pack.js"></script> 
<script type="text/javascript" src="js/jquery.tablesorter.pack.js"></script> 

<? include "javascript_calendario.php"; ?>

<script>
$(document).ready(function(){
	$( "#data_inicial" ).datePicker({startDate : "01/01/2000"});
    $( "#data_inicial" ).maskedinput("99/99/9999");
	$( "#data_final" ).datePicker({startDate : "01/01/2000"});
	$("#data_final").maskedinput("99/99/9999");
});
</script>


<body onLoad="initIt();initIt2();">

<br>

<table width="700" border="0" cellpadding="1" cellspacing="1" align="center" class="texto_avulso">
	<tr>
		<td><?echo "Este relatório considera a data de geração do extrato."?></td>
	</tr>
</table>
<br>


<? if (strlen($msg_erro) > 0) { ?>
<table width="700" border="0" cellpadding="1" cellspacing="1" align="center" class="msg_erro">
	<tr>
		<td><?echo $msg_erro?></td>
	</tr>
</table>

<? } ?>

<form name="frm_relatorio" ID="frm_relatorio" method="post" action="<? echo $PHP_SELF ?>" onSubmit="conta_produtos();conta_produtos2();return selIt();">
<input type="hidden" name="acao">
<input type="hidden" name="produtos_pesquisa">
<table width="700" border="0" cellspacing="0" cellpadding="2" align="center" class='formulario'>
	<tr class="titulo_tabela">
		<td colspan="5">Parâmetros de Pesquisa</td>
	</tr>
	<tr>	<td>&nbsp;</td>		</tr>
	
	<tr>
		<td width="29%">&nbsp;</td>
		<td style='width:60px'>Data Inicial</td>
		<td width="10">&nbsp;</td>
		<td>Data Final</td>
		<td width="10">&nbsp;</td>
	</tr>
	<tr>
		<td width="10">&nbsp;</td>
		<td style='width:100px'>
			<input type="text" id="data_inicial"  name="data_inicial" size="10" maxlength="10" class="frm">
		</td>
		<td width="10">&nbsp;</td>
		<td>
			<input type="text"  id="data_final"  name="data_final" size="10" maxlength="10" class="frm">
		</td>
		<td width="10">&nbsp;</td>
	</tr>
	<tr>
		<td colspan="5">&nbsp;</td>
	</tr>
	<tr>
		<td width="10">&nbsp;</td>
		<td style='width:110px'>Referência do Produto</td>
		<td width="10">&nbsp;</td>
		<td>Produto(s) Selecionado(s)</td>
		<td width="10">&nbsp;</td>
	</tr>
	<tr>
		<td width="10">&nbsp;</td>
		<td style='width:50px'>

		<select multiple="multiple" size="13" class="frm" ID="SelectList" NAME="SelectList" style='width:120px'>
			<?  
			$sql = "SELECT tbl_produto.referencia, tbl_produto.produto
					FROM tbl_produto
					JOIN tbl_familia using(familia)
					WHERE fabrica = $login_fabrica
					AND tbl_produto.ativo='t' 
					ORDER by tbl_produto.referencia";

			$res = pg_exec($con,$sql);
			if(pg_num_rows($res)>0){
				for ($i=0; $i<pg_numrows($res); $i++){
					$referencia = pg_result($res,$i,referencia);
					$produto = pg_result($res,$i,produto);
					echo "<option value='$produto'>$referencia</option>";
				}
			}
			?>
			</select>
		</td>
		<td align='center' style='width:10px'>
			<input style="cursor:pointer;width:35px" TYPE="BUTTON" VALUE="->" ONCLICK="addIt();"></input>
			<br>
			<input  style="cursor:pointer;width:35px"  TYPE="BUTTON" VALUE="<-" ONCLICK="delIt();"></input>
		</td>
		<td>
			<select NAME="PickList" ID="PickList" SIZE="10" multiple="multiple" style="width: 120px">
			<option VALUE="01sel">Selection 01</option>
			</select>
		</td>
		<td width="10">&nbsp;</td>
	</tr>
<input type="hidden" name="pecas_pesquisa">

<tr>	<td>&nbsp;</td>		</tr>
	<tr>
		<td width="10">&nbsp;</td>
		<td style='width:100px'>Referência da Peça</td>
		<td width="10">&nbsp;</td>
		<td>Peça(s) Selecionado(s)</td>
		<td width="10">&nbsp;</td>
	</tr>
	<tr>
		<td width="10">&nbsp;</td>
		<td style='width:50px'>

		<select multiple="multiple" size="13" class="frm" ID="SelectList2" NAME="SelectList2" style='width:120px'>
			<?  
			$sql = "SELECT tbl_peca.referencia,
							tbl_peca.descricao,
							tbl_peca.peca
					FROM tbl_peca
					WHERE tbl_peca.fabrica = $login_fabrica
					AND tbl_peca.ativo='t'
					order by tbl_peca.referencia";


			$res = pg_exec($con,$sql);
			if(pg_num_rows($res)>0){
				
				for ($i=0; $i<pg_numrows($res); $i++){
					$referencia = pg_result($res,$i,referencia);
					$peca       = pg_result($res,$i,peca);
					
					echo "<option value='$peca'>$referencia</option>";
				}
			}
			?>
		</select>
			
		</td>
		<td align='center' style='width:10px'>
			<input  style="cursor:pointer;width:35px"  TYPE="BUTTON" VALUE="->" ONCLICK="addIt2();"></input>
			<br>
			<input  style="cursor:pointer;width:35px"  TYPE="BUTTON" VALUE="<-" ONCLICK="delIt2();"></input>
		</td>
		
		<td>
			<select NAME="PickList2" ID="PickList2" class="frm" SIZE="13" multiple="multiple" style="width: 120px">
				<option VALUE="01sel">Selection 01</option>
			</select>
		</td>
		<td width="10">&nbsp;</td>
	</tr>



<? if($login_fabrica==24){ ?>
  <tr>
	  <td width="20">&nbsp;</td>
	  <TD colspan = '5'> Por tipo</TD>
  </TR>
	<tr>
	<td width="20">&nbsp;</td>
	  <TD colspan = '5' >
<select name="tipo_os" size="1"  style='width:200px'>
<option value=""></option>
<option value="C">Consumidor</option>
<option value="R">Revenda</option>
</select>
</TD>
  </TR>

<? } ?>



	<tr>
		<td colspan="5">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="5" align='center'><input type='submit' name='acao' value='Pesquisar'>
<!--<img src="imagens_admin/btn_pesquisar_400.gif" onclick="javascript: document.frm_relatorio.acao.value='PESQUISAR'; document.frm_relatorio.submit();" style="cursor:pointer " alt="Clique AQUI para pesquisar">--></td>
	</tr>
</table>
</form>

<br>

<?



if (strlen($acao) > 0 && empty($msg_erro)) {
	$sql = "
			select tbl_os.os
			INTO TEMP temp_rfcpp_$login_admin
			from tbl_extrato
			join tbl_os_extra using(extrato)
			join tbl_os ON tbl_os_extra.os = tbl_os.os and tbl_os.fabrica = tbl_extrato.fabrica
			where tbl_extrato.fabrica = $login_fabrica
			and tbl_extrato.data_geracao BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'
			and tbl_os.produto in($produto_referencia)
			and NOT tbl_extrato.posto = 6359
			and $cond_5;

			CREATE INDEX temp_rfcpp_OS_$login_admin ON temp_rfcpp_$login_admin(os);

			SELECT tbl_peca.referencia, tbl_peca.descricao, count(tbl_os_item.peca) as qtde
			FROM temp_rfcpp_$login_admin OSS
			join tbl_os_produto on tbl_os_produto.os = OSS.os
			join tbl_os_item on tbl_os_produto.os_produto = tbl_os_item.os_produto
			join tbl_peca on tbl_os_item.peca = tbl_peca.peca
			where tbl_os_item.peca in($pecas_pesquisa)
			and tbl_os_item.servico_realizado = 504
			group by tbl_peca.referencia, tbl_peca.descricao
			ORDER BY qtde desc";


	// echo nl2br($sql);

	$res = pg_exec ($con,$sql);
	if (pg_numrows($res) == 0) {
		echo "<br>Nenhum resultado foi encontrado";
	}else{
		echo "<br>";
		//echo "<font size='1'><center><b>Resultado de pesquisa entre os dias ".trim($_POST["data_inicial"])." e ". trim($_POST["data_final"]) ."</b><BR> Buscando pelos produtos $xreferencia_produto<BR> Buscando pelas peças $xreferencia_pecas</center></font>";
		
		echo "<TABLE width='700' border='0' cellspacing='1' cellpadding='1' align='center' name='relatorio' id='relatorio' class='tabela' >";

		echo "<tr > 
				<td colspan='4' class='titulo_tabela'>
					Quantidade de peças utilizadas no período selecionado para os produtos
				</td>
			  </tr>";
				

		echo "<TR class='titulo_coluna'>";
		echo "<TD width='5%' height='15'>Referência</TD>";
		echo "<TD width='30%' height='15'>Descrição</TD>";
		echo "<TD width='10%' height='15' ALIGN='CENTER'>Qtde</TD>";
		echo "</TR>";

		echo "<tbody>";
		for ($i=0; $i<pg_numrows($res); $i++){
			$referencia = trim(pg_result($res,$i,referencia));
			$descricao = trim(pg_result($res,$i,descricao));
			$qtde = trim(pg_result($res,$i,qtde));
		
		$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

			echo "<TR bgcolor='$cor'>";
			echo "<TD align='left'>$referencia";
			echo "</TD>";
			echo "<TD align='left'>$descricao";
			echo "</TD>";
			echo "<TD align='center'>$qtde";
			echo "</TD>";
			echo "</TR>";
		}
		echo "<tbody>";
		echo " </TABLE>";
		
	}


	$sql = "
			SELECT tbl_os.os, tbl_os.produto, tbl_os.mao_de_obra, tbl_os.posto
			INTO TEMP temp_rfcpp2_$login_admin
			FROM temp_rfcpp_$login_admin
			JOIN tbl_os USING(os);

			CREATE INDEX temp_rfcpp2_OS_$login_admin ON temp_rfcpp2_$login_admin(os);
			CREATE INDEX temp_rfcpp2_PRODUTO_$login_admin ON temp_rfcpp2_$login_admin(produto);
			CREATE INDEX temp_rfcpp2_POSTO_$login_admin ON temp_rfcpp2_$login_admin(posto);

			SELECT DISTINCT tbl_os_produto.os        , 
							tbl_produto.referencia   , 
							tbl_produto.descricao    , 
							OSS.mao_de_obra,
							tbl_posto_fabrica.codigo_posto,
							tbl_posto.nome
			FROM temp_rfcpp2_$login_admin as OSS
			JOIN tbl_os_produto on tbl_os_produto.os = OSS.os
			JOIN tbl_os_item on tbl_os_produto.os_produto = tbl_os_item.os_produto
			JOIN tbl_produto on tbl_produto.produto = OSS.produto
			JOIN tbl_posto on tbl_posto.posto = OSS.posto
			JOIN tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE tbl_os_item.peca in($pecas_pesquisa)
			AND tbl_os_item.servico_realizado=504
	";
			//	echo $sql; exit;
		$res = pg_exec ($con,$sql);
	if (pg_numrows($res) > 0) {
		echo "<br>";

		echo "<TABLE width='700' border='0' cellspacing='1' cellpadding='1' align='center' class='tabela'>";
		echo "<TR >";
		echo "<TD class='titulo_tabela' colspan='5'>OS do produto e peça selecionada e sua mão-de-obra</TD>";
		echo "</TR>";

		echo "<TR class='titulo_coluna'>";
		echo "<TD height='15'OS</TD>";
		echo "<TD height='15'>Código</TD>";
		echo "<TD height='15'>Nome</TD>";
		echo "<TD height='15'>Produto</TD>";
		echo "<TD height='15'>Mão-de-Obra</TD>";
		echo "</TR>";
		$total = 0;
		for ($i=0; $i<pg_numrows($res); $i++){
			$referencia = trim(pg_result($res,$i,referencia));
			$descricao  = trim(pg_result($res,$i,descricao));
			$os         = trim(pg_result($res,$i,os));
			$mao_de_obra         = trim(pg_result($res,$i,mao_de_obra));
			$codigo_posto         = trim(pg_result($res,$i,codigo_posto));
			$nome         = trim(pg_result($res,$i,nome));
			$total = $total + $mao_de_obra;
			$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";



			echo "<TR bgcolor='$cor'>";
			echo "<TD class='conteudo10' align='center'>$os</TD>";
			echo "<TD class='conteudo10' align='left'>$codigo_posto</TD>";
			echo "<TD class='conteudo10' align='left'>$nome</TD>";
			echo "<TD class='conteudo10' align='left' nowrap>$referencia - $descricao</TD>";
			echo "<TD class='conteudo10' align='center'>$mao_de_obra</td>";
			echo "</TR>";
		}
			echo "<TR>";
			echo "<TD class='titulo_coluna' align='center' colspan='4'>Total de Mão-de-obra</TD>";

			echo "<TD class='titulo_coluna' align='center'>$total</td>";
			echo "</TR>";
		echo " </TABLE>";
		
	}

}
include "rodape.php";
?>

