<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="financeiro";
include 'autentica_admin.php';
include 'funcoes.php';

/*
    arquivo de exportação
*/
$sqlAdmin = "SELECT nome_completo from tbl_admin where admin = $login_admin and fabrica = $login_fabrica";
$resAdmin = pg_query($con, $sqlAdmin);
$nome_completo = pg_fetch_result($resAdmin, 0, nome_completo);


if(isset($_POST["excluir"])){
    $extrato_nota_avulsa = $_POST['extratoavulso'];

    $sql = "DELETE from tbl_extrato_nota_avulsa WHERE extrato_nota_avulsa = $extrato_nota_avulsa AND fabrica = $login_fabrica";
    $res = pg_query($con, $sql);
    if(strlen(pg_last_error($con))>0){
        $msg_erro = pg_last_error($con);
        echo "erro";
    }else{
        echo "sucesso";
    }
    exit;
}

if(isset($_POST['btnacao'])){

    $codigo_posto           = $_POST['codigo_posto'];
    $descricao_posto        = $_POST['descricao_posto'];
    $data_lancamento        = $_POST['data_lancamento'];
    $data_emissao           = $_POST['data_emissao'];
    $previsao_pagamento     = $_POST["previsao_pagamento"];
    $nota_fiscal            = $_POST["nota_fiscal"];
    $valor_original         = $_POST["valor_original"];
    $serie                  = $_POST["serie"];
    $observacao             = $_POST["observacao"];
    $extrato_tipo_nota      = $_POST["extrato_tipo_nota"];

    $valor_original = str_replace(".", "", $valor_original);
    $valor_original = str_replace(",", ".", $valor_original);

    if(strlen(trim($data_lancamento))>0){
        $data_lancamento = fnc_formata_data_pg($data_lancamento);
    }else{
        $data_lancamento = "'".date('Y-m-d')."'";
    }
    
    if(strlen(trim($extrato_tipo_nota))==0){
        $msg_erro["msg"][]    = "Preencha todos os campos obrigatórios";
        $msg_erro["campos"][] = "extrato_tipo_nota";
    }

    if(strlen(trim($nota_fiscal))==0){
        $msg_erro["msg"][]    = "Preencha todos os campos obrigatórios";
        $msg_erro["campos"][] = "nota_fiscal";
    }

    if(strlen(trim($valor_original))==0){
        $msg_erro["msg"][]    = "Preencha todos os campos obrigatórios";
        $msg_erro["campos"][] = "valor_original";
    }

    if(strlen(trim($previsao_pagamento))==0){
        $msg_erro["msg"][]    = "Preencha todos os campos obrigatórios";
        $msg_erro["campos"][] = "previsao_pagamento";
    }


    if(strlen(trim($data_emissao))==0){
        $msg_erro["msg"][]    = "Preencha todos os campos obrigatórios";
        $msg_erro["campos"][] = "data_emissao";
    }else{
        $data_emissao = fnc_formata_data_pg($data_emissao);
    }

    if(strlen(trim($previsao_pagamento))==0){
        $msg_erro["msg"][]    = "Preencha todos os campos obrigatórios";
        $msg_erro["campos"][] = "emissao";
    }else{
        $previsao_pagamento = fnc_formata_data_pg($previsao_pagamento);
    }
        
    if((!strlen ($codigo_posto) > 0) && (!strlen ($descricao_posto) > 0) && (!strlen ($data_final) > 0)) {
        $msg_erro["msg"][]    = "Preencha todos os campos obrigatórios";
        $msg_erro["campos"][] = "posto";
        $msg_erro["campos"][] = "data";
    }else{
        if ( isset($_POST['codigo_posto']) || isset($_POST['descricao_posto']) ) {
            if (strlen ($descricao_posto) > 0) {
                $sql = "SELECT posto, codigo_posto, nome FROM tbl_posto JOIN tbl_posto_fabrica USING (posto) 
                        WHERE tbl_posto_fabrica.fabrica = $login_fabrica AND tbl_posto.nome ILIKE '%$descricao_posto%' ORDER BY nome";
            }
            if (strlen ($codigo_posto) > 0) {
                $sql = "SELECT posto, codigo_posto, nome FROM tbl_posto JOIN tbl_posto_fabrica USING (posto) 
                        WHERE tbl_posto_fabrica.fabrica = $login_fabrica AND tbl_posto_fabrica.codigo_posto = '$codigo_posto'";
            }
            
            if (strlen ($sql) > 0) {
                $res = pg_exec ($con,$sql);
                if (pg_numrows($res) == 0) {
                    $msg_erro["msg"][]    = "Posto não encontrado";
                    $msg_erro["campos"][] = "posto";
                }
                if (pg_numrows($res) == 1) {
                    $relatorio = true;
                    $posto        = trim(pg_result($res,0,posto));
                    $codigo_posto = trim(pg_result($res,0,codigo_posto));
                    $descricao_posto   = trim(pg_result($res,0,nome));
                }
                if (pg_numrows($res) > 1) {
                    $escolhe_posto = true;
                }
            }
        }
    }

    if(count($msg_erro)==0){

        $begin = pg_query($con, "BEGIN TRANSACTION");
        $sql = "INSERT INTO tbl_extrato(
                posto,
                data_geracao,
                total,
                avulso,
                fabrica
                ) values(
                $posto,
                current_timestamp,
                $valor_original,
                $valor_original,
                $login_fabrica) RETURNING extrato";
        $res = pg_query($con, $sql);

                if(strlen(pg_last_error($con))==0){
                    $extrato = pg_fetch_result($res, 0, extrato);
                }else{
                    $msg_erro['erro'][] = pg_last_error($con);
                }

                $sql = "SELECT contato_estado
                        FROM tbl_posto_fabrica
                        JOIN tbl_extrato USING(posto,fabrica)
                        WHERE tbl_extrato.extrato = $extrato";
                $res = pg_query($con,$sql);
                if(strlen(pg_last_error($con))>0){
                    $msg_erro['erro'][] = pg_last_error();
                }
                if(pg_num_rows($res) > 0){
                    $contato_estado = pg_fetch_result($res,0,'contato_estado');

                    if(strlen(trim($contato_estado)) > 0) {
                        $sql = "SELECT cfop,codigo_item
                                FROM tbl_extrato_tipo_nota_excecao
                                WHERE extrato_tipo_nota = $extrato_tipo_nota
                                AND estado = '$contato_estado'";
                        $resT = pg_query($con,$sql);
                        if(pg_num_rows($resT) == 0){
                            $sql = "SELECT cfop,codigo_item
                                FROM tbl_extrato_tipo_nota
                                WHERE extrato_tipo_nota = $extrato_tipo_nota";
                            $resT = pg_query($con,$sql);
                        }
                    }else{
                        $sql = "SELECT cfop,codigo_item
                                FROM tbl_extrato_tipo_nota
                                WHERE extrato_tipo_nota = $extrato_tipo_nota";
                        $resT = pg_query($con,$sql);
                    }

                    if(pg_num_rows($resT) > 0){
                        $cfop = pg_fetch_result($resT,0,'cfop');
                        $codigo_item = pg_fetch_result($resT,0,'codigo_item');
                    }
                }

        $sql = "INSERT INTO tbl_extrato_nota_avulsa (
                fabrica ,
                extrato ,
                data_lancamento ,
                nota_fiscal ,
                data_emissao ,
                valor_original ,
                previsao_pagamento,
                admin ,
                observacao ,
                cfop ,
                codigo_item ,
                serie ,
                estabelecimento
                ) values(
                $login_fabrica ,
                $extrato ,
                $data_lancamento ,
                '$nota_fiscal' ,
                $data_emissao ,
                '$valor_original' ,
                $previsao_pagamento,
                $login_admin ,
                '$observacao' ,
                '$cfop' ,
                '$codigo_item' ,
                '$serie' ,
                '$estabelecimento'
                ) RETURNING extrato_nota_avulsa";
        $res = pg_query($con, $sql);

        if(strlen(pg_last_error($con))==0){
            $extrato_nota_avulsa = pg_fetch_result($res, 0, 'extrato_nota_avulsa');
        }

        if(strlen(pg_last_error($con))>0){
            $msg_erro['erro'][] = pg_last_error();
        }

        if(count($msg_erro)==0){
            $ok = "Cadastro realizado com sucesso."; 
            $begin = pg_query($con, "COMMIT TRANSACTION");
        }else{
            $begin = pg_query($con, "ROLLBACK TRANSACTION");
        }

        if(strlen(trim($extrato_nota_avulsa))>0){
            system("php exporta_extrato_tipo_nota.php nota_avulsa $extrato_nota_avulsa",$ret);
            $arquivo = "xls/integracao_ems.txt";

            if(file_exists($arquivo)){
                /*echo "<div style='margin: 20px; text-align: center;'>
                <a href='exporta_extrato_tipo_nota_download.php'>Download do Arquivo</a>
                </div>";*/

                echo "<script>";
                echo "window.open('exporta_extrato_tipo_nota_download.php'); ";
                echo "</script>";
            }else{
                if($ret == 0) {
                    echo "<script language='javascript'>";
                    echo "window.opener=null; ";
                    echo "window.open(\"\",\"_self\"); ";
                    echo "setTimeout('window.close()',1000); ";
                    echo "</script>";
                }
            }
        }

     
    }
}

if(isset($_POST['btnpesquisar'])){

    $codigo_posto           = $_POST['codigo_posto'];
    $descricao_posto        = $_POST['descricao_posto'];
    $data_inicial           = $_POST['data_inicial'];
    $data_final             = $_POST['data_final'];
    $lancado                = $_POST["lancado"];

    if(strlen(trim($data_inicial))>0 AND strlen(trim($data_final))>0){

        $xdata_inicial  = fnc_formata_data_pg($data_inicial);
        $xdata_final    = fnc_formata_data_pg($data_final);

        $cond_data = " AND tbl_extrato_nota_avulsa.data_lancamento between $xdata_inicial AND $xdata_final ";
    }


    if((!strlen ($codigo_posto) > 0) && (!strlen ($descricao_posto) > 0) && (!strlen ($data_final) > 0)) {
        $msg_erro["msg"][]    = "Preencha todos os campos obrigatórios";
        $msg_erro["campos"][] = "posto";
        $msg_erro["campos"][] = "data";
    }else{
        if ( isset($_POST['codigo_posto']) || isset($_POST['descricao_posto']) ) {
            if (strlen ($descricao_posto) > 0) {
                $sql = "SELECT posto, codigo_posto, nome FROM tbl_posto JOIN tbl_posto_fabrica USING (posto) 
                        WHERE tbl_posto_fabrica.fabrica = $login_fabrica AND tbl_posto.nome ILIKE '%$descricao_posto%' ORDER BY nome";
            }
            if (strlen ($codigo_posto) > 0) {
                $sql = "SELECT posto, codigo_posto, nome FROM tbl_posto JOIN tbl_posto_fabrica USING (posto) 
                        WHERE tbl_posto_fabrica.fabrica = $login_fabrica AND tbl_posto_fabrica.codigo_posto = '$codigo_posto'";
            }
            
            $verifica_erro = "nao";
            
            if (strlen ($sql) > 0) {
                $res = pg_exec ($con,$sql);
                if (pg_numrows($res) == 0) {
                    $msg_erro["msg"][]    = "Posto não encontrado";
                    $msg_erro["campos"][] = "posto";
                }
                if (pg_numrows($res) == 1) {
                    $relatorio = true;
                    $posto        = trim(pg_result($res,0,posto));
                    $codigo_posto = trim(pg_result($res,0,codigo_posto));
                    $descricao_posto   = trim(pg_result($res,0,nome));
                }
                if (pg_numrows($res) > 1) {
                    $escolhe_posto = true;
                }
            }
        }
    }

    if(count($msg_erro)==0){

        $pesquisa = true;

        $sqlPesquisa = "SELECT tbl_extrato.extrato,
            extrato_nota_avulsa,
            tbl_extrato_nota_avulsa.nota_fiscal,
            tbl_extrato_nota_avulsa.valor_original,
            tbl_extrato_nota_avulsa.previsao_pagamento,
            tbl_extrato_nota_avulsa.observacao,
            tbl_extrato_nota_avulsa.data_lancamento
            from tbl_extrato
            inner join tbl_extrato_nota_avulsa on tbl_extrato.extrato = tbl_extrato_nota_avulsa.extrato AND tbl_extrato_nota_avulsa.fabrica = $login_fabrica 
            where 
            tbl_extrato.fabrica = $login_fabrica
            and tbl_extrato.posto = $posto 
            $cond_data ";
        $resPesquisa = pg_query($con, $sqlPesquisa);
        if(strlen(pg_last_error($con))>0){
            $msg_erro['erro'][] = pg_last_error();
        }
    }
}


?>

<?
$layout_menu = "financeiro";
$title = "Lançamento de Nota Fiscal Avulsa";
include 'cabecalho_new.php';

$plugins = array(
"autocomplete",
"datepicker",
"shadowbox",
"mask",
"price_format",
"dataTable"
);

include("plugin_loader.php");
?>
<style>
    .campo_data{
        width:100px;
    }
    .campos{
        width:110px;
    }
    .campo_obs{
        width:350px;
        margin-bottom:0px !important;   
    }
    .select{
        margin-bottom:0px !important;   
    }
</style>
<script type="text/javascript">

    $(function() {
        $.datepickerLoad(Array("data_lancamento", "data_emissao", "previsao_pagamento","data_inicial", "data_final"),{dateFormat: "dd/mm/yy" });

        $.autocompleteLoad(Array("produto", "peca", "posto"));
        Shadowbox.init();

        $("span[rel=lupa]").click(function () {
            $.lupa($(this));
        });

        $(".btn_excluir").click(function(){
            var id = $(this).data('extratoavulso');

            var r = confirm("Deseja realmente excluir esse avulso?");
            if (r == true) {
                $.ajax({
                    url: '<?php echo $_SERVER[PHP_SELF]; ?>',
                    type: 'POST',
                    data: {
                        excluir : true,
                        extratoavulso: id
                    },
                    complete: function(data){
                        data = data.responseText;

                        if(data == 'sucesso'){
                            $('#'+id).remove();
                        }else{
                            alert("Falha ao excluir avulso.");
                        }
                    }
                });
            } 
        });

      //  $("#btnacao").show();
      //  $("#btnpesquisar").hide();

        $("#lancado").change(function(){
            if($("#lancado").is(":checked")) {
                $("#btnacao").hide();
                $("#btnpesquisar").show();
                $("#campos_data").show();
                $("#tabela_gravar").hide();
            }else{
                $("#btnacao").show();
                $("#btnpesquisar").hide();
                $("#campos_data").hide();
                $("#tabela_gravar").show();
                $("#resultado_pesquisa").hide();
            }
        });

    });

    function retorna_posto(retorno){
        $("#codigo_posto").val(retorno.codigo);
        $("#descricao_posto").val(retorno.nome);
    }

</script>

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
if (strlen($ok) > 0) {
?>
    <div class="alert alert-success">
        <h4><?=$ok?></h4>
    </div>
<?php
}
?>

<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>

<form name="frm_encontro" method="post" action="<? $PHP_SELF ?>">
<div class='form-search form-inline tc_formulario'>
    <div class='titulo_tabela '>Parâmetros de Pesquisa</div>
    <br/>
    <div class='row-fluid'>
        <div class='span2'></div>
            <div class='span4'>
                <div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='codigo_posto'>Código Posto</label>
                    <div class='controls controls-row'>
                        <div class='span7 input-append'>
                            <h5 class='asteristico'>*</h5>
                            <input type="text" name="codigo_posto" id="codigo_posto" value="<? echo $codigo_posto ?>" class='span12'>
                            <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                            <input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
                        </div>
                    </div>
                </div>
            </div>
            <div class='span4'>
                <div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='descricao_posto'>Nome Posto</label>
                    <div class='controls controls-row'>
                        <div class='span12 input-append'>
                            <h5 class='asteristico'>*</h5>
                            <input type="text" name="descricao_posto" id="descricao_posto" value="<? echo $descricao_posto ?>" class='span12'>&nbsp;
                            <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                            <input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
                        </div>
                    </div>
                </div>
            </div>
        <div class='span2'></div>
    </div>
    <div class='row-fluid'>
        <div class='span2'></div>
            <div class='span6'>
                <div class="checkbox">
                <br>
                    <label>
                        <input type="checkbox" name='lancado' id="lancado" value='S' <?php if($lancado == 'S') echo " checked "?>> Verificar lançamento para o posto autorizado 
                    </label>
                </div>
            </div>
        <div class='span4'></div>
        <div class='span2'></div>
    </div>
    
    




    


    <?php   if($pesquisa == true){
                $mostra_data = " style='display:block;' ";
            }else{
                $mostra_data =  " style='display:none;' ";
            }  
    ?>
    <div id="campos_data" <?=$mostra_data ?> >
        <div class='row-fluid'>
            <div class='span2'></div>
                <div class='span4'>
                    <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
                        <label class='control-label' for='data_inicial'>Data Inicial</label>
                        <div class='controls controls-row'>
                            <div class='span7 input-append'>
                                <input type="text" name="data_inicial" id="data_inicial" value="<? echo $data_inicial ?>" class='span12'>
                            </div>
                        </div>
                    </div>
                </div>
                <div class='span4'>
                    <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
                        <label class='control-label' for='data_final'>Data Final</label>
                        <div class='controls controls-row'>
                            <div class='span7 input-append'>
                                <input type="text" name="data_final" id="data_final" value="<? echo $data_final ?>" class='span12'>&nbsp;
                            </div>
                        </div>
                    </div>
                </div>
            <div class='span2'></div>
        </div>
        <div class='row-fluid'>
            <div class='span2'></div>
                <div class='span12 tac'>
                        <input type="submit" name="btnpesquisar" id="btnpesquisar" class='btn' value="Pesquisar"  <?php if($pesquisa != true) echo " style='display:none' ";?> >
                </div>
            <div class='span4'></div>
            <div class='span2'></div>
        </div>
    </div>
</div>
</div>

<div id="tabela_gravar"  <?php  if($pesquisa == true) echo "style='display: none'; " ?>>
    <table class='table table-striped table-bordered table-hover table-fixed' align='center' border='0'>
        <thead>
            <tr class="titulo_coluna">
                <th>Data Lançamento</th>
                <th>Admin</th>
                <th>Nota Fiscal</th>
                <th>Data Emissão</th>
                <th>Valor Original</th>
                <th>Previsão de Pagamento</th>
                <th>Série</th>
                <th>Estabelecimento</th>
            </tr>
            <tr>
                <td class='tac'><input type="text" name="data_lancamento" class='campo_data' id="data_lancamento" value="<?=mostra_data($data_lancamento) ?>"></td>
                <td class='tac'><?=$nome_completo?></td>
                <td class='tac control-group <?=(in_array("nota_fiscal", $msg_erro["campos"])) ? "error" : ""?>'><input type="text" name="nota_fiscal" maxlength="25" class='campos' id="nota_fiscal" value="<?=$nota_fiscal ?>"></td>
                <td class='tac control-group <?=(in_array("data_emissao", $msg_erro["campos"])) ? "error" : ""?>'><input type="text" name="data_emissao" class='campo_data' id="data_emissao" value="<?=mostra_data($data_emissao) ?>"></td>
                <td class='tac control-group <?=(in_array("valor_original", $msg_erro["campos"])) ? "error" : ""?>'><input type="text" name="valor_original"  maxlength="50" id="valor_original" price="true" value="<?=$valor_original ?>"></td>
                <td class='tac control-group <?=(in_array("previsao_pagamento", $msg_erro["campos"])) ? "error" : ""?>'><input type="text" name="previsao_pagamento" class='campo_data' id="previsao_pagamento" value="<?=mostra_data($previsao_pagamento) ?>"></td>
                <td class='tac'><input type="text" name="serie"  maxlength="50" class='campos' id="serie" value="<?=$serie ?>"></td>
                <td>
                    1 <input type="radio" name="estabelecimento" id="estabelecimento_1" value="1" <?php if($estabelecimento == 1){ echo " checked "; }?> > 
                    22 <input type="radio" name="estabelecimento" id="estabelecimento_22" value="22" <?php if($estabelecimento == 22){ echo " checked "; }?>>

                    <input type="submit" name="btnacao" id="btnacao" class='btn' value="Gravar" <?php if($pesquisa == true) echo " style='display:none' "; ?> > 
                </td>
            </tr>
            <tr>
                <th class="titulo_coluna">Observação</th>
                <td colspan="3">
                    <input type="text" name="observacao" class='campo_obs' value="<?=$observacao?>">
                </td>
                <th class="titulo_coluna">Tipo Nota</th>
                <td colspan="3" class="control-group <?=(in_array("extrato_tipo_nota", $msg_erro["campos"])) ? "error" : ""?>">
                    <select name="extrato_tipo_nota" class="select">
                        <option value="">Selecione</option>
                        <?php 
                            $sql = "select extrato_tipo_nota, descricao from  tbl_extrato_tipo_nota where fabrica = $login_fabrica";
                            $res = pg_query($con, $sql);
                            for($i=0; $i<pg_num_rows($res); $i++){
                                $extrato_tipo_nota_db = pg_fetch_result($res, $i, extrato_tipo_nota);
                                $descricao = pg_fetch_result($res, $i, descricao);

                                if($extrato_tipo_nota == $extrato_tipo_nota_db){
                                    $selected = " selected ";
                                }else{
                                    $selected = " ";
                                }

                                echo "<option value='$extrato_tipo_nota_db' $selected>$descricao</option>";
                            }
                        ?>
                    </select>
                </td>
            </tr>
        </thead>
    </table>
</div>
</form>

<?php if(pg_num_rows($resPesquisa) > 0 AND $pesquisa == true){ ?>
    <br><br>
    <div id="resultado_pesquisa" <?php  if($pesquisa == true) echo "style='display: block'; " ?>>
    <table class='table table-striped table-bordered table-fixed' align='center' border='0'>
        <tr class="titulo_coluna">
            <th colspan="6">Avulsos Cadastrados</th>
        </tr>
        <tr class="titulo_coluna">
            <th>Extrato</th>
            <th>NF</th>
            <th>Valor</th>
            <th>Previsão de Pagamento</th>
            <th>Observação</th>
            <th>Ações</th>
        </tr>

        <?php 
        for($a=0; $a<pg_num_rows($resPesquisa); $a++){
            $extrato = pg_fetch_result($resPesquisa, $a, 'extrato');
            $nota_fiscal = pg_fetch_result($resPesquisa, $a, 'nota_fiscal');
            $valor_original = pg_fetch_result($resPesquisa, $a, 'valor_original');
            $valor_original = number_format($valor_original, 2, ',', ' ');
            $previsao_pagamento = pg_fetch_result($resPesquisa, $a, 'previsao_pagamento');
            $observacao = pg_fetch_result($resPesquisa, $a, 'observacao');
            $data_lancamento = pg_fetch_result($resPesquisa, $a, 'data_lancamento');
            $extrato_nota_avulsa = pg_fetch_result($resPesquisa, $a, extrato_nota_avulsa);

        ?>
        <tr id="<?=$extrato_nota_avulsa?>">
            <td class='tac' ><?=$extrato?></td>
            <td class='tac'><?=$nota_fiscal?></td>
            <td class='tac'><?="R$ ".$valor_original?></td>
            <td class='tac'><?=mostra_data($previsao_pagamento)?></td>
            <td class='tac'><?=$observacao?></td>
            <td class='tac'>
            <?php if($data_lancamento == date("Y-m-d")){ ?>
                <button type='button' class="btn btn-danger btn_excluir"  data-extratoavulso="<?=$extrato_nota_avulsa?>">Excluir</button>
            <?php } ?>
            </td>
        </tr>

        <?php

        }

        ?>


    </table>
    </div>
    <br>
    <br>


<?php } ?>


<div>
<br><br>


<?php
if(pg_num_rows($resPesquisa)==0 AND $pesquisa == true) { ?>
    <div class="container">
        <div class="alert">
                <h4>Nenhum resultado encontrado</h4>
        </div>
    </div>
</div>
<? }
include_once 'rodape.php';
?>
