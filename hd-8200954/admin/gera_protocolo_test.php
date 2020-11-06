<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="call_center";
include "autentica_admin.php";

$titulo = 'Atendimento interativo';

$res = pg_exec ($con,"BEGIN TRANSACTION");

$sql = "INSERT INTO tbl_hd_chamado (titulo) values ('titulo')";
$res = pg_exec($con,$sql);
$msg_erro = pg_errormessage($con);

if (strlen($msg_erro)==0) {
	$res    = pg_exec ($con,"SELECT CURRVAL ('seq_hd_chamado')");
	$hd_chamado = pg_result ($res,0,0);
	$msg_erro = pg_errormessage($con);
}

$sql = "INSERT INTO tbl_hd_chamado_extra (hd_chamado) values ($hd_chamado)";
$res = pg_exec($con,$sql);
$msg_erro = pg_errormessage($con);

if (strlen($msg_erro)==0) {
	$res = pg_exec ($con,"COMMIT TRANSACTION");
	echo 'sim|'.$hd_chamado;
} else {
	$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	echo 'nao|';
}

?>
