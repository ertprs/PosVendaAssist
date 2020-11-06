<?php
$contador_ver ="0";
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_usuario.php';

	$produto	= trim($_REQUEST["produto"]);
	$descricao	= trim($_REQUEST["descricao"]);
	$posicao	= trim($_REQUEST["posicao"]);
	$peca		= trim($_REQUEST["peca"]);
	
	if(!empty($peca)){
		$join_lista_basica = " JOIN tbl_lista_basica ON tbl_lista_basica.produto = tbl_produto.produto AND tbl_lista_basica.peca = $peca ";
	}

	function verificaValorCampo($campo){
		return strlen($campo) > 0 ? $campo : "&nbsp;";
	}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
	<head>
		<title><?=traduz('pesquisa.de.produto', $con)?></title>
		<meta http-equiv=pragma content=no-cache>
		<link rel="stylesheet" type="text/css" href="css/lupas/lupas.css">
		<style type="text/css">
			body {
				margin: 0;
				font-family: Arial, Verdana, Times, Sans;
				background: #fff;
			}
		</style>
		<script type="text/javascript" src="js/jquery-1.6.2.js"></script>
		<script src="plugins/jquery/jquery.tablesorter.min.js" type="text/javascript"></script>
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
			<form action='<?=$PHP_SELF?>' method='POST' name='nova_pesquisa'>
				<input type='hidden' name='voltagem' value='<?=$voltagem?>' />
				<input type='hidden' name='tipo'     value='<?=$tipo?>' />
				<input type='hidden' name='posicao'  value='<?=$posicao?>' />

				<table cellspacing='1' cellpadding='2' border='0'>
					<tr>
						<td>
							<label><?=traduz('referencia', $con)?></label>
							<input type='text' name='referencia' value='<?=$referencia?>' style='width: 150px' maxlength='20' />
						</td>
						<td>
							<label><?=traduz('descricao', $con)?></label>
							<input type='text' name='descricao' value='<?=$descricao?>' style='width: 370px' maxlength='80' />
						</td>
						<td colspan='2' class='btn_acao' valign='bottom'><input type='submit' name='btn_acao' value='<?=traduz('pesquisar.novamente', $con)?>' /></td>
					</tr>
				</table>
			</form>
		</div>

		<?

			if ($login_fabrica == 14) 
				$sql_familia = " JOIN tbl_familia on tbl_familia.familia = tbl_produto.familia ";

			$msg_confirma ="0";
			
			if ($login_fabrica == 30) {
				if (strlen($referencia) > 2) {

					$referencia = str_replace(".","",$referencia);
					$referencia = str_replace(",","",$referencia);
					$referencia = str_replace("-","",$referencia);
					$referencia = str_replace("/","",$referencia);

					$sql = "
						SELECT 
							CASE WHEN tbl_produto.marca = 164 then 't' ELSE 'f' END as itatiaia
						FROM  
							tbl_produto
							JOIN tbl_linha ON tbl_produto.linha = tbl_linha.linha
						WHERE 
							tbl_produto.referencia_pesquisa LIKE '%$referencia%'
							AND tbl_linha.fabrica = $login_fabrica
							AND tbl_produto.ativo
							AND tbl_produto.produto_principal;";
				}elseif(strlen($descricao) > 2){
					$descricao = strtoupper($descricao);
		
					$sql = "
						SELECT 
							CASE WHEN tbl_produto.marca = 164 then 't' ELSE 'f' END as itatiaia
						FROM tbl_produto
							JOIN  tbl_linha   on tbl_produto.linha = tbl_linha.linha
						WHERE 
							(UPPER(tbl_produto.descricao) LIKE '%$descricao%' OR UPPER(tbl_produto.nome_comercial) LIKE '%$descricao%' )
							AND tbl_linha.fabrica = $login_fabrica
							AND tbl_produto.ativo
							AND tbl_produto.produto_principal;";
				}
				/*
				else{
					$msg_erro = "Informar toda ou parte da informação para realizar a pesquisa!";
				}
				*/

				$res = pg_exec($con,$sql);
				if (pg_numrows($res) > 0) {
					$itatiaia = pg_result($res,0,'itatiaia');

					if ($itatiaia == 't') {
						$contador_ver ="1";
						echo "<script language='javascript'>";
							echo "alert('Este produto é ITATIAIA não pode ser aberto Ordem de Serviço pelo Posto, somente o CALLCENTER poderá abrir. Favor entrar em contato com o CALLCENTER!');";
							echo "window.parent.Shadowbox.close();";
						echo "</script>";
					}
				}
			}

			if (strlen($referencia) > 2) {
				echo "<div class='lp_pesquisando_por'>" . traduz('pesquisando.pela.referencia', $con, $cook_idioma, array($referencia)) . "</div>";

				$referencia = str_replace(".","",$referencia);
				$referencia = str_replace(",","",$referencia);
				$referencia = str_replace("-","",$referencia);
				$referencia = str_replace("/","",$referencia);

				$sql = "SELECT *
						FROM tbl_produto
							JOIN tbl_linha ON tbl_produto.linha = tbl_linha.linha
							LEFT JOIN tbl_produto_pais   using(produto)
							$sql_familia
							$join_lista_basica
						WHERE tbl_produto.referencia_pesquisa ILIKE '%$referencia%'
							AND tbl_linha.fabrica = $login_fabrica
							AND tbl_produto.ativo
							AND tbl_produto.produto_principal ";

				if ($login_fabrica == 20) {
					$sql = "	SELECT *
							FROM tbl_produto
								JOIN tbl_linha ON tbl_produto.linha = tbl_linha.linha
								LEFT JOIN tbl_produto_pais   using(produto)
							WHERE (tbl_produto.referencia_pesquisa ILIKE '%$referencia%' OR tbl_produto.referencia_fabrica ILIKE '%$referencia%')
								AND tbl_linha.fabrica = $login_fabrica
								AND tbl_produto.ativo
								AND tbl_produto.produto_principal ";
				}

				if ($login_fabrica == 14 or $login_fabrica == 66) $sql .= " AND tbl_produto.abre_os IS TRUE ";
				if ($login_fabrica == 20) $sql .= " AND tbl_produto_pais.pais = '$login_pais' ";

				if ($login_fabrica == 14 and $login_pais=='BR') $sql .= " and upper(tbl_produto.origem) <> 'IMP' and upper(tbl_produto.origem) <> 'USA' and upper(tbl_produto.origem) <> 'ASI' ";
				
				$sql .= " ORDER BY";
				
				if ($login_fabrica == 45) $sql .= " tbl_produto.referencia, ";
				
				$sql .= " tbl_produto.descricao;";

				$res = pg_exec($con,$sql);

			}elseif(strlen($descricao) > 2){
				echo "<div class='lp_pesquisando_por'>" . traduz('pesquisando.pela.descricao', $con, $cook_idioma, array($descricao)) . "</div>";

				$descricao = strtoupper($descricao);

				if ($login_pais <> 'BR') {
					$cond1 = "";
				}

				$cond_ativo = "tbl_produto.ativo";

				$sql = "	SELECT *
						FROM tbl_produto
							JOIN tbl_linha ON tbl_produto.linha = tbl_linha.linha
							LEFT JOIN tbl_produto_idioma using(produto)
							LEFT JOIN tbl_produto_pais   using(produto)
							$sql_familia
							$join_lista_basica
						WHERE 
							(UPPER(tbl_produto.descricao) ILIKE '%$descricao%' OR UPPER(tbl_produto.nome_comercial) ILIKE '%$descricao%' 
							OR 
							(UPPER(tbl_produto_idioma.descricao) LIKE '%$descricao%' AND tbl_produto_idioma.idioma = '$sistema_lingua'))
						AND tbl_linha.fabrica = $login_fabrica
						AND $cond_ativo
						AND tbl_produto.produto_principal";

					if ($login_fabrica == 14 or $login_fabrica == 66) 
						$sql .= " AND tbl_produto.abre_os IS TRUE ";

					if ($login_fabrica == 20) 
						$sql .= " AND tbl_produto_pais.pais = '$login_pais' ";

					if ($login_fabrica == 14 and $login_pais=='BR') 
						$sql .= " and upper(tbl_produto.origem) <> 'IMP' and upper(tbl_produto.origem) <> 'USA' and upper(tbl_produto.origem) <> 'ASI' ";

				$sql .= " ORDER BY tbl_produto.descricao;";

			}else
				$msg_erro = traduz('informar.toda.parte.informacao.para.realizar.pesquisa', $con);

			if(strlen($msg_erro) > 0){
				echo "<div class='lp_msg_erro'>$msg_erro</div>";
			}else{
				$res = pg_exec ($con,$sql);

				if(pg_numrows ($res) > 0){?>
					<table width='100%' border='0' cellspacing='1' cellspading='0' class='lp_tabela' id='gridRelatorio'>
						<thead>
							<tr>
								<th width="20%"><?=traduz('codigo.do.produto', $con)?></th>
								<?php
									if ($login_fabrica == 20) 
										echo '<th width="10%">Código Fábrica</th>';
								?>
								<th width="40%"><?=traduz('modelo.do.produto', $con)?></th>
								<th width="10%"><?=traduz('descricao', $con)?></th>
								<th width="10%"><?=traduz('voltagem', $con)?></th>
								<th width="10%"><?=traduz('status', $con)?></th>
							</tr>
						</thead>
						<tbody><? 
							for ($i = 0 ; $i < pg_num_rows($res); $i++) {
								$produto            = trim(pg_result($res, $i, 'produto'));
								$linha              = trim(pg_result($res, $i, 'linha'));
								$descricao          = trim(pg_result($res, $i, 'descricao'));
								$nome_comercial     = trim(pg_result($res, $i, 'nome_comercial'));
								$voltagem           = trim(pg_result($res, $i, 'voltagem'));
								$referencia         = trim(pg_result($res, $i, 'referencia'));
								$referencia_fabrica = trim(pg_result($res, $i, 'referencia_fabrica'));
								$garantia           = trim(pg_result($res, $i, 'garantia'));
								$ativo              = trim(pg_result($res, $i, 'ativo'));
								$valor_troca        = trim(pg_result($res, $i, 'valor_troca'));
								$troca_garantia     = trim(pg_result($res, $i, 'troca_garantia'));
								$troca_faturada     = trim(pg_result($res, $i, 'troca_faturada'));

								$descricao          = str_replace('"','',$descricao);
								$descricao          = str_replace("'","",$descricao);

								$troca_obrigatoria  = trim(pg_result($res, $i, 'troca_obrigatoria'));

								$sql_idioma = "SELECT * FROM tbl_produto_idioma WHERE produto = $produto AND idioma = '$cook_idioma'";
								$res_idioma = pg_exec($con,$sql_idioma);

								if (pg_numrows($res_idioma) >0)
									$descricao  = trim(pg_result($res_idioma, 0, 'descricao'));

								$mativo = ($ativo == 't') ?  "ATIVO" : "INATIVO";

								$produto_pode_trocar = 1;
								if ($troca_produto == 't' or $revenda_troca == 't') {
									if ($troca_faturada != 't' AND $troca_garantia != 't') 
										$produto_pode_trocar = 0;
								}

								$produto_so_troca = 1;
								if ($troca_obrigatoria_consumidor == 't' or $troca_obrigatoria_revenda == 't') {
									if ($troca_obrigatoria == 't') 
										$produto_so_troca = 0;
								}

								if ($produto_pode_trocar == 0) 
									$onclick = "onclick= \"javascript: window.alert('Este produto não é troca. Solicitar peças e realizar o reparo normalmente. \nEm caso de dúvidas entre em contato com o suporte da sua região.');";
								elseif($produto_so_troca == 0)
									$onclick = "onclick= \"javascript: window.alert('Prezado Posto, este produto é somente para troca. Gentlileza cadastrar na O.S de troca Específica.');";
								else{
									if ($login_fabrica == 11) {
										$num = pg_numrows($res);
										if ($num>1) 
											$msg_confirma = "1";
									}
									$onclick = "onclick= \"javascript: window.parent.retorna_dados_produto('$referencia','$descricao','$produto','$linha','$nome_comercial','$voltagem','$referencia_fabrica','$garantia','$ativo','$valor_troca','$troca_garantia','$troca_faturada','$posicao'); window.parent.Shadowbox.close();\"";
									if(pg_num_rows($res) == 1){
										echo "<script type='text/javascript'>";
											echo "window.parent.retorna_dados_produto('$referencia','$descricao','$produto','$linha','$nome_comercial','$voltagem','$referencia_fabrica','$garantia','$ativo','$valor_troca','$troca_garantia','$troca_faturada','$posicao'); window.parent.Shadowbox.close();";
										echo "</script>";
									}
								}

								if ($login_fabrica == 20) {
									if (strlen($referencia_fabrica) > 0)
										$referencia_fabrica = "<font size='1' color='#AAAAAA'>Bare Tool</font> ".$referencia_fabrica;
								}

								$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
								echo "<tr style='background: $cor' $onclick>";
									echo "<td>".verificaValorCampo($referencia)."</td>";
									if ($login_fabrica == 20) 
										echo "<td>".verificaValorCampo($referencia_fabrica)."</td>";
									echo "<td>".verificaValorCampo($descricao)."</td>";
									echo "<td>".verificaValorCampo($nome_comercial)."</td>";
									echo "<td>".verificaValorCampo($voltagem)."</td>";
									echo "<td>".verificaValorCampo($mativo)."</td>";
								echo "</tr>";
							}
						echo "</tbody>";
					echo "</table>";
				}else{
					echo "<div class='lp_msg_erro'>" . traduz('nenhum.resultado.encontrado', $con) . "</div>";
				}
			}?>
	</body>
</html>
