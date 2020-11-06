<?php
/*ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);*/
include_once "class/tdocs.class.php";

use Lojavirtual\Loja;
use Lojavirtual\Banner;
use Lojavirtual\AviseMe;
use Lojavirtual\Produto;
use Lojavirtual\CupomDesconto;
use Lojavirtual\Categoria;
use Lojavirtual\CarrinhoCompra;
use Lojavirtual\Comunicacao;
use Lojavirtual\LojaCliente;
use Lojavirtual\Fornecedor;
use Lojavirtual\Checkout;

$objCheckout       = new Checkout();

$objComunicacao    = new Comunicacao(null,null,$externalId);
$objLoja           = new Loja();
$objBanner         = new Banner();
$objAviseMe        = new AviseMe();
$objLojaCliente    = new LojaCliente();
$dadosCliente      = $objLojaCliente->get(null,$login_posto);
$objFornecedor     = new Fornecedor();

if (empty($dadosCliente)) {
    $objLojaCliente->savePosto($login_posto);
}
$objProduto        = new Produto($login_posto,$dadosCliente["loja_b2b_cliente"]);
$objCupomDesconto  = new CupomDesconto();
$objCategoria      = new Categoria();
$objCarrinhoCompra = new CarrinhoCompra();
$TDocs             = new TDocs($con, $login_fabrica);
$TDocs->setContext('lojalogo');

$configLoja        = $objLoja->getConfigLoja();

$configLojaFrete     = json_decode($configLoja["pa_forma_envio"], 1);
$configLojaPagamento = json_decode($configLoja["pa_forma_pagamento"], 1);

if (isset($configLojaPagamento["meio"]["pagseguro"]["ambiente"]) && $configLojaPagamento["meio"]["pagseguro"]["ambiente"] == "sandbox") {

    $xmail          = $configLojaPagamento["meio"]["pagseguro"]["email_sandbox"];
    $xtoken         = $configLojaPagamento["meio"]["pagseguro"]["token_sandbox"];
    $url_sessao     = "https://ws.sandbox.pagseguro.uol.com.br/v2/sessions?email=$xmail&token=$xtoken";

} else {

    $xmail          = $configLojaPagamento["meio"]["pagseguro"]["email_producao"];
    $xtoken         = $configLojaPagamento["meio"]["pagseguro"]["token_producao"];
    $url_sessao     = "https://ws.pagseguro.uol.com.br/v2/sessions?email=$xmail&token=$xtoken";

}
$status_pagseguro = array();
if (isset($configLojaPagamento["meio"]["pagseguro"])) {
    $status_pagseguro = $configLojaPagamento["meio"]["pagseguro"]["status_pagamento"];
}

function getSessaoPagSeguro() {
    global $url_sessao;
    $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => $url_sessao,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {
        return $err;
    } else {
        $sessao = simplexml_load_string($response);
        foreach ($sessao as $key => $value) {
            $sessao_id =  $value;
        }
        return $sessao_id;
    }
}
$sessao_id = getSessaoPagSeguro();
$totalItemCarrinho = $objCarrinhoCompra->getTotalItemCarrinho($dadosCliente["loja_b2b_cliente"]);
$logoLoja          = $TDocs->getDocumentsByRef($objLoja->loja)->url;
$logomarca_loja    = (!empty($logoLoja)) ? $logoLoja : "loja/layout/img/logo.png";

if (empty($objLoja->_loja)) {
    exit('<div class="alert alert-error"><h4>'.traduz('loja.não.encontrada').'.</h4></div>');
}
?>
<link type="text/css" rel="stylesheet" href="loja/layout/css/loja.php?loja=<?php echo $objLoja->loja;?>&v=<?php echo date("YmdHis");?>" />

<div id="eco_topo" class="navbar navbar-inverse">
    <!-- <div class="eco_top"></div> -->
    <div class="eco_info_login" align="center">
        <?php echo traduz('olá');?> <b><?php echo $login_codigo_posto;?> - <?php echo $login_nome;?></b>, <?php echo traduz('bem.vindo.a.loja.b2b');?>!
    </div>
     <div class="navbar-inner">
        <div class="container">
            <div class="row-fluid">
                <div class="span3">
                    <a class="brand" href="loja_new.php">
                        <img src="<?php echo $logomarca_loja;?>" class="eco_logo" style="height: 80px !important; " />
                    </a>
                </div>
                <div class="span6" align="center" style="text-align: center;">
                <form action="" method="get">
                    <div class="input-append eco_margin_top30" style="text-align: center;">
                        <input class="span11 eco_input_busca" placeholder="<?php echo traduz('digite.uma.palavra.chave');?>..." id="busca" name="busca" type="text">
                        <button class="btn eco_btn_busca_ok eco_btn_busca" type="submit">OK</button>
                    </div>
                    <p class="pull-right"><a href="loja_new.php?pg=busca-avancada" class="eco_margin_top0"><?php echo traduz('busca.avançada');?></a></p>
                </form>
                </div>
                <div class="span3">
                    <div class="row eco_margin_top20">
                        <div class="span2"></div>
                        <div class="span10 eco_text_align_right eco_text_carrinho">
                            <h4 class="eco_margin_top10">                          
                                <i class="fa fa-shopping-cart"></i> 
                                <b><?php echo strtoupper(traduz('meu.carrinho'));?></b>
                            </h4>
                        </div>
                    </div>
                    <div class="row">
                        <div class="span2"></div>
                        <div class="span10 eco_text_align_right">
                            <?php echo traduz('total.de.iten');?>(s) - <b class="totalItens"><?php echo $totalItemCarrinho['count'];?> un</b>
                            <?php if ($totalItemCarrinho['count'] > 0) {?>
                            <br /><a href="loja_new.php?pg=carrinho"><?php echo traduz('ir.ao.carrinho');?></a>
                            <?php }?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
