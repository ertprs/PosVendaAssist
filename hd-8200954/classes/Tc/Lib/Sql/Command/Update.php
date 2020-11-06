<?php

namespace Tc\Lib\Sql\Command;

use Tc\Lib\Sql\SqlCommand;
use Tc\Lib\Sql\SqlFilter;

class Update extends CachedSql implements SqlCommand {

    private $table;
    private $fields;

    /**
     *
     * @var Tc\Lib\Sql\SqlFilter
     */
    private $filter;

    function __construct($table = null, $fields = array(), $filter = null) {
        $this->table = $table;
        $this->fields = $fields;
        $this->filter = $filter;
    }

    function getTable() {
        return $this->table;
    }

    function getFields() {
        return $this->fields;
    }

    function getFilter() {
        return $this->filter;
    }

    function setTable($table) {
        $this->clearCache();
        $this->table = $table;
    }

    function setFields(Array $fields) {
        $this->clearCache();
        $this->fields = $fields;
    }

    function setFilter(SqlFilter $filter) {
        $this->clearCache();
        $this->filter = $filter;
    }

    protected function makeCache() {
        $args = array();
        $clauses = array();
        foreach ($this->fields as $name => $value) {
            $clauses[] = $name . ' = ?';
            $args[] = $value;
        }
        if ($this->filter == null) {
            $sql = 'UPDATE ' . $this->table . ' SET ' . implode(',', $clauses) . ';';
        } else {
            $sql = 'UPDATE ' . $this->table . ' SET ' . implode(',', $clauses) . ' WHERE ' . $this->filter->getSql() . ';';
            $args = array_merge($args, $this->filter->getArgs());
        }
        $this->setCache($sql,$args);
    }

    public function execute($pdo) {
        if (!$this->inCache()) {
            $this->makeCache();
        }
        $stmt = $pdo->prepare($this->sql);
        $stmt->execute($this->args);
        return $stmt->rowCount();
    }

}
