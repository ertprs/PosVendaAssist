<?php

namespace Posvenda\Model;

abstract class AbstractModel
{

    /**
     * @var string
     */
    protected $tabela;

    /**
     * @var \Posvenda\Model\Sql\Select|Insert|Update
     */
    protected $sql;

    /**
     * @var string
     */
    protected $query;

    /**
     * @var array
     */
    protected $campos = array();

    /**
     * @var array
     */
    protected $join = array();

    /**
     * @var array
     */
    protected $where = array();

    /**
     * @var array
     */
    protected $groupBy = array();

    /**
     * @var array
     */
    protected $orderBy = array();

    /**
     * @var integer
     */
    protected $limit = null;

    /**
     * @var string
     */
    private $configFile;

    /**
     * @var \PDOStatement
     */
    private $_prep;

    /**
     * @var array
     */
    private $_condParam = array();

    /**
     * @var array
     */
    private $_condStack = array();

    /**
     * @resource bd
     * contém o recurso da conexão
     */
    static protected $conn;

    /**
     * @param string $tabela Nome da tabela
     * @param config $config Nome do arquivo de configuração para conexão com db
     */
    public function __construct($tabela, $config = '')
    {
        if (!empty($tabela)) {
            $this->tabela = $tabela;
        }

        if (!empty($config)) {
            $this->configFile = $config;
        // } else if (!empty($configFile)) {
        //     $this->configFile = $configFile;
        } else {
            $this->configFile = '/etc/telecontrol.cfg';
        }

        $this->connect('');
    }

    public function __get($varname) {
        if ($varname == '_pdo')
            return $this->getPDO();
    }

    /**
     * Cria uma instância de \Posvenda\Model\Sql\Select;
     *
     * @return \Posvenda\Model\AbstractModel
     */

    public function select($tabela = null)
    {
        if (!empty($tabela)) {
            $this->tabela = $tabela;
        }

        $this->sql = new \Posvenda\Model\Sql\Select;

        $this->_reset();

        return $this;
    }

    /**
     * Cria uma instância de \Posvenda\Model\Sql\Insert;
     *
     * @return \Posvenda\Model\AbstractModel
     */

    public function insert($tabela = null)
    {
        if (!empty($tabela)) {
            $this->tabela = $tabela;
        }

        $this->sql = new \Posvenda\Model\Sql\Insert;

        $this->_reset();

        return $this;
    }

    /**
     * Cria uma instância de \Posvenda\Model\Sql\Update;
     *
     * @return \Posvenda\Model\AbstractModel
     */

    public function update($tabela = null)
    {
        if (!empty($tabela)) {
            $this->tabela = $tabela;
        }

        $this->sql = new \Posvenda\Model\Sql\Update;

        $this->_reset();

        return $this;
    }

    /**
     * Campos a serem utilizados na query
     *
     * @param array $campos
     * @return \Posvenda\Model\AbstractModel
     */
    public function setCampos(array $campos)
    {
        if ($this->sql instanceof \Posvenda\Model\Sql\Update) {
            $campos = $this->transformUpdateField($campos);
        }

        $this->campos = $campos;

        return $this;
    }

    /**
     * Joins a serem utilizados na query
     *
     * @param array $join array('tbl' => 'cond')
     * @return \Posvenda\Model\AbstractModel
     */

    public function addJoin(array $join, $left = false)
    {
        foreach ($join as $tbl => $cond) {
            $leftJoin = ($left) ? "LEFT " : "";
            $this->join[] = $leftJoin . 'JOIN ' . $tbl . ' ' . $cond;

        }

        return $this;
    }

    /**
     * Compõe as cláusulas da consulta
     *
     * @param mixed array('campo' => 'valor') | string
     * @return \Posvenda\Model\AbstractModel
     */
    public function addWhere($where)
    {
        if (is_array($where)) {
            $_key = key($where);

            $explode_key = explode('.', $_key);

            if (count($explode_key) == 2) {
                $val = $explode_key[1];
            } else {
                $val = $explode_key[0];
            }

            $params = $_key . ' = :' . $val;

            $stack[$val] = $where[$_key];
            $this->stack($stack);
        } else {
            $params = $where;
        }

        if (empty($this->_condParam)) {
            $this->_condParam[0] = ' WHERE ' . $params;
        } else {
            $this->_condParam[] = ' AND ' . $params;
        }

        return $this;
    }

    /**
     * @param mixed $order
     * @param boolean $desc
     * @return AbstractModel
     */
    public function orderBy($order, $desc = false)
    {
        $orderBy = 'ORDER BY ';

        if (is_array($order)) {
            $orderBy .= implode(', ', $order);
        } else {
            $orderBy .= $order;
        }

        if (true === $desc) {
            $orderBy .= ' DESC';
        }

        $this->_condParam[] = $orderBy;

        return $this;
    }

    /**
     * Define o LIMIT
     *
     * @param integer
     * @return \Posvenda\Model\AbstractModel
     */
    public function setLimit($limit)
    {
        if (!empty($limit) && is_numeric($limit)) {
            $this->sql->setLimit($limit);
        }
    }

    /**
     * Define o ORDER BY
     *
     * @param integer
     * @return \Posvenda\Model\AbstractModel
     */
    public function setOrderBy($orderBy)
    {
        if (!empty($orderBy) && is_array($orderBy)) {
            $this->sql->setOrderBy($orderBy);
        }
    }
    
    /**
     * Cria uma condição >[=] valor
     *
     * @param array   $campo    array('campo' => 'valor')
     * @param boolean $is_equal adiciona condição de iqualdade
     * @return \Posvenda\Model\AbstractModel::biggerOrLesser
     */
    public function biggerOrEqualThan(array $campo, $is_equal = false)
    {
        $eq = '';
        if (true === $is_equal) {
            $eq = '=';
        }

        $stack = array($campo[0] => $campo[1]);
        $this->stack($stack);

        return $this->biggerOrLesser($campo[0], '>', $eq);
    }

    /**
     * Cria uma condição <[=] valor
     *
     * @param array   $campo    array('campo' => 'valor')
     * @param boolean $is_equal adiciona condição de iqualdade
     * @return \Posvenda\Model\AbstractModel::biggerOrLesser
     */
    public function lesserOrEqualThan(array $campo, $is_equal = false)
    {
        $eq = '';
        if (true === $is_equal) {
            $eq = '=';
        }

        $stack = array($campo[0] => $campo[1]);
        $this->stack($stack);

        return $this->biggerOrLesser($campo[0], '<', $eq);
    }

    /**
     * Cria uma condição BETWEEN
     *
     * @param string $campo
     * @param array $datas
     * @return string
     */
    public function between($campo, array $datas)
    {
        foreach ($datas as $k => $d) {
            $this->stack(array(($k + 1) => $d));
        }

        return $campo . ' BETWEEN :1 AND :2';
    }

    /**
     * Monta a query e a prepara para execução
     *
     * @return \Posvenda\Model\AbstractModel
     */
    public function prepare()
    {
        $this->sql->setTabela($this->tabela);

        if ($this->sql instanceof \Posvenda\Model\Sql\Insert) {
            $this->sql->setCampos($this->campos);
        } else {
            foreach ($this->campos as $chave => $campo) {
                $add = $campo;

                if ($this->sql instanceof \Posvenda\Model\Sql\Update) {
                    $add = array("{$chave}" => $campo);
                }

                $this->sql->addCampo($add);
            }

            foreach ($this->join as $join) {
                $this->sql->addJoin($join);
            }

            foreach ($this->_condParam as $cond) {
                $this->sql->addCond($cond);
            }
        }

        $this->sql->prepare();

        $this->query = $this->sql->getQuery();

        $this->_prep = $this->_pdo->prepare($this->query, array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY));

        return $this;
    }

    /**
     * Executa a query
     *
     * @return boolean
     */
    public function execute()
    {
        return $this->_prep->execute($this->_condStack);
    }

    /**
     * @return string
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @return \PDO
     */
    public function getPDO()
    {
        return self::$conn;
    }

    /**
     * @return \PDOStatement
     */
    public function getPDOStatement()
    {
        return $this->_prep;
    }

    /**
     * Carrega o arquivo com os parâmetros da conexão e retorna
     * a string de conexão PDO.
     *
     * @return string
     */
    protected function loadConfig()
    {
        require $this->configFile;
        
        if (empty($dbhost))
            die('Conexão remota não estabelecida.');
        
        $conn_str = 'pgsql:host=' . $dbhost . ';port=' . $dbport . ';dbname=' . $dbnome . ';user=' . $dbusuario . ';password=' . $dbsenha;

        return $conn_str;
    }

    /**
     * Tenta realizar a conexão
     *
     * @param string $conn_str String de conexão com o DB
     * @return boolean
     */
    protected function connect($conn_str)
    {
        if (is_object(self::$conn)) {
            // if (gethostname() == 'ip-10-253-157-123')
            //     echo "Reutilizando conexão PDO\n";
            return true;
        }

        try {
			$conn_str = $this->loadConfig();
			self::$conn = new \PDO($conn_str);

            // if (gethostname() == 'ip-10-253-157-123')
            //     echo 'Nova conexão PDO'.PHP_EOL;
        } catch (\Exception $e) {
            die('Falha na conexão: ' . $e->getMessage());
        }
        return true;
    }

    /**
     * Cria uma condição <|>[=] valor
     *
     * @param string $campo
     * @param string $operator
     * @param string $is_equal
     * @return string
     */
    protected function biggerOrLesser($campo, $operator, $is_equal)
    {
        if (!empty($is_equal)) {
            $eq = '=';
        }

        return $campo . ' ' . $operator . $eq . ' :' . $campo;
    }

    /**
     * Empilha os parâmetros da query
     *
     * @param array $value Parâmetro a ser empilhado
     * @return \Posvenda\Model\AbstractModel
     */
    private function stack(array $value)
    {
        $key = key($value);
        $this->_condStack[':' . (string) $key] = $value[$key];

        return $this;
    }

    /**
     * Reseta as condições da query
     *
     * @return \Posvenda\Model\AbstractModel
     */
    private function _reset()
    {
        $this->_condParam = array();
        $this->_condStack = array();
        $this->join = array();
        $this->campos = array();

        return $this;
    }

    /**
     * Altera os campos do UPDATE
     *   de:   array('chave' => $valor)
     *   para: array('chave' > ':chave')
     *  E empilha $valor
     *
     * @param  array $campos
     * @return array
     */
    private function transformUpdateField(array $campos)
    {
        $ret = array();

        foreach ($campos as $key => $val) {
            $ret[$key] = ':' . $key;
            $stack = array($key => $val);
            $this->stack($stack);
        }

        return $ret;
    }
}
