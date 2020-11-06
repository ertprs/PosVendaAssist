<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

include 'autentica_admin.php';

include 'funcoes.php';

$layout_menu = "callcenter";
$title = "RELATÓRIO INDICAÇÃO POSTO";

include "cabecalho.php";
$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");
$mes = $_POST["mes"];
$ano = $_POST["ano"];
if(empty($mes)) $mes = date('m');
if(empty($ano)) $ano = date('Y');
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

}

</style>

<br>

<form name="frm_relatorio" method="post" action="<? echo $PHP_SELF ?>">

<table width="400" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr>
		<td colspan="5" class="menu_top"><b>Relatório Indicação de Posto</b></td>
	</tr>
	<tr>
		<td class="table_line" style="width: 10px">&nbsp;</td>
		<td class="table_line">&nbsp;</td>
		<td class="table_line">Mês</td>
		<td class="table_line" align='left'>Ano</td>
		<td class="table_line" align='left' colspan='2'>&nbsp;</td>
	</tr>
	<tr>
		<td width="19" class="table_line" style="text-align: left;">&nbsp;</td>
		<td width="19" class="table_line" style="text-align: left;">&nbsp;</td>
		<td class="table_line">
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
		<td class="table_line">
			<select name="ano" size="1" class="frm">
			<option value=''></option>
			<?
			for ($i = 2003 ; $i <= date("Y") ; $i++) {
				echo "<option value='$i'";
				if ($ano == $i) echo " selected";
				echo ">$i</option>";
			}
			?>
			</select>
		</td>
		<td width="19" class="table_line" style="text-align: left;">&nbsp;</td>
	</tr>
	<tr>
		<input class="botao" type="hidden" name="btn_acao"  value=''>
		<td colspan="5" class="table_line" style="text-align: left;"><img src="imagens_admin/btn_pesquisar_400.gif" onclick="javascript:
		if(document.frm_relatorio.btn_acao.value!='') {alert('Aguarde submissão')}else{document.frm_relatorio.btn_acao.value='Consultar';document.frm_relatorio.submit();}">
	</tr>
</table>
</form>


<?
$btn_acao = $_POST['btn_acao'];
if(strlen($btn_acao)>0){
	$mes = $_POST["mes"];
	$ano = $_POST["ano"];
	if (strlen($mes) > 0 AND strlen($ano) > 0) {
		$data_inicial = date("Y-m-01 00:00:00", mktime(0, 0, 0, $mes, 1, $ano));
		$data_final   = date("Y-m-t 23:59:59", mktime(0, 0, 0, $mes, 1, $ano));
	}else $msg_erro = "Selecione o Mês e o Ano para fazer a pesquisa";

	if(strlen($msg_erro)==0){
		$sql = "SELECT COUNT(hd_chamado) AS qtde, 
						TO_CHAR(data,'DD/MM/YYYY') AS data_chamado 
				FROM tbl_hd_chamado
				WHERE fabrica_responsavel    =     $login_fabrica 
				AND   titulo                 =    'Indicação de Posto' 
				AND   data                BETWEEN '$data_inicial' AND '$data_final' 
				GROUP BY data_chamado 
				ORDER BY data_chamado";

		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){

			echo "<br>";
			echo "<table width='300' border='0' align='center' cellpadding='1' cellspacing='2' style='border:#485989 1px solid; background-color: #e6eef7;font-size:11px'>";
			echo "<tr>\n";
			echo "<td class='menu_top'>data</td>\n";
			echo "<td class='menu_top'>quantidade</td>\n";
			echo "</tr >\n";
			for($i=0;pg_numrows($res)>$i;$i++){
				$data = pg_result($res,$i,data_chamado);
				$dia = substr($data,0,2);
				$qtde = pg_result($res,$i,qtde);
				if ($i % 2 == 0) {$cor = '#F1F4FA';}else{$cor = '#e6eef7';}
				echo "<tr bgcolor='$cor'>\n";
				echo "<td align='center' nowrap>Dia $dia</td>\n";
				echo "<td align='center' nowrap>$qtde</td>\n";
				echo "</tr >\n";
			}
			echo "</table>";
		}else $msg_erro = "Não há Indicação de Posto no período.";
	}
}

if(strlen($msg_erro)>0){
	echo "<br>";
	echo "<center>$msg_erro</center>";
}
?>

<p>

<? include "rodape.php" ?>
