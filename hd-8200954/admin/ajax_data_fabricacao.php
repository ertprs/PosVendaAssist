<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';

header("Content-Type: text/html; charset=iso-8859-1");

if ($_POST) {
	$data = $_POST["data"];
	$os_item = $_POST["os_item"];

	if (strlen($data) > 0 && strlen($os_item) > 0) {
		$sql = "SELECT tbl_os_item.parametros_adicionais, tbl_os.data_nf
				FROM tbl_os_item
				JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
				JOIN tbl_os ON tbl_os.os = tbl_os_produto.os AND tbl_os.fabrica = $login_fabrica
				WHERE tbl_os_item.os_item = $os_item";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			$data_nf = pg_fetch_result($res, 0, "data_nf");
			$pa      = pg_fetch_result($res, 0, "parametros_adicionais");

			if (strlen($pa) > 0) {
				$pa = json_decode($pa, true);
			}

			list($d, $m, $y) = explode("/", $data);

			if (!checkdate($m, $d, $y)) {
				$msg["erro"] = "Data Inválida";
				echo json_encode($msg);
				exit;
			} else {
				$data = "{$y}-{$m}-{$d}";
			}

			if (strtotime($data) > strtotime($data_nf)) {
				$msg["erro"] = "Data de fabricação da peça não pode ser maior que data de compra do Produto";
				echo json_encode($msg);
				exit;
			}

			$pa["data_fabricacao"] = $data;
			$pa = json_encode($pa);

			$sql = "UPDATE tbl_os_item 
					SET parametros_adicionais = '$pa' 
					WHERE os_item = $os_item";
			$res = pg_query($con, $sql);

			if (!pg_last_error()) {
				echo "ok";
				exit;
			} else {
				$msg["erro"] = "Erro ao gravar a data de fabricação da peça";
				echo json_encode($msg);
				exit;
			}
		}
	}
}

?>