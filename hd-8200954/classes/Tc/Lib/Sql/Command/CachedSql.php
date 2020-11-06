<?php

namespace Tc\Lib\Sql\Command;

use Tc\Lib\Sql\SqlFilter;

abstract class CachedSql implements SqlFilter {

    protected $sql = null;
    protected $args = null;

    public function inCache() {
        if ($this->sql == null) {
            return false;
        }
        if ($this->args == null) {
            return false;
        }
        return true;
    }

    public function clearCache() {
        $this->sql = null;
        $this->args = null;
    }

    protected function setCache($sql, $args = array()) {
        $this->sql = $sql;
        $this->args = $args;
    }

    protected abstract function makeCache();

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
