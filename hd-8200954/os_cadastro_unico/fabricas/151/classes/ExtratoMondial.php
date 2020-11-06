<?php

	/* Regras de valores de extrato para a Mondial */

	class RegrasExtrato
	{

		private $_fabrica;
		private $_classExtrato;

		public function __construct($classExtrato, $fabrica = "")
		{
			$this->_classExtrato = $classExtrato;
			$this->_fabrica = $fabrica;
		}

		public function verificaRegraPosto($posto)
		{

			global $con;

			$sql = "SELECT parametros_adicionais FROM tbl_posto_fabrica WHERE fabrica = {$this->_fabrica} AND posto = {$posto}";
			$res = pg_query($con, $sql);

			if(pg_num_rows($res) > 0){

				$parametros_adicionais = json_decode(pg_fetch_result($res, 0, "parametros_adicionais"), true);

				if(isset($parametros_adicionais["qtde_os_item"]) && isset($parametros_adicionais["valor_extrato"])){

					$qtde_os 		= $parametros_adicionais["qtde_os_item"];
					$valor_extrato 	= $parametros_adicionais["valor_extrato"];

					if (empty($qtde_os) || empty($valor_extrato)) {
						return false;
					} else {
						return array("qtde_os" => $qtde_os, "valor_extrato" => $valor_extrato);
					}

				} else {
					return false;
				}

			} else {
				return false;
			}

		}

		public function recalcularExtrato ($dados_regra = array(), $qtde_os_extrato_posto = 0, $posto = "")
        {

			$extrato = $this->_classExtrato->getExtrato();

			if (count($dados_regra) > 0 && strlen($posto) > 0 && strlen($extrato) > 0) {

				extract($dados_regra);

				$valor_extrato = str_replace(",", ".", $valor_extrato);

				// echo $qtde_os." - ".$valor_extrato." - ".$posto." - ".$extrato;

				if ($qtde_os_extrato_posto > $qtde_os) {

					$this->atualizaAvulso($posto, $extrato, $valor_extrato, $qtde_os);

				} else {

					$this->atualizaAvulso($posto, $extrato, $valor_extrato);

				}

			} else {
				return false;
			}

		}

		public function atualizaAvulso($posto, $extrato, $valor_extrato, $limit = "")
		{

			$pdo = $this->_classExtrato->_model->getPDO();

			if (strlen($limit) > 0) {
				$limit = " LIMIT {$limit} ";
			}

			/* Seleciona as OSs com maiores valores */
			$sql = "SELECT tbl_os.os
					FROM tbl_os
					JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os AND tbl_os_extra.extrato = {$extrato}
					ORDER BY tbl_os.mao_de_obra DESC
					{$limit}";
			$query  = $pdo->query($sql);
    		$res    = $query->fetchAll(\PDO::FETCH_ASSOC);

			if (count($res) > 0) {

				foreach ($res as $os) {
					foreach ($os as $key => $value) {
						$oss[] = $value;
					}
				}

				$oss = implode(",", $oss);

				/* Zera a mão de obra das OSs com maiores valores */
				$sql = "UPDATE tbl_os SET mao_de_obra = 0 WHERE os IN({$oss})";
				$query  = $pdo->query($sql);

				$sql =" INSERT INTO tbl_extrato_lancamento (
		                            posto           ,
		                            fabrica         ,
		                            lancamento      ,
		                            historico       ,
		                            debito_credito  ,
		                            valor           ,
		                            automatico      ,
		                            extrato
		                        ) VALUES (
		                         	$posto,
		                            {$this->_fabrica},
		                            198,
		                            'Crédito do valor fixo estabelecido pela fabrica',
		                            'C',
		                            {$valor_extrato},
		                            true ,
		                            {$extrato}
		                        )
		                  ";
                $query = $pdo->query($sql);

			}

			return true;

		}

		public function run()
		{
			echo "regras de extrato para a fabrica ".$this->_fabrica." ativa...";
		}


	}
