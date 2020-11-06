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
    
    $data_inicial       = $_POST['data_inicial'];
    $data_final         = $_POST['data_final'];
    $periodo_meses      = $_POST['rolling'];
    $limite_componentes = $_POST["limite_componentes"];
    $tipo_pesquisa      = $_POST['tipo_pesquisa'];
    $familia            = $_POST["familia"];
    $produto_referencia = $_POST['produto_referencia'];
    $linha_producao     = $_POST["linha_producao"];
    $origem             = $_POST["origem"];
    $familia_sap        = $_POST["familia_sap"];
    $pd                 = $_POST["pd"];

    if (is_array($linha_producao)) {
        $linha_producao = array_map(function($e) {
            return "'{$e}'";
        } , $linha_producao);
        $linha_producao = implode(",", $linha_producao);
    }

    $cond = [];

    if (!empty($linha_producao)) {
        $cond[] = "AND tbl_produto.nome_comercial IN ({$linha_producao})";
    }

    if (is_array($origem)) {
        $origem = array_map(function($e) {
            return "'{$e}'";
        } , $origem);
        $origem = implode(",", $origem);
    }

    if (!empty($origem)) {
        $cond[] = "AND tbl_produto.origem IN ({$origem})";
    }

    if (is_array($familia_sap)) {
        $familia_sap = array_map(function($e) {
            return "'{$e}'";
        } , $familia_sap);
        $familia_sap = implode(",", $familia_sap);
    }

    if (!empty($familia_sap)) {
        $cond[] = "AND tbl_produto.parametros_adicionais::jsonb->>'familia_desc' IN ({$familia_sap})
                   AND tbl_produto.parametros_adicionais::jsonb->>'familia_desc' IS NOT NULL";
    }

    if (is_array($pd)) {
        $pd = array_map(function($e) {
            return "'{$e}'";
        } , $pd);
        $pd = implode(",", $pd);
    }


    if (!empty($pd)) {
        $cond[] = "AND tbl_produto.parametros_adicionais::jsonb->>'pd' IN ({$pd})
                   AND tbl_produto.parametros_adicionais::jsonb->>'pd' IS NOT NULL";
    }

    if (is_array($familia)) {
        $familia = array_map(function($e) {
            return "'{$e}'";
        } , $familia);
        $familia = implode(",", $familia);
    }

    if (!empty($familia)) {
        $cond[] = "AND tbl_produto.familia IN ({$familia})";
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
            $cond[] = "AND tbl_produto.produto = {$produto}";
        }
    }

    
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

}

$layout_menu = "gerencia";
$title = "Montanha de Falhas";

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
    $.datepickerLoad(["data_final","data_inicial"]);

    $("span[rel=lupa]").click(function () {
        $.lupa($(this));
    });

    var valores = $(".quebra-os").filter(function(){
        return $.trim($(this).text()) != "";
    }).map(function () {
        return parseInt($.trim($(this).text()));
    }).get();

    var maiorValor = Math.max.apply(null, valores);

    $(".quebra-os").filter(function(){
        return $.trim($(this).text()) != "";
    }).each(function(){

        var valorTd = ($.trim($(this).text()) == "") ? 0 : parseInt($.trim($(this).text()));

        var proporcaoRgb = 255 - (((valorTd * 255) / maiorValor) / 2);

        //var proporcaoRgb = (127.5 - ((valorTd * 100) / maiorValor));

        $(this).css({
            'background-color': "rgb(255, "+proporcaoRgb+", "+proporcaoRgb+")"
        });

    });

    $("tr.quebra-fcr").each(function(){
        var valoresFcr = $(this).find("td.fcr-mes").filter(function(){
            return $(this).data("total") != "";
        }).map(function () {
            return parseFloat($(this).data("total"));
        }).get();

        var maiorValorFcr = Math.max.apply(null, valoresFcr);

        $(this).find("td.fcr-mes").filter(function(){
            return $(this).data("total") != "";
        }).each(function(){

            var valorTd = ($.trim($(this).data("total")) == "") ? 0 : parseFloat($.trim($(this).data("total")));

            var proporcaoRgb = (255 - (((valorTd * 255) / maiorValorFcr) / 2)) + 40;

            if (proporcaoRgb > 255) {
                proporcaoRgb = 255;
            }

            //var proporcaoRgb = (127.5 - ((valorTd * 100) / maiorValor));

            $(this).css({
                'background-color': "rgb(255, "+proporcaoRgb+", "+proporcaoRgb+")"
            });

        });
    });

    $(".tipos-header").click(function(){
        $(this).next("div").slideToggle();
    });

});

function retorna_produto (retorno) {
    $("#produto_referencia").val(retorno.referencia);
    $("#produto_descricao").val(retorno.descricao);
}

</script>
<style>

.tipos-header {
    background-color: #53a3b9;
    color: white;
    height: 40px;
    font-family: sans-serif;
    margin-bottom: 15px;
    font-size: 17px;
    border-radius: 7px;
    text-align: center;
    padding-top: 12px;
    display: block;
    cursor: pointer;
}

.tipos-header:hover {
    background-color: #297083;
    transition: 0.25s ease-in;
}

#modal-os-filter {
    width: 80%;
    margin-left:-40%;
}

.table td {
    padding: 5px;
}
.prod_qtd, .prod_tot, .componente {
    text-align: center !important;
    background-color: #190dba;
    color: white;
    text-shadow: 1px 1px black;
    font-weight: bolder;
    font-family: sans-serif;
}
.prod_tot {
    background-color: #7c73f0;
    font-size: 14px !important;
}
.prod_qtd {
    background-color: #190dba;
    font-size: 17px !important;
}
.escape_qtd {
    background-color: #190dba;
    color: white;
    text-shadow: 1px 1px black;
    font-weight: bolder;
    padding-left: 15px !important;
    padding-right: 15px !important;
}
.tx_total {
    background-color: #190dba;
    color: white;
    text-shadow: 1px 1px black;
    font-weight: bolder;
    padding-left: 15px !important;
    padding-right: 15px !important;
}
.total_escapes {
    background-color: #7c73f0;
    color: white;
    text-shadow: 1px 1px black;
    font-weight: bolder;
    font-size: 17px !important;
    text-align: center;
    vertical-align: middle;
}
.tx_escapes {
    background-color: #7c73f0;
    color: white;
    text-shadow: 1px 1px black;
    font-weight: bolder;
    font-size: 17px !important;
    text-align: center;
    vertical-align: middle;
}
.componente {
    background-color: #190dba;
    font-size: 12px !important;
}
.total_comp {
    background-color: #7c73f0;
    color: white;
    text-shadow: 1px 1px black;
    font-weight: bolder;
    font-size: 17px !important;
    text-align: center;
    vertical-align: middle;
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
                        </div>
                    </div>
                </div>
            </div>
        </div>
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
                            <input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value= "<?= $data_final; ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class="span4" id="campo_familia">
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
            <div class='span6'>
                <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='data_inicial'><strong>Deslocar período de falha em:</strong></label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <h5 class='asteristico'>*</h5>
                            <label><input type="radio" name="rolling" value="6" <?= (empty($btn_acao) || $_POST['rolling'] == 6) ? "checked" : "" ?> /> 6 Meses </label>&nbsp;&nbsp;
                            <label><input type="radio" name="rolling" value="9" <?= ($_POST['rolling'] == 9) ? "checked" : "" ?> /> 9 Meses </label>&nbsp;&nbsp;
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
            <div class="span4">
                <div class='control-group'>
                    <label class='control-label' for='origem'>Product Division</label>
                    <div class='controls controls-row'>
                        <?
                        $sql = "SELECT DISTINCT JSON_FIELD('pd', parametros_adicionais) AS pd FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND ativo IS TRUE AND parametros_adicionais like '%pd%' ORDER BY pd;";
                        $res = pg_query($con,$sql); ?>
                        <select name="pd[]" id="pd" multiple="multiple" class='span12'>
                            <? $selected_origem = $_POST['pd'];
                            foreach (pg_fetch_all($res) as $key) { ?>
                                <option value="<?= $key['pd']?>" <?= (in_array($key['pd'], $selected_origem)) ? "selected" : ""; ?>>
                                    <?= $key['pd']; ?>
                                </option>
                            <? } ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class="span4">
                <div class='control-group'>
                    <label class='control-label' for='origem'>Desc. Família</label>
                    <div class='controls controls-row'>
                        <?
                        $sql = "SELECT DISTINCT JSON_FIELD('familia_desc', parametros_adicionais) AS familia_sap FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND ativo IS TRUE AND parametros_adicionais LIKE '%familia_desc%' ORDER BY familia_sap;";
                        $res = pg_query($con,$sql); ?>
                        <select name="familia_sap[]" id="familia_sap" multiple="multiple" class='span12'>
                            <? $selected_origem = $_POST['familia_sap'];
                            foreach (pg_fetch_all($res) as $key) { ?>
                                <option value="<?= $key['familia_sap']?>" <?= (in_array($key['familia_sap'], $selected_origem)) ? "selected" : ""; ?>>
                                    <?= $key['familia_sap']; ?>
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
                            <? $selected_origem = $_POST['origem'];
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

    $sqlRollingMonths = "SELECT data::date as inicio,
                                (data + INTERVAL '1 month -1 day')::date as fim,
                                TO_CHAR(data, 'yyyymm') as mes_ano
                         FROM generate_series(
                            ('{$aux_data_inicial}'::date - INTERVAL '12 month')::date,
                            ('{$aux_data_inicial}'::date - INTERVAL '1 month')::date,
                            INTERVAL '1 MONTH'::interval
                         ) data";
    $resRollingMonths = pg_query($con, $sqlRollingMonths);

    while ($rollingData = pg_fetch_object($resRollingMonths)) {

        $sqlTmpRolling = "SELECT ((COUNT(DISTINCT tbl_numero_serie.serie) FILTER(WHERE tbl_os.os IS NOT NULL))::float / COUNT(DISTINCT tbl_numero_serie.serie)::float) * 100 as fcr_mes
                          FROM tbl_numero_serie 
                          JOIN tbl_produto ON tbl_produto.produto = tbl_numero_serie.produto
                          AND tbl_produto.fabrica_i = {$login_fabrica}
                          LEFT JOIN tbl_os ON tbl_os.serie = tbl_numero_serie.serie
                          AND tbl_os.fabrica = {$login_fabrica}
                          AND tbl_os.produto = tbl_numero_serie.produto
                          WHERE tbl_numero_serie.fabrica = {$login_fabrica}
                          AND data_fabricacao BETWEEN '{$rollingData->inicio}' and '{$rollingData->fim}'
                         ".implode(" ", $cond);
        $resTmpRolling = pg_query($sqlTmpRolling);

        $dadosRolling[$rollingData->mes_ano] = (float) number_format(pg_fetch_result($resTmpRolling, 0, "fcr_mes"), 2);

    }

    $sqlDatas = "SELECT data::date as inicio,
                        (data + INTERVAL '1 month -1 day')::date as fim,
                        TO_CHAR(data, 'mm/yyyy') as mes_ano,
                        TO_CHAR(data, 'yyyymm') as mes_ano_int
                 FROM generate_series(
                    '{$aux_data_inicial}'::date,
                    '{$aux_data_final}'::date,
                    INTERVAL '1 MONTH'::interval
                 ) data";
    $resDatas = pg_query($con, $sqlDatas);


    $dadosQuebra = [];$arrMesAno   = [];$valoresProducaoGrafico = [];$totalMeses = 0;$dadosGeral = [];
    while ($dadosPeriodo = pg_fetch_object($resDatas)) {

        pg_query($con, "DROP TABLE IF EXISTS tmp_numero_serie_producao");

        $arrMesAno[] = $dadosPeriodo->mes_ano;

        $sqlTmp = "
                        SELECT serie,
                                data_fabricacao,
                                data_quebra,
                                (extract(year from age(data_quebra, data_fabricacao)) * 12 +
                                extract(month from age(data_quebra, data_fabricacao))) as meses_ate_quebra
                        INTO tmp_numero_serie_producao
                        FROM (
                            SELECT  serie, 
                                    data_fabricacao,
                                    (
                                        SELECT DISTINCT ON (tbl_os.os) 
                                            data_abertura
                                        FROM tbl_os
                                        WHERE tbl_os.serie = tbl_numero_serie.serie
                                        AND tbl_os.fabrica = {$login_fabrica}
                                        ORDER BY tbl_os.os ASC
                                        LIMIT 1
                                    ) as data_quebra
                             FROM tbl_numero_serie 
                             JOIN tbl_produto ON tbl_produto.produto = tbl_numero_serie.produto
                             AND tbl_produto.fabrica_i = {$login_fabrica}
                             WHERE tbl_numero_serie.fabrica = {$login_fabrica}
                             AND data_fabricacao BETWEEN '{$dadosPeriodo->inicio}' and '{$dadosPeriodo->fim}'
                             ".implode(" ", $cond)."
                        ) dados_serie;";
        pg_query($con, $sqlTmp);

        $sqlTotais = "WITH quebras AS (

                        SELECT COUNT(DISTINCT tbl_os.os) as quebras_mes
                        FROM tbl_os
                        JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
                        AND tbl_produto.fabrica_i = {$login_fabrica}
                        WHERE data_abertura BETWEEN '{$dadosPeriodo->inicio}' AND '{$dadosPeriodo->fim}'
                        AND tbl_os.fabrica = {$login_fabrica}
                        ".implode(" ", $cond)."

                      ), unidades_vendidas AS (

                        SELECT  COUNT(DISTINCT tbl_numero_serie.numero_serie) as vendas_mes,
                                COUNT(DISTINCT tbl_numero_serie.numero_serie) FILTER(WHERE tbl_os.os IS NOT NULL) as quebras_vendas
                        FROM tbl_numero_serie 
                        JOIN tbl_produto ON tbl_produto.produto = tbl_numero_serie.produto
                        AND tbl_produto.fabrica_i = {$login_fabrica}
                        LEFT JOIN tbl_os ON tbl_os.serie = tbl_numero_serie.serie
                        AND tbl_os.produto = tbl_produto.produto
                        AND tbl_os.fabrica = {$login_fabrica}
                        WHERE tbl_numero_serie.fabrica = {$login_fabrica}
                        AND data_venda BETWEEN '{$dadosPeriodo->inicio}' and '{$dadosPeriodo->fim}'
                        ".implode(" ", $cond)."

                      )
                      SELECT producao.total_producao_mes,
                             falhas.quebras_por_serie,
                             quebras.quebras_mes,
                             unidades_vendidas.vendas_mes,
                             (falhas.quebras_por_serie::float / COALESCE(producao.total_producao_mes::float, 0)) * 100 AS fcr_producao,
                             (unidades_vendidas.quebras_vendas::float / COALESCE(unidades_vendidas.vendas_mes::float, 0)) * 100 AS fcr_vendas
                      FROM quebras,
                           unidades_vendidas
                      JOIN (
                         SELECT count(1) as total_producao_mes  FROM tmp_numero_serie_producao
                      ) AS producao ON true
                      JOIN (
                        SELECT COUNT(DISTINCT tbl_os.os) as quebras_por_serie
                        FROM tbl_os
                        WHERE serie IN (SELECT serie FROM tmp_numero_serie_producao)
                        AND data_abertura > (SELECT min(data_fabricacao) FROM tmp_numero_serie_producao WHERE data_fabricacao IS NOT NULL)
                        AND fabrica = {$login_fabrica}
                      ) AS falhas ON true;";
        $resTotais = pg_query($con, $sqlTotais);

        $dadosGeral[$dadosPeriodo->mes_ano] = [
            "fcr" => (float) number_format(pg_fetch_result($resTotais, 0, 'fcr_vendas'), 2),
            "escape_count" => (int) pg_fetch_result($resTotais, 0, 'quebras_mes'),
            "unit_sold" => (int) pg_fetch_result($resTotais, 0, 'vendas_mes'),
            "fcr_production" => (float) number_format(pg_fetch_result($resTotais, 0, 'fcr_producao'), 2),
            "escape_production" => (int) pg_fetch_result($resTotais, 0, 'quebras_por_serie'),
            "production" => (int) pg_fetch_result($resTotais, 0, 'total_producao_mes'),
        ];

        $dadosRolling[$dadosPeriodo->mes_ano_int] = (float) number_format(pg_fetch_result($resTotais, 0, 'fcr_producao'), 2);

        $valoresProducaoGrafico[] = (int) pg_fetch_result($resTotais, 0, 'total_producao_mes');
        $valoresFcrGrafico2[]     = (float) number_format(pg_fetch_result($resTotais, 0, 'fcr_producao'), 2);

        $sqlPeriodoGarantia = "SELECT (
                                        SELECT COUNT(*)
                                        FROM tmp_numero_serie_producao
                                        WHERE meses_ate_quebra = mes_garantia
                                        AND data_quebra IS NOT NULL
                                      ) as total,
                                      mes_garantia
                               FROM generate_series(1,{$periodo_meses},1) AS mes_garantia";
        $resPeriodoGarantia = pg_query($con, $sqlPeriodoGarantia);

        $totalQuebrasGarantiaMes = 0;
        while ($dadosPeriodoGar = pg_fetch_object($resPeriodoGarantia)) {

            $totalQuebrasGarantiaMes += $dadosPeriodoGar->total;

            $dadosQuebra[$dadosPeriodo->mes_ano][$dadosPeriodoGar->mes_garantia] = $totalQuebrasGarantiaMes;

            $dadosQuebraGar[$dadosPeriodo->mes_ano][$dadosPeriodoGar->mes_garantia] = $dadosPeriodoGar->total;

            $totalQuebraMesGar[$dadosPeriodoGar->mes_garantia] += $dadosPeriodoGar->total;

        }

        $totalQuebrasGarantia += $totalQuebrasGarantiaMes;

        pg_query($con, "DROP TABLE IF EXISTS tmp_numero_serie_producao");

    } 

    for ($x=1;$x<=$periodo_meses;$x++) {

        $valoresQuebrasGrafico = [];
        foreach ($arrMesAno as $mes_ano) {
            $valoresQuebrasGrafico[] = (float) number_format(($dadosQuebra[$mes_ano][$x] / $dadosGeral[$mes_ano]["production"]) * 100, 2);
        }

        $arrGraficoValores[] = [
            "name" => utf8_encode("{$x}º Mês"),
            "data" => $valoresQuebrasGrafico,
            "yAxis" => 1,
            "type"  => "line",
            "zIndex" => 10,
            "tooltip" => [
                "headerFormat" => "<b>{point.x}</b><br/>",
                "pointFormat" => "{point.y}%"
            ],
        ];
    }

    krsort($dadosRolling);

    $totalRollingArr = [];
    foreach ($arrMesAno as $mes_ano) {
        
        $mesAnoInteger = explode("/", $mes_ano);
        $mesAnoInteger = (int) $mesAnoInteger[1].$mesAnoInteger[0];

        foreach ($dadosRolling as $mesAnoInt => $valor) {

            if ($mesAnoInt <= $mesAnoInteger && (count($totalRollingArr[$mes_ano]) < 12 || !isset($totalRollingArr[$mes_ano]))) {

                $totalRollingArr[$mes_ano][] = (int) $valor;

            }

        }

        $totalRollingMes[] = (float) number_format(array_sum($totalRollingArr[$mes_ano]) / count($totalRollingArr[$mes_ano]), 2);

    }

    $arrGraficoValores[] = [
        "name"  => utf8_encode("Produção"),
        "type"  => "areaspline",
        "data"  => $valoresProducaoGrafico,
        "zIndex" => 1
    ];

    $arrGraficoValores2[] = [
        "name"  => utf8_encode("Produção"),
        "type"  => "areaspline",
        "data"  => $valoresProducaoGrafico,
        "zIndex" => 1,
        "yAxis" => 1
    ];

   $arrGraficoValores2[] = [
        "name" => utf8_encode("FCR / Prod"),
        "data" => $valoresFcrGrafico2,
        "yAxis" => 2,
        "type"  => "column",
        "zIndex" => 10
    ];

    $arrGraficoValores2[] = [
        "name" => utf8_encode("12m FCR"),
        "data" => $totalRollingMes,
        "type"  => "spline",
        "zIndex" => 30
    ];

    $jsonGraficoMes      = json_encode($arrMesAno);
    $jsonGraficoValores  = json_encode($arrGraficoValores);
    $jsonGraficoValores2 = json_encode($arrGraficoValores2);

    ?>
<div class="tipos-header">
            Gráfico FCR Falhas Período Garantia
        </div>
        <div id="grafico" style="min-width: 600px; height: 500px; margin: 0 auto;" hidden></div>
        <script>
            Highcharts.chart('grafico', {
            chart: {
                type: 'column',
                marginBottom: 130
            },
            title: {
                text: '<?= $_POST['data_inicial'] ?> - <?= $_POST['data_final'] ?> <?= $rolling ?> FCR By Production'
            },
            xAxis: {
                categories: <?= $jsonGraficoMes ?>
            },
            yAxis: [{
                min: 0,
                title: {
                    text: 'Total Quebras'
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
            },{ // Secondary yAxis
                title: {
                    text: '% Mês Falha'
                },
                labels: {
                    format: '{value} %'
                },
                opposite: true
            }],
            legend: {
                align: 'center',
                x: 0,
                verticalAlign: 'bottom',
                y: 0,
                floating: true,
                backgroundColor:
                    Highcharts.defaultOptions.legend.backgroundColor || 'white',
                borderColor: '#CCC',
                borderWidth: 1,
                shadow: false,
                width: 800
            },
            plotOptions: {
                column: {
                    stacking: 'normal',
                    dataLabels: {
                        enabled: true
                    }
                }
            },
            series: <?= $jsonGraficoValores ?>
        });
        </script>
        <div class="tipos-header">
            Gráfico FCR rolling data
        </div>
        <div id="grafico2" style="min-width: 600px; height: 500px; margin: 0 auto;" hidden></div>
        <script>
            Highcharts.chart('grafico2', {
            chart: {
                type: 'column',
                marginBottom: 130
            },
            title: {
                text: '<?= $_POST['data_inicial'] ?> - <?= $_POST['data_final'] ?> 12m Rolling data'
            },
            xAxis: {
                categories: <?= $jsonGraficoMes ?>
            },
            yAxis: [{
                min: 0,
                title: {
                    text: 'Produção'
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
                },
                opposite: true
            },{ // Secondary yAxis
                title: {
                    text: '% FCR by Prod.'
                },
                labels: {
                    format: '{value}'
                }
            },{ // Secondary yAxis
                title: {
                    text: '% FCR 12M'
                },
                labels: {
                    format: '{value} %'
                },
                opposite: true
            }],
            legend: {
                align: 'center',
                x: 0,
                verticalAlign: 'bottom',
                y: 0,
                floating: true,
                backgroundColor:
                    Highcharts.defaultOptions.legend.backgroundColor || 'white',
                borderColor: '#CCC',
                borderWidth: 1,
                shadow: false,
                width: 800
            },
            plotOptions: {
                column: {
                    stacking: 'normal',
                    dataLabels: {
                        enabled: true
                    }
                }
            },
            series: <?= $jsonGraficoValores2 ?>
        });
        </script>
</div>
    <table class="table table-bordered" style="min-width: 1000px;">
        <tr class="titulo_coluna">
            <th>KPI</th>
            <th colspan="2">Mês de Ocorrência</th>
            <?php
            foreach ($arrMesAno as $mes_ano) { ?>
                <th><?= $mes_ano ?></th>
            <?php
            } ?>
        </tr>
        <tr>
            <td rowspan="6" style="padding-top: 75px !important;background-color: lightgray;font-weight: bolder;text-align: center;">12M OSs FCR</td>
            <td colspan="2" style="background-color: lightgray;font-weight: bolder;">FCR Vendas</td>
            <?php
            foreach ($arrMesAno as $mes_ano) { ?>
                <td class="tac"><?= $dadosGeral[$mes_ano]["fcr"] ?>%</td>
            <?php
            } ?>
        </tr>
        <tr>
            <td colspan="2" style="background-color: lightgray;font-weight: bolder;">Total de OSs</td>
            <?php
            foreach ($arrMesAno as $mes_ano) { ?>
                <td class="tac"><?= number_format($dadosGeral[$mes_ano]["escape_count"],0,",",".") ?></td>
            <?php
            } ?>
        </tr>
        <tr>
            <td colspan="2" style="background-color: lightgray;font-weight: bolder;">Unidades Vendidas</td>
            <?php
            foreach ($arrMesAno as $mes_ano) { ?>
                <td class="tac"><?= number_format($dadosGeral[$mes_ano]["unit_sold"],0,",",".") ?></td>
            <?php
            } ?>
        </tr>
        <tr>
            <td colspan="2" style="background-color: lightgray;font-weight: bolder;">FCR Produção</td>
            <?php
            foreach ($arrMesAno as $mes_ano) { ?>
                <td class="tac"><?= $dadosGeral[$mes_ano]["fcr_production"] ?>%</td>
            <?php
            } ?>
        </tr>
        <tr>
            <td colspan="2" style="background-color: lightgray;font-weight: bolder;">OSs por Produção</td>
            <?php
            $totalEscapes = 0;
            foreach ($arrMesAno as $mes_ano) { 

                $totalEscapes += $dadosGeral[$mes_ano]["escape_production"];

                ?>
                <td class="tac"><?= number_format($dadosGeral[$mes_ano]["escape_production"],0,",",".") ?></td>
            <?php
            } ?>
        </tr>
        <tr>
            <td colspan="2" style="background-color: lightgray;font-weight: bolder;">Produção</td>
            <?php
            $totalProducao = 0;
            foreach ($arrMesAno as $mes_ano) { 

                $totalProducao += $dadosGeral[$mes_ano]["production"];

                ?>
                <td class="tac"><?= number_format($dadosGeral[$mes_ano]["production"], 0,",",".") ?></td>
            <?php
            } ?>
        </tr>
        <tr class="titulo_coluna">
            <th>Prod. Qty</th>
            <th>Tx</th>
            <th>Mês Falha</th>
            <?php
            foreach ($arrMesAno as $mes_ano) { ?>
                <th><?= $mes_ano ?></th>
            <?php
            } ?>
        </tr>
        <?php
        for ($x=1;$x<=$periodo_meses;$x++) { ?>
            <tr class="quebra-fcr">
                <?php
                if ($x == 1) { ?>
                    <td colspan="2" class="prod_qtd">Total Produção</td>
                <?php
                } else if ($x == 2) { ?>
                    <td colspan="2" class="prod_tot"><?= number_format($totalProducao, 0, ",", ".") ?></td>
                <?php
                } else if ($x == 3) { ?>
                    <td class="tac escape_qtd">Total OSs</td>
                    <td class="tac tx_total">Tx. Total</td>
                <?php
                } else if ($x == 4) {

                    $rowspanTx = ($periodo_meses - 1) - $x;

                    if ($totalProducao > 0) {
                        $txEscapes = number_format(($totalEscapes * 100) / $totalProducao, 2);
                    } else {
                        $txEscapes = 0;
                    }

                    $sqlTopComponente = "SELECT COUNT(DISTINCT tbl_os.os) as total,
                                                tbl_peca.descricao,
                                                tbl_peca.referencia
                                         FROM tbl_os
                                         JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
                                         AND tbl_produto.fabrica_i = {$login_fabrica}
                                         JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os
                                         JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
                                         AND tbl_os_item.fabrica_i = {$login_fabrica}
                                         JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca 
                                         AND tbl_peca.fabrica = {$login_fabrica}
                                         WHERE tbl_os.fabrica = {$login_fabrica}
                                         AND tbl_os.data_abertura BETWEEN '{$aux_data_inicial}' AND '{$aux_data_final}'
                                         ".implode(" ", $cond)."
                                         GROUP BY tbl_peca.descricao,
                                                  tbl_peca.referencia
                                         ORDER BY total DESC
                                         LIMIT 1
                                         ";
                    $resTopComponente = pg_query($con, $sqlTopComponente);

                    ?>
                    <td rowspan="<?= $rowspanTx ?>" class="tac total_escapes"><?= number_format($totalEscapes, 0, ",",".") ?></td>
                    <td rowspan="<?= $rowspanTx ?>" class="tac tx_escapes"><?= $txEscapes ?>%</td>
                <?php
                } else if ($x == $rowspanTx + 4) { ?>
                    <td colspan="2" class="tac componente"><?= pg_fetch_result($resTopComponente, 0, "descricao") ?></td>
                <?php
                } else if ($x == $rowspanTx + 5) { ?>
                    <td colspan="2" class="tac total_comp"><?= number_format(pg_fetch_result($resTopComponente, 0, "total"), 0, ",",".") ?></td>
                <?php
                } ?>
                <td class="tac" style="background-color: lightgray;font-weight: bolder;"><?= $x ?>º Mês</td>
                <?php
                foreach ($arrMesAno as $mes_ano) {

                    $mesAnoVerifica = explode("/",$mes_ano);

                    $mesAnoAtual    = date("Y-m")."-01";
                    $mesAnoTotal    = new DateTime($mesAnoVerifica[1]."-".$mesAnoVerifica[0]."-01");
                    $mesAnoTotal->modify('+'.$x.' month');

                    if (strtotime($mesAnoTotal->format("Y-m-d")) > strtotime($mesAnoAtual)) {
                        $corTd  = "background-color: #D9E2EF;";
                        $fcrQuebrasMes = "";
                        $fcrQuebrasMesRgb = "";
                    } else {

                        if ($dadosGeral[$mes_ano]["production"] > 0) {
                            $fcrQuebrasMes = number_format(($dadosQuebra[$mes_ano][$x] * 100) / $dadosGeral[$mes_ano]["production"], 2);
                            $fcrQuebrasMesRgb = $fcrQuebrasMes;
                            $fcrQuebrasMes .= "%";
                        } else {
                            $fcrQuebrasMes = 0;
                            $fcrQuebrasMesRgb = 0;
                        }
                        $corTd = "";
                    }
                    
                    ?>
                    <td class="tac fcr-mes" data-total="<?= $fcrQuebrasMesRgb ?>" style="<?= $corTd ?>"><?= $fcrQuebrasMes ?></td>
                <?php
                } ?>
            </tr>
        <?php
        }
        ?>
        <tr class="titulo_coluna">
            <th>Tx.</th>
            <th>Total Falhas mês</th>
            <th>Mês Falha</th>
            <?php
            foreach ($arrMesAno as $mes_ano) { ?>
                <th><?= $mes_ano ?></th>
            <?php
            } ?>
        </tr>
        <?php
        for ($x=1;$x<=$periodo_meses;$x++) { ?>
            <tr>
                <?php
                $count = 1;
                foreach ($arrMesAno as $mes_ano) {

                    $mesAnoVerifica = explode("/",$mes_ano);

                    $mesAnoAtual    = date("Y-m")."-01";
                    $mesAnoTotal    = new DateTime($mesAnoVerifica[1]."-".$mesAnoVerifica[0]."-01");
                    $mesAnoTotal->modify('+'.$x.' month');

                    if (strtotime($mesAnoTotal->format("Y-m-d")) > strtotime($mesAnoAtual)) {
                        $corTd = "background-color: #D9E2EF;";
                        $dadosQuebraG = "";
                    } else {
                        $dadosQuebraG = $dadosQuebraGar[$mes_ano][$x];
                        $corTd = "";
                    }

                    if ($count == 1) { 

                        $fcrQuebrasMes = ($dadosQuebra[$mes_ano][$x] * 100) / $dadosGeral[$mes_ano]["production"];
                        $fcrQuebrasGar = ($totalQuebraMesGar[$x] * 100) / $totalQuebrasGarantia;

                        ?>
                        <td class="tac"><?= number_format($fcrQuebrasGar, 2) ?>%</td>
                        <td class="tac"><?= $totalQuebraMesGar[$x] ?></td>
                        <td class="tac" style="background-color: lightgray;font-weight: bolder;"><?= $x ?>º Mês</td>
                    <?php
                    } 

                    ?>
                    <td class="tac quebra-os" style="<?= $corTd ?>"><?= $dadosQuebraG ?></td>
                <?php
                    $count++;
                } ?>
            </tr>
        <?php
        }
        ?>
        </table>
<?php
} ?>
<br/><br/><br/>
<?php
include "rodape.php";
?>