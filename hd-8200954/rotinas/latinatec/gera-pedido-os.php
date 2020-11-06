<?php

/*
# Telecontrol Networking
# www.telecontrol.com.br
# Geração de pedidos de pecas com base na OS
# 
# php dos perls da LATINATEC:
#gera-pedido-os-consumidor-funcional.pl
#gera-pedido-os-consumidor-plasticas.pl
#gera-pedido-os-revenda-funcional.pl
#gera-pedido-os-revenda-plasticas.pl
*/

define('ENV', 'producao');

try {

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	require dirname(__FILE__) . '/../funcoes.php';
	
	$msg_erro       = array();
	$log            = array();
	$os_agrupadas   = array();
	$peca_agrupadas = array();

	$vet['fabrica'] = 'latinatec';
	$vet['tipo']    = 'pedido';

	if (ENV == 'testes') {
		$vet['dest'] = 'andreus@telecontrol.com.br';
	} else {
		$vet['dest'] = 'helpdesk@telecontrol.com.br';
	}

	$vet['log']     = 2;

	$vet2        = $vet;
	$vet2['log'] = 1;

	/*
	 tipo_pedido |           descricao           
	-------------+-------------------------------
	 190 		 | Garantia Consumidor Funcional 
	 192 		 | Garantia Consumidor Plasticas 
	 194 		 | Garantia Revenda Funcional    
	 195 		 | Garantia Revenda Plásticas    
	*/
	
	$fabrica    = "15" ;
	$dia_semana = date('w');

	$phpCron = new PHPCron($fabrica, __FILE__);
	$phpCron->inicio();

	/* 
		HD 755863
		
		A variável $dia_semana, servirá para as condições de
		gerar pedido para cada região de acordo com o dia da semana
		
		0 -> domingo
		1 -> segunda-feira
		2 -> terça-feira
		3 -> quarta-feira
		4 -> quinta-feira
		5 -> sexta-feira
		6 -> sabado

		Irá ocorrer 1 vez por semana da seguinte forma:

		segunda-feira -> região norte (ro, ac, am, rr, pa, ap e to)
		terça-feira  -> região nordeste (ma, pi, ce, rn, pb, pe, al, se e ba)
		quarta-feira  -> região centro-oeste (ms, mt, go + df) e região sul (rs, sc e pr)
		quinta-feira  -> sudeste (es, rj, sp e mg)
	*/
	
	switch ($dia_semana) {

		case '1':
			$cond_posto_estado = " and tbl_posto_fabrica.contato_estado in ('AC','AM','AP','PA','RO','RR','TO') ";
		break;
		
		case '2':
			$cond_posto_estado = " and tbl_posto_fabrica.contato_estado in ('MA', 'PI', 'CE', 'RN', 'PB', 'PE', 'AL', 'SE', 'BA') ";
		break;
		
		case '3':
			$cond_posto_estado = " and tbl_posto_fabrica.contato_estado in ('MS', 'MT', 'GO', 'DF', 'RS', 'SC', 'PR') ";
		break;
		
		case '4':
			$cond_posto_estado = " and tbl_posto_fabrica.contato_estado in ('ES', 'RJ', 'SP', 'MG') ";
		break;

	}

	if (strlen($cond_posto_estado) > 0) {
		
		//GERA PEDIDO PARA OS POSTOS QUE NAO CONTROLAM ESTOQUE
		$sql1 = "SELECT  DISTINCT tbl_posto_fabrica.posto
				FROM    tbl_posto_fabrica
				WHERE   tbl_posto_fabrica.posto   <> 6359
				AND     tbl_posto_fabrica.fabrica = $fabrica
				AND     (tbl_posto_fabrica.credenciamento = 'CREDENCIADO' OR tbl_posto_fabrica.credenciamento = 'EM DESCREDENCIAMENTO') 
				AND 	(tbl_posto_fabrica.controla_estoque IS FALSE AND tbl_posto_fabrica.controle_estoque_novo IS FALSE AND tbl_posto_fabrica.controle_estoque_manual IS FALSE) 
				$cond_posto_estado ";

		$res1 = pg_query($con, $sql1); 

		if (pg_num_rows($res1) > 0) {

			for ($i = 0; $i < pg_num_rows($res1); $i++) {
				
				$posto = pg_result($res1, $i, 0);

				$sql2 = "SELECT tbl_os_item.peca,
						tbl_peca.referencia AS peca_referencia,
						tbl_os.os   ,
						tbl_os.posto,
						tbl_os_item.qtde,
						tbl_os_item.os_item,
						tbl_peca.devolucao_obrigatoria,
						tbl_os.consumidor_revenda
					INTO    TEMP tmp_pedido_latina_$posto
					FROM    tbl_os_item
					JOIN    tbl_servico_realizado USING (servico_realizado)
					JOIN    tbl_os_produto USING (os_produto)
					JOIN    tbl_os         USING (os)
					JOIN    tbl_peca ON tbl_peca.peca=tbl_os_item.peca AND tbl_peca.fabrica=$fabrica
					WHERE   tbl_os_item.pedido IS NULL
					AND     tbl_os.excluida    IS NOT TRUE
					AND     tbl_os.fabrica    = $fabrica
					AND 	tbl_os_item.fabrica_i = $fabrica
					AND     tbl_os_item.digitacao_item::date between current_date - 35 and current_date - 1
					AND     tbl_os.posto      = $posto
					AND     (tbl_servico_realizado.troca_de_peca AND tbl_servico_realizado.gera_pedido)	; ";

				$res2 = pg_query($con, $sql2); 

				$sql_check = "SELECT peca from tmp_pedido_latina_$posto where 1=1 limit 1";
				$res_check = pg_query($con,$sql_check); 
				
				if (pg_num_rows($res_check) > 0) {
					for ($w = 0; $w < 2; $w++) {
 
						$tipo_pedido            = null;
						$xconsumidor_revenda    = ($w < 1) ? "C" : "R";
						unset($msg_erro);

						if ($xconsumidor_revenda == 'C') {
							$tipo_pedido = '192';
						}else{
							$tipo_pedido = '195';
						}

						$sql2x = "SELECT   peca,
									os,
									SUM(qtde) AS qtde
							  FROM     tmp_pedido_latina_$posto
							  WHERE    consumidor_revenda = '$xconsumidor_revenda'
							  GROUP BY peca,os; ";
						
						$res2x = pg_query($con,$sql2x); 
					
						#tipo de pedido 190 - OS de consumidor e pecas devolucao obrigatoria true
						if (pg_num_rows($res2x) > 0) {
							
							$resultX = pg_query($con, "BEGIN TRANSACTION"); 
							
							$sql3 = "INSERT INTO tbl_pedido (
										pedido    ,
										posto     ,
										fabrica   ,
										tabela    ,
										condicao  ,
										tipo_pedido,
										consumidor_revenda
									) VALUES (
										DEFAULT ,
										$posto  ,
										$fabrica,
										(SELECT tabela FROM tbl_tabela WHERE fabrica = $fabrica and ativa order by tabela limit 1)   , 
										'101'    ,
										$tipo_pedido     ,
										'$xconsumidor_revenda'
									)
									RETURNING pedido; ";

							$res3 = pg_query($con,$sql3); 

							if (strlen(pg_last_error($con)) > 0) {
								$msg_erro[] = pg_last_error($con);
							} else {
								$pedido = pg_result($res3, 0, 'pedido');
								$log[]  = "Inserido Posto: $posto  Pedido: $pedido";
							}
							
							//INSERE OS ITENS DE ACORDO COM A PESQUISA $sql2
							$sql4 = "INSERT INTO tbl_pedido_item (
										pedido,
										peca  ,
										qtde,
										obs
									) select 
										$pedido,
										peca  ,
										qtde,
										os_item::text 
										from tmp_pedido_latina_$posto
									where posto = $posto
									AND   consumidor_revenda = '$xconsumidor_revenda'";

							$res4 = pg_query($con,$sql4);

							if (strlen(pg_last_error($con)) > 0) {
								
								$msg_erro[] = pg_last_error($con);
							
							} else {
								
								$log[] = " $pedido Inserido Item $peca qtde $qtde";
							
							}
							
							$sql5 = "SELECT fn_pedido_finaliza ($pedido,$fabrica)";
							$res5 = pg_query($con, $sql5); 

							if (strlen(pg_last_error($con)) > 0) {
									
								$msg_erro[] = pg_last_error($con);
								$log[] = "Não finalizou o pedido $pedido";

							}

							$sql6 = "SELECT fn_atualiza_os_item_pedido_item(obs::integer, $pedido,pedido_item, $fabrica)
									FROM    tbl_pedido_item where pedido=$pedido";
							
							$res6 = pg_query($con,$sql6); 
							
							if (strlen(pg_last_error($con)) > 0) {
									
								$msg_erro[] = pg_last_error($con);
								$log[] = "Nao atualizou a os_item do pedido $pedido.";

							}
							
							if ($msg_erro) {

								$res7 = pg_query($con,"ROLLBACK TRANSACTION"); 
								
							} else {
								
								$res7 = pg_query($con,"COMMIT TRANSACTION"); 
								
							}

						}

					}

				}

			}

			if ($msg_erro) {

				$msg_erro = implode("\n", $msg_erro);
				Log::log2($vet, $msg_erro);

			}
			
			if ($log) {
				
				$log = implode("\n", $log);
				Log::log2($vet2, $log);
			
			}

		}

		//GERA PEDIDO PARA OS POSTOS QUE TEM "tbl_posto_fabrica.controla_estoque TRUE e tbl_posto_fabrica.controla_estoque_novo FALSE"
		# HD 402523
		# Nesse trecho ele vai primeiro baixar o estoque e depois verificar se o posto está com estoque abaixo do estoque mí­nimo, se estiver, faz o pedido de peças para aquele posto, apenas para estoque mí­nimo * coeficiente
		# Aqui ele vai verificar se a OS tem movimentação na 'tbl_estoque_posto_movimento', e se essa movimentação está maior que zero.
		$sql8 = "SELECT  DISTINCT
						tbl_posto_fabrica.posto
				FROM    tbl_posto_fabrica
				WHERE   tbl_posto_fabrica.posto   <> 6359
				AND     tbl_posto_fabrica.fabrica = $fabrica
				AND     (tbl_posto_fabrica.credenciamento = 'CREDENCIADO' OR tbl_posto_fabrica.credenciamento = 'EM DESCREDENCIAMENTO') 
				AND 	(tbl_posto_fabrica.controla_estoque IS TRUE AND tbl_posto_fabrica.controle_estoque_novo is FALSE AND tbl_posto_fabrica.controle_estoque_manual IS FALSE )
				$cond_posto_estado " ;

		$res8 = pg_query($con,$sql8); 

		if (strlen(pg_last_error($con)) == 0 and pg_num_rows($res8) > 0) {
			
			unset($msg_erro);
			unset($log);

			$msg_erro = array();
			$log      = array();

			for ($i = 0; $i < pg_num_rows($res8); $i++) {
				
				$posto = pg_result($res8, $i, 0);
				
				#Pega o coeficiente do posto
				$sql9 = "SELECT CASE WHEN tbl_estoque_minimo_parametro.coeficiente IS NULL THEN
								1
							ELSE
								ROUND(tbl_estoque_minimo_parametro.coeficiente)
							END AS coeficiente
						FROM tbl_posto 
						LEFT JOIN tbl_estoque_minimo_parametro ON (tbl_estoque_minimo_parametro.fabrica = $fabrica AND tbl_estoque_minimo_parametro.estado = tbl_posto.estado) 
						WHERE (tbl_estoque_minimo_parametro.fabrica = $fabrica OR tbl_estoque_minimo_parametro.fabrica IS NULL) 
						AND	tbl_estoque_minimo_parametro.data_final IS NULL 
						AND tbl_posto.posto = $posto";
						
				$res9 = pg_query($con, $sql9); 

				if (strlen(pg_last_error($con)) > 0) {
			
					$msg_erro[] = pg_last_error($con);
				
				} else {
					
					$coeficiente = pg_fetch_result($res9, 0, 'coeficiente');

				}
				 
				#Pega as pecas das OS
				/**
				 * @since HD 899678 - retirado:
				 *     AND     tbl_os_item.digitacao_item::date between current_date - 7 and current_date - 1
				 *  Adicionado:
				 *     AND tbl_os_item.digitacao_item::date >= current_date - '30 days'::interval
				 */
				$sql10 = "SELECT  tbl_os_item.peca	,
								tbl_os.os   		,
								tbl_os_item.qtde	,
								tbl_os_item.os_item,
								tbl_peca.devolucao_obrigatoria,
								tbl_os.consumidor_revenda
						INTO    TEMP tmp_pedido_latina_estoque_$posto
						FROM    tbl_os_item
						JOIN    tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado and tbl_servico_realizado.fabrica = $fabrica
						JOIN    tbl_os_produto USING (os_produto)
						JOIN    tbl_os         USING (os)
						JOIN    tbl_peca ON tbl_peca.peca = tbl_os_item.peca and tbl_peca.fabrica = $fabrica
						WHERE   tbl_os_item.pedido IS NULL
						AND     tbl_os.excluida    IS NOT TRUE
						AND     tbl_os.fabrica     = $fabrica
						AND 	tbl_os_item.fabrica_i = $fabrica
						AND     tbl_os.posto       = $posto
						AND tbl_os_item.digitacao_item::date >= current_date - '30 days'::interval
						AND     (tbl_servico_realizado.troca_de_peca AND tbl_servico_realizado.gera_pedido);";
				
				$res10 = pg_query($con, $sql10); 
				
				if (strlen(pg_last_error($con)) > 0) {

					$msg_erro[] = pg_last_error($con);

				} else {

					$log[]  = 'QUERY => ' .nl2br($sql10) ."\n\n";

				}

				$sql_check = "SELECT peca from tmp_pedido_latina_estoque_$posto where 1=1 limit 1";
				$res_check = pg_query($con, $sql_check); 

				if (pg_num_rows($res_check) > 0) {
						
					for ($w = 0;  $w < 2; $w++) {

						unset($msg_erro);
						$tipo_pedido            = '';
						$xconsumidor_revenda    = ($w < 1) ? "C" : "R";

						if ($xconsumidor_revenda == 'C') {
							$tipo_pedido = '192';
						}else{
							$tipo_pedido = '195';
						}

						$sql10x = " SELECT  peca,
											SUM(qtde) AS qtde
									FROM    tmp_pedido_latina_estoque_$posto
									WHERE   consumidor_revenda = '$xconsumidor_revenda'
									GROUP BY peca    ; ";
						
						$res10x = pg_query($con, $sql10x);
						$log[]  = 'QUERY => ' .nl2br($sql10x) ."\n\n";
					
						if (pg_num_rows($res10x) > 0) {

							#Insere pedido
							$res11 = pg_query($con, "BEGIN TRANSACTION"); 

							$sql12 = "INSERT INTO tbl_pedido (
										posto     ,
										fabrica   ,
										tabela    ,
										condicao  ,
										tipo_pedido,
										consumidor_revenda
									) VALUES (
										$posto  ,
										$fabrica,
										(SELECT tabela FROM tbl_tabela WHERE fabrica = $fabrica and ativa order by tabela limit 1)   , 
										'101'    ,
										$tipo_pedido,
										'$xconsumidor_revenda'
									)
									RETURNING pedido;";

							$res12 =  pg_query($con,$sql12); 

							if (strlen(pg_last_error($con)) > 0) {

								$msg_erro[] = pg_last_error($con);

							} else {

								$pedido = pg_result($res12,0,0);
								$log[]  = "Inserido Posto: $posto  Pedido: $pedido";

							} #Fim insere pedido

							#For de pecas das OS
							for ($z = 0; $z < pg_num_rows($res10x); $z++) {

								list($peca, $qtde) = pg_fetch_row($res10x, $z);

								$sql_estoque  = "SELECT qtde from tbl_estoque_posto where fabrica = $fabrica and posto = $posto and peca = $peca";
								$res_estoque  = pg_query($con, $sql_estoque); 
								$qtde_estoque = (pg_num_rows($res_estoque) > 0) ? pg_result($res_estoque, 0, 0) : "1";

								if ($qtde_estoque <= 0) {

									#Baixa o estoque e verifica o estque minimo!
									$sql13 = "INSERT INTO tbl_estoque_posto_movimento (
													fabrica, 
													posto, 
													data, 
													qtde_saida, 
													peca,
													os)
											select 
													$fabrica,
													$posto,
													NOW(),
													qtde,
													peca,
													os
											from tmp_pedido_latina_estoque_$posto
											where peca = $peca
											  and consumidor_revenda = '$xconsumidor_revenda'";
									
									$res13 = pg_query($con, $sql13); 
									
									if (strlen(pg_last_error($con)) > 0) {

										$msg_erro[] = pg_last_error($con);

									} else {

										$log[]  = 'QUERY => ' .nl2br($sql13) ."\n\n";

									}

									#Verifica se tem posto com peca abaixo do estoque minimo, se tiver faz o pedido
									$sql14 = "SELECT posto,peca,qtde FROM tbl_estoque_posto 
											WHERE tbl_estoque_posto.fabrica = $fabrica 
											AND tbl_estoque_posto.posto = $posto
											AND tbl_estoque_posto.peca = $peca;";
									
									$res14 = pg_query($con,$sql14); 
									
									if (strlen(pg_last_error($con)) > 0) {

										$msg_erro[] = pg_last_error($con);

									}
									
									if (pg_num_rows($res14) > 0) {

										$sql15 = "UPDATE tbl_estoque_posto 
													SET  qtde = tbl_estoque_posto.qtde - $qtde,
														 estoque_minimo = (
															SELECT SUM(qtde_saida)
															FROM tbl_estoque_posto_movimento 
															WHERE tbl_estoque_posto_movimento.fabrica = $fabrica 
															AND tbl_estoque_posto_movimento.posto = $posto 
															AND tbl_estoque_posto_movimento.peca = $peca
															AND data >= current_date - '15 days'::interval
														 ) * 2 * $coeficiente
													WHERE 	tbl_estoque_posto.fabrica = $fabrica
													AND 	tbl_estoque_posto.posto = $posto
													AND 	tbl_estoque_posto.peca = $peca;";
										
										$res15 = pg_query($con,$sql15); 
										
										if (strlen(pg_last_error($con)) > 0) {

											$msg_erro[] = pg_last_error($con);

										}
								
									} else {

										$sql16 = "INSERT INTO tbl_estoque_posto (
													fabrica, 
													posto, 
													peca,
													qtde,
													estoque_minimo)
												VALUES (
													$fabrica,
													$posto,
													$peca,
													-$qtde,
													(SELECT SUM(qtde_saida)
														FROM tbl_estoque_posto_movimento 
														WHERE tbl_estoque_posto_movimento.fabrica = $fabrica 
														AND tbl_estoque_posto_movimento.posto = $posto 
														AND tbl_estoque_posto_movimento.peca = $peca
														AND data >= current_date - '15 days'::interval
													) * 2 * $coeficiente
												)";

										$res16 = pg_query($con,$sql16); 
																	
										if (strlen(pg_last_error($con)) > 0) {
											$msg_erro[] = pg_last_error($con);
										}

									}
									#Fim Baixa estoque
									
									#Verifica se tem posto com peca abaixo do estoque minimo, se tiver faz o pedido
									$sql17 = "SELECT 	tbl_estoque_posto.posto AS id_posto,
														tbl_estoque_posto.peca AS id_peca,
														tbl_estoque_posto.qtde AS quantidade,
														tbl_estoque_posto.estoque_minimo,
														tbl_estoque_posto.estoque_minimo - tbl_estoque_posto.qtde AS qtde_pedido
												FROM tbl_estoque_posto 
												WHERE tbl_estoque_posto.fabrica = $fabrica 
												AND tbl_estoque_posto.posto = $posto 
												AND tbl_estoque_posto.peca = $peca
												AND tbl_estoque_posto.estoque_minimo >= tbl_estoque_posto.qtde;";
										
									$res17 = pg_query($con,$sql17); 
									
									if (strlen(pg_last_error($con)) > 0) {
										$msg_erro[] = pg_last_error($con);
									}

									if (pg_num_rows($res17) > 0) {

										list($id_posto, $id_peca, $quantidade, $estoque_minimo, $qtde_pedido) = pg_fetch_row($res17, 0, 0);
										if ($qtde > 0){

											$sql18 = "INSERT INTO tbl_pedido_item (
														pedido,
														peca  ,
														qtde,
														obs
													) select 
														$pedido,
														peca  ,
														qtde,
														os_item::text 											
													  from tmp_pedido_latina_estoque_$posto
													  where peca = $peca
													    and consumidor_revenda = '$xconsumidor_revenda'";
											
											$res18 = pg_query($con,$sql18); 
											
											if (strlen(pg_last_error($con)) > 0) {

												$msg_erro[] = pg_last_error($con);

											} else {

												$log[] = " $pedido Inserido Item $peca qtde $qtde_pedido " ;

											}

										}
										
										$sql19 = "SELECT pedido FROM tbl_pedido WHERE pedido = $pedido";
										$res19 = pg_query($con, $sql19); 

										if (pg_num_rows($res19) > 0) {
											
											$sql20 = "SELECT fn_pedido_finaliza ($pedido,$fabrica)";
											$res20 = pg_query($con, $sql20); 

											if (strlen(pg_last_error($con)) > 0) {

												$msg_erro[] = pg_last_error($con);
												$log[]      = "Nao finalizou o pedido $pedido.";

											}

											$sql21 = "SELECT fn_atualiza_os_item_pedido_item(obs::integer, $pedido,pedido_item, $fabrica)
														FROM tbl_pedido_item  
														WHERE pedido = $pedido
														AND  peca = $peca";

											$res21 = pg_query($con,$sql21); 
											
											if (strlen(pg_last_error($con)) > 0) {

												$msg_erro[] = pg_last_error($con);
												$log[]      = "Nao atualizou a os_item do pedido $pedido.";

											}

										} else {

											$msg_erro[] = "Posto: $posto - Peça: $peca - PEDIDO SEM ITENS - PEÇAS EM ESTOQUE";

										} #Fim do pedido

									} else {
										
										//SE NAO FAZ PEDIDO, EH PQ USOU DO ESTOQUE DO POSTO,
										//ENTAO MUDA-SE O SERVICO REALIZADO PARA TROCA DE PECA DO ESTOQUE INTERNO
										$sql_sr = "SELECT fn_estoque_servico_realizado(os_item,peca,$fabrica)
													 FROM tmp_pedido_latina_estoque_$posto
													where peca = $peca
													  and consumidor_revenda = '$xconsumidor_revenda'";
										
										$res_sr = pg_query($con,$sql_sr); 
										
										if (strlen(pg_last_error($con)) > 0) {

											$msg_erro[] = pg_last_error($con);
										
										}

									}
									
								} else {

									$sqlxx = "SELECT peca,
														os,
														SUM(qtde) AS qtde
												FROM  tmp_pedido_latina_estoque_$posto
												WHERE peca = $peca		
												  and consumidor_revenda = '$xconsumidor_revenda'
												GROUP BY peca, os;";

									$resxx = pg_query($con, $sqlxx); 

									if (pg_num_rows($resxx) > 0) {
										
										for ($b = 0; $b < pg_num_rows($resxx); $b++) {

											list($peca, $os, $qtde) = pg_fetch_row($resxx, $b);

											#Baixa o estoque e verifica o estque minimo!
											$sql13 = "INSERT INTO tbl_estoque_posto_movimento (fabrica, posto, data, qtde_saida, peca,os   ) VALUES (
													  $fabrica,
													  $posto,
													  now(),
													  $qtde,
													  $peca,
													  $os
														)";
											
											$res13 = pg_query($con,$sql13); 
											
											if (strlen(pg_last_error($con)) > 0) {

												$msg_erro[] = pg_last_error($con);

											}
										
											#Verifica se tem posto com peca abaixo do estoque minimo, se tiver faz o pedido
											$sql14 = "SELECT posto,peca,qtde FROM tbl_estoque_posto 
														WHERE tbl_estoque_posto.fabrica = $fabrica 
														AND tbl_estoque_posto.posto = $posto
														AND tbl_estoque_posto.peca = $peca;";
											
											$res14 = pg_query($con, $sql14); 
											
											if (strlen(pg_last_error($con)) > 0) {

												$msg_erro[] = pg_last_error($con);

											}
											
											if (pg_num_rows($res14) > 0) {

												$sql15 = "UPDATE tbl_estoque_posto 
															SET qtde = tbl_estoque_posto.qtde - $qtde,
																estoque_minimo = (
																	SELECT SUM(qtde_saida)
																	FROM tbl_estoque_posto_movimento 
																	WHERE tbl_estoque_posto_movimento.fabrica = $fabrica 
																	AND tbl_estoque_posto_movimento.posto = $posto 
																	AND tbl_estoque_posto_movimento.peca = $peca
																	AND data >= current_date - '15 days'::interval
																) * 2 * $coeficiente
														WHERE 	tbl_estoque_posto.fabrica = $fabrica
														AND 	tbl_estoque_posto.posto = $posto
														AND 	tbl_estoque_posto.peca = $peca;";
												
												$res15 = pg_query($con,$sql15); 
												
												if (strlen(pg_last_error($con)) > 0) {

													$msg_erro[] = pg_last_error($con);

												}
										
											} else {

												$sql16 = "INSERT INTO tbl_estoque_posto (
																fabrica, 
																posto, 
																peca,
																qtde,
																estoque_minimo
															) VALUES (
																$fabrica,
																$posto,
																$peca,
																-$qtde,
																(SELECT SUM(qtde_saida)
																	FROM tbl_estoque_posto_movimento 
																	WHERE tbl_estoque_posto_movimento.fabrica = $fabrica 
																	AND tbl_estoque_posto_movimento.posto = $posto 
																	AND tbl_estoque_posto_movimento.peca = $peca
																	AND data >= current_date - '15 days'::interval
																) * 2 * $coeficiente
															)";

												$res16 = pg_query($con,$sql16); 
																			
												if (strlen(pg_last_error($con)) > 0) {

													$msg_erro[] = pg_last_error($con);

												}

											}#Fim Baixa estoque
											
											#Verifica se tem posto com peça abaixo do estoque mí­nimo, se tiver faz o pedido
											$sql17 = "SELECT 	tbl_estoque_posto.posto AS id_posto,
															tbl_estoque_posto.peca AS id_peca,
															tbl_estoque_posto.qtde AS quantidade,
															tbl_estoque_posto.estoque_minimo,
															tbl_estoque_posto.estoque_minimo - tbl_estoque_posto.qtde AS qtde_pedido
													FROM tbl_estoque_posto 
													WHERE tbl_estoque_posto.fabrica = $fabrica 
													AND tbl_estoque_posto.posto = $posto 
													AND tbl_estoque_posto.peca = $peca
													AND tbl_estoque_posto.estoque_minimo >= tbl_estoque_posto.qtde;";
											
											$res17 = pg_query($con,$sql17); 
											
											if (strlen(pg_last_error($con)) > 0) {
												$msg_erro[] = pg_last_error($con);
											}
											
											if (pg_num_rows($res17) > 0) {

												list($id_posto, $id_peca, $quantidade, $estoque_minimo, $qtde_pedido) = pg_fetch_row($res17,0,0);
												if ($qtde > 0){
													
													$sql18 = "INSERT INTO tbl_pedido_item (
																pedido,
																peca  ,
																qtde
															)VALUES(
																$pedido,
																$peca,
																$qtde
															) returning pedido_item";
													
													$res18 = pg_query($con, $sql18); 
													
													if (strlen(pg_last_error($con)) > 0) {

														$msg_erro[] = pg_last_error($con);

													} else {

														$log[] = " $pedido Inserido Item $peca qtde $qtde_pedido " ;
														$pedido_item =  pg_result($res18, 0, 0);

													}

												}
												$sql19 = "SELECT pedido FROM tbl_pedido WHERE pedido = $pedido";
												$res19 = pg_query($con,$sql19); 

												if (pg_num_rows($res19)>0) {
													
													$sql20 = "SELECT fn_pedido_finaliza ($pedido,$fabrica)";
													
													$res20 = pg_query($con,$sql20); 
													if (strlen(pg_last_error($con)) > 0) {
														$msg_erro[] = pg_last_error($con);
														$log[] = "Nao finalizou o pedido $pedido.";
													}

													$sql21 = "SELECT fn_atualiza_os_item_pedido_item(os_item, $pedido,$pedido_item, $fabrica)
															FROM   tmp_pedido_latina_estoque_$posto 
															WHERE peca=$peca and os=$os";
													$res21 = pg_query($con,$sql21); 
													
													if (strlen(pg_last_error($con)) > 0) {
														$msg_erro[] = pg_last_error($con);
														$log[] = "Nao atualizou a os_item do pedido $pedido.";
													}

												} else {

													$msg_erro[] = "Posto: $posto - Peça: $peca - PEDIDO SEM ITENS - PEí‡AS EM ESTOQUE";								
												}#Fim do pedido

											} else {
												
												//SE NAO FAZ PEDIDO, EH PQ USOU DO ESTOQUE DO POSTO,
												//ENTAO MUDA-SE O SERVICO REALIZADO PARA TROCA DE PECA DO ESTOQUE INTERNO
												$sql_sr = "SELECT fn_estoque_servico_realizado(os_item,peca,$fabrica)
															FROM tmp_pedido_latina_estoque_$posto where peca = $peca and os = $os";
												
												$res_sr = pg_query($con,$sql_sr); 
												
												if (strlen(pg_last_error($con)) > 0) {

													$msg_erro[] = pg_last_error($con);
												
												}

											}

										}

									}
									
								}

							}

							if ($msg_erro) {

								$res24 = pg_query($con,"ROLLBACK TRANSACTION"); 
								
							} else {

								$res25 = pg_query($con,"COMMIT TRANSACTION"); 

							}

						}

					}

				}

			}

			if ($msg_erro) {
			
				$msg_erro = implode("\n\r", $msg_erro);
				Log::log2($vet, $msg_erro);

			}

			if ($log) {
				
				$log = implode("\n\r", $log);
				Log::log2($vet2, $log);
			
			}

		}
		
		//GERA PEDIDO PARA OS POSTOS QUE TEM "tbl_posto_fabrica.controla_estoque FALSE e tbl_posto_fabrica.controla_estoque_novo TRUE"
		$sql26 = "SELECT  DISTINCT
						tbl_posto_fabrica.posto
				FROM    tbl_posto_fabrica
				WHERE   tbl_posto_fabrica.posto   <> 6359
				AND     tbl_posto_fabrica.fabrica = $fabrica
				AND     (tbl_posto_fabrica.credenciamento = 'CREDENCIADO' OR tbl_posto_fabrica.credenciamento = 'EM DESCREDENCIAMENTO') 
				AND 	(tbl_posto_fabrica.controla_estoque IS FALSE AND tbl_posto_fabrica.controle_estoque_novo is TRUE AND tbl_posto_fabrica.controle_estoque_manual IS FALSE)
				$cond_posto_estado ";
		
		$res26 = pg_query($con, $sql26); 

		if (strlen(pg_last_error($con)) == 0 and pg_num_rows($res26) > 0) {
			
			unset($msg_erro);
			unset($log);

			$msg_erro = array();
			$log      = array();
			
			for ($i = 0; $i < pg_num_rows($res26); $i++) {

				$posto = pg_result($res26, $i, 0);

				#Pega o coeficiente do posto
				$sql27 = "SELECT CASE WHEN tbl_estoque_minimo_parametro.coeficiente IS NULL THEN
								1
							ELSE
								ROUND(tbl_estoque_minimo_parametro.coeficiente)
							END AS coeficiente
						FROM tbl_posto 
						LEFT JOIN tbl_estoque_minimo_parametro ON (tbl_estoque_minimo_parametro.fabrica = $fabrica AND tbl_estoque_minimo_parametro.estado = tbl_posto.estado) 
						WHERE (tbl_estoque_minimo_parametro.fabrica = $fabrica OR tbl_estoque_minimo_parametro.fabrica IS NULL) 
						AND	tbl_estoque_minimo_parametro.data_final IS NULL 
						AND tbl_posto.posto = $posto";
				
				$res27 = pg_query($con, $sql27); 

				if (strlen(pg_last_error($con)) > 0) {

					$msg_erro[] = 'erro $sql27: '.pg_last_error($con);

				} else {

					$coeficiente = pg_fetch_result($res27, 0, 'coeficiente');

				}

				#Pega as pecas das OS
				$sql28 = "SELECT 	tbl_os_item.peca	,
									tbl_os.os   		,
									tbl_os_item.qtde	,
									tbl_os_item.os_item ,
									tbl_os.posto,
									tbl_peca.devolucao_obrigatoria
						INTO    TEMP tmp_pedido_latina_estoque_$posto
						FROM    tbl_os_item
						JOIN    tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado and tbl_servico_realizado.fabrica=$fabrica
						JOIN    tbl_os_produto USING (os_produto)
						JOIN    tbl_os         USING (os)
						JOIN 	tbl_peca ON tbl_peca.peca = tbl_os_item.peca
						WHERE   tbl_os_item.pedido IS NULL
						AND     tbl_os.excluida    IS NOT TRUE
						AND     tbl_os.fabrica     = $fabrica 
						AND 	tbl_os_item.fabrica_i = $fabrica
						AND     tbl_os_item.digitacao_item::date between current_date - 7 and current_date - 1
						AND     tbl_os.posto       = $posto
						AND     (tbl_servico_realizado.troca_de_peca AND tbl_servico_realizado.gera_pedido); ";
				$res28 = pg_query($con,$sql28); 

				if (strlen(pg_last_error($con)) > 0) {

					$msg_erro[] = 'erro $sql28: '.pg_last_error($con);

				}

				$sql_check = "SELECT peca from tmp_pedido_latina_estoque_$posto where posto = $posto limit 1";
				$res_check = pg_query($con, $sql_check); 

				if (pg_num_rows($res_check) > 0) {
					for ($w = 0;  $w < 2; $w++) { 
						
						unset($msg_erro);
						$xdevolucao_obrigatoria = ($w % 2) ? "'t'" : "'f'"; 

						//USADO PARA O PEDIDO
						if ($xdevolucao_obrigatoria == "'t'") {
							$tipo_pedido = ' , 190';
						}

						if ($xdevolucao_obrigatoria == "'f'") {
							$tipo_pedido = ' , 192';
						}

						$sql28x = "
							SELECT  peca,
									os   ,
									SUM(qtde) AS qtde
							FROM    tmp_pedido_latina_estoque_$posto
							WHERE   devolucao_obrigatoria = $xdevolucao_obrigatoria
							GROUP BY 
									peca    ,
									os      ;";
						$res28x = pg_query($con,$sql28x); 
						
						if (pg_num_rows($res28x) > 0) {#Insere pedido
							
							$sql30 = "INSERT INTO tbl_pedido (
										posto     ,
										fabrica   ,
										tabela    ,
										condicao  ,
										tipo_pedido
									) VALUES (
										$posto  ,
										$fabrica,
										(SELECT tabela FROM tbl_tabela WHERE fabrica = $fabrica and ativa order by tabela limit 1)   , 
										'101'    
										$tipo_pedido
									)
									RETURNING pedido;";

							$res30 =  pg_query($con,$sql30); 

							if (strlen(pg_last_error($con)) > 0) {
							
								$msg_erro[] = 'erro $sql30: '.pg_last_error($con);

							} else {

								$pedido = pg_result($res30, 0, 0);
								$log[]  = "Inserido Posto: $posto  Pedido: $pedido";

							}
							#Fim insere pedido
							
							#For de pecas das OS
							for ($z = 0; $z < pg_num_rows($res28x); $z++) {

								$res = pg_query($con,'BEGIN TRANSACTION');

								list($peca, $os, $qtde) = pg_fetch_row($res28x, $z);

								if ($peca_agrupadas) {

									if (in_array($peca, $peca_agrupadas)) {
										continue;
									}

								}
								
								if ($os_agrupadas) {

									if (in_array($os, $os_agrupadas)) {
										continue;
									}

								}

								#Verifica se tem posto com peca abaixo do estoque minimo, se tiver faz o pedido
								$sql31 = "SELECT posto,peca,qtde FROM tbl_estoque_posto 
										WHERE tbl_estoque_posto.fabrica = $fabrica 
										AND tbl_estoque_posto.posto = $posto
										AND tbl_estoque_posto.peca = $peca;";

								$res31 = pg_query($con, $sql31); 
								
								if (strlen(pg_last_error($con)) > 0) {

									$msg_erro[] = 'erro $sql31: '.pg_last_error($con);

								}

								if (pg_num_rows($res31) == 0) {
									
									$sql32 = "INSERT INTO tbl_estoque_posto (
												fabrica, 
												posto, 
												peca,
												qtde, 
												consumo_mensal,
												estoque_minimo)
											VALUES (
												$fabrica,
												$posto,
												$peca,
												-$qtde,
												0,
												0
											)";
									
									$res32 = pg_query($con, $sql32); 
									
									$primeira_carga = 't';

									if (strlen(pg_last_error($con)) > 0) {

										$msg_erro[] = 'erro $sql32: '.pg_last_error($con);

									}

								} else {

									$primeira_carga = 'f';

								}
														
								#Verifica se tem posto com peca abaixo do estoque minimo, se tiver faz o pedido
								$sql33 = "SELECT tbl_estoque_posto.posto AS id_posto,
												tbl_estoque_posto.peca AS id_peca,
												tbl_estoque_posto.qtde AS quantidade,
												tbl_estoque_posto.estoque_minimo,
												tbl_estoque_posto.estoque_minimo - tbl_estoque_posto.qtde AS qtde_pedido
										FROM tbl_estoque_posto 
										WHERE tbl_estoque_posto.fabrica = $fabrica 
										AND tbl_estoque_posto.posto = $posto 
										AND tbl_estoque_posto.peca = $peca
										AND tbl_estoque_posto.estoque_minimo >= tbl_estoque_posto.qtde;";
								
								$res33 = pg_query($con, $sql33); 
								
								if (strlen(pg_last_error($con)) > 0) {
									$msg_erro[] = 'erro $sql33: '.pg_last_error($con);
								}


								if (pg_num_rows($res33) > 0) {

									if ($os_agrupadas) {
										$os_processadas = implode(',', $os_agrupadas);
										$cond_os_agrupadas = " and os not in ($os_processadas) ";
									}

									$sql_agrupa_pecas = "	SELECT  peca,
																	SUM(qtde) AS qtde
															FROM    tmp_pedido_latina_estoque_$posto
															WHERE devolucao_obrigatoria = $xdevolucao_obrigatoria
															and peca = $peca

															GROUP BY 
																	peca; ";

									$res_agrupa_pecas = pg_query($con, $sql_agrupa_pecas); 

									if (pg_num_rows($res_agrupa_pecas) > 0) {
										
										$res_begin_1 = pg_query($con,"BEGIN TRANSACTION"); 
										$zpeca = pg_result($res_agrupa_pecas,0,0);
										$zqtde = pg_result($res_agrupa_pecas,0,1);
										
										$peca_agrupadas[] = $zpeca;

										if ($qtde_processadas) {
											$zqtde = $zqtde - $qtde_processadas;
										}

										//O SERVICO REALIZADO SERÁ SEMPRE ALTERADO NESTE TIPO DE ESTOQUE
										$sql_sr = "SELECT fn_estoque_servico_realizado(os_item,peca,$fabrica)
													FROM tmp_pedido_latina_estoque_$posto where peca = $zpeca $cond_os_agrupadas";
										
										$res_sr = pg_query($con,$sql_sr); 
										
										if (strlen(pg_last_error($con)) > 0) {

											$msg_erro[] = 'erro $sql_sr: '.pg_last_error($con);
										
										}

										#Verifica se tem posto com peca abaixo do estoque minimo, se tiver faz o pedido
										$sql34 = "SELECT posto,peca,qtde 
												FROM tbl_estoque_posto 
												WHERE tbl_estoque_posto.fabrica = $fabrica 
												AND tbl_estoque_posto.posto = $posto
												AND tbl_estoque_posto.peca = $zpeca;";
										
										$res34 = pg_query($con,$sql34); 
										
										if (strlen(pg_last_error($con)) > 0) {
											$msg_erro[] = 'erro $sql34: '.pg_last_error($con);
										}

										$sql35 = "SELECT SUM(qtde)
													FROM tbl_os_item
													JOIN tbl_os_produto using (os_produto)
													JOIN tbl_os using (os)
													JOIN tbl_servico_realizado on tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
													WHERE tbl_os_item.peca = $zpeca 
													AND tbl_os_item.digitacao_item::date >= current_date - '30 days'::interval
													AND tbl_os.fabrica = $fabrica
													AND tbl_os.posto = $posto
													AND tbl_os.excluida IS NOT TRUE
													AND tbl_servico_realizado.fabrica=$fabrica
													AND tbl_servico_realizado.peca_estoque is true ";

										$res35 = pg_query($con, $sql35);

										if (pg_result($res35,0,0) == 'null' or pg_result($res35,0,0) == '') {
											$soma_qtde_saida = 0;
										} else {
											$soma_qtde_saida = pg_result($res35, 0, 0);
										}

										if (pg_num_rows($res34) > 0) {
										
											$sql36 = "UPDATE tbl_estoque_posto 
														SET estoque_minimo = $soma_qtde_saida * $coeficiente,
															consumo_mensal = $soma_qtde_saida 
														WHERE 	tbl_estoque_posto.fabrica = $fabrica
														AND 	tbl_estoque_posto.posto = $posto
														AND 	tbl_estoque_posto.peca = $zpeca;";
											
											$res36 = pg_query($con,$sql36); 
											
											if (strlen(pg_last_error($con)) > 0) {

												$msg_erro[] = 'erro $sql36: '.pg_last_error($con);

											}
									
										}#Fim Baixa estoque
										
										//Pega quantidade a ser usada no pedido 
										$sql_consumo_mensal = "SELECT (consumo_mensal-qtde) as qtde_baixa 
																from tbl_estoque_posto 
																where posto = $posto 
																AND fabrica = $fabrica 
																AND peca = $zpeca";
										$res_consumo_mensal = pg_query($con, $sql_consumo_mensal); 
										
										$qtde_baixa = (pg_num_rows($res_consumo_mensal) > 0) ? pg_result($res_consumo_mensal, 0, 0) : 0;

										if (strlen(pg_last_error($con)) > 0) {

											$msg_erro[] ='erro $sql_consumo_mensal: '. pg_last_error($con);

										}

										#Movimenta o estoque
										$sql37 = "INSERT INTO tbl_estoque_posto_movimento (
														fabrica, 
														posto, 
														data, 
														qtde_saida, 
														peca,
														os,
														pedido)
												select 
														$fabrica,
														$posto,
														NOW(),
														qtde,
														peca,
														os,
														$pedido
												from tmp_pedido_latina_estoque_$posto 
												where peca = $zpeca $cond_os_agrupadas";
										
										$res37 = pg_query($con,$sql37); 
										
										if (strlen(pg_last_error($con)) > 0) {
											
											$msg_erro[] = 'erro $sql37: '.pg_last_error($con);

										}
										
										if ($primeira_carga == 'f') {
											
											$sql_estoque_posto = "
													UPDATE tbl_estoque_posto set 
															qtde = qtde - $zqtde
													WHERE tbl_estoque_posto.fabrica = $fabrica 
													AND   tbl_estoque_posto.posto   = $posto 
													AND   tbl_estoque_posto.peca    = $zpeca ";

										} else {

											$sql_estoque_posto = "
												UPDATE tbl_estoque_posto set qtde = 0 
												WHERE tbl_estoque_posto.fabrica = $fabrica 
												AND   tbl_estoque_posto.posto   = $posto 
												AND   tbl_estoque_posto.peca    = $zpeca;

												UPDATE tbl_estoque_posto set 

														qtde = qtde - $zqtde

												WHERE tbl_estoque_posto.fabrica = $fabrica 
												AND   tbl_estoque_posto.posto   = $posto 
												AND   tbl_estoque_posto.peca    = $zpeca; ";

										}

										$res_estoque_posto = pg_query($con, $sql_estoque_posto); 

										if (strlen(pg_last_error($con)) > 0) {

											$msg_erro[] = 'erro $sql_estoque_Posto: '.pg_last_error($con);

										}

										list($id_posto, $id_peca, $quantidade, $estoque_minimo, $qtde_pedido) = pg_fetch_row($res33,$z);
										if ($qtde_baixa > 0){

											$sql38 = "INSERT INTO tbl_pedido_item (
														pedido,
														peca  ,
														qtde
													) VALUES (
														$pedido,
														$zpeca  ,
														$qtde_baixa
													)";
											
											$res38 = pg_query($con, $sql38);

											if (strlen(pg_last_error($con)) > 0) {

												$msg_erro[] = 'erro $sql38: '.pg_last_error($con);

											} else {

												$log[] = " $pedido Inserido Item $zpeca qtde $qtde_pedido";

											}

										}

										$sql39 = "SELECT pedido FROM tbl_pedido WHERE pedido = $pedido";
										$res39 = pg_query($con,$sql39); 

										if (pg_num_rows($res39) > 0) {
											
											$sql40 = "SELECT fn_pedido_finaliza ($pedido,$fabrica)";
											$res40 = pg_query($con,$sql40); 

											if (strlen(pg_last_error($con)) > 0) {

												$msg_erro[] = 'erro $sql40: '.pg_last_error($con);
												$log[] = "Nao finalizou o pedido $pedido.";

											}

										} else {

											$msg_erro[] = "Posto: $posto - Peca: $zpeca - PEDIDO SEM ITENS - PECAS EM ESTOQUE";

										}
										
										#Fim do pedido
										if ($msg_erro) {

											$res24 = pg_query($con, "ROLLBACK TRANSACTION"); 
											
										} else {

											unset($os_agrupadas);
											$qtde_processadas  = 0;
											$cond_os_agrupadas = "";
											$res25 = pg_query($con, "COMMIT TRANSACTION");

										}

									}

								} else {
									
									//se o saldo do estoque for maior que o estoque minimo, vai buscar na temp agrupando por OS...
									//e vai baixar o estoque ate que o estoque minimo seja >= saldo
									$sql_agrupa_os = "SELECT  peca,
														os,
														SUM(qtde) AS qtde
														FROM    tmp_pedido_latina_estoque_$posto
														WHERE  devolucao_obrigatoria = $xdevolucao_obrigatoria 
														and peca = $peca
														GROUP BY
														peca, os   ;";

									$res_agrupa_os = pg_query($con, $sql_agrupa_os); 

									if (pg_num_rows($res_agrupa_os) > 0) {

										if ($os_agrupadas) {
											unset($os_agrupadas);
										}

										$qtde_processadas = 0;

										for ($q = 0; $q < pg_num_rows($res_agrupa_os); $q++) {
											
											list($xpeca, $xos, $xqtde) = pg_fetch_row($res_agrupa_os, $q);

											//SE O ESTOQUE MINIMO FOR MENOR QUE O SALDO, NAO GERA PEDIDO,
											//FAZ MOVIMENTACAO E TROCA SERVICO REALIZADO

											#Verifica tem peca com estoque minimo >= saldo, se nao tiver, 
											#Vai baixando e nao volta para gerar pedido...
											$sql_ver_em = "SELECT 	tbl_estoque_posto.posto AS id_posto,
															tbl_estoque_posto.peca AS id_peca,
															tbl_estoque_posto.qtde AS quantidade,
															tbl_estoque_posto.estoque_minimo,
															tbl_estoque_posto.estoque_minimo - tbl_estoque_posto.qtde AS qtde_pedido
													FROM tbl_estoque_posto 
													WHERE tbl_estoque_posto.fabrica = $fabrica 
													AND tbl_estoque_posto.posto = $posto 
													AND tbl_estoque_posto.peca = $xpeca
													AND tbl_estoque_posto.estoque_minimo >= tbl_estoque_posto.qtde;";
											
											$res_ver_em = pg_query($con,$sql_ver_em); 

											if (strlen(pg_last_error($con)) > 0) {
												$msg_erro[] = 'erro $sql_ver_em: '.pg_last_error($con);
											}

											if (pg_num_rows($res_ver_em) == 0) {
												
												$res_another_begin = pg_query($con, 'BEGIN TRANSACTION'); 
												
												$os_agrupadas[] = $xos;
												$qtde_processadas = $qtde_processadas + $xqtde;
												//echo "ESTOQUE MINIMO < SALDO - não gera pedido - $peca \n\n";

												$sql_sr = "SELECT fn_estoque_servico_realizado(os_item,peca,$fabrica)
															FROM tmp_pedido_latina_estoque_$posto where peca = $xpeca and os=$xos";
												
												$res_sr = pg_query($con,$sql_sr); 
												
												if (strlen(pg_last_error($con)) > 0) {

													$msg_erro[] = 'erro $sql_sr: '.pg_last_error($con);
												
												}

												#Verifica se tem posto com peça abaixo do estoque mí­nimo, se tiver faz o pedido
												$sql34 = "SELECT posto,peca,qtde 
														FROM tbl_estoque_posto 
														WHERE tbl_estoque_posto.fabrica = $fabrica 
														AND tbl_estoque_posto.posto = $posto
														AND tbl_estoque_posto.peca = $xpeca;";
												
												$res34 = pg_query($con,$sql34); 
												
												if (strlen(pg_last_error($con)) > 0) {
													$msg_erro[] = 'erro $sql34 -2: '.pg_last_error($con);
												}

												$sql35 = "SELECT SUM(qtde)
															FROM tbl_os_item
															JOIN tbl_os_produto using (os_produto)
															JOIN tbl_os using (os)
															JOIN tbl_servico_realizado on tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
															WHERE tbl_os_item.peca = $xpeca 
															AND tbl_os_item.digitacao_item::date >= current_date - '30 days'::interval
															AND tbl_os.fabrica = $fabrica
															AND tbl_os.posto = $posto
															AND tbl_os.excluida IS NOT TRUE
															AND tbl_servico_realizado.fabrica = $fabrica
															AND tbl_servico_realizado.peca_estoque is true ";

												$res35 = pg_query($con,$sql35); 

												if (pg_result($res35, 0, 0) == 'null' or pg_result($res35,0,0) == '') {
													$xsoma_qtde_saida = 0;
												} else {
													$xsoma_qtde_saida = pg_result($res35, 0, 0);
												}

												if (pg_num_rows($res34) > 0) {
												
													$sql36 = "UPDATE tbl_estoque_posto 
																SET estoque_minimo = $xsoma_qtde_saida * $coeficiente,
																	consumo_mensal = $xsoma_qtde_saida 
																WHERE 	tbl_estoque_posto.fabrica = $fabrica
																AND 	tbl_estoque_posto.posto = $posto
																AND 	tbl_estoque_posto.peca = $xpeca;";
													
													$res36 = pg_query($con, $sql36); 
													
													if (strlen(pg_last_error($con)) > 0) {

														$msg_erro[] = 'erro $sql36 -2: '.pg_last_error($con);

													}

												}
												#Fim Baixa estoque
												
												//Pega quantidade a ser usada no pedido 
												$sql_consumo_mensal = "SELECT (consumo_mensal-qtde) as qtde_baixa 
																		from tbl_estoque_posto 
																		where posto = $posto 
																		AND fabrica = $fabrica 
																		AND peca = $xpeca";
												$res_consumo_mensal = pg_query($con,$sql_consumo_mensal); 
												
												$qtde_baixa = (pg_num_rows($res_consumo_mensal) > 0) ? pg_result($res_consumo_mensal,0,0) : 0;

												if (strlen(pg_last_error($con)) > 0) {	
													$msg_erro[] = 'erro $sql_consumo_mensal 2: '.pg_last_error($con);
												}

												#Baixa o estoque e verifica o estque mí­nimo!
												$sql37 = "INSERT INTO tbl_estoque_posto_movimento (
																fabrica, 
																posto, 
																data, 
																qtde_saida, 
																peca,
																os)
														select 
																$fabrica,
																$posto,
																NOW(),
																qtde,
																peca,
																os
														from tmp_pedido_latina_estoque_$posto 
														where peca = $xpeca and os = $xos";
												
												$res37 = pg_query($con,$sql37); 
												
												if (strlen(pg_last_error($con)) > 0) {
													
													$msg_erro[] = 'erro $sql37 -2: '.pg_last_error($con);

												}

												if ($primeira_carga == 'f') {
													
													$sql_estoque_posto = "UPDATE tbl_estoque_posto set 
																					qtde = qtde - $xqtde 
																			WHERE tbl_estoque_posto.fabrica = $fabrica 
																			AND   tbl_estoque_posto.posto   = $posto 
																			AND   tbl_estoque_posto.peca    = $xpeca ";

													$res_estoque_posto = pg_query($con, $sql_estoque_posto); 

													if (strlen(pg_last_error($con)) > 0) {

														$msg_erro[] = 'erro $sql_estoque_posto 2: '.pg_last_error($con);

													}

												}
												
												if ($msg_erro) {

													$res24 = pg_query($con, "ROLLBACK TRANSACTION");
													
												} else {

													$res25 = pg_query($con, "COMMIT TRANSACTION");

												}

											} else {
												
												break;
												break;

											}

										}

									}
									
								}
								
								if ($msg_erro) {

									$res24 = pg_query($con, "ROLLBACK TRANSACTION");
									unset($msg_erro);
									
								} else {

									$res25 = pg_query($con, "COMMIT TRANSACTION");

								}

							}

						}

					}

				}
			
			}

			if ($msg_erro) {

				$msg_erro = implode("<br>", $msg_erro);
				Log::log2($vet, $msg_erro);

			}

			if ($log) {

				$log = implode("<br>", $log);
				Log::log2($vet2, $log);

			}

		}

		//GERA PEDIDO PARA OS POSTOS QUE TEM "tbl_posto_fabrica.controla_estoque FALSE e tbl_posto_fabrica.controla_estoque_manual TRUE"
		$sql26 = "SELECT  DISTINCT
						tbl_posto_fabrica.posto
				FROM    tbl_posto_fabrica
				WHERE   tbl_posto_fabrica.posto   <> 6359
				AND     tbl_posto_fabrica.fabrica = $fabrica
				AND     (tbl_posto_fabrica.credenciamento = 'CREDENCIADO' OR tbl_posto_fabrica.credenciamento = 'EM DESCREDENCIAMENTO') 
				AND 	(tbl_posto_fabrica.controla_estoque IS FALSE AND tbl_posto_fabrica.controle_estoque_novo is FALSE AND tbl_posto_fabrica.controle_estoque_manual IS TRUE)
				$cond_posto_estado ";
		
		$res26 = pg_query($con, $sql26); 

		if (strlen(pg_last_error($con)) == 0 and pg_num_rows($res26) > 0) {
			
			unset($msg_erro);
			unset($log);

			$msg_erro = array();
			$log      = array();
			
			for ($i = 0; $i < pg_num_rows($res26); $i++) {

				$posto = pg_result($res26, $i, 0);

				#Pega as pecas das OS
				$sql28 = "SELECT 	tbl_os_item.peca	,
									tbl_os.os   		,
									tbl_os_item.qtde	,
									tbl_os_item.os_item ,
									tbl_os.posto,
									tbl_peca.devolucao_obrigatoria
						INTO    TEMP tmp_pedido_latina_estoque_$posto
						FROM    tbl_os_item
						JOIN    tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado and tbl_servico_realizado.fabrica=$fabrica
						JOIN    tbl_os_produto USING (os_produto)
						JOIN    tbl_os         USING (os)
						JOIN 	tbl_peca ON tbl_peca.peca = tbl_os_item.peca
						WHERE   tbl_os_item.pedido IS NULL
						AND     tbl_os.excluida    IS NOT TRUE
						AND     tbl_os.fabrica     = $fabrica 
						AND 	tbl_os_item.fabrica_i = $fabrica
						AND     tbl_os_item.digitacao_item::date between current_date - 7 and current_date - 1
						AND     tbl_os.posto       = $posto
						AND     (tbl_servico_realizado.troca_de_peca AND tbl_servico_realizado.gera_pedido); ";
				$res28 = pg_query($con,$sql28); 

				if (strlen(pg_last_error($con)) > 0) {

					$msg_erro[] = 'erro $sql28: '.pg_last_error($con);

				}

				$sql_check = "SELECT peca from tmp_pedido_latina_estoque_$posto where posto = $posto limit 1";
				$res_check = pg_query($con, $sql_check); 

				if (pg_num_rows($res_check) > 0) {
					for ($w = 0;  $w < 2; $w++) { 
						
						unset($msg_erro);
						$xdevolucao_obrigatoria = ($w % 2) ? "'t'" : "'f'"; 

						//USADO PARA O PEDIDO
						if ($xdevolucao_obrigatoria == "'t'") {
							$tipo_pedido = ' , 190';
						}

						if ($xdevolucao_obrigatoria == "'f'") {
							$tipo_pedido = ' , 192';
						}

						$sql28x = "
							SELECT  peca,
									os   ,
									SUM(qtde) AS qtde
							FROM    tmp_pedido_latina_estoque_$posto
							WHERE   devolucao_obrigatoria = $xdevolucao_obrigatoria
							GROUP BY 
									peca    ,
									os      ;";
						$res28x = pg_query($con,$sql28x); 
						
						if (pg_num_rows($res28x) > 0) {#Insere pedido
							
							$sql30 = "INSERT INTO tbl_pedido (
										posto     ,
										fabrica   ,
										tabela    ,
										condicao  ,
										tipo_pedido
									) VALUES (
										$posto  ,
										$fabrica,
										(SELECT tabela FROM tbl_tabela WHERE fabrica = $fabrica and ativa order by tabela limit 1)   , 
										'101'    
										$tipo_pedido
									)
									RETURNING pedido;";

							$res30 =  pg_query($con,$sql30); 

							if (strlen(pg_last_error($con)) > 0) {
							
								$msg_erro[] = 'erro $sql30: '.pg_last_error($con);

							} else {

								$pedido = pg_result($res30, 0, 0);
								$log[]  = "Inserido Posto: $posto  Pedido: $pedido";

							}
							#Fim insere pedido
							
							#For de pecas das OS
							for ($z = 0; $z < pg_num_rows($res28x); $z++) {

								$res = pg_query($con,'BEGIN TRANSACTION');

								list($peca, $os, $qtde) = pg_fetch_row($res28x, $z);

								if ($peca_agrupadas) {

									if (in_array($peca, $peca_agrupadas)) {
										continue;
									}

								}
								
								if ($os_agrupadas) {

									if (in_array($os, $os_agrupadas)) {
										continue;
									}

								}

								#Verifica se tem posto com peca abaixo do estoque minimo, se tiver faz o pedido
								$sql31 = "SELECT posto,peca,qtde FROM tbl_estoque_posto 
										WHERE tbl_estoque_posto.fabrica = $fabrica 
										AND tbl_estoque_posto.posto = $posto
										AND tbl_estoque_posto.peca = $peca;";

								$res31 = pg_query($con, $sql31); 
								
								if (strlen(pg_last_error($con)) > 0) {

									$msg_erro[] = 'erro $sql31: '.pg_last_error($con);

								}

								if (pg_num_rows($res31) == 0) {
									
									$sql32 = "INSERT INTO tbl_estoque_posto (
												fabrica, 
												posto, 
												peca,
												qtde, 
												consumo_mensal,
												estoque_minimo)
											VALUES (
												$fabrica,
												$posto,
												$peca,
												-$qtde,
												0,
												0
											)";
									
									$res32 = pg_query($con, $sql32); 
									
									$primeira_carga = 't';

									if (strlen(pg_last_error($con)) > 0) {

										$msg_erro[] = 'erro $sql32: '.pg_last_error($con);

									}

								} else {

									$primeira_carga = 'f';

								}
														
								#Verifica se tem posto com peca abaixo do estoque minimo, se tiver faz o pedido
								$sql33 = "SELECT tbl_estoque_posto.posto AS id_posto,
												tbl_estoque_posto.peca AS id_peca,
												tbl_estoque_posto.qtde AS quantidade,
												tbl_estoque_posto.estoque_minimo,
												tbl_estoque_posto.estoque_minimo - tbl_estoque_posto.qtde AS qtde_pedido
										FROM tbl_estoque_posto 
										WHERE tbl_estoque_posto.fabrica = $fabrica 
										AND tbl_estoque_posto.posto = $posto 
										AND tbl_estoque_posto.peca = $peca
										AND tbl_estoque_posto.estoque_minimo >= tbl_estoque_posto.qtde;";
								
								$res33 = pg_query($con, $sql33); 
								
								if (strlen(pg_last_error($con)) > 0) {
									$msg_erro[] = 'erro $sql33: '.pg_last_error($con);
								}


								if (pg_num_rows($res33) > 0) {

									if ($os_agrupadas) {
										$os_processadas = implode(',', $os_agrupadas);
										$cond_os_agrupadas = " and os not in ($os_processadas) ";
									}

									$sql_agrupa_pecas = "	SELECT  peca,
																	SUM(qtde) AS qtde
															FROM    tmp_pedido_latina_estoque_$posto
															WHERE devolucao_obrigatoria = $xdevolucao_obrigatoria
															and peca = $peca

															GROUP BY 
																	peca; ";

									$res_agrupa_pecas = pg_query($con, $sql_agrupa_pecas); 

									if (pg_num_rows($res_agrupa_pecas) > 0) {
										
										$res_begin_1 = pg_query($con,"BEGIN TRANSACTION"); 
										$zpeca = pg_result($res_agrupa_pecas,0,0);
										$zqtde = pg_result($res_agrupa_pecas,0,1);
										
										$peca_agrupadas[] = $zpeca;

										if ($qtde_processadas) {
											$zqtde = $zqtde - $qtde_processadas;
										}

										//O SERVICO REALIZADO SERÁ SEMPRE ALTERADO NESTE TIPO DE ESTOQUE
										$sql_sr = "SELECT fn_estoque_servico_realizado(os_item,peca,$fabrica)
													FROM tmp_pedido_latina_estoque_$posto where peca = $zpeca $cond_os_agrupadas";
										
										$res_sr = pg_query($con,$sql_sr); 
										
										if (strlen(pg_last_error($con)) > 0) {

											$msg_erro[] = 'erro $sql_sr: '.pg_last_error($con);
										
										}

										#Verifica se tem posto com peca abaixo do estoque minimo, se tiver faz o pedido
										$sql34 = "SELECT posto,peca,qtde 
												FROM tbl_estoque_posto 
												WHERE tbl_estoque_posto.fabrica = $fabrica 
												AND tbl_estoque_posto.posto = $posto
												AND tbl_estoque_posto.peca = $zpeca;";
										
										$res34 = pg_query($con,$sql34); 
										
										if (strlen(pg_last_error($con)) > 0) {
											$msg_erro[] = 'erro $sql34: '.pg_last_error($con);
										}

										$sql35 = "SELECT SUM(qtde)
													FROM tbl_os_item
													JOIN tbl_os_produto using (os_produto)
													JOIN tbl_os using (os)
													JOIN tbl_servico_realizado on tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
													WHERE tbl_os_item.peca = $zpeca 
													AND tbl_os_item.digitacao_item::date >= current_date - '30 days'::interval
													AND tbl_os.fabrica = $fabrica
													AND tbl_os.posto = $posto
													AND tbl_os.excluida IS NOT TRUE
													AND tbl_servico_realizado.fabrica=$fabrica
													AND tbl_servico_realizado.peca_estoque is true ";

										$res35 = pg_query($con, $sql35);

										if (pg_result($res35,0,0) == 'null' or pg_result($res35,0,0) == '') {
											$soma_qtde_saida = 0;
										} else {
											$soma_qtde_saida = pg_result($res35, 0, 0);
										}

																				
										#Movimenta o estoque
										$sql37 = "INSERT INTO tbl_estoque_posto_movimento (
														fabrica, 
														posto, 
														data, 
														qtde_saida, 
														peca,
														os,
														pedido)
												select 
														$fabrica,
														$posto,
														NOW(),
														qtde,
														peca,
														os,
														$pedido
												from tmp_pedido_latina_estoque_$posto 
												where peca = $zpeca $cond_os_agrupadas";
										
										$res37 = pg_query($con,$sql37); 
										
										if (strlen(pg_last_error($con)) > 0) {
											
											$msg_erro[] = 'erro $sql37: '.pg_last_error($con);

										}
										
										if ($primeira_carga == 'f') {
											
											$sql_estoque_posto = "
													UPDATE tbl_estoque_posto set 
															qtde = qtde - $zqtde
													WHERE tbl_estoque_posto.fabrica = $fabrica 
													AND   tbl_estoque_posto.posto   = $posto 
													AND   tbl_estoque_posto.peca    = $zpeca ";

										} else {

											$sql_estoque_posto = "
												UPDATE tbl_estoque_posto set qtde = 0 
												WHERE tbl_estoque_posto.fabrica = $fabrica 
												AND   tbl_estoque_posto.posto   = $posto 
												AND   tbl_estoque_posto.peca    = $zpeca;

												UPDATE tbl_estoque_posto set 

														qtde = qtde - $zqtde

												WHERE tbl_estoque_posto.fabrica = $fabrica 
												AND   tbl_estoque_posto.posto   = $posto 
												AND   tbl_estoque_posto.peca    = $zpeca; ";

										}

										$res_estoque_posto = pg_query($con, $sql_estoque_posto); 

										if (strlen(pg_last_error($con)) > 0) {

											$msg_erro[] = 'erro $sql_estoque_Posto: '.pg_last_error($con);

										}

										list($id_posto, $id_peca, $quantidade, $estoque_minimo, $qtde_pedido) = pg_fetch_row($res33,$z);
										

										$sql38 = "INSERT INTO tbl_pedido_item (
													pedido,
													peca  ,
													qtde
												) VALUES (
													$pedido,
													$zpeca  ,
													$qtde_pedido + $zqtde
												)";
										
										$res38 = pg_query($con, $sql38);

										if (strlen(pg_last_error($con)) > 0) {

											$msg_erro[] = 'erro $sql38: '.pg_last_error($con);

										} else {

											$log[] = " $pedido Inserido Item $zpeca qtde $qtde_pedido";

										}

									

										$sql39 = "SELECT pedido FROM tbl_pedido WHERE pedido = $pedido";
										$res39 = pg_query($con,$sql39); 

										if (pg_num_rows($res39) > 0) {
											
											$sql40 = "SELECT fn_pedido_finaliza ($pedido,$fabrica)";
											$res40 = pg_query($con,$sql40); 

											if (strlen(pg_last_error($con)) > 0) {

												$msg_erro[] = 'erro $sql40: '.pg_last_error($con);
												$log[] = "Nao finalizou o pedido $pedido.";

											}

										} else {

											$msg_erro[] = "Posto: $posto - Peca: $zpeca - PEDIDO SEM ITENS - PECAS EM ESTOQUE";

										}
										
										#Fim do pedido
										if ($msg_erro) {

											$res24 = pg_query($con, "ROLLBACK TRANSACTION"); 
											
										} else {

											unset($os_agrupadas);
											$qtde_processadas  = 0;
											$cond_os_agrupadas = "";
											$res25 = pg_query($con, "COMMIT TRANSACTION");

										}

									}

								} else {
									
									//se o saldo do estoque for maior que o estoque minimo, vai buscar na temp agrupando por OS...
									//e vai baixar o estoque ate que o estoque minimo seja >= saldo
									$sql_agrupa_os = "SELECT  peca,
														os,
														SUM(qtde) AS qtde
														FROM    tmp_pedido_latina_estoque_$posto
														WHERE  devolucao_obrigatoria = $xdevolucao_obrigatoria 
														and peca = $peca
														GROUP BY
														peca, os   ;";

									$res_agrupa_os = pg_query($con, $sql_agrupa_os); 

									if (pg_num_rows($res_agrupa_os) > 0) {

										if ($os_agrupadas) {
											unset($os_agrupadas);
										}

										$qtde_processadas = 0;

										for ($q = 0; $q < pg_num_rows($res_agrupa_os); $q++) {
											
											list($xpeca, $xos, $xqtde) = pg_fetch_row($res_agrupa_os, $q);

											//SE O ESTOQUE MINIMO FOR MENOR QUE O SALDO, NAO GERA PEDIDO,
											//FAZ MOVIMENTACAO E TROCA SERVICO REALIZADO

											#Verifica tem peca com estoque minimo >= saldo, se nao tiver, 
											#Vai baixando e nao volta para gerar pedido...
											$sql_ver_em = "SELECT 	tbl_estoque_posto.posto AS id_posto,
															tbl_estoque_posto.peca AS id_peca,
															tbl_estoque_posto.qtde AS quantidade,
															tbl_estoque_posto.estoque_minimo,
															tbl_estoque_posto.estoque_minimo - tbl_estoque_posto.qtde AS qtde_pedido
													FROM tbl_estoque_posto 
													WHERE tbl_estoque_posto.fabrica = $fabrica 
													AND tbl_estoque_posto.posto = $posto 
													AND tbl_estoque_posto.peca = $xpeca
													AND tbl_estoque_posto.estoque_minimo >= tbl_estoque_posto.qtde;";
											
											$res_ver_em = pg_query($con,$sql_ver_em); 

											if (strlen(pg_last_error($con)) > 0) {
												$msg_erro[] = 'erro $sql_ver_em: '.pg_last_error($con);
											}

											if (pg_num_rows($res_ver_em) == 0) {
												
												$res_another_begin = pg_query($con, 'BEGIN TRANSACTION'); 
												
												$os_agrupadas[] = $xos;
												$qtde_processadas = $qtde_processadas + $xqtde;
												//echo "ESTOQUE MINIMO < SALDO - não gera pedido - $peca \n\n";

												$sql_sr = "SELECT fn_estoque_servico_realizado(os_item,peca,$fabrica)
															FROM tmp_pedido_latina_estoque_$posto where peca = $xpeca and os=$xos";
												
												$res_sr = pg_query($con,$sql_sr); 
												
												if (strlen(pg_last_error($con)) > 0) {

													$msg_erro[] = 'erro $sql_sr: '.pg_last_error($con);
												
												}

												#Verifica se tem posto com peça abaixo do estoque mí­nimo, se tiver faz o pedido
												$sql34 = "SELECT posto,peca,qtde 
														FROM tbl_estoque_posto 
														WHERE tbl_estoque_posto.fabrica = $fabrica 
														AND tbl_estoque_posto.posto = $posto
														AND tbl_estoque_posto.peca = $xpeca;";
												
												$res34 = pg_query($con,$sql34); 
												
												if (strlen(pg_last_error($con)) > 0) {
													$msg_erro[] = 'erro $sql34 -2: '.pg_last_error($con);
												}

												$sql35 = "SELECT SUM(qtde)
															FROM tbl_os_item
															JOIN tbl_os_produto using (os_produto)
															JOIN tbl_os using (os)
															JOIN tbl_servico_realizado on tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
															WHERE tbl_os_item.peca = $xpeca 
															AND tbl_os_item.digitacao_item::date >= current_date - '30 days'::interval
															AND tbl_os.fabrica = $fabrica
															AND tbl_os.posto = $posto
															AND tbl_os.excluida IS NOT TRUE
															AND tbl_servico_realizado.fabrica = $fabrica
															AND tbl_servico_realizado.peca_estoque is true ";

												$res35 = pg_query($con,$sql35); 

												if (pg_result($res35, 0, 0) == 'null' or pg_result($res35,0,0) == '') {
													$xsoma_qtde_saida = 0;
												} else {
													$xsoma_qtde_saida = pg_result($res35, 0, 0);
												}

																							
												#Baixa o estoque e verifica o estque mí­nimo!
												$sql37 = "INSERT INTO tbl_estoque_posto_movimento (
																fabrica, 
																posto, 
																data, 
																qtde_saida, 
																peca,
																os)
														select 
																$fabrica,
																$posto,
																NOW(),
																qtde,
																peca,
																os
														from tmp_pedido_latina_estoque_$posto 
														where peca = $xpeca and os = $xos";
												
												$res37 = pg_query($con,$sql37); 
												
												if (strlen(pg_last_error($con)) > 0) {
													
													$msg_erro[] = 'erro $sql37 -2: '.pg_last_error($con);

												}

												if ($primeira_carga == 'f') {
													
													$sql_estoque_posto = "UPDATE tbl_estoque_posto set 
																					qtde = qtde - $xqtde 
																			WHERE tbl_estoque_posto.fabrica = $fabrica 
																			AND   tbl_estoque_posto.posto   = $posto 
																			AND   tbl_estoque_posto.peca    = $xpeca ";

													$res_estoque_posto = pg_query($con, $sql_estoque_posto); 

													if (strlen(pg_last_error($con)) > 0) {

														$msg_erro[] = 'erro $sql_estoque_posto 2: '.pg_last_error($con);

													}

												}
												
												if ($msg_erro) {

													$res24 = pg_query($con, "ROLLBACK TRANSACTION");
													
												} else {

													$res25 = pg_query($con, "COMMIT TRANSACTION");

												}

											} else {
												
												break;
												break;

											}

										}

									}
									
								}
								
								if ($msg_erro) {

									$res24 = pg_query($con, "ROLLBACK TRANSACTION");
									unset($msg_erro);
									
								} else {

									$res25 = pg_query($con, "COMMIT TRANSACTION");

								}

							}

						}

					}

				}
			
			}

			if ($msg_erro) {

				$msg_erro = implode("<br>", $msg_erro);
				Log::log2($vet, $msg_erro);

			}

			if ($log) {

				$log = implode("<br>", $log);
				Log::log2($vet2, $log);

			}

		}
	
	}

	if ($msg_erro) {
		
		$str_consumidor_revenda = ($consumidor_revenda == 'C') ? "Consumidor" : "Revenda";
		$str_tipo_os = ($tipo_os == 'funcional') ? "Funcional" : "Plasticas";
		
		Log::envia_email($vet, "Log - Geração de Pedido de OS $str_consumidor_revenda $str_tipo_os", $msg_erro);

	}

	$phpCron->termino();
	
} catch (Exception $e) {

	echo $e->getMessage();

}?>
