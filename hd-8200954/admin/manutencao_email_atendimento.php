<?php
$admin_privilegios = 'cadastros';
$layout_menu       = 'cadastro';
$title             = 'Cadastro De Email';
$plugins           = array('dataTable', 'shadowbox', 'select2', 'multiselect');
$msg_erro          = array();

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';
include_once dirname(__FILE__) . '/../class/AuditorLog.php';

/**
* $pw string
* $key openssl_random_pseudo_bytes(32)
*/
function passwordCrypt($pw, $key) {
    $enc = openssl_encrypt($pw, 'aes-128-cbc', $key);
    $key = bin2hex($key);
    $keyI = substr($key, 0, strlen($key) / 2);
    $keyF = substr($key, strlen($key) / 2);
    return $keyF."/".$enc."/".$keyI;
}

if (isset($_POST) && count($_POST) && isset($_POST['btn_acao'])) {
    $email_consulta = (isset($_POST['email_consulta']) && !empty($_POST['email_consulta'])) ? $_POST['email_consulta'] : '';

    $array_nao_valida = array('ativo', 'admin_email', 'email_consulta');
    if (!empty($email_consulta)) {
        $array_nao_valida[] = 'senha';
    }

    foreach ($_POST as $key => $value) {
        if (in_array($key, $array_nao_valida)) { continue; } //Campos não obrigatórios
        if (empty($value)) {
            $msg_erro['campos'][] = $key;
        }
    }

    if (!count($_POST['admin_email'])) {
        $msg_erro['campos'][] = 'admin_email';
    }

    if (count($msg_erro['campos'])) {
        $msg_erro['erro'][] = 'Preencha os campos obrigatórios';
    }else{
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $msg_erro['campos'][] = 'email';
            $msg_erro['erro'][]   = 'O email informado é inválido';
        } else {

            $sql = "SELECT
                        callcenter_email
                    FROM tbl_callcenter_email
                    WHERE fabrica = {$login_fabrica}
                    AND email = '".trim($_POST['email'])."'";
            $res = pg_query($con, $sql);

            if (pg_num_rows($res) > 0) {
                if (empty($email_consulta) || (!empty($email_consulta) && pg_fetch_result($res, 0, 'callcenter_email') !== $email_consulta)) {
                    $msg_erro['campos'][] = 'email';
                    $msg_erro['erro'][]   = 'O email informado já foi cadastrado';
                }
            }
        }
    }

    if (!count($msg_erro['erro'])) {
        $senha = (!empty($_POST['senha'])) ? passwordCrypt($_POST['senha'], openssl_random_pseudo_bytes(32)) : '';
        $email = $_POST['email'];
        $ativo = (!empty($_POST['ativo'])) ? $_POST['ativo'] : 'f';
        $hostname_email     = $_POST['hostname_email'];
        $limite_atendimento = $_POST['limite_atendimento'];
        $admin_email        = $_POST['admin_email'];

        $AuditorLog = new AuditorLog;
        $AuditorLog->RetornaDadosTabela('tbl_callcenter_email',array("fabrica" => $login_fabrica));

        pg_query($con, 'BEGIN');
        if (!empty($email_consulta)) {
            if (!empty($senha)) {
                $cond = ",senha = '$senha'";
            }
            $sql = "UPDATE tbl_callcenter_email SET
                        email = '{$email}',
                        hostname = '{$hostname_email}',
                        limite_atendimento = {$limite_atendimento},
                        ativa = '{$ativo}' {$cond}
                    WHERE callcenter_email = {$email_consulta} AND fabrica = {$login_fabrica} RETURNING callcenter_email";
        }else{
            $sql = "INSERT INTO tbl_callcenter_email (
                        fabrica,
                        email,
                        hostname,
                        senha,
                        limite_atendimento,
                        ativa,
                        admin_cadastro
                    ) VALUES(
                        {$login_fabrica},
                        '{$email}',
                        '{$hostname_email}',
                        '{$senha}',
                        {$limite_atendimento},
                        '{$ativo}',
                        {$login_admin}
                    ) RETURNING callcenter_email";
        }
        $res = pg_query($con, $sql);
        if (strlen(pg_last_error()) > 0) {
            if (!empty($email_consulta)) {
                $msg_erro['erro'][]   = 'Ocorreu um erro ao tentar alterar o email';
            }else{
                $msg_erro['erro'][]   = 'Ocorreu um erro ao tentar cadastrar um novo email';
            }
            pg_query($con, 'ROLLBACK');
        }else{
            $callcenter_email_id = pg_fetch_result($res, 0, 'callcenter_email');

            pg_prepare($con, 'inclui_callcenter_email_admin', "INSERT INTO tbl_callcenter_email_admin(callcenter_email,admin) VALUES ({$callcenter_email_id}, $1)");
            pg_prepare($con, 'verifica_callcenter_email_admin', "SELECT callcenter_email_admin FROM tbl_callcenter_email_admin WHERE callcenter_email = {$callcenter_email_id} AND admin = $1");
            foreach ($admin_email as $adm) {
                $res = pg_execute($con, 'verifica_callcenter_email_admin', array($adm));
                if (pg_num_rows($res) == 0) {
                    pg_execute($con, 'inclui_callcenter_email_admin', array($adm));
                    if (strlen(pg_last_error()) > 0) { break; }
                }
            }

            if (strlen(pg_last_error()) > 0) {
                if (!empty($email_consulta)) {
                    $msg_erro['erro'][]   = 'Ocorreu um erro ao tentar alterar o email';
                }else{
                    $msg_erro['erro'][]   = 'Ocorreu um erro ao tentar cadastrar um novo email';
                }
                pg_query($con, 'ROLLBACK');
            }else{
                if (!empty($email_consulta)) {
                    $sql = "UPDATE tbl_admin SET
                                login = '{$email}',
                                email = '{$email}',
                                nome_completo = '{$email}'
                            WHERE callcenter_email = {$callcenter_email_id} AND fabrica = {$login_fabrica}";
                }else{
                    $sql = "INSERT INTO tbl_admin (
                                fabrica,
                                senha,
                                login,
                                email,
                                nome_completo,
                                ativo,
                                callcenter_email
                            ) VALUES (
                                {$login_fabrica},
                                '*',
                                '{$email}',
                                '{$email}',
                                '{$email}',
                                false,
                                {$callcenter_email_id}
                            )";
                }
                pg_query($con, $sql);
                if (strlen(pg_last_error()) > 0) {
                    if (!empty($email_consulta)) {
                        $msg_erro['erro'][]   = 'Ocorreu um erro ao tentar alterar o email';
                    }else{
                        $msg_erro['erro'][]   = 'Ocorreu um erro ao tentar cadastrar um novo email';
                    }
                    pg_query($con, 'ROLLBACK');
                }else{
                    if (!empty($email_consulta)) {
                        $action = 'update';
                        $msg_sucesso = 'Alteração concluido com sucesso';
                    }else{
                        $action = 'insert';
                        $msg_sucesso = 'Cadastro concluido com sucesso';
                    }
                    pg_query($con, 'COMMIT');

                    $AuditorLog->RetornaDadosTabela()->EnviarLog($action, 'tbl_callcenter_email',"$login_fabrica");

                    /* LIMPA AS INFORMAÇÕES*/
                    $senha = $email = $hostname_email = $email_consulta = '';
                    $admin_email = array();
                    $ativo = 't';
                    $limite_atendimento = 1;
                }
            }
        }
    }
}
if (isset($_GET['email_consulta'])) {
    $sql = "SELECT
                email,
                hostname,
                limite_atendimento,
                ativa
            FROM tbl_callcenter_email
            WHERE callcenter_email = ".$_GET['email_consulta']." AND fabrica = {$login_fabrica}";
    $res = pg_query($con, $sql);
    if (pg_num_rows($res) > 0) {
        $email = pg_fetch_result($res, 0, 'email');
        $hostname_email = pg_fetch_result($res, 0, 'hostname');
        $limite_atendimento = pg_fetch_result($res, 0, 'limite_atendimento');
        $ativo = pg_fetch_result($res, 0, 'ativa');

        $sql = "SELECT admin FROM tbl_callcenter_email_admin WHERE callcenter_email = ".$_GET['email_consulta'];
        $res = pg_query($con, $sql);
        $admin_email = pg_fetch_all_columns($res);
    }else{
        $msg_erro['erro'][] = 'Não foi possível carregar o registro selecionado';
    }
}

if (in_array($login_fabrica, array(174))){
    $hd_chamado_origem_descricao = "Intelipost";
}else{
    $hd_chamado_origem_descricao = "Email";
}

$joinOrigem  = (in_array($login_fabrica, [198])) ? "" : "JOIN tbl_hd_chamado_origem ON tbl_hd_chamado_origem.hd_chamado_origem = tbl_hd_origem_admin.hd_chamado_origem";
$andOrigem   = (in_array($login_fabrica, [198])) ? "" : "AND tbl_hd_chamado_origem.descricao = '$hd_chamado_origem_descricao'";
$useDistinct = (in_array($login_fabrica, [198])) ? "DISTINCT(tbl_admin.admin)," : "tbl_admin.admin,";

$sql_admin = "SELECT {$useDistinct}
                tbl_admin.login,
                tbl_admin.callcenter_supervisor,
                tbl_admin.atendente_callcenter
            FROM tbl_hd_origem_admin
                {$joinOrigem}
                JOIN tbl_admin ON tbl_admin.admin = tbl_hd_origem_admin.admin AND tbl_admin.fabrica = {$login_fabrica}
            WHERE tbl_hd_origem_admin.fabrica = {$login_fabrica}
                AND tbl_admin.ativo IS TRUE
                {$andOrigem}
                AND (tbl_admin.callcenter_supervisor IS TRUE OR tbl_admin.atendente_callcenter IS TRUE) ORDER BY tbl_admin.login";
$res_admin = pg_query($con, $sql_admin);

include 'cabecalho_new.php';
include 'plugin_loader.php';
?>
<?php if (count($msg_erro['erro'])) { ?>
<div class="alert alert-error">
    <h4><?=implode('<br />', $msg_erro['erro']);?></h4>
</div>
<?php } ?>
<?php if (strlen($msg_sucesso) > 0) { ?>
<div class="alert alert-success">
    <h4><?=$msg_sucesso;?></h4>
</div>
<?php } ?>
<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form class='form-search form-inline tc_formulario' name="frm_familia" method="post" action="manutencao_email_atendimento.php">
    <input type="hidden" name="email_consulta" value="<?=$email_consulta?>">
    <div class="titulo_tabela">Cadastro de Email</div><br />
    <div class="row-fluid">
        <div class="span2"></div>
        <div class="span4">
            <div class="control-group <?=(in_array('hostname_email', $msg_erro['campos'])) ? 'error' : ''?>">
                <label class="control-label" for='hostname_email'>Hostname</label>
                <div class='controls controls-row'>
                    <h5 class="asteristico">*</h5>
                    <input class="span12" type="text" id="hostname_email" name="hostname_email" value="<?=$hostname_email?>"/>
                </div>
            </div>
        </div>
        <div class="span4">
            <div class="control-group <?=(in_array('limite_atendimento', $msg_erro['campos'])) ? 'error' : ''?>">
                <label class="control-label" for='limite_atendimento'>Limite de atendimento por usuário</label>
                <div class='controls controls-row'>
                    <input class="span9" type="number" min="1" id="limite_atendimento" name="limite_atendimento" value="<?=(!empty($limite_atendimento)) ? $limite_atendimento : 1?>"/>
                </div>
            </div>
        </div>
    </div>
    <div class="row-fluid">
        <div class="span2"></div>
        <div class="span4">
            <div class="control-group <?=(in_array('email', $msg_erro['campos'])) ? 'error' : ''?>">
                <label class="control-label" for='email'>Email</label>
                <div class='controls controls-row'>
                    <h5 class="asteristico">*</h5>
                    <input class="span12" type="text" id="email" name="email" value="<?=$email?>"/>
                </div>
            </div>
        </div>
        <div class="span4">
            <?php
                if($_GET['email_consulta']){
                    $label_senha = "Nova senha";
                }else{
                    $label_senha = "Senha";
                }
            ?>
            <div class="control-group <?=(in_array('senha', $msg_erro['campos'])) ? 'error' : ''?>">
                <label class="control-label" for='senha'><?=$label_senha?></label>
                <div class='controls controls-row'>
                    <?php if (empty($email_consulta)) { ?>
                    <h5 class="asteristico">*</h5>
                    <?php } ?>
                    <input class="span9" type="password" id="senha" name="senha" value="<?=$senha_email?>"/>
                </div>
                <input type="checkbox" name="visualizar"> Mostrar senha
            </div>
        </div>
    </div>
    <div class="row-fluid">
        <div class="span2"></div>
        <div class="span4">
            <div class="control-group <?=(in_array('admin_email', $msg_erro['campos'])) ? 'error' : ''?>">
                <label class="control-label" for='ativo'>Admin</label>
                <div class='controls controls-row'>
                    <h5 class="asteristico">*</h5>
                    <select class="span12" id="admin_email" name="admin_email[]" multiple="multiple">
                        <?php
                        for ($i=0; $i < pg_num_rows($res_admin); $i++) {
                            $admin = pg_fetch_result($res_admin, $i, 'admin');
                            $login = pg_fetch_result($res_admin, $i, 'login');
                            $selected = (in_array($admin, $admin_email)) ? 'selected' : '';
                            echo "<option value='{$admin}' {$selected}>{$login}</option>";
                        } ?>
                    </select>
                </div>
            </div>
        </div>
        <div class="span2">
            <div class="control-group">
                <label class="control-label" for='ativo'></label>
                <div class='controls controls-row'>
                    Ativo <input type="checkbox" id="ativo" name="ativo" value="t" <?=($ativo == 't' || empty($ativo)) ? 'checked' : ''?>/>
                </div>
            </div>
        </div>
    </div>
    <div class="row-fluid">
        <div class="span4"></div>
        <div class="span4 tac">
            <button class="btn" alt="Gravar formulário" name="btn_acao" value="gravar">Gravar</button>
            <?php if (isset($_GET['email_consulta'])) { ?>
            <a class="btn btn-warning" alt="Limpar formulário" href="manutencao_email_atendimento.php">Limpar</a>
            <?php } ?>
        </div>
    </div>
</form>
<?php
/* LISTA TODOS OS CADASTROS */
$sql = "SELECT callcenter_email, email, ativa, limite_atendimento
        FROM tbl_callcenter_email WHERE fabrica = {$login_fabrica}";
$res = pg_query($con, $sql);
if (pg_num_rows($res) > 0) { ?>
<div class="row-fluid">
    <table id="resultado_emails" class='table table-striped table-bordered table-hover table-fixed' >
        <thead>
            <tr class='titulo_coluna' >
                <th>Email</th>
                <th>Limite de atendimento por usuário</th>
                <th>Ativo</th>
                <th>Operação</th>
            </tr>
        </thead>
        <tbody>
            <?php for ($i=0; $i < pg_num_rows($res); $i++) {
                $ativo = pg_fetch_result($res, $i, 'ativa');
                $ativo = ($ativo == 't') ? "<img title='Ativo' src='imagens/status_verde.png'>" : "<img title='Inativo' src='imagens/status_vermelho.png'>";

            ?>
            <tr>
                <td class='tac'><?=pg_fetch_result($res, $i, 'email') ?></td>
                <td class='tac'><?=pg_fetch_result($res, $i, 'limite_atendimento') ?></td>
                <td class='tac'><?=$ativo?></td>
                <td class='tac'><a class="btn btn-small btn-primary" href='manutencao_email_atendimento.php?email_consulta=<?=pg_fetch_result($res, $i, 'callcenter_email') ?>'>Alterar</a></td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
</div>
<div class="row-fluid" style="text-align: center;">
    <a rel="shadowbox" href="relatorio_log_alteracao_new.php?parametro=tbl_callcenter_email" name="btnAuditorLog">Visualizar Log Auditor</a>
</div>
<?php } ?>
<script type="text/javascript">
    $(function(){
        Shadowbox.init();

        $('#admin_email').multiselect();
        $.dataTableLoad(
            {
                table: "#resultado_emails",
                type: 'custom',
                config: [ 'pesquisa' ],
            }
        );

        $('input[name=visualizar]').click(function(){
            if ($('input[name=visualizar]').is(':checked')) {
                $('#senha').attr('type', 'text');
            }else{
                $('#senha').attr('type', 'password');
            }
        });
    });
</script>
<?php include "rodape.php"; ?>
