<?php

try {
	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	include dirname(__FILE__) . '/../funcoes.php';

	if ($_serverEnvironment == "production") {
                define("ENV", "prod");
        } else {
                define("ENV", "dev");
        }

	$login_fabrica = 143;
	$fabrica_nome  = "wackerneuson";
	$msg_erro      = array();

	ini_set('default_socket_timeout', 800);

	if (ENV == "prod") {
		$soap = new SoapClient("http://201.91.139.164:8080/g5-senior-services/sapiens_Synccom_senior_g5_co_wacker_consulta_notasfiscais?wsdl", array("trace" => 1, "exception" => 1));
	} else {
		$soap = new SoapClient("http://187.87.251.133:8080/g5-senior-services/sapiens_Synccom_senior_g5_co_wacker_consulta_notasfiscais?wsdl", array("trace" => 1, "exception" => 1));
	}

	$argumentos = array(
		"user"       => "suporte",
		"password"   => "suporte",
		"encryption" => "0",
		"parameters" => array(
			"dadosGerais" => array(
				"codEmp" => 1,
				"codFil" => 1,
				"datIni" => date("d/m/Y", strtotime("-10 day")),
				"datFim" => date("d/m/Y")
			)
		)
    );

    $metodo = "ConsultarNotas";

    $soapResult = $soap->__soapCall($metodo, $argumentos);

    if (strlen($soapResult->erroExecucao) > 0) {
    	throw new Exception($soapResult->erroExecucao);
    }

    if (count($soapResult->retornos) > 0) {
    	if (!is_array($soapResult->retornos->cabecalho) && is_object($soapResult->retornos->cabecalho)) {
    		$objecToArray = $soapResult->retornos->cabecalho;

    		$soapResult->retornos->cabecalho = array(
    			$objecToArray
    		);
    	}

    	foreach ($soapResult->retornos->cabecalho as $nf) {
    		$erro = false;

		$nota_fiscal           = trim($nf->numNfv);
		$serie                 = trim($nf->codSnf);
		$data_emissao          = trim($nf->datEmi);
		$data_saida            = trim($nf->datSai);
		$codigo_transportadora = trim($nf->codTra);
		$cfop                  = trim($nf->nopPro);
		$total                 = trim($nf->vlrBpr);
		$pedido                = trim($nf->pedTlc);
		$pedido_wacker_neuson  = trim($nf->numPed);
		$situacao_nota_fiscal  = trim($nf->sitNfv);

		if (!in_array($situacao_nota_fiscal, array(3, 9))) {
			continue;
		}

		if (empty($nota_fiscal)) {
			continue;
		}

		if (empty($serie)) {
			$erro = true;
			$msg_erro["campo_obrigatorio"][] = "Erro ao importar a nota fiscal '{$nota_fiscal}', série não informada";
		}

		if (!strlen($pedido)) {
                        $erro = true;
                        $msg_erro["campo_obrigatorio"][] = "Erro ao importar a nota fiscal '{$nota_fiscal}', pedido não informado";
                } else {
                        $sql = "SELECT pedido,posto FROM tbl_pedido WHERE fabrica = {$login_fabrica} AND pedido = {$pedido}";
                        $res = pg_query($con, $sql);

	                if (!pg_num_rows($res)) {
        	                $erro = true;
                		$msg_erro["campo_obrigatorio"][] = "Erro ao importar a nota fiscal '{$nota_fiscal}', pedido não encontrado";
					}else{
						$posto = pg_fetch_result($res,0,'posto');
					}
		}

		if ($situacao_nota_fiscal == 3) {
			if (empty($data_emissao)) {
				$erro = true;
				$msg_erro["campo_obrigatorio"][] = "Erro ao importar a nota fiscal '{$nota_fiscal}', data de emissão não informada";
			} else {
				list($de_dia, $de_mes, $de_ano) = explode("/", $data_emissao);

				if (!checkdate($de_mes, $de_dia, $de_ano)) {
					$erro = true;
					$msg_erro["campo_obrigatorio"][] = "Erro ao importar a nota fiscal '{$nota_fiscal}', data de emissão inválida";
				} else {
					$data_emissao = "{$de_ano}-{$de_mes}-{$de_dia}";
				}
			}

			if (empty($data_saida)) {
				$erro = true;
				$msg_erro["campo_obrigatorio"][] = "Erro ao importar a nota fiscal '{$nota_fiscal}', data de saida não informada";
			} else {
				list($ds_dia, $ds_mes, $ds_ano) = explode("/", $data_saida);

				if (!checkdate($ds_mes, $ds_dia, $ds_ano)) {
					$erro = true;
					$msg_erro["campo_obrigatorio"][] = "Erro ao importar a nota fiscal '{$nota_fiscal}', data de saida inválida";
				} else {
					$data_saida = "{$ds_ano}-{$ds_mes}-{$ds_dia}";
				}
			}

			if (!strlen($total)) {
				$erro = true;
				$msg_erro["campo_obrigatorio"][] = "Erro ao importar a nota fiscal '{$nota_fiscal}', valor total não informado";
			}

			if (!empty($codigo_transportadora)) {
				$sql = "SELECT transportadora FROM tbl_transportadora_fabrica WHERE fabrica = {$login_fabrica} AND UPPER(codigo_interno) = UPPER('{$codigo_transportadora}')";
				$res = pg_query($con, $sql);

				if (pg_num_rows($res) > 0) {
					$transportadora = pg_fetch_result($res, 0, "transportadora");
				} else {
					$erro = true;
					$msg_erro["campo_obrigatorio"][] = "Erro ao importar a nota fiscal '{$nota_fiscal}', transportadora não encontrada";
				}
			} else {
				$transportadora = "null";
			}

			if ($erro === true) {
				continue;
			} else {
				pg_query($con, "BEGIN");

				$sql = "SELECT faturamento
						FROM tbl_faturamento
						WHERE nota_fiscal = '$nota_fiscal'
						AND   serie ='$serie'
						AND   fabrica = $login_fabrica
						AND   posto = $posto";
				$res = pg_query($con,$sql);
				if(pg_num_rows($res) == 0 ) {
					$insert = "INSERT INTO tbl_faturamento
							   (fabrica, nota_fiscal, serie, emissao, saida, transportadora, cfop, total_nota, posto)
							   VALUES
							   ({$login_fabrica}, '{$nota_fiscal}', '{$serie}', '{$data_emissao}', '{$data_saida}', {$transportadora}, '{$cfop}', {$total},{$posto})
							   RETURNING faturamento";
					$res = pg_query($con, $insert);
					#echo pg_last_error(); exit;

					if (strlen(pg_last_error()) > 0) {
						$erro = true;
						$msg_erro["campo_obrigatorio"][] = "Erro ao gravar informações da nota fiscal '{$nota_fiscal}'";

						pg_query($con, "ROLLBACK");
						continue;
					} else {
						$faturamento = pg_fetch_result($res, 0, "faturamento");
					}
				}else{
						$faturamento = pg_fetch_result($res, 0, "faturamento");
				}

				$erro_item = false;

				if (!is_array($nf->itens) && is_object($nf->itens)) {
			                $objecToArray = $nf->itens;

			                $nf->itens = array(
			                        $objecToArray
			                );
			        }


				foreach ($nf->itens as $nf_item) {
					$erro = false;

					$peca_referencia                    = trim($nf_item->codPro);
					$qtde_faturada                      = trim($nf_item->qtdFat);
					$preco_unitario                     = trim($nf_item->preUni);
					$percentual_icms                    = trim($nf_item->perIcm);
					$percentual_ipi                     = trim($nf_item->perIpi);
					$valor_base_icms                    = trim($nf_item->vlrBic);
					$valor_ipi                          = trim($nf_item->vlrIpi);
					$valor_base_substituicao_tributaria = trim($nf_item->vlrBsi);
					$valor_substituicao_tributaria      = trim($nf_item->vlrIcs);

					if (!strlen($percentual_icms)) {
						$percentual_icms = 0;
					}

					if (!strlen($percentual_ipi)) {
						$percentual_ipi = 0;
					}

					if (!strlen($valor_base_icms)) {
						$valor_base_icms = 0;
					}

					if (!strlen($valor_ipi)) {
						$valor_ipi = 0;
					}

					if (!strlen($valor_base_substituicao_tributaria)) {
						$valor_base_substituicao_tributaria = 0;
					}

					if (!strlen($valor_substituicao_tributaria)) {
						$valor_substituicao_tributaria = 0;
					}

					if (empty($peca_referencia)) {
						continue;
					} else {
						$sqlPeca = "SELECT peca FROM tbl_peca WHERE fabrica = {$login_fabrica} AND UPPER(referencia) = UPPER('{$peca_referencia}')";
						$resPeca = pg_query($con, $sqlPeca);

						if (!pg_num_rows($resPeca)) {
							$erro = true;
							$msg_erro["campo_obrigatorio"][] = "Erro ao importar item '{$peca_referencia}' da nota fiscal '{$nota_fiscal}', peça não encontrada";
						} else {
							$peca = pg_fetch_result($resPeca, 0, "peca");
						}
					}

					if (empty($qtde_faturada)) {
						$erro = true;
						$msg_erro["campo_obrigatorio"][] = "Erro ao importar item '{$peca_referencia}' da nota fiscal '{$nota_fiscal}', quantidade faturada não informada";
					}

					if (empty($preco_unitario)) {
						$erro = true;
						$msg_erro["campo_obrigatorio"][] = "Erro ao importar item '{$peca_referencia}' da nota fiscal '{$nota_fiscal}', preço não informado";
					}

					if ($erro === false) {
						$sqlPedidoItem = "SELECT pedido_item FROM tbl_pedido_item WHERE pedido = {$pedido} AND peca = {$peca}";
						$resPedidoItem = pg_query($con, $sqlPedidoItem);

						if (!pg_num_rows($resPedidoItem)) {
							$erro_item = true;
							$msg_erro["campo_obrigatorio"][] = "Erro ao importar item '{$peca_referencia}' da nota fiscal '{$nota_fiscal}', peça não encontrada no pedido '{$pedido}'";
							continue;
						} else if ($erro_item === false) {
							$pedido_item = pg_fetch_result($resPedidoItem, 0, "pedido_item");

							$sql = "SELECT faturamento
									FROM tbl_faturamento_item
									WHERE faturamento = $faturamento
									AND   pedido_item = $pedido_item";
							$res = pg_query($con,$sql);
							if(pg_num_rows($res) == 0) {
								$insert = "INSERT INTO tbl_faturamento_item
										   (faturamento, pedido, pedido_item, peca, qtde, preco, aliq_icms, aliq_ipi, base_icms, valor_ipi, base_subs_trib, valor_subs_trib)
										   VALUES
										   ({$faturamento}, {$pedido}, {$pedido_item}, {$peca}, {$qtde_faturada}, {$preco_unitario}, {$percentual_icms}, {$percentual_ipi}, {$valor_base_icms}, {$valor_ipi}, {$valor_base_substituicao_tributaria}, {$valor_substituicao_tributaria})";
								$res = pg_query($con, $insert);

								if (strlen(pg_last_error()) > 0) {
									$erro_item = true;
									$msg_erro["campo_obrigatorio"][] = "Erro ao gravar informações do item '{$peca_referencia}' da nota fiscal '{$nota_fiscal}'";
									break;
								}

								$sql = "SELECT fn_atualiza_pedido_item({$peca}, {$pedido}, {$pedido_item}, {$qtde_faturada})";
								$res = pg_query($con, $sql);

								if (strlen(pg_last_error()) > 0) {
									$erro_item = true;
									$msg_erro["campo_obrigatorio"][] = "Erro ao gravar informações do item '{$peca_referencia}' da nota fiscal '{$nota_fiscal}'";
									break;
								}
							}
						}
					} else {
						$erro_item = true;
						continue;
					}
				}

				if ($erro_item === true) {
					pg_query($con, "ROLLBACK");
				} else {
					$sql = "SELECT fn_atualiza_status_pedido({$login_fabrica}, {$pedido})";
					$res = pg_query($con, $sql);

					if (strlen(pg_last_error()) > 0) {
						$msg_erro["campo_obrigatorio"][] = "Erro ao atualizar pedido '{$pedido}'";
						pg_query($con, "ROLLBACK");
						continue;
					}

					pg_query($con, "COMMIT");
				}
			}
		} else if ($situacao_nota_fiscal == 9) {
			$sql = "SELECT faturamento FROM tbl_faturamento WHERE fabrica = {$login_fabrica} AND nota_fiscal = '{$nota_fiscal}' AND serie = '{$serie}' AND pedido = {$pedido}";
			$res = pg_query($con, $sql);

			if (!pg_num_rows($res)) {
				continue;
			} else {
				$faturamento = pg_fetch_result($res, 0, "faturamento");

				$erro_cancela = false;
				pg_query($con, "BEGIN");

				$update = "UPDATE tbl_faturamento SET cancelada = current_date where fabrica = {$login_fabrica} AND faturamento = {$faturamento}";
				$res = pg_query($con, $sql);

				if (strlen(pg_last_error()) > 0) {
					$erro_cancela = true;
					$msg_erro["campo_obrigatorio"][] = "Erro ao cancelar nota fiscal '{$nota_fiscal}'";
				}

				$update = "UPDATE tbl_pedido_item SET
						qtde_faturada = qtde_faturada - tbl_faturamento_item.qtde
					   FROM tbl_faturamento_item, tbl_faturamento
					   WHERE tbl_faturamento_item.pedido_item = tbl_pedido_item.pedido_item
					   AND tbl_faturamento_item.faturamento = tbl_faturamento.faturamento
					   AND tbl_faturamento.faturamento = {$faturamento}";
				$res = pg_query($con, $sql);

				if (strlen(pg_last_error()) > 0) {
					$erro_cancela = true;
					$msg_erro["campo_obrigatorio"][] = "Erro ao cancelar nota fiscal '{$nota_fiscal}'";
				}

				$sql = "SELECT fn_atualiza_status_pedido({$login_fabrica}, {$pedido})";
                                $res = pg_query($con, $sql);

                                if (strlen(pg_last_error()) > 0) {
					$erro_cancela = true;
                                        $msg_erro["campo_obrigatorio"][] = "Erro ao atualizar pedido '{$pedido}' no processo de cancelamento de nota fiscal";
                                }

				if ($erro_cancela === true) {
					pg_query($con, "ROLLBACK");
				} else {
					pg_query($con, "COMMIT");
				}
			}
		}
    	}


		#Verificação dos Erros
    	if (count($msg_erro) > 0) {
    		system("mkdir /tmp/{$fabrica_nome}/ 2> /dev/null ; chmod 777 /tmp/{$fabrica_nome}/" );
			system("mkdir /tmp/{$fabrica_nome}/faturamento/ 2> /dev/null ; chmod 777 /tmp/{$fabrica_nome}/faturamento/" );

			$arquivo_erro_nome = "/tmp/{$fabrica_nome}/faturamento/importa-faturamento-".date("dmYH").".txt";
    		$arquivo_erro = fopen($arquivo_erro_nome, "w");

    		if (count($msg_erro["campo_obrigatorio"]) > 0) {
    			fwrite($arquivo_erro, "<br />########## Erro na importação das Notas Fiscais (as notas fiscais presente no corpo do email não foram importadas) ##########<br />");
				fwrite($arquivo_erro, implode("<br />", $msg_erro["campo_obrigatorio"]));
    			fwrite($arquivo_erro, "<br />###############################################################################################<br />");
    			fwrite($arquivo_erro, "<br />");
    		}

			fclose($arquivo_erro);

			if (ENV == "dev") {
				mail("guilherme.curcio@telecontrol.com.br", "Telecontrol - Erro na importação de Notas Fiscais da Wacker Neuson", file_get_contents($arquivo_erro_nome), "MIME-Version: 1.0 \r\nContent-type: text/html; charset=iso-8859-1 \r\n");
			} else {
				mail("helpdesk@telecontrol.com.br, vanilde.sartorelli@wackerneuson.com", "Telecontrol - Erro na importação de Notas Fiscais da Wacker Neuson", file_get_contents($arquivo_erro_nome), "MIME-Version: 1.0 \r\nContent-type: text/html; charset=iso-8859-1 \r\n");
			}
    	}
    	###
    }
} catch(Exception $e) {
	system("mkdir /tmp/{$fabrica_nome}/ 2> /dev/null ; chmod 777 /tmp/{$fabrica_nome}/" );
	system("mkdir /tmp/{$fabrica_nome}/faturamento/ 2> /dev/null ; chmod 777 /tmp/{$fabrica_nome}/faturamento/" );

	$arquivo_erro = fopen("/tmp/{$fabrica_nome}/faturamento/importa-faturamento-".date("dmYH").".txt", "w");
	fwrite($arquivo_erro, $e->getMessage());
	fclose($arquivo_erro);

	if (ENV == "dev") {
		mail("guilherme.curcio@telecontrol.com.br", "Telecontrol - Erro na importação de Notas Fiscais da Wacker Neuson", $e->getMessage());
	} else {
		mail("helpdesk@telecontrol.com.br, vanilde.sartorelli@wackerneuson.com", "Telecontrol - Erro na importação de Notas Fiscais da Wacker Neuson", $e->getMessage());
	}
}

?>
