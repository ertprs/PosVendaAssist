<?php

try {

    /*
    * Includes
    */

    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require dirname(__FILE__) . '/../funcoes.php';

    include dirname(__FILE__) . '/../../classes/Posvenda/Fabrica.php';
    include dirname(__FILE__) . '/../../classes/Posvenda/Os.php';
    include dirname(__FILE__) . '/../../classes/Posvenda/Pedido.php';
    include dirname(__FILE__) . '/../../classes/Posvenda/Fabricas/_158/ExportaPedido.php';
    include dirname(__FILE__) . '/../../classes/Posvenda/Fabricas/_158/PedidoBonificacao.php';

    /*
    * Definição
    */
    $fabrica = 158;
    $data = date('d-m-Y');

    $env = ($_serverEnvironment == 'development') ? "dev" : "producao";
    
    $param = "os"; /* posto | os */

    /*
    * Log
    */
    $logClass = new Log2();
    $logClass->adicionaLog(array("titulo" => "Log erro - Geração de Pedidos Imbera")); // Titulo
    if ($env == 'producao') {
        $logClass->adicionaEmail("helpdesk@telecontrol.com.br");
    } else {
        $logClass->adicionaEmail("kaique.magalhaes@telecontrol.com.br");
    }

    /*
    * Cron
    */
    $phpCron = new PHPCron($fabrica, __FILE__);
    $phpCron->inicio();

    /*
    * Class Fábrica
    */
    $fabricaClass = new \Posvenda\Fabrica($fabrica);

    /*
    * Resgata o nome da Fabrica
    */
    $fabrica_nome = $fabricaClass->getNome();

    /*
    * Resgata as OSs em Garantia
    */
    $osClass = new \Posvenda\Os($fabrica);

    $os_garantia = $osClass->getOsGarantia($param);

    if (empty($os_garantia)) {
        exit;
    }

    /*
    * Mensagem de Erro
    */
    $msg_erro = array();

    $pedidoClass = new \Posvenda\Pedido($fabrica, null, $param);
    $exportaPedidoClass = new \Posvenda\Fabricas\_158\ExportaPedido($pedidoClass, $osClass, $fabrica);
    $oPedidoBonificacao = new \Posvenda\Fabricas\_158\PedidoBonificacao($pedidoClass);

    /*
    * Resgata a condição da Fabrica
    */
    $condicao = $pedidoClass->getCondicaoGarantia();
    /*
    * Resgata a condição da Fabrica
    */
    $tipo_pedido = $pedidoClass->getTipoPedidoGarantia("NTP");

    for ($i = 0; $i < count($os_garantia); $i++) {
        try {
            $osPedido = array();
            $posto = $os_garantia[$i]["posto"];

            /*
            * Begin
            */
            $pedidoClass->_model->getPDO()->beginTransaction();

            $dados = array(
                "posto"         => $posto,
                "tipo_pedido"   => $tipo_pedido,
                "condicao"      => $condicao,
                "fabrica"       => $fabrica,
                "status_pedido" => 1,
                "finalizado"       => "'".date("Y-m-d H:i:s")."'"
            );

            /*
            * Grava o Pedido
            */
            $os = $os_garantia[$i]["os"];
            $pedidoClass->grava($dados, null);
            $pedido = $pedidoClass->getPedido();

            $dadosItens = array();

            /**
             * Pega as peças da OS
             */
            $osClass = new \Posvenda\Os($fabrica, $os);

            $pecas = $osClass->getPecasPedidoGarantia();

            foreach ($pecas as $key => $peca) {

                unset($dadosItens);

                /*
                * Insere o Pedido Item
                */
                $preco = $pedidoClass->getPrecoPecaGarantia($peca["peca"], $os);

                $dadosItens[] = array(
                    "pedido"            => (int)$pedido,
                    "peca"              => $peca["peca"],
                    "qtde"              => $peca["qtde"],
                    "qtde_faturada"     => 0,
                    "qtde_cancelada"    => 0,
                    "preco"             => $preco,
                    "total_item"        => $preco * $peca["qtde"]
                );

                $pedidoClass->gravaItem($dadosItens, $pedido);

                /*
                * Resgata o Pedido Item
                */
                $pedido_item = $pedidoClass->getPedidoItem();

                /*
                * Atualiza os Pedidos Item na OS Item
                */
                $pedidoClass->atualizaOsItemPedidoItem($peca["os_item"], $pedido, $pedido_item, $fabrica);
            }

            $pedidoClass->finaliza($pedido);

            /*
             * Commit
             */
            $pedidoClass->_model->getPDO()->commit();

            /*
             * Exportação dos Pedidos
             */
            $resOSs = $exportaPedidoClass->getPedido($os);

            /*
             * Valida parametros para exportação
             */
            /*if (is_array($resOSs)) {
		$osPedido = $oPedidoBonificacao->organizaEstoque($resOSs, true);

		if (!empty($resOSs['centro_custo'])) {
		    $centro_custo = $resOSs['centro_custo'];
		} else if (!empty($resOSs[0]['centro_custo'])) {
		    $centro_custo = $resOSs[0]['centro_custo'];
		}

		if (!empty($resOSs['garantia_antecipada'])) {
		    $garantia_antecipada = $resOSs['garantia_antecipada'];
		} else if (!empty($resOSs[0]['garantia_antecipada'])) {
		    $garantia_antecipada = $resOSs[0]['garantia_antecipada'];
		}

                /*
                * Se Posto tiver Depósito (ForaKit/Piso) senão (CriaOrdemVenda)
                */
                /*if (!empty($centro_custo) && $garantia_antecipada != 't' && $exportaPedidoClass->verificaTerceiroGarantia($os) == false) {
                    $exportaPedidoClass->pedidoIntegracao($osPedido);
                } else {
                    $exportaPedidoClass->pedidoIntegracaoSemDeposito($osPedido);
                }
            }*/
        } catch (Exception $e) {
            $pedidoClass->_model->getPDO()->rollBack();
            $msg_erro[] = $e->getMessage();
            continue;
        }
    }

    if (!empty($msg_erro)) {
        $logClass->adicionaLog(implode("<br />", $msg_erro));

        $logClass->enviaEmails();

        $nome_fabrica = strtolower($fabrica_nome);
        $logError = "./{$nome_fabrica}-";

        if (!is_dir("/tmp/{$nome_fabrica}/pedidos")) {
            if (mkdir("/tmp/{$nome_fabrica}/pedidos")) {
                $logError = "/tmp/{$nome_fabrica}/pedidos/";
            }
        } else {
            $logError = "/tmp/{$nome_fabrica}/pedidos/";
        }

        $fp = fopen("{$logError}log-erro".date("d-m-Y_H-i-s").".txt", "a");
        fwrite($fp, "Data Log: " . date("d/m/Y") . "\n");
        fwrite($fp, implode("\n", $msg_erro));
        fclose($fp);
    } else {
        $msg_sucesso = "Pedidos gerados com sucesso";
    }

    $phpCron->termino();

} catch (Exception $e) {
    echo $e->getMessage();
}
