<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

if ($_REQUEST["roteiro"]) {
    $roteiro = $_REQUEST["roteiro"];
    if (verificaRoteiro($roteiro)) {
        $erro = false;
    } else {
        $erro = true;
    }
} else {
    $erro = true;
}

if ($_POST) {
    $msg_erro = array();
    $msg_sucesso = array();

    $descricao = $_POST["descricao"];
 

    if (strlen($descricao) == 0) {
        $msg_erro["campos"][] = "descricao";
        $msg_erro["msg"][] = "Campo Motivo é obrigatório";
    }

    $dados = array();
    if (count($msg_erro["msg"]) == 0 ) {

        $dados["descricao"]             = $descricao;
        $dados["roteiro"]               = $roteiro;

        $retorno = cancelaRoteiro($dados);
        
        if (!$retorno["erro"]) {
            $msg_sucesso["msg"][] = $retorno["msg"];
            $checkin   = "";
            $checkout  = "";
            $descricao = "";
            echo "<meta http-equiv=refresh content=\"3;URL=listagem_roteiros.php\">";
        } else {
            $msg_erro["msg"][] = $retorno["msg"];
        }
    }
}

function cancelaRoteiro($dados = array()) {
    global $login_fabrica, $con, $login_admin;

    if (empty($dados)) {
        return array("erro" => true, "msg" => "Dados da visita, não enviado");
    }
    $sqlUp = "UPDATE tbl_roteiro 
                     SET status_roteiro = 4, 
                         admin=".$login_admin."
                   WHERE roteiro=".$dados['roteiro'];

    $resUp = pg_query($con, $sqlUp);
    if (pg_last_error($resUp)) {
        return array("erro" => true, "msg" => "Erro ao cancelar roteiro");
    }
    $sqlUp = "UPDATE tbl_roteiro_posto 
                     SET status = 'CC', 
                         motivo_reagendamento='".$dados['descricao']."'
                   WHERE roteiro=".$dados['roteiro'];
    $resUp = pg_query($con, $sqlUp);
    if (pg_last_error($resUp)) {
        return array("erro" => true, "msg" => "Erro ao cancelar roteiro");
    }
    return array("erro" => false, "msg" => "Roteiro cancelado com sucesso");
    
}

function verificaRoteiro($roteiro) {
    global $login_fabrica, $con;

    $sql = "SELECT tbl_roteiro.roteiro
                 FROM tbl_roteiro
                WHERE tbl_roteiro.fabrica = {$login_fabrica} 
                  AND tbl_roteiro.roteiro = {$roteiro}
                ";
    $res = pg_query($con, $sql);
    if (pg_num_rows($res) > 0) {
        return true;
    }
    return false;
}


$layout_menu = "tecnica";
$title = "Cancelar Roteiro";
include 'cabecalho_new.php';

$plugins = array(
    "datepicker",
    "mask",
    "shadowbox",
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

        $("#btn_acao").click(function() {
            $("form").submit();
        });

    });
</script>
<?php if ($erro == true) {?>
    <div class="alert alert-error">
        <h4>Nenhum roteiro encontrado</h4>
    </div>
<?php exit;}?>
<?php if (count($msg_erro["msg"]) > 0) {?>
    <div class="alert alert-error">
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php }?>
<?php if (count($msg_erro["msg"]) == 0 && count($msg_sucesso["msg"]) > 0) {?>
    <div class="alert alert-success">
        <h4><?=implode("<br />", $msg_sucesso["msg"])?></h4>
    </div>
<?php }?>
    <div class="row">
        <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
    </div>
    <form name='frm_relatorio' METHOD='POST' ACTION='cancela_roteiro.php?roteiro=<?php echo $roteiro;?>' align='center' class='form-search form-inline tc_formulario' >
        <input type="hidden" name="pesquisa" value="true">
        <div class='titulo_tabela '>Cancelamento de Roteiro</div>
        <br/>
        <div class="row-fluid">
            <div class="span2"></div>
            <div class="span8">
                <div class='control-group <?=(in_array("descricao", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='tecnico'>Motivo do Cancelamento</label>
                    <h5 class='asteristico'>*</h5>
                    <div class="controls controls-row">
                        <div class="span12">
                            <textarea name="descricao" id="descricao" class="span12"  rows="10"></textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div><br />
        
        <p><br/>
            <button class='btn btn-success' id="btn_acao" type="button">Gravar</button>
            <input type='hidden' id="btn_click" name='btn_acao' value='' />
        </p><br/>
    </form> <br />
  </div>
</div> 
<?php include 'rodape.php';?>
