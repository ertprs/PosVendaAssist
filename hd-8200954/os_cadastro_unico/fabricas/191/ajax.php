<?php

if (isset($_POST["ajax_mo_solucao"])) {

	$solucao = $_POST["solucao"];
	$produto = $_POST["produto"];

	if(!empty($solucao) AND !empty($produto)){

		$sql = "SELECT tbl_diagnostico.mao_de_obra
				FROM tbl_diagnostico
				INNER JOIN tbl_produto ON tbl_diagnostico.familia = tbl_produto.familia AND tbl_produto.fabrica_i = {$login_fabrica}
				WHERE tbl_diagnostico.solucao = {$solucao}
				AND tbl_produto.produto = {$produto}
				AND tbl_diagnostico.fabrica = {$login_fabrica}";
		$resMo = pg_query($con,$sql);

		if(pg_num_rows($resMo) > 0){
			$mao_de_obra = pg_fetch_result($resMo, 0, 'mao_de_obra');
		}else{
			$mao_de_obra = "0,00";
		}

		echo json_encode(["success" => "ok","mao_obra" => $mao_de_obra]);
		exit;
	}

}

?>