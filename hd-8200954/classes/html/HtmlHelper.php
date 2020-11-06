<?php

namespace html;

use util\NameHelper;
use html\element\HtmlElement;
use html\element\DefaultHtmlElement;
use html\util\HtmlHolder;

class HtmlHelper {

	/**
	* @return html\util\HtmlHolder
	*/
	public static function inlineBuild($string,$attr=array(),$content=''){
		$elements = explode('>',$string);
		$element = HtmlHelper::inlineBuildElement(array_shift($elements));
		$holder = $element;
		while(!empty($elements)){
			$newElement = HtmlHelper::inlineBuildElement(array_shift($elements));
			$element->setContent($newElement);
			$element = $newElement;
		}
		$element->mergeAttributes($attr);
		$element->setContent($content);
		return new HtmlHolder($holder,$element);
	}

	protected static function inlineBuildElement($string){
		$explode = explode('.',$string);
		$tag = array_shift($explode);
		$classes = $explode;
		$attr = HtmlHelper::extractAttributes($tag);
		$attr['class'] = $classes;
		$htmlElement = HtmlHelper::initElement($tag);
		$htmlElement->mergeAttributes($attr);
		return $htmlElement;
	}

	protected static function extractAttributes(&$tag){
		$explodeAttrs = preg_split('@[\\[\\]]@',$tag,-1,PREG_SPLIT_NO_EMPTY);
		$tag = array_shift($explodeAttrs);
		$attr = array();
		foreach($explodeAttrs as $attrEqual){
			list($key,$value) = explode('=',$attrEqual);
			$attr[$key] = $value;
		}
		return $attr;
	}

	protected function initElement($tag){
		$className = 'html\\element\\'.NameHelper::toClassName($tag);
		if(!class_exists($className)){
			return new DefaultHtmlElement($tag);
		}
		$htmlElement = new $className();
		return $htmlElement;
	}



}