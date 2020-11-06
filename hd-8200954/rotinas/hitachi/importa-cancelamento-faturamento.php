<?php

try {
	include dirname(__FILE__)."/../../dbconfig.php";
	include dirname(__FILE__)."/../../includes/dbconnect-inc.php";

	$fabrica = 147;
	$fabrica_nome = "hitachi";

	if ($_serverEnvironment == "production") {
		$arquivos = glob("/home/hitachi/pos-vendas/{$fabrica_nome}-telecontrol/cancelamento/nf-cancelado*", GLOB_BRACE);
	} else {
		$arquivos = glob("/home/william/public_html/treinamento/{$fabrica_nome}/cancelamento*", GLOB_BRACE);
	}

	$data = date("Y-m-d-h-i-s");

	$erro = array();

	if (count($arquivos) > 0) {
		foreach ($arquivos as $arquivo) {
			$conteudo = file_get_contents($arquivo);
			$conteudo = explode("\n", $conteudo);
			$conteudo = array_filter($conteudo);
			foreach ($conteudo as $key => $linha) {
				if (empty($linha)) {
					continue;
				}

				list($notaFiscal, $serie, $postoCnpj) = explode("\t", $linha);

				$notaFiscal = trim($notaFiscal);
				$serie      = trim($serie);
				$postoCnpj  = trim($postoCnpj);

				if (empty($notaFiscal)) {
					$erro[$arquivo][] = "Linha {$key} - nota fiscal nãinformada";
				}

				if (empty($serie)) {
					$erro[$arquivo][] = "Linha {$key} - sée nãinformada";
				}

				if (empty($postoCnpj)) {
					$erro[$arquivo][] = "Linha {$key} - CNPJ do Posto Autorizado nãinformado";
				} else {
					$sql = "SELECT fn_valida_cnpj_cpf('{$postoCnpj}')";
					$res = pg_query($con, $sql);

					if (strlen(pg_last_error()) > 0) {
						$erro[$arquivo][] = "Linha {$key} - CNPJ do Posto Autorizado invádo";
					} else {
						$sql = "SELECT tbl_posto_fabrica.posto 
								FROM tbl_posto 
								INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = {$fabrica}
								WHERE tbl_posto.cnpj = '{$postoCnpj}'";
						$res = pg_query($con, $sql);

						if (!pg_num_rows($res)) {
							$erro[$arquivo][] = "Linha {$key} - Posto Autorizado nãencontrado, cnpj: {$postoCnpj}";
						} else {
							$posto = pg_fetch_result($res, 0, "posto");
						}
					}
				}

				if (count($erro[$arquivo]) > 0) {
					continue;
				} else {
					$notaFiscal = str_pad($notaFiscal,9,"0",STR_PAD_LEFT);
					$sql = "SELECT faturamento
							FROM tbl_faturamento
							WHERE fabrica = {$fabrica}
							AND nota_fiscal = '{$notaFiscal}'
							AND serie = '{$serie}'
							AND posto = {$posto}";
					$res = pg_query($con, $sql);

					if (!pg_num_rows($res)) {
						$erro[$arquivo][] = "Nãfoi encontrado nota fiscal com os seguintes dados: nota fiscal {$notaFiscal}, sée {$serie}, posto {$postoCnpj}";
						continue;
					} else {
						$faturamento = pg_fetch_result($res, 0, "faturamento");

						pg_query($con, "BEGIN");

						$sql = "SELECT 
									tbl_faturamento_item.faturamento_item, 
									tbl_pedido_item.pedido_item, 
									tbl_pedido_item.pedido, 
									tbl_faturamento_item.qtde
								FROM tbl_faturamento_item
								INNER JOIN tbl_pedido ON tbl_pedido.pedido = tbl_faturamento_item.pedido
								WHERE tbl_faturamento_item.faturamento = {$faturamento}";
						$res = pg_query($con, $sql);

						while ($item = pg_fetch_object($res)) {
							$update = "UPDATE tbl_pedido_item SET
											qtde_faturada = 0,
											obs = 'Nota fiscal {$nota_fiscal} cancelada, data cancelamento: ' || TO_CHAR(CURRENT_DATE, 'DD/MM/YYYY')
									   WHERE pedido_item = {$item->pedido_item}
									   AND pedido = {$item->pedido}";
							$resUpdate = pg_query($con, $update);

							if (strlen(pg_last_error()) > 0) {
								$erro[$arquivo][] = "Erro ao cancelar nota fiscal {$notaFiscal}, sée {$serie}, posto {$postoCnpj}";
								break;
							}

							$delete = "DELETE FROM tbl_faturamento_item WHERE faturamento_item = {$item->faturamento_item}";
							$resDelete = pg_query($con, $delete);

							if (strlen(pg_last_error()) > 0) {
								$erro[$arquivo][] = "Erro ao cancelar nota fiscal {$notaFiscal}, sée {$serie}, posto {$postoCnpj}";
								break;
							}

							$select = "SELECT fn_atualiza_status_pedido({$fabrica}, {$item->pedido})";
							$resSelect = pg_query($con, $select);

							if (strlen(pg_last_error()) > 0) {
								$erro[$arquivo][] = "Erro ao cancelar nota fiscal {$notaFiscal}, sée {$serie}, posto {$postoCnpj}";
								break;
							}
						}

						if (count($erro[$arquivo]) > 0) {
							pg_query($con, "ROLLBACK");
							continue;
						} else {
							$update = "UPDATE tbl_faturamento SET fabrica = 0 WHERE faturamento = {$faturamento}";
							$resUpdate = pg_query($con, $update);

							if (strlen(pg_last_error()) > 0) {
								pg_query($con, "ROLLBACK");
								$erro[$arquivo][] = "Erro ao cancelar nota fiscal {$notaFiscal}, sée {$serie}, posto {$postoCnpj}";
								continue;
							} else {
								pg_query($con, "COMMIT");
							}
						}
					}
				}
			}

			system("mkdir /tmp/{$fabrica_nome}/ 2> /dev/null ; chmod 777 /tmp/{$fabrica_nome}/" );
			system("mkdir /tmp/{$fabrica_nome}/nota_fiscal_cancelada/ 2> /dev/null ; chmod 777 /tmp/{$fabrica_nome}/nota_fiscal_cancelada/" );
			system("mkdir /tmp/{$fabrica_nome}/nota_fiscal_cancelada/backup/ 2> /dev/null ; chmod 777 /tmp/{$fabrica_nome}/nota_fiscal_cancelada/backup/" );
			system("mv {$arquivo} /tmp/{$fabrica_nome}/nota_fiscal_cancelada/backup/".basename($arquivo));
		}

		if (count($erro) > 0) {
			system("mkdir /tmp/{$fabrica_nome}/ 2> /dev/null ; chmod 777 /tmp/{$fabrica_nome}/" );
			system("mkdir /tmp/{$fabrica_nome}/nota_fiscal_cancelada/ 2> /dev/null ; chmod 777 /tmp/{$fabrica_nome}/nota_fiscal_cancelada/" );

			$arquivo_erro_nome = "/tmp/{$fabrica_nome}/nota_fiscal_cancelada/{$data}.txt";
			$arquivo_erro = fopen($arquivo_erro_nome, "w");

			foreach ($erro as $arquivo => $erros) {
				fwrite($arquivo_erro, "<br />########## Erro no cancelamento de nota fiscal ##########<br />");
				fwrite($arquivo_erro, "Arquivo: {$arquivo}<br /><br />");
				fwrite($arquivo_erro, implode("<br />", $erros));
				fwrite($arquivo_erro, "<br />###############################################################################################<br />");
				fwrite($arquivo_erro, "<br />");
			}

			fclose($arquivo_erro);

			if ($_serverEnvironment == "production") {
				mail("amaral@hitachi-koki.com.br, helpdesk@telecontrol.com.br", "Telecontrol - Erro no cancelamento de nota fiscal da Hitachi", file_get_contents($arquivo_erro_nome), "MIME-Version: 1.0 \r\nContent-type: text/html; charset=iso-8859-1 \r\n");
			} else {
				mail("guilherme.curcio@telecontrol.com.br", "Telecontrol - Erro no cancelamento de nota fiscal da Hitachi", file_get_contents($arquivo_erro_nome), "MIME-Version: 1.0 \r\nContent-type: text/html; charset=iso-8859-1 \r\n");
			}
		}
	}
} catch (Exception $e) {
	system("mkdir /tmp/{$fabrica_nome}/ 2> /dev/null ; chmod 777 /tmp/{$fabrica_nome}/" );
	system("mkdir /tmp/{$fabrica_nome}/nota_fiscal_cancelada/ 2> /dev/null ; chmod 777 /tmp/{$fabrica_nome}/nota_fiscal_cancelada/" );

	$arquivo_erro_nome = "/tmp/{$fabrica_nome}/nota_fiscal_cancelada/{$data}.txt";
	$arquivo_erro = fopen($arquivo_erro_nome, "w");

	fwrite($arquivo_erro, $e->getMessage());

	fclose($arquivo_erro);

	if ($_serverEnvironment == "production") {
		mail("amaral@hitachi-koki.com.br, helpdesk@telecontrol.com.br", "Telecontrol - Erro no cancelamento de nota fiscal da Hitachi", file_get_contents($arquivo_erro_nome), "MIME-Version: 1.0 \r\nContent-type: text/html; charset=iso-8859-1 \r\n");
	} else {
		mail("guilherme.curcio@telecontrol.com.br", "Telecontrol - Erro no cancelamento de nota fiscal da Hitachi", file_get_contents($arquivo_erro_nome), "MIME-Version: 1.0 \r\nContent-type: text/html; charset=iso-8859-1 \r\n");
	}
}
