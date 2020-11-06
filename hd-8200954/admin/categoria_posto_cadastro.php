<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios = "gerencia";
include 'autentica_admin.php';
include 'funcoes.php';
include_once "../class/AuditorLog.php";


$categoria_posto = $_REQUEST["categoria_posto"];

if (isset($_POST['ajax_ativa_inativa'])) {

    $categoria = $_POST['categoria_posto'];
    $ativo = ($_POST['ativo'] == "t") ? "f" : "t";

    $objLog = new AuditorLog();

    $objLog->retornaDadosSelect("SELECT nome as descricao, ativo FROM tbl_categoria_posto WHERE categoria_posto = {$categoria_posto}");

    $sql = "UPDATE tbl_categoria_posto 
            SET ativo = '{$ativo}'
            WHERE categoria_posto = {$categoria}";
    $res = pg_query($con, $sql);

    $objLog->retornaDadosSelect("SELECT nome || '&nbsp;' as descricao, ativo FROM tbl_categoria_posto WHERE categoria_posto = {$categoria_posto}")->enviarLog("update", "tbl_categoria_posto", $login_fabrica);

    if (!pg_last_error()) {
        $retorno = ["retorno" => true];
    } else {
        $retorno = ["retorno" => false];
    }

    exit(json_encode($retorno));

}

if (isset($_POST["descricao"])) {

    $descricao = trim($_POST['descricao']);
    $ativo     = isset($_POST['ativo']) ? "t" : "f";

    if (empty($descricao)) {
        $msg_erro["msg"][] = "Preencha os campos obrigatórios";
        $msg_erro["campo"][] = "descricao";
    }

    $sqlVerificaCategoria = "SELECT categoria_posto FROM tbl_categoria_posto
                             WHERE UPPER(nome) = UPPER('{$descricao}')
                             AND fabrica = {$login_fabrica}";
    $resVerificaCategoria = pg_query($con, $sqlVerificaCategoria);

    if (pg_num_rows($resVerificaCategoria) > 0 && empty($categoria_posto)) {

        $msg_erro["msg"][] = "Já existe uma categoria cadastrada com essa descrição";
        $msg_erro["campo"][] = "descricao";

    }

    if ($login_fabrica == 195) {

        $criterios = $_POST['criterios'];
        if (count($criterios) == 0) {
            $msg_erro["msg"][] = "Preencha os campos obrigatórios";
            $msg_erro["campo"][] = "prazo_atendimento";
            $msg_erro["campo"][] = "troca_produto";
            $msg_erro["campo"][] = "auditoria_os";
            $msg_erro["campo"][] = "reincidencia";
            $msg_erro["campo"][] = "orcamento_aprovado";
        } else {

            if (strlen($criterios["prazo_atendimento"]) == 0) {
                $msg_erro["msg"][] = "Preencha o campo Prazo de atendimento";
                $msg_erro["campo"][] = "prazo_atendimento";
            }

            if (strlen($criterios["troca_produto"]) == 0) {
                $msg_erro["msg"][] = "Preencha o campo Troca do produto";
                $msg_erro["campo"][] = "troca_produto";
            }

            if (strlen($criterios["auditoria_os"]) == 0) {
                $msg_erro["msg"][] = "Preencha o campo Auditorias de O.S.";
                $msg_erro["campo"][] = "auditoria_os";
            }

            if (strlen($criterios["reincidencia"]) == 0) {
                $msg_erro["msg"][] = "Preencha o campo Reincidência";
                $msg_erro["campo"][] = "reincidencia";
            }

            if (strlen($criterios["orcamento_aprovado"]) == 0) {
                $msg_erro["msg"][] = "Preencha o campo Orçamentos Aprovados";
                $msg_erro["campo"][] = "orcamento_aprovado";
            }

        }
    }

    if (count($msg_erro) == 0) {

        pg_query($con, 'BEGIN');

        if (empty($categoria_posto)) {

            $objLog = new AuditorLog('insert');

            $sql = "INSERT INTO tbl_categoria_posto(nome, ativo, fabrica) 
                    VALUES ('{$descricao}','{$ativo}',$login_fabrica)
                    RETURNING categoria_posto";
            $res = pg_query($con, $sql);

            $categoria_posto_new = pg_fetch_result($res, 0, 'categoria_posto');

            $objLog->retornaDadosSelect("SELECT nome as descricao, ativo FROM tbl_categoria_posto WHERE categoria_posto = {$categoria_posto_new}")->enviarLog("insert", "tbl_categoria_posto", $login_fabrica);

        } else {
            $campoUP = "";

            if ($login_fabrica == 195) {

                $jsoncriterios = json_encode($criterios);

                $campoUP = ",parametros_adicionais='{$jsoncriterios}' ";
            }

            $objLog = new AuditorLog();

            $objLog->retornaDadosSelect("SELECT nome as descricao, ativo, parametros_adicionais FROM tbl_categoria_posto WHERE categoria_posto = {$categoria_posto}");

            $sql = "UPDATE tbl_categoria_posto 
                    SET nome = '{$descricao}',
                        ativo = '{$ativo}' {$campoUP}
                    WHERE categoria_posto = {$categoria_posto}";
            pg_query($con, $sql);

            $objLog->retornaDadosSelect("SELECT nome || '&nbsp;' as descricao, ativo,parametros_adicionais FROM tbl_categoria_posto WHERE categoria_posto = {$categoria_posto}")->enviarLog("update", "tbl_categoria_posto", $login_fabrica);

        }
        if (pg_last_error()) {
            $msg_erro["msg"][] = "Erro ao efetuar o cadastro";
            pg_query($con, "ROLLBACK");
        } else {
            $msg_success = "Operação realizada com sucesso!";
            pg_query($con, "COMMIT");
            unset($descricao, $ativo);
            if ($login_fabrica == 195) {
                header("Location: categoria_posto_cadastro.php");
            }
        }

    }

} else if (!empty($categoria_posto)) {

    $sqlPopula = "SELECT nome, ativo,parametros_adicionais
                FROM tbl_categoria_posto
                WHERE fabrica = {$login_fabrica}
                AND categoria_posto = {$categoria_posto}";
    $resPopula = pg_query($con, $sqlPopula);

    $descricao = pg_fetch_result($resPopula, 0, 'nome');
    $ativo     = pg_fetch_result($resPopula, 0, 'ativo'); 
    if ($login_fabrica == 195) {
        $xxparametros_adicionais     = json_decode(pg_fetch_result($resPopula, 0, 'parametros_adicionais'),1); 
        extract($xxparametros_adicionais);
    }

}

$layout_menu = "cadastro";
$title = "Cadastro de Categoria de Posto";
if ($login_fabrica == 195) {
    $title = "Cadastro de Critérios do Ranking de Postos";
}
include "cabecalho_new.php";

$plugins = array(
   "select2",
   "shadowbox",
   "dataTable",
   "mask",
   "datepicker"
);

include "plugin_loader.php";
?>
<script>

    $(function(){

        Shadowbox.init();

        $(document).on("click", ".btn-ativar-inativar", function(){

            let that = $(this);
            let categoria_posto = $(that).data("categoria_posto");
            let ativo = $(that).data("ativo");

            $.ajax({
                url: window.location,
                type: "POST",
                data: {
                    ajax_ativa_inativa: true,
                    categoria_posto: categoria_posto,
                    ativo: ativo
                },
                dataType: "json",
                beforeSend: function(){
                    $(that).prop("disabled", true).text("Enviando...");
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
        
    });
</script>
<?php

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
<?php if ($login_fabrica == 195) {?>
    <?php if (strlen($categoria_posto) > 0) {?>
        <div class="row">
            <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
        </div>
        <form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
            <input type="hidden" name="categoria_posto" value="<?= $categoria_posto ?>" class="span12 form-control" />
            <div class='titulo_tabela '>Parâmetros de Cadastro</div>
            <br />
            <div class='row-fluid'>
                <div class='span2'></div>
                <div class='span8'>
                    <div class='control-group <?=(in_array("descricao", $msg_erro["campos"])) ? "error" : ""?>'>
                        <label class='control-label' for='descricao'>Descrição Categoria</label>
                        <div class='controls controls-row'>
                            <div class='span12'>
                                <h5 class='asteristico'>*</h5>
                                <input type="text" disabled value="<?= $descricao ?>" class="span12 form-control" />
                                <input type="hidden"  name="descricao" value="<?= $descricao ?>"/>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class='row-fluid'>
                <div class='span2'></div>
                <div class='span3'>
                    <div class='control-group <?=(in_array("prazo_atendimento", $msg_erro["campos"])) ? "error" : ""?>'>
                        <label class='control-label' for='criterios'>Prazo de atendimento</label>
                        <div class='controls controls-row'>
                            <div class='span12'>
                                <h5 class='asteristico'>*</h5>
                                <input type="text" placeholder="indice" name="criterios[prazo_atendimento]" value="<?= $prazo_atendimento ?>" class="span4 form-control" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class='span3'>
                    <div class='control-group <?=(in_array("troca_produto", $msg_erro["campos"])) ? "error" : ""?>'>
                        <label class='control-label' for='criterios'>Troca do produto</label>
                        <div class='controls controls-row'>
                            <div class='span12'>
                                <h5 class='asteristico'>*</h5>
                                <input type="text" placeholder="indice" name="criterios[troca_produto]" value="<?= $troca_produto ?>" class="span4 form-control" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class='span3'>
                    <div class='control-group <?=(in_array("auditoria_os", $msg_erro["campos"])) ? "error" : ""?>'>
                        <label class='control-label' for='criterios'>Auditorias de O.S.</label>
                        <div class='controls controls-row'>
                            <div class='span12'>
                                <h5 class='asteristico'>*</h5>
                                <input type="text" placeholder="indice" name="criterios[auditoria_os]" value="<?= $auditoria_os ?>" class="span4 form-control" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class='row-fluid'>
                <div class='span2'></div>
                <div class='span3'>
                    <div class='control-group <?=(in_array("reincidencia", $msg_erro["campos"])) ? "error" : ""?>'>
                        <label class='control-label' for='criterios'>Reincidência</label>
                        <div class='controls controls-row'>
                            <div class='span12'>
                                <h5 class='asteristico'>*</h5>
                                <input type="text" placeholder="indice" name="criterios[reincidencia]" value="<?= $reincidencia ?>" class="span4 form-control" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class='span3'>
                    <div class='control-group <?=(in_array("orcamento_aprovado", $msg_erro["campos"])) ? "error" : ""?>'>
                        <label class='control-label' for='criterios'>Orçamentos Aprovados</label>
                        <div class='controls controls-row'>
                            <div class='span12'>
                                <h5 class='asteristico'>*</h5>
                                <input type="text" placeholder="indice" name="criterios[orcamento_aprovado]" value="<?= $orcamento_aprovado ?>" class="span4 form-control" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <br />
            <div class="row-fluid">
                <p class="tac">
                    <button class='btn' id="btn_acao"><?= (!empty($categoria_posto)) ? "Alterar" : "Gravar" ?></button>
                </p>
            </div>
        </form>
    <?php }?>
<?php } else {?>
<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
    <input type="hidden" name="categoria_posto" value="<?= $categoria_posto ?>" class="span12 form-control" />
    <div class='titulo_tabela '>Parâmetros de Cadastro</div>
    <br />
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span5'>
            <div class='control-group <?=(in_array("descricao", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='descricao'>Descrição Categoria</label>
                <div class='controls controls-row'>
                    <div class='span12'>
                        <h5 class='asteristico'>*</h5>
                        <input type="text" name="descricao" value="<?= $descricao ?>" class="span9 form-control" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span5'>
            <div class='control-group <?=(in_array("atvio", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='descricao'>Ativo</label>
                <div class='controls controls-row'>
                    <div class='span12'>
                        <input type="checkbox" name="ativo" <?= ($ativo == "t" || empty($categoria_posto)) ? "checked" : "" ?> value="t" class="form-control" />
                    </div>
                </div>
            </div>
        </div>
    </div>
    <br />
    <div class="row-fluid">
        <p class="tac">
            <button class='btn' id="btn_acao"><?= (!empty($categoria_posto)) ? "Alterar" : "Gravar" ?></button>
        </p>
    </div>
</form>
<?php }?>

<?php
$sqlPesquisa = "SELECT nome,
                       ativo,
                       categoria_posto,parametros_adicionais
                FROM tbl_categoria_posto
                WHERE fabrica = {$login_fabrica}";
$resPesquisa = pg_query($con, $sqlPesquisa);

if (pg_num_rows($resPesquisa) > 0) { ?>
    <center>
        <a rel='shadowbox' href='relatorio_log_alteracao_new.php?parametro=tbl_categoria_posto&id=<?= $login_fabrica ?>' class="btn btn-warning">Visualizar Log Auditor</a>
    </center>
    <table class="table table-bordered table-hover table-fixed" id="tabela_categorias">
        <thead>
            <tr class="titulo_coluna">
                <?php if ($login_fabrica == 195) {?>
                    <th>Categoria</th>
                    <th>Prazo de atendimento</th>
                    <th>Troca do produto</th>
                    <th>Auditorias de O.S.</th>
                    <th>Reincidência</th>
                    <th>Orçamentos Aprovados</th>
                <?php } else {?>
                    <th>Descrição</th>
                <?php }?>
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

                if ($login_fabrica == 195) { 
                    $xpararams  = json_decode($dados->parametros_adicionais,1);
                    $prazo_atendimento = $xpararams["prazo_atendimento"];
                    $troca_produto = $xpararams["troca_produto"];
                    $auditoria_os = $xpararams["auditoria_os"];
                    $reincidencia = $xpararams["reincidencia"];
                    $orcamento_aprovado = $xpararams["orcamento_aprovado"];

                }

            ?>
                <tr>
                    <td>
                        <a href="<?= $_SERVER["PHP_SELF"] ?>?categoria_posto=<?= $dados->categoria_posto ?>"><?= $dados->nome ?></a>
                    </td>
                    <?php if ($login_fabrica == 195) {?>
                        <td class="tac"><?=$prazo_atendimento;?>%</td>
                        <td class="tac"><?=$troca_produto;?></td>
                        <td class="tac"><?=$auditoria_os;?></td>
                        <td class="tac"><?=$reincidencia;?>%</td>
                        <td class="tac"><?=$orcamento_aprovado;?>%</td>
                    <?php }?>
                    <td class="tac">
                        <img class="img-ativo-inativo" src="imagens/<?= $status ?>" />
                    </td>
                    <td class="tac">
                        <button data-ativo="<?= $dados->ativo ?>" data-categoria_posto="<?= $dados->categoria_posto ?>" class="btn btn-<?= $classe ?> btn-ativar-inativar"><?= $texto ?></button>
                    </td>
                </tr>
            <?php
            } ?>
        </tbody>
    </table>
    <br />
    
    <script>
        $.dataTableLoad({
            table: "#tabela_categorias"
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