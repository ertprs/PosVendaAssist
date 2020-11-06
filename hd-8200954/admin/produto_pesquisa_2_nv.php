<?php
	$contador_ver ="0";
	include "dbconfig.php";
	include "includes/dbconnect-inc.php";
	include 'autentica_admin.php';

	if (strpos($_SERVER["PHP_SELF"], "/admin_cliente")) {
		$url_cliente_admin = "../admin/";
	}

	$descricao	= trim($_REQUEST["descricao"]);
	$referencia	= trim(strtoupper($_REQUEST["referencia"]));
  	$posicao    = trim($_REQUEST["posicao"]);
	$origem	    = trim($_REQUEST["origem"]);
	$exibe	   = trim($_REQUEST["exibe"]);
	$id_posto 	= trim($_REQUEST['id_posto']);
	if ($login_fabrica == 1) {
		$troca 	= trim($_REQUEST['troca']);
	}

	if(!empty($id_posto)) {
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
			if ($login_fabrica == 3) {
				$cond_posto_interno = "AND (tbl_produto.ativo IS TRUE OR (tbl_produto.ativo IS NOT TRUE AND tbl_produto.parametros_adicionais::jsonb->>'ativacao_automatica' = 't'))";
			}
		}
	}else{
			$cond_posto_interno = "AND tbl_produto.ativo";
			if ($login_fabrica == 3) {
				$cond_posto_interno = "AND (tbl_produto.ativo IS TRUE OR (tbl_produto.ativo IS NOT TRUE AND tbl_produto.parametros_adicionais::jsonb->>'ativacao_automatica' = 't'))";
			}
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
		<script type="text/javascript" src="<?=$url_cliente_admin?>js/jquery-1.4.2.js"></script>
		<script src="<?=$url_cliente_admin?>../plugins/jquery/jquery.tablesorter.min.js" type="text/javascript"></script>
		<link rel="stylesheet" type="text/css" href="<?=$url_cliente_admin?>../css/lupas/lupas.css">
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
				<img src='<?=$url_cliente_admin?>css/modal/excluir.png' alt='Fechar' class='lp_btn_fechar' />
			</a>
		</div>
		<?
			echo "<div class='lp_nova_pesquisa'>";
				echo "<form action='$PHP_SELF' method='POST' name='nova_pesquisa'>";
					echo "<input type='hidden' name='voltagem' value='$voltagem' />";
					echo "<input type='hidden' name='tipo' value='$tipo' />";
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
							$cond_posto_interno
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
							$cond_posto_interno
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
				echo "<div class='lp_pesquisando_por'>Pesquisando pela referência: $referencia</div>";

				$referencia = str_replace(".","",$referencia);
				$referencia = str_replace(",","",$referencia);
				$referencia = str_replace("-","",$referencia);
				$referencia = str_replace("/","",$referencia);

				$sql = "SELECT *
						FROM tbl_produto
							JOIN tbl_linha ON tbl_produto.linha = tbl_linha.linha
							LEFT JOIN tbl_produto_pais   using(produto)
							$sql_familia
						WHERE tbl_produto.referencia_pesquisa LIKE '%$referencia%'
							AND tbl_linha.fabrica = $login_fabrica
							$cond_posto_interno
							AND tbl_produto.produto_principal ";

				if ($login_fabrica == 20) {
					$sql = "	SELECT *
							FROM tbl_produto
								JOIN tbl_linha ON tbl_produto.linha = tbl_linha.linha
								
							WHERE (tbl_produto.referencia_pesquisa LIKE '%$referencia%' OR tbl_produto.referencia_fabrica LIKE '%$referencia%' or tbl_produto.referencia like '%$referencia%')
								AND tbl_linha.fabrica = $login_fabrica
								$cond_posto_interno
								AND tbl_produto.produto_principal ";
				}

				if ($login_fabrica == 14 or $login_fabrica == 66) $sql .= " AND tbl_produto.abre_os IS TRUE ";

				if ($login_fabrica == 14 and $login_pais=='BR') $sql .= " and upper(tbl_produto.origem) <> 'IMP' and upper(tbl_produto.origem) <> 'USA' and upper(tbl_produto.origem) <> 'ASI' ";
				
				$sql .= " ORDER BY";
				
				if ($login_fabrica == 45) $sql .= " tbl_produto.referencia, ";
				
				$sql .= " tbl_produto.descricao;";
				
				// $res = pg_query($con,$sql);

			}elseif(strlen($descricao) > 2){
				echo "<div class='lp_pesquisando_por'>Pesquisando pela descrição: $descricao</div>";

				$descricao = $descricao;

				if ($login_pais <> 'BR') {
					$cond1 = "";
				}
				$join_pais_idioma = ($login_fabrica <> 20) ? "LEFT JOIN tbl_produto_idioma using(produto) LEFT JOIN tbl_produto_pais   using(produto)" : "" ;
				if ($login_fabrica <> 20 ){
					$where1 = "
						OR 
							(UPPER(tbl_produto_idioma.descricao) LIKE '%$descricao%' AND tbl_produto_idioma.idioma = '$sistema_lingua')
					";
				}

				$sql = "	SELECT *
						FROM tbl_produto
							JOIN tbl_linha ON tbl_produto.linha = tbl_linha.linha

							$join_pais_idioma
							$sql_familia
						WHERE 
							(tbl_produto.descricao ILIKE '%$descricao%' OR tbl_produto.nome_comercial ILIKE '%$descricao%' 
							$where1
							)
						AND tbl_linha.fabrica = $login_fabrica
						$cond_posto_interno
						AND tbl_produto.produto_principal";

					if ($login_fabrica == 14 or $login_fabrica == 66) 
						$sql .= " AND tbl_produto.abre_os IS TRUE ";

					if ($login_fabrica == 14 and $login_pais=='BR') 
						$sql .= " and upper(tbl_produto.origem) <> 'IMP' and upper(tbl_produto.origem) <> 'USA' and upper(tbl_produto.origem) <> 'ASI' ";

				$sql .= " ORDER BY tbl_produto.descricao;";

				// echo nl2br($sql);

			}else
				$msg_erro = "Informar toda ou parte da informação para realizar a pesquisa!";

			if(strlen($msg_erro) > 0){
				echo "<div class='lp_msg_erro'>$msg_erro</div>";
			}else{
				$res = pg_query($con,$sql);

				if(pg_numrows ($res) > 0){?>
					<table width='100%' border='0' cellspacing='1' cellspading='0' class='lp_tabela' id='gridRelatorio'>
						<thead>
							<tr>
								<th width="20%">Código</th>
								<th width="40%">Nome</th>
								<th width="10%">Voltagem</th>
								<th width="10%">Status</th>
							</tr>
						</thead>
						<tbody><? 
							for ($i = 0 ; $i < pg_num_rows($res); $i++) {
								$produto			= trim(pg_result($res, $i, 'produto'));
								$linha				= trim(pg_result($res, $i, 'linha'));
								$nome_comercial		= trim(pg_result($res, $i, 'nome_comercial'));
								$voltagem			= trim(pg_result($res, $i, 'voltagem'));
								$referencia			= trim(pg_result($res, $i, 'referencia'));
								$descricao			= trim(pg_result($res, $i, 'descricao'));
								$referencia_fabrica	= trim(pg_result($res, $i, 'referencia_fabrica'));
								$garantia			= trim(pg_result($res, $i, 'garantia'));
								$ativo			= trim(pg_result($res, $i, 'ativo'));
								$valor_troca		= trim(pg_result($res, $i, 'valor_troca'));
								$troca_garantia		= trim(pg_result($res, $i, 'troca_garantia'));
								$troca_faturada		= trim(pg_result($res, $i, 'troca_faturada'));
								$mobra			= str_replace(".",",",trim(pg_result($res,$i,mao_de_obra)));
								$off_line			= trim(pg_result($res,$i,off_line));
								$capacidade		= trim(pg_result($res,$i,capacidade));
								$ipi				= trim(pg_result($res,$i,ipi));
								$troca_obrigatoria	= trim(pg_result($res, $i, 'troca_obrigatoria'));

								$sql_idioma = "SELECT * FROM tbl_produto_idioma WHERE produto = $produto AND upper(idioma) = '$sistema_lingua'";
								$res_idioma = pg_exec($con,$sql_idioma);

								if (pg_numrows($res_idioma) >0)
									$descricao  = trim(pg_result($res_idioma, 0, 'descricao'));

								$descricao			= str_replace ('"','',$descricao);
								$descricao			= str_replace("'","",$descricao);
								$descricao			= str_replace("''","",$descricao);

								$mativo = ($ativo == 't') ?  " ATIVO " : " INATIVO ";

								if (strlen($ipi)>0 AND $ipi != "0") {
									$valor_troca = $valor_troca * (1 + ($ipi /100));
								}

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

								if ($login_fabrica == 3) {
				                  if (strrpos($exibe, "helpdesk_cadastrar.php") === false ) {
				                    $onclick = "onclick= \"javascript: window.parent.retorna_dados_produto('$produto','$linha','$nome_comercial','$voltagem','$referencia','$descricao','$referencia_fabrica','$garantia','$ativo','$valor_troca','$troca_garantia','$troca_faturada','$mobra','$off_line','$capacidade','$ipi','$troca_obrigatoria','$posicao','$origem'); window.parent.Shadowbox.close();\"";
				                  }else{
				                    $onclick = "onclick= \"javascript: window.parent.retorna_dados_produto('$produto','$linha','$nome_comercial','$voltagem','$referencia','$descricao','$referencia_fabrica','$garantia','$ativo','$valor_troca','$troca_garantia','$troca_faturada','$mobra','$off_line','$capacidade','$ipi','$troca_obrigatoria','$posicao','$origem'); window.parent.busca_defeitos_produto(); window.parent.Shadowbox.close();\"";
				                  }
				                  
				                }elseif ($login_fabrica == 1 AND $troca == TRUE) {
				                  $onclick = "onclick= \"javascript: window.parent.retorna_dados_produto('$produto','$linha','$nome_comercial','$voltagem','$referencia','$descricao','$referencia_fabrica','$garantia','$ativo','$valor_troca','$troca_garantia','$troca_faturada','$mobra','$off_line','$capacidade','$ipi','$troca_obrigatoria','$posicao','$origem'); if(window.parent.document.title.toLowerCase().indexOf('troca') > 0) {window.parent.limpa_troca();} window.parent.Shadowbox.close();\"";
				                }else{
				                  $onclick = "onclick= \"javascript: window.parent.retorna_dados_produto('$produto','$linha','$nome_comercial','$voltagem','$referencia','$descricao','$referencia_fabrica','$garantia','$ativo','$valor_troca','$troca_garantia','$troca_faturada','$mobra','$off_line','$capacidade','$ipi','$troca_obrigatoria','$posicao','$origem'); window.parent.Shadowbox.close();\"";
				                }

								$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
								echo "<tr style='background: $cor' $onclick>";
									echo "<td>".verificaValorCampo($referencia)."</td>";
									echo "<td>".verificaValorCampo($descricao)."</td>";
									echo "<td>".verificaValorCampo($voltagem)."</td>";
									echo "<td>".verificaValorCampo($mativo)."</td>";
								echo "</tr>";
							}
						echo "</tbody>";
					echo "</table>";
				}else{
					echo "<div class='lp_msg_erro'>Nehum resultado encontrado</div>";
				}
			}?>
	</body>
</html>
