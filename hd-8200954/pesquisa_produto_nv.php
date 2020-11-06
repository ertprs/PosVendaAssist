<?
	include "dbconfig.php";
	include "includes/dbconnect-inc.php";
	include "autentica_usuario.php";
	include "funcoes.php";

	$referencia	= trim($_REQUEST["referencia"]);
	$descricao	= trim($_REQUEST["descricao"]);
	$voltagem	= trim($_REQUEST["voltagem"]);
	$posicao	= trim($_REQUEST["posicao"]);

	if ($login_fabrica == 11) {
		$l_mostra_produto = $_REQUEST["l_mostra_produto"];
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
		<script type="text/javascript" src="js/jquery-1.6.2.js"></script>
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
		</div>
		<div class='lp_nova_pesquisa'>
			<form action='<?=$_SERVER['PHP_SELF']?>' method='POST' name='nova_pesquisa'>
				<input type='hidden' name='voltagem' value='<?=$voltagem?>' />
				<input type='hidden' name='tipo' value='<?=$tipo?>' />
				<input type='hidden' name='posicao' value='<?=$posicao?>' />
				<?php
					if ($login_fabrica == 11) {
						echo "<input type='hidden' name='l_mostra_produto' value='$l_mostra_produto' />";
					}
				?>

				<table cellspacing='1' cellpadding='2' border='0'>
					<tr>
						<td>
							<label><?=traduz("referencia.do.produto",$con,$cook_idioma)?></label>
							<input type='text' name='referencia' value='<?=$referencia?>' style='width: 150px' maxlength='20' />
						</td>
						<td>
							<label><?=traduz('descricao.do.produto',$con,$cook_idioma)?></label>
							<input type='text' name='descricao' value='<?=$descricao?>' style='width: 370px' maxlength='80' />
						</td>
						<td colspan='2' class='btn_acao' valign='bottom'><input type='submit' name='btn_acao' value='<?=traduz('pesquisar',$con,$cook_idioma)?>' /></td>
					</tr>
				</table>
			</form>
		</div>
		<?

			if($login_fabrica == 20){
				$join_bosch = " JOIN tbl_produto_pais ON tbl_produto_pais.produto = tbl_produto.produto AND pais = '$login_pais'";
			}else{
				$join_bosch = "";
			}

			if (in_array($login_fabrica, array(11,172))) {
				$programa = $_REQUEST['exibe'];

				$sqlPosto = "SELECT permite_envio_produto 
							FROM tbl_posto 
							JOIN tbl_posto_fabrica USING(posto)
							WHERE tbl_posto_fabrica.fabrica = $login_fabrica
							AND tbl_posto_fabrica.posto = $login_posto";
				$resPosto = pg_query($con, $sqlPosto);

				$permite_envio_produto = pg_fetch_result($resPosto, 0, "permite_envio_produto");

				if($l_mostra_produto <> "ok" && $permite_envio_produto == "f"){
					$sql_abre_os = " AND tbl_produto.abre_os IS TRUE ";
				}

				if (preg_match("pedido_cadastro.php", $programa)) {
					$sql_abre_os = "";
				}
			}
			
			if(strlen($sql_abre_os) == 0 && in_array($login_fabrica, array(11,172))){
				$sql_abre_os = " AND tbl_produto.abre_os = true ";
			}	

			$condFab = (!in_array($login_fabrica, [11,172])) ? "AND tbl_linha.fabrica = $login_fabrica" : "AND tbl_linha.fabrica IN (11,172)";

			if (strlen($referencia) > 2) {
				echo "<div class='lp_pesquisando_por'>".traduz('pesquisando.pela.referencia',$con,$cook_idioma,$referencia)."</div>";

				$referencia = str_replace(array("-", "/", ".", " "), "", $referencia);
				
				$sql = "
					SELECT DISTINCT ON (referencia)
						tbl_produto.produto	,
						tbl_produto.referencia	,
						tbl_produto.referencia_fabrica,
						tbl_produto.descricao,
						tbl_linha.informatica,
                        tbl_produto.voltagem
					FROM tbl_produto
						JOIN tbl_linha ON tbl_produto.linha = tbl_linha.linha
						$join_bosch
					WHERE 
						(tbl_produto.referencia_pesquisa ILIKE '%$referencia%' OR tbl_produto.referencia_fabrica ILIKE '%$referencia%')
						
                        AND tbl_produto.ativo IS TRUE
                        {$condFab}
                        {$sql_abre_os}
					ORDER BY tbl_produto.referencia;";
					
					
			}elseif(strlen($descricao) > 2){
				echo "<div class='lp_pesquisando_por'>".traduz('pesquisando.pela.descricao',$con,$cook_idioma,$descricao)."</div>";

				$descricao = str_replace(array("-", "/", "."), "", $descricao);
	
				$sql = "
					SELECT DISTINCT ON (referencia)
						tbl_produto.produto	,
						tbl_produto.referencia	,
						tbl_produto.referencia_fabrica,
						tbl_produto.descricao   ,
						tbl_linha.informatica,
                        tbl_produto.voltagem
					FROM tbl_produto
						JOIN tbl_linha ON tbl_produto.linha = tbl_linha.linha
						$join_bosch
					WHERE 
						fn_retira_especiais(tbl_produto.descricao) ILIKE '%' || fn_retira_especiais('$descricao') || '%'
                        AND tbl_produto.ativo IS TRUE
                        {$sql_abre_os}
                        {$condFab}
					ORDER BY tbl_produto.referencia;";
			}else{
				$msg_erro = traduz('informar.toda.parte.informacao.para.realizar.pesquisa',$con);
			}

            //echo nl2br($sql);
			if(strlen($msg_erro) > 0){
				echo "<div class='lp_msg_erro'>$msg_erro</div>";
			}else{
				$res = pg_exec ($con,$sql);

				if (pg_numrows ($res) == 1) {
						$produto    = pg_fetch_result($res, 0, 'produto');
						$referencia = pg_fetch_result($res, 0, 'referencia');
						$descricao  = pg_fetch_result($res, 0, 'descricao');
						$voltagem   = pg_fetch_result($res, 0, 'voltagem');
						$informatica   = pg_fetch_result($res, 0, 'informatica');

						if (in_array($login_fabrica, [11,172])) {

							$arrDadosProduto = valida_produto_pacific_lennox($referencia);

							if (count($arrDadosProduto["fabrica"]) > 1) {

								$perguntar = "t";

							}

							if ($perguntar == "t") { ?>

								<script>

									var posicao = '<?= $posicao ?>';

									$("#botoes_sim_nao_"+posicao, window.parent.document).show();
									$("input[name=possui_codigo_interno_"+posicao+"]").prop("checked", false);

								</script>

							<?php
							} else { ?>

								<script>

									var posicao = '<?= $posicao ?>';

									$("#botoes_sim_nao_"+posicao, window.parent.document).hide();
									$("input[name=possui_codigo_interno_"+posicao+"]").prop("checked", false);

								</script>

							<?php
							}

						}

						echo "<script type='text/javascript'>";
							echo "window.parent.retorna_produto('$produto','$referencia','$descricao','$posicao','$voltagem'); window.parent.Shadowbox.close();";
						echo "</script>";
				}elseif(pg_numrows ($res) > 1){
					?>
					<table width='100%' border='0' cellspacing='1' cellspading='0' class='lp_tabela' id='gridRelatorio'>
						<thead>
							<tr>
								<th><?php echo traduz("referencia.do.produto",$con); ?></th>
								<?php 
									if($login_fabrica == 20)
										echo "<th>Bar Tool</th>";	
								?>
								<th><?php echo traduz("descricao.do.produto",$con); ?></th>
                                <th><?php echo traduz("voltagem",$con); ?></th>
							</tr>
						</thead>
						<tbody><?
							for ($i = 0 ; $i < pg_num_rows($res); $i++) {
								$produto            = pg_fetch_result($res, $i, 'produto');
								$referencia         = pg_fetch_result($res, $i, 'referencia');
								$descricao          = pg_fetch_result($res, $i, 'descricao');
								$voltagem           = pg_fetch_result($res, $i, 'voltagem');
								$referencia_fabrica = pg_fetch_result($res, 0 , 'referencia_fabrica');
								$informatica 		= pg_fetch_result($res, 0 , 'informatica');

								if (in_array($login_fabrica, [11,172])) {

									$arrDadosProduto = valida_produto_pacific_lennox($referencia);

									if (count($arrDadosProduto["fabrica"]) > 1) {

										$perguntar = "t";

									}

									if ($perguntar == "t") {

										$acaoBtn = "$('#botoes_sim_nao_".$posicao."', window.parent.document).show();";

									} else { 

										$acaoBtn = "$('#botoes_sim_nao_".$posicao."', window.parent.document).hide();";

									}

								}

								$onclick = "onclick= \"javascript: window.parent.retorna_produto('$produto','$referencia','$descricao','$posicao','$voltagem', '$informatica');{$acaoBtn} window.parent.Shadowbox.close();\"";

								$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
										echo "<tr style='background: $cor' $onclick>";
											echo "<td>".verificaValorCampo($referencia)."</td>";
											if($login_fabrica == 20)
												echo "<td>".verificaValorCampo($referencia_fabrica)."</td>";
											echo "<td>".verificaValorCampo($descricao)."</td>";
                                            echo "<td>".verificaValorCampo($voltagem)."</td>";
										echo "</tr>";
							}
						echo "</tbody>";
					echo "</table>";
				}else{
					echo "<div class='lp_msg_erro'>".traduz("nenhum.resultado.encontrado",$con,$cook_idioma)."</div>";
				}
			}?>
	</body>
</html>
