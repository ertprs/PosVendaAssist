<?php
	include "dbconfig.php";
	include "includes/dbconnect-inc.php";
	include "autentica_usuario.php";

	$dir = '/var/www/cgi-bin/latinatec/entrada/';

	$referencia	= trim($_REQUEST["referencia"]);
	$descricao	= trim($_REQUEST["descricao"]);
	$voltagem	= trim($_REQUEST["voltagem"]);
	$posicao	= trim($_REQUEST["posicao"]);

	function verificaValorCampo($campo){
		return strlen($campo) > 0 ? $campo : "&nbsp;";
	}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
	<head>
		<meta http-equiv=pragma content=no-cache>
		<style type="text/css">
			body {
				margin: 0;
				font-family: Arial, Verdana, Times, Sans;
				background: #fff;
			}
		</style>
		<script type="text/javascript" src="js/jquery-1.4.2.js"></script>
		<script src="plugins/jquery/jquery.tablesorter.min.js" type="text/javascript"></script>
		<link rel="stylesheet" type="text/css" href="css/lupas/lupas.css">
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
			<a href='javascript:window.parent.Shadowbox.close();' style='border: 0;'><img src='css/modal/excluir.png' alt='Fechar' class='lp_btn_fechar' /></a>
		</div><?php

		$file = $dir.'pagamento_ordem_serv.txt';
		$vet  = array();

		if (file_exists($file)) {

			$arquivo = file($file);
			
			for ($i = 0; $i < count($arquivo); $i++) {

				$new_vet = explode(';', $arquivo[$i]);

				if ($login_cnpj == $new_vet[2]) {
					$vet[] = $arquivo[$i];
				}

			}

		}

		if (!empty($vet)) {?>

			<br />
			<br />
			<table width='100%' border='0' cellspacing='1' cellspading='0' class='lp_tabela' id='gridRelatorio'>
				<thead>
					<tr>
						<th>Número Nota Fiscal</th>
						<th>Data emissão</th>
						<th>Valor Nota Fiscal</th>
						<th>Data pagamento</th>
					</tr>
				</thead>
				<tbody><?php

				for ($x = 0 ; $x < count($vet); $x++) {

					$new_vet = explode(';', $vet[$x]);

					$data_pagamento = date('d/m/Y', strtotime($new_vet[0]));
					$data_emissao   = date('d/m/Y', strtotime($new_vet[3]));
					$numero_nf      = $new_vet[1];
					$valor_nf       = number_format($new_vet[4], 2, ',', '.');

					$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

					echo "<tr style='background: $cor'>";
						echo "<td>".verificaValorCampo($numero_nf)."</td>";
						echo "<td>".verificaValorCampo($data_emissao)."</td>";
						echo "<td>".verificaValorCampo($valor_nf)."</td>";
						echo "<td>".verificaValorCampo($data_pagamento)."</td>";
					echo "</tr>";

				}

				echo "</tbody>";
			echo "</table>";

		} else {
			echo "<div class='lp_msg_erro'>Nehum resultado encontrado</div>";
		}?>
	</body>
</html>
