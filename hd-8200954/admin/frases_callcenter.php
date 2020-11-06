<?php
$admin_privilegios = 'cadastros';
$layout_menu       = 'cadastro';
$title             = 'Frases Callcenter';
$plugins           = array('select2', 'ckeditor', 'dataTable');

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

if (isset($_POST['ajax']) && $_POST['ajax'] == 'sim') {
    if ($_POST['action'] == 'excluir_frase') {
        $sql = "DELETE FROM tbl_frase_callcenter WHERE frase_callcenter = ".$_POST['id'];
        pg_query($con, $sql);
        if (strlen(pg_last_error()) == 0) {
            exit(json_encode(array("ok" => "Registro excluido com sucesso")));
        }
        exit(json_encode(array("erro" => "Ocorreu um erro ao tentar excluir o registro selecionado")));
    }elseif ($_POST['action'] == 'consulta_texto') {
        $sql = "SELECT frase FROM tbl_frase_callcenter WHERE frase_callcenter = ".$_POST['id'];
        $res = pg_query($con, $sql);
        exit(json_encode(array("ok" => utf8_encode(pg_fetch_result($res, 0, 'frase')))));
    }
}

$saudacao = $callcenterSaudacaoInicial[0];
$assinatura = $callcenterAssinaturaAtendente[0];

if (isset($_POST['grava_assinatura'])) {
    if (empty($_POST['assinatura'])) {
        $msg_erro['erro'][] = "Preencha os campos obrigatórios.";
    }

    $assinatura = $_POST['assinatura'];
    if (!preg_match("/@atendente/", $assinatura)) {
        $msg_erro['erro'][] = "Você deve utilizar um dos coringas.";
    }

    if (empty($msg_erro)) {
        $queryParams = "SELECT
                            parametros_adicionais
                        FROM tbl_fabrica
                        WHERE fabrica = {$login_fabrica}";
        $result = pg_query($con, $queryParams);
        $resultParams = pg_fetch_result($result, 0, "parametros_adicionais");
        $resultParams = json_decode($resultParams, true);

        $resultParams['callcenterAssinaturaAtendente'] = [];
        $resultParams['callcenterAssinaturaAtendente'][] = iconv("ISO-8859-1", "UTF-8", $assinatura);

        $resultParams = json_encode($resultParams);

        pg_query($con, "BEGIN");

        $queryParams = "UPDATE tbl_fabrica
                        SET parametros_adicionais = '{$resultParams}'
                        WHERE fabrica = {$login_fabrica}";
        $result = pg_query($con, $queryParams);

        if (strlen(pg_last_error()) > 0 OR pg_affected_rows($result) > 1) {
            $msg_erro['erro'][] = "Erro ao salvar assinatura.";
            pg_query($con, "ROLLBACK");
        } else {
            $msg_sucesso = 'Cadastro realizado com sucesso';
            pg_query($con, "COMMIT");
        }
    }
}

if (isset($_POST['grava_saudacao'])) {
    $saudacao = $_POST['saudacao'];
    if (!preg_match("/@consumidor/", $saudacao)) {
        $msg_erro['erro'][] = "Você deve utilizar um dos coringas.";
    } elseif (empty($saudacao)) {
        $msg_erro['erro'][] = "Preencha os campos obrigatórios.";
    }

    if (empty($msg_erro)) {
        $queryParams = "SELECT
                            parametros_adicionais
                        FROM tbl_fabrica
                        WHERE fabrica = {$login_fabrica}";
        $result = pg_query($con, $queryParams);
        $resultParams = pg_fetch_result($result, 0, "parametros_adicionais");
        $resultParams = json_decode($resultParams, true);

        $resultParams['callcenterSaudacaoInicial'] = [];
        $resultParams['callcenterSaudacaoInicial'][] = iconv("ISO-8859-1", "UTF-8", $saudacao);

        $resultParams = json_encode($resultParams);

        pg_query($con, "BEGIN");

        $queryParams = "UPDATE tbl_fabrica
                        SET parametros_adicionais = '{$resultParams}'
                        WHERE fabrica = {$login_fabrica}";
        $result = pg_query($con, $queryParams);

        if (strlen(pg_last_error()) > 0 OR pg_affected_rows($result) > 1) {
            $msg_erro['erro'][] = "Erro ao salvar saldação.";
            pg_query($con, "ROLLBACK");
        } else {
            $msg_sucesso = 'Cadastro realizado com sucesso';
            pg_query($con, "COMMIT");
        }
    }
}

if (isset($_POST['frase']) && count($_POST)) {
    $origem = $_POST['origem'];
    $frase  = $_POST['frase'];
    $titulo = $_POST['titulo'];
    $frase_callcenter = $_POST['frase_callcenter']; 

    if (empty($titulo)) {
        $msg_erro['campos'][] = 'titulo';
    }

    if (empty($origem) and in_array($login_fabrica, [169,170])) {
        $msg_erro['campos'][] = 'origem';
    }else{
        $origem = 'null';
    }

    if (empty($frase)) {
        $msg_erro['campos'][] = 'frase';
    }else{
        $frase = pg_escape_string(html_entity_decode($frase, ENT_QUOTES, 'ISO-8859-1'));
    }

    if (count($msg_erro['campos'])) {
        $msg_erro['erro'][] = 'Preencha os campos obrigatórios';
    }

    if (!count($msg_erro["erro"])) {
        if (!empty($frase_callcenter)){
            $sql = "
                SELECT frase_callcenter FROM tbl_frase_callcenter WHERE fabrica = {$login_fabrica} AND frase_callcenter = {$frase_callcenter};
            ";
            $res = pg_query($con, $sql);

            if (pg_num_rows($res) > 0) {
                $sql = "
                    UPDATE tbl_frase_callcenter SET titulo = '{$titulo}', hd_chamado_origem = {$origem}, frase = '{$frase}'
                    WHERE fabrica = {$login_fabrica}
                    AND frase_callcenter = {$frase_callcenter}
                ";
                $res = pg_query($con, $sql);

                if (strlen(pg_last_error()) > 0) {
                    $msg_erro['erro'][] = 'Erro ao Atualizar Frase';
                }else{
                    header("Location: frases_callcenter.php");
                }

            }
        }else{
            $sql_valida = "SELECT frase_callcenter
                    FROM tbl_frase_callcenter
                    WHERE fabrica = {$login_fabrica}
                    AND titulo = '{$titulo}'
                    AND frase = '{$frase}'
                    AND hd_chamado_origem = {$origem}
            ";
            $res_valida = pg_query($con, $sql_valida);

            if (pg_num_rows($res_valida) > 0){
                $msg_erro['erro'][] = 'Já existe um registro com esses dados.';
            }

            if (!count($msg_erro["erro"])) {
                $sql = "INSERT INTO tbl_frase_callcenter(
                            fabrica,
                            frase,
                            hd_chamado_origem,
                            titulo
                        ) VALUES (
                            {$login_fabrica},
                            '{$frase}',
                            {$origem},
                            '{$titulo}'
                        )";
                pg_query($con, $sql);
                if (strlen(pg_last_error()) > 0) {
                    $msg_erro['erro'][] = 'Ocorreu um erro ao tentar gravar uma nova frase para o callcenter';
                }else{
                    unset($origem);
                    unset($frase);
                    unset($titulo);
                    $msg_sucesso = 'Cadastro realizado com sucesso';
                }
            }
        }
    }
}

$cond = '';
if (isset($_GET['origem'])) {
    $cond = ' AND tbl_frase_callcenter.hd_chamado_origem = '.$_GET['origem'];
}

if (isset($_GET['id'])){
    $cond = 'AND tbl_frase_callcenter.frase_callcenter = '.$_GET['id'];
}

$sql = "SELECT
            tbl_frase_callcenter.titulo,
            tbl_frase_callcenter.frase_callcenter,
            tbl_hd_chamado_origem.descricao,
            tbl_hd_chamado_origem.hd_chamado_origem,
            tbl_frase_callcenter.frase
        FROM tbl_frase_callcenter
            LEFT JOIN tbl_hd_chamado_origem ON tbl_hd_chamado_origem.hd_chamado_origem = tbl_frase_callcenter.hd_chamado_origem AND tbl_hd_chamado_origem.fabrica = {$login_fabrica}
        WHERE tbl_frase_callcenter.fabrica = {$login_fabrica} {$cond}";
$res_pesquisa = pg_query($con, $sql);
if (pg_num_rows($res_pesquisa) > 0 AND isset($_GET['id'])){
    $titulo = pg_fetch_result($res_pesquisa, 0, 'titulo');
    $frase  = pg_fetch_result($res_pesquisa, 0, 'frase');
    $origem = pg_fetch_result($res_pesquisa, 0, 'hd_chamado_origem');
    $frase_callcenter = pg_fetch_result($res_pesquisa, 0, 'frase_callcenter');
}

if (!isset($_GET['consulta_callcenter'])) {
    include 'cabecalho_new.php';
}else{
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/bootstrap.css" />
    <link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/extra.css" />
    <link type="text/css" rel="stylesheet" media="screen" href="css/tc_css.css" />
    <link type="text/css" rel="stylesheet" media="screen" href="css/tooltips.css" />
    <link type="text/css" rel="stylesheet" media="screen" href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css">
    <link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/ajuste.css" />
    <script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
    <script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
    <script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.core.min.js"></script>
    <script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.widget.min.js"></script>
    <script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.effect.min.js"></script>
    <script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.tabs.min.js"></script>
    <script src="bootstrap/js/bootstrap.js"></script>
</head>
<body>
<?php
}

include 'plugin_loader.php';

$sql = "SELECT
            hd_chamado_origem,
            descricao
        FROM tbl_hd_chamado_origem
        WHERE fabrica = $login_fabrica AND ativo IS TRUE ORDER BY descricao;";
$res_origem = pg_query($con, $sql);
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
<style type="text/css">
    .table{
        width: 100% !important;
    }
</style>
<div id='msg_sucesso' class="row-fluid" style="<?=$display?>">
    <div class="alert alert-success"><h4><?=$msg_sucesso ?></h4></div>
</div>
<?php if (in_array($login_fabrica, [174])) { ?>
    <div class="row">
        <b class="obrigatorio pull-right">* Campos obrigatórios</b>
    </div>
    <form class="form-search form-inline tc_formulario" name="frm_assinatura" method="POST" action="">
        <div style="background-color:#596D9B;text-align:center;padding:5px 0px;color:#FFF;font-weight:bold;font-size:16px;">Assinatura do Atendente</div>
        <div class="row-fluid" style="padding:10px 0">
            <div class="span2"></div>
            <div class="span8">
                <div class="control-group" style="padding:20px 0">
                    <label class="control-label" for="assinatura">Digita o texto da assinatura utilizando os coringas disponíveis:</label>
                    <div class="controls control-row">
                        <h5 class="asteristico">*</h5>
                        <textarea type="text" name="assinatura" style="width:100%;resize:none"><?= $assinatura ?></textarea>
                    </div>
                </div>
            </div>
            <div class="span2"></div>
        </div>
        <div class="row-fluid">
            <div class="span12 alert alert-warning">
                <b>Coringas disponíveis:</b><br />
                Nome do atendente: @atendente
            </div>
        </div>
        <div class="row-fluid" style="text-align:center;">
            <button type="submit" name="grava_assinatura">Gravar</button>
        </div>
    </form>
    <div class="row">
        <b class="obrigatorio pull-right">* Campos obrigatórios</b>
    </div>
    <form class="form-search form-inline tc_formulario" name="frm_saudacao" method="POST" action="">
        <div style="background-color:#596D9B;text-align:center;padding:5px 0px;color:#FFF;font-weight:bold;font-size:16px;">Saudações iniciais</div>
        <div class="row-fluid" style="padding:10px 0">
            <div class="span2"></div>
            <div class="span8">
                <div class="control-group" style="padding:20px 0">
                    <label class="control-label" for="saudacao">Digite o texto da saudação com os coringas disponíveis:</label>
                    <div class="controls control-row">
                        <h5 class="asteristico">*</h5>
                        <input type="text" name="saudacao" style="width:100%" value="<?= $saudacao ?>">
                    </div>
                </div>
            </div>
            <div class="span2"></div>
        </div>
        <div class="row-fluid">
            <div class="span12 alert alert-info">
                <b>Coringas disponíveis:</b><br />
                Nome do consumidor: @consumidor
            </div>
        </div>
        <div class="row-fluid" style="text-align:center;">
            <button type="submit" name="grava_saudacao">Gravar</button>
        </div>
    </form>
<?php } ?>
<?php if (!isset($_GET['consulta_callcenter'])) { ?>
<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form class='form-search form-inline tc_formulario' name="frm_super_atendente" method="post" action="">
    <input type="hidden" name="frase_callcenter" id='frase_callcenter' value='<?=$frase_callcenter?>'>
    <div class="titulo_tabela"><?=$title ?></div>
    <br/>
    <div class="row-fluid">
        <div class="span1"></div>
        <div class="span4">
            <div class="control-group <?=(in_array('titulo', $msg_erro['campos'])) ? 'error' : '' ?>">
                <label class="control-label" for='titulo'>Título</label>
                <div class='controls controls-row'>
                    <h5 class="asteristico">*</h5>
                    <input type="text" name="titulo" id="titulo" value="<?=$titulo?>">
                </div>
            </div>
        </div>
        <?php if(!in_array($login_fabrica, array(90, 174, 186))){ ?>
        <div class="span4">
            <div class="control-group <?=(in_array('origem', $msg_erro['campos'])) ? 'error' : '' ?>">
                <label class="control-label" for='origem'>Origem</label>
                <div class='controls controls-row'>
                    <h5 class="asteristico">*</h5>
                    <select id="origem" name="origem">
                        <option value=""></option>
                        <?php
                        for ($i=0; $i < pg_num_rows($res_origem); $i++) {
                            $selected = ($origem == pg_fetch_result($res_origem, $i, 'hd_chamado_origem')) ? 'selected' : '';

                            echo "<option value='".pg_fetch_result($res_origem, $i, 'hd_chamado_origem')."' {$selected}>".pg_fetch_result($res_origem, $i, 'descricao')."</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
        </div>
        <?php } ?>
        <div class="span1"></div>
    </div>
    <div class="row-fluid">
        <div class="span1"></div>
        <div class="span10">
            <div class="control-group <?=(in_array('frase', $msg_erro['campos'])) ? 'error' : '' ?>">
                <label class="control-label" for='frase'>Frase</label>
                <div class='controls controls-row'>
                    <h5 class="asteristico">*</h5>
                    <textarea name="frase" id="frase"><?=$frase ?></textarea>
                </div>
            </div>
        </div>
    </div><br />
    <div class="row-fluid">
        <div class="span4"></div>
        <div class="span4 tac">
            <?php if(isset($_GET['id'])){$label_btn = "Atualizar";}else{$label_btn = "Gravar";} ?>
            <button class="btn" id="btn_acao" value="gravar" alt="Gravar formulário"><?=$label_btn?></button>
        </div>
    </div>
</form>
<?php } if (pg_num_rows($res_pesquisa) > 0) { ?>
<br />
<div class="row-fluid" id="row-tabela">
    <div class="span12">
        <table id="resultado_frase_callcenter" class='table table-striped table-bordered table-hover table-large' >
            <thead>
                <tr class='titulo_coluna' >
                    <th>Título</th>
                    <?php if(!in_array($login_fabrica, [90, 174, 186])){?>
                    <th>Origem</th>
                    <?php } ?>
                    <th>Frase</th>
                    <th>Operação</th>
                </tr>
            </thead>
            <tbody>
                <?php for ($i=0; $i < pg_num_rows($res_pesquisa); $i++) {
                    $id = trim(pg_fetch_result($res_pesquisa, $i, 'frase_callcenter'));
                ?>
                <tr>
                    <td class='tac'><?=pg_fetch_result($res_pesquisa, $i, 'titulo') ?></td>
                    <?php if(!in_array($login_fabrica, [90, 174, 186])){?>
                    <td class='tac'><?=pg_fetch_result($res_pesquisa, $i, 'descricao') ?></td>
                    <?php } ?>
                    <td class='tac'><?=pg_fetch_result($res_pesquisa, $i, 'frase') ?></td>
                    <td class='tac'>
                        <?php if (!isset($_GET['consulta_callcenter'])) { ?>
                        <button type="button" class="btn btn-danger" data-del="<?=pg_fetch_result($res_pesquisa, $i, 'frase_callcenter') ?>">Excluir</button>
                        <a href="frases_callcenter.php?id=<?=$id?>"> <button type="button" class="btn">Alterar</button> </a>
                        <?php }else{ ?>
                        <button type="button" class="btn btn-primary" data-id="<?=pg_fetch_result($res_pesquisa, $i, 'frase_callcenter') ?>">Selecionar</button>
                        <?php } ?>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>
<?php } ?>
<script type="text/javascript">
    var wordCountConf = {
        showParagraphs: false,
        showWordCount: false,
        showCharCount: true,
        countSpacesAsChars: false,
        countHTML: false,
        maxWordCount: -1
    }

    $(function(){
        if ($('#frase').length) {
            CKEDITOR.replace("frase",
                {
                    enterMode : CKEDITOR.ENTER_BR,
                    wordcount: wordCountConf,
                    width: '100%'
                }
            );
        }
        $.dataTableLoad(
            {
                table: "#resultado_frase_callcenter",
                type: 'custom',
                config: [ 'pesquisa' ],
            }
        );

        $(document).on('click', '.btn-danger', function(){
            if (confirm('Deseja realmente deletar esse registro?')) {
                var id = $(this).data('del');
                var btn_excluir = $(this);
                $.ajax({
                    url: window.location.href,
                    type: 'POST',
                    data: { ajax: 'sim', action: 'excluir_frase', id: id },
                    timeout: 8000
                }).fail(function(){
                    $('#msg_erro').show().find('h4').html('Ocorreu um erro ao tentar excluir o registro selecionado');
                }).done(function(data){
                    data = JSON.parse(data);
                    if (data.ok !== undefined) {
                        $('#msg_sucesso').show().find('h4').html(data.ok);
                        btn_excluir.parent().parent().remove();
                        if($('#resultado_frase_callcenter tbody tr').length == 0){
                            $('#row-tabela').hide();
                        }
                    }else{
                        $('#msg_erro').show().find('h4').html(data.erro);
                    }
                });
            }
        });

        $('.btn-primary').on('click', function(){
            var id = $(this).data('id');
            $.ajax({
                url: window.location.href,
                type: 'POST',
                data: { ajax: 'sim', action: 'consulta_texto', id: id },
                timeout: 8000
            }).fail(function(){
                alert('Ocorreu um erro ao tentar selecionar o registro');
            }).done(function(data){
                data = JSON.parse(data);
                window.parent.retorna_frase_callcenter(data.ok);
                window.parent.Shadowbox.close();
            });
        });
    });
</script>
<?php
if (!isset($_GET['consulta_callcenter'])) {
    include "rodape.php";
}else{
    echo "</body>
            </html>";
}
?>
