<?php

use Posvenda\Model\Extrato as ExtratoModel;

class ExtratoIbramed
{
    
    private $_model;
    private $_fabrica;
    
    public function __construct($fabrica) {
        $this->_fabrica = $fabrica;

        if(!empty($this->_fabrica)){
            $this->_model = new ExtratoModel($this->_fabrica);
        }
    }
    
    public function verificaLGR($extrato = "", $posto = "", $data_15 = "")
    {
        if (empty($extrato)) {
            $desc_posto = (!empty($posto)) ? "- Posto : {$posto}" : "";
            throw new \Exception("Extrato não informado para a verificação de LGR {$desc_posto}");
        }

        if (empty($posto)) {
            throw new \Exception("Posto não informado para a verificação de LGR - Extrato : {$extrato}");
        }

        if (empty($data_15)) {
            throw new \Exception("Período de geração não informado para a verificação de LGR - Extrato : {$extrato}");
        }

        $fabrica = $this->_fabrica;
        $pdo = $this->_model->getPDO();
        
        $sql = "
            UPDATE tbl_faturamento_item SET
                extrato_devolucao = $extrato,
                devolucao_obrig = tbl_os_item.peca_obrigatoria
            FROM tbl_os_item, tbl_os_produto, tbl_os_extra, tbl_faturamento, tbl_peca, tbl_servico_realizado
            WHERE (
                tbl_os_item.os_item = tbl_faturamento_item.os_item
                OR ( tbl_os_item.peca = tbl_faturamento_item.peca AND tbl_os_item.pedido_item = tbl_faturamento_item.pedido_item AND tbl_faturamento_item.os_item IS NULL )
                OR ( tbl_os_item.peca = tbl_faturamento_item.peca AND tbl_os_item.pedido = tbl_faturamento_item.pedido AND tbl_faturamento_item.os_item IS NULL )
            )
            AND tbl_peca.peca = tbl_os_item.peca
            AND tbl_os_item.os_produto = tbl_os_produto.os_produto
            AND tbl_os_produto.os = tbl_os_extra.os
            AND tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado
            AND tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
            AND tbl_faturamento.posto = $posto
            AND tbl_faturamento.fabrica = $fabrica
            AND tbl_faturamento.emissao <='$data_15'
            AND tbl_faturamento.cancelada IS NULL
            AND tbl_faturamento_item.extrato_devolucao IS NULL
            AND tbl_peca.aguarda_inspecao IS NOT TRUE
            AND (
                (
                    tbl_os_item.peca_obrigatoria IS TRUE 
                    AND tbl_servico_realizado.gera_pedido IS TRUE
                    AND (
                        tbl_faturamento.cfop ILIKE '59%' 
                        OR 
                        tbl_faturamento.cfop ILIKE '69%'
                    )
                )
                OR
                (
                    tbl_servico_realizado.gera_pedido IS NOT TRUE
                    AND tbl_servico_realizado.troca_de_peca IS TRUE
                )
            )
        ";
        $query  = $pdo->query($sql);

        if(!$query){
            throw new \Exception("Erro ao verificar o LGR do extrato {$extrato} - /* SQL 1 */");
        }

       $sql = "INSERT INTO tbl_extrato_lgr (extrato, posto, peca, qtde)
                SELECT
                tbl_extrato.extrato,
                tbl_extrato.posto,
                tbl_faturamento_item.peca,
                SUM (tbl_faturamento_item.qtde)
                FROM tbl_extrato
                JOIN tbl_faturamento_item ON tbl_extrato.extrato = tbl_faturamento_item.extrato_devolucao
                WHERE tbl_extrato.fabrica = $fabrica
                AND tbl_extrato.extrato = $extrato
                GROUP BY tbl_extrato.extrato,
                tbl_extrato.posto,
                tbl_faturamento_item.peca";
        $query  = $pdo->query($sql);

        if(!$query){
            throw new \Exception("Erro ao verificar o LGR do extrato {$extrato} - /* SQL 3 */");
        }
    }
    
}