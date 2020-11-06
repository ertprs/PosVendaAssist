<?php

include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
include_once __DIR__.'/autentica_admin.php';
include 'funcoes.php';
include_once __DIR__ . '/../class/AuditorLog.php';

$os                         = (!empty($_GET['os'])) ? $_GET['os'] : "null";
$status_checkpoint          = (!empty($_GET['status_checkpoint'])) ? replace_status_checkpoint($_GET['status_checkpoint']) : "null";
$status_aguardando_conserto = 'Aguardando Conserto';

if ($_POST['grava']) {
    global $con, $login_fabrica, $login_admin;
    $msg = "";
	
	if ($_POST['os'] != "") {
        $os                = $_POST['os'];
        $status_checkpoint = replace_status_checkpoint($_POST['status_checkpoint']);
        $justificativa     = '';

        $auditorLog = new AuditorLog();
        $auditorLog->retornaDadosSelect("SELECT '$justificativa' AS justificativa, '$status_checkpoint' AS status_checkpoint FROM tbl_os WHERE tbl_os.os = {$os} AND tbl_os.fabrica = {$login_fabrica}");
        atualiza_status_checkpoint($os, $status_aguardando_conserto);
        
        $justificativa = $_POST['textarea'];

        pg_query($con, "BEGIN");
        $sql = "INSERT INTO tbl_os_interacao (os, admin, comentario, interno, fabrica, posto) 
            VALUES 
            ($os, $login_admin, '{$justificativa}', true, {$login_fabrica},(SELECT posto FROM tbl_os WHERE os = {$os} AND fabrica = {$login_fabrica}))";
        pg_query($con, $sql);

        if (strlen(pg_last_error()) > 0) {
            pg_query($con, "ROLLBACK");
            throw new Exception("Erro ao salvar a justificativa na interação da OS {$os}");
        }else {
            pg_query($con, "COMMIT");
        }

        $auditorLog->retornaDadosSelect("SELECT '$justificativa' AS justificativa, '$status_aguardando_conserto' AS status_checkpoint FROM tbl_os WHERE tbl_os.os = {$os} AND tbl_os.fabrica = {$login_fabrica}");
        $auditorLog->enviarLog('update', "tbl_os", $login_fabrica."*".$os);

        $msg = 'success';
    } else {
        $msg = 'error';
    }

    echo $msg;
    exit();
}

function replace_status_checkpoint($status){
    return str_replace(array("Á","É","Í","Ó","Ú","Ç"), array("á","é","í","ó","ú","ç"), $status);
}

$plugins = array(
    "jquery3",
    "bootstrap3"
);

include("plugin_loader.php");
?>

<style type="text/css" media="screen">
    

    .titulo {
        background-color: #5a6d9c;
        color: #ffffff;
    }    

    .txt {
        width: 350px;
        height: 150px;
    }

</style>

<form name="frm_form" action="altera_status_checkpoint_manual.php" method="POST">
    <div class="conteudo">
        <div class="row">
            <div class="col-sm-4 titulo">
                <center>
                    <label>ALTERAR STATUS OS MANUAL</label>
                </center>
            </div>
        </div>
        <br />
	<div class= "row">
		<div class="col-sm-4">
            <div class="message alert" style="display:none; text-align: center;">
			     <h4><b class="message_text"></b></h4>
            </div>
		</div>
	</div>
        <div class="row sim">
            <div class="col-sm-1"></div>
            <div class="col-sm-4">
                <center>
                    <label class="oculta" >Alterar status da os para Aguardando Conserto?</label>
                </center>
            </div>
            <div class="col-sm-2">
                <center>
                    <input class="oculta" type="checkbox" checked name="check_sim" id="check_sim" disabled="true">
                    <label class="oculta" for="check_sim">Sim</label>
                </center>
            </div>
        </div>
        <br />
        <div class="row justificativa">
            <div class="col-sm-1"></div>
            <div class="col-sm-3">
                <center>
                    <textarea class="txt oculta" name="txt_justificativa" id="txt_justificativa" placeholder="Justificativa"></textarea>
                    <img src="imagens/loading_img.gif" class="anexo_loading" style="width: 64px; height: 64px; display: none;" />
                </center>
            </div>
        </div>
        <br />
        <div class="row justificativa">
            <div class="col-sm-1"></div>
            <div class="col-sm-3">
                <center>
                    <button type="button" class="btn btn-success oculta" name="btn_acao" id="btn_alterar">Alterar</button>
                </center>
            </div>
        </div>
    </div>
</form>

<script type="text/javascript">
    $(function() {
        $("#btn_alterar").click(function(e) {

            var os                = <?=$os?>;
            var status_checkpoint = "<?=$status_checkpoint?>";
            var check             = "";
            clear_message();
            $('.message').hide();

            if ($("#check_sim").prop("checked")) {
                check = "sim";
            }
        	
        	if ($("#txt_justificativa").val() == undefined || $("#txt_justificativa").val() == "") {
        		messageAddClass("alert-danger erro_justificativa");
                $('.message_text').addClass("text_erro").html('Informe a Justificativa');
        		return false;
        	}

        	$(".oculta").hide();
        	$(".anexo_loading").show();

            $.ajax({
                type: "POST",
                url: "altera_status_checkpoint_manual.php",
                data: {
                    grava:true,
                    os:os,
                    status_checkpoint: status_checkpoint,
                    textarea:$("#txt_justificativa").val()
                }
            })
            .done(function(dados) {
                $('.anexo_loading').hide();

                if (dados == 'success'){
                    messageAddClass("alert-success success");
                    $('.message_text').html('Alterado com Sucesso');
                    setTimeout(function(){ 
                        window.parent.location.href="os_press.php?os="+os;     
                    }, 300);
                }else{
                    messageAddClass("alert-danger erro_justificativa");
                    $('.oculta').show();
                    $('.message_text').addClass('text_erro').html('Erro na alteração do status da os');
                    return false;
                }
            });
        });

        function clear_message(){
            $('.message').removeClass("alert-success success alert-danger erro_justificativa");
            $('.message_text').removeClass('text_erro').html('');
        }

        function messageAddClass(classes){
            $('.message').show().addClass(classes);
        }
    });
</script>