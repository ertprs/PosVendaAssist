<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
?>
<style type="text/css">

.inicio {
	font-family: Arial;
	FONT-SIZE: 8pt; 
	font-weight: bold;
	text-align: left;
	color: #FFFFFF;
}

.conteudo {
	font-family: Arial;
	FONT-SIZE: 8pt; 
	font-weight: bold;
	text-align: left;
	background: #F4F7FB;
}

.conteudo2 {
	font-family: Arial;
	FONT-SIZE: 8pt; 
	font-weight: bold;
	text-align: left;
	background: #FFDCDC;
}
</style>
<?
$os           = $_GET['os'];
$sua_os       = $_GET['sua_os'];
$codigo_posto = $_GET['codigo_posto'];
if(strlen($os)> 0) {
	$sql="SELECT tbl_os.nota_fiscal                                       ,
				 to_char(tbl_os.data_nf_saida,'DD/MM/YY') as data_nf_saida,
				 tbl_os.consumidor_revenda                                ,
				 tbl_os_troca.ri                                          ,
				 tbl_os_troca.observacao      as observacao_troca         ,
				 tbl_status_os.descricao                                  
			FROM tbl_os
			LEFT JOIN tbl_os_troca on tbl_os.os=tbl_os_troca.os
			LEFT JOIN tbl_status_os on tbl_status_os.status_os = tbl_os_troca.status_os 
			LEFT JOIN tbl_os_status        ON tbl_os_status.os = tbl_os.os and tbl_os_status.status_os = tbl_os_troca.status_os
			WHERE tbl_os.os=$os";
	$res=pg_exec($con,$sql);
	if(pg_numrows($res) > 0) {
		$ri					= pg_result($res,0,ri);
		$descricao			= pg_result($res,0,descricao);
		$nota_fiscal		= pg_result($res,0,nota_fiscal);
		$observacao_troca	= pg_result($res,0,observacao_troca);
		$data_nf_saida		= pg_result($res,0,data_nf_saida);
		$consumidor_revenda	= pg_result($res,0,consumidor_revenda);

		if ($consumidor_revenda == 'R')		$consumidor_revenda = 'REVENDA';
		elseif ($consumidor_revenda == 'C')	$consumidor_revenda = 'CONSUMIDOR';
	}
}
		echo "<BR><BR><table width='350' border='0' align='center' cellpadding='3' cellspacing='1' style='font-family: verdana; font-size: 11px' bgcolor='#FFFFFF'>";

		echo "<caption><FONT SIZE='4' COLOR='#C67700'>OS FABRICANTE - ";
		echo $codigo_posto.$sua_os ."</FONT> <br> ". $consumidor_revenda;
		echo "</caption>";

		//hd 45281
		$sqlx = "SELECT tbl_os_status.observacao
				FROM tbl_os
				LEFT JOIN tbl_os_status ON tbl_os_status.os = tbl_os.os AND   tbl_os_status.status_os = 15
				WHERE tbl_os.excluida         IS TRUE
				AND   tbl_os.os               = $os
				ORDER BY tbl_os_status.os_status LIMIT 1";
		$resx = pg_exec($con, $sqlx);
		if (pg_numrows($resx) > 0) {
			$descricaox = pg_result($resx,0,0);

			echo "<tr>";
			echo "<td bgcolor='#485989' colspan='100%' align='center'><font color='#FFFFFF'><B>Excluída do Sistema</B></font></td>";
			echo "</TR>";
			echo "<TR>";
			echo "<TD class='conteudo' colspan='100%' align='center'>$descricaox</TD>";
			echo "</TR>";
		}

		echo "<tr>";
		echo "<td bgcolor='#485989' colspan='100%' align='center'><font color='#FFFFFF'><B>SITUAÇÃO DA TROCA</B></font></td>";
		echo "</TR>";
		echo "<TR>";
		echo "<TD class='conteudo' colspan='100%' align='center'>$descricao</TD>";
		echo "</TR>";
		echo "<TR>";
		echo "<TD bgcolor='#485989' colspan='100%' align='center'><font color='#FFFFFF'><B>HISTÓRICO</B></font></TD>";
		echo "</TR>";
		$sql2="SELECT to_char(data,'DD/MM/YYYY') as data,descricao,observacao from tbl_os_status join tbl_status_os using(status_os) where os=$os AND status_os_troca IS TRUE";
		$res2=pg_exec($con,$sql2);
		if(pg_numrows($res2) > 0){
			for ($i = 0 ; $i < pg_numrows ($res2) ; $i++) {
				$data              = pg_result($res2,$i,data);
				$descricao_status  = pg_result($res2,$i,descricao);
				$observacao_status = pg_result($res2,$i,observacao);
				echo "<TR>";
				echo "<TD class='conteudo' colspan='100%' align='center'>$data - $descricao_status</TD>";
				echo "</TR>";
				echo "<TR>";
				echo "<TD class='conteudo2' colspan='100%' align='center'>Motivo: $observacao_status</TD>";
				echo "</TR>";

			}
		}
		echo "<TR>";
		echo "<td bgcolor='#485989' colspan='100%'><font color='#FFFFFF'><B>OBSERVAÇÃO DO POSTO</B></font></td>";
		echo "</TR>";
		echo "<TR>";
		echo "<TD class='conteudo' colspan='100%'>$observacao_troca</TD>";
		echo "</TR>";
		echo "<tr>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>PEDIDO</B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>NOTA FISCAL</B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>DATA NF</B></font></td>";
		echo "</TR>";
		echo "<TR>";
		echo "<TD class='conteudo'>$ri</TD>";
		echo "<TD class='conteudo'>$nota_fiscal</TD>";
		echo "<TD class='conteudo'>$data_nf_saida</TD>";
		echo "</TR>";
		echo "</TABLE>";

?>