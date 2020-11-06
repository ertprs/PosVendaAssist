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

if ($_POST["btn_acao"] == "submit") {
    $ano        = $_REQUEST["ano"];
    $mes        = $_REQUEST["mes"];
    $linha      = $_REQUEST["linha_producao"];
    $origem     = $_REQUEST["origem"];
    $familia    = $_REQUEST["familia"];

    if (!empty($mes) AND empty($ano)){
        $msg_erro["msg"][] = "Selecione o ano para pesquisa";
        $msg_erro["campos"][] = "ano";
    }
    
    if (count($msg_erro['msg']) == 0) {
        if (is_array($ano)){
            $aux_ano = array_map(function($e){
                return "'{$e}'";
            }, $ano);
            $aux_ano = implode(",", $aux_ano);
            $cond_ano = " AND DATE_PART('year', o.data_abertura) IN ($aux_ano) ";
        }

        if (is_array($mes)){
            $aux_mes = array_map(function($e){
                return "'{$e}'";
            }, $mes);
            $aux_mes = implode(",", $aux_mes);
            $cond_mes = " AND DATE_PART('month', o.data_abertura) IN ($aux_mes) ";
        }

        if (is_array($linha)){
            $aux_linha = array_map(function($e){
                return "'{$e}'";
            }, $linha);

            $aux_linha = implode(",", $aux_linha);
            $cond_linha = " AND p.nome_comercial IN ({$aux_linha}) ";
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
            mes,
            ano,
            familia_descricao
        FROM(
                SELECT
                    ROW_NUMBER() OVER (PARTITION BY familia_descricao ORDER BY qtde_os DESC) AS r,
                    qtde_os,
                    mes,
                    ano,
                    familia_descricao
                FROM (
                        SELECT
                            COUNT(o.os) AS qtde_os,
                            DATE_PART('month', o.data_abertura) AS mes,
                            DATE_PART('year', o.data_abertura) AS ano,
                            f.descricao AS familia_descricao
                        FROM tbl_os o
                        JOIN tbl_os_produto op USING(os)
                        JOIN tbl_produto p ON p.produto = op.produto AND p.fabrica_i = {$login_fabrica}
                        JOIN (
                                SELECT DISTINCT
                                    COUNT(o.os) AS qtde_os,
                                    f.familia,
                                    f.descricao
                                FROM tbl_os o
                                JOIN tbl_os_produto op USING(os)
                                JOIN tbl_produto p ON p.produto = op.produto AND p.fabrica_i = {$login_fabrica}
                                JOIN tbl_familia f ON f.familia = p.familia AND f.fabrica = {$login_fabrica}
                                WHERE o.fabrica = {$login_fabrica}
                                AND o.excluida IS NOT TRUE
                                {$cond_ano}
                                {$cond_mes}
                                {$cond_origem}
                                {$cond_linha}
                                {$cond_familia}
                                GROUP BY f.familia, f.descricao
                                ORDER BY qtde_os DESC
                                LIMIT 5
                            ) f ON f.familia = p.familia
                        WHERE o.fabrica = {$login_fabrica}
                        AND o.excluida IS NOT TRUE
                        {$cond_ano}
                        {$cond_mes}
                        {$cond_origem}
                        {$cond_linha}
                        {$cond_familia}
                        GROUP BY f.descricao, mes, ano
                        ORDER BY qtde_os DESC
                    ) x
            ) xx
        WHERE r <= 5
        ORDER BY familia_descricao, mes, ano";
    $res = pg_query($con, $sql);
    if (pg_num_rows($res) > 0){

        if (!empty($mes) AND !empty($ano)){
            $pesquisa_mes_ano = "mes_ano";
            $titulo = "Month (Occurrence Year)";
        }else{
            $titulo = "YTD";
            $pesquisa_mes_ano = "ano";
        }

        $count = pg_num_rows($res);
        $result = pg_fetch_all($res);
        $dados_column = array();
        $xcategories = array();
        
        foreach ($result as $key => $value) {
            $aux_mes = (strlen($value['mes']) == 1) ? '0'.$value['mes'] : $value['mes'];
                    
            if (!empty($mes)){
                #$dados_column[$value["familia_descricao"]][$aux_mes.'/'.$value["ano"]] += $value["qtde_os"];
                $dados_column[$value["familia_descricao"]."||".$aux_mes.'/'.$value["ano"]] += $value["qtde_os"];
            }else{
                #$dados_column[$value["familia_descricao"]][$value["ano"]] += $value["qtde_os"];
                $dados_column[$value["familia_descricao"]."||".$value["ano"]] += $value["qtde_os"];
            }
            #$xcategories[] = $value["familia_descricao"];
            #$categories[]  =  utf8_encode($value["familia_descricao"]);
        }
        $chaves = array();

        arsort($dados_column);
        foreach ($dados_column as $key => $value) {
            list($familia_x, $ano_x) = explode('||', $key);
            $xdados_column[$familia_x][$ano_x] = $value;

            $xcategories[] = $familia_x;
            $categories[]  =  utf8_encode($familia_x);
        }

        $dados_column = $xdados_column;
        
        foreach ($dados_column as $key => $value) {
            foreach ($value as $x_ano => $qtde) {
                $chaves[] = $x_ano;
            }
        }

        $xcategories = array_unique($xcategories);
        $xcategories = array_values($xcategories);
        $categories  = array_unique($categories);
        $categories  = array_values($categories);

        $categories = json_encode($categories);
        $chaves = array_unique($chaves);
        $series = array();
        
        foreach ($chaves as $y_ano) {
            $data = $xcategories;
            foreach ($data as $key => $familia) {
                $data[$key] = array(
                    "y" => (int) $dados_column[$familia][$y_ano],
                    "color" => $color,
                    "legendColor" => $legend
                );
            }
            $series[] = array(
                'name' => $y_ano,
                'color' => $legend,
                'type' => 'column',
                'data' => $data
            );
        }
        $series = json_encode($series);
    }
}

$layout_menu = "gerencia";
$title = "TOP FIVE";

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

<div class="tc_formulario" >
    <form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
        <div class='titulo_tabela '>Parâmetros de Pesquisa</div>
        <br />
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span4'>
                <div class='control-group <?=(in_array("ano", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='ano'>Ano:</label>
                    <div class='controls controls-row'>
                        <h5 class='asteristico'>*</h5>
                        <select id="ano" name="ano[]" multiple="multiple" class="span10">
                            <option value=""></option>
                            <?
                            $sqlAno = "SELECT DATE_PART('year', MIN(data_digitacao)) FROM tbl_os WHERE fabrica = $login_fabrica;";
                            $resAno = pg_query($con, $sqlAno);
                            $anoInicial = pg_fetch_result($resAno, 0, 0);
                            $anoAtual = date('Y');
                            if ($anoInicial < $anoAtual) {
                                for ($i = $anoAtual; $i >= $anoInicial; $i--){
                                    $selected = (in_array($i, $ano)) ? "selected" : ""; ?>
                                    <option value='<?= $i; ?>' <?= $selected; ?>><?= $i; ?></option>
                                <? }
                            } else {
                                $selected = ($ano == $anoAtual) ? "selected" : ""; ?>
                                <option value="<?= $anoAtual; ?>" <?= $selected; ?>><?= $anoAtual; ?></option>
                            <? } ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class='span4'>
                <div class='control-group <?=(in_array("mes", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='mes'>Mês:</label>
                    <div class='controls controls-row'>
                        <h5 class='asteristico'>*</h5>
                        <select id="mes" name="mes[]" multiple="multiple" class="span10">
                            <option value=""></option>
                            <? for ($i = 1; $i <= 12; $i++){
                                $meses = array('Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro');
                                $mesCombo = ($i < 10) ? "0".$i : $i;
                                $selected = (in_array($mesCombo, $mes)) ? "selected" : ""; ?>
                                <option value='<?= $mesCombo; ?>' <?= $selected; ?>><?= $meses[$i - 1]; ?></option>
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
                    <label class='control-label' for='linha_producao'>Linha de Produto</label>
                    <div class='controls controls-row'>
                        <?php
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
<div id="grafico_ecfm_ytd"></div>

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

<?php if ($count > 0) { ?>

    var linha            = "<?=$aux_linha?>";
    var origem           = "<?=$aux_origem?>";
    var titulo           = "Escapes Count Failure Mode - <?=$titulo?>";
    var pesquisa_mes_ano = "<?=$pesquisa_mes_ano?>";
    
    Highcharts.chart('grafico_ecfm_ytd', {
        title: {
            text: titulo
        },
        xAxis: {
            categories: <?=$categories?>
        },
        yAxis: {
            labels: {
                format: '{value} OS',
                style: {
                    color: Highcharts.getOptions().colors[1]
                }
            },
            title: {
                text: 'Qtde Itens',
                style: {
                    color: Highcharts.getOptions().colors[1]
                }
            }
        },
        plotOptions: {
            series: {
                //stacking: 'normal',
                cursor: "pointer",
                point: {
                    events: {
                        click: function (event) {
                            var familia   = this.category;
                            var mes_ano = this.series.name;
                            
                            var url = "top_five_qualidade_detalhado.php?familia="+familia+"&mes_ano="+mes_ano+"&linha="+linha+"&origem="+origem+"&pesquisa="+pesquisa_mes_ano                            
                            Shadowbox.open({
                                content:url,
                                player: "iframe",
                                title:  "Escapes Count Failure Mode",
                                width:  1000,
                                height: 600
                            });
                        }
                    }
                }
            }
        },
        series: <?=$series?>,
    });


<?php } ?>
</script>
<br/><br/><br/>
<?php
include "rodape.php";
?>
