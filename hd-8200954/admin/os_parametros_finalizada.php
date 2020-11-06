<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia,call_center";
include 'autentica_admin.php';

$btn_acao = trim (strtolower ($_POST['btn_acao']));

$msg_erro = "";
$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

$layout_menu = "financeiro";
$title = "Relação de Ordens de Serviços Finalizadas";

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


#container {
  border: 0px;
  padding:0px 0px 0px 0px;
  margin:0px 0px 0px 0px;
  background-color: white;
}

</style>

<? include "javascript_pesquisas.php" ?>


<br>

<FORM name="frm_pesquisa" METHOD="GET" ACTION="os_consulta_finalizada.php">
<TABLE width="500" align="center" border="0" cellspacing="0" cellpadding="2">

<TR>
	<TD colspan="4" class="menu_top"><div align="center"><b>Pesquisa</b></div></TD>
</TR>

<TR>
	<TD class="table_line" style="width: 10px"  >&nbsp;</TD>
	<TD class="table_line" rowspan=2>Data </TD>
	<TD class="table_line" align='left'>* Mês</td>
	<TD class="table_line" align='left'>* Ano</TD>
</TR>
	<tr bgcolor="#D9E2EF" align='center'>
		<TD class="table_line" style="width: 10px"  >&nbsp;</TD>
		<td  class="table_line">
			<select name="mes" size="1" class="frm">
			<option value=''></option>
			<?
			for ($i = 1 ; $i <= count($meses) ; $i++) {
				echo "<option value='$i'";
				if ($mes == $i) echo " selected";
				echo ">" . $meses[$i] . "</option>";
			}
			?>
			</select>
		</td>
		<td>
			<select name="ano" size="1" class="frm">
			<option value=''></option>
			<?
			for ($i = 2005 ; $i <= date("Y") ; $i++) {
				echo "<option value='$i'";
				if ($ano == $i) echo " selected";
				echo ">$i</option>";
			}
			?>
			</select>
		</td>
	</tr>

<TR>
	<TD colspan="4" class="table_line">&nbsp;</TD>
</TR>
<TR>
	<TD class="table_line" style="width: 10px"  >&nbsp;</TD>
	<TD rowspan="2" width="250" class="table_line" valign='middle' align='center'> Posto</TD>
	<TD width="180" class="table_line">Código do Posto</TD>
	<TD width="180" class="table_line">Nome do Posto</TD>
</TR>
<TR>
	<TD class="table_line" style="text-align: center;">&nbsp;</TD>
	<TD class="table_line" align="left" nowrap><INPUT TYPE="text" NAME="codigo_posto" SIZE="8" ><IMG src="imagens/lupa.png" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto,document.frm_pesquisa.nome_posto,'codigo')"></TD>
	<TD width="151" class="table_line" style="text-align: left;" nowrap><INPUT TYPE="text" NAME="nome_posto" size="15"> <IMG src="imagens/lupa.png" style="cursor:pointer" align='absmiddle' alt="Clique aqui para pesquisas postos pelo nome" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto,document.frm_pesquisa.nome_posto,'nome')"></TD>
</TR>
<TR>
	<TD colspan="4" class="table_line">&nbsp;</TD>
</TR>
<TR>
	<TD class="table_line" style="text-align: center;">&nbsp;</TD>
	<TD class="table_line"> Nome de Consumidor ou Revenda</TD>
	<TD class="table_line" style="text-align: left;"><INPUT TYPE="text" NAME="consumidor_nome" size="18"></TD>
	<TD class="table_line" style="text-align: center;">&nbsp;</TD>
</TR>
<TR>
	<TD colspan="4" class="table_line"><hr color='#eeeeee'></TD>
</TR>
<TR>
	<TD width="19" class="table_line" style="text-align: left;">&nbsp;</TD>
	<TD width="350" class="table_line"> Tipo de OS</TD>
	<TD width="180" class="table_line"><input type=radio name="tipo_os" value="C" <? $tipo_os=='C'; echo "checked"; ?>>Consumidor</TD>
	<TD width="180" class="table_line"><input type=radio name="tipo_os" value="R" >Revenda</TD>
</TR>
<TR>
	<TD colspan="5" class="table_line"><hr color='#eeeeee'></TD>
</TR>
<TR>
	<TD colspan="5" class="table_line" style="text-align: center;"><IMG src="imagens_admin/btn_pesquisar_400.gif" onClick="document.frm_pesquisa.submit();" style="cursor:pointer " alt="Preencha as opções e clique aqui para pesquisar"></TD>
</TR>
</TABLE>
</FORM>
<BR>

<? include "rodape.php" ?>