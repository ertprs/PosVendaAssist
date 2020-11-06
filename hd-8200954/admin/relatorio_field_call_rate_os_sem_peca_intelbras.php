
<?

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';

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
	
	if(strlen($_POST["linha"]) > 0) $linha = trim($_POST["linha"]);

	if (strlen($erro) == 0) $listar = "ok";
	
	if (strlen($erro) > 0) {
		$data_inicial = trim($_POST["data_inicial_01"]);
		$data_final   = trim($_POST["data_final_01"]);
		$linha        = trim($_POST["linha"]);
		$msg  = "<b>Foi(foram) detectado(s) o(s) seguinte(s) erro(s): </b><br>";
		$msg .= $erro;
	}
	
}



$layout_menu = "gerencia";
//$title = "RELAT�RIO - FIELD CALL-RATE : LINHA DE PRODUTO";
$title = "RELAT�RIO OS SEM PE�A";

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

function AbrePeca(produto,data_inicial,data_final,linha,estado){
	janela = window.open("relatorio_field_call_rate_pecas.php?produto=" + produto + "&data_inicial=" + data_inicial + "&data_final=" + data_final + "&linha=" + linha + "&estado=" + estado,"produto",'scrollbars=yes,width=750,height=280,top=0,left=0');
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
<? include "javascript_calendario.php"; ?>

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

<TABLE width="400" align="center" border="0" cellspacing="0" cellpadding="2">
  <TR>
	<TD colspan="4" class="menu_top"><div align="center"><b>Relat�rio de Ordem de Servi�o sem pe�as</b></div></TD>
  </TR>

  <TR>
    <TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line" colspan='2'><center>Este relat�rio considera a data de abertura da OS.</center></TD>
    <TD class="table_line" style="width: 10px">&nbsp;</TD>
  </TR>

  <TR>
    <TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line"><center>Data Inicial</center></TD>
    <TD class="table_line"><center>Data Final</center></TD>
    <TD class="table_line" style="width: 10px">&nbsp;</TD>
  </TR>
  <TR>
    <TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line" style="width: 185px"><center><INPUT size="12" maxlength="10" TYPE="text" NAME="data_inicial_01" id='data_inicial_01' value="<? if (strlen($data_inicial) > 0) echo $data_inicial; else echo "dd/mm/aaaa"; ?>" onClick="this.value=''">&nbsp;</center></TD>
	<TD class="table_line" style="width: 185px"><center><INPUT size="12" maxlength="10" TYPE="text" NAME="data_final_01" id='data_final_01' value="<? if (strlen($data_final) > 0) echo $data_final; else echo "dd/mm/aaaa"; ?>" onClick="this.value=''">&nbsp;</center></TD>
    <TD class="table_line" style="width: 10px">&nbsp;</TD>
  </TR>

  <TR width = '100%' align="center">
	  <TD colspan='4' CLASS='table_line' > <center>Fam�lia</center></TD>
  </TR>

  <TR width='100%' align="center">
	  <TD colspan='4' CLASS='table_line'>
		<center>
			<!-- come�a aqui -->
			<?
			$sql = "SELECT  *
					FROM    tbl_familia
					WHERE   tbl_familia.fabrica = $login_fabrica
					ORDER BY tbl_familia.descricao;";
			$res = pg_exec ($con,$sql);
			
			if (pg_numrows($res) > 0) {
				echo "<select name='linha'>\n";
				echo "<option value=''>Todas</option>\n";
				
				for ($x = 0 ; $x < pg_numrows($res) ; $x++){
					$aux_linha = trim(pg_result($res,$x,familia));
					$aux_nome  = trim(pg_result($res,$x,descricao));
					
					echo "<option value='$aux_linha'"; 
					if ($linha == $aux_linha){
						echo " SELECTED "; 
						$mostraMsgLinha = "<br> da LINHA $aux_nome";
					}
					echo ">$aux_nome</option>\n";
				}
				echo "</select>\n";
			}
			?>
		</center>
	</TD>
  </TR>
  <TR>
    <input type='hidden' name='btn_finalizar' value='0'>
    <TD colspan="4" class="table_line" style="text-align: center;"><IMG src="imagens_admin/btn_pesquisar_400.gif" onclick="javascript: if ( document.frm_pesquisa.btn_finalizar.value == '0' ) { document.frm_pesquisa.btn_finalizar.value='1'; document.frm_pesquisa.submit() ; } else { alert ('Aguarde submiss�o da OS...'); }" style="cursor:pointer " alt='Clique AQUI para pesquisar'></TD>
  </TR>
</TABLE>

</FORM>

<!-- =========== AQUI TERMINA O FORMUL�RIO FRM_PESQUISA =========== -->


<?
		
if ((strlen($aux_data_inicial)>0) AND (strlen($aux_data_final)>0)){
	if (strlen($linha)>0) $cond_linha =" AND tbl_produto.familia=$linha ";
	$sql="
		SELECT DISTINCT(tbl_os.os)
		INTO TEMP tmp_fcr_i_$login_admin
		FROM tbl_os 
		JOIN tbl_produto    ON tbl_produto.produto = tbl_os.produto 
		JOIN tbl_os_produto ON tbl_os.os           = tbl_os_produto.os 
		WHERE tbl_os.fabrica = $login_fabrica AND tbl_os.excluida IS NOT TRUE 
		AND   tbl_os.data_abertura BETWEEN '$aux_data_inicial' AND '$aux_data_final' $cond_linha ;

		SELECT  tbl_os.os,
			tbl_os.sua_os,
			tbl_posto.nome  as posto_nome,
			tbl_os.defeito_reclamado,
			tbl_defeito_reclamado.descricao as defeito_reclamado_descricao,
			tbl_os.defeito_constatado,
			tbl_defeito_constatado.descricao as defeito_constatado_descricao,
			tbl_os.solucao_os,
			tbl_servico_realizado.descricao as solucao,
			to_char(tbl_os.data_abertura,'DD/MM/YYYY')         AS abertura,
			to_char(tbl_os.data_fechamento,'DD/MM/YYYY')       AS fechamento,
			tbl_os.fabrica,
			tbl_produto.descricao as produto_descricao
		FROM tbl_os 
		JOIN tbl_produto            ON tbl_produto.produto                       = tbl_os.produto
		JOIN tbl_defeito_reclamado  ON tbl_defeito_reclamado.defeito_reclamado   = tbl_os.defeito_reclamado 
		JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_os.defeito_constatado 
		JOIN tbl_servico_realizado  ON tbl_servico_realizado.servico_realizado   = tbl_os.solucao_os 
		JOIN tbl_posto              ON tbl_os.posto                              = tbl_posto.posto
		WHERE tbl_os.data_abertura BETWEEN '$aux_data_inicial' AND '$aux_data_final'
		AND   tbl_os.fabrica = $login_fabrica  $cond_linha 
		AND   tbl_os.data_fechamento IS NOT NULL
		AND   tbl_os.excluida IS NOT TRUE
		AND   tbl_os.os              NOT IN(SELECT os FROM tmp_fcr_i_$login_admin )
		ORDER BY tbl_defeito_reclamado.descricao, tbl_defeito_constatado.descricao, tbl_servico_realizado.descricao";

//	echo nl2br($sql);
//	exit;
	
	$res = @pg_exec($con, $sql);
	$qtde = pg_numrows($res);
	if(pg_numrows($res)>0){
	echo "<BR><BR><center><font size='1'>Foram encontradas $qtde OS sem pe�a.</font></center><BR>";
		echo "<table width='700' border='0' bgcolor='#485989' align='center' cellpadding='3' cellspacing='1' style='font-family: verdana; font-size: 12px'>";
		echo "<tr height='25'>";
		echo "<td><font color='#FFFFFF'><B>OS</B></font></td>";
		//echo "<td><font color='#FFFFFF'><B>Posto</B></font></td>";
		echo "<td><font color='#FFFFFF'><B>Produto</B></font></td>";
		echo "<td><font color='#FFFFFF'><B>Abertura</B></font></td>";
		echo "<td><font color='#FFFFFF'><B>Fechamento</B></font></td>";
		echo "<td><font color='#FFFFFF'><B>Defeito Reclamado</B></font></td>";
		echo "<td><font color='#FFFFFF'><B>Defeito Constatado</B></font></td>";
		echo "<td><font color='#FFFFFF'><B>Solu��o</B></font></td>";
		echo "</tr>";
		for ($i=0; $i<pg_numrows($res); $i++){
			$os                           = trim(pg_result($res,$i,os));
			$sua_os                       = trim(pg_result($res,$i,sua_os));
			$defeito_reclamado_descricao  = trim(pg_result($res,$i,defeito_reclamado_descricao));
			$defeito_constatado_descricao = trim(pg_result($res,$i,defeito_constatado_descricao));
			$solucao                      = trim(pg_result($res,$i,solucao));
			$abertura                     = trim(pg_result($res,$i,abertura));
			$fechamento                   = trim(pg_result($res,$i,fechamento));
			$posto_nome                   = trim(pg_result($res,$i,posto_nome));
			$produto_descricao            = trim(pg_result($res,$i,produto_descricao));

			$cor = ($y % 2 == 0) ? "#FFFFFF": '#f4f7fb';
			echo "<tr bgcolor='$cor'>";
			echo "<td align='left'><a href='os_press.php?os=$os' target='blank'>$sua_os</A></td>";
			//echo "<td align='left'>$posto_nome</td>";
			echo "<td align='left'>$produto_descricao</td>";
			echo "<td>$abertura</td>";
			echo "<td>$fechamento</td>";
			echo "<td align='left'>$defeito_reclamado_descricao</td>";
			echo "<td align='left'>$defeito_constatado_descricao</td>";
			echo "<td align='left'>$solucao</td>";
			echo "</tr>";
		}
		echo "</table>";
	}else{
	echo "<center>Nenhuma Ordem de Servi�o encontrada.</center>";
	}
}

?>



		<? include "rodape.php"; ?>
 