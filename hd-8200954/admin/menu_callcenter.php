<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="call_center";
include 'autentica_admin.php';
include_once 'funcoes.php';

$layout_menu = ($login_fabrica == 108  and $login_fabrica == 111) ? '' : 'callcenter';

$title = traduz("MENU CALL-CENTER");
include 'cabecalho_new.php';

include 'jquery-ui.html';

if (in_array($login_fabrica, $fabrica_callcenter_deshabilitado)) {
	echo "<h1 style='color:redmargin:auto;text-align:center'>".traduz('M�dulo de Call-Center Desabilitado')."</h1>";
	include 'rodape.php';
}

menuTCAdmin($menu = include('menus/menu_callcenter.php'));

// Monta o menu CALL-CENTER
if ($_GET['debug'] == 'array') {
	foreach($menu as $secao) {
		$total += count($secao) - 2; //'secao' e 'linha_de_separa��o'
		echo "Total das " . count($menu) . " se��es: <b>$total</b> �tens.<br />";
	}
	if (isCLI)
		print_r($menu);
}

include 'rodape.php';
include '../google_analytics.php';

