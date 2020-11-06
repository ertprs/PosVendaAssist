<?php
namespace Lojavirtual;
use Lojavirtual\Controller;

class LojaCliente extends Controller {
    public $loja;
    public function __construct() {
        parent::__construct();
    }

    /*
    *   Retorna uma loja cadastradas
    */
    public function get($loja_b2b_cliente = null, $posto = null) {

        $cond = "";
        if (strlen($this->_loja) == 0) {
            return array("erro" => true, "msn" => traduz("loja.nao.encontrada"));
        }

        if (strlen($loja_b2b_cliente) > 0) {
            $cond .= " AND tbl_loja_b2b_cliente.loja_b2b_cliente={$loja_b2b_cliente}";
        }

        if (strlen($posto) > 0) {
            $cond .= " AND tbl_loja_b2b_cliente.posto={$posto}";
        }

        $sql = "SELECT tbl_loja_b2b_cliente.*,
                       tbl_posto.nome AS nome_posto,
                       tbl_posto_fabrica.codigo_posto,
                       tbl_posto_fabrica.contato_endereco,
                       tbl_posto_fabrica.contato_numero,
                       tbl_posto_fabrica.contato_complemento,
                       tbl_posto_fabrica.contato_bairro,
                       tbl_posto_fabrica.contato_cep,
                       tbl_posto_fabrica.contato_cidade,
                       tbl_posto_fabrica.contato_estado
                  FROM tbl_loja_b2b_cliente 
                  JOIN tbl_loja_b2b USING(loja_b2b)
             LEFT JOIN tbl_posto_fabrica USING(fabrica, posto)
             LEFT JOIN tbl_posto USING(posto)
                 WHERE tbl_loja_b2b_cliente.loja_b2b = {$this->_loja} 
                 $cond";
        $res = pg_query($this->_con, $sql);

        if (strlen(pg_last_error($this->_con)) >0 ) {
            return array("erro" => true, "msn" => traduz(["erro.ao.buscar", "cliente"]));
        }

        if (pg_num_rows($res) > 0) {
            return pg_fetch_array($res);
        } 

        return array();
    }

    /*
    *   add
    */
    public function savePosto($posto = null) {

        if (empty($posto)) {
            return array("erro" => true, "msn" => traduz("dados.não.enviado"));
        }

        if (strlen($this->_loja) == 0) {
            return array("erro" => true, "msn" => traduz("loja.nao.encontrada"));
        }

        $sql = "INSERT INTO tbl_loja_b2b_cliente (
                                        posto, 
                                        loja_b2b
                                    ) VALUES (
                                        ".$posto.",
                                        ".$this->_loja."
                                    ) RETURNING loja_b2b_cliente;";

        $res = pg_query($this->_con, $sql);
        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz(["erro.ao.cadastrar", "cliente"]));
        }

        $loja_b2b_cliente = pg_fetch_result($res, 0, 0);
        return array("loja_b2b_cliente" => $loja_b2b_cliente, "sucesso" => true);
    }


    /*
    *   Atualiza layout da loja
    */
    public function atualizaLayout($layout, $loja_b2b) {

        if (empty($layout) || !$loja_b2b) {
            return array("erro" => true, "msn" => traduz("dados.não.enviado"));
        }

        $sql = "UPDATE tbl_loja_b2b SET layout='$layout' WHERE fabrica={$this->_fabrica} AND loja_b2b={$loja_b2b}";
        $res = pg_query($this->_con, $sql);

        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz("loja.nao.encontrada"));
        }

        return array("sucesso" => true);
    }

    /*
    *   Verifica se a fábrica já tem uma loja criada
    */
    public function verificaLojaExistente($fabrica) {

        if (strlen($fabrica) == 0) {
            return false;
        }

        $sql = "SELECT loja_b2b FROM tbl_loja_b2b WHERE fabrica={$fabrica}";
        $res = pg_query($this->_con, $sql);

        return (pg_num_rows($res) > 0) ? true : false;
    }

    /*
    *   Retorna fabrica
    */
    private function getFabrica($fabrica) {

        if (strlen($fabrica) == 0) {
            return array("erro" => true, "msn" => traduz("fabrica.nao.encontrada"));
        }

        $sql = "SELECT fabrica, nome
                  FROM tbl_fabrica
                 WHERE ativo_fabrica  
                   AND fabrica={$fabrica}
              ORDER BY nome";
        $res = pg_query($this->_con, $sql);

        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz(["erro.ao.buscar", "fábrica"]));
        }
        return pg_fetch_assoc($res);
    }

    /*
    *   Retorna posto
    */
    private function getPosto($posto) {

        if (strlen($fabrica) == 0) {
            return array("erro" => true, "msn" => traduz("fabrica.nao.encontrada"));
        }

        $sql = "SELECT fabrica, nome
                  FROM tbl_fabrica
                 WHERE ativo_fabrica  
                   AND fabrica={$fabrica}
              ORDER BY nome";
        $res = pg_query($this->_con, $sql);

        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz(["erro.ao.buscar", "fábrica"]));
        }
        return pg_fetch_assoc($res);
    }


}