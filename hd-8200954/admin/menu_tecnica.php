<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="info_tecnica";
include 'autentica_admin.php';

$title = traduz("MENU INFORMAÇÕES TÉCNICAS");
$layout_menu = "tecnica";
include 'cabecalho_new.php';

include 'jquery-ui.html';

include_once 'funcoes.php';

if($login_fabrica == 86 and 1==2) {
	echo "<h2 style='text-align:center'>".traduz('Acesso Restrito')."</h2>";
	include "rodape.php" ;
	exit;
}

menuTCAdmin($menu = include('menus/menu_tecnica.php'));

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

