<?php

namespace Tc\Lib\Sql\Builder;

use Tc\Lib\Sql\Command\Update;
use Tc\Lib\Sql\CustomSql;

class UpdateBuilder {

    private $table;
    private $sets = array();
    private $whereSql;
    private $whereArgs;

    public function setTable($table) {
        $this->table = $table;
        return $this;
    }

    public function set($values) {
        if (is_array($values) && func_num_args() == 1) {
            $this->sets = $values;
            return $this;
        }
        if (is_string($values) && func_num_args() == 2) {
            list($name, $value) = func_get_args();
            $this->sets[$name] = $value;
            return $this;
        }
    }

    public function where($where) {
        $args = func_get_args();
        $this->whereSql = array_shift($args);
        $this->whereArgs = $args;
        return $this;
    }

    public function build() {
        if (empty($this->whereSql)) {
            $filter = null;
        } else {
            $filter = new CustomSql($this->whereSql, $this->whereArgs);
        }
        $update = new Update($this->table, $this->sets, $filter);
        return $update;
    }

    public function exec($pdo) {
        $update = $this->build();
        return $update->execute($pdo);
    }

}
