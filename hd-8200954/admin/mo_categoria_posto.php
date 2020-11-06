<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios = "gerencia";
include 'autentica_admin.php';
include 'funcoes.php';
include_once "../class/AuditorLog.php";

$diagnostico = $_REQUEST["diagnostico"];

if (isset($_POST['ajax_ativa_inativa'])) {

    $diagnostico = $_POST['diagnostico'];
    $ativo = ($_POST['ativo'] == "t") ? "f" : "t";

    $objLog = new AuditorLog();

    $objLog->retornaDadosSelect("SELECT tbl_linha.nome || '&nbsp;' as linha, 
                                        tbl_categoria_posto.descricao || '&nbsp;' as categoria,
                                        tbl_diagnostico.ativo
                                 FROM tbl_diagnostico
                                 JOIN tbl_linha USING(linha)
                                 LEFT JOIN tbl_categoria_posto USING(categoria_posto)
                                 WHERE tbl_diagnostico.diagnostico = {$diagnostico}");

    $sql = "UPDATE tbl_diagnostico
            SET ativo = '{$ativo}'
            WHERE diagnostico = {$diagnostico}";
    $res = pg_query($con, $sql);

    $objLog->retornaDadosSelect("SELECT '&nbsp;' || tbl_linha.nome as linha,
                                        '&nbsp;' || tbl_categoria_posto.descricao as categoria,
                                        tbl_diagnostico.ativo
                                 FROM tbl_diagnostico
                                 JOIN tbl_linha USING(linha)
                                 LEFT JOIN tbl_categoria_posto USING(categoria_posto)
                                 WHERE tbl_diagnostico.diagnostico = {$diagnostico}")->enviarLog("update", "tbl_diagnostico", $login_fabrica);

    if (!pg_last_error()) {
        $retorno = ["retorno" => true];
    } else {
        $retorno = ["retorno" => false];
    }

    exit(json_encode($retorno));

}

if (isset($_POST['ajax_exclui'])) {

    $diagnostico = $_POST['diagnostico'];

    $sql = "DELETE FROM tbl_diagnostico WHERE diagnostico = {$diagnostico}";
    pg_query($con, $sql);

    if (!pg_last_error()) {
        $retorno = ["retorno" => true];
    } else {
        $retorno = ["retorno" => false];
    }

    exit(json_encode($retorno));

}

if ($_POST["gerar_excel"]) {

    $data = date("d-m-Y-H:i");

    $fileName = "relatorio-mo-categoria-{$data}.xls";

    $file = fopen("/tmp/{$fileName}", "w");
    $thead = "
        <table border='1'>
            <thead>
                <tr>
                    <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Linha</th>
                    <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Categoria</th>
                    <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Ativo</th>
                    <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Mão de Obra</th>
                </tr>
            </thead>
            <tbody>";
    fwrite($file, $thead);

    $sqlPesquisa = "SELECT tbl_linha.nome as nome_linha,
                   tbl_categoria_posto.nome as nome_categoria,
                   tbl_diagnostico.ativo,
                   tbl_diagnostico.mao_de_obra,
                   tbl_diagnostico.diagnostico
            FROM tbl_diagnostico
            JOIN tbl_linha USING(linha)
            LEFT JOIN tbl_categoria_posto USING(categoria_posto)
            WHERE tbl_diagnostico.fabrica = {$login_fabrica}";
    $resPesquisa = pg_query($con, $sqlPesquisa);

    $body = "";
    while ($dados = pg_fetch_object($resPesquisa)) {

        $ativo = ($dados->ativo == 't') ? "Sim" : "Não";

            $body .= "<tr>
                        <td nowrap align='center' valign='top'>{$dados->nome_linha}</td>
                        <td nowrap align='center' valign='top'>{$dados->nome_categoria}</td>
                        <td nowrap align='center' valign='top'>{$ativo}</td>
                        <td nowrap align='center' valign='top'>{$dados->mao_de_obra}</td>
                    </tr>";

    }

    fwrite($file, $body);

    fclose($file);

    if (file_exists("/tmp/{$fileName}")) {
        system("mv /tmp/{$fileName} xls/{$fileName}");

        echo "xls/{$fileName}";
    }

    exit;
}

if (isset($_POST["linha"])) {

    $linha           = trim($_POST['linha']);
    $categoria_posto = $_POST['categoria_posto'];
    $mao_de_obra     = trim($_POST['mao_de_obra']);
    $ativo           = isset($_POST['ativo']) ? "t" : "f";

    $mao_de_obra = str_replace(",", ".", str_replace(".", "", trim($_POST['mao_de_obra'])));

    if (empty($linha)) {
        $msg_erro["msg"][] = "Preencha os campos obrigatórios";
        $msg_erro["campo"][] = "linha";
    }

    if (empty($mao_de_obra)) {
        $msg_erro["msg"][] = "Preencha os campos obrigatórios";
        $msg_erro["campo"][] = "mao_de_obra";
    }

    $sql = "SELECT tipo_posto
            FROM tbl_tipo_posto
            WHERE fabrica = {$login_fabrica}
            AND tipo_revenda IS TRUE";
    $res = pg_query($con, $sql);

    $tipo_posto_revenda = pg_fetch_result($res, 0, 'tipo_posto');

    if (empty($tipo_posto_revenda)) {
        $msg_erro["msg"][] = "Tipo de posto Revenda não encontrado";
    }

    if (count($msg_erro) == 0) {

        pg_query($con, 'BEGIN');

        if (count($categoria_posto) > 0) {

            foreach ($categoria_posto as $categoriaId) {

                $sqlVerifica = "SELECT diagnostico
                                FROM tbl_diagnostico
                                WHERE linha = {$linha}
                                AND categoria_posto = {$categoriaId}
                                AND fabrica = {$login_fabrica}";
                $resVerifica = pg_query($con, $sqlVerifica);

                if (pg_num_rows($resVerifica) > 0) {

                    $diagnosticoId = pg_fetch_result($resVerifica, 0, 'diagnostico');

                    $objLog = new AuditorLog();

                    $objLog->retornaDadosSelect("SELECT tbl_linha.nome || '&nbsp;' as linha, 
                                                        tbl_categoria_posto.nome || '&nbsp;' as categoria,
                                                        tbl_diagnostico.ativo,
                                                        tbl_diagnostico.mao_de_obra
                                                 FROM tbl_diagnostico
                                                 JOIN tbl_linha USING(linha)
                                                 JOIN tbl_categoria_posto USING(categoria_posto)
                                                 WHERE diagnostico = {$diagnosticoId}");

                    $sql = "UPDATE tbl_diagnostico 
                            SET ativo = '{$ativo}',
                                mao_de_obra = {$mao_de_obra}
                            WHERE diagnostico = {$diagnosticoId}";
                    pg_query($con, $sql);

                    $objLog->retornaDadosSelect("SELECT '&nbsp;' || tbl_linha.nome as linha, 
                                                        '&nbsp;' || tbl_categoria_posto.nome as categoria,
                                                        tbl_diagnostico.ativo,
                                                        tbl_diagnostico.mao_de_obra
                                                 FROM tbl_diagnostico
                                                 JOIN tbl_linha USING(linha)
                                                 JOIN tbl_categoria_posto USING(categoria_posto)
                                                 WHERE diagnostico = {$diagnosticoId}")->enviarLog("update", "tbl_diagnostico", $login_fabrica);

                } else {

                    $objLog = new AuditorLog('insert');

                    $sql = "INSERT INTO tbl_diagnostico (linha, categoria_posto, ativo, mao_de_obra, fabrica, tipo_posto) VALUES ({$linha}, {$categoriaId}, '{$ativo}', {$mao_de_obra}, {$login_fabrica}, {$tipo_posto_revenda}) RETURNING diagnostico";
                    $res = pg_query($con, $sql);

                    $newDiagnostico = pg_fetch_result($res, 0, 'diagnostico');

                    $objLog->retornaDadosSelect("SELECT tbl_linha.nome as linha, 
                                                        tbl_categoria_posto.nome as categoria,
                                                        tbl_diagnostico.ativo,
                                                        tbl_diagnostico.mao_de_obra
                                                 FROM tbl_diagnostico
                                                 JOIN tbl_linha USING(linha)
                                                 JOIN tbl_categoria_posto USING(categoria_posto)
                                                 WHERE diagnostico = {$newDiagnostico}")->enviarLog("insert", "tbl_diagnostico", $login_fabrica);

                }

            }

        } else {

            $sqlVerifica = "SELECT diagnostico
                            FROM tbl_diagnostico
                            WHERE linha = {$linha}
                            AND fabrica = {$login_fabrica}
                            AND categoria_posto IS NULL";
            $resVerifica = pg_query($con, $sqlVerifica);

            if (pg_num_rows($resVerifica) > 0) {

                $diagnosticoId = pg_fetch_result($resVerifica, 0, 'diagnostico');

                $objLog = new AuditorLog();

                $objLog->retornaDadosSelect("SELECT tbl_linha.nome || '&nbsp;' as linha,
                                                    tbl_diagnostico.ativo,
                                                    tbl_diagnostico.mao_de_obra
                                             FROM tbl_diagnostico
                                             JOIN tbl_linha USING(linha)
                                             WHERE diagnostico = {$diagnosticoId}")->enviarLog("update", "tbl_diagnostico", $login_fabrica);

                $sql = "UPDATE tbl_diagnostico 
                        SET ativo = '{$ativo}',
                            mao_de_obra = {$mao_de_obra}
                        WHERE diagnostico = {$diagnosticoId}";
                pg_query($con, $sql);

                $objLog->retornaDadosSelect("SELECT '&nbsp;' || tbl_linha.nome as linha,
                                                    tbl_diagnostico.ativo,
                                                    tbl_diagnostico.mao_de_obra
                                             FROM tbl_diagnostico
                                             JOIN tbl_linha USING(linha)
                                             WHERE diagnostico = {$diagnosticoId}")->enviarLog("update", "tbl_diagnostico", $login_fabrica);

            } else {

                $objLog = new AuditorLog('insert');

                $sql = "INSERT INTO tbl_diagnostico (linha, ativo, mao_de_obra, fabrica, tipo_posto) VALUES ({$linha}, '{$ativo}', {$mao_de_obra}, {$login_fabrica}, {$tipo_posto_revenda}) RETURNING diagnostico";
                $res = pg_query($con, $sql);

                $newDiagnostico = pg_fetch_result($res, 0, 'diagnostico');

                $objLog->retornaDadosSelect("SELECT tbl_linha.nome as linha,
                                                    tbl_diagnostico.ativo,
                                                    tbl_diagnostico.mao_de_obra
                                             FROM tbl_diagnostico
                                             JOIN tbl_linha USING(linha)
                                             WHERE diagnostico = {$newDiagnostico}")->enviarLog("insert", "tbl_diagnostico", $login_fabrica);

            }

        }

        if (pg_last_error()) {
            $msg_erro["msg"][] = "Erro ao efetuar o cadastro";
            pg_query($con, "ROLLBACK");
        } else {
            $msg_success = "Operação realizada com sucesso!";
            pg_query($con, "COMMIT");
            unset($mao_de_obra, $linha, $ativo, $categoria_posto);
        }

    }

} else if (!empty($diagnostico)) {

    $sqlPopula = "SELECT linha, ativo, categoria_posto, mao_de_obra
                  FROM tbl_diagnostico
                  WHERE fabrica = {$login_fabrica}
                  AND diagnostico = {$diagnostico}";
    $resPopula = pg_query($con, $sqlPopula);

    $linha              = pg_fetch_result($resPopula, 0, 'linha');
    $ativo              = pg_fetch_result($resPopula, 0, 'ativo');
    $categoria_posto[]  = pg_fetch_result($resPopula, 0, 'categoria_posto');
    $mao_de_obra        = pg_fetch_result($resPopula, 0, 'mao_de_obra');

}

$layout_menu = "cadastro";
$title = "Cadastro de Categoria de Posto";

include "cabecalho_new.php";

$plugins = array(
   "select2",
   "shadowbox",
   "dataTable",
   "mask",
   "datepicker",
   "multiselect",
   "price_format"
);

include "plugin_loader.php";
?>
<script>

    $(function(){

        Shadowbox.init();

        $("#categoria_posto").multiselect({
            selectedText: "selecionados # de #"
        });

        $('#mao_de_obra').priceFormat({
            prefix: '',
            decimals: 2,
            thousandsSeparator: '.',
            centsSeparator: ','
        });

        $(document).on("click", ".btn-ativar-inativar", function(){

            let that = $(this);
            let diagnostico = $(that).data("diagnostico");
            let ativo = $(that).data("ativo");

            $.ajax({
                url: window.location,
                type: "POST",
                data: {
                    ajax_ativa_inativa: true,
                    diagnostico: diagnostico,
                    ativo: ativo
                },
                dataType: "json",
                beforeSend: function(){
                    $(that).prop("disabled", true).text("Aguarde...");
                },
                success: function(data){

                    if (data.retorno) {

                        $(that).data("ativo", (ativo == 't') ? "f" : "t");

                        $(that).toggleClass("btn-success btn-danger")
                                 .prop("disabled", false)
                                    .text((ativo == 't') ? 'Ativar' : 'Inativar');

                        $(that).closest("tr")
                                .find(".img-ativo-inativo")
                                    .attr("src", (ativo == "t") ? "imagens/status_vermelho.png" : "imagens/status_verde.png");


                    } else {

                        alert("Erro ao ativar/inativar produto");

                    }

                }
            });

        });

        $(document).on("click", ".btn-excluir", function(){

            let that = $(this);
            let diagnostico = $(that).data("diagnostico");

            if (confirm("Confirma a exclusão do registro?")) {

                $.ajax({
                    url: window.location,
                    type: "POST",
                    data: {
                        ajax_exclui: true,
                        diagnostico: diagnostico
                    },
                    dataType: "json",
                    beforeSend: function(){
                        $(that).prop("disabled", true).text("Aguarde...");
                    },
                    success: function(data){

                        if (data.retorno) {

                            $(that).closest("tr").remove();

                        } else {

                            alert("Não é possível remover este registro");

                        }

                    }
                });

            }

        });
        
    });
</script>


<?php

if($login_fabrica == 148){
?>
	<div class="alert alert-warning"><center><b>Cadastro válido apenas para Ordens de Serviço com o Tipo de Atendimento Garantia</b></center></div>
<?php
}


if (!empty($msg_success)) { ?>
    <div class="alert alert-success">
        <h4><?= $msg_success ?></h4>
    </div>
<?php
}

if (count($msg_erro["msg"]) > 0) { ?>
    <div class="alert alert-danger">
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php } ?>
<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
    <div class='titulo_tabela '>Parâmetros de Cadastro</div>
    <br />
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group <?=(in_array("linha", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='descricao'>Linha</label>
                <div class='controls controls-row'>
                    <div class='span12'>
                        <h5 class='asteristico'>*</h5>
                        <select class="form-control span10" name="linha">
                            <option value="">Selecione a Linha</option>
                            <?php
                            $sqlLinha = "SELECT nome, linha
                                           FROM tbl_linha
                                           WHERE fabrica = {$login_fabrica}
                                           AND ativo";
                            $resLinha = pg_query($con, $sqlLinha);

                            while ($dados = pg_fetch_object($resLinha)) {

                                $selected = ($linha == $dados->linha) ? "selected" : "";

                            ?>
                                <option value="<?= $dados->linha ?>" <?= $selected ?>><?= $dados->nome ?></option>
                            <?php
                            } ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group <?=(in_array("categoria_posto", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='descricao'>Categoria</label>
                <div class='controls controls-row'>
                    <div class='span12'>
                        <select class="form-control span10" id="categoria_posto" multiple name="categoria_posto[]">
                            <?php
                            $sqlCategoria = "SELECT nome, categoria_posto, ativo
                                             FROM tbl_categoria_posto
                                             WHERE fabrica = {$login_fabrica}
                                             AND ativo";
                            $resCategoria = pg_query($con, $sqlCategoria);

                            while ($dados = pg_fetch_object($resCategoria)) {

                                $selected = (in_array($dados->categoria_posto, $categoria_posto)) ? "selected" : "";
                                
                            ?>
                                <option value="<?= $dados->categoria_posto ?>" <?= $selected ?>><?= $dados->nome ?></option>
                            <?php
                            } ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row-fluid">
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group <?=(in_array("mao_de_obra", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='descricao'>Mão de Obra</label>
                <div class='controls controls-row'>
                    <div class='span4'>
                        <h5 class='asteristico'>*</h5>
                        <input type="text" class="form-control span12" name="mao_de_obra" id="mao_de_obra" value="<?= $mao_de_obra ?>" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group <?=(in_array("ativo", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='descricao'>Ativo</label>
                <div class='controls controls-row'>
                    <div class='span12'>
                        <input type="checkbox" name="ativo" <?= ($ativo == "t" || empty($diagnostico)) ? "checked" : "" ?> value="t" class="form-control" />
                    </div>
                </div>
            </div>
        </div>
    </div>
    <br />
    <div class="row-fluid">
        <p class="tac">
            <button class='btn' id="btn_acao"><?= (!empty($diagnostico)) ? "Alterar" : "Gravar" ?></button>
        </p>
    </div>
</form>
<?php
$sqlPesquisa = "SELECT tbl_linha.nome as nome_linha,
                       tbl_categoria_posto.nome as nome_categoria,
                       tbl_diagnostico.ativo,
                       tbl_diagnostico.mao_de_obra,
                       tbl_diagnostico.diagnostico
                FROM tbl_diagnostico
                JOIN tbl_linha USING(linha)
                LEFT JOIN tbl_categoria_posto USING(categoria_posto)
                WHERE tbl_diagnostico.fabrica = {$login_fabrica}";
$resPesquisa = pg_query($con, $sqlPesquisa);

if (pg_num_rows($resPesquisa) > 0) { ?>
    <center>
        <a rel='shadowbox' href='relatorio_log_alteracao_new.php?parametro=tbl_diagnostico&id=<?= $login_fabrica ?>' class="btn btn-warning">Visualizar Log Auditor</a>
    </center>
    <table class="table table-bordered table-hover table-fixed" id="tabela_diagnostico">
        <thead>
            <tr class="titulo_coluna">
                <th>Linha</th>
                <th>Categoria</th>
                <th>Mão de Obra</th>
                <th>Ativo</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php
            while ($dados = pg_fetch_object($resPesquisa)) {

                $status = ($dados->ativo == "t") ? "status_verde.png" : "status_vermelho.png";

                if ($dados->ativo == "t") {

                    $status = "status_verde.png"; $classe = "danger"; $texto  = "Inativar";

                } else {

                    $status = "status_vermelho.png"; $classe = "success"; $texto   = "Ativar";

                }

            ?>
                <tr>
                    <td class="tac">
                        <a href="<?= $_SERVER["PHP_SELF"] ?>?diagnostico=<?= $dados->diagnostico ?>"><?= $dados->nome_linha ?></a>
                    </td>
                    <td class="tac"><?= (empty($dados->nome_categoria)) ? "<i>Sem categoria</i>" : $dados->nome_categoria ?></td>
                    <td class="tac">R$ <?= number_format($dados->mao_de_obra, 2, ",", ".") ?></td>
                    <td class="tac">
                        <img class="img-ativo-inativo" src="imagens/<?= $status ?>" />
                    </td>
                    <td class="tac">
                        <button data-ativo="<?= $dados->ativo ?>" data-diagnostico="<?= $dados->diagnostico ?>" class="btn btn-<?= $classe ?> btn-ativar-inativar"><?= $texto ?></button>
                        <button data-diagnostico="<?= $dados->diagnostico ?>" class="btn btn-danger btn-excluir">Excluir</button>
                    </td>
                </tr>
            <?php
            } ?>
        </tbody>
    </table>
    <br />
    <?php
        $jsonPOST = excelPostToJson($_POST);
    ?>
    <div id='gerar_excel' class="btn_excel">
        <input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
        <span><img src='imagens/excel.png' /></span>
        <span class="txt">Gerar Arquivo Excel</span>
    </div>
    <br />
    <script>
        $.dataTableLoad({
            table: "#tabela_diagnostico"
        });
    </script>
<?php
} else { ?>
    <div class="alert alert-warning">
        <h4>Nenhum resultado encontrado</h4>
    </div>
<?php
}

include "rodape.php";
?>
