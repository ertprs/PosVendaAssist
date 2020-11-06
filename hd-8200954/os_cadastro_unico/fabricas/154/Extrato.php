<?php

	/* Regras de valores de extrato para a RHEEN */

	class RegrasExtrato{

		private $_fabrica;
		private $_classExtrato;

		public function __construct($classExtrato, $fabrica = ""){
			$this->_classExtrato = $classExtrato;
			$this->_fabrica = $fabrica;
		}


		public function zeraKm($extrato){

			$pdo = $this->_classExtrato->_model->getPDO();

			$sql = "UPDATE tbl_os set
						qtde_km_calculada = 0 
					FROM tbl_os_extra
					WHERE tbl_os.os = tbl_os_extra.os
					AND   extrato = {$extrato}
					AND   tbl_os.qtde_km < 51";
			$query  = $pdo->query($sql);
			if(!$query){
				$this->_erro = $pdo->errorInfo();
				throw new \Exception("Erro ao atualizar os valores das os com km menor que 51 Extrato: {$extrato}");
			}

			return true;

		}

	}


?>
