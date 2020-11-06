<?php


namespace rules\exceptions;

use util\NameHelper;
use \Exception;
use exceptions\MultipleException;

class ValidateException extends Exception{

	private $element;
	private $fails = array();

	public function __construct($element=null){
		parent::__construct();
		$this->element = $element;
	}

	public function catchCheckException(CheckException $exception){
		$field = $exception->getField();
		$this->addFail($field,$exception);
	}

	public function catchValidateException(ValidateException $exception){
		$this->addFails($exception->fails);
	}

	public function catchMultipleException(MultipleException $exception){
		foreach ($exception->getExceptions() as $ex) {
			if(!is_a($ex,'rules\\exceptions\\CheckException'))
				throw $ex;
			$this->addFail($ex->getField(),$ex->getMessage());
		}
	}

	public function addFail($field,$exception){
		if(!isset($this->fails[$field]))
			$this->fails[$field] = array();
		if(is_string($exception))
			$this->fails[$field][] = $exception;
		else
			$this->fails[$field][] = $exception->getMessage();
	}

	public function addFails(Array $fails){
		$this->fails = array_merge_recursive($this->fails,$fails);
	}

	public function throwIfNotEmpty(){
		if(!empty($this->fails))
			throw $this;
	}

	public function toMsgErro($mask='%',$wildcard='%'){
		$msgErro = array();
		$msgErro['campos'] = array();
		$msgErro['msg'] = array();
		foreach($this->fails as $field => $fails){
			$msgErro['campos'][] = str_replace($wildcard,NameHelper::toColumnName($field),$mask);
			foreach ($fails as $message) {
				if(!in_array($message,$msgErro['msg']))
					$msgErro['msg'][] = $message;
			}
		}
		return $msgErro;
	}

	public function hasFail($fielName){
		return isset($this->fails[$fielName]);
	}

	public function getFailKeys(){
		return array_keys($this->fails);
	}

}