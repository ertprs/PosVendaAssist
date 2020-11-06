<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios = "gerencia";
include 'autentica_admin.php';
include 'funcoes.php';

$btn_acao = $_REQUEST['btn_acao'];

if (!empty($btn_acao)) {
    
    $data_inicial = $_POST['data_inicial'];
    $data_final   = $_POST['data_final'];
    $pd           = $_POST['pd'];

    if (count($pd) > 0) {
        $condPd = "AND tbl_produto.parametros_adicionais::jsonb->>'pd' IN ('".implode("','", $pd)."')";
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
}

$layout_menu = "gerencia";
$title = "PERFORMANCE";

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
<script src="https://code.highcharts.com/highcharts.js"></script>
<script src="https://code.highcharts.com/modules/exporting.js"></script>
<script src="https://code.highcharts.com/modules/export-data.js"></script>
<script>
$(function(){
    $("select").select2();
    $.datepickerLoad(["data_inicial", "data_final"]);
});
</script>
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

<div class="tc_formulario" >
    <form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
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
if (!empty($btn_acao) && count($msg_erro) == 0) { 

    $sqlPd = "SELECT DISTINCT parametros_adicionais::jsonb->>'pd' as pd 
            FROM tbl_produto 
            WHERE fabrica_i = {$login_fabrica} 
            AND ativo IS TRUE 
            AND parametros_adicionais::jsonb->>'pd' IS NOT NULL
            {$condPd}
            ORDER BY pd;";
    $resPd = pg_query($con,$sqlPd);

    ?>
    <div id="performance" style="min-width: 600px; height: 400px; margin: 0 auto"></div>
    <table class="table table-bordered">
        <tr class="titulo_coluna">
            <th>Product Division</th>
            <?php
            $arrPd = [];
            while ($dadosPd = pg_fetch_object($resPd)) { 
                $arrPd[] = $dadosPd->pd;
            ?>
                <th><?= $dadosPd->pd ?></th>
            <?php
            } ?>
        </tr>
        <tr>
            <td class="tac" style="background-color: lightgray;font-weight: bolder;">Unit Sold</td>
            <?php
            $resPd = pg_query($con,$sqlPd);

            $totalVendaPd = [];
            $arrTotalVendas = [];
            while ($dadosPd = pg_fetch_object($resPd)) {

                $sqlVendas = "SELECT COUNT(*) as total
                              FROM tbl_numero_serie
                              JOIN tbl_produto ON tbl_produto.produto = tbl_numero_serie.produto
                              AND tbl_produto.parametros_adicionais::jsonb->>'pd' = '{$dadosPd->pd}'
                              AND tbl_produto.fabrica_i = {$login_fabrica}
                              WHERE tbl_numero_serie.fabrica = {$login_fabrica}
                              AND tbl_produto.parametros_adicionais::jsonb->>'pd' = '{$dadosPd->pd}'
                              AND tbl_numero_serie.data_venda BETWEEN '{$aux_data_inicial}' AND '{$aux_data_final}'";
                $resVendas = pg_query($con, $sqlVendas);

                $totalVendas = pg_fetch_result($resVendas, 0, 'total');

                $totalVendaPd[$dadosPd->pd] = $totalVendas;
                $arrTotalVendas[] = (int) $totalVendas;

            ?>
                <td class="tac"><?= $totalVendas ?></td>
            <?php
            } ?>
        </tr>
        <tr>
            <td class="tac" style="background-color: lightgray;font-weight: bolder;">Escape</td>
            <?php
            $resPd = pg_query($con,$sqlPd);

            $totalEscapePd = [];
            $arrTotalEscape = [];
            while ($dadosPd = pg_fetch_object($resPd)) {

                $sqlEscape = "SELECT COUNT(tbl_os.os) as total
                              FROM tbl_produto
                              JOIN tbl_os ON tbl_os.produto = tbl_produto.produto
                              AND tbl_os.fabrica = {$login_fabrica}
                              WHERE fabrica_i = {$login_fabrica}
                              AND tbl_os.data_abertura BETWEEN '{$aux_data_inicial}' AND '{$aux_data_final}'
                              AND tbl_produto.parametros_adicionais::jsonb->>'pd' = '{$dadosPd->pd}'";
                $resEscape = pg_query($con, $sqlEscape);

                $totalEscape = pg_fetch_result($resEscape, 0, 'total');

                $totalEscapePd[$dadosPd->pd] = $totalEscape;
                $arrTotalEscape[] = (int) $totalEscape;

            ?>
                <td class="tac"><?= $totalEscape ?></td>
            <?php
            }
            ?>
        </tr>
        <tr>
            <td class="tac" style="background-color: lightgray;font-weight: bolder;">FCR</td>
            <?php
            $resPd = pg_query($con,$sqlPd);

            while ($dadosPd = pg_fetch_object($resPd)) {

                $fcr = 0;
                if ($totalVendaPd[$dadosPd->pd] > 0) {
                    $fcr = ($totalEscapePd[$dadosPd->pd] * 100) / $totalVendaPd[$dadosPd->pd];
                }

            ?>
                <td class="tac"><?= number_format($fcr, 2) ?>%</td>
            <?php
            } 

            $listaPds     = json_encode($arrPd);
            $listaVendas  = json_encode($arrTotalVendas);
            $listaEscapes = json_encode($arrTotalEscape);

            ?>
        </tr>
    </table>
    <script>

        Highcharts.chart('performance', {
            chart: {
                type: 'column'
            },
            title: {
                text: 'Product Division x FCR Performance'
            },
            subtitle: {
                text: ''
            },
            xAxis: {
                categories: <?= $listaPds ?>,
                crosshair: true
            },
            yAxis: {
                min: 0,
                title: {
                    text: 'Total'
                }
            },
            tooltip: {
                headerFormat: '<span style="font-size:10px">{point.key}</span><table>',
                pointFormat: '<tr><td style="color:{series.color};padding:0">{series.name}: </td>' +
                    '<td style="padding:0"><b>{point.y:.0f}</b></td></tr>',
                footerFormat: '</table>',
                shared: true,
                useHTML: true
            },
            plotOptions: {
                column: {
                    pointPadding: 0.2,
                    borderWidth: 0
                }
            },
            series: [{
                name: 'Unit Sold',
                data: <?= $listaVendas ?>

            },{
                name: 'Escape',
                data: <?= $listaEscapes ?>

            }]
        });

    </script>
    <?php
    $sqlCountEscape = "SELECT tbl_produto.parametros_adicionais::jsonb->>'pd' as pd,
                              TO_CHAR(tbl_os.data_abertura, 'mm/yyyy') as mes_ano,
                              COUNT(*) as total_os
                       FROM tbl_os
                       JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
                       AND tbl_produto.parametros_adicionais::jsonb->>'pd' IS NOT NULL
                       WHERE tbl_os.fabrica = {$login_fabrica}
                       AND tbl_os.data_abertura BETWEEN '{$aux_data_inicial}' AND '{$aux_data_final}'
                       {$condPd}
                       GROUP BY mes_ano,
                                pd";
    $resCountEscape = pg_query($con, $sqlCountEscape);

    $arrCountEscape = pg_fetch_all($resCountEscape);

    $dadosEscape    = [];
    $dadosEscapeMes = [];
    $dadosEscapePd  = [];
    foreach ($arrCountEscape as $key => $val) {

        $dadosEscapeMes[] = $val["mes_ano"];
        $dadosEscapePd[]  = $val["pd"];

        $dadosEscape[$val['mes_ano']][$val['pd']] = $val['total_os'];

    } 

    $dadosEscapeMes = array_unique($dadosEscapeMes);
    $dadosEscapePd  = array_unique($dadosEscapePd);

    ?>
    <div id="escapes" style="min-width: 600px; height: 400px; margin: 0 auto"></div>
    <table class="table table-bordered" style="min-width: 700px;">
        <tr class="titulo_coluna">
            <th>Product Division</th>
        <?php
        foreach ($dadosEscapeMes as $mes) { ?>
            <th class="tac"><?= $mes ?></th>
        <?php
        } ?>
        </tr>
        <tr>
            <td class="tac" style="background-color: darkred;color: white;font-weight: bolder;">Sold</td>
        <?php

        $sqlVendasMes = "SELECT TO_CHAR(tbl_numero_serie.data_venda, 'mm/yyyy') as mes_ano,
                                COUNT(*) as total
                         FROM tbl_numero_serie
                         JOIN tbl_produto ON tbl_produto.produto = tbl_numero_serie.produto
                         AND tbl_produto.parametros_adicionais::jsonb->>'pd' IS NOT NULL
                         AND tbl_produto.fabrica_i = {$login_fabrica}
                         WHERE tbl_numero_serie.fabrica = {$login_fabrica}
                         AND tbl_produto.parametros_adicionais::jsonb->>'pd' IS NOT NULL
                         AND tbl_numero_serie.data_venda BETWEEN '{$aux_data_inicial}' AND '{$aux_data_final}'
                         {$condPd}
                         GROUP BY mes_ano
                        ";
        $resVendasMes = pg_query($con, $sqlVendasMes);

        $arrMesVendas = [];
        foreach (pg_fetch_all($resVendasMes) as $key => $val) {

            $arrMesVendas[$val['mes_ano']] = $val["total"];

        }

        $valoresSoldGrafico = [];
        foreach ($dadosEscapeMes as $mes) {

            $valoresSoldGrafico[] = (int) $arrMesVendas[$mes];

        ?>
            <td class="tac"><strong><?= (int) $arrMesVendas[$mes] ?></strong></td>
        <?php
        } ?>
        </tr>
        <?php
        $linhasGrafico = [];
        foreach ($dadosEscapePd as $pd) { ?>
        <tr>
            <td class="tac" style="background-color: lightgray;font-weight: bolder;"><?= $pd ?></td>
            <?php
            $valoresPdGrafico = [];
            foreach ($dadosEscape as $mes => $pd2) {

                $valoresPdGrafico[] = (int) $pd2[$pd];

            ?>
                <td class="tac"><?= (int) $pd2[$pd] ?></td>
            <?php
            } 

            $linhasGrafico[] = [
                "name" => $pd,
                "type" => "spline",
                "data" => $valoresPdGrafico,
                "zIndex" => 10
            ];

            ?>
        </tr>
        <?php
        }

        $linhasGrafico[] = [
            "name"  => "Sold",
            "type"  => "areaspline",
            "yAxis" => 1,
            "data"  => $valoresSoldGrafico,
            "zIndex" => 1
        ];

        $listaMeses = json_encode(array_values($dadosEscapeMes));
        $listaPds   = json_encode($linhasGrafico);

        ?>
    </table>
    <script>
        Highcharts.chart('escapes', {
            chart: {
                zoomType: 'xy'
            },
            title: {
                text: 'ESCAPE COUNT / FCR'
            },
            subtitle: {
                text: ''
            },
            xAxis: [{
                categories: <?= $listaMeses ?>,
                crosshair: true
            }],
            yAxis: [{ // Primary yAxis
                labels: {
                    format: '{value}'
                },
                title: {
                    text: 'Product Division'
                }
            }, { // Secondary yAxis
                title: {
                    text: 'Sold'
                },
                labels: {
                    format: '{value}'
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
                floating: true
            },
            series: <?= $listaPds ?>
        });
    </script>
<?php
}
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

</script>
<br/><br/><br/>
<?php
include "rodape.php";
?>
