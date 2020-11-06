<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

include "cabecalho.php";
?>

<html>
<head>
<title>Lista de OS Não atendidas pela Fábrica</title>
</head>

<body>

<? include 'menu.php' ?>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	border: 0px solid;
	color:#ffffff;
	background-color: #596D9B
}

.link{
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: normal;
}

</style>
<?

//PERIODO DE ATENDIMENTO DO DISTRIB
$atend_periodo_ini = $_GET['atend_periodo_ini'];
$atend_periodo_fim = $_GET['atend_periodo_fim'];

//PERIODO DE EMISSAO DE  NOTA DA BRITANIA
$nf_periodo_ini = $_GET['nf_periodo_ini'];
$nf_periodo_fim = $_GET['nf_periodo_fim'];

if (strlen($atend_periodo_ini) == 0) {
	$atend_periodo_ini= 5;
}
if (strlen($atend_periodo_fim) == 0) {
	$atend_periodo_fim= 1;
}

if (strlen($nf_periodo_ini) == 0) {
	$sql="select (current_date - interval'5 day')::date as data";
	$res = pg_exec ($con,$sql);
	$nf_periodo_ini = trim(pg_result($res,0,data));
}else{
	$nf_periodo_ini = substr ($nf_periodo_ini,6,4) . "-" . substr ($nf_periodo_ini,3,2) . "-" . substr ($nf_periodo_ini,0,2) ;
}
if (strlen($nf_periodo_fim) == 0) {
	$nf_periodo_fim = date('Y-m-d');
}else{
	$nf_periodo_fim = substr ($nf_periodo_fim,6,4) . "-" . substr ($nf_periodo_fim,3,2) . "-" . substr ($nf_periodo_fim,0,2) ;
}

$sql = "SELECT
			tbl_embarque.embarque,
			tbl_embarque.posto,
			tbl_embarque_item.os_item,
			tbl_os_item.peca,
			tbl_os_item.pedido,
			tbl_os_produto.os
		FROM tbl_embarque_item
		JOIN tbl_embarque USING(embarque)
		JOIN tbl_faturamento           ON tbl_faturamento.embarque     = tbl_embarque.embarque AND tbl_faturamento.fabrica=3
		JOIN tbl_os_item               ON tbl_os_item.os_item          = tbl_embarque_item.os_item
		JOIN tbl_os_produto            ON tbl_os_produto.os_produto    = tbl_os_item.os_produto
		WHERE tbl_embarque.data < '$nf_periodo_fim'
		AND tbl_embarque.data > '$nf_periodo_ini'
		AND tbl_embarque_item.os_item IS NOT NULL
		";
$res = pg_exec ($con,$sql);

$nf_periodo_ini = substr($nf_periodo_ini,8,2)."/".substr($nf_periodo_ini,5,2)."/".substr($nf_periodo_ini,0,4) ;
$nf_periodo_fim = substr($nf_periodo_fim,8,2)."/".substr($nf_periodo_fim,5,2)."/".substr($nf_periodo_fim,0,4) ;

echo "<center><h1>Relatório de OS atendidas pelo Distribudor e não atendidas pela Fábrica</h1></center>";

echo "<table width='650' border='1' cellspacing='1' cellpadding='3' align='center'>\n";

echo "<form name='frm_per' method='get' action='$PHP_SELF'>";
echo "<tr><td colspan='10'>\n";
echo "Notas de Saída da Britania no período de <input type='text' name='nf_periodo_ini' id='nf_periodo_ini' size='12' maxlength='11' value='$nf_periodo_ini'>\n";	
echo " <input type='text' name='nf_periodo_fim' id='nf_periodo_fim' size='12' maxlength='10' value='$nf_periodo_fim'>\n";	
echo "<INPUT TYPE='submit' name='bt_per' id='bt_per' value='Pesquisar'>";
echo "</td></tr>\n";
echo "</form>\n";

echo "<tr>\n";
echo "<td class='menu_top' width='20'>#</td>\n";
echo "<td class='menu_top'>OS</td>\n";
echo "<td class='menu_top'>PEDIDO</td>\n";
echo "<td class='menu_top'>EMBARQUE</td>\n";
echo "</tr>\n";
		
if (pg_numrows($res) > 0) {

	$c=0;

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {

		$embarque		= trim(pg_result($res,$i,embarque));
		$posto			= trim(pg_result($res,$i,posto));
		$os_item		= trim(pg_result($res,$i,os_item)) ;
		$peca			= trim(pg_result($res,$i,peca)) ;
		$pedido			= trim(pg_result($res,$i,pedido)) ;
		$os				= trim(pg_result($res,$i,os));

		$sql = "SELECT faturamento
				FROM tbl_faturamento
				JOIN tbl_faturamento_item USING(faturamento)
				WHERE tbl_faturamento.fabrica IN (".implode(",", $fabricas).")
				AND   tbl_faturamento.posto   = $posto
				AND tbl_faturamento_item.os    = $os
				AND tbl_faturamento_item.peca  = $peca";
		$res2 = pg_exec ($con,$sql);

		$cor = "#ffffff";
		if ($c % 2 == 0) {
			$cor = "#DDDDEE";
		}

		if (pg_numrows($res2) == 0){
			$c++;

			$teste = 0 ;

			echo "<tr style='font-size: 10px' bgcolor='$cor'>\n";
			echo "<td align='center' nowrap>".($c+1)."&nbsp;</td>\n";
			echo "<td align='left'><a href='../os_press.php?os=$os' target='_blank' class='link'>$sua_os</a></td>\n";
			echo "<td align='center'>$pedido</td>\n";
			echo "<td align='center'>$embarque </td>\n";
			echo "</tr>\n";
			
		}
	}

}else{
	echo "<tr><td colspan='8' align='center'>NADA ENCONTRADO NO PERÍODO DE $nf_periodo_ini A $nf_periodo_fim</tr></td>";
}
	echo "</table>\n";
?>
</body>
<p>
<? include "rodape.php"; ?>