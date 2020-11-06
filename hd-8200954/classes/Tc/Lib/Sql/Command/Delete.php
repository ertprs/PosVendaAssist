<?php

namespace Tc\Lib\Sql\Command;

use Tc\Lib\Sql\SqlCommand;

class Delete extends CachedSql implements SqlCommand {

    private $table;
    private $filter;

    function __construct($table = null, $filter = null) {
        $this->table = $table;
        $this->filter = $filter;
    }

    function getTable() {
        return $this->table;
    }

    function getFilter() {
        return $this->filter;
    }

    function setTable($table) {
        $this->clearCache();
        $this->table = $table;
    }

    /**
     * 
     * @param \Tc\Lib\Sql\SqlFilter $filter
     */
    function setFilter($filter) {
        $this->clearCache();
        $this->filter = $filter;
    }

    protected function makeCache() {
        $args = array();
        if ($this->filter == null) {
            $sql = 'DELETE FROM ' . $this->table . ';';
        } else {
            $sql = 'DELETE FROM ' . $this->table . ' WHERE ' . $this->filter->getSql() . ';';
            $args = $this->filter->getArgs();
        }
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
