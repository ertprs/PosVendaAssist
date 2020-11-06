<?php

namespace rules\check;

use util\NameHelper;
use rules\check\RuleCheck;

class RuleCheckFactory{


	public static function makeRuleCheck($ruleName,$ruleArg=null){
		$class = NameHelper::toClassName($ruleName.'Check');
		$class = 'rules\\check\\'.$class;
		if(!class_exists($class))
			throw new \Exception('Regra nao existe ('.$class.')');
		$reflectionClass = new \ReflectionClass($class);
		if(!$reflectionClass->implementsInterface('rules\\check\\RuleCheck'))
			throw new \Exception('Regra nao foi implementada devidamente ('.$class.')');
		return new $class($ruleArg);
	}

}
