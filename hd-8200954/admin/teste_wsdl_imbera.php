<?php

//    $client  = new \nusoap_client("http://fiori.efemsa.com:7084/soap/default", array());
$url = 'imbera.wsdl' ;
        $client  = new SoapClient($url, array("trace" => 1, "exception" => 1));

print_r($client);


				        $teste = (object) array("T_ENTRADA" => (object) array('CENTRO' => '6200',
                                                                  'CLIENTE' => '7310',
                                                                  'DATA' => '20160503',
                                                                  'O_TELEC' => '42490684',
                                                                  'TECNICO' => '2001',
                                                                  'MATERIAL' => '3034488',
                                                                  'CANTIDAD' => '1',
                                                                  'UM' => 'UN',
                                                                  'NF' => '')
                                            );

   //     $client->call('ConsumoTec', array($teste));
	$function = 'ConsumoTec';
        try {
echo "REQUEST:\n" . $client->__getLastRequest() . "\n";
		//$Response = $client->DoRemoteFunction($teste); // Send the request.
		$result  = $client->__soapCall($function, array($teste));

    	} catch (Exception $e) {
        //if ($_environment == 'development')
          //  var_dump($e);

	echo "<pre>";

	echo "REQUEST:\n" . $client->__getLastRequest() . "\n";
	//	print_r($e);
       // echo $msg_erro;
    }
?>
	


?>
