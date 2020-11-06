<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

	echo `rm /var/www/assist/www/download/relatorio-pedido-cancelado-consulta-lenoxx.html`;
	echo `rm /var/www/assist/www/download/relatorio-pedido-cancelado-consulta-lenoxx.xls`;
	echo `rm /var/www/assist/www/download/relatorio-pedido-cancelado-consulta-lenoxx.zip`;


$title = "Consulta de Pedidos cancelados pelo fabricante";

	$sql = "SELECT  tbl_posto_fabrica.codigo_posto||' - '||tbl_posto.nome as posto,
					tbl_pedido_cancelado.pedido                                   ,
					tbl_pedido.pedido_blackedecker                                ,
					tbl_pedido_cancelado.qtde                                     ,
					tbl_pedido_cancelado.os                                       ,
					tbl_os.sua_os                                                 ,
					TO_CHAR(tbl_pedido_cancelado.data,'DD/MM/YYYY') as datax      ,
					tbl_pedido_cancelado.data                                     ,
					tbl_peca.referencia||' - '||tbl_peca.descricao as peca
			FROM tbl_pedido_cancelado
			JOIN tbl_posto on tbl_pedido_cancelado.posto             = tbl_posto.posto
			JOIN tbl_posto_fabrica ON tbl_pedido_cancelado.posto     = tbl_posto_fabrica.posto
									AND tbl_pedido_cancelado.fabrica = tbl_posto_fabrica.fabrica
			JOIN tbl_peca on tbl_pedido_cancelado.peca               = tbl_peca.peca
			LEFT JOIN tbl_os ON tbl_pedido_cancelado.os              = tbl_os.os
			JOIN tbl_pedido ON tbl_pedido_cancelado.pedido           = tbl_pedido.pedido
			WHERE tbl_pedido_cancelado.fabrica                       = 11
			AND   tbl_pedido_cancelado.posto                        <> 6359
			AND tbl_pedido_cancelado.data BETWEEN current_timestamp - interval '15 day' AND current_timestamp 						
			ORDER BY tbl_pedido_cancelado.oid DESC";

	$res = pg_exec ($con,$sql);
	
	// ##### PAGINACAO ##### //
	if (@pg_numrows($res) > 0) {

		flush();
		
		echo `rm /var/www/assist/www/download/relatorio-pedido-cancelado-consulta-lenoxx.xls`;

		$fp = fopen ("/var/www/assist/www/download/relatorio-pedido-cancelado-consulta-lenoxx.html","w");

		fputs ($fp,"<html>");
		fputs ($fp,"<head>");
		fputs ($fp,"<title>RELATÓRIO DE PEDIDOS CANCELADOS - 15 dias atrás a ". date("d/m/Y"));
		fputs ($fp,"</title>");
		fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
		fputs ($fp,"</head>");
		fputs ($fp,"<body>");	
		fputs ($fp,"RELATÓRIO DE PEDIDOS CANCELADOS  - 15 dias atrás a ". date("d/m/Y")."<BR>");
		fputs ($fp,"<table width='800' border='1' cellspacing='0' cellpadding='0' align='center'>");
		fputs ($fp,"<tr height='20' bgcolor='#596D9B'>");
		fputs ($fp,"<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif' color=#FFFFFF><b>Posto</b></font></td>");
		fputs ($fp,"<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif' color=#FFFFFF><b>OS</b></font></td>");
		fputs ($fp,"<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif' color=#FFFFFF><b>Pedido</b></font></td>");
		fputs ($fp,"<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif' color=#FFFFFF><b>Ped. Fab.</b></font></td>");
		fputs ($fp,"<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif' color=#FFFFFF><b>Peça</b></font></td>");
		fputs ($fp,"<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif' color=#FFFFFF><b>Qtde</b></font></td>");
		fputs ($fp,"<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif' color=#FFFFFF><b>Dt. Cancelada</b></font></td>");
		fputs ($fp,"</tr>");
		
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {

			$posto             = substr(pg_result($res,$i,posto),0,30);
			$pedido            = trim(pg_result($res,$i,pedido));
			$pedido_fabricante = trim(pg_result($res,$i,pedido_blackedecker));
			$qtde              = trim(pg_result($res,$i,qtde));
			$os                = trim(pg_result($res,$i,os));
			$sua_os            = trim(pg_result($res,$i,sua_os));
			$data              = trim(pg_result($res,$i,datax));
			$peca              = substr(trim(pg_result($res,$i,peca)),0,40);
			
			fputs ($fp,"<tr >");
			fputs ($fp,"<td align='left' nowrap><font size='1' face='Geneva, Arial, Helvetica, san-serif'>$posto</font></td>");

			if (strlen($sua_os) > 0) {
				fputs ($fp,"<td align='right'><font size='2' face='Geneva, Arial, Helvetica, san-serif'>$sua_os</a></font></td>");
			} else {
				fputs ($fp,"<td align='right'><font size='2' face='Geneva, Arial, Helvetica, san-serif'>&nbsp;</font></td>");
			}
			
			fputs ($fp,"<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'>$pedido</a></font></td>");
			fputs ($fp,"<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'>$pedido_fabricante</font></td>");
			fputs ($fp,"<td align='left' nowrap><font size='1' face='Geneva, Arial, Helvetica, san-serif'>$peca</font></td>");
			fputs ($fp,"<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'>$qtde</font></td>");
			fputs ($fp,"<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'>$data</font></td>");
			fputs ($fp,"</tr>");
		}
		fputs ($fp,"</table>");
		fclose ($fp);		
	

		
	//gera o xls
	echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f /var/www/assist/www/download/relatorio-pedido-cancelado-consulta-lenoxx.xls /var/www/assist/www/download/relatorio-pedido-cancelado-consulta-lenoxx.html`;

	//gera o zip
	echo `cd /var/www/assist/www/download/; rm -rf relatorio-pedido-cancelado-consulta-lenoxx.zip; zip -o relatorio-pedido-cancelado-consulta-lenoxx.zip relatorio-pedido-cancelado-consulta-lenoxx.xls > /dev/null`;

}
?>