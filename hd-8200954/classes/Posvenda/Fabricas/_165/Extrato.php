<?php

include dirname(__FILE__) . '/../../classes/Posvenda/Extrato.php';

/**
* Class Extrato Tecvoz
*/
class ExtratoTecvoz
{

	private $extrato;
	private $_serverEnvironment;

	public function __construct($extrato)
    {
        $this->extrato = $extrato;

        include "/etc/telecontrol.cfg";
        $this->_serverEnvironment = $_serverEnvironment;
    }
	
	/**
     *  - LGRNovo($extrato, $posto, $fabrica)
     *  Realiza a geração das notas de devolução
     * de peças envolvidas em movimentação de estoque
     *
     * @param $extrato ID do extrato
     * @param $posto ID do posto que devolverá as peças
     * @param $fabrica ID da fábrica que receberá as peças danificadas
     *
     * @return void
     */
    public function LGRTecvoz($extrato, $posto, $fabrica){

        $pdo = $this->extrato->_model->getPDO();

        $sql = "
			SELECT DISTINCT
                tbl_os_extra.extrato,
                tbl_os_extra.os
            FROM tbl_os_extra
            JOIN tbl_os USING(os)
            JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os_extra.os
            JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
            JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado
            LEFT JOIN tbl_estoque_posto ON tbl_estoque_posto.peca = tbl_os_item.peca AND tbl_estoque_posto.posto = {$posto}
            WHERE tbl_os.fabrica = {$fabrica}
            AND tbl_os_extra.extrato = {$extrato}
            AND (tbl_servico_realizado.peca_estoque IS TRUE
            OR (tbl_servico_realizado.gera_pedido IS TRUE
            AND tbl_os_item.peca_obrigatoria IS TRUE)
            OR (tbl_servico_realizado.troca_produto IS TRUE
            AND tbl_estoque_posto.qtde > 0));
        ";

        // echo $sql;
        $query  = $pdo->query($sql);
        $res    = $query->fetchAll(\PDO::FETCH_ASSOC);

        // var_dump($res);
        // exit;

        foreach ($res as $dadosLinha) {
            $os = $dadosLinha['os'];

            $sqlfaturamento = "
                INSERT INTO tbl_faturamento (
                    fabrica,
                    cfop,
                    extrato_devolucao,
                    emissao,
                    saida,
                    total_nota,
                    posto
                ) VALUES (
                    $fabrica,
                    '5949',
                    $extrato,
                    now(),
                    now(),
                    '0' ,
                    $posto
                ) RETURNING faturamento;
            ";
            $resfaturamento = $pdo->query($sqlfaturamento);

            $faturamento_id = $resfaturamento->fetch(\PDO::FETCH_ASSOC);

            $faturamento = $faturamento_id['faturamento'];

            $sql_os = "
                SELECT
                    tbl_os_item.peca,
                    tbl_os_item.qtde,
                    tbl_os_item.custo_peca,
                    tbl_os_item.os_item
                FROM tbl_os_produto
                JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
                WHERE tbl_os_produto.os = {$dadosLinha[os]}
                AND tbl_os_item.fabrica_i = {$fabrica};
            ";

            $res_os = $pdo->query($sql_os);
            $dadosItens    = $res_os->fetchAll(\PDO::FETCH_ASSOC);

            foreach($dadosItens as $dados){
                $peca       = $dados['peca'];
                $qtde       = $dados['qtde'];
                $custo_peca = ($dados['custo_peca'] == '')? '0': $dados['custo_peca'] ;
                $os_item    = $dados['os_item'];

                $sql_fat_item_existente = "
                    SELECT faturamento_item
                    FROM tbl_faturamento_item
                    WHERE os = {$os}
                    AND os_item = {$os_item};
                ";
                $query = $pdo->query($sql_fat_item_existente);
                $res_fat_item_existente = $query->fetchAll(\PDO::FETCH_ASSOC);

                if(pg_num_rows($res_fat_item_existente) == 0){

                    $sql_fat_item = "
                        INSERT INTO tbl_faturamento_item (
                            faturamento,
                            peca,
                            devolucao_obrig,
                            extrato_devolucao,
                            qtde,
                            preco,
                            os,
                            os_item
                        ) VALUES (
                            $faturamento,
                            $peca,
                            't',
                            $extrato,
                            '$qtde',
                            '$custo_peca',
                            $os,
                            $os_item
                        );
                    ";
                    $res_fat_item = $pdo->query($sql_fat_item);

                    $sql_ext_lgr = "
                        INSERT INTO tbl_extrato_lgr (
                            peca,
                            qtde,
                            faturamento,
                            posto,
                            extrato
                        ) VALUES (
                            $peca,
                            $qtde,
                            $faturamento,
                            $posto,
                            $extrato
                        );
                    ";
                    $res_ext_lgr = $pdo->query($sql_ext_lgr);

                }
            }
        }
    }
}

?>