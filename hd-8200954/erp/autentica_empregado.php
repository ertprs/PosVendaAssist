<?
$cook_empresa          = $HTTP_COOKIE_VARS['cook_empresa'];
$cook_empregado        = $HTTP_COOKIE_VARS['cook_empregado'];
$cook_pessoa           = $HTTP_COOKIE_VARS['cook_pessoa'];

global $login_empresa        ;
global $login_pessoa         ;
global $login_empregado      ;
global $login_privilegios    ;
global $fabricas_atendidas   ;

if(strlen($cook_empregado)>0){
	$sql = "SELECT 
				tbl_pessoa.email ,
				tbl_pessoa.nome ,
				tbl_empregado.empregado,
				tbl_empregado.privilegios
			FROM tbl_pessoa JOIN tbl_empregado USING(pessoa) 
			WHERE tbl_empregado.empregado=$login_empregado";
	$res = pg_exec ($con,$sql);
	if (pg_numrows ($res) > 0) {
		$login_privilegios       = trim (pg_result ($res,0,privilegios));
	}
	$acesso = explode(",", $login_privilegios);
}

$login_empresa   = $cook_empresa   ;
$login_empregado = $cook_empregado ;
$login_pessoa    = $cook_pessoa    ;

if (strlen ($login_empresa) == 0) {
	if (strlen($login_empregado)==0){
		header ("Location: ../index.php");
		exit;
	}
}

//ACESSO RESTRITO AO USUARIO MASTER 
/*if (strpos ($login_privilegios,'*') === false ) {
		echo "<script>"; 
			echo "window.location.href = 'menu_inicial.php?msg_erro=Você não tem permissão para acessar a tela.'";
		echo "</script>";
	exit;
}*/

$var_post = "";
foreach($_POST as $key => $val) { 
    $var_post .= "[" . $key . "]=" . $val . "; ";
}

foreach($_GET as $key => $val) { 
    $var_get .= "[" . $key . "]=" . $val . "; ";
}

$sql = "/* PROGRAMA $PHP_SELF  # FABRICA $login_fabrica # EMPREGADO $login_empregado # POST-FORM $var_post # GET-DATA $var_get  */";
$resX = @pg_exec ($con,$sql);
?>
