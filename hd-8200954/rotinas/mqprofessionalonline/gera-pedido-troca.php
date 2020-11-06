<?php

try {
    error_reporting(0);
    /*
    * Includes
    */

    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require dirname(__FILE__) . '/../funcoes.php';

    include dirname(__FILE__) . '/../../classes/Posvenda/Fabrica.php';
    include dirname(__FILE__) . '/../../classes/Posvenda/Os.php';
    include dirname(__FILE__) . '/../../classes/Posvenda/Pedido.php';

    /*
    * Definição
    */
    date_default_timezone_set('America/Sao_Paulo');
    $fabrica = 186;
    $data = date('d-m-Y');

    $env = "producao";

    /*
    * A variavel $param defire se o pedido vai ser por posto ou OS, sendo o padrão pedido por posto
    */

    $param = "troca-produto-posto"; /* posto | os */

    /*
    * Log
    */
    $logClass = new Log2();
    $logClass->adicionaLog(array("titulo" => "Log erro - Geração de Pedidos MQ Hair")); // Titulo
    $logClass->adicionaEmail("gabriel.tinetti@telecontrol.com.br");

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
    $osClass = new \Posvenda\Model\Os($fabrica);
    $rawPostoOsGarantia = $osClass->selectOsGarantia($param);

    if(empty($rawPostoOsGarantia)){
        exit;
    }

    /*
    * Mensagem de Erro
    */
    $msg_erro = array();

    $pedidoClass = new \Posvenda\Pedido($fabrica);

    /*
    * Resgata a condição da Fabrica
    */
    $condicao = $pedidoClass->getCondicaoGarantia();

    $postoOsGarantia = [];
    foreach ($rawPostoOsGarantia as $posto => $osData) {
        foreach ($osData as $os) {
            $camposAdicionais = json_decode($os["campos_adicionais"] , true);
            if (is_null($camposAdicionais)) {
                continue;
            }

            unset($os["campos_adicionais"]);

            $tipoGeraPedido = strtoupper($camposAdicionais["tipo_gera_pedido"]);
            
            $postoOsGarantia[$posto][$tipoGeraPedido][] = $os;
        }
    }

    foreach ($postoOsGarantia as $posto => $tiposGeraPedido) {
        try {
            $pedidoClass->_model->getPDO()->beginTransaction();

            foreach ($tiposGeraPedido as $tipoPedidoDesc => $data) {
                $tipoPedido = $pedidoClass->getTipoPedidoGarantia(null, implode(" ", explode("_", $tipoPedidoDesc)));
                $tabela = $pedidoClass->_model->getTabelaPreco($posto, $tipoPedido);

                $dados = [
                    "posto"         => $posto,
                    "tipo_pedido"   => $tipoPedido,
                    "condicao"      => $condicao,
                    "fabrica"       => $fabrica,
                    "troca"         => "TRUE",
                    "total"         => 0,
                    "tabela"        => $tabela,
                    "status_pedido" => 1,
                    "finalizado"       => "'".date("Y-m-d H:i:s")."'"
                ];

                $pedidoClass->grava($dados);
                $pedido = $pedidoClass->getPedido();

                $dadosAtt = [];

                foreach ($data as $key => $osData) {
                    $os = $osData['os'];
                    $dadosItens = [];

                    $osClass = new \Posvenda\Os($fabrica, $os);

                    $dadosAtt[] = ["os" => (string)$os];

                    $pecas = $osClass->getPecasPedidoGarantiaTroca();

                    foreach ($pecas as $key => $peca) {
                        unset($dadosItens);

                        $dadosItens[] = [
                            "pedido"            => (int)$pedido,
                            "peca"              => $peca["peca"],
                            "qtde"              => $peca["qtde"],
                            "qtde_faturada"     => 0,
                            "qtde_cancelada"    => 0,
                            "troca_produto"     => "TRUE",
                            "preco"             => $pedidoClass->getPrecoPecaGarantia($peca["peca"], $os)
                        ];

                        $pedidoClass->gravaItem($dadosItens, $pedido);

                        $pedidoItem = $pedidoClass->getPedidoItem();
                        $pedidoClass->atualizaOsItemPedidoItem($peca["os_item"], $pedido, $pedidoItem, $fabrica);
                    }

                    if ($key === count($data) - 1) {
                        $pedidoClass->grava(["obs" => json_encode($dadosAtt)], $pedido);
                    }
                }
            }

            $pedidoClass->_model->getPDO()->commit();
        } catch (Exception $e) {
            $pedidoClass->_model->getPDO()->rollBack();
            $msg_erro[] = $e->getMessage();

            continue;
        }
    }

    if(!empty($msg_erro)){

        $logClass->adicionaLog(implode("<br />", $msg_erro));

        if($logClass->enviaEmails() == "200"){
          echo "Log de erro enviado com Sucesso!";
        }else{
          $logClass->enviaEmails();
        }

        $fp = fopen("/tmp/{$fabrica_nome}/pedidos/log-erro-gera-pedido-troca".date("d-m-Y_H-i-s").".txt", "a");
        fwrite($fp, "Data Log: " . date("d/m/Y") . "\n");
        fwrite($fp, implode("\n", $msg_erro));
        fclose($fp);

    }

    $phpCron->termino();

} catch (Exception $e) {
    echo $e->getMessage();
}
