<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';
include_once "../class/tdocs.class.php";
use Lojavirtual\Produto;
use Lojavirtual\Categoria;

$objCategoria = new Categoria();
$objKit       = new Produto();
$tDocs        = new TDocs($con, $login_fabrica);
$dadosKit     = $objKit->getKit();
$url_redir    = "<meta http-equiv=refresh content=\"0;URL=loja_b2b_kit_peca.php\">";



if ($_GET["ajax_ver_kit_peca"] == true) {

    $loja_b2b_kit_peca = $_GET["id"];

    $rowProdutos        = $objKit->getKit($loja_b2b_kit_peca);

    echo '
    <style>
        .base{padding:20px;font-family: arial;}
        .important{background:#fbeed5;color:#c09853;border:solid 1px #c09853;padding:20px}
        .titulo{padding:10px;text-align:center;font-weight:bold;color:#444444;background:#f5f5f5;border: solid 1px #dddddd;}
        .titulotr{background:#596d9b;color:#ffffff;font-weight:bold;}
        .titulotr td{padding:5px 15px;}
        .conteudo td{padding:5px 15px;}
        .tac{text-align:center;}
    </style>
    ';

    if ($rowProdutos["erro"] == true) {
        exit("<div class='base'><div class='important'>".$rowProdutos["msg"]."</div></div>");
    }

    if (count($rowProdutos["itens_kit"]) == 0) {
        exit("<div class='base'><div class='important'>Nenhum item cadastrado nesse kit.</div></div>");
    }

    $retorno .= '
        <div class="base">
        <div class="titulo" align="center">'.$rowProdutos["ref_peca"].' - '.$rowProdutos["nome_peca"].'</div>
        <table width="100%" border="0" cellspacing="1" cellpadding="1" >
            <tr class="titulotr">
                <td class="tac">Referência</td>
                <td>Descrição</td>
                <td class="tac">Quantidade</td>
                <td class="tac">Preço</td>
            </tr>';
    foreach ($rowProdutos["itens_kit"] as $key => $rows) {
        $color = ($key % 2 == 0) ? "#eeeeee" : "#ffffff";
        $retorno .= '
            <tr bgcolor="'.$color.'" class="conteudo">
                <td class="tac">'.$rows["referencia"].'</td>
                <td>'.$rows["descricao"].'</td>
                <td class="tac">'.$rows["qtde"].'</td>
                <td class="tac">R$ '.number_format($rows["preco_venda"], 2, '.', '').'</td>
            </tr>
        ';
    }
    $retorno .= '</table>
            </div>';
            
    exit($retorno);
}

if ($_GET["acao"] == "delete" && $_GET["loja_b2b_kit_peca"] > 0) {
    
    $loja_b2b_kit_peca = $_GET['loja_b2b_kit_peca'];

    if (empty($loja_b2b_kit_peca)) {
        $msg_erro["msg"][]    = "Kit não encontrado";
    }

    if (count($msg_erro) == 0) {

        $res = pg_query($con,"BEGIN");

        if (!empty($objKit)) {
            $retorno = $objKit->deleteKit($loja_b2b_kit_peca);
            if ($retorno["erro"]) {
                $msg_erro["msg"][] = $retorno["msn"];
            } else {
                $msg_sucesso["msg"][] = 'Kit removido com sucesso!';
            }
        }

        if (count($msg_erro) > 0) {
            $res = pg_query($con,"ROLLBACK");
            echo $url_redir;
        } else {
            $res = pg_query($con,"COMMIT");
            echo $url_redir;
        }
    }
}

if ($_GET["acao"] == "edit" && $_GET["loja_b2b_kit_peca"] > 0) {

    $loja_b2b_kit_peca  = $_GET['loja_b2b_kit_peca'];
    $rowProdutos        = $objKit->getKit($loja_b2b_kit_peca);
    $referencia         = $rowProdutos['ref_peca'];
    $nome               = $rowProdutos['nome_peca'];
    $categoria          = $rowProdutos['categoria'];
    $descricao          = $rowProdutos['descricao'];
    $disponivel         = $rowProdutos['disponibilidade_peca'];
    $destaque           = $rowProdutos['peca_destaque'];
    $itens_kit          = $rowProdutos['itens_kit'];
    $tipo_acao          = "edit";
}

if ($_POST["btn_acao"] == "submit" && $tipo_acao  == "edit") {

    $loja_b2b_kit_peca  = $_POST['loja_b2b_kit_peca'];
    $referencia         = $_POST['referencia'];
    $nome               = $_POST['nome'];
    $categoria          = $_POST['categoria'];
    $descricao          = $_POST['descricao'];
    $anexo              = $_POST['anexo'];
    $disponivel         = ($_POST['disponivel'] == 't') ? 't' : 'f';
    $destaque           = ($_POST['destaque'] == 't') ? 't' : 'f';
    $itens_kit          = $_POST['itens_kit'];

    foreach ($anexo as $key => $value) {
        if (empty($value)) {
            unset($anexo[$key]);
        }
    }

    if (strlen($loja_b2b_kit_peca) == 0) {
        $msg_erro["msg"][]    = "Kit não encontrado";
    }

    foreach ($itens_kit as $key => $value) {
        if (empty($value["peca_referencia"])) {
            unset($itens_kit[$key]);
        }
    }

    if (strlen($referencia) == 0) {
        $msg_erro["msg"][]    = "Preencha o campo Referência do Kit e/ou Descrição do Kit";
        $msg_erro["campos"][] = "referencia";
        $msg_erro["campos"][] = "nome";
    }

    if (strlen($categoria) == 0) {
        $msg_erro["msg"][]    = "Escolha uma Categoria";
        $msg_erro["campos"][] = "categoria";
    }
    foreach ($itens_kit as $key => $rows) {

        $dadosPeca = $objKit->get($rows["loja_b2b_peca"], null);
        if (empty($dadosPeca)) {

            $msg_erro["msg"][]    = "Peça ".$itens_kit["peca_referencia"]." - ".$itens_kit["peca_decricao"]." não encontrada";
            $msg_erro["campos"][] = "peca_referencia";

        }

    }

    if (count($msg_erro) == 0) {

        $res = pg_query($con,"BEGIN");
        $dataSave = array(
                "loja_b2b_kit_peca" => $loja_b2b_kit_peca,
                "referencia"  => $referencia,
                "nome"        => $nome,
                "categoria"   => $categoria,
                "descricao"   => $descricao,
                "disponivel"  => $disponivel,
                "destaque"    => $destaque,
                "itens_kit"   => $itens_kit
             );

        if (!empty($objKit)) {
            $retorno = $objKit->updateKit($dataSave);

            if ($retorno["erro"]) {
                $msg_erro["msg"][] = $retorno["msn"];
            } else {
                $msg_sucesso["msg"][] = 'Kit atualizado com sucesso!';
                if (!empty($anexo)) {
                    foreach ($anexo as $vAnexo) {
                        if (empty($vAnexo)) {
                            continue;
                        }
                        $dadosAnexo = json_decode($vAnexo, 1);
                        $anexoID = $tDocs->setDocumentReference($dadosAnexo, $retorno["loja_b2b_kit_peca"], "anexar", false, "lojapecakit");
                        if (!$anexoID) {
                            $msg_erro["msg"][] = 'Erro ao fazer upload!';
                        }
                    }
                }
            }
        }

        if (count($msg_erro) > 0) {
            $res = pg_query($con,"ROLLBACK");
            $referencia     = $_POST['referencia'];
            $nome           = $_POST['nome'];
            $categoria      = $_POST['categoria'];
            $descricao      = $_POST['descricao'];
            $disponivel     = $_POST['disponivel'];
            $destaque       = $_POST['destaque'];
            $itens_kit      = $_POST['itens_kit'];
            $anexo          = $_POST['anexo'];
        } else {
            $res = pg_query($con,"COMMIT");
            echo $url_redir;
        }
    }
}

if ($_POST["btn_acao"] == "submit" && $tipo_acao  != "edit") {

    $referencia         = $_POST['referencia'];
    $nome               = $_POST['nome'];
    $categoria          = $_POST['categoria'];
    $descricao          = $_POST['descricao'];
    $anexo              = $_POST['anexo'];
    $disponivel         = ($_POST['disponivel'] == 't') ? 't' : 'f';
    $destaque           = ($_POST['destaque'] == 't') ? 't' : 'f';
    $itens_kit          = $_POST['itens_kit'];

    if (count($itens_kit) == 0) {
        $msg_erro["msg"][]    = "Adicione os itens do Kit";
        $msg_erro["campos"][] = "referencia";
    } 
    foreach ($anexo as $key => $value) {
        if (empty($value)) {
            unset($anexo[$key]);
        }
    }
    foreach ($itens_kit as $key => $value) {
        if (empty($value["peca_referencia"])) {
            unset($itens_kit[$key]);
        }
    }

    if (strlen($referencia) == 0) {
        $msg_erro["msg"][]    = "Preencha o campo Referência do Kit e/ou Descrição do Kit";
        $msg_erro["campos"][] = "referencia";
        $msg_erro["campos"][] = "nome";
    }

    if (strlen($categoria) == 0) {
        $msg_erro["msg"][]    = "Escolha uma Categoria";
        $msg_erro["campos"][] = "categoria";
    }
    foreach ($itens_kit as $key => $rows) {

        $dadosPeca = $objKit->get($rows["loja_b2b_peca"], null);
        if (empty($dadosPeca)) {

            $msg_erro["msg"][]    = "Peça ".$itens_kit["peca_referencia"]." - ".$itens_kit["peca_decricao"]." não encontrada";
            $msg_erro["campos"][] = "peca_referencia";

        }

    }

    if (count($msg_erro) == 0) {

        $res = pg_query($con,"BEGIN");
        $dataSave = array(
                        "referencia"  => $referencia,
                        "nome"        => $nome,
                        "categoria"   => $categoria,
                        "descricao"   => $descricao,
                        "disponivel"  => $disponivel,
                        "destaque"    => $destaque,
                        "itens_kit"   => $itens_kit
                     );

        if (!empty($objKit)) {
            $retorno = $objKit->saveKit($dataSave);

            if ($retorno["erro"]) {
                $msg_erro["msg"][] = $retorno["msn"];
            } else {
                $msg_sucesso["msg"][] = 'Kit cadastrado com sucesso!';

                if (!empty($anexo)) {

                    foreach ($anexo as $vAnexo) {
                        if (empty($vAnexo)) {
                            continue;
                        }
                        $dadosAnexo = json_decode($vAnexo, 1);
                        $anexoID = $tDocs->setDocumentReference($dadosAnexo, $retorno["loja_b2b_kit_peca"], "anexar", false, "lojapecakit");
                        if (!$anexoID) {
                            $msg_erro["msg"][] = 'Erro ao fazer upload!';
                        }
                    }

                }
            }
        }

        if (count($msg_erro) > 0) {
            $res = pg_query($con,"ROLLBACK");
            $peca_referencia    = $_POST['peca_referencia'];
            $peca_descricao     = $_POST['peca_descricao'];
            $categoria          = $_POST['categoria'];
            $descricao          = $_POST['descricao'];
            $preco              = $_POST['preco'];
            $preco_promocional  = $_POST['preco_promocional'];
            //$qtde_estoque       = $_POST['qtde_estoque'];
            //$qtde_max_posto     = $_POST['qtde_max_posto'];
            $disponivel         = $_POST['disponivel'];
            $destaque           = $_POST['destaque'];
            $anexo              = $_POST['anexo'];
        } else {
            $res = pg_query($con,"COMMIT");
            echo $url_redir;
        }
    }
}

if ($_POST["ajax_anexo_upload"] == true) {
    $posicao = $_POST["anexo_posicao"];
    $chave   = $_POST["anexo_chave"];

    $arquivo = $_FILES["anexo_upload_{$posicao}"];

    $ext = strtolower(preg_replace("/.+\./", "", $arquivo["name"]));

    if (strlen($arquivo['tmp_name']) > 0) {

        if (!in_array($ext, array('png', 'jpg', 'jpeg', 'bmp', 'gif'))) {

            $retorno = array('error' => utf8_encode('Arquivo em formato inválido, são aceitos os seguintes formatos: png, jpeg, bmp, gif'),'posicao' => $posicao);

        } else {

            if ($_FILES["anexo_upload_{$posicao}"]["tmp_name"]) {

                $anexoID      = $tDocs->sendFile($_FILES["anexo_upload_{$posicao}"]);
                $arquivo_nome = json_encode($tDocs->sentData);

                if (!$anexoID) {
                    $retorno = array('error' => utf8_encode('Erro ao anexar arquivo'),'posicao' => $posicao);
                } 

            }

            if (empty($anexoID)) {
                $retorno = array('error' => utf8_encode('Erro ao anexar arquivo'),'posicao' => $posicao);
            }

            $link = '//api2.telecontrol.com.br/tdocs/document/id/'.$anexoID;
            $href = '//api2.telecontrol.com.br/tdocs/document/id/'.$anexoID;
            $tdocs_id = $anexoID;
            if (!strlen($link)) {
                $retorno = array('error' => utf8_encode(' 2'),'posicao' => $posicao);
            } else {
                $retorno = compact('link', 'arquivo_nome', 'href', 'ext', 'posicao','tdocs_id');
            }
        }
    } else {
        $retorno = array('error' => utf8_encode('Erro ao anexar arquivo'),'posicao' => $posicao);
    }

    exit(json_encode($retorno));
}

if ($_POST["ajax_remove_anexo"] == true) {
    $posicao    = $_POST["posicao"];
    $tdocs_id   = $_POST["tdocsid"];

    $tDocs->setContext('lojapecakit');

    $anexoID = $tDocs->deleteFileById($tdocs_id);

    if (!$anexoID) {
        $retorno = array('erro' => true, 'msg' => utf8_encode('Erro ao remover arquivo'),'posicao' => $posicao);
    }  else {

        $retorno = array('sucesso' => true, 'posicao' => $posicao);
    }
    exit(json_encode($retorno));
}

$layout_menu = "cadastro";
$title       = "Kit de Peças - Loja Virtual";
include 'cabecalho_new.php';

$plugins = array(
    "shadowbox",
    "dataTable",
    "price_format",
    "mask",
    "ckeditor",
    "autocomplete",
    "ajaxform",
    "fancyzoom",
    "multiselect"
);

include("plugin_loader.php");
?>
<script language="javascript">
    $(function() {
        Shadowbox.init();
        $.dataTableLoad("#tabelas");
        $.autocompleteLoad(Array("peca"), Array("peca"));
        CKEDITOR.replace("descricao", { enterMode: CKEDITOR.ENTER_BR});
        setupZoom();


        $(".preco").priceFormat({
            prefix: '',
            thousandsSeparator: '',
            centsSeparator: '.',
            centsLimit: 2
        });

        var contador  = 0;
        $(".btn-add-itens").click(function(){


            $("#itens_kit").append('<tr id="tr-'+contador+'">'+
                '<td class="tac">'+
                    '<div class="input-append">'+
                        '<input type="text" name="itens_kit['+contador+'][peca_referencia]" class="span9 peca_referencia_'+contador+'">'+
                        '<span data-contador="'+contador+'"  data-tipo="referencia" class="add-on lupa-peca"><i class="icon-search"></i></span>'+
                        '<input type="hidden" name="itens_kit['+contador+'][loja_b2b_peca]" class="loja_b2b_peca_'+contador+'" />'+
                    '</div>'+
                '</td>'+
                '<td class="tal">'+
                    '<div class="input-append">'+
                        '<input type="text" name="itens_kit['+contador+'][peca_decricao]" class="span12 peca_descricao_'+contador+'">'+
                        '<span data-contador="'+contador+'"  data-tipo="descricao" class="add-on lupa-peca"><i class="icon-search"></i></span>'+
                    '</div>'+
                '</td>'+
                '<td class="tac">'+
                    '<input class="span10" type="text" value="1" name="itens_kit['+contador+'][quantidade]">'+
                '</td>'+
                '<td class="tac">'+
                    '<div class="input-prepend">'+
                        '<span class="add-on">R$</span>'+
                        '<input class="span10 preco loja_peca_preco_'+contador+'" type="text" name="itens_kit['+contador+'][loja_peca_preco]">'+
                    '</div>'+
                '</td>'+
                '<td class="tac">'+
                    '<button type="button" title="Remover Item" data-contador="'+contador+'" class="btn btn-mini btn-remove-item btn-danger">'+
                        '<i class="icon-remove icon-white"></i>'+
                    '</button>'+
                '</td>'+
            '</tr>');
            contador++;
        });

        $(document).on("click", ".lupa-peca", function () {
            var tipo            = $(this).data("tipo");
            var posicao         = $(this).data("contador");
            var peca_referencia = $(".peca_referencia_"+posicao).val();
            var peca_descricao   = $(".peca_descricao_"+posicao).val();
            
            if (peca_referencia != '' || peca_descricao != '') {
                Shadowbox.open({
                    content: "lupa_b2b_peca.php?ajax_kit_peca=true&tipo="+tipo+"&peca_referencia="+peca_referencia+"&peca_descricao="+peca_descricao+"&posicao="+posicao,
                    player: "iframe",
                    title:  "Busca de Peças ",
                    width:  800,
                    height: 500
                });
            }

        }); 

        $(document).on("click", ".ver-itens-peca", function () {
            var posicao = $(this).data("posicao");
            var id      = $(this).data("id");
            
            if (id != '') {
                Shadowbox.open({
                    content: "loja_b2b_kit_peca.php?ajax_ver_kit_peca=true&id="+id+"&posicao="+posicao,
                    player: "iframe",
                    title:  "Ver Peças do Kit",
                    width:  800,
                    height: 500
                });
            }

        }); 


        $(document).on("click", ".btn-remove-item", function () {
            var posicao = $(this).data("contador");
            $("#tr-"+posicao).remove();
        }); 

        $("#desconto").priceFormat({
            prefix: '',
            thousandsSeparator: '',
            centsSeparator: '.',
            centsLimit: 2
        });
        $("div[id^=div_anexo_]").each(function(i) {
            var tdocs_id = $("#div_anexo_"+i).find(".btn-remover-anexo").data("tdocsid");
            if (tdocs_id != '' && tdocs_id != null && tdocs_id != undefined) {
                $("#div_anexo_"+i).find("button[name=anexar]").hide();
                $("#div_anexo_"+i).find(".btn-remover-anexo").show();
            } else {
                $("#div_anexo_"+i).find(".btn-remover-anexo").hide();
            }
        });

        /* REMOVE DE FOTOS */
        $(document).on("click", ".btn-remover-anexo", function () {
            var tdocsid = $(this).data("tdocsid");
            var posicao = $(this).data("posicao");

            if (tdocsid != '' && tdocsid != null && tdocsid != undefined) {

                $.ajax({
                    url: 'loja_b2b_kit_peca.php',
                    type: "POST",
                    dataType:"JSON",
                    data: { 
                        ajax_remove_anexo: true,
                        tdocsid: tdocsid,
                        posicao: posicao
                    }
                }).done(function(data) {
                    if (data.erro == true) {
                        alert(data.msg);
                        return false;
                    } else {
                        alert("Removido com sucesso.");
                        $("#div_anexo_"+data.posicao).find("img.anexo_loading").hide();
                        $("#div_anexo_"+data.posicao).find("button[name=anexar]").show();
                        $("#div_anexo_"+data.posicao).find(".btn-remover-anexo").hide();
                        $("#div_anexo_"+data.posicao).find(".btn-remover-anexo").data("tdocsid", "");
                        $("#div_anexo_"+data.posicao).find("input[rel=anexo]").val("");
                        $("#div_anexo_"+data.posicao).find("img.anexo_thumb").attr("src", "imagens/imagem_upload.png");
                    }
                });

            }

        });

        /* ANEXO DE FOTOS */
        $("input[name^=anexo_upload_]").change(function() {
            var i = $(this).parent("form").find("input[name=anexo_posicao]").val();

            $("#div_anexo_"+i).find("button[name=anexar]").hide();
            $("#div_anexo_"+i).find("img.anexo_thumb").hide();
            $("#div_anexo_"+i).find("img.anexo_loading").show();

            $(this).parent("form").submit();
        });

        $("button[name=anexar]").click(function() {
            var posicao = $(this).attr("rel");
            $("input[name=anexo_upload_"+posicao+"]").click();
        });

        $("form[name=form_anexo]").ajaxForm({
            complete: function(data) {
                data = $.parseJSON(data.responseText);
            if (data.error) {
                alert(data.error);
                $("#div_anexo_"+data.posicao).find("img.anexo_loading").hide();
                $("#div_anexo_"+data.posicao).find("button[name=anexar]").show();
                $("#div_anexo_"+data.posicao).find("img.anexo_thumb").show();
            } else {
                var imagem = $("#div_anexo_"+data.posicao).find("img.anexo_thumb").clone();
                $(imagem).attr({ src: data.link });

                $("#div_anexo_"+data.posicao).find("img.anexo_thumb").remove();

                var link = $("<a></a>", {
                    href: data.href,
                    target: "_blank"
                });

                $(link).html(imagem);

                $("#div_anexo_"+data.posicao).prepend(link);

                setupZoom();

                $("#div_anexo_"+data.posicao).find("input[rel=anexo]").val(data.arquivo_nome);
            }

            $("#div_anexo_"+data.posicao).find("img.anexo_loading").hide();
            $("#div_anexo_"+data.posicao).find("button[name=anexar]").hide();
            $("#div_anexo_"+data.posicao).find(".btn-remover-anexo").show();
            $("#div_anexo_"+data.posicao).find(".btn-remover-anexo").data("tdocsid", data.tdocs_id);
            $("#div_anexo_"+data.posicao).find("img.anexo_thumb").show();
        }
        /* FIM ANEXO DE FOTOS */    

    });

    });
    function retorna_peca(retorno){
        $('.loja_b2b_peca_'+retorno.posicao).val(retorno.loja_b2b_peca);
        $('.peca_referencia_'+retorno.posicao).val(retorno.referencia);
        $('.peca_descricao_'+retorno.posicao).val(retorno.descricao);
        $('.loja_peca_preco_'+retorno.posicao).val(retorno.preco);
    }
</script>
<style>
    #cke_descricao{
        width: 100% !important;
    }
    .icon-edit {
        background-position: -95px -75px;
    }
    .icon-remove {
        background-position: -312px -3px;
    }
    .icon-search {
        background-position: -48px -1px;
    }
    .table th, .table td {
        vertical-align: middle !important;
    }
    .tabela_itens{
        border-color: #eee;
        background-color: #ffffff;
    }
    .tabela_itens tr td{
        padding:5px;
    }
</style>
    <?php
        if (empty($objKit->_loja)) {
            exit('<div class="alert alert-error"><h4>Loja não encontrada.</h4></div>');
        }
    ?>
    <?php if (count($msg_erro["msg"]) > 0) {?>
        <div class="alert alert-error">
            <h4><?php echo implode("<br />", $msg_erro["msg"]);?></h4>
        </div>
    <?php }?>

    <?php if (count($msg_sucesso["msg"]) > 0){?>
        <div class="alert alert-success">
            <h4><?php echo implode("<br />", $msg_sucesso["msg"]);?></h4>
        </div>
    <?php }?>

    <div class="row">
        <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
    </div>

    <form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
        <?php 
            if ($tipo_acao == "edit") {
        ?>
        <input type="hidden" name="tipo_acao" value="edit">
        <input type="hidden" name="loja_b2b_kit_peca" value="<?php echo $loja_b2b_kit_peca;?>">
        <?php } else {?> 
        <input type="hidden" name="tipo_acao" value="add">
        <?php }?> 
        <div class='titulo_tabela '>Cadastro</div>
        <br/>
        <div class="row-fluid">
            <div class="span2"></div>
            <div class="span3">
                <div class='control-group <?=(in_array("referencia", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class="control-label" for="referencia">Referência do Kit</label>
                    <div class="controls controls-row">
                        <div class="span12">
                            <h5 class='asteristico'>*</h5>
                            <input type="text" value="<?php echo $referencia;?>" id="referencia" name="referencia" class="span9">
                        </div>
                    </div>
                </div>
            </div>
            <div class='span5'>
                <div class='control-group <?=(in_array("nome", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='nome'>Descrição do Kit</label>
                    <div class="controls controls-row">
                        <div class="span12">
                            <h5 class='asteristico'>*</h5>
                            <input type="text" value="<?php echo $nome;?>" name="nome" id="nome" class="span12">
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span4'>
                <div class='control-group <?=(in_array("categoria", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label'>Categoria</label>
                    <div class='controls controls-row'>
                        <div class='span11'>
                            <h5 class='asteristico'>*</h5>
                            <select name="categoria" class='span12' id="categoria">
                                <option value="" selected="selected"> - Escolha uma categoria - </option>
                                <?php 
                                    $categoriasBusca = $objCategoria->get();
                                    if (!empty($categoriasBusca)) {
                                        foreach ($categoriasBusca as $vCat) {
                                            $selected = ($categoria == $vCat['categoria']) ? 'selected' : '';
                                ?>
                                <option value="<?php echo $vCat['categoria'];?>" <?php echo $selected;?>><?php echo $vCat['descricao'];?></option>
                                <?php  }
                                    }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'>
                <div class='control-group'>
                    <label class='control-label'>Disponível na Loja</label>
                    <div class='controls controls-row'>
                        <div class='span10'>
                            <input type="checkbox" name="disponivel" <?php echo ($disponivel == 't') ? 'checked' : '';?> value="t"> Sim
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'>
                <div class='control-group'>
                    <label class='control-label'>Destaque</label>
                    <div class='controls controls-row'>
                        <div class='span10'>
                            <input type="checkbox" name="destaque" <?php echo ($destaque == 't') ? 'checked' : '';?> value="t"> Sim
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span8'>
                <div class='control-group '>
                    <label class='control-label'>Descrição</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <textarea name="descricao" id="descricao" class="span12"><?php echo $descricao;?></textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>
        <hr>
        <br/>
        <div class='titulo_tabela '>Itens do Kit</div>
        <br/>
        <div class='row-fluid'>
            <div class='span1'></div>
            <div class='span10'>

                <table width="100%" class="tabela_itens" border="1" cellspacing="1" cellpadding="1" >
                    <tr class="titulo_tabela">
                        <td width="25%">Referência</td>
                        <td class="tal">Descrição</td>
                        <td width="10%">Quantidade</td>
                        <td width="20%">Valor Unitário</td>
                        <td width="5%">Ação</td>
                    </tr>
                    <?php 
                        if (count($itens_kit) > 0) {
                            foreach ($itens_kit as $key => $kit) {
                    ?>
                        <tr>
                            <td class="tac" width="25%"><?php echo $kit["referencia"];?></td>
                            <td class="tal"><?php echo $kit["descricao"];?></td>
                            <td class="tac" width="10%"><?php echo $kit["qtde"];?></td>
                            <td class="tac" width="20%"><?php echo number_format($kit["preco_venda"], 2, '.', '');?></td>
                            <td class="tac" width="5%"></td>
                        </tr>
                    <?php }}?>
                    <tbody id="itens_kit">
                        
                    </tbody>
                </table>
                <br>
                <div class="tac">
                    <button class="btn btn-small btn-add-itens btn-primary" type="button">Adicionar Item</button>
                </div>
                <br>
            </div>
            <div class='span1'></div>
        </div>
        <div class="titulo_tabela">Fotos do Kit</div><br />
        <div class='row-fluid'>
            <div class='span1'></div>
            <div class='span10'>
                <div class="row-fluid">
                    <div class="thumbnails">
                        <div class="span1"></div>
                        <?php 
                            $tDocs->setContext('lojapecakit');
                            $info = $tDocs->getDocumentsByRef($loja_b2b_kit_peca)->attachListInfo;
                            $pos  = 1;

                            if (count($info) > 0) {

                                foreach ($info as $k => $vAnexo) {
                                    $info[$k]["posicao"] = $pos++;
                                }

                            }

                            for ($i=1; $i <= 5 ; $i++) { 

                                $imagemAnexo = "imagens/imagem_upload.png";
                                $linkAnexo   = "#";
                                $tdocs_id   = "";

                                if ($loja_b2b_kit_peca > 0) {
                                    if (count($info) > 0) {

                                        foreach ($info as $k => $vAnexo) {

                                            if ($vAnexo["posicao"] != $i) {
                                                continue;
                                            }

                                            $linkAnexo   = $vAnexo["link"];
                                            $imagemAnexo = $vAnexo["link"];
                                            $tdocs_id = $vAnexo["tdocs_id"];

                                        }
                                    } 
                                }
                        ?>
                        <div id="div_anexo_<?=$i?>" class="tac" style="display: inline-block; margin: 0px 5px 0px 5px; vertical-align: top">
                            <?php if ($linkAnexo != "#") { ?>
                            <a href="<?=$linkAnexo?>" target="_blank" >
                            <?php } ?>
                                <img src="<?=$imagemAnexo?>" class="anexo_thumb" style="width: 100px; height: 90px;" />
                            <?php if ($linkAnexo != "#") { ?>
                            </a>

                            <script>setupZoom();</script>
                            <?php } ?>
                            <button type="button" style="display: none;" class="btn btn-mini btn-remover-anexo btn-danger btn-block" data-tdocsid="<?=$tdocs_id?>" data-posicao="<?=$i?>" >Remover</button>
                            <button type="button" class="btn btn-mini btn-primary btn-block" name="anexar" rel="<?=$i?>" >Anexar</button>
                            <img src="imagens/loading_img.gif" class="anexo_loading" style="width: 64px; height: 64px; display: none;" />
                            <input type="hidden" rel="anexo" name="anexo[<?=$i?>]" value="<?=$anexo[$i]?>" />
                        </div>
                        <?php } ?>
                        <div class="span1"></div>
                    </div>
                </div>
            </div>
            <div class='span1'></div>
        </div>
        <p><br/>
            <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Gravar</button>
            <input type='hidden' id="btn_click" name='btn_acao' value='' />
        </p><br/>
    </form> <br />

    <?php for ($i = 1; $i <=  5; $i++) {?>
        <form name="form_anexo" method="post" action="loja_b2b_kit_peca.php" enctype="multipart/form-data" style="display: none !important;" >
            <input type="file" name="anexo_upload_<?=$i?>" value="" />
            <input type="hidden" name="ajax_anexo_upload" value="t" />
            <input type="hidden" name="anexo_posicao" value="<?=$i?>" />
            <input type="hidden" name="anexo_chave" value="<?=$anexo_chave?>" />
        </form>
    <?php }?>

    <?php
        if ($dadosProduto["erro"]) {
            echo '<div class="alert alert-error"><h4>'.$dadosProduto["msn"].'</h4></div>';
        } else {
        $tDocs2 = new TDocs($con, $login_fabrica);
    ?>
</div>
    <table class='table table-striped table-bordered table-hover table-fixed' id='tabelas'>
        <thead>
            <tr class='titulo_coluna' >
                <th class='tac'>Referência do Kit</th>
                <th class='tal'>Nome do Kit</th>
                <th class='tal'>Categoria</th>
                <th class='tac'>Destaque</th>
                <th class='tac'>Disponivel</th>
                <th class='tac'>Peças do Kit</th>
                <th class='tac' width="10%">Ações</th>
            </tr>
        </thead>
        <tbody>
        <?php
            foreach ($dadosKit as $key => $rowsProduto) {
        ?>
        <tr>
            <td class='tac'><?php echo $rowsProduto["ref_peca"];?></td>
            <td class='tal'><?php echo $rowsProduto["nome_peca"];?></td>
            <td class='tal'><?php echo $rowsProduto["nome_categoria"];?></td>
            <td class='tac'>
                <?php echo ($rowsProduto["peca_destaque"] == 't') ? '<span class="label label-success">Sim</span>' : '<span class="label label-important">Não</span>';?>
            </td>
            <td class='tac'>
                <?php echo ($rowsProduto["disponibilidade_peca"] == 't') ? '<span class="label label-success">Sim</span>' : '<span class="label label-important">Não</span>';?>
            </td>
            <td class='tac'>
                <button type="button" data-id="<?php echo $rowsProduto["loja_b2b_kit_peca"];?>" data-posicao="<?php echo $key;?>" class="btn btn-info ver-itens-peca btn-mini" title="Ver peças do kit"><i class="icon-search icon-white"></i> Ver peças</button>
            </td>
            <td class='tac'>
                <a href="loja_b2b_kit_peca.php?acao=edit&loja_b2b_kit_peca=<?php echo $rowsProduto["loja_b2b_kit_peca"];?>" class="btn btn-info btn-mini" title="Editar"><i class="icon-edit icon-white"></i></a>
                <a onclick="if (confirm('Deseja remover este registro?')) window.location='loja_b2b_kit_peca.php?acao=delete&loja_b2b_kit_peca=<?php echo $rowsProduto["loja_b2b_kit_peca"];?>';return false;" href="#"  class="btn btn-danger btn-mini" title="Remover"><i class="icon-remove icon-white"></i></a>
            </td>
        </tr>
        <?php }?>
        </tbody>
    </table>
<?php }?>
<?php include 'rodape.php';?>