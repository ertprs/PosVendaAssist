<?php

namespace Tc\Lib\Sql\Builder;

use Tc\Lib\Sql\Command\Delete;
use Tc\Lib\Sql\CustomSql;

class DeleteBuilder {

    private $table;
    private $whereSql;
    private $whereArgs;

    public function from($table) {
        $this->table = $table;
        return $this;
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
        $delete = new Delete($this->table, $filter);
        return $delete;
    }

    public function exec($pdo) {
        $delete = $this->build();
        return $delete->execute($pdo);
    }

}
