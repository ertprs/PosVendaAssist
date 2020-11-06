<?php

namespace Tc\Lib\Sql;

interface SqlCommand {

    public function execute($pdo);
}
