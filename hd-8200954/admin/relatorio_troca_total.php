<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';

include 'cabecalho.php';

?>

<link rel="stylesheet" href="js/blue/style.css" type="text/css" id="" media="print, projection, screen" />
<script type="text/javascript" src="../js/jquery.js"></script>
<script type='text/javascript' src='../helpdesk/js_admin/dimensions.js'></script>
<script type="text/javascript" src="../helpdesk/js_admin/jquery.tablesorter.js"></script>
<script type="text/javascript" src="../helpdesk/js_admin/jquery.tablesorter.pager.js"></script>

<script language="JavaScript">
$(function() {
	// add new widget called repeatHeaders
	$.tablesorter.addWidget({
		// give the widget a id
		id: "repeatHeaders",
		// format is called when the on init and when a sorting has finished
		format: function(table) {
			// cache and collect all TH headers
			if(!this.headers) {
				var h = this.headers = [];
				$("thead th",table).each(function() {
					h.push(
						"<th>" + $(this).text() + "</th>"
					);

				});
			}

			// remove appended headers by classname.
			$("tr.repated-header",table).remove();

			// loop all tr elements and insert a copy of the "headers"
			for(var i=0; i < table.tBodies[0].rows.length; i++) {
				// insert a copy of the table head every 10th row
				if((i%20) == 0) {
					if(i!=0){
					$("tbody tr:eq(" + i + ")",table).before(
						$("<tr></tr>").addClass("repated-header").html(this.headers.join(""))

					);
				}}
			}

		}
	});
	$("table.tablesorter").tablesorter({
		widgets: ['zebra','repeatHeaders']
	});

});
</script>

<?

$sql = "
SELECT DISTINCT
tbl_os_troca.os

FROM
tbl_os_troca
JOIN tbl_os ON tbl_os_troca.os=tbl_os.os

WHERE
tbl_os_troca.fabric=$login_fabrica
AND tbl_os.fabrica=$login_fabrica
AND tbl_os.excluida IS NOT TRUE

ORDER BY
os
";
$res = pg_query($con, $sql);


echo "
<table class='tablesorter'>
<thead>
	<tr>
		<th width=30>OS</th>
		<th>Ocorrências</th>
		<th>Nota Fiscal</th>
	</tr>
</thead>
<tbody>";

for($i = 0; $i < pg_num_rows($res); $i++) {
	$os = pg_result($res, $i, os);

	/**************** NOTAS ****************/

	$sql = "
	SELECT
	nota_fiscal

	FROM
	tbl_faturamento_item
	JOIN tbl_faturamento ON tbl_faturamento_item.faturamento=tbl_faturamento.faturamento

	WHERE
	tbl_faturamento_item.os=$os
	";
	$res_faturamento = pg_query($con, $sql);

	$notas = array();
	for($f = 0; $f < pg_num_rows($res_faturamento); $f++) {
		$notas[] = pg_result($res_faturamento, $f, nota_fiscal);
	}
	$notas = implode("<br>", $notas);

	/**************** TROCAS ****************/

	$sql = "
	SELECT
	os_troca,
	ressarcimento,
	pedido,
	observacao

	FROM
	tbl_os_troca

	WHERE
	os=$os

	ORDER BY
	os_troca
	";
	$res_troca = pg_query($con, $sql);
	
	$troca = array();
	for($t = 0; $t < pg_num_rows($res_troca); $t++) {
		$os_troca = pg_result($res_troca, $t, os_troca);
		$pedido = pg_result($res_troca, $t, pedido);
		$observacao = pg_result($res_troca, $t, observacao);

		if ($pedido) {
			$pedido = "<a href='pedido_admin_consulta.php?pedido=$pedido'>PEDIDO: $pedido</a>";
		}

		$ressarcimento = pg_result($res_troca, $t, ressarcimento);
		if ($ressarcimento == 't') {
			if ($pedido) {
				$pedido .= " - ";
			}
			$ressarcimento = "RESSARCIMENTO";
		}
		else {
			$ressarcimento = "";
		}

		if ($notas && $ressarcimento) {
			$ressarcimento = "<font color='#FF0000'>$ressarcimento</font>";
		}

		if ($observacao) {
			$observacao = " | OBSERVAÇÃO " . $observacao;
		}

		$troca[] = $pedido . $ressarcimento . $observacao;
	}

	$troca = implode("<br>", $troca);

	echo  "
	<tr>
		<td><a href='os_press.php?os=$os'>$os</a></td>
		<td>$troca</td>
		<td>$notas</td>
	</tr>";
}

echo  "
</tbody>
</table>";

?>

<? include "rodape.php" ?>
