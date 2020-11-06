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
    $fabrica = 188;
    $data = date('d-m-Y');

    $env = "producao";

    $os_para      = strtolower($argv[1]);

    /*
    * A variavel $param defire se o pedido vai ser por posto ou OS, sendo o padrão pedido por posto
    */
    if(!empty($os_para)){
        $param = "os"; /* posto | os */
    }else{
        $param = "posto"; /* posto | os */
		$os_para = null;
    } 


    /* 
    * Log 
    */
    $logClass = new Log2();
    $logClass->adicionaLog(array("titulo" => "Log erro - Geração de Pedidos ingco")); // Titulo
    if ($env == 'producao' ) {
        $logClass->adicionaEmail("helpdesk@telecontrol.com.br");
        //$logClass->adicionaEmail("filipe.souza@esab.com.br");
    } else {
        $logClass->adicionaEmail("lucas.carlos@telecontrol.com.br");
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
    $os_garantia = $osClass->getOsGarantia($param, $os_para);


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

            $os_pedido_posto = $osClass->getOsPosto($posto, null, $os_para);

            if ((is_array($os_pedido_posto) && count($os_pedido_posto) == 0) || (empty($os_pedido_posto))) {
                continue;
            }

            /* 
            * Begin
            */
            $pedidoClass->_model->getPDO()->beginTransaction();

            $dados = array(
                "posto"            => $posto,
                "tipo_pedido"      => $tipo_pedido,
                "condicao"         => $condicao,
                "fabrica"          => $fabrica,
                "distribuidor"     => 4311,
                "status_pedido"    => 1 
            );

            /*
            * Grava o Pedido
            */
            $pedidoClass->grava($dados);

            $pedido = $pedidoClass->getPedido();

            $desconto = $pedidoClass->aplicaDesconto($pedido);

            //$comunicado .= "<li>Pedido: ".$pedido." - Previsão de faturamento: ".date("d/m/Y",strtotime($previsao_entrega))."</li>";
           
            $os_aguardando_estoque = [];

            //if(empty($param) or $param == "os"){
            for ($x = 0; $x < count($os_pedido_posto); $x++) {

                $os = $os_pedido_posto[$x]["os"];

                $dadosItens = [];
                
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
                    $emailPecaPeco = new Log2();
                    $emailPecaPeco->adicionaLog(array("titulo" => "Log - Peças sem preço. ")); // Titulo

                    $preco = $pedidoClass->getPrecoPecaGarantia($peca["peca"], $os);

                    $preco = round($preco - ($preco * ($desconto/100)), 2);

                    if($preco == '10000' or $preco == '0'){
                        $emailPecaPeco->adicionaLog("Mensagem: Peça estava sem preço, foi adicionado preço $preco para gerar o pedido.");
                        $emailPecaPeco->adicionaLog("Peça: ". $peca['referencia']);
                        $emailPecaPeco->enviaEmails();
                    }
                    
                    $peca_alternativa = "";
                    if ($pedidoClass->verificaEstoquePecaDistrib($peca['peca'], $peca["qtde"])) {
                        $peca_alternativa = $pedidoClass->verificaPecaAlternativa($peca['peca'], $peca["qtde"]);
                        if(!$peca_alternativa){
                            $os_aguardando_estoque[] = $os;
                        }
                    }                                

                    if (!empty($peca_alternativa)) {
                        $dadosItens[] = array(
                            "pedido"            => (int)$pedido,
                            "peca"              => $peca["peca"],
                            "qtde"              => $peca["qtde"],
                            "qtde_faturada"     => 0,
                            "qtde_cancelada"    => 0,
                            "preco"             => $preco,
                            "total_item"        => $preco * $peca["qtde"],
                            "peca_alternativa"  => $peca_alternativa
                        );
                    } else {
                        $dadosItens[] = array(
                            "pedido"            => (int)$pedido,
                            "peca"              => $peca["peca"],
                            "qtde"              => $peca["qtde"],
                            "qtde_faturada"     => 0,
                            "qtde_cancelada"    => 0,
                            "preco"             => $preco,
                            "total_item"        => $preco * $peca["qtde"]
                        );
                    }

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

                    if (!empty($peca_alternativa) && !empty($pedido_item)) {
                        $pedidoClass->gravaObsPecaAlternativa($peca, $peca_alternativa, $pedido_item, $os, $peca["os_item"]);
                    }
                }
            }

            $pedidoClass->finaliza($pedido);
            /*
            * Commit
            */

            $pedidoClass->_model->getPDO()->commit();

            /*foreach ($os_aguardando_estoque as $id_os) {

                $pedidoClass->atualiza_status_checkpoint($id_os, 'Aguard. Abastecimento Estoque');
                
            }*/

        } catch(Exception $e) {
            $pedidoClass->_model->getPDO()->rollBack();

            $msg_erro[] = $e->getMessage();

            continue;
        }

    }

    if(!empty($msg_erro)){

        $logClass->adicionaLog(implode("<br />", $msg_erro));
        $logClass->enviaEmails();

        $fp = fopen("/tmp/{$fabrica_nome}/pedidos/log-erro.text", "a");
        fwrite($fp, "Data Log: " . date("d/m/Y") . "\n");
        fwrite($fp, implode("\n", $msg_erro));
        fclose($fp);

    }

    $phpCron->termino();

} catch (Exception $e) {
    echo $e->getMessage();
}
