<?php
include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require dirname(__FILE__) . '/../funcoes.php';
require dirname(__FILE__) . '/../../classes/Posvenda/Extrato.php';
require dirname(__FILE__) . '/../../classes/Posvenda/Fabricas/_158/Extrato.php';

/*
* Definições
*/
$fabrica 		= 158;
$dia_mes     	= date('d');
$dia_extrato 	= date('Y-m-d H:i:s');

#$dia_mes     = "31";
#$dia_extrato = "2018-12-15 23:59:00";

/*
* Cron Class
*/
$phpCron = new PHPCron($fabrica, __FILE__);
$phpCron->inicio();

/*
* Log Class
*/
$logClass = new Log2();
$logClass->adicionaLog(array("titulo" => "Log erro Geração de Extrato - Imbera")); // Titulo
# $logClass->adicionaEmail("maicon.luiz@telecontrol.com.br");
$logClass->adicionaEmail("helpdesk@telecontrol.com.br");

/*
* Extrato Class
*/
$classExtrato = new Extrato($fabrica);
$classExtratoImbera = new ExtratoImbera($fabrica);

/*
* Resgata a quantidade de OS por Posto
*/
$pdo = $classExtrato->_model->getPDO();

$sql = "
	SELECT
		tbl_posto_fabrica.posto,
		os.fora_garantia,
		os.unidade_negocio
  	FROM tbl_posto_fabrica
	JOIN (
		SELECT
			o.os,
			CASE WHEN hcc.hd_chamado IS NOT NULL OR ta.fora_garantia IS TRUE THEN 't' ELSE 'f' END AS fora_garantia,
      json_field('unidadeNegocio',oce.campos_adicionais::text) AS unidade_negocio,
			o.posto
  		FROM tbl_os o
		JOIN tbl_tipo_atendimento ta ON ta.tipo_atendimento = o.tipo_atendimento AND ta.fabrica = {$fabrica}
		JOIN tbl_os_extra oe ON oe.os = o.os
		JOIN tbl_posto_fabrica pf ON pf.posto = o.posto AND pf.fabrica = {$fabrica}
		JOIN tbl_tipo_posto tp ON tp.tipo_posto = pf.tipo_posto AND tp.fabrica = {$fabrica}
    JOIN tbl_os_campo_extra oce ON oce.os = o.os AND oce.fabrica = {$fabrica}
		LEFT JOIN tbl_hd_chamado hc ON hc.hd_chamado = o.hd_chamado AND hc.fabrica = {$fabrica}
		LEFT JOIN tbl_hd_chamado_cockpit hcc ON hcc.hd_chamado = hc.hd_chamado AND hcc.fabrica = {$fabrica}
		WHERE o.fabrica = {$fabrica}
		AND o.posto NOT IN(444985)
		AND o.excluida IS NOT TRUE
		AND o.os NOT IN (SELECT os FROM tbl_auditoria_os WHERE os = o.os AND liberada IS NULL)
		AND o.finalizada <= '{$dia_extrato}'
		AND oe.extrato IS NULL
		AND (tp.posto_interno IS NOT TRUE AND tp.tecnico_proprio IS NOT TRUE)
		AND ta.fora_garantia IS NOT TRUE
	) AS os ON os.posto = tbl_posto_fabrica.posto
	WHERE tbl_posto_fabrica.fabrica = {$fabrica}
	GROUP BY tbl_posto_fabrica.posto, os.fora_garantia, os.unidade_negocio
";

$query = $pdo->query($sql);

$os_posto = $query->fetchAll(\PDO::FETCH_ASSOC);

if(empty($os_posto)){
  exit;
}

/*
* Mensagem de Erro
*/
$msg_erro = "";
$msg_erro_arq = "";

for ($i = 0; $i < count($os_posto); $i++) {
  $posto         = $os_posto[$i]["posto"];
  $fora_garantia = $os_posto[$i]["fora_garantia"];
  $unidade_negocio = $os_posto[$i]["unidade_negocio"];

	try {
    /*
    * Begin
    */
    $classExtrato->_model->getPDO()->beginTransaction();

    /*
    * Insere o Extrato para o Posto
    */
    $classExtrato->insereExtratoPosto($fabrica, $posto, $dia_extrato, $mao_de_obra = 0, $pecas = 0, $total = 0, $avulso = 0, (($fora_garantia != "t") ? "Garantia" : "Fora de Garantia"));

    /*
    * Resgata o numero do Extrato
    */
    $extrato = $classExtrato->getExtrato();

    $classExtratoImbera->extratoUnidadeNegocio($extrato,$unidade_negocio);

    /*
    * Insere lançamentos avulsos para o Posto
    */
    $classExtrato->atualizaAvulsosPosto($fabrica, $posto, $extrato, $fora_garantia);

    /*
    * Relaciona as OSs com o Extrato
    */
    $classExtratoImbera->relacionaExtratoOS($fabrica, $posto, $extrato, $dia_extrato, null, $fora_garantia, $unidade_negocio);

    /**
     * LGR
     */
    if ($_serverEnvironment == "development") {
      $data_15 = date("Y-m-d");
    } else {
      $data_15 = $classExtrato->getPeriodoDias(14, $dia_extrato);
    }

    $pdo = $classExtrato->_model->getPDO();

    if ($fora_garantia == "t") {
      /**
       * LGR de Peças - Troca de Peça (gera pedido)
      $sql = "
        INSERT INTO tbl_extrato_lgr
        (extrato, posto, peca, qtde, devolucao_obrigatoria, faturamento_item)
        SELECT
        {$extrato}, {$posto}, tbl_os_item.peca, SUM(tbl_os_item.qtde), tbl_os_item.peca_obrigatoria, tbl_faturamento_item.faturamento_item
        FROM tbl_os_item
        INNER JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica = {$fabrica}
        INNER JOIN tbl_pedido_item ON tbl_pedido_item.pedido_item = tbl_os_item.pedido_item
        INNER JOIN tbl_faturamento_item ON tbl_faturamento_item.pedido_item = tbl_pedido_item.pedido_item
        INNER JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
        INNER JOIN tbl_os ON tbl_os.os = tbl_os_produto.os AND tbl_os.fabrica = {$fabrica}
        INNER JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
        WHERE tbl_os_extra.extrato = {$extrato}
        AND tbl_os.posto = {$posto}
        AND (tbl_servico_realizado.troca_de_peca IS TRUE AND tbl_servico_realizado.gera_pedido IS TRUE)
        GROUP BY tbl_os_item.peca, tbl_os_item.peca_obrigatoria, tbl_faturamento_item.faturamento_item
      ";
      $qry = $pdo->query($sql);

      if (!$qry) {
        throw new Exception("Erro ao montar LGR do Posto {$posto}");
      }
      */
    
    } else {      
      
       /* LGR de Peças - Troca de Peça (usa estoque) */
      $sql = "
        SELECT tbl_os.os, tbl_os_item.peca, SUM(tbl_os_item.qtde) AS qtde, tbl_os_item.peca_obrigatoria
        FROM tbl_os_item
        INNER JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica = {$fabrica}
        INNER JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
        INNER JOIN tbl_os ON tbl_os.os = tbl_os_produto.os AND tbl_os.fabrica = {$fabrica}
        INNER JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
        INNER JOIN tbl_tipo_atendimento on tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento and tbl_tipo_atendimento.fabrica = {$fabrica}
        WHERE tbl_os_extra.extrato = {$extrato}        
        AND tbl_tipo_atendimento.fora_garantia IS NOT TRUE
        AND tbl_os.posto = {$posto}
        AND (tbl_servico_realizado.troca_de_peca IS TRUE AND tbl_servico_realizado.gera_pedido IS NOT TRUE)
        and tbl_os_item.peca_obrigatoria is true
        GROUP BY tbl_os.os, tbl_os_item.peca, tbl_os_item.peca_obrigatoria
      ";
      $res = $pdo->query($sql);

      if (!$res) {
        throw new Exception("Erro ao montar LGR do Posto {$posto}");
      }

      if ($res->rowCount() > 0) {

        $pecas = $res->fetchAll();

        foreach ($pecas as $peca) {

          $total_peca = $peca["qtde"];

          while ($total_peca > 0) {
            
            $sqlMovimento = "
		SELECT
		    tbl_estoque_posto_movimento.qtde_entrada,
		    tbl_estoque_posto_movimento.qtde_usada_estoque,
		    tbl_faturamento.faturamento
                FROM tbl_estoque_posto_movimento
                JOIN tbl_faturamento ON tbl_estoque_posto_movimento.nf::INTEGER = tbl_faturamento.nota_fiscal::INTEGER AND tbl_faturamento.fabrica = {$fabrica} AND tbl_faturamento.posto = {$posto}
                JOIN tbl_faturamento_item ON tbl_faturamento_item.faturamento = tbl_faturamento.faturamento
                WHERE tbl_estoque_posto_movimento.fabrica = {$fabrica}
                AND tbl_estoque_posto_movimento.posto = {$posto}
                AND tbl_estoque_posto_movimento.peca = {$peca['peca']}
                AND tbl_estoque_posto_movimento.qtde_entrada IS NOT NULL
                AND (tbl_estoque_posto_movimento.qtde_usada_estoque IS NULL
		OR tbl_estoque_posto_movimento.qtde_usada_estoque < tbl_estoque_posto_movimento.qtde_entrada)
                ORDER BY tbl_estoque_posto_movimento.data_digitacao ASC
		LIMIT 1;
	    ";

            $resMovimento = $pdo->query($sqlMovimento);

	    if (!$resMovimento || $resMovimento->rowCount() == 0) {
		$total_peca -= $peca['qtde'];	
		continue;
	            #  throw new Exception("Erro peça sem estoque {$posto}");
            }

            $movimento = $resMovimento->fetch();

            if (is_null($movimento["qtde_usada_estoque"])) {
              $movimento["qtde_usada_estoque"] = 0;
            }

            if ($total_peca > ($movimento["qtde_entrada"] - $movimento["qtde_usada_estoque"])) {
              $qtde_update = $movimento["qtde_entrada"] - $movimento["qtde_usada_estoque"];
            } else {
              $qtde_update = $total_peca;
            }

            $update = "
              UPDATE tbl_estoque_posto_movimento SET
                qtde_usada_estoque = COALESCE(qtde_usada_estoque, 0) + {$qtde_update}
              WHERE fabrica = {$fabrica}
              AND posto = {$posto}
              AND peca = {$peca['peca']}
              AND faturamento = {$movimento['faturamento']}
            ";
            $resUpdate = $pdo->query($update);


            if (!$resUpdate) {
              throw new Exception("Erro ao montar LGR do Posto {$posto}");
            }

            $sqlFaturamentoItem = "
              SELECT faturamento, faturamento_item
              FROM tbl_faturamento_item
              WHERE faturamento = {$movimento["faturamento"]}
              AND peca = {$peca['peca']}
            ";
            $resFaturamentoItem = $pdo->query($sqlFaturamentoItem);

            if (!$resFaturamentoItem || $resFaturamentoItem->rowCount() == 0) {
              throw new Exception("Erro ao montar LGR do Posto {$posto}");
            }

            $faturamento_item = $resFaturamentoItem->fetch();

            if ($peca['peca_obrigatoria'] == 1) {
              $peca_obrigatoria = "true";
            } else {
              $peca_obrigatoria = "false";
            }

            $insert = "
              INSERT INTO tbl_extrato_lgr
              (extrato, posto, peca, qtde, devolucao_obrigatoria, faturamento, faturamento_item)
              VALUES
              ({$extrato}, {$posto}, {$peca['peca']}, {$qtde_update}, {$peca_obrigatoria}, {$faturamento_item['faturamento']}, {$faturamento_item['faturamento_item']})
            ";

            $resInsert = $pdo->query($insert);

            if (!$resInsert) {
              throw new Exception("Erro ao montar LGR do Posto {$posto}");
            }

            $total_peca -= $qtde_update;
          }
        }
      }
      
      $sql = "
        SELECT tbl_distribuidor_sla.unidade_negocio
        FROM tbl_distribuidor_sla_posto
        INNER JOIN tbl_distribuidor_sla ON tbl_distribuidor_sla.distribuidor_sla = tbl_distribuidor_sla_posto.distribuidor_sla
        WHERE tbl_distribuidor_sla_posto.fabrica = {$fabrica}
        AND tbl_distribuidor_sla_posto.posto = {$posto}
      ";
      $qry = $pdo->query($sql);

      $retornoPosto = $qry->fetchAll(\PDO::FETCH_ASSOC);

      $unidadesUsamLgr = \Posvenda\Regras::getUnidades("usaLgr", $fabrica);

      foreach ($retornoPosto as $key => $value) {
        $unidadesPosto[] = $value["unidade_negocio"];
      }

      $compararUnidades = array_intersect($unidadesUsamLgr, $unidadesPosto);
      
      if (count($compararUnidades) > 0) {
        $classExtrato->verificaLGR($extrato, $posto, $data_15);
      }
    }

    /*
    * Atualiza os valores avulso dos postos
    */
    $classExtrato->atualizaValoresAvulsos($fabrica,$extrato);

    /*
    * Calcula o Extrato
    */
    $total_extrato = $classExtratoImbera->calcula($extrato, $posto, $unidade_negocio, null, 't');
    $total_extrato = $classExtrato->verificaValorMinimoExtrato(1, $total_extrato);

    /*
    * Commit
    */
    $classExtrato->_model->getPDO()->commit();
	    //$classExtrato->_model->getPDO()->rollBack();
	} catch (Exception $e){
    $msg_erro .= $e->getMessage()."<br />";
    $msg_erro_arq .= $msg_erro . " - SQL: " . $classExtrato->getErro();

    /*
    * Rollback
    */
    $classExtrato->_model->getPDO()->rollBack();
	}
}
/*
* Erro
*/
if(!empty($msg_erro)){

  $logClass->adicionaLog($msg_erro);

  if($logClass->enviaEmails() == "200"){
    echo "Log de erro enviado com Sucesso!";
  }else{
    echo $logClass->enviaEmails();
  }

  $fp = fopen("/tmp/imbera/log-extrato-erro.text", "a");
  fwrite($fp, "Data Log: " . date("d/m/Y") . "\n");
  fwrite($fp, $msg_erro_arq . "\n \n");
  fclose($fp);
}

/*
* Cron Término
*/
$phpCron->termino();
