<?php
$admin_privilegios = "callcenter,financeiro";
$layout_menu       = "cadastro";
$title             = "CADASTRO TIPO DE SOLICITAÇÃO";
$plugins           = array("dataTable");

include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
include_once 'autentica_admin.php';

if (isset($_POST['ajax']) && $_POST['ajax'] == 'sim') {
    if ($_POST['action'] == 'altera_status') {
        $ativo = ($_POST['ativa_inativa'] == 'inativa') ? 'false' : 'true';
        $id    = $_POST['tipo_solicitacao'];

        $sql = "UPDATE tbl_tipo_solicitacao SET ativo = $ativo WHERE tipo_solicitacao = $id";
        pg_query($con, $sql);
        if (strlen(pg_last_error()) > 0) {
            exit(json_encode(array("error" => "Ocorreu um erro ao tentar alterar o status do registro selecionado")));
        }
        exit(json_encode(array("ok" => "Registro alterado com sucesso")));
    }
}

if (isset($_POST['btn_acao']) && $_POST['btn_acao'] == 'cadastrar') {
    if (empty($descricao)) {
        $campos_erro[] = 'descricao';
    }
    if (empty($tipo_solicitacao)) {
        $campos_erro[] = 'tipo_solicitacao';
    }

    if (count($campos_erro) > 0) {
        $msg_erro = 'Preencha os campos obrigatórios';
    }else{
        $ativo = (empty($ativo)) ? 'false' : 'true';
        $sql = "INSERT INTO tbl_tipo_solicitacao(
                    fabrica,
                    descricao,
                    informacoes_adicionais,
                    ativo
                )VALUES(
                    {$login_fabrica},
                    '{$descricao}',
                    lower('{$tipo_solicitacao}'),
                    {$ativo}
                )";
        pg_query($con, $sql);
        if (strlen(pg_last_error()) > 0) {
            $msg_erro = 'Ocorreu um erro ao tentar cadastrar uma nova solicitação';
        }else{
            $msg_ok           = 'Solicitação cadastrada com sucesso';
            $descricao        = '';
            $tipo_solicitacao = '';
            $ativo            = '';
        }
    }
}

include_once 'cabecalho_new.php';
include_once 'plugin_loader.php';
?>
<div id="alertas_tela">
    <?php if (!empty($msg_erro)) { ?>
    <div class="alert alert-danger"><h4><?=$msg_erro;?></h4></div>
    <?php } ?>
    <?php if (!empty($msg_ok)) { ?>
    <div class="alert alert-success"><h4><?=$msg_ok;?></h4></div>
    <?php } ?>
</div>
<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form class='form-search form-inline tc_formulario' name="frm_familia" method="post" action="cadastra_tipo_solicitacao.php">
    <div class="titulo_tabela">Tipo de Solicitação</div>
    <br/>
    <div class="row-fluid">
        <div class="span2"></div>
        <div class="span4">
            <div class="control-group <?=(in_array('descricao', $campos_erro)) ? 'error' : '';?>">
                <label class="control-label" for='descricao'>Descrição</label>
                <div class='controls controls-row'>
                    <h5 class="asteristico">*</h5>
                    <input class="span12" type="text" name="descricao" maxlength="60" value="<?=$descricao?>">
                </div>
            </div>
        </div>
        <div class="span4">
            <div class="control-group <?=(in_array('tipo_solicitacao', $campos_erro)) ? 'error' : '';?>">
                <label class="control-label" for='tipo_solicitacao'>Tipo de Solicitação</label>
                <div class='controls controls-row'>
                    <h5 class="asteristico">*</h5>
                    <select class="span12" name="tipo_solicitacao">
                        <option value=""></option>
                        <option value="consumidor">Consumidor</option>
                        <option value="posto">Posto</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
    <div class="row-fluid">
        <div class="span2"></div>
        <div class="span4">
            <div class="control-group">
                <label class="control-label" for='ativo'>Ativo</label>
                <div class='controls controls-row'>
                    <input type="checkbox" name="ativo" value="true" <?=(empty($ativo) || $ativo == 'true') ? 'checked' : ''?>>
                </div>
            </div>
        </div>
    </div>
    <br />
    <div class="row-fluid">
        <div class="span4"></div>
        <div class="span4 tac">
            <button class="btn" name="btn_acao" value="cadastrar">Cadastrar</button>
        </div>
    </div>
</form>
<?php
$sql = "SELECT
            tipo_solicitacao,
            descricao,
            informacoes_adicionais,
            ativo
        FROM tbl_tipo_solicitacao WHERE fabrica = $login_fabrica";

$res = pg_query($con, $sql);
if (pg_num_rows($res) > 0) {
?>
<table id="resultado_tipo_solicitacao" class='table table-striped table-bordered table-hover table-large table-fixed' >
    <thead>
        <tr class='titulo_coluna' >
            <th>Descrição</th>
            <th>Tipo de Solicitação</th>
            <th>Ação</th>
        </tr>
    </thead>
    <tbody>
        <?php
        for ($i = 0; $i < pg_num_rows($res); $i++) {
            $descricao        = pg_fetch_result($res, $i, 'descricao');
            $tipo_solicitacao = pg_fetch_result($res, $i, 'informacoes_adicionais');
            $ativo            = pg_fetch_result($res, $i, 'ativo');
            $id               = pg_fetch_result($res, $i, 'tipo_solicitacao');
        ?>
        <tr>
            <td class='tac'><?=$descricao?></td>
            <td class='tac'><?=ucfirst($tipo_solicitacao)?></td>
            <td class='tac' width="10%"><?=($ativo == 't') ? "<button class='btn btn-danger' data-solicitacao='".$id."'>Inativar</button>" : "<button class='btn btn-success' data-solicitacao='".$id."'>Ativar</button>" ?></td>
        </tr>
        <?php } ?>
    </tbody>
</table>
<br />
<?php
}
?>
<script type="text/javascript">
    $(function(){
        $.dataTableLoad({ table: '#resultado_tipo_solicitacao' });


        $('.btn-danger,.btn-success').on('click', function(){
            var button        = $(this);
            var ativa_inativa = (button.hasClass('btn-danger')) ? 'inativa' : 'ativa';
            var tipo_solicitacao = button.data('solicitacao');

            $.ajax({
                url: window.location.href,
                type: 'POST',
                data: { ajax: 'sim', action: 'altera_status', ativa_inativa: ativa_inativa, tipo_solicitacao: tipo_solicitacao },
                timeout: 8000
            }).fail(function(){
                alert('Não foi possível alterar o status do registro selecionado, tente novamente mais tarde');
            }).done(function(data){
                data = JSON.parse(data);
                if (data.ok !== undefined) {
                    if (button.hasClass('btn-danger')) {
                        button.removeClass('btn-danger').addClass('btn-success').text('Ativar');
                    }else{
                        button.removeClass('btn-success').addClass('btn-danger').text('Inativar');
                    }
                }else{
                    alert(data.error);
                }
            });
        });
    });
</script>
<?php include_once "rodape.php"; ?>