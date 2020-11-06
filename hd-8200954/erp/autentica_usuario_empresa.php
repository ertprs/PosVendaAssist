<?

#$cook_master           = $HTTP_COOKIE_VARS['cook_master'];
#$cook_fabrica          = $HTTP_COOKIE_VARS['cook_fabrica'];

$cook_empresa          = $HTTP_COOKIE_VARS['cook_empresa'];
$cook_admin            = $HTTP_COOKIE_VARS['cook_admin'];
$cook_posto            = $HTTP_COOKIE_VARS['cook_loja']; #loja = posto
$cook_loja             = $HTTP_COOKIE_VARS['cook_loja'];
$cook_empregado        = $HTTP_COOKIE_VARS['cook_empregado'];
$cook_pessoa           = $HTTP_COOKIE_VARS['cook_pessoa'];



# empresa = fabrica
# loja    = posto

global $login_empresa        ;
global $login_empresa_nome   ;

global $login_loja           ;
global $login_loja_nome      ;
global $login_posto;

global $login_posto_empregado;
global $login_empregado      ;
global $login_empregado_nome ;
global $login_empregado_email;
global $login_filial_nome    ;
global $login_empresa_nome   ;
global $login_pessoa         ;

global $fabricas_atendidas;

//if(strlen($cook_empregado)>0 AND 1==2){ #DESATIVADO! Os dados do empregado agora estao tbl_empregado
//	$sql = "SELECT	nome, email
//			FROM tbl_posto
//			WHERE posto = $cook_empregado;";
//	$res = pg_exec ($con,$sql);
//	if (pg_numrows ($res) > 0) {
//		$login_empregado_nome  = trim (pg_result ($res,0,nome));
//		$login_empregado_email = trim (pg_result ($res,0,email));
//	}
//}

if(strlen($cook_pessoa)>0){
	$sql = "SELECT nome,email
		FROM tbl_pessoa
		WHERE pessoa = $cook_pessoa;";
	$res = pg_exec ($con,$sql);
	if (pg_numrows ($res) > 0) {
		$login_empregado_nome  = trim (pg_result ($res,0,nome));
		$login_empregado_email = trim (pg_result ($res,0,email));
	}
}


if (strlen($cook_loja)>0){
	$sql = "SELECT tbl_posto.nome,tbl_posto.posto
			FROM tbl_posto
			LEFT JOIN tbl_loja_dados ON tbl_posto.posto = tbl_loja_dados.loja
			WHERE tbl_posto.posto = $cook_loja;";
	$res = pg_exec ($con,$sql);
	if (pg_numrows ($res) > 0) {
		$login_loja_nome  = trim (pg_result ($res,0,nome));
		$login_posto      = trim (pg_result ($res,0,posto));
	}
}

if (strlen($cook_posto)>0){
	$sql = "SELECT	tbl_posto_fabrica.fabrica,
					tbl_posto_fabrica.codigo_posto,
					tbl_fabrica.nome
			FROM tbl_posto
			JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
			JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_posto_fabrica.fabrica
			WHERE tbl_posto.posto = $cook_posto;";
	$res = pg_exec ($con,$sql);
	if (pg_numrows ($res) > 0) {
		$numero_fabricas = pg_numrows ($res);
		$fabricas_atendidas = array();
		for ($i=0;$i<$numero_fabricas;$i++){
			$_fabrica       = trim(pg_result ($res,$i,fabrica));
			$_codigo_posto  = trim(pg_result ($res,$i,codigo_posto));
			$_nome          = trim(pg_result ($res,$i,nome));
			array_push($fabricas_atendidas,array("fabrica" => $_fabrica, "nome" => $_nome, "codigo_posto" => $_codigo_posto));
		}
	}
}


$login_empresa         = $cook_empresa        ;
$login_loja            = $cook_loja           ;
$login_posto           = $cook_posto;
$login_empregado       = $cook_empregado      ;
$login_admin           = $cook_admin          ;
$login_pessoa          = $cook_pessoa         ;


if (strlen ($login_empresa) == 0) {
	if (strlen($login_loja)==0){
		header ("Location: ../index.php");
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


?>
