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

    $env = "producaoX";
    $os_argv = $argv[1];

    /*
    * A variavel $param defire se o pedido vai ser por posto ou OS, sendo o padrão pedido por posto
    */

    $param = "os"; /* posto | os */

    /*
    * Log
    */
    $logClass = new Log2();
    $logClass->adicionaLog(array("titulo" => "Log erro - Geração de Pedidos Posto Interno MQ Hair")); // Titulo
    if ($env == 'producao' ) {
        $logClass->adicionaEmail("helpdesk@telecontrol.com.br");
    } else {
        $logClass->adicionaEmail("felipe.marttos@telecontrol.com.br");
    }
   /*
    * Mensagem de Erro
    */
    $msg_erro = array();

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

    $verificaPecaOs = $osClass->verificaPecaOs($os_argv);

    if (!$verificaPecaOs) {
        $retorno = $osClass->inserePecaProdutoAcabadoOsItem($os_argv);

        if(isset($retorno["erro"]) && $retorno["erro"] == true){
            $msg_erro[] = $retorno["msg"];
        }
    } 
    $os_garantia = $osClass->getOsGarantiaPostoInterno($param, $os_argv,null,true);
  

    if(empty($os_garantia)){
       $msg_erro[] = "Nenhum OS encontrada";
    }
 
    $pedidoClass = new \Posvenda\Pedido($fabrica);

    /*
    * Resgata a condição da Fabrica
    */
    $condicao = $pedidoClass->getCondicaoGarantia();
    /*
    * Resgata a condição da Fabrica
    */
    $tipo_pedido = $pedidoClass->getTipoPedidoGarantia();
    if (count($msg_erro) == 0) {
        for ($i = 0; $i < count($os_garantia); $i++) {

            try {

                $posto = $os_garantia[$i]["posto"];

                /*
                * Begin
                */
                $pedidoClass->_model->getPDO()->beginTransaction();
                $dados = array(
                    "posto"         => $posto,
                    "tipo_pedido"   => $tipo_pedido,
                    "condicao"      => "'".$condicao."'",
                    "fabrica"       => $fabrica,
                    "status_pedido" => 4,
                    "obs"           => "'Pedido gerado para devolução do produto da OS: ".$os_garantia[0]["os"]."'",
                    "finalizado"    => "'".date("Y-m-d H:i:s")."'"
                );
                /*
                * Grava o Pedido
                */
                $pedidoClass->grava($dados);

                $pedido = $pedidoClass->getPedido();

                /* Pedido por Posto */

                    for ($j = 0; $j < count($os_garantia); $j++) {

                        $os = $os_garantia[$i]["os"];

                        $dadosItens = array();

                        /**
                         * Pega as peças da OS
                         */
                        $osClass = new \Posvenda\Os($fabrica, $os);

                        $pecas = $osClass->verificaPecaOs($os_argv);

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
                                "qtde_faturada"     => $peca["qtde"],
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

                    }

                    $pedidoClass->finaliza($pedido);
                    $pedidoClass->registrarPedidoExportado($pedido,4);

                /*
                * Commit
                */
                $pedidoClass->_model->getPDO()->commit();

            } catch(Exception $e) {
                $pedidoClass->_model->getPDO()->rollBack();

                $msg_erro[] = $e->getMessage();

                continue;
            }
        }
    }
    if(count($msg_erro) > 0){
        exit(json_encode(["erro" => true, "msg" => implode("\n", $msg_erro)]));
    } else {
        exit(json_encode(["erro" => false, "msg" => "Pedido Gerado com Sucesso"]));
    }

    $phpCron->termino();

} catch (Exception $e) {
    echo $e->getMessage();
}
