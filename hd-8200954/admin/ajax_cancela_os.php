<?php
	include "dbconfig.php";
	include "includes/dbconnect-inc.php";
	include "funcoes.php";
	include "autentica_admin_cliente.php";

	$os = $_REQUEST['sua_os'];

	if($login_fabrica == 74){

		$sql = "SELECT os_bloqueada FROM tbl_os_campo_extra WHERE fabrica = {$login_fabrica} AND os = {$os}";
		$res = pg_query($con,$sql);

		if(pg_num_rows($res) == 0){

			$sql = "INSERT INTO tbl_os_campo_extra(os,fabrica,os_bloqueada) VALUES({$os},{$login_fabrica},TRUE)";
			$res = pg_query($con,$sql);
		}else{
			$status = pg_fetch_result($res, 0, 'os_bloqueada');

			if($status == "t"){
				$sql = "UPDATE tbl_os_campo_extra SET os_bloqueada = false WHERE os = {$os}";
			}else{
				$sql = "UPDATE tbl_os_campo_extra SET os_bloqueada = true WHERE os = {$os}";
			}
			$res = pg_query($con,$sql);
			$msg_erro = pg_errormessage($con);

		}

		$sql = "SELECT os_bloqueada FROM tbl_os_campo_extra WHERE fabrica = {$login_fabrica} AND os = {$os}";
		$res = pg_query($con,$sql);

		if(pg_num_rows($res) > 0){
			$cancelada = pg_fetch_result($res_status, 0, 'os_bloqueada');
		}

	}else{

		$sql_status = "SELECT cancelada FROM tbl_os WHERE os = {$os}";
		$res_status = pg_query($con, $sql_status);

		$status = pg_fetch_result($res_status, 0, 'cancelada');

		if($status == "t"){
			$sql = "UPDATE tbl_os SET cancelada = false WHERE os = {$os}";
		}else{
			$sql = "UPDATE tbl_os SET cancelada = true WHERE os = {$os}";
		}
		$res = pg_query($con,$sql);
		$msg_erro = pg_errormessage($con);


		$sql_congelada = "SELECT cancelada FROM tbl_os WHERE os = {$os}";
		$res_congelda = pg_query($con, $sql_congelada);
		$msg_erro = pg_errormessage($con);

		if(pg_num_rows($res_congelda) > 0){
			$cancelada = pg_fetch_result($res_congelda, 0, 'cancelada');
		}

	}

	if(strlen($msg_erro)== 0){
		echo "OK|$cancelada";
	}else{
		echo $msg_erro;
	}

	exit;
?>
