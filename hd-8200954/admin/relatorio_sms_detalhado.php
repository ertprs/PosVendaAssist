<?php

include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
$title = "RELATÓRIO DE SMS E RESPOSTAS";
$cabecalho = "SMS ENVIADOS E RESPOSTAS";
$layout_menu = "callcenter";

$admin_privilegios="call_center";
include_once 'autentica_admin.php';
include_once 'funcoes.php';
$valor_unitario_sms = 0.15;
if (in_array($login_fabrica, [1])) {
	$valor_unitario_sms = 0.13;
} else if ($login_fabrica == 151) {
	$valor_unitario_sms = 0.11;
} else if (in_array($login_fabrica, array(3,35))) {
	$valor_unitario_sms = 0.12;
}
$valor_unitario_sms_desc = number_format($valor_unitario_sms, 2, ",", ".");

$btn_acao = $_POST['btn_acao'];
if(strlen($btn_acao)>0){
	$campo_os = 'tbl_os.sua_os,';


	$os = $_POST['os'];



	if ($login_fabrica != 10) {
		system("php ../rotinas/telecontrol/atualiza-sms-resposta.php {$login_fabrica}",$rest);
	}

	if( !empty($data_inicial) && !empty($data_final) ){
		try {
			if ($telecontrol_distrib) {
				validaData($data_inicial, $data_final, 12);	
			} else {
				validaData($data_inicial, $data_final, 3);
			}

			//list($dia, $mes, $ano) = explode("/", $data_inicial);
			$aux_data_inicial      = formata_data($data_inicial); // $ano."-".$mes."-".$dia;

	        //list($dia, $mes, $ano) = explode("/", $data_final);
	        $aux_data_final        = formata_data($data_final);// $ano."-".$mes."-".$dia;
		} catch (Exception $e) {
			$msg_erro["msg"][] = $e->getMessage();
			$msg_erro["campos"][] = "data_inicial";
			$msg_erro["campos"][] = "data_final";
		}
	}else{

		if(empty($os)) {
			$msg_erro["msg"][] = "Por favor, informe o intervalo para a pesquisa";
			$msg_erro["campos"][] = "data_inicial";
			$msg_erro["campos"][] = "data_final";
		}
	}


	if($login_fabrica == 1){
		$marca = $_POST['marca'];

		if(count($marca)>0){
			$where_marca = " AND tbl_produto.marca in (".implode(",",$marca).") ";

			if(!in_array(4, $marca)){
				$where_marca .= " AND tbl_produto.linha <> 199 ";
			}

			if(count($marca) == 1 AND in_array(4, $marca)){
				$where_marca = " AND tbl_produto.marca = 11 AND tbl_produto.linha = 199 ";
			}
		}

		if ($origem_envio != 2) {

            $cond_produto = "LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto and tbl_produto.fabrica_i = $login_fabrica ";
            $cond_marca = " LEFT JOIN tbl_marca on tbl_produto.marca = tbl_marca.marca and tbl_marca.fabrica = $login_fabrica ";
        } else {

            $cond_marca = " LEFT JOIN tbl_marca on tbl_marca.marca = tbl_treinamento.marca  and tbl_marca.fabrica = $login_fabrica";
        }


		$campo_marca = "";
		$campo_group_by = "";
		if ($origem_envio != 2) {
            $campo_marca    .= " tbl_produto.linha,";
            $campo_group_by .= " ,tbl_produto.linha";
		}

		$campo_marca .= " tbl_marca.marca, tbl_marca.nome as nome_marca, ";

		$campo_group_by .= " ,tbl_marca.marca, tbl_marca.nome ";
	}

	if ($login_fabrica == 3 && !empty($_POST["marca"])) {
		$marca = $_POST["marca"];

		if (count($marca) > 0) {
			$where_marca    = " AND tbl_produto.marca in (".implode(",",$marca).") ";
	        $cond_produto   = " LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto and tbl_produto.fabrica_i = $login_fabrica ";
	        $cond_marca     = " LEFT JOIN tbl_marca on tbl_produto.marca = tbl_marca.marca and tbl_marca.fabrica = $login_fabrica ";
	        $campo_marca    = " ,tbl_produto.linha";
	        $campo_group_by = " ,tbl_produto.linha ";
			$campo_marca    = " tbl_marca.marca, tbl_marca.nome as nome_marca, ";
			$campo_group_by = " ,tbl_marca.marca ,tbl_marca.nome ";
		}
	}

	$ver_fabricas = false;
	if ($login_fabrica == 10) {
		$fabricas = $_POST['fabricas'];
		if (count($fabricas) <= 0) {
			unset($fabricas);
			$fabricas = array();

			$aux_sql = "
				SELECT fabrica FROM tbl_fabrica WHERE api_secret_key_sms IS NOT NULL AND ativo_fabrica IS TRUE ORDER BY nome
			";
			$aux_res = pg_query($con, $aux_sql);

			for ($x = 0; $x < pg_num_rows($aux_res); $x++) {
				$fabricas[] = pg_fetch_result($aux_res, $x, 'fabrica');
			}
		}

		$ver_fabricas = true;

		$join_fabrica = "
		    LEFT JOIN tbl_os ON tbl_sms.os = tbl_os.os
		    LEFT JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto
		    LEFT JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
		";

		$where_fabrica = "
		    tbl_os.fabrica IN (". implode(",", $fabricas) .")
		    AND tbl_posto_fabrica.fabrica IN (". implode(",", $fabricas) .")
		";

		$order_by = "  ORDER BY tbl_sms.fabrica, tbl_sms.data ";
	}

    if ($login_fabrica == 1) {
        $ver_fabricas = true;

        if ($origem_envio != 2) {
            $where_fabrica = "
                tbl_sms.fabrica = {$login_fabrica}
            ";
            $join_fabrica = "
                LEFT JOIN tbl_os ON tbl_sms.os = tbl_os.os and tbl_os.fabrica = $login_fabrica 
                LEFT JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto
                                        AND tbl_posto_fabrica.fabrica = tbl_os.fabrica
                LEFT JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
            ";
        } else if ($origem_envio == 2) {
        	$campo_os = "";
            $campo_treino = "
                tbl_treinamento.titulo,
            ";


            $where_fabrica = "
                tbl_sms.fabrica = {$login_fabrica}
            ";

            $join_fabrica = "

                JOIN tbl_treinamento       USING(treinamento)
                LEFT JOIN tbl_treinamento_posto ON tbl_treinamento_posto.treinamento  = tbl_treinamento.treinamento
                LEFT JOIN tbl_posto             ON tbl_treinamento_posto.posto  = tbl_posto.posto
                LEFT JOIN tbl_posto_fabrica     ON tbl_posto_fabrica.posto      = tbl_treinamento_posto.posto
                                                AND tbl_posto_fabrica.fabrica   = $login_fabrica
            ";
        }
    }

	if ($ver_fabricas === false) {

		if (in_array($origem_envio, array(0,8))) {
			$join_fabrica = "
			    LEFT JOIN tbl_os ON tbl_sms.os = tbl_os.os
			    LEFT JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
			    LEFT JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
			";

			$where_fabrica = " tbl_sms.fabrica = {$login_fabrica} ";			
		} else {
			$join_fabrica = "
			    JOIN tbl_os ON tbl_sms.os = tbl_os.os
			    JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
			    JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
			";

			$where_fabrica = "
			    tbl_sms.fabrica = {$login_fabrica}
			    AND tbl_os.fabrica = {$login_fabrica}
			";
		}


		$order_by = "";
	}

	if (!empty($status)) {

		if ($status == 2) {
			$fielStatus = " AND tbl_sms.status_sms = 'Erro no Envio'";
		} elseif ( $status == 1) {
			$fielStatus = " AND tbl_sms.status_sms = 'Enviada com Sucesso'";
		} elseif($status == 3){
			$fielStatus = " AND tbl_sms.status_sms IS NULL";
		}
	}


	if(!empty($os)) {

		$whereOS = " AND tbl_os.sua_os = '$os' ";

	}

	if (!empty($origem_envio)) {

		if ($login_fabrica == 160) {

			switch ($origem_envio) {
				case 'abertura':
					$fielOrigem = "AND tbl_sms.texto_sms ILIKE '%ABERTA%'";
					break;
				case 'faturamento':
					$fielOrigem = "AND tbl_sms.texto_sms ILIKE '%EM ANDAMENTO%'";
					break;
				case 'conserto':
					$fielOrigem = "AND tbl_sms.texto_sms ILIKE '%CONCLUIDA%'";
					break;
				case 'fechamento':
					$fielOrigem = "AND tbl_sms.texto_sms ILIKE '%PROD.ENTREGUE%'";
					break;
			}
		}elseif ($login_fabrica == 35){
			switch ($origem_envio) {
				case 'conserto':
					$fielOrigem = "AND tbl_sms.texto_sms ILIKE '%Seu produto Cadence / Oster%'";
					break;
				case 'interacao':
					$fielOrigem = "AND tbl_sms.texto_sms NOT ILIKE '%Seu produto Cadence / Oster%'
								   AND tbl_sms.origem != 'sms_pesquisa'";
					break;
				case 'pesquisa':
					$fielOrigem = "AND tbl_sms.origem = 'sms_pesquisa'";
					break;
			}
		} else if (in_array($login_fabrica, array(3)) && $origem_envio == 3) {
			$fielOrigem = "
			    AND tbl_sms.os IS NOT NULL
			    AND tbl_sms.texto_sms ILIKE '%foi consertado%'
			";
		}elseif ($login_fabrica == 123){
			switch ($origem_envio) {
				case 'abertura':
					$fielOrigem = "AND tbl_sms.texto_sms NOT ILIKE '%registrada para seu produto%'
									AND tbl_sms.fabrica = $login_fabrica";
					break;
				case 'conserto':
					$fielOrigem = "AND tbl_sms.texto_sms ILIKE '%aguardar o reparo e contato da%'
									AND tbl_sms.fabrica = $login_fabrica";
					break;
				case 'retirada':
					$fielOrigem = "AND tbl_sms.texto_sms ILIKE '%reparo do seu equipamento%'
									AND tbl_sms.fabrica = $login_fabrica";
					break;
			}
		} else {
			if ($origem_envio == 1 ) {
				$fielOrigem = "AND tbl_sms.os IS NOT NULL";
			} else if ($origem_envio == 2) {

				$fielOrigem = "AND tbl_sms.treinamento IS NOT NULL";

			} else if ($origem_envio == 8) {

				$fielOrigem = "AND tbl_sms.hd_chamado IS NOT NULL";
			}
		}

        if ($origem_envio != 2) {
            $campoOs = "
                tbl_os.os AS os_fabrica,
                tbl_os.sua_os,
            ";

            $groupOs = "
                os_fabrica,
                tbl_os.sua_os,
            ";
        }
	}
	if (empty($msg_erro)) {

		if ($login_fabrica != 10) {
			system("php ../rotinas/telecontrol/atualiza-sms-detalhado.php {$login_fabrica}",$rest);
		}


		if(!empty($aux_data_inicial) and !empty($aux_data_final)) {
			$whereData = "AND ( tbl_sms.data BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'  
			     OR tbl_sms_resposta.data BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59')";
		}

		$sql = "SELECT
						$campoOs
						$campo_treino
						tbl_sms.sms,
						tbl_sms.fabrica,
						tbl_sms.data as data_envio,
						tbl_sms.hd_chamado,
						tbl_sms.os,
						tbl_sms.destinatario,
						tbl_sms.status_sms,
						tbl_sms.texto_sms,
						tbl_sms.credito_envio,
						tbl_sms.admin,
						tbl_sms.origem,
						$campo_os
						tbl_posto_fabrica.codigo_posto,
						$campo_marca
						SUM(tbl_sms_resposta.credito_resposta) as credito_resposta
					FROM tbl_sms
						$join_fabrica
						$cond_produto
						$cond_marca
						LEFT JOIN tbl_sms_resposta ON tbl_sms.sms = tbl_sms_resposta.sms
					WHERE $where_fabrica
						$fielStatus
						$fielOrigem
						$where_marca
						$whereData
						$whereOS
					GROUP BY
                    $groupOs
                    $campo_treino
					tbl_sms.sms,
					tbl_sms.fabrica,
					data_envio,
					tbl_sms.hd_chamado,
					tbl_sms.os,
					tbl_sms.destinatario,
					tbl_sms.status_sms,
					tbl_sms.texto_sms,
					tbl_sms.credito_envio,
					tbl_sms.admin,
					$campo_os
					tbl_posto_fabrica.codigo_posto,
					credito_resposta
					$campo_group_by
					$order_by;";
 					//exit(nl2br($sql));
			$res_pesq = pg_query($con, $sql);

		if ($_POST["gerar_excel"]) {
			if (pg_num_rows($res_pesq)>0) {
				$data = date("d-m-Y-H-i");
				$fileName = "relatorio_sms_{$login_admin}.csv";
				$file = fopen("/tmp/{$fileName}", "w");


				$head = "OS;Call-Center;Data Envio;Destinatário;Status Mensagem;Conteúdo do SMS;Conteúdo da Resposta;Créditos Envio;Créditos Resposta;Valor Total\r\n";


				if (!$sms_callcenter) {
					$head = "OS;Data Envio;Destinatário;Origem do Envio;";

					if ($login_fabrica == 35) {
						$head .= "Admin;";
					}

					$head .= "Status Mensagem;SMS-Envio;Créditos-Envio;Data Resposta;SMS-Resposta;Créditos-Resposta;Valor Total\r\n";
                    if ($origem_envio == 2 && $login_fabrica == 1) {
                        $head = "Treinamento;";
                    } else {
                        $head = "OS;";
                    }
                    if ($login_fabrica == 1) {
                        $head .= "Marca;";
                    } else if (!in_array($login_fabrica, array(35))) {
                    	$head .= "Call-Center;";
                    }

					$head .= "Data Envio;Destinatário;Origem do Envio;";

					if ($login_fabrica == 35) {
						$head .= "Admin;";
					}

					$head .="Status Mensagem;SMS-Envio;Créditos-Envio;Data Resposta;SMS-Resposta;Créditos-Resposta;Valor Total\r\n";
				}

				if ($login_fabrica == 10) {
					$head = "Fábrica;$head";
				}

				fwrite($file, $head);
				$body = '';
				$x_valor_sms_tot = 0;
				$aux_vef = array();
				for ($x=0; $x<pg_num_rows($res_pesq);$x++){

					$x_sms              = pg_fetch_result($res_pesq, $x, 'sms');
					$x_marca              = pg_fetch_result($res_pesq, $x, 'marca');
					$x_fabrica          = (int) pg_fetch_result($res_pesq, $x, 'fabrica');
					$x_data_envio       = pg_fetch_result($res_pesq, $x, 'data_envio');
					$x_hd_chamado       = pg_fetch_result($res_pesq, $x, 'hd_chamado');
					$x_os                   = pg_fetch_result($res_pesq, $x, 'os_fabrica');
                    $x_treinamento_titulo   = pg_fetch_result($res_pesq, $x, 'titulo');
					$x_sua_os           = pg_fetch_result($res_pesq, $x, 'sua_os');
					$x_codigo_posto           = pg_fetch_result($res_pesq, $x, 'codigo_posto');
					$x_destinatario     = pg_fetch_result($res_pesq, $x, 'destinatario');
					$x_status_sms       = pg_fetch_result($res_pesq, $x, 'status_sms');
					$x_texto_sms        = utf8_decode( pg_fetch_result($res_pesq, $x, 'texto_sms') );

					if ($login_fabrica == 35 && empty($x_os)) {
						$x_os = pg_fetch_result($res_pesq, $x, 'os');
					}

					if ($login_fabrica == 10) {
						if (in_array($x_sms, $aux_vef)) {
							continue;
						}
						$aux_vef[] = $x_sms;
						$aux_sql = "SELECT nome FROM tbl_fabrica WHERE fabrica = $x_fabrica LIMIT 1";
						$aux_res = pg_query($con, $aux_sql);
						$aux_fab = pg_fetch_result($aux_res, 0, 'nome');
						$body   .= "$aux_fab;";
					}

					if($login_fabrica == 1){
						$nome_marca       = mb_strtoupper(pg_fetch_result($res_pesq, $x, 'nome_marca'));			
						$linha 				= pg_fetch_result($res_pesq, $x, 'linha');
						
						if($linha == 199){
							$nome_marca = "ELETRO-PORTÁTEIS";
						}

						$nome_marca = $nome_marca;
					}


					$x_credito_envio    = pg_fetch_result($res_pesq, $x, 'credito_envio');
					$x_credito_resposta = pg_fetch_result($res_pesq, $x, 'credito_resposta');
					$sqldata = "SELECT '$x_data_envio'::timestamp between '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'";
					$resdata = pg_query($con, $sqldata);
					if(pg_fetch_result($resdata,0,0) == 'f') {
						$x_credito_envio = 0 ;
					}

					$x_qtde_creditos_consumidos = ceil((int)$x_credito_envio + (int)$x_credito_resposta);

					if ($login_fabrica == 10) {
						if ($x_fabrica == 1) {
							$valor_unitario_sms = 0.13;
						}else if ($x_fabrica == 151) {
							$valor_unitario_sms = 0.11;
						}else if (in_array($x_fabrica, array(3,35))) {
							$valor_unitario_sms = 0.12;
						} else {
							$valor_unitario_sms = 0.15;
						}
					}

					$x_valor_sms = $x_qtde_creditos_consumidos * $valor_unitario_sms;
					$x_valor_sms_tot = $x_valor_sms_tot + $x_valor_sms;
					$x_valor_sms = "R$ ".number_format($x_valor_sms, 2, ",", ".");

					if ($x_credito_resposta > 0) {
						$sql_resp = "SELECT
									tbl_sms_resposta.sms,
									tbl_sms_resposta.resposta,
									tbl_sms_resposta.credito_resposta,
									tbl_sms_resposta.data
								FROM
									tbl_sms_resposta
								WHERE
									tbl_sms_resposta.sms = {$x_sms};";
						$res_resp = pg_query($con,$sql_resp);

						if (pg_num_rows($res_resp) > 0) {
							$x_resposta_sms= array();
							$x_value_data = array();

							$linha = 0;
							while ( $dados = pg_fetch_object($res_resp,$linha)) {
								$x_resposta_sms[] = $dados->resposta;
								$x_value_data[] = mostra_data_hora($dados->data);
								$linha++;
							}
							$x_resposta_sms = implode(" | ", $x_resposta_sms);
							$x_value_data = implode(" | ", $x_value_data);
						}
					}

					if ($login_fabrica == 10 && $x_fabrica == 1) {
						$aux_sql = "SELECT posto, sua_os FROM tbl_os WHERE os = $x_os";
						$aux_res = pg_query($con, $aux_sql);
						$aux_pos = pg_fetch_result($aux_res, 0, 'posto');
						$aux_sua = pg_fetch_result($aux_res, 0, 'sua_os');

						$aux_sql = "SELECT codigo_posto FROM tbl_posto_fabrica WHERE fabrica = 1 AND posto = $aux_pos";
						$aux_res = pg_query($con, $aux_sql);
						$aux_cop = pg_fetch_result($aux_res, 0, 'codigo_posto');

						$x_sua_os = $aux_cop.$aux_sua;
					}
					
					if ($sms_callcenter) {
						$body .= "$x_sua_os;$x_hd_chamado;".mostra_data_hora($x_data_envio).";$x_destinatario;$x_status_sms;$x_texto_sms;$x_resposta_sms;$x_credito_envio;$x_credito_resposta;$x_valor_sms\r\n";
					} else {
						if ($login_fabrica == 35) {
							$texto = "Seu produto Cadence / Oster";
							$pos = strpos($x_texto_sms, $texto);

							if ($pos === false) {
								$x_origem_envio = "Interacao OS";
							} else {
								$x_origem_envio = "Conserto OS";
							}
						} else {
                            $x_origem_envio = (empty($x_os) && $x_fabrica == "1") ? "Treinamento" : "Fechamento OS";
						}


						if (empty($x_status_sms)) {
							$x_status_sms = "Aguardando Envio";
						}

						if (!in_array($login_fabrica, [1])) {
							if (!in_array($login_fabrica, array(10)) && $x_fabrica != 1) {
								$x_sua_os = $x_os;
							}
						} else {
                            if (empty($x_os)) {
                                $x_sua_os = $x_treinamento_titulo;
                            } else {
                                $x_sua_os = $x_codigo_posto.$x_sua_os;
                            }
						}

							$aux_sql = "SELECT destinatario, texto_sms, credito_envio, os, hd_chamado FROM tbl_sms WHERE sms = $x_sms";
							$aux_res = pg_query($con, $aux_sql);

							$aux_os          = pg_fetch_result($aux_res, 0, 'os');
							$aux_hd          = pg_fetch_result($aux_res, 0, 'hd_chamado');
							$x_destinatario  = pg_fetch_result($aux_res, 0, 'destinatario');
							$x_texto_sms     = pg_fetch_result($aux_res, 0, 'texto_sms');
							$x_texto_sms  = mb_detect_encoding($x_texto_sms, 'UTF-8', true) ? utf8_decode($x_texto_sms) : $x_texto_sms;

							if (empty($aux_os) && !empty($aux_hd)) {
								$x_origem_envio = "Call-Center";
							} else if (empty($aux_hd) && !empty($aux_os)) {
								$x_origem_envio = "Ordem de Serviço";
							}

							$aux_sql = "SELECT TO_CHAR(data, 'DD/MM/YYYY') AS data_resposta FROM tbl_sms_resposta WHERE sms = $x_sms ORDER BY data DESC LIMIT 1";
							$aux_res = pg_query($con, $aux_sql);

							$quebra_linha    = array("\n","<br>","<BR>","\nr","\r",";","|");
							$xx_texto_sms    = str_replace($quebra_linha, "", $x_texto_sms);
							$xx_status_sms   = str_replace($quebra_linha, "", $x_status_sms);
							$xx_resposta_sms = str_replace($quebra_linha, "", $x_resposta_sms);
							$xx_resposta_sms  = mb_detect_encoding($xx_resposta_sms, 'UTF-8', true) ? utf8_decode($xx_resposta_sms) : $xx_resposta_sms;
							$data_resposta   = pg_fetch_result($aux_res, 0, 'data_resposta');

							if ($login_fabrica == 1 or $login_fabrica == 3) {
								$aux_sql = "SELECT tbl_os.sua_os, tbl_posto_fabrica.codigo_posto
											FROM tbl_os
											JOIN tbl_posto_fabrica ON tbl_posto_fabrica.fabrica = $login_fabrica AND tbl_posto_fabrica.posto = tbl_os.posto
											WHERE tbl_os.os = $aux_os";
								$aux_res = pg_query($con, $aux_sql);

								$aux_sua_os = pg_fetch_result($aux_res, 0, 'sua_os');
								$aux_posto  = pg_fetch_result($aux_res, 0, 'codigo_posto');
								$aux_os     = ($login_fabrica == 1) ? $aux_posto . $aux_sua_os : $aux_sua_os;
								$nome_marca = ($login_fabrica == 1) ? $nome_marca . ";" : "";
							} else {
								$nome_marca = "";
							}

							$body .= "$aux_os;";
							$body .= ($login_fabrica == 1) ? "" : "$aux_hd;";
							$body .= "$nome_marca";
							$body .= mostra_data_hora($x_data_envio).";";
							$body .= "$x_destinatario;";
							$body .= "$x_origem_envio;";
							$body .= "$xx_status_sms;";
							$body .= "$xx_texto_sms;";
							$body .= "$x_credito_envio;";
							$body .= "$data_resposta;";
							$body .= "$xx_resposta_sms;";
							$body .= "$x_credito_resposta;";
							$body .= "$x_valor_sms;";

						if ($login_fabrica == 35) {
							unset ($nome_completo);

							$aux_admin = trim(pg_fetch_result($res_pesq, $x, 'admin'));

							if (!empty($aux_admin)) {
								$aux_sql = "SELECT nome_completo FROM tbl_admin WHERE admin = $aux_admin";
								$aux_res = pg_query($con, $aux_sql);

								$nome_completo = pg_fetch_result($aux_res, 0, 'nome_completo');
							} else {
								$nome_completo = "";
							}

							$aux_sql = "SELECT TO_CHAR(data, 'DD/MM/YYYY') AS data_resposta FROM tbl_sms_resposta WHERE sms = $x_sms ORDER BY data DESC LIMIT 1";
							$aux_res = pg_query($con, $aux_sql);

							$data_resposta = pg_fetch_result($aux_res, 0, 'data_resposta');

							$quebra_linha  = array("\n","<br>","<BR>","\nr","\r",";","|");
							$xx_texto_sms  = str_replace($quebra_linha, "", $x_texto_sms);
							$xx_status_sms = str_replace($quebra_linha, "", $x_status_sms);

							$body .= "$nome_completo;";
							$body .= "$xx_status_sms;";
							$body .= "$xx_texto_sms;";
							$body .= "$x_credito_envio;";
							$body .= "$data_resposta;";
							$body .= "$x_resposta_sms;";
							$body .= "$x_credito_resposta;";
							$body .= "$x_valor_sms;";
						}

						if (strlen($dados_linha) > 0) {
							$body .= $dados_linha;
						}
					}
					unset($x_resposta_sms);
					unset($x_value_data);

					 $body .= "\n";
				}
				
				$x_valor_sms_tot = "R$ ".number_format($x_valor_sms_tot, 2, ",", ".");

				$auxiliar = ";";
				$body .= "$auxiliar;;;;;;;;;TOTAL GERAL;$x_valor_sms_tot\r\n";

			    fwrite($file, $body);
			    fclose($file);
			    if (file_exists("/tmp/{$fileName}")) {

	                system("mv /tmp/{$fileName} xls/{$fileName}");

	                echo "xls/{$fileName}";
				}
			}
			exit;
		}
	}
}

if (isset($_POST["ajax_atualiza_resposta"]) && !empty($_POST["sms_ajax"])) {
	$sms_ajax = $_POST["sms_ajax"];

	$sql = "SELECT data,fabrica,sms FROM tbl_sms WHERE sms = {$sms_ajax} AND fabrica = {$login_fabrica};";
	$res = pg_query($con,$sql);

	if (pg_num_rows($res) > 0) {
		/*$sys = system("php ../rotinas/telecontrol/atualiza-sms-resposta.php {$login_fabrica} {$sms_ajax}",$rest);
		var_dump($sys);*/
		if ($login_fabrica != 10) {
			if (system("php ../rotinas/telecontrol/atualiza-sms-resposta.php {$login_fabrica} {$sms_ajax}",$rest)) {
				$retorno = array("error" => utf8_encode("Aguarde 30s para atualizar!"));
			}
		}
	} else {
		$retorno = array("error" => utf8_encode("SMS informado não encontrado!"));
	}

	if (empty($retorno)) {
		$sql = "SELECT
						tbl_sms_resposta.sms,
						tbl_sms_resposta.resposta,
						tbl_sms_resposta.credito_resposta,
						tbl_sms_resposta.data,
						tbl_sms.credito_envio
					FROM
						tbl_sms_resposta
						JOIN tbl_sms USING(sms)
					WHERE
						tbl_sms_resposta.sms = {$sms_ajax}
						AND tbl_sms.fabrica = {$login_fabrica};";
		$res = pg_query($con,$sql);
		// echo nl2br($sql);

		if (pg_num_rows($res) > 0) {
			$ajax_resp_sms = pg_fetch_result($res, 0, resposta);
			$ajax_cred_resp = pg_fetch_result($res, 0, credito_resposta);
			$ajax_cred_env = pg_fetch_result($res, 0, credito_envio);

			$tot_credito = ($ajax_cred_resp + $ajax_cred_env) * $valor_unitario_sms;
			$tot_credito = number_format($tot_credito, 2, ",", ".");

			if (!empty($ajax_cred_resp) AND !empty($ajax_resp_sms)) {
				$retorno = array("ok" => array("resposta_sms" => $ajax_resp_sms, "credito_resposta" => $ajax_cred_resp,"tot_credito" => $tot_credito));
			} else {
				$retorno = array("nulo" => utf8_encode("Resposta não encontrada!"));
			}
		} else {
			$retorno = array("error" => utf8_encode("Resposta não encontrada!"));
		}
	}

	exit(json_encode($retorno));
}

if ($_GET["shadowbox"] == 'resposta_sms') {
	$sb_sms = $_GET['sms'];

	$aux_sql = "SELECT fabrica FROM tbl_sms WHERE sms = $sb_sms LIMIT 1";
	$aux_res = pg_query($con, $aux_sql);
	$sms_fab = (int) pg_fetch_result($aux_res, 0, 0);

    $sql_resposta = "SELECT
						tbl_posto_fabrica.codigo_posto||tbl_os.sua_os as os_fabrica,
						tbl_sms.sms,
						tbl_sms.data as data_envio,
						tbl_sms.hd_chamado,
						tbl_sms.os,
						tbl_sms.destinatario,
						tbl_sms.status_sms,
						tbl_sms.texto_sms,
						tbl_sms_resposta.resposta,
						tbl_sms.credito_envio,
						tbl_sms_resposta.credito_resposta,
						tbl_sms_resposta.data as data_resposta
					FROM tbl_sms
						LEFT JOIN tbl_os ON tbl_sms.os = tbl_os.os AND tbl_os.fabrica = {$sms_fab}
						LEFT JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto AND tbl_os.fabrica = {$sms_fab}
						LEFT JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = {$sms_fab}
						LEFT JOIN tbl_sms_resposta ON tbl_sms.sms = tbl_sms_resposta.sms
					WHERE tbl_sms.fabrica = {$sms_fab}
						AND tbl_sms.sms = {$sb_sms};";
    $res_resposta = pg_query($con,$sql_resposta);
    # echo nl2br($sql_resposta);
    ?>
    <!DOCTYPE html>
    <html>
    	<head>
    		<link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
    		<link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
    		<link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
    		<link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />
    		<link href="plugins/dataTable.css" type="text/css" rel="stylesheet" />
    		<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
    		<script src="bootstrap/js/bootstrap.js"></script>
    		<script src="plugins/dataTable.js"></script>
    		<script src="plugins/resize.js"></script>
		</head>
		<body>
    		<div class='titulo_tabela' style="padding: 10px;">Histórico de Respostas SMS</div>
	<?php
    $resultado = pg_num_rows($res_resposta);
    if ($resultado > 0) { ?>
        <br>
        <table class='table table-striped table-bordered  table-large'>
        	<thead>
        		<tr>
        			<th>Resposta</th>
        			<th>Data</th>
        			<th>Crédito</th>
        			<th>Valor</th>
        		</tr>
        	</thead>
        	<tbody>
        		<?php
        		$sb_tot_resp = 0;
        		for ($i=0;$i<$resultado;$i++){
		            $sb_sua_os = pg_fetch_result($res_resposta, $i, os_fabrica);
					$sb_sms = pg_fetch_result($res_resposta, $i, sms);
					$sb_data_envio = pg_fetch_result($res_resposta, $i, data_envio);
					$sb_os = pg_fetch_result($res_resposta, $i, os);
					$sb_destinatario = pg_fetch_result($res_resposta, $i, destinatario);
					$sb_resposta = pg_fetch_result($res_resposta, $i, resposta);
					$sb_credito_resposta = pg_fetch_result($res_resposta, $i, credito_resposta);
					$sb_data_resposta = pg_fetch_result($res_resposta, $i, data_resposta);

					$sb_tot_resp += $sb_credito_resposta;

					if ($login_fabrica == 10) {
						if ($sms_fab == 1) {
							$valor_unitario_sms = 0.13;
						}else if ($sms_fab == 151) {
							$valor_unitario_sms = 0.11;
						}else if (in_array($sms_fab, array(3,35))) {
							$valor_unitario_sms = 0.12;
						} else {
							$valor_unitario_sms = 0.15;
						}
					}

					$sb_valor_resposta = $sb_credito_resposta * $valor_unitario_sms;
					$sb_valor_resposta = "R$ ".number_format($sb_valor_resposta, 2, ",", ".");
					?>
					<tr>
						<td><?=$sb_resposta?></td>
						<td><?=mostra_data_hora($sb_data_resposta)?></td>
						<td class="tac"><?=$sb_credito_resposta?></td>
						<td class="tac"><?=$sb_valor_resposta?></td>

					</tr>
		        <?php
		        } ?>
        	</tbody>
        	<tfoot>
				<tr>
					<th colspan="2" style='text-align: right !important;'> Total: </th>
					<th class="tac"><?php echo $sb_tot_resp; ?></th>
					<th class="tac">
						<?php
						$sb_tot_resp = $sb_tot_resp * $valor_unitario_sms;
						$sb_tot_resp = "R$ ".number_format($sb_tot_resp, 2, ",", ".");
						echo $sb_tot_resp;
						?>
					</th>
				</tr>
			</tfoot>
        </table>
    <?php
    }else{?>
        <br />
        <div class="alert alert-warning"><h4><b>Não há respostas para este SMS</b><br> </h4></div>

    <?php
    } ?>
    	</body>
	</html>
	<?php
	exit;
}


include_once 'cabecalho_new.php';

$sms_callcenter = (in_array($login_fabrica, array(80,104,151,169,170,174)))? true : false ;

$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable",
	"multiselect"
);

include("plugin_loader.php");

?>

<script type="text/javascript" charset="utf-8">
	$(function() {
		$.dataTableLoad({table: "#lista_sms"});

		$("#marca").multiselect({
           selectedText: "selecionados # de #"
        });

        $("#fabricas").multiselect({
           selectedText: "selecionados # de #"
        });

		$("#data_inicial").datepicker({ maxDate: 0, dateFormat: "dd/mm/yy" }).mask("99/99/9999");
		$("#data_final").datepicker({ maxDate: 0, dateFormat: "dd/mm/yy" }).mask("99/99/9999");

	});

	function atualizaResposta(sms){
		var img = $("<img />", { src: "imagens/loading_img.gif", css: { width: "30px", height: "30px" } });

		if (sms != 0) {
			$.ajax({
				async: false,
				url: "<?=$PHP_SELF?>",
				type: "POST",
				data: { ajax_atualiza_resposta: true,
						sms_ajax: sms
				},
				beforeSend: function() {
					if ($("#sms_resp_"+sms).next("img").length == 0) {
						$("#atualizar_sms_"+sms).hide();
						$("#loading_sms_"+sms).show();
					}
					$("button[id^=atualizar_sms_]").each(function(){
						$(this).attr('disabled',true);
					});
				},
				complete: function(data) {
					data = $.parseJSON(data.responseText);

					if (data.error) {
						alert(data.error);

						$("#loading_sms_"+sms).hide();
						$("#atualizar_sms_"+sms).show();

						setTimeout(function(){
							$("button[id^=atualizar_sms_]").each(function(){
								$(this).attr('disabled',false);
							});
						},30000);
					} else if(data.nulo) {
						alert(data.nulo);

						$("#loading_sms_"+sms).hide();
						$("#atualizar_sms_"+sms).show();

						setTimeout(function(){
							$("button[id^=atualizar_sms_]").each(function(){
								$(this).attr('disabled',false);
							});
						},30000);
					} else {
						$("#loading_sms_"+sms).hide();
						$("#resposta_sms_"+sms).show();
						$("#cred_resp_"+sms).text(data.ok.credito_resposta);
						$("#tot_cred_"+sms).text('');
						$("#tot_cred_"+sms).text('R$ '+data.ok.tot_credito);

						setTimeout(function(){
							$("button[id^=atualizar_sms_]").each(function(){
								$(this).attr('disabled',false);
							});
						},30000);
					}
				}
			});
		}

		if(typeof cidade != "undefined" && cidade.length > 0){

			$('#cli_cidade option[value='+cidade+']').attr('selected','selected');

		}
	}
	function sms_resposta(conteudo){

		Shadowbox.init();

        Shadowbox.open({
            content: "<?=$PHP_SELF?>?shadowbox=resposta_sms&sms="+conteudo,
            player: "iframe",
            width: 800,
            height: 600,
            options: {
                enableKeys: false
            }
        });

	}
</script>

<?php
if (count($msg_erro["msg"]) > 0) { ?>
	<div class="alert alert-error" >
		<button type="button" class="close" data-dismiss="alert" >&times;</button>
		<strong><?=implode("<br />", $msg_erro['msg'])?></strong>
	</div>
<?php
} ?>
<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>

<form name='frm_pesquisa' method='POST' action='<?=$PHP_SELF?>' class="form-search form-inline tc_formulario">
	<div class="titulo_tabela">Parâmetros de Pesquisa</div>
	<br />
	<div class='row-fluid'>
		<div class='span1'></div>
		<div class="span2">
			<div class="control-group <?=(in_array('data_inicial', $msg_erro['campos'])) ? 'error' : ''?>">
				<label class='control-label'>Data Inicial</label>
				<div class='controls controls-row'>
					<h5 class='asteristico'>*</h5>
					<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=$data_inicial?>">
				</div>
			</div>
		</div>
		<div class="span2">
			<div class="control-group <?=(in_array('data_final', $msg_erro['campos'])) ? 'error' : ''?>">
				<label class='control-label'>Data Final</label>
				<div class='controls controls-row'>
					<h5 class='asteristico'>*</h5>
					<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value= "<?=$data_final?>">
				</div>
			</div>
   		</div>
   		<div class="span3">
			<div class="control-grup">
				<label class='control-label'>Status</label>
				<div class='controls controls-row'>
					<select name='status' id='status' class='span12'>
					<option value='0' <?if($status=='0') echo " SELECTED ";?>>Todos</option>
					<option value='3' <?if($status=='3') echo " SELECTED ";?>>Aguardando Envio</option>
					<option value='1' <?if($status=='1') echo " SELECTED ";?>>Enviada com Sucesso</option>
					<option value='2' <?if($status=='2') echo " SELECTED ";?>>Erro no Envio</option>
				</select>
				</div>
			</div>
   		</div>
   		<?php?>
   		<div class="span3">
			<div class="control-grup">
				<label class='control-label'>Origem do Envio</label>
				<div class='controls controls-row'>
					<select name='origem_envio' id='origem_envio' class='span12'>
					<?php
					if ($login_fabrica == 160) { ?>
						<option value='' <?php if($origem_envio == '') echo " SELECTED "; ?>> Todos </option>
						<option value='abertura' <?php if($origem_envio == 'abertura') echo " SELECTED "; ?>> Abertura OS </option>
						<option value='faturamento' <?php if($origem_envio == 'faturamento') echo " SELECTED "; ?>> Faturamento</option>
						<option value='conserto' <?php if($origem_envio == 'conserto') echo " SELECTED "; ?>> Conserto OS </option>
						<option value='fechamento' <?php if($origem_envio == 'fechamento') echo " SELECTED "; ?>> Fechamento OS </option>
					<?php
					}elseif (in_array($login_fabrica,[35])){
					?>
						<option value='' <?php if($origem_envio == '') echo " SELECTED "; ?>> Todos </option>
						<option value='conserto' <?php if($origem_envio == 'conserto') echo " SELECTED "; ?>> Conserto OS </option>
						<option value='interacao' <?php if($origem_envio == 'interacao') echo " SELECTED "; ?>> Interação OS </option>
						<option value='pesquisa' <?php if($origem_envio == 'pesquisa') echo " SELECTED "; ?>> Pesquisa Satisfação </option>
					<?php
					}elseif (in_array($login_fabrica,[123])){
					?>
						<option value='' <?php if($origem_envio == '') echo " SELECTED "; ?>> Todos </option>
						<option value='abertura' <?php if($origem_envio == 'abertura') echo " SELECTED "; ?>> Abertura OS </option>
						<option value='conserto' <?php if($origem_envio == 'conserto') echo " SELECTED "; ?>> Conserto OS </option>
						<option value='retirada' <?php if($origem_envio == 'retirada') echo " SELECTED "; ?>> Retirada OS </option>
					<?php
					} else { ?>
						<option value='0' <?php if($origem_envio == '0') echo " SELECTED "; ?>> Todos </option>
                        <?php if ($login_fabrica == 1) { ?>
	                        <option value='2' <?=($origem_envio == '2') ? " SELECTED " : ""; ?>> Treinamento </option>
						<?php }

                        if (in_array($login_fabrica, array(3,11,80,101,104,151,167,169,172,174,203))) { ?>
                        	<?php if (!in_array($login_fabrica, array(167,174,203))) {?>
                        		<option value="1"<?=($origem_envio == '1') ? " SELECTED " : ""; ?>> Enviado pela Ordem de Serviço </option>
							<?php }	

                        	if (!in_array($login_fabrica, array(3,11,167,203))) {?>
                        		<option value="8"<?=($origem_envio == '8') ? " SELECTED " : ""; ?>> Enviado pelo Call-Center </option>
                        	<?php }	
                        	
                        	if (!in_array($login_fabrica, array(3,11,80,101,104,167,174,203))) {?>
                        		<option value="9"<?=($origem_envio == '9') ? " SELECTED " : ""; ?>> Enviado pela Providência </option>
                        	<?php }
                        }

                        if (!in_array($login_fabrica, array(3,11,80,101,104,151,169,172))) {?>
							<option value='1' <?php if($origem_envio == '1') echo " SELECTED "; ?>> Fechamento OS </option>
					<?php }
					}
					?>
				</select>
				</div>
			</div>
   		</div>
   		<div class="span1"></div>
   	</div>
	<?php if(!in_array($login_fabrica, array(1))){ ?>
	<div class='row-fluid'>
		<div class='span1'></div>
		<div class="span2">
			<div class="<? echo $controlgrup?>">
				<label class='control-label'>Ordem de Serviço</label>
				<div class='controls controls-row'>
					<input type='text' name='os' id='os' value='<?=$os?>' class='span12'>
				</div>
			</div>
		</div>
	</div>
    <? } ?>
	<?php if(in_array($login_fabrica, array(1, 3))){ ?>
   	<div class='row-fluid'>
		<div class='span1'></div>
		<div class="span2">
			<div class="control-group <?=(in_array('marca', $msg_erro['campos'])) ? 'error' : ''?>">
				<label class='control-label'>Marca</label>
				<div class='controls controls-row'>
					<select name="marca[]" id="marca" multiple="multiple" class='span12'>
						<?php if ($login_fabrica == 1) { ?>
							<option value="11" <?php if(in_array(11, $marca)){ echo " selected "; } ?> >BLACK&DECKER</option>
							<option value="237" <?php if(in_array(237, $marca)){ echo " selected "; } ?>>DEWALT</option>
							<option value="239" <?php if(in_array(239, $marca)){ echo " selected "; } ?>>STANLEY</option>
							<option value="4" <?php if(in_array(4, $marca)){ echo " selected "; } ?>>ELETRO-PORTÁTEIS</option>
						<?php }  else if ($login_fabrica == 3) {?>
							<option value="110" <?php if(in_array(110, $marca)){ echo " selected "; } ?>>PHILCO</option>
							<option value="8" <?php if(in_array(8, $marca)){ echo " selected "; } ?>>BRITÂNIA</option>
						<?php } ?>
					</select>
				</div>
			</div>
		</div>
	</div>
	<?php } ?>
	<?php if($login_fabrica == 10){ /*HD-4084214*/ ?>
   	<div class='row-fluid'>
		<div class='span1'></div>
		<div class="span2">
			<div class="control-group <?=(in_array('fabricas', $msg_erro['campos'])) ? 'error' : ''?>">
				<label class='control-label'>Fábricas</label>
				<div class='controls controls-row'>
					<select name="fabricas[]" id="fabricas" multiple="multiple" class='span12'>
						<?php
							$aux_sql = "
								SELECT fabrica, nome FROM tbl_fabrica WHERE api_secret_key_sms IS NOT NULL AND ativo_fabrica IS TRUE ORDER BY nome
							";
							$aux_res = pg_query($con, $aux_sql);

							for ($x = 0; $x < pg_num_rows($aux_res); $x++) {
								$fabrica = pg_fetch_result($aux_res, $x, 'fabrica');
								$nome    = pg_fetch_result($aux_res, $x, 'nome');
								?> <option value="<?=$fabrica;?>"><?=strtoupper($nome);?></option> <?php
							}
						?>
					</select>
				</div>
			</div>
		</div>
	</div>
	<?php } ?>
	<br />
	<div class="row-fluid">
        <div class="span1"></div>
        <div class="span10">
            <div class="control-group">
                <div class="controls controls-row tac">
                	<span class="msg-btn-pesquisar"></span>
                    <input type="hidden" name="btn_acao"  value=''>
                    <button class="btn btn-pesquisar" name="bt" value='Listar' onclick="javascript:if (document.frm_pesquisa.btn_acao.value!='') alert('Aguarde Submissão'); else{document.frm_pesquisa.btn_acao.value='Listar';document.frm_pesquisa.submit();}" >Pesquisar</button>
                </div>
            </div>
        </div>
        <div class="span4"> </div>
    </div>
</form>

<!-- HD - 3956227 -->
<div class='container'>
    <div class="alert">
        <p>Conteúdo da mensagem a ser enviada:<br>
        	<b>IMPORTANTE:</b> Em casos que o conteúdo do SMS for superior a 160 caracteres, será tarifado mais de um crédito a cada 153 caracteres. <br>
        	Algumas operadoras como a "Oi" e "Sercomtel" não suportam concatenação de mensagens.
        </p>
    </div>  
</div>

<?php

if (isset($res_pesq)) {
	if(pg_num_rows($res_pesq) > 0){
		?>

		<br />

		<br />
		<?php
			$jsonPOST = excelPostToJson($_POST);
		?>
		<div id='gerar_excel' class="btn_excel">
			<input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
			<span><img style="width:40px ; height:40px;" src='imagens/icon_csv.png' /></span>
			<span class="txt">Gerar Arquivo CSV</span>
		</div>
		<br />
		<?
			if ($login_fabrica == 10) { ?>
				<div class="alert alert-info">
					Valor unitário por SMS: (BlackeDecker): <strong>R$ 0,13</strong> (Mondial): <strong>R$ 0,9</strong> (Demais Fábricas): <strong>R$ 0,15</strong>
				</div>
			<?php } else { ?>
				<div class="alert alert-info">
					Valor unitário por SMS: <strong>R$ <?php echo $valor_unitario_sms_desc; ?></strong>
				</div>
			<?php } ?>
		<input type="hidden" id="val_unit_sms" value='<?=$valor_unitario_sms;?>' />
		<br />
		<?php
		if ($login_fabrica != 10) {
			$fabricas[] = $login_fabrica;
		}

		$dados = array();
		foreach ($fabricas as $fabrica) {
			$aux_sql = "SELECT nome FROM tbl_fabrica WHERE fabrica = $fabrica LIMIT 1";
			$aux_res = pg_query($con, $aux_sql);
			$nome    = pg_fetch_result($aux_res, 0, 'nome');
			unset($aux_sql, $aux_res);
			
			if(!empty($aux_data_inicial) and !empty($aux_data_final)) {
				$whereData = "AND tbl_sms.data BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'";
			}
		
			$sql_envio = "SELECT SUM(credito_envio) as tot_envio
							FROM tbl_sms 
							LEFT JOIN tbl_os USING(os)
							WHERE tbl_sms.fabrica = {$fabrica}
							$fielStatus
							$fielOrigem
							$whereData
							$whereOS;";
			$res_envio = pg_query($con,$sql_envio);

			$tot_envio = pg_fetch_result($res_envio, 0, tot_envio);
			if ($tot_envio <= 0) {
				$tot_envio = 0;
			}
			if ($fabrica == 1) {
				$valor_unitario_sms = 0.13;
			}else if ($fabrica == 151) {
				$valor_unitario_sms = 0.11;
			}else if (in_array($fabrica, array(3,35))) {
				$valor_unitario_sms = 0.12;
			} else {
				$valor_unitario_sms = 0.15;
			}

			if($data_ok) {
				$whereData = " AND tbl_sms_resposta.data BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'; ";
			}

			$tot_envio_valor = $tot_envio * $valor_unitario_sms;
			$aux_envio_valor = $tot_envio_valor;
			$tot_envio_valor = "R$ ".number_format($tot_envio_valor, 2, ",", ".");

			$sql_resposta = "SELECT SUM(credito_resposta) as tot_resposta
							FROM tbl_sms_resposta
								JOIN tbl_sms USING(sms)
							WHERE fabrica = {$fabrica}
							$fielStatus
							$fielOrigem
							$whereData
							$whereOS";
			
			$res_resposta = pg_query($con,$sql_resposta);

			$tot_resposta = pg_fetch_result($res_resposta, 0, tot_resposta);
			if ($tot_resposta <= 0) {
				$tot_resposta = 0;
			}
			$tot_resposta_valor = $tot_resposta * $valor_unitario_sms;
			$aux_resposta_valor = $tot_resposta_valor;
			$tot_resposta_valor = "R$ ".number_format($tot_resposta_valor, 2, ",", ".");

			$tot_geral = $tot_resposta + $tot_envio;
			$tot_geral_valor = ($tot_resposta * $valor_unitario_sms) + ($tot_envio * $valor_unitario_sms);
			$aux_geral_valor = $tot_geral_valor;
			$tot_geral_valor = "R$ ".number_format($tot_geral_valor, 2, ",", ".");

			$dados[$fabrica]["nome"]               = $nome;
			$dados[$fabrica]["tot_envio"]          = $tot_envio;
			$dados[$fabrica]["aux_envio_valor"]    = $aux_envio_valor;
			$dados[$fabrica]["tot_envio_valor"]    = $tot_envio_valor;

			$dados[$fabrica]["tot_resposta"]       = $tot_resposta;
			$dados[$fabrica]["aux_resposta_valor"] = $aux_resposta_valor;
			$dados[$fabrica]["tot_resposta_valor"] = $tot_resposta_valor;

			$dados[$fabrica]["tot_geral"]          = $tot_geral;
			$dados[$fabrica]["aux_geral_valor"]    = $aux_geral_valor;
			$dados[$fabrica]["tot_geral_valor"]    = $tot_geral_valor;
		}

		if ($login_fabrica == 10) { ?>
			<table class='table table-striped table-bordered table-hover table-fixed'>
				<thead>
					<tr class='titulo_tabela'>
						<td class="tac" colspan="7">Total SMS no Período de <?=$data_inicial?> a <?=$data_final?></td>
					</tr>
					<tr class='titulo_coluna'>
						<th>Fábrica</th>
						<th colspan="2">SMS de Envio</th>
						<th colspan="2">SMS de Resposta</th>
						<th colspan="2">Total</th>
					</tr>
				</thead>
				<tbody>
			<?php
				$total_geral = array();
				foreach ($dados as $fabrica => $valor) { ?>
					<tr>
						<td class="tal"><?=$valor["nome"];?></td>
						<td class="tar"><?=$valor["tot_envio"];?></td>
						<td class="tar"><?=$valor["tot_envio_valor"];?></td>
						<td class="tar"><?=$valor["tot_resposta"];?></td>
						<td class="tar"><?=$valor["tot_resposta_valor"];?></td>
						<td class="tar"><?=$valor["tot_geral"];?></td>
						<td class="tar"><?=$valor["tot_geral_valor"];?></td>
					</tr>
				<?	$total_geral["tot_envio"]          += $valor["tot_envio"];
					$total_geral["tot_envio_valor"]    += $valor["aux_envio_valor"];
					$total_geral["tot_resposta"]       += $valor["tot_resposta"];
					$total_geral["tot_resposta_valor"] += $valor["aux_resposta_valor"];
					$total_geral["tot_geral"]          += $valor["tot_geral"];
					$total_geral["tot_geral_valor"]    += $valor["aux_geral_valor"];
				}
			?>
				</tbody>
				<tfoot>
					<tr class='titulo_coluna'>
						<td class="tac">Total Geral</td>
						<td class="tac"><?= $total_geral["tot_envio"];?></td>
						<td class="tac"><? echo "R$ ".number_format($total_geral["tot_envio_valor"], 2, ",", ".");?></td>
						<td class="tac"><?=$total_geral["tot_resposta"];?></td>
						<td class="tac"><? echo "R$ ".number_format($total_geral["tot_resposta_valor"], 2, ",", ".");?></td>
						<td class="tac"><?=$total_geral["tot_geral"];?></td>
						<td class="tac"><? echo "R$ ".number_format($total_geral["tot_geral_valor"], 2, ",", ".");?></td>
					</tr>
				</tfoot>
			</table>
		<?php } else {


			if(!empty($os)) {

				$os_sms = " para a os $os";
			}
			
			foreach ($dados as $fabrica => $valor) { ?>
				<table class='table table-striped table-bordered  table-fixed'>
					<thead>
						<tr class='titulo_coluna'>
							<td class="tac" colspan="3">Total SMS no Período de <?=$data_inicial?> a <?=$data_final?> <?=$os_sms?></td>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td class="tac">SMS de Envio</td>
							<td class="tac"><?=$valor["tot_envio"];?></td>
							<td class="tac"><?=$valor["tot_envio_valor"];?></td>
						</tr>
						<tr>
							<td class="tac">SMS de Resposta</td>
							<td class="tac"><?=$valor["tot_resposta"];?></td>
							<td class="tac"><?=$valor["tot_resposta_valor"];?></td>
						</tr>
						<tr>
							<td class="tac">Total</td>
							<td class="tac"><?=$valor["tot_geral"];?></td>
							<td class="tac"><?=$valor["tot_geral_valor"];?></td>
						</tr>
					</tbody>
				</table>
			<?php }
		} ?>

		</div>
		<br />
	<div class="container-fluid">
		<table class='table table-striped table-bordered table-hover table-fixed' id="lista_sms">
			<thead>
				<tr class='titulo_coluna'>
					<?php if ($login_fabrica == 10) { ?>
						<th class='tac'>Fábrica</th>
                    <?php
                    }
                    if ($login_fabrica != 1 || ($login_fabrica == 1 && $origem_envio != 2)) {
                    ?>
					<th class="tac" nowrap>OS</th>
					<?php
                    } else {
                    ?>
					<th class="tac">Treinamento</th>
                    <?php
                    }
					if (in_array($login_fabrica, [1])) { ?>
						<th class="tac">Marca</th>
					<?php
					}
					if ($sms_callcenter) { ?>
						<th class="tac">Call-Center</th>
					<?php
					}
					?>
					<th class="tac date_column" >Data Envio</th>
					<th class="tac" >Destinatário</th>
					<th class="tac" nowrap>Origem do Envio</th>

					<?php if ($login_fabrica == 35) { /*HD - 4355934*/
						?> <th class="tac" >Admin</th> <?php
					} ?>

					<th class="tac" >Status Mensagem</th>
					<th class="tac" style="min-width: 300px;">SMS-Envio</th>
					<th class="tac" nowrap>Créditos-Envio</th>
					<th class="tac" style="min-width: 140px;">SMS-Resposta</th>
					<th class="tac" nowrap>Créditos-Resposta</th>
					<th class="tac" nowrap >Valor Total</th>
				</tr>
			</thead>
			<tbody>
				<?php
				$valor_total_creditos_env = 0;
				$valor_total_creditos_resp = 0;
				$aux_sms = array();

				for ($i = 0 ; $i < pg_num_rows($res_pesq) ; $i++) {
					$t_sms              = pg_fetch_result($res_pesq, $i, 'sms');
					$t_fabrica          = pg_fetch_result($res_pesq, $i, 'fabrica');
					$t_data_envio       = pg_fetch_result($res_pesq, $i, 'data_envio');
					$t_hd_chamado       = pg_fetch_result($res_pesq, $i, 'hd_chamado');
					$t_os               = pg_fetch_result($res_pesq, $i, 'os_fabrica');
					$t_sua_os           = pg_fetch_result($res_pesq, $i, 'sua_os');
                    $treinamento_titulo = pg_fetch_result($res_pesq, $i, 'titulo');
					$codigo_posto       = pg_fetch_result($res_pesq, $i, 'codigo_posto');
					$t_destinatario     = pg_fetch_result($res_pesq, $i, 'destinatario');
					$t_status_sms       = pg_fetch_result($res_pesq, $i, 'status_sms');
					$t_texto_sms        = pg_fetch_result($res_pesq, $i, 'texto_sms');
					$t_credito_envio    = pg_fetch_result($res_pesq, $i, 'credito_envio');
					$t_credito_resposta = pg_fetch_result($res_pesq, $i, 'credito_resposta');
					$t_os_id            = pg_fetch_result($res_pesq, $i, 'os');
					$t_origem           = pg_fetch_result($res_pesq, $i, 'origem');

					if (!in_array($login_fabrica, [1,3,11,35,172])) {
						$t_sua_os = $t_os;
					} else {
						$t_origem_envio = (empty($t_os) && $t_fabrica == "1") ? "Treinamento" : "Fechamento OS";
						$nome_marca       	= mb_strtoupper(pg_fetch_result($res_pesq, $i, 'nome_marca'));
						$linha 				= pg_fetch_result($res_pesq, $i, 'linha');

						if($linha == 199){
							$nome_marca = "ELETRO-PORTÁTEIS";
						}
					}

					$sqldata = "SELECT '$t_data_envio'::timestamp between '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'";
					$resdata = pg_query($con, $sqldata);
					if(pg_fetch_result($resdata,0,0) == 'f') {
						$t_credito_envio = 0 ;
					}
					if ($login_fabrica == 160) {
						if (strpos($t_texto_sms, 'ABERTA')) {
							$t_origem_envio = "Abertura OS";
						} else if (strpos($t_texto_sms, 'EM ANDAMENTO')) {
							$t_origem_envio = "Faturamento";
						} else if (strpos($t_texto_sms, 'CONCLUIDA')) {
							$t_origem_envio = "Conserto OS";
						} else if (strpos($t_texto_sms, 'PROD.ENTREGUE')) {
							$t_origem_envio = "Fechamento OS";
						}

					}

					if ($login_fabrica == 123) {
						if (strpos($t_texto_sms, 'ABERTA')) {
							$t_origem_envio = "Abertura OS";
						} else if (strpos($t_texto_sms, 'CHEGARAM')) {
							$t_origem_envio = "Conserto OS";
						} else if (strpos($t_texto_sms, 'RETIRADO')) {
							$t_origem_envio = "Retirada OS";
						}
					}

					if ($login_fabrica == 35){
						if ($t_origem == 'sms_pesquisa') {
							$t_origem_envio = "Pesquisa de Satisfação";
						} else {
							if (strpos($t_texto_sms, 'Seu produto Cadence / Oster')) {
								$t_origem_envio = "Conserto OS";
							}else{
								$t_origem_envio = "Interacao OS";
							}
						}

					}

					if ($login_fabrica == 10) {
						if (in_array($t_sms, $aux_sms)) {
							continue;
						} else {
							$aux_sms[] = $t_sms;
						}

						$aux_sql = "SELECT os, hd_chamado FROM tbl_sms WHERE sms = $t_sms";
						$aux_res = pg_query($con, $aux_sql);

						$aux_os = pg_fetch_result($aux_res, 0, 'os');
						$aux_hd = pg_fetch_result($aux_res, 0, 'hd_chamado');

						if (empty($aux_os) && !empty($aux_hd)) {
							$t_origem_envio = "Call-Center";
						} else if (empty($aux_hd) && !empty($aux_os)) {
							$t_origem_envio = "Ordem de Serviço";
						} else {
							$t_origem_envio = "";
						}
					}

					if (empty($t_status_sms)) {
						$t_status_sms = "Aguardando Envio";
					}

					$valor_total_creditos_env = $valor_total_creditos_env + $t_credito_envio  ;
					$valor_total_creditos_resp = $valor_total_creditos_resp + $t_credito_resposta ;

					/*HD - 4355934*/
					if ($login_fabrica == 35) {
						unset ($nome_completo);

						$aux_admin = trim(pg_fetch_result($res_pesq, $i, 'admin'));

						if (!empty($aux_admin)) {
							$aux_sql = "SELECT nome_completo FROM tbl_admin WHERE admin = $aux_admin";
							$aux_res = pg_query($con, $aux_sql);

							$nome_completo = pg_fetch_result($aux_res, 0, 'nome_completo');
						} else {
							$nome_completo = "";
						}
					}

					?>
					<tr>
						<?php if ($login_fabrica == 10) {
							$aux_sql = "SELECT nome FROM tbl_fabrica WHERE fabrica = $t_fabrica LIMIT 1";
							$aux_res = pg_query($con, $aux_sql);
							$nome    = pg_fetch_result($aux_res, 0, 'nome');

							unset($aux_sql, $aux_res);
							?> <td><?=$nome;?></td> <?
						} ?>
						<td nowrap>
							<?php
							if ($login_fabrica == 10)  {
								if ($t_fabrica == 1) {
									$aux_sql = "SELECT posto, sua_os FROM tbl_os WHERE os = $t_os";
									$aux_res = pg_query($con, $aux_sql);
									$aux_pos = pg_fetch_result($aux_res, 0, 'posto');
									$aux_sua = pg_fetch_result($aux_res, 0, 'sua_os');

									$aux_sql = "SELECT codigo_posto FROM tbl_posto_fabrica WHERE fabrica = 1 AND posto = $aux_pos";
									$aux_res = pg_query($con, $aux_sql);
									$aux_cop = pg_fetch_result($aux_res, 0, 'codigo_posto');

									echo $aux_cop.$aux_sua;
								} else {
									echo $t_os;
								}
							} else {
								if ($t_fabrica == 1) {
									$aux_sql = "SELECT tbl_os.sua_os, tbl_posto_fabrica.codigo_posto
											FROM tbl_os
											JOIN tbl_posto_fabrica ON tbl_posto_fabrica.fabrica = $login_fabrica AND tbl_posto_fabrica.posto = tbl_os.posto
											WHERE tbl_os.os = $t_os_id";
									$aux_res = pg_query($con, $aux_sql);

									$aux_sua_os = pg_fetch_result($aux_res, 0, 'sua_os');
									$aux_posto  = pg_fetch_result($aux_res, 0, 'codigo_posto');
									$aux_os     = $aux_posto . $aux_sua_os;

									$t_sua_os = $aux_os;
									$t_os     = $t_os_id;
								} else {									
									if (in_array($login_fabrica, array(35,11,172))) {
										$t_os = $t_os_id;
									} else {
										$t_os = $t_sua_os;
									}

									if (strlen($t_sua_os) == 0 && strlen($t_os_id) > 0) {
										$t_sua_os = $t_os_id;
										$t_os     = $t_os_id;
									}
								}
							?> <a href="os_press.php?os=<?=$t_os_id?>" target="_blank"><?=$t_sua_os;?></a>
                            <?
								}
	                            if ($origem_envio == 2) {
	                                echo $treinamento_titulo;
	                            }
                            ?>
						</td>
						<?php
						if (in_array($login_fabrica, [1])) { ?>
							<td nowrap><?=$nome_marca;?></td>
						<?php }

						if ($sms_callcenter) { ?>
							<td nowrap> <a href="callcenter_interativo_new.php?callcenter=<?=$t_hd_chamado?>" target="_blank"><?=$t_hd_chamado;?></a></td>
						<?php
						}?>
						<td nowrap ><?=mostra_data_hora($t_data_envio);?></td>
						<td nowrap ><?=phone_format($t_destinatario);?></td>
						<td nowrap >
							<?php if (strlen($t_origem_envio) == 0 || in_array($login_fabrica, array(1, 3))) {
								$aux_sql = "SELECT os, hd_chamado FROM tbl_sms WHERE sms = $t_sms";
								$aux_res = pg_query($con, $aux_sql);

								$aux_os = pg_fetch_result($aux_res, 0, 'os');
								$aux_hd = pg_fetch_result($aux_res, 0, 'hd_chamado');

								if (empty($aux_os) && !empty($aux_hd)) {
									echo "Call-Center";
								} else if (empty($aux_hd) && !empty($aux_os)) {
									echo "Ordem de Serviço";
								} else if (empty($aux_os) && empty($aux_hd) && $t_fabrica == "1") {
									echo "Treinamento";
								} else {
									echo "";
								}
							} else {
								echo $t_origem_envio;
							} ?>	
						</td>
						<?php if ($login_fabrica == 35) { /*HD - 4355934*/
							?> <td nowrap ><?=$nome_completo?></td> <?
						} ?>
						<td nowrap ><?=$t_status_sms?></td>
						<td ><?=utf8_decode($t_texto_sms);?></td>
						<td class="tac"><?=$t_credito_envio;?></td>
						<?php
						if (empty($t_credito_resposta)) { ?>
							<td id="sms_resp_<?=$t_sms;?>" class="tac">
								<img src="imagens/loading_img.gif" id="loading_sms_<?=$t_sms?>" class="anexo_loading" style="width: 64px; height: 64px; display: none;" />
								<button class="btn btn-info" name="atualizar" id="atualizar_sms_<?=$t_sms?>"  value='atualizar' onclick="atualizaResposta(<?=$t_sms;?>)" >Atualizar</button>
								<button type='button' onclick='sms_resposta("<?=$t_sms?>");' class='btn btn-success' style="display: none"  id="resposta_sms_<?=$t_sms?>" >Ver Resposta</button>
							</td>
							<td class="tac" id="cred_resp_<?=$t_sms?>">
								0
							</td>
						<?php
						} else { ?>
							<td class='tac'>
								<button type='button' onclick='sms_resposta("<?=$t_sms?>");' class='btn btn-success'>Ver Resposta</button>
							</td>
							<td class="tac"><?=$t_credito_resposta;?></td>
						<?php
						}

						$qtde_creditos_consumidos = ceil((int)$t_credito_envio + (int)$t_credito_resposta);

						if ($t_fabrica == 1) {
							$valor_unitario_sms = 0.13;
						} else if ($t_fabrica == 151) {
							$valor_unitario_sms = 0.11;
						}else if (in_array($t_fabrica, array(3,35))) {
							$valor_unitario_sms = 0.12;
						} else {
							$valor_unitario_sms = 0.15;
						}

						$valor_sms = $qtde_creditos_consumidos * $valor_unitario_sms;
						$aux_valor_sms = $aux_valor_sms + $valor_sms;
						$valor_sms = "R$ ".number_format($valor_sms, 2, ",", ".");
						?>
						<td id="tot_cred_<?=$t_sms?>"><?=$valor_sms;?></td>
					</tr>

				<?php
					unset($t_credito_resposta);
				} ?>
			</tbody>
			<tfoot>
				<tr>
					<th colspan="<?php echo ($sms_callcenter) ? 7 : 6; ?>" style='text-align: right !important;'> Total: </th>
					<?php if($login_fabrica == 1) { ?>
							<th></th>
					<?php } ?>
					<th><?php echo $valor_total_creditos_env; ?></th>
					<th></th>
					<th><?php echo $valor_total_creditos_resp; ?></th>
					<th>
						<?php
						$valor_sms = $aux_valor_sms;
						$valor_sms = "R$ ".number_format($valor_sms, 2, ",", ".");
						echo $valor_sms;
						?>
					</th>
				</tr>
			</tfoot>
		</table>
	</div>
	<?php
	}else{?>
	<div class="container">
		<div class="alert">
		    <h4>Nenhum resultado encontrado</h4>
		</div>
	</div>
	<?
	}
}

include "rodape.php";
?>
