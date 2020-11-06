<?php

namespace html\element;

class Textarea extends DefaultHtmlElement{

	public function __construct($attributes=array(),$content=""){
		parent::__construct('textarea',$attributes,$content);
	}

	public function setAttribute($name,$value){
		if($name == 'value')
			$this->content = $value;
		else
			$this->attributes[$name] = $value;	
		
	}

	public function mergeAttributes(Array $attributes){
		$this->attributes = array_merge_recursive($this->attributes,$attributes);
		if(isset($this->attributes['value'])){
			$this->content = $this->attributes['value'];
			unset($this->attributes['value']);
		}
	}

	public function setContent($content){
		return;
	}

	protected function renderContent(){
		if(empty($this->content))
			return;
		echo (string)$this->content;
	}
}