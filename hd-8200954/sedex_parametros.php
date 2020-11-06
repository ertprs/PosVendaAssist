<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$btn_acao = trim (strtolower ($_POST['btn_acao']));

$msg_erro = "";

$layout_menu = "os";
$title = "Seleção de Parâmetros para Relação de OS de Sedex Lançadas";
#$body_onload = "javascript: document.frm_os.condicao.focus()";

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

<? include "javascript_pesquisas.php" ?>


<!--=============== <FUNÇÕES> ================================!-->
<!--  XIN´S POP UP CALENDAR -->

<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
<script language="javascript" src="js/assist.js"></script>

<script language="JavaScript">
function fnc_pesquisa_posto (campo1, campo2, tipo, posto) {
	var url = "";
	if (tipo == "codigo" ) {
		var xcampo = campo1;
	}
	if (tipo == "nome" ) {
		var xcampo = campo2;
	}
	if ((campo1 == "" || campo2 == "") && xcampo != "") {
		var url = "";
		url = "pesquisa_posto_sedex.php?campo=" + xcampo + "&tipo=" + tipo + "&posto=" + posto;
		janela = window.open(url,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0");
		janela.codigo  = campo1;
		janela.nome    = campo2;
		janela.focus();
	}
}
</script>

<br>

<FORM name="frmdespesa" METHOD="GET" ACTION="sedex_consulta.php">
<TABLE width="500" align="center" border="0" cellspacing="0" cellpadding="2">
<TR>
	<TD colspan="5" class="menu_top"><div align="center"><b>Selecione os parâmetros para a pesquisa.</b></div></TD>
</TR>
<TR>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line" colspan=4><INPUT TYPE="checkbox" NAME="chk_opt1" value="1">&nbsp; OS Lançadas Hoje</TD>
</TR>
<TR>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line" colspan=4><INPUT TYPE="checkbox" NAME="chk_opt2" value="1">&nbsp; OS Lançadas Ontem</TD>
</TR>
<TR>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line" colspan=4><INPUT TYPE="checkbox" NAME="chk_opt3" value="1">&nbsp; OS Lançadas Nesta Semana</TD>
</TR>
<TR>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line" colspan=4><INPUT TYPE="checkbox" NAME="chk_opt4" value="1">&nbsp; OS Lançadas Neste Mês</TD>
</TR>
<TR>
	<TD colspan="5" class="table_line" style="text-align: left;"><center><IMG src="imagens/btn_pesquisar_400.gif" onClick="document.frmdespesa.submit();" style="cursor:pointer " alt="Preencha as opções e clique aqui para pesquisar"></center></TD>
</TR>
<TR>
	<TD colspan="5" class="table_line"><hr color='#eeeeee'></TD>
</TR>
<TR>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line"><INPUT TYPE="checkbox" NAME="chk_opt5" value="1">&nbsp;Entre datas</TD>
	<TD class="table_line" align='left'>Data Inicial</TD>
	<TD class="table_line" align='left' colspan='2'>Data Final</TD>
</TR>
<TR>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line" align='left' nowrap><INPUT size="12" maxlength="10" TYPE="text" NAME="data_inicial_01" value="dd/mm/aaaa" onclick="this.value=''">&nbsp;<IMG src="imagens/btn_lupa.gif" align='absmiddle' onclick="javascript:showCal('DataDespesaInicial')" style="cursor:pointer" alt="Clique aqui para abrir o calendário"></TD>
	<TD class="table_line" align='left' colspan=2><INPUT size="12" maxlength="10" TYPE="text" NAME="data_final_01" value="dd/mm/aaaa" onclick="this.value=''">&nbsp;<IMG src="imagens/btn_lupa.gif" align='absmiddle' onclick="javascript:showCal('DataDespesaFinal')" style="cursor:pointer" alt="Clique aqui para abrir o calendário"></TD>
</TR>
<TR>
	<TD colspan="5" class="table_line"><hr color='#eeeeee'></TD>
</TR>
<TR>
	<TD width="19" class="table_line" style="text-align: left;">&nbsp;</TD>
	<TD rowspan="2" width="350" class="table_line"><INPUT TYPE="checkbox" NAME="chk_opt6" value="1">&nbsp;Posto Origem</TD>
	<TD width="100" class="table_line">Código</TD>
	<TD width="180" class="table_line">Nome</TD>
	<TD width="19" class="table_line" style="text-align: left;">&nbsp;</TD>
</TR>
<TR>
	<TD class="table_line" style="text-align: center;">&nbsp;</TD>
	<TD class="table_line" align="left"><INPUT TYPE="text" NAME="posto_origem" SIZE="8">&nbsp;<img src="imagens/btn_lupa.gif" onclick="javascript:fnc_pesquisa_posto (document.frmdespesa.posto_origem.value, document.frmdespesa.nome_posto_origem.value, 'codigo', 'origem')" style='cursor:hand;'></TD>
	<TD class="table_line" style="text-align: left;"><INPUT TYPE="text" NAME="nome_posto_origem" size="15">&nbsp;<img src="imagens/btn_lupa.gif" onclick="javascript:fnc_pesquisa_posto (document.frmdespesa.posto_origem.value, document.frmdespesa.nome_posto_origem.value, 'codigo', 'origem')" style='cursor:hand;'></TD>
	<TD class="table_line" style="text-align: center;">&nbsp;</TD>
</TR>
<TR>
	<TD colspan="5" class="table_line"><hr color='#eeeeee'></TD>
</TR>
<TR>
	<TD width="19" class="table_line" style="text-align: left;">&nbsp;</TD>
	<TD rowspan="2" width="350" class="table_line"><INPUT TYPE="checkbox" NAME="chk_opt7" value="1">&nbsp;Posto Destino</TD>
	<TD width="100" class="table_line">Código</TD>
	<TD width="180" class="table_line">Nome</TD>
	<TD width="19" class="table_line" style="text-align: left;">&nbsp;</TD>
</TR>
<TR>
	<TD class="table_line" style="text-align: center;">&nbsp;</TD>
	<TD class="table_line" align="left"><INPUT TYPE="text" NAME="posto_destino" SIZE="8">&nbsp;<IMG src="imagens/btn_lupa.gif" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frmdespesa.posto_destino.value, document.frmdespesa.nome_posto_destino.value, 'codigo', 'destino')"></TD>
	<TD class="table_line" style="text-align: left;"><INPUT TYPE="text" NAME="nome_posto_destino" size="15">&nbsp;<IMG src="imagens/btn_lupa.gif" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisas pela referência do aparelho." onclick="javascript: fnc_pesquisa_posto (document.frmdespesa.posto_destino.value, document.frmdespesa.nome_posto_destino.value, 'codigo', 'destino')"></TD>
	<TD class="table_line" style="text-align: center;">&nbsp;</TD>
</TR>
<TR>
	<TD colspan="5" class="table_line"><hr color='#eeeeee'></TD>
</TR>
<TR>
	<TD class="table_line" style="text-align: left;">&nbsp;</TD>
	<TD class="table_line" colspan=2><INPUT TYPE="checkbox" NAME="chk_opt8" value="1"> Numero da OS</TD>
	<TD class="table_line" style="text-align: left;"><INPUT TYPE="text" NAME="numero_os" size="17"></TD>
	<TD class="table_line" style="text-align: center;">&nbsp;</TD>
</TR>
<TR>
	<TD colspan="5" class="table_line" style="text-align: left;"><center><IMG src="imagens/btn_pesquisar_400.gif" onClick="document.frmdespesa.submit();" style="cursor:pointer " alt="Preencha as opções e clique aqui para pesquisar"></center></TD>
</TR>
</TABLE>

</FORM>

<BR>

<? include "rodape.php" ?>
