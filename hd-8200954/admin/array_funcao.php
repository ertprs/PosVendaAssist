<?php
if($cook_idioma == "pt-br" OR empty($cook_idioma)){
    $array_funcao = array(
                "T"  => "Técnico",
                "A"  => "Administrativo",
                "G"  => "Gerente AT",
                "P"  => "Proprietário",
                "AB" => "Atendente/Balcão");
}else{
    $array_funcao = array(
                "T"  => "Técnico",
                "A"  => "Administrativo",
                "G"  => "Gerente AT",
                "P"  => "Propietario",
                "AB" => "Asistente/Mostrador");
}