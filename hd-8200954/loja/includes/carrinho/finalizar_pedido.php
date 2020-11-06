<?php 
    $pedido = $_REQUEST["pedido"];
    $dadosCarrinho = $objCarrinhoCompra->getAllCarrinho($dadosCliente['loja_b2b_cliente'],$pedido);
?>
<div class="navbar">
    <div class="navbar-inner eco_carrinho_titulo">
        <h4><i class="fa fa-check"></i> <?php echo strtoupper(traduz("finalizar.pedido"));?></h4>
    </div>
</div>
<?php
    if (empty($dadosCarrinho)) {
        echo '<div class="alert alert-important alert-carrinho"><h5>'.traduz("finalizar.pedido").'.</h5></div>';
        echo '<meta http-equiv="refresh" content="2;URL=loja_new.php?pg=carrinho" />';
        exit;
    }
    if ($objLoja->usacheckout == "S") {

        if (!isset($_POST["formaEnvio"])) {
            echo '<div class="alert alert-danger alert-carrinho"><h5>'.traduz("selecione.uma.forma.de.envio").'.</h5></div>';
            echo '<meta http-equiv="refresh" content="2;URL=loja_new.php?pg=carrinho" />';
            exit;
        }
        list($forma_envio, $dias_entrega, $valor_envio) = explode("|", $_POST["formaEnvio"]);
    }
?>
<table class="table table-bordered table-striped table-hover">
    <thead>
        <tr class="eco_carrinho_titulo_tr">
            <th class="eco_text_align_left"><?php echo strtoupper(traduz("produto"));?></th>
             <th width="5%"><?php echo strtoupper(traduz("quantidade"));?></th>
            <th width="13%"><?php echo strtoupper(traduz("valor.unitário"));?></th>
            <th width="5%"><?php echo strtoupper(traduz("subtotal"));?></th>
        </tr>    
    </thead>  
    <tbody>
        <?php 
            foreach ($dadosCarrinho["itens"] as $kCart => $vCart) {
                $totalPedido[] = ($vCart["qtde"]*$vCart["valor_unitario"]);
                $tm = "";
                if (isset($vCart["tamanho"]) && strlen($vCart["tamanho"]) > 0) {
                    $tm = "<br><em>Tamanho selecionado: <b>".$vCart["tamanho"]."</b></em>";
                }
        ?>
        <tr id="eco_item_carrinho_<?php echo $vCart["loja_b2b_carrinho_item"];?>">
            <td class="eco_vertical_align_middle">
                <div class="thumbnail span2">
                    <img style="height:65px !important" src="<?php echo $vCart["produto"]["fotos"][0];?>">
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
        <?php }?>
    </tbody>  
    </tfoot> 
        <tr>
            <td colspan="2" class="eco_text_align_right"><?php echo strtoupper(traduz("subtotal"));?></td>
            <td colspan="3" class="eco_carrinho_preco_subtotal"> R$ <?php echo number_format(array_sum($totalPedido), 2, ',', '.');?></td>
        </tr>
        <?php if ($objLoja->usacheckout == "N") {?>  
        <tr>
            <td colspan="2" class="eco_text_align_right"> <?php echo strtoupper(traduz("condicao.de.pagamento"));?></td>
            <td colspan="3" class="eco_carrinho_preco_subtotal"><?php echo $dadosCarrinho['condicaopagamento']['descricao'];?></td>

        </tr>
        <?php 
            if ($login_fabrica == 42) { 
                list($forma_envio, $dias_entrega, $valor_envio) = explode("|", $_POST["formaEnvio"]);
                ?>
                <td colspan="4" class="eco_text_align_right">
                    FRETE ESCOLHIDO <b> - <?php echo $forma_envio;?>, <?php echo $dias_entrega;?> dia (s)</b>
                    <p><b>Para o endereço: </b>
                    <?php
                        $endereco_posto = $loja_posto_endereco.", ".$loja_posto_numero." - ".$loja_posto_complemento . " - ".$loja_posto_bairro;
                        $endereco_posto .= " - CEP: ".$loja_posto_cep." - ".$loja_posto_cidade."/".$loja_posto_estado;
                    ?>
                    <?php echo $endereco_posto;?></p>
                </td>
        <?php
            }
        }?>
        <?php if ($objLoja->usacheckout == "S") {?>
        <tr>
            <td colspan="2" class="eco_text_align_right">
            FRETE ESCOLHIDO <b> - <?php echo $forma_envio;?>, <?php echo $dias_entrega;?> dia (s)</b>
            <p><b>Para o endereço: </b>
            <?php
                $endereco_posto = $loja_posto_endereco.", ".$loja_posto_numero." - ".$loja_posto_complemento . " - ".$loja_posto_bairro;
                $endereco_posto .= " - CEP: ".$loja_posto_cep." - ".$loja_posto_cidade."/".$loja_posto_estado;
            ?>
            <?php echo $endereco_posto;?></p>
            </td>
            <td colspan="3" class="eco_carrinho_preco_total">R$ <?php echo $valor_envio;?></td>
        </tr>   
        <?php }?>
        <tr>
            <td colspan="2" class="eco_text_align_right"><?php echo strtoupper(traduz("total.do.pedido"));?></td>
            <td colspan="3" class="eco_carrinho_preco_total">R$ <?php echo number_format(number_format($valor_envio, 2)+array_sum($totalPedido), 2, ',', '.');?></td>
        </tr>    
    </tfoot>  
</table>  
<?php if ($objLoja->usacheckout == "S") {?>   
<BR >
<BR >
<div class="navbar">
    <div class="navbar-inner eco_carrinho_titulo eco_text_align_center">
        <h4><i class="fa fa-money"></i> <?php echo strtoupper(traduz("opções.de.pagamento"));?></h4>
    </div>
</div>
<form action="loja_new.php?pg=finalizado" method="post" class="form-horizontal">
<input type="hidden" name="formaEnvio" value="<?php echo $_POST["formaEnvio"];?>">
    <div class="control-group">
        <div class="controls">
            <img style="border: solid 2px #dddddd" src="https://stc.pagseguro.uol.com.br/public/img/banners/pagamento/todos_animado_550_50.gif" alt="Meios de pagamento do PagSeguro" title="Este site aceita pagamentos com as principais bandeiras e bancos, saldo em conta PagSeguro e boleto.">
        </div>
    </div>
<br /><br />
<div class="navbar">
    <div class="row-fluid" align="center">
    <div class="span12" align="center">
        <div class=" eco_text_align_center" align="center">
            <button type="submit"  data-loading-text="Aguarde estamos processando..." class="btn btn-success btn-pagar btn-large">
                <i class="icon-ok icon-white"></i> Pagar com PagSeguro
            </button>
        </div>
    </div>
    </div>
</div>
</form>
<?php } elseif(empty($pedido)) {?>  
<form action="loja_new.php?pg=finalizado" method="post" class="form-horizontal">
<div class="navbar">
    <div class="row-fluid" align="center">
    <div class="span12" align="center">
        <div class=" eco_text_align_center" align="center">
            <button type="button" data-loading-text="Aguarde estamos processando..." class="btn btn-success btn-finaliza-sem-checkout btn-large">
                <i class="icon-ok icon-white"></i> Confirmar e Finalizar
            </button>
        </div>
    </div>
    </div>
</div>
</form>
<?php }?>
