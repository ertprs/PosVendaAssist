<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';
include 'funcoes.php';
include("plugin_loader.php");

include_once "../class/tdocs_obs.class.php";
include_once '../class/communicator.class.php';

$posto          = $_GET['posto'];
$codigo_posto   = $_GET['codigo_posto'];
$title = "Cancela Prestação de Serviço";
header('Content-Type: text/html; charset=iso-8859-1');

if($_POST["descredenciamento"]){
    $posto           = $_POST["posto"];
    $codigo_posto       = $_POST['codigo_posto'];
    $motivo             = pg_escape_string($_POST['motivo']);
    $observacao_interna = pg_escape_string($_POST['observacao_interna']);

    $resS = pg_query($con,"BEGIN TRANSACTION");

    $sql_credenciamento = "INSERT INTO tbl_credenciamento (fabrica, status, confirmacao_admin, posto, texto) values ($login_fabrica, 'descredenciamento em aprovação', $login_admin, $posto, '$motivo') returning credenciamento";
    $res_credenciamento = pg_query($con, $sql_credenciamento);

    if(strlen(trim(pg_last_error($con)))==0){
        $id_credenciamento = pg_fetch_result($res_credenciamento, 0, 'credenciamento');
    }else{
        $msg_erro = "erro";
    }

    $sql_posto_fabrica = "UPDATE tbl_posto_fabrica SET credenciamento = 'Descredenciamento' WHERE posto = $posto and fabrica = $login_fabrica";
    $res_posto_fabrica = pg_query($con, $sql_posto_fabrica);

    if(strlen(pg_last_error($con))>0){
        $msg_erro = "erro";
    }
    
    $tdocs_obs = new TDocs_obs($con, $login_fabrica, 'credenciamento');
    $retorno = $tdocs_obs->gravaObservacao($observacao_interna, 'tbl_credenciamento', $id_credenciamento);

    if($retono['retorno'] == 'erro'){
        $msg_erro = "erro";
    }

    if ($login_fabrica == 1) {
        include "gera_cancelamento_prestacao_servico.php";        
        $arquivo = "/tmp/contrato_cancelamento_servico_$posto.pdf";

        if(file_exists($arquivo)){
            $tDocs = new TDocs($con, $login_fabrica);
            $tDocs->setContext("posto", "contrato");

            $anexou = $tDocs->uploadFileS3($arquivo, $posto, false);
            if(!$anexou){
                $msg_erro = "erro";
            }
        }else{
            $msg_erro = "erro";
        }
    }

    if(strlen(pg_last_error($con))==0 and strlen(trim($msg_erro))==0){
        $resS = pg_query($con,"COMMIT TRANSACTION");

        $assunto = "Descredenciamento do posto $codigo_posto pendente para aprovação. ";
        $mensagem = "O descredenciamento do posto $codigo_posto está aguardando aprovação.";

        $sql_admin = "SELECT email from tbl_admin where fabrica = $login_fabrica and responsavel_ti is true and ativo is true ";
        $res_admin = pg_query($con, $sql_admin);
        if(pg_num_rows($res_admin)>0){
            $email = pg_fetch_result($res_admin, 0, 'email');

            $mailTc = new TcComm($externalId);
            $res = $mailTc->sendMail(
                $email,
                $assunto,
                $mensagem,
                $externalEmail
            );
        }

        echo json_encode(array('retorno' => "ok"));
    }else{
        echo json_encode(array('retorno' => "erro"));
        $resS = pg_query($con,"ROLLBACK TRANSACTION");
    } 
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="UTF-8">
		<title><?=$title?></title>
		<link type="text/css" rel="stylesheet" media="screen" href="../bootstrap/css/bootstrap.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="../bootstrap/css/extra.css" />
		<!-- <link type="text/css" rel="stylesheet" media="screen" href="../css/tc_css.css" /> -->
		<!-- <link type="text/css" rel="stylesheet" media="screen" href="../css/tooltips.css" /> -->
		<link type="text/css" rel="stylesheet" media="screen" href="../bootstrap/css/ajuste.css" />

		<script src="../plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
		<script src="../bootstrap/js/bootstrap.js"></script>

        <script type="text/javascript">
            
            $(function(){

                $("#gravar").click(function(){
                    var posto = $("#posto").val();
                    var motivo = $("#motivo").val();
                    var codigo_posto = $("#codigo_posto").val();
                    var observacao_interna = $("#observacao_interna").val();

                    if(motivo.length == 0){
                        alert('Informe o motivo');
                    }else if(observacao_interna.length == 0){
                        alert('Informe a observação interna');
                    }else{
                        $.ajax({
                            url: "<?php echo $_SERVER['PHP_SELF']; ?>",
                            data:{"descredenciamento": true, posto:posto, codigo_posto:codigo_posto, motivo:motivo, observacao_interna:observacao_interna},
                            type: 'POST',
                            beforeSend: function () {
                                $("#loading_pre_cadastro").show();
                                $("#gravar").hide();
                                $("#cancelar").hide();
                            },
                            complete: function(data) {
                                data = $.parseJSON(data.responseText);
                                if(data.retorno == 'ok'){
                                    $(".sucesso").show();
                                    $(".sucesso h4").html('Posto enviado para descredenciamento'); 
                                    $('.cancelamento_prestacao', window.parent.document).hide();                               
                                }else{
                                    $(".erro").show();
                                    $(".erro h4").html('Falha ao enviar posto para descredenciamento.');
                                }
                                $("#loading_pre_cadastro").hide();
                                $("#cancelar").show();
                            }
                        });
                    }
                });
            });

        </script>
		
	</head>
<body>
    <div style="width: 500px">
        
        <div class="titulo_tabela">Descredenciamento</div>
        <br />
        <div class='alert alert-error erro' style="display: none;">
            <h4></h4>
        </div>
        <div class='alert alert-success sucesso ' style="display: none;">
            <h4></h4>
        </div>
        <div class="row-fluid">
            <div class="span1"></div>
            <div class="span10">
                <div class="<? echo $controlgrup ?>">
                    <label class="control-label">Motivo do Cancelamento</label>
                    <div class="controls controls-row">
                        <h5 class='asteristico'>*</h5>
                        <input class='span12' type='text' id="motivo" name='motivo' value="<?=$motivo?>">
                    </div>
                </div>
            </div>
            <div class="span1"></div>
        </div>
        <div class="row-fluid">
            <div class="span1"></div>
            <div class="span10">
                <div class="<? echo $controlgrup ?>">
                    <label class="control-label">Observação Interna</label>
                    <div class="controls controls-row">
                        <h5 class='asteristico'>*</h5>
                        <input class='span12' type='text' id="observacao_interna" name='observacao_interna' value="<?=$observacao_interna?>">
                    </div>
                </div>
            </div>
            <div class="span1"></div>
        </div>
        <br>
        <div class="row-fluid tac">
           
            <button type="button" class="btn btn-success" id="gravar">Gravar</button>
            <button type="button" onclick="window.parent.Shadowbox.close()" class="btn btn-danger" id="cancelar">Fechar</button>
            <input type="hidden" name="posto" id="posto" value="<?=$posto?>">
            <input type="hidden" name="codigo_posto" id="codigo_posto" value="<?=$codigo_posto?>">
        </div>
        <div class="row-fluid tac">
             <center><img src="imagens/loading_img.gif" style="display:none; height:20px; width: 20px;" id="loading_pre_cadastro"/></center>
        </div>    
    </div>
</body>
</html>
