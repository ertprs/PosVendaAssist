<?php
$ip        = getenv ("REMOTE_ADDR");
$programa  = $_SERVER["SCRIPT_FILENAME"] ;

$time_user_online = 5; // in minutes

global $cookie_login, $dbhost, $dbport, $dbnome, $dbusuario, $dbsenha, $pdo;

require_once "/etc/telecontrol.cfg";

if (!isset($no_pdo)) {
	try {
		$conStr = "pgsql:host=$dbhost;port=$dbport;dbname=$dbnome";
		$pdo = new PDO($conStr, $dbusuario, $dbsenha);
		$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	} catch(PDOException $e) {
		echo 'ERROR: ' . $e->getMessage();
	}
}

if (isset($_SERVER['SERVER_ADDR'])) {
	$server_addr = $_SERVER['SERVER_ADDR'];
} else {
	$server_addr = "0.0.0.0";
}


if ($server_addr == "192.168.0.19") {
	error_reporting(E_ALL & ~E_NOTICE);
} else {
	error_reporting(E_ERROR);
}

#error_reporting(E_ALL & ~E_NOTICE);
/*
echo "<CENTER><h1>ATENÇÃO</h1>";
echo "<h3>O sistema passará por manutenção técnica</h3";
echo "<h3>Dentro de algumas horas será restabelecido</h3";
echo "<h3> </h3";
echo "<p><h3>Agradecemos a compreensão!</h3>";
exit;
*/

require_once __DIR__.'/classes/autoload.php';
require_once dirname(realpath(__FILE__)) . DIRECTORY_SEPARATOR . 'token_cookie.php';
require_once dirname(realpath(__FILE__)) . DIRECTORY_SEPARATOR . 'filter_var.php';

