<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
?>
<html>
<head>
<title>Telecontrol - Help Desk</title>
<link type="text/css" rel="stylesheet" href="css/css.css">
</head>
<body>
<?
include "menu.php";
$sql="SELECT to_char(current_date,'MM') as mes,
			 to_char(current_date,'YYYY') as ano;";
$res=pg_exec($con,$sql);
$mes=pg_result($res,0,mes);
$ano=pg_result($res,0,ano);
if (strlen($mes) > 0) {
	$data_inicial = date("Y-m-01 00:00:00", mktime(0, 0, 0, $mes, 1, $ano));
	$data_final   = date("Y-m-t 23:59:59", mktime(0, 0, 0, $mes, 1, $ano));
}

$sql="SELECT saldo_hora            ,
			 hora_franqueada       ,
			 hora_faturada         ,
			 hora_utilizada        ,
			 valor_faturado
		from tbl_hd_franquia
		where fabrica=$login_fabrica
		and mes='$mes'
		and ano='$ano'";
$res=pg_exec($con,$sql);
if(pg_numrows($res) > 0){
	$saldo_hora            = pg_result($res,0,saldo_hora);
	$hora_franqueada       = pg_result($res,0,hora_franqueada);
	$hora_faturada         = pg_result($res,0,hora_faturada);
	$hora_utilizada        = pg_result($res,0,hora_utilizada);
	$valor_faturado        = pg_result($res,0,valor_faturado);

	echo "<table width='700' align='center' bgcolor='#FFFFFF' border='0'>";
	echo "<thead align='center'><FONT size=5>$mes/$ano</font></thead>";
	echo "<tr><td>";
	echo "Total de franquia de horas deste mês: $hora_franqueada";
	echo "</td></tr>";
	echo "<tr><td>";
	echo "Total de horas utilizadas: $hora_utilizada";
	echo "</td></tr>";
	echo "<tr><td>";
	echo "Saldo de Hora: $saldo_hora";
	echo "</td></tr>";
	echo "<tr><td>";
	echo "Hora faturada: $hora_faturada";
	echo "</td></tr>";
	echo "<tr><td>";
	echo "Valor faturado: $valor_faturado";
	echo "</td></tr>";
	echo "</table>";
}
	
$sql = "SELECT 
			hd_chamado ,
			tbl_hd_chamado.admin ,
			tbl_admin.nome_completo ,
			tbl_admin.login ,
			to_char (tbl_hd_chamado.data,'DD/MM HH24:MI') AS data,
			titulo ,
			status ,
			tbl_fabrica.nome AS fabrica_nome,
			hora_desenvolvimento
		FROM tbl_hd_chamado
		JOIN tbl_admin ON tbl_hd_chamado.admin = tbl_admin.admin
		JOIN tbl_fabrica ON tbl_admin.fabrica = tbl_fabrica.fabrica 
		WHERE tbl_hd_chamado.fabrica_responsavel = 10 
		AND tbl_hd_chamado.data_aprovacao BETWEEN '$data_inicial' AND '$data_final'
		AND tbl_hd_chamado.fabrica=$login_fabrica
		ORDER BY tbl_hd_chamado.status,tbl_hd_chamado.data DESC";
$res = pg_exec ($con,$sql);
   
		
if (@pg_numrows($res) > 0) {
	echo "<br><br>";
	echo "<table width = '700' align = 'center' cellpadding='1' cellspacing='1' border='0'>";
	echo "<tr>";
	echo "	<td colspan='6' align = 'center' width='100%' style='font-family: arial ; color:#666666' bgcolor='#D9E8FF'><CENTER>Chamados Aprovados do mês $mes </CENTER></td>";
	echo "	<td rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";
	echo "<tr bgcolor='#D9E8FF' style='font-family: arial ; color: #666666'>";
	echo "	<td>N°</td>";
	echo "	<td>Título</td>";
	echo "	<td>Status</td>";
	echo "	<td>Data</td>";
	echo "	<td>Solicitante</td>";
	echo "	<td>Horas Utilizadas</td>";
	echo "</tr>";

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$hd_chamado           = pg_result($res,$i,hd_chamado);
		$admin                = pg_result($res,$i,admin);
		$login                = pg_result($res,$i,login);
		$data                 = pg_result($res,$i,data);
		$titulo               = pg_result($res,$i,titulo);
		$status               = pg_result($res,$i,status);
		$nome_completo        = trim(pg_result($res,$i,nome_completo));
		$fabrica_nome         = trim(pg_result($res,$i,fabrica_nome));
		$hora_desenvolvimento         = trim(pg_result($res,$i,hora_desenvolvimento));		
		$cor='#F2F7FF';
		if ($i % 2 == 0) $cor = '#FFFFFF';

		echo "<tr  style='font-family: arial ; font-size: 12px ; cursor: hand; ' height='25' bgcolor='$cor'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor'\" ><a href='chamado_detalhe.php?hd_chamado=$hd_chamado'>";
		echo "<td>$hd_chamado&nbsp;</td>";
		echo "<td><a href='chamado_detalhe.php?hd_chamado=$hd_chamado'>$titulo</a></td>";
		if (($status != 'Resolvido') and ($status != 'Cancelado')) {
			echo "<td nowrap><font color=#FF0000><B>$status </B></font></td>";
		}else{
			echo "<td nowrap>$status </td>";
		}
		echo "<td nowrap>&nbsp;$data &nbsp;</td>";
		echo "<td>";
		if (strlen ($nome_completo) > 0) {
			echo $nome_completo;
			
		}else{
			echo $login;
		}
		echo "</td>";
		echo "<td align = 'center'>$hora_desenvolvimento</td>";
		echo "</tr>"; 
		
	}
	
	echo "<tr>";
	echo "<td colspan='5' align = 'center' width='100%'></td>";
	echo "</tr>";
	echo "</table>"; 
}

$sql = "SELECT 
			hd_chamado ,
			tbl_hd_chamado.admin ,
			tbl_admin.nome_completo ,
			tbl_admin.login ,
			to_char (tbl_hd_chamado.data,'DD/MM HH24:MI') AS data,
			titulo ,
			status ,
			tbl_fabrica.nome AS fabrica_nome,
			hora_faturada
		FROM tbl_hd_chamado
		JOIN tbl_admin ON tbl_hd_chamado.admin = tbl_admin.admin
		JOIN tbl_fabrica ON tbl_admin.fabrica = tbl_fabrica.fabrica 
		WHERE tbl_hd_chamado.fabrica_responsavel = 10 
		AND tbl_hd_chamado.data_aprovacao BETWEEN '$data_inicial' AND '$data_final'
		AND tbl_hd_chamado.fabrica=$login_fabrica
		AND hora_faturada IS NOT NULL
		ORDER BY tbl_hd_chamado.status,tbl_hd_chamado.data DESC";
$res = pg_exec ($con,$sql);
   
		
if (@pg_numrows($res) > 0) {
	echo "<br><br>";
	echo "<table width = '700' align = 'center' cellpadding='0' cellspacing='1' border='0'>";
	echo "<tr>";
	echo "	<td colspan='6' align = 'center' width='100%' style='font-family: arial ; color:#666666' bgcolor='#D9E8FF'><CENTER>Chamados Aprovados do mês $mes </CENTER></td>";
	echo "	<td rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";
	echo "<tr bgcolor='#D9E8FF' style='font-family: arial ; color: #666666'>";
	echo "	<td>N°</td>";
	echo "	<td>Título</td>";
	echo "	<td>Status</td>";
	echo "	<td>Data</td>";
	echo "	<td>Solicitante</td>";
	echo "<td>Horas Faturadas</td>";
	echo "</tr>";


	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$hd_chamado           = pg_result($res,$i,hd_chamado);
		$admin                = pg_result($res,$i,admin);
		$login                = pg_result($res,$i,login);
		$data                 = pg_result($res,$i,data);
		$titulo               = pg_result($res,$i,titulo);
		$status               = pg_result($res,$i,status);
		$nome_completo        = trim(pg_result($res,$i,nome_completo));
		$fabrica_nome         = trim(pg_result($res,$i,fabrica_nome));
		$hora_faturada        = trim(pg_result($res,$i,hora_faturada));
	
		
		$cor='#F2F7FF';
		if ($i % 2 == 0) $cor = '#FFFFFF';

		echo "<tr  style='font-family: arial ; font-size: 12px ; cursor: hand; ' height='25' bgcolor='$cor'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor'\" ><a href='chamado_detalhe.php?hd_chamado=$hd_chamado'>";
		echo "<td>$hd_chamado&nbsp;</td>";
		echo "<td><a href='chamado_detalhe.php?hd_chamado=$hd_chamado'>$titulo</a></td>";
		if (($status != 'Resolvido') and ($status != 'Cancelado')) {
			echo "<td nowrap><font color=#FF0000><B>$status </B></font></td>";
		}else{
			echo "<td nowrap>$status </td>";
		}
		echo "<td nowrap>&nbsp;$data &nbsp;</td>";
		echo "<td>";
		if (strlen ($nome_completo) > 0) {
			echo $nome_completo;
			
		}else{
			echo $login;
		}
		echo "</td>";
		echo "<td align = 'center'>$hora_faturada</td>";
		echo "</tr>"; 
		
	}
	
	echo "<tr>";
	echo "<td colspan='5' align = 'center' width='100%'></td>";
	echo "</tr>";
	echo "</table>"; 
}
	
include "rodape.php" 
?>
</body>
</html>