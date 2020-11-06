<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$pecas = array();
$produtos = array();
$posto = 0;
$desconsidera_conversor = 'false';

if (!empty($_GET['ab'])) {
	$abertura = $_GET['ab'];
}

if (!empty($_GET['nf'])) {
	$data_fabricacao = $_GET['nf'];
}

if (!empty($_GET['fa'])) {
	$familia = $_GET['fa'];
}

if (!empty($_GET['fm'])) {
	$meses = $_GET['fm'];
}

if (!empty($_GET['p1'])) {
	$pecas[] = (int) $_GET['p1'];
}

if (!empty($_GET['p2'])) {
	$pecas[] = (int) $_GET['p2'];
}

if (empty($pecas) and !empty($_GET['pc'])) {
    $pecas = explode('|', $_GET['pc']);
}

if (!empty($_GET['fo'])) {
    $fo = $_GET['fo'];
}

if (!empty($_GET['d'])) {
    $d = $_GET['d'];
}

if (!empty($_GET['pd'])) {
    $produtos = explode('|', $_GET['pd']);
}

if (!empty($_GET['po'])) {
    $posto = $_GET['po'];
}

if (!empty($_GET["dcg"])) {
    $desconsidera_conversor = $_GET["dcg"];
}


if (!empty($_POST['abertura'])) {
	$abertura = $_POST['abertura'];
}

if (!empty($_POST['data_fabricacao'])) {
	$data_fabricacao = $_POST['data_fabricacao'];
}

if (!empty($_POST['familia'])) {
	$familia = $_POST['familia'];
}

if (!empty($_POST['meses'])) {
	$meses = $_POST['meses'];
}

if (!empty($_POST['produtos'])) {
    $produtos = explode('|', $_POST['produtos']);
}

if (!empty($_POST['posto'])) {
    $posto = $_POST['posto'];
}

if (!empty($_POST["dcg"])) {
    $desconsidera_conversor = $_POST["dcg"];
}


if (!empty($_POST['limit'])) {
	$limit = $_POST['limit'];
} else {
	$limit = 5;
}

if (empty($abertura) or empty($data_fabricacao)) {
	$conteudo = "Dados não encontrados!";
}

if (empty($meses)) {
	$meses = 1;
}

if($login_fabrica == 24){
    $matriz_filial = $_GET["matriz_filial"];
    if($matriz_filial == '02'){
        $cond_matriz_filial = " AND suggar_os_serie.matriz IS TRUE ";
    }else{
        $cond_matriz_filial = " AND suggar_os_serie.matriz IS FALSE ";
    }
}

if (empty($conteudo)) {
	if (!empty($pecas)) {
        $paramsProd = '';
        if (!empty($produtos)) {
            $paramsProd = '&pd=' . implode('|', $produtos);
        }

        $paramMatriz = '';
        if (!empty($_GET['matriz_filial'])) {
        	$paramMatriz = '&matriz_filial=' . $_GET['matriz_filial'];
        }

		echo '<meta http-equiv="Refresh" content="0; url=relatorio_extratificacao_pareto_detalhe_vdnf.php?ab=' . $abertura . '&nf=' . $data_fabricacao . '&fm=' . $meses . '&p=' . implode('|', $pecas) . '&f=' . $familia . '&fo=' . $fo . '&d=' . $d . '&po=' . $po . $paramsProd . $paramMatriz . '" />';
		exit;
	}

	$abertura_request = $abertura;
	$data_fabricacao_request = $data_fabricacao;

	$abertura = $abertura_request . '-01';
	$data_fabricacao = $data_fabricacao_request . '-01';

	$arr_abertura = explode('-', $abertura_request);
    $arr_data_fabricacao = explode('-', $data_fabricacao_request);

    $cond_produtos = '';
    $join_posto = '';
    $cond_posto = '';

    $cond_conversor = '';

    if ($desconsidera_conversor == 'true') {
        $cond_conversor = " AND defeito_constatado <> 23118 AND solucao_os <> 4504 ";
    }

    $qryMeses = pg_query($con, "SELECT fn_qtos_meses_entre('$data_fabricacao', '$abertura')");
    $qtos_meses = pg_fetch_result($qryMeses, 0, 0);

    if (!empty($produtos)) {
        if ($login_fabrica == '24') {
            $tbl_numero_serie = '';
        } else {
            $tbl_numero_serie = 'tbl_numero_serie.';
        }

        $cond_produtos = ' AND produto IN (' . implode(',', $produtos) . ') ' ;
        $cond_produtos2 = ' AND ' . $tbl_numero_serie . 'produto IN (' . implode(',', $produtos) . ') ' ;
    }

    if (!empty($posto)) {
        $arr_posto = explode('|', $posto);
        $join_posto = ' JOIN tbl_os USING (os) ';
        $cond_posto = ' AND tbl_os.posto IN (' . implode(',', $arr_posto) . ') ';
    }

    if ($login_fabrica == '24') {
        $tbl_ns = 'suggar_numero_serie';
        $tbl_from = "suggar_os_serie";
        $tbl_produto = $tbl_from . '.';
        $getos = ', os';
        $join = " JOIN suggar_os_serie USING(serie, produto) ";
        $conds = "WHERE data_fabricacao BETWEEN '$data_fabricacao' AND (('$data_fabricacao'::date + interval '1 month') - interval '1 day')::date 
                    AND data_abertura BETWEEN '$abertura' AND (('$abertura'::date + interval '$meses month') - interval '1 day')::date 
                    $cond_produtos2";
    } else {
        /**
         * @TODO
         */
    }

    $sql1 = "
        select distinct(os) as os, 
        serie,
        produto,
        data_nf, 
		data_fabricacao,
        data_abertura into temp temp_oss 
        from $tbl_from
        join $tbl_ns
        USING(serie,produto) 
        where data_fabricacao 
        between '$data_fabricacao' and (('$data_fabricacao'::date + interval '1 month') - interval '1 day')::date 
        and data_nf >= data_fabricacao
        AND suggar_os_serie.familia = $familia
        $cond_conversor
        $cond_matriz_filial
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

    $sql3 = "SELECT os into temp temp_os from temp_oss where (select fn_qtos_meses_entre(data_fabricacao, data_abertura)) $cond $cond_produtos;";

    $qry = pg_query($sql1 . $sql2 . $sql3);

    if (empty($cond_produtos)) {
        $sql_total_geral = "SELECT sum(total) as total_geral from temp_total_os where meses $cond";
    } else {
        $sql_total_geral = "SELECT count(os) as total_geral from temp_os";
    }

    $qry_total_geral = pg_query($con, $sql_total_geral);
    $total_geral = pg_fetch_result($qry_total_geral, 0, 'total_geral');

	if ($total_geral > 0) {
		$qry_familia = pg_query($con, "SELECT descricao FROM tbl_familia WHERE familia = $familia");
		$familia_descricao = pg_fetch_result($qry_familia, 0, 'descricao');

		$sql_os_com_peca = "
			SELECT count(distinct(temp_os.os)) AS total_os
			FROM temp_os
			JOIN tbl_os_produto ON temp_os.os = tbl_os_produto.os
			JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
			JOIN tbl_peca ON tbl_os_item.peca = tbl_peca.peca
            ";
		$query_os_com_peca = pg_query($con, $sql_os_com_peca);

		$total_os          = $total_geral;
		$total_os_com_peca = pg_fetch_result($query_os_com_peca, 0, 'total_os');
		$total_os_sem_peca = $total_os - $total_os_com_peca;

		$sql = "
			select tbl_peca.referencia,
			tbl_peca.descricao,
			tbl_peca.peca,
			count(tbl_os_item.qtde) as total
			FROM temp_os
			JOIN tbl_os_produto ON temp_os.os = tbl_os_produto.os
			JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
			JOIN tbl_peca ON tbl_os_item.peca = tbl_peca.peca
			group by tbl_peca.referencia, tbl_peca.descricao, tbl_peca.peca order by total desc limit $limit
			";
		$query = pg_query($con, $sql);

		$percentual = 0;

		$categorias = array();
		$totais = array();
		$percentuais = array();
		
		if (pg_num_rows($query) == 0) {
            $paramsProd = '';
            if (!empty($produtos)) {
                $paramsProd = '&pd=' . implode('|', $produtos);
			}
			if (!empty($_GET['matriz_filial'])) {
		    	$paramMatriz = '&matriz_filial=' . $_GET['matriz_filial'];
	        }

            header("Location: relatorio_extratificacao_pareto_sem_peca_vdnf.php?ab=$abertura_request&nf=$data_fabricacao_request&fm=$meses&f=$familia&po=$posto{$paramsProd}{$paramMatriz}");
            exit;
		}

		if (pg_num_rows($query) > 0) {
			$conteudo = '<table class="tabela" cellspacing="1" align="center" style="width: 500px;">';
			$conteudo.= '<tr class="titulo_tabela">';
			$conteudo.= '<th>Referência</th>';
			$conteudo.= '<th>Descrição</th>';
			$conteudo.= '<th>Total</th>';

			while ($fetch = pg_fetch_assoc($query)) {
				$referencia = $fetch['referencia'];
				$descricao = $fetch['descricao'];
				$peca = $fetch['peca'];
				$total = (int) $fetch['total'];

				$percentual_uniq = ($total / $total_geral) * 100;
				$percentual+= $percentual_uniq;

				$cat = utf8_encode($referencia . ' ' . $descricao);

				$categorias[] = $cat;
				$totais[] = $total;
				$percentuais[] = round($percentual, 2);

				$conteudo.= '<tr>';
				$conteudo.= '<td>' . $referencia . '</td>';
				$conteudo.= '<td>' . $descricao . '</td>';
				$conteudo.= '<td style="cursor: pointer" onClick="detalhe(\'' . $abertura_request . '\', \'' . $data_fabricacao_request . '\', \'' . $meses . '\', \'' . $peca . '\', \'' . $familia . '\', \''.$matriz_filial.'\', \'' . implode('|', $produtos) . '\', \'' . $posto . '\', \'' . $desconsidera_conversor . '\')">' . $total . '</td>';
				$conteudo.= '</tr>';
			}
			$conteudo.= '</table><br/>';

			$conteudo.= '<table class="tabela" cellspacing="1" align="center" style="width: 500px;">';
			$conteudo.= '<tr class="titulo_tabela">';
			$conteudo.= '<th colspan="2">Totais de OS\'s</th>';
			$conteudo.= '</tr>';
			$conteudo.= '<tr>';
			$conteudo.= '<td align="right">Com peça</td><td style="cursor: pointer" onClick="detalhe(\'' . $abertura_request . '\', \'' . $data_fabricacao_request . '\', \'' . $meses . '\', \'\', \'' . $familia . '\', \''.$matriz_filial.'\', \'' . implode('|', $produtos) . '\', \'' . $posto . '\', \'' . $desconsidera_conversor . '\')">' . $total_os_com_peca . '</td>';
			$conteudo.= '</tr>';
			$conteudo.= '<tr>';
			$conteudo.= '<td align="right">Sem peça</td><td style="cursor: pointer" onClick="sem_peca(\'' . $abertura_request . '\', \'' . $data_fabricacao_request . '\', \'' . $meses . '\', \'' . $familia . '\', \''.$matriz_filial.'\', \'' . implode('|', $produtos) . '\', \'' . $posto . '\')">' . $total_os_sem_peca . '</td>';
			$conteudo.= '</tr>';
			$conteudo.= '</table><br/>';

			$conteudo.= '<div id="container" style="min-width: 400px; height: 400px; margin: 0 auto"></div>';

			$conteudo.= '<div style="margin-top: 40px;" align="center">';
			$conteudo.= '<form method="post" action="">';
			$conteudo.= '<input type="hidden" name="abertura" value="' . $abertura_request . '" />';
			$conteudo.= '<input type="hidden" name="data_fabricacao" value="' . $data_fabricacao_request . '" />';
			$conteudo.= '<input type="hidden" name="meses" value="' . $meses . '" />';
			$conteudo.= '<input type="hidden" name="familia" value="' . $familia . '" />';
			$conteudo.= '<input type="hidden" name="posto" value="' . $posto . '" />';
			$conteudo.= '<input type="hidden" name="produtos" value="' . implode('|', $produtos) . '" />';
			$conteudo.= 'Qtde peças: <input type="input" name="limit" value="' . $limit . '" style="width: 30px;" />';
			$conteudo.= '<span style="padding-left: 10px;"><input type="submit" value="Enviar" /></span>';
			$conteudo.= '</form>';
			$conteudo.= '</div>';
		}
	} else {
		//$conteudo = 'Dados não encontrados!';
        $paramsProd = '';
        if (!empty($produtos)) {
            $paramsProd = '&pd=' . implode('|', $produtos);
        }
        header("Location: relatorio_extratificacao_pareto_sem_peca.php?ab=$abertura_request&nf=$data_fabricacao_request&fm=$meses&f=$familia&po=$posto{$paramsProd}");
        exit;
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
            function detalhe(abertura, data_fabricacao, meses, peca, familia, matriz_filial, produtos, posto, dcg) {
                if(!matriz_filial){matriz_filial = ''};
                if (!peca) { peca = ''};
                if (!familia) { familia = '' };
                if (!produtos) { produtos = '' };
                if (!posto) { posto = '' };
                if (!dcg) { dcg = '' };

                <?php if ($login_fabrica == 24) { ?>
                			var url = "relatorio_extratificacao_grafico_defeitos.php?ab=" + abertura + "&nf=" + data_fabricacao + "&fm=" + meses + "&p=" + peca + "&f=" + familia +"&matriz_filial="+ matriz_filial+ "&pd=" + produtos + "&po=" + posto + "&dcg=" + dcg;
                <?php } else { ?>
                			var url = "relatorio_extratificacao_pareto_detalhe_vdnf.php?ab=" + abertura + "&nf=" + data_fabricacao + "&fm=" + meses + "&p=" + peca + "&f=" + familia +"&matriz_filial="+ matriz_filial+ "&pd=" + produtos + "&po=" + posto + "&dcg=" + dcg;
                <?php } ?>
                
                window.open (url, "detalhe", "height=640,width=1040,scrollbars=1");
            }

            function sem_peca(abertura, data_fabricacao, meses, familia, matriz_filial, produtos, posto) {
                if (!produtos) { produtos = '' };
                if (!posto) { posto = '' };

                var url = "relatorio_extratificacao_pareto_sem_peca_vdnf.php?ab=" + abertura + "&nf=" + data_fabricacao + "&fm=" + meses + "&f=" + familia + "&pd=" + produtos + "&po=" + posto+ "&matriz_filial="+matriz_filial;
                window.open (url, "detalhe", "height=640,width=1040,scrollbars=1");
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
								text: 'Peças com maiores números de quebra'
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

