<?

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';

if ($btn_finalizar == 1) {
	if (strlen($erro) == 0) {
		if (strlen($_POST["data_inicial_01"]) == 0) {
			$erro .= "Favor informar la fecha inicial para busca<br>";
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
			$erro .= "Favor informar la fecha final para busca<br>";
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
		$msg  = "<b>Fue(fueran) detectado(s) lo(s) seguiente(s) error(es): </b><br>";
		$msg .= $erro;
	}
	
}



$layout_menu = "gerencia";
$title = "REPORTE DE ÓRDENES DE SERVICIOS SIN PIEZAS";

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
	<TD colspan="4" class="menu_top"><div align="center"><b>Reporte de órdenes de servicios sin pieza</b></div></TD>
  </TR>

  <TR>
    <TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line" colspan='2'><center>Ese  reporte considera la fecha de abertura de la OS.</center></TD>
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
	<TD class="table_line" style="width: 185px"><center><INPUT size="12" maxlength="10" TYPE="text" NAME="data_inicial_01" value="<? if (strlen($data_inicial) > 0) echo $data_inicial; else echo "dd/mm/aaaa"; ?>" onClick="this.value=''">&nbsp;<IMG src="imagens_admin/btn_lupa.gif" align='absmiddle' onclick="javascript:showCal('dataPesquisaInicial_01')" style="cursor:pointer" alt="Haga um click aquí para abrir el calendario"></center></TD>
	<TD class="table_line" style="width: 185px"><center><INPUT size="12" maxlength="10" TYPE="text" NAME="data_final_01" value="<? if (strlen($data_final) > 0) echo $data_final; else echo "dd/mm/aaaa"; ?>" onClick="this.value=''">&nbsp;<IMG src="imagens_admin/btn_lupa.gif" align='absmiddle' onclick="javascript:showCal('dataPesquisaFinal_01')" style="cursor:pointer" alt="Haga um click aquí para abrir el calendario"></center></TD>
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
				echo "<option value=''>Todas</option>\n";
				
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
    <TD colspan="4" class="table_line" style="text-align: center;"><IMG src="imagens_admin/btn_pesquisar_400.gif" onclick="javascript: if ( document.frm_pesquisa.btn_finalizar.value == '0' ) { document.frm_pesquisa.btn_finalizar.value='1'; document.frm_pesquisa.submit() ; } else { alert ('Espere la submisión...'); }" style="cursor:pointer " alt='Buscar'></TD>
  </TR>
</TABLE>

</FORM>

<!-- =========== AQUI TERMINA O FORMULÁRIO FRM_PESQUISA =========== -->


<?
		
if ((strlen($aux_data_inicial)>0) AND (strlen($aux_data_final)>0)){
	$sql="SELECT 	tbl_os.os															, 
					tbl_os.sua_os														,
					tbl_posto.nome	as posto_nome										,
					tbl_os.defeito_reclamado											, 
					tbl_defeito_reclamado_idioma.descricao as defeito_reclamado_descricao		,
					tbl_os.defeito_constatado											, 
					tbl_defeito_constatado_idioma.descricao as defeito_constatado_descricao	,
					tbl_os.solucao_os													,
					tbl_servico_realizado_idioma.descricao as solucao,
					to_char(tbl_os.data_abertura,'DD/MM/YYYY')         AS abertura		,
					to_char(tbl_os.data_fechamento,'DD/MM/YYYY')       AS fechamento	,
					tbl_os.fabrica														,
					tbl_produto.descricao as produto_descricao
			FROM tbl_os 
			JOIN tbl_produto            USING (produto)
			JOIN tbl_produto_pais       ON tbl_produto.produto = tbl_produto_pais.produto and tbl_produto_pais.pais = '$login_pais'
			JOIN tbl_defeito_reclamado  USING (defeito_reclamado)
			JOIN tbl_defeito_reclamado_idioma  USING (defeito_reclamado)
			JOIN tbl_defeito_constatado USING (defeito_constatado)
			JOIN tbl_defeito_constatado_idioma USING (defeito_constatado)
			JOIN tbl_posto              USING (posto)
			JOIN tbl_servico_realizado  ON     tbl_servico_realizado.servico_realizado=tbl_os.solucao_os
			JOIN tbl_servico_realizado_idioma  ON     tbl_servico_realizado_idioma.servico_realizado=tbl_os.solucao_os

			WHERE tbl_os.data_abertura BETWEEN '$aux_data_inicial' AND '$aux_data_final'
			AND   tbl_posto.pais = '$login_pais'
			AND tbl_os.fabrica=$login_fabrica ";
	if (strlen($linha)>0) $sql .=" AND tbl_produto.linha=$linha ";
	$sql .=" AND tbl_os.data_fechamento notnull 
			AND tbl_os.os NOT IN(
								SELECT DISTINCT(tbl_os.os) 
									FROM tbl_os 
									JOIN tbl_produto    USING (produto)
									JOIN tbl_os_produto USING (os)
									JOIN tbl_posto      USING(posto)
									WHERE tbl_os.fabrica = $login_fabrica
									AND   tbl_posto.pais = '$login_pais'
									AND (tbl_os.data_abertura BETWEEN '$aux_data_inicial' AND '$aux_data_final') ";
									if (strlen($linha)>0) $sql .=" AND tbl_produto.linha=$linha ";
						$sql .=" ) order by tbl_defeito_reclamado.descricao, tbl_defeito_constatado.descricao, tbl_servico_realizado.descricao";
//	echo "$sql";exit;

	if (strlen($linha)>0) $cond_1 = " AND tbl_produto.linha=$linha ";
	if (strlen($marca)>0) $cond_1 = " AND tbl_produto.marca=$marca ";
	if($data_filtro == "nao_finalizada") $cond_x = " AND tbl_os.data_fechamento IS NULL  ";
	else                                 $cond_x = " AND tbl_os.data_fechamento IS NOT NULL ";

	$sql="
		SELECT DISTINCT(tbl_os.os) 
		INTO TEMP tmp_fcr_ossempeca_$login_admin
		FROM tbl_os 
		JOIN tbl_posto      ON tbl_posto.posto        = tbl_os.posto
		JOIN tbl_produto    ON tbl_produto.produto    = tbl_os.produto 
		JOIN tbl_os_produto ON tbl_os.os              = tbl_os_produto.os 
		JOIN tbl_os_item    ON tbl_os_item.os_produto = tbl_os_produto.os_produto
		WHERE tbl_os.fabrica=$login_fabrica 
		AND   tbl_posto.pais = '$login_pais'
		AND (tbl_os.data_abertura BETWEEN '$aux_data_inicial' AND '$aux_data_final') 
		$cond_1 $cond_2;

		SELECT 	tbl_os.os, 
				tbl_os.sua_os,
				tbl_posto.nome                                    AS posto_nome,
				tbl_os.defeito_reclamado,
				tbl_defeito_reclamado_idioma.descricao as defeito_reclamado_descricao,
				tbl_os.defeito_constatado,
				tbl_defeito_constatado_idioma.descricao as defeito_constatado_descricao,
				tbl_os.solucao_os,
				tbl_servico_realizado_idioma.descricao AS solucao,
				to_char(tbl_os.data_abertura,'DD/MM/YYYY')         AS abertura,
				to_char(tbl_os.data_fechamento,'DD/MM/YYYY')       AS fechamento,
				tbl_os.fabrica,
				tbl_produto.descricao as produto_descricao
		INTO TEMP tmp_fcr_ossempeca2_$login_admin
		FROM tbl_os 
		JOIN tbl_produto on tbl_produto.produto=tbl_os.produto
		JOIN tbl_produto_pais ON tbl_produto.produto = tbl_produto_pais.produto AND tbl_produto_pais.pais = '$login_pais'
		LEFT JOIN tbl_defeito_reclamado         USING (defeito_reclamado)
		LEFT JOIN tbl_defeito_reclamado_idioma  USING (defeito_reclamado)
		LEFT JOIN tbl_defeito_constatado        USING (defeito_constatado)
		LEFT JOIN tbl_defeito_constatado_idioma USING (defeito_constatado)
		LEFT JOIN tbl_servico_realizado         ON     tbl_servico_realizado.servico_realizado        = tbl_os.solucao_os
		LEFT JOIN tbl_servico_realizado_idioma  ON     tbl_servico_realizado_idioma.servico_realizado = tbl_os.solucao_os

		LEFT JOIN tbl_posto on tbl_os.posto = tbl_posto.posto
		WHERE (tbl_os.data_abertura BETWEEN '$aux_data_inicial' AND '$aux_data_final') 
		AND tbl_os.fabrica = $login_fabrica 
		AND tbl_posto.pais = '$login_pais'
		$cond_1 $cond_2 
		$cond_x
		AND tbl_os.os NOT IN( select os from tmp_fcr_ossempeca_$login_admin);

		SELECT * FROM  tmp_fcr_ossempeca2_$login_admin X
		ORDER BY X.defeito_reclamado_descricao, X.defeito_constatado_descricao, X.defeito_constatado_descricao";
	#echo nl2br($sql);
	#exit;
	$res = pg_exec($con, $sql);
	$qtde = pg_numrows($res);
	if(pg_numrows($res)>0){
	echo "<BR><BR><center><font size='1'>Fueran encuentradas $qtde OS sin piezas.</font></center><BR>";
		echo "<table width='700' border='0' bgcolor='#485989' align='center' cellpadding='3' cellspacing='1' style='font-family: verdana; font-size: 12px'>";
		echo "<tr height='25'>";
		echo "<td><font color='#FFFFFF'><B>OS</B></font></td>";
		//echo "<td><font color='#FFFFFF'><B>Posto</B></font></td>";
		echo "<td><font color='#FFFFFF'><B>Producto</B></font></td>";
		echo "<td><font color='#FFFFFF'><B>Abertura</B></font></td>";
		echo "<td><font color='#FFFFFF'><B>Cierre</B></font></td>";
		echo "<td><font color='#FFFFFF'><B>Reclamado</B></font></td>";
		echo "<td><font color='#FFFFFF'><B>Reparo</B></font></td>";
		echo "<td><font color='#FFFFFF'><B>Identificacion</B></font></td>";
		echo "</tr>";
		for ($i=0; $i<pg_numrows($res); $i++){
			$os								= trim(pg_result($res,$i,os));
			$sua_os							= trim(pg_result($res,$i,sua_os));
			$defeito_reclamado_descricao 	= trim(pg_result($res,$i,defeito_reclamado_descricao));
			$defeito_constatado_descricao 	= trim(pg_result($res,$i,defeito_constatado_descricao));
			$solucao 						= trim(pg_result($res,$i,solucao));
			$abertura 						= trim(pg_result($res,$i,abertura));
			$fechamento 					= trim(pg_result($res,$i,fechamento));
			$posto_nome 					= trim(pg_result($res,$i,posto_nome));
			$produto_descricao				= trim(pg_result($res,$i,produto_descricao));
			$cor = ($i % 2 == 0) ? "#FFFCF2": '#f4f7fb';
			echo "<tr >";
			echo "<td bgcolor='$cor' align='left'><a href='os_press.php?os=$os' target='blank'>$sua_os</A></td>";
			//echo "<td align='left'>$posto_nome</td>";
			echo "<td bgcolor='$cor' align='left'>$produto_descricao</td>";
			echo "<td bgcolor='$cor'>$abertura</td>";
			echo "<td bgcolor='$cor'>$fechamento</td>";
			echo "<td bgcolor='$cor' align='left'>$defeito_reclamado_descricao</td>";
			echo "<td bgcolor='$cor' align='left'>$defeito_constatado_descricao</td>";
			echo "<td bgcolor='$cor' align='left'>$solucao</td>";
			echo "</tr>";
		}
		echo "</table>";
	}else{
	echo "<center>Ninguna orden de servicio encuentrada.</center>";
	}
}

?>



		<? include "rodape.php"; ?>
 