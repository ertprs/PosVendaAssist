<?php
#	error_reporting(0);
#	error_reporting(E_ALL ^ E_NOTICE);

    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    include dirname(__FILE__) . '/../funcoes.php';
    require dirname(__FILE__) . '/../../class/tdocs.class.php';

    include_once __DIR__.'/../../classes/autoload.php';

	$env = ($_serverEnvironment == 'development') ? 'teste' : 'producao';

	$ftp_server = "rocabr.br.roca.net";

	if ($env == "producao"){
		$ftp_user_name = "telecontrol";
    	$ftp_user_pass = "r0c4tc";
    }else{
		$ftp_user_name = "telecontrolqa";
    	$ftp_user_pass = "r0c4tc";
	}

	$conn_id = ftp_connect($ftp_server);
	ftp_login($conn_id, $ftp_user_name, $ftp_user_pass);
    ftp_pasv($conn_id, true);
?>
