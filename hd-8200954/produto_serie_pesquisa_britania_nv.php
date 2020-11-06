<?php
	include "/etc/telecontrol.cfg";
	include "dbconfig.php";
	include "includes/dbconnect-inc.php";
	include 'autentica_usuario.php';

	$serie		= trim($_REQUEST["serie"]);
	$posicao	= trim($_REQUEST["posicao"]);

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
		<!-- <script src="plugins/jquery/jquery.tablesorter.min.js" type="text/javascript"></script> -->
		<link rel="stylesheet" type="text/css" href="css/lupas/lupas.css">

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
						
						var produto    = $(this).data('produto');
						var referencia = $(this).data('referencia');
						var descricao  = $(this).data('descricao');
						var posicao    = $(this).data('posicao');
						var serie      = $(this).data('serie');

						window.parent.retorna_numero_serie(produto, referencia, descricao, posicao, serie);

						$(".tbl_pesquisa_produto").css("background","LightGray");
						$(this).css("background","#00ff00");
						$('#btn_confirmar_produto').text("Confirmar Modelo: " + descricao);
						$('#btn_confirmar_cancelar').show();
					});
				});
			<?php } ?>

			function escolherProduto () {
				
				window.parent.Shadowbox.close();
			}

			function cancelarEscolhaProduto () {
				$(".tbl_pesquisa_produto").css("background","transparent");
				$('#btn_confirmar_cancelar').hide();
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
							<label>".traduz("numero.de.serie",$con,$cook_idioma)."</label>
							<input type='text' name='serie' value='$serie' style='width: 500px' maxlength='20' />
						</td>"; 
						echo "<td colspan='2' class='btn_acao' valign='bottom'><input type='submit' name='btn_acao' value='Pesquisar Novamente' /></td>"; 
					echo "</tr>";
				echo "</table>";
			echo "</form>";
		echo "</div>";

		if(strlen($serie) > 2){

			echo "<h5 style='color:red;text-align: center;'>FAVOR SELECIONAR O MODELO CORRETO DO PRODUTO</h5>";
			
			echo "<div class='lp_pesquisando_por'>Pesquisando por número de série do produto: $serie</div>";
				//$n_serie = preg_replace('/(.*)([A-Z][0-9]+[A-Z])$/', '\2', $serie);
				$n_serie = preg_replace('/(.*)([A-Z0-9]{5})$/', '\2', $serie);
				if ($serie != $n_serie) {
					$serie = $n_serie;
				}
				$sql = " SELECT *
						 FROM tbl_produto
						 WHERE (radical_serie ilike '%$serie%' OR
						 radical_serie2 ilike '%$serie%' OR
						 radical_serie3 ilike '%$serie%' OR
						 radical_serie4 ilike '%$serie%' OR
						 radical_serie5 ilike '%$serie%' OR
						 radical_serie6 ilike '%$serie%') 
						 AND fabrica_i = $login_fabrica"; 
						 #echo nl2br($sql);exit;
			$res = pg_exec ($con,$sql);

			if (pg_numrows ($res) == 1) {
				$produto		= pg_fetch_result($res,0,produto);
				$referencia		= pg_fetch_result($res,0,referencia);
				$descricao		= pg_fetch_result($res,0,descricao);
				if ($login_fabrica==50){
					$cnpj_revenda  = pg_result($res,$i,'cnpj');
					$nome_revenda  = pg_result($res,$i,'nome');
					$email_revenda = pg_result($res,$i,'email');
					$fone_revenda  = pg_result($res,$i,'fone');
				}
										
				echo "<script type='text/javascript'>";
					
					echo "window.parent.retorna_numero_serie('$produto','$referencia','$descricao','$posicao','$cnpj_revenda','$nome_revenda','$fone_revenda','$email_revenda','$serie'); window.parent.Shadowbox.close();";
				echo "</script>";
			}elseif(pg_numrows ($res) > 1){ ?>

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

							if ($login_fabrica != 3) {

								$onclick = "onclick= \"javascript: $msg_confirma window.parent.retorna_numero_serie('$produto','$referencia','$descricao','$posicao','$cnpj_revenda','$nome_revenda','$fone_revenda','$email_revenda','$serie'); window.parent.Shadowbox.close(); $chave\"";
							
							} else { 

								$onclick = "data-produto='{$produto}' data-referencia='{$referencia}' data-descricao='{$descricao}' data-posicao='{$posicao}' data-serie='{$serie}'";
							}

							$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
							
							if ($login_fabrica != 3) {
								echo "<tr style='background: $cor' $onclick>";
							} else {
								echo "<tr $onclick class='tbl_pesquisa_produto' style='background: $cor'>";
							}

							if($login_fabrica==50) $radical_serie = $serie;

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
				?>

				<div id="btn_confirmar_cancelar" hidden style="text-align:center; margin-top: 10%">
					<button class="btn btn-success" type="button" id="btn_confirmar_produto" onclick="escolherProduto(this)">Confirmar</button>
					<button class="btn btn-danger" type="button" id="btn_cancelar_produto" onclick="cancelarEscolhaProduto(this)">Cancelar</button>
				</div>

				<?php

			}else
				echo "<div class='lp_msg_erro'>Nehum resultado encontrado</div>";
		}else
			$msg_erro = "Informar toda ou parte da informação para realizar a pesquisa!";?>
	</body>
</html>
