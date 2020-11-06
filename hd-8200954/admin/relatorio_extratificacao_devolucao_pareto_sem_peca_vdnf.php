<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$produtos = array();
$posto = 0;

if (!empty($_GET['ab'])) {
    $abertura = $_GET['ab'];
}

if (!empty($_GET['nf'])) {
    $data_fabricacao = $_GET['nf'];
}

if (!empty($_GET['f'])) {
    $familia = $_GET['f'];
}

if (empty($abertura) or empty($data_fabricacao) or empty($familia)) {
    $conteudo = "Dados não encontrados!";
}

if (!empty($_GET['fm'])) {
    $meses = $_GET['fm'];
} else {
    $meses = $_POST['meses'];
}

if (!empty($_GET['defeito'])) {
    $defeito = $_GET['defeito'];
} else {
    $defeito = $_POST['defeito'];
}

if(!empty($defeito)){
    $cond = " tbl_os_laudo.defeito_constatado = $defeito ";
}else{
    $cond = "";
}

if (!empty($_GET['pd'])) {
    $produtos = explode('|', $_GET['pd']);
}

if (!empty($_POST['produtos'])) {
    $produtos = explode('|', $_POST['produtos']);
}

if (!empty($_GET['po'])) {
    $posto = explode('|', $_GET['po']);
}

if (!empty($_POST['posto'])) {
    $posto = explode('|', $_POST['po']);  
}

if (!empty($_POST['limit'])) {
    $limit = $_POST['limit'];
} else {
    $limit = 5;
}

if (empty($conteudo)) {
    $abertura_request = $abertura;
    $data_fabricacao_request = $data_fabricacao;

    $abertura = $abertura . '-01';
    $data_fabricacao = $data_fabricacao . '-01';

    $arr_abertura = explode('-', $abertura);
    $arr_data_fabricacao = explode('-', $data_fabricacao);

    $cond_produtos = '';

    if (!empty($produtos)) {
        if ($login_fabrica == '24') {
            $tbl_numero_serie = '';
        } else {
            $tbl_numero_serie = 'tbl_numero_serie.';
        }

        $cond_produtos = ' AND ' . $tbl_numero_serie . 'produto IN (' . implode(',', $produtos) . ') ' ;
    }

    $cond_posto = '';

    /*if (!empty($posto)) {
        $cond_posto = ' AND tbl_os.posto IN (' . implode(',', $posto) . ') ' ;
    }*/  //tbl_os_laudo não tem campo posto

    if ($login_fabrica == '24') {
        $tbl_ns = 'suggar_devolucao_numero_serie';
        $tbl_from = "suggar_os_devolucao_serie";
        $tbl_produto = $tbl_from . '.';
        $getos = ', os_laudo ';
        $join = " JOIN suggar_os_serie_{$familia} USING(serie, produto) ";
        $conds = "WHERE data_fabricacao BETWEEN '$data_fabricacao' AND (('$data_fabricacao'::date + interval '1 month') - interval '1 day')::date 
                    AND data_abertura BETWEEN '$abertura' AND (('$abertura'::date + interval '$meses month') - interval '1 day')::date 
                    $cond_produtos";
    } else {
        /**
         * @TODO
         */
    }
   
    $qryMeses = pg_query($con, "SELECT fn_qtos_meses_entre('$data_fabricacao', '$abertura')");
    $qtos_meses = pg_fetch_result($qryMeses, 0, 0);

    $sql1 = "
        select distinct(os_laudo) as os_laudo, 
        serie,
        produto,
        data_fabricacao,
        data_nf, 
        data_recebimento 

        into temp temp_oss 
        
        from $tbl_from
        join $tbl_ns
        USING(serie,produto) 
        where data_fabricacao 
        between '$data_fabricacao' and (('$data_fabricacao'::date + interval '1 month') - interval '1 day')::date 
        and data_nf >= data_fabricacao 
        order by data_nf;
    ";

    $qry = pg_query($sql1);

    $sql2 = "
        select count(os_laudo) as total, 
        case fn_qtos_meses_entre(data_nf, data_recebimento) when 0 THEN 1 else fn_qtos_meses_entre(data_nf, data_recebimento) end as meses  
        into temp temp_total_os 
        from temp_oss 
        group by meses 
        order by meses;
    ";
    $qry = pg_query($sql2);

    if ($meses > 1) {
        $cond_meses = '<> 0';
    } else {
        $cond_meses = '= '. $qtos_meses;
    }

    $sql3 = "SELECT serie, produto, os_laudo, data_fabricacao into temp temp_numero_serie from temp_oss where (select case fn_qtos_meses_entre(data_nf, data_recebimento) when 0 THEN 1 else fn_qtos_meses_entre(data_nf, data_recebimento) end as meses ) $cond_meses $cond_produtos;";
    $sql3 .= "CREATE INDEX idx_temp_nsos ON temp_numero_serie(os_laudo);";
    $sql3 .= "CREATE INDEX idx_temp_nspr ON temp_numero_serie(produto);";
    $sql3 .= "CREATE INDEX idx_temp_nsse ON temp_numero_serie(serie);";

    $qry = pg_query($sql3);

    if (!empty($cond)) {
        $sql = "
            SELECT 
            tbl_os_laudo.serie,
            tbl_os_laudo.produto,
            tbl_os_laudo.os_laudo,  
            tbl_defeito_constatado.descricao as constatado, 
            to_char(temp_numero_serie.data_fabricacao, 'DD/MM/YYYY') as data_fabricacao 
            into temp temp_os
            from temp_numero_serie
            join tbl_os_laudo using(os_laudo)  
            LEFT JOIN tbl_defeito_constatado ON tbl_os_laudo.defeito_constatado = tbl_defeito_constatado.defeito_constatado   
            where $cond;
            select * from temp_os;;
            ";
    } else {
        $sql = "

            SELECT distinct(temp_numero_serie.os_laudo) AS os_laudo
            INTO TEMP temp_os_com_peca
            FROM temp_numero_serie
            JOIN tbl_os_laudo_peca on temp_numero_serie.os_laudo = tbl_os_laudo_peca.os_laudo

            JOIN tbl_peca ON tbl_os_laudo_peca.peca = tbl_peca.peca;

            select
                tbl_os_laudo.serie,
                tbl_os_laudo.produto,
                tbl_os_laudo.os_laudo,
                tbl_defeito_constatado.descricao as constatado
            INTO TEMP temp_os
            FROM tbl_os_laudo
            JOIN tbl_produto ON tbl_os_laudo.produto = tbl_produto.produto

            LEFT JOIN tbl_defeito_constatado ON tbl_os_laudo.defeito_constatado = tbl_defeito_constatado.defeito_constatado

            WHERE tbl_os_laudo.os_laudo IN (SELECT distinct os_laudo FROM temp_numero_serie WHERE os_laudo NOT IN (SELECT os_laudo FROM temp_os_com_peca))
            $cond_posto ;
            
            CREATE INDEX idx_temp_oss ON temp_os(serie);
            CREATE INDEX idx_temp_osp ON temp_os(produto);
            CREATE INDEX idx_temp_osos ON temp_os(os_laudo);
            
            SELECT distinct temp_os.os_laudo, constatado, 
                    temp_numero_serie.serie,
                    to_char(temp_numero_serie.data_fabricacao, 'DD/MM/YYYY') as data_fabricacao
                FROM temp_os
                JOIN temp_numero_serie ON temp_os.serie = temp_numero_serie.serie
                AND temp_numero_serie.produto = temp_os.produto
            ";
    }
    $query = pg_query($con, $sql);

    $percentual = 0;

    //print_r(pg_fetch_assoc($query));

    $categorias = array();
    $totais = array();
    $percentuais = array();

    if (pg_num_rows($query) > 0) {
        $conteudo = '<table class="tabela" cellspacing="1" align="center">';
        $conteudo.= '<tr class="titulo_tabela">';
        $conteudo.= '<th>OS</th>';
        //$conteudo.= '<th>Tipo de OS</th>';
        //$conteudo.= '<th style="width: 260px;">Defeito Reclamado</th>';
        $conteudo.= '<th>Defeito Constatado</th>';
        $conteudo.= '<th>Número Série</th>';
        $conteudo.= '<th>Data NF</th>';

        while ($fetch = pg_fetch_assoc($query)) {
            $os_laudo = $fetch['os_laudo'];
            $total = (int) $fetch['total'];

            $defeito_reclamado = $fetch['reclamado'];
            $defeito_constatado = $fetch['constatado'];
            if (empty($defeito_constatado)) {
                $defeito_constatado = '&nbsp;';
            }

            $serie = $fetch['serie'];
            $data_fabricacao = $fetch['data_fabricacao'];       

            $conteudo.= '<tr>';
            $conteudo.= '<td><a href="informacao_devolucao.php?os_laudo=' . $os_laudo . '" target="_blank">' . $os_laudo . '</a></td>';
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
        $conteudo.= '<form method="post" action="">';
        $conteudo.= '<input type="hidden" name="abertura" value="' . $abertura_request . '" />';
        $conteudo.= '<input type="hidden" name="data_fabricacao" value="' . $data_fabricacao_request . '" />';
        $conteudo.= '<input type="hidden" name="meses" value="' . $meses . '" />';
        $conteudo.= '<input type="hidden" name="familia" value="' . $familia . '" />';
        $conteudo.= '<input type="hidden" name="posto" value="' . $posto . '" />';
        $conteudo.= '<input type="hidden" name="produtos" value="' . implode('|', $produtos) . '" />';
        $conteudo.= 'Qtde de defeitos: <input type="input" name="limit" value="' . $limit . '" style="width: 30px;" />';
        $conteudo.= '<span style="padding-left: 10px;"><input type="submit" value="Enviar" /></span>';
        $conteudo.= '</form>';
        $conteudo.= '</div>';

        $destino = dirname(__FILE__) . '/xls';
        date_default_timezone_set('America/Sao_Paulo');
        $data = date('YmdGis');
        $arq_nome = 'relatorio_extratificacao_defeitos_defeitos-' . $login_fabrica . $data . '.xls';
        $file = $destino . '/' . $arq_nome ;
        $f = fopen($file, 'w');
        fwrite($f, $conteudo);
        fclose($f);

         $sql = "
            select constatado,
                count(distinct(temp_os.os_laudo)) AS total
            FROM temp_os
            JOIN temp_numero_serie ON temp_os.serie = temp_numero_serie.serie
                AND temp_numero_serie.produto = temp_os.produto
            GROUP BY constatado
            ORDER BY count(temp_os.os_laudo) DESC limit $limit
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

            function defeito(abertura, fabricacao, meses, familia) {
                var url = "relatorio_extratificacao_pareto_defeito.php?ab=" + abertura + "&fb=" + fabricacao + "&fm=" + meses + "&f=" + familia;
                window.open (url, "detalhe", "height=640,width=1040,scrollbars=1");
            }

	    function download(link) {
                window.location=link;
            }

        </script>
        <?php if (!empty($conteudo) and $conteudo <> "Dados não encontrados!"): ?>
            <script type="text/javascript" src="../js/jquery-1.4.2.js"></script>
            <script src="js/highcharts.js"></script>
            <script src="js/exporting.js"></script>

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
                                        echo ' - Produto fabricado em: ' . $arr_data_fabricacao[1] . '/' . $arr_data_fabricacao[0];
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

