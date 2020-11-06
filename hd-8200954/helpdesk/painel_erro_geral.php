<?php
	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	
	// usando para pesquisa em Ajax
	$consulta_dados = @$_POST['consulta_dados'];
	//if($consulta_dados == "busca_dados_ajax"){
		function verificaValor($valor){
			if($valor > 0)
				return $valor;
			else
				return 0;
		}

		$hd_analise = "";
		$hd_abertos = "";
		$semana = "";
//		echo date('d')+3;
		for ($x = 0 ; $x < 7 ; $x++){
			$date = date("Y-m-d",mktime(0, 0, 0, date("m"), date("d") - $x, date("Y")));


			$sql = "
					SELECT
					count(*)
					FROM
						tbl_hd_chamado
					WHERE
			titulo <> 'Atendimento interativo'
                                        AND fabrica_responsavel = 10 and categoria <> 'Novos' and tipo_chamado <> 6 AND
		status <> 'Novo' and data BETWEEN '$date 00:00:00' AND '$date 23:59:59';";

//			echo nl2br($sql);
			$res = pg_query($con, $sql);
			$total_hd_dia = pg_result($res,0,0);
			$hd_analise .= $total_hd_dia."|";
			$total_hd_hd_analise += $total_hd_analise;
			
			if($x == 0)
				$hoje_novo = $total_hd_analise;

			$sql = "	SELECT 
							COUNT(hd_chamado) 
						FROM 
							tbl_hd_chamado 
						WHERE 
							tipo_chamado IS NOT NULL 
							AND status <> 'Cancelado' 
							AND data_resolvido BETWEEN '$date 00:00:00' AND '$date 23:59:59';";
			$res = pg_query($con, $sql);
	//		echo $sql;	
			$hd_abertos .= pg_fetch_result($res,0)."|";
			$total_hd_hd_abertos += pg_fetch_result($res,0);
			
			if($x == 0)
				$hoje_resolvido = pg_fetch_result($res,0);
			
			//Pega o dia da semana no banco de dados
			$sql = "
				SELECT 
				CASE EXTRACT(DOW FROM DATE '$date')
					WHEN 0 THEN 'Domingo'
					WHEN 1 THEN 'Segunda'
					WHEN 2 THEN 'Terça'
					WHEN 3 THEN 'Quarta'
					WHEN 4 THEN 'Quinta'
					WHEN 5 THEN 'Sexta'
					WHEN 6 THEN 'Sábado'
					ELSE 'Semana'
				END;";
			$res = pg_query($con, $sql);
			$semana .= pg_fetch_result($res,0)."|";

		}
		
		$hoje_total = $hoje_resolvido - $hoje_novo;
		$retorno = $hd_analise.$total_hd_hd_analise."|".$hd_abertos.$total_hd_hd_abertos."|".$semana;
		
		//print_r(explode("|",$retorno.$hoje_novo."|".$hoje_resolvido."|".$hoje_total));
		$dados = explode("|",$retorno.$hoje_novo."|".$hoje_resolvido."|".$hoje_total);


//		print_r($dados);
	//}
?>
		<style type="text/css">
			*{
				font-family: Verdana,Arial,sans-serif;
			}
			body, html{
				padding: 0;
				margin: 0;
				background: #FCFCFC;

			}

			h1{
				font-size: 24px; 
				color: #F00;
				margin: 40px;
				font-weight: normal;
				margin: 5px;
				padding: 0;
			}

			.painel{
				margin: 5px 40px;
				border: 1px solid #999;
				padding: 1px;
				background: #FFF;

			}

			.titlePainel {
				color: #666;
				font-size: 16px;
				padding: 0 10px;
				padding: 10px;
				text-align:  right;
				background: #CCC;
			}

			.totalHD{
				font-size: 200px;
				color: #F00;
				text-align: center;
				font-weight: bold;
			}
			
			.descricaoHD{
				font-size: 60px;
				color: #F00;
				text-align: center;
			}
			table{
				width: 98%;
				margin: 10px auto;
				background: #026089
			}
			
			table thead th{
				width: 13%;
			}
			
			table tbody td{
				font-size: 100px;
				color: #026089;
				text-align: center;
				font-weight: bold;
				background: #FCFCFC;
			}
			
			#hj_hd_analise, #hj_hd_fechados, #hj_total{
				font-size: 200px;
				color: #026089;
				text-align: center;
				font-weight: bold;
				background: #FCFCFC;
			}


			table tbody th{
				background: #026089;
				color: #FFF;
				font-size: 16px;
				text-align: left;
				padding: 5px 10px;
			}
			
			table thead th{
				background: #CCC;
				color: #026089;
			}
			
			table caption{
				font-size: 18px;
				text-align: right;
				padding: 10px;
				border: 1px solid #026089;
				border-bottom: none;
				color: #fff;
				background: #026089;
				font-weight: bold;
			}
			
			#hd_total, #hd_total_r, #hj_total{
				background: #93D69A;
			}
			
			#hd_total{
				background: #F49C9C;
			}

			#hj_hd_analise{
				
				background: #F49C9C;
			}
			
			#hj_hd_fechados{
				background: #93D69A;
			}

			
			#panelErros{
				z-index: 0;
			}



			#galeria ul, #galeria li{
				margin: 0 auto;
				padding: 0;
				text-align: center !important;
				list-style: none;
			}

			#galeria li img{
				margin: auto;
			}
		</style>

			<table cellpadding="2" cellspacing="1" border="0">
				<caption>Painel: "Chamados"</caption>
				<thead>
					<tr>
						<th colspan=2>Geral</th>
                        <th colspan="2">Hoje</th>
					</tr>
				</thead>
				<tbody>
					<tr> 
						<th style="width: 40%">Total</th>
						<th>Sem Confirmação Cliente</th>
                        <th style="width: 30%">Erros</th>
						<th style="width: 30%">Chamados Fechados</th>
						<!-- <th style="width: 33%">Total</th> -->
					</tr>
					<tr>
						<td id='hj_hd_total' >
                        <?php
                            $sql = "SELECT 
                                        COUNT(hd_chamado) AS total
                                     FROM tbl_hd_chamado
                                     WHERE
                                        status NOT IN ('Resolvido','Cancelado', 'Novo','Suspenso')
                                        AND titulo <> 'Atendimento interativo'
                                        AND fabrica_responsavel = 10 and categoria <> 'Novos' and tipo_chamado <> 6;";

                            $res = pg_query($con, $sql);
                            echo $total_hd = pg_fetch_result($res,0,'total');
                        ?>
                        </td>

			 <?php
                            $sql = "SELECT 
                                        COUNT(hd_chamado) AS total
                                     FROM tbl_hd_chamado
                                     WHERE
                                        status in ('Resolvido')
                                        AND titulo <> 'Atendimento interativo'
                                        AND fabrica_responsavel = 10 and categoria <> 'Novos' and tipo_chamado <> 6 and data_resolvido is null and data > '2015-05-14 00:00:00';";

                            $res = pg_query($con, $sql);
                            $total_hd_sem_resposta = pg_fetch_result($res,0,'total');
                        ?>

			<td><?=$total_hd_sem_resposta?></td>
                        <?php
                            $sql = "SELECT 
                                        COUNT(hd_chamado) AS total
                                     FROM tbl_hd_chamado
                                     WHERE
                                        status NOT IN ('Resolvido','Cancelado', 'Novo','Suspenso')
                                        AND titulo <> 'Atendimento interativo'
                                        AND fabrica_responsavel = 10 and categoria <> 'Novos' and tipo_chamado =  5;";

                            $res = pg_query($con, $sql);
                            $total_hd_erro = pg_fetch_result($res,0,'total');
                        ?>
                        <td id='hj_hd_analise' ><?php echo $total_hd_erro ?></td>
						<td id='hj_hd_fechados' ><?php echo $dados[24]; ?></td>
						<!-- <td id='hj_total' ><?php echo $dados[1]; ?></th> -->
					</tr>
				</tbody>
			</table>
			
			<table cellpadding="2" cellspacing="1" border="0">
			<thead>
					<tr>
						<!-- <th id='sm_0'><?php echo $dados[1]; ?></th> -->
						<th id='sm_6'><?php echo $dados[22]; ?></th>
						<th id='sm_5'><?php echo $dados[21]; ?></th>
						<th id='sm_4'><?php echo $dados[20]; ?></th>
						<th id='sm_3'><?php echo $dados[19]; ?></th>
						<th id='sm_2'><?php echo $dados[18]; ?></th>
						<th id='sm_1'><?php echo $dados[17]; ?></th>
						<th>Total</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<th colspan='7'>Chamados para Análise</th>
					</tr>
					<tr>
						<!-- <td id='hd_0'><?php echo $dados[7]; ?></th> -->
						<td id='hd_6'><?php echo $dados[6]; ?></th>
						<td id='hd_5'><?php echo $dados[5]; ?></th>
						<td id='hd_4'><?php echo $dados[4]; ?></th>
						<td id='hd_3'><?php echo $dados[3]; ?></th>
						<td id='hd_2'><?php echo $dados[2]; ?></th>
						<td id='hd_1'><?php echo $dados[1]; ?></th>
						<td id='hd_total'><?php echo $dados[7]; ?></th>
					</tr>
					<tr>
						<th colspan="7">Chamados Fechados</th>
					</tr>
					<tr>
						<!-- <td id='hd_0_r'><?php echo $dados[1]; ?></th> -->
						<td id='hd_6_r'><?php echo $dados[14]; ?></th>
						<td id='hd_5_r'><?php echo $dados[13]; ?></th>
						<td id='hd_4_r'><?php echo $dados[12]; ?></th>
						<td id='hd_3_r'><?php echo $dados[11]; ?></th>
						<td id='hd_2_r'><?php echo $dados[10]; ?></th>
						<td id='hd_1_r'><?php echo $dados[9]; ?></th>
						<td id='hd_total_r'><?php echo $dados[15]; ?></th>
					</tr>
				</tbody>
			</table>
