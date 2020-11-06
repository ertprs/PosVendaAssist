<?php

try {

    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require dirname(__FILE__) . '/../funcoes.php';

    $bug         = '';
    $fabrica     = 74;
	$dia_mes     = date('d');
	$dia_fim_mes = date('t');
    #$dia_mes     = "31";
    $dia_extrato = date('Y-m-d H:i:s');
    #$dia_extrato = "2012-07-28 02:00:00";

    $phpCron = new PHPCron($fabrica, __FILE__);
    $phpCron->inicio();

    $vet['fabrica'] = 'atlas';
    $vet['tipo']    = 'extrato';
    $vet['dest']    = 'ronald.santos@telecontrol.com.br';
    $vet['log']     = 2;

    $linha_fogo = 541; // bonificação

    if ($dia_mes == $dia_fim_mes) {

			$sql9 = "SELECT ('$dia_extrato'::date - INTERVAL '1 month' + INTERVAL '14 days')::date";
			$res9 = pg_query($con,$sql9);
			$data_15 = pg_fetch_result($res9, 0, 0);

			/**
			* - Segundo o hd-1232852 de 06/09/2013
			* Devo dividir os extratos entre Linhas com os seguintes ID's
			*
			*/

			$sql = "SELECT  DISTINCT
							posto,
							current_date as data_limite
					FROM    tbl_os
					JOIN    tbl_os_extra USING (os)
					WHERE   tbl_os.fabrica = $fabrica
					AND     tbl_os_extra.extrato    IS NULL
					AND     tbl_os.excluida         IS NOT TRUE
					AND     tbl_os.finalizada::date <=  CURRENT_DATE
					AND     tbl_os.posto            NOT IN (6359)
					GROUP BY posto
					union (
						select posto, current_date from tbl_posto_fabrica where parametros_adicionais ~ E'\"valor_km_fixo\":\".+\"' and credenciamento not in('DESCREDENCIADO') and fabrica = $fabrica
					)
				  ;";
			$res      = pg_query($con, $sql);
			$msg_erro = pg_last_error($con);

			if (pg_num_rows($res) > 0 && strlen($msg_erro) == 0) {

				for ($i = 0; $i < pg_num_rows($res); $i++) {
					$posto          = pg_result($res, $i, 'posto');
					$data_limite    = pg_result($res, $i, 'data_limite');

					$resP = pg_query($con,"BEGIN TRANSACTION");

					$sql_extrato = "SELECT fn_fechamento_extrato ($posto, $fabrica, '$data_limite');";
					$res_extrato = pg_query($con, $sql_extrato);
					$msg_erro = pg_last_error($con);

					if (strlen($msg_erro) == 0) {
						$sql_extrato = "SELECT  extrato
										FROM    tbl_extrato
										WHERE   fabrica = $fabrica
										AND     posto = $posto
										AND     data_geracao::date = CURRENT_DATE";
						$res_extrato = pg_query($con,$sql_extrato);
						$msg_erro = pg_errormessage($con);
						$extrato = pg_fetch_result($res_extrato,0,extrato);

						$sql_extrato = "SELECT fn_calcula_extrato($fabrica, $extrato);";
						$res_extrato = pg_query($con,$sql_extrato);
						$msg_erro = pg_last_error($con);

                        $sql_os_fogo = "SELECT os,
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

									$log_bonific = '';

                                    foreach ($bonificacoes as $boni) {
                                        if (in_array($dias, $boni["range"])) {
											$log_bonific .= "{$assoc["os"]} - $data_abertura - $data_conserto - $dias\n";

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

								if (!empty($log_bonific)) {
									mail("francisco.ambrozio@telecontrol.com.br", "[Atlas] Bonificacao de OS - Extrato $extrato", $log_bonific);
								}

                                if (strlen($msg_erro) == 0) {
                                    // Totaliza o extrato novamente pois bonificacao pode alterar M.O.
                                    $sql_extrato = "SELECT fn_totaliza_extrato($fabrica, $extrato);";
                                    $res_extrato = pg_query($con,$sql_extrato);
                                    $msg_erro = pg_last_error($con);
                                }

                            }
                        }

						if (strlen($msg_erro) == 0) {
							$sql_extrato = "SELECT fn_extrato_recompra($fabrica, $extrato);";
							$res_extrato = pg_query($con,$sql_extrato);
							$msg_erro = pg_last_error($con);
						}
					}

					if(strlen($extrato) > 0){
						$sql_extrato = "UPDATE  tbl_posto_fabrica
										SET     extrato_programado = NULL
										WHERE   fabrica = $fabrica
										AND     posto   = $posto;
						";
						$res_extrato = pg_query($con,$sql_extrato);
						$msg_erro = pg_last_error($con);
					}

					if (strlen($msg_erro) > 0) {

						$resP = pg_query('ROLLBACK;');
						$bug .= $msg_erro;

						Log::log2($vet, $msg_erro);

					} else {

						$resP = pg_query('COMMIT;');

					}

				}

			}

			if (strlen($bug) > 0) {

				Log::envia_email($vet, 'Log - Extrato ATLAS FOGÕES', $bug);

			}

			$phpCron->termino();
	}
} catch (Exception $e) {

    Log::envia_email($data,Date('d/m/Y H:i:s')." - ATLAS FOGÕES - Erro na geração de extrato(gera-extrato.php)", $e->getMessage());

}
?>
