<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';
include_once "../class/tdocs.class.php";
use Lojavirtual\Produto;
use Lojavirtual\Categoria;

use Lojavirtual\Fornecedor;
use Lojavirtual\Loja;

$objCategoria= new Categoria();
$objProduto  = new Produto();
$tDocs       = new TDocs($con, $login_fabrica);
$objFornecedor   = new Fornecedor();
$objLoja      = new Loja();

$dadosProduto = $objProduto->get();

$url_redir = "<meta http-equiv=refresh content=\"0;URL=loja_produto.php\">";

if ($_POST["buscaPeca"]) {

    $ref  = $_POST["ref"];

    $resPeca = $objProduto->getPeca('',$ref);

    if (!empty($resPeca["peca"])) {
        $resProd = $objProduto->get('',$resPeca["peca"]);

        if (!empty($resProd[0]["codigo_peca"])) {
            $retorno = array('sucesso' => true, 'peca' => $resProd[0]["codigo_peca"]);
        } else {
            $retorno = array('semItem' => true);
        }
    } else {
        $retorno = array('erro' => true, 'msg' => utf8_encode('Peca não Encontrada'));
    }

    exit(json_encode($retorno));
}

if ($_GET["acao"] == "delete" && !empty($_GET['loja_b2b_peca'])) {

    if (empty($_GET['loja_b2b_peca'])) {
        $msg_erro["msg"][]    = "Produto não encontrado!";
    }

    if (count($msg_erro) == 0) {

        $res = pg_query($con,"BEGIN");

        if (!empty($objProduto)) {
            $retorno = $objProduto->delete($_GET['loja_b2b_peca']);
            if ($retorno["erro"]) {
                $msg_erro["msg"][] = $retorno["msn"];
            } else {
                $msg_sucesso["msg"][] = 'Produto removido com sucesso!';
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

if ($_GET["acao"] == "edit" && $_GET["loja_b2b_peca"] > 0) {
    
    $loja_b2b_peca          = $_GET['loja_b2b_peca'];
    $rowProdutos        = $objProduto->get($loja_b2b_peca);
    $peca_referencia    = $rowProdutos['ref_peca'];
    $peca_descricao     = $rowProdutos['nome_peca'];
    $categoria          = $rowProdutos['peca_categoria'];
    $descricao          = $rowProdutos['descricao_peca'];
    $preco              = $rowProdutos['preco_peca'];
    $preco_promocional  = $rowProdutos['preco_promocional_peca'];
    $qtde_estoque       = $rowProdutos['qtde_estoque_peca'];
    //$qtde_max_posto     = $rowProdutos['qtde_max_posto_peca'];
    $kit_peca           = $rowProdutos['kit_peca'];
    $anexo              = $rowProdutos['anexo'];
    $disponivel         = $rowProdutos['disponibilidade_peca'];
    $destaque           = $rowProdutos['peca_destaque'];
    $fornecedor         = $rowProdutos['loja_b2b_fornecedor'];
    $tipo_acao          = "edit";
    
    $tamanhos = $objProduto->getGradeByProduto($loja_b2b_peca);
    if ($tamanhos["erro"]) {
        $tamanhos = [];
    } 
    if (isset($objLoja->configuracao_envio["meio"]) && count($objLoja->configuracao_envio["meio"]) > 0) {
        $altura         = $rowProdutos['altura'];
        $largura        = $rowProdutos['largura'];
        $comprimento    = $rowProdutos['comprimento'];
        $peso           = $rowProdutos['peso'];
    }

}


if ($_POST["btn_acao"] == "submit" && $tipo_acao  == "edit") {

    $loja_b2b_peca          = $_POST['loja_b2b_peca'];
    $categoria          = $_POST['categoria'];
    $descricao          = $_POST['descricao'];
    $preco              = $_POST['preco'];
    $preco_promocional  = $_POST['preco_promocional'];
    $qtde_estoque       = $_POST['qtde_estoque'];
    //$qtde_max_posto     = $_POST['qtde_max_posto'];
    $kit_peca           = ($_POST['kit_peca'] == 't') ? 't' : 'f';
    $anexo              = $_POST['anexo'];
    $disponivel         = ($_POST['disponivel'] == 't') ? 't' : 'f';
    $destaque           = ($_POST['destaque'] == 't') ? 't' : 'f';

    if ($login_fabrica == 42) {
        $fornecedor = $_POST['fornecedor'];

        if (empty($fornecedor)) {
            $msg_erro['msg'][]    = "Informe o fornecedor";
            $msg_erro["campos"][] = "fornecedor";
        }
    } else {
        $fornecedor = 'null';
    }

    foreach ($anexo as $key => $value) {
        if (empty($value)) {
            unset($anexo[$key]);
        }
    }

    if (isset($objLoja->configuracao_envio["meio"]) && count($objLoja->configuracao_envio["meio"]) > 0) {
        $altura         = $_POST['altura'];
        $largura        = $_POST['largura'];
        $comprimento    = $_POST['comprimento'];
        $peso           = $_POST['peso'];

        if (strlen($altura) == 0 || $altura <= 0) {
            $msg_erro["msg"][]    = "Preencha o campo Altura";
            $msg_erro["campos"][] = "altura";
        }

        if (strlen($largura) == 0 || $largura <= 0) {
            $msg_erro["msg"][]    = "Preencha o campo Largura";
            $msg_erro["campos"][] = "largura";
        }

        if (strlen($comprimento) == 0 || $comprimento <= 0) {
            $msg_erro["msg"][]    = "Preencha o campo Comprimento";
            $msg_erro["campos"][] = "comprimento";
        }

        if (strlen($peso) == 0 || $peso <= 0) {
            $msg_erro["msg"][]    = "Preencha o campo Peso";
            $msg_erro["campos"][] = "peso";
        }

    }

    if (strlen($loja_b2b_peca) == 0) {
        $msg_erro["msg"][]    = "Produto não encontrado";
    }

    if (strlen($categoria) == 0) {
        $msg_erro["msg"][]    = "Escolha uma Categoria";
        $msg_erro["campos"][] = "categoria";
    }

    if (strlen($preco) == 0 || $preco <= 0) {
        $msg_erro["msg"][]    = "Preencha o campo Preço";
        $msg_erro["campos"][] = "preco";
    }

    if (count($msg_erro) == 0) {

        $res = pg_query($con,"BEGIN");
        $dataSave = array(
                "loja_b2b_peca"         => $loja_b2b_peca,
                "categoria"         => $categoria,
                "descricao"         => $descricao,
                "preco"             => empty($preco) ? 0 : $preco,
                "preco_promocional" => empty($preco_promocional) ? 0 : $preco_promocional,
                "qtde_estoque"      => empty($qtde_estoque) ? 0 : $qtde_estoque,
                //"qtde_max_posto"    => empty($qtde_max_posto) ? 0 : $qtde_max_posto,
                "kit_peca"          => $kit_peca,
                "disponivel"        => $disponivel,
                "destaque"          => $destaque,
                "loja_b2b_fornecedor" => $fornecedor
             );

        if (isset($objLoja->configuracao_envio["meio"]) && count($objLoja->configuracao_envio["meio"]) > 0) {
            $dataSave["altura"] = $altura      ;
            $dataSave["largura"] = $largura     ;
            $dataSave["comprimento"] = $comprimento ;
            $dataSave["peso"] = $peso        ;
        }

        if (!empty($objProduto)) {
            $retorno = $objProduto->update($dataSave);

            if ($retorno["erro"]) {
                $msg_erro["msg"][] = $retorno["msn"];
            } else {
                $msg_sucesso["msg"][] = 'Produto atualizado com sucesso!';

                $objProduto->updateGradeProduto($_POST["tamanhos"], $retorno["loja_b2b_peca"]);

                if (!empty($anexo)) {
                    foreach ($anexo as $vAnexo) {
                        if (empty($vAnexo)) {
                            continue;
                        }

                        $dadosAnexo = json_decode($vAnexo, 1);
                        $anexoID = $tDocs->setDocumentReference($dadosAnexo, $retorno["loja_b2b_peca"], "anexar", false, "lojapeca");
                        if (!$anexoID) {
                            $msg_erro["msg"][] = 'Erro ao fazer upload do banner!';
                        }
                    }
                }
            }
        }

        if (count($msg_erro) > 0) {
            $res = pg_query($con,"ROLLBACK");
            $loja_cupom_desconto = $_POST['loja_cupom_desconto'];
            $descricao      = $_POST['descricao'];
            $codigo_cupom   = $_POST['codigo_cupom'];
            $data_validade  = $_POST['data_validade'];
            $desconto       = $_POST['desconto'];
            $kit_peca       = $_POST['kit_peca'];
            $qtde_cupom     = $_POST['qtde_cupom'];
            $limite_cupom   = $_POST['limite_cupom'];
            $anexo          = $_POST['anexo'];
        } else {
            $res = pg_query($con,"COMMIT");
            echo $url_redir;
        }
    }
}

if ($_POST["btn_acao"] == "submit" && $tipo_acao  != "edit") {

    $peca_referencia    = $_POST['peca_referencia'];
    $categoria          = $_POST['categoria'];
    $descricao          = $_POST['descricao'];
    $preco              = $_POST['preco'];
    $preco_promocional  = $_POST['preco_promocional'];
    $qtde_estoque       = $_POST['qtde_estoque'];
    //$qtde_max_posto     = $_POST['qtde_max_posto'];
    $anexo              = $_POST['anexo'];
    $disponivel         = ($_POST['disponivel'] == 't') ? 't' : 'f';
    $destaque           = ($_POST['destaque'] == 't') ? 't' : 'f';
    $kit_peca           = ($_POST['kit_peca'] == 't') ? 't' : 'f';

    if ($login_fabrica == 42) {
        $fornecedor = $_POST['fornecedor'];

        if (empty($fornecedor)) {
            $msg_erro['msg'][]    = "Informe o fornecedor";
            $msg_erro["campos"][] = "fornecedor";
        }
    } else {
        $fornecedor = 'null';
    }

    foreach ($anexo as $key => $value) {
        if (empty($value)) {
            unset($anexo[$key]);
        }
    }

    if (isset($objLoja->configuracao_envio["meio"]) && count($objLoja->configuracao_envio["meio"]) > 0) {
        $altura         = $_POST['altura'];
        $largura        = $_POST['largura'];
        $comprimento    = $_POST['comprimento'];
        $peso           = $_POST['peso'];

        if (strlen($altura) == 0 || $altura <= 0) {
            $msg_erro["msg"][]    = "Preencha o campo Altura";
            $msg_erro["campos"][] = "altura";
        }

        if (strlen($largura) == 0 || $largura <= 0) {
            $msg_erro["msg"][]    = "Preencha o campo Largura";
            $msg_erro["campos"][] = "largura";
        }

        if (strlen($comprimento) == 0 || $comprimento <= 0) {
            $msg_erro["msg"][]    = "Preencha o campo Comprimento";
            $msg_erro["campos"][] = "comprimento";
        }

        if (strlen($peso) == 0 || $peso <= 0) {
            $msg_erro["msg"][]    = "Preencha o campo Peso";
            $msg_erro["campos"][] = "peso";
        }

    }

    if (strlen($peca_referencia) == 0) {
        $msg_erro["msg"][]    = "Preencha o campo Referência Peça";
        $msg_erro["campos"][] = "peca_referencia";
        $msg_erro["campos"][] = "peca_descricao";
    }

    if (strlen($categoria) == 0) {
        $msg_erro["msg"][]    = "Escolha uma Categoria";
        $msg_erro["campos"][] = "categoria";
    }

    if (strlen($preco) == 0 || $preco <= 0) {
        $msg_erro["msg"][]    = "Preencha o campo Preço";
        $msg_erro["campos"][] = "preco";
    }

    $dadosPeca = $objProduto->getPeca(null, $peca_referencia);
    if (!empty($dadosPeca)) {

        $peca = $dadosPeca["peca"];

    } else {

        $msg_erro["msg"][]    = "Peça não encontrada";
        $msg_erro["campos"][] = "peca_referencia";
        $msg_erro["campos"][] = "peca_descricao";

    }

    if (count($msg_erro) == 0) {

        $res = pg_query($con,"BEGIN");
        $dataSave = array(
                        "peca"              => $peca,
                        "categoria"         => $categoria,
                        "descricao"         => $descricao,
                        "preco"             => empty($preco) ? 0 : $preco,
                        "preco_promocional" => empty($preco_promocional) ? 0 : $preco_promocional,
                        "qtde_estoque"      => empty($qtde_estoque) ? 0 : $qtde_estoque,
                        //"qtde_max_posto"    => empty($qtde_max_posto) ? 0 : $qtde_max_posto,
                        "kit_peca"          => $kit_peca,
                        "disponivel"        => $disponivel,
                        "destaque"          => $destaque,
                        "loja_b2b_fornecedor" => $fornecedor
                     );

        if (isset($objLoja->configuracao_envio["meio"]) && count($objLoja->configuracao_envio["meio"]) > 0) {
            $dataSave["altura"] = $altura      ;
            $dataSave["largura"] = $largura     ;
            $dataSave["comprimento"] = $comprimento ;
            $dataSave["peso"] = $peso        ;
        }

        if (!empty($objProduto)) {
			if(!empty($peca)) {
				$sql = "select loja_b2b_peca from tbl_loja_b2b_peca where peca= $peca";
				$res = pg_query($con,$sql);
				if(pg_num_rows($res) >0) {
					$loja_b2b_peca = pg_fetch_result($res, 0 , 'loja_b2b_peca') ;
					$dataSave['loja_b2b_peca'] = $loja_b2b_peca;
					$retorno = $objProduto->update($dataSave);
				}else{
					$retorno = $objProduto->save($dataSave);
				}
			}

            if ($retorno["erro"]) {
                $msg_erro["msg"][] = $retorno["msn"];
            } else {
                $msg_sucesso["msg"][] = 'Produto cadastrado com sucesso!';

                if (count($_POST["tamanhos"]) > 0) {
                   $objProduto->cadastraGradeProduto($_POST["tamanhos"], $retorno["loja_b2b_peca"]);
                }
                if (!empty($anexo)) {

                    foreach ($anexo as $vAnexo) {
                        if (empty($vAnexo)) {
                            continue;
                        }
                        $dadosAnexo = json_decode($vAnexo, 1);
                        $anexoID = $tDocs->setDocumentReference($dadosAnexo, $retorno["loja_b2b_peca"], "anexar", false, "lojapeca");
                        if (!$anexoID) {
                            $msg_erro["msg"][] = 'Erro ao fazer upload do banner!';
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
            $qtde_estoque       = $_POST['qtde_estoque'];
            //$qtde_max_posto     = $_POST['qtde_max_posto'];
            $kit_peca           = $_POST['kit_peca'];
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

    $tDocs->setContext('lojapeca');

    $anexoID = $tDocs->deleteFileById($tdocs_id);

    if (!$anexoID) {
        $retorno = array('erro' => true, 'msg' => utf8_encode('Erro ao remover arquivo'),'posicao' => $posicao);
    }  else {

        $retorno = array('sucesso' => true, 'posicao' => $posicao);
    }

    exit(json_encode($retorno));

}


$layout_menu = "cadastro";
$title       = "Cadastro de Produtos - Loja Virtual";
include 'cabecalho_new.php';

$plugins = array(
    "shadowbox",
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
        $.autocompleteLoad(Array("peca"), Array("peca"));
        CKEDITOR.replace("descricao", { enterMode: CKEDITOR.ENTER_BR});
        setupZoom();

        $("#peca_referencia, #peca_descricao").on("blur", function() {
            if ($("#peca_referencia").val() != '' && $("#peca_referencia").val() != undefined && $("#peca_descricao").val() != '' && $("#peca_referencia").val() != undefined) {
                $.ajax({
                    url: 'loja_cadastra_produto.php',
                    type: "POST",
                    dataType:"JSON",
                    data: { 
                        buscaPeca: true,
                        ref: $("#peca_referencia").val()
                    }
                }).done(function(data) {
                    if (data.erro == true) {
                        alert(data.msg);
                        return false;
                    } else if (data.sucesso) {
                        window.location.href = "loja_cadastra_produto.php?acao=edit&loja_b2b_peca="+data.peca;
                    }
                });
            } 
        });

        $(".multiple").multiselect({
           selectedText: "# of # selected"
        });

        $("#preco").priceFormat({
            prefix: '',
            thousandsSeparator: '',
            centsSeparator: '.',
            centsLimit: 2
        });

        $("#preco_promocional").priceFormat({
            prefix: '',
            thousandsSeparator: '',
            centsSeparator: '.',
            centsLimit: 2
        });

        $(".unimedidas").priceFormat({
            prefix: '',
            thousandsSeparator: '',
            centsSeparator: '.',
            centsLimit: 2
        });

        $(document).on("click", "span[rel=lupa]", function () {
            $.lupa($(this),Array('posicao' <?php echo ($login_fabrica == 30) ? ",'pesquisa_produto_acabado'" : ""; ?>));
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
                    url: 'loja_cadastra_produto.php',
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
        $("#peca_referencia").val(retorno.referencia);
        $("#peca_descricao").val(retorno.descricao);

         $.ajax({
            url: 'loja_cadastra_produto.php',
            type: "POST",
            dataType:"JSON",
            data: { 
                buscaPeca: true,
                ref: $("#peca_referencia").val()
            }
        }).done(function(data) {
            if (data.erro == true) {
                alert(data.msg);
                return false;
            } else if (data.sucesso) {
                window.location.href = "loja_cadastra_produto.php?acao=edit&loja_b2b_peca="+data.peca;
            }
        });
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
    .check_box_tamanhos{
        margin-top: 0px !important;
    }
    .campo_tamanho{
        vertical-align: middle;
        display: inline-block;
        margin-left:10px;
    }
</style>
    <?php
        if (empty($objProduto->_loja)) {
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
                $readOnlyPeca    = "readonly='readonly'";
                $displayNonePeca = "style='display:none;'";
        ?>
        <input type="hidden" name="tipo_acao" value="edit">
        <input type="hidden" name="loja_b2b_peca" value="<?php echo $loja_b2b_peca;?>">
        <?php } else {?> 
        <input type="hidden" name="tipo_acao" value="add">
        <?php }?> 
        <div class='titulo_tabela '>Cadastro</div>
        <br/>
        <div class="row-fluid">
            <div class="span2"></div>
            <div class="span3">
                <div class='control-group <?=(in_array("peca_referencia", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class="control-label" for="peca_referencia">Referência Peça</label>
                    <div class="controls controls-row">
                        <div class="span12">
                            <h5 class='asteristico'>*</h5>
                            <div class="input-append">
                                <input type="text" <?php echo $readOnlyPeca;?> value="<?php echo $peca_referencia;?>" id="peca_referencia" name="peca_referencia" class="span9">
                                <span class="add-on" <?php echo $displayNonePeca;?> rel="lupa"><i class="icon-search"></i></span>
                                <input type="hidden" name="lupa_config" tipo="peca"  parametro="referencia" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class='span5'>
                <div class='control-group <?=(in_array("peca_descricao", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='peca_descricao'>Descrição Peça</label>
                    <div class="controls controls-row">
                        <div class="span12">
                            <h5 class='asteristico'>*</h5>
                            <div class="input-append span11">
                                <input type="text" <?php echo $readOnlyPeca;?> value="<?php echo $peca_descricao;?>" name="peca_descricao" id="peca_descricao" class="span12">
                                <span class="add-on" <?php echo $displayNonePeca;?> rel="lupa"><i class="icon-search"></i></span>
                                <input type="hidden" name="lupa_config" tipo="peca" parametro="descricao" />
                            </div>
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
                <div class='control-group <?=(in_array("preco", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label'>Preço</label>
                    <div class='controls controls-row'>
                        <div class='span10'>
                            <h5 class='asteristico'>*</h5>
                            <div class="input-prepend">
                                <span class="add-on">R$</span>
                                <input class="span12" type="text" name="preco" value="<?=number_format($preco, 2, '.', '')?>" id="preco">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'>
                <div class='control-group <?=(in_array("descricao", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label'>Preço promocional</label>
                    <div class='controls controls-row'>
                        <div class='span10'>
                            <div class="input-prepend">
                                <span class="add-on">R$</span>
                                <input class="span12" type="text" name="preco_promocional" value="<?=number_format($preco_promocional, 2, '.', '')?>" id="preco_promocional">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>
        <div class='row-fluid'>
            <div class="row-fluid">
                <div class="span2"></div>
                <?php
                if ($login_fabrica == 42) {
                ?>
                <div class='span4'>
                    <div class='control-group <?=(in_array("fornecedor", $msg_erro["campos"])) ? "error" : ""?>'>
                        <label class='control-label'>Fornecedor</label>
                        <div class='controls controls-row'>
                            <div class='span11'>
                                <h5 class='asteristico'>*</h5>
                                <select name="fornecedor" class='span12' id="fornecedor">
                                    <option value=""> - Escolha um fornecedor - </option>
                                    <?php 
                                        $fornecedoresBusca = $objFornecedor->get();
                                        if (!empty($fornecedoresBusca)) {
                                            foreach ($fornecedoresBusca as $vFor) {
                                                $selected = ($fornecedor == $vFor['loja_b2b_fornecedor']) ? 'selected' : '';

                                                if ($vFor['ativo'] != 't') {
                                                    continue;
                                                }
                                    ?>
                                    <option value="<?php echo $vFor['loja_b2b_fornecedor'];?>" <?php echo $selected;?>><?php echo $vFor['nome']; ?></option>
                                    <?php  }
                                        }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <?php
                }
                ?>
                <?php if ($moduloB2BGrade) {?>
                <div class='span4'>
                    <div class='control-group <?=(in_array("tamanhos", $msg_erro["campos"])) ? "error" : ""?>'>
                        <label class='control-label'>Tamanho</label>
                        <div class='controls controls-row'>
                            <div class='span12'>
                                <div class="campo_tamanho">
                                    <input class="check_box_tamanhos" type="checkbox" name="tamanhos[]" <?php echo (in_array('P', $tamanhos)) ? 'checked' : '';?> value="P"> P
                                </div>
                                <div class="campo_tamanho">
                                    <input class="check_box_tamanhos" type="checkbox" name="tamanhos[]" <?php echo (in_array('M', $tamanhos)) ? 'checked' : '';?> value="M"> M
                                </div>
                                <div class="campo_tamanho">
                                    <input class="check_box_tamanhos" type="checkbox" name="tamanhos[]" <?php echo (in_array('G', $tamanhos)) ? 'checked' : '';?> value="G"> G
                                </div>
                                <div class="campo_tamanho">
                                    <input class="check_box_tamanhos" type="checkbox" name="tamanhos[]" <?php echo (in_array('GG', $tamanhos)) ? 'checked' : '';?> value="GG"> GG
                                </div>
                                <div class="campo_tamanho">
                                    <input class="check_box_tamanhos" type="checkbox" name="tamanhos[]" <?php echo (in_array('EXG', $tamanhos)) ? 'checked' : '';?> value="EXG"> EXG
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php }?>
            </div>
        <div class='row-fluid'>
            <div class='span2'></div>
            <?php if ($objLoja->_controlaEstoque) {?>
            <div class='span2'>
                <div class='control-group <?=(in_array("qtde_estoque", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label'>Qtde Estoque</label>
                    <div class='controls controls-row'>
                        <div class='span10'>
                            <input class="span12" type="text" name="qtde_estoque" value="<?php echo $qtde_estoque;?>" id="qtde_estoque">
                        </div>
                    </div>
                </div>
            </div>
            <?php }?>
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
            <div class='span2'>
                <div class='control-group'>
                    <label class='control-label'>Peça de Kit?</label>
                    <div class='controls controls-row'>
                        <div class='span10'>
                            <input type="checkbox" name="kit_peca" <?php echo ($kit_peca == 't') ? 'checked' : '';?> value="t"> Sim
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>
        <?php if (isset($objLoja->configuracao_envio["meio"]) && count($objLoja->configuracao_envio["meio"]) > 0) {?>
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span2'>
                <div class='control-group <?=(in_array("altura", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label'>Altura</label>
                    <div class='controls controls-row'>
                        <div class='span10'>
                            <h5 class='asteristico'>*</h5>
                            <div class="input-prepend">
                                <input class="span12 unimedidas" type="text" name="altura" value="<?=number_format($altura, 2, '.', '')?>" id="altura">
                                <span class="add-on">cm</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'>
                <div class='control-group <?=(in_array("largura", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label'>Largura</label>
                    <div class='controls controls-row'>
                        <div class='span10'>
                            <h5 class='asteristico'>*</h5>
                            <div class="input-prepend">
                                <input class="span12 unimedidas" type="text" name="largura" value="<?=number_format($largura, 2, '.', '')?>" id="largura">
                                <span class="add-on">cm</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'>
                <div class='control-group <?=(in_array("comprimento", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label'>Comprimento</label>
                    <div class='controls controls-row'>
                        <div class='span10'>
                            <h5 class='asteristico'>*</h5>
                            <div class="input-prepend">
                                <input class="span12 unimedidas" type="text" name="comprimento" value="<?=number_format($comprimento, 2, '.', '')?>" id="comprimento">
                                <span class="add-on">cm</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'>
                <div class='control-group <?=(in_array("peso", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label'>Peso</label>
                    <div class='controls controls-row'>
                        <div class='span10'>
                            <h5 class='asteristico'>*</h5>
                            <div class="input-prepend">
                                <input class="span12 unimedidas" type="text" name="peso" value="<?=number_format($peso, 2, '.', '')?>" id="peso">
                                <span class="add-on">kg</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>
        <?php }?>
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
        </div><hr>
        <div class="titulo_tabela">Fotos do Produto</div><br />
        <div class='row-fluid'>
            <div class='span1'></div>
            <div class='span10'>
                <div class="row-fluid">
                    <div class="thumbnails">
                        <div class="span1"></div>
                        <?php 
                            $tDocs->setContext('lojapeca');
                            $info = $tDocs->getDocumentsByRef($loja_b2b_peca)->attachListInfo;
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

                                if ($loja_b2b_peca > 0) {
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
        <p align="center"><br/><br/><br/>
            <button class='btn btn-primary' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Gravar</button>
            <a class='btn' href='loja_produto.php'>Listagem de  Produto</a>
            <input type='hidden' id="btn_click" name='btn_acao' value='' />
        </p><br/>
    </form> <br />

    <?php for ($i = 1; $i <=  5; $i++) {?>
        <form name="form_anexo" method="post" action="loja_cadastra_produto.php" enctype="multipart/form-data" style="display: none !important;" >
            <input type="file" name="anexo_upload_<?=$i?>" value="" />
            <input type="hidden" name="ajax_anexo_upload" value="t" />
            <input type="hidden" name="anexo_posicao" value="<?=$i?>" />
            <input type="hidden" name="anexo_chave" value="<?=$anexo_chave?>" />
        </form>
    <?php }?>

    <?php
        if ($dadosProduto["erro"]) {
            echo '<div class="alert alert-error"><h4>'.$dadosProduto["msn"].'</h4></div>';
        }?>
<?php include 'rodape.php';?>
