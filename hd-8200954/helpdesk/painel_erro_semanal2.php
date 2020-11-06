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

		$novos = "";
		$resolvidos = "";
		$semana = "";
		for ($x = 0 ; $x < 7 ; $x++){
			$date = date("Y-m-d",mktime(0, 0, 0, date("m"), date("d") - $x, date("Y")));

			$sql = "	SELECT 
							COUNT(hd_chamado) 
						FROM 
							tbl_hd_chamado 
						WHERE 
							tipo_chamado = 5 
							AND status <> 'Cancelado' 
							AND data BETWEEN '$date 00:00:00' AND '$date 23:59:59';";
			$res = pg_query($con, $sql);
			
			$novos .= pg_fetch_result($res,0)."|";
			$total_hd_novos += pg_fetch_result($res,0);
			
			if($x == 0)
				$hoje_novo = pg_fetch_result($res,0);


			$sql = "	SELECT 
							COUNT(hd_chamado) 
						FROM 
							tbl_hd_chamado 
						WHERE 
							tipo_chamado = 5 
							AND status <> 'Cancelado' 
							AND data_resolvido BETWEEN '$date 00:00:00' AND '$date 23:59:59';";
			$res = pg_query($con, $sql);
			
			$resolvidos .= pg_fetch_result($res,0)."|";
			$total_hd_resolvidos += pg_fetch_result($res,0);
			
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
						    ELSE '<?php echo $dados[0]; ?>'
						  END;
			";
			$res = pg_query($con, $sql);
			$semana .= pg_fetch_result($res,0)."|";

		}
		
		$hoje_total = $hoje_resolvido - $hoje_novo;
		$retorno = $novos.$total_hd_novos."|".$resolvidos.$total_hd_resolvidos."|".$semana;
		
		$dados = explode("|",$retorno.$hoje_novo."|".$hoje_resolvido."|".$hoje_total);
			
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
				background: #A51515
			}
			
			table thead th{
				width: 13%;
			}
			
			table tbody td{
				font-size: 100px;
				color: #A51515;
				text-align: center;
				font-weight: bold;
				background: #FCFCFC;
			}
			
			#hj_novos, #hj_resolvidos, #hj_total{
				font-size: 200px;
				color: #A51515;
				text-align: center;
				font-weight: bold;
				background: #FCFCFC;
			}

			#hj_resolvidos{
				background: #93D69A url(imagem/logo/logoLinux.png) top right no-repeat;
			}
			
			#hj_novos{
				background: #F49C9C url(imagem/logo/logoMicrosoft.png) top left no-repeat;
			}

			table tbody th{
				background: #A51515;
				color: #FFF;
				font-size: 16px;
				text-align: left;
				padding: 5px 10px;
			}
			
			table thead th{
				background: #CCC;
				color: #A51515;
			}
			
			table caption{
				font-size: 18px;
				text-align: right;
				padding: 10px;
				border: 1px solid #A51515;
				border-bottom: none;
				color: #fff;
				background: #A51515;
				font-weight: bold;
			}
			
			#hd_total, #hd_total_r, #hj_total{
				background: #93D69A;
			}
			
			#hd_total{
				background: #F49C9C;
			}
			
			#panelErros{
				z-index: 0;
			}

			#galeria{
				z-index: 100;
				width: 100%;
				height: 100%;
				background: #000 url('http://i1.peperonity.info/c/4DD967/538119/ssc3/home/084/aawarapan1/178_1848_tv_chuvisco.gif_320_320_256_9223372036854775000_0_1_0.gif');
				text-align:center;
				position: absolute;
				margin: 0;
				padding: 0;
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

		<div id='panelErros'>
			<table cellpadding="2" cellspacing="1" border="0">
				<caption>Painel de Erros: "Erro no Programa"</caption>
				<thead>
					<tr>
						<th colspan="2">Hoje</th>
					</tr>
				</thead>
				<tbody>
					<tr> 
						<th style="width: 50%">Chamados Novos</th>
						<th style="width: 50%">Chamados Resolvidos</th>
						<!-- <th style="width: 33%">Total</th> -->
					</tr>
					<tr>
						<td id='hj_novos' ><?php echo $dados[23]; ?></td>
						<td id='hj_resolvidos' ><?php echo $dados[24]; ?></td>
						<!-- <td id='hj_total' ><?php echo $dados[25]; ?></td> -->
					</tr>
				</tbody>
			</table>
			
			<table cellpadding="2" cellspacing="1" border="0">
			<thead>
					<tr>
						<!-- <th id='sm_0'><?php echo $dados[23]; ?></th> -->
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
						<th colspan='7'>Chamados Novos</th>
					</tr>
					<tr>
						<!-- <td id='hd_0'><?php echo $dados[7]; ?></td> -->
						<td id='hd_6'><?php echo $dados[6]; ?></td>
						<td id='hd_5'><?php echo $dados[5]; ?></td>
						<td id='hd_4'><?php echo $dados[4]; ?></td>
						<td id='hd_3'><?php echo $dados[3]; ?></td>
						<td id='hd_2'><?php echo $dados[2]; ?></td>
						<td id='hd_1'><?php echo $dados[1]; ?></td>
						<td id='hd_total'><?php echo $dados[7]; ?></td>
					</tr>
					<tr>
						<th colspan="7">Chamados Resolvidos</th>
					</tr>
					<tr>
						<!-- <td id='hd_0_r'><?php echo $dados[15]; ?></td> -->
						<td id='hd_6_r'><?php echo $dados[14]; ?></td>
						<td id='hd_5_r'><?php echo $dados[13]; ?></td>
						<td id='hd_4_r'><?php echo $dados[12]; ?></td>
						<td id='hd_3_r'><?php echo $dados[11]; ?></td>
						<td id='hd_2_r'><?php echo $dados[10]; ?></td>
						<td id='hd_1_r'><?php echo $dados[9]; ?></td>
						<td id='hd_total_r'><?php echo $dados[15]; ?></td>
					</tr>
				</tbody>
			</table>
		</div>
		
		<!-- <embed src="http://www.phon.ucl.ac.uk/home/mark/audio/success.wav" autostart="true" width="0" height="0" id="sound1" enablejavascript="true"> -->
		<script>
			setTimeout(function() { window.location.reload(); }, 360000);
		</script>
	</body>
</html>
