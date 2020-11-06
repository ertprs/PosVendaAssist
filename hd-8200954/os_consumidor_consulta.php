<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia,call_center";
include 'autentica_admin.php';

if (strlen($_POST["acao"]) > 0) $acao = strtoupper($_POST["acao"]);

$msg_erro = "";

if (strlen($acao) > "PESQUISAR") {
	##### Pesquisa entre datas #####
	$x_data_inicial = trim($_POST["data_inicial"]);
	$x_data_final   = trim($_POST["data_final"]);
	if ($x_data_inicial != "dd/mm/aaaa" && $x_data_final != "dd/mm/aaaa") {

		if (strlen($x_data_inicial) > 0) {
			$x_data_inicial = fnc_formata_data_pg($x_data_inicial);
			$x_data_inicial = str_replace("'", "", $x_data_inicial);
			$dia_inicial    = substr($x_data_inicial, 8, 2);
			$mes_inicial    = substr($x_data_inicial, 5, 2);
			$ano_inicial    = substr($x_data_inicial, 0, 4);
			$data_inicial = date("01/m/Y H:i:s", mktime(0, 0, 0, $mes_inicial, $dia_inicial, $ano_inicial));
		}else{
			$msg .= " Preencha o campo Data Inicial para realizar a pesquisa. ";
		}

		if (strlen($x_data_final) > 0) {
			$x_data_final = fnc_formata_data_pg($x_data_final);
			$x_data_final = str_replace("'", "", $x_data_final);
			$dia_final    = substr($x_data_final, 8, 2);
			$mes_final    = substr($x_data_final, 5, 2);
			$ano_final    = substr($x_data_final, 0, 4);
			$data_final   = date("t/m/Y H:i:s", mktime(23, 59, 59, $mes_final, $dia_final, $ano_final));
		}else{
			$msg .= " Preencha o campo Data Final para realizar a pesquisa. ";
		}
	}else{
		$msg .= " Informe as datas corretas para realizar a pesquisa. ";
	}
}

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

include "cabecalho.php";
?>

<style type="text/css">
.menu {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}
.table {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF
}
</style>

<? include "javascript_pesquisas.php" ?>

<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>

<form name="frm_consulta" method="post" action="<?echo $PHP_SELF?>">

<input type="hidden" name="acao">

<table width="450" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr class="menu">
		<td colspan="5">Pesquisa OS do Consumidor</td>
	</tr>
	<tr class="table">
		<td width="10">&nbsp;</td>
		<td>Data Inicial</td>
		<td colspan="2">Data Final</td>
		<td width="10">&nbsp;</td>
	</tr>
	<tr class="table">
		<td>&nbsp;</td>
		<td>
			<input size="12" maxlength="10" TYPE="text" NAME="data_inicial" value="<?if (strlen($data_inicial) == 0) echo 'dd/mm/aaaa'; else echo $data_inicial;?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') { this.value = ''; }">
			<img src="imagens_admin/btn_lupa.gif" align='absmiddle' onclick="javascript:showCal('DataPesquisaInicial')" style="cursor: hand;" alt="Clique aqui para abrir o calendário">
		</td>
		<td colspan="2">
			<input size="12" maxlength="10" TYPE="text" NAME="data_final" value="<?if (strlen($data_final) == 0) echo 'dd/mm/aaaa'; else echo $data_final;?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') { this.value = ''; }">
			<img src="imagens_admin/btn_lupa.gif" align='absmiddle' onclick="javascript:showCal('DataPesquisaFinal')" style="cursor: hand;" alt="Clique aqui para abrir o calendário">
		</td>
		<td>&nbsp;</td>
	</TR>
	<TR class="table">
		<TD colspan="5"><hr color='#eeeeee'></TD>
	</TR>
	<TR class="table">
		<TD>&nbsp;</TD>
		<TD>Código do Posto</TD>
		<TD>Nome do Posto</TD>
		<TD>Estado</TD>
		<TD>&nbsp;</TD>
	</TR>
	<TR class="table">
		<TD>&nbsp;</TD>
		<td><input type="text" name="posto_codigo" size="8" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_consulta.posto_codigo, document.frm_consulta.posto_nome,'codigo');" <? } ?>><IMG src="imagens_admin/btn_lupa.gif" style="cursor: hand;" align='absmiddle' alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_consulta.posto_codigo, document.frm_consulta.posto_nome,'codigo');"></td>
		<td><input type="text" name="posto_nome" size="15" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_consulta.posto_codigo, document.frm_consulta.posto_nome,'nome');" <? } ?>> <IMG src="imagens_admin/btn_lupa.gif" style="cursor: hand;" align='absmiddle' alt="Clique aqui para pesquisas postos pelo nome" onclick="javascript: fnc_pesquisa_posto (document.frm_consulta.posto_codigo, document.frm_consulta.posto_nome,'nome');"></td>
		<TD>
			<select name='uf_posto'>
				<? selectUF($uf); ?>
			</select>
		</TD>
		<td>&nbsp;</td>
	</tr>
	<tr class="table">
		<td colspan="5"><hr color="#EEEEEE"></td>
	</tr>
	<tr class="table">
		<td colspan="5"><img src="imagens_admin/btn_pesquisar_400.gif" onclick="javascript: document.frm_relatorio.acao.value='PESQUISAR'; document.frm_consulta.submit();" style="cursor: hand;" alt="Preencha as opções e clique aqui para pesquisar"></td>
	</tr>
</table>
</form>

<? include "rodape.php" ?>
