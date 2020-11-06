<?php

abstract class Parametros {
	
	protected $con;
	protected $fabrica;
	
	public function setDB($con) {

		if (!is_resource($con))
			throw new Exception("Passe uma conexão por parâmetro");

		$this->con = $con;

	}

	public function setFabrica ($fabrica) { //@todo tratar se fabrica existe, ativa e etc.
		$this->fabrica = $fabrica;
	}

	public function getFabrica() {
		return $this->fabrica;
	}

	public function getCon() {
		return $this->con;
	}

}