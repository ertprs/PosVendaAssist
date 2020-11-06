<?php
include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
include dirname(__FILE__) . '/../../autentica_usuario.php';

use Lojavirtual\CarrinhoCompra;
use Lojavirtual\Produto;
use Lojavirtual\LojaCliente;

$objLojaCliente    = new LojaCliente();
$dadosCliente      = $objLojaCliente->get(null,$login_posto);
$objProduto        = new Produto($login_posto,$dadosCliente["loja_b2b_cliente"]);
$objCarrinhoCompra = new CarrinhoCompra();

if ($_GET["ajax_atualiza_total_item"] == true) {

    $totalItemCarrinho = $objCarrinhoCompra->getTotalItemCarrinho($dadosCliente["loja_b2b_cliente"]);
    exit(json_encode(array("total" => $totalItemCarrinho["count"])));

}

/* REMOVE ITEM DO CARRINHO DE COMPRAS */
if ($_POST["ajax_remove_item"] == true) {

    $id = $_POST["id"];

    if (empty($id)) {
        exit(json_encode(array("erro" => true, "msg" => utf8_encode("Item não enviado!"))));
    }
    $kit = false;
    if (isset($_POST["kit"]) && $_POST["kit"] == true) {
        $kit = $_POST["kit"];
    }
    $retorno = $objCarrinhoCompra->removeItemCarrinho($id, $kit);

	if ($retorno["sucesso"]) {
		exit(json_encode(array("sucesso" => true, "msg" => utf8_encode($retorno["msg"]))));
	} else {
		exit(json_encode(array("erro" => true, "msg" => utf8_encode($retorno["msg"]))));
	}
}

/* ATUALIZA QUANTIDADE ITEM DO CARRINHO DE COMPRAS */
if ($_POST["ajax_atualiza_item"] == true) {

    $id = $_POST["id"];
    $qtde = $_POST["qtde"];
    $kitpeca = $_POST["kitpeca"];
    $idcarrinho = $_POST["idcarrinho"];

    if (empty($id)) {
        exit(json_encode(array("erro" => true, "msg" => utf8_encode("Item não enviado!"))));
    }
        $retorno = array();

        if (isset($kitpeca) && strlen($kitpeca) > 0) {

            $dados["loja_b2b_kit_peca"] = $kitpeca;
            $dados["loja_b2b_carrinho"] = $idcarrinho;
            $resgataItens = $objCarrinhoCompra->verificaItemCarrinho($dados, true);

            foreach ($resgataItens as $key => $value) {

                $novaQtde = 0;
                for ($i=1; $i <= $qtde; $i++) { 

                    $dadosKit = $objProduto->getItensKit($kitpeca, $value["loja_b2b_peca"]);
                    $novaQtde += $dadosKit["qtde"];
                	$retorno = $objCarrinhoCompra->atualizaItemCarrinho($value["loja_b2b_carrinho_item"], $novaQtde);
                	
                }
                $retorno = $objCarrinhoCompra->atualizaItemCarrinho($value["loja_b2b_carrinho_item"], $novaQtde);
            }
        } else {
            $retorno = $objCarrinhoCompra->atualizaItemCarrinho($id, $qtde);
        }

        if ($retorno["sucesso"]) {
            exit(json_encode(array("sucesso" => true, "msg" => utf8_encode($retorno["msg"]))));
        } else {
            exit(json_encode(array("erro" => true, "msg" => utf8_encode($retorno["msg"]))));
        }
}


/* VERIFICA SE EXISTE ITENS NO CARRINHO À MAIS DE 48H */
if ($_POST["ajax_remove_itens_expirados_carrinho"] == true) {

    if($login_fabrica == 42){

        $dados = $objCarrinhoCompra->verificaCarrinhoAberto($dadosCliente["loja_b2b_cliente"]);

        if(isset($dados['loja_b2b_carrinho']) && !empty($dados['loja_b2b_carrinho'])){
            $result = $objCarrinhoCompra->removeItensExpiradosCarrinho($dados['loja_b2b_carrinho']);
        }
    }else{
        $result = ['erro' => false, 'removidos' => false, 'msg' => 'Fabrica nao adicionada nesta condicao'];
    }

   
    exit(json_encode($result));
}

