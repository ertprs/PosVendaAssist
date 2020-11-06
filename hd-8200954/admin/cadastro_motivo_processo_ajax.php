 <?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
include 'autentica_admin.php';
include 'funcoes.php';

header("Content-Type: application/json");

$id = (int) $_POST["motivo_processo"];

if(!empty($id)){

	$sql = "SELECT motivo_processo, ativo from tbl_motivo_processo where motivo_processo = $id and fabrica = $login_fabrica";
	$res = pg_query($con,$sql);
//	echo $sql;

	$ativo	= pg_fetch_result($res, 0, 'ativo');

	if ($ativo == 't') {

		$sql = "UPDATE tbl_motivo_processo SET ativo = false WHERE motivo_processo = $id and fabrica = $login_fabrica";
	 
		//echo '{"sql": "' .  $sql . '"}';
		$res = pg_query($con,$sql);

		if (!pg_last_error($con)) {
			echo "success"; 
	 	} else {
	 		echo "error";
	 	}
	}else{
		$sql = "UPDATE tbl_motivo_processo SET ativo = true WHERE motivo_processo = $id and fabrica = $login_fabrica";
	 
		//echo '{"sql": "' .  $sql . '"}';
		$res = pg_query($con,$sql);

		if (!pg_last_error($con)) {
			echo "success"; 
	 	} else {
	 		echo "error";
	 	}
	} 	
}else {
	echo "error";
}
