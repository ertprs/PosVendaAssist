<?

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';

$layout_menu = "gerencia";
$title = "RELATÓRIO - FIELD CALL-RATE : OS COM PEÇA(S)";

include "cabecalho.php";

?>
<? include "javascript_calendario.php"; ?>
<script>
	$(function(){
		$('input[rel=data]').datePicker({startDate:'01/01/2000'});
		$("input[rel=data]").maskedinput("99/99/9999");
	});
</script>
<script language="JavaScript">

function date_onkeydown() {
  if (window.event.srcElement.readOnly) return;
  var key_code = window.event.keyCode;
  var oElement = window.event.srcElement;
  if (window.event.shiftKey && String.fromCharCode(key_code) == "T") {
        var d = new Date();
        oElement.value = String(d.getMonth() + 1).padL(2, "0") + "/" +
                         String(d.getDate()).padL(2, "0") + "/" +
                         d.getFullYear();
        window.event.returnValue = 0;
    }
    if (!window.event.shiftKey && !window.event.ctrlKey && !window.event.altKey) {
        if ((key_code > 47 && key_code < 58) ||
          (key_code > 95 && key_code < 106)) {
            if (key_code > 95) key_code -= (95-47);
            oElement.value =
                oElement.value.replace(/[dma]/, String.fromCharCode(key_code));
        }
        if (key_code == 8) {
            if (!oElement.value.match(/^[dma0-9]{2}\/[dma0-9]{2}\/[dma0-9]{4}$/))
                oElement.value = "dd/mm/aaaa";
            oElement.value = oElement.value.replace(/([dma\/]*)[0-9]([dma\/]*)$/,
                function ($0, $1, $2) {
                    var idx = oElement.value.search(/([dma\/]*)[0-9]([dma\/]*)$/);
                    if (idx >= 5) {
                        return $1 + "a" + $2;
                    } else if (idx >= 2) {
                        return $1 + "m" + $2;
                    } else {
                        return $1 + "d" + $2;
                    }
                } );
            window.event.returnValue = 0;
        }
    }
    if (key_code != 9) {
        event.returnValue = false;
    }
}

</script>

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
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

/*****************************
ELEMENTOS DE POSICIONAMENTO
*****************************/

#container {
  border: 0px;
  padding:0px 0px 0px 0px;
  margin:0px 0px 0px 0px;
  background-color: white;
}

</style>

<style type="text/css">
<!--
.titPreto14 {
	color: #000000;
	text-align: center;
	font:12px Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

.titDatas12 {
	color: #000000;
	text-align: center;
	font:12px Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

.titChamada10{
	background-color: #596D9B;
	color: #ffffff;
	text-align: center;
	font:11px Tahoma, Verdana, Arial, Helvetica, Sans-Serif
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

-->
</style>

<? include "javascript_pesquisas.php" ?>



<DIV ID="container" style="width: 100%; ">

<!-- =========== PESQUISA POR INTERVALO ENTRE DATAS ============ -->
<FORM name="frm_pesquisa" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">

<?
if (strlen($msg) > 0){
?>
<table width="730" border="0" cellpadding="2" cellspacing="2" align='center'>
<tr>
	<td align="center" class='error'>
			<? echo $msg ?>
			
	</td>
</tr>
</table>

<br>
<?
}
?>

<br>

<TABLE width="400" align="center" border="1" cellspacing="0" cellpadding="2">
  <TR>
	<TD colspan="4" class="menu_top"><div align="center"><b>Pesquisa</b></div></TD>
  </TR>

  <TR>
    <TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line" colspan='2'><center>Este relatório considera a data de digitação.</center></TD>
    <TD class="table_line" style="width: 10px">&nbsp;</TD>
  </TR>

  <TR>
    <TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line"><center>Data Inicial</center></TD>
    <TD class="table_line"><center>Data Final</center></TD>
    <TD class="table_line" style="width: 10px">&nbsp;</TD>
  </TR>
  <TR>
    <TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line" style="width: 185px"><center><INPUT size="12" maxlength="10" TYPE="text" NAME="data_inicial_01" value="<? if (strlen($data_inicial) > 0) echo $data_inicial;?>" rel="data"></center></TD>
	<TD class="table_line" style="width: 185px"><center><INPUT size="12" maxlength="10" TYPE="text" NAME="data_final_01" value="<? if (strlen($data_final) > 0) echo $data_final;?>" rel="data"></center></TD>
    <TD class="table_line" style="width: 10px">&nbsp;</TD>
  </TR>
 
  <TR>
    <input type='hidden' name='btn_finalizar' value='0'>
    <TD colspan="4" class="table_line" style="text-align: center;"><IMG src="imagens_admin/btn_pesquisar_400.gif" onclick="javascript: if ( document.frm_pesquisa.btn_finalizar.value == '0' ) { document.frm_pesquisa.btn_finalizar.value='1'; document.frm_pesquisa.submit() ; } else { alert ('Aguarde submissão da OS...'); }" style="cursor:pointer " alt='Clique AQUI para pesquisar'></TD>
  </TR>
</TABLE>

</FORM>

<!-- =========== AQUI TERMINA O FORMULÁRIO FRM_PESQUISA =========== -->
</DIV>

<?
	
	
$btn_finalizar = $_POST["btn_finalizar"];

if (strlen($btn_finalizar)>0) {
	$data_inicial = $_POST["data_inicial_01"];
	$data_final = $_POST["data_final_01"];
	$aux_data_inicial = substr($data_inicial,8,2) . "/" . substr($data_inicial,5,2) . "/" . substr($data_inicial,0,4);
	$aux_data_final   = substr($data_final,8,2)   . "/" . substr($data_final,5,2)   . "/" . substr($data_final,0,4);
		if (strlen($_POST["data_inicial_01"]) == 0) {
			$erro .= "Favor informar a data inicial para pesquisa<br>";
		}
		
		if (strlen($erro) == 0) {
			$data_inicial   = trim($_POST["data_inicial_01"]);
			$fnc            = @pg_exec($con,"SELECT fnc_formata_data('$data_inicial')");
			
			if (strlen ( pg_errormessage ($con) ) > 0) {
					$erro = pg_errormessage ($con) ;
			}
			
			//if (strlen($erro) == 0) $aux_data_inicial = @pg_result ($fnc,0,0) ." 00:00:00";
			if (strlen($erro) == 0) $aux_data_inicial = @pg_result ($fnc,0,0);
			}
		if (strlen($_POST["data_final_01"]) == 0) {
			$erro .= "Favor informar a data final para pesquisa<br>";
		}
		
		if (strlen($erro) == 0) {
			$data_final   = trim($_POST["data_final_01"]);
			$fnc            = @pg_exec($con,"SELECT fnc_formata_data('$data_final')");
			
			if (strlen ( pg_errormessage ($con) ) > 0) {
			$erro = pg_errormessage ($con) ;
			}
			
			//if (strlen($erro) == 0) $aux_data_final = @pg_result ($fnc,0,0) ." 23:59:59";
			if (strlen($erro) == 0) $aux_data_final = @pg_result ($fnc,0,0);
		}
	
	if (strlen($erro) == 0) {
	$sql ="SELECT os,
				sua_os, 
				consumidor_nome,
				tbl_posto.posto, 
				tbl_posto.nome, 
				fabrica 
			FROM tbl_os 
			JOIN tbl_posto using(posto) 
			WHERE os in (
			SELECT  distinct(os) 
			FROM tbl_os_produto LEFT JOIN tbl_os using(os) 
			JOIN tbl_os_item using(os_produto) 
			WHERE fabrica=$login_fabrica 
			AND   data_digitacao BETWEEN '$aux_data_inicial' AND '$aux_data_final' 
			ORDER BY os DESC
			)   GROUP BY os,sua_os,consumidor_nome, tbl_posto.posto, tbl_posto.nome, fabrica";
			
			
// 			SELECT os,sua_os, tbl_posto.posto, tbl_posto.nome, fabrica FROM tbl_os join tbl_posto using(posto) WHERE os in ( SELECT  distinct(os) FROM tbl_os_produto LEFT JOIN tbl_os using(os) JOIN tbl_os_item using(os_produto) WHERE fabrica=6 ORDER BY os DESC  LIMIT 10)  GROUP BY os,sua_os, tbl_posto.posto, tbl_posto.nome, fabrica
	$res = pg_exec ($con,$sql);
// 	echo "$sql";
	if (pg_numrows($res) > 0) {
	echo "<br>";
		$qtde = pg_numrows($res);
		echo "<font size='1'><b>Resultado de pesquisa entre os dias $data_inicial e $data_final de OSs com peça(s)</b><BR> Foram encontrados: $qtde</font>";
		
		echo "<br><br>";
		echo "<TABLE width='600' border='0' cellspacing='2' bgcolor='#D9E2EF' cellpadding='2' align='center' style='font-family: verdana; font-size: 11px'>";
		echo "	<TR>";
		echo "		<TD height='15'><b>OS</b></TD>";
		echo "		<TD height='15'><b>Posto</b></TD>";
		echo "		<TD height='15'><b>Consumidor</b></TD>";
		echo "	</TR>";
	

		for($i = 0 ; $i < pg_numrows($res) ; $i++){
			$os = pg_result ($res,$i,os);
			$sua_os = pg_result ($res,$i,sua_os);
			$consumidor_nome = pg_result ($res,$i,consumidor_nome);
			$posto = pg_result ($res,$i,posto);
			$fabrica = pg_result ($res,$i,fabrica);
			$nome = pg_result ($res,$i,nome);
		$cor = "#FFFFFF"; 
		if ($i % 2 == 0) $cor = '#E8F2FF';
		echo "<tr>";
			echo "<td  bgcolor='$cor' align='left'><a href='os_press.php?os=$os'  target='_blank'>$sua_os</A></td>";
			echo "<td  bgcolor='$cor' align='left'>$posto - $nome</td>";
			echo "<td  bgcolor='$cor' align='left'>$consumidor_nome</td>";
		echo "</tr>";
// 			echo "$os - $fabrica<BR>";
		}
		echo "</table>";
	}
}
}

?>

<p>

<? include "rodape.php" ?>
