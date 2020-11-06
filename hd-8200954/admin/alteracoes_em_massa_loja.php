<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';
use Lojavirtual\Categoria;
use Lojavirtual\Produto;
$objCategoria= new Categoria();
$objProduto  = new Produto();
$dadosProduto = $objProduto->get();

$atualizar_em_massa = $_POST["atualizar_em_massa"];
$atualiza_categoria = $_POST["atualiza_categoria"];
$msg_erro = array();

//ATUALIZACAO DE CATEGORIAS
if (strlen($atualiza_categoria) > 0) {

    $nova_categoria = $_POST['nova_categoria'];
    $loja_b2b_peca  = $_POST['loja_b2b_peca'];

    if (empty($nova_categoria)) {
        $msg_erro["msg"][]    = "Nova Categoria não informada";
    }

    if (empty($loja_b2b_peca)) {
        $msg_erro["msg"][]    = "Escolha ao menos um produto";
    }
    if (count($msg_erro["msg"]) == 0) {

        $res = pg_query($con,"BEGIN");
        
        foreach ($loja_b2b_peca as $key => $loja_peca) {
            $retorno = $objProduto->updateCategoria($loja_peca, $nova_categoria);
            if ($retorno["erro"]) {
                $msg_erro["msg"][]  = $retorno["msn"];
            }
        }


        if (count($msg_erro["msg"]) > 0) {
            $res = pg_query($con,"ROLLBACK");
        } else {
            $res = pg_query($con,"COMMIT");
            $msg_sucesso = "Categoria(s) atualizada(s) com sucesso";
            echo "<meta http-equiv=refresh content=\"2;URL=alteracoes_em_massa_loja.php\">";
        }   
    }
    $atualizar_em_massa = true;
    $tipo_atualizacao   = $_POST["tipo_atualizacao"];

}


//ATUALIZACAO DE PRECO
if (strlen($atualiza_precos) > 0) {
    $loja_b2b_peca = $_POST['loja_b2b_peca'];
    $novo_preco  = $_POST['novo_preco'];
    $novo_preco_promocional_peca  = $_POST['novo_preco_promocional_peca'];


    if (empty($loja_b2b_peca)) {
        $msg_erro["msg"][]    = "Escolha ao menos um produto";
    }

    if (count($msg_erro["msg"]) == 0) {

        $res = pg_query($con,"BEGIN");
        
        foreach ($loja_b2b_peca as $key => $loja_peca) {
            $retorno = $objProduto->updatePrecos($loja_peca, $novo_preco[$key], $novo_preco_promocional_peca[$key]);
            if ($retorno["erro"]) {
                $msg_erro["msg"][]  = $retorno["msn"];
            }
        }

        if (count($msg_erro["msg"]) > 0) {
            $res = pg_query($con,"ROLLBACK");
        } else {
            $res = pg_query($con,"COMMIT");
            $msg_sucesso = "Preço(s) atualizado(s) com sucesso";
            echo "<meta http-equiv=refresh content=\"2;URL=alteracoes_em_massa_loja.php\">";
        }   

    }
    $atualizar_em_massa = true;
    $tipo_atualizacao   = $_POST["tipo_atualizacao"];

}

if (strlen($atualizar_em_massa) > 0) {
    $tipo_atualizacao   = $_POST["tipo_atualizacao"];
    if (empty($tipo_atualizacao)) {
        $msg_erro["msg"][]  = "Campo obrigatório não seclecionado.";
        $atualizar_em_massa = "";
    } 
}

$layout_menu = "cadastro";
$title = "Alterações em Massa - Loja Virtual";
include 'cabecalho_new.php';

$plugins = array(
    "shadowbox",
    "price_format",
    "mask",
    "dataTable",
    "multiselect"
);

include("plugin_loader.php");

if (count($msg_erro["msg"]) > 0) {
    echo '
    <div class="alert alert-error">
        <h4>'.implode("<br />", $msg_erro["msg"]).'</h4>
    </div>
    ';
}
if (strlen($msg_sucesso) > 0) {
    echo '
    <div class="alert alert-success">
        <h4>'.$msg_sucesso.'</h4>
    </div>
    ';
}

?>
<script language="javascript">
    $(function() {
        Shadowbox.init();
        var dataCustom = {"sDom": "<'row'<'span6'l><'span6'f>r>t<'row'<'span6'i><'span6'p>>",
                    "sPaginationType": "bootstrap",
                    "iDisplayLength": 50,
                    "oLanguage": {
                        "sLengthMenu": "_MENU_ Registros por página",
                        "sSearch": "Pesquisar",
                        "oPaginate": {
                            "sNext": "Próxima",
                            "sPrevious": "Anterior"
                        },
                        "sInfo": "Mostrando de _START_ a _END_ de _TOTAL_ registros",
                        "sInfoFiltered": " ( filtrando _MAX_ registros ) ",
                        "sInfoEmpty": "Mostrando de 0 a _END_ de _TOTAL_ registros",
                        "sZeroRecords": "Nenhum registro encontrado"
                    }}
        $("#tabela").dataTable(
            {
                aoColumnDefs: [{ "bSortable": false, "aTargets": [0]}],
                "sDom": "<'row'<'span6'l><'span6'f>r>t<'row'<'span6'i><'span6'p>>",
                    "sPaginationType": "bootstrap",
                    "iDisplayLength": 50,
                    "oLanguage": {
                        "sLengthMenu": "_MENU_ Registros por página",
                        "sSearch": "Pesquisar",
                        "oPaginate": {
                            "sNext": "Próxima",
                            "sPrevious": "Anterior"
                        },
                        "sInfo": "Mostrando de _START_ a _END_ de _TOTAL_ registros",
                        "sInfoFiltered": " ( filtrando _MAX_ registros ) ",
                        "sInfoEmpty": "Mostrando de 0 a _END_ de _TOTAL_ registros",
                        "sZeroRecords": "Nenhum registro encontrado"
                    }
            }
        );
        $("#tabela_preco").dataTable(
            {
                aoColumnDefs: [{ "bSortable": false, "aTargets": [0,2,3,4,5]}],
                "sDom": "<'row'<'span6'l><'span6'f>r>t<'row'<'span6'i><'span6'p>>",
                    "sPaginationType": "bootstrap",
                    "iDisplayLength": 50,
                    "oLanguage": {
                        "sLengthMenu": "_MENU_ Registros por página",
                        "sSearch": "Pesquisar",
                        "oPaginate": {
                            "sNext": "Próxima",
                            "sPrevious": "Anterior"
                        },
                        "sInfo": "Mostrando de _START_ a _END_ de _TOTAL_ registros",
                        "sInfoFiltered": " ( filtrando _MAX_ registros ) ",
                        "sInfoEmpty": "Mostrando de 0 a _END_ de _TOTAL_ registros",
                        "sZeroRecords": "Nenhum registro encontrado"
                    }
            }
        );

        $(".novo_preco").priceFormat({
            prefix: '',
            thousandsSeparator: '',
            centsSeparator: '.',
            centsLimit: 2
        });

        $(".novo_preco_promocional_peca").priceFormat({
            prefix: '',
            thousandsSeparator: '',
            centsSeparator: '.',
            centsLimit: 2
        });

    });
    checked=false;
    function checkedAll(frm) {
        var fm = document.getElementById(frm);
         if (checked == false) {
               checked = true
        } else {
            checked = false
        }
        for (var i =0; i < fm.elements.length; i++) {
            fm.elements[i].checked = checked;
        }
    }
</script>
<?php if (strlen($atualizar_em_massa) == 0) {?>

<form name='frm_relatorio' METHOD='POST' enctype="multipart/form-data" ACTION='alteracoes_em_massa_loja.php' align='center' class='form-search form-inline tc_formulario' >
    <input type='hidden' name='atualizar_em_massa' value='true' />
    <div class='titulo_tabela'>Tipo de Ação</div><br/>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span8'>
            <b>O que deseja atualizar?</b>
            <div class='control-group tac'>
                <h5 class='asteristico'>*</h5>
                <select name="tipo_atualizacao" required id="tipo_atualizacao" class="span12">
                    <option value="">Escolha ...</option>
                    <option value="categorias" <?php echo ($tipo_atualizacao == "categorias") ? 'selected' : '';?>>Categorização de Produtos</option>
                    <option value="precos" <?php echo ($tipo_atualizacao == "precos") ? 'selected' : '';?>>Preços do Produtos</option>
                </select>
            </div>
        </div>
        <div class='span2'></div>
    </div>
    <p><br/>
        <button class='btn btn-success' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Iniciar</button>
        <input type='hidden' id="btn_click" name='btn_acao' value='' />

    </p><br/>
</form> <br />

<?php }
if (strlen($atualizar_em_massa) > 0 && strlen($msg_erro) == 0) {?>


    <?php if ($tipo_atualizacao == "categorias") {?>
    <form id='frm_atualiza_categoria' name='frm_atualiza_categoria' METHOD='POST' enctype="multipart/form-data" ACTION='alteracoes_em_massa_loja.php' align='center' class='form-search form-inline' >
    <div class="tc_formulario">
            <input type='hidden' name='atualiza_categoria' value='true' />
            <input type='hidden' name='tipo_atualizacao' value='<?php echo $tipo_atualizacao;?>' />

            <div class='titulo_tabela'>Categorização de Produtos</div><br/>
            <div class='row-fluid'>
                <div class='span2'></div>
                <div class='span8'>
                    <b>Categoria</b>
                    <div class='control-group tac'>
                        <h5 class='asteristico'>*</h5>
                        <select name="nova_categoria" class='span12' id="nova_categoria">
                            <option value="" selected="selected"> - Escolha uma categoria - </option>
                            <?php 
                                $categoriasBusca = $objCategoria->get();
                                if (!empty($categoriasBusca)) {
                                    foreach ($categoriasBusca as $vCat) {
                                        $selected = ($nova_categoria == $vCat['categoria']) ? 'selected' : '';
                            ?>
                            <option value="<?php echo $vCat['categoria'];?>" <?php echo $selected;?>><?php echo $vCat['descricao'];?></option>
                            <?php  }
                                }
                            ?>
                        </select>
                    </div>
                </div>
                <div class='span2'></div>
            </div>
    </div><br />
    <table class='table table-striped table-bordered table-hover table-fixed' id='tabela'>
        <thead>
            <tr class='titulo_coluna' >
                <th class='tac no-sort'><input type="checkbox" onclick="checkedAll('frm_atualiza_categoria');"></th>
                <th class='tal'>Produto</th>
                <th class='tal'>Categoria Atual</th>
            </tr>
        </thead>
        <tbody>
           <?php
                foreach ($dadosProduto as $key => $rowsProduto) {
            ?>
            <tr>
                <td class='tac'>
                    <input type="checkbox" name="loja_b2b_peca[]" id="loja_b2b_peca" value="<?php echo $rowsProduto["codigo_peca"];?>">
                </td>
                <td class='tal'><?php echo $rowsProduto["codigo_peca"];?> - <?php echo $rowsProduto["nome_peca"];?></td>
                <td class='tal'><?php echo $rowsProduto["nome_categoria"];?></td>
            </tr>
            <?php }?>
        </tbody>
    </table>
        <p class="tac"><br/>
            <button class='btn btn-primary' id="btn_acao" type="button"  onclick="submit('#frm_atualiza_categoria');">Gravar</button>
            <input type='hidden' id="btn_click" name='btn_acao' value='' />
            <a class='btn' href='alteracoes_em_massa_loja.php'>Cancelar</a>

        </p><br/>
    </form> <br />

    <?php }?>

    <?php if ($tipo_atualizacao == "precos") {?>
    <form id='frm_atualiza_precos' name='frm_atualiza_precos' METHOD='POST' enctype="multipart/form-data" ACTION='alteracoes_em_massa_loja.php' align='center' class='form-search form-inline' >
        <input type='hidden' name='atualiza_precos' value='true' />
        <input type='hidden' name='tipo_atualizacao' value='<?php echo $tipo_atualizacao;?>' />

        <div class='titulo_tabela'>Atualização em Massa  - Preços do Produtos</div><br/>
        <br />
        <table class='table table-striped table-bordered table-hover table-fixed' id='tabela_preco'>
            <thead>
                <tr class='titulo_coluna' >
                    <th width="2%" class='tac no-sort'><input type="checkbox" onclick="checkedAll('frm_atualiza_precos');"></th>
                    <th class='tal'>Produto</th>
                    <th nowrap>Preço Atual</th>
                    <th nowrap>Novo Preço</th>
                    <th nowrap>Preço Promocional Atual</th>
                    <th nowrap>Novo Preço Promocional </th>
                </tr>
            </thead>
            <tbody>
               <?php
                    foreach ($dadosProduto as $key => $rowsProduto) {
                ?>
                <tr>
                    <td class='tac'>
                        <input type="checkbox" name="loja_b2b_peca[]" id="loja_b2b_peca" value="<?php echo $rowsProduto["codigo_peca"];?>">
                    </td>
                    <td class='tal'><?php echo $rowsProduto["codigo_peca"];?> - <?php echo $rowsProduto["nome_peca"];?></td>
                    <td class='tac'><?php echo 'R$ '.number_format($rowsProduto["preco_peca"], 2, ',', '.');?></td>
                    <td class='tac'>
                        <div class="input-prepend">
                            <span class="add-on">R$</span>
                            <input type="text" style="width:60% !important" name="novo_preco[]" class="novo_preco">
                        </div>
                    </td>
                    <td class='tac'><?php echo 'R$ '.number_format($rowsProduto["preco_promocional_peca"], 2, ',', '.');?></td>
                    <td class='tac'>
                       <div class="input-prepend">
                            <span class="add-on">R$</span>
                            <input type="text" style="width:60% !important" name="novo_preco_promocional_peca[]" class="novo_preco_promocional_peca">
                        </div>
                    </td>
                </tr>
                <?php }?>
            </tbody>
        </table>

        <p class="tac"><br/>
            <button class='btn btn-primary' id="btn_acao" type="button"  onclick="submit('#frm_atualiza_categoria');">Gravar</button>
            <input type='hidden' id="btn_click" name='btn_acao' value='' />
            <a class='btn' href='alteracoes_em_massa_loja.php'>Cancelar</a>

        </p><br/>
    </form> <br />

    <?php }?>







<?php }?>