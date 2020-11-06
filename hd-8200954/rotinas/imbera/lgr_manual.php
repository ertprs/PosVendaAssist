<?php
include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require dirname(__FILE__) . '/../../classes/Posvenda/Extrato.php';

$fabrica = 158;

$classExtrato = new Extrato($fabrica);

$pdo = $classExtrato->_model->getPDO();

$sql = "
  SELECT DISTINCT e.posto, e.extrato, e.data_geracao, pf.codigo_posto, p.nome, ea.codigo AS unidade_negocio
  FROM tbl_extrato e
  INNER JOIN tbl_posto_fabrica pf ON pf.posto = e.posto AND pf.fabrica = {$fabrica}
  INNER JOIN tbl_posto p ON p.posto = pf.posto
  INNER JOIN tbl_tipo_posto tp ON tp.tipo_posto = pf.tipo_posto AND tp.fabrica = {$fabrica}
  INNER JOIN tbl_extrato_agrupado ea ON ea.extrato = e.extrato
  LEFT JOIN tbl_extrato_lgr elgr ON elgr.extrato = e.extrato
  WHERE e.fabrica = {$fabrica}
  AND (tp.posto_interno IS NOT TRUE AND tp.tecnico_proprio IS NOT TRUE)
  AND e.protocolo = 'Fora de Garantia'
  AND elgr.extrato IS NULL
  ORDER BY e.posto, e.data_geracao ASC
";
$qry = $pdo->query($sql);

if ($qry && $qry->rowCount() > 0) {
  $result = $qry->fetchAll();

  echo "total extratos: ".count($result)."\n";

  foreach ($result as $k_extrato => $extrato) {
    echo "extrato {$extrato['extrato']}\n";

    $classExtrato->_model->getPDO()->beginTransaction();

    $sql = "
    	INSERT INTO tbl_extrato_lgr
    	(extrato, posto, peca, qtde, devolucao_obrigatoria, faturamento_item)
    	SELECT
    	{$extrato['extrato']}, {$extrato['posto']}, tbl_os_item.peca, SUM(tbl_os_item.qtde), tbl_os_item.peca_obrigatoria, tbl_faturamento_item.faturamento_item
    	FROM tbl_os_item
    	INNER JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica = {$fabrica}
    	INNER JOIN tbl_pedido_item ON tbl_pedido_item.pedido_item = tbl_os_item.pedido_item
    	INNER JOIN tbl_faturamento_item ON tbl_faturamento_item.pedido_item = tbl_pedido_item.pedido_item
    	INNER JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
    	INNER JOIN tbl_os ON tbl_os.os = tbl_os_produto.os AND tbl_os.fabrica = {$fabrica}
    	INNER JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = {$fabrica}
    	INNER JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
    	WHERE tbl_os_extra.extrato = {$extrato['extrato']}
    	AND tbl_os.posto = {$extrato['posto']}
    	AND (tbl_tipo_atendimento.grupo_atendimento IS NULL AND tbl_tipo_atendimento.fora_garantia IS TRUE)
    	AND (tbl_servico_realizado.troca_de_peca IS TRUE AND tbl_servico_realizado.gera_pedido IS TRUE)
    	GROUP BY tbl_os_item.peca, tbl_os_item.peca_obrigatoria, tbl_faturamento_item.faturamento_item
    ";
    $qry = $pdo->query($sql);

    if (!$qry) {
    	exit("Erro ao montar LGR de Pedido NTP, extrato {$extrato['posto']}");
    }

    $sql = "
      SELECT tbl_os_item.peca, tbl_peca.referencia, SUM(tbl_os_item.qtde) AS qtde, CASE WHEN tbl_os_item.peca_obrigatoria IS TRUE THEN 't' ELSE 'f' END AS peca_obrigatoria
      FROM tbl_os_item
      INNER JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica = {$fabrica}
      INNER JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
      INNER JOIN tbl_os ON tbl_os.os = tbl_os_produto.os AND tbl_os.fabrica = {$fabrica}
      INNER JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = {$fabrica}
      INNER JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
      INNER JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = {$fabrica}
      WHERE tbl_os_extra.extrato = {$extrato['extrato']}
      AND tbl_os.posto = {$extrato['posto']}
      AND (tbl_tipo_atendimento.grupo_atendimento IS NULL AND tbl_tipo_atendimento.fora_garantia IS TRUE)
      AND (tbl_servico_realizado.troca_de_peca IS TRUE AND tbl_servico_realizado.gera_pedido IS NOT TRUE)
      GROUP BY tbl_os_item.peca, tbl_peca.referencia, tbl_os_item.peca_obrigatoria
    ";
    $res = $pdo->query($sql);

    if ($res && $res->rowCount() > 0) {
       $pecas = $res->fetchAll();

      echo "total peças: ".count($pecas)."\n";
      echo "------------------------------\n";

      foreach ($pecas as $k_peca => $peca) {
        echo "\tpeça {$peca['referencia']}\n";

        $total_peca = $peca["qtde"];
		if($peca['peca_obrigatoria'] == 'f') {
			continue;
		}

        while ($total_peca > 0) {
          echo "\tquantidade {$total_peca}\n";
          echo "\t\tprocurando movimentação\n";

        if (in_array($extrato['unidade_negocio'], array('6200','6105','6500','6600','6201','6900','7000'))) {
           # $where = " AND JSON_FIELD('unidadeNegocio', epm.parametros_adicionais) = '{$extrato['unidade_negocio']}'";
        } else {
           #  $where = " AND JSON_FIELD('unidadeNegocio', epm.parametros_adicionais) IN('6107','6101','6108','6103','6102','6106','6104')";
        }
            # AND ((tp.pedido_em_garantia IS NOT TRUE) OR epm.tipo IS NULL) retirada essa condição em hd 4201194 
          $sqlMovimento = "
	    SELECT epm.qtde_entrada, epm.qtde_usada_estoque, f.faturamento, epm.faturamento AS faturamento_estoque, epm.data_digitacao, epm.nf, epm.obs, epm.pedido, epm.data, epm.parametros_adicionais
            FROM tbl_estoque_posto_movimento epm
            INNER JOIN tbl_faturamento f ON (f.faturamento = epm.faturamento OR f.nota_fiscal = epm.nf) AND f.fabrica = {$fabrica}
            INNER JOIN tbl_faturamento_item fi ON fi.faturamento = f.faturamento AND fi.peca = epm.peca AND (fi.pedido = epm.pedido OR epm.pedido IS NULL)
            LEFT JOIN tbl_pedido_item pi ON pi.pedido_item = fi.pedido_item
            LEFT JOIN tbl_pedido p ON p.pedido = pi.pedido AND p.fabrica = {$fabrica}
            LEFT JOIN tbl_tipo_pedido tp ON tp.tipo_pedido = p.tipo_pedido AND tp.fabrica = {$fabrica}
            WHERE epm.fabrica = {$fabrica}
            AND epm.posto = {$extrato['posto']}
            AND epm.peca = {$peca['peca']}
            AND epm.qtde_entrada IS NOT NULL
            {$where}
            AND (epm.qtde_usada_estoque IS NULL OR epm.qtde_usada_estoque < epm.qtde_entrada)
            ORDER BY epm.data ASC, epm.nf::integer ASC
            LIMIT 1
          ";
          $resMovimento = $pdo->query($sqlMovimento);

          if (!$resMovimento) {
            $classExtrato->_model->getPDO()->rollBack();
            exit("Erro buscar movimentação do estoque da Peça {$peca['referencia']} Posto {$extrato['posto']}");
          }

          if ($resMovimento->rowCount() == 0) {
            echo "\t\tgravando lgr\n";

            $insert = "
              INSERT INTO tbl_extrato_lgr
              (extrato, posto, peca, qtde, devolucao_obrigatoria)
              VALUES
              ({$extrato['extrato']}, {$extrato['posto']}, {$peca['peca']}, {$total_peca}, '{$peca['peca_obrigatoria']}')
            ";
            $resInsert = $pdo->query($insert);

            if (!$resInsert) {
              $classExtrato->_model->getPDO()->rollBack();
              exit("Erro ao montar LGR do Posto {$extrato['posto']} #0");
            }

            $total_peca -= $total_peca;

            echo "\tquantidade pendente {$total_peca}\n";

            continue;
          }

	  $movimento = $resMovimento->fetch();
    $movimento["parametros_adicionais"] = json_decode($movimento["parametros_adicionais"], true);
	if (in_array($extrato['unidade_negocio'], array('6200','6105','6500','6600','6201','6900','7000'))) {
       # $where = " AND JSON_FIELD('unidadeNegocio', tbl_estoque_posto_movimento.parametros_adicionais) = '{$extrato['unidade_negocio']}'";
    } else {
       # $where = " AND JSON_FIELD('unidadeNegocio', tbl_estoque_posto_movimento.parametros_adicionais) IN('6107','6101','6108','6103','6102','6106','6104')";
    }
	  $sqlEstoqueAnterior = "
		SELECT faturamento
		FROM tbl_estoque_posto_movimento
		WHERE fabrica = {$fabrica}
		AND posto = {$extrato['posto']}
		AND peca = {$peca['peca']}
    {$where}
		AND data_digitacao < '{$movimento['data_digitacao']}'
		AND qtde_entrada IS NOT NULL
		LIMIT 1
	  ";	
	  $resSqlEstoqueAnterior = $pdo->query($sqlEstoqueAnterior);

	  if ($resSqlEstoqueAnterior->rowCount() > 0) {
		  $estoque_anterior = true;
	  } else {
		  $estoque_anterior = false;
	  }

          if (is_null($movimento["qtde_usada_estoque"])) {
            $movimento["qtde_usada_estoque"] = 0;
          }

          if ($total_peca > ($movimento["qtde_entrada"] - $movimento["qtde_usada_estoque"])) {
            $qtde_update = $movimento["qtde_entrada"] - $movimento["qtde_usada_estoque"];
          } else {
            $qtde_update = $total_peca;
          }

	  echo "\t\tatualizando movimentação\n";

	  $whereObs = (empty($movimento["obs"])) ? "AND obs IS NULL" : "AND obs = '{$movimento['obs']}'";
	  $wherePedido = (empty($movimento["pedido"])) ? "AND pedido IS NULL": "AND pedido = {$movimento['pedido']}";
	  $whereFaturamento = (empty($movimento["faturamento_estoque"])) ? "AND faturamento IS NULL" : "AND faturamento = {$movimento['faturamento_estoque']}";

          $update = "
            UPDATE tbl_estoque_posto_movimento SET
              qtde_usada_estoque = COALESCE(qtde_usada_estoque, 0) + {$qtde_update}
	      WHERE fabrica = {$fabrica}
	      AND COALESCE(qtde_usada_estoque, 0) < qtde_entrada
            AND posto = {$extrato['posto']}
            AND json_field('unidadeNegocio',parametros_adicionais) = '{$movimento["parametros_adicionais"]["unidadeNegocio"]}'
            AND peca = {$peca['peca']}
	    AND nf = '{$movimento['nf']}'
	    AND data_digitacao = '{$movimento['data_digitacao']}'
	  {$whereFaturamento}
	  {$whereObs}
	  {$wherePedido}
            AND qtde_entrada = {$movimento['qtde_entrada']}
          ";
          $resUpdate = $pdo->query($update);

          if (!$resUpdate) {
		  $classExtrato->_model->getPDO()->rollBack();
            exit("Erro ao montar LGR do Posto {$extrato['posto']} #1");
          }

	  echo "\t\tbuscando faturamento\n";

	  if ($estoque_anterior == true) {
		  $whereFaturamentoItem = "AND (tp.garantia_antecipada IS TRUE OR fi.pedido_item IS NULL) AND fi.qtde = {$movimento['qtde_entrada']}";
	  } else {
		  $whereFaturamentoItem = "";
	  }

	  $wherePedido = (empty($movimento["pedido"])) ? "": "AND fi.pedido = {$movimento['pedido']}";

          $sqlFaturamentoItem = "
            SELECT fi.faturamento_item
            FROM tbl_faturamento_item fi
            INNER JOIN tbl_faturamento f ON f.faturamento = fi.faturamento AND f.fabrica = {$fabrica}
            LEFT JOIN tbl_pedido_item pi ON pi.pedido_item = fi.pedido_item
            LEFT JOIN tbl_pedido p ON p.pedido = pi.pedido AND p.fabrica = {$fabrica}
            LEFT JOIN tbl_tipo_pedido tp ON tp.tipo_pedido = p.tipo_pedido AND tp.fabrica = {$fabrica}
            WHERE fi.faturamento = {$movimento["faturamento"]}
	    AND fi.peca = {$peca['peca']}
	  {$whereFaturamentoItem}
	  {$wherePedido}
          ";
          $resFaturamentoItem = $pdo->query($sqlFaturamentoItem);

          if (!$resFaturamentoItem || $resFaturamentoItem->rowCount() == 0) {
		  $classExtrato->_model->getPDO()->rollBack();
            exit("Erro ao montar LGR do Posto {$extrato['posto']} #2");
          }

          $faturamento_item = $resFaturamentoItem->fetch();

          if ($peca['peca_obrigatoria'] == 1) {
            $peca_obrigatoria = "true";
          } else {
            $peca_obrigatoria = "false";
          }

          echo "\t\tgravando lgr\n";

          $insert = "
            INSERT INTO tbl_extrato_lgr
            (extrato, posto, peca, qtde, devolucao_obrigatoria, faturamento_item)
            VALUES
            ({$extrato['extrato']}, {$extrato['posto']}, {$peca['peca']}, {$qtde_update}, {$peca_obrigatoria}, {$faturamento_item['faturamento_item']})
          ";
          $resInsert = $pdo->query($insert);

          if (!$resInsert) {
            $classExtrato->_model->getPDO()->rollBack();
            exit("Erro ao montar LGR do Posto {$extrato['posto']} #3");
          }

          $total_peca -= $qtde_update;

          echo "\tquantidade pendente {$total_peca}\n";
        }

        echo "\n";
      }
    }
    
    $classExtrato->_model->getPDO()->commit();

    echo "\n";
  }
} else {
  echo "\nNenhum Extrato encontrado\n";
}
