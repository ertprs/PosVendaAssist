<?
if($monitora=="LIGADO"){
if($monitoramento_sistema){

$monitora_end = getmicrotime_monitora();
$monitora_total = $monitora_end - $monitora_start;
$time_log = round($monitora_total,4) ;

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


$sql = "UPDATE tbl_monitora set
				tempo = '$time_log'           ,
				post_data = '$xpost'          ,
				get_data  = '$xget'           ,
				data_saida = current_timestamp
		WHERE monitora = $monitoramento_sistema";
$res = pg_exec($con,$sql);
}
}
?>
