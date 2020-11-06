<?php

use Posvenda\Model\Extrato as ExtratoModel;

/**
* Class Extrato Midea Carrier
*/
class ExtratoMideaCarrier
{
    public $_model;
    protected $_fabrica;
    protected $_posto;
    private $extrato;

    public function __construct($fabrica, $posto = null) {

        $this->_fabrica = $fabrica;

        if(!empty($this->_fabrica)){
            $this->_model = new ExtratoModel($this->_fabrica);
        }

        if (!empty($posto)) {
            $this->_posto = $posto;
        }

    }

    public function getOsPosto() {
        $pdo = $this->_model->getPDO();
        
        if (!empty($this->_posto)) {
            $condPosto = "AND tbl_posto_fabrica.posto = {$this->_posto}";
        }

        $sql = "
            SELECT
                posto,
                codigo_posto,
                conta_contabil,
                centro_custo
            FROM tbl_posto_fabrica
            WHERE fabrica = {$this->_fabrica}
            AND centro_custo IS NOT NULL
            AND conta_contabil IS NOT NULL
            AND credenciamento IN ('EM CREDENCIAMENTO','CREDENCIADO','EM DESCREDENCIAMENTO')
            AND posto NOT IN (SELECT posto_filial FROM tbl_posto_filial WHERE fabrica = {$this->_fabrica})
            {$condPosto};
        ";

        $query  = $pdo->query($sql);
        $res    = $query->fetchAll(\PDO::FETCH_ASSOC);

        return $res;
    }

    public function getExtratoPagto() {
        $pdo = $this->_model->getPDO();
        
        $sql = "
            SELECT DISTINCT
                pf.posto,
                pf.codigo_posto,
                pf.conta_contabil,
                pf.centro_custo
            FROM tbl_posto_fabrica pf
            JOIN tbl_extrato e USING(posto,fabrica)
            JOIN tbl_extrato_pagamento ep USING(extrato)
            WHERE pf.fabrica = {$this->_fabrica}
	    AND ep.data_pagamento IS NULL;
        ";

        $query  = $pdo->query($sql);
        $res    = $query->fetchAll(\PDO::FETCH_ASSOC);

        return $res;
    }

    public function getOsPostoLgr($posto)
    {

        $pdo = $this->_model->getPDO();

        $sql = "
            SELECT
                pf.posto,
                pf.codigo_posto,
                pf.conta_contabil,
                pf.centro_custo
            FROM tbl_posto_fabrica pf
            WHERE pf.fabrica = {$this->_fabrica}
            AND pf.centro_custo IS NOT NULL
            AND pf.conta_contabil IS NOT NULL
            AND posto = {$posto};
        ";

        $query  = $pdo->query($sql);
        $res    = $query->fetch(\PDO::FETCH_ASSOC);

        return $res;

    }

    public function verificaMatriz($codigo_posto = null, $codigo_fornecedor = null)
    {

	$retorno = false;

	if (!empty($codigo_posto) && !empty($codigo_fornecedor)) {

	    $pdo = $this->_model->getPDO();

	    $sql = "SELECT codigo_posto, conta_contabil FROM tbl_posto_fabrica WHERE fabrica = 169 AND JSON_FIELD('matriz', parametros_adicionais) = 't';";
	    $query = $pdo->query($sql);
	    $matrizes = $query->fetchAll(\PDO::FETCH_ASSOC);

	    foreach($matrizes as $matriz) {
		if ($matriz['conta_contabil'] == $codigo_fornecedor && $matriz['codigo_posto'] != $codigo_posto) {
            	    $retorno = true;
        	}
	    }
	}

	return $retorno;

    }

    public function relacionaExtratoOS($fabrica = null, $posto = null, $extrato = null, $os = null, $valor_os_sap = null)
    {

        $pdo = $this->_model->getPDO();

        if (empty($fabrica)) {
            $fabrica = $this->_fabrica;
        } else if (empty($posto)) {
            throw new Exception("Id do posto não informado para relacionar com o extrato para o posto");
        } else if (empty($extrato)) {
            throw new Exception("Extrato não informado para relacionar a OS com o extrato para o posto: {$posto}");
        } else if (empty($os)) {
            throw new Exception("OS não informada para relacionar com o extrato para o posto: {$posto}");
        } else if (empty($valor_os_sap)) {
            throw new Exception("Valor da OS no SAP não informada para relacionar com o extrato para o posto: {$posto}");
        }

        $os_garantia = \Posvenda\Regras::get("os_garantia", "extrato", $this->_fabrica);

        if ($os_garantia) {
            $whereOsGarantia = "AND tbl_tipo_atendimento.fora_garantia IS NOT TRUE";
        }

        $sql = "
            SELECT
                tbl_os.os,
        		tbl_os_extra.extrato,
        		tbl_os_extra.valor_total_hora_tecnica
            FROM tbl_os
            JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
            JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = {$this->_fabrica}
            WHERE tbl_os.fabrica = {$this->_fabrica}
            AND tbl_os.posto = {$posto}
            AND tbl_os.os = {$os}
            AND tbl_os.excluida IS NOT TRUE
            {$whereOsGarantia};
        ";

        $query = $pdo->query($sql);

        if (!$query) {
            $this->_erro = $pdo->errorInfo();
            throw new \Exception("Erro ao relacionar OS com Extrato para o posto: {$posto}");
        }

        $res = $query->fetch();

        $nao_gera_extrato_os_auditoria = \Posvenda\Regras::get("nao_gera_extrato_os_auditoria", "extrato", $this->_fabrica);

        if ($res !== false) {
            if ($nao_gera_extrato_os_auditoria == true) {
                $osClass = new \Posvenda\Os($fabrica, $os);

                $intervencao = $osClass->_model->verificaOsIntervencao();

                if($intervencao != false){
                    return false;
                }
            }

	    $valor_total_hora_tecnica = number_format($res['valor_total_hora_tecnica'], 2, '.', '');

	    if ($valor_total_hora_tecnica == $valor_os_sap) {
		return false;
	    }

	    if (empty($res['extrato'])) {
		$sql = "UPDATE tbl_os_extra SET extrato = {$extrato}, valor_total_hora_tecnica = '{$valor_os_sap}' WHERE os = {$os};";
	    } else {
		$sql = "UPDATE tbl_os_extra SET valor_total_hora_tecnica = valor_total_hora_tecnica + '{$valor_os_sap}' WHERE os = {$os} AND extrato = {$extrato};";
	    }

	    $query  = $pdo->query($sql);

            if(!$query){
                throw new \Exception("Erro ao relacionar OS com Extrato para o posto: {$posto}");
            }
        }

        return true;

    }

    public function setValorTotal($extrato = null, $valor_total = null)
    {

        if (empty($valor_total)) {
            throw new Exception("Valor Total não informado para o extrato {$extrato} para o posto: {$posto}");
        } else if (empty($extrato)) {
            throw new Exception("Extrato não informado para atualizar o valor do extrato {$extrato} para o posto : {$posto}");
        }

        $pdo = $this->_model->getPDO();

        $sql = "UPDATE tbl_extrato SET total = '{$valor_total}' WHERE extrato = {$extrato};";

        $query  = $pdo->query($sql);

        if(!$query){
            throw new \Exception("Erro ao relacionar OS com Extrato para o posto : {$posto}");
        }

    }

    public function insereExtratoPagto($fabrica, $extrato = null, $pedido_compra = null)
    {
        if (empty($fabrica)) {
            $fabrica = $this->_fabrica;
        }

        if (empty($pedido_compra)) {
            throw new Exception("Pedido de Compra não informado para o extrato {$extrato} para o posto: {$posto}");
        } else if (empty($extrato)) {
            throw new Exception("Extrato não informado para atualizar o valor do extrato {$extrato} para o posto : {$posto}");
        }

        $pdo = $this->_model->getPDO();

        $sql = "INSERT INTO tbl_extrato_pagamento (extrato, autorizacao_pagto) VALUES ({$extrato}, '{$pedido_compra}');";
        $query  = $pdo->query($sql);

        if(!$query){
            throw new \Exception("Erro ao gravar o pedido de compra SAP no Extrato para o posto: {$posto}");
        }
    }

    public function atualizarPagto($extrato, $dataPagto, $statusSAP)
    {
        if (empty($dataPagto)) {
            throw new Exception("Data de pagamento não informada para o extrato {$extrato}");
        } else if (empty($extrato)) {
            throw new Exception("Extrato não informado para atualizar as informações de pagamento");
        }

        $pdo = $this->_model->getPDO();

        if ($statusSAP == 'Bloqueado') {
            $query = $pdo->query("UPDATE tbl_extrato SET bloqueado = TRUE, previsao_pagamento = NULL WHERE extrato = {$extrato};");
            $query = $pdo->query("UPDATE tbl_extrato_pagamento SET data_pagamento = NULL WHERE extrato = {$extrato};");
            
            if(!$query){
                throw new \Exception("Erro ao atualizar informações de pagamento do extrato {$extrato} #001");
            }

        } else if ($statusSAP != 'Aberto') {
            if (strtotime($dataPagto) >= strtotime(date('Y-m-d'))) {
                $query = $pdo->query("UPDATE tbl_extrato SET bloqueado = FALSE, previsao_pagamento = '{$dataPagto}' WHERE extrato = {$extrato};");
                $query = $pdo->query("UPDATE tbl_extrato_pagamento SET data_pagamento = NULL WHERE extrato = {$extrato};");

                if(!$query){
                    throw new \Exception("Erro ao atualizar informações de pagamento do extrato {$extrato} #002");
                }
            } else {
                $query = $pdo->query("UPDATE tbl_extrato_pagamento SET data_pagamento = '{$dataPagto}' WHERE extrato = {$extrato};");
                $query = $pdo->query("UPDATE tbl_extrato SET bloqueado = FALSE, previsao_pagamento = NULL WHERE extrato = {$extrato};");
                if(!$query){
                    throw new \Exception("Erro ao atualizar informações de pagamento do extrato {$extrato} #003");
                }
            }
        }

        if(!$query){
            throw new \Exception("Erro ao atualizar informações de pagamento do extrato {$extrato} #004");
        }
    }

    public function atualizarStatusOS($extrato)
    {
        if (empty($extrato)) {
            throw new \Exception("Extrato não informado para atualizar as informações de pagamento");
        }

        $pdo = $this->_model->getPDO();

        $sql = "SELECT DISTINCT os FROM tbl_os_extra WHERE extrato = {$extrato};";
        $query = $pdo->query($sql);

        $res = $query->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($res as $ordem) {
            $query = $pdo->query("SELECT fn_os_status_checkpoint_os({$ordem['os']}) AS status_checkpoint;");
            $resStatus = $query->fetch();

            if (!empty($resStatus['status_checkpoint'])) {
                $status_checkpoint = $resStatus['status_checkpoint'];
                $query = $pdo->query("UPDATE tbl_os SET status_checkpoint = {$status_checkpoint} WHERE os = {$ordem['os']};");
                if (!$query) {
                    throw new \Exception("Erro ao atualizar informações de pagamento do extrato {$extrato} #005");
                }
            }
        }
    }

}

