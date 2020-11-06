<?php
include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
include dirname(__FILE__) . '/../../autentica_usuario.php';

use Lojavirtual\Frete;
use Lojavirtual\CarrinhoCompra;

$objFrete    = new Frete();
$objCarrinho = new CarrinhoCompra();

if ($_GET["metodo"] == "correios") {
    $carrinho = $_POST['carrinho'];
    $cepPosto           = $_POST["cep"];
    $id_fornecedor           = $_POST["id_fornecedor"];

    if ($login_fabrica == 42) {
        $cepOrigem    = $objFrete->getCepOrigem($con, $id_fornecedor, $login_fabrica);
        $dadosProduto = $objCarrinho->getItensCarrinho($carrinho);

        $pesoProduto  = 0;
        $arr_altura = [];
        $arr_largura = []; 
        $arr_comprimento = [];

        foreach ($dadosProduto as $key => $val) {

            $pesoProduto      += ($val['produto']['peso'] * $val['qtde']);
            $arr_altura[]      = $val['produto']['altura'];
            $arr_largura[]     = $val['produto']['largura'];
            $arr_comprimento[] = $val['produto']['comprimento'];

        }

        $alturaProduto      = max($arr_altura);
        $larguraProduto     = max($arr_largura);
        $comprimentoProduto = max($arr_comprimento);

    } else {
        $cepOrigem          = $objFrete->getCepOrigem($con, "", $login_fabrica);
        $alturaProduto      = "15";//$_POST[""];
        $comprimentoProduto = "17";//$_POST[""];
        $larguraProduto     = "12";//$_POST[""];
        $pesoProduto        = "1";//$_POST[""];

    }

    $retorno = $objFrete->calculaPrazoValorCorreiosSigep($cepPosto, $alturaProduto, $comprimentoProduto, $larguraProduto, $pesoProduto, $cepOrigem);
    if ($retorno["erro"] == true) {
        exit(json_encode($retorno));
    }
    exit(json_encode(["erro" => false, "formas" => $retorno]));

}
