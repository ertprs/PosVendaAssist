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
		$soap = new SoapClient("http://201.91.139.164:8080/g5-senior-services/sapiens_Synccom_senior_g5_co_wacker_consulta_pedidos?wsdl", array("trace" => 1, "exception" => 1));
	} else {
		$soap = new SoapClient("http://187.87.251.133:8080/g5-senior-services/sapiens_Synccom_senior_g5_co_wacker_consulta_pedidos?wsdl", array("trace" => 1, "exception" => 1));
	}

	$metodo = "ConsultarPedidos";

	$sql = "SELECT tbl_pedido.pedido, tbl_pedido.seu_pedido
			FROM tbl_pedido
			INNER JOIN tbl_pedido_item ON tbl_pedido_item.pedido = tbl_pedido.pedido
			WHERE tbl_pedido.fabrica = {$login_fabrica}
			AND tbl_pedido.status_pedido NOT IN(4, 14, 17, 19, 1, 18)
			GROUP BY tbl_pedido.pedido, tbl_pedido.seu_pedido
			HAVING SUM(tbl_pedido_item.qtde - (tbl_pedido_item.qtde_cancelada + tbl_pedido_item.qtde_faturada)) > 0";
	$resPedido = pg_query($con, $sql);

	if (pg_num_rows($resPedido) > 0) {
		$pedidos = array();

		while ($pedidoSql = pg_fetch_object($resPedido)) {
			if (empty($pedidoSql->seu_pedido)) {
				continue;
			}

			$argumentos = array(
				"user"       => "suporte",
				"password"   => "suporte",
				"encryption" => "0",
				"parameters" => array(
					"dadosGerais" => array(
						"codEmp" => 1,
						"codFil" => 1,
						"numPed" => $pedidoSql->seu_pedido,
					)
				)
		    );

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

		    	foreach ($soapResult->retornos->cabecalho as $pedido) {
		    		$sql = "SELECT pedido, posto FROM tbl_pedido WHERE fabrica = {$login_fabrica} AND pedido = {$pedido->pedTlc}";
		    		$res = pg_query($con, $sql);

		    		if (pg_num_rows($res) > 0) {
						$posto                 = pg_fetch_result($res, 0, "posto");
						$erro                  = false;
						$qtde_pedido_cancelada = 0;

		    			if (!is_array($pedido->itens) && is_object($pedido->itens)) {
							$objecToArray  = $pedido->itens;
							$pedido->itens = array($objecToArray);
				        }

		    			foreach ($pedido->itens as $item) {
		    				$sql = "SELECT tbl_pedido_item.pedido_item, tbl_pedido_item.peca, tbl_pedido_item.qtde_cancelada, tbl_peca.referencia AS peca_referencia, tbl_os_produto.os
		    						FROM tbl_pedido_item 
		    						INNER JOIN tbl_peca ON tbl_peca.peca = tbl_pedido_item.peca AND tbl_peca.fabrica = {$login_fabrica}
		    						LEFT JOIN tbl_os_item ON tbl_os_item.pedido_item = tbl_pedido_item.pedido_item
		    						LEFT JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
		    						WHERE tbl_pedido_item.pedido = {$pedido->pedTlc}
		    						AND UPPER(tbl_peca.referencia) = UPPER('{$item->codPro}')";
		    				$res = pg_query($con, $sql);

		    				if (pg_num_rows($res) > 0) {
								$pedido_item     = pg_fetch_result($res, 0, "pedido_item");
								$qtde_cancelada  = pg_fetch_result($res, 0, "qtde_cancelada");
								$peca            = pg_fetch_result($res, 0, "peca");
								$peca_referencia = pg_fetch_result($res, 0, "peca_referencia");
								$os              = pg_fetch_result($res, 0, "os");

								if (empty($os)) {
									$os = "null";
								}

								if ($qtde_cancelada != $item->qtdCan && strlen($item->qtdCan) > 0) {
									$sql = "SELECT fn_atualiza_pedido_item_cancelado({$peca}, {$pedido->pedTlc}, {$pedido_item}, {$item->qtdCan})";
									$res = pg_query($con, $sql);

									if (strlen(pg_last_error()) > 0) {
										$erro = true;
										$msg_erro["erro"][] = "Erro ao cancelar a peça {$peca_referencia} do pedido {$pedido->pedTlc}";
									} else if (!empty($item->qtdCan)) {
										$sql = "INSERT INTO tbl_pedido_cancelado
												(pedido, posto, fabrica, os, peca, qtde, data, pedido_item)
												VALUES
												({$pedido->pedTlc}, {$posto}, {$login_fabrica}, {$os}, {$peca}, {$item->qtdCan}, CURRENT_DATE, {$pedido_item})";
										$res = pg_query($con, $sql);

										if (strlen(pg_last_error()) > 0) {
											$erro = true;
											$msg_erro["erro"][] = "Erro ao cancelar a peça {$peca_referencia} do pedido {$pedido->pedTlc}";
										} else {
											$qtde_pedido_cancelada++;
										}
									}
								}
		    				}
		    			}

		    			if ($qtde_pedido_cancelada > 0 && $erro === false) {
		    				$sql = "SELECT fn_atualiza_status_pedido({$login_fabrica}, {$pedido->pedTlc})";
							$res = pg_query($con, $sql);

							if (strlen(pg_last_error()) > 0) {
								$erro = true;
								$msg_erro["erro"][] = "Erro ao atualizar o status do pedido {$pedido->pedTlc}";
							}
						}

		    			if ($erro === true) {
		    				pg_query($con, "ROLLBACK");
		    			} else {
		    				pg_query($con, "COMMIT");
		    			}
		    		}
		    	}
		    }
   		}
	}

	if (count($msg_erro) > 0) {
		system("mkdir /tmp/{$fabrica_nome}/ 2> /dev/null ; chmod 777 /tmp/{$fabrica_nome}/" );
		system("mkdir /tmp/{$fabrica_nome}/pedido/ 2> /dev/null ; chmod 777 /tmp/{$fabrica_nome}/pedido/" );

		$arquivo_erro_nome = "/tmp/{$fabrica_nome}/pedido/importa-pedido-cancelado-".date("dmYH").".txt";
		$arquivo_erro = fopen($arquivo_erro_nome, "w");
		
		if (count($msg_erro["erro"]) > 0) {
			fwrite($arquivo_erro, "<br />########## Erro no cancelamento de pedidos ##########<br />");
			fwrite($arquivo_erro, implode("<br />", $msg_erro["erro"]));
			fwrite($arquivo_erro, "<br />###############################################################################################<br />");
			fwrite($arquivo_erro, "<br />");
		}

		fclose($arquivo_erro);

		if (ENV == "dev") {
			mail("guilherme.curcio@telecontrol.com.br", "Telecontrol - Erro no cancelamento de pedidos da Wacker Neuson", file_get_contents($arquivo_erro_nome), "MIME-Version: 1.0 \r\nContent-type: text/html; charset=iso-8859-1 \r\n");
		} else {
			mail("helpdesk@telecontrol.com.br, vanilde.sartorelli@wackerneuson.com", "Telecontrol - Erro no cancelamento de pedidos da Wacker Neuson", file_get_contents($arquivo_erro_nome), "MIME-Version: 1.0 \r\nContent-type: text/html; charset=iso-8859-1 \r\n");
		}
	}
} catch(Exception $e) {
	system("mkdir /tmp/{$fabrica_nome}/ 2> /dev/null ; chmod 777 /tmp/{$fabrica_nome}/" );
	system("mkdir /tmp/{$fabrica_nome}/pedido/ 2> /dev/null ; chmod 777 /tmp/{$fabrica_nome}/pedido/" );

	$arquivo_erro = fopen("/tmp/{$fabrica_nome}/pedido/importa-pedido-cancelado-".date("dmYH").".txt", "w");
	fwrite($arquivo_erro, $e->getMessage());
	fclose($arquivo_erro);

	if (ENV == "dev") {
		mail("guilherme.curcio@telecontrol.com.br", "Telecontrol - Erro no cancelamento de pedidos da Wacker Neuson", $e->getMessage());
	} else {
		mail("helpdesk@telecontrol.com.br, vanilde.sartorelli@wackerneuson.com", "Telecontrol - Erro no cancelamento de pedidos da Wacker Neuson", $e->getMessage());
	}
}

?>
