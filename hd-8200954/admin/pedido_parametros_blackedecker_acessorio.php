<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia,call_center";
include 'autentica_admin.php';

$btn_acao = trim (strtolower ($_POST['btn_acao']));

$msg_erro = "";

$layout_menu = "callcenter";
$title = "RELAÇAO DE PEDIDOS LANÇADOS";
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


function fnc_pesquisa_peca (campo, campo2, tipo) {
	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}


	if (xcampo.value != "") {
		var url = "";
		url = "peca_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=18, left=0");
		peca_referencia	= campo;
		peca_descricao	= campo2;
		janela.focus();
	}

	else{
		alert("Informe toda ou parte da informação para realizar a pesquisa");
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
<? include "javascript_calendario_new.php";
    include "../js/js_css.php";
?>

<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial_01').datepick({startDate:'01/01/2000'});
		$('#data_final_01').datepick({startDate:'01/01/2000'});
		$("#data_inicial_01").mask("99/99/9999");
		$("#data_final_01").mask("99/99/9999");
	});
</script>

<!-- Valida formularios -->
<script language="javascript">
function fcn_valida_formDatas()
{
	f = document.frm_pesquisa1;
/*	if(f.data_inicial_01.value.length < 10)
	{
		alert('Digite a Data Inicial');
		f.data_inicial_01.focus();
		return false;
	}
	if(f.data_final_01.value.length < 10)
	{
		alert('Digite a Data Final');
		f.data_final_01.focus();
		return false;
	}
	if(f.codigo_posto.value == "")
	{
		alert('Digite o Código do Posto');
		f.codigo_posto.focus();
		return false;
	}
	if(f.nome_posto.value == "")
	{
		alert('Digite o Nome do Posto');
		f.nome_posto.focus();
		return false;
	}
*/
	f.submit();
}

function fcn_valida_formDatas2()
{
	f = document.frm_pesquisa2;
/*
	// verifica se algum radio foi selecionado
	var total = 0;
	var max = f.radioDatas.length;
	for (var idx = 0; idx < max; idx++)
	{
		if (eval("f.radioDatas[" + idx + "].checked") == true)
		{
			total += 1;
		}
	}
	if(total == 0)
	{
		alert('Selecione uma das opções');
		return false;
	}else{
		if(f.radioDatas['0'].checked == true)
		{
			if(f.data_inicial_02.value.length < 10)
			{
				alert('Digite a Data Inicial');
				f.data_inicial_02.focus();
				return false;
			}
			if(f.data_final_02.value.length < 10)
			{
				alert('Digite a Data Final');
				f.data_final_02.focus();
				return false;
			}
		}
		if(f.radioDatas['5'].checked == true)
		{
			if(f.codigo_posto.value == "")
			{
				alert('Digite o Código do Posto');
				f.codigo_posto.focus();
				return false;
			}
			if(f.nome_posto.value == "")
			{
				alert('Digite o Nome do Posto');
				f.nome_posto.focus();
				return false;
			}
		}
	}
*/
	f.submit();
}
</script>

<DIV ID="container" style="width: 100%; ">

<!-- =========== PESQUISA POR INTERVALO ENTRE DATAS ============ -->

<?
if ($login_fabrica == 8){
?>
<FORM name="frm_pesquisa1" METHOD="POST" ACTION="pedido_nao_exportado_consulta.php">
<br>
<TABLE width="400" align="center" border="0" cellspacing="0" cellpadding="2">
<TR>
	<TD class="menu_top"><div align="center"><b>Exibe todos pedidos não exportados</b></div></TD>
</TR>
<TR>
	<TD  style="text-align: center;"><IMG src="imagens/btn_exibirpedidosnaoexportados.gif" onclick="javascript: fcn_valida_formDatas();" style="cursor:pointer " alt="Preencha as opções e clique aqui para pesquisar"></TD>
</TR>
</TABLE>
</form>
<BR>

<FORM name="frm_pesquisa3" METHOD="POST" ACTION="pedido_nao_faturado_consulta.php">
<TABLE width="400" align="center" border="0" cellspacing="0" cellpadding="2">
<TR>
	<TD class="menu_top"><div align="center"><b>Exibe todos pedidos exportados e não faturados</b></div></TD>
</TR>
<TR>
	<TD  style="text-align: center;"><IMG src="imagens/btn_exibirpedidosnaofaturados.gif" onclick="javascript:submit();" style="cursor:pointer " alt="Preencha as opções e clique aqui para pesquisar"></TD>
</TR>
</TABLE>
</form>
<BR>

<?
}
?>

<FORM name="frm_pesquisa2" METHOD="GET" ACTION="pedido_consulta_blackedecker_acessorio.php">

<TABLE width="700" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">
<TR class="titulo_tabela">
	<TD colspan="4" >Pesquisa por Intervalo entre Datas</TD>
</TR>
<TR>
	<TD  style="width: 100px">&nbsp;</TD>
	<TD colspan="3" ><INPUT TYPE="checkbox" NAME="chk_opt1" value="1">&nbsp;Pedidos Lançados Hoje</TD>
</TR>
<TR>
	<TD >&nbsp;</TD>
	<TD colspan="3" ><INPUT TYPE="checkbox" NAME="chk_opt2" value="2">&nbsp;Pedidos Lançados Ontem</TD>
</TR>
<TR>
	<TD >&nbsp;</TD>
	<TD colspan="3" ><INPUT TYPE="checkbox" NAME="chk_opt3" value="3">&nbsp;Pedidos Lançados Nesta Semana</TD>
</TR>
<TR>
	<TD >&nbsp;</TD>
	<TD colspan="3" ><INPUT TYPE="checkbox" NAME="chk_opt4" value="3">&nbsp;Pedidos Lançados Na Semana Anterior</TD>
</TR>
<TR>
	<TD >&nbsp;</TD>
	<TD colspan="3" ><INPUT TYPE="checkbox" NAME="chk_opt5" value="4">&nbsp;Pedidos Lançados Neste Mês</TD>
</TR>
<TR>
	<TD colspan="4"  style="text-align: center;"><input type="button" style="background:url(imagens_admin/btn_pesquisar_400.gif); width:400px;cursor:pointer;" value="&nbsp;" onclick="javascript: fcn_valida_formDatas2();"  alt="Preencha as opções e clique aqui para pesquisar"></TD>
</TR>
<TR>
	<TD colspan="4"  ><hr></TD>
</TR>
<TR>
	<TD >&nbsp;</TD>
	<TD ><INPUT TYPE="checkbox" NAME="chk_opt6" value="5" nowrap>&nbsp;Entre Datas</TD>
	<TD >Data Inicial</TD>
	<TD >Data Final</TD>
</TR>
<TR>
	<TD >&nbsp;</TD>
	<TD >&nbsp;</TD>
	<TD ><INPUT size="12" maxlength="10" TYPE="text" NAME="data_inicial_01" id="data_inicial_01" value='dd/mm/aaaa' onclick="this.value=''" class="frm"></TD>
	<TD ><INPUT size="12" maxlength="10" TYPE="text" NAME="data_final_01" id="data_final_01"  value='dd/mm/aaaa' onclick="this.value=''" class="frm"></TD>
</TR>
<TR>
	<TD colspan="4"  ></TD>
</TR>
<TR>
	<TD >&nbsp;</TD>
	<TD ><INPUT TYPE="checkbox" NAME="chk_opt7" value="6">&nbsp;Posto</TD>
	<TD >Código do Posto</TD>
	<TD >Posto Autorizado</TD>
</TR>
<TR>
	<TD >&nbsp;</TD>
	<TD >&nbsp;</TD>
	<TD  align="left"><INPUT TYPE="text" NAME="codigo_posto" SIZE="10" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_pesquisa2.codigo_posto,document.frm_pesquisa2.nome_posto,'codigo')" <? } ?> class="frm"><IMG src="imagens/lupa.png" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa2.codigo_posto,document.frm_pesquisa2.nome_posto,'codigo')"></TD>
	<TD  align="left"><INPUT TYPE="text" NAME="nome_posto" SIZE="15" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_pesquisa2.codigo_posto,document.frm_pesquisa2.nome_posto,'nome')" <? } ?> class="frm"><IMG src="imagens/lupa.png" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisas postos pelo nome" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa2.codigo_posto,document.frm_pesquisa2.nome_posto,'nome')"></TD>
</TR>
<TR>
	<TD colspan="4"  ></TD>
</TR>
<? if ($login_fabrica == 1) { ?>
<TR>
	<TD >&nbsp;</TD>
	<TD ><INPUT TYPE="checkbox" NAME="chk_opt8" value="7">&nbsp;Peça</TD>
	<TD >Referência</TD>
	<TD >Descrição</TD>
</TR>
<TR>
	<TD >&nbsp;</TD>
	<TD >&nbsp;</TD>
	<TD  align="left"><INPUT TYPE="text" NAME="peca_referencia" SIZE="10" class="frm"><IMG src="imagens/lupa.png" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_peca ( document.frm_pesquisa2.peca_referencia, document.frm_pesquisa2.peca_descricao , 'referencia')"></TD>
	<TD  align="left"><INPUT TYPE="text" NAME="peca_descricao" size="15" class="frm"><IMG src="imagens/lupa.png" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisas pela referência do aparelho." onclick="javascript: fnc_pesquisa_peca ( document.frm_pesquisa2.peca_referencia, document.frm_pesquisa2.peca_descricao , 'descricao')"></TD>
</TR>
<? }else{ ?>
<TR>
	<TD >&nbsp;</TD>
	<TD ><INPUT TYPE="checkbox" NAME="chk_opt8" value="7">&nbsp;Aparelho</TD>
	<TD >Referência</TD>
	<TD >Descrição</TD>
</TR>
<TR>
	<TD >&nbsp;</TD>
	<TD >&nbsp;</TD>
	<!--
	<TD  align="left"><INPUT TYPE="text" NAME="peca_referencia" SIZE="10"><IMG src="imagens/lupa.png" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisar postos pelo código" onclick="fnc_pesquisa_peca (document.frm_pesquisa2.peca_referencia, document.frm_pesquisa2.peca_descricao, 'referencia')"></TD>
	<TD  align="left"><INPUT TYPE="text" NAME="peca_descricao" size="15"><IMG src="imagens/lupa.png" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisas pela referência do aparelho." onclick="fnc_pesquisa_peca (document.frm_pesquisa2.peca_referencia, document.frm_pesquisa2.peca_descricao, 'descricao')"></TD>
	-->
	<TD  align="left"><INPUT TYPE="text" NAME="produto_referencia" SIZE="10" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_produto(document.frm_pesquisa2.produto_referencia, document.frm_pesquisa2.produto_nome, 'referencia')" <? } ?> class="frm"><IMG src="imagens/lupa.png" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_tamanho_minimo(document.frm_pesquisa2.produto_referencia,3); fnc_pesquisa_produto(document.frm_pesquisa2.produto_referencia, document.frm_pesquisa2.produto_nome, 'referencia')"></TD>
	<TD  align="left"><INPUT TYPE="text" NAME="produto_nome" size="15" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_produto(document.frm_pesquisa2.produto_referencia, document.frm_pesquisa2.produto_nome, 'descricao')" <? } ?> class="frm"><IMG src="imagens/lupa.png" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisas pela referência do aparelho." onclick="javascript: fnc_tamanho_minimo(document.frm_pesquisa2.produto_nome,3); fnc_pesquisa_produto(document.frm_pesquisa2.produto_referencia, document.frm_pesquisa2.produto_nome, 'descricao')"></TD>
</TR>
<? } ?>
<TR>
	<TD colspan="4"  ></TD>
</TR>
<TR>
	<TD >&nbsp;</TD>
	<TD colspan="2" ><INPUT TYPE="checkbox" NAME="chk_opt9" value="13">&nbsp;Número do Pedido</TD>
	<TD  align="left"><INPUT TYPE="text" NAME="numero_pedido" size="17" class="frm"></TD>
</TR>
<? if ($login_fabrica == 1) { ?>
<TR>
	<TD colspan="4"  ></TD>
</TR>
<TR>
	<TD >&nbsp;</TD>
	<TD colspan="2" ><INPUT TYPE="checkbox" NAME="chk_opt10" value="finalizado">&nbsp;Pedido Não Finalizado</TD>
	<TD  align="left">&nbsp;</TD>
</TR>
<? } ?>
<TR>
	<TD colspan="4"  style="text-align: center;">&nbsp;</TD>
</TR>
<TR>
	<TD colspan="4"  style="text-align: center;"><input type="button" style="background:url(imagens_admin/btn_pesquisar_400.gif); width:400px;cursor:pointer;" value="&nbsp;" onclick="javascript: fcn_valida_formDatas2();" alt="Preencha as opções e clique aqui para pesquisar"></TD>
</TR>
</TABLE>
</FORM>

<? include "rodape.php" ?>