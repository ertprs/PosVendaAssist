<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "gerencia,call_center";

include 'autentica_admin.php';
include 'funcoes.php';

if($_GET["lista_etiqueta"]){

	$dados["lista_etiqueta"] = $_GET["lista_etiqueta"];

	$imprimirEtiqueta = new \Posvenda\ImprimirEtiqueta($login_fabrica);

	try{
		$tipos = $imprimirEtiqueta->imprimirEtiquetaPdf($dados);
	}catch(Exception $e){
		exit($e->getMessage());	
	}
	
	echo json_encode($tipos);
	exit;
}
?>
