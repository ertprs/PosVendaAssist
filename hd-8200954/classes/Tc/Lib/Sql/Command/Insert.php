<?php

namespace Tc\Lib\Sql\Command;

use Tc\Lib\Sql\SqlCommand;

class Insert extends CachedSql implements SqlCommand {

    private $table;
    private $fields;

    function __construct($table = null, $fields = array()) {
        $this->table = $table;
        $this->fields = $fields;
    }

    function getTable() {
        return $this->table;
    }

    function getFields() {
        return $this->fields;
    }

    function setTable($table) {
        $this->clearCache();
        $this->table = $table;
    }

    function setFields($fields) {
        $this->clearCache();
        $this->fields = $fields;
    }

    protected function makeCache() {
        $args = array_values($this->fields);
        $columns = implode(',', array_keys($this->fields));
        $values = implode(',', array_fill(0, count($this->fields), '?'));
        $sql = 'INSERT INTO ' . $this->table . '(' . $columns . ') VALUES (' . $values . ')';
        $this->setCache($sql, $args);
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
