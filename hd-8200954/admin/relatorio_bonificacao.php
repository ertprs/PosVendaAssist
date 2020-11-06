<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

$title = "RELATÓRIO BÔNUS POSTO";
$layout_menu = "callcenter";
$admin_privilegios="call_center";

$msg_erro = array();
$result = array();
$pesquisou = false;

if ($_POST["pesquisar"] == "Pesquisar") {
    $pesquisou = true;
    $codigo_posto = $_POST["codigo_posto"];
    $nome_posto = $_POST["nome_posto"];
    $mes = $_POST["filtro_mes"];
    $csv = $_POST["csv"];

    $cond_posto = '';
    $cond_mes = '';

    if (!empty($codigo_posto) and !empty($nome_posto)) {
        $sql_posto = "SELECT tbl_posto.posto
            FROM tbl_posto
            JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
              AND fabrica = $login_fabrica
            WHERE codigo_posto = '{$codigo_posto}'
            AND nome = '{$nome_posto}'";
        $qry_posto = pg_query($con, $sql_posto);

        if (pg_num_rows($qry_posto) == 0) {
            $msg_erro["msg"][] = "Posto não encontrado";
            $msg_erro["campos"][] = "posto";
        } else {
            $posto = pg_fetch_result($qry_posto, 0, 'posto');

            $cond_posto = " AND tbl_os.posto = $posto ";
        }
    }

    if (empty($mes)) {
        $msg_erro["msg"][] = "Selecione um mês para efetuar a pesquisa";
        $msg_erro["campos"][] = "filtro_mes";
    } else {
        $ano = date('Y');
        $cond_mes = " AND tbl_os.data_abertura
          BETWEEN '{$ano}-{$mes}-01 00:00:00'
          AND ('{$ano}-{$mes}-01 23:59:59'::timestamp + interval '1 month') - interval '1 day'";

        $sql_os = "SELECT codigo_posto,
                        nome AS posto_nome,
                        tbl_os.os,
                        tbl_os.posto,
                        campos_adicionais
                    FROM tbl_os
                    JOIN tbl_os_campo_extra ON tbl_os_campo_extra.os = tbl_os.os
                    JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto
                    JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto
                        AND tbl_posto_fabrica.fabrica = $login_fabrica
                    WHERE JSON_FIELD('bonificacao', campos_adicionais) <> ''
                    $cond_mes
                    $cond_posto ORDER BY codigo_posto";
        $qry_os = pg_query($con, $sql_os);


        while ($fetch = pg_fetch_assoc($qry_os)) {
            $res_posto_id = $fetch["posto"];
            $res_posto = $fetch["codigo_posto"] . ' - ' . $fetch["posto_nome"];

            $ca = json_decode($fetch["campos_adicionais"], true);
            $bonificacao_num = (int) $ca["bonificacao"]["bonificacao"];

            if (array_key_exists($bonificacao_num, $result[$res_posto_id]["bonificacoes"])) {
                $result[$res_posto_id]["bonificacoes"][$bonificacao_num]++;
                    $result[$res_posto_id]["total"] += floatval($ca["bonificacao"]["valor"]);
            } else {
                $result[$res_posto_id]["bonificacoes"][$bonificacao_num] = 1;
                $result[$res_posto_id]["total"] = floatval($ca["bonificacao"]["valor"]);
            }

            $result[$res_posto_id]["posto"] = $res_posto;
        }

        if ($csv == "true") {
            if (!empty($result)) {
                $content = "Posto;OS;OS Bonificação 1;OS Bonificação 2;OS Bonificação 3;Valor total OSs Posto\n";

                foreach ($result as $res) {
                    if (!array_key_exists(2, $res["bonificacoes"])) {
                        $res["bonificacoes"][2] = 0;
                    }

                    if (!array_key_exists(3, $res["bonificacoes"])) {
                        $res["bonificacoes"][3] = 0;
                    }

                    $content .= $res["posto"] . ';';
                    $content .= array_sum($res["bonificacoes"]) . ';';
                    $content .= $res["bonificacoes"][1] . ';';
                    $content .= $res["bonificacoes"][2] . ';';
                    $content .= $res["bonificacoes"][3] . ';';
                    $content .= number_format($res["total"], 2, ',', '') . "\n";
                }

                $csv_name = 'xls/relatorio_bonificacao_' . substr(sha1($login_admin), 0, 6) . date('Ymd') . '.csv';
                file_put_contents($csv_name, utf8_encode($content));

                die("$csv_name");
            } else {
                die('');
            }
        }
    }
}

include 'cabecalho_new.php';

$plugins = array(
    "shadowbox",
    "dataTable"
);

include 'plugin_loader.php';

$meses = array(
    "01" => "Janeiro",
    "02" => "Fevereiro",
    "03" => "Março",
    "04" => "Abril",
    "05" => "Maio",
    "06" => "Junho",
    "07" => "Julho",
    "08" => "Agosto",
    "09" => "Setembro",
    "10" => "Outubro",
    "11" => "Novembro",
    "12" => "Dezembro"
);

?>

<script type="text/javascript">

function retorna_posto(retorno){
    $("#codigo_posto").val(retorno.codigo);
    $("#nome_posto").val(retorno.nome);
}

function carrega_os(posto, mes, idx) {
    var idx_par = '';

    if (idx != undefined) {
        idx_par = '&bonificacao=' + idx;
    }

    Shadowbox.open({
        content: "relatorio_bonificacao_posto_os.php?posto=" + posto + "&mes=" + mes + idx_par,
        player: "iframe",
        width: 1000
    });
}

function gera_csv(codigo_posto, nome_posto, mes) {
    var csv_html = $("#csv").html();
    $("#csv").html("<img src='imagens_admin/carregando_callcenter.gif' >Por favor, aguarde...");

    $.ajax({
        type: 'POST',
        url: 'relatorio_bonificacao.php',
        data: {
            pesquisar: "Pesquisar",
            csv: true,
            codigo_posto: codigo_posto,
            nome_posto: nome_posto,
            filtro_mes: mes
        },
    }).done(function(data) {
        $("#csv").html(csv_html);
        location = data;
    });
}

$(function() {
    Shadowbox.init();

    $("span[rel=lupa]").click(function () {
        $.lupa($(this));
    });

    $("#limpar").on("click", function() {
        location = 'relatorio_bonificacao.php';
    });

    $.dataTableLoad({ table: "#table_callcenter_tempo_medio_atendimento" });
});

</script>

<?php
if (count($msg_erro["msg"]) > 0) {
?>
<br />
	<div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
	</div>
<?php
}
?>
<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<FORM name='relatorio_callcenter_tempo_medio_atendimento' METHOD='POST' ACTION='' class="form-search form-inline tc_formulario">
	<div class="titulo_tabela">Parâmetros de Pesquisa</div>
	<br />
	<div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='codigo_posto'>Código Posto</label>
                <div class='controls controls-row'>
                    <div class='span7 input-append'>
                        <input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<? echo $codigo_posto ?>" >
                        <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='nome_posto'>Nome Posto</label>
                <div class='controls controls-row'>
                    <div class='span12 input-append'>
                        <input type="text" name="nome_posto" id="nome_posto" class='span12' value="<? echo $nome_posto ?>" >&nbsp;
                        <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>

    <div class="row-fluid">
        <div class="span2"></div>
        <div class='span4'>
            <div class='control-group'>
                <label class='control-label' for='filtro_mes'>Mês</label>
                <div class='controls controls-row'>
                    <div class='span12 input-append'>
                        <select name="filtro_mes" class="span10" id="filtro_mes">
                            <option value=""></option>
                            <?php
                            foreach ($meses as $idx => $m) {
                                $mes_sel = ($idx == $mes) ? 'selected' : '';
                                echo "\t\t\t<option value='$idx' {$mes_sel}>$m</option>\n";
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="span6"></div>
    </div>
	<br />
	<p class="tac">
		<input type="submit" class="btn" name="pesquisar" value="Pesquisar" />
		<button type="button" id="limpar" class="btn btn-warning" />Limpar</button>
	</p>
	<br />
</FORM>
<br /> 

<!-- Tabela -->
<?php
if (true === $pesquisou) {
    if (!empty($result)) {
    ?>
    <table id="table_callcenter_tempo_medio_atendimento" class='table table-striped table-bordered table-hover table-fixed'>
        <thead>
            <tr class='titulo_coluna'>
                <td>Posto</td>
                <td>OS</td>
                <td>OS Bonificação 1</td>
                <td>OS Bonificação 2</td>
                <td>OS Bonificação 3</td>
                <td>Valor total OSs Posto</td>
            </tr>
        </thead>
        <tbody>
            <?php

            foreach ($result as $key => $res) {
                $carrega_os_b0 = '';
                $carrega_os_b1 = '';
                $carrega_os_b2 = '';
                $carrega_os_b = ' style="cursor: pointer" onClick="carrega_os(' . $key . ', \'' . $mes . '\', @IDX@)" ';

                if (!array_key_exists(2, $res["bonificacoes"])) {
                    $res["bonificacoes"][2] = 0;
                }

                if (!array_key_exists(3, $res["bonificacoes"])) {
                    $res["bonificacoes"][3] = 0;
                }

                if (!empty($res["bonificacoes"][1] )) {
                    $carrega_os_b0 = str_replace('@IDX@', '0', $carrega_os_b);
                }

                if (!empty($res["bonificacoes"][2] )) {
                    $carrega_os_b1 = str_replace('@IDX@', '1', $carrega_os_b);
                }

                if (!empty($res["bonificacoes"][3] )) {
                    $carrega_os_b2 = str_replace('@IDX@', '2', $carrega_os_b);
                }


                echo '<tr>';
                echo '<td>' . $res["posto"] . '</td>';
                echo '<td style="cursor: pointer" onClick="carrega_os(' . $key . ', \'' . $mes . '\')">' . array_sum($res["bonificacoes"]) . '</td>';
                echo '<td' . $carrega_os_b0 . '>' . $res["bonificacoes"][1] . '</td>';
                echo '<td' . $carrega_os_b1 . '>' . $res["bonificacoes"][2] . '</td>';
                echo '<td' . $carrega_os_b2 . '>' . $res["bonificacoes"][3] . '</td>';
                echo '<td>' . number_format($res["total"], 2, ',', '') . '</td>';
                echo '</tr>';
            }
            ?>
        </tbody>
    </table><br />
    <div>
    <div id="csv" style="text-align:center">
        <div class="btn_excel"  onClick="gera_csv('<?php echo $codigo_posto ?>', '<?php echo $nome_posto ?>', '<?php echo $mes ?>')">
            <span><img src='imagens/excel.png' /></span>
            <span class="txt">Gerar Arquivo Excel</span>
        </div>
    </div>

	<?php } elseif (empty($msg_erro)) { ?>
	<div class="container">
		<div class="alert">
		    <h4>Nenhum resultado encontrado</h4>
		</div>
	</div>
	<?
	}
}
?>

<?php include "rodape.php"; ?>
