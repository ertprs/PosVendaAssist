<?php
include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
unset($_COOKIE["senha_posto_skip"]);
unset($_COOKIE["senha_skip"]);
setcookie("senha_posto_skip", null, -1, '/');
setcookie("senha_skip", null, -1, '/');
$retorno = "http://www.telecontrol.com.br";
$retorno = '//' . $_SERVER["HTTP_HOST"] .
	preg_replace('#/(admin|admin_es|admin_callcenter|helpdesk)#', '', dirname($_SERVER['SCRIPT_NAME'])) . DIRECTORY_SEPARATOR .
	'externos/login_posvenda_new.php';
// goto FIM;

if (!empty($cookie_login)) {
	$login_fabrica = $cookie_login["cook_fabrica"];

	switch ($login_fabrica) {
		// case 20:
		// 	$retorno = "http://www.bosch.com.br/assist";
		// break;
		case 87:
			$retorno = "http://www.jacto.com.br";
		break;
		case 104:
			$retorno = "https://ww2.telecontrol.com.br/vonder/login.html";
		break;
		default:
			$retorno = '//' . $_SERVER["HTTP_HOST"] .
				preg_replace('#/(admin|admin_es|admin_callcenter|helpdesk)#', '', dirname($_SERVER['SCRIPT_NAME'])) . DIRECTORY_SEPARATOR .
				'externos/login_posvenda_new.php';
		break;
	}
}

FIM:

session_start();
if($_SESSION['user']){
    unset($_SESSION['user']);
}
session_destroy();

if(!empty($_COOKIE['sess'])){
    remove_login_cookie($_COOKIE['sess']);
    unset($_COOKIE['sess']);
    setcookie("sess", "", 1);
}
    


header('Location: ' . $retorno);

