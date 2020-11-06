<?php


	if(strlen($xservico) > 0){

		$sql = "SELECT gera_pedido, troca_de_peca, ressarcimento 
			FROM tbl_servico_realizado 
			WHERE fabrica = $login_fabrica AND servico_realizado = '$xservico'";
		$res = pg_query($con, $sql);

		if(pg_num_rows($res) > 0){

			$gera_pedido 	= pg_fetch_result($res, 0, 'gera_pedido');
			$troca_de_peca 	= pg_fetch_result($res, 0, 'troca_de_peca');
			$ressarcimento 	= pg_fetch_result($res, 0, 'ressarcimento');

			if($ressarcimento == "t"){

				$sql = "SELECT tbl_estoque_posto.qtde , tbl_peca.referencia
					FROM tbl_estoque_posto 
					JOIN tbl_peca ON tbl_estoque_posto.peca = tbl_peca.peca AND tbl_peca.fabrica = $login_fabrica
					WHERE tbl_estoque_posto.peca = $xpeca 
				   	AND tbl_estoque_posto.posto = $login_posto 
				   	AND tbl_estoque_posto.fabrica = $login_fabrica
				   	AND tbl_estoque_posto.tipo = 'garantia'";
				$res = pg_query($con, $sql);

				/* Se tiver Qtde no estoque  */
				if(pg_num_rows($res) > 0){

					/* Qtde no estoque */
					$qtde_estoque = pg_fetch_result($res, 0, 'qtde');
					$referencia = pg_fetch_result($res, 0, 'referencia');

					$sql = "SELECT peca 
							FROM tbl_estoque_posto_movimento
							WHERE fabrica = $login_fabrica
							AND posto = $login_posto
							AND os = $os
							AND peca = $xpeca
							AND qtde_saida = $xqtde
							AND tipo = 'garantia'";
					$resS = pg_query($con, $sql);

					if(pg_num_rows($resS) == 0){
						if($qtde_estoque >= $xqtde){
							$sql_posto_movimento = "INSERT INTO tbl_estoque_posto_movimento 
																(	fabrica, 
																	posto, 
																	os, 
																	peca, 
																	qtde_saida, 
																	os_item, 
																	tipo,
																	obs,
																	data
																) VALUES (
																	$login_fabrica, 
																	$login_posto, 
																	$os, 
																	$xpeca, 
																	$xqtde, 
																	$xos_item, 
																	'garantia',
																	'Sa&iacute;da autom&aacute;tica, pe&ccedil;a solicitada em Ordem de Servi&ccedil;o', current_date)";
					    	$res_posto_movimento = pg_query($con, $sql_posto_movimento);

					    	$sql_qtde_update = "UPDATE tbl_estoque_posto  
				                                           SET qtde = qtde - $xqtde 
				                                           WHERE fabrica = $login_fabrica 
				                                           AND posto = $login_posto 
				                                           AND peca  = $xpeca
				                                           AND tipo = 'garantia'";
				            $res_servico_update = pg_query($con, $sql_qtde_update);
						}else{
							$array_peca_sem_estoque[] = "A peça $referencia não possui quantidade suficiente em estoque em estoque, efetue um pedido para reposição";
						}
					}

				}
			}
						
		}

	}
