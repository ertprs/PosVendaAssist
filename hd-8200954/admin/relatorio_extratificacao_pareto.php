<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$pecas = array();
$produtos = array();
$posto = 0;
$regiao = '';

if (!empty($_GET['ab'])) {
	$abertura = $_GET['ab'];
}

if (!empty($_GET['fb'])) {
	$fabricacao = $_GET['fb'];
}

if (!empty($_GET['fa'])) {
	$familia = $_GET['fa'];
	if($familia == 'global') {
		$sql = "SELECT  array_to_string(array_agg(familia), ',') as familia from tbl_familia where fabrica = $login_fabrica and ativo";
		$res = pg_query($con,$sql);
		$familia = pg_fetch_result($res,0,'familia');
		
	}
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

if (!empty($_GET['rv'])) {
    $revendas = explode('|', $_GET['rv']);
}

if (!empty($_GET['po'])) {
    $posto = $_GET['po'];
}

if (!empty($_GET['regiao'])) {
    $regiao = (int) $_GET['regiao'];
}

if (!empty($_POST['abertura'])) {
	$abertura = $_POST['abertura'];
}

if (!empty($_POST['fabricacao'])) {
	$fabricacao = $_POST['fabricacao'];
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

if (!empty($_POST['regiao'])) {
    $regiao = (int) $_POST['regiao'];
}

if (!empty($_POST['limit'])) {
	$limit = $_POST['limit'];
} else {
	$limit = 5;
}


if (empty($abertura) or empty($fabricacao)) {
	$conteudo = "Dados não encontrados!";
}

if (empty($meses)) {
	$meses = 1;
}

if ($login_fabrica == '50') {
    $suffix = $login_fabrica . '_' . $familia;
}

if (empty($conteudo)){
	if (!empty($pecas)) {
        $paramsProd = '';
        if (!empty($produtos)) {
            $paramsProd = '&pd=' . implode('|', $produtos);
        }
        if (!empty($revendas)) {
            $paramsRev = '&rv=' . implode('|', $revendas);
        }
		echo '<meta http-equiv="Refresh" content="0; url=relatorio_extratificacao_pareto_detalhe.php?ab=' . $abertura . '&fb=' . $fabricacao . '&fm=' . $meses . '&p=' . implode('|', $pecas) . '&f=' . $familia . '&fo=' . $fo . '&d=' . $d . '&po=' . $po . '&regiao=' . $regiao . $paramsProd . $paramsRev. '" />';
		exit;
	}

	$abertura_request = $abertura;
	$fabricacao_request = $fabricacao;

	$abertura = $abertura_request . '-01';
	$fabricacao = $fabricacao_request . '-01';

	$arr_abertura = explode('-', $abertura_request);
    $arr_fabricacao = explode('-', $fabricacao_request);

    $cond_produtos = '';
    $join_posto = '';
    $cond_posto = '';
    $cond_revenda = '';

    if (!empty($produtos)) {
        if ($login_fabrica == '50') {
            $tbl_numero_serie = '';
        } else {
            $tbl_numero_serie = 'tbl_numero_serie.';
        }

        $cond_produtos = ' AND tbl_os.produto IN (' . implode(',', $produtos) . ') ' ;
        $cond_produtos2 = ' AND ' . $tbl_numero_serie . 'produto IN (' . implode(',', $produtos) . ') ' ;
    }

    if (!empty($posto)) {
        $arr_posto = explode('|', $posto);
        $join_posto = ' JOIN tbl_os USING (os) ';
        $cond_posto = ' AND tbl_os.posto IN (' . implode(',', $arr_posto) . ') ';
    }

    if (!empty($revendas)) {
    	$cond_revendas = " AND colormaq_os_serie.cnpj IN('".implode("','", $revendas)."')";
    }else{
    	$cond_revendas = "";
    }


    if ($login_fabrica == '50') {
        $tbln1 = 'colormaq_numero_serie1';
        $tbln2 = 'colormaq_numero_serie2';
        $tbl_produto = '';
        $join = '';
        $conds = "WHERE familia  in ($familia) "  . $cond_produtos2;
    } else {
        $tbln1 = 'tbl_numero_serie';
        $tbln2 = 'tbl_numero_serie';
        $tbl_produto = 'tbl_produto.';
        $join = 'JOIN tbl_produto ON tbl_numero_serie.produto = tbl_produto.produto';
        $conds = "WHERE tbl_numero_serie.fabrica = $login_fabrica AND tbl_produto.familia = $familia $cond_produtos2";
    }

	$sqlN = "SELECT  serie, {$tbl_produto}produto,data_fabricacao, {$login_fabrica} as fabrica
			INTO TEMP n1
			FROM $tbln1 $join $conds
			AND {$tbln1}.data_fabricacao between '$fabricacao' and (('$fabricacao'::date + interval '1 month') - interval '1 day')::date;

			SELECT serie, {$tbl_produto}produto, data_fabricacao, {$login_fabrica} as fabrica
			INTO TEMP n2
			FROM $tbln2 $join $conds
			AND {$tbln2}.data_fabricacao between '$fabricacao' and (('$fabricacao'::date + interval '1 month') - interval '1 day')::date;

			SELECT serie, produto, data_fabricacao,fabrica
			INTO TEMP temp_numero_serie
			FROM (select * from n1 union select * from n2) x;
            
            CREATE INDEX idx_temp_nss ON temp_numero_serie(serie);
            CREATE INDEX idx_temp_nsp ON temp_numero_serie(produto);
			"; //echo "$sqlN<br/><br/>";
	$resN = pg_query($con, $sqlN);
	//echo nl2br($sqlN);
	
	if ($login_fabrica == '50') {
        $total_os_sql = "SELECT colormaq_os_serie.produto,
                            colormaq_os_serie.serie,
                            colormaq_os_serie.os,
                            colormaq_os_serie.posto,
                            colormaq_os_serie.cnpj
                        INTO TEMP temp_os
                        FROM colormaq_os_serie
                        $join_posto
                        WHERE colormaq_os_serie.data_abertura between '$abertura' and (('$abertura'::date + interval '$meses month') - interval '1 day')::date
                        AND   colormaq_os_serie.familia in( $familia)
                        $cond_posto
                        $cond_revendas
        ";
	} else {
        $total_os_sql = "SELECT tbl_os.produto, tbl_os.serie, tbl_os.os, 0 as posto
                        INTO TEMP temp_os
                        FROM tbl_os
                        JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
                        WHERE tbl_os.fabrica = $login_fabrica
                        AND tbl_produto.familia = $familia
                        AND tbl_os.data_abertura between '$abertura' and (('$abertura'::date + interval '$meses month') - interval '1 day')::date
                        $cond_posto";
    }

    $join_tbl_posto = '';
    $cond_posto_estado = '';

    if (!empty($regiao)) {
        $qry_estados = pg_query($con, "SELECT estados_regiao FROM tbl_regiao WHERE regiao = $regiao");
        //echo nl2br("SELECT estados_regiao FROM tbl_regiao WHERE regiao = $regiao");
        $estados_regiao = pg_fetch_result($qry_estados, 0, 'estados_regiao');

        if (!empty($estados_regiao)) {
            $tmp = explode(',', $estados_regiao);
            $quoted = array();

            foreach ($tmp as $t) {
                $quoted[] = "'" . trim($t) . "'";
            }

            $estados_regiao = implode(', ', $quoted);

            $join_tbl_posto = " JOIN tbl_posto USING(posto) ";
            $cond_posto_estado = " AND tbl_posto.estado IN ($estados_regiao) ";
        }
    }

	$sql_total = "
            $total_os_sql ;
            
            CREATE INDEX idx_temp_oss ON temp_os(serie);
            CREATE INDEX idx_temp_osp ON temp_os(produto);
            CREATE INDEX idx_temp_osos ON temp_os(os);
            CREATE INDEX idx_temp_ospo ON temp_os(posto);
            
            SELECT count(os) as total FROM temp_os $join_tbl_posto
            JOIN temp_numero_serie ON temp_os.serie = temp_numero_serie.serie
            AND temp_numero_serie.produto = temp_os.produto $cond_posto_estado ;
	    "; //die(nl2br($sql_total));
	$qry_total = pg_query($con, $sql_total);
	//echo nl2br($sql_total);
	$total_geral = pg_fetch_result($qry_total, 0, 'total');

	if ($total_geral > 0) {
		$qry_familia = pg_query($con, "SELECT descricao FROM tbl_familia WHERE familia IN ($familia)");
		//echo nl2br("SELECT descricao FROM tbl_familia WHERE familia = $familia");
		$familia_descricao = pg_fetch_result($qry_familia, 0, 'descricao');

        $cond_posto_estado = str_replace('AND', 'WHERE', $cond_posto_estado);

		$sql_os_com_peca = "
			SELECT count(distinct(temp_os.os)) AS total_os
			FROM temp_os $join_tbl_posto
			JOIN temp_numero_serie ON temp_os.serie = temp_numero_serie.serie
              AND temp_numero_serie.produto = temp_os.produto
			JOIN tbl_os_produto ON temp_os.os = tbl_os_produto.os
			JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
			JOIN tbl_peca ON tbl_os_item.peca = tbl_peca.peca $cond_posto_estado
            ";
		//$query_os_total    = pg_query($con, $sql_os_total);
		$query_os_com_peca = pg_query($con, $sql_os_com_peca);
		//echo nl2br($sql_os_com_peca);

		$total_os          = $total_geral;
		$total_os_com_peca = pg_fetch_result($query_os_com_peca, 0, 'total_os');
		$total_os_sem_peca = $total_os - $total_os_com_peca;

		$sql = "
			select tbl_peca.referencia,
			tbl_peca.descricao,
			tbl_peca.peca,
			count(tbl_os_item.qtde) as total
			FROM temp_os $join_tbl_posto
			JOIN temp_numero_serie ON temp_os.serie = temp_numero_serie.serie
              AND temp_numero_serie.produto = temp_os.produto
			JOIN tbl_os_produto ON temp_os.os = tbl_os_produto.os
			JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
			JOIN tbl_peca ON tbl_os_item.peca = tbl_peca.peca
            $cond_posto_estado
			group by tbl_peca.referencia, tbl_peca.descricao, tbl_peca.peca order by total desc limit $limit
			";
		$query = pg_query($con, $sql);

		//echo nl2br($sql);

		$percentual = 0;

		$categorias = array();
		$totais = array();
		$percentuais = array();
		
		if (pg_num_rows($query) == 0) {
			$paramsProd = '';
			
			if (!empty($produtos)) {
				$paramsProd = '&pd=' . implode('|', $produtos);
			}
			
			header("Location: relatorio_extratificacao_pareto_sem_peca.php?ab=$abertura_request&fb=$fabricacao_request&fm=$meses&f=$familia&po=$posto{$paramsProd}&regiao=$regiao");

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
				$conteudo.= '<td style="cursor: pointer" onClick="detalhe(\'' . $abertura_request . '\', \'' . $fabricacao_request . '\', \'' . $meses . '\', \'' . $peca . '\', \'' . $familia . '\',\'' . implode('|', $revendas) . '\', \'' . implode('|', $produtos) . '\', \'' . $posto . '\')">' . $total . '</td>';
				$conteudo.= '</tr>';
			}
			$conteudo.= '</table><br/>';

			$conteudo.= '<table class="tabela" cellspacing="1" align="center" style="width: 500px;">';
			$conteudo.= '<tr class="titulo_tabela">';
			$conteudo.= '<th colspan="2">Totais de OS\'s</th>';
			$conteudo.= '</tr>';
			$conteudo.= '<tr>';
			$conteudo.= '<td align="right">Com peça</td><td style="cursor: pointer" onClick="detalhe(\'' . $abertura_request . '\', \'' . $fabricacao_request . '\', \'' . $meses . '\', \'\', \'' . $familia . '\',\'' . implode('|', $revendas) . '\', \'' . implode('|', $produtos) . '\', \'' . $posto . '\')">' . $total_os_com_peca . '</td>';
			$conteudo.= '</tr>';
			$conteudo.= '<tr>';
			$conteudo.= '<td align="right">Sem peça</td><td style="cursor: pointer" onClick="sem_peca(\'' . $abertura_request . '\', \'' . $fabricacao_request . '\', \'' . $meses . '\', \'' . $familia . '\',\'' . implode('|', $revendas) . '\', \'' . implode('|', $produtos) . '\', \'' . $posto . '\')">' . $total_os_sem_peca . '</td>';
			$conteudo.= '</tr>';
			$conteudo.= '</table><br/>';

			$conteudo.= '<div id="container" style="min-width: 400px; height: 400px; margin: 0 auto"></div>';

			$conteudo.= '<div style="margin-top: 40px;" align="center">';
			$conteudo.= '<form method="post" action="">';
			$conteudo.= '<input type="hidden" name="abertura" value="' . $abertura_request . '" />';
			$conteudo.= '<input type="hidden" name="fabricacao" value="' . $fabricacao_request . '" />';
			$conteudo.= '<input type="hidden" name="meses" value="' . $meses . '" />';
			$conteudo.= '<input type="hidden" name="familia" value="' . $familia . '" />';
			$conteudo.= '<input type="hidden" name="revenda" value="' . $revenda. '" />';
			$conteudo.= '<input type="hidden" name="posto" value="' . $posto . '" />';
			$conteudo.= '<input type="hidden" id="regiao" name="regiao" value="' . $regiao . '" />';
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

        $paramsRev = '';
        if (!empty($revendas)) {
            $paramsRev = '&rv=' . implode('|', $revendas);
        }

        echo "total vazio";exit;
        header("Location: relatorio_extratificacao_pareto_sem_peca.php?ab=$abertura_request&fb=$fabricacao_request&fm=$meses&f=$familia&po=$posto $paramsProd $paramsRev");
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
            function detalhe(abertura, fabricacao, meses, peca, familia,revendas, produtos, posto) {
                if (!peca) { peca = ''};
                if (!familia) { familia = '' };
                if (!produtos) { produtos = '' };
                if (!revendas) { revendas = '' };
                if (!posto) { posto = '' };

                var regiao = $("#regiao").val();

                var url = "relatorio_extratificacao_pareto_detalhe.php?ab=" + abertura + "&fb=" + fabricacao + "&fm=" + meses + "&p=" + peca + "&f=" + familia + "&pd=" + produtos + "&rv=" + revendas + "&po=" + posto + "&regiao=" + regiao;
                window.open (url, "detalhe", "height=640,width=1040,scrollbars=1");
            }

            function sem_peca(abertura, fabricacao, meses, familia,revendas, produtos, posto) {
                if (!produtos) { produtos = '' };
                if (!revendas) { revendas = '' };
                if (!posto) { posto = '' };

                var regiao = $("#regiao").val();

                var url = "relatorio_extratificacao_pareto_sem_peca.php?ab=" + abertura + "&fb=" + fabricacao + "&fm=" + meses + "&f=" + familia + "&rv="+revendas+"&pd=" + produtos + "&po=" + posto + "&regiao=" + regiao;
                window.open (url, "detalhe", "height=640,width=1040,scrollbars=1");
            }
		</script>
		<?php if (!empty($conteudo) and $conteudo <> "Dados não encontrados!"): ?>
			<script type="text/javascript" src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
			<script src="js/highcharts_4.1.5.js"></script>
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

