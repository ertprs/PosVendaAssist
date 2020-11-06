<?php


namespace html\element;


use html\HtmlBuilder;
use html\element\Br;
use util\NameHelper;
use util\ArrayHelper;

class BootstrapRows extends DefaultHtmlElement{

	private $marginSize = 1;

	public function __construct(){
		parent::__construct('div',array(),array());
	}

	public function setContent($content){
		$content = $this->prepareContent($content);
		parent::setContent($content);
	}

	public function makeHtmlElement($config,$name){
		$htmlBuilder = HtmlBuilder::getInstance();
		$innerElement = array();
		if(ArrayHelper::getIfSet($config,'required',false))
			$innerElement[] = new DefaultHtmlElement('h5',array('class'=>'asteristico'),'*');
		$explode = explode('/',$config['type']);
		$classType ='html\\element\\'.NameHelper::toClassName($explode[0]);
		$htmlElement = null;
		$elementAttributes = isset($config['extra'])?$config['extra']:array();
		$elementAttributes['id'] = $config['id'];
		$elementAttributes['name'] = $name;
		$elementAttributes['class'] = (($config['width'])?array('span'.$config['width']):'');
		$elementAttributes['type'] = ArrayHelper::getIfSet($explode,1,'');
		if($elementAttributes['type'] == 'checkbox'){
			$elementAttributes['value'] = true;
			if($htmlBuilder->getValue($name)){
				$elementAttributes['checked'] = 'checked';
			}
		}
		else{
			$elementAttributes['value'] = $htmlBuilder->getValue($name);
		}
		
		$center = ArrayHelper::getIfSet($config,'center',false);
		if(class_exists($classType)){
			$htmlElement = new $classType();
		}
		else{
			$htmlElement = new DefaultHtmlElement($explode[0],array('class'=>$config['type']));
		}
		$htmlElement->mergeAttributes($elementAttributes);
		if($elementAttributes['type'] == 'hidden'){
			return $htmlElement;
		}
		$content = ArrayHelper::getIfSet($config,'content',false);
		if($content)
			$htmlElement->setContent($content);
		if($elementAttributes['type'] == 'checkbox'){
			$innerElement[] = new DefaultHtmlElement('label',array('class'=>'inline checkbox'),$htmlElement);
		}
		else{
			$innerElement[] = $htmlElement;
			$lupa = ArrayHelper::getIfSet($config,'lupa',false);
			if($lupa){
				$lupaElement = new Lupa();
				$lupaElement->config($lupa);
				$innerElement[] = $lupaElement;
			}
			$innerElement =
			new DefaultHtmlElement(
				'div',
				array(
					'class' => 'span12'.($lupa?' input-append':'').($center?' tac':'')
				),
				array(
					$innerElement
				)
			);
		}

		return new DefaultHtmlElement(
			'div',
			array(
				'class' => 'span'.$config['span'],
			),
			array(
				new DefaultHtmlElement(
					'div',
					array(
						'class' => 'control-group'.($htmlBuilder->hasError($name)?' error':'')
					),
					array(
						new DefaultHtmlElement(
							'label',
							array(
								'class' => 'control-label',
								'for' => $config['id'],
							),
							ArrayHelper::getIfSet($config,'label','')
						),
						new DefaultHtmlElement(
							'div',
							array(
								'class' => 'controls controls-row'
							),
							array(
								$innerElement
							)
						),
					)
				)
			)
		);
	}


	protected function prepareContent($content){
		if(array_key_exists('@marginSize',$content) && is_numeric($content['@marginSize'])){
			$this->marginSize = (int)$content['@marginSize'];
			unset($content['@marginSize']);
		}
		$insertBr = false;
		$rows = array();
		$row = array();
		$spanCounter = $this->marginSize;
		$row[] = new DefaultHtmlElement('div',array('class'=>'span'.$this->marginSize));
		foreach ($content as $fieldName => $config) {
			if($spanCounter + $config['span'] > (12 - $this->marginSize)){
				$row[] = new DefaultHtmlElement('div',array('class'=>'span'.$this->marginSize));
				$rows[] = new DefaultHtmlElement('div',array('class'=>'row-fluid'),$row);
				if($insertBr){
					$rows[] = new Br();
					$insertBr = false;
				}
				$row = array();
				$spanCounter = 1;
				$row[] = new DefaultHtmlElement('div',array('class'=>'span'.$this->marginSize));
			}
			$spanCounter += $config['span'];
			$htmlElement = $this->makeHtmlElement($config,$fieldName);
			if(strtolower($config['type']) == 'textarea'){
				$insertBr = true;
			}
			$row[] = $htmlElement;
		}
		if($spanCounter== (12 - $this->marginSize)){
			$row[] = new DefaultHtmlElement('div',array('class'=>'span'.$this->marginSize));
		}
		$rows[] = new DefaultHtmlElement('div',array('class'=>'row-fluid'),$row);
		if($insertBr){
			$rows[] = new Br();
			$insertBr = false;
		}
		return $rows;
	}

}