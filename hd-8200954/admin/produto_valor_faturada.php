<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "call_center";
include 'autentica_admin.php';

$os = $_GET['os'];

if(strlen($os) > 0) {
	$sql = " SELECT total_troca
			FROM tbl_os_troca
			WHERE os = $os ";
	$res = pg_query($con,$sql);
	if(pg_num_rows($res) > 0){
		$total_troca = pg_fetch_result($res,0,total_troca);
		$total_troca = number_format($total_troca,2,",",".");
	}
}

?>

<p style='text-align:center;color:red;font-weight:bold'>O valor da troca faturada desta Ordem de Serviço é de R$ <?=$total_troca?></p>

