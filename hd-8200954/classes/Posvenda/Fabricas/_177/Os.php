<?php

namespace Posvenda\Fabricas\_177;

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
                AND tbl_tipo_atendimento.fora_garantia IS NOT TRUE ";
        $res = pg_query($con, $sql);
        
        if (pg_num_rows($res) > 0){
            $sql_tdocs = "SELECT json_field('typeId',obs) AS typeId 
                                FROM tbl_tdocs 
                                WHERE tbl_tdocs.fabrica = {$this->_fabrica}
                                AND tbl_tdocs.situacao = 'ativo'
                                AND tbl_tdocs.referencia_id = {$this->_os}";
            $res_tdocs = pg_query($con,$sql_tdocs);
            
            if (pg_num_rows($res_tdocs) > 0){
                $typeId = pg_fetch_all_columns($res_tdocs);

                if (!in_array('foto_frontal', $typeId) OR !in_array('foto_traseira', $typeId) OR !in_array('notafiscal', $typeId)){
                    throw new \Exception("{$this->_sua_os} - Obrigatório os seguintes anexos: foto frontal do produto, foto da traseira do produto e nota fiscal do produto");
                }
            }else{
                throw new \Exception("{$this->_sua_os} - Obrigatório os seguintes anexos: foto frontal do produto, foto da traseira do produto e nota fiscal do produto");
            }
        }
        parent::finaliza($con, $troca_produto_api, $login_admin, $origem);
    }
}
