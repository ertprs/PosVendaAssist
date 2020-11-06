<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios = "gerencia";
include 'autentica_admin.php';
include 'funcoes.php';

$deParaOrigem = array(
    "MNS" => "Manaus",
    "IMP" => "Importado",
    "CNS" => "Canoas"
);

$btn_acao = $_REQUEST['btn_acao'];

if (!empty($btn_acao)) {
    
    $data_final  = $_POST['data_final'];
    $periodo_meses = $_POST['rolling'];
    $linha_producao     = $_REQUEST["linha_producao"];
    $tipo               = $_POST["tipo"];

    if (is_array($linha_producao)) {
        $linha_producao = array_map(function($e) {
            return "'{$e}'";
        } , $linha_producao);
        $linha_producao = implode(",", $linha_producao);
    }

    if ($tipo == "producao") {
        $campoData = "tbl_numero_serie.data_fabricacao";
    } else {
        $campoData = "tbl_numero_serie.data_venda";
    }

    if (!empty($linha_producao)) {
        $whereLinhaProducao = "AND tbl_produto.nome_comercial IN ({$linha_producao})";
    }
    if (empty($data_final)) {
        $msg_erro["msg"]["obrigatorio"] = "Preencha os campos obrigatórios";
        $msg_erro["campos"][] = "data";
    } else {
        list($di, $mi, $yi) = explode("/", $data_final);

        if (!checkdate($mi, $di, $yi)) {
            $msg_erro["msg"][]    = "Data Inválida";
            $msg_erro["campos"][] = "data";
        } else {
            $aux_data_final = "{$yi}-{$mi}-{$di}";

            $sqlDataInicial = "SELECT '{$aux_data_final}'::date - INTERVAL '{$periodo_meses} MONTHS'";
            $resDataInicial = pg_query($con, $sqlDataInicial);

            $aux_data_inicial = explode(" ", pg_fetch_result($resDataInicial, 0, 0));
            $aux_data_inicial = $aux_data_inicial[0];
            
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
    $.datepickerLoad(["data_final"]);
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
            <div class='span8'>
                <div class='control-group <?=(in_array("tipo", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='producao_venda'>Tipo</label>
                    <div class='controls controls-row'>
                        <div class='span6'>
                            <label class="radio">
                                <input type="radio" name="tipo" value="producao" <?= ($tipo == 'producao' || empty($btn_acao)) ? "checked" : ""; ?>> Produção
                            </label>
                            <label class="radio">
                                <input type="radio" name="tipo" value="venda" <?= ($tipo == 'venda') ? "checked" : ""; ?>> Venda
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span3'>
                <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='data_final'>Data</label>
                    <div class='controls controls-row'>
                        <div class='span6'>
                            <h5 class='asteristico'>*</h5>
                            <input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value= "<?= $data_final; ?>">
                        </div>
                    </div>
                </div>
            </div>
            <div class='span7'>
                <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='data_inicial'>Deslocar período em:</label>
                    <div class='controls controls-row'>
                        <div class='span6'>
                            <h5 class='asteristico'>*</h5>
                            <label><input type="radio" name="rolling" value="6" <?= (empty($btn_acao) || $_POST['rolling'] == 6) ? "checked" : "" ?> /> 6 Meses </label>
                            <label><input type="radio" name="rolling" value="9" <?= ($_POST['rolling'] == 9) ? "checked" : "" ?> /> 9 Meses </label>
                            <label><input type="radio" name="rolling" value="12" <?= ($_POST['rolling'] == 12) ? "checked" : "" ?> /> 12 Meses </label>
                        </div>
                    </div>
                </div>
            </div>
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
<div id="production_data" style="min-width: 600px; height: 800px; margin: 0 auto"></div>
<?php 
if (!empty($btn_acao) && count($msg_erro) == 0) { 

    $sqlProduction = "SELECT COUNT(*) as total_fabricado,
                              tbl_produto.nome_comercial
                       FROM tbl_numero_serie
                       JOIN tbl_produto ON tbl_produto.produto = tbl_numero_serie.produto
                       AND tbl_produto.nome_comercial IS NOT NULL
                       WHERE {$campoData} BETWEEN '{$aux_data_inicial}' AND '{$aux_data_final}'
                       AND tbl_numero_serie.fabrica = {$login_fabrica}
                       {$whereLinhaProducao}
                       GROUP BY tbl_produto.nome_comercial
                       ORDER BY total_fabricado DESC";
    $resProduction = pg_query($con, $sqlProduction); ?>

    <table class="table table-bordered">
        <tr class="titulo_coluna">
            <th>Linha Produto</th>
            <th>Production</th>
            <th>Total OSs</th>
            <th>FCR</th>
        </tr>
        <?php
        $arrProdutos = [];
        $arrOs = [];
        $arrTotalFab = [];
        while ($dadosProduction = pg_fetch_object($resProduction)) {
            $sqlTotalOs = "SELECT COUNT(*) as total_os
                           FROM tbl_os
                           JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
                           AND tbl_produto.nome_comercial IS NOT NULL
                           WHERE tbl_produto.nome_comercial = '{$dadosProduction->nome_comercial}'
                           AND tbl_os.data_abertura BETWEEN '{$aux_data_inicial}' AND '{$aux_data_final}'
                           AND tbl_os.fabrica = {$login_fabrica}";
            $resTotalOs = pg_query($con, $sqlTotalOs);

            $arrProdutos[]  = utf8_encode($dadosProduction->nome_comercial);
            $arrOs[]        = (int) pg_fetch_result($resTotalOs, 0, 'total_os') * -1;
            $arrTotalFab[]  = (int) $dadosProduction->total_fabricado;
            $arrSpace[] = " ";
        ?>
            <tr>
                <td><?= $dadosProduction->nome_comercial ?></td>
                <td class="tac"><?= $dadosProduction->total_fabricado ?></td>
                <td class="tac"><?= pg_fetch_result($resTotalOs, 0, 'total_os') ?></td>
                <td class="tac"><?= number_format((pg_fetch_result($resTotalOs, 0, 'total_os') * 100) / $dadosProduction->total_fabricado, 2) ?>%</td>
            </tr>
        <?php
        } ?>
        </table>
        <?php
        $jsonProdutos = json_encode($arrProdutos);
        $jsonOs       = json_encode($arrOs);
        $jsonFab      = json_encode($arrTotalFab);
        $jsonSpace    = json_encode($arrSpace);

?>
<script>
    // Data gathered from http://populationpyramid.net/germany/2015/

// Age categories

Highcharts.chart('production_data', {
    chart: {
        type: 'bar'
    },
    title: {
        text: 'Total OSs x Produção'
    },
    subtitle: {
        text: ''
    },
    xAxis: [{
        categories: <?= $jsonProdutos ?>,
        reversed: false,
        labels: {
            step: 1
        }
    }, { // mirror axis on right side
        opposite: true,
        reversed: false,
        categories: <?= $jsonSpace ?>,
        linkedTo: 0,
        labels: {
            step: 1
        }
    }],
    yAxis: {
        title: {
            text: null
        },
    },

    plotOptions: {
        series: {
            stacking: 'normal'
        }
    },
    series: [{
        name: 'Total de OSs',
        data: <?= $jsonOs ?>
    }, {
        name: 'Fabricados',
        data: <?= $jsonFab ?>
    }]
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
