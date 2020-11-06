<?
	include "dbconfig.php";
	include "includes/dbconnect-inc.php";
	include 'autentica_usuario.php';

/*
HD 731643
ESTA TELA DE PESQUISA NOVA FOI FEITA PARA A COLORMAQ PARA ATENDER AO CHAMADO ABERTO
*/

	$serie	= trim($_REQUEST["produto_serie"]);

	function verificaValorCampo($campo){
		return strlen($campo) > 0 ? $campo : "&nbsp;";
	}

	$usa_atacadista = in_array($login_fabrica, array(50));

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
			<a href='javascript:window.parent.Shadowbox.close();' style='border: 0;'>
				<img src='admin/css/modal/excluir.png' alt='Fechar' class='lp_btn_fechar' />
			</a>
		</div>
		<?
		echo "<div class='lp_nova_pesquisa'>";
			echo "<form action='$PHP_SELF' method='POST' name='nova_pesquisa'>";
				echo "<input type='hidden' name='produto' value='$produto' />";
				echo "<input type='hidden' name='posicao' value='$posicao' />";

				echo "<table cellspacing='1' cellpadding='2' border='0'>";
					echo "<tr>";
						echo "<td>
							<label>Número de Série</label>
							<input type='text' name='produto_serie' value='$serie' style='width: 500px' maxlength='20' />
						</td>";
						echo "<td colspan='2' class='btn_acao' valign='bottom'><input type='submit' name='btn_acao' value='Pesquisar Novamente' /></td>";
					echo "</tr>";
				echo "</table>";
			echo "</form>";
		echo "</div>";

		if(strlen($serie) > 2){
			echo "<div class='lp_pesquisando_por'>Pesquisando por número de série do produto: $serie</div>";
		}


				$sql = "SELECT
							referencia_produto,
							to_char(data_venda, 'dd/mm/yyyy') as data_venda,
							to_char(data_fabricacao, 'dd/mm/yyyy') as data_fabricacao,
							tbl_revenda.nome                ,
							tbl_revenda.revenda             ,
							tbl_revenda.cnpj                ,
							tbl_revenda.cidade              ,
							tbl_revenda.fone                ,
							tbl_revenda.endereco            ,
							tbl_revenda.numero              ,
							tbl_revenda.complemento         ,
							tbl_revenda.bairro              ,
							tbl_revenda.cep                 ,
							tbl_revenda.atacadista          ,
							tbl_revenda.email               ,
							tbl_cidade.nome AS nome_cidade  ,
							tbl_cidade.estado

						FROM tbl_numero_serie

						LEFT JOIN tbl_revenda on (tbl_numero_serie.cnpj = tbl_revenda.cnpj)
						LEFT JOIN tbl_cidade  on (tbl_revenda.cidade = tbl_cidade.cidade)
						LEFT JOIN tbl_estado  on (tbl_cidade.estado  = tbl_estado.estado)

						WHERE 	tbl_numero_serie.fabrica = $login_fabrica
						AND 	tbl_numero_serie.serie = trim('$produto_serie')";

				$res = pg_query ($con,$sql);

				if (pg_numrows ($res) == 0) {
					if($login_fabrica ==50) {
						$sql = "SELECT
							referencia_produto,
							to_char(data_venda, 'dd/mm/yyyy') as data_venda,
							to_char(data_fabricacao, 'dd/mm/yyyy') as data_fabricacao,
							tbl_revenda.nome                ,
							tbl_revenda.revenda             ,
							tbl_revenda.cnpj                ,
							tbl_revenda.cidade              ,
							tbl_revenda.fone                ,
							tbl_revenda.endereco            ,
							tbl_revenda.numero              ,
							tbl_revenda.complemento         ,
							tbl_revenda.bairro              ,
							tbl_revenda.cep                 ,
							tbl_revenda.atacadista          ,
							tbl_revenda.email               ,
							tbl_cidade.nome AS nome_cidade  ,
							tbl_cidade.estado

						FROM tbl_numero_serie

						LEFT JOIN tbl_revenda on (tbl_numero_serie.cnpj = tbl_revenda.cnpj)
						LEFT JOIN tbl_cidade  on (tbl_revenda.cidade = tbl_cidade.cidade)
						LEFT JOIN tbl_estado  on (tbl_cidade.estado  = tbl_estado.estado)

						WHERE 	tbl_numero_serie.fabrica = $login_fabrica
						AND     tbl_numero_serie.data_fabricacao between '2013-07-25' and '2013-09-13'
						AND 	substr(tbl_numero_serie.serie,1,length(tbl_numero_serie.serie) -1) = trim('$produto_serie')";
						$res = pg_query ($con,$sql);
						if (pg_numrows ($res) == 0) {
							$msg_erro = "Nº Série '$produto_serie' não encontrado.";
						}
					}else{
						$msg_erro = "Nº Série '$produto_serie' não encontrado.";
					}
				}

				$sqlx ="
					SELECT  distinct(tmp_serie1.numero_serie),
							tmp_serie1.serie   ,
							tmp_serie1.referencia_produto as referencia_produto1,
							tmp_serie1.data_venda,
							tmp_serie1.data_fabricacao,
							tbl_revenda.nome                ,
							tbl_revenda.revenda             ,
							tbl_revenda.cnpj                ,
							tbl_revenda.cidade              ,
							tbl_revenda.fone                ,
							tbl_revenda.endereco            ,
							tbl_revenda.numero              ,
							tbl_revenda.atacadista          ,
							tbl_revenda.complemento         ,
							tbl_revenda.bairro              ,
							tbl_revenda.cep                 ,
							tbl_revenda.email               ,
							tbl_cidade.nome AS nome_cidade  ,
							tbl_cidade.estado

					FROM tbl_numero_serie tmp_serie1

					JOIN tbl_numero_serie tmp_serie2 ON (tmp_serie2.serie = tmp_serie1.serie AND tmp_serie2.fabrica = $login_fabrica AND tmp_serie2.referencia_produto <> tmp_serie1.referencia_produto)
					LEFT JOIN tbl_revenda on (tmp_serie1.cnpj = tbl_revenda.cnpj)
					LEFT JOIN tbl_cidade  on (tbl_revenda.cidade = tbl_cidade.cidade)
					LEFT JOIN tbl_estado  on (tbl_cidade.estado  = tbl_estado.estado)

					WHERE tmp_serie1.fabrica = $login_fabrica
					AND   tmp_serie1.serie = trim('$produto_serie');

				";

				$resx = pg_query ($con,$sqlx);


			if(strlen($msg_erro) > 0){

				echo "<div class='lp_msg_erro'>$msg_erro</div>";

			}else{

				if (pg_numrows ($res) > 0) {

				?>
					<table width='100%' border='0' cellspacing='1' cellspading='0' class='lp_tabela' id='gridRelatorio'>
						<thead>
							<tr>
								<th >Produto Referência</th>
								<th >Produto Descrição</th>
								<? if ($login_fabrica != 120 and $login_fabrica != 201) { ?>
									<th >CNPJ Revenda</th>
									<th >Nome Revenda</th>
								<? } ?>
							</tr>
						</thead>

						<tbody><?
						if (pg_num_rows($resx)>0){

							for ($i = 0 ; $i < pg_num_rows($res); $i++) {


								$revenda     		= trim(pg_result($resx,$i,'revenda'));
								$nome        		= trim(pg_result($resx,$i,'nome'));
								$cnpj        		= trim(pg_result($resx,$i,'cnpj'));
								$bairro    			= trim(pg_result($resx,$i,'bairro'));
								$cidade      		= trim(pg_result($resx,$i,'nome_cidade'));
								$fone        		= trim(pg_result($resx,$i,'fone'));
								$endereco	 		= trim(pg_result($resx,$i,'endereco'));
								$numero 	 		= trim(pg_result($resx,$i,'numero'));
								$complemento 		= trim(pg_result($resx,$i,'complemento'));
								$bairro      		= trim(pg_result($resx,$i,'bairro'));
								$cep        		= trim(pg_result($resx,$i,'cep'));
								$cidade     		= trim(pg_result($resx,$i,'nome_cidade'));
								$estado      		= pg_result($resx,$i,'estado');
								$referencia_produto = trim(pg_result($resx,$i,'referencia_produto1'));
								$data_venda         = trim(pg_result($resx,$i,'data_venda'));
								$data_fabricacao    = trim(pg_result($resx,$i,'data_fabricacao'));
								if ($usa_atacadista) {
									$atacadista    		= trim(pg_result($resx,$i,'atacadista'));
								}else{
									$atacadista = '';
								}

								$sql_produto = "
									SELECT   tbl_produto.referencia,
											 tbl_produto.descricao,
											 tbl_produto.voltagem,
											 tbl_produto.produto
									FROM     tbl_produto
									JOIN     tbl_linha ON tbl_produto.linha = tbl_linha.linha and tbl_linha.fabrica = $login_fabrica
									JOIN     tbl_familia ON tbl_familia.familia = tbl_produto.familia and tbl_familia.fabrica = $login_fabrica
									WHERE    tbl_produto.referencia = '$referencia_produto'
									AND      tbl_linha.ativo IS TRUE
									AND      tbl_familia.ativo IS TRUE
									AND      tbl_produto.ativo IS TRUE ";

								$res_produto = pg_query ($con,$sql_produto);

								for ($x = 0; $x < pg_num_rows($res_produto); $x++)
								{
									$produto    = trim(pg_result($res_produto,$x,'produto'));
									$descricao  = trim(pg_result($res_produto,$x,'descricao'));
									$voltagem   = trim(pg_result($res_produto,$x,'voltagem'));
									$referencia = trim(pg_result($res_produto,$x,'referencia'));
									$descricao = str_replace ('"','',$descricao);
									$descricao = str_replace ("'","",$descricao);

									$msg_confirma = "if (confirm('Atenção! Mais de um produto com o mesmo número de série.\\n Verifique se o produto selecionado está correto.')){";

									if($login_fabrica == 120 or $login_fabrica == 201){
										if($data_fabricacao != ""){										
											$data_fabricacao = date("d/m/Y",strtotime($data_fabricacao));										
										}
									}

									$onclick = "onclick= \"javascript: ".$msg_confirma." window.parent.retorna_dados_serie('$produto_serie','$revenda','$nome','$cnpj','$fone','$endereco','$numero','$complemento','$bairro','$cep','$cidade','$estado','$data_venda','$data_fabricacao','$referencia','$descricao','$voltagem','$atacadista'); window.parent.Shadowbox.close(); } \" ";

									$cor = ($x % 2) ? "#F7F5F0" : "#F1F4FA";
									echo "<tr style='background: $cor' $onclick>";
										echo "<td>".verificaValorCampo($referencia)."</td>";
										echo "<td>".verificaValorCampo($descricao)."</td>";
										if ($login_fabrica != 120 and $login_fabrica != 201) {
											echo "<td>".verificaValorCampo($cnpj)."</td>";
											echo "<td>".verificaValorCampo($nome)."</td>";
										}

									echo "</tr>";

								}

							}

						}else if(pg_num_rows($res) == 1){

							//CASO SÓ ENCONTRE UM RESULTADO PARA A SÉRIE PESQUISADA O PROGRAMA RETORNA OS VALORES AUTOMATICAMENTE

							$revenda     		= trim(pg_result($res,0,'revenda'));
							$nome        		= trim(pg_result($res,0,'nome'));
							$cnpj        		= trim(pg_result($res,0,'cnpj'));
							$bairro    			= trim(pg_result($res,0,'bairro'));
							$cidade      		= trim(pg_result($res,0,'nome_cidade'));
							$fone        		= trim(pg_result($res,0,'fone'));
							$endereco	 		= trim(pg_result($res,0,'endereco'));
							$numero 	 		= trim(pg_result($res,0,'numero'));
							$complemento 		= trim(pg_result($res,0,'complemento'));
							$bairro      		= trim(pg_result($res,0,'bairro'));
							$cep        		= trim(pg_result($res,0,'cep'));
							$cidade     		= trim(pg_result($res,0,'nome_cidade'));
							$estado      		= trim(pg_result($res,0,'estado'));
							$referencia_produto = trim(pg_result($res,0,'referencia_produto'));
							$data_venda         = trim(pg_result($res,0,'data_venda'));
							$data_fabricacao    = trim(pg_result($res,0,'data_fabricacao'));
							if ($usa_atacadista) {
								$atacadista    		= trim(pg_result($res,0,'atacadista'));
							}else{
								$atacadista = '';
							}

							$sql_produto = "
								SELECT	 tbl_produto.referencia,
										tbl_produto.descricao,
										tbl_produto.voltagem,
										tbl_produto.produto
								FROM     tbl_produto
								JOIN     tbl_linha ON tbl_produto.linha = tbl_linha.linha and tbl_linha.fabrica = $login_fabrica
								JOIN     tbl_familia ON tbl_familia.familia = tbl_produto.familia and tbl_familia.fabrica = $login_fabrica
								WHERE    tbl_produto.referencia = '$referencia_produto'
								AND      tbl_linha.ativo IS TRUE
								AND      tbl_familia.ativo IS TRUE
								AND      tbl_produto.ativo IS TRUE ";

							$res_produto = pg_query ($con,$sql_produto);

							$produto    = trim(pg_result($res_produto,0,'produto'));
							$descricao  = trim(pg_result($res_produto,0,'descricao'));
							$voltagem   = trim(pg_result($res_produto,0,'voltagem'));
							$referencia = trim(pg_result($res_produto,0,'referencia'));
							$descricao = str_replace ('"','',$descricao);
							$descricao = str_replace ("'","",$descricao);

							$onclick = "onclick= \"javascript: window.parent.retorna_dados_serie('$produto_serie','$revenda','$nome','$cnpj','$fone','$endereco','$numero','$complemento','$bairro','$cep','$cidade','$estado','$data_venda','$data_fabricacao','$referencia','$descricao','$voltagem','$atacadista'); window.parent.Shadowbox.close(); \"  ";

							$cor = "#F7F5F0";
							echo "<tr style='background: $cor' $onclick>";
								echo "<td>".verificaValorCampo($referencia)."</td>";
								echo "<td>".verificaValorCampo($descricao)."</td>";
								if ($login_fabrica != 120 and $login_fabrica != 201) {
									echo "<td>".verificaValorCampo($cnpj)."</td>";
									echo "<td>".verificaValorCampo($nome)."</td>";
								}
							echo "</tr>";

						}
					?>
						</tbody>
					</table>
					<?
				}else
					echo "<div class='lp_msg_erro'>Nehum resultado encontrado</div>";
			}?>
	</body>
</html>
