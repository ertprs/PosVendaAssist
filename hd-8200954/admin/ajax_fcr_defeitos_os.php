<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';

header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
//header("Pragma: no-cache, public");

$peca       = $_GET ['peca'];
$pais       = $_GET ['pais'];
$posto      = $_GET ['posto'];

$defeito = $_GET ['defeito'];
$data_inicial = $_GET ['data_inicial'];
$data_final = $_GET ['data_final'];
$tipo_os    = $_GET ['tipo_os'];

$cond = "1=1";
$cond_pais = "1=1";
$cond_3 = "1=1";
if($login_fabrica == 20) {
	$cond_pais = " tbl_posto.pais = '$pais'";
}

if (strlen ($posto)   > 0) $cond_3 = " tbl_posto.posto     = $posto ";

if(strlen($tipo_os)>0)$cond=" tbl_os.consumidor_revenda = '$tipo_os' ";
$sql = "SELECT 	tbl_os.os,
			tbl_os.sua_os,
			tbl_posto.nome,
			tbl_posto_fabrica.codigo_posto,
			to_char(tbl_os.data_abertura,'DD/MM') as data_abertura,
			to_char(tbl_os.data_fechamento,'DD/MM') as data_fechamento
		FROM tbl_os 
		JOIN tbl_os_produto USING (os) 
		JOIN tbl_os_item USING (os_produto) 
		JOIN tbl_defeito USING (defeito) 
		JOIN tbl_posto on tbl_os.posto = tbl_posto.posto
		JOIN tbl_posto_fabrica on tbl_os.posto = tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica = $login_fabrica
		WHERE tbl_os.data_abertura BETWEEN '$data_inicial' AND '$data_final' 

		AND tbl_os.fabrica = $login_fabrica
		AND tbl_os_item.peca = $peca
		AND $cond
		AND $cond_pais
		AND $cond_3
AND tbl_defeito.defeito = $defeito
order by tbl_os.os";
$res = pg_exec($con, $sql);

if(pg_numrows($res)>0){
echo "<TABLE width='450' cellspacing='1' cellpadding='2' border='0' align = 'center' style='font-family: verdana; font-size: 10px'  bgcolor='#596D9B'>";

/*if($login_admin == 568 ){
	echo "<TR>";
	echo "<TD align='center'><font color='#ffffff'><B>$sql</B></font></TD>";
	echo "</TR>";
}*/
	
	echo "<TR>";
	echo "<TD align='center'><font color='#ffffff'><B>OS</B></font></TD>";
	echo "<TD align='center'><font color='#ffffff'><B>Abertura</B></font></TD>";
	echo "<TD align='center'><font color='#ffffff'><B>Fechamento</B></font></TD>";
	echo "<TD align='center'><font color='#ffffff'><B>Posto</B></font></TD>";
	echo "</TR>";
	for($x=0;pg_numrows($res)>$x;$x++){
		$os             = pg_result($res,$x,os);
		$sua_os         = pg_result($res,$x,sua_os);
		$nome           = pg_result($res,$x,nome);
		$codigo_posto   = pg_result($res,$x,codigo_posto);
		$data_abertura  = pg_result($res,$x,data_abertura);
		$data_fechamento= pg_result($res,$x,data_fechamento);
		echo "<TR>";
		echo "<TD align='center' bgcolor='#FFFFFF'><a href='os_press.php?os=$os' target='blank'>$sua_os</a></TD>";
		echo "<TD align='center' bgcolor='#FFFFFF'>$data_abertura</TD>";
		echo "<TD align='center' bgcolor='#FFFFFF'>$data_fechamento</TD>";
		echo "<TD  bgcolor='#FFFFFF'>$codigo_posto - $nome</TD>";
		echo "</TR>";
	}
echo "</table>";
}

?>
