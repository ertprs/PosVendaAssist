<?php 
if (isset($_REQUEST["result"])) {

    if (isset($_REQUEST["tipo"]) && $_REQUEST["tipo"] == "PS") {
        $dadosRetorno = base64_decode($_REQUEST["result"]);
        list($pedido, $bandeira, $tipo_pagamento_escolhido, $link_boleto, $status_boleto) = explode("|", $dadosRetorno);
        $dadosRetorno = [
            "pedido" => $pedido, 
            "bandeira" => $bandeira, 
            "tipo_pagamento_escolhido" => $tipo_pagamento_escolhido, 
            "link_boleto" => $link_boleto, 
            "status_boleto" => $status_boleto
        ];
    } else {

        $dadosRetorno = base64_decode($_REQUEST["result"]);
        $dadosRetorno = current(json_decode($dadosRetorno, 1));
        if (empty($dadosRetorno)) {

        }
        $pedido = $dadosRetorno["pedido"];
    }
}

if (isset($_REQUEST["pedido"])) {
    $pedido = $_REQUEST["pedido"];
}

$tipo_pagamento_escolhido = $dadosRetorno["tipo_pagamento_escolhido"];

if ($tipo_pagamento_escolhido == "BOLETO") {
    $link_boleto   = $dadosRetorno["link_boleto"];
    $status_boleto = $dadosRetorno["status_boleto"];
}

if ($tipo_pagamento_escolhido == "CREDIT_CARD") {
    $bandeira      = $dadosRetorno["bandeira"];
    $status_cartao = $dadosRetorno["status_cartao"];
}


if ($login_fabrica == 42) {

    $dadosCliente = $objLojaCliente->get(null, $login_posto);

    $dadosPedido = $objCarrinhoCompra->getAllCarrinho($dadosCliente['loja_b2b_cliente'],'',false,$pedido);
    $dadosPedido['pedido'] = $pedido;
    $dadosPedido["valor_frete"] = $dadosPedido['total_frete'];

} else {

    $dadosPedido = $objCarrinhoCompra->getPedidoB2B($pedido);

}

if (empty($dadosPedido)) {
    echo '<div class="alert alert-important alert-carrinho"><h5>'.traduz("nenhum.pedido.foi.encontrado").'.</h5></div>';
    exit;
}
?>
<div class="navbar">
    <div class="navbar-inner eco_carrinho_titulo_sucesso">
        <h4><i class="fa fa-check"></i> <?php echo strtoupper(traduz("pedido.realizado.com.sucesso"));?></h4>
    </div>
</div>
<?php
if ($login_fabrica == 42) { 
    $dadosFornecedor = $objCarrinhoCompra->dadosFornecedorCarrinho($dadosPedido["pedido"]);
?>
<div class="row-fluid">
    <div class="span12">
        <span style="font-weight: normal;">
            <h4>Dados Fornecedor</h4>
            <strong>Fornecedor: </strong> 
            <?= $dadosPedido['itens'][0]['produto']['nome_fornecedor'] ?>
            &nbsp;&nbsp;&nbsp;&nbsp;<strong>Celular: </strong><?= (empty($dadosFornecedor['cel_fornecedor'])) ? ' Não possui' : $dadosFornecedor['cel_fornecedor'] ?><br />
            <strong>Fone:</strong> <?= (empty($dadosFornecedor['fone_fornecedor'])) ? ' Não possui' : $dadosFornecedor['fone_fornecedor'] ?>
            &nbsp;&nbsp;&nbsp;&nbsp;<strong>E-mail:</strong> <?= (empty($dadosFornecedor['email_fornecedor'])) ? ' Não possui' : $dadosFornecedor['email_fornecedor'] ?>
        </span>
        <br /><br />
    </div>
</div>
<?php
}
?>
<div class="row-fluid">
    <div class="span8">
        <h4><?php echo traduz("resumo.do.pedido");?></h4>
        <table class="table table-striped">
            <tbody>
            <?php 
                if ($login_fabrica != 42) {
                    foreach ($dadosPedido["itens"] as $kCart => $vCart) {
                        $totalPedido[] = ($vCart["qtde"]*$vCart["preco"]);
                        $dadosProduto  =  $objProduto->get(null, $vCart["peca"]);
                        $tm = "";
                        if (isset($vCart["tamanho"]) && strlen($vCart["tamanho"]) > 0) {
                            $tm = "<br><em>Tamanho selecionado: <b>".$vCart["tamanho"]."</b></em>";
                        }

            ?>
            <tr id="eco_item_carrinho_<?php echo $vCart["loja_b2b_carrinho_item"];?>">
                <td class="eco_vertical_align_middle">
                    <div class="thumbnail span2">
                        <img style="height:45px !important" src="<?php echo $dadosProduto[0]["fotos"][0];?>">
                    </div>
                    <div class="eco_carrinho_nome_produto span10">
                        <p style="margin-top: 3%"><?php echo $dadosProduto[0]["ref_peca"];?> - <?php echo $dadosProduto[0]["nome_peca"] . $tm ;?></p>
                    </div>
                </td>
                <td class="eco_text_align_center eco_vertical_align_middle">
                    <?php echo $vCart["qtde"];?>
                </td>
                <td class="eco_text_align_center eco_vertical_align_middle">R$ <?php echo number_format($vCart["preco"], 2, ',', '.');?></td>
                <td class="eco_text_align_center eco_vertical_align_middle">R$ <?php echo number_format(($vCart["qtde"]*$vCart["preco"]), 2, ',', '.');?></td>
            </tr>    
            <?php }//fecha foreach
               

            } else { 
                 foreach ($dadosPedido["itens"] as $kCart => $vCart) {
                        $totalPedido[] = ($vCart["qtde"]*$vCart["valor_unitario"]);
                        //$dadosProduto  =  $objProduto->get(null, $vCart["peca"]);
                        $tm = "";
                        if (isset($vCart["tamanho"]) && strlen($vCart["tamanho"]) > 0) {
                            $tm = "<br><em>Tamanho selecionado: <b>".$vCart["tamanho"]."</b></em>";
                        }

                ?>
                        <tr id="eco_item_carrinho_<?php echo $vCart["loja_b2b_carrinho_item"];?>">
                            <td class="eco_vertical_align_middle">
                                <div class="thumbnail span2">
                                    <img style="height:45px !important" src="<?php echo $vCart["produto"]["fotos"][0];?>">
                                </div>
                                <div class="eco_carrinho_nome_produto span10">
                                    <p style="margin-top: 3%"><?php echo $vCart["produto"]["nome_peca"] . $tm;?></p>
                                </div>
                            </td>
                            <td class="eco_text_align_center eco_vertical_align_middle">
                                <?php echo $vCart["qtde"];?>
                            </td>
                            <td class="eco_text_align_center eco_vertical_align_middle">R$ <?php echo number_format($vCart["valor_unitario"], 2, ',', '.');?></td>
                            <td class="eco_text_align_center eco_vertical_align_middle">R$ <?php echo number_format(($vCart["qtde"]*$vCart["valor_unitario"]), 2, ',', '.');?></td>
                        </tr>    
                    <?php 
                    }//fecha foreac
                ?>

            <?php
            }
            ?>
            </tbody>  
            </tfoot> 
                <tr>
                    <td colspan="3" class="eco_text_align_right"><?php echo strtoupper(traduz("subtotal"));?></td>
                    <td class="eco_text_align_right"><b>R$ <?php echo number_format(array_sum($totalPedido), 2, ',', '.');?></b></td>
                </tr> 
               <?php if (isset($configLojaFrete["meio"])) {?>
                   <tr>
                       <td colspan="3" class="eco_text_align_right">FRETE</td>
                       <?php
                       if ($login_fabrica == 42) { 
                    ?>
                            <td class="eco_text_align_right"><b><?php echo $dadosPedido["forma_envio"];?> - R$ <?php echo $dadosPedido["valor_frete"];?></b></td>
                       <?php
                       } else { ?>
                            <td class="eco_text_align_right"><b><?php echo $dadosPedido["obs"];?> - R$ <?php echo $dadosPedido["valor_frete"];?></b></td>
                       <?php
                       }
                       ?>
                       
                   </tr>    
               <?php 
                }?>
                <tr>
                    <td colspan="3" class="eco_text_align_right"><?php echo strtoupper(traduz("total.do.pedido"));?></td>
                    <td class="eco_text_align_right"><b>R$ <?php echo number_format(number_format($dadosPedido["valor_frete"], 2)+array_sum($totalPedido), 2, ',', '.');?></b></td>
                </tr>
            </tfoot>  
        </table> 
        <?php
            if (!empty($mensagem_sucesso)) {
                echo '<div class="alert alert-success">
                        '.$mensagem_sucesso.'
                      </div>';
            }
            if (!empty($mensagem_erro)) {
                echo '<div class="alert alert-danger">
                        '.$mensagem_erro.'
                      </div>';
            }
        ?>

    </div>
    <div class="span4">
        <div class="well eco_text_align_center">
            <?php if ($objLoja->usacheckout == "N") {?>
            <p><b><?php echo strtoupper(traduz("pedido"));?> Nº</b></p>
            <h1 class="eco_carrinho_npedido"> <?php echo $dadosPedido["pedido"];?></h1>
            <hr>
                <?php
                if ($login_fabrica != 42) {
                ?>
                    <p><b><?php echo strtoupper(traduz("status.do.pedido"));?></b></p>
                    <p class="label label-important"><?php echo $dadosPedido["status"]["descricao"];?></p>
                    <hr>
                <?php
                } ?>
                <p><b><?php echo strtoupper(traduz("condicao.de.pagamento"));?></b></p>
                <p class="label label-info"><?php echo $dadosPedido["condicaopagamento"]["descricao"];?></p>
                <?php 
             }
                ?>

            <?php if ($objLoja->usacheckout == "S") {?>
            <p><b><?php echo strtoupper(traduz("pedido"));?> Nº</b></p>
            <h1 class="eco_carrinho_npedido"> <?php echo $dadosPedido["pedido"];?></h1>
            <hr>
            <p><b> <?php echo strtoupper(traduz("opção.de.pagamento.escolhida"));?></b></p>
            <?php if ($tipo_pagamento_escolhido == "BOLETO") {?>
                <b><?php echo strtoupper(traduz("boleto"));?></b><BR />
                <a href="<?php echo $link_boleto;?>" class="btn btn-warning btn-large" target="_blank" title="Imprimir Boleto"> <i class="fa fa-barcode"></i> Imprimir Boleto</a>
            <?php }?>
            <?php 
                if ($tipo_pagamento_escolhido == "CREDIT_CARD") {
                    echo '<b>'.strtoupper(traduz("cartão.de.crédito")).'</b><br/><br/>';
                    if (isset($configLojaPagamento["meio"]["pagseguro"]) && $configLojaPagamento["meio"]["pagseguro"]["status"] == 1  && $configLojaPagamento["meio"]["pagseguro"]["cartao"]) {
                        echo '<img src="https://stc.pagseguro.uol.com.br/public/img/payment-methods-flags/68x30/'.$bandeira.'.png" alt="">';
                    } elseif (isset($configLojaPagamento["meio"]["cielo"]) && $configLojaPagamento["meio"]["cielo"]["status"] == 1  && $configLojaPagamento["meio"]["cielo"]["cartao"]) {
                        echo '<img src="loja/layout/img/bandeiras/'.strtolower($bandeira).'.png" alt="">';

                        $dadosPagamento = json_decode($dadosPedido["dados_pagamento"]["response"],1);
                        echo "<p style='display:block;width:100%'>".$dadosPagamento["payment"]["installments"]."x R$ " . number_format(($dadosPedido["dados_pagamento"]["total_pedido"]/$dadosPagamento["payment"]["installments"]), 2, ',', '.') . " sem juros</p>";

                    }
                 }   
            ?>
            <hr>
            <p><b> <?php echo mb_strtoupper(traduz("status.pagamento"));?></b></p>
            <?php 
                if (isset($configLojaPagamento["meio"]["pagseguro"]) && $configLojaPagamento["meio"]["pagseguro"]["status"] == "1" && $configLojaPagamento["meio"]["pagseguro"]["cartao"]) {
                    $statusPagamento = $objCarrinhoCompra->getStatusPagamento($dadosPedido["pedido"]);
                    if (in_array($statusPagamento, array(1,2,7))) {
                        $classAlert = "important";
                    } elseif (in_array($statusPagamento, array(3,4))) {
                        $classAlert = "success";
                    } elseif (in_array($statusPagamento, array(5))) {
                        $classAlert = "warning";
                    } elseif (in_array($statusPagamento, array(6))) {
                        $classAlert = "info";
                    } else {
                        $classAlert = "warning";
                    }
                    echo '<br/><p class="label label-'.$classAlert.'" title="'.$status_pagseguro[$statusPagamento].'">'.$status_pagseguro[$statusPagamento].'</p>';
                    
                }
                if (isset($configLojaPagamento["meio"]["cielo"]) && $configLojaPagamento["meio"]["cielo"]["status"] == "1"  && $configLojaPagamento["meio"]["cielo"]["cartao"]) {

                    if (in_array($status_cartao, array('4','6'))) {
                        $classAlert = "success";
                    } else {
                        $classAlert = "important";
                    }
                    echo '<br/><p class="label label-'.$classAlert.'" title="'.$objCheckout->status_cielo_cartao_credito[$status_cartao].'">'.$objCheckout->status_cielo_cartao_credito[$status_cartao].'</p>';
                    
                }
                if (isset($configLojaPagamento["meio"]["maxipago"]) && $configLojaPagamento["meio"]["maxipago"]["status"] == "1") {

                    if ($tipo_pagamento_escolhido == "BOLETO") {

                        if (in_array($status_boleto, array('10','35','36'))) {
                            $classAlert = "success";
                        } else {
                            $classAlert = "important";
                        }
                        echo '<br/><p class="label label-'.$classAlert.'" title="'.$objCheckout->status_maxipago[$status_boleto].'">'.$objCheckout->status_maxipago[$status_boleto].'</p>';


                    }

                    if ($tipo_pagamento_escolhido == "CREDIT_CARD") {

                        if (in_array($status_cartao, array('4','6'))) {
                            $classAlert = "success";
                        } else {
                            $classAlert = "important";
                        }
                        echo '<br/><p class="label label-'.$classAlert.'" title="'.$objCheckout->status_maxipago[$status_cartao].'">'.$objCheckout->status_maxipago[$status_cartao].'</p>';
                    }                   
                }
            ?>
            <hr>
            <p><b><?php echo strtoupper(traduz("forma.de.envio.escolhida"));?></b></p>
            <?php 
                if (isset($configLojaFrete["meio"])) {

                	$dados_frete = current($dadosRetorno["dados_frete"]);

                	if (!empty($dados_frete)) {
                		echo '<p>
                		Tipo de Entrega: '.$dados_frete["servicoEnvio"].'<br />
                		Prazo de Entrega: '.$dados_frete["diasEnvio"].' dias úteis<br />

                		</p> ';
                	}


                } elseif (isset($configLojaFrete["frete_gratis"]) && $configLojaFrete["frete_gratis"]) {
                    echo '<span class="label label-success">'.traduz("frete.grátis").'</span>';
                }

            ?><br />

            <?php }?>
        </div>
    </div>
</div> 

<div class="navbar">
    <div class="eco_carrinho_barra_bottom eco_text_align_center" align="center">
        <a href="loja/print/imprimir_pedido.php?pedido=<?php echo $dadosPedido["pedido"];?>" target="_blank" class="btn btn-large">
            <i class="fa fa-print"></i>  <?php echo traduz("imprimir.pedido");?>
        </a>
    </div>
</div>


