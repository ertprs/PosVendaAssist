<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
	<head>
		<meta http-equiv='pragma' content='no-cache'>
		<title></title>
		<script type="text/javascript" src="../js/jquery-1.4.2.js"></script>
		<script src="../plugins/jquery/jquery.tablesorter.min.js" type="text/javascript"></script>
		<link rel="stylesheet" type="text/css" href="../css/lupas/lupas.css">
		<style type="text/css">
			body {
				margin: 0;
				font-family: Arial, Verdana, Times, Sans;
				background: #fff;
			}

			.lp_tabela td{
				cursor: default; !important;
			}
		</style>
		<script type='text/javascript'>
			//função para fechar a janela caso a telca ESC seja pressionada!
			$(window).keypress(function(e) {
				if(e.keyCode == 27) {
					 window.parent.Shadowbox.close();
				}
			});

			$(document).ready(function() {
				$("#gridRelatorio").tablesorter();
			});
		</script>
	</head>

	<body>
		<div class="lp_header">
			<a href='javascript:window.parent.Shadowbox.close();' style='border: 0;'>
				<img src='../css/modal/excluir.png' alt='Fechar' class='lp_btn_fechar' />
			</a>
		</div>

		<?php
			if($sistema_lingua == 'ES'){?>
		<div class='lp_pesquisando_por'>
			<h3>Llamados de error</h3>
			Llamados de Error en programa puede ser apertos en cualquier momento<br>
			por cualquier usuario y la equipo de Telecontrol las tratará con prioridad.<br/><br/><br/>
			Estimado usuario, consulte la lista a continuación para obtener un registro idéntico.<br />
			En caso de llamadas abiertas como "error del sistema" y, de hecho, tienen requisitos de desarrollo, independientemente de la aprobación, se cobrarán de acuerdo con el tiempo necesario para el servicio, análisis o desarrollo.
		</div>
	
			<? }else{ ?>

		<div class='lp_pesquisando_por'>
			<h3>Chamados de Erro</h3>
			Chamados de Erro em programa podem ser abertos a qualquer momento<br>
			por qualquer usuário e a equipe Telecontrol tratará como prioridade.<br/><br/><br/>
			Prezado(a) Usuário(a), queira conferir na lista abaixo se existe um registro idêntico.<br />
			No caso de chamados abertos como "erro de sistema" e na realidade apresentarem quesitos para desenvolvimento, independente de aprovação, haverá cobrança de acordo com o tempo utilizado para o atendimento, análise ou desenvolvimento.
		</div>

		<?}

			$sql = "SELECT
						tbl_hd_chamado.hd_chamado,
						tbl_hd_chamado.tipo_chamado,
						tbl_hd_chamado.categoria,
						tbl_hd_chamado.status,
						tbl_hd_chamado.titulo,
						tbl_hd_chamado.data,
						tbl_admin.nome_completo AS admin
					FROM tbl_hd_chamado
						LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado.admin AND tbl_admin.fabrica = {$login_fabrica}
					WHERE tbl_hd_chamado.fabrica = {$login_fabrica}
						AND tbl_hd_chamado.status NOT IN ('Novo','Cancelado','Resolvido')
						AND tbl_hd_chamado.tipo_chamado = 5
						;";

			$res = pg_query($con, $sql);
			if (pg_num_rows($res) > 0 ){?>

				<table width='100%' border='0' cellspacing='1' cellspading='0' class='lp_tabela' id='gridRelatorio'>
					<thead>
						<tr>
							<th>HD</th>
							<th><?=traduz('Data')?></th>
							<th>Status</th>
							<th>Admin</th>
							<th><?=traduz('Título')?></th>
						</tr>
					</thead>
					<tbody>
						<?
						for ($i = 0 ; $i < pg_num_rows($res); $i++) {
							extract(pg_fetch_array($res));
							$data = implode("/",array_reverse(explode("-", substr($data,0,10))));

							$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
							echo "<tr style='background: $cor'>";
								echo "<td style='text-align: center'>
										<a href='chamado_detalhe.php?hd_chamado={$hd_chamado}' target='_blank' style='font-weight: bold'>{$hd_chamado}</a>
									  </td>";
								echo "<td>{$data}</td>";
								echo "<td>{$status}</td>";
								echo "<td>{$admin}</td>";
								echo "<td>{$titulo}</td>";
							echo "</tr>";
						}
					echo "</tbody>";
				echo "</table>";
			}
		?>
	</body>
</html>
