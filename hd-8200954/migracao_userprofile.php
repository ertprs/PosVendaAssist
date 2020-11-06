<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

if($_GET['env'] == 'posto'){
	include 'autentica_usuario.php';
}else{
	include 'autentica_admin.php';
}

$transferCode = "0tcchat0transfer0code20180220";

include 'funcoes.php';
if($_GET['env'] == 'posto'){

	header("Location: https://telecontrol.com.br");
	exit;
}else{

	$sql = "SELECT nome_completo,fabrica FROM tbl_admin WHERE admin = $login_admin";
	$result = pg_query($con,$sql);
	$result = pg_fetch_row($result);
	$data = array(
		"admin" => $login_admin,
		"nome_completo" => utf8_encode($result[0]),
		"fabrica" => $result[1],
		"env" => "admin"
	);

	$data = json_encode($data);

	$transferData = openssl_encrypt($data,"aes-256-ctr",$transferCode);
	$transferData = urlencode($transferData);

	header("Location: http://userauthtc.telecontrol.com.br/posvenda-migration?code=".$transferData);
	exit;
}
