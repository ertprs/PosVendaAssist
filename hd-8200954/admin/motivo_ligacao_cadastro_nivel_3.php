<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "cadastros";
include 'autentica_admin.php';

include 'funcoes.php';

$hd_providencia = $_REQUEST['hd_providencia'];

if (isset($_POST['btn_acao'])) {

    $descricao      = $_POST['descricao'];
    $ativo          = ($_POST['ativo']) ? 't' : 'f';
    $motivo_ligacao = $_POST['motivo_ligacao'];

    if (empty($descricao)) {
        $msg_erro['campos'][] = "descricao";
        $msg_erro['msg'][]    = "Informe a descrição";
    } else {

        if (!empty($hd_providencia)) {
            $condVerifica = "AND hd_providencia != {$hd_providencia}";
        }

        $sqlVerificaCadastro = "SELECT hd_providencia 
                            FROM tbl_hd_providencia
                            WHERE UPPER(descricao) = UPPER('{$descricao}')
                            AND fabrica = {$login_fabrica}
                            {$condVerifica}";
        $resVerificaCadastro = pg_query($con, $sqlVerificaCadastro);

        if (pg_num_rows($resVerificaCadastro) > 0) {
            $msg_erro['campos'][] = "descricao";
            $msg_erro['msg'][]    = "Já existe um cadastro com essa descrição";
        }

    }

    if (empty($motivo_ligacao)) {
        $msg_erro['campos'][] = "motivo_ligacao";
        $msg_erro['msg'][]    = "Selecione uma providência";
    }

    if (count($msg_erro) == 0) {


        if (!empty($hd_providencia)) {

            $sql = "UPDATE tbl_hd_providencia 
                          SET descricao         = '{$descricao}',
                              ativo             = '{$ativo}',
                              hd_motivo_ligacao = {$motivo_ligacao}
                          WHERE hd_providencia = {$hd_providencia}
                          AND fabrica = {$login_fabrica}
                          ";

        } else {

            $sql = "INSERT INTO tbl_hd_providencia (descricao, ativo, hd_motivo_ligacao, fabrica)
                    VALUES ('{$descricao}', '{$ativo}', {$motivo_ligacao}, {$login_fabrica})";

        }

        pg_query($con, $sql);

        if (!pg_last_error()) {

            unset($descricao, $ativo, $motivo_ligacao, $hd_providencia);

            $msg_success = "Cadastro/Alteração realizado(a) com sucesso";

        } else {

            $msg_erro['msg'][]    = "Erro ao realizar operação";

        }

    }

}

$layout_menu = "cadastro";
$title = "CADASTRO PROVIDÊNCIA NÍVEL 3";

include 'cabecalho_new.php';

$plugins = array("dataTable","select2");

include "plugin_loader.php";

if (strlen($hd_providencia) == 0) {
    $title_page = "Cadastro";
} else {
    $title_page = "Alteração de Cadastro";
} ?>

<script type="text/javascript">
    $(function () {

        $("#motivo_ligacao").select2();

        $(".btn_ativo_inativo").click(function(){

            var btn               = $(this);
            var defeito_reclamado = $(btn).data("defeito");
            var ativo             = $(btn).data("ativo");

            $.ajax({
                url: "defeito_reclamado_cadastro.php",
                type: "POST",
                data: { 
                    ajax_ativa_inativa : true,
                    defeito_reclamado : defeito_reclamado,
                    ativo : ativo
                },
                beforeSend:function(){
                    $(btn).text("Alterando...");
                },
                complete: function (data) {
                    if (data != 'erro') {
                        $(btn).toggleClass("btn-success btn-danger");

                        if (ativo == "Sim") {
                            $(btn).text("Inativo");
                        } else {
                            $(btn).text("Ativo");
                        }

                    } else {
                        alert("Erro ao Ativar/Inativar Defeito");
                    }
                }
            });
        });
    });

</script>

<?php
    
    if (!empty($hd_providencia) && !isset($_POST['btn_acao'])) {

        $sqlAltera = "SELECT tbl_hd_providencia.descricao,
                             tbl_hd_providencia.ativo,
                             tbl_hd_motivo_ligacao.hd_motivo_ligacao
                     FROM tbl_hd_providencia
                     JOIN tbl_hd_motivo_ligacao USING(hd_motivo_ligacao)
                     WHERE tbl_hd_providencia.hd_providencia = {$hd_providencia}
                     AND   tbl_hd_providencia.fabrica = {$login_fabrica}
                    ";
        $resAltera = pg_query($con, $sqlAltera);

        $descricao          = pg_fetch_result($resAltera, 0, 'descricao');
        $ativo              = pg_fetch_result($resAltera, 0, 'ativo');
        $motivo_ligacao  = pg_fetch_result($resAltera, 0, 'hd_motivo_ligacao');

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
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span6'>
            <div class='control-group <?=(in_array("motivo_ligacao", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='descricao'>Providência</label>
                <div class='controls controls-row'>
                    <h5 class='asteristico'>*</h5>
                    <select name="motivo_ligacao" id="motivo_ligacao" class="span12">
                        <option value=""></option>
                        <?php
                        $sqlMotivoLigacao = "SELECT tbl_hd_motivo_ligacao.hd_motivo_ligacao, 
                                                    tbl_hd_motivo_ligacao.descricao
                                             FROM tbl_hd_motivo_ligacao
                                             WHERE tbl_hd_motivo_ligacao.fabrica = {$login_fabrica}
                                             AND tbl_hd_motivo_ligacao.ativo IS TRUE";
                        $resMotivoLigacao = pg_query($con, $sqlMotivoLigacao);

                        while ($dadosMotivo = pg_fetch_object($resMotivoLigacao)) {

                            $selected = ($dadosMotivo->hd_motivo_ligacao == $motivo_ligacao) ? 'selected' : '';

                            ?>
                            <option value="<?= $dadosMotivo->hd_motivo_ligacao ?>" <?= $selected ?>><?= $dadosMotivo->descricao ?></option>
                        <?php
                        }

                        ?>
                    </select>
                </div>
            </div>
        </div>
    </div>
    <input type="hidden" name="hd_providencia" value="<?= $hd_providencia ?>" />
    <button class='btn btn-primary' name="btn_acao"><?= (!empty($hd_providencia)) ? "Alterar" : "Gravar" ?></button>
</p>
<br/>
</form>

    <div class='alert'>Para efetuar alterações, clique na descrição da providência.</div>
    <table id="providencia" class="table table-striped table-bordered table-hover table-fixed">
        <thead>
            <tr class="titulo_coluna" >
                <th>Providência nv. 3</th>
                <th>Providência</th>
                <th>Ativo</th>
            </tr>
        </thead>
        <tbody>
            <?php
                $sqlLista = "SELECT tbl_hd_providencia.descricao as descricao_providencia,
                                    tbl_hd_providencia.hd_providencia,
                                    tbl_hd_providencia.ativo,
                                    tbl_hd_motivo_ligacao.descricao as motivo_ligacao
                             FROM tbl_hd_providencia
                             JOIN tbl_hd_motivo_ligacao USING(hd_motivo_ligacao)
                             WHERE tbl_hd_providencia.fabrica = {$login_fabrica}";
                $resLista = pg_query($con, $sqlLista);

                while ($dadosProvidencia = pg_fetch_object($resLista)) { ?>
                    <tr>
                        <td>
                            <a href="<?= $_SERVER['PHP_SELF']."?hd_providencia={$dadosProvidencia->hd_providencia}" ?>"><?= $dadosProvidencia->descricao_providencia ?></a>
                            </td>
                        <td><?= $dadosProvidencia->motivo_ligacao ?></td>
                        <td class="tac">
                            <img src="imagens/status_<?= ($dadosProvidencia->ativo == 't') ? 'verde' : 'vermelho' ?>.png">
                        </td>
                    </tr>
                <?php
                }
            ?>
        </tbody>
    </table>

<script>
    $(function(){
        
        $.dataTableLoad({ table: "#providencia" });

    });
</script>

<? include "rodape.php"; ?>
