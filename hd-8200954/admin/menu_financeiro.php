<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "financeiro";
include 'autentica_admin.php';

$title       = traduz("MENU FINANCEIRO");
$layout_menu = "financeiro";
include 'cabecalho_new.php';

include 'jquery-ui.html';

menuTCAdmin($menu=include('menus/menu_financeiro.php'), null, '#f1f4fa', '#fefefe');

if ($_GET['debug'] == 'array') {
	foreach($menu as $secao) {
		$total += count($secao) - 2; //'secao' e 'linha_de_separação'
	}
	echo "Total das " . count($menu) . " seções: <b>$total</b> ítens.<br />";
	if (isCLI)
		print_r($menu);
}
include 'rodape.php';
include '../google_analytics.php';

