<?php
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
if (empty($_REQUEST["fabrica"])) { 
include '../autentica_usuario.php';
} else {
$login_fabrica = $_REQUEST["fabrica"];
}
include_once '../class/simple_rest.class.php';


$ajax = $_REQUEST['ajax'];

if(!empty($login_fabrica)) {
	$cond = " AND fabrica = $login_fabrica ";
}

switch ($ajax) {
	case "verifyObjectIdOnly":
	$objectId = $_REQUEST['objectId'];	
	$context = $_REQUEST['contexto'];
	$hashTemp = $_REQUEST['hashTemp'];

	if($hashTemp=="true"){
		$sql = "SELECT tdocs_id, referencia, obs FROM tbl_tdocs WHERE hash_temp = '$objectId' AND situacao = 'ativo' $cond ";
		
	}else{
		$sql = "SELECT tdocs_id, referencia, obs FROM tbl_tdocs WHERE referencia_id = $objectId AND referencia = '$contexto' AND contexto = '$contexto' AND situacao = 'ativo' $cond ";
	}	
	

	$res = pg_query($con,$sql);
	$res = pg_fetch_all($res);

	if(count($res)>0 && $res != false){
		echo json_encode($res);	
		exit;
	}
	echo json_encode(array("exception" => "Nenhum arquivo encontrado","code" => 404));
	break;
	case 'verifyObjectId':

	$objectId = $_REQUEST['objectId'];
	$context = $_REQUEST['context'];
	$hashTemp = $_REQUEST['hashTemp'];
	if($hashTemp=="true"){
		$sql = "SELECT tdocs_id, referencia, obs FROM tbl_tdocs WHERE hash_temp = '$objectId' $cond ";
	}else{
		$sql = "SELECT tdocs_id, referencia, obs FROM tbl_tdocs WHERE referencia_id = 0 AND referencia = '$objectId' AND contexto = '$context' $cond ";
	}	

	$res = pg_query($con,$sql);
	$res = pg_fetch_all($res);

	if(count($res)>0 && $res != false){
		echo json_encode($res);	
		exit;
	}
	echo json_encode(array("exception" => "Nenhum arquivo encontrado","code" => 404));
	exit;	
	break;

	case 'removeImage':
		$objectId = $_REQUEST['objectId'];
		$context = $_REQUEST['context'];

		$sql =  "DELETE FROM tbl_tdocs WHERE referencia_id = 0 AND tdocs_id = '$objectId' AND contexto = '$context'";

		
		$res = pg_query($con, $sql);
		if(!pg_last_error($con)){
			echo json_encode(array("res" => "ok"));
			exit;
		}else{
			echo json_encode(array("res" => "erro","erro" => pg_last_error($con)));
			exit;
		}	
	break;
}
