<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

if ($_GET) {
	$peca = $_GET;

	$sql = "SELECT
				tbl_peca.descricao, tbl_tabela_item.preco, tbl_peca.ipi, tbl_peca.marca
			FROM tbl_tabela_item
			JOIN tbl_tabela USING (tabela)
			JOIN tbl_peca USING (peca)
			WHERE
			referencia = '{$peca['ref']}'
			AND tbl_peca.fabrica = $login_fabrica
			AND tbl_tabela.fabrica = $login_fabrica
			AND (tbl_peca.marca = {$peca['marca_emp']} OR tbl_peca.marca IS NULL)";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		$result = pg_fetch_object($res);

		$peca["desc"]  = utf8_encode($result->descricao);
		$peca["preco"] = $result->preco;
		$peca["ipi"]   = $result->ipi;
		$peca["marca"] = $result->marca;

		echo json_encode($peca);
	} else {
		echo json_encode(array("erro" => "not found"));
	}
}
?>