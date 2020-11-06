<?php

switch ($_SERVER["SERVER_NAME"]) {
	case "posvenda.telecontrol.com.br":
		$retorno = "http://www.telecontrol.com.br";
		break;
	case "ww2.telecontrol.com.br":
		$retorno = "https://ww2.telecontrol.com.br/assist/externos/login_posvenda.php";
		break;
	default:
		$retorno = $_SERVER["SERVER_NAME"];
}

include_once __DIR__ . '/token_cookie.php';
remove_login_cookie($_COOKIE['sess']);
unset($_COOKIE["senha_posto_skip"]);
unset($_COOKIE["senha_skip"]);
setcookie("senha_posto_skip", null, -1, '/');
setcookie("senha_skip", null, -1, '/');
header('Location: ' . $retorno); 

exit;
