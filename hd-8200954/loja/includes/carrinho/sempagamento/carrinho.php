<?php 

    if ($_REQUEST['codigo_produto']) {
        $kit         = trim($_REQUEST['kit']);
        $produto     = trim($_REQUEST['codigo_produto']);
        $qtdeProduto = trim($_REQUEST['qtde_produto']);
        $qtdeProduto = empty($qtdeProduto) ? 1 : $qtdeProduto;




        if (isset($kit) && $kit == true) {
            $rowProduto  = $objProduto->getKit($produto);
            $totalProduto = array();
            foreach ($rowProduto["itens_kit"] as $k => $rows) {
                $totalProduto[] = $rows["preco_venda"];
            }
        } else {
            $rowProduto  = $objProduto->get($produto);
        }

        if (empty($rowProduto)) {

            echo '<div class="alert alert-warning"><h4>'.traduz("produto.nao.encontrado").'.</h4></div>';
            echo '<a href="loja_new.php" class="btn"><i class="fa fa-shopping-cart"></i> '.traduz("continuar.comprando").'</a>';
            exit;

        } else {

            if (isset($kit) && $kit == true) {

                //verifica se ja tem um carrinho em aberto
                $dados = $objCarrinhoCompra->verificaCarrinhoAberto($dadosCliente["loja_b2b_cliente"]);
                $redireciona = false;

                if (!empty($dados)) {

                    $dataSave  = array();
                    $retornoItem  = array();

                    foreach ($rowProduto["itens_kit"] as $key => $rows) {

                        $dataSave["loja_b2b_kit_peca"] = $rows["loja_b2b_kit_peca"];
                        $dataSave["loja_b2b_peca"]     = $rows["loja_b2b_peca"];
                        $dataSave["qtde"]              = $rows["qtde"];
                        $dataSave["valor_unitario"]    = $rows["preco_venda"];
                        $dataSave["loja_b2b_carrinho"] = $dados["loja_b2b_carrinho"];
                        if ($moduloB2BGrade) {
                            $tamanho     = trim($_REQUEST['item_grade_'.$produto]);

                            $dadosTamanho  = $objProduto->getGradeByProduto($produto,$tamanho);
                            $dataSave["loja_b2b_peca_grade"] = $dadosTamanho;

                        }

                        $retornoItem = $objCarrinhoCompra->addItemCarrinho($dataSave);
                        $msg_sucesso = "";
                        $msg_erro = "";
                        if ($retornoItem["sucesso"]) {
                            $msg_sucesso = $retornoItem["msn"];

                        } else {
                            $msg_erro = $retornoItem["msn"];
                        }
                    }
                    $redireciona = true;

                } else {
                    $msg_sucesso = "";
                    $msg_erro = "";
                    $dataSave  = array();
                    $retornoItem  = array();
                    $dataSaveCarrinho["posto"] = $login_posto;

                    $retornoCarrinho = $objCarrinhoCompra->abreCarrinho($dataSaveCarrinho);

                    if ($retornoCarrinho["sucesso"]) {
                        foreach ($rowProduto["itens_kit"] as $key => $rows) {
                            $dataSave["loja_b2b_kit_peca"] = $rows["loja_b2b_kit_peca"];
                            $dataSave["loja_b2b_peca"]     = $rows["loja_b2b_peca"];
                            $dataSave["qtde"]              = $rows["qtde"];
                            $dataSave["valor_unitario"]    = $rows["preco_venda"];
                            $dataSave["loja_b2b_carrinho"] = $retornoCarrinho["loja_b2b_carrinho"];

                            if ($moduloB2BGrade) {
                                $tamanho     = trim($_REQUEST['item_grade_'.$produto]);

                                $dadosTamanho  = $objProduto->getGradeByProduto($produto,$tamanho);

                                $dataSave["loja_b2b_peca_grade"] = $dadosTamanho;

                            }
                            $retornoItem = $objCarrinhoCompra->addItemCarrinho($dataSave);
                        }
                        $redireciona = true;

                    }
                }

            } else {
                $redireciona = false;

                $dataSave = array("loja_b2b_peca" => $produto, "qtde" => $qtdeProduto, "valor_unitario" => $rowProduto["preco_venda"]);
                //verifica se ja tem um carrinho em aberto
                $dados = $objCarrinhoCompra->verificaCarrinhoAberto($dadosCliente["loja_b2b_cliente"]);
                if (!empty($dados)) {

                    $dataSave["loja_b2b_carrinho"] = $dados["loja_b2b_carrinho"];

                    if ($login_fabrica == 42) {
                        $retornoFornecedor = $objCarrinhoCompra->verificaMesmoFornecedorCarrinho($rowProduto['loja_b2b_fornecedor'],$dados["loja_b2b_carrinho"]);

                        if ($retornoFornecedor["erro"]) {
                            echo '<div class="alert alert-warning"><h4>'.traduz("Todos os itens do carrinho precisam ser do mesmo fornecedor").'.</h4></div>';
                            echo '<a href="loja_new.php" class="btn"><i class="fa fa-shopping-cart"></i> '.traduz("continuar.comprando").'</a>';
                            exit;
                        }
                    }
                    if ($moduloB2BGrade) {
                        $tamanho     = trim($_REQUEST['item_grade_'.$produto]);

                        $dadosTamanho  = $objProduto->getGradeByProduto($produto,$tamanho);

                        $dataSave["loja_b2b_peca_grade"] = $dadosTamanho;

                    }
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
                    $msg_sucesso = "";
                    $msg_erro = "";

                    $dataSaveCarrinho["posto"] = $login_posto;

                    $retornoCarrinho = $objCarrinhoCompra->abreCarrinho($dataSaveCarrinho);

                    if ($retornoCarrinho["sucesso"]) {
                        $dataSave["loja_b2b_carrinho"] = $retornoCarrinho["loja_b2b_carrinho"];
                        if ($moduloB2BGrade) {
                            $tamanho     = trim($_REQUEST['item_grade_'.$produto]);

                            $dadosTamanho  = $objProduto->getGradeByProduto($produto,$tamanho);

                            $dataSave["loja_b2b_peca_grade"] = $dadosTamanho["loja_b2b_peca_grade"];

                        }
                        $retornoItem = $objCarrinhoCompra->addItemCarrinho($dataSave);
                    }
                    $redireciona = true;

                }
            }
        }
    }
    //Busca produto no carrinho de compras
    $dadosCarrinho = $objCarrinhoCompra->getAllCarrinho($dadosCliente["loja_b2b_cliente"]);

    $kit_peca = array();
    foreach ($dadosCarrinho["itens"] as $kCart => $vCart) {
        if (strlen($vCart["loja_b2b_kit_peca"]) > 0) {
            $kit_peca[$vCart["loja_b2b_kit_peca"]][] = $vCart;
            continue;
        }
    }

?>

<div class="navbar">
    <div class="navbar-inner eco_carrinho_titulo">
        <h4><i class="fa fa-shopping-cart"></i> <?php echo strtoupper(traduz("carrinho.de.compras"));?></h4>
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
<form action="?pg=finalizar-pedido" method="post">
<table class="table table-bordered table-striped table-hover">
    <thead>
        <tr class="eco_carrinho_titulo_tr">
            <th class="eco_text_align_left"><?php echo strtoupper(traduz("produto"));?></th>
            <?php
            if ($login_fabrica == 42) { ?>
                <th width="5%"><?php echo strtoupper(traduz("fornecedor"));?></th>
            <?php 
            } ?>
            <th width="5%"><?php echo strtoupper(traduz("quantidade"));?></th>
            <th width="13%"><?php echo strtoupper(traduz("valor.unitário"));?></th>
            <th width="5%"><?php echo strtoupper(traduz("subtotal"));?></th>
            <th width="5%"><?php echo strtoupper(traduz("acoes"));?></th>
        </tr>    
    </thead>  
    <tbody>
        <?php 
            foreach ($dadosCarrinho["itens"] as $kCart => $vCart) {
                if ($kCart == 0) {
                    $id_fornecedor = $vCart["produto"]["loja_b2b_fornecedor"];
                }
                if (strlen($vCart["loja_b2b_kit_peca"]) > 0) {
                    continue;
                }
                $totalPedido[] = ($vCart["qtde"]*$vCart["valor_unitario"]);
                

        ?>
        <tr id="eco_item_carrinho_<?php echo $vCart["loja_b2b_carrinho_item"];?>">
            <td class="eco_vertical_align_middle">
                <div class="thumbnail span2">
                    <img style="height:65px !important" src="<?php echo $vCart["produto"]["fotos"][0];?>">
                </div>
                <div class="eco_carrinho_nome_produto span10">
                    <p style="margin-top: 3%">
                        <?php echo $vCart["produto"]["ref_peca"];?> - <?php echo $vCart["produto"]["nome_peca"];?>
                            
                        </p>
                    <?php 
                    if (isset($vCart["tamanho"]) && strlen($vCart["tamanho"]) > 0) {
                        echo "<br><em>Tamanho selecionado: <b>".$vCart["tamanho"]."</b></em>";
                    }
                    ?>
                </div>
            </td>
            <?php
            if ($login_fabrica == 42) { ?>
                <td class="eco_text_align_center eco_vertical_align_middle">
                    <p style="margin-top: 3%"><?php echo $vCart["produto"]["nome_fornecedor"];?></p>
                </td>
            <?php 
            } ?>
            <td class="eco_text_align_center eco_vertical_align_middle">
                <input type="text" price="true" class="eco_carrinho_input_qtd qtde_carrinho_<?php echo $vCart["loja_b2b_carrinho_item"];?>" name="qtde" value="<?php echo $vCart["qtde"];?>">
                <button type="button" data-id="<?php echo $vCart["loja_b2b_carrinho_item"];?>" class="btn btn-link btn-atualiza-item-carrinho"><i class="fa fa-refresh"></i> <?php echo traduz("atualizar");?></button>
            </td>
            <td class="eco_text_align_center eco_vertical_align_middle">R$ <?php echo number_format($vCart["valor_unitario"], 2, ',', '.');?></td>
            <td class="eco_text_align_center eco_vertical_align_middle">R$ <?php echo number_format(($vCart["qtde"]*$vCart["valor_unitario"]), 2, ',', '.');?></td>
            <td class="eco_text_align_center eco_vertical_align_middle">
                <button data-id="<?php echo $vCart["loja_b2b_carrinho_item"];?>" type="button" class="btn btn-danger btn-mini btn-remove-item-carrinho"><i class="icon-remove icon-white"></i></a>
            </td>
        </tr>    
        <?php }?>
        <?php 
        if (count($kit_peca) > 0) {
            $qtde_kit = 0;
            foreach ($kit_peca as $loja_b2b_kit_peca => $itensKit) {
              
                $dadosKit = $objProduto->getKit($loja_b2b_kit_peca);
                $totalPecasKit = array();
                $totalItensPecaCarrinho = array();
                foreach ($dadosKit["itens_kit"] as $key => $value) {
                    $totalPecasKit[] = $value["qtde"];
                }
                foreach ($itensKit as $key => $value) {
                    $totalItensPecaCarrinho[] = $value["qtde"];
                }
                $qtde_kit = array_sum($totalItensPecaCarrinho)/array_sum($totalPecasKit);
        ?>
        <tr id="eco_item_carrinho_tr_kit_<?php echo $loja_b2b_kit_peca;?>">
            <td class="eco_vertical_align_middle">
                <div class="thumbnail span2">
                    <img style="height:65px !important" src="<?php echo $dadosKit["fotos"][0];?>">
                </div>
                <div class="eco_carrinho_nome_produto span10">
                    <p style="margin-top: 3%"><?php echo $dadosKit["ref_peca"];?> 
- <?php echo $dadosKit["nome_peca"];?></p>
                </div>
            </td>
            <td class="eco_text_align_center eco_vertical_align_middle">
                 <input type="text" price="true" class="eco_carrinho_input_qtd qtde_carrinho_<?php echo $loja_b2b_kit_peca;?>" name="qtde" value="<?php echo $qtde_kit;?>">
                <button type="button" data-idcarrinho="<?php echo $dadosCarrinho["loja_b2b_carrinho"];?>" data-kitpeca="<?php echo $loja_b2b_kit_peca;?>" data-id="<?php echo $loja_b2b_kit_peca;?>" class="btn btn-link btn-atualiza-item-carrinho"><i class="fa fa-refresh"></i> Atualizar</button>
            </td>
            <td class="eco_text_align_center eco_vertical_align_middle">R$ <?php echo number_format($dadosKit["total_itens_kit"], 2, ',', '.');?></td>
            <td class="eco_text_align_center eco_vertical_align_middle">R$ <?php echo number_format($dadosKit["total_itens_kit"]*$qtde_kit, 2, ',', '.');?></td>
            <td class="eco_text_align_center eco_vertical_align_middle">
                <button data-kit="true" data-id="<?php echo $loja_b2b_kit_peca;?>" type="button" class="btn btn-danger btn-mini btn-remove-item-carrinho"><i class="icon-remove icon-white"></i></a>
            </td>
        </tr>  
        <tr class="eco_item_carrinho_tr_kit_<?php echo $loja_b2b_kit_peca;?>">
            <td colspan="5">

                    <table class="table table-bordered">
                        <tr>
                            <td  style="background: #1381ce !important;color: #ffffff;" colspan="2">ITENS DO KIT</td>
                            <td class="tac" style="background: #1381ce !important;color: #ffffff;">QUANTIDADE</td>
                            <td class="tac" style="background: #1381ce !important;color: #ffffff;">VALOR UNIT.</td>
                            <td class="tac" style="background: #1381ce !important;color: #ffffff;">SUBTOTAL</td>
                        </tr>
                        <?php                 
                            foreach ($itensKit as $k => $vCart) {
                                $totalPedido[] = ($vCart["qtde"]*$vCart["valor_unitario"]);
                        ?>
                        <tr>
                            <td class="eco_vertical_align_middle"  colspan="2">
                                <div class="thumbnail span2">
                                    <img style="height:65px !important" src="<?php echo $vCart["produto"]["fotos"][0];?>">
                                </div>
                                <div class="eco_carrinho_nome_produto span10">
                                    <p style="margin-top: 3%"><?php echo $vCart["produto"]["nome_peca"];?></p>
                                </div>
                            </td>
                            <td class="eco_text_align_center  eco_vertical_align_middle">
                                <?php echo $vCart["qtde"];?>
                                <input type="hidden" price="true" class="eco_carrinho_input_qtd qtde_carrinho_<?php echo $vCart["loja_b2b_carrinho_item"];?>" name="qtde" value="<?php echo $vCart["qtde"];?>">
                            </td>
                            <td class="eco_text_align_center eco_vertical_align_middle">R$ <?php echo number_format($vCart["valor_unitario"], 2, ',', '.');?></td>
                            <td class="eco_text_align_center eco_vertical_align_middle">R$ <?php echo number_format(($vCart["qtde"]*$vCart["valor_unitario"]), 2, ',', '.');?></td>
                        </tr> 
                        <?php }?> 
                    </table>

            </td> 
        </tr>
        <?php }}?>

    </tbody>  
    </tfoot> 
        <?php
            $colspan = ($login_fabrica == 42) ? '3' : '2';
        ?>
        <tr>
            <td colspan="<?= $colspan ?>" class="eco_text_align_right"><?php echo strtoupper(traduz("subtotal"));?></td>
            <td colspan="3" class="eco_carrinho_preco_subtotal">
                <input type="hidden" name="carrinhosubtotal" id="carrinhosubtotal" value="<?php echo array_sum($totalPedido);?>" data-totalreal="<?php echo array_sum($totalPedido);?>">
                R$ <?php echo number_format(array_sum($totalPedido), 2, ',', '.');?>
                
            </td>
        </tr>
        <input type="hidden" name="loja_b2b_carrinho" value="<?= $dadosCarrinho['loja_b2b_carrinho'] ?>" />
        <input type="hidden" name="forma_envio"       value="<?= $dadosCarrinho['forma_envio'] ?>" />
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
                <td colspan="4" class="eco_text_align_left">
                    <ul class="eco_carrinho_formas_envio" id="eco_carrinho_formas_envio">
                        
                    </ul>
                </td>
            </tr>
        <?php }?>
        <tr>
            <td colspan="<?= $colspan ?>" class="eco_text_align_right"><?php echo strtoupper(traduz("total.do.pedido"));?></td>
            <td colspan="3" id="total_pedido_frete" class="eco_carrinho_preco_total">R$ <?php echo number_format((array_sum($totalPedido)), 2, ',', '.');?></td>
        </tr>    
    </tfoot>  
</table>   


<div class="navbar">
    <div class="eco_carrinho_barra_bottom">
        <a href="loja_new.php" class="btn"><i class="fa fa-shopping-cart"></i> <?php echo traduz("continuar.comprando");?></a>
        <?php if (array_sum($totalPedido)  >= $objLoja->_pedidominimo) {?>
        <button type="submit" class="btn btn-success btn-large pull-right" id="btn_fecha_pedido">
            <i class="icon-ok icon-white"></i> <?php echo traduz("fechar.pedidos");?>
        </button>
        <?php 
        } else {
            echo '<span class="alert alert-danger pull-right">'.traduz("pedido.mínimo").': <b>R$ ' . number_format($objLoja->_pedidominimo, 2, ',', '.') . '</b></span>';
        }
        ?>

    </div>
</div>

</form>
