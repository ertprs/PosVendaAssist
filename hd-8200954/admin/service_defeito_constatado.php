<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$tipo = $_POST['tipo'];
$grupo = $_POST['grupo'];

if($tipo == 'ajax'){
	$grupo 		 = $_POST['grupo'];
	$diagnostico = $_POST['diagnostico'];

	if(!empty($grupo)){

		$sql = "SELECT 
					defeito_constatado, 
					codigo, 
					descricao 
				FROM tbl_defeito_constatado 
				WHERE
					fabrica = {$login_fabrica}
					AND defeito_constatado_grupo = {$grupo}
					AND ativo
				ORDER BY descricao ASC;";
		$res = pg_query($con, $sql);

		if(pg_num_rows($res)){
			
			for ($i=0; $i < pg_num_rows($res); $i++) { 
				extract(pg_fetch_array($res));

				echo "<option value='{$defeito_constatado}' label='{$codigo} - {$descricao}'>{$descricao}</option>";
			}
		}
	}	

	if(!empty($diagnostico)){ //passa o diagnostico via ajax somente para excluir os dados
		$sql = "DELETE FROM tbl_diagnostico WHERE diagnostico = {$diagnostico};";
		if(pg_query($con, $sql))
			echo  1;
	}

}

?>