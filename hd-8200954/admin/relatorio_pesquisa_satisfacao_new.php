<?php
/**
 *	@description Relatorio Pesquisa de Satisfação - HD 1764897
 *  @author Guilherme Monteiro.
 **/
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include "autentica_admin.php";
include 'funcoes.php';


if ( $_POST["btn_acao"] == "submit") {

	$data_inicial 			= $_POST["data_inicial"];
	$data_final 				= $_POST["data_final"];
	$produto_referencia	= $_POST["produto_referencia"];
	$produto_descricao	= $_POST["produto_descricao"];
	$atendente 					= $_POST["atendente"];
	$estado_posto 			= $_POST["estado"];
	$pesquisa 					= $_POST["pesquisa"];

	if (!strlen($data_inicial) or !strlen($data_final)) {
		$msg_erro["msg"][]    = "Preencha os campos obrigatórios";
		$msg_erro["campos"][] = "data";
	} else {
		list($di, $mi, $yi) = explode("/", $data_inicial);
		list($df, $mf, $yf) = explode("/", $data_final);

		if (!checkdate($mi, $di, $yi) or !checkdate($mf, $df, $yf)) {
			$msg_erro["msg"][]    = "Data Inválida";
			$msg_erro["campos"][] = "data";
		} else {
			$aux_data_inicial = "{$yi}-{$mi}-{$di}";
			$aux_data_final   = "{$yf}-{$mf}-{$df}";

			if (strtotime($aux_data_final) < strtotime($aux_data_inicial)) {
				$msg_erro["msg"][]    = "Data Final não pode ser menor que a Data Inicial";
				$msg_erro["campos"][] = "data";
			}else{
				if (strtotime("$aux_data_inicial + 1 month" ) < strtotime($aux_data_final)){
				 	$msg_erro["msg"][] = 'O intervalo entre as datas não pode ser maior que um mês.';
				 	$msg_erro["campos"][] = "data";
				}
			}
		}
	}

	if (strlen($produto_referencia) > 0 or strlen($produto_descricao) > 0){
		$sql = "SELECT produto
				FROM tbl_produto
				WHERE fabrica_i = {$login_fabrica}
				AND (
            	(UPPER(referencia) = UPPER('{$produto_referencia}'))
              OR
              (UPPER(descricao) = UPPER('{$produto_descricao}'))
            )";
		$res = pg_query($con ,$sql);

		if (!pg_num_rows($res)) {
			$msg_erro["msg"][]    = "Produto não encontrado";
			$msg_erro["campos"][] = "produto";
		} else {
			$produto = pg_fetch_result($res, 0, "produto");
		}
	}

	if(!strlen($pesquisa)){
		$msg_erro["msg"][]    = "Selecione uma pesquisa";
		$msg_erro["campos"][] = "pesquisa";
	}

	if(!count($msg_erro["msg"])){

		if(strlen($produto) > 0){
			$cond_produto = "AND tbl_os.produto = $produto";
		}

		if(strlen($atendente) > 0){
			$cond_atendente = "AND tbl_admin.admin = $atendente";
		}

		if(strlen($estado_posto) > 0){
			$cond_estado_posto = "AND UPPER (tbl_posto_fabrica.contato_estado) = UPPER('$estado_posto')";
			//$join_posto				 = "JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = $login_fabrica";
		}

		$sql = "SELECT 
							tbl_admin.nome_completo AS nome_admin,
							tbl_admin.admin AS admin,
							tbl_posto.nome AS nome_posto,
							tbl_posto.posto,
							tbl_resposta.os,
							tbl_os.consumidor_estado,
							tbl_resposta.resposta,
							tbl_pergunta.pergunta,
							tbl_pergunta.descricao AS pergunta_descricao,
							LOWER(TO_ASCII(tbl_tipo_resposta_item.descricao, 'LATIN9')) AS tipo_resposta_item
						INTO TEMP tmp_pesquisa_satisfacao_$login_admin
						FROM tbl_resposta
						INNER JOIN tbl_os ON tbl_os.os = tbl_resposta.os AND tbl_os.fabrica = $login_fabrica
						INNER JOIN tbl_tipo_resposta_item ON tbl_tipo_resposta_item.tipo_resposta_item = tbl_resposta.tipo_resposta_item
						INNER JOIN tbl_pesquisa ON tbl_pesquisa.pesquisa = tbl_resposta.pesquisa AND tbl_pesquisa.fabrica = $login_fabrica
						INNER JOIN tbl_pergunta ON tbl_pergunta.pergunta = tbl_resposta.pergunta AND tbl_pergunta.fabrica = $login_fabrica
						INNER JOIN tbl_tipo_resposta ON tbl_tipo_resposta.tipo_resposta = tbl_pergunta.tipo_resposta AND tbl_tipo_resposta.fabrica = $login_fabrica
						INNER JOIN tbl_admin ON tbl_admin.admin = tbl_resposta.admin AND tbl_admin.fabrica = $login_fabrica
						INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
						INNER JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
						WHERE tbl_os.data_abertura BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'
						AND tbl_pesquisa.pesquisa = $pesquisa
						AND tbl_tipo_resposta.tipo_descricao = 'radio'
						$cond_produto
						$cond_atendente
						$cond_estado_posto
						ORDER BY tbl_admin.nome_completo";

		$resSubmit = pg_query($con,$sql);
	}

	#### EXCEL ####
	if ($_POST["gerar_excel"]) {
		
		$sql = "SELECT nome_admin, admin, COUNT(DISTINCT os) AS total_os, COUNT(resposta) AS total_resposta
						FROM tmp_pesquisa_satisfacao_$login_admin
						GROUP BY nome_admin, admin
						ORDER BY nome_admin";
		$resSubmit = pg_query($con, $sql);

		if (pg_num_rows($resSubmit) > 0) {

			$rows = pg_num_rows($resSubmit);

			$data = date("d-m-Y-H:i");
			$fileName = "relatorio_pesquisa_satisfacao-{$data}.xls";
			$file = fopen("/tmp/{$fileName}", "w");

			$thead1 .="  
				<table border='1'>
					<thead>
						<tr>
							<th colspan='7' bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;' >
								RELATORIO PESQUISA SATISFAÇÃO 							
							</th>
						</tr>
						<tr>
							<th colspan='2' bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Inspetor Técnico</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Estados</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Total OS</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Total Resposta</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Sim / Não</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Satisfação</th>
						</tr>
					</thead>
					<tbody>
			";
			fwrite($file, $thead1);

			for ($i = 0; $i < $rows; $i++) {
				$inspetor 			= pg_fetch_result($resSubmit, $i,'nome_admin');
				$total_os 			= pg_fetch_result($resSubmit, $i,'total_os');
				$total_resposta = pg_fetch_result($resSubmit, $i,'total_resposta');
				$admin 					= pg_fetch_result($resSubmit, $i, 'admin');

				$sql = "SELECT DISTINCT tmp_pesquisa_satisfacao_$login_admin.consumidor_estado FROM tmp_pesquisa_satisfacao_$login_admin WHERE tmp_pesquisa_satisfacao_$login_admin.admin = $admin";
				$res_estado = pg_query($con,$sql);
				$rows_estados = pg_num_rows($res_estado);

				$estados_admin = array();
				for ($j = 0; $j < $rows_estados; $j++) {
					$estados_admin[] = pg_result($res_estado,$j,0);
				}

				$sql = "SELECT COUNT(tmp_pesquisa_satisfacao_$login_admin.resposta) 
								FROM tmp_pesquisa_satisfacao_$login_admin
								WHERE admin = $admin AND tipo_resposta_item = 'nao'";
				$res_descricao = pg_query($con,$sql);
				$descricao_nao = pg_fetch_result($res_descricao,0,0);

				$sql = "SELECT COUNT(tmp_pesquisa_satisfacao_$login_admin.resposta) 
								FROM tmp_pesquisa_satisfacao_$login_admin 
								WHERE admin = $admin AND tipo_resposta_item = 'sim'";
				$res_descricao = pg_query($con,$sql);
				$descricao_sim = pg_fetch_result($res_descricao,0,0);

				$body1 ="  
					<tr>
						<th colspan='2' nowrap align='left' valign='top'>{$inspetor}</th>
						<td nowrap align='center' valign='top'>".implode(', ',$estados_admin)."</td>
						<td nowrap align='center' valign='top'>{$total_os}</td>
						<td nowrap align='center' valign='top'>{$total_resposta}</td>
						<td nowrap align='center' valign='top'>{$descricao_sim} / {$descricao_nao}</td>
						<td nowrap align='center' valign='top'>".number_format ( $descricao_sim * 100 / ($total_resposta), 2, ',', '' )." %</td>
					</tr>
					<tr>
						<td>&nbsp;</td>
						<td bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Posto</td>
						<td bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Estados</td>
						<td bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Total OS</td>
						<td bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Total Resposta</td>
						<td bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Sim / Não</td>
						<td bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Satisfação</td>
					</tr>
				";
				fwrite($file, $body1);
				$body1 ="";
				fwrite($file, $body1);
				
				$sql = "SELECT nome_posto, posto, COUNT(DISTINCT os) AS total_os, COUNT(resposta) AS total_resposta
								FROM tmp_pesquisa_satisfacao_$login_admin 
								WHERE admin = $admin
								GROUP BY nome_posto, posto";
				$res_posto = pg_query($con,$sql);
				$rows2 = pg_num_rows($res_posto);

				for ($k = 0; $k < $rows2; $k++) {
					$posto_nome 					= pg_fetch_result($res_posto,$k,'nome_posto');
					$total_os_posto 			= pg_fetch_result($res_posto,$k,'total_os');
					$total_res_posto 			= pg_fetch_result($res_posto,$k,'total_resposta');
					$posto								= pg_fetch_result($res_posto,$k,'posto');

					$sql = "SELECT DISTINCT tmp_pesquisa_satisfacao_$login_admin.consumidor_estado 
									FROM tmp_pesquisa_satisfacao_$login_admin
									WHERE admin = $admin
									AND posto = $posto";
					$res_estado_do_posto = pg_query($con,$sql);
					$estado_do_posto = pg_fetch_result($res_estado_do_posto,0,0);

					$sql = "SELECT COUNT(tmp_pesquisa_satisfacao_$login_admin.resposta) 
									FROM tmp_pesquisa_satisfacao_$login_admin 
									WHERE admin = $admin 
									AND posto = $posto
									AND tipo_resposta_item = 'sim'";
					$res2_descricao = pg_query($con,$sql);
					$descricao_sim_posto = pg_fetch_result($res2_descricao,0,0);

					$sql = "SELECT COUNT(tmp_pesquisa_satisfacao_$login_admin.resposta) 
									FROM tmp_pesquisa_satisfacao_$login_admin 
									WHERE admin = $admin 
									AND posto = $posto
									AND tipo_resposta_item = 'nao'";
					$res3_descricao = pg_query($con,$sql);
					$descricao_nao_posto = pg_fetch_result($res3_descricao,0,0);

					$body2="  
						<tr>
							<td>&nbsp;</td>
							<td nowrap align='left' valign='top'>{$posto_nome}</td>
							<td nowrap align='center' valign='top'>{$estado_do_posto}</td>
							<td nowrap align='center' valign='top'>{$total_os_posto}</td>
							<td nowrap align='center' valign='top'>{$total_res_posto}</td>
							<td nowrap align='center' valign='top'>{$descricao_sim_posto} / {$descricao_nao_posto}</td>
							<td nowrap align='center' valign='top'>".number_format ( $descricao_sim_posto * 100 / ($total_res_posto), 2, ',', '' )."'&nbsp; %</td>
						</tr>
					";
					fwrite($file, $body2);
					$body2 ="";
					fwrite($file, $body2);
						
				}
			}

			$body3 ="  
				</tbody> </table>
			";
			fwrite($file, $body3);
			$colspan = 7;
			if ($login_fabrica == 30) {
				$colspan = 9;
				$theadX = "
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Completamente Satisfeito / Completamente Insatisfeito <br />
Insatisfeito / Satisfeito</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Total</th>
						";
			}
			$thead4 ="  
				<table border='1'>
						<thead>
							<tr>
								<th colspan='{$colspan}' bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;' >
									TOTAIS
								</th>
							</tr>
							<tr>
								<th colspan='4' bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Perguntas</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Total Respostas</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Total Sim / Não</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Total Satisfação</th>
								{$theadX}
							</tr>
						</thead>
					</tbody>
			";
			fwrite($file, $thead4);

			### PERGUNTA
			$sql = "SELECT pergunta,
							pergunta_descricao
							FROM tmp_pesquisa_satisfacao_$login_admin
							WHERE admin = $admin
							AND posto = $posto
							GROUP BY pergunta, pergunta_descricao
							ORDER BY pergunta_descricao";
			$res_pergunta 	= pg_query($con,$sql);
			$rows_pergunta 	= pg_num_rows($res_pergunta);

			####
			for ($i= 0; $i < $rows_pergunta; $i++) { 
				$id_pergunta = pg_fetch_result($res_pergunta,$i,"pergunta");
 
				### RESPOSTAS
				$sql = "SELECT pergunta,
								pergunta_descricao,
								COUNT(resposta) AS geral_resposta
								FROM tmp_pesquisa_satisfacao_$login_admin 
								GROUP BY pergunta, pergunta_descricao
								ORDER BY pergunta_descricao";
				$res_geral 	= pg_query($con,$sql);
				$total_geral_os = pg_fetch_result($res_geral,$i,"geral_resposta");
				###

				### RESPOSTA SIM
				$sql = "SELECT COUNT(tmp_pesquisa_satisfacao_$login_admin.resposta) AS resposta_sim,
								pergunta_descricao
								FROM tmp_pesquisa_satisfacao_$login_admin
								WHERE tipo_resposta_item = 'sim' AND pergunta = $id_pergunta
								GROUP BY pergunta_descricao
								ORDER BY pergunta_descricao";
				$res_geral_descricao_sim = pg_query($con,$sql);
				$total_geral_sim = pg_fetch_result($res_geral_descricao_sim,0,'resposta_sim');
				###

				### RESPOSTA NÃO
				$sql = "SELECT COUNT(tmp_pesquisa_satisfacao_$login_admin.resposta) AS resposta_nao,
								pergunta_descricao
								FROM tmp_pesquisa_satisfacao_$login_admin
								WHERE tipo_resposta_item = 'nao' AND pergunta = $id_pergunta
								GROUP BY pergunta_descricao
								ORDER BY pergunta_descricao";
				$res_geral_descricao_nao = pg_query($con,$sql);		
				$total_geral_nao = pg_fetch_result($res_geral_descricao_nao,0,'resposta_nao');
				###

				### TOTAL GERAL RESPOSTA
				$sql = "SELECT COUNT(tmp_pesquisa_satisfacao_$login_admin.resposta)
								FROM tmp_pesquisa_satisfacao_$login_admin
								";
				$res_todas_respostas = pg_query($con,$sql);
				$total_todas_os = pg_fetch_result($res_todas_respostas,0,0);
				###

				### TOTAL GERAL SIM
				$sql = "SELECT COUNT(tmp_pesquisa_satisfacao_$login_admin.resposta)
								FROM tmp_pesquisa_satisfacao_$login_admin
								WHERE tipo_resposta_item = 'sim'";
				$res_sim = pg_query($con,$sql);
				$cont_sim = pg_fetch_result($res_sim,0,0);
				###

				### TOTAL GERAL NÃO
				$sql = "SELECT COUNT(tmp_pesquisa_satisfacao_$login_admin.resposta)
								FROM tmp_pesquisa_satisfacao_$login_admin
								WHERE tipo_resposta_item = 'nao'";
				$res_nao = pg_query($con,$sql);
				$cont_nao = pg_fetch_result($res_nao,0,0);
				###

				$descricao_pergunta = pg_fetch_result($res_pergunta,$i,'pergunta_descricao');
				if ($login_fabrica == 30) {
					$countCompleSatisfeito    	= 0; 
					$countSatisfeito    		= 0;
					$countCompleInSatisfeito    = 0;
					$countInSatisfeito    		= 0; 

					$sqlSatis = "SELECT COUNT(tmp_pesquisa_satisfacao_$login_admin.resposta), pergunta_descricao, tipo_resposta_item
								   FROM tmp_pesquisa_satisfacao_$login_admin
								  WHERE tipo_resposta_item ilike '%satisf%'
								  AND pergunta = $id_pergunta
						       GROUP BY pergunta_descricao, tipo_resposta_item";
					$resSatis = pg_query($con, $sqlSatis);

					while($rowSatis = pg_fetch_array($resSatis)) {

						if (in_array($rowSatis["tipo_resposta_item"], array("completamente   satisfeito", "completamente satisfeito"))) {
							$countCompleSatisfeito    = $rowSatis["count"];
						}
						if ($rowSatis["tipo_resposta_item"] == "satisfeito") {
							$countSatisfeito    = $rowSatis["count"];
						}
						if ($rowSatis["tipo_resposta_item"] == "completamente insatisfeito") {
							$countCompleInSatisfeito    = $rowSatis["count"];
						}
						if ($rowSatis["tipo_resposta_item"] == "insatisfeito") {
							$countInSatisfeito    = $rowSatis["count"];
						}

						if (in_array($rowSatis["tipo_resposta_item"], array("completamente   satisfeito", "completamente satisfeito"))  || $rowSatis["tipo_resposta_item"] == "satisfeito") {
							$countTotalSatisfeito    += $rowSatis["count"];
						} 

						if ($rowSatis["tipo_resposta_item"] == "completamente insatisfeito" || $rowSatis["tipo_resposta_item"] == "insatisfeito") {
							$countTotalInSatisfeito  += $rowSatis["count"];
						}

					}
				}
				if ($login_fabrica == 30) {
					$bodyX4 = '<td nowrap class="tac" valign="top">
					'.$countCompleSatisfeito.'&nbsp;/&nbsp;'.$countCompleInSatisfeito.'
					&nbsp;/&nbsp;'.$countInSatisfeito.'&nbsp;/&nbsp;'.$countSatisfeito.'
					</td>
					<td class="tac" nowrap valign="top">'.number_format($countTotalSatisfeito*100/($total_geral_os), 2, ',', '' ).'&nbsp; %</td>';
		  		}
		  		$new_total_sim = (empty($total_geral_sim)) ? 0 : $total_geral_sim;
				$new_total_nao = (empty($total_geral_nao)) ? 0 : $total_geral_nao;

				$body4 ="  
					<tr>
						<td colspan='4' >{$descricao_pergunta}</td>
						<td nowrap align='center' valign='top'>{$total_geral_os}</td>
						<td nowrap align='center' valign='top'>{$new_total_sim} / {$new_total_nao}</td>
						<td nowrap align='center' valign='top'>".number_format ( $total_geral_sim * 100 / ($total_geral_os), 2, ',', '' )." %</td>
						{$bodyX4}
				 </tr>";

				fwrite($file, $body4);
				$bodyX4 ="";
				$body4 ="";
				fwrite($file, $body4);
			}
			if ($login_fabrica == 30) {
				$bodyX5 = "
					<th nowrap align='center' valign='top'>
						".$countTotalSatisfeito." / ".$countTotalInSatisfeito."
					</th>
					<th nowrap align='center' valign='top'>".number_format( $countTotalSatisfeito * 100 / ($total_todas_os), 2, ',', '' )." %</th>";
			}
			$body5 ="  
						<tr>
							<th colspan='4' nowrap align='center' valign='top'>Total Pesquisado</th>
							<th nowrap align='center' valign='top'>{$total_todas_os}</th>
							<th nowrap align='center' valign='top'>{$cont_sim} / {$cont_nao}</th>
							<th nowrap align='center' valign='top'>".number_format ( $cont_sim * 100 / ($total_todas_os), 2, ',', '' )."%</th>
							{$bodyX5}
						</tr>	
					</tbody>
				</table>
			";
			fwrite($file, $body5);

			fclose($file);

			if (file_exists("/tmp/{$fileName}")) {
				system("mv /tmp/{$fileName} xls/{$fileName}");

				echo "xls/{$fileName}";
			}
		}
		exit;	
	}
	#### FIM EXCEL ####	

	//** PERGUNTAS/RESPOSTAS DO POSTO **//
	if (in_array($login_fabrica, [30])) {
		$array_perg_resp_posto = [];
		$perg_resp_table       = '';

		//** EXCEL **//
		$data          = date("d-m-Y-H:i");
		$fileName_perg = "relatorio_satisfacao_pergunta-{$data}.xls";
		$file_perg     = fopen("/tmp/{$fileName_perg}", "w");


		$thead_perg = '<table class="table table-bordered table-large table-fixed" border="1">
					  		<thead>
						  		<tr class="titulo_coluna">
									<th colspan="2">RELATÓRIO SATISFAÇÃO POR PERGUNTA</th>
								</tr>
							</thead>';	
		fwrite($file_perg, $thead_perg);

		echo '<div id="pergunta-resposta-posto" style="display: none !important;">
				<table class="table table-bordered table-large table-fixed">
				  	<thead>
				  		<tr class="titulo_coluna">
							<th colspan="5">PERGUNTAS E RESPOSTAS</th>
						</tr>
					</thead>';

		$sql = "SELECT nome_admin, admin FROM tmp_pesquisa_satisfacao_$login_admin GROUP BY nome_admin, admin ORDER BY nome_admin";
		$resSubmit = pg_query($con, $sql);

		if (pg_num_rows($resSubmit) > 0) {
			for ($i = 0; $i < pg_num_rows($resSubmit); $i++) {
				$admin = pg_fetch_result($resSubmit, $i, 'admin');

				$sql = "SELECT nome_posto, posto, COUNT(resposta) AS total_resposta
								FROM tmp_pesquisa_satisfacao_$login_admin 
								WHERE admin = $admin
								GROUP BY nome_posto, posto";
				$res_posto = pg_query($con,$sql);
				$rows2 = pg_num_rows($res_posto);

				for ($k = 0; $k < $rows2; $k++) {
					echo "<tbody id='pergunta-resposta-posto-".$posto."' class='hide-all' style='display: none;'>";

					$posto_nome = pg_fetch_result($res_posto,$k,'nome_posto');
					$posto      = pg_fetch_result($res_posto,$k,'posto');
				
					//** PERGUNTA **//
					$sql = "SELECT pergunta,
									pergunta_descricao
									FROM tmp_pesquisa_satisfacao_$login_admin
									WHERE admin = $admin
									GROUP BY pergunta, pergunta_descricao
									ORDER BY pergunta_descricao";
					$res_pergunta 	= pg_query($con,$sql);
					$rows_pergunta 	= pg_num_rows($res_pergunta);
					$arr_oss        = array();

					fwrite($file_perg, "<tbody>");

					for ($p= 0; $p < $rows_pergunta; $p++) { 
					    $id_pergunta   = pg_fetch_result($res_pergunta,$p,"pergunta");
					    $pergunta_desc = pg_fetch_result($res_pergunta,$p,"pergunta_descricao");
	
						//** TOTAL GERAL RESPOSTA **//
						$sql = "SELECT COUNT(tmp_pesquisa_satisfacao_$login_admin.resposta) AS geral_resposta
										FROM tmp_pesquisa_satisfacao_$login_admin
										WHERE pergunta = $id_pergunta
										and posto = $posto";
						$res_geral 	= pg_query($con,$sql);
						$total_geral_os = pg_fetch_result($res_geral,0,"geral_resposta");
					
						//** TOTAL GERAL SIM **//
						$sql = "SELECT COUNT(tmp_pesquisa_satisfacao_$login_admin.resposta) AS resposta_sim
										FROM tmp_pesquisa_satisfacao_$login_admin
										WHERE tipo_resposta_item = 'sim' 
										AND pergunta = $id_pergunta
										AND posto = $posto";
						$res_geral_descricao_sim = pg_query($con,$sql);
						$total_geral_sim = pg_fetch_result($res_geral_descricao_sim,0,'resposta_sim');
						
						//** TOTAL GERAL NÃO **//
						$sql = "SELECT COUNT(tmp_pesquisa_satisfacao_$login_admin.resposta) AS resposta_nao
										FROM tmp_pesquisa_satisfacao_$login_admin
										WHERE tipo_resposta_item = 'nao' 
										AND pergunta = $id_pergunta
										AND posto = $posto";
						$res_geral_descricao_nao = pg_query($con,$sql);		
						$total_geral_nao = pg_fetch_result($res_geral_descricao_nao,0,'resposta_nao');

						//** RESPOSTA **//
				        $sql = "SELECT tmp.pergunta_descricao, tmp.tipo_resposta_item, tmp.os, p.descricao, f.descricao AS familia, TO_CHAR(r.data_input, 'DD/MM/YYYY HH24:MI') AS data_resposta
						   		FROM tmp_pesquisa_satisfacao_$login_admin AS tmp
							   		INNER JOIN tbl_os AS os ON os.os = tmp.os and os.posto = $posto
									INNER JOIN tbl_produto AS p ON p.produto = os.produto
									INNER JOIN tbl_familia as f ON f.familia = p.familia
									INNER JOIN tbl_resposta AS r ON r.resposta = tmp.resposta
						  		WHERE tmp.pergunta = $id_pergunta
							  		AND tmp.admin = $admin
									AND tmp.posto = $posto
					       		GROUP BY tmp.pergunta_descricao, tmp.tipo_resposta_item, tmp.os, p.descricao, f.descricao, data_resposta
					       		ORDER BY tmp.OS";
						$res_geral 	    = pg_query($con,$sql);
						$rows_resposta 	= pg_num_rows($res_geral);

						for ($r=0; $r < $rows_resposta; $r++) {
							$resposta_desc = pg_fetch_result($res_geral,$r,"tipo_resposta_item");
							$data_resposta = pg_fetch_result($res_geral,$r,"data_resposta");
							$produto_des   = pg_fetch_result($res_geral,$r,"descricao");
							$produto_fam   = pg_fetch_result($res_geral,$r,"familia");
							$os            = pg_fetch_result($res_geral,$r,"os");

							if (!in_array($os, $arr_oss)) {
								echo "<tr>
										<td colspan='6' class='titulo_coluna'>
											<h5 style='text-align: center;'> OS: ".$os."</h5>
										</td>
										<tr class='titulo_coluna collapse' id='perg-resp-os-".$os."'> 
											<th class='text15'>Produto</th>
											<th class='text15'>Família Produto</th>
											<th class='text15'>Pergunta</th>
											<th class='text15'>Resposta</th>
											<th class='text15'>Data Resposta</th>
										</tr>";
								
								$echo1 = "<tr><td>&nbsp;</td></tr>
									<tr>
										<td colspan='2' class='titulo_coluna'>
											<h5 style='text-align: center;'><b>".$posto_nome."</b></h5>
										</td>
										<tr class='titulo_coluna'> 
											<th class='text15'>Pergunta</th>
											<th class='text15'>Satisfação</th>
										</tr>";

								fwrite($file_perg, $echo1);
								$echo1 = "";
								fwrite($file_perg, $echo1);
							}

								echo "<tr class='perg-resp-os-".$os."'>
											<td>".$produto_des."</td>
											<td>".$produto_fam."</td>
											<td class='center'>".$pergunta_desc."</td>
											<td class='text-center'>".$resposta_desc."</td>
											<td>".$data_resposta."</td>
										</tr>";

								$echo2 = "<tr>
											<td class='center'>".$pergunta_desc."</td>
											<td>" . number_format ( $total_geral_sim * 100 / ($total_geral_os), 2, ',', '' ) . " %</td>
										</tr>";

								fwrite($file_perg, $echo2);
								$echo2 = "";
								fwrite($file_perg, $echo2);

							if (!in_array($os, $arr_oss)) {
								$echo3 = "</tr>";
								array_push($arr_oss, $os);

								echo $echo3;
								fwrite($file_perg, $echo3);
								$echo3 = "";
								fwrite($file_perg, $echo3);
							}
						}
					}

					$echo4 = "</tbody>";
					echo $echo4;
					fwrite($file_perg, $echo4);
				}
			}
		}

		$echo5 = "</table>
			</tbody>
		</div>";

		echo $echo5;
		fwrite($file_perg, $echo5);

		if (file_exists("/tmp/{$fileName_perg}")) {
			system("mv /tmp/{$fileName_perg} xls/{$fileName_perg}");
			$excel_perg = "<div class='btn_excel' style='width: 275px !important;'>
							<span><img src='imagens/excel.png' /></span>
							<span class='txt'>
								<a href='xls/{$fileName_perg}' target='_blank' style='text-decoration: none; color: #FFF;'>Excel - Satisfação por Pergunta</a>
								</span>
						</div><br />";
		}
	}
}

$layout_menu = "cadastro";
$title = "RELATÓRIO DE PESQUISA DE SATISFAÇÃO";
include "cabecalho_new.php";

$plugins = array(
"autocomplete",
"datepicker",
"shadowbox",
"mask",
"sorttable"
);

include("plugin_loader.php");

?>

<script language="javascript">
var hora = new Date();
var engana = hora.getTime();

$(function() {
	$.datepickerLoad(Array("data_final", "data_inicial"));
	$.autocompleteLoad(Array("produto", "peca", "posto"));
	Shadowbox.init();

	$("span[rel=lupa]").click(function () {
		$.lupa($(this));
	});
});

function retorna_produto (retorno) {
	$("#produto_referencia").val(retorno.referencia);
	$("#produto_descricao").val(retorno.descricao);
}

$().ready(function(){
	$(".table-more").hide();

	$(".expand").click(function(){
		var id = $(this).attr('id');
		$(".admin_"+id).toggle();
	});

	$(".expand_fam").click(function(){
		var fam = $(this).attr('data-fam');
		$(".fam_"+fam).toggle();
	});

	$(".show-pergunta-resposta-posto").click(function(){
		var posto = $(this).attr('data-posto');

		$(".hide-all").hide();
		$("#pergunta-resposta-posto-" + posto).show();

		var content = $("#pergunta-resposta-posto");

		Shadowbox.open({
            content: content.html(),
            player: "html",
            width: 1000,
            height: 600
        });

        setTimeout(function(){
			$(".html").css({
			 	overflow: 'auto'
		    });
		},1500); 
	});
});
</script>

<?php
if (count($msg_erro["msg"]) > 0) {
?>
  <div class="alert alert-error">
	<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
  </div>
<?php
}
?>
<!-- Monteiro Inicio -->
<div class="row">
<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
<div class='titulo_tabela '>Parâmetros de Pesquisa</div>
<br />

<div class='row-fluid'>
	<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='data_inicial'>Data Inicial</label>
				<div class='controls controls-row'>
					<div class='span4'>
						<h5 class='asteristico'>*</h5>
							<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=isset($_POST['data_inicial'])?$_POST['data_inicial'] : '' ?>">
					</div>
				</div>
			</div>
		</div>
	<div class='span4'>
		<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
			<label class='control-label' for='data_final'>Data Final</label>
			<div class='controls controls-row'>
				<div class='span4'>
					<h5 class='asteristico'>*</h5>
						<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?=isset($_POST['data_final'])?$_POST['data_final'] : ''?>" >
				</div>
			</div>
		</div>
	</div>
	<div class='span2'></div>
</div>

<div class='row-fluid'>
	<div class='span2'></div>
	<div class='span4'>
		<div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
			<label class='control-label' for='produto_referencia'>Ref. Produto</label>
			<div class='controls controls-row'>
				<div class='span7 input-append'>
					<input type="text" id="produto_referencia" name="produto_referencia" class='span12' maxlength="20" value="<?php echo $produto_referencia;?>" >
					<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
					<input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
				</div>
			</div>
		</div>
	</div>
	<div class='span4'>
		<div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
			<label class='control-label' for='produto_descricao'>Descrição Produto</label>
			<div class='controls controls-row'>
				<div class='span12 input-append'>
					<input type="text" id="produto_descricao" name="produto_descricao" class='span12' value="<?php echo $produto_descricao;?>" >
					<span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
					<input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
				</div>
			</div>
		</div>
	</div>
	<div class='span2'></div>
</div>

<div class='row-fluid'>
	<div class='span2'></div>
	<div class='span4'>
		<div class='control-group <?=(in_array("atendente", $msg_erro["campos"])) ? "error" : ""?>'>
			<label class='control-label' for='atendente'>Atendente</label>
			<div class='controls controls-row'>
				<div class='span4'>
					<select name="atendente" id="atendente">
						<option value=""></option>
						<?php
						$sql = "SELECT admin, login
								FROM tbl_admin
								WHERE fabrica = $login_fabrica
								AND ativo is true
								and (privilegios like '%call_center%' or privilegios like '*') order by login";
						$res = pg_query($con,$sql);

						foreach (pg_fetch_all($res) as $key) {
							$select_atendente = (isset($atendente) and ($atendente == $key ['admin']) ) ? "SELECTD": '' ;
						
						?>
						<option value="<?php echo $key['admin']?>" <?php echo $selected_atendente ?> >
							<?php echo $key['login']?>
						</option>
						<?
						}
						?>
					</select>
				</div>
			</div>
		</div>
	</div>
	<div class='span4'>
		<div class='control-group'>
			<label class='control-label' for='estado'>Estado do Posto</label>
			<div class='controls controls-row'>
				<div class='span4'>
					<?php
				  		$array_estado = array("AC"=>"AC - Acre","AL"=>"AL - Alagoas","AM"=>"AM - Amazonas",
				    					"AP"=>"AP - Amapá", "BA"=>"BA - Bahia", "CE"=>"CE - Ceará","DF"=>"DF - Distrito Federal",
					 					"ES"=>"ES - Espírito Santo", "GO"=>"GO - Goiás","MA"=>"MA - Maranhão","MG"=>"MG - Minas Gerais",
					    				"MS"=>"MS - Mato Grosso do Sul","MT"=>"MT - Mato Grosso", "PA"=>"PA - Pará","PB"=>"PB - Paraíba",
						  				"PE"=>"PE - Pernambuco","PI"=>"PI - Piauí","PR"=>"PR - Paraná","RJ"=>"RJ - Rio de Janeiro",
						    			"RN"=>"RN - Rio Grande do Norte","RO"=>"RO - Rondônia","RR"=>"RR - Roraima",
									 	"RS"=>"RS - Rio Grande do Sul", "SC"=>"SC - Santa Catarina","SE"=>"SE - Sergipe",
							    		"SP"=>"SP - São Paulo","TO"=>"TO - Tocantins");
					?>
					<select name="estado" id="estado">
						<option value=""></option>
						<?php
				    		foreach ($array_estado as $k => $v) {
						    	echo '<option value="'.$k.'"'.($estado == $k ? ' selected="selected"' : '').'>'.$v."</option>\n";
							}
						?>
					</select>
				</div>
				
			</div>
		</div>
	</div>
	<div class='span2'></div>
</div>

<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span8'>
			<div class='control-group'>
				<label class='control-label' for='pesquisa'>Pesquisa</label>
				<div class='controls controls-row'>
					<div class='span12'>
						<h5 class='asteristico'>*</h5>
						<select name="pesquisa" id="pesquisa" class="span12">
							<option value=""></option>
							<?php 
								$sql = "SELECT pesquisa,descricao
										FROM tbl_pesquisa
										WHERE fabrica = $login_fabrica";
								$res = pg_query($con,$sql);
								for ( $i = 0; $i < pg_num_rows($res); $i++ ) {
								
									$xpesquisa = pg_result($res,$i,'pesquisa');
									$xselected = $_POST['pesquisa'] == $xpesquisa ? 'selected' : '';
									$xdescricao= pg_result($res,$i,'descricao');
									echo '<option value="'.$xpesquisa.'" '.$xselected.'>'.$xdescricao.'</option>';
								
								}
							?>
						</select>
					</div>
				</div>
			</div>
		</div>
	</div>

	<p><br/>
		<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
		<input type='hidden' id="btn_click" name='btn_acao' value='' />
	</p><br/>
</form>

<?php 

if (isset($resSubmit)) {
	$sql = "SELECT nome_admin, admin, COUNT(DISTINCT os) AS total_os, COUNT(resposta) AS total_resposta
					FROM tmp_pesquisa_satisfacao_$login_admin
					GROUP BY nome_admin, admin";
	$resSubmit = pg_query($con, $sql);

	if (pg_num_rows($resSubmit) > 0) {

		$rows = pg_num_rows($resSubmit);
?>
		<table class="table table-bordered table-large table-fixed ">
	  	<thead>
	  		<tr class="titulo_coluna">
					<th colspan="2">Inspetor Técnico</th>
					<th >Estados</th>
					<th>Total OS</th>
					<th>Total Resposta</th>
					<th>Sim / Não</th>
					<th>Satisfação</th>
				</tr>
			</thead>
			<tbody>
<?					

		$THnbsp     = (in_array($login_fabrica, [30])) ? '' : '<th>&nbsp;</th>';
		$TDnbsp     = (in_array($login_fabrica, [30])) ? '' : '<td>&nbsp;</td>';
		$sortable   = (in_array($login_fabrica, [30])) ? true : false;
		$arr_admins = [];

		for ($i = 0; $i < $rows; $i++) {
			$inspetor 			= pg_fetch_result($resSubmit, $i,'nome_admin');
			$total_os 			= pg_fetch_result($resSubmit, $i,'total_os');
			$total_resposta = pg_fetch_result($resSubmit, $i,'total_resposta');
			$admin 					= pg_fetch_result($resSubmit, $i, 'admin');
		
			$sql = "SELECT DISTINCT tmp_pesquisa_satisfacao_$login_admin.consumidor_estado FROM tmp_pesquisa_satisfacao_$login_admin WHERE tmp_pesquisa_satisfacao_$login_admin.admin = $admin";
			$res_estado = pg_query($con,$sql);
			$rows_estados = pg_num_rows($res_estado);

			$estados_admin = array();
			
			for ($j = 0; $j < $rows_estados; $j++) {
				$estados_admin[] = pg_result($res_estado,$j,0);
			}

			$sql = "SELECT COUNT(tmp_pesquisa_satisfacao_$login_admin.resposta) 
							FROM tmp_pesquisa_satisfacao_$login_admin
							WHERE admin = $admin AND tipo_resposta_item = 'nao'";
			$res_descricao = pg_query($con,$sql);
			$descricao_nao = pg_fetch_result($res_descricao,0,0);

			$sql = "SELECT COUNT(tmp_pesquisa_satisfacao_$login_admin.resposta) 
							FROM tmp_pesquisa_satisfacao_$login_admin 
							WHERE admin = $admin AND tipo_resposta_item = 'sim'";
			$res_descricao = pg_query($con,$sql);
			$descricao_sim = pg_fetch_result($res_descricao,0,0);

			echo '<tr class="expand" id="'.$admin.'">
							<td colspan="2" align="left" style="cursor: pointer !important;">&nbsp;<img src="imagens/mais.gif" alt="Exibir" /> &nbsp; '.$inspetor.'</td>
				  		<td>'.(implode(', ',$estados_admin)).'&nbsp;</td>
							<td>'.$total_os.'</td>
							<td>'.$total_resposta.'</td>
							<td class="tac">'.$descricao_sim.'&nbsp;/&nbsp;'.$descricao_nao.'</td>
							<td>'.number_format ( $descricao_sim * 100 / ($total_resposta), 2, ',', '' ). '&nbsp; %</td>
			  	</tr>';

			if ($sortable) {
				echo "<tr> <td colspan='7'> <table class='sortable' width='100%'> <thead>";
			}

			echo'	<tr class="admin_'.$admin.'" style="display: none;">
								'.$THnbsp.'
								<th class="titulo_coluna">Posto</th>
								<th class="titulo_coluna">Estados</th>
								<th class="titulo_coluna">Total OS</th>
								<th class="titulo_coluna">Total Resposta</th>
								<th class="titulo_coluna">Sim / Não</th>
								<th class="titulo_coluna">Satisfação</th>
							</tr>';
			
			if ($sortable) {
				echo "</thead> <tbody>";
			}

			$sql = "SELECT nome_posto, posto, COUNT(DISTINCT os) AS total_os, COUNT(resposta) AS total_resposta
							FROM tmp_pesquisa_satisfacao_$login_admin 
							WHERE admin = $admin
							GROUP BY nome_posto, posto";
			$res_posto = pg_query($con,$sql);
			$rows2 = pg_num_rows($res_posto);

			for ($k = 0; $k < $rows2; $k++) {
				$posto_nome 					= pg_fetch_result($res_posto,$k,'nome_posto');
				$total_os_posto 			= pg_fetch_result($res_posto,$k,'total_os');
				$total_res_posto 			= pg_fetch_result($res_posto,$k,'total_resposta');
				$posto								= pg_fetch_result($res_posto,$k,'posto');
				//$estado_do_posto  		= pg_fetch_result($res_estado_do_posto, row, field)
						
				$sql = "SELECT DISTINCT tmp_pesquisa_satisfacao_$login_admin.consumidor_estado 
								FROM tmp_pesquisa_satisfacao_$login_admin
								WHERE admin = $admin
								AND posto = $posto";
				$res_estado_do_posto = pg_query($con,$sql);
				$estado_do_posto = pg_fetch_result($res_estado_do_posto,0,0);

				$sql = "SELECT COUNT(tmp_pesquisa_satisfacao_$login_admin.resposta) 
								FROM tmp_pesquisa_satisfacao_$login_admin 
								WHERE admin = $admin 
								AND posto = $posto
								AND tipo_resposta_item = 'sim'";
				$res2_descricao = pg_query($con,$sql);
				$descricao_sim_posto = pg_fetch_result($res2_descricao,0,0);
				
				$sql = "SELECT COUNT(tmp_pesquisa_satisfacao_$login_admin.resposta) 
								FROM tmp_pesquisa_satisfacao_$login_admin 
								WHERE admin = $admin 
								AND posto = $posto
								AND tipo_resposta_item = 'nao'";
				$res3_descricao      = pg_query($con,$sql);
				$descricao_nao_posto = pg_fetch_result($res3_descricao,0,0);
				$total_satisfacao    = number_format ( $descricao_sim_posto * 100 / ($total_res_posto), 2, ',', '' );

				if (in_array($login_fabrica, [30])):
					$postoPesqResp = "class='show-pergunta-resposta-posto' data-posto='".$posto."' data-admin='".$admin."' data-login-admin='".$login_admin."' style='cursor: pointer !important;'";
				else:
					$postoPesqResp = '';

				endif;

				echo'	<tr class="admin_'.$admin.'" style="display: none;">
 								'.$TDnbsp.'
 								<td '.$postoPesqResp.'><img src="imagens/mais.gif" alt="Exibir" /> &nbsp; '.$posto_nome.'</td>
 								<td>'.$estado_do_posto.'</td>
 								<td>'.$total_os_posto.'</td>
 								<td>'.$total_res_posto.'</td>
 								<td class="tac">'.$descricao_sim_posto.'&nbsp;/&nbsp;'.$descricao_nao_posto.'</td>
 								<td>'.$total_satisfacao. '&nbsp; %</td>
							</tr>';
			}

			if ($sortable) {
				echo "</tbody> </table>";
			}
		}
echo "</tbody> </table>";

//** SATISFAÇÃO POR FAMILIA **/
if (in_array($login_fabrica, [30])) {
	if (isset($resSubmit)) {
		$sql = "SELECT f.familia, tmp.os, f.descricao AS familia_desc, COUNT(tmp.resposta) AS total_resposta, p.descricao, pt.nome, pt.posto, pf.codigo_posto
		   		FROM tmp_pesquisa_satisfacao_$login_admin AS tmp
		   			INNER JOIN tbl_os AS os ON os.os = tmp.os
					INNER JOIN tbl_produto AS p ON p.produto = os.produto
					INNER JOIN tbl_familia as f ON f.familia = p.familia
					INNER JOIN tbl_resposta AS r ON r.resposta = tmp.resposta
					INNER JOIN tbl_posto AS pt ON pt.posto = tmp.posto
					INNER JOIN tbl_posto_fabrica AS pf ON pf.posto = tmp.posto AND pf.fabrica = $login_fabrica
	       		GROUP BY tmp.os, f.familia, p.descricao, pt.nome, pt.posto, pf.codigo_posto
	       		ORDER BY f.familia";
		$resSubmit = pg_query($con, $sql);
		$rows      = pg_num_rows($resSubmit);
		
		if ($rows > 0) {		
			//** EXCEL **//
			$data         = date("d-m-Y-H:i");
			$fileName_fam = "relatorio_satisfacao_familia-{$data}.xls";
			$file_fam     = fopen("/tmp/{$fileName_fam}", "w");
			$thead_fam    = '<table class="table table-bordered table-large table-fixed" border="1">
						  		<thead>
							  		<tr class="titulo_coluna">
										<th colspan="6">RELATÓRIO SATISFAÇÃO POR FAMILIA</th>
									</tr>
								</thead></table>';	
			fwrite($file_fam, $thead_fam);

			echo '<table class="table table-bordered table-large table-fixed ">
				  	<thead>
				  		<tr class="titulo_coluna">
							<th>Familia</th>
							<th>Total Resposta</th>
							<th>Sim / Não</th>
							<th>Satisfação</th>	
						</tr>
					</thead>
					<tbody>';


			$thead_fam2 = '<table class="table table-bordered table-large table-fixed ">
				  	<thead>
				  		<tr class="titulo_coluna">
							<th>Familia</th>
							<th>OS</th>
							<th>Total Resposta</th>
							<th>Sim / Não</th>
							<th>Satisfação</th>	
						</tr>
					</thead>
					<tbody>';
			fwrite($file_fam, $thead_fam2);

			$arr_familia = [];
			for ($r=0; $r < $rows; $r++) {
				$codigo_posto   = pg_fetch_result($resSubmit,$r,"codigo_posto");
				$postocod       = pg_fetch_result($resSubmit,$r,"posto");
				$posto_nome     = pg_fetch_result($resSubmit,$r,"nome");
				$produto_desc   = pg_fetch_result($resSubmit,$r,"descricao");
				$familia_desc   = pg_fetch_result($resSubmit,$r,"familia_desc");
				$familia        = pg_fetch_result($resSubmit,$r,"familia");
				$total_resposta = pg_fetch_result($resSubmit,$r,"total_resposta");
				$os             = pg_fetch_result($resSubmit,$r,"os");

				//** TOTAL GERAL RESPOSTA **//
				$sql = "SELECT COUNT(tmp.resposta) AS geral_resposta
							FROM tmp_pesquisa_satisfacao_$login_admin AS tmp
							INNER JOIN tbl_os AS os ON os.os = tmp.os
							INNER JOIN tbl_produto AS p ON p.produto = os.produto
							INNER JOIN tbl_familia as f ON f.familia = p.familia
						WHERE tmp.posto = $postocod
							AND f.familia = $familia";
				$res_geral 	= pg_query($con,$sql);
				$total_geral_os = pg_fetch_result($res_geral,0,"geral_resposta");
			
				//** TOTAL GERAL SIM **//
				$sql = "SELECT COUNT(tmp.resposta) AS resposta_sim
						FROM tmp_pesquisa_satisfacao_$login_admin AS tmp
							INNER JOIN tbl_os AS os ON os.os = tmp.os
							INNER JOIN tbl_produto AS p ON p.produto = os.produto
							INNER JOIN tbl_familia as f ON f.familia = p.familia
						WHERE tmp.tipo_resposta_item = 'sim' 
							AND tmp.posto = $postocod
							AND f.familia = $familia";
				$res_geral_descricao_sim = pg_query($con,$sql);
				$total_geral_sim = pg_fetch_result($res_geral_descricao_sim,0,'resposta_sim');
				$porcent         = $total_geral_sim * 100 / $total_geral_os;

				//** TOTAL GERAL NÃO **//	
				$sql = "SELECT COUNT(tmp.resposta) AS resposta_nao
						FROM tmp_pesquisa_satisfacao_$login_admin AS tmp
							INNER JOIN tbl_os AS os ON os.os = tmp.os
							INNER JOIN tbl_produto AS p ON p.produto = os.produto
							INNER JOIN tbl_familia as f ON f.familia = p.familia
						WHERE tmp.tipo_resposta_item = 'nao' 
							AND tmp.posto = $postocod
							AND f.familia = $familia";
				$res_geral_descricao_nao = pg_query($con,$sql);
				$total_geral_nao = pg_fetch_result($res_geral_descricao_nao,0,'resposta_nao');

				$tbody_fam = '<tr>
						<td style="cursor: pointer !important;" data-codigo="'.$codigo_posto.'" data-posto="'.$posto_nome.'" data-os="'.$os.'" class="btn-pesq-familia-shadowbox">&nbsp; <img src="imagens/mais.gif" alt="Exibir" /> &nbsp;'.$familia_desc.'</td>
						<td align="center">'.$os.'</td>
						<td align="center">'.$total_resposta.'</td>
						<td align="center">'.$total_geral_sim.' / '.$total_geral_nao.'</td>
						<td align="center">'.number_format($porcent, 2, ',', '').'%</td>
					</tr>';
				
				$arr_familia[$familia]['descricao']      = $familia_desc; 
				$arr_familia[$familia]['total_sim']     += $total_geral_sim;
				$arr_familia[$familia]['total_nao']     += $total_geral_nao;
				$arr_familia[$familia]['total_geral']   += $total_geral_os;
				$arr_familia[$familia]['total_resposta'] = $total_resposta;
				$arr_familia[$familia]['rows'][] = '<tr class="fam_'.$familia.'" style="display: none;">
													<td>'.$codigo_posto.' - '.$posto_nome.'</td>
													<td>'.$os.'</td>
													<td align="center">'.$total_resposta.'</td>
													<td align="center">'.$total_geral_sim.' / '.$total_geral_nao.'</td>
													<td align="center">'.number_format($porcent, 2, ',', '').'%</td>
												</tr>';
				//echo $tbody_fam;
				fwrite($file_fam, $tbody_fam);
			}

			//* ORGANIZANDO ARRAY POR PORCENTAGEM *//
			$new_arr_familia = [];

			foreach ($arr_familia as $id_familia => $familia) {

				$porcent = $familia['total_sim'] * 100 / $familia['total_geral'];
				$porcent = number_format($porcent, 2, ',', '');

				foreach ($familia['rows'] as $rows) {
					$new_arr_familia[$porcent]['rows'][] = $rows;
				}

				$new_arr_familia[$porcent]['familia']        = $id_familia;
				$new_arr_familia[$porcent]['descricao']      = $familia['descricao']; 
				$new_arr_familia[$porcent]['total_sim']      = $familia['total_sim'];
				$new_arr_familia[$porcent]['total_nao']      = $familia['total_nao'];
				$new_arr_familia[$porcent]['total_geral']    = $familia['total_geral'];
				$new_arr_familia[$porcent]['total_resposta'] = $familia['total_resposta'];
			}

			krsort($new_arr_familia);

			foreach ($new_arr_familia as $porcent => $familia) {
				$id_familia = $familia['familia'];

				echo "<tr class='expand_fam' data-fam='".$id_familia."'>
						<td>&nbsp;<img src='imagens/mais.gif' alt='Exibir' />&nbsp;".$familia['descricao']."</td>
						<td>".$familia['total_geral']."</td>
						<td>".$familia['total_sim']." / ".$familia['total_nao']."</td>
						<td>".$porcent." %</td>
				</tr>";

				echo '<tr class="fam_'.$id_familia.'" style="display: none;">
						<td colspan="4">
							<table class="table table-bordered table-large table-fixed sortable">
							  	<thead>
							  		<tr class="titulo_coluna">
										<th>Posto</th>
										<th>OS</th>
										<th>Total Resposta</th>
										<th>Sim / Não</th>
										<th>Satisfação</th>	
									</tr>
								</thead>
								<tbody>';
				
									foreach ($familia['rows'] as $tr) {
										echo $tr;
									}
			
				echo '			</tbody>
						</table>
					</td>
				</tr>';
			}

			$tbody_fam2 = '</tbody> 
						</table>';
			fwrite($file_fam, $tbody_fam2);
			fclose($file_fam);

			if (file_exists("/tmp/{$fileName_fam}")) {
				system("mv /tmp/{$fileName_fam} xls/{$fileName_fam}");
				$excel_fam = "<br /><div class='btn_excel' style='width: 275px !important;'>
							<span><img src='imagens/excel.png' /></span>
							<span class='txt'>
								<a href='xls/{$fileName_fam}' target='_blank' style='text-decoration: none; color: #FFF;'>Excel - Satisfação por Família</a>
								</span>
						</div><br />";
			}
		}
	}	
}	
?>
<table class="table table-bordered table-large table-fixed">
	<thead>
		<tr class="titulo_coluna">
			<?php 
				$colspan = 4;
				if ($login_fabrica == 30) {
					$colspan = 6;
				}
			?>
			<th colspan="<?php echo $colspan;?>">TOTAIS</th>
		</tr>
		<tr class="titulo_coluna">
			<th>Perguntas</th>
			<th>Total Respostas</th>
			<th>Total Sim / Não</th>
			<th>Total Satisfação</th>
			<?php if ($login_fabrica == 30) {?>
			<th nowrap>
				Completamente Satisfeito /  Completamente Insatisfeito <br>
				 Insatisfeito /  Satisfeito
			</th>
			<th nowrap>
				Total
			</th>
			<?php }?>
		</tr>
	</thead>
	<tbody>
<?
	### PERGUNTA
	$sql = "SELECT pergunta,
					pergunta_descricao
					FROM tmp_pesquisa_satisfacao_$login_admin
					WHERE admin = $admin
					AND posto = $posto
					GROUP BY pergunta, pergunta_descricao
					ORDER BY pergunta_descricao";
	$res_pergunta 	= pg_query($con,$sql);
	$rows_pergunta 	= pg_num_rows($res_pergunta);

	####
		for ($i= 0; $i < $rows_pergunta; $i++) { 
		    $id_pergunta = pg_fetch_result($res_pergunta,$i,"pergunta");
			
		### RESPOSTAS
		$sql = "SELECT pergunta,
						pergunta_descricao,
						COUNT(resposta) AS geral_resposta
						FROM tmp_pesquisa_satisfacao_$login_admin 
						GROUP BY pergunta, pergunta_descricao
						ORDER BY pergunta_descricao";
		$res_geral 	= pg_query($con,$sql);
		$total_geral_os = pg_fetch_result($res_geral,$i,"geral_resposta");
		###

		### RESPOSTA SIM
		$sql = "SELECT COUNT(tmp_pesquisa_satisfacao_$login_admin.resposta) AS resposta_sim,
						pergunta_descricao
						FROM tmp_pesquisa_satisfacao_$login_admin
						WHERE tipo_resposta_item = 'sim' AND pergunta = $id_pergunta
						GROUP BY pergunta_descricao
						ORDER BY pergunta_descricao";
		$res_geral_descricao_sim = pg_query($con,$sql);
		$total_geral_sim = pg_fetch_result($res_geral_descricao_sim,0,'resposta_sim');
		###

		### RESPOSTA NÃO
		$sql = "SELECT COUNT(tmp_pesquisa_satisfacao_$login_admin.resposta) AS resposta_nao,
						pergunta_descricao
						FROM tmp_pesquisa_satisfacao_$login_admin
						WHERE tipo_resposta_item = 'nao' AND pergunta = $id_pergunta
						GROUP BY pergunta_descricao
						ORDER BY pergunta_descricao";
		$res_geral_descricao_nao = pg_query($con,$sql);		
		$total_geral_nao = pg_fetch_result($res_geral_descricao_nao,0,'resposta_nao');
		###

		### TOTAL GERAL RESPOSTA
		$sql = "SELECT COUNT(tmp_pesquisa_satisfacao_$login_admin.resposta)
						FROM tmp_pesquisa_satisfacao_$login_admin
						";
		$res_todas_respostas = pg_query($con,$sql);
		$total_todas_os = pg_fetch_result($res_todas_respostas,0,0);
		###

		### TOTAL GERAL SIM
		$sql = "SELECT COUNT(tmp_pesquisa_satisfacao_$login_admin.resposta)
						FROM tmp_pesquisa_satisfacao_$login_admin
						WHERE tipo_resposta_item = 'sim'";
		$res_sim = pg_query($con,$sql);
		$cont_sim = pg_fetch_result($res_sim,0,0);
		###

		### TOTAL GERAL NÃO
		$sql = "SELECT COUNT(tmp_pesquisa_satisfacao_$login_admin.resposta)
						FROM tmp_pesquisa_satisfacao_$login_admin
						WHERE tipo_resposta_item = 'nao'";
		$res_nao = pg_query($con,$sql);
		$cont_nao = pg_fetch_result($res_nao,0,0);
		###

		$descricao_pergunta = pg_fetch_result($res_pergunta,$i,'pergunta_descricao');


		if ($login_fabrica == 30) {
			$countCompleSatisfeito    	= 0; 
			$countSatisfeito    		= 0;
			$countCompleInSatisfeito    = 0;
			$countInSatisfeito    		= 0; 

			$sqlSatis = "SELECT COUNT(tmp_pesquisa_satisfacao_$login_admin.resposta), pergunta_descricao, tipo_resposta_item
						   FROM tmp_pesquisa_satisfacao_$login_admin
						  WHERE tipo_resposta_item ilike '%satisf%' 
						    AND pergunta = $id_pergunta
				       GROUP BY pergunta_descricao, tipo_resposta_item";
			$resSatis = pg_query($con, $sqlSatis);
			while($rowSatis = pg_fetch_array($resSatis)) {

				if (in_array($rowSatis["tipo_resposta_item"], array("completamente   satisfeito", "completamente satisfeito")) ) {
					$countCompleSatisfeito    = $rowSatis["count"];
				}
				if ($rowSatis["tipo_resposta_item"] == "satisfeito") {

					$countSatisfeito    = $rowSatis["count"];
				}
				if ($rowSatis["tipo_resposta_item"] == "completamente insatisfeito") {

					$countCompleInSatisfeito    = $rowSatis["count"];
				}
				if ($rowSatis["tipo_resposta_item"] == "insatisfeito") {
					$countInSatisfeito    = $rowSatis["count"];
				}

				if (in_array($rowSatis["tipo_resposta_item"], array("completamente   satisfeito", "completamente satisfeito")) || $rowSatis["tipo_resposta_item"] == "satisfeito") {
					$countTotalSatisfeito    += $rowSatis["count"];
				} 

				if ($rowSatis["tipo_resposta_item"] == "completamente insatisfeito" || $rowSatis["tipo_resposta_item"] == "insatisfeito") {
					$countTotalInSatisfeito  += $rowSatis["count"];
				}

			}
		}

			$new_total_sim = (empty($total_geral_sim)) ? 0 : $total_geral_sim;
			$new_total_nao = (empty($total_geral_nao)) ? 0 : $total_geral_nao;
			echo '<tr>
	 					<td>'.$descricao_pergunta.'</td>
	 		  			<td>'.$total_geral_os.'</td>
	 		  			<td class="tac">'.$new_total_sim.'&nbsp;/&nbsp;'.$new_total_nao.'</td>';
	 			  echo '
	 					<td>'.number_format ( $total_geral_sim * 100 / ($total_geral_os), 2, ',', '' ). '&nbsp; %</td>
		 			';
	 		  			if ($login_fabrica == 30) {
							echo '<td class="tac">
							'.$countCompleSatisfeito.'&nbsp;/&nbsp;'.$countCompleInSatisfeito.'
							&nbsp;/&nbsp;'.$countInSatisfeito.'&nbsp;/&nbsp;'.$countSatisfeito.'
							</td>
							<td class="tac">'.number_format($countTotalSatisfeito*100/($total_geral_os), 2, ',', '' ).'&nbsp; %</td>';
	 		  			}
	 		  	echo '</tr>';
		}
?>
		<tr class="titulo_coluna">
			<td>Total Pesquisado</td>
			<td><? echo $total_todas_os ?></td>
			<td class="tac"><? echo $cont_sim.'&nbsp;/&nbsp;'.$cont_nao ?></td>
			<td><? echo number_format ( $cont_sim * 100 / ($total_todas_os), 2, ',', '' ) ?> %</td>
			<?php if ($login_fabrica == 30) {?>
			<td class="tac">
				<?php echo $countTotalSatisfeito;?> /  <?php echo $countTotalInSatisfeito;?>
			</td>
			<td><?php echo number_format( $countTotalSatisfeito * 100 / ($total_todas_os), 2, ',', '' ) ?> %</td>
			<?php }?>
		</tr>	
	</tbody>
</table>
<?
	$jsonPOST = excelPostToJson($_POST);
?>
	<div id='gerar_excel' class="btn_excel">
		<input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
		<span><img src='imagens/excel.png' /></span>
		<span class="txt">Gerar Arquivo Excel</span>
	</div>
<?

	if (in_array($login_fabrica, [30])) {
		echo (isset($excel_fam) and !empty($excel_fam)) ? $excel_fam : '';
		echo (isset($excel_perg) and !empty($excel_perg)) ? $excel_perg : '';
	}
}else{
		echo '
			<div class="container">
			<div class="alert">
				    <h4>Nenhum resultado encontrado</h4>
			</div>
			</div>';
	}
}
?>

<?php include 'rodape.php'; ?>
