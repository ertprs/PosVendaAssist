<?php
	define('APP','Troca Pedido - Britania'); // Nome da rotina, para ser enviado por e-mail
	define('ENV','producao'); // Alterar para produção ou algo assim

	try {
		include dirname(__FILE__) . '/../../dbconfig.php';
		include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
		require dirname(__FILE__) . '/../funcoes.php';

		$fabrica     = 3;
		$data 		 = date('d-m-Y');
		
		$phpCron = new PHPCron($fabrica, __FILE__); 
		$phpCron->inicio();

		$vet['fabrica'] = 'britania';
		$vet['tipo']    = 'pedido_troca';
		$vet['dest'][0]    = 'helpdesk@telecontrol.com.br';
		$vet['dest'][1]    = 'sistemas@britania.com.br';
		$vet['dest'][2]    = 'airton.garcia@britania.com.br';
		$vet['log']     = 1;
		
		$dir = "/tmp/britania/pedidos";
		$file = 'pedidos_troca_erro.txt';

		$sql = "SELECT os,status_os ,data
					  INTO TEMP tmp_britania_os_status
					FROM tbl_os_status 
					WHERE fabrica_status=$fabrica AND status_os IN (62,64,65,72,73,174,175)
					and data > current_timestamp - interval '6 months';
				
				CREATE INDEX tmp_britania_os_status_os_os ON tmp_britania_os_status(os);
				
				CREATE INDEX tmp_britania_os_status_os_status_os ON tmp_britania_os_status(status_os);
				
				CREATE INDEX tmp_britania_os_status_os_os_status_os ON tmp_britania_os_status(os,status_os);

				";
		$result = pg_query($con,$sql);
		if (pg_last_error()) {
			$erro = "Erro ao executar a rotina de gerar pedido da os's de troca <br />";
		} else {
			$sql = "SELECT tbl_posto_fabrica.posto,
						   tbl_linha.linha
					INTO    TEMP  tmp_britania_posto_linha
					FROM    tbl_posto_fabrica
					JOIN    tbl_linha ON tbl_linha.fabrica = tbl_posto_fabrica.fabrica AND tbl_linha.fabrica = $fabrica
					WHERE   tbl_posto_fabrica.posto   <> 6359
					AND     tbl_posto_fabrica.fabrica = $fabrica
					AND     tbl_posto_fabrica.tipo_posto <> 346
					AND     (tbl_posto_fabrica.credenciamento = 'CREDENCIADO' OR tbl_posto_fabrica.credenciamento = 'EM DESCREDENCIAMENTO' )
					AND     tbl_posto_fabrica.posto IN (SELECT posto FROM tbl_os JOIN tbl_os_troca ON tbl_os_troca.os = tbl_os.os AND tbl_os_troca.fabric = $fabrica
					WHERE   tbl_os.fabrica = $fabrica AND tbl_os_troca.gerar_pedido  IS TRUE  AND tbl_os_troca.pedido IS NULL
					AND     tbl_os_troca.ressarcimento IS FALSE) ;

					SELECT posto,linha FROM tmp_britania_posto_linha ORDER BY 1,2;";
		
			$result = pg_query($con,$sql);
			if (pg_last_error()) {
				$erro_hd .= pg_last_error()."<br />";
			} else {
				$total_posto = pg_numrows($result);

				for($i = 0; $i < $total_posto; $i++){
					$posto  = pg_result($result,$i,'posto');
					$linha  =  pg_result($result,$i,'linha');

					$sql_posto  = "SELECT tbl_posto.nome FROM tbl_posto JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $fabrica WHERE tbl_posto.posto = $posto";
					$res_posto  = pg_query($con, $sql_posto);
					$nome_posto = pg_fetch_result($res_posto, 0, "nome");

					$sql = "SELECT  tbl_os_troca.peca,
									tbl_os.os
								FROM    tbl_os
								JOIN    tbl_os_troca    ON tbl_os_troca.os = tbl_os.os
								JOIN    tbl_produto     ON tbl_os.produto  = tbl_produto.produto AND tbl_produto.fabrica_i = $fabrica
								WHERE   tbl_os_troca.gerar_pedido  IS TRUE
								AND     tbl_os_troca.pedido        IS NULL
								AND     tbl_os_troca.ressarcimento IS FALSE
								AND     tbl_os.cancelada    IS NOT TRUE
								AND     tbl_os.finalizada   IS NULL
								AND     tbl_os.fabrica    = $fabrica
								AND     tbl_os.posto      = $posto
								AND     tbl_produto.linha = $linha
								AND     (
									(
										(
										SELECT status_os FROM tmp_britania_os_status 
										WHERE tbl_os.os = tmp_britania_os_status.os AND status_os IN (62,64,65,72,73,174,175)
										ORDER BY data DESC LIMIT 1
										) NOT IN (62,65,72,174,175) 
									)OR (
										SELECT status_os FROM tmp_britania_os_status 
										WHERE tbl_os.os = tmp_britania_os_status.os AND status_os IN (62,64,65,72,73,174,175)
										ORDER BY data DESC LIMIT 1
									) IS NULL
								)";
					$result2 = pg_query($con,$sql);
					if (pg_last_error()) {
						$erro_hd .= pg_last_error()."<br />";
					} else {
						$total = pg_num_rows($result2);
					}

					if($total > 0){

						$res = pg_query($con,"BEGIN TRANSACTION");

						for($j = 0; $j < $total; $j++){
							$peca				= pg_result($result2,$j,'peca');
							$os					= pg_result($result2,$j,'os');

							$sql = "INSERT INTO tbl_pedido (
															posto     ,
															fabrica   ,
															linha     ,
															condicao  ,
															tipo_pedido,
															troca
														) VALUES (
															$posto    ,
															$fabrica  ,
															$linha    ,
															'7'       ,
															'3'       ,
															TRUE
														) RETURNING pedido;";
							$resultX = pg_query($con,$sql);
							if (pg_last_error()) {
								$erro_hd .= pg_last_error()."<br />";
							} else {
								$pedido = pg_fetch_result($resultX, 0, 'pedido');
							}

							if(!empty($pedido)) {
								$sql = "INSERT INTO tbl_pedido_item (
																		pedido,
																		peca  ,
																		qtde  ,
																		qtde_faturada,
																		qtde_cancelada,
																		troca_produto
																	) VALUES (
																		$pedido,
																		$peca  ,
																		1      ,
																		0      ,
																		0      ,
																		't') RETURNING pedido_item";
								$resultX = pg_query($con,$sql);
								if (pg_last_error()) {
									$erro_hd .= pg_last_error()."<br />";
								} else {
									$pedido_item = pg_fetch_result($resultX, 0, 'pedido_item');
								}

								if (!empty($pedido_item)) {
									$sql = "UPDATE tbl_os_troca SET pedido = $pedido, pedido_item = $pedido_item WHERE os = $os";
									$resultX = pg_query($con,$sql);
									if (pg_last_error()) {
										$erro_hd .= pg_last_error()."<br />";
									} else {
										$sql = "UPDATE tbl_os_item SET pedido = $pedido,pedido_item = $pedido_item WHERE peca = $peca AND os_produto IN (select os_produto from tbl_os_produto where os = $os)";
										$resultX = pg_query($con,$sql);
										if (pg_last_error()) {
											$erro_hd .= pg_last_error()."<br />";
										} else {
											$sql = "SELECT fn_pedido_finaliza ($pedido,$fabrica)";
											$resultX = pg_query($con,$sql);
											if (pg_last_error()) {
												$msg_erro .= pg_last_error()."<br />";
											}
										}
									}
								}
							}
						}
						
						if ( !empty($msg_erro) ) {
							pg_query($con,"ROLLBACK TRANSACTION");

							$sqlY = "SELECT  tbl_os_troca.peca, tbl_os.os
										FROM    tbl_os
										JOIN    tbl_os_troca          ON tbl_os_troca.os = tbl_os.os
										JOIN    tbl_produto           ON tbl_os.produto  = tbl_produto.produto
										WHERE   tbl_os_troca.gerar_pedido  IS TRUE
										AND     tbl_os_troca.pedido        IS NULL
										AND     tbl_os_troca.ressarcimento IS FALSE
										AND     tbl_os.fabrica    = $fabrica
										AND     tbl_os.posto      = $posto
										AND     tbl_produto.linha = $linha
										AND     (
											(
												(
												SELECT status_os FROM tmp_britania_os_status 
												WHERE tbl_os.os = tmp_britania_os_status.os AND status_os IN (62,64,65,72,73)
												ORDER BY data DESC LIMIT 1
												) NOT IN (62,65,72) 
											)OR (
												SELECT status_os FROM tmp_britania_os_status 
												WHERE tbl_os.os = tmp_britania_os_status.os AND status_os IN (62,64,65,72,73)
												ORDER BY data DESC LIMIT 1
											) IS NULL
										)";
								$resultY = pg_query($con,$sqlY);

								$total_falha = pg_numrows($resultY);
						
							$fp = fopen( $dir . '/' . $file, "a" );

								for($x = 0; $x < $total_falha; $i++){
									$os		= pg_result($resultY,$x,'os');
									$posto	= pg_result($resultY,$x,'posto');
									$linha	= pg_result($resultY,$x,'linha');

									$conteudo .= "Posto: $posto - OS: $os - Linha: $linha \n <br />";

								}

							fputs ($fp,$conteudo);
							fclose ($fp);
						} else {
							pg_query($con,"COMMIT TRANSACTION");
						}
					}
				}
			}
		}

		if ( !empty($msg_erro) ) {
			$msg_erro = str_replace("ERROR: ", "", $msg_erro);
			$arquivo_msg = file_get_contents($dir.'/'.$file);
			$msg_erro .= $arquivo_msg;
			Log::envia_email($vet,APP, $msg_erro, true, "erro");
		}

		if (!empty($erro_hd)):
			$erro_hd = str_replace("ERROR: ", "", $erro_hd);
			unset($vet["dest"]);
			$vet["dest"] = "helpdesk@telecontrol.com.br";
			$erro = "Erro na Rotina Gera Pedido de OSs de Troca<br /> $erro_hd";
			Log::envia_email($vet,APP, $erro, true, "erro");
		endif;
		
		$phpCron->termino();
				
	}
	catch (Exception $e) {
	
		$msg = 'Script: '.__FILE__.'<br />Erro na linha ' . $e->getLine() . ':<br />' . $e->getMessage();
		Log::envia_email($vet,APP, $msg );

	}
