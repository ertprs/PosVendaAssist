<?php

namespace Tc\Lib\Sql\Filter;

use Tc\Lib\Sql\SqlFilter;
use Tc\Lib\Util\PropertySupport;

class CustomFilter implements SqlFilter {

    use PropertySupport;

    private $glue;
    private $clauses = array();
    private $sql;
    private $args;

    function __construct($clauses = array(), $glue = ' AND ') {
        $this->glue = $glue;
        $this->clauses = $clauses;
    }

    function getGlue() {
        return $this->glue;
    }

    function getClauses() {
        return $this->clauses;
    }

    function setGlue($glue) {
        $this->clearCache();
        $this->glue = $glue;
    }

    function setClauses($clauses) {
        $this->clearCache();
        $this->clauses = $clauses;
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
        foreach ($this->clauses as $clause) {
            if (is_string($clause)) {
                $clauses[] = $clause;
                continue;
            }
            if (is_array($clause) && is_string($clause[0])) {
                $clauses[] = array_shift($clause);
                $args = array_merge($args, $clause);
            }
        }
        $this->sql = implode($this->glue, $clauses);
        $this->args = $args;
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
