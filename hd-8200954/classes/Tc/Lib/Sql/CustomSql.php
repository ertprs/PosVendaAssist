<?php

namespace Tc\Lib\Sql;

class CustomSql implements SqlCommand, SqlFilter {

    public $sql;
    public $args;

    function __construct($sql, $args) {
        $this->sql = $sql;
        $this->args = $args;
    }

    public function execute($pdo) {
        $stmt = $pdo->prepare($this->sql);
        $stmt->execute($this->args);
        $count = $stmt->rowCount();
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

    public function exec($pdo) {
        return $this->execute($pdo);
    }

    public function getArgs() {
        return $this->args;
    }

    public function getSql() {
        return $this->sql;
    }

}
