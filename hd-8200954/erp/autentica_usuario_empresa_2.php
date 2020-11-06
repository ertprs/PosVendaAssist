<?
$cook_posto_fabrica    = $_COOKIE['cook_posto_fabrica'];
$cook_posto            = $_COOKIE['cook_posto'];



$cook_fabrica          = $HTTP_COOKIE_VARS['cook_fabrica'];
$cook_admin            = $HTTP_COOKIE_VARS['cook_admin'];
$cook_master           = $HTTP_COOKIE_VARS['cook_master'];
$cook_empresa          = $HTTP_COOKIE_VARS['cook_empresa'];
$cook_loja             = $HTTP_COOKIE_VARS['cook_loja'];
$cook_empregado        = $HTTP_COOKIE_VARS['cook_empregado'];
$cook_pessoa           = $HTTP_COOKIE_VARS['cook_pessoa'];



global $login_empresa        ;
global $login_empresa_nome   ;
global $login_loja           ;
global $login_loja_nome      ;
global $login_pessoa         ;
global $login_empregado      ;
global $login_empregado_nome ;
global $login_empregado_email;
global $login_filial_nome    ;
global $login_empresa_nome   ;

$sql = "SELECT nome,email
	FROM tbl_pessoa
	WHERE pessoa = $cook_pessoa;";
$res = @pg_exec ($con,$sql);
$l_empregado_nome = trim (@pg_result ($res,0,nome));
$l_empregado_email = trim (@pg_result ($res,0,email));

$sql = "SELECT nome
	FROM tbl_posto
	WHERE posto = $cook_loja;";
$res = @pg_exec ($con,$sql);
$l_loja_nome = trim (@pg_result ($res,0,nome));

$login_empresa         = $cook_empresa        ;
$login_loja            = $cook_loja           ;
$login_loja_nome       = $l_loja_nome         ;
$login_empregado       = $cook_empregado      ;
$login_pessoa          = $cook_pessoa         ;
$login_admin           = $cook_admin          ;
$login_empregado_nome  = $l_empregado_nome    ;
$login_empregado_email = $l_empregado_email   ;


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

echo "$login_pessoa";
###########################################
### AVISO E BLOQUEIO DE PEDIDO FATURADO ###
###########################################
?>
