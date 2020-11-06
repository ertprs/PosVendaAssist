<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "auditoria";

include 'autentica_admin.php';
include_once 'funcoes.php';

$title       = traduz("MENU AUDITORIA");
$layout_menu = "auditoria";

include 'cabecalho_new.php';

include 'jquery-ui.html';

menuTCAdmin($menu = include('menus/menu_auditoria.php'));

// Monta o menu AUDITORIA
if ($_GET['debug'] == 'array') {
	foreach($menu as $secao) {
		$total += count($secao) - 2; //'secao' e 'linha_de_separação'
	}
	echo "Total das " . count($menu) . " seções: <b>$total</b> ítens.<br />";
	if (isCLI)
		var_export($menu);
}

include 'rodape.php';
include '../google_analytics.php';

