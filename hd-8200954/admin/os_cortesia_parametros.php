<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia,call_center";
include 'autentica_admin.php';

$btn_acao = trim (strtolower ($_POST['btn_acao']));

$msg_erro = "";

$layout_menu = "callcenter";
$title = "RELAÇÃO DE ORDENS DE SERVIÇO LANÇADAS DO TIPO CORTESIA";

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

.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
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

<!--
<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
-->
<?php include "../js/js_css.php";?>

<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial_01').datepick({startDate:'01/01/2000'});
		$('#data_final_01').datepick({startDate:'01/01/2000'});
		$("#data_inicial_01").mask("99/99/9999");
		$("#data_final_01").mask("99/99/9999");
	});
</script>

<br>

<FORM name="frm_pesquisa" METHOD="GET" ACTION="os_cortesia_consulta.php">
<TABLE width="700" align="center" border="0" cellspacing="0" cellpadding="2" class='formulario'>
<TR class='titulo_tabela'>
	<TD colspan="5" >Pesquisa por Intervalo entre Datas</TD>
</TR>
<TR>
	<TD style="width: 50px">&nbsp;</TD>
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
<TR>
	<TD colspan="5"  style="text-align: center;"><input type='button'value='Pesquisar' onClick="document.frm_pesquisa.submit();" style="cursor:pointer " alt="Preencha as opções e clique aqui para pesquisar"></TD>
</TR>
<tr><td colspan="6">&nbsp;</td></tr>
<TR>
	<TD  style="text-align: center;">&nbsp;</TD>
	<TD > Situação da OS</TD>
	<TD colspan='2'>
		<select name='situacao' class='frm'>
			<option value='' selected>Todas</option>
			<option value='IS NULL'>Em aberto</option>
			<option value='NOTNULL'>Fechadas</option>
		</select>
	</TD>
	<TD width='50'>&nbsp;</TD>
</TR>

<TR>
	<TD  style="width: 10px">&nbsp;</TD>
	<TD  ><INPUT TYPE="checkbox" NAME="chk_opt5" value="1">&nbsp;OS lançadas em aberto</TD>
	<TD  align='left' colspan='4'>Quantidade de dias em aberto</TD>
</TR>
<TR>
	<TD  style="width: 10px">&nbsp;</TD>
	<TD  style="width: 10px">&nbsp;</TD>
	
	<TD  align='left' colspan='2'><INPUT size="2" maxlength="2" TYPE="text" NAME="dia_em_aberto" value="" onclick="this.value=''" class='frm'></TD>
</TR>

<TR>
	<TD  style="width: 10px">&nbsp;</TD>
	<TD ><INPUT TYPE="checkbox" NAME="chk_opt6" value="1">&nbsp;Entre datas</TD>
	<TD  align='left'>Data Inicial</TD>
	<TD  align='left' colspan='2'>Data Final</TD>
</TR>
<TR>
	<TD  style="width: 10px">&nbsp;</TD>
	<TD  style="width: 10px">&nbsp;</TD>
	<TD  align='left'><INPUT size="12" maxlength="10" TYPE="text" NAME="data_inicial_01" id="data_inicial_01" onclick="this.value=''" class='frm'></TD>
	<TD  align='left' colspan=2><INPUT size="12" maxlength="10" TYPE="text" NAME="data_final_01" id="data_final_01"  onclick="this.value=''" class='frm'></TD>
</TR>

<TR>
	<TD width="19"  style="text-align: left;">&nbsp;</TD>
	<TD rowspan="2" width="350" ><INPUT TYPE="checkbox" NAME="chk_opt7" value="1" CHECKED readonly> Posto</TD>
	<TD width="180" >Código do Posto</TD>
	<TD width="180" >Nome do Posto</TD>
	<TD width="19"  style="text-align: left;">&nbsp;</TD>
</TR>
<TR>
	<TD  style="text-align: center;">&nbsp;</TD>
	<TD  align="left"><INPUT TYPE="text" NAME="codigo_posto" SIZE="8" class='frm'><IMG src="imagens/lupa.png" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto,document.frm_pesquisa.nome_posto,'codigo')"></TD>
	<TD width="151"  style="text-align: left;"><INPUT TYPE="text" NAME="nome_posto" size="30" class='frm'> <IMG src="imagens/lupa.png" style="cursor:pointer" align='absmiddle' alt="Clique aqui para pesquisas postos pelo nome" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto,document.frm_pesquisa.nome_posto,'nome')"></TD>
	<TD  style="text-align: left;">&nbsp;</TD>
</TR>

<TR>
	<TD width="19"  style="text-align: left;">&nbsp;</TD>
	<TD rowspan="2" width="350" ><INPUT TYPE="checkbox" NAME="chk_opt8" value="1">Aparelho</TD>
	<TD width="100" >Referência</TD>
	<TD width="250" >Descrição</TD>
	<TD width="19"  style="text-align: left;">&nbsp;</TD>
</TR>
<TR>
	<TD  style="text-align: center;">&nbsp;</TD>
	<TD  align="left"><INPUT TYPE="text" NAME="produto_referencia" SIZE="8" class='frm'><IMG src="imagens/lupa.png" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_produto (document.frm_pesquisa.produto_referencia,document.frm_pesquisa.produto_nome,'referencia')"></TD>
	<TD  style="text-align: left;"><INPUT TYPE="text" NAME="produto_nome" size="30" class='frm'><IMG src="imagens/lupa.png" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisas pela referência do aparelho." onclick="javascript: fnc_pesquisa_produto (document.frm_pesquisa.produto_referencia,document.frm_pesquisa.produto_nome,'descricao')"></TD>
	<TD  style="text-align: center;">&nbsp;</TD>
</TR>

<TR>
	<TD  style="text-align: center;">&nbsp;</TD>
	<TD  ><INPUT TYPE="checkbox" NAME="chk_opt9" value="1"> Número de Série</TD>
	<TD  style="text-align: left;" colspan="2"><INPUT TYPE="text" NAME="numero_serie" size="17" class='frm'></TD>
	<TD  style="text-align: center;">&nbsp;</TD>
</TR>
<TR>
	<TD  style="text-align: center;">&nbsp;</TD>
	<!-- HD 216395: Mudar todas as buscas de nome para LIKE com % apenas no final. A funcao function mostrarMensagemBuscaNomes() está definida no js/assist.js -->
	<TD  ><INPUT TYPE="checkbox" NAME="chk_opt10" value="1"> Nome do Consumidor</TD>
	<TD  style="text-align: left;" colspan="2"><INPUT TYPE="text" NAME="nome_consumidor" size="17" class='frm'> <img src='imagens/help.png' title='Clique aqui para ajuda na busca deste campo' onclick='mostrarMensagemBuscaNomes()'></TD>
	<TD  style="text-align: center;">&nbsp;</TD>
</TR>
<TR>
	<TD  style="text-align: center;">&nbsp;</TD>
	<TD  ><INPUT TYPE="checkbox" NAME="chk_opt11" value="1"> CPF/CNPJ do Consumidor</TD>
	<TD  align="left" colspan='2'><INPUT TYPE="text" NAME="cpf_consumidor" size="17" class='frm'></TD>
	<TD  style="text-align: center;">&nbsp;</TD>
</TR>
<TR>
	<TD  style="text-align: left;">&nbsp;</TD>
	<TD  ><INPUT TYPE="checkbox" NAME="chk_opt12" value="1"> Número da OS</TD>
	<TD  style="text-align: left;" colspan='2'><INPUT TYPE="text" NAME="numero_os" size="17" class='frm'></TD>
	<TD  style="text-align: center;">&nbsp;</TD>
</TR>
<TR>
	<TD  style="text-align: left;">&nbsp;</TD>
	<TD  ><INPUT TYPE="checkbox" NAME="chk_opt13" value="1"> Número da NF de Compra</TD>
	<TD  style="text-align: left;" colspan="2"><INPUT TYPE="text" NAME="numero_nf" size="17" class='frm'></TD>
	<TD  style="text-align: center;">&nbsp;</TD>
</TR>
<TR>
	<TD  style="text-align: left;">&nbsp;</TD>
	<TD  ><INPUT TYPE="checkbox" NAME="chk_opt14" value="1"> Tipo OS Cortesia</TD>
	<TD  style="text-align: left;" colspan="2">
		<select name='tipo_os_cortesia' class='frm'>
			<option value='' selected></option>
			<option value='Garantia'>Garantia</option>
			<option value='Sem Nota Fiscal'>Sem Nota Fiscal</option>
			<option value='Fora da Garantia'>Fora da Garantia</option>
			<option value='Transformação'>Transformação</option>
			<option value='Promotor'>Promotor</option>
			<option value='Mau uso'>Mau uso</option>
			<option value='Devolução de valor'>Devolução de valor</option>
		</select>
	</TD>
	<TD  style="text-align: center;">&nbsp;</TD>
</TR>
<tr><td colspan="6">&nbsp;</td></tr>
<TR>
	<TD colspan="5"  style="text-align: center;"><input type="button" value="Pesquisar" onClick="document.frm_pesquisa.submit();" style="cursor:pointer " alt="Preencha as opções e clique aqui para pesquisar"></TD>
</TR>
</TABLE>
</FORM>
<BR>

<? include "rodape.php" ?>
