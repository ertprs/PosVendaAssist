<?php
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include '../autentica_admin.php';

if ($_POST["buscaCidade"] == true) {
	$estado = strtoupper($_POST["estado"]);

	if (strlen($estado) > 0) {
		$sql = "SELECT DISTINCT * FROM (
					SELECT UPPER(TO_ASCII(nome, 'LATIN9')) AS cidade FROM tbl_cidade WHERE UPPER(estado) = '{$estado}'
					UNION (
						SELECT UPPER(TO_ASCII(cidade, 'LATIN9')) AS cidade FROM tbl_ibge WHERE UPPER(estado) = '{$estado}'
					)
				) AS cidade
				ORDER BY cidade ASC";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			$cidades = array();

			while ($result = pg_fetch_object($res)) {
				$cidades[] = $result->cidade;
			}

			$retorno = array("cidades" => $cidades);
		} else {
			$retorno = array("erro" => "Nenhuma cidade encontrada para o estado {$estado}");
		}
	} else {
		$retorno = array("erro" => "Nenhum estado selecionado");
	}

	exit(json_encode($retorno));
}

?>