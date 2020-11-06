<?php

namespace exceptions;

use \Exception;

class MultipleException extends Exception{

	private $exceptions = array();

	public function __construct(){
		parent::__construct('Multiple Exception');
	}

	public function merge(MultipleException $exception){
		$this->exceptions = array_merge($this->exceptions,$exception->exceptions);
	}

	public function addException($exception){
		$this->exceptions[] = $exception;
	}

	public function getMessageArray(){
		$messages = array();
		foreach ($this->exceptions as $ex) {
			$messages[] = $ex->getMessage();
		}
		return $messages;
	}

	public function throwIfNotEmpty(){
		if(!empty($this->exceptions))
			throw $this;
	}

	public function getExceptions(){
		return $this->exceptions;
	}

}