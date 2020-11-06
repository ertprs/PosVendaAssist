<?php

namespace Tc\Lib\Sql;

interface SqlFilter {
    
    public function getSql();
    
    public function getArgs();
    
}
