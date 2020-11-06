<?php

namespace rules\exceptions;

use \Exception;

class CheckException extends Exception{

	private $field;

	public function __construct($field,$message){
		parent::__construct($message);
		$this->field = $field;
	}

	public function getField(){
		return $this->field;
	}

}