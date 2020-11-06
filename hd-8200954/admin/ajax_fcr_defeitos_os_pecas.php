<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';

header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
//header("Pragma: no-cache, public");

$peca    = $_GET ['peca'];
$defeito = $_GET ['defeito'];
$serie   = $_GET ['serie'];
$produto = $_GET ['produto'];

$condConsumidorRevenda = '';
if (!empty($_GET['tipo_os'])) {
	$tipo_os = $_GET['tipo_os'];
	$condConsumidorRevenda = "AND tbl_os.consumidor_revenda = '$tipo_os'";
}

$sql = "SELECT  distinct tbl_os.os,
				tbl_os.sua_os,
				to_char(tbl_os.data_abertura,'DD/MM') as data_abertura,
				to_char(tbl_os.data_fechamento,'DD/MM') as data_fechamento,
				tbl_posto.nome
		FROM tbl_os
		JOIN tbl_os_produto on tbl_os_produto.os = tbl_os.os
		join tbl_os_item on tbl_os_item.os_produto = tbl_os_produto.os_produto
		JOIN tbl_defeito on tbl_defeito.defeito = tbl_os_item.defeito
		JOIN tbl_os_extra on tbl_os.os=tbl_os_extra.os
		JOIN tbl_peca on tbl_peca.peca = tbl_os_item.peca
		Join tbl_produto on tbl_produto.produto = tbl_os.produto
		JOIN tbl_posto on tbl_os.posto = tbl_posto.posto
		JOIN tbl_servico_realizado ON tbl_os_item.servico_realizado=tbl_servico_realizado.servico_realizado
		WHERE tbl_os.fabrica =  $login_fabrica
		AND tbl_os.produto = $produto
		AND tbl_os.serie like '$serie%'
		AND tbl_os_item.peca = $peca
		AND tbl_os_item.defeito = $defeito
		$condConsumidorRevenda
		AND tbl_os.solucao_os <>127
		AND tbl_os_extra.extrato notnull
		AND tbl_servico_realizado.gera_pedido IS TRUE";
$res = pg_exec($con, $sql);
//echo "$sql";
if(pg_numrows($res)>0){

echo "<TABLE width='450' cellspacing='1' cellpadding='2' border='0' align = 'center' style='font-family: verdana; font-size: 10px'  bgcolor='#596D9B'>";
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
		$data_abertura  = pg_result($res,$x,data_abertura);
		$data_fechamento= pg_result($res,$x,data_fechamento);
		echo "<TR>";
		echo "<TD align='center' bgcolor='#FFFFFF'><a href='os_press.php?os=$os' target='blank'>$sua_os</a></TD>";
		echo "<TD align='center' bgcolor='#FFFFFF'>$data_abertura</TD>";
		echo "<TD align='center' bgcolor='#FFFFFF'>$data_fechamento</TD>";
		echo "<TD  bgcolor='#FFFFFF'>$nome</TD>";
		echo "</TR>";
	}
echo "</table>";
}

?>
