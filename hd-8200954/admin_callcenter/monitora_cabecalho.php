<?
//$monitora = "LIGADO";
$monitora = "LIGADO";
if($monitora=="LIGADO" and $login_fabrica==3) {
function getmicrotime_monitora(){
	list($usec, $sec) = explode(" ",microtime());
	return ((float)$usec + (float)$sec);
}

//pega o ip do usuario
$ip_log = getenv("REMOTE_ADDR"); 
$monitora_start = getmicrotime_monitora();

$xpost="";
$xget="";
foreach ($_POST as $key => $value) {
  	if(strlen($value)>0){
  		$xpost .= $key."-".$value. " || ";
	}
}
foreach ($_GET as $key => $value) {
	if(strlen($value)>0){
  		$xget .= $key."-".$value. " || ";
    }
}

$sql =  "INSERT into tbl_monitora(
						ip                  ,
						admin               ,
						programa            ,
						post_data           ,
						get_data            ,
						data_entrada        
				)values(
						'$ip_log'           ,
						$login_admin        ,
						'$PHP_SELF'         ,
						'$xpost'            ,
						'$xget'             ,
						current_timestamp   )";
$res = pg_exec($con,$sql);


$msg_errox = pg_errormessage($con);
if (strlen ($msg_errox) == 0) {
	$res = pg_exec ($con,"SELECT CURRVAL ('tbl_monitora_seq')");
	$monitoramento_sistema  = pg_result ($res,0,0);
}
}
?>
