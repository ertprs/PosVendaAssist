<?php

	/* Verifica se existem extrato  */

	class FinalizaOsTroca{

		public function verificaOsTroca(){

			global $con;

			$fabrica = 151;

			$sql = "SELECT DISTINCT tbl_os.os
						FROM tbl_os_item
						INNER JOIN tbl_os_produto ON tbl_os_item.os_produto = tbl_os_produto.os_produto
						INNER JOIN tbl_os ON tbl_os.os = tbl_os_produto.os
						INNER JOIN tbl_os_troca on tbl_os.os = tbl_os_troca.os and tbl_os_troca.fabric = {$fabrica}
						INNER JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca and tbl_peca.fabrica = {$fabrica}
						INNER JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado
										and tbl_servico_realizado.fabrica = {$fabrica}
						INNER JOIN tbl_pedido_item ON tbl_os_item.pedido_item = tbl_pedido_item.pedido_item
						INNER JOIN tbl_pedido ON tbl_pedido.pedido = tbl_pedido_item.pedido and tbl_pedido.fabrica = {$fabrica}
						INNER JOIN tbl_faturamento_item ON tbl_faturamento_item.pedido_item = tbl_pedido_item.pedido_item
						WHERE tbl_os.finalizada is null
						AND tbl_peca.produto_acabado is true
						AND tbl_servico_realizado.troca_produto is true
						AND tbl_os.fabrica = {$fabrica} ";
			$res = pg_query($con, $sql);

			if(strlen(pg_last_error()) > 0){
				throw new \Exception("Erro ao buscar os para finalizar");
			}else{

				if(pg_num_rows($res) > 0){

					$arr_os = array();

					for($i = 0; $i < pg_num_rows($res); $i++){

						$os   = pg_fetch_result($res, $i, "os");

						$arr_os[] = array("os" => $os);

					}

					return $arr_os;

				}else{
					return false;
				}

			}

		}

	}

?>