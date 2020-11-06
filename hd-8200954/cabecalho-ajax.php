<?
include 'token_cookie.php';
$token_cookie = $_COOKIE['sess'];
$cookie_login = get_cookie_login($token_cookie);

$cook_posto  = $cookie_login['cook_posto'];
$login_posto = $cook_posto;

$gmtDate = gmdate("D, d M Y H:i:s");
header ("Expires: {$gmtDate} GMT");
header ("Last-Modified: {$gmtDate} GMT");
header ("Cache-Control: no-cache, must-revalidate");
header ("Pragma: no-cache");

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

?>