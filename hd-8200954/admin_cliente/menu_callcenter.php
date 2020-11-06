<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="call_center, gerencia";

require_once 'autentica_admin.php';
include_once 'funcoes.php';
include_once '../helpdesk/mlg_funciones.php';

$layout_menu = ($login_fabrica == 108  and $login_fabrica == 111) ? '' : 'callcenter';

if (in_array($login_fabrica, [167, 203])) {
  header("Location:menu_gerencia.php");
}

$sql_om = "
     SELECT SUBSTR(tbl_marca.nome, 0, 6) AS marca
       FROM tbl_admin
       JOIN tbl_cliente_admin USING(cliente_admin)
       JOIN tbl_marca         USING(marca)
      WHERE tbl_admin.admin = $login_admin";

$res_om = pg_query($con, $sql_om);

if (!is_resource($res) or !pg_num_rows($res)) {
    include_once '../admin/logout.php';
    // header('Location: ../externos/login_posvenda_new.php');
}

#$marca = pg_fetch_result(pg_query($con, $sql_om), 0, 'marca');

if (pg_num_rows($res_om)>0) {
    $marca = pg_fetch_result($res_om,0,0);
}

$title = "Menu Call-Center";
include 'cabecalho_new.php';

menuTCAdmin($menu = include('menus/menu_callcenter.php'));

// Monta o menu CALL-CENTER
if ($_GET['debug'] == 'array') {
    foreach($menu as $secao) {
        $total += count($secao) - 2; //'secao' e 'linha_de_separação'
        echo "Total das " . count($menu) . " seções: <b>$total</b> ítens.<br />";
    }
    if (isCLI)
        print_r($menu);
}

include 'rodape.php';
include '../google_analytics.php';

