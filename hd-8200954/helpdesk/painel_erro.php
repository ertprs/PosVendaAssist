<?php
		
	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	
	
	// usando para pesquisa em Ajax
	$consulta_dados = @$_POST['consulta_dados'];
	if($consulta_dados == "busca_dados_ajax"){
		function verificaValor($valor){
			if($valor > 0)
				return $valor;
			else
				return 0;
		}

		$sql_hoje = "SELECT COUNT(hd_chamado) FROM tbl_hd_chamado WHERE tipo_chamado = 5 AND status <> 'Cancelado' AND DATE(data) =  DATE(NOW());";
		$res_hoje = pg_query($con, $sql_hoje);

		$sql_ontem = "SELECT COUNT(hd_chamado) FROM tbl_hd_chamado WHERE tipo_chamado = 5 AND status <> 'Cancelado' AND DATE(data) = DATE(NOW()) - INTERVAL '1 day';";
		$res_ontem = pg_query($con, $sql_ontem);

		$sql_hoje_resolvido = "SELECT COUNT(hd_chamado) FROM tbl_hd_chamado WHERE status <> 'Cancelado' AND tipo_chamado = 5 AND DATE(data_resolvido) =  DATE(NOW());";
		$res_hoje_resolvido = pg_query($con, $sql_hoje_resolvido);

		$sql_ontem_resolvido = "SELECT COUNT(hd_chamado) FROM tbl_hd_chamado WHERE status <> 'Cancelado' AND tipo_chamado = 5 AND DATE(data_resolvido) =  DATE(NOW()) - interval '1 day';";
		$res_ontem_resolvido = pg_query($con, $sql_ontem_resolvido);

		//Layout Retorno = HD Hoje | HD Ontem | HD Resolvido Hoje | HD Resolvido Ontem | Total Abertos | Total Finalizado
		echo 
			pg_fetch_result($res_hoje,0)."|".
			pg_fetch_result($res_ontem,0)."|".
			pg_fetch_result($res_hoje_resolvido,0)."|".
			pg_fetch_result($res_ontem_resolvido,0)."|".
			(pg_fetch_result($res_ontem,0)+pg_fetch_result($res_hoje,0))."|".
			(pg_fetch_result($res_hoje_resolvido,0)+pg_fetch_result($res_ontem_resolvido,0));
		exit;				
	}
	
	
				
				
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>HD - Painel de Erros</title>
		<script type="text/javascript" src="js/jquery.js"></script>
		<script type="text/javascript">
			function atualizaDados(){
				$.ajax({
							url: "<?php echo $PHP_SELF;?>",
							type: "POST",
							data: "consulta_dados=busca_dados_ajax",
							success: function(retorno){
								dados = retorno.split('|');
								//Layout Retorno = HD Hoje | HD Ontem | HD Resolvido Hoje | HD Resolvido Ontem | Total Abertos | Total Finalizado
								$('#hd_hoje').html(dados[0]);
								$('#hd_ontem').html(dados[1]);
								$('#hd_demais').html(dados[4]);
								
								$('#hd_ontem_resolvidos').html(dados[3]);
								$('#hd_hoje_resolvidos').html(dados[2]);
								$('#hd_demais_resolvidos').html(dados[5]);
							}
						});
				setTimeout("atualizaDados()",10000);
			}
					
			$(document).ready(function() { 
				atualizaDados();	
			
			});
	
		</script>
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
				width: 33%;
			}
			
			table tbody td.dados{
				font-size: 200px;
				color: #A51515;
				text-align: center;
				font-weight: bold;
				background: #FCFCFC;
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
				background: #999;
			}
			
			#hd_demais_resolvidos, #hd_demais{
				background: #F49C9C;
			}
		
		</style>
	</head>

	<body>
		<table cellpadding="2" cellspacing="1" border="0">
			<caption>Painel de Erros: "Erro no Programa"</caption>
			<thead>
				<tr>
					<th>Ontem</th>
					<th>Hoje</th>
					<th>Total</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<th colspan='3'>Chamados Novos</th>
				</tr>
				<tr>
					<td id='hd_ontem' class='dados'>&nbsp;</td>
					<td id='hd_hoje' class='dados'>&nbsp;</td>
					<td id='hd_demais' class='dados'>&nbsp;</td>
				</tr>
				<tr>
					<th colspan="3">Chamados Resolvidos</th>
				</tr>
				<tr>
					<td id='hd_ontem_resolvidos' class='dados'>&nbsp;</td>
					<td id='hd_hoje_resolvidos' class='dados'>&nbsp;</td>
					<td id='hd_demais_resolvidos' class='dados'>&nbsp;</td>
				</tr>
			</tbody>
		</table>
<!--
		<div class="painel">
			<div class='totalHD'>10</div>
			<div class='descricaoHD'>Novos</div>
			
			<hr style='background: #F00' />
	
			<div class='totalHD'>50</div>
			<div class='descricaoHD'>Resolvidos</div>
		</div>
//-->		
		</body>

</html>
