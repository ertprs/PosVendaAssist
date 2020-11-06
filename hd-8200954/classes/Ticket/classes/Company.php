<?php 


	class Company{
		private $url;
		private $header;

		function __construct($url, $header){
			$this->url = $url;
			$this->header = $header;
		}

		function buscaDadosCompany($cnpj){
			$curl = curl_init();
			curl_setopt_array($curl, array(
			  CURLOPT_URL => $this->url."/company/company-docs/internalHash/$cnpj",
			  CURLOPT_RETURNTRANSFER => true,
			  CURLOPT_ENCODING => "",
			  CURLOPT_MAXREDIRS => 10,
			  CURLOPT_TIMEOUT => 30,
			  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			  CURLOPT_CUSTOMREQUEST => "GET",
			  CURLOPT_HTTPHEADER => $this->header,
			));
			$response = curl_exec($curl);
			$err = curl_error($curl);
			curl_close($curl);
			if ($err) {
			  	$erro['erro'] = $err;
				return json_encode($erro);
			}
			return $response;
		}


		function enviaConvite($params = array()){
			$params = json_encode($params);
			$curl = curl_init();
			curl_setopt_array($curl, array(
			  CURLOPT_URL => $this->url."/company/company-invites/",
			  CURLOPT_RETURNTRANSFER => true,
			  CURLOPT_ENCODING => "",
			  CURLOPT_MAXREDIRS => 10,
			  CURLOPT_TIMEOUT => 30,
			  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			  CURLOPT_CUSTOMREQUEST => "POST",
			  CURLOPT_POSTFIELDS => $params,
			  CURLOPT_HTTPHEADER => $this->header,
			));
			$response = curl_exec($curl);
			$err = curl_error($curl);
			curl_close($curl);
			if ($err) {
			  	$erro['erro'] = $err;
				return json_encode($erro);
			} 
			return $response;
		}


		function criaCompany($params){

			$curl = curl_init();
			curl_setopt_array($curl, array(
			  CURLOPT_URL => $this->url."/company/company-user",
			  CURLOPT_RETURNTRANSFER => true,
			  CURLOPT_ENCODING => "",
			  CURLOPT_MAXREDIRS => 10,
			  CURLOPT_TIMEOUT => 30,
			  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			  CURLOPT_CUSTOMREQUEST => "POST",
			  CURLOPT_POSTFIELDS => $params,
			  CURLOPT_HTTPHEADER => $this->header,
			));
			$response = curl_exec($curl);
			$err = curl_error($curl);
			curl_close($curl);
			if ($err) {
				$erro['erro'] = $err;
				return json_encode($erro);
			} else {
				return $response;
			}
		}

	}