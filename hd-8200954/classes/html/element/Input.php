<?php

namespace html\element;

class Input extends DefaultHtmlElement{

	public function __construct($attributes = array()){
		parent::__construct('input',$attributes,null);
	}

	public function render(){
		echo '<',$this->tag, parent::renderAttributes() ,'/>';
	}

}