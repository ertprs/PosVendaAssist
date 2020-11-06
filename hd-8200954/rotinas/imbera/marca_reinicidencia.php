<?php

try {

	/*
	 * Includes
	 */

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	require dirname(__FILE__) . '/../funcoes.php';
	
	/*
	 * Definição
	 */
	$fabrica = 158;
	$data = date('d-m-Y');

	$sql = "
		SELECT
			o.os
		FROM tbl_os o
		JOIN tbl_os_extra oe USING(os)
		WHERE o.fabrica = {$fabrica}
		AND o.finalizada IS NOT NULL
		AND o.data_abertura >= '2017-01-01'
		AND oe.serie_justificativa IS NOT NULL;
	";

	echo $sql;

	$res = pg_query($con, $sql);
	$cont = pg_num_rows($res);

	$s = 0;

	for ($i = 0; $i < $cont; $i++) {
		$os = pg_fetch_result($res, $i, 'os');

		$sql = "
			SELECT
				o.os,
				(SELECT
					xo.os
				FROM tbl_os xo
				JOIN tbl_os_extra xoe USING(os)
				WHERE xo.fabrica = {$fabrica} 
				AND xo.os != o.os
				AND xo.defeito_reclamado = o.defeito_reclamado
				AND xoe.serie_justificativa = oe.serie_justificativa
				AND xo.finalizada IS NOT NULL
				AND xo.data_digitacao BETWEEN o.data_digitacao - INTERVAL '90 day' AND o.data_digitacao
				ORDER BY xo.data_digitacao ASC
				LIMIT 1) AS os_reincidente
			FROM tbl_os o
			JOIN tbl_os_extra oe USING(os)
			WHERE o.fabrica = {$fabrica}
			AND o.os = {$os};
		";

		echo $sql;

		$res_reincidente = pg_query($con, $sql);
		$dados = pg_fetch_all($res_reincidente);

		if (!empty($dados[0]['os_reincidente'])) {
			$justificativa_adicionais = array('reincidencia_reclamado' => $dados[0]['os_reincidente']);
			$justificativa_adicionais = json_encode($justificativa_adicionais);
			$upd = "
				UPDATE tbl_os
				SET justificativa_adicionais = '{$justificativa_adicionais}'
				WHERE fabrica = {$fabrica}
				AND os = {$os};
			";

			echo $upd;

			$res_upd = pg_query($con, $upd);

			if (strlen(pg_last_error()) > 0) {
				throw new Exception("Ocorreu um erro ao gravar a reincidencia para a Ordem: {$os}");
			} else {
				$s++;
			}
		}
	}

	echo "Foram atualizadas {$s} ordens.";

} catch (Exception $e) {
	echo $e->getMessage();
}

?>
