<?
if (1==1) {
	$var_post="";
	$var_get="";
	foreach($_POST as $key => $val) { 
		$var_post .= "[" . $key . "]=" . $val . "; ";
	} 
	if (strlen ($var_post) == 0) $var_post = "null";

	foreach($_GET as $key => $val) { 
		$var_get .= "[" . $key . "]=" . $val . "; ";
	} 
	if (strlen ($var_get) == 0) $var_get = "null";
	
	$LOG_POSTO = $login_posto ;
	if (strlen ($LOG_POSTO) == 0) $LOG_POSTO = "null";

	$LOG_ADMIN = $login_admin ;
	if (strlen ($LOG_ADMIN) == 0) $LOG_ADMIN = "null";

	$LOG_FABRICA = $login_fabrica ;
	if (strlen ($LOG_FABRICA) == 0) $LOG_FABRICA = "null";


	$sql = "INSERT INTO log_pagina_inicio (pagina,posto,admin,fabrica,post,get,pgpid) VALUES ('$PHP_SELF',$LOG_POSTO, $LOG_ADMIN, $LOG_FABRICA, '$var_post', '$var_get', pg_backend_pid() );";
	$res = @pg_exec ($con,$sql);

	$sql = "SELECT CURRVAL ('seq_log_pagina');";
	$res = @pg_exec ($con,$sql);
	$LOG_PAGINA = @pg_result ($res,0,0);
}
?>
