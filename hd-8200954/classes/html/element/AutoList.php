<?php


namespace html\element;

use util\ArrayHelper;
use html\HtmlBuilder;

class AutoList extends DefaultHtmlElement{


	public function __construct(){
		parent::__construct('div',array('class'=>array('AutoList')),array());
	}


	public function setContent($content){
		$content = $this->prepareContent($content);
		parent::setContent($content);
	}

	private function prepareContent($content){
		$newContent = array();
		$htmlBuilder = HtmlBuilder::getInstance();
		$wildcard = ArrayHelper::getIfSet($this->attributes,'list-wildcard','*');
		$name = ArrayHelper::getIfSet($this->attributes,'name',false);
		if($name){
			$lastK = null;
			$value = $htmlBuilder->getValue($name);
			foreach($value as $key => $v){
				$lastK = $key;
				$element = ArrayHelper::replaceAll($content,$wildcard,$key);
				$newContent[] = new DefaultHtmlElement('div',array('list-index'=>$key),$htmlBuilder->build($element,false));
			}
			if($lastK !== null){
				$this->mergeAttributes(array('list-counter'=>$lastK+1));
			}
		}
		$newContent[] = new DefaultHtmlElement('div',array('class'=>'AutoListModel'),$htmlBuilder->build($content,false));
		return $newContent;
	}
};