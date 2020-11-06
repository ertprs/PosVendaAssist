<?php

namespace Posvenda\Model;

class Produto extends AbstractModel
{
    private $_produto;

    public function __construct($produto = null)
    {
        if (!empty($produto)) {
            $this->_produto = $produto;
        }

        parent::__construct('tbl_produto');
    }

    public function setProduto($produto)
    {
        $this->_produto = $produto;

        return $this;
    }

    public function getProdutoByRef($referencia, $fabrica)
    {
        $this->select()
                ->setCampos(array('tbl_produto.*', 'tbl_familia.descricao as familia_descricao'))
                ->addJoin(array(
                    "tbl_familia" => "ON tbl_familia.familia = tbl_produto.familia"
                 ))
                ->addWhere("referencia = '{$referencia}'")
                ->addWhere("fabrica_i = {$fabrica}");

        $this->prepare()->execute();
        if ($this->getPDOStatement()->rowCount() == 0) {
            return false;
        } else {
            $res = $this->getPDOStatement()->fetch();
            return $res;
        }
    }

    public function getMaoDeObra()
    {
        $this->select()
             ->setCampos(array('mao_de_obra'))
             ->addWhere(array('produto' => $this->_produto));

        $this->prepare()->execute();

    }
}
