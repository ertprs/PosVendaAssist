<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_admin.php";

if ($_GET["term"]) {
	$term   = utf8_decode($_GET["term"]);
	$estado = $_GET["estado"];

	if (!strlen($term) || !strlen($estado)) {
		exit;
	}

	$limit = "LIMIT 21";

	$sql = "SELECT DISTINCT * FROM (
				SELECT UPPER(fn_retira_especiais(nome)) AS cidade FROM tbl_cidade WHERE UPPER(fn_retira_especiais(nome)) ~ UPPER(TO_ASCII('{$term}', 'LATIN9')) AND UPPER(estado) = UPPER('{$estado}')
				UNION (
					SELECT UPPER(fn_retira_especiais(cidade)) AS cidade FROM tbl_ibge WHERE UPPER(fn_retira_especiais(cidade)) ~ UPPER(fn_retira_especiais('{$term}')) AND UPPER(estado) = UPPER('{$estado}')
				)
				{$limit}
			) AS cidade";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		for ($i = 0; $i < pg_num_rows($res); $i++ ){
			$resultado[$i]["cidade"]  = utf8_encode(pg_fetch_result($res, $i, "cidade"));
		}
	}

	echo json_encode($resultado);
}

exit;
?>
