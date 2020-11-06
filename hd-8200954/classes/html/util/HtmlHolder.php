<?php

namespace html\util;

use html\element\HtmlElement;

class HtmlHolder implements HtmlElement{

	private $border;
	private $inner;

	public function __construct(HtmlElement $border,HtmlElement $inner){
		$this->border = $border;
		$this->inner = $inner;
	}

	public function getBorder(){
		return $this->border();
	}

	public function getInner(){
		return $this->inner;
	}

	public function setAttribute($name,$value){
		$this->inner->setAttribute($name,$value);
	}

	public function setContent($content){
		$this->inner->setContent($content);	
	}

	public function mergeAttributes(Array $attributes){
		$this->inner->mergeAttributes($attributes);
	}

	public function render(){
		$this->border->render();
	}

}
