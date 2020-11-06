<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "funcoes.php";

if($_POST['modal_usuarios'] == true){
    $posto = $_POST['posto_modal'];
    $data = new DateTime();
    $paramentros_adicionais['data_abertura_modal'] = $data->format('d-m-Y');
    $paramentros_adicionais = json_encode($paramentros_adicionais);

    $sql_update_posto = "UPDATE tbl_login_unico set parametros_adicionais = '$paramentros_adicionais' WHERE posto = {$posto}";

	$res_usuarios_postos = pg_query($con, $sql_update_posto);
	
	if ($res_usuarios_postos == false) {
        exit(json_encode(["erro" => false]));
    } else {
        exit(json_encode(["erro" => true]));
    }
}

$posto_id = $_GET['posto_id'];
	$sql_usuarios_posto = "
		SELECT 
			nome, 
			email, 
			ativo,
			abre_os,
			item_os,
			fecha_os,
			compra_peca,
			extrato,
			master,
			tecnico_posto,
			distrib_total,
			TO_CHAR(email_autenticado,'DD/MM/YYYY') as data_autenticacao
		FROM tbl_login_unico WHERE posto = {$posto_id} AND ativo IS TRUE";
	$res_usuarios_postos = pg_query($con, $sql_usuarios_posto);
?>
<!DOCTYPE html />
<html>
	<head>
		<meta http-equiv=pragma content=no-cache>
		<link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
		<link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
		<link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
		<link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />
		<link href="plugins/dataTable.css" type="text/css" rel="stylesheet" />

		<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
		<script src="bootstrap/js/bootstrap.js"></script>
		<script src="plugins/dataTable.js"></script>
		<script src="plugins/resize.js"></script>
		<script src="plugins/shadowbox_lupa/lupa.js"></script>
	</head>
	<style type="text/css">
		.titulo_coluna{
			background-color: #596d9b;
			font: bold 11px "Arial";
			color: #FFFFFF;
			text-align: center;
			padding: 5px 0 0 0;
		}
		.titulo_tabela{
			background-color: #596d9b;
			font: bold 16px "Arial";
			color: #FFFFFF;
			text-align: center;
			padding: 5px 0 0 0;
		}
	</style>
	<body style='background-color: #eeeeee'>
		<div class="alert alert-info">
 			<h3>Usuários com acesso ao sistema Telecontrol</h3>
 			<p>se não reconhece algum destes logins entre em contato com a Telecontrol pelos canais<br/>
 				Fone: <b>(11) 4063-4230</b> <br/>
 				E-mail: <b>suporte@telecontrol.com.br </b> <br/>
 				<b><a href="http://telecontrol.global/contato-chat/" target="_blank">Chat Telecontrol</a></b>
 			</p>
		</div>
		<?php if (pg_num_rows($res_usuarios_postos) > 0){ ?>
			<div id="container_lupa" style="overflow-y:auto;">
				<table id="resultado_os_atendimento" class='table table-striped table-bordered table-hover table-fixed' >
					<thead>
						<tr>
							<th colspan="4" class='titulo_tabela'>Usuários Cadastrados</th>
						</tr>
						<tr>
							<th>Nome</th>
							<th>Email</th>
							<th>Data Autenticação</th>
							<th>Permissões</th>
						</tr>
					</thead>
					<tbody>
						<?php 
							for ($z=0; $z < pg_num_rows($res_usuarios_postos); $z++) { 
								$nome 				= pg_fetch_result($res_usuarios_postos, $z, "nome");
								$email 				= pg_fetch_result($res_usuarios_postos, $z, "email");
								$data_autenticacao  = pg_fetch_result($res_usuarios_postos, $z, "data_autenticacao");
								
								$abre_os 			= pg_fetch_result($res_usuarios_postos, $z, "abre_os");
								$item_os 			= pg_fetch_result($res_usuarios_postos, $z, "item_os");
								$fecha_os 			= pg_fetch_result($res_usuarios_postos, $z, "fecha_os");
								$compra_peca 		= pg_fetch_result($res_usuarios_postos, $z, "compra_peca");
								$extrato 			= pg_fetch_result($res_usuarios_postos, $z, "extrato");
								$master 			= pg_fetch_result($res_usuarios_postos, $z, "master");
								$tecnico_posto 		= pg_fetch_result($res_usuarios_postos, $z, "tecnico_posto");
								$distrib_total 		= pg_fetch_result($res_usuarios_postos, $z, "distrib_total");
						?>		
							<tr>
								<td><?=$nome?></td>
								<td><?=$email?></td>
								<td style="text-align: center;"><?=$data_autenticacao?></td>
								<td>
									<?php 
										echo ($master == 't') ? "Usuário Master <br/>" : "";
										echo ($abre_os == 't') ? "Abre Ordem de Serviço <br/>" : "";
										echo ($item_os == 't') ? "Lança Item <br/>" : "";
										echo ($fecha_os == 't') ? "Fecha Ordem de Serviço <br/>" : "";
										echo ($compra_peca == 't') ? "Compra Peças <br/>" : "";
										echo ($extrato == 't') ? "Acessa Extrato <br/>" : "";
										echo ($tecnico_posto == 't') ? "Técnico Posto <br/>" : "";
									?>
								</td>
							</tr>
						<?php
							} 
						?>
					</tbody>
				</table>
			</div>
		<?php } ?>
	</body>
</html>
