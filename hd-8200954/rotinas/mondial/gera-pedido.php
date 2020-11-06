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
    $fabrica = 151;
    $data = date('d-m-Y');

    $env = "producao1";
    /*
    * A variavel $param defire se o pedido vai ser por posto ou OS, sendo o padr�o pedido por posto
    */

    $os_argv = $argv[1];

    $param = "os"; /* posto | os */

    /*
    * Log
    */
    $logClass = new Log2();
    $logClass->adicionaLog(array("titulo" => "Log erro - Gera��o de Pedidos Mondial Brasil")); // Titulo
    if ($env == 'producao' ) {
        $logClass->adicionaEmail("helpdesk@telecontrol.com.br");
    } else {
        $logClass->adicionaEmail("breno.sabella@telecontrol.com.br");
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

    $os_garantia = $osClass->getOsGarantia($param,$os_argv);

    if(empty($os_garantia)){
        exit;
    }

    /*
    * Mensagem de Erro
    */
    $msg_erro = array();

    $pedidoClass = new \Posvenda\Pedido($fabrica, null, $param);

    /*
    * Resgata a condi��o da Fabrica
    */
    $condicao = $pedidoClass->getCondicaoGarantia();

    /*
    * Resgata a condi��o da Fabrica
    */
    $tipo_pedido = $pedidoClass->getTipoPedidoGarantia("GARANTIA - PE�AS");

    $contador_os_garantia = count($os_garantia);

    for ($i = 0; $i < $contador_os_garantia; $i++) {
        try {
            $posto = $os_garantia[$i]["posto"];

			if($posto == 6359) {
				continue;
			}
            /*
            * Begin
            */
            $pedidoClass->_model->getPDO()->beginTransaction();

            $dados = array(
                "posto"         => $posto,
                "tipo_pedido"   => $tipo_pedido,
                "condicao"      => $condicao,
                "fabrica"       => $fabrica,
                "status_pedido" => 1
            );

            /*
            * Grava o Pedido
            */
            $os = $os_garantia[$i]["os"];

            $pedidoClass->grava($dados, null);
            $pedido = $pedidoClass->getPedido();

            $dadosItens = array();

            /**
             * Pega as pe�as da OS
             */
			$osClass->setOs($os);
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

        } catch(Exception $e) {
            $pedidoClass->_model->getPDO()->rollBack();
            $msg_erro[] = $e->getMessage();
            continue;
        }
    }

    if(!empty($msg_erro)){

	if (isset($argv[1])) {
		echo json_encode(array("erro" => utf8_encode($msg_erro[0])));
	} else {
        	$logClass->adicionaLog(implode("<br />", $msg_erro));

	        $logClass->enviaEmails();

	        $fp = fopen("tmp/{$fabrica_nome}/log-erro".date("d-m-Y_H-i-s").".txt", "a");
        	fwrite($fp, "Data Log: " . date("d/m/Y") . "\n");
        	fwrite($fp, implode("\n", $msg_erro));
	        fclose($fp);
	}
    } else if (isset($argv[1])) {
	echo json_encode(array("sucesso" => "true"));
    }

    $phpCron->termino();

} catch (Exception $e) {
    echo $e->getMessage();
}
