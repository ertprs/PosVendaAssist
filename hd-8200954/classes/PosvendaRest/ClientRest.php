<?php

namespace PosvendaRest;

class ClientRest {

	const API_HOST = 'https://api2.telecontrol.com.br/';

	public $url;
	public $header     = array('Content-Type' => 'application/json');
	public $urlParams  = array();
	public $bodyParams = array();


	public function setUrl($nome_api, $endpoint){
		$this->url = "http://api2.telecontrol.com.br/".$nome_api.'/'.$endpoint;
	}

	public function setHeader($headers){
		$this->header = $headers;
		$this->header["Content-Type"] = "application/json";
	}

	private function utf8Decode($utf8Encoded){
		if(is_string($utf8Encoded)){
			return utf8_decode($utf8Encoded);
		}
		if(is_array($utf8Encoded)){
			$newArray = array();
			foreach ($utf8Encoded as $key => $value) {
				$newKey = $key;
				if(is_string($key)){
					$newKey = utf8_decode($key);
				}
				$newValue = $this->utf8Decode($value);
				$newArray[$newKey] = $newValue;
			}
			return $newArray;
		}
		return $utf8Encoded;
	}


	public function prepareUrl($moreParams = array()){
		$params = array_merge($this->urlParams,$moreParams);
		$suffix = '';
		foreach ($params as $name => $value) {
			if (is_numeric($name)) {
				continue;
			}
			$suffix .= '/' . urlencode($name) . '/' . urlencode((string) $value);
		}
		return $this->url . $suffix;
	}

	public function prepareHeader($moreHeaders = array()) {
		$headers = array_merge($this->header, $moreHeaders);
		$h       = array();
		foreach ($headers as $name => $value) {
			if (is_numeric($name)) {
				$h[] = (string) $value;
				continue;
			}
			$h[] = $name . ': ' . (string) $value;
		}
		return $h;
	}

	public function preprareJsonBody($moreParams = array()) {
		$body = array_merge($this->bodyParams, $moreParams);
		if (empty($body)) {
			return '{}';
		}
		//toUtf8 in funcoes.php
		$body = json_encode(toUtf8($body));
		return $body;
	}

	public function get($urlParams = array()) {
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $this->prepareUrl($urlParams));
		curl_setopt($curl, CURLOPT_HTTPHEADER, $this->prepareHeader());
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		$result = curl_exec($curl);
		if ($result === false) {
			throw new \Exception(curl_error($curl));
		}
		$response = json_decode($result,true);
		$response = $this->utf8Decode($response);
		if($response['exception']){
			throw new \Exception($response['message']);
		}

		return $response;
	}

	public function post($urlParams = array(), $bodyParams = array()) {
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $this->prepareUrl($urlParams));
		curl_setopt($curl, CURLOPT_HTTPHEADER, $this->prepareHeader());
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $this->preprareJsonBody($bodyParams));
		$result = curl_exec($curl);
		if ($result === false) {
			throw new \Exception(curl_error($curl));
		}
		$response = json_decode($result,true);
		$response = $this->utf8Decode($response);
		if($response['exception']){
			throw new \Exception($response['message']);
		}

		return $response;
	}

	public function put($urlParams = array(), $bodyParams = array()) {
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $this->prepareUrl($urlParams));
		curl_setopt($curl, CURLOPT_HTTPHEADER, $this->prepareHeader());
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $this->preprareJsonBody($bodyParams));
		$result = curl_exec($curl);
		if ($result === false) {
			throw new \Exception(curl_error($curl));
		}
		$response = json_decode($result,true);
		$response = $this->utf8Decode($response);
		if($response['exception']){
			throw new \Exception($response['message']);
		}

		return $response;
	}

	public function delete($urlParams = array()) {
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $this->prepareUrl($urlParams));
		curl_setopt($curl, CURLOPT_HTTPHEADER, $this->prepareHeader());
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		$result = curl_exec($curl);
		if ($result === false) {
			throw new \Exception(curl_error($curl));
		}
		$response = json_decode($result,true);
		$response = $this->utf8Decode($response);
		if($response['exception']){
			throw new \Exception($response['message']);
		}

		return $response;
	}

}
