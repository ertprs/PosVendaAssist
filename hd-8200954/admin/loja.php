<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';
include_once "../class/tdocs.class.php";
use Lojavirtual\Loja;
$tDocs     = new TDocs($con, $login_fabrica);
$objLoja   = new Loja();
$dadosLoja = $objLoja->get();

if (empty($dadosLoja) || $dadosLoja["erro"] == true) {
    header("Location: menu_cadastro.php");
    exit;
}
$url_redir = "<meta http-equiv=refresh content=\"0;URL=loja.php\">";

if ($_POST) {
    $valor_pedido_minimo            = $_POST["valor_pedido_minimo"];
    $controla_estoque               = $_POST["controla_estoque"];
    $layout                         = $_POST["layout"];
    $xlayout                        = json_encode($_POST["layout"]);
    $dados["valor_pedido_minimo"]   = $valor_pedido_minimo;
    $dados["controla_estoque"]      = $controla_estoque;

    if (!empty($layout)) {
        $res = pg_query($con,"BEGIN");
        $retornoParametros = $objLoja->atualizaParamentrosAdicionais($dados);
        $retorno           = $objLoja->atualizaLayout($xlayout);
        
        if ($retorno["erro"] || $retornoParametros["erro"]) {
            $erro = true;
            $msg_erro["msg"][] = $retorno["msn"];
            $res  = pg_query($con,"ROLLBACK");
            echo $url_redir;
        } else {
            $sucesso = true;
            $res     = pg_query($con,"COMMIT");
            echo $url_redir;
        }
    }
}


if ($_POST["ajax_anexo_upload"] == true) {

    $chave   = $_POST["anexo_chave"];
    $arquivo = $_FILES["anexo_upload"];
    $ext = strtolower(preg_replace("/.+\./", "", $arquivo["name"]));

    if (strlen($arquivo['tmp_name']) > 0) {
        if (!in_array($ext, array('png', 'jpg', 'jpeg', 'bmp', 'gif'))) {
            $retorno = array('error' => utf8_encode('Arquivo em formato inválido, são aceitos os seguintes formatos: png, jpeg, bmp, gif'),'posicao' => $posicao);
        } else {

            if ($_FILES["anexo_upload"]["tmp_name"]) {
                $tDocs->setContext("loja", "lojalogo");
                $anexoID = $tDocs->uploadFileS3($_FILES["anexo_upload"], $dadosLoja["loja_b2b"], true, "", "");

                if (!$anexoID) {
                    $retorno = array('error' => utf8_encode('Erro ao anexar arquivo'));
                } 

            }
            $anexoID = $tDocs->getDocumentsByRef($dadosLoja["loja_b2b"]);
            if (empty($anexoID)) {
                $retorno = array('error' => utf8_encode('Erro ao anexar arquivo'));
            }

            $link = $anexoID->url;
            $href = $anexoID->url;
            if (!strlen($link)) {
                $retorno = array('error' => utf8_encode(' 2'));
            } else {
                $retorno = compact('link', 'arquivo_nome', 'href', 'ext');
            }
        }
    } else {
        $retorno = array('error' => utf8_encode('Erro ao anexar arquivo'));
    }

    exit(json_encode($retorno));
    
}

$layout_menu = "cadastro";
$title = "DADOS DA LOJA VIRTUAL";
include 'cabecalho_new.php';

$plugins = array(
    "shadowbox",
    "colorpicker",
    "ajaxform",
    "price_format",
    "mask",
    "multiselect"
);

include("plugin_loader.php");
?>

<script language="javascript">

    $(function() {

        Shadowbox.init();

        $(".multiple").multiselect({
           selectedText: "# of # selected"
        });
        $('.colorpicker').colorpicker();
        $('.ppop').popover({placement: 'bottom',  html: true});

        $('.btn-show-hide').click(function(){
            var targ = $(this).data('target');
            var posicao = $(this).data('posicao');
            if (targ == 'show') {
                $('.colls_'+posicao).collapse('show');
                $(this).data('target', 'hide');
            } else {
                $('.colls_'+posicao).collapse('hide');
                $(this).data('target', 'show')

            }
        });

        $(".valor").priceFormat({
            prefix: '',
            thousandsSeparator: '',
            centsSeparator: '.',
            centsLimit: 2
        });

        /* ANEXO DE LOGO */
        $("input[name=anexo_upload]").change(function() {

            $("#div_anexo").find("button").hide();
            $("#div_anexo").find("img.anexo_thumb").hide();
            $("#div_anexo").find("img.anexo_loading").show();

            $(this).parent("form").submit();
        });

        $("button[name=anexar]").click(function() {
            $("input[name=anexo_upload]").click();
        });

        $("form[name=form_anexo]").ajaxForm({
            complete: function(data) {
                data = $.parseJSON(data.responseText);

                if (data.error) {
                    alert(data.error);
                    $("#div_anexo").find("img.anexo_loading").hide();
                    $("#div_anexo").find("button").show();
                    $("#div_anexo").find("img.anexo_thumb").show();
                } else {
                    var imagem = $("#div_anexo").find("img.anexo_thumb").clone();
                    $(imagem).attr({ src: data.link });

                    $("#div_anexo").find("img.anexo_thumb").remove();

                    var link = $("<a></a>", {
                        href: data.href,
                        target: "_blank"
                    });

                    $(link).html(imagem);

                    $("#div_anexo").prepend(link);
                    $("#div_anexo").find("input[rel=anexo]").val(data.arquivo_nome);
                }

                $("#div_anexo").find("img.anexo_loading").hide();
                $("#div_anexo").find("button").show();
                $("#div_anexo").find("img.anexo_thumb").show();
            }
        });
        /* FIM ANEXO DE LOGO */

    });

</script>
<style>
    .oculta_div{
        display: none;
    }
    .titulo_ext{background-color: #337AB7;text-align: center;padding: 5px 0px;cursor: pointer;color: #fff}
    .text_all{
        background-color: #eeeeee;border: 1px solid #cccccc;
        -webkit-box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.075);
        -moz-box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.075);
        box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.075);
        -webkit-transition: border linear 0.2s, box-shadow linear 0.2s;
        -moz-transition: border linear 0.2s, box-shadow linear 0.2s;
        -o-transition: border linear 0.2s, box-shadow linear 0.2s;
        transition: border linear 0.2s, box-shadow linear 0.2s;
        padding-top: 10px;
    }
</style>

<?php if ($erro) {?>
    <div class="alert alert-error">
        <h4><?php echo implode("<br />", $msg_erro["msg"]);?></h4>
    </div>
<?php } ?>

<?php if ($sucesso) {?>
    <div class="alert alert-success">
        <h4>Dados da Loja atualizada com sucesso</h4>
    </div>
<?php }?>

<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<?php
    $externa   = ($dadosLoja["externa"] == 't')  ? '<span class="btn btn-info">Sim</span>'      : '<span class="btn btn-danger">Não</span>';
    $checkout  = ($dadosLoja["checkout"] == 't') ? '<span class="btn btn-info">B2B com Integração de Pagamento</span>' : '<span class="btn btn-danger">B2B sem Integração de Pagamento </span>';
    $ativo     = ($dadosLoja["ativo"] == 't')     ? '<span class="btn btn-success">Ativo</span>' : '<span class="btn btn-danger">Inativo</span>';
    $layout    = json_decode($dadosLoja["layout"], 1);
    $parametros_adicionais = json_decode($dadosLoja["parametros_adicionais"], 1);
    if (isset($parametros_adicionais["valor_pedido_minimo"])) {
        $valor_pedido_minimo = $parametros_adicionais["valor_pedido_minimo"];
    }
    if (isset($parametros_adicionais["controla_estoque"])) {
        $controla_estoque = $parametros_adicionais["controla_estoque"];
    }

?>
<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline' >

<div class="tc_formulario">
    
    <div class='titulo_tabela '>Dados Gerais</div>
    <br/>
    <div class='row-fluid'>
        <div class='span1'></div>
        <div class='span4'>
            <div class='control-group'>
                <label class='control-label'>Tipo de B2B</label>
                <div class='controls controls-row'>
                    <div class='span12'>
                        <?php echo $checkout;?>
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'>
            <div class='control-group tac'>
                <label class='control-label'>B2B Externo</label>
                <div class='controls controls-row'>
                    <div class='span12 tac'>
                        <?php echo $externa;?>
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'>
            <div class='control-group tac'>
                <label class='control-label'>Status do B2B</label>
                <div class='controls controls-row'>
                    <div class='span12 tac'>
                        <?php echo $ativo;?>
                    </div>
                </div>
            </div>
        </div>
        <div class='span1'></div>
    </div>

    <div class='row-fluid'>
        <div class='span1'></div>
        <div class='span2'>
            <div class='control-group <?=(in_array("descricao", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label'>Pedido minimo</label>
                <div class='controls controls-row'>
                    <div class='span10'>
                        <div class="input-prepend">
                            <span class="add-on">R$</span>
                            <input class="span12 valor" type="text" name="valor_pedido_minimo" value="<?=number_format($valor_pedido_minimo, 2, '.', '')?>" id="valor_pedido_minimo">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'>
            <div class='control-group <?=(in_array("controla_estoque", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label'>Controla Estoque</label>
                <div class='controls controls-row'>
                    <div class='span10'>
                        <select name="controla_estoque" id="controla_estoque">
                            <option value="">Selecione ...</option>
                            <option value="true" <?php echo ($controla_estoque == "true") ? "selected" : ""?>>Sim</option>
                            <option value="false" <?php echo ($controla_estoque == "false") ? "selected" : ""?>>Não</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class='span1'></div>
    </div>

    <br/><br/>
    <div class='titulo_tabela '>Layout da Loja</div><br/><br/>

    <div class='row-fluid'>
        <div class='span1'></div>
        <div class='span10'>

            <div class='row-fluid'>
                <div class='span1'></div>
                <div class='span10'>
                   <div class="row-fluid">
                        <div class="thumbnails">
                            <div class="span1"></div>
                            <div class="span10 tac">
                                <?php 
                                    $tDocs->setContext('lojalogo');
                                    $info = $tDocs->getDocumentsByRef($dadosLoja["loja_b2b"])->attachListInfo;
                                    $imagemAnexo = "imagens/imagem_upload.png";
                                    $dimensoes = "width: 100px; height: 90px;";

                                    foreach ($info as $k => $vAnexo) {
                                        $imagemAnexo = $vAnexo["link"];
                                        $dimensoes = "width: 180px; height: 90px;";
                                    }
                                ?>
                                <div id="div_anexo" class="tac" style="display: inline-block; margin: 0px 5px 0px 5px; vertical-align: top">
                                    <img src="<?=$imagemAnexo?>" class="anexo_thumb" style="<?php echo $dimensoes;?>">
                                    <button type="button" name="anexar" class="btn btn-mini btn-warning btn-block">Alterar Logomarca</button>
                                    <img src="imagens/loading_img.gif" class="anexo_loading" style="width: 64px; height: 64px; display: none;" />
                                    <input type="hidden" rel="anexo" name="anexo" value="<?=$anexo;?>" />
                                </div>
                            </div>
                            <div class='span1'></div>
                        </div>
                    </div>
                </div>
                <div class='span1'></div>
            </div>

                <h5 class="titulo_ext btn-show-hide" data-posicao="1" data-target="show">CABEÇALHO</h5>
                <div class="collapse colls_1">
                    <div class='row-fluid'>
                        <div class='span3'>
                            <div class='control-group'>
                                <label class='control-label'>Barra Top(fundo) 
                                </label>
                                <div class='controls controls-row'>
                                    <div class='span4'>
                                        <div class="input-append colorpicker colorpicker-component">
                                            <input class="span7" name="layout[eco_top_fundo]" id="eco_top_fundo" value="<?php echo $layout['eco_top_fundo'];?>" type="text">
                                            <span class="add-on input-group-addon"><i></i></span>
                                        </div>
                                    </div>  
                                </div>  
                            </div>
                        </div>
                        <div class='span3'>
                            <div class='control-group'>
                                <label class='control-label'>Barra Meio Top(fundo)
                                </label>
                                <div class='controls controls-row'>
                                    <div class='span4'>
                                        <div class="input-append colorpicker colorpicker-component">
                                            <input class="span7" name="layout[eco_info_login_fundo]" id="eco_info_login_fundo" value="<?php echo $layout['eco_info_login_fundo'];?>" type="text">
                                            <span class="add-on input-group-addon"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class='span3'>
                            <div class='control-group'>
                                <label class='control-label'>Barra Meio Top(cor)
                                </label>
                                <div class='controls controls-row'>
                                    <div class='span4'>
                                        <div class="input-append colorpicker colorpicker-component">
                                            <input class="span7" name="layout[eco_info_login_cor]" id="eco_info_login_cor" value="<?php echo $layout['eco_info_login_cor'];?>" type="text">
                                            <span class="add-on input-group-addon"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr> 
                    <div class='row-fluid'>
                        <div class='span3'>
                            <div class='control-group'>
                                <label class='control-label'>Botão OK (fundo)
                                </label>
                                <div class='controls controls-row'>
                                    <div class='span2'>
                                        <div class="input-append colorpicker colorpicker-component">
                                            <input class="span7" name="layout[eco_btn_busca_ok_fundo]" id="eco_btn_busca_ok_fundo" value="<?php echo $layout['eco_btn_busca_ok_fundo'];?>" type="text">
                                            <span class="add-on input-group-addon"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class='span3'>
                            <div class='control-group'>
                                <label class='control-label'>Botão OK (hover)
                                </label>
                                <div class='controls controls-row'>
                                    <div class='span2'>
                                        <div class="input-append colorpicker colorpicker-component">
                                            <input class="span7" id="eco_btn_busca_ok_hover" name="layout[eco_btn_busca_ok_hover]" value="<?php echo $layout['eco_btn_busca_ok_hover'];?>" type="text">
                                            <span class="add-on input-group-addon"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class='span3'>
                            <div class='control-group'>
                                <label class='control-label'>Botão OK (cor)
                                </label>
                                <div class='controls controls-row'>
                                    <div class='span3'>
                                        <div class="input-append colorpicker colorpicker-component">
                                            <input class="span7" name="layout[eco_btn_busca_ok_cor]" id="eco_btn_busca_ok_cor" value="<?php echo $layout['eco_btn_busca_ok_cor'];?>" type="text">
                                            <span class="add-on input-group-addon"><i></i></span>
                                        </div>
                                    </div>  
                                </div>  
                            </div>
                        </div>
                        <div class='span3'>
                            <div class='control-group'>
                                <label class='control-label'>Texto Meu Carrinho

                                </label>
                                <div class='controls controls-row'>
                                    <div class='span4'>
                                        <div class="input-append colorpicker colorpicker-component">
                                            <input class="span7" name="layout[eco_text_carrinho_cor]" id="eco_text_carrinho_cor" value="<?php echo $layout['eco_text_carrinho_cor'];?>" type="text">
                                            <span class="add-on input-group-addon"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <h5 class="titulo_ext btn-show-hide" data-posicao="2" data-target="show">MENU</h5>
                <div class="collapse colls_2">
                    <div class='row-fluid'>
                        <div class='span3'>
                            <div class='control-group'>
                                <label class='control-label'>Menu(fundo)
                                </label>
                                <div class='controls controls-row'>
                                    <div class='span2'>
                                        <div class="input-append colorpicker colorpicker-component">
                                            <input class="span7" name="layout[eco_menu_box_fundo]" id="eco_menu_box_fundo" value="<?php echo $layout['eco_menu_box_fundo'];?>" type="text">
                                            <span class="add-on input-group-addon"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class='span3'>
                            <div class='control-group'>
                                <label class='control-label'>Menu Titulo(fundo)
                                </label>
                                <div class='controls controls-row'>
                                    <div class='span2'>
                                        <div class="input-append colorpicker colorpicker-component">
                                            <input class="span7" name="layout[eco_menu_box_titulo_fundo]" id="eco_menu_box_titulo_fundo" value="<?php echo $layout['eco_menu_box_titulo_fundo'];?>" type="text">
                                            <span class="add-on input-group-addon"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class='span3'>
                            <div class='control-group'>
                                <label class='control-label'>Menu Titulo(cor)
                                </label>
                                <div class='controls controls-row'>
                                    <div class='span3'>
                                        <div class="input-append colorpicker colorpicker-component">
                                            <input class="span7" name="layout[eco_menu_box_titulo_cor]" id="eco_menu_box_titulo_cor" value="<?php echo $layout['eco_menu_box_titulo_cor'];?>" type="text">
                                            <span class="add-on input-group-addon"><i></i></span>
                                        </div>
                                    </div>  
                                </div>  
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class='row-fluid'>

                        <div class='span3'>
                            <div class='control-group'>
                                <label class='control-label'>Menu Fundo Link(hover)
                                </label>
                                <div class='controls controls-row'>
                                    <div class='span4'>
                                        <div class="input-append colorpicker colorpicker-component">
                                            <input class="span7" name="layout[eco_menu_bg_link_hover]" id="eco_menu_bg_link_hover" value="<?php echo $layout['eco_menu_bg_link_hover'];?>" type="text">
                                            <span class="add-on input-group-addon"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>  
                       <div class='span3'>
                            <div class='control-group'>
                                <label class='control-label'>Menu Link(cor)
                                </label>
                                <div class='controls controls-row'>
                                    <div class='span4'>
                                        <div class="input-append colorpicker colorpicker-component">
                                            <input class="span7" name="layout[eco_menu_link_cor]" id="eco_menu_link_cor" value="<?php echo $layout['eco_menu_link_cor'];?>" type="text">
                                            <span class="add-on input-group-addon"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>  
                        <div class='span3'>
                            <div class='control-group'>
                                <label class='control-label'>Menu Link(hover)

                                </label>
                                <div class='controls controls-row'>
                                    <div class='span4'>
                                        <div class="input-append colorpicker colorpicker-component">
                                            <input class="span7" name="layout[eco_menu_link_hover]" id="eco_menu_link_hover" value="<?php echo $layout['eco_menu_link_hover'];?>" type="text">
                                            <span class="add-on input-group-addon"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                      <div class='span3'>
                            <div class='control-group'>
                                <label class='control-label'>Menu Link(saparador)
                                </label>
                                <div class='controls controls-row'>
                                    <div class='span4'>
                                        <div class="input-append colorpicker colorpicker-component">
                                            <input class="span7" name="layout[eco_menu_separador_cor]" id="eco_menu_separador_cor" value="<?php echo $layout['eco_menu_separador_cor'];?>" type="text">
                                            <span class="add-on input-group-addon"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <h5 class="titulo_ext btn-show-hide" data-posicao="3" data-target="show">VITRINE</h5>
                <div class="collapse colls_3">
                    <div class='row-fluid'>
                        <div class='span3'>
                            <div class='control-group'>
                                <label class='control-label'>Preço Produto(cor)
                                </label>
                                <div class='controls controls-row'>
                                    <div class='span4'>
                                        <div class="input-append colorpicker colorpicker-component">
                                            <input class="span7" name="layout[eco_vitrine_preco_produto_cor]" id="eco_vitrine_preco_produto_cor" value="<?php echo $layout['eco_vitrine_preco_produto_cor'];?>" type="text">
                                            <span class="add-on input-group-addon"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class='span3'>
                            <div class='control-group'>
                                <label class='control-label'>Botão Adicionar(fundo)
                                </label>
                                <div class='controls controls-row'>
                                    <div class='span4'>
                                        <div class="input-append colorpicker colorpicker-component">
                                            <input class="span7" name="layout[eco_vitrine_botao_adicionar_fundo]" id="eco_vitrine_botao_adicionar_fundo" value="<?php echo $layout['eco_vitrine_botao_adicionar_fundo'];?>" type="text">
                                            <span class="add-on input-group-addon"><i></i></span>
                                        </div>
                                    </div>  
                                </div>  
                            </div>
                        </div>
                        <div class='span3'>
                            <div class='control-group'>
                                <label class='control-label'>Botão Adicionar(hover)
                                </label>
                                <div class='controls controls-row'>
                                    <div class='span4'>
                                        <div class="input-append colorpicker colorpicker-component">
                                            <input class="span7" name="layout[eco_vitrine_botao_adicionar_hover]" id="eco_vitrine_botao_adicionar_hover" value="<?php echo $layout['eco_vitrine_botao_adicionar_hover'];?>" type="text">
                                            <span class="add-on input-group-addon"><i></i></span>
                                        </div>
                                    </div>  
                                </div>  
                            </div>
                        </div>
                        <div class='span3'>
                            <div class='control-group'>
                                <label class='control-label'>Botão Adicionar(cor)
                                </label>
                                <div class='controls controls-row'>
                                    <div class='span4'>
                                        <div class="input-append colorpicker colorpicker-component">
                                            <input class="span7" name="layout[eco_vitrine_botao_adicionar_cor]" id="eco_vitrine_botao_adicionar_cor" value="<?php echo $layout['eco_vitrine_botao_adicionar_cor'];?>" type="text">
                                            <span class="add-on input-group-addon"><i></i></span>
                                        </div>
                                    </div>  
                                </div>  
                            </div>
                        </div>
                    </div>
                </div>
                <h5 class="titulo_ext btn-show-hide" data-posicao="4" data-target="show">DETALHE DO PRODUTO</h5>
                <div class="collapse colls_4">
                    <div class='row-fluid'>
                        <div class='span3'>
                            <div class='control-group'>
                                <label class='control-label'>Detalhe Produto(fundo)
                                </label>
                                <div class='controls controls-row'>
                                    <div class='span4'>
                                        <div class="input-append colorpicker colorpicker-component">
                                            <input class="span7" name="layout[eco_detalhe_titulo_descricao_produto_fundo]" id="eco_detalhe_titulo_descricao_produto_fundo" value="<?php echo $layout['eco_detalhe_titulo_descricao_produto_fundo'];?>" type="text">
                                            <span class="add-on input-group-addon"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class='span3'>
                            <div class='control-group'>
                                <label class='control-label'>Detalhe Produto(cor)
                                </label>
                                <div class='controls controls-row'>
                                    <div class='span4'>
                                        <div class="input-append colorpicker colorpicker-component">
                                            <input class="span7" name="layout[eco_detalhe_titulo_descricao_produto_cor]" id="eco_detalhe_titulo_descricao_produto_cor" value="<?php echo $layout['eco_detalhe_titulo_descricao_produto_cor'];?>" type="text">
                                            <span class="add-on input-group-addon"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class='span3'>
                            <div class='control-group'>
                                <label class='control-label'>Nome do Produto(cor)
                                </label>
                                <div class='controls controls-row'>
                                    <div class='span4'>
                                        <div class="input-append colorpicker colorpicker-component">
                                            <input class="span7" name="layout[eco_detalhe_nome_produto_cor]" id="eco_detalhe_nome_produto_cor" value="<?php echo $layout['eco_detalhe_nome_produto_cor'];?>" type="text">
                                            <span class="add-on input-group-addon"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class='span3'>
                            <div class='control-group'>
                                <label class='control-label'>Preço Produto(cor)
                                </label>
                                <div class='controls controls-row'>
                                    <div class='span4'>
                                        <div class="input-append colorpicker colorpicker-component">
                                            <input class="span7" name="layout[eco_detalhe_preco_cor]" id="eco_detalhe_preco_cor" value="<?php echo $layout['eco_detalhe_preco_cor'];?>" type="text">
                                            <span class="add-on input-group-addon"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class='row-fluid'>
                        <div class='span3'>
                            <div class='control-group'>
                                <label class='control-label'>Botão Adicionar(cor)
                                </label>
                                <div class='controls controls-row'>
                                    <div class='span4'>
                                        <div class="input-append colorpicker colorpicker-component">
                                            <input class="span7" name="layout[eco_detalhe_botao_adicionar_cor]" id="eco_detalhe_botao_adicionar_cor" value="<?php echo $layout['eco_detalhe_botao_adicionar_cor'];?>" type="text">
                                            <span class="add-on input-group-addon"><i></i></span>
                                        </div>
                                    </div>  
                                </div>  
                            </div>
                        </div>
                        <div class='span3'>
                            <div class='control-group'>
                                <label class='control-label'>Botão Adicionar(fundo)
                                </label>
                                <div class='controls controls-row'>
                                    <div class='span4'>
                                        <div class="input-append colorpicker colorpicker-component">
                                            <input class="span7" name="layout[eco_detalhe_botao_adicionar_fundo]" id="eco_detalhe_botao_adicionar_fundo" value="<?php echo $layout['eco_detalhe_botao_adicionar_fundo'];?>" type="text">
                                            <span class="add-on input-group-addon"><i></i></span>
                                        </div>
                                    </div>  
                                </div>  
                            </div>
                        </div>
                        <div class='span3'>
                            <div class='control-group'>
                                <label class='control-label'>Botão Adicionar(hover)
                                </label>
                                <div class='controls controls-row'>
                                    <div class='span4'>
                                        <div class="input-append colorpicker colorpicker-component">
                                            <input class="span7" name="layout[eco_detalhe_botao_adicionar_hover]" id="eco_detalhe_botao_adicionar_hover" value="<?php echo $layout['eco_detalhe_botao_adicionar_hover'];?>" type="text">
                                            <span class="add-on input-group-addon"><i></i></span>
                                        </div>
                                    </div>  
                                </div>  
                            </div>
                        </div>
                    </div>
                </div>
                <h5 class="titulo_ext btn-show-hide" data-posicao="5" data-target="show">CARRINHO DE COMPRAS / FINALIZA PEDIDO</h5>
                <div class="collapse colls_5">
                    <div class='row-fluid'>
                        <div class='span3'>
                            <div class='control-group'>
                                <label class='control-label'>Carrinho Compras(fundo)
                                </label>
                                <div class='controls controls-row'>
                                    <div class='span4'>
                                        <div class="input-append colorpicker colorpicker-component">
                                            <input class="span7" name="layout[eco_carrinho_titulo_fundo]" id="eco_carrinho_titulo_fundo" value="<?php echo $layout['eco_carrinho_titulo_fundo'];?>" type="text">
                                            <span class="add-on input-group-addon"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class='span3'>
                            <div class='control-group'>
                                <label class='control-label'>Carrinho Compras(cor)
                                </label>
                                <div class='controls controls-row'>
                                    <div class='span4'>
                                        <div class="input-append colorpicker colorpicker-component">
                                            <input class="span7" name="layout[eco_carrinho_titulo_cor]" id="eco_carrinho_titulo_cor" value="<?php echo $layout['eco_carrinho_titulo_cor'];?>" type="text">
                                            <span class="add-on input-group-addon"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class='span3'>
                            <div class='control-group'>
                                <label class='control-label'>Subtotal(cor)
                                </label>
                                <div class='controls controls-row'>
                                    <div class='span4'>
                                        <div class="input-append colorpicker colorpicker-component">
                                            <input class="span7" name="layout[eco_carrinho_preco_subtotal_cor]" id="eco_carrinho_preco_subtotal_cor" value="<?php echo $layout['eco_carrinho_preco_subtotal_cor'];?>" type="text">
                                            <span class="add-on input-group-addon"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class='span3'>
                            <div class='control-group'>
                                <label class='control-label'>Frete(cor)
                                </label>
                                <div class='controls controls-row'>
                                    <div class='span4'>
                                        <div class="input-append colorpicker colorpicker-component">
                                            <input class="span7" name="layout[eco_carrinho_preco_correio_cor]" id="eco_carrinho_preco_correio_cor" value="<?php echo $layout['eco_carrinho_preco_correio_cor'];?>" type="text">
                                            <span class="add-on input-group-addon"><i></i></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class='row-fluid'>
                        <div class='span3'>
                            <div class='control-group'>
                                <label class='control-label'>Total(cor)
                                </label>
                                <div class='controls controls-row'>
                                    <div class='span4'>
                                        <div class="input-append colorpicker colorpicker-component">
                                            <input class="span7" name="layout[eco_carrinho_preco_total_cor]" id="eco_carrinho_preco_total_cor" value="<?php echo $layout['eco_carrinho_preco_total_cor'];?>" type="text">
                                            <span class="add-on input-group-addon"><i></i></span>
                                        </div>
                                    </div>  
                                </div>  
                            </div>
                        </div>
                        <div class='span3'>
                            <div class='control-group'>
                                <label class='control-label'>Hover Borda Frete(cor)
                                </label>
                                <div class='controls controls-row'>
                                    <div class='span4'>
                                        <div class="input-append colorpicker colorpicker-component">
                                            <input class="span7" name="layout[eco_carrinho_frete_borda_hover_cor]" id="eco_carrinho_frete_borda_hover_cor" value="<?php echo $layout['eco_carrinho_frete_borda_hover_cor'];?>" type="text">
                                            <span class="add-on input-group-addon"><i></i></span>
                                        </div>
                                    </div>  
                                </div>  
                            </div>
                        </div>
                        <div class='span3'>
                            <div class='control-group'>
                                <label class='control-label'>Hover Borda Cartão(cor)
                                </label>
                                <div class='controls controls-row'>
                                    <div class='span4'>
                                        <div class="input-append colorpicker colorpicker-component">
                                            <input class="span7" name="layout[eco_carrinho_cartao_borda_hover_cor]" id="eco_carrinho_cartao_borda_hover_cor" value="<?php echo $layout['eco_carrinho_cartao_borda_hover_cor'];?>" type="text">
                                            <span class="add-on input-group-addon"><i></i></span>
                                        </div>
                                    </div>  
                                </div>  
                            </div>
                        </div>
                        <div class='span3'>
                            <div class='control-group'>
                                <label class='control-label'>Nº Pedido(cor)
                                </label>
                                <div class='controls controls-row'>
                                    <div class='span4'>
                                        <div class="input-append colorpicker colorpicker-component">
                                            <input class="span7" name="layout[eco_carrinho_npedido_cor]" id="eco_carrinho_npedido_cor" value="<?php echo $layout['eco_carrinho_npedido_cor'];?>" type="text">
                                            <span class="add-on input-group-addon"><i></i></span>
                                        </div>
                                    </div>  
                                </div>  
                            </div>
                        </div>
                    </div>
                </div>
                <p align="center"><br/>
                    <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Gravar</button>
                    <input type='hidden' id="btn_click" name='btn_acao' value='' />
                </p><br/>
        </div>
        <div class='span1'></div>
    </div>
</div>    
</form> 

<form name="form_anexo" method="post" action="loja.php" enctype="multipart/form-data" style="display: none !important;" >
    <input type="file" name="anexo_upload" value="" />
    <input type="hidden" name="ajax_anexo_upload" value="t" />
    <input type="hidden" name="anexo_chave" value="<?=$anexo_chave?>" />
</form><br />

</div> <!-- Aqui fecha a DIV Container que abre no cabeçãlho -->
<?php
    include 'rodape.php';
?>
