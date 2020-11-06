<?php

namespace Mirrors\Parts;

class Faturamento extends \Mirrors\AbstractMirror
{
	public function post($body)
	{
		$curl = curl_init();

		curl_setopt_array($curl, array(
				CURLOPT_URL => $this->getBaseURI() . "/parts/faturamentos",
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 60,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => "POST",
				CURLOPT_POSTFIELDS => json_encode($body),
				CURLOPT_HTTPHEADER => array(
						"Content-Type: application/json",
						"Access-Application-Key: " . $this->getApplicationKey(),
						"Access-Env: " . $this->getApplicationEnvironment(),
				),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		$response = json_decode($response, 1);

		return $response;
	}
}
