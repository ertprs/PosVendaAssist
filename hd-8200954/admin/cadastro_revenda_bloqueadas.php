<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';
require "../class/AuditorLog.php";

if (filter_input(INPUT_POST,"desbloquear") == true) {
    $revenda_fabrica    = filter_input(INPUT_POST,"revenda_fabrica");

    $audProd    = new AuditorLog();

    $sqlAudLog  = "SELECT tbl_revenda.cnpj, tbl_revenda_fabrica.motivo_bloqueio, tbl_admin.nome_completo, TO_CHAR(tbl_revenda_fabrica.data_bloqueio,'DD/MM/YYYY') as data_bloqueio 
                                    FROM tbl_revenda
                                    INNER JOIN tbl_revenda_fabrica ON tbl_revenda_fabrica.revenda = tbl_revenda.revenda
                                    INNER JOIN tbl_admin on tbl_admin.admin = tbl_revenda_fabrica.admin_bloqueio AND tbl_admin.fabrica = $login_fabrica
                                    WHERE tbl_revenda_fabrica.fabrica = $login_fabrica
                                    AND tbl_revenda_fabrica.revenda = '$revenda_fabrica'";

    $audProd->retornaDadosSelect($sqlAudLog);

    $sql = "UPDATE tbl_revenda_fabrica SET
            admin_bloqueio = $login_admin,
            data_bloqueio = null
            WHERE fabrica = $login_fabrica
            AND revenda = $revenda_fabrica";
    $res = pg_query($con, $sql);
    if(strlen(pg_last_error($con))==0){
        $audProd->retornaDadosSelect($sqlAudLog)->enviarLog('INSERT','tbl_revenda_fabrica',$login_fabrica."*".$revenda_fabrica);
        echo json_encode(array('retorno' => 'ok'));
    }else{
        echo json_encode(array('retorno' => 'erro'));
    }
    exit;
}

if (filter_input(INPUT_GET,"revenda_fabrica")) {
    
    $revenda_fabrica    = filter_input(INPUT_GET,"revenda_fabrica");

    $sql = "SELECT tbl_revenda.nome, tbl_revenda.revenda, tbl_revenda.cnpj, tbl_revenda_fabrica.motivo_bloqueio, tbl_revenda_fabrica.revenda_fabrica 
                FROM tbl_revenda_fabrica 
                INNER JOIN tbl_revenda on tbl_revenda.revenda = tbl_revenda_fabrica.revenda
                WHERE fabrica = $login_fabrica and tbl_revenda_fabrica.revenda_fabrica = $revenda_fabrica";
    $res = pg_query($con,$sql);
    if (pg_num_rows($res) > 0) {
        $revenda_nome       = pg_fetch_result($res, 0, "nome");
        $revenda_cnpj       = pg_fetch_result($res, 0, "cnpj");
        $revenda            = pg_fetch_result($res, 0, "revenda");
        $motivo_bloqueio    = pg_fetch_result($res, 0, "motivo_bloqueio");
    }
}

if (filter_input(INPUT_POST,"btn_acao") == "submit") {

    $audProd    = new AuditorLog();

    $sqlAudLog  = "SELECT tbl_revenda.cnpj, tbl_revenda_fabrica.motivo_bloqueio, tbl_admin.nome_completo, TO_CHAR(tbl_revenda_fabrica.data_bloqueio,'DD/MM/YYYY') as data_bloqueio  
                                    FROM tbl_revenda
                                    INNER JOIN tbl_revenda_fabrica ON tbl_revenda_fabrica.revenda = tbl_revenda.revenda
                                    INNER JOIN tbl_admin on tbl_admin.admin = tbl_revenda_fabrica.admin_bloqueio AND tbl_admin.fabrica = $login_fabrica
                                    WHERE tbl_revenda_fabrica.fabrica = $login_fabrica
                                    AND tbl_revenda.cnpj = '$revenda_cnpj'";

    $audProd->retornaDadosSelect($sqlAudLog);

    $revenda_nome       = filter_input(INPUT_POST,"revenda_nome");
    $revenda_cnpj       = filter_input(INPUT_POST,"revenda_cnpj");
    $motivo             = filter_input(INPUT_POST,"motivo");

    if (strlen($revenda_cnpj) > 0 && strlen($revenda_nome) > 0) {
        $sql = "SELECT  revenda ,
                        cnpj ,
                        nome
                FROM    tbl_revenda
                WHERE   cnpj = '$revenda_cnpj';";
        $res = pg_query($con,$sql);
        if (pg_numrows($res) == 1) {
            $revenda      = pg_result($res,0,revenda);
            $revenda_cnpj = pg_result($res,0,cnpj);
            $revenda_nome = pg_result($res,0,nome);
        } else {
            $msg_erro["msg"][] = "Revenda não encontrada. ";
            $msg_erro["campos"][] = "revenda";
        }
    }else{
        $msg_erro["msg"][] = "Informar os campo obrigatórios ";
        $msg_erro["campos"][] = "revenda";
    }

    if(count($msg_erro)==0){
        $sql = "SELECT revenda, cidade FROM tbl_revenda WHERE cnpj = '$revenda_cnpj' ";
        $res = pg_query($con, $sql);

        if(pg_num_rows($res) > 0){
            $revenda = pg_fetch_result($res,0,'revenda');
            $cidade = pg_fetch_result($res,0,'cidade');
        }

        if(strlen(trim($revenda))>0){
            $sql_rf = "SELECT revenda_fabrica FROM tbl_revenda_fabrica WHERE fabrica = $login_fabrica AND revenda = $revenda";
            $res_rf = pg_query($con, $sql_rf);
            
            if(pg_num_rows($res_rf) > 0){
                $sql = "UPDATE tbl_revenda_fabrica SET
                        admin_bloqueio = $login_admin,
                        data_bloqueio = CURRENT_TIMESTAMP,
                        motivo_bloqueio = '$motivo'
                        WHERE fabrica = $login_fabrica
                        AND revenda = $revenda";
            }else{
                $sql = "INSERT INTO tbl_revenda_fabrica (fabrica, revenda, data_bloqueio, admin_bloqueio, motivo_bloqueio, contato_razao_social,cidade, cnpj) VALUES ($login_fabrica, $revenda, current_timestamp, {$login_admin}, '$motivo', '$revenda_nome', $cidade,'$revenda_cnpj')";
            }
            $res = pg_query($con, $sql);
            if(strlen(pg_last_error($con))==0){
                $msg_erro["ok"][] = 'Cadastro Realizado com Sucesso.';
                $revenda_nome = "";
                $revenda_cnpj = "";
                $motivo       = "";

            }
        }else{
            $msg_erro["msg"][] = "Falha ao buscar ou cadastrar revenda. ";
            $msg_erro["campos"][] = "busca_revenda";
        }
    }

    $audProd->retornaDadosSelect($sqlAudLog)->enviarLog('INSERT','tbl_revenda_fabrica',$login_fabrica."*".$revenda);
}

$layout_menu = "cadastro";
$title= "CADASTRO DE REVENDA BLOQUEADAS";
include "cabecalho_new.php";
$plugins = array(
    "datepicker",
    "mask",
    "alphanumeric",
    "dataTable",
    "shadowbox",
    "multiselect"
);
include("plugin_loader.php");

?>

<script type="text/javascript">
$(function() {
    //$.datepickerLoad(Array("data_final", "data_inicial"));
//     $("#sua_os").numeric({ allow: "-"});
    Shadowbox.init();

    $("span[rel=lupa]").click(function () {
        $.lupa($(this));
    });
});

function retorna_revenda(retorno) {
    $("#revenda_nome").val(retorno.razao);
    $("#revenda_cnpj").val(retorno.cnpj);
}

function desbloquear(revenda, posicao){    
    var revenda = revenda;   
    $.ajax({
        type: "POST",
        url: "./cadastro_revenda_bloqueadas.php",
        data: {"desbloquear": true, "revenda_fabrica":revenda},
        cache: false,
        success: function(data){
            data = JSON.parse(data);            
            if(data.retorno == 'ok'){
              alert("Revenda desbloqueada com sucesso. ");
              $('.desbloquear_'+posicao).hide();
              $('.bloquear_'+posicao).show();
            }else{
              alert("Falha ao desbloquear revenda.");
            }
        }
    });
}


</script>
<style type="text/css">
#revenda_bloqueada {
    width:100%;
}
.bloqueado{
    width: 107px;
}
</style>
<?php
if (count($msg_erro["msg"]) > 0) {
?>
<div class="alert alert-error">
    <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
</div>
<?php
}
?>
<?php
if (count($msg_erro["ok"]) > 0) {
?>
<div class="alert alert-success">
    <h4><?=implode("<br />", $msg_erro["ok"])?></h4>
</div>
<?php
}
?>
<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>

<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
    <div class ='titulo_tabela'>Parametros de Pesquisa </div>
    <br />
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class="span4">
            <div class='control-group <?=(in_array('revenda', $msg_erro['campos'])) ? "error" : "" ?>' >
                <label class="control-label" for="revenda_cnpj">CNPJ</label>
                <div class="controls controls-row">
                    <div class="span10 input-append">
                        <input id="revenda_cnpj" name="revenda_cnpj" class="span12" type="text" value="<?=$revenda_cnpj?>" />
                        <span class="add-on" rel="lupa" >
                            <i class="icon-search"></i>
                        </span>
                        <input type="hidden" name="lupa_config" tipo="revenda" parametro="cnpj" />
                    </div>
                </div>
            </div>
        </div>
        <div class="span4">
            <div class='control-group <?=(in_array('revenda', $msg_erro['campos'])) ? "error" : "" ?>' >
                <label class="control-label" for="revenda_nome">Nome Revenda</label>
                <div class="controls controls-row">
                    <div class="span12 input-append">

                        <input id="revenda_nome" name="revenda_nome" class="span12" type="text" maxlength="50" value="<?=$revenda_nome?>" />
                        <span class="add-on" rel="lupa" >
                            <i class="icon-search"></i>
                        </span>
                        <input type="hidden" name="lupa_config" tipo="revenda" parametro="razao_social" />
                    </div>
                </div>
            </div>
        </div>

        <div class='span2'></div>
    </div>

    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span8'>
            <div class='control-group <?=(in_array('motivo', $msg_erro['campos'])) ? "error" : "" ?>'>
                <label class='control-label' for='motivo'>Motivo</label>
                <div class='controls controls-row'>
                    <div class='span12 input-append'>
                        <textarea id="motivo" name="motivo" class='span12'><?=$motivo_bloqueio?></textarea>                        
                    </div>
                </div>
            </div>
        </div>
        
        <div class='span2'></div>
    </div>
    <p><br />
        <button class='btn' id="btn_acao" >Gravar</button>
        <?php if(strlen($revenda_fabrica)>0){ ?>
            <a rel='shadowbox' href='relatorio_log_alteracao_new.php?parametro=tbl_revenda_fabrica&id=<?php echo $revenda; ?>' name="btnAuditorLog">Visualizar Log Auditor</a></td>
        <?php } ?>

        <input type='hidden' id="revenda_fabrica" name='revenda_fabrica' value='<?=$revenda_fabrica?>' />
        <input type='hidden' id="btn_click" name='btn_acao' value='submit' />
    </p><br />
</form>
<?php
//if (filter_input(INPUT_POST,"btn_acao") == "submit") {
    //if (count($msg_erro["msg"]) == 0) {        

        $sql = "SELECT tbl_revenda.revenda, tbl_revenda.nome, tbl_revenda.cnpj, tbl_revenda_fabrica.motivo_bloqueio, tbl_revenda_fabrica.revenda_fabrica, tbl_revenda_fabrica.data_bloqueio 
                FROM tbl_revenda_fabrica 
                INNER JOIN tbl_revenda on tbl_revenda.revenda = tbl_revenda_fabrica.revenda
                WHERE fabrica = $login_fabrica";
        $res = pg_query($con,$sql);

        if (pg_num_rows($res) > 0) {
?>
</div>
<table id="revenda_bloqueada" class = 'table table-striped table-bordered table-hover table-large'>
    <thead>
        <tr class = 'titulo_coluna'>
            <th>CNPJ Revenda</th>
            <th>Nome Revenda</th>
            <th>Motivo</th>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody>
<?php
            $z = 0;
            while ($resultado = pg_fetch_object($res)) {

                $revenda_cnpj     = $resultado->cnpj;
                $revenda_nome     = $resultado->nome;
                $motivo           = $resultado->motivo_bloqueio;
                $revenda_fabrica  = $resultado->revenda_fabrica;
                $revenda          = $resultado->revenda;
                $data_bloqueio    = $resultado->data_bloqueio;
?>
        <tr>
            <td class='tac' ><?=$revenda_cnpj?></td>
            <td class='tac' ><?=$revenda_nome?></td>
            <td class='tac' ><?=$motivo?></td>
            <td style="text-align:center">
                <?php if(strlen($data_bloqueio)>0){ 
                    $display_blo = " style='display:none' ";
                    $display_des = " style='display:block' ";
                }else{
                    $display_blo = " style='display:block' ";
                    $display_des = " style='display:none' ";
                } ?>
                <center><a class="btn btn-danger bloqueado bloquear_<?=$z?>" href="cadastro_revenda_bloqueadas.php?revenda_fabrica=<?=$revenda_fabrica?>" <?=$display_blo?> >Bloquear</a></center>
                <center><button class="btn btn-success desbloquear_<?=$z?>" onclick="desbloquear(<?=$revenda?>, <?=$z?>)" <?=$display_des?>>Desbloquear</button></center>
            </td>
        </tr>
<?php
                $z++;
            }
?>
    </tbody>
</table>

<script type="text/javascript">
    $.dataTableLoad({
        table: "#revenda_bloqueada",
        type:"basic"
    });
</script>
<?php
        } else {
?>
<div class="container">
    <div class="alert">
        <h4>Nenhum resultado encontrado</h4>
    </div>
</div>
<?
        }
  //  }
//}
?>
<? include "rodape.php" ?>
