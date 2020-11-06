<?php

if (!function_exists('codigo_visitar_loja')) { // HD 834947 - Strange error.. LOL
    function codigo_visitar_loja($login, $is_lu=true, $fabrica='') { // BEGIN function codigo_visitar_loja
        $lu = ($is_lu) ? "1" : "0";
        $cp_len     = dechex(strlen($login));   // Comprimento do código_posto / login_unico, em hexa (até 5 chars)

        $ctrl_pos   = str_pad(4 + $cp_len,2, "0",STR_PAD_LEFT); // Posição do código de controle, 2 dígitos (até 55 chars... suficiente)
        $fabrica    = str_pad($fabrica,   2, "0",STR_PAD_LEFT); // Código da fábrica. '00' se é login_unico
        $controle   = ((date('d')*24) + date('h')) * 3600;      // Pega apenas dia do mês e hora, para
                                                                // minimizar divergências se passarem vários minutos desde
                                                                // que carregou a página até que clica em 'visitar loja'...

        return $lu . $cp_len . $ctrl_pos . $fabrica . $login . $controle;
    } // END function codigo_visitar_loja
}
