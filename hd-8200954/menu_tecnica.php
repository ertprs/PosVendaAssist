<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

if (in_array($login_tipo_posto, array(36, 82, 83, 84))) {
	header("Location: login.php");
	exit;
}

$layout_menu = "tecnica";
$title = traduz('menu.de.comunicados.e.informacoes.tecnicas');

include 'cabecalho.php';

echo $cabecalho->menu(include(MENU_DIR.'menu_tecnica.php'));

include "rodape.php";

