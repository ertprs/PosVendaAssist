<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios = "gerencia";
include 'autentica_admin.php';
include 'funcoes.php';

$title = "Relatório de Garantia";
$layout_menu = "gerencia";

include "cabecalho_new.php";

if ($_POST) {
    $ano              = $_POST["ano"];
    $mes              = $_POST["mes"];
    $cliente_admin    = $_POST["cliente_admin"];
    $cliente_admin    = implode(",", $cliente_admin);
    $posto            = $_POST["posto"];
    $posto            = implode(",", $posto);
    $classificacao    = $_POST["classificacao"];
    $classificacao    = implode(",", $classificacao);
    $tipo_atendimento = $_POST["tipo_atendimento"];
    $tipo_atendimento = implode(",", $tipo_atendimento);
    $tipo             = $_POST["tipo"]; // posto ou cliente_admin

    if (empty($ano)) {
        $msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
        $msg_erro["campos"][] = "ano";
    }

    if (empty($msg_erro["msg"])) {
        if (!empty($mes)) {
            $whereMes = "AND DATE_PART('MONTH', tbl_os.data_abertura) = {$mes}";
        }

        if ($tipo == "cliente_admin" && !empty($cliente_admin)) {
            $whereClienteAdmin = "AND tbl_cliente_admin.cliente_admin IN ({$cliente_admin})";
        }

        if ($tipo == "posto" && !empty($posto)) {
            $whereClienteAdmin = "AND tbl_posto_fabrica.posto IN ({$posto})";
        }

        if (!empty($tipo_atendimento)) {
            $whereTipoAtendimento = "AND tbl_os.tipo_atendimento IN ({$tipo_atendimento})";
        }

        if (!empty($classificacao)) {
            $distinctOnClassificacao = "DISTINCT ON (tbl_os.os)";
            $innerJoinClassificacao = "
                INNER JOIN tbl_os_defeito_reclamado_constatado ON tbl_os_defeito_reclamado_constatado.os = tbl_os.os
                INNER JOIN tbl_solucao ON tbl_solucao.solucao = tbl_os_defeito_reclamado_constatado.solucao AND tbl_solucao.fabrica = {$login_fabrica}
                INNER JOIN tbl_classificacao ON tbl_classificacao.classificacao = tbl_solucao.classificacao AND tbl_classificacao.fabrica = {$login_fabrica}
            ";
            $whereClassificacao = "AND tbl_classificacao.classificacao IN ({$classificacao})";
            $orderByClassificacao = "ORDER BY tbl_os.os";
        }

        $sql = "
            SELECT 
                {$distinctOnClassificacao}
                tbl_{$tipo}.nome AS {$tipo},
                DATE_PART('MONTH', tbl_os.data_abertura) AS mes,
                ARRAY_TO_STRING(
                    ARRAY(
                        SELECT tbl_classificacao.classificacao
                        FROM tbl_os_defeito_reclamado_constatado
                        INNER JOIN tbl_solucao ON tbl_solucao.solucao = tbl_os_defeito_reclamado_constatado.solucao AND tbl_solucao.fabrica = {$login_fabrica}
                        INNER JOIN tbl_classificacao ON tbl_classificacao.classificacao = tbl_solucao.classificacao AND tbl_classificacao.fabrica = {$login_fabrica}
                        WHERE tbl_os_defeito_reclamado_constatado.os = tbl_os.os
                        AND tbl_os_defeito_reclamado_constatado.solucao IS NOT NULL
                    ), 
                ',') AS solucao,
                tbl_status_checkpoint.descricao AS status,
                (CURRENT_DATE - tbl_os.data_abertura) AS dias_pendente,
                tbl_os.finalizada
            FROM tbl_os
            INNER JOIN tbl_hd_chamado ON tbl_hd_chamado.hd_chamado = tbl_os.hd_chamado AND tbl_hd_chamado.fabrica = {$login_fabrica}
            INNER JOIN tbl_cliente_admin ON tbl_cliente_admin.cliente_admin = tbl_hd_chamado.cliente_admin AND tbl_cliente_admin.fabrica = {$login_fabrica}
            INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
            INNER JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
            INNER JOIN tbl_status_checkpoint ON tbl_status_checkpoint.status_checkpoint = tbl_os.status_checkpoint
            INNER JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = {$login_fabrica} AND tbl_tipo_atendimento.fora_garantia IS NOT TRUE
            {$innerJoinClassificacao}
            WHERE tbl_os.fabrica = {$login_fabrica}
            AND DATE_PART('YEAR', tbl_os.data_abertura) = {$ano}
            AND (tbl_tipo_atendimento.grupo_atendimento != 'P' OR tbl_tipo_atendimento.grupo_atendimento IS NULL)
            {$whereMes}
            {$whereClienteAdmin}
            {$whereClassificacao}
            {$whereTipoAtendimento}
            {$orderByClassificacao}
        ";
        
        $res = pg_query($con, $sql);

        if (!pg_num_rows($res)) {
            $msg_erro["msg"][] = "Nenhum resultado encontrado";
        } else {
            $resultado = array();
            $solucoes  = array();

            $max_dias_pendentes = 0;

            array_map(function($row) use($tipo) {
                global $max_dias_pendentes, $resultado, $solucoes;

                if (!empty($row["solucao"])) {
                    $row["solucao"]                      = explode(",", $row["solucao"]);
                    $solucoes                            = array_merge($solucoes, $row["solucao"]);
                    $resultado["solucao"][$row[$tipo]][] = $row;
                }

                if (!empty($row["finalizada"])) {
                    $resultado["mes"][$row[$tipo]][$row["mes"]][] = $row;
                } else {
                    $resultado["status"][$row[$tipo]][$row["status"]][$row["dias_pendente"]][] = $row;

                    if ($row["dias_pendente"] > $max_dias_pendentes) {
                        $max_dias_pendentes = $row["dias_pendente"];
                    }
                }
            }, pg_fetch_all($res));

            $solucoes = array_unique($solucoes);
        }
    }
}

if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error" >
        <strong><?=implode("<br />", $msg_erro["msg"])?></strong>
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
        <div class="span1" ></div>
        <div class="span2" >
            <div class="control-group <?= (in_array("ano", $msg_erro["campos"])) ? "error" : ""; ?>" >
                <label class="control-label" for="ano" >Ano</label>
                <div class="controls controls-row" >
                    <div class="span12" >
                        <h5 class="asteristico" >*</h5>
                        <select id="ano" name="ano" class="span12" >
                            <option value="" >Selecione</option>
                            <?php
                            $sql = "SELECT DATE_PART('YEAR', MIN(data_abertura)) FROM tbl_os WHERE fabrica = {$login_fabrica}";
                            $res = pg_query($con, $sql);

                            if (pg_num_rows($res) > 0) {
                                $ano = pg_fetch_result($res, 0, 0);
                            } else {
                                $ano = date("Y");
                            }

			    for ($i = $ano; $i <= date("Y"); $i++) { 
                                $selected = ($i == getValue("ano")) ? "selected" : "";

                                echo "<option value='{$i}' {$selected} >{$i}</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="span2" >
            <div class="control-group" >
                <label class="control-label" for="mes" >Mês</label>
                <div class="controls controls-row" >
                    <div class="span12" >
                        <select id="mes" name="mes" class="span12" >
                            <option value="" >Selecione</option>
                            <?php
                            foreach ($meses_idioma["pt-br"] as $mes_numero => $mes_nome) {
                                $selected = ($mes_numero == getValue("mes")) ? "selected" : "";

                                echo "<option value='{$mes_numero}' {$selected} >{$mes_nome}</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="span4" >
            <div class="control-group" >
                <label class="control-label" >Tipo</label>
                <div class="controls controls-row" >
                    <div class="span12 radio" >
                        <label class="radio" >
                            <input type="radio" name="tipo" value="cliente_admin" checked /> Por Cliente Admin
                        </label>
                        <label class="radio" >
                            <input type="radio" name="tipo" value="posto" <?=(getValue("tipo") == "posto") ? "checked" : ""?> /> Por Posto Autorizado
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row-fluid" >
        <div class="span1" ></div>
        <div class="span4 tipo-cliente-admin" >
            <div class="control-group" >
                <label class="control-label" for="cliente_admin" >Cliente Admin</label>
                <div class="controls controls-row" >
                    <div class="span12" >
                        <select id="cliente_admin" name="cliente_admin[]" class="span12" multiple="multiple">
                            <option value="" >Selecione</option>
                            <?php
                            $sql = "
                                SELECT cliente_admin, nome
                                FROM tbl_cliente_admin
                                WHERE fabrica = {$login_fabrica}
                                ORDER BY nome
                            ";
                            $res = pg_query($con, $sql);

                            while ($row = pg_fetch_object($res)) {
                                $selected = (in_array($row->cliente_admin, getValue("cliente_admin"))) ? "selected" : "";
                                echo "<option value='{$row->cliente_admin}' {$selected} >{$row->nome}</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="span6 tipo-posto" >
            <div class="control-group" >
                <label class="control-label" for="posto" >Posto Autorizado</label>
                <div class="controls controls-row" >
                    <div class="span12" >
                        <select id="posto" name="posto[]" class="span12" multiple="multiple">
                            <option value="" >Selecione</option>
                            <?php
                            $sql = "
                                SELECT tbl_posto.posto, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
                                FROM tbl_posto_fabrica
                                INNER JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
                                WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
                                ORDER BY tbl_posto.nome
                            ";
                            $res = pg_query($con, $sql);

                            while ($row = pg_fetch_object($res)) {
                                $selected = (in_array($row->posto, getValue("posto"))) ? "selected" : "";
                                echo "<option value='{$row->posto}' {$selected} >{$row->codigo_posto} - {$row->nome}</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row-fluid" >
        <div class="span1" ></div>
        <div class="span3" >
            <div class="control-group" >
                <label class="control-label" for="classificacao" >Classificação</label>
                <div class="controls controls-row" >
                    <div class="span12" >
                        <select id="classificacao" name="classificacao[]" class="span12" multiple="multiple">
                            <option value="" >Selecione</option>
                            <?php
                            $sql = "
                                SELECT classificacao, descricao
                                FROM tbl_classificacao
                                WHERE fabrica = {$login_fabrica}
                                ORDER BY descricao
                            ";
                            $res = pg_query($con, $sql);

                            while ($row = pg_fetch_object($res)) {
                                $selected = (in_array($row->classificacao, getValue("classificacao"))) ? "selected" : "";
                                echo "<option value='{$row->classificacao}' {$selected} >{$row->descricao}</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="span3" >
            <div class="control-group" >
                <label class="control-label" for="tipo_atendimento" >Tipo de Atendimento</label>
                <div class="controls controls-row" >
                    <div class="span12" >
                        <select id="tipo_atendimento" name="tipo_atendimento[]" class="span12" multiple="multiple">
                            <option value="" >Selecione</option>
                            <?php
                            $sql = "
                                SELECT tipo_atendimento, descricao
                                FROM tbl_tipo_atendimento
                                WHERE fabrica = {$login_fabrica}
                                AND (grupo_atendimento != 'P' OR grupo_atendimento IS NULL)
                                ORDER BY descricao
                            ";
                            $res = pg_query($con, $sql);

                            while ($row = pg_fetch_object($res)) {
                                $selected = (in_array($row->tipo_atendimento, getValue("tipo_atendimento"))) ? "selected" : "";
                                echo "<option value='{$row->tipo_atendimento}' {$selected} >{$row->descricao}</option>";
                            }
                            ?>
                        </select>
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

    if ($tipo == "posto") {
        $titulo = "Posto Autorizado";
    } else {
        $titulo = "Cliente Admin";
    }

    /**
     * Solução
     */
    if (count($resultado["solucao"]) > 0) {
    ?>
        <br />
        <table class="table table-bordered relatorio" >
            <caption class="titulo_tabela" >Ordens de Serviço por <?=$titulo?></caption>
            <thead>
                <tr class="titulo_coluna" >
                    <th>&nbsp;</th>
                    <?php
                    foreach ($solucoes as $classificacao) {
                        $sql = "SELECT descricao FROM tbl_classificacao WHERE fabrica = {$login_fabrica} AND classificacao = {$classificacao}";
                        $res = pg_query($con, $sql);

                        echo "<th>".pg_fetch_result($res, 0, "descricao")."</th>";
                    }
                    ?>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $totalGeral = 0;
                foreach ($resultado["solucao"] as $nome => $value) {
                ?>
                    <tr>
                        <th class="titulo_coluna" nowrap ><?=$nome?></th>
                        <?php
                        $total = 0;

                        foreach ($solucoes as $classificacao) {
                            $total_classificacao = count(array_filter($value, function($row) use($classificacao) {
                                if (in_array($classificacao, $row["solucao"])) {
                                    return true;
                                }
                            }));

                            echo "<td>{$total_classificacao}</td>";

                            $total += $total_classificacao;
                            $totalColuna[$classificacao] += $total_classificacao;
                        }
                        $totalGeral += $total;
                        ?>
                        <th class="titulo_coluna" ><?=$total?></th>
                    </tr>
                <?php
                }
                ?>
                <tr>
                    <th class="titulo_coluna" nowrap>Total Geral</th>
                    <? foreach ($totalColuna as $coluna) { ?>
                        <td class="titulo_coluna"><?= $coluna; ?></td>
                    <? } ?>
                    <th class="titulo_coluna"><?= $totalGeral; ?></th>
                </tr>
            </tbody>
        </table>
    <?php
    }
 
    /**
     * Mensal
     */
    if (count($resultado["mes"]) > 0) {
    ?>
        <br />
        <table class="table table-bordered relatorio" >
            <caption class="titulo_tabela" >Ordens de Serviço Finalizadas</caption>
            <thead>
                <tr class="titulo_coluna" >
                    <th>&nbsp;</th>
                    <?php
                    foreach ($meses_idioma["pt-br"] as $mes_numero => $mes_nome) {
                        if (strlen(getValue("mes")) > 0 && $mes_numero != getValue("mes")) {
                            continue;
                        }

                        echo "<th>{$mes_nome}</th>";
                    }
                    ?>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $totalGeral = 0;
                $totalColuna = array();
                foreach ($resultado["mes"] as $nome => $value) {
                ?>
                    <tr>
                        <th class="titulo_coluna" nowrap ><?=$nome?></th>
                        <?php
                        $total = 0;

                        foreach ($meses_idioma["pt-br"] as $mes_numero => $mes_nome) {
                            if (strlen(getValue("mes")) > 0 && $mes_numero != getValue("mes")) {
                                continue;
                            }

                            if (!isset($total_mes[$mes_numero])) {
                                $total_mes[$mes_numero] = 0;
                            }

                            echo "<td class='tac' >".count($value[$mes_numero])."</td>";
                            $total += count($value[$mes_numero]);
                            $totalColuna[$mes_numero] += count($value[$mes_numero]);
                        }
                        $totalGeral += $total;
                        ?>
                        <th class="titulo_coluna" ><?=$total?></th>
                    </tr>
                <?php
                }
                ?>
                <tr>
                    <th class="titulo_coluna" nowrap>Total Geral</th>
                    <? foreach ($totalColuna as $coluna) { ?>
                        <th class="titulo_coluna"><?= $coluna; ?></th>
                    <? } ?>
                    <th class="titulo_coluna"><?= $totalGeral; ?></th>
                </tr>
            </tbody>
        </table>
    <?php
    }

    /**
     * Status
     */
    if (count($resultado["status"]) > 0) {
    ?>
        <br />
        <table class="table table-bordered relatorio">
            <caption class="titulo_tabela" >Ordens de Serviço Pendentes</caption>
            <thead>
                <tr class="titulo_coluna" >
                    <th rowspan="2" >&nbsp;</th>
                    <th rowspan="2" >Status</th>
                    <th colspan="<?=$max_dias_pendentes + 2?>" >Dias Pendentes</th>
                </tr>
                <tr class="titulo_coluna" >
                    <?php
                    for ($i = 0; $i <= $max_dias_pendentes; $i++) { 
                        echo "<th>{$i}</th>";
                    }
                    ?>
                </tr>
            </thead>
            <tbody>
                <?php
                $totalGeral = 0;
                $totalColuna = array();
                foreach ($resultado["status"] as $nome => $status_array) {
                ?>
                    <tr>
                        <th class="titulo_coluna" nowrap rowspan="<?=count($status_array) + 1?>" ><?=$nome?></th>
                    </tr>
                    <?php
                    foreach ($status_array as $status => $os_array) {
                    ?>
                        <tr>
                            <td><?=$status?></td>
                            <?php
                            for ($i = 0; $i <= $max_dias_pendentes; $i++) { 
                                echo "<td class='tac' >".count($os_array[$i])."</td>";
                                $totalColuna[$i] += count($os_array[$i]);
                            }
                            ?>
                        </tr>
                    <?php
                    }
                }
                ?>
                <tr>
                    <th class="titulo_coluna" colspan="2" nowrap>Total Geral</th>
                    <? foreach ($totalColuna as $coluna) { ?>
                        <th class="titulo_coluna"><?= $coluna; ?></th>
                    <? } ?>
                </tr>
            </tbody>
        </table>
    <?php
    }

    $html = ob_get_contents();

    ob_end_flush();
    ob_clean();

    $xls  = "relatorio-garantia-por-cliente-admin-{$login_fabrica}-{$login_admin}-".date("YmdHi").".xls";
    $file = fopen("/tmp/".$xls, "w");
    fwrite($file, $html);
    fclose($file);
    system("mv /tmp/{$xls} xls/{$xls}");
    ?>

    <br />

    <p class="tac" >
        <button type="button" class="btn btn-success download-xls" data-xls="<?=$xls?>" ><i class="icon-download-alt icon-white" ></i> Download XLS</button>
    </p>

<?php
}

$plugins = array(
    "select2"
);

include "plugin_loader.php";
?>

<style>

table.relatorio {
    width: 1300px;
    margin: 0 auto;
}

</style>

<script>

$("select").select2();

$("button.download-xls").on("click", function() {
    var xls = $(this).data("xls");

    window.open("xls/"+xls);
});

$("input[name=tipo]").on("change", function() {
    if ($("input[name=tipo]:checked").val() == "posto") {
        $(".tipo-cliente-admin").hide();
        $(".tipo-posto").show();
    } else {
        $(".tipo-posto").hide();
        $(".tipo-cliente-admin").show();
    }
});

if ($("input[name=tipo]:checked").val() == "posto") {
    $(".tipo-cliente-admin").hide();
    $(".tipo-posto").show();
} else {
    $(".tipo-posto").hide();
    $(".tipo-cliente-admin").show();
}

</script>

<br />

<?php 
include "rodape.php"; 
?>
