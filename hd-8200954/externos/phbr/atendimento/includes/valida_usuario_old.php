<?php
$diretorio	= getenv("QUERY_STRING");
$res		= dbexec($con,"SET DateStyle TO 'SQL,EUROPEAN'",0);

######## Carrega variável com login do usuário ########
#$user_php	= $PHP_AUTH_USER;
$user_php   = $cook_cod_posto;
$user_php	= strtoupper(trim($user_php));
#######################################################

# Carrega variável com senha criptografada do usuário #
#$passwd	= $PHP_AUTH_PW;
#$passwd	= strtoupper(trim($passwd));
$passwd = $cook_senha;
#$passwd	= substr(crypt($passwd,"ak"),2);
#######################################################

##### Verifica no BD se usuário realmente existe ######
$sql = "SELECT * FROM TBUSUARIO 
		WHERE usuario ='$user_php' 
		AND senha ='$passwd'";
$res = dbexec($con,$sql,0);
#######################################################

######### Carrega cook e variaveis do usuário #########
if (dbnumrows($res) > 0) {
	setcookie ("cook_user",dbresult ($res,0,cod_usuario));
	setcookie ("cook_onde","tbusuario");
	$cook_user	= dbresult ($res,0,cod_usuario);
	$cook_onde	= "tbusuario";
	$php_user	= trim(dbresult ($res,0,usuario));
	$php_senha	= trim(dbresult ($res,0,senha));
}
#######################################################

##### Verifica no BD se usuário realmente existe ######
$sql = "SELECT * FROM TBLOGIN 
		WHERE cod_posto = $user_php
		AND senha ='$passwd'";
$res = dbexec($con,$sql,0);
#######################################################

######### Carrega cook e variaveis do usuário #########
if (dbnumrows($res) > 0) {
	setcookie ("cook_user",dbresult ($res,0,cod_posto));
	setcookie ("cook_onde","TBLOGIN");
	$cook_user	= dbresult ($res,0,cod_posto);
	$cook_onde	= "tblogin";
	$php_user	= trim(dbresult ($res,0,cod_posto));
	$php_senha	= trim(dbresult ($res,0,senha));
}
#######################################################

############## Caso o usuário seja Akacia #############
if ($user_php == "akacia") {
	if ($passwd != "aicaka") {
		$passwd = $php_senha;
	}else{
		$passwd = "aicaka";
		$passwd	= strtoupper(trim($passwd));
#		$passwd	= substr(crypt($passwd,"ak"),2);
	}
}
#######################################################

############# Caso o usuário seja Britania ############
if ($user_php == "britania") {
	if ($passwd != "britania") {
		$passwd = $php_senha;
	}else{
		$passwd = "britania";
		$passwd	= strtoupper(trim($passwd));
#		$passwd	= substr(crypt($passwd,"ak"),2);
	}
}
#######################################################

############## Valida outros usuários #################
if (($PHP_AUTH_USER != "akacia") || ($php_senha != "$passwd") AND ($PHP_AUTH_USER != "britania") || ($php_senha != "$passwd")){
	
	if (strlen ($user) > 0 OR strtoupper($cook_resp) == "ADMIN"){
		setcookie ("cook_resp","admin");
		$cook_resp	= "admin";
	}
	if (strtoupper($cook_onde) == "TBLOGIN"){
		$sql = "SELECT * FROM TBLOGIN 
				WHERE cod_posto = $cook_user
				AND senha ='$passwd'";
	}
	if (strtoupper($cook_onde) == "TBUSUARIO"){
		$sql = "SELECT * FROM TBACESSO 
			WHERE cod_usuario = $cook_user
			AND acesso ='$pagina'";
	}
	$res = dbexec($con,$sql,0);
	if (dbnumrows($res) == 0 AND strlen($diretorio) == 0 AND strlen($cook_resp) == 0){
		echo "<meta http-equiv='refresh' content='0;url=/britania/admin/index.php'>";
		exit;
	}
}
#######################################################
?>