<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';
include_once "../class/tdocs.class.php";
use Lojavirtual\Produto;
use Lojavirtual\Loja;
use Lojavirtual\Categoria;
use Lojavirtual\Fornecedor;
use Lojavirtual\LojaTabelaPreco;
$objLoja        = new Loja();
$objCategoria   = new Categoria();
$objProduto     = new Produto();
$objFornecedor  = new Fornecedor();
$objLojaTabelaPreco  = new LojaTabelaPreco();
$tDocs          = new TDocs($con, $login_fabrica);

$dadosProduto = array();
if ($_POST['pesquisa']) {
    $dadosProduto = $objProduto->getAll($_POST);
}
$url_redir = "<meta http-equiv=refresh content=\"0;URL=loja_produto.php\">";

$layout_menu = "cadastro";
$title       = "Produtos - Loja Virtual";
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
        $.dataTableLoad("#tabela");
        //$.autocompleteLoad(Array("peca"), Array("peca"));

        $(".multiple").multiselect({
           selectedText: "# of # selected"
        });
    
        $(document).on("click", ".lupa-peca", function () {
            var tipo            = $(this).data("tipo");
            var referencia_peca = $("input[name=referencia_peca]").val();
            var descricao_peca   = $("input[name=descricao_peca]").val();
            
            if (referencia_peca != '' || descricao_peca != '') {
                Shadowbox.open({
                    content: "lupa_b2b_peca.php?ajax_busca_peca=true&tipo="+tipo+"&referencia_peca="+referencia_peca+"&descricao_peca="+descricao_peca,
                    player: "iframe",
                    title:  "Busca de Peças ",
                    width:  800,
                    height: 500
                });
            } else {
                alert("Digite uma Referência ou Descrição da Peça");
                return false;
            }

        }); 
    });
    function retorna_peca(retorno){
        $("input[name=referencia_peca]").val(retorno.referencia);
        $("input[name=descricao_peca]").val(retorno.descricao);
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
    .titulotr {
        background: #344263 !important;
        color: #fff !important;
        font-weight: bold !important;
        padding: 5px 15px !important;
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
    <form name='frm_relatorio' METHOD='POST' ACTION='loja_produto.php' align='center' class='form-search form-inline tc_formulario' >
        <input type="hidden" name="pesquisa" value="true">
        <div class='titulo_tabela '>Parâmetros de Pesquisa</div>
        <br/>
        <div class="row-fluid">
            <div class="span2"></div>
            <div class="span3">
                <div class='control-group <?=(in_array("referencia_peca", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='referencia_peca'>Referência Peça</label>
                    <div class="controls controls-row">
                        <div class="span12">
                            <div class="input-append span11">
                                <input type="text" value="<?php echo $referencia_peca;?>" name="referencia_peca" id="referencia_peca" class="span12">
                                <span  data-tipo="referencia" class="add-on lupa-peca"><i class="icon-search"></i></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class='span5'>
                <div class='control-group <?=(in_array("descricao_peca", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='descricao_peca'>Descrição Peça</label>
                    <div class="controls controls-row">
                        <div class="span12">
                            <div class="input-append span11">
                                <input type="text" value="<?php echo $descricao_peca;?>" name="descricao_peca" id="descricao_peca" class="span12">
                                <span class="add-on lupa-peca" data-tipo="descricao"><i class="icon-search"></i></span>
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
        </div>

        <p><br/>
            <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
            <a class='btn btn-primary' href='loja_cadastra_produto.php'>Cadastrar Produto</a>
            <input type='hidden' id="btn_click" name='btn_acao' value='' />
        </p><br/>
    </form> <br />

    <?php
        if ($dadosProduto["erro"]) {
            echo '<div class="alert alert-error"><h4>'.$dadosProduto["msn"].'</h4></div>';
        } elseif($_POST['pesquisa']) {
        //$tDocs2 = new TDocs($con, $login_fabrica);
    ?>
</div>
    <table class='table table-striped table-bordered table-fixed' id='tabela'>
        <thead>
            <tr class='titulo_coluna' >
                <th class='tac'>Referência</th>
                <th class='tal'>Nome Produto</th>
                <th class='tal'>Categoria</th>
                <?php if ($moduloB2BGrade) {?>
                <th class='tal' width="12%" nowrap>Grade Tamanho</th>
                <?php }?>
                <th class='tal' width="18%" nowrap>Preço</th>
                <th class='tac'>Preço Promocional</th>
                <th class='tac'>Destaque</th>
                <?php if ($objLoja->_controlaEstoque) {?>
                <th class='tac'>Estoque atual</th>
                <?php }?>
                <th class='tac'>Disponível</th>
                <th class='tac' width="8%">Ações</th>
            </tr>
        </thead>
        <tbody>
        <?php
            unset($dadosProduto["kits"]);
            foreach ($dadosProduto as $key => $rowsProduto) {
                $precosTabelas = $objLojaTabelaPreco->getPrecoProduto($rowsProduto["codigo_peca"]);
                $precoTab = "";
                    $precoTab .= "<table class='table table-bordered table-fixed'>
                                    <tr>
                                        <td class='titulotr'>Tabela</td>
                                        <td class='titulotr'>Preço</td>
                                    </tr>";
                if (count($precosTabelas) > 0) {
                    foreach ($precosTabelas as $k => $rows) {
                        $precoTab .= "<tr>
                                        <td>".$rows["descricao"]."</td>
                                        <td>". number_format($rows["preco"], 2, ',', '.')."</td>
                                    </tr>";
                    }
                }
                        $precoTab .= "<tr>
                                        <td>Preço Produto</td>
                                        <td>". number_format($rowsProduto["preco_peca"], 2, ',', '.')."</td>
                                    </tr>";
                    $precoTab .= "</table>";
                if ($moduloB2BGrade) {
                    $tabTamanho = "";
                    $tamanhos = $objProduto->getGradeByProduto($rowsProduto["codigo_peca"]);
                    if (!empty($tamanhos)) {
                        $tabTamanho .= "<table class='table table-bordered table-fixed'>
                                        <tr>
                                            <td colspan='100%' class='titulotr tac' style='background:#25927e !important'>Tamanhos</td>
                                        </tr>
                                        <tr>";
                        foreach ($tamanhos as $k => $tm) {
                            $tabTamanho .= "
                                            <td class='tac'><b>".$tm."</b></td>
                                        ";
                        }
                        $tabTamanho .= "</tr></table>";

                    }
                }

        ?>
        <tr>
            <td class='tac'><?php echo $rowsProduto["ref_peca"];?></td>
            <td class='tal'><?php echo $rowsProduto["nome_peca"];?></td>
            <td class='tal'><?php echo $rowsProduto["nome_categoria"];?></td>
            <?php if ($moduloB2BGrade) {?>
            <td class='tal' nowrap>
                <?php 
                    echo $tabTamanho;
                ?>
            </td>
            <?php }?>
            <td class='tal' nowrap>
                <?php 
                    echo $precoTab;
                ?>
            </td>
            <td class='tac'><?php echo 'R$ '.number_format($rowsProduto["preco_promocional_peca"], 2, ',', '.');?></td>
            <td class='tac'>
                <?php echo ($rowsProduto["peca_destaque"] == 't') ? '<span class="label label-success">Sim</span>' : '<span class="label label-important">Não</span>';?>
            </td>
            <?php if ($objLoja->_controlaEstoque) {?>
            <td class='tac'><?php echo $rowsProduto["qtde_estoque_peca"];?></td>
            <?php }?>
            <td class='tac'>
                <?php echo ($rowsProduto["disponibilidade_peca"] == 't') ? '<span class="label label-success">Sim</span>' : '<span class="label label-important">Não</span>';?>
            </td>
            <td class='tac'>
                <a href="loja_cadastra_produto.php?acao=edit&loja_b2b_peca=<?php echo $rowsProduto["codigo_peca"];?>" class="btn btn-info btn-mini" title="Editar"><i class="icon-edit icon-white"></i></a>
                <a onclick="if (confirm('Deseja remover este registro?')) window.location='loja_cadastra_produto.php?acao=delete&loja_b2b_peca=<?php echo $rowsProduto["codigo_peca"];?>';return false;" href="#"  class="btn btn-danger btn-mini" title="Remover"><i class="icon-remove icon-white"></i></a>
            </td>
        </tr>
        <?php }?>
        </tbody>
    </table>
    <?php }?>
<?php include 'rodape.php';?>
