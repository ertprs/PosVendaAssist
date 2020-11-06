<?php

    /* Regras de valores de extrato para a Qbex */

    class RegrasExtrato{

        private $_fabrica;
        private $_classExtrato;

        public function __construct($classExtrato, $fabrica = ""){
            $this->_classExtrato = $classExtrato;
            $this->_fabrica = $fabrica;
        }

        public function run(){
            echo "regras de extratoeeee para a fabrica ".$this->_fabrica." ativa...";
        }
       

        function verificarTotalPeca($extrato = "", $posto = "", $data_15 = "", $fabrica = ""){
            $fabrica = $this->_fabrica;

            $pdo = $this->_classExtrato->_model->getPDO();

            $sql = "SELECT SUM(tbl_faturamento_item.preco*tbl_faturamento_item.qtde) AS total_peca
                FROM tbl_faturamento
                    JOIN tbl_faturamento_item using(faturamento)
                    JOIN tbl_os_item using(os_item)
                    JOIN tbl_extrato using(posto) 
                WHERE tbl_faturamento.fabrica in(10, $fabrica)
                AND tbl_faturamento.emissao >='2010-01-01'
                AND tbl_faturamento.emissao <='$data_15'
                AND tbl_faturamento.cancelada IS NULL
                AND tbl_faturamento_item.extrato_devolucao IS NULL
                AND tbl_os_item.peca_obrigatoria
                AND tbl_os_item.fabrica_i = $fabrica
                AND (tbl_faturamento.cfop ILIKE '59%' OR tbl_faturamento.cfop ILIKE '69%')
                AND tbl_extrato.extrato = $extrato";
            $query = $pdo->query($sql);
            $qtd   = $query->rowCount();
        
            if($qtd > 0){
                $res = $query->fetchAll(\PDO::FETCH_ASSOC);

                if((float) $res[0]["total_peca"] >= 50.0){
                    return true;
                }else{
                    return false;
                }
            }
        }
    }
?>