<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="call_center";
include 'autentica_admin.php';
include 'funcoes.php';

if ($_POST["exec_relatorio_os"]) {
    $os_revenda = $_POST["os_revenda"];

    $sql = "
        SELECT
            o.os,
            o.sua_os,
            o.consumidor_revenda,
            TO_CHAR(o.data_conserto, 'DD/MM/YYYY') AS data_conserto,
            TO_CHAR(o.data_fechamento, 'DD/MM/YYYY') AS data_fechamento,
            TO_CHAR(o.data_abertura, 'DD/MM/YYYY') AS data_abertura,
            ta.descricao AS tipo_atendimento,
            sc.descricao AS status_checkpoint,
            p.descricao AS descricao_produto,
            p.referencia AS referencia_produto
        FROM tbl_os_campo_extra oce
        JOIN tbl_os o ON o.os = oce.os AND o.fabrica = $login_fabrica
        JOIN tbl_status_checkpoint sc ON sc.status_checkpoint = o.status_checkpoint
        LEFT JOIN tbl_tipo_atendimento ta ON ta.tipo_atendimento = o.tipo_atendimento AND ta.fabrica = $login_fabrica
        LEFT JOIN tbl_produto p ON p.produto = o.produto AND p.fabrica_i = $login_fabrica
        WHERE oce.os_revenda = $os_revenda
        AND oce.fabrica = $login_fabrica
    ";
    $res = pg_query($con, $sql);
    
    if (pg_num_rows($res) > 0){
        $dados = pg_fetch_all($res);
        $dados = array_map(function($t) {
            $t['status_checkpoint']  = utf8_encode($t['status_checkpoint']);
            $t['tipo_atendimento']   = utf8_encode($t['tipo_atendimento']);
            $t['descricao_produto']  = utf8_encode($t['descricao_produto']);
            $t['referencia_produto'] = utf8_encode($t['referencia_produto']);
            return $t;
        }, $dados);
        exit(json_encode(array('dados' => $dados)));
    }else{
        exit(json_encode(array('error' => "ok")));
    }
    exit;
}

if ($_POST["btn_acao"] == "submit") {
    $data_inicial       = $_POST['data_inicial'];
    $data_final         = $_POST['data_final'];
    $origem             = $_POST['origem'];
    
    if (!strlen($data_inicial) or !strlen($data_final)) {
        $msg_erro["msg"][]    = "Preencha os campos obrigatórios";
        $msg_erro["campos"][] = "data";
    } else {
        list($di, $mi, $yi) = explode("/", $data_inicial);
        list($df, $mf, $yf) = explode("/", $data_final);

        if (!checkdate($mi, $di, $yi) or !checkdate($mf, $df, $yf)) {
            $msg_erro["msg"][]    = "Data Inválida";
            $msg_erro["campos"][] = "data";
        } else {
            $aux_data_inicial = "{$yi}-{$mi}-{$di}";
            $aux_data_final   = "{$yf}-{$mf}-{$df}";

            if (strtotime($aux_data_final) < strtotime($aux_data_inicial)) {
                $msg_erro["msg"][]    = "Data Final não pode ser menor que a Data Inicial";
                $msg_erro["campos"][] = "data";
            }
        }
    }

    if (!empty($origem)){
        $origens = implode(',', $origem);
        $cond_origem = " AND hce.hd_chamado_origem IN ($origens) ";
    }

    if (!count($msg_erro["msg"])) {
        if (!empty($data_inicial) AND !empty($data_final)){
            $interval_label = "período {$data_inicial} - {$data_final}";
        }

        $sql = "
            SELECT 
                osr.os_revenda,
                osr.consumidor_nome,
                p.nome,
                hc.hd_chamado
            FROM tbl_os_revenda osr
            LEFT JOIN tbl_hd_chamado hc ON hc.hd_chamado = osr.hd_chamado AND hc.fabrica = $login_fabrica
            LEFT JOIN tbl_hd_chamado_extra hce ON hce.hd_chamado = hc.hd_chamado 
            LEFT JOIN tbl_posto_fabrica pf ON pf.posto = osr.posto AND pf.fabrica = $login_fabrica
            LEFT JOIN tbl_posto p ON p.posto = pf.posto
            WHERE osr.fabrica = $login_fabrica
            AND osr.data_abertura BETWEEN '$aux_data_inicial' AND '$aux_data_final'
            AND osr.excluida IS FALSE
            $cond_origem  ";
        $res = pg_query($con, $sql);
        $count_res = pg_num_rows($res);
        if ($count_res > 0) {
            $array_dados = pg_fetch_all($res);
            $array_atendimento = array();
            $array_os = array();

            for ($i=0; $i < pg_num_rows($res); $i++) { 
                $atendimento = pg_fetch_result($res, $i, "hd_chamado");
                $array_os[] = pg_fetch_result($res, $i, "os_revenda");

                if (!empty($atendimento)){
                    $array_atendimento[] = pg_fetch_result($res, $i, "hd_chamado");
                }
            }
            
            $dados_series[] = array(
                "name" => utf8_encode("Ordens de Serviços"),
                "y" => count($array_os),
                "sliced" => true,
                "selected" => true
            );
            $dados_series[] = array(
                "name" => "Atendimento",
                "y" => count($array_atendimento)
            );
            $dados_series  = json_encode($dados_series);
        }
    }
}

$layout_menu = "gerencia";
$title = "RELATÓRIO DE ORDEM DE SERVIÇO";

include "cabecalho_new.php";

$plugins = array(
    "autocomplete",
    "datepicker",
    "shadowbox",
    "highcharts_v7",
    "mask",
    "dataTable",
    "select2"
);

include("plugin_loader.php");
?>

<script type="text/javascript">
    
    $(function() {
        $.datepickerLoad(Array("data_final", "data_inicial"));
        $.autocompleteLoad(Array("produto", "posto"));
        Shadowbox.init();

        $("span[rel=lupa]").click(function () {
            $.lupa($(this));
        });
    });

    function retorna_produto (retorno) {
        $("#produto_referencia").val(retorno.referencia);
        $("#produto_descricao").val(retorno.descricao);
    }

    function retorna_posto(retorno){
        $("#codigo_posto").val(retorno.codigo);
        $("#descricao_posto").val(retorno.nome);
    }

    // ----- SCRIPT MODAL -----//
    $(function() {
        $(".visualizar-dados").on("click", function() {
            $('#tabela_resultado').dataTable().fnClearTable();
            $('#tabela_resultado').dataTable().fnDestroy();

            let os_revenda = $(this).data("os_revenda");
            let modal_relatorio_os  = $("#modal-relatorio-os");
            
            $(".th_titulo").text("OS Produto");

            var data_ajax = {
                exec_relatorio_os: true,
                os_revenda: os_revenda
            };

            $.ajax({
                url: "relatorio_os_aberta_callcenter.php",
                type: "post",
                data: data_ajax,
                async: false,
                timeout: 10000
            }).done(function(res) {
                res = JSON.parse(res);
                var tabela = ""; 
                if (res.dados){
                    $(res.dados).each(function(x,y){
                        if (y.data_conserto == "" || y.data_conserto == undefined){
                            y.data_conserto = "";
                        }
                        if (y.data_fechamento == "" || y.data_fechamento == undefined){
                            y.data_fechamento = "";
                        }
                        if (y.consumidor_revenda != "" && y.consumidor_revenda != undefined){
                            if (y.consumidor_revenda == "C"){
                                y.consumidor_revenda = "Consumidor";
                            }else{
                                y.consumidor_revenda = "Revenda";
                            }
                        }

                        if (y.referencia_produto != "" && y.referencia_produto != undefined && y.descricao_produto != "" && y.descricao_produto != undefined){
                            y.descricao_produto = y.referencia_produto+' - '+y.descricao_produto;
                        }

                        tabela +="<tr>";
                        tabela +="<td><a href='os_press.php?os="+y.os+"' target='_blank'>"+y.sua_os+"</a></td><td>"+y.data_abertura+"</td><td>"+y.status_checkpoint+"</td><td>"+y.consumidor_revenda+"</td><td>"+y.descricao_produto+"</td><td>"+y.tipo_atendimento+"</td>";
                        tabela +="</tr>";
                    });
                    $(".tabela_tbody").html(tabela);
                }
            });
            $(modal_relatorio_os).modal("show");
            $.dataTableLoad({ table: "#tabela_resultado" });
        });

        $("#btn-close-modal-relatorio-os").on("click", function(){
            let modal_relatorio_os  = $("#modal-relatorio-os");
            $(modal_relatorio_os).modal("hide");
        });
    });
</script>
<style type="text/css">
    div#modal-relatorio-os {
        width: 950px;
        margin-left: -475px;
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

<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>" align='center' class='form-search form-inline tc_formulario'>
    <div class='titulo_tabela '>Parâmetros de Pesquisa</div>
    <br/>

    <div class='row-fluid'>
        <div class='span2'></div>
            <div class='span4'>
                <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='data_inicial'>Data Inicial</label>
                    <div class='controls controls-row'>
                        <div class='span4'>
                            <h5 class='asteristico'>*</h5>
                                <input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=$data_inicial?>">
                        </div>
                    </div>
                </div>
            </div>
        <div class='span4'>
            <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='data_final'>Data Final</label>
                <div class='controls controls-row'>
                    <div class='span4'>
                        <h5 class='asteristico'>*</h5>
                            <input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?=$data_final?>" >
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>

    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class="control-group" >
                <label class="control-label" for="origem" >Origem</label>
                <div class="controls controls-row" >
                    <select name="origem[]" class="span10" multiple="multiple">
                        <?php
                        $sql = " 
                            SELECT hd_chamado_origem, descricao, valida_obrigatorio 
                            FROM tbl_hd_chamado_origem WHERE fabrica = $login_fabrica 
                            AND ativo IS TRUE ORDER BY descricao ASC";
                        $res = pg_query($con,$sql);

                        for ($i = 0; $i < pg_num_rows($res); $i++) {
                            $aux_origem = pg_fetch_result($res, $i, "hd_chamado_origem");
                            $aux_descricao = pg_fetch_result($res, $i, "descricao");
                            $selected = (in_array($aux_origem, $origem)) ? "SELECTED" : "";
                        ?>
                            <option value="<?= $aux_origem; ?>" <?= $selected; ?>><?= $aux_descricao; ?></option>
                        <?php
                        }
                        ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <p><br/>
        <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
        <input type='hidden' id="btn_click" name='btn_acao' value='' />
    </p><br/>
</form>

<script type="text/javascript">
    $("select").select2();
</script>
<?php 
if (isset($_POST["btn_acao"]) AND !count($msg_erro)) {
    if ($count_res > 0){ ?>
        <!-- <div id="container" style="width: 700px; height: 400px; margin: 0 auto"></div> -->
        <script type="text/javascript">
            
            // Highcharts.chart('container', {
            //     chart: {
            //         plotBackgroundColor: null,
            //         plotBorderWidth: null,
            //         plotShadow: false,
            //         type: 'pie'
            //     },
            //     title: {
            //         text: '<b>Quantidade de Ordens de Serviços Abertas X Quantidade de Atendimento</b> <br/> <?=$interval_label?>'
            //     },
            //     tooltip: {
            //         headerFormat: '<span style="font-size:11px">{series.name}</span><br>',
            //         pointFormat: '<span style="color:{point.color}">{point.name}</span>: <b>Qtde {point.y}</b><br/>'
            //     },
            //     plotOptions: {
            //         pie: {
            //             allowPointSelect: true,
            //             cursor: 'pointer',
            //             dataLabels: {
            //                 enabled: false
            //             },
            //             showInLegend: true
            //         }
            //     },
            //     series: [{
            //         name: '',
            //         colorByPoint: true,
            //         data: <?=$dados_series?>
            //     }]
            // });
        </script>

        <table id="resultado_os_atendimento" class='table table-striped table-bordered table-hover table-fixed' >
            <thead>
                <tr class='titulo_tabela'>
                    <th colspan="4" >Relatório OSs Aberta x Fechadas</th>
                </tr>
                <tr class="titulo_coluna">
                    <th colspan="2">Qtde OS Aberta: <?=count($array_os);?></th>
                    <th colspan="2">Qtde Atendimento: <?=count($array_atendimento);?></th>
                </tr>
                <tr class="titulo_coluna">
                    <th>OS Principal</th>
                    <th>Consumidor</th>
                    <th>Posto Autorizado</th>
                    <th>Número Atendimento</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    foreach ($array_dados as $key => $value) {
                ?>
                        <tr>
                            <td class="tac visualizar-dados" data-os_revenda='<?=$value["os_revenda"]?>' style="cursor: pointer; color: #0033c3;"><?=$value["os_revenda"]?></td>
                            <td><?=$value["consumidor_nome"]?></td>
                            <td><?=$value["nome"]?></td>
                            <td class='tac'><?=$value["hd_chamado"]?></td>
                        </tr>
                <?php
                    }
                ?>
            </tbody>
        </table>
        <?php
        if ($count_res > 50) {
        ?>
            <script>
                $.dataTableLoad({ table: "#resultado_os_atendimento" });
            </script>
        <?php
        }
        ?>
<?php }else{ ?>
    <div class="container">
        <div class="alert">
            <h4>Nenhum resultado encontrado</h4>
        </div>
    </div>
<?php 
    } 
}
?>

<div id="modal-relatorio-os" class="modal hide fade" data-backdrop="static" data-keyboard="false" >
    <div class="modal-body">
        <table id="tabela_resultado" class="table table-bordered table-hover table-fixed" >
            <thead>
                <tr class='titulo_tabela'>
                    <th colspan="7" class="th_titulo"></th>
                </tr>
                <tr class="titulo_coluna">
                    <th>OS</th>
                    <th>Data Abertura</th>
                    <th>Status</th>
                    <th>Consumidor/Revenda</th>
                    <th>Produto</th>
                    <th>Tipo Atendimento</th>
                </tr>
            </thead>
            <tbody class="tabela_tbody">
            
            </tbody>
        </table>
    </div>
    <div class="modal-footer">
        <button type="button" id="btn-close-modal-relatorio-os" class="btn">Fechar</button>
    </div>
</div>



<?php include "rodape.php" ?>
