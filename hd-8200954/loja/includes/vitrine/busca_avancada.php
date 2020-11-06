<h2 class="eco_vitrine_titulo"> <?php echo traduz("busca.avancada");?> </h2>
<div class="well">
    <form action="" style="margin-bottom: 0px" method="get">
    <div class="row-fluid">
        <div class="span4">
            <label><?php echo traduz("categorias");?></label>
            <select name="categoria" id="categoria" class="span11 eco_select_busca">
                    <option value="" selected="selected"> - <?php echo traduz("escolha.uma.categoria");?> - </option>
                    <?php 
                        $categoriasBusca = $objCategoria->get();
                        if (!empty($categoriasBusca)) {
                            foreach ($categoriasBusca as $vCat) {
                    ?>
                    <option value="<?php echo $vCat['categoria'];?>"><?php echo $vCat['descricao'];?></option>
                    <?php  }
                        }
                    ?>
            </select>
        </div>
        <div class="span4">
            <label>Entre Preços</label>
            <input class="span6 eco_input_busca" placeholder="0,00" id="preco_inicial" name="preco_inicial" type="text">
            <input class="span6 eco_input_busca" placeholder="0,00" id="preco_final" name="preco_final" type="text">
        </div>
        <div class="span4">
            <label>Palavra chave</label>
            <input class="span11 eco_input_busca" placeholder="<?php echo traduz("digite.uma.palavra.chave");?> ..." id="descricao_peca" name="descricao_peca" type="text">
            <input id="busca_avancada" name="busca_avancada" value="true" type="hidden">
        </div>
    </div>
    <div class="row-fluid">
        <div class="span4"></div>
        <div class="span4 tac">
            <button class="btn eco_btn_busca_ok eco_btn_busca" type="submit"><i class="icon-search icon-white"></i> <?php echo traduz("pesquisar");?></button>
        </div>
        <div class="span4"></div>
    </div>
    </form>
</div>