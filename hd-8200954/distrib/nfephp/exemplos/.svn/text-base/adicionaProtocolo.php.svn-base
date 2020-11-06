<?php
require_once('../libs/ToolsNFePHP.class.php');

$nfe = new ToolsNFePHP();
$nfefile = './35100258716523000119550000000033453539003003-nfe.xml';

if ( is_file($nfefile) ) {
    $arq = file_get_contents($nfefile);
    $dom = new DomDocument;
    $dom->formatOutput = false;
    $dom->preserveWhiteSpace = false;
    $dom->loadXML($arq,LIBXML_NOBLANKS | LIBXML_NOEMPTYTAG);
    $nfeProc = $dom->getElementsByTagName("nfeProc")->item(0);
    $infNFe = $dom->getElementsByTagName("infNFe")->item(0);
    $nfeID = str_replace('NFe','',$infNFe->getAttribute("Id"));

    if ( !isset($nfeProc)){
        //nao contem a tag nfeProc
        //buscar o protocolo
        $aRet = $nfe->getNFeProtocol($nfeID);
        if ($aRet['status']){
            //houve retorno do SEFAZ
            //entao o protocolo fou gravado na pasta temporarias
            $protfile = $nfe->temDir.$nfeID.'-prot.xml';
            if ( is_file($protfile) ) {
                $prot = $nfe->addProt($nfefile,$protfile);
                if ( !file_put_contents($nfefile, $prot) ) {
                    echo "Erro na gravação da NFe!!";
                } else {
                    //remove o arquivo do protocolo
                    unlink($protfile);
                } //grava a NFe com o protocolo
            } //testa existencia do arquivo com o protocolo
        }//testa retorno do SEFAZ
    } //testa a existencia do protocolo na NFe
} //testa a existencia da NFe
?>
