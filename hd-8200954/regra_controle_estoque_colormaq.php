<?php

	$sql_controla_estoque = "SELECT controla_estoque FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND posto = $login_posto;";
	$res_controla_estoque = pg_query($con,$sql_controla_estoque);
	$controla_estoque     = pg_fetch_result($res_controla_estoque, 0, "controla_estoque");

	/* Se o posto controlar estoque irá abater as peças do estoque pulmão, caso haja quantidade suficiente, ou gerará pedido normalmente */
	if(strlen($xservico) > 0 && $controla_estoque == "t" && strlen($xos_item) > 0){

		$tipo_estoque = "pulmao";

		/* Verifica se o tipo de serviço é Troca de peça */
		$sql = "SELECT servico_realizado  
			FROM tbl_servico_realizado 
			WHERE fabrica = $login_fabrica 
			AND servico_realizado = $xservico  
			AND troca_de_peca = 't'";
		$res = pg_query($con, $sql);



		if(pg_num_rows($res) > 0){

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

					/* Verifica se há movimentação com pedido para a peça */
					if($qtde_pecas_movimento != $xqtde){

						/* Se a quantidade em estoque foi maior que a quantidade solicitada */
						if($qtde_estoque >= $xqtde){

							// Abate do estoque pulmão e não gera pedido
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

		                    /* altera o servico da peça para Peça Estoque */
		                    $sql_servico = "SELECT servico_realizado FROM tbl_servico_realizado WHERE fabrica = {$login_fabrica} AND peca_estoque = 't'";
		                    $res_servico = pg_query($con, $sql_servico);

		                    if(pg_num_rows($res_servico) > 0){
		                    	$servico_troca = pg_fetch_result($res_servico, 0, "servico_realizado");

		                    	$update_servico_realizado = "UPDATE tbl_os_item 
				                                                    SET servico_realizado = $servico_troca
				                                                    WHERE tbl_os_item.os_item = $xos_item";
		                        // echo nl2br($update_servico_realizado); exit;
		                        $res_update_servico_realizado = pg_query($con, $update_servico_realizado);

		                    }
				        }
				    }
				}
    		}
		}
	}
