<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include "autentica_admin.php";

include 'relatorio_extratificacao_devolucao.class.php';

$relatorio = new relatorioExtratificacaoDevolucao;

$cond_peca = '';
$cond_familia = '';
$joinOSSerie = '';
$produtos = array();
$posto = 0;

if($login_fabrica == 24){

    if (!empty($_GET['matriz_filial'])) {
        $matriz_filial = $_GET['matriz_filial'];
    }
   
    if($matriz_filial == '02'){
        $cond_matriz_filial = " AND suggar_os_devolucao_serie.matriz IS TRUE ";
    }else{
        $cond_matriz_filial = " AND suggar_os_devolucao_serie.matriz IS FALSE ";
    }
}


if (!empty($_GET['ab'])) {
	$abertura = $_GET['ab'];
}

if (!empty($_GET['nf'])) {
	$data_fabricacao = $_GET['nf'];
}

if (!empty($_GET['p'])) {
	$peca = $_GET['p'];

	$pecas = explode('|', $peca);

	if (count($pecas) == 1) {
		$peca = (int) $pecas[0];
		$cond_peca = " AND tbl_peca.peca = $peca ";
	} else {
		$cond_peca = ' AND tbl_os_laudo_peca.peca IN (' . implode(', ', $pecas) . ') ';
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

    if ($login_fabrica != 120 and $login_fabrica != 201) {
    	$descDefeito = " ,tbl_defeito.descricao as desc_defeito ";
    	$joinDefeito = " LEFT JOIN tbl_defeito ON tbl_os_laudo_peca.defeito = tbl_defeito.defeito ";
    }

    $cond_posto = '';

    if (!empty($posto)) {
        $arr_posto = explode('|', $posto);
        $cond_posto = ' AND tbl_os.posto IN (' . implode(',', $arr_posto) . ') ';
    }

    if ($login_fabrica == '24') {
        $tbl_ns = 'suggar_devolucao_numero_serie';
        $tbl_from = "suggar_os_devolucao_serie";
        $tbl_produto = $tbl_from . '.';
        $getos = ', os';
        $join = " JOIN suggar_os_devolucao_serie USING(serie, produto) ";
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
        data_recebimento into temp temp_oss 
        from $tbl_from
        join $tbl_ns
        USING(serie,produto) 
        where data_fabricacao 
        between '$data_fabricacao' and (('$data_fabricacao'::date + interval '1 month') - interval '1 day')::date 
        and data_nf >= data_fabricacao 
        $cond_conversor
        $cond_matriz_filial
        AND suggar_os_devolucao_serie.familia = $familia
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
        $cond = '<> 0';
    } else {
        $cond = '= '. $qtos_meses;
    }

    $sql3 = "SELECT os_laudo, data_fabricacao,serie, produto into temp temp_numero_serie from temp_oss where (select  case fn_qtos_meses_entre(data_nf, data_recebimento) when 0 THEN 1 else fn_qtos_meses_entre(data_nf, data_recebimento) end as meses ) $cond $cond_produtos;";
    $sql3 .= "CREATE INDEX idx_temp_nsos ON temp_numero_serie(os_laudo)";

    $qry = pg_query($sql3);

	$sql = "
        select
                DISTINCT tbl_peca.referencia,
                tbl_os_laudo.os_laudo, 

                tbl_os_laudo.serie,
                tbl_os_laudo.produto,
                tbl_servico_realizado.descricao as desc_serv_real, 
                tbl_os_laudo_peca.defeito, 
                
                
                to_char(temp_numero_serie.data_fabricacao, 'DD/MM/YYYY') as data_fabricacao,
                tbl_defeito_constatado.descricao as defeito_constatado, 
                tbl_os_laudo_peca.os_laudo_peca,
                tbl_os_laudo_peca.peca
                $descDefeito,
                
                tbl_peca.descricao
                into temp temp_os
            FROM tbl_os_laudo
            JOIN temp_numero_serie USING(os_laudo)
            
            JOIN tbl_os_laudo_peca on tbl_os_laudo_peca.os_laudo = tbl_os_laudo.os_laudo            

            left JOIN tbl_servico_realizado ON tbl_os_laudo_peca.servico_realizado = tbl_servico_realizado.servico_realizado 
            JOIN tbl_produto ON tbl_os_laudo.produto = tbl_produto.produto 
            JOIN tbl_peca ON tbl_os_laudo_peca.peca = tbl_peca.peca
            
            $joinDefeito

            LEFT JOIN tbl_defeito_constatado ON tbl_os_laudo.defeito_constatado = tbl_defeito_constatado.defeito_constatado 
            
            WHERE 1=1 $cond_peca $cond_datas $cond_posto
            ORDER BY tbl_peca.referencia, tbl_peca.descricao, data_fabricacao; 
     
            SELECT * FROM temp_os;

		";

		//LEFT JOIN tbl_defeito_reclamado ON tbl_os_laudo.defeito_reclamado = tbl_defeito_reclamado.defeito_reclamado
		//JOIN tbl_posto_fabrica ON tbl_posto_fabrica.fabrica = tbl_os_laudo.fabrica AND  tbl_posto_fabrica.posto = tbl_os_laudo.posto
        //JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto

	$query = pg_query($con, $sql);

	if (pg_num_rows($query) > 0) {
		$conteudo = '<table class="tabela" cellspacing="1" align="center">';
		$conteudo.= '<tr class="titulo_tabela">';
		$conteudo.= '<th>Laudo OS</th>';
		//$conteudo.= '<th>Tipo de OS</th>';
		//$conteudo.= '<th style="width: 260px;">Defeito Reclamado</th>';
		//$conteudo.= '<th>Defeito Constatado</th>';
		$conteudo.= '<th>Referência</th>';
		$conteudo.= '<th>Descrição</th>';
		$conteudo.= '<th>Número Série</th>';
		$conteudo.= '<th>Fabricação</th>';
		
		$conteudo.= '<th>Defeito</th>';
		
		$conteudo.= '<th>Serviço Realizado</th>';
		//print_r(pg_fetch_assoc($query));

		while ($fetch = pg_fetch_assoc($query)) {
			$referencia = $fetch['referencia'];
			$descricao = $fetch['descricao'];
			$laudo_os = $fetch['os_laudo'];
			$defeito_reclamado = $fetch['defeito_reclamado_descricao'];
			$defeito_constatado = $fetch['defeito_constatado'];           
            
			if (empty($defeito_constatado)) {
				$defeito_constatado = '&nbsp;';
			}
			$sua_os = $fetch['sua_os'];

			if ($login_fabrica != 120 and $login_fabrica != 201) {
				$defeito = $fetch['desc_defeito'];
			}
			$servico_realizado = $fetch['desc_serv_real'];
			$serie = $fetch['serie'];
			$data_fabricacao = $fetch['data_fabricacao'];

 			$conteudo.= '<tr>';
			$conteudo.= '<td><a href="informacao_devolucao.php?os_laudo=' . $laudo_os . '" target="_blank">' . $laudo_os . '</a></td>';
			//$conteudo.= '<td>' . $consumidor_revenda . '</td>';
			//$conteudo.= '<td>' . $defeito_reclamado . '</td>';
			//$conteudo.= '<td>' . $defeito_constatado . '</td>';
			$conteudo.= '<td>' . $referencia . '</td>';
			$conteudo.= '<td>' . $descricao . '</td>';
			$conteudo.= '<td>' . $serie . '</td>';
			$conteudo.= '<td>' . $data_fabricacao . '</td>';
			if ($login_fabrica != 120 and $login_fabrica != 201) {
				$conteudo.= '<td>' . $defeito . '</td>';
			}
			$conteudo.= '<td>' . $servico_realizado . '</td>';
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

		$sql_servico = "select desc_serv_real as descricao, count(temp_os.os_laudo_peca) as servicos
			FROM temp_os
			JOIN temp_numero_serie ON temp_os.serie = temp_numero_serie.serie AND temp_numero_serie.produto = temp_os.produto
			JOIN tbl_os_laudo_peca ON temp_os.os_laudo_peca = tbl_os_laudo_peca.os_laudo_peca
			JOIN tbl_peca ON tbl_os_laudo_peca.peca = tbl_peca.peca 
            $joinNSFornecedor
            WHERE 1=1 $cond_peca
			group by desc_serv_real order by servicos desc
			";

		$sql_defeito = "
			select desc_defeito as descricao, count(distinct temp_os.os_laudo_peca) as defeitos
			FROM temp_os
            JOIN temp_numero_serie ON temp_os.serie = temp_numero_serie.serie AND temp_numero_serie.produto = temp_os.produto
            JOIN tbl_os_laudo_peca ON temp_os.os_laudo_peca = tbl_os_laudo_peca.os_laudo_peca
			JOIN tbl_peca ON tbl_os_laudo_peca.peca = tbl_peca.peca 
            $joinNSFornecedor
            WHERE 1=1 $cond_peca
			group by desc_defeito order by count(distinct temp_os.os_laudo_peca) desc";

		$qry_defeito = pg_query($con, $sql_defeito);

		if (pg_num_rows($qry_defeito) > 0) {
			while ($fetch_defeito = pg_fetch_assoc($qry_defeito)) {
				$categorias_defeito[] = utf8_encode($fetch_defeito['descricao']);
				$totais_defeito[] = (int) $fetch_defeito['defeitos'];
			}
		}

		$qry_servico = pg_query($con, $sql_servico);

		if (pg_num_rows($qry_servico) > 0) {
			while ($fetch_servico = pg_fetch_assoc($qry_servico)) {
				$categorias_servico[] = utf8_encode($fetch_servico['descricao']);
				$totais_servico[] = (int) $fetch_servico['servicos'];
			}
		}

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
            function download(link) {
                window.location=link;
            }
        </script>

		<?php if (!empty($conteudo) and $conteudo <> "Dados não encontrados!"): ?>
			<?php $conteudo.= '<div style="float: left; width: 900px; margin-left: 80px;">' ?>
			<script type="text/javascript" src="../js/jquery-1.4.2.js"></script>
			<script src="js/highcharts.js"></script>
			<script src="js/exporting.js"></script>

			<?php if (!empty($categorias_defeito) and !empty($totais_defeito) and !empty($pecas)): ?>
				<?php
				$percentual = 0;
				$percentuais = array();
				$total_defeito = array_sum($totais_defeito);

				foreach ($totais_defeito as $valor) {
					$percentual_uniq = ($valor / $total_defeito) * 100;
					$percentual+= $percentual_uniq;
					$percentuais[] = round($percentual, 2);
				}

				$conteudo.= '<div id="defeitos" style="width: 400px; height: 400px; margin: 0 auto; float: left;"></div>';
				?>
				<script>
					$(function () {
						var chart;
						$(document).ready(function() {
							chart = new Highcharts.Chart({
								chart: {
									renderTo: 'defeitos',
									zoomType: 'xy'
								},
								title: {
									text: 'Defeitos'
								},
								<?php if (count($pecas) == 1): ?>
								subtitle: {
									text: '<?php echo $descricao ?>'
								},
								<?php endif ?>
								credits: {
									enabled: false
								},
								xAxis: [{
									categories: <?php echo json_encode($categorias_defeito) ?>
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
											data: <?php echo json_encode($totais_defeito) ?>

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

			<?php  
			if (!empty($categorias_servico) and !empty($totais_servico) and !empty($pecas)): ?>
				<?php
				$percentual = 0;
				$percentuais = array();
				$total_servico = array_sum($totais_servico);

				foreach ($totais_servico as $valor) {
					$percentual_uniq = ($valor / $total_servico) * 100;
					$percentual+= $percentual_uniq;
					$percentuais[] = round($percentual, 2);
				}

				$conteudo.= '<div id="servicos" style="width: 400px; height: 400px; margin-left: 20px; float: left;"></div>';
				?>
				<script>
					$(function () {
						var chart;
						$(document).ready(function() {
							chart = new Highcharts.Chart({
								chart: {
									renderTo: 'servicos',
									zoomType: 'xy'
								},
								title: {
									text: 'Serviço Realizado'
								},
								<?php if (count($pecas) == 1): ?>
								subtitle: {
									text: '<?php echo $descricao ?>'
								},
								<?php endif ?>
								credits: {
									enabled: false
								},
								xAxis: [{
									categories: <?php echo json_encode($categorias_servico) ?>
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
											data: <?php echo json_encode($totais_servico) ?>

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
			<?php $conteudo.= '</div>' ?>
		<?php endif; ?>
	</head>
	<body>
		<div class="conteudo">
			<?php echo $conteudo; ?>
		</div>
	</body>
</html>

