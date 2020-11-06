<?php 

    $condicoes_busca = array();
    $dadosProdutos = array();
    if (isset($_GET['categoria']) && $_GET['categoria'] > 0) {
    
        $categoria               = $_GET['categoria'];
        $dadosCategoria          = $objCategoria->get($categoria);
        $titulo_pagina           = $dadosCategoria["descricao"];
        $condicoes_busca["categoria"] = $categoria;

    } elseif (isset($_GET['busca']) && strlen($_GET['busca']) > 0  || $_GET['busca_avancada']) {
        
        if (isset($_GET['busca_avancada'])) {
            
            $titulo_pagina                  = traduz("resultado.da.pesquisa");            
            $busca_avancada                 = $_GET['busca_avancada'];
            $condicoes_busca["categoria"]        = $_GET['categoria'];
            $condicoes_busca["descricao_peca"]   = $_GET['descricao_peca'];
            $condicoes_busca["preco_inicial"]    = $_GET['preco_inicial'];
            $condicoes_busca["preco_final"]      = $_GET['preco_final'];

        } else {

            $condicoes_busca["descricao_peca"] = $_GET['busca'];
            $titulo_pagina = traduz("a.palavra.chave.pesquisada.foi").": <b>" . $busca . "</b>";

        }

    } else {

        include_once('loja/template/banner.php');
        $titulo_pagina = traduz("produtos.em.destaques");

    }
    $dadosProdutos = $objProduto->getAll($condicoes_busca, 'posto');


?>
<h2 class="eco_vitrine_titulo"><?php echo $titulo_pagina;?></h2>
<div class="row-fluid eco_margin_bottom30 eco_margin_top30" id="vitrini_produtos">
<?php 

    if (empty($dadosProdutos) || $dadosProdutos["erro"]) {
        echo "<div class='alert alert-warning'>".traduz("nenhum.produto.encontrado").".</div>";
    } else {
        foreach ($dadosProdutos as $kProduto => $rowsProduto) {

            if (!isset($_GET['categoria']) && !isset($_GET['busca']) && !isset($_GET['busca_avancada'])) {
                if ($rowsProduto["peca_destaque"] != 't') {
                    continue;
                }
            }

            $fotoProduto = $rowsProduto["fotos"][0];
            if ($rowsProduto["disponibilidade_peca"] == 'f') {
                $linkProduto  = "";
                $preco_mostra = "<p class='eco_vitrine_preco_produto' style='color: #d90000;font-size: 19px;letter-spacing: 0px;'>".traduz("produto.indisponível")."</p>";
            } else {

                if (isset($rowsProduto["itens_kit"]) && count($rowsProduto["itens_kit"])> 0) {
                    $totalProduto = array();
                    foreach ($rowsProduto["itens_kit"] as $k => $rows) {
                        $totalProduto[] = $rows["preco_venda"]*$rows["qtde"];
                    }

                    $linkProduto  = " onclick=\"window.location.href='loja_new.php?pg=detalhe-produto&kit=true&produto=".$rowsProduto["loja_b2b_kit_peca"]."'\"";
                    $linkCarrinho = "href='loja_new.php?pg=carrinho&kit=true&codigo_produto=".$rowsProduto["loja_b2b_kit_peca"]."'";
                    $preco_mostra = "<p class='eco_vitrine_preco_produto'>R$ ".number_format(array_sum($totalProduto), 2, ',', '.')."</p>";
                } else {

                    $linkProduto  = " onclick=\"window.location.href='loja_new.php?pg=detalhe-produto&produto=".$rowsProduto["codigo_peca"]."'\"";
                    $linkCarrinho = "href='loja_new.php?pg=carrinho&codigo_produto=".$rowsProduto["codigo_peca"]."'";
                    $preco_mostra = "<p class='eco_vitrine_preco_produto'>R$ ".number_format($rowsProduto["preco_venda"], 2, ',', '.')."</p>";
                }

            }
?>
    <div class="span3 quadroDiv" style="margin:7px;margin-bottom:20px">
        <div class="thumbnail eco_text_align_center">
            <div class="eco_text_align_center" <?php echo $linkProduto;?> align="center">
                <img src="<?php echo $fotoProduto;?>" class="eco_vitrine_thumb" alt="...">
                <div class="caption">
                    <h4 class="eco_vitrine_nome_produto">
                        <?php echo $rowsProduto["ref_peca"];?> - <?php echo $rowsProduto["nome_peca"];?></h4>
                    <?php echo $preco_mostra;?>
                </div>
            </div>
            <?php if ($rowsProduto["disponibilidade_peca"] == 'f') {?>
            <div align="center">               
                <button data-id="<?php echo $rowsProduto["codigo_peca"];?>" class="btn eco_vitrine_botao_avisa_me btn-avise-me btn-block" type="button"> 
                    <i class="fa fa-envelope" aria-hidden="true"></i> <?php echo traduz("avise.me");?>
                </button>
            </div>
            <?php
             } else {
                $link_carrinho = $linkCarrinho;
                $montaGrade = "";
                if ($moduloB2BGrade) {
                    $grade = $objProduto->getGradeByProduto($rowsProduto["codigo_peca"]);
                    #if (!isset($grade["erro"]) && count($grade) > 0) {
                        echo "<input type='hidden' name='item_grade_".$rowsProduto["codigo_peca"]."' >";
                        $montaGrade .= "<ul class='grade'>";
                        for ($i=0; $i < count($grade); $i++) { 
                            $montaGrade .= "<li class='item_grade' data-produto='".$rowsProduto["codigo_peca"]."' data-tamanho='".$grade[$i]."'>".$grade[$i]."</li>";
                        }
                        $montaGrade .= "</ul>";
                    $link_carrinho = " onclick='add_carrinho_com_grade(".$rowsProduto["codigo_peca"].", $(\"input[name=item_grade_".$rowsProduto["codigo_peca"]."]\").val())' ";
                    #}
                }
            ?>
            <div align="center">
                <?php echo $montaGrade;?>
                <a <?php echo $link_carrinho;?> class="btn eco_vitrine_botao_adicionar btn-block" role="button">
                    <i class="icon-shopping-cart icon-white"></i> <?php echo traduz("adicionar.no.carrinho");?>

                </a> 
            </div>
            <?php }?>
        </div>
    </div>
<?php } 

    /*if (isset($dadosProdutos["kits"])) {
    foreach ($dadosProdutos["kits"] as $kProduto => $rowsProduto) {

        if (!isset($_GET['categoria']) && !isset($_GET['busca']) && !isset($_GET['busca_avancada'])) {
            if ($rowsProduto["destaque"] != 't') {
                continue;
            }
        }
        $totalProduto = array();
        foreach ($rowsProduto["itens_kit"] as $k => $rows) {
            $totalProduto[] = $rows["preco_venda"]*$rows["qtde"];
        }

        $fotoProduto = $rowsProduto["fotos"][0];

        if ($rowsProduto["disponivel"] == 'f') {
            $linkProduto  = "";
            $preco_mostra = "<p class='eco_vitrine_preco_produto' style='color: #d90000;font-size: 19px;letter-spacing: 0px;'>Produto indisponível</p>";
        } else {
            $linkProduto  = " onclick=\"window.location.href='loja_new.php?pg=detalhe-produto&kit=true&produto=".$rowsProduto["loja_b2b_kit_peca"]."'\"";
            $preco_mostra = "<p class='eco_vitrine_preco_produto'>R$ ".number_format(array_sum($totalProduto), 2, ',', '.')."</p>";
        }
?>
    <div class="span3" style="margin:7px;margin-bottom:20px">
        <div class="thumbnail eco_text_align_center">
            <div class="eco_text_align_center" <?php echo $linkProduto;?> align="center">
                <img src="<?php echo $fotoProduto;?>" class="eco_vitrine_thumb" alt="...">
                <div class="caption">
                    <h4 class="eco_vitrine_nome_produto"><?php echo $rowsProduto["referencia"];?> - <?php echo $rowsProduto["nome"];?></h4>
                    <?php echo $preco_mostra;?>
                </div>
            </div>
            <?php if ($rowsProduto["disponivel"] == 'f') {?>
            <div align="center">               
                <button data-id="<?php echo $rowsProduto["loja_b2b_kit_peca"];?>" class="btn eco_vitrine_botao_avisa_me btn-avise-me btn-block" type="button"> 
                    <i class="fa fa-envelope" aria-hidden="true"></i> Avise-me
                </button>
            </div>
            <?php } else {?>
            <div align="center">
                <a href="loja_new.php?pg=carrinho&kit=true&codigo_produto=<?php echo $rowsProduto["loja_b2b_kit_peca"];?>" class="btn eco_vitrine_botao_adicionar btn-block" role="button">
                    <i class="icon-shopping-cart icon-white"></i> Adicionar no carrinho
                </a> 
            </div>
            <?php }?>
        </div>
    </div>

<?php


        }
    }
*/

?>
<?php }?>
</div>
