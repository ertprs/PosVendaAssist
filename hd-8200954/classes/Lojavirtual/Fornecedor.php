<?php
namespace Lojavirtual;

use Lojavirtual\Controller;

class Fornecedor extends Controller {

    public function __construct() {
        parent::__construct();       
    }

    /*
    *   Retorna uma ou todas fornecedors
    */
    public function get($fornecedor = null) {

        $cond = "";
        if (strlen($this->_loja) == 0) {
            return array("erro" => true, "msn" => traduz("loja.nao.encontrada"));
        }

        if (!empty($fornecedor)) {
            $cond = "AND loja_b2b_fornecedor={$fornecedor}";
        }

        $sql = "SELECT nome,    
                        endereco,
                        numero,
                        bairro,
                        estado,
                        cep,
                        email,
                        celular,
                        fone,
                        cnpj,
                        cidade,
                        ativo,
                        loja_b2b_fornecedor
                     FROM tbl_loja_b2b_fornecedor
                    WHERE loja_b2b=".$this->_loja."
                          $cond";
        $res = pg_query($this->_con, $sql);

        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz(["erro.ao.buscar", "fornecedores"]));
        }

        if ($fornecedor > 0) {
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

        //$this->_auditor->retornaDadosSelect("SELECT fornecedor FROM tbl_fornecedor WHERE fornecedor=0");

        $sql = "INSERT INTO tbl_loja_b2b_fornecedor (
                                        loja_b2b, 
                                        nome,    
                                        endereco,
                                        numero,
                                        bairro,
                                        estado,
                                        cep,
                                        email,
                                        celular,
                                        fone,
                                        cnpj,
                                        cidade,
                                        ativo
                                    ) VALUES (
                                        ".$this->_loja.",
                                        '".$dados["nome"]."',
                                        '".$dados["endereco"]."',
                                        '".$dados["numero"]."',
                                        '".$dados["bairro"]."',
                                        '".$dados["estado"]."',
                                        '".$dados["cep"]."',
                                        '".$dados["email"]."',
                                        '".$dados["celular"]."',
                                        '".$dados["telefone"]."',
                                        '".$dados["cnpj"]."',
                                        '".$dados["cidade"]."',
                                        '".$dados['ativo']."'
                                    ) RETURNING loja_b2b_fornecedor;";
        $res = pg_query($this->_con, $sql);

        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz(["erro.ao.cadastrar", "fornecedores"]));
        }

        $fornecedor = pg_fetch_result($res, 0, loja_b2b_fornecedor);

        //$this->_auditor->retornaDadosTabela("tbl_fornecedor", array("fornecedor" => $fornecedor))->enviarLog("insert", "tbl_fornecedor",$this->_fabrica."*".$fornecedor);

        return array("sucesso" => true);
    }

    /*
    *   altera
    */
    public function update($dados = array()) {

        if (empty($dados)) {
            return array("erro" => true, "msn" => traduz("dados.não.enviado"));
        }

        $sql = "UPDATE tbl_loja_b2b_fornecedor  
                   SET  nome='".$dados["nome"]."',
                        endereco='".$dados["endereco"]."',
                        numero='".$dados["numero"]."',
                        bairro='".$dados["bairro"]."',
                        estado='".$dados["estado"]."',
                        cep='".$dados["cep"]."',
                        email='".$dados["email"]."',
                        celular='".$dados["celular"]."',
                        fone='".$dados["telefone"]."',
                        cnpj='".$dados["cnpj"]."',
                        cidade='".$dados["cidade"]."',
                        ativo='".$dados['ativo']."'
                 WHERE loja_b2b=".$this->_loja." 
                   AND loja_b2b_fornecedor=".$dados["b2b_fornecedor"];
        $res = pg_query($this->_con, $sql);

        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz(["erro.ao.atualizar", "fornecedors"]));
        }

        return array("fornecedor" => $dados["fornecedor"], "sucesso" => true);
    }

    /*
    *   Deleta
    */
    public function delete($fornecedor) {
        if (empty($fornecedor)) {
            return array("erro" => true, "msn" => traduz("dados.não.enviado"));
        }

        $sql = "UPDATE tbl_loja_b2b_fornecedor  
                   SET ativo='f'
                 WHERE loja_b2b=".$this->_loja." 
                   AND loja_b2b_fornecedor=".$fornecedor;
        $res = pg_query($this->_con, $sql);

        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz(["erro.ao.atualizar", "fornecedores"]));
        }

        return array("sucesso" => true);
    }

    public function getFornecedorProduto($peca) {

        if (empty($peca)) {
            return array("erro" => true, "msn" => traduz("dados.não.enviado"));
        }

        $sql = "SELECT loja_b2b_fornecedor 
                FROM tbl_loja_b2b_peca
                WHERE loja_b2b_peca = $peca";
        $res = pg_query($this->_con, $sql);

        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz("erro.ao.buscar.fornecedor"));
        }

        if (pg_num_rows($res) > 0) {
            return pg_fetch_result($res, 0, 'loja_b2b_fornecedor');
        } else {
            return false;
        }
    }

    public function getFornecedor($cnpj) {

        if (empty($cnpj)) {
            return array("erro" => true, "msn" => traduz("dados.não.enviado"));
        }

        $sql = "SELECT loja_b2b_fornecedor 
                FROM tbl_loja_b2b_fornecedor
                WHERE cnpj = '$cnpj'";
        $res = pg_query($this->_con, $sql);

        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz("erro.ao.buscar.fornecedor"));
        }

        if (pg_num_rows($res) > 0) {
            return pg_fetch_result($res, 0, 'loja_b2b_fornecedor');
        } else {
            return false;
        }
    }
}
