<?php


$regras["consumidor|celular"]["obrigatorio"] = true;
$regras["consumidor|telefone"]["obrigatorio"] = true;

if (strlen(trim(getValue("consumidor[celular]"))) > 0 OR strlen(trim(getValue("consumidor[telefone]"))) > 0) {
    $regras["consumidor|telefone"]["obrigatorio"] = false;
    $regras["consumidor|celular"]["obrigatorio"] = false;
}


