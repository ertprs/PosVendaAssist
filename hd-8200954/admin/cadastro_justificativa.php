<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios = "cadastros";
include 'autentica_admin.php';
include_once "../helpdesk.inc.php";

$layout_menu = "cadastro";
$title = "CADASTRO DE JUSTIFICATIVA";

if (filter_input(INPUT_GET,'justificativa')) {
    $justificativa = filter_input(INPUT_GET,'justificativa');
    $sqlBusca = "
        SELECT  descricao AS justificativa_descricao,
                ativa
        FROM    tbl_justificativa
        WHERE   fabrica = $login_fabrica
        AND     justificativa = $justificativa
    ";
    $resBusca = pg_query($con,$sqlBusca);

    $justificativa_descricao    = pg_fetch_result($resBusca,0,justificativa_descricao);
    $ativo                      = pg_fetch_result($resBusca,0,ativa);
}

if (strlen($_POST['btn_acao']) > 0) {
    $justificativa              = filter_input(INPUT_POST,"justificativa");
    $justificativa_descricao    = filter_input(INPUT_POST,"justificativa_descricao");
    $ativo                      = filter_input(INPUT_POST,"ativo");

    $sqlBusca = "
        SELECT  justificativa
        FROM    tbl_justificativa
        WHERE   fabrica = $login_fabrica
        AND     descricao ILIKE '$justificativa_descricao'
    ";
    $resBusca = pg_query($con,$sqlBusca);

    if (pg_num_rows($resBusca) > 0) {
        $justificativa = pg_fetch_result($resBusca,0,justificativa);
    }

    $ativo = (!empty($ativo)) ? 'TRUE': 'FALSE';
    pg_query($con,"BEGIN TRANSACTION");

    if (empty($justificativa)) {
        $sql = "
            INSERT INTO tbl_justificativa (
                fabrica,
                descricao,
                ativa
            ) VALUES (
                $login_fabrica,
                '$justificativa_descricao',
                $ativo
            )";
    } else {

        $sql = "
            UPDATE  tbl_justificativa
            SET     descricao = '$justificativa_descricao',
                    ativa = $ativo
            WHERE   fabrica = $login_fabrica
            AND     justificativa = $justificativa
        ";
    }
    $res = pg_query($con,$sql);

    if (pg_last_error($con)) {
        $msg_erro['msg'][] = "Erro ao gravar Justificativa: ".pg_last_error($con);
        pg_query($con,"ROLLBACK TRANSACTION");
    } else {
        pg_query($con,"COMMIT TRANSACTION");
        $msg_success = TRUE;
    }
}

include "cabecalho_new.php";

$plugins = array(
                "shadowbox",
                "mask",
                "dataTable"
                );
include ("plugin_loader.php");

if (count($msg_erro["msg"]) > 0) {
    echo '
    <div class="alert alert-error">
        <h4>'.$msg_erro["msg"][0].'</h4>
    </div>
    ';
} else if (strlen($msg_success) > 0) {
    echo '
    <div class="alert alert-success">
        <h4>Gravado com sucesso</h4>
    </div>
    ';
}
echo  '
    <div class="alert" id="class_alert_mensagem" style="display:none">
        <h4 id="txt_mensagem"></h4>
    </div>';
?>
<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form name='frm_justificativa' METHOD='POST' ACTION='<?=$PHP_SELF?>' class='form-search form-inline tc_formulario'>
    <div class='titulo_tabela'>CADASTRO</div>
    <br />
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group <?=(in_array("justificativa", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='justificativa'>Justificativa</label>
                <div class='controls controls-row'>
                    <div class='span12 input-append'>
                        <h5 class='asteristico'>*</h5>
                        <input type="hidden" name="justificativa" id="justificativa" class='span12' value="<?=$justificativa?>" />&nbsp;
                        <input type="text" name="justificativa_descricao" id="justificativa_descricao" class='span12' value="<?=$justificativa_descricao?>" maxlength="80" />&nbsp;
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'>
            <div class='control-group'>
                <label class='control-label' for='justificativa'>Ativo</label>
                <div class='controls controls-row'>
                    <div class='span12'>
                        <input type="checkbox" name="ativo" id="ativo" value="t" <?=(!empty($ativo) ? "checked" : "")?> >&nbsp;
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
    <br />
    <p><br/>
        <button class='btn' id="btn_acao" type="button" onclick="submitForm($(this).parents('form'));">Gravar</button>
        <input type='hidden' id="btn_click" name='btn_acao' value='' />
    </p><br/>
</form>
<br />

<table id="atendente_cadastrados" class='table table-striped table-bordered table-hover table-fixed' >
    <thead>
        <tr class="titulo_coluna" >
            <th>Justificativa</th>
            <th>Ativo</th>
        </tr>
    </thead>
    <tbody>
<?php
$sqlCarrega = "
        SELECT  justificativa,
                descricao,
                ativa
        FROM    tbl_justificativa
        WHERE   fabrica = $login_fabrica
  ORDER BY      justificativa
";
$resCarrega = pg_query($con,$sqlCarrega);

while ($result = pg_fetch_object($resCarrega)) {
?>
        <tr>
            <td class="tac"><a href="<?=$PHP_SELF?>?justificativa=<?=$result->justificativa?>"><?=$result->descricao?></a></td>
            <td class="tac"><?=($result->ativa == 't') ? "<span style='color:#0F0;font-weight:bold;'>SIM</span>" : "<span style='color:#F00;font-weight:bold;'>NÃO</span>"?></td>
        </tr>
<?php
}
?>
    </tbody>
</table>

<?php
include "rodape.php";
?>
