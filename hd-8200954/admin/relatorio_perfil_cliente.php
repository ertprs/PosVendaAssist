<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'funcoes.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';

if ($btn_finalizar == 1) {
		if (strlen($_POST["data_inicial_01"]) == 0 OR $_POST["data_inicial_01"]=="dd/mm/aaaa") {

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
	
	
	if (strlen($erro) == 0) {
		if (strlen($_POST["data_final_01"]) == 0 OR $_POST["data_final_01"]=="dd/mm/aaaa") {
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
	
	if (strlen($erro) == 0){
		$codigo_posto_off = trim($_POST["codigo_posto_off"]);
		$posto_nome_off   = trim($_POST["posto_nome_off"]);

		$sql = "SELECT posto FROM tbl_posto_fabrica WHERE codigo_posto = '$codigo_posto_off' AND fabrica = $login_fabrica";
		$res  = @pg_exec($con, $sql);
		$erro = pg_errormessage ($con);
		if(pg_numrows($res)>0) $posto = pg_result($res, 0, posto);
	}

	if (strlen($erro) > 0) {
		$data_inicial = trim($_POST["data_inicial_01"]);
		$data_final   = trim($_POST["data_final_01"]);
		$codigo_posto_off = trim($_POST["codigo_posto_off"]);
		$posto_nome_off   = trim($_POST["posto_nome_off"]);
		$msg  = "<b>Foi(foram) detectado(s) o(s) seguinte(s) erro(s): </b><br>";
		$msg .= $erro;
	}
	
}



$layout_menu = "gerencia";
$title = "RELATÓRIO PERFIL DO CLIENTE";

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

<TABLE width="500" align="center" border="0" cellspacing="0" cellpadding="2">
  <TR>
	<TD colspan="4" class="menu_top"><div align="center"><b>Relatório Perfil do Cliente</b></div></TD>
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
	<tr class="table_line">
	    <TD class="table_line" style="width: 10px">&nbsp;</TD>
		<td>Código</td>
		<td>Razão Social</td>
	    <TD class="table_line" style="width: 10px">&nbsp;</TD>
	</tr>
	<tr class="table_line">
	    <TD class="table_line" style="width: 10px">&nbsp;</TD>
		<td nowrap><input type="text" name="codigo_posto_off" id="codigo_posto_off" size="8" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto_off, document.frm_pesquisa.posto_nome_off, 'codigo');" <? } ?> value="<? echo $codigo_posto_off ?>" class="frm">
				<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto_off, document.frm_pesquisa.posto_nome_off, 'codigo')">
		</td>
		<td nowrap>
				<input type="text" name="posto_nome_off" id="posto_nome_off" size="30" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto_off, document.frm_pesquisa.posto_nome_off, 'nome');" <? } ?> value="<?echo $posto_nome_off ?>" class="frm">
				<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto_off, document.frm_pesquisa.posto_nome_off, 'nome')">
		</TD>
		<TD class="table_line" style="width: 10px">&nbsp;</TD>
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

$sql="SELECT tbl_os.os															    ,
					tbl_os.sua_os														,
					tbl_posto.nome	as posto_nome										,
					to_char(tbl_os.data_abertura,'DD/MM/YYYY')         AS abertura		,
					tbl_produto.descricao as produto_descricao                          ,
					tbl_os_extra.obs_adicionais
			FROM tbl_os 
			JOIN tbl_os_extra USING(os)
			JOIN tbl_produto on tbl_produto.produto=tbl_os.produto
			JOIN tbl_posto on tbl_os.posto = tbl_posto.posto
			WHERE (tbl_os.data_abertura BETWEEN '$aux_data_inicial' AND '$aux_data_final')
			AND tbl_os.fabrica=$login_fabrica ";
			if(strlen($posto)>0) $sql .= " AND tbl_os.posto = $posto ";
			$sql .= " AND tbl_os_extra.obs_adicionais IS NOT NULL";
	#echo nl2br($sql);

	$res  = @pg_exec($con, $sql);
	$qtde = pg_numrows($res);

	if(pg_numrows($res)>0){
		echo "<table width='700' border='0' bgcolor='#485989' align='center' cellpadding='3' cellspacing='1' style='font-family: verdana; font-size: 12px'>";
		echo "<tr height='25'>";
		echo "<td><font color='#FFFFFF'><B>OS</B></font></td>";
		echo "<td><font color='#FFFFFF'><B>Abertura</B></font></td>";
		echo "<td><font color='#FFFFFF'><B>Produto</B></font></td>";
		echo "<td><font color='#FFFFFF'><B>Fogão</B></font></td>";
		echo "<td><font color='#FFFFFF'><B>Refrigerador</B></font></td>";
		echo "<td><font color='#FFFFFF'><B>Bebeduro</B></font></td>";
		echo "<td><font color='#FFFFFF'><B>Microondas</B></font></td>";
		echo "<td><font color='#FFFFFF'><B>Lavadoura</B></font></td>";
		echo "</tr>";
		for ($i=0; $i<pg_numrows($res); $i++){
			$os					= trim(pg_result($res,$i,os));
			$sua_os				= trim(pg_result($res,$i,sua_os));
			$abertura 			= trim(pg_result($res,$i,abertura));
			$posto_nome			= trim(pg_result($res,$i,posto_nome));
			$produto_descricao	= trim(pg_result($res,$i,produto_descricao));
			$obs_adicionais		= trim(pg_result($res,$i,obs_adicionais));

			$marcas = explode(";", $obs_adicionais);

			$fogao        = "";
			$refrigerador = "";
			$bebedouro    = "";
			$microondas   = "";
			$lavadoura    = "";

			if(strlen($marcas[0])>0 AND strlen($marcas[1])>0){
				$fogao        = $marcas[0]. ' - ' . $marcas[1];
			}

			if(strlen($marcas[2])>0 AND strlen($marcas[3])>0){
				$refrigerador = $marcas[2]. ' - ' . $marcas[3];
			}

			if(strlen($marcas[4])>0 AND strlen($marcas[5])>0){
				$bebedouro    = $marcas[4]. ' - ' . $marcas[5];
			}

			if(strlen($marcas[6])>0 AND strlen($marcas[7])>0){
				$microondas   = $marcas[6]. ' - ' . $marcas[7];
			}

			if(strlen($marcas[8])>0 AND strlen($marcas[9])>0){
				$lavadoura    = $marcas[8]. ' - ' . $marcas[9];
			}

			$cor = ($i % 2 == 0) ? "#FFFFFF": '#f4f7fb';
		
			echo "<tr bgcolor='$cor'>";
			echo "<td align='left'><a href='os_press.php?os=$os' target='blank'><font size='1'>$sua_os</font></A></td>";
			echo "<td><font size='1'>$abertura</font></td>";
			echo "<td align='left' nowrap><font size='1'>$produto_descricao</font></td>";
			echo "<td align='left' nowrap><font size='1'>$fogao</font></td>";
			echo "<td align='left' nowrap><font size='1'>$refrigerador</font></td>";
			echo "<td align='left' nowrap><font size='1'>$bebedouro</font></td>";
			echo "<td align='left' nowrap><font size='1'>$microondas</font></td>";
			echo "<td align='left' nowrap><font size='1'>$lavadoura</font></td>";
			echo "</tr>";
		}
		echo "</table>";
	}else{
		echo "<center>Nenhuma Ordem de Serviço encontrada.</center>";
	}
}
echo "<BR>";
?>



		<? include "rodape.php"; ?>
