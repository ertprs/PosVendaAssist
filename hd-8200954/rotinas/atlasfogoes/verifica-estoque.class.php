<?php

	class Estoque
	{
		private $con;

		public function __construct(){
			global $con;
			$this->con = $con;
		}

		public function verificaEstoque($fabrica,$posto,$os,$os_item,$peca,$qtde)
		{			

			$sql = "SELECT qtde 
					FROM tbl_estoque_posto
					WHERE fabrica = $fabrica
					AND posto = $posto
					AND peca = $peca
					AND qtde > 0
					AND qtde >= $qtde
					AND tipo = 'pulmao'";
			$res = pg_query($this->con,$sql);
			if(pg_num_rows($res) > 0)
			{
				$qtde_peca = pg_fetch_result($res, 0, 'qtde');

				$sqlServico = "SELECT servico_realizado FROM tbl_servico_realizado 
								WHERE fabrica = $fabrica 
								AND ativo IS TRUE
								AND peca_estoque IS TRUE 
								AND gera_pedido IS NOT TRUE
								AND ressarcimento IS NULL";
				$resServico = pg_query($this->con,$sqlServico);

				$servico_estoque = pg_fetch_result($resServico, 0, 'servico_realizado');


				$sqlItem = "SELECT fn_atualiza_servico_os_item($os_item, $servico_estoque, $fabrica)";
				$resItem = pg_query($this->con,$sqlItem);
				if(!pg_last_error($this->con))
				{
					$sqlMovimento = "INSERT INTO tbl_estoque_posto_movimento(
													fabrica,
													posto,
													os,
													peca,
													data,
													qtde_saida,
													os_item,
													obs,
													tipo) VALUES(
													$fabrica,
													$posto,
													$os,
													$peca,
													CURRENT_DATE,
													$qtde,
													$os_item,
													'Saída automática, peça solicitada em Ordem de Serviço',
													'pulmao')
													";
					$resMovimento = pg_query($this->con,$sqlMovimento);

					if(!pg_last_error($this->con))
					{
						$sqlU = "UPDATE tbl_estoque_posto SET qtde = $qtde_peca - $qtde
								 WHERE fabrica = $fabrica
								 AND posto = $posto
								 AND peca  = $peca
								 AND tipo  = 'pulmao'";
						$resU = pg_query($this->con,$sqlU);
						if(!pg_last_error($this->con))
						{
							return true;
						}else{
							$msg_erro = pg_last_error($this->con);
							return "false|$msg_erro";
						}
					}else{
						$msg_erro = pg_last_error($this->con);
						return "false|$msg_erro";
					}
				}else{
					$msg_erro = pg_last_error($this->con);
					return "false|$msg_erro";
				}
								
			}else{
				$msg_erro = pg_last_error($this->con);
				return "false|$msg_erro";
			}
			
		}

		public function estoqueAntigo($fabrica,$posto,$peca,$os)
		{

			$sql = "SELECT qtde 
					FROM tbl_estoque_posto 
					WHERE fabrica = $fabrica 
					AND posto = $posto 
					AND peca = $peca
					AND tipo = 'estoque'";
			$res = pg_query($this->con,$sql);

			if(pg_num_rows($res) > 0)
			{
				$qtde_peca = pg_fetch_result($res, 0, 'qtde');

				if($qtde_peca > 0)
				{
					$sqlMovimento = "INSERT INTO tbl_estoque_posto_movimento(
												fabrica,
												posto,
												os,
												peca,
												data,
												qtde_saida,
												obs,
												tipo) VALUES(
												$fabrica,
												$posto,
												$os,
												$peca,
												CURRENT_DATE,
												$qtde_peca,
												'Estoque foi zerado automaticamente, para iniciar o estoque pulmão',
												'estoque')
												";
					$resMovimento = pg_query($this->con,$sqlMovimento);

					$sql = "UPDATE tbl_estoque_posto SET qtde = 0
							WHERE fabrica = $fabrica 
							AND posto = $posto 
							AND peca = $peca
							AND tipo = 'estoque'";
					$res = pg_query($this->con,$sql);
				}else{
					$msg_erro = pg_last_error($this->con);
					return "false|$msg_erro";
				}
			}else{
				$msg_erro = pg_last_error($this->con);
				return "false|$msg_erro";
			}
		}

		function qtdePecaEstoque($peca,$posto,$fabrica){

			$sql = "SELECT qtde 
					FROM tbl_estoque_posto
					WHERE fabrica = $fabrica
					AND posto = $posto
					AND peca = peca
					AND tipo = 'pulmao'";
			$res = pg_query($this->con,$sql);

			if(pg_num_rows($res) > 0){
				$qtde = pg_fetch_result($res, 0, 'qtde');
				if($qtde == 0){
					return true;
				}else{
					$msg_erro = pg_last_error($this->con);
					return "false|$msg_erro";
				}
			}else{
				$msg_erro = pg_last_error($this->con);
				return "false|$msg_erro";
			}
		}

		public function pedidoPulmao($pecas,$fabrica,$posto,$linha){

			$pecas = implode(",", $pecas);

			$sql = "SELECT DISTINCT tbl_peca.peca 
					FROM tbl_peca 
					JOIN tbl_estoque_posto ON tbl_peca.peca = tbl_estoque_posto.peca
					AND tbl_estoque_posto.fabrica = $fabrica
					AND tbl_estoque_posto.posto = $posto
					AND tbl_estoque_posto.qtde = 0
					AND tbl_estoque_posto.tipo = 'pulmao'
					JOIN tbl_estoque_posto_movimento ON tbl_peca.peca = tbl_estoque_posto_movimento.peca
					AND tbl_estoque_posto_movimento.fabrica = $fabrica
					AND tbl_estoque_posto_movimento.posto = $posto
					AND tbl_estoque_posto_movimento.tipo = 'pulmao'
					WHERE tbl_peca.fabrica = $fabrica
					AND tbl_peca.peca IN($pecas) 
					AND controla_saldo";
			$resPeca = pg_query($this->con,$sql);
			
			if(pg_num_rows($resPeca) > 0){
				$sql = "INSERT INTO tbl_pedido (
								posto        ,
								fabrica      ,
								condicao     ,
								tipo_pedido  ,
								status_pedido
							) VALUES (
								$posto       ,
								$fabrica     ,
								1896         ,
								201          ,
								1
							) RETURNING pedido;";
				$res = pg_query($this->con, $sql);
				$msg_erro = pg_errormessage($this->con);
				
				if(empty($msg_erro)){
					$pedido_bonificado = pg_fetch_result($res, 0, 'pedido');
					
					if(empty($msg_erro)){

						for ($i=0; $i < pg_num_rows($resPeca); $i++) { 
							
							$peca = pg_fetch_result($resPeca, $i, 'peca');
							
							$sql = "SELECT qtde_entrada 
									FROM tbl_estoque_posto_movimento
									WHERE fabrica = $fabrica
									AND posto = $posto
									AND peca  = $peca
									AND tipo  = 'pulmao'
									AND qtde_entrada notnull
									ORDER BY data_digitacao DESC
									LIMIT 1";
							$res = pg_query($this->con,$sql);
							$msg_erro .= pg_errormessage($this->con);

							if(pg_num_rows($res) > 0){
								$qtde = pg_fetch_result($res, 0, 'qtde_entrada');

								$sql = "INSERT INTO tbl_pedido_item (
																		pedido ,
																		peca   ,
																		qtde   ,
																		qtde_faturada,
																		qtde_cancelada
																	) VALUES (
																		$pedido_bonificado ,
																		$peca   ,
																		$qtde   ,
																		0       ,
																		0
																	)";
								$res = pg_query($this->con,$sql);
								$msg_erro .= pg_errormessage($this->con);
							}																			

						}
						$sql = "SELECT fn_pedido_finaliza($pedido_bonificado,$fabrica)";
						$resultX = pg_query($this->con,$sql);
						$msg_erro .= pg_errormessage($this->con);
						
						if(empty($msg_erro)){
							return true;
						}else{
							$msg_erro = pg_last_error($this->con);
							return "false|$msg_erro";
						}
					}else{
						$msg_erro = pg_last_error($this->con);
						return "false|$msg_erro";
					}					
				}else{
					$msg_erro = pg_last_error($this->con);
					return "false|$msg_erro";;
				}
			}else{
				return true;
			}
			
		}
	}

	
