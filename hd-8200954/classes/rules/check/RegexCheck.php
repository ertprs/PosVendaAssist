<?php

namespace rules\check;

use exceptions\MultipleException;

class RegexCheck implements RuleCheck{

	private $regex;

	public function __construct($regex){
		if(!is_array($regex))
			$regex = array($regex);
		$this->regex = $regex;
	}

	public function checkValue($value){
		if(!is_string($value))
			$value = (string)$value;
		$ex = new MultipleException();
		foreach($this->regex as $key=> $v){
			if(is_numeric($key)){
				$regex = $v;
				$message = 'O campo % não passou na expressão regular '.$regex;
			}
			else{
				$regex = $key;
				$message = $v;
			}
			if(!preg_match($regex,$value))
				$ex->addException(new \Exception($message));
		}
		$ex->throwIfNotEmpty();
	}
	
}