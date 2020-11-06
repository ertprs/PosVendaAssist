<?php

namespace Tc\Lib\Sql\Filter;

use Tc\Lib\Sql\SqlFilter;
use Tc\Lib\Util\PropertySupport;

class EqualFilter implements SqlFilter {

    use PropertySupport;

    private $fields;
    private $sql;
    private $args;

    function __construct($fields = array()) {
        $this->fields = $fields;
    }

    public function setFields($fields) {
        $this->clearCache();
        $this->fields = $fields;
    }

    public function getFields() {
        return $this->fields;
    }

    private function inCache() {
        if ($this->sql == null) {
            return false;
        }
        if ($this->args == null) {
            return false;
        }
        return true;
    }

    private function clearCache() {
        $this->sql = null;
        $this->args = null;
    }

    private function makeCache() {
        $args = array();
        $clauses = array();
        foreach ($this->fields as $key => $value) {
            if (is_array($value)) {
                $clauses[] = $key . ' IN (' . implode(',', array_fill(0, count($value), '?')) . ')';
                $args = array_merge($args, $value);
            } else {
                $clauses[] = $key . ' = ?';
                $args[] = $value;
            }
        }
        $this->args = $args;
        $this->sql = implode(' AND ', $clauses);
    }

    public function getArgs() {
        if (!$this->inCache()) {
            $this->makeCache();
        }
        return $this->args;
    }

    public function getSql() {
        if (!$this->inCache()) {
            $this->makeCache();
        }
        return $this->sql;
    }

}
