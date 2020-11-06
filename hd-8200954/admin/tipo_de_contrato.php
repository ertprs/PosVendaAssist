<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';
include_once '../class/AuditorLog.php';

if (!$moduloGestaoContrato) {
    echo "<meta http-equiv=refresh content=\"0;URL=menu_gerencia.php\">";
}

use GestaoContrato\TipoContrato;

$objTipoContrato   = new TipoContrato($login_fabrica, $con);
$url_redir         = "<meta http-equiv=refresh content=\"1;URL=tipo_de_contrato.php\">";

if ($_GET["acao"] == "edit" && isset($_GET["tipo_contrato"]) && strlen($_GET["tipo_contrato"]) > 0) {
    $retorno   = $objTipoContrato->get($_GET["tipo_contrato"]);
    $dadosTipo = $objTipoContrato->get();
} else {
    $dadosTipo = $objTipoContrato->get();
}

if ($_POST["tipo_acao"] == "add") {
    $retorno        = $_POST;
    $codigo         = $_POST["codigo"];
    $descricao      = $_POST["descricao"];
    $mao_de_obra    = ($_POST["mao_de_obra"] == "t")    ? "t" : "f";
    $pecas          = ($_POST["pecas"] == "t")          ? "t" : "f";
    $consumiveis    = ($_POST["consumiveis"] == "t")    ? "t" : "f";
    $sla            = ($_POST["sla"] == "t")            ? "t" : "f";
    $ativo          = ($_POST["ativo"] == "t")          ? "t" : "f";

    if (strlen($codigo) == 0) {
        $msg_erro["msg"][] = "Campo Código é obrigatório";
        $msg_erro["campos"][] = "codigo";
    }

    if (strlen($descricao) == 0) {
        $msg_erro["msg"][] = "Campo Descrição é obrigatório";
        $msg_erro["campos"][] = "descricao";
    }

    if (count($msg_erro["msg"]) == 0) {
        $resB = pg_query($con,"BEGIN TRANSACTION");

        $auditorLog = new AuditorLog('insert');

        $dadosSave = [
                        "codigo"        => $codigo,
                        "descricao"     => $descricao,
                        "mao_de_obra"   => $mao_de_obra,
                        "pecas"         => $pecas,
                        "consumiveis"   => $consumiveis,
                        "sla"           => $sla,
                        "ativo"         => $ativo,
        ];
      
        $result   = $objTipoContrato->add($dadosSave);

        if (isset($result["erro"]) && $result["erro"] == true) {
            $msg_erro["msg"][] = $result["msn"];
        } else {
            $sqlLog   = "SELECT * FROM tbl_tipo_contrato WHERE tipo_contrato = ".$result["tipo_contrato"];
            $auditorLog->retornaDadosSelect($sqlLog)->enviarLog('insert', 'tbl_tipo_contrato', $login_fabrica.'*'.$result["tipo_contrato"]);
            $msg_sucesso["msg"][] = "Gravado com sucesso";
            echo $url_redir;
        }

        if (count($msg_erro['msg']) == 0) {
            $resB = pg_query($con,"COMMIT TRANSACTION");
        } else {
            $resB = pg_query($con,"ROLLBACK TRANSACTION");
        }
    }
}

if ($_POST["tipo_acao"] == "edit") {

    $tipo_contrato  = $_POST["tipo_contrato"];
    $codigo         = $_POST["codigo"];
    $descricao      = $_POST["descricao"];
    $mao_de_obra    = ($_POST["mao_de_obra"] == "t")    ? "t" : "f";
    $pecas          = ($_POST["pecas"] == "t")          ? "t" : "f";
    $consumiveis    = ($_POST["consumiveis"] == "t")    ? "t" : "f";
    $sla            = ($_POST["sla"] == "t")            ? "t" : "f";
    $ativo          = ($_POST["ativo"] == "t")          ? "t" : "f";

    if (strlen($codigo) == 0) {
        $msg_erro["msg"][] = "Campo Código é obrigatório";
        $msg_erro["campos"][] = "codigo";
    }

    if (strlen($descricao) == 0) {
        $msg_erro["msg"][] = "Campo Descrição é obrigatório";
        $msg_erro["campos"][] = "descricao";
    }

    if (count($msg_erro["msg"]) == 0) {
        $resB = pg_query($con,"BEGIN TRANSACTION");
        $dadosSave = [
                        "codigo"        => $codigo,
                        "descricao"     => $descricao,
                        "mao_de_obra"   => $mao_de_obra,
                        "pecas"         => $pecas,
                        "consumiveis"   => $consumiveis,
                        "sla"           => $sla,
                        "ativo"         => $ativo,
        ];


        $auditorLog = new AuditorLog;
        $auditorLog->retornaDadosSelect("SELECT * FROM tbl_tipo_contrato WHERE tipo_contrato =".$tipo_contrato);

        $result   = $objTipoContrato->edit($tipo_contrato, $dadosSave);
        if (isset($result["erro"]) && $result["erro"] == true) {
            $msg_erro["msg"][] = $result["msn"];
        } else {
            $auditorLog->retornaDadosSelect()->enviarLog('update', 'tbl_tipo_contrato', trim($login_fabrica.'*'.$tipo_contrato));

            $msg_sucesso["msg"][] = "Gravado com sucesso";
            echo $url_redir;
        }
        if (count($msg_erro['msg']) == 0) {
            $resB = pg_query($con,"COMMIT TRANSACTION");
        } else {
            $resB = pg_query($con,"ROLLBACK TRANSACTION");
        }
    }
}

$layout_menu       = "gerencia";
$admin_privilegios = "gerencia";
$title             = "Tipo de Contrato";

include 'cabecalho_new.php';

$plugins = array(
    "shadowbox",
    "dataTable",
    "multiselect"
);

include("plugin_loader.php");
?>
<style>
    .icon-edit {
        background-position: -95px -75px;
    }
    .icon-remove {
        background-position: -312px -3px;
    }
    .icon-search {
        background-position: -48px -1px;
    }
</style>
<script language="javascript">
    var hora = new Date();
    var engana = hora.getTime();

    $(function() {
        Shadowbox.init();
        $.dataTableLoad("#tabela");
        $(".multiple").multiselect({
           selectedText: "# of # selected"
        });

    });
</script>
    <?php if (count($msg_erro["msg"]) > 0) {?>
        <div class="alert alert-error">
            <h4><?php echo implode("<br />", $msg_erro["msg"]);?></h4>
        </div>
    <?php }?>

    <?php if (count($msg_sucesso["msg"]) > 0){?>
        <div class="alert alert-success">
            <h4><?php echo implode("<br />", $msg_sucesso["msg"]);?></h4>
        </div>
    <?php }?>

    <div class="row">
        <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
    </div>

    <form name='frm_relatorio' METHOD='POST' enctype="multipart/form-data" ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
        <?php if (isset($_GET["acao"]) && $_GET["acao"] == "edit") {?>
            <input type="hidden" name="tipo_acao" value="edit">
            <input type="hidden" name="tipo_contrato" value="<?php echo (isset($retorno["tipo_contrato"]) && strlen($retorno["tipo_contrato"]) > 0) ? $retorno["tipo_contrato"] : "";?>">
        <?php } else {?> 
            <input type="hidden" name="tipo_acao" value="add">
        <?php }?> 
        <div class='titulo_tabela '>Cadastro</div>
        <br/>
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span2'>
                <div class='control-group <?=(in_array("codigo", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label'>Código</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <h5 class='asteristico'>*</h5>
                            <input type="text" class="span12" value="<?php echo (isset($retorno["codigo"]) && strlen($retorno["codigo"]) > 0) ? $retorno["codigo"] : "";?>" name="codigo" id="codigo">
                        </div>
                    </div>
                </div>
            </div>
            <div class='span6'>
                <div class='control-group <?=(in_array("descricao", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label'>Descrição</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <h5 class='asteristico'>*</h5>
                            <input type="text" class="span12" value="<?php echo (isset($retorno["descricao"]) && strlen($retorno["descricao"]) > 0) ? $retorno["descricao"] : "";?>" name="descricao" id="descricao">
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span2'>
                <div class='control-group'>
                    <label class='control-label' for='mao_de_obra'>Mão de Obra</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <input type="checkbox" name="mao_de_obra" <?php echo (isset($retorno["mao_de_obra"]) && $retorno["mao_de_obra"] == 't') ? "checked" : "";?> id="mao_de_obra" value="t"> 
                            <div><strong></strong></div>
                        </div>  
                    </div>
                </div>
            </div>
            <div class='span1'>
                <div class='control-group'>
                    <label class='control-label' for='pecas'>Peças</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <input type="checkbox" name="pecas" <?php echo (isset($retorno["mao_de_obra"]) && $retorno["mao_de_obra"] == 't') ? "checked" : "";?> id="pecas" value="t">
                            <div><strong></strong></div>
                        </div>  
                    </div>
                </div>
            </div>
            <div class='span2'>
                <div class='control-group'>
                    <label class='control-label' for='consumiveis'>Consumíveis</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <input type="checkbox" name="consumiveis" <?php echo (isset($retorno["consumiveis"]) && $retorno["consumiveis"] == 't') ? "checked" : "";?> id="consumiveis" value="t">
                            <div><strong></strong></div>
                        </div>  
                    </div>
                </div>
            </div>
            <div class='span1'>
                <div class='control-group'>
                    <label class='control-label' for='sla'>SLA</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <input type="checkbox" name="sla" <?php echo (isset($retorno["sla"]) && $retorno["sla"] == 't') ? "checked" : "";?> id="sla" value="t">
                            <div><strong></strong></div>
                        </div>  
                    </div>
                </div>
            </div>
            <div class='span1'>
                <div class='control-group'>
                    <label class='control-label' for='ativo'>Ativo</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <input type="checkbox" name="ativo" <?php echo (isset($retorno["ativo"]) && $retorno["ativo"] == 't') ? "checked" : "";?> id="ativo" value="t">
                            <div><strong></strong></div>
                        </div>  
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>
        <p><br/>
                <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Gravar</button>
                <input type='hidden' id="btn_click" name='btn_acao' value='' />
            </p><br/>
    </form> <br />

    <?php
        if ($dadosTipo["erro"]) {
            echo '<div class="alert alert-waring"><h4>'.$dadosTipo["msn"].'</h4></div>';
        } else {
    ?>

    <table class='table table-striped table-bordered table-hover table-fixed' id='tabela'>
        <thead>
            <tr class='titulo_coluna' >
                <th align="left">Código</th>
                <th align="left">Descrição</th>
                <th>Mão de Obra</th>
                <th>Peças</th>
                <th>Consumíveis</th>
                <th>SLA</th>
                <th>Ativo</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($dadosTipo as $k => $rows) {?>
            <tr>
                <td class='tal'><?php echo $rows["codigo"];?></td>
                <td class='tal'><?php echo $rows["descricao"];?></td>
                <td class='tac'>
                    <?php echo ($rows["mao_de_obra"] == 't') ? '<img title="Sim" src="imagens/status_verde.png">' : '<img title="Não" src="imagens/status_vermelho.png">';?>
                </td>
                <td class='tac'>
                    <?php echo ($rows["pecas"] == 't') ? '<img title="Sim" src="imagens/status_verde.png">' : '<img title="Não" src="imagens/status_vermelho.png">';?>
                </td>
                <td class='tac'>
                    <?php echo ($rows["consumiveis"] == 't') ? '<img title="Sim" src="imagens/status_verde.png">' : '<img title="Não" src="imagens/status_vermelho.png">';?>
                </td>
                <td class='tac'>
                    <?php echo ($rows["sla"] == 't') ? '<img title="Sim" src="imagens/status_verde.png">' : '<img title="Não" src="imagens/status_vermelho.png">';?>
                </td>
                <td class='tac'>
                    <?php echo ($rows["ativo"] == 't') ? '<img title="Ativo" src="imagens/status_verde.png">' : '<img title="Inativo" src="imagens/status_vermelho.png">';?>
                </td>
                <td class='tac'>
                    <a href="tipo_de_contrato.php?acao=edit&tipo_contrato=<?php echo $rows["tipo_contrato"];?>" class="btn btn-info btn-mini" title="Editar"><i class="icon-edit icon-white"></i></a>
                    <a rel='shadowbox' href='relatorio_log_alteracao_new.php?parametro=tbl_tipo_contrato&id=<?php echo $login_fabrica."*".$rows["tipo_contrato"];?>'><button class='btn btn-mini btn-primary'>Log</button></a>

                </td>
            </tr>
        <?php }?>
        </tbody>
    </table>
    <?php }?>
</div> 
<?php include 'rodape.php';?>
