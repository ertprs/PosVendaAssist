<?php
// O cabeçalho é usado na área do admin/bi/, aqui define os paths relativos,
// Pode ser usado dentro dos programas do BI para pegar as imagens do admin, também.
if(strpos($_SERVER['PHP_SELF'],'/bi/') == true){
	define('BI_BACK', '../');
} else {
	define('BI_BACK', '');
}

// include("../helpdesk/mlg_funciones.php");
// p_echo ($_SERVER['SCRIPT_NAME']);
// p_echo (dirname($_SERVER['SCRIPT_NAME']));
// p_echo (substr($_SERVER['SCRIPT_NAME'], 0, strpos($_SERVER['SCRIPT_NAME'], 'admin')));

setcookie('HDComunicadoJanela', null);

include_once BI_BACK . 'dbconfig.php';
include_once BI_BACK . 'includes/dbconnect-inc.php';

//Inicializa a session
session_start();
	if(intval($_SESSION['session_admin']['admin']) > 0){
		$sql = "DELETE FROM tbl_admin_online WHERE admin = {$_SESSION['session_admin']['admin']};";
		pg_query($con, $sql);
	}
//Destroy a session
session_destroy();

switch ($_SERVER["SERVER_NAME"]) {
	case "posvenda.telecontrol.com.br":
		$retorno = "http://www.telecontrol.com.br";
		break;
	case "ww2.telecontrol.com.br":
		$retorno = "https://ww2.telecontrol.com.br/assist/externos/login_posvenda_new.php";
		break;
	default:
		$retorno = '//' . $_SERVER["HTTP_HOST"] .
			preg_replace('#/(admin|admin_es|admin_callcenter|helpdesk)#', '', dirname($_SERVER['SCRIPT_NAME'])) .
			DIRECTORY_SEPARATOR . 'externos/login_posvenda_new.php';
}

remove_login_cookie($_COOKIE['sess']);

header('Location: ' . $retorno);
exit;

