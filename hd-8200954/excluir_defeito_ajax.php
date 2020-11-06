<?
header("Content-Type: text/html; charset=ISO-8859-1",true);
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Pragma: no-cache"); // HTTP/1.0
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$id = $_GET["id"];

$sql = "DELETE FROM tbl_os_defeito_reclamado_constatado where defeito_constatado_reclamado = $id";
$res = pg_exec($con,$sql);
$msg_erro        = pg_errormessage($con);

if (strlen($msg_erro)==0) {
	echo 'ok';
} else {
	echo 'no';
}

?>