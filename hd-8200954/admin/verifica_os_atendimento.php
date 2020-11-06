<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$hd_chamado = $_POST["hd_chamado"];

if (!empty($hd_chamado)) {
	$sql = "SELECT hd_chamado FROM tbl_hd_chamado WHERE fabrica = {$login_fabrica} AND hd_chamado = {$hd_chamado}";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		$sql = "SELECT tbl_os.os 
				FROM tbl_os 
				JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.os = tbl_os.os 
				WHERE tbl_os.fabrica = {$login_fabrica} 
				AND tbl_os.hd_chamado = {$hd_chamado}
				LIMIT 1";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			$retorno = array("os" => pg_fetch_result($res, 0, "os"));
		} else {
			$retorno = array("sem_os" => true);
		}
	} else {
		$retorno = array("erro" => utf8_encode("Atendimento não encontrado"));
	}
} else {
	$retorno = array("erro" => utf8_encode("Atendimento não encontrado"));
}

echo json_encode($retorno);

exit;

?>