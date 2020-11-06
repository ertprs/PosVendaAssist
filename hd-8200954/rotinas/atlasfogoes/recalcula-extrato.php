<?php

if (empty($argv[1])) {
	die("Informe a data do extrato\n");
}

include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';

$fabrica = 74;
$data = $argv[1];
$linha_fogo = 541; // bonificação

$sql = "select extrato, posto 
		from tbl_extrato
		where fabrica = $fabrica
		and data_geracao >= '$data 00:00:00'
		and aprovado is null and liberado is null";
$qry = pg_query($con, $sql);

if (pg_num_rows($qry) == 0) {
	die("Nenhum extrato encontrado\n");
}

$resP = pg_query($con,"BEGIN TRANSACTION");
$msg_erro = '';

while ($fetch = pg_fetch_assoc($qry)) {
	$extrato = $fetch["extrato"];
	$posto = $fetch["posto"];

	echo "$extrato - $posto\n";

	$sql_extrato = "SELECT fn_calcula_extrato($fabrica, $extrato);";
	$res_extrato = pg_query($con,$sql_extrato);
	$msg_erro = pg_last_error($con);

	$sql_os_fogo = "
			SELECT os,
				data_abertura, 
				data_conserto,
				data_conserto::date - data_abertura as dias_corridos
			FROM tbl_os_extra
			JOIN tbl_os USING(os)
			JOIN tbl_extrato USING(extrato)
			JOIN tbl_produto USING(produto)
			JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha
			WHERE tbl_extrato.extrato = $extrato
			AND tbl_linha.linha = $linha_fogo
			AND cancelada IS NOT TRUE
			AND hd_chamado IS NOT NULL";
	$qry_os_fogo = pg_query($con, $sql_os_fogo);

	if (pg_num_rows($qry_os_fogo) > 0) {
		$qry_bonificacoes = pg_query(
				$con,
				"SELECT parametros_adicionais
				FROM tbl_posto_fabrica
				WHERE posto = $posto
				AND fabrica = $fabrica"
			);

		$parametros_posto = json_decode(pg_fetch_result($qry_bonificacoes, 0, 'parametros_adicionais'), true);
		$bonificacoes = array();

		if (!empty($parametros_posto) and array_key_exists("bonificacoes", $parametros_posto)) {
			$bonificacoes = array();

			foreach ($parametros_posto["bonificacoes"] as $k => $bon) {
				$bonificacoes[] = array(
						"bonificacao" => $k + 1,
						"de" => "{$bon["de"]}",
						"ate" => "{$bon["ate"]}",
						"range" => range($bon["de"], $bon["ate"]),
						"valor" => $bon["valor"]
					);
			}

			while ($assoc = pg_fetch_assoc($qry_os_fogo)) {
				$data_abertura = $assoc["data_abertura"];
				$data_conserto = $assoc["data_conserto"];
				$dias_corridos = (int) $assoc["dias_corridos"];

				if (empty($data_conserto)) {
					continue;
				}

				$sql_fer = "SELECT COUNT(feriado) AS feriados
					FROM tbl_feriado
					WHERE data BETWEEN '{$data_abertura}' AND '{$data_conserto}'
					AND fabrica = $fabrica
					AND DATE_PART('dow', data) NOT IN(0, 6)";
				$qry_fer = pg_query($con, $sql_fer);
				$feriados = pg_fetch_result($qry_fer, 0, 'feriados');

				$sql_dw = "SELECT
					DATE_PART('w', DATE '{$data_abertura}') AS wa,
					DATE_PART('w', DATE '{$data_conserto}') AS wc,
					DATE_PART('dow', DATE '{$data_abertura}') AS a,
					DATE_PART('dow', DATE '{$data_conserto}') AS c";

				$dows = pg_query($con, $sql_dw);

				$dow_a = (int) pg_fetch_result($dows, 0, 'a');
				$dow_c = (int) pg_fetch_result($dows, 0, 'c');
				$dow_wa = (int) pg_fetch_result($dows, 0, 'wa');
				$dow_wc = (int) pg_fetch_result($dows, 0, 'wc');

				if ($dow_wa == $dow_wc) {
					$dias = $dias_corridos;

					if ($dow_c == 6) {
						$dias--;
					}
				} else {
					$dias = $dias_corridos - (($dow_wc - $dow_wa) * 2);

					if ($dow_a == 6) {
						$dias++;
					}

					if ($dow_c == 6) {
						$dias--;
					} elseif ($dow_c == 0) {
						$dias -= 2;
					}
				}

				$dias -= $feriados;

				foreach ($bonificacoes as $boni) {
					if (in_array($dias, $boni["range"])) {
						echo "Bonificacao: {$assoc["os"]} - $data_abertura - $data_conserto - $dias\n";

						$qry_ca = pg_query(
								$con,
								"SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = {$assoc["os"]}"
							);

						$campos_adicionais = array();

						if (pg_num_rows($qry_ca) == 1) {
							$campos_adicionais = json_decode(pg_fetch_result($qry_ca, 0, 'campos_adicionais'), true);
						}

						$campos_adicionais["bonificacao"] = array(
								"de" => $boni["de"],
								"ate" => $boni["ate"],
								"valor" => $boni["valor"],
								"bonificacao" => $boni["bonificacao"]
							);

						$json_adicionais = json_encode($campos_adicionais);

						$up_ce = pg_query(
								$con,
								"UPDATE tbl_os_campo_extra
								SET campos_adicionais = '{$json_adicionais}'
								WHERE os = {$assoc["os"]}"
								);

						if (pg_affected_rows($up_ce) > 1) {
							$msg_erro = "Erro ao gravar bonificação na OS";
						}

						$up_mo = pg_query(
								$con,
								"UPDATE tbl_os
								SET mao_de_obra = {$boni["valor"]}
								WHERE os = {$assoc["os"]}"
								);

						if (pg_affected_rows($up_mo) > 1) {
							$msg_erro = "Erro ao gravar valor de bonificação na OS";
						}
					}
				}
			}

			if (strlen($msg_erro) == 0) {
				// Totaliza o extrato novamente pois bonificacao pode alterar M.O.
				$sql_extrato = "SELECT fn_totaliza_extrato($fabrica, $extrato);";
				$res_extrato = pg_query($con,$sql_extrato);
				$msg_erro = pg_last_error($con);
			}

		}
	}
}

if (strlen($msg_erro) > 0) {
	$resP = pg_query('ROLLBACK;');
	echo "$msg_erro\n";
} else {
	echo "COMMIT\n";
	$resP = pg_query('COMMIT;');
}
