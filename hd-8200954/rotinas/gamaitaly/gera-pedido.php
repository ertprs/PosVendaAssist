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
    $fabrica = 164;
    $data = date('d-m-Y');

    $env = ($_serverEnvironment == 'development') ? 'teste' : 'producao';

    /*
    * A variavel $param defire se o pedido vai ser por posto ou OS, sendo o padrão pedido por posto
    */

    $param = "posto"; /* posto | os */

    /*
    * Log
    */
    $logClass = new Log2();
    $logClass->adicionaLog(array("titulo" => "Log erro - Geração de Pedidos Gama Italy")); // Titulo
    if ($env == 'producao' ) {
        $logClass->adicionaEmail("helpdesk@telecontrol.com.br");
        $logClass->adicionaEmail("fabricia.carmo@gamaitaly.com.br");
        $logClass->adicionaEmail("heidy.batista@gamaitaly.com.br");
        $logClass->adicionaEmail("roberta.ricomini@gamaitaly.com.br");
	$logClass->adicionaEmail("cleonice.maria@gamaitaly.com.br");
	$logClass->adicionaEmail("ronald.santos@telecontrol.com.br");
    } else {
        $logClass->adicionaEmail("ronald.santos@telecontrol.com.br");
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
    $fabrica_nome = "gamaitaly";//$fabricaClass->getNome();

    /*
    * Resgata as OSs em Garantia
    */
    $osClass = new \Posvenda\Os($fabrica);
    $os_garantia = $osClass->getOsGarantia($param);
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
    include "classes/verificaSaldoPeca.php";
    $verificaSaldoPeca = new verificaSaldoPeca($fabrica);

    $previsao_entrega = date('Y-m-d',strtotime('+7 days',strtotime(date('Y-m-d'))));
    $array_posto = 0;

    for ($i = 0; $i < count($os_garantia); $i++) {


            $posto = $os_garantia[$i]["posto"];
	    $pedido = "";
            $os_pedido_posto = $osClass->getOsPosto($posto);

            if ((is_array($os_pedido_posto) && count($os_pedido_posto) == 0) || (empty($os_pedido_posto)) || $posto == 6359) {
                continue;
            }

            /*
            * Verifica Saldo da peça
            */

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

            /*
            * Grava o Pedido
            */
            for ($x = 0; $x < count($os_pedido_posto); $x++) {

        	try {
			
			$msg_erro_posto = array();
			$estoque_peca_suficiente = "";
			$os = $os_pedido_posto[$x]["os"];

			$estoque_peca_suficiente = $verificaSaldoPeca->retornaAuditoriaOsPecas($os);

			if($estoque_peca_suficiente == false){
			    continue;
			}

			if (strlen($pedido) == 0) {
			    $pedidoClass->grava($dados);
			    $pedido = $pedidoClass->getPedido();
			}

			$dadosItens = array();

			/**
			 * Resgata as peças da OS
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

			    $array_posto[$posto][$pedido][] = $peca["peca"];

			}
		} catch(Exception $e) {


			$msg_erro[] = $e->getMessage();
			$msg_erro_posto[] = $e->getMessage();
            		continue;

        	}
	    }

	    if(count($msg_erro_posto) > 0){
		
            	    $pedidoClass->_model->getPDO()->rollBack();

	    }else{ 

		    if (strlen($pedido) > 0) {
			$pedidoClass->finaliza($pedido);
		    }
		    /*
		    * Commit
		    */
		    $pedidoClass->_model->getPDO()->commit();
	    }
    }

    if(!empty($msg_erro)){

        $logClass->adicionaLog(implode("<br />", $msg_erro));
        $logClass->enviaEmails();

        $fp = fopen("tmp/{$fabrica_nome}/logs/pedido-log-erro".date('Y-m-d').".text", "a");
        fwrite($fp, "Data Log: " . date("d/m/Y") . "\n");
        fwrite($fp, implode("\n", $msg_erro));
        fclose($fp);

    }

    $phpCron->termino();

} catch (Exception $e) {
    echo $e->getMessage();
}
