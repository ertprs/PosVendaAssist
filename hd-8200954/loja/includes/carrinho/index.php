<?php 

    if ($objLoja->usacheckout == "S") {
        include_once('loja/includes/carrinho/compagamento/carrinho.php');
    } else {
        include_once('loja/includes/carrinho/sempagamento/carrinho.php');
    }
