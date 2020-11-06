<?php

$ip        = getenv ("REMOTE_ADDR");
$programa  = $_SERVER["SCRIPT_FILENAME"] ;

global $cookie_login, $dbhost, $dbport, $dbnome, $dbusuario, $dbsenha, $pdo;
require_once "/etc/telecontrol.cfg";

// TOKEN Cookie usa...
if (!isset($no_pdo)) {
	try {
		$conStr = "pgsql:host=$dbhost;port=$dbport;dbname=$dbnome";
		$pdo = new PDO($conStr, $dbusuario, $dbsenha);
		$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	} catch(PDOException $e) {
		echo 'ERROR: ' . $e->getMessage();
	}
}

require_once dirname(realpath(__FILE__)) . DIRECTORY_SEPARATOR . '../token_cookie.php';
require_once dirname(realpath(__FILE__)) . DIRECTORY_SEPARATOR . '../filter_var.php';

