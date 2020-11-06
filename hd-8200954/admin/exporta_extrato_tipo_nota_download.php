<?php 
    $arquivo = "xls/integracao_ems.txt";
    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
    header("Cache-Control: no-store, no-cache, must-revalidate");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    header("Content-type: application/x-msdownload");
    header('Content-Disposition: attachment; filename="integracao_ems.txt"');

    flush();
        $fp = fopen($arquivo,"r");
    fpassthru($fp);

    //system("mv xls/integracao_ems.txt xls/integracao_ems_bkp.txt");    
    exit;
?>