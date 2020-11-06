<?php

namespace Posvenda\Model\Sql;

interface InterfaceSql
{
    public function setTabela($tabela);
    public function addCampo($campo);
    public function addCond($cond);
    public function getQuery();
    public function prepare();
}

