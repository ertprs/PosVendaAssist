<?php
include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
include dirname(__FILE__) . '/../../autentica_usuario.php';

use Lojavirtual\CarrinhoCompra;
use Lojavirtual\Comunicacao;
use Lojavirtual\LojaCliente;

$objCarrinhoCompra = new CarrinhoCompra();
$objComunicacao    = new Comunicacao($externalId);
$objLojaCliente = new LojaCliente();

$dadosCliente = $objLojaCliente->get(null, $login_posto);

/* INSERE PEDIDO  */
if ($_POST["ajax_insere_pedido_sem_checkout"] == true) {

	$dadosCarrinho     = $objCarrinhoCompra->getAllCarrinho($dadosCliente["loja_b2b_cliente"]);

	if (empty($dadosCarrinho)) {
		exit(json_encode(array("erro" => true, "msg" => utf8_encode("Não tem itens no carrinho!"))));
	}

	if ($login_fabrica == 42) {
		$retorno = $objCarrinhoCompra->finalizaCarrinhoFornecedor($dadosCarrinho);
	} else {
		$retorno = $objCarrinhoCompra->geraPedidoB2B($dadosCarrinho);
	}

	if ($retorno["sucesso"]) {
		if ($login_fabrica != 42) {
        	$dadosPedido = $objCarrinhoCompra->getPedidoB2B($retorno["pedido"]);

        	$dadosPedido["email_posto"] = $login_email;
        	$objComunicacao->enviaNovoPedido($dadosPedido);
		} else {

			$email_fornecedor = $objCarrinhoCompra->retornaFornecedorEmail($dadosCarrinho['loja_b2b_carrinho']);

			if (!empty($email_fornecedor)) {
				$dadosCarrinho['email_fornecedor'] = $email_fornecedor;
				$dadosCarrinho['pedido']      = $dadosCarrinho['loja_b2b_carrinho'];
				$retorno['pedido']            = $dadosCarrinho['loja_b2b_carrinho'];
				
				$objComunicacao->enviaNovoPedidoFornecedor($dadosCarrinho);
			} else {
				exit(json_encode(array("erro" => true, "msg" => "E-mail fornecedor não encontrado")));
			}
			
		}

        
		exit(json_encode(array("sucesso" => true, "msg" => $retorno["msg"], "pedido" => $retorno["pedido"])));
	} else {
		exit(json_encode(array("erro" => true, "msg" => $retorno["msg"])));
	}

}


