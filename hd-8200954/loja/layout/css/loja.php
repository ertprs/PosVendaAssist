<?php
include_once('../../../dbconfig.php');
include_once('../../../includes/dbconnect-inc.php');
include_once('../../../autentica_usuario.php');
use Lojavirtual\Loja;
$loja = (isset($_GET['loja']) && $_GET['loja'] > 0) ? $_GET['loja'] : null;
if ($loja) {
    $objLoja = new Loja();
    $layoutLoja = $objLoja->getLayout($loja);
} else {
    $layoutLoja = file_get_contents("../../../classes/Lojavirtual/Layout/layout_padrao.json");
    $layoutLoja = json_decode($layoutLoja,1);
}

header("Content-type: text/css");

$eco_top_fundo                                  = $layoutLoja["eco_top_fundo"];
$eco_info_login_fundo                           = $layoutLoja["eco_info_login_fundo"];
$eco_info_login_cor                             = $layoutLoja["eco_info_login_cor"];

$eco_btn_busca_ok_fundo                         = $layoutLoja["eco_btn_busca_ok_fundo"];
$eco_btn_busca_ok_hover                         = $layoutLoja["eco_btn_busca_ok_hover"];
$eco_btn_busca_ok_cor                           = $layoutLoja["eco_btn_busca_ok_cor"];
$eco_text_carrinho_cor                          = $layoutLoja["eco_text_carrinho_cor"];

$eco_menu_box_fundo                             = $layoutLoja["eco_menu_box_fundo"];
$eco_menu_box_titulo_fundo                      = $layoutLoja["eco_menu_box_titulo_fundo"];
$eco_menu_box_titulo_cor                        = $layoutLoja["eco_menu_box_titulo_cor"];
$eco_menu_link_cor                              = $layoutLoja["eco_menu_link_cor"];
$eco_menu_link_hover                            = $layoutLoja["eco_menu_link_hover"];
$eco_menu_bg_link_hover                         = $layoutLoja["eco_menu_bg_link_hover"];
$eco_menu_separador_cor                         = $layoutLoja["eco_menu_separador_cor"];

$eco_vitrine_botao_adicionar_cor                = $layoutLoja["eco_vitrine_botao_adicionar_cor"];
$eco_vitrine_botao_adicionar_fundo              = $layoutLoja["eco_vitrine_botao_adicionar_fundo"];
$eco_vitrine_botao_adicionar_hover              = $layoutLoja["eco_vitrine_botao_adicionar_hover"];
$eco_vitrine_preco_produto_cor                  = $layoutLoja["eco_vitrine_preco_produto_cor"];

$eco_detalhe_titulo_descricao_produto_fundo     = $layoutLoja["eco_detalhe_titulo_descricao_produto_fundo"];
$eco_detalhe_nome_produto_cor                   = $layoutLoja["eco_detalhe_nome_produto_cor"];
$eco_detalhe_preco_cor                          = $layoutLoja["eco_detalhe_preco_cor"];
$eco_detalhe_titulo_descricao_produto_cor       = $layoutLoja["eco_detalhe_titulo_descricao_produto_cor"];
$eco_detalhe_botao_adicionar_cor                = $layoutLoja["eco_detalhe_botao_adicionar_cor"];
$eco_detalhe_botao_adicionar_fundo              = $layoutLoja["eco_detalhe_botao_adicionar_fundo"];
$eco_detalhe_botao_adicionar_hover              = $layoutLoja["eco_detalhe_botao_adicionar_hover"];

$eco_carrinho_titulo_fundo                      = $layoutLoja["eco_carrinho_titulo_fundo"];
$eco_carrinho_titulo_cor                        = $layoutLoja["eco_carrinho_titulo_cor"];
$eco_carrinho_npedido_cor                       = $layoutLoja["eco_carrinho_npedido_cor"];
$eco_carrinho_preco_subtotal_cor                = $layoutLoja["eco_carrinho_preco_subtotal_cor"];
$eco_carrinho_preco_total_cor                   = $layoutLoja["eco_carrinho_preco_total_cor"];
$eco_carrinho_preco_correio_cor                 = $layoutLoja["eco_carrinho_preco_correio_cor"];
$eco_carrinho_frete_borda_hover_cor             = $layoutLoja["eco_carrinho_frete_borda_hover_cor"];
$eco_carrinho_cartao_borda_hover_cor            = $layoutLoja["eco_carrinho_cartao_borda_hover_cor"];

?>

body {
    padding-top: 0px;
}  
.navbar {
    margin-bottom: 30px;
}
#eco_nav_left .nav {
    z-index: 10;
    background: transparent !important;
    height: auto !important;
    position: relative;
    top: 0;
    left: 0;
}
/* HOME */

    .eco_top{
        background: <?php echo $eco_top_fundo;?>;
        padding: 10px;
    }

    .eco_info_login{
        background: <?php echo $eco_info_login_fundo;?>;
        color: <?php echo $eco_info_login_cor;?>;
        width: 70%;
        margin: 0 auto;
        margin-top: -20px;
        z-index: 1000;
        padding: 10px;
        border-radius: 0px 0px 10px 10px;
        text-align: center;
    }

    #eco_topo{
        background: #ffffff !important;
        margin-top: -14px;
        height: 150px;
    }
    .eco_logo{
        margin-top: 5px;
    }

/* BUSCA HOME */

    .eco_input_busca{
        height: 35px;
        padding: 10px !important;
    }

    .eco_select_busca {
        height: 42px !important;
        padding: 6px !important;
    }
    .eco_btn_busca{
        padding:10px !important;
    }

    .eco_btn_busca_ok{
        background: <?php echo $eco_btn_busca_ok_fundo;?> !important;
        border-color: <?php echo $eco_btn_busca_ok_fundo;?> !important;
        color: <?php echo $eco_btn_busca_ok_cor;?> !important;
        cursor: pointer !important;
    }
    .eco_btn_busca_ok:hover{
        background: <?php echo $eco_btn_busca_ok_hover;?> !important;
        border-color: <?php echo $eco_btn_busca_ok_hover;?> !important;
        color: <?php echo $eco_btn_busca_ok_cor;?> !important;
        cursor: pointer !important;
    }

    .eco_text_carrinho{
        color: <?php echo $eco_text_carrinho_cor;?> !important;
    }

/* MENU LEFT */

    .eco_menu_box{
        background: <?php echo $eco_menu_box_fundo;?>;
        border-radius: 0px;
    }

    .eco_menu_box_titulo{
        padding: 10px;
        color: <?php echo $eco_menu_box_titulo_cor;?>;
        background: <?php echo $eco_menu_box_titulo_fundo;?>;
        font-size: 16px;
    }

    #eco_nav_left .well{
        padding: 3px !important
    }

    #eco_nav_left .nav-list > li > a {
        color: <?php echo $eco_menu_link_cor;?> !important;
        padding: 15px !important;
        text-transform: uppercase !important !important;
        border-bottom: solid 1px  <?php echo $eco_menu_separador_cor;?> !important;
    }
    #eco_nav_left .nav-list > li > a:hover,  #eco_nav_left .nav-list > li > a:focus {
        color: <?php echo $eco_menu_link_hover;?>;
        padding: 15px;
        background-color: <?php echo $eco_menu_bg_link_hover;?>;
        border-bottom: solid 1px <?php echo $eco_menu_separador_cor;?>;
    }

    #eco_nav_left .nav-list > .active > a,  #eco_nav_left .nav-list > .active > a:hover {
        color: <?php echo $eco_menu_link_hover;?>;;
        text-shadow: 0 -1px 0 rgba(0, 0, 0, 0.2);
        background-color: <?php echo $eco_menu_bg_link_hover;?>;
    }



/* VITRINE DE PRODUTOS */
    .eco_vitrine_titulo{
        font-weight: normal;
        font-size: 25px;
    }

    .eco_vitrine_nome_produto{
        font-size: 15px !important;
        font-weight: normal;
    }
    .eco_vitrine_preco_produto{
        font-size: 26px;
        color: <?php echo $eco_vitrine_preco_produto_cor;?>;
        text-align: center;
    }

    .eco_vitrine_thumb{
        width: 100%;
        height: 180px;
    }

    .eco_vitrine_botao_adicionar{
        background:  <?php echo $eco_vitrine_botao_adicionar_fundo;?> !important;
        border-color:<?php echo $eco_vitrine_botao_adicionar_fundo;?> !important;
        color: <?php echo $eco_vitrine_botao_adicionar_cor;?> !important;
    }
    .eco_vitrine_botao_adicionar:hover{
        background:  <?php echo $eco_vitrine_botao_adicionar_hover;?> !important;
        border-color: <?php echo $eco_vitrine_botao_adicionar_hover;?> !important;
        color: <?php echo $eco_vitrine_botao_adicionar_cor;?> !important;
    }

    .eco_vitrine_botao_avisa_me{
        background:   #d90000 !important;
        border-color: #ff0000 !important;
        color: #ffffff !important;
    }
    .eco_vitrine_botao_avisa_me:hover{
        background:   #ff0000 !important;
        border-color: #d90000 !important;
        color: #ffffff !important;
    }


/* DETALHE DO PRODUTO */
    .eco_box_detalhe .thumbnail:hover{
        opacity: 0.6;
    }
    .eco_box_detalhe .thumbnail{
        cursor: pointer;
       -webkit-transition: opacity .25s ease;
          -moz-transition: opacity .25s ease;
            -o-transition: opacity .25s ease;
           -ms-transition: opacity .25s ease;
               transition: opacity .25s ease;
    }
    .eco_detalhe_preco{
        font-size: 32px;
        color: <?php echo $eco_detalhe_preco_cor;?>;
        text-align: left;
    }
    .eco_detalhe_preco_de{
        font-size: 18px;
        color: #d90000;
        text-align: left;
    }

    .zoom {
        display:inline-block;
        position: relative;
    }

    .zoom:after {
        content:'';
        display:block; 
        width:33px; 
        height:33px; 
        position:absolute; 
        top:0;
        right:0;
        background:url("../js/icon.png");
    }

    .zoom img {
        display: block;
    }

    .zoom img::selection { background-color: transparent; }

    .eco_detalhe_input_qtd{
        height: 40px !important;
    }
    .eco_detalhe_titulo_descricao_produto{
        background:  <?php echo $eco_detalhe_titulo_descricao_produto_fundo;?>;
        padding:10px;
        color: <?php echo $eco_detalhe_titulo_descricao_produto_cor;?>;
    }
    .eco_detalhe_nome_produto{
        color: <?php echo $eco_detalhe_nome_produto_cor;?>;
        line-height: 28px;
        margin-top:0px;
    }
    .eco_detalhe_botao_adicionar{
        background:  <?php echo $eco_detalhe_botao_adicionar_fundo;?> !important;
        border-color:<?php echo $eco_detalhe_botao_adicionar_fundo;?> !important;
        color: <?php echo $eco_detalhe_botao_adicionar_cor;?> !important;
    }
    .eco_detalhe_botao_adicionar:hover{
        background:  <?php echo $eco_detalhe_botao_adicionar_hover;?> !important;
        border-color: <?php echo $eco_detalhe_botao_adicionar_hover;?> !important;
        color: <?php echo $eco_detalhe_botao_adicionar_cor;?> !important;
    }

/* CARRINHO DE COMPRAS */
    .eco_carrinho_titulo{
        border-radius:0px;
        border-color: <?php echo $eco_carrinho_titulo_fundo;?>;
        background: <?php echo $eco_carrinho_titulo_fundo;?>;
        color: <?php echo $eco_carrinho_titulo_cor;?>;
        font-size: 19px;
    }
    
    .eco_carrinho_titulo_sucesso{
        border-radius:0px;
        color:#ffffff;
        font-size: 19px;
        border-color: #51a351;
        background: #51a351;
    }
    
    .eco_carrinho_npedido{
        color: <?php echo $eco_carrinho_npedido_cor;?>;
    }

    .eco_carrinho_titulo_tr{
        background: #ddd ;
        font-size: 13px;
    }

    .eco_carrinho_preco_subtotal{
        color: <?php echo $eco_carrinho_preco_subtotal_cor;?>;
        font-size: 16px;
        font-weight: bold;
    }

    .eco_carrinho_preco_correio{
        color: <?php echo $eco_carrinho_preco_correio_cor;?>;
        font-size: 12px;
        font-weight: bold;
    }

    .eco_carrinho_prazo_correio{

    }

    .eco_carrinho_tipo_correio{

    }
    
    .eco_carrinho_nome_produto{
        margin-top: 10px !important;
    }

    .eco_carrinho_formas_envio{
        list-style: none;
    }

    .eco_carrinho_preco_total{
        color: <?php echo $eco_carrinho_preco_total_cor;?>;
        font-size: 20px;
        font-weight: bold;
    }

    .eco_carrinho_barra_bottom{
        margin-top: 0px;
        border-radius:0px;
    }


    .eco_carrinho_frete_escolher{
        padding:10px;
        margin-bottom: 10px;
        text-align: center;
    }

    .eco_carrinho_frete_escolher{
        padding:10px;
        margin-bottom: 10px;
        text-align: center;
        border:solid 2px #eee;
    }

    .eco_carrinho_frete_escolhido{
        padding:10px;
        margin-bottom: 10px;
        text-align: center;
        border:solid 4px  <?php echo $eco_carrinho_frete_borda_hover_cor;?>;
    }

/* FORMAS DE PAGAMENTO - reutilizei as classes do CARRINHO DE COMPRAS, e criei umas adicionais */
    .eco_formapagamento_bandeira{
        background:  url('../img/sprite_bandeiras.png');
        width: 332px;
        height: 31px;
        display: inline-block;
    }
    .eco_formapagamento_bandeira_all{
        border:  solid 2px #fff;
        cursor: pointer;
    }
    .eco_formapagamento_bandeira_all:hover, .eco_formapagamento_bandeira_all:focus, .eco_formapagamento_bandeira_all.active{
        border:  solid 2px <?php echo $eco_carrinho_cartao_borda_hover_cor;?>;
        cursor: pointer;
    }
    .eco_formapagamento_bandeira_visa{
        background-position: 332px 0px;
        width: 49px;
    }
    .eco_formapagamento_bandeira_master{
        width: 45px;
        background-position:  283px 0px;

    }
    .eco_formapagamento_bandeira_amex{
        width: 34px;
        background-position:  238px 0px;

    }
    .eco_formapagamento_bandeira_elo{
        width: 36px;
        background-position:  204px 0px;

    }
    .eco_formapagamento_bandeira_diners{
        width: 42px;
        background-position:  168px 0px;

    }
    .eco_formapagamento_bandeira_discover{
        width: 45px;
        background-position:  126px 0px;

    }
    .eco_formapagamento_bandeira_jbc{
        width: 38px;
        background-position:  81px 0px;

    }
    .eco_formapagamento_bandeira_aura{
        width: 43px;
        background-position:  43px 0px;

    }
    .eco_formapagamento_cartao{
        list-style: none;
        margin: 0px;
        padding: 0px;
    }
    .eco_formapagamento_cartao li{
        display: inline-block;
    }

/* UTEIS */
    .eco_margin_top0{
        margin-top: 0px;
    }

    .eco_margin_top5{
        margin-top: 5px;
    }

    .eco_margin_top10{
        margin-top: 10px  !important;
    }

    .eco_margin_top20{
        margin-top: 20px  !important;
    }

    .eco_margin_top30{
        margin-top: 30px  !important;
    }

    .eco_margin_bottom30{
        margin-bottom: 30px  !important;
    }

    .eco_text_align_right{
        text-align: right !important;
    }

    .eco_text_align_left{
        text-align: left !important;
    }

    .eco_text_align_center{
        text-align: center !important;
    }

    .eco_padding_left10 {
        padding-left: 10px;
    }
    .eco_zera_margin{
        margin: 0px !important;
    }

    .eco_zera_padding{
        padding: 0px !important;
    }

    .eco_zera_padding_margin{
        padding: 0px !important;
        margin: 0px !important;
    }

    .eco_vertical_align_middle{
        vertical-align: middle !important;
    }

    .eco_padding_bottom10 {
        padding-bottom: 7px !important;
    }

/* EXTRAS */
    .oculta_loja{
        display: none !important;
    }

    .btn-show-hide{
        text-align:center;
        background:#eee;
        display:block;
        padding:10px;
        width:100%;
        cursor: pointer;
    }

/* SOBRESCRITA BOOTSTRAP */
    .navbar-inverse .navbar-inner {
        height: 120px;
        background: #ffffff !important;
        border: none;
        border-bottom: solid 1px #ddd;
        border-radius: 0px !important;
    }
    .thumbnail:hover{
        opacity: 0.6;
    }
    .thumbnail{

        cursor: pointer;
       -webkit-transition: opacity .25s ease;
          -moz-transition: opacity .25s ease;
            -o-transition: opacity .25s ease;
           -ms-transition: opacity .25s ease;
               transition: opacity .25s ease;
    }


    .sidebar{margin-top: 150px; }
    .nav-sidebar > li{
        border-bottom: solid 1px #474883;
    }
    .nav-sidebar > li > a {
        color: #fff;
    }
    .nav-sidebar > li > a:hover, .nav-sidebar > li > a:focus{
        color: #373865;
    } 

    .eco_carrinho_input_qtd{
        padding: 10px !important;
        width:100%;
        text-align: center !important;
    }
    #loading-loja {
        position: fixed !important;
        width: 54%;
        z-index: 9999;
        background:#eeeeee;
        text-align:center;
        top: 30%;
        left: 24%;
        padding: 20px 40px;
        display: none;
        border: solid 3px #222;
    }
    #txt-status-pedido-loja{
        font-size: 16px;
        font-weight: bold;
        margin-top: 30px;
    }


.txt-pagamento{line-height: 30px;width: 88%;float: right;margin-bottom: 30px;font-size:16px;}
.meios-pagamentos, .meios-pagamentos input{text-align: left;cursor: pointer;}
.meio_pagamento:hover, .meio_pagamento:active{background-color: green !important;color: #ffffff;}
.active_meio_pagamento{background-color: green !important;color: #ffffff;}
.icone-pagamento{text-align: left;}
.icone-pagamento svg{font-size: 30px;}
label{font-size: 12px;font-weight: normal;margin-top: 5px;margin-bottom: -2px !important;}
.form-control{border-radius: 0px;}
hr{margin-bottom: 10px;margin-top: 10px;}
#icone{text-align: center;}
#icone svg{font-size: 230px;color:green;}
.all_cliente b{font-size: 12px;}
.all_cliente span{font-size: 12px;}
#brand_cartao{margin-top: 23px;}
#box-pagamento{background: #eeeeee;padding: 10px;}
.meio_pagamento_cartao{padding:20px;background: #ffffff;}
.meio_pagamento_boleto{padding:20px;background: #f5f5f5;}

.icone-bandeiras{background:#ffffff !important;margin-left:0px  !important;margin-right:8px  !important;margin-bottom: 10px  !important;border:solid 3px #ffffff  !important;cursor: pointer;}
.icone-bandeiras:hover{background:#ffffff !important;margin-left:0px  !important;margin-right:8px  !important;margin-bottom: 10px  !important;border:solid 3px #494994  !important;cursor: pointer;}
.active_bandeira{background:#ffffff !important;margin-left:0px  !important;margin-right:8px  !important;margin-bottom: 10px  !important;border:solid 3px #494994  !important;cursor: pointer;}

.ajuste-hr{margin-top: 0px !important}



.grade{
    list-style-type: none;
}
.item_grade{
    display: inline-block;
    border: solid 1px #ccc;
    padding: 5px 10px;
    margin-left:10px;
    cursor:pointer;
}
.item_grade:hover{
    display: inline-block;
    border: solid 1px #ccc;
    padding: 5px 10px;
    margin-left:10px;
    background: #559a16;
    color: #fff;
    cursor:pointer;
}


.ativo{
    display: inline-block;
    border: solid 1px #ccc;
    padding: 5px 10px;
    margin-left:10px;
    background: #559a16;
    color: #fff;
    cursor:pointer;
}


