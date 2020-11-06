<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$sql = "select descricao from tbl_jacto_produto where produto=15";
$res = pg_query($con, $sql);

//header('Content-Type: text/html; charset=utf-8');

echo '<html><head><style>@charset "utf-8";</style><meta http-equiv="content-type" content="text/html; charset=UTF-8"></head><body>';

echo pg_result($res, 0, 0);

echo '</body>';

?>