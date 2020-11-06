<?php
	
	/* Verifica se existem extrato  */

	class NotaFiscalServicoExtrato{

		private $_fabrica = 151;

		public function verificaExtratroSemNFServico($periodo = 1){

			global $con;

			$sql = "SELECT tbl_extrato.extrato,
						   tbl_extrato.posto 
					FROM tbl_extrato 
					JOIN tbl_extrato_pagamento ON tbl_extrato_pagamento.extrato = tbl_extrato.extrato 
					WHERE 
						tbl_extrato.fabrica = {$this->_fabrica} 
						AND tbl_extrato_pagamento.nf_autorizacao IS NULL 
						AND tbl_extrato.data_geracao <= current_timestamp - INTERVAL '{$periodo} MONTHS'";
			$res = pg_query($con, $sql);

			if(strlen(pg_last_error()) > 0){
				throw new \Exception("Erro ao buscar os extratos sem nf de serviço dos postos");
			}else{

				if(pg_num_rows($res) > 0){

					$arr_extratos = array();

					for($i = 0; $i < pg_num_rows($res); $i++){

						$posto   = pg_fetch_result($res, $i, "posto");
						$extrato = pg_fetch_result($res, $i, "extrato");

						$arr_extratos[] = array("posto" => $posto, "extrato" => $extrato);

					}

					return $arr_extratos;

				}else{
					return false;
				}

			}

		}

		public function enviaComunicadoPosto($dados = array()){

			global $con;

			if(count($dados) > 0){

				$posto   = $dados["posto"];
				$extrato = $dados["extrato"];

				$mensagem = "Favor inserir o número de Nota Fiscal de Serviço para o extrato {$extrato}";

				$sql = "INSERT INTO tbl_comunicado 
						(
							mensagem, 
							tipo,
							fabrica,
							descricao,
							posto,
							obrigatorio_site,
							ativo
						) 
						VALUES 
						(
							'{$mensagem}',
							'Com. Unico Posto',
							{$this->_fabrica},
							'Nota de Fiscal de Serviço no Extrato: {$extrato}',
							{$posto},
							true,
							true
						)";
				$res = pg_query($con, $sql);

				if(strlen(pg_last_error()) > 0){
					throw new \Exception("Erro ao inserir o Comunicado para o posto {$posto}, e extrato {$extrato}");
				}else{
					return true;
				}

			}else{
				return false;
			}

		}

	}

?>