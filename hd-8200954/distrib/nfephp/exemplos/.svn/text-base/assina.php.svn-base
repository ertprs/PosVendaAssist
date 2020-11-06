<?php
//assina todas as NFe na pasta entradas e as move para a pasta assinadas

require_once('../libs/ToolsNFePHP.class.php');

$nfe = new ToolsNFePHP;
$arqxml = './35100258716523000119550000000033453539003003-nfe.xml';

if ( is_file($arqxml) ) {
    $nfefile = file_get_contents($arqxml);
    if ( $signn = $nfe->signXML($nfefile, 'infNFe') ) {
        echo "NFe Assinada ..";
        unlink($arqxml);
        if ( !file_put_contents($arqxml, $signn) ) {
            echo "FALHA na gravação da NFe Assinada!!";
        }
        
    } else {
        echo "FALHA NFe não assinada!!!!";
    }    
}

?>
