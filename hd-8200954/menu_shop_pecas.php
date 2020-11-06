<?php
include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
include_once 'autentica_usuario.php';

$desabilita_tela = isFabrica(20) ? '' : traduz("Sem permissão");

$layout_menu = "shop_pecas";
$title = traduz('menu.de.compra.e.venda.de.pecas.entre.postos');
include 'cabecalho.php';

echo $cabecalho->menu(MENU_DIR . 'menu_shop_pecas.php');

include "rodape.php";

