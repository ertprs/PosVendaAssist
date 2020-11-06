 <?php
include __DIR__.'/dbconfig.php';
include __DIR__.'/includes/dbconnect-inc.php';
include __DIR__.'/autentica_usuario.php';

header("Content-Type: application/json");

$id = (int) $_POST["tecnico"];

if(!empty($id)){
	$sql = "SELECT tecnico, ativo from tbl_tecnico where tecnico = $id and fabrica = $login_fabrica";
	$res = pg_query($con,$sql);

	$ativo	= pg_fetch_result($res, 0, 'ativo');

	if ($ativo == 't') {

		$sql = "UPDATE tbl_tecnico SET ativo = false WHERE tecnico = $id and fabrica = $login_fabrica";
	 
		//echo '{"sql": "' .  $sql . '"}';
		$res = pg_query($con,$sql);

		if (!pg_last_error($con)) {
			echo '{"result": "false"}'; 
	 	} else {
	 		echo '{"result": "erro"}'; 
	 	}
	}else{
		$sql = "UPDATE tbl_tecnico SET ativo = true WHERE tecnico = $id and fabrica = $login_fabrica";
	 
		//echo '{"sql": "' .  $sql . '"}';
		$res = pg_query($con,$sql);

		if (!pg_last_error($con)) {
			echo '{"result": "true"}'; 
	 	} else {
	 		echo '{"result": "erro"}'; 
	 	}
	} 	
}else {
	echo '{"result": "erro"}'; 
}
