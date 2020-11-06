<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia,call_center";
include 'autentica_admin.php';

$btn_acao = trim (strtolower ($_POST['btn_acao']));

$msg_erro = "";

/* Fucao que exibe os Estados (UF) */
function selectUF($selUF=""){
	$cfgUf = array("","AC","AL","AM","AP","BA","CE","DF","ES","GO","MA","MG","MS","MT","PA","PB","PI","PR","RJ","RN","RO","RR","RS","SC","SE","SP","TO");
	if($selUF == "") $selUF = $cfgUf[0];

	$totalUF = count($cfgUf) - 1;
	for($currentUF=0; $currentUF <= $totalUF; $currentUF++){
		echo "                      <option value=\"$cfgUf[$currentUF]\"";
		if($selUF == $cfgUf[$currentUF]) print(" selected");
		echo ">$cfgUf[$currentUF]</option>\n";
	}
}
if($login_fabrica <> 108 and $login_fabrica <> 111){
$layout_menu = "callcenter";
} else {
$layout_menu = "gerencia";
}
$title = "RELAÇÃO DE ORDENS DE SERVIÇOS EXCLUÍDAS";

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
</style>

<?php
include "javascript_pesquisas.php";
include "javascript_calendario_new.php";
include_once '../js/js_css.php';
?>
<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial_01').datepick({startdate:'01/01/2000'});
		$('#data_final_01').datepick({startdate:'01/01/2000'});
		$("#data_inicial_01").mask("99/99/9999");
		$("#data_final_01").mask("99/99/9999");
	});
</script>

<br>

<FORM name="frm_pesquisa" METHOD="GET" ACTION="os_consulta_excluida.php">
<TABLE width="700" align="center" border="0" cellspacing="0" cellpadding="2" class='formulario'>
<tr class='titulo_tabela'><td colspan='5'>Parâmetros de Pesquisa</td></tr>

<TR>
	<TD style="width: 40px">&nbsp;</TD>
	<TD width='150'>&nbsp;</TD>
	<TD align='left' width='130'>Data Inicial</TD>
	<TD align='left' colspan='2'>Data Final</TD>
</TR>
<TR>
	<TD  style="width: 10px">&nbsp;</TD>
	<TD  style="width: 10px">&nbsp;</TD>
	<TD  align='left' nowrap width='130'><INPUT size="12" maxlength="10" TYPE="text" NAME="data_inicial_01" id="data_inicial_01" value="" onclick="this.value=''" class='frm'></TD>
	<TD  align='left' colspan=2 nowrap><INPUT size="12" maxlength="10" TYPE="text" NAME="data_final_01" id="data_final_01" value="" onclick="this.value=''" class='frm'></TD>
</TR>
<TR>
	<TD  style="width: 10px">&nbsp;</TD>
	<TD  style="width: 10px">&nbsp;</TD>
	<TD  align='left' nowrap width='130'>
		<label>Data Abertura:</label>
		<input type="radio" name="data_pesquisa" id="data_abertura" value="abertura" checked />
	</TD>
	<TD  align='left' colspan=2 nowrap>
		<label>Data Exclusão:</label>
		<input type="radio" name="data_pesquisa" id="data_exclusao" value="exclusao" />
	</TD>
</TR>
<TR>
	<TD colspan="5"  style="text-align: center;"><input type="button" style="background:url(imagens_admin/btn_pesquisar_400.gif); width:400px;cursor:pointer;" value="&nbsp;" onClick="document.frm_pesquisa.submit();" alt="Preencha as opções e clique aqui para pesquisar"></TD>
</TR>
<TR>
	<TD colspan="5" ><hr color='#eeeeee'></TD>
</TR>
<TR>
	<TD width="19" style="text-align: left;">&nbsp;</TD>
	<TD rowspan="2"width='150' ><INPUT TYPE="checkbox" NAME="chk_opt6" value="1"> Posto</TD>
	<TD width='130' >Código do Posto</TD>
	<TD width="180" >Nome do Posto</TD>
	<TD width="70"  style="text-align: left;">Estado</TD>
</TR>
<TR>
	<TD style="text-align: center;">&nbsp;</TD>
	<TD align="left" nowrap width='130'><INPUT TYPE="text" NAME="codigo_posto" SIZE="8" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto,document.frm_pesquisa.nome_posto,'codigo')" <? } ?> class='frm'><IMG src="imagens/lupa.png" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto,document.frm_pesquisa.nome_posto,'codigo')"></TD>
	<TD width="151"  style="text-align: left;" nowrap><INPUT TYPE="text" NAME="nome_posto" size="15" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto,document.frm_pesquisa.nome_posto,'nome')" <? } ?> class='frm'> <IMG src="imagens/lupa.png" style="cursor:pointer" align='absmiddle' alt="Clique aqui para pesquisas postos pelo nome" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto,document.frm_pesquisa.nome_posto,'nome')"></TD>
	<TD  style="text-align: left;">
		<select name='uf_posto' class='frm'>
			<? selectUF($uf); ?>
		</select>
	</TD>
</TR>
<TR>
	<TD colspan="5" ><hr color='#eeeeee'></TD>
</TR>
<TR>
	<TD width="19"  style="text-align: left;">&nbsp;</TD>
	<TD rowspan="2" width='150' ><INPUT TYPE="checkbox" NAME="chk_opt7" value="1">Aparelho</TD>
	<TD width='130' >Referência</TD>
	<TD width="180" >Descrição</TD>
	<TD width="19" style="text-align: left;">&nbsp;</TD>
</TR>
<TR>
	<TD style="text-align: center;">&nbsp;</TD>
	<TD align="left" nowrap width='130'><INPUT TYPE="text" NAME="produto_referencia" SIZE="8" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_produto (document.frm_pesquisa.produto_referencia,document.frm_pesquisa.produto_nome,'referencia')" <? } ?> class='frm'><IMG src="imagens/lupa.png" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_produto (document.frm_pesquisa.produto_referencia,document.frm_pesquisa.produto_nome,'referencia')"></TD>
	<TD  style="text-align: left;" nowrap><INPUT TYPE="text" NAME="produto_nome" size="15" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_produto (document.frm_pesquisa.produto_referencia,document.frm_pesquisa.produto_nome,'descricao')" <? } ?> class='frm'><IMG src="imagens/lupa.png" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisas pela referência do aparelho." onclick="javascript: fnc_pesquisa_produto (document.frm_pesquisa.produto_referencia,document.frm_pesquisa.produto_nome,'descricao')"></TD>
	<TD  style="text-align: center;">&nbsp;</TD>
</TR>
<TR>
	<TD colspan="5" ><hr color='#eeeeee'></TD>
</TR>
<TR>
	<TD  style="text-align: center;">&nbsp;</TD>
	<TD  width='150'><INPUT TYPE="checkbox" NAME="chk_opt8" value="1"> Número Série</TD>
	<TD style="text-align: left;" colspan="2"><INPUT TYPE="text" NAME="numero_serie" size="17" class='frm'></TD>
	<TD style="text-align: center;">&nbsp;</TD>
</TR>
<TR>
	<TD style="text-align: center;">&nbsp;</TD>
	<!-- HD 216395: Mudar todas as buscas de nome para LIKE com % apenas no final. A funcao function mostrarMensagemBuscaNomes() está definida no js/assist.js -->
	<TD width='150'><INPUT TYPE="checkbox" NAME="chk_opt9" value="1"> Nome do Consumidor</TD>
	<TD  style="text-align: left;" colspan="2"><INPUT TYPE="text" NAME="nome_consumidor" size="17" class='frm'> <img src='imagens/help.png' title='Clique aqui para ajuda na busca deste campo' onclick='mostrarMensagemBuscaNomes()'><!-- IMG src="imagens/lupa.png" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisas pelo nome do consumidor." onclick="javascript: fnc_pesquisa_consumidor (document.frm_pesquisa.nome_consumidor,'nome')"--></TD>
	<TD  style="text-align: center;">&nbsp;</TD>
</TR>
<TR>
	<TD  style="text-align: left;">&nbsp;</TD>
	<TD  width='150'><INPUT TYPE="checkbox" NAME="chk_opt13" value="1"> Número da OS</TD>
	<TD  style="text-align: left;" colspan="2"><INPUT TYPE="text" NAME="sua_os" size="17" class='frm'></TD>
	<TD  style="text-align: center;">&nbsp;</TD>
</TR>
<TR>
	<TD  style="text-align: left;">&nbsp;</TD>
	<TD  width='150'><INPUT TYPE="checkbox" NAME="chk_opt14" value="1"> Número da NF de Compra</TD>
	<TD  style="text-align: left;" colspan="2"><INPUT TYPE="text" NAME="nota_fiscal" size="17" class='frm'></TD>
	<TD  style="text-align: center;">&nbsp;</TD>
</TR>
<tr><td colspan='5'>&nbsp;</td></tr>
<TR>
	<TD colspan="5"  style="text-align: center;"><input type="button" style="background:url(imagens_admin/btn_pesquisar_400.gif); width:400px;cursor:pointer;" value="&nbsp;" value='&nbsp;' onClick="document.frm_pesquisa.submit();" alt="Preencha as opções e clique aqui para pesquisar" ></TD>
</TR>
</TABLE>
</FORM>
<BR>

<? include "rodape.php" ?>
