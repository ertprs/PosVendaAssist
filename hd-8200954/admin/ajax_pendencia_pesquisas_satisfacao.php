<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';

if (!empty($_POST)) {
	switch ($_POST['ajax']) {
		case 'loadPesquisas':
			$qPesquisas = "
				SELECT
					tbl_os.hd_chamado,
					TO_CHAR(tbl_resposta.data_input, 'DD/MM/YYYY') AS data
				FROM tbl_pesquisa
				JOIN tbl_resposta ON tbl_resposta.pesquisa = tbl_pesquisa.pesquisa
				JOIN tbl_os ON tbl_os.os = tbl_resposta.os AND tbl_os.fabrica = {$login_fabrica}
				WHERE tbl_pesquisa.fabrica = {$login_fabrica}
				AND tbl_pesquisa.categoria = 'os'
				AND CURRENT_DATE > tbl_os.finalizada + INTERVAL '72 HOURS'
				AND tbl_resposta.sem_resposta IS TRUE
			";
			$rPesquisas = pg_query($con, $qPesquisas);

			if (pg_num_rows($rPesquisas) == 0 || strlen(pg_last_error()) > 0) {
				$response = ['exception' => 'Nenhuma pesquisa encontrada'];
			} else {
				$pesquisas = pg_fetch_all($rPesquisas);
				$response = [
					'quantidade' => count($pesquisas),
					'pesquisas' => $pesquisas,
				];
			}

			echo json_encode($response);
			break;
	}
	exit;
}
?>
