<?
	include "dbconfig.php";
	include "includes/dbconnect-inc.php";
	include 'autentica_admin.php';

	$hds	= trim($_REQUEST["hds"]);

	$sql = "
			SELECT 
				tbl_backlog_item.backlog_item, 
				tbl_backlog_item.backlog,
				tbl_backlog_item.hd_chamado,
				tbl_backlog_item.prioridade,
				tbl_backlog_item.projeto,
				tbl_backlog_item.analista,
				tbl_hd_chamado.fabrica,
				TO_CHAR(tbl_hd_chamado.data_resolvido,'DD/MM/YYYY') AS data_resolvido,
				tbl_fabrica.nome,
				tbl_hd_chamado.titulo,
				tbl_hd_chamado.atendente,
				tbl_tipo_chamado.descricao,
				tbl_backlog_item.desenvolvedor,
				tbl_backlog_item.suporte,
				tbl_backlog_item.fator_hora
			FROM tbl_backlog_item
				JOIN tbl_hd_chamado ON tbl_hd_chamado.hd_chamado = tbl_backlog_item.hd_chamado
				JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_hd_chamado.fabrica
				LEFT JOIN tbl_tipo_chamado ON tbl_tipo_chamado.tipo_chamado = tbl_hd_chamado.tipo_chamado
			WHERE 
				backlog_item IN ($hds)
			ORDER BY backlog_item;";	
	$res = pg_query($con, $sql);
	$registros = pg_num_rows($res);
	
	$backlog = $_REQUEST['backlog'];
	
	$sql_backlog = "SELECT TO_CHAR(data_inicio, 'DD/MM/YYYY') AS data_inicio, TO_CHAR(data_fim, 'DD/MM/YYYY') AS data_fim FROM tbl_backlog WHERE backlog = $backlog;";
	$res_backlog = pg_query($con, $sql_backlog);
	
	$dados_backlog = "<b>Backlog: ".sprintf("%04s", $backlog)."</b><br />".pg_result($res_backlog, 0, 'data_inicio')." ~ ".pg_result($res_backlog, 0, 'data_fim');
	
	
	function buscaADM($admin){
		if(strlen($admin) > 0){
			$sql_admin = "SELECT nome_completo FROM tbl_admin WHERE admin = $admin;";
			$res_admin = pg_query($sql_admin);
	
			return pg_result($res_admin, 0, 'nome_completo');
		}
	}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
	<head>
		<meta http-equiv=pragma content=no-cache>
		<title><?php echo Date("Ymd-His")."_BackLogPrintPostIT";?></title>
		<style type="text/css" media="all">
			body {
				margin: 0;
				font-family: Arial, Verdana, Times, Sans;
				background: #fff;
			}
			
			ul, li{
				margin: 0;
				padding: 0;
				text-align: left;
				list-style: none;
			}
			
			#content{
				width: 800px;
			}
			
			#content li{
				width: 245px;
				height: 260px; 
				border-right: 1px dashed #CCC;
				border-top: 1px dashed #CCC;
				padding: 10px;
				/*display: inline-table;*/
				float: left
			}
			
			#content li table{
				font-size: 11px;
				width: 245px;
				background: #999;

			}

			#content li table th{
				background: #CCC;
				font-size: 11px;
				width: 245px;
				padding: 3px;
				text-transform: uppercase;
				text-align: left;
				font-weight: bold;
			}
			
			#content li table td{
				background: #FFF;
				padding: 2px;
			}
			
			.pag{
				page-break-after: always;
			}
			
			.dados_backlog{
				text-align: center;
				font-size: 14px;
				color: #666;
			}	
		</style>
		<script type="text/javascript" src="js/jquery-1.4.2.js"></script>
		<script type='text/javascript'>
			//função para fechar a janela caso a telca ESC seja pressionada!
			$(window).keypress(function(e) { 
				if(e.keyCode == 27) { 
					 window.parent.Shadowbox.close();
				}
			});

		</script>
	</head>
	<!--  " -->
	<body onload="window.print();">
		<div id="content">
				<?php 
					$pag = 1;
					for($i = 0; $i < $registros; $i++){
						if($pag == 1){
							echo "<div class='pag'>";
								echo "<ul>";
						}
						
						$backlog_item     = pg_result($res, $i, 'backlog_item');
						$backlog          = pg_result($res, $i, 'backlog');
						$hd_chamado       = pg_result($res, $i, 'hd_chamado');
						$titulo       	  = pg_result($res, $i, 'titulo');
						$prioridade       = pg_result($res, $i, 'prioridade');
						$fabrica          = pg_result($res, $i, 'fabrica');
						$nome_fabrica     = pg_result($res, $i, 'nome');
						$tipo             = pg_result($res, $i, 'descricao');
						$projeto          = pg_result($res, $i, 'projeto');
						$analista         = buscaADM(pg_result($res, $i, 'analista'));
						$desenvolvedor    = buscaADM(pg_result($res, $i, 'desenvolvedor'));
						$suporte          = buscaADM(pg_result($res, $i, 'suporte'));
						$atendente        = buscaADM(pg_result($res, $i, 'atendente'));
						$data_resolvido   = strlen(pg_result($res, $i, 'data_resolvido')) > 0 ? "  - ".pg_result($res, $i, 'data_resolvido') : "";
						?>
						<li>
							<table border="0" cellpadding="0" cellspacing="1">
								<tr>
									<td colspan="3">
										<div class="dados_backlog"><?php echo $dados_backlog;?></div>
									</td>
								</tr>
								<tr>
									<th width='33%'>CHAMADO</th>
									<td width='66%' colspan="2">&nbsp;<?php echo $hd_chamado;?> <b><?php echo $data_resolvido;?></b></td>
								</tr>
								<tr>
									<th>FÁBRICA</th>
									<td colspan="2">&nbsp;<?php echo sprintf("%03s", $fabrica). " - ". $nome_fabrica; ?></td>
								</tr>
								<tr>
									<th>ANALISTA</th>
									<td colspan="2">&nbsp;<?php echo $analista;?></td>
								</tr>
								<tr>
									<th>DESENV.</th>
									<td colspan="2">&nbsp;<?php echo $desenvolvedor;?></td>
								</tr>
								<tr>
									<th>SUPORTE</th>
									<td colspan="2">&nbsp;<?php echo $suporte;?></td>
								</tr>
								<tr>
									<th>ATENDENTE</th>
									<td colspan="2">&nbsp;<?php echo $atendente;?></td>
								</tr>
								<tr>
									<th style="text-align: center">PRIORIDADE</th>
									<th style="text-align: center" colspan='2'>TIPO</th>
								</tr>
								<tr>
									<td style="text-align: center">&nbsp;<?php echo $prioridade;?>&nbsp;</td>
									<td style="text-align: center" nowrap colspan='2'>&nbsp;<?php echo $tipo;?>&nbsp;</td>
								</tr>
								<tr>
									<th colspan="3">DESCRIÇÃO</th>
								</tr>
								<tr>
									<td colspan="3">
										<div style="height: 28px;">
											<?php echo $titulo;?>		
										</div>
									</td>
								</tr>
							</table>
						</li>
						<?
						if($pag == 12 OR $i == ($registros - 1)){
								echo "</ul>";
								echo "<div style='clear: both;'>&nbsp;</div>";
							echo "</div>";
							$pag = 0;
						}
						
						$pag += 1;
					}
				?>
			</ul>
		</div>
		<?php
			$itens = explode(',', $hds);

			foreach ($itens as $hd) {
				echo "<script>window.parent.impresso({$hd})</script>";
			}
			

			$sql = "UPDATE tbl_backlog_item SET impresso = NOW() WHERE backlog_item IN ($hds)";
			pg_query($con, $sql);
		?>
	</body>
</html>