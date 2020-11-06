<?php

class PushQueue{

	private $url = "https://api2.telecontrol.com.br/queue";    

    public function post($command,$params)
    {

    	$data = [
    		"command" => $command,
    		"params" => $params
    	];


        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => $this->url."/push-queue",
          CURLOPT_RETURNTRANSFER => true,          
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 60,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS => json_encode($data),
          CURLOPT_HTTPHEADER => array(            
            "Content-Type: application/json",            
            "Access-Application-Key: 084f77e7ff357414d5fe4a25314886fa312b2cff",
            "Access-Env: PRODUCTION"
          ),
        ));

        $response = curl_exec($curl);
        
        $err = curl_error($curl);

        curl_close($curl);
        
        $response = json_decode($response, 1);
        if (array_key_exists("exception", $response)) {
            throw new \Exception("Ocorreu um erro ao enviar a requisição: " . $response['message']);
        }

        return $response;
    }


}