<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios = "gerencia";
include 'autentica_admin.php';
include 'funcoes.php';
use Posvenda\DistribuidorSLA;

$title = "Indicadores de Ordens de Serviço Abertas";
$layout_menu = "gerencia";

include "cabecalho_new.php";

function retorna_unidade($unidade_negocio){
    $sqlUN = "SELECT DISTINCT tbl_unidade_negocio.nome AS cidade,
                     tbl_unidade_negocio.codigo 
                FROM tbl_distribuidor_sla
                JOIN tbl_unidade_negocio ON tbl_unidade_negocio.codigo = tbl_distribuidor_sla.unidade_negocio
                WHERE tbl_distribuidor_sla.fabrica = {$login_fabrica}";

    //echo $sqlUN;
    $resUN = pg_query($con, $sqlUN);
    $contadorUN = pg_num_rows($resUN);

    if($contadorUN > 0){
        for($y=0; $y<$contadorUN; $y++){
            $unidade_negocio[] = pg_fetch_result($resUN, $y, codigo) . ' - ' . pg_fetch_result($resUN, $y, cidade);
        }
    }    

    return $unidade_negocio;
}

if ($_POST) {
    $data_inicial    = $_POST["data_inicial"];
    $data_final      = $_POST["data_final"];
    $unidade_negocio = $_POST["unidade_negocio"];
    $tipo            = $_POST["tipo"]; // null, garantia ou fora_garantia

    if (empty($data_inicial) || empty($data_final)) {
        $msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
        $msg_erro["campos"][] = "data_inicial";
        $msg_erro["campos"][] = "data_final";
    }else{
        $data_1 = explode('/', $data_inicial);
        $data_2 = explode('/', $data_final);

        $res_1 = checkdate($data_1[1], $data_1[0], $data_1[2]);
        $res_2 = checkdate($data_2[1], $data_2[0], $data_2[2]);
        if ($res_1 != 1) {
            $msg_erro["msg"]["obg"] = "Data inicio inválida<br />";
            $msg_erro["campos"][] = "data_inicial";
        }
        if ($res_2 != 1) {
            $msg_erro["msg"]["obg"] .= "Data final inválida";
            $msg_erro["campos"][] = "data_final";
        }
        if (empty($msg_erro["msg"])) {
            $date = new DateTime($data_1[2]."-".$data_1[1]."-".$data_1[0]);
            $diferenca = $date->diff(new DateTime($data_2[2]."-".$data_2[1]."-".$data_2[0]));

            if ($diferenca->invert == 1) {
                $msg_erro["msg"]["obg"] .= "Data inicial não pode ser maior que a data final";
                $msg_erro["campos"][] = "data_inicial";
                $msg_erro["campos"][] = "data_final";                
            }else{
                if ($diferenca->m > 2) {
                    $msg_erro["msg"]["obg"] .= "Não será possível consultar mais de 2 meses";
                    $msg_erro["campos"][] = "data_inicial";
                    $msg_erro["campos"][] = "data_final";                    
                 }
            }  

            // TRANSFORMANDO DATA NO PADRAO AMERICANO        
            $di_x = formata_data($data_inicial);
            $df_x = formata_data($data_final);
        }

        $where_unidade = '';
        if (count($unidade_negocio)) {
            $unidade_negocio_aux = implode(',', $unidade_negocio);
            if (strpos($unidade_negocio_aux, '6101') !== false) {
                $unidade_negocio_aux .= ',6102,6103,6104,6105,6106,6107';
            }

            $where_unidade = " AND CASE WHEN JSON_FIELD('unidadeNegocio', tbl_os_campo_extra.campos_adicionais) = 'null' THEN '0' ELSE JSON_FIELD('unidadeNegocio', tbl_os_campo_extra.campos_adicionais) END::integer IN($unidade_negocio_aux)";
        }else{
            $where_unidade = " AND JSON_FIELD('unidadeNegocio', tbl_os_campo_extra.campos_adicionais) <> 'null' ";
        }
    }

    if (empty($msg_erro["msg"])) {
        switch ($tipo) {
            case 'garantia':
                $whereTipo = "AND tbl_tipo_atendimento.fora_garantia IS NOT TRUE";
                break;
            
            case 'fora_garantia':
                $whereTipo = "AND tbl_tipo_atendimento.fora_garantia IS TRUE";
                break;
        }

        $sql = "
            SELECT DISTINCT
                (
                    CASE WHEN tbl_tipo_atendimento.fora_garantia IS TRUE THEN
                        'Fora de Garantia'
                    ELSE
                        'Garantia'
                    END
                ) AS garantia,
                unidade_negocio.nome AS unidade_negocio2,
                JSON_FIELD('unidadeNegocio', tbl_os_campo_extra.campos_adicionais) AS unidade_negocio,
                tbl_tipo_atendimento.descricao AS tipo_servico,
                tbl_familia.descricao AS familia_produto,
                tbl_status_checkpoint.descricao AS status,
                (
                    CASE WHEN (CURRENT_DATE - tbl_os.data_abertura) > 30 THEN
                        30
                    ELSE
                        CURRENT_DATE - tbl_os.data_abertura
                    END
                ) AS dias_pendente,
                EXTRACT(MONTH FROM tbl_os.data_digitacao) AS mes,
                tbl_os.os,
                TO_CHAR(tbl_os.data_abertura, 'DD/MM/YYYY') AS data_abertura,
                tbl_produto.referencia AS referencia_produto,
                tbl_os_produto.serie AS numero_serie,
                tbl_cliente_admin.codigo AS cliente_admin_codigo,
                tbl_os.consumidor_nome AS cliente,
                tbl_posto.nome AS posto
            FROM tbl_os
            INNER JOIN tbl_os_campo_extra ON tbl_os_campo_extra.os = tbl_os.os
            INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
            INNER JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
            INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
            INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto AND tbl_produto.fabrica_i = {$login_fabrica}
            INNER JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia AND tbl_familia.fabrica = {$login_fabrica}
            INNER JOIN tbl_status_checkpoint ON tbl_status_checkpoint.status_checkpoint = tbl_os.status_checkpoint
            INNER JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = {$login_fabrica}
            LEFT JOIN tbl_hd_chamado ON tbl_hd_chamado.hd_chamado = tbl_os.hd_chamado AND tbl_hd_chamado.fabrica = {$login_fabrica}
            LEFT JOIN tbl_cliente_admin ON tbl_cliente_admin.cliente_admin = tbl_hd_chamado.cliente_admin AND tbl_cliente_admin.fabrica = {$login_fabrica}
            LEFT JOIN (
                    SELECT DISTINCT unidade_negocio, cidade
                    FROM tbl_distribuidor_sla
                    WHERE fabrica = {$login_fabrica}
                ) AS unidades ON unidades.unidade_negocio = JSON_FIELD('unidadeNegocio', tbl_os_campo_extra.campos_adicionais)
            LEFT JOIN tbl_unidade_negocio unidade_negocio ON unidade_negocio.codigo = unidades.unidade_negocio      
            WHERE tbl_os.fabrica = {$login_fabrica}
            AND tbl_os.data_abertura BETWEEN '{$di_x}' AND '{$df_x}'
            AND tbl_os.finalizada IS NULL
	    AND tbl_os.excluida IS NOT TRUE
            AND (tbl_tipo_atendimento.grupo_atendimento != 'P' OR tbl_tipo_atendimento.grupo_atendimento IS NULL)
            AND tbl_posto.posto <> 6359
            {$whereTipo}
            {$where_unidade}
            ORDER BY garantia DESC, unidade_negocio, tipo_servico, familia_produto, status, dias_pendente
        ";

        //die(nl2br($sql));
        $res = pg_query($con, $sql);

        if (!pg_num_rows($res)) {
            $msg_erro["msg"][] = "Nenhum resultado encontrado";
        } else {
            $resultado_detalhado = pg_fetch_all($res);
            $resultado           = array();
            $dias_pendente       = array();

            array_map(function($row) {
                global $resultado, $dias_pendente;

                $resultado[$row["garantia"]][$row["unidade_negocio"]][$row["tipo_servico"]][$row["familia_produto"]][$row["status"]][$row["mes"]][$row["dias_pendente"]][] = $row;

                $dias_pendente[$row["mes"]][] = $row["dias_pendente"];
                $dias_pendente[$row["mes"]] = array_unique($dias_pendente[$row["mes"]]);
                asort($dias_pendente[$row["mes"]]);
            }, pg_fetch_all($res));            
        }
    }
}

if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error" >
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>

<div class="row" >
    <b class="obrigatorio pull-right" >* Campos obrigatórios</b>
</div>

<form method="POST" class="form-search form-inline tc_formulario" >
    <div class="titulo_tabela" >Parâmetros de Pesquisa</div>

    <br />

    <div class="row-fluid" >
        <div class="span2" ></div>
        <div class="span2" >
            <div class="control-group <?php if (in_array("data_inicial", $msg_erro["campos"])) { echo "error"; } ?>">
                <label class="control-label" for="data_inicial">Data Inicial</label>
                <div class="controls controls-row">
                    <div class="span12">
                        <h5 class="asteristico">*</h5>
                        <input id="data_inicial" name="data_inicial" class="span12 " value="<?=$data_inicial ?>" type="text">
                    </div>
                </div>
            </div>
        </div>
        <div class="span2" >
            <div class="control-group <?php if (in_array("data_final", $msg_erro["campos"])) { echo "error"; } ?>">
                <label class="control-label" for="data_final">Data Final</label>
                <div class="controls controls-row">
                    <div class="span12">
                        <h5 class="asteristico">*</h5>
                        <input id="data_final" name="data_final" class="span12 " value="<?=$data_final ?>" type="text">
                    </div>
                </div>
            </div>
        </div>
        <div class="span5" >
            <div class="control-group  <?=(in_array('unidade_negocio', $msg_erro['campos'])) ? 'error' : '' ?>" >
                <label class="control-label" >Unidade de Negócio</label>
                <div class="controls controls-row">
                    <div class="span12">
                        <select name="unidade_negocio[]" multiple="multiple"  class="span12 select2" id="unidade_negocio">
                            <?php 
                                echo '<option value="">Escolha ...</option>';
                                $oDistribuidorSLA = new DistribuidorSLA();
                                $oDistribuidorSLA->setFabrica($login_fabrica);
                                $distribuidores_disponiveis = $oDistribuidorSLA->SelectUnidadeNegocioNotIn();

                                foreach ($distribuidores_disponiveis as $unidadeNegocio) {
                                    if (in_array($unidadeNegocio["unidade_negocio"], array(6102,6103,6104,6105,6106,6107,6108))) {
                                        unset($unidadeNegocio["unidade_negocio"]);
                                        continue;
                                    }
                                    $unidade_negocio_agrupado[$unidadeNegocio["unidade_negocio"]] = $unidadeNegocio["cidade"];
                                }

                                foreach ($unidade_negocio_agrupado as $unidade => $descricaoUnidade) {
                                    $selected = (in_array($unidade, $unidade_negocio)) ? 'selected' : '';
                                    echo '<option '.$selected.' value="'.$unidade.'">'.$descricaoUnidade.'</option>';
                                }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row-fluid" >
        <div class="span2" ></div>
        <div class="span6" >
            <div class="control-group" >
                <label class="control-label" >Tipo</label>
                <div class="controls controls-row" >
                    <div class="span12 radio" >
                        <label class="radio" >
                            <input type="radio" name="tipo" value="" checked /> Ambos
                        </label>
                        <label class="radio" >
                            <input type="radio" name="tipo" value="garantia" <?=(getValue("tipo") == "garantia") ? "checked" : ""?> /> OSs de Garantia
                        </label>
                        <label class="radio" >
                            <input type="radio" name="tipo" value="fora_garantia" <?=(getValue("tipo") == "fora_garantia") ? "checked" : ""?> /> OSs Fora de Garantia
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <br />

    <p class="tac" >
        <button type="submit" name="pesquisa" class="btn" >Pesquisar</button>
    </p>

    <br />
</form>

</div>

<?php
if (count($resultado) > 0) {
    ob_start();
    foreach ($dias_pendente as $mes_posicionado => $array_dias) {
    ?>
    <br />
    <table class="table table-bordered relatorio" >
        <caption class="titulo_tabela" >Ordens de Serviço pendentes por tipo de técnico <?php if(count($dias_pendente) > 1){ echo "- Mês: {$mes_posicionado}"; } ?></caption>
        <thead>
            <tr class="titulo_coluna" >
                <th rowspan="2" >Tipo</th>
                <th rowspan="2" >Unidade de Négocio</th>
                <th rowspan="2" >Tipo de Atendimento</th>
                <th rowspan="2" >Família</th>
                <th rowspan="2" >Status</th>
                <th colspan="<?=count($array_dias)?>" >Dias Pendente</th>
                <th rowspan="2" >Total</th>
            </tr>
            <tr>
                <?php
                foreach ($array_dias as $dia) {
                    if ($dia == 30) {
                        $dia = "30 ou mais";
                    }

                    echo "<th nowrap >{$dia}</th>";
                }
                ?>
            </tr>
        </thead>
        <tbody>
            <?php
            foreach ($resultado as $garantia => $unidades) {
                $total_garantia = 0;

                $rowspan = count($unidades) + 2;

                array_map(function($unidades) {
                    global $rowspan;

                    $rowspan += count($unidades) + 1;

                    array_map(function($tipos) {
                        global $rowspan;

                        $rowspan += count($tipos) + 1;

                        array_map(function($familias) {
                            global $rowspan;

                            $rowspan += count($familias);
                        }, $tipos);
                    }, $unidades);
                }, $unidades);

                echo "
                    <tr class='error' >
                        <td rowspan='{$rowspan}' >{$garantia}</td>
                    </tr>
                ";

                foreach ($unidades as $unidade_negocio => $tipos) {
                    $total_unidade = 0;

                    $rowspan = count($tipos) + 2;

                    array_map(function($tipos) {
                        global $rowspan;

                        $rowspan += count($tipos) + 1;

                        array_map(function($familias) {
                            global $rowspan;

                            $rowspan += count($familias);
                        }, $tipos);
                    }, $tipos);

                    $unidade_negocio = retorna_unidade($unidade_negocio);

                    echo "
                        <tr class='warning' >
                            <td rowspan='{$rowspan}' >{$unidade_negocio}</td>
                        </tr>
                    ";

                    foreach ($tipos as $tipo => $familias) {
                        $total_tipo = 0;

                        $rowspan = count($familias) + 2;

                        array_map(function($r) {
                            global $rowspan;

                            $rowspan += count($r);
                        }, $familias);

                        echo "
                            <tr class='info' >
                                <td rowspan='{$rowspan}' >{$tipo}</td>
                            </tr>
                        ";

                        foreach ($familias as $familia => $status_array) {
                            $rowspan = count($status_array) + 1;

                            echo "
                                <tr>
                                    <td rowspan='{$rowspan}' >{$familia}</td>
                                </tr>
                            ";

                            foreach ($status_array as $status => $array_mes) {
                                $total = 0;

                                echo "
                                    <tr>
                                        <td>{$status}</td>
                                ";

                                foreach ($array_mes as $mes_selecionado => $array_dia_recebidas) {
                                    if ($mes_selecionado == $mes_posicionado) {
                                        foreach ($array_dias as $dia) {
                                            $qtde_os    = (int) count($array_dia_recebidas[$dia]);
                                            $total      += $qtde_os;

                                            /*echo "<td>";
                                            echo json_encode(array_map(function($o) {
                                                return $o["os"];
                                            }, $array_dia_recebidas[$dia]));
                                            echo "{$qtde_os}</td>";*/

                                            echo "<td class='tac' >{$qtde_os}</td>";
                                        }
                                    }
                                }
                                if ($total == 0) {
                                    foreach ($array_dias as $dia) {
                                        echo "<td class='tac' ></td>";
                                    }                                    
                                }

                                echo "
                                        <th>{$total}</th>
                                    </tr>
                                ";

                                $total_tipo += $total;
                            }
                        }

                        echo "
                            <tr class='info' >
                                <td colspan='".(2 + count($array_dias))."' ><strong>Total</strong></td>
                                <td class='tac' ><strong>{$total_tipo}</strong></td>
                            </tr>
                        ";

                        $total_unidade += $total_tipo;
                    }

                    echo "
                        <tr class='warning' >
                            <td colspan='".(3 + count($array_dias))."' ><strong>Total</strong></td>
                            <td class='tac' ><strong>{$total_unidade}</strong></td>
                        </tr>
                    ";

                    $total_garantia += $total_unidade;
                }

                echo "
                    <tr class='error' >
                        <td colspan='".(4 + count($array_dias))."' ><strong>Total</strong></td>
                        <td class='tac' ><strong>{$total_garantia}</strong></td>
                    </tr>
                ";
            }
            ?>
        </tbody>
    </table>
    <?php
    }
    $html = ob_get_contents();

    ob_end_flush();
    ob_clean();

    $xls  = "relatorio-indicadores-oss-abertas-{$login_fabrica}-{$login_admin}-".date("YmdHi").".xls";
    $file = fopen("/tmp/".$xls, "w");
    fwrite($file, $html);
    fclose($file);
    system("mv /tmp/{$xls} xls/{$xls}");

    $csv_detalhado = "relatorio-indicadores-oss-abertas-detalhado-{$login_fabrica}-{$login_admin}-".date("YmdHi").".csv";
    $file = fopen("/tmp/".$csv_detalhado, "w");

    $cabecalho = array(
        utf8_encode("Unidade de Negócio"),
        utf8_encode("Ordem de Serviço"),
        "Data de Abertura",
        "Tipo de Atendimento",
        utf8_encode("Família"),
        "Produto",
        utf8_encode("Série"),
        "Status",
        "Tipo",
        "Cliente",
        "Posto"
    );

    fwrite($file, implode(";", $cabecalho)."\n");

    foreach ($resultado_detalhado as $os) {
        $valores = array(
            retorna_unidade($os["unidade_negocio"]),
            $os["os"],
            $os["data_abertura"],
            utf8_encode($os["tipo_servico"]),
            $os["familia_produto"],
            $os["referencia_produto"],
            $os["numero_serie"],
            utf8_encode($os["status"]),
            (($os["cliente_admin_codigo"] == "158-KOF") ? "KOF" : "OTR"),
            utf8_encode($os["cliente"]),
            utf8_encode($os["posto"])
        );

        fwrite($file, implode(";", $valores)."\n");
    }

    fclose($file);
    system("mv /tmp/{$csv_detalhado} xls/{$csv_detalhado}");
    ?>

    <br />

    <p class="tac" >
        <button type="button" class="btn btn-success download-xls" data-xls="<?=$xls?>" ><i class="icon-download-alt icon-white" ></i> Download XLS</button>

        <button type="button" class="btn btn-info download-xls" data-xls="<?=$csv_detalhado?>" ><i class="icon-list-alt icon-white" ></i> Download CSV Detalhado</button>
    </p>
<?php
}

$plugins = array(
    "select2",
    "mask"
);

include "plugin_loader.php";
?>

<style>

table.relatorio {
    margin: 0 auto;
}

</style>

<script>
$(function() {
    $.datepickerLoad(Array("data_final", "data_inicial"));
});

$("select").select2();

$("button.download-xls").on("click", function() {
    var xls = $(this).data("xls");

    window.open("xls/"+xls);
});

</script>

<br />

<?php 
include "rodape.php"; 
?>
