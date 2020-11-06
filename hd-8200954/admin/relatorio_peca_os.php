<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios = "gerencia";
include 'autentica_admin.php';
include 'funcoes.php';

$title = "Relatório de Resumo de Peças Utilizadas em OS";
$layout_menu = "gerencia";

include "cabecalho_new.php";

$array_meses = array (
    1 => "Janeiro",
    2 => "Fevereiro",
    3 => "Março",
    4 => "Abril",
    5 => "Maio",
    6 => "Junho",
    7 => "Julho",
    8 => "Agosto",
    9 => "Setembro",
   10 => "Outubro",
   11 => "Novembro",
   12 => "Dezembro"
);

use Posvenda\DistribuidorSLA;
$oDistribuidorSLA = new DistribuidorSLA();
$oDistribuidorSLA->setFabrica($login_fabrica);

if ($_POST) {
    $mes_inicio       = filter_input(INPUT_POST,"mes_inicio");
    $ano_inicio       = filter_input(INPUT_POST,"ano_inicio");
    $mes_final         = filter_input(INPUT_POST,"mes_final");
    $ano_final         = filter_input(INPUT_POST,"ano_final");
    $tipo_os            = filter_input(INPUT_POST,'consumidor_revenda_pesquisa');
    $tipo_atendimento   = filter_input(INPUT_POST,'tipo_atendimento',FILTER_UNSAFE_RAW,FILTER_REQUIRE_ARRAY);
    $unidade_negocio    = filter_input(INPUT_POST,'unidadenegocio',FILTER_UNSAFE_RAW,FILTER_REQUIRE_ARRAY);

    $tipo_atendimento = implode(",", $tipo_atendimento);

    if(empty($mes_inicio) || empty($ano_inicio) || empty($mes_final) || empty($ano_final)){
        $msg_erro["msg"][]    = "Informe o Período";
        $msg_erro["campos"][] = "mes_inicio";
        $msg_erro["campos"][] = "mes_fim";
        $msg_erro["campos"][] = "ano_inicio";
        $msg_erro["campos"][] = "ano_fim";
    } else {
        $xmes_fim = ($mes_final < 10) ? "0$mes_final" : $mes_final;
        $xmes_inicio = ($mes_inicio < 10) ? "0$mes_inicio" : $mes_inicio;

        $aux_data_inicial = "$ano_inicio-$xmes_inicio-01";

        $dia_final = date("t", mktime(0,0,0,$xmes_fim,'01',$ano_final));
        
        $aux_data_final = "$ano_final-$xmes_fim-$dia_final";

        if (empty($msg_erro["msg"])) {
            $date = new DateTime($aux_data_inicial);
            $diferenca = $date->diff(new DateTime($aux_data_final));

            if ($diferenca->invert == 1) {
                $msg_erro["msg"]["obg"] .= "Data inicial não pode ser maior que a data final";
                $msg_erro["campos"][] = "mes_inicio";
                $msg_erro["campos"][] = "mes_fim";
                $msg_erro["campos"][] = "ano_inicio";
                $msg_erro["campos"][] = "ano_fim";
            } else {
                if ($diferenca->m > 12) {
                    $msg_erro["msg"]["obg"] .= "Não será possível consultar mais de 12 meses";
                    $msg_erro["campos"][] = "mes_inicio";
                    $msg_erro["campos"][] = "mes_fim";
                    $msg_erro["campos"][] = "ano_inicio";
                    $msg_erro["campos"][] = "ano_fim";
                 }
            }
        }
    }

    if (count($unidade_negocio) > 0) {
        foreach ($unidade_negocio as $key => $value) {
            if ($value == "6101") {
                $unidade_negocios[] = "'6107'";
                $unidade_negocios[] = "'6101'";
                $unidade_negocios[] = "'6102'";
                $unidade_negocios[] = "'6103'";
                $unidade_negocios[] = "'6106'";
                $unidade_negocios[] = "'6104'";
                $unidade_negocios[] = "'6108'";
            } else {
                $unidade_negocios[] = "'$value'";
            }
        }
        $whereUnidadeNegocio = "AND JSON_FIELD('unidadeNegocio', tbl_os_campo_extra.campos_adicionais) IN (".implode(',', $unidade_negocios).")";
    }

    if (strlen($tipo_atendimento) > 0) {
        $whereTpAtendimento = " AND tbl_os.tipo_atendimento IN({$tipo_atendimento})";
        $tipo_atendimento   = explode(',', $tipo_atendimento);
    } else {
        $whereTpAtendimento = "";
    }

    if (!empty($tipo_os)) {
        $whereTipoOs = " AND tbl_os.consumidor_revenda = '$tipo_os'";
    }

    $sql = "
        SELECT
                unidadeNegocio,
                data_fechamento,
                referencia,
                descricao,
                SUM(qtde) AS qtde
        FROM    (
                    SELECT  tbl_unidade_negocio.nome AS unidadeNegocio,
                            TO_CHAR(data_fechamento,'MM/YYYY')   AS data_fechamento,
                            tbl_peca.referencia,
                            tbl_peca.descricao,
                            tbl_os_item.qtde
                    FROM    tbl_os
                    JOIN    tbl_os_produto      ON tbl_os.os = tbl_os_produto.os
                    JOIN    tbl_os_item         ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                    JOIN    tbl_os_campo_extra  ON tbl_os.os = tbl_os_campo_extra.os
                    JOIN    tbl_peca            ON tbl_os_item.peca = tbl_peca.peca
                    JOIN    tbl_unidade_negocio ON tbl_unidade_negocio.codigo = JSON_FIELD('unidadeNegocio',tbl_os_campo_extra.campos_adicionais)
                    WHERE   tbl_os.fabrica          = $login_fabrica
                    AND     tbl_os.data_fechamento  BETWEEN '$aux_data_inicial' AND '$aux_data_final'
                    $whereUnidadeNegocio
                    $whereTpAtendimento
                    $whereTipoOs
                ) AS nome        
        GROUP BY    unidadeNegocio,
                    data_fechamento,
                    referencia,
                    descricao
        ORDER BY    qtde DESC;
        ";

    //die(nl2br($sql));
    $resSubmit = pg_query($con,$sql);
}

$plugins = array(
    "select2",
    "dataTable",
    "mask"
);

include "plugin_loader.php";
?>
<script type="text/javascript">
$(function() {
    $.datepickerLoad(Array("data_final", "data_inicial"));
    $("select#unidadenegocio").select2();
    $("select#tipo_atendimento").select2();


    $("button.download-csv").on("click", function() {
        var csv = $(this).data("csv");

        window.open("xls/"+csv);
    });
});

</script>


<?php
if (count($msg_erro["msg"]) > 0) {
?>

    <div id='alertError' class="alert alert-error no-print" >
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
if (count($msg_erro["alerta"]) > 0) { ?>
    <div id='Alert' class="alert no-print" >
        <h4><?=implode("<br />", $msg_erro["alerta"])?></h4>
    </div>
<?php
}
?>

<div class="row no-print" >
    <b class="obrigatorio pull-right" >* Campos obrigatórios</b>
</div>

<form method="POST" class="form-search form-inline tc_formulario no-print" >
    <div class="titulo_tabela" >Parâmetros de Pesquisa</div>
    <br />
    <div class="row-fluid" >
        <div class="span2" ></div>
        <div class="span2" >
            <div class="control-group <?=(in_array("mes_inicio", $msg_erro["campos"])) ? "error" : ""?>" id="gDtInicial">
                <label class="control-label" for="mes_inicio">Mês Inicial</label>
                <div class="controls controls-row">
                    <div class="span12">
                        <h5 class="asteristico">*</h5>
                        <select name="mes_inicio" class='span12'>
                            <option value=''>Mês</option>
<?php
foreach ($array_meses as $mesN => $mes) {
?>
                            <option value="<?=$mesN?>"  <?=($mes_inicio == $mesN) ? "selected" : ""?>><?=$mes?></option>
<?php
}
?>                      </select>
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'>
            <div class='control-group <?=(in_array("ano_inicio", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='ano_inicio'></label>
                <div class='controls controls-row'>

                        <h5 class='asteristico'>*</h5>
                        <select name="ano_inicio" class='span8'>
                            <option value=''>Ano</option>
                            <?
                            for ($i = date("Y") ; $i >= 2003 ; $i--) {
                                echo "<option value='$i'";
                                if ($ano_inicio == $i) echo " selected";
                                echo ">$i</option>";
                            }
                                ?>
                        </select>
                </div>
            </div>
        </div>
        <div class="span2" >
            <div class="control-group <?=(in_array("mes_final", $msg_erro["campos"])) ? "error" : ""?>" id="gDtInicial">
                <label class="control-label" for="mes_final">Mês Final</label>
                <div class="controls controls-row">
                    <div class="span12">
                        <h5 class="asteristico">*</h5>
                        <select name="mes_final" class='span12'>
                            <option value=''>Mês</option>
<?php
foreach ($array_meses as $mesN => $mes) {
?>
                            <option value="<?=$mesN?>" <?=($mes_final == $mesN) ? "selected" : ""?>><?=$mes?></option>
<?php
}
?>                      </select>
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'>
            <div class='control-group <?=(in_array("ano_final", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='ano_final'></label>
                <div class='controls controls-row'>

                        <h5 class='asteristico'>*</h5>
                        <select name="ano_final" class='span8'>
                            <option value=''>Ano</option>
                            <?
                            for ($i = date("Y") ; $i >= 2003 ; $i--) {
                                echo "<option value='$i'";
                                if ($ano_final == $i) echo " selected";
                                echo ">$i</option>";
                            }
                                ?>
                        </select>
                </div>
            </div>
        </div>
        <div class="span2" ></div>
    </div>
    <div class="row-fluid" >
        <div class="span2" ></div>
        <div class="span8">
            <label class='control-label' for='tipo_atendimento'>Tipo OS</label>
            <div class='controls controls-row'>
                <select id="consumidor_revenda_pesquisa" name="consumidor_revenda_pesquisa" class='frm' style='width:95px'>
                    <option value="">Todas</option>
                    <option value="C" <?=$selected_c?>>Consumidor</option>
                    <option value="R" <?=$selected_r?>>Revenda</option>
                </select>
            </div>
        </div>
        <div class="span2" ></div>
    </div>
<?php
        $unidadenegocio = $_POST['unidadenegocio'];
?>
    <div class="row-fluid" >
        <div class="span2" ></div>
        <div class="span8" >
            <div class='control-group'>
                <label class="control-label" for="unidade_negocio" >Unidade de Negócio</label>
                <div class='controls controls-row'>
                    <select id="unidadenegocio" multiple="multiple" name="unidadenegocio[]" class="span12" >
                        <option value="" >Selecione</option>
<?php
$distribuidores_disponiveis = $oDistribuidorSLA->SelectUnidadeNegocioNotIn();
foreach ($distribuidores_disponiveis as $unidadeNegocio) {
    if (in_array($unidadeNegocio["unidade_negocio"], array(6102,6103,6104,6105,6106,6107,6108))) {
        unset($unidadeNegocio["unidade_negocio"]);
        continue;
    }
    $unidade_negocio_agrupado[$unidadeNegocio["unidade_negocio"]] = $unidadeNegocio["cidade"];
}

foreach ($unidade_negocio_agrupado as $unidade => $descricaoUnidade) {
    $selected = (in_array($unidade, $unidadenegocio)) ? 'SELECTED' : '';
?>
                        <option value='<?=$unidade?>' <?=$selected?>> <?=$descricaoUnidade?></option>
<?php
}
?>
                    </select>
                </div>
            </div>
        </div>
        <div class="span2" ></div>
    </div>
    <div class="row-fluid" >
        <div class="span2" ></div>
        <div class="span8" >
            <div class='control-group'>
                <label class='control-label' for='tipo_atendimento'>Tipo Atendimento:</label>
                <div class='controls controls-row'>
                    <select id="tipo_atendimento" name='tipo_atendimento[]' class='span12' multiple="multiple">
                        <option value=''>Selecione</option>
<?php
$sql = "SELECT tipo_atendimento,descricao FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica AND ativo;";
$res   = pg_exec($con,$sql);

for($i = 0; $i < pg_numrows($res); $i++){
    $tipo_atendimento_id = pg_result($res,$i,tipo_atendimento);
    $descricao   = pg_result($res,$i,descricao);
    $selected = (in_array($tipo_atendimento_id, $tipo_atendimento)) ? 'selected' : "";
?>
                        <option value='<?=$tipo_atendimento_id?>' <?=$selected?>><?=$descricao?></option>
<?php
}
?>
                    </select>
                </div>
            </div>
        </div>
        <div class="span2" ></div>
    </div>
    <br />
    <p class="tac" >
        <button type="submit" name="pesquisa" class="btn" >Pesquisar</button>
    </p>

    <br />
</form>

<?php
if (isset($resSubmit)) {
    if (pg_num_rows($resSubmit) > 0) {
        $csv = "relatorio-resumo-pecas-{$login_fabrica}-".date("YmdHi").".csv";
        $file = fopen("/tmp/".$csv, "w");
        $titulo = array(
            "Unidade de Negocio",
            "Mês e Ano Fechamento da OS",
            "Peca Referência",
            "Peca Descrição",
            "Qtde"
        );

        fwrite($file, $titulo);
        $linhas = implode("@", $titulo)."\r\n";
?>
<p class="tac no-print" >
    <button type="button" class="btn btn-success download-csv" data-csv="<?=$csv?>" ><i class="icon-download-alt icon-white" ></i> Download CSV</button>
</p>
<table id="callcenter_relatorio_peca" class='table table-striped table-bordered table-hover table-fixed' >
    <thead>
        <TR class='titulo_coluna'>
            <th>Unidade de Negócio</th>
            <th>Mês e Ano<br />Fechamento da OS</th>
            <th>Peça Referência</th>
            <th>Peça Descrição</th>
            <th>Qtde</th>
        </tr>
    </thead>
    <tbody>
<?php
        while ($result = pg_fetch_object($resSubmit)) {
            $linhas .= $result->unidadenegocio."@".$result->data_fechamento."@".$result->referencia . "@" . $result->descricao."@".$result->qtde."\r\n";
?>
            <tr>
                <td><?=$result->unidadenegocio?></td>
                <td><?=$result->data_fechamento?></td>
                <td><?=$result->referencia?></td>
                <td><?=$result->descricao?></td>
                <td><?=$result->qtde?></td>
            </tr>
<?php
        }
        fwrite($file, $linhas);
        fclose($file);
        system("mv /tmp/{$csv} xls/{$csv}");
?>
    </tbody>
</table>
<script type="text/javascript">
$(function(){
    $.dataTableLoad({ table: "#callcenter_relatorio_peca" });
});
</script>
<?php

    } else {
?>
<div class='container'>
    <div class='alert'>
            <h4>Nenhum resultado encontrado</h4>
    </div>
</div>
<?php
    }
}
?>

<?php
include "rodape.php";
?>
