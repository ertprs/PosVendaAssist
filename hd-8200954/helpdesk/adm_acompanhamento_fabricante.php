<?php
include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
include_once 'autentica_admin.php';
include_once '../admin/funcoes.php';
include_once 'mlg_funciones.php';

define('BS3', true); // Está tela utiliza o novo menu e layout Bootstrap 3

$array_fabricas = pg_fetch_pairs(
    $con,
    "SELECT fabrica, nome FROM tbl_fabrica WHERE fabrica NOT IN (SELECT fabrica FROM tbl_fabrica WHERE parametros_adicionais::jsonb->>'telecontrol_distrib' = 't' AND ativo_fabrica)  AND ativo_fabrica ORDER BY nome"
);

$array_fabricas_gestao = pg_fetch_pairs(
    $con,
    "SELECT fabrica, nome FROM tbl_fabrica WHERE parametros_adicionais::jsonb->>'telecontrol_distrib' = 't' AND ativo_fabrica ORDER BY nome"
);

/*$array_equipe= pg_fetch_pairs(
    $con,
    "SELECT parametros_adicionais::jsonb->>'equipe' as id, upper(parametros_adicionais::jsonb->>'equipe') as descricao  FROM tbl_fabrica WHERE ativo_fabrica and parametros_adicionais::jsonb->>'equipe' notnull order by 1"
);*/
$TITULO = 'Relatório de Acompanhamento de Fabricantes';

if(count($_POST)){
    $data_ini       = is_date(getPost('data_inicial'));
    $data_fim       = is_date(getPost('data_final'));
    $enviar         = $_POST['send_email'];
    $gerar_gf       = $_POST['gerar_gf'];
    $gestao         = $_POST['gestao'];
    if ($gestao) {
    	$fabrica    = getPost('fabricante_gestao');
    } else {
    	$fabrica    = getPost('fabricante');
    }

    /*$equipe        = getPost('equipe');*/
    
    if(strlen($fabrica) > 0) {
    	$MAX_INTERVAL = 3;
		$dias = 91;
    } else {
		$MAX_INTERVAL = 1;
		$dias = 31;
    }
	$date = new DateTime($data_ini);
    $diferenca = $date->diff(new DateTime($data_fim));

    if (!$data_ini || !$data_fim) {
    	$msg_erro[] = "Preencha todos os campos obrigatórios";
    } else {

        // Já deixa no _POST para recarregar...
        $_POST['data_inicial'] = is_date($data_ini, 'ISO', 'EUR');
        $_POST['data_final']   = is_date($data_fim, 'ISO', 'EUR');



	    $data_fim = "$data_fim 23:59:59";

		try {

	    	if ($diferenca->days > $dias){
	        	throw new Exception("Intervalo entre datas deve ser de no máximo $MAX_INTERVAL mes.");
	    	}

	    	if(strlen($fabrica) > 0) {
	    		$cond_hd_chamado = " AND tbl_hd_chamado.fabrica = $fabrica ";
	    		$filtrar_fabrica = "sim";
	    	} else {
	    		$cond_hd_chamado = " AND tbl_hd_chamado.fabrica <> 10";
	    		$filtrar_fabrica = "nao";
	    	}

			/*if(strlen($equipe) > 0) {
				$cond_hd_chamado = " AND tbl_fabrica.parametros_adicionais::jsonb->>'equipe'= '$equipe' ";
	    		$filtrar_equipe= "sim";
	    	} */

	    	if (!empty($gestao) && $gestao == 't' && $filtrar_fabrica == 'nao'){
	    		$cond_hd_chamado = " AND tbl_fabrica.parametros_adicionais::jsonb->>'telecontrol_distrib' = 't' ";
	    	}

	    	$sql_hd_chamado = "
	    		SELECT
	    			tbl_fabrica.fabrica as fabrica,
					tbl_fabrica.nome as nome_fabrica
	    		FROM
	    			tbl_hd_chamado
	    			LEFT JOIN tbl_tipo_chamado ON tbl_tipo_chamado.tipo_chamado = tbl_hd_chamado.tipo_chamado
	    			JOIN tbl_posto_fabrica ON tbl_posto_fabrica.fabrica = tbl_hd_chamado.fabrica
	    			JOIN tbl_fabrica ON tbl_posto_fabrica.fabrica = tbl_fabrica.fabrica
	    		WHERE
	    			tbl_hd_chamado.data BETWEEN '{$data_ini}' AND '{$data_fim}'
	    			{$cond_hd_chamado}
	    		GROUP BY
	    		tbl_fabrica.nome,
	    		tbl_fabrica.fabrica
	    		ORDER BY
	    		tbl_fabrica.nome
	    	";
	    	$res_hd_chamado = pg_query($con, $sql_hd_chamado);

	    	$total_qtde_alteracao            = 0;
			$total_qtde_erro                 = 0;
			$total_qtde_os                   = 0;
			$total_qtde_pedido_faturado      = 0;
			$total_qtde_pedido_garantia      = 0;
			$total_qtde_credenciado          = 0;
			$total_qtde_em_descredenciamento = 0;
			$total_telefonia_geral 			 = 0;
			$total_telefonia                 = 0;
			$total_nfe 						 = 0;
			$total_nfe_geral                 = 0;
			$total_pecas_geral 				 = 0;
			$total_pecas			         = 0;

			$dados = array();

			if(pg_num_rows($res_hd_chamado) > 0){

		    	for($i = 0; $i < pg_num_rows($res_hd_chamado); $i++){
		    		
		    		$fabrica      = pg_result($res_hd_chamado, $i, 'fabrica');
					$nome_fabrica = pg_result($res_hd_chamado, $i, 'nome_fabrica');

		    		$sql_qtde_alteracao = "
			    		SELECT
			    			COUNT(DISTINCT tbl_hd_chamado.hd_chamado) as qtde_alteracao
			    		FROM
			    			tbl_hd_chamado
			    			JOIN tbl_tipo_chamado ON tbl_tipo_chamado.tipo_chamado = tbl_hd_chamado.tipo_chamado
			    		WHERE
			    			tbl_hd_chamado.data BETWEEN '{$data_ini}' AND '{$data_fim}'
			    			AND tbl_tipo_chamado.tipo_chamado IN (1,2,3,4,7,8)
			    			AND tbl_hd_chamado.fabrica = {$fabrica}
			    		LIMIT 1;
			    	";

			    	$res_qtde_alteracao = pg_query($con, $sql_qtde_alteracao);
			    	$qtde_alteracao = pg_result($res_qtde_alteracao, 0, 'qtde_alteracao');

			    	if(!$qtde_alteracao) {
			    		$qtde_alteracao = 0;
			    	}

			    	$sql_qtde_erro = "
			    		SELECT
			    			COUNT(DISTINCT tbl_hd_chamado.hd_chamado) as qtde_erro
			    		FROM
			    			tbl_hd_chamado
			    			JOIN tbl_tipo_chamado ON tbl_tipo_chamado.tipo_chamado = tbl_hd_chamado.tipo_chamado
			    		WHERE
			    			tbl_hd_chamado.data BETWEEN '{$data_ini}' AND '{$data_fim}'
			    			AND tbl_tipo_chamado.tipo_chamado = 5
			    			AND tbl_hd_chamado.fabrica = {$fabrica}
			    		LIMIT 1;
			    	";

			    	$res_qtde_erro = pg_query($con, $sql_qtde_erro);
			    	$qtde_erro = pg_result($res_qtde_erro, 0, 'qtde_erro');

			    	if(!$qtde_erro) {
			    		$qtde_erro = 0;
			    	}

			    	$sql_os = "
			    		SELECT
			    			COUNT(tbl_os.os) as qtde_os
			    		FROM
			    			tbl_os
			    		WHERE
			    			fabrica = {$fabrica}
							AND tbl_os.data_abertura BETWEEN '{$data_ini}' AND '{$data_fim}'
						LIMIT 1;
			    	";

			    	$res_os  = pg_query($con, $sql_os);
			    	$qtde_os = pg_result($res_os, 0, 'qtde_os');

			    	if(!$qtde_os) {
			    		$qtde_os = 0;
			    	}

			    	$sql_pedido_faturado = "
			    		SELECT
			    			COUNT(tbl_pedido.pedido) as qtde_pedido_faturado
			    		FROM
			    			tbl_pedido
						JOIN tbl_tipo_pedido ON tbl_tipo_pedido.tipo_pedido = tbl_pedido.tipo_pedido AND tbl_tipo_pedido.fabrica = tbl_pedido.fabrica
			    		WHERE
			    			tbl_pedido.fabrica = {$fabrica}
			    			AND (tbl_tipo_pedido.pedido_faturado IS TRUE OR tbl_tipo_pedido.descricao ~* '^FAT')
							AND tbl_pedido.data BETWEEN '{$data_ini}' AND '{$data_fim}'
			    	";

			    	$res_pedido_faturado  = pg_query($con, $sql_pedido_faturado);
			    	$qtde_pedido_faturado = pg_result($res_pedido_faturado, 0, 'qtde_pedido_faturado');

			    	if(!$qtde_pedido_faturado) {
			    		$qtde_pedido_faturado = 0;
			    	}

			    	$sql_pedido_garantia = "
			    		SELECT
			    			COUNT(tbl_pedido.pedido) as qtde_pedido_garantia
			    		FROM
			    			tbl_pedido
			    		JOIN tbl_tipo_pedido ON tbl_tipo_pedido.tipo_pedido = tbl_pedido.tipo_pedido AND tbl_tipo_pedido.fabrica = tbl_pedido.fabrica
			    		WHERE
			    			tbl_pedido.fabrica = {$fabrica}
			    			AND (tbl_tipo_pedido.pedido_faturado IS not TRUE OR tbl_tipo_pedido.descricao ~* '^GAR')
							AND tbl_pedido.data BETWEEN '{$data_ini}' AND '{$data_fim}'
			    	";

			    	$res_pedido_garantia  = pg_query($con, $sql_pedido_garantia);
			    	$qtde_pedido_garantia = pg_result($res_pedido_garantia, 0, 'qtde_pedido_garantia');

			    	if(!$qtde_pedido_garantia) {
			    		$qtde_pedido_garantia = 0;
			    	}

			    	$sql_credenciado = "
			    		SELECT
			    			COUNT(posto) as qtde_credenciado
			    		FROM
			    			tbl_posto_fabrica
			    		WHERE
			    			fabrica = {$fabrica}
			    			AND credenciamento = 'CREDENCIADO'
			    		LIMIT 1;
			    	";

			    	$res_credenciado  = pg_query($con, $sql_credenciado);
			    	$qtde_credenciado = pg_result($res_credenciado, 0, 'qtde_credenciado');

			    	if(!$qtde_credenciado) {
			    		$qtde_credenciado = 0;
			    	}

			    	$sql_em_descredenciamento = "
			    		SELECT
			    			COUNT(posto) as qtde_em_descredenciamento
			    		FROM
			    			tbl_posto_fabrica
			    		WHERE
			    			fabrica = {$fabrica}
			    			AND credenciamento = 'EM DESCREDENCIAMENTO'
			    		LIMIT 1;
			    	";

			    	$res_em_descredenciamento  = pg_query($con, $sql_em_descredenciamento);
			    	$qtde_em_descredenciamento = pg_result($res_em_descredenciamento, 0, 'qtde_em_descredenciamento');

			    	if(!$qtde_em_descredenciamento) {
			    		$qtde_em_descredenciamento = 0;
			    	}

			    	$dados[$fabrica]["fabrica"]                   = $fabrica;
			    	$dados[$fabrica]["nome_fabrica"]              = $nome_fabrica;
			    	$dados[$fabrica]["qtde_alteracao"]            = $qtde_alteracao;
			    	$dados[$fabrica]["qtde_erro"]                 = $qtde_erro;
			    	$dados[$fabrica]["qtde_os"]                   = $qtde_os;
			    	$dados[$fabrica]["qtde_pedido_faturado"]      = $qtde_pedido_faturado;
			    	$dados[$fabrica]["qtde_pedido_garantia"]      = $qtde_pedido_garantia;
			    	$dados[$fabrica]["qtde_credenciado"]          = $qtde_credenciado;
			    	$dados[$fabrica]["qtde_em_descredenciamento"] = $qtde_em_descredenciamento;
			    	$dados[$fabrica]["qtde_ligacoes_atendidas"]	  = $total_telefonia;
			    	$dados[$fabrica]["qtde_nfes_emitidas"]		  = $total_nfe;
			    	$dados[$fabrica]["qtde_pecas_despachadas"]    =	$total_pecas;	

			    	$total_qtde_alteracao            += $qtde_alteracao;
					$total_qtde_erro                 += $qtde_erro;
					$total_qtde_os                   += $qtde_os;
					$total_qtde_pedido_faturado      += $qtde_pedido_faturado;
					$total_qtde_pedido_garantia      += $qtde_pedido_garantia;
					$total_qtde_credenciado          += $qtde_credenciado;
					$total_qtde_em_descredenciamento += $qtde_em_descredenciamento;
					$total_telefonia_geral           += $total_telefonia_geral;
					$total_nfe_geral 				 += $total_nfe_geral;
					$total_pecas_geral 				 += $total_pecas_geral;  
		    	}

	        	if ($_POST["gerar_excel"]) {
	        		$data = date ("d-m-Y-H-i");
					$fileName = "relatorio_acompanhamento_fabricante-{$data}.xls";
					$file = fopen("/tmp/{$fileName}", "w");

					$thead = "
						<table border='1'>
							<thead>
								<tr>
									<th colspan='8' bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;' >
										RELATÓRIO DE ACOMPANHAMENTO DE FABRICANTES
									</th>
								</tr>
								<tr>
									<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".strtoupper('Fábrica')."</th>
									<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".strtoupper('Chamados de Alteração')."</th>
									<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".strtoupper('Chamados de Erro')."</th>
									<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".strtoupper("OS's Abertas")."</th>
									<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".strtoupper('Pedidos Faturados')."</th>
									<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".strtoupper('Pedidos em Garantia')."</th>
									<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".strtoupper('Postos Credenciados')."</th>
									<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".strtoupper('Qtde de Ligações Atendidas')."</th>
									<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".strtoupper('Qtde de NFEs Emitidas')."</th>
									<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".strtoupper('Qtde de Peças Despachadas')."</th>
								</tr>
							</thead>
							<tbody>
					";
					fwrite($file, $thead);
					$body = "";

		            foreach ($dados as $dado_bruto => $fabrica) {
		            	$body .= "
		            		<tr>
		            			<td nowrap align='left' valign='top'>".strtoupper($fabrica["nome_fabrica"])."</td>
		            			<td nowrap align='left' valign='top'>".$fabrica["qtde_alteracao"]."</td>
		            			<td nowrap align='left' valign='top'>".$fabrica["qtde_erro"]."</td>
		            			<td nowrap align='left' valign='top'>".$fabrica["qtde_os"]."</td>
		            			<td nowrap align='left' valign='top'>".$fabrica["qtde_pedido_faturado"]."</td>
		            			<td nowrap align='left' valign='top'>".$fabrica["qtde_pedido_garantia"]."</td>
		            			<td nowrap align='left' valign='top'>".$fabrica["qtde_credenciado"]."</td>
		            			<td nowrap align='left' valign='top'>".$fabrica["qtde_em_descredenciamento"]."</td>
		            			<td nowrap align='left' valign='top'>".$fabrica["qtde_ligacoes_atendidas"]."</td>
		            			<td nowrap align='left' valign='top'>".$fabrica["qtde_nfes_emitidas"]."</td>
		            			<td nowrap align='left' valign='top'>".$fabrica["qtde_pecas_despachadas"]."</td>
		            		</tr>
		            	";
		            }
		            fwrite($file, $body);

		            $tfoot = "
				            	<tr>
									<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>TOTAL - ".pg_num_rows($res_hd_chamado)." REGISTROS</th>
									<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".$total_qtde_alteracao."</th>
									<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".$total_qtde_erro."</th>
									<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".$total_qtde_os."</th>
									<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".$total_qtde_pedido_faturado."</th>
									<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".$total_qtde_pedido_garantia."</th>
									<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".$total_qtde_credenciado."</th>
									<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".$total_qtde_em_descredenciamento."</th>
									<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".$total_telefonia_geral."</th>
									<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".$total_nfe_geral."</th>
									<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".$total_pecas_geral."</th>
				            	</tr>
		            		</tbody>
		            	</table>
		            ";
		            fwrite($file, $tfoot);
		            fclose($file);

		            if (file_exists("/tmp/{$fileName}")) {
						system("mv /tmp/{$fileName} ../admin/xls/{$fileName}");
						echo "../admin/xls/{$fileName}";
					}

					exit;
	        	}

				function retorna_fila_telefone($data_inicial, $data_final) {
					global $filasTelefonia, $login_fabrica;

					if (empty($data_inicial) || empty($data_final)) {
						$msg_erro["msg"][]    = "Data informada inválida";
						$msg_erro["campos"][] = "data";
					}

					if (count($msg_erro) == 0) {
						$resultadoPesquisa = [];
						$responseData = [];

						$filasTelefonia = str_replace("'", "", $filasTelefonia);

						$filasTelefonia = array_map(function ($r) {
							return "'$r'";
						}, $filasTelefonia);

						$queryString = "/inicio/{$data_inicial}/final/{$data_final}/companhia/10/setor/sac/filas/" . urlencode(implode(",", $filasTelefonia)) . "/fabrica/" . $login_fabrica;
						$curlData = curl_init();

						curl_setopt_array($curlData, array(
							CURLOPT_URL => 'https://api2.telecontrol.com.br/telefonia/relatorio-atendentes' . $queryString,
							CURLOPT_RETURNTRANSFER => true,
							CURLOPT_TIMEOUT => 90,
							CURLOPT_HTTPHEADER => array(
								"Access-Application-Key: 084f77e7ff357414d5fe4a25314886fa312b2cff",
						        "Access-Env: PRODUCTION",
						        "Cache-Control: no-cache",
						        "Content-Type: application/json"
							),
						));

						$responseData[] = json_decode(curl_exec($curlData), true);
						
						if (!empty(curl_error($curl)) OR $responseData['exception']) {

							$msg_erro["msg"][]    = strlen($responseData['exception']) ? $responseData['exception'] : curl_error($curlData);

						} else {
							foreach ($responseData as $key => $value) {
								foreach ($value as $at => $vl) {
									foreach ($vl as $k => $v) {
										$resultadoPesquisa[] = $v['recebidas']['total_ligacoes'];
									}
								}
							}
							foreach ($resultadoPesquisa as $key => $value) {
								if (empty($value)) {
									unset($resultadoPesquisa[$key]);
								}
							}
						}	
						
						curl_close($curlData);
						return $resultadoPesquisa;
					} else {
						return $msg_erro;
					}
				}

				$retorno_tel = retorna_fila_telefone($data_ini, $data_fim);

				foreach ($retorno_tel as $p => $ttl) {
					$total_telefonia += $ttl;
				}

				$total_telefonia_geral += $total_telefonia;

				$sql_total_nfe_garantia = "	SELECT COUNT(tbl_faturamento.faturamento) AS total_faturamento_garantia,
											   SUM(tbl_faturamento_item.qtde) AS total_pecas_garantia
										FROM tbl_faturamento
										JOIN tbl_faturamento_item ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
										JOIN tbl_peca ON tbl_faturamento_item.peca = tbl_peca.peca
										WHERE tbl_peca.fabrica = $login_fabrica
										AND emissao between '$inicial 00:00' and '$final 23:59'
										AND (left(tbl_faturamento.cfop, 2) IN ('59','69'))";
				$res_total_nfe_garantia = pg_query($con, $sql_total_nfe_garantia);

				if (pg_num_rows($res_total_nfe_garantia) > 0) {
					$total_nfe_garantia = pg_fetch_result($res_total_nfe_garantia, 0, 'total_faturamento_garantia');
					$total_nfe_garantia_geral += $total_nfe_garantia;
					$total_qtde_peca_garantia = pg_fetch_result($res_total_nfe_garantia, 0, 'total_pecas_garantia');
					$total_qtde_peca_garantia = (empty($total_qtde_peca_garantia)) ? 0 : $total_qtde_peca_garantia;
					$total_qtde_peca_garantia_geral += $total_qtde_peca_garantia;
				}	

				$sql_total_nfe_faturada = "	SELECT COUNT(tbl_faturamento.faturamento) AS total_faturamento_faturado,
											   SUM(tbl_faturamento_item.qtde) AS total_pecas_faturada	
										FROM tbl_faturamento
										JOIN tbl_faturamento_item ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
										JOIN tbl_peca ON tbl_faturamento_item.peca = tbl_peca.peca
										WHERE tbl_peca.fabrica = $login_fabrica
										AND emissao between '$inicial 00:00' and '$final 23:59'
										AND (left(tbl_faturamento.cfop, 2) NOT IN ('59','69'))";
				$res_total_nfe_faturada = pg_query($con, $sql_total_nfe_faturada);

				if (pg_num_rows($res_total_nfe_faturada) > 0) {
					$total_nfe_faturado = pg_fetch_result($res_total_nfe_faturada, 0, 'total_faturamento_faturado');
					$total_nfe_faturado_geral += $total_nfe_faturado;
					$total_qtde_peca_faturado = pg_fetch_result($res_total_nfe_faturada, 0, 'total_pecas_faturada');
					$total_qtde_peca_faturado = (empty($total_qtde_peca_faturado)) ? 0 : $total_qtde_peca_faturado;
					$total_qtde_peca_faturado_geral += $total_qtde_peca_faturado;
				}

				$total_nfe = $total_nfe_garantia + $total_nfe_faturado;
				$total_nfe_geral += $total_nfe;
				$total_pecas = $total_qtde_peca_garantia + $total_qtde_peca_faturado;
				$total_pecas_geral += $total_pecas;

	        	if($gerar_gf == 't' && count($res_hd_chamado)) {
	        		$dados_graficos = array();
	        		$graph_array    = array();

	        		for($i = 0; $i < 7; $i++){
	        			$valor = 0;
	        			$porcentagem = 0;

	        			switch ($i) {
	        				case '0':
	        					$dados_graficos[$i]['chart_title'] = "Chamados de Alteração";

	        					foreach ($dados as $dado_bruto => $fabrica) {
					    			$valor = $fabrica['qtde_alteracao'];
	        						$porcentagem = round(($valor/$total_qtde_alteracao)*100,2);
					    			$graph_array[] = "['".strtoupper($fabrica['nome_fabrica'])."', $porcentagem]";
					    		}

					    		$dados_graficos[$i]['chart_data'] = implode(',', $graph_array);
					    		unset($graph_array);
	        					break;

	        				case '1':
	        					$dados_graficos[$i]['chart_title'] = "Chamados de Erro";

	        					foreach ($dados as $dado_bruto => $fabrica) {
					    			$valor = $fabrica['qtde_erro'];
	        						$porcentagem = round(($valor/$total_qtde_erro)*100,2);
					    			$graph_array[] = "['".strtoupper($fabrica['nome_fabrica'])."', $porcentagem]";
					    		}

					    		$dados_graficos[$i]['chart_data'] = implode(',', $graph_array);
					    		unset($graph_array);
	        					break;

	        				case '2': 
	        					$dados_graficos[$i]['chart_title'] = "OS&#39;s Abertas";

	        					foreach ($dados as $dado_bruto => $fabrica) {
					    			$valor = $fabrica['qtde_os'];
	        						$porcentagem = round(($valor/$total_qtde_os)*100,2);
					    			$graph_array[] = "['".strtoupper($fabrica['nome_fabrica'])."', $porcentagem]";
					    		}

					    		$dados_graficos[$i]['chart_data'] = implode(',', $graph_array);
	        					unset($graph_array);
	        					break;

	        				case '3':
	        					$dados_graficos[$i]['chart_title'] = "Pedidos Faturados";

	        					foreach ($dados as $dado_bruto => $fabrica) {
					    			$valor = $fabrica['qtde_pedido_faturado'];
	        						$porcentagem = round(($valor/$total_qtde_pedido_faturado)*100,2);
					    			$graph_array[] = "['".strtoupper($fabrica['nome_fabrica'])."', $porcentagem]";
					    		}

					    		$dados_graficos[$i]['chart_data'] = implode(',', $graph_array);
	        					unset($graph_array);
	        					break;

	        				case '4':
	        					$dados_graficos[$i]['chart_title'] = "Pedidos em Garantia";

	        					foreach ($dados as $dado_bruto => $fabrica) {
					    			$valor = $fabrica['qtde_pedido_garantia'];
	        						$porcentagem = round(($valor/$total_qtde_pedido_garantia)*100,2);
					    			$graph_array[] = "['".strtoupper($fabrica['nome_fabrica'])."', $porcentagem]";
					    		}

					    		$dados_graficos[$i]['chart_data'] = implode(',', $graph_array);
	        					unset($graph_array);
	        					break;

	        				case '5':
	        					$dados_graficos[$i]['chart_title'] = "Postos Credenciados";

	        					foreach ($dados as $dado_bruto => $fabrica) {
	        						$valor = $fabrica['qtde_credenciado'];
	        						$porcentagem = round(($valor/$total_qtde_credenciado)*100,2);
					    			$graph_array[] = "['".strtoupper($fabrica['nome_fabrica'])."', $porcentagem]";
					    		}

					    		$dados_graficos[$i]['chart_data'] = implode(',', $graph_array);
	        					unset($graph_array);
	        					break;

	        				case '6':
	        					$dados_graficos[$i]['chart_title'] = "Postos em Descredenciamento";

	        					foreach ($dados as $dado_bruto => $fabrica) {
					    			$valor = $fabrica['qtde_em_descredenciamento'];
	        						$porcentagem = round(($valor/$total_qtde_em_descredenciamento)*100,2);
					    			$graph_array[] = "['".strtoupper($fabrica['nome_fabrica'])."', $porcentagem]";
					    		}

					    		$dados_graficos[$i]['chart_data'] = implode(',', $graph_array);
	        					unset($graph_array);
	        					break;
	        			}
	        		}
		    	}
	    	}
	    } catch (Exception $e) {
	    	$msg_erro = $e->getMessage();
	    }
	}
}

$bs_extras = array('datepicker', 'bstable', 'toggle');
include './menu.php';

?>
<script type="text/javascript" src="https://code.highcharts.com/highcharts.js"></script>
<script src="https://code.highcharts.com/modules/data.js"></script>
<script src="https://code.highcharts.com/modules/drilldown.js"></script>

<style>
	.titulo_tabela {
		background-color: #3d3e71 !important;
		color: #FFF;
	}

	.titulo_tabela th {
		text-align: center !important;
	}

	.titulo_coluna {
		font: bold 14px "Arial";
		background-color: #3d3e71;
  		color:#FFFFFF;
  		text-align:center;
  		padding: 5px 0 0 0;
	}
	
	td {
		font: 14px "Arial";
	}

	.tac {
		text-align: center !important;
	}

	.tal {
		text-align: left !important;
	}

	.tar {
		text-align: right !important;
	}

	#border {
		padding: 3px;
	}

	.asteristico {
		color: #B94A48;
	    background-color: inherit;
	    float: left;
	    margin-bottom: 0;
	    margin-left: -9px;
	    margin-top: 7px;
	}

	.obrigatorio {
		color: #B94A48; 
		font-size: 12px;
	}

</style>

<script>
	function limpar_form() {
		$("#data_final").val("");
		$("#data_inicial").val("");
		$("#sel-fabricante").val("Selecione a Fábrica");
		$("#sel-fabricante_gestao").val("Selecione a Fábrica");
		$("#send_email").prop("checked", false);
	}
	var lang = '<?=$cook_idioma?>' || 'pt-BR';
	$(function() {
		$("#data_inicial").mask("99/99/9999");
		$("#data_final").mask("99/99/9999");
	    
	    $('.input-group.date').datepicker({
	        format: 'dd/mm/yyyy',
	        language: 'pt-BR',
	        weekStart: (lang == 'es') ? 1 : 0,
	        endDate: '0d'
	    });

	    $("#sel-fabricante").change(function(){
	    	var fabrica = $("#sel-fabricante").val();
	    	if(fabrica != ""){
	    		$("#gerar_gf").prop("disabled", true);
	    	} else {
	    		$("#gerar_gf").prop("disabled", false);
	    	}
	    });

	    $("#sel-fabricante_gestao").change(function(){
	    	var fabrica = $("#sel-fabricante_gestao").val();
	    	if(fabrica != ""){
	    		$("#gerar_gf").prop("disabled", true);
	    	} else {
	    		$("#gerar_gf").prop("disabled", false);
	    	}
	    });

	    $('#gestao').change(function() {
	        if($(this).is(":checked")) {
	        	$("#fab").hide();
	        	$("#fab_gestao").show();
	        } else {
	        	$("#fab_gestao").hide();
	        	$("#fab").show();
	        }
	        
	    });

	    $("#gerar_excel").click(function () {
			var json = $.parseJSON($("#jsonPOST").val());
			json["gerar_excel"] = true;

			$.ajax({
				url: "<?=$_SERVER['PHP_SELF']?>",
				type: "POST",
				data: json,
				beforeSend: function () {
					alert("Gerando relatório, favor aguardar alguns instantes!");
				},
				complete: function (data) {
					window.open(data.responseText, "_blank");
				}
			});
		});
	});
</script>

<div class="container">
	<div class="row-fluid">
	   	<b class="pull-right obrigatorio">  * Campos obrigatórios </b>
	   	<div class="row"></div>
	</div>
	<div class="panel panel-default">
		<div class="panel-heading tac">
			<h3 class="panel-title"><?=$TITULO?></h3>
		</div>
		<div class="panel-body">
			<form method="POST">
				<div class="row">
		          <div class="col-md-2 col-md-offset-1 col-sm-4 col-xs-6">
		            <div class="form-group">
		              <label for="data_inicial"><?=traduz("Data Inicial")?></label>
		              <div class="input-group date">
		              	<h5 class="asteristico">*</h5>
		                <input type="text" class="form-control" id="data_inicial" name="data_inicial" placeholder="<?=traduz("Data Inicial")?>" autocomplete="off" value="<?=$_POST['data_inicial']?>" required>
		                <span class="input-group-addon"><i class="glyphicon glyphicon-th"></i></span>
		              </div>
		            </div>
		          </div>
		          <div class="col-md-2 col-sm-4 col-xs-6">
		            <div class="form-group">
		              <label for="data_final"><?=traduz('Data Final')?></label>
		              <div class="input-group date">
		              	<h5 class="asteristico">*</h5>
		                <input type="text" class="form-control" id="data_final" name="data_final" placeholder="<?=traduz('Data Final')?>" autocomplete="off" value="<?=$_POST['data_final']?>" required>
		                <span class="input-group-addon"><i class="glyphicon glyphicon-th"></i></span>
		                <div class="help-block with-errors"></div>
		              </div>
		            </div>
		          </div>
		          <?php if ($gestao && $gestao == 't') { 
		      	  			$display_fab = "display: none";
		      	  			$display_fab_gestao = "";
		      	  ?>
		      	  <?php } else { 
		      	  			$display_fab_gestao = "display: none";
		      	  			$display_fab = "";
		      	  		} 
		      	  ?>
		          <div class="col-md-3 col-sm-6 col-xs-6" style="<?=$display_fab?>" id="fab">
		              <label for="sel-fabricante">Fabricante</label>
		              <?php echo  array2select('fabricante', 'sel-fabricante', $array_fabricas, $_POST['fabricante'], ' class="form-control"', 'Selecione a Fábrica', true); ?>
				  </div>
				  <div class="col-md-3 col-sm-6 col-xs-6" style="<?=$display_fab_gestao?>" id="fab_gestao">
		              <label for="sel-fabricante">Fabricante</label>
		              <?php echo  array2select('fabricante_gestao', 'sel-fabricante_gestao', $array_fabricas_gestao, $_POST['fabricante_gestao'], ' class="form-control"', 'Selecione a Fábrica', true); ?>
				  </div>
				  <!-- <div class="col-md-3 col-sm-6 col-xs-6">
		              <label for="sel-fabricante">Equipe</label>
		              <?php //echo  array2select('equipe', 'sel-equipe', $array_equipe, $_POST['equipe'], ' class="form-control"', 'Selecione Equipe', true); ?>
		          </div> -->
		          <div class="col-md-3 col-sm-6 col-xs-6">
		              <div class="form-group">
		              <label><?=traduz('Fábricas de Gestão')?></label>
		              <div class="form-group form-inline-group">
						  <p>	
						      <label class="checkbox-inline"
						       	data-toggle="popover" data-placement="top" data-html="true"
						      	data-trigger="hover" data-content="Mostrar somente as fábricas de gestão?">
						        <input type="checkbox" id="gestao" data-onstyle="info" data-toggle="toggle" data-on="Sim" data-off="Não" <?=$gestao=='t' ? 'checked="true"':''?> name="gestao" value="t"> Somente Gestão
						      </label>
						  </p>
		              </div>
		            </div>
		          </div>

		          <br> <br> <br> <br>
		          <div class="col-md-1 col-sm-6 col-xs-6"></div>
		          <div class="col-md-3 col-sm-6 col-xs-6">
		            <div class="form-group">
		              <label><?=traduz('Gráficos em Tela')?></label>
		              <div class="form-group form-inline-group">
						  <p>
						      <label class="checkbox-inline"
						       data-toggle="popover" data-placement="top" data-html="true"
						      data-trigger="hover" data-content="Gerar os gráficos sobre as informações apresentadas?">
						        <input type="checkbox" id="gerar_gf" data-onstyle="info" data-toggle="toggle" data-on="Sim" data-off="Não" <?=$gerar_gf=='t' ? 'checked="true"':''?> name="gerar_gf" value="t"> Gerar Gráficos
						      </label>
						  </p>
		              </div>
		            </div>
		          </div>
				</div>
		        <div class="row-fluid text-center">
		          <div>
		            <button type="submit" class="btn btn-primary"><?=traduz('Gerar Relatório')?></button>
		            <?php if (count($_POST)){ ?>
		            <button type="button" id="clear-form" class="btn btn-warning" onclick="javascript: limpar_form();"><?=traduz('Limpar')?></button>
		            <?php } if (pg_num_rows($res_hd_chamado) > 0) {
		            			$jsonPOST = excelPostToJson($_POST); ?>
					<input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
					<button type="button" id="gerar_excel" class="btn btn-success btn_excel" ><?=traduz('Download Excel')?></button>
		            <?php } ?>
		          </div>
		        </div>
		        <div class="row"><p>&nbsp;</p></div>
			</form>
		</div>
	</div>
</div>
<div id="border">
<?php
if(pg_num_rows($res_hd_chamado) > 0) {?>
	<table class="table table-bordered table-striped" id="resultado_fabricantes">
		<tr class="titulo_tabela">
			<th colspan="100%"><?=$TITULO;?></th>
		</tr>
		<tr class="titulo_coluna">
			<th class='tac'>Fábrica</th>
			<th class='tac'>Chamados de Alteração</th>
			<th class='tac'>Chamados de Erro</th>
			<th class='tac'>OS's Abertas</th>
			<th class='tac'>Pedidos Faturados</th>
			<th class='tac'>Pedidos em Garantia</th>
			<th class='tac'>Postos Credenciados</th>
			<th class='tac'>Postos em Descredenciamento</th>
			<th class='tac'>Qtde de Ligações Atendidas</th>
			<th class='tac'>Qtde de NFEs Emitidas</th>
			<th class='tac'>Qtde de Peças Despachadas</th>
		</tr>
		<?php foreach ($dados as $dado_bruto => $fabrica) { ?>
			<tr>
				<td><?=strtoupper($fabrica["nome_fabrica"]);?></td>
				<td><?=$fabrica["qtde_alteracao"];?></td>
				<td><?=$fabrica["qtde_erro"];?></td>
				<td><?=$fabrica["qtde_os"];?></td>
				<td><?=$fabrica["qtde_pedido_faturado"];?></td>
				<td><?=$fabrica["qtde_pedido_garantia"];?></td>
				<td><?=$fabrica["qtde_credenciado"];?></td>
				<td><?=$fabrica["qtde_em_descredenciamento"];?></td>
				<td><?=$fabrica["qtde_ligacoes_atendidas"];?></td>
				<td><?=$fabrica["qtde_nfes_emitidas"];?></td>
				<td><?=$fabrica["qtde_pecas_despachadas"];?></td>
			</tr>
		<?php } ?>
		<tfoot class="titulo_coluna">
			<th class="tac">Total</th>
			<th class="tac"><?=$total_qtde_alteracao;?></th>
			<th class="tac"><?=$total_qtde_erro;?></th>
			<th class="tac"><?=$total_qtde_os;?></th>
			<th class="tac"><?=$total_qtde_pedido_faturado;?></th>
			<th class="tac"><?=$total_qtde_pedido_garantia;?></th>
			<th class="tac"><?=$total_qtde_credenciado;?></th>
			<th class="tac"><?=$total_qtde_em_descredenciamento;?></th>
			<th class="tac"><?=$total_telefonia_geral;?></th>
			<th class="tac"><?=$total_nfe_geral;?></th>
			<th class="tac"><?=$total_pecas_geral;?></th>
		</tfoot>
	</table>
</div>
<?php }
	if(count($dados_graficos) > 0 && $filtrar_fabrica == "nao"){
		$count = 0;
		foreach ($dados_graficos as $grafico) { ?>
			<br>
			<div id="container_<?=$count;?>" style="width: 800px; margin: 0 auto;" ></div>
			<script>
				$(function () {
					    $('#container_<?=$count;?>').highcharts({
						chart: {
							plotBackgroundColor: null,
							    plotBorderWidth: null,
							plotShadow: false
						},
						title: {
							text: "<?=$grafico['chart_title'];?>"
						},
						tooltip: {
							formatter: function() {
								return '<b>'+ this.point.name +'</b>: '+ Highcharts.numberFormat(this.y, 2, '.') +' %';
							}
						},
						plotOptions: {
							pie: {
								allowPointSelect: true,
								cursor: 'pointer',
								dataLabels: {
									enabled: true,
									color: '#000000',
									connectorColor: '#000000',
									formatter: function() {
										return '<b>'+ this.point.name +'</b>: '+ Highcharts.numberFormat(this.y, 2, '.') +' %';
									}
								}
							}
						},
						series: [{
							type: 'pie',
							name: 'Browser share',
							data: [
								<?php echo $grafico['chart_data']; ?>
							]
						}]
					});
				});
			</script>
	<?php $count++; 
		}
	}
include 'rodape.php';
