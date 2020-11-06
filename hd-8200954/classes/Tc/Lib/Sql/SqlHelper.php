<?php

namespace Tc\Lib\Sql;

use Tc\Lib\Sql\Builder\SelectBuilder;
use Tc\Lib\Sql\Builder\InsertBuilder;
use Tc\Lib\Sql\Builder\DeleteBuilder;
use Tc\Lib\Sql\Builder\UpdateBuilder;
use Tc\Lib\Sql\CustomSql;

class SqlHelper {

    /**
     * 
     * @param string $sql
     * @param array $args
     * @return \Tc\Lib\Sql\CustomSql
     */
    public static function sql($sql, $args = array()) {
        if (func_num_args() == 2 && is_array($args)) {
            return new CustomSql($sql, $args);
        }
        $args = func_get_args();
        $sql = array_shift($args);
        return new CustomSql($sql, $args);
    }

    /**
     * 
     * @return \Tc\Lib\Sql\Builder\SelectBuilder
     */
    public static function select() {
        $fields = func_get_args();
        $builder = new SelectBuilder();
        return $builder->setFields($fields);
    }

    /**
     * 
     * @param string $table
     * @return \Tc\Lib\Sql\Builder\InsertBuilder
     */
    public static function insert($table = null) {
        $builder = new InsertBuilder();
        return $builder->into($table);
    }

    /**
     * 
     * @param type $table
     * @return \Tc\Lib\Sql\Builder\UpdateBuilder
     */
    public static function update($table) {
        $builder = new UpdateBuilder();
        return $builder->setTable($table);
    }

    /**
     * 
     * @param type $table
     * @return \Tc\Lib\Sql\Builder\DeleteBuilder
     */
    public static function delete($table = null) {
        $builder = new DeleteBuilder();
        return $builder->from($table);
    }

}
