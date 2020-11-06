<?php

namespace Posvenda\Fabricas\_175;

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

        $sql = "SELECT
                    tbl_tipo_atendimento.tipo_atendimento
                FROM tbl_os
                JOIN tbl_tipo_atendimento USING(tipo_atendimento)
                WHERE tbl_os.os = {$this->_os}
                AND tbl_tipo_atendimento.fora_garantia IS NOT TRUE
                AND COALESCE(tbl_os.capacidade) > 0 ";
        $res = pg_query($con, $sql);
        
        if (pg_num_rows($res) > 0){
            $sql_tdocs = "SELECT json_field('typeId',obs) AS typeId 
                                FROM tbl_tdocs 
                                WHERE tbl_tdocs.fabrica = {$this->_fabrica}
                                AND tbl_tdocs.referencia_id = {$this->_os} ";
            $res_tdocs = pg_query($con,$sql_tdocs); 
            if (pg_num_rows($res_tdocs) > 0){
                $typeId = pg_fetch_all_columns($res_tdocs);
                if (!in_array('display', $typeId)){
                    throw new \Exception("{$this->_sua_os} - É obrigatório um anexo do display do produto");
                }
            }else{
                throw new \Exception("{$this->_sua_os} - É obrigatório um anexo do display do produto");
            }
        }
        parent::finaliza($con, $troca_produto_api, $login_admin, $origem);
    }
}
