<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios = "gerencia";
include "autentica_admin.php";
include "funcoes.php";

$title = "Indicadores de Tempo de Resposta";
$layout_menu = "gerencia";

include "cabecalho_new.php";

if ($_POST) {
    $data_inicial    = $_POST["data_inicial"];
    $data_final      = $_POST["data_final"];

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
        $sql = "
            SELECT 
                f.descricao AS familia,
                EXTRACT(MONTH FROM os.data_digitacao) AS data,
                c.estado AS regiao,
                AVG(EXTRACT(EPOCH FROM ose.termino_atendimento - rsl.create_at)) AS intervalo
            FROM tbl_os os
            INNER JOIN tbl_os_extra ose ON ose.os = os.os 
            INNER JOIN tbl_os_campo_extra osce ON osce.os = os.os
            INNER JOIN tbl_os_produto osp ON osp.os = os.os
            INNER JOIN tbl_produto p ON p.produto = osp.produto AND p.fabrica_i = {$login_fabrica}
            INNER JOIN tbl_familia f ON f.familia = p.familia AND f.fabrica = {$login_fabrica}
            INNER JOIN tbl_hd_chamado_cockpit hdc ON hdc.hd_chamado = os.hd_chamado
            INNER JOIN tbl_routine_schedule_log rsl ON rsl.routine_schedule_log = hdc.routine_schedule_log
            INNER JOIN tbl_posto_fabrica pf ON pf.posto = os.posto AND pf.fabrica = {$login_fabrica}
            LEFT JOIN (
                SELECT DISTINCT unidade_negocio, cidade
                FROM tbl_distribuidor_sla
                WHERE fabrica = {$login_fabrica}
            ) AS unidades ON unidades.unidade_negocio = JSON_FIELD('unidadeNegocio', osce.campos_adicionais)
            LEFT JOIN tbl_cidade c ON c.cidade = unidades.cidade
            WHERE os.fabrica = {$login_fabrica}
            AND ose.termino_atendimento IS NOT NULL
            AND c.cidade IS NOT NULL 
            AND os.hd_chamado IS NOT NULL
            AND rsl.create_at between '{$data_inicial} 00:00:00' and '{$data_final} 23:59:59' 
            AND os.posto <> 6359
            AND os.excluida IS NOT TRUE
            GROUP BY f.descricao, c.estado, data

            UNION

            SELECT
                f.descricao AS familia, 
                EXTRACT(YEAR FROM os.data_digitacao) AS data, 
                c.estado AS regiao, 
                AVG(EXTRACT(EPOCH FROM ose.termino_atendimento - rsl.create_at)) AS intervalo
            FROM tbl_os os 
            INNER JOIN tbl_os_extra ose ON ose.os = os.os 
            INNER JOIN tbl_os_campo_extra osce ON osce.os = os.os
            INNER JOIN tbl_os_produto osp ON osp.os = os.os
            INNER JOIN tbl_produto p ON p.produto = osp.produto AND p.fabrica_i = {$login_fabrica}
            INNER JOIN tbl_familia f ON f.familia = p.familia AND f.fabrica = {$login_fabrica}
            INNER JOIN tbl_hd_chamado_cockpit hdc ON hdc.hd_chamado = os.hd_chamado
            INNER JOIN tbl_routine_schedule_log rsl ON rsl.routine_schedule_log = hdc.routine_schedule_log
            INNER JOIN tbl_posto_fabrica pf ON pf.posto = os.posto AND pf.fabrica = {$login_fabrica}
            LEFT JOIN (
                SELECT DISTINCT unidade_negocio, cidade
                FROM tbl_distribuidor_sla
                WHERE fabrica = {$login_fabrica}
            ) AS unidades ON unidades.unidade_negocio = JSON_FIELD('unidadeNegocio', osce.campos_adicionais)
            LEFT JOIN tbl_cidade c ON c.cidade = unidades.cidade
            WHERE os.fabrica = {$login_fabrica} 
            AND ose.termino_atendimento IS NOT NULL
            AND c.cidade IS NOT NULL 
            AND os.hd_chamado IS NOT NULL 
            AND rsl.create_at between '{$data_inicial} 00:00:00' and '{$data_final} 23:59:59'
            AND os.posto <> 6359
            AND os.excluida IS NOT TRUE
            GROUP BY f.descricao, c.estado, data

            UNION

            SELECT
                f.descricao AS familia, 
                EXTRACT(MONTH FROM os.data_digitacao) AS data,
                'BRASIL' AS regiao, 
                AVG(EXTRACT(EPOCH FROM ose.termino_atendimento - rsl.create_at) / 2) AS intervalo
            FROM tbl_os os 
            INNER JOIN tbl_os_extra ose ON ose.os = os.os 
            INNER JOIN tbl_os_campo_extra osce ON osce.os = os.os
            INNER JOIN tbl_os_produto osp ON osp.os = os.os
            INNER JOIN tbl_produto p ON p.produto = osp.produto AND p.fabrica_i = {$login_fabrica}
            INNER JOIN tbl_familia f ON f.familia = p.familia AND f.fabrica = {$login_fabrica}
            INNER JOIN tbl_hd_chamado_cockpit hdc ON hdc.hd_chamado = os.hd_chamado
            INNER JOIN tbl_routine_schedule_log rsl ON rsl.routine_schedule_log = hdc.routine_schedule_log
            INNER JOIN tbl_posto_fabrica pf ON pf.posto = os.posto AND pf.fabrica = {$login_fabrica}
            LEFT JOIN (
                SELECT DISTINCT unidade_negocio, cidade
                FROM tbl_distribuidor_sla
                WHERE fabrica = {$login_fabrica}
            ) AS unidades ON unidades.unidade_negocio = JSON_FIELD('unidadeNegocio', osce.campos_adicionais)
            LEFT JOIN tbl_cidade c ON c.cidade = unidades.cidade
            WHERE os.fabrica = {$login_fabrica} 
            AND ose.termino_atendimento IS NOT NULL
            AND c.cidade IS NOT NULL 
            AND os.hd_chamado IS NOT NULL 
            AND rsl.create_at between '{$data_inicial} 00:00:00' and '{$data_final} 23:59:59'
            AND os.posto <> 6359
            AND os.excluida IS NOT TRUE
            GROUP BY f.descricao, data

            UNION

            SELECT
                f.descricao AS familia, 
                EXTRACT(YEAR FROM os.data_digitacao) AS data,
                'BRASIL' AS regiao, 
                AVG(EXTRACT(EPOCH FROM ose.termino_atendimento - rsl.create_at) / 2) AS intervalo
            FROM tbl_os os 
            INNER JOIN tbl_os_extra ose ON ose.os = os.os 
            INNER JOIN tbl_os_campo_extra osce ON osce.os = os.os
            INNER JOIN tbl_os_produto osp ON osp.os = os.os
            INNER JOIN tbl_produto p ON p.produto = osp.produto AND p.fabrica_i = {$login_fabrica}
            INNER JOIN tbl_familia f ON f.familia = p.familia AND f.fabrica = {$login_fabrica}
            INNER JOIN tbl_hd_chamado_cockpit hdc ON hdc.hd_chamado = os.hd_chamado
            INNER JOIN tbl_routine_schedule_log rsl ON rsl.routine_schedule_log = hdc.routine_schedule_log
            INNER JOIN tbl_posto_fabrica pf ON pf.posto = os.posto AND pf.fabrica = {$login_fabrica}
            LEFT JOIN (
                SELECT DISTINCT unidade_negocio, cidade
                FROM tbl_distribuidor_sla
                WHERE fabrica = {$login_fabrica}
            ) AS unidades ON unidades.unidade_negocio = JSON_FIELD('unidadeNegocio', osce.campos_adicionais)
            LEFT JOIN tbl_cidade c ON c.cidade = unidades.cidade
            WHERE os.fabrica = {$login_fabrica} 
            AND ose.termino_atendimento IS NOT NULL
            AND c.cidade IS NOT NULL 
            AND os.hd_chamado IS NOT NULL 
            AND rsl.create_at between '{$data_inicial} 00:00:00' and '{$data_final} 23:59:59'
            AND os.posto <> 6359
            AND os.excluida IS NOT TRUE
            GROUP BY f.descricao, data

            ORDER BY familia, data, regiao
        ";
        /*$sql = "
            SELECT 
                f.descricao AS familia,
                EXTRACT(MONTH FROM os.data_digitacao) AS data,
                c.estado AS regiao,
                AVG(EXTRACT(EPOCH FROM ose.termino_atendimento - rsl.create_at)) AS intervalo
            FROM tbl_os os
            INNER JOIN tbl_os_extra ose ON ose.os = os.os 
            INNER JOIN tbl_os_campo_extra osce ON osce.os = os.os
            INNER JOIN tbl_os_produto osp ON osp.os = os.os
            INNER JOIN tbl_produto p ON p.produto = osp.produto AND p.fabrica_i = {$login_fabrica}
            INNER JOIN tbl_familia f ON f.familia = p.familia AND f.fabrica = {$login_fabrica}
            INNER JOIN tbl_hd_chamado_cockpit hdc ON hdc.hd_chamado = os.hd_chamado
            INNER JOIN tbl_routine_schedule_log rsl ON rsl.routine_schedule_log = hdc.routine_schedule_log
            INNER JOIN tbl_posto_fabrica pf ON pf.posto = os.posto AND pf.fabrica = {$login_fabrica}
            LEFT JOIN tbl_distribuidor_sla_posto dsp ON dsp.posto = pf.posto AND dsp.fabrica = {$login_fabrica}
            LEFT JOIN tbl_distribuidor_sla ds ON osce.campos_adicionais LIKE '%\"unidadeNegocio\":\"' || ds.unidade_negocio || '\"%' AND ds.fabrica = {$login_fabrica} AND ds.distribuidor_sla = dsp.distribuidor_sla
            LEFT JOIN tbl_cidade c ON c.cidade = ds.cidade
            WHERE os.fabrica = {$login_fabrica}
            AND ose.termino_atendimento IS NOT NULL
            AND c.cidade IS NOT NULL 
            AND os.hd_chamado IS NOT NULL
            AND os.data_digitacao between '{$data_inicial} 00:00:00' and '{$data_final} 23:59:59' 
            AND os.posto <> 6359
            AND os.excluida IS NOT TRUE
            GROUP BY f.descricao, c.estado, data

            UNION

            SELECT
                f.descricao AS familia, 
                EXTRACT(YEAR FROM os.data_digitacao) AS data, 
                c.estado AS regiao, 
                AVG(EXTRACT(EPOCH FROM ose.termino_atendimento - rsl.create_at)) AS intervalo
            FROM tbl_os os 
            INNER JOIN tbl_os_extra ose ON ose.os = os.os 
            INNER JOIN tbl_os_campo_extra osce ON osce.os = os.os
            INNER JOIN tbl_os_produto osp ON osp.os = os.os
            INNER JOIN tbl_produto p ON p.produto = osp.produto AND p.fabrica_i = {$login_fabrica}
            INNER JOIN tbl_familia f ON f.familia = p.familia AND f.fabrica = {$login_fabrica}
            INNER JOIN tbl_hd_chamado_cockpit hdc ON hdc.hd_chamado = os.hd_chamado
            INNER JOIN tbl_routine_schedule_log rsl ON rsl.routine_schedule_log = hdc.routine_schedule_log
            INNER JOIN tbl_posto_fabrica pf ON pf.posto = os.posto AND pf.fabrica = {$login_fabrica}
            LEFT JOIN tbl_distribuidor_sla_posto dsp ON dsp.posto = pf.posto AND dsp.fabrica = {$login_fabrica}
            LEFT JOIN tbl_distribuidor_sla ds ON osce.campos_adicionais LIKE '%\"unidadeNegocio\":\"' || ds.unidade_negocio || '\"%' AND ds.fabrica = {$login_fabrica} AND ds.distribuidor_sla = dsp.distribuidor_sla
            LEFT JOIN tbl_cidade c ON c.cidade = ds.cidade
            WHERE os.fabrica = {$login_fabrica} 
            AND ose.termino_atendimento IS NOT NULL
            AND c.cidade IS NOT NULL 
            AND os.hd_chamado IS NOT NULL 
            AND os.data_digitacao between '{$data_inicial} 00:00:00' and '{$data_final} 23:59:59'
            AND os.posto <> 6359
            AND os.excluida IS NOT TRUE
            GROUP BY f.descricao, c.estado, data

            UNION

            SELECT
                f.descricao AS familia, 
                EXTRACT(MONTH FROM os.data_digitacao) AS data,
                'BRASIL' AS regiao, 
                AVG(EXTRACT(EPOCH FROM ose.termino_atendimento - rsl.create_at) / 2) AS intervalo
            FROM tbl_os os 
            INNER JOIN tbl_os_extra ose ON ose.os = os.os 
            INNER JOIN tbl_os_campo_extra osce ON osce.os = os.os
            INNER JOIN tbl_os_produto osp ON osp.os = os.os
            INNER JOIN tbl_produto p ON p.produto = osp.produto AND p.fabrica_i = {$login_fabrica}
            INNER JOIN tbl_familia f ON f.familia = p.familia AND f.fabrica = {$login_fabrica}
            INNER JOIN tbl_hd_chamado_cockpit hdc ON hdc.hd_chamado = os.hd_chamado
            INNER JOIN tbl_routine_schedule_log rsl ON rsl.routine_schedule_log = hdc.routine_schedule_log
            INNER JOIN tbl_posto_fabrica pf ON pf.posto = os.posto AND pf.fabrica = {$login_fabrica}
            LEFT JOIN tbl_distribuidor_sla_posto dsp ON dsp.posto = pf.posto AND dsp.fabrica = {$login_fabrica}
            LEFT JOIN tbl_distribuidor_sla ds ON osce.campos_adicionais LIKE '%\"unidadeNegocio\":\"' || ds.unidade_negocio || '\"%' AND ds.fabrica = {$login_fabrica} AND ds.distribuidor_sla = dsp.distribuidor_sla
            LEFT JOIN tbl_cidade c ON c.cidade = ds.cidade
            WHERE os.fabrica = {$login_fabrica} 
            AND ose.termino_atendimento IS NOT NULL
            AND c.cidade IS NOT NULL 
            AND os.hd_chamado IS NOT NULL 
            AND os.data_digitacao between '{$data_inicial} 00:00:00' and '{$data_final} 23:59:59'
            AND os.posto <> 6359
            AND os.excluida IS NOT TRUE
            GROUP BY f.descricao, data

            UNION

            SELECT
                f.descricao AS familia, 
                EXTRACT(YEAR FROM os.data_digitacao) AS data,
                'BRASIL' AS regiao, 
                AVG(EXTRACT(EPOCH FROM ose.termino_atendimento - rsl.create_at) / 2) AS intervalo
            FROM tbl_os os 
            INNER JOIN tbl_os_extra ose ON ose.os = os.os 
            INNER JOIN tbl_os_campo_extra osce ON osce.os = os.os
            INNER JOIN tbl_os_produto osp ON osp.os = os.os
            INNER JOIN tbl_produto p ON p.produto = osp.produto AND p.fabrica_i = {$login_fabrica}
            INNER JOIN tbl_familia f ON f.familia = p.familia AND f.fabrica = {$login_fabrica}
            INNER JOIN tbl_hd_chamado_cockpit hdc ON hdc.hd_chamado = os.hd_chamado
            INNER JOIN tbl_routine_schedule_log rsl ON rsl.routine_schedule_log = hdc.routine_schedule_log
            INNER JOIN tbl_posto_fabrica pf ON pf.posto = os.posto AND pf.fabrica = {$login_fabrica}
            LEFT JOIN tbl_distribuidor_sla_posto dsp ON dsp.posto = pf.posto AND dsp.fabrica = {$login_fabrica}
            LEFT JOIN tbl_distribuidor_sla ds ON osce.campos_adicionais LIKE '%\"unidadeNegocio\":\"' || ds.unidade_negocio || '\"%' AND ds.fabrica = {$login_fabrica} AND ds.distribuidor_sla = dsp.distribuidor_sla
            LEFT JOIN tbl_cidade c ON c.cidade = ds.cidade
            WHERE os.fabrica = {$login_fabrica} 
            AND ose.termino_atendimento IS NOT NULL
            AND c.cidade IS NOT NULL 
            AND os.hd_chamado IS NOT NULL 
            AND os.data_digitacao between '{$data_inicial} 00:00:00' and '{$data_final} 23:59:59'
            AND os.posto <> 6359
            AND os.excluida IS NOT TRUE
            GROUP BY f.descricao, data

            ORDER BY familia, data, regiao
        ";*/
        $res = pg_query($con, $sql);

        if (!pg_num_rows($res)) {
            $msg_erro["msg"][] = "Nenhum resultado encontrado";
        } else {
            $graficos  = array();
            $resultado = array();

            $grafico_keys = array(
                "BRASIL" => 2,
                "MG"     => 0,
                "SP"     => 1
            );

            while ($row = pg_fetch_object($res)) {
                $resultado[] = (array) $row;

                if ($row->data <= 12) {
                    $row->data = $meses_idioma["pt-br"][$row->data];
                }

                $graficos[$row->familia][$row->data]["name"] = $row->data;
                $graficos[$row->familia][$row->data]["type"] = "column";

                if (!isset($graficos[$row->familia][$row->data]["data"])) {
                    $graficos[$row->familia][$row->data]["data"] = array(0, 0, 0);
                }

                $graficos[$row->familia][$row->data]["data"][$grafico_keys[$row->regiao]] = $row->intervalo;
            }

            $graficos     = array($graficos);
            $mes_pesquisa = $meses_idioma["pt-br"][$mes];
            $ano_pesquisa = $ano;
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

<div class="row no-print" >
    <b class="obrigatorio pull-right" >* Campos obrigatórios</b>
</div>

<form method="POST" class="form-search form-inline tc_formulario no-print" >
    <div class="titulo_tabela" >Parâmetros de Pesquisa</div>

    <br />

    <div class="row-fluid" >
        <div class="span4" ></div>
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
    </div>

    <br />

    <p class="tac" >
        <button type="submit" name="pesquisa" class="btn" >Pesquisar</button>
    </p>

    <br />
</form>

<?php
$plugins = array(
    "select2",
    "highcharts",
    "mask"
);

include "plugin_loader.php";
?>

<div id="graficos" ></div>

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

    table, #graficos > div {
        width: 100%;
    }
}

</style>

<script>
$(function() {
    $.datepickerLoad(Array("data_final", "data_inicial"));
});

$("select").select2();

<?php
if (count($graficos)) {
?>

    var resultado    = <?=json_encode($resultado)?>;
    var graficos     = <?=json_encode($graficos)?>;
    var mes_pesquisa = "<?=$mes_pesquisa?>";
    var ano_pesquisa = "<?=$ano_pesquisa?>";

    graficos.map(function(grafico) {
        $.each(grafico, function(g, o) {
            var div_id = g.replace(" ", "");

            $("#graficos").append($("<div></div>", { id: div_id }));
            $("#"+div_id).after("\
                <table class='table table-bordered "+div_id+"' >\
                    <thead>\
                        <tr class='titulo_coluna' >\
                            <th colspan='2' >Tempo de Resposta "+g+"</th>\
                            <th>"+mes_pesquisa+"</th>\
                            <th>"+ano_pesquisa+"</th>\
                        </tr>\
                    </thead>\
                    <tbody>\
                        <tr class='MG' >\
                            <td>Belo Horizonte</td>\
                            <td>Total</td>\
                            <td class='mes tac' >0:0</td>\
                            <td class='ano tac' >0:0</td>\
                        </tr>\
                        <tr class='SP' >\
                            <td>São Paulo</td>\
                            <td>Total</td>\
                            <td class='mes tac' >0:0</td>\
                            <td class='ano tac' >0:0</td>\
                        </tr>\
                        <tr class='BRASIL' >\
                            <td>Brasil</td>\
                            <td>Total</td>\
                            <td class='mes tac' >0:0</td>\
                            <td class='ano tac' >0:0</td>\
                        </tr>\
                    </tbody>\
                </table>\
                <hr />\
            ");

            var series_grafico = [];

            $.each(o, function(k, s) {
                $.each(s.data, function(i, v) {
                    v     = parseFloat(v);
                    var l = Date.secondsToTimeString(v);

                    s.data[i] = ["<b>"+l+"</b>", v];

                    s.dataLabels = {
                        enabled: true,
                        color: '#000000',
                        formatter: function() { 
                            return this.point.name;
                        }
                    };
                });

                series_grafico.push(s);
            });

            $("#"+div_id).highcharts({
                title: {
                    text: g
                },
                xAxis: {
                    categories: ["MG", "SP", "BRASIL"]
                },
                yAxis: {
                    minorTickInterval: null,
                    labels: {
                        enabled: false
                    },
                    title: {
                        text: "Tempo de Resposta"
                    }
                },
                tooltip: {
                    pointFormatter: function() {
                        return "<b>"+this.category+" "+this.series.name+"</b>";
                    }
                },
                series: series_grafico
            });
        });
    });

    resultado.map(function(row) {
        var data  = parseInt(row.data);
        var table = $("table."+row.familia.replace(" ", ""));

        if (data <= 12) {
            $(table).find("tr."+row.regiao+" > td.mes").text(Date.secondsToTimeString(row.intervalo));
        } else {
            $(table).find("tr."+row.regiao+" > td.ano").text(Date.secondsToTimeString(row.intervalo));
        }
    });

<?php
}
?>

</script>

<br />

<?php 
include "rodape.php"; 
?>
