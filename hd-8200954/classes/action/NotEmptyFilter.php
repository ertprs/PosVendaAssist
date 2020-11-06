<?php

namespace action;

class NotEmptyFilter{

	private $checkKeys;

	public function __construct($checkKeys){
		if(!is_array($checkKeys))
			$checkKeys = array($checkKeys);
		$this->checkKeys = $checkKeys;
	}

	public function __invoke($event){
		foreach($this->checkKeys as $key){
			if(!array_key_exists($key,$event['element']))
				return false;
			if(empty($event['element'][$key]))
				return false;
		}
		return true;
	}
	
}