<?php

namespace Tc\Lib\Sql\Builder;

use Tc\Lib\Sql\Command\Insert;

class InsertBuilder {

    private $table;
    private $fields;

    function __construct() {
        
    }

    public function into($table) {
        $this->table = $table;
        return $this;
    }

    public function field($column, $value) {
        $this->fields[$column] = $value;
        return $this;
    }

    public function values(Array $values) {
        $this->fields = $values;
        return $this;
    }

    /**
     *
     * @return Tc\Lib\Sql\SqlCommand
     */
    public function build() {
        $insert = new Insert($this->table, $this->fields);
        return $insert;
    }

    function exec($pdo) {
        $insert = $this->build();
        return $insert->execute($pdo);
    }

}
