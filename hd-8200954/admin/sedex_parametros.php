<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="call_center";
include 'autentica_admin.php';

$btn_acao = trim (strtolower ($_POST['btn_acao']));

$msg_erro = "";

$layout_menu = "callcenter";
$title = "SELEÇÃO DE PARÂMETROS PARA RELAÇÃO DE OS SEDEX LANÇADAS";

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
	text-align:left;
}

.subtitulo{

	background-color: #7092BE;
	font:bold 11px Arial;
	color: #FFFFFF;
}

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}

.texto_avulso{
	font: 14px Arial; color: rgb(89, 109, 155);
	background-color: #d9e2ef;
	text-align: center;
	width:700px;
	margin: 0 auto;
}

</style>

<!--=============== <FUNÇÕES> ================================!-->
<!--  XIN´S POP UP CALENDAR -->
<!--
<script language="javascript" src="js/cal2.js">
/*
Xin's Popup calendar script-  Xin Yang (http://www.yxscripts.com/)
Script featured on/available at http://www.dynamicdrive.com/
This notice must stay intact for use
*/
</script>

<script language="javascript" src="js/cal_conf2.js"></script>
<script language="javascript" src="js/assist.js"></script>
-->


<? include "javascript_calendario.php"; ?>
<script type="text/javascript" charset="utf-8">
	$(function()
	{
		$('#data_inicial_01').datePicker({startDate:'01/01/2000'});
		$('#data_final_01').datePicker({startDate:'01/01/2000'});
		$("#data_inicial_01").maskedinput("99/99/9999");
		$("#data_final_01").maskedinput("99/99/9999");
	});
</script>

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

	else{
		alert("Informe toda ou parte da informação para realizar a pesquisa");
	}
}


</script>

<br>

<FORM name="frmdespesa" METHOD="GET" ACTION="sedex_consulta.php">
<TABLE width="700" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">
<TR>
	<TD colspan="5" class="titulo_tabela">Parâmetros de Pesquisa</TD>
</TR>
<TR>
	<TD style="width: 80px">&nbsp;</TD>
	<TD colspan=4><INPUT TYPE="checkbox" NAME="chk_opt1" value="1">&nbsp; OS Lançadas Hoje</TD>
</TR>
<TR>
	<TD style="width: 10px">&nbsp;</TD>
	<TD colspan=4><INPUT TYPE="checkbox" NAME="chk_opt2" value="1">&nbsp; OS Lançadas Ontem</TD>
</TR>
<TR>
	<TD style="width: 10px">&nbsp;</TD>
	<TD colspan=4><INPUT TYPE="checkbox" NAME="chk_opt3" value="1">&nbsp; OS Lançadas Nesta Semana</TD>
</TR>
<TR>
	<TD style="width: 10px">&nbsp;</TD>
	<TD colspan=4><INPUT TYPE="checkbox" NAME="chk_opt4" value="1">&nbsp; OS Lançadas Neste Mês</TD>
</TR>
<tr><td colspan="5">&nbsp;</td></tr>

<TR>
	<TD colspan="5" ><hr color='#eeeeee'></TD>
</TR>
<TR>
	<TD style="width: 10px">&nbsp;</TD>
	<TD width="130"><INPUT TYPE="checkbox" NAME="chk_opt5" value="1" >&nbsp;Entre datas</TD>
	<TD align='left' width="130">Data Inicial</TD>
	<TD align='left' colspan='2' >Data Final</TD>
</TR>
<TR>
	<TD style="width: 10px">&nbsp;</TD>
	<TD style="width: 10px">&nbsp;</TD>
	<TD align='left' width="130"><INPUT size="12" maxlength="10" TYPE="text" NAME="data_inicial_01"  id="data_inicial_01" onclick="this.value=''" class='frm'></TD>
	<TD align='left' colspan=2 ><INPUT size="12" maxlength="10" TYPE="text" NAME="data_final_01" id="data_final_01" onclick="this.value=''" class='frm'></TD>
</TR>
<TR>
	<TD colspan="5" ><hr color='#eeeeee'></TD>
</TR>
<TR>
	<TD width="19"  style="text-align: left;">&nbsp;</TD>
	<TD rowspan="2" width="130" ><INPUT TYPE="checkbox" NAME="chk_opt6" value="1">&nbsp;Posto Origem</TD>
	<TD width="100" >Código</TD>
	<TD width="180" >Nome</TD>
	<TD width="19"  style="text-align: left;">&nbsp;</TD>
</TR>
<TR>
	<TD  style="text-align: center;">&nbsp;</TD>
	<TD  align="left"><INPUT TYPE="text" NAME="posto_origem" SIZE="8" class='frm'>&nbsp;<img src="imagens/lupa.png" onclick="javascript:fnc_pesquisa_posto (document.frmdespesa.posto_origem.value, document.frmdespesa.nome_posto_origem.value, 'codigo', 'origem')" style='cursor:hand;'></TD>
	<TD  style="text-align: left;"><INPUT TYPE="text" NAME="nome_posto_origem" size="15" class='frm'>&nbsp;<img src="imagens/lupa.png" onclick="javascript:fnc_pesquisa_posto (document.frmdespesa.posto_origem.value, document.frmdespesa.nome_posto_origem.value, 'codigo', 'origem')" style='cursor:hand;'></TD>
	<TD  style="text-align: center;">&nbsp;</TD>
</TR>
<TR>
	<TD colspan="5" ><hr color='#eeeeee'></TD>
</TR>
<TR>
	<TD width="19"  style="text-align: left;">&nbsp;</TD>
	<TD rowspan="2" width="130" ><INPUT TYPE="checkbox" NAME="chk_opt7" value="1">&nbsp;Posto Destino</TD>
	<TD width="100" >Código</TD>
	<TD width="180" >Nome</TD>
	<TD width="19"  style="text-align: left;">&nbsp;</TD>
</TR>
<TR>
	<TD  style="text-align: center;">&nbsp;</TD>
	<TD  align="left"><INPUT TYPE="text" NAME="posto_destino" SIZE="8" class='frm'>&nbsp;<IMG src="imagens/lupa.png" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frmdespesa.posto_destino.value, document.frmdespesa.nome_posto_destino.value, 'codigo', 'destino')"></TD>
	<TD  style="text-align: left;"><INPUT TYPE="text" NAME="nome_posto_destino" size="15" class='frm'>&nbsp;<IMG src="imagens/lupa.png" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisas pela referência do aparelho." onclick="javascript: fnc_pesquisa_posto (document.frmdespesa.posto_destino.value, document.frmdespesa.nome_posto_destino.value, 'codigo', 'destino')"></TD>
	<TD  style="text-align: center;">&nbsp;</TD>
</TR>
<TR>
	<TD colspan="5" ><hr color='#eeeeee'></TD>
</TR>
<TR>
	<TD  style="text-align: left;">&nbsp;</TD>
	<TD  colspan=2><INPUT TYPE="checkbox" NAME="chk_opt8" value="1"> Número da OS</TD>
	<TD  style="text-align: left;"><INPUT TYPE="text" NAME="numero_os" size="17" class='frm'></TD>
	<TD  style="text-align: center;">&nbsp;</TD>
</TR>
<tr><td colspan="5">&nbsp;</td></tr>
<TR>
	<TD colspan="5"  style="text-align: center;"><input type="button" style="background:url(imagens_admin/btn_pesquisar_400.gif); width:400px;cursor:pointer;" value="&nbsp;" onClick="document.frmdespesa.submit();" alt="Preencha as opções e clique aqui para pesquisar"></TD>
</TR>
<tr><td colspan="5">&nbsp;</td></tr>
</TABLE>

</FORM>

<BR>

<? include "rodape.php" ?>
