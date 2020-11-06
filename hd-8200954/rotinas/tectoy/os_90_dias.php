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

	$sql = "SELECT os
			FROM tbl_os
			WHERE fabrica = {$fabrica}
			AND finalizada IS NULL
			AND excluida IS NOT TRUE
			AND (data_abertura + INTERVAL '90 days') < CURRENT_DATE";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		while ($result = pg_fetch_object($res)) {
			pg_query($con, "BEGIN");

			$sql_df = "UPDATE tbl_os 
					   SET data_fechamento = CURRENT_TIMESTAMP, finalizada = CURRENT_TIMESTAMP, status_checkpoint = 9
					   WHERE os = {$result->os} 
					   AND fabrica = {$fabrica}";
			$res_df = pg_query($con, $sql_df);

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
		Log::envia_email($vet, 'Log - Finaliza OS 90 abertas a mais de 90 dias', implode("\n", $msg_erro));
	}
} catch (Exception $e) {
	Log::envia_email($vet, 'Log - Finaliza OS 90 abertas a mais de 90 dias', $e->getMessage());
}

?>
