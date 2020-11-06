<?
#echo "Rotina em manutenção! Aguarde alguns instantes!"; 
#exit;
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

if(strlen($_GET['extrato']) == 0){
	header("Location: os_extrato.php");
	exit;
}

$extrato = trim($_POST['extrato']);
if(strlen($_GET['extrato']) > 0) $extrato = trim($_GET['extrato']);

$posto = trim($_POST['posto']);
if(strlen($_GET['posto']) > 0) $posto = trim($_GET['posto']);

$msg_erro = "";

$layout_menu = "os";
if($sistema_lingua == 'ES') $title = "Extracto - Detallado";
else $title = "Extrato - Detalhado";

include "cabecalho.php";

?>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#000000;
	background-color: #d9e2ef
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF;
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

</style>

<p>
<?
if(strlen($msg_erro) > 0){
	echo "<TABLE width=\"600\" align='center' border=0>";
	echo "	<TR>";
	echo "		<TD align='center'>$msg_erro</TD>";
	echo "	</TR>";
	echo "</TABLE>";
}
// hd 16353
if($login_fabrica==11){
	$sql_status=" AND tbl_os_status.status_os <> 58 ";
}
$sql = "SELECT 	tbl_os.os                                             ,
			tbl_os.sua_os                                             , 
			tbl_os.consumidor_nome                                    , 
			tbl_os.mao_de_obra                                        ,
			tbl_os.pecas                                              ,
			tbl_os_status.status_os as status_da_os                   , 
			to_char(tbl_os_status.data, 'DD/MM/YYYY')  AS data_recusa ,
			tbl_os_status.extrato                                     ,
			tbl_os_status.observacao  
		FROM tbl_os_status 
		JOIN tbl_os on tbl_os.os=tbl_os_status.os 
		WHERE tbl_os_status.extrato=$extrato
		AND tbl_os_status.os NOT IN (
									SELECT tbl_os_extra.os 
										FROM tbl_os_extra 
										WHERE tbl_os_extra.extrato=$extrato)
		$sql_status";
		#order by sua_os, os";



if($login_fabrica ==19){
	$sql = "
	SELECT 	tbl_os.os                                             ,
		tbl_os.sua_os                                             ,
		tbl_os.consumidor_nome                                    ,
		tbl_os.mao_de_obra                                        ,
		tbl_os.pecas                                              ,
		tbl_os_status.status_os as status_da_os                   ,
		to_char(tbl_os_status.data, 'DD/MM/YYYY')  AS data_recusa ,
		tbl_os_status.extrato                                     ,
		tbl_os_status.observacao  ,
		(   SELECT status_os 
			FROM tbl_os_status 
			WHERE tbl_os_status.os = tbl_os.os 
			AND tbl_os_status.status_os = 17
			ORDER BY os_status DESC LIMIT 1
		)                                     AS status_os_aprovada
	FROM tbl_os_extra
	JOIN tbl_os_status on tbl_os_extra.os = tbl_os_status.os
	JOIN tbl_os on tbl_os.os=tbl_os_status.os 
	WHERE tbl_os_extra.extrato=$extrato
		AND tbl_os_status.os_status = 
			(SELECT os_status
			 FROM tbl_os_status 
			 WHERE tbl_os_status.os = tbl_os.os and tbl_os_status.status_os <>70
			 ORDER BY os_status DESC LIMIT 1)
		AND tbl_os_status.os IN (
			SELECT os 
			FROM tbl_os_status 
			WHERE tbl_os_status.os = tbl_os.os 
			AND tbl_os_status.status_os IN(13, 14, 15)
			ORDER BY os_status DESC LIMIT 1)
	";
}


$res = pg_exec($con,$sql);
#echo nl2br($sql);

		if ($status_da_os=='13'){$cor="#FFCA99";}
		if ($status_da_os=='14'){$cor="#FFA390";}
		if ($status_da_os=='15'){$cor="#B44747";}
if (pg_numrows($res) > 0){
echo "<table width='300' align='center' border='0' cellspacing='1' cellpadding='0'>\n";
		echo "<tr>";
		echo "<td colspan='2' align='center'><font size='1'><B>Legenda</B></font></td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td align='center' bgcolor='#FFCA99' width='50' heigth='25'>&nbsp;</td>";
		
		if($sistema_lingua == 'ES') echo "<td align='left'>&nbsp;<font size='1'>OS rechazada por el proveedor</font></td>";
		else echo "<td align='left'>&nbsp;<font size='1'>OS recusada pelo fabricante</font></td>";
		
		echo "</tr>";
		echo "<tr>";
		echo "<td align='center' bgcolor='#FFA390' heigth='25'>&nbsp;</td>";
		
		if($sistema_lingua == 'ES') echo "<td align='left'>&nbsp;<font size='1'>Retirada del extracto</font></td>";
		else echo "<td align='left'>&nbsp;<font size='1'>Retirada do extrato</font></td>";
		
		echo "</tr>";
		echo "<tr>";
		echo "<td align='center' bgcolor='#B44747' heigth='25'>&nbsp;</td>";
		
		if($sistema_lingua == 'ES') echo "<td align='left'>&nbsp;<font size='1'>OS excluída por el proveedor</font></td>";
		else echo "<td align='left'>&nbsp;<font size='1'>OS excluida pelo fabricante</font></td>";
		
		echo "</tr>";
		echo "</table><BR>";

		echo "<table width='700' align='center' border='0' cellspacing='1' cellpadding='3'>\n";
		echo "<tr class='menu_top'>";
		
		if($sistema_lingua == 'ES') echo "<td colspan='5' heigth='30' align='center'>".pg_numrows($res)." OS no calculadas en el extracto $extrato</td>";
		else echo "<td colspan='5' heigth='30' align='center'>".pg_numrows($res)." OS não entraram no EXTRATO $extrato</td>";
		
		echo "</tr>";
		echo "<tr class='menu_top'>\n";
		echo "<td align='center' width='17%'>OS</td>\n";
		echo "<td align='center'>CLIENTE</td>\n";
		echo "<td align='center'>MO</td>\n";
		if ( $login_fabrica != 104 && $login_fabrica != 105 && $login_fabrica != 106 && $login_fabrica != 108 && $login_fabrica != 111) {
			if($sistema_lingua == 'ES') echo "<td align='center'>REPUESTO</td>\n";
			else echo "<td align='center'>PEÇAS</td>\n";
		}
		if($sistema_lingua == 'ES') echo "<td align='center'>Observación</td>\n";
		else echo "<td align='center'>Observação</td>\n";

		echo "</tr>";
		$total_mao_de_obra = 0;
		$total_pecas = 0;
	for($x=0; $x< pg_numrows($res);$x++){
		$sua_os             = pg_result($res,$x,sua_os);
		$os                 = pg_result($res,$x,os);
		$status_da_os       = pg_result($res,$x,status_da_os);
		$data_recusa        = pg_result($res,$x,data_recusa);
		$extrato            = pg_result($res,$x,extrato);
		$consumidor_nome    = pg_result($res,$x,consumidor_nome);
		$mao_de_obra        = pg_result($res,$x,mao_de_obra);
		$pecas              = pg_result($res,$x,pecas);
		$observacao         = pg_result($res,$x,observacao);
		$total_mao_de_obra += $mao_de_obra;
		$total_pecas += $pecas;
		$cor="#d9e2ef";
		if ($status_da_os=='13'){$cor="#FFCA99";}
		if ($status_da_os=='14'){$cor="#FFA390";}
		if ($status_da_os=='15'){$cor="#B44747";}
		echo "<tr class='menu_top' style='background-color: $cor'>\n";
		echo "<td align='center' width='17%'><a href=\"os_press.php?os=$os\" target='_blank'><font color='#000000'>$sua_os</font></a></td>\n";
		echo "<td align='left' nowrap>$consumidor_nome</td>\n";
		echo "<td align='center'>". number_format ($mao_de_obra,2,",",".") ."</td>\n";
		if ( $login_fabrica != 104 && $login_fabrica != 105 && $login_fabrica != 106 && $login_fabrica != 108 && $login_fabrica != 111) {
			echo "<td align='center'>". number_format ($pecas,2,",",".") ."</td>\n";
		}
		echo "<td align='left' >$observacao</td>\n";
		echo "</tr>";
		
	}
		echo "<tr class='menu_top'>\n";
		if ($login_fabrica == 104 || $login_fabrica == 105 || $login_fabrica == 106 || $login_fabrica == 108 || $login_fabrica == 111) {
			echo "<td align='right' colspan='2'>TOTAL</td>\n";
		} else {
			echo "<td align='right' colspan='2'></td>\n";
		}
		echo "<td align='center'>". number_format ($total_mao_de_obra,2,",",".") ."</td>\n";
		if ( $login_fabrica != 104 && $login_fabrica != 105 && $login_fabrica != 106 && $login_fabrica != 108 && $login_fabrica != 111) {
			echo "<td align='center'>". number_format ($total_pecas,2,",",".") ."</td>\n";
		}
		echo "<td align='center'></td>\n";
		echo "</tr>";
		if ( $login_fabrica != 104 && $login_fabrica != 105 && $login_fabrica != 106 && $login_fabrica != 108 && $login_fabrica != 111){
			echo "<tr class='menu_top'>\n";
			if($sistema_lingua == 'ES') echo "<td align='right' colspan='2'>Valor total (MO + Repuesto)&nbsp;&nbsp; </td>\n";
			else echo "<td align='right' colspan='2'>Valor total (MO + Peça)&nbsp;&nbsp; </td>\n";
		
			echo '<td>&nbsp;</td>';
			echo "<td align='lefth' colspan='3'>" . number_format ($total_mao_de_obra +  $total_pecas,2,",",".") . "</td>\n";
		
			echo "</tr>";
		}
	echo "</table>";
}else{
	if($sistema_lingua == 'ES') echo "<center>Ninguna orden de servicio encuentrada fue Rejeictada</center>";
	else                        echo "<center>Nenhuma Ordem de serviço foi rejeitada</center>";
}
?>
</table>
	
<br>

<table align='center'>
<tr>
	<td>
		<br>

		<?if($sistema_lingua == 'ES') {?>
			<img src="admin_es/imagens/btn_volver.gif" onclick="javascript: window.location='os_extrato.php';" ALT="Voltar" border='0' style="cursor:pointer;">
			&nbsp;&nbsp;
		<? } else {?>
		<img src="imagens/btn_voltar.gif" onclick="javascript: window.location='os_extrato.php';" ALT="Voltar" border='0' style="cursor:pointer;">
		&nbsp;&nbsp;
		<? } ?>

<? 
	if ($login_fabrica == 1) $url = "os_extrato_detalhe_print_blackedecker.php";
	else                     $url = "os_extrato_detalhe_print.php";
?>
		<img src="imagens/btn_imprimir.gif" onclick="javascript: janela=window.open('<? echo $url; ?>?extrato=<? echo $extrato; ?>','extrato');" ALT="Imprimir" border='0' style="cursor:pointer;">


	</td>
</tr>
</table>

<p>
<p>

<? include "rodape.php"; ?>
