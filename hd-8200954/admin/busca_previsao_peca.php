<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

if(isset($_POST['busca_previsao_peca']) == true){

	$referencia_peca = $_POST['referencia_peca'];

	$sql_peca = "SELECT peca, referencia, parametros_adicionais FROM tbl_peca WHERE fabrica = $login_fabrica and referencia = '$referencia_peca' ";
	$res_peca = pg_query($con, $sql_peca);

	if(pg_num_rows($res_peca)>0){
		$parametros_adicionais = pg_fetch_result($res_peca, 0, 'parametros_adicionais');
		$parametros_adicionais = json_decode($parametros_adicionais, true);
		$previsao = $parametros_adicionais['previsao'];
	}
	if(strlen(trim($previsao))==0){
		$previsao = "";
	}else{
		$previsao = mostra_data($previsao);
	}

	echo json_encode(array("previsao" => $previsao));
	exit;
}




?>


