<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
error_reporting(E_ERROR);
$admin_privilegios="call_center, gerencia";

include 'autentica_admin.php';
include_once 'funcoes.php';

$title       = "MENU GERÊNCIA";
$layout_menu = "gerencia";

include 'cabecalho_new.php';

include '../admin/jquery-ui.html';
?>
<!--
<TABLE width="700px" border="0" align="center">
<TR>
	<TD>
		<?
		echo "<a href='$login_fabrica_site' target='_new'>";
		echo "<IMG SRC='/assist/logos/$login_fabrica_logo' ALT='$login_fabrica_site' border='0'>";
		echo "</a>";
		?>
	</TD>
</TR>
</TABLE>

<br>
-->

<?php

// Monta o menu GERENCIA
menuTCAdmin($menu = include( 'menus/menu_gerencia.php')); //, null, '#faf5f8', '#f2e8ee'

if ($_GET['debug'] == 'array') {
	foreach($menu as $secao) {
		$total += count($secao) - 2; //'secao' e 'linha_de_separação'
	}
	echo "Total das " . count($menu) . " seções: <b>$total</b> ítens.<br />";
	if (isCLI)
		print_r($menu);
}

include '../admin/rodape.php';
include '../google_analytics.php';

