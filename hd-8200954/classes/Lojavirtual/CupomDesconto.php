<?php
namespace Lojavirtual;
use Lojavirtual\Controller;

class CupomDesconto extends Controller {

    public function __construct() {
        parent::__construct();
    }

    /*
    *   Retorna um ou todos
    */
    public function get($loja_b2b_cupom_desconto = null) {

        $cond = "";
        if (strlen($this->_loja) == 0) {
            return array("erro" => true, "msn" => traduz("loja.nao.encontrada"));
        }

        if ($loja_b2b_cupom_desconto > 0) {
            $cond = " AND loja_b2b_cupom_desconto={$loja_b2b_cupom_desconto}";
        }

        $sql = "SELECT *
                     FROM tbl_loja_b2b_cupom_desconto
                    WHERE loja_b2b = {$this->_loja}
                      AND status IS TRUE
                          $cond";
        $res = pg_query($this->_con, $sql);

        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" =>  traduz(["cupom","nao.encontrada"]));
        }

        if ($loja_b2b_cupom_desconto > 0) {
            return pg_fetch_assoc($res);
        }
        return pg_fetch_all($res);
    }



    /*
    *   cadastra
    */
    public function save($dados = array()) {

        if (empty($dados)) {
            return array("erro" => true, "msn" => traduz("dados.não.enviado"));
        }

        $sql = "INSERT INTO tbl_loja_b2b_cupom_desconto (
                                                descricao,
                                                codigo_cupom,
                                                data_validade,
                                                desconto,
                                                qtde_cupom,
                                                limite_cupom,
                                                status,
                                                loja_b2b
                                            ) VALUES (
                                                '".$dados["descricao"]."',
                                                '".$dados["codigo_cupom"]."',
                                                '".$dados["data_validade"]."',
                                                '".$dados["desconto"]."',
                                                '".$dados["qtde_cupom"]."',
                                                '".$dados["limite_cupom"]."',
                                                't',
                                            ".$this->_loja."
                                        );";
        $res = pg_query($this->_con, $sql);

        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz(["erro.ao.cadastrar", "cupom"]));
        }

        return array("sucesso" => true);
    }

    /*
    *   altera
    */
    public function update($dados = array()) {

        if (empty($dados)) {
            return array("erro" => true, "msn" => traduz("dados.não.enviado"));
        }

        $sql = "UPDATE tbl_loja_b2b_cupom_desconto  
                   SET
                      descricao='".$dados["descricao"]."', 
                      codigo_cupom='".$dados["codigo_cupom"]."', 
                      data_validade='".$dados["data_validade"]."', 
                      desconto='".$dados["desconto"]."', 
                      qtde_cupom='".$dados["qtde_cupom"]."', 
                      limite_cupom='".$dados["limite_cupom"]."'
                WHERE loja_b2b=".$this->_loja." 
                  AND loja_b2b_cupom_desconto=".$dados["loja_b2b_cupom_desconto"];
        $res = pg_query($this->_con, $sql);

        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz(["erro.ao.atualizar", "cupom"]));
        }

        return array("loja_b2b_cupom_desconto" => $dados["loja_b2b_cupom_desconto"], "sucesso" => true);
    }


    /*
    *   Deleta
    */
    public function delete($loja_b2b_cupom_desconto) {
        if (empty($loja_b2b_cupom_desconto)) {
            return array("erro" => true, "msn" => traduz("dados.não.enviado"));
        }

        $sql = "UPDATE tbl_loja_b2b_cupom_desconto  
                   SET status='f'
                 WHERE loja_b2b=".$this->_loja." 
                   AND loja_b2b_cupom_desconto=".$loja_b2b_cupom_desconto;
        $res = pg_query($this->_con, $sql);

        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz(["erro.ao.remover", "cupom"]));
        }

        return array("sucesso" => true);
    }
}