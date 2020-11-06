<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$btn_acao = trim (strtolower ($_POST['btn_acao']));

$msg_erro = "";

if ($btn_acao == "gravar") {
}


$layout_menu = "os";
$title = "Seleção de Parâmetros para Relação de Ordens de Serviços Lançadas";

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

<script language="javascript" src="js/cal2.js">
/*
Xin's Popup calendar script-  Xin Yang (http://www.yxscripts.com/)
Script featured on/available at http://www.dynamicdrive.com/
This notice must stay intact for use
*/
</script>

<script language="javascript" src="js/cal_conf2.js"></script>
<script language="javascript" src="js/assist.js"></script>

<br>

<FORM name="frm_pesquisa" METHOD="GET" ACTION="os_manutencao_consulta.php">
<TABLE width="400" align="center" border="0" cellspacing="0" cellpadding="2">
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
	<TD colspan="5" class="table_line" style="text-align: left;"><IMG src="imagens/btn_pesquisar_400.gif" onClick="document.frm_pesquisa.submit();" style="cursor:pointer " alt="Preencha as opções e clique aqui para pesquisar"></TD>
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
	<TD class="table_line"><INPUT TYPE="checkbox" NAME="chk_opt5" value="1">&nbsp;Entre datas</TD>
	<TD class="table_line" align='left'>Data Inicial</TD>
	<TD class="table_line" align='left' colspan='2'>Data Final</TD>
</TR>
<TR>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line" align='left'><INPUT size="12" maxlength="10" TYPE="text" NAME="data_inicial_01" value="dd/mm/aaaa" onclick="this.value=''">&nbsp;<IMG src="imagens/btn_lupa.gif" align='absmiddle' onclick="javascript:showCal('dataPesquisaInicial_01')" style="cursor:pointer" alt="Clique aqui para abrir o calendário"></TD>
	<TD class="table_line" align='left' colspan=2><INPUT size="12" maxlength="10" TYPE="text" NAME="data_final_01" value="dd/mm/aaaa" onclick="this.value=''">&nbsp;<IMG src="imagens/btn_lupa.gif" align='absmiddle' onclick="javascript:showCal('dataPesquisaFinal_01')" style="cursor:pointer" alt="Clique aqui para abrir o calendário"></TD>
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
	<TD class="table_line" align="left"><INPUT TYPE="text" NAME="produto_referencia" SIZE="8">&nbsp;<IMG src="imagens/btn_lupa.gif" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_tamanho_minimo(document.frm_pesquisa.produto_referencia,3); fnc_pesquisa_produto (document.frm_pesquisa.produto_referencia,document.frm_pesquisa.produto_nome,'referencia')"></TD>
	<TD class="table_line" style="text-align: left;"><INPUT TYPE="text" NAME="produto_nome" size="15">&nbsp;<IMG src="imagens/btn_lupa.gif" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisas pela referência do aparelho." onclick="javascript: fnc_tamanho_minimo(document.frm_pesquisa.produto_nome,3); fnc_pesquisa_produto (document.frm_pesquisa.produto_referencia,document.frm_pesquisa.produto_nome,'descricao')"></TD>
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
	<TD class="table_line" style="text-align: left;"><INPUT TYPE="text" NAME="nome_consumidor" size="17"><!-- IMG src="imagens/btn_lupa.gif" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisas pelo nome do consumidor." onclick="javascript: fnc_pesquisa_consumidor (document.frm_pesquisa.nome_consumidor,'nome')"--></TD>
	<TD class="table_line" style="text-align: center;">&nbsp;</TD>
</TR>
<TR>
	<TD class="table_line" style="text-align: center;">&nbsp;</TD>
	<TD class="table_line" colspan=2><INPUT TYPE="checkbox" NAME="chk_opt10" value="1"> CPF/CNPJ do Consumidor</TD>
	<TD class="table_line" align="left"><INPUT TYPE="text" NAME="cpf_consumidor" size="17"><!-- IMG src="imagens/btn_lupa.gif" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisar um consumidor pelo seu CPF" onclick="javascript: fnc_tamanho_minimo(document.frm_pesquisa.codigo_posto,3); fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto,document.frm_pesquisa.nome_posto,'codigo')" --></TD>
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
	<TD class="table_line" style="text-align: left;"><INPUT TYPE="text" NAME="uf" size="17"></TD>
	<TD class="table_line" style="text-align: center;">&nbsp;</TD>
</TR>
<TR>
	<TD class="table_line" style="text-align: left;">&nbsp;</TD>
	<TD class="table_line" colspan=2><INPUT TYPE="checkbox" NAME="chk_opt13" value="1"> Numero da OS</TD>
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
	<TD colspan="5" class="table_line" style="text-align: left;"><IMG src="imagens/btn_pesquisar_400.gif" onClick="document.frm_pesquisa.submit();" style="cursor:pointer " alt="Preencha as opções e clique aqui para pesquisar"></TD>
</TR>
</TABLE>

</FORM>

<BR>

<? include "rodape.php" ?>
