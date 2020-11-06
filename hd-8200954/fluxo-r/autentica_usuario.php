<?
include '../token_cookie.php';
$token_cookie = $_COOKIE['sess'];
$cookie_login = get_cookie_login($token_cookie);

$cook_posto         = $cookie_login['cook_posto'];
$cook_login_unico   = $cookie_login['cook_login_unico'];
$cook_fabrica       = $cookie_login['cook_fabrica'];
// setcookie ("cook_fabrica","");

if(strlen($cook_login_unico)==0 OR $cook_login_unico == 'temporario'){
	header("Location: http://www.telecontrol.com.br/index.php");
	exit;
}

$sql = "SELECT pedido FROM tbl_pedido WHERE fabrica = 10 AND finalizado IS NULL AND posto=$cook_posto";

$res = @pg_exec ($con,$sql);
$msg_erro = pg_errormessage($con);
if(pg_numrows($res)>0){
	$cook_pedido_lu = pg_result($res, 0, pedido);
	// setcookie ("cook_pedido_lu",$cook_pedido_lu);
	add_cookie($cookie_login,"cook_pedido_lu",$cook_pedido_lu);	
	set_cookie_login($token_cookie,$cookie_login);
}

$sql = "SELECT  * 
	FROM tbl_login_unico
	WHERE login_unico = $cook_login_unico";

$res = pg_exec($sql);
if(pg_numrows($res)>0){
	$login_nome         = pg_result($res,0,nome);
	$login_email        = pg_result($res,0,email);
	$login_master       = pg_result($res,0,master);
}

$login_posto = $cook_posto;
$login_unico = $cook_login_unico;

header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");
// Data no passado
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
// Sempre modificado
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
// HTTP/1.1
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

// HTTP/1.0
header("Pragma: no-cache");
header("Content-Type: text/html; charset=ISO-8859-1",true);


?>
