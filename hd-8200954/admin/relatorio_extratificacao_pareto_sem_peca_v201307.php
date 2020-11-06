<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

if (!empty($_GET['ab'])) {
    $abertura = $_GET['ab'];
}

if (!empty($_GET['fb'])) {
    $fabricacao = $_GET['fb'];
}

if (!empty($_GET['f'])) {
    $familia = $_GET['f'];
}

if (empty($abertura) or empty($fabricacao) or empty($familia)) {
    $conteudo = "Dados não encontrados!";
}

if (!empty($_GET['fm'])) {
    $meses = $_GET['fm'];
} else {
    $meses = $_POST['meses'];
}

if (!empty($_POST['limit'])) {
    $limit = $_POST['limit'];
} else {
    $limit = 5;
}

if (empty($conteudo)) {
    $abertura_request = $abertura;
    $fabricacao_request = $fabricacao;

    $abertura   = $abertura . '-01';
    $fabricacao = $fabricacao . '-01';

    $arr_abertura = explode('-', $abertura);
    $arr_fabricacao = explode('-', $fabricacao);

    $sql = "
        select
            tbl_os.os,
            tbl_os.sua_os,
            tbl_numero_serie.serie,
            to_char(tbl_numero_serie.data_fabricacao, 'DD/MM/YYYY') as data_fabricacao,
            tbl_os.defeito_reclamado_descricao as reclamado,
            tbl_defeito_constatado.descricao as constatado
        FROM tbl_os
        JOIN tbl_numero_serie ON tbl_os.serie = tbl_numero_serie.serie
            AND tbl_numero_serie.produto = tbl_os.produto AND tbl_numero_serie.fabrica = $login_fabrica
        JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
        LEFT JOIN tbl_defeito_constatado ON tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado
        LEFT JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os
        WHERE tbl_os.fabrica = $login_fabrica
        AND tbl_produto.familia = $familia
        AND tbl_numero_serie.data_fabricacao between '$fabricacao' and (('$fabricacao'::date + interval '1 month') - interval '1 day')::date
        AND tbl_os.data_abertura between '$abertura' and (('$abertura'::date + interval '$meses month') - interval '1 day')::date
        AND tbl_os_produto.os_produto IS NULL
        ORDER BY tbl_numero_serie.data_fabricacao 
        ";
    $query = pg_query($con, $sql);
    #echo nl2br($sql);
    $percentual = 0;

    $categorias = array();
    $totais = array();
    $percentuais = array();

    if (pg_num_rows($query) > 0) {
        $prepare = pg_prepare("os_item", "select distinct os from tbl_os join tbl_os_produto using (os) join tbl_os_item using (os_produto) where os = $1");

        $conteudo = '<table class="tabela" cellspacing="1" align="center">';
        $conteudo.= '<tr class="titulo_tabela">';
        $conteudo.= '<th>OS</th>';
        $conteudo.= '<th style="width: 260px;">Defeito Reclamado</th>';
        $conteudo.= '<th>Defeito Constatado</th>';
        $conteudo.= '<th>Número Série</th>';
        $conteudo.= '<th>Data Fabricação</th>';

        while ($fetch = pg_fetch_assoc($query)) {
            $os = $fetch['os'];
            $total = (int) $fetch['total'];

            /*$x = pg_execute("os_item", array($os));

            if (pg_num_rows($x) > 0) {
                continue;
            }*/

            $defeito_reclamado = $fetch['reclamado'];
            $defeito_constatado = $fetch['constatado'];
            if (empty($defeito_constatado)) {
                $defeito_constatado = '&nbsp;';
            }


            $sua_os = $fetch['sua_os'];
            $serie = $fetch['serie'];
            $data_fabricacao = $fetch['data_fabricacao'];

            $conteudo.= '<tr>';
            $conteudo.= '<td><a href="os_press.php?os=' . $os . '" target="_blank">' . $sua_os . '</a></td>';
            $conteudo.= '<td>' . $defeito_reclamado . '</td>';
            $conteudo.= '<td>' . $defeito_constatado . '</td>';
            $conteudo.= '<td>' . $serie . '</td>';
            $conteudo.= '<td>' . $data_fabricacao . '</td>';
            $conteudo.= '</tr>';
        }
	$conteudo.= '</table><br/>';

	$destino = dirname(__FILE__) . '/xls';
	date_default_timezone_set('America/Sao_Paulo');
	$data = date('YmdGis');
	$arq_nome = 'relatorio_extratificacao_defeitos-' . $login_fabrica . $data . '.xls';
	$file = $destino . '/' . $arq_nome ;
	$f = fopen($file, 'w');
	fwrite($f, $conteudo);
	fclose($f);

        $conteudo.= '<div align="center"><input type="button" value="Download do arquivo Excel" onClick="download(\'xls/' . $arq_nome . '\')" /></div><br/>';

        $conteudo.= '<div id="container" style="min-width: 400px; height: 400px; margin: 0 auto"></div>';

        $conteudo.= '<div style="margin-top: 40px;" align="center">';
        $conteudo.= '<form method="post" action="relatorio_extratificacao_pareto_sem_peca.php">';
        $conteudo.= '<input type="hidden" name="abertura" value="' . $abertura_request . '" />';
        $conteudo.= '<input type="hidden" name="fabricacao" value="' . $fabricacao_request . '" />';
        $conteudo.= '<input type="hidden" name="meses" value="' . $meses . '" />';
        $conteudo.= '<input type="hidden" name="familia" value="' . $familia . '" />';
        $conteudo.= 'Qtde de defeitos: <input type="input" name="limit" value="' . $limit . '" style="width: 30px;" />';
        $conteudo.= '<span style="padding-left: 10px;"><input type="submit" value="Enviar" /></span>';
        $conteudo.= '</form>';
        $conteudo.= '</div>';

        $destino = dirname(__FILE__) . '/xls';
        date_default_timezone_set('America/Sao_Paulo');
        $data = date('YmdGis');
        $arq_nome = 'relatorio_extratificacao_defeitos-' . $login_fabrica . $data . '.xls';
        $file = $destino . '/' . $arq_nome ;
        $f = fopen($file, 'w');
        fwrite($f, $conteudo);
        fclose($f);

         $sql = "
            select tbl_defeito_constatado.descricao as constatado,
                count(tbl_os.os) AS total
            FROM tbl_os
            JOIN tbl_numero_serie ON tbl_os.serie = tbl_numero_serie.serie
                AND tbl_numero_serie.produto = tbl_os.produto AND tbl_numero_serie.fabrica = $login_fabrica
            JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
            LEFT JOIN tbl_defeito_constatado ON tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado
            LEFT JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os
            WHERE tbl_os.fabrica = $login_fabrica
            AND tbl_produto.familia = $familia
            AND tbl_numero_serie.data_fabricacao between '$fabricacao' and (('$fabricacao'::date + interval '1 month') - interval '1 day')::date
            AND tbl_os.data_abertura between '$abertura' and (('$abertura'::date + interval '$meses month') - interval '1 day')::date
            AND tbl_os_produto.os_produto IS NULL
            GROUP BY tbl_defeito_constatado.descricao
            ORDER BY count(tbl_os.os) DESC limit $limit
            ";
        $query2 = pg_query($con, $sql);

        if (pg_num_rows($query2) > 0) {
            $total_geral = 0;
            for($i = 0; $i < pg_num_rows($query2); $i++){
                 $total_geral += pg_fetch_result($query2, $i, 'total');
            }

            while ($fetch = pg_fetch_assoc($query2)) {
                $defeito_constatado = $fetch['constatado'];
                $total = (int) $fetch['total'];
                $percentual_uniq = ($total / $total_geral) * 100;
                $percentual+= $percentual_uniq;
                $cat = utf8_encode($defeito_constatado);
                $categorias[] = $cat;
                $totais[] = $total;
                $percentuais[] = round($percentual, 2);
            }
            //print_r($categorias);
        }
    } else {
        $conteudo = 'Dados não encontrados!';
    }

}

?>

<html>
    <head>
        <title></title>
        <style>
            .conteudo { width: 1000px; height: 400px; }
            .titulo_tabela{
                background-color:#596d9b;
                font: bold 14px "Arial";
                color:#FFFFFF;
                text-align:center;
            }
            .titulo_coluna{
                background-color:#596d9b;
                font: bold 11px "Arial";
                color:#FFFFFF;
                text-align:center;
            }
            .formulario{
                background-color:#D9E2EF;
                font:11px Arial;
                text-align:left;
            }
            table.form tr td{
                padding:10px 30px 0 0;
            }
            table.tabela tr td{
                font-family: verdana;
                font-size: 11px;
                border-collapse: collapse;
                border:1px solid #596d9b;
                padding: 0 10px;
            }
            tr th a {color:white !important;}
            tr th a:hover {color:blue !important;}

            div.formulario form p{ margin:0; padding:0; }
        </style>
        <script>
            function detalhe(abertura, fabricacao, meses, peca,  familia) {
                if (!peca) { peca = ''};
                if (!familia) { familia = '' };

                var url = "relatorio_extratificacao_pareto_detalhe.php?ab=" + abertura + "&fb=" + fabricacao + "&fm=" + meses + "&p=" + peca + "&f=" + familia;
                window.open (url, "detalhe", "height=640,width=1040,scrollbars=1");
            }

            function sem_peca(abertura, fabricacao, meses, familia) {
                var url = "relatorio_extratificacao_pareto_sem_peca.php?ab=" + abertura + "&fb=" + fabricacao + "&fm=" + meses + "&f=" + familia;
                window.open (url, "detalhe", "height=640,width=1040,scrollbars=1");
            }

	    function download(link) {
                window.location=link;
            }

        </script>
        <?php if (!empty($conteudo) and $conteudo <> "Dados não encontrados!"): ?>
            <script type="text/javascript" src="../js/jquery-1.4.2.js"></script>
            <script src="http://code.highcharts.com/highcharts.js"></script>
            <script src="http://code.highcharts.com/modules/exporting.js"></script>

            <script>
                $(function () {
                    var chart;
                    $(document).ready(function() {
                        chart = new Highcharts.Chart({
                            chart: {
                                renderTo: 'container',
                                zoomType: 'xy'
                            },
                            title: {
                                text: 'Defeitos constatados sem pedido de peça'
                            },
                            subtitle: {
                                text: '<?php
                                        echo $familia_descricao;
                                        echo ' - Produto fabricado em: ' . $arr_fabricacao[1] . '/' . $arr_fabricacao[0];
                                        ?>'
                            },
                            credits: {
                                enabled: false
                            },
                            xAxis: [{
                                categories: <?php echo json_encode($categorias) ?>
                            }],
                            yAxis: [{
                                title: {
                                    text: ''
                                },
                                labels: {
                                    formatter: function() {
                                        return this.value +' %';
                                    },
                                        style: {
                                            color: '#A0A0A0'
                                        }
                                }
                            }, {
                                title: {
                                    text: ''
                                },
                                labels: {
                                    formatter: function() {
                                        return this.value;
                                    },
                                        style: {
                                            color: '#4572A7'
                                        }
                                },
                                    opposite: true
                            }],
                            tooltip: {
                                formatter: function() {
                                    return ''+
                                        this.x +': '+ this.y +
                                        (this.series.name == 'Perc' ? ' %' : '');
                                }
                            },
                                legend: {
                                    enabled: false
                                },
                                series: [{
                                    name: 'Qtde',
                                        color: '#4572A7',
                                        type: 'column',
                                        yAxis: 1,
                                        data: <?php echo json_encode($totais) ?>

                                }, {
                                    name: 'Perc',
                                        color: '#FF0000',
                                        type: 'spline',
                                        data: <?php echo json_encode($percentuais) ?>
                                }]
                        });
                    });

                });
            </script>
        <?php endif; ?>
    </head>
    <body>
        <div class="conteudo">
            <?php echo $conteudo; ?>
        </div>
    </body>
</html>

