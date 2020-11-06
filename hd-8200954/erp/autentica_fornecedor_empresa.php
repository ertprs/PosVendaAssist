<?
$cook_empresa   = $HTTP_COOKIE_VARS['cook_empresa'];
$cook_fornecedor= $HTTP_COOKIE_VARS['cook_fornecedor'];
$cook_menu      = $HTTP_COOKIE_VARS['cook_menu'];
$cook_key       = $HTTP_COOKIE_VARS['cook_key'];

echo $cook_empresa  ;
echo $cook_fornecedor;
echo $cook_menu ;
exit;
# empresa = fabrica
global $login_empresa        ;
global $login_empresa_nome   ;
global $login_pessoa         ;
global $login_menu          ;

if(strlen($cook_pessoa)>0){
	$sql = "select
			tbl_pessoa.pessoa ,
			nome              ,
			tbl_pessoa.empresa
			from tbl_pessoa
			JOIN tbl_pessoa_fornecedor
			ON tbl_pessoa_fornecedor.pessoa = tbl_pessoa.pessoa 
			where tbl_pessoa.pessoa = $cook_pessoa;";
	$res = pg_exec ($con,$sql);
	if (pg_numrows ($res) > 0) {
		$login_empresa_nome = trim (pg_result ($res,0,nome));
		$login_pessoa       = trim (pg_result ($res,0,pessoa));
		$login_empresa      = trim (pg_result ($res,0,email));
	}
}

$login_empresa         = $cook_empresa ;
$login_pessoa          = $cook_pessoa  ;

if (strlen ($login_empresa) == 0) {
	if (strlen($login_pessoa)==0){
		header ("Location: ../index.php");
		exit;
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
?>
