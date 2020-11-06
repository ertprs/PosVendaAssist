<?php
include "monitora_cabecalho.php";

function getmicrotime(){
    list($usec, $sec) = explode(" ",microtime());
    return ((float)$usec + (float)$sec);
}

function TempoExec($pagina, $sql, $time_start, $time_end){
        $time = $time_end - $time_start;
        $time = str_replace ('.',',',$time);
        $sql  = str_replace ('\t',' ',$sql);
}

require_once __DIR__ . DIRECTORY_SEPARATOR . 'fn_traducao.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'funcoes.php';
$micro_time_start = getmicrotime();

$gmtDate = gmdate("D, d M Y H:i:s");
header("Expires: {$gmtDate} GMT");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");
header("Last-Modified: {$gmtDate} GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

// O cabeçalho é usado na área do admin/bi/, aqui define os paths relativos,
// Pode ser usado dentro dos programas do BI para pegar as imagens do admin, também.
define('BI_BACK', (strpos($_SERVER['PHP_SELF'],'/bi/') == true)?'../':'');

// Para saber se está numa tela de menu...
define('TELA_MENU', (strpos($PHP_SELF, 'menu_')!==false));  // Define se a tela atual é algum menu
$tira_adSense      = in_array($login_fabrica, array(87));   // Adicionar os fabricantes que não querem o adSense nos menus. Também no admin.

$dir_help_desk     = BI_BACK . '../helpdesk';
$dir_help_desk_img = "$dir_help_desk/imagem";
$path_logo         = BI_BACK . '../logos';
$imagens_admin     = BI_BACK . 'imagens_admin';
$admin_fotos       = BI_BACK . 'admin_fotos';
$altera_logo_TcNet = ($login_fabrica == 46 and in_array($login_login, array('manuel','ronaldo','sergiotelecontrolnet','waldir','paulo')));

include (BI_BACK . '../fn_logoResize.php');
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">

<!-- AQUI COMEÇA O HTML DO MENU -->

<?php
    if ($login_fabrica == 87) {
        $pagina = basename($_SERVER['PHP_SELF']);
        if (!in_array($pagina, array('peca_cadastro.php','peca_consulta.php','preco_cadastro.php','posto_cadastro.php','pedido_parametros.php','pedido_consulta.php','pedido_cadastro.php','pedido_admin_consulta.php','menu_cadastro.php', 'menu_cadastro.php', 'menu_gerencia.php', 'transportadora_cadastro.php','depara_cadastro.php','admin_senha_n.php','menu_tecnica.php','comunicado_produto.php','relatorio_comunicado.php','comunicado_inicial.php')))
        {
            echo "<script>window.location = 'http://posvenda.telecontrol.com.br/assist/admin/menu_cadastro.php'</script>";
            //echo "<script>window.location = 'http://192.168.0.199/~guilherme/assist/admin/menu_cadastro.php'</script>";
        }
    }

    //Abas para usuário admin normal.
    switch ($layout_menu) {
        case "gerencia":
            $imgAbas = "<img src='$imagens_admin/btn_gerencia.gif' usemap='#menu_map'>";
            $cor = "#E6D1DE";
            break;
        case "callcenter":
            $imgAbas = "<img src='$imagens_admin/btn_callcenter.gif' usemap='#menu_map'>";
            $cor = "#E2F6D7";
            break;
        case "cadastro":
            $imgAbas = "<img src='$imagens_admin/btn_cadastro.gif' usemap='#menu_map'>";
            $cor = "#FFCA8F";
            break;
        case "tecnica":
            $imgAbas = "<img src='$imagens_admin/btn_tecnica.gif' usemap='#menu_map'>";
            $cor = "#C4E6F8";
            break;
        case "financeiro":
            $imgAbas = "<img src='$imagens_admin/btn_financeiro.gif' usemap='#menu_map'>";
            $cor = "#FEEFB7";
            break;
        case "auditoria":
            $imgAbas = "<img src='$imagens_admin/btn_auditoria.gif' usemap='#menu_map'>";
            $cor = "#B29C88";
            break;
        default:
            $imgAbas = "<img src='$imagens_admin/btn_gerencia.gif' usemap='#menu_map'>";
            $cor = "#FF9886";
            break;
    }
?>
<head>
    <title><? echo $title ?></title>
    <meta http-equiv="X-UA-Compatible" content="IE=8"/>
    <meta http-equiv="content-Type"  content="text/html; charset=iso-8859-1">
    <meta http-equiv="Expires"       content="0">
    <meta http-equiv="Pragma"        content="no-cache, public">
    <meta http-equiv="Cache-control" content="no-cache, public, must-revalidate, post-check=0, pre-check=0">
    <meta name      ="Author"        content="Telecontrol Networking Ltda">
    <meta name      ="Generator"     content="na mão...">
    <meta name      ="Description"   content="Sistema de gerenciamento para Postos de Assistência Técnica e Fabricantes.">
    <meta name      ="KeyWords"      content="Assistência Técnica, Postos, Manutenção, Internet, Webdesign, Orçamento, Comercial, Jóias, Callcenter">

    <link href="../admin/bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
    <link href="../admin/bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
    <link href="../admin/css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
    <link href="../admin/css/tooltips.css" type="text/css" rel="stylesheet" />
    <link href="../admin/plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css" type="text/css" rel="stylesheet" media="screen">
    <link href="../admin/bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />
    <link href="../admin/js/jquery-ui-1.8.23.custom/development-bundle/themes/base/jquery.ui.accordion.css" type="text/css" rel="stylesheet" media="screen" />

    <!--[if lt IE 10]>
    <link href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-ie.css" rel="stylesheet" type="text/css" media="screen" />
    <link rel='stylesheet' type='text/css' href="bootstrap/css/ajuste_ie.css">
    <![endif]-->

    <script src="../admin/plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
    <script src="../admin/plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
    <script src="../admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.core.min.js"></script>
    <script src="../admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.widget.min.js"></script>
    <script src="../admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.effect.min.js"></script>
    <script src="../admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.tabs.min.js"></script>
    <script src="../admin/bootstrap/js/bootstrap.js"></script>
   <script>
        function loading (display) {
            switch (display) {
                case "show":
                    $("#loading").show();
                    $("#loading_action").val("t");
                    break;

                case "hide":
                    $("#loading").hide();
                    $("#loading_action").val("f");
                    break;
            }
        }

        function ajaxAction () {
            if ($("#loading_action").val() == "t") {
                alert("Espere o processo atual terminar!");
                return false;
            } else {
                return true;
            }
        }

        function submitForm (form, valor) {
            if(valor == undefined){
                valor = "submit";
            }

            var btn = $(form).find("#btn_click");

            if ($(btn).val().length > 0) {
                alert("Aguarde Submissão...");
            } else {
                $(btn).val(valor);
                $(form).submit();
            }
        }

        $(function () {
            $("#gerar_excel").click(function () {
                if (ajaxAction()) {
                    var json = $.parseJSON($("#jsonPOST").val());
                    json["gerar_excel"] = true;

                    $.ajax({
                        url: "<?=$_SERVER['PHP_SELF']?>",
                        type: "POST",
                        data: json,
                        beforeSend: function () {
                            loading("show");
                        },
                        complete: function (data) {
                            window.open(data.responseText, "_blank");

                            loading("hide");
                        }
                    });
                }
            });

            $(".lbl_usuario").click(function() {
                $(this).removeClass("lbl");
            });

            $("input[type!=radio][type!=checkbox], select, textarea").bind("valid", function (e, obj) {
                if ($.trim($(obj).val()).length > 0) {
                    $(obj).parents("div.control-group.error").removeClass("error");
                }
            });

            $("input[type!=radio][type!=checkbox], select, textarea").change(function () {
                $(this).trigger("valid", [ $(this) ]);
            });
        });
    </script>

<style>
    .scrollup{
        width:40px;
        height:40px;
        opacity: 1;
        position:fixed;
        bottom:100px;
        right:100px;
        display:none;
        text-indent:-9999px;
        background: url('imagens_admin/icon_top.jpg') no-repeat;
    }
    #adm_foto {
        float:right;
        max-height:50px;
        border: 3px solid white;
        margin:auto 0 auto 3px;
        box-shadow: 1px 1px 2px black;
        border-radius:3px;
        -o-transition: max-height 0.3s ease-out;
        -ms-transition: max-height 0.3s ease-out;
        -moz-transition: max-height 0.3s ease-out;
        -webkit-transition: max-height 0.3s ease-out;
        transition-delay: 0.3s;
        -o-transition-delay: 0.3s;
        -ms-transition-delay: 0.3s;
        -moz-transition-delay: 0.3s;
        -webkit-transition-delay: 0.3s;
    }
    #adm_foto:hover {
        max-height:128px;
        box-shadow: 2px 2px 3px black;
        border-radius:4px;
        border: 4px solid white;
    }

    .borda{
        background: -o-linear-gradient(right, <?php echo $cor; ?> , white);/*Opera 11.1+*/
        background: -ms-linear-gradient(right, <?php echo $cor; ?> , white);/*IE>=10*/
        background: -moz-linear-gradient(right, <?php echo $cor; ?> , white);/*Firefox*/
        background: -webkit-gradient(linear, 100% 0, 0 0, from(<?php echo $cor; ?>), white))); /*Chrome 9-*/
        background: -webkit-linear-gradient(right, <?php echo $cor; ?> , white);/*Chrome 9+*/
        background: linear-gradient(right, <?php echo $cor; ?> , white);/*Padrão*/
        padding-right: 10px;
    }

    .borda2{
        border-top-width:medium;
        border-top-style:solid;
        border-top-color:#DEE3EF;

    }

    #helpdesk_pendencia {
        position: absolute;
        float: right;
        display: block;
        width: 70px;
        text-align: center;
        top: 25px;
        margin-left: 850px;
        font-weight: bold;
        font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
        color: #B4696B;
        font-size: 9px;
    }

    #pendencia_atendimentos_icon {
        position: absolute;
        float: right;
        display: block;
        width: 70px;
        text-align: center;
        margin-left: 850px;
        top: 104px;
        background-image: url(imagens/pendencia_atendimento3.jpg);
        background-position: center;
        height: 45px;
        background-repeat: no-repeat;
        cursor: pointer;
    }

    #pendencia_atendimentos_list {
        display: none;
        position: absolute;
        float: right;
        text-align: center;
        margin-left: 610px;
        top: 100px;
        width: 300px;
        max-height: 288px;
        border: 1px solid black;
        border-radius: 8px;
        background-color: #FFF;
        z-index: 9999;
        padding: 5px;
    }

    #helpdesk {
        position: absolute;
        float: right;
        display: block;
        width: 70px;
        text-align: center;
        margin-left: 850px;
        top: 44px;
    }

    #helpdesk img {
        width: 35px;
        height: 35px;
        border: 0px;
    }

    #helpdesk span {
        position: relative;
        display: block;
        top: -4px;
        /* color: #5A6D9C; */
        color: #999999;
        font-weight: bold;
        font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
        font-size: 9px;
    }

    #helpdesk:hover a {
        text-decoration: none;
    }

    option {
        height: 22px;
    }

</style>
<!--[if lt IE 10]>
    <style>
        .borda {
            -pie-background: linear-gradient(right, <?php echo $cor; ?> , white);
            behavior: url(plugins/PIE/PIE.htc);
        }
    </style>
<![endif]-->
<script>
/*****************************************************************
Nome da Função : displayText
        Apresenta em um campo as informações de ajuda de onde
        o cursor estiver posicionado.
******************************************************************/
    function displayText( sText ) {

        if (document.getElementById("displayArea")) {
            document.getElementById("displayArea").innerHTML = sText;
        }

    }

    function atualiza_dado(admin) {

        window.open('atualiza_dado.php?admin='+admin, 'ouverture', 'toolbar=no, status=yes, scrollbars=yes, resizable=no, width=400, height=500');

    }

    function toggleCustomizePopUp(iFrameID) {
        var popUp = document.getElementById(iFrameID);
        
        $(popUp).toggle();

    }
</script>
<?

// Para as telas do menu, adicionada funcionalidade para colapsar e expandir as seções dos menus.
// Imagem com  status da conexão, oferecido pelo PingDom
// Barra com validação da versão do Navegador, e links para baixar os últimos navegadores.
if (TELA_MENU) { ?>
    <!--<script src="js/jquery-ui-1.8.23.custom/js/jquery-1.8.0.min.js"></script>-->
    <script type="text/javascript">
        $(function() {
            $('img.colexpand').parent().click(function(){
                $(this).find('.colexpand').attr('src', ($(this).next('table').is(':visible')) ? 'imagens/icon_expand.png':'imagens/icon_collapse.png');
                $(this).next('table').slideToggle();
            });
            $('table.tabela.ocultar caption').click(function() {
                $(this).parent()
                       .find('tbody.ocultar,thead').slideToggle('normal')
                       .delay(200)
                       .end()
                       .toggleClass('oculta');
            });

            //SCROLL DO MOUSE REVELA BOTAO PARA VOLTAR AO TOPO
            $('.scrollup').click(function(){
                $("html, body").animate({ scrollTop: 0 }, 600);
                return false;
            });
            $(window).scroll(function(){
                if ($(this).scrollTop() > 200) {
                    $('.scrollup').slideDown('slow');
                } else {
                    $('.scrollup').slideUp('slow');
                }
            });

        });
    </script>
    <link rel="stylesheet" href="../admin/css/menu_tc.css" />
    <style type="text/css">
            .lbl {
                color:red;
            }
        /* CSS PingDom */
        #pingDomImg {
            /* Positioning */
            position: fixed;
            top: -128px;
            right: 64px;
            overflow-y: hidden;

            /* Effects */
            border-radius: 0 0 5px 5px;
            -moz-border-radius: 0 0 5px 5px;
            transition: all 0.3s ease-in;
            -o-transition: all 0.3s ease-in;
            -ms-transition: all 0.3s ease-in;
            -moz-transition: all 0.3s ease-in;
            -webkit-transition: all 0.3s ease-in;
        }
        #pingDomImg {
            transition-delay: 0.5s;
            -o-transition-delay: 0.5s;
            -ms-transition-delay: 0.5s;
            -moz-transition-delay: 0.5s;
            -webkit-transition-delay: 0.5s;
        }
        #pingDomImg:hover {
            top: 0;
            z-index: 10000;
        }
    </style>
</head>
<body>
<!--[if lt IE 7]>
<div id='oldIE' style='padding: 0pt 0pt 0pt 15px; position: relative; width: 100%; text-align: center; margin: 0pt auto;'>
    <a href="http://windows.microsoft.com/en-US/internet-explorer/products/ie/home?ocid=ie6_countdown_bannercode" target='_blank'>
        <img src="http://storage.ie6countdown.com/assets/100/images/banners/warning_bar_0010_portuguese.jpg" border="0" height="42" width="820" alt="Você está usando um navegador desatualizado. Para uma experiência de navegação mais rápida, segura atualizar gratuitamente hoje." />
    </a>
</div>
<![endif]-->

    <script type="text/javascript">
    function showPingDomStats() {
        TINY.box.show({
            iframe: 'http://stats.pingdom.com/7amtsbb6gpl3',
            boxid:  'PingDom',
            width:  1024,
            height: 500,
            fixed:  true,
            maskid: 'bluemask',
            maskopacity:70
        });
    }
    </script>
    <img id='pingDomImg' src='http://share.pingdom.com/banners/13582b38' alt=''
        onClick="showPingDomStats()" />
<?

    //stats.pingdom.com/7amtsbb6gpl3 // Endereço de teste
} else {
    echo "</head>\n<body bgcolor='#ffffff' marginwidth='2' marginheight='2' topmargin='2' leftmargin='2' $body_onload />\n";
}
if ($login_fabrica != 190) {
    
include '../inc_browsers_new.php';
}
$arquivo_atual = $_SERVER["SCRIPT_FILENAME"];

if ($arquivo_atual) {
    $sql = "SELECT help
              FROM tbl_help
              JOIN tbl_arquivo
                ON tbl_help.arquivo = tbl_arquivo.arquivo
             WHERE tbl_arquivo.descricao ILIKE '%$arquivo_atual%'
               AND tbl_help.fabrica IN ($login_fabrica, 0)
             ORDER BY tbl_help.fabrica DESC LIMIT 1
    ";
    $res = pg_query($sql);
    if (pg_num_rows($res)) {
        $tbl_help_help = pg_fetch_result($res, 1, 0);

        //HD 205958: Help nas telas dos programas. Foi usado um iframe para evitar problemas com includes de JavaScript
        echo "
            <style>
            .div_tbl_help {
                position: absolute;
                top: 0px;
                left: 0px;
                width: 100%;
                height: 100%;
                display: none;
            }
            </style>
            <iframe class=div_tbl_help id='iframe_tbl_help' name='iframe_tbl_help' src='help_iframe_cabecalho.php?help=$tbl_help_help' frameborder=0 allowtransparency='true' scrolling=no width=100% height=100% style='background:none'></iframe>
        ";
    }
}

//include ("email_admin_include.php");

if ($admin_consulta_os == true)
    $sem_menu = true;

function montaSubmenu ($submenu) {
    global $login_fabrica;
    foreach ($submenu as $item) {
        if (array_key_exists("fabrica",$item)) {
            if (is_array($item["fabrica"])) {
                if (!in_array($login_fabrica, $item["fabrica"])) {
                    continue;
                }
            } else {
                if ($login_fabrica <> $item["fabrica"]) {
                    continue;
                }
            }
        }

        if (array_key_exists("fabrica_no",$item)) {
            if (is_array($item["fabrica_no"])) {
                if (in_array($login_fabrica, $item["fabrica_no"])) {
                    continue;
                }
            } else {
                if ($login_fabrica == $item["fabrica_no"]) {
                    continue;
                }
            }
        }

        if (array_key_exists("attr",$item)) {
            $style = $item["attr"];
        }

        $link  = $item["link"];
        $title = $item["descr"];
        $text  = $item["titulo"];

        $html .= "<span class='tc_submenu' {$style} title='{$title}' onclick=\"javascript: window.location = '{$link}'\">{$text}</span>";
    }

    return $html;
}
?>

<div class='container tc_container'>
    <div id="loading">
        <img src="imagens/loading_img.gif" />
        <input type="hidden" id="loading_action" value="f" />
    </div>

    <?php
    if ($sem_menu == false || !strlen($sem_menu)) {
        $arrayMenu = array(
            "contrato"  => "Contratos",
            "sair"        => "Sair"
        );
    ?>
    <div id="tc_menu" >
        <ul>
            <?php
            switch ($layout_menu) {
                case "tecnica":
                case "info_tecnica":
                    $layout_menu = "infotecnica";
                    break;
            }

            $i = 1;

            foreach ($arrayMenu as $key => $value) {
                if ($key == "callcenter" && in_array($login_fabrica, array(108, 111))) {
                    continue;
                }
                $rel = $i - 1;

                $atual = ($layout_menu == $key) ? "atual" : "";

                echo "<li class='{$key} {$atual}' rel='{$rel}'><a href='#tabs-{$i}' onclick=''>{$value}</a></li>";

                $i++;
            }

            ?>
        </ul>
    </div>

    <script>
        function getClass (li) {
            if ($(li).hasClass("gerencia")) {
                return "gerencia";
            } else if ($(li).hasClass("callcenter")) {
                return "callcenter";
            } else if ($(li).hasClass("cadastro")) {
                return "cadastro";
            } else if ($(li).hasClass("contrato")) {
                return "contrato";
            } else if ($(li).hasClass("infotecnica")) {
                return "infotecnica";
            } else if ($(li).hasClass("financeiro")) {
                return "financeiro";
            } else if ($(li).hasClass("auditoria")) {
                return "auditoria";
            } else if ($(li).hasClass("sair")) {
                return "sair";
            } else {
                return false;
            }
        }

        function toggleBackground (liClass) {
            var background = false;

            switch (liClass) {
                case "gerencia":
                    background = "#E6D1DE";
                    break;
                case "callcenter":
                    background = "#E2F6D7";
                    break;
                case "cadastro":
                    background = "#FFCA8F";
                    break;
                case "infotecnica":
                    background = "#C4E6F8";
                    break;
                case "financeiro":
                    background = "#FEEFB7";
                    break;
                case "auditoria":
                    background = "#B29C88";
                    break;
                case "sair":
                    background = "#FF9886";
                    break;
            }

            if (background != false) {
                $("td.borda").css({
                    "background-image": "-moz-linear-gradient(right, "+background+", #FFF)"
                });

                $("td.borda").css({
                    "background-image": "-webkit-linear-gradient(right, "+background+", #FFF)"
                });

                $("td.borda").css({
                    "background-image": "-o-linear-gradient(right, "+background+", #FFF)"
                });

                $("td.borda").css({
                    "background-image": "-ms-linear-gradient(right, "+background+", #FFF)"
                });

                $("td.borda").css({
                    "background-image": "linear-gradient(right, "+background+", #FFF)"
                });

                $("td.borda").css({
                    "filter": "progid:DXImageTransform.Microsoft.gradient(startColorstr='#FFFFFF', endColorstr='"+background+"', GradientType=1)"
                });
            }
        }

        $( "#tc_menu" ).tabs({
            //event: "mouseover",
            activate: function (event, ui) {
                var li      = $(ui.newTab);
                var liClass = getClass(li);

                if (liClass != false) {
                    toggleBackground(liClass);
                }
            },
            active: $("li.atual").attr("rel")
        });

        $(function () {

            $("#tc_menu").mouseleave(function () {
                $("#tc_menu").tabs("option", {"active" : $("li.atual").attr("rel")});
            });

            $("li.sair").mouseleave(function () {
                $("#tc_menu").tabs("option", {"active" : $("li.atual").attr("rel")});
            });


            $("#tc_menu > ul > li").mousedown(function (e) {
                var liClass = getClass(this);

                if (liClass != false) {
                    var url;

                    <?php
                    $bi_path = ($bi == "sim") ? "../" : "";
                    ?>

                    switch (liClass) {
                        case "gerencia":
                            url = "<?=$bi_path?>menu_gerencia.php";
                            break;

                        case "callcenter":
                            url = "<?=$bi_path?>menu_callcenter.php";
                            break;
                        case "contrato":
                            url = "<?=$bi_path?>menu_contrato.php";
                            break;

                        case "cadastro":
                            url = "<?=$bi_path?>menu_cadastro.php";
                            break;

                        case "infotecnica":
                            url = "<?=$bi_path?>menu_tecnica.php";
                            break;

                        case "financeiro":
                            url = "<?=$bi_path?>menu_financeiro.php";
                            break;

                        case "auditoria":
                            url = "<?=$bi_path?>menu_auditoria.php";
                            break;

                        case "sair":
                            url = "<?=$bi_path?>../admin/logout.php";
                            break;
                    }

                    if (url.length > 0) {
                        if (navigator.userAgent.match(/MSIE 8.0/gi)) {
                            if (e.button == 4) {
                                if (liClass == "sair") {
                                    window.location = url;
                                }

                                window.open(url, "_blank");
                            }

                            if (e.button == 1) {
                                window.location = url;
                            }
                        } else {
                            if (e.button == 1) {
                                if (liClass == "sair") {
                                    window.location = url;
                                }

                                window.open(url, "_blank");
                            }

                            if (e.button == 0) {
                                window.location = url;
                            }
                        }
                    }
                }
            });

            if ($("#helpdesk_pendencia").length > 0) {
                setInterval(function () {
                    if ($("#helpdesk_pendencia").is(":visible")) {
                        $("#helpdesk_pendencia").hide();
                    } else {
                        $("#helpdesk_pendencia").show();
                    }
                }, 800);
            }
        });
    </script>
<?
    if ($login_fabrica == "10") {
        $prefixo = 'adm_';
    }

    $sql = "SELECT COUNT(*)
            FROM tbl_hd_chamado
            WHERE admin = {$login_admin}
            AND (
                (exigir_resposta IS TRUE AND status <> 'Resolvido')
                OR
                (resolvido IS NULL AND status = 'Resolvido')
            )
            AND status <> 'Cancelado'
            AND fabrica_responsavel <> {$login_fabrica}";
    $resX = pg_query($con, $sql);

    $qtde_help = pg_fetch_result($resX, 0, 0);

    if (($qtde_help == 0 OR strlen ($qtde_help) == 0)) {


        if ($login_fabrica == 10) {
            $url = BI_BACK."../helpdesk/adm_chamado_lista_novo.php";
        } else {
            $url = BI_BACK."../helpdesk/{$prefixo}chamado_detalhe.php";
        }

        ?>
        <div id="helpdesk">
            <a href="<?=$url?>" target="_blank" >
                <center><img src="<?=$dir_help_desk_img?>/help.jpg" alt="HELP-DESK - Clique aqui para abrir um chamado no Suporte Telecontrol." /></center>
                <span>Help-Desk</span>
            </a>
        </div>
    <?php
    } else if ($qtde_help >= 1 ) {
        if ($qtde_help == 1) {
            $msg_help = "Você tem $qtde_help chamado pendente, aguardando sua resposta" ;
        } else {
            $msg_help = "Você tem $qtde_help chamados pendentes, aguardando sua resposta" ;
        }

        if (strlen($prefixo) > 0 or 1 == 1) {
            if ($login_fabrica == 10) {
                $urlAdc = "?atendente_busca={$login_admin}";
            }

            if ($login_admin == 432) {
                $url = BI_BACK."../helpdesk/{$prefixo}chamado_lista_novo.php{$urlAdc}";
            } else if ($login_admin <> 822) {
                $url = BI_BACK."../helpdesk/{$prefixo}chamado_lista.php{$urlAdc}";
            } else{
                $url = BI_BACK."../helpdesk/adm_atendimento_lista.php";
            }
            ?>

            <div id="helpdesk_pendencia" >Pendências</div>
            <div id="helpdesk">
                <a href="<?=$url?>" target="_blank" >
                    <center><img src="<?=$dir_help_desk_img?>/help-vermelho.jpg" alt="<?=$msg_help?>" title="<?=$msg_help?>" /></center>
                    <span>Help-Desk</span>
                </a>
            </div>
        <?php
        } else {
        ?>
            <div id="helpdesk_pendencia" >Pendências</div>
            <div id="helpdesk">
                <a href="<?=BI_BACK?>../helpdesk/<?=$prefixo?>chamado_lista.php?status=Análise&exigir_resposta=t&assist=assist" target='_blank' >
                    <center><img src="<?=$dir_help_desk_img?>/help-vermelho.jpg" alt="<?=$msg_help?>" title="<?=$msg_help?>" /></center>
                    <span>Help-Desk</span>
                </a>
            </div>
        <?php
        }
    }
}

$sql = "SELECT privilegios from tbl_admin where admin = $login_admin";
$res = pg_query($con,$sql);

$privilegios_adm = pg_fetch_result($res, 0, 0);

if ((($login_fabrica == 11) OR ($login_fabrica == 15))&& (strpos($privilegios_adm, "*") !== false || strpos($privilegios_adm, "call_center") !== false)) {
    if ($login_fabrica == 15) {
        include "pendencia_atendimento_retorno.php";
    }else {
        include "pendencia_atendimentos.php";
    }
}
?>
<iframe src="../admin/admin_personaliza.php" id='admCfgFrm' frameborder="0"
      style='width:555px;height:320px;position:absolute;z-index:200;top:30%;left:45%;display: none;'>
</iframe>
<table  class='no-print tc_container titulo_pagina' border='0' style='border: 0; position: relative; clear: both;' >
    <tr height="45" >
        <td style='font-size: 16px; font-weight: bold; font-family: arial;text-align: center; color:#FFFFFF; border-bottom-width:medium; border-bottom-style:solid;border-bottom-color:#E4E4E4;background-color:#596D9B;'> <? echo strtoupper($title) ?> </td>
    </tr>
</table>

<table width="850px"  border="0" align="center" cellpadding="0" cellspacing="0" bordercolor="#D9E2EF" style="margin:0 auto; padding-top: 4px; table-layout: fixed" class='no-print'>
<tr height="60">
    <?php
    $sql = "SELECT nome_completo, fone, email, dia_nascimento, mes_nascimento FROM tbl_admin WHERE fabrica = $login_fabrica AND admin = $login_admin";
    $res = pg_query($con,$sql);
        $msg_atualiza       = "";
        $iDisplay           = 'none';
        $bDisplay           = 'none';
    if (pg_num_rows($res) > 0) {
        $nome_admin         = pg_fetch_result($res, 0, nome_completo);
        $fone_admin         = pg_fetch_result($res, 0, fone);
        $email_admin        = pg_fetch_result($res, 0, email);
        $dia_nascimento     = pg_fetch_result($res, 0, dia_nascimento);
        $mes_nascimento     = pg_fetch_result($res, 0, mes_nascimento);
        $valida_email_admin = preg_match("/^[A-Za-z0-9._%-]+@([A-Za-z0-9.-]+){1,2}([.][A-Za-z]{2,4}){1,2}$/", $email_admin);

        if (!$nome_admin or !$fone_admin or !$email_admin or !$dia_nascimento or !$mes_nascimento or
            !$valida_email_admin) {
                if ($dia_nascimento and $mes_nascimento) {
                    $iDisplay = 'block';
                    $bDisplay = 'inline';
                }
                $msg_atualiza = (!$valida_email_admin) ? "O e-mail cadastrado<br />não é válido. Por favor, informe o e-mail correto."
                                                       : "O sistema detectou que <br />alguns dados seus estão desatualizados.";
        }
    }

    function escreveData($data) {
        $vardia = substr($data, 8, 2);
        $varmes = substr($data, 5, 2);
        $varano = substr($data, 0, 4);

        $convertedia = date ("w", mktime (0,0,0,$varmes,$vardia,$varano));

        $diaSemana = array("Domingo", "Segunda-Feira", "Terça-Feira", "Quarta-Feira", "Quinta-Feira", "Sexta-Feira", "Sábado");

        $mes = array(1=>"Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

        if ($varmes < 10) $varmes = substr($varmes,1,1);

        return $diaSemana[$convertedia] . ", " . $vardia  . " de " . $mes[$varmes] . " de " . $varano;
    }

    // Utilizar da seguinte maneira
    //echo escreveData("2005-12-02");?>
    <td>
    <div style='float:left;padding-top:1px;padding-bottom:1px;'>
<?php

        // Logos cabeçalho admin. //
        $imagensLogo = include('../logos.inc.php');
        $login_fabrica_logo = getFabricaLogo($login_fabrica, $imagensLogo);

        switch ($login_fabrica) {
            case  74: $url_logo = "$path_logo/atlas_saa_anim.gif"    ; break;
            case  81: $url_logo = "$path_logo/bestway_anim_admin.gif"; break;
            case 104: $url_logo = "$path_logo/logo_vonder_adm.jpg";    break;
            default:  $url_logo = "$path_logo/$login_fabrica_logo";    break;
        }
        switch ($login_login) {
            case 'suggar': $url_logo = "$path_logo/suggar.jpg";      break;
        }
         switch ($login_admin) {
            case   '57': $url_logo = "$path_logo/telecontrol_new.gif"; break;
            //case '1097': $url_logo = "$path_logo/telecontrol_new.gif"; break;
        }
        if ($login_fabrica == 46 and $AWS_sdk_OK) {
            include_once AWS_SDK;
            $s3logo   = new AmazonS3();
            if (is_object($s3logo)) {
                $logoS3 = 'logos/' . basename($url_logo);
                $bucket = 'br.com.telecontrol.posvenda-downloads';
                $url_logo = ($usaLogoS3 = $s3logo->if_object_exists($bucket, $logoS3)) ? $s3logo->get_object_url($bucket, $logoS3) : $url_logo;
            }
        }
        if ($usaLogoS3 or file_exists($url_logo)) {
            echo "<img src='$url_logo' alt='$login_fabrica_site' $onclick border='0' style='max-height:55px;max-width:240px;' />";
        } else {
            if ($altera_logo_TcNet) {
                echo "<a href='javascript:toggleCustomizePopUp(\"admLogoTCNet\")'>Alterar Logo<img src='$url_logo' alt='$login_fabrica_site' onclick='toggleCustomizePopUp(\"admLogoTCNet\")' border='0'$logo_attr /></a>";
            } else {
                echo "<a href='$login_fabrica_site' target='_new'>$login_fabrica_nome</a>\n";
            }
        }
    $ano_tc = date('Y');
?>
    </div>
    </td>
    <td align='center'>&nbsp;</td>
    <td style="font-size: 12px; font-family: arial;text-align:right;font-weight:bold" class='borda'>
<?php
    if (!$sem_menu) {
        $imagem_do_admin = $cook_avatar ? : "../imagens/sem_imagem.jpg' title='Clique aqui para subir sua foto!";
        echo "<img src='$imagem_do_admin' id='adm_foto' onClick='toggleCustomizePopUp(\"admCfgFrm\")' />\n";
    }
        $data = date("Y-m-d");
        echo escreveData($data);
        //echo date(" - H:i");
    if (!$sem_menu) {
        echo " <br /> Usuário: <span class='lbl lbl_usuario' style='cursor: help;color: red;' id='cfgUsr' onClick='toggleCustomizePopUp(\"admCfgFrm\")'>".ucfirst($login_login);
        if(strlen($msg_atualiza) > 0) {
            echo "&nbsp;&nbsp;<img src='" . BI_BACK . "../imagens/alerta2.gif' /><span class='tooltip'><span class='top'></span><span class='middle'>$msg_atualiza</span><span class='bottom'></span></span>";
        }
    } else {
        echo " <br /> Usuário: <span style='color:red;cursor:help' id='cfgUsr' >".ucfirst($login_login);
    }
        echo "</span>";
?>
    </td><!-- HD 205958: Help nas telas dos programas --><?php
    if ($tbl_help_help) {
        echo '<td>';
            echo "<img src='" . BI_BACK . "imagens/help.jpg' title=\"Clique aqui para ajuda sobre este programa\" onclick=\"window.frames.iframe_tbl_help.abre_help_tbl_help($tbl_help_help); document.getElementById('iframe_tbl_help').style.display='block';\" style=\"cursor:pointer;\">";
        echo '</td>';
    }?>
</tr><?php

if (($login_fabrica == 11 or $login_fabrica == 30) and ($title=='Cadastros do Sistema' or $title=='MENU GERÊNCIA')) {

    $sql = "SELECT log_integracao from tbl_log_integracao where fabrica = $login_fabrica and confirmar_leitura = 'f'";
    $res = pg_query($con,$sql);

    if (pg_num_rows($res) > 0) {
        $texto = "<div class='alert alert-error' style='margin-bottom: 8px; margin-top: 8px;'><h4><a href='log_erro_integracao.php' style='color: #b94a48; text-decoration: none;'>Existem Erros de Integracao - Clique aqui para Visualizar</a></h4></div>";
    }

}

if ($login_fabrica == 43 or $login_fabrica == 66 or $login_fabrica == 14){
##1981,260,1941,1939,553,602,1940,1952,1806,1991,1992,1583,1995,1995,1997,1994,1896,1996,1628,1838,2019,2016,2013,2007,2017,2012,2008,2014,2018, 417, 1661, 2141 HD 175298, HD 188390, HD 190335
    if (in_array($login_admin,array(260,270,417,553,602,1164,1216,1279,1405,1516,1583,1628,1661,1796,1806,1838,1939,1940,1941,1952,1981,1991,1992,1994,1995,1996,1997,2007,2008,2011,2012,2013,2014,2016,2017,2018,2019,2023,2058,2138,2139,2141,2145,2663,3229,3210,3230,3231))) {

        /*
        Gera dois arrays:
            $admins     => contém os ids admin que o usuário tem acesso
            $fabricas   => contém os nomes das fábricas acessadas por cada admin
                       para acessar o nome de uma fabrica, use o ID dela
                       como índice. Ex: $fabrica[40]

        */

        $sql = "SELECT * FROM tbl_admin_igual WHERE admin=$login_admin OR admin_igual=$login_admin LIMIT 1";
        $res = pg_query($con, $sql);

        $admin_principal = pg_fetch_result($res, 0, admin);

        $sql = "SELECT * FROM tbl_admin_igual WHERE admin=$admin_principal";
        $res = pg_query($con, $sql);

        $admins = array();
        $admins[] = $admin_principal;
        for($i = 0; $i < pg_num_rows($res); $i++)
        {
            $admins[] = pg_fetch_result($res, $i, admin_igual);
        }
        $admins = implode(",", $admins);

        $sql =  "
          SELECT tbl_admin.admin, tbl_admin.fabrica, tbl_fabrica.nome
            FROM tbl_admin
            JOIN tbl_fabrica
           USING (fabrica)
           WHERE tbl_admin.admin IN ($admins)
             AND tbl_admin.admin <> $login_admin
                ";
        $res = pg_query($con, $sql);

        // $fabricas = array();

        ?>
        <tr style='font-size:11px;position:relative;top:-1.8em'>
            <td colspan='3'>Logar Como...&nbsp;&nbsp;<select name='logar_como' onChange='trocaFabrica(this.value);'>
                    <option>selecione</option><?php
                    for ($i = 0; $i < pg_num_rows($res); $i++) {
                        list($m_admin,$m_fabrica,$nome_fabrica) = pg_fetch_row($res, $i);
                        echo "<option value='$m_admin|$m_fabrica'>$nome_fabrica</option>";
                    }?>
                </select>
            </td>
        </tr><?php
    }
}

if (strlen(trim($texto)) > 0) {?>
    <tr>
        <td colspan=3><?echo $texto;?></td>
    </tr><?php
}

if (strlen(trim($msg_validade_cadastro)) > 0) {
    echo "<tr height='3'>";
    echo "<td align='center' colspan=3 bgcolor='red' style='border-top-width:medium;
    border-top-style:solid;border-top-color:#E4E4E4;'>$msg_validade_cadastro</td>";
    echo "</tr>";
} else {
    echo "<tr height='6'>";
    echo "<td align='center' colspan=3 bgcolor='#5A6D9C' style='border-top-width:medium;
    border-top-style:solid;border-top-color:#E4E4E4;'></td>";
    echo "</tr>";
}

/*******************************************************
 * Verifica se o e-mail é válido.                      *
 * Se não for, vai mostrar o iframe para ele cadastrar *
 * um e-mail válido.                                   *
 *******************************************************/
?>

</table>

<iframe src="../admin/logo_tcnet.php" id='admLogoTCNet' frameborder="0"
      style='width:666px;height:456px;position:fixed;z-index:200;top:22%;right:45%;background:transparent;overflow:hide;display:<?=$iDisplay?>'></iframe>
  <br />
<?php

#------------- Programa Restrito ------------------#
$sql = "SELECT privilegios from tbl_admin where admin = $login_admin";
$res = pg_query($con,$sql);

$privilegios_adm = pg_fetch_result($res, 0, 0);

if (strpos ($privilegios_adm,"*") === false) { // 1 - Usuário 'master'
    /* Define os ítens do menu...
     * HD 684194
     * - Consulta OS:       http://www.telecontrol.com.br/assist/admin/os_consulta_lite.php
     * - Consulta Pedidos:  http://www.telecontrol.com.br/assist/admin/pedido_parametros.php
     * - Abre Chamado:      http://www.telecontrol.com.br/assist/admin/callcenter_interativo_new.php
     * - Consulta Chamado:  http://www.telecontrol.com.br/assist/admin/callcenter_parametros_interativo.php
     * - Cadastrar pedido:  http://www.telecontrol.com.br/assist/admin/pedido_cadastro.php
     * - Consultar posto:   http://www.telecontrol.com.br/assist/admin/posto_consulta.php
     * - Vista Explodida e Comunicados (apenas visualizar, conforme esta na aba Call Center):
     *                      http://www.telecontrol.com.br/assist/admin/comunicado_produto_consulta.php
     */

    if ($login_fabrica == 91 and $admin_e_promotor_wanke) {
         $a_telas_promotor_wanke = array(
             'os_press',
             'posto_consulta',
             'pedido_cadastro',
             'menu_callcenter',
             'pedido_consulta',
             'os_consulta_lite',
             'pedido_parametros',
             'pedido_admin_consulta',
             'callcenter_interativo_new',
             'comunicado_produto_consulta',
             'callcenter_parametros_interativo',
             'callcenter_consulta_lite_interativo',
         );
        // Tela que está tentando acessar, sem extensão (assim, pode pegar _test _teste _685194, etc...)
        $pw_tela_atual = preg_replace('/_\d{6}/', '', basename($PHP_SELF, '.php'));

        //echo "Conferindo login Promotor...<br>".preg_replace('/_\d{6}/', '', basename($PHP_SELF, '.php'));
        if (!in_array($pw_tela_atual, $a_telas_promotor_wanke)) {
            echo "<p><hr><center><h1>*Sem permissão para acessar este programa</h1></center><p><hr>";
            exit;
        }
    } else {

        $sql = "  SELECT programa
                    FROM tbl_programa_restrito
                   WHERE tbl_programa_restrito.programa = '$PHP_SELF'
                     AND tbl_programa_restrito.fabrica  = $login_fabrica";
        $res = pg_query($con,$sql);


        if (pg_num_rows($res) > 0) {
            $programa = pg_fetch_result($res,0,programa); //HD 72857

            if($login_fabrica <> 3 OR ($login_fabrica == 3 AND $programa <> '/assist/admin/os_cadastro.php')){
                $sql = "SELECT programa
                        FROM   tbl_programa_restrito
                        JOIN   tbl_admin USING (admin)
                        WHERE  tbl_programa_restrito.programa = '$PHP_SELF'
                        AND    tbl_programa_restrito.admin    = $login_admin
                        AND    tbl_programa_restrito.fabrica  = $login_fabrica ";
                $res = pg_query($con,$sql);

                if (pg_num_rows($res) == 0) {
                    echo "<p><hr><center><h1>*Sem permissão para acessar este programa</h1></center><p><hr>";
                    exit;
                }
            }
        }
    }
}

// Permite bloquear ou desbloquear a aprovação de chamados de desenvolvimento, de acordo com a tbl_hd_janela
$sql_lock =  "SELECT hd_janela, data_final::DATE = CURRENT_DATE AS data_fim
                FROM tbl_hd_janela
               WHERE CURRENT_TIMESTAMP BETWEEN data_inicial AND data_final
                 AND (fabricas IS NULL OR fabricas @> ARRAY[$login_fabrica])";

$res_lock = pg_query($con, $sql_lock);
$libera_hds = false;// Vai que dá erro...

if (is_resource($res_lock)) {

    $libera_hds = (pg_num_rows($res_lock)>0); // Se a query devolve resultados, é porque tem janela aberta.
    if (pg_num_rows($res_lock)>0){

        $mostrarAvisoFim = (pg_fetch_result($res_lock, 0, 'data_fim')== 't');
    }
}

// echo array2table(array(
//      0=>array(
//          'libera_hds' => $libera_hds,
//          'Cookie'     => $_COOKIE['HDComunicadoJanela'],
//          'DataFim'    => pg_fetch_result($res_lock, 0, 'data_fim'),
//          'adminSup'   => array(
//              $login_admin,$login_login,var_export($login_supervisor, true)
//          )
//      )
//  ),'Teste');

if ($libera_hds and $login_supervisor and $_COOKIE['HDComunicadoJanela'] != 'ja_li') {

    if ($mostrarAvisoFim) {
        include "tc_comunicado_janela_fim.html";
    } else {
        include "tc_comunicado_janela.html";
    }

}

// Comunicado Final de Ano
$data_atual = date("d/m/Y");
$mostra_comunicado_fim_ano = "";

switch ($data_atual) {
    case "09/12/2013": $mostra_comunicado_fim_ano = "sim"; break; // segunda
    case "11/12/2013": $mostra_comunicado_fim_ano = "sim"; break; // quarta
    case "13/12/2013": $mostra_comunicado_fim_ano = "sim"; break; // sexta
    case "16/12/2013": $mostra_comunicado_fim_ano = "sim"; break; // segunda
    case "18/12/2013": $mostra_comunicado_fim_ano = "sim"; break; // quarta
    case "20/12/2013": $mostra_comunicado_fim_ano = "sim"; break; // sexta
    case "23/12/2013": $mostra_comunicado_fim_ano = "sim"; break; // segunda
    case "25/12/2013": $mostra_comunicado_fim_ano = "sim"; break; // quarta
    case "27/12/2013": $mostra_comunicado_fim_ano = "sim"; break; // sexta
    case "30/12/2013": $mostra_comunicado_fim_ano = "sim"; break; // segunda
    case "01/01/2014": $mostra_comunicado_fim_ano = "sim"; break; // quarta
}

$dados = $_COOKIE['ComunicadoFimAno_'.$login_admin];
if(strlen($dados) > 0){
    list($fabrica, $admin_fim_ano, $comunicado_fim_ano, $tempo) = explode("-", $dados);
}

if((strlen($dados) == 0 && $mostra_comunicado_fim_ano == "sim") || ($mostra_comunicado_fim_ano == "sim" && $comunicado_fim_ano != "ja_li" && $login_fabrica == $fabrica && $login_admin == $admin_fim_ano)){
    // include "tc_comunicado_janela_fim_ano.html";

    $plugins = array( "shadowbox" );

    include "plugin_loader.php";

    ?>

        <style>
            #comunicado_importante{
                padding: 10px;
            }

            #comunicado_importante h4{
                text-align: center;
            }
        </style>

        <script type="text/javascript">
        Shadowbox.init({
            // let's skip the automatic setup because we don't have any
            // properly configured link elements on the page
            skipSetup: true,
            modal: true
        });

        window.onload = function() {

            // open a welcome message as soon as the window loads
            Shadowbox.open({
                content:    '<div id="comunicado_importante">'+
                        '<h4 class="text-error">Comunicado Importante</h4> <hr class="featurette-divider">'+
                        'Prezado Cliente,'+
                        ' <br />'+
                        'Informamos que nosso atendimento de Suporte estará suspenso nos '+
                        'dias <strong>23/12</strong>, <strong>24/12</strong> e <strong>25/12</strong> para as comemorações de Natal, '+
                        'e nos dias <strong>30/12</strong>, <strong>31/12</strong> e <strong>01/01/2014</strong> para o Ano Novo.<br /> <br />'+
                        'O funcionamento do nosso sistema não sofrerá interrupções, e nossos analistas '+
                        'estarão trabalhando no monitoramento dos servidores, e atentos aos Chamados '+
                        'Urgentes em nosso sistema de Help-Desk e E-mail.'+
                        ' <br />'+
                        'Também teremos os seguintes números para contato telefônico, em caso de '+
                        'urgências: <br /> <br />'+
                        '<table style="width: 300px !important; padding: 5px;">'+
                        '<tr><td>(14) 98154-1375 </td><td> Suporte Celular</td></tr>'+
                        '<tr><td>(14) 3306-3226 </td><td> Suporte Fixo</td></tr>'+
                        '<tr><td>(14) 99779-5594 </td><td> Rodrigo</td></tr>'+
                        '<tr><td>(14) 98141-1021 </td><td> Ronaldo</td></tr>'+
                        '<tr><td>(14) 99655-6060 </td><td> Túlio</td></tr>'+
                        '<tr><td>(14) 98124-1597 </td><td> Valéria</td></tr>'+
                        '</table> <br />'+
                        'Voltaremos as nossas atividades normais nos dias 26/12 e 02/01/2014 a partir '+
                        'das 8 horas da manhã. '+
                        'Agradecemos a colaboração, e desejamos um Feliz Natal e Próspero Ano Novo.'+
                        '<br /> <br />'+
                        'Atenciosamente, <br /> <strong>Equipe Telecontrol</strong>'+
                    '</div>',
                player:     "html",
                title:      "Comunicado Importante",
                height:     600,
                width:      860,
                onClose: desfazerNuvemComunicadoFimAno()
            })

        };
        </script>

    <?php
}

// 4908987-comunicado_final_ano_2013_amarelo.png

//Mensagem que bloqueia o HelpDesk
$mlg_hoje = strtotime('now');

if ($mlg_hoje >= strtotime('02/01/2012 00:00:01') and $mlg_hoje < strtotime('02/01/2012 09:00:00'))
    include 'dropdown_mensagem_noHD.html';
if (strpos($PHP_SELF, 'menu_') > 0) {
    include BI_BACK . '../helpdesk/popup_anivs.php';
}

$comunicado_tc_obrigatorio = true;

/*
 * Comentado temporáriamente
 *
// Comunicado de leitura obrigatória sobre o novo HelpDesk e o chat
$sql_c_tc = "SELECT admin,data_confirmacao
               FROM tbl_comunicado_tc_leitura
              WHERE admin            = $login_admin
                AND comunicado_tc    = 1
                AND data_confirmacao > '2012-02-10'";
$res_c_tc = pg_query($con, $sql_c_tc);

if (pg_num_rows($res_c_tc) == 0 and file_exists('../tc_comunicado.php')) {
    include '../tc_comunicado.php';
    if ($comunicado_tc_obrigatorio) {
        include "rodape.php";
        exit();
    }
}
 **/

// Comunicado sobre o novo sistema de segurança, que permite apenas o uso do login para um único usuário ao mesmo tempo.
$sql_c_tc = "SELECT admin,data_confirmacao
               FROM tbl_comunicado_tc_leitura
              WHERE admin            = $login_admin
                AND comunicado_tc    = 2
                AND data_confirmacao > '2012-02-10'";
$res_c_tc = pg_query($con, $sql_c_tc);

if (pg_num_rows($res_c_tc) == 0 and file_exists('../tc_comunicado_login.php')) {
    include '../tc_comunicado_login.php';
    if ($comunicado_tc_obrigatorio) {
        include "rodape.php";
        exit();
    }
}

?>
<map name="calendario_tc">
    <area shape='rect' nohref title='Janela de abertura de chamados de desenvolvimento' alt='Janela de abertura de chamados de desenvolvimento' coords='243,70,362,83'></area>
    <area shape='rect' nohref title='Janela de abertura de chamados de desenvolvimento' alt='Janela de abertura de chamados de desenvolvimento' coords='471,70,591,83'></area>
    <area shape='rect' nohref title='Janela de abertura de chamados de desenvolvimento' alt='Janela de abertura de chamados de desenvolvimento' coords='700,83,820,97'></area>
    <area shape='rect' nohref title='Janela de abertura de chamados de desenvolvimento' alt='Janela de abertura de chamados de desenvolvimento' coords='12,230,134,242'></area>
    <area shape='rect' nohref title='Janela de abertura de chamados de desenvolvimento' alt='Janela de abertura de chamados de desenvolvimento' coords='243,229,362,244'></area>
    <area shape='rect' nohref title='Janela de abertura de chamados de desenvolvimento' alt='Janela de abertura de chamados de desenvolvimento' coords='472,230,591,243'></area>
    <area shape='rect' nohref title='Janela de abertura de chamados de desenvolvimento' alt='Janela de abertura de chamados de desenvolvimento' coords='700,230,820,243'></area>
    <area shape='rect' nohref title='Janela de abertura de chamados de desenvolvimento' alt='Janela de abertura de chamados de desenvolvimento' coords='13,402,133,417'></area>
    <area shape='rect' nohref title='Janela de abertura de chamados de desenvolvimento' alt='Janela de abertura de chamados de desenvolvimento' coords='243,376,362,388'></area>
    <area shape='rect' nohref title='Janela de abertura de chamados de desenvolvimento' alt='Janela de abertura de chamados de desenvolvimento' coords='472,389,590,402'></area>
    <area shape='rect' nohref title='Janela de abertura de chamados de desenvolvimento' alt='Janela de abertura de chamados de desenvolvimento' coords='701,388,771,402'></area>
    <area shape='rect' nohref title='Data Inicial para novos projetos' alt='Data Inicial para novos projetos' coords='700,70,724,84'></area>
    <area shape='rect' nohref title='Data final para novos projetos' alt='Data final para novos projetos' coords='290,428,313,443'></area>
    <area shape='rect' nohref title='Feriado Nacional' alt='Feriado Nacional' coords='266,96,290,109'></area>
    <area shape='rect' nohref title='Feriado Nacional' alt='Feriado Nacional' coords='796,69,819,84'></area>
    <area shape='rect' nohref title='Feriado Nacional' alt='Feriado Nacional' coords='37,214,60,228'></area>
    <area shape='rect' nohref title='Feriado Nacional' alt='Feriado Nacional' coords='110,388,132,401'></area>
    <area shape='rect' nohref title='Feriado Nacional' alt='Feriado Nacional' coords='337,388,362,402'></area>
    <area shape='rect' nohref title='Feriado Nacional' alt='Feriado Nacional' coords='568,374,591,389'></area>
    <area shape='rect' nohref title='Feriado Nacional' alt='Feriado Nacional' coords='544,401,567,416'></area>
    <area shape='rect' nohref title='Feriado Nacional' alt='Feriado Nacional' coords='724,427,749,442'></area>
    <area shape='poly' nohref title='Férias Área Comercial' alt='Férias Área Comercial' coords='702,415,701,428,750,428,750,442,820,443,820,416,701,416'></area>
    <area shape='rect' nohref title='Data final visita comercial' alt='Data final visita comercial' coords='701,403,723,417'></area>
    <area shape='rect' nohref title='Visitas e Treinamentos' alt='Visitas e Treinamentos' coords='243,111,315,124'></area>
    <area shape='rect' nohref title='Visitas e Treinamentos' alt='Visitas e Treinamentos' coords='472,110,591,124'></area>
    <area shape='rect' nohref title='Visitas e Treinamentos' alt='Visitas e Treinamentos' coords='700,111,820,124'></area>
    <area shape='rect' nohref title='Visitas e Treinamentos' alt='Visitas e Treinamentos' coords='12,269,110,283'></area>
    <area shape='rect' nohref title='Visitas e Treinamentos' alt='Visitas e Treinamentos' coords='339,214,361,231'></area>
    <area shape='rect' nohref title='Visitas e Treinamentos' alt='Visitas e Treinamentos' coords='242,270,362,283'></area>
    <area shape='rect' nohref title='Visitas e Treinamentos' alt='Visitas e Treinamentos' coords='471,270,590,284'></area>
    <area shape='rect' nohref title='Visitas e Treinamentos' alt='Visitas e Treinamentos' coords='700,270,821,284'></area>
    <area shape='rect' nohref title='Visitas e Treinamentos' alt='Visitas e Treinamentos' coords='13,429,133,444'></area>
    <area shape='rect' nohref title='Visitas e Treinamentos' alt='Visitas e Treinamentos' coords='243,416,361,430'></area>
    <area shape='rect' nohref title='Visitas e Treinamentos' alt='Visitas e Treinamentos' coords='471,430,591,443'></area>
    <area shape='rect' nohref title='Prazo final novos contratos' alt='Prazo final novos contratos' coords='544,56,567,70'></area>
    <area shape='rect' nohref title='Reunião Equipe. Sem Atendimento.' alt='Reunião Equipe. Sem Atendimento.' coords='289,56,313,71'></area>
    <area shape='rect' nohref title='Reunião Equipe. Sem Atendimento.' alt='Reunião Equipe. Sem Atendimento.' coords='569,56,590,69'></area>
    <area shape='rect' nohref title='Reunião Equipe. Sem Atendimento.' alt='Reunião Equipe. Sem Atendimento.' coords='727,70,747,84'></area>
    <area shape='rect' nohref title='Reunião Equipe. Sem Atendimento.' alt='Reunião Equipe. Sem Atendimento.' coords='84,214,108,230'></area>
    <area shape='rect' nohref title='Reunião Equipe. Sem Atendimento.' alt='Reunião Equipe. Sem Atendimento.' coords='771,214,796,231'></area>
    <area shape='rect' nohref title='Reunião Equipe. Sem Atendimento.' alt='Reunião Equipe. Sem Atendimento.' coords='13,388,37,403'></area>
    <area shape='rect' nohref title='Reunião Equipe. Sem Atendimento.' alt='Reunião Equipe. Sem Atendimento.' coords='243,390,267,401'></area>
    <area shape='rect' nohref title='Reunião Equipe. Sem Atendimento.' alt='Reunião Equipe. Sem Atendimento.' coords='544,375,566,389'></area>
    <area shape='rect' nohref title='Reunião Equipe. Sem Atendimento.' alt='Reunião Equipe. Sem Atendimento.' coords='773,388,796,402'></area>
    <area shape='rect' nohref title='Data Final alteração agenda semestral' alt='Data Final alteração agenda semestral' coords='472,98,495,111'></area>
    <area shape='rect' nohref title='Data Final alteração agenda semestral' alt='Data Final alteração agenda semestral' coords='40,388,59,405'></area>
    <area shape='rect' nohref title='Reunião distribuição desenvolvimento' alt='Reunião distribuição desenvolvimento' coords='472,85,494,99'></area>
    <area shape='rect' nohref title='Reunião distribuição desenvolvimento' alt='Reunião distribuição desenvolvimento' coords='700,99,723,110'></area>
    <area shape='rect' nohref title='Reunião distribuição desenvolvimento' alt='Reunião distribuição desenvolvimento' coords='13,245,37,256'></area>
    <area shape='rect' nohref title='Reunião distribuição desenvolvimento' alt='Reunião distribuição desenvolvimento' coords='243,244,266,256'></area>
    <area shape='rect' nohref title='Reunião distribuição desenvolvimento' alt='Reunião distribuição desenvolvimento' coords='471,244,494,256'></area>
    <area shape='rect' nohref title='Reunião distribuição desenvolvimento' alt='Reunião distribuição desenvolvimento' coords='699,245,724,255'></area>
    <area shape='rect' nohref title='Reunião distribuição desenvolvimento' alt='Reunião distribuição desenvolvimento' coords='14,418,36,431'></area>
    <area shape='rect' nohref title='Reunião distribuição desenvolvimento' alt='Reunião distribuição desenvolvimento' coords='269,390,288,402'></area>
    <area shape='rect' nohref title='Reunião distribuição desenvolvimento' alt='Reunião distribuição desenvolvimento' coords='472,403,495,416'></area>
    <area shape='rect' nohref title='Reunião distribuição desenvolvimento' alt='Reunião distribuição desenvolvimento' coords='798,388,819,402'></area>
    <!-- this map has been created with eleomap. http://dhost.info/eleomap/ -->
</map>
<script type="text/javascript">

    var idioma_verifica_servidor = "<?=$cook_idioma?>";
    function toJSON (data)   {
        return $.parseJSON(data);
    }

    function desfazerNuvemComunicadoFimAno(){
        // $('#nuvem').css({"display" : "none"});

        $.ajax({

            url: "<?php echo $_SERVER['PHP_SELF'] ?>",
            type: "POST",
            data: "ComunicadoFimAno=ja_li",
            complete: function(data){
                data = data.responseText;
                console.log("ok -> ja leu");
            }

        });

    }


</script>
<a href="#" class="scrollup">Scroll</a>

<?

ob_end_flush();



