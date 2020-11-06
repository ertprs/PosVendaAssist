<?php

try {
	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	require_once dirname(__FILE__) . '/../funcoes.php';

	$fabrica = 6;

	$vet["fabrica"] = "tectoy";
	$vet["tipo"]    = "finaliza_os";
	$vet["log"]     = 1;
	$vet["dest"]    = "helpdesk@telecontrol.com.br";

	$sql_extrato = "SELECT tbl_os_extra.extrato
					FROM tbl_os
					JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os AND tbl_os_extra.i_fabrica = $fabrica
					JOIN tbl_extrato ON tbl_os_extra.extrato = tbl_extrato.extrato AND tbl_extrato.fabrica = {$fabrica}
					WHERE tbl_os.fabrica = {$fabrica}
					AND tbl_os_extra.extrato IS NOT NULL
					AND tbl_os.finalizada IS NOT NULL
					AND tbl_os.excluida IS NOT TRUE
					AND tbl_extrato.liberado IS NULL
					AND (SELECT count(os_status) FROM tbl_os_status WHERE tbl_os_status.fabrica_status=$fabrica AND tbl_os_status.os = tbl_os.os AND tbl_os_status.status_os IN (14, 13)) > 1
					GROUP BY tbl_os_extra.extrato";
	$res_extrato = pg_query($con, $sql_extrato);
	
	if (pg_num_rows($res_extrato) > 0) {
		$extratos = pg_fetch_all($res_extrato);

		$sql = "SELECT tbl_os.os, tbl_os_extra.extrato
				FROM tbl_os
				JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os AND tbl_os_extra.i_fabrica = $fabrica
				JOIN tbl_extrato ON tbl_os_extra.extrato = tbl_extrato.extrato AND tbl_extrato.fabrica = {$fabrica}
				WHERE tbl_os.fabrica = {$fabrica}
				AND tbl_os_extra.extrato IS NOT NULL
				AND tbl_os.finalizada IS NOT NULL
				AND tbl_os.excluida IS NOT TRUE
				AND tbl_extrato.liberado IS NULL
				AND (SELECT count(os_status) FROM tbl_os_status WHERE tbl_os_status.fabrica_status=$fabrica AND tbl_os_status.os = tbl_os.os AND tbl_os_status.status_os IN (14, 13)) > 1";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			while ($result = pg_fetch_object($res)) {
				pg_query($con, "BEGIN");

				$sqlx = "UPDATE tbl_os_extra SET extrato = null WHERE tbl_os_extra.i_fabrica = $fabrica AND os = {$result->os}";
				$resx = pg_query($con, $sqlx);

				if (pg_last_error()) {
					$msg_erro[] = pg_last_error();
					pg_query($con, "ROLLBACK");
					continue;
				}

				$sqlx = "SELECT fn_exclui_os({$result->os}, {$fabrica})";
				$resx = pg_query($con, $sqlx);

				if (pg_last_error()) {
					$msg_erro[] = pg_last_error();
					pg_query($con, "ROLLBACK");
					continue;
				}

				pg_query($con, "COMMIT");
			}
		}

		foreach ($extratos as $key => $value) {
			$extrato = $value["extrato"];

			pg_query($con, "BEGIN");

			$sqlx = "SELECT fn_calcula_extrato({$fabrica}, {$extrato})";
			$resx = pg_query($con, $sqlx);

			if (pg_last_error()) {
				$msg_erro[] = pg_last_error();
				pg_query($con, "ROLLBACK");
				continue;
			}

			pg_query($con, "COMMIT");
		}
	}

	if (count($msg_erro) > 0) {
		echo implode("\n", $msg_erro);
		Log::envia_email($vet, 'Log - Exclui OS acumulada', implode("\n", $msg_erro));
	}
} catch (Exception $e) {
	Log::envia_email($vet, 'Log - Exclui OS acumulada', $e->getMessage());
}

?>
