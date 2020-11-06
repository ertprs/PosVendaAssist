<?php

require "../../../dbconfig.php";
require "../../../includes/dbconnect-inc.php";
require "../../../autentica_admin.php";

if ($_GET["exporta_pedido_manual"]) {
	try {
		$pedido = $_GET["pedido"];

		if(empty($pedido)) {
			throw new Exception("Pedido nÃ£o informado");
		}

		$sql = "SELECT exportado FROM tbl_pedido WHERE fabrica = {$login_fabrica} AND pedido = {$pedido}";
		$res = pg_query($con, $sql);

		if (!pg_num_rows($res)) {
			throw new Exception(utf8_encode("Pedido não encontrado"));
		}

		$exportado = pg_fetch_result($res, 0, "exportado");

		if (!empty($exportado)) {
			throw new Exception(utf8_encode("Pedido já exportado"));
		}

		system("php ../../../rotinas/mondial/exporta-pedido.php {$pedido}", $ret);
	} catch(Exception $e) {
		$retorno = array("erro" => utf8_encode($e->getMessage()));
	}

	if (isset($retorno)) {
		echo json_encode($retorno);
	}
}
