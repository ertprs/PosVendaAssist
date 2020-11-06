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

$limit = 5;
$data_pesquisa  = date('d/m/Y', strtotime('-6 month')).' - '.date('d/m/Y');
$cond_venda     = " AND ns.data_venda BETWEEN CURRENT_DATE - INTERVAL '6 MONTHS' AND CURRENT_DATE ";
$cond_abertura  = " AND o.data_abertura BETWEEN CURRENT_DATE - INTERVAL '6 MONTHS' AND CURRENT_DATE ";

if ($_POST["btn_acao"] == "submit") {
    $linha          = $_REQUEST["linha_producao"];
    $origem         = $_REQUEST["origem"];
    $familia        = $_REQUEST["familia"];
    $data_inicial   = $_REQUEST["data_inicial"];
    $data_final     = $_REQUEST["data_final"];
    $limit          = $_REQUEST['qtde'];

    if(!empty($data_inicial) AND !empty($data_final)){
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

            $sqlX = "SELECT '$aux_data_inicial'::date + interval '12 months' >= '$aux_data_final'";
            $resSubmitX = pg_query($con,$sqlX);
            $periodo_6meses = pg_fetch_result($resSubmitX,0,0);
            if($periodo_6meses == 'f'){
                $msg_erro['msg'][] = "O intervalo entre as datas deve ser de no máximo 12 meses";
                $msg_erro["campos"][] = "data";
            }
        }
        $data_pesquisa = "$data_inicial - $data_final";
    }

    if (count($msg_erro['msg']) == 0) {
        
        if (!empty($aux_data_inicial) AND !empty($aux_data_final)){
            $cond_venda = " AND ns.data_venda BETWEEN '$aux_data_inicial' AND '$aux_data_final' ";
            $cond_abertura = " AND o.data_abertura BETWEEN '$aux_data_inicial' AND '$aux_data_final' ";
        }
       
        if (is_array($linha)){
            $aux_linha = array_map(function($e){
                $xlinha = strtoupper(retira_acentos($e));
                return "'{$xlinha}'";
            }, $linha);
            $aux_linha = implode(",", $aux_linha);
            $cond_linha = " AND UPPER(fn_retira_especiais(p.nome_comercial)) IN ({$aux_linha}) ";
        }

        if (is_array($origem)){
            $aux_origem = array_map(function($e){
                return "'{$e}'";
            }, $origem);

            $aux_origem = implode(",", $aux_origem);
            $cond_origem = " AND p.origem IN ({$aux_origem}) ";
        }

        if (is_array($familia)){
            $aux_familia = array_map(function($e){
                return "'{$e}'";
            }, $familia);

            $aux_familia = implode(",", $aux_familia);
            $cond_familia = " AND f.familia IN ({$aux_familia}) ";
        }        
    }
}

if (count($msg_erro['msg']) == 0) {
    $sql = "
        SELECT
            qtde_os,
            fcr_os,
            fa.descricao AS familia_descricao
        FROM(
            SELECT
                ROW_NUMBER() OVER (PARTITION BY familia ORDER BY qtde_os DESC) AS r,
                qtde_os,
                fcr_os,
                familia
            FROM (
                SELECT
                    COUNT(o.os) AS qtde_os,
                    (COUNT(o.os) * 100) / COALESCE(qtde_venda, 0) AS fcr_os,
                    f.familia
                FROM tbl_os o
                JOIN tbl_os_produto op USING(os)
                JOIN tbl_produto p ON p.produto = op.produto AND p.fabrica_i = {$login_fabrica}
                JOIN (
                    SELECT DISTINCT
                        COUNT(o.os) AS qtde_os,
                        fven.qtde_venda,
                        p.familia
                    FROM tbl_os o
                    JOIN tbl_os_produto op USING(os)
                    JOIN tbl_produto p ON p.produto = op.produto AND p.fabrica_i = {$login_fabrica}
                    LEFT JOIN (
                        SELECT
                            COUNT(ns.numero_serie) AS qtde_venda,
                            p.familia
                        FROM tbl_numero_serie ns
                        JOIN tbl_produto p ON p.produto = ns.produto AND p.fabrica_i = {$login_fabrica}
                        WHERE ns.fabrica = {$login_fabrica}
                        $cond_venda
                        GROUP BY p.familia
                    ) fven ON fven.familia = p.familia
                    WHERE o.fabrica = {$login_fabrica}
                    AND o.excluida IS NOT TRUE
                    $cond_abertura
                    GROUP BY p.familia, fven.qtde_venda
                    ORDER BY qtde_os DESC
                    LIMIT {$limit}
                ) f ON f.familia = p.familia
                WHERE o.fabrica = {$login_fabrica}
                AND o.excluida IS NOT TRUE
                $cond_familia
                $cond_linha
                $cond_origem
                GROUP BY f.familia, f.qtde_venda
                ORDER BY qtde_os DESC
            ) x
        ) xx
        JOIN tbl_familia fa USING(familia)
        WHERE r <= {$limit}
        ORDER BY familia_descricao ";
    $res = pg_query($con, $sql);
    
    if (pg_num_rows($res) > 0){
        $titulo = "TOP Five Products Pareto - data.";
        
        $count          = pg_num_rows($res);
        $result         = pg_fetch_all($res);
        $dados_column   = array();
        $xcategories    = array();
        $dados_os       = array();
        $dados_fcr      = array();

        foreach ($result as $key => $value) {
            $dados_column["os"][$value["familia_descricao"]] = $value["qtde_os"];
            $dados_column["fcr"][$value["familia_descricao"]] = $value["fcr_os"];
            $xcategories[] = $value["familia_descricao"];
            $categories[]  =  utf8_encode($value["familia_descricao"]);
        }
        
        foreach ($xcategories as $familia) {
            foreach ($dados_column as $key => $value) {
                if ($key == "os"){
                    $dados_os[] = (int) $value[$familia];
                }else if ($key == "fcr"){
                    $dados_fcr[] = (int) $value[$familia];
                }
            }
        }

        $categories = json_encode($categories);
        $series_os = json_encode($dados_os);
        $series_fcr = json_encode($dados_fcr);
    }
}

$layout_menu = "gerencia";
$title = "TOP FIVE - PARETO";

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
                            <div class='span4'>
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
                            <input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?=$data_final?>" >
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
            <div class='span2'></div>
        </div>
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class="span4">
                <div class='control-group'>
                    <label class='control-label' for='origem'>Desc. Família</label>
                    <div class='controls controls-row'>
                        <?
                        $sql = "SELECT familia, descricao FROM tbl_familia WHERE fabrica = {$login_fabrica}";
                        $res = pg_query($con,$sql); ?>
                        <select name="familia[]" id="familia" multiple="multiple" class='span12'>
                            <? $selected_familia = $_REQUEST['familia'];
                            foreach (pg_fetch_all($res) as $key => $value) {
                            ?>
                                <option value="<?= $value['familia']?>" <?= (in_array($value['familia'], $selected_familia)) ? "selected" : ""; ?>>
                                    <?= $value['descricao']; ?>
                                </option>
                            <? } ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="span4">
                <div class='control-group'>
                    <label class='control-label' for='qtde'>Qtde Produtos Pesquisa</label>
                    <div class='controls controls-row'>
                        <select name="qtde" id="qtde" class='span6'>
                            <? $xqtde = $_REQUEST['qtde'];
                            for ($i=5; $i < 16; $i++) { 
                            ?>
                                <option value="<?= $i ?>" <?= ($xqtde == $i) ? "selected" : ""; ?>>
                                    <?= $i ?>
                                </option>
                            <? } ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>
        <div class="row-fluid">
            <br/>
            <p class="tac">
                <button class='btn' id="btn_acao" type="button" onclick="submitForm($(this).parents('form'));">Pesquisar</button>
                <input type='hidden' id="btn_click" name='btn_acao' value='' />
            </p>
        </div>
    </form>
</div>

<div id="grafico_pareto"></div>

<?php if ($count == 0){ ?>
    <div class="container">
    <div class="alert">
        <h4>Nenhum resultado encontrado</h4>
    </div>
    </div>
<?php } ?>
<?php 
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

<?php if ($count > 0 ) { ?>
   
    Highcharts.chart('grafico_pareto', {
        chart: {
            zoomType: 'xy'
        },
        title: {
            text: 'TOP Five Products Pareto - <?=$data_pesquisa?>'
        },
        subtitle: {
            text: ''
        },
        xAxis: [{
            categories: <?=$categories?>,
            crosshair: true
        }],
        yAxis: [
        { // Primary yAxis
            labels: {
                format: '{value} Qtde',
                style: {
                    color: Highcharts.getOptions().colors[0]
                }
            },
            title: {
                text: '',
                style: {
                    color: Highcharts.getOptions().colors[0]
                }
            }
        }, { // Secondary yAxis
            title: {
                text: '',
                style: {
                    color: Highcharts.getOptions().colors[1]
                }
            },
            labels: {
                format: '{value} %',
                style: {
                    color: Highcharts.getOptions().colors[1]
                }
            },
            opposite: true
        }
        ],
        tooltip: {
            shared: true
        },
        series: [{
            name: 'Qtde',
            type: 'column',
            data: <?=$series_os?>,
            tooltip: {
                valueSuffix: 'Qtde'
            }
        },
        {
            name: 'FCR',
            type: 'spline',
            yAxis: 1,
            data: <?=$series_fcr?>,
            tooltip: {
                valueSuffix: ' %'
            }

        }]
    });
<?php } ?>
</script>
<br/><br/><br/>
<?php
include "rodape.php";
?>
