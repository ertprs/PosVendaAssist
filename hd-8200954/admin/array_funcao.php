<?php
if($cook_idioma == "pt-br" OR empty($cook_idioma)){
    $array_funcao = array(
                "T"  => "T�cnico",
                "A"  => "Administrativo",
                "G"  => "Gerente AT",
                "P"  => "Propriet�rio",
                "AB" => "Atendente/Balc�o");
}else{
    $array_funcao = array(
                "T"  => "T�cnico",
                "A"  => "Administrativo",
                "G"  => "Gerente AT",
                "P"  => "Propietario",
                "AB" => "Asistente/Mostrador");
}