<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="auditoria";
include 'autentica_admin.php';

$data_inicial = trim($_GET["data_inicial"]);
$data_final   = trim($_GET["data_final"]);
$peca        = trim($_GET["peca"]);

$layout_menu = "auditoria";
$title = "Auditoria -  Peças Pendentes por Estoque";

include "cabecalho.php";

?>

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

<?
if (strlen($data_final) > 0 AND strlen($data_inicial) > 0) {
	$cond_1 = " 1=1 ";
	if (strlen($peca) > 0){ 
		$cond_1 = " tbl_pedido_item.peca = $peca ";
	}

	$sql = "select tbl_peca.referencia,
				tbl_peca.descricao,
				tbl_peca.peca,
				count(tbl_pedido_item.peca) as qtde
			from tbl_pedido_item
			JOIN tbl_peca on tbl_pedido_item.peca = tbl_peca.peca
			JOIN tbl_pedido on tbl_pedido.pedido = tbl_pedido_item.pedido
			where tbl_pedido.pedido_blackedecker NOTNULL
			and tbl_pedido.data > '2007-01-01 00:00:00'
			and $cond_1
			AND tbl_pedido.data between '$data_inicial 00:00:00' and '$data_final 23:59:59'
			AND tbl_pedido_item.qtde_faturada + tbl_pedido_item.qtde_cancelada < tbl_pedido_item.qtde 
			AND tbl_pedido.fabrica = $login_fabrica
			GROUP BY
			tbl_peca.referencia,
			tbl_peca.descricao,tbl_peca.peca
			order by tbl_peca.referencia";
	$res = pg_exec ($con,$sql);
//echo $sql;
	if (pg_numrows($res) > 0) {
		flush();
		
		$data = date ("d/m/Y H:i:s");

		echo `rm /tmp/assist/auditoria_pecas_pendentes_pecas-$login_fabrica.xls`;

		$fp = fopen ("/tmp/assist/auditoria_pecas_pendentes_pecas-$login_fabrica.html","w");

		fputs ($fp,"<html>");
		fputs ($fp,"<head>");
		fputs ($fp,"<title>FIELD CALL-RATE - $data");
		fputs ($fp,"</title>");
		fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
		fputs ($fp,"</head>");
		fputs ($fp,"<body>");
		

		fputs ($fp,"<table border='1' cellpadding='0' cellspacing='0'>");
	
		fputs ($fp,"<tr class='Titulo'>");
		fputs ($fp,"<td >Código</td>");
		fputs ($fp,"<td >Descrição</td>");
		fputs ($fp,"<td >Qtde</td>");
		fputs ($fp,"</tr>");
	
		$total = pg_numrows($res);

		for ($i=0; $i<pg_numrows($res); $i++){
	
			$referencia          = trim(pg_result($res,$i,referencia));
			$descricao           = trim(pg_result($res,$i,descricao));
			$peca           = trim(pg_result($res,$i,peca));
			$qtde                = trim(pg_result($res,$i,qtde));
			$total_total = $total_total + $qtde;
			if($cor=="#F1F4FA")$cor = '#F7F5F0';
			else               $cor = '#F1F4FA';
			
			fputs ($fp,"<tr class='Conteudo'align='center'>");
			fputs ($fp,"<td bgcolor='$cor' align='center' nowrap>$referencia</td>");
			fputs ($fp,"<td bgcolor='$cor' align='left' nowrap>$descricao</td>");
			fputs ($fp,"<td bgcolor='$cor' nowrap>$qtde</td>");
			fputs ($fp,"</tr>");
		}
		fputs ($fp,"<tr class='Conteudo'align='center'>");
		fputs ($fp,"<td bgcolor='$cor' align='center' nowrap colspan='2'>Total</td>");
		fputs ($fp,"<td bgcolor='$cor' nowrap>$total_total</td>");
		fputs ($fp,"</tr>");
		fputs ($fp,"</table>");
	}else{
		echo "<br><center>Nenhum resultado encontrado</center>";
	}
	fputs ($fp,"</body>");
	fputs ($fp,"</html>");
	fclose ($fp);
	$data = date("Y-m-d").".".date("H-i-s");

	echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f /www/assist/www/admin/xls/auditoria_pecas_pendentes_pecas-$login_fabrica.$data.xls /tmp/assist/auditoria_pecas_pendentes_pecas-$login_fabrica.html`;
	
	echo"<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
	echo"<tr>";
	echo "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font><a href='xls/auditoria_pecas_pendentes_pecas-$login_fabrica.$data.xls'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em EXCEL</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</font></td>";
	echo "</tr>";
	echo "</table>";
}

?>

<p>

<? include "rodape.php" ?>
