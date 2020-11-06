<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';
include_once "../class/tdocs.class.php";
use Lojavirtual\Categoria;

$objCategoria   = new Categoria();
$dadosCategoria = $objCategoria->get();

$url_redir = "<meta http-equiv=refresh content=\"2;URL=loja_categoria_produto.php\">";

if ($_GET["acao"] == "delete" && $_GET["categoria"] > 0) {
    $categoria   = $_GET['categoria'];

    if (empty($categoria)) {
        $msg_erro["msg"][]    = "Categoria não encontrada!";
    }

    if (count($msg_erro) == 0) {

        $res = pg_query($con,"BEGIN");

        if (!empty($objCategoria)) {
            $retorno = $objCategoria->delete($categoria);
            if ($retorno["erro"]) {
                $msg_erro["msg"][] = $retorno["msn"];
            } else {
                $msg_sucesso["msg"][] = 'Categoria removida com sucesso!';
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

if ($_GET["acao"] == "edit" && $_GET["categoria"] > 0) {
    
    $categoria   = $_GET['categoria'];
    $dataCat     = $objCategoria->get($categoria);
    $descricao   = $dataCat['descricao'];
    $tipo_acao   = "edit";
}

if ($_POST["btn_acao"] == "submit" && $tipo_acao  != "edit") {

    $descricao   = $_POST['descricao'];

    if (empty($descricao)) {
        $msg_erro["msg"][]    = "Preencha os campos obrigatórios";
        $msg_erro["campos"][] = "descricao";
    }

    if (count($msg_erro) == 0) {

        $res = pg_query($con,"BEGIN");
        $dataSave = array(
                        "descricao" => $descricao
                     );

        if (!empty($objCategoria)) {
            $retorno = $objCategoria->save($dataSave);

            if ($retorno["erro"]) {
                $msg_erro["msg"][] = $retorno["msn"];
            } else {
                $msg_sucesso["msg"][] = 'Categoria cadastrada com sucesso!';
            }
        }

        if (count($msg_erro) > 0) {
            $res = pg_query($con,"ROLLBACK");
            $descricao   = $_POST['descricao'];
        } else {
            $res = pg_query($con,"COMMIT");
            echo $url_redir;
        }
    }
}

if ($_POST["btn_acao"] == "submit" && $tipo_acao  == "edit") {

    $categoria   = 0;
    $descricao   = $_POST['descricao'];
    $categoria   = $_POST['categoria'];

    if (empty($descricao)) {
        $msg_erro["msg"][]    = "Preencha os campos obrigatórios";
        $msg_erro["campos"][] = "descricao";
    }

    if (count($msg_erro) == 0) {

        $res = pg_query($con,"BEGIN");
        $dataSave = array(
                        "categoria"     => $categoria,
                        "descricao"     => $descricao
                     );

        if (!empty($objCategoria)) {
            $retorno = $objCategoria->update($dataSave);

            if ($retorno["erro"]) {
                $msg_erro["msg"][] = $retorno["msn"];
            } else {
                $msg_sucesso["msg"][] = 'Categoria atualizada com sucesso!';
            }
        }

        if (count($msg_erro) > 0) {
            $res = pg_query($con,"ROLLBACK");
            $categoria   = $_POST['categoria'];
            $descricao   = $_POST['descricao'];
        } else {
            $res = pg_query($con,"COMMIT");
            echo $url_redir;
        }
    }
}

$layout_menu = "cadastro";
$title = "Categoria de Produtos - Loja Virtual";
include 'cabecalho_new.php';

$plugins = array(
    "shadowbox",
    "dataTable",
    "multiselect"
);

include("plugin_loader.php");
?>
<style>
    .icon-edit {
        background-position: -95px -75px;
    }
    .icon-remove {
        background-position: -312px -3px;
    }
    .icon-search {
        background-position: -48px -1px;
    }
</style>
<script language="javascript">
    var hora = new Date();
    var engana = hora.getTime();

    $(function() {
        Shadowbox.init();
        $.dataTableLoad("#tabela");
        $(".multiple").multiselect({
           selectedText: "# of # selected"
        });

    });
</script>
    <?php
        if (empty($objCategoria->_loja)) {
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

    <form name='frm_relatorio' METHOD='POST' enctype="multipart/form-data" ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
        <?php if ($tipo_acao == "edit") {?>
        <input type="hidden" name="tipo_acao" value="edit">
        <input type="hidden" name="categoria" value="<?php echo $categoria;?>">
        <?php } else {?> 
        <input type="hidden" name="tipo_acao" value="add">
        <?php }?> 
        <div class='titulo_tabela '>Cadastro</div>
        <br/>

        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span8'>
                <div class='control-group <?=(in_array("descricao", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label'>Nome da Categoria</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <h5 class='asteristico'>*</h5>
                            <input type="text" class="span12" value="<?php echo $descricao;?>" name="descricao" id="descricao">
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>
        <p><br/>
                <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Gravar</button>
                <input type='hidden' id="btn_click" name='btn_acao' value='' />
            </p><br/>
    </form> <br />
    <?php
        if ($dadosCategoria["erro"]) {
            echo '<div class="alert alert-error"><h4>'.$dadosCategoria["msn"].'</h4></div>';
        } else {
    ?>
    <table class='table table-striped table-bordered table-hover table-fixed' id='tabela'>
        <thead>
            <tr class='titulo_coluna' >
                <th align="left">Categoria</th>
                <th class='tal'>Descrição</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
        <?php 
        foreach ($dadosCategoria as $kCategoria => $rowsCategoria) {
        ?>
        <tr>
            <td class='tac'><?php echo  $rowsCategoria["categoria"];?></td>
            <td class='tal'><?php echo  $rowsCategoria["descricao"];?></td>
            <td class='tac'>
                <?php echo ($rowsCategoria["ativo"] == 't') ? '<span class="label label-success">Ativo</span>' : '<span class="label label-important">Inativo</span>';?>
            </td>
            <td class='tac'>
                <a href="loja_categoria_produto.php?acao=edit&categoria=<?php echo $rowsCategoria["categoria"];?>" class="btn btn-info btn-mini" title="Editar"><i class="icon-edit icon-white"></i></a>
                <a onclick="if (confirm('Deseja remover este registro?')) window.location='loja_categoria_produto.php?acao=delete&categoria=<?php echo $rowsCategoria["categoria"];?>';return false" href="#" class="btn btn-danger btn-mini" title="Remover"><i class="icon-remove icon-white"></i></a>
            </td>
        </tr>
        <?php }?>
        </tbody>
    </table>
    <?php }?>
</div> 
<?php include 'rodape.php';?>
