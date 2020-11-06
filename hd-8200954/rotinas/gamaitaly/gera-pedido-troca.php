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

    /*
    * Defini��o
    */
    date_default_timezone_set('America/Sao_Paulo');
    $fabrica = 164;
    $data    = date('d-m-Y');
    $env     = ($_serverEnvironment == 'development') ? 'teste' : 'producao';

    /*
    * A variavel $param defire se o pedido vai ser por posto ou OS, sendo o padr�o pedido por posto
    */
    $param = "posto"; /* posto | os */

    /*
    * Log
    */
    $logClass = new Log2();
    $logClass->adicionaLog(array("titulo" => "Log erro - Gera��o de Pedidos Gama Italy")); // Titulo
    if ($env == 'producao' ) {
        $logClass->adicionaEmail("helpdesk@telecontrol.com.br");
        $logClass->adicionaEmail("fabricia.carmo@gamaitaly.com.br");
        $logClass->adicionaEmail("heidy.batista@gamaitaly.com.br");
        $logClass->adicionaEmail("roberta.ricomini@gamaitaly.com.br");
        $logClass->adicionaEmail("cleonice.maria@gamaitaly.com.br");
    } else {
        $logClass->adicionaEmail("guilherme.silva@telecontrol.com.br");
    }

    /*
    * Cron
    */
    $phpCron = new PHPCron($fabrica, __FILE__);
    $phpCron->inicio();

    /*
    * Class F�brica
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
    * Resgata a condi��o da Fabrica
    */
    $condicao = $pedidoClass->getCondicaoPagamentoGarantia();

    /*
    * Resgata o tipo pedido da Fabrica
    */
    $tipo_pedido = $pedidoClass->getTipoPedidoGarantia();

    for ($i = 0; $i < count($osTroca); $i++) {
        try {
            $posto = $osTroca[$i]["posto"];

            $os_pedido_posto = $osClass->getOsTrocaPosto($posto);

            if (empty($os_pedido_posto) || $posto == 6359) {
                continue;
            }

            /*
            * Verifica Saldo da pe�a. Para pedidos de troca n�o ir� verificar Saldo
            */
            #include "classes/verificaSaldoPeca.php";
            #$verificaSaldoPeca = new verificaSaldoPeca($fabrica);

            $dados = array(
                'posto'         => $posto,
                'tipo_pedido'   => $tipo_pedido,
                'condicao'      => $condicao,
                'fabrica'       => $fabrica,
                'status_pedido' => 1,
                'troca'         => "'t'"
            );

            /*
            * Grava o Pedido. Begin.
            */
            $pedidoClass->_model->getPDO()->beginTransaction();
            $pedidoClass->grava($dados);

            $pedido = $pedidoClass->getPedido();

            for ($x = 0; $x < count($os_pedido_posto); $x++) {

                $os = $os_pedido_posto[$x]["os"];

                #$estoque_peca_suficiente = $verificaSaldoPeca->retornaAuditoriaOsPecas($os);
		/*
                if($estoque_peca_suficiente == false){
                    continue;
                }
		*/
                $dadosItens = array();

                /**
                 * Pega as pe�as da OS
                 */
                $osClass = new \Posvenda\Os($fabrica, $os);

                // Consultando se tem distribuidor...
                $distrib = $osClass->getDistribuidorOsTroca($os);

                if ($distrib) {
                    $pedidoClass->grava(array(
                        'distribuidor' => $distrib,
                        'pedido_via_distribuidor' => 't' // MLG: queria usar TRUE, mas... :D
                    ), $pedido);
                }

                $pecas = $osClass->getPecasPedidoGarantiaTroca();

                foreach ($pecas as $key => $peca) {

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
                //seta distribuidor 4311 - distrib
                $osClass->setPedidoOsTroca($pedido, $os);
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

    if(!empty($msg_erro)){

        $logClass->adicionaLog(implode("<br />", $msg_erro));

        // if($logClass->enviaEmails() == "200"){
        //   echo "Log de erro enviado com Sucesso!";
        // }else{
        //   $logClass->enviaEmails();
        // }

        $fp = fopen("tmp/{$fabrica_nome}/pedidos/troca-log-erro-".date("dmY").".txt", "a");
        fwrite($fp, "Data Log: " . date("d/m/Y") . "\n");
        fwrite($fp, implode("\n", $msg_erro));
        fclose($fp);

    }

    $phpCron->termino();

} catch (Exception $e) {
    echo $e->getMessage();
}
