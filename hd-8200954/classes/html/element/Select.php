<?php

namespace html\element;

use util\ArrayHelper;

class Select extends DefaultHtmlElement{

	public function __construct($attributes = array()){
		parent::__construct('select',$attributes,array());
	}

	public function setContent($content){
		if(is_callable($content)){
			$content = $content();
		}
		$selected = ArrayHelper::getIfSet($this->attributes,'value','');
		unset($this->attributes['value']);
		$options = array();
		$options[] = new DefaultHtmlElement('option');
		foreach($content as $key => $value){
			if(is_string($value)){
				$optionLabel = $value;
				$optionAttr = array('value'=>$key);
			}
			else{
				$optionLabel = ArrayHelper::getIfSet($value,'label','');
				$optionAttr = $value;
			}
			if($selected == $optionAttr['value']){
				$optionAttr['selected'] = 'selected';
			}
			$options[] = new DefaultHtmlElement('option',$optionAttr,$optionLabel);
		}
		parent::setContent($options);
	}

}