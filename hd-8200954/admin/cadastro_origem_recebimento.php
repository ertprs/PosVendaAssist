<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios = "cadastros";

include 'autentica_admin.php';
include 'funcoes.php';

if(isset($_GET["origem_recebimento"])){

    $origem_recebimento = $_GET["origem_recebimento"];

    $sql = "SELECT descricao, ativo FROM tbl_origem_recebimento WHERE origem_recebimento = {$origem_recebimento} AND fabrica = {$login_fabrica}";
    $res = pg_query($con, $sql);

    if(pg_num_rows($res) > 0){

        $descricao = pg_fetch_result($res, 0, "descricao");
        $ativo = pg_fetch_result($res, 0, "ativo");

    }

}

if(isset($_POST["btn_acao"])){

    $descricao = trim($_POST["descricao"]);
    $ativo = (isset($_POST["ativo"])) ? $_POST["ativo"] : "f";
    $origem_recebimento = $_POST["origem_recebimento"];

    if(strlen($descricao) == 0){

        $msg_erro["msg"]["obg"] ="Por favor insira a descrição";
        $msg_erro["campos"][]     = "descricao";

    }

    if(count($msg_erro["msg"]) == 0){

        if(strlen($origem_recebimento) == 0){

            $sql = "INSERT INTO tbl_origem_recebimento (descricao, ativo, fabrica) VALUES ('{$descricao}', '{$ativo}', $login_fabrica) ";

        }else{

            $sql = "UPDATE tbl_origem_recebimento SET descricao = '{$descricao}', ativo = '$ativo' WHERE origem_recebimento = {$origem_recebimento} AND fabrica = {$login_fabrica}";

        }

        $res = pg_query($con, $sql);

        if(strlen(pg_last_error()) == 0){

            $msg = (strlen($status_analise) > 0) ? "Origem de Recebimento Alterado com Sucesso" : "Origem de Recebimento Cadastrado com Sucesso";

        }

        $descricao = "";
        $ativo = "";
        $origem_recebimento = "";

    }

}

$layout_menu = "cadastro";
$title = "ORIGEM DE RECEBIMENTO";

include 'cabecalho_new.php';

$plugins = array(
    "datatable",
    "mask",
);

include("plugin_loader.php");

if (count($msg_erro["msg"]) > 0) {
    ?>
    <div class="alert alert-error">
        <h4><?= implode("<br />", $msg_erro["msg"]) ?></h4>
    </div>
    <?php
}

if (!empty($msg)) {
    ?>
    <div class="alert alert-success">
        <h4>
        <?php
            if (!empty($msg)) {
                echo $msg;
            }
        ?>
        </h4>
    </div>
<?php } ?>

<div class="row">
    <b class="obrigatorio pull-right">* Campos obrigatórios</b>
</div>

<form name='frm_cadastro' method='POST' action='<?= $PHP_SELF ?>' class='form-search form-inline tc_formulario' >
    

    <input type="hidden" name="origem_recebimento" value="<?= $origem_recebimento; ?>">
    
    <div class='titulo_tabela'>Cadastro de Origem de Recebimento</div>
    
    <br/>
    
    <div class="row-fluid">

        <div class="span2"></div>

        <div class='span7'>
            <div class='control-group <?= (in_array("descricao", $msg_erro["campos"])) ? "error" : "" ?>'>
                <label class='control-label' for='descricao'>Descrição</label>
                <div class='controls controls-row'>
                    <div class='span12 input-append'>
                        <h5 class='asteristico'>*</h5>
                        <input type="text" id="descricao" name="descricao" class='span12' maxlength="200" value="<?php echo $descricao ?>" >
                    </div>
                </div>
            </div>
        </div>

        <div class='span1'>
            <div class='control-group '>
                <label class='control-label' for=''>Ativo</label>
                <div class='controls controls-row'>
                    <div class='span12 input-append'>
                        <input type="checkbox" id="ativo" name="ativo" value="t" <?= ($ativo == 't') ? "checked" : "" ?> >
                    </div>
                </div>
            </div>
        </div>
    
    </div>

    <p>

    <br/>
        <input type='hidden' id="btn_click" name='btn_acao' value='' />
        <button class='btn' type="button" onclick="submitForm($(this).parents('form'));">Gravar</button>
    </p>

    <br/>

</form>

<div class='alert tac'>Para efetuar alterações, clique na descrição da Origem de Recebimento.</div>

<div class="container">
    <table id="tecnicos-list" class='table table-striped table-bordered table-hover' >
        <thead>
            <tr class='titulo_tabela'>
                <th colspan="2">Relação de Origem de Recebimento de Peça cadastrados</th>
            </tr>
            <tr class='titulo_coluna' >
                <th>Descrição</th>
                <th>Ativo</th>
            </tr>
        </thead>

        <tbody>
            <?php

            $sql = "SELECT origem_recebimento, descricao, ativo FROM tbl_origem_recebimento WHERE fabrica = {$login_fabrica} ORDER BY descricao ASC";
            $res = pg_query($con, $sql);

            if(pg_num_rows($res) == 0){

                echo "
                <tr colspan='2'>
                    <td>Nenhum resultado encontrado.</td>
                </tr>";

            }else{

                for ($i = 0; $i < pg_num_rows($res); $i++) {

                    $origem_recebimento = pg_fetch_result($res, $i, "origem_recebimento");
                    $descricao = pg_fetch_result($res, $i, "descricao");
                    $ativo = pg_fetch_result($res, $i, "ativo");

                    ?>
                    <tr>
                        <td><a href="<?php echo $_SERVER['PHP_SELF'] . '?origem_recebimento=' . $origem_recebimento ?>"><?php echo $descricao ?><a></td>
                        <td class='tac'><img src="imagens/<?=($ativo == 't') ? 'status_verde.png' : 'status_vermelho.png'?>" title="<?=($ativo == 't') ? 'Origem de Recebimento ativo' : 'Origem de Recebimento inativo'?>"/></td>
                    </tr>         
            <? } } ?>

        </tbody>
    </table>
</div>

<?php
    
    include "rodape.php";

?>
                        