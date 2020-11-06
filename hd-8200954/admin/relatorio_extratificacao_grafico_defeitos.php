<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include "autentica_admin.php";

include 'relatorio_extratificacao.class_vdnf.php';

$relatorio = new relatorioExtratificacao;

$cond_peca = '';
$cond_familia = '';
$joinOSSerie = '';
$produtos = array();
$posto = 0;

if (!empty($_GET['matriz_filial'])) {
    $matriz_filial = $_GET['matriz_filial'];
}

if($matriz_filial == '02'){
    $cond_matriz_filial = " AND suggar_os_serie.matriz IS TRUE ";
}else{
    $cond_matriz_filial = " AND suggar_os_serie.matriz IS FALSE ";
}

if (!empty($_GET['ab'])) {
	$abertura = $_GET['ab'];
	$ab_data = $_GET['ab'];
}

if (!empty($_GET['nf'])) {
	$data_fabricacao = $_GET['nf'];
	$nf_data = $_GET['nf'];
}

if (!empty($_GET['p'])) {
	$peca = $_GET['p'];
	$pecas = explode('|', $peca);

	if (count($pecas) == 1) {
		$peca = (int) $pecas[0];
		$cond_peca = " AND tbl_peca.peca = $peca ";
	} else {
		$cond_peca = ' AND tbl_os_item.peca IN (' . implode(', ', $pecas) . ') ';
	}
}

if (!empty($_GET['fo'])) {
    $fo = $_GET['fo'];
    $relatorio->setFornecedores(explode('|', $fo));
}

if (!empty($_GET['d'])) {
    $d = $_GET['d'];
    $exp = explode('|', $d);

    foreach ($exp as $val) {
        if (!empty($val)) {
            $a = explode('*', $val);
            $b = explode(';', $a[1]);

            foreach ($b as $c) {
                $d = explode(':', $c);

                if (count($d) == 2) {
                    $datas[$a[0]][$d[0]] = $d[1];
                }
            }
        }
    }

    $relatorio->setDatas($datas);
}

if (!empty($_GET['f'])) {
    $familia = $_GET['f'];
    $cond_familia = " AND tbl_produto.familia = $familia ";
}

if (!empty($_GET['pd'])) {
    $produtos = explode('|', $_GET['pd']);
    $pd_prod = $_GET['pd'];
}

if (!empty($_GET['po'])) {
    $posto = $_GET['po'];
}

$cond_conversor = "";

if (!empty($_GET["dcg"]) and $_GET["dcg"] == "true") {
    $cond_conversor = " AND defeito_constatado <> 23118 AND solucao_os <> 4504 ";
}

if (empty($abertura) or empty($data_fabricacao)) {
	$conteudo = "Dados não encontrados!";
}

if (!empty($_GET['fm'])) {
	$meses = $_GET['fm'];
} else {
	$meses = 1;
}

$categorias_servico = array();
$totais_servico = array();
$categorias_defeito = array();
$totais_defeito = array();

if (empty($conteudo)) {
	$abertura = $abertura . '-01';
	$data_fabricacao = $data_fabricacao . '-01';

    $condFornecedores = $relatorio->montaCondFornecedores();
    $condDatas = $relatorio->montaCondDatas();

    if (!empty($condFornecedores) or !empty($condDatas)) {
        if (count($pecas) == 1) {
            $peca = (int) $pecas[0];
            $condpeca = " AND peca = $peca ";
        } else {
            $condpeca = ' AND peca IN (' . implode(', ', $pecas) . ') ';
        }

        $sqlns =  "SELECT serie INTO TEMP temp_fornecedor_serie 
                    FROM tbl_ns_fornecedor 
                    JOIN tbl_numero_serie USING(numero_serie)
                    WHERE  tbl_ns_fornecedor.fabrica = $login_fabrica $condFornecedores $condDatas $condpeca
                    ";
        $resns = pg_query($con,$sqlns);
        $joinNSFornecedor = ' JOIN temp_fornecedor_serie ON temp_fornecedor_serie.serie = temp_numero_serie.serie ';
    } else {
        $joinNSFornecedor = '';
    }

    $cond_datas = '';

    if (!empty($condDatas)) {
        $cond_datas = ' AND (';
        $cond_datas.= implode('OR', $condDatas);
        $cond_datas.= ')';
    }

    $cond_produtos = '';

    if (!empty($produtos)) {
        if ($login_fabrica == '24') {
            $tbl_numero_serie = '';
        } else {
            $tbl_numero_serie = 'tbl_numero_serie.';
        }

        $cond_produtos = ' AND ' . $tbl_numero_serie . 'produto IN (' . implode(',', $produtos) . ') ' ;
    }

    if ($login_fabrica != 120) {
    	$descDefeito = " ,tbl_defeito.descricao AS desc_defeito, tbl_defeito.defeito AS cod_defeito ";
    	$joinDefeito = " LEFT JOIN tbl_defeito ON tbl_os_item.defeito = tbl_defeito.defeito ";
    }

    $cond_posto = '';

    if (!empty($posto)) {
        $arr_posto = explode('|', $posto);
        $cond_posto = ' AND tbl_os.posto IN (' . implode(',', $arr_posto) . ') ';
    }

   
    $tbl_ns = 'suggar_numero_serie';
    $tbl_from = "suggar_os_serie";
    $tbl_produto = $tbl_from . '.';
    $getos = ', os';
    $join = " JOIN suggar_os_serie USING(serie, produto) ";
    $conds = "WHERE data_fabricacao BETWEEN '$data_fabricacao' AND (('$data_fabricacao'::date + interval '1 month') - interval '1 day')::date 
                AND data_abertura BETWEEN '$abertura' AND (('$abertura'::date + interval '$meses month') - interval '1 day')::date 
                $cond_produtos";
    

    $qryMeses = pg_query($con, "SELECT fn_qtos_meses_entre('$data_fabricacao', '$abertura')");
    $qtos_meses = pg_fetch_result($qryMeses, 0, 0);

    $sql1 = "
        select distinct(os) as os, 
        serie,
        produto,
        data_fabricacao,
        data_nf, 
        data_abertura into temp temp_oss 
        from $tbl_from
        join $tbl_ns
        USING(serie,produto) 
        where data_fabricacao 
        between '$data_fabricacao' and (('$data_fabricacao'::date + interval '1 month') - interval '1 day')::date 
        and data_nf >= data_fabricacao 
        $cond_conversor
        $cond_matriz_filial
        AND suggar_os_serie.familia = $familia
        order by data_nf;
    ";

    $sql2 = "
        select count(os) as total, 
        fn_qtos_meses_entre(data_fabricacao, data_abertura) as meses 
        into temp temp_total_os 
        from temp_oss 
        group by meses 
        order by meses;
    ";

    if ($meses > 1) {
        $cond = '<> 0';
    } else {
        $cond = '= '. $qtos_meses;
    }

    $sql3 = "SELECT os, data_fabricacao,serie into temp temp_numero_serie from temp_oss where (select fn_qtos_meses_entre(data_fabricacao, data_abertura)) $cond $cond_produtos;";
    $sql3 .= "CREATE INDEX idx_temp_nsos ON temp_numero_serie(os)";

    $qry = pg_query($sql1 . $sql2 . $sql3);

	$sql = "
	
        select
                DISTINCT tbl_peca.referencia,
                tbl_os.defeito_constatado AS df,
                tbl_os.os, 
                tbl_os.sua_os, 
                tbl_os.serie,
                tbl_os.produto,
                tbl_servico_realizado.descricao as desc_serv_real, 
                tbl_os.defeito_reclamado_descricao, 
                tbl_defeito_reclamado.descricao AS defeito_reclamado,
                tbl_os.consumidor_revenda, 
                to_char(temp_numero_serie.data_fabricacao, 'DD/MM/YYYY') as data_fabricacao,
                tbl_defeito_constatado.descricao as defeito_constatado, 
                tbl_os_item.os_item,
                tbl_os_item.peca
                $descDefeito,
                tbl_posto.nome as razao_social_posto,
                tbl_posto_fabrica.contato_cidade as posto_cidade,
                tbl_posto_fabrica.contato_estado as posto_estado,
                tbl_peca.descricao
                into temp temp_os
            FROM tbl_os
            JOIN temp_numero_serie USING(os)
            JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os 
            JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto 
            JOIN tbl_posto_fabrica ON tbl_posto_fabrica.fabrica = tbl_os.fabrica AND
                                      tbl_posto_fabrica.posto = tbl_os.posto

            JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto

            JOIN tbl_servico_realizado ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado 
            JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto 
            JOIN tbl_peca ON tbl_os_item.peca = tbl_peca.peca
            $joinDefeito
            LEFT JOIN tbl_defeito_constatado ON tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado 
            LEFT JOIN tbl_defeito_reclamado ON tbl_os.defeito_reclamado = tbl_defeito_reclamado.defeito_reclamado
            WHERE 1=1 $cond_peca $cond_datas $cond_posto
            ORDER BY tbl_os.sua_os, tbl_peca.referencia, tbl_peca.descricao, data_fabricacao; 
		";
	$query = pg_query($con, $sql);

	$sql_total_geral = "SELECT count(os) AS total_geral FROM temp_os";
	$query_total_geral = pg_query($con, $sql_total_geral);
	$total_geral = pg_fetch_result($query_total_geral, 0, 'total_geral');

    $sql_total_defeito = "SELECT df, defeito_constatado, count(os) AS total_defeito FROM temp_os GROUP BY df, defeito_constatado ORDER BY count(os) DESC";
	
    if ($login_fabrica == 24) {
        $sql_total_defeito = "SELECT cod_defeito, desc_defeito AS defeito_constatado, count(os) AS total_defeito FROM temp_os GROUP BY cod_defeito, desc_defeito ORDER BY count(os) DESC";
    }

	$query_total_defeito = pg_query($con, $sql_total_defeito);
	$fetch = pg_fetch_all($query_total_defeito);

	$categorias = array();
	$totais = array();
	$percentuais = array();

	$div = '<div><br /><br /></div>';

	if (count($fetch) > 0) {
		foreach ($fetch as $key => $value) {
		 	
			$defeito_constatado = $value['defeito_constatado'];
			//$peca = $fetch['peca'];
			$total = (int) $value['total_defeito'];

			$percentual_uniq = ($total / $total_geral) * 100;
			//$percentual = $percentual_uniq;

			$cat = utf8_encode($defeito_constatado);

			$categorias[] = $cat;
			$totais[] = $total;
			//$percentuais[] = round($percentual, 2);

		}
		
		$div .= '<div id="container" style="min-width: 400px; height: 400px; margin: 0 auto"></div>';
	} else {
		$div .= '<div id="container" style="min-width: 400px; height: 400px; margin: 0 auto"><h3>Dados Não Encontrados</h3></div>';
	}


}

?>
<html>
	<head>
		<title></title>
		<style>
			.conteudo { width: 1000px; height: 500px; }
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
            function detalhe(defeito = null) {
                let abertura = '<?=$ab_data?>'
                let data_fabricacao = '<?=$nf_data?>'
                let meses = '<?=$meses?>'
                let peca = '<?=$peca?>'
                let familia = '<?=$familia?>'
                let matriz_filial = '<?=$matriz_filial?>'
                let produtos = '<?=$pd_prod?>'
                let posto = '<?=$posto?>'
                let dcg = '<?=$dcg?>'

                if(!matriz_filial){matriz_filial = ''}
                if (!peca) { peca = ''}
                if (!familia) { familia = '' }
                if (!produtos) { produtos = '' }
                if (!posto) { posto = '' }
                if (!dcg) { dcg = '' }
                if (!defeito) { defeito = '' }

                let url = "relatorio_extratificacao_pareto_detalhe_vdnf.php?ab=" + abertura + "&nf=" + data_fabricacao + "&fm=" + meses + "&p=" + peca + "&f=" + familia +"&matriz_filial="+ matriz_filial+ "&pd=" + produtos + "&po=" + posto + "&dcg=" + dcg + "&def=" + defeito
                
                window.open (url, "detalhe_defeito", "height=640,width=1040,scrollbars=1")
            }
		</script>
		<?php if (count($fetch) > 0) { ?>
			<script type="text/javascript" src="../js/jquery-1.4.2.js"></script>
			<script src="js/highcharts.js"></script>
			<script src="js/exporting.js"></script>

			<script>
				$(function () {
					var chart;
					$(document).ready(function() {
						chart = new Highcharts.Chart({
							chart: {
								type: 'column',
								renderTo: 'container',
								zoomType: 'xy'
							},
							title: {
								text: 'Motivo(s) da(s) Quebra(s)'
							},
							subtitle: {
								text: 'Defeito Constatado Informado na OS'
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
								}
							}], 
							plotOptions: {
						        series: {
						        	dataLabels: {
									  enabled: true
									},
						            cursor: 'pointer',
						            point: {
						            	events: {
							                click: function (event) {
							                	detalhe(this.category)
							                }
						            	}
						            }
						        }
						    },
							series: [{
								name: ['Qtde(s)'],
								data: <?php echo json_encode($totais) ?>

							}]
						});
					});

				});
			</script>
		<?php } ?>
	</head>
	<body>
		<div class="conteudo">
			<?=$div?>
		</div>
	</body>
</html>

