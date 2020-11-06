<?php
	include "dbconfig.php";
	include "includes/dbconnect-inc.php";

	$tipo          = trim($_GET["tipo"]);
	$valor 		   = trim($_GET["valor"]);
	$login_fabrica = trim($_GET["fabrica"]);
	$msg_erro      = "";

	if (empty($tipo)) {
		$msg_erro = "Erro ao localizar os ténicos esporádicos";
	} else {
		if (empty($valor)) {
			$sql = "SELECT tecnico, codigo_externo, nome, cidade, estado FROM tbl_tecnico WHERE fabrica = $login_fabrica AND ativo IS TRUE ORDER BY estado, cidade, nome";
		} else {
			if ($tipo == "codigo") {
				$sql = "SELECT tecnico, codigo_externo, nome, cidade, estado FROM tbl_tecnico WHERE fabrica = $login_fabrica AND codigo_externo = '$valor' ORDER BY estado, cidade, nome";
			} else if ($tipo == "nome") {
				$sql = "SELECT tecnico, codigo_externo, nome, cidade, estado FROM tbl_tecnico WHERE fabrica = $login_fabrica AND nome = '$valor' ORDER BY estado, cidade, nome";
			} else {
				$msg_erro = "Erro ao identificar o ténico esporádico";
			}
		}

		if (empty($msg_erro)) {
			$res  = pg_query($con, $sql);
			$rows = pg_num_rows($res);

			if ($rows <= 0) {
				$msg_erro = "Não foi localizado nenhum técnico esporádico relacionado com \"$valor\"";
			} else {
				$dados = array();

				for ($i = 0; $i < $rows; $i++) { 
					$tecnico_id     = pg_fetch_result($res, $i, 'tecnico');
					$codigo_externo = pg_fetch_result($res, $i, 'codigo_externo');
					$nome           = pg_fetch_result($res, $i, 'nome');
					$cidade         = pg_fetch_result($res, $i, 'cidade');
					$estado 	    = pg_fetch_result($res, $i, 'estado');

					$dados[$i]["tecnico_id"]     = $tecnico_id;
					$dados[$i]["codigo_externo"] = $codigo_externo;
					$dados[$i]["nome"] 			 = $nome;
					$dados[$i]["cidade"] 	  	 = $cidade;
					$dados[$i]["estado"]  		 = $estado;
				}
			}
		}
	}
?>

<script type="text/javascript">
	function enviar_dados (tecnico_id, codigo, nome) {
		window.parent.retorna_tecnico_esporadico(tecnico_id, codigo, nome);
		window.parent.Shadowbox.close();
	}
</script>

<!DOCTYPE HTML public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
	<title> Pesquisa Produto... </title>
	<meta name="Author" content="">
	<meta name="Keywords" content="">
	<meta name="Description" content="">
	<meta http-equiv=pragma content=no-cache>
	<link href="css/estilo_cad_prod.css" rel="stylesheet" type="text/css">
	<link href="css/posicionamento.css" rel="stylesheet" type="text/css">
	<link rel="stylesheet" type="text/css" href="js/thickbox.css" media="screen">
	<script src="js/jquery-1.3.2.js"	type="text/javascript"></script>
	<script src="js/thickbox.js"		type="text/javascript"></script>
	<script src="../plugins/jquery/jquery.tablesorter.min.js" type="text/javascript"></script>
	<style type="text/css">
		@import "../css/lupas/lupas.css";
		body {
			margin: 0;
			font-family: Arial, Verdana, Times, Sans;
			background: #fff;
		}
	</style>
	<script type="text/javascript">
		$(document).ready(function() {
		$("#gridRelatorio").tablesorter();
	});
	</script>
</head>
<body>
	<div class="lp_header">
		<a href='' onclick='window.parent.Shadowbox.close();' style='border: 0;'>
			<img src='css/modal/excluir.png' alt='Fechar' class='lp_btn_fechar' />
		</a>
	</div>

	<?php 
		if (!empty($msg_erro)) {
			?> <div class="lp_msg_erro"> <?=$msg_erro;?> </div> <?php
		}
		
		if (!empty($dados)) { ?>
			<br>
			<table style='width:100%; border: 0;' cellspacing='1' class='lp_tabela' id='gridRelatorio'>
				<thead>
					<tr>
						<th colspan="4">Consulta de Ténicos Esporádicos</th>
					</tr>
					<tr>
						<th>Código</th>
						<th>Nome</th>
						<th>Cidade</th>
						<th>Estado</th>
					</tr>
				</thead>
				<tbody>
					<?php
						foreach ($dados as $key => $tecnico) {
							$tecnico_id     = $tecnico["tecnico_id"];
							$codigo_externo = $tecnico["codigo_externo"];
							$nome           = $tecnico["nome"];

							$parametos = "'$tecnico_id', '$codigo_externo', '$nome'";
					?>
							<tr style='background: #F1F4FA'>
								<td style='display: none;'> <?=$tecnico_id;?> </td>
								<td style='text-align: left;' onclick="enviar_dados(<?=$parametos;?>);"> <?=$codigo_externo;?> </td>
								<td style='text-align: left;' onclick="enviar_dados(<?=$parametos;?>);"> <?=$nome;?> </td>
								<td style='text-align: left;' onclick="enviar_dados(<?=$parametos;?>);"> <?=$tecnico["cidade"];?> </td>
								<td style='text-align: center;'> <?=$tecnico["estado"];?> </td>
							</tr>
					<?php } ?>
				</tbody>
			</table>
	<?php } ?>
</body>