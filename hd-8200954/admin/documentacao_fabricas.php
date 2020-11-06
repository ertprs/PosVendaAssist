<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro,gerencia,call_center";
include 'autentica_admin.php';
include 'funcoes.php';

include_once "../class/tdocs.class.php";
if(!in_array($login_admin, array(4960,6497))){
    $permissao = "false";
    $msg_erro["msg"][] = "Você não tem permissão para acessar essa tela";
}

if ($_POST["btn_gravar"] == "update") {

    $xfabrica            = $_POST['fabrica'];
    $versao             = $_POST['versao'];
    $hd_chamado         = $_POST['hd_chamado'];
    $descricao          = $_POST['descricao'];
    $ativo_inativo      = $_POST['ativo_inativo'];
    $documentacao       = $_POST['documentacao'];

    $file_documentacao  = $_FILES['file_documentacao'];

    if(strlen(trim($xfabrica)) == 0){
        $msg_erro["msg"][]    = "O campo fábrica é obrigatório";
        $msg_erro["campos"][] = "fabrica";
    }

    if(strlen(trim($versao)) == 0){
        $msg_erro["msg"][]    = "O campo versão é obrigatório";
        $msg_erro["campos"][] = "versao";
    }

    if(strlen(trim($hd_chamado)) == 0){
        $msg_erro["msg"][]    = "O campo número do chamado é obrigatório";
        $msg_erro["campos"][] = "hd_chamado";
    }

    if(strlen(trim($descricao)) == 0){
        $msg_erro["msg"][]    = "O campo descrição é obrigatório";
        $msg_erro["campos"][] = "descricao";
    }

    // if(strlen(trim($file_documentacao['name'])) == 0){
    //     $msg_erro["msg"][]    = "O anexo da documentação é obrigatório";
    //     $msg_erro["campos"][] = "file_documentacao";
    // }

    if(!count($msg_erro["msg"])){
        $sql = "SELECT hd_chamado, fabrica FROM tbl_hd_chamado WHERE hd_chamado = $hd_chamado AND fabrica = $xfabrica";
        $res = pg_query($con, $sql);
        $erro = pg_last_error($con);
        if(pg_num_rows($res) == 0 OR strlen($erro) > 0){
            $msg_erro["msg"][]    = "Número de chamado não encontrado para a fábrica";
            $msg_erro["campos"][] = "hd_chamado";
        }

        $sql = "SELECT change_log FROM tbl_change_log WHERE fabrica = $xfabrica AND titulo = '$versao'";
        $res = pg_query($con, $sql);
        $erro = pg_last_error($con);
        if(pg_num_rows($res) > 0 OR strlen($erro) > 0){
            $doc_versao = pg_fetch_result($res, 0, 'change_log');
            if($doc_versao <> $documentacao){
                $msg_erro["msg"][]    = "Já existe essa versão gravada para essa fábrica";
                $msg_erro["campos"][] = "versao";
            }
        }

        $sql = "SELECT change_log FROM tbl_change_log WHERE fabrica = $xfabrica AND ativo = 't' ";
        $res = pg_query($con, $sql);
        $erro = pg_last_error($con);
        if(pg_num_rows($res) > 0 OR strlen($erro) > 0){
            $doc_ativo = pg_fetch_result($res, 0, 'change_log');
            if($doc_ativo <> $documentacao){
                $msg_erro["msg"][]    = "Já existe uma documentação ativa para essa fábrica";
                $msg_erro["campos"][] = "tipo";
            }

        }
    }
    if (!count($msg_erro["msg"])) {
        $update = " UPDATE tbl_change_log
                    SET hd_chamado = $hd_chamado,
                        titulo = '$versao',
                        admin = $login_admin,
                        fabrica = $xfabrica,
                        change_log_fabrica = '$descricao',
                        ativo = '$ativo_inativo',
                        data_atualizacao = now()
                    WHERE change_log = $documentacao
                    AND fabrica = $xfabrica ";
        $res = pg_query($con, $update);
        $erro = pg_last_error($con);
        if(strlen($erro) == 0){
            if(strlen(trim($file_documentacao['name'])) > 0){
                $s3 = new TDocs($con, $xfabrica);
                $s3->setContext('documentacao');

                if($s3->uploadFileS3($file_documentacao, $xfabrica,  true)){
                    $documents = $s3->getdocumentsByRef($xfabrica, 'documentacao')->attachListInfo;
                    foreach ($documents as $key => $value) {
                        $rash = $value['tdocs_id'];
                        break;
                    }
                    $update = "UPDATE tbl_change_log set change_log_interno = '$rash' WHERE change_log = $documentacao AND fabrica = $xfabrica";
                    $res_up = pg_query($con, $update);

                    $msg_success = "Documentação alterada com sucesso";
                }else{
                    $msg_erro["msg"][] = "Erro ao anexar documentação";
                }
            }

        }else{
            $msg_erro["msg"][] = "Erro ao atualizar documentação";
        }
    }

    // if(count($msg_erro['msg']) > 0){
    //     #header("Location:documentacao_fabricas.php?documentacao=$documentacao");
    // }
}

if ($_POST["btn_gravar"] == "gravar") {

    $xfabrica            = $_POST['fabrica'];
    $versao             = $_POST['versao'];
    $hd_chamado         = $_POST['hd_chamado'];
    $descricao          = $_POST['descricao'];
    $ativo_inativo      = $_POST['ativo_inativo'];

    $file_documentacao  = $_FILES['file_documentacao'];

    if(strlen(trim($xfabrica)) == 0){
        $msg_erro["msg"][]    = "O campo fábrica é obrigatório";
        $msg_erro["campos"][] = "fabrica";
    }

    if(strlen(trim($versao)) == 0){
        $msg_erro["msg"][]    = "O campo versão é obrigatório";
        $msg_erro["campos"][] = "versao";
    }

    if(strlen(trim($hd_chamado)) == 0){
        $msg_erro["msg"][]    = "O campo número do chamado é obrigatório";
        $msg_erro["campos"][] = "hd_chamado";
    }

    if(strlen(trim($descricao)) == 0){
        $msg_erro["msg"][]    = "O campo descrição é obrigatório";
        $msg_erro["campos"][] = "descricao";
    }

    if(strlen(trim($file_documentacao['name'])) == 0){
        $msg_erro["msg"][]    = "Por favor, anexar a documentação";
        $msg_erro["campos"][] = "file_documentacao";
    }

    if(!count($msg_erro["msg"])){
        $sql = "SELECT hd_chamado, fabrica FROM tbl_hd_chamado WHERE hd_chamado = $hd_chamado AND fabrica = $xfabrica";
        $res = pg_query($con, $sql);
        $erro = pg_last_error($con);
        if(pg_num_rows($res) == 0 OR strlen($erro) > 0){
            $msg_erro["msg"][]    = "Número de chamado não encontrado para a fábrica";
            $msg_erro["campos"][] = "hd_chamado";
        }

        $sql = "SELECT titulo FROM tbl_change_log WHERE fabrica = $xfabrica AND titulo = '$versao'";
        $res = pg_query($con, $sql);
        $erro = pg_last_error($con);
        if(pg_num_rows($res) > 0 OR strlen($erro) > 0){
            $msg_erro["msg"][]    = "Já existe essa versão gravada para essa fábrica";
            $msg_erro["campos"][] = "versao";
        }

        if($ativo_inativo == 't'){
            $sql = "SELECT change_log FROM tbl_change_log WHERE fabrica = $xfabrica AND ativo = 't' ";
            $res = pg_query($con, $sql);
            $erro = pg_last_error($con);
            if(pg_num_rows($res) > 0 OR strlen($erro) > 0){
                $msg_erro["msg"][]    = "Já existe uma documentação ativa para essa fábrica";
            }
        }

    }
    if (!count($msg_erro["msg"])) {
        $res = pg_query ($con,"BEGIN TRANSACTION");
        $insert = "INSERT INTO tbl_change_log (
                        hd_chamado,
                        titulo,
                        admin,
                        fabrica,
                        change_log_fabrica,
                        ativo,
                        tipo,
                        data
                    )VALUES(
                        $hd_chamado,
                        '$versao',
                        $login_admin,
                        $xfabrica,
                        '$descricao',
                        '$ativo_inativo',
                        'documentacao',
                        now()
                    )";
        $res = pg_query($con, $insert);
        $erro = pg_last_error($con);

        if(strlen($erro) == 0){
            $sql = "SELECT CURRVAL ('seq_change_log')";
            $res = pg_query ($con,$sql);
            $change_log = pg_fetch_result ($res,0,0);
            $erro = pg_last_error($con);

            if(strlen($erro) == 0){
                $s3 = new TDocs($con, $xfabrica);
                $s3->setContext('documentacao');

                if($s3->uploadFileS3($file_documentacao, $xfabrica,  false)){
                    $documents = $s3->getdocumentsByRef($xfabrica, 'documentacao')->attachListInfo;
                    foreach ($documents as $key => $value) {
                        $rash = $value['tdocs_id'];
                        $update = "UPDATE tbl_change_log set change_log_interno = '$rash' WHERE change_log = $change_log AND fabrica = $xfabrica";
                        $res_up = pg_query($con, $update);
                    }
                }else{
                    $msg_erro["msg"][] = "Erro ao anexar documentação";
                }
            }

        }else{
            $msg_erro["msg"][] = "Erro ao cadastrar documentação.";
        }

        if (!count($msg_erro["msg"])) {
            $msg_success = "Documentação gravada com sucesso";
            $res = pg_query ($con,"COMMIT TRANSACTION");
        }else{
            $res = pg_query ($con,"ROLLBACK TRANSACTION");
        }
    }

}

if ($_POST["btn_acao"] == "submit" OR isset($_GET['documentacao']) OR $_POST["btn_gravar"] == "update") {
    $xfabrica            = $_POST['fabrica'];
    $versao             = $_POST['versao'];
    $hd_chamado         = $_POST['hd_chamado'];
    $ativo_inativo      = $_POST['ativo_inativo'];

    if($_POST["btn_acao"] == "submit"){
        unset($documentacao);
    }

    if(strlen(trim($xfabrica)) > 0){
        $cond_fabrica = " AND tbl_change_log.fabrica = $xfabrica ";
    }

    if(strlen(trim($versao)) > 0){
        $cond_versao = " AND tbl_change_log.titulo = '$versao' ";
    }

    if(strlen(trim($hd_chamado)) > 0){
        $cond_chamado = " AND tbl_change_log.hd_chamado = $hd_chamado ";
    }

    if(strlen(trim($ativo_inativo)) > 0){
        $cond_ativo_inativo = " AND tbl_change_log.ativo = '$ativo_inativo' ";
    }

    if(strlen($_GET['documentacao']) > 0){
        $documentacao = $_GET['documentacao'];
        $cond_documentaocao = " AND tbl_change_log.change_log = $documentacao ";
    }

    $sql = "SELECT tbl_change_log.hd_chamado,
                    tbl_change_log.change_log,
                    tbl_change_log.titulo,
                    tbl_change_log.change_log_fabrica,
                    tbl_change_log.ativo,
                    TO_CHAR(tbl_change_log.data, 'DD/MM/YYYY') AS data,
                    tbl_fabrica.nome,
                    tbl_change_log.fabrica,
                    tbl_admin.nome_completo
                FROM tbl_change_log
                JOIN tbl_admin ON tbl_admin.admin = tbl_change_log.admin
                JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_change_log.fabrica
                WHERE 1=1
                $cond_fabrica
                $cond_versao
                $cond_chamado
                $cond_ativo_inativo
                $cond_documentaocao";
    $res = pg_query($con, $sql);
    $erro = pg_last_error($con);
    $resSubmit = pg_query($con, $sql);

    if(strlen($documentacao) > 0){
        $rows = pg_num_rows($resSubmit);
        #for ($x=0; $x < $rows; $x++) {
            $xfabrica            = pg_fetch_result($resSubmit, 0, 'fabrica');
            $change_log         = pg_fetch_result($resSubmit, 0, 'change_log');
            $hd_chamado         = pg_fetch_result($resSubmit, 0, 'hd_chamado');
            $versao             = pg_fetch_result($resSubmit, 0, 'titulo');
            $descricao          = pg_fetch_result($resSubmit, 0, 'change_log_fabrica');
            $tipo               = pg_fetch_result($resSubmit, 0, 'ativo');
            $data               = pg_fetch_result($resSubmit, 0, 'data');
            $nome               = pg_fetch_result($resSubmit, 0, 'nome');
            $nome_completo      = pg_fetch_result($resSubmit, 0, 'nome_completo');
        #}
    }
}

if($_POST["btn_acao"] == "ativar"){
    $change_log = $_POST["change_log"];
    $xfabrica = $_POST['fabrica'];

    $sql = "SELECT change_log FROM tbl_change_log WHERE fabrica = $xfabrica AND ativo = 't' ";
    $res = pg_query($con, $sql);

    if(pg_num_rows($res) > 0){
        echo "error";
    }else{
        $sql = "UPDATE tbl_change_log SET ativo = 't' WHERE change_log = {$change_log}";
        $res = pg_query($con, $sql);

        if (!pg_last_error()) {
            echo "success";
        } else {
            echo "error";
        }
    }
    exit;
}

if ($_POST["btn_acao"] == "inativar") {
    $change_log = $_POST["change_log"];

    $sql = "UPDATE tbl_change_log SET ativo = 'f' WHERE change_log = {$change_log}";
    $res = pg_query($con, $sql);

    if (!pg_last_error()) {
        echo "success";
    } else {
        echo "error";
    }
    exit;
}

$layout_menu = "gerencia";
$title = "CADASTRO DOCUMENTAÇÃO FÁBRICAS";
include 'cabecalho_new.php';


$plugins = array(
    "autocomplete",
    "datepicker",
    "shadowbox",
    "mask",
    "dataTable",
    "tooltip"
);

include("plugin_loader.php");
?>
<style type="text/css">

.fileUpload {
    position: relative;
    overflow: hidden;
    margin: 10px;
}
.fileUpload input.upload {
    position: absolute;
    top: 0;
    right: 0;
    margin: 0;
    padding: 0;
    font-size: 20px;
    cursor: pointer;
    opacity: 0;
    filter: alpha(opacity=0);
}
</style>
<script type="text/javascript" src="js/jquery.alphanumeric.js"></script>
<script type="text/javascript">
    var hora = new Date();
    var engana = hora.getTime();

    $(function() {
        $("#versao").mask('9.99.99');
        $('#uploadBtn').change(function(){
            var upload = $(this).val();
            $("#uploadFile").val(upload);
        });
        //$("#hd_chamado").numeric();


        $('#btnPopover').popover('hide')

        $(document).on("click", "button[name=ativar]", function () {
            if (ajaxAction()) {
                var fabrica = $(this).parent().find("input[name=id_fabrica]").val();
                var change_log = $(this).parent().find("input[name=change_log]").val();
                var that     = $(this);

                $.ajax({
                    async: false,
                    url: "<?=$_SERVER['PHP_SELF']?>",
                    type: "POST",
                    dataType: "JSON",
                    data: { btn_acao: "ativar", change_log: change_log, fabrica: fabrica },
                    beforeSend: function () {
                        loading("show");
                    },
                    complete: function (data) {
                        data = data.responseText;
                        if (data == "success") {
                            $(that).removeClass("btn-success").addClass("btn-danger");
                            $(that).attr({ "name": "inativar", "title": "Alterar a condição de pagamento para não visível" });
                            $(that).text("Inativar");
                            //$(that).parents("tr").find("img[name=visivel]").attr({ "src": "imagens/status_verde.png?" + (new Date()).getTime() });
                        }else{
                            alert("Já existe uma documentação ativa para essa fábrica");
                        }
                        loading("hide");
                    }
                });
            }
        });

        $(document).on("click", "button[name=inativar]", function () {
            if (ajaxAction()) {
                //var fabrica = $(this).parent().find("input[name=id_fabrica]").val();
                var change_log = $(this).parent().find("input[name=change_log]").val();
                var that     = $(this);

                $.ajax({
                    async: false,
                    url: "<?=$_SERVER['PHP_SELF']?>",
                    type: "POST",
                    dataType: "JSON",
                    data: { btn_acao: "inativar", change_log: change_log },
                    beforeSend: function () {
                        loading("show");
                    },
                    complete: function (data) {
                        data = data.responseText;

                        if (data == "success") {
                            $(that).removeClass("btn-danger").addClass("btn-success");
                            $(that).attr({ "name": "ativar", "title": "Alterar a condição de pagamento para visível" });
                            $(that).text("Ativar");
                            //$(that).parents("tr").find("img[name=visivel]").attr({ "src": "imagens/status_vermelho.png?" + (new Date()).getTime() });
                        }
                        loading("hide");
                    }
                });
            }
        });
    });

    function visualizar(linha){
        $('#'+linha).show();
        $("#visualizar_"+linha).hide();
        $("#ocultar_"+linha).show();
    }
    function ocultar(linha){
        $('#'+linha).hide();
        $("#ocultar_"+linha).hide();
        $("#visualizar_"+linha).show();
    }

    function limparCampos(){
        $('#frm_relatorio').each (function(){
          $(':input').val('');
        });
    }

</script>

<?php

if(strlen($_GET['msg']) > 0){
    $erro = $_GET['msg'];
    $erro = explode(',', $erro);
    $msg_erro["msg"] = array_merge($erro);
}

if (count($msg_erro["msg"]) > 0 OR $_GET['msg'] == 'error') {
?>
    <div class="alert alert-error">
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
    if($permissao == "false"){
        exit;
    }
}
?>

<?php
    if(strlen($msg_success) > 0){
?>
    <div class="alert alert-success">
        <h4><?=$msg_success;?></h4>
    </div>
<?php
    }
?>

<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios somente para inserção </b>
</div>

<form name='frm_relatorio' id='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' enctype='multipart/form-data' class='form-search form-inline tc_formulario' >
        <div class='titulo_tabela '>Cadastro de Documentação</div>
        <br/>
        <!-- Fábrica/Versão -->
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span4'>
                <div class='control-group <?=(in_array("fabrica", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='fabrica'>Fábrica</label>
                    <div class='controls controls-row'>
                        <div class='span4'>
                            <h5 class='asteristico'>*</h5>
                            <select name="fabrica" id="fabrica">
                                <option value=""></option>
                                <?php
                                $sql = "SELECT fabrica, nome
                                        FROM tbl_fabrica
                                        WHERE ativo_fabrica IS TRUE
                                        ORDER BY fabrica ASC";
                                $res = pg_query($con,$sql);
                                foreach (pg_fetch_all($res) as $key) {
                                    $selected_linha = ( isset($xfabrica) and ($xfabrica == $key['fabrica']) ) ? "SELECTED" : '' ;
                                ?>
                                    <option value="<?php echo $key['fabrica']?>" <?php echo $selected_linha ?> >
                                        <?php echo $key['fabrica'].' - '.$key['nome']?>
                                    </option>
                                <?php
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'>
                <div class='control-group <?=(in_array("versao", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='versao'>Versão</label>
                    <div class='controls controls-row'>
                        <div class='span10'>
                            <h5 class='asteristico'>*</h5>
                            <input type="text" id="versao" name="versao" class='span12' maxlength="20" value="<? echo $versao ?>" >
                            Ex.: 1.01.02
                            <span class="add-on"><i id="btnPopover" rel="popover" data-placement="top" data-trigger="hover" data-html="true" data-delay="500" title="Info" data-content="1. &nbsp; &nbsp;- Versão do Sistema<br/>00.&nbsp;&nbsp;- Alteração de telas<br/>00.&nbsp;&nbsp;- Alteração de telas" class="icon-question-sign" style='margin-top: 0px;'></i> </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'>
                <div class='control-group <?=(in_array("hd_chamado", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='hd_chamado'>Número Chamado</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <h5 class='asteristico'>*</h5>
                            <input type="text" id="hd_chamado" name="hd_chamado" class='span12' maxlength="20" value="<? echo $hd_chamado ?>" >
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>
        <!-- xxxx -->

        <!-- Descrição -->
        <div class="row-fluid">
            <div class="span2"></div>
            <div class='span8'>
                <div class='control-group <?=(in_array("descricao", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='descricao'>Descrição</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <h5 class='asteristico'>*</h5>
                            <textarea rows="3" name='descricao' value="<?=$descricao?>" class='span12'><?=$descricao?></textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>
        <!-- xxxx -->
        <br/>
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span4'>
                 <label class="radio">
                    <input type="radio" name="ativo_inativo" value="t" checked>
                    Ativo
                </label>
            </div>
            <div class='span4'>
                <label class="radio">
                    <input type="radio" name="ativo_inativo" value="f" <?php if($ativo_inativo == "f") echo "checked"; ?> >
                    Inativo
                </label>
            </div>
            <div class='span2'></div>
        </div>

        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span8'>
                <div class='control-group <?=(in_array("upload", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='upload'>Documentação</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <h5 class='asteristico'>*</h5>
                            <input id="uploadFile"  placeholder="" disabled="disabled" />
                            <div class="fileUpload btn">
                                <span>Upload</span>
                                <input id="uploadBtn" name='file_documentacao' type="file" class="upload" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>

        <p><br/>
            <?php
                if(strlen($documentacao) > 0){
            ?>
                <input type='hidden' name='documentacao' value='<?=$documentacao;?>'>
                <button type="submit" name="btn_gravar" value='update' class='btn btn-primary'>Alterar</button>
            <?php
                }else{
            ?>
                <button type="submit" name="btn_gravar" value='gravar' class='btn btn-primary'>Gravar</button>
            <?php
                }
            ?>

            <button class='btn btn-info' id="btn_limpar" type="button"  onclick="limparCampos();">Limpar campos</button>
            <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
            <input type='hidden' id="btn_click" name='btn_acao' value='' />
        </p><br/>
</form>
</div>
<?php
if (isset($resSubmit)) {
    if (pg_num_rows($resSubmit) > 0) {
        echo "<br />";
        $count = pg_num_rows($resSubmit);
    ?>
        <table id="resultado_documentacao" class='table table-striped table-bordered table-hover table-fixed' >
            <thead>
                <tr class='titulo_coluna' >
                    <th>Fábrica</th>
                    <th>Versão</th>
                    <th>Data</th>
                    <th>Número Chamado</th>
                    <th>Admin</th>
                    <th>Descrição</th>
                    <th>Status</th>
                    <th>Ação</th>
            </thead>
            <tbody>
                <?php
                for ($i = 0; $i < $count; $i++) {

                    $xfabrica            = pg_fetch_result($resSubmit, $i, 'fabrica');
                    $change_log         = pg_fetch_result($resSubmit, $i, 'change_log');
                    $hd_chamado         = pg_fetch_result($resSubmit, $i, 'hd_chamado');
                    $versao             = pg_fetch_result($resSubmit, $i, 'titulo');
                    $descricao          = pg_fetch_result($resSubmit, $i, 'change_log_fabrica');
                    $tipo               = pg_fetch_result($resSubmit, $i, 'ativo');
                    $data               = pg_fetch_result($resSubmit, $i, 'data');
                    $nome               = pg_fetch_result($resSubmit, $i, 'nome');
                    $nome_completo      = pg_fetch_result($resSubmit, $i, 'nome_completo');
                ?>
                    <tr>
                        <td class='tac' style='vertical-align: middle;'><?=$nome;?></td>
                        <td class='tac' style='vertical-align: middle;'><?=$versao;?></td>
                        <td class='tac' style='vertical-align: middle;'><?=$data;?></td>
                        <td class='tac' style='vertical-align: middle;'><?=$hd_chamado;?></td>
                        <td class='tac' style='vertical-align: middle;'><?=$nome_completo;?></td>
                        <td class='tac' style='vertical-align: middle;'>
                            <button class='btn btn-small' id='visualizar_<?=$change_log?>' onclick='visualizar(<?=$change_log?>);' >Visualizar descrição</button>
                            <button style='display:none;' class='btn btn-small' id='ocultar_<?=$change_log?>' onclick='ocultar(<?=$change_log?>);' >Ocultar descrição</button>
                        </td>
                        <td class='tac' style='vertical-align: middle;'>
                            <input type="hidden" name="change_log" value="<?=$change_log?>" />
                            <input type="hidden" name="id_fabrica" value="<?=$xfabrica?>" />
                            <?php
                            if ($tipo == "f") {
                                echo "<button type='button' name='ativar' class='btn btn-small btn-success' title='Alterar a documentação para Ativo' >Ativar</button>";
                            } else {
                                echo "<button type='button' name='inativar' class='btn btn-small btn-danger' title='Alterar a documentação para Inativo' >Inativar</button>";
                            }
                            ?>
                        </td>
                        <td class='tac' style='vertical-align: middle;'>
                            <a href='<?=$_SERVER['PHP_SELF']?>?documentacao=<?=$change_log;?>'>
                                <button type='button' name='alterar' class='btn btn-small btn-primary' title='Alterar a dados documentação' >Alterar</button>
                            </a>
                        </td>
                    </tr>
                    <tr id='<?=$change_log?>' style='display:none;'>
                        <td class='tac' colspan='8' style='vertical-align: middle;'>
                            <textarea readOnly rows='4' class='span12' ><?=$descricao;?></textarea>
                        </td>
                    </tr>
                <?php
                }
                ?>
            </tbody>
        </table>
        <br />
    <?php
    }else{
        echo '
        <div class="container">
        <div class="alert">
                <h4>Nenhum resultado encontrado</h4>
        </div>
        </div>';
    }
}



include 'rodape.php';?>
