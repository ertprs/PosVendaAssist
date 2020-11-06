<?php
namespace GestaoContrato;

class ContratoStatusMovimento extends Controller {

    public function __construct($login_fabrica, $con) {
        parent::__construct($login_fabrica, $con);
    }

    public function get($contrato_status = null, $descricao = null) {

        $cond    = "";

        if (strlen($contrato_status) > 0) {
            $cond = " AND contrato_status = {$contrato_status}";
        }


        if (strlen($descricao) > 0) {
            $cond .= " AND descricao = '".trim($descricao)."'";
        }

        $sql = "SELECT * FROM tbl_contrato_status WHERE 1=1 $cond";
        $res = pg_query($this->_con, $sql);
        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz("Nenhum registro foi encontrado"));
        }
        if (strlen($contrato_status) > 0 || strlen($descricao) > 0) {
            return pg_fetch_assoc($res);
        }
        return pg_fetch_all($res);

    }

    public function getUltimoStatusByContrato($contrato) {


        $sql = "SELECT tbl_contrato_status.contrato_status,tbl_contrato_status.descricao
                       from tbl_contrato_status_movimento 
                       JOIN tbl_contrato_status ON  tbl_contrato_status.contrato_status = tbl_contrato_status_movimento.contrato_status
                       where tbl_contrato_status_movimento.contrato = {$contrato} ORDER BY tbl_contrato_status_movimento.data desc LIMIT 1";
        $res = pg_query($this->_con, $sql);
        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz("Nenhum registro foi encontrado"));
        }
        return pg_fetch_assoc($res);

    }

    public function add($contrato, $contrato_status) {

        $existe = false;

        if (empty($contrato_status)) {
            return array("erro" => true, "msn" => traduz("Dados não enviado"));
        }


        if (empty($contrato)) {
            return array("erro" => true, "msn" => traduz("Dados não enviado"));
        }

       
      
        $sql = "INSERT INTO tbl_contrato_status_movimento (
                                        contrato_status,
                                        contrato
                                    ) VALUES (
                                        ".$contrato_status.",
                                        ".$contrato."
                                    )";
        $res = pg_query($this->_con, $sql);

        if (pg_last_error()) {
            return array("erro" => true, "msn" => traduz("Erro ao gravar"));
        }
        return array("sucesso" => true);
    }

    public function edit($tipo_contrato, $dados = []) {

        if (empty($tipo_contrato) || empty($dados)) {
            return array("erro" => true, "msn" => traduz("Dados não enviado"));
        }

        $sql = "UPDATE tbl_tipo_contrato 
                   SET codigo='".$dados["codigo"]."',
                       descricao='".$dados["descricao"]."',
                       mao_de_obra='".$dados["mao_de_obra"]."',
                       pecas='".$dados["pecas"]."',
                       consumiveis='".$dados["consumiveis"]."',
                       sla='".$dados["sla"]."',
                       ativo='".$dados["ativo"]."'
                 WHERE fabrica={$this->_fabrica} 
                   AND tipo_contrato={$tipo_contrato}";
        $res = pg_query($this->_con, $sql);

        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz("Erro ao gravar"));
        }

        return array("sucesso" => true);
    }

    public function checaDuplicidade($codigo, $descricao, $id =null) {

        if (!empty($id)) {
            $cond = " AND tipo_contrato <> {$id}";
        }

        $sql = "SELECT tipo_contrato 
                  FROM tbl_tipo_contrato 
                 WHERE fabrica={$this->_fabrica} 
                   AND codigo='{$codigo}' 
                   AND descricao='{$descricao}'";
        $res = pg_query($this->_con, $sql);

        return (pg_num_rows($res) > 0) ? true : false;

    }

}