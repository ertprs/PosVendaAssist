<?php

if (isset($_POST["dados_numero_serie"])) {

	$numero_serie = trim($_POST["numero_serie"]);
	$produto_referencia = trim($_POST["produto_referencia"]);

	$sql = "
		SELECT
			tbl_numero_serie.serie,
			tbl_numero_serie.produto,
			tbl_numero_serie.ordem,
			tbl_numero_serie.data_fabricacao,
			tbl_numero_serie.data_venda,
			tbl_numero_serie.bloqueada_garantia,
			tbl_produto.referencia,
			tbl_produto.descricao,
			tbl_produto.linha,
			tbl_produto.voltagem
		FROM tbl_numero_serie
		JOIN tbl_produto ON tbl_produto.produto = tbl_numero_serie.produto AND tbl_produto.fabrica_i = {$login_fabrica}
		WHERE tbl_numero_serie.serie = '{$numero_serie}'
		AND tbl_produto.referencia = '{$produto_referencia}'
		AND tbl_numero_serie.fabrica = {$login_fabrica};
	";
	
	$res = pg_query($con, $sql);

	if(pg_num_rows($res) > 0){

		list($ano, $mes, $dia) = explode("-", pg_fetch_result($res, 0, data_venda));
		$data_venda = $dia."/".$mes."/".$ano;
		list($ano, $mes, $dia) = explode("-", pg_fetch_result($res, 0, data_fabricacao));
		$data_fabricacao = $dia."/".$mes."/".$ano;

		$data = array(
			"status" => true,
			"data_fabricacao" => $data_fabricacao,
			"data_venda" => $data_venda
		);

	} else {

		$data = array(
			"status" => false,
			"msg_erro" => utf8_encode("Dados do número de série não encontrado")
		);

	}

	echo json_encode($data);
	exit;

}

?>
