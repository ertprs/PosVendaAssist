<?php

function aprovaPedidoWackerNeuson($pedido_wacker_neuson, $soap = null) {
	global $_serverEnvironment;

	if (empty($pedido_wacker_neuson)) {
		return false;
	}

	if ($soap === null) {
		if ($_serverEnvironment == "production") {
			$soap = new SoapClient("http://201.91.139.164:8080/g5-senior-services/sapiens_Synccom_senior_g5_co_mcm_ven_pedidos?wsdl", array("trace" => 1, "exception" => 1));
		} else {
			$soap = new SoapClient("http://187.87.251.133:8080/g5-senior-services/sapiens_Synccom_senior_g5_co_mcm_ven_pedidos?wsdl", array("trace" => 1, "exception" => 1));
		}
	}

	$argumentos = array(
		"user"       => "suporte",
		"password"   => "suporte",
		"encryption" => "0",
		"parameters" => array(
			"pedidos" => array(
				"codEmp" => 1,
				"codFil" => 1,
				"numPed" => $pedido_wacker_neuson
			)
		)
    );

    $metodo = "AtualizarPedTlc";

    return $soapResult = $soap->__soapCall($metodo, $argumentos);
}

function consultaPedidoWackerNeuson($pedido_wacker_neuson, $soap = null) {
	global $_serverEnvironment;

	if (empty($pedido_wacker_neuson)) {
		return false;
	}

	if ($soap === null) {
		if ($_serverEnvironment == "production") {
			$soap = new SoapClient("http://201.91.139.164:8080/g5-senior-services/sapiens_Synccom_senior_g5_co_mcm_ven_pedidos?wsdl", array("trace" => 1, "exception" => 1));
		} else {
			$soap = new SoapClient("http://187.87.251.133:8080/g5-senior-services/sapiens_Synccom_senior_g5_co_mcm_ven_pedidos?wsdl", array("trace" => 1, "exception" => 1));
		}
	}
	
	$argumentos = array(
		"user"       => "suporte",
		"password"   => "suporte",
		"encryption" => "0",
		"parameters" => array(
			"consultar" => array(
				"pedido" => array(
					"codEmp" => 1,
					"codFil" => 1,
					"numPed" => $pedido_wacker_neuson
				)
			)
		)
    );

    $metodo = "ConsultarPedido";

    return $soapResult = $soap->__soapCall($metodo, $argumentos);
}

function deletaPedidoWackerNeuson($pedido_wacker_neuson, $soap = null) {
	global $_serverEnvironment;

	if (empty($pedido_wacker_neuson)) {
		return false;
	}

	if ($soap === null) {
		if ($_serverEnvironment == "production") {
			$soap = new SoapClient("http://201.91.139.164:8080/g5-senior-services/sapiens_Synccom_senior_g5_co_mcm_ven_pedidos?wsdl", array("trace" => 1, "exception" => 1));
		} else {
			$soap = new SoapClient("http://187.87.251.133:8080/g5-senior-services/sapiens_Synccom_senior_g5_co_mcm_ven_pedidos?wsdl", array("trace" => 1, "exception" => 1));
		}
	}
	
	$argumentos = array(
		"user"       => "suporte",
		"password"   => "suporte",
		"encryption" => "0",
		"parameters" => array(
			"pedido" => array(
				"codEmp" => 1,
				"codFil" => 1,
				"opeExe" => "E",
				"numPed" => $pedido_wacker_neuson
			)
		)
    );

    $metodo = "GravarPedidos";

    return $soapResult = $soap->__soapCall($metodo, $argumentos);
}

function exportaPedidoVendaWackerNeuson($pedido, $soap = null) {
	global $con, $_serverEnvironment;

	$login_fabrica = 143;
	$fabrica_nome  = "wackerneuson";

	if ($soap === null) {
		if ($_serverEnvironment == "production") {
			$soap = new SoapClient("http://201.91.139.164:8080/g5-senior-services/sapiens_Synccom_senior_g5_co_mcm_ven_pedidos?wsdl", array("trace" => 1, "exception" => 1));
		} else {
			$soap = new SoapClient("http://187.87.251.133:8080/g5-senior-services/sapiens_Synccom_senior_g5_co_mcm_ven_pedidos?wsdl", array("trace" => 1, "exception" => 1));
		}
	}

	$argumentos = array(
		"user"       => "suporte",
		"password"   => "suporte",
		"encryption" => "0",
		"parameters" => array()
    );

    $metodo = "GravarPedidos";

    if (empty($pedido)) {
    	throw new Exception("Pedido não informado");
    }

    $sql = "SELECT
    			tbl_pedido.pedido,
    			tbl_posto_fabrica.codigo_posto,
    			tbl_condicao.codigo_condicao,
    			TO_CHAR(tbl_pedido.data, 'DD/MM/YYYY') AS data,
    			tbl_pedido.obs,
    			tbl_transportadora_fabrica.codigo_interno AS codigo_transportadora,
    			tbl_tabela.sigla_tabela,
    			tbl_tipo_pedido.descricao AS tipo_pedido
    		FROM tbl_pedido
    		INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_pedido.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
    		INNER JOIN tbl_condicao ON tbl_condicao.condicao = tbl_pedido.condicao AND tbl_condicao.fabrica = {$login_fabrica}
    		LEFT JOIN tbl_transportadora_fabrica ON tbl_transportadora_fabrica.transportadora = tbl_pedido.transportadora AND tbl_transportadora_fabrica.fabrica = {$login_fabrica}
    		INNER JOIN tbl_tabela ON tbl_tabela.tabela = tbl_pedido.tabela AND tbl_tabela.fabrica = {$login_fabrica}
    		INNER JOIN tbl_tipo_pedido ON tbl_tipo_pedido.tipo_pedido = tbl_pedido.tipo_pedido AND tbl_tipo_pedido.fabrica = {$login_fabrica}
    		WHERE tbl_pedido.fabrica = {$login_fabrica}
    		AND tbl_pedido.recebido_fabrica IS NULL
    		AND tbl_pedido.finalizado IS NOT NULL
    		AND tbl_pedido.exportado IS NULL
    		AND tbl_pedido.pedido = {$pedido}
    		AND tbl_tipo_pedido.pedido_em_garantia IS NOT TRUE";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {
    	while ($pedido = pg_fetch_object($res)) {
			$pedido_transacao       = (strtolower($pedido->tipo_pedido) == "revenda") ? 80101 : 80100;
			$pedido_transacao_sigla = (strtolower($pedido->tipo_pedido) == "revenda") ? "R" : "C";

			$sqlItens = "SELECT
							tbl_peca.referencia,
							tbl_pedido_item.qtde,
							tbl_pedido_item.qtde_cancelada
						 FROM tbl_pedido_item
						 INNER JOIN tbl_peca ON tbl_peca.peca = tbl_pedido_item.peca AND tbl_peca.fabrica = {$login_fabrica}
						 WHERE tbl_pedido_item.pedido = {$pedido->pedido} ORDER BY tbl_pedido_item.pedido_item ASC";
			$resItens = pg_query($con, $sqlItens);

			if (pg_num_rows($res) > 0) {
				$itens_pedido = array();

				$i = 1;

				while ($item = pg_fetch_object($resItens)) {
					$itens_pedido[] = array(
						"codDep" => 1,
						"codDer" => "",
						"codPro" => $item->referencia,
						"codTpr" => $pedido->sigla_tabela,
						"obsIpd" => "",
						"opeExe" => "I",
						"qtdPed" => $item->qtde - $ite->qtde_cancelada,
						"seqIpd" => $i,
						"sitIpd" => 9
					);

					$i++;
				}
			} else {
				continue;
			}

    		$argumentos["parameters"] = array(
    			"pedido" => array(
					"acePar"  => "S",
					"codCli"  => $pedido->codigo_posto,
					"codCpg"  => $pedido->codigo_condicao,
					"codEmp"  => 1,
					"codFil"  => 1,
					"datEmi"  => $pedido->data,
					"fecPed"  => "N",
					"obsPed"  => utf8_decode($pedido->obs),
					"opeExe"  => "I",
					"produto" => $itens_pedido,
					"sitPed"  => 9,
					"tnsPro"  => $pedido_transacao,
					"usuario" => array(
	    				array(
	    					"cmpUsu" => "USU_DESMER",
	    					"vlrUsu" => $pedido_transacao_sigla
	    				),
	    				array(
	    					"cmpUsu" => "USU_PEDTLC",
	    					"vlrUsu" => $pedido->pedido
	    				),
	    				array(
	    					"cmpUsu" => "USU_SITTLC",
	    					"vlrUsu" => 1
	    				)
	    			),
	    			"codTra" => $pedido->codigo_transportadora
	    		)
    		);

    		$soapResult = $soap->__soapCall($metodo, $argumentos);
    	}
    }

	return $soapResult;
}

function exportaPedidoGarantiaWackerNeuson($pedido, $soap = null) {
	global $con, $_serverEnvironment;

	$login_fabrica = 143;
	$fabrica_nome  = "wackerneuson";

	if ($soap === null) {
		if ($_serverEnvironment == "production") {
			$soap = new SoapClient("http://201.91.139.164:8080/g5-senior-services/sapiens_Synccom_senior_g5_co_mcm_ven_pedidos?wsdl", array("trace" => 1, "exception" => 1));
		} else {
			$soap = new SoapClient("http://187.87.251.133:8080/g5-senior-services/sapiens_Synccom_senior_g5_co_mcm_ven_pedidos?wsdl", array("trace" => 1, "exception" => 1));
		}
	}

	$argumentos = array(
		"user"       => "suporte",
		"password"   => "suporte",
		"encryption" => "0",
		"parameters" => array()
    );

    $metodo = "GravarPedidos";

    if (empty($pedido)) {
    	throw new Exception("Pedido não informado");
    }

    $sql = "SELECT
    			tbl_pedido.pedido,
    			tbl_posto_fabrica.codigo_posto,
    			TO_CHAR(tbl_pedido.data, 'DD/MM/YYYY') AS data
    		FROM tbl_pedido
    		INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_pedido.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
    		WHERE tbl_pedido.fabrica = {$login_fabrica}
    		AND tbl_pedido.pedido = {$pedido}";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {
    	while ($pedido = pg_fetch_object($res)) {
			$pedido_transacao       = 90105;
			$pedido_transacao_sigla = null;

			$sqlItens = "SELECT
							tbl_peca.referencia,
							tbl_pedido_item.qtde
						 FROM tbl_pedido_item
						 INNER JOIN tbl_peca ON tbl_peca.peca = tbl_pedido_item.peca AND tbl_peca.fabrica = {$login_fabrica}
						 WHERE tbl_pedido_item.pedido = {$pedido->pedido} ORDER BY tbl_pedido_item.pedido_item ASC";
			$resItens = pg_query($con, $sqlItens);

			if (pg_num_rows($res) > 0) {
				$itens_pedido = array();

				$i = 1;

				while ($item = pg_fetch_object($resItens)) {
					$itens_pedido[] = array(
						"codDep" => 1,
						"codDer" => "",
						"codPro" => $item->referencia,
						"codTpr" => "",
						"preUni" => "1,00",
						"obsIpd" => "",
						"opeExe" => "I",
						"qtdPed" => $item->qtde,
						"seqIpd" => $i,
						"sitIpd" => 9
					);

					$i++;
				}
			} else {
				continue;
			}

    		$argumentos["parameters"] = array(
    			"pedido" => array(
					"acePar"  => "S",
					"codCli"  => $pedido->codigo_posto,
					"codCpg"  => "01",
					"codEmp"  => 1,
					"codFil"  => 1,
					"datEmi"  => $pedido->data,
					"fecPed"  => "N",
					"obsPed"  => "",
					"opeExe"  => "I",
					"produto" => $itens_pedido,
					"sitPed"  => 9,
					"tnsPro"  => $pedido_transacao,
					"usuario" => array(
	    				array(
	    					"cmpUsu" => "USU_DESMER",
	    					"vlrUsu" => $pedido_transacao_sigla
	    				),
	    				array(
	    					"cmpUsu" => "USU_PEDTLC",
	    					"vlrUsu" => $pedido->pedido
	    				),
	    				array(
	    					"cmpUsu" => "USU_SITTLC",
	    					"vlrUsu" => 1
	    				)
	    			)
	    		)
    		);

    		$soapResult = $soap->__soapCall($metodo, $argumentos);
    	}
    }

	return $soapResult;
}
?>
