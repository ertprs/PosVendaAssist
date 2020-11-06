<?php
//ini_set("display_errors", "1");
//error_reporting(E_ALL);
include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
include dirname(__FILE__) . '/../funcoes.php';

use Posvenda\ImportaFotosProdutos;
$logClass = new Log2();
$logClass->adicionaEmail("felipe.marttos@telecontrol.com.br");
$logClass->adicionaEmail("gustavo.pinsard@telecontrol.com.br");

$fabrica_nome  = "Rowa";
$fabrica       = 163;
//$pathCompleto  = '/home/felipe/public_html/pecas_rowa/';//testes
$pathCompleto  = '/tmp/rowa-pinsard/';

$objImporta    = new ImportaFotosProdutos($fabrica, $con, "tdocs");
$dadosRetorno  = $objImporta->importaFotos($pathCompleto, "peca");

if (isset($dadosRetorno["erro"]) && $dadosRetorno["erro"]) {

    $logClass->adicionaLog(array("titulo" => "Log de Erro - Importação de Fotos de Produtos")); // Titulo
    $dadosLog = "<div style='text-align:center;background:#d90000;color:#ffffff;padding:5px;'><h2>".$dadosRetorno["msg"]."</h2></div>";
    $logClass->adicionaLog($dadosLog);

    if ($logClass->enviaEmails() == "200") {
        echo "Log de Erro enviado com Sucesso!";
    } else {
        $logClass->enviaEmails();
    }

    $fp = fopen("tmp/{$fabrica_nome}/importafoto/log-erro".date("d-m-Y_H-i-s").".txt", "a");
    fwrite($fp, "Data Log: " . date("d/m/Y") . "\n");
    fwrite($fp, $dadosRetorno["msg"]);
    fclose($fp);

} else {

    $logClass->adicionaLog(array("titulo" => "Importação de Fotos de Produtos")); // Titulo
    $logClass->adicionaLog($dadosRetorno);

    if ($logClass->enviaEmails() == "200") {
        echo "Log enviado com Sucesso!";
    } else {
        $logClass->enviaEmails();
    }

    $fp = fopen("tmp/{$fabrica_nome}/importafoto/log-".date("d-m-Y_H-i-s").".txt", "a");
    fwrite($fp, "Data Log: " . date("d/m/Y") . "\n");
    fwrite($fp, $dadosRetorno);
    fclose($fp); 
}



