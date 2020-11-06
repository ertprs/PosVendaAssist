<?php

if (substr_count($PHP_SELF, 'os_press.php') || substr_count($_SERVER['HTTP_REFERER'], 'os_press.php')) {

    define ('CAMPO_PESQUISA', 'os');
    $value = $os;

} else if (substr_count($PHP_SELF, 'callcenter_interativo_new.php') || substr_count($_SERVER['HTTP_REFERER'], 'callcenter_interativo_new.php')) {

    define ('CAMPO_PESQUISA', 'hd_chamado');
    $value = !empty($hd_chamado) ? $hd_chamado : $callcenter;

}

?>
