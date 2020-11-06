<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "funcoes.php";

$body_onload = "initIt()";

$admin_privilegios="financeiro";
include "autentica_admin.php";

//HD6481 - Tectoy
if ($login_fabrica <> 11) {
	echo "<h1><center>Fechamento de Extrato realizado pela TELECONTROL</center></h1>";
	exit;
}



$erro = "";

if (strlen($_POST["acao"]) > 0) $acao = strtoupper($_POST["acao"]);
if (strlen($_GET ["acao"]) > 0) $acao = strtoupper($_GET ["acao"]);


$btn_extrato = $_POST['btn_extrato'];

if (strlen ($btn_extrato) > 0) {
	$qtde_extrato = $_POST['qtde_extrato'];

	for ($i = 0 ; $i < $qtde_extrato ; $i++) {
		$posto = $_POST['gerar_' . $i];
		if (strlen ($posto) > 0) {
			$res = pg_exec($con,"BEGIN TRANSACTION");

			$sql = "SELECT fn_fechamento_extrato ($posto, $login_fabrica, '$data_limite'::date);";
			$res = pg_exec($con,$sql);

			$extrato = pg_result($res,0,0);
			$erro .= pg_errormessage($con);

			if (strlen($erro) == 0 AND strlen($extrato) > 0){
				$sql = "SELECT fn_calcula_extrato($login_fabrica, $extrato)";
				$res = pg_exec ($con,$sql);

				if ( ($login_fabrica <> 6) and ($login_fabrica <> 11) ) {
					$sql = "SELECT fn_aprova_extrato($posto, $login_fabrica, $extrato)";
					$res = pg_exec ($con,$sql);
					$erro .= pg_errormessage($con);
					$res = pg_exec ($con,"UPDATE tbl_extrato SET liberado = aprovado WHERE extrato = $extrato");
				}
			}

			if (strlen($erro) > 0) break;
		}
	}

	if (strlen($erro) == 0) {
		$res = pg_exec($con,"COMMIT TRANSACTION");
		header("Location: $PHP_SELF");
		exit;
	}else{
		$res = pg_exec($con,"ROLLBACK TRANSACTION");
	}
}



$layout_menu = "financeiro";
$title = "Pré Fechamento de Extrato";

include "cabecalho.php";

?>

<style type="text/css">
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
</style>

<script language="JavaScript">
function fnc_pesquisa_posto (campo, campo2, tipo) {
	if (tipo == "nome" ) {
		var xcampo = campo;
	}

	if (tipo == "cnpj" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=300, top=0, left=0");
		janela.retorno = "<? echo $PHP_SELF ?>";
		janela.nome	= campo;
		janela.cnpj	= campo2;
		janela.focus();
	}
}
</script>

<script language="JavaScript" src="js/cal2.js"></script>
<script language="JavaScript" src="js/cal_conf2.js"></script>
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
    alert("É necessário selecionar pelo menos um posto");
    return false;
  }
   if (pickOLength > 10) {
    alert("Favor selecionar no máximo 10 postos");
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
      produtos += pickOptions[i].value+" ";
  }
document.FormExtrato.postos_pesquisa.value = produtos;
//alert(document.FormExtrato.postos_pesquisa.value);
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
<body onLoad="initIt();">

<? if (strlen($erro) > 0){ ?>
<br>
<table width="420" border="0" cellpadding="2" cellspacing="0" align="center" class="error">
	<tr>
		<td><? echo $erro; ?></td>
	</tr>
</table>
<? } ?>


<?

$data_limite = $_POST['data_limite_01'];

?>

<table width="500" border="0" cellpadding="2" cellspacing="0" align="center">

<form method="post" action="<?echo $PHP_SELF?>" name="FormExtrato" onSubmit="conta_produtos();return selIt();">
<input type="hidden" name="acao">
<input type="hidden" name="postos_pesquisa">
	<tr class="Titulo">
		<td height="30">Informe a Data Limite do Fechamento das OS para geração dos extratos.</td>
	</tr>

	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td COLSPAN='2'>
			Data Limite<br>
			<input type="text" name="data_limite_01" size="13" maxlength="10" value="<? if (strlen($data_limite) > 0) echo $data_limite; else echo "dd/mm/aaaa"; ?>" onClick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';" class="frm">
			<img src="imagens_admin/btn_lupa.gif" align="absmiddle" onclick="javascript: showCal('DataLimite01');" style="cursor: hand;" alt="Clique aqui para abrir o calendário">
		</td>
	</tr>
<TR  class='Conteudo' bgcolor='#D9E2EF'>
<TD  ALIGN='center' nowrap>Código Posto &nbsp;&nbsp;Razão Social<BR>
	<select multiple="multiple" size="4" ID="SelectList" NAME="SelectList" style='width:400px'>
		<?  
		$sql = "SELECT tbl_posto_fabrica.codigo_posto   ,
						tbl_posto_fabrica.posto         ,
						tbl_posto.nome
				FROM tbl_posto
				JOIN tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto
				and tbl_posto_fabrica.fabrica = $login_fabrica
				where tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
				ORDER by tbl_posto_fabrica.codigo_posto";

		$res = pg_exec($con,$sql);
		if(pg_num_rows($res)>0){
			for($x;pg_num_rows($res)>$x;$x++){
				$codigo_posto = pg_result($res,$x,codigo_posto);
				$nome         = pg_result($res,$x,nome);
				$posto        = pg_result($res,$x,posto);
				
				echo "<option value='$posto'>$codigo_posto - $nome</option>";
			}
		}
		?>
		</select>
</td>
</tr>
<TR>
<TR  class='Conteudo' bgcolor='#D9E2EF'>
<TD  ALIGN='center' COLSPAN='2' nowrap>
<img src="imagens_admin/seta_cima.gif" align="absmiddle" onclick="javascript: delIt();" style="cursor: hand;" alt="Clique excluir o posto">
&nbsp;&nbsp;&nbsp;&nbsp;
<img src="imagens_admin/seta_baixo.gif" align="absmiddle" onclick="javascript: addIt();" style="cursor: hand;" alt="Clique selecionar o posto">
</td>
</tr>

<TR>
<TR  class='Conteudo' bgcolor='#D9E2EF'>
<TD  ALIGN='center' COLSPAN='2' nowrap>
SELECIONADO : Código Posto &nbsp;&nbsp;Razão Social<BR>
<select NAME="PickList" ID="PickList" SIZE="4" multiple="multiple" style="width: 400px">
<option VALUE="01sel">Selection 01</option>
</select>
</td>
</tr>

	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td align="center">
		<!--<img src="imagens_admin/btn_pesquisar_400.gif" onClick="javascript: document.FormExtrato.acao.value='BUSCAR'; document.FormExtrato.submit();" style="cursor: hand;" alt="Preencha as opções e clique aqui para pesquisar">-->
		<input type='submit' name='acao' value='Pesquisar'></td>
	</tr>
</form>
</table>

<?
$postos_pesquisa = $_POST['postos_pesquisa'];
$postos_pesquisa  = trim($_POST['postos_pesquisa']);
$postos_pesquisa_aux = str_replace(" ","#",$postos_pesquisa);
$postos_pesquisa = str_replace(" "," , ",$postos_pesquisa);
$postos_pesquisa;


$btn_acao    = $_POST['acao'];
$data_limite = $_POST['data_limite_01'];
/*$posto_codigo = $_POST['posto_codigo'];
$posto_nome   = $_POST['posto_nome'];*/
if (strlen ($btn_acao) > 0) {

	if ($data_limite == "dd/mm/aaaa"){
		$msg_erro .= "Digite a data limite";
	}
	if (strlen($data_limite)==0){
		$msg_erro .= "Digite a data limite";
	}

	$data_limite = str_replace("-", "", $data_limite);
	$data_limite = str_replace("_", "", $data_limite);
	$data_limite = str_replace(".", "", $data_limite);
	$data_limite = str_replace(",", "", $data_limite);
	$data_limite = str_replace("/", "", $data_limite);
	
	$data_limite = substr ($data_limite,4,4) . "-" . substr ($data_limite,2,2) . "-" . substr ($data_limite,0,2)." 23:59:59";
	$cond_0 = " 1=1 ";
	if(strlen($msg_erro)==0){
	$sql = "SELECT tbl_posto_fabrica.codigo_posto, tbl_posto.posto, tbl_posto.nome, os.qtde
			FROM   tbl_posto
			JOIN   tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN  (SELECT tbl_os.posto, COUNT(*) AS qtde
					FROM tbl_os
					JOIN tbl_os_extra USING (os)
					WHERE  tbl_os.finalizada      IS NOT NULL 
					AND    tbl_os.data_fechamento IS NOT NULL 
					AND    tbl_os.data_fechamento <= '$data_limite' 
					AND    tbl_os.excluida        IS NOT TRUE 
					AND    tbl_os_extra.extrato   IS NULL 
					AND    tbl_os.fabrica          = $login_fabrica
					AND    tbl_os.posto in ($postos_pesquisa)
					GROUP BY tbl_os.posto
			) os ON tbl_posto.posto = os.posto
			ORDER BY tbl_posto.nome";
//echo $sql; echo "<BR>";
		$sql = "
			SELECT tbl_posto_fabrica.codigo_posto , 
					tbl_posto.posto               , 
					tbl_posto.nome                ,
					tbl_os.posto                  , 
					COUNT(tbl_os.os) AS qtde 
			FROM tbl_os
			JOIN tbl_os_extra USING (os) 
			JOIN tbl_posto on tbl_os.posto = tbl_posto.posto
			JOIN tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto 
			AND tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE tbl_os.finalizada IS NOT NULL 
			AND tbl_os.data_fechamento IS NOT NULL 
			AND tbl_os.data_fechamento <= '$data_limite' 
			AND tbl_os.excluida IS NOT TRUE 
			AND tbl_os_extra.extrato IS NULL 
			AND tbl_os.fabrica = $login_fabrica
			AND tbl_os.posto in ($postos_pesquisa) 
			GROUP BY tbl_posto_fabrica.codigo_posto, 
					tbl_posto.posto, 
					tbl_posto.nome,
					tbl_os.posto 
			ORDER BY tbl_posto.nome
		";

//echo $sql;//exit;
	$res = pg_exec ($con,$sql) ;
echo "<BR><BR>";
	echo "<table width='500' align='center'>";
	echo "<form method='post' name='frm_extrato' action='$PHP_SELF'>";
	echo "<tr align='center' bgcolor='#D9E2EF'>";
	echo "<td><b>Fechar</b></td>";
	echo "<td><b>Código</b></td>";
	echo "<td><b>Nome</b></td>";
	echo "<td><b>Qtde OS</b></td>";
	echo "</tr>";

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$posto = pg_result ($res,$i,posto);

		echo "<tr align='left' style='font-size:10px' id='linha_$i' >";
		echo "<td align='center'><input type='checkbox' name='gerar_$i' value='$posto' onclick=\"javascript: if (this.checked) { linha_$i.bgColor='#eeeeee' }else{ linha_$i.bgColor='#ffffff' } \" ></td>";
		echo "<td align='center'>" . pg_result ($res,$i,codigo_posto) . "</td>";
		echo "<td>" . pg_result ($res,$i,nome) . "</td>";
		echo "<td align='center'>" . pg_result ($res,$i,qtde) . "</td>";
		echo "</tr>";
	}


	echo "<input type='hidden' name='qtde_extrato' value='$i'>";
	echo "<input type='hidden' name='data_limite' value='$data_limite'>";

	echo "<tr bgcolor='#D9E2EF'>";
	echo "<td colspan='4' align='center'>";
	echo "<center><input type='submit' name='btn_extrato' value='Gerar Extratos'></center>";
	echo "</td>";
	echo "</tr>";
	echo "</form>";
	echo "</table>";
	}
}

echo "<br>";

include "rodape.php";

?>
