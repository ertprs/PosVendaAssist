<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

if(strlen($_GET['extrato']) == 0){
	echo "<script>";
	echo "close();";
	echo "</script>";
	exit;
}

$extrato = trim($_POST['extrato']);
if(strlen($_GET['extrato']) > 0){
	$extrato = trim($_GET['extrato']);
}

$title = "EXTRATO - DETALHADO";

?>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#000000;
}

.table_line {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
	font-weight: normal;
	border: 1px solid;
	color:#000000;
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	color:#000000;
}

</style>

<p>
<?
# ----------------------------------------- #
# -- VERIFICA SE É POSTO OU DISTRIBUIDOR -- #
# ----------------------------------------- #
$sql = "SELECT  DISTINCT
				tbl_tipo_posto.tipo_posto     ,
				tbl_posto.estado
		FROM    tbl_tipo_posto
		JOIN    tbl_posto_fabrica    ON tbl_posto_fabrica.tipo_posto = tbl_tipo_posto.tipo_posto
									AND tbl_posto_fabrica.posto      = $login_posto
									AND tbl_posto_fabrica.fabrica    = $login_fabrica
		JOIN    tbl_posto            ON tbl_posto.posto = tbl_posto_fabrica.posto
		WHERE   tbl_tipo_posto.distribuidor IS TRUE
		AND     tbl_posto_fabrica.fabrica = $login_fabrica
		AND     tbl_tipo_posto.fabrica    = $login_fabrica
		AND     tbl_posto_fabrica.posto   = $login_posto ";
$res = pg_exec ($con,$sql);
if (pg_numrows($res) == 0) $tipo_posto = "P"; else $tipo_posto = "D";


$sql = "SELECT  tbl_os.sua_os                                                   ,
				tbl_os.os                                                       ,
				tbl_os.mao_de_obra                                              ,
				tbl_os.mao_de_obra_distribuidor                                 ,
				tbl_os.pecas                                                    ,
				tbl_os.consumidor_nome                                          ,
				tbl_os.data_abertura                                            ,
				tbl_os.data_fechamento                                          ,
				to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data_geracao ,
				to_char(tbl_extrato_extra.baixado,'DD/MM/YYYY') AS baixado      ,
				lpad (tbl_extrato.protocolo,5,'0')               AS protocolo   ,
				tbl_extrato_extra.obs                                           ,
				tbl_os_extra.mao_de_obra                         AS extra_mo          ,
				tbl_os_extra.custo_pecas                         AS extra_pecas       ,
				tbl_os_extra.taxa_visita                         AS extra_instalacao  ,
				tbl_os_extra.deslocamento_km                     AS extra_deslocamento
		FROM	tbl_os_extra
		JOIN	tbl_os            ON tbl_os.os           = tbl_os_extra.os
		JOIN	tbl_extrato       ON tbl_extrato.extrato = tbl_os_extra.extrato
		JOIN	tbl_extrato_extra ON tbl_extrato.extrato = tbl_extrato_extra.extrato
		WHERE	tbl_os_extra.extrato = $extrato
		AND		tbl_os.posto         = $login_posto ";
$sql .= "ORDER BY    lpad(substr(tbl_os.sua_os,0,strpos(tbl_os.sua_os,'-')),20,0)               ASC,
					replace(lpad(substr(tbl_os.sua_os,strpos(tbl_os.sua_os,'-')),20,0),'-','') ASC";

$res = pg_exec ($con,$sql);

$totalRegistros = pg_numrows($res);
if ($totalRegistros == 0){
	echo "<script>";
	echo "close();";
	echo "</script>";
	exit;
}elseif ($totalRegistros > 0){
	$ja_baixado = false ;
	$protocolo = pg_result ($res,0,protocolo) ;

	echo "<TABLE width='650' align='center' border='0' cellspacing='0' cellpadding='1'>\n";
	echo "<tr>";
	echo "	<td class='menu_top' colspan='4' align='center'>";
	echo "	<BR><b>$title<br>Extrato ";
	if ($login_fabrica == 1) echo $posto_codigo.$protocolo;
	else                     echo $extrato;
	echo " gerado em " . pg_result ($res,0,data_geracao) ;
	echo "	</b><BR><BR></td>";
	echo "</tr>";
	echo "</TABLE><br>\n";

	echo "<TABLE width='650' align='center' border='0' cellspacing='0' cellpadding='1'>\n";
	echo "	<TR>\n";
	echo "		<TD class='menu_top' align='center' width='17%'>OS</TD>\n";
	echo "		<TD class='menu_top' align='center' width='35%'>CLIENTE</TD>\n";
	if ($login_fabrica == 6){
		echo "<td class='menu_top' align='center'>MO</td>\n";
		echo "<td class='menu_top' align='center'>MO REVENDA</td>\n";
		echo "<td class='menu_top' align='center'>PEÇAS</td>\n";
		echo "<td class='menu_top' align='center'>PEÇAS REVENDA</td>\n";
	}elseif ($login_fabrica == 19){
		echo "<td class='menu_top' align='center'>MO</td>\n";
		echo "<td class='menu_top' align='center'>PEÇAS</td>\n";
		echo "<td class='menu_top' align='center'>INST</td>\n";
		echo "<td class='menu_top' align='center'>DESL</td>\n";
		echo "<td class='menu_top' align='center'>TOTAL</td>\n";
	}else{
		echo "<td class='menu_top' colspan=2 align='center'>MO</td>\n";
		echo "<td class='menu_top' colspan=2 align='center'>PEÇAS</td>\n";
	}
	echo "	</TR>\n";

	$total             = 0;
	$total_mao_de_obra = 0;
	$total_pecas       = 0;
	
	$total_mao_de_obra_revenda = 0;
	$total_pecas_revenda       = 0;
	
	$total_extra_mo            = 0;
	$total_extra_pecas         = 0;
	$total_extra_instalacao    = 0;
	$total_extra_deslocamento  = 0;
	$total_extra_total         = 0;
	
	for ($i = 0 ; $i < $totalRegistros; $i++){
		$os				 = trim(pg_result ($res,$i,os));
		$sua_os			 = trim(pg_result ($res,$i,sua_os));
		$mao_de_obra	 = trim(pg_result ($res,$i,mao_de_obra));
		$mao_de_obra_distribuidor = trim(pg_result ($res,$i,mao_de_obra_distribuidor));
		$pecas			 = trim(pg_result ($res,$i,pecas));
		$consumidor_nome = strtoupper(trim(pg_result ($res,$i,consumidor_nome)));
		$consumidor_str	 = substr($consumidor_nome,0,40);
		$data_abertura   = trim (pg_result ($res,$i,data_abertura));
		$data_fechamento = trim (pg_result ($res,$i,data_fechamento));
		$baixado         = pg_result ($res,0,baixado) ;
		$obs             = pg_result ($res,0,obs) ;
		
		if ($login_fabrica == 19) {
		    $extra_mo  		  = trim(pg_result ($res,$i,extra_mo));
		    $extra_pecas 	  = trim(pg_result ($res,$i,extra_pecas));
		    $extra_instalacao	  = trim(pg_result ($res,$i,extra_instalacao));
		    $extra_deslocamento	  = trim(pg_result ($res,$i,extra_deslocamento));
		    $extra_total 	  = $extra_mo + $extra_pecas + $extra_instalacao + $extra_deslocamento;
		
		    $total_extra_mo	   	+= $extra_mo;
		    $total_extra_pecas         	+= $extra_pecas;
		    $total_extra_instalacao    	+= $extra_instalacao;
		    $total_extra_deslocamento  	+= $extra_deslocamento;
		    $total_extra_total         	+= $extra_total;
		}
		
		if (strlen($baixado) > 0) $ja_baixado = true ;
		
		if ($login_fabrica == 6){
			if ($consumidor_revenda == 'R'){
				$mao_de_obra         = '0,00';
				$mao_de_obra_revenda = $mao_de_obra;
				$pecas_posto         = '0,00';
				$pecas_revenda       = $pecas;
				
				if ($tipo_posto == "P") $total_mao_de_obra_revenda += $mao_de_obra_revenda ;
				else					$total_mao_de_obra_revenda += $mao_de_obra_distribuidor ;
			}else{
				$mao_de_obra         = $mao_de_obra;
				$mao_de_obra_revenda = '0,00';
				$pecas_posto         = $pecas;
				$pecas_revenda       = '0,00';
				
				if ($tipo_posto == "P") $total_mao_de_obra += $mao_de_obra;
				else					$total_mao_de_obra += $mao_de_obra_distribuidor ;
			}
			
			$total_pecas         += $pecas_posto;
			$total_pecas_revenda += $pecas_revenda;
		}else{
			//if ($tipo_posto == "P") {
				$total_mao_de_obra += $mao_de_obra;
			//}else{
			//	$total_mao_de_obra += $mao_de_obra_distribuidor ;
			//}
			$mao_de_obra         = $mao_de_obra;
			$pecas_posto         = $pecas;
			$total_pecas        += $pecas ;
		}

		# soma valores
/*
		if ($tipo_posto == "P") {
			$total_mao_de_obra += $mao_de_obra ;
		}else{
			$total_mao_de_obra += $mao_de_obra_distribuidor ;
		}
		$total_pecas       += $pecas ;
*/		
		echo "	<TR>\n";
		echo "		<TD class='table_line' align='center'>";
		if($login_fabrica == 1) echo $posto_codigo;
		echo "$sua_os</TD>\n";
		echo "		<TD class='table_line' align='left' nowrap>$consumidor_str &nbsp;</TD>\n";
		/*
		if ($tipo_posto == "P") {
			echo "		<TD class='table_line' align='right' style='padding-right:5px'>" . number_format ($mao_de_obra,2,",",".") . "</TD>\n";
		}else{
			echo "		<TD class='table_line' align='right' style='padding-right:5px'>" . number_format ($mao_de_obra_distribuidor,2,",",".") . "</TD>\n";
		}
		echo "		<TD class='table_line' align='right' style='padding-right:5px'>" . number_format ($pecas,2,",",".") . "&nbsp;</TD>\n";
		*/
		if ($login_fabrica == 6){
			if ($tipo_posto == "P") {
				echo "<td class='table_line' align='right' style='padding-right:2px'> R$ " . number_format ($mao_de_obra,2,",",".") . "</td>\n";
				echo "<td class='table_line' align='right' style='padding-right:2px'> R$ " . number_format ($mao_de_obra_revenda,2,",",".") . "</td>\n";
			}else{
				echo "<td class='table_line' align='right' style='padding-right:2px'> R$ " . number_format ($mao_de_obra_distribuidor,2,",",".") . "</td>\n";
				echo "<td class='table_line' align='right' style='padding-right:2px'> R$ " . number_format ($mao_de_obra_revenda,2,",",".") . "</td>\n";
			}
			echo "<td class='table_line' align='right' style='padding-right:2px'> R$ " . number_format ($pecas_posto,2,",",".") . "</td>\n";
			echo "<td class='table_line' align='right' style='padding-right:2px'> R$ " . number_format ($pecas_revenda,2,",",".") . "</td>\n";
		}elseif ($login_fabrica == 19){
			echo "<td class='table_line' align='right' style='padding-right:2px'> " . number_format ($extra_mo,2,",",".") . "</td>\n";
			echo "<td class='table_line' align='right' style='padding-right:2px'> " . number_format ($extra_pecas,2,",",".") . "</td>\n";
			echo "<td class='table_line' align='right' style='padding-right:2px'> " . number_format ($extra_instalacao,2,",",".") . "</td>\n";
			echo "<td class='table_line' align='right' style='padding-right:2px'> " . number_format ($extra_deslocamento,2,",",".") . "</td>\n";
			echo "<td class='table_line' align='right' style='padding-right:2px'> " . number_format ($extra_total,2,",",".") . "</td>\n";
		}else{
			//if ($tipo_posto == "P") {
				echo "<td class='table_line' colspan=2 align='right' style='padding-right:2px'> R$ " . number_format ($mao_de_obra,2,",",".") . "</td>\n";
			//}else{
			//	echo "<td colspan=2 align='right' style='padding-right:5px'> R$ " . number_format ($mao_de_obra_distribuidor,2,",",".") . "</td>\n";
			//}
			
			echo "<td class='table_line' colspan=2 align='right' style='padding-right:2px'> R$ " . number_format ($pecas_posto,2,",",".") . "</td>\n";
		}
		
		echo "	</TR>\n";
	}
	
	echo "<tr class='table_line'>\n";
	echo "<td colspan=\"2\"></td>\n";
	if ($login_fabrica == 6){
		echo "<td align='right' bgcolor='$cor' style='padding-right:2px' nowrap><b> R$ " . number_format ($total_mao_de_obra,2,",",".") . "</b></td>\n";
		echo "<td align='right' bgcolor='$cor' style='padding-right:2px' nowrap><b> R$ " . number_format ($total_mao_de_obra_revenda,2,",",".") . "</b></td>\n";
		echo "<td align='right' bgcolor='$cor' style='padding-right:2px' nowrap><b> R$ " . number_format ($total_pecas,2,",",".") . "</b></td>\n";
		echo "<td align='right' bgcolor='$cor' style='padding-right:2px' nowrap><b> R$ " . number_format ($total_pecas_revenda,2,",",".") . "</b></td>\n";
	}elseif ($login_fabrica == 19){
		echo "<td align='right' bgcolor='$cor' style='padding-right:2px' nowrap><b> R$ " . number_format ($total_extra_mo,2,",",".") . "</b></td>\n";
		echo "<td align='right' bgcolor='$cor' style='padding-right:2px' nowrap><b> R$ " . number_format ($total_extra_pecas,2,",",".") . "</b></td>\n";
		echo "<td align='right' bgcolor='$cor' style='padding-right:2px' nowrap><b> R$ " . number_format ($total_extra_instalacao,2,",",".") . "</b></td>\n";
		echo "<td align='right' bgcolor='$cor' style='padding-right:2px' nowrap><b> R$ " . number_format ($total_extra_deslocamento,2,",",".") . "</b></td>\n";
		echo "<td align='right' bgcolor='$cor' style='padding-right:2px' nowrap><b> R$ " . number_format ($total_extra_total,2,",",".") . "</b></td>\n";
	}else{
		echo "<td colspan=2 align='right' bgcolor='$cor' style='padding-right:2px' nowrap><b> R$ " . number_format ($total_mao_de_obra,2,",",".") . "</b></td>\n";
		echo "<td colspan=2 align='right' bgcolor='$cor' style='padding-right:2px' nowrap><b> R$ " . number_format ($total_pecas,2,",",".") . "</b></td>\n";
	}
	echo "</tr>\n";
	if ($login_fabrica == 19) {
	    echo "<tr class='table_line'>\n";
	    echo "<td colspan=\"2\" align=\"center\" style='padding-right:2px'><b>TOTAL (MO + Peças)</b></td>\n";
	    echo "<td colspan=\"5\" bgcolor='$cor' align='center'><b> R$ " . number_format ($total_extra_total,2,",",".") . "</b></td>\n";
	}else{
	    echo "<tr class='table_line'>\n";
	    echo "<td colspan=\"2\" align=\"center\" style='padding-right:2px'><b>TOTAL (MO + Peças)</b></td>\n";
	    echo "<td colspan=\"4\" bgcolor='$cor' align='center'><b> R$ " . number_format ($total_mao_de_obra + $total_mao_de_obra_revenda + $total_pecas + $total_pecas_revenda,2,",",".") . "</b></td>\n";
	}
	echo "</tr>\n";
	echo "</TABLE>\n";
	
}

echo "</TABLE>\n";

?>

<BR>

<? if ($ja_baixado == true) { ?>
<TABLE width='650' border='0' cellspacing='1' cellpadding='0' align='center'>
<TR>
	<TD height='20' class="table_line" colspan='4'>PAGAMENTO</TD>
</TR>
<TR>
	<TD align='left' class="table_line" width='20%'>EXTRATO PAGO EM: </TD>
	<TD class="table_line" width='15%'><? echo $baixado; ?></TD>
	<TD align='left' class="table_line" width='15%'><center>OBSERVAÇÃO:</center></TD>
	<TD class="table_line" width='50%'><? echo $obs;?>
	</td>
</TR>
</TABLE>
<? } ?>

<br>

<p>

<script>
	window.print();
</script>