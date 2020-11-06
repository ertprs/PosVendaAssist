<?php
	
	define('APP','Gera Pedido  - Britania'); // Nome da rotina, para ser enviado por e-mail
	define('ENV','producao'); // Alterar para produção ou algo assim

	try {
		include dirname(__FILE__) . '/../../dbconfig.php';
		include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
		require dirname(__FILE__) . '/../funcoes.php';

		$login_fabrica   = 3;
		$data 		 = date('d-m-Y');
		
		$phpCron = new PHPCron($login_fabrica, __FILE__); 
		$phpCron->inicio();

		$vet['fabrica'] = 'britania';
		$vet['tipo']    = 'pedido';
		$vet['dest'][0]    = 'helpdesk@telecontrol.com.br';
		#$vet['dest'][1]    = 'sistemas@britania.com.br';
		#$vet['dest'][2]    = 'airton.garcia@britania.com.br';
		$vet['log']     = 1;

		$dir = "/tmp/britania/pedidos";
		$file = 'pedidos_erro.txt';

		$sql = "SELECT tbl_os.posto,
					tbl_produto.linha,
					tbl_os_item.peca,
					tbl_os_item.os_item,
					tbl_peca.referencia AS peca_referencia,
					tbl_os.sua_os ,
					tbl_os.os,
					tbl_servico_realizado.troca_produto,
					tbl_os_item.qtde
						INTO TEMP tmp_pedido_britania
				FROM tbl_os_item
					JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado
				AND tbl_servico_realizado.fabrica = $login_fabrica
					JOIN tbl_os_produto USING (os_produto)
					JOIN tbl_os USING (os)
					JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
					LEFT JOIN tbl_os_troca ON tbl_os.os = tbl_os_troca.os
					JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = $login_fabrica
					JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				WHERE tbl_os_item.pedido IS NULL
					AND tbl_os.excluida IS NOT TRUE
					AND tbl_os.cancelada IS NOT TRUE
					AND tbl_os.finalizada IS NULL
					AND tbl_os.fabrica = $login_fabrica
					AND tbl_os.posto <> 6359
					AND tbl_posto_fabrica.tipo_posto <> 346
					AND tbl_os_item.fabrica_i = $login_fabrica
					AND tbl_os_troca.os IS NULL
					AND (tbl_servico_realizado.troca_de_peca AND tbl_servico_realizado.gera_pedido);
				SELECT distinct os, posto, linha FROM tmp_pedido_britania;";
		$result   = pg_query($con,$sql);

		if (pg_last_error()) {
			$erro_hd .= pg_last_error()."<br />";
		} else {
			$total_posto = pg_num_rows($result);

			for($i = 0; $i < $total_posto; $i++) {

				$os         = pg_fetch_result($result,$i,'os');
				$posto      = pg_fetch_result($result,$i,'posto');
				$linha      = pg_fetch_result($result,$i,'linha');

				##INTERVENCAO OS

				$sql = "SELECT interv_os.os
						FROM (
							SELECT
							ultima_status.os,
							(
								SELECT status_os 
								FROM tbl_os_status 
								WHERE tbl_os_status.os = ultima_status.os 
								AND tbl_os_status.fabrica_status= $login_fabrica 
								AND status_os IN (62,175,174,64)
								ORDER BY os_status DESC LIMIT 1
							) AS ultimo_os_status

							FROM (
								SELECT DISTINCT os 
								FROM tbl_os_status 
								WHERE tbl_os_status.fabrica_status= $login_fabrica
								AND status_os IN (62,175,174,64)
								AND tbl_os_status.os = $os
							) ultima_status
						) interv_os 
						WHERE interv_os.ultimo_os_status IN (62, 175, 174)";

				$res = pg_query($con,$sql);

				if(pg_num_rows($res) > 0){
					continue;
				}


				##INTERVENCAO CARTEIRA

				$sql = "SELECT interv_os.os
						FROM (
							SELECT
							ultima_status.os,
							(
								SELECT status_os 
								FROM tbl_os_status 
								WHERE tbl_os_status.os = ultima_status.os 
								AND tbl_os_status.fabrica_status= $login_fabrica 
								AND status_os IN (116,117)
								ORDER BY os_status DESC LIMIT 1
							) AS ultimo_os_status

							FROM (
								SELECT DISTINCT os 
								FROM tbl_os_status 
								WHERE tbl_os_status.fabrica_status= $login_fabrica
								AND status_os IN (116,117)
								AND tbl_os_status.os = $os
							) ultima_status
						) interv_os
						WHERE interv_os.ultimo_os_status IN (116);";

				$res = pg_query($con,$sql);

				if(pg_num_rows($res) > 0){
					continue;
				}

				##INTERVENCAO SAP

				$sql = "SELECT interv_os.os
						FROM (
							SELECT
							ultima_status.os,
							(
								SELECT status_os 
								FROM tbl_os_status 
								WHERE tbl_os_status.os = ultima_status.os 
								AND tbl_os_status.fabrica_status= $login_fabrica 
								AND status_os IN (72,73)
								ORDER BY os_status DESC LIMIT 1
							) AS ultimo_os_status

							FROM (
									SELECT DISTINCT os 
									FROM tbl_os_status 
									WHERE tbl_os_status.fabrica_status= $login_fabrica
									AND status_os IN (72,73)
									AND tbl_os_status.os = $os
							) ultima_status
						) interv_os
						WHERE interv_os.ultimo_os_status IN (72);";

				$res = pg_query($con,$sql);

				if(pg_num_rows($res) > 0){
					continue;
				}

				##REPARO

				$sql = "SELECT interv_os.os
						FROM (
							SELECT
							ultima_status.os,
							(
								SELECT status_os 
								FROM tbl_os_status 
								WHERE tbl_os_status.os = ultima_status.os 
								AND tbl_os_status.fabrica_status= $login_fabrica 
								AND status_os IN (65)
								ORDER BY os_status DESC LIMIT 1
							) AS ultimo_os_status

							FROM (
								SELECT DISTINCT os 
								FROM tbl_os_status 
								WHERE tbl_os_status.fabrica_status= $login_fabrica
								AND status_os IN (65)
								AND tbl_os_status.os = $os
							) ultima_status
						) interv_os
						WHERE interv_os.ultimo_os_status IN (65);";

				$res = pg_query($con,$sql);

				if(pg_num_rows($res) > 0){
					continue;
				}

				unset($msg_erro);
				unset($total);

				$sql_posto  = "SELECT tbl_posto.nome FROM tbl_posto JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica WHERE tbl_posto.posto = $posto";
				$res_posto  = pg_query($con, $sql_posto);
				$nome_posto = pg_fetch_result($res_posto, 0, "nome");

				$sql = "SELECT  
							peca,
							peca_referencia,
							sua_os,
							os_item, 
							troca_produto,
							SUM(qtde) AS qtde
						FROM tmp_pedido_britania
						WHERE os = $os
						GROUP BY 
							peca           ,
							peca_referencia,
							sua_os         ,
							os_item 		,
							troca_produto";

				$result2  = pg_query($con,$sql);
				if (pg_last_error()) {
					$erro_hd .= pg_last_error()."<br />";
				} else {
					$total = pg_num_rows($result2);
				}

				if($total > 0) {
					$res = pg_query($con,"BEGIN TRANSACTION");

					$condicao = "7";

					$sql = "INSERT INTO tbl_pedido 
								(
									pedido    ,
									posto     ,
									fabrica   ,
									linha     ,
									condicao  ,
									tipo_pedido
								) 
							VALUES 
								(
									DEFAULT   ,
									$posto    ,
									$login_fabrica  ,
									$linha    ,
									$condicao ,
									'3'
								)
							RETURNING pedido;";

					$resultX  = pg_query($con,$sql);
					if (pg_last_error()) {
						$erro_hd .= pg_last_error()."<br />";
					} else {
						$pedido = pg_fetch_result($resultX,0,'pedido');
					}

					if (!empty($pedido)) {
						for($j = 0; $j < $total; $j++) {
							$peca            = pg_fetch_result($result2,$j,'peca');
							$peca_referencia = pg_fetch_result($result2,$j,'peca_referencia');
							$sua_os          = pg_fetch_result($result2,$j,'sua_os');
							$os_item         = pg_fetch_result($result2,$j,'os_item');
							$troca_produto   = pg_fetch_result($result2,$j,'troca_produto');
							$qtde            = pg_fetch_result($result2,$j,'qtde');

							$sql = "INSERT INTO tbl_pedido_item 
										(
											pedido,
											peca  ,
											qtde  ,
											qtde_faturada,
											qtde_cancelada,
											troca_produto
										)
									VALUES 
										(
											$pedido,
											$peca  ,
											$qtde  ,
											0      ,
											0      ,
											'$troca_produto'
										) 
									RETURNING pedido_item";

							$resultX     = pg_query($con,$sql);
							if (pg_last_error()) {
								$erro_hd .= pg_last_error()."<br />";
							}

							$pedido_item = pg_fetch_result($resultX,0,'pedido_item');

							$sql      = "SELECT fn_atualiza_os_item_pedido_item($os_item, $pedido, $pedido_item,$login_fabrica)";
							$resultX  = pg_query($con,$sql);
							if (pg_last_error()) {
								$msg_erro .= pg_last_error()."<br />";
							}

							$log_os .= "Posto: $nome_posto - Os: $sua_os - Peça: $peca_referencia \n <br />";
						}

						$sql      = "SELECT fn_pedido_finaliza ($pedido,$login_fabrica)";
						$resultX  = pg_query($con,$sql);
						if (pg_last_error()) {
							$msg_erro .= pg_last_error()."<br />";
						}
					}

					if (!empty($msg_erro)) {
						pg_query($con,"ROLLBACK TRANSACTION");
						$fp = fopen( $dir . '/' . $file, "a" );
						fputs ($fp,$log_os);
						fclose ($fp);
					} else {
						pg_query($con,"COMMIT TRANSACTION");
					}
				}
			}
		}

		if (!empty($msg_erro)):
			$msg_erro = str_replace("ERROR: ", "", $msg_erro);
			$arquivo_msg = file_get_contents($dir.'/'.$file);
			$msg_erro .= $arquivo_msg;
			Log::envia_email($vet,APP, $msg_erro, true, "erro");
		endif;

		if (!empty($erro_hd)):
			$erro_hd = str_replace("ERROR: ", "", $erro_hd);
			unset($vet["dest"]);
			$vet["dest"] = "helpdesk@telecontrol.com.br";
			$erro = "Erro na Rotina Gera Pedido <br /> $erro_hd";
			Log::envia_email($vet,APP, $erro, true, "erro");
		endif;
		
		$phpCron->termino();
	
	} catch (Exception $e) {
	
		if (!empty($msg_erro)) {
			$msg .= $msg_erro;
		}
		$msg = 'Script: '.__FILE__.'<br />Erro na linha ' . $e->getLine() . ':<br />' . $e->getMessage();
		Log::envia_email($vet,APP, $msg, true );

	}

