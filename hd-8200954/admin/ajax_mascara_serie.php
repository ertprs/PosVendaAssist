<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$mascara = trim($_POST['mascara']); 
$produto = trim($_POST['produto']); 

$sql = "SELECT * FROM tbl_produto_valida_serie WHERE fabrica = $login_fabrica AND produto = $produto AND mascara = '$mascara';";
$res = pg_exec($con,$sql);
$tot = pg_num_rows($res);

if ($tot) {
	$sql = "DELETE FROM tbl_produto_valida_serie WHERE fabrica = $login_fabrica AND produto = $produto AND mascara = '$mascara';";
	$res = pg_exec($con,$sql);
	echo (pg_affected_rows($res)) ? 1 : 0;
} else {
	echo 1;
}

