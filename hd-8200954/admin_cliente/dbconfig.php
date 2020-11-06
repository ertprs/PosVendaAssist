<?php

$ip        = getenv ("REMOTE_ADDR");
$programa  = $_SERVER["SCRIPT_FILENAME"] ;

global $cookie_login, $con, $pdo;

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

$server_addr = array_key_exists('SERVER_ADDR', $_SERVER)
    ? $_SERVER['SERVER_ADDR']
    : '0.0.0.0';

define ('CA_APP_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);

require_once CA_APP_PATH . 'classes/autoload.php';
require_once CA_APP_PATH . 'token_cookie.php';
require_once CA_APP_PATH . 'filter_var.php';

/*
echo "<CENTER><h1>ATENÇÃO</h1>";
echo "<h3>O sistema passará por manutenção técnica</h3";
echo "<h3>Dentro de algumas horas será restabelecido</h3";
echo "<h3> </h3";
echo "<p><h3>Agradecemos a compreensão!</h3>";
exit;
*/

?>
