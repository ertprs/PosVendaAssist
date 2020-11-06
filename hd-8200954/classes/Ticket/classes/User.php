<?php

class User{
	private $url;
	private $header;

	function __construct($url, $header){
		$this->url = $url;
		$this->header = $header;
	}

	function criaUsuario($params){
		$curl = curl_init();
		curl_setopt_array($curl, array(
		  CURLOPT_URL => $this->url."/user/user",
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

	function buscaUsuario($email){

		$curl = curl_init();
		curl_setopt_array($curl, array(
		  CURLOPT_URL => $this->url."/user/user/email/".$email,
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

		return $response;	
	}

	
	function atualizaUsuario($params) {

		$user = json_decode($params); 

		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => $this->url."/user/user",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 30,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "PUT",
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

	function recuperaSenhaUsuario($params){

		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => $this->url."/user/password-recovery",
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

	function alteraSenhaUsuario($params){

		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => $this->url."/user/password-recovery",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 30,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "PUT",
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
	
}