<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="call_center";
include 'autentica_admin.php';
include 'funcoes.php';

if($_POST["btn_acao"]){

    $data_inicial   = $_POST["data_inicial"];
    $data_final     = $_POST["data_final"];
    $codigo_posto   = $_POST["codigo_posto"];
    $posto_nome     = $_POST["posto_nome"];
    $tecnico        = $_POST["tecnico"];

    if(strlen($data_inicial) > 0 && strlen($data_final) > 0){

        list($d,$m,$y) = explode("/", $data_inicial);

        if(!checkdate($m, $d, $y)){
            $msg_erro["msg"] = "Data Inicial inválida";
        }else{
            $aux_data_inicial = "$y-$m-$m";
        }

        list($d,$m,$y) = explode("/", $data_final);

        if(!checkdate($m, $d, $y)){
            $msg_erro["msg"] = "Data Final inválida";
        }else{
            $aux_data_final = "$y-$m-$m";
        }

    }

    if(strlen($codigo_posto) > 0 AND strlen($tecnico) == 0){

        $sql = "SELECT posto FROM tbl_posto_fabrica WHERE fabrica = {$login_fabrica} AND codigo_posto = '{$codigo_posto}'";
        $res = pg_query($con,$sql);

        if(pg_num_rows($res) > 0){
            $posto = pg_fetch_result($res, 0, "posto");

            $cond = " AND tbl_os.posto = {$posto} ";
        }else{
            $msg_erro["msg"] = "Posto não encontrado";
        }

    }

    if(strlen($tecnico) > 0){
        $cond .= " AND tbl_os.tecnico = {$tecnico} ";
    }

    if(count($msg_erro) == 0){

        $sql = "SELECT 
              tbl_tecnico.tecnico,
              tbl_tecnico.nome,
              count(DISTINCT tbl_os.os) as produtos,
              count(tbl_os_item.peca) as pecas,
              (SUM(data_conserto::date - data_abertura)::float / count(DISTINCT tbl_os.os))::float AS tmat,
              (SELECT COUNT(tbl_os.os) FROM tbl_os WHERE tbl_os.os_reincidente AND tbl_os.tecnico = tbl_tecnico.tecnico AND tbl_os.data_conserto NOTNULL  AND tbl_os.data_fechamento NOTNULL  AND tbl_os.data_digitacao BETWEEN '{$aux_data_inicial} 00:00:00' AND '{$aux_data_final} 23:59:59') AS retorno
            FROM tbl_os 
              LEFT JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os AND tbl_os_produto.produto = tbl_os.produto
              LEFT JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
              LEFT JOIN tbl_tecnico ON tbl_tecnico.tecnico = tbl_os.tecnico 
            WHERE 
              tbl_os.data_conserto NOTNULL
              AND tbl_os.data_fechamento NOTNULL
              $cond
              AND tbl_os.fabrica = {$login_fabrica} 
              AND tbl_os.excluida IS NOT TRUE
              AND tbl_os.data_digitacao BETWEEN '{$aux_data_inicial} 00:00:00' AND '{$aux_data_final} 23:59:59'
            GROUP BY tbl_tecnico.tecnico,tbl_tecnico.nome;";
        $resSubmit = pg_query($con,$sql);

    }

}

$layout_menu = "callcenter";
$title = "RELATÓRIO DE PRODUTIVIDADE DE TÉCNICOS";

include "cabecalho_new.php";

$plugins = array(
    "autocomplete",
    "datepicker",
    "shadowbox",
    "mask",
    "dataTable"
);

include("plugin_loader.php");

?>

<script>
    $(function(){

        Shadowbox.init();

        $.datepickerLoad(Array("data_final", "data_inicial"));
        $.autocompleteLoad(Array("posto"));        

        $("span[rel=lupa]").click(function () {
            $.lupa($(this));
        });

        $("button.btn-ver-os").on("click", function() {
            var tecnico  = $(this).attr("data-tecnico");
            var data_ini = $(this).attr("data-ini");
            var data_fim = $(this).attr("data-fim");
            var nome     = $(this).attr("data-nome");
            var tipo     = $(this).attr("data-tipo");

            Shadowbox.open({
                content: "relatorio_os_tecnico.php?tecnico="+tecnico+"&data_ini="+data_ini+"&data_fim="+data_fim+"&nome="+nome+"&tipo="+tipo,
                player: "iframe",
                width: 850,
                height: 600,
                title: "Ordens de Serviço"
            });
        });

    });

    function retorna_posto(retorno){
        $("#codigo_posto").val(retorno.codigo);
        $("#descricao_posto").val(retorno.nome);
    }

</script>

<!-- FORM NOVO -->

<div class="container">

    <?php
    if (count($msg_erro["msg"]) > 0) {
    ?>
        <div class="alert alert-error">
            <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
        </div>
    <?php
    }
    ?>

    <div class="row">
        <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
    </div>

    <form name='frm_lbm' MEthOD='POST' ACTION='<?=$PHP_SELF?>' align='center' enctype='multipart/form-data' class='form-search form-inline tc_formulario' >
        <div class='titulo_tabela '>Parâmetros de Pesquisa</div>

        <br />

        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span4'>
                <div class='control-group <?=(in_array("data_inicial", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='data_inicial'>Data Inicial</label>
                    <div class='controls controls-row'>
                        <div class='span7 input-append'>
                            <h5 class='asteristico'>*</h5>
                            <input type="text" id="data_inicial" name="data_inicial" class='span12' maxlength="20" value="<?=$data_inicial?>">
                        </div>
                    </div>
                </div>
            </div>
            <div class='span4'>
                <div class='control-group <?=(in_array("data_final", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='data_final'>Data Final</label>
                    <div class='controls controls-row'>
                        <div class='span7 input-append'>
                            <h5 class='asteristico'>*</h5>
                            <input type="text" id="data_final" name="data_final" class='span12' value="<?=$data_final?>">
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>

        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span4'>
                <div class='control-group'>
                    <label class='control-label' for='codigo_posto'>Cod. Posto</label>
                    <div class='controls controls-row'>
                        <div class='span10 input-append'>
                            <input type="text" id="codigo_posto" name="codigo_posto" class='span12' maxlength="20" value="<? echo $codigo_posto ?>" >
                            <span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
                            <input type="hidden" name="lupa_config" tipo="posto" parametro="referencia" />
                        </div>
                    </div>
                </div>
            </div>
            <div class='span4'>
                <div class='control-group'>
                    <label class='control-label' for='descricao_posto'>
                        Nome Posto
                    </label>
                    <div class='controls controls-row'>
                        <div class='span10 input-append'>
                            <input type="text" id="descricao_posto" name="posto_nome" class='span12' value="<? echo $posto_nome; ?>" >
                            <span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
                            <input type="hidden" name="lupa_config" tipo="posto" parametro="descricao" />
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>

        <p>
            <br/>
            <button class='btn' id="btn_acao" type="button"  onclick=" submitForm($(this).parents('form'),'pesquisar');">Pesquisar</button>
            <input type='hidden' id="btn_click" name='btn_acao' value='' />
        </p>

        <br/>

    </form>

<p>

<?php

    if(pg_num_rows($resSubmit) > 0){
?>
        <table name='relatorio' id='relatorio' class='table table-striped table-bordered table-hover table-fixed'>
            <thead>
                <th>Técnico</th>
                <th>Qtde OS</th>
                <th>Qtde Peças</th>
                <th>Tempo Médio Atendimento</th>
            </thead>
            <tbody>
<?php


            for($i = 0; $i < pg_num_rows($resSubmit); $i++){

                $tecnico_nome = pg_fetch_result($resSubmit, $i, "nome");
                $tecnico = pg_fetch_result($resSubmit, $i, "nome");
                $produtos = pg_fetch_result($resSubmit, $i, "produtos");
                $pecas = pg_fetch_result($resSubmit, $i, "pecas");
                $tmat = pg_fetch_result($resSubmit, $i, "tmat");
                $retorno = pg_fetch_result($resSubmit, $i, "retorno");

            ?>
                <tr>
                    <td><?=$tecnico_nome?></td>
                    <td class='tac'><button type="button" class="btn btn-link btn-ver-os" data-tecnico="<?=$tecnico?>" data-ini="<?=$aux_data_inicial?>" data-fim="<?=$aux_data_final?>" data-tipo="os"><i class="icon-comment icon-white"><?=$produtos?></i></button></td>
                    <td class='tac'><button type="button" class="btn btn-link btn-ver-os" data-tecnico="<?=$tecnico?>" data-ini="<?=$aux_data_inicial?>" data-fim="<?=$aux_data_final?>" data-tipo="peca"><i class="icon-comment icon-white"><?=$pecas?></i></button></td>
                    <td class='tac'><?=$tmat?></td>
                </tr>
            <?php

            }
            echo "</tbody></table>";

    }
?>

</div>

<? include "rodape.php" ?>
