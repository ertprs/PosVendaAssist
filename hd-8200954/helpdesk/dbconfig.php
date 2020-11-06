<?php

$ip        = getenv ("REMOTE_ADDR");
$programa  = $_SERVER["SCRIPT_FILENAME"] ;

require_once "/etc/telecontrol.cfg";

if (isset($_SERVER['SERVER_ADDR']))
{
	$server_addr = $_SERVER['SERVER_ADDR'];
}else{
	$server_addr = "0.0.0.0";
}


if ($server_addr == "192.168.0.199") {
	error_reporting(E_ALL & ~E_NOTICE);
} else {
	error_reporting(E_ERROR);
}

/*
echo "<CENTER><h1>ATENÇÃO</h1>";
echo "<h3>O sistema passará por manutenção técnica</h3";
echo "<h3>Dentro de algumas horas será restabelecido</h3";
echo "<h3> </h3";
echo "<p><h3>Agradecemos a compreensão!</h3>";
exit;
*/


/**
 * Método que carrega as classes "automaticamente"
 * no momento que ela é instanciada
 */
if (!function_exists('__autoload')) {
	function __autoload($class) {
		$path = strtolower($class);
		$diretorio    = 'classes/';
		$subDiretorio = '';
		
		do {
			if(file_exists($subDiretorio. $diretorio . $path . '.php')) {
				require_once($subDiretorio. $diretorio . $path . '.php');
				break;
			}
			$subDiretorio .= '../';
			continue;
		} while (true);
	}
}

require_once dirname(realpath(__FILE__)) . DIRECTORY_SEPARATOR . '../token_cookie.php';
require_once dirname(realpath(__FILE__)) . DIRECTORY_SEPARATOR . '../filter_var.php';

