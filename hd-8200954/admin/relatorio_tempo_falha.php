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

    if ($tipo_pesquisa == 'linha_pd') {

        if (is_array($linha_producao)) {
            $linha_producao = array_map(function($e) {
                return "'{$e}'";
            } , $linha_producao);
            $linha_producao = implode(",", $linha_producao);
        }

        if (!empty($linha_producao)) {
            $whereLinhaProducao = "AND tbl_produto.nome_comercial IN ({$linha_producao})";
        }

    } else if ($tipo_pesquisa == 'familia') {

        if (is_array($familia)) {
            $familia = array_map(function($e) {
                return "'{$e}'";
            } , $familia);
            $familia = implode(",", $familia);
        }

        if (!empty($familia)) {
            $whereFamilia = "AND tbl_produto.familia IN ({$familia})";
        }

    } else {
        
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

    var data_inicial = '<?= $aux_data_inicial ?>';
    var data_final   = '<?= $aux_data_final ?>';
    var limite_componentes = '<?= $limite_componentes ?>';
    var rolling = '<?= $periodo_meses ?>';
    var tipo_pesquisa = '<?= $tipo_pesquisa ?>';

    $(".grafico-linha-produto").click(function(e){

        e.preventDefault();

        var chave_pesquisa = $(this).data("nome");

        Shadowbox.open({
            content: "grafico_tempo_falha.php?chave_pesquisa="+chave_pesquisa+"&data_inicial="+data_inicial+"&data_final="+data_final+"&limite_componentes="+limite_componentes+"&rolling="+rolling+"&tipo_pesquisa="+tipo_pesquisa,
            player: "iframe",
            width: 1300,
            height: 800
        });

    });

    $(".tipo_pesquisa").click(function(){

        $("#campo_linha, #campo_familia, #campo_produto").hide("fast");

        if ($(this).val() == "linha_pd") {
            $("#campo_linha").show("fast");
        } else if ($(this).val() == "familia") {
            $("#campo_familia").show("fast");
        } else {
            $("#campo_produto").show("fast");
        }

    });

    $(".tipo_pesquisa:checked").click();

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
                            <label><input class="tipo_pesquisa" type="radio" name="tipo_pesquisa" value="linha_pd" <?= (empty($btn_acao) || $_POST['tipo_pesquisa'] == "linha_pd") ? "checked" : "" ?> /> Linha Produto (PD) </label>&nbsp;&nbsp;&nbsp; 
                            <label><input class="tipo_pesquisa" type="radio" name="tipo_pesquisa" value="familia" <?= ($_POST['tipo_pesquisa'] == "familia") ? "checked" : "" ?> /> Família </label>&nbsp;&nbsp;&nbsp; 
                            <label><input class="tipo_pesquisa" type="radio" name="tipo_pesquisa" value="produto" <?= ($_POST['tipo_pesquisa'] == "produto") ? "checked" : "" ?> /> Produto </label>
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
                            <label><input type="radio" name="rolling" value="6" <?= ($_POST['rolling'] == 6) ? "checked" : "" ?> /> 6 Meses </label>&nbsp;&nbsp;
                            <label><input type="radio" name="rolling" value="9" <?= ($_POST['rolling'] == 9) ? "checked" : "" ?> /> 9 Meses </label>&nbsp;&nbsp;
                            <label><input type="radio" name="rolling" value="12" <?= (empty($btn_acao) || $_POST['rolling'] == 12) ? "checked" : "" ?> /> 12 Meses </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class="span5" id="campo_linha">
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
            <div class="span5" id="campo_familia" hidden>
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
        </div>
        <div class='row-fluid' id="campo_produto" hidden>
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

    if ($tipo_pesquisa == "linha_pd") {
        $sqlLPrd = "SELECT tbl_produto.nome_comercial,
                           COUNT(*) as total
                    FROM tbl_os
                    JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
                    AND tbl_produto.nome_comercial IS NOT NULL
                    WHERE data_abertura BETWEEN '{$aux_data_inicial}' AND '{$aux_data_final}'
                    AND tbl_os.fabrica = {$login_fabrica}
                    {$whereLinhaProducao}
                    GROUP BY tbl_produto.nome_comercial
                    ORDER BY total DESC";
        $resLPrd = pg_query($con, $sqlLPrd); ?>

        <table class="table table-bordered" style="width: 700px;">
            <tr class="titulo_tabela">
                <th>Total de OSs</th>
                <th>Linha de Produto</th>
                <th>Ações</th>
            </tr>
            <?php
            while ($dadosLp = pg_fetch_object($resLPrd)) { ?>
                <tr>
                    <td class="tac">
                        <?= $dadosLp->total ?>
                    </td>
                    <td class="tac">
                        <?= $dadosLp->nome_comercial ?>
                    </td>
                    <td class="tac">
                        <button class="grafico-linha-produto btn btn-info" data-nome="<?= $dadosLp->nome_comercial ?>">Exibir Gráfico</button>
                    </td>
                </tr>
            <?php
            }
?>
        </table>
<?php
    } else { 

        if ($tipo_pesquisa == "familia") {
            $descCampo = "tbl_familia.descricao";
            $idCampo   = "tbl_familia.familia";
        } else {
            $descCampo = "tbl_produto.referencia || ' - ' || tbl_produto.descricao as descricao";
            $idCampo   = "tbl_produto.produto";
        }

        $sqlPr = "SELECT COUNT(*) as total,
                         {$descCampo},
                         {$idCampo} as id
                    FROM tbl_os
                    JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
                    JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia
                    WHERE data_abertura BETWEEN '{$aux_data_inicial}' AND '{$aux_data_final}'
                    AND tbl_os.fabrica = {$login_fabrica}
                    {$whereFamilia}
                    {$whereProduto}
                    GROUP BY {$idCampo}
                    ORDER BY total DESC";
        $resPr = pg_query($con, $sqlPr); 

    ?>
    <table class="table table-bordered" style="width: 700px;">
        <tr class="titulo_tabela">
            <th>Total de OSs</th>
            <th><?= ($tipo_pesquisa == "familia") ? "Família" : "Produto" ?></th>
            <th>Ações</th>
        </tr>
        <?php
        while ($dadosLp = pg_fetch_object($resPr)) { ?>
            <tr>
                <td class="tac">
                    <?= $dadosLp->total ?>
                </td>
                <td class="tac">
                    <?= $dadosLp->descricao ?>
                </td>
                <td class="tac">
                    <button class="grafico-linha-produto btn btn-info" data-nome="<?= $dadosLp->id ?>">Exibir Gráfico</button>
                </td>
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
