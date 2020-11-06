<?php 
    if (strlen($_GET["pedido"]) == 0)  {
        echo "<script>alert('Pedido não encontrado');window.close();</script>";
        exit;
    }
    $pedido = $_GET["pedido"];  
    include 'dbconfig.php';
    include 'includes/dbconnect-inc.php';
    include 'autentica_admin.php';
    include 'funcoes.php'; 
    include_once "../class/tdocs.class.php";

    use Lojavirtual\Loja;
    use Lojavirtual\Banner;
    use Lojavirtual\AviseMe;
    use Lojavirtual\Produto;
    use Lojavirtual\CupomDesconto;
    use Lojavirtual\Categoria;
    use Lojavirtual\CarrinhoCompra;
    use Lojavirtual\Comunicacao;
    use Lojavirtual\LojaCliente;
    use Lojavirtual\Checkout;

    $objComunicacao    = new Comunicacao(null,null,$externalId);
    $objLoja           = new Loja();
    $objCheckout       = new Checkout();
    $objBanner         = new Banner();
    $objAviseMe        = new AviseMe();
    $objLojaCliente    = new LojaCliente();
    $objCarrinhoCompra = new CarrinhoCompra();

    if ($login_fabrica == 42) {
        $login_posto   = $objCarrinhoCompra->retornaPostoCarrinho($pedido);
    }

    $dadosCliente      = $objLojaCliente->get(null,$login_posto);
    $objProduto        = new Produto($login_posto,$dadosCliente["loja_b2b_cliente"]);
    $objCupomDesconto  = new CupomDesconto();
    $objCategoria      = new Categoria();
    
    $TDocs             = new TDocs($con, $login_fabrica);
    $TDocs->setContext('lojalogo');

    if ($login_fabrica == 42) {
        $dadosPedido = $objCarrinhoCompra->getAllCarrinho($dadosCliente['loja_b2b_cliente'],'',false,$pedido);
        $dadosPedido['valor_frete'] = $dadosPedido['total_frete'];
    } else {
        $dadosPedido = $objCarrinhoCompra->getPedidoB2B($pedido);
    }

    $title                  = "Pedido Loja B2B - Impressão";
    $logoLoja               = $TDocs->getDocumentsByRef($objLoja->loja)->url;
    $logomarca_loja         = (!empty($logoLoja)) ? $logoLoja : "../loja/layout/img/logo.png";
    $configLoja             = $objLoja ->getConfigLoja();
    $configLojaFrete     = json_decode($configLoja["pa_forma_envio"], 1);
    $configLojaPagamento = json_decode($configLoja["pa_forma_pagamento"], 1);

?>
<html>
<head>
    <title><?php echo $title ?></title>
    <meta http-equiv="content-Type"  content="text/html; charset=iso-8859-1">

    <style type="text/css">
        body{font-family: arial;font-size: 13px}
        .titulo_tabela {
        font-weight: bold;
        background-color: #eeeeee;
        }
        table tr td{
            padding: 5px 20px;
        }

        .box-print {
            max-width: 100%;
            margin: 0 auto;
        }

        table {
        width: 100%;
        font-size: 12px;
        }
        .tar{text-align: right;}
        .tac{text-align: center;}
        .important {
            background-color: #b94a48;color: #ffffff;
        }
        .info{background-color: #3a87ad;color: #ffffff;}
        .warning{background-color: #f89406;color: #ffffff;}
        .success{background-color: #468847;color: #ffffff;}
        span.success{background-color: #468847;color: #ffffff;padding: 5px 35px;width: 100%}
        h1, h2, h3 {
            line-height: 20px;
        }
        h3{font-size: 17px}
        .row-fluid{
            width: 100%;
        }
        .span2{width: 20%;float: left;}
        .span10{width: 80%;float: left;}
        .span12{width: 100%;float: left;}
        .span6{width: 50%;float: right;} 
        .span4{width: 40%;float: right;} 
        .span66{width: 50%;float: left;} 
    </style>
</head>
<body>
    
<script language="JavaScript">
   window.print();
</script> 
     <div class="box-print">
        <div class="row-fluid">
            <div class="span2"  align="left">
                <img src="<?php echo $logomarca_loja;?>" class="eco_logo" style="height: 80px !important; " />
            </div>
            <div class="span10" align="right">
                <h3>PEDIDO Nº</h3>
                <h2><?php echo $pedido;?></h2>
                <?php
                    if ($login_fabrica == 42) {
                        echo $dadosPedido['data_ultimo_item'];
                    }
                ?>
            </div>
        </div>
        <div class="row-fluid">
            <div class="span66" style="text-align: left" align="left">
                <strong>Razão Social:</strong> <br><?= $dadosPedido['cnpj_posto'] ?> &nbsp; - &nbsp; <?= $dadosPedido['nome_posto'] ?> <br />
                <strong>I.E:</strong> <?= $dadosPedido['inscricao_estadual'] ?> &nbsp; - &nbsp; <strong>Telefone:</strong> <?= $dadosPedido['telefone_posto'] ?><br />
                <strong>E-mail:</strong> <?= $dadosPedido['email_posto'] ?> 
                
            </div>
            <div class="span4" align="right">
                <strong>Endereço:</strong> <br>
                <?= $dadosPedido['endereco_posto'] ?>, <?= $dadosPedido['numero_posto'] ?> - <?= $dadosPedido['complemento_posto'] ?>
                &nbsp; - &nbsp; <?= $dadosPedido['bairro_posto'] ?>&nbsp; - &nbsp;              
                <strong>CEP:</strong> <?= $dadosPedido['cep_posto'] ?> &nbsp; - &nbsp;    
                <?= $dadosPedido['cidade_posto'] ?>&nbsp; / &nbsp;
                <?= $dadosPedido['estado_posto'] ?>
                <br />
            </div>
        </div>
        <hr style="display: block;width: 100%">
        <table style="border-color: #eeeeee" BORDER="1" CELLSPACING="0" CELLPADDING="0">
            <tr class="titulo_tabela">
                <td nowrap width="60%">Produto</td>
                <td nowrap class="tac">Quantidade</td>
                <td nowrap class="tac">Valor Unitário</td>
                <td nowrap class="tac">Subtotal</td>
            </tr>
                <?php 
                    $subtotal = array();
                    $cor = ($linha % 2 == 0) ? "#fff" : "#f5f5f5" ;


                    if ($login_fabrica != 42) {

                        foreach ($dadosPedido["itens"] as $key => $item) {
                            $dadosProduto  =  $objProduto->get(null, $item["peca"]);
                            $subtotal[]    = ($item["preco"]*$item["qtde"]);
                            $tm = "";
                                if (isset($item["tamanho"]) && strlen($item["tamanho"]) > 0) {
                                    $tm = "<br><em>Tamanho selecionado: <b>".$item["tamanho"]."</b></em>";
                                }
                            ?>
                            <tr bgcolor="<?php echo $cor;?>">
                                <td><?php echo $dadosProduto[0]["nome_peca"] . $tm;?></td>
                                <td class="tac"><?php echo $item["qtde"];?></td>
                                <td class="tac"><?php echo "R$ ".number_format($item["preco"], 2, ',', '.');?></td>
                                <td class="tac"><?php echo "R$ ".number_format(($item["preco"]*$item["qtde"]), 2, ',', '.');?></td>
                            </tr>
                            <?php $linha++;

                        }

                    } else {
                        foreach ($dadosPedido["itens"] as $key => $item) {
                                $dadosProduto  =  $objProduto->get(null, $item["peca"]);
                                $subtotal[]    = ($item["valor_unitario"]*$item["qtde"]);
                                $tm = "";
                                if (isset($item["tamanho"]) && strlen($item["tamanho"]) > 0) {
                                    $tm = "<br><em>Tamanho selecionado: <b>".$item["tamanho"]."</b></em>";
                                }
                            ?>
                            <tr bgcolor="<?php echo $cor;?>">
                                <td><?php echo $item["produto"]["nome_peca"] . $tm;?></td>
                                <td class="tac"><?php echo $item["qtde"];?></td>
                                <td class="tac"><?php echo "R$ ".number_format($item["valor_unitario"], 2, ',', '.');?></td>
                                <td class="tac"><?php echo "R$ ".number_format(($item["valor_unitario"]*$item["qtde"]), 2, ',', '.');?></td>
                            </tr>
                            <?php $linha++;
                        }
                    }
                ?>
                <tr>
                    <td colspan="3" class="tar"><b>SUBTOTAL</b></td>
                    <td class="tac"><?php echo "R$ ".number_format(array_sum($subtotal), 2, ',', '.');?></td>
                </tr>
                
                    <?php 
                        if (!empty($configLojaFrete)) {
                            if ($login_fabrica == 42) {
                                echo '
                                <tr>
                                    <td colspan="3" class="tar"><b>FRETE</b></td>';
                                echo '<td class="tac"><strong>'.$dadosPedido['forma_envio'].'</strong><br />R$'.number_format($dadosPedido['valor_frete'], 2,',', '.').'</td>';
                                echo ' </tr>';
                            } elseif (isset($configLojaFrete["frete_gratis"]) && $configLojaFrete["frete_gratis"]) {
                                echo '
                                <tr>
                                    <td colspan="3" class="tar"><b>FRETE</b></td>
                                    <td class="tac">R$ 0,00</td>
                                </tr>';
                            } else {
                                echo '
                                <tr>
                                    <td colspan="3" class="tar"><b>FRETE</b></td>';
                                echo '<td class="tac">R$'.number_format($dadosPedido['valor_frete'], 2,',', '.').'</td>';
                                echo ' </tr>';
                                
                            }
                        }
                    ?>
                <tr>
                    <td colspan="3" class="tar"><b>TOTAL DO PEDIDO</b></td>
                    <td class="tac"><?php
                        if ($dadosPedido['valor_frete'] > 0) {
                            echo "R$ ".number_format(array_sum($subtotal) + $dadosPedido['valor_frete'], 2, ',', '.');
                        } else {
                            echo "R$ ".number_format(array_sum($subtotal), 2, ',', '.');
                        }
                    ?></td>
                </tr>
        </table>
        <?php if ($objLoja->usacheckout != "S") {?>
            <table style="border-color: #eeeeee" BORDER="1" CELLSPACING="0" CELLPADDING="0">
                <?php if ($login_fabrica != 42) {?>
                <tr>
                    <td nowrap bgcolor="#eeeeee"  class="tar"><?php echo strtoupper(traduz("status.do.pedido"));?></td>
                    <td nowrap><?php echo $dadosPedido["status"]["descricao"];?></td>
                </tr>
                <?php }?>
                <tr>
                    <td nowrap bgcolor="#eeeeee"  class="tar"><?php echo strtoupper(traduz("condicao.de.pagamento"));?></td>
                    <td nowrap><?php echo $dadosPedido["condicaopagamento"]["descricao"];?></td>
                </tr>
          </table>
        <?php }?>
        <?php if ($objLoja->usacheckout == "S") {?>
            <table style="border-color: #eeeeee" BORDER="1" CELLSPACING="0" CELLPADDING="0">
                <tr>
                    <td nowrap bgcolor="#eeeeee"  class="tar"><?php echo strtoupper(traduz("opcao.de.pagamento.escolhida"));?></td>
                    <td nowrap>
                    <?php 
                        $pagamento = $objCarrinhoCompra->getPagamentoB2B($pedido);
                        if ($pagamento["tipo_pagamento"] == "B") {
                            echo traduz("boleto");
                        }
                        if ($pagamento["tipo_pagamento"] == "C") {
                            echo traduz("cartao.de.credito");
                        }
                    ?>
                    </td>
                </tr>
                <tr>
                    <td nowrap bgcolor="#eeeeee" class="tar">
                        <?php echo mb_strtoupper(traduz("status.pagamento"));?>
                            
                        </td>
                    <?php 

                        if (isset($configLojaPagamento["meio"]["pagseguro"]) && $configLojaPagamento["meio"]["pagseguro"]["status"] == "1" ) {
                            $status_pagseguro = $configLojaPagamento["meio"]["pagseguro"]["status_pagamento"];
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
                            echo '<td nowrap><p class="label label-'.$classAlert.'" title="'.$status_pagseguro[$statusPagamento].'">'.$status_pagseguro[$statusPagamento].'</p></td>';
                            
                        } 
                        if (isset($configLojaPagamento["meio"]["cielo"]) && $configLojaPagamento["meio"]["cielo"]["status"] == "1"  && $configLojaPagamento["meio"]["cielo"]["cartao"]) {
                            
                            if ($pagamento["tipo_pagamento"] == "B" && $configLojaPagamento["meio"]["cielo"]["boleto"]) {

                                if (in_array($pagamento["status_pagamento"], array('4','6'))) {
                                    $classAlert = "success";
                                } else {
                                    $classAlert = "important";
                                }
                                echo '<td nowrap><p class="label label-'.$classAlert.'" title="'.$objCheckout->status_cielo_cartao_credito[$pagamento["status_pagamento"]].'">'.$objCheckout->status_cielo_cartao_credito[$pagamento["status_pagamento"]].'</p></td>';
                            }

                            if ($pagamento["tipo_pagamento"] == "C" && $configLojaPagamento["meio"]["cielo"]["cartao"]) {

                                if (in_array($pagamento["status_pagamento"], array('4','6'))) {
                                    $classAlert = "success";
                                } else {
                                    $classAlert = "important";
                                }
                                echo '<td nowrap><p class="label label-'.$classAlert.'" title="'.$objCheckout->status_cielo_cartao_credito[$pagamento["status_pagamento"]].'">'.$objCheckout->status_cielo_cartao_credito[$pagamento["status_pagamento"]].'</p></td>';
                            }

                        } 

                        if (isset($configLojaPagamento["meio"]["maxipago"]) && $configLojaPagamento["meio"]["maxipago"]["status"] == "1" ) {


                            if ($pagamento["tipo_pagamento"] == "B" && $configLojaPagamento["meio"]["maxipago"]["boleto"]) {

                                if (in_array($pagamento["status_pagamento"], array('10','35','36'))) {
                                    $classAlert = "success";
                                } else {
                                    $classAlert = "important";
                                }
                                echo '<td nowrap><p class="label label-'.$classAlert.'" title="'.$objCheckout->status_maxipago[$pagamento["status_pagamento"]].'">'.$objCheckout->status_maxipago[$pagamento["status_pagamento"]].'</p></td>';
                            }
                            //ainda não foi integrado com a maxi pago o cartao de credito, quando integrar remover esse comentario
                            /*if ($pagamento["tipo_pagamento"] == "C" && $configLojaPagamento["meio"]["maxipago"]["cartao"]) {
                                if (in_array($pagamento["status_pagamento"], array('4','6'))) {
                                    $classAlert = "success";
                                } else {
                                    $classAlert = "important";
                                }
                                echo '<td nowrap><p class="label label-'.$classAlert.'" title="'.$objCheckout->status_maxipago[$pagamento["status_pagamento"]].'">'.$objCheckout->status_maxipago[$pagamento["status_pagamento"]].'</p></td>';
                            }  */                 
                        }
                    ?>
                </tr>
                <tr>
                    <td bgcolor="#eeeeee" nowrap class="tar"><?php echo strtoupper(traduz("forma.de.envio.escolhida"));?></td>
                    <?php 
                        if (isset($configLojaFrete["meio"])) {
                            if (!empty($dadosPedido["forma_envio"])) {
                                echo '<td>
                                Tipo de Entrega: <b>'.$dadosPedido["forma_envio"]["descricao"].'</b>
                                </td>';
                            }
                        } elseif (isset($configLojaFrete["frete_gratis"]) && $configLojaFrete["frete_gratis"]) {
                            echo '<td><span class="label label-success">'.traduz("frete.grátis").'</span></td>';
                        }
                        
                    ?>
                </tr>
            </table>
        <?php }?>
    </div>
</body>
</html>