<div id="eco_nav_left" class="span3">
    <div class="well sidebar-nav eco_menu_box">
        <div class="eco_menu_box_titulo">
            <b class="eco_padding_left10"><?php echo strtoupper(traduz('categorias'));?></b>
        </div>
        <ul class="nav nav-list">
            <li <?php echo (!$_GET["pg"] && !$_GET["categoria"]) ? "class='active'" : "";?>><a href="loja_new.php"><?php echo traduz('principal');?></a></li>
            <?php 
                $menuLoja = $objCategoria->get();
                if (!empty($menuLoja)) {
                    foreach ($menuLoja as $vMenu) {
                    $active = ($vMenu['categoria'] == $_GET["categoria"]) ? "active" : "";
            ?>
            <li class="<?php echo $active;?>"><a href="loja_new.php?categoria=<?php echo $vMenu['categoria'];?>" title="<?php echo $value_menu['descricao'];?>"><?php echo $vMenu['descricao'];?></a></li>
            <?php  }
                }
            ?>
        </ul>
    </div>
</div>