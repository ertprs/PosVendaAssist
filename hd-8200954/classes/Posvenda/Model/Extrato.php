<?php

namespace Posvenda\Model;

class Extrato extends AbstractModel
{

    private $_extrato; 
    private $_fabrica;

    public function __construct($fabrica = null, $extrato = null)
    {

        parent::__construct('tbl_extrato');

        if(!empty($extrato)) {
            $this->_extrato = $extrato;
        }

        $this->_fabrica = $fabrica;

    }

    /* Seleciona Posto */
    public function getPosto(){

        $pdo = $this->getPDO();

        $sql = "
             SELECT
                tbl_posto.posto 
            FROM tbl_extrato
            JOIN tbl_posto ON tbl_posto.posto = tbl_extrato.posto
            JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_extrato.posto AND tbl_posto_fabrica.fabrica = {$this->_fabrica}
            WHERE tbl_extrato.extrato = {$this->_extrato}
            AND tbl_extrato.fabrica = {$this->_fabrica}
        ";

        $query  = $pdo->query($sql);
        $res    = $query->fetch(\PDO::FETCH_ASSOC);

        return $res['posto'];

    }

    /* Seleciona a quantidade de Itens */
    public function getQtdeOSs(){

        $pdo = $this->getPDO();

        $sql = "
            SELECT count(*) AS qtde_itens 
            FROM tbl_os
            JOIN tbl_os_extra USING (os)
            WHERE tbl_os_extra.extrato = {$this->_extrato}
        ";

        $query  = $pdo->query($sql);
        $res    = $query->fetch(\PDO::FETCH_ASSOC);

        return $res['qtde_itens'];

    }

    /* Seleciona a OS do extrato */
    public function getOS($offset){

        $pdo = $this->getPDO();

        $sql = "
            SELECT tbl_os.os 
            FROM tbl_os
            JOIN tbl_os_extra   using (os)
            WHERE tbl_os_extra.extrato = {$this->_extrato}
            AND tbl_os.fabrica = {$this->_fabrica}
            ORDER BY tbl_os.os DESC
            LIMIT 1 OFFSET {$offset}
        ";

        $query  = $pdo->query($sql);
        $res    = $query->fetch(\PDO::FETCH_ASSOC);

        return $res['os'];

    }

    public function getMaoObra($os){

        $pdo = $this->getPDO();

        $sql = "
           SELECT
                max(tbl_diagnostico.mao_de_obra) as mao_de_obra 
            FROM tbl_os
            JOIN tbl_os_defeito_reclamado_constatado USING(os)
            JOIN tbl_diagnostico ON tbl_diagnostico.fabrica = {$this->_fabrica} and tbl_os_defeito_reclamado_constatado.defeito_constatado = tbl_diagnostico.defeito_constatado
            JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto and tbl_produto.familia = tbl_diagnostico.familia
            WHERE os = {$os}
            AND tbl_diagnostico.mao_de_obra IS NOT NULL
        ";

        $query  = $pdo->query($sql);
        $res    = $query->fetch(\PDO::FETCH_ASSOC);

        $mobra = $res['mao_de_obra'];

        if(empty($mobra)){

            $sql = "
               SELECT
                    mao_de_obra 
                FROM tbl_os
                WHERE os = {$os} 
                AND fabrica = {$this->_fabrica}
            ";

            $query  = $pdo->query($sql);
            $res    = $query->fetch(\PDO::FETCH_ASSOC);

            $mobra = $res['mao_de_obra'];

        }

        return $mobra;

    }

    public function atualizaMaoObraOs($os, $mao_de_obra){

        $pdo = $this->getPDO();

        $sql = "
           UPDATE tbl_os SET mao_de_obra = {$mao_de_obra} WHERE os = {$os}
        ";

        $query  = $pdo->query($sql);

        return true;

    }

    public function atualizaOsItem($os){

        $pdo = $this->getPDO();

        $sql = "
            UPDATE  tbl_os_item
            SET     preco       = tbl_tabela_item.preco,
                    custo_peca  = tbl_tabela_item.preco + (tbl_tabela_item.preco * (COALESCE(tbl_peca.percentual_reembolso,0) / 100))
            FROM    tbl_os
            JOIN    tbl_os_produto          USING(os)
            JOIN    tbl_os_item osi         USING(os_produto)
            JOIN    tbl_peca                USING(peca)
            JOIN    tbl_posto_linha         USING(posto)
            JOIN    tbl_tabela_item         ON  tbl_tabela_item.tabela  = tbl_posto_linha.tabela
                                            AND tbl_tabela_item.peca    = osi.peca
            JOIN    tbl_servico_realizado   USING(servico_realizado)
            WHERE   tbl_os_item.os_item                 = osi.os_item
            AND     tbl_peca.fabrica                    = {$this->_fabrica}
            AND     tbl_servico_realizado.fabrica       = {$this->_fabrica}
            AND     tbl_servico_realizado.peca_estoque  IS TRUE
            AND     tbl_servico_realizado.ressarcimento IS TRUE
            AND     tbl_os.os = {$os}
        ";

        $query  = $pdo->query($sql);

        return true;

    }

    public function atualizaOsPecas($os){

        $pdo = $this->getPDO();

        $sql = "
            UPDATE tbl_os SET pecas =
                CASE WHEN
                    (
                        SELECT SUM (tbl_os_item.custo_peca * tbl_os_item.qtde) AS total
                        FROM   tbl_os_item
                        JOIN   tbl_os_produto        USING (os_produto)
                        JOIN   tbl_peca        USING(peca)
                        JOIN   tbl_servico_realizado USING (servico_realizado)
                        WHERE  tbl_os_produto.os = tbl_os.os
                        AND    tbl_servico_realizado.troca_de_peca IS TRUE
                        AND    tbl_servico_realizado.ressarcimento IS TRUE
                    ) IS NOT NULL THEN (
                        SELECT SUM (tbl_os_item.custo_peca * tbl_os_item.qtde) AS total
                        FROM   tbl_os_item
                        JOIN   tbl_os_produto        USING (os_produto)
                        JOIN   tbl_servico_realizado USING (servico_realizado)
                        WHERE  tbl_os_produto.os = tbl_os.os
                        AND    tbl_servico_realizado.troca_de_peca IS TRUE
                        AND    tbl_servico_realizado.ressarcimento IS TRUE
                    )
                ELSE
                    0
                END
            WHERE tbl_os.os = {$os}
        ";

        $query  = $pdo->query($sql);

        return true;

    }

    public function totalizaExtrato($con){

        $sql = "
            SELECT 
                SUM(tbl_os.mao_de_obra) as total_mo, 
                SUM(tbl_os.qtde_km_calculada) as total_km, 
                SUM(tbl_os.pecas) as total_pecas, 
                SUM(tbl_os.valores_adicionais) as total_adicionais  
            FROM tbl_os 
            JOIN tbl_os_extra USING(os) 
            WHERE tbl_os_extra.extrato = {$this->_extrato} 
        ";

        $res = pg_query($con, $sql);

        if(pg_num_rows($res) > 0){

            $total_mo               = (pg_fetch_result($res, 0, 'total_mo'))         ? pg_fetch_result($res, 0, 'total_mo')    : 0;
            $total_km               = (pg_fetch_result($res, 0, 'total_km'))         ? pg_fetch_result($res, 0, 'total_km')    : 0;
            $total_pecas            = (pg_fetch_result($res, 0, 'total_pecas'))      ? pg_fetch_result($res, 0, 'total_pecas') : 0;
            $total_adicionais       = (pg_fetch_result($res, 0, 'total_adicionais')) ? pg_fetch_result($res, 0, 'total_mo')    : 0;

            $total = $total_mo + $total_km + $total_pecas + $total_adicionais;

            $sql = "
                UPDATE 
                    tbl_extrato 
                SET 
                    total           = {$total},
                    mao_de_obra     = {$total_mo},
                    pecas           = {$total_pecas},
                    deslocamento    = {$total_km},
                    valor_adicional = {$total_adicionais} 
                WHERE 
                    extrato = {$this->_extrato}
            ";

            $res = pg_query($con, $sql);

        }else{
            $total = 0;
        }

        return $total;        

    }

    public function calcula($os){

        $mao_de_obra = $this->getMaoObra($os);

        if(!empty($mao_de_obra)){
            $this->atualizaMaoObraOs($os, $mao_de_obra);
        }

        $this->atualizaOsItem($os);

        $this->atualizaOsPecas($os);

    }

}

