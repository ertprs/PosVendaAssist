<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios = "cadastros";

include 'autentica_admin.php';
include 'funcoes.php';
//error_reporting(E_ALL);
//ini_set('display_errors', 1);
//

if ($_POST["btn_acao"] == "save") {
    try {
        if (!save($_POST)) {
            $formData = $_POST;
            $msg_erro['msg'][] = "Erro ao cadastrar Técnico";
        } else {

            $acao = (!empty($_POST['tecnico'])) ? "Instalador alterado" : "Instalador cadastrado";
            $msg = "Técnico {$acao} com sucesso";
            unset($_POST);
        }
    } catch (Exception $e) {
        $msg_erro['msg'][] = $e->getMessage();
    }
    
} else if (!empty($_GET["tecnico"])) {
    $res = getTecnico($_GET["tecnico"]);
    $formData = pg_fetch_assoc($res, 0);
}

$layout_menu = (in_array($login_fabrica, array(129,165))) ? "cadastro" : "tecnica";

if ($login_fabrica == 165) {
    $title = "Cadastro de Instaladores";
    $title_page = "Cadastro de Instaladores";
} else {
    $title = "Cadastro de Técnicos";
    $title_page = "Cadastro de Técnicos";
}

if (isset($formData['tecnico'])) {
    $title_page = "Alteração de Cadastro";
}

include 'cabecalho_new.php';

$plugins = array(
    "datatable",
    "mask"
);

include("plugin_loader.php"); ?>

<script type="text/javascript">
    function retiraMascara(form){
        <? if ($login_fabrica == 165) { ?>
            $("#telefone").val($("#telefone").val().replace(/[./-]+/gi,''));
        <? } else { ?>
            $("#cpf").val($("#cpf").val().replace(/[./-]+/gi,''));
        <? } ?>        
    }

    $(function () {
        <? if ($login_fabrica == 165) { ?>
            $("#telefone").focus(function () {
                $(this).mask("99999999999");
            });
            $("#telefone").blur(function () {
                var el = $(this);
                if (el.val().length == 11) {
                    el.mask("(99)99999-9999");
                } else if (el.val().length == 10) {
                    el.mask("(99)9999-9999");
                } else {
                    alert("Telefone Inválido");
                }
            });
        <? } else { ?>
            $("#cpf").focus(function () {
                $(this).mask("99999999999999");
            });
            $("#cpf").blur(function () {
                var el = $(this);
                el.mask("999.999.999-99");
            });
        <? } ?>
    });
</script>

<? if (count($msg_erro["msg"]) > 0) { ?>
    <div class="alert alert-error">
        <h4><?= implode("<br />", $msg_erro["msg"]); ?></h4>
    </div>
<? }
if (!empty($msg)) { ?>
    <div class="alert alert-success">
        <h4><?= $msg; ?></h4>
    </div>
<? } ?>

<div class="row">
    <b class="obrigatorio pull-right"> * Campos obrigatórios</b>
</div>

<form name='frm_cadastro' onsubmit="retiraMascara(this);" method='POST' action='<?= $PHP_SELF ?>' class='form-search form-inline tc_formulario' >
    <input type="hidden" id="tecnico" name="tecnico" value="<?= $formData["tecnico"] ?>">
    <div class='titulo_tabela '><?= $title_page; ?></div>
    <br/>
    <div class="row-fluid">
        <div class="span2"></div>
        <div class='span6'>
            <div class='control-group <?= (in_array("nome", $msg_erro["campos"])) ? "error" : "" ?>'>
                <label class='control-label' for='nome'>Nome</label>
                <div class='controls controls-row'>
                    <div class='span12 input-append'>
                        <h5 class='asteristico'>*</h5>
                        <input type="text" id="nome" name="nome" class='span12' maxlength="100" value="<?= $formData["nome"]; ?>" />
                    </div>
                </div>
            </div>
        </div>
        <div class="span2"></div>
    </div>
    <div class="row-fluid">
        <div class="span2"></div>
        <? if ($login_fabrica == 165) { ?>
            <div class='span3'>
                <div class='control-group '>
                    <label class='control-label' for='telefone'>Telefone</label>
                    <div class='controls controls-row'>
                        <div class='span12 input-append'>
                            <input type="text" id="telefone" name="telefone" class='span12' maxlength="20" value="<?= $formData["telefone"]; ?>" />
                        </div>
                    </div>
                </div>
            </div>
        <? } else { ?>
            <div class='span3'>
                <div class='control-group <?= (in_array("cpf", $msg_erro["campos"])) ? "error" : "" ?>'>
                    <label class='control-label' for='cpf'>CPF</label>
                    <div class='controls controls-row'>
                        <div class='span12 input-append'>
                            <? if($login_fabrica != 129){ ?> <h5 class='asteristico'>*</h5> <? } ?>
                            <input type="text" id="cpf" name="cpf" class='span12' maxlength="20" value="<?= $formData["cpf"]; ?>" />
                        </div>
                    </div>
                </div>
            </div>
            <div class='span4'>
                <div class='control-group <?= (in_array("email", $msg_erro["campos"])) ? "error" : "" ?>'>
                    <label class='control-label' for=''>E-mail</label>
                    <div class='controls controls-row'>
                        <div class='span12 input-append'>
                            <input type="text" id="email" name="email" class='span12' value="<?= $formData["email"]; ?>" />
                        </div>
                    </div>
                </div>
            </div>
        <? } ?>
        <div class='span1'>
            <div class='control-group '>
                <label class='control-label' for='ativo'>Ativo</label>
                <div class='controls controls-row'>
                    <div class='span12 input-append'>
                        <input type="checkbox" id="ativo" name="ativo" value="t" <?= ($formData["ativo"] == 't') ? "checked" : "" ?> />
                    </div>
                </div>
            </div>
        </div>
        <div class="span2"></div>
    </div>
    <p><br/>
        <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'), 'save');"> 
            <?= empty($formData['tecnico']) ? 'Cadastrar' : 'Atualizar'; ?>
        </button>
        <input type='hidden' id="btn_click" name='btn_acao' value='' />
    </p><br/>
</form>

<? if ($login_fabrica != 129) { ?>
    <div class="container">
        <div class="row-fluid">
            <div class="span12">
                <div class="control-group">
                    <div class="controls controls-row  tac">
                        <button type='button' class="btn" onclick="window.location='<?= $PHP_SELF; ?>?list=1'">Listar Todos os Técnicos</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <br />
<? } ?>

<? if ($login_fabrica == 129 || ($login_fabrica != 129 && !empty($_GET["list"]))) {
    $tecnicos = getTecnicoList();
    $count = pg_num_rows($tecnicos); ?>
    <div class='alert tac'>Para efetuar alterações, clique no nome do Técnico.</div>
    <table id="tecnicos-list" class='table table-striped table-bordered table-hover' >
        <thead>
             <tr class='titulo_tabela' >
                <th colspan="4">Relação de Técnicos cadastrados</th>
            </tr>
            <tr class='titulo_coluna' >
                <th>Nome</th>
                <? if ($login_fabrica == 165) { ?>
                    <th>Telefone</th>
                <? } else { ?>
                    <th>E-mail</th>
                    <th>CPF</th>
                <? } ?>
                <th>Ativo</th>
            </tr>
        </thead>
        <tbody>
            <? for ($i = 0; $i < $count; $i++) {
                $tecnico = pg_fetch_result($tecnicos, $i, tecnico);
                $nome = pg_fetch_result($tecnicos, $i, nome);
                $cpf = pg_fetch_result($tecnicos, $i, cpf);
                $email = pg_fetch_result($tecnicos, $i, email);
                $telefone = pg_fetch_result($tecnicos, $i, telefone);
                $ativo = pg_fetch_result($tecnicos, $i, ativo) == 't' ? 'imagens/status_verde.png' : 'imagens/status_vermelho.png'; ?>
                <tr>
                    <td><a href="<?= $_SERVER['PHP_SELF'].'?tecnico='.$tecnico; ?>"><?= $nome; ?><a></td>
                    <? if ($login_fabrica == 165) { ?>
                        <td><?= $telefone ?></td>
                    <? } else { ?>
                        <td><?= $email ?></td>
                        <td><?= $cpf ?></td>
                    <? } ?>
                    <td class="tac"><img src='<?= $ativo ?>' alt="status" /></td>
                    </tr>         
            <? } ?>
        </tbody>
    </table>
<? }

function save($formData) {
    
    global $login_fabrica, $msg_erro;

    if (empty($formData['nome'])) {
        $msg_erro['campos'][] = "nome";
        throw new Exception("Preencha os campos obrigatórios");
    }

    if (!in_array($login_fabrica,array(129,165))) {
        if (empty($formData['cpf'])) {
            $msg_erro['campos'][] = "cpf";
            throw new Exception("Preencha os campos obrigatórios");
        }
    }

    return empty($formData['tecnico']) ? insert($formData) : update($formData);

}

function insert($formData) {
    global $con;
    global $login_fabrica;
    $insert = "
        INSERT INTO tbl_tecnico (fabrica, nome, cpf, email, tipo_tecnico, telefone, ativo)
        VALUES ($1,$2,$3,$4,$5,$6,$7);
    ";

    $prepareResult = pg_prepare($con, "insert", $insert);

    return pg_execute($con, "insert", array($login_fabrica, $formData["nome"], $formData["cpf"], $formData["email"], 'TF', $formData["telefone"], $formData["ativo"]));
}

function update($formData) {
    global $con;

    $update = "
        UPDATE tbl_tecnico
        SET nome = $1,
            cpf = $2,
            email = $3,
            telefone = $4,
            ativo = $5
        WHERE tecnico = $6;
    ";

    $prepareResult = pg_prepare($con, "update", $update);
    $res = pg_execute($con, "update", array($formData["nome"], $formData["cpf"], $formData["email"], $formData["telefone"], $formData["ativo"], $formData["tecnico"]));

    return $res;
}

function delete($id) {
    global $con;
    global $login_fabrica;
    $delete = "DELETE FROM tbl_tecnico WHERE tecnico = $1";

    $prepareResult = pg_prepare($con, "delete", $delete);

    $res = pg_execute($con, "delete", array($formData["tecnico"]));
    return ($res) ? true : false;
}

function getTecnicoList() {

    global $con;
    global $login_fabrica;

    $sql = "
        SELECT
            tecnico,
            nome,
            cpf,
            email,
            telefone,
            ativo
        FROM tbl_tecnico
        WHERE tipo_tecnico = 'TF'
        AND fabrica = $1
        ORDER BY nome;
    ";

    $prepareResult = pg_prepare($con, "select", $sql);
    return pg_execute($con, "select", array($login_fabrica));
}

function getTecnico($tecnico) {
    global $con;

    $sql = "
        SELECT
            tecnico,
            nome,
            cpf,
            email,
            telefone,
            ativo
        FROM tbl_tecnico
        WHERE tecnico = $1;
    ";

    $prepareResult = pg_prepare($con, "select", $sql);
    return pg_execute($con, "select", array($tecnico));

}

include "rodape.php"; ?>