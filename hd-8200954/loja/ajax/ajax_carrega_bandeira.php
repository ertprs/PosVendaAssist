<?php
include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
include dirname(__FILE__) . '/../../autentica_usuario.php';
use Lojavirtual\Loja;
use Lojavirtual\Checkout;

$objLoja               = new Loja();
$objCheckout           = new Checkout();
$configLoja            = $objLoja->getConfigLoja();
$configLojaFrete       = json_decode($configLoja["pa_forma_envio"], 1);
$configLojaPagamento   = json_decode($configLoja["pa_forma_pagamento"], 1);

if ($_GET["carrega_bandeiras"] == true) {

	if (isset($configLojaPagamento["meio"]["cielo"]["bandeiras"])) {
		$bandeiras = $configLojaPagamento["meio"]["cielo"]["bandeiras"];
		foreach ($bandeiras as $key => $bandeira) {
			$retorno .= "<div class='span2 icone-bandeiras' title='Selecione' data-id='".$bandeira."'>
							<img width='100%' src='loja/layout/img/bandeiras/".strtolower($bandeira).".png' alt=''>
						</div>";
		}
		exit($retorno);
	} else {
		exit("<div class='alert alert-warning'>Nenhuma bandeira encontrada.</div>");
	}
}


if ($_GET["carrega_trava_ccv"] == true) {

	$bandeira = $_REQUEST["bandeira"];
	exit($objCheckout->ccvTrava[strtolower($bandeira)]);

}

