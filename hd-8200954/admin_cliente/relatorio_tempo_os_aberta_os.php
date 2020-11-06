<?php

include_once 'dbconfig.php';
include_once 'dbconnect-inc.php';

$admin_privilegios = "call_center";
$layout_menu = 'callcenter';
include_once 'autentica_admin.php';

$sql = "SELECT cliente_admin FROM tbl_admin WHERE admin=$login_admin";
$res = pg_query($con, $sql);

$trava_cliente_admin = pg_result($res, 0, 'cliente_admin');

include_once "../admin/relatorio_tempo_os_aberta_os.php";
