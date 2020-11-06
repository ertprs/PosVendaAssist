<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$extrato     = $_GET['extrato'];
$data_coleta = $_GET['data_coleta'];
$linha       = $_GET['linha'];

if (strlen($data_coleta)>0 AND strlen($extrato)>0) {
	$sql = "UPDATE tbl_extrato_extra SET data_coleta = '$data_coleta' WHERE extrato=$extrato";
	$res = @pg_exec ($con,$sql);
	if (strlen (pg_errormessage ($con)) == 0) {
		echo "<ok>Tudo OK</ok>";
		exit;
	}else{
		echo "<erro>" . pg_errormessage ($con) . "|" . $linha . "|data_coletaT" . "</erro>";
		exit;
	}
}

?>