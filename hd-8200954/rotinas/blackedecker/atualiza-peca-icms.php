<?php
/**
 * atualiza-peca-icms.php
 */

include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require dirname(__FILE__) . '/../funcoes.php';

$fabrica = 1;
$peca    = null;

$phpCron = new PHPCron($fabrica, __FILE__); 
$phpCron->inicio();

if ($argv[1]) {
	$peca = $argv[1];
}

$aliquotas = array(
		0 => array (
				'estados' => array(
						'AC', 'AL', 'AM', 'AP', 'BA', 'CE', 'DF', 
						'ES', 'GO', 'MA', 'MT', 'MS', 'PA', 'PB', 
						'PE', 'PI', 'RO', 'RN', 'RR', 'SE', 'TO'
					),
				'indices' => array(
						0 => 7,
						1 => 4,
						2 => 4,
						3 => 4,
						5 => 7,
						8 => 4
					)
			),
		1 => array (
				'estados' => array(
						'PR', 'RJ', 'RS', 'SC', 'SP'
					),
				'indices' => array(
						0 => 12,
						1 => 4,
						2 => 4,
						3 => 4,
						5 => 12,
						8 => 4
					)
			)
	);

$sql = "SELECT peca, classificacao_fiscal FROM tbl_peca WHERE fabrica = $fabrica";

if ($peca) {
	$sql.= " AND peca = $peca";
}

$sql.= " AND classificacao_fiscal IN ('0', '1', '2', '3', '5','8')";

$query = pg_query($con, $sql);

if (pg_num_rows($query) > 0) {

	while ($fetch = pg_fetch_assoc($query)) {
		$begin = pg_query($con, "BEGIN TRANSACTION");
		$peca                 = $fetch['peca'];
		$classificacao_fiscal = $fetch['classificacao_fiscal'];

		// Ver importa-pecas.php que tem o insert de estado MG
		$delete = "DELETE FROM tbl_peca_icms WHERE peca = $peca AND fabrica = $fabrica AND estado_destino <> 'MG'";
		$exec = pg_query($con, $delete);
		$rows = pg_affected_rows($exec);

		// echo $rows , "\n";
		if ($rows > 27) {
			$begin = pg_query($con, "ROLLBACK TRANSACTION");
			continue;
		} else {
			$commit = pg_query($con, "COMMIT TRANSACTION"); 
		}

		foreach ($aliquotas as $key => $value) {
			foreach ($value['estados'] as $estado) {
				$indice = $value['indices'][$classificacao_fiscal];
				
				$insert = "INSERT INTO tbl_peca_icms (
								fabrica, peca, codigo, estado_destino, indice
							) VALUES (
								$fabrica, $peca, '$classificacao_fiscal', '$estado', $indice
							)";
				$qry_insert = pg_query($con, $insert);
			}
		}
	}
}

$phpCron->termino();
