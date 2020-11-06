<?php

if ($_serverEnvironment == "production") {
    $fabrica_setor_email = array(
        "1314" => array(
            "nome"  => "SAC",
            "email" => "sac_posto@lenoxx.com.br"
        ),
        "10461" => array(
            "nome"  => "Dúvida Técnica",
            "email" => "apoio_tecnico@lenoxx.com.br"
        ),
        "10426" => array(
            "nome"  => "Revenda",
            "email" => "sac_revenda@lenoxx.com.br"
        ),
        "10481" => array(
            "nome"  => "Suprimentos",
            "email" => "sup_sp@lenoxx.com.br"
        ),
    );
} else {
    $fabrica_setor_email = array(
        "1314" => array(
            "nome"  => "SAC",
            "email" => "sac_posto@lenoxx.com.br"
        ),
        "10461" => array(
            "nome"  => "Dúvida Técnica",
            "email" => "apoio_tecnico@lenoxx.com.br"
        ),
        "10426" => array(
            "nome"  => "Revenda",
            "email" => "sac_revenda@lenoxx.com.br"
        ),
        "10481" => array(
            "nome"  => "Suprimentos",
            "email" => "gd_supbr@lenoxx.com.br"
        ),
    );
}

if ($areaAdmin === false) {
    $inputs_interacao = array(
        "interacao_email_setor"
    );
}
