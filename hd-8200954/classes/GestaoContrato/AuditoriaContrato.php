<?php
namespace GestaoContrato;

class AuditoriaContrato extends Controller {

    public function __construct($login_fabrica, $con) {
        parent::__construct($login_fabrica, $con);
    }

    public function get($cond = "") {

        $sql = "SELECT tbl_contrato_auditoria.*,TO_CHAR(tbl_contrato_auditoria.data_input::DATE, 'dd/mm/YYYY') as data,
                       tbl_representante.nome AS nome_repre,
                       tbl_cliente_admin.nome,
                       tbl_cliente_admin.cidade,
                       tbl_cliente_admin.estado
                  FROM tbl_contrato_auditoria 
                  JOIN tbl_contrato ON tbl_contrato.contrato = tbl_contrato_auditoria.contrato AND tbl_contrato.fabrica = {$this->_fabrica}
                  LEFT JOIN tbl_cliente_admin ON tbl_cliente_admin.cliente_admin = tbl_contrato.cliente AND tbl_cliente_admin.fabrica = {$this->_fabrica}
                  LEFT JOIN tbl_representante ON  tbl_representante.representante = tbl_contrato.representante AND tbl_representante.fabrica = {$this->_fabrica}
                 WHERE 1 = 1 $cond ORDER BY tbl_contrato_auditoria.contrato DESC";
        $res = pg_query($this->_con, $sql);
        if (pg_last_error($this->_con) || empty(pg_fetch_all($res))) {
            return array("erro" => true, "msn" => traduz("Nenhum registro foi encontrado"));
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
                                        ".$dados["fabrica"].",
                                        '".$dados["codigo"]."',
                                        '".$dados["descricao"]."',
                                        '".$dados["mao_de_obra"]."',
                                        '".$dados["pecas"]."',
                                        '".$dados["consumiveis"]."',
                                        '".$dados["sla"]."',
                                        '".$dados["ativo"]."'
                                    )";
        $res = pg_query($this->_con, $sql);
        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz("Erro ao gravar"));
        }

        return array("sucesso" => true);
    }

    public function edit($tipo_contrato, $dados = []) {

        if (empty($tipo_contrato) || empty($dados)) {
            return array("erro" => true, "msn" => traduz("Dados não enviado"));
        }

        $sql = "UPDATE tbl_tipo_contrato 
                   SET codigo='$codigo',
                       descricao='$descricao',
                       mao_de_obra='$mao_de_obra',
                       pecas='$pecas',
                       consumiveis='$consumiveis',
                       sla='$sla',
                       ativo='$ativo'
                 WHERE fabrica={$this->_fabrica} 
                   AND tipo_contrato={$tipo_contrato}";
        $res = pg_query($this->_con, $sql);

        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz("Erro ao gravar"));
        }

        return array("sucesso" => true);
    }

    public function checaDuplicidade($codigo, $descricao) {

        $sql = "SELECT tipo_contrato 
                  FROM tbl_tipo_contrato 
                 WHERE fabrica={$this->_fabrica} 
                   AND codigo='{$codigo}' 
                   AND descricao='{$descricao}'";
        $res = pg_query($this->_con, $sql);

        return (pg_num_rows($res) > 0) ? true : false;

    }

}