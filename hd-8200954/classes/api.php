<?php
/**
 * Classe para o Interaчуo com a API
 */
include('restcurlclient.php');

class Api
{
	// configuraчѕes da API
	const API_URL			= 'http://api.telecontrol.local';
	const API_VERSION		= '2012-10-25';
	const API_ENVIRONMENT	= 'desenv'; // desenv, sandbox, production

	private $client;

	public $login_fabrica;
	public $uri;
	public $dados;
	
	public function __construct() {
		$this->client = new RestCurlClient();
	}
	
	public function GET() {
		$response = $this->client->get($this->__setUri(), $this->__setHeaders());
		return json_decode($response);
	}

	public function HEAD() {
		$response = $this->client->get($this->__setUri(), $this->__setHeaders());
		return json_decode($response);
	}

	public function POST() {
		$this->__validaDados();
		$response = $this->client->post($this->__setUri(), $this->dados, $this->__setHeaders());
		return json_decode($response);
	}

	public function PUT() {
		$this->__validaDados();
		$response = $this->client->put($this->__setUri(), $this->dados, $this->__setHeaders());
		return json_decode($response);
	}
	
	public function DELETE() {
		$response = $this->client->delete($this->__setUri(), $this->__setHeaders());
		return json_decode($response);
	}
	
	private function __setHeaders() {
		return array(
					CURLOPT_HTTPHEADER => array(
						'fabrica: '.$this->login_fabrica,
						'version: '.self::API_VERSION,
						'environment: '.self::API_ENVIRONMENT
					)
				);
	}

	private function __setUri() {
		if(!isset($this->uri)){
			throw new Exception("A URI nуo foi setada.");
		}
		return self::API_URL . '/' . $this->uri;
	}

	private function __validaDados() {
		if(!is_array($this->dados)){
			throw new Exception("O dado para gravaчуo nуo foi setado.");
		}
	}

}
?>