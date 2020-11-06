<?php
include dirname(__FILE__) . "/../../dbconfig.php";
include dirname(__FILE__) . "/../../includes/dbconnect-inc.php";

$sql = "
	SELECT
	om.os,
	JSON_FIELD('osKof', hcc.dados) AS os_kof,
	TO_CHAR(oe.termino_atendimento, 'DD/MM/YYYY HH24:MI') AS termino_atendimento,
	TO_CHAR(o.data_digitacao, 'DD/MM/YYYY') AS data_abertura,
	om.dados AS json
	FROM tbl_os_mobile om
	INNER JOIN tbl_os o ON o.fabrica = 158 AND o.os = om.os
	INNER JOIN tbl_os_extra oe ON oe.os = o.os
	INNER JOIN tbl_hd_chamado hc ON hc.fabrica = 158 AND hc.hd_chamado = o.hd_chamado
	INNER JOIN tbl_hd_chamado_cockpit hcc ON hcc.fabrica = 158 AND hcc.hd_chamado = hc.hd_chamado
	WHERE om.fabrica = 158
	AND o.finalizada IS NOT NULL
	AND (oe.termino_atendimento >= '2016-12-17 00:00' OR oe.termino_atendimento IS NULL)
	/*AND (oe.termino_atendimento < '2016-12-17 00:00')*/
	ORDER BY o.os ASC, om.os_mobile ASC
	";
$qry = pg_query($con, $sql);

$pecas = array();

foreach (pg_fetch_all($qry) as $row) {
	    $row = (object) $row;

	        $row->json = json_decode($row->json);

	        if (!isset($row->json->pecas) || !count($row->json->pecas)) {
			        continue;
				    }

		    if (!isset($pecas[$row->os])) {
			            $pecas[$row->os] = array(
					    "os_kof"              => $row->os_kof,
					    "data_abertura"       => $row->data_abertura,
					    "termino_atendimento" => $row->termino_atendimento,
					    "pecas"               => array()
										        );
				        }

		    foreach ($row->json->pecas as $peca) {
			            $sqlPeca = "
					                SELECT peca, referencia FROM tbl_peca WHERE fabrica = 158 AND referencia = '{$peca->referencia}'
							        ";
				            $qryPeca = pg_query($con, $sqlPeca);

				            $sqlServico = "
						                SELECT descricao FROM tbl_servico_realizado WHERE fabrica = 158 AND servico_realizado = {$peca->servicoRealizado}
								        ";
					            $qryServico = pg_query($con, $sqlServico);

					            if (!isset($pecas[$row->os]["pecas"][pg_fetch_result($qryPeca, 0, "peca")])) {
							                $pecas[$row->os]["pecas"][pg_fetch_result($qryPeca, 0, "peca")] = array();
									        }

						            $pecas[$row->os]["pecas"][pg_fetch_result($qryPeca, 0, "peca")]["referencia"] = pg_fetch_result($qryPeca, 0, "referencia");
						            $pecas[$row->os]["pecas"][pg_fetch_result($qryPeca, 0, "peca")]["qtde"]       = $peca->qtde;
							            $pecas[$row->os]["pecas"][pg_fetch_result($qryPeca, 0, "peca")]["servico"]    = pg_fetch_result($qryServico, 0, "descricao");

							            if (!isset($pecas[$row->os]["pecas"][pg_fetch_result($qryPeca, 0, "peca")]["gravou_os"])) {
									                $sqlOsItem = "
												                SELECT os_item 
														                FROM tbl_os_item oi 
																                INNER JOIN tbl_os_produto op ON op.os_produto = oi.os_produto
																		                WHERE op.os = {$row->os}
																				                AND oi.peca = ".pg_fetch_result($qryPeca, 0, "peca")."
																						            ";
											            $qryOsItem = pg_query($con, $sqlOsItem);

											            if (pg_num_rows($qryOsItem) > 0) {
													                    $pecas[$row->os]["pecas"][pg_fetch_result($qryPeca, 0, "peca")]["gravou_os"] = true;
															                } else {
																		                $pecas[$row->os]["pecas"][pg_fetch_result($qryPeca, 0, "peca")]["gravou_os"] = false;
																				            }
												            }
								        }
}

system("touch relatorio_pecas_mobile.csv");

file_put_contents("relatorio_pecas_mobile.csv", "os telecontrol;data abertura;os kof;termino atendimento;peca telecontrol;referencia peca;quantidade;servico;lancada em os\n", FILE_APPEND);

foreach ($pecas as $os => $dados) {
	    $dados = (object) $dados;
	        foreach ($dados->pecas as $peca => $peca_dados) {
			        $peca_dados = (object) $peca_dados;
				        
				        $lancado_os = (isset($peca_dados->gravou_os) && $peca_dados->gravou_os == true) ? "SIM" : "NAO";

				        $peca_csv = "{$os};{$dados->data_abertura};{$dados->os_kof};{$dados->termino_atendimento};{$peca};{$peca_dados->referencia};{$peca_dados->qtde};{$peca_dados->servico};{$lancado_os}\n";

					        file_put_contents("relatorio_pecas_mobile.csv", $peca_csv, FILE_APPEND);
					    }
}
