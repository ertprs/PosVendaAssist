<?php

function geraNum($length=8) {

    $numero = '';

    for ($x = 0; $x < $length; $x++) {

        $numero .= rand(0,9);

    }

    return $numero;

}

$x = (int) isset($_GET['x']) ? $_GET['x'] : 8;
$y = (int) isset($_GET['y']) ? $_GET['y'] : 8;

for ($i = 0; $i < $y; $i++) {

	echo geraNum($x) . '<br />';

}

?>
