<!DOCTYPE html>
<?php
if (array_key_exists('doc', $_GET)) {
    $pag = $_GET['doc'];
} else {
    $pag = null;
}

if($_COOKIE['login'] <> 'true'){
    header("location: login.php");
}



?>

<html>
    <head>
        <title>Documentação Pós-Venda</title>
        <!-- <meta charset="utf-8">   -->
        <meta http-equiv="content-Type"  content="text/html; charset=iso-8859-1">
        <link rel="stylesheet" type="text/css" href="public/bootstrap/css/bootstrap.css">
        <link href="http://posvenda.telecontrol.com.br/assist/plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css" type="text/css" rel="stylesheet" media="screen">
        <link href="http://posvenda.telecontrol.com.br/assist/admin/css/tc_css.css" type="text/css" rel="stylesheet" media="screen">
        <link href="http://posvenda.telecontrol.com.br/assist/plugins/dataTable.css" type="text/css" rel="stylesheet" media="screen">
        <link href="http://posvenda.telecontrol.com.br/assist/admin/bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen">
        <link href="http://posvenda.telecontrol.com.br/assist/admin/bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen">
        <!--<link href="http://192.168.0.199/~guilherme/assist/plugins/shadowbox_lupa/shadowbox.css" type="text/css" rel="stylesheet" media="screen">-->
        <!--<link href="http://www.shadowbox-js.com/build/shadowbox.css" type="text/css" rel="stylesheet" media="screen">-->
        <link href="public/css/shadowbox.css" type="text/css" rel="stylesheet" media="screen">
        <link rel="stylesheet" type="text/css" href="public/css/style.css">
        <link rel="stylesheet" type="text/css" href="http://posvenda.telecontrol.com.br/assist/admin/plugins/multiselect/multiselect.css">
        <script src="http://posvenda.telecontrol.com.br/assist/plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
        <script src="public/bootstrap/js/bootstrap.min.js"></script>
        <script src="public/js/dataTable.js"></script>
        <script src="public/js/script.js"></script>
        <script src="http://posvenda.telecontrol.com.br/assist/plugins/jquery.alphanumeric.js"></script>
    </head>

    <body>

        <div id="env-geral">
            <div class="container">
                <div class='row'>
                    <div class="span2">
                        <img src="public/img/logo_tc_2009_texto.png" id='marca' width="200"/>
                    </div>
                    <div class="span10">
                        <div class="navbar" id='env-menu-superior'>
                            <div class="navbar-inner">
                                <a href="?doc=inicio" class="brand">Documentação Pos-Venda</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="span2">
                        <ul id="menu-acesso" class="nav nav-tabs nav-stacked ">
                            <li class="active"><a href="?doc=inicio">Home</a></li>
                            <li><a href="?doc=introducao">Primeiros Passos</a></li>
                            <li><a href="?doc=layout">Layout</a></li>
                            <li><a href="?doc=inputs">Inputs</a></li>
                            <li><a href="?doc=lupa">Lupa</a></li>
                            <li><a href="?doc=botoes">Botões</a></li>
                            <li><a href="?doc=form">Gerador de Form</a></li>
                            <li><a href="?doc=excel">Gerar Excel</a></li>
                            <li><a href="?doc=mensagens">Mensagens</a></li>
                            <li><a href="?doc=ajax">Ajax</a></li>
                            <li><a href="?doc=tables">Tabelas</a></li>
                            <li><a href="?doc=plugins" >Plugins</a></li>
                            <li><a href="?doc=modelo_tela">Modelo de Telas</a></li>
                        </ul>
                    </div>

                    <div class="span10">

                        <div id="documentacao">
                            <?php
                            if ($pag != null) {
                                if (!include './documentacao/' . $pag . '.php') {
                                    ?>
                                    <h4>Ops, Essa página não existe</h4>
                                    <p>Talvez o link esteja incorreto..</p>

                                    <?php
                                }
                            } else {
                                include './documentacao/inicio.php';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="container-fluid rodape">
            <div class="row-fluid">
                <div class="span1">

                </div>
                <div class="span2">
                    <ul class="list-rodape">
                        <li><a pagina="inicio" href="#">Home</a></li>
                        <li><a pagina="introducao" href="#">Primeiros Passos</a></li>
                        <li><a pagina="layout" href="#">Layout</a></li>
                        <li><a pagina="inputs" href="#">Input</a></li>
                        <li><a pagina="botoes" href="#">Botões</a></li>
                    </ul>
                </div>
                <div class="span2">
                    <ul class="list-rodape">
                        <li><a pagina="excel" href="#">Gerar Excel</a></li>
                        <li><a pagina="javascript" href="#">Javascript</a></li>
                        <li><a pagina="shadow" href="#">ShadowBox</a></li>
                        <li><a pagina="datatable" href="#">Data Table</a></li>
                        <li><a pagina="mensagens" href="#">Mensagens</a></li>
                    </ul>
                </div>
                <div class="span4">

                </div>
                <div class="span2 informacao-tecnica">
                    <ul>
                        <li>Documentação Pós-Venda</li>
                        <li>Atualizada em Julho/2013</li>
                    </ul>

                </div>
                <div class="span1">

                </div>
            </div>
            <div class="row-fluid row-titulo-inferior">
                <div class="span12">
                    <p class="titulo-inferior">© Copyright Telecontrol - Gestão de Pós-Venda e Pedido-Web  2013</p>
                </div>

            </div>
        </div>

    </body>
    <script src="http://posvenda.telecontrol.com.br/assist/plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
    <script src="http://posvenda.telecontrol.com.br/assist/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.core.min.js"></script>
    <script src="http://posvenda.telecontrol.com.br/assist/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.widget.min.js"></script>
    <script src="http://posvenda.telecontrol.com.br/assist/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.datepicker.min.js"></script>
    <script src="http://posvenda.telecontrol.com.br/assist/admin/plugins/multiselect/multiselect.js"></script>
    <script src="http://posvenda.telecontrol.com.br/assist/plugins/jquery.mask.js"></script>
    <script src="http://posvenda.telecontrol.com.br/assist/plugins/price_format/jquery.price_format.1.7.min.js"></script>
    <script src="http://posvenda.telecontrol.com.br/assist/plugins/price_format/config.js"></script>
    <script src="public/bootstrap/js/bootstrap-tooltip.js"></script>

    <!--<script src="http://192.168.0.199/~guilherme/assist/plugins/shadowbox_lupa/shadowbox.js"></script>-->
    <!--<script src="http://www.shadowbox-js.com/build/shadowbox.js"></script>-->
    <script src="public/js/shadowbox.js"></script>

</html>

