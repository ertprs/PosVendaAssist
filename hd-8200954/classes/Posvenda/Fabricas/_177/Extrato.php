<?php

use Posvenda\Model\Extrato as ExtratoModel;

class ExtratoAnauger
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
            SELECT SUM(COALESCE(p.peso, 0))
            FROM tbl_os o
            INNER JOIN tbl_os_produto op ON op.os = o.os
            INNER JOIN tbl_os_item oi ON oi.os_produto = op.os_produto
            INNER JOIN tbl_peca p ON p.peca = oi.peca AND p.fabrica = $fabrica
            INNER JOIN tbl_servico_realizado sr ON sr.servico_realizado = oi.servico_realizado AND sr.fabrica = $fabrica
            INNER JOIN tbl_posto_fabrica pf ON pf.posto = o.posto AND pf.fabrica = $fabrica
            INNER JOIN tbl_tipo_posto tp ON tp.tipo_posto = pf.tipo_posto AND tp.fabrica = $fabrica
            LEFT JOIN tbl_extrato_lgr el ON el.os_item = oi.os_item
            WHERE o.fabrica = $fabrica
            AND o.posto = $posto
            AND o.excluida IS NOT TRUE
            AND o.finalizada IS NOT NULL
            AND ((sr.troca_de_peca IS TRUE and sr.gera_pedido is not true) OR sr.troca_produto IS TRUE)
            AND oi.peca_obrigatoria IS TRUE
            AND el.extrato_lgr IS NULL
            AND tp.posto_interno IS NOT TRUE
            --AND oi.peca_reposicao_estoque IS NOT TRUE
        ";
        
        $query  = $pdo->query($sql);
        if(!$query){
            throw new \Exception("Erro ao verificar o LGR do extrato {$extrato} - /* SQL 1 */");
        }   
        $res = $query->fetch(\PDO::FETCH_ASSOC);

        if (count($res) > 0) {
            $sum = intval($res["sum"]);
            if ($sum >= 10){
                $sql ="
                    INSERT INTO tbl_extrato_lgr (extrato, posto, peca, qtde, os_item)
                        SELECT $extrato, $posto, oi.peca, oi.qtde, oi.os_item
                            FROM tbl_os o
                            INNER JOIN tbl_os_produto op ON op.os = o.os
                            INNER JOIN tbl_os_item oi ON oi.os_produto = op.os_produto
                            INNER JOIN tbl_servico_realizado sr ON sr.servico_realizado = oi.servico_realizado AND sr.fabrica = $fabrica
                            LEFT JOIN tbl_extrato_lgr el ON el.os_item = oi.os_item
                            WHERE o.fabrica = $fabrica
                            AND o.posto = $posto
                            AND o.excluida IS NOT TRUE
                            AND o.finalizada IS NOT NULL
                            AND sr.troca_de_peca IS TRUE
                            AND sr.gera_pedido IS NOT TRUE
                            AND oi.peca_obrigatoria IS TRUE
                            AND el.extrato_lgr IS NULL
                    UNION
                        SELECT $extrato, $posto, oi.peca, oi.qtde, oi.os_item
                            FROM tbl_os o
                            INNER JOIN tbl_os_produto op ON op.os = o.os
                            INNER JOIN tbl_os_item oi ON oi.os_produto = op.os_produto
                            INNER JOIN tbl_peca p ON p.peca = oi.peca AND p.fabrica = $fabrica
                            INNER JOIN tbl_servico_realizado sr ON sr.servico_realizado = oi.servico_realizado AND sr.fabrica = $fabrica
                            LEFT JOIN tbl_extrato_lgr el ON el.os_item = oi.os_item
                            WHERE o.fabrica = $fabrica
                            AND o.posto = $posto
                            AND o.excluida IS NOT TRUE
                            AND o.finalizada IS NOT NULL
                            AND sr.troca_produto IS TRUE
                            AND p.produto_acabado IS TRUE
                            AND oi.peca_obrigatoria IS TRUE
                            AND el.extrato_lgr IS NULL
                ";
                $query  = $pdo->query($sql);
                if(!$query){
                    throw new \Exception("Erro ao inserir extrato lgr - /* SQL 2 */");
                } 
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    public function enviaComunicadoLgr(
        $msg,
        $tipo,
        $posto = null, 
        $obrigatorio_site = 'f',
        $titulo
    ) {

        $pdo = $this->_model->getPDO();

        $sql = "
            INSERT INTO tbl_comunicado (fabrica, posto, ativo, obrigatorio_site, mensagem, tipo, descricao)
            VALUES ({$this->_fabrica}, {$posto}, 't', '{$obrigatorio_site}', '{$msg}', '$tipo','{$titulo}');
        ";
        $query = $pdo->query($sql);

        if(empty($query)){
            throw new \Exception("Falha ao enviar comunicado");
        }

    }
}
