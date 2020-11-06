<?

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';

if ($login_fabrica == 14){
	header("Location: relatorio_field_call_rate_produto_familia.php");
	exit;
}

// Criterio padrão
$_POST["criterio"] = "data_digitacao";
//////////////////

if ($btn_finalizar == 1) {
	if (strlen($erro) == 0) {
		if (strlen($_POST["data_inicial_01"]) == 0) {
			$erro .= "Favor informar la fecha inicial de busca<br>";
		}
		
		if (strlen($erro) == 0) {
			$data_inicial   = trim($_POST["data_inicial_01"]);
			$fnc            = @pg_exec($con,"SELECT fnc_formata_data('$data_inicial')");
			
			if (strlen ( pg_errormessage ($con) ) > 0) {
				$erro = pg_errormessage ($con) ;
			}
			
			//if (strlen($erro) == 0) $aux_data_inicial = @pg_result ($fnc,0,0) ." 00:00:00";
			if (strlen($erro) == 0) $aux_data_inicial = @pg_result ($fnc,0,0);
		}
	}
	
	if (strlen($erro) == 0) {
		if (strlen($_POST["data_final_01"]) == 0) {
			$erro .= "Favor informar la fecha final de busca<br>";
		}
		
		if (strlen($erro) == 0) {
			$data_final   = trim($_POST["data_final_01"]);
			$fnc            = @pg_exec($con,"SELECT fnc_formata_data('$data_final')");
			
			if (strlen ( pg_errormessage ($con) ) > 0) {
				$erro = pg_errormessage ($con) ;
			}
			
			//if (strlen($erro) == 0) $aux_data_final = @pg_result ($fnc,0,0) ." 23:59:59";
			if (strlen($erro) == 0) $aux_data_final = @pg_result ($fnc,0,0);
		}
	}
	
	if(strlen($_POST["linha"]) > 0) $linha = trim($_POST["linha"]);

	if(strlen($_POST["estado"]) > 0){
		$estado = trim($_POST["estado"]);
		$mostraMsgEstado = "<br>no Provincia $estado";
	}

	/*if (strlen($erro) == 0) {
		if(strlen($_POST["criterio"]) == 0) {
			$erro .= "Favor informar o critério (Abertura ou Lançamento de OS) para pesquisa<br>";
		}
		
		if (strlen($erro) == 0) {
			$aux_criterio = trim($_POST["criterio"]);
		}
	}*/
	
	if (strlen($erro) == 0) $listar = "ok";
	
	if (strlen($erro) > 0) {
		$data_inicial = trim($_POST["data_inicial_01"]);
		$data_final   = trim($_POST["data_final_01"]);
		$linha        = trim($_POST["linha"]);
		$estado       = trim($_POST["estado"]);
		$criterio     = trim($_POST["criterio"]);
		
		$msg  = "<b>Fue(fueran) detectado(s) lo(s) seguiente(s) error(es): </b><br>";
		$msg .= $erro;
	}
}

$layout_menu = "gerencia";
$title = "REPORTES - FIELD CALL-RATE : LÍNEA DE HERRAMIENTAS";

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
	janela = window.open("relatorio_field_call_rate_pecas2.php?produto=" + produto + "&data_inicial=" + data_inicial + "&data_final=" + data_final + "&linha=" + linha + "&estado=" + estado,"produto",'resizable=1,scrollbars=yes,width=750,height=550,top=0,left=0');
	janela.focus();
}

</script>

<script type="text/javascript" src="../admin/js/jquery-latest.pack.js"></script>
<link rel="stylesheet" type="text/css" href="../admin/js/datePicker.v1.css" title="default" media="screen" />
<script type="text/javascript" src="../admin/js/datePicker.v1.js"></script>
<script type="text/javascript" src="../admin/js/jquery.maskedinput.js"></script>

<script type="text/javascript" charset="utf-8">
	$(function()
	{
		$('#data_inicial_01').datePicker({startDate:'01/01/2000'});
		$('#data_final_01').datePicker({startDate:'01/01/2000'});
		$("#data_inicial_01").maskedinput("99/99/9999");
		$("#data_final_01").maskedinput("99/99/9999");
	});
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

<style type="text/css">
<!--
.titPreto14 {
	color: #000000;
	text-align: center;
	font:12px Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

.titDatas12 {
	color: #000000;
	text-align: center;
	font:12px Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

.titChamada10{
	background-color: #596D9B;
	color: #ffffff;
	text-align: center;
	font:11px Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

.conteudo10 {
	color: #000000;
	font:12px Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

.bgTRConteudo1{
	background-color: #FEFEFF;
}

.bgTRConteudo2{
	background-color: #F9FCFF;
}

-->
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

<DIV ID="container" style="width: 100%; ">

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
	<TD colspan="4" class="menu_top"><div align="center"><b>Consulta</b></div></TD>
  </TR>

  <TR>
    <TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line" colspan='2'><center>Este reporte toma en cuenta la fecha de creación del extracto aprobado.</center></TD>
    <TD class="table_line" style="width: 10px">&nbsp;</TD>
  </TR>

  <TR>
    <TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line"><center>Fecha Inicial</center></TD>
    <TD class="table_line"><center>Fecha Final</center></TD>
    <TD class="table_line" style="width: 10px">&nbsp;</TD>
  </TR>
  <TR>
    <TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line" ><center><INPUT size="12" maxlength="10" TYPE="text" NAME="data_inicial_01" value="<? if (strlen($data_inicial) > 0) echo $data_inicial; else echo "dd/mm/aaaa"; ?>" onClick="this.value=''" id='data_inicial_01' ></center></TD>
	<TD class="table_line" ><center><INPUT size="12" maxlength="10" TYPE="text" NAME="data_final_01" value="<? if (strlen($data_final) > 0) echo $data_final; else echo "dd/mm/aaaa"; ?>" onClick="this.value=''" id='data_final_01'></center></TD>
    <TD class="table_line" style="width: 10px">&nbsp;</TD>
  </TR>

  <TR width = '100%' align="center">
	  <TD colspan='4' CLASS='table_line' > <center>Línea</center></TD>
  </TR>

  <TR width='100%' align="center">
	  <TD colspan='4' CLASS='table_line'>
		<center>
			<!-- começa aqui -->
			<?
			$sql = "SELECT  *
					FROM    tbl_linha
					WHERE   tbl_linha.fabrica = $login_fabrica
					ORDER BY tbl_linha.nome;";
			$res = pg_exec ($con,$sql);
			
			if (pg_numrows($res) > 0) {
				echo "<select name='linha'>\n";
				echo "<option value=''>ELIJA</option>\n";
				
				for ($x = 0 ; $x < pg_numrows($res) ; $x++){
					$aux_linha = trim(pg_result($res,$x,linha));
					$aux_nome  = trim(pg_result($res,$x,nome));
					
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
    <TD colspan="4" class="table_line" style="text-align: center;"><IMG src="imagens_admin/btn_pesquisar_400.gif" onclick="javascript: if ( document.frm_pesquisa.btn_finalizar.value == '0' ) { document.frm_pesquisa.btn_finalizar.value='1'; document.frm_pesquisa.submit() ; } else { alert ('Aguarde submisión...'); }" style="cursor:pointer "></TD>
  </TR>
</TABLE>

</FORM>

<!-- =========== AQUI TERMINA O FORMULÁRIO FRM_PESQUISA =========== -->
</DIV>

<?
if ($listar == "ok") {

	$cond_1 = "1=1";
	$cond_2 = "1=1";
	$cond_3 = "1=1";
	if (strlen ($linha)  > 0) $cond_1 = " tbl_produto.linha = $linha ";
	if (strlen ($estado) > 0) $cond_2 = " tbl_posto.estado  = '$estado' ";
	if (strlen ($posto)  > 0) $cond_3 = " tbl_posto.posto   = $posto ";


	$sql = "SELECT tbl_produto.produto, tbl_produto.ativo, tbl_produto.referencia,  tbl_produto.descricao AS produto_descricao, tbl_produto_idioma.descricao, fcr1.qtde AS ocorrencia, tbl_produto.familia, tbl_produto.linha
			FROM tbl_produto
			LEFT JOIN tbl_produto_idioma using(produto)
			JOIN (
					SELECT tbl_os.produto, COUNT(*) AS qtde
					FROM tbl_os
					JOIN (
							SELECT tbl_os_extra.os ,
								(SELECT status_os FROM tbl_os_status WHERE tbl_os_status.os = tbl_os_extra.os ORDER BY data DESC LIMIT 1) AS status
							FROM tbl_os_extra
							JOIN tbl_extrato       USING (extrato)
							JOIN tbl_extrato_extra USING (extrato)
							JOIN tbl_posto         USING (posto)
							WHERE tbl_extrato.fabrica = $login_fabrica
							AND   tbl_posto.pais      = '$login_pais'
							AND   tbl_extrato.data_geracao BETWEEN '$aux_data_inicial' AND '$aux_data_final'
						) fcr ON tbl_os.os = fcr.os
					JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
					WHERE (fcr.status NOT IN (13,15) OR fcr.status IS NULL)
					AND tbl_os.excluida IS NOT TRUE
					AND tbl_posto.pais = '$login_pais'
					AND $cond_2
					AND $cond_3
					GROUP BY tbl_os.produto
				) fcr1 ON tbl_produto.produto = fcr1.produto
			WHERE $cond_1
			ORDER BY fcr1.qtde DESC " ;

	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		echo "<br>";
		
		echo "<b>Resultado de busca entre los días $data_inicial y $data_final $mostraMsgLinha $mostraMsgEstado</b>";
		
		echo "<br><br>";
		echo "<FONT SIZE=\"2\">(*) Repuestos inactivos.</FONT>";
		echo"<TABLE width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
		echo"	<TR>";
		echo"		<TD width='30%' height='15' class='table_line'><b>Referencia</b></TD>";
		echo"		<TD width='55%' height='15' class='table_line'><b>Herramienta</b></TD>";
		echo"		<TD width='10%' height='15' class='table_line'><b>Ocurrencia</b></TD>";
		echo"		<TD width='05%' height='15' class='table_line'><b>%</b></TD>";
		echo"	</TR>";
		
		for ($x = 0; $x < pg_numrows($res); $x++) {
			$total_ocorrencia = $total_ocorrencia + pg_result($res,$x,ocorrencia);
		}
		
		for ($i=0; $i<pg_numrows($res); $i++){
			$referencia        = trim(pg_result($res,$i,referencia));
			$produto_descricao = trim(pg_result($res,$i,produto_descricao));
			$ativo             = trim(pg_result($res,$i,ativo));
			$descricao         = trim(pg_result($res,$i,descricao));
			$produto           = trim(pg_result($res,$i,produto));
			if (strlen($linha) > 0) $linha = trim(pg_result($res,$i,linha));
			$ocorrencia = trim(pg_result($res,$i,ocorrencia));

			if (strlen($descricao)==0){
				$descricao = $produto_descricao;
			}
			
#			if (strlen($estado) > 0)   $estado      = trim(pg_result($res,$i,estado));
			if ($total_ocorrencia > 0) $porcentagem = (($ocorrencia * 100) / $total_ocorrencia);
			
			$cor = '2';
			if ($i % 2 == 0) $cor = '1';
// Todo produto que for inativo estará com um (*) na frente para indicar se está Inativo ou Ativo.
				if($ativo == 'f'){$ativo = "<B>*</B>"; }else{$ativo= '';} 
			echo "<TR class='bgTRConteudo$cor'>";
			echo "<TD class='conteudo10' align='left' nowrap>$ativo<a href='javascript:AbrePeca(\"$produto\",\"$aux_data_inicial\",\"$aux_data_final\",\"$linha\",\"$estado\");'>$referencia</a></TD>";
			echo "<TD class='conteudo10' align='left' nowrap>$descricao</TD>";
			echo "<TD class='conteudo10' align='center' nowrap>$ocorrencia</TD>";
			echo "<TD class='conteudo10' align='right' nowrap>". number_format($porcentagem,2,",",".") ." %</TD>";
			echo "</TR>";
		}
		echo"</TABLE>";
		
		echo "<br>";
		echo "<hr width='600'>";
		echo "<br>";
		
		// monta URL
		$data_inicial = trim($_POST["data_inicial_01"]);
		$data_final   = trim($_POST["data_final_01"]);
		$linha        = trim($_POST["linha"]);
		$estado       = trim($_POST["estado"]);
		$criterio     = trim($_POST["criterio"]);
		
		echo"<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
		echo"<tr>";
		echo "<td align='center'><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Haga un click para hacer </font><a href='relatorio_field_call_rate_produto-xls.php?data_inicial=$data_inicial&data_final=$data_final&linha=$linha&estado=$estado&criterio=$criterio' target='_blank'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>el download en EXCEL</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Usted puede ver, imprimir y guardar la tabla.</font></td>";
		echo "</tr>";
		echo "</table>";
		
	}else{
		echo "<br>";
		
		echo "<b>Ningún resultado encuentrado entre $data_inicial y $data_final $mostraMsgLinha $mostraMsgEstado</b>";
	}
	
}

flush();

?>

<p>

<? include "rodape.php" ?>
