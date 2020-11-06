<?php
	include "dbconfig.php";
	include "includes/dbconnect-inc.php";
	include 'autentica_admin.php';
	
	$serie		= trim($_REQUEST["serie"]);
	$posicao	= trim($_REQUEST["posicao"]);
	$produto	= trim($_REQUEST["produto"]);
	$id_posto 	= trim($_REQUEST['id_posto']);

	$sql_posto = "SELECT tbl_tipo_posto.posto_interno
					FROM tbl_tipo_posto
					JOIN tbl_posto_fabrica ON tbl_posto_fabrica.tipo_posto = tbl_tipo_posto.tipo_posto
					JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
					AND tbl_posto_fabrica.fabrica = $login_fabrica
					AND tbl_posto_fabrica.posto = $id_posto";
	$res = pg_query($con, $sql_posto);
	if(pg_last_error($con)){ $msg_erro = "Erro ao consultar o posto"; }

	if(pg_num_rows($res) > 0){
		$posto_interno = pg_fetch_result($res, 0, 'posto_interno');
	}

	if($posto_interno == 't'){
		$cond_posto_interno = "AND tbl_produto.uso_interno_ativo";
	}else{
		$cond_posto_interno = "AND tbl_produto.ativo";
	}

	if(!empty($produto)) {
		$sql = "SELECT produto
			FROM tbl_produto
			WHERE referencia = '$produto'
			AND fabrica_i = $login_fabrica";
		$res = pg_query($con,$sql);

		if(pg_num_rows($res) > 0) {

			$cond = " AND (tbl_numero_serie.produto = ". pg_fetch_result($res,0,0)." OR tbl_numero_serie.produto <> ". pg_fetch_result($res,0,0).") ";
		}
	}
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

		<link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
    	<link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
    	<link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
    	<link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />
		<link href="plugins/dataTable.css" type="text/css" rel="stylesheet" />

		<!-- <script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script> -->
		<script src="bootstrap/js/bootstrap.js"></script>
		<script src="plugins/dataTable.js"></script>
		<script src="plugins/resize.js"></script>
		<script src="plugins/shadowbox_lupa/lupa.js"></script>


		<script type='text/javascript'>
			//função para fechar a janela caso a telca ESC seja pressionada!
			$(window).keypress(function(e) {
				if(e.keyCode == 27) {
					 window.parent.Shadowbox.close();
				}
			});

			<?php if ($login_fabrica == 3) { ?> 
				$(function() {
				
					$(".tbl_pesquisa_produto").click(function() {
						
						var produto     	= $(this).data('produto');
						var referencia   	= $(this).data('referencia');
						var descricao 		= $(this).data('descricao');
						var posicao    		= $(this).data('posicao');
						var cnpj_revenda 	= null;
						var nome_revenda 	= null;
						var fone_revenda 	= null;
						var	email_revenda 	= null;
						var serie      		= null;
						var voltagem 		= null;
						var data_fabricacao = null;


						$(".tbl_pesquisa_produto").css("background","transparent");
						$(this).css("background","#00ff00");
						$('#btn_confirmar_cancelar_produto').show();

						$("#btn_confirmar_produto").attr({
							"produto"    	  : produto,
							"referencia"      : referencia,
							"descricao" 	  : descricao,
							"posicao"    	  : posicao,
							"cnpj_revenda" 	  : cnpj_revenda,
							"nome_revenda" 	  : nome_revenda,
							"fone_revenda" 	  : fone_revenda,
							"email_revenda"   : email_revenda,
							"serie"      	  : serie,
							"voltagem" 		  : voltagem,
							"data_fabricacao" : data_fabricacao
						}).text("Confirmar Modelo: " + descricao);

					});
				});
			<?php } ?>

			function cancelarEscolhaProduto () {
				$(".tbl_pesquisa_produto").css("background","transparent");
				$('#btn_confirmar_cancelar_produto').hide();
			}

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
							<label>Número de Série</label>
							<input type='text' name='serie' value='$serie' style='width: 500px' maxlength='20' />
						</td>";
						echo "<td colspan='2' class='btn_acao' valign='bottom'><input type='submit' name='btn_acao' value='Pesquisar Novamente' /></td>";
					echo "</tr>";
				echo "</table>";
			echo "</form>";
		echo "</div>";

		if(strlen($serie) > 2){
			
			echo "<h5 style='color:red;text-align: center;'>FAVOR SELECIONAR O MODELO CORRETO DO PRODUTO</h5>";
			echo "<div class='lp_pesquisando_por'>";
				echo "Pesquisando por número de série do produto: $serie";
			echo "</div>";

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
						$cond_posto_interno
						AND tbl_numero_serie.serie = '$serie'
						$cond_posto_interno
						$cond
					ORDER BY tbl_produto.descricao;";
			}else if($login_fabrica==74 || $login_fabrica == 120 or $login_fabrica == 201){
				$sql = "
					SELECT
						tbl_produto.produto      ,
						tbl_produto.referencia   ,
						tbl_produto.descricao    ,
						tbl_numero_serie.serie   ,
						tbl_numero_serie.data_fabricacao
					FROM 	tbl_produto
					JOIN    tbl_numero_serie ON tbl_numero_serie.produto = tbl_produto.produto AND tbl_numero_serie.fabrica = $login_fabrica
					WHERE
						tbl_produto.fabrica_i = $login_fabrica
						AND tbl_numero_serie.serie = '$serie'
						$cond_posto_interno
						$cond
					ORDER BY tbl_produto.descricao;";

			}else if ($login_fabrica == 3) {

				$sql = " SELECT *
					 FROM tbl_produto
					 WHERE (radical_serie ilike '%$serie%' OR
					 radical_serie2 ilike '%$serie%' OR
					 radical_serie3 ilike '%$serie%' OR
					 radical_serie4 ilike '%$serie%' OR
					 radical_serie5 ilike '%$serie%' OR
					 radical_serie6 ilike '%$serie%') 
					 AND fabrica_i = $login_fabrica"; 
					 //echo nl2br($sql);
			} else {

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
						$cond_posto_interno
						AND tbl_produto.radical_serie = '$xserie'
					ORDER BY
						tbl_produto.descricao";
			}
			#echo nl2br($sql);
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

				if($login_fabrica == 74 || $login_fabrica == 120 or $login_fabrica == 201){
					$data_fabricacao       = pg_fetch_result($res,0,data_fabricacao);
					$data_fabricacao = ",'" . $data_fabricacao . "'";
				}
				if ($login_fabrica == 6)
				{
					$voltagem = ",'" . $voltagem . "'";
				}

				echo "<script type='text/javascript'>";

					echo "window.parent.retorna_numero_serie('$produto','$referencia','$descricao','$posicao','$cnpj_revenda','$nome_revenda','$fone_revenda','$email_revenda','$serie' $voltagem $data_fabricacao); window.parent.Shadowbox.close();";
				echo "</script>";
			}elseif(pg_numrows ($res) > 1){

			?>

				<table width='100%' border='0' class='tabela table table-bordered table-fixed' cellspacing='1'>
					<thead>
						<tr class='titulo_coluna'>
							<th>Referência</th>
							<th>Descrição</th>
							<th>Série</th>
						</tr>
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
							$voltagem       = pg_fetch_result($res,0,voltagem);

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
								$data_fabricacao       = pg_fetch_result($res,0,data_fabricacao);
								$data_fabricacao = ",'" . $data_fabricacao . "'";
							}

							if ($login_fabrica == 6)
							{
								$voltagem = ",'" . $voltagem . "'";
							}

							if ($login_fabrica != 3) {

								$onclick = "onclick= \"javascript: $msg_confirma window.parent.retorna_numero_serie('$produto','$referencia','$descricao','$posicao','$cnpj_revenda','$nome_revenda','$fone_revenda','$email_revenda','$serie', '$voltagem', '$data_fabricacao', '$chave'); window.parent.Shadowbox.close(); \"";

							} else { 

								$onclick = "data-produto='{$produto}' data-referencia='{$referencia}' data-descricao='{$descricao}' data-posicao='{$posicao}' data-serie='{$serie}'";
							}

							if($login_fabrica==50) $radical_serie = $serie;

							$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
							$cor = "transparent";

							if ($login_fabrica != 3) {
								echo "<tr style='background: $cor' $onclick>";
							} else {
								echo "<tr $onclick class='tbl_pesquisa_produto' style='background: $cor'>";
							}
								echo "<td>".verificaValorCampo($referencia)."</td>";
								echo "<td>".verificaValorCampo($descricao)."</td>";
								echo "<td>".verificaValorCampo($radical_serie)."</td>";
								if ($login_fabrica == 50) {
									echo "<td>".verificaValorCampo($cnpj_revenda)."</td>";
									echo "<td>".verificaValorCampo($nome_revenda)."</td>";
								}
							echo "</tr>";
						}
					echo "</tbody>";
				echo "</table>";

				?>

				<div id="btn_confirmar_cancelar_produto" hidden style="text-align:center; margin-top: 10%">
					<button class="btn btn-success" type="button" id="btn_confirmar_produto" onclick="
							window.parent.retorna_numero_serie(this.getAttribute('produto'),
							this.getAttribute('referencia'),
							this.getAttribute('descricao'),
							this.getAttribute('posicao'),
							this.getAttribute('cnpj_revenda'),
							this.getAttribute('nome_revenda'),
							this.getAttribute('fone_revenda'),
							this.getAttribute('email_revenda'),
							this.getAttribute('serie'),
							this.getAttribute('voltagem'), 
							this.getAttribute('data_fabricacao')); 
							window.parent.Shadowbox.close();">Confirmar Modelo</button>
					<button class="btn btn-danger" type="button" id="btn_cancelar_produto" onclick="cancelarEscolhaProduto(this)">Cancelar</button>
				</div>

				<?php

			}else
				echo "<div class='lp_msg_erro'>Nehum resultado encontrado </div>";
		}else
			$msg_erro = "Informar toda ou parte da informação para realizar a pesquisa!";?>
	</body>
</html>
