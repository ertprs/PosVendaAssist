<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';

	function verificaValorCampo($campo){
		return strlen($campo) > 0 ? $campo : "&nbsp;";
	}

	$posicao = trim($_REQUEST["posicao"]);
	$xserie = trim (strtoupper($_REQUEST["serie"]));
	$serie = substr($xserie,0,3);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
	<head>
		<meta http-equiv='pragma' content='no-cache'>
		<style type="text/css">
			body {
				margin: 0;
				font-family: Arial, Verdana, Times, Sans;
				background: #fff;
			}
		</style>
		<script type="text/javascript" src="../js/jquery-1.4.2.js"></script>
		<script src="../plugins/jquery/jquery.tablesorter.min.js" type="text/javascript"></script>
		<link rel="stylesheet" type="text/css" href="../css/lupas/lupas.css">
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
				<img src='css/modal/excluir.png' alt='Fechar' class='lp_btn_fechar' />
			</a>
		</div>
		<?
			echo "<div class='lp_nova_pesquisa'>";
				echo "<form action='".$_SERVER["PHP_SELF"]."' method='POST' name='nova_pesquisa'>";
					echo "<input type='hidden' name='posicao' value='$posicao' />";
					echo "<table cellspacing='1' cellpadding='2' border='0'>";
							echo "<td>
								<label>Serie</label>
								<input type='text' name='serie' value='$xserie' style='width: 370px' maxlength='80' />
							</td>"; 
							echo "<td colspan='2' class='btn_acao' valign='bottom'><input type='submit' name='btn_acao' value='Pesquisar Novamente' /></td>"; 
						echo "</tr>";
					echo "</table>";
				echo "</form>";
			echo "</div>";

	if(strlen($xserie) > 0){
		echo "<div class='lp_pesquisando_por'>Pesquisando por número de série: $serie</div>";
		$sql = "SELECT 
				referencia	,
				descricao	,
				radical_serie
			FROM tbl_produto
				JOIN tbl_linha ON tbl_produto.linha = tbl_linha.linha
			WHERE tbl_linha.fabrica = $login_fabrica
				AND tbl_produto.ativo
	
			ORDER BY tbl_produto.descricao;";

		$res = pg_exec ($con,$sql);
	}else
		$msg_erro = "Informar toda ou parte da informação para realizar a pesquisa!";

	if(strlen($msg_erro) > 0){
		echo "<div class='lp_msg_erro'>$msg_erro</div>";
	}else{

		$res = pg_query($con, $sql);
		if (pg_numrows ($res) > 0 ) {?>
			<table width='100%' border='0' cellspacing='1' cellspading='0' class='lp_tabela' id='gridRelatorio'>
				<thead>
					<tr>
						<th>Referência</th>
						<th>Descrição</th>
						<th>Serie</th>
					</tr>
				</thead>
				<tbody>
					<?
					for ($i = 0 ; $i < pg_num_rows($res); $i++) {
						$referencia		= trim(pg_result($res,$i,referencia));
						$descricao		= trim(pg_result($res,$i,descricao));
						$descricao		= str_replace ('"','',$descricao);
						$serie			= trim(pg_result($res,$i,radical_serie));

						if(pg_num_rows($res) == 1){
							echo "<script type='text/javascript'>";
								echo "window.parent.retorna_serie('$referencia','$descricao','$serie','$posicao'); window.parent.Shadowbox.close();";
							echo "</script>";
							exit;
						}

						$onclick = "onclick= \"javascript: window.parent.retorna_serie('$referencia','$descricao','$serie','$posicao'); window.parent.Shadowbox.close();\"";

						$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
						echo "<tr style='background: $cor' $onclick>";
							echo "<td>".verificaValorCampo($referencia)."</td>";
							echo "<td>".verificaValorCampo($descricao)."</td>";
							echo "<td>".verificaValorCampo($serie)."</td>";
						echo "</tr>";
					}
				echo "</tbody>";
			echo "</table>";
		}else
			echo "<div class='lp_msg_erro'>Nehum resultado encontrado</div>";
	}?>
	</body>
</html>
