<?php

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
    $fabrica = 182;
    $data = date('d-m-Y');

    $env = "producao";
    $os = $argv[1];
    /*
    * A variavel $param defire se o pedido vai ser por posto ou OS, sendo o padrão pedido por posto
    */

    $param = "posto"; /* posto | os */

    /*
    * Log
    */
    $logClass = new Log2();
    $logClass->adicionaLog(array("titulo" => "Log erro - Geração de Pedidos ESAB Peru")); // Titulo
    if ($env == 'producao' ) {
        $logClass->adicionaEmail("filipe.souza@esab.com.br");
        $logClass->adicionaEmail("helpdesk@telecontrol.com.br");
    } else {
        $logClass->adicionaEmail("rafael.macedo@telecontrol.com.br");
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
    $osTroca = $osClass->getTrocaPosto($param);

    if(empty($osTroca)){
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
    $previsao_entrega = date('Y-m-d',strtotime('+7 days',strtotime(date('Y-m-d'))));

    /*
    * Resgata a condição da Fabrica
    */
    $tipo_pedido = $pedidoClass->getTipoPedidoGarantia();

    for ($i = 0; $i < count($osTroca); $i++) {
            $posto = $osTroca[$i]["posto"];
            $comunicado = "<ul>";

            $os_pedido_posto = $osClass->getOsTrocaPosto($posto);

            // if (empty($os_pedido_posto) || $posto == 6359) {
            //     continue;
            // }

            $dados = array(
                "posto"         => $posto,
                "tipo_pedido"   => $tipo_pedido,
                "condicao"      => $condicao,
                "previsao_entrega" => "'".$previsao_entrega."'",
                "fabrica"       => $fabrica,
                "status_pedido" => 1,
                "finalizado"       => "'".date("Y-m-d H:i:s")."'"
            );

            for ($x = 0; $x < count($os_pedido_posto); $x++) {
                try {
                    /*
                    * Begin
                    */
                    $pedidoClass->_model->getPDO()->beginTransaction();
                    /*
                    * Grava o Pedido
                    */
                    $pedidoClass->grava($dados);

                    $pedido = $pedidoClass->getPedido();
                    $os = $os_pedido_posto[$x]["os"];
                    $dadosItens = array();

                    /**
                    * Pega as peças da OS
                    */
                    $osClass = new \Posvenda\Os($fabrica, $os);

                    $pecas = $osClass->getPecasPedidoGarantiaTroca();
                    $comunicado .= "<li>Pedido: ".$pedido." - Previsão de Envio: ".date("d/m/Y",strtotime($previsao_entrega))."</li>";

                    foreach ($pecas as $key => $peca) {
                        // print_r($pecas);
                        unset($dadosItens);

                        /*
                        * Insere o Pedido Item
                        */
                        $dadosItens[] = array(
                            "pedido"            => (int)$pedido,
                            "peca"              => $peca["peca"],
                            "qtde"              => $peca["qtde"],
                            "qtde_faturada"     => 0,
                            "qtde_cancelada"    => 0,
                            "preco"             => $pedidoClass->getPrecoPecaGarantia($peca["peca"], $os)
                        );
                        // $dadosItens["total_item"] = ($dadosItens["preco"] * $dadosItens["qtde"]);

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
                } catch(Exception $e) {
                    $pedidoClass->_model->getPDO()->rollBack();

                    $msg_erro[] = $e->getMessage();

                    continue;
                }
            }

            if($comunicado !== "<ul>"){
                $comunicado .= "</ul>";

                pg_query($con,"BEGIN");

                $sql = "INSERT INTO tbl_comunicado (fabrica,posto,
                            obrigatorio_site, tipo, ativo, descricao, mensagem 
                        ) VALUES (
                        {$fabrica},{$posto}, true, 'Com. Unico Posto', true,
                    'Foram gerados os seguintes pedidos com a previsão de faturamento',
                    '{$comunicado}')";
                pg_query($con, $sql);

                if(strlen(pg_last_error()) > 0){
                    pg_query($con,"ROLLBACK");
                    $msg_erro = "ERRO: Não foi possível gravar o comnunicado do posto, em relação aos pedidos gerados.";
                }else{
                    pg_query($con,"COMMIT");
                }
            }
    }

    if(!empty($msg_erro)){

        $logClass->adicionaLog(implode("<br />", $msg_erro));

        if($logClass->enviaEmails() == "200"){
          echo "Log de erro enviado com Sucesso!";
        }else{
          $logClass->enviaEmails();
        }

        $fp = fopen("tmp/{$fabrica_nome}/pedidos/log-erro-gera-pedido-troca".date("d-m-Y_H-i-s").".txt", "a");
        fwrite($fp, "Data Log: " . date("d/m/Y") . "\n");
        fwrite($fp, implode("\n", $msg_erro));
        fclose($fp);

    }

    $phpCron->termino();

