<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$layout_menu = "gerencia";
$title = "CONSULTA RELATÓRIO DE QUEBRA";

include "cabecalho.php";

?>

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

</style>

<?
if ($btn_acao == ''){
?>

<?
}
?>

<FORM name="frm_pesquisa" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">

<table width="400" border="0" cellpadding="2" cellspacing="2" align='center'>
<tr>
	<td>
		<font face='Arial, Verdana, Times, Sans' size='2' color='#FF0000'>
		<b><? echo $msg ?></b>
		</font>
	</td>
</tr>
</table>

<TABLE width="400" align="center" border="1" cellspacing="0" cellpadding="2">
<TR>
	<TD colspan="5" class="menu_top"><div align="center"><b>Selecione os critérios para pesquisa</b></div></TD>
</TR>

<!-- ========================= PERÍODO ============================ -->
<TR>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line" rowspan='2' width='100px'>Período:&nbsp;</TD>
	<TD class="table_line">Data Inicial</TD>
	<TD class="table_line">Data Final</TD>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
</TR>
<TR>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line" style="width: 185px"><INPUT size="12" maxlength="10" TYPE="text" NAME="data_inicial_01" onkeydown="date_onkeydown()" value="" language="javascript" onfocus="if (this.value=='') this.value='dd/mm/aaaa'">&nbsp;</TD>
	<TD class="table_line" style="width: 185px"><INPUT size="12" maxlength="10" TYPE="text" NAME="data_final_01" onkeydown="date_onkeydown()" value="" language="javascript" onfocus="if (this.value=='') this.value='dd/mm/aaaa'">&nbsp;</TD>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
</TR>
<TR>
	<TD class="table_line" colspan="5"></TD>
</TR>
<TR>
	<input type='hidden' name='btn_acao' value='0'>
	<TD colspan="5" class="table_line" style="text-align: center;"><IMG src="imagens_admin/btn_pesquisar_400.gif" onclick="javascript: if ( document.frm_pesquisa.btn_acao.value == '0' ) { alert('Efetuando Pesquisa...') ; document.frm_pesquisa.btn_acao.value='1'; document.frm_pesquisa.submit() ; } else { alert ('Aguarde submissão da OS...'); }" style="cursor:pointer " alt='Clique AQUI para pesquisar'></TD>
</TR>
</FORM>
</TABLE>

<br>

<? include "rodape.php" ?>