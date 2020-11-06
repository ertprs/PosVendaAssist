<?
include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';

$admin_privilegios="call_center";

include_once 'autentica_admin.php';
include_once '../fn_traducao.php';

if( in_array($login_fabrica, [167, 203]) ){
	include "./os_consulta_lite_brother.php";
}else{
	chdir (CA_APP_PATH . 'admin');
	include("os_consulta_lite.php");
}
