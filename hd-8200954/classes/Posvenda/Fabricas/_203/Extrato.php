<?php

#namespace Posvenda;

use Posvenda\Model\Extrato as ExtratoModel;

class ExtratoBrotherInternational {

    public $_model;
    public $_erro;

    protected $_fabrica;

    private $_extrato;

    public function __construct($fabrica, $extrato) {
        if (!empty($extrato)) {
            $this->_extrato = $extrato;
        }

        $this->_fabrica = $fabrica;

        if(!empty($this->_fabrica)){
            $this->_model = new ExtratoModel($this->_fabrica);
        }

    }

    public function getExtrato(){
        return $this->_extrato;
	}

	public function calcula($extrato,$posto){

        $pdo = $this->_model->getPDO();

        if(!empty($posto)) {
			$cond_posto = " AND tbl_os.posto = $posto ";
		}

        $sql = "
        	SELECT 
        		tbl_os.os,
        		tbl_os_item.servico_realizado,
        		tbl_posto_fabrica.tipo_posto,
        		tbl_os.tipo_atendimento,
        		tbl_os.produto,
        		mosr.mao_de_obra,
        		tbl_os_extra.extrato
        	FROM tbl_os
        	JOIN tbl_os_extra       ON tbl_os_extra.os = tbl_os.os
			JOIN tbl_produto        ON tbl_produto.produto = tbl_os.produto
			JOIN tbl_os_produto     ON tbl_os_produto.produto = tbl_os.produto 
			AND tbl_os_produto.os = tbl_os.os
			JOIN tbl_os_item        ON tbl_os_item.os_produto = tbl_os_produto.os_produto
			JOIN tbl_posto_fabrica  ON tbl_posto_fabrica.posto = tbl_os.posto and tbl_posto_fabrica.fabrica = tbl_os.fabrica
			JOIN tbl_tipo_posto     ON tbl_posto_fabrica.tipo_posto = tbl_tipo_posto.tipo_posto AND tbl_tipo_posto.fabrica = tbl_posto_fabrica.fabrica 
			JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_os.fabrica
			JOIN tbl_mao_obra_servico_realizado mosr ON mosr.fabrica = {$this->_fabrica}
			AND mosr.servico_realizado = tbl_os_item.servico_realizado
			AND mosr.tipo_posto = tbl_posto_fabrica.tipo_posto
			AND mosr.produto = tbl_os.produto
			WHERE tbl_os.fabrica = {$this->_fabrica} 
			AND tbl_os_extra.extrato = $extrato
			{$cond_posto}";
        $query  = $pdo->query($sql);
		$res    = $query->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($res as $dadosLinha) {
            $valor_mo   = $dadosLinha['mao_de_obra'];
            $os         = $dadosLinha['os'];

            $sqlmo = "UPDATE tbl_os SET mao_de_obra = $valor_mo WHERE os = $os AND fabrica = {$this->_fabrica}";
            $querymo  = $pdo->query($sqlmo);
        }
    }
}

?>
