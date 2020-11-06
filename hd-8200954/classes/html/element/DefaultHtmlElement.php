<?php

namespace html\element;

use html\HtmlBuilder;

class DefaultHtmlElement implements HtmlElement{
	
	protected $tag;
	protected $content;
	protected $attributes;

	public function __construct($tag='div',$attributes=array(),$content=array()){
		$this->tag = $tag;
		$this->attributes = $attributes;
		$this->content = $content;
	}

	public function setAttribute($name,$value){
		$this->attributes[$name] = $value;
	}

	public function mergeAttributes(Array $attributes){
		$this->attributes = array_merge_recursive($this->attributes,$attributes);
	}

	public function setContent($content){
		if(is_a($content,'html\\element\\HtmlElement'))
			return $this->content = $content;
		if(is_string($content))
			return $this->content = $content;
		if(!is_array($content))
			return $this->content = $content;
		if(is_a($content[0],'html\\element\\HtmlElement'))
			return $this->content = $content;
		$htmlBuilder = HtmlBuilder::getInstance();
		return $this->content = $htmlBuilder->build($content,false);
	}

	protected function renderElement($element){
		if(is_string($element)){
			echo $element;
			return;
		}
		if(is_a($element,'html\\element\\HtmlElement')){
			$element->render();
			return;
		}
		if(!is_array($element)){
			throw new \Exception('falha ao renderizar html');
		}
		foreach($element as $e){
			$this->renderElement($e);
		}
	}

	protected function renderContent(){
		if(empty($this->content))
			return;
		$this->renderElement($this->content);
	}

	protected function preparaValue($value){
		if(is_bool($value))
			return $value?'true':'false';
		if(is_array($value))
			return implode(' ',$value);
		return (string)$value;
	}

	protected function renderAttributes(){
		foreach($this->attributes as $name => $value){
			echo ' ',$name,'="',$this->preparaValue($value),'"';
		}
	}

	public function render(){
		echo '<',$this->tag,' ', $this->renderAttributes() ,' >';
		$this->renderContent();
		echo '</',$this->tag,'>';
	}

}