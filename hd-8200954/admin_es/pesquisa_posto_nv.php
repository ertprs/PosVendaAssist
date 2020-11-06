<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';

header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");
// include 'cabecalho_pop_postos.php';

	$tipo		= trim (strtolower ($_REQUEST['tipo']));
	$codigo	= trim (strtolower ($_REQUEST['codigo']));
	$nome	= trim (strtolower ($_REQUEST['nome']));

	function verificaValorCampo($campo){
		return strlen($campo) > 0 ? $campo : "&nbsp;";
	}
?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
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
		<img src='../css/modal/excluir.png' alt='Fechar' class='lp_btn_fechar' />
	</a>
</div>
	<?
			echo "<div class='lp_nova_pesquisa'>";
				echo "<form action='".$_SERVER["PHP_SELF"]."' method='POST' name='nova_pesquisa'>";
					echo "<input type='hidden' name='forma' value='$forma' />";
					echo "<table cellspacing='1' cellpadding='2' border='0'>";
						echo "<tr>";
							echo "<td>
								<label>Código del Servicio</label>
								<input type='text' name='codigo' value='$codigo' style='width: 150px' maxlength='20' />
							</td>"; 
							echo "<td>
								<label>Nombre del Servicio</label>
								<input type='text' name='nome' value='$nome' style='width: 370px' maxlength='80' />
							</td>"; 
							echo "<td colspan='2' class='btn_acao' valign='bottom'><input type='submit' name='btn_acao' value='Pesquisar Novamente' /></td>"; 
						echo "</tr>";
					echo "</table>";
				echo "</form>";
			echo "</div>";

if (strlen($nome) > 2) {
		echo "<div class='lp_pesquisando_por'>Búsqueda por nombre: $nome</div>";
	
		$sql = "SELECT   
				tbl_posto.*, 
				tbl_posto_fabrica.codigo_posto, 
				tbl_posto_fabrica.credenciamento
			FROM     tbl_posto
				JOIN     tbl_posto_fabrica USING (posto)
			WHERE    (tbl_posto.nome ILIKE '%$nome%' OR tbl_posto.nome_fantasia ILIKE '%$nome%')
				AND      tbl_posto_fabrica.fabrica = $login_fabrica
				AND tbl_posto.pais = '$login_pais'
			ORDER BY tbl_posto.nome";

	}elseif (strlen($codigo) > 2) {
		$codigo_posto = trim (strtoupper($codigo));
		$codigo_posto = str_replace (".","",$codigo_posto);
		$codigo_posto = str_replace (",","",$codigo_posto);
		$codigo_posto = str_replace ("-","",$codigo_posto);
		$codigo_posto = str_replace ("/","",$codigo_posto);

		echo "<div class='lp_pesquisando_por'>Pesquisando por código: $codigo</div>";
		
		$sql = "SELECT
				tbl_posto.*, 
				tbl_posto_fabrica.codigo_posto, 
				tbl_posto_fabrica.credenciamento
			FROM tbl_posto
				JOIN tbl_posto_fabrica USING (posto)
			WHERE tbl_posto_fabrica.codigo_posto ILIKE '%$codigo_posto%'
				AND tbl_posto_fabrica.fabrica = $login_fabrica
				AND tbl_posto.pais = '$login_pais'
			ORDER BY tbl_posto.nome";
	}else{
		echo "<div class='lp_msg_erro'>Informar a todas o parte de la información para realizar la búsqueda!</div>";
		exit;
	}

$res = pg_query($con, $sql);
	if (pg_numrows ($res) > 0 ) {?>
		<table width='100%' border='0' cellspacing='1' cellspading='0' class='lp_tabela' id='gridRelatorio'>
			<thead>
				<tr>
					<th>Código</th>
					<th>CNPJ</th>
					<th>Nombre</th>
					<th>Cidade</th>
					<th>Estado</th>
				</tr>
			</thead>
			<tbody>
				<?
				for ($i = 0 ; $i < pg_num_rows($res); $i++) {
					$credenciamento	= pg_result($res,$i,credenciamento);
					$codigo_posto	=trim(pg_result($res,$i,codigo_posto));
					$posto		= trim(pg_result($res,$i,posto));
					$nome		= trim(pg_result($res,$i,nome));
					$cnpj			= trim(pg_result($res,$i,cnpj));
					$cidade		= trim(pg_result($res,$i,cidade));
					$estado		= trim(pg_result($res,$i,estado));
					$nome		= str_replace("'", "\'", $nome);

					if(pg_num_rows($res) == 1){
						echo "<script type='text/javascript'>";
							echo "window.parent.retorna_posto('$codigo_posto','$posto','$nome','$cnpj','$cidade','$estado','$nome','$credenciamento'); window.parent.Shadowbox.close();";
						echo "</script>";
						exit;
					}

					$onclick = "onclick= \"javascript: window.parent.retorna_posto('$codigo_posto','$posto','$nome','$cnpj','$cidade','$estado','$nome','$credenciamento'); window.parent.Shadowbox.close();\"";

					$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
					echo "<tr style='background: $cor' $onclick>";
						echo "<td>".verificaValorCampo($codigo_posto)."</td>";
						echo "<td>".verificaValorCampo($cnpj)."</td>";
						echo "<td>".verificaValorCampo($nome)."</td>";
						echo "<td>".verificaValorCampo($cidade)."</td>";
						echo "<td>".verificaValorCampo($estado)."</td>";
					echo "</tr>";
				}
			echo "</tbody>";
		echo "</table>";
	}else{
		echo "<div class='lp_msg_erro'>No se encontraron resultados</div>";
	}?>

</body>
</html>
