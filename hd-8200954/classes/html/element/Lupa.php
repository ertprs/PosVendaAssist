<?php

namespace html\element;

use html\element\Input;
use util\ArrayHelper;

class Lupa extends DefaultHtmlElement{

	private $inputConfig;

	public function __construct(){
		parent::__construct(
			'span',
			array('rel'=>'lupa','class'=>array('add-on')),
			new DefaultHtmlElement('i',array('class'=>'icon-search'),'')
		);
		$this->inputConfig = new Input(array('type'=>'hidden'));
	}

	public function config($lupaConfig){
		$extra = ArrayHelper::getIfSet($lupaConfig,'extra',array());
		unset($lupaConfig['extra']);
		$this->inputConfig->mergeAttributes($extra);
		$this->inputConfig->mergeAttributes($lupaConfig);
	}

	public function setContent($content){
		return;
	}

	public function mergeAttributes(Array $attr){
		$this->inputConfig->mergeAttributes($attr);
	}

	public function setAttribute($name,$attr){
		$this->inputConfig->setAttribute($name,$attr);
	}

	public function render(){
		parent::render();
		$this->inputConfig->render();
	}

}