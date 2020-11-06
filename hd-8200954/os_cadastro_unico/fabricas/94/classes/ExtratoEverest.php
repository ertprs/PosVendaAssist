<?php
/*
	REGRAS DA EVEREST
*/
	class ExtratoEverest {

		private $_fabrica = 94;
		private $_classExtrato;

		public function __construct($classExtrato){
			$this->_classExtrato = $classExtrato;
		}

		public function getValorMinimoLGR(){
			$pdo = $this->_classExtrato->_model->getPDO();

			$sql = "SELECT tbl_fabrica.valor_minimo_extrato FROM tbl_fabrica
				WHERE tbl_fabrica.fabrica = ".$this->_fabrica;
			$query = $pdo->query($sql);
			$qtd   = $query->rowCount();

			if($qtd > 0){
				$res = $query->fetchAll(\PDO::FETCH_ASSOC);
				$valor_minimo_extrato = $res[0]['valor_minimo_extrato'];

				return array("success" => true, "valor_minimo_lgr" => $valor_minimo_extrato);
			}else{
				return array("success" => false);
			}
		}


	    function verificarTotalPeca($extrato = "", $posto = "", $data_15 = "", $valor_minimo_lgr = 0){
            $fabrica = $this->_fabrica;

	        $pdo = $this->_classExtrato->_model->getPDO();

	        $sql = "SELECT SUM(tbl_faturamento_item.preco*tbl_faturamento_item.qtde) AS total_peca
				FROM tbl_faturamento
					JOIN tbl_faturamento_item using(faturamento)
					JOIN tbl_os_item ON tbl_faturamento_item.pedido = tbl_os_item.pedido and tbl_faturamento_item.peca = tbl_os_item.peca
				WHERE tbl_faturamento.fabrica in(10, $fabrica)
				AND tbl_faturamento.emissao >='2010-01-01'
				AND tbl_faturamento.emissao <='$data_15'
				AND tbl_faturamento.cancelada IS NULL
				AND tbl_faturamento_item.extrato_devolucao IS NULL
				AND tbl_os_item.peca_obrigatoria
				and tbl_faturamento.posto = $posto 
				AND tbl_os_item.fabrica_i = $fabrica
				AND (tbl_faturamento.cfop ILIKE '59%' OR tbl_faturamento.cfop ILIKE '69%')
				";
			$query = $pdo->query($sql);
			$qtd   = $query->rowCount();
		
			if($qtd > 0){
				$res = $query->fetchAll(\PDO::FETCH_ASSOC);

				if((float) $res[0]["total_peca"] >= (float) $valor_minimo_lgr){
					return true;
				}else{
					return false;
				}
			}
	    }
	}
?>
