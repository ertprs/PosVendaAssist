<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_admin.php";

$cpf = str_replace(".", "", $_POST['cpf']);
$cpf = str_replace("-", "", $cpf);

$ressarcimentoVerf = NULL;
$sua_os_array = array();
$hd_chamado_array = array();
$counterFetch = 1;

$sqlVerfCpf = "SELECT tbl_ressarcimento.ressarcimento,tbl_ressarcimento.hd_chamado,tbl_ressarcimento.os as sua_os FROM tbl_ressarcimento
			   WHERE tbl_ressarcimento.cpf = '".$cpf."' AND tbl_ressarcimento.fabrica = '".$login_fabrica."' ORDER BY ressarcimento DESC";

$resVerf = pg_query($con, $sqlVerfCpf);

$fetchAll = pg_fetch_all($resVerf);
$qtFetch = count($fetchAll);


foreach ($fetchAll as $value) {
		if(!empty($value['hd_chamado'])){
			array_push($hd_chamado_array, $value['hd_chamado']);	
		}

		if(!empty($value['sua_os'])){
			array_push($sua_os_array, $value['sua_os']);
		}
	$counterFetch++;
	
}

$ressarcimentoVerf = pg_fetch_result($resVerf, 0, ressarcimento);
if(!empty($ressarcimentoVerf)){
	$status = 1;
}else{
	$status = 2;
}

if(!empty($sua_os_array) && !empty($hd_chamado_array)){
	$arrayReturn = array(
	"status"=>$status,
	"hdchamado"=>implode(", ",$hd_chamado_array),
	"suaos"=>implode(", ",$sua_os_array)
	);
}

if(empty($sua_os_array)){
	$arrayReturn = array(
	"status"=>$status,
	"hdchamado"=>implode(", ",$hd_chamado_array)
	);
}

if(empty($hd_chamado_array)){
	$arrayReturn = array(
	"status"=>$status,
	"suaos"=>implode(", ",$sua_os_array)
	);
}

if(empty($hd_chamado_array) && empty($sua_os_array)){
	$arrayReturn = array(
	"status"=>$status
	);
}

echo json_encode($arrayReturn);

?>