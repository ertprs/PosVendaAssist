<?php

namespace rules\check;

use rules\check\RuleCheck;

class NotEmptyCheck implements RuleCheck{

	private $message;

	public function __construct($message){
		if (is_string($message)) {
			$this->message = $message;
		}
		else{
			$this->message = 'O campo % não pode ser vazio';
		}
	}

	public function checkValue($value){
		if(empty($value))
			throw new \Exception($this->message);
	}	
}
