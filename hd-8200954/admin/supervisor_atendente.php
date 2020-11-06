<?php
$admin_privilegios = 'cadastros';
$layout_menu       = 'cadastro';
$title             = 'Supervisor Callcenter x Atendente Callcenter';
$plugins           = array('select2');


include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
include_once 'autentica_admin.php';
include_once '../helpdesk/mlg_funciones.php';

// if ($_serverEnvironment == "development") {
//     $campo_id = "supervisor_callcenter";
// } else {
//     $campo_id = "supervisor_atendente";
// }

if (isset($_POST['ajax']) && $_POST['ajax'] == 'sim') {
    if ($_POST['action'] == 'exclui_relacionamento') {
        $supervisor = $_POST['supervisor'];
        $atendente  = $_POST['atendente'];

        $sql = "DELETE FROM tbl_supervisor_atendente
                WHERE fabrica = {$login_fabrica}
                    AND supervisor = $supervisor AND atendente = {$atendente}";
        pg_query($con, $sql);
        if (strlen(pg_last_error()) > 0) {
            exit(json_encode(array("erro" => "Ocorreu um erro ao tentar deletar o registro")));
        }
        exit(json_encode(array("ok" => "Registro deletado com sucesso")));
    }
}

$supervisor = $_POST['supervisor'];
$atendente  = $_POST['atendente'];

if (isset($_POST['btn_acao']) && $_POST['btn_acao'] == 'gravar') {
    if (empty($supervisor)) {
        $msg_erro['campos'][] = 'supervisor';
    }
    if (empty($atendente)) {
        $msg_erro['campos'][] = 'atendente';
    }

    if (count($msg_erro['campos'])) {
        $msg_erro['erro'][] = 'Preencha os campos obrigatórios';
    }else{
        $sql = "SELECT
                    supervisor_atendente
                FROM tbl_supervisor_atendente
                WHERE fabrica = {$login_fabrica}
                    AND supervisor = {$supervisor} AND atendente = {$atendente}";
        $res = pg_query($con, $sql);
        if (pg_num_rows($res) == 0) {
            $sql = "INSERT INTO tbl_supervisor_atendente (
                        fabrica,
                        supervisor,
                        atendente
                    ) VALUES (
                        {$login_fabrica},
                        {$supervisor},
                        {$atendente}
                    )";
            pg_query($con, $sql);
        }

        if (strlen(pg_last_error()) > 0) {
            $msg_erro['erro'][] = 'Ocorreu um erro ao tentar realizar um novo cadastro';
        }else{
            $msg_sucesso = 'Cadastro realizado com sucesso';
        }
    }
}elseif (!isset($_POST['btn_acao']) || (isset($_POST['btn_acao']) &&  $_POST['btn_acao'] == 'pesquisar')) {
    $cond_pesquisa = '';
    if (!empty($supervisor)) {
        $cond_pesquisa .= " AND supervisor = {$supervisor}";
    }
    if (!empty($atendente)) {
        $cond_pesquisa .= " AND atendente = {$atendente}";
    }
}

include 'cabecalho_new.php';
include 'plugin_loader.php';

$sql = "SELECT
            tbl_supervisor_atendente.supervisor_atendente,
            tbl_admin.login ||' - '|| tbl_admin.nome_completo AS supervisor,
            admin_atendente.login ||' - '|| admin_atendente.nome_completo AS atendente,
            tbl_supervisor_atendente.supervisor AS id_supervisor,
            tbl_supervisor_atendente.atendente AS id_atendente
        FROM tbl_supervisor_atendente
            JOIN tbl_admin ON tbl_admin.admin = tbl_supervisor_atendente.supervisor AND tbl_admin.fabrica = {$login_fabrica} AND tbl_admin.ativo
            JOIN tbl_admin AS admin_atendente ON admin_atendente.admin = tbl_supervisor_atendente.atendente AND admin_atendente.fabrica = {$login_fabrica}
        WHERE tbl_supervisor_atendente.fabrica = {$login_fabrica} {$cond_pesquisa} ORDER BY supervisor";

$res_pesquisa = pg_query($con, $sql);
echo pg_last_error();

$sql_admin = "SELECT
                admin,
                nome_completo,
                tbl_admin.callcenter_supervisor,
                tbl_admin.atendente_callcenter
            FROM tbl_admin WHERE ativo IS TRUE AND fabrica = {$login_fabrica}
                AND (tbl_admin.callcenter_supervisor IS TRUE OR tbl_admin.atendente_callcenter IS TRUE)";
$res = pg_query($con, $sql_admin);

$rows = pg_fetch_all($res);

foreach ($rows as $row) {
    if ($row['callcenter_supervisor'] == 't')
        $res_supervisor[$row['admin']] = $row['nome_completo'];

    if ($row['atendente_callcenter'] == 't')
        $res_atendente[$row['admin']] = $row['nome_completo'];
}
asort($res_supervisor);
asort($res_atendente);

$display = 'display: none';
if (count($msg_erro['erro']) > 0) {
    $display = '';
}
?>
<div id='msg_erro' class="row-fluid" style="<?=$display?>">
    <div class="alert alert-error"><h4><?=implode('<br />', $msg_erro['erro']) ?></h4></div>
</div>
<?php
$display = 'display: none';
if (strlen($msg_sucesso) > 0) {
    $display = '';
}
?>
<div id='msg_sucesso' class="row-fluid" style="<?=$display?>">
    <div class="alert alert-success"><h4><?=$msg_sucesso ?></h4></div>
</div>
<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios para cadastro </b>
</div>
<form class='form-search form-inline tc_formulario' name="frm_super_atendente" method="post" action="">
    <div class="titulo_tabela"><?=$title ?></div>
    <br/>
    <div class="row-fluid">
        <div class="span2"></div>
        <div class="span4">
            <div class="control-group <?=(in_array('supervisor', $msg_erro['campos'])) ? 'error' : '' ?>">
                <label class="control-label" for='supervisor'>Supervisor</label>
                <div class='controls controls-row'>
                    <h5 class="asteristico">*</h5>
                    <?=array2select('supervisor', 'supervisor', $res_supervisor, $_POST['supervisor'], '', ' ',true)?>
                </div>
            </div>
        </div>
        <div class="span4">
            <div class="control-group <?=(in_array('atendente', $msg_erro['campos'])) ? 'error' : '' ?>">
                <label class="control-label" for='atendente'>Atendente</label>
                <div class='controls controls-row'>
                    <h5 class="asteristico">*</h5>
                    <?=array2select('atendente', 'atendente', $res_atendente, $_POST['atendente'], '', ' ',true)?>
                </div>
            </div>
        </div>
    </div>
    <br />
    <div class="row-fluid">
        <div class="span4"></div>
        <div class="span4 tac">
            <button type="button" class="btn" id="btn_gravar" value="gravar" alt="Gravar formulário">Gravar</button>
            <button type="button" class="btn btn-primary" id="btn_pesquisar" value="pesquisar" alt="Pesquisar">Pesquisar</button>
            <input type="hidden" name="btn_acao">
        </div>
    </div>
</form>
<?php if (pg_num_rows($res_pesquisa) > 0) { ?>
<div class="row-fluid">
    <div class="span12">
        <?php
        $info_supervisor = '';
        for ($i=0; $i < pg_num_rows($res_pesquisa); $i++) {
            if ($info_supervisor !== pg_fetch_result($res_pesquisa, $i, 'supervisor')) {
                $info_supervisor = pg_fetch_result($res_pesquisa, $i, 'supervisor');
        ?>
        <table id="<?=pg_fetch_result($res_pesquisa, $i, 'id_supervisor') ?>" class='table table-striped table-bordered table-hover table-large'>
            <thead>
                <tr class='titulo_tabela' >
                        <th colspan="9">Supervisor: <?=pg_fetch_result($res_pesquisa, $i, 'supervisor') ?></th>
                </tr>
                <tr class='titulo_coluna' >
                    <th>Login</th>
                    <th width="50%">Nome</th>
                    <th>Ação</th>
                </tr>
            </thead>
            <tbody>
        <?php } ?>
                <tr>
                    <?php
                    $atendente = explode('-', pg_fetch_result($res_pesquisa, $i, 'atendente'));
                    echo "<td class='tac'>".trim($atendente[0])."</td>";
                    echo "<td class='tac'>".trim($atendente[1])."</td>";

                    $btn_exclui = pg_fetch_result($res_pesquisa, $i, 'id_supervisor').'|'.pg_fetch_result($res_pesquisa, $i, 'id_atendente')
                    ?>
                    <td class='tac'><button class="btn btn-danger" id="btn_exclui" data-del='<?=$btn_exclui ?>'>Excluir</button></td>
                </tr>
        <?php if ($info_supervisor !== pg_fetch_result($res_pesquisa, $i, 'supervisor')) {
        ?>
            </tbody>
        </table>
        <?php }
        } ?>
    </div>
</div>
<?php }else{ ?>
<div class="row-fluid">
    <div class="alert"><h4>Não foi localizado nenhum registro</h4></div>
</div>
<?php } ?>
<script type="text/javascript">
    $(function(){
        $('#supervisor, #atendente').select2();

        $('#btn_gravar, #btn_pesquisar').on('click', function(){
            $('input[name=btn_acao]').val($(this).val());
            $('form[name=frm_super_atendente]').submit();
        });

        $(document).on('click', '#btn_exclui', function(){
            if (confirm('Deseja realmente deletar este registro?')) {
                var IDs = $(this).data('del').split('|');
                var btn_exclui = $(this);

                $.ajax({
                    url: window.location.href,
                    type: 'POST',
                    data: { ajax: 'sim', action: 'exclui_relacionamento', supervisor: IDs[0], atendente: IDs[1] },
                    timeout: 8000
                }).fail(function(){
                }).done(function(data){
                    data = JSON.parse(data);
                    if (data.ok !== undefined) {
                        $('#msg_sucesso').show().find('h4').html(data.ok);
                        setTimeout(function(){
                            $('#msg_sucesso').hide();
                        }, 4000);

                        btn_exclui.closest("tr").remove();

                        $('#'+IDs[0]).filter(function(){
                            return !$(this).find("tbody > tr").length;
                        }).remove();

                    }else{
                        $('#msg_erro').show().find('h4').html(data.erro);
                        setTimeout(function(){
                            $('#msg_erro').hide();
                        }, 4000);
                    }
                });
            }
        });
    });
</script>
<?php include "rodape.php"; ?>
