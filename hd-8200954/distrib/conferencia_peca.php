<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
$login_fabrica_distrib = 10;
$numero_nf = "";
$status_nf = "";

$condicao = "";

$faturamento         = $_GET['faturamento'];
$nota_fiscal         = $_GET['nf'];
$serie               = $_GET['serie'];
$garantia_antecipada = $_GET['garantia_antecipada'];
$nf_devolucao = $_GET['nf_origem'];
$justificativa = $_GET['obs_conf'];


if (!empty($_GET['btn_conferido'])) {
    $conferido = $_GET['btn_conferido'];
    $imp_readonly = "readonly";
}

if(filter_input(INPUT_POST, "btn_acao")){
    $btn = $_POST['btn_acao'];
}
if(filter_input(INPUT_POST, "btn_conferir")){
    $btn = $_POST['btn_conferir'];
}

if ($btn == "gravarConferencia") {
    $aj_faturamento_item  = $_POST['faturamento_item'];
    $aj_qtde_peca = $_POST['qtde_peca'];
    $aj_qtde_conferida = $_POST['qtde_conferida'];
    $aj_faturamento = $_POST['faturamento'];

    if ($aj_qtde_conferida > $aj_qtde_peca) {        
        $retorno = array("resultado" => false, "mensagem" => utf8_encode("Erro ao gravar conferência da peça.<br />Quantidade de Peça maior que o Faturado."));
        echo json_encode($retorno); exit;
    }
    $aj_qtde_conferida = $aj_qtde_peca - $aj_qtde_conferida;
    pg_query($con,"BEGIN TRANSACTION");

    $sql = "UPDATE tbl_faturamento_item 
                SET 
                    qtde_quebrada = {$aj_qtde_conferida} 
                WHERE tbl_faturamento_item.faturamento_item = {$aj_faturamento_item}";
    pg_query($con,$sql);

    if(pg_last_error($con)){
        pg_query($con,"ROLLBACK");
        $retorno = array("resultado" => false, "mensagem" => utf8_encode("Erro ao gravar conferência da peça."));
        
    }else{
        pg_query($con,"COMMIT");
        $sql = "SELECT sum(qtde)-sum(qtde_quebrada) as tot
                    FROM tbl_faturamento_item 
                    WHERE faturamento = $aj_faturamento;";
        $res = pg_query($con,$sql);
        $tot_conferido = pg_fetch_result($res, 0, 0);
        $retorno["resultado"] = true;
        $retorno["conferido"] = $tot_conferido;
    }
    echo json_encode($retorno); exit;
}

if($btn == "gravarRecebimento"){
    
    $conferencia  = $_POST['conferencia']['peca'];

    if(count($conferencia) > 0){        
        $count = count($conferencia);
        $tot_conferido = 0;

        pg_query($con,"BEGIN TRANSACTION");

        for($k=0; $k<$count; $k++){
            $qtde_peca = 0;

            $qtde_peca_recebida  = $conferencia[$k]['qtde_recebida']; 
            $qtde_faturada       = $conferencia[$k]['qtde_faturada'];   
            $faturamento_item    = $conferencia[$k]['faturamento_item'];
            $justificativa       = $conferencia[$k]['justificativa'];
            $codigo_peca         = $conferencia[$k]['codigo_peca'];
            $garantia_antecipada = $conferencia[$k]['garantia_antecipada'];
            $nf_devolucao        = $conferencia[$k]['nf_devolucao'];
            $condicao            = "";

            if ($qtde_faturada < $qtde_peca_recebida) {                
                $retorno = array("resultado" => false, "mensagem" => utf8_encode("Erro ao gravar conferência da peça.<br />Quantidade de Peça maior que o Faturado."));
                break;
            }
            
            $tot_conferido += $qtde_peca_recebida;
            $qtde_peca = $qtde_faturada - $qtde_peca_recebida;


            if(strlen($justificativa) > 0){
                $condicao = ", obs_conferencia = '$justificativa' ";
            }

            $sql = "UPDATE tbl_faturamento_item SET qtde_quebrada = {$qtde_peca} , nota_fiscal_origem = {$nf_devolucao} $condicao
                WHERE tbl_faturamento_item.faturamento_item = {$faturamento_item}";
            pg_query($con,$sql);

            if(pg_last_error($con)){                
                $retorno = array("resultado" => false, "mensagem" => utf8_encode("Erro ao gravar conferência da peça."));
                break;
            }
        }

        if (count($retorno) > 0) {
            pg_query($con,"ROLLBACK");
        }else{
            pg_query($con,"COMMIT");
            $retorno["resultado"] = true;
            $retorno["conferido"] = $tot_conferido;
        }
    }else{
        $retorno = array("resultado" => false, "mensagem" => utf8_encode("ERRO: A lista da conferência está faltando informação."));
    }
    echo json_encode($retorno); exit;
}

$sql = "SELECT  tbl_faturamento_item.os                 ,
                tbl_faturamento_item.faturamento_item   ,
                tbl_peca.peca                           ,
                tbl_peca.referencia                     ,
                tbl_peca.descricao                      ,
                tbl_faturamento_item.qtde               ,
                tbl_faturamento_item.nota_fiscal_origem ,
                tbl_faturamento_item.qtde_quebrada      
        FROM    tbl_faturamento_item
  INNER JOIN    tbl_peca    ON  tbl_peca.peca       = tbl_faturamento_item.peca
        WHERE   tbl_faturamento_item.faturamento = {$faturamento}";
$res = pg_query($con,$sql);

include 'funcoes.php';
?>

<style type="text/css">
form {
    width: 900px;
}
.div_conferencia {
    overflow-y: scroll;
    height: 470px;
}

.table td{
    text-align: center;
}

.table {
    width: 850px;
    margin: 0 auto;
}

input.numeric {
    width: 50px;
}

td.qtde_peca{
    width: 100px;
}

#btn_gravar {
    margin-top: 20px;
}

.error {
    border-color: #b94a48 !important;
    box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.075) !important;
    color: #b94a48 !important;
}
</style>
<link href="../bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" />
<link href="../bootstrap/css/extra.css" type="text/css" rel="stylesheet" />
<link href="../css/tc_css.css" type="text/css" rel="stylesheet" />
<link href="../plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css" type="text/css" rel="stylesheet" >
<link href="../bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" />

<script src="../plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script src="../plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
<script src="../bootstrap/js/bootstrap.js"></script>
<script src='../plugins/jquery.alphanumeric.js'></script>
<script type="text/javascript">
    $(function(){
        $("input.numeric").numeric();

        $("#btn_gravar").on("click",function(){
            $("#btn_gravar").button("loading");
            $("#mensagem_erro").html("");

            var conferencia = {peca:[]};
            var aux = true;

            $("#table_nota_peca > tbody > tr ").each(function(){
                var linha = $(this).attr('id');

                var faturamento_item    = $("#faturamento_item_"+linha).val();
                var qtde_recebida       = $("#qtde_conferida_"+linha).val();
                var qtde_faturada       = $("#qtde_peca_"+linha).val();
                var codigo_peca         = $("#codigo_peca_"+linha).val();
                var nf_shadow           = $("#nf_shadow").val();
                var garantia_antecipada = $("#garantia_antecipada_shadow").val();
                var justificativa       = "";
                var nf_devolucao = $("#nf_devolucao").val();

                if (nf_devolucao == "") {
                    $("#nf_devolucao").addClass("error");
                    $("#mensagem_erro").html('<br/><div class="alert alert-error"><h4>Preencha Nota Fiscal de Devolução</h4> </div>');
                    aux = false;
                } else if(qtde_recebida == ""){
                    $("#qtde_conferida_"+linha).addClass("error");
                    $("#mensagem_erro").html('<br/><div class="alert alert-error"><h4>Preenche todos os campos da quantidade recebida</h4> </div>');
                    aux = false;
                }else{
                    qtde_recebida = parseInt(qtde_recebida);

                    qtde_faturada = parseInt(qtde_faturada);

                    if(qtde_recebida < qtde_faturada){
                        if($("#justificativa").val() == ""){

                            $("#justificativa").addClass("error");
                            $("#mensagem_erro").html('<br/><div class="alert alert-error"><h4>Quantidade recebida menor que a faturada. Preencha o campo Justificativa.</h4> </div>');
                            aux = false;
                            return false;

                        }else{
                            justificativa = $("#justificativa").val();
                        }
                    }
                    if ($("#justificativa").val() != "") {
                        justificativa = $("#justificativa").val();
                    }

                    conferencia.peca.push({
                        faturamento_item    : faturamento_item,
                        qtde_recebida       : qtde_recebida,
                        qtde_faturada       : qtde_faturada,
                        codigo_peca         : codigo_peca,
                        nf_shadow           : nf_shadow,
                        garantia_antecipada : garantia_antecipada,
                        justificativa       : justificativa,
                        nf_devolucao        : nf_devolucao
                    });
                    
                }

            });

            // var linha = this.id.replace(/\D/g, "");
           
            if(aux){
                if(confirm("Deseja realmente realizar a conferência?")){
                    $.ajax({
                        url:"conferencia_peca.php",
                        type:"POST",
                        dataType:"json",
                        data:{
                            conferencia : conferencia,
                            btn_acao  : "gravarRecebimento"
                        }
                    })
                    .done(function(data){
                        $("#btn_gravar").button("reset");

                        if(data.resultado){
                            window.parent.conferencia_realizada($("#numero_faturamento").val());
                            window.parent.conferencia_tot_realizada($("#numero_faturamento").val(),data.conferido,$("#nf_devolucao").val(),$("#justificativa").val());  
                            window.parent.Shadowbox.close();
                        }else{
                            $("#mensagem_erro").html('<br/><div class="alert alert-error"><h4>'+data.mensagem+'</h4> </div>');
                        }
                    });
                }else{
                    $("#btn_gravar").button("reset");
                }
            }else{
                $("#btn_gravar").button("reset");
            }
        });

        //$("#btn_conferir").on("click",function(){
        $(document).on("click","button[id^=btn_conferir_]",function(){

            var linha         = this.id.replace(/\D/g, "");
            var faturamento_item = $("#faturamento_item_"+linha).val();
            var qtde_peca = $("#qtde_peca_"+linha).val();
            var qtde_conferida = $("#qtde_conferida_"+linha).val();
            var faturamento = $("#numero_faturamento").val();

            $.ajax({
                url:"conferencia_peca.php",
                type:"POST",
                data:{
                    faturamento_item : faturamento_item,
                    qtde_peca : qtde_peca,
                    qtde_conferida : qtde_conferida,
                    faturamento : faturamento,
                    btn_conferir  : "gravarConferencia"
                },complete: function(data) {
                    data = $.parseJSON(data.responseText);
                    
                    if (!data.resultado) {
                        $("#mensagem_erro").html('<br/><div class="alert alert-error"><h4>'+data.mensagem+'</h4> </div>');
                    } else {

                        $("#mensagem_erro").html('<br/><div class="alert alert-success"><h4>Conferido com sucesso!</h4> </div>');
                            setTimeout(function() { 
                                $("#mensagem_erro").hide(); 
                            }, 10000);
                        if (qtde_peca == qtde_conferida) {
                            $("#btn_conferir_"+linha).hide(); 
                            $("#qtde_conferida_"+linha).attr("readonly", true); 
                        }
                        window.parent.conferencia_tot_realizada($("#numero_faturamento").val(),data.conferido,$("#nf_devolucao").val(),$("#justificativa").val());                        
                    }
                }
            });
        });
    });
</script>

<? if (count($msg_erro['msg']) > 0) { ?>
    <br/>
    <div class="alert alert-error"><h4><?=implode("<br />", $msg_erro['msg'])?></h4></div>
    <br/>
<? } ?>
<center>
<form id="fm_conferencia_peca" method="POST" class="form-search form-inline" >
    <div id="mensagem_erro"></div>
    <div class="div_conferencia" style="margin: 5px; padding-right: 20px;">
        <div id="mensagem_conferencia">
            <div class='container tc_container'>
                <br/>
                <div class="row-fluid">
                    <div class="span2"></div>
                    <div class="span2">
                        <div class="control-group" >
                            <label class="control-label" for="nf_shadow" >Nº Nota Fiscal</label>

                            <div class="controls controls-row" >
                                <input type="text" id="nf_shadow" readOnly class="span12" value="<?=$nota_fiscal?>"/>
                                <input type="hidden" id="garantia_antecipada_shadow" value="<?=$garantia_antecipada?>">
                                <input type="hidden" id="numero_faturamento" value="<?=$faturamento?>">
                            </div>
                        </div>
                    </div>

                    <div class="span2">
                        <div class="control-group" >
                            <label class="control-label" for="serie_shadow" >Série</label>
                            <div class="controls controls-row" >
                                <input type="text" name="serie_shadow" id="serie_shadow" readOnly class="span12" value="<?=$serie?>" />
                            </div>
                        </div>
                    </div>

                    <div class="span2">
                        <div class="control-group">
                            <label class="control-label" for="nf_devolucao" >Nº NF - Devolução</label>
                            <div class="controls controls-row" >
                                <input type="text" id="nf_devolucao" class="span12" value="<?=$nf_devolucao?>" <?=$imp_readonly?>/>
                            </div>
                        </div>
                    </div>
                </div>
                <br />
                <div class="row-fluid">
                    <div class="span2"></div>
                    <div class="span6">
                        <div class="control-group" >
                            <label class="control-label" for="justificativa" >Justificativa</label>

                            <div class="controls controls-row" >
                                <input type="text" name="justificativa" id="justificativa" class="span12" value="<?=$justificativa?>" <?=$imp_readonly?> />
                            </div>
                        </div>
                    </div>
                    <div class="span2" >
                        <div class="control-group" align="center">
                            <div class="controls controls-row botao_gravar" >
                            <?php
                            if (empty($imp_readonly)) {?>
                                <button type="button" class="btn" data-loading-text="Gravando..." id="btn_gravar" >Gravar</button>
                            <?php
                            }
                            ?>                                
                            </div>
                        </div>
                    </div>
                    <div class="span2"></div>
                </div>
            </div>
        </div>
            <br/>
            <table id="table_nota_peca" class='table table-striped table-bordered table-large' >
                <thead>
                    <tr class='titulo_coluna'>
                        <?php
                        if($login_fabrica_distrib == 30){?>
                                <th>OS</th>
                        <?php
                        }?>
                        <th>Referência</th>
                        <th>Descrição</th>
                        <th>Quantidade Faturada</th>
                        <th>Quantidade Recebida</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                if(pg_num_rows($res) > 0){
                    $count = pg_num_rows($res);

                    for($i = 0; $i < $count; $i++){
                        $os                 = pg_fetch_result($res, $i, "os");
                        $faturamento_item   = pg_fetch_result($res, $i, "faturamento_item");
                        $peca               = pg_fetch_result($res, $i, "peca");
                        $referencia         = pg_fetch_result($res, $i, "referencia");
                        $descricao_peca     = pg_fetch_result($res, $i, "descricao");
                        $qtde_peca          = pg_fetch_result($res, $i, "qtde");
                        if (!empty($imp_readonly)) {
                            $qtde_quebrada      = pg_fetch_result($res, $i, "qtde_quebrada");
                            $qtde_quebrada = $qtde_peca - $qtde_quebrada;
                        }
                        
                        ?>
                        <tr id="<?=$i?>">
                            <?php
                            if($login_fabrica_distrib == 30){?>
                                <td><?=$os?></td>
                            <?php
                            }?>
                            <td><?=$referencia?></td>
                            <td><?=$descricao_peca?></td>
                            <td class="qtde_peca tac"><?=$qtde_peca?></td>
                            <td class="tac">
                            <?php
                                if ($qtde_quebrada < $qtde_peca) {
                                    $readonly_imp = "";
                                }else{
                                    $readonly_imp = "readonly";
                                }
                                ?>
                                <input type="text" class="numeric" id="qtde_conferida_<?=$i?>" <?=$readonly_imp?> value="<?=$qtde_quebrada?>"/>
                            </td>
                            <td class="tac" id="acao">
                                <?php
                                if( $qtde_quebrada < $qtde_peca AND !empty($imp_readonly) ) {?>
                                    <button type="button" class="btn" data-loading-text="Gravando..." id="btn_conferir_<?=$i?>" >Conferir</button>
                                <?php
                                }
                                ?>
                                <input type="hidden" id="qtde_peca_<?=$i?>" value="<?=$qtde_peca?>"/>
                                <input type="hidden" id="codigo_peca_<?=$i?>" value="<?=$peca?>"/>
                                <input type="hidden" id="faturamento_item_<?=$i?>" value="<?=$faturamento_item?>"/>
                            </td>
                        </tr>
                    <?php 
                    }
                }
                ?>
                </tbody>
            </table>
        </div>
    </div>
</form>
</center>
