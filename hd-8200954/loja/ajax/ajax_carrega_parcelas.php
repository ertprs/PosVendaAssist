<?php
include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
include dirname(__FILE__) . '/../../autentica_usuario.php';
use Lojavirtual\Loja;
use Lojavirtual\Checkout;

$objLoja               = new Loja();
$objCheckout           = new Checkout();

if ($_GET["integrador"] == "cielo") {


	$total 		= $_POST["total"];
	$bandeira 	= $_POST["bandeira"];

	if (in_array($bandeira, array("VISA","MASTERCARD","ELO","DINERS"))) {
		for ($i=1; $i <= 12; $i++) { 
			$retorno[$i]["parcela"] = $i;
			$retorno[$i]["valor_parcela"] = ($total/$i);
			$retorno[$i]["valor_parcela_formatado"] =  number_format(($total/$i), 2, ',', '.');
		}
		exit(json_encode(array("erro" => false, "parcelas" => $retorno)));
	} elseif (in_array($bandeira, array("DISCOVER"))) {
		$retorno[1]["parcela"] = 1;
		$retorno[1]["valor_parcela"] = $total;
		$retorno[1]["valor_parcela_formatado"] = number_format($total, 2, ',', '.');;
		exit(json_encode(array("erro" => false, "parcelas" => $retorno)));
	} else {
		exit(json_encode(array("erro" => true)));
	}
}

