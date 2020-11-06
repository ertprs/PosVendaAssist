<?php
 
namespace Posvenda\Model\Sql;

/**
 * Classe para geração de INSERTs
 *
 * XXX: Esta classe apenas monta a query, *não* trata 
 *       os dados que nela serão executados
 */
class Insert
{
    /**
     * @var array
     */
    private $_campos = array();

    /**
     * @var string
     */
    private $_tabela;

    /**
     * @var string
     */
    private $_query;

    public function __construct($tabela = null, $campos = array())
    {
        if (!empty($tabela)) {
            $this->_tabela = $tabela;
        }

        if (is_array($campos) && count($campos) > 0) {
            foreach ($campos as $key => $value) {
                $this->_campos[$key] = $value;
            }
        }
    }

    /**
     * Set tabela
     *
     * @param string $tabela
     * @return Insert
     */
    public function setTabela($tabela) {
        $this->_tabela = $tabela;

        return $this;
    }

    /**
     * Get tabela
     *
     * @return string
     */
    public function getTabela()
    {
        return $this->_tabela;
    }

    public function setCampos($campos) {
        if (!is_array($campos) || !count($campos)) {
            return false;
        }

        $this->_campos = array();

        foreach ($campos as $key => $value) {
            $this->_campos[$key] = $value;
        }

        return $this;
    }

    public function getCampos(){
        return $this->_campos;
    }

    /**
     * Get query
     *
     * @return string
     */
    public function getQuery(){
        return $this->_query;
    }

    /**
     * Monta a query
     *
     * @return Insert
     */
    public function prepare(){
        $query = "
            INSERT INTO {$this->_tabela}
            (".implode(",", array_keys($this->_campos)).")
            VALUES
            (".implode(",", $this->_campos).")
        ";

        $this->_query = $query;

        return $this;
    }

}
