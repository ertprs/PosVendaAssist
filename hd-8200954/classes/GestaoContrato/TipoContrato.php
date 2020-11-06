<?php
namespace GestaoContrato;

class TipoContrato extends Controller {

    public function __construct($login_fabrica, $con) {
        parent::__construct($login_fabrica, $con);
    }

    public function get($tipo_contrato = null) {

        $cond    = "";

        if ($tipo_contrato > 0) {
            $cond = " AND tipo_contrato = {$tipo_contrato}";
        }

        $sql = "SELECT * FROM tbl_tipo_contrato WHERE ativo IS TRUE $cond";
        $res = pg_query($this->_con, $sql);
        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz("Nenhum registro foi encontrado"));
        }
        if ($tipo_contrato > 0) {
            return pg_fetch_assoc($res);
        }
        return pg_fetch_all($res);

    }

    public function add($dados = []) {

        $existe = false;

        if (empty($dados)) {
            return array("erro" => true, "msn" => traduz("Dados não enviado"));
        }

        $existe = $this->checaDuplicidade($dados["codigo"],$dados["descricao"]);
        if ($existe) {
            return array("erro" => true, "msn" => traduz("Já existe um Tipo de Contrato com esse Código e Descrição"));
        }
      
        $sql = "INSERT INTO tbl_tipo_contrato (
                                        fabrica,
                                        codigo, 
                                        descricao, 
                                        mao_de_obra, 
                                        pecas, 
                                        consumiveis, 
                                        sla, 
                                        ativo
                                    ) VALUES (
                                        ".$this->_fabrica.",
                                        '".$dados["codigo"]."',
                                        '".$dados["descricao"]."',
                                        '".$dados["mao_de_obra"]."',
                                        '".$dados["pecas"]."',
                                        '".$dados["consumiveis"]."',
                                        '".$dados["sla"]."',
                                        '".$dados["ativo"]."'
                                    ) RETURNING tipo_contrato";
        $res = pg_query($this->_con, $sql);

        if (pg_last_error()) {
            return array("erro" => true, "msn" => traduz("Erro ao gravar"));
        }

        return array("sucesso" => true, "tipo_contrato" => pg_fetch_result($res, 0, "tipo_contrato"));
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