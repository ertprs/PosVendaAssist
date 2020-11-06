<?php 
    $pedido = $_REQUEST["pedido"];

    if ($objLoja->usacheckout == "N" && strlen($pedido) > 0) {
        $dadosPedido = $objCarrinhoCompra->getPedidoB2B($pedido);
    }
    if ($objLoja->usacheckout == "S") {
		$dadosCarrinho = $objCarrinhoCompra->getAllCarrinho($dadosCliente["loja_b2b_cliente"]);
        if (empty($dadosCarrinho)) {
            echo '<div class="alert alert-important alert-carrinho"><h5>Nenhum produto adicionado no carrinho.</h5></div>';
            echo '<meta http-equiv="refresh" content="2;URL=loja_new.php?pg=carrinho" />';
            exit;
        }

        if (!isset($_POST["formaEnvio"])) {
            echo '<div class="alert alert-danger alert-carrinho"><h5>Selecione uma forma de Envio.</h5></div>';
            echo '<meta http-equiv="refresh" content="2;URL=loja_new.php?pg=carrinho" />';
            exit;
        }

        list($forma_envio, $dias_entrega, $valor_envio) = explode("|", $_POST["formaEnvio"]);
        //integracao com pagseguro
        include_once("loja/integracoes/pagseguro/public/Checkout/createPaymentRequestLightbox.php");
    }
?>
<div class="navbar">
    <div class="navbar-inner eco_carrinho_titulo_sucesso">
        <h4><i class="fa fa-check"></i> PEDIDO REALIZADO COM SUCESSO</h4>
    </div>
</div>

<div class="row-fluid">
    <div class="span8">
        <h4>Resumo do Pedido</h4>
        <table class="table table-striped">
            <tbody>
            <?php 
                if ($objLoja->usacheckout == "S") {

                } else {

                foreach ($dadosPedido["itens"] as $kCart => $vCart) {
                    $totalPedido[] = ($vCart["qtde"]*$vCart["preco"]);
                    $dadosProduto  =  $objProduto->get(null, $vCart["peca"]);

            ?>
            <tr id="eco_item_carrinho_<?php echo $vCart["loja_b2b_carrinho_item"];?>">
                <td class="eco_vertical_align_middle">
                    <div class="thumbnail span2">
                        <img style="height:45px !important" src="<?php echo $dadosProduto[0]["fotos"][0];?>">
                    </div>
                    <div class="eco_carrinho_nome_produto span10">
                        <p style="margin-top: 3%"><?php echo $dadosProduto[0]["nome_peca"];?></p>
                    </div>
                </td>
                <td class="eco_text_align_center eco_vertical_align_middle">
                    <?php echo $vCart["qtde"];?>
                </td>
                <td class="eco_text_align_center eco_vertical_align_middle">R$ <?php echo number_format($vCart["preco"], 2, ',', '.');?></td>
                <td class="eco_text_align_center eco_vertical_align_middle">R$ <?php echo number_format(($vCart["qtde"]*$vCart["preco"]), 2, ',', '.');?></td>
            </tr>    
            <?php }//fecha foreach
            }//fecha else usa checkout
            ?>
            </tbody>  
            </tfoot> 
                <tr>
                    <td colspan="3" class="eco_text_align_right">SUBTOTAL</td>
                    <td class="eco_text_align_right"><b>R$ <?php echo number_format(array_sum($totalPedido), 2, ',', '.');?></b></td>
                </tr> 
                <?php if ($objLoja->usacheckout == "S") {?>
                <tr>
                    <td colspan="3" class="eco_text_align_right">FRETE</td>
                    <td class="eco_text_align_right"><b>R$ <?php echo $valor_envio;?></b></td>
                </tr>    
                <?php }?>
                <tr>
                    <td colspan="3" class="eco_text_align_right">TOTAL DO PEDIDO</td>
                    <td class="eco_text_align_right"><b>R$ <?php echo number_format(number_format($valor_envio, 2)+array_sum($totalPedido), 2, ',', '.');?></b></td>
                </tr>
                <?php if (in_array($login_fabrica, array(15))) {?> 
                <tr>
                    <td colspan="4">
                        <div class="alert alert-danger"><b style="font-size: 16px;">* NO ATO DO FATURAMENTO SERÃO ACRESCIDOS IPI, ST E O FRETE </b></div>
                    </td>
                </tr>
                <?php }elseif (in_array($login_fabrica, array(91))) {?> 
                <tr>
                    <td colspan="4">
                        <div class="alert alert-danger"><b style="font-size: 16px;">* NO ATO DO FATURAMENTO SERÃO ACRESCIDOS IPI  E O FRETE </b></div>
                    </td>
                </tr>
                <?php }?>
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
            <p><b>PEDIDO Nº</b></p>
            <h1 class="eco_carrinho_npedido"> <?php echo $dadosPedido["pedido"];?></h1>
            <hr>
            <p><b> STATUS DO PEDIDO</b></p>
            <p class="label label-important"><?php echo $dadosPedido["status"]["descricao"];?></p>
            <hr>
            <p><b> CONDIÇÃO DE PAGAMENTO</b></p>
            <p class="label label-info"><?php echo $dadosPedido["condicaopagamento"]["descricao"];?></p>
            <?php }?>

            <?php if ($objLoja->usacheckout == "S") {?>
            <p><b>PEDIDO Nº</b></p>
            <h1 class="eco_carrinho_npedido"> <?php echo $dadosPedido["pedido"];?></h1>
            <hr>
            <p><b> OPÇÕES DE PAGAMENTO ESCOLHIDA</b></p>
            <img style="border: solid 2px #dddddd" src="https://stc.pagseguro.uol.com.br/public/img/banners/seguranca/seguranca_125x125.gif" alt="PagSeguro" title="Compre com pagSeguro e fique sossegado">
            <hr>
            <p><b>FORMA DE ENVIO ESCOLHIDA</b></p>
            <?php 

                if ($forma_envio == 'SEDEX') {
                    $tipo_frete = 'SEDEX - R$ ' . $valor_envio;
                    $img_frete  = 'sedex-correios-logo.png';
                } elseif ($forma_envio == 'PAC') {
                    $tipo_frete = 'PAC - R$ ' . $valor_envio;
                    $img_frete  = 'pac-correios-logo.png';
                }
                        
            ?>
            <b><?php echo $tipo_frete;?></b> <br />

            <img src="loja/layout/img/<?php echo $img_frete;?>" width="50%" /><br />
            <?php }?>
        </div>
    </div>
</div> 

<div class="navbar">
    <div class="eco_carrinho_barra_bottom eco_text_align_center" align="center">
	<a href="?pg=finalizar-pedido&pedido=<?=$pedido?>" class="btn btn-large">
            <i class="fa fa-print"></i> Imprimir Pedido
        </a>
    </div>
</div>


