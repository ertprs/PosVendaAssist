<?php
$soap = new SoapClient("http://201.91.139.164:8080/g5-senior-services/sapiens_Synccom_senior_g5_co_mcm_ven_pedidos?wsdl", array("trace" => 1, "exception" => 1));

        $argumentos = array(
                "user"       => "suporte",
                "password"   => "suporte",
                "encryption" => "0",
                "parameters" => array(
                        "consultar" => array(
                                "pedido" => array(
                                        "codEmp" => 1,
                                        "codFil" => 1,
                                        "numPed" => 80413
                                )
                        )
                )
    );

    $metodo = "ConsultarPedido";

$soapResult = $soap->__soapCall($metodo, $argumentos);

print_r($soapResult);

