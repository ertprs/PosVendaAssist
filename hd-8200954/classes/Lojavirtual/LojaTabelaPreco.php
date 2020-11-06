<?php
namespace Lojavirtual;
use Lojavirtual\Controller;
use Lojavirtual\LojaCliente;

class LojaTabelaPreco extends Controller {

    private $produto;
    private $_lojaCliente;

    public function __construct() {
        parent::__construct();
        $this->_lojaCliente = new LojaCliente();
    }
    /*
    *   Atualiza tabela de preço da loja
    */
    public function add($REFERENCIA, $PRECO, $LOJA_TABELA) {
        $dadosTabelaPreco = array();
        $dadosProdutoLoja = array();
        if (strlen($this->_loja) == 0) {
            return array("erro" => true, "msn" => traduz("loja.nao.encontrada"));
        }

        if (strlen($LOJA_TABELA) == 0) {
            return array("erro" => true, "msn" => traduz("tabela.não.encontrada"));
        }

        if (strlen($REFERENCIA) == 0) {
            return array("erro" => true, "msn" => traduz("peca.nao.encontrada"));
        }

        $dadosProdutoLoja = $this->getPecaLojaByRef($REFERENCIA);

        if (empty($dadosProdutoLoja)) {
            return array("erro" => true, "msn" => traduz("produto.nao.encontrado"));
        }

        $dadosTabelaPreco = $this->getItem($dadosProdutoLoja['codigo_peca'], $LOJA_TABELA);
        if (!empty($dadosTabelaPreco) ) {
            $sql  = "UPDATE tbl_loja_b2b_tabela_item SET 
                                                    preco=".$PRECO.",
                                                    admin=".$this->_login_admin."
                                                 WHERE loja_b2b_tabela=".$LOJA_TABELA." AND loja_b2b_peca = ".$dadosTabelaPreco["loja_b2b_peca"];

            $res = pg_query($this->_con, $sql);

            if (pg_last_error($this->_con)) {
                return array("erro" => true, "msn" => traduz(["erro.ao.atualizar", "tabela"]));
            }
            return array("sucesso" => "update");

        } else {

            $sql  = "INSERT INTO tbl_loja_b2b_tabela_item (
                                                    loja_b2b_peca,
                                                    preco,
                                                    loja_b2b_tabela,
                                                    admin
                                                ) VALUES (
                                                    ".$dadosProdutoLoja["codigo_peca"].",
                                                    ".$PRECO.",
                                                    ".$LOJA_TABELA.",
                                                    ".$this->_login_admin."
                                                )";

            $res = pg_query($this->_con, $sql);

            if (pg_last_error($this->_con)) {
                return array("erro" => true, "msn" => traduz(["erro.ao.cadastrar", "tabela"]));
            }

            return array("sucesso" => "insert");
        }
    }

    /*
    *   retorna item da tabela de preco
    */
    public function get($loja_b2b_peca = null) {
        $cond = "";
        if (strlen($this->_loja) == 0) {
            return array("erro" => true, "msn" => traduz("loja.nao.encontrada"));
        }

        if (strlen($loja_b2b_peca) > 0) {
            $cond = " AND loja_b2b_peca = ".$loja_b2b_peca;
        }

        $sql  = "SELECT * FROM tbl_loja_b2b_tabela 
                         WHERE loja_b2b=".$this->_loja." {$cond}";
        $res = pg_query($this->_con, $sql);

        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz(["erro.ao.buscar", "tabela"]));
        }
        if (strlen($loja_b2b_peca) > 0) {
            return pg_fetch_assoc($res);
        } else {
            return pg_fetch_all($res);
        }
    }

    public function getTabelaByCliente($loja_b2b_cliente, $posto) {

        if (strlen($this->_loja) == 0) {
            return array("erro" => true, "msn" => traduz("loja.nao.encontrada"));
        }

        if (strlen($posto) > 0) {
            $dadosCliente = $this->_lojaCliente->get(null, $posto);
            $loja_b2b_cliente = $dadosCliente["loja_b2b_cliente"];
        }

        $sql  = "SELECT * FROM tbl_loja_b2b_tabela_cliente
                         WHERE loja_b2b_cliente = {$loja_b2b_cliente}";
        $res = pg_query($this->_con, $sql);

        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz("tabela.não.encontrada"));
        }
        
        return pg_fetch_assoc($res);
    }

    /*
    *   retorna item da tabela de preco
    */
    public function getItem($loja_b2b_peca, $loja_b2b_tabela) {

        if (strlen($this->_loja) == 0) {
            return array("erro" => true, "msn" => traduz("loja.nao.encontrada"));
        }

        $sql  = "SELECT * FROM tbl_loja_b2b_tabela_item 
                         WHERE loja_b2b_peca = {$loja_b2b_peca} 
                           AND loja_b2b_tabela = {$loja_b2b_tabela}";

        $res = pg_query($this->_con, $sql);

        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz("produto.nao.encontrado"));
        }
        
        return pg_fetch_assoc($res);
    }


    public function getPrecoProduto($loja_b2b_peca) {

        if (strlen($this->_loja) == 0) {
            return array("erro" => true, "msn" => traduz("loja.nao.encontrada"));
        }

        $sql = "SELECT tbl_loja_b2b_tabela.descricao,
                       tbl_loja_b2b_tabela_item.preco
                  FROM tbl_loja_b2b_tabela_item
                  JOIN tbl_loja_b2b_tabela USING(loja_b2b_tabela)
                 WHERE tbl_loja_b2b_tabela.loja_b2b = {$this->_loja}
                   AND tbl_loja_b2b_tabela_item.loja_b2b_peca = {$loja_b2b_peca}";
        $res = pg_query($this->_con, $sql);

        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz("produto.nao.encontrado"));
        }

        if (pg_num_rows($res) > 0) {
            return pg_fetch_ALL($res);
        } else {
            return array();
        }

    }


    /*
    *   Retorna todos
    */
    public function getPecaLojaByRef($referencia_peca) {

        if (strlen($this->_loja) == 0) {
            return array("erro" => true, "msn" => traduz("loja.nao.encontrada"));
        }

        $sql = "SELECT tbl_loja_b2b_peca.loja_b2b_peca AS codigo_peca
                  FROM tbl_loja_b2b_peca
                  JOIN tbl_peca ON tbl_loja_b2b_peca.peca=tbl_peca.peca AND tbl_peca.fabrica={$this->_fabrica}
             LEFT JOIN tbl_categoria ON tbl_categoria.categoria=tbl_loja_b2b_peca.categoria AND tbl_categoria.fabrica={$this->_fabrica}
                 WHERE tbl_loja_b2b_peca.loja_b2b = {$this->_loja}
				AND tbl_loja_b2b_peca.ativo
                 AND tbl_peca.referencia = '{$referencia_peca}'";
        $res = pg_query($this->_con, $sql);

        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz("produto.nao.encontrado"));
        }

        if (pg_num_rows($res) > 0) {
            return pg_fetch_array($res);
        } else {
            return array();
        }

    }

    public function addTabela($descricao, $admin) {

        if (strlen($this->_loja) == 0) {
            return array("erro" => true, "msn" => traduz("loja.nao.encontrada"));
        }

        $dadosProdutoLoja = $this->validaExisteTabela($descricao);

        if ($dadosProdutoLoja) {
            return array("erro" => true, "msn" => traduz("já.existe.uma.tabela.cadastrada.com.esse.nome"));
        }

        $sql  = "INSERT INTO tbl_loja_b2b_tabela (
                                                loja_b2b,
                                                descricao,
                                                admin
                                            ) VALUES (
                                                ".$this->_loja.",
                                                '".$descricao."',
                                                ".$admin."
                                            )";

        $res = pg_query($this->_con, $sql);

        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz(["erro.ao.cadastrar", "tabela"]));
        }

        return array("sucesso" => true);
    }

    public function validaExisteTabela($descricao) {

        if (strlen($this->_loja) == 0) {
            return array("erro" => true, "msn" => traduz("loja.nao.encontrada"));
        }

        $sql = "SELECT loja_b2b_tabela 
                    FROM tbl_loja_b2b_tabela 
                   WHERE loja_b2b = {$this->_loja} 
                     AND descricao = '{$descricao}'";
        $res = pg_query($this->_con, $sql);

        if (pg_last_error($this->_con)) {
            return true;
        }
        if (pg_num_rows($res) > 0) {
            return true;
        } else {
           return false; 
        }
    }


    public function relacionaClienteTabela($loja_b2b_tabela, $posto, $admin) {

        if (strlen($this->_loja) == 0) {
            return array("erro" => true, "msn" => traduz("loja.nao.encontrada"));
        }

        if (strlen($loja_b2b_tabela) == 0) {
            return array("erro" => true, "msn" => traduz("tabela.não.encontrada"));
        }

        if (strlen($posto) == 0) {
            return array("erro" => true, "msn" => traduz("posto.nao.encontrado"));
        }

        $tabelaCliente  = $this->getTabelaByCliente(null, $posto);
        if (!empty($tabelaCliente["loja_b2b_tabela"])) {

            $sql  = "UPDATE tbl_loja_b2b_tabela_cliente 
                        SET loja_b2b_tabela=".$loja_b2b_tabela.",
                            admin=".$admin."
                      WHERE loja_b2b_cliente=".$tabelaCliente["loja_b2b_cliente"]." 
                        AND loja_b2b_tabela_cliente=".$tabelaCliente["loja_b2b_tabela_cliente"];

            $res = pg_query($this->_con, $sql);
            if (pg_last_error($this->_con)) {
                return array("erro" => true, "msn" => traduz(["erro.ao.atualizar", "tabela"]));
            }
            return array("sucesso" => true);
            
        } else {

            $dadosCliente = $this->_lojaCliente->get(null, $posto);
            if (empty($dadosCliente)) {
                $dadosCliente = $this->_lojaCliente->savePosto($posto);
            }

            if (!empty($dadosCliente)) {
                $sql  = "INSERT INTO tbl_loja_b2b_tabela_cliente (
                                                        loja_b2b_tabela,
                                                        loja_b2b_cliente,
                                                        admin
                                                    ) VALUES (
                                                        ".$loja_b2b_tabela.",
                                                        ".$dadosCliente["loja_b2b_cliente"].",
                                                        ".$admin."
                                                    )";

                $res = pg_query($this->_con, $sql);

                if (pg_last_error($this->_con)) {
                    return array("erro" => true, "msn" => traduz(["erro.ao.cadastrar", "tabela"]));
                }

                return array("sucesso" => true);
            } else {
                return array("erro" => true, "msn" => traduz("posto.nao.encontrado"));
            }

        }

    }
}
