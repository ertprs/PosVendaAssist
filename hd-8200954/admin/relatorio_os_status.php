<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'funcoes.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';

if ($btn_finalizar == 1) {
		$os     = $_POST["os"];
		$status = $_POST["status"];

		if(!empty($os) && !is_numeric($os)) //hd#292017
			$erro = 'O campo OS deve conter apenas números';

		if ((strlen($_POST["data_inicial_01"]) == 0 OR $_POST["data_inicial_01"]=="dd/mm/aaaa") AND strlen($os)==0) {
			$erro = 'Data Inválida';
		}else if (strlen($erro) == 0 AND !empty($_POST["data_inicial_01"])) {
			$data_inicial   = trim($_POST["data_inicial_01"]);

			list($d, $m, $y) = explode("/", $data_inicial);
			if(!checkdate($m,$d,$y)) 
				$erro = "Data Inválida";
			else {

				$fnc = @pg_exec($con,"SELECT fnc_formata_data('$data_inicial')");

				if (strlen ( pg_errormessage ($con) ) > 0) {
					$erro = pg_errormessage ($con) ;
				}

				if (strlen($erro) == 0) $aux_data_inicial = @pg_result ($fnc,0,0);
			}
		}
	
	
	if (strlen($erro) == 0) {
		if ((strlen($_POST["data_final_01"]) == 0 OR $_POST["data_final_01"]=="dd/mm/aaaa")  AND strlen($os)==0) {
			$erro = 'Data Inválida';
		}else if (strlen($erro) == 0 AND !empty($_POST["data_final_01"])) {
			$data_final   = trim($_POST["data_final_01"]);

			list($d, $m, $y) = explode("/", $data_final);
			if(!checkdate($m,$d,$y)) 
				$erro = "Data Inválida";
			else {
				$fnc            = @pg_exec($con,"SELECT fnc_formata_data('$data_final')");

				if (strlen ( pg_errormessage ($con) ) > 0) {
					$erro = pg_errormessage ($con) ;
				}
				
				if (strlen($erro) == 0) $aux_data_final = @pg_result ($fnc,0,0);
			}
		}
	}

	if (strlen($erro) == 0) {
		if($aux_data_inicial > $aux_data_final)
			$erro = 'Data Inválida';
	}
	
	if (strlen($erro) == 0){
		$codigo_posto_off = trim($_POST["codigo_posto_off"]);
		$posto_nome_off   = trim($_POST["posto_nome_off"]);

		$sql = "SELECT posto FROM tbl_posto_fabrica WHERE codigo_posto = '$codigo_posto_off' AND fabrica = $login_fabrica";
		$res  = @pg_exec($con, $sql);
		$erro = pg_errormessage ($con);
		if(pg_numrows($res)>0) $posto = pg_result($res, 0, posto);
	}

	if (strlen($erro) > 0) {
		$os               = trim($_POST["os"]);
		$data_inicial     = trim($_POST["data_inicial_01"]);
		$data_final       = trim($_POST["data_final_01"]);
		$codigo_posto_off = trim($_POST["codigo_posto_off"]);
		$posto_nome_off   = trim($_POST["posto_nome_off"]);
		$status           = trim($_POST["status"]);

		$msg = $erro;
	}
	
}



$layout_menu = "gerencia";
$title = "RELATÓRIO DE OS";

include "cabecalho.php";

?>


<style type="text/css">

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}

.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
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
table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}
.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}

</style>


<? include "javascript_pesquisas.php"; ?>

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

<? include "javascript_calendario.php";  // adicionado por Fabio 27-09-2007 ?>
<script type="text/javascript" src="js/jquery.alphanumeric.js"></script>
<script type="text/javascript" charset="utf-8">
	$(function()
	{
		$('#data_inicial_01').datePicker({startDate:'01/01/2000'});
		$('#data_final_01').datePicker({startDate:'01/01/2000'});
		$("#data_inicial_01").maskedinput("99/99/9999");
		$("#data_final_01").maskedinput("99/99/9999");
		$("input[name=os]").numeric(); <!-- hd#292017 -->
	});
</script>



<!-- =========== PESQUISA POR INTERVALO ENTRE DATAS ============ -->
<FORM name="frm_pesquisa" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">

<?
if (strlen($msg) > 0){
?>
<table width="700" border="0" cellpadding="0" cellspacing="1" align='center'>
<tr>
	<td align="center" class='error'>
			<? echo $msg ?>
			
	</td>
</tr>
</table>


<?
}
?>

<TABLE width="700" align="center" border="0" cellspacing="1" cellpadding="0" class="formulario">
  <TR>
	<TD colspan="4" class="titulo_tabela"><b>Parâmetros de Pesquisa</b></TD>
  </TR>
  <TR>
    <TD style="width: 110px">&nbsp;</TD>
	<TD width="50px">
		OS<br />
		<INPUT TYPE="text" size="12" maxlength="12" NAME="os" value="<? echo $os; ?>" class="frm">
	</TD>
    <TD style="width: 10px">&nbsp;</TD>
  </TR>
  <TR>
    <TD style="width: 10px">&nbsp;</TD>
	<TD>
		Data Inicial<br />
		<input type="text" name="data_inicial_01" id="data_inicial_01" size="12" maxlength="10" class='frm' value="<? if (strlen($data_inicial) > 0) echo $data_inicial; ?>">
	</TD>
    <TD>
		Data Final<br />
		<input type="text" name="data_final_01" id="data_final_01" size="12" maxlength="10" class='frm' value="<? if (strlen($data_final) > 0) echo $data_final;?>" >
	</TD>
  </TR>
	<tr>
	    <TD style="width: 10px">&nbsp;</TD>
		<td>
			Cod. Posto<br />
			<input type="text" name="codigo_posto_off" id="codigo_posto_off" size="12" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto_off, document.frm_pesquisa.posto_nome_off, 'codigo');" <? } ?> value="<? echo $codigo_posto_off ?>" class="frm">
			<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto_off, document.frm_pesquisa.posto_nome_off, 'codigo')">
		</td>
		<td>
			Nome Posto<br />
			<input type="text" name="posto_nome_off" id="posto_nome_off" size="30" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto_off, document.frm_pesquisa.posto_nome_off, 'nome');" <? } ?> value="<?echo $posto_nome_off ?>" class="frm">
			<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto_off, document.frm_pesquisa.posto_nome_off, 'nome')" />
		</td>
	</tr>
	<TR>
    <TD style="width: 10px">&nbsp;</TD>
	<TD colspan="2">
		<fieldset style="width:380px;">
			<legend>Status</legend>
			<INPUT TYPE="radio" NAME="status" value="RECUSADA" <? if($status=="RECUSADA") echo "CHECKED";?> CHECKED>
			OS RECUSADAS pela Auditoria

			<INPUT TYPE="radio" NAME="status" value="ACUMULADA" <? if($status=="ACUMULADA") echo "CHECKED";?>>
			OS ACUMULADAS pela Auditoria
		</fieldset>
	</TD>
  </TR>


	 <TR>
    <input type='hidden' name='btn_finalizar' value='0'>
    <TD colspan="3" style="text-align: center;">
		<input type="button" onclick="javascript: if ( document.frm_pesquisa.btn_finalizar.value == '0' ) { document.frm_pesquisa.btn_finalizar.value='1'; document.frm_pesquisa.submit() ; } else { alert ('Aguarde submissão da OS...'); }"
		style="background:url(imagens/btn_pesquisar_400.gif); width:400px; height:22px;cursor:pointer; margin:5px 0 5px;" />
		
	</TD>
  </TR>
</TABLE>

</FORM>
<br />
<!-- =========== AQUI TERMINA O FORMULRIO FRM_PESQUISA =========== -->


<?
flush();

if ($btn_finalizar == 1 AND strlen($msg)==0){//HD 31959

$sql="SELECT DISTINCT tbl_os.os                                                         ,
					tbl_os.sua_os                                                       ,
					tbl_posto_fabrica.codigo_posto as posto_codigo                      ,
					tbl_posto.nome	as posto_nome                                       ,
					to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS abertura              ,
					tbl_produto.descricao as produto_descricao                          ,
					tbl_os_status.status_os                                             ,
					tbl_os_status.observacao                                            
			FROM tbl_os 
			JOIN tbl_os_status USING(os)
			JOIN tbl_produto on tbl_produto.produto=tbl_os.produto
			JOIN tbl_posto on tbl_os.posto = tbl_posto.posto 
			JOIN tbl_posto_fabrica on tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE tbl_os.fabrica=$login_fabrica 
			AND tbl_posto.pais = 'BR' ";
			if ((strlen($aux_data_inicial)>0) AND (strlen($aux_data_final)>0))
			$sql .= " AND (tbl_os.data_abertura BETWEEN '$aux_data_inicial' AND '$aux_data_final') ";
			if(strlen($os)>0)        $sql .= " AND tbl_os.os    = $os";
			if(strlen($posto)>0)     $sql .= " AND tbl_os.posto = $posto";
			if($status=='ACUMULADA') $sql .= " AND tbl_os_status.status_os = 14";
			if($status=='RECUSADA')  $sql .= " AND tbl_os_status.status_os = 13";
			$sql .= " ORDER BY abertura, posto_codigo DESC";
	#echo nl2br($sql);

	$res  = @pg_exec($con, $sql);
	$qtde = pg_numrows($res);

	if(pg_numrows($res)>0){
		echo "<table width='100%' align='center' cellpadding='0' cellspacing='1' style='font-family: verdana; font-size: 12px' class='tabela'>";
		echo "<tr height='25' class='titulo_coluna'>";
		echo "<td>OS</td>";
		echo "<td>Abertura</td>";
		echo "<td>Produto</td>";
		echo "<td>Posto</td>";
		echo "<td>Status</td>";
		echo "<td>Observação</td>";
		echo "</tr>";
		for ($i=0; $i<pg_numrows($res); $i++){
			$os                 = trim(pg_result($res,$i,os));
			$sua_os             = trim(pg_result($res,$i,sua_os));
			$abertura           = trim(pg_result($res,$i,abertura));
			$posto_codigo       = trim(pg_result($res,$i,posto_codigo));
			$posto_nome         = trim(pg_result($res,$i,posto_nome));
			$produto_descricao  = trim(pg_result($res,$i,produto_descricao));
			$status_os          = trim(pg_result($res,$i,status_os));
			$observacao         = trim(pg_result($res,$i,observacao));

			$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

			$sqlS = "SELECT descricao FROM tbl_status_os WHERE status_os = $status_os";
			$resS = pg_exec($con, $sqlS);
			if( pg_numrows($resS) > 0 ) $status_descricao = pg_result($resS, 0, descricao);
		
			echo "<tr bgcolor='$cor'>";
			echo "<td align='left'><a href='os_press.php?os=$os' target='blank'><font size='1'>$sua_os</font></A></td>";
			echo "<td><font size='1'>$abertura</font></td>";
			echo "<td align='left' ><font size='1'>$produto_descricao</font></td>";
			echo "<td align='left' ><font size='1'>$posto_codigo - $posto_nome</font></td>";
			echo "<td align='left' ><font size='1'>$status_descricao</font></td>";
			echo "<td align='left' ><font size='1'>$observacao</font></td>";
			echo "</tr>";
		}
		echo "</table>";
	} else {
		echo "<center>Nenhuma Ordem de Serviço Encontrada.</center>";
	}
}
echo "<BR>";
?>



		<? include "rodape.php"; ?>
