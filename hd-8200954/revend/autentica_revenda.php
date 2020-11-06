<?
include '../token_cookie.php';

$token_cookie = $_COOKIE['sess'];
$cookie_login = get_cookie_login($token_cookie);

$cook_revenda          = $cookie_login['cook_revenda'];
$cook_fabrica          = $cookie_login['cook_fabrica'];

//antes era $HTTP_COOKIE_VARS
$cook_revenda          = $cookie_login['cook_revenda'];
$cook_fabrica          = $cookie_login['cook_fabrica'];
$cook_email            = $cookie_login['cook_email'];
$cook_nome             = $cookie_login['cook_nome'];
$cook_cnpj             = $cookie_login['cook_cnpj'];

print_r($_cookie);

global $login_revenda        ;
global $login_email          ;
global $login_nome           ;
global $login_cnpj           ;
global $login_logo           ;
global $login_fabrica        ;

if(strlen($cook_revenda)>0){
	$sql = "SELECT  tbl_revenda.revenda,
			tbl_revenda.cnpj,
			tbl_revenda.nome,
			tbl_revenda_fabrica.email,
			tbl_revenda.logo
		FROM  tbl_revenda
		JOIN  tbl_revenda_fabrica USING(revenda)
		WHERE tbl_revenda.revenda = $cook_revenda
		AND   tbl_revenda_fabrica.fabrica = $cook_fabrica ";
	$res = pg_exec ($con,$sql);

	$r_revenda  = trim (@pg_result ($res,0,revenda));
	$r_cnpj     = trim (@pg_result ($res,0,cnpj));
	$r_nome     = trim (@pg_result ($res,0,nome));
	$r_email    = trim (@pg_result ($res,0,email));
	$r_logo     = trim (@pg_result ($res,0,logo));
}

$login_revenda    = $cook_revenda        ;
$login_fabrica    = $cook_fabrica        ;
$login_cnpj       = $r_cnpj              ;
$login_nome       = $r_nome              ;
$login_email      = $r_email             ;
$login_logo       = $r_logo              ;

if (strlen ($cook_revenda) == 0) {
	if (strlen($login_revenda)==0){
		header ("Location: http://www.telecontrol.com.br.index.php");
		exit;
	}
}

$var_post = "";
foreach($_POST as $key => $val) $var_post .= "[" . $key . "]=" . $val . "; ";
foreach($_GET as $key => $val)  $var_get  .= "[" . $key . "]=" . $val . "; ";

$sql = "/* PROGRAMA $PHP_SELF  # FABRICA $login_fabrica  #  POSTO $login_posto  # POST-FORM $var_post # GET-DATA $var_get  */";
$resX = @pg_exec ($con,$sql);



?>
