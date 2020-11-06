<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include "autentica_admin.php";

include 'relatorio_extratificacao.class.php';

$relatorio = new relatorioExtratificacao;

$cond_peca = '';
$cond_familia = '';
$joinOSSerie = '';
$produtos = array();
$posto = 0;
$regiao = '';

if (!empty($_GET['ab'])) {
	$abertura = $_GET['ab'];
}

if (!empty($_GET['fb'])) {
	$fabricacao = $_GET['fb'];
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
    $cond_familia = " AND tbl_produto.familia in ( $familia )";

    if ($login_fabrica == '50') {
        if ($familia == 'global') {
            $cond_familia = '';
        }
        $joinOSSerie = " JOIN colormaq_os_serie USING(os) ";
    }
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

if (empty($abertura) or empty($fabricacao)) {
	$conteudo = "Dados não encontrados!";
}

if (!empty($_GET['fm'])) {
	$meses = $_GET['fm'];
} else {
	$meses = 1;
}

if ($login_fabrica == '50') {
    $suffix = $login_fabrica . '_' . $familia;
}

$categorias_servico = array();
$totais_servico = array();
$categorias_defeito = array();
$totais_defeito = array();

if (empty($conteudo)) {
	$abertura   = $abertura . '-01';
	$fabricacao = $fabricacao . '-01';

    $condFornecedores = $relatorio->montaCondFornecedores();
    $condDatas = $relatorio->montaCondDatas();


    if (!empty($condFornecedores) or !empty($condDatas)) {
        if (count($pecas) == 1) {
            $peca = (int) $pecas[0];
            $condpeca = " AND peca = $peca ";
        } else {
            $condpeca = ' AND peca IN (' . implode(', ', $pecas) . ') ';
        }

        $sqlns =  "SELECT serie, peca
                            INTO TEMP temp_fornecedor_serie1
                    FROM tbl_ns_fornecedor
                    JOIN tbl_numero_serie USING(numero_serie)
                    WHERE  tbl_ns_fornecedor.fabrica = $login_fabrica $condFornecedores $condDatas $condpeca;

                    SELECT substr(serie,1,length(serie) -1) as serie, peca
                    INTO TEMP temp_fornecedor_serie2
                    FROM tbl_ns_fornecedor
                    JOIN tbl_numero_serie USING(numero_serie)
                    WHERE  tbl_ns_fornecedor.fabrica = $login_fabrica $condFornecedores $condDatas $condpeca;

                    SELECT serie, peca INTO TEMP temp_fornecedor_serie FROM (select * from temp_fornecedor_serie1 UNION select * from temp_fornecedor_serie2) x

                    "; //echo $sqlns , '<br/><Br/>';
        $resns = pg_query($con,$sqlns);
        //echo nl2br($sqlns);

        $joinNSFornecedor = ' JOIN temp_fornecedor_serie ON temp_fornecedor_serie.serie = temp_numero_serie.serie 
AND tbl_peca.peca = temp_fornecedor_serie.peca ';
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
        if ($login_fabrica == '50') {
            $tbl_numero_serie = '';
        } else {
            $tbl_numero_serie = 'tbl_numero_serie.';
        }

        $cond_produtos = ' AND ' . $tbl_numero_serie . 'produto IN (' . implode(',', $produtos) . ') ' ;
    }

    if ($login_fabrica != 120) {
    	$descDefeito = " ,tbl_defeito.descricao as desc_defeito ";
    	$joinDefeito = " JOIN tbl_defeito ON tbl_os_item.defeito = tbl_defeito.defeito ";
    }

    $cond_posto = '';

    if (!empty($posto)) {
        $arr_posto = explode('|', $posto);
        $cond_posto = ' AND tbl_os.posto IN (' . implode(',', $arr_posto) . ') ';
    }

    if ($login_fabrica == '50') {
        $tbln1 = 'colormaq_numero_serie1';
        $tbln2 = 'colormaq_numero_serie2';
        $tbl_produto = '';
        $join = '';
        $conds = "WHERE familia in ($familia) " . $cond_produtos;
        if(!empty($revendas)){
            $cond_revendas = " AND colormaq_os_serie.cnpj IN('".implode("','", $revendas)."')";
        }
    } else {
        $tbln1 = 'tbl_numero_serie';
        $tbln2 = 'tbl_numero_serie';
        $tbl_produto = 'tbl_produto.';
        $join = 'JOIN tbl_produto ON tbl_numero_serie.produto = tbl_produto.produto';
        $conds = "WHERE tbl_numero_serie.fabrica = $login_fabrica $cond_familia $cond_produtos";
    }

    $sqlN = "SELECT  serie, {$tbl_produto}produto, data_fabricacao, $login_fabrica as fabrica
			INTO TEMP n1
			FROM $tbln1 $join $conds
			AND {$tbln1}.data_fabricacao between '$fabricacao' and (('$fabricacao'::date + interval '1 month') - interval '1 day')::date;

			SELECT serie, {$tbl_produto}produto, data_fabricacao, $login_fabrica as fabrica
			INTO TEMP n2
			FROM $tbln2 $join $conds
			AND {$tbln2}.data_fabricacao between '$fabricacao' and (('$fabricacao'::date + interval '1 month') - interval '1 day')::date;

			SELECT serie, produto, data_fabricacao,fabrica
			into temp temp_numero_serie
			FROM (select * from n1 union select * from n2) x;

            CREATE INDEX idx_temp_nss ON temp_numero_serie(serie);
            CREATE INDEX idx_temp_nsp ON temp_numero_serie(produto);
		"; 
        //echo $sqlN , '<br/><br/>';
        $resN = pg_query($con, $sqlN);

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

	$sql = "

        select
                tbl_os.os,
                tbl_os.sua_os,
                tbl_os.serie,
				tbl_os.produto,
				tbl_os.consumidor_nome,
				tbl_os.consumidor_fone,
				tbl_os.consumidor_endereco,
				tbl_os.consumidor_cidade,
				tbl_os.consumidor_estado,
                tbl_produto.descricao AS produto_descricao,
                tbl_servico_realizado.descricao as desc_serv_real,
                tbl_os.defeito_reclamado AS defeito_descricao,
                tbl_os.defeito_reclamado_descricao,
                tbl_os.consumidor_revenda,
                tbl_defeito_constatado.descricao as defeito_constatado,
                tbl_os_item.os_item,
                tbl_os_item.peca
                $descDefeito,
                tbl_posto.nome as razao_social_posto,
                tbl_posto_fabrica.contato_cidade as posto_cidade,
                tbl_posto_fabrica.contato_estado as posto_estado
                into temp temp_os
            FROM tbl_os
            $joinOSSerie
            JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os
            JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
            JOIN tbl_posto_fabrica ON tbl_posto_fabrica.fabrica = tbl_os.fabrica AND
                                      tbl_posto_fabrica.posto = tbl_os.posto

            JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto

            JOIN tbl_servico_realizado ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
            $joinDefeito
            JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
            LEFT JOIN tbl_defeito_constatado ON tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado
            WHERE tbl_os.fabrica = $login_fabrica
            $cond_familia $cond_datas  $cond_posto $cond_posto_estado $cond_revendas
            AND tbl_os.data_abertura between '$abertura' and (('$abertura'::date + interval '$meses month') - interval '1 day')::date;

            CREATE INDEX idx_temp_oss ON temp_os(serie);
            CREATE INDEX idx_temp_osp ON temp_os(produto);
            CREATE INDEX idx_temp_ositem ON temp_os(os_item);
            CREATE INDEX idx_temp_osos ON temp_os(os);

            select
                    distinct tbl_peca.referencia,
                    tbl_peca.descricao,
                    tbl_peca.peca,
                    temp_numero_serie.serie,
                    to_char(temp_numero_serie.data_fabricacao, 'DD/MM/YYYY') as data_fabricacao,
                    temp_os.*
                FROM temp_os
                JOIN temp_numero_serie ON temp_os.serie = temp_numero_serie.serie AND temp_numero_serie.produto = temp_os.produto
                JOIN tbl_os_item ON temp_os.os_item = tbl_os_item.os_item
                JOIN tbl_peca ON tbl_os_item.peca = tbl_peca.peca
                $joinNSFornecedor
                WHERE 1=1 $cond_peca
                order by temp_os.os,tbl_peca.referencia, tbl_peca.descricao, data_fabricacao




		";
            //echo nl2br($sql);
	$query = pg_query($con, $sql);

	if (pg_num_rows($query) > 0) {
		$conteudo = '<table class="tabela" cellspacing="1" align="center">';
		$conteudo.= '<tr class="titulo_tabela">';
		$conteudo.= '<th>OS</th>';
		$conteudo.= '<th>Tipo de OS</th>';
        if($login_fabrica == 50){
            $conteudo.= '<th>Razão Social Posto</th>';
            $conteudo.= '<th>Posto Cidade</th>';
            $conteudo.= '<th>Posto Estado</th>';
            $conteudo.= '<th>Consumidor/Revenda</th>';
            $conteudo.= '<th>Telefone</th>';
            $conteudo.= '<th>Endereço Consumidor</th>';
            $conteudo.= '<th>Cidade Consumidor</th>';
            $conteudo.= '<th>UF Consumidor</th>';
        }
		$conteudo.= '<th style="width: 260px;">Defeito Reclamado</th>';
		$conteudo.= '<th>Defeito Constatado</th>';
		$conteudo.= '<th>Referência</th>';
        $conteudo.= '<th>Descrição</th>';
        if($login_fabrica == 50){
            $conteudo.= '<th>Produto</th>';
		}
		$conteudo.= '<th>Número Série</th>';
		$conteudo.= '<th>Data Fabricação</th>';
		if ($login_fabrica != 120) {
			$conteudo.= '<th>Defeito</th>';
		}
		$conteudo.= '<th>Serviço Realizado</th>';

		while ($fetch = pg_fetch_assoc($query)) {
			$referencia = $fetch['referencia'];
			$descricao = $fetch['descricao'];
			$os = $fetch['os'];
            $defeito_descricao = $fetch['defeito_descricao'];

            if(strlen($defeito_descricao) > 0){

                $sql_def = "SELECT descricao FROM tbl_defeito_reclamado WHERE defeito_reclamado = {$defeito_descricao}";
                $res_def = pg_query($con, $sql_def); 

                $defeito_reclamado = pg_fetch_result($res_def, 0, 'descricao');

            }else{
                $defeito_reclamado =  $fetch['defeito_reclamado_descricao'];
            }

			$defeito_constatado = $fetch['defeito_constatado'];
            if($login_fabrica == 50){
                $razao_social_posto = $fetch["razao_social_posto"];
                $posto_cidade = $fetch["posto_cidade"];
                $posto_estado = $fetch["posto_estado"];
                $consumidor_nome = $fetch["consumidor_nome"];
                $consumidor_fone = $fetch["consumidor_fone"];
                $consumidor_endereco = $fetch["consumidor_endereco"];
                $consumidor_cidade = $fetch["consumidor_cidade"];
                $consumidor_estado = $fetch["consumidor_estado"];
                $produto = $fetch["produto_descricao"];
            }

			if (empty($defeito_constatado)) {
				$defeito_constatado = '&nbsp;';
			}
			$sua_os = $fetch['sua_os'];

			if ($login_fabrica != 120) {
				$defeito = $fetch['desc_defeito'];
			}
			$servico_realizado = $fetch['desc_serv_real'];
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
                    $consumidor_revenda = '';
            }

			$conteudo.= '<tr>';
			$conteudo.= '<td><a href="os_press.php?os=' . $os . '" target="_blank">' . $sua_os . '</a></td>';
			$conteudo.= '<td>' . $consumidor_revenda . '</td>';

            if($login_fabrica == 50){
                $conteudo.= '<td>' . $razao_social_posto . '</td>';
                $conteudo.= '<td>' . $posto_cidade . '</td>';
				$conteudo.= '<td>' . $posto_estado . '</td>';
				$conteudo.= '<td>' . $consumidor_nome. '</td>';
				$conteudo.= '<td>' . $consumidor_fone. '</td>';
				$conteudo.= '<td>' . $consumidor_endereco. '</td>';
				$conteudo.= '<td>' . $consumidor_cidade. '</td>';
				$conteudo.= '<td>' . $consumidor_estado. '</td>';

            }

			$conteudo.= '<td>' . $defeito_reclamado . '</td>';
			$conteudo.= '<td>' . $defeito_constatado . '</td>';
			$conteudo.= '<td>' . $referencia . '</td>';
			$conteudo.= '<td>' . $descricao . '</td>';
			if($login_fabrica == 50){
                $conteudo.= '<td>' . $produto . '</td>';
                $conteudo .= '<td>' . $serie . '__replaceC__</td>';
			} else {
			    $conteudo .= '<td>' . $serie . '</td>';
            }
			$conteudo.= '<td>' . $data_fabricacao . '</td>';
			if ($login_fabrica != 120) {
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
        if ($login_fabrica == 50) {
            fwrite($f, preg_replace("/__replaceC__/", "C", $conteudo));
        } else {
            fwrite($f, $conteudo);
        }

		fclose($f);

		$conteudo.= '<div align="center"><input type="button" value="Download do arquivo Excel" onClick="download(\'xls/' . $arq_nome . '\')" /></div><br/>';

		$sql_servico = "
			select desc_serv_real as descricao, count(temp_os.os_item) as servicos
			FROM temp_os
			JOIN temp_numero_serie ON temp_os.serie = temp_numero_serie.serie AND temp_numero_serie.produto = temp_os.produto
			JOIN tbl_os_item ON temp_os.os_item = tbl_os_item.os_item
            JOIN tbl_peca ON tbl_os_item.peca = tbl_peca.peca
            $joinNSFornecedor
            WHERE 1=1 $cond_peca
			group by desc_serv_real order by servicos desc
			";

		$sql_defeito = "
			select desc_defeito as descricao, count(distinct temp_os.os_item) as defeitos
			FROM temp_os
            JOIN temp_numero_serie ON temp_os.serie = temp_numero_serie.serie AND temp_numero_serie.produto = temp_os.produto
            JOIN tbl_os_item ON temp_os.os_item = tbl_os_item.os_item
            JOIN tbl_peca ON tbl_os_item.peca = tbl_peca.peca
            $joinNSFornecedor
            WHERE 1=1 $cond_peca
			group by desc_defeito order by defeitos desc";

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

			<?php if (!empty($categorias_servico) and !empty($totais_servico) and !empty($peca)): ?>
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

