<?
include '../token_cookie.php';
$token_cookie = $_COOKIE['sess'];
$cookie_login = get_cookie_login($token_cookie);

$cook_posto_fabrica    = $cookie_login['cook_posto_fabrica'];
$cook_posto            = $cookie_login['cook_posto'];



$cook_fabrica          = $cookie_login['cook_fabrica'];
$cook_admin            = $cookie_login['cook_admin'];
$cook_master           = $cookie_login['cook_master'];
$cook_empresa          = $cookie_login['cook_empresa'];
$cook_loja             = $cookie_login['cook_loja'];
$cook_empregado        = $cookie_login['cook_empregado'];
$cook_posto_empregado  = $cookie_login['cook_posto_empregado'];



global $login_empresa        ;
global $login_empresa_nome   ;
global $login_loja           ;
global $login_loja_nome      ;
global $login_posto_empregado;
global $login_empregado      ;
global $login_empregado_nome ;
global $login_filial_nome    ;
global $login_empresa_nome   ;

$sql = "SELECT nome
	FROM tbl_posto
	WHERE posto = $cook_posto_empregado;";
$res = @pg_exec ($con,$sql);
$l_empregado_nome = trim (@pg_result ($res,0,nome));

$sql = "SELECT nome
	FROM tbl_posto
	WHERE posto = $cook_loja;";
$res = @pg_exec ($con,$sql);
$l_loja_nome = trim (@pg_result ($res,0,nome));

$login_empresa         = $cook_empresa        ;
$login_loja            = $cook_loja           ;
$login_loja_nome       = $l_loja_nome         ;
$login_empregado       = $cook_empregado      ;
$login_posto_empregado = $cook_posto_empregado;
$login_admin           = $cook_admin          ;
$login_empregado_nome  = $l_empregado_nome    ;


if (strlen ($cook_posto_fabrica) == 0) {

	if (strlen($login_loja)==0){
		header ("Location: index.php");
		exit;
	}else{
		$login_posto = $login_loja;
	}
}


$var_post = "";
foreach($_POST as $key => $val) { 
    $var_post .= "[" . $key . "]=" . $val . "; ";
} 
foreach($_GET as $key => $val) { 
    $var_get .= "[" . $key . "]=" . $val . "; ";
} 
		          

$sql = "/* PROGRAMA $PHP_SELF  # FABRICA $login_fabrica  #  POSTO $login_posto  # POST-FORM $var_post # GET-DATA $var_get  */";
$resX = @pg_exec ($con,$sql);

