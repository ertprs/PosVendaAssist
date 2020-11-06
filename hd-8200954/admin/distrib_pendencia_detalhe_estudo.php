<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$linha=$_GET['linha'];
$peca=$_GET['peca'];

$aux_data_corte_garantia_posto = $_GET['aux_data_corte_garantia_posto'];
$aux_data_corte_faturada_posto = $_GET['aux_data_corte_faturada_posto'];
$aux_data_corte_garantia_distrib = $_GET['aux_data_corte_garantia_distrib'];
$aux_data_corte_faturada_distrib = $_GET['aux_data_corte_faturada_distrib'];

# HD 132147
$tipo = $_GET['tipo'];
$cond = "";

if( $tipo == 'garantia'){
	if ($login_fabrica == 51) {//HD 132147
		$cond = " AND tbl_pedido.tipo_pedido = 132 ";
	} else if ($login_fabrica == 81) {//HD 215186
		$cond = " AND tbl_pedido.tipo_pedido = 154 ";
	}
} elseif($tipo == 'faturado'){
	if ($login_fabrica == 51) {//HD 132147
		$cond = " AND tbl_pedido.tipo_pedido = 131 ";
	} else if ($login_fabrica == 81) {//HD 215186
		$cond = " AND tbl_pedido.tipo_pedido = 153 ";
	}
} else {
	$cond = "";
}
# Tipo primeiro GARANTIA
$sql = "SELECT tbl_posto.nome                               ,
			TO_CHAR (tbl_pedido.data,'DD-MM-YYYY') AS data  ,
			tbl_pedido.pedido                               ,
			tbl_tipo_pedido.descricao AS tipo_pedido        ,
			tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada - tbl_pedido_item.qtde_faturada_distribuidor AS qtde ,
			(SELECT tbl_os.sua_os FROM tbl_os JOIN tbl_os_produto USING (os) JOIN tbl_os_item USING (os_produto) WHERE tbl_os_item.pedido_item = tbl_pedido_item.pedido_item AND tbl_os_item.peca = $peca) AS sua_os ,
			(SELECT tbl_os.os     FROM tbl_os JOIN tbl_os_produto USING (os) JOIN tbl_os_item USING (os_produto) WHERE tbl_os_item.pedido_item = tbl_pedido_item.pedido_item AND tbl_os_item.peca = $peca) AS os
		FROM tbl_pedido
		JOIN tbl_pedido_item USING (pedido)
		JOIN tbl_posto ON tbl_pedido.posto = tbl_posto.posto
		JOIN tbl_tipo_pedido ON tbl_pedido.tipo_pedido = tbl_tipo_pedido.tipo_pedido
		WHERE tbl_pedido_item.peca = $peca
		AND   tbl_pedido.fabrica = $login_fabrica
		AND   tbl_pedido.distribuidor = 4311 
		AND   tbl_pedido.data > '$aux_data_corte_garantia_posto 23:59:59' ";
	if ($login_fabrica == 51) {
		$sql .= " AND tbl_pedido.tipo_pedido = 132 ";
	}
	if ($login_fabrica == 81) {
		$sql .= " AND tbl_pedido.tipo_pedido = 154 ";
	}
	$sql .= " /* AND   tbl_pedido.status_pedido <> 13 */
		AND   tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada - tbl_pedido_item.qtde_faturada_distribuidor > 0
		ORDER BY tbl_pedido.data";
$res = pg_query ($con,$sql);

echo "$linha|";

if(pg_num_rows ($res)>0) {
	flush();
	$data = date ("d/m/Y H:i:s");

	$arquivo_nome     = "relatorio-pendencia-peca-$login_admin-$peca.xls";
	$path             = "/www/assist/www/admin/xls/";
	$path_tmp         = "/tmp/";

	$arquivo_completo     = $path.$arquivo_nome;
	$arquivo_completo_tmp = $path_tmp.$arquivo_nome;

	echo `rm $arquivo_completo_tmp `;
	echo `rm $arquivo_completo `;

	$fp = fopen ($arquivo_completo_tmp,"w");

	fputs ($fp,"<html>");
	fputs ($fp,"<head>");
	fputs ($fp,"<title>RELATÓRIO PENDENCIA DO POSTO DE PEÇA EM GARANTIA - $data");
	fputs ($fp,"</title>");
	fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
	fputs ($fp,"</head>");
	fputs ($fp,"<body>");
	fputs ($fp, "<table border=1 cellspacing=0 style='text-align:center'>");
	fputs ($fp, "<thead>");
	fputs ($fp, "<tr bgcolor='#3366FF' style='font-weight:bold;'>");
	fputs ($fp, "<td>Posto</td>");
	fputs ($fp, "<td>Pedido</td>");
	fputs ($fp, "<td>Tipo</td>");
	fputs ($fp, "<td>O.S.</td>");
	fputs ($fp, "<td>Data</td>");
	fputs ($fp, "<td>Qtde</td>");
	fputs ($fp, "</tr>");
	fputs ($fp, "</thead>");
	fputs ($fp,"<tbody>");

	$resultados = pg_fetch_all($res);
	foreach ($resultados as $resultado){
		fputs ($fp, "<tr bgcolor='#FFFFFF'>");

		fputs ($fp, "<td>".$resultado['nome']."</td >");
		fputs ($fp, "<td>".$resultado['pedido']."</td >");
		fputs ($fp, "<td>".$resultado['tipo_pedido']."</td >");

		fputs ($fp, "<td>");
		$sua_os = $resultado['sua_os'];
		$os     = $resultado['os'];
		if (strlen ($sua_os) == 0) {
			fputs ($fp, "&nbsp;");
		}else{
			fputs ($fp, "<font color=blue><u>");
			fputs ($fp, $sua_os);
			fputs ($fp, "</u></font>");
		}
		fputs ($fp, "</td >");

		fputs ($fp, "<td>".$resultado['data']."</td >");
		fputs ($fp, "<td>".$resultado['qtde']."</td >");

		fputs ($fp, "</tr>");
	}
	fputs ($fp,"</tbody>");
	fputs ($fp, " </table>");
	fputs ($fp, " </body>");
	fputs ($fp, " </html>");

	echo ` cp $arquivo_completo_tmp $path `;

	echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f $arquivo_completo $arquivo_completo_tmp `;
	echo '<br>';
	echo "<a href='xls/$arquivo_nome' target='_blank'>Arquivo<br><img src='imagens/excel.png' width='40' border='0' style='float:left'></a>";
	echo '<br>';
	flush();

	echo "<table border=1 cellspacing=0 align=center>";
	echo "<tr bgcolor=\"#3366FF\">";
	echo "<td colspan='6'><b>RELATÓRIO PENDENCIA DO POSTO DE PEÇA EM GARANTIA - $data</b></td>";
	echo "</tr>";
	
	echo "<tr bgcolor=\"#3366FF\">";
	echo "<td><b>Posto</b></td>";
	echo "<td><b>Pedido</b></td>";
	echo "<td><b>Tipo</b></td>";
	echo "<td><b>O.S.</b></td>";
	echo "<td><b>Data</b></td>";
	echo "<td><b>Qtde</b></td>";
	echo "</tr>";
	for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
		echo "<tr bgcolor='#99ddff'>";

		echo "<td>";
		echo pg_fetch_result ($res,$i,nome);
		echo "</td >";

		echo "<td>";
		echo pg_fetch_result ($res,$i,pedido);
		echo "</td >";

		echo "<td>";
		echo pg_fetch_result ($res,$i,tipo_pedido);
		echo "</td >";

		echo "<td>";
		$sua_os = pg_fetch_result ($res,$i,sua_os);
		$os     = pg_fetch_result ($res,$i,os);
		if (strlen ($sua_os) == 0) {
			echo "&nbsp;";
		}else{
			echo "<a href=os_press.php?os=$os target=_blank><font color=blue><u>";
			echo $sua_os;
			echo "</u></font></a>";
		}
		echo "</td >";

		echo "<td>";
		echo pg_fetch_result ($res,$i,data);
		echo "</td>";

		echo "<td align=right>";
		echo pg_fetch_result ($res,$i,qtde);
		echo "</td>";
		echo "</tr>";
	}
	echo "</table>";
}else{
	echo "<center>Sem Pendência do Posto de Peça em GARANTIA<center>";
}
# Tipo Faturado
$sql = "SELECT tbl_posto.nome                               ,
			TO_CHAR (tbl_pedido.data,'DD-MM-YYYY') AS data  ,
			tbl_pedido.pedido                               ,
			tbl_tipo_pedido.descricao AS tipo_pedido        ,
			tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada - tbl_pedido_item.qtde_faturada_distribuidor AS qtde ,
			(SELECT tbl_os.sua_os FROM tbl_os JOIN tbl_os_produto USING (os) JOIN tbl_os_item USING (os_produto) WHERE tbl_os_item.pedido_item = tbl_pedido_item.pedido_item AND tbl_os_item.peca = $peca) AS sua_os ,
			(SELECT tbl_os.os     FROM tbl_os JOIN tbl_os_produto USING (os) JOIN tbl_os_item USING (os_produto) WHERE tbl_os_item.pedido_item = tbl_pedido_item.pedido_item AND tbl_os_item.peca = $peca) AS os
		FROM tbl_pedido
		JOIN tbl_pedido_item USING (pedido)
		JOIN tbl_posto ON tbl_pedido.posto = tbl_posto.posto
		JOIN tbl_tipo_pedido ON tbl_pedido.tipo_pedido = tbl_tipo_pedido.tipo_pedido
		WHERE tbl_pedido_item.peca = $peca
		AND   tbl_pedido.fabrica = $login_fabrica
		AND   tbl_pedido.distribuidor = 4311 
		AND   tbl_pedido.data > '$aux_data_corte_faturada_posto 23:59:59' ";
	if ($login_fabrica == 51) {
		$sql .= " AND tbl_pedido.tipo_pedido = 131 ";
	}
	if ($login_fabrica == 81) {
		$sql .= " AND tbl_pedido.tipo_pedido = 153 ";
	}
	$sql .= " /* AND   tbl_pedido.status_pedido <> 13 */
		AND   tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada - tbl_pedido_item.qtde_faturada_distribuidor > 0
		ORDER BY tbl_pedido.data";
$res = pg_query ($con,$sql);

echo "<br>";

if(pg_num_rows ($res)>0) {
	flush();
	$data = date ("d/m/Y H:i:s");

	$arquivo_nome     = "relatorio-pendencia-peca-$login_admin-$peca.xls";
	$path             = "/www/assist/www/admin/xls/";
	$path_tmp         = "/tmp/";

	$arquivo_completo     = $path.$arquivo_nome;
	$arquivo_completo_tmp = $path_tmp.$arquivo_nome;

	echo `rm $arquivo_completo_tmp `;
	echo `rm $arquivo_completo `;

	$fp = fopen ($arquivo_completo_tmp,"w");

	fputs ($fp,"<html>");
	fputs ($fp,"<head>");
	fputs ($fp,"<title>RELATÓRIO PENDENCIA DO POSTO DE PEÇA FATURADA - $data");
	fputs ($fp,"</title>");
	fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
	fputs ($fp,"</head>");
	fputs ($fp,"<body>");
	fputs ($fp, "<table border=1 cellspacing=0 style='text-align:center'>");
	fputs ($fp, "<thead>");
	fputs ($fp, "<tr bgcolor='#3366FF' style='font-weight:bold;'>");
	fputs ($fp, "<td>Posto</td>");
	fputs ($fp, "<td>Pedido</td>");
	fputs ($fp, "<td>Tipo</td>");
	fputs ($fp, "<td>Data</td>");
	fputs ($fp, "<td>Qtde</td>");
	fputs ($fp, "</tr>");
	fputs ($fp, "</thead>");
	fputs ($fp,"<tbody>");

	$resultados = pg_fetch_all($res);
	foreach ($resultados as $resultado){
		fputs ($fp, "<tr bgcolor='#FFFFFF'>");

		fputs ($fp, "<td>".$resultado['nome']."</td >");
		fputs ($fp, "<td>".$resultado['pedido']."</td >");
		fputs ($fp, "<td>".$resultado['tipo_pedido']."</td >");
		fputs ($fp, "<td>".$resultado['data']."</td >");
		fputs ($fp, "<td>".$resultado['qtde']."</td >");

		fputs ($fp, "</tr>");
	}
	fputs ($fp,"</tbody>");
	fputs ($fp, " </table>");
	fputs ($fp, " </body>");
	fputs ($fp, " </html>");

	echo ` cp $arquivo_completo_tmp $path `;

	echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f $arquivo_completo $arquivo_completo_tmp `;
	echo '<br>';
	echo "<a href='xls/$arquivo_nome' target='_blank'>Arquivo<br><img src='imagens/excel.png' width='40' border='0' style='float:left'></a>";
	echo '<br>';
	flush();

	echo "<table border=1 cellspacing=0 align=center>";
	echo "<tr bgcolor=\"#3366FF\">";
	echo "<td colspan='5'><b>RELATÓRIO PENDENCIA DO POSTO DE PEÇA FATURADO - $data</b></td>";
	echo "</tr>";

	echo "<tr bgcolor=\"#3366FF\">";
	echo "<td><b>Posto</b></td>";
	echo "<td><b>Pedido</b></td>";
	echo "<td><b>Tipo</b></td>";
	echo "<td><b>Data</b></td>";
	echo "<td><b>Qtde</b></td>";
	echo "</tr>";
	for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
		echo "<tr bgcolor='#99ddff'>";

		echo "<td>";
		echo pg_fetch_result ($res,$i,nome);
		echo "</td >";

		echo "<td>";
		echo pg_fetch_result ($res,$i,pedido);
		echo "</td >";

		echo "<td>";
		echo pg_fetch_result ($res,$i,tipo_pedido);
		echo "</td >";

		echo "<td>";
		echo pg_fetch_result ($res,$i,data);
		echo "</td>";

		echo "<td align=right>";
		echo pg_fetch_result ($res,$i,qtde);
		echo "</td>";
		echo "</tr>";
	}
	echo "</table>";
}else{
	echo "<center>Sem Pendência do Posto de Peça FATURADA<center>";
}








# Tipo primeiro GARANTIA
$sql = "SELECT tbl_posto.nome                               ,
			TO_CHAR (tbl_pedido.data,'DD-MM-YYYY') AS data  ,
			tbl_pedido.pedido                               ,
			tbl_tipo_pedido.descricao AS tipo_pedido        ,
			tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada - tbl_pedido_item.qtde_faturada AS qtde ,
			(SELECT tbl_os.sua_os FROM tbl_os JOIN tbl_os_produto USING (os) JOIN tbl_os_item USING (os_produto) WHERE tbl_os_item.pedido_item = tbl_pedido_item.pedido_item AND tbl_os_item.peca = $peca) AS sua_os ,
			(SELECT tbl_os.os     FROM tbl_os JOIN tbl_os_produto USING (os) JOIN tbl_os_item USING (os_produto) WHERE tbl_os_item.pedido_item = tbl_pedido_item.pedido_item AND tbl_os_item.peca = $peca) AS os
		FROM tbl_pedido
		JOIN tbl_pedido_item USING (pedido)
		JOIN tbl_posto ON tbl_pedido.posto = tbl_posto.posto
		JOIN tbl_tipo_pedido ON tbl_pedido.tipo_pedido = tbl_tipo_pedido.tipo_pedido
		WHERE tbl_pedido_item.peca = $peca
		AND   tbl_pedido.fabrica = $login_fabrica
		AND   tbl_pedido.posto in (4311,20682) 
		AND   tbl_pedido.data > '$aux_data_corte_garantia_distrib 23:59:59' ";
	if ($login_fabrica == 51) {
		$sql .= " AND tbl_pedido.tipo_pedido = 132 ";
	}
	if ($login_fabrica == 81) {
		$sql .= " AND tbl_pedido.tipo_pedido = 154 ";
	}
	$sql .= " /* AND   tbl_pedido.status_pedido <> 13 */
		AND   tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada - tbl_pedido_item.qtde_faturada > 0
		ORDER BY tbl_pedido.data";
$res = pg_query ($con,$sql);

echo "<br>";

if(pg_num_rows ($res)>0) {
	flush();
	$data = date ("d/m/Y H:i:s");

	$arquivo_nome     = "relatorio-pendencia-peca-$login_admin-$peca.xls";
	$path             = "/www/assist/www/admin/xls/";
	$path_tmp         = "/tmp/";

	$arquivo_completo     = $path.$arquivo_nome;
	$arquivo_completo_tmp = $path_tmp.$arquivo_nome;

	echo `rm $arquivo_completo_tmp `;
	echo `rm $arquivo_completo `;

	$fp = fopen ($arquivo_completo_tmp,"w");

	fputs ($fp,"<html>");
	fputs ($fp,"<head>");
	fputs ($fp,"<title>RELATÓRIO PENDENCIA DO DISTRIB DE PEÇA EM GARANTIA - $data");
	fputs ($fp,"</title>");
	fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
	fputs ($fp,"</head>");
	fputs ($fp,"<body>");
	fputs ($fp, "<table border=1 cellspacing=0 style='text-align:center'>");
	fputs ($fp, "<thead>");
	fputs ($fp, "<tr bgcolor='#3366FF' style='font-weight:bold;'>");
	fputs ($fp, "<td>Posto</td>");
	fputs ($fp, "<td>Pedido</td>");
	fputs ($fp, "<td>Tipo</td>");
	fputs ($fp, "<td>O.S.</td>");
	fputs ($fp, "<td>Data</td>");
	fputs ($fp, "<td>Qtde</td>");
	fputs ($fp, "</tr>");
	fputs ($fp, "</thead>");
	fputs ($fp,"<tbody>");

	$resultados = pg_fetch_all($res);
	foreach ($resultados as $resultado){
		fputs ($fp, "<tr bgcolor='#FFFFFF'>");

		fputs ($fp, "<td>".$resultado['nome']."</td >");
		fputs ($fp, "<td>".$resultado['pedido']."</td >");
		fputs ($fp, "<td>".$resultado['tipo_pedido']."</td >");

		fputs ($fp, "<td>");
		$sua_os = $resultado['sua_os'];
		$os     = $resultado['os'];
		if (strlen ($sua_os) == 0) {
			fputs ($fp, "&nbsp;");
		}else{
			fputs ($fp, "<font color=blue><u>");
			fputs ($fp, $sua_os);
			fputs ($fp, "</u></font>");
		}
		fputs ($fp, "</td >");

		fputs ($fp, "<td>".$resultado['data']."</td >");
		fputs ($fp, "<td>".$resultado['qtde']."</td >");

		fputs ($fp, "</tr>");
	}
	fputs ($fp,"</tbody>");
	fputs ($fp, " </table>");
	fputs ($fp, " </body>");
	fputs ($fp, " </html>");

	echo ` cp $arquivo_completo_tmp $path `;

	echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f $arquivo_completo $arquivo_completo_tmp `;
	echo '<br>';
	echo "<a href='xls/$arquivo_nome' target='_blank'>Arquivo<br><img src='imagens/excel.png' width='40' border='0' style='float:left'></a>";
	echo '<br>';
	flush();

	echo "<table border=1 cellspacing=0 align=center>";
	echo "<tr bgcolor=\"#3366FF\">";
	echo "<td colspan='6'><b>RELATÓRIO PENDENCIA DO DISTRIB DE PEÇA EM GARANTIA - $data</b></td>";
	echo "</tr>";
	
	echo "<tr bgcolor=\"#3366FF\">";
	echo "<td><b>Posto</b></td>";
	echo "<td><b>Pedido</b></td>";
	echo "<td><b>Tipo</b></td>";
	echo "<td><b>O.S.</b></td>";
	echo "<td><b>Data</b></td>";
	echo "<td><b>Qtde</b></td>";
	echo "</tr>";
	for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
		echo "<tr bgcolor='#99ddff'>";

		echo "<td>";
		echo pg_fetch_result ($res,$i,nome);
		echo "</td >";

		echo "<td>";
		echo pg_fetch_result ($res,$i,pedido);
		echo "</td >";

		echo "<td>";
		echo pg_fetch_result ($res,$i,tipo_pedido);
		echo "</td >";

		echo "<td>";
		$sua_os = pg_fetch_result ($res,$i,sua_os);
		$os     = pg_fetch_result ($res,$i,os);
		if (strlen ($sua_os) == 0) {
			echo "&nbsp;";
		}else{
			echo "<a href=os_press.php?os=$os target=_blank><font color=blue><u>";
			echo $sua_os;
			echo "</u></font></a>";
		}
		echo "</td >";

		echo "<td>";
		echo pg_fetch_result ($res,$i,data);
		echo "</td>";

		echo "<td align=right>";
		echo pg_fetch_result ($res,$i,qtde);
		echo "</td>";
		echo "</tr>";
	}
	echo "</table>";
}else{
	echo "<center>Sem Pendência do DISTRIB de Peça em Garantia<center>";
}
# Tipo Faturado
$sql = "SELECT tbl_posto.nome                               ,
			TO_CHAR (tbl_pedido.data,'DD-MM-YYYY') AS data  ,
			tbl_pedido.pedido                               ,
			tbl_tipo_pedido.descricao AS tipo_pedido        ,
			tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada - tbl_pedido_item.qtde_faturada AS qtde ,
			(SELECT tbl_os.sua_os FROM tbl_os JOIN tbl_os_produto USING (os) JOIN tbl_os_item USING (os_produto) WHERE tbl_os_item.pedido_item = tbl_pedido_item.pedido_item AND tbl_os_item.peca = $peca) AS sua_os ,
			(SELECT tbl_os.os     FROM tbl_os JOIN tbl_os_produto USING (os) JOIN tbl_os_item USING (os_produto) WHERE tbl_os_item.pedido_item = tbl_pedido_item.pedido_item AND tbl_os_item.peca = $peca) AS os
		FROM tbl_pedido
		JOIN tbl_pedido_item USING (pedido)
		JOIN tbl_posto ON tbl_pedido.posto = tbl_posto.posto
		JOIN tbl_tipo_pedido ON tbl_pedido.tipo_pedido = tbl_tipo_pedido.tipo_pedido
		WHERE tbl_pedido_item.peca = $peca
		AND   tbl_pedido.fabrica = $login_fabrica
		AND   tbl_pedido.posto in (4311,20682) 
		AND   tbl_pedido.data > '$aux_data_corte_faturada_distrib 23:59:59' ";
	if ($login_fabrica == 51) {
		$sql .= " AND tbl_pedido.tipo_pedido = 131 ";
	}
	if ($login_fabrica == 81) {
		$sql .= " AND tbl_pedido.tipo_pedido = 153 ";
	}
	$sql .= " /* AND   tbl_pedido.status_pedido <> 13 */
		AND   tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada - tbl_pedido_item.qtde_faturada > 0
		ORDER BY tbl_pedido.data";
$res = pg_query ($con,$sql);

echo "<br>";

if(pg_num_rows ($res)>0) {
	flush();
	$data = date ("d/m/Y H:i:s");

	$arquivo_nome     = "relatorio-pendencia-peca-$login_admin-$peca.xls";
	$path             = "/www/assist/www/admin/xls/";
	$path_tmp         = "/tmp/";

	$arquivo_completo     = $path.$arquivo_nome;
	$arquivo_completo_tmp = $path_tmp.$arquivo_nome;

	echo `rm $arquivo_completo_tmp `;
	echo `rm $arquivo_completo `;

	$fp = fopen ($arquivo_completo_tmp,"w");

	fputs ($fp,"<html>");
	fputs ($fp,"<head>");
	fputs ($fp,"<title>RELATÓRIO PENDENCIA DO DISTRIB DE PEÇA FATURADA - $data");
	fputs ($fp,"</title>");
	fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
	fputs ($fp,"</head>");
	fputs ($fp,"<body>");
	fputs ($fp, "<table border=1 cellspacing=0 style='text-align:center'>");
	fputs ($fp, "<thead>");
	fputs ($fp, "<tr bgcolor='#3366FF' style='font-weight:bold;'>");
	fputs ($fp, "<td>Posto</td>");
	fputs ($fp, "<td>Pedido</td>");
	fputs ($fp, "<td>Tipo</td>");
	fputs ($fp, "<td>Data</td>");
	fputs ($fp, "<td>Qtde</td>");
	fputs ($fp, "</tr>");
	fputs ($fp, "</thead>");
	fputs ($fp,"<tbody>");

	$resultados = pg_fetch_all($res);
	foreach ($resultados as $resultado){
		fputs ($fp, "<tr bgcolor='#FFFFFF'>");

		fputs ($fp, "<td>".$resultado['nome']."</td >");
		fputs ($fp, "<td>".$resultado['pedido']."</td >");
		fputs ($fp, "<td>".$resultado['tipo_pedido']."</td >");
		fputs ($fp, "<td>".$resultado['data']."</td >");
		fputs ($fp, "<td>".$resultado['qtde']."</td >");

		fputs ($fp, "</tr>");
	}
	fputs ($fp,"</tbody>");
	fputs ($fp, " </table>");
	fputs ($fp, " </body>");
	fputs ($fp, " </html>");

	echo ` cp $arquivo_completo_tmp $path `;

	echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f $arquivo_completo $arquivo_completo_tmp `;
	echo '<br>';
	echo "<a href='xls/$arquivo_nome' target='_blank'>Arquivo<br><img src='imagens/excel.png' width='40' border='0' style='float:left'></a>";
	echo '<br>';
	flush();

	echo "<table border=1 cellspacing=0 align=center>";
	echo "<tr bgcolor=\"#3366FF\">";
	echo "<td colspan='5'><b>RELATÓRIO PENDENCIA DO DISTRIB DE PEÇA FATURADA - $data</b></td>";
	echo "</tr>";

	echo "<tr bgcolor=\"#3366FF\">";
	echo "<td><b>Posto</b></td>";
	echo "<td><b>Pedido</b></td>";
	echo "<td><b>Tipo</b></td>";
	echo "<td><b>Data</b></td>";
	echo "<td><b>Qtde</b></td>";
	echo "</tr>";
	for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
		echo "<tr bgcolor='#99ddff'>";

		echo "<td>";
		echo pg_fetch_result ($res,$i,nome);
		echo "</td >";

		echo "<td>";
		echo pg_fetch_result ($res,$i,pedido);
		echo "</td >";

		echo "<td>";
		echo pg_fetch_result ($res,$i,tipo_pedido);
		echo "</td >";

		echo "<td>";
		echo pg_fetch_result ($res,$i,data);
		echo "</td>";

		echo "<td align=right>";
		echo pg_fetch_result ($res,$i,qtde);
		echo "</td>";
		echo "</tr>";
	}
	echo "</table>";
}else{
	echo "<center>Sem Pendência do DISTRIB de Peça FATURADA<center>";
}

?>
