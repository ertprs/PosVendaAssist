<?
	include "dbconfig.php";
	include "includes/dbconnect-inc.php";
	include 'autentica_admin.php';


	$referencia	= trim($_REQUEST["referencia"]);
	$descricao	= trim($_REQUEST["descricao"]);
    $posicao    = trim($_REQUEST["posicao"]);
	$item       = trim($_REQUEST["item"]);
	if($login_fabrica == 1){
		$status 	= trim($_REQUEST["status"]);
	}

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
				echo "<form action='$PHP_SELF' method='POST' name='nova_pesquisa'>";
					echo "<input type='hidden' name='posicao' value='$posicao' />";
					echo "<table cellspacing='1' cellpadding='2' border='0'>";
						echo "<tr>";
							echo "<td>
								<label>Refêrencia</label>
								<input type='text' name='referencia' value='$referencia' style='width: 150px' maxlength='20' />
							</td>";
							echo "<td>
								<label>Descrição</label>
								<input type='text' name='descricao' value='$descricao' style='width: 370px' maxlength='80' />
							</td>";
							echo "<td colspan='2' class='btn_acao' valign='bottom'><input type='submit' name='btn_acao' value='Pesquisar Novamente' /></td>";
						echo "</tr>";
					echo "</table>";
				echo "</form>";
			echo "</div>";
			if($login_fabrica == 1 and $status == 'indispl' ){
				$cond_status_peca = " AND tbl_peca.informacoes = upper('$status') ";
			}

			if (strlen($referencia) > 2) {
				echo "<div class='lp_pesquisando_por'>Pesquisando pela referência: $referencia</div>";

				$referencia = strtoupper($referencia);
				$referencia = str_replace (".","",$referencia);
				$referencia = str_replace ("-","",$referencia);
				$referencia = str_replace ("/","",$referencia);
				$referencia = str_replace (" ","",$referencia);

				//hd-3625122 - fputti
				$condReferenciaFabrica = "";
				if ($login_fabrica == 171) {
					$condReferenciaFabrica = " OR tbl_peca.referencia_fabrica ILIKE '%$referencia%'";
				}

				$sql = "SELECT
						tbl_peca.peca		,
						tbl_peca.referencia	,
						tbl_peca.referencia_fabrica	,
						tbl_peca.descricao	,
						tbl_peca.ipi			,
						tbl_peca.origem		,
						tbl_peca.estoque		,
						tbl_peca.unidade		,
						tbl_peca.marca          ,
						tbl_peca.ativo
					FROM tbl_peca
					JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_peca.fabrica
				   WHERE (tbl_peca.referencia_pesquisa ILIKE '%$referencia%' 
						 $condReferenciaFabrica)
					 AND tbl_peca.fabrica = $login_fabrica
						$cond_status_peca
					ORDER BY tbl_peca.descricao;";

			}elseif(strlen($descricao) > 2){
				$descricao = str_replace ("\\\\\\","\\\\\\\\",$descricao);

				$sql = "SELECT
						tbl_peca.peca		,
						tbl_peca.referencia	,
						tbl_peca.referencia_fabrica	,
						tbl_peca.descricao	,
						tbl_peca.ipi			,
						tbl_peca.origem		,
						tbl_peca.estoque		,
						tbl_peca.unidade		,
						tbl_peca.marca          ,
						tbl_peca.ativo
					FROM tbl_peca
						JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_peca.fabrica
					WHERE tbl_peca.descricao ILIKE '%$descricao%'
						AND tbl_peca.fabrica = $login_fabrica
						$cond_status_peca
					ORDER BY tbl_peca.descricao;";
			}else
				$msg_erro = "Informar toda ou parte da informação para realizar a pesquisa!";

			if(strlen($msg_erro) > 0){
				echo "<div class='lp_msg_erro'>$msg_erro</div>";
			}else{
				$res = pg_exec ($con,$sql);

				if (@pg_numrows ($res) > 0) {?>
					<table width='100%' border='0' cellspacing='1' cellspading='0' class='lp_tabela' id='gridRelatorio'>
						<thead>
							<tr>
								<?php
									//hd-3625122 - fputti
									if ($login_fabrica == 171) {
										echo "<th  width='20%'>Referência FN</th>";
									}

									//hd-6614402 - Gestão
									if ($telecontrol_distrib) {
										echo "<th  width='20%'>Situação</th>";
									}
								?>
								<th width="20%">Código</th>
								<th width="40%">Descrição</th>
								<?php if ($login_fabrica == 87) { ?>
									<th>Empresa</th>
								<?php } ?>
							</tr>
						</thead>
						<tbody><?
							for ($i = 0 ; $i < pg_num_rows($res); $i++) {
								$peca	= trim(pg_result($res, $i, 'peca'));
								$referencia	= trim(pg_result($res, $i, 'referencia'));
								$referencia	= preg_replace("/'/","",$referencia);
								$descricao	= trim(pg_result($res, $i, 'descricao'));
								$descricao      = mb_detect_encoding($descricao,'UTF-8',true) ? utf8_decode($descricao) : $descricao;
								$descricao	= preg_replace("/'/","",$descricao);
								$ipi		= trim(pg_result($res, $i, 'ipi'));
								$origem	    = trim(pg_result($res, $i, 'origem'));
								$estoque	= trim(pg_result($res, $i, 'estoque'));
								$marca      = pg_result($res, $i, 'marca');
								$unidade	= trim(pg_result($res, $i, 'unidade'));

								if ($login_fabrica == 87 && !empty($marca)) {
									$sql_emp = "SELECT tbl_empresa.descricao FROM tbl_marca JOIN tbl_empresa USING(empresa) WHERE marca = $marca AND tbl_marca.fabrica = $login_fabrica";
									$res_emp = pg_query($con, $sql_emp);
									$desc_emp = pg_fetch_result($res_emp, 0, 'descricao');
								}

								//hd-3625122 - fputti
								if ($login_fabrica == 171) {
									$referencia_fabrica = trim(pg_result($res, $i, 'referencia_fabrica'));
								}

								$ativo	= trim(pg_result($res, $i, 'ativo'));

								if(pg_num_rows($res) == 1){
									echo "<script type='text/javascript'>";
										echo "window.parent.retorna_dados_peca('$peca','$referencia','".htmlentities($descricao)."','$ipi','$origem','$estoque','$unidade','$ativo','$posicao','$item'); window.parent.Shadowbox.close();";
									echo "</script>";
								}

								$onclick = "onclick= \"javascript: window.parent.retorna_dados_peca('$peca','$referencia','".htmlentities($descricao)."','$ipi','$origem','$estoque','$unidade','$ativo','$posicao','$item'); window.parent.Shadowbox.close();\"";

								$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
								echo "<tr style='background: $cor' $onclick>";
									//hd-3625122 - fputti
									if ($login_fabrica == 171) {
										echo "<td>".verificaValorCampo($referencia_fabrica)."</td>";
									}

									//hd-6614402 Gestão
									if ($telecontrol_distrib) {
										$desc_ativo = ($ativo == "t") ? "Ativo" : "Inativo";
										echo "<td>".verificaValorCampo($desc_ativo)."</td>";
									}

									echo "<td>".verificaValorCampo($referencia)."</td>";
									echo "<td>".verificaValorCampo($descricao)."</td>";

									if ($login_fabrica == 87) {
										echo "<td>".verificaValorCampo($desc_emp)."</td>";
									}

								echo "</tr>";
							}
				}else
					echo "<div class='lp_msg_erro'>Nehum resultado encontrado</div>";
			}?>
	</body>
</html>
