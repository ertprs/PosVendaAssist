<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$produtos = array();
$pecas = array();
$fornecedores = array();
$posto = 0;
$regiao = '';

if (!empty($_GET['ab'])) {
    $abertura = $_GET['ab'];
}

if (!empty($_GET['fb'])) {
    $fabricacao = $_GET['fb'];
}

echo "fabricacao = $fabricacao";

if (!empty($_GET['f'])) {
    $familia = $_GET['f'];
}
echo "passou aqui";
if (empty($abertura) or empty($fabricacao) or empty($familia)) {
   // $conteudo = "Dados não encontrados!";
}
echo "passou aqui2";
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
    $cond = " AND tbl_os.defeito_constatado = $defeito ";
}else{
    $cond = " AND tbl_os_produto.os_produto IS NULL ";
}

if (!empty($_GET['rv'])) {
    $revendas = explode("|", $_GET['rv']);
}

if (!empty($_GET['pd'])) {
    $produtos = explode('|', $_GET['pd']);
}

if (!empty($_POST['produtos'])) {
    $produtos = explode('|', $_POST['produtos']);
}

if (!empty($_POST['pc'])) {
    $pecas = explode('|', $_POST['pc']);
}

if (!empty($_POST['fo'])) {
    $fornecedores = explode('|', $_POST['fo']);
}
if (!empty($_GET['po'])) {
    $posto = explode('|', $_GET['po']);
}

if (!empty($_POST['posto'])) {
    $posto = explode('|', $_POST['po']);
}

if (!empty($_GET['regiao'])) {
    $regiao = (int) $_GET['regiao'];
}

if (!empty($_POST['regiao'])) {
    $regiao = (int) $_POST['regiao'];
}

if (!empty($_POST['limit'])) {
    $limit = $_POST['limit'];
} else {
    $limit = 5;
}

$joinOSSerie = '';
echo "---$conteudo";
if (empty($conteudo)) {
    echo "passou aqui 3";
    $abertura_request = $abertura;
    $fabricacao_request = $fabricacao;

    $abertura   = $abertura . '-01';
    $fabricacao = $fabricacao . '-01';

    $arr_abertura = explode('-', $abertura);
    $arr_fabricacao = explode('-', $fabricacao);

    $cond_produtos = '';
	$cond_pecas = "";
	$cond_fornecedores = "";

    if (!empty($produtos)) {
        
        $tbl_numero_serie = 'tbl_numero_serie.';
        
        $cond_produtos = ' AND ' . $tbl_numero_serie . 'produto IN (' . implode(',', $produtos) . ') ' ;
    }

    $cond_posto = '';

    if (!empty($posto)) {
        $cond_posto = ' AND tbl_os.posto IN (' . implode(',', $posto) . ') ' ;
    }

	if (count($pecas) > 0) {
		$cond_pecas = " AND tbl_ns_fornecedor.peca in (".implode(',',$pecas).")";
		$join_ns_fornecedor = " JOIN tbl_ns_fornecedor ON temp_numero_serie.numero_serie = tbl_ns_fornecedor.numero_serie ";
	}

	if (count($fornecedores) > 0) {
		$cond_fornecedores = " AND tbl_ns_fornecedor.nome_fornecedor in ('".implode("','",$fornecedores)."')";
		$join_ns_fornecedor = " JOIN tbl_ns_fornecedor ON temp_numero_serie.numero_serie = tbl_ns_fornecedor.numero_serie ";
	}

    if ($login_fabrica == '50') {
        $tbln1 = 'colormaq_numero_serie1';
        $tbln2 = 'colormaq_numero_serie2';
        $tbl_produto = '';
        $join = '';
        $conds = "WHERE familia in ( $familia ) " . $cond_produtos;
        if(!empty($revendas)){
            $cond_revendas = " AND colormaq_os_serie.cnpj IN ('".implode("','", $revendas)."')";
        }
    } else {
        $tbln1 = 'tbl_numero_serie';
        $tbln2 = 'tbl_numero_serie';
        $tbl_produto = 'tbl_produto.';
        $join = 'JOIN tbl_produto ON tbl_numero_serie.produto = tbl_produto.produto';
        $conds = "WHERE tbl_numero_serie.fabrica = $login_fabrica $cond_familia $cond_produtos";
    }

    $sqlN = "SELECT numero_serie, serie, {$tbl_produto}produto, data_fabricacao, $login_fabrica as fabrica
			INTO TEMP n1
			FROM $tbln1 $join $conds
			AND {$tbln1}.data_fabricacao between '$fabricacao' and (('$fabricacao'::date + interval '1 month') - interval '1 day')::date;

			SELECT numero_serie,serie, {$tbl_produto}produto, data_fabricacao, $login_fabrica as fabrica
			INTO TEMP n2
			FROM $tbln2 $join $conds
			AND {$tbln2}.data_fabricacao between '$fabricacao' and (('$fabricacao'::date + interval '1 month') - interval '1 day')::date;

			SELECT numero_serie, serie, produto, data_fabricacao,fabrica
			INTO TEMP temp_numero_serie
			FROM (select * from n1 union select * from n2) x;

            CREATE INDEX idx_temp_nss ON temp_numero_serie(serie);
            CREATE INDEX idx_temp_nsp ON temp_numero_serie(produto);
            CREATE INDEX idx_temp_nsns ON temp_numero_serie(numero_serie);
		";


	echo nl2br($sqlN);

	$resN = pg_query($con, $sqlN);
    echo "<br><br>Error 1".pg_last_error();

	 /*$sql = "
        select
            tbl_os.os,
            tbl_os.sua_os,
            tbl_os.consumidor_revenda,
            numero_serie.serie,
            to_char(numero_serie.data_fabricacao, 'DD/MM/YYYY') as data_fabricacao,
            tbl_os.defeito_reclamado_descricao as reclamado,
            tbl_defeito_constatado.descricao as constatado
        FROM tbl_os
        JOIN numero_serie ON tbl_os.serie = numero_serie.serie
            AND numero_serie.produto = tbl_os.produto AND numero_serie.fabrica = $login_fabrica
        JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
        LEFT JOIN tbl_defeito_constatado ON tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado
        LEFT JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os
        WHERE tbl_os.fabrica = $login_fabrica
        AND tbl_produto.familia = $familia
        AND tbl_os.data_abertura between '$abertura' and (('$abertura'::date + interval '$meses month') - interval '1 day')::date
        $cond  $cond_posto
        ORDER BY numero_serie.data_fabricacao
        "; */

    $join_tbl_posto = '';
    $cond_posto_estado = '';

    if (!empty($regiao)) {
        $qry_estados = pg_query($con, "SELECT estados_regiao FROM tbl_regiao WHERE regiao = $regiao");
        $estados_regiao = pg_fetch_result($qry_estados, 0, 'estados_regiao');

        if (!empty($estados_regiao)) {
            $tmp = explode(',', $estados_regiao);
            $quoted = array();

            foreach ($tmp as $t) {
                $quoted[] = "'" . trim($t) . "'";
            }

            $estados_regiao = implode(', ', $quoted);

            $join_tbl_posto = " JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto ";
            $cond_posto_estado = " AND tbl_posto.estado IN ($estados_regiao) ";
        }
    }

    if ($familia == 'global') {
        $cond_familia = '';
    } else {
        $cond_familia = " AND tbl_produto.familia in( $familia )";
    }

    $sql = "
        SELECT
            tbl_os.serie,
            tbl_os.produto,
	        tbl_produto.referencia,
            tbl_os.os,
            tbl_os.sua_os,
            tbl_os.consumidor_revenda,
            tbl_os.defeito_reclamado AS reclamado_descricao,
            tbl_os.defeito_reclamado_descricao as reclamado,
            tbl_defeito_constatado.descricao as constatado
        INTO TEMP temp_os
        FROM tbl_os
        $joinOSSerie
        JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
        $join_tbl_posto
        LEFT JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os
        LEFT JOIN tbl_defeito_constatado ON tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado
        WHERE tbl_os.fabrica = $login_fabrica
		$cond_familia $cond_revendas
		        AND tbl_os.data_abertura between '$abertura' and (('$abertura'::date + interval '$meses month') - interval '1 day')::date
        $cond  $cond_posto $cond_posto_estado ;

        CREATE INDEX idx_temp_oss ON temp_os(serie);
        CREATE INDEX idx_temp_osp ON temp_os(produto);
        CREATE INDEX idx_temp_osos ON temp_os(os);

        SELECT os, sua_os, referencia,consumidor_revenda, reclamado, reclamado_descricao, constatado,
                temp_numero_serie.serie,
                to_char(temp_numero_serie.data_fabricacao, 'DD/MM/YYYY') as data_fabricacao
            FROM temp_os
            JOIN temp_numero_serie ON temp_os.serie = temp_numero_serie.serie
			AND temp_numero_serie.produto = temp_os.produto
			$join_ns_fornecedor 
			$cond_pecas 
			$cond_fornecedores

        ";

	echo "<Br><br>".nl2br($sql);
    $query = pg_query($con, $sql);
    echo "<br><br> Error 2". pg_last_error();
    $percentual = 0;

    $categorias = array();
    $totais = array();
    $percentuais = array();

    if (pg_num_rows($query) > 0) {
        //$prepare = pg_prepare("os_item", "select distinct os from tbl_os join tbl_os_produto using (os) join tbl_os_item using (os_produto) where os = $1");

        $conteudo = '<table class="tabela" cellspacing="1" align="center">';
        $conteudo.= '<tr class="titulo_tabela">';
        $conteudo.= '<th>OS</th>';
        $conteudo.= '<th>Produto</th>';
        $conteudo.= '<th>Tipo de OS</th>';
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

            $reclamado_descricao = $fetch['reclamado_descricao'];

            if(strlen($reclamado_descricao) > 0){

                $sql_def = "SELECT descricao FROM tbl_defeito_reclamado WHERE defeito_reclamado = {$reclamado_descricao}";
                $res_def = pg_query($con, $sql_def); 

                $defeito_reclamado = pg_fetch_result($res_def, 0, 'descricao');

            }else{
                $defeito_reclamado = $fetch['reclamado'];
            }

            $referencia_produto = $fetch['referencia'];
            $defeito_constatado = $fetch['constatado'];
            if (empty($defeito_constatado)) {
                $defeito_constatado = '&nbsp;';
            }

            $sua_os = $fetch['sua_os'];
            $serie = $fetch['serie'];
            $data_fabricacao = $fetch['data_fabricacao'];

            switch ($fetch['consumidor_revenda']) {
                case 'C':
                    $consumidor_revenda = 'CONSUMIDOR';
                    break;
                case 'R':
                    $consumidor_revenda = 'REVENDA';
                    break;
                default:
                    $consumidor_revenda = '&nsbp;';
                    break;
            }

            $conteudo.= '<tr>';
            $conteudo.= '<td><a href="os_press.php?os=' . $os . '" target="_blank">' . $sua_os . '</a></td>';
            $conteudo.= '<td>' . $referencia_produto . '</td>';
            $conteudo.= '<td>' . $consumidor_revenda . '</td>';
            $conteudo.= '<td>' . $defeito_reclamado . '</td>';
            $conteudo.= '<td>' . $defeito_constatado . '</td>';
            if ($login_fabrica == 50) {
                $conteudo.= '<td>' . $serie . '__replaceC__</td>';
            } else {
                $conteudo.= '<td>' . $serie . '</td>';
            }
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
    if ($login_fabrica == 50) {
        fwrite($f, preg_replace("/__replaceC__/", "C", $conteudo));
    } else {
        fwrite($f, $conteudo);
    }
	fclose($f);

        $conteudo.= '<div align="center"><input type="button" value="Download do arquivo Excel" onClick="download(\'xls/' . $arq_nome . '\')" /></div><br/>';

        $conteudo.= '<div id="container" style="min-width: 400px; height: 400px; margin: 0 auto"></div>';

        $conteudo.= '<div style="margin-top: 40px;" align="center">';
        $conteudo.= '<form method="post" action="">';
        $conteudo.= '<input type="hidden" name="abertura" value="' . $abertura_request . '" />';
        $conteudo.= '<input type="hidden" name="fabricacao" value="' . $fabricacao_request . '" />';
        $conteudo.= '<input type="hidden" name="meses" value="' . $meses . '" />';
        $conteudo.= '<input type="hidden" name="familia" value="' . $familia . '" />';
        $conteudo.= '<input type="hidden" name="posto" value="' . $posto . '" />';
        $conteudo.= '<input type="hidden" name="regiao" value="' . $regiao . '" />';
        $conteudo.= '<input type="hidden" name="produtos" value="' . implode('|', $produtos) . '" />';
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
        if ($login_fabrica == 50) {
            fwrite($f, preg_replace("/__replaceC__/", "C", $conteudo));
        } else {
            fwrite($f, $conteudo);
        }
        fclose($f);

         $sql = "
            select constatado,
                count(os) AS total
            FROM temp_os
            JOIN temp_numero_serie ON temp_os.serie = temp_numero_serie.serie
                AND temp_numero_serie.produto = temp_os.produto
            GROUP BY constatado
            ORDER BY count(os) DESC limit $limit
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
            <?php
                if ($login_fabrica == 50) {
                    echo preg_replace("/__replaceC__/", "", $conteudo);
                } else {
                    echo $conteudo;
                }
            ?>
        </div>
    </body>
</html>

