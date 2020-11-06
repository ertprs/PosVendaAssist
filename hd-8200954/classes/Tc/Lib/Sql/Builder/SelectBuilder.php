<?php

namespace Tc\Lib\Sql\Builder;

use Tc\Lib\Sql\Command\Select;
use Tc\Lib\Sql\CustomSql;

class SelectBuilder {

    private $selectFields = array();
    private $tables = '';
    private $joins = array();
    private $orders = array();
    private $groups = array();
    private $whereSql = '';
    private $whereArgs = array();

    public function __construct() {
        
    }

    public function from() {
        $this->tables = func_get_args();
        return $this;
    }

    public function setTables($tables) {
        $this->tables = $tables;
        return $this;
    }

    public function field($field) {
        $this->selectFields = array_merge($this->selectFields, func_get_args());
        return $this;
    }

    public function setFields(Array $fields) {
        $this->selectFields = $fields;
        return $this;
    }

    public function addField($field) {
        if (func_num_args() > 1) {
            $funcGetArgs = func_get_args();
            $key = array_shift($funcGetArgs);
            $this->selectFields[$key] = $funcGetArgs;
        } else {
            $this->selectFields[] = $field;
        }
        return $this;
    }

    public function join($table, $on) {
        if (func_num_args() > 2) {
            $on = func_get_args();
            array_shift($on);
        }
        $this->joins[] = array('INNER', $table, $on);
        return $this;
    }

    public function fullJoin($table, $on) {
        if (func_num_args() > 2) {
            $on = func_get_args();
            array_shift($on);
        }
        $this->joins[] = array('FULL', $table, $on);
        return $this;
    }

    public function leftJoin($table, $on) {
        if (func_num_args() > 2) {
            $on = func_get_args();
            array_shift($on);
        }
        $this->joins[] = array('LEFT', $table, $on);
        return $this;
    }

    public function rightJoin($table, $on) {
        if (func_num_args() > 2) {
            $on = func_get_args();
            array_shift($on);
        }
        $this->joins[] = array('RIGHT', $table, $on);
        return $this;
    }

    public function orderBy() {
        $this->orders = func_get_args();
        return $this;
    }

    public function groupBy() {
        $this->groups = func_get_args();
        return $this;
    }

    function where($where) {
        $args = func_get_args();
        $this->whereSql = array_shift($args);
        $this->whereArgs = $args;
        return $this;
    }

    /**
     * 
     * @return Tc\Lib\Sql\SqlCommand
     */
    public function build() {
        if (empty($this->whereSql)) {
            $filter = null;
        } else {
            $filter = new CustomSql($this->whereSql, $this->whereArgs);
        }
        $select = new Select($this->tables, $this->selectFields, $filter, $this->joins, $this->orders, $this->groups);
        return $select;
    }

    function exec($pdo) {
        $select = $this->build();
        return $select->execute($pdo);
    }

}
