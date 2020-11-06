<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios = "call_center";
include 'autentica_admin.php';

$layout_menu = "callcenter";
$title       = "TEMPO MÉDIO DE ATENDIMENTO";

include 'cabecalho_new.php';

$grafico_01 = [];
$grafico_02 = [];
$msg = '';

if (!empty($_POST['btn_acao'])) {
    $msg_erro = '';
    $classificacao = '';
    $atendente = '';

    $cond_classificacao = '';
    $cond_atendente = '';

    $grafico_tma = [];
    $grafico_porc = [];

    if (empty($_POST['data_inicial']) or empty($_POST['data_final'])) {
        $msg_erro = 'Selecione um período para realizar a pesquisa.';
    } else {
        $data_inicial = $_POST['data_inicial'];
        $data_final = $_POST['data_final'];

        $dtDi = DateTime::createFromFormat('d/m/Y', $data_inicial); 
        $dtDf = DateTime::createFromFormat('d/m/Y', $data_final);

        if ($dtDi->format('d/m/Y') <> $data_inicial) {
            $msg_erro = 'Data Inicial inválida.';
        }

        if ($dtDf->format('d/m/Y') <> $data_final) {
            $msg_erro = 'Data Final inválida.';
        }

        if ($dtDi > $dtDf) {
            $msg_erro = 'Data inválida.';
        }

        if (empty($msg_erro)) {
            $data_inicial_sql = $dtDi->format('Y-m-d');
            $data_final_sql = $dtDf->format('Y-m-d');

            $interval = new DateInterval('P1Y');
            $dtDi->add($interval);

            if ($dtDf >= $dtDi) {
                $msg_erro = 'Período máximo para pesquisa é de 1 ano.';
            }
        }
    }

    if (!empty($_POST['classificacao'])) {
        $classificacao = (int) $_POST['classificacao'];

        $cond_classificacao = " AND tbl_hd_chamado.hd_classificacao = $classificacao ";
    }

    if (!empty($_POST['atendente'])) {
        $atendente = $_POST['atendente'];

        if (!empty($atendente[0])) {
            $cond_atendente = '  AND tbl_hd_chamado.atendente IN (' . implode(', ', $atendente) . ') ';
        }
    }

    if (empty($msg_erro)) {
        $sql = "SELECT COUNT(tbl_hd_chamado.hd_chamado) AS qtde,
                    TO_CHAR(tbl_hd_chamado.data, 'MM/YYYY') AS mes_ano,
                    (SELECT tbl_hd_chamado_item.data
                    FROM tbl_hd_chamado_item
                    WHERE tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado
                    and tbl_hd_chamado_item.interno is not true
                    AND tbl_hd_chamado.status is not null
                    ORDER BY data desc LIMIT 1)::date - tbl_hd_chamado.data::date AS periodo
                FROM tbl_hd_chamado_extra
                JOIN tbl_hd_chamado using(hd_chamado)
                WHERE fabrica_responsavel = $login_fabrica
                AND tbl_hd_chamado.data BETWEEN '$data_inicial_sql 00:00:00' AND '$data_final_sql 23:59:59'
                AND tbl_hd_chamado.status = 'Resolvido'
                AND tbl_hd_chamado.posto is null
                $cond_classificacao
                $cond_atendente
                GROUP BY periodo, tbl_hd_chamado.data
                ORDER BY tbl_hd_chamado.data";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0) {
            while ($fetch = pg_fetch_assoc($res)) {
                if (!array_key_exists($fetch['mes_ano'], $grafico_tma)) {
                    $grafico_tma[$fetch['mes_ano']] = [
                        'dias' => (int) $fetch['periodo'],
                        'chamados' => (int) $fetch['qtde']
                    ];
                } else {
                    $grafico_tma[$fetch['mes_ano']]['dias'] += (int) $fetch['periodo'];
                    $grafico_tma[$fetch['mes_ano']]['chamados'] += (int) $fetch['qtde'];
                }

                if (!array_key_exists($fetch['mes_ano'], $grafico_porc)) {
                    $abaixo_30 = ($fetch['periodo'] <= 30) ? (int) $fetch['qtde'] : 0;
                    $grafico_porc[$fetch['mes_ano']] = [
                        'abaixo_30' => $abaixo_30,
                        'total' => (int) $fetch['qtde']
                    ];
                } else {
                    $abaixo_30 = ($fetch['periodo'] <= 30) ? (int) $fetch['qtde'] : 0;
                    $grafico_porc[$fetch['mes_ano']]['abaixo_30'] += $abaixo_30;
                    $grafico_porc[$fetch['mes_ano']]['total'] += (int) $fetch['qtde'];
                }
            }

            foreach ($grafico_tma as $key => $value) {
                $grafico_01["'{$key}'"] = round($value['dias'] / $value['chamados']);
            }

            foreach ($grafico_porc as $key => $value) {
                $grafico_02["'{$key}'"] = round(($value['abaixo_30'] / $value['total']) * 100, 2);
            }

            $grafico_01_meta = array_fill(0, count($grafico_01), 30);
            $grafico_02_meta = array_fill(0, count($grafico_02), 70);

            unset($grafico_tma);
            unset($grafico_porc);
        } else {
            $msg = "<center>Nenhum Resultado Encontrado</center>";
        }
    }
}

$plugins = array(
    "mask",
    "datepicker",
    "select2"
);

include 'plugin_loader.php';

?>

<script type="text/javascript" charset="utf-8">
    $(function() {
        $("select").select2();
        $("#data_inicial").datepicker().mask("99/99/9999");
        $("#data_final").datepicker().mask("99/99/9999");
    });
</script>

<script type="text/javascript" src="../ajax.js"></script>

<?php include 'javascript_pesquisas.php'; ?>

<script type="text/javascript" src="js/highcharts_4.0.3.js"></script>

<?php if (!empty($msg_erro)): ?>
    <div class="alert alert-danger"><strong><?= $msg_erro ?></strong></div>
<? endif ?>

<form name="frm_relatorio" class="form-search form-inline tc_formulario" method="post" action="">
    <div class="titulo_tabela">Parâmetros de Pesquisa</div>

    <div class="row-fluid">
        <div class="span2"></div>
        <div class="span4">
            <div class="control-group">
                <label class="control-label" for=''>Data Inicial</label>
                <div class='controls controls-row'>
                    <input class="span7" type="text" name="data_inicial" id="data_inicial" size="14" maxlength="10" value="<? if (strlen($data_inicial) > 0) echo $data_inicial; ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';">
                </div>
            </div>
        </div>
        <div class="span4">
            <div class="control-group">
                <label class="control-label" for=''>Data Final</label>
                <div class='controls controls-row'>
                    <input class="span7" type="text" name="data_final" id="data_final" size="12" maxlength="10" class='frm' value="<? if (strlen($data_final) > 0) echo $data_final;  ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';">
                </div>
            </div>
        </div>
        <div class="span2"></div>
    </div>

    <div class="row-fluid">
        <div class="span2"></div>
        <div class="span4">
            <label class="control-label" for=''>Classificação</label>
            <div class='controls controls-row'>
            <select name="classificacao" class='controls' style='width: 180px'>
                <option value=""></option>
                <?php 
                    $sqlCl = "SELECT hd_classificacao, descricao from tbl_hd_classificacao where fabrica = $login_fabrica AND ativo is true ORDER BY descricao";
                    $resCL = pg_query($con, $sqlCl);
                    for($a = 0; $a<pg_num_rows($resCL); $a++){
                        $hd_classificacao   = pg_fetch_result($resCL, $a, hd_classificacao);
                        $descricao          = pg_fetch_result($resCL, $a, descricao);

                        if($hd_classificacao == $classificacao){
                            $selected = " selected ";
                        }else{
                            $selected = " ";
                        }
                    
                        echo "<option value='$hd_classificacao' $selected >$descricao</option>";

                    }

                ?>
            </select>
            </div>
        <div class="span2"></div>
        </div>  
    </div>

    <?php
    $sql = "SELECT admin, login
            FROM tbl_admin
            WHERE
                fabrica = $login_fabrica
                AND ativo IS TRUE
                AND (privilegios LIKE '%call_center%' OR privilegios like '*')
                $cond_admin_fale_conosco
            ORDER BY login";

    $res = pg_query($con,$sql);

    if (pg_num_rows($res) > 0) {

        $total = pg_num_rows($res);
        $total = round((($total * 20) / 3)+15);

        if ($total > 120) $total = 120;

        $style = "style='height: {$total}px'";

    }

    ?>

    <div class="row-fluid">
        <br />
        <div class="panel well well-lg" style="width: 90%; margin: 0 auto;">
            <h4 align="center">Atendentes</h4>
            <div class="row-fluid">
                <div class='span12'>
                    <?php
                        if(count($atendente) > 0)
                            $checked = ($atendente[array_search("", $atendente)] == "") ? "checked": "" ;
                        ?>
                        <div class="row-fluid" style="min-height: 40px !important;">
                            <div class="span2"></div>
                            <div class="span8" style="text-align: center;">
                                <label class="checkbox" for=''>
                                <?
                                 echo "<input type='checkbox' value='' name='atendente[]' {$checked} />Toda a Equipe";
                                 ?>
                                 </label>
                             </div>
                            <div class="span2"></div>
                        </div>

                         <?

                        $res = pg_query($con,$sql);
                        if (pg_num_rows($res)>0) {
                            $i = 0;

                            while($dado = pg_fetch_array($res)){
                                $i++;
                                if(count($atendente)>0)
                                    $checked = ($atendente[array_search($dado[0], $atendente)] == $dado[0]) ? "checked": "" ;
                                if ($i % 3 == 0) { ?>
                                    <div class="row-fluid" style="min-height: 40px !important;">
                                <? } ?>

                                    <div class='span4'>
                                        <label class="checkbox" for=''>
                                        <?
                                        echo "<input type='checkbox' value='{$dado[0]}' name='atendente[]' {$checked} />{$dado[1]}";
                                        ?>
                                        </label>
                                    </div>
                                <?
                                if ($i % 3 == 0) { ?>
                                        </div>
                                <? }
                            }

                        }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="span5"></div>
        <div class="span2" id='btn_submit'>
            <br />
            <input type='submit' class='btn' style="cursor:pointer" name='btn_acao' id='btn_acao' value='Pesquisar'>
        </div>
        <div class="span5"></div>
    </div><br>
</form>

<?php if (!empty($msg)): ?>
    <div class="alert alert-success"><?= $msg ?></div>
<? endif ?>

<div id="grafico01"></div>
<div id="grafico02" style="margin-top: 75px;"></div>

 <script type="text/javascript">
    $(function () {
        <?php if (!empty($grafico_01)): ?>
         $('#grafico01').highcharts({

            title: {
                text: 'Tempo médio de atendimento'
            },

            xAxis: {
                categories: [<?= implode(', ', array_keys($grafico_01)) ?>]
            },

            yAxis: {
                title: {
                    text: ''
                },
                tickInterval: 5,
                min: 0
            },

            legend: {
                layout: 'vertical',
                align: 'right',
                verticalAlign: 'middle'
            },

            plotOptions: {
                line: {
                    dataLabels: {
                        enabled: true,
                        style: {
                            fontWeight: "bold"
                        }
                    },
                    enableMouseTracking: false
                }
            },
           
            series: [{
                name: 'Média',
                data: [<?= implode(', ', $grafico_01) ?>]
            }, {
                name: 'Meta',
                data: [<?= implode(', ', $grafico_01_meta) ?>],
                color: 'green',
            }],

            responsive: {
                rules: [{
                    condition: {
                        maxWidth: 500
                    },
                    chartOptions: {
                        legend: {
                            layout: 'horizontal',
                            align: 'center',
                            verticalAlign: 'bottom'
                        }
                    }
                }]
            }

        });
        <?php endif ?>

        <?php if (!empty($grafico_02)): ?>
         $('#grafico02').highcharts({

            title: {
                text: '% de atendimentos realizados em até 30 dias'
            },

            xAxis: {
                categories: [<?= implode(', ', array_keys($grafico_02)) ?>]
            },

            yAxis: {
                title: {
                    text: ''
                },
                labels: {
                    format: '{value}%'
                },
                tickInterval: 5,
                min: 0,
                max: 100
            },
            
            legend: {
                layout: 'vertical',
                align: 'right',
                verticalAlign: 'middle'
            },

            plotOptions: {
                line: {
                    dataLabels: {
                        enabled: true,
                        style: {
                            fontWeight: "bold"
                        }
                    },
                    enableMouseTracking: false
                },
                series: {
                    dataLabels: {
                        enabled: true,
                        format: '{y}%'
                    }
                }
            },
           
            series: [{
                name: 'Porc.',
                data: [<?= implode(', ', $grafico_02) ?>]
            }, {
                name: 'Meta',
                data: [<?= implode(', ', $grafico_02_meta) ?>],
                color: 'green',
            }],

            responsive: {
                rules: [{
                    condition: {
                        maxWidth: 500
                    },
                    chartOptions: {
                        legend: {
                            layout: 'horizontal',
                            align: 'center',
                            verticalAlign: 'bottom'
                        }
                    }
                }]
            }

        });
        <?php endif ?> 
    });
</script>

<?php include 'rodape.php'; ?>
