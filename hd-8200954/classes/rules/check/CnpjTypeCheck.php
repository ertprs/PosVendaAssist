<?php

namespace rules\check;

class CnpjTypeCheck implements RuleCheck{

	private $message;

	public function __construct($message){
		if(is_string($message)){
			$this->message = $message;
		}
		else{
			$this->message =  'O campo % não é um CNPJ válido';
		}
	}

	public function checkValue($value){
		if(empty($value))
			return;
		$cnpj = preg_replace('@[^0-9]*@','',$value);
		if(strlen($cnpj) != 14)
			throw new \Exception($this->message);
		$tamanho = strlen($cnpj)-2;
		$numeros = substr($cnpj,0,$tamanho);
		$digitos = substr($cnpj,$tamanho);
		$soma = 0;
		$pos = $tamanho-7;
		for($i=$tamanho;$i>=1;$i--){
			$soma += $numeros[$tamanho-$i] * $pos--;
			if($pos < 2)
				$pos = 9;
		}
		if(($soma % 11) < 2)
			$resultado = 0;
		else
			$resultado = 11 - ($soma % 11);
		if($resultado != $digitos[0]){
			throw new \Exception($this->message);
		}

		$tamanho = $tamanho +1;
		$numeros = substr($cnpj,0,$tamanho);
		$soma = 0;
		$pos = $tamanho-7;
		for($i=$tamanho;$i>=1;$i--){
			$soma += $numeros[$tamanho-$i] * $pos--;
			if($pos < 2)
				$pos = 9;
		}
		if(($soma % 11) < 2)
			$resultado = 0;
		else
			$resultado = 11 - ($soma % 11);
		if($resultado != $digitos[1]){
			throw new \Exception($this->message);
		}
		return true;
	}
}

