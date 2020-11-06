<?php

namespace Tc\Lib\Sql\Command;

use Tc\Lib\Sql\SqlFilter;
use Tc\Lib\Sql\SqlCommand;
use Tc\Lib\Sql\CustomSql;

class Select implements SqlCommand {

    private $fields;
    private $tables;
    private $joins;
    private $groups;
    private $orders;
    /*
      private $limit; */

    /**
     * @var Tc\Lib\Sql\SqlFilter
     */
    private $filter;

    /**
     *
     * @var Tc\Lib\Sql\CustomSql;
     */
    private $cache = null;

    function __construct($tables = null, $fields = array(), $filter = null, $joins = array(), $orders = array(), $groups = array()) {
        $this->fields = $fields;
        if (!is_array($tables)) {
            $tables = array($tables);
        }
        $this->tables = $tables;
        $this->filter = $filter;
        $this->joins = $joins;
        $this->orders = $orders;
        $this->groups = $groups;
    }

    private function clearCache() {
        $this->cache = null;
    }

    private function inCache() {
        return $this->cache != null;
    }

    private function buildOrder() {
        $orders = array();
        foreach ($this->orders as $key => $value) {
            if (is_numeric($key)) {
                $orders[] = $value;
                continue;
            }
            $orders[] = $key . ' ' . $value;
        }
        if (empty($orders))
            return '';
        return ' ORDER BY ' . implode(',', $orders);
    }

    private function buildGroup() {
        if (empty($this->groups))
            return '';
        return ' GROUP BY ' . implode(',', $this->groups);
    }

    private function buildWhere() {
        if (empty($this->filter)) {
            return array('', array());
        }
        return array(' WHERE ' . $this->filter->getSql(), $this->filter->getArgs());
    }

    private function buildJoins() {
        $sql = array();
        $args = array();
        foreach ($this->joins as $join) {
            $joinType = strtoupper($join[0]);
            if (!in_array($joinType, array('INNER', 'FULL', 'LEFT', 'RIGHT'))) {
                throw new \Exception('Tipo de join(' . $type . ') invalido');
            }
            $table = $join[1];
            if ($join[2] instanceof SqlFilter) {
                $sql[] = $joinType . $table . ' ON (' . $join[2]->getSql() . ') ';
                $args = array_merge($join[2]->getArgs());
                continue;
            }
            if (is_string($join[2])) {
                $joinColumn = $join[2];
                $sql[] = $joinType . ' JOIN ' . $table . ' ON (' . $joinColumn . '=' . $joinColumn . ') ';
                continue;
            }
            if (!is_array($join[2])) {
                throw new \Exception("Condição on do JOIN não valida");
            }
            $on = array();
            foreach ($join[2] as $key => $value) {
                $leftColumn = is_numeric($key) ? $value : $key;
                $rigthColumn = $value;
                $on[] = $leftColumn . ' = ' . $rigthColumn;
            }
            $on = implode(' AND ', $on);
            $sql[] = $joinType . ' JOIN ' . $table . ' ON (' . $on . ') ';
        }
        return array(' ' . implode(' ', $sql), $args);
    }

    private function makeCache() {
        if ($this->fields == null) {
            $this->fields = array('*');
        }
        if (!is_array($this->fields)) {
            $this->fields = array($this->fields);
        }
        $columns = array();
        $args = array();
        foreach ($this->fields as $field) {
            if (is_string($field)) {
                $columns[] = $field;
                continue;
            }
            if (is_array($field) && is_string($field[0])) {
                $columns[] = array_shift($field);
                $args = array_merge($args, $field);
                continue;
            }
            throw new Exception("Este tipo de field para o select ainda não foi implementado");
        }
        list($joins, $joinArgs) = $this->buildJoins();
        $args = array_merge($args, $joinArgs);
        list($where, $whereArgs) = $this->buildWhere();
        $args = array_merge($args, $whereArgs);
        $orderBy = $this->buildOrder();
        $groupBy = $this->buildGroup();
        $sql = $sql = 'SELECT ' . implode(',', $columns) . ' FROM ' . implode(',', $this->tables) . $joins . $where . $groupBy . $orderBy . ';';
        $this->cache = new CustomSql($sql, $args);
    }

    function getJoins() {
        return $this->joins;
    }

    function setJoins($joins) {
        $this->clearCache();
        $this->joins = $joins;
    }

    function getOrders() {
        return $this->orders;
    }

    function setOrders($orders) {
        $this->clearCache();
        if (!is_array($orders)) {
            $orders = array($orders);
        }
        $this->orders = $orders;
    }

    function getGroups() {
        return $this->groups;
    }

    function setGroups($groups) {
        $this->clearCache();
        if (!is_array($groups)) {
            $groups = array($groups);
        }
        $this->groups = $groups;
    }

    public function setFields($fields) {
        $this->clearCache();
        $this->fields = $fields;
    }

    public function getFields() {
        return $this->fields;
    }

    public function setTables($tables) {
        $this->clearCache();
        if (!is_array($tables)) {
            $tables = array($tables);
        }
        $this->tables = $tables;
    }

    public function getTables() {
        return $this->tables;
    }

    public function setFilter(SqlFilter $filter) {
        $this->clearCache();
        $this->filter = $filter;
    }

    public function getFilter() {
        return $this->filter;
    }

    public function execute($pdo) {
        if (!$this->inCache()) {
            $this->makeCache();
        }
        return $this->cache->execute($pdo);
    }

}
