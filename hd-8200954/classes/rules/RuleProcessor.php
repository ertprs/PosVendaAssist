<?php

namespace rules;

use \Exception;

use util\NameHelper;

use exceptions\MultipleException;

use rules\check\RuleCheckFactory;
use rules\exceptions\CheckException;

class RuleProcessor{

	private $fieldRules = array();
	private $toCheck;
	private $default;
	private $required;

	public function __construct($ruleArray){
		$ruleArray = NameHelper::prepareArrayWithRecursiveLimit($ruleArray,false,$r=2);
		$this->default = array();
		$this->required = array();
		foreach($ruleArray as $fieldName => $rules){
			$this->fieldRules[$fieldName] = array();
			foreach($rules as $ruleName => $ruleArg){
				if($ruleName == 'default' && $ruleArg !== false){
					$this->default[$fieldName] = $ruleArg;
					continue;
				}
				if($ruleName == 'required' && $ruleArg !== false){
					$this->required[$fieldName] = $ruleArg;
					continue;
				}
				try{
					$this->fieldRules[$fieldName][] = RuleCheckFactory::makeRuleCheck($ruleName,$ruleArg);
				}
				catch(Exception $ex){
					trigger_error($ex->getMessage(),E_USER_NOTICE);
					continue;
				}
			}
		}
	}

	private function isRequired($name){
		return isset($this->required[$name]);
	}

	private function isSetField($name){
		if(is_array($this->toCheck))
			return isset($this->toCheck[$name]);
		$reflectionClass = new ReflectionClass($this->toCheck);
		$reflectionProperty = $reflectionClass->getProperty($name);
		return !empty($reflectionProperty);
	}

	private function getFieldValue($name){
		if(is_array($this->toCheck))
			return isset($this->toCheck[$name])?$this->toCheck[$name]:null;
		$reflectionClass = new ReflectionClass($this->toCheck);
		$reflectionProperty = $reflectionClass->getProperty($name);
		$reflectionProperty->setAccessible(true);
		return $reflectionProperty->getValue($this->toCheck);
	}

	private function setFieldValue($name,$value){
		if(is_array($this->toCheck))
			return $this->toCheck[$name] = $value;
		$reflectionClass = new ReflectionClass($this->toCheck);
		$reflectionProperty = $reflectionClass->getProperty($name);
		$reflectionProperty->setAccessible(true);
		$reflectionProperty->setValue($this->toCheck,$value);
	}

	private function checkField($fieldName){
		$name = NameHelper::prepareName($fieldName);
		$value = $this->getFieldValue($name);
		$exception = new MultipleException();
		foreach($this->fieldRules[$name] as $checkRule){
			try{
				$checkRule->checkValue($value);	
			}
			catch(MultipleException $multipleException){
				foreach ($multipleException->getExceptions() as $ex) {
					$exception->addException(new CheckException($fieldName,$ex->getMessage()));	
				}
			}
			catch(Exception $ex){
				$exception->addException(new CheckException($fieldName,$ex->getMessage()));
			}
		}
		$exception->throwIfNotEmpty();
	}

	private function fillDefaults(){
		foreach($this->default as $name => $value){
			$fieldValue = $this->getFieldValue($name);
			if(!is_null($fieldValue))
				continue;
			$this->setFieldValue($name,$value);
		}
	}

	private function checkRequired($fieldName){
		if(!$this->isRequired($fieldName))
			return;
		$message = is_string($this->required[$fieldName])?$this->required[$fieldName]:'Campo % é requerido';
		if(!$this->isSetField($fieldName)){
			throw new Exception($message);
		}
		$value = $this->getFieldValue($fieldName);
		
		if(empty($value)){
			throw new Exception($message);	
		}
	}

	public function validate(&$object){
		$exception = new MultipleException();
		$this->toCheck = &$object;
		foreach(array_keys($this->fieldRules) as $fieldName){
			try{
				$this->checkRequired($fieldName);
				$this->checkField($fieldName);
			}
			catch(MultipleException $ex){
				$exception->merge($ex);
			}
			catch(Exception $ex){
				$exception->addException(new CheckException($fieldName,$ex->getMessage()));
			}
		}
		$exception->throwIfNotEmpty();
	}
}


