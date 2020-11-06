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
    * Definição
    */
    date_default_timezone_set('America/Sao_Paulo');
    $fabrica = 161;
    $data = date('d-m-Y');

    $env = ($_serverEnvironment == 'development') ? 'teste' : 'producao';

    /*
    * A variavel $param defire se o pedido vai ser por posto ou OS, sendo o padrão pedido por posto
    */

    $param = "os"; /* posto | os */

    /*
    * Log
    */
    $logClass = new Log2();
    $logClass->adicionaLog(array("titulo" => "Log erro - Geração de Pedidos Cristófoli")); // Titulo
    if ($env == 'producao' ) {
        $logClass->adicionaEmail("helpdesk@telecontrol.com.br");
        $logClass->adicionaEmail("informatica@cristofoli.com");
    } else {
        $logClass->adicionaEmail("guilherme.silva@telecontrol.com.br");
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
	$fabrica_nome = strtolower($fabrica_nome);
    /*
    * Resgata as OSs em Garantia
    */
    $osClass = new \Posvenda\Os($fabrica);
    $os_garantia = $osClass->getOsGarantia($param);
     //print_r($os_garantia);var_dump($os_garantia);exit;
    if(empty($os_garantia)){
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

    /*
    * Resgata a condição da Fabrica
    */
    $tipo_pedido = $pedidoClass->getTipoPedidoGarantia();

    $previsao_entrega = date('Y-m-d',strtotime('+7 days',strtotime(date('Y-m-d'))));
    $array_posto = 0;

    for ($i = 0; $i < count($os_garantia); $i++) {
        //$comunicado = "<ul>";
        try {

            $posto = $os_garantia[$i]["posto"];

            $os_pedido_posto = $osClass->getOsPosto($posto);

            if ((is_array($os_pedido_posto) && count($os_pedido_posto) == 0) || (empty($os_pedido_posto)) || $posto == 6359) {
                continue;
            }

            /*
            * Begin
            */
            $pedidoClass->_model->getPDO()->beginTransaction();

            $dados = array(
                "posto"             => $posto,
                "tipo_pedido"       => $tipo_pedido,
                "condicao"          => $condicao,
                "status_pedido"     => 1,
                "fabrica"           => $fabrica
            );

print_r($dados);

            /*
            * Grava o Pedido
            */
            $pedidoClass->grava($dados);
            $pedido = $pedidoClass->getPedido();

            for ($x = 0; $x < count($os_pedido_posto); $x++) {

                $os = $os_pedido_posto[$x]["os"];

echo $os."\n";

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
                    //chamado 3325514 somente para peças com preço
                    if ($preco > 0 ) {
                        $dadosItens[] = array(
                            "pedido"            => (int)$pedido,
                            "peca"              => $peca["peca"],
                            "qtde"              => $peca["qtde"],
                            "qtde_faturada"     => 0,
                            "qtde_cancelada"    => 0,
                            "preco"             => $preco,
                            "total_item"        => $preco * $peca["qtde"]
                        );

print_r($dadosItens);


                        $pedidoClass->gravaItem($dadosItens, $pedido);

                        /*
                        * Resgata o Pedido Item
                        */
                        $pedido_item = $pedidoClass->getPedidoItem();

                        /*
                        * Atualiza os Pedidos Item na OS Item
                        */
                        $pedidoClass->atualizaOsItemPedidoItem($peca["os_item"], $pedido, $pedido_item, $fabrica);

                        $array_posto[$posto][$pedido][] = $peca["peca"];
                    }
                    //chamado 3325514 - Fim
                }
            }

            $osClass->_model->atualizaCustoItem(array($pedido));
            $pedidoClass->finaliza($pedido);
            /*
            * Commit
            */
            $pedidoClass->_model->getPDO()->commit();
        } catch(Exception $e) {
echo $e->getMessage();
            $pedidoClass->_model->getPDO()->rollBack();

            $msg_erro[] = $e->getMessage();

            continue;
        }

    }

    if(!empty($msg_erro)){

        $logClass->adicionaLog(implode("<br />", $msg_erro));
        $logClass->enviaEmails();
        
        $fp = fopen("/tmp/{$fabrica_nome}/log-erro.text", "a");
        fwrite($fp, "Data Log: " . date("d/m/Y") . "\n");
        fwrite($fp, implode("\n", $msg_erro));
        fclose($fp);

    }

    $phpCron->termino();

} catch (Exception $e) {
    echo $e->getMessage();
}
