<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios = "cadastros";

include 'autentica_admin.php';
include 'funcoes.php';

if(isset($_GET["status_analise_peca"])){

    $status_analise = $_GET["status_analise_peca"];

    $sql = "SELECT descricao, ativo FROM tbl_status_analise_peca WHERE status_analise_peca = {$status_analise} AND fabrica = {$login_fabrica}";
    $res = pg_query($con, $sql);

    if(pg_num_rows($res) > 0){

        $descricao = pg_fetch_result($res, 0, "descricao");
        $ativo     = pg_fetch_result($res, 0, "ativo");

    }

}

if(isset($_POST["btn_acao"])){

    $descricao      = trim($_POST["descricao"]);
    $ativo          = (isset($_POST["ativo"])) ? $_POST["ativo"] : "f";
    $status_analise = $_POST["status_analise"];

    if(strlen($descricao) == 0){

        $msg_erro["msg"]["obg"] ="Por favor insira a descrição";
        $msg_erro["campos"][]   = "descricao";

    }

    if(count($msg_erro["msg"]) == 0){

        if(strlen($status_analise) == 0){

            $sql = "INSERT INTO tbl_status_analise_peca (descricao, fabrica, ativo) VALUES ('{$descricao}', $login_fabrica, '{$ativo}') ";

        }else{

            $sql = "UPDATE tbl_status_analise_peca SET descricao = '{$descricao}', ativo = '{$ativo}' WHERE status_analise_peca = {$status_analise} AND fabrica = {$login_fabrica}";

        }

        $res = pg_query($con, $sql);

        if(strlen(pg_last_error()) == 0){

            $msg = (strlen($status_analise) > 0) ? "Posição da Análise Alterada com Sucesso" : "Posição de Análise Cadastrada com Sucesso";

        }

        $descricao = "";
        $ativo = "";
        $status_analise = "";

    }

}

$layout_menu = "cadastro";
$title = "POSIÇÃO DA ANÁLISE DA PEÇA";

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
    

    <input type="hidden" name="status_analise" value="<?= $status_analise; ?>">
    
    <div class='titulo_tabela'>Cadastro de Posição da Análise</div>
    
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
                        <input type="checkbox" id="ativo" name="ativo" value="t" <?php echo ($ativo == "t") ? "checked" : "" ?> >
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

<div class='alert tac'>Para efetuar alterações, clique na descrição da Posição da Análise.</div>

<div class="container">
    <table id="tecnicos-list" class='table table-striped table-bordered table-hover' >
        <thead>
            <tr class='titulo_tabela'>
                <th colspan="2">Relação de Posição da Análise de Peça cadastradas</th>
            </tr>
            <tr class='titulo_coluna' >
                <th>Descrição</th>
                <th>Ativo</th>
            </tr>
        </thead>

        <tbody>
            <?php

            $sql = "SELECT status_analise_peca, descricao, ativo FROM tbl_status_analise_peca WHERE fabrica = {$login_fabrica} ORDER BY descricao ASC";
            $res = pg_query($con, $sql);

            if(pg_num_rows($res) == 0){

                echo "
                <tr>
                    <td>Nenhum resultado encontrado.</td>
                </tr>";

            }else{

                for ($i = 0; $i < pg_num_rows($res); $i++) {

                    $status_analise_peca = pg_fetch_result($res, $i, "status_analise_peca");
                    $descricao           = pg_fetch_result($res, $i, "descricao");
                    $ativo               = pg_fetch_result($res, $i, "ativo");

                    ?>
                    <tr>
                        <td><a href="<?php echo $_SERVER['PHP_SELF'] . '?status_analise_peca=' . $status_analise_peca ?>"><?php echo $descricao ?><a></td>
                        <td class='tac'><img src="imagens/<?=($ativo == 't') ? 'status_verde.png' : 'status_vermelho.png'?>" title="<?=($ativo == 't') ? 'Origem de Recebimento ativo' : 'Origem de Recebimento inativo'?>"/></td>
                    </tr>         
            <? } } ?>

        </tbody>
    </table>
</div>

<?php
    
    include "rodape.php";

?>
                        