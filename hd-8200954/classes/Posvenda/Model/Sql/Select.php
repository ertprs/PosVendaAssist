<?php

namespace Posvenda\Model\Sql;

/**
 * Classe para geração de SELECTs
 *
 * XXX: Esta classe apenas monta a query, *não* trata 
 *       os dados que nela serão executados
 */
class Select implements InterfaceSql
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
    private $_joins = array();

    /**
     * @var array
     */
    private $_conds = array();

    /**
     * @var string
     */
    private $_query;

    /**
     * @var array
     */
    private $_orderBy = array();

    /**
     * @var integer
     */
    private $_limit = null;

    /**
     * Construtor
     *
     * @param array  $campos array('campo1', 'campo2', 'campo3', 'campoN')
     *                      array('alias1' => 'campo1', 'alias2' => 'campo2', 'aliasN' => 'campoN')
     * @param string $tabela
     * @param array  $joins
     * @param array  $conds
     */
    public function __construct($campos = array(), $tabela = null, $joins = array(), $conds = array())
    {
        if (!empty($campos)) {
            $arr = array();
            foreach ($campos as $key => $val) {
                if (is_numeric($key)) {
                    $add = $val;
                } else {
                    $add = "$val AS $key";
                }

                $this->addCampo($add);
            }
        }

        if (!empty($tabela)) {
            $this->setTabela($tabela);
        }

        if (!empty($joins)) {
            foreach ($joins as $val) {
                $this->addJoin($val);
            }
        }

        if (!empty($conds)) {
            foreach ($conds as $val) {
                $this->addCond($val);
            }
        }
    }

    /**
     * Set tabela
     *
     * @param string $tabela
     * @return Select
     */
    public function setTabela($tabela)
    {
        $this->_tabela = $tabela;

        return $this;
    }

    /**
     * Adiciona um campo
     *
     * @param mixed $campo
     * @return Select
     */
    public function addCampo($campo)
    {
        if (is_array($campo)) {
            $key = key($campo);
            $val = $campo[$key];
            $campo = $val . ' AS ' . $key;
        }

        array_push($this->_campos, $campo);

        return $this;
    }

    /**
     * Adiciona um JOIN
     *
     * @param string $join
     * @return Select
     */
    public function addJoin($join)
    {
        array_push($this->_joins, $join);

        return $this;
    }


    /**
     * Adiciona uma condição
     *
     * @param string $cond
     * @return Select
     */
    public function addCond($cond)
    {
        array_push($this->_conds, $cond);

        return $this;
    }

    /**
     * Adiciona o ORDER BY a query
     * @param array
     * @return Select
     */
    public function setOrderBy($orderBy) {
        $this->_orderBy = $orderBy;
    }

    /**
     * Adiciona o LIMIT a query
     *
     * @param integer
     * @return Select
     */
    public function setLimit($limit)
    {
        $this->_limit = $limit;

        return $this;
    }

    /**
     * Get query
     *
     * @return string
     */
    public function getQuery()
    {
        return $this->_query;
    }

    /**
     * Monta a query
     *
     * @return Select
     */
    public function prepare()
    {
        $query = 'SELECT ';
        $query.= implode(', ', $this->_campos);
        $query.= ' FROM ' . $this->_tabela;

        if (!empty($this->_joins)) {
            $query.= ' ' . implode(' ', $this->_joins);            
        }

        $query.= ' ' . implode(' ', $this->_conds);

        if (count($this->_orderBy) > 0) {
            $query .= " ORDER BY ".implode(", ", $this->_orderBy);
        }

        if ($this->_limit != null) {
            $query .= " LIMIT {$this->_limit}";
        }

        $this->_query = $query;

        return $this;
    }

}