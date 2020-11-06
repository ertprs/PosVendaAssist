<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "cadastros";
include 'autentica_admin.php';

include 'funcoes.php';

$motivo_contato = $_REQUEST['motivo_contato'];

if (isset($_POST['btn_acao'])) {

    $descricao      = $_POST['descricao'];
    $ativo          = ($_POST['ativo']) ? 't' : 'f';

    if (empty($descricao)) {
        $msg_erro['campos'][] = "descricao";
        $msg_erro['msg'][]    = "Informe a descrição";
    } else {

        if (!empty($motivo_contato)) {
            $condVerifica = "AND motivo_contato != {$motivo_contato}";
        }

        $sqlVerificaCadastro = "SELECT motivo_contato 
                            FROM tbl_motivo_contato
                            WHERE UPPER(descricao) = UPPER('{$descricao}')
                            AND fabrica = {$login_fabrica}
                            {$condVerifica}";
        $resVerificaCadastro = pg_query($con, $sqlVerificaCadastro);

        if (pg_num_rows($resVerificaCadastro) > 0) {
            $msg_erro['campos'][] = "descricao";
            $msg_erro['msg'][]    = "Já existe um cadastro com essa descrição";
        }

    }

    if (count($msg_erro) == 0) {


        if (!empty($motivo_contato)) {

            $sql = "UPDATE tbl_motivo_contato 
                          SET descricao         = '{$descricao}',
                              ativo             = '{$ativo}'
                          WHERE motivo_contato = {$motivo_contato}
                          AND fabrica = {$login_fabrica}
                          ";

        } else {

            $sql = "INSERT INTO tbl_motivo_contato (descricao, ativo, fabrica)
                    VALUES ('{$descricao}', '{$ativo}', {$login_fabrica})";

        }

        pg_query($con, $sql);

        if (!pg_last_error()) {

            unset($descricao, $ativo, $motivo_contato);

            $msg_success = "Cadastro/Alteração realizado(a) com sucesso";

        } else {

            $msg_erro['msg'][]    = "Erro ao realizar operação";

        }

    }

}

$layout_menu = "cadastro";
$title = "CADASTRO DE CONTATO CALLCENTER";

include 'cabecalho_new.php';

$plugins = array("dataTable","select2");

include "plugin_loader.php";

if (strlen($motivo_contato) == 0) {
    $title_page = "Cadastro";
} else {
    $title_page = "Alteração de Cadastro";
} 
    
if (!empty($motivo_contato) && !isset($_POST['btn_acao'])) {

    $sqlAltera = "SELECT tbl_motivo_contato.descricao,
                         tbl_motivo_contato.ativo
                 FROM tbl_motivo_contato
                 WHERE tbl_motivo_contato.motivo_contato = {$motivo_contato}
                 AND   tbl_motivo_contato.fabrica = {$login_fabrica}
                ";
    $resAltera = pg_query($con, $sqlAltera);

    $descricao          = pg_fetch_result($resAltera, 0, 'descricao');
    $ativo              = pg_fetch_result($resAltera, 0, 'ativo');

}

if (count($msg_erro['msg']) > 0) {
?>
    <div class="alert alert-error">
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
} else if (!empty($msg_success)) { ?>
    <div class="alert alert-success">
        <h4><?= $msg_success ?></h4>
    </div>
<?php
}
?>

<div class="row">
    <b class="obrigatorio pull-right">* Campos obrigatórios</b>
</div>
<form name='frm_defeito' method='post' class="form-search form-inline tc_formulario" action='<?=$PHP_SELF?>'>
<div class="titulo_tabela "><?=$title_page?></div>
    <br/>
    <div class='row-fluid'>
        <div class='span2'></div>
            <div class='span6'>
                <div class='control-group <?=(in_array("descricao", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='descricao'>Descrição</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <h5 class='asteristico'>*</h5>
                                <input type="text" name="descricao" id="descricao" size="12" class='span8' value= "<?=$descricao?>">
                        </div>
                    </div>
                </div>
            </div>
        <div class='span2'>
            <div class='control-group <?=(in_array("ativo", $msg_erro["campos"])) ? "error" : ""?>'>
                <div class='controls controls-row'>
                    <br />
                     <label class="checkbox">
                        <input type="checkbox" name="ativo" value="t" <?= (isset($ativo) || !isset($_POST['btn_acao'])) ? 'checked' : "" ?>>
                        Ativo
                    </label>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
    <input type="hidden" name="motivo_contato" value="<?= $motivo_contato ?>" />
    <button class='btn btn-primary' name="btn_acao"><?= (!empty($motivo_contato)) ? "Alterar" : "Gravar" ?></button>
</p>
<br/>
</form>

    <div class='alert'>Para efetuar alterações, clique na descrição da providência.</div>
    <table id="motivo" class="table table-striped table-bordered table-hover table-fixed">
        <thead>
            <tr class="titulo_coluna" >
                <th>Motivo Contato</th>
                <th>Ativo</th>
            </tr>
        </thead>
        <tbody>
            <?php
                $sqlLista = "SELECT tbl_motivo_contato.descricao,
                                    tbl_motivo_contato.motivo_contato,
                                    tbl_motivo_contato.ativo
                             FROM tbl_motivo_contato
                             WHERE tbl_motivo_contato.fabrica = {$login_fabrica}";
                $resLista = pg_query($con, $sqlLista);

                while ($dadosMotivo= pg_fetch_object($resLista)) { ?>
                    <tr>
                        <td>
                            <a href="<?= $_SERVER['PHP_SELF']."?motivo_contato={$dadosMotivo->motivo_contato}" ?>"><?= $dadosMotivo->descricao ?></a>
                        </td>
                        <td class="tac">
                            <img src="imagens/status_<?= ($dadosMotivo->ativo == 't') ? 'verde' : 'vermelho' ?>.png">
                        </td>
                    </tr>
                <?php
                }
            ?>
        </tbody>
    </table>

<script>
    $(function(){
        
        $.dataTableLoad({ table: "#motivo" });

    });
</script>

<? include "rodape.php"; ?>
