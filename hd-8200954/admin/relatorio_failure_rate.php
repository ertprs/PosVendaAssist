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
    $linha_producao     = $_POST["linha_producao"];
    $limite_componentes = $_POST["limite_componentes"];
    $tipo_pesquisa = $_POST['tipo_pesquisa'];
    $familia = $_POST["familia"];
    $produto_referencia = $_POST['produto_referencia'];

    if (is_array($linha_producao)) {
        $linha_producao = array_map(function($e) {
            return "'{$e}'";
        } , $linha_producao);
        $linha_producao = implode(",", $linha_producao);
    }

    if (!empty($linha_producao)) {
        $whereLinhaProducao = "AND tbl_produto.nome_comercial IN ({$linha_producao})";
    }


    if (is_array($familia)) {
        $familia = array_map(function($e) {
            return "'{$e}'";
        } , $familia);
        $familia = implode(",", $familia);
    }

    if (!empty($familia)) {
        $whereFamilia = "AND tbl_produto.familia IN ({$familia})";
    }

    
    if (strlen($produto_referencia) > 0){
        $sql = "SELECT produto
                FROM tbl_produto
                WHERE fabrica_i = {$login_fabrica}
                AND UPPER(referencia) = UPPER('{$produto_referencia}')
                ";
        $res = pg_query($con ,$sql);

        if (!pg_num_rows($res)) {
            $msg_erro["msg"][]    = "Produto não encontrado";
            $msg_erro["campos"][] = "produto";
        } else {
            $produto = pg_fetch_result($res, 0, "produto");
            $whereProduto = "AND tbl_produto.produto = {$produto}";
        }
    }

    if (empty($limite_componentes)) {
        $msg_erro["msg"]["obrigatorio"] = "Preencha os campos obrigatórios";
        $msg_erro["campos"][] = "limite_componentes";
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
$title = "Relatório de Falhas";

include "cabecalho_new.php";

$plugins = array(
   "select2",
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
    Shadowbox.init();
    $("select").select2();
    $.datepickerLoad(["data_final"]);

    $("span[rel=lupa]").click(function () {
        $.lupa($(this));
    });

});

function retorna_produto (retorno) {
    $("#produto_referencia").val(retorno.referencia);
    $("#produto_descricao").val(retorno.descricao);
}

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
            <div class='span10'>
                <div class='control-group <?=(in_array("tipo", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='data_inicial'><strong>Tipo Pesquisa</strong></label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <h5 class='asteristico'>*</h5>
                            <label><input class="tipo_pesquisa" type="radio" name="tipo_pesquisa" value="produtos" <?= (empty($btn_acao) || $_POST['tipo_pesquisa'] == "produtos") ? "checked" : "" ?> /> Produtos </label>&nbsp;&nbsp;&nbsp;
                            <label><input class="tipo_pesquisa" type="radio" name="tipo_pesquisa" value="pecas" <?= ($_POST['tipo_pesquisa'] == "pecas") ? "checked" : "" ?> /> Peças </label>&nbsp;&nbsp;&nbsp;
                        </div>
                    </div>
                </div>
            </div>
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
                    <label class='control-label' for='data_inicial'><strong>Deslocar período em:</strong></label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <h5 class='asteristico'>*</h5>
                            <label><input type="radio" name="rolling" value="12" <?= (empty($btn_acao) || $_POST['rolling'] == 12) ? "checked" : "" ?> /> 12 Meses </label>&nbsp;&nbsp;
                            <label><input type="radio" name="rolling" value="18" <?= ($_POST['rolling'] == 18) ? "checked" : "" ?> /> 18 Meses </label>&nbsp;&nbsp;
                            <label><input type="radio" name="rolling" value="24" <?= ($_POST['rolling'] == 24) ? "checked" : "" ?> /> 24 Meses </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span3'>
                <div class='control-group <?=(in_array("limite_componentes", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='limite_componentes'>Limite de Componentes</label>
                    <div class='controls controls-row'>
                        <div class='span6'>
                            <h5 class='asteristico'>*</h5>
                            <input type="number" name="limite_componentes" id="limite_componentes" size="12" maxlength="10" class='span12' value= "<?= (empty($_POST['limite_componentes'])) ? 5 : $_POST['limite_componentes'] ?>">
                        </div>
                    </div>
                </div>
            </div>
            <div class="span5">
                <div class='control-group'>
                    <label class='control-label' for='linha_producao'>Linha de Produto (PD)</label>
                    <div class='controls controls-row'>
                        <?
                        $sqlLPrd = "SELECT DISTINCT nome_comercial FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND ativo IS TRUE AND TRIM(nome_comercial) IS NOT NULL ORDER BY nome_comercial;";
                        $resLPrd = pg_query($con, $sqlLPrd); ?>
                        <select name="linha_producao[]" id="linha_producao" multiple="multiple" class='span10'>
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
        </div>
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class="span5" id="campo_familia">
                <div class='control-group'>
                    <label class='control-label' for='linha_producao'>Família</label>
                    <div class='controls controls-row'>
                        <?
                        $sqlLPrd = "SELECT familia, descricao 
                                    FROM tbl_familia 
                                    WHERE fabrica = {$login_fabrica} 
                                    AND ativo
                                    ORDER BY descricao
                                    ;";
                        $resLPrd = pg_query($con, $sqlLPrd); ?>
                        <select name="familia[]" id="familia" multiple="multiple" class='span10'>
                            <? if (is_array($_REQUEST["familia"])) {
                                $selected_familia = $_REQUEST['familia'];
                            }
                            foreach (pg_fetch_all($resLPrd) as $key) { ?>
                                <option value="<?= $key['familia']?>" <?= (in_array($key['familia'], $selected_familia)) ? "selected" : ""; ?>>
                                    <?= $key['descricao']; ?>
                                </option>
                            <? } ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span4'>
                <div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='produto_referencia'>Ref. Produto</label>
                    <div class='controls controls-row'>
                        <div class='span7 input-append'>
                            <input type="text" id="produto_referencia" name="produto_referencia" class='span12' maxlength="20" value="<? echo $produto_referencia ?>" >
                            <span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
                            <input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
                        </div>
                    </div>
                </div>
            </div>
            <div class='span4'>
                <div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='produto_descricao'>Descrição Produto</label>
                    <div class='controls controls-row'>
                        <div class='span12 input-append'>
                            <input type="text" id="produto_descricao" name="produto_descricao" class='span12' value="<? echo $produto_descricao ?>" >
                            <span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
                            <input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
                        </div>
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


     /*
        Por questões de desempenho, a query foi demembrada em várias partes
     */
    $sqlDatas = "SELECT data::date as inicio,
                        (data + INTERVAL '1 month -1 day')::date as fim,
                        TO_CHAR(data, 'mm/yyyy') as mes_ano
                 FROM generate_series(
                    '{$aux_data_inicial}'::date,
                    '{$aux_data_final}'::date,
                    INTERVAL '1 MONTH'::interval
                 ) data";
    $resDatas = pg_query($con, $sqlDatas);

    $listaMesesGrafico = [];
    while ($dados = pg_fetch_object($resDatas)) {
        $listaMesesGrafico[] = $dados->mes_ano;
    }

    if ($tipo_pesquisa == "pecas") {

        $sqlTopPecas = "SELECT DISTINCT top.peca
                        FROM (
                            SELECT tbl_peca.peca,
                                   COUNT(*) as total
                            FROM tbl_peca
                            JOIN tbl_os_item ON tbl_os_item.peca = tbl_peca.peca
                            AND tbl_os_item.fabrica_i = {$login_fabrica}
                            JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                            JOIN tbl_os ON tbl_os_produto.os = tbl_os.os
                            AND tbl_os.fabrica = {$login_fabrica}
                            JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
                            AND tbl_produto.fabrica_i = {$login_fabrica}
                            WHERE data_abertura BETWEEN '{$aux_data_inicial}' AND '{$aux_data_final}'
                            AND tbl_os.fabrica = {$login_fabrica}
                            AND tbl_peca.fabrica = {$login_fabrica}
                            {$whereLinhaProducao}
                            {$whereFamilia}
                            {$whereProduto}
                            GROUP BY tbl_peca.peca
                            ORDER BY total DESC
                            LIMIT {$limite_componentes}
                        ) top";
        $resTopPecas = pg_query($con, $sqlTopPecas);

        $listaPecas = [];
        $listaPecasGrafico = [];
        while ($dadosPecas = pg_fetch_object($resTopPecas)) {
            $listaPecas[] = $dadosPecas->peca;
            $listaPecasGrafico[] = [
                "name" => $dadosPecas->peca,
                "data" => []
            ];
        }

        $resDatas = pg_query($con, $sqlDatas);

        $arrDadosGeral = [];
        while ($dados = pg_fetch_object($resDatas)) {

            foreach ($listaPecas as $pecaId) {

                $sqlProducao = "SELECT COUNT(DISTINCT tbl_numero_serie.numero_serie) as total
                                FROM tbl_numero_serie
                                JOIN tbl_produto ON tbl_numero_serie.produto = tbl_produto.produto
                                AND tbl_produto.fabrica_i = {$login_fabrica}
                                JOIN tbl_lista_basica ON tbl_produto.produto = tbl_lista_basica.produto
                                AND tbl_lista_basica.fabrica = {$login_fabrica}
                                WHERE data_fabricacao BETWEEN '{$dados->inicio} 00:00:00' AND '{$dados->fim} 23:59:59'
                                {$whereLinhaProducao}
                                {$whereFamilia}
                                {$whereProduto}                                
                                AND tbl_lista_basica.peca = {$pecaId}
                                AND tbl_numero_serie.fabrica = {$login_fabrica}";
                $resProducao = pg_query($con, $sqlProducao);

                $totalProducao = (int) pg_fetch_result($resProducao, 0, 'total');

                $sqlQuebras = "SELECT COUNT(DISTINCT tbl_os.os) as total
                                FROM tbl_peca
                                JOIN tbl_os_item ON tbl_os_item.peca = tbl_peca.peca
                                AND tbl_os_item.peca = {$pecaId}
                                JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                                JOIN tbl_os ON tbl_os_produto.os = tbl_os.os
                                AND tbl_os.fabrica = {$login_fabrica}
                                JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
                                WHERE data_abertura BETWEEN '{$dados->inicio} 00:00:00' AND '{$dados->fim} 23:59:59'
                                {$whereLinhaProducao}
                                {$whereFamilia}
                                {$whereProduto}
                                AND tbl_os.fabrica = {$login_fabrica}
                                AND tbl_peca.peca = {$pecaId}
                                ORDER BY total DESC";
                $resQuebras = pg_query($con, $sqlQuebras);

                $totalQuebras = (int) pg_fetch_result($resQuebras, 0, 'total');

                $arrDadosGeral[$dados->mes_ano][$pecaId] = [
                    "producao" => $totalProducao,
                    "quebras" => $totalQuebras,
                    "porcentagem" => (float) number_format(($totalQuebras * 100) / $totalProducao, 2)
                ];

                foreach ($listaPecasGrafico as $key => $arrData) {

                    if ($pecaId == $arrData["name"]) {

                        if ($totalProducao <= $totalQuebras) {
                            $listaPecasGrafico[$key]["data"][] = 0;
                        } else {
                            $listaPecasGrafico[$key]["data"][] = (float) number_format(($totalQuebras * 100) / $totalProducao, 2);
                        }

                    }

                }

            }

        }

        foreach ($listaPecasGrafico as $key => $val) {

            $sqlDesPeca = "SELECT descricao
                           FROM tbl_peca
                           WHERE peca = ".$val['name'];
            $resDesPeca = pg_query($con, $sqlDesPeca);

            $listaPecasGrafico[$key]["name"] = utf8_encode(pg_fetch_result($resDesPeca, 0, 'descricao'));

        }

        $jsonPecasGrafico       = json_encode($listaPecasGrafico);
        $jsonMesesGrafico       = json_encode($listaMesesGrafico);

    ?>
        <div id="grafico" style="min-width: 600px; height: 500px; margin: 0 auto;"></div>
        <script>
            Highcharts.chart('grafico', {
                chart: {
                    type: 'column'
                },
                title: {
                    text: 'TOP <?= $limite_componentes ?> Peças com Falha'
                },
                xAxis: {
                    categories: <?= $jsonMesesGrafico ?>
                },
                yAxis: {
                    min: 0,
                    title: {
                        text: 'Porcentagem (%)'
                    },
                    stackLabels: {
                        enabled: true,
                        style: {
                            fontWeight: 'bold',
                            color: ( // theme
                                Highcharts.defaultOptions.title.style &&
                                Highcharts.defaultOptions.title.style.color
                            ) || 'gray'
                        }
                    }
                },
                legend: {
                    align: 'right',
                    x: -30,
                    verticalAlign: 'top',
                    y: 25,
                    floating: true,
                    backgroundColor:
                        Highcharts.defaultOptions.legend.backgroundColor || 'white',
                    borderColor: '#CCC',
                    borderWidth: 1,
                    shadow: false
                },
                tooltip: {
                    headerFormat: '<b>{point.x}</b><br/>',
                    pointFormat: '{series.name}: {point.y}%<br/>Total: {point.stackTotal}'
                },
                plotOptions: {
                    column: {
                        stacking: 'normal',
                        dataLabels: {
                            enabled: true
                        }
                    }
                },
                series: <?= $jsonPecasGrafico ?>
            });
        </script>
    </div>
        <table class="table table-bordered">
            <tr class="titulo_coluna">
                <th>Peça</th>
                <th>Produção/Falhas</th>
                <?php
                foreach ($listaMesesGrafico as $mes) { 
                ?>
                    <th><?= $mes ?></th>
                <?php
                } ?>
            </tr>
            <?php
            $resPd = pg_query($con,$sqlPd);

            $totalEscapePd = [];
            $arrTotalEscape = [];
            foreach ($listaPecas as $peca) { 

                $sqlDesPeca = "SELECT descricao
                               FROM tbl_peca
                               WHERE peca = {$peca}";
                $resDesPeca = pg_query($con, $sqlDesPeca);

            ?>
                <tr>
                    <td rowspan="2" class="tac" style="font-weight: bolder;"><?= pg_fetch_result($resDesPeca, 0, 'descricao') ?></td>
                    <td style="font-weight: bolder;">Total Produção</td>
                    <?php
                    foreach ($listaMesesGrafico as $mes) { ?>
                        <td class="tac"><?= $arrDadosGeral[$mes][$peca]["producao"] ?></td>
                    <?php
                    }
                    ?>
                </tr>
                <tr>
                    <td style="font-weight: bolder;">Total Falhas</td>
                    <?php
                    foreach ($listaMesesGrafico as $mes) { ?>
                        <td class="tac"><?= $arrDadosGeral[$mes][$peca]["quebras"] ?> (<?= $arrDadosGeral[$mes][$peca]["porcentagem"] ?>%)</td>
                    <?php
                    }
                    ?>
                </tr>
            <?php
            }
            ?>
        </table>
    <?php
    } else { 

        /*
            Por questões de desempenho, a query foi demembrada em várias partes
         */
        $sqlDatas = "SELECT data::date as inicio,
                            (data + INTERVAL '1 month -1 day')::date as fim,
                            TO_CHAR(data, 'mm/yyyy') as mes_ano
                     FROM generate_series(
                        '{$aux_data_inicial}'::date,
                        '{$aux_data_final}'::date,
                        INTERVAL '1 MONTH'::interval
                     ) data";
        $resDatas = pg_query($con, $sqlDatas);

        $listaMesesGrafico = [];
        while ($dados = pg_fetch_object($resDatas)) {
            $listaMesesGrafico[] = $dados->mes_ano;
        }

        $sqlTopProdutos = "SELECT DISTINCT top.produto
                        FROM (
                            SELECT tbl_produto.produto,
                                   COUNT(*) as total
                            FROM tbl_os
                            JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
                            AND tbl_produto.fabrica_i = {$login_fabrica}
                            WHERE data_abertura BETWEEN '{$aux_data_inicial}' AND '{$aux_data_final}'
                            {$whereLinhaProducao}
                            {$whereFamilia}
                            {$whereProduto}
                            AND tbl_os.fabrica = {$login_fabrica}
                            GROUP BY tbl_produto.produto
                            ORDER BY total DESC
                            LIMIT {$limite_componentes}
                        ) top";
        $resTopProdutos = pg_query($con, $sqlTopProdutos);

        $arrTopProd = pg_fetch_all($resTopProdutos);

        foreach ($arrTopProd as $key => $value) {

            $listaProdutos[] = $value["produto"];

            $listaProdutosGrafico[] = [
                "name" => $value["produto"],
                "data" => []
            ];
        }

        $resDatas = pg_query($con, $sqlDatas);
        while ($dados = pg_fetch_object($resDatas)) {

            foreach ($arrTopProd as $key => $value) {

                $sqlDadosSerie = "WITH series as  (
                    SELECT serie, 
                           data_fabricacao 
                    from tbl_numero_serie 
                    where fabrica = {$login_fabrica}  
                    and produto = {$value['produto']} 
                    and data_fabricacao between '{$dados->inicio}' and '{$dados->fim}'
                )
                SELECT (SELECT count(1) FROM series) as total_producao,
                       COUNT(1) as total_quebras
                FROM tbl_os
                WHERE serie IN (SELECT serie FROM series)
                AND data_abertura > (SELECT min(data_fabricacao) FROM series WHERE data_fabricacao IS NOT NULL)
                AND fabrica = {$login_fabrica}
                ";
                $resDadosSerie = pg_query($con, $sqlDadosSerie);

                $totalProducao = (int) pg_fetch_result($resDadosSerie, 0, 'total_producao');
                $totalQuebras  = (int) pg_fetch_result($resDadosSerie, 0, 'total_quebras');

                $arrDadosGeral[$dados->mes_ano][$value["produto"]] = [
                    "producao" => $totalProducao,
                    "quebras" => $totalQuebras,
                    "porcentagem" => (float) number_format(($totalQuebras * 100) / $totalProducao, 2)
                ];

                foreach ($listaProdutosGrafico as $key => $arrData) {

                    if ($value["produto"] == $arrData["name"]) {

                        if ($totalProducao <= $totalQuebras) {
                            $listaProdutosGrafico[$key]["data"][] = 0;
                        } else {
                            $listaProdutosGrafico[$key]["data"][] = (float) number_format(($totalQuebras * 100) / $totalProducao, 2);
                        }

                    }

                }

            }

        }

        foreach ($listaProdutosGrafico as $key => $val) {

            $sqlDesProd = "SELECT descricao
                           FROM tbl_produto
                           WHERE produto = ".$val['name'];
            $resDesProd = pg_query($con, $sqlDesProd);

            $listaProdutosGrafico[$key]["name"] = utf8_encode(pg_fetch_result($resDesProd, 0, 'descricao'));

        }

        $jsonProdutosGrafico = json_encode($listaProdutosGrafico);
        $jsonMesesGrafico    = json_encode($listaMesesGrafico);

    ?>
        <div id="grafico" style="min-width: 600px; height: 500px; margin: 0 auto;"></div>
        <script>
            Highcharts.chart('grafico', {
                chart: {
                    type: 'column'
                },
                title: {
                    text: 'TOP <?= $limite_componentes ?> Produtos com Falha'
                },
                xAxis: {
                    categories: <?= $jsonMesesGrafico ?>
                },
                yAxis: {
                    min: 0,
                    title: {
                        text: 'Porcentagem (%)'
                    },
                    stackLabels: {
                        enabled: true,
                        style: {
                            fontWeight: 'bold',
                            color: ( // theme
                                Highcharts.defaultOptions.title.style &&
                                Highcharts.defaultOptions.title.style.color
                            ) || 'gray'
                        }
                    }
                },
                legend: {
                    align: 'right',
                    x: -30,
                    verticalAlign: 'top',
                    y: 25,
                    floating: true,
                    backgroundColor:
                        Highcharts.defaultOptions.legend.backgroundColor || 'white',
                    borderColor: '#CCC',
                    borderWidth: 1,
                    shadow: false
                },
                tooltip: {
                    headerFormat: '<b>{point.x}%</b><br/>',
                    pointFormat: '{series.name}: {point.y}%<br/>Total: {point.stackTotal}'
                },
                plotOptions: {
                    column: {
                        stacking: 'normal',
                        dataLabels: {
                            enabled: true
                        }
                    }
                },
                series: <?= $jsonProdutosGrafico ?>
            });
        </script>
    </div>
        <table class="table table-bordered">
            <tr class="titulo_coluna">
                <th>Peça</th>
                <th>Produção/Falhas</th>
                <?php
                foreach ($listaMesesGrafico as $mes) { 
                ?>
                    <th><?= $mes ?></th>
                <?php
                } ?>
            </tr>
            <?php
            $resPd = pg_query($con,$sqlPd);

            $totalEscapePd = [];
            $arrTotalEscape = [];
            foreach ($listaProdutos as $produto) { 

                $sqlDesProd = "SELECT descricao
                               FROM tbl_produto
                               WHERE produto = {$produto}";
                $resDesProd = pg_query($con, $sqlDesProd);

            ?>
                <tr>
                    <td rowspan="2" class="tac" style="font-weight: bolder;"><?= pg_fetch_result($resDesProd, 0, 'descricao') ?></td>
                    <td style="font-weight: bolder;">Total Produção</td>
                    <?php
                    foreach ($listaMesesGrafico as $mes) { ?>
                        <td class="tac"><?= $arrDadosGeral[$mes][$produto]["producao"] ?></td>
                    <?php
                    }
                    ?>
                </tr>
                <tr>
                    <td style="font-weight: bolder;">Total Falhas</td>
                    <?php
                    foreach ($listaMesesGrafico as $mes) { ?>
                        <td class="tac"><?= $arrDadosGeral[$mes][$produto]["quebras"] ?> (<?= $arrDadosGeral[$mes][$produto]["porcentagem"] ?>%)</td>
                    <?php
                    }
                    ?>
                </tr>
            <?php
            }
            ?>
        </table>
    <?php
    }
}
?>

<br/><br/><br/>
<?php
include "rodape.php";
?>
