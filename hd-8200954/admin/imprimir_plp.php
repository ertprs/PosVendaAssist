<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "gerencia,call_center";

include 'autentica_admin.php';
include 'funcoes.php';

if($_GET['idplp']){
	$idplp = $_GET['idplp'];
	
	$correios = new \Posvenda\Sigep($login_fabrica);
	$plp      = $correios->imprimirPLP($idplp,$cartao_postagem);
	exit;
}
?>