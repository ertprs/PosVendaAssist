<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios = "gerencia";
include 'autentica_admin.php';
include 'funcoes.php';

// O Campo origem em tbl_produto.origem só aceita 3 caracteres, por isso a necessidade de criar o De Para
$deParaOrigem = array(
    "MNS" => "Manaus",
    "IMP" => "Importado",
    "CNS" => "Canoas"
);

$btn_acao = $_REQUEST['btn_acao'];

if (!empty($btn_acao)) {

    $data_inicial       = $_REQUEST["data_inicial"];
    $data_final         = $_REQUEST["data_final"];
    $defeito_constatado = $_REQUEST["defeito_constatado"];
    $status             = $_REQUEST["status"];
    $linha              = $_REQUEST["linha"];
    $linha_producao     = $_REQUEST["linha_producao"];
    $origem             = $_REQUEST["origem"];
    $familia_sap        = $_REQUEST["familia_sap"];
    $pd                 = $_REQUEST["pd"];

    if (is_array($linha)) {
        $linha = implode(",", $linha);
    }

    if (is_array($tipo_atendimento)) {
        $tipo_atendimento = implode(",", $tipo_atendimento);
    }

    if (is_array($status)) {
        $status = implode(",", $status);
    }

    if (is_array($origem)) {
        $origem = array_map(function($e) {
            return "'{$e}'";
        } , $origem);
        $origem = implode(",", $origem);
    }

    if (is_array($linha_producao)) {
        $linha_producao = array_map(function($e) {
            return "'{$e}'";
        } , $linha_producao);
        $linha_producao = implode(",", $linha_producao);
    }

    if (is_array($familia_sap)) {
        $familia_sap = array_map(function($e) {
            return "'{$e}'";
        } , $familia_sap);
        $familia_sap = implode(",", $familia_sap);
    }

    if (is_array($pd)) {
        $pd = array_map(function($e) {
            return "'{$e}'";
        } , $pd);
        $pd = implode(",", $pd);
    }

    if (empty($data_inicial) || empty($data_final)) {
        $msg_erro["msg"]["obrigatorio"] = "Preencha os campos obrigatórios";
        $msg_erro["campos"][] = "data";
    } else {
        list($di, $mi, $yi) = explode("/", $data_inicial);
        list($df, $mf, $yf) = explode("/", $data_final);

        if (!checkdate($mi, $di, $yi) || !checkdate($mf, $df, $yf)) {
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

        $sqlX = "SELECT '$aux_data_inicial'::date + interval '36 months' >= '$aux_data_final'";
        $resSubmitX = pg_query($con,$sqlX);
        $periodo_6meses = pg_fetch_result($resSubmitX,0,0);
        if($periodo_6meses == 'f'){
            $msg_erro['msg'][] = "O intervalo entre as datas deve ser de no máximo 6 meses";
        }
    }



    if (count($msg_erro['msg']) == 0) {

        $interval_label = "período {$data_inicial} - {$data_final}";

        if (!empty($status)) {
            $whereStatus = "AND o.status_checkpoint IN ({$status})";
        }

        if (!empty($origem)) {
            $whereOrigem = "AND prt.origem IN ({$origem})";
        }

        if (!empty($linha_producao)) {
            $whereLinhaProducao = "AND prt.nome_comercial IN ({$linha_producao})";
        }

        if (!empty($familia_sap)) {
            $whereFamiliaSap = "AND JSON_FIELD('familia_desc', prt.parametros_adicionais) IN ({$familia_sap})";
        }

        if (!empty($pd)) {
            $wherePd = "AND JSON_FIELD('pd', prt.parametros_adicionais) IN ({$pd})";
        }

        $sqlPesquisa = "
            SELECT DISTINCT
                COALESCE(REPLACE((pq.meses_fcr::JSON->xx.mes::TEXT)::TEXT, '\"', '')::FLOAT, 0) AS plan_fcr,
                COALESCE((SELECT (meses_fcr::JSON->xx.mes::TEXT)::TEXT FROM tbl_planejamento_qualidade WHERE ano::INT = xx.ano AND fabrica = $login_fabrica AND planejamento_qualidade != pq.planejamento_qualidade AND ano_pd IS NULL ORDER BY revisao DESC LIMIT 1), '0') AS plan_fcr_rev,
                COALESCE(REPLACE((pq.meses_venda::JSON->xx.mes::TEXT)::TEXT, '\"', '')::FLOAT, 0) AS plan_venda,
                xx.qtde_venda,
                COALESCE(xx.qtde_os, 0) AS qtde_os,
                COALESCE(xx.fcr_os, 0) AS fcr_os,
                xx.mes,
                xx.ano
            FROM (
                SELECT
                    COALESCE(x.qtde_venda, 0) AS qtde_venda,
                    COUNT(o.os) AS qtde_os,
                    ((COUNT(o.os) * 100) / x.qtde_venda) AS fcr_os,
                    x.mes,
                    x.ano
                FROM (
                    SELECT
                        COUNT(ns.numero_serie) AS qtde_venda,
                        DATE_PART('month', ns.data_venda) AS mes,
                        DATE_PART('year', ns.data_venda) AS ano
                    FROM tbl_numero_serie ns
                    LEFT JOIN tbl_produto prt ON prt.produto = ns.produto AND prt.fabrica_i = $login_fabrica
                    WHERE ns.fabrica = $login_fabrica
                    AND ns.data_venda BETWEEN '$aux_data_inicial' AND '$aux_data_final'
                    AND ns.data_venda IS NOT NULL
                    GROUP BY mes, ano
                ) x
                LEFT JOIN tbl_os o ON o.fabrica = $login_fabrica AND DATE_PART('year', o.data_abertura) = x.ano AND DATE_PART('month', o.data_abertura) = x.mes
                LEFT JOIN tbl_os_produto op ON op.os = o.os
                LEFT JOIN tbl_produto prt ON prt.produto = op.produto AND prt.fabrica_i = $login_fabrica
                WHERE o.excluida IS NOT TRUE
                GROUP BY x.qtde_venda, x.mes, x.ano
                ORDER BY x.ano, x.mes
            ) xx
            LEFT JOIN tbl_planejamento_qualidade pq ON pq.ano::FLOAT = xx.ano 
            AND pq.fabrica = $login_fabrica 
            AND ano_pd IS NULL 
            AND pq.revisao = 0";
        $resPesquisa = pg_query($con, $sqlPesquisa);
        $count = pg_num_rows($resPesquisa);
        $dadosPesquisa = pg_fetch_all($resPesquisa);

        if ($count > 0){
            $grafDataInicial = strtotime($aux_data_inicial);
            $grafDataFinal = strtotime($aux_data_final);
            $rangePeriodo = array();
            while ($grafDataFinal >= $grafDataInicial) {
                $rangePeriodo[] = date('m/Y',$grafDataFinal);
                $grafDataFinal = strtotime(date('Y/m/01/',$grafDataFinal).' -1 month');
            }
            $rangePeriodoJson = json_encode($rangePeriodo);
            $infQtdeVenda = array();
            $infFcr = array();

            $infPlanFcr    = array();
            $infPlanFcrRev = array();
            $infPlanVenda  = array();

            $meses_ano = array();
            $qtde_total_os = array();
            $total_mes_geral = array();
            for ($p = 0; $p < count($rangePeriodo); $p++) {
                $semDados = true;
                foreach ($dadosPesquisa as $dados) {
                    
                    $pqsQtdeVenda = $dados['qtde_venda'];
                    $pqsQtdeOs = $dados['qtde_os'];
                    $pqsFcrOs = $dados['fcr_os'];
                    
                    $pqsPlanFcr     = $dados['plan_fcr'];
                    $pqsPlanFcrRev  = $dados['plan_fcr_rev'];
                    $pqsPlanVenda   = $dados['plan_venda'];

                    $pqsMes = (strlen($dados['mes']) == 1) ? '0'.$dados['mes'] : $dados['mes'];
                    $pqsAno = $dados['ano'];
                    
                    if ($rangePeriodo[$p] == $pqsMes.'/'.$pqsAno) {
                        $infQtdeVenda[] = (int) $pqsQtdeVenda;
                        $infFcr[] = (int) $pqsFcrOs;

                        $infPlanFcr[]    = (int)$pqsPlanFcr;
                        $infPlanFcrRev[] = (int) str_replace('"', "", $pqsPlanFcrRev);
                        $infPlanVenda[]  = (int)$pqsPlanVenda;

                        $semDados = false;
                    
                        $qtde_total_os[] = $dados['qtde_os'];
                        $total_mes_geral[$pqsMes.'/'.$pqsAno] = $dados['qtde_os'];
                    }
                }

                if ($semDados === true) {
                    $infQtdeVenda[]  = 0;
                    $infFcr[]        = 0;
                    $infPlanFcr[]    = 0;
                    $infPlanFcrRev[] = 0;
                    $infPlanVenda[]  = 0;
                }
            }
            
            $qtde_total_os = array_sum($qtde_total_os);
            $infQtdeVendaJson = json_encode($infQtdeVenda);
            $infFcrJson = json_encode($infFcr);
            
            $infPlanFcrJson = json_encode($infPlanFcr);
            $infPlanFcrRevJson = json_encode($infPlanFcrRev);
            $infPlanVendaJson = json_encode($infPlanVenda);

            // Query OS Series //
            $sqlPesquisaSerie = "
                SELECT
                    COUNT(*) AS qtde_os_ns,
                    DATE_PART('year', ns.data_venda) AS ano_venda,
                    DATE_PART('month', o.data_abertura) AS mes_os,
                    DATE_PART('year', o.data_abertura) AS ano_os
                FROM tbl_os o
                JOIN tbl_os_produto op USING(os)
                JOIN tbl_numero_serie ns ON ns.serie = o.serie AND ns.produto = o.produto AND ns.fabrica = $login_fabrica
                JOIN tbl_produto prt ON prt.produto = op.produto AND prt.fabrica_i = $login_fabrica
                WHERE o.fabrica = $login_fabrica
                AND o.data_abertura BETWEEN '{$aux_data_inicial}' AND '{$aux_data_final}'
                AND o.excluida IS NOT TRUE
                AND ns.data_venda IS NOT NULL
                {$whereStatus}
                {$whereOrigem}
                {$whereLinhaProducao}
                {$whereFamiliaSap}
                {$wherePd}
                GROUP BY ano_venda, mes_os, ano_os
                ORDER BY ano_os, mes_os, ano_venda";
            $resPesquisaSerie = pg_query($con, $sqlPesquisaSerie);
            $countSerie = pg_num_rows($resPesquisaSerie);
            $dadosPesquisaSerie = pg_fetch_all($resPesquisaSerie);
            
            if ($countSerie > 0){
                foreach ($dadosPesquisaSerie as $key => $value) {
                    $mes = (strlen($value['mes_os']) == 1) ? '0'.$value['mes_os'] : $value['mes_os'];
                    
                    $mes_ano_os = $mes.'/'.$value['ano_os'];
                    $ano_venda  = $value['ano_venda'];
                    $qtde_os_ns = $value['qtde_os_ns'];
                    
                    $infBarraMes[$mes_ano_os][$ano_venda] = $qtde_os_ns;
                    $infBarraMes[$mes_ano_os]["total"] += $qtde_os_ns;
                }
                
                $dados_column = array();
                foreach ($total_mes_geral as $key => $value) {
                    if (!empty($value)){
                        $sem_ns = $value - $infBarraMes[$key]["total"];
                        unset($infBarraMes[$key]["total"]);
                        $dados_column[$key] = $infBarraMes[$key];
                        $dados_column[$key]["sem_ns"] = $sem_ns;
                    }else{
                        $dados_column[$key] = 0;
                    }
                }

                $chaves = array();
                
                foreach ($dados_column as $key => $value) {
                    foreach ($value as $ano => $qtde) {
                        $chaves[] = $ano;
                    }
                }
               
                $chaves = array_unique($chaves);
                $series = array();
                
                foreach ($chaves as $ano) {
                    $data = $rangePeriodo;
                    foreach ($data as $key => $mes) {
                        switch ($ano) {
                            case 'sem_ns':
                                $color = "#c42121";
                                $legend = "#c42121";
                                break;
                            default:
                                $color = null;
                                $legend = null;
                                break;    
                        }

                        $data[$key] = array(
                            "y" => (int) $dados_column[$mes][$ano],
                            "color" => $color,
                            "legendColor" => $legend
                        );
                    }
                    $series[] = array(
                        'name' => $ano,
                        'color' => $legend,
                        'type' => 'column',
                        'data' => $data
                    );
                }
                
                $spline =  array();
                $spline[] = array(
                    "name" => "UNIT",
                    "yAxis" => 1,
                    "type" => "areaspline",
                    "data" => $infQtdeVenda
                );
                $series_grafico = array_merge($spline, $series);
                $series_grafico = json_encode($series_grafico);
            }   
        }
    }
}

$layout_menu = "gerencia";
$title = "FCR X VENDA";

include "cabecalho_new.php";

$plugins = array(
   "select2",
   "highcharts",
   "shadowbox",
   "dataTable",
   "mask",
   "datepicker"
);

include "plugin_loader.php";

?>

<style>

#modal-os-filter {
    width: 80%;
    margin-left:-40%;
}

</style>

<?php if (count($msg_erro["msg"]) > 0) { ?>
    <div class="alert alert-danger">
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php } ?>

<div class="tc_formulario">
    <form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario'>
        <div class='titulo_tabela '>Parâmetros de Pesquisa</div>
        <br />
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span4'>
                <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='data_inicial'>Data Inicial</label>
                    <div class='controls controls-row'>
                        <div class='span6'>
                            <h5 class='asteristico'>*</h5>
                            <input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?= $data_inicial; ?>">
                        </div>
                    </div>
                </div>
            </div>
            <div class='span4'>
                <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='data_final'>Data Final</label>
                    <div class='controls controls-row'>
                        <div class='span6'>
                            <h5 class='asteristico'>*</h5>
                            <input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?= $data_final; ?>" >
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class="span4">
                <div class='control-group'>
                    <label class='control-label' for='linha_producao'>Linha de Produto</label>
                    <div class='controls controls-row'>
                        <?
                        $sqlLPrd = "SELECT DISTINCT nome_comercial FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND ativo IS TRUE AND TRIM(nome_comercial) IS NOT NULL ORDER BY nome_comercial;";
                        $resLPrd = pg_query($con, $sqlLPrd); ?>
                        <select name="linha_producao[]" id="linha_producao" multiple="multiple" class='span12'>
                            <? if (is_array($_REQUEST["linha_producao"])) {
                                $selected_linha_producao = $_REQUEST['linha_producao'];
                            }
                            foreach (pg_fetch_all($resLPrd) as $key) { ?>
                                <option value="<?= $key['nome_comercial']?>" <?= (in_array($key['nome_comercial'], $selected_linha_producao)) ? "selected" : ""; ?>>
                                    <?= $key['nome_comercial']; ?>
                                </option>
                            <? } ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="span4">
                <div class='control-group'>
                    <label class='control-label' for='status'>Status</label>
                    <div class='controls controls-row'>
                        <?
                        $sql =  "SELECT * FROM tbl_status_checkpoint WHERE status_checkpoint IN(1,2,3,4,9,8,14,0,30);";
                        $res = pg_query($con,$sql); ?>
                        <select name="status[]" id="status" multiple="multiple" class='span12'>
                            <? $selected_linha = explode(",", $status);
                            foreach (pg_fetch_all($res) as $key) { ?>
                                <option value="<?= $key['status_checkpoint']?>" <?= (in_array($key['status_checkpoint'], $selected_linha)) ? "selected" : ""; ?>>
                                    <?= $key['descricao']; ?>
                                </option>
                            <? } ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class="span4">
                <div class='control-group'>
                    <label class='control-label' for='origem'>Origem</label>
                    <div class='controls controls-row'>
                        <?
                        $sql = "SELECT DISTINCT origem FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND ativo IS TRUE AND TRIM(origem) IS NOT NULL ORDER BY origem;";
                        $res = pg_query($con,$sql); ?>
                        <select name="origem[]" id="origem" multiple="multiple" class='span12'>
                            <? $selected_origem = $_REQUEST['origem'];
                            foreach (pg_fetch_all($res) as $key) { ?>
                                <option value="<?= $key['origem']?>" <?= (in_array($key['origem'], $selected_origem)) ? "selected" : ""; ?>>
                                    <?= $deParaOrigem[$key['origem']]; ?>
                                </option>
                            <? } ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="span4">
                <div class='control-group'>
                    <label class='control-label' for='origem'>Desc. Família</label>
                    <div class='controls controls-row'>
                        <?
                        $sql = "SELECT DISTINCT JSON_FIELD('familia_desc', parametros_adicionais) AS familia_sap FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND ativo IS TRUE AND parametros_adicionais like '%familia_desc%' ORDER BY familia_sap;";
                        $res = pg_query($con,$sql); ?>
                        <select name="familia_sap[]" id="familia_sap" multiple="multiple" class='span12'>
                            <? $selected_origem = $_REQUEST['familia_sap'];
                            foreach (pg_fetch_all($res) as $key) { ?>
                                <option value="<?= $key['familia_sap']?>" <?= (in_array($key['familia_sap'], $selected_origem)) ? "selected" : ""; ?>>
                                    <?= $key['familia_sap']; ?>
                                </option>
                            <? } ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class="span4">
                <div class='control-group'>
                    <label class='control-label' for='origem'>Product Division</label>
                    <div class='controls controls-row'>
                        <?
                        $sql = "SELECT DISTINCT JSON_FIELD('pd', parametros_adicionais) AS pd FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND ativo IS TRUE AND parametros_adicionais like '%pd%' ORDER BY pd;";
                        $res = pg_query($con,$sql); ?>
                        <select name="pd[]" id="pd" multiple="multiple" class='span12'>
                            <? $selected_origem = $_REQUEST['pd'];
                            foreach (pg_fetch_all($res) as $key) { ?>
                                <option value="<?= $key['pd']?>" <?= (in_array($key['pd'], $selected_origem)) ? "selected" : ""; ?>>
                                    <?= $key['pd']; ?>
                                </option>
                            <? } ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>
        <div class="row-fluid">
            <p class="tac">
                <button class='btn' id="btn_acao" type="button" onclick="submitForm($(this).parents('form'));">Pesquisar</button>
                <input type='hidden' id="btn_click" name='btn_acao' value='' />
            </p>
        </div>
    </form>
</div>
<?php 
    $arrDt = explode('-',$aux_data_inicial);
    $anoAtual = empty($aux_data_inicial) ? date('Y') : $arrDt[0];

     if (!empty($pd)) {
        $wherePdBp = "AND tbl_produto.parametros_adicionais::jsonb->>'pd' IN ({$pd})";
    }

    $sqlBp = "  WITH dados_planejamento AS (
                    SELECT json_data.key as pd,
                           json_data.value::int as total_pd
                    FROM tbl_planejamento_qualidade,
                         json_each_text(ano_pd::json) as json_data
                    WHERE ano = '{$anoAtual}'
                    AND fabrica = {$login_fabrica}
                    AND revisao = (
                        SELECT MAX(revisao) 
                        FROM tbl_planejamento_qualidade 
                        WHERE ano = '{$anoAtual}'
                        AND fabrica = {$login_fabrica}
                        AND meses_fcr IS NULL
                        AND meses_venda IS NULL
                        AND meses_os IS NULL
                    )
                    AND meses_fcr IS NULL
                    AND meses_venda IS NULL
                    AND meses_os IS NULL
                )
                SELECT COUNT(DISTINCT tbl_os.os) as total_os,
                       dados_planejamento.pd,
                       dados_planejamento.total_pd,
                       COUNT(DISTINCT tbl_os.os) - dados_planejamento.total_pd as dif_planejamento,
                       ((COUNT(DISTINCT tbl_os.os) - dados_planejamento.total_pd) * 100) / dados_planejamento.total_pd as porcentagem_dif_planejamento
                FROM tbl_produto
                JOIN dados_planejamento ON dados_planejamento.pd = tbl_produto.parametros_adicionais::jsonb->>'pd'
                JOIN tbl_os ON tbl_os.produto = tbl_produto.produto
                AND tbl_os.fabrica = {$login_fabrica}
                WHERE fabrica_i = {$login_fabrica}
                {$wherePdBp}
                GROUP BY dados_planejamento.pd,
                         dados_planejamento.total_pd";
    $resBp = pg_query($con, $sqlBp);

    if (pg_num_rows($resBp) > 0) {
    ?>
        <div class="alert alert-info"><h5>A tabela abaixo busca os dados de acordo com o ano inserido na data inicial.</h5></div>
        <table class="table table-bordered" style="width: 100%;">
            <tr class="titulo_tabela">
                <th colspan="100%">Dados gerais do ano de <?= $anoAtual ?></th>
            </tr>
            <tr class="titulo_coluna">
                <th>Product Division (PD)</th>
                <th>BP <?= $anoAtual ?> YTD</th>
                <th>Escapes <?= $anoAtual ?> YTD</th>
                <th colspan="2">Escape out of BP YTD</th>
            </tr>
            <?php
            while ($dadosBp = pg_fetch_object($resBp)) {
                ?>
                <tr>
                    <td><?= $dadosBp->pd ?></td>
                    <td class="tac"><?= $dadosBp->total_pd ?></td>
                    <td class="tac"><?= $dadosBp->total_os ?></td>
                    <td class="tac"><?= $dadosBp->porcentagem_dif_planejamento ?>%</td>
                    <td class="tac"><?= $dadosBp->dif_planejamento ?></td>
                </tr>
            <?php
            } ?>
        </table>
    <?php
    }
        ?>

    <?php
    if (!empty($btn_acao)) { 
    ?>
    <div id="grafico_fcr_x_vendas" ></div>

    <br/></br>
    <div id="vendas_escapes"></div>
<?php }

$plugins = array(
   "select2",
   "highcharts",
   "shadowbox",
   "dataTable",
   "mask",
   "datepicker"
);

include __DIR__."/admin/plugin_loader.php";
?>

<script>
Shadowbox.init();
$("select").select2();
$.datepickerLoad(["data_inicial", "data_final"]);

<?php if (!empty($btn_acao) && $count > 0) { ?>

    var aux_data_inicial = '<?=$aux_data_inicial?>';
    var aux_data_final = '<?=$aux_data_final?>';


    var status          = "<?=$status?>";
    var origem          = "<?=$origem?>";
    var linha_producao  = "<?=$linha_producao?>";
    var familia_sap     = "<?=$familia_sap?>";
    var pd              = "<?=$pd?>";

    if ($("#grafico_fcr_x_vendas").length > 0) {
        $("#grafico_fcr_x_vendas").highcharts({
            chart: {
                zoomType: 'xy'
            },
            title: {
                text: "FCR X Vendas, <?=$interval_label?>"
            },
            xAxis: {
                categories: <?= $rangePeriodoJson; ?>,
                crosshair: true
            },
            yAxis: [{
                labels: {
                    format: '{value} un',
                    style: {
                        color: Highcharts.getOptions().colors[1]
                    }
                },
                title: {
                    text: 'Vendas',
                    style: {
                        color: Highcharts.getOptions().colors[1]
                    }
                }
            }, {
                labels: {
                    format: '{value} %',
                    style: {
                        color: Highcharts.getOptions().colors[0]
                    }
                },
                title: {
                    text: 'FCR',
                    style: {
                        color: Highcharts.getOptions().colors[0]
                    }
                },
                opposite: true
            }],
            tooltip: {
                shared: true
            },
            legend: {
                layout: 'vertical',
                align: 'left',
                x: 120,
                verticalAlign: 'top',
                y: 100,
                floating: true,
                backgroundColor: (Highcharts.theme && Highcharts.theme.legendBackgroundColor) || '#FFFFFF'
            },
            plotOptions: {
                area: {
                    name: 'Sales FCST',
                    fillOpacity: 0.5
                }
            },
            series: [
            {
                name: 'Vendas',
                type: 'area',
                data: <?= $infQtdeVendaJson; ?>,
                tooltip: {
                    valueSuffix: ' un'
                }
            },
            {
                name: 'Sales FCST',
                type: 'area',
                data: <?= $infPlanVendaJson; ?>,
                tooltip: {
                    valueSuffix: ' un'
                }
            }
            , {
                name: 'FCR',
                type: 'spline',
                yAxis: 1,
                data: <?= $infFcrJson; ?>,
                tooltip: {
                    valueSuffix: '%'
                }
            }, {
                name: 'FCR BP ',
                type: 'spline',
                yAxis: 1,
                data: <?= $infPlanFcrJson; ?>,
                marker: {
                    enabled: false
                },
                dashStyle: 'shortdot',
                tooltip: {
                    valueSuffix: '%'
                }
            }
            , {
                name: 'FCR FCST 03',
                type: 'spline',
                yAxis: 1,
                data: <?= $infPlanFcrRevJson; ?>,
                marker: {
                    enabled: false
                },
                dashStyle: 'shortdot',
                tooltip: {
                    valueSuffix: '%'
                }
            }
            ]
        });
    }

    $("#vendas_escapes").highcharts({
        chart: {
            zoomType: 'xy'
        },
        title: {
            text: "Vendas & Escapes Count"
        },
        xAxis: {
            maxPadding: 0,
            type: 'category',
            categories: <?= $rangePeriodoJson; ?>,
            labels: {
                align: 'right',
                //align: 'center',
                reserveSpace: true,
                rotation: 270
            },
            lineWidth: 0,
            margin: 0,
            //tickWidth: 1,
            crosshair: true
        },
        yAxis: [
            {
                // allowDecimals: false,
                // max: <?=$qtde_total_os?>,
                // min: 0,
                labels: {
                    format: '{value} OS',
                    style: {
                        color: Highcharts.getOptions().colors[1]
                    }
                },
                title: {
                    text: 'ESCAPES',
                    style: {
                        color: Highcharts.getOptions().colors[1]
                    }
                }
            },
            {
                labels: {
                    format: '{value} un',
                    style: {
                        color: Highcharts.getOptions().colors[0]
                    }
                },
                title: {
                    text: 'UNIT',
                    style: {
                        color: Highcharts.getOptions().colors[0]
                    }
                },
                opposite: true
            }
        ],
        plotOptions: {
            series: {
                stacking: 'normal',
                cursor: "pointer",
                point: {
                    events: {
                        click: function (event) {
                            var categoria   = this.category;
                            var legenda = this.series.name;
                            var url = "vendas_escapes_os.php?categoria="+categoria+"&legenda="+legenda+"&data_inicial="+aux_data_inicial+"&data_final="+aux_data_final+"&status="+status+"&origem="+origem+"&linha_producao="+linha_producao+"&familia_sap="+familia_sap+"&pd="+pd                            
                            Shadowbox.open({
                                content:url,
                                player: "iframe",
                                title:  "Vendas & Escapes Count",
                                width:  1000,
                                height: 600
                            });
                        }
                    }
                }
            }
        },
        series: <?=$series_grafico?>,
        tooltip: {
            pointFormat: '{series.name}: <b>{point.y}</b> ({point.percentage:.1f}%)<br/>'
        }
    });

<?php } ?>
</script>
<br/><br/><br/>
<?php
include "rodape.php";
?>
