<?php

if (date ("H",time()) >= "03" AND date ("H",time()) < "05" ) {
}else{
	$dbhost    = "postgres.telecontrol.com.br";
	$dbbanco   = "postgres";
	$dbport    = 5432;
	$dbusuario = "telecontrol";
	$dbsenha   = "p0stg43s05";
	$dbnome    = "telecontrol";
	
	$ip = getenv ("REMOTE_ADDR");
	
#	if ($ip <> "200.206.158.62") {
#		echo "<center><b>ESTAMOS EM MANUTENÇÃO. FAVOR AGUARDAR LIBERAÇÃO...</b></center>";
#		exit;
#	}
}
?>
