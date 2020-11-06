<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="call_center,gerencia";
include 'autentica_admin.php';
#::::::::::::::::::::::::::::::::::::::::
if (in_array($login_fabrica,array(1,11,19,172))) {
    header("Location: os_revenda_consulta_lite.php");
} else {
    header("Location: consulta_os_revenda.php");
}
exit;
#::::::::::::::::::::::::::::::::::::::::


$msg_erro = "";

if($login_fabrica <> 108 and $login_fabrica <> 111){
$layout_menu = "callcenter";
} else {
$layout_menu = "gerencia";
}
$title = "Rela��o de Ordens de Servi�os de Revenda Lan�adas";

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

function fnc_pesquisa_revenda (campo, tipo) {
	var url = "";
	if (tipo == "nome") {
		url = "pesquisa_revenda.php?nome=" + campo.value + "&tipo=nome";
	}
	if (tipo == "cnpj") {
		url = "pesquisa_revenda.php?cnpj=" + campo.value + "&tipo=cnpj";
	}
	janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0");
	janela.nome			= document.frm_pesquisa.nome_revenda;
	janela.cnpj			= document.frm_pesquisa.cnpj_revenda;
	janela.fone			= document.frm_pesquisa.revenda_fone;
	janela.cidade		= document.frm_pesquisa.revenda_cidade;
	janela.estado		= document.frm_pesquisa.revenda_estado;
	janela.endereco		= document.frm_pesquisa.revenda_endereco;
	janela.numero		= document.frm_pesquisa.revenda_numero;
	janela.complemento	= document.frm_pesquisa.revenda_complemento;
	janela.bairro		= document.frm_pesquisa.revenda_bairro;
	janela.cep			= document.frm_pesquisa.revenda_cep;
	janela.email		= document.frm_pesquisa.revenda_email;
	janela.focus();
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


<!--=============== <FUN��ES> ================================!-->
<!--  XIN�S POP UP CALENDAR -->

<script language="javascript" src="js/cal2.js">
/*
Xin's Popup calendar script-  Xin Yang (http://www.yxscripts.com/)
Script featured on/available at http://www.dynamicdrive.com/
This notice must stay intact for use
*/
</script>

<script language="javascript" src="js/cal_conf2.js"></script>

<DIV ID="container" style="width: 100%; ">

<br>

<FORM name="frm_pesquisa" METHOD="GET" ACTION="os_revenda_consulta.php">
<TABLE width="400" align="center" border="0" cellspacing="0" cellpadding="2">
<TR>
	<TD colspan="5" class="menu_top"><div align="center"><b>Pesquisa por Intervalo entre Datas</b></div></TD>
</TR>
<TR>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line" colspan=4><INPUT TYPE="checkbox" NAME="chk_opt1" value="1">&nbsp; OS Lan�adas Hoje</TD>
</TR>
<TR>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line" colspan=4><INPUT TYPE="checkbox" NAME="chk_opt2" value="1">&nbsp; OS Lan�adas Ontem</TD>
</TR>
<TR>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line" colspan=4><INPUT TYPE="checkbox" NAME="chk_opt3" value="1">&nbsp; OS Lan�adas Nesta Semana</TD>
</TR>
<TR>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line" colspan=4><INPUT TYPE="checkbox" NAME="chk_opt4" value="1">&nbsp; OS Lan�adas Neste M�s</TD>
</TR>
<TR>
	<TD colspan="5" class="table_line" style="text-align: left;"><IMG src="imagens_admin/btn_pesquisar_400.gif" onClick="document.frm_pesquisa.submit();" style="cursor:pointer " alt="Preencha as op��es e clique aqui para pesquisar"></TD>
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
	<TD class="table_line" align='left'><INPUT size="12" maxlength="10" TYPE="text" NAME="data_inicial_01" value='dd/mm/aaaa' onclick="this.value=''">&nbsp;<IMG src="imagens_admin/btn_lupa.gif" align='absmiddle' onclick="javascript:showCal('dataPesquisaInicial_01')" style="cursor:pointer" alt="Clique aqui para abrir o calend�rio"></TD>
	<TD class="table_line" align='left' colspan=2><INPUT size="12" maxlength="10" TYPE="text" NAME="data_final_01" value='dd/mm/aaaa' onclick="this.value=''">&nbsp;<IMG src="imagens_admin/btn_lupa.gif" align='absmiddle' onclick="javascript:showCal('dataPesquisaFinal_01')" style="cursor:pointer" alt="Clique aqui para abrir o calend�rio"></TD>
</TR>
<TR>
	<TD colspan="5" class="table_line"><hr color='#eeeeee'></TD>
</TR>
<TR>
	<TD width="19" class="table_line" style="text-align: left;">&nbsp;</TD>
	<TD rowspan="2" width="350" class="table_line"><INPUT TYPE="checkbox" NAME="chk_opt6" value="1"> Posto</TD>
	<TD width="180" class="table_line">C�digo do Posto</TD>
	<TD width="180" class="table_line">Nome do Posto</TD>
	<TD width="19" class="table_line" style="text-align: left;">&nbsp;</TD>
</TR>
<TR>
	<TD class="table_line" style="text-align: center;">&nbsp;</TD>
	<TD class="table_line" align="left" nowrap><INPUT TYPE="text" NAME="codigo_posto" SIZE="8" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto,document.frm_pesquisa.nome_posto,'codigo')" <? } ?>><IMG src="imagens_admin/btn_lupa.gif" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisar postos pelo c�digo" onclick="javascript: fnc_tamanho_minimo(document.frm_pesquisa.codigo_posto,3); fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto,document.frm_pesquisa.nome_posto,'codigo')"></TD>
	<TD width="151" class="table_line" style="text-align: left;" nowrap><INPUT TYPE="text" NAME="nome_posto" size="15" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto,document.frm_pesquisa.nome_posto,'nome')" <? } ?>> <IMG src="imagens_admin/btn_lupa.gif" style="cursor:pointer" align='absmiddle' alt="Clique aqui para pesquisas postos pelo nome" onclick="javascript: fnc_tamanho_minimo(document.frm_pesquisa.nome_posto,3); fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto,document.frm_pesquisa.nome_posto,'nome')"></TD>
	<TD width="19" class="table_line" style="text-align: center;">&nbsp;</TD>
</TR>
<TR>
	<TD colspan="5" class="table_line"><hr color='#eeeeee'></TD>
</TR>
<TR>
	<TD width="19" class="table_line" style="text-align: left;">&nbsp;</TD>
	<TD rowspan="2" width="350" class="table_line"><INPUT TYPE="checkbox" NAME="chk_opt7" value="1">Aparelho</TD>
	<TD width="100" class="table_line">Refer�ncia</TD>
	<TD width="180" class="table_line">Descri��o</TD>
	<TD width="19" class="table_line" style="text-align: left;">
	<?
	if ($login_fabrica == 1) echo "Voltagem";
	else                     echo "&nbsp;";
	?>
	</TD>
</TR>
<TR>
	<TD class="table_line" style="text-align: center;">&nbsp;</TD>
	<TD class="table_line" align="left"><INPUT TYPE="text" NAME="produto_referencia" SIZE="8" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_produto (document.frm_pesquisa.produto_referencia,document.frm_pesquisa.produto_nome,'descricao',document.frm_pesquisa.produto_voltagem)" <? } ?>><IMG src="imagens_admin/btn_lupa.gif" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisar postos pelo c�digo" onclick="javascript: fnc_tamanho_minimo(document.frm_pesquisa.produto_referencia,3); fnc_pesquisa_produto (document.frm_pesquisa.produto_referencia,document.frm_pesquisa.produto_nome,'referencia',document.frm_pesquisa.produto_voltagem)"></TD>
	<TD class="table_line" style="text-align: left;"><INPUT TYPE="text" NAME="produto_nome" size="15" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_produto (document.frm_pesquisa.produto_referencia,document.frm_pesquisa.produto_nome,'descricao',document.frm_pesquisa.produto_voltagem)" <? } ?>><IMG src="imagens_admin/btn_lupa.gif" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisas pela refer�ncia do aparelho." onclick="javascript: fnc_tamanho_minimo(document.frm_pesquisa.produto_nome,3); fnc_pesquisa_produto (document.frm_pesquisa.produto_referencia,document.frm_pesquisa.produto_nome,'descricao',document.frm_pesquisa.produto_voltagem)"></TD>
	<TD class="table_line" style="text-align: center;">
	<?
	if ($login_fabrica == 1) echo "<INPUT TYPE='text' NAME='produto_voltagem' size='5'>";
	else                     echo "&nbsp;";
	?></TD>
</TR>
<TR>
	<TD colspan="5" class="table_line"><hr color='#eeeeee'></TD>
</TR>
<TR>
	<TD width="19" class="table_line" style="text-align: left;">&nbsp;</TD>
	<TD rowspan="2" width="350" class="table_line"><INPUT TYPE="checkbox" NAME="chk_opt8" value="1">Revenda</TD>
	<TD width="100" class="table_line">CNPJ da revenda</TD>
	<TD width="180" class="table_line">Nome da revenda</TD>
	<TD width="19" class="table_line" style="text-align: left;">&nbsp;</TD>
</TR>
<TR>
	<TD class="table_line" style="text-align: center;">&nbsp;</TD>
	<TD class="table_line" align="left"><INPUT TYPE="text" NAME="cnpj_revenda" SIZE="8" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_revenda (document.frm_pesquisa.cnpj_revenda,'cnpj')" <? } ?>><IMG src="imagens_admin/btn_lupa.gif" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisar revendas pelo c�digo" onclick="javascript: fnc_tamanho_minimo(document.frm_pesquisa.cnpj_revenda,3); fnc_pesquisa_revenda (document.frm_pesquisa.cnpj_revenda,'cnpj')"></TD>
	<TD class="table_line" style="text-align: left;"><INPUT TYPE="text" NAME="nome_revenda" size="15" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_revenda (document.frm_pesquisa.nome_revenda,'nome')" <? } ?>><IMG src="imagens_admin/btn_lupa.gif" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisar pelo nome da revenda." onclick="javascript: fnc_tamanho_minimo(document.frm_pesquisa.nome_revenda,3); fnc_pesquisa_revenda (document.frm_pesquisa.nome_revenda,'nome')"></TD>
	<TD class="table_line" style="text-align: center;">&nbsp;</TD>
<input type="hidden" name="revenda_fone" value="">
<input type="hidden" name="revenda_cidade" value="">
<input type="hidden" name="revenda_estado" value="">
<input type="hidden" name="revenda_endereco" value="">
<input type="hidden" name="revenda_cep" value="">
<input type="hidden" name="revenda_numero" value="">
<input type="hidden" name="revenda_complemento" value="">
<input type="hidden" name="revenda_bairro" value="">
<input type='hidden' name = 'revenda_email'>

</TR>
<TR>
	<TD colspan="5" class="table_line"><hr color='#eeeeee'></TD>
</TR>
<TR>
	<TD class="table_line" style="text-align: center;">&nbsp;</TD>
	<TD colspan="2" class="table_line"><INPUT TYPE="checkbox" NAME="chk_opt9" value="1"> N�mero S�rie</TD>
	<TD class="table_line" style="text-align: left;"><INPUT TYPE="text" NAME="numero_serie" size="17"></TD>
	<TD class="table_line" style="text-align: center;">&nbsp;</TD>
</TR>
<TR>
	<TD class="table_line" style="text-align: left;">&nbsp;</TD>
	<TD class="table_line" colspan=2><INPUT TYPE="checkbox" NAME="chk_opt10" value="1"> Numero da OS Revenda</TD>
	<TD class="table_line" style="text-align: left;"><INPUT TYPE="text" NAME="numero_os" size="17"></TD>
	<TD class="table_line" style="text-align: center;">&nbsp;</TD>
</TR>
<TR>
	<TD colspan="5" class="table_line" style="text-align: left;"><IMG src="imagens_admin/btn_pesquisar_400.gif" onClick="document.frm_pesquisa.submit();" style="cursor:pointer " alt="Preencha as op��es e clique aqui para pesquisar"></TD>
</TR>

</TABLE>
</FORM>
<BR>

</div>

<? include "rodape.php" ?>
