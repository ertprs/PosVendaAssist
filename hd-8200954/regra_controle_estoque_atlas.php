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

			/* Verifica qual estoque para conta a quantidade no estoque
            hd_chamado=2782600
            $tipo_estoque = ($ressarcimento == 't') ? "estoque" : "pulmao";
            */
            $tipo_estoque = "estoque";

			$sql = "SELECT qtde
					FROM tbl_estoque_posto
					WHERE peca = $xpeca
				   	AND posto = $login_posto
				   	AND fabrica = $login_fabrica
				   	AND tipo = '$tipo_estoque'";
			$res = pg_query($con, $sql);

			/* Se tiver Qtde no estoque  */
			if(pg_num_rows($res) > 0){

				/* Qtde no estoque */
				$qtde_estoque = pg_fetch_result($res, 0, 'qtde');

				/* Verifica se há movimento para aquela peça com o pedido */
		    	$sql_verifica_movimento = "SELECT peca, qtde_saida
					   FROM tbl_estoque_posto_movimento
					   WHERE
					   peca = $xpeca
					   AND posto = $login_posto
					   AND fabrica = $login_fabrica
					   AND tipo = '$tipo_estoque'
					   AND os = $os
					   AND os_item = $xos_item";
    			$res_verifica_movimento = pg_query($con, $sql_verifica_movimento);

    			/* Se tiver movimentação */
    			if(pg_num_rows($res_verifica_movimento) > 0){

    				$qtde_pecas_movimento = pg_fetch_result($res_verifica_movimento, 0, 'qtde_saida');

    			}else{

    				$qtde_pecas_movimento = 0;

    			}

    			$sql = "SELECT peca
							FROM tbl_estoque_posto_movimento
							WHERE fabrica = $login_fabrica
							AND posto = $login_posto
							AND os = $os
							AND peca = $xpeca
							AND qtde_saida = $xqtde
							AND tipo = '$tipo_estoque'";
				$resS = pg_query($con, $sql);

				if(pg_num_rows($resS) == 0){
	    			/* Verifica se gera pedido */
					if($troca_de_peca == 't'){

						if($qtde_pecas_movimento != $xqtde){

			    			/* Se a qtde do estoque for maior do ele está passando e ainda não haver movimentação.. insere na tbl_estoque_posto_movimentacao */
							if($qtde_estoque >= $xqtde && $qtde_pecas_movimento == 0){

						    	$sql_posto_movimento = "INSERT INTO tbl_estoque_posto_movimento
								(fabrica, posto, os, peca, qtde_saida, os_item, tipo,obs,data) VALUES
								($login_fabrica, $login_posto, $os, $xpeca, $xqtde, $xos_item, '$tipo_estoque','Sa&iacute;da autom&aacute;tica, pe&ccedil;a solicitada em Ordem de Servi&ccedil;o OS: $os',current_date)";
					    		$res_posto_movimento = pg_query($con, $sql_posto_movimento);

						        // TROCA DE PEÇA GERANDO PEDIDO
						        //if($tipo_estoque == "pulmao"){ hd_chamado=2782600
						        if($tipo_estoque == "estoque"){
				                    $sql_qtde_update = "UPDATE tbl_estoque_posto
				                                           SET qtde = qtde - $xqtde
				                                           WHERE
				                                           fabrica = $login_fabrica
				                                           AND posto = $login_posto
				                                           AND tipo = '$tipo_estoque'
				                                           AND peca = $xpeca
				                    ";
				                    // echo nl2br($sql_qtde_update); exit;
				                    $res_servico_update = pg_query($con, $sql_qtde_update);

			                        $update_servico_realizado = "UPDATE tbl_os_item
			                                                     SET servico_realizado = 10745
			                                                     WHERE tbl_os_item.os_item = $xos_item";
			                        // echo nl2br($update_servico_realizado); exit;
			                        $res_update_servico_realizado = pg_query($con, $update_servico_realizado);

						        }

							}

						}

					}
					/* //hd_chamado=2782600
					else{

						if($qtde_pecas_movimento != $xqtde){

							if($qtde_estoque >= $xqtde){

								// PECA USANDO ESTOQUE (RECOMPRA)
						        if($tipo_estoque == "estoque"){

							        $sql_posto_movimento = "INSERT INTO tbl_estoque_posto_movimento
									(fabrica, posto, os, peca, qtde_saida, os_item, tipo, obs,data) VALUES
									($login_fabrica, $login_posto, $os, $xpeca, $xqtde, $xos_item, '$tipo_estoque','Sa&iacute;da autom&aacute;tica, pe&ccedil;a solicitada em Ordem de Servi&ccedil;o', current_date)";
						    		$res_posto_movimento = pg_query($con, $sql_posto_movimento);

				                    $sql_servico_update = "UPDATE tbl_estoque_posto
				                                           SET qtde = qtde - $xqtde
				                                           WHERE
				                                           fabrica = $login_fabrica
				                                           AND posto = $login_posto
				                                           AND tipo = '$tipo_estoque'
				                                           AND peca = $xpeca";
				                    // echo nl2br($sql_servico_update); exit;
				                    $res_servico_update = pg_query($con, $sql_servico_update);

						        }

					        }

					    }

					}*/
				}

    		}

		}

	}
