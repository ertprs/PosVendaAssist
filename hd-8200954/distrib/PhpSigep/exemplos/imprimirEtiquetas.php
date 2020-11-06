<?php

require_once __DIR__ . '/bootstrap-exemplos.php';

$etiqueta = true;
global $result;

include_once '../../../distrib/funcao_correio.php';

$logoFile = __DIR__ . '/logo_acaciaeletro_pequeno.png';

// $layoutChancela = array(\PhpSigep\Pdf\CartaoDePostagem::TYPE_CHANCELA_CARTA);
// $pdf = new \PhpSigep\Pdf\CartaoDePostagem($params, time(), $logoFile, $layoutChancela);
$pdf = new \PhpSigep\Pdf\CartaoDePostagem($result, time(), $logoFile);
$pdf->render();
