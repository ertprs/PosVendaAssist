<?php
namespace Lojavirtual;

use Lojavirtual\Controller;

class Categoria extends Controller {

    public function __construct() {
        parent::__construct();       
    }

    /*
    *   Retorna uma ou todas categorias
    */
    public function get($categoria = null) {

        $cond = "";
        if (strlen($this->_loja) == 0) {
            return array("erro" => true, "msn" => traduz("loja.nao.encontrada"));
        }

        if ($categoria > 0) {
            $cond = "AND categoria={$categoria}";
        }

        $sql = "SELECT categoria, descricao, ativo
                     FROM tbl_categoria
                    WHERE ativo IS TRUE
                      AND fabrica = {$this->_fabrica}
                          $cond";
        $res = pg_query($this->_con, $sql);

        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz(["erro.ao.buscar", "categorias"]));
        }

        if ($categoria > 0) {
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

        //$this->_auditor->retornaDadosSelect("SELECT categoria FROM tbl_categoria WHERE categoria=0");

        $sql = "INSERT INTO tbl_categoria (
                                        descricao, 
                                        fabrica,    
                                        ativo
                                    ) VALUES (
                                        '".$dados["descricao"]."',
                                        ".$this->_fabrica.",
                                        't'
                                    ) RETURNING categoria;";
        $res = pg_query($this->_con, $sql);

        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz(["erro.ao.cadastrar", "categorias"]));
        }

        $categoria = pg_fetch_result($res, 0, categoria);

        //$this->_auditor->retornaDadosTabela("tbl_categoria", array("categoria" => $categoria))->enviarLog("insert", "tbl_categoria",$this->_fabrica."*".$categoria);

        return array("sucesso" => true);
    }

    /*
    *   altera
    */
    public function update($dados = array()) {

        if (empty($dados)) {
            return array("erro" => true, "msn" => traduz("dados.não.enviado"));
        }

        $sql = "UPDATE tbl_categoria  
                   SET descricao='".$dados["descricao"]."'
                 WHERE fabrica=".$this->_fabrica." 
                   AND categoria=".$dados["categoria"];
        $res = pg_query($this->_con, $sql);

        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz(["erro.ao.atualizar", "categorias"]));
        }

        return array("categoria" => $dados["categoria"], "sucesso" => true);
    }

    /*
    *   Deleta
    */
    public function delete($categoria) {
        if (empty($categoria)) {
            return array("erro" => true, "msn" => traduz("dados.não.enviado"));
        }

        $sql = "SELECT loja_b2b_peca 
                  FROM tbl_loja_b2b_peca  
                 WHERE loja_b2b=".$this->_loja."
                   AND tbl_loja_b2b_peca.ativo IS TRUE 
                   AND categoria=".$categoria;
        $res = pg_query($this->_con, $sql);
        if (pg_num_rows($res) > 0) {
            return array("erro" => true, "msn" => traduz("não.é.possível.excluir.essa.categoria.pois.existe.produto.vinculado.a.ela"));
        }

        $sql = "UPDATE tbl_categoria  
                   SET ativo='f'
                 WHERE fabrica=".$this->_fabrica." 
                   AND categoria=".$categoria;
        $res = pg_query($this->_con, $sql);

        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz(["erro.ao.atualizar", "categorias"]));
        }

        return array("sucesso" => true);
    }

}
