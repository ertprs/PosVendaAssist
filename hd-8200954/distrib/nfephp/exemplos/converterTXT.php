<?php
/*
 * Exemplo de conversão de TXT para XML
 *
 */
require_once('../libs/ConvertNFePHP.class.php');

$cNFe = new ConvertNFePHP;


$arqtxt = './35100258716523000119550000000033453539003003-nfe.txt';
if ( is_file($arqtxt) ) {
    $arq = $cNFe->nfetxt2xml($arqtxt);
    $file = './'.$cNFe->chave.'-nfe.xml';
    if ( !file_put_contents($file, $arq) ) {
        echo "Erro na gravação da NFe em xml";
    }
}


?>
