<?php
namespace Lojavirtual;
use Lojavirtual\Controller;
use Lojavirtual\LojaCliente;

class AviseMe extends Controller {
    private  $lojaCliente;
    public function __construct() {
        parent::__construct();
        $this->lojaCliente = new LojaCliente();
    }

    /*
    *   Retorna todos avise-me cadastrados
    */
    public function get($avise_me = null) {

        $cond = "";
        if (strlen($this->_loja) == 0) {
            return array("erro" => true, "msn" => traduz("loja.nao.encontrada"));
        }

        if ($avise_me > 0) {
            $cond = "AND tbl_loja_b2b_avise_me.loja_b2b_avise_me={$avise_me}";
        }

        $sql = "SELECT 
                          tbl_loja_b2b_avise_me.loja_b2b_avise_me,
                          TO_CHAR(tbl_loja_b2b_avise_me.data_input,'DD/MM/YYYY') AS data,
                          TO_CHAR(tbl_loja_b2b_avise_me.avisado,'DD/MM/YYYY'),
                          tbl_peca.descricao                    AS nome_peca,
                          tbl_loja_b2b_peca.loja_b2b_peca       AS codigo_peca,
                          tbl_loja_b2b_cliente.loja_b2b_cliente AS codigo_cliente,
                          tbl_posto.nome                        AS nome_cliente,
                          tbl_posto_fabrica.contato_email       AS email_cliente
                     FROM tbl_loja_b2b_avise_me
                     JOIN tbl_loja_b2b_peca     ON tbl_loja_b2b_peca.loja_b2b_peca=tbl_loja_b2b_avise_me.loja_b2b_peca
                     JOIN tbl_peca              ON tbl_loja_b2b_peca.peca=tbl_peca.peca
                     JOIN tbl_loja_b2b_cliente  ON tbl_loja_b2b_avise_me.loja_b2b_cliente=tbl_loja_b2b_cliente.loja_b2b_cliente
                     JOIN tbl_posto_fabrica     ON tbl_loja_b2b_cliente.posto=tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica={$this->_fabrica} 
                     JOIN tbl_posto             ON tbl_posto.posto=tbl_posto_fabrica.posto
                    WHERE tbl_loja_b2b_avise_me.avisado IS NULL
                      AND tbl_loja_b2b_avise_me.loja_b2b = {$this->_loja}
                          $cond";
        $res = pg_query($this->_con, $sql);

        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz(["erro.ao.buscar", "avise.me"]));
        }
        if ($avise_me > 0) {
            return pg_fetch_assoc($res);
        }
        return pg_fetch_all($res);
    }

    /*
    *   Grava avise-me via loja
    */
    public function saveAviseMe($data) {

        if (strlen($this->_loja) == 0 || empty($data)) {
            return array("erro" => true, "msn" => traduz(["erro.ao.cadastrar", "avise.me"]));
        }

        $codigo_cliente_loja = $this->lojaCliente->get(null, $data["posto"]);

        if (empty($codigo_cliente_loja)) {
            return array("erro" => true, "msn" => traduz("posto.nao.encontrado"));
        }

        $sql = "INSERT INTO tbl_loja_b2b_avise_me 
                                            (
                                                loja_b2b_peca, 
                                                loja_b2b_cliente, 
                                                loja_b2b
                                            ) VALUES (
                                                ".$data["loja_b2b_peca"].",
                                                ".$codigo_cliente_loja["loja_b2b_cliente"].",
                                                ".$this->_loja."
                                            )";
        $res = pg_query($this->_con, $sql);
        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz(["erro.ao.cadastrar", "avise.me"]));
        }

        return array("sucesso" => true, "msn" => traduz("cadastrado.com.sucesso"));
    }


    public function updateAvisado($id) {

        if (strlen($this->_loja) == 0 || empty($id)) {
            return array("erro" => true, "msn" => traduz(["erro.ao.cadastrar", "avise.me"]));
        }

        $sql = "UPDATE tbl_loja_b2b_avise_me 
                   SET avisado='".date("Y-m-d H:i:s")."' 
                 WHERE loja_b2b_avise_me={$id}";
        $res = pg_query($this->_con, $sql);

        if (pg_last_error($this->_con)) {
            return false;
        }

        return true;
    }


}
