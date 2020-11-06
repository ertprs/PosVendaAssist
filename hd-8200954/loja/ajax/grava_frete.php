<?php
include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
include dirname(__FILE__) . '/../../autentica_usuario.php';

use Lojavirtual\Frete;
use Lojavirtual\CarrinhoCompra;

$objFrete    = new Frete();
$objCarrinho = new CarrinhoCompra();

if (isset($_REQUEST['totalFrete'])) {

    $carrinho   = $_REQUEST['carrinho'];
    $tipoEnvio  = $_REQUEST['tipoEnvio'];
    $totalFrete = $_REQUEST['totalFrete'];

    $retorno = $objCarrinho->gravaFreteCarrinho($carrinho, $tipoEnvio, $totalFrete);

    if ($retorno) {
        exit("sucesso");
    } else {
        exit("erro");
    }

}