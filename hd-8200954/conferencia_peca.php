<?php
include __DIR__.'/dbconfig.php';
include __DIR__.'/includes/dbconnect-inc.php';
include __DIR__.'/autentica_usuario.php';
include __DIR__.'/funcoes.php';
$numero_nf = "";
$status_nf = "";

$condicao = "";

$faturamento         = $_GET['faturamento'];
$nota_fiscal         = $_GET['nf'];
$serie               = $_GET['serie'];
$garantia_antecipada = $_GET['garantia_antecipada'];

if(filter_input(INPUT_POST, "btn_acao")){
    $btn = $_POST['btn_acao'];
}

if($btn == "gravarRecebimento"){
    $conferencia      = $_POST['conferencia']['peca'];
    $faturamento      = $_POST['faturamento'];
    
    if(count($conferencia) > 0){
        $count = count($conferencia);

        pg_query($con,"BEGIN TRANSACTION");

        for($k=0; $k<$count; $k++){
            $condicao = "";
            $qtde_peca = 0;

            $qtde_peca_recebida  = $conferencia[$k]['qtde_recebida']; 
            $qtde_faturada       = $conferencia[$k]['qtde_faturada'];   
            $faturamento_item    = $conferencia[$k]['faturamento_item'];
            $justificativa       = $conferencia[$k]['justificativa'];
            $codigo_peca         = $conferencia[$k]['codigo_peca'];
            $garantia_antecipada = $conferencia[$k]['garantia_antecipada'];
            $condicao            = "";

            if(strlen($justificativa) > 0){
                $condicao = ", obs_conferencia = '$justificativa' ";
            }
	
	    if(in_array($login_fabrica, [30, 164])){
		$condicao .= ", qtde_inspecionada = {$qtde_peca_recebida}";
	    }

            $qtde_peca = $qtde_faturada - $qtde_peca_recebida;

            $sql = "UPDATE tbl_faturamento_item SET qtde_quebrada = {$qtde_peca} $condicao
                WHERE tbl_faturamento_item.faturamento_item = {$faturamento_item}";
            pg_query($con,$sql);

            if(pg_last_error($con)){
                pg_query($con,"ROLLBACK");
                $retorno = array("resultado" => false, "mensagem" => utf8_encode("Erro ao gravar conferência da peça."));
                break;
            }

            if(in_array($login_fabrica,array(30, 164))){
                $sqlOs = "
                    SELECT  tbl_faturamento_item.os
                    FROM    tbl_faturamento_item
                    WHERE   tbl_faturamento_item.faturamento_item = $faturamento_item
                ";
                $resOs = pg_query($con,$sqlOs);
                if(pg_num_rows($resOs) > 0){
                    $os_checkpoint = pg_fetch_result($resOs,0,os);

    		    $sql = "UPDATE tbl_os SET status_checkpoint = fn_os_status_checkpoint_os($os_checkpoint) WHERE os = $os_checkpoint";
                        $res = pg_query($con,$sql);
                    }   

                $sqlOS = "SELECT os_produto FROM tbl_os_produto WHERE os = {$os_checkpoint}";
                $resOS = pg_query($con, $sqlOS);

                $osProduto = pg_fetch_result($resOS, 0, os_produto);

                $sqlOsItem = "SELECT parametros_adicionais,os_item FROM  tbl_os_item WHERE os_produto = {$osProduto} AND fabrica_i = $login_fabrica and peca = $codigo_peca";
                $resOsItem = pg_query($con, $sqlOsItem);

                if (pg_num_rows($resOsItem) > 0) {
                    $parametros_adicionais = json_decode(pg_fetch_result($resOsItem, 0, parametros_adicionais), true);
					$os_item = pg_fetch_result($resOsItem, 0 , 'os_item'); 

                    $data_conferencia = date('d/m/Y'); 

                    $parametros_adicionais['data_recebimento'] = $data_conferencia;

                    $adicionais = json_encode($parametros_adicionais);

					$sqlUpdate = "SELECT fn_atualiza_pa_os_item($os_item, '$adicionais', $login_fabrica) ";
                    $resUpdate = pg_query($con, $sqlUpdate);
                }

            }

            if($qtde_peca_recebida > 0 && $garantia_antecipada == 't' && !in_array($login_fabrica,array(30,160,164)) && !$replica_einhell){
                $nf_shadow    = $conferencia[$k]['nf_shadow'];
                $data_entrada = date("Y-m-d");

                $sql = "SELECT controla_estoque FROM tbl_posto_fabrica 
                    WHERE fabrica = {$login_fabrica} AND posto = {$login_posto} AND controla_estoque IS TRUE";
                $res = pg_query($con,$sql);

                if(pg_num_rows($res) > 0){
                    $sql = "INSERT INTO tbl_estoque_posto_movimento
                        (fabrica, posto, peca, qtde_entrada, nf, data, obs) VALUES
                    ({$login_fabrica}, {$login_posto}, {$codigo_peca}, {$qtde_peca_recebida}, '{$nf_shadow}', '{$data_entrada}', 'Entrada de peça pela conferência da nota fiscal {$nf_shadow}.')";

                    $res = pg_query($con, $sql);

                    if (pg_last_error($con)) {
                        pg_query($con,"ROLLBACK");
                        $retorno = array("resultado" => false, "mensagem" => utf8_encode("Erro ao lançar movimentação"));
                        break;
                    } else {
                        $sql = "SELECT qtde FROM tbl_estoque_posto WHERE fabrica = {$login_fabrica}
                                AND posto = {$login_posto} AND peca = {$codigo_peca}";
                        $res = pg_query($con,$sql);

                        $estoque = pg_fetch_result($res, 0, "qtde");
                        $estoque = $estoque + $qtde_peca_recebida;

                        $sql = "UPDATE tbl_estoque_posto SET qtde = {$estoque}
                            WHERE fabrica = {$login_fabrica}
                                AND posto = {$login_posto} AND peca = {$codigo_peca}";
                        $res = pg_query($con, $sql);

                        if (pg_last_error($con)){
                            pg_query($con,"ROLLBACK");
                            $retorno = array("resultado" => false, "mensagem" => utf8_encode("Erro ao lançar no estoque."));
                            break;
                        }
                    }
                }
            }

        }

        if (count($retorno) > 0) {
            pg_query($con,"ROLLBACK");
        }else{
            pg_query($con,"COMMIT");
            $retorno["resultado"] = true;
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
                tbl_faturamento_item.qtde_quebrada      ,
                tbl_faturamento.conferencia as data_recebimento
        FROM    tbl_faturamento_item
  INNER JOIN    tbl_peca    ON  tbl_peca.peca       = tbl_faturamento_item.peca
                            AND tbl_peca.fabrica    = {$login_fabrica}
  INNER JOIN    tbl_faturamento ON tbl_faturamento.faturamento = {$faturamento}
			    WHERE   tbl_faturamento_item.faturamento = {$faturamento}";

if(in_array($login_fabrica, [30, 164])){
	$sql = "SELECT  tbl_os_produto.os                 ,
			tbl_faturamento_item.faturamento_item   ,
			tbl_peca.peca                           ,
			tbl_peca.referencia                     ,
			tbl_peca.descricao                      ,
			tbl_faturamento_item.qtde               ,
			tbl_faturamento_item.qtde_quebrada
		FROM    tbl_faturamento_item
		INNER JOIN tbl_peca ON  tbl_peca.peca = tbl_faturamento_item.peca
		AND tbl_peca.fabrica    = {$login_fabrica}
		INNER JOIN tbl_os_item ON tbl_faturamento_item.pedido = tbl_os_item.pedido AND tbl_os_item.peca = tbl_faturamento_item.peca
		INNER JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
		WHERE   tbl_faturamento_item.faturamento = {$faturamento}";
}

$res = pg_query($con,$sql);

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
<link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" />
<link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" />
<link href="css/tc_css.css" type="text/css" rel="stylesheet" />
<link href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css" type="text/css" rel="stylesheet" >
<link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" />

<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
<script src="bootstrap/js/bootstrap.js"></script>
<?php

$plugins = array(
   "alphanumeric",
   "datepicker",
   "mask"
);

include __DIR__."/admin/plugin_loader.php";

?>
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

                if(qtde_recebida == ""){
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

                    conferencia.peca.push({
                        faturamento_item    : faturamento_item,
                        qtde_recebida       : qtde_recebida,
                        qtde_faturada       : qtde_faturada,
                        codigo_peca         : codigo_peca,
                        nf_shadow           : nf_shadow,
                        garantia_antecipada : garantia_antecipada,
                        justificativa       : justificativa
                    });
                    
                }

            });

            // var linha = this.id.replace(/\D/g, "");
           
            if(aux){

                if(confirm("Deseja realmente realizar a conferência? Uma vez conferida, não será possível alterar!")){
                    $.ajax({
                        url:"conferencia_peca.php",
                        type:"POST",
                        dataType:"json",
                        data:{
                            conferencia: conferencia,
                            faturamento: $("#numero_faturamento").val(), 
                            btn_acao  : "gravarRecebimento"
                        }
                    })
                    .done(function(data){
                        $("#btn_gravar").button("reset");

                        if(data.resultado){
                            window.parent.conferencia_realizada($("#numero_faturamento").val());
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
    });
</script>
<? if (count($msg_erro['msg']) > 0) { ?>
    <br/>
    <div class="alert alert-error"><h4><?=implode("<br />", $msg_erro['msg'])?></h4></div>
    <br/>
<? } ?>
<form id="fm_conferencia_peca" method="POST" class="form-search form-inline" >
    <div id="mensagem_erro"></div>
    <div class="div_conferencia" style="margin: 5px; padding-right: 20px;">
        <div id="mensagem_conferencia">
            <div class='container tc_container'>
                <div class="row-fluid">
                    <br/>
                    <div class="span2" style="margin-left:70px" >
                        <div class="control-group" >
                            <label class="control-label" for="nf_shadow" >Nº Nota Fiscal</label>

                            <div class="controls controls-row" >
                                <input type="text" id="nf_shadow" readOnly class="span10" value="<?=$nota_fiscal?>"/>
                                <input type="hidden" id="garantia_antecipada_shadow" value="<?=$garantia_antecipada?>">
                                <input type="hidden" id="numero_faturamento" value="<?=$faturamento?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="span2" >
                        <div class="control-group" >
                            <label class="control-label" for="serie_shadow" >Série</label>

                            <div class="controls controls-row" >
                                <input type="text" name="serie_shadow" id="serie_shadow" readOnly class="span8" value="<?=$serie?>" />
                            </div>
                        </div>
                    </div>

                    <div class="span3" >
                        <div class="control-group" >
                            <label class="control-label" for="justificativa" >Justificativa</label>

                            <div class="controls controls-row" >
                                <input type="text" name="justificativa" id="justificativa" class="span12" value="" />
                            </div>
                        </div>
                    </div>
                    <?php 
                    if ($login_fabrica == 160 or $replica_einhell) {

                        $data_conferencia = pg_fetch_result($res, 0, 'data_recebimento');
                    ?>
                        <div class="span2" >
                            <div class="control-group" >
                                <label class="control-label" for="data_recebimento" >Data Recebimento</label>

                                <div class="controls controls-row" >
                                    <input type="text" name="data_recebimento" id="data_recebimento" class="span12" value="<?= mostra_data($data_conferencia) ?>" readonly />
                                </div>
                            </div>
                        </div>
                    <?php 
                    }
                    ?>
                    <div class="span2" >
                        <div class="control-group" >
                            <div class="controls controls-row botao_gravar" >
                                <button type="button" class="btn" data-loading-text="Gravando..." id="btn_gravar">Gravar</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <br/>
            <table id="table_nota_peca" class='table table-striped table-bordered table-large' >
                <thead>
                    <tr class='titulo_coluna'>
                    <?php
                        if(in_array($login_fabrica, [30, 164])){
                    ?>
                                <th>OS</th>
                    <?php
                        }
                    ?>
                        <th>Referência</th>
                        <th>Descrição</th>
                        <th>Quantidade Faturada</th>
                        <th>Quantidade Recebida</th>
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
                        ?>
                        <tr id="<?=$i?>">
<?php
                if(in_array($login_fabrica, [30, 164])){
?>
                            <td><?=$os?></td>
<?php
                }
?>
                            <td><?=$referencia?></td>
                            <td><?=$descricao_peca?></td>
                            <td class="qtde_peca tac"><?=$qtde_peca?></td>
                            <td class="tac">
                                <input type="text" class="numeric" id="qtde_conferida_<?=$i?>" />
                                <input type="hidden" id="qtde_peca_<?=$i?>" value="<?=$qtde_peca?>"/>
                                <input type="hidden" id="codigo_peca_<?=$i?>" value="<?=$peca?>"/>
                                <input type="hidden" id="faturamento_item_<?=$i?>" value="<?=$faturamento_item?>"/>
                                <?php if ($login_fabrica == 164) { ?>
                                    <input type="hidden" id="os<?=$i?>" value="<?=$os?>"/>
                                <?php } ?>
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
