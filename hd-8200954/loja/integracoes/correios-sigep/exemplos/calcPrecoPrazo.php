<?php

require_once __DIR__ . '/bootstrap-exemplos.php';

$dimensao = new \PhpSigep\Model\Dimensao();
$dimensao->setTipo(\PhpSigep\Model\Dimensao::TIPO_PACOTE_CAIXA);
$dimensao->setAltura(15); // em centímetros
$dimensao->setComprimento(17); // em centímetros
$dimensao->setLargura(12); // em centímetros

$params = new \PhpSigep\Model\CalcPrecoPrazo();
$params->setAccessData(new \PhpSigep\Model\AccessDataHomologacao());
$params->setCepOrigem('30170-010');
$params->setCepDestino('04538-132');
$params->setServicosPostagem(\PhpSigep\Model\ServicoDePostagem::getAll());
$params->setAjustarDimensaoMinima(true);
$params->setDimensao($dimensao);
$params->setPeso(0.150);// 150 gramas


$phpSigep = new PhpSigep\Services\SoapClient\Real();
$result = $phpSigep->calcPrecoPrazo($params);
$retorno = (object)$result;

echo "<pre>".print_r($retorno->getResult(), 1)."</pre>";exit;
echo "<pre>".print_r($retorno->getResult()[0]->getServico(), 1)."</pre>";exit;
var_dump((array)$result);