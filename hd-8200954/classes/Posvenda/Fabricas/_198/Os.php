<?php

namespace Posvenda\Fabricas\_198;

use Posvenda\Os as OsPosvenda;

class Os extends OsPosvenda
{
    public function __construct($fabrica, $os = null, $conn = null)
    {
        parent::__construct($fabrica, $os, $conn);

        $this->_fabrica = $fabrica;
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
