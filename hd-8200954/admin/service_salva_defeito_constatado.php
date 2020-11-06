<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

	if( $_POST['consultaValue'] == "Inativar"){
		$sqlDelete = "UPDATE tbl_diagnostico SET ativo = FALSE WHERE diagnostico = ".$_POST['diagnosticoId'].";";
		if(pg_query($con, $sqlDelete)){
				echo 1;
				unset($_POST);
			}else{
				echo 2;
			}
	}

	if( $_POST['consultaValue'] == "Ativar"){
		$sqlDelete = "UPDATE tbl_diagnostico SET ativo = TRUE WHERE diagnostico = ".$_POST['diagnosticoId'].";";
		if(pg_query($con, $sqlDelete)){
				echo 4;
				unset($_POST);
			}else{
				echo 2;
			}
	}


	if( $_POST['consultaValue'] == "Gravar"){
		$sqlUpdate = "UPDATE tbl_diagnostico SET mao_de_obra = ".str_replace(",",".",str_replace(".", "", $_POST['valorMaoDeObra']))." WHERE diagnostico = ".$_POST['diagnosticoId'].";";

		if(pg_query($con, $sqlUpdate)){
				echo 3;
				unset($_POST);
			}else{
				echo 2;
			}
	}


?> 
