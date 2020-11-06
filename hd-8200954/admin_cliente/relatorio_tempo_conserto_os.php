<?php

include 'dbconfig.php';
include 'dbconnect-inc.php';
$admin_privilegios="call_center";
$layout_menu = 'callcenter';
include 'autentica_admin.php';

$sql = "SELECT cliente_admin FROM tbl_admin WHERE admin=$login_admin";
$res = pg_query($con, $sql);

$trava_cliente_admin = pg_result($res, 0, cliente_admin);

include("../admin/relatorio_tempo_conserto_os.php");

?>
