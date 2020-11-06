<?php 
namespace rules\process\os;
use \Exception;
use \ReflectionClass;
use util\ArrayHelper;
use util\NameHelper;

class CalculaOs{
    private $calculos = array();

    public function __construct(){
	$args = func_get_args();

	foreach($args as $key => $calculo){
	    if(is_callable($calculo)){
		$this->calculos[] = $calculo;
		continue;
	    }

	    if(is_string($calculo)) {
		$this->setCalculosClassByClassName($calculo);
		continue;
	    }

	    if(is_array($calculo) ){

		list($className, $parameters) = $this->validateArray($calculo);

		$this->setCalculosClassByNameAndParameters($className, $parameters);
		continue;
	    }
	}
    }

    public function setCalculosClassByClassName($calculo){
	$className = $this->getCalculosClassPath($calculo);
	if(class_exists($className)){
	    $this->calculos[] = new $className();
	}else{
	    throw new Exception("Class ". $className . " does not exist");
	}
    }

    public function setCalculosClassByNameAndParameters($className, $parameters){
	$className = $this->getCalculosClassPath($className);
	$reflectionClass = new ReflectionClass($className);

	$constructor = $reflectionClass->getConstructor();

	if(!ArrayHelper::isMapArray($parameters)){
	    $this->calculos[] = $reflectionClass->newInstanceArgs($parameters);
	    return;
	}

	$parameters = NameHelper::prepareArray($parameters);
	$args = array();

	foreach($constructor->getParameters() as $parameter){
	    $defaultValue = $parameter->getDefaultValue();
	    $value = ArrayHelper::getIfSet($parameters, $parameter->name, $defaultValue);
	    $args[] = $value;
	}

	if(class_exists($className)){
	    $this->calculos[] = $reflectionClass->newInstanceArgs($args);
	}
    
    }

    public function validateArray($calculo){
        if(count($calculo) > 1){
	     throw new Exception("Array must have only one item array('classname'=>array(parameters,to,constructor)).");
        }

        $keys = array_keys($calculo);
        $className = $keys[0];
        $parameters = $calculo[$className];
        return array($className, $parameters);
    }

    public function getCalculosClassPath($className){
	return "rules\\process\\os\\calculos\\".NameHelper::toClassName($className);
    }

    public function calcula($os){
	$soma = 0;

	foreach($this->calculos as $calculo){

	    $soma += $calculo($os);

	}
	return $soma;
    }

    public function __invoke($self, $os){
	return $this->calcula($os);
    }
}
