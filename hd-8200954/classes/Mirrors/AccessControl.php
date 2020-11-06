<?php
namespace Mirrors;

class AccessControl
{

    public static function getAppKey($clientCode, $environment, $application = "posvenda")
    {

        $curl = curl_init();

        curl_setopt_array($curl, array(
		        CURLOPT_URL => "http://api2.telecontrol.com.br/AccessControl/application-key/client_code/{$clientCode}/application/{$application}",
		        CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 100,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => [
                    "Cache-Control: no-cache",
                    "Content-Type: application/json"
                ]
	    	)
    	);

        $response = json_decode(curl_exec($curl), true);
        $err = curl_error($curl);

        foreach ($response as $key => $dados) {

            if (strtoupper($dados["key_type"]["system_code"]) == strtoupper($environment)) {

                return $dados["application_key"];

            }

        }

        throw new \Exception("Nenhuma chave encontrada", 400);

    }

}
