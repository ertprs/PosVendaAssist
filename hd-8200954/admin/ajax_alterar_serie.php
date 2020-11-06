<?php

$areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;

include_once "dbconfig.php";
include_once "includes/dbconnect-inc.php";

/*ini_set("display_errors", 1);
error_reporting(E_ALL);*/

if ($areaAdmin === true) {
    $admin_privilegios = "call_center";
    include 'autentica_admin.php';
} else {
    include 'autentica_usuario.php';
    include "class/tdocs.class.php";
}

include_once 'helpdesk/mlg_funciones.php';
include 'funcoes.php';

$os = $_REQUEST['os'];

include_once "../os_cadastro_unico/fabricas/169/regras.php";

if ($_REQUEST['btn_acao'] == 'gravar') {

    $nova_serie = $_REQUEST['nova_serie'];
    $campos = array();
    $msg_erro = array();
    $msg_sucesso = "";

    try {

        if (empty($nova_serie)) {
            throw new Exception("Informe a nova série");
        }

        $sqlDadosOS = "
            SELECT
                o.fabrica,
                o.data_fechamento,
                o.finalizada,
                op.serie,
                o.posto,
                p.produto,
                p.referencia
            FROM tbl_os o
            JOIN tbl_os_produto op USING(os)
            JOIN tbl_produto p ON p.produto = op.produto AND p.fabrica_i = o.fabrica
            WHERE os = {$os};
        ";
        $resDadosOS = pg_query($con, $sqlDadosOS);
        $dadosOS = pg_fetch_all($resDadosOS);

        $campos['produto']['id'] = $dadosOS[0]['produto'];
        $campos['produto']['referencia'] = $dadosOS[0]['referencia'];
        $campos['produto']['serie_anterior'] = $dadosOS[0]['serie'];
        $campos['produto']['serie'] = $nova_serie;
        $campos['posto']['id'] = $dadosOS[0]['posto'];
        $campos['os']['data_fechamento'] = $dadosOS[0]['data_fechamento'];
        $campos['os']['finalizada'] = $dadosOS[0]['finalizada'];

        $login_fabrica = $dadosOS[0]['fabrica'];

        valida_serie_midea_carrier();
        valida_serie_bloqueada();

        pg_query($con, "BEGIN;");

        pg_query($con, "UPDATE tbl_os SET data_fechamento = null, finalizada = null WHERE os = {$os};");

        if (strlen(pg_last_error()) > 0) {
            throw new Exception("Ocorreu um erro atualizando dados da OS #01");
        }

        pg_query($con, "UPDATE tbl_os SET serie = '{$nova_serie}' WHERE os = {$os};");

        if (strlen(pg_last_error()) > 0) {
            throw new Exception("Ocorreu um erro atualizando dados da OS #02");
        }

        pg_query($con, "UPDATE tbl_os_produto SET serie = '{$nova_serie}' WHERE os = {$os};");

        if (strlen(pg_last_error()) > 0) {
            throw new Exception("Ocorreu um erro atualizando dados da OS #03");
        }

        pg_query($con, "UPDATE tbl_os SET data_fechamento = '{$campos['os']['data_fechamento']}', finalizada = '{$campos['os']['finalizada']}' WHERE os = {$os};");

        if (strlen(pg_last_error()) > 0) {
            throw new Exception("Ocorreu um erro atualizando dados da OS #04");
        }

        pg_query($con, "INSERT INTO tbl_os_interacao (os,admin,posto,comentario,interno,fabrica,programa) VALUES ({$os},{$login_admin},{$campos['posto']['id']},'Número de série da OS foi alterado de {$campos['produto']['serie_anterior']} para a {$nova_serie}.',TRUE,{$login_fabrica},'{$_SERVER['PHP_SELF']}');");

        if (strlen(pg_last_error()) > 0) {
            throw new Exception("Ocorreu um erro atualizando dados da OS #05");
        }

        pg_query($con, "COMMIT;");

        $msg_sucesso = 'Série atualizada com sucesso';

    } catch(Exception $e) {
        pg_query($con, "ROLLBACK;");
        $msg_erro['msg'][] = $e->getMessage();
    }

}

if (!empty($msg_sucesso)) { ?>
    <script type="text/javascript">
        alert("<?= $msg_sucesso; ?>");
        window.parent.atualizaLinhaSerie(<?= $os; ?>, '<?= $nova_serie; ?>');
    </script>
<? }

if (count($msg_erro["msg"]) > 0) { ?>
    <br />
    <div class="alert alert-error"><h4><?= implode("<br />", $msg_erro["msg"]); ?></h4></div>
<? } ?>

<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/bootstrap.css" />
<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/extra.css" />
<link type="text/css" rel="stylesheet" media="screen" href="../css/tc_css.css" />
<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/ajuste.css" />

<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script src="bootstrap/js/bootstrap.js"></script>

<form name="frm_nova_serie" id="frm_serie" method="POST" class="form-search form-inline" enctype="multipart/form-data" >
    <div id="div_informacoes" class="tc_formulario">
        <div class="titulo_tabela">Nova Série</div>
        <br />
        <div class="row-fluid">
            <div class="span1"></div>
            <div class="span4">
                <div class='control-group <?=(in_array('nova_serie', $msg_erro['campos'])) ? "error" : "" ?>' >
                    <label class="control-label" for="nova_serie">Série</label>
                    <div class="controls controls-row">
                        <input type="text" id="nova_serie" name="nova_serie" value='<?= $_REQUEST["nova_serie"]; ?>' />
                    </div>
                </div>
            </div>
            <div class="span1"></div>
        </div>
        <div class="row-fluid">
            <div class="span1"></div>
            <div class="span8">
                <input type="hidden" name="btn_acao" id="btn_acao" value="">
                <button type="button" class="btn btn-default btn-small" onclick="if ($('#btn_acao').val() == '') { $('#btn_acao').val('gravar'); $('form[name=frm_nova_serie]').submit(); } else { alert('Aguarde! A gravação está sendo processada.'); return false; }">Gravar</button>
            </div>
            <div class="span1"></div>
        </div>
    </div>
</form>