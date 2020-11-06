<?php

namespace html;

use util\NameHelper;
use util\ArrayHelper;
use html\element\DefaultHtmlElement;
use rules\exceptions\ValidateException;

$pattern = array(
	'fieldName' => array(
		"span" => "2",//tamanho para o bootstrap 0 a 12
		"width" => "2",//tamanho interno no span 0 a 12
		"required" => true,//boolean coloca o * de obrigatorio
		"type" => "HtmlElement",//nome da classe
	),
);

class HtmlBuilder{

	private static $instance = null;

	public static function getInstance(){
		if(HtmlBuilder::$instance == null){
			HtmlBuilder::$instance = new HtmlBuilder();
		}
		return HtmlBuilder::$instance;
	}

	protected $error;
	protected $values;

	public function __construct($values=null){
		$this->values = ($values==null)?NameHelper::prepareArray($_REQUEST):$values;
		$this->error = array();
	}

	public function setValues(Array $values){
		$this->values = NameHelper::prepareArray($values);
	}

	public function mergeValues(Array $values){
		$this->values = array_merge($this->values,NameHelper::prepareArray($values));
	}

	public function fillError($modelName,ValidateException $exception){
		$key = NameHelper::toColumnName($modelName);
		foreach ($exception->getFailKeys() as $failKey) {
			$this->addError($key.'['.NameHelper::toColumnName($failKey).']');
		}
	}

	public function addError($fieldName){
		$this->error[$fieldName] = true;
	}

	public function hasError($fieldName){
		if(!isset($this->error[$fieldName]))
			return false;
		return true;
	}

	public function getValue($fieldName){
		$split = preg_split("@[\\[\\]]+@",$fieldName,-1,PREG_SPLIT_NO_EMPTY);
		$value = $this->values;
		foreach($split as $key){
			if(is_numeric($key))
				$key = (int)$key;
			else
				$key = NameHelper::prepareName($key);
			if(!isset($value[$key]))
				return null;
			$value = $value[$key];
		}
		return $value;
	}

	protected function initInlineElement($inlineString){
		$explode = explode('.',$inlineString);
		$tag = array_shift($explode);
		return new DefaultHtmlElement($tag,array('class'=>$explode));
	}

	protected function inlineBuild(&$array){
		$element = $this->initInlineElement(array_shift($array));
		if(empty($array))
			return $element;
		$element->setContent($this->inlineBuild($array));
		return $element;
	}

	public function build($html,$inDiv = true){
		$return = array();
		if(ArrayHelper::isMapArray($html))
			$htmls = array($html);
		else
			$htmls = $html;
		
		foreach($htmls as $html){
			$class = ArrayHelper::getAnySet($html,array('renderer','class'),'div');
			$attr = ArrayHelper::getAnySet($html,array('attr','attributes'),array());
			$content = ArrayHelper::getIfSet($html,'content','');
			$holder = HtmlHelper::inlineBuild($class,$attr,$content);
			$inner = $holder->getInner();
			$reflectionClass = new \ReflectionClass($inner);
			$properties = $reflectionClass->getProperties(\ReflectionProperty::IS_PUBLIC);
			foreach($properties as $property){
				$property->setValue($inner,ArrayHelper::getIfSet($html,$property->name,null));
			}
			$return[] = $holder;
		}
		if(count($return) == 1)
			return $return[0];
		else
			return ($inDiv)?new DefaultHtmlElement('div',array(),$return):$return;
	}
}
