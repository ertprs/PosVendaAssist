<?php
	include "/etc/telecontrol.cfg";
	include "dbconfig.php";
	include "includes/dbconnect-inc.php";
	include 'autentica_usuario.php';

	$serie		= trim($_REQUEST["serie"]);
	$posicao	= trim($_REQUEST["posicao"]);

	if ($login_fabrica == 1) {

		$programa_troca = $_REQUEST['exibe'];

		if (preg_match("os_cadastro_troca.php", $programa_troca)) {
			$troca_produto = 't';
		}

		if (preg_match("os_revenda_troca.php", $programa_troca)) {
			$revenda_troca = 't';
		}

		if (preg_match("os_cadastro.php", $programa_troca)) {
			$troca_obrigatoria_consumidor = 't';
		}

		if (preg_match("os_revenda.php", $programa_troca)) {
			$troca_obrigatoria_revenda = 't';
		}

	}

	function verificaValorCampo($campo){
		return strlen($campo) > 0 ? $campo : "&nbsp;";
	}
?>
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
			<a href='javascript:window.parent.Shadowbox.close();' style='border: 0;'>
				<img src='css/modal/excluir.png' alt='Fechar' class='lp_btn_fechar' />
			</a>
		</div><?
		echo "<div class='lp_nova_pesquisa'>";
			echo "<form action='$PHP_SELF' method='POST' name='nova_pesquisa'>";
				echo "<input type='hidden' name='produto' value='$produto' />";
				echo "<input type='hidden' name='posicao' value='$posicao' />";

				echo "<table cellspacing='1' cellpadding='2' border='0'>";
					echo "<tr>";
						echo "<td>
							<label>".traduz("numero.de.serie",$con,$cook_idioma)."</label>
							<input type='text' name='serie' value='$serie' style='width: 500px' maxlength='20' />
						</td>";
						echo "<td colspan='2' class='btn_acao' valign='bottom'><input type='submit' name='btn_acao' value='Pesquisar Novamente' /></td>";
					echo "</tr>";
				echo "</table>";
			echo "</form>";
		echo "</div>";

		if ($login_fabrica == 11) {
			$sql_abre_os = " AND tbl_produto.abre_os IS TRUE ";
		}

		if(strlen($serie) > 2){
			echo "<div class='lp_pesquisando_por'>Pesquisando por número de série do produto: $serie</div>";

			if($login_fabrica==50){
				$sql = "
					SELECT
						tbl_produto.produto      ,
						tbl_produto.referencia   ,
						tbl_produto.descricao    ,
						tbl_produto.radical_serie,
						tbl_revenda.cnpj,
						tbl_revenda.nome,
						tbl_revenda.fone,
						tbl_revenda.email
					FROM 	tbl_produto
					JOIN    tbl_numero_serie ON tbl_numero_serie.produto = tbl_produto.produto AND tbl_numero_serie.fabrica = $login_fabrica
					JOIN	tbl_revenda on (tbl_numero_serie.cnpj = tbl_revenda.cnpj)
					WHERE
						tbl_produto.fabrica_i = $login_fabrica
						AND tbl_produto.ativo
						AND tbl_numero_serie.serie = '$serie'
					ORDER BY tbl_produto.descricao;";
			}else if(in_array($login_fabrica, array(74,120,201,165,167,203))){

				if (in_array($login_fabrica, [167,203])) {
					$joinLinha = "JOIN tbl_posto_linha ON tbl_posto_linha.linha = tbl_produto.linha
								  AND  tbl_posto_linha.posto = {$login_posto}";
				}

				$sql = "
					SELECT
						tbl_produto.produto      ,
						tbl_produto.referencia   ,
						tbl_produto.descricao    ,
						tbl_numero_serie.serie   ,
						tbl_numero_serie.data_fabricacao
					FROM 	tbl_produto
					JOIN    tbl_numero_serie ON tbl_numero_serie.produto = tbl_produto.produto AND tbl_numero_serie.fabrica = $login_fabrica
					{$joinLinha}
					WHERE
						tbl_produto.fabrica_i = $login_fabrica
						AND tbl_produto.ativo
						AND tbl_numero_serie.serie = '$serie'
					ORDER BY tbl_produto.descricao;";
			}else{

				$xserie = substr($serie,0,3);
				$sql = "
					SELECT
						tbl_produto.produto      ,
						tbl_produto.referencia   ,
						tbl_produto.descricao    ,
						tbl_produto.radical_serie,
						tbl_produto.voltagem
					FROM	tbl_produto
					WHERE
						tbl_produto.fabrica_i = $login_fabrica
						AND tbl_produto.ativo
						AND tbl_produto.radical_serie ILIKE '$xserie%'
						$sql_abre_os
					ORDER BY
						tbl_produto.descricao;";
			}
			//echo nl2br($sql);
			$res = pg_exec ($con,$sql);

			if (pg_numrows ($res) == 1) {
				$produto		= pg_fetch_result($res,0,produto);
				$referencia		= pg_fetch_result($res,0,referencia);
				$descricao		= pg_fetch_result($res,0,descricao);
				$voltagem       = pg_fetch_result($res,0,voltagem);
				if ($login_fabrica==50){
					$cnpj_revenda  = pg_result($res,$i,'cnpj');
					$nome_revenda  = pg_result($res,$i,'nome');
					$email_revenda = pg_result($res,$i,'email');
					$fone_revenda  = pg_result($res,$i,'fone');
				}

				if(in_array($login_fabrica,array(74,165))){
					$data_fabricacao       = pg_fetch_result($res,0,data_fabricacao);
					$data_fabricacao = ",'" . $data_fabricacao . "'";
				}
				if ($login_fabrica == 6)
				{
					$voltagem = ",'" . $voltagem . "'";
				}

				if($login_fabrica == 120){
					$data_fabricacao       = pg_fetch_result($res,0,data_fabricacao);
					$data_fabricacao = ",'" . $data_fabricacao . "'";
				}

				echo "<script type='text/javascript'>";

					echo "window.parent.retorna_numero_serie('$produto','$referencia','$descricao','$posicao','$cnpj_revenda','$nome_revenda','$fone_revenda','$email_revenda','$serie','$voltagem' $data_fabricacao); window.parent.Shadowbox.close();";
				echo "</script>";
			}elseif(pg_num_rows ($res) > 1){

			?>

				<table width='100%' border='0' cellspacing='1' cellpadding='0' class='lp_tabela' id='gridRelatorio'>
					<thead>
						<tr>
							<th>Referência</th>
							<th>Descrição</th>
							<th>Série</th>
						<?if ($login_fabrica==50){?>
							<th>Revenda CNPJ</th>
							<th>Revenda Nome</th>
						<?}?>
						</tr>
					</thead>
					<tbody><?
						for ($i = 0 ; $i < pg_num_rows($res); $i++) {
							$produto		= pg_fetch_result($res,$i,produto);
							$referencia		= pg_fetch_result($res,$i,referencia);
							$descricao		= pg_fetch_result($res,$i,descricao);
							$radical_serie	= pg_fetch_result($res,$i,radical_serie);
							$voltagem       = pg_fetch_result($res,$i,voltagem);

							if ($login_fabrica==50){
								$cnpj_revenda  = pg_result($res,$i,'cnpj');
								$nome_revenda  = pg_result($res,$i,'nome');
								$email_revenda = pg_result($res,$i,'email');
								$fone_revenda  = pg_result($res,$i,'fone');

							}

							if ($login_fabrica == 50){
								$msg_confirma = "if (confirm('Atenção! Mais de um produto com o mesmo número de série.\\n Verifique se o produto selecionado está correto.')){";
								$chave = " }";
							}

							if($login_fabrica == 74){
								$data_fabricacao       = pg_fetch_result($res,$i,data_fabricacao);
								$data_fabricacao = ",'" . $data_fabricacao . "'";
							}

							if ($login_fabrica == 6)
							{
								$voltagem = ",'" . $voltagem . "'";
							}

							$onclick = "onclick= \"javascript: $msg_confirma window.parent.retorna_numero_serie('$produto','$referencia','$descricao','$posicao','$cnpj_revenda','$nome_revenda','$fone_revenda','$email_revenda','$serie', '$voltagem' $data_fabricacao); window.parent.Shadowbox.close(); $chave\"";


							if($login_fabrica==50) $radical_serie = $serie;

							$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
							echo "<tr style='background: $cor' $onclick>";
								echo "<td>".verificaValorCampo($referencia)."</td>";
								echo "<td>".verificaValorCampo($descricao)."</td>";
								echo "<td>".verificaValorCampo($radical_serie)."</td>";
								if ($login_fabrica == 50){
									echo "<td>".verificaValorCampo($cnpj_revenda)."</td>";
									echo "<td>".verificaValorCampo($nome_revenda)."</td>";
								}
							echo "</tr>";
						}
					echo "</tbody>";
				echo "</table>";
			}else
				echo "<div class='lp_msg_erro'>Nehum resultado encontrado</div>";
		}else
			$msg_erro = "Informar toda ou parte da informação para realizar a pesquisa!";?>
	</body>
</html>
