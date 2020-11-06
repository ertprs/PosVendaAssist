<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';


#::::::::::::::::::::::::::::::::::::::::
header("Location: os_consulta_lite.php");
exit;
#::::::::::::::::::::::::::::::::::::::::


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

$layout_menu = "callcenter";
$title = "Relação de Ordens de Serviços Lançadas";
#$body_onload = "javascript: document.frm_os.condicao.focus()";

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

</style>

<? include "javascript_pesquisas.php" ?>


<!--=============== <FUNÇÕES> ================================!-->
<!--  XIN´S POP UP CALENDAR -->

<script language="javascript" src="js/cal2.js">
/*
Xin's Popup calendar script-  Xin Yang (http://www.yxscripts.com/)
Script featured on/available at http://www.dynamicdrive.com/
This notice must stay intact for use
*/
</script>

<script language="javascript" src="js/cal_conf2.js"></script>

<br>

<FORM name="frm_pesquisa" METHOD="GET" ACTION="os_consulta.php">
<TABLE width="450" align="center" border="0" cellspacing="0" cellpadding="2">
<TR>
	<TD colspan="5" class="menu_top"><div align="center"><b>Pesquisa por Intervalo entre Datas</b></div></TD>
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
	<TD colspan="5" class="table_line" style="text-align: center;"><IMG src="imagens_admin/btn_pesquisar_400.gif" onClick="document.frm_pesquisa.submit();" style="cursor:pointer " alt="Preencha as opções e clique aqui para pesquisar"></TD>
</TR>

<TR>
	<TD colspan="5" class="table_line"><hr color='#eeeeee'></TD>
</TR>
<TR>
	<TD colspan="5" class="table_line">
		<TABLE width="80%" align="center" border="0" cellspacing="0" cellpadding="0">
		<TR>
			<TD class="table_line"><input type="radio" name="consumidor_revenda" value="T" checked> Todas</TD>
			<TD class="table_line"><input type="radio" name="consumidor_revenda" value="C"> Consumidor</TD>
			<TD class="table_line"><input type="radio" name="consumidor_revenda" value="R"> Revenda</TD>
		</TR>
		</TABLE>
	</TD>
</TR>

<TR>
	<TD colspan="5" class="table_line"><hr color='#eeeeee'></TD>
</TR>
<TR>
	<TD class="table_line" style="text-align: center;">&nbsp;</TD>
	<TD colspan="2" class="table_line"> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Situação da OS</TD>
	<TD class="table_line">
		<select name='situacao' class='frm'>
			<option value='' selected>Todas</option>
			<option value='IS NULL'>Em aberto</option>
			<option value='NOTNULL'>Fechadas</option>
		</select>
	</TD>
	<TD class="table_line">&nbsp;</TD>
</TR>
<TR>
	<TD colspan="5" class="table_line"><hr color='#eeeeee'></TD>
</TR>
<TR>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line" colspan="2"><INPUT TYPE="checkbox" NAME="chk_opt15" value="1">&nbsp;OS lançadas em aberto</TD>
	<TD class="table_line" align='left' colspan='2'>Quantidade de dias em aberto</TD>
</TR>
<TR>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line" align='left'>&nbsp;</TD>
	<TD class="table_line" align='left' colspan=2><INPUT size="2" maxlength="2" TYPE="text" NAME="dia_em_aberto" value="" onclick="this.value=''"></TD>
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
	<TD class="table_line" align='left'><INPUT size="12" maxlength="10" TYPE="text" NAME="data_inicial_01" value="dd/mm/aaaa" onclick="this.value=''">&nbsp;<IMG src="imagens_admin/btn_lupa.gif" align='absmiddle' onclick="javascript:showCal('dataPesquisaInicial_01')" style="cursor:pointer" alt="Clique aqui para abrir o calendário"></TD>
	<TD class="table_line" align='left' colspan=2><INPUT size="12" maxlength="10" TYPE="text" NAME="data_final_01" value="dd/mm/aaaa" onclick="this.value=''">&nbsp;<IMG src="imagens_admin/btn_lupa.gif" align='absmiddle' onclick="javascript:showCal('dataPesquisaFinal_01')" style="cursor:pointer" alt="Clique aqui para abrir o calendário"></TD>
</TR>
<TR>
	<TD colspan="5" class="table_line"><hr color='#eeeeee'></TD>
</TR>
<TR>
	<TD width="19" class="table_line" style="text-align: left;">&nbsp;</TD>
	<TD rowspan="2" width="350" class="table_line"><INPUT TYPE="checkbox" NAME="chk_opt6" value="1"> Posto</TD>
	<TD width="180" class="table_line">Código do Posto</TD>
	<TD width="180" class="table_line">Nome do Posto</TD>
	<TD width="19" class="table_line" style="text-align: left;">Estado</TD>
</TR>
<TR>
	<TD class="table_line" style="text-align: center;">&nbsp;</TD>
	<TD class="table_line" align="left"><INPUT TYPE="text" NAME="codigo_posto" SIZE="8" <? if ($login_fabrica == 5) { ?> onblur="fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto,document.frm_pesquisa.nome_posto,'codigo')" <? } ?>><IMG src="imagens_admin/btn_lupa.gif" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_tamanho_minimo(document.frm_pesquisa.codigo_posto,3); fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto,document.frm_pesquisa.nome_posto,'codigo')"></TD>
	<TD width="151" class="table_line" style="text-align: left;"><INPUT TYPE="text" NAME="nome_posto" size="15" <? if ($login_fabrica == 5) { ?> onblur="fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto,document.frm_pesquisa.nome_posto,'nome')" <? } ?>> <IMG src="imagens_admin/btn_lupa.gif" style="cursor:pointer" align='absmiddle' alt="Clique aqui para pesquisas postos pelo nome" onclick="javascript: fnc_tamanho_minimo(document.frm_pesquisa.nome_posto,3); fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto,document.frm_pesquisa.nome_posto,'nome')"></TD>
	<TD class="table_line" style="text-align: left;">
		<select name='uf_posto'>
			<? selectUF($uf); ?>
		</select>
	</TD>
</TR>
<TR>
	<TD colspan="5" class="table_line"><hr color='#eeeeee'></TD>
</TR>
<TR>
	<TD width="19" class="table_line" style="text-align: left;">&nbsp;</TD>
	<TD rowspan="2" width="350" class="table_line"><INPUT TYPE="checkbox" NAME="chk_opt7" value="1">Aparelho</TD>
	<TD width="100" class="table_line">Referência</TD>
	<TD width="180" class="table_line">Descrição</TD>
	<TD width="19" class="table_line" style="text-align: left;">&nbsp;</TD>
</TR>
<TR>
	<TD class="table_line" style="text-align: center;">&nbsp;</TD>
	<TD class="table_line" align="left"><INPUT TYPE="text" NAME="produto_referencia" SIZE="8" <? if ($login_fabrica == 5) { ?> onblur="fnc_pesquisa_produto (document.frm_pesquisa.produto_referencia,document.frm_pesquisa.produto_nome,'referencia')" <? } ?>><IMG src="imagens_admin/btn_lupa.gif" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_tamanho_minimo(document.frm_pesquisa.produto_referencia,3); fnc_pesquisa_produto (document.frm_pesquisa.produto_referencia,document.frm_pesquisa.produto_nome,'referencia')"></TD>
	<TD class="table_line" style="text-align: left;"><INPUT TYPE="text" NAME="produto_nome" size="15" <? if ($login_fabrica == 5) { ?> onblur="fnc_pesquisa_produto (document.frm_pesquisa.produto_referencia,document.frm_pesquisa.produto_nome,'descricao')" <? } ?>><IMG src="imagens_admin/btn_lupa.gif" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisas pela referência do aparelho." onclick="javascript: fnc_tamanho_minimo(document.frm_pesquisa.produto_nome,3); fnc_pesquisa_produto (document.frm_pesquisa.produto_referencia,document.frm_pesquisa.produto_nome,'descricao')"></TD>
	<TD class="table_line" style="text-align: center;">&nbsp;</TD>
</TR>
<TR>
	<TD colspan="5" class="table_line"><hr color='#eeeeee'></TD>
</TR>
<TR>
	<TD class="table_line" style="text-align: center;">&nbsp;</TD>
	<TD colspan="2" class="table_line"><INPUT TYPE="checkbox" NAME="chk_opt8" value="1"> Número Série</TD>
	<TD class="table_line" style="text-align: left;"><INPUT TYPE="text" NAME="numero_serie" size="17"></TD>
	<TD class="table_line" style="text-align: center;">&nbsp;</TD>
</TR>
<TR>
	<TD class="table_line" style="text-align: center;">&nbsp;</TD>
	<TD colspan="2" class="table_line"><INPUT TYPE="checkbox" NAME="chk_opt9" value="1"> Nome do Consumidor</TD>
	<TD class="table_line" style="text-align: left;"><INPUT TYPE="text" NAME="nome_consumidor" size="17"><!-- IMG src="imagens_admin/btn_lupa.gif" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisas pelo nome do consumidor." onclick="javascript: fnc_pesquisa_consumidor (document.frm_pesquisa.nome_consumidor,'nome')"--></TD>
	<TD class="table_line" style="text-align: center;">&nbsp;</TD>
</TR>
<TR>
	<TD class="table_line" style="text-align: center;">&nbsp;</TD>
	<TD class="table_line" colspan=2><INPUT TYPE="checkbox" NAME="chk_opt10" value="1"> CPF/CNPJ do Consumidor</TD>
	<TD class="table_line" align="left"><INPUT TYPE="text" NAME="cpf_consumidor" size="17"><!-- IMG src="imagens_admin/btn_lupa.gif" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisar um consumidor pelo seu CPF" onclick="javascript: fnc_tamanho_minimo(document.frm_pesquisa.codigo_posto,3); fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto,document.frm_pesquisa.nome_posto,'codigo')" --></TD>
	<TD class="table_line" style="text-align: center;">&nbsp;</TD>
</TR>
<TR>
	<TD class="table_line" style="text-align: left;">&nbsp;</TD>
	<TD class="table_line" colspan=2><INPUT TYPE="checkbox" NAME="chk_opt11" value="1"> Cidade</TD>
	<TD class="table_line" style="text-align: left;"><INPUT TYPE="text" NAME="cidade" size="17"></TD>
	<TD class="table_line" style="text-align: center;">&nbsp;</TD>
</TR>
<TR>
	<TD class="table_line" style="text-align: left;">&nbsp;</TD>
	<TD class="table_line" colspan=2><INPUT TYPE="checkbox" NAME="chk_opt12" value="1"> UF</TD>
	<TD class="table_line" style="text-align: left;">
		<select name='uf'>
			<? selectUF($uf); ?>
		</select>
	</TD>
	<TD class="table_line" style="text-align: center;">&nbsp;</TD>
</TR>
<TR>
	<TD class="table_line" style="text-align: left;">&nbsp;</TD>
	<TD class="table_line" colspan=2><INPUT TYPE="checkbox" NAME="chk_opt13" value="1"> Número da OS</TD>
	<TD class="table_line" style="text-align: left;"><INPUT TYPE="text" NAME="numero_os" size="17"></TD>
	<TD class="table_line" style="text-align: center;">&nbsp;</TD>
</TR>
<TR>
	<TD class="table_line" style="text-align: left;">&nbsp;</TD>
	<TD colspan="2" class="table_line"><INPUT TYPE="checkbox" NAME="chk_opt14" value="1"> Número da NF de Compra</TD>
	<TD class="table_line" style="text-align: left;"><INPUT TYPE="text" NAME="numero_nf" size="17"></TD>
	<TD class="table_line" style="text-align: center;">&nbsp;</TD>
</TR>
<TR>
	<TD colspan="5" class="table_line" style="text-align: center;"><IMG src="imagens_admin/btn_pesquisar_400.gif" onClick="document.frm_pesquisa.submit();" style="cursor:pointer " alt="Preencha as opções e clique aqui para pesquisar"></TD>
</TR>
</TABLE>
</FORM>
<BR>

<? include "rodape.php" ?>