<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios = "gerencia";
include 'autentica_admin.php';
include 'funcoes.php';

use Posvenda\DistribuidorSLA;

$title = "Relatório de OSs fora de garantia por centro distribuidor";
$layout_menu = "gerencia";

include "cabecalho_new.php";

$oDistribuidorSLA = new DistribuidorSLA();
$oDistribuidorSLA->setFabrica($login_fabrica);
$distribuidores = $oDistribuidorSLA->SelectUnidadeNegocio();

if ($_POST) {
    $data_inicial    = $_POST["data_inicial"];
    $data_final      = $_POST["data_final"];
    $tipo            = $_POST["tipo"]; // null, pendente ou finalizada
    $unidade_negocio = $_POST['unidade_negocio'];
    $unidade_negocio = implode("|", $unidade_negocio);
    $tipo_atendimento = $_POST['tipo_atendimento'];
    $tipo_atendimento = implode(",", $tipo_atendimento);

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
        }
    }

    if (empty($msg_erro["msg"])) {
        if (strlen($unidade_negocio) > 0) {
            $whereUnid = "AND JSON_FIELD('unidadeNegocio',campos_adicionais) ~'{$unidade_negocio}'";
        }
        if (strlen($tipo_atendimento) > 0) {
            $whereTpAtendimento = " AND tbl_os.tipo_atendimento IN({$tipo_atendimento})";
            $tipo_atendimento = explode(',', $tipo_atendimento);
        }

        switch ($tipo) {
            case 'pendente':
                $whereTipo = "AND tbl_os.finalizada IS NULL";
                break;
            
            case 'finalizada':
                $whereTipo = "AND tbl_os.finalizada IS NOT NULL";
                break;
        }
        if ($diferenca->m > 0) {
            $whereIntervalo = '';
        }

        $tabela = "temp_os_fora_garantia_{$login_admin}_{$login_fabrica}";

        $sql = "
            SELECT
                tbl_os.os,
                EXTRACT(DAY FROM tbl_os.data_digitacao) AS dia,
                EXTRACT(MONTH FROM tbl_os.data_digitacao) AS mes,
                EXTRACT(DAY FROM tbl_os.data_conserto) AS dia_consertada,
                EXTRACT(DAY FROM tbl_os.finalizada) AS dia_finalizada,
                tbl_os.finalizada,
                tbl_os.data_conserto AS consertado,
                tbl_os.exportado,
                JSON_FIELD('unidadeNegocio',campos_adicionais)||' - '||unidade_negocio.nome AS unidade_negocio,
                tbl_familia.descricao AS familia,
                tbl_tipo_posto.descricao AS tipo_posto,
                tbl_posto.nome AS tecnico,
                tbl_tecnico_agenda.tecnico_agenda AS agenda,
                tbl_status_checkpoint.descricao AS status,
                tbl_cliente_admin.nome AS cliente_admin
            INTO TEMP TABLE {$tabela}
            FROM tbl_os
            LEFT JOIN tbl_os_campo_extra ON tbl_os_campo_extra.os = tbl_os.os
            INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
            INNER JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
            LEFT JOIN (
                SELECT DISTINCT unidade_negocio, cidade
                FROM tbl_distribuidor_sla
                WHERE fabrica = {$login_fabrica}
            ) AS unidades ON unidades.unidade_negocio = JSON_FIELD('unidadeNegocio', tbl_os_campo_extra.campos_adicionais)
            LEFT JOIN tbl_cidade unidade_negocio ON unidade_negocio.cidade = unidades.cidade
            INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
            INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto AND tbl_produto.fabrica_i = {$login_fabrica}
            INNER JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia AND tbl_familia.fabrica = {$login_fabrica}
            INNER JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = {$login_fabrica}
            LEFT JOIN tbl_tecnico_agenda ON tbl_tecnico_agenda.os = tbl_os.os AND tbl_tecnico_agenda.fabrica = {$login_fabrica}
            INNER JOIN tbl_status_checkpoint ON tbl_status_checkpoint.status_checkpoint = tbl_os.status_checkpoint
            INNER JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = {$login_fabrica}
            INNER JOIN tbl_hd_chamado ON tbl_hd_chamado.hd_chamado = tbl_os.hd_chamado AND tbl_hd_chamado.fabrica = {$login_fabrica}
            LEFT JOIN tbl_cliente_admin ON tbl_cliente_admin.cliente_admin = tbl_hd_chamado.cliente_admin AND tbl_cliente_admin.fabrica = {$login_fabrica}
            INNER JOIN tbl_hd_chamado_cockpit on tbl_hd_chamado_cockpit.hd_chamado = tbl_os.hd_chamado
            WHERE tbl_os.fabrica = {$login_fabrica}
            AND tbl_os.data_abertura between '{$data_inicial} 00:00:00' and '{$data_final} 23:59:59'
            AND tbl_tipo_atendimento.grupo_atendimento IS NULL
            AND tbl_posto.posto <> 6359
            {$whereTpAtendimento}
            {$whereTipo}
            {$whereUnid};

            SELECT * FROM {$tabela};
        ";
        
        $res = pg_query($con, $sql);

        if (!pg_num_rows($res)) {
            $msg_erro["alerta"][] = "Nenhum resultado encontrado";
        }
    }
}

if (count($msg_erro["msg"]) > 0) {
?>
    <div id='alertError' class="alert alert-error no-print" >
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
if (count($msg_erro["alerta"]) > 0) {
?>
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
            <div class="control-group" id="gDtInicial">
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
            <div class="control-group" id="gDtFinal">
                <label class="control-label" for="data_final">Data Final</label>
                <div class="controls controls-row">
                    <div class="span12">
                        <h5 class="asteristico">*</h5>
                        <input id="data_final" name="data_final" class="span12 " value="<?=$data_final ?>" type="text">
                    </div>
                </div>
            </div>
        </div>
        <div class='span6'>
            <div class="control-group" >
                <label class="control-label" >Tipo</label>
                <div class="controls controls-row" >
                    <div class="span12 radio" >
                        <label class="radio" >
                            <input type="radio" name="tipo" value="" checked /> Ambos
                        </label>
                        <label class="radio" >
                            <input type="radio" name="tipo" value="pendente" <?=(getValue("tipo") == "pendente") ? "checked" : ""?> /> OSs Pendentes
                        </label>
                        <label class="radio" >
                            <input type="radio" name="tipo" value="finalizada" <?=(getValue("tipo") == "finalizada") ? "checked" : ""?> /> OSs Finalizadas
                        </label>
                    </div>
                </div>
            </div>
        </div>        
    </div>
    <div class="row-fluid" >
        <div class="span2" ></div>
        <div class="span4" >
            <div class='control-group'>
                <label class='control-label' for='unidade_negocio'>Unidade de Negócio:</label>
                <div class='controls controls-row'>
                    <select id="unidade_negocio" name='unidade_negocio[]' class='span12' multiple="multiple">
                        <option value=''>Selecione</option>
                        <? foreach ($distribuidores as $unidadeNegocio) {
                            $selected = (in_array($unidadeNegocio['unidade_negocio'], getValue("unidade_negocio"))) ? "selected" : ""; ?>
                            <option value="<?= $unidadeNegocio['unidade_negocio']; ?>" <?= $selected; ?>><?= $unidadeNegocio['cidade']; ?></option>
                        <? } ?>
                    </select>
                </div>
            </div>            
        </div>
        <div class="span4" >
            <div class='control-group'>
                <label class='control-label' for='tipo_atendimento'>Tipo Atendimento:</label>
                <div class='controls controls-row'>
                    <select id="tipo_atendimento" name='tipo_atendimento[]' class='span12' multiple="multiple">
                        <option value=''>Selecione</option>
                        <?php
                        //Listando somente Garantia corretiva e Corretiva
                        $sql = "SELECT tipo_atendimento,descricao FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica AND ativo AND tipo_atendimento IN(273,252);";
                        $res   = pg_exec($con,$sql);
                        
                        for($i = 0; $i < pg_numrows($res); $i++){
                            $tipo_atendimento_id = pg_result($res,$i,tipo_atendimento);
                            $descricao   = pg_result($res,$i,descricao);
                            $retorno .= "<option value={$tipo_atendimento_id} ";
                            if (in_array($tipo_atendimento_id, $tipo_atendimento)) {
                                $retorno .= 'selected';
                            }                            
                            $retorno .= ">{$descricao}</option>";
                        }
                        echo $retorno;
                        ?>
                    </select>
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
if ($_POST && empty($msg_erro["msg"]) && empty($msg_erro["alerta"])) {
    ob_start();

    $sql = "
        SELECT dia, mes, unidade_negocio, familia, tipo_posto, tecnico, status, COUNT(os) AS qtde_os
        FROM {$tabela}
        WHERE finalizada IS NULL
        AND agenda IS NOT NULL
        GROUP BY mes, dia, unidade_negocio, familia, tipo_posto, tecnico, status
        ORDER BY dia ASC, unidade_negocio, familia, tipo_posto, tecnico, status
    ";

    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {
        $resultado = array();
        $dias      = array();
        $mes       = array();

        array_map(function($row) {
            global $resultado, $dias, $mes;

            $dias[$row["mes"]][] = $row["dia"];
            if (!in_array($row["mes"], $mes)) {
                $mes[] = $row["mes"];
            }

            $resultado["familia"][$row["unidade_negocio"]][$row["familia"]][$row["dia"]][$row["mes"]]                          += $row["qtde_os"];
            $resultado["tipo_tecnico"][$row["unidade_negocio"]][$row["tipo_posto"]][$row["familia"]][$row["dia"]][$row["mes"]] += $row["qtde_os"];
            $resultado["tecnico"][$row["tipo_posto"]][$row["tecnico"]][$row["familia"]][$row["dia"]]              += $row["qtde_os"];
            $resultado["status"][$row["unidade_negocio"]][$row["familia"]][$row["status"]][$row["dia"]][$row["mes"]]           += $row["qtde_os"];
        }, pg_fetch_all($res));

        foreach ($mes as $mes_selecionado) {
            $dias[$mes_selecionado] = array_unique($dias[$mes_selecionado]);                
        }
        ?>
        <br />
        <?php foreach ($mes as $mes_selecionado) { ?>
        <table class="table table-bordered relatorio" >
            <caption class="titulo_tabela" >Ordens de Serviço pendentes por família <?php if (count($mes) > 1) { echo "- Mês: {$mes_selecionado}"; } ?></caption>
            <thead>
                <tr class="titulo_coluna" >
                    <th>Unidade de Négocio</th>
                    <th>Família</th>
                    <?php
                    foreach ($dias as $mes_posicionado => $dia) {
                        if ($mes_selecionado == $mes_posicionado) {
                            foreach ($dia as $val_dia) {
                                echo "<th>{$val_dia}</th>";
                            }                            
                        }                        
                    }
                    ?>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $dias_total_array = array();
                
                foreach ($resultado["familia"] as $unidade_negocio => $familias) {
                    $unidade_negocio_dias_total_array = array();
                    $rowspan                          = 2 + count($familias);

                    echo "
                        <tr>
                            <td rowspan='{$rowspan}' >{$unidade_negocio}</td>
                        </tr>
                    ";

                    foreach ($familias as $familia => $dias_array) {
                        $total_familia = 0;

                        echo "
                            <tr>
                                <td>{$familia}</td>
                        ";

                        foreach ($dias as $mes_posicionado => $val_dia) {
                            if ($mes_selecionado == $mes_posicionado) {
                                foreach ($val_dia as $dia) {                            
                                    $qtde_os                                = (int) $dias_array[$dia][$mes_selecionado];
                                    $total_familia                          += $qtde_os;    
                                    $unidade_negocio_dias_total_array[$dia] += $qtde_os;

                                    echo "<td class='tac' >{$qtde_os}</td>";
                                }
                            }
                        }

                        echo "
                                <td class='tac' >{$total_familia}</td>
                            </tr>
                        ";
                    }

                    echo "
                        <tr class='info' >
                            <td><strong>Total</strong></td>
                    ";

                    $dias_total = 0;

                    foreach ($unidade_negocio_dias_total_array as $dia => $qtde_os) {
                        $dias_total             += $qtde_os;
                        $dias_total_array[$dia] += $qtde_os;
                        echo "<td class='tac' ><strong>{$qtde_os}</strong></td>";
                    }

                    echo "
                            <td class='tac' ><strong>{$dias_total}</strong></td>
                        </tr>
                    ";
                }
                ?>
                <tr class="titulo_coluna" >
                    <th colspan="2" >Total</th>
                    <?php
                    $dias_total = 0;

                    foreach ($dias_total_array as $qtde_os) {
                        $dias_total += $qtde_os;
                        echo "<th>{$qtde_os}</th>";
                    }
                    ?>
                    <th><?=$dias_total?></th>
                </tr>
            </tbody>
        </table>
        <br />
        <?php } 
        foreach ($mes as $mes_selecionado) {
        ?>
        <br />
        <table class="table table-bordered relatorio" >
            <caption class="titulo_tabela" >Ordens de Serviço pendentes por tipo de técnico <?php if (count($mes) > 1) { echo "- Mês: {$mes_selecionado}"; } ?></caption>
            <thead>
                <tr class="titulo_coluna" >
                    <th>Unidade de Négocio</th>
                    <th>Tipo de Técnico</th>
                    <th>Família</th>
                    <?php
                    foreach ($dias as $mes_posicionado => $dia) {
                        if ($mes_selecionado == $mes_posicionado) {
                            foreach ($dia as $val_dia) {
                                echo "<th>{$val_dia}</th>";
                            }                            
                        }                        
                    }
                    ?>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $dias_total_array = array();

                foreach ($resultado["tipo_tecnico"] as $unidade_negocio => $tipos) {
                    $unidade_negocio_dias_total_array = array();
                    $rowspan                          = 2 + (count($tipos) * 2);

                    array_map(function($r) {
                        global $rowspan;

                        $rowspan += count($r);
                    }, $tipos);

                    echo "
                        <tr>
                            <td rowspan='{$rowspan}' >{$unidade_negocio}</td>
                        </tr>
                    ";

                    foreach ($tipos as $tipo_tecnico => $familias) {
                        $tipo_tecnico_dias_total_array = array();
                        $rowspan                       = 2 + count($familias);

                        echo "
                            <tr>
                                <td rowspan='{$rowspan}' >{$tipo_tecnico}</td>
                            </tr>
                        ";

                        foreach ($familias as $familia => $dias_array) {
                            $total_familia = 0;

                            echo "
                                <tr>
                                    <td>{$familia}</td>
                            ";

                            foreach ($dias as $mes_posicionado => $val_dia) {
                                if ($mes_selecionado == $mes_posicionado) {
                                    foreach ($val_dia as $dia) {
                                        $qtde_os                             = (int) $dias_array[$dia][$mes_posicionado];
                                        $total_familia                       += $qtde_os;
                                        $tipo_tecnico_dias_total_array[$dia] += $qtde_os;

                                        echo "<td class='tac' >{$qtde_os}</td>";
                                    }
                                }
                            }

                            echo "
                                    <td class='tac' >{$total_familia}</td>
                                </tr>
                            ";
                        }

                        echo "
                            <tr class='info' >
                                <td><strong>Total</strong></td>
                        ";

                        $dias_total = 0;

                        foreach ($tipo_tecnico_dias_total_array as $dia => $qtde_os) {
                            $dias_total                             += $qtde_os;
                            $unidade_negocio_dias_total_array[$dia] += $qtde_os;
                            echo "<td class='tac' ><strong>{$qtde_os}</strong></td>";
                        }

                        echo "
                                <td class='tac' ><strong>{$dias_total}</strong></td>
                            </tr>
                        ";
                    }

                    echo "
                        <tr class='error' >
                            <td colspan='2' ><strong>Total</strong></td>
                    ";

                    $dias_total = 0;

                    foreach ($unidade_negocio_dias_total_array as $dia => $qtde_os) {
                        $dias_total             += $qtde_os;
                        $dias_total_array[$dia] += $qtde_os;
                        echo "<td class='tac' ><strong>{$qtde_os}</strong></td>";
                    }

                    echo "
                            <td class='tac' ><strong>{$dias_total}</strong></td>
                        </tr>
                    ";
                }
                ?>
                <tr class="titulo_coluna" >
                    <th colspan="3" >Total</th>
                    <?php
                    $dias_total = 0;

                    foreach ($dias_total_array as $qtde_os) {
                        $dias_total += $qtde_os;
                        echo "<th>{$qtde_os}</th>";
                    }
                    ?>
                    <th><?=$dias_total?></th>
                </tr>
            </tbody>
        </table>        
        <br />
        <?php } ?>
        <table class="table table-bordered relatorio" >
            <caption class="titulo_tabela" >Ordens de Serviço pendentes por técnico <?php echo "- Período: {$data_inicial} - {$data_final}" ?></caption>
            <thead>
                <tr class="titulo_coluna" >
                    <th>Tipo de Técnico</th>
                    <th>Técnico</th>
                    <th>Família</th>
                    <th>Total</th>
                </tr>            
            </thead>
            <tbody>
            <?php
            $contador = array();
            foreach ($resultado["tecnico"] as $tipo_tecnico => $tecnico) {
                $contador[$tipo_tecnico] = count($tecnico);        
                foreach ($tecnico as $nome_tecnico => $familia) {
                    if (count($familia) > 1) {
                        $contador[$tipo_tecnico] += count($familia) - 1;
                    }                    
                }
                $contador[$tipo_tecnico] += 1;
            }
            foreach ($resultado["tecnico"] as $tipo_tecnico => $tecnico) {
                echo "<tr><td rowspan='{$contador[$tipo_tecnico]}'>{$tipo_tecnico}</td></tr>";
                foreach ($tecnico as $nome_tecnico => $familia) {
                    $rowspan = '';
                    if (count($familia) > 1) {
                        $rowspan = "rowspan='".count($familia)."'";
                    }
                    echo "<tr><td {$rowspan}>{$nome_tecnico}</td>";
                    $prox_linha = 0;
                    foreach ($familia as $nome_familia => $dias_tecnico) {
                        if ($prox_linha == 1) {
                            echo "<tr>";
                        }
                        echo "<td>{$nome_familia}</td>";
                        $prox_linha += 1;
                        $total_familia = 0;
                        foreach ($dias_tecnico as $dia_tecnico => $total) {
                            $total_familia += $total;
                        }
                        echo "<td>{$total_familia}</td></tr>";
                    }                    
                }
            }
            ?>
            </tbody>
        </table>
        <br />
        <?php foreach ($mes as $mes_selecionado) { ?>
        <table class="table table-bordered relatorio" >
            <caption class="titulo_tabela" >Ordens de Serviço pendentes por status <?php if (count($mes) > 1) { echo "- Mês: {$mes_selecionado}"; } ?></caption>
            <thead>
                <tr class="titulo_coluna" >
                    <th>Unidade de Négocio</th>
                    <th>Família</th>
                    <th>Status</th>
                    <?php                    
                    foreach ($dias as $mes_posicionado => $dia) {
                        if ($mes_selecionado == $mes_posicionado) {
                            foreach ($dia as $val_dia) {
                                echo "<th>{$val_dia}</th>";
                            }                            
                        }                        
                    }
                    ?>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $dias_total_array = array();

                foreach ($resultado["status"] as $unidade_negocio => $familias) {
                    $unidade_negocio_dias_total_array = array();
                    $rowspan                          = 2 + (count($familias) * 2);

                    array_map(function($r) {
                        global $rowspan;

                        $rowspan += count($r);
                    }, $familias);

                    echo "
                        <tr>
                            <td rowspan='{$rowspan}' >{$unidade_negocio}</td>
                        </tr>
                    ";

                    foreach ($familias as $familia => $status_array) {
                        $familia_dias_total_array = array();
                        $rowspan                  = 2 + count($status_array);

                        echo "
                            <tr>
                                <td rowspan='{$rowspan}' >{$familia}</td>
                            </tr>
                        ";

                         foreach ($status_array as $status => $dias_array) {
                            $total_status = 0;

                            echo "
                                <tr>
                                    <td>{$status}</td>
                            ";

                            foreach ($dias as $mes_posicionado => $val_dia) {
                                if ($mes_selecionado == $mes_posicionado) {
                                    foreach ($val_dia as $dia) {
                                        $qtde_os                        = (int) $dias_array[$dia][$mes_selecionado];
                                        $total_status                   += $qtde_os;
                                        $familia_dias_total_array[$dia] += $qtde_os;

                                        echo "<td class='tac' >{$qtde_os}</td>";
                                    }
                                }
                            }

                            echo "
                                    <td class='tac' >{$total_status}</td>
                                </tr>
                            ";
                        }

                        echo "
                            <tr class='info' >
                                <td><strong>Total</strong></td>
                        ";

                        $dias_total = 0;

                        foreach ($familia_dias_total_array as $dia => $qtde_os) {
                            $dias_total                             += $qtde_os;
                            $unidade_negocio_dias_total_array[$dia] += $qtde_os;
                            echo "<td class='tac' ><strong>{$qtde_os}</strong></td>";
                        }

                        echo "
                                <td class='tac' ><strong>{$dias_total}</strong></td>
                            </tr>
                        ";
                    }

                    echo "
                        <tr class='error' >
                            <td colspan='2' ><strong>Total</strong></td>
                    ";

                    $dias_total = 0;

                    foreach ($unidade_negocio_dias_total_array as $dia => $qtde_os) {
                        $dias_total             += $qtde_os;
                        $dias_total_array[$dia] += $qtde_os;
                        echo "<td class='tac' ><strong>{$qtde_os}</strong></td>";
                    }

                    echo "
                            <td class='tac' ><strong>{$dias_total}</strong></td>
                        </tr>
                    ";
                }
                ?>
                <tr class="titulo_coluna" >
                    <th colspan="3" >Total</th>
                    <?php
                    $dias_total = 0;

                    foreach ($dias_total_array as $qtde_os) {
                        $dias_total += $qtde_os;
                        echo "<th>{$qtde_os}</th>";
                    }
                    ?>
                    <th><?=$dias_total?></th>
                </tr>
            </tbody>
        </table>
        <br />
    <?php
        }
    }

    $sql = "
        SELECT dia, mes, unidade_negocio, familia, COUNT(os) AS qtde_os
        FROM {$tabela}
        WHERE agenda IS NULL AND finalizada IS NULL
        GROUP BY dia, mes, unidade_negocio, familia
        ORDER BY dia ASC, unidade_negocio, familia
    ";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {
         $resultado = array();
         $dias      = array();
         $mes       = array();

        array_map(function($row) {
            global $resultado, $dias, $mes;

            $dias[$row["mes"]][] = $row["dia"];
            if (!in_array($row["mes"], $mes)) {
                $mes[] = $row["mes"];
            }

            $resultado[$row["unidade_negocio"]][$row["familia"]][$row["dia"]][$row["mes"]] += $row["qtde_os"];
        }, pg_fetch_all($res));

        foreach ($mes as $mes_selecionado) {
            $dias[$mes_selecionado] = array_unique($dias[$mes_selecionado]);
        ?>        
        <table class="table table-bordered relatorio" >
            <caption class="titulo_tabela" >Ordens de Serviço não agendadas <?php if (count($mes) > 1) { echo "- Mês: {$mes_selecionado}"; } ?></caption>
            <thead>
                <tr class="titulo_coluna" >
                    <th>Unidade de Négocio</th>
                    <th>Família</th>
                    <?php
                    foreach ($dias as $mes_posicionado => $dia) {
                        if ($mes_selecionado == $mes_posicionado) {
                            foreach ($dia as $val_dia) {
                                echo "<th>{$val_dia}</th>";
                            }                            
                        }                        
                    }
                    ?>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $dias_total_array = array();

                foreach ($resultado as $unidade_negocio => $familias) {
                    $unidade_negocio_dias_total_array = array();
                    $rowspan                          = 2 + count($familias);

                    echo "
                        <tr>
                            <td rowspan='{$rowspan}' >{$unidade_negocio}</td>
                        </tr>
                    ";

                    foreach ($familias as $familia => $dias_array) {
                        $total_familia = 0;

                        echo "
                            <tr>
                                <td>{$familia}</td>
                        ";

                        foreach ($dias as $mes_posicionado => $val_dia) {
                            if ($mes_selecionado == $mes_posicionado) {
                                foreach ($val_dia as $dia) {
                                    $qtde_os                                = (int) $dias_array[$dia][$mes_selecionado];
                                    $total_familia                          += $qtde_os;
                                    $unidade_negocio_dias_total_array[$dia] += $qtde_os;

                                    echo "<td class='tac' >{$qtde_os}</td>";
                                }
                            }
                        }

                        echo "
                                <td class='tac' >{$total_familia}</td>
                            </tr>
                        ";
                    }

                    echo "
                        <tr class='info' >
                            <td><strong>Total</strong></td>
                    ";

                    $dias_total = 0;

                    foreach ($unidade_negocio_dias_total_array as $dia => $qtde_os) {
                        $dias_total             += $qtde_os;
                        $dias_total_array[$dia] += $qtde_os;
                        echo "<td class='tac' ><strong>{$qtde_os}</strong></td>";
                    }

                    echo "
                            <td class='tac' ><strong>{$dias_total}</strong></td>
                        </tr>
                    ";
                }
                ?>
                <tr class="titulo_coluna" >
                    <th colspan="2" >Total</th>
                    <?php
                    $dias_total = 0;

                    foreach ($dias_total_array as $qtde_os) {
                        $dias_total += $qtde_os;
                        echo "<th>{$qtde_os}</th>";
                    }
                    ?>
                    <th><?=$dias_total?></th>
                </tr>
            </tbody>
        </table>
        <br/>
    <?php
        }
    }

    $sql = "
        SELECT dia_consertada, mes, unidade_negocio, familia, COUNT(os) AS qtde_os
        FROM {$tabela}
        WHERE consertado IS NOT NULL
        AND finalizada IS NULL
        GROUP BY dia_consertada, mes, unidade_negocio, familia
        ORDER BY dia_consertada ASC, unidade_negocio, familia
    ";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {
         $resultado = array();
         $dias      = array();
         $mes       = array();

        array_map(function($row) {
            global $resultado, $dias, $mes;

            $dias[$row["mes"]][] = $row["dia_consertada"];
            if (!in_array($row["mes"], $mes)) {
                $mes[] = $row["mes"];
            }

            $resultado[$row["unidade_negocio"]][$row["familia"]][$row["dia_consertada"]][$row["mes"]] += $row["qtde_os"];
        }, pg_fetch_all($res));

        foreach ($mes as $mes_selecionado) {
            $dias[$mes_selecionado] = array_unique($dias[$mes_selecionado]);
        ?>
        <br />
        <table class="table table-bordered relatorio" >
            <caption class="titulo_tabela" >Ordens de Serviço consertadas e não finalizadas <?php if (count($mes) > 1) { echo "- Mês: {$mes_selecionado}"; } ?></caption>
            <thead>
                <tr class="titulo_coluna" >
                    <th>Unidade de Négocio</th>
                    <th>Família</th>
                    <?php
                    foreach ($dias as $mes_posicionado => $dia) {
                        if ($mes_selecionado == $mes_posicionado) {
                            foreach ($dia as $val_dia) {
                                echo "<th>{$val_dia}</th>";
                            }                            
                        }                        
                    }
                    ?>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $dias_total_array = array();

                foreach ($resultado as $unidade_negocio => $familias) {
                    $unidade_negocio_dias_total_array = array();
                    $rowspan                          = 2 + count($familias);

                    echo "
                        <tr>
                            <td rowspan='{$rowspan}' >{$unidade_negocio}</td>
                        </tr>
                    ";

                    foreach ($familias as $familia => $dias_array) {
                        $total_familia = 0;

                        echo "
                            <tr>
                                <td>{$familia}</td>
                        ";

                        foreach ($dias as $mes_posicionado => $val_dia) {
                            if ($mes_selecionado == $mes_posicionado) {
                                foreach ($val_dia as $dia) {                            
                                    $qtde_os                                = (int) $dias_array[$dia][$mes_selecionado];
                                    $total_familia                          += $qtde_os;
                                    $unidade_negocio_dias_total_array[$dia] += $qtde_os;

                                    echo "<td class='tac' >{$qtde_os}</td>";
                                }
                            }
                        }

                        echo "
                                <td class='tac' >{$total_familia}</td>
                            </tr>
                        ";
                    }

                    echo "
                        <tr class='info' >
                            <td><strong>Total</strong></td>
                    ";

                    $dias_total = 0;

                    foreach ($unidade_negocio_dias_total_array as $dia => $qtde_os) {
                        $dias_total             += $qtde_os;
                        $dias_total_array[$dia] += $qtde_os;
                        echo "<td class='tac' ><strong>{$qtde_os}</strong></td>";
                    }

                    echo "
                            <td class='tac' ><strong>{$dias_total}</strong></td>
                        </tr>
                    ";
                }
                ?>
                <tr class="titulo_coluna" >
                    <th colspan="2" >Total</th>
                    <?php
                    $dias_total = 0;

                    foreach ($dias_total_array as $qtde_os) {
                        $dias_total += $qtde_os;
                        echo "<th>{$qtde_os}</th>";
                    }
                    ?>
                    <th><?=$dias_total?></th>
                </tr>
            </tbody>
        </table>
    <?php
        }
    }

    $sql = "
        SELECT dia_finalizada, mes, unidade_negocio, familia, COUNT(os) AS qtde_os
        FROM {$tabela}
        WHERE finalizada IS NOT NULL
        AND exportado IS NULL
        GROUP BY dia_finalizada, mes, unidade_negocio, familia
        ORDER BY dia_finalizada ASC, unidade_negocio, familia
    ";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {
         $resultado = array();
         $dias      = array();
         $mes       = array();

        array_map(function($row) {
            global $resultado, $dias, $mes;

            $dias[$row["mes"]][] = $row["dia_finalizada"];
            if (!in_array($row["mes"], $mes)) {
                $mes[] = $row["mes"];
            }

            $resultado[$row["unidade_negocio"]][$row["familia"]][$row["dia_finalizada"]][$row["mes"]] += $row["qtde_os"];
        }, pg_fetch_all($res));

        foreach ($mes as $mes_selecionado) {
            $dias[$mes_selecionado] = array_unique($dias[$mes_selecionado]);
        ?>
        <br />
        <table class="table table-bordered relatorio" >
            <caption class="titulo_tabela" >Ordens de Serviço finalizadas e não enviadas para a KOF <?php if (count($mes) > 1) { echo "- Mês: {$mes_selecionado}"; } ?></caption>
            <thead>
                <tr class="titulo_coluna" >
                    <th>Unidade de Négocio</th>
                    <th>Família</th>
                    <?php
                    foreach ($dias as $mes_posicionado => $dia) {
                        if ($mes_selecionado == $mes_posicionado) {
                            foreach ($dia as $val_dia) {
                                echo "<th>{$val_dia}</th>";
                            }                            
                        }                        
                    }
                    ?>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $dias_total_array = array();

                foreach ($resultado as $unidade_negocio => $familias) {
                    $unidade_negocio_dias_total_array = array();
                    $rowspan                          = 2 + count($familias);

                    echo "
                        <tr>
                            <td rowspan='{$rowspan}' >{$unidade_negocio}</td>
                        </tr>
                    ";

                    foreach ($familias as $familia => $dias_array) {
                        $total_familia = 0;

                        echo "
                            <tr>
                                <td>{$familia}</td>
                        ";

                        foreach ($dias as $mes_posicionado => $val_dia) {
                            if ($mes_selecionado == $mes_posicionado) {
                                foreach ($val_dia as $dia) {
                                    $qtde_os                                = (int) $dias_array[$dia][$mes_selecionado];
                                    $total_familia                          += $qtde_os;
                                    $unidade_negocio_dias_total_array[$dia] += $qtde_os;

                                    echo "<td class='tac' >{$qtde_os}</td>";
                                }
                            }
                        }

                        echo "
                                <td class='tac' >{$total_familia}</td>
                            </tr>
                        ";
                    }

                    echo "
                        <tr class='info' >
                            <td><strong>Total</strong></td>
                    ";

                    $dias_total = 0;

                    foreach ($unidade_negocio_dias_total_array as $dia => $qtde_os) {
                        $dias_total             += $qtde_os;
                        $dias_total_array[$dia] += $qtde_os;
                        echo "<td class='tac' ><strong>{$qtde_os}</strong></td>";
                    }

                    echo "
                            <td class='tac' ><strong>{$dias_total}</strong></td>
                        </tr>
                    ";
                }
                ?>
                <tr class="titulo_coluna" >
                    <th colspan="2" >Total</th>
                    <?php
                    $dias_total = 0;

                    foreach ($dias_total_array as $qtde_os) {
                        $dias_total += $qtde_os;
                        echo "<th>{$qtde_os}</th>";
                    }
                    ?>
                    <th><?=$dias_total?></th>
                </tr>
            </tbody>
        </table>
    <?php
        }
    }

    $sql = "
        SELECT dia_finalizada, mes, familia, tecnico, tipo_posto, unidade_negocio, cliente_admin, COUNT(os) AS qtde_os
        FROM {$tabela}
        WHERE finalizada IS NOT NULL
        GROUP BY dia_finalizada, mes, familia, tecnico, tipo_posto, unidade_negocio, cliente_admin
        ORDER BY dia_finalizada ASC, mes, familia, tecnico, tipo_posto, unidade_negocio, cliente_admin
    ";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {
         $resultado = array();
         $dias = array();
         $mes  = array();

        array_map(function($row) {
            global $resultado, $dias, $mes;

            $dias[$row["mes"]][] = $row["dia_finalizada"];
            if (!in_array($row["mes"], $mes)) {
                $mes[] = $row["mes"];
            }            

            $resultado["tecnico"][$row["familia"]][$row["tecnico"]] += $row["qtde_os"];
            $resultado["tipo_posto"][$row["tipo_posto"]][$row["unidade_negocio"]][$row["familia"]][$row["dia_finalizada"]] += $row["qtde_os"];
            $resultado["cliente_admin"][$row["cliente_admin"]][$row["dia_finalizada"]]                                     += $row["qtde_os"];
            $resultado["finalizada"][$row["unidade_negocio"]][$row["familia"]][$row["mes"]][$row["dia_finalizada"]] += $row["qtde_os"];
        }, pg_fetch_all($res));
        ?>
        <br />
        <table class="table table-bordered relatorio" >
            <caption class="titulo_tabela" >Ordens de Serviço finalizadas por técnico <?php echo "- Período: {$data_inicial} - {$data_final}" ?></caption>
            <thead>
                <tr class="titulo_coluna" >
                    <th>Família</th>
                    <th>Técnico</th>                    
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $dias_total_array = array();

                $total_todos_tecnico = 0;
                foreach ($resultado["tecnico"] as $familia => $tecnicos) {
                    $familia_dias_total       = 0;
                    $rowspan                  = 2 + count($tecnicos);

                    echo "
                        <tr>
                            <td rowspan='{$rowspan}' >{$familia}</td>
                        </tr>
                    ";

                    foreach ($tecnicos as $tecnico => $qtde_os) {
                        echo "
                            <tr>
                                <td>{$tecnico}</td>
                        ";
                        echo "
                                <td class='tac' >{$qtde_os}</td>
                            </tr>
                        ";
                        
                        $familia_dias_total += $qtde_os;
                        $total_todos_tecnico += $qtde_os;
                    }

                    echo "
                        <tr class='info' >
                            <td><strong>Total</strong></td>
                    ";

                    echo "
                            <td class='tac' ><strong>{$familia_dias_total}</strong></td>
                        </tr>
                    ";                    
                }
                ?>
                <tr class="titulo_coluna" >
                    <th colspan="2" >Total</th>
                    <th><?=$total_todos_tecnico?></th>
                </tr>
            </tbody>
        </table>
        <?php foreach ($mes as $mes_selecionado) {                     
            $dias[$mes_selecionado] = array_unique($dias[$mes_selecionado]);
        ?>
        <br />        
        <table class="table table-bordered relatorio" >
            <caption class="titulo_tabela" >Ordens de Serviço finalizadas por tipo de técnico <?php if (count($mes) > 1) { echo "- Mês: {$mes_selecionado}"; } ?></caption>
            <thead>
                <tr class="titulo_coluna" >
                    <th>Tipo de Técnico</th>
                    <th>Unidade de Negócio</th>
                    <th>Família</th>
                    <?php
                    foreach ($dias as $mes_posicionado => $dia) {
                        if ($mes_selecionado == $mes_posicionado) {
                            foreach ($dia as $val_dia) {
                                echo "<th>{$val_dia}</th>";
                            }                            
                        }                        
                    }
                    ?>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $dias_total_array = array();

                foreach ($resultado["tipo_posto"] as $tipo_posto => $unidades) {
                    $tipo_posto_dias_total_array = array();
                    $rowspan                     = 2 + (count($unidades) * 2);

                    array_map(function($r) {
                        global $rowspan;

                        $rowspan += count($r);
                    }, $unidades);

                    echo "
                        <tr>
                            <td rowspan='{$rowspan}' >{$tipo_posto}</td>
                        </tr>
                    ";

                    foreach ($unidades as $unidade_negocio => $familias) {
                        $unidade_negocio_dias_total_array = array();
                        $rowspan                          = 2 + count($familias);

                        echo "
                            <tr>
                                <td rowspan='{$rowspan}' >{$unidade_negocio}</td>
                            </tr>
                        ";

                         foreach ($familias as $familia => $dias_array) {
                            $total_familia = 0;

                            echo "
                                <tr>
                                    <td>{$familia}</td>
                            ";

                            foreach ($dias as $mes_posicionado => $val_dia) {
                                if ($mes_selecionado == $mes_posicionado) {
                                    foreach ($val_dia as $dia) {
                                        $qtde_os                                = (int) $dias_array[$dia];
                                        $total_familia                          += $qtde_os;
                                        $unidade_negocio_dias_total_array[$dia] += $qtde_os;

                                        echo "<td class='tac' >{$qtde_os}</td>";
                                    }
                                }
                            }

                            echo "
                                    <td class='tac' >{$total_familia}</td>
                                </tr>
                            ";
                        }

                        echo "
                            <tr class='info' >
                                <td><strong>Total</strong></td>
                        ";

                        $dias_total = 0;

                        foreach ($unidade_negocio_dias_total_array as $dia => $qtde_os) {
                            $dias_total                        += $qtde_os;
                            $tipo_posto_dias_total_array[$dia] += $qtde_os;
                            echo "<td class='tac' ><strong>{$qtde_os}</strong></td>";
                        }

                        echo "
                                <td class='tac' ><strong>{$dias_total}</strong></td>
                            </tr>
                        ";
                    }

                    echo "
                        <tr class='error' >
                            <td colspan='2' ><strong>Total</strong></td>
                    ";

                    $dias_total = 0;

                    foreach ($tipo_posto_dias_total_array as $dia => $qtde_os) {
                        $dias_total             += $qtde_os;
                        $dias_total_array[$dia] += $qtde_os;
                        echo "<td class='tac' ><strong>{$qtde_os}</strong></td>";
                    }

                    echo "
                            <td class='tac' ><strong>{$dias_total}</strong></td>
                        </tr>
                    ";
                }
                ?>
                <tr class="titulo_coluna" >
                    <th colspan="3" >Total</th>
                    <?php
                    $dias_total = 0;

                    foreach ($dias_total_array as $qtde_os) {
                        $dias_total += $qtde_os;
                        echo "<th>{$qtde_os}</th>";
                    }
                    ?>
                    <th><?=$dias_total?></th>
                </tr>
            </tbody>
        </table>
        <?php } foreach ($mes as $mes_selecionado) { ?>
        <br />
        <table class="table table-bordered relatorio" >
            <caption class="titulo_tabela" >Ordens de Serviço finalizadas por cliente admin <?php if (count($mes) > 1) { echo "- Mês: {$mes_selecionado}"; } ?></caption>
            <thead>
                <tr class="titulo_coluna" >
                    <th>Cliente Admin</th>
                    <?php
                    foreach ($dias as $mes_posicionado => $dia) {
                        if ($mes_selecionado == $mes_posicionado) {
                            foreach ($dia as $val_dia) {
                                echo "<th>{$val_dia}</th>";
                            }                            
                        }                        
                    }
                    ?>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $dias_total_array = array();

                foreach ($resultado["cliente_admin"] as $cliente_admin => $dias_array) {
                    $total_cliente_admin = 0;

                    echo "
                        <tr>
                            <td>{$cliente_admin}</td>
                    ";

                    foreach ($dias as $mes_posicionado => $val_dia) {
                        if ($mes_selecionado == $mes_posicionado) {
                            foreach ($val_dia as $dia) {
                                $qtde_os                = (int) $dias_array[$dia];
                                $total_cliente_admin    += $qtde_os;
                                $dias_total_array[$dia] += $qtde_os;

                                echo "<td class='tac' >{$qtde_os}</td>";
                            }
                        }
                    }

                    echo "
                            <td class='tac' >{$total_cliente_admin}</td>
                        </tr>
                    ";
                }
                ?>
                <tr class="titulo_coluna" >
                    <th>Total</th>
                    <?php
                    $dias_total = 0;

                    foreach ($dias_total_array as $qtde_os) {
                        $dias_total += $qtde_os;
                        echo "<th>{$qtde_os}</th>";
                    }
                    ?>
                    <th><?=$dias_total?></th>
                </tr>
            </tbody>
        </table>
    <?php
        }
    }
    if (count($resultado["finalizada"]) > 0) {
        foreach ($dias as $mes_posicionado => $array_dias) {
?>
        <br />
        <table class="table table-bordered relatorio" >
            <caption class="titulo_tabela" >Ordens de Serviço finalizadas <?php if(count($dias) > 1){ echo "- Mês: {$mes_posicionado}"; } ?></caption>
            <thead>
                <tr class="titulo_coluna" >
                    <th>Unidade de Négocio</th>
                    <th>Família</th>
                    <?php
                    foreach ($array_dias as $dia) {
                    ?>
                        <th><?=$dia?></th>
                    <?php
                    }
                    ?>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $dias_total_array = array();

                foreach ($resultado["finalizada"] as $unidade_negocio => $familias) {
                    $unidade_negocio_total_array = array();
                    $rowspan = 2 + count($familias);

                    echo "
                        <tr>
                            <td rowspan='{$rowspan}' >{$unidade_negocio}</td>
                        </tr>
                    ";

                    foreach ($familias as $familia => $array_mes) {
                        $total_familia = 0;

                        echo "
                            <tr>
                                <td>{$familia}</td>
                        ";

                        foreach ($array_mes as $mes_selecionado => $array_dia_recebidas) {
                            if ($mes_selecionado == $mes_posicionado) {
                                foreach ($array_dias as $dia) {
                                    $qtde_os = $array_dia_recebidas[$dia];
                                    if (!isset($qtde_os)) { $qtde_os = 0; }
                                    echo "<td class='tac' >{$qtde_os}</td>";
                                    $unidade_negocio_total_array[$dia] += $qtde_os;
                                    $total_familia += $qtde_os;
                                }
                            }
                        }
                        if ($total_familia == 0) {
                            foreach ($array_dias as $dia) {
                                echo "<td class='tac' >0</td>";
                            }
                        }                        

                        echo "
                                <td class='tac' >{$total_familia}</td>
                            </tr>
                        ";
                    }

                    echo "
                        <tr class='info' >
                            <td><strong>Total</strong></td>
                    ";

                    $dias_total = 0;

                    foreach ($unidade_negocio_total_array as $dia => $qtde_os) {
                        echo "<td class='tac' ><strong>{$qtde_os}</strong></td>";

                        $dias_total_array[$dia] += $qtde_os;
                        $dias_total += $qtde_os;
                    }

                    echo "
                            <td class='tac' ><strong>{$dias_total}</strong></td>
                        </tr>
                    ";
                }

                echo "
                    <tr class='titulo_coluna' >
                        <th colspan='2' >Total</th>
                ";

                $dias_total = 0;

                foreach ($dias_total_array as $qtde_os) {
                    echo "<th>{$qtde_os}</th>";

                    $dias_total += $qtde_os;
                }

                echo "
                        <th>{$dias_total}</th>
                    </tr>
                ";
                ?>
            </tbody>
        </table>
<?php
        }
    }
    $sql = "
        SELECT dia, mes, familia, COUNT(os) AS qtde_os
        FROM {$tabela}
        GROUP BY dia, mes, familia
        ORDER BY dia ASC, familia
    ";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {
        $resultado = array();
        $dias = array();
        $mes  = array();

        array_map(function($row) {
            global $resultado, $dias, $mes;

            $dias[$row["mes"]][] = $row["dia"];
            if (!in_array($row["mes"], $mes)) {
                $mes[] = $row["mes"];
            }

            $resultado[$row["familia"]][$row["dia"]][$row["mes"]] = $row["qtde_os"];
        }, pg_fetch_all($res));

        foreach ($mes as $mes_selecionado) {
            $dias[$mes_selecionado] = array_unique($dias[$mes_selecionado]);
        ?>
        <br />
        <table class="table table-bordered relatorio" >
            <caption class="titulo_tabela" >Total de Ordens de Serviço recebidas <?php if (count($mes) > 1) { echo "- Mês: {$mes_selecionado}"; } ?></caption>
            <thead>
                <tr class="titulo_coluna" >
                    <th>Família</th>
                    <?php
                    foreach ($dias as $mes_posicionado => $dia) {
                        if ($mes_selecionado == $mes_posicionado) {
                            foreach ($dia as $val_dia) {
                                echo "<th>{$val_dia}</th>";
                            }                            
                        }                        
                    }
                    ?>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $dias_total_array = array();

                foreach ($resultado as $familia => $dias_array) {
                    $total_familia = 0;

                    echo "
                        <tr>
                            <td>{$familia}</td>
                    ";

                    foreach ($dias as $mes_posicionado => $val_dia) {
                        if ($mes_selecionado == $mes_posicionado) {
                            foreach ($val_dia as $dia) {
                                $qtde_os                = (int) $dias_array[$dia][$mes_selecionado];
                                $total_familia          += $qtde_os;
                                $dias_total_array[$dia] += $qtde_os;

                                echo "<td class='tac' >{$qtde_os}</td>";
                            }
                        }
                    }

                    echo "
                            <td class='tac' >{$total_familia}</td>
                        </tr>
                    ";
                }
                ?>
                <tr class="titulo_coluna" >
                    <th>Total</th>
                    <?php
                    $dias_total = 0;

                    foreach ($dias_total_array as $qtde_os) {
                        $dias_total += $qtde_os;
                        echo "<th>{$qtde_os}</th>";
                    }
                    ?>
                    <th><?=$dias_total?></th>
                </tr>
            </tbody>
        </table>
    <?php
        }
    }

    $html = ob_get_contents();

    ob_end_flush();
    ob_clean();

    $xls  = "relatorio-fora-garantia-centro-distribuidor-{$login_fabrica}-{$login_admin}-".date("YmdHi").".xls";
    $file = fopen("/tmp/".$xls, "w");
    fwrite($file, $html);
    fclose($file);
    system("mv /tmp/{$xls} xls/{$xls}");
    ?>

    <br />

    <p class="tac no-print" >
        <button type="button" class="btn btn-success download-xls" data-xls="<?=$xls?>" ><i class="icon-download-alt icon-white" ></i> Download XLS</button>
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

@media print {
    .no-print {
        display: none;
    }

    table, table tr, table td, table th {
        border: 1px solid;
        border-collapse: collapse;
    }

    table td, table th {
        padding: 10px;
    }

    table {
        width: 100%;
    }
}

@media screen {
    table.relatorio {
        width: 1280px;
        margin: 0 auto;
    }
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
