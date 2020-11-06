<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

$layout_menu = "cadastro";
$title       = "Consumidores";
include 'cabecalho_new.php';

function getAll($cpf_busca = NULL, $xfabrica = NULL) {
    global $con;

    $limit = " LIMIT 500";
    $cond  = "";
    if (!empty($cpf_busca)) {
        $cond  = " AND cpf ILIKE '%".$cpf_busca."%'";
    }
    if (!empty($xfabrica)) {
        $cond  = " AND fabrica = ".$xfabrica;
    }

    $sql = "SELECT tbl_cliente.*,
                  tbl_cidade.nome AS cidade_nome,
                  tbl_cidade.estado AS estado
            FROM tbl_cliente
            JOIN tbl_cidade ON tbl_cidade.cidade = tbl_cliente.cidade
            WHERE 1=1 
            AND replace(trim(tbl_cliente.nome::text), '.', '') is not null 
            AND replace(trim(tbl_cliente.nome::text), '.', '') <> '' 
             {$cond}
        ORDER BY tbl_cliente.nome ASC {$limit}";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0 ) {
        $dados = pg_fetch_all($res);
        foreach ($dados as $key => $rows) {
           if (strlen(trim($rows["nome"])) == 0) {
                unset($dados[$key]);
                continue;
           }
        }
        return $dados;
    } else {
        return array();
    }
}
if (in_array($login_fabrica, array(42))) {
    $dadosConsumidor = getAll($_POST["cpf"], $login_fabrica);
} else{ 
    $dadosConsumidor = getAll($_POST["cpf"]);
}

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
        $.autocompleteLoad(Array( "consumidor"));

        $("span[rel=lupa]").click(function () {
            $.lupa($(this));
        });
    });
    function retorna_consumidor(retorno){
            $("#cliente").val(retorno.cliente);
            $("#cpf").val(retorno.cpf);
            $("#nome").val(retorno.nome);
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
</style>
  
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
<form name='frm_relatorio' METHOD='POST' ACTION='consumidor.php' align='center' class='form-search form-inline tc_formulario' >
        <input type="hidden" name="pesquisa" value="true">
        <div class='titulo_tabela '>Parâmetros de Pesquisa</div>
        <br/>
            <div class='row-fluid'>
        <div class='span2'></div>
            <div class="span4">
                <div class="<? echo $controlgrup?>">
                    <label class='control-label' for='nome'>Nome Consumidor</label>
                    <div class='controls controls-row'>
                        <div class='span11 input-append'>
                            <h5 class='asteristico'>*</h5>
                            <input type="text" name="nome" id="nome" class="span12" value="<? echo $nome ?>" >
                            <span class="add-on" rel="lupa"><i class="icon-search" ></i>
                            <input type="hidden" name="lupa_config" tipo="consumidor" parametro="nome_consumidor">
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="span4">
                <div class='control-group <?=(in_array("consumidor", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='cpf'>CPF/CNPJ Consumidor</label>
                    <div class='controls controls-row'>
                        <div class='span11 input-append'>
                            <input type="text" name="cpf" id="cpf" class='span12' value="<? echo $cpf ?>" >
                            <span class='add-on' rel="lupa"><i class='icon-search' ></i>
                            <input type="hidden" name="lupa_config" tipo="consumidor" parametro="cnpj">
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        <div class='span2'></div>
    </div>
        <p><br/>
            <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
            <a class='btn btn-primary' href='consumidor_cadastro.php'>Cadastrar Consumidor</a>
            <input type='hidden' id="btn_click" name='btn_acao' value='' />
        </p><br/>
    </form> <br />

    <?php
        if ($dadosConsumidor["erro"]) {
            echo '<div class="alert alert-error"><h4>'.$dadosConsumidor["msn"].'</h4></div>';
        } else {
    ?>
</div>
<div class="container-fluid">
    <table class='table table-striped table-bordered table-hover table-fixed' id='tabela'>
        <thead>
            <tr class='titulo_coluna' >
                <th class='tal'>Nome</th>
                <th class='tal'>Nome Fantasia</th>
                <th class='tal'>CPF/CNPJ</th>
                <th class='tac'>Fone</th>
                <th class='tal'>E-mail</th>
            </tr>
        </thead>
        <tbody>
        <?php
            foreach ($dadosConsumidor as $key => $rows) {
        ?>
        <tr>
            <td class='tal'><?php echo $rows["nome"];?></td>
            <td class='tal'><?php echo $rows["nome_fantasia"];?></td>
            <td class='tal'><?php echo $rows["cpf"];?></td>
            <td class='tac'><?php echo $rows["fone"];?></td>
            <td class='tal'><?php echo $rows["email"];?></td>
        </tr>
        <?php }?>
        </tbody>
    </table>
    <?php }?>
</div>
<?php include 'rodape.php';?>