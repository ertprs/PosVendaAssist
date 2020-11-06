<?php
/*
 * Exemplo de conversão de XML para TXT
 *
 */

require_once('../libs/ConvertNFePHP.class.php');

$cNFe = new ConvertNFePHP;

$arqxml = './35100258716523000119550000000033453539003003-nfe.xml';

if ( is_file($arqxml) ) {
    $arq = $cNFe->nfexml2txt($arqxml);
    $file = './'.$cNFe->chave.'-nfe.txt';
    if ( !file_put_contents($file, $arq) ) {
        echo "Erro na gravação da NFe em txt";
    }
}


?>
