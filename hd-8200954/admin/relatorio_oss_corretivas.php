<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios = "gerencia";
include 'autentica_admin.php';
include 'funcoes.php';

$title = "Relatório de OS Corretiva Garantia";
$layout_menu = "gerencia";

include "cabecalho_new.php";

if ($_POST) {
    $data_inicial    = $_POST["data_inicial"];
    $data_final      = $_POST["data_final"];
    $tipo            = $_POST["tipo"]; // null, pendente ou finalizada

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
        $whereTipo = '';
        switch ($tipo) {
            case 'pendente':
                $whereTipo = "AND tbl_os.finalizada IS NULL";
                break;
            
            case 'finalizada':
                $whereTipo = "AND tbl_os.finalizada IS NOT NULL";
                break;
        }
        $tabela = "temp_os_corretivas_{$login_admin}_{$login_fabrica}";

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
                tbl_distribuidor_sla.unidade_negocio||' - '||tbl_cidade.nome AS unidade_negocio,
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
            LEFT JOIN tbl_distribuidor_sla_posto ON tbl_distribuidor_sla_posto.posto = tbl_posto.posto AND tbl_distribuidor_sla_posto.fabrica = {$login_fabrica}
            LEFT JOIN tbl_distribuidor_sla ON tbl_distribuidor_sla.distribuidor_sla = tbl_distribuidor_sla_posto.distribuidor_sla AND tbl_distribuidor_sla.fabrica = {$login_fabrica} AND tbl_os_campo_extra.campos_adicionais LIKE '%\"unidadeNegocio\":\"' || tbl_distribuidor_sla.unidade_negocio || '\"%'
            INNER JOIN tbl_cidade ON tbl_cidade.cidade = tbl_distribuidor_sla.cidade
            INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
            INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto AND tbl_produto.fabrica_i = {$login_fabrica}
            INNER JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia AND tbl_familia.fabrica = {$login_fabrica}
            INNER JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = {$login_fabrica}
            LEFT JOIN tbl_tecnico_agenda ON tbl_tecnico_agenda.os = tbl_os.os AND tbl_tecnico_agenda.fabrica = {$login_fabrica}
            INNER JOIN tbl_status_checkpoint ON tbl_status_checkpoint.status_checkpoint = tbl_os.status_checkpoint
            INNER JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = {$login_fabrica}
            LEFT JOIN tbl_hd_chamado ON tbl_hd_chamado.hd_chamado = tbl_os.hd_chamado AND tbl_hd_chamado.fabrica = {$login_fabrica}
            LEFT JOIN tbl_hd_chamado_cockpit ON tbl_hd_chamado_cockpit.hd_chamado = tbl_hd_chamado.hd_chamado
            LEFT JOIN tbl_cliente_admin ON tbl_cliente_admin.cliente_admin = tbl_hd_chamado.cliente_admin AND tbl_cliente_admin.fabrica = {$login_fabrica}            
            WHERE tbl_os.fabrica = {$login_fabrica}
            AND tbl_os.data_abertura between '{$data_inicial} 00:00:00' and '{$data_final} 23:59:59'
            AND LOWER(tbl_tipo_atendimento.descricao) = 'garantia corretiva'
            AND tbl_posto.posto <> 6359
            AND tbl_hd_chamado_cockpit.hd_chamado IS NULL
            {$whereTipo};

            SELECT * FROM {$tabela};
            ";
        $res = pg_query($con, $sql);

        if (!pg_num_rows($res)) {
            $msg_erro["msg"][] = "Nenhum resultado encontrado";
        }        
    }
}

if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error no-print" >
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
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
        <div class="span6" >
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

    <br />

    <p class="tac" >
        <button type="submit" name="pesquisa" class="btn" >Pesquisar</button>
    </p>

    <br />
</form>

</div>
<?php
if($_POST) {
    $sql = "
        SELECT dia, mes, unidade_negocio, familia, tipo_posto, tecnico, status, COUNT(os) AS qtde_os
        FROM {$tabela}
        WHERE finalizada IS NULL
        GROUP BY mes, dia, unidade_negocio, familia, tipo_posto, tecnico, status
        ORDER BY dia ASC, unidade_negocio, familia, tipo_posto, tecnico, status
    ";

    $res = pg_query($con, $sql);

    ob_start();
    if (pg_num_rows($res) > 0) {
        $resultado = array();
        $dias      = array();

        array_map(function($row) {
            global $resultado, $dias;

            $dias[$row["mes"]][] = $row["dia"];
            $dias[$row["mes"]] = array_unique($dias[$row["mes"]]);

            $resultado["familia"][$row["unidade_negocio"]][$row["familia"]][$row["mes"]][$row["dia"]] += $row["qtde_os"];
            $resultado["tecnico"][$row["tipo_posto"]][$row["tecnico"]][$row["familia"]][$row["dia"]] += $row["qtde_os"];
            $resultado["status"][$row["unidade_negocio"]][$row["familia"]][$row["status"]][$row["mes"]][$row["dia"]] += $row["qtde_os"];
        }, pg_fetch_all($res));
        foreach ($dias as $mes_posicionado => $array_dias) {
?>
        <table class="table table-bordered relatorio" >
            <caption class="titulo_tabela" >Ordens de Serviço pendentes por família <?php if(count($dias) > 1){ echo "- Mês: {$mes_posicionado}"; } ?></caption>
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
                
                foreach ($resultado["familia"] as $unidade_negocio => $familias) {
                    $unidade_negocio_dias_total_array = array();
                    $rowspan                          = 2 + count($familias);

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
                                    $qtde_os = (int) $array_dia_recebidas[$dia];
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
<?php
        }
	}
}
    if (count($resultado["tecnico"]) > 0) {
?>
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
        $total_geral = 0;
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
                    $total_geral += $total_familia;
                }                    
            }
        }
        echo "<tr class='titulo_coluna'><th colspan='3'><strong>Total</strong></th>";
        echo "<th><strong>{$total_geral}</strong></th></tr>";
        ?>
        </tbody>
    </table>
    <br />
        <?php }foreach ($dias as $mes_posicionado => $array_dias) { ?>
        <table class="table table-bordered relatorio" >
            <caption class="titulo_tabela" >Ordens de Serviço pendentes por status <?php if (count($dias) > 1) { echo "- Mês: {$mes_posicionado}"; } ?></caption>
            <thead>
                <tr class="titulo_coluna" >
                    <th>Unidade de Négocio</th>
                    <th>Família</th>
                    <th>Status</th>
                    <?php                    
                    foreach ($array_dias as $dia) {
                        echo "<th>{$dia}</th>";
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

                         foreach ($status_array as $status => $array_mes) {
                            $total_status = 0;

                            echo "
                                <tr>
                                    <td>{$status}</td>
                            ";

                            $Mes_percorrido = 0;
                            foreach ($array_mes as $mes_selecionado => $array_dia_recebidas) {
                                if ($mes_selecionado == $mes_posicionado) {
                                    $Mes_percorrido = 1;
                                    foreach ($array_dias as $dia) {
                                        $qtde_os                        = (int) $array_dia_recebidas[$dia];
                                        $total_status                   += $qtde_os;
                                        $familia_dias_total_array[$dia] += $qtde_os;

                                        echo "<td class='tac' >{$qtde_os}</td>";
                                    }
                                }
                            }
                            if ($Mes_percorrido == 0) {
                                foreach ($array_dias as $dia) {
                                    echo "<td class='tac' ></td>";
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

        array_map(function($row) {
            global $resultado, $dias;

            $dias[$row["mes"]][] = $row["dia_consertada"];
            $dias[$row["mes"]] = array_unique($dias[$row["mes"]]);

            $resultado[$row["unidade_negocio"]][$row["familia"]][$row["mes"]][$row["dia_consertada"]] += $row["qtde_os"];
        }, pg_fetch_all($res));
        foreach ($dias as $mes_posicionado => $array_dias) {
?>        
        <table class="table table-bordered relatorio" >
            <caption class="titulo_tabela" >Ordens de Serviço consertadas e não finalizadas <?php if (count($dias) > 1) { echo "- Mês: {$mes_posicionado}"; } ?></caption>
            <thead>
                <tr class="titulo_coluna" >
                    <th>Unidade de Négocio</th>
                    <th>Família</th>
                    <?php
                    foreach ($array_dias as $dia) {
                        echo "<th>{$dia}</th>";
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

                    foreach ($familias as $familia => $array_mes) {
                        $total_familia = 0;

                        echo "
                            <tr>
                                <td>{$familia}</td>
                        ";

                        foreach ($array_mes as $mes_selecionado => $array_dia_recebidas) {
                            if ($mes_selecionado == $mes_posicionado) {
                                foreach ($array_dias as $dia) {
                                    $qtde_os = (int) $array_dia_recebidas[$dia];
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
            $dias[$row["mes"]] = array_unique($dias[$row["mes"]]);

            $resultado["tecnico"][$row["familia"]][$row["tecnico"]] += $row["qtde_os"];
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
                    $rowspan                  = 1 + count($tecnicos);

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
                }
                ?>
                <tr class="titulo_coluna" >
                    <th colspan="2" >Total</th>
                    <th><?=$total_todos_tecnico?></th>
                </tr>
            </tbody>
        </table>
<?php
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
                                    echo "<td class='tac' >{$qtde_os}</td>";
                                    $unidade_negocio_total_array[$dia] += $qtde_os;
                                    $total_familia += $qtde_os;
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

    array_map(function($row) {
        global $resultado, $dias;

        $dias[$row["mes"]][] = $row["dia"];
        $dias[$row["mes"]] = array_unique($dias[$row["mes"]]);

        $resultado[$row["familia"]][$row["mes"]][$row["dia"]] = $row["qtde_os"];
    }, pg_fetch_all($res));    
    foreach ($dias as $mes_posicionado => $array_dias) {
    ?>
    <br />
    <table class="table table-bordered relatorio" >
        <caption class="titulo_tabela" >Ordens de Serviço recebidas <?php if(count($dias) > 1){ echo "- Mês: {$mes_posicionado}"; } ?></caption>
        <thead>
            <tr class="titulo_coluna" >
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
            $unidade_negocio_total_array = array();

            foreach ($resultado as $familia => $array_mes) {
                $total_familia = 0;                

                echo "
                    <tr>
                        <td>{$familia}</td>
                ";

                foreach ($array_mes as $mes_selecionado => $array_dia_recebidas) {
                    if ($mes_selecionado == $mes_posicionado) {
                        foreach ($array_dias as $dia) {
                            $qtde_os = $array_dia_recebidas[$dia];
                            echo "<td class='tac' >{$qtde_os}</td>";
                            $unidade_negocio_total_array[$dia] += $qtde_os;
                            $total_familia += $qtde_os;
                        }
                    }
                }

                echo "
                        <td class='tac' >{$total_familia}</td>
                    </tr>
                ";
            }

            if (count($resultado) > 1) {
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

                echo "
                    <tr class='titulo_coluna' >
                        <th colspan='1' >Total</th>
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
            }else{
                $colspan = count($array_dias) + 2;
                echo "
                    <tr class='titulo_coluna' >
                        <th colspan='{$colspan}' ></th>
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

    $xls  = "relatorio-oss-corretivas-{$login_fabrica}-{$login_admin}-".date("YmdHi").".xls";
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
