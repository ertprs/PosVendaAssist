<?php
ini_set("display_errors", 1);

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$sqlIdioma = "SELECT UPPER(tbl_l10n.nome) as nome
		   FROM tbl_admin 
		   JOIN tbl_l10n USING(l10n)
		   JOIN tbl_hd_chamado USING(admin)
		   WHERE tbl_hd_chamado.hd_chamado = {$_REQUEST['hd_chamado']}";

		   $res = pg_query($con, $sqlIdioma);
		   $sistema_lingua = pg_fetch_result($res, 0, 'nome');
		   

/// new_status_gestao_author: gabriel.tinetti;
if ($_POST['statusPostType'] == "insert") {
	$dataInicio = date('Y-m-d H:i:s');
	$hdChamado = $_POST['hdChamado'];
    $controle_status_master = $_POST['etapa'];

    if (!empty($_POST['prazo'])) {
		$dataPrazoRaw = explode(" ", $_POST['prazo']);
		$dateRaw = explode("/", $dataPrazoRaw[0]);
		$dataPrazo = implode("-", array_reverse($dateRaw)) . " " . $dataPrazoRaw[1];

		if ($dataPrazo < $dataInicio) {
			echo "O prazo deve ser maior que a data atual";
			exit;
		}
	} else {
		$select_days = "SELECT status, dias FROM tbl_controle_status WHERE controle_status = {$controle_status_master};";
		$result = pg_query($con, $select_days);
		$dias = pg_fetch_result($result, 0, 'dias');
		$time = time();
		$dataPrazo = date('Y-m-d H:i:s', strtotime('+' . $dias . ' day', $time));
	}

	pg_query($con, "BEGIN");
	
	$etapa_status    = $_POST['etapa'];
	$sql_status_novo = "SELECT status FROM tbl_controle_status WHERE controle_status = $etapa_status";
	$res_status_novo = pg_query($con, $sql_status_novo);
	$etapa_status    = pg_fetch_result($res_status_novo, 0, 'status');
	
	switch ($etapa_status) {
		case 'Requisitos':
			$select_status = "SELECT status_chamado
							  FROM tbl_status_chamado
							  WHERE hd_chamado = {$hdChamado}
							  AND controle_status = {$controle_status_master};";

			$result = pg_query($con, $select_status);

			if (pg_num_rows($result) == 0) {
				$hdChamado = $_POST['hdChamado'];
				$admin = $_POST['admin'];
				$etapa = $_POST['etapa'];
				$status = $_POST['status'];

				$params = [$hdChamado, $admin, $etapa, $status, $dataInicio, $dataPrazo];
				$sql_insert_requisito = "INSERT INTO tbl_status_chamado (
											hd_chamado, 
											admin, 
											controle_status, 
											status, 
											data_inicio, 
											data_prazo
										) VALUES ($1, $2, $3, $4, $5, $6);";
				$rInsertRequisito = pg_query_params($con, $sql_insert_requisito, $params);

				$qAdminName = "SELECT nome_completo
							   FROM tbl_admin
							   WHERE admin = {$admin}";
				$rAdminName = pg_query($con, $qAdminName);
				$rAdminName = pg_fetch_result($rAdminName, 0, "nome_completo");

				$comment = "MENSAGEM AUTOMÁTICA - <b>{$rAdminName}</b>, favor levantar os requisitos referentes ao chamado.";

				$qAtendenteAnterior = "SELECT atendente
									   FROM tbl_hd_chamado
									   WHERE hd_chamado = {$hdChamado}
									   AND fabrica_responsavel = {$login_fabrica}";
				$rAtendenteAnterior = pg_query($con, $qAtendenteAnterior);
				$rAtendenteAnterior = pg_fetch_result($rAtendenteAnterior, 0, "atendente");

				$params = [$hdChamado, $comment, $rAtendenteAnterior, true];
				$qInsertInteracao = "INSERT INTO tbl_hd_chamado_item (
										hd_chamado,
										comentario,
										admin,
										interno
									) VALUES ($1, $2, $3, $4);";
				$rInsertInteracao = pg_query_params($con, $qInsertInteracao, $params);

				// HD-6381421
				$qTransfere = "UPDATE tbl_hd_chamado
							   SET atendente = {$admin},
							   	   login_admin = {$admin}
							   WHERE hd_chamado = {$hdChamado}";
				$rTransfere = pg_query($con, $qTransfere);

				if (strlen(pg_last_error()) > 0) {
					echo "Erro ao transferir atendimento";
					exit;
				}
			} else {
				echo "Este chamado já está nesta etapa!";
				exit;
			}			

			break;
		case 'Orcamento':
			$select_status = "SELECT ts.status_chamado,
								  ts.data_input,
								  tc.status,
								  tc.ordem
							  FROM tbl_status_chamado ts
							  JOIN tbl_controle_status tc ON tc.controle_status = ts.controle_status
							  WHERE ts.hd_chamado = {$hdChamado}
							  ORDER BY ts.data_input DESC;";
			$result = pg_query($con, $select_status);
			$status_chamado = pg_fetch_result($result, 0, 'status_chamado');
			$status_desc = pg_fetch_result($result, 0, 'status');
			$ordem = pg_fetch_result($result, 0, 'ordem');

			if ($ordem == 2 and $status_desc == 'Requisitos') {
				$select_controle = "SELECT controle_status
									FROM tbl_controle_status
									WHERE status = 'Requisitos'
									AND ordem = $ordem;";
				$result = pg_query($con, $select_controle);
				$controle_status = pg_fetch_result($result, 0, 'controle_status');

				$update_requisito = "UPDATE tbl_status_chamado ts
									 SET data_entrega = '{$dataInicio}'
									 WHERE controle_status = {$controle_status}
									 AND hd_chamado = {$hdChamado};";
				pg_query($con, $update_requisito);			
			}

			// insert orcamento status
			$select_controle_chamado = "SELECT controle_status, dias FROM tbl_controle_status 
	        WHERE status = 'Orcamento' AND ordem = 1;";
	        $result = pg_query($con, $select_controle_chamado);
	        $controle = pg_fetch_result($result, 0, 'controle_status');
	        $dias = pg_fetch_result($result, 0, 'dias');

	        $query = "SELECT * FROM tbl_status_chamado WHERE controle_status = {$controle} AND hd_chamado = {$hdChamado};";
	        $result = pg_query($con, $query);

	        if (pg_numrows($result) > 0) {
	        	echo "Este chamado já está nesta etapa!";
	        	exit;
	        } else {
	        	$time = time();
				$dataPrazo = date('Y-m-d H:i:s', strtotime('+' . $dias . ' day', $time));
				$admin = $_POST['admin'];
				$orcamento = $_POST['orcamento'];

				$params = [$hdChamado, $admin, $controle, $_POST['status'], $dataInicio];

				$insert_status_query = "INSERT INTO tbl_status_chamado (
											hd_chamado,
											admin,
											controle_status,
											status,
											data_inicio,
											data_prazo
										) VALUES ($1, $2, $3, $4, $5, fn_calcula_previsao_retorno('{$dataInicio}', '{$dias}', {$login_fabrica}));";
		        pg_query_params($con, $insert_status_query, $params);

		        $qAdminName = "SELECT ta.nome_completo,
		        					  ta.admin
		        			   FROM tbl_admin ta
		        			   JOIN tbl_hd_chamado thc ON thc.atendente = ta.admin
		        			   WHERE hd_chamado = {$hdChamado}";
		        $rAdminName = pg_query($con, $qAdminName);
		        $rAdminName = pg_fetch_result($rAdminName, 0, "nome_completo");
		        $rAdmin = pg_fetch_result($rAdminName, 0, "admin");

		        $qAdminTransfere = "SELECT nome_completo
		        					FROM tbl_admin
		        					WHERE admin = {$admin}";
		        $rAdminTransfere = pg_query($con, $qAdminTransfere);
		        $rAdminTransfere = pg_fetch_result($rAdminTransfere, 0, "nome_completo");

		        /*$qSuporte = "SELECT nome_completo,
		        					admin
		        			 FROM tbl_admin
		        			 WHERE fabrica = 10
		        			 AND nome_completo ILIKE 'Suporte'";
		        $rSuporte = pg_query($con, $qSuporte);
		        $rSuporteName = pg_fetch_result($rSuporte, 0, 'nome_completo');
		        $rSuporte = pg_fetch_result($rSuporte, 0, 'admin');

		        $comment = "MENSAGEM AUTOMÁTICA - O Analista <b>{$rAdminName}</b> definiu um total de <b>{$orcamento}</b> horas para o desenvolvimento. Chamado transferido para <b>{$rAdminTransfere}</b> realizar o orçamento.";
		        $params = [$hdChamado, $comment, $rSuporte, true, $status];
		        $qOrcamento = "INSERT INTO tbl_hd_chamado_item (
		        			       hd_chamado,
		        			       comentario,
		        			       admin,
		        			       interno,
		        			       status_item
		        			   ) VALUES ($1, $2, $3, $4, $5)";
		        $rOrcamento = pg_query_params($con, $qOrcamento, $params);*/

		        // HD-6381421
		        /*$qTransfere = "UPDATE tbl_hd_chamado
		        			   SET atendente = {$admin},
		        			   	   login_admin = {$admin}
		        			   WHERE hd_chamado = {$hdChamado}
		        			   AND fabrica_responsavel = {$login_fabrica}";
		        $rTransfere = pg_query($con, $qTransfere);

		        if (strlen(pg_last_error()) > 0) {
		        	echo "Falha ao transferir chamado";
		        	exit;
		        }*/
	        }

			break;
		case 'Analise':
			$query_select_status = "SELECT ts.status_chamado, ts.data_input, tc.status, tc.ordem FROM tbl_status_chamado ts 
			JOIN tbl_controle_status tc ON tc.controle_status = ts.controle_status 
			WHERE ts.hd_chamado = {$hdChamado} AND tc.status ILIKE 'Analise' ORDER BY ts.data_input DESC;";
			$result = pg_query($con, $query_select_status);

            $data_inicio = date('Y-m-d H:i:s');
            
            if (!empty($_POST['prazo'])) {
                $dataPrazoRaw = explode(" ", $_POST['prazo']);

                $dateRaw = explode("/", $dataPrazoRaw[0]);
                $data_prazo = implode("-", array_reverse($dateRaw)) . " " . $dataPrazoRaw[1];
                if (strtotime($data_prazo) < strtotime($data_inicio)) {
                    echo "O prazo deve ser maior que a data atual";
                    exit;
                }
            }
            
			$admin = $_POST['admin'];

			if (pg_num_rows($result) == 0) {
				$query_insert_status = "INSERT INTO tbl_status_chamado (hd_chamado, admin, controle_status, status, data_inicio, data_prazo) 
				VALUES ({$hdChamado}, {$admin}, 6, 'Analise', '{$data_inicio}', '{$data_prazo}');";
                pg_query($con, $query_insert_status);

                $query_select_status = "SELECT dias FROM tbl_controle_status WHERE controle_status = 7;";
                $result = pg_query($con, $query_select_status);
                $dias = pg_fetch_result($result, 0, 'dias');
                
			    $data_prazo = date('Y-m-d H:i:s', strtotime('+' . $dias . ' day', time()));

				$query_insert_status_two = "INSERT INTO tbl_status_chamado (hd_chamado, admin, controle_status, status, data_inicio, data_prazo) 
				VALUES ({$hdChamado}, {$admin}, 7, 'Analise', '{$data_inicio}', '{$data_prazo}');";
				pg_query($con, $query_insert_status_two);
			} else {
				$status_chamado = pg_fetch_result($result, 0, 'status_chamado');
				$status_desc = pg_fetch_result($result, 0, 'status');
				$ordem = pg_fetch_result($result, 0, 'ordem');
				
				$query_controle_status = "SELECT controle_status FROM tbl_controle_status WHERE status = 'Analise' AND ordem = 1";
				$result = pg_query($con, $query_controle_status);

				if (pg_numrows($result) > 0) {
					$controle_status = pg_fetch_result($result, 0, 'controle_status');
					$data_atual = date('Y-m-d H:i:s');

					$query_update_status = "UPDATE tbl_status_chamado SET data_entrega = '{$data_atual}' 
					WHERE controle_status = {$controle_status} AND status_chamado = {$status_chamado};";
					pg_query($con, $query_update_status);
					
					$ordem++;
					$query_controle_status = "SELECT controle_status, status FROM tbl_controle_status WHERE status = 'Analise' AND ordem = {$ordem};";
					$result = pg_query($con, $query_controle_status);

					///// insert analise 2
					if (pg_numrows($result) > 0) {                            
						$controle_status = pg_fetch_result($result, 0, 'controle_status');
						$status_desc = pg_fetch_result($result, 0, 'status');

						$params = [$hdChamado, $_POST['admin'], $controle_status, $status_desc, $data_atual, $data_prazo];
						$query_insert_status = "INSERT INTO tbl_status_chamado (hd_chamado, admin, controle_status, status, data_inicio, data_prazo)
						VALUES ($1, $2, $3, $4, $5, $6);";
						pg_query_params($con, $query_insert_status, $params);
					}

					////// insert previsao
					$ordem++;
					$query_controle_status = "SELECT controle_status, status, dias FROM tbl_controle_status WHERE status = 'Analise' AND ordem = {$ordem};";
					$result = pg_query($con, $query_controle_status);

					if (pg_numrows($result) > 0) {
						$controle_status = pg_fetch_result($result, 0, 'controle_status');
						$status_desc = pg_fetch_result($result, 0, 'status');
						$dias = pg_fetch_result($result, 0, 'dias');

						$query_select_admin = "SELECT COUNT(tbl_hd_chamado.hd_chamado) AS tantos, tbl_admin.admin FROM tbl_hd_chamado
					    JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado.atendente
					    WHERE tbl_hd_chamado.fabrica_responsavel = 10 AND tbl_admin.grupo_admin IN (1) AND tbl_admin.ativo IS TRUE and status not in ('Resolvido', 'Cancelado', 'Parado')
					    GROUP BY tbl_admin.admin ORDER BY tantos ASC;";
						$result = pg_query($con, $query_select_admin);
						$admin = pg_fetch_result($result, 0, 'admin');

						$params = [$hdChamado, $admin, $controle_status, $status_desc, $data_atual];
						$query_insert_status = "INSERT INTO tbl_status_chamado (hd_chamado, admin, controle_status, status, data_inicio, data_prazo)
						VALUES ($1, $2, $3, $4, $5, fn_calcula_previsao_retorno('{$data_atual}', {$dias}, {$login_fabrica}));";
						pg_query_params($con, $query_insert_status, $params);
					}
				}
			}
			
			break;
		case 'Aguard.Execucao':
			$query_verify_step = "SELECT status FROM tbl_status_chamado WHERE hd_chamado = {$hdChamado} ORDER BY data_input DESC LIMIT 1;";
			$result = pg_query($con, $query_verify_step);
			$admin = $_POST['admin'];

			if(pg_numrows($result) == 0 || pg_fetch_result($result, 0, 'status') != 'Aguard.Execucao') {
				$query_controle_status = "SELECT controle_status, status, dias FROM tbl_controle_status WHERE status = 'Aguard.Execucao' AND ordem = 1;";
				$result = pg_query($con, $query_controle_status);

				if (pg_numrows($result) > 0) {
					$controle_status = pg_fetch_result($result, 0, 'controle_status');
					$status_desc = pg_fetch_result($result, 0, 'status');
					$dias = pg_fetch_result($result, 0, 'dias');

					$data_inicio = date('Y-m-d H:i:s');
					if (!empty($_POST['prazo'])) {
						$dataPrazoRaw = explode(" ", $_POST['prazo']);

						$dateRaw = explode("/", $dataPrazoRaw[0]);
						$data_prazo = implode("-", array_reverse($dateRaw)) . " " . $dataPrazoRaw[1];
						if (strtotime($data_prazo) < strtotime($data_inicio)) {
							echo "O prazo deve ser maior que a data atual";
							exit;
						}
					}

					$params = [$hdChamado, $_POST['admin'], $controle_status, $status_desc, $data_inicio, $data_prazo];

					$query_insert_status = "INSERT INTO tbl_status_chamado (
												hd_chamado,
												admin,
												controle_status,
												status,
												data_inicio,
												data_prazo
											) VALUES ($1, $2, $3, $4, $5, $6);";
					pg_query_params($con, $query_insert_status, $params);

					$query_update_prazo = "UPDATE tbl_hd_chamado 
										   SET previsao_termino_interna = '{$data_prazo}'
										   WHERE hd_chamado = {$hdChamado};";
					pg_query($con, $query_update_prazo);

					/*$qAdminDev = "SELECT nome_completo
								  FROM tbl_admin
								  WHERE admin = {$admin}
								  AND fabrica = 10";
					$rAdminDev = pg_query($con, $qAdminDev);
					$rAdminDev = pg_fetch_result($rAdminDev, 0, 'nome_completo');

					$comment = "MENSAGEM AUTOMÁTICA - Chamado transferido para <b>{$rAdminDev}</b> dar início ao desenvolvimento.";
			        $params = [$hdChamado, $comment, $login_admin, true, $status];
			        $qDev = "INSERT INTO tbl_hd_chamado_item (
			        			       hd_chamado,
			        			       comentario,
			        			       admin,
			        			       interno,
			        			       status_item
			        			   ) VALUES ($1, $2, $3, $4, $5)";
			        $rDev = pg_query_params($con, $qDev, $params);*/

			        // HD-6381421
			       /* $qTransfere = "UPDATE tbl_hd_chamado
			        			   SET atendente = {$admin},
			        			   	   login_admin = {$admin}
			        			   WHERE hd_chamado = {$hdChamado}
			        			   AND fabrica_responsavel = {$login_fabrica}";
			        $rTransfere = pg_query($con, $qTransfere);

			        if (strlen(pg_last_error()) > 0) {
			        	echo "Falha ao transferir chamado";
			        	exit;
			        }*/
				}				
			} else {
				echo "Este chamado já está nesta etapa!";
				exit;
			}
			break;
		case 'ValidacaoHomologacao':
			$query_verify_step = "SELECT status FROM tbl_status_chamado WHERE hd_chamado = {$hdChamado} ORDER BY data_input DESC LIMIT 1;";
			$result = pg_query($con, $query_verify_step);

			if (pg_numrows($result) == 0 || pg_fetch_result($result, 0, 'status') != 'ValidacaoHomologacao') {
				$query_select_status = "SELECT ts.status_chamado, ts.data_input, tc.status, tc.ordem FROM tbl_status_chamado ts 
				JOIN tbl_controle_status tc ON tc.controle_status = ts.controle_status 
				WHERE ts.hd_chamado = {$hdChamado} ORDER BY ts.data_input DESC;";
				$result = pg_query($con, $query_select_status);

				if (pg_numrows($result) > 0) {
					$status_chamado = pg_fetch_result($result, 0, 'status_chamado');
					$status_desc = pg_fetch_result($result, 0, 'status');
                    $ordem = pg_fetch_result($result, 0, 'ordem');
                    
                    $data_inicio = date('Y-m-d H:i:s');
					
					$data_entrega = date('Y-m-d H:i:s');
					
					/// insert validacao homologacao
					$query_controle_status = "SELECT controle_status, status, dias FROM tbl_controle_status WHERE controle_status = {$controle_status_master} AND ordem = 1;";
					$result = pg_query($con, $query_controle_status);

					$controle_status = pg_fetch_result($result, 0, 'controle_status');
					$status_desc = pg_fetch_result($result, 0, 'status');
					$dias = pg_fetch_result($result, 0, 'dias');

					$data_prazo = date('Y-m-d H:i:s', strtotime('+' . $dias . ' day', time()));

					if (!empty($_POST['prazo'])) {
		                $dataPrazoRaw = explode(" ", $_POST['prazo']);

		                $dateRaw = explode("/", $dataPrazoRaw[0]);
		                $data_prazo = implode("-", array_reverse($dateRaw)) . " " . $dataPrazoRaw[1];
		                if (strtotime($data_prazo) < strtotime($data_inicio)) {
		                    echo "O prazo deve ser maior que a data atual";
		                    exit;
		                }
		            }

					$params = [$hdChamado, $_POST['admin'], $controle_status, $status_desc, $data_entrega, $data_prazo];
					$query_insert_status = "INSERT INTO tbl_status_chamado (hd_chamado, admin, controle_status, status, data_inicio, data_prazo) 
					VALUES ($1, $2, $3, $4, $5, $6);";
					pg_query_params($con, $query_insert_status, $params);
				}
			} else {
				echo "Este chamado já está nesta etapa!";
				exit;
			}

			break;


		case 'Validacao':
			$query_verify_step = "SELECT status FROM tbl_status_chamado WHERE hd_chamado = {$hdChamado} ORDER BY data_input DESC LIMIT 1;";
			$result = pg_query($con, $query_verify_step);

			if (pg_numrows($result) == 0 || pg_fetch_result($result, 0, 'status') != 'Validacao') {
				$query_select_status = "SELECT ts.status_chamado, ts.data_input, tc.status, tc.ordem FROM tbl_status_chamado ts 
				JOIN tbl_controle_status tc ON tc.controle_status = ts.controle_status 
				WHERE ts.hd_chamado = {$hdChamado} ORDER BY ts.data_input DESC;";
				$result = pg_query($con, $query_select_status);

				if (pg_numrows($result) > 0) {
					$status_chamado = pg_fetch_result($result, 0, 'status_chamado');
					$status_desc = pg_fetch_result($result, 0, 'status');
                    $ordem = pg_fetch_result($result, 0, 'ordem');
                    
                    $data_inicio = date('Y-m-d H:i:s');

					$data_entrega = date('Y-m-d H:i:s');
					
					/// insert validacao homologacao
					$query_controle_status = "SELECT controle_status, status, dias FROM tbl_controle_status WHERE controle_status = {$controle_status_master} AND ordem = 1;";
					$result = pg_query($con, $query_controle_status);

					$controle_status = pg_fetch_result($result, 0, 'controle_status');
					$status_desc = pg_fetch_result($result, 0, 'status');
					$dias = pg_fetch_result($result, 0, 'dias');

					$data_prazo = date('Y-m-d H:i:s', strtotime('+' . $dias . ' day', time()));

					if (!empty($_POST['prazo'])) {
		                $dataPrazoRaw = explode(" ", $_POST['prazo']);

		                $dateRaw = explode("/", $dataPrazoRaw[0]);
		                $data_prazo = implode("-", array_reverse($dateRaw)) . " " . $dataPrazoRaw[1];
		                if (strtotime($data_prazo) < strtotime($data_inicio)) {
		                    echo "O prazo deve ser maior que a data atual";
		                    exit;
		                }
		            }

					$params = [$hdChamado, $_POST['admin'], $controle_status, $status_desc, $data_entrega, $data_prazo];
					$query_insert_status = "INSERT INTO tbl_status_chamado (hd_chamado, admin, controle_status, status, data_inicio, data_prazo) 
					VALUES ($1, $2, $3, $4, $5, $6);";
					pg_query_params($con, $query_insert_status, $params);
			}
			} else {
				echo "Este chamado já está nesta etapa!";
				exit;
			}

			break;



		case 'Efetivacao':
			$query_select_status = "SELECT status FROM tbl_status_chamado WHERE hd_chamado = {$hdChamado} ORDER BY data_input DESC LIMIT 1;";
			$result = pg_query($con, $query_select_status);

			if (pg_numrows($result) == 0 || pg_fetch_result($result, 0, 'status') != 'Efetivacao') {
				$query_select_status = "SELECT ts.status_chamado, ts.data_input, tc.status, tc.ordem FROM tbl_status_chamado ts 
				JOIN tbl_controle_status tc ON tc.controle_status = ts.controle_status 
				WHERE ts.hd_chamado = {$hdChamado} ORDER BY ts.data_input DESC;";
				$result = pg_query($con, $query_select_status);

				if (pg_numrows($result) > 0) {
					$status_chamado = pg_fetch_result($result, 0, 'status_chamado');
					$status_desc = pg_fetch_result($result, 0, 'status');
					$ordem = pg_fetch_result($result, 0, 'ordem');

					if ($ordem == 1) {
						$data_entrega = date('Y-m-d H:i:s');

						/// update data entrega
						$query_update_status = "UPDATE tbl_status_chamado SET data_entrega = '{$data_entrega}' WHERE status_chamado = {$status_chamado};";
						pg_query($con, $query_update_status);

						/// insert efetivacao
						$query_controle_status = "SELECT controle_status, status, dias FROM tbl_controle_status WHERE controle_status = {$controle_status_master};";
						$result = pg_query($con, $query_controle_status);

						$controle_status = pg_fetch_result($result, 0, 'controle_status');
						$status_desc = pg_fetch_result($result, 0, 'status');
                        $dias = pg_fetch_result($result, 0, 'dias');
                        
                        $data_inicio = date('Y-m-d H:i:s');
						// $data_prazo = date('Y-m-d H:i:s', strtotime('+' . $dias . ' days', time()));

						if (!empty($_POST['prazo'])) {
			                $dataPrazoRaw = explode(" ", $_POST['prazo']);

			                $dateRaw = explode("/", $dataPrazoRaw[0]);
			                $data_prazo = implode("-", array_reverse($dateRaw)) . " " . $dataPrazoRaw[1];
			                if (strtotime($data_prazo) < strtotime($data_inicio)) {
			                    echo "O prazo deve ser maior que a data atual";
			                    exit;
			                }
			            }

						$params = [$hdChamado, $_POST['admin'], $controle_status, $status_desc, $data_entrega, $data_prazo];
						$query_insert_status = "INSERT INTO tbl_status_chamado (hd_chamado, admin, controle_status, status, data_inicio, data_prazo) 
						VALUES ($1, $2, $3, $4, $5, $6);";
						pg_query_params($con, $query_insert_status, $params);
					}
				}
			} else {
				echo "Este chamado já está nesta etapa!";
				exit;
			}

			break;
		case 'Correcao':
			$query_select_status = "SELECT status_chamado FROM tbl_status_chamado WHERE hd_chamado = {$hdChamado} AND status LIKE '%Correcao%';";
			$result = pg_query($con, $query_select_status);
			
			if (pg_num_rows($result) == 0) {
				$query_select_status = "SELECT controle_status, status, dias FROM tbl_controle_status WHERE status LIKE 'Correcao%' AND ordem = 1;";
				$result = pg_query($con, $query_select_status);
				
				if (pg_num_rows($result) > 0) {
					$controle_status = pg_fetch_result($result, 0, 'controle_status');
					$dias = pg_fetch_result($result, 0, 'dias');
					$admin = $_POST['admin'];
					$status_desc = pg_fetch_result($result, 0, 'status');
					$data_inicio = date('Y-m-d H:i:s');
					
					if (!empty($_POST['prazo'])) {
		                $dataPrazoRaw = explode(" ", $_POST['prazo']);

		                $dateRaw = explode("/", $dataPrazoRaw[0]);
		                $data_prazo = implode("-", array_reverse($dateRaw)) . " " . $dataPrazoRaw[1];
		                if (strtotime($data_prazo) < strtotime($data_inicio)) {
		                    echo "O prazo deve ser maior que a data atual";
		                    exit;
		                }
		            }

					$params = [$hdChamado, $admin, $controle_status, $status_desc, $data_inicio, $data_prazo];
					$query_insert_status = "INSERT INTO tbl_status_chamado (hd_chamado, admin, controle_status, status, data_inicio, data_prazo)
					VALUES ($1, $2, $3, $4, $5, $6);";
					pg_query_params($con, $query_insert_status, $params);
				}
			} else {
				echo "Este chamado já está nesta etapa!";
			}
			
			break;
	}

	if (strlen(pg_last_error()) > 0) {
		$response_ajax = "Erro no banco de dados!";
		// $response_ajax = pg_last_error();
		pg_query($con, "ROLLBACK");
	} else {
		$response_ajax = "ok";
		pg_query($con, "COMMIT");
	}

	echo $response_ajax;
	exit;
} elseif ($_POST['loadAdmins']) {
	$etapa = $_POST['etapa'];
	$hdChamado = $_POST['hdChamado'];

	if ($etapa == 1 or $etapa == 14) {
		$admin_grupo = "ta.grupo_admin IN (6, 4)";
	} elseif ($etapa == 2) {
		$admin_grupo = "ta.grupo_admin = 1";
	} elseif ($etapa == 3 or $etapa == 4) {
		$admin_grupo = "ta.grupo_admin = 11";
	} elseif ($etapa == 5 or $etapa == 6) {
		$admin_grupo = "ta.grupo_admin = 1";
	} elseif ($etapa == 8 or $etapa == 10) {
		$admin_grupo = "ta.grupo_admin = 4";
	} elseif ($etapa == 11) {
		$admin_grupo = "ta.grupo_admin IN (1,2)";
	} elseif ($etapa == 7) {
		$admin_grupo = "ta.grupo_admin IN (1,9)";
	} elseif ($etapa == 9) {
		$admin_grupo = "ta.grupo_admin = 6";
	}

	$query_list_admin = "SELECT nome_completo, admin FROM tbl_admin ta 
	JOIN tbl_grupo_admin tg ON ta.grupo_admin = tg.grupo_admin 
	WHERE " . $admin_grupo . " AND ta.ativo IS TRUE;";
	$result = pg_query($con, $query_list_admin);

	if (strlen(pg_last_error()) > 0) {
		$response = pg_last_error();
	} else {
		$response_admins = pg_fetch_all($result);
		$response_admins = array_map(function ($r) {
			$r['nome_completo'] = utf8_encode($r['nome_completo']);
			return $r; 
		}, $response_admins);
	}

	$query_dtentrega = "SELECT
							data_entrega
						FROM tbl_status_chamado
						WHERE hd_chamado = {$hdChamado}
						AND controle_status = {$etapa}";
	$result = pg_query($con, $query_dtentrega);

	if (strlen(pg_last_error()) > 0) {
		$response = pg_last_error();
	} else {
		$response_dtentrega = pg_fetch_result($result, 0, 'data_entrega');
	}

	$response['admins'] = $response_admins;
	$response['dt_entrega'] = $response_dtentrega;

	$response = json_encode($response);
	echo($response);
	exit;
} elseif ($_POST['statusPostType'] == "update") {
	$status_chamado = $_POST['statusChamado'];
	$new_admin = $_POST['admin'];
	$pendente = $_POST['pendente'];
	$entregue = $_POST['entregue'];

	if ($pendente == 'true' AND $entregue == 'true') {
		echo "Entregue e pendente selecionados, escolha um!";
		exit;
	}

	if (!empty($_POST['prazo'])) {
		$dataPrazoRaw = explode(" ", $_POST['prazo']);

		$dateRaw = explode("/", $dataPrazoRaw[0]);
		$new_prazo = implode("-", array_reverse($dateRaw)) . " " . $dataPrazoRaw[1];
		if (strtotime($new_prazo) < date('Y-m-d H:i:s')) {
			echo "O prazo deve ser maior que a data atual";
			exit;
		}
	} else {
		echo "Preencha as informações necessárias";
		exit;
	}

	$response = "";

	$query_select_status = "SELECT tsc.status_chamado,
								   tsc.admin,
								   tsc.data_entrega,
								   tsc.hd_chamado,
								   thc.fabrica
							FROM tbl_status_chamado tsc
							JOIN tbl_hd_chamado thc ON thc.hd_chamado = tsc.hd_chamado
							WHERE tsc.status_chamado = {$status_chamado};";
	$result = pg_query($con, $query_select_status);

	if (strlen(pg_last_error()) == 0) {
		if (pg_numrows($result) > 0) {
			$hdChamado = pg_fetch_result($result, 0, 'hd_chamado');
			$fabricaQ = pg_fetch_result($result, 0, 'fabrica');
			$entrega= pg_fetch_result($result, 0, 'data_entrega');
			
			if ($entregue == "true" OR $entregue === true) {
				$entrega = date('Y-m-d H:i:s');
			}

			if ($pendente == "true" OR $pendente === true) {
				$entrega = null;
			}

			$params = [$new_admin, $new_prazo, $entrega, $status_chamado];
			$query_update_status = "UPDATE 
										tbl_status_chamado 
									SET 
										admin = $1, 
										data_prazo = $2, 
										data_entrega = $3 
									WHERE status_chamado = $4;";
			pg_query_params($con, $query_update_status, $params);

			if (strlen(pg_last_error()) > 0) {
				$response = "Erro ao atualizar";
			} else {
				$response = "ok";
			}

			$queryUpdatePrev = "UPDATE tbl_hd_chamado
								SET previsao_termino_interna = '{$new_prazo}'
								WHERE hd_chamado = {$hdChamado}
								AND fabrica = {$fabricaQ}";
			$result = pg_query($con, $queryUpdatePrev);

			/*
			$qAdminAtual = "SELECT tbl_hd_chamado.atendente,
								   tbl_admin.nome_completo
							FROM tbl_hd_chamado
							JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado.atendente
							WHERE hd_chamado = {$hdChamado}
							AND fabrica_responsavel = {$login_fabrica}";
			$rAdmin = pg_query($con, $qAdminAtual);
			$rAdminAtual = pg_fetch_result($rAdmin, 0, "atendente");
			$rAdminAtualName = pg_fetch_result($rAdmin, 0, "nome_completo");

			if ($rAdminAtual != $new_admin) {
				$qSup = "SELECT admin
						 FROM tbl_admin
						 WHERE nome_completo ILIKE 'Suporte'
						 AND fabrica = 10";
				$rSup = pg_query($con, $qSup);
				$rSup = pg_fetch_result($rSup, 0, "admin");

				$qAdminDestino = "SELECT nome_completo
								  FROM tbl_admin
								  WHERE admin = {$new_admin}
								  AND fabrica = {$login_fabrica}";
				$rAdminDestino = pg_query($con, $qAdminDestino);
				$rAdminDestino = pg_fetch_result($rAdminDestino, 0, "nome_completo");

				$comment = "Chamado transferido de <b>$rAdminAtualName</b> para <b>$rAdminDestino</b>.";
				$params = [$hdChamado, $comment, $rSup, true];

				$qInteracao = "INSERT INTO tbl_hd_chamado_item (
							       hd_chamado,
							       comentario,
							       admin,
							       interno
							   ) VALUES ($1, $2, $3, $4);";
				$rInteracao = pg_query_params($con, $qInteracao, $params);

				// HD-6381421
				$qTransfere = "UPDATE tbl_hd_chamado
							   SET atendente = {$new_admin},
							   	   login_admin = {$new_admin}
							   WHERE hd_chamado = {$hdChamado}
							   AND fabrica_responsavel = {$login_fabrica}";
				$rTransfere = pg_query($con, $qTransfere);
			}*/

			if (strlen(pg_last_error()) > 0 OR pg_affected_rows($result) > 1) {
				$response = "Erro ao atualizar";
			} else {
				$response = "ok";
			}
		}
	}

	echo $response;
	exit;
}

// echo "<pre>";
// print_r($_REQUEST);
// print_r($_SERVER);
// echo "</pre>";
// echo "<pre>";
// print_r($_GET);
// echo "</pre>";

if ($login_fabrica<>10) {
	header ("Location: index.php");
}

if(isset($_POST['btn_orcamento'])) {
	$hd_chamado = $_POST['hd_chamado'];
	$pre_hora_analise = (int)$_POST['pre_hora_analise'];
	$pre_hora_desenvolvimento = (int)$_POST['pre_hora_desenvolvimento'];
	$pre_hora_teste = (int)$_POST['pre_hora_teste'];

	$pre_hora_analise = (empty($pre_hora_analise)) ? 0 : $pre_hora_analise; 
	$pre_hora_desenvolvimento = (empty($pre_hora_desenvolvimento)) ? 0 : $pre_hora_desenvolvimento; 
	$pre_hora_teste = (empty($pre_hora_teste)) ? 0 : $pre_hora_teste; 
	$json_horas = json_encode(['pre_hora_analise'=>$pre_hora_analise, 'pre_hora_desenvolvimento'=>$pre_hora_desenvolvimento, 'pre_hora_teste'=>$pre_hora_teste]);

	$sql = "UPDATE tbl_hd_chamado set campos_adicionais = replace(campos_adicionais::text,'null','{}')::jsonb || '$json_horas', atendente = (select admin from tbl_admin where ativo and grupo_admin = 11 and admin = 8527), status='Orçamento'  where hd_chamado = $hd_chamado ; 
			INSERT INTO tbl_hd_chamado_item(
				hd_chamado,
				status_item,
				admin,
				comentario,
				interno			
			)values(
				$hd_chamado,
				'Orçamento',
				$login_admin,
				'Segue horas para orçamento:<br>
					Hora Análise: $pre_hora_analise horas <br>
					Hora Desenvolvimento:: $pre_hora_desenvolvimento horas <br>
					Hora Teste: $pre_hora_teste horas <br>	',
				true
			)"; 
	$res = pg_query($con, $sql);
	
	header ("Location: $PHP_SELF?hd_chamado=$hd_chamado");
	
}

// dummy class para evitar erros no ambiente de testes.
if ($_serverEnvironment == 'production') {
    class Painel {
        function __construct($conn) {
            $this->conn = $conn;
        }
        public function setChamadoAtendente($a=null,$b=null,$c=null) {
            return true;
        }
    }

    $painel = new Painel($con);
}



include_once '../class/aws/s3_config.php';
include_once '../class/aws/aws_init.php';

define('S3TEST',  (DEV_ENV) ? 'testes/': '');

include_once '../class/aws/anexaS3.class.php';
include_once 'funcoes.php';

$s3requisito = new AmazonTC('requisitos', (int) $login_fabrica);

$msg_erro = '';
$msg_sucesso = 'Dados atualizados com sucesso';

//Atendentes que ao receber o chamado será sincronizado com o CRM Pipedrive
$atendenteSyncPipedrive = array(
	5186,
	8527
);
$syncPipedrive = false;

function syncPipedrive($hdChamado){

	$curl = curl_init();

	curl_setopt_array($curl, array(
	  CURLOPT_URL => "http://api2.telecontrol.com.br/callcenter/pipelineSync/hdChamado/".$hdChamado,
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => "",
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 30,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => "POST",
	  CURLOPT_HTTPHEADER => array(
	    "access-application-key: 701c59e0eb73d5ffe533183b253384bd52cd6973",
	    "access-env: PRODUCTION",
	    "cache-control: no-cache",
	    "content-type: application/json"
	  ),
	));

	$response = curl_exec($curl);
	$err = curl_error($curl);

	curl_close($curl);
}

function getMaiorInteracao($tabela,$hd_chamado,$rodada = false){

	$max = 0;
	$cond = null;

	if($rodada>0){
		$cond = ' AND rodada = '.$rodada;
	}

	$sql = 'SELECT MAX(interacao) AS maior_interacao FROM '.$tabela.' WHERE hd_chamado = '.$hd_chamado.' '.$cond.';';
	$res = pg_query($sql);
	$num = pg_num_rows($res);

	if($num){
		$max = pg_fetch_result($res,0,'maior_interacao');
	}

	if($max)
		return $max;
	else
		return 0;

}
function excluiAnexo($filename){
    return unlink($filename);
}

function excluiAnexoRequisitos($hd_chamado, $idx) {
	global $s3requisito;

	$anexo = $s3requisito->getObjectList("requisito_{$hd_chamado}_{$idx}.");

	if (count($anexo) > 0) {
		$anexo = basename($anexo[0]);

		$s3requisito->deleteObject($anexo);
	}
}

function getAnexosRequisitos($hd_chamado, $idx){
	global $s3requisito;

	$anexo = $s3requisito->getObjectList("requisito_{$hd_chamado}_{$idx}.");

	if (count($anexo) > 0) {
		$anexo = $anexo[0];

		$anexo = $s3requisito->getLink(basename($anexo));

		return $anexo;
	} else {
		return false;
	}

}
if(strlen($_GET['exclui_anexo']) > 0 ){
   $return = excluiAnexo($_GET['exclui_anexo']);
   $respJson = json_encode(array('success' => $return));
   echo $respJson;
   exit;
}

if($_GET['inicio_trabalho'] == 1){

	$hd_chamado = (int) $_GET['hd_chamado'];

	$res = pg_begin();

	//$sql = "UPDATE tbl_hd_chamado SET admin_desenvolvedor = $login_admin WHERE hd_chamado = $hd_chamado";
	//$res = pg_query($con,$sql);
	//EXECUTA A ROTINA PARA SETAR O TERMINO NO HD_CHAMADO_ITEM
	$sql = "UPDATE tbl_hd_chamado
			   SET previsao_termino_interna  = fn_prazo_termino(horas_analisadas::int4, CURRENT_TIMESTAMP)
			  FROM (
					SELECT hd_chamado, horas_analisadas
					  FROM tbl_backlog_item
					 WHERE hd_chamado                = $hd_chamado
					 ORDER BY backlog_item desc limit 1) BackLog
			 WHERE tbl_hd_chamado.hd_chamado = BackLog.hd_chamado
			   AND previsao_termino_interna IS NULL ;";

	$sql .= "
			 UPDATE tbl_hd_chamado_item
			    SET termino    = CURRENT_TIMESTAMP
			  WHERE hd_chamado_item IN(
					 SELECT hd_chamado_item
					   FROM tbl_hd_chamado_item
					  WHERE hd_chamado = (
							 SELECT hd_chamado
							   FROM tbl_hd_chamado_atendente
							  WHERE admin      = $login_admin
						AND data_termino IS NULL LIMIT 1)
						AND termino IS NULL
					  ORDER BY hd_chamado_item DESC
					  LIMIT 1
					)";
	$res = pg_query($con,$sql);

	$sql ="INSERT INTO tbl_hd_chamado_item (
				hd_chamado,
				comentario,
				admin,
				status_item,
				interno
			) VALUES (
				$hd_chamado,
				'Início do Trabalho',
				$login_admin,
				'$status',
				't'
			);";
	$res = pg_query($con,$sql);
	$msg_erro = pg_last_error($con);


	//muda o status para Aguard.Execução dos outros chamados que estao em execução e seta o atendente Fila Analistas ao inves de $login_admin

	//--======================================================================
	if (strlen($msg_erro) == 0) {

		$sql = "SELECT hd_chamado_atendente,
						hd_chamado
						FROM tbl_hd_chamado_atendente
						WHERE admin = $login_admin
						AND   data_termino IS NULL
						ORDER BY hd_chamado_atendente DESC LIMIT 1";
		$res = pg_query($con,$sql);
		$msg_erro = pg_last_error($con);
		if ( pg_num_rows($res) > 0) {
			$hd_chamado_atendente = pg_fetch_result($res, 0, 'hd_chamado_atendente');
			$hd_chamado_atual     = pg_fetch_result($res, 0, 'hd_chamado');
		}


		if($hd_chamado_atual <> $hd_chamado){ // se tiver interagindo em outro chamado eu insiro um novo

			//coloca termino no chamado que o Desenvolvedor estava trabalhando

			if(isset($hd_chamado_atual) && strlen($hd_chamado_atual) > 0){
				$sql = "UPDATE tbl_hd_chamado_atendente
						SET data_termino = CURRENT_TIMESTAMP
						WHERE hd_chamado = $hd_chamado_atual
						AND   admin = $login_admin
						AND   data_termino IS NULL";
				$res = pg_query($con,$sql);
				$msg_erro = pg_last_error($con);

				$sql =	"INSERT INTO tbl_hd_chamado_item (
								hd_chamado,
								comentario,
								interno,
								admin,
								data,
								termino,
								atendimento_telefone
							) VALUES (
								$hd_chamado_atual,
								'Término de Trabalho',
								't',
								$login_admin,
								CURRENT_TIMESTAMP,
								CURRENT_TIMESTAMP,
								'f'
							);";
				$res = pg_query($con,$sql);
				$msg_erro = pg_last_error($con);
			}
			$sql = "INSERT INTO tbl_hd_chamado_atendente(
											hd_chamado ,
											admin      ,
											data_inicio,
											hd_chamado_item
									)VALUES(
									$hd_chamado       ,
									$login_admin      ,
									CURRENT_TIMESTAMP ,
									(select hd_chamado_item from tbl_hd_chamado_item where hd_chamado = $hd_chamado order by 1 desc limit 1) 
									)";
			$res = pg_query($con,$sql);
			$msg_erro = pg_last_error($con);
		}

		$sql = "UPDATE tbl_hd_chamado SET status = 'Execução' WHERE hd_chamado = $hd_chamado AND status='Aguard.Execução'";
		$res = pg_query($con,$sql);
		$msg_erro = pg_last_error($con);
	}

	if(strlen($msg_erro) > 0){
		$res = pg_rollBack();
	}else{
		$res = pg_commit();
		if ($_serverEnvironment == 'production') {
			$painel->setChamadoAtendente($hd_chamado);
		}

		header ("Location: $PHP_SELF?hd_chamado=$hd_chamado");
	}

}

if($_GET['termino_trabalho'] == 1){

	$hd_chamado = $_GET['hd_chamado'];

	$res = pg_begin();

	$sql ="SELECT hd_chamado_item
			 FROM tbl_hd_chamado_item
			WHERE hd_chamado = (SELECT hd_chamado
                                  FROM tbl_hd_chamado_atendente
                                 WHERE admin        =  $login_admin
								   AND data_termino IS NULL
                                 LIMIT 1)
			  AND termino IS NULL
			ORDER BY hd_chamado_item DESC
			LIMIT 1 ;";

	$res = pg_query($con,$sql);
	$msg_erro .= pg_last_error($con);
	if( pg_num_rows($res)>0){

		$hd_chamado_item = pg_fetch_result($res, 0, 'hd_chamado_item');

		//Faz update do hd_chamado_item com o tempo de execução da ultima interação.
		$sql = "UPDATE tbl_hd_chamado_item
                   SET termino    = CURRENT_TIMESTAMP
                 WHERE hd_chamado_item IN(
						SELECT hd_chamado_item
						  FROM tbl_hd_chamado_item
						 WHERE hd_chamado = (
								SELECT hd_chamado
								  FROM tbl_hd_chamado_atendente
								 WHERE admin      = $login_admin
						   AND data_termino IS NULL LIMIT 1)
						   AND termino IS NULL
                 ORDER BY hd_chamado_item DESC LIMIT 1 );";

		$res = pg_query($con,$sql);
		$msg_erro .= pg_last_error($con);
	}

	$sql ="SELECT	hd_chamado_atendente  ,
					hd_chamado            ,
					data_termino
			FROM tbl_hd_chamado_atendente
			WHERE admin = $login_admin
			ORDER BY data_termino DESC
			LIMIT 1";
	$res = pg_query($con,$sql);

	if(pg_num_rows($res)>0){
		$xhd_chamado           = pg_fetch_result($res, 0, 'hd_chamado');
		$data_termino          = pg_fetch_result($res, 0, 'data_termino');
		$hd_chamado_atendente  = pg_fetch_result($res, 0, 'hd_chamado_atendente');
		if(strlen($data_termino)==0) {/*atendente estava trabalhando com algum chamado*/
			$sql =	"INSERT INTO tbl_hd_chamado_item (
							hd_chamado,
							comentario,
							interno,
							admin,
							data,
							termino,
							atendimento_telefone
						) VALUES (
							$hd_chamado,
							'Término de Trabalho',
							't',
							$login_admin,
							CURRENT_TIMESTAMP,
							CURRENT_TIMESTAMP,
							'f'
						);";
			$res = pg_query($con,$sql);
			$msg_erro = pg_last_error($con);
			if(strlen($msg_erro)==0){
				$sql = "UPDATE tbl_hd_chamado_atendente SET data_termino=CURRENT_TIMESTAMP
					WHERE hd_chamado_atendente = $hd_chamado_atendente
					AND admin = $login_admin
					and data_termino ISNULL";
				$res = pg_query($con,$sql);
				$msg_erro = pg_last_error($con);
			}
		}
	}

	if(strlen($msg_erro) > 0){
		$res = pg_rollBack();
	}else{
		$res = pg_commit();
		header ("Location: $PHP_SELF?hd_chamado=$hd_chamado");
	}
}

#Trecho alterado por Thiago Contardi HD: 304470
#Caso seja para adicionar algum requisito, chama este pedaço em ajax para inserir mais um requisito
if($_GET['adReq'] == 1){
	$numero = $_GET['numero'];
	?>
	<tr id="requisito_<?php echo $_GET['numero'];?>">
		<td><?php echo $numero;?></td>
		<td class="sub_label">
			<textarea name="requisitos[]" cols="50" rows="6"></textarea>
		</td>
		<td class="sub_label" align="center">
			<select name="analiseRequisitos[]">
				<option value="0">Não</option>
				<option value="1">Sim</option>
			</select>
		</td>
		<td class="sub_label" align="center">
			<select name="testeRequisitos[]">
				<option value="0">Não</option>
				<option value="1">Sim</option>
			</select>
		</td>
		<td valign="middle" align="center">
			<a href="javascript:void(0)" class="xAnalise" onclick="analise.delRequisito('<?php echo $_GET['numero'];?>')"> X </a>
		</td>
	</tr>
	<tr id="requisito_anexo<?php echo $i;?>">
		<td align='center' colspan='2' class='sub_label'>
			<ul id="container_file_anexo<?php echo $i;?>" style='list-style:none;text-align:left;'>
				<li class='titulo_cab' style='margin: 3px'> Arquivo: <input type='file' name='arquivo[<?=$numero?>]'></li>
			</ul>
		</td>
	</tr>
	<?php
	exit;

}elseif($_GET['addMelhorias'] == 1){
	//$numero = $_GET['numero'];
	$sql_analista = " SELECT admin, nome_completo, grupo_admin
	                                  FROM tbl_admin
	                                 WHERE tbl_admin.fabrica =  10
	                                   AND ativo             IS TRUE
	                                   --AND (grupo_admin       IN(1) OR admin = 5992)
	                                   AND grupo_admin       IN(1)
	                                   AND admin = $login_admin
	                                 ORDER BY tbl_admin.nome_completo;";
       $res_anlista= pg_query($con,$sql_analista);
       //echo nl2br($sql_analista);
       if (pg_num_rows($res_anlista) > 0) {
       	$readonly_melhorias = '';
       }else{
       	$readonly_melhorias = "readonly='true'";
       }
	?>
	<tr id="melhorias_<?php echo $_GET['numero'];?>">
		<td>
			<textarea name="melhorias[]" cols="50" rows="6"></textarea>
		</td>
		<td align="center">
			<input type="hidden" name="idMelhorias[]" value="<?php echo $idMelhorias;?>"/>
			<input id="horas_melhorias[]" class="caixa" type="text" value="" name="horas_melhorias[]" maxlength="8" size="2" <?php echo $readonly_melhorias; ?>>
horas
		</td>
	</tr>
	<?php
	exit;
#Caso seja para adicionar alguma análise, chama este pedaço em ajax para inserir mais uma análise
}elseif($_GET['adAnalise'] == 1){
	$numero = $_GET['numero'];
	?>
	<tr id="analise_<?php echo $_GET['numero'];?>">
		<td><?php echo $numero;?></td>
		<td>
			<textarea name="analises[]" cols="50" rows="6"></textarea>
		</td>
		<td align="center">
			<select name="desenvAnalise[]">
				<option value="0">Não</option>
				<option value="1">Sim</option>
			</select>
		</td>
		<td align="center">
			<select name="testeAnalise[]">
				<option value="0">Não</option>
				<option value="1">Sim</option>
			</select>
		</td>
		<td valign="middle" align="center">
			<a href="javascript:void(0)" class="xAnalise" onclick="analise.delAnalise('<?php echo $_GET['numero'];?>')"> X </a>
		</td>
	</tr>
	<?php
	exit;

#Caso seja para remover algum requisito, chama este pedaço em ajax para remover do banco esse requisito
}elseif($_GET['delReq'] == 1){
	$idReq = $_GET['idReq'];
	$sql = 'UPDATE tbl_hd_chamado_requisito SET excluido = TRUE, admin = '.$login_admin.' WHERE hd_chamado_requisito = '.$idReq;
	$res = pg_query($con,$sql);


	exit;
#Caso seja para remover alguma análise, chama este pedaço em ajax para remover do banco essa análise
}elseif($_GET['delAnalise'] == 1){
	$idAnalise = $_GET['idAnalise'];
	$sql = 'UPDATE tbl_hd_chamado_analise SET excluido = TRUE, admin = '.$login_admin.' WHERE hd_chamado_analise = '.$idAnalise;
	$res = pg_query($con,$sql);
	exit;
#Ao adicionar algula correção
}elseif($_GET['adCorrecao']==1){
	$rodadaCorrecao = $_GET['rodadaCorrecao'];
	$numero = $_GET['numero'];
	?>
	<tr id="correcao_<?php echo $rodadaCorrecao;?>_<?php echo $numero;?>">
		<td><?php echo $numero;?></td>
		<td>
			<textarea name="descricaoCorrecaos[]" cols="50" rows="6"></textarea>
		</td>
		<td>
			<textarea name="analiseCorrecaos[]" cols="50" rows="6"></textarea>
		</td>
		<td align="center">
			<select name="gravidadeCorrecaos[]">
				<option value="1">Leve</option>
				<option value="5">Normal</option>
				<option value="10">Grave</option>
			</select>
		</td>
		<td align="center">
			<select name="atendidoCorrecaos[]">
				<option value="NULL">Não aplicável</option>
				<option value="TRUE">Sim</option>
				<option value="FALSE">Não</option>
			</select>
		</td>
		<td valign="middle" align="center">
			<a href="javascript:void(0)" class="xAnalise" onclick="analise.delCorrecao(<?php echo $rodadaCorrecao;?>,<?php echo $numero;?>)"> X </a>
		</td>
	</tr>

	<?php
	exit;
}elseif($_GET['adRodada'] == 1){

	$rodadaCorrecao = $_GET['rodadaCorrecao'];
	$numero = ($_GET['numero']) ? $_GET['numero'] : 1;

	?>
	<form name='frm_correcao_<?php echo $rodadaCorrecao;?>' action="<? echo $_SERVER['PHP_SELF'];?>?hd_chamado=<?php echo $_GET['hd_chamado'];?>" method='post'>
		<table border='0' cellpadding='2' class="table_analise" id="tbl_correcao_<?php echo $rodadaCorrecao;?>">
			<tr class="titulo_cab">
				<th align="left" colspan="5">
					Rodada <?php echo $rodadaCorrecao;?>
				</th>
			</tr>
			<tr>
				<th>&nbsp;</th>
				<th align="left" width="35%">
					Descrição
				</th>
				<th align="left" width="35%">
					Análise
				</th>
				<th align="left">
					Gravidade
				</th>
				<th align="left">
					Atendido
				</th>
			</tr>
			<tr id="correcao_<?php echo $rodadaCorrecao;?>_<?php echo $numero;?>">
				<td><?php echo $numero;?></td>
				<td>
					<textarea name="descricaoCorrecaos[]" cols="50" rows="6"></textarea>
				</td>
				<td>
					<textarea name="analiseCorrecaos[]" cols="50" rows="6"></textarea>
				</td>
				<td align="center">
					<select name="gravidadeCorrecaos[]">
						<option value="1">Leve</option>
						<option value="5">Normal</option>
						<option value="10">Grave</option>
					</select>
				</td>
				<td align="center">
					<select name="atendidoCorrecaos[]">
						<option value="NULL">Não aplicável</option>
						<option value="TRUE">Sim</option>
						<option value="FALSE">Não</option>
					</select>
				</td>
				<td valign="middle" align="center">
					<a href="javascript:void(0)" class="xAnalise" onclick="analise.delCorrecao(<?php echo $rodadaCorrecao;?>,<?php echo $numero;?>)"> X </a>
				</td>
			</tr>

		</table>

		<p class="finalizarCorrecoes">
			<label>
				<input type="checkbox" name="rodadaFinalizada" value="TRUE" />
				Finalizar Rodada de Correções
			</label>
		</p>

		<div class="salvarAnalise">
			<input type="hidden" name="aba_post" value="correcao1" />
			<input type="hidden" name="rodada" value="<?php echo $rodadaCorrecao;?>" />
			<input type="hidden" name="chamado" value="<?php echo $_GET['hd_chamado'];?>" />
			<input type="submit" name="salvar" value="Salvar" />
		</div>

		</form>

	<input type="hidden" id="numeroCorrecao_<?php echo $rodadaCorrecao;?>" value="<?php echo ++$numero;?>" />
	[ <a href="javascript:void(0)" onclick="analise.addCorrecao(<?php echo $rodadaCorrecao;?>)">Adicionar Correção</a> ]
	<?php
	exit;

}elseif($_GET['delCorrecao'] == 1){

	$idCorrecao = $_GET['idCorrecao'];
	$sql = 'DELETE FROM tbl_hd_chamado_correcao WHERE hd_chamado_correcao = '.$idCorrecao;
	$res = pg_query($con,$sql);
	exit;

}else if ($_POST['atualizaHoras'] == 'true') {
	/**
	*Atualiza chamado com a previsao_termino_interna
	 */
	$hd_chamado = $_POST['hd_chamado'];
	$motivo     = $_POST['motivo'];
	$tipo_admin = $_POST['tipo_admin'];
	$motivo     = utf8_decode($motivo);
	$horas_internas = !empty($_POST['horas_internas']) ? $_POST['horas_internas'] : 1;
	$motivo     = str_replace("'","",$motivo);


	$hrs_internas = ($horas_internas > 0) ? $horas_internas : "horas_desenvolvimento::integer";
	if (in_array($grupo_admin,array(1,2,7,9))) {
		if(strlen($msg_erro) == 0 ){

			$sql = "UPDATE tbl_hd_chamado
					   SET previsao_termino_interna  = fn_prazo_termino(
						   CASE WHEN previsao_termino_interna IS NOT NULL and horas_desenvolvimento > 0
								THEN TRUNC(CASE WHEN horas_desenvolvimento/2 = 0 THEN 1 ELSE horas_desenvolvimento/2 END )::integer
					            ELSE $hrs_internas
					       END,
					       CASE WHEN previsao_termino_interna IS NOT NULL
								 AND CURRENT_TIMESTAMP         > previsao_termino_interna
						        THEN CURRENT_TIMESTAMP
								WHEN previsao_termino_interna IS NOT NULL
								THEN previsao_termino_interna
					            ELSE CURRENT_TIMESTAMP
					       END)
					 WHERE tbl_hd_chamado.hd_chamado = $hd_chamado
				 RETURNING TO_CHAR(previsao_termino_interna, 'DD/MM/YYYY HH24:MI:SS') ";
			$res = pg_query($con,$sql);

			$msg_erro .= pg_last_error($con);
			if ($msg_erro) die ($msg_erro);

			if(pg_affected_rows($res) !== 1) {
				die('Previsão NÃO atualizada.');
			}

			$previsao = pg_fetch_result($res,0,0);
		    $comentario = "'Previsão Cadastrada para $previsao',";
			if(strlen($motivo) > 10)  {
				$comentario = "'Previsão Cadastrada para $previsao<br>Motivo de adiamento: $motivo',";
			}

			$sql  = "
				INSERT INTO tbl_hd_chamado_item(
						hd_chamado,
						comentario,
						admin,
						status_item,
						interno
					) VALUES (
						$hd_chamado,
						$comentario
						$login_admin,
						'Previsao Cadastrada',
						true
					);";
			$res = pg_query($con,$sql);
			echo $previsao;
		}
	}
	die;
} else if (isset($_POST["previsaoTerminoInterna"]) && $_POST["previsaoTerminoInterna"] ==1) {
	$hd_chamado = $_POST["hd_chamado"];
	$sql ="SELECT previsao_termino_interna from tbl_hd_chamado WHERE hd_chamado = $hd_chamado";
	$res = pg_query($con, $sql);
	if (pg_num_rows($res)>0) {
		echo pg_fetch_result($res,0,0);
	}
	exit;
}elseif(isset($_POST['atualiza_interna'])){
	$hd_chamado = $_POST['hd'];
	$previsao_interna = $_POST['data'];

	if(!empty($hd_chamado) and !empty($previsao_interna)) {
		$sql = "BEGIN; UPDATE tbl_hd_chamado set previsao_termino_interna = '$previsao_interna' where hd_chamado = $hd_chamado; insert into tbl_hd_chamado_item(hd_chamado, interno, admin, comentario) values($hd_chamado, true, $login_admin, 'Previsão de término interna cadastrada: $previsao_interna') ;";
		$res = pg_query($con,$sql);
		if(empty(pg_last_error())) {
			pg_query($con,'commit');
			echo "Previsão Cadastrada";
		}else{
			pg_query($con,'rollback');
			echo "Erro ao cadastrar previsão";
		}

	}	
	exit;
}

#Ao enviar o formulário de Requisitos, faz as validações
if($_POST['aba_post'] == 'req1'){

	$totalRequisitos = count($_POST['requisitos']);
	#Maior interação dos requisitos
	$maiorInteracao = getMaiorInteracao('tbl_hd_chamado_requisito',$hd_chamado);
	$maiorInteracao++;
    $logicToIndexImage = $maiorInteracao; //sorry for this solution
	$res = pg_begin();

	for($i=0;$i<$totalRequisitos;$i++){

		$idRequisito = isset($_POST['idRequisitos'][$i]) ? $_POST['idRequisitos'][$i] : null;
		$requisito = trim($_POST['requisitos'][$i]);


		$analiseRequisitos = ($_POST['analiseRequisitos'][$i]==1) ? 'TRUE' : 'FALSE';
		$testeRequisitos = ($_POST['testeRequisitos'][$i]==1) ? 'TRUE' : 'FALSE';

		if($idRequisito>0){

			$sql = 'SELECT analise,teste,admin_analise,admin_teste
					  FROM tbl_hd_chamado_requisito
					 WHERE hd_chamado_requisito = '.$idRequisito;
			$res = pg_query($con,$sql);
			$num = pg_num_rows($res);

			if($num){

				$analise = pg_fetch_result($res,0,'analise');
				$teste = pg_fetch_result($res,0,'teste');
				$admin_analise = pg_fetch_result($res,0,'admin_analise');
				$admin_teste = pg_fetch_result($res,0,'admin_teste');

				$adminAnalise = ($analiseRequisitos == 'TRUE' && $analise == 'f') ? $login_admin : $admin_analise;
				$adminTeste = ($testeRequisitos == 'TRUE' && $teste == 'f') ? $login_admin : $admin_teste;

				if(!$adminAnalise)
					$adminAnalise = 'NULL';

				if(!$adminTeste)
					$adminTeste = 'NULL';

				$sql = 'UPDATE  tbl_hd_chamado_requisito
						   SET  analise = '.$analiseRequisitos.',
								admin_analise = '.$adminAnalise.',
								teste = '.$testeRequisitos.',
								admin_teste = '.$adminTeste.'
						 WHERE  hd_chamado_requisito = '.$idRequisito.';';
				$res = pg_query($con,$sql);
				$msg_erro .= pg_last_error($con);

			}

		}elseif($requisito){
			$adminAnalise = ($analiseRequisitos == 'TRUE') ? $login_admin : 'NULL';
			$adminTeste = ($testeRequisitos == 'TRUE') ? $login_admin : 'NULL';

			$date = date('Y-m-d H:i:s');

			$select_status = "SELECT ts.status_chamado, ts.data_input, tc.status, tc.ordem 
			FROM tbl_status_chamado ts 
			JOIN tbl_controle_status tc ON tc.controle_status = ts.controle_status 
			WHERE ts.hd_chamado = {$hd_chamado} ORDER BY ts.data_input DESC;";
			$result = pg_query($con, $select_status);
			$status_chamado = pg_fetch_result($result, 0, 'status_chamado');
			$status_desc = pg_fetch_result($result, 0, 'status');
			$ordem = pg_fetch_result($result, 0, 'ordem');


			if ($ordem == 1 and $status_desc == 'Requisitos') {
				$select_controle = "SELECT controle_status FROM tbl_controle_status WHERE status = 'Requisitos' AND ordem = $ordem;";
				$result = pg_query($con, $select_controle);
				$controle_status = pg_fetch_result($result, 0, 'controle_status');

				$update_requisito = "UPDATE tbl_status_chamado ts 
									 SET data_entrega = '{$date}'
									 WHERE ts.status = 'Requisitos'
									 AND controle_status = {$controle_status}
									 AND ts.hd_chamado = {$hd_chamado};";
				pg_query($con, $update_requisito);
				$msg_erro .= pg_last_error();
			}

			// if ($rows != 0) {
			// 	$update_status_query = "UPDATE tbl_status_chamado SET data_entrega = '{$date}' WHERE hd_chamado = {$hd_chamado};";
			// 	pg_query($con, $update_status_query);
			// }

			$sql =	'INSERT INTO tbl_hd_chamado_requisito (
							hd_chamado,
							requisito,
							analise,
							admin_analise,
							teste,
							admin_teste,
							interacao,
							admin
						) VALUES (
							'.$_POST['chamado'].',
							E\''.$requisito.'\',
							'.$analiseRequisitos.',
							'.$adminAnalise.',
							'.$testeRequisitos.',
							'.$adminTeste.',
							'.$maiorInteracao.',
							'.$login_admin.'
						);';

			$res = pg_query($con, $sql);
			$msg_erro .= pg_last_error($con);

			$arquivo_post = $_FILES["arquivo"];
        	        $arquivo = array();

	                foreach($arquivo_post as $key => $value) {
        	                $arquivo[$key] = $value[$maiorInteracao];
	                }

			if (!empty($arquivo) and $arquivo["size"] > 0) {
				$ext = preg_replace("/.+\./", "", $arquivo["name"]);

				$s3requisito->upload("requisito_{$_POST['chamado']}_{$maiorInteracao}.{$ext}", $arquivo);
			}

			$maiorInteracao++;
		}


	}

	if(empty($msg_erro)){
		if($sistema_lingua == 'ES'){
			$comentario = "Por favor, comprobar los requisitos planteados en la llamada, si está de acuerdo por favor pase en un máximo de 10 días laborables si usted no está de acuerdo, por favor no aprobar y poner a una interacción en la llamada.";
			$comentario .= "<p /> <strong style=\"color: #ff0000; font-size: 14px;\">Atención, si la llamada no se aprueba dentro de los 10 días que será cancelada automáticamente por el sistema de Help Desk</strong>";
		}else{
			$comentario = "Favor verificar os REQUISITOS levantados no chamado, caso esteja de acordo favor aprovar em no máximo 10 dias úteis, caso não esteja de acordo, favor não aprovar e fazer uma interação no chamado.";

			$comentario .= "<p /> <strong style=\"color: #ff0000; font-size: 14px;\">Atenção, caso o chamado não seja aprovado dentro de 10 dias úteis ele será cancelado automaticamente pelo sistema Help-Desk</strong>";
		}

		$sql = "INSERT INTO tbl_hd_chamado_item(
						hd_chamado,
						comentario,
						admin,
						status_item
					) VALUES (
						".$_POST['chamado'].",
						'$comentario',
						435,
						'Ap.Requisitos'
					)";
		$res = pg_query($con,$sql);
		$msg_erro .= pg_last_error($con);

		if(empty($msg_erro)){
			$sql = "UPDATE tbl_hd_chamado SET status = 'Requisitos',login_admin = $login_admin WHERE hd_chamado = ".$_POST['chamado'];
			$res = pg_query($con,$sql);
			$msg_erro .= pg_last_error($con);
		}
	
	}

	if(empty($msg_erro)){

		$sql = "SELECT tbl_admin.email FROM tbl_hd_chamado JOIN tbl_admin ON tbl_hd_chamado.admin = tbl_admin.admin WHERE hd_chamado =". $_POST['chamado'];
		$res = pg_query($con,$sql);

		if(pg_num_rows($res) > 0){
			$destinatario = pg_result($res,0,'email');
			if($sistema_lingua == 'ES'){
				$assunto = "APROBAR REQUISITOS LLAMADA: ".$_POST['chamado'];

				$mensagem  = "Estimado Cliente.<br><br>";
				$mensagem .= "Verifique los REQUISITOS planteados en el llamado ".$_POST['chamado'].", si está de acuerdo, apruebe.<br><br>";
				$mensagem .= "Si no está de acuerdo con los requisitos planteados, no apruebe la llamada y realice una nueva interacción que indique qué se debe cambiar en los requisitos.<br><br>";
			}else{

				$assunto = "APROVAR REQUISITOS CHAMADO: ".$_POST['chamado'];

				$mensagem = "Prezado Cliente.<br><br>";
				$mensagem .= "Favor verificar os REQUISITOS levantados no chamado ".$_POST['chamado'].", caso esteja de acordo favor aprovar.<br><br>";
				$mensagem .= "Caso não esteja de acordo com os requisitos levantados, não aprovar o chamado e fazer uma nova interação informando o que deverá ser alterado nos requisitos.<br><br>";
			}
			// To send HTML mail, the Content-type header must be set
			$headers  = 'MIME-Version: 1.0' . "\r\n";
			$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

			// Additional headers
			$headers .= "To: $destinatario" . "\r\n";
			$headers .= 'From: helpdesk@telecontrol.com.br' . "\r\n";
			$mailer->sendMail($destinatario, $assunto, $mensagem, 'helpdesk@telecontrol.com.br');
		}


	}

	if(strlen($msg_erro) > 0){
		$res = pg_rollBack();
	}else{
		if ($_serverEnvironment == 'production') {
			// #######################################
			// ---------------------------------------
			$painel->setChamadoAtendente($_POST['chamado']);
			// ---------------------------------------
			// #######################################
		}

		$res = pg_commit();
		header ("Location: $PHP_SELF?hd_chamado=$hd_chamado");
	}

}


#Ao enviar o formulário de Requisitos, faz as validações
if($_POST['aba_post'] == 'requisitoTeste1'){

	$totalRequisitos = count($_POST['requisitos']);

	$res = pg_begin($con);

	for($i=0;$i<$totalRequisitos;$i++){

		$idRequisito		= isset($_POST['idRequisitos'][$i]) ? $_POST['idRequisitos'][$i] : null;
		$requisito			= $_POST['requisitos'][$i];
		$requisito			= utf8_decode($requisito);
		$analiseRequisitos	= ($_POST['analiseRequisitos'][$i]==1) ? 'TRUE' : 'FALSE';
		$testeRequisitos	= ($_POST['testeRequisitos'][$i]==1) ? 'TRUE' : 'FALSE';

		if($idRequisito>0){

			$sql = 'SELECT analise,teste,admin_analise,admin_teste
					  FROM tbl_hd_chamado_requisito
					 WHERE hd_chamado_requisito = '.$idRequisito;
			$res = pg_query($con,$sql);
			$num = pg_num_rows($res);

			if($num){

				$analise		= pg_fetch_result($res,0,'analise');
				$teste			= pg_fetch_result($res,0,'teste');
				$admin_analise	= pg_fetch_result($res,0,'admin_analise');
				$admin_teste	= pg_fetch_result($res,0,'admin_teste');

				$adminAnalise	= ($analiseRequisitos == 'TRUE' && $analise == 'f') ? $login_admin : $admin_analise;
				$adminTeste		= ($testeRequisitos   == 'TRUE' && $teste   == 'f') ? $login_admin : $admin_teste;

				if(!$adminAnalise)
					$adminAnalise = 'NULL';

				if(!$adminTeste)
					$adminTeste = 'NULL';

				$sql = 'UPDATE  tbl_hd_chamado_requisito
						   SET  analise = '.$analiseRequisitos.',
								admin_analise = '.$adminAnalise.',
								teste = '.$testeRequisitos.',
								admin_teste = '.$adminTeste.'
						 WHERE  hd_chamado_requisito = '.$idRequisito.';';
				$res = pg_query($con,$sql);
				$msg_erro .= pg_last_error($con);

			}

		}

	}

	if(strlen($msg_erro) > 0){
		echo $msg_erro;
		$res = pg_rollBack();
	}else{
		echo $msg_sucesso;
		$res = pg_commit();
	}
	exit;

}

#Ao enviar o formulário de Análise, faz as validações
if($_POST['aba_post'] == 'analise1'){

	$totalAnalises = count($_POST['analises']);
	#Maior interação das Análise
	$maiorInteracao = getMaiorInteracao('tbl_hd_chamado_analise',$hd_chamado);
	$maiorInteracao++;

	$res = pg_begin($con);

	$query_verify_step = "SELECT status_chamado
						  FROM tbl_status_chamado ts
						  JOIN tbl_controle_status tc ON tc.controle_status = ts.controle_status
						  WHERE ts.hd_chamado = {$hd_chamado}
						  AND tc.status = 'Analise'
						  ORDER BY ts.data_input DESC;";
	$result = pg_query($con, $query_verify_step);

	if (pg_numrows($result) > 0) {
		$status_chamado = pg_fetch_result($result, 0, 'status_chamado');
		$data_now = date('Y-m-d H:i:s');

		$query_update_status = "UPDATE tbl_status_chamado
								SET data_entrega = '{$data_now}'
								WHERE status_chamado = {$status_chamado};";
		pg_query($con, $query_update_status);


		$qTransfere = "UPDATE tbl_hd_chamado
					   SET atedente = (select admin from tbl_admin , tbl_fabrica where tbl_fabrica.fabrica = tbl_hd_chamado.fabrica and grupo_admin = 6 and tbl_fabrica.parametros_adicionais::jsonb->>'equipe' = tbl_admin.parametros_adicionais::jsonb->>'equipe' order by random() limit 1)  
					   WHERE hd_chamado = {$hd_chamado}
					   AND fabrica_responsavel = {$login_fabrica}";
		$rTransfere = pg_query($con, $qTransfere);
	}

	for($i=0;$i<$totalAnalises;$i++){

		$idAnalise     = isset($_POST['idAnalise'][$i])  ?$_POST['idAnalise'][$i] : null;
		$analise       = trim($_POST['analises'][$i]);
		$desenvAnalise = ($_POST['desenvAnalise'][$i]==1)?'TRUE' : 'FALSE';
		$testeAnalise  = ($_POST['testeAnalise'][$i]==1) ?'TRUE' : 'FALSE';

		if($idAnalise>0){

			$sql = 'SELECT analise,desenvolvimento,admin_desenvolvimento,teste,admin_teste
					  FROM tbl_hd_chamado_analise
					 WHERE hd_chamado_analise = '.$idAnalise;
			$res = pg_query($con,$sql);
			$num = pg_num_rows($res);

			if($num){

				$desenvolvimento = pg_fetch_result($res,0,'desenvolvimento');
				$admin_desenvolvimento = pg_fetch_result($res,0,'admin_desenvolvimento');
				$teste = pg_fetch_result($res,0,'teste');
				$admin_teste = pg_fetch_result($res,0,'admin_teste');

				$adminDesenvolvimento = ($desenvAnalise == 'TRUE' && $desenvolvimento == 'f') ? $login_admin : $admin_desenvolvimento;
				$adminTeste = ($testeAnalise == 'TRUE' && $teste == 'f') ? $login_admin : $admin_teste;

				if(!$adminDesenvolvimento)
					$adminDesenvolvimento = 'NULL';

				if(!$adminTeste)
					$adminTeste = 'NULL';

				$sql = 'UPDATE  tbl_hd_chamado_analise
						   SET  desenvolvimento = '.$desenvAnalise.',
								admin_desenvolvimento = '.$adminDesenvolvimento.',
								teste = '.$testeAnalise.',
								admin_teste = '.$adminTeste.'
						 WHERE  hd_chamado_analise = '.$idAnalise.';';
				$res = pg_query($con,$sql);
				$msg_erro .= pg_last_error($con);

			}

		}elseif($analise){

			$adminDesenvolvimento = ($desenvAnalise == 'TRUE') ? $login_admin : 'NULL';
			$adminTeste = ($testeAnalise == 'TRUE') ? $login_admin : 'NULL';

			$analise = addslashes($analise);

			$sql =	'INSERT INTO tbl_hd_chamado_analise (
							hd_chamado,
							analise,
							desenvolvimento,
							admin_desenvolvimento,
							teste,
							admin_teste,
							interacao,
							admin
						) VALUES (
							'.$_POST['chamado'].',
							E\''.$analise.'\',
							'.$desenvAnalise.',
							'.$adminDesenvolvimento.',
							'.$testeAnalise.',
							'.$adminTeste.',
							'.$maiorInteracao.',
							'.$login_admin.'
						);';
			$res = pg_query($con,$sql);
			$msg_erro .= pg_last_error($con);

			
			$maiorInteracao++;
		}

	}

	
	#Alterações para a tabela de chamado
	$plano_teste = $_POST['plano_teste'];
	$analiseTexto = $_POST['analiseTexto'];

	$sql = 'UPDATE  tbl_hd_chamado
			   SET  plano_teste = E\''.$plano_teste.'\',
					analise = E\''.$analiseTexto.'\',
					admin_plano_teste = E\''.$login_admin.'\'
			 WHERE  hd_chamado = '.$_POST['chamado'] ;
	$res = pg_query($con,$sql);
	$msg_erro .= pg_last_error($con);

	$sql = " INSERT INTO tbl_hd_chamado_item (
		                                  hd_chamado,
		                                  comentario,
						  admin,
						  interno
		                                 ) VALUES (
		                                  ".$_POST['chamado'].",
		                                 'Chamado analisado. Distribuir para desenvolvimento',
						 ".$login_admin.",
						 true
		                                )";
	$res = pg_query($con,$sql);
	$msg_erro .= pg_last_error($con);

	if(strlen($msg_erro) > 0){
		$res = pg_rollBack();
	}else{
		if ($_serverEnvironment == 'production') {
			// #######################################
			// ---------------------------------------
			$painel->setChamadoAtendente($_POST['chamado']);
			// ---------------------------------------
			// #######################################
		}

		$res = pg_commit();
		header ("Location: $PHP_SELF?hd_chamado=$hd_chamado");
	}
	exit;

}

if($_POST['aba_post'] == 'melhorias1'){

	$totalMelhorias = count($_POST['melhorias']);

	$res = pg_query($con, "BEGIN");

	for($i=0;$i<$totalMelhorias;$i++){

		$idMelhorias	 = isset($_POST['idMelhorias'][$i])  ?$_POST['idMelhorias'][$i] : null;
		$melhorias 		 = trim($_POST['melhorias'][$i]);
		$melhorias= utf8_decode($melhorias);
		$horas_melhorias = trim($_POST['horas_melhorias'][$i]);

		if ($horas_melhorias > 0) {
			$qtde_horas_melhorias = $horas_melhorias;
		}else{
			$qtde_horas_melhorias = "NULL";
		}

		if($idMelhorias>0){

			$sql = "SELECT qtde_horas,hd_chamado
					  FROM tbl_hd_chamado_melhoria
					 WHERE hd_chamado_melhoria = {$idMelhorias};";

			$res = pg_query($con,$sql);
			$num = pg_num_rows($res);

			if($num){

				$hd_chamado_melhorias = pg_fetch_result($res,0,'hd_chamado');

				if( count($qtde_horas_melhorias) > 0 ){

					$sql = 'UPDATE  tbl_hd_chamado_melhoria
							   SET  qtde_horas = '.$qtde_horas_melhorias.'
							 WHERE  hd_chamado_melhoria = '.$idMelhorias.';';
					$res = pg_query($con,$sql);
					$msg_erro .= pg_last_error($con);

					$sql_bk = "SELECT horas_analisadas,backlog_item
									FROM tbl_backlog_item
									WHERE hd_chamado = $hd_chamado_melhorias";
					$res_bk = pg_query($con,$sql_bk);

					$horas_bk = pg_fetch_result($res_bk, 0, 'horas_analisadas');
					$backlog_item = pg_fetch_result($res_bk, 0, 'backlog_item');

					$horas_bk = $horas_bk + $qtde_horas_melhorias;

					$sql_bk_up = 'UPDATE  tbl_backlog_item
							   		SET  horas_analisadas = '.$horas_bk.'
							 		WHERE  backlog_item = '.$backlog_item.';';
					$res_bk_up = pg_query($con,$sql_bk_up);
					$msg_erro .= pg_last_error($con);
				}

			}

		}elseif($melhorias){

			$sql =	'INSERT INTO tbl_hd_chamado_melhoria (
							hd_chamado,
							interacao,
							qtde_horas,
							admin
						) VALUES (
							'.$_POST['chamado'].',
							E\''.$melhorias.'\',
							'.$qtde_horas_melhorias.',
							'.$login_admin.'
						);';
			$res = pg_query($con,$sql);
			$msg_erro .= pg_last_error($con);

			if($qtde_horas_melhorias > 0) {
				$sql_bk_up = "UPDATE  tbl_backlog_item
									SET  horas_analisadas = horas_analisadas + $qtde_horas_melhorias
								WHERE  hd_chamado = ".$_POST['chamado'].";";
				$res_bk_up = pg_query($con,$sql_bk_up);
				$msg_erro .= pg_last_error($con);
			}
		}

	}
	//echo $msg_erro;
	if(strlen($msg_erro) > 0){
		$res = pg_query($con, "ROLLBACK");
		//$res = pg_rollBack();
	}else{
		if ($_serverEnvironment == 'production') {
			// #######################################
			// ---------------------------------------
			$painel->setChamadoAtendente($_POST['chamado']);
			// ---------------------------------------
			// #######################################
		}

		echo $msg_sucesso;
		$res = pg_query($con, "COMMIT");
		//header ("Location: $PHP_SELF?hd_chamado=$hd_chamado");
		//$res = pg_commit();
	}
	exit;
}

#Ao enviar o formulário de Análise, faz as validações
if($_POST['aba_post'] == 'analiseTeste1'){

	$totalAnalises = count($_POST['analises']);

	$res = pg_begin($con);

	for($i=0;$i<$totalAnalises;$i++){

		$idAnalise = isset($_POST['idAnalise'][$i]) ? $_POST['idAnalise'][$i] : null;
		$analise = $_POST['analises'][$i];
		$analise = utf8_decode($analise);
		$desenvAnalise = ($_POST['desenvAnalise'][$i]==1) ? 'TRUE' : 'FALSE';
		$testeAnalise = ($_POST['testeAnalise'][$i]==1) ? 'TRUE' : 'FALSE';

		if($idAnalise>0){

			$sql = 'SELECT analise,desenvolvimento,admin_desenvolvimento,teste,admin_teste
					  FROM tbl_hd_chamado_analise
					 WHERE hd_chamado_analise = '.$idAnalise;
			$res = pg_query($con,$sql);
			$num = pg_num_rows($res);

			if($num){

				$desenvolvimento = pg_fetch_result($res,0,'desenvolvimento');
				$admin_desenvolvimento = pg_fetch_result($res,0,'admin_desenvolvimento');
				$teste = pg_fetch_result($res,0,'teste');
				$admin_teste = pg_fetch_result($res,0,'admin_teste');

				$adminDesenvolvimento = ($desenvAnalise == 'TRUE' && $desenvolvimento == 'f') ? $login_admin : $admin_desenvolvimento;
				$adminTeste = ($testeAnalise == 'TRUE' && $teste == 'f') ? $login_admin : $admin_teste;

				if(!$adminDesenvolvimento)
					$adminDesenvolvimento = 'NULL';

				if(!$adminTeste)
					$adminTeste = 'NULL';

				$sql = 'UPDATE  tbl_hd_chamado_analise
						   SET  desenvolvimento = '.$desenvAnalise.',
								admin_desenvolvimento = '.$adminDesenvolvimento.',
								teste = '.$testeAnalise.',
								admin_teste = '.$adminTeste.',
								admin = '.$login_admin.'
						 WHERE  hd_chamado_analise = '.$idAnalise.';';
				$res = pg_query($con,$sql);
				$msg_erro .= pg_last_error($con);

			}

		}

	}

	if(strlen($msg_erro) > 0){
		echo $msg_erro;
		$res = pg_rollBack();
	}else{
		echo $msg_sucesso;
		$res = pg_commit();
	}
	exit;

}

if($_POST['aba_post'] == 'teste1'){
	$res = pg_begin();

	$procedimento_teste = $_POST['procedimento_teste'];
	$procedimento_teste = utf8_decode($procedimento_teste);
	$comentario_desenvolvedor  = $_POST['comentario_desenvolvedor'];
	$comentario_desenvolvedor = utf8_decode($comentario_desenvolvedor);

	$sql = 'UPDATE  tbl_hd_chamado
			   SET  procedimento_teste = E\''.$procedimento_teste.'\',
					comentario_desenvolvedor = E\''.$comentario_desenvolvedor.'\',
					admin_procedimento_teste = E\''.$login_admin.'\'
			 WHERE  hd_chamado = '.$_POST['chamado'].';';
	$res = pg_query($con,$sql);
	$msg_erro .= pg_last_error($con);

	if(strlen($msg_erro) > 0){
		echo $msg_erro;
		$res = pg_rollBack();
	}else{
		echo $msg_sucesso.'|'.nl2br($procedimento_teste);
		$res = pg_commit();
	}
	exit;
}

if ($_POST['aba_post'] == 'teste2') {
	$res = pg_begin();
	
	$erros .= "";
	
	/// update data entrega validacao
	$query_select_status = "SELECT status_chamado FROM tbl_status_chamado ts 
	JOIN tbl_controle_status tc ON tc.controle_status = ts.controle_status 
	WHERE ts.hd_chamado = {$hd_chamado} AND tc.status LIKE '%Validacao%' AND ts.data_entrega IS NULL";
	$result = pg_query($con, $query_select_status);

	if (pg_numrows($result) > 0) {
		$status_chamado = pg_fetch_result($result, 0, 'status_chamado');
		$data_entrega = date('Y-m-d H:i:s');
		
		$query_update_status = "UPDATE tbl_status_chamado SET data_entrega = '{$data_entrega}' WHERE status_chamado = {$status_chamado};";
		pg_query($con, $query_update_status);
		
		$erros .= pg_last_error();
	}
	
	// insert campo teste
	$testes = utf8_encode($_POST['proc_teste']);	
	
	$query_select_adicionais = "SELECT array_campos_adicionais FROM tbl_hd_chamado_extra WHERE hd_chamado = {$hd_chamado};";
	$result = pg_query($con, $query_select_adicionais);
	
	$array_adicionais = [];
	if (pg_num_rows($result) > 0) {
		$array_adicionais = pg_fetch_assoc($result);
		$array_adicionais = $array_adicionais['array_campos_adicionais'];
		$array_adicionais = json_decode($array_adicionais, true);
		
		$query_insert_adicionais = "UPDATE tbl_hd_chamado_extra SET array_campos_adicionais = $2 WHERE hd_chamado = $1;";
	} else {
		$query_insert_adicionais = "INSERT INTO tbl_hd_chamado_extra 
		(hd_chamado, array_campos_adicionais) VALUES ($1, $2);";
	}
	
	$array_adicionais['testes'] = $testes;
	$json_adicionais = json_encode($array_adicionais);
	
	$params = [$hd_chamado, $json_adicionais];
	pg_query_params($con, $query_insert_adicionais, $params);
	
	$erros .= pg_last_error();
	
	if (strlen($erros) > 0) {
		echo $erros;
		$res = pg_rollBack();
	} else {
		$res = pg_commit();
	}

	header ("Location: $PHP_SELF?hd_chamado=$hd_chamado");
	exit;
}

if($_POST['aba_post'] == 'efetivacao1'){

	$procedimento_efetivacao = $_POST['procedimento_efetivacao'];
	$comentario_efetivacao = $_POST['comentario_efetivacao'];
	$procedimento_efetivacao = utf8_decode($procedimento_efetivacao);
	$comentario_efetivacao = utf8_decode($comentario_efetivacao);
	$procedimento_efetivacao = pg_escape_string($con, $procedimento_efetivacao);
	$comentario_efetivacao   = pg_escape_string($con, $comentario_efetivacao);

	$sql = 'UPDATE  tbl_hd_chamado
			   SET  procedimento_efetivacao = E\''.$procedimento_efetivacao.'\',
			        comentario_efetivacao   = E\''.$comentario_efetivacao.'\',
					admin_efetivacao        = E\''.$login_admin.'\'
			 WHERE  hd_chamado = '.$_POST['chamado'].';';
	$res = pg_query($con,$sql);
	$msg_erro .= pg_last_error($con);

	if(strlen($msg_erro) > 0){
		echo $msg_erro;
		$res = pg_rollBack();
	}else{
		echo $msg_sucesso;
		$res = pg_commit();
	}
	exit;
}

if($_POST['aba_post'] == 'validacao1'){

	$validacao = $_POST['validacao'];
	$validacao = utf8_decode($validacao);

	$sql = 'UPDATE  tbl_hd_chamado
			   SET  validacao = E\''.$validacao.'\'
			 WHERE  hd_chamado = '.$_POST['chamado'].';';

	$res = pg_query($con,$sql);
	$msg_erro .= pg_last_error($con);

	/// updates status chamdo
	$data_entrega = date('Y-m-d H:i:s');
	
	$query_select_correcao = "SELECT status_chamado
							  FROM tbl_status_chamado ts
							  JOIN tbl_controle_status tc ON ts.controle_status = tc.controle_status
							  WHERE ts.hd_chamado = {$hd_chamado}
							  AND tc.status LIKE '%Correcao%'
							  AND data_entrega IS NULL;";
	$result_correcao = pg_query($con, $query_select_correcao);
	
	// entrega correcao se existir
	
	if (pg_num_rows($result_correcao) > 0) {
		$status_chamado = pg_fetch_result($result_correcao, 0, 'status_chamado');
		$query_update_status = "UPDATE tbl_status_chamado
								SET data_entrega = '{$data_entrega}'
								WHERE status_chamado = {$status_chamado}";
		pg_query($con, $query_update_status);
	}
	
	// entrega desenvolvimento
	
	$query_select_exec = "SELECT status_chamado FROM tbl_status_chamado ts
	JOIN tbl_controle_status tc ON ts.controle_status = tc.controle_status 
	WHERE ts.hd_chamado = {$hd_chamado} AND tc.status LIKE '%Aguard.Exec%' AND ts.data_entrega IS NULL;";
	$result_exec = pg_query($con, $query_select_exec);
	
	if (pg_num_rows($result_exec) > 0) {
		$status_chamado = pg_fetch_result($result_exec, 0, 'status_chamado');
		$query_update_exec = "UPDATE tbl_status_chamado SET data_entrega = '{$data_entrega}' WHERE status_chamado = {$status_chamado};";
		pg_query($con, $query_update_exec);
	}
	
	/// insert validação
	$query_controle_status = "SELECT controle_status, status, dias FROM tbl_controle_status 
	WHERE status = 'Validacao' AND ordem = 1;";
	$result = pg_query($con, $query_controle_status);

	if (pg_numrows($result) > 0) {
		$controle_status = pg_fetch_result($result, 0, 'controle_status');
		$status_desc = pg_fetch_result($result, 0, 'status');
		$dias = pg_fetch_result($result, 0, 'dias');

		$query_verify_status = "SELECT status_chamado FROM tbl_status_chamado WHERE controle_status = {$controle_status} AND hd_chamado = {$hd_chamado};";
		$result = pg_query($con, $query_verify_status);

		if (pg_numrows($result) == 0) {
			$queryCheckAdm = "SELECT admin FROM tbl_admin WHERE grupo_admin = 6 AND fabrica = 10 AND nome_completo LIKE 'Suporte'";
			$resultCheck = pg_query($con, $queryCheckAdm);
			$checkAdmin = pg_fetch_result($resultCheck, 0, 'admin');

			$query_select_admin = "SELECT COUNT(tbl_hd_chamado.hd_chamado) AS tantos, tbl_admin.admin, tbl_admin.nome_completo FROM tbl_hd_chamado
	        JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado.atendente
	        WHERE tbl_hd_chamado.fabrica_responsavel = 10
	        AND tbl_admin.grupo_admin IN (6)
	        AND tbl_admin.ativo IS TRUE
	        AND tbl_admin.admin <> 2466
	        AND status NOT IN ('Resolvido', 'Cancelado', 'Parado')
	        AND tbl_admin.admin NOT IN ({$checkAdmin}, 6375)
	        GROUP BY tbl_admin.admin ORDER BY tantos ASC;";
	        $result = pg_query($con, $query_select_admin);

	        $admin = pg_fetch_result($result, 0, 'admin');

			$data_inicio = date('Y-m-d H:i:s');
			$data_prazo = date('Y-m-d H:i:s', strtotime('+' . $dias . ' day', time()));

			/* Removido HD-6381421
			$params = [$hd_chamado, $admin, $controle_status, $status_desc, $data_inicio, $data_prazo];

			$query_insert_status = "INSERT INTO tbl_status_chamado (hd_chamado, admin, controle_status, status, data_inicio, data_prazo) 
			VALUES ($1, $2, $3, $4, $5, $6);";
			pg_query_params($con, $query_insert_status, $params);*/
		}
	}

	if(strlen($msg_erro) > 0){
		echo $msg_erro;
		$res = pg_rollBack();
	}else{
		echo $msg_sucesso;
		$res = pg_commit();
	}
	exit;
}

if($_POST['aba_post'] == 'orcamento1'){

	$admin_orcamento  = $login_admin;
	$status_orcamento = 'Orçamento';
	$interno = 'TRUE';

	if ($tercerizado) {
		$admin_orcamento  = 1375;
		$status_orcamento = 'Análise';
		$interno = 'TRUE';
	}

	$taxa_abertura    = ($_POST['taxa_abertura'])    ? $_POST['taxa_abertura']    : 'NULL';
	$horas_suporte    = ($_POST['horas_suporte'])    ? $_POST['horas_suporte']    : 'NULL';
	$horas_telefone   = ($_POST['horas_telefone'])   ? $_POST['horas_telefone']   : 'NULL';
	$horas_analise    = ($_POST['horas_analise'])    ? $_POST['horas_analise']    : 'NULL';
	$prazo_horas      = ($_POST['prazo_horas'])      ? $_POST['prazo_horas']      : 'NULL';
	$horas_teste      = ($_POST['horas_teste'])      ? $_POST['horas_teste']      : 'NULL';
	$horas_efetivacao = ($_POST['horas_efetivacao']) ? $_POST['horas_efetivacao'] : 'NULL';
	$desconto         = ($_POST['desconto']) ? $_POST['desconto'] : 'NULL';
	$aux_desconto = str_replace(".", "", $desconto);
	$aux_desconto = str_replace(",", ".", $aux_desconto);

	$total_horas = round($taxa_abertura + $horas_suporte + $horas_telefone + $horas_analise + $horas_teste + $horas_efetivacao + $prazo_horas, 1);
	$cobrar = ($prazo_horas > 0) ? 'TRUE':'FALSE';

	
	$sql = "UPDATE  tbl_hd_chamado
			   SET  horas_suporte         = $horas_suporte,
					horas_suporte_telefone= $horas_telefone,
					horas_analise         = $horas_analise,
					taxa_abertura         = $taxa_abertura,
					prazo_horas           = $prazo_horas,
					hora_desenvolvimento  = $total_horas,
					horas_desenvolvimento = $prazo_horas,
					horas_teste           = $horas_teste,
					cobrar                = $cobrar,
					horas_efetivacao      = $horas_efetivacao,
					data_envio_aprovacao  = CURRENT_TIMESTAMP,
					valor_desconto         = $aux_desconto,
					exigir_resposta        = TRUE
			 WHERE  hd_chamado            = {$_POST['chamado']}";

	$res = pg_query($con,$sql);
	$msg_erro .= pg_last_error($con);

	$query_status_chamado = "SELECT ts.status_chamado, ts.data_input, tc.status, tc.ordem FROM tbl_status_chamado ts 
	JOIN tbl_controle_status tc ON tc.controle_status = ts.controle_status 
	WHERE ts.hd_chamado = {$hd_chamado} ORDER BY ts.data_input DESC;";
	$result = pg_query($con, $query_status_chamado);
	$chamado = pg_fetch_result($result, 0, 'status_chamado');
	$ordem = pg_fetch_result($result, 0, 'ordem');
	
	if (strlen(pg_last_error()) == 0 and pg_num_rows($result) > 0) {
		$data = date('Y-m-d H:i:s');
		$query_update_status = "UPDATE tbl_status_chamado SET data_entrega = '{$data}' WHERE status_chamado = {$chamado}";
		pg_query($con, $query_update_status);
	}
	
	$query_next_step = "SELECT controle_status, dias FROM tbl_controle_status WHERE status = 'Orcamento' AND ordem = 2;";
	$result = pg_query($con, $query_next_step);
	
	if (strlen(pg_last_error()) == 0) {
		$controle_status = pg_fetch_result($result, 0, 'controle_status');
		$dias = pg_fetch_result($result, 0, 'dias');
		
		// $query_select_admin = "SELECT ts.admin FROM tbl_controle_status tc
		// JOIN tbl_status_chamado ts ON tc.controle_status = ts.controle_status
		// WHERE tc.status = 'Orcamento' AND ts.hd_chamado = {$hd_chamado} AND tc.ordem = 1;";
		// $result = pg_query($con, $query_select_admin);
		// $admin = pg_fetch_result($result, 0, 'admin');

		$time = time();
		$data_prazo = date('Y-m-d H:i:s', strtotime('+' . $dias . ' day', $time));

		$params = [$hd_chamado, $login_admin, $controle_status, 'Orcamento', date('Y-m-d H:i:s'), $data_prazo];

		$query_insert_status = "INSERT INTO tbl_status_chamado (hd_chamado, admin, controle_status, status, data_inicio, data_prazo) 
		VALUES ($1, $2, $3, $4, $5, $6);";
		pg_query_params($con, $query_insert_status, $params);
		
		if (strlen(pg_last_error()) > 0) {
			echo "Erro ao inserir controle de status. <br>";
		}
	}

	if(strlen($msg_erro) > 0){
		$res = pg_rollBack();
	}else{
		if ($_serverEnvironment == 'production') {
			// #######################################
			// ---------------------------------------
			$painel->setChamadoAtendente($_POST['chamado']);
			// ---------------------------------------
			// #######################################
		}

		$res = pg_commit();

		//Prepara a tabela com o orçamento.
		if($sistema_lingua == 'ES'){
			$mensagem_orcamento = "
			<p><strong>Horario Detallado Presupuesto.</strong><br />
			Según la encuesta, se presenta el presupuesto para atender esta llamada.<br>
			Se van a utilizar las cantidades de horas siguientes:</p>
			<dl>\n";
			if (is_numeric($horas_suporte))    $mensagem_orcamento .= "<p>Horas Soporte: $horas_suporte</p>";
			if (is_numeric($horas_telefone))   $mensagem_orcamento .= "<p>Horas Soporte Teléfono: $horas_telefone</p>";
			if (is_numeric($horas_analise))    $mensagem_orcamento .= "<p>Horas Análisis: $horas_analise</p>";
			if (is_numeric($prazo_horas))      $mensagem_orcamento .= "<p>Horas Desarrollo: $prazo_horas</p>";
			if (is_numeric($horas_teste))      $mensagem_orcamento .= "<p>Horas Preuba: $horas_teste</p>";
			if (is_numeric($horas_efetivacao)) $mensagem_orcamento .= "<p>Horas Efectuación: $horas_efetivacao</p>";
			if (is_numeric($aux_desconto)) $mensagem_orcamento .= "<p>Descuento: R$".number_format($aux_desconto,2,',','.')."</p>";

			$mensagem_orcamento .= "<p><strong>Total Horas: $total_horas</strong></p><p><b>La aprobación de este presupuesto está disponible en la pantalla del supervisor, situado en la área de Help-Desk, simplemente haga clic en el botón APROBAR PRESUPUESTO.<b></p>";

			$mensagem_orcamento .= "<br> <strong style=\"color: #ff0000;font-size: 14px;\"> Atención, si la llamada no se aprueba dentro de los 10 días que será cancelada automáticamente por el sistema de Help Desk</strong>";
		}else{
			$mensagem_orcamento = "
			<p><strong>Orçamento de horas detalhadas.</strong><br />
			Conforme o levantamento realizado, apresentamos o orçamento para atender este chamado.<br>
			Serão utilizadas as quantidades de horas a seguir:</p>
			<dl>\n";
			if (is_numeric($horas_suporte))    $mensagem_orcamento .= "<p>Horas Suporte: $horas_suporte</p>";
			if (is_numeric($horas_telefone))   $mensagem_orcamento .= "<p>Horas Suporte Telefone: $horas_telefone</p>";
			if (is_numeric($horas_analise))    $mensagem_orcamento .= "<p>Horas Análise: $horas_analise</p>";
			if (is_numeric($prazo_horas))      $mensagem_orcamento .= "<p>Horas Desenvolvimento: $prazo_horas</p>";
			if (is_numeric($horas_teste))      $mensagem_orcamento .= "<p>Horas Teste: $horas_teste</p>";
			if (is_numeric($horas_efetivacao)) $mensagem_orcamento .= "<p>Horas Efetivação: $horas_efetivacao</p>";
			if (is_numeric($aux_desconto)) $mensagem_orcamento .= "<p>Desconto: R$".number_format($aux_desconto,2,',','.')."</p>";

			$mensagem_orcamento .= "<p><strong>Total Horas: $total_horas</strong></p><p><b>A aprovação deste orçamento está disponível na tela do supervisor, localizada na área de Help-Desk, bastando clicar no botão APROVAR ORÇAMENTO.<b></p>";

			$mensagem_orcamento .= "<br> <strong style=\"color: #ff0000;font-size: 14px;\"> Atenção, caso o chamado não seja aprovado dentro de 10 dias úteis ele será cancelado automaticamente pelo sistema Help-Desk</strong>";
		}
		$res = pg_begin();
		$sql = " INSERT INTO tbl_hd_chamado_item (
						hd_chamado,
						comentario,
						admin
					) VALUES (
						$hd_chamado,
						'$mensagem_orcamento',
						435
				)";
		$res = pg_query($con, $sql);
		if (is_resource($res)) {
		$res = pg_commit();

		$sqlM = "SELECT tbl_admin.email
				FROM tbl_hd_chamado
				JOIN tbl_admin ON tbl_hd_chamado.admin = tbl_admin.admin
				  AND tbl_admin.fabrica = tbl_hd_chamado.fabrica
				  AND tbl_admin.help_desk_supervisor IS TRUE
				WHERE tbl_hd_chamado.hd_chamado = $hd_chamado";
		$resM = pg_query($con,$sqlM);

		for($i = 0; $i < pg_num_rows($res); $i++){
			$destinatario .= pg_result($resM,$i,'email'); //Emails supervisor helpdesk

			if($i > 0){
				$destinatario .= ",";
			}
		}

		$sqlM = "SELECT tbl_admin.email
					FROM tbl_hd_chamado
					JOIN tbl_admin ON tbl_hd_chamado.admin = tbl_admin.admin
					WHERE tbl_hd_chamado.hd_chamado = $hd_chamado";
		$resM = pg_query($con,$sqlM);

		if(pg_result($resM,0,'email') <> $destinatario){
			$destinatario .=  pg_result($resM,0,'email'); // Email Admin que abriu o chamado
		}
		if($sistema_lingua == 'ES'){
			$assunto = "APROBAR PRESUPUESTO LLAMADA: ".$_POST['chamado'];
		}else{
			$assunto = "APROVAR ORÇAMENTO CHAMADO: ".$_POST['chamado'];
		}

		// To send HTML mail, the Content-type header must be set
		$headers  = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

		// Additional headers
		$headers .= "To: $destinatario" . "\r\n";
		$headers .= 'From: helpdesk@telecontrol.com.br' . "\r\n";
        $mailer->sendMail($destinatario, $assunto, $mensagem_orcamento, 'helpdesk@telecontrol.com.br');

		echo $msg_sucesso;
		} else {
			pg_rollBack();
			echo "Erro ao gravar a interação do Orçamento";
		}

	}
	exit;
}

#Ao enviar o formulário de Análise, faz as validações
if($_POST['aba_post'] == 'correcao1'){

	$res = pg_begin($con);

	$rodada = $_POST['rodada'];
	$chamado = $_POST['chamado'];

	$totalCorrecaos = count($_POST['descricaoCorrecaos']);
	#Maior interação das Análise
	$maiorInteracao = getMaiorInteracao('tbl_hd_chamado_correcao',$chamado,$rodada);
	$maiorInteracao++;

	for($i=0;$i<$totalCorrecaos;$i++){

		$idCorrecao = isset($_POST['idCorrecao'][$i]) ? $_POST['idCorrecao'][$i] : null;
		if($_POST['at'] == 1){
			$descricaoCorrecaos = utf8_decode($_POST['descricaoCorrecaos'][$i]);
			$analiseCorrecaos = utf8_decode($_POST['analiseCorrecaos'][$i]);
		}else{
			$descricaoCorrecaos = $_POST['descricaoCorrecaos'][$i];
			$analiseCorrecaos = $_POST['analiseCorrecaos'][$i];
		}
		$gravidadeCorrecaos = $_POST['gravidadeCorrecaos'][$i];
		$atendidoCorrecaos = $_POST['atendidoCorrecaos'][$i];
		$rodadaFinalizada = ($_POST['rodadaFinalizada']) ? $_POST['rodadaFinalizada'] : 'FALSE';

		if($idCorrecao>0){

			$sql = 'SELECT atendido,admin_atendido
					  FROM tbl_hd_chamado_correcao
					 WHERE hd_chamado_correcao = '.$idCorrecao;
			$res = pg_query($con,$sql);
			$num = pg_num_rows($res);
			if($num){

				$atendido = pg_fetch_result($res,0,'atendido');
				$admin_atendido = pg_fetch_result($res,0,'admin_atendido');

				$adminAtendido = ($atendidoCorrecaos == 'TRUE' && $atendido == 'f') ? $login_admin : $admin_atendido;

				if(!$adminAtendido)
					$adminAtendido = 'NULL';

				$sql = 'UPDATE  tbl_hd_chamado_correcao
						   SET  descricao = E\''.$descricaoCorrecaos.'\',
								rodada = '.$rodada.',
								analise = E\''.$analiseCorrecaos.'\',
								gravidade = '.$gravidadeCorrecaos.',
								atendido = '.$atendidoCorrecaos.',
								admin_atendido = '.$adminAtendido.',
								rodada_finalizada = TRUE,
								admin = '.$login_admin.'
						 WHERE  hd_chamado_correcao = '.$idCorrecao.';';

				$res = pg_query($con,$sql);
				$msg_erro .= pg_last_error($con);
			}

		}else{

			$adminAtendido = ($atendidoCorrecaos == 'TRUE') ? $login_admin : 'NULL';

			$sql =	'INSERT INTO tbl_hd_chamado_correcao (
							hd_chamado,
							descricao,
							rodada,
							analise,
							gravidade,
							atendido,
							admin_atendido,
							rodada_finalizada,
							interacao,
							admin
						) VALUES (
							'.$chamado.',
							E\''.$descricaoCorrecaos.'\',
							'.$rodada.',
							E\''.$analiseCorrecaos.'\',
							'.$gravidadeCorrecaos.',
							'.$atendidoCorrecaos.',
							'.$adminAtendido.',
							'.$rodadaFinalizada.',
							'.$maiorInteracao.',
							'.$login_admin.'
						);';
			$res = pg_query($con,$sql);
			$msg_erro .= pg_last_error($con);

			$maiorInteracao++;
			$iii_teste .= "INSERT<br>";
		}

	}

	if(strlen($msg_erro) > 0){
		if($_POST['at'] == 1){
			echo $msg_erro;
		}
		$res = pg_rollBack();
	}else{
		$res = pg_commit();
		if($_POST['at'] == 1){
			echo $msg_sucesso;
			exit;
		}
		header ("Location: $PHP_SELF?hd_chamado=$hd_chamado");
	}

}

#Ao enviar o formulário de Análise, faz as validações
if($_POST['aba_post'] == 'check1' OR $_GET['check1']==1 ){

	$idCheck = $_GET['check'];
	$hd_chamado = $_GET['hd_chamado'];
	$atendido = $_GET['opcao'];
	$sqlCheck = "SELECT hd_chamado
				FROM tbl_hd_chamado_checklist
				WHERE hd_chamado = " . $hd_chamado . " AND admin <> " . $login_admin;
	$resCheck = pg_query($con,$sqlCheck);
	$numCheck   = pg_num_rows($resCheck);

	if($numCheck > 0){

		$sql = "UPDATE tbl_hd_chamado_checklist
		           SET admin = ".$login_admin."
				 WHERE hd_chamado = ".$hd_chamado;
		$res = pg_query($con,$sql);
		$msg_erro .= pg_last_error($con);

	}

	//consulta se ja tem cadastrado o checklist, senao tiver, faz insert abaixo.
	$sql = "SELECT hd_chamado FROM tbl_hd_chamado_checklist WHERE hd_chamado = $hd_chamado AND checklist = $idCheck";
	$res = pg_query($con,$sql);
	if (pg_num_rows($res) == 0) {
		$sql =	'INSERT INTO tbl_hd_chamado_checklist (
						hd_chamado,
						checklist,
						atendido,
						admin
					) VALUES (
						'.$hd_chamado.',
						'.$idCheck.',
						'.$atendido.',
						'.$login_admin.'
					);';
		$res = pg_query($con,$sql);
	}
	if(strlen($msg_erro) > 0){
		echo $msg_erro;
	}else{

		$sqlAdmin = 'SELECT tbl_admin.nome_completo
					  FROM tbl_admin
					 WHERE admin = '.$login_admin;

		$resAdmin = pg_query($con,$sqlAdmin);
		//$num_checklist= pg_numrows($resAdmin);
		$admin_checklist = pg_result($resAdmin,0,'nome_completo');
		echo $admin_checklist;
	}

	exit;

}
#Fim Trecho alterado por Thiago Contardi HD: 304470

$atualiza_hd = $_GET['atualiza_hd'];
if(strlen($atualiza_hd)==0){
	$atualiza_hd = $_POST['atualiza_hd'];
}


$atualiza_hd_terceiro = $_GET['atualiza_hd_terceiro'];
if(strlen($atualiza_hd_terceiro)==0){
	$atualiza_hd_terceiro = $_POST['atualiza_hd_terceiro'];
}

if(strlen($atualiza_hd)>0){
	$hd   = $_GET['hd'];
	$hr   = $_GET['hr'];
	$prazo = $_GET['prazo'];
	if(strlen($hd)>0 and strlen($hr)>0){
		$sql = "UPDATE tbl_hd_chamado set hora_desenvolvimento = $hr
				where hd_chamado = $hd
				and fabrica_responsavel = $login_fabrica";
		$res = pg_query($con,$sql);
		$msg_erro = pg_last_error($con);

		echo (strlen($msg_erro)==0) ? "Atualizado com Sucesso!" : "Ocorreu o seguinte erro $msg_erro";
	}
	if(strlen($hd)>0 and strlen($prazo)>0){
		$sql = "UPDATE tbl_hd_chamado set prazo_horas= $prazo
				where hd_chamado = $hd
				and fabrica_responsavel = $login_fabrica";
		$res = pg_query($con,$sql);
		$msg_erro = pg_last_error($con);
		echo (strlen($msg_erro)==0) ? "Prazo atualizado!" : "Ocorreu o seguinte erro $msg_erro";
	}
	exit;
}


if(strlen($atualiza_hd_terceiro)>0){
	$tipo  = $_GET['tipo'];
	$hd    = $_GET['hd'];
	$hr    = $_GET['hr'];
	$prazo = $_GET['prazo'];

	if(strlen($hd)>0 and strlen($prazo)>0 and $tipo == 1){
		$sql = "UPDATE tbl_hd_chamado set horas_franq_ter = $prazo
				where hd_chamado = $hd
				and fabrica_responsavel = $login_fabrica";
		$res = pg_query($con,$sql);
		$msg_erro = pg_last_error($con);

		echo (strlen($msg_erro)==0) ? "Prazo atualizado!" : "Ocorreu o seguinte erro $msg_erro";
	}
	if(strlen($hd)>0 and strlen($prazo)>0 and $tipo == 2){
		$sql = "UPDATE tbl_hd_chamado set horas_fat_ter= $prazo
				where hd_chamado = $hd
				and fabrica_responsavel = $login_fabrica";
		$res = pg_query($con,$sql);
		$msg_erro = pg_last_error($con);
		echo (strlen($msg_erro)==0) ? "Prazo atualizado!" : "Ocorreu o seguinte erro $msg_erro";
	}
	exit;
}

$atualiza_previsao_termino = getPost('atualiza_previsao_termino');
if (strlen($atualiza_previsao_termino)>0) {
	$hd_chamado          = is_numeric($_REQUEST['hd']) ? (int)$_REQUEST['hd'] : false;
	$previsao_termino    = is_date(getPost('data_previsao'));
	$fabrica_responsavel = $login_fabrica;
	$admin               = $login_admin;

	if ($hd_chamado and $previsao_termino) {
		$sql = sql_cmd(
			'tbl_hd_chamado',
			array('previsao_termino' => $previsao_termino),
			compact('hd_chamado', 'fabrica_responsavel')
		);
		$res = pg_query($con, $sql);
		if (is_resource($res) and !pg_last_error($con) and pg_affected_rows($res) == 1) {
			$comentario = 'A previsão de término deste chamado é para '.is_date($previsao_termino, '', 'EUR') . '.';
			$sql = sql_cmd(
				'tbl_hd_chamado_item',
				compact('hd_chamado', 'comentario', 'admin')
			);
			$res = pg_query($con, $sql);
		}
		if (!is_resource($res) or strlen($pg_erro=pg_last_error($con))) {
			$msg = "Ocorreu o seguinte erro: $msg.";
		} else {
			$query_select_status = "SELECT ts.status_chamado FROM tbl_status_chamado ts 
			JOIN tbl_controle_status tc ON tc.controle_status = ts.controle_status 
		    WHERE ts.hd_chamado = {$hd_chamado} AND tc.status = 'Analise' AND tc.ordem = 3 ORDER BY ts.data_input DESC;";
		    $result = pg_query($con, $query_select_status);

		    if (pg_numrows($result) > 0) {
		    	$status_chamado = pg_fetch_result($result, 0, 'status_chamado');
		    	$data_entrega = date('Y-m-d H:i:s');

		    	$query_update_status = "UPDATE tbl_status_chamado SET data_entrega = '{$data_entrega}' WHERE status_chamado = {$status_chamado};";
		    	pg_query($con, $query_update_status);
		    }
			$msg = "Atualizado com êxito!";
		}
		die($msg);
	}
}

$begin_aberto = 0;

if (!function_exists('pg_begin')) {
	function pg_begin($savepoint=null)
	{ // BEGIN function pg_begin
		global $con, $begin_aberto;

	//	Se já há um begin aberto, não abrir um outro, o banco retorna um Warning
		if ($begin_aberto > 0 and is_null($savepoint)) return false;
		$sql = (is_null($savepoint)) ? 'BEGIN TRANSACTION' :"SAVEPOINT $savepoint";
		$res = @pg_query($con, $sql);
		if (is_resource($res)) {
			$begin_aberto+= 1;
			return $res;
		}
		return false;
	} // END function pg_begin

	function pg_commit($savepoint=null)
	{ // BEGIN function pg_commit
		global $con, $begin_aberto;
		if (!$begin_aberto) return false;
		$sql = (is_null($savepoint)) ? 'COMMIT' : "RELEASE SAVEPOINT $savepoint";
		$res = @pg_query($con, $sql);
		if (is_resource($res)) {
			$begin_aberto-= 1;
			return $res;
		}
		return false;
	}

	function pg_rollBack($savepoint=null)
	{ // BEGIN function pg_rollBack
		global $con, $begin_aberto;
		$sql = (is_null($savepoint)) ? 'ROLLBACK TRANSACTION' : "ROLLBACK TO SAVEPOINT $savepoint";
		$res = @pg_query($con, $sql);
		if (is_resource($res)) {
			$begin_aberto-= 1;
			return $res;
		}
		return false;
	}
}

if($_POST['hd_chamado']) $hd_chamado = trim ($_POST['hd_chamado']);
if($_GET ['hd_chamado']) $hd_chamado = trim ($_GET ['hd_chamado']);

if($_POST['btn_tranferir']) $btn_tranferir = trim ($_POST['btn_tranferir']);
if($_POST['btn_acao'])      $btn_acao      = trim ($_POST['btn_acao']);

if (strlen ($btn_tranferir) > 0) {

	if($_POST['transfere'])           {
		$transfere      = trim ($_POST['transfere']);
	}
	$data_resolvido = "";
	if($status == 'Resolvido'){
		$data_resolvido = " data_resolvido = CURRENT_TIMESTAMP,";
	}
	$sql =" UPDATE tbl_hd_chamado
			SET status = '$status' ,
				$data_resolvido
				atendente = $transfere
			WHERE hd_chamado = $hd_chamado";
	$res = pg_query($con,$sql);

	if ($_serverEnvironment == 'production') {
		// #######################################
		// ---------------------------------------
		$painel->setChamadoAtendente($hd_chamado);
		// ---------------------------------------
		// #######################################
	}
}

if(strlen($hd_chamado)>0){
	$sql =" SELECT SUM(CASE WHEN data_termino IS NULL THEN CURRENT_TIMESTAMP ELSE data_termino END - data_inicio )
			FROM tbl_hd_chamado_atendente WHERE hd_chamado = $hd_chamado;";
	$res = pg_query($con, $sql);
	if( pg_num_rows($res)>0)
		$horas= pg_fetch_result($res, 0, 0);
}


if(strlen($_POST['btn_telefone']) > 0) { // HD 39347

	$res = pg_begin();

	$sql =" SELECT hd_chamado_item
		FROM tbl_hd_chamado_item
		WHERE hd_chamado = (SELECT hd_chamado FROM tbl_hd_chamado_atendente WHERE admin = $login_admin AND data_termino IS NULL LIMIT 1)
			AND termino IS NULL
		ORDER BY hd_chamado_item desc
		LIMIT 1 ;";

	$res = pg_query($con,$sql);
	$msg_erro .= pg_last_error($con);
	if( pg_num_rows($res)>0){

		$hd_chamado_item = pg_fetch_result($res, 0, 'hd_chamado_item');

		//Faz update do hd_chamado_item com o tempo de execução da ultima interação.
		$sql =" UPDATE tbl_hd_chamado_item
				SET termino = current_timestamp
				WHERE hd_chamado_item in(SELECT hd_chamado_item
							 FROM tbl_hd_chamado_item
							 WHERE hd_chamado = (SELECT hd_chamado FROM tbl_hd_chamado_atendente WHERE admin = $login_admin AND data_termino IS NULL LIMIT 1)
								AND termino IS NULL
							 ORDER BY hd_chamado_item desc
							 LIMIT 1 );";

		$res = pg_query($con,$sql);
		$msg_erro .= pg_last_error($con);
	}

	$sql ="SELECT	hd_chamado_atendente  ,
					hd_chamado            ,
					data_termino
			FROM tbl_hd_chamado_atendente
			WHERE admin = $login_admin
			ORDER BY data_termino DESC
			LIMIT 1";
	$res = pg_query($con,$sql);

	if( pg_num_rows($res)>0){
		$xhd_chamado           = pg_fetch_result($res, 0, 'hd_chamado');
		$data_termino          = pg_fetch_result($res, 0, 'data_termino');
		$hd_chamado_atendente  = pg_fetch_result($res, 0, $hd_chamado_atendente);
		if(strlen($data_termino)==0) {/*atendente estava trabalhando com algum chamado*/
			$sql =	"INSERT INTO tbl_hd_chamado_item (
							hd_chamado                   ,
							comentario                   ,
							interno                      ,
							admin                        ,
							data                         ,
							termino                      ,
							atendimento_telefone
						) VALUES (
							$xhd_chamado                                                  ,
							'Chamado interrompido para atendimento de telefone'           ,
							't'                                                           ,
							$login_admin                                                  ,
							current_timestamp                                             ,
							current_timestamp                                             ,
							't'
						);";
			$res = pg_query($con,$sql);
			$msg_erro = pg_last_error($con);
			if(strlen($msg_erro)==0){
				$sql = "UPDATE tbl_hd_chamado_atendente SET data_termino=CURRENT_TIMESTAMP
						WHERE hd_chamado_atendente = $hd_chamado_atendente and data_termino isnull ";
				$res = pg_query($con,$sql);
				$msg_erro = pg_last_error($con);
			}
		}
	}
	$sql = "INSERT INTO tbl_hd_chamado_atendente(
				hd_chamado ,
				admin      ,
				data_inicio,
				atendimento_telefone,
				hd_chamado_item
				)VALUES(
				$xhd_chamado       ,
				$login_admin       ,
				CURRENT_TIMESTAMP  ,
				't',
				(select hd_chamado_item from tbl_hd_chamado_item where hd_chamado = $hd_chamado order by 1 desc limit 1) 
				)";
	$res = pg_query($con,$sql);
	$msg_erro = pg_last_error($con);

	if(strlen($msg_erro) > 0){
		$res = pg_rollBack();
	}else{
		$res = pg_commit();
		header ("Location: $PHP_SELF?hd_chamado=$xhd_chamado");
	}
} // HD 39347

if (strlen ($btn_acao) > 0) {

	if($_POST['comentario'])          {$comentario			= pg_escape_string(trim($_POST['comentario']));}
	if($_POST['transfere'])           {$transfere			= trim($_POST['transfere']);}
	if($_POST['admin_desenvolvedor']) {
		$admin_desenvolvedor	= trim($_POST['admin_desenvolvedor']);
	}else{
		$admin_desenvolvedor	= 'NULL';
	}
	if($_POST['status'])              {$status				= trim($_POST['status']);}
	if($_POST['categoria'])           {$categoria			= trim($_POST['categoria']);}
	if($_POST['sequencia'])           {$sequencia			= trim($_POST['sequencia']);}
	if($_POST['interno'])             {$interno				= trim($_POST['interno']);}
	if($_POST['exigir_resposta'])     {$exigir_resposta		= trim($_POST['exigir_resposta']);}
	if($_POST['hora_desenvolvimento']){$hora_desenvolvimento= trim($_POST['hora_desenvolvimento']);}
	if($_POST['cobrar'])              {$cobrar				= trim($_POST['cobrar']);}
	if($_POST['prioridade'])          {$prioridade			= trim($_POST['prioridade']);}

	if($_POST['prazo_horas'])         {$prazo_horas			= trim($_POST['prazo_horas']);}
	if($_POST['tipo_chamado'])        {$tipo_chamado		= trim($_POST['tipo_chamado']);}

	if($_POST['horas_analisadas'])        {$horas_analisadas		= trim($_POST['horas_analisadas']);}
	if($_POST['importante'])        {$importante		= trim($_POST['importante']);}

	if (isset($_POST['hd_chamado'])) {
 
		$query = " SELECT campos_adicionais
				   FROM tbl_hd_chamado  
				   WHERE hd_chamado = {$hd_chamado}";

	   	$res = pg_query($con, $query);

	   	$campos_adicionais = pg_fetch_result($res, 0, 'campos_adicionais');
	   	$campos_adicionais = json_decode($campos_adicionais, True);
	}

	if($interno){
		unset($_POST['exigir_resposta'],$exigir_resposta);
	}

	$fabricaChamado = $_POST['fabricaChamado']; 

	if(strlen(trim($_POST['previsao_termino_interna']))>0){
		$previsao_termino_interna = $_POST['previsao_termino_interna'];
		$campos_previsao_termino_interna = ", previsao_termino_interna = '$previsao_termino_interna' ";
	}

	$msg_interacao = "";

	if(isset($_POST['impacto_financeiro'])){
		$impacto_financeiro 						= $_POST["impacto_financeiro"];
		$campos_adicionais['impacto_financeiro'] 	= $impacto_financeiro;

		if($impacto_financeiro == 1){
			$descricao_impacto_financeiro = "Sim";
		}else{
			$descricao_impacto_financeiro = "Não";
		}

		if($impacto_financeiro_anterior == 1){
			$descricao_impacto_financeiro_anterior = "Sim";
		}else if ($impacto_financeiro_anterior == 2){
			$descricao_impacto_financeiro_anterior = "Não";
		} else {
			$descricao_impacto_financeiro_anterior = "Sem Valor";
		}

		if($impacto_financeiro != $_POST["impacto_financeiro_anterior"]){
			$msg_interacao = " Impacto Financeiro foi alterado de <b>". $descricao_impacto_financeiro_anterior ."</b> para <b> ".$descricao_impacto_financeiro." </b> <br>";
		}
	}

	if($fabricaChamado == 159){
		if(isset($_POST['campoPrioridade'])){
			$campoPrioridade 					= $_POST["campoPrioridade"];
			$campos_adicionais['prioridade'] 	= $campoPrioridade;

			if($_POST['campoPrioridade_anterior'] != $campoPrioridade){
				if ($_POST['campoPrioridade_anterior'] == "") {
					$_POST['campoPrioridade_anterior'] = 'Sem Valor';
				}
				$msg_interacao .= " Classificação de Prioridade foi alterada de <b>".$_POST['campoPrioridade_anterior']."</b> para <b> $campoPrioridade </b> <br>";	
			}			
		}	
	}
	$campos_adicionais = json_encode( $campos_adicionais);
	$field_campos_adicionais = ", campos_adicionais 	= '$campos_adicionais' ";

	if($login_fabrica == 10){

       $atendente_responsavel = $_POST["atendente_responsavel"];

       if(strlen($atendente_responsavel) > 0){

       		$sql_ar = "SELECT login_admin FROM tbl_hd_chamado WHERE hd_chamado = {$hd_chamado}";
       		$res_ar = pg_query($con, $sql_ar);

       		if(pg_num_rows($res_ar) > 0){

       			$ar_ant = pg_fetch_result($res_ar, 0, "login_admin");

       		}

       		if($ar_ant != $atendente_responsavel){

       			$sql = "SELECT nome_completo FROM tbl_admin WHERE admin = {$ar_ant}";
       			$res = pg_query($con, $sql);

       			$nome_admin_ant = pg_fetch_result($res, 0, "nome_completo");

       			$sql = "SELECT nome_completo FROM tbl_admin WHERE admin = {$atendente_responsavel}";
       			$res = pg_query($con, $sql);

       			$nome_admin_atual = pg_fetch_result($res, 0, "nome_completo");

       			$sql = "INSERT INTO tbl_hd_chamado_item (hd_chamado, comentario, admin, interno) VALUES ({$hd_chamado}, 'Atendente Responsável alterado de <strong>{$nome_admin_ant}</strong> para <strong>{$nome_admin_atual}</strong>.', {$login_admin}, 't')";
				$res = pg_query($con, $sql);

       		}

            $sql_ar = "UPDATE tbl_hd_chamado SET login_admin = {$atendente_responsavel} WHERE hd_chamado = {$hd_chamado}";
            $res_ar = pg_query($con, $sql_ar);

       }

    }

	$admin_erro = $_POST['admin_erro'];
	$tipo_erro = $_POST['tipo_erro'];
	$motivo_erro = $_POST['motivo_erro'];

	if(!empty($admin_erro) and (empty($tipo_erro)  or empty($motivo_erro)) ) {
		$msg_erro = "Favor seleciona tipo de erro e motivo";
	}

    $admin_erro = (empty($admin_erro)) ? "NULL":$admin_erro;
    $tipo_erro = (empty($tipo_erro)) ? "''":"'$tipo_erro'";
	$motivo_erro = (empty($motivo_erro)) ? "''":"'$motivo_erro'";

	if(strlen($categoria)==0){
		$msg_erro = "Escolha a categoria";
	}

	if ($prioridade == 1 || $prioridade == 2) {
		$xprioridade = "'$prioridade'";
	} else {
		$xprioridade = ($prioridade=="t") ? "'t'" : "'f'";
	}

	$xcobrar      = ($cobrar=="t") ? "'t'" : "'f'";
	$xprazo_horas = (strlen($prazo_horas)>0) ? "$prazo_horas" : "null";
	$xtipo_chamado= (strlen($tipo_chamado)>0) ? "$tipo_chamado" :  "null";

	$arquivo = isset($_FILES["arquivo"]) ? $_FILES["arquivo"] : FALSE;

	$sql = "SELECT categoria , status, atendente FROM tbl_hd_chamado WHERE hd_chamado = $hd_chamado";
	$res = pg_query($con,$sql);
	$categoria_anterior = pg_fetch_result($res, 0, 'categoria');
	$status_anterior    = pg_fetch_result($res, 0, 'status');
	$atendente_anterior = pg_fetch_result($res, 0, 'atendente');

	if (strlen($comentario) < 3) $msg_erro = "Comentário muito pequeno";

	//$comentario = pg_escape_string($comentario);

	if (strlen($hora_desenvolvimento)==0){
		$hora_desenvolvimento = ' NULL ';
	}

	#-------- De Análise para Execução -------
	if (strlen ($sequencia) == 0 AND $status == "Análise" AND $status_anterior == "Análise") {
		$msg_erro = "Escolha a seqüência da tarefa. Ou continua em análise, ou vai para Execução.";
	}
	if ($sequencia == "SEGUE" AND $status_anterior == "Análise") $status = "Execução" ;

	if ($sequencia == "AGUARDANDO" AND $status_anterior == "Análise") $status = "Aguard.Execução" ;

	#-------- De Execução para Resolvido -------
	if (strlen ($sequencia) == 0 AND $status == "Execução" AND $status_anterior == "Execução") {
		$msg_erro = "Escolha a seqüência da tarefa. Ou continua em execução ou está resolvido.";
	}

	if ($sequencia == "SEGUE" AND $status_anterior == "Execução") $status = "Resolvido" ;
	if ($sequencia == "SEGUE" AND $status_anterior == "Aguard.Execução") $status = "Execução" ;

	// 20-01-2012 - MLG - A pedido do Rodrigo, chamado 'novo' não passa mais para 'Análise' de forma automática.
	//if ($status == "Novo" AND $status_anterior == "Novo") $status = "Análise";


	$sql = "SELECT exigir_resposta FROM tbl_hd_chamado WHERE hd_chamado=$hd_chamado";
	$res = pg_query($con,$sql);
	$xexigir_resposta = pg_fetch_result($res, 0, 0);

	if (strlen($xexigir_resposta)==0) $xexigir_resposta = 'f';

	$exigir_resposta = (strlen ($exigir_resposta) > 0) ? 't' : 'f';
	$xinterno = (strlen ($interno) > 0) ? 't' : 'f';

	if (strlen($msg_erro) == 0){
		$res = pg_begin();
		//EXECUTA A ROTINA PARA SETAR O TERMINO NO HD_CHAMADO_ITEM
		$sql =" UPDATE tbl_hd_chamado_item
				   SET termino = current_timestamp
                 WHERE hd_chamado_item in(
                       SELECT hd_chamado_item
                         FROM tbl_hd_chamado_item
                        WHERE hd_chamado = (
                            SELECT hd_chamado
                              FROM tbl_hd_chamado_atendente
                             WHERE admin = $login_admin AND data_termino IS NULL
                             LIMIT 1)
                          AND termino IS NULL
                        ORDER BY hd_chamado_item DESC
                        LIMIT 1
                )";
		$res = pg_query($con,$sql); 
		if($status == 'Resolvido'){
			$data_resolvido = " data_resolvido = current_timestamp ,";
		}

		$sql =" UPDATE tbl_hd_chamado
				   SET status = '$status' ,
					$data_resolvido
					atendente           = $transfere,
					admin_desenvolvedor = $admin_desenvolvedor,
					categoria           = '$categoria',
					prioridade          = $xprioridade,
					tipo_chamado        = $xtipo_chamado,
					admin_erro          = $admin_erro,
					tipo_erro           = $tipo_erro,
					motivo_erro         = $motivo_erro,
					cobrar              = $xcobrar 
					$campos_previsao_termino_interna
					$field_campos_adicionais  
					";

		if ($xexigir_resposta=='f') {
			$sql .= ", exigir_resposta = '$exigir_resposta'  ";
		}
		$sql .= " WHERE hd_chamado = $hd_chamado";
		$res = pg_query($con,$sql);

		if(strlen(trim($msg_interacao))>0){
			$msg_interacao .= "por <b>$login_nome_completo </b>";

			$sql_cl_pri = "INSERT INTO tbl_hd_chamado_item (hd_chamado, comentario, admin, interno) VALUES ({$hd_chamado}, '$msg_interacao', {$login_admin}, 'f')";
			$res_cl_pri = pg_query($con, $sql_cl_pri);
		}

		//#API2
		if(in_array($transfere, $atendenteSyncPipedrive)){
			$syncPipedrive = true;
		}

		if ($atendente_anterior <> $transfere) {
			$transferiu = "sim";
			$sql = "INSERT INTO tbl_hd_chamado_item (hd_chamado, comentario, admin, interno , admin_transferencia) VALUES ($hd_chamado, 'Chamado Transferido',$login_admin, 't', $transfere)";
			$res = pg_query($con,$sql);
			
			$sql = "UPDATE tbl_hd_chamado set atendente = $transfere WHERE hd_chamado = $hd_chamado";
			$res = pg_query($con,$sql);
		}

		if ($categoria <> $categoria_anterior) {
			$sql = "INSERT INTO tbl_hd_chamado_item (hd_chamado, comentario, admin, interno) VALUES ($hd_chamado, 'Categoria Alterada de $categoria_anterior para <b> $categoria </b>',$login_admin, 't')";
			$res = pg_query($con,$sql);
		}

		if ($status == "Correção" and in_array($grupo_admin_transferencia, array('2','4')) ) {
			$sql = "UPDATE tbl_hd_chamado
					set previsao_termino_interna = fn_prazo_termino(case when previsao_termino_interna notnull then (trunc(case when horas_analisadas/2 = 0 then 1 else horas_analisadas/2 end ))::integer else horas_analisadas::integer end, current_timestamp )
					from (select hd_chamado,horas_analisadas from tbl_backlog_item where hd_chamado = $hd_chamado order by backlog_item desc limit 1) x
					where tbl_hd_chamado.hd_chamado = x.hd_chamado ;";
			$res = pg_query($con,$sql);


		}

		if(in_array($grupo_admin_transferencia,array('2','4'))) {
			prioridade($login_admin);
		}

		if ($status == "Resolvido" AND $status_anterior == "Execução") {
			#$sql = "INSERT INTO tbl_hd_chamado_item (hd_chamado, comentario, admin) VALUES ($hd_chamado, 'Chamado resolvido. Se você não concordar com a solução basta inserir novo comentário para reabrir o chamado.',$login_admin)";
			//if($login_admin ==568)	echo "sql-9 $sql<br>";
			#$res = pg_query($con,$sql);
		}

		// HD 17195
		if($transferiu == "sim" and $status != "Cancelado" AND ($status_anterior == "Novo" OR $status_anterior == "Análise") ) {
			$sql="SELECT sv.email AS supervisor_email,
						 sv.nome_completo AS supervisor_nome,
						 sv.admin AS supervisor_admin,
						 admin.email,
						 admin.nome_completo,
						 tbl_hd_chamado.status,
						 TO_CHAR(previsao_termino,'DD/MM/YYYY') as previsao_termino,
						 titulo
					FROM tbl_hd_chamado
					JOIN tbl_admin sv on tbl_hd_chamado.fabrica=sv.fabrica
					JOIN tbl_admin admin on tbl_hd_chamado.admin= admin.admin
					WHERE sv.help_desk_supervisor IS TRUE
					AND   admin.admin                 <> 19
					AND   sv.email IS NOT NULL
					AND   previsao_termino IS NOT NULL
					AND   hd_chamado=$hd_chamado
					limit 1 ";
			$res = pg_query($con,$sql);
			if( pg_num_rows($res) > 0){
				for ($x = 0 ; $x <  pg_num_rows($res) ; $x++){
					$supervisor_email  = pg_fetch_result($res, $x, 'supervisor_email');
					$supervisor_nome   = pg_fetch_result($res, $x, 'supervisor_nome');
					$supervisor_admin  = pg_fetch_result($res, $x, 'supervisor_admin');
					$admin_email       = pg_fetch_result($res, $x, 'email');
					$admin_nome        = pg_fetch_result($res, $x, 'nome_completo');
					$status            = pg_fetch_result($res, $x, 'status');
					$previsao_termino  = pg_fetch_result($res, $x, 'previsao_termino');
					$titulo            = pg_fetch_result($res, $x, 'titulo');

					if(strlen($supervisor_email) > 0 and strlen($admin_email) >0 ){
						$chave1 = md5($hd_chamado);
						$chave2 = md5($supervisor_admin);
						$email_origem  = "suporte@telecontrol.com.br";
						$email_destino = $supervisor_email." ; ".$admin_email;
						if($sistema_lingua =='ES'){
							$assunto 	   = "La llamada $hd_chamado ha sido aprobada para el desarrollo y está en estado $status ";

							$body_top  = "MIME-Version: 1.0\n";
							//$body_top = "--Message-Boundary\n";
							$body_top .= "From: $email_origem\n";
							$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
							$body_top .= "Content-transfer-encoding: 8BIT\n";
							$body_top .= "Content-description: Mail message body\n\n";

							$corpo = "<P align=left><STRONG>Nota: este correo electrónico se genera automáticamente. **** POR FAVOR NO RESPONDA ESTE MENSAJE ****.</STRONG> </P>
							<P align=justify>Llamada&nbsp;<STRONG>$hd_chamado</STRONG> - <STRONG>$titulo</STRONG>&nbsp; </P>
							<P align=left>$nome,</P>
							<P align=justify>
							La predicción de la finalización de esta llamada es $previsao_termino.
							</P>
							<P align=justify>Haga clic en el enlace para acceder a su llamada:<br> <a href='http://posvenda.telecontrol.com.br/assist/index.php?id=$hd_chamado&key1=$chave1&id2=$adm&key2=$chave2&ajax=sim&acao=validar'><u><b>Haga clic aquí para ver su llamada</b></u></a>.</P>
							<P align=justify>Suporte Telecontrol Networking.<BR>suporte@telecontrol.com.br";
						}else{

							$assunto       = "O chamado n° $hd_chamado foi aprovado para desenvolvimento e está em estado $status ";

							$body_top  = "MIME-Version: 1.0\n";
							//$body_top = "--Message-Boundary\n";
							$body_top .= "From: $email_origem\n";
							$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
							$body_top .= "Content-transfer-encoding: 8BIT\n";
							$body_top .= "Content-description: Mail message body\n\n";

							$corpo = "<P align=left><STRONG>Nota: Este e-mail é gerado automaticamente. **** POR FAVOR NÃO RESPONDA ESTA MENSAGEM ****.</STRONG> </P>
							<P align=justify>Chamado&nbsp;<STRONG>$hd_chamado</STRONG> - <STRONG>$titulo</STRONG>&nbsp; </P>
							<P align=left>$admin_nome,</P>
							<P align=justify>
							Previsão do término deste chamado é $previsao_termino.
							</P>
							<P align=justify>Clique no link para acessar o seu chamado:<br> <a href='http://posvenda.telecontrol.com.br/assist/index.php?id=$hd_chamado&key1=$chave1&id2=$adm&key2=$chave2&ajax=sim&acao=validar'><u><b>Clique aqui para ver este chamado</b></u></a>.</P>
							<P align=justify>Suporte Telecontrol Networking.<BR>suporte@telecontrol.com.br </P>";
						}
						//if (@mail($email_destino, stripslashes(utf8_encode($assunto)), utf8_encode($corpo), $body_top)) {
						if ($mailer->sendMail($email_destino, stripslashes($assunto), $corpo, $email_origem)) {
							$msg = "<br>Foi enviado um email para: ".$email_destino."<br>";
						}else{
							$msg_erro = "Não foi possível enviar o email. ";
						}
					}
				}
			}
		}

		if($status== "Aprovação" AND ($status_anterior <> "Cancelado" OR $status_anterior <> "Resolvido")){

 			$res = pg_begin();
			$sql="SELECT hora_desenvolvimento,data_aprovacao
					FROM tbl_hd_chamado
					WHERE hd_chamado=$hd_chamado";
			$res = pg_query($con,$sql);
			if( pg_num_rows($res) == 0){
				$msg_erro="Suporte, para o supervisor aprovar a execução, terá que preencher a Hora de desenvolvimento";
			}else{
				$hora_desenvolvimento = pg_fetch_result($res, 0, 'hora_desenvolvimento');
				$data_aprovacao		  = pg_fetch_result($res, 0, 'data_aprovacao');

				if($hora_desenvolvimento == 0 or strlen($hora_desenvolvimento)==0){
					$msg_erro="Suporte, para o supervisor aprovar a execução, terá que preencher a Hora de desenvolvimento";
				}

				if(strlen($data_aprovacao) > 0){
					$sql2="UPDATE tbl_hd_chamado set data_aprovacao = null where hd_chamado=$hd_chamado";
					$res2=pg_query($con,$sql2);
					$msg_erro = pg_last_error($con);

					$sql3="SELECT to_char(current_date,'MM') as mes,to_char(current_date,'YYYY') as ano";
					$res3=pg_query($con,$sql3);
					$mes=pg_fetch_result($res3, 0, 'mes');
					$ano=pg_fetch_result($res3, 0, 'ano');

					$sql4=" UPDATE tbl_hd_franquia set
							hora_utilizada=(hora_utilizada-hora_desenvolvimento)
							FROM  tbl_hd_chamado
							WHERE tbl_hd_chamado.fabrica=tbl_hd_franquia.fabrica
							AND   hd_chamado=$hd_chamado
							AND   mes=$mes
							AND   ano=$ano
							AND   tbl_hd_franquia.periodo_fim is null";
					$res4 = pg_query($con,$sql4);
					$msg_erro = pg_last_error($con);

				}
			}
			if(strlen($msg_erro) ==0){
				$sql=" UPDATE tbl_hd_chamado SET
						data_envio_aprovacao=current_timestamp
						WHERE hd_chamado=$hd_chamado";
				$res = pg_query($con,$sql);

				$sql = "SELECT nome_completo,email,tbl_admin.admin
								FROM tbl_admin
								JOIN tbl_hd_chamado ON tbl_hd_chamado.fabrica = tbl_admin.fabrica
								WHERE tbl_hd_chamado.hd_chamado    = $hd_chamado
								AND tbl_admin.help_desk_supervisor IS TRUE
								AND tbl_admin.email IS NOT NULL
								AND tbl_admin.admin                 <> 19";
				$res = pg_query($con,$sql);
				if ( pg_num_rows($res) > 0) {
					$conta = ($login_fabrica==20) ? "3" :  pg_num_rows($res);
					for($i =0;$i<$conta;$i++) {

						$supervisor_email  = pg_fetch_result($res, $i, 'email');
						$supervisor_nome   = pg_fetch_result($res, $i, 'nome_completo');
						$supervisor_adm    = pg_fetch_result($res, $i, 'admin');

						$chave1 = md5($hd_chamado);
						$chave2 = md5($supervisor_adm);
						$email_origem  = "suporte@telecontrol.com.br";
						$email_destino = $supervisor_email;
						if($sistema_lingua == 'ES'){
							$assunto       = "La llamada $hd_chamado está esperando tu aprobación";

							$body_top  = "MIME-Version: 1.0\n";
							//$body_top = "--Message-Boundary\n";
							$body_top .= "From: $email_origem\n";
							$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
							$body_top .= "Content-transfer-encoding: 8BIT\n";
							$body_top .= "Content-description: Mail message body\n\n";

							$corpo = "<P align=left><STRONG>Nota: este correo electrónico se genera automáticamente. **** POR FAVOR NO RESPONDA ESTE MENSAJE ****.</STRONG> </P>
							<P align=justify>Llamada&nbsp;<STRONG>$hd_chamado</STRONG> - <STRONG>$titulo</STRONG>&nbsp; </P>
							<P align=left>$nome,</P>
							<P align=justify>
							Necesitamos su aprobación en los horarios de facturación para continuar respondiendo la llamada.
							</P>
							<P align=justify>Haga clic en el enlace para acceder a su llamada:<br> <a href='http://posvenda.telecontrol.com.br/assist/index.php?id=$hd_chamado&key1=$chave1&id2=$adm&key2=$chave2&ajax=sim&acao=validar'><u><b>Haga clic aquí para ver su llamada</b></u></a>.</P>
							<P align=justify>Suporte Telecontrol Networking.<BR>suporte@telecontrol.com.br";
						}else{
							$assunto       = "O chamado n° $hd_chamado está aguardando sua aprovação";

							$body_top  = "MIME-Version: 1.0\n";
							//$body_top = "--Message-Boundary\n";
							$body_top .= "From: $email_origem\n";
							$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
							$body_top .= "Content-transfer-encoding: 8BIT\n";
							$body_top .= "Content-description: Mail message body\n\n";

							$corpo = "<P align=left><STRONG>Nota: Este e-mail é gerado automaticamente. **** POR FAVOR NÃO RESPONDA ESTA MENSAGEM ****.</STRONG> </P>
							<P align=justify>Chamado&nbsp;<STRONG>$hd_chamado</STRONG> - <STRONG>$titulo</STRONG>&nbsp; </P>
							<P align=left>$nome,</P>
							<P align=justify>
							Precisamos de sua aprovação em faturamento de horas para continuarmos atendendo o chamado.
							</P>
							<P align=justify>Clique no link para acessar o seu chamado:<br> <a href='http://posvenda.telecontrol.com.br/assist/index.php?id=$hd_chamado&key1=$chave1&id2=$adm&key2=$chave2&ajax=sim&acao=validar'><u><b>Clique aqui para ver este chamado</b></u></a>.</P>
							<P align=justify>Suporte Telecontrol Networking.<BR>suporte@telecontrol.com.br </P>";
						}
						//if ( @mail($email_destino, stripslashes(utf8_encode($assunto)), utf8_encode($corpo), $body_top)) {
						if ($mailer->sendMail($email_destino, stripslashes($assunto), $corpo, $email_origem)) {
							$msg = "<br>Foi enviado um email para: ".$email_destino."<br>";
						}else{
							$msg_erro = "Não foi possível enviar o email. ";
						}
					}
				}
			}
 			if(strlen($msg_erro) > 0){
				$res = pg_rollBack();
			}else{
				$res = @pg_commit();
 			}
		}
		if(!$bloqueia_iteracao){

			if (strlen ($comentario) > 0) {

				$status_item  = ($importante =='t' and $xinterno == 't') ? 'Importante' : $status; 

				$sql ="INSERT INTO tbl_hd_chamado_item (
									hd_chamado,
									comentario,
									admin,
									status_item,
									interno
								) VALUES (
									$hd_chamado,
									'$comentario',
									$login_admin,
									'$status_item',
									'$xinterno'
								);";
					$res = pg_query($con,$sql);
					$msg_erro = pg_last_error($con);


				$res = @pg_query($con,"SELECT CURRVAL ('seq_hd_chamado_item')");
				$hd_chamado_item  = pg_fetch_result($res, 0, 0);

				if (strlen($msg_erro) == 0 and strlen($hd_chamado_item) > 0) {
					$att_max_size = 30 * 1024 * 1024;       // Tamanho máximo do arquivo (em bytes)

					if (isset($_FILES)) {

						/**
						 * White list de arquivos que podem ser adicionados
						 */
						$white_list = array(
						/* Imagens */
							'image/bmp',
							'image/gif',
							'image/x-icon',
							'image/jpeg',
							'image/pjpeg',
							'image/png',
							'image/x-png',
							'image/tiff',
							'image/vnd.adobe.photoshop',
						/* Texto */
							'text/comma-separated-values',
							'text/csv',
							'application/vnd.ms-excel',
							'application/postscript',
							'application/pdf',
							'application/postscript',
							'text/rtf',
							'text/tab-separated-values',
							'text/tsv;application/vnd.ms-excel',
							'text/plain',
						/* Office */
							'application/msword',
							'application/vnd.ms-powerpoint',
							'application/vnd.ms-excel',
							'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
							'application/vnd.openxmlformats-officedocument.presentationml.presentation',
							'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
						/* Star/OpenOffice.org */
							'application/vnd.oasis.opendocument.text',
							'application/x-vnd.oasis.opendocument.text',
							'application/vnd.oasis.opendocument.spreadsheet',
							'application/x-vnd.oasis.opendocument.spreadsheet',
							'application/vnd.oasis.opendocument.presentation',
							'application/x-vnd.oasis.opendocument.presentation',
						/* Compactadores */
							'application/x-stuffit',
							'application/mac-binhex40',
							'application/octet-stream',
							'application/octet-stream',
							'application/octet-stream',
							'application/octet-stream;application/x-rar-compressed',
							'application/x-compressed',
							'application/x-zip-compressed',
							'application/zip',
							'application/x-gzip',
						);

						$arrayExt = [
										'bmp',
										'gif',
										'x-icon',
										'jpeg',
										'pjpeg',
										'png',
										'jpg',
										'gif',
										'x-png',
										'tiff',
										'photoshop',
										'comma-separated-values',
										'csv',
										'ms-excel',
										'postscript',
										'pdf',
										'postscript',
										'rtf',
										'tab-separated-values',
										'plain',
										'msword',
										'ms-powerpoint',
										'document',
										'presentation',
										'sheet',
										'text',
										'xls',
										'xlsx',
										'xml',
										'ppt',
										'pps',
										'txt',
										'doc',
										'docx',
										'rar',
										'spreadsheet',
										'x-stuffit',
										'mac-binhex40',
										'octet-stream',
										'x-rar-compressed',
										'x-compressed',
										'x-zip-compressed',
										'zip',
										'x-gzip',
										'psd',
										'cdr',
										'ai',
										'xml',
										'svg',
										'tif',
										'fla',
										'swf',
										'dwg',
									];

						$arquivos = array( array( ) );
						foreach(  $_FILES as $key=>$info ) {

							foreach( $info as $key=>$dados ) {
								for( $i = 0; $i < sizeof( $dados ); $i++ ) {
								    // Aqui, transformamos o array $_FILES de:
								    // $_FILES["arquivo"]["name"][0]
								    // $_FILES["arquivo"]["name"][1]
								    // $_FILES["arquivo"]["name"][2]
								    // $_FILES["arquivo"]["name"][3]
								    // para
								    // $arquivo[0]["name"]
								    // $arquivo[1]["name"]
								    // $arquivo[2]["name"]
								    // $arquivo[3]["name"]
								    // Dessa forma, fica mais facil trabalharmos o array depois, para salvar o arquivo
								    $arquivos[$i][$key] = $info[$key][$i];
								}
							}

						}

						$i = 0;
						// Fazemos o upload normalmente, igual no exemplo anterior
						foreach( $arquivos as $file ) {

							// Verificar se o campo do arquivo foi preenchido
							if( $file['name'] != '' ) {

								$path = $file['name'];
                				$extPath = pathinfo($path, PATHINFO_EXTENSION);

								if (!in_array($file['type'], $white_list) && !in_array(strtolower($extPath), $arrayExt)) {
									$msg_erro = "O tipo do arquivo '".$file['name']."' não pode ser enviado. Formato inválido <br>";
								}

								if ($file["size"] > $att_max_size){

									$msg_erro .= "Arquivo ".$file['name']." tem tamanho muito grande! Deve ser de no máximo 5MB. Envie outro arquivo.";

								}

								if ( empty($msg_erro) ) {
									//  Substituir tudo q não for caracteres aceitos para nome de arquivo para '_'
									$file_name = preg_replace("/[^a-zA-Z0-9_-]/", '.', tira_acentos($file['name']));

									// Gera um nome único para a imagem
									$pathToSave = strtolower(dirname(__FILE__) . "/documentos/hd-$hd_chamado-itens");
								
									if (!is_dir($pathToSave)) {
										system("mkdir -m 777 $pathToSave");
									}

									$arquivo = $pathToSave . "/$hd_chamado_item-$file_name";

									if( !move_uploaded_file( $file['tmp_name'], $arquivo ) ) {

										$msg_erro = 'Erro no upload do arquivo "'.$file['name'].'" ';

									}

								}

							}

							$i++;

						}

					}

				}//fim de todo o upload

				//--======================================================================
				if (strlen($msg_erro) == 0) {
					$sql = "SELECT hd_chamado_atendente,
									hd_chamado
									FROM tbl_hd_chamado_atendente
									WHERE admin = $login_admin
									AND   data_termino IS NULL
									ORDER BY hd_chamado_atendente DESC LIMIT 1";
					$res = pg_query($con,$sql);
					$msg_erro = pg_last_error($con);
					if ( pg_num_rows($res) > 0) {
						$hd_chamado_atendente = pg_fetch_result($res, 0, 'hd_chamado_atendente');
						$hd_chamado_atual     = pg_fetch_result($res, 0, 'hd_chamado');
					}

					if(($hd_chamado_atual <> $hd_chamado) or $transferiu == "sim"){
						//se eu tiver interagindo em outro chamado ou transferindo

						//fecho o chamado_item
						$sql =" UPDATE tbl_hd_chamado_item
								SET termino = current_timestamp
								WHERE hd_chamado_item in(SELECT hd_chamado_item
											 FROM tbl_hd_chamado_item
											 WHERE hd_chamado = (select hd_chamado from tbl_hd_chamado_atendente where admin = $login_admin and data_termino is null limit 1)
												AND termino IS NULL
											 ORDER BY hd_chamado_item desc
											 LIMIT 1 );";
						$res = pg_query($con,$sql);
						$msg_erro = pg_last_error($con);

						if(strlen($hd_chamado_atendente)>0){
							$sql = "UPDATE tbl_hd_chamado_atendente
											SET data_termino = CURRENT_TIMESTAMP
											WHERE hd_chamado_atendente = $hd_chamado_atendente
											AND   admin               =  $login_admin
											AND   data_termino IS NULL
											";
							$res = pg_query($con,$sql);
							$msg_erro = pg_last_error($con);
						}
					}
					/*IGOR - 12/08/2008 - SE FOR SUPORTE, NÃO CONTA TEMPO DE ANALISE NO CHAMADO SE NÃO FOR Execução*/
					if($login_admin == 435 and 1==2){
						$sql =" UPDATE tbl_hd_chamado_item
								SET termino = current_timestamp
								WHERE hd_chamado_item in(SELECT hd_chamado_item
											 FROM tbl_hd_chamado_item
											 WHERE hd_chamado = (select hd_chamado from tbl_hd_chamado_atendente where admin = $login_admin and data_termino is null limit 1)
												AND termino IS NULL
											 ORDER BY hd_chamado_item desc
											 LIMIT 1 );";

						$res = pg_query($con,$sql);
						$msg_erro = pg_last_error($con);

						//fecha o atendimento se tiver algum aberto
						$sql = "UPDATE tbl_hd_chamado_atendente
										SET data_termino = CURRENT_TIMESTAMP
										WHERE hd_chamado_atendente = (
																		SELECT hd_chamado_atendente
																		FROM tbl_hd_chamado_atendente
																		WHERE admin = 435
																		AND   data_termino IS NULL
																		ORDER BY hd_chamado_atendente DESC LIMIT 1
																	)
										AND   admin               =  $login_admin
										AND   data_termino IS NULL
										";
						$res = pg_query($con,$sql);
						$msg_erro = pg_last_error($con);
					}


					if($hd_chamado_atual <> $hd_chamado){ // se tiver interagindo em outro chamado eu insiro um novo
							$sql = "INSERT INTO tbl_hd_chamado_atendente(
															hd_chamado ,
															admin      ,
															data_inicio,
															hd_chamado_item
													)VALUES(
													$hd_chamado       ,
													$login_admin      ,
													CURRENT_TIMESTAMP,
													(select hd_chamado_item from tbl_hd_chamado_item where hd_chamado = $hd_chamado order by 1 desc limit 1) 
													)";
							$res = pg_query($con,$sql);
							$msg_erro = pg_last_error($con);
							$sql="SELECT CURRVAL('seq_hd_chamado_atendente');";
							$res = pg_query($con,$sql);
							$hd_chamado_atendente =  pg_fetch_result($res, 0, 0);
					}
					if($status == 'Resolvido'){
						//EXECUTA A ROTINA PARA SETAR O TERMINO NO HD_CHAMADO_ITEM
						$sql =" UPDATE tbl_hd_chamado_item
								SET termino = current_timestamp
								WHERE hd_chamado_item in(SELECT hd_chamado_item
											 FROM tbl_hd_chamado_item
											 WHERE hd_chamado = (select hd_chamado from tbl_hd_chamado_atendente where admin = $login_admin and data_termino is null limit 1)
												AND termino IS NULL
											 ORDER BY hd_chamado_item desc
											 LIMIT 1 );";
						$res = pg_query($con,$sql);
						$msg_erro = pg_last_error($con);

						$sql = "UPDATE tbl_hd_chamado_atendente
							SET data_termino = CURRENT_TIMESTAMP
							WHERE admin                = $login_admin
							AND   hd_chamado           = $hd_chamado
							and   data_termino isnull
							AND   hd_chamado_atendente = $hd_chamado_atendente";
						$res = pg_query($con,$sql);
						$msg_erro = pg_last_error($con);

						$sql= "UPDATE tbl_controle_acesso_arquivo SET
							data_fim = CURRENT_DATE,
							hora_fim = CURRENT_TIME,
							status   = 'finalizado'
							WHERE hd_chamado = $hd_chamado";
						$res = pg_query($con,$sql);
						$msg_erro = pg_last_error($con);
					}
				}

			}
		}

		$msg_erro = str_ireplace('ERROR: ', '', $msg_erro);
		if(strlen($msg_erro) > 0){
			$res = pg_rollBack();
			$msg_erro.= ($hd_chamado_item) ? ' Não foi possível gravar sua interação.' : ' Não foi possível Inserir o Chamado.';
		}
		else {

			if ($_serverEnvironment == 'production') {
				$painel->setChamadoAtendente($hd_chamado,'',$atendente_anterior);
			}

			$res = @pg_commit();

			if ($status == "Correção") {

				$sqlVerificaCorrecoes = "SELECT COUNT(*) as total_correcoes
										 FROM tbl_hd_chamado_item
										 WHERE hd_chamado = {$hd_chamado}
										 AND tbl_hd_chamado_item.status_item = 'Correção'";
				$resVerificaCorrecoes = pg_query($con, $sqlVerificaCorrecoes);

				$totalCorrecoes = pg_fetch_result($resVerificaCorrecoes, 0, 'total_correcoes');

				if ($totalCorrecoes > 1) {

					$sqlDChamado = "SELECT nome_completo,
										   titulo,
										   tbl_hd_chamado.fabrica,
										   tbl_hd_chamado.horas_desenvolvimento,
										   (
										   	SELECT EXTRACT(epoch FROM SUM(data_termino - data_inicio)) / 3600
										   	FROM tbl_hd_chamado_atendente
										   	WHERE tbl_hd_chamado_atendente.hd_chamado = tbl_hd_chamado.hd_chamado
										   	AND tbl_hd_chamado_atendente.admin = tbl_hd_chamado.atendente
										   ) as horas_trab
									FROM tbl_hd_chamado
									JOIN tbl_admin ON tbl_hd_chamado.atendente = tbl_admin.admin
									WHERE tbl_hd_chamado.hd_chamado = {$hd_chamado}";
					$resDchamado = pg_query($con, $sqlDChamado);

					$nomeAtendenteAtual = pg_fetch_result($resDchamado, 0, "nome_completo");
					$tituloHd = pg_fetch_result($resDchamado, 0, "titulo");
					$fabResp = pg_fetch_result($resDchamado, 0, "fabrica");
					$hDev    = pg_fetch_result($resDchamado, 0, 'horas_desenvolvimento');
					$hTrab   = number_format(pg_fetch_result($resDchamado, 0, 'horas_trab'), 2);

					$mensagemTelegram = utf8_encode("------------------(Correção)----------------------------\nChamado: {$hd_chamado} - {$tituloHd}\nFábrica:      {$fabResp}\nHoras Dev.:  {$hDev}h\nAtendente:   {$nomeAtendenteAtual}\nHoras Trab.:  {$hTrab}h\nStatus:            Correção\nCorreções:     {$totalCorrecoes}\n---------------------------------------------------------------");

					sendMessage($mensagemTelegram);

				}

			}

			if($syncPipedrive==true){
				syncPipedrive($hd_chamado);
			}
			if($status == 'Resolvido' OR $exigir_resposta == 't'){
				$query_select_status = "SELECT ts.status_chamado, ts.data_input FROM tbl_status_chamado ts 
				WHERE ts.hd_chamado = {$hd_chamado} ORDER BY ts.data_input DESC limit 1;";
				$result = pg_query($con, $query_select_status);

				if (pg_numrows($result) > 0) {
					$status_chamado = pg_fetch_result($result, 0, 'status_chamado');
					$data_entrega = date('Y-m-d H:i:s');

					$query_update_status = "UPDATE tbl_status_chamado SET data_entrega = '{$data_entrega}' WHERE status_chamado = {$status_chamado};";
					pg_query($con, $query_update_status);
				}
				$sql="SELECT nome_completo,email,tbl_admin.admin, tbl_hd_chamado.fabrica, titulo
							FROM tbl_admin
							JOIN tbl_hd_chamado ON tbl_hd_chamado.admin = tbl_admin.admin
							WHERE hd_chamado = $hd_chamado";
				$res = pg_query($con,$sql);
				$email	= pg_fetch_result($res, 0, 'email');
				$nome	= pg_fetch_result($res, 0, 'nome_completo');
				$adm	= pg_fetch_result($res, 0, 'admin');
				$fabrica= pg_fetch_result($res, 0, 'fabrica');
				$titulo = pg_fetch_result($res, 0, 'titulo');

				$chave1	= md5($hd_chamado);
				$chave2 = md5($adm);
				$email_origem  = "suporte@telecontrol.com.br";
				$email_destino = $email;
				if($sistema_lingua == 'ES'){
					$assunto   = "Su llamada nº $hd_chamado ha sido resuelta";
					$body_top  = "MIME-Version: 1.0\n";
					//$body_top = "--Message-Boundary\n";
					$body_top .= "From: $email_origem\n";
					$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
					$body_top .= "Content-transfer-encoding: 8BIT\n";
					$body_top .= "Content-description: Mail message body\n\n";

					$corpo ="<P align=left><STRONG>Nota: este correo electrónico se genera automáticamente. **** POR FAVOR NO RESPONDA ESTE MENSAJE ****.</STRONG> </P>
							 <P align=justify>Llamada&nbsp;<STRONG>$hd_chamado</STRONG> - <STRONG>$titulo</STRONG>&nbsp; </P>
							 <P align=left>$nome,</P>
							 <P align=justify>Su llamada ha sido&nbsp;<FONT color=#006600><STRONG>resuelta</STRONG></FONT> por el soporte de Telecontrol. Si tiene un problema, póngase en contacto a través de CHAT o interactúe en la llamada.</P>
							 <P align=left>Recuerde: ¡No es necesario hacer un comentario de agradecimiento, ya que el sistema comprenderá que la llamada no se ha resuelto!</P>
							 <P align=justify>Haga clic en el enlace para acceder a su llamada:<br> <a href='http://posvenda.telecontrol.com.br/assist/index.php?id=$hd_chamado&key1=$chave1&id2=$adm&key2=$chave2&ajax=sim&acao=validar'><u><b>Haga clic aquí para ver su llamada</b></u></a>.</P>
							<P align=justify>Suporte Telecontrol Networking.<BR>suporte@telecontrol.com.br";
				}else{
					$assunto       = "Seu chamado n° $hd_chamado foi RESOLVIDO";

					$body_top  = "MIME-Version: 1.0\n";
					//$body_top = "--Message-Boundary\n";
					$body_top .= "From: $email_origem\n";
					$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
					$body_top .= "Content-transfer-encoding: 8BIT\n";
					$body_top .= "Content-description: Mail message body\n\n";

					$corpo ="<P align=left><STRONG>Nota: Este e-mail é gerado automaticamente. **** POR FAVOR NÃO RESPONDA ESTA MENSAGEM ****.</STRONG> </P>
							<P align=justify>Chamado&nbsp;<STRONG>$hd_chamado</STRONG> - <STRONG>$titulo</STRONG>&nbsp; </P>
							<P align=left>$nome,</P>
							<P align=justify>Seu chamado foi&nbsp;<FONT color=#006600><STRONG>resolvido</STRONG></FONT> pelo suporte Telecontrol. Caso esteja com algum problema, entrar em contato via CHAT ou interagir no chamado.</P>
							<P align=justify>Lembre-se: Não precisa fazer comentário de agradecimento, pois o sistema vai entender que o chamado foi mal resolvido!</P>
							<P align=justify>Clique no link para acessar o seu chamado:<br> <a href='http://posvenda.telecontrol.com.br/assist/index.php?id=$hd_chamado&key1=$chave1&id2=$adm&key2=$chave2&ajax=sim&acao=validar'><u><b>Clique aqui para ver seu chamado</b></u></a>.</P>
							<P align=justify>Suporte Telecontrol Networking.<BR>suporte@telecontrol.com.br
							</P>";
					}
				if($exigir_resposta=='t' and $status<>'Resolvido' ){
					if($sistema_lingua == 'ES'){
						$assunto = "Su llamada nº $hd_chamado está esperando tu respuesta";

						$corpo = "<P align=left><STRONG>Nota: este correo electrónico se genera automáticamente. **** POR FAVOR NO RESPONDA ESTE MENSAJE ****.</STRONG> </P>
							 	  <P align=justify>Llamada&nbsp;<STRONG>$hd_chamado</STRONG> - <STRONG>$titulo</STRONG>&nbsp; </P>
							 	  <P align=left>$nome,</P>
							 	  <P align=justify>
							 	  Necesitamos su posición para continuar respondiendo la llamada.
							 	  </P>
							 	  <P align=justify>Haga clic en el enlace para acceder a su llamada:<br> <a href='http://posvenda.telecontrol.com.br/assist/index.php?id=$hd_chamado&key1=$chave1&id2=$adm&key2=$chave2&ajax=sim&acao=validar'><u><b>Haga clic aquí para ver su llamada</b></u></a>.</P>
								  <P align=justify>Suporte Telecontrol Networking.<BR>suporte@telecontrol.com.br";
					}else{

					$assunto = "Seu chamado n° $hd_chamado está aguardando sua resposta";

					$corpo   = "<P align=left><STRONG>Nota: Este e-mail é gerado automaticamente. **** POR FAVOR NÃO RESPONDA ESTA MENSAGEM ****.</STRONG> </P>
							<P align=justify>Chamado&nbsp;<STRONG>$hd_chamado</STRONG> - <STRONG>$titulo</STRONG>&nbsp; </P>
							<P align=left>$nome,</P>

							<P align=justify>
							Precisamos de sua posição para continuarmos atendendo o chamado.
							</P>
							<P align=justify>Clique no link para acessar o seu chamado:<br> <a href='http://posvenda.telecontrol.com.br/assist/index.php?id=$hd_chamado&key1=$chave1&id2=$adm&key2=$chave2&ajax=sim&acao=validar'><u><b>Clique aqui para ver seu chamado</b></u></a>.</P>
							<P align=justify>Suporte Telecontrol Networking.<BR>suporte@telecontrol.com.br
							</P>";
						}
				}

				//if ( @mail($email_destino, stripslashes(utf8_encode($assunto)), utf8_encode($corpo), $body_top) ){
				if ($mailer->sendMail($email_destino, stripslashes($assunto), $corpo, $email_origem)) {
					$msg = "<br>Foi enviado um email para: ".$email_destino."<br>";
				}else{
					$msg_erro = "Não foi possível enviar o email. Por favor entre em contato com a TELECONTROL.";
				}

				#HD 16226
				if($exigir_resposta=='t' and $status<>'Resolvido' AND $xinterno=='f' and $fabrica==3){
					$sql = "SELECT nome_completo,email,tbl_admin.admin
							FROM tbl_admin
							JOIN tbl_hd_chamado ON tbl_hd_chamado.fabrica = tbl_admin.fabrica
							WHERE tbl_hd_chamado.hd_chamado    = $hd_chamado
							AND tbl_admin.help_desk_supervisor IS TRUE
							AND tbl_admin.admin                 <> 19";
					$res = pg_query($con,$sql);
					if ( pg_num_rows($res) > 0) {
						$surpevisor_email  = pg_fetch_result($res, 0, 'email');
						$surpevisor_nome   = pg_fetch_result($res, 0, 'nome_completo');
						$surpevisor_adm    = pg_fetch_result($res, 0, 'admin');

						$chave1 = md5($hd_chamado);
						$chave2 = md5($surpevisor_adm);
						$email_origem  = "suporte@telecontrol.com.br";
						$email_destino = $surpevisor_email;
						$assunto       = "O chamado n° $hd_chamado está aguardando resposta";
						$body_top  = "MIME-Version: 1.0\n";
						//$body_top = "--Message-Boundary\n";
						$body_top .= "From: $email_origem\n";
						$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
						$body_top .= "Content-transfer-encoding: 8BIT\n";
						$body_top .= "Content-description: Mail message body\n\n";

						$corpo = "<P align=left><STRONG>Nota: Este e-mail é gerado automaticamente. **** POR FAVOR NÃO RESPONDA ESTA MENSAGEM ****.</STRONG> </P>
								<P align=justify>Chamado&nbsp;<STRONG>$hd_chamado</STRONG> -
								<STRONG>$titulo</STRONG>&nbsp; </P>
								<P align=left>$nome,</P>
								<P align=justify>Estamos aguardando a posição do(a) $nome para continuarmos atendendo o chamado.</P>
								<p>O seguinte comentário foi inserido no chamado: <br><i>$comentario</i></p>
								<P align=justify>Suporte Telecontrol Networking.<BR>suporte@telecontrol.com.br
								</P>";

						//<P align=justify>Clique no link para acessar o seu chamado:<br> <a href='http://posvenda.telecontrol.com.br/assist/index.php?id=$hd_chamado&key1=$chave1&id2=$surpevisor_adm&key2=$chave2&ajax=sim&acao=validar'><u><b>Clique aqui para ver o chamado</b></u></a>.</P>

						//if ( @mail($email_destino, stripslashes(utf8_encode($assunto)), utf8_encode($corpo), $body_top)) {
						if ($mailer->sendMail($email_destino, stripslashes($assunto), $corpo, $email_origem)) {
							$msg = "<br>Foi enviado um email para: ".$email_destino."<br>";
						}
					}
				}
			}
			if($status =='Resolvido'){
				$sql = "
				SELECT
				hd_chamado_melhoria

				FROM
				tbl_hd_chamado_melhoria

				WHERE
				hd_chamado=$hd_chamado
				";
				$res = pg_query($con, $sql);

				if (pg_num_rows($res)) {
					//A variável abaixo armazena qual o admin responsável por gerenciar as Melhorias
					//em Programas, normalmente o Tester, ele receberá e-mails de notificações
					$admin_responsavel_melhorias = 2310;

					$sql = "
					SELECT
					email

					FROM
					tbl_admin

					WHERE
					admin=$admin_responsavel_melhorias
					";
					$res = pg_query($con, $sql);
					$email = pg_fetch_result($res, 0, 'email');

					$mensagem = "O chamado $hd_chamado possui melhorias associadas a ele e foi Resolvido nesta data.<br>
					Por favor, acessar o sistema de melhorias em programas para validar.<br>
					<br>
					Suporte Telecontrol";

					$headers .= "MIME-Version: 1.0\n";
					$headers .= "Content-type: text/html; charset=iso-8859-1\n";
					$headers .= "To: $email" . "\r\n";
					$headers .= "From: Telecontrol Melhorias <suporte@telecontrol.com.br>";// . "\r\n";

					$titulo = "Melhorias: Chamado $hd_chamado Resolvido";

					$mailer->sendMail($to, $titulo, $mensagem, 'suporte@telecontrol.com.br');
				}
			}else{
				header ("Location: $PHP_SELF?hd_chamado=$hd_chamado");
			}
		}
	}
}

if($_POST["btn_transferir"]){

	$status 				= $_POST["status_transferir"];
	$transfere 				= $_POST["transfere"];
	$transfere_anterior 	= $_POST["transfere_anterior"];

	if(strlen(trim($status))==0){
		$msg_erro .= "Informar o status para transferência <br>";
	}

	if($transfere_anterior == $transfere){
		$msg_erro .= "O suporte não pode ser o mesmo para transferência <br>";
	}



	if(strlen(trim($msg_erro))==0){

		$sql =" UPDATE tbl_hd_chamado
				SET status = '$status' ,
					atendente = $transfere
				WHERE hd_chamado = $hd_chamado";
		$res = pg_query($con,$sql);


		//#API2
		if(in_array($transfere, $atendenteSyncPipedrive)){
			$syncPipedrive = true;
		}


		$sqlPegaAtendente = "SELECT nome_completo FROM tbl_admin WHERE admin = $transfere";
		$resPegaAtendente = pg_query($con, $sqlPegaAtendente);

		if(pg_num_rows($resPegaAtendente)>0){
			$nome_completo = pg_fetch_result($resPegaAtendente, 0, nome_completo);
		}

		$texto = '<p> Transferindo para ' .
			$nome_completo .
			' para ' .
			strtolower($status) .
			'. </p>';


			$sql2 ="INSERT INTO tbl_hd_chamado_item (
				hd_chamado,
				comentario,
				admin,
				status_item,
				interno
			) VALUES (
				$hd_chamado,
				'$texto',
				$login_admin,
				'$status',
				't'
			);";
			$res2 = pg_query($con,$sql2);

			if($syncPipedrive==true){
				syncPipedrive($hd_chamado);
			}

			if(strlen(trim(pg_last_error($con))) == 0) {
				header ("Location: $PHP_SELF?hd_chamado=$hd_chamado");
			}
	}


}

if (strlen($hd_chamado) > 0) {
	
	$sql = "UPDATE tbl_hd_chamado SET atendente = $login_admin WHERE hd_chamado = $hd_chamado AND atendente IS NULL";
	$res = pg_query($con,$sql);

	$sql= "
		SELECT
			   tbl_hd_chamado.hd_chamado                       ,
			   tbl_hd_chamado.comentario_efetivacao            ,
			   tbl_hd_chamado.admin                            ,
			   TO_CHAR(data, 'DD/MM/YYYY HH24:MI') AS data     ,
			   tbl_hd_chamado.titulo                           ,
			   tbl_hd_chamado.categoria                        ,
			   tbl_hd_chamado.status                           ,
			   tbl_hd_chamado.duracao                          ,
			   tbl_hd_chamado.admin_desenvolvedor              ,
			   tbl_hd_chamado.atendente                        ,
			   tbl_hd_chamado.fabrica_responsavel              ,
			   tbl_hd_chamado.fabrica                          ,
			   tbl_hd_chamado.prioridade                       ,
			   tbl_hd_chamado.prazo_horas                      ,
			   tbl_hd_chamado.horas_suporte                    ,
			   tbl_hd_chamado.horas_suporte_telefone           ,
			   tbl_hd_chamado.horas_analise                    ,
			   tbl_hd_chamado.taxa_abertura                    ,
			   tbl_hd_chamado.horas_teste                      ,
			   tbl_hd_chamado.horas_efetivacao                 ,
			   tbl_hd_chamado.cobrar                           ,
			   tbl_hd_chamado.tipo_chamado                     ,
			   tbl_hd_chamado.analise                          ,
			   tbl_hd_chamado.plano_teste                      ,
			   tbl_hd_chamado.procedimento_teste               ,
			   tbl_hd_chamado.procedimento_efetivacao          ,
			   tbl_hd_chamado.validacao                        ,
			   tbl_hd_chamado.comentario_desenvolvedor         ,
			   tbl_hd_chamado.admin_desenvolvedor              ,
			   tbl_hd_chamado.hora_desenvolvimento             ,
			   tbl_hd_chamado.admin_erro                       ,
			   tbl_hd_chamado.tipo_erro                        ,
			   tbl_hd_chamado.motivo_erro                      ,
			   tbl_hd_chamado.login_admin                      ,
			   to_char (previsao_termino        , 'DD/MM/YYYY HH24:MI') AS previsao_termino        ,
			   to_char (previsao_termino_interna, 'DD/MM/YYYY HH24:MI') AS previsao_termino_interna,
			   tbl_fabrica.nome   AS fabrica_nome              ,
			   tbl_admin.login                                 ,
			   tbl_admin.nome_completo                         ,
			   tbl_admin.fone                                  ,
			   tbl_admin.email                                 ,
			   tbl_admin.grupo_admin                           ,
			   tbl_admin.parametros_adicionais AS admin_adicionais,
			   atend.nome_completo AS atendente_nome           ,
			   tbl_hd_chamado.horas_franq_ter                  ,
			   tbl_hd_chamado.horas_fat_ter					   ,
			   tbl_hd_chamado.campos_adicionais				   ,
			   tbl_backlog_item.horas_analisadas			   ,
			   tbl_backlog_item.horas_utilizadas,
			   tbl_hd_chamado.valor_desconto,
			   tbl_hd_chamado.campos_adicionais ,
               (select count(1) from tbl_hd_chamado_item where hd_chamado = $hd_chamado and status_item ='Previsao Cadastrada') as previsao_cadastrada,
                (SELECT nome_completo FROM tbl_admin WHERE admin = tbl_backlog_item.suporte ) AS suporte_nome,
                (SELECT nome_completo 
                 FROM tbl_hd_chamado_analise 
                 LEFT JOIN tbl_admin 
                 	ON tbl_admin.admin = tbl_hd_chamado_analise.admin
				 WHERE hd_chamado = $hd_chamado 
				 ORDER BY hd_chamado_analise DESC 
				 LIMIT 1) AS analista_chamado_nome
			FROM tbl_hd_chamado
			JOIN tbl_admin            ON tbl_admin.admin          = tbl_hd_chamado.admin
			JOIN tbl_fabrica          ON tbl_fabrica.fabrica      = tbl_hd_chamado.fabrica
			LEFT JOIN tbl_backlog_item on tbl_backlog_item.hd_chamado = $hd_chamado
			LEFT JOIN tbl_admin atend ON tbl_hd_chamado.atendente = atend.admin
			WHERE tbl_hd_chamado.hd_chamado = $hd_chamado";
	$res = pg_query($con,$sql);

	if ( pg_num_rows($res) > 0) {
		$comentario_efetivacao    = pg_fetch_result($res, 0, 'comentario_efetivacao');
		$admin                    = pg_fetch_result($res, 0, 'admin');
		$data                     = pg_fetch_result($res, 0, 'data');
		$titulo                   = pg_fetch_result($res, 0, 'titulo');
		$categoria                = pg_fetch_result($res, 0, 'categoria');
		$status                   = pg_fetch_result($res, 0, 'status');
		$admin_desenvolvedor      = pg_fetch_result($res, 0, 'admin_desenvolvedor');
		$atendente                = pg_fetch_result($res, 0, 'atendente');
		$atendente_nome           = pg_fetch_result($res, 0, 'atendente_nome');
        $suporte_nome             = pg_fetch_result($res, 0, 'suporte_nome');
        $analista_chamado_nome    = pg_fetch_result($res, 0, 'analista_chamado_nome');
		$fabrica_responsavel      = pg_fetch_result($res, 0, 'fabrica_responsavel');
		$fabrica                  = pg_fetch_result($res, 0, 'fabrica');
		$nome                     = pg_fetch_result($res, 0, 'nome_completo');
		$email                    = pg_fetch_result($res, 0, 'email');
		$prioridade               = pg_fetch_result($res, 0, 'prioridade');
		$fone                     = pg_fetch_result($res, 0, 'fone');
		$plano_teste              = pg_fetch_result($res, 0, 'plano_teste');
		$analiseTexto             = pg_fetch_result($res, 0, 'analise');
		$procedimento_teste       = pg_fetch_result($res, 0, 'procedimento_teste');
		$procedimento_efetivacao  = pg_fetch_result($res, 0, 'procedimento_efetivacao');
		$validacao                = pg_fetch_result($res, 0, 'validacao');
		$comentario_desenvolvedor = pg_fetch_result($res, 0, 'comentario_desenvolvedor');
		$nome_completo            = pg_fetch_result($res, 0, 'nome_completo');
		$fabrica_nome             = pg_fetch_result($res, 0, 'fabrica_nome');
		$login                    = pg_fetch_result($res, 0, 'login');
		$prazo_horas              = pg_fetch_result($res, 0, 'prazo_horas');
		$horas_suporte            = pg_fetch_result($res, 0, 'horas_suporte');
		$horas_telefone           = pg_fetch_result($res, 0, 'horas_suporte_telefone');
		$horas_analise            = pg_fetch_result($res, 0, 'horas_analise');
		$taxa_abertura            = pg_fetch_result($res, 0, 'taxa_abertura');
		$horas_teste              = pg_fetch_result($res, 0, 'horas_teste');
		$horas_efetivacao         = pg_fetch_result($res, 0, 'horas_efetivacao');
		$previsao_termino         = pg_fetch_result($res, 0, 'previsao_termino');
		$previsao_termino_interna = pg_fetch_result($res, 0, 'previsao_termino_interna');
		$previsao_cadastrada      = pg_fetch_result($res, 0, 'previsao_cadastrada');
		$hora_desenvolvimento     = pg_fetch_result($res, 0, 'hora_desenvolvimento');
		$cobrar                   = pg_fetch_result($res, 0, 'cobrar');
		$tipo_chamado             = pg_fetch_result($res, 0, 'tipo_chamado') ? : 0;
		$horas_utilizadas         = pg_fetch_result($res, 0, 'horas_utilizadas');
		$horas_analisadas         = pg_fetch_result($res, 0, 'horas_analisadas');
		$admin_erro               = pg_fetch_result($res, 0, 'admin_erro');
		$tipo_erro                = pg_fetch_result($res, 0, 'tipo_erro');
		$motivo_erro              = pg_fetch_result($res, 0, 'motivo_erro');
		$campos_adicionais        = pg_fetch_result($res, 0, 'campos_adicionais');
		$desconto                 = number_format(pg_fetch_result($res, 0, "valor_desconto"), 2, ",", ".");
		$atendente_responsavel    = pg_fetch_result($res, 0, 'login_admin');
		$campos_adicionais    	  = pg_fetch_result($res, 0, 'campos_adicionais');
		$admin_adicionais    	  = pg_fetch_result($res, 0, 'admin_adicionais');

		$campos_adicionais = json_decode($campos_adicionais, true);
		$admin_adicionais  = json_decode($admin_adicionais,  true);

		$celular = $admin_adicionais['celular'];	
		
		if($fabrica == 159){
			$campoPrioridade = $campos_adicionais['prioridade'];	
		}	
		$impacto_financeiro = $campos_adicionais['impacto_financeiro'];	
		$horas_franq_ter          = pg_fetch_result($res, 0, 'horas_franq_ter');
		$horas_fat_ter            = pg_fetch_result($res, 0, 'horas_fat_ter');
		if (strlen($campos_adicionais)) {
		    extract(json_decode($campos_adicionais, true));
		}
		//HD 218848: Criação do questionário na abertura do Help Desk
		$sql = "SELECT * FROM tbl_hd_chamado_questionario WHERE hd_chamado=$hd_chamado";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res)) {
			$mostra_questionario = true;
			$necessidade   = pg_fetch_result($res, 0, 'necessidade');
			$funciona_hoje = pg_fetch_result($res, 0, 'funciona_hoje');
			$objetivo      = pg_fetch_result($res, 0, 'objetivo');
			$local_menu    = pg_fetch_result($res, 0, 'local_menu');
			$http          = pg_fetch_result($res, 0, 'http');
			$tempo_espera  = pg_fetch_result($res, 0, 'tempo_espera');
			$impacto       = pg_fetch_result($res, 0, 'impacto');
		}
	}else{
		$msg_erro="Chamado não encontrado";
	}
}

#HD 351094
$atualizacaoDados = true;
if($status =='Resolvido' || $status =='Cancelado'){
	$atualizacaoDados = false;
}

if($hd_chamado) $TITULO = $hd_chamado;
$TITULO .= " ADM - Responder Chamado";

include "menu.php";
?>
<!--
<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.5.1/jquery.min.js"></script>
//-->
<script type="text/javascript" src="js/ajax_busca.js"></script>
<script type="text/javascript" src="../plugins/Quill/quill.js"></script>
<!-- <script type='text/javascript' src='../admin/js/fckeditor/fckeditor.js'></script> -->

<script>
var statusPostType = "insert";
window.onload = function () {
	$.quillPlugin("#comentario");
	
	$("#input-pendente").prop({disabled: true});
	$("#input-entregue").prop({disabled: true});
	$("#input-entregue").prop({checked: false});

	if ($("#input-orcamento")) {
		$("#input-orcamento").mask("99:99:99");
	}
	
	$(".edit-etapa").on("click", function () {
		$("#input-pendente").prop({disabled: false});
		$("#input-pendente").prop({checked: false});

		statusPostType = "update";
		var statusChamado = $(this).parents("tr").data("status");
		var entrega = $(this).parents("tr").find("td:last").data("entrega");
		var etapa_desc = $(this).parents("tr").find("td:nth-child(3)").html();
		var etapa = $(this).parents("tr").find("td:nth-child(3)").data("etapa");
		var prazo = $(this).parents("tr").find("td:nth-child(5)").html();
		var admin = $(this).parents("tr").find("td:nth-child(2)").data("admin");

		$(".form-gestao").attr("data-statuschamado", statusChamado);

		var select = $(this).parents("form").find(".select-etapa");
		var selectAdmin = $(this).parents("form").find(".select-admin");
		var inputPrazo = $(this).parents("form").find("#input-prazo");

		var hdChamado = $(".form-gestao").data("chamado");

		$(selectAdmin).html("<option value='select'>Selecione</option>");

		$(select).attr("disabled", "disabled");
		$(inputPrazo).prop({disabled: false});
		$(inputPrazo).mask("99/99/9999 99:99");
		$(inputPrazo).val(prazo);
		$(select).html("<option selected value='" + etapa + "'>" + etapa_desc + "</option>");

		var admin_resp = $(".select-admin").val();

		$.ajax({
			url: 'adm_chamado_detalhe.php',
			type: 'POST',
			data: {
				loadAdmins: true,
				etapa: etapa,
				hdChamado: hdChamado,
			}
		}).done(function (response) {
			response = JSON.parse(response);
			$(response.admins).each(function (index, element) {
				var option = $("<option></option>", {
					value: element.admin,
					text: element.nome_completo
				});

				if (element.admin == admin) {
					$(option).prop({selected: true});
				}
				
				$(selectAdmin).append(option);
			});

			if (response.dt_entrega == null) {
				$("#input-entregue").prop({disabled: false});
				$("#input-pendente").prop({disabled: true});
			} else {
				$("#input-entregue").prop({disabled: true});
				$("#input-pendente").prop({disabled: false});
			}

			$("#input-entregue").prop({checked:false});
		});
	});

	// GESTÃO CHAMADO REQUISITOS
	var selectEtapa = $(".select-etapa").find("option");
	$(selectEtapa).each(function (index, element) {
		if ($(element).val() == "select") {
			$(element).prop({selected: true});
		}
	});

	var selectAdmin = $(".select-admin").find("option");
	$(selectAdmin).each(function (index, element) {
		if ($(element).val() == "select") {
			$(element).prop({selected: true});
		}
	})
	
	$("#input-prazo").val("");
	$("#input-prazo").mask("99/99/9999 99:99");

	$(".select-etapa").on("change", function () {
		$("#input-prazo").val("");
		var tipo = $(this).find("option:selected").data("tipo");
		if (tipo == 't') {
			$("#input-prazo").mask("99/99/9999 99:99");
		} 
	});

	$("#btn-salvar-gestao").on("click", function () {
		var frmGestao = $(".form-gestao");

		var hdChamado = frmGestao.data("chamado");
		var etapa = frmGestao.find(".select-etapa").val();
		var status = frmGestao.data("status");
		var prazo = frmGestao.find("#input-prazo").val();
		var admin = frmGestao.find(".select-admin").val();
		var statusChamado = frmGestao.attr("data-statuschamado");
		var pendente = frmGestao.find("#input-pendente").prop("checked");
		var entregue = frmGestao.find("#input-entregue").prop("checked");
		var orcamento = null;
		if (frmGestao.find("#input-orcamento")) {
			orcamento = $("#input-orcamento").val();
		}

		if (etapa == "select") {
			alert("Preencha todas as informações.");
			return false;
		}

		if ($(".select-etapa").find("option:selected").data("tipo") == "t" && prazo.length != 16) {
			alert("Preencha todas as informações");
			return false;
		}

		$.ajax({
			url: 'adm_chamado_detalhe.php',
			type: 'POST',
			data: {
				statusPostType: statusPostType,
				statusChamado: statusChamado,
				hdChamado: hdChamado,
				etapa: etapa,
				status: status,
				prazo: prazo,
				admin: admin,
				pendente: pendente,
				entregue: entregue,
				orcamento: orcamento
			}
		}).done(function (response) {
			if (response != "ok") {
				alert(response);
			} else {
				window.location.reload();
			}
		});
	});
}

/* var oFCKeditor;

window.onload = function(){
	document.getElementById("comentario").value = "";
	oFCKeditor = new FCKeditor( 'comentario');
	oFCKeditor.BasePath = "../admin/js/fckeditor/" ;
	oFCKeditor.ToolbarSet = 'Chamado' ;
	oFCKeditor.ReplaceTextarea();
}

function resetTextarea(){
	FCKeditorAPI.GetInstance('comentario').SetHTML('');
} */

function recuperardados(hd_chamado) {
	var programa = document.frm_chamada.programa.value;
	if(programa.length > 4 ){
		var busca = new BUSCA();
		busca.Updater("ajax_listar_programa.php?digito="+programa+"&hd_chamado="+escape(hd_chamado),"conteudo","get","carregando os dados...");
	}
}
function checarNumero(campo){
	var num = campo.value.replace(",",".");
	campo.value = parseFloat(num).toFixed(2);
	if (campo.value=='NaN') {
		campo.value='';
	}
}
function createRequestObject(){
	var request_;
	var browser = navigator.appName;
	if(browser == "Microsoft Internet Explorer"){
		 request_ = new ActiveXObject("Microsoft.XMLHTTP");
	}else{
		 request_ = new XMLHttpRequest();
	}
	return request_;
}

var http3 = new Array();


function atualizaHr(hd,hr){
	var curDateTime = new Date();
	http3[curDateTime] = createRequestObject();
	var campo = document.getElementById('result');

	if (campo==false) return;
	if (campo.style.display=="block"){
		campo.style.display = "none";
	}else{
		campo.style.display = "block";
	}

	url = "<?$PHP_SELF;?>?atualiza_hd=true&hd="+hd+"&hr="+hr+"&data="+curDateTime;
	http3[curDateTime].open('get',url);
	http3[curDateTime].onreadystatechange = function(){
		if(http3[curDateTime].readyState == 1) {
			campo.innerHTML = " <font size='1' face='verdana'> Aguarde..</font>";
		}
		if (http3[curDateTime].readyState == 4){
			if (http3[curDateTime].status == 200 || http3[curDateTime].status == 304){
				var results = http3[curDateTime].responseText;
				campo.innerHTML = results;
				/*	if (campo==false) return;
					if (campo.style.display=="block"){
						campo.style.display = "none";
					}else{
						campo.style.display = "block";
					}*/
			}else {
				alert('Ocorreu um erro');
			}
		}
	}
	http3[curDateTime].send(null);

}

function atualizaPrazo(hd,prazo){
	var curDateTime = new Date();
	http3[curDateTime] = createRequestObject();
	var campo = document.getElementById('result');

	if (campo==false) return;
	if (campo.style.display=="block"){
		campo.style.display = "none";
	}else{
		campo.style.display = "block";
	}

	url = "<?$PHP_SELF;?>?atualiza_hd=true&hd="+hd+"&prazo="+prazo+"&data="+curDateTime;
	http3[curDateTime].open('get',url);
	http3[curDateTime].onreadystatechange = function(){
		if(http3[curDateTime].readyState == 1) {
			campo.innerHTML = " <font size='1' face='verdana'> Aguarde..</font>";
		}
		if (http3[curDateTime].readyState == 4){
			if (http3[curDateTime].status == 200 || http3[curDateTime].status == 304){
				var results = http3[curDateTime].responseText;
				campo.innerHTML = results;
			}else {
				alert('Ocorreu um erro');
			}
		}
	}
	http3[curDateTime].send(null);

}

function atualizaPrazo_terceiro(tipo,hd,prazo) {
	var curDateTime = new Date();
	http3[curDateTime] = createRequestObject();
	var campo = document.getElementById('msg_result_'+tipo);

	if (campo==false) return;
	if (campo.style.display=="block"){
		campo.style.display = "none";
	}else{
		campo.style.display = "block";
	}

	url = "<?$PHP_SELF;?>?tipo="+tipo+"&atualiza_hd_terceiro=true&hd="+hd+"&prazo="+prazo+"&data="+curDateTime;
	http3[curDateTime].open('get',url);
	http3[curDateTime].onreadystatechange = function(){
		if(http3[curDateTime].readyState == 1) {
			campo.innerHTML = " <font size='1' face='verdana'> Aguarde..</font>";
		}
		if (http3[curDateTime].readyState == 4){
			if (http3[curDateTime].status == 200 || http3[curDateTime].status == 304){
				var results = http3[curDateTime].responseText;
				campo.innerHTML = results;
			}else {
				alert('Ocorreu um erro');
			}
		}
	}
	http3[curDateTime].send(null);

}

function atualizaPrazoTermino(hd_chamado,botao,tipo_admin,tem_motivo) {
	var horas_internas = $('#horas_analisadas').val();

	var postData = {
		atualizaHoras: 'true',
		hd_chamado: hd_chamado,
		tipo_admin: tipo_admin,
		horas_internas: horas_internas
	};

	if (tem_motivo == 'sim') {
		postData.motivo = $("#motivo").val();
		if (/(.)\1{3,}/.test(postData.motivo) || postData.motivo.length <= 10) {
			alert('Por favor, informar corretamente o motivo (mín. 10 caracteres)');
			return false;
		}
	}

	$.ajax({
		url:  document.location.pathname,
		type: "POST",
		data: postData,
		success: function(data) {
			if (tipo_admin != 1) {
				$(botao).hide();
			}
			$('#previsao_termino_interna').html(data);
			$(this).hide();
			$('#motivo').val('');
			
			return true;
		},
		error: function(data) {  return false}
	});
	return false;
}

function atualizaPrevisaoTermino(hd, data) {

	if(data.length == 0){
		return false;
	}

	var reqData = {
		atualiza_previsao_termino: 'true',
		hd: hd,
		data_previsao: data,
		data: new Date().valueOf() // UNIX TimeStamp
	};

	if ($('#result2').length === 0) return;

	$('#result2').toggle();

	$.ajax({
		url: document.location.pathname,
		method: 'GET',
		data: reqData,
		beforeSend: function() {$('#result2').html('Aguarde...');},
		success: function(results) {$('#result2').html(results);}
	});
}

function atualizaPrevisaoInterna(hd) {

	var data = $('#previsao_termino_interna').val();
	if(data.length == 0){
		return false;
	}

	var reqData = {
		atualiza_interna: 'true',
		hd: hd,
		data: data,
		timestamp: new Date().valueOf() // UNIX TimeStamp
	};

	if ($('#result2').length === 0) return;

	$('#result2').toggle();

	$.ajax({
		url: document.location.pathname,
		method: 'POST',
		data: reqData,
		beforeSend: function() {$('#div_previsao_interna').html('Aguarde...');},
		success: function(results) {$('#div_previsao_interna').html(results);}
	});
}


	$(function(){

		$("ul.pai > li.first > span").click(function(){

			$(this).parent().find('ul').slideToggle();

		});

		$("li.node > span").click(function(){
			$(this).parent().find('ul').slideToggle();
		});

	});

</script>
<link rel="stylesheet" href="js/blue/style.css" type="text/css" id="" media="print, projection, screen" />
<link rel="stylesheet" href="plugins/font_awesome/css/font-awesome.css">


<script type="text/javascript" charset="utf-8">
	$(function()
	{
		$("#previsao_termino").mask("99/99/9999 99:99");
		$("#previsao_termino_interna").mask("99/99/9999 99:99");

		var horas;
		var horas2;

	});

	/* Encurta a URL do Comentário para não exceder o tamanho padrão */
	$(document).ready(function(){
		$('.relatorio > tbody > tr > td > a').each(function(){
			var link = $(this).text();
			link = link.substr(0, 70);
			if(link.length >= 70)
				$(this).text(link+"...");
			else
				$(this).text(link);
		});
	});

</script>

<script type="text/javascript" src="js/jquery.tablesorter.pack.js"></script>
<?php
#HD 351094
?>
<script type="text/javascript" src="js/jquery.maskmoney.js"></script>

<script>
$(document).ready(function(){
	$.tablesorter.defaults.widgets = ['zebra'];
	<?php
	#HD 351094
	?>
	$(".horas[name!=desconto]").maskMoney({symbol:"", decimal:".", thousands:"",precision:1});

	$("input[name=desconto]").maskMoney({symbol:"", decimal:",", thousands:".",precision:2});

	$(".relatorio tr").mouseover(function(){$(this).addClass("over");}).mouseout(function(){$(this).removeClass("over");});
	$(".relatorio tr:even").addClass("alt");
	$(".anexo").click(function(){
		$(this).next("table").toggle();
	});
});
</script>

<script language="JavaScript">
function abrir(URL) {
	var width = 300;
	var height = 290;
	var left = 99;
	var top = 99;

	window.open(URL,'janela', 'width='+width+', height='+height+', top='+top+', left='+left+', scrollbars=yes, status=no, toolbar=no, location=no, directories=no, menubar=no, resizable=no, fullscreen=no');
}
</script>

<style>
.box-admins-responsaveis{
	padding: 6px;
}
.resolvido{
	background: #259826;
	color: #FCFCFC;
	float: left;
	clear: none;
	padding: 2px 2px 2px 2px;
	width: 100%;
	font-style: normal;
	font-variant: normal;
	font-weight: bold;
	text-align: center;
	}
.interno{
	background: #FFE0B0;
	color: #000000;
	float: left;
	clear: none;
	padding: 2px 2px 2px 2px;
	width: 100%;
	font-style: normal;
	font-variant: normal;
	font-weight: bold;
	text-align: center;
	}

	.anexo {
		background: #C9D7E7;
		color: #000000;
		float: left;
		clear: none;
		padding: 2px 0px 2px 0px;
		width: 100%;
		font-style: normal;
		font-variant: normal;
		font-weight: bold;
		text-align: center;
		cursor: pointer;
	}
	.anexo:hover {
		background: #B5C2D0;
	}

	table.tab_cabeca{
		border:1px solid #3e83c9;
		font-family: Verdana;
		font-size: 11px;

	}
	ul.pai li {list-style:none; font-size:11px;  }
	.adm_res { padding:0;margin:0; }
	ul.pai{ padding-left:0;margin-left:0; padding-right:0;margin-right:0; background:#e7eaf1 !important; }
	ul.pai span{ font-weight:bold; font-size:12px; cursor:pointer; display:block;}
	li.node, ul.pai li{ border-bottom:1px solid #ededed; }
	.status { float:right;width: 90px;display:inline-block;_zoom:1; padding:0;margin:0; text-align:center; margin:auto;border-left:1px solid white; }
	.titulo_cab{
		background: #C9D7E7;
		padding: 5px;
		color: #000000;
		font: bold;
	}
	.sub_label{
		background: #E7EAF1;
		padding: 5px;
		color: #000000;
		border-bottom:1px solid #ccc;
	}
	table.relatorio {
		font-family: Verdana;
		font-size: 11px;
		border-collapse: collapse;
		width: 750px;
		font-size: 1.1em;
		border-left: 1px solid #8BA4EB;
		border-right: 1px solid #8BA4EB;
	}

	table.relatorio th {
		font-family: Verdana;
		font-size: 11px;
		background: #3e83c9;
		color: #fff;
		font-weight: bold;
		padding: 2px 2px;
		text-align: left;
		border-right: 1px solid #fff;
		line-height: 1.2;
		padding-top: 5px;
		padding-bottom: 5px;
	}

	table.relatorio td {
		font-family: Verdana;
		font-size: 11px;
		padding: 1px 5px 5px 5px;
		border-bottom: 1px solid #95bce2;
		line-height: 15px;
	}

/*
	table.sample td * {
		padding: 1px 11px;
	}
*/
	table.relatorio tr.alt td {
		background: #ecf6fc;
	}
<? if($login_admin != 822) { ?>
	table.relatorio tr.over td {
		background: #bcd4ec;
	}
<? } ?>

	table.relatorio tr.clicado td {
		background: #FF9933;
	}
	table.relatorio tr.sem_defeito td {
		background: #FFCC66;
	}
	table.relatorio tr.mais_30 td {
		background: #FF0000;
	}
	table.relatorio tr.erro_post td {
		background: #99FFFF;
	}

	dt,dd {
		display:inline-block;
		font-size: 13px;
	}
	dt {
		width:150px;
		font-weight: bold;
	}
	dd {
		width: 50px;
		text-align: right;
	}

	div.div_tabela_1_159 {
		
	}

	div.div_tabela {
		
	    
	}

	div.tabela_e_helpdesk {
		
	}

	div.tabela_e_helpdesk_todos { 
		
	}

	div.ajuste {
		
	}
	div.mae{
		width: 100%;
	}
	
	div.trinta_1{
		width: 19%;
	    float: left;
	    min-height: 400px;
	}
	
	div.trinta_2{
		width: 19%;
	    float: left;
	    min-height: 400px;
		padding-left: 2%;
	}


	div.quarenta{
		width: 59%;
		float: left;
	}

<?php 
	if($fabrica == 1) { 
?>
		
<?php 	
	}
?>

</style>

<?php 
	if (in_array($login_admin, array(5205,4789,8527)) || ($analista_hd == 'sim' && $login_admin != 586) || !in_array($grupo_admin, array(3,4,5,7,8))) {
		$div_class = 'tabela_e_helpdesk_todos';
		$div_tabela = 'div_tabela';
		if (in_array($fabrica, [1, 159])) {
			$div_tabela = 'div_tabela_1_159';
			$div_class = 'tabela_e_helpdesk';
		}
	} else {
		$div_class = '';
		$div_tabela = '';
	}

?>
<div class="mae">

<div class="trinta_1"></div>
<div class="quarenta <?=$div_class?>">


	<div class="<?=$div_tabela?> <?=$ajuste?>">
		<table width = '750' class = 'tab_cabeca' align = 'center' border='0' cellpadding='2' cellspacing='2' >

		<tr>
			<td class='titulo_cab' width='10'><strong>Título </strong></td>
			<td class='sub_label'><?= $titulo ?> </td>

			<td class='titulo_cab' width="60"><strong>Abertura </strong></td>
			<td  class='sub_label'><?= $data ?> </td>

		</tr>
		<tr>
			<td class='titulo_cab' ><strong>Solicitante </strong></td>
			<td  class='sub_label' ><?= $login ?> </td>
			<td class='titulo_cab' width="60" ><strong>Chamado </strong></td>
			<td  class='sub_label'><strong><font  color='#FF0033' size='4'><?=$hd_chamado?></font></strong></td>
		</tr>
		<tr>
			<td class='titulo_cab' ><strong>Nome </strong></td>
			<td class='sub_label'><?= $nome ?></td>
			<td class='titulo_cab' width="60"><strong>Fábrica </strong></td>
			<td  class='sub_label'><?='('.$fabrica.') - ' . $fabrica_nome ?> </td>
		</tr>

		<tr>
			<td class='titulo_cab'><strong>e-mail </strong></td>
			<td class='sub_label'><?= $email ?></td>
			<td class='titulo_cab'><strong>Contato </strong></td>

			<td  class='sub_label'>
				<?php 
					if ( strlen($fone) == 0 && strlen($celular) == 0 && strlen($whatsapp) == 0) {
						echo "Sem número para contato cadastrado";
					}
				?>
				<table>
					<tr>
						<?php if (strlen($fone) > 0) { ?>
							<td align="center" width="100">
								<strong>Telefone</strong>
							</td>
						<?php } ?>
						<?php if (strlen($celular) > 0) { ?>
							<td align="center"> | </td>
							<td align="center" width="100">
								<strong>Celular</strong>
							</td>
						<?php } ?>
						<?php if (strlen($whatsapp) > 0) { ?>
							<td align="center"> | </td>
							<td align="center" width="100">
								<strong>Whatsapp</strong>
							</td>
						<?php } ?>
					</td>
					<tr>
						<?php if (strlen($fone) > 0) { ?>
							<td align="center" width="100"><?= $fone ?></td>
						<?php } ?>
						<?php if (strlen($celular) > 0) { ?>
							<td align="center"> | </td>
							<td align="center" width="100"><?= $celular ?></td>
						<?php } ?>
						<?php if (strlen($whatsapp) > 0) { ?>
							<td align="center"> | </td>
							<td align="center" width="100"><?= $whatsapp ?></td>
						<?php } ?>
					</td>
				</table>	
			</td>
		</tr>

		<tr>
		    <td class='titulo_cab'><strong>Origem</strong></td>
		    <td class='sub_label'><?= array_key_exists('origem', $campos_adicionais) ? $campos_adicionais['origem'] : 'Help-Desk' ?></td>
		    <td class='titulo_cab'><strong>Analista</strong></td>
		    <td class='sub_label'><?= $analista_chamado_nome ?></td>
		</tr>

		<tr>
			<td class='titulo_cab' ><strong>Atendente </strong></td>
			<td class='sub_label'><?= $atendente_nome ?></td>
			<td class='titulo_cab'><strong>Status </strong></td>
			<td  class='sub_label'><?= $status ?></td>
		</tr>


		<!-- HD 218848: Criação do questionário na abertura do Help Desk -->
		<?
		if ($mostra_questionario) {
			$desabilita_questionario = "readonly";
			$desabilita_questionario_combo = "disabled";
		?>
		<tr>
			<td colspan="4" bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'>
				<strong>&nbsp;O que você precisa que seja feito?</strong>
			</td>
		</tr>
		<tr>
			<td colspan=4 bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px; padding: 10px;'>
				<? echo $necessidade; ?>
			</td>
		</tr>

		<tr>
			<td colspan="4" bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'>
				<strong>&nbsp;Como funciona hoje?</strong>
			</td>
		</tr>
		<tr>
			<td colspan=4 bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px; padding: 10px;'>
				<? echo $funciona_hoje; ?>
			</td>
		</tr>

		<!-- <tr>
			<td colspan="4" bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'>
				<strong>&nbsp;Qual o objetivo desta solicitação? Que problema visa resolver?</strong>
			</td>
		</tr>
		<tr>
			<td colspan=4 bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px; padding: 10px;'>
				<? echo $objetivo; ?>
			</td>
		</tr> -->

		<!-- <tr>
			<td colspan="4" bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'>
				<strong>&nbsp;Esta rotina terá impacto financeiro para a empresa? Por quê?</strong>
			</td>
		</tr>
		<tr>
			<td colspan=4 bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px; padding: 10px;'>
				<? echo $impacto; ?>
			</td>
		</tr> -->

		<tr>
			<td colspan="4" bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'>
				<strong>&nbsp;Em que local do sistema você precisa de alteração?</strong>
			</td>
		</tr>
		<tr>
			<td colspan=4 bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px; padding: 10px;'>
			<?

			switch($local_menu) {
				case "admin_gerencia":
					echo "Administração: Gerência";
				break;

				case "admin_callcenter":
					echo "Administração: CallCenter";
				break;

				case "admin_cadastro":
					echo "Administração: Cadastro";
				break;

				case "admin_infotecnica":
					echo "Administração: Info Técnica";
				break;

				case "admin_financeiro":
					echo "Administração: Financeiro";
				break;

				case "admin_auditoria":
					echo "Administração: Auditoria";
				break;

				case "posto_os":
					echo "Área do Posto: Ordem de Serviço";
				break;

				case "posto_infotecnica":
					echo "Área do Posto: Info Técnica";
				break;

				case "posto_pedidos":
					echo "Área do Posto: Pedidos";
				break;

				case "posto_cadastro":
					echo "Área do Posto: Cadastro";
				break;

				case "posto_tabelapreco":
					echo "Área do Posto: Tabela Preço";
				break;
			}

			?>
			</td>
		</tr>

		<!-- <tr>
			<td colspan="4" bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'>
				<strong>&nbsp;Quanto tempo é possível esperar por esta mudança?</strong>
			</td>
		</tr>
		<tr>
			<td colspan=4 bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px; padding: 10px;'>
			<?
			$a_tempo_espera = array(0=>'Imediato','1 Dia','2 Dias','3 Dias','4 Dias','5 Dias','6 Dias','1 Semana',
									14=>'2 Semanas', 21=>'3 Semanas', 30=>'1 Mês',
									60=>'2 Meses', 90=>'3 Meses', 180=>'6 Meses');
			$tempo_espera = (isset($a_tempo_espera[$tempo_espera])) ? $a_tempo_espera[$tempo_espera] : "$tempo_espera Dias";

			// $http = (substr($http, 0, 7) == 'http://') ? $http : "http://$http";
			$http = 'https://' . preg_replace('#^https?://#', '', trim($http));
			?>
			</td>
		</tr>
		 -->
		<tr>
			<td colspan="4" bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'>
				<strong>&nbsp;Endereço HTTP da tela onde está sendo solicitada a alteração:</strong>
			</td>
		</tr>
		<tr>
			<td colspan=4 bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px; padding: 10px;'>
				<? echo $http; ?>
			</td>
		</tr>
		<?
		}
		?>
		</table>
	</div>



<?php
//Modificado por Thiago Contardi - HD: 304470
?>
<style>
#menu_requisitos{
	list-style:none;
	width:750px;
	border: 1px solid #3e83c9;
	margin:25px 0;
	padding:1px;
	text-align:left;
}
#menu_requisitos > li,#menu_requisitos li{
	margin:3px;
}
#menu_requisitos > li:hover{
	color:#o9f;
}
p .link{
	font-size:16px;
	cursor:pointer;
}
.menuAnalise{
	width:100%;
	padding:2px;
	margin:1px;
	cursor:pointer;
	display:block;
}
.rodada{
	border:1px solid #ccc;
	margin:0;
	padding:0;
}
.table_requisitos{
	font-family: arial;
	font-size: 12px;
	width:720px;
	margin-top:30px;
}
.table_melhorias{
	font-family: arial;
	font-size: 12px;
	width:720px;
	margin-top:30px;
}
.salvarAnalise input{
	float:right;
	margin:10px;
}
.salvarAnalise{
	height:35px;
}
.salvarTeste input {
	float:right;
	margin:10px;
}
.salvarTeste {
	height:35px;
}
.textareaAnalise{
	/* width:690px;
	height:150px; */
}
.textareaEfetivacao,.textareaValidacao,.textareaMelhorias{
	/* width:690px;
	height:300px; */
}
.table_analise{
	font-family: arial;
	font-size: 12px;
	width:710px;
}
.table_analise tr,.table_analise td{
	margin:1px;
}
.xAnalise{
	padding:5px;
	text-decoration:none;
}
.xAnalise:hover{
	background-color:#999;
	text-decoration:none;
	color:#cdcdff;
}
.finalizarCorrecoes{
	font-size:10pt;
}
.greenHD{
	background-color:#85C940;
}
.yellowHD{
	background-color:#C9C940;
}
.orangeHD{
	background-color:#C98540;
}
.brownHD{
	background-color:#A37246;
}
.redHD{
	background-color:#C94040;
}
.grayHD{
	background-color:#ccc;
}
.pinkHD{
	background-color:#88B3DD;
}
.purpleHD{
	background-color:#E1CAE7;
}

.deepPurpleHD {
	background-color:#598DFF;
}
.control-group {
	width:32%;
	display:inline-block;
	margin:0px;
}

.control-select {
	width:100%;
	display:block;
}

.span12 {
	width:100%;
}

.span12 th {
	 text-align:center;
}

.admin{
	font-size:10px;
	margin:0;
	padding:0;
}
.topo{
	width:100%;
	background-color:#95C940;
	color:#fff;
	font-size:16px;
}
#procedimento_teste_ajax { font-weight:normal; }

.esconde {
	display: none;
}

.botao_interacao {
text-decoration: none;
font:  16px/1em 'Droid Sans', sans-serif ;
color: red;
font-weight: bold;
text-shadow: rgba(255,255,255,.5) 0 1px 0;
-webkit-user-select: none;
-moz-user-select: none;
user-select: none;

/* layout */

padding: .5em .6em .4em .6em;
margin: .5em;
display: inline-block;
position: relative;
-webkit-border-radius: 8px;
-moz-border-radius: 8px;
border-radius: 8px;

/* effects */

border-bottom: 1px solid rgba(0,0,0,0.1);
-webkit-transition: background .2s ease-in-out;
-moz-transition: background .2s ease-in-out;
transition: background .2s ease-in-out;

/* color */

color: red hsl(0, 0%, 40%) !important;
background-color: hsl(0, 0%, 75%);
-webkit-box-shadow: inset rgba(255,254,255,0.6) 0 0.3em .3em, inset rgba(0,0,0,0.15) 0 -0.1em .3em, /* inner shadow */ 
hsl(0, 0%, 60%) 0 .1em 3px, hsl(0, 0%, 45%) 0 .3em 1px, /* color border */
rgba(0,0,0,0.2) 0 .5em 5px; /* drop shadow */
-moz-box-shadow: inset rgba(255,254,255,0.6) 0 0.3em .3em, inset rgba(0,0,0,0.15) 0 -0.1em .3em, /* inner shadow */ 
hsl(0, 0%, 60%) 0 .1em 3px, hsl(0, 0%, 45%) 0 .3em 1px, /* color border */
rgba(0,0,0,0.2) 0 .5em 5px; /* drop shadow */
box-shadow:inset rgba(255,254,255,0.6) 0 0.3em .3em, inset rgba(0,0,0,0.15) 0 -0.1em .3em, /* inner shadow */ 
hsl(0, 0%, 60%) 0 .1em 3px, hsl(0, 0%, 45%) 0 .3em 1px, /* color border */
rgba(0,0,0,0.2) 0 .5em 5px; /* drop shadow */

}
</style>

<script>
var tipo_de_chamado = <?=$tipo_chamado?>;
var grupo = <?=$grupo_admin;?>;

$(document).ready(function() {
	$('#menu_requisitos li, #menu_requisitos table tr th').addClass('titulo_cab');
	$('#menu_requisitos li div, #menu_requisitos table tr td').addClass('sub_label');

	$('#menu_requisitos li > strong').click(function(){
		$(this).next().toggle();
	});

	$(document).delegate('.itemDelete', 'click', function() {
		$(this).closest('li').remove();
		var qtdeArquivos = $('#qtdeArquivos').val();
		qtdeArquivos--;
		$('#qtdeArquivos').val(qtdeArquivos);
	});

	//interacoes();
});

function verificaPrazo(){
    <?php if (in_array($login_admin, array(5205,4789,8527))) {?>
	if ($('#previsao_termino').val() == '' && $("select[name=status]").val() == 'Orçamento') {
		var r = confirm("Deseja gravar sem informar a previsão do cliente?");
		if (r == false) {
			$('#previsao_termino').focus();
			return false;
		}
	}
	<?php }?>
	
	if($("select[name=status]").val() == 'Requisitos'){
		return true;
	}

	var submit = true;
	if (grupo == 4) {
		$.ajax({
			url: document.location.pathname,
			cache: false,
			type:"POST",
			async:false,
			data:{
				previsaoTerminoInterna:1,
				hd_chamado:"<?=$hd_chamado;?>"
			},
			complete: function(data) {
				submit = false;
				var dtPrevisaoTerminoInterna = data.responseText;
				// console.log(dtPrevisaoTerminoInterna);

				if($("#status_atual").val() == "Requisitos" || $("#status_atual").val() == "Aguard.Admin"){
					submit = true;
					return submit;
				}

				if (dtPrevisaoTerminoInterna.length != 0) {

					// var timePrevisto = new Date(data[2]+"-"+data[1]+"-"+data[0]+"T"+hora+":00").getTime();
					var timePrevisto = new Date(dtPrevisaoTerminoInterna),
						timeAtual    = new Date();

					console.log("Previsto: "+timePrevisto);
					console.log("atual: "+timeAtual);

					if (timeAtual > timePrevisto) {
						submit = true;
						return submit;

						alert('O chamado passou da previsão, contata o analista para informar o motivo');
						return false;

						var motivo = '';
						motivo = prompt("O chamado passou da Previsão Interna.\nInforme um motivo para extender o prazo:");

						if (motivo.length < 4) {
							$('#hidden_horas_utilizadas').val("n");
							return false;
						}

						if ($("#motivo").length == 0) {
							$('body').append("<input type='hidden' id='motivo' name='motivo' />");
						}

						$("#motivo").val(motivo);
						submit = atualizaPrazoTermino(<?=$hd_chamado?>, '', grupo, 'sim');

					} else {
						$('#hidden_horas_utilizadas').val("s");
						submit = true;
					}

				} else {
					if($("#status_atual").val() != "Requisitos"){
						if (tipo_de_chamado == 5) {
							alert ("Chamado de erro SEM Previsão Interna.\nPode continuar.");
						} else {
							alert ("Chamado AINDA NÃO TEM Previsão Interna.\nCadastrando previsão, pode continuar.");
						}
					}
					submit = true;
					$('#hidden_horas_utilizadas').val("n");
				}
			}
		});
	}
	return submit;
}

function muda_admin(admin,valor) {
	if(admin!=valor.value){
		if(confirm("Você está alterando o responsável pelo chamado, deseja continuar?")){
			return true;
		}else{
			valor.selectedIndex = $('#indexDesenv').val();
		}
	}
}

var analise = {

	addRequisito:function(){
		var numero = $('#numeroRequisito').val();
		$.ajax({
			url: "<?php echo $_SERVER['PHP_SELF']; ?>?adReq=1&numero="+numero,
			cache: false,
			success: function(data){
				console.log(data);
				$(".naoRequisitos").remove();
				numero++;
				$('#numeroRequisito').val(numero);
				$("#frm_req > tbody").append(data);
			}
		});
	},
	delRequisito:function(numero){
		if(confirm('Deseja mesmo remover este requisito?')){
			$('#requisito_'+numero).remove();
		}
	},
	deleteRequisito:function(id,numero){

		if(confirm('Deseja mesmo remover este requisito?')){
			$.ajax({
				url: "<?php echo $_SERVER['PHP_SELF']; ?>?delReq=1&idReq="+id,
				cache: false,
				success: function(data){
                    var nextTr = $('#requisito_'+numero).next('tr');
                    nextTr.remove();
					$('#requisito_'+numero).remove();
					$('#admin_requisito_'+numero).remove();

				}
			});

		}
	},
    excluiAnexoRequisito: function(el){
        var anexo = $(el).prev().attr('href');
        if(confirm('Deseja mesmo remover este anexo?')){
            $.ajax({
                url: "<?php echo $_SERVER['PHP_SELF']; ?>",
                data:{'exclui_anexo': anexo},
                cache: false,
                success: function(data){
                    console.log(data);
                    var resp = $.parseJSON(data);
                    console.log(resp);
                    if(resp.success == true){
                        var tr = $(el).parents('tr');
                        tr.empty();
                    }
                }
            });

        }

    },
	addRequisitoFile:function(){
		var numero = $('#qtdeArquivos').val();
		numero++;
		var newLiFile = "<li class='titulo_cab'> Arquivo "+numero+": <input type='file' name='reqArquivo[]' > <img src='imagens/close_black_opaque.png' class='itemDelete' style='cursor:pointer;float:right'> </li>";
		$("#reqArquivosContainer").append(newLiFile);
		$("#qtdeArquivos").val(numero);
	},
	addInteracaoFile:function(){
		var newLiFile = "<li class='titulo_cab' style='margin:3px'> Arquivo: <input type='file' name='arquivo[]' > <img src='imagens/close_black_opaque.png' class='itemDelete' style='cursor:pointer;float:right'> </li>";
		$("#container_file_interacao").append(newLiFile);
	},
	addMelhorias:function(){

		//var numero = $('#numeroMelhorias').val();

		$.ajax({
			//url: "<?php echo $_SERVER['PHP_SELF']; ?>?addMelhorias=1&numero="+numero,
			url: "<?php echo $_SERVER['PHP_SELF']; ?>?addMelhorias=1",
			cache: false,
			success: function(data){
				$(".naoMelhorias").remove();
				//numero++;
				//$('#numeroMelhorias').val(numero);
				$("#melhorias_frm").append(data);
			}
		});
	},
	addAnalise:function(){

		var numero = $('#numeroAnalise').val();

		$.ajax({
			url: "<?php echo $_SERVER['PHP_SELF']; ?>?adAnalise=1&numero="+numero,
			cache: false,
			success: function(data){
				$(".naoAnalise").remove();
				numero++;
				$('#numeroAnalise').val(numero);
				$("#frm_ana").append(data);
			}
		});
	},
	delAnalise:function(numero){
		if(confirm('Deseja mesmo remover esta analise?')){
			$('#analise_'+numero).remove();
		}
	},
	deleteAnalise:function(id,numero){

		if(confirm('Deseja mesmo remover esta analise?')){
			$.ajax({
				url: "<?php echo $_SERVER['PHP_SELF']; ?>?delAnalise=1&idAnalise="+id,
				cache: false,
				success: function(data){
					$('#analise_'+numero).remove();
					$('#admin_analise_'+numero).remove();
				}
			});

		}
	},
	addCorrecao:function(rodada){

		var numero = $('#numeroCorrecao_'+rodada).val();

		$.ajax({
			url: "<?php echo $_SERVER['PHP_SELF']; ?>?adCorrecao=1&rodadaCorrecao="+rodada+"&numero="+numero,
			cache: false,
			success: function(data){
				$(".naoCorrecao").remove();
				numero++;
				$('#numeroCorrecao_'+rodada).val(numero);
				$("#tbl_correcao_"+rodada).append(data);
			}
		});
	},
	addRodada:function(rodada,chamado){

		var numero = $('#numeroCorrecao_'+rodada).val();

		$.ajax({
			url: "<?php echo $_SERVER['PHP_SELF']; ?>?adRodada=1&rodadaCorrecao="+rodada+"&numero="+numero+'&hd_chamado='+chamado,
			cache: false,
			success: function(data){
				$(".naoCorrecao").remove();
				numero++;
				$('#numeroCorrecao_'+rodada).val(numero);
				$("#nova_rodada").html(data);
			}
		});
	},
	delCorrecao:function(rodada,numero){
		if(confirm('Deseja mesmo remover esta correção?')){
			$('#correcao_'+rodada+'_'+numero).remove();
		}
	},
	deleteCorrecao:function(id,rodada,numero){
		if(confirm('Deseja mesmo remover esta correção?')){
			$.ajax({
				url: "<?php echo $_SERVER['PHP_SELF']; ?>?delCorrecao=1&idCorrecao="+id,
				cache: false,
				success: function(data){
					$('#correcao_'+rodada+'_'+numero).remove();
				}
			});

		}
	},
	showData:function(id){
		$("#"+id).toggle();
	},
	inicioTrabalho:function(chamado){

		if(verificaPrazo()){
				window.location.href='<?php echo $_SERVER['PHP_SELF']; ?>?hd_chamado='+chamado+'&inicio_trabalho=1';
		}
	},
	fimTrabalho:function(chamado){
		window.location.href='<?php echo $_SERVER['PHP_SELF']; ?>?hd_chamado='+chamado+'&termino_trabalho=1';
	},
	enviaFormulario:function(formulario){

		var dados = '';
		var total_horas = 0;
		var dadosInput = $('#'+formulario.id+' input').serialize();
		var dadosSelect = $('#'+formulario.id+' select').serialize();
		var dadosTextarea = $('#'+formulario.id+' textarea').serialize();

		if(dadosInput.length > 0)
			dados = dadosInput;
		if(dadosSelect.length > 0){
			if(dados.length > 0)
				dados = dados+'&'+dadosSelect;
			else
				dados = dadosSelect;
		}
		if(dadosTextarea.length > 0){
			if(dados.length > 0)
				dados = dados+'&'+dadosTextarea;
			else
				dados = dadosTextarea;
		}

		$.ajax({
			type: "POST",
			url: formulario.action,
			data: dados,
			beforeSend: function(){
				$('#'+formulario.id).append('<div id="msg_envio" align="right" style="padding:2px;border:1px solid #ccc;">enviando...</div>');
			},
			success: function(resposta) {
				//var respostas = array();
				var respostas = resposta.split('|');

				$('.horas[name!=desconto]').each(function(){
					if($(this).val() == ''){
						$(this).val('0');
					}
					total_horas += parseFloat($(this).val());
				});
				$('#total_horas').html(' '+total_horas+' h ');
				$('#msg_envio').addClass('topo');
				$('#msg_envio').html(respostas[0]);

				if(respostas[1]!=""){
					$('#procedimento_teste_ajax').html(respostas[1]);
				}

				setTimeout("limpaForm()",3000);
			}
		});

		return false;
	}
};

function atualizaChecklist(opcao,check){
		$.ajax({
			type: "GET",
			url: "<?php echo $_SERVER['PHP_SELF']; ?>?check1=1&check="+check+"&opcao="+opcao.value+"&hd_chamado=<?php echo $hd_chamado;?>",
			cache: false,
				success: function(data){
					$('#checkRes').html('<b>Admin Responsável</b> '+data).show();
				}
		});
	}

function limpaForm(){
	$('#msg_envio').fadeOut();
	$('#msg_envio').remove();
}

function interacoes(){
	(new Promise(function(resolve, reject) {
		$(".esconde").each(function() {
                	if ($(this).is(":visible")) {
        	                $(this).css({ "display": "none" });
	                } else {
                	        $(this).css({ "display": "table-row" });
        	        }
	        });
	}))();
}

function interacoes_importantes(){
		if($(".normal").is(":visible")){
				$("#relatorio tbody tr[class^=normal]").css({ "display": "none" });
				$("#relatorio tbody tr[class^=esconde]").css({ "display": "none" });
				$("#relatorio tbody tr[class^=importante]").css({ "display": "table-row" });
		}else{
				$("#relatorio tbody tr[class^=normal]").css({ "display": "table-row" });
				$("#relatorio tbody tr[class^=importante]").css({ "display": "table-row" });
		};

}
</script>
<?
		if($_GET["transferir"]== "sim"){

			?>
			<br><br>
			<form name="frm_transferir" method="POST" >
			<table width = '500px' align = 'center' class='tab_cabeca'  cellpadding='2' cellspacing='1' border='0' >
				<tr>
					<td colspan='2' align='center' class='titulo_cab'><strong><font size='4'><?echo $hd_chamado; ?></font></strong></td>
				</tr>
				<tr>
					<td class="sub_label" align="right" width="150">
						<b>Status:</b>
					</td>
					<td class="sub_label">
						<center>
							<input type="radio" name="status_transferir" value="EfetivaçãoHomologação">Homologar
							<input type="radio" name="status_transferir" value="Validação"> Validar
							<input type="radio" name="status_transferir" value="Efetivação"> Efetivar
						</center>
					</td>
					<tr>
						<td class="sub_label" align="right">
							<b>Suporte: </b>
						</td>
						<td  class ='sub_label' align='center' >
							<?
	                        $sql = "  SELECT *
										FROM tbl_admin
									   WHERE tbl_admin.fabrica =  10
									   AND ativo               IS TRUE
									   AND grupo_admin         IS NOT NULL
									   ORDER BY tbl_admin.nome_completo;";
							$res = pg_query($con,$sql);

							if ( pg_num_rows($res) > 0) {
								echo "<select class='frm' style='width: 150px;' name='transfere'>\n";
								echo "<option value=''>- ESCOLHA -</option>\n";

								for ($x = 0 ; $x <  pg_num_rows($res) ; $x++){
									$aux_admin = trim(pg_fetch_result($res, $x, 'admin'));
									$aux_nome_completo  = trim(pg_fetch_result($res, $x, 'nome_completo'));

									echo "<option value='$aux_admin'"; if ($atendente == $aux_admin) echo " SELECTED "; echo "> $aux_nome_completo</option>\n";
								}
								echo "</select>\n";


								echo "<input type='hidden' name='transfere_anterior' value='$atendente'>";
							}
							?>
						</td>
					</tr>
					<tr>
						<td colspan="2" class="sub_label">
							<center>
								<input type="submit" name="btn_transferir" value="Transferir">
							</center>
						</td>
					</tr>
				</tr>
			</table>
			</form>
			</div>
			<div class="trinta_2"></div>
			<?
			include "rodape.php";
			exit;
		}




		if(in_array($grupo_admin ,array(2,4,6)) and $_GET['consultar'] <> 'sim'){

						$sql = "SELECT data_termino,hd_chamado
								FROM tbl_hd_chamado_atendente
								WHERE  admin = $login_admin
								ORDER BY hd_chamado_atendente DESC limit 1";
						$res = pg_query($con,$sql);
						$hd_chamado_aux  = pg_fetch_result($res,0,'hd_chamado');
						$data_termino    = pg_fetch_result($res,0,'data_termino');
						if(empty($data_termino) and $hd_chamado <> $hd_chamado_aux and pg_num_rows($res) > 0 ){
								echo "<div class='trabalho_iniciado'><center style='color:red'>Você já está trabalhando no chamado $hd_chamado_aux</center>";
								echo "<br>";
								echo "<center><button type='button' onclick='window.location=\"adm_chamado_detalhe.php?hd_chamado=$hd_chamado&consultar=sim\"'>Consultar</button></center><div> <br>";
								echo "</div>";
								if (in_array($login_admin, array(5205,4789,8527)) || ($analista_hd == 'sim' && $login_admin != 586) || !in_array($grupo_admin, array(3,4,5,7,8))) {
									echo "</div>";
									echo "</div>";
									echo "</div>";
								}
								echo "<div class='trinta_2'>";
									if (in_array($login_admin, array(5205,4789,8527)) || ($analista_hd == 'sim' && $login_admin != 586) || !in_array($grupo_admin, array(3,4,5,7,8))) {
										include_once("icone_pendencia_helpdesk.php");
									} 
								echo "</div>";
								include "rodape.php";								
								exit;
						}elseif(!empty($data_termino) or pg_num_rows($res) == 0 ){
								echo "<div class='inicio_trabalho'><center>Dar início de trabalho para continuar<br/><input type='button' onclick='analise.inicioTrabalho($hd_chamado)' value='Início do Trabalho' />";
								echo "&nbsp;";
								echo "<button type='button' onclick='window.location=\"adm_chamado_detalhe.php?hd_chamado=$hd_chamado&consultar=sim\"'>Consultar</button>";
								echo "&nbsp;";
								echo "<input type='button' onclick='window.location=\"adm_chamado_detalhe.php?hd_chamado=$hd_chamado&transferir=sim\"' value=' Transferir ' /></center></div>";
								echo "</div>";
								echo "<div class='trinta_2'>";
									if (in_array($login_admin, array(5205,4789,8527)) || ($analista_hd == 'sim' && $login_admin != 586) || !in_array($grupo_admin, array(3,4,5,7,8))) {
										include_once("icone_pendencia_helpdesk.php");
									} 
								echo "</div>";
								include "rodape.php";
								exit;
						}
		}
?>

	<div align="center">
		
		<input type='hidden' id='status_atual' value='<?=$status?>' />
		<ul id="menu_requisitos">

			<?php
			#Aba de Requisitos
			?>
			<li class="greenHD">
				<strong class="menuAnalise">Requisitos do Sistema</strong>

				<div style="display:none;">

					<?php
					#HD 351094
					if($atualizacaoDados):?>
					<form name="frm_requisitos" id="frm_requisitos" action="<?php echo $_SERVER['PHP_SELF'];?>?hd_chamado=<?php echo $hd_chamado;?>" method="post" enctype="multipart/form-data">
					<?php endif;?>

						<table border="0" cellpadding='2' class="table_requisitos" id="frm_req">
							<tr>
								<th valign='top' align="left">&nbsp;</th>
								<th valign='top' align="left" width="65%">Requisito</th>
								<th valign='top' width="13%">Análise</th>
								<th valign='top' width="13%">Teste</th>
								<th valign='top' width="3%">&nbsp;</th>
							</tr>
							<?php
							if(!empty($hd_chamado)){
								$sql = 'SELECT hd_chamado_requisito,requisito,analise,admin_analise,teste,admin_teste,interacao,data_requisito_aprova
										  FROM tbl_hd_chamado_requisito
										 WHERE hd_chamado = '.$hd_chamado.'
										   AND excluido = FALSE
										 ORDER BY interacao;';
								$res = pg_query($con,$sql);
								$totalRequisitos = pg_num_rows($res);
							}

							if($totalRequisitos > 0):?>

								<?php for($i=0;$i<$totalRequisitos;$i++):

									$interacaoRequisito = pg_fetch_result($res,$i,'interacao');
									$idRequisito = pg_fetch_result($res,$i,'hd_chamado_requisito');
									$textoRequisitos = pg_fetch_result($res,$i,'requisito');
									$analiseRequisitos = pg_fetch_result($res,$i,'analise');
									$testeRequisitos = pg_fetch_result($res,$i,'teste');
									$data_requisito_aprova = pg_fetch_result($res, $i, 'data_requisito_aprova');
									?>

									<tr id="requisito_<?php echo $i;?>">
										<td valign='top' align="left"><?php echo $interacaoRequisito;?></td>
										<td>
											<input type="hidden" name="idRequisitos[]" value="<?php echo $idRequisito;?>" />
											<input type="hidden" name="requisitos[]" value="<?php echo $totalRequisitos;?>" />
											<?php echo nl2br($textoRequisitos);?>
										</td>
										<td align="center">

											<select name="analiseRequisitos[]">
												<option value="0" <?php echo ($analiseRequisitos=='f') ? 'selected="selected"' : null;?>>Não</option>
												<option value="1" <?php echo ($analiseRequisitos=='t') ? 'selected="selected"' : null;?>>Sim</option>
											</select>
										</td>
										<td align="center">
											<select name="testeRequisitos[]">
												<option value="0" <?php echo (!$testeRequisitos=='f') ? 'selected="selected"' : null;?>>Não</option>
												<option value="1" <?php echo ($testeRequisitos=='t') ? 'selected="selected"' : null;?>>Sim</option>
											</select>
										</td>
										<td valign="middle" align="center">
											<?php
											#HD 351094
											if($atualizacaoDados and !$data_requisito_aprova):?>
											<a href="javascript:void(0)" class="xAnalise" onclick="analise.deleteRequisito(<?php echo $idRequisito;?>,<?php echo $i;?>)"> X </a>
											<?php endif;?>
										</td>
									</tr>
<?php
                                    $anexos = getAnexosRequisitos($hd_chamado, $interacaoRequisito);
                                    if($anexos !== false){
?>
                                    <tr>
                                        <td>Anexos:</td>
                                        <td > <a href="<?=$anexos?>">Anexo</a></td>
                                    </tr>
<?php
                                    }
									$sqlAdmin = 'SELECT tbl_admin.nome_completo
												  FROM tbl_hd_chamado_requisito
											 LEFT JOIN tbl_admin ON (tbl_admin.admin = tbl_hd_chamado_requisito.admin)
												 WHERE hd_chamado_requisito = '.$idRequisito;

									$resAdmin = pg_query($con,$sqlAdmin);
									$admin_requisito = pg_fetch_result($resAdmin,0,'nome_completo');
									if($admin_requisito):
										?>
										<tr id="admin_requisito_<?php echo $i;?>">
											<td colspan="5" align="right" class="admin">Admin Responsável: <?php echo $admin_requisito;?></td>
										</tr>
									<?php endif;
									?>
								<?php endfor;?>

							<?php else: ?>

								<?php $i=0;?>
								<tr class="naoRequisitos">
									<td colspan="4" align="center" class="sub_label">
										Nenhum Requisito Cadastrado
									</td>
								</tr>

							<?php endif;?>

						</table>

					<?php

					#HD 351094
					if($atualizacaoDados):
						$maiorInteracao = getMaiorInteracao('tbl_hd_chamado_requisito',$hd_chamado);
						?>

						<input type="hidden" id="numeroRequisito" value="<?php echo ($maiorInteracao+1);?>" />
						<input type="hidden" name="chamado" value="<?php echo $hd_chamado;?>" />

						[ <a href="javascript:void(0)" onclick="analise.addRequisito()">Adicionar Requisito</a> ]

						<div class="salvarAnalise">
							<input type="hidden" name="aba_post" value="req1" />
							<input type="submit" name="salvar" value="Salvar">
						</div>

					</form>
					<?php endif;?>

					<?php
					$sql = 'SELECT hd_chamado_requisito,requisito,analise,admin_analise,teste,admin_teste,interacao
							  FROM tbl_hd_chamado_requisito
							 WHERE hd_chamado = '.$hd_chamado.'
							   AND excluido = TRUE
							 ORDER BY interacao;';
					$res = pg_query($con,$sql);
					$totalRequisitos = pg_num_rows($res);
					if($totalRequisitos > 0):?>

						[ <a href="javascript:void(0)" onclick="analise.showData('requisitosExcluidos')">Requisitos Excluídos</a> ]

						<div id="requisitosExcluidos" style="display:none;">

							<table border='0' cellpadding='2' class="table_analise">
								<tr>
									<th valign='top' align="left">&nbsp;</th>
									<th valign='top' align="left" width="65%">Requisito</th>
									<th valign='top' width="15%">Análise</th>
									<th valign='top' width="15%">Teste</th>
								</tr>

<?php
								for($i=0;$i<$totalRequisitos;$i++){
									$hd_chamado_requisito = pg_fetch_result($res,$i,'hd_chamado_requisito');
									$interacaoRequisito = pg_fetch_result($res,$i,'interacao');
									$textoRequisitos = pg_fetch_result($res,$i,'requisito');
									$analiseRequisitos = pg_fetch_result($res,$i,'analise');
									$testeRequisitos = pg_fetch_result($res,$i,'teste');
									?>

									<tr>
										<td>
											<?php echo $interacaoRequisito;?>
										</td>
										<td>
											<?php echo nl2br($textoRequisitos);?>
										</td>
										<td align="center">
											<?php echo ($analiseRequisitos=='f') ? 'Não' : 'Sim';?>
										</td>
										<td align="center">
											<?php echo (!$testeRequisitos=='f') ? 'Não' : 'Sim';?>
										</td>
									</tr>

									<?php
									$sqlAdmin = 'SELECT tbl_admin.nome_completo
												  FROM tbl_hd_chamado_requisito
											 LEFT JOIN tbl_admin ON (tbl_admin.admin = tbl_hd_chamado_requisito.admin)
												 WHERE hd_chamado_requisito = '.$hd_chamado_requisito;

									$resAdmin = pg_query($con,$sqlAdmin);
									$admin_requisito = pg_fetch_result($resAdmin,0,'nome_completo');
									if($admin_requisito){
?>
										<tr>
											<td colspan="5" align="right" class="admin">Admin Responsável: <?php echo $admin_requisito;?></td>
										</tr>
<?php
                                    }
                                    $anexos = getAnexosRequisitos($hd_chamado, $maiorInteracao);
                                    if($anexos !== false){
?>
                                        <tr>
                                            <td>Anexos:</td>
                                            <td > <a href="<?=$anexos?>">Anexo</a></td>
                                        </tr>
<?php
                                    }
                                }


?>
                           </table>
						</div>
					<?php endif;?>

				</div>
			</li>

			<?php
			#Aba de Análise
			?>
			<li class="yellowHD">
				<strong class="menuAnalise">Análise</strong>
				<div style="display:none;">

					<?php
					#HD 351094
					if($atualizacaoDados):?>
					<form name="frm_analise" id="frm_analise" action="<? echo $_SERVER['PHP_SELF'];?>?hd_chamado=<?php echo $hd_chamado;?>" method="post" onsubmit="if(document.frm_analise.submetido.value==''){document.frm_analise.submetido.value='1';document.frm_analise.submit()}else{ alert('Aguarde a submissão do formulário'); return false;}">
					<?php endif;?>

						<input type="hidden" name="submetido" value=""/>

						<div>
							<p class="titulo_cab"><strong>Texto Análise</strong></p>
							<textarea name="analiseTexto" id="analiseTexto" class="textareaAnalise" cols="86" rows="5"><?php echo $analiseTexto;?></textarea>
						</div>

						<table border='0' cellpadding='2' class="table_requisitos" id="frm_ana">
							<tr>
								<th valign='top' align="left">&nbsp;</th>
								<th valign='top' align="left" width="65%">Análise</th>
								<th valign='top' width="13%">Desenvolvimento</th>
								<th valign='top' width="13%">Teste</th>
								<th valign='top' width="3%">&nbsp;</th>
							</tr>
							<?php
							$sql = 'SELECT hd_chamado_analise,analise,desenvolvimento,admin_desenvolvimento,teste,admin_teste,interacao
									  FROM tbl_hd_chamado_analise
									 WHERE hd_chamado = '.$hd_chamado.'
									   AND excluido = FALSE
									 ORDER BY interacao;';
							$res = pg_query($con,$sql);
							$totalAnalises = pg_num_rows($res);
							?>

							<?php if($totalAnalises > 0):
								$valor1 = array('<','>');
								$valor2 = array('&lt;','&gt;');

							?>

								<?php for($i=0;$i<$totalAnalises;$i++): ?>

									<?php
									$idAnalise = pg_fetch_result($res,$i,'hd_chamado_analise');
									$interacaoAnalise = pg_fetch_result($res,$i,'interacao');
									$textoAnalise = pg_fetch_result($res,$i,'analise');
									$desenvAnalise = pg_fetch_result($res,$i,'desenvolvimento');
									$testeAnalise = pg_fetch_result($res,$i,'teste');
									?>

									<tr id="analise_<?php echo $i;?>">
										<td><?php echo $interacaoAnalise;?></td>
										<td>
											<input type="hidden" name="idAnalise[]" value="<?php echo $idAnalise;?>" />
											<input type="hidden" name="analises[]" value="<?php echo $totalAnalises;?>" />
											<div style="width:450px; ">
												<?php
													echo nl2br(str_replace($valor1,$valor2,$textoAnalise));
													//echo nl2br(htmlspecialchars($textoAnalise, ENT_QUOTES));
												?>
											</div>
										</td>
										<td align="center">
											<select name="desenvAnalise[]">
												<option value="0" <?php echo ($desenvAnalise=='f') ? 'selected="selected"' : null;?>>Não</option>
												<option value="1" <?php echo ($desenvAnalise=='t') ? 'selected="selected"' : null;?>>Sim</option>
											</select>
										</td>
										<td align="center">
											<select name="testeAnalise[]">
												<option value="0" <?php echo ($testevAnalise=='f') ? 'selected="selected"' : null;?>>Não</option>
												<option value="1" <?php echo ($testeAnalise=='t') ? 'selected="selected"' : null;?>>Sim</option>
											</select>
										</td>
										<td valign="middle" align="center">
											<?php
											#HD 351094
											if($atualizacaoDados):?>
											<a href="javascript:void(0)" class="xAnalise" onclick="analise.deleteAnalise(<?php echo $idAnalise;?>,<?php echo $i;?>)"> X </a>
											<?php endif;?>
										</td>
									</tr>
									<?php
									$sqlAdmin = 'SELECT tbl_admin.nome_completo
												  FROM tbl_hd_chamado_analise
											 LEFT JOIN tbl_admin ON (tbl_admin.admin = tbl_hd_chamado_analise.admin)
												 WHERE hd_chamado_analise = '.$idAnalise;

									$resAdmin = pg_query($con,$sqlAdmin);
									$admin_analise = pg_fetch_result($resAdmin,0,'nome_completo');
									if($admin_analise):
										?>
										<tr id="admin_analise_<?php echo $i;?>">
											<td colspan="5" align="right" class="admin">Admin Responsável: <?php echo $admin_analise;?></td>
										</tr>
									<?php endif;
									?>
								<?php endfor;?>

							<?php else: ?>
								<?php $i=0;?>
								<tr class="naoAnalise">
									<td colspan="4" align="center" class="sub_label">
										Nenhuma Análise Cadastrada
									</td>
								</tr>
							<?php endif;?>

						</table>

						<?php
						#HD 351094
						if($atualizacaoDados):
							$maiorInteracao = getMaiorInteracao('tbl_hd_chamado_analise',$hd_chamado);
							?>
							<input type="hidden" id="numeroAnalise" value="<?php echo ($maiorInteracao+1);?>" />
							[ <a href="javascript:void(0)" onclick="analise.addAnalise()">Adicionar Análise</a> ]
						<?php endif;?>

						<div>
							<p class="titulo_cab"><strong>Plano de Teste</strong></p>
							<textarea name="plano_teste" id="plano_teste" class="textareaAnalise" cols="86" rows="5"><?php echo $plano_teste;?></textarea>
							<?php
									$sqlAdmin = 'SELECT tbl_admin.nome_completo
												  FROM tbl_hd_chamado
											 LEFT JOIN tbl_admin ON (tbl_admin.admin = tbl_hd_chamado.admin_plano_teste)
												 WHERE tbl_hd_chamado.hd_chamado = '.$hd_chamado;

									$resAdmin = pg_query($con,$sqlAdmin);
									$admin_plano_teste = pg_fetch_result($resAdmin,0,'nome_completo');
									if($admin_plano_teste):
										?>
											<p align="right" class="admin">Admin Responsável: <?php echo $admin_plano_teste;?></p>

							<?php endif;?>
						</div>
					<?php
					#HD 351094
					if($atualizacaoDados):?>
						<div class="salvarAnalise">
							<input type="hidden" name="aba_post" value="analise1" />
							<input type="hidden" name="chamado" value="<?php echo $hd_chamado;?>" />
							<input type="submit" name="salvar" value="Salvar" />
						</div>
					</form>
					<?php endif;?>


					<?php
					#Análises excluídas
					$sql = 'SELECT hd_chamado_analise,analise,desenvolvimento,admin_desenvolvimento,teste,admin_teste,interacao
							  FROM tbl_hd_chamado_analise
							 WHERE hd_chamado = '.$hd_chamado.'
							   AND excluido = TRUE
							 ORDER BY interacao;';
					$res = pg_query($con,$sql);
					$totalAnalises = pg_num_rows($res);
					?>

					<?php if($totalAnalises > 0):?>

						[ <a href="javascript:void(0)" onclick="analise.showData('analiseExcluidas')">Análises Excluídas</a> ]

						<div id="analiseExcluidas" style="display:none">

							<table border='0' cellpadding='2' class="table_analise">

								<tr>
									<th valign='top' align="left">&nbsp;</th>
									<th valign='top' align="left" width="65%">Análise</th>
									<th valign='top' width="13%">Desenvolvimento</th>
									<th valign='top' width="13%">Teste</th>
								</tr>

								<?php for($i=0;$i<$totalAnalises;$i++): ?>

									<?php
									$interacaoAnalise = pg_fetch_result($res,$i,'interacao');
									$idAnalise = pg_fetch_result($res,$i,'hd_chamado_analise');
									$textoAnalise = pg_fetch_result($res,$i,'analise');
									$desenvAnalise = pg_fetch_result($res,$i,'desenvolvimento');
									$testeAnalise = pg_fetch_result($res,$i,'teste');
									?>

									<tr>
										<td><?php echo $interacaoAnalise;?></td>
										<td>
											<?php echo nl2br($textoAnalise);?>
										</td>
										<td align="center">
											<?php echo ($desenvAnalise=='f') ? 'Não' : 'Sim'; ?>
										</td>
										<td align="center">
											<?php echo ($testevAnalise=='f') ? 'Não' : 'Sim'; ?>
										</td>
									</tr>
									<?php
									$sqlAdmin = 'SELECT tbl_admin.nome_completo
												  FROM tbl_hd_chamado_analise
											 LEFT JOIN tbl_admin ON (tbl_admin.admin = tbl_hd_chamado_analise.admin)
												 WHERE hd_chamado_analise = '.$idAnalise;

									$resAdmin = pg_query($con,$sqlAdmin);
									$admin_analise = pg_fetch_result($resAdmin,0,'nome_completo');
									if($admin_analise):
										?>
										<tr id="admin_analise_<?php echo $i;?>">
											<td colspan="5" align="right" class="admin">Admin Responsável: <?php echo $admin_analise;?></td>
										</tr>
									<?php endif;
									?>
								<?php endfor;?>

							</table>
						</div>
					<?php endif;?>

				</div>
			</li>

			<?php
			#Aba de Desenvolvimento
			?>
			<li class="brownHD">
				<strong class="menuAnalise">Desenvolvimento</strong>
				<div style="display:none;">

					<?php
					#HD 351094
					if($atualizacaoDados):?>
					<form name='frm_analise_dados' id="frm_analise_dados" action="<? echo $_SERVER['PHP_SELF'];?>?hd_chamado=<?php echo $hd_chamado;?>" method='post'  onsubmit="return analise.enviaFormulario(this);return false;">
					<?php endif;?>

						<div>
							<p class="titulo_cab"><strong>Procedimento de Teste</strong></p>
							<textarea name="procedimento_teste" id="procedimento_teste" class="textareaEfetivacao" cols="86" rows="10"><?php echo $procedimento_teste;?></textarea>
							<?php
									$sqlAdmin = 'SELECT tbl_admin.nome_completo
												  FROM tbl_hd_chamado
											 LEFT JOIN tbl_admin ON (tbl_admin.admin = tbl_hd_chamado.admin_procedimento_teste)
												 WHERE tbl_hd_chamado.hd_chamado = '.$hd_chamado;

									$resAdmin = pg_query($con,$sqlAdmin);
									$admin_procedimento = pg_fetch_result($resAdmin,0,'nome_completo');
									if($admin_procedimento):
										?>
											<p align="right" class="admin">Admin Responsável: <?php echo $admin_procedimento;?></p>

									<?php endif;?>
						</div>

						<div>
							<p class="titulo_cab"><strong>Comentários do Desenvolvedor</strong></p>
							<textarea name="comentario_desenvolvedor" id="comentario_desenvolvedor" class="textareaEfetivacao" cols="86" rows="10"><?php echo $comentario_desenvolvedor;?></textarea>
						</div>

					<?php
					#HD 351094
					if($atualizacaoDados):?>
						<div class="salvarAnalise">
							<input type="hidden" name="aba_post" value="teste1" />
							<input type="hidden" name="chamado" value="<?php echo $hd_chamado;?>" />
							<input type="submit" name="salvar" value="Salvar" />
						</div>
					</form>
					<?php endif;?>

				</div>
			</li>

			<?php
			#Aba de Melhorias
			?>
			<li class="orangeHD">
				<strong class="menuAnalise">Melhorias</strong>
				<div style="display:none;">
					<?php
					#HD 351094
					if($atualizacaoDados):?>
					<form name='frm_melhorias' id="frm_melhorias" action="<? echo $_SERVER['PHP_SELF'];?>?hd_chamado=<?php echo $hd_chamado;?>" method='post' onsubmit="return analise.enviaFormulario(this);">
					<?php endif;?>
						<!-- <div>
							<p class="titulo_cab"><strong>Procedimento de Melhorias</strong></p>
							<textarea name="procedimento_melhorias" id="procedimento_melhorias" class="textareaMelhorias" cols="86" rows="20"><?php //echo $melhoriaTexto;?></textarea>
						</div> -->
						<table border='0' cellpadding='2' class="table_melhorias" id="melhorias_frm">
							<tr>
								<th valign='top' align="left" width="75%">Descrição Melhoria</th>
								<th valign='top' width="25%">Qtde Horas</th>
							</tr>
							<?php
							$sql = 'SELECT hd_chamado_melhoria,
											qtde_horas,
											interacao
									  FROM tbl_hd_chamado_melhoria
									 WHERE hd_chamado = '.$hd_chamado.'
									 ORDER BY hd_chamado_melhoria;';
							$res = pg_query($con,$sql);
							$totalMelhorias = pg_num_rows($res);

							if ($totalMelhorias > 0){
								$valor1_m = array('<','>');
								$valor2_m = array('&lt;','&gt;');

								for ($y=0; $y < $totalMelhorias ; $y++){
									$idMelhorias = pg_fetch_result($res, $y, hd_chamado_melhoria);
									$dtdeHorasMelhorias = pg_fetch_result($res, $y, qtde_horas);
									$interacaoMelhorias = pg_fetch_result($res, $y, interacao);

									$sql_analista = " SELECT admin, nome_completo, grupo_admin
									                                  FROM tbl_admin
									                                 WHERE tbl_admin.fabrica =  10
									                                   AND ativo             IS TRUE
									                                   --AND (grupo_admin       IN(1) OR admin = 5992)
									                                   AND grupo_admin       IN(1)
									                                   AND admin = $login_admin
									                                 ORDER BY tbl_admin.nome_completo;";
								       $res_anlista= pg_query($con,$sql_analista);
								       //echo nl2br($sql_analista);
								       if (pg_num_rows($res_anlista) > 0) {
								       	$readonly_melhorias = '';
								       }else{
								       	$readonly_melhorias = "readonly='true'";
								       }
									?>
									<tr id="melhorias_<?php echo $y;?>">
										<td>
											<!-- <input type="hidden" name="idMelhorias[]" value="<?php //echo $idMelhorias;?>"/> -->

											<div style="width:450px; ">
												<?php
												echo nl2br(str_replace($valor1_m,$valor2_m,$interacaoMelhorias));
												?>
											</div>
										</td>
										<td align="center">
											<?php
											if (empty($dtdeHorasMelhorias)) {?>
												<input type="hidden" name="idMelhorias[]" value="<?php echo $idMelhorias;?>"/>
												<input type="hidden" name="melhorias[]" value="<?php echo $interacaoMelhorias;?>"/>
												<input id="horas_melhorias[]" class="caixa" type="text" value="" name="horas_melhorias[]" maxlength="8" size="2" <?php echo $readonly_melhorias; ?>>
											horas
											<?php
											}else{
												echo nl2br(str_replace($valor1_m,$valor2_m,$dtdeHorasMelhorias.' horas.'));
												//echo $dtdeHorasMelhorias." horas.";
											}
											?>
										</td>
									</tr>
									<?php
									$sqlAdmin = 'SELECT tbl_admin.nome_completo
												  	FROM tbl_hd_chamado_analise
											 			LEFT JOIN tbl_admin ON (tbl_admin.admin = tbl_hd_chamado_analise.admin)
												 	WHERE hd_chamado_analise = '.$idAnalise;

									$resAdmin = pg_query($con,$sqlAdmin);
									$admin_analise = pg_fetch_result($resAdmin,0,'nome_completo');
									if($admin_analise):
										?>
										<tr id="admin_analise_<?php echo $i;?>">
											<td colspan="5" align="right" class="admin">Admin Responsável: <?php echo $admin_analise;?></td>
										</tr>
									<?php endif;?>
								<?php
								}?>
							<?php
							}else{ ?>
								<?php $y=0;?>
								<tr class="naoMelhorias">
									<td colspan="4" align="center" class="sub_label">
										Nenhuma Melhoria Cadastrada
									</td>
								</tr>
							<?php
							}?>
						</table>
						<?php
						if($atualizacaoDados):
							$maiorInteracao_m = getMaiorInteracao('tbl_hd_chamado_melhoria',$hd_chamado);
							?>
							<input type="hidden" id="numeroMelhorias" value="<?php echo ($maiorInteracao_m+1);?>" />
							[ <a href="javascript:void(0)" onclick="analise.addMelhorias()">Adicionar Melhoria</a> ]
						<?php endif;?>

						<?php
						if($atualizacaoDados):?>
							<div class="salvarAnalise">
								<input type="hidden" name="aba_post" value="melhorias1" />
								<input type="hidden" name="chamado" value="<?php echo $hd_chamado;?>" />
								<input type="submit" name="salvar" value="Salvar" />
							</div>
					</form>
					<?php endif;?>
				</div>
			</li>


			<?php
			#Aba de Testes
			?>
			<li class="redHD">
				<strong class="menuAnalise">Testes</strong>
				<div style="display:none;">

					<p class="titulo_cab link" onclick="analise.showData('checkListMinimo')">
						<a href="javascript:void(0)"><strong>Check List Mínimo</strong></a>
					</p>

					<div style="display:none;" id="checkListMinimo">

						<?php
						#HD 351094
						if($atualizacaoDados):?>
						<form name='frm_check_list' id="frm_check_list" action="<? echo $_SERVER['PHP_SELF'];?>?hd_chamado=<?php echo $hd_chamado;?>" method='post' onsubmit="return analise.enviaFormulario(this);">
						<?php endif;?>
							<p class="titulo_cab"><strong>CheckList</strong></p>
							<?php

								$sql = "SELECT tbl_admin.nome_completo
										FROM tbl_hd_chamado_checklist
										JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado_checklist.admin
										WHERE tbl_hd_chamado_checklist.hd_chamado = 389311
										LIMIT 1;";
								$res = pg_query($con,$sql);
								if(pg_num_rows($res)) {

									$admin_checklist = pg_result($res,0,'nome_completo');
									echo '<p class="titulo_cab" style="font-size:12px;" id="checkRes"><b>Admin Responsável</b> ' . $admin_checklist . '</p>';

								}

							?>
							<table border='0' cellpadding='2' class="table_analise">
								<thead>
									<tr>
										<th align="left" width="85%">Item do Check List</th>
										<th width="15%">Atendido</th>
									</tr>
								</thead>
							</table>
							<?php
								$checklist_ok = array();

								$sql = 'SELECT tc.checklist,thcc.atendido
										  FROM tbl_hd_chamado_checklist AS thcc
										  JOIN tbl_checklist AS tc ON (thcc.checklist = tc.checklist)
										 WHERE thcc.hd_chamado = '.$hd_chamado.'
										   AND tc.ativo IS TRUE;';

								$res = pg_query($con,$sql);
								$totalCheckListOk = pg_num_rows($res);

								for($i=0;$i<$totalCheckListOk;$i++){
									$checklist_ok[pg_fetch_result($res,$i,'checklist')] = pg_fetch_result($res,$i,'atendido');
								}
							?>

							<?php

								$sql = "SELECT descricao,checklist_categoria
										FROM tbl_checklist_categoria
										WHERE categoria_pai IS NULL;";

								$res_cat = pg_query($con,$sql);

								for($i=0;$i<pg_num_rows($res_cat);$i++) { // laço para exibir categorias pai

									$cat_pai 		= pg_result($res_cat,$i,'checklist_categoria');
									$descricao_pai 	= pg_result($res_cat,$i,'descricao');

									echo '<ul class="pai">
											<li class="first">
												<span>'.$descricao_pai.'</span>';

									$sql_filhas = 'SELECT descricao, checklist_categoria FROM tbl_checklist_categoria
													WHERE categoria_pai = ' . $cat_pai ;

									$res_filhas = pg_query ($con,$sql_filhas);

									for($j = 0; $j < pg_num_rows($res_filhas);$j++) { //laço para pegar as sub-categorias

										$check_categoria = pg_fetch_result($res_filhas,$j,'checklist_categoria');
										$descricao = pg_fetch_result($res_filhas,$j,'descricao');

										echo '<ul style="display:none;">
												<li class="node">
													<span>'.$descricao.'</span>
													<ul style="display:none;">';
										$sql_itens_check = 'SELECT checklist,item_verificar
															FROM tbl_checklist
															JOIN tbl_checklist_categoria USING(checklist_categoria)
															WHERE ativo IS TRUE
															AND tbl_checklist_categoria.checklist_categoria = ' .$check_categoria. '
															ORDER BY checklist;';
										$res_item_check = pg_query($con,$sql_itens_check);
								?>

										<?php for($k=0;$k<pg_num_rows($res_item_check);$k++): /* laço para mostrar itens do checklist */
												$checklist_id = pg_result($res_item_check,$k,'checklist');
												$checklist_nome = pg_result($res_item_check,$k,'item_verificar');
										?>
														<li>
															<input type="hidden" name="checklist[]" value="<?php echo $checklist_id;?>" />
															<?php echo $checklist_nome;?>
															<span class="status">
																<select name="atendido[]" onchange="atualizaChecklist(this,<?php echo $checklist_id;?>)">
																	<option value="NULL" <?php echo (!$checklist_ok[$checklist_id]) ? 'selected="selected"' : null;?>>Não aplicável</option>
																	<option value="TRUE" <?php echo ($checklist_ok[$checklist_id] == 't') ? 'selected="selected"' : null;?>>Sim</option>
																	<option value="FALSE" <?php echo ($checklist_ok[$checklist_id] == 'f') ? 'selected="selected"' : null;?>>Não</option>
																</select>
															</span>
														</li>


										<?php endfor; ?>
													</ul>
												</li>
											</ul>
							<?php
									} // endfor subcategorias
									echo '</ul>';
								} // end for categorias pai
							?>

						<?php
						#HD 351094
						if($atualizacaoDados):?>
							<!--
							<div class="salvarAnalise">
								<input type="hidden" name="aba_post" value="check1" />
								<input type="hidden" name="chamado" value="<?php echo $hd_chamado;?>" />
								<input type="submit" name="salvar" value="Salvar" />
							</div>
							-->
						</form>
						<?php endif;?>

					</div>

					<?php
					#Requisitos dos Testes
					$sql = 'SELECT hd_chamado_requisito,requisito,analise,admin_analise,teste,admin_teste,interacao
							  FROM tbl_hd_chamado_requisito
							 WHERE hd_chamado = '.$hd_chamado.'
							   AND excluido = FALSE
							   AND teste = TRUE;';
					$res = pg_query($con,$sql);
					$totalRequisitos = pg_num_rows($res);
					?>

					<?php if($totalRequisitos > 0):?>

						<p class="titulo_cab link" onclick="analise.showData('requisitosTestes')">
							<a href="javascript:void(0)"><strong>Requisitos em Teste</strong></a>
						</p>

						<div id="requisitosTestes" style="display:none">
							<?php
							#HD 351094
							if($atualizacaoDados):?>
							<form name='frm_analise_teste' id="frm_analise_teste" action="<? echo $_SERVER['PHP_SELF'];?>?hd_chamado=<?php echo $hd_chamado;?>" method='post' onsubmit="return analise.enviaFormulario(this);">
							<?php endif;?>
								<table border='0' cellpadding='2' class="table_analise">
									<tr>
										<th valign='top' align="left" width="3%">&nbsp;</th>
										<th valign='top' align="left">Requisito</th>
										<th valign='top' width="15%">Análise</th>
										<th valign='top' width="15%">Teste</th>
									</tr>

									<?php for($i=0;$i<$totalRequisitos;$i++): ?>

										<?php
										$interacaoRequisito = pg_fetch_result($res,$i,'interacao');
										$idRequisito = pg_fetch_result($res,$i,'hd_chamado_requisito');
										$textoRequisitos = pg_fetch_result($res,$i,'requisito');
										$analiseRequisitos = pg_fetch_result($res,$i,'analise');
										$testeRequisitos = pg_fetch_result($res,$i,'teste');
										?>

										<tr>
											<td><?php echo $interacaoRequisito;?></td>
											<td>
												<input type="hidden" name="idRequisitos[]" value="<?php echo $idRequisito;?>" />
												<input type="hidden" name="requisitos[]" value="<?php echo $textoRequisitos;?>" />
												<?php echo nl2br($textoRequisitos);?>
											</td>
											<td align="center">
												<select name="analiseRequisitos[]">
												<option value="0" <?php echo ($analiseRequisitos=='f') ? 'selected="selected"' : null;?>>Não</option>
												<option value="1" <?php echo ($analiseRequisitos=='t') ? 'selected="selected"' : null;?>>Sim</option>
											</select>
											</td>
											<td align="center">
												<select name="testeRequisitos[]">
													<option value="0" <?php echo (!$testeRequisitos=='f') ? 'selected="selected"' : null;?>>Não</option>
													<option value="1" <?php echo ($testeRequisitos=='t') ? 'selected="selected"' : null;?>>Sim</option>
												</select>
											</td>
										</tr>
									</tr>

									<?php endfor;?>

								</table>
							<?php
							#HD 351094
							if($atualizacaoDados):?>
								<div class="salvarAnalise">
									<input type="hidden" name="aba_post" value="requisitoTeste1" />
									<input type="hidden" name="chamado" value="<?php echo $hd_chamado;?>" />
									<input type="submit" name="salvar" value="Salvar" />
								</div>
							</form>
							<?php endif;?>
						</div>
					<?php endif;?>

					<?php
					#Analises dos Testes
					?>
					<?php
					$sql = 'SELECT hd_chamado_analise,analise,desenvolvimento,admin_desenvolvimento,teste,admin_teste,interacao
							  FROM tbl_hd_chamado_analise
							 WHERE hd_chamado = '.$hd_chamado.'
							   AND excluido = FALSE
							   AND teste = TRUE';
					$res = pg_query($con,$sql);
					$totalAnalises = pg_num_rows($res);
					?>

					<?php if($totalAnalises > 0):?>

						<p class="titulo_cab link" onclick="analise.showData('analisesTestes')">
							<a href="javascript:void(0)"><strong>Análises em Teste</strong></a>
						</p>

						<div id="analisesTestes" style="display:none">
							<?php
							#HD 351094
							if($atualizacaoDados):?>
							<form name='frm_requisitos_teste' id="frm_requisitos_teste" action="<? echo $_SERVER['PHP_SELF'];?>?hd_chamado=<?php echo $hd_chamado;?>" method='post' onsubmit="return analise.enviaFormulario(this);return false;">
							<?php endif;?>
								<table border='0' cellpadding='2' class="table_analise">
									<tr>
										<th width="3%">&nbsp;</th>
										<th valign='top' align="left">Análise</th>
										<th valign='top' width="15%">Desenvolvimento</th>
										<th valign='top' width="15%">Teste</th>
									</tr>

									<?php for($i=0;$i<$totalAnalises;$i++): ?>

										<?php
										$idAnalise = pg_fetch_result($res,$i,'hd_chamado_analise');
										$interacaoAnalise = pg_fetch_result($res,$i,'interacao');
										$textoAnalise = pg_fetch_result($res,$i,'analise');
										$desenvAnalise = pg_fetch_result($res,$i,'desenvolvimento');
										$testeAnalise = pg_fetch_result($res,$i,'teste');
										?>

										<tr>
											<td><?php echo $interacaoAnalise;?></td>
											<td>
												<input type="hidden" name="idAnalise[]" value="<?php echo $idAnalise;?>" />
												<input type="hidden" name="analises[]" value="<?php echo $textoAnalise;?>" />
												<?php echo nl2br($textoAnalise);?>
											</td>
											<td align="center">
												<select name="desenvAnalise[]">
													<option value="0" <?php echo ($desenvAnalise=='f') ? 'selected="selected"' : null;?>>Não</option>
													<option value="1" <?php echo ($desenvAnalise=='t') ? 'selected="selected"' : null;?>>Sim</option>
												</select>
											</td>
											<td align="center">
												<select name="testeAnalise[]">
													<option value="0" <?php echo ($testevAnalise=='f') ? 'selected="selected"' : null;?>>Não</option>
													<option value="1" <?php echo ($testeAnalise=='t') ? 'selected="selected"' : null;?>>Sim</option>
												</select>
											</td>
										</tr>

									<?php endfor;?>

								</table>

							<?php
							#HD 351094
							if($atualizacaoDados):?>
								<div class="salvarAnalise">
									<input type="hidden" name="aba_post" value="analiseTeste1" />
									<input type="hidden" name="chamado" value="<?php echo $hd_chamado;?>" />
									<input type="submit" name="salvar" value="Salvar" />
								</div>
							</form>
							<?php endif;?>
						</div>
					<?php endif;?>

					<?php
					#Corrreções
					?>
					<p class="titulo_cab link" onclick="analise.showData('correcoes_lista')">
						<a href="javascript:void(0)"><strong>Correções Análise</strong></a>
					</p>
					<div id="correcoes_lista" style="display:none;">

						<?php
						$sql = 'SELECT DISTINCT(rodada),rodada_finalizada
								  FROM tbl_hd_chamado_correcao
								 WHERE hd_chamado = '.$hd_chamado.';';
						$res = pg_query($con,$sql);
						$totalRodada = pg_num_rows($res);

						$i=0;
						?>

						<?php if($totalRodada>0):?>

							<?php for($i=0;$i<$totalRodada;$i++): ?>

								<?php
								$rodadaCorrecao = pg_fetch_result($res,$i,'rodada');
								$rodadaFinalizadaCorrecao = pg_fetch_result($res,$i,'rodada_finalizada');

								$textoRodada = ($rodadaFinalizadaCorrecao == 't') ? '( Finalizada )' : null;

								$style = ($i!=($totalRodada-1)) ? 'style="display:none;"' : null;
								?>

								<?php if($rodadaFinalizadaCorrecao == 't'):?>

									<p class="titulo_cab link" onclick="analise.showData('rodada_lista_<?php echo $rodadaCorrecao;?>')">
										<a href="javascript:void(0)"><strong>Rodada <?php echo $rodadaCorrecao;?> <?php echo $textoRodada; ?></strong></a>
									</p>

									<div id="rodada_lista_<?php echo $rodadaCorrecao;?>" <?php echo $style;?> class='rodada'>

										<table border='0' cellpadding='2' class="table_analise">
											<tr>
												<th align="left">&nbsp;</th>
												<th align="left" width="35%">
													Descrição
												</th>
												<th align="left" width="35%">
													Análise
												</th>
												<th align="left">
													Gravidade
												</th>
												<th align="left">
													Atendido
												</th>
											</tr>

											<?php
											$sql = 'SELECT hd_chamado_correcao,hd_chamado,rodada,descricao,analise,
														   gravidade,atendido,admin_atendido,rodada_finalizada,interacao
													  FROM tbl_hd_chamado_correcao
													 WHERE hd_chamado = '.$hd_chamado.'
													   AND rodada = '.$rodadaCorrecao.'
													 ORDER BY interacao;';
											$res2 = pg_query($con,$sql);
											$totalCorrecao = pg_num_rows($res2);
											?>

											<?php if($totalCorrecao > 0):?>

												<?php for($j=0;$j<$totalCorrecao;$j++): ?>

													<?php
													$idCorrecao = pg_fetch_result($res2,$j,'hd_chamado_correcao');
													$interacaoCorrecao = pg_fetch_result($res2,$j,'interacao');
													$descricaoCorrecao = pg_fetch_result($res2,$j,'descricao');
													$analiseCorrecao = pg_fetch_result($res2,$j,'analise');
													$gravidadeCorrecao = pg_fetch_result($res2,$j,'gravidade');
													$atendidoCorrecao = pg_fetch_result($res2,$j,'atendido');
													$admin_atendido = pg_fetch_result($res2,$j,'admin_atendido');

													if($gravidadeCorrecao == 1){
														$gravidadeCorrecao = 'Leve';
													}elseif($gravidadeCorrecao == 5){
														$gravidadeCorrecao = 'Normal';
													}elseif($gravidadeCorrecao == 10){
														$gravidadeCorrecao = 'Grave';
													}

													if($atendidoCorrecao == 't'){
														$atendidoCorrecao = 'Sim';
													}elseif($atendidoCorrecao == 'f'){
														$atendidoCorrecao = 'Não';
													}else{
														$atendidoCorrecao = 'Não Aplicável';
													}
													?>

													<tr>
														<td>
															<?php echo $interacaoCorrecao;?>
														</td>
														<td>
															<?php echo nl2br($descricaoCorrecao);?>
														</td>
														<td>
															<?php echo nl2br($analiseCorrecao);?>
														</td>
														<td align="center">
															<?php echo $gravidadeCorrecao;?>
														</td>
														<td>
															<?php echo $atendidoCorrecao;?>
														</td>
													</tr>
													<?php
															$sqlAdmin = 'SELECT tbl_admin.nome_completo
																		  FROM tbl_hd_chamado_correcao
																	 LEFT JOIN tbl_admin ON (tbl_admin.admin = tbl_hd_chamado_correcao.admin)
																		 WHERE hd_chamado_correcao = '.$idCorrecao;

															$resAdmin = pg_query($con,$sqlAdmin);
															$admin_correcao = pg_fetch_result($resAdmin,0,'nome_completo');
															if($admin_correcao):
																?><tr><td colspan='5' align="right" class="admin">Admin Responsável: <?php echo $admin_correcao;?></td></tr>
															<?php endif;
															?>
												<?php endfor;?>

											<?php endif;?>

										</table>

									</div>

								<?php else: ?>

									<?php
									#HD 351094
									if($atualizacaoDados):?>
									<form name='frm_correcao_<?php echo $rodadaCorrecao;?>' action="<? echo $_SERVER['PHP_SELF'];?>?hd_chamado=<?php echo $hd_chamado;?>" method='post'>
									<?endif;?>

										<p class="titulo_cab link" onclick="analise.showData('rodada_lista_<?php echo $rodadaCorrecao;?>')">
											<a href="javascript:void(0)"><strong>Rodada <?php echo $rodadaCorrecao;?> <?php echo $textoRodada; ?></strong></a>
										</p>

										<div id="rodada_lista_<?php echo $rodadaCorrecao;?>" <?php echo $style;?> class='rodada'>
											<table border='0' cellpadding='2' class="table_analise" id="tbl_correcao_<?php echo $rodadaCorrecao;?>">
												<tr>
													<th>&nbsp;</th>
													<th align="left" width="35%">
														Descrição
													</th>
													<th align="left"  width="35%">
														Análise
													</th>
													<th align="left">
														Gravidade
													</th>
													<th align="left">
														Atendido
													</th>
													<th align="left">
														&nbsp;
													</th>
												</tr>

												<?php
												$sql = 'SELECT hd_chamado_correcao,hd_chamado,rodada,descricao,analise,
															   gravidade,atendido,admin_atendido,rodada_finalizada,interacao
														  FROM tbl_hd_chamado_correcao
														 WHERE hd_chamado = '.$hd_chamado.'
														   AND rodada = '.$rodadaCorrecao.'
														 ORDER BY interacao;';
												$res2 = pg_query($con,$sql);
												$totalCorrecao = pg_num_rows($res2);
												?>

												<?php if($totalCorrecao > 0):?>

													<?php for($j=0;$j<$totalCorrecao;$j++): ?>

														<?php
														$idCorrecao = pg_fetch_result($res2,$j,'hd_chamado_correcao');
														$interacaoCorrecao = pg_fetch_result($res2,$j,'interacao');
														$descricaoCorrecao = pg_fetch_result($res2,$j,'descricao');
														$analiseCorrecao = pg_fetch_result($res2,$j,'analise');
														$gravidadeCorrecao = pg_fetch_result($res2,$j,'gravidade');
														$atendidoCorrecao = pg_fetch_result($res2,$j,'atendido');
														$admin_atendido = pg_fetch_result($res2,$j,'admin_atendido');
														?>

														<tr id="correcao_<?php echo $rodadaCorrecao;?>_<?php echo $j;?>">
															<td><?php echo $interacaoCorrecao;?></td>
															<td>
																<input type="hidden" name="idCorrecao[]" value="<?php echo $idCorrecao;?>" />
																<input type="hidden" name="rodadaCorrecao[]" value="<?php echo $rodadaCorrecao;?>" />
																<textarea name="descricaoCorrecaos[]" cols="50" rows="6"><?php echo nl2br($descricaoCorrecao);?></textarea>
															</td>
															<td>
																<textarea name="analiseCorrecaos[]" cols="50" rows="6"><?php echo nl2br($analiseCorrecao);?></textarea>
															</td>
															<td align="center">
																<select name="gravidadeCorrecaos[]">
																	<option value="1" <?php echo ($gravidadeCorrecao == 1) ? 'selected="selected"' : null;?>>Leve</option>
																	<option value="5" <?php echo ($gravidadeCorrecao == 5) ? 'selected="selected"' : null;?>>Normal</option>
																	<option value="10" <?php echo ($gravidadeCorrecao == 10) ? 'selected="selected"' : null;?>>Grave</option>
																</select>
															</td>
															<td align="center">
																<select name="atendidoCorrecaos[]">
																	<option value="NULL" <?php echo (!$atendidoCorrecao) ? 'selected="selected"' : null;?>>Não aplicável</option>
																	<option value="TRUE" <?php echo ($atendidoCorrecao == 't') ? 'selected="selected"' : null;?>>Sim</option>
																	<option value="FALSE" <?php echo ($atendidoCorrecao == 'f') ? 'selected="selected"' : null;?>>Não</option>
																</select>
															</td>
															<td valign="middle" align="center">
																<?php
																#HD 351094
																if($atualizacaoDados):?>
																<a href="javascript:void(0)" class="xAnalise" onclick="analise.deleteCorrecao(<?php echo $idCorrecao;?>,<?php echo $rodadaCorrecao;?>,<?php echo $j;?>)"> X </a>
																<?php endif;?>
															</td>
														</tr>

													<?php endfor;?>

												<?php endif;?>

											</table>

									<?php
									#HD 351094
									if($atualizacaoDados):
										$maiorInteracao = getMaiorInteracao('tbl_hd_chamado_correcao',$hd_chamado,$rodadaCorrecao);
										?>
											<p class="finalizarCorrecoes">
												<label>
													<input type="checkbox" name="rodadaFinalizada" value="TRUE" />
													Finalizar Rodada de Correções
												</label>
											</p>

											<div class="salvarAnalise">
												<input type="hidden" name="aba_post" value="correcao1" />
												<input type="hidden" name="rodada" value="<?php echo $rodadaCorrecao;?>" />
												<input type="hidden" name="chamado" value="<?php echo $hd_chamado;?>" />
												<input type="submit" name="salvar" value="Salvar" />
											</div>

											<input type="hidden" id="numeroCorrecao_<?php echo $rodadaCorrecao;?>" value="<?php echo ($maiorInteracao+1);?>" />
											[ <a href="javascript:void(0)" onclick="analise.addCorrecao(<?php echo $rodadaCorrecao;?>)">Adicionar Correção</a> ]

										</div>

									</form>
									<?php endif;?>

								<?php endif;?>

							<?php endfor;?>

						<?php else:?>

							<table border='0' cellpadding='2' class="table_analise" id="frm_correcao">
								<tr class="naoCorrecao">
									<td align="center" class="sub_label">
										Nenhuma Rodada Cadastrada
									</td>
								</tr>
							</table>

						<?php endif;?>

						<?php
						$sql = 'SELECT MAX(rodada) as maior_rodada
								  FROM tbl_hd_chamado_correcao
								 WHERE hd_chamado = '.$hd_chamado.';';
						$res3 = pg_query($con,$sql);
						$num3 = pg_num_rows($res3);

						$maior_rodada = pg_fetch_result($res3,0,'maior_rodada');
						$maior_rodada = ($maior_rodada) ? ($maior_rodada+1) : 1;

						#HD 351094
						if($atualizacaoDados):?>
						<div id="nova_rodada">
							<div align="center">
								<input type="hidden" id="numeroCorrecao_<?php echo $maior_rodada;?>" value="0" />
								[ <a href="javascript:void(0)" onclick="analise.addRodada(<?php echo $maior_rodada;?>,<?php echo $hd_chamado;?>)">Adicionar Nova Rodada</a> ]
							</div>
						</div>
						<?php endif;?>

					</div>

					<p class="titulo_cab link" onclick="analise.showData('correcoes_teste')">
						<a href="javascript:void(0)"><strong>Correções Teste</strong></a>
					</p>
					<div id="correcoes_teste" style="display:none;">

						<?php
						$sql = 'SELECT DISTINCT(rodada),rodada_finalizada
								  FROM tbl_hd_chamado_correcao
								 WHERE hd_chamado = '.$hd_chamado.';';
						$res = pg_query($con,$sql);
						$totalRodada = pg_num_rows($res);
						$i=0;
						?>

						<?php if($totalRodada>0):?>

							<?php for($i=0;$i<$totalRodada;$i++): ?>

								<?php
								$rodadaCorrecao = pg_fetch_result($res,$i,'rodada');
								$rodadaFinalizadaCorrecao = pg_fetch_result($res,$i,'rodada_finalizada');

								$style = ($i!=($totalRodada-1)) ? 'style="display:none;"' : null;
								$textoRodada = ($rodadaFinalizadaCorrecao == 't') ? '( Finalizada )' : null;
								?>

								<p class="titulo_cab link" onclick="analise.showData('rodada_teste_<?php echo $rodadaCorrecao;?>')">
									<a href="javascript:void(0)"><strong>Rodada <?php echo $rodadaCorrecao;?> <?php echo $textoRodada;?></strong></a>
								</p>

								<?php
								#HD 351094
								if($atualizacaoDados):?>
								<form name='frm_correcao_teste_<?php echo $rodadaCorrecao;?>' id="frm_correcao_teste_<?php echo $rodadaCorrecao;?>" action="<? echo $_SERVER['PHP_SELF'];?>?hd_chamado=<?php echo $hd_chamado;?>" method='post' onsubmit="return analise.enviaFormulario(this);">
								<?php endif;?>

									<div id="rodada_teste_<?php echo $rodadaCorrecao;?>" <?php echo $style;?> class='rodada'>
										<table border='0' cellpadding='2' width="650" class="table_analise" id="tbl_correcao_<?php echo $rodadaCorrecao;?>">
											<tr>
												<th align="left">&nbsp;</th>
												<th align="left" style="width:250px">
													Descrição
												</th>
												<th align="left" style="width:250px">
													Análise
												</th>
												<th align="left">
													Gravidade
												</th>
												<th align="left">
													Atendido
												</th>
											</tr>

	<?php

											$sql = 'SELECT hd_chamado_correcao,hd_chamado,rodada,descricao,analise,
														   gravidade,atendido,admin_atendido,rodada_finalizada,interacao
													  FROM tbl_hd_chamado_correcao
													 WHERE hd_chamado = '.$hd_chamado.'
													   AND rodada = '.$rodadaCorrecao.'
												  ORDER BY interacao;';
											$res2 = pg_query($con,$sql);
											//echo $sql;
											$totalCorrecao = pg_num_rows($res2);
											?>

											<?php if($totalCorrecao > 0):?>

												<?php for($j=0;$j<$totalCorrecao;$j++): ?>

													<?php
													$idCorrecao = pg_fetch_result($res2,$j,'hd_chamado_correcao');
													$interacaoCorrecao = pg_fetch_result($res2,$j,'interacao');
													$descricaoCorrecao = pg_fetch_result($res2,$j,'descricao');
													$analiseCorrecao = pg_fetch_result($res2,$j,'analise');
													$gravidadeCorrecao = pg_fetch_result($res2,$j,'gravidade');
													$atendidoCorrecao = pg_fetch_result($res2,$j,'atendido');
													$admin_atendido = pg_fetch_result($res2,$j,'admin_atendido');
													?>

													<tr id="correcao_teste_<?php echo $rodadaCorrecao;?>_<?php echo $j;?>">
														<td><?php echo $interacaoCorrecao;?></td>
														<td style="width:240px">
															<div style="border:0;max-width:230px;word-wrap:break-word;display:block;">
																<input type="hidden" name="idCorrecao[]" value="<?php echo $idCorrecao;?>" />
																<input type="hidden" name="rodadaCorrecao[]" value="<?php echo $rodadaCorrecao;?>" />
																<input type="hidden" name="descricaoCorrecaos[]" value="<?php echo $descricaoCorrecao;?>" />
																<?php echo $descricaoCorrecao;?>
															</div>
														</td>
														<td style="width:240px">
															<div style="border:0;max-width:230px;word-wrap:break-word;">
																<input type="hidden" name="analiseCorrecaos[]" value="<?php echo $analiseCorrecao;?>" />
																<?php echo $analiseCorrecao;?>
															</div>
														</td>
														<td align="center">
															<input type="hidden" name="gravidadeCorrecaos[]" value="<?php echo $gravidadeCorrecao;?>" />
															<?php
															if($gravidadeCorrecao == 1){
																echo 'Leve';
															}elseif($gravidadeCorrecao == 5){
																echo 'Normal';
															}elseif($gravidadeCorrecao == 10){
																echo 'Grave';
															}?>
														</td>
														<td align="center">
															<select name="atendidoCorrecaos[]">
																<option value="NULL" <?php echo (!$atendidoCorrecao) ? 'selected="selected"' : null;?>>Não aplicável</option>
																<option value="TRUE" <?php echo ($atendidoCorrecao == 't') ? 'selected="selected"' : null;?>>Sim</option>
																<option value="FALSE" <?php echo ($atendidoCorrecao == 'f') ? 'selected="selected"' : null;?>>Não</option>
															</select>
														</td>
													</tr>
													<?php
															$sqlAdmin = 'SELECT tbl_admin.nome_completo
																		  FROM tbl_hd_chamado_correcao
																	 LEFT JOIN tbl_admin ON (tbl_admin.admin = tbl_hd_chamado_correcao.admin)
																		 WHERE hd_chamado_correcao = '.$idCorrecao;

															$resAdmin = pg_query($con,$sqlAdmin);
															$admin_correcao = pg_fetch_result($resAdmin,0,'nome_completo');
															if($admin_correcao):
																?><tr><td colspan='5' align="right" class="admin">Admin Responsável: <?php echo $admin_correcao;?></td></tr>
															<?php endif;
															?>
												<?php endfor;?>

											<?php endif;?>

										</table>

									<?php
									#HD 351094
									if($atualizacaoDados):?>
										<div class="salvarAnalise">
											<input type="hidden" name="aba_post" value="correcao1" />
											<input type="hidden" name="at" value="1" />
											<input type="hidden" name="rodada" value="<?php echo $rodadaCorrecao;?>" />
											<input type="hidden" name="chamado" value="<?php echo $hd_chamado;?>" />
											<input type="submit" name="salvar" value="Salvar" />
										</div>
									<?php endif;?>

									</div>

								<?php
								#HD 351094
								if($atualizacaoDados):?>
								</form>
								<?php endif;?>

							<?php endfor;?>

						<?php else:?>

							<table border='0' cellpadding='2' class="table_analise" id="frm_correcao">
								<tr class="naoCorrecao">
									<td align="center" class="sub_label">
										Nenhuma Rodada Cadastrada
									</td>
								</tr>
							</table>

						<?php endif;?>

					</div>
						<?php
							$query_select_teste = "SELECT array_campos_adicionais FROM tbl_hd_chamado_extra WHERE hd_chamado = {$hd_chamado};";
							$result_teste = pg_query($con, $query_select_teste);
							$array_teste = pg_fetch_assoc($result_teste);
							$array_teste = $array_teste['array_campos_adicionais'];
							$array_teste = json_decode($array_teste, true);
							$teste_field = $array_teste['testes'];
						?>
						<div>
						<form name='frm_teste2' id="frm_teste2" action="<? echo $_SERVER['PHP_SELF'];?>?hd_chamado=<?php echo $hd_chamado;?>" method='post'>
							<p class="titulo_cab"><strong>Procedimento de Teste</strong></p>
							<textarea name="proc_teste" id="proc_teste" cols="86" rows="15" value=""><?= $teste_field ?></textarea>
							<div class="salvarTeste">
								<input type="hidden" name="aba_post" value="teste2" />
								<input type="hidden" name="chamado" value="<?php echo $hd_chamado;?>" />
								<input type="submit" name="salvar" value="Salvar" />
							</div>
						</form>
							<div id='procedimento_teste_ajax'><?php echo nl2br($procedimento_teste);?></div>
							<?php
									$sqlAdmin = 'SELECT tbl_admin.nome_completo
												  FROM tbl_hd_chamado
											 LEFT JOIN tbl_admin ON (tbl_admin.admin = tbl_hd_chamado.admin_procedimento_teste)
												 WHERE tbl_hd_chamado.hd_chamado = '.$hd_chamado;

									$resAdmin = pg_query($con,$sqlAdmin);
									$admin_procedimento = pg_fetch_result($resAdmin,0,'nome_completo');
									if($admin_procedimento):
										?>
											<p align="right" class="admin">Admin Responsável: <?php echo $admin_procedimento;?></p>

									<?php endif;?>
						</div>

				</div>
			</li>

			<?php
													#Aba de Orçamento
			$sql = "SELECT hora_franqueada+saldo_hora-hora_utilizada AS horas_fabrica
			FROM tbl_hd_franquia
			JOIN tbl_hd_chamado ON tbl_hd_chamado.fabrica = tbl_hd_franquia.fabrica
			WHERE hd_chamado = $hd_chamado
			AND   periodo_fim ISNULL";
			$res = pg_query($con,$sql);
			if(pg_num_rows($res) > 0){
				$horas_fabrica = pg_fetch_result($res,0,0);
			}
			?>
			<li class="purpleHD">
				<strong class="menuAnalise">Orçamento</strong>
				<div style="display:none;">

					<?php
					#HD 351094
					$totalHoras = ($taxa_abertura+$horas_suporte+$horas_telefone+$horas_analise+$prazo_horas+$horas_teste+$horas_efetivacao);

					if($atualizacaoDados):?>
					<form name='frm_orcamento' id="frm_orcamento" action="<? echo $_SERVER['PHP_SELF'];?>?hd_chamado=<?php echo $hd_chamado;?>" method='post' onsubmit="return analise.enviaFormulario(this);return false;">
					<?php endif;?>

					<? if(!empty($horas_fabrica)):?>
						<div>
							<p class="titulo_cab"><strong>Essa fábrica ainda tem <?=$horas_fabrica?> horas</strong></p>
						</div>
					<?php endif;?>
						<div>
							<p class="titulo_cab"><strong>Estimativa de Horas</strong></p>
						</div>

						<table border='0' cellpadding='2' class="table_analise" id="tbl_correcao_<?php echo $rodadaCorrecao;?>">
							<tr>
								<th align="left">Horas Suporte</th>
								<th align="left"  width="35%">
									<input type="text" size="2" maxlength="5" name="horas_suporte" value="<?php echo $horas_suporte;?>" class="caixa horas"> h
								</th>
							</tr>
							<tr>
								<th align="left">Horas Suporte Telefone</th>
								<th align="left"  width="35%">
									<input type="text" size="2" maxlength="5" name="horas_telefone" value="<?php echo $horas_telefone;?>" class="caixa horas"> h
								</th>
							</tr>
							<tr>
								<th align="left">Horas Análise</th>
								<th align="left">
									<input type="text" size="2" maxlength="5" name="horas_analise" value="<?php echo $horas_analise;?>" class="caixa horas"> h
								</th>
							</tr>
							<tr>
								<th align="left" nowrap>Hrs. Desenvol.</th>
								<th align="left">
									<input type="text" size="2" maxlength="5" name="prazo_horas" value="<?php echo $prazo_horas;?>" class="caixa horas"> h
								</th>
							</tr>
							<tr>
								<th align="left">Horas Teste</th>
								<th align="left">
									<input type="text" size="2" maxlength="5" name="horas_teste" value="<?php echo $horas_teste;?>" class="caixa horas"> h
								</th>
							</tr>
							<tr>
								<th align="left">Horas Efetivação</th>
								<th align="left">
									<input type="text" size="2" maxlength="5" name="horas_efetivacao" value="<?php echo $horas_efetivacao;?>" class="caixa horas"> h
								</th>
							</tr>
							<tr>
								<th align="left">Desconto</th>
								<th align="left">
									<input type="text" size="7" name="desconto" value="<?php echo $desconto;?>" class="caixa horas"> R$
								</th>
							</tr>
							<tr>
								<th align="left">Total de Horas</th>
								<th align="left" id="total_horas">
									<?php echo $totalHoras; ?>
								</th>
							</tr>
						</table>

					<?php
					#HD 351094
					if($atualizacaoDados):?>
						<div class="salvarAnalise">
							<input type="hidden" name="aba_post" value="orcamento1" />
							<input type="hidden" name="chamado" value="<?php echo $hd_chamado;?>" />
							<input type="submit" name="salvar" value="Salvar" />
						</div>
					</form>
					<?php endif;?>
				</div>
			</li>

			<?php
			#Aba de Validação
			?>
			<li class="pinkHD">
				<strong class="menuAnalise">Validação</strong>
				<div style="display:none;">
					<?php
					#HD 351094
					if($atualizacaoDados):?>
					<form name='frm_validacao' id="frm_validacao" action="<? echo $_SERVER['PHP_SELF'];?>?hd_chamado=<?php echo $hd_chamado;?>" method='post' onsubmit="return analise.enviaFormulario(this);return false;">
					<?php endif;?>
						<div>
							<p class="titulo_cab"><strong>Procedimento de Validação</strong></p>
							<textarea name="validacao" id="validacao" cols="86" rows="20" class="textareaValidacao"><?php echo $validacao;?></textarea>
						</div>
					<?php
					#HD 351094
					if($atualizacaoDados):?>
						<div class="salvarAnalise">
							<a href='http://ww2.telecontrol.com.br/externos/documentacao_posvenda/login.php' target='_blank'><img src='imagens/notepad_48.png' width='36'>Manual Layout</a>
							<input type="hidden" name="aba_post" value="validacao1" />
							<input type="hidden" name="chamado" value="<?php echo $hd_chamado;?>" />
							<input type="submit" name="salvar" value="Salvar" />
						</div>
					</form>
					<?php endif;?>
				</div>
			</li>
			<?php
			#Aba de Efetivação
			?>
			<li class="grayHD">
				<strong class="menuAnalise">Efetivação</strong>
				<div style="display:none;">
					<?php
					#HD 351094
					if($atualizacaoDados):?>
					<form name='frm_efetivacao' id="frm_efetivacao" action="<? echo $_SERVER['PHP_SELF'];?>?hd_chamado=<?php echo $hd_chamado;?>" method='post' onsubmit="return analise.enviaFormulario(this);return false;">
					<?php endif;?>
						<div>
							<p class="titulo_cab"><strong>Procedimento de Efetivação</strong></p>
							<textarea name="procedimento_efetivacao" id="procedimento_efetivacao" class="textareaEfetivacao" cols="86" rows="20"><?php echo $procedimento_efetivacao;?></textarea>
							<?php
									$sqlAdmin = 'SELECT tbl_admin.nome_completo
												  FROM tbl_hd_chamado
											 LEFT JOIN tbl_admin ON (tbl_admin.admin = tbl_hd_chamado.admin_efetivacao)
												 WHERE tbl_hd_chamado.hd_chamado = '.$hd_chamado;

									$resAdmin = pg_query($con,$sqlAdmin);
									$admin_efetivacao = pg_fetch_result($resAdmin,0,'nome_completo');
									if($admin_procedimento):
										?>
											<p align="right" class="admin">Admin Responsável: <?php echo $admin_efetivacao;?></p>

							<?php endif;?>
						</div>

						<div>
							<p class="titulo_cab"><strong>Comentários da Efetivação</strong></p>
							<textarea name="comentario_efetivacao" id="comentario_efetivacao" class="textareaEfetivacao" cols="86" rows="20"><?php echo $comentario_efetivacao;?></textarea>
						</div>
					<?php
					#HD 351094
					if($atualizacaoDados):?>
						<div class="salvarAnalise">
							<input type="hidden" name="aba_post" value="efetivacao1" />
							<input type="hidden" name="chamado" value="<?php echo $hd_chamado;?>" />
							<input type="submit" name="salvar" value="Salvar" />
						</div>
					</form>
					<?php endif;?>
				</div>
			</li>
			<li class="deepPurpleHD">
				<strong class="menuAnalise">Gestão Chamado</strong>
				<div style="display:none;">
					<?php
					$pattern = ['ã', 'à', 'á', 'é', 'ç'];
					$replace = ['a', 'a', 'a', 'e', 'c'];
					$wstatus = str_replace($pattern, $replace, $status);
					?>
					<form class="span12 form-gestao" data-chamado="<?= $hd_chamado ?>" data-status="<?= $wstatus ?>">
						<div class="span12">
							<div class="control-group">
								<label class="">Etapa</label>
								<select class="select-etapa control-select">
									<option value="select" data-tipo="f">Selecione</option>
									<?php
									$gestao_status_query = "SELECT * FROM tbl_controle_status ORDER BY 1"; // AND automatico = 'f';

									$result = pg_query($con, $gestao_status_query);
									$gestao_status_response = pg_fetch_all($result);

									foreach ($gestao_status_response as $etapa) {
									?>
										<option value="<?= $etapa['controle_status'] ?>" data-tipo="<?= $etapa['manual'] ?>"><?= $etapa['etapa'] ?></option>
									<?php
									}
									?>
								</select>
							</div>
							<div class="control-group">
								<label class="">Prazo</label>
								<input id="input-prazo" type="text">
							</div>
							<?php
								$qEtapa = "SELECT tcs.ordem,
												  tsc.status
										   FROM tbl_status_chamado tsc
										   JOIN tbl_controle_status tcs ON tcs.controle_status = tsc.controle_status
										   WHERE tsc.hd_chamado = {$hd_chamado}
										   ORDER BY tsc.status_chamado DESC";
								$rEtapa = pg_query($con, $qEtapa);
								$rEtapa = pg_fetch_all($rEtapa);
								
								if ($rEtapa[0]['status'] == 'Requisitos' AND $rEtapa[0]['ordem'] == "2") {
							?>
							<div class="control-group">
								<label class="">Orçamento</label>
								<input id="input-orcamento" type="text">
							</div>
							<?php } ?>
							<div class="control-group">
								<label class="">Admin</label>
								<select class="select-admin control-select">
									<option value="select">Selecione</option>
									<?php

									$gestao_admin_query = "SELECT ta.admin, ta.nome_completo FROM tbl_admin ta 
									JOIN tbl_grupo_admin ga ON ta.grupo_admin = ga.grupo_admin 
									WHERE ta.ativo is true AND fabrica = 10 ORDER BY nome_completo;";

									$result = pg_query($con, $gestao_admin_query);
									$gestao_admin_response = pg_fetch_all($result);

									foreach ($gestao_admin_response as $admin) {
									?>
										<option value="<?= $admin['admin'] ?>"><?= $admin['nome_completo'] ?></option>
									<?php
									}
									?>
								</select>
							</div>
							<?php
							$query_habemuscb = "SELECT
													tbl_admin.admin
												FROM tbl_admin
												JOIN tbl_grupo_admin ON tbl_grupo_admin.grupo_admin = tbl_admin.grupo_admin
												WHERE tbl_grupo_admin.grupo_admin IN (9, 1, 2);";
							$result_habemuscb = pg_query($con, $query_habemuscb);
							
							while ($data = pg_fetch_assoc($result_habemuscb)) {
								$result_habemus[] = $data['admin'];
							}

							if (in_array($login_admin, $result_habemus)) {
							?>
								<div class="control-group">
									<input id="input-pendente" disabled type="checkbox" style="margin-right:10px;"><b>Entrega pendente</b>
								</div>
								<div class="control-group">
									<input id="input-entregue" disabled type="checkbox" style=""><b>Marcar como entregue</b>
								</div>
							<?php } ?>
						</div>
						<div class="span12" style="height:40px;">
							<button id="btn-salvar-gestao" type="button" style="float:right;margin-right:25px;">Salvar</button>
						</div>
						<div class="span12">
							<table class="span12" style="font-size:14px;">
								<thead style="text-align:center;">
									<tr>
										<th>#</th>
										<th style="text-align:left;">Admin</th>
										<th>Etapa</th>
										<th>Início</th>
										<th>Prazo</th>
										<th style="text-align:right;">Entrega</th>
									</tr>
								</thead>
								<tbody>
									<?php
									$sql_status= "SELECT ts.status_chamado, tc.controle_status, ta.admin, ta.nome_completo, tc.status, tc.etapa, ts.data_inicio, ts.data_prazo, ts.data_entrega
									FROM tbl_status_chamado ts JOIN tbl_controle_status tc ON tc.controle_status = ts.controle_status
									JOIN tbl_admin ta ON ts.admin = ta.admin 
									WHERE ts.hd_chamado = {$hd_chamado} ORDER BY data_inicio DESC;";
									$result_status = pg_query($con, $sql_status);

									if (strlen(pg_last_error()) == 0) {
										$dados_status = pg_fetch_all($result_status);
										
										$query_select_ga = "SELECT grupo_admin FROM tbl_admin WHERE admin = {$login_admin};";
										$result = pg_query($con, $query_select_ga);
										$ga = pg_fetch_result($result, 0, 'grupo_admin');
										
										foreach ($dados_status as $etapa) {
											$inicio = explode(" ", $etapa['data_inicio']);
											$prazo = explode(" ", $etapa['data_prazo']);
											$entrega = explode(" ", $etapa['data_entrega']);
										?>
											<tr data-status="<?= $etapa['status_chamado'] ?>">
												<?php
												if (in_array($ga, [1, 2, 7, 9])) { 
												?>
													<td style="text-align:center;"><i class="fa fa-edit edit-etapa" style="font-size:18px;cursor:pointer;" aria-hidden="true"></i></td> 
												<?php 
												} else {
												?>
													<td style="text-align:center;">#</td>
												<?php
												}
												?>
												<td data-admin="<?= $etapa['admin'] ?>" style="font-weight:bold;"><?= $etapa['nome_completo'] ?></td>
												<td data-etapa="<?= $etapa['controle_status'] ?>" style="text-align:center;"><?= $etapa['etapa'] ?></td>
												<td style="text-align:center;"><?= implode("/", array_reverse(explode("-", $inicio[0]))) . " " . $inicio[1]; ?></td>
												<td style="text-align:center;"><?= implode("/", array_reverse(explode("-", $prazo[0]))) . " " . $prazo[1]; ?></td>
												<td data-entrega="<?= $etapa['data_entrega'] ?>" style="text-align:right;font-weight:bold;">
												<?php 
													if ($etapa['data_entrega']) {
														echo(implode("/", array_reverse(explode("-", $entrega[0]))) . " " . $entrega[1]);
													} else { 
														echo("Pendente");
													}
												?>
												</td>
											</tr>
										<?php
										}
									}
									?>
								</tbody>
							</table>
						</div>
					</form>
				</div>
			</li>
		</ul>
<?
if ($_GET['consultar'] <> 'sim'){

?>
		<input type="button" onclick="analise.inicioTrabalho(<?php echo $hd_chamado;?>)" value="Início do Trabalho" />
		<input type="button" onclick="analise.fimTrabalho(<?php echo $hd_chamado;?>)" value="Fim do Trabalho" />
	</div>
<?php
	//Fim modificação Thiago - HD: 304470
	echo ($fabrica == 87) ? "<h3 style='text-align:center;color:red'>Jacto não pode efetivar na produção(subir no github) antes de passar para teste_jacto</h3>":"";
}
?>

<BR>
<form name='frm_chamada' action='<? echo $PHP_SELF ?>' method='POST' enctype="multipart/form-data" >
	<input type='hidden' name='hd_chamado' value='<?= $hd_chamado?>'>
	<input type='hidden'name='hidden_horas_utilizadas' id='hidden_horas_utilizadas' value='<?= $horas_utilizadas?>'>
	<?
	if ($_GET['consultar'] <> 'sim'){
	?>

	<table width = '700px' align = 'center' border='0' cellpadding='2'  style='font-family: arial ; font-size: 12px'>
		<tr>
		<td valign='top'>
			<table width = '475px' align = 'center' class='tab_cabeca' border='0' cellpadding='2'  >
				<? if ($status == "Análise") {?>
					<tr>
						<td class = 'titulo_cab'><strong>Seqüência </strong></td>
						<td class='sub_label'>
							<input type='radio' name='sequencia' value='CONTINUA' id='continua'><label for='continua'>Continua em Análise</label>
							<br>
							<input type='radio' name='sequencia' value='AGUARDANDO' id='aguardando'><label for='aguardando'>Aguard.Execução</label>
							<br>
							<input type='radio' name='sequencia' value='SEGUE' id='segue'><label for='segue'>Vai para Execução</label>
						</td>
					</tr>
				<? } ?>

				<? if ($status == "Aguard.Execução") {?>
					<tr>
						<td class = 'titulo_cab' ><strong>Seqüência </strong></td>
						<td class='sub_label'>
							<input type='radio' name='sequencia' value='CONTINUA' id='continua'><label for='continua'>Continua Aguard.Execução</label>
							&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
							<input type='radio' name='sequencia' value='SEGUE' id='segue'><label for='segue'>Vai para Execução</label>
						</td>
					</tr>
				<? }

				if ($status == "Execução") {?>
					<tr>
						<td class = 'titulo_cab'><strong>Seqüência </strong></td>
						<td  class='sub_label'>
							<input type='radio' name='sequencia' value='CONTINUA' id='continua'><label for='continua'>Continua em Execução</label>
							&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
							<input type='radio' name='sequencia' value='SEGUE' id='segue'><label for='segue'>Resolvido</label>
						</td>
					</tr>
				<? } ?>
				<tr>
					<td  align='center' colspan='2'  class='sub_label'>
						<textarea name='comentario' cols="50" rows="5" id='comentario' value=""><?echo $comentario;?></textarea>

						<br />

						<input type='checkbox' name='exigir_resposta' value='t' id='exigir_resposta'><label for='exigir_resposta'>Exigir resposta do usuário</label>
						<input type='checkbox' name='interno' value='t' id='interno'><label for='interno'>Chamado Interno</label> &nbsp; &nbsp; <button type="button" onclick="resetTextarea()">Limpar Conteúdo</button>
					</td>
				</tr>
				<tr>
					<td align='center' colspan='2' class='sub_label'>
						<ul id="container_file_interacao" style='list-style:none;text-align:left;'>
							<li class='titulo_cab' style='margin: 3px'> Arquivo: <input type='file' name='arquivo[]'></li>
						</ul>
						<p>
							<input type="button" value="Adicionar novo arquivo" onclick="analise.addInteracaoFile()" >
						</p>
					</td>
				</tr>
				<tr>
					<td align='center' class='sub_label'>
						<center><input type='submit' name='btn_telefone' value='Telefone'>
						</center>
					</td>
					<td align='center' class='sub_label'>
						<!-- value="Responder Chamado" -->
						<input id="btn_acao" type='hidden' name='btn_acao'/>
						<center><button type='button' id="btn_responder_chamado" onclick="javascript: $(this).hide(); if(verificaPrazo()){$('#btn_acao').val('Responder Chamado'); $(this).parents('form').submit(); }">Responder Chamado </button>
						
						<input type="hidden" name="fabrica" value="<?=$fabrica?>">

						<input type='checkbox' name='importante' value='t' id='importante' title='Usar para informar uma interação importante para verificação futura'><label for='importante'>Importante(interno)</label>
						</center>
					</td>
				</tr>
			</table>
		</td>
		<td valign='top'>
			<table width = '300px' align = 'center' class='tab_cabeca'  cellpadding='2' cellspacing='1' border='0' >
				<tr>
					<td colspan='2' align='center' class='titulo_cab'><strong><font size='5'><?echo $hd_chamado; ?></font></strong></td>
				</tr>
				<tr>
					<td class ='sub_label'><strong>Status </strong></td>
					<td class ='sub_label' align = 'center'>
						<select name="status" size="1"  style='width: 150px;'>
							<!--<option value=''></option>-->
							<option value='Novo'             <? if($status=='Novo')            echo ' SELECTED '?> >Novo</option>
							<option value='Requisitos'       <? if($status=='Requisitos')      echo ' SELECTED '?> >Requisitos</option>
							<option value='Orçamento'        <? if($status=='Orçamento')       echo ' SELECTED '?> >Orçamento</option>
							<option value='Análise'          <? if($status=='Análise')         echo ' SELECTED '?> >Análise</option>
							<option value='Documentação'          <? if($status=='Documentação')         echo ' SELECTED '?> >Documentação</option>
							<option value='Aguard.Execução'  <? if($status=='Aguard.Execução') echo ' SELECTED '?> >Aguard.Execução</option>
							<option value='Execução'         <? if($status=='Execução')        echo ' SELECTED '?> >Execução</option>
							<option value='Validação'        <? if($status=='Validação')       echo ' SELECTED '?> >Validação</option>
							<option value='EfetivaçãoHomologação'       <? if($status=='EfetivaçãoHomologação')      echo ' SELECTED '?> >Efetivação Homologação</option>
							<option value='ValidaçãoHomologação'        <? if($status=='ValidaçãoHomologação')       echo ' SELECTED '?> >Validação Homologação</option>
							<option value='Efetivação'       <? if($status=='Efetivação')      echo ' SELECTED '?> >Efetivação</option>
							<option value='Correção'         <? if($status=='Correção')        echo ' SELECTED '?> >Correção</option>
							<option value='Parado'           <? if($status=='Parado' )         echo ' SELECTED '?> >Parado</option>
							<!-- <option value='Impedimento'      <? if($status=='Impedimento' )    echo ' SELECTED '?> >Impedimento</option> hd_chamado=2728371 -->
							<option value='Suspenso'         <? if($status=='Suspenso' )       echo ' SELECTED '?> >Suspenso </option>
							<option value='Aguard.Admin'     <? if($status=='Aguard.Admin')    echo ' SELECTED '?> >Aguard.Admin</option>
							<option value='Resolvido'        <? if($status=='Resolvido')       echo ' SELECTED '?> >Resolvido</option>
							<option value='Cancelado'        <? if($status=='Cancelado')       echo ' SELECTED '?> >Cancelado</option>
						</select>
					</td>
				</tr>
				<tr>
				    <td  class ='sub_label'><strong>Atendente Responsável</strong></td>
				    <td  class ='sub_label' align='center' >
					<?php

					if($login_fabrica == 10){

						$mostrar_admin_responsavel = true;

						if(strlen($atendente_responsavel) > 0){

							$sql_nome_admin_responsavel = "SELECT nome_completo FROM tbl_admin WHERE admin = {$atendente_responsavel}";
							$res_nome_admin_responsavel = pg_query($con, $sql_nome_admin_responsavel);

							if(pg_num_rows($res_nome_admin_responsavel) > 0){

								$mostrar_admin_responsavel = false;
								$nome_admin_responsavel    = pg_fetch_result($res_nome_admin_responsavel, 0, "nome_completo");

							}

						}

			            $sqlAtendResponsavel = "SELECT
			            							admin,
			            							nome_completo
			                                    FROM tbl_admin
			                                    WHERE
			                                    	fabrica = 10
			                                    	AND ativo IS TRUE
			                                    	AND nome_completo NOTNULL
			                                    	AND grupo_admin IS NOT NULL
			                                    ORDER BY nome_completo ASC";
			            $resAtendResponsavel = pg_query($con, $sqlAtendResponsavel);

			            if (pg_num_rows($resAtendResponsavel) > 0) {

			            	if($mostrar_admin_responsavel === false){

			            		echo "<strong> {$nome_admin_responsavel} </strong> <button type='button' onclick='mostrar_admin_responsavel();'> Alterar </button> ";

			            		?>

			            		<script>

			            			function mostrar_admin_responsavel(){

			            				$(".box-admins-responsaveis").toggle();

			            			}

			            		</script>

			            		<?php

			            	}

			            	$display_admins_responsaveis = ($mostrar_admin_responsavel === false) ? "style='display: none;'" : "";

			            	echo "<div class='box-admins-responsaveis' {$display_admins_responsaveis}>";

				            	echo "<select name='atendente_responsavel' class='frm' style='width: 150px;' >";

				            		echo "<option value=''></option>";

					            	for($i = 0; $i < pg_num_rows($resAtendResponsavel); $i++){

										$admin_atendente_responsavel = pg_fetch_result($resAtendResponsavel, $i, "admin");
										$nome_atendente_responsavel  = pg_fetch_result($resAtendResponsavel, $i, "nome_completo");

										$selected = ($admin_atendente_responsavel == $atendente_responsavel) ? "selected" : "";

					            		echo "<option value='{$admin_atendente_responsavel}' {$selected} > {$nome_atendente_responsavel} </option>";

					            	}

				            	echo "</select>";

			            	echo "</div>";

						}

					}else{

						$sqlAtendResponsavel = "SELECT
			            							admin,
			            							nome_completo
			                                    FROM tbl_admin
			                                    WHERE
			                                    	fabrica = {$login_fabrica}
			                                    	AND ativo IS TRUE
			                                    	AND nome_completo NOTNULL
			                                    	AND admin = {$atendente_responsavel}";
			            $resAtendResponsavel = pg_query($con, $sqlAtendResponsavel);

						$rowAtendResponsavel  = pg_fetch_array($resAtendResponsavel);
			            echo $rowAtendResponsavel['nome_completo'];

					}

				    ?>
				    </td>
				</tr>
				<tr>
					<td  class ='sub_label'><strong>Atendente</strong></td>
					<td  class ='sub_label' align='center' >
						<?
                        $sql = "  SELECT *
									FROM tbl_admin
								   WHERE tbl_admin.fabrica =  10
								   AND ativo               IS TRUE
								   AND grupo_admin         IS NOT NULL
								   ORDER BY tbl_admin.nome_completo;";
						$res = pg_query($con,$sql);

						if ( pg_num_rows($res) > 0) {
							echo "<select class='frm' style='width: 150px;' name='transfere'>\n";
							echo "<option value=''>- ESCOLHA -</option>\n";

							for ($x = 0 ; $x <  pg_num_rows($res) ; $x++){
								$aux_admin = trim(pg_fetch_result($res, $x, 'admin'));
								$aux_nome_completo  = trim(pg_fetch_result($res, $x, 'nome_completo'));
							?>
								<option value="<?= $aux_admin ?>" <?= ($atendente == $aux_admin) ? " selected " : "" ?>><?= $aux_nome_completo ?></option>
							<?php
							}
							echo "</select>\n";
						}
						?>
					</td>
				</tr>
				<tr>
					<td class ='sub_label' ><strong>Categoria </strong></td>
					<td  class ='sub_label' align='center'>
						<select name="categoria" size="1"  style='width: 150px;'>
							<option></option>
							<option value='Ajax' <? if($categoria=='Ajax') echo ' SELECTED '?> >Ajax, JavaScript</option>
							<option value='Design' <? if($categoria=='Design') echo ' SELECTED '?> >Design</option>
							<option value='Documentação' <? if($categoria=='Documentação') echo ' SELECTED '?> >Documentação</option>
							<option value='Erro em Tela' <? if($categoria=='Erro em Tela') echo ' SELECTED '?> >Erro em Tela</option>
							<option value='Implantação' <? if($categoria=='Implantação') echo ' SELECTED '?> >Implantação</option>
							<option value='Infra' <? if($categoria=='Infra') echo ' SELECTED '?> >Infraestrutura</option>
							<option value='Integração' <? if($categoria=='Integração') echo ' SELECTED '?> >Integração (ODBC, Perl)</option>
							<option value='Linux' <? if($categoria=='Linux') echo ' SELECTED '?> >Linux, Hardware, Data-Center</option>
							<option value='Novos' <? if($categoria=='Novos') echo ' SELECTED '?> >Novos Projetos</option>
							<option value='SQL' <? if($categoria=='SQL') echo ' SELECTED '?> >Otimização de SQL e Views</option>
							<option value='Parada do Sistema' <? if($categoria=='Parada do Sistema') echo ' SELECTED '?> >Parada do Sistema</option>
							<option value='PHP' <? if($categoria=='PHP') echo ' SELECTED '?> >PHP</option>
							<option value='PL' <? if($categoria=='PL') echo ' SELECTED '?> >PL/PgSQL, functions e triggers</option>
							<option value='Processos' <? if($categoria=='Processos') echo ' SELECTED '?> >Processos</option>
							<option value='Postgres' <? if($categoria=='Postgres') echo ' SELECTED '?> >Postgres</option>
							<option value='Suporte Telefone' <? if($categoria=='Suporte Telefone') echo ' SELECTED '?> >Suporte Telefone</option>
						</select>
					</td>
				</tr>
				<tr>
					<td class ='sub_label'><strong>Tipo </strong></td>
					<td  class ='sub_label' align='center'>
						<select name="tipo_chamado" size="1"  style='width: 150px;'>
						<?
						$sql = "SELECT	tipo_chamado,
											descricao
									FROM tbl_tipo_chamado
									WHERE tipo_chamado in (1,4,5,6,7,8,9,10)
									ORDER BY descricao;";
							$res = pg_query($con,$sql);
							if( pg_num_rows($res)>0){
									for($i=0; pg_num_rows($res)>$i;$i++){
										$xtipo_chamado = pg_fetch_result($res, $i, 'tipo_chamado');
										$xdescricao    = pg_fetch_result($res, $i, 'descricao');
										echo "<option value='$xtipo_chamado' ";
										if($tipo_chamado == $xtipo_chamado){echo " SELECTED ";}
										echo " >$xdescricao</option>";
									}
							}
						?>
						</select>
					</td>
				</tr>
								<?
					if(strlen($hd_chamado)>0){
						$sql_sup ="SELECT TO_CHAR((EXTRACT(epoch FROM SUM(termino-data_inicio))/60 || ' minutes')::INTERVAL, 'HH24:MI')
									 FROM tbl_hd_chamado_atendente
								     JOIN tbl_admin using(admin)
									 JOIN tbl_hd_chamado_item USING(hd_chamado,admin)
									WHERE tbl_hd_chamado_atendente.hd_chamado = $hd_chamado
									  AND data = data_inicio
									  AND data_inicio NOTNULL
									  AND termino NOTNULL
								      AND grupo_admin=6";
						$res_sup = pg_query($con, $sql_sup);
						if (pg_num_rows($res_sup) > 0) {
							$horas_sup = pg_fetch_result($res_sup, 0, 0);
						}

						if (strlen($horas_sup) == 0) {
							$horas_sup = "00:00:00";
						}
						$xhoras_sup = explode(":",$horas_sup);
						$horas_sup = $xhoras_sup[0].":".$xhoras_sup[1];
					}
					?>
				<tr>
	            	<td  class ='sub_label'><strong>Trabalhadas Suporte</strong></td>
	            	<td class ='sub_label' align='center'><?php echo $horas_sup;?> horas</td>
				</tr>
				<?
					if(strlen($hd_chamado)>0){
						$sql_dev ="SELECT TO_CHAR((EXTRACT(epoch FROM SUM(termino-data_inicio))/60 || ' minutes')::INTERVAL, 'HH24:MI')
								  FROM tbl_hd_chamado_atendente
								  JOIN tbl_admin using(admin)
								  JOIN tbl_hd_chamado_item USING(hd_chamado,admin)
								 WHERE tbl_hd_chamado_atendente.hd_chamado = $hd_chamado
								   AND data = data_inicio
								   AND data_inicio NOTNULL
							 	   AND termino NOTNULL
								   AND grupo_admin=4 ";
						$res_dev = pg_query($con, $sql_dev);
						if (pg_num_rows($res_dev) > 0) {
							$horas_dev= pg_fetch_result($res_dev, 0, 0);
						}

						if (strlen($horas_dev) == 0) {
							$horas_dev = "00:00:00";
						}
						$xhoras_dev = explode(":",$horas_dev);
						$horas_dev = $xhoras_dev[0].":".$xhoras_dev[1];
					}
				?>
				<tr>
					<td  class ='sub_label'><strong>Trabalhadas Desenvolvimento</strong></td>
					<td class ='sub_label' align='center'>
						<?php echo $horas_dev;?> horas
						<?php if ($horas_dev) { ?>
						<button type='button' class='btn btn-sm details pull-right' title='Detalhe das horas trabalhadas'
						  data-title='<?=$hd_chamado?>'
						 data-target='#aux'
						   data-href='../admin/backlog_cadastro.php?hd_chamado=<?=$hd_chamado?>&ajax=detHT'>
							<i class='glyphicon glyphicon-tasks'></i>
						</button>
						<?php } ?>
					</td>
				</tr>

				<tr>
					<?
					if(strlen($hd_chamado)>0){
						$cond1 = " AND grupo_admin in (4,6) ";
						$wsql ="SELECT TO_CHAR((EXTRACT(epoch FROM SUM(termino-data_inicio))/60 || ' minutes')::INTERVAL, 'HH24:MI')
								FROM tbl_hd_chamado_atendente
								JOIN tbl_admin using(admin)
								JOIN tbl_hd_chamado_item USING(hd_chamado,admin)
								WHERE tbl_hd_chamado_atendente.hd_chamado = $hd_chamado
								AND  data = data_inicio
								AND  data_inicio NOTNULL
								AND  termino NOTNULL
								$cond1
								/*AND   responsabilidade in ('Analista de Help-Desk','Programador')*/";
						$wres = pg_query($con, $wsql);
						if( pg_num_rows($wres)>0)
						$horas= pg_fetch_result($wres, 0, 0);

						if(strlen($horas)==0){
							$horas = "00:00:00";
						}
						$xhoras = explode(":",$horas);
						$horas = $xhoras[0].":".$xhoras[1];

						$horas = str_replace("day", "dia", $horas);
						$horas = str_replace("days", "dias", $horas);
						// $horas = str_replace("ss", "s", $horas);
					}
					?>

					<td  class ='sub_label'><strong>Total Trabalhadas </strong></td>
					<?
					echo "<td  class ='sub_label'align='center' title='";

					$sqlx = "SELECT tbl_admin.login,
									tbl_hd_chamado_atendente.data_inicio,
									TO_CHAR(tbl_hd_chamado_atendente.data_inicio,'DD/MM/YYYY hh24:mi:ss') as inicio,
									TO_CHAR(tbl_hd_chamado_atendente.data_termino,'hh24:mi:ss') as fim
							FROM tbl_hd_chamado_atendente
							JOIN tbl_admin USING(admin)
							WHERE hd_chamado = $hd_chamado
							ORDER BY tbl_hd_chamado_atendente.data_inicio";
					$resx = pg_query($con, $sqlx);

					for ($i=0;$i< pg_num_rows($resx);$i++) {
						echo pg_fetch_result($resx, $i, 'login')." (".pg_fetch_result($resx, $i, 'inicio')." - ".pg_fetch_result($resx, $i, 'fim').")\n";
					}
					echo "'> $horas horas";
					?>
						<button type='button' class='btn btn-sm details pull-right' title='Detalhe das horas trabalhadas'
						  data-title='<?=$hd_chamado?>'
						 data-target='#aux'
						   data-href='adm_rae.php?hd_chamado=<?=$hd_chamado?>&modal=1&fabrica=<?=$fabrica?>'>
							<i class='glyphicon glyphicon-tasks'></i>
						</button>
					</td>
				</tr>

				<tr> 
					<td class ='sub_label'><strong>Previsão Interna</strong></td>
					<td  class ='sub_label' align='center'>
						<?php if($grupo_admin == 6)	{ ?>
							<input type="text" size='16' maxlength ='16' class='caixa' id="previsao_termino_interna" name="previsao_termino_interna" value="<?=$previsao_termino_interna?>" >

						<?}else if(empty($previsao_termino_interna) ){

						if( isset($grupo_admin) && (in_array($grupo_admin,[1,2,7,9]))) {

							$readonly_pi = (empty($horas_analisadas)) ? "" : " readonly='true' ";
?>

							<input type="text" size='16' maxlength ='16' class='caixa' id="previsao_termino_interna" name="previsao_termino_interna" value="<?=$previsao_termino_interna?>" >

								<div id='div_previsao_interna'></div>
								<input type='button' value='Previsão' onclick="javascript:atualizaPrevisaoInterna('<?=$hd_chamado?>')">



								<?php }  else if(!empty($previsao_termino_interna)){
										echo "<input type='text' readonly='true' size='2' maxlength ='8' id='horas_analisadas' name='horas_analisadas' value='$horas_analisadas' class='caixa'> horas <br />";
										echo $previsao_termino_interna;

								?>

							<?php }
						}else{
							echo "<input type='text'  size='2' maxlength ='8' id='horas_analisadas' name='horas_analisadas' value='$horas_analisadas' class='caixa'> horas <br />";
							echo "<div id='previsao_termino_interna'>$previsao_termino_interna</div>";
							if(in_array($grupo_admin, array(1,2))) {
								echo "<input type='button' value='Adiar Previsao' onclick=\"javascript:atualizaPrazoTermino('$hd_chamado',this,'$grupo_admin','sim')\">";
								echo "<div>Motivo<textarea name='motivo' id='motivo' cols='30' rows='2' ></textarea></div>";
							}
						} ?>
					</td>

					<!-- <td class ='sub_label'><strong>Horas Analisadas</strong></td>
					<td  class ='sub_label' align='center'>
						<input type='text' readonly='true' size='2' maxlength ='8' id='horas_analisadas' name='horas_analisadas' value='<?php echo $horas_analisadas ?>' class='caixa'> horas
					</td> -->

				</tr>
				<!-- <tr>
					<td class ='sub_label'><strong>Horas Utilizadas</strong></td>
					<td  class ='sub_label' align='center'>
						<?php echo $horas_utilizadas." horas"; ?>
					</td>
				</tr>	-->
				<? if($analista_hd == "sim" || in_array($login_admin, array(5205,4789,8527))){  ?>
					<!-- <tr>
						<td  class ='sub_label'><strong>Hrs Desenvol.</strong></td>
						<td  class ='sub_label'align='center'>

						<input type='text' size='2' maxlength ='5' name='prazo_horas' value='<?= $prazo_horas ?>' class='caixa' onblur="javascript:checarNumero(this);atualizaPrazo('<?echo $hd_chamado;?>',this.value)"> horas

						<div id='result' style='position:absolute; display:none; border: 1px solid #949494;background-color: #F1F0E7;width:150px;'>
						</div>
						</td>
					</tr> -->

					<tr>
						<td  class ='sub_label' title='Horas que será deduzida da quantidade de horas da franquia do fabricante.'><strong>Orçamento</strong></td>
						<td  class ='sub_label'align='center'>

						<input type='text' size='2' maxlength ='5' name='hora_desenvolvimento' value='<?= $hora_desenvolvimento ?>' class='caixa' onblur="javascript:checarNumero(this);atualizaHr('<?echo $hd_chamado;?>',this.value)"> horas<BR>

						</td>
					</tr>

					<tr>
						<td  class ='sub_label'><strong>Previsão Cliente</strong></td>
						<td  class ='sub_label' align='center'>

						<?php  if( isset($grupo_admin) && (in_array($grupo_admin,array(1,2,6,7,9)) ) || in_array($login_admin, array(5205,4789,8527))) { ?>
						<input type='text' size='16' maxlength ='16' name='previsao_termino' id='previsao_termino' value='<?= $previsao_termino ?>' <?
						?> class='caixa' onblur="javascript:atualizaPrevisaoTermino('<?echo $hd_chamado;?>',this.value)">
						<?}else{
							echo $previsao_termino;
						} ?>
						<div id='result2' style='position:absolute; display:none;  border: 1px solid #949494;background-color: #F1F0E7;width:100px;'>
						</div>
						</td>
					</tr>

					<tr>
						<td class ='sub_label' ><strong>Cobrar</strong></td>
						<td  class ='sub_label' align='center'>
						<input type='checkbox' name='cobrar' value='t' <? if ($cobrar == "t") echo "Checked";?>> Sim

						</td>
					</tr>
					<tr>
						<td class ='sub_label' ><strong>Prioridade</strong></td>
						<td  class ='sub_label' align='center'>
							<?php
							 	if ($prioridade == 1 || $prioridade == 2) {
							 		echo '<input type="hidden" name="prioridade" value="'.$prioridade.'"> '.$prioridade;
							 	} else {
							 ?>
								<input type='checkbox' name='prioridade' value='t' <? if ($prioridade == "t") echo "Checked";?>> Sim

							<?php }?>
						</td>
					</tr>
				<? }else{ ?>
					<tr>
						<td  class ='sub_label'><strong>Desenvol.</strong></td>
						<td  class ='sub_label'align='center'>
							<?= $hora_desenvolvimento ?> horas
						</td>
					</tr>
					<tr>
						<td  class ='sub_label'><strong>Cobrar ? </strong></td>
						<td  class ='sub_label' align='center'>
							<input type='hidden' name='cobrar' value='<? echo $cobrar;?>'>
							<? if ($cobrar == "t"){ echo "Sim";}else{ echo "Não";}?>
						</td>
					</tr>
					
				<? } ?>

				<?php if($grupo_admin == 1 OR $grupo_admin == 6 ){?>
					
					<?php if($fabrica == 159){ ?>
						<tr>
							<td  class ='sub_label'><strong>Classficação de Prioridade </strong></td>
							<td  class ='sub_label' align='center'>
								
									<select name='campoPrioridade'>
									    <option value="">Selecione</option>
									    <option value="P1" <?php if($campoPrioridade == 'P1'){ echo " selected "; } ?> >P1</option>
									    <option value="P2" <?php if($campoPrioridade == 'P2'){ echo " selected "; } ?> >P2</option>
									    <option value="P3" <?php if($campoPrioridade == 'P3'){ echo " selected "; } ?> >P3</option>
									    <option value="P4" <?php if($campoPrioridade == 'P4'){ echo " selected "; } ?> >P4</option>
									</select>
									<input type='hidden' name="fabricaChamado" value="<?=$fabrica?>">
									<input type="hidden" name="campoPrioridade_anterior" value="<?=$campoPrioridade ?>">
								
							</td>
						</tr>
					<?php } ?>
					<tr>
						<td  class ='sub_label'><strong>Impacto Financeiro </strong></td>
						<td  class ='sub_label' align='center'>
							<select name='impacto_financeiro' id='impacto_financeiro'>
				                <option value=''>Selecione</option>
				                <option value='1' <?php if ($impacto_financeiro == 1) {echo 'selected="selected"';}?>>Sim</option>
				                <option value='2' <?php if ($impacto_financeiro == 2) {echo 'selected="selected"';}?>>Não</option>
				            </select>
				            <input type="hidden" name="impacto_financeiro_anterior" value="<?=$impacto_financeiro ?>">
						</td>
					</tr>

				<?php } ?>

					<tr>
						<td  class ='sub_label'><strong>Desenvolvedor</strong></td>
						<td  class ='sub_label' align='center' >
						<?
						$sql = "SELECT admin, nome_completo
                                  FROM tbl_admin
                                 WHERE tbl_admin.fabrica =  10
                                   AND ativo             IS TRUE
                                   AND grupo_admin       IN(2,4,7)
                                 ORDER BY tbl_admin.nome_completo;";
						$res = pg_query($con,$sql);
						$totalDesenv = pg_num_rows($res);

						if ($totalDesenv  > 0) {
							echo "<select class='frm' style='width: 150px;' name='admin_desenvolvedor' onchange='return muda_admin(\"$admin_desenvolvedor\",this);'>\n";
							echo "<option value=''>- ESCOLHA -</option>\n";

							for ($x=0;$x<$totalDesenv;$x++){

								$aux_admin = trim(pg_fetch_result($res, $x, 'admin'));
								$aux_nome_completo  = trim(pg_fetch_result($res, $x, 'nome_completo'));

								if($admin_desenvolvedor == $aux_admin){
									$selected = 'selected';
									$indice = ($x+1);
								}else{
									$selected = null;
								}

								echo "<option value='$aux_admin'".$selected."> $aux_nome_completo</option>\n";
							}
							echo "</select>\n";
							echo "<input type='hidden' value='$indice' id='indexDesenv'>\n";
						}
						?>
						</td>
					</tr>
				<? if($tipo_chamado == 5 ) { ?>
					<tr>
						<td  class ='sub_label'><strong>Causador</strong></td>
						<td  class ='sub_label' align='center' >
						<?
						$sql = "SELECT admin, nome_completo
                                  FROM tbl_admin
                                 WHERE tbl_admin.fabrica =  10
                                   AND ativo
                                   AND grupo_admin   NOTNULL
                                 ORDER BY tbl_admin.nome_completo;";
						$res = pg_query($con,$sql);
						$totalDesenv = pg_num_rows($res);

						if ($totalDesenv  > 0) {
							echo "<select class='frm' style='width: 150px;' name='admin_erro' >\n";
							echo "<option value=''>- ESCOLHA -</option>\n";

							for ($x=0;$x<$totalDesenv;$x++){

								$aux_admin = trim(pg_fetch_result($res, $x, 'admin'));
								$aux_nome_completo  = trim(pg_fetch_result($res, $x, 'nome_completo'));

								if($admin_erro == $aux_admin){
									$selected = 'selected';
									$indice = ($x+1);
								}else{
									$selected = null;
								}

								echo "<option value='$aux_admin'".$selected."> $aux_nome_completo</option>\n";
							}
							echo "</select>\n";
						}
						?>
						</td>
					</tr>
					<tr>
						<td  class ='sub_label'><strong>Tipo Erro</strong></td>
						<td  class ='sub_label' align='center' >
						<select name='tipo_erro' style="width: 150px;">
								<? $tipo_erros = array('','falha_analista'=>'Falha Análise', 'falha_programador'=>'Falha Programação','falha_suporte'=>'Falha Requisito','falha_teste'=>'Falha Teste','falha'=>'Falha Geral');

								foreach($tipo_erros as $key => $value){
										echo"<option value='".$key."'";
										if ($tipo_erro == $key) echo " selected";
										echo ">".$value."</option>\n";
								}?>
						</select>
						</td>
					</tr>
					<tr>
						<td  class ='sub_label'><strong>Motivo Erro</strong></td>
						<td  class ='sub_label' align='center' >
								<textarea name='motivo_erro' id='motivo_erro' cols='30' rows='2' ><?=$motivo_erro?></textarea>
						</td>
				     </tr>
				<?	} ?>
				<?php  if( isset($grupo_admin) && (in_array($grupo_admin,array(1,2,9)) )) { ?>
					<tr>
						<td  class ='purpleHD'><strong>Hora Análise</strong></td>
						<td  class ='purpleHD' align='center' >
							<input type='text' size='2' maxlength ='5' name='pre_hora_analise' value='<?= $pre_hora_analise ?>' class='caixa' > horas<BR>
						</td>
					</tr>
					<tr>
						<td  class ='purpleHD'><strong>Hora Desenvolvimento</strong></td>
						<td  class ='purpleHD' align='center' >
							<input type='text' size='2' maxlength ='5' name='pre_hora_desenvolvimento' value='<?= $pre_hora_desenvolvimento ?>' class='caixa' > horas<BR>
						</td>
					</tr>
						<tr>
						<td  class ='purpleHD'><strong>Hora Teste</strong></td>
						<td  class ='purpleHD' align='center' >
							<input type='text' size='2' maxlength ='5' name='pre_hora_teste' value='<?= $pre_hora_teste ?>' class='caixa' > horas<BR>
						</td>
					 </tr>
						<tr>
						<td  class ='purpleHD' align='center' colspan='2'>
							<input type='submit' name='btn_orcamento' value='Passar Orçamento' >
						</td>
				     </tr>
				<?	} ?>
					<tr>
						<td  class ='sub_label' colspan='2'>
							<div id='conteudo' class='Chamados2' style='position: absolute;opacity:.80;'></div>&nbsp;
						</td>
					</tr>
				</TABLE>
			</td>
		</tr>
		<tr>
			<td colspan='2'>

				<?
				$sql = "SELECT
							tbl_arquivo.descricao AS arquivo,
							to_char (tbl_controle_acesso_arquivo.data_inicio,'DD/MM') AS data_inicio,
							to_char (tbl_controle_acesso_arquivo.hora_inicio,'HH24:MI') AS hora_inicio,
							to_char (tbl_controle_acesso_arquivo.data_fim,'DD/MM') AS data_fim,
							to_char (tbl_controle_acesso_arquivo.hora_fim,'HH24:MI') AS hora_fim
						FROM tbl_arquivo
						JOIN tbl_controle_acesso_arquivo USING(arquivo)
						WHERE hd_chamado=$hd_chamado
						ORDER BY tbl_controle_acesso_arquivo.data_inicio";
				$res_arquivos = pg_query($con,$sql);
				echo "<table width = '750' align = 'center' class='tab_cabeca'  cellpadding='2' cellspacing='1' border='0' >";

				if (@ pg_num_rows($res_arquivos) > 0) {
					echo "<tr  bgcolor='#D9E8FF'; style='font-family: arial ; font-size: 10px ;'>\n";
					echo "<td nowrap style='border-bottom:1px solid #cecece'><b>Início</b></td>\n";
					echo "<td nowrap style='border-bottom:1px solid #cecece'align='center'><b>Histórico dos Arquivos Utilizados</b></td>\n";
					echo "<td nowrap style='border-bottom:1px solid #cecece'><b>Fim</b></td>\n";
					echo "</tr>\n";
					$arquivo = "";
					$data_inicio = "";
					$data_fim = "";
					for ($k = 0 ; $k <  pg_num_rows($res_arquivos) ; $k++) {
						$arquivo	.= str_replace ("/var/www/assist/www/","",pg_fetch_result($res_arquivos, $k, 'arquivo'))."<br>";
						$data_inicio.= pg_fetch_result($res_arquivos, $k, 'data_inicio')."  ".pg_fetch_result($res_arquivos, $k, 'hora_inicio')."<br>";
						$data_fim.= pg_fetch_result($res_arquivos, $k, 'data_fim')."  ".pg_fetch_result($res_arquivos, $k, 'hora_fim')."<br>";
					}
					echo "<tr style='font-family: arial ; font-size: 10px ;' height='25'>\n";
					echo "<td nowrap>$data_inicio</td>\n";
					echo "<td align='left' style='padding-left:10px'>$arquivo</td>\n";
					echo "<td nowrap>$data_fim</td>\n";
					echo "</tr>\n";
				}
				?>
				</table>
			</td>
		</tr>

	</table>

	<?
	}

	if(!empty($hd_chamado)) {
		$sqli = "SELECT count(1) FROM tbl_hd_chamado_item where hd_chamado = $hd_chamado and status_item = 'Importante'";
		$resi = pg_query($con, $sqli); 
		$contagem_i = pg_fetch_result($resi, 0 , 0) ; 
	}
	$hiddenText = array('do Trabalho', 'de Trabalho', 'balho auto','Chamado Transferido','Categoria Alterada','Previsão Cadastrada p');

	$sql= "SELECT   tbl_hd_chamado_item.hd_chamado_item,
			to_char (tbl_hd_chamado_item.data,'DD/MM/YY HH24:MI') AS data   ,
			tbl_hd_chamado_item.comentario                               ,
			tbl_hd_chamado_item.interno                                  ,
			tbl_admin.nome_completo                            AS autor  ,
			tbl_hd_chamado_item.status_item
			FROM tbl_hd_chamado_item
			JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado_item.admin
			WHERE hd_chamado = $hd_chamado
			$cond
			ORDER BY hd_chamado_item DESC";
			
	$res = @pg_query($con,$sql);

	if (pg_num_rows($res) > 0) {
?>
	<br />
	<? if($contagem_i > 0) { ?>
	<center><button class='botao_interacao' onclick='interacoes_importantes(); return false;'>Ver interações importantes</button></center><br/>
	<? } ?>
    <center><input type='button' value='Mostrar interação completa' onclick='interacoes()'></center>
    <br />
    <br />
    <table width = '750' align = 'center' cellpadding='2' cellspacing='1' border='0' name='relatorio' id='relatorio' class='relatorio'>
        <thead>
            <tr  bgcolor='#D9E8FF'>
                <th><strong>Nº</th>
                <th nowrap>Data / Status</th>
                <th>  Comentário </th>
                <th><strong>Autor </strong></th>
            </tr>
        </thead>
        <tbody>
<?php
		$total_interacoes = pg_num_rows($res);

		for ($i = 0 ; $i < $total_interacoes ; $i++) {

			$x=($total_interacoes)-$i;
			$hd_chamado_item = pg_fetch_result($res, $i, 'hd_chamado_item');
			$data_interacao  = pg_fetch_result($res, $i, 'data');
			$autor           = pg_fetch_result($res, $i, 'autor');
			$item_comentario = pg_fetch_result($res, $i, 'comentario');
			$status_item     = pg_fetch_result($res, $i, 'status_item');
			$interno         = pg_fetch_result($res, $i, 'interno');
			//$tempo_trabalho  = pg_fetch_result($res, $i, 'tempo_trabalho');

			//$autor = explode(" ",$autor);
			//$autor = $autor[0];
			$hide = false;
			$item_importante = false;
				if($interno == 't') {
					foreach($hiddenText as $value) {
						if(strpos($item_comentario,$value) !==false) {
							$hide = true;
							break;
						}
					}
				}
			
			$class = " class='normal' "; 
			if($hide) {
				$class = " class='esconde' "; 
			}	
			if (strpos($status_item, 'Importante') !== false) {
				$class = " class='importante' "; 
			}
?>
			<tr bgcolor='<?=$cor?>' <?=$class  ?>>
                <td nowrap width='25'><?=$x?></td>
                <td nowrap width='50' style="text-align:center">
                    <?=$data_interacao?><br />
                    <?=$status_item?>
                </td>
                <td width='500px'>
<?php
                if ($status_item == 'Resolvido') {
?>
                    <span class='resolvido'><b>Chamado foi resolvido nesta interação</b></span>
<?php
                }
                if ($interno == 't') {
?>
                    <span class='interno'><b>Chamado interno</b></span>
<?php
                }

                $item_comentario = str_replace($filtro,"", $item_comentario);
                $item_comentario = str_replace("body","", $item_comentario);
                echo str_replace("\\","",$item_comentario);

                $dir = "documentos/";

                if(is_dir($dir."hd-$hd_chamado-itens")){
                    if (($dh  = glob($dir."hd-$hd_chamado-itens/$hd_chamado_item-*"))) {

?>
                    <br/>
                    <br/>
                    <span class='anexo'><b>Anexos</b></span>
                    <table align='center' border='0' style='width: 100%; border-collapse: collapse; float: left; display: none;'>
                        <tr>
                            <td style='border-bottom: 0px; width: 50px; text-align:center; background-color: transparent'>
<?php
                        foreach($dh as $filename) {

                            $att_icon = (preg_match("/\.(gif|jpg|jpeg|png|tif|tiff|bmp)$/i", $filename) and $login_admin != 1749) ?
                                    "'$filename' width='24' height='24' style='max-height:24px;max-width:32px'" :
                                    "'imagem/clips.gif'";
?>
                                <div style='float:left; padding:3px;'>
								  	<!--ARQUIVO-I-->&nbsp;&nbsp;
							<a href="<?=$filename?>" target="_blank"><img src=<?=$att_icon?> border='0'></a>
                                    <a href="<?=$filename?>" rel='nozoom' name='anexosInteracao' target='_blank' style='display: block;'><?=str_replace("hd--itens/-", "", str_replace(array($dir, $hd_chamado_item, $hd_chamado), "", $filename))?></a>
                                </div>
<?php
                        }
?>
                            </td>
                        </tr>
                    </table>
<?php
                    }
                } else {
                    if (($dh  = glob($dir."$hd_chamado_item-*"))) {
?>
                    <br/>
                    <br/>
                    <span class='anexo'><b>Anexos</b></span>
                    <table align='center' border='0' style='width: 100%; border-collapse: collapse; float: left; display: none;'>
                        <tr>
                            <td style='border-bottom: 0px; width: 50px; text-align:center; background-color: transparent'>
<?php
                        foreach ($dh as $filename) {

                            $att_icon = (preg_match("/\.(gif|jpg|jpeg|png|tif|tiff|bmp)$/i", $filename) and $login_admin != 1749) ?
                                    "'$filename' width='24' height='24' style='max-height:24px;max-width:32px'" :
                                    "'imagem/clips.gif'";
?>
                                <div style='float:left; padding:3px;'>
                                    <!--ARQUIVO-I-->&nbsp;&nbsp;
                                    <a href=<?=$filename?> target ="_blank"><img src=$att_icon border='0'></a>
                                    <a href='<?=$filename?>' rel='nozoom' name='anexosInteracao' target='blank' style='display: block;'>" . str_replace("hd--itens/-", "", str_replace(array($dir, $hd_chamado_item, $hd_chamado), "", $filename)) . "</a>
                                </div>
<?php
                        }
?>
                            </td>
                        </tr>
                    </table>
<?php
                    }

                }

				if ($i == $total_interacoes - 1) {
					$sql_tdocs = "SELECT * FROM tbl_tdocs WHERE referencia_id = $hd_chamado_item AND contexto = 'hd_chamado_item'";
					$res_tdocs = pg_query($con, $sql_tdocs);

					if (pg_num_rows($res_tdocs) > 0) {
						include __DIR__ . '/../plugins/fileuploader/TdocsMirror.php';

						$tDocsMirror = new TdocsMirror();
						?>

                    <br/>
                    <br/>
                    <span class='anexo'><b>Anexos</b></span>
                    <table align='center' border='0' style='width: 100%; border-collapse: collapse; float: left; display: none;'>
                        <tr>
                            <td style='border-bottom: 0px; width: 50px; text-align:center; background-color: transparent'>

						<?php

						while ($fetch = pg_fetch_assoc($res_tdocs)) {
							$link = $tDocsMirror->get($fetch['tdocs_id']);
							$obs_tdocs = json_decode($fetch['obs'], true);
							$anexo_nome = '';

							if (array_key_exists('filename', $obs_tdocs[0])) {
								$anexo_nome = $obs_tdocs[0]['filename'];
							}

							if (array_key_exists('link', $link)) {
								?>
                                <div style='float:left; padding:3px;'>
                                    <!--ARQUIVO-I-->&nbsp;&nbsp;
                                    <a href='anexo_download.php?arquivo=<?=$link['link']?>&nome=<?=$anexo_nome?>' target ="_blank"><img src='imagem/clips.gif' border='0'></a>
                                    <a href='anexo_download.php?arquivo=<?=$link['link']?>&nome=<?=$anexo_nome?>' rel='nozoom' name='anexosInteracao' target='_blank' style='display: block;'>
										<?=(!empty($anexo_nome)) ? $anexo_nome : $link['file_name']?>
									</a>
                                </div>
								<?php
							}
						}

?>
                            </td>
                        </tr>
                    </table>
<?php

					}

				}

?>
                </td>
                <td ><?=$autor?></td>
            </tr>
<?php
            }
?>
        </tbody>
    </table>
<?php
        }

if (!in_array($login_admin, array(1749))) {
?>
</form>
</div>
</div>
<div class="trinta_2">
	<?php
		if (in_array($login_admin, array(5205,4789,8527)) || ($analista_hd == 'sim' && $login_admin != 586) || !in_array($grupo_admin, array(3,4,5,7,8))) {
			include_once("icone_pendencia_helpdesk.php");
		} 
	?>
</div>
</div>

<div id="aux" class="modal" role="dialog">
  <div class="modal-dialog">
      <div class="modal-content">
          <div class="modal-header">
              <h4 class="modal-title">Detalhe das atividades do HD</h4>
          </div>
          <div class="modal-body"></div>
      </div>
  </div>
</div>
<script type='text/javascript' src='../js/FancyZoom.js'></script>
<script type='text/javascript' src='../js/FancyZoomHTML.js'></script>
<script type="text/javascript">
setupZoom();
$(function() {
    $('.details').click(function() {
        var lnk = $(this).data('href');
        var modal = $(this).data('target');
        $(modal + ' .modal-body').load(lnk);
        $(modal).modal('show');
    });
});

</script>
<?php
}
include "rodape.php"
?>
