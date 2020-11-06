<?php
class itemException extends Exception {}

try {
	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	include dirname(__FILE__) . '/../funcoes.php';

	if ($_serverEnvironment == "production") {
        	define("ENV", "prod");
	} else {
        	define("ENV", "dev");
	}

	include "exporta-pedido-funcao.php";

	$login_fabrica = 143;
	$fabrica_nome  = "wackerneuson";
	$msg_erro      = array();

	if (ENV == "prod") {
		$soap = new SoapClient("http://201.91.139.164:8080/g5-senior-services/sapiens_Synccom_senior_g5_co_mcm_ven_pedidos?wsdl", array("trace" => 1, "exception" => 1));
	} else {
		$soap = new SoapClient("http://187.87.251.133:8080/g5-senior-services/sapiens_Synccom_senior_g5_co_mcm_ven_pedidos?wsdl", array("trace" => 1, "exception" => 1));
	}

    $sql = "SELECT
    			tbl_pedido.pedido,
    			tbl_pedido.posto
    		FROM tbl_pedido
    		INNER JOIN tbl_tipo_pedido ON tbl_tipo_pedido.tipo_pedido = tbl_pedido.tipo_pedido AND tbl_tipo_pedido.fabrica = {$login_fabrica}
    		WHERE tbl_pedido.fabrica = {$login_fabrica}
    		AND tbl_pedido.recebido_fabrica IS NULL
    		AND tbl_pedido.status_pedido = 19
    		AND tbl_pedido.finalizado IS NOT NULL
    		AND tbl_pedido.posto <> 6359
    		AND tbl_pedido.exportado IS NULL";
    $resPedidos = pg_query($con, $sql);

    if (pg_num_rows($resPedidos) > 0) {
    	while ($pedido = pg_fetch_object($resPedidos)) {
    		try {
    			$resultadoExportacao = exportaPedidoVendaWackerNeuson($pedido->pedido, $soap);

	    		if (!empty($resultadoExportacao->erroExecucao)) {
		            throw new itemException(utf8_decode($resultadoExportacao->erroExecucao));
		        } else if ($resultadoExportacao->respostaPedido->retorno != "OK") {
		            throw new itemException(utf8_decode($resultadoExportacao->respostaPedido->retorno));
		        } else {
		            pg_query($con, "BEGIN");

		            if (!is_array($resultadoExportacao->respostaPedido->gridPro)) {
		            	$pedido_wacker_neuson = $resultadoExportacao->respostaPedido->gridPro->numPed;	
		            } else {
		            	$pedido_wacker_neuson = $resultadoExportacao->respostaPedido->gridPro[0]->numPed;	
		            }

		            if (empty($pedido_wacker_neuson)) {
		                throw new itemException("Nùmero de pedido da fábrica não retornado pelo webservice");
		            } else {
		                $sql = "UPDATE tbl_pedido SET seu_pedido = '{$pedido_wacker_neuson}' WHERE fabrica = {$login_fabrica} AND pedido = {$pedido->pedido}";
		                $res = pg_query($con, $sql);

		                if (strlen(pg_last_error()) > 0) {
		                    $resultadoDelete = deletaPedidoWackerNeuson($pedido_wacker_neuson, $soap);

		                    if (!empty($resultadoDelete->erroExecucao)) {
		                    	throw new itemException("Erro de execução ao gravar número de pedido da fábrica, não foi possível deletar o pedido $pedido_wacker_neuson gerado pela fábrica");
		                    } else {
		                    	throw new itemException("Erro de execução ao gravar número de pedido da fábrica, pedido $pedido_wacker_neuson gerado pela fábrica foi deletado");
		                    }
		                } else {
		                    $itensPedido = consultaPedidoWackerNeuson($pedido_wacker_neuson, $soap);

		                    if (!empty($itensPedido->erroExecucao)) {
		                        $resultadoDelete = deletaPedidoWackerNeuson($pedido_wacker_neuson, $soap);

		                        if (!empty($resultadoDelete->erroExecucao)) {
			                    	throw new itemException("Erro ao consultar pedido $pedido_wacker_neuson no webservice, não foi possível deletar o pedido gerado pela fábrica. Erro webservice consulta: ".utf8_decode($itensPedido->erroExecucao));
			                    } else {
			                    	throw new itemException("Erro ao consultar pedido $pedido_wacker_neuson no webservice, pedido gerado pela fábrica foi deletado. Erro webservice: ".utf8_decode($itensPedido->erroExecucao));
			                    }
		                    } else {
		                    	if (!is_array($itensPedido->retornos->dadosGerais->itens)) {
		                            $update = "UPDATE tbl_pedido_item SET 
													acrescimo_financeiro  = {$itensPedido->retornos->dadosGerais->itens->preUni},
													acrescimo_tabela_base = {$itensPedido->retornos->dadosGerais->itens->vlrLiq}
		                                       FROM tbl_peca
		                                       WHERE tbl_pedido_item.pedido = {$pedido->pedido}
		                                       AND tbl_pedido_item.peca = tbl_peca.peca
		                                       AND tbl_peca.fabrica = {$login_fabrica}
		                                       AND tbl_peca.referencia = '{$itensPedido->retornos->dadosGerais->itens->codPro}'";
		                            $res = pg_query($con, $update);

		                            if (strlen(pg_last_error()) > 0) {
		                                $resultadoDelete = deletaPedidoWackerNeuson($pedido_wacker_neuson, $soap);

		                                if (!empty($resultadoDelete->erroExecucao)) {
					                    	throw new itemException("Erro ao atualizar itens do pedido, não foi possível deletar o pedido $pedido_wacker_neuson gerado pela fábrica");
					                    } else {
					                    	throw new itemException("Erro ao atualizar itens do pedido, pedido $pedido_wacker_neuson gerado pela fábrica foi deletado");
					                    }
		                            }
		                    	} else {
			                        foreach ($itensPedido->retornos->dadosGerais->itens as $key => $item) {
			                            $update = "UPDATE tbl_pedido_item SET 
														acrescimo_financeiro  = {$item->preUni},
														acrescimo_tabela_base = {$item->vlrLiq}
			                                       FROM tbl_peca
			                                       WHERE tbl_pedido_item.pedido = {$pedido->pedido}
			                                       AND tbl_pedido_item.peca = tbl_peca.peca
			                                       AND tbl_peca.fabrica = {$login_fabrica}
			                                       AND tbl_peca.referencia = '{$item->codPro}'";
			                            $res = pg_query($con, $update);

			                            if (strlen(pg_last_error()) > 0) {
			                                $resultadoDelete = deletaPedidoWackerNeuson($pedido_wacker_neuson, $soap);

			                                if (!empty($resultadoDelete->erroExecucao)) {
						                    	throw new itemException("Erro ao atualizar itens do pedido, não foi possível deletar o pedido $pedido_wacker_neuson gerado pela fábrica");
						                    } else {
						                    	throw new itemException("Erro ao atualizar itens do pedido, pedido $pedido_wacker_neuson gerado pela fábrica foi deletado");
						                    }
			                            }
			                        }
		                    	}

		                        $update = "UPDATE tbl_pedido SET
		                        				status_pedido = 18, 
		                        				recebido_fabrica = CURRENT_TIMESTAMP, 
		                        				exportado = CURRENT_TIMESTAMP
		                        		   WHERE fabrica = {$login_fabrica}
		                        		   AND pedido = {$pedido->pedido}";
		                       	$res = pg_query($con, $update);

		                       	if (strlen(pg_last_error()) > 0) {
	                                $resultadoDelete = deletaPedidoWackerNeuson($pedido_wacker_neuson, $soap);

	                                if (!empty($resultadoDelete->erroExecucao)) {
				                    	throw new itemException("Erro ao atualizar status do pedido, não foi possível deletar o pedido $pedido_wacker_neuson gerado pela fábrica");
				                    } else {
				                    	throw new itemException("Erro ao atualizar status do pedido, pedido $pedido_wacker_neuson gerado pela fábrica foi deletado");
				                    }
	                            } else {
	                            	$sql = "INSERT INTO tbl_comunicado (
												fabrica,
												posto,
												obrigatorio_site,
												tipo,
												ativo,
												descricao,
												mensagem
											) VALUES (
												{$login_fabrica},
												{$pedido->posto},
												true,
												'Com. Unico Posto',
												true,
												'Pedido {$pedido->pedido} calculado aguardando aprovação',
												'O pedido {$pedido->pedido} teve seus valores atualizados e está aguardando aprovação.'
											)";
									$res = pg_query($con, $sql);

									if (strlen(pg_last_error()) > 0) {
		                                $resultadoDelete = deletaPedidoWackerNeuson($pedido_wacker_neuson, $soap);

		                                if (!empty($resultadoDelete->erroExecucao)) {
					                    	throw new itemException("Erro ao atualizar status do pedido, não foi possível deletar o pedido $pedido_wacker_neuson gerado pela fábrica");
					                    } else {
					                    	throw new itemException("Erro ao atualizar status do pedido, pedido $pedido_wacker_neuson gerado pela fábrica foi deletado");
					                    }
		                            } else {
		                            	$sql = "SELECT contato_email FROM tbl_posto_fabrica WHERE fabrica = {$login_fabrica} AND posto = {$pedido->posto}";
		                            	$res = pg_query($con, $sql);


										if (strlen(pg_last_error()) > 0) {
			                                $resultadoDelete = deletaPedidoWackerNeuson($pedido_wacker_neuson, $soap);

			                                if (!empty($resultadoDelete->erroExecucao)) {
						                    	throw new itemException("Erro ao atualizar status do pedido, não foi possível deletar o pedido $pedido_wacker_neuson gerado pela fábrica");
						                    } else {
						                    	throw new itemException("Erro ao atualizar status do pedido, pedido $pedido_wacker_neuson gerado pela fábrica foi deletado");
						                    }
			                            } else {
			                            	$posto_email = pg_fetch_result($res, 0, "contato_email");

			                            	if (strlen($posto_email) > 0 && filter_var($posto_email, FILTER_VALIDATE_EMAIL)) {
			                            		if (ENV != "prod") {
			                            			mail("guilherme.curcio@telecontrol.com.br", "Telecontrol - Pedido {$pedido->pedido} calculado aguardando aprovação", "O pedido {$pedido->pedido} teve seus valores atualizados e está aguardando aprovação, <a href=\"http://posvenda.telecontrol.com.br/assist/pedido_finalizado.php?pedido={$pedido->pedido}\" target=\"_blank\" >clique aqui</a> para visualizar o pedido.", "MIME-Version: 1.0 \r\nContent-type: text/html; charset=iso-8859-1 \r\n");
			                            		} else {
			                            			mail($posto_email, "Telecontrol - Pedido {$pedido->pedido} calculado aguardando aprovação", "O pedido {$pedido->pedido} teve seus valores atualizados e está aguardando aprovação, <a href=\"http://posvenda.telecontrol.com.br/assist/pedido_finalizado.php?pedido={$pedido->pedido}\" target=\"_blank\" >clique aqui</a> para visualizar o pedido.", "MIME-Version: 1.0 \r\nContent-type: text/html; charset=iso-8859-1 \r\n");
			                            		}
			                            	}
			                            }
		                            }
				                }
		                    }
		                }
		            }

		            pg_query($con, "COMMIT");
		        }
	    	} catch(itemException $p) {
	    		$msg_erro[] = "Erro ao calcular o pedido {$pedido->pedido}: ".$p->getMessage();
	    		pg_query($con, "ROLLBACK");

	    		$sql = "UPDATE tbl_pedido SET status_pedido = 24, rejeitado_motivo = 'Erro ao calcular o pedido {$pedido->pedido}: ".$p->getMessage()."' WHERE fabrica = {$login_fabrica} AND pedido = {$pedido->pedido}";
	    		$res = pg_query($con, $sql);

	    		if (ENV != "prod") {
        			mail("guilherme.curcio@telecontrol.com.br", "Telecontrol - Pedido {$pedido->pedido} recusado pelo fabricante", "Erro ao calcular o pedido {$pedido->pedido}: ".$p->getMessage()."<br /><a href=\"http://posvenda.telecontrol.com.br/assist/pedido_finalizado.php?pedido={$pedido->pedido}\" target=\"_blank\" >clique aqui</a> para visualizar e estar cancelando o pedido ou entre em contato com o fabricante", "MIME-Version: 1.0 \r\nContent-type: text/html; charset=iso-8859-1 \r\n");
        		} else {
        			mail("sac@wackerneuson.com", "Telecontrol - Pedido {$pedido->pedido} recusado pelo fabricante", "Erro ao calcular o pedido {$pedido->pedido}: ".$p->getMessage()."<br /><a href=\"http://posvenda.telecontrol.com.br/assist/pedido_finalizado.php?pedido={$pedido->pedido}\" target=\"_blank\" >clique aqui</a> para visualizar e estar cancelando o pedido ou entre em contato com o fabricante", "MIME-Version: 1.0 \r\nContent-type: text/html; charset=iso-8859-1 \r\n");
        		}
	    	}
    	}

    	#Verificação dos Erros
    	if (count($msg_erro) > 0) {
    		system("mkdir /tmp/{$fabrica_nome}/ 2> /dev/null ; chmod 777 /tmp/{$fabrica_nome}/" );
			system("mkdir /tmp/{$fabrica_nome}/pedido_venda/ 2> /dev/null ; chmod 777 /tmp/{$fabrica_nome}/pedido_venda/" );

			$arquivo_erro_nome = "/tmp/{$fabrica_nome}/pedido_venda/exporta-pedido-venda-".date("dmYH").".txt";
    		$arquivo_erro = fopen($arquivo_erro_nome, "w");
			
    		if (count($msg_erro) > 0) {
    			fwrite($arquivo_erro, "<br />########## Erro no cálculo de pedidos ##########<br />");
				fwrite($arquivo_erro, implode("<br />", $msg_erro));
    			fwrite($arquivo_erro, "<br />###############################################################################################<br />");
    			fwrite($arquivo_erro, "<br />");
    		}

			fclose($arquivo_erro);

			if (ENV == "dev") {
				mail("guilherme.curcio@telecontrol.com.br", "Telecontrol - Erro no cálculo de pedidos da Wacker Neuson", file_get_contents($arquivo_erro_nome), "MIME-Version: 1.0 \r\nContent-type: text/html; charset=iso-8859-1 \r\n");
			} else {
				mail("helpdesk@telecontrol.com.br, vanilde.sartorelli@wackerneuson.com", "Telecontrol - Erro no cálculo de pedidos da Wacker Neuson", file_get_contents($arquivo_erro_nome), "MIME-Version: 1.0 \r\nContent-type: text/html; charset=iso-8859-1 \r\n");
			}
    	}
    	###
    }
} catch(Exception $e) {
	system("mkdir /tmp/{$fabrica_nome}/ 2> /dev/null ; chmod 777 /tmp/{$fabrica_nome}/" );
	system("mkdir /tmp/{$fabrica_nome}/pedido_venda/ 2> /dev/null ; chmod 777 /tmp/{$fabrica_nome}/pedido_venda/" );

	$arquivo_erro = fopen("/tmp/{$fabrica_nome}/pedido_venda/exporta-pedido-venda-".date("dmYH").".txt", "w");
	fwrite($arquivo_erro, "Erro na execução do cálculo de pedidos: ".$e->getMessage());
	fclose($arquivo_erro);

	if (ENV != "prod") {
		mail("guilherme.curcio@telecontrol.com.br", "Telecontrol - Erro no cálculo de pedidos da Wacker Neuson", $e->getMessage());
	} else {
		mail("helpdesk@telecontrol.com.br, vanilde.sartorelli@wackerneuson.com", "Telecontrol - Erro no cálculo de pedidos da Wacker Neuson", $e->getMessage());
	}
}

?>
