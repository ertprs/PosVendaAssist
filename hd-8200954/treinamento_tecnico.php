<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
include 'funcoes.php';
include 'ajax_cabecalho.php';
include 'autentica_usuario.php';

$layout_menu = "tecnica";
$title       = "Treinamento";

if (in_array($login_fabrica, array(175))){
    if (isset($_GET['treinamento']) && isset($_GET['acao']) && $_GET['acao'] == 'descricao'){
        $treinamento_id = $_GET['treinamento'];
        $sql            = "SELECT descricao, titulo 
                        FROM tbl_treinamento
                        WHERE treinamento  = {$treinamento_id}
                            AND fabrica    = {$login_fabrica}";
        $res         = pg_query($con,$sql);
        $msg_erro    = pg_last_error($con);

        if (empty($msg_erro)){
            $descricao      = pg_fetch_result($res,0,'descricao');
            $titulo         = pg_fetch_result($res,0,'titulo');
                $resposta  .=  "<table border='0' cellpadding='1' cellspacing='1' class='table table-striped table-fixed'  align='center' width='700px' style='color: #FFF;'>";
                $resposta  .=  "<thead>";
                    $resposta  .=  "<TR class='titulo_coluna'  height='25'>";
                        $resposta  .=  "<th style='background-color: #373865;'>".$titulo."</th>";
                    $resposta  .=  "</TR>";
                $resposta  .=  "</table>";   
                $resposta  .=  "<table border='0' cellpadding='1' cellspacing='1' class='table table-striped table-fixed'  align='center' width='700px' style='color: #FFF;'>";
                $resposta  .=  "<thead>";
                    $resposta  .=  "<TR class='titulo_coluna'  height='25'>";
                        $resposta  .=  "<th style='background-color: #373865;' align='left'>Descrição do Treinamento</th>";
                    $resposta  .=  "</TR>";
                $resposta  .=  "</thead>";
                $resposta  .= "<tbody>";
                    $resposta  .= "<TD align='left' style='color: #545b64; background-color: #f6f9fe;'>".$descricao."</TD>";
                $resposta  .= "</tbody>";
            $resposta .= "</table>";

            echo $resposta;
            exit;
        }
    }
}

include "cabecalho.php";

$plugins = array(
    "shadowbox",
    "mask",
    "select2",
);
include "plugin_loader.php";
?>
<style type="text/css">
.Titulo {
    text-align: center;
    font-family: Verdana;
    font-size: 14px;
    font-weight: bold;
    color: #FFFFFF;
    background-color: #485989;
}
.Titulo2 {
    text-align: center;
    font-family: Verdana;
    font-size: 12px;
    font-weight: bold;
    color: #FFFFFF;
    background-color: #485989;
}
.Conteudo {
    font-family: Arial;
    font-size: 8pt;
    font-weight: normal;
}
.Conteudo2 {
    font-family: Arial;
    font-size: 10pt;
}
.Caixa{
    BORDER-RIGHT: #6699CC 1px solid;
    BORDER-TOP: #6699CC 1px solid;
    FONT: 8pt Arial ;
    BORDER-LEFT: #6699CC 1px solid;
    BORDER-BOTTOM: #6699CC 1px solid;
    BACKGROUND-COLOR: #FFFFFF;
}
.Caixa2{
    BORDER-RIGHT: #6699CC 1px solid;
    BORDER-TOP: #6699CC 1px solid;
    FONT: 7pt Arial ;
    BORDER-LEFT: #6699CC 1px solid;
    BORDER-BOTTOM: #6699CC 1px solid;
    BACKGROUND-COLOR: #FFFFFF;
}
.Botao1{
    BORDER-RIGHT:  #6699CC 1px solid;
    BORDER-TOP:    #6699CC 1px solid;
    BORDER-LEFT:   #6699CC 1px solid;
    BORDER-BOTTOM: #6699CC 1px solid;
    FONT:             10pt Arial ;
    FONT-WEIGHT:      bold;
    COLOR:            #009900;
    BACKGROUND-COLOR: #EEEEEE;
}
.Botao2{
    BORDER-RIGHT:  #6699CC 1px solid;
    BORDER-TOP:    #6699CC 1px solid;
    BORDER-LEFT:   #6699CC 1px solid;
    BORDER-BOTTOM: #6699CC 1px solid;
    FONT:             10pt Arial;
    FONT-WEIGHT:      bold;
    COLOR:            #990000;
    BACKGROUND-COLOR: #EEEEEE;
}
.Erro{
    BORDER-RIGHT: #990000 1px solid;
    BORDER-TOP: #990000 1px solid;
    FONT: 10pt Arial ;
    COLOR: #ffffff;
    BORDER-LEFT: #990000 1px solid;
    BORDER-BOTTOM: #990000 1px solid;
    BACKGROUND-COLOR: #FF0000;
}
.banner_makita{
    width: 200px;
    height: auto;
    position: absolute;
    top: 130px;
    right: 10px;
}
.btn-inscreva-tecnico {
    BORDER: #bd362f 1px solid;
    FONT: 10pt Arial;
    FONT-WEIGHT: bold;
    COLOR: #ffffff;
    BACKGROUND-COLOR: #da4f49;
    cursor: pointer;
    padding: 10px;
}
.btn-inscreva-tecnico:hover {
    BORDER: #da4f49 1px solid;
    FONT: 10pt Arial;
    FONT-WEIGHT: bold;
    COLOR: #ffffff;
    BACKGROUND-COLOR: #bd362f;
    cursor: pointer;
}
.btn-upload-tecnico {
    BORDER: #2f96b4 1px solid;
    FONT: 10pt Arial;
    FONT-WEIGHT: bold;
    COLOR: #ffffff;
    BACKGROUND-COLOR: #49afcd;
    cursor: pointer;
    padding: 10px;
}
.btn-upload-tecnico:hover {
    BORDER: #49afcd 1px solid;
    FONT: 10pt Arial;
    FONT-WEIGHT: bold;
    COLOR: #ffffff;
    BACKGROUND-COLOR: #2f96b4;
    cursor: pointer;
}
.label-warning{
    color: #856404;
}
.label-info{
    color: #3a87ad;
}
.label-default{
    color: #1b1e21;
}
.label-success{
    color: #468847;
}
.label-important, .label-danger{
    color: #ff6666;
}
</style>

<script type="text/javascript" src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script src='plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.datepicker.min.js'></script>
<script src='plugins/shadowbox_lupa/shadowbox.js'></script>
<link rel='stylesheet' type='text/css' href='plugins/shadowbox_lupa/shadowbox.css' /> 

<script type="text/javascript">
$(function(){
    Shadowbox.init(); 

    $(document).on('click', 'a.iframe', function(){
        var url = $(this).data('url');
        Shadowbox.open({
            content: url,
            player: 'iframe',
            width: 1024,
            height: 600
        });
    });

    $(document).on('click', 'a.show_descricao', function(){
        var url = $(this).data('url');
        Shadowbox.open({
            content: url,
            player: 'iframe',
            width: 700,
            height: 320
        });
    });

    // ação btn realiza inscrição
    $(document).on('click', '.realizar_inscricao', function(){
        var id_tecnico     = $(this).data('tecnico');
        var id_treinamento = $(this).data('treinamento');
        var online         = $(this).data('isOnline');
        var id_posto       = <?=$login_posto;?>
    
        $.ajax({
            method: "GET",
            url: "ajax_treinamento_tecnico.php",
            data: { ajax: 'sim', acao: 'cadastrar_tecnico', isOnline: online, treinamento: id_treinamento, posto: id_posto, tecnico: id_tecnico},
            timeout: 8000
        }).done(function(data) {
            data = JSON.parse(data);
            if (data.ok !== undefined) {
                if (!alert(data.ok)){
                    window.location.reload();
                }
            }else{
                if (!alert(data.erro)){
                    window.location.reload();
                }
            }
        });
    });


    // ação btn remover inscrição
    $(document).on('click', '.remover_inscricao', function(){
        var id_tecnico     = $(this).data('tecnico');
        var id_treinamento = $(this).data('treinamento');
        var id_posto       = <?=$login_posto;?>
        
        $.ajax({
            method: "GET",
            url: "ajax_treinamento_tecnico.php",
            data: { ajax: 'sim', acao: 'remover_tecnico', treinamento: id_treinamento, posto: id_posto, tecnico: id_tecnico},
            timeout: 8000
        }).done(function(data) {
            data = JSON.parse(data);
            if (data.ok !== undefined) {
                if (!alert(data.ok)){
                    window.location.reload();
                }
                
            }else{
                if (!alert(data.erro)){
                    window.location.reload();
                }
                
            }
        });
    });

    // ação btn concluido
    $(document).on('click', '.ativa_desativa_participou', function(){
        var id_tecnico     = $(this).data('tecnico');
        var id_treinamento = $(this).data('treinamento');
        var id_posto       = <?=$login_posto;?>
        
        $.ajax({
            method: "GET",
            url: "ajax_treinamento_tecnico.php",
            data: { ajax: 'sim', acao: 'ativa_desativa_participou', treinamento: id_treinamento, posto: id_posto, tecnico: id_tecnico},
            timeout: 8000
        }).done(function(data) {
            data = JSON.parse(data);
            if (data.ok !== undefined) {
                if (!alert(data.ok)){
                    window.location.reload();
                }
            }else{
                if (!alert(data.erro)){
                    window.location.reload();   
                }
                
            }
        });
    });
});

function createRequestObject(){
    var request_;
    var browser = navigator.appName;
    if(browser == "Microsoft Internet Explorer"){
         request_ = new ActiveXObject("Microsoft.XMLHTTP");
    }else{
         request_ = new XMLHttpRequest();
    }
    return request_;
}

var http_forn = new Array();

function mostrar_treinamento(componente) {
    var com  = document.getElementById(componente);
    var acao = 'ver';

    url = "ajax_treinamento_tecnico.php?ajax=sim&acao="+acao;

    com.innerHTML   ="Carregando<br><img src='imagens/carregar2.gif'>";

    var curDateTime = new Date();
    http_forn[curDateTime] = createRequestObject();
    http_forn[curDateTime].open('GET',url,true);

    http_forn[curDateTime].onreadystatechange = function(){
        if (http_forn[curDateTime].readyState == 4)
        {
            if (http_forn[curDateTime].status == 200 || http_forn[curDateTime].status == 304)
            {
                var response = http_forn[curDateTime].responseText.split("|");
                if (response[0]=="ok"){
                    com.innerHTML   = response[1];
                }
                if (response[0]=="0"){
                    // posto ja cadastrado
                    alert(response[1]);
                }
                if (response[0]=="1"){
                    // dados incompletos
                    alert("Campos incompletos:\n\n"+response[1]);
                }
            }
        }
    }
    http_forn[curDateTime].send(null);
}
</script>
<?php
    echo "<div id='dados'></div>";
    echo "<script language='javascript'>mostrar_treinamento('dados');</script>";

    include "rodape.php"
?>