<?php

$areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

if ($areaAdmin === true) {
	include __DIR__.'/admin/autentica_admin.php';
} else {
	include __DIR__.'/autentica_usuario.php';
}

$plugins = array(
   "dataTable"
);

include __DIR__.'/admin/plugin_loader.php';
?>
<!DOCTYPE html>
<html>
	<head>
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
		<!-- Latest compiled and minified JavaScript -->
		<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
		<style>
			#menu {
				top: 0; 
				width: 100%;
			}
			#menu ul {
			    padding:0px;
			    margin-bottom:10px;
			    list-style:none;
			    text-align: center
			}
			#menu ul li { 
				display: inline; 
			}
			#menu ul li a {
			    padding: 2px 10px;
			    display: inline-block;
			    text-decoration: none;
				width: 200px;
				color: black;
				font-weight: bold;
			}
			#menu ul li #conclusiva {
				background-color: rgb(0,0,255,0.2);
			}
			#menu ul li #finalizado {
				background-color: rgb(0,255,0,0.2);
			}
			#menu ul li #cancelado {
				background-color: rgb(255,0,0,0.2);
			}
			table {
				width: 100%;
			}
			table thead tr { 
				background-color: blue;  
			}
			table thead tr th { 
				color: white;  
			}
		</style>
	</head>
	<body>
		<nav id="menu">
		    <ul>
		        <li><a id="conclusiva">Resposta Conclusiva</a></li>
		        <li><a id="finalizado">Finalizado</a></li>
		        <li><a id="cancelado">Cancelado</a></li>
		    </ul>
		</nav>
		<div class="content" >
			<table class="table table-bordered table-striped table-dark">
				<thead>
					<tr>
						<th>Nº Help-Desk</th>
						<th>Status</th>
						<th width="50%;">Mensagem</th>
						<th>Admin</th>
						<th>Data</th>
					</tr>
				</thead>
				<tbody>
				<?php 
				$sql = "SELECT 	tbl_hd_chamado.hd_chamado,  
								status_item,
								comentario,
								atendente,
								tbl_hd_chamado_item.interno,
								tbl_hd_chamado.data,
								tbl_admin.nome_completo
						FROM tbl_hd_chamado 
						JOIN tbl_hd_chamado_extra
						ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado 
						JOIN tbl_hd_chamado_item
						ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_item.hd_chamado
						JOIN  tbl_admin
						ON tbl_hd_chamado.atendente = tbl_admin.admin
						where pedido = {$_GET['pedido']};";

				$res = pg_query($con ,$sql);
				

				if (pg_num_rows($res)) {
					while ($hd = pg_fetch_object($res)) {
						
                    	/**
	                     * @author William Castro <william.castro@telecontrol.com.br>
	                     * 
	                     * hd-6517984
	                     * 
	                     * Bloquei na visualização de mensagens que sejam internas
	                     *
                     	*/

						if ($login_fabrica == 35 && !$areaAdmin) { 

							if ($hd->interno == 't') {
								continue;
							}
						}
					
					if ($hd->status_item == "Ag. Conclusão") {
						$color = "background-color: rgb(0,0,255,0.2);";
					} else if ($hd->status_item == "Finalizado") {
						$color = "background-color: rgb(0,255,0,0.2);";
					} else if ($hd->status_item == "Cancelado") {
						$color = "background-color: rgb(255,0,0,0.2);";
					} else {
						$color = "";
					}
					?>
						<tr style="<?=$color?>">
							<td><a target="_blank" href="helpdesk_posto_autorizado_atendimento.php?hd_chamado=<?=$hd->hd_chamado;?>"><?=$hd->hd_chamado?></a></td>
							<td><?=$hd->status_item?></td>
							<td><?=$hd->comentario?></td>
							<td><?=$hd->nome_completo?></td>
							<td><?=date("d/m/Y G:i", strtotime("$hd->data"));?></td>
						</tr>
					<?php
					}
				}
				?>
				</tbody>
			</table>
		</div>
	</body>
</html>