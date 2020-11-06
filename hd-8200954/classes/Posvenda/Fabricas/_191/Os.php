<?php

namespace Posvenda\Fabricas\_191;

use Posvenda\Os as OsPosvenda;

class Os extends OsPosvenda
{
    public function __construct($fabrica, $os = null, $conn = null)
    {
        parent::__construct($fabrica, $os, $conn);

        $this->_fabrica = $fabrica;
    }

    public function getOsOrcamento($posto = null,$os = null){

        $pdo = $this->_model->getPDO();

        if ($os != null) {
            $where_tbl_os_numero = " AND tbl_os.os = {$os}";
        }

        if($posto != null){
             $where_tbl_os_numero = " AND tbl_os.posto = {$posto}";
        }

        $sql = "SELECT DISTINCT 
                       tbl_os.posto,
               tbl_os.os,
               tbl_status_os.descricao AS status_os
                FROM tbl_os
                INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
                INNER JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
                INNER JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = {$this->_fabrica}
                --INNER JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica = {$this->_fabrica}
                INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = {$this->_fabrica}
                INNER JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto
                INNER JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
                JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = {$this->_fabrica}
                JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = {$this->_fabrica}
        LEFT  JOIN tbl_status_os ON tbl_os.status_os_ultimo = tbl_status_os.status_os
                WHERE tbl_os.fabrica = {$this->_fabrica}
                AND tbl_posto_fabrica.credenciamento IN ('CREDENCIADO', 'EM DESCREDENCIAMENTO')
                --AND tbl_servico_realizado.gera_pedido IS TRUE
                AND tbl_os.excluida IS NOT TRUE
                AND tbl_peca.produto_acabado IS NOT TRUE
                AND tbl_os_item.pedido IS NULL
                AND fn_retira_especiais(LOWER(tbl_tipo_atendimento.descricao)) = 'orcamento'                        
                AND tbl_tipo_posto.posto_interno IS TRUE
                {$where_tbl_os_numero}";
        $query = $pdo->query($sql);
        $res = $query->fetchAll(\PDO::FETCH_ASSOC);

    if(count($res) > 0){

            $os_pedido = array();

            for($i = 0; $i < count($res); $i++){

                    $os     = $res[$i]["os"];
                    $posto  = $res[$i]["posto"];
                    $status = $res[$i]["status_os"];

                    $os_pedido[] = array(
                                    "os"    => $os,
                                    "posto" => $posto,
                                    "status" => $status
                    );
            }

        return $os_pedido;

        }else{
            return false;
        }

    }

     public function getPecasPedidoOrcamento($os) {

               if (empty($os)) {
                        return false;
               }

                $pdo = $this->_model->getPDO();

                $sql = "SELECT
                                        tbl_os_item.os_item,
                                        tbl_os_item.peca,
                                        tbl_peca.referencia,
                                        tbl_os_item.qtde
                                FROM tbl_os_item
                                INNER JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                                INNER JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = {$this->_fabrica}
                                --INNER JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica = {$this->_fabrica}
                                INNER JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os_produto.os
                                WHERE tbl_os_produto.os = {$os}
                                AND tbl_peca.produto_acabado IS NOT TRUE
                                --AND tbl_os_item.pedido IS NULL
                                --AND tbl_servico_realizado.gera_pedido IS TRUE
                                ";
                $query = $pdo->query($sql);
                $res = $query->fetchAll(\PDO::FETCH_ASSOC);

                return $res;

        }

        public function getCondicaoBoleto(){

            $pdo = $this->_model->getPDO();
            $sql = "SELECT condicao FROM tbl_condicao WHERE fabrica = {$this->_fabrica} AND lower(descricao) = 'boleto'";
            $query = $pdo->query($sql);
            $res = $query->fetchAll(\PDO::FETCH_ASSOC);

            return $res[0]['condicao'];
            }

            public function getTipoPedidoOrcamento(){

            $pdo = $this->_model->getPDO();
            $sql = "SELECT tipo_pedido FROM tbl_tipo_pedido WHERE fabrica = {$this->_fabrica} AND lower(codigo) = 'orc'";
            $query = $pdo->query($sql);
            $res = $query->fetchAll(\PDO::FETCH_ASSOC);

            return $res[0]['tipo_pedido'];
        }


    public function getTabelaVenda(){

        $pdo = $this->_model->getPDO();
        $sql = "SELECT tabela FROM tbl_tabela WHERE fabrica = {$this->_fabrica} AND tabela_garantia IS NOT TRUE AND lower(descricao) = 'venda' AND ativa IS TRUE";
        $query = $pdo->query($sql);
        $res = $query->fetchAll(\PDO::FETCH_ASSOC);

        return $res[0]['tabela'];
    }

    public function finaliza($con, $troca_produto_api = false, $login_admin = null, $origem = null)
    {
        if (empty($this->_os)) {
            throw new \Exception("Ordem de Serviço não informada");
        }
        
        parent::finaliza($con, $troca_produto_api, $login_admin, $origem);
    }

    public function calculaOs(){
        parent::calculaOs();
    }

    public function verificaOsSemPeca($con, $login_fabrica, $os) {

        $sql = "SELECT os
                FROM   tbl_os_produto
                JOIN   tbl_os_item USING(os_produto)
                WHERE  tbl_os_produto.os = {$os}";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) == 0) {
            return true;
        }
        
        return false;

    }

    public function verificaOsServicoAjuste($con, $login_fabrica, $os) {
        $sql = "SELECT tbl_os_produto.os, tbl_os_item.servico_realizado
                FROM   tbl_os_produto
                JOIN   tbl_os_item USING(os_produto)
                WHERE  tbl_os_produto.os = {$os}";
        $res = pg_query($con, $sql);
        $total_peca_ajuste = [];
        $total_peca = [];

        foreach (pg_fetch_all($res) as $key => $row) {

            $servico_realizado     = $row["servico_realizado"];

            $sqlx = "SELECT descricao
                    FROM   tbl_servico_realizado
                    WHERE  servico_realizado = {$servico_realizado}";
            $resx = pg_query($con, $sqlx);
            $total_peca[] = true;

            $descricao = pg_fetch_result($resx, 0, "descricao");
            if ($descricao == "Ajuste (não gera pedido)") {
                $total_peca_ajuste[] = true;
            } 
        }
        if (count($total_peca_ajuste) == count($total_peca)) {
            return true;
        }

        return false;

    }

    public function insereAuditoriaDeFabrica($con, $os) {

        $sqlStatusAud = "SELECT auditoria_status 
                           FROM tbl_auditoria_status 
                          WHERE fabricante = 't'";
        $resStatusAud = pg_query($con, $sqlStatusAud);

        $auditoria_status = pg_fetch_result($resStatusAud, 0, "auditoria_status");

        $sqlAud = "SELECT tbl_auditoria_os.os,
                          tbl_auditoria_os.auditoria_os,
                          tbl_auditoria_os.liberada,
                          tbl_auditoria_os.reprovada
                     FROM tbl_auditoria_os
                     JOIN tbl_os ON tbl_os.os = tbl_auditoria_os.os AND tbl_os.fabrica = {$this->_fabrica}
                    WHERE tbl_auditoria_os.os = {$os}
                      AND tbl_auditoria_os.auditoria_status = {$auditoria_status}
                      AND tbl_auditoria_os.observacao ILIKE '%Auditoria de F%'";
        $resAud = pg_query($con, $sqlAud);
        if (pg_num_rows($resAud) == 0) {
            $sqlInsertAud = "INSERT INTO tbl_auditoria_os 
                                                        (
                                                            os,
                                                            auditoria_status,
                                                            observacao
                                                        ) VALUES (
                                                            {$os},
                                                            $auditoria_status,
                                                            'Auditoria de Fábrica: Os fechada sem peça'
                                                        )";
                        $resInsertAud = pg_query($con, $sqlInsertAud);

            if (strlen(pg_last_error()) > 0) {
                throw new Exception("Erro ao lançar ordem de serviço");
            }
        }
    }
}
