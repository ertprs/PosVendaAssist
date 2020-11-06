<?php

namespace rules\check;

use \DateTime;

class DateTypeCheck implements RuleCheck{

	private $message;

	public function __construct($message){
		if(is_string($message)){
			$this->message = $message;
		}
		else{
			$this->message =  'O campo % n�o � uma data v�lida';
		}
	}

	public function checkValue($value){
		$dateTime = DateTime::createFromFormat('d/m/Y',$value);
		if(!$dateTime)
			throw new \Exception($this->message);
	}

}