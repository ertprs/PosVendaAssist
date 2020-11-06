<?php

namespace Posvenda\Model\Sql;

/**
 * Classe para geração de UPDATEs
 *
 * XXX: Esta classe apenas monta a query, *não* trata 
 *       os dados que nela serão executados
 */
class Update implements InterfaceSql
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
     * @var array
     */
    private $_conds = array();

    /**
     * @var string
     */
    private $_query;

    public function __construct($tabela = null, $campos = array(), $cond = array())
    {
        if (!empty($tabela)) {
            $this->_tabela = $tabela;
        }

        if (!empty($campos)) {
            foreach ($campos as $k => $ca) {
                $this->addCampo(array("{$k}" => "{$ca}"));
            }
        }

        if (!empty($cond)) {
            foreach ($cond as $co) {
                $this->addCond($co);
            }
        }
    }

    /**
     * Set tabela
     *
     * @param string $tabela
     * @return Update
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

    /**
     * Adiciona um campo.
     *
     * LEMBRANDO: a classe não trata os dados
     *
     * @param array $campo array('campo' => 'valor')
     *
     * @return Update
     */
    public function addCampo($campo) {
        if (!is_array($campo)) {
            return false;
        }

        $key = key($campo);
        $val = $campo["{$key}"];

        array_push($this->_campos, $key . ' = ' . $val);

        return $this;
    }

    public function setCampos($campo) {
        if (!is_array($campo)) {
            return false;
        }

        $key = key($campo);
        $val = $campo["{$key}"];

        array_push($this->_campos, $key . ' = ' . $val);

        return $this;
    }

    public function getCampos(){
        return $this->_campos;
    }


    /**
     * Adiciona uma condição.
     *
     * @param string $cond
     * @return Update
     */
    public function addCond($cond) {
        array_push($this->_conds, $cond);

        return $this;
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
     * @return Update
     */
    public function prepare(){
        $query = 'UPDATE ' . $this->_tabela . ' SET ';
        $query.= implode(', ', $this->_campos);
        $query.= ' ' . implode(' ', $this->_conds);

        $this->_query = $query;

        return $this;
    }
}
