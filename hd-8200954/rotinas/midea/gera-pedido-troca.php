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
    $fabrica = 169;
    $data = date('d-m-Y');

    $env = "producao";

    /*
    * A variavel $param defire se o pedido vai ser por posto ou OS, sendo o padrão pedido por posto
    */

    $param = "troca-produto"; /* posto | os */

    /* 
    * Log 
    */
    $logClass = new Log2();
    $logClass->adicionaLog(array("titulo" => "Log erro - Geração de Pedidos Midea")); // Titulo

    if ($_serverEnvironment == 'development') {
        $logClass->adicionaEmail("maicon.luiz@telecontrol.com.br");
    } else {
        $logClass->adicionaEmail('helpdesk@telecontrol.com.br');
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
    $osClass = new \Posvenda\Model\Os($fabrica);
    $os_garantia = $osClass->selectOsGarantia($param);

    if(empty($os_garantia)){
		$phpCron->termino('Sem OS para gerar');
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
    $begin = false;

    $tabela_padrao = $pedidoClass->_model->getTabelaId("GAR");

    for ($i = 0; $i < count($os_garantia); $i++) {
        try {
    
            $posto = $os_garantia[$i]["posto"];
            $os = $os_garantia[$i]["os"];

            /*
            * Begin
            */
            $pedidoClass->_model->getPDO()->beginTransaction();
	    $begin = true;

            $dados = array(
                "posto"         => $posto,
                "tipo_pedido"   => $tipo_pedido,
                "condicao"      => $condicao,
                "fabrica"       => $fabrica,
                "troca"         => "TRUE",
                "total"         => 0,
                "status_pedido" => 1,
                "finalizado"       => "'".date("Y-m-d H:i:s")."'"
            );

            /*
            * Grava o Pedido
            */
            $pedidoClass->grava($dados);
            $pedido = $pedidoClass->getPedido();

            $dadosItens = array();
            
            /**
             * Pega as peças da OS
             */
            $osClass = new \Posvenda\Os($fabrica, $os);

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
                    "troca_produto"     => "TRUE",
                    "preco"             => $pedidoClass->getPrecoPecaGarantia($peca["peca"], $os, $tabela_padrao)
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
        
            $pedidoClass->registrarPedidoExportado($pedido);

            /*
            * Commit
            */
            $pedidoClass->_model->getPDO()->commit();
	    $begin = false;

            $osFabricaClass = new \Posvenda\Fabricas\_169\Os($fabrica,$os);

            // Integração Notificação
            $notaIntegracao = $osFabricaClass->getDadosNotaExport($os);
            $notificacao = $osFabricaClass->exportNotificacao($notaIntegracao);

            if ($notificacao === true) {
                // Integração Ordem de Serviço
                $osIntegracao = $osFabricaClass->getDadosOSExport($os);
                $result = $osFabricaClass->exportOS($osIntegracao);
            }

        } catch(Exception $e) {
	    if ($begin == true) {
            	$pedidoClass->_model->getPDO()->rollBack();
	    }
            $msg_erro[] = $e->getMessage();
            continue;
        }
    }

    if(!empty($msg_erro)){

        $logClass->adicionaLog(implode("<br />", $msg_erro));
        $fp = fopen("/tmp/midea/log-erro-gera-pedido-troca".date("d-m-Y_H-i-s").".txt", "a");
        fwrite($fp, "Data Log: " . date("d/m/Y") . "\n");
        fwrite($fp, implode("\n", $msg_erro));
        fclose($fp);


        if($logClass->enviaEmails() == "200"){
          echo "Log de erro enviado com Sucesso!";
        }else{
          $logClass->enviaEmails();
        }

    }

    $phpCron->termino();

} catch (Exception $e) {
    echo $e->getMessage();
}
