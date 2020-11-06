<?php

namespace Posvenda\Fabricas\_151;
use Posvenda\Os as OsPosvenda;

class Os extends OsPosvenda
{
    private $_fabrica;

    public function __construct($fabrica, $os = null, $conn = null)
    {

        if (!empty($fabrica)) {
            $this->_fabrica = $fabrica;
        }

        if (!empty($os)) {
            $this->_os = $os;
        }

        parent::__construct($this->_fabrica, $this->_os, $conn);

    }

    /**
     * - finalizar
     * MONDIAL só poderá finalizar OS
     * que estiver com peças sem devolução obrigatória
     * ou com peças nessas condições com LGR já aprovado.
     */
    public function finaliza($os)
    {
        $pdo = $this->_model->getPDO();

	$sqlItem = "SELECT oi.os_item, f.faturamento
			FROM tbl_os o
			INNER JOIN tbl_os_produto op ON op.os = o.os
			INNER JOIN tbl_os_item oi ON oi.os_produto = op.os_produto
			INNER JOIN tbl_servico_realizado sr ON sr.fabrica = {$this->_fabrica} AND sr.troca_de_peca IS TRUE AND sr.servico_realizado = oi.servico_realizado
			INNER JOIN tbl_peca p ON p.fabrica = {$this->_fabrica} AND p.peca = oi.peca
			LEFT JOIN tbl_faturamento_item fi ON fi.os = op.os AND fi.peca = oi.peca AND fi.pedido IS NULL
			LEFT JOIN tbl_faturamento f ON f.faturamento = fi.faturamento AND f.distribuidor = o.posto
            JOIN tbl_pedido_item pi ON pi.pedido_item = oi.pedido_item
			WHERE o.fabrica = {$this->_fabrica}
			AND o.os = {$this->_os}
			AND oi.peca_obrigatoria IS TRUE
			AND oi.parametros_adicionais::jsonb->>'bloqueio' = 'false'
			AND p.produto_acabado IS NOT TRUE
			AND (f.faturamento IS NULL OR (f.faturamento IS NOT NULL AND f.conferencia IS NULL))
            AND pi.qtde_faturada > 0";
        $queryItem = $pdo->prepare($sqlItem);

        if (!$queryItem->execute()) {
            return false;
        } else {
            $resItem     = $queryItem->fetchAll(\PDO::FETCH_ASSOC);

	    if (count($resItem) > 0) {
            
                throw new \Exception("Não é possível finalizar a Ordem de Serviço {$this->_sua_os} pois a devolução de peças está pendente");
	    }
        }

                $sql = "UPDATE tbl_os SET data_fechamento = now(), finalizada = now() where os = ".$this->_os." and fabrica = ".$this->_fabrica;
                $query = $pdo->query($sql);
                if (empty($query)) {
                    return false;
                } else {
                    return true;
                }
    }
}
