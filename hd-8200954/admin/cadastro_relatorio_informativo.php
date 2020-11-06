<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="cadastro, gerencia";
include 'autentica_admin.php';
include 'funcoes.php';

require_once "relatorio_informativo/aba_config.php";

$ri = $_REQUEST['ri_id'];

try {

    $riClass = new \Mirrors\Ri\RiMirror($login_fabrica,$login_admin);

    if (isset($_POST['codigo_aba'])) {
        /*
            Formatar campos de data, preço etc..
        */
        $dadosRequisicao = $riClass->formataCampos($_POST);

        $dadosRequisicao["ri"]["id"] = $_POST['ri_id'];

        $retorno = $riClass->envia($dadosRequisicao);

        $ri = $retorno["id_ri"];

        $msg_success = "Aba ".$abas[$_POST['codigo_aba']]["descricao"]." Gravada com sucesso!";

    }

    if (!empty($ri)) {

        $dadosRequisicao = $riClass->consulta($ri);

        /*
        por causa da MALDITA proxy de produção, para não ter que alterar o projeto inteiro foi necessário converter as chaves do retorno da api de camelCase para snakeCase.Ex: testeTeste -> teste_teste
        */
        $dadosRequisicao = $riClass->camelToSnakeRecursivo($dadosRequisicao);

        //nova config de abas. A config padrão esta no arquivo aba_config.php
        $abas = $riClass->atualizaConfigAbas($dadosRequisicao);

        $exibeAreaTransferencia = true;

    } else {
        //nova config de abas. A config padrão esta no arquivo aba_config.php
        $abas = $riClass->atualizaConfigAbas();

    }

} catch(\Exception $e){

    $msg_erro["msg"] = utf8_decode($e->getMessage());

}



$layout_menu = "gerencia";
$title = "Preenchimento do RI";
include 'cabecalho_new.php';

$plugins = array(
   "bootstrap3",
   "mask",
   "datepicker",
   "shadowbox",
   "price_format"
);

include "plugin_loader.php";

if (($exibeAreaTransferencia && !$abas["posvenda"]["apenas_visualiza"]) || $dadosRequisicao["ri_transferencia"]["status"] == "Finalizado") { ?>
    <div class="row row-relatorio" style="width: 1000px;background-color: #D9E2EF;padding-top: 15px;margin-left: 150px !important;border: solid lightgray 1px;" class="tc_formulario">
        <div class="col-sm-12">
            <div class="form-group col-sm-3 col-sm-offset-2">
                <label>Grupo Follow-up</label><br />
                <select class="form-control" id="transferir_para" name="ri_transferencia[ri_followup]" style="width: 210px;">
                    <option value="">Selecione o grupo</option>
                    <?php
                    $sqlFollowUp = "SELECT ri_followup, nome
                                    FROM tbl_ri_followup
                                    WHERE fabrica = {$login_fabrica}
                                    AND ativo";
                    $resFollowUp = pg_query($con, $sqlFollowUp);

                    while ($dadosFollow = pg_fetch_object($resFollowUp)) {

                        $selected = $dadosRequisicao["ri_transferencia"]["ri_followup"] == $dadosFollow->ri_followup ? "selected" : "";

                    ?>
                        <option value="<?= $dadosFollow->ri_followup ?>" <?= $selected ?>><?= $dadosFollow->nome ?></option>
                    <?php
                    } ?>
                </select>
            </div>
            <div class="form-group col-sm-3">
                <label>Status</label><br />
                <select class="form-control" id="status_ri" name="ri_transferencia[status]" style="width: 210px;">
                    <option value="Aberto" <?= ($dadosRequisicao["ri_transferencia"]["status"] == "Aberto") ? "selected" : "" ?>>Aberto</option>
                    <option value="Aguardando Produto" <?= ($dadosRequisicao["ri_transferencia"]["status"] == "Aguardando Produto") ? "selected" : "" ?>>Aguardando Produto</option>
                    <?php
                    if (!empty($dadosRequisicao["ri"]["conclusao"])) { ?>
                        <option value="Finalizado" <?= ($dadosRequisicao["ri_transferencia"]["status"] == "Finalizado") ? "selected" : "" ?>>Finalizado</option>
                    <?php
                    } ?>
                </select>
            </div>
            <div class="form-group col-sm-3">
                <br />
                <button class="btn btn-primary" id="transferir">
                    Gravar
                </button>
            </div>
        </div>
    </div>
<?php
}

$displayErro = "hidden";
if (count($msg_erro["msg"]) > 0) {
    $displayErro = "";
    $msgErro     = $msg_erro["msg"];
}

$displaySuccess = "hidden";
if (!empty($msg_success)) {
    $displaySuccess = "";
}

?>
<input type="hidden" id="status_relatorio_informativo" value="<?= $dadosRequisicao["ri_transferencia"]["status"] ?>" />
<br />
<div class="alert alert-error" <?= $displayErro ?>>
    <h4 id="texto_erro"><?= $msgErro ?></h4>
</div>
<div class="alert alert-success" <?= $displaySuccess ?>>
    <h4><?= $msg_success ?></h4>
</div>
<div class="row" style="width: 1300px;">
    <br />
    <table border="0" cellspacing="0" cellpadding="0">
        <tbody>
            <tr height="18">
                <td width="50">
                    <div style="background-color: #c2e3b6;border: solid 1px gray;margin-left: 20px;">&nbsp;</div> 
                </td>
                <td width="100">
                    <strong> &nbsp;Aba preenchida</strong>
                </td>
                <td width="50">
                    <div style="background-color: #facf96;border: solid 1px gray;margin-left: 20px;">&nbsp;</div> 
                </td>
                <td width="100">
                    <strong> &nbsp;Aba pendente</strong>
                </td>
                <td width="50">
                    <div style="background-color: lightgray;border: solid 1px gray;margin-left: 20px;">&nbsp;</div> 
                </td>
                <td width="100">
                    <strong> &nbsp;Aba bloqueada</strong>
                </td>
            </tr>
        </tbody>
    </table>
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<!-- Nav tabs -->
<ul class="nav nav-tabs" role="tablist">
    <?php 
    foreach ($abas as $codigoAba => $config) { 

        $classDisabled = $config['status_preenchimento'] == "bloqueada" ? "disabled" : "";
        $classActive   = $config['ativa'] ? 'active' : '';
        $corAba        = $statusAbasConfig[$config["status_preenchimento"]]["cor"];
        ?>

        <li role="presentation" class="<?= $classDisabled ?> <?= $classActive ?>">

            <a style="background-color: <?= $corAba ?> ;"
               href="#<?= $codigoAba ?>" 
               aria-controls="<?= $codigoAba ?>" 
               role="tab" 
               <?= empty($classDisabled) ? 'data-toggle="tab"' : "" ?>>
                    <?= $config["descricao"] ?>
            </a>

        </li>

    <?php
    } ?>
</ul>
<!-- Tab panes -->
<div class="tab-content">
    <?php
    $dadosRequisicaoAnt = $dadosRequisicao;
    foreach ($abas as $codigoAba => $config) {

        $arquivoAba = "relatorio_informativo/aba_{$codigoAba}.php";

        if (is_file($arquivoAba)) {

            $abaAtiva        = $config["ativa"] ? "active" : "";
            $apenasVisualiza = $config["apenas_visualiza"] ? "bloquear-campos" : "";

            if (count($config["anexo_config"]) > 0) {

                if (!empty($dadosRequisicao["ri"]["id"])) {
                    $tempUniqueId = $dadosRequisicao["ri"]["id"];
                    $anexoNoHash = null;
                } else if (!empty($dadosRequisicao["anexo_chave"])) {
                    $tempUniqueId = $dadosRequisicao["anexo_chave"];
                    $anexoNoHash = true;
                } else {
                    $tempUniqueId = $codigoAba.$login_fabrica.$login_admin.date("dmYHis");
                    $anexoNoHash = true;
                }

                $boxUploader = array(
                    "div_id"        => "anexo_aba_{$codigoAba}",
                    "prepend"       => $anexo_prepend,
                    "context"       => $config["anexo_config"]["contexto"],
                    "unique_id"     => $tempUniqueId,
                    "hash_temp"     => $anexoNoHash,
                    "reference_id"  => $tempUniqueId,
                    "root"          => $config["anexo_config"]["plugin_id"],
                    "hidden_button" => $config["apenas_visualiza"],
                    "hidden_title"  => true
                );

            }
            ?>
            <div role="tabpanel" class="tab-pane <?= $abaAtiva ?> <?= $apenasVisualiza ?>" id="<?= $codigoAba ?>">
                <form action="<?= $_SERVER['PHP_SELF'] ?>" method="POST">
                    <input type="hidden" name="codigo_aba" value="<?= $codigoAba ?>" />
                    <input type="hidden" name="ri_id" value="<?= $ri ?>" />
                    <?php require_once $arquivoAba; ?>
                </form>
            </div>
            <?php
        } else {
            echo '<div role="tabpanel" style="font-size: 30px;" class="tab-pane" id="'.$codigoAba.'">
                    ABA '.$codigoAba.' NÃO CONFIGURADA!
                </div>';
        }

    }

    if ($dadosRequisicao["ri_transferencia"]["status"] == "Finalizado") { ?>
        <center>
            <button type="button" class="btn btn-danger btn-lg" id="gerar_pdf" data-ri="<?= $ri ?>">
                <i class="glyphicon glyphicon-file"></i>
                Baixar PDF
            </button>
        </center>
        <br />
    <?php
    } ?>
</div>
<script src="relatorio_informativo/script.js?v=<?= date('dmYhis') ?>"></script>
<link rel="stylesheet" href="relatorio_informativo/style.css?v=<?= date('dmYhis') ?>">
<script src="../plugins/ckeditor_new/ckeditor.js"></script>

<?php
include "rodape.php";
?>
