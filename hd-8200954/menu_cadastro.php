<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$layout_menu = "cadastro";
$title = traduz("menu.de.cadastramentos",$con);

include 'cabecalho.php';

echo $cabecalho->menu(include(MENU_DIR.'menu_cadastro.php'));

include "rodape.php";

