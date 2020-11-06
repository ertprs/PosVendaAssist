<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="cadastros";
include 'autentica_admin.php';

header('Content-Type: text/html; charset=ISO-8859-1');

$str = $_GET['str'];
$os = $_GET['os'];

$orientacao_sac =  date("d/m/Y H:i")." - ".$str;						

	$sql = "UPDATE tbl_os_extra SET orientacao_sac  =  
								CASE WHEN orientacao_sac IS NULL 
								OR orientacao_sac = 'null' THEN '' ELSE orientacao_sac || ' \n' END || trim('$orientacao_sac')
			WHERE os = $os";
	
	$res = pg_exec($con,$sql);

	$msg_erro = pg_errormessage($con);
	
	if (strlen($msg_erro)==0) {
		echo "ok|$os";
	}else{
			echo "no|não";
	}

?>