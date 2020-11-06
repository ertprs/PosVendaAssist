<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$acao    = $_POST["acao"];
$os      = $_POST["os"];
$extrato = $_POST["extrato"];
$programa_insert = $_SERVER['PHP_SELF'];

$sql = "SELECT os FROM tbl_os WHERE fabrica = {$login_fabrica} AND os = {$os}";
$res = pg_query($con, $sql);

if (pg_num_rows($res) > 0) {
	switch ($acao) {
		case 'aprova_os_extrato':
			$sql = "UPDATE tbl_os_extra SET os_faturamento = 1 WHERE os = {$os}";
			$res = pg_query($con, $sql);

			if (strlen(pg_last_error()) > 0) {
				$retorno = array("erro" => "Erro ao aprovar OS");
			} else {
				$retorno = array("ok" => true);
			}
			break;
		
		case 'reprova_os_extrato':
			$sql = "UPDATE tbl_os_extra SET extrato = 0 WHERE os = {$os}";
			$res = pg_query($con, $sql);

			if (strlen(pg_last_error()) > 0) {
				$retorno = array("erro" => "Erro ao reprovar OS");
			} else {
				$sql = "SELECT hd_chamado FROM tbl_os WHERE os = {$os} AND fabrica = {$login_fabrica}";
				$res = pg_query($con, $sql);

				$atendimento = pg_fetch_result($res, 0, "hd_chamado");

				$sql = "INSERT INTO tbl_os_interacao 
						(programa,os, data, admin, comentario, fabrica, interno) 
						VALUES 
						({'$programa_insert'},{$os}, current_timestamp, {$login_admin}, 'OS desassociada do atendimento {$atendimento}, reprovada para entrar em extrato na auditoria de os desassociada', {$login_fabrica}, TRUE)";
				$res = pg_query($con, $sql);

				$retorno = array("ok" => true);
			}
			break;

		case 'exclui_os_extrato':
			$sql = "SELECT extrato FROM tbl_extrato WHERE fabrica = {$login_fabrica} AND extrato = {$extrato}";
			$res = pg_query($con, $sql);

			if (pg_num_rows($res) > 0) {
				$sql = "UPDATE tbl_os_extra SET extrato = 0 WHERE os = {$os}";
				$res = pg_query($con, $sql);

				if (strlen(pg_last_error()) > 0) {
					$retorno = array("erro" => "Erro ao excluir OS do extrato");
				} else {
					$sql = "SELECT fn_calcula_extrato({$login_fabrica}, {$extrato})";
					$res = pg_query($con, $sql);

					if (strlen(pg_last_error()) > 0) {
						$retorno = array("erro" => "Erro ao excluir OS do extrato");
					} else {
						$sql = "SELECT hd_chamado FROM tbl_os WHERE os = {$os} AND fabrica = {$login_fabrica}";
						$res = pg_query($con, $sql);

						$atendimento = pg_fetch_result($res, 0, "hd_chamado");

						$sql = "INSERT INTO tbl_os_interacao 
								(programa,os, data, admin, comentario, fabrica, interno) 
								VALUES 
								({'$programa_insert'},{$os}, current_timestamp, {$login_admin}, 'OS desassociada do atendimento {$atendimento}, excluida do extrato {$extrato} na auditoria de os desassociada', {$login_fabrica}, TRUE)";
						$res = pg_query($con, $sql);

						$retorno = array("ok" => true);
					}
				}
			} else {
				$retorno = array("erro" => "Nenhum extrato selecionado");
			}
			
			break;
	}
} else {
	$retorno = array("erro" => "Nenhuma OS selecionada");
}

exit(json_encode($retorno));
?>