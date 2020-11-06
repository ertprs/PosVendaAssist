<?php

include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
include dirname(__FILE__) . '/../../autentica_usuario.php';
use Lojavirtual\CarrinhoCompra;
use Lojavirtual\Comunicacao;
use Lojavirtual\Loja;
use Lojavirtual\Checkout;
use Lojavirtual\LojaCliente;

$objLoja               = new Loja();
$configLoja            = $objLoja ->getConfigLoja();
$configLojaFrete       = json_decode($configLoja["pa_forma_envio"], 1);
$configLojaPagamento   = json_decode($configLoja["pa_forma_pagamento"], 1);

if (isset($_GET['method_pagamento']) && $_GET['method_pagamento'] == "CREDIT_CARD") {

    $dadosFrete = array();
    if (count($objLoja->_loja_config["forma_envio"]["meio"][$_REQUEST['tipoEnvio']]) > 0) {

        $formaEnvio = $_REQUEST['formaEnvio'];
        $tipoEnvio  = $_REQUEST['tipoEnvio'];
        list($servicoEnvio, $diasEnvio, $valorEnvio, $codigoEnvio) = explode("|", $formaEnvio);
        $dadosFrete["codigoEnvio"]  = $codigoEnvio;
        $dadosFrete["servicoEnvio"] = $servicoEnvio;
        $dadosFrete["diasEnvio"]    = $diasEnvio;
        $dadosFrete["valorEnvio"]   = $valorEnvio;

    }
    
    $posto = $_GET['posto'];
    $objLojaCliente    = new LojaCliente();
    $dadosCliente      = $objLojaCliente->get(null,$posto);

    $objCarrinhoCompra = new CarrinhoCompra();
    $objComunicacao    = new Comunicacao($externalId);
    $objCheckout       = new Checkout();

    //pega dados do carrinho 
    $dadosCarrinho     = $objCarrinhoCompra->getAllCarrinho($dadosCliente["loja_b2b_cliente"]);

    if (empty($dadosCarrinho)) {
        exit(json_encode(array("erro" => true, "msg" => utf8_encode("Não tem itens no carrinho!"))));
    }


    //$res = pg_query ($con,"BEGIN TRANSACTION");
    //gera pedido
    $retorno = $objCarrinhoCompra->geraPedidoB2B($dadosCarrinho, true, $dadosFrete);
    if ($retorno["erro"]) {
        exit(json_encode(array("erro" => true, "msg" => $retorno["msg"])));
    }

    //busca pedido gerado
    $dadosPedido = $objCarrinhoCompra->getPedidoB2B($retorno["pedido"]);

    //processa pagamento pagseguro
    if (isset($configLojaPagamento["meio"]["pagseguro"]) && $configLojaPagamento["meio"]["pagseguro"]["status"] == "1" && $_POST["integrador"] == "pagseguro") {
        $resultado = $objCheckout->processaPagSeguro($dadosPedido, $_GET['method_pagamento'], $login_posto, $_POST, $dadosFrete);

    /*    if ($resultado["erro"] == true) {
            $res = pg_query ($con,"ROLLBACK TRANSACTION");
        } else {
            $res = pg_query ($con,"COMMIT TRANSACTION");
        }*/

        exit(json_encode($resultado));
    }

    //processa pagamento cielo
    if (isset($configLojaPagamento["meio"]["cielo"]) && $configLojaPagamento["meio"]["cielo"]["status"] == "1" && $_POST["integrador"] == "cielo") {
        $resultado = $objCheckout->processaCielo($dadosPedido, $_GET['method_pagamento'], $login_posto, $_POST, $dadosFrete);

       /* if ($resultado["erro"] == true) {
            $res = pg_query ($con,"ROLLBACK TRANSACTION");
        } else {
            $res = pg_query ($con,"COMMIT TRANSACTION");
        }*/

        exit(json_encode($resultado));
    }
}

if (isset($_GET['method_pagamento']) && $_GET['method_pagamento'] == "BOLETO") {

    $dadosFrete = array();
    if (count($objLoja->_loja_config["forma_envio"]["meio"][$_REQUEST['tipoEnvio']]) > 0) {

        $formaEnvio = $_REQUEST['formaEnvio'];
        $tipoEnvio  = $_REQUEST['tipoEnvio'];
        list($servicoEnvio, $diasEnvio, $valorEnvio, $codigoEnvio) = explode("|", $formaEnvio);
        $dadosFrete["codigoEnvio"] 	= $codigoEnvio;
        $dadosFrete["servicoEnvio"] = $servicoEnvio;
        $dadosFrete["diasEnvio"] 	= $diasEnvio;
        $dadosFrete["valorEnvio"] 	= $valorEnvio;

    }
    

    $posto             = $_GET['posto'];
    $objLojaCliente    = new LojaCliente();
    $dadosCliente      = $objLojaCliente->get(null,$posto);

	$objCarrinhoCompra = new CarrinhoCompra();
    $objComunicacao    = new Comunicacao($externalId);
	$objCheckout       = new Checkout();

	//pega dados do carrinho 
	$dadosCarrinho     = $objCarrinhoCompra->getAllCarrinho($dadosCliente["loja_b2b_cliente"]);
	if (empty($dadosCarrinho)) {
		exit(json_encode(array("erro" => true, "msg" => utf8_encode("Não tem itens no carrinho!"))));
	}



    //$res = pg_query ($con,"BEGIN TRANSACTION");
    //gera pedido
	$retorno = $objCarrinhoCompra->geraPedidoB2B($dadosCarrinho, true, $dadosFrete);
	if ($retorno["erro"]) {
		exit(json_encode(array("erro" => true, "msg" => $retorno["msg"])));
	}

    //busca pedido gerado
    $dadosPedido = $objCarrinhoCompra->getPedidoB2B($retorno["pedido"]);

    //processa pagamento pagseguro
    if (isset($configLojaPagamento["meio"]["pagseguro"]) && $configLojaPagamento["meio"]["pagseguro"]["status"] == "1" && $_POST["integrador"] == "pagseguro") {

        $resultado = $objCheckout->processaPagSeguro($dadosPedido, $_GET['method_pagamento'], $login_posto, $_POST, $dadosFrete);
       /* if ($resultado["erro"] == true) {
            $res = pg_query ($con,"ROLLBACK TRANSACTION");
        } else {
            $res = pg_query ($con,"COMMIT TRANSACTION");
        }*/

        exit(json_encode($resultado));
    }

    //processa pagamento cielo
    if (isset($configLojaPagamento["meio"]["cielo"]) && $configLojaPagamento["meio"]["cielo"]["status"] == "1" && $_POST["integrador"] == "cielo") {
        $resultado = $objCheckout->processaCielo($dadosPedido, $_GET['method_pagamento'], $login_posto, $_POST, $dadosFrete);
        if ($resultado["erro"] == true) {
            //$res = pg_query ($con,"ROLLBACK TRANSACTION");
        } else {
           // $res = pg_query ($con,"COMMIT TRANSACTION");
        }

        exit(json_encode($resultado));
    }

    //processa pagamento maxipago
    if (isset($configLojaPagamento["meio"]["maxipago"]) && $configLojaPagamento["meio"]["maxipago"]["status"] == "1" && $_POST["integrador"] == "maxipago") {
        $resultado = $objCheckout->processaMaxiPago($dadosPedido, $_GET['method_pagamento'], $login_posto, $_POST, $dadosFrete);
        
        if ($resultado["erro"] == true) {
            //$res = pg_query ($con,"ROLLBACK TRANSACTION");
        } else {
           // $res = pg_query ($con,"COMMIT TRANSACTION");
        }

        exit(json_encode($resultado));
    }

}
