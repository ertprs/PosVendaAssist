<?php
    include_once('dbconfig.php');
    include_once('includes/dbconnect-inc.php');
    include_once('autentica_usuario.php');


    //retirar assim que  efetuar os testes
    if (in_array($login_fabrica, array(3,15,42,91,157,198))) {
	
	$hoje = new DateTime();
	$corte = new DateTime('2018-12-01');

        if ((in_array($login_fabrica, array(3,91)) && !in_array($login_posto, array(6359,343311,420340))) || (in_array($login_fabrica, array(157)) && $hoje <= $corte) ) {
            echo "<meta http-equiv=refresh content=\"0;URL=menu_inicial.php\">";
            exit;
        }

    } else {
        echo "<meta http-equiv=refresh content=\"0;URL=menu_inicial.php\">";
        exit;
    }

    $layout_menu = 'pedido';
    $title       = traduz('bem.vindo.a.loja.b2b');
    include_once('cabecalho_new.php');
?>
</div> 
    <?php include_once('loja/template/cabecalho.php');?>
    <div class="container-fluid">
        <div class="row-fluid">
            <?php include_once('loja/template/menu.php');?>
            <div class="span9">

                <?php 
                    if ($login_fabrica == 3) {
                        echo '<div class="alert alert-warning">As peças indisponíveis podem ser compradas na <a href="pedido_cadastro_normal.php" target="_blank"><b>Tela de Pedido Normal<b></a>, pois tratam-se de estoques separados</div>';
                    }
                    switch ($_GET['pg']) {
                        case 'detalhe-produto':
                            include_once('loja/includes/produtos/detalhe-produto.php');
                            break;
                        case 'carrinho':
                            include_once('loja/includes/carrinho/index.php');
                            break;
                        case 'finalizar-pedido':
                            include_once('loja/includes/carrinho/finalizar_pedido.php');
                            break;
                        case 'finalizado':
                            include_once('loja/includes/carrinho/finalizado.php');
                            break;
                        case 'busca-avancada':
                            include_once('loja/includes/vitrine/busca_avancada.php');
                            break;
                        default:
                            include_once('loja/includes/vitrine/vitrine.php');
                            break;
                    }
                ?>               
            </div>
        </div>
    </div>
    <?php include_once('loja/template/rodape.php');?>
    <script defer src="https://use.fontawesome.com/releases/v5.0.8/js/all.js"></script>
    <script src='loja/layout/js/jquery.zoom.js?v=<?php echo date("YmdHis");?>'></script>
    <script src='plugins/jquery.maskedinput_new.js?v=<?php echo date("YmdHis");?>'></script>
    <script src='plugins/price_format/jquery.price_format.1.7.min.js?v=<?php echo date("YmdHis");?>'></script>
    <script src='plugins/price_format/config.js?v=<?php echo date("YmdHis");?>'></script>
    <script src='loja/layout/js/loja.js?v=<?php echo date("YmdHis");?>'></script>
    <?php 
    if ($_GET['pg'] == "carrinho" && $objLoja->usacheckout == "S") {
        /*<script>//calculaFreteCarrinho('<?php echo //$loja_posto_cep;?>');</script> */

        if (isset($configLojaPagamento["meio"]["pagseguro"]) && $configLojaPagamento["meio"]["pagseguro"]["status"] == "1") {

            if (isset($configLojaPagamento["meio"]["pagseguro"]["ambiente"]) && $configLojaPagamento["meio"]["pagseguro"]["ambiente"] == "sandbox") {
                echo '<script type="text/javascript" src="'.$configLojaPagamento["meio"]["pagseguro"]["url_sandbox_js"].'pagseguro.directpayment.js"></script> ';
            } else {
                echo '<script type="text/javascript" src="'.$configLojaPagamento["meio"]["pagseguro"]["url_producao_js"].'pagseguro.directpayment.js"></script> ';
            }
            echo '<script src="loja/layout/js/payments_ps.js?v='.date("YmdHis").'"></script>
                  <script>
                     var sessao_id = "'.$sessao_id.'";
                  </script>';
        }
        if (isset($configLojaPagamento["meio"]["cielo"]) && $configLojaPagamento["meio"]["cielo"]["status"] == "1") {
            echo '<script src="loja/layout/js/payments_cielo.js?v='.date("YmdHis").'"></script>';
        }
        if (isset($configLojaPagamento["meio"]["maxipago"]) && $configLojaPagamento["meio"]["maxipago"]["status"] == "1") {
            echo '<script src="loja/layout/js/payments_maxipago.js?v='.date("YmdHis").'"></script>';
        }

       /** calculaFreteCarrinho('<?php echo $loja_posto_cep;?>');*/

    }

    if ($_GET['pg'] == "carrinho") {
        echo '<script> atualizaTotalItens();</script>';

        if (isset($configLojaFrete["meio"]["correios"]) && $configLojaFrete["meio"]["correios"]["status"] == "1") {
           echo '<script>calculaFreteCarrinho("'. $loja_posto_cep . '", "correios", "'.$dadosCarrinho['loja_b2b_carrinho'].'", "'.$id_fornecedor.'")</script>';
        }
    }
