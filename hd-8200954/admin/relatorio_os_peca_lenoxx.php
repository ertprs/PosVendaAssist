<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
include "funcoes.php";
include 'autentica_admin.php';
$admin_privilegios="auditoria";

if ($btn_finalizar == 1) {
	if (strlen($erro) == 0) {
		if (strlen($_POST["data_inicial_01"]) == 0) {
			$erro .= "Favor informar a data inicial para pesquisa<br>";
		}
		
		if (strlen($erro) == 0) {
			$data_inicial   = trim($_POST["data_inicial_01"]);
			$fnc            = @pg_exec($con,"SELECT fnc_formata_data('$data_inicial')");
			
			if (strlen ( pg_errormessage ($con) ) > 0) {
				$erro = pg_errormessage ($con) ;
			}
			
			if (strlen($erro) == 0) $aux_data_inicial = @pg_result ($fnc,0,0);
		}
	}
	
	if (strlen($erro) == 0) {
		if (strlen($_POST["data_final_01"]) == 0) {
			$erro .= "Favor informar a data final para pesquisa<br>";
		}
		
		if (strlen($erro) == 0) {
			$data_final   = trim($_POST["data_final_01"]);
			$fnc            = @pg_exec($con,"SELECT fnc_formata_data('$data_final')");
			
			if (strlen ( pg_errormessage ($con) ) > 0) {
				$erro = pg_errormessage ($con) ;
			}
			
			if (strlen($erro) == 0) $aux_data_final = @pg_result ($fnc,0,0);
		}
	}
	
	if (strlen($erro) == 0) $listar = "ok";
	
	if (strlen($erro) > 0) {
		$data_inicial = trim($_POST["data_inicial_01"]);
		$data_final   = trim($_POST["data_final_01"]);
		$msg  = "<b>Foi(foram) detectado(s) o(s) seguinte(s) erro(s): </b><br>";
		$msg .= $erro;
	}
	
}

$layout_menu = "auditoria";
$title = "RELATÓRIO - Ordem de Serviço com pedido de peças com mais de 15 dias";

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
</style>


<? include "javascript_pesquisas.php" ?>


<!--=============== <FUNES> ================================!-->
<!--  XINS POP UP CALENDAR -->

<!--
<script language="javascript" src="js/cal2.js">
/*
Xin's Popup calendar script-  Xin Yang (http://www.yxscripts.com/)
Script featured on/available at http://www.dynamicdrive.com/
This notice must stay intact for use
*/
</script>

<script language="javascript" src="js/cal_conf2.js"></script>
-->

<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
<script language="javascript" src="js/assist.js"></script>


<? include "javascript_calendario.php";  // adicionado por Fabio 27-09-2007 ?>
<script type="text/javascript" charset="utf-8">
	$(function()
	{
		$('#data_inicial_01').datePicker({startDate:'01/01/2000'});
		$('#data_final_01').datePicker({startDate:'01/01/2000'});
		$("#data_inicial_01").maskedinput("99/99/9999");
		$("#data_final_01").maskedinput("99/99/9999");
	});
</script>


<!-- =========== PESQUISA POR INTERVALO ENTRE DATAS ============ -->
<FORM name="frm_pesquisa" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">

<?
if (strlen($msg) > 0){
?>
<table width="730" border="0" cellpadding="2" cellspacing="2" align='center'>
<tr>
	<td align="center" class='error'>
			<? echo $msg ?>
			
	</td>
</tr>
</table>

<br>
<?
}
?>

<br>

<TABLE width="400" align="center" border="1" cellspacing="0" cellpadding="2">
  <TR>
	<TD colspan="4" class="menu_top"><div align="center"><b>Relatório de Ordem de Serviço com pedido de peças com mais de 15 dias</b></div></TD>
  </TR>

  <TR>
    <TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line" colspan='2'><center>Este relatório considera a data de abertura da OS.</center></TD>
    <TD class="table_line" style="width: 10px">&nbsp;</TD>
  </TR>

  <TR>
    <TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line">Data Inicial</TD>
    <TD class="table_line">Data Final</TD>
    <TD class="table_line" style="width: 10px">&nbsp;</TD>
  </TR>
  <TR>
    <TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line" style="width: 185px">
		<center>
		<INPUT size="12" maxlength="10" TYPE="text" NAME="data_inicial_01" id="data_inicial_01" value="<? if (strlen($data_inicial) > 0) echo $data_inicial; else echo "dd/mm/aaaa"; ?>" onClick="this.value=''">
		<!--
		&nbsp;<IMG src="imagens_admin/btn_lupa.gif" align='absmiddle' onclick="javascript:showCal('dataPesquisaInicial_01')" style="cursor:pointer" alt="Clique aqui para abrir o calendrio">
		-->
		</center>
	</TD>
	<TD class="table_line" style="width: 185px"><center><INPUT size="12" maxlength="10" TYPE="text" NAME="data_final_01" id="data_final_01" value="<? if (strlen($data_final) > 0) echo $data_final; else echo "dd/mm/aaaa"; ?>" onClick="this.value=''">

	<!--
	&nbsp;<IMG src="imagens_admin/btn_lupa.gif" align='absmiddle' onclick="javascript:showCal('dataPesquisaFinal_01')" style="cursor:pointer" alt="Clique aqui para abrir o calendrio">
	-->
	</center></TD>
    <TD class="table_line" style="width: 10px">&nbsp;</TD>
  </TR>
  <tr>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
	<td class="table_line">Posto</td>
	<td class="table_line" colspan=2>Nome do Posto</td>
  </tr>
  <tr>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
	<td class="table_line">
		<input type="text" name="codigo_posto" size="8" value="<? echo $codigo_posto ?>" class="frm">
		<img border="0" src="imagens_admin/btn_lupa.gif" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto, document.frm_pesquisa.posto_nome, 'codigo')">
	</td>
	<td class="table_line" colspan=2 width='250'>
		<input type="text" name="posto_nome" size="30" value="<?echo $posto_nome?>" class="frm">
		<img border="0" src="imagens_admin/btn_lupa.gif" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto, document.frm_pesquisa.posto_nome, 'nome')">
	</td>
  </tr>
  <TR>
    <input type='hidden' name='btn_finalizar' value='0'>
    <TD colspan="4" class="table_line" style="text-align: center;"><IMG src="imagens_admin/btn_pesquisar_400.gif" onclick="javascript: if ( document.frm_pesquisa.btn_finalizar.value == '0' ) { document.frm_pesquisa.btn_finalizar.value='1'; document.frm_pesquisa.submit() ; } else { alert ('Aguarde submissão da OS...'); }" style="cursor:pointer " alt='Clique AQUI para pesquisar'></TD>
  </TR>
</TABLE>

</FORM>

<!-- =========== AQUI TERMINA O FORMULRIO FRM_PESQUISA =========== -->


<?
flush();
if ((strlen($aux_data_inicial)>0) AND (strlen($aux_data_final)>0)){

	
	$codigo_posto = $_POST['codigo_posto'];
	$posto_nome   = $_POST['posto_nome'];

	
	$cond_1 = " 1=1 ";
	if(strlen($codigo_posto)>0){
		$sql = "SELECT posto from tbl_posto_fabrica where codigo_posto='$codigo_posto' and fabrica = $login_fabrica";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			$posto = pg_result($res,0,posto);
			$cond_1 = "tbl_os.posto = $posto ";
		}
	}
		

		$sql="SELECT DISTINCT tbl_os.os													,
					tbl_os.sua_os															,
					tbl_posto.nome	as posto_nome											,
					tbl_posto.estado														,
					tbl_posto_fabrica.codigo_posto											,
					to_char(tbl_os.data_abertura,'DD/MM/YYYY')         AS abertura			,
					to_char(tbl_pedido.data,'DD/MM/YYYY')              AS data_pedido		,
					tbl_pedido.pedido														,
					date(tbl_pedido.data)-date(tbl_os.data_abertura)            As dias					
			FROM tbl_os
			JOIN tbl_os_produto USING(os)
			JOIN tbl_posto using(posto)
			LEFT JOIN tbl_os_item USING(os_produto)
			LEFT JOIN tbl_pedido on tbl_pedido.pedido=tbl_os_item.pedido and 
			          tbl_pedido.fabrica = tbl_os.fabrica and tbl_pedido.status_pedido <> 14
			JOIN tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_posto.posto AND
	                     tbl_posto_fabrica.fabrica=tbl_os.fabrica  
			WHERE (tbl_os.data_abertura BETWEEN '$aux_data_inicial' AND '$aux_data_final')   
			AND tbl_os.fabrica=$login_fabrica
			AND $cond_1
			AND (date(tbl_pedido.data)-date(tbl_os.data_abertura)) > 15 ORDER BY tbl_os.os, tbl_posto.nome";
		


//echo nl2br($sql);

	$res = @pg_exec($con, $sql);
	$qtde = pg_numrows($res);
	if(pg_numrows($res)>0){
	echo "<BR><BR><center><font size='1'>Foram encontradas $qtde OS.</font></center><BR>";
	echo "<BR><center><font size='2'><a href='relatorio_os_peca_lenoxx_xls.php?gera_excel=sim&data_inicial=$aux_data_inicial&data_final=$aux_data_final&posto=$posto' target=_blank>Clique aqui para fazer download no formato XLS</font></a></center><BR>";

		echo "<table width='700' border='0' bgcolor='#485989' align='center' cellpadding='3' cellspacing='1' style='font-family: verdana; font-size: 12px'>";
		echo "<tr height='25'>";
		echo "<td><font color='#FFFFFF'><B>OS</B></font></td>";
		echo "<td><font color='#FFFFFF'><B>Posto</B></font></td>";
		echo "<td><font color='#FFFFFF'><B>Data Abertura</B></font></td>";
		echo "<td><font color='#FFFFFF'><B>Data Pedido</B></font></td>";
		echo "<td><font color='#FFFFFF'><B>Pedido</B></font></td>";
		echo "<td><font color='#FFFFFF'><B>UF</B></font></td>";
		echo "</tr>";
		for ($i=0; $i<pg_numrows($res); $i++){
			$os								= trim(pg_result($res,$i,os));
			$sua_os							= trim(pg_result($res,$i,sua_os));
			$abertura 						= trim(pg_result($res,$i,abertura));
			$data_pedido 					= trim(pg_result($res,$i,data_pedido));
			$pedido     					= trim(pg_result($res,$i,pedido));
			$posto_codigo 					= trim(pg_result($res,$i,codigo_posto));
			$posto_nome 					= trim(pg_result($res,$i,posto_nome));
			$estado							= trim(pg_result($res,$i,estado));
			$cor = ($y % 2 == 0) ? "#FFFFFF": '#f4f7fb';
			echo "<tr bgcolor='$cor'>";
			echo "<td align='left'><a href='os_press.php?os=$os' target='blank'><font size='1'>$sua_os</font></A></td>";
			echo "<td align='left'>$posto_codigo-$posto_nome</td>";
			echo "<td><font size='1'>$abertura</font></td>";
			echo "<td><font size='1'>$data_pedido</font></td>";
			echo "<td><font size='1'>$pedido</font></td>";
			echo "<td><font size='1'>$estado</font></td>";
			echo "</tr>";
		}
			
		echo "</table>";
	}else{
	echo "<center>Nenhuma Ordem de Serviço encontrada.</center>";
	}
}
?>
<? include "rodape.php"; ?>
 