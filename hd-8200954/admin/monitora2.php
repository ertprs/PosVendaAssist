<?
//pega o ip do usuario
$ip_log = getenv("REMOTE_ADDR"); 
/*
//pegando a data e hora
$data_log = date("Y-m-d",time());
$hora_log = date("H:i",time());

$msg_monitoramento = "$data_log \t $hora_log \t $ip_log \t $login_admin \t $PHP_SELF \n";

$arquivo_monitoramento = "log.tak";
$conteudo_monitoramento = fopen($arquivo_monitoramento, "a");
$escreve_monitoramento = fwrite($conteudo_monitoramento, "$msg_monitoramento"); 
fclose($conteudo_monitoramento); 
*/
//$login_admin =397;
//$micro_time_end = getmicrotime();
//print_r($_GET);

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
$time_log = round($time,4) ;

 monitoramento | integer                     | not null default nextval('tbl_monitora_seq'::regclass)
 ip            | character varying(15)       | not null
 admin         | integer                     |
 programa      | text                        |
 tempo         | double precision            |
 post_data     | text                        |
 get_data      | text                        |
 data_entrada  | timestamp without time zone | default now()
 data_saida    | timestamp without time zone | default now()



$sql =  "INSERT into tbl_monitora(
						data,
						admin,
						ip,
						programa,
						tempo,
						post_data, 
						get_data
				)values(
						current_timestamp,
						$login_admin,
						'$ip_log',
						'$PHP_SELF',
						'$time_log',
						'$xpost',
						'$xget')";

//echo $sql;
$res = pg_exec($con,$sql);

?>
