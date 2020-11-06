<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

error_reporting(E_ALL ^ E_NOTICE);

$header   = 'MIME-Version: 1.0' . "\r\n";
$header  .= 'FROM: noreply@telecontrol.com.br' . "\r\n";
$header  .= 'Content-type: text/html; charset=utf-8' . "\r\n";

try {
	include_once dirname(__FILE__) . '/../../../dbconfig.php';
	include_once dirname(__FILE__) . '/../../../includes/dbconnect-inc.php';

	include_once dirname(__FILE__) . '/../../../funcoes.php';

	include_once dirname(__FILE__) . '/../../../classes/autoload.php';

	include_once dirname(__FILE__) . '/../../../classes/autoload.php';
	include_once dirname(__FILE__) . '/../../../classes/Mirrors/Parts/Faturamento.php';

	$faturamentoMirror = new \Mirrors\Parts\Faturamento;

	$_integratedFactories = "SELECT
		fabrica,
		parametros_adicionais
	FROM tbl_fabrica
	WHERE json_field('integracaoParts', parametros_adicionais)::boolean IS TRUE
	AND json_field('companyExternalHash', parametros_adicionais) IS NOT NULL
	AND json_field('responsibleExternalHash', parametros_adicionais) IS NOT NULL";

	$stmt_integratedFactories = $pdo->query($_integratedFactories);

	if (!$stmt_integratedFactories) {
		logerror($stmt_integratedFactories->errorInfo());
		die('failed to run script');
	}

	if ($stmt_integratedFactories->rowCount() === 0) {
		die('no integrated factory.');
	}

	while ($factory = $stmt_integratedFactories->fetch(PDO::FETCH_OBJ)) {
		$factory->parametros_adicionais = json_decode($factory->parametros_adicionais);

		$_integratedOrdersBill = "SELECT
			tbl_faturamento.faturamento,
			tbl_faturamento.nota_fiscal,
			tbl_faturamento.serie,
			tbl_faturamento.emissao,
			tbl_faturamento.saida,
			tbl_faturamento.previsao_chegada,
			tbl_faturamento.cfop,
			tbl_faturamento.natureza,
			tbl_faturamento.total_nota,
			tbl_faturamento.transp,
			tbl_faturamento.tipo_frete,
			tbl_faturamento.valor_frete,
			tbl_faturamento.info_extra,
			json_agg(tbl_faturamento_item.*) AS itens
		FROM tbl_faturamento
		JOIN tbl_faturamento_item ON tbl_faturamento_item.faturamento = tbl_faturamento.faturamento
		JOIN tbl_pedido ON tbl_pedido.pedido = tbl_faturamento_item.pedido
		WHERE tbl_faturamento.fabrica = :fabrica
		AND json_field('partsOrderExternalHash', tbl_pedido.valores_adicionais) IS NOT NULL
		AND tbl_faturamento.info_extra->>'partsBillExternalHash' IS NULL
		GROUP BY tbl_faturamento.faturamento";

		$stmt_integratedOrdersBill = $pdo->prepare($_integratedOrdersBill);
		$stmt_integratedOrdersBill->execute(['fabrica' => $factory->fabrica]);

		if (!$stmt_integratedOrdersBill) {
			logerror($stmt_integratedOrdersBill->errorInfo());
			continue;
		}

		if ($stmt_integratedFactories->rowCount() === 0) {
			continue;
		}

		$requestBody = [
			'user' => $factory->parametros_adicionais->responsibleExternalHash,
			'company' => $factory->parametros_adicionais->companyExternalHash,
			'origin' => 'POSVENDA'
		];

		while ($bill = $stmt_integratedOrdersBill->fetch(PDO::FETCH_OBJ)) {
			$bill->itens = json_decode($bill->itens);

			$requestBody['faturamentos'][0] = [
				'nota' => $bill->nota_fiscal,
				'serie' => $bill->serie,
				'emissao' => $bill->emissao,
				'total' => $bill->total_nota,
				'cfop' => $bill->cfop,
				'natureza' => $bill->natureza,
				'frete' => [
					'valor' => $bill->valor_frete,
					'tipo' => $bill->tipo_frete
				],
				'itens' => []
			];

			foreach ($bill->itens as $k => $item) {
				$_orderParams = "SELECT valores_adicionais
				FROM tbl_pedido
				WHERE pedido = :pedido
				AND fabrica = :fabrica";

				$stmt_orderParams = $pdo->prepare($_orderParams);
				$stmt_orderParams->execute(['pedido' => $item->pedido, 'fabrica' => $factory->fabrica]);

				if (!$stmt_orderParams) {
					logerror($stmt_orderParams->errorInfo());
					continue;
				}

				if ($stmt_orderParams->rowCount() === 0) {
					continue;
				}

				$additionalParams = $stmt_orderParams->fetch(PDO::FETCH_OBJ);
				$additionalParams = json_decode($additionalParams->valores_adicionais);

				$_billPart = "SELECT
					referencia
				FROM tbl_peca
				WHERE peca = :peca
				AND fabrica = :fabrica
				LIMIT 1";

				$stmt_billPart = $pdo->prepare($_billPart);
				$stmt_billPart->execute(['peca' => $item->peca, 'fabrica' => $factory->fabrica]);

				if (!$stmt_billPart) {
					logerror($stmt_billPart->errorInfo());
					continue;
				}

				if ($stmt_billPart->rowCount() === 0) {
					continue;
				}

				$billParts = $stmt_billPart->fetch(PDO::FETCH_OBJ);

				$requestBody['faturamentos'][0]['itens'][] = [
					'pedido' => $additionalParams->partsOrderExternalHash,
					'quantidade' => $item->qtde,
					'referencia_item' => $billParts->referencia,
					'aliquota' => [
						'icms' => $item->aliq_icms,
						'ipi' => $item->aliq_ipi,
						'reducao' => $item->aliq_reducao
					],
					'base' => [
						'icms' => $item->base_icms,
						'ipi' => $item->base_ipi,
						'substituicao' => $item->base_subs_trib
					],
					'valores' => [
						'icms' => $item->valor_icms,
						'ipi' => $item->valor_ipi,
						'substituicao' => $item->valor_subs_trib
					]
				];
			}

			if (count($requestBody['faturamentos'][0]['itens']) === 0) {
				continue;
			}

			$response = $faturamentoMirror->post($requestBody);

			$faturamentos = $response['faturamentos'];
			$failed = $response['failed'];

			if (isset($failed)) {
				logerror($failed);
				continue;
			}

			$billExtraParams = json_decode($bill->info_extra);
			$billExtraParams->partsBillExternalHash = $response['faturamentos'][0]['internal_hash'];

			$billExtraParams = json_encode($billExtraParams);

			$_updateBill = "UPDATE tbl_faturamento SET info_extra = :extra WHERE faturamento = :faturamento";

			$stmt_updateBill = $pdo->prepare($_updateBill);
			$stmt_updateBill->execute(['faturamento' => $bill->faturamento, 'extra' => $billExtraParams]);

			if (!$stmt_updateBill) {
				logerror($stmt_updateBill->errorInfo());
				continue;
			}
		}
	}
} catch (Exception $e) {
	mail('gabriel.tinetti@telecontrol.com.br', utf8_encode('Rotina de Exportação de Faturamento - Parts API'), utf8_encode($e->getMessage()), $header);
} catch (Throwable $t) {
	mail('gabriel.tinetti@telecontrol.com.br', utf8_encode('Rotina de Exportação de Faturamento - Parts API'), utf8_encode($t->getMessage()), $header);
}

function logerror($message)
{
	file_put_contents('/tmp/parts-exportacao-fat.log', print_r($message, true), FILE_APPEND);
}
