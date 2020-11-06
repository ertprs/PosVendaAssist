<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_usuario.php';

$data_inicial = $_REQUEST["data_inicial"];
$data_final = $_REQUEST["data_final"];
$os = $_REQUEST["os"];
$pedido = $_REQUEST["pedido"];
//include "cabecalho_new.php";
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<title><?=traduz('pesquisa.de.produto', $con)?></title>
	<meta name="Author" content="">
	<meta name="Keywords" content="">
	<meta name="Description" content="">
	<meta http-equiv="pragma" content="no-cache">

	<link rel="stylesheet" type="text/css" href="css/lupas/lupas.css">
	<link rel="stylesheet" type="text/css" href="css/posicionamento.css">
	<link rel="stylesheet" type="text/css" href="js/thickbox.css" media="screen">	
	<link media="screen" type="text/css" rel="stylesheet" href="admin/bootstrap/css/bootstrap.css" />
    <link media="screen" type="text/css" rel="stylesheet" href="admin/bootstrap/css/extra.css" />
    <link media="screen" type="text/css" rel="stylesheet" href="admin/css/tc_css.css" />
    <link media="screen" type="text/css" rel="stylesheet" href="admin/plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css">
    <link media="screen" type="text/css" rel="stylesheet" href="admin/bootstrap/css/ajuste.css" />

    <!--[if lt IE 10]>
    <link href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-ie.css" rel="stylesheet" type="text/css" media="screen" />
    <link rel='stylesheet' type='text/css' href="bootstrap/css/ajuste_ie.css">
    <![endif]-->

    <script type="text/javascript" src="admin/plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
    <script type="text/javascript" src="admin/plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
    <script type="text/javascript" src="admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.core.min.js"></script>
    <script type="text/javascript" src="admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.widget.min.js"></script>
    <script type="text/javascript" src="admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.effect.min.js"></script>
    <script type="text/javascript" src="admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.tabs.min.js"></script>
    <script type="text/javascript" src="admin/bootstrap/js/bootstrap.js"></script>
    <script src="plugins/jquery/jquery.tablesorter.min.js" type="text/javascript"></script>
	<style type="text/css">
				body {
					margin: 0;
					font-family: Arial, Verdana, Times, Sans;
					background: #fff;
				}
	</style>
	<script type='text/javascript'>
		//função para fechar a janela caso a telca ESC seja pressionada!
		$(window).keypress(function(e) {
			if(e.keyCode == 27) {
				 window.parent.Shadowbox.close();
			}
		});

		$(document).ready(function() {
			$("#gridRelatorio").tablesorter({
                headers: {
                    5: {sorter: false}
                }
			});
		});
	</script>
</head>
<?php

$plugins = array(
    "autocomplete",
    "shadowbox",
    "datepicker",
    "dataTable",
    "mask"
);
 include("admin/plugin_loader.php");
 ?>
<body>
	<div class="lp_header">
		<a href='javascript:window.parent.Shadowbox.close();' style='border: 0;'>
			<img src='css/modal/excluir.png' alt='Fechar' class='lp_btn_fechar' />
		</a>
	</div>
	<div class='lp_nova_pesquisa'>
		<form action='<?=$PHP_SELF?>' method='POST' name='nova_pesquisa'>
			<table cellspacing='1' cellpadding='2' border='0'>
				<tr style="text-align: left;">
					<td>
						<label>Data Inicial</label>
						<input type='text' name='data_inicial' id='data_inicial' value='<?=$data_inicial?>' />
					</td>
					<td>
						<label>Data Final</label>
						<input type='text' name='data_final' id='data_final' value='<?=$data_final?>' />
					</td>
				</tr>
				<tr style="text-align: left;">
					<td>
						<label>OS</label>
						<input type='text' name='os' value='<?=$os?>' />
					</td>
					<td>
						<label>Pedido</label>
						<input type='text' name='pedido' value='<?=$pedido?>' />
					</td>
				</tr>
				<tr style="text-align: left;">
					<td colspan='2' class='btn_acao' valign='bottom'><input type='submit' name='btn_acao' value='<?=traduz('pesquisar.novamente', $con)?>' /></td>
				</tr>
			</table>
		</form>
	</div>

<?
if (!empty($data_inicial) && !empty($data_final)) {
	list($di, $mi, $yi) = explode("/", $data_inicial);
	list($df, $mf, $yf) = explode("/", $data_final);

	$inicial = "$yi-$mi-$di";
	$final = "$yf-$mf-$df";
}
if (!empty($os) && !empty($pedido)) {
	$sql = "SELECT DISTINCT tbl_os.os, tbl_pedido.pedido 
			FROM tbl_os LEFT JOIN tbl_os_produto USING(os) 
			LEFT JOIN tbl_os_item USING (os_produto) 
			LEFT JOIN tbl_pedido_item USING (pedido_item) 
			LEFT JOIN tbl_pedido ON tbl_pedido_item.pedido = tbl_pedido.pedido 
			WHERE tbl_os.fabrica = {$login_fabrica} 
			AND (tbl_os.os = {$os}  OR tbl_pedido.pedido = {$pedido}) 
			AND tbl_pedido.posto = {$login_posto} ";
	if (!empty($data_inicial) && !empty($data_final)) {
		$sql .= " AND (data_abertura between '{$inicial}' AND '{$final}' OR data between '{$inicial}' AND '{$final}') ";
	}
	$result = pg_query($con, $sql);
	$arr = pg_fetch_all($result);
} else if(!empty($os)){
	$sql = "SELECT DISTINCT tbl_os.os, tbl_pedido.pedido 
			FROM tbl_os LEFT JOIN tbl_os_produto USING(os) 
			LEFT JOIN tbl_os_item USING (os_produto) 
			LEFT JOIN tbl_pedido_item USING (pedido_item) 
			LEFT JOIN tbl_pedido ON tbl_pedido.pedido = tbl_pedido_item.pedido 
			WHERE tbl_os.fabrica = {$login_fabrica} 
			AND tbl_os.os = {$os} 
			AND tbl_os.posto = {$login_posto} ";
	if (!empty($data_inicial) && !empty($data_final)) {
		$sql .= " AND data_abertura between '{$inicial}' AND '{$final}'";
	}
	$result = pg_query($con, $sql);
	$arr = pg_fetch_all($result);
} else if(!empty($pedido)){
	$sql = "SELECT DISTINCT tbl_os.os, tbl_pedido.pedido 
			FROM tbl_pedido LEFT JOIN tbl_pedido_item USING(pedido) 
			LEFT JOIN tbl_os_item USING (pedido_item) 
			LEFT JOIN tbl_os_produto USING (os_produto) 
			LEFT JOIN tbl_os USING (os)
			WHERE tbl_pedido.fabrica = {$login_fabrica} 
			AND tbl_pedido.pedido = {$pedido} 
			AND tbl_pedido.posto = {$login_posto} ";
	if (!empty($data_inicial) && !empty($data_final)) {
		$sql .= " AND data between '{$inicial}' AND '{$final}' ";
	}
	$result = pg_query($con, $sql);
	$arr = pg_fetch_all($result);
} else if (!empty($data_inicial) && !empty($data_final)) {
	$sqlPedido = "
			SELECT DISTINCT tbl_os.os, tbl_pedido.pedido 
			FROM tbl_pedido LEFT JOIN tbl_pedido_item USING(pedido) 
			LEFT JOIN tbl_os_item USING (pedido_item) 
			LEFT JOIN tbl_os_produto USING (os_produto) 
			LEFT JOIN tbl_os USING (os)
			WHERE tbl_pedido.fabrica = {$login_fabrica} 
			AND tbl_pedido.posto = {$login_posto} 
			AND data between '{$inicial}' AND '{$final}' ";
	$sqlOs = "
			SELECT DISTINCT tbl_os.os, tbl_pedido.pedido 
			FROM tbl_os LEFT JOIN tbl_os_produto USING(os) 
			LEFT JOIN tbl_os_item USING (os_produto) 
			LEFT JOIN tbl_pedido_item USING (pedido_item) 
			LEFT JOIN tbl_pedido ON tbl_pedido.pedido = tbl_pedido_item.pedido 
			WHERE tbl_os.fabrica = {$login_fabrica} 
			AND tbl_os.posto = {$login_posto} 
			AND data_abertura between '{$inicial}' AND '{$final}'";
	$result_pedido = pg_query($con, $sqlPedido);
	$arr_pedido = pg_fetch_all($result_pedido);
	
	$result_os = pg_query($con, $sqlOs);
	$arr_os = pg_fetch_all($result_os);	

	$arr = array_merge($arr_pedido, $arr_os);
} else {
	echo "<font style='color:red;'><b>Digite uma OS, pedido ou Data inicial e final.</b></font>";
	die();
}

echo "<table width='100%' border='1' cellspacing='1' cellspading='0' class='lp_tabela' id='gridRelatorio'>";
	echo "<thead>";
		echo "<tr>";
			echo "<th width='50%'>" . traduz('os', $con) . "</th>";
			echo "<th width='50%'>" . traduz('pedido', $con) . "</th>";
		echo "</tr>";
	echo "</thead>";
	echo "<tbody>";

foreach ($arr as $resultado) {
	$os = ($resultado['os']) ? $resultado['os'] : '0';
	$pedido = ($resultado['pedido']) ? $resultado['pedido'] : '0';
	echo "<tr onclick='javascript: window.parent.retorna_dados_os_pedido($os, $pedido)'; >";
		echo "<td width='50%'>{$os}</td>";
		echo "<td width='50%'>{$pedido}</td>";
	echo "</tr>";
}
	echo "</tbody>";
echo "</table>\n";

?>

</body>
</html>
<script type="text/javascript">
	$(function() {
        $.datepickerLoad(Array("data_inicial", "data_final"));
	});
</script>
<?php
if (is_resource($con)) {
    pg_close($con);
}
?>
