<?php 
    if (!isset($_GET['produto']) && !$_GET['produto']) {
        include_once("loja/404.php");
        exit;
    }

    $produto     = $_GET['produto'];
    $kit         = $_GET['kit'];
    if (isset($kit) && $kit == true) {
        $rowProduto  = $objProduto->getKit($produto);
        $totalProduto = array();
        foreach ($rowProduto["itens_kit"] as $k => $rows) {
            $totalProduto[] = $rows["preco_venda"]*$rows["qtde"];
        }
    } else {
        $rowProduto  = $objProduto->get($produto);
    }
    $xproduto    = $rowProduto["fotos"];

    if (count($xproduto) == 1) {

        $fotoProdutoPrincipal = $xproduto[0];
        $fotoProduto = $xproduto[0];

    } else {

        $i = 0;

        foreach ($xproduto as $kFotos => $vFotos) {
            if ($i == 0) {
                $fotoProdutoPrincipal = $vFotos;
                $i++;
            } 
            $fotoProduto[] = $vFotos;
        }

    }
?>

<div class="eco_box_detalhe">
    <div class="row-fluid">
        <div class="span6">
            <div class="thumbnail" style="height:350px !important;">
                <span class='zoom ex1' id='ex1'>
                    <img  id="grande" class="img-responsive" style="height:350px !important;width:100% !important"  src="<?php echo $fotoProdutoPrincipal;?>" alt="...">
                </span>
            </div>
            <div class="row-fluid eco_margin_top10">
                <?php foreach ($fotoProduto as $vThumb) {?>
                <div class="span3" style="margin: 3px;">
                    <div class="thumbnail">
                        <img style="height:100px !important" class="eco_detalhe_thumb" src="<?php echo $vThumb;?>">
                    </div>
                </div>
                <?php }?>
            </div>
        </div>
        <div class="span6">
            <h3 class="eco_detalhe_nome_produto">
            <?php
            
                if ($login_fabrica != 42) {
                    echo $rowProduto["ref_peca"]." - ";
                }

                echo $rowProduto["nome_peca"];
            ?></h3>
            <?php if ($rowProduto["disponibilidade_peca"] == 'f') {?>
                <p class="eco_zera_padding_margin"><?php echo traduz("disponibilidade");?>: <b style="color:red"><?php echo traduz("indisponível");?></b></p><hr>
                <div class="well">
                    <h5 style="padding: 10px;margin: -20px;background-color: #d90000;color: #ffffff;"><?php echo strtoupper(traduz("avise.me.quando.disponível"));?></h5>
                    <p align="center" style="margin-top: 30px;"><?php echo traduz("produto.indisponível.no.momento");?>.</p>
                    <div align="center" style="text-align: center;">
                        <button class="btn btn-success btn-avise-me btn-large" type="button"> 
                            <i class="fa fa-envelope" aria-hidden="true"></i> <?php echo strtoupper(traduz("avise.me"));?>
                        </button>
                    </div>
                </div>
            <?php } else {?>
                <p class="eco_zera_padding_margin"><?php echo traduz("disponibilidade");?>: <b style="color:green"> <?php echo traduz("em.estoque");?></b></p>
                <?php /*if ($rowProduto["preco_promocional_peca"] > 0 && $rowProduto["preco_promocional_peca"] < $rowProduto["preco_peca"]) {?>
                    <p class="eco_zera_padding_margin eco_margin_top00"><strike>De:</strike></p>
                    <p class="eco_detalhe_preco_de" align="left">
                        <strike>R$ <?php echo number_format($rowProduto["preco_peca"], 2, ',', '.');?></strike>
                    </p>
                    <p class="eco_zera_padding_margin eco_padding_bottom10 eco_margin_top20">Por:</p>
                    <p class="eco_detalhe_preco" align="left">
                        R$ <?php echo number_format($rowProduto["preco_promocional_peca"], 2, ',', '.');?>
                    </p>
                <?php } else {*/?>
                    <div class="eco_zera_padding_margin eco_padding_bottom10 eco_margin_top20"><?php echo traduz("por");?>:</div>
                    <p class="eco_detalhe_preco" align="left">
                        R$ <?php 
                            if (isset($kit) && $kit == true) {
                                echo number_format(array_sum($totalProduto), 2, ',', '.');
                            } else {
                                echo number_format($rowProduto["preco_venda"], 2, ',', '.');
                            }
                         ?>
                    </p>
                <?php //} ?>
                <hr>
                <!--<p>
                Calcular Frete: 
                <div class="row-fluid">
                    <div class="span6">
                        <div class="input-group">
                            <div class="input-append">
                                <input class="span6 cep" id="simula_cep" name="simula_cep" placeholder="00000-000" type="text">
                                <button class="btn btn-detalhe-calcula-frete" type="button"> <i class="fa fa-truck"></i> Calcular</button>
                            </div>
                        </div>
                    </div>
                    <div class="span4">
                        <a href="http://www.buscacep.correios.com.br/sistemas/buscacep/" target="_blank">Não sabe o CEP?</a>
                    </div>
                </div>-->
                <form  action="loja_new.php?pg=carrinho" method="post">
                <?php if (isset($kit) && $kit == true) {?>

                    <input type="hidden" value="true" name="kit">
                    <input type="hidden" value="<?php echo $rowProduto["loja_b2b_kit_peca"];?>" name="codigo_produto">

                <?php } else {?>

                    <input type="hidden" value="<?php echo $rowProduto["codigo_peca"];?>" name="codigo_produto">

                <?php }?>
                <p>
                    <?php echo traduz("quantidade");?>:
                    <div class="row-fluid">
                        <div class="span2">
                            <input type="text" price="true" value="1" name="qtde_produto" class="span12 eco_detalhe_input_qtd">
                        </div>
                    </div>
                </p>
                <?php 
                    $typeButtonCarrinho = "submit";
                    $classButtonCarrinho = "";
                    $montaGrade = "";
                    if ($moduloB2BGrade) {
                        $grade = $objProduto->getGradeByProduto($rowProduto["codigo_peca"]);
                        $typeButtonCarrinho = "button";
                        $classButtonCarrinho = "btn_add_grade";
                        if (!isset($grade["erro"]) && count($grade) > 0) {
                            echo traduz("Tamanho:");
                            echo "<input type='hidden' name='item_grade_".$rowProduto["codigo_peca"]."' >";
                            $montaGrade .= "<ul class='grade'>";
                            for ($i=0; $i < count($grade); $i++) { 
                                $montaGrade .= "<li class='item_grade' data-produto='".$rowProduto["codigo_peca"]."' data-tamanho='".$grade[$i]."'>".$grade[$i]."</li>";
                            }
                            $montaGrade .= "</ul>";
                        }
                        echo $montaGrade ;
                    }


                ?>
                <hr>
                <div align="center">
                    <button type="<?php echo $typeButtonCarrinho?>" class="btn eco_detalhe_botao_adicionar <?php echo $classButtonCarrinho?> btn-large btn-block" role="button">
                        <i class="fa fa-shopping-cart"></i> <?php echo traduz("adicionar.no.carrinho");?>
                    </button> 
                </div>
                </form>
            <?php }?>
        </div>
    </div>
    <div class="row-fluid eco_margin_top30 eco_margin_bottom30">
        <div class="span12">
           <h4 class="eco_detalhe_titulo_descricao_produto"><?php echo strtoupper(traduz("detalhes.do.produto"));?></h4>
           <?php echo nl2br($rowProduto["descricao_peca"]);?>
           
        </div>
    </div>
</div>

<!-- Modal Calcula Frete -->
<div class="modal fade" id="modal_calcula_frete" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="myModalLabel"> <?php echo traduz("calcula.frete");?></h4>
      </div>
      <div class="modal-body">
        <div class="loading" align="center"></div>
        <div id="resultado"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo traduz("fechar");?> </button>
      </div>
    </div>
  </div>
</div>
