<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios = "auditoria";
include "autentica_admin.php";
include __DIR__.'/funcoes.php';

$numero_nf = "";
$faturamento = $_GET['faturamento'];
$linha       = $_GET['linha'];
$numero_nf   = $_GET['nf'];
$serie       = $_GET['serie'];

if(filter_input(INPUT_POST, "btn_acao")){
    $btn = $_POST['btn_acao'];
}

if ($_POST["btn_acao"] == "reprovar") {
    $faturamento   = trim($_POST['faturamento']);
    $justificativa = trim($_POST['justificativa']);

    pg_query($con, "BEGIN TRANSACTION");

        $sql = "UPDATE tbl_faturamento SET garantia_antecipada = 'false',  
            obs = '$justificativa' 
        WHERE faturamento = {$faturamento} AND fabrica = {$login_fabrica}";
    pg_query($con, $sql);

    if(strlen(pg_last_error()) > 0){
        pg_query($con, "ROLLBACK");
        $resposta = array("resultado" => false, "mensagem" => "Erro ao reprovar a auditoria.");
    }else{
        // pg_query($con, "COMMIT");
        pg_query($con, "COMMIT");
        $resposta = array("resultado" => true);
    }
    echo json_encode($resposta); exit;
}
?>
<link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" />
<link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" />
<link href="css/tc_css.css" type="text/css" rel="stylesheet" />
<link href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css" type="text/css" rel="stylesheet" >
<link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" />

<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
<script src="bootstrap/js/bootstrap.js"></script>

<style type="text/css">
div.div_justificativa {
    margin: 5px; 
    padding-right: 20px;
    overflow-y: scroll;
    height: 440px;
}

textarea {
    margin: 0px 0px 10px; 
    width: 603px; 
    height: 200px;
}
</style>

<script type="text/javascript">
$(function(){
    $(document).on("click","button.btJustificativa",function(){
        var justificativa = $("textarea.justificativa").val();

        if(justificativa != "" && justificativa != "undefined"){
            var acao = confirm("Deseja realmente reprovar a auditoria? Depois de reprovado, não é possível realizar alteração!")

            if(acao){
                var faturamento      = $("#numero_faturamento").val();
                var linha            = $("#numero_linha").val();

                $.ajax({
                    url: "<?php echo $_SERVER['PHP_SELF']; ?>",
                    type: "POST",
                    data: {
                        faturamento: faturamento,
                        justificativa: justificativa,
                        btn_acao: "reprovar"
                    }
                }).done( function(data){
                    data = JSON.parse(data);
                    if(data.resultado == false){
                        $("div.mensagem_justificativa").html('<div class="alert alert-error"><h4>'+data.mensagem+'</h4> </div>');
                    }else{
                        window.parent.retorno_grava_conferencia(faturamento);
                    }
                    $("button[id=btReprovado_"+linha+"]").button('reset');

                }).fail(function(data){
                    if(data.resultado == false){
                        $("#mensagem").html('<div class="alert alert-error"><h4>'+data.mensagem+'</h4> </div>');
                    }
                    $("button[id=btReprovado_"+linha+"]").button('reset');
                });
            }
        }else{
            $("div.mensagem_justificativa").html('<div class="alert alert-error"><h4>Preencha o campo Justificativa.</h4> </div>');
        }
    });
});
</script>
<? if (count($msg_erro['msg']) > 0) { ?>
    <br/>
    <div class="alert alert-error"><h4><?=implode("<br />", $msg_erro['msg'])?></h4></div>
    <br/>
<? } ?>
<form id="fm_conferencia_justificativa" action="<?=$PHP_SELF?>" method="POST" class="form-search form-inline" >
    <div id="mensagem_erro"></div>
    <div class="div_justificativa">
        <input type="hidden" id="numero_faturamento" value="<?=$faturamento?>"/>
        <input type="hidden" id="numero_linha" value="<?=$linha?>"/>
        <br/>
        <div class="mensagem_justificativa"></div>
        <div class="row-fluid">
            <br/>
            <div class="span1" ></div>
            <div class="span3" >
                <div class="control-group" >
                    <label class="control-label" for="nf_shadow" >Nº Nota Fiscal</label>

                    <div class="controls controls-row" >
                        <input type="text" readOnly class="span6 nf_shadow" value="<?=$numero_nf?>"/>
                    </div>
                </div>
            </div>
            
            <div class="span4" >
                <div class="control-group" >
                    <label class="control-label" for="serie_shadow" >Série</label>

                    <div class="controls controls-row" >
                        <input type="text" readOnly class="span6 serie_shadow" value="<?=$serie?>" />
                    </div>
                </div>
            </div>
        </div>
        <div class="span1"></div>
        <label>Justificativa</label>
        <div class="tac">
            <textarea class="justificativa" rows="10" cols="10"></textarea>
            <br/>
            <br/>
            <button type="button" class="btn btn-success btJustificativa">Salvar</button>
        </div>
    </div>
</form>
