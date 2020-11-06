<?php
	include "dbconfig.php";
	include "includes/dbconnect-inc.php";
	include 'autentica_usuario.php';

	header("Expires: 0");
	header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
	header("Pragma: no-cache, public");

	
	$posicao    = @$_REQUEST['posicao'];
	$pesquisa   = trim(strtolower(@$_REQUEST['pesquisa']));
	$btn_acao   = trim(@$_REQUEST['btn_acao']);
	$tipo       = trim(@$_REQUEST['tipo']);
	$referencia = trim(strtolower(@$_REQUEST['referencia']));
	$descricao  = trim(strtolower(@$_REQUEST['descricao']));

	if(strlen($referencia) > 0)
		$tipo = "referencia";
	else
		$tipo = "descricao";
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd"> 
<html>
	<head>
		<meta http-equiv=pragma content=no-cache>
		<link rel="stylesheet" type="text/css" href="css/lupas/lupas.css" />
		<script type="text/javascript" src="js/jquery-1.6.1.min.js"></script>
		<script src="plugins/jquery/jquery.tablesorter.min.js"	type="text/javascript"></script>
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
		<style type='text/css'>
			html, body{
				background: #FFF;
			}
		</style>
	</head>

	<body>
		<div class="lp_header" style='background-image: url(imagens/lupas/bg_lupa_2.jpg);'>
			<div class='lp_btn_fechar'>
				<a href='javascript:window.parent.Shadowbox.close();' style='border: 0;'>
					<img src='css/modal/excluir.png' alt='Fechar'  />
				</a>
			</div>
		</div>
		<div class="lp_nova_pesquisa">
			<form method="POST" name="nova_pesquisa" action="<?=$_SERVER['PHP_SELF']?>">
				<input name="pesquisa" type="hidden" value="" />
				<input name="posicao"  type="hidden" value="<?=$posicao?>" />
				<table cellspacing='1' cellpadding='2' border='0'>
					<tr>
						<td>
							<label for="ref"><?=traduz('referencia', $con)?></label>
							<input id="ref" maxlength="20" name="referencia" style="width:150px" type="text" value="<?=$referencia?>" />
						</td>
						<td>
							<label for="desc"><?=traduz('descricao', $con)?></label>
							<input id="desc" maxlength="80" name="descricao" style="width:370px" type="text" value="<?=$descricao?>" />
						</td>
						<td class="btn_acao" colspan="2" valign="bottom">
							<button name="btn_acao"><?=traduz('pesquisar.novamente', $con)?></button>
						</td>
					</tr>
				</table>
			</form>
		</div>

		<?php

			if (strlen($tipo) > 0) {
				if($tipo == 'referencia'){
					echo "<div class='lp_pesquisando_por'>" . traduz('pesquisando.pela.referencia', $con, $cook_idioma, array($referencia)) . "</div>";
					$sql = "SELECT 
							tbl_peca.referencia,
							tbl_peca.descricao,
							tbl_marca.marca,
							tbl_marca.codigo_marca || ' - ' || tbl_empresa.descricao as empresa_peca
							FROM tbl_peca
							LEFT JOIN tbl_marca ON tbl_marca.marca = tbl_peca.marca
						LEFT JOIN tbl_empresa ON tbl_marca.empresa = tbl_empresa.empresa
						WHERE 
							LOWER(referencia_pesquisa) LIKE '%$referencia%'
							AND tbl_peca.fabrica = $login_fabrica
						AND tbl_peca.ativo
						ORDER BY tbl_peca.referencia ASC;";
				}else{
					echo "<div class='lp_pesquisando_por'>" . traduz('pesquisando.pela.descricao', $con, $cook_idioma, array($descricao)) . "</div>";
					$sql = "SELECT 
							tbl_peca.referencia,
							tbl_peca.descricao,
							tbl_marca.marca,
							tbl_marca.codigo_marca || ' - ' || tbl_empresa.descricao as empresa_peca
						FROM tbl_peca
						LEFT JOIN tbl_marca ON tbl_marca.marca = tbl_peca.marca
						LEFT JOIN tbl_empresa ON tbl_marca.empresa = tbl_empresa.empresa
						WHERE 
							LOWER(tbl_peca.descricao) LIKE '%$descricao%'
							AND tbl_peca.fabrica = $login_fabrica
						AND tbl_peca.ativo
						ORDER BY tbl_peca.referencia ASC;";
				}
				//echo $sql;
				$res = pg_query($con,$sql);

				if (pg_num_rows($res) > 0) {
					echo "<table width='100%' border='0' cellspacing='1' cellspading='0' class='lp_tabela'  id='gridRelatorio'>";
						echo "<thead>";
							echo "<tr>";
								echo "<th>" . traduz('referencia', $con) . "</th>";
								echo "<th>" . traduz('descricao.da.peca', $con) . "</th>";
								echo "<th>" . traduz('Empresa da Peça') . "</th>";
							echo "</tr>";
						echo "</thead>"; 
						echo "<tbody>"; 
						for ($i = 0; $i < pg_numrows($res); $i++) {
							$peca_referencia = trim(pg_fetch_result($res, $i, 'referencia'));
							$peca_descricao  = trim(pg_fetch_result($res, $i, 'descricao'));
							$marca           = pg_fetch_result($res, $i, 'marca');
							$empresa_peca    = pg_fetch_result($res, $i, 'empresa_peca');

							//limpas as ASPAS para entrar no JS
							$peca_descricao_js = preg_replace("/(\"|')/","\\$1",$peca_descricao);
							$peca_descricao_js = str_replace("'","",$peca_descricao_js);

							$onclick = "onclick= \"javascript: window.parent.retorna_peca('$peca_referencia','$peca_descricao_js','$posicao','$marca'); window.parent.Shadowbox.close();\"";

							$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
							echo "<tr style='background: $cor' $onclick>";
								echo "<td>$peca_referencia</td>";
								echo "<td>$peca_descricao</td>";
								echo "<td>$empresa_peca</td>";
							echo "</tr>";
						}
						echo "</tbody>"; 
				}else
					echo "<div class='lp_msg_erro'>" . traduz('nenhum.resultado.encontrado', $con) . "</div>";
			}
	echo "</body>";
echo "</html>";

