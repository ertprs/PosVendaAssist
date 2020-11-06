<?php 
    $anoAtual       = intval(date('Y'));
    $sessao_id      = getSessaoPagSeguro();

    if ($_REQUEST['codigo_produto']) {
        $produto     = trim($_REQUEST['codigo_produto']);
        $qtdeProduto = trim($_REQUEST['qtde_produto']);
        $qtdeProduto = empty($qtdeProduto) ? 1 : $qtdeProduto;
        $rowProduto  = $objProduto->get($produto);

        if (empty($rowProduto)) {

            echo '<div class="alert alert-warning"><h4>'.traduz("produto.nao.encontrado").'.</h4></div>';
            echo '<a href="loja_new.php" class="btn"><i class="fa fa-shopping-cart"></i> '.traduz("continuar.comprando").'</a>';
            exit;
        } else {
             $redireciona = false;
            $dataSave = array("loja_b2b_peca" => $produto, "qtde" => $qtdeProduto, "valor_unitario" => $rowProduto["preco_venda"]);
            
            //verifica se ja tem um carrinho em aberto
            $dados = $objCarrinhoCompra->verificaCarrinhoAberto($dadosCliente["loja_b2b_cliente"]);

            if (!empty($dados)) {

                $dataSave["loja_b2b_carrinho"] = $dados["loja_b2b_carrinho"];

                $retornoItem = $objCarrinhoCompra->addItemCarrinho($dataSave);
                $msg_sucesso = "";
                $msg_erro = "";
                if ($retornoItem["sucesso"]) {
                    $msg_sucesso = $retornoItem["msn"];

                } else {
                    $msg_erro = $retornoItem["msn"];
                }
                $redireciona = true;
            } else {

                $dataSaveCarrinho["posto"] = $login_posto;

                $retornoCarrinho = $objCarrinhoCompra->abreCarrinho($dataSaveCarrinho);

                if ($retornoCarrinho["sucesso"]) {
                    $dataSave["loja_b2b_carrinho"] = $retornoCarrinho["loja_b2b_carrinho"];
                    $retornoItem = $objCarrinhoCompra->addItemCarrinho($dataSave);
                    $redireciona = true;
                }

            }
        }
    }
    $dadosCarrinho = $objCarrinhoCompra->getAllCarrinho($dadosCliente["loja_b2b_cliente"]);
?>

<div class="navbar">
    <div class="navbar-inner eco_carrinho_titulo">
        <h4><i class="fa fa-shopping-cart"></i> <?php echo strtoupper(traduz("carrinho.de.compras"));?>
</h4>
    </div>
</div>
<div class="mensagens">
<?php
    if (!empty($msg_sucesso)) {
        echo '<div class="alert alert-success alert-carrinho"><h5>'.$msg_sucesso.'</h5></div>';
    }
    if (!empty($msg_erro)) {
        echo '<div class="alert alert-danger alert-carrinho"><h5>'.$msg_erro.'</h5></div>';
    }
    if (empty($dadosCarrinho["itens"])) {
        echo '<div class="alert alert-important alert-carrinho"><h5>'.traduz("nenhum.produto.adicionado.no.carrinho").'.</h5></div>';
        exit;
    }
    if ($redireciona) {
        echo "<meta http-equiv=refresh content=\"0;URL=loja_new.php?pg=carrinho\">";
    }
?>
</div>
<form action="" id="form_finaliza_compra" method="post">
    <input type="hidden" name="posto" id="posto" value="<?php echo $login_posto;?>">
    <input type="hidden" name="sendHarsh" id="sendHarsh">
    <input type="hidden" name="cardHashs" id="cardHashs">
    <input type="hidden" name="tipo_pagamento_nome">
    <input type="hidden" name="tipo_pagamento_valor">
    <input type="hidden" id="bandeira" name="bandeira">
    <input type="hidden" id="integrador" name="integrador">
    <?php if (isset($configLojaFrete["meio"])) {?>
    <input type="hidden" class="tipoEnvio" name="tipoEnvio">
    <input type="hidden" class="usaEnvio" name="usaEnvio" value="true">
    <?php }?>

    <table class="table table-bordered table-striped table-hover">
        <thead>
            <tr class="eco_carrinho_titulo_tr">
                <th class="eco_text_align_left"><?php echo strtoupper(traduz("produto"));?></th>
                <th width="5%"><?php echo strtoupper(traduz("quantidade"));?></th>
                <th width="13%"><?php echo strtoupper(traduz("valor.unitário"));?></th>
                <th width="5%"><?php echo strtoupper(traduz("subtotal"));?></th>
                <th width="5%"><?php echo strtoupper(traduz("acoes"));?></th>
            </tr>    
        </thead>  
        <tbody>
            <?php 
                foreach ($dadosCarrinho["itens"] as $kCart => $vCart) {
                    $totalPedido[] = ($vCart["qtde"]*$vCart["valor_unitario"]);
                    echo "<input type='hidden' name='produtos[{$kCart}][item]' value='".$vCart["loja_b2b_carrinho_item"]."'>";
                    echo "<input type='hidden' name='produtos[{$kCart}][nome]' value='".$vCart["produto"]["nome_peca"]."'>";
                    echo "<input type='hidden' name='produtos[{$kCart}][qtde]' value='".$vCart["qtde"]."'>";
                    echo "<input type='hidden' name='produtos[{$kCart}][valor]' value='".$vCart["valor_unitario"]."'>";
            ?>
            <tr id="eco_item_carrinho_<?php echo $vCart["loja_b2b_carrinho_item"];?>">
                <td class="eco_vertical_align_middle">
                    <div class="thumbnail span2">
                        <img style="height:65px !important" src="<?php echo $vCart["produto"]["fotos"][0];?>">
                    </div>
                    <div class="eco_carrinho_nome_produto span10">
                        <p style="margin-top: 3%"><?php echo $vCart["produto"]["ref_peca"];?> - <?php echo $vCart["produto"]["nome_peca"];?></p>
                    </div>
                </td>
                <td class="eco_text_align_center eco_vertical_align_middle">
                    <input type="text" price="true" class="eco_carrinho_input_qtd qtde_carrinho_<?php echo $vCart["loja_b2b_carrinho_item"];?>" name="qtde" value="<?php echo $vCart["qtde"];?>">
                    <button type="button" data-id="<?php echo $vCart["loja_b2b_carrinho_item"];?>" class="btn btn-link btn-atualiza-item-carrinho"><i class="fa fa-sync-alt"></i> <?php echo traduz("atualizar");?></button>
                </td>
                <td class="eco_text_align_center eco_vertical_align_middle">R$ <?php echo number_format($vCart["valor_unitario"], 2, ',', '.');?></td>
                <td class="eco_text_align_center eco_vertical_align_middle">R$ <?php echo number_format(($vCart["qtde"]*$vCart["valor_unitario"]), 2, ',', '.');?></td>
                <td class="eco_text_align_center eco_vertical_align_middle">
                    <button data-id="<?php echo $vCart["loja_b2b_carrinho_item"];?>" type="button" class="btn btn-danger btn-mini btn-remove-item-carrinho"><i class="fa fa-trash-alt"></i></a>
                </td>
            </tr>    
            <?php }?>
        </tbody>  
        </tfoot> 
            <tr>
                <td colspan="2" class="eco_text_align_right"><?php echo strtoupper(traduz("subtotal"));?></td>
                <td colspan="3" class="eco_carrinho_preco_subtotal">
                    <input type="hidden" name="carrinhosubtotal" id="carrinhosubtotal" value="<?php echo array_sum($totalPedido);?>" data-totalreal="<?php echo array_sum($totalPedido);?>">
                    R$ <?php echo number_format(array_sum($totalPedido), 2, ',', '.');?>
                    
                </td>
            </tr>
            <?php if (isset($configLojaFrete["frete_gratis"]) && $configLojaFrete["frete_gratis"]) {?>
            <tr>
                <td colspan="2" class="eco_text_align_right"><?php echo strtoupper(traduz("frete"));?></td>
                <td colspan="3" class="eco_carrinho_preco_subtotal">
                    <span class="label label-success"><?php echo traduz("frete.grátis");?></span>
                </td>
            </tr>
            <?php }?>
            <?php if (isset($configLojaFrete["meio"])) {?>
            <tr>
                <td colspan="2" valign="middle">
                    <div class="row">
                        <div class="span12 eco_text_align_right">
                            <b class="eco_margin_top5">Valor do frete calculado para o endereço:</b>
                            <?php
                                $endereco_posto = $loja_posto_endereco.", ".$loja_posto_numero." - ".$loja_posto_complemento . " - ".$loja_posto_bairro;
                                $endereco_posto .= " - CEP: ".$loja_posto_cep." - ".$loja_posto_cidade."/".$loja_posto_estado;
                            ?>
                            <p><?php echo $endereco_posto;?></p>
                        </div>
                    </div>
                </td>
                <td colspan="3" class="eco_text_align_left">
                    <ul class="eco_carrinho_formas_envio" id="eco_carrinho_formas_envio">
                        
                    </ul>
                </td>
            </tr>
            <?php }?>
            <tr>
                <td colspan="2" class="eco_text_align_right"><?php echo strtoupper(traduz("total.do.pedido"));?></td>
                <td colspan="3" id="total_pedido_frete" class="eco_carrinho_preco_total">R$ <?php echo number_format((array_sum($totalPedido)), 2, ',', '.');?></td>
            </tr>    
        </tfoot>  
    </table>   
    <?php if (array_sum($totalPedido)  >= $objLoja->_pedidominimo) {?>
    <div class="row-fluid">
        <div class="span5">
            <legend><i class="fa fa-list"></i> <?php echo traduz("dados.pessoais");?></legend>
            <p>Olá, <b><?php echo $login_codigo_posto;?> - <?php echo $login_nome;?></b>, CNPJ: <?php echo $login_cnpj;?></p>
            <hr>
            <legend style="margin-bottom: 0px;padding-bottom: 7px;"><i class="fa fa-map-marker-alt"></i> <?php echo traduz("endereço.de.entrega");?></legend><br />
            <div class="row-fluid">
                <div class="span12">
                    <?php echo $loja_posto_endereco;?>, 
                    Nº <?php echo $loja_posto_numero;?>  - 
                    <?php echo $loja_posto_complemento;?>  - 
                    <?php echo $loja_posto_bairro;?><br />
                    <?php echo "CEP: ".$loja_posto_cep;?> - 
                    <?php echo $loja_posto_cidade;?> / 
                    <?php echo $loja_posto_estado;?>
                </div>
            </div>
        </div>
        <div class="span7">
            <legend><i class="fa fa-money-bill-alt"></i> <?php echo traduz("formas.de.pagamentos");?></legend>
            <div id="box-pagamento">
                <p><?php echo traduz("selecione.uma.forma.de.pagamento");?></p>
                <?php 
                    if (isset($configLojaPagamento["meio"]["pagseguro"]) && $configLojaPagamento["meio"]["pagseguro"]["status"] == "1") {
                        if ($configLojaPagamento["meio"]["pagseguro"]["cartao"] == "1") {
                            include_once("loja/includes/carrinho/compagamento/checkout/pagseguro/cartao.php");
                        }
                        if ($configLojaPagamento["meio"]["pagseguro"]["boleto"] == "1") {
                            include_once("loja/includes/carrinho/compagamento/checkout/pagseguro/boleto.php");
                        }
                    }

                    if (isset($configLojaPagamento["meio"]["cielo"]) && $configLojaPagamento["meio"]["cielo"]["status"] == "1") {
                        if ($configLojaPagamento["meio"]["cielo"]["cartao"] == "1") {
                            include_once("loja/includes/carrinho/compagamento/checkout/cielo/cartao.php");
                        }
                        if ($configLojaPagamento["meio"]["cielo"]["boleto"] == "1") {
                            include_once("loja/includes/carrinho/compagamento/checkout/cielo/boleto.php");
                        }
                    }

                    if (isset($configLojaPagamento["meio"]["maxipago"]) && $configLojaPagamento["meio"]["maxipago"]["status"] == "1") {
                        if ($configLojaPagamento["meio"]["maxipago"]["cartao"] == "1") {
                            include_once("loja/includes/carrinho/compagamento/checkout/maxipago/cartao.php");
                        }
                        if ($configLojaPagamento["meio"]["maxipago"]["boleto"] == "1") {
                            include_once("loja/includes/carrinho/compagamento/checkout/maxipago/boleto.php");
                        }
                    }
                ?>
            </div>
        </div>
    </div>
    <hr>
    <?php 
        } else {
            echo '<div class="row-fluid"><div class="span12"><div style="font-size:18px" class="alert alert-danger">'.traduz("pedido.mínimo").': <b>R$ ' . number_format($objLoja->_pedidominimo, 2, ',', '.') . '</b></div></div></div>';
        }
    ?>
    <div class="navbar" style="margin-bottom: 40px;margin-top: 50px">
        <div class="eco_carrinho_barra_bottom tac">
        <?php if (array_sum($totalPedido)  >= $objLoja->_pedidominimo) {?>
            <button type="button" disabled="disabled" class="btn btn-success btn-finaliza-compra btn-large ">
                <i class="icon-ok icon-white"></i> <?php echo traduz("fechar.pedidos");?>
            </button>
        <?php } ?>
        </div>
    </div>
</form>

