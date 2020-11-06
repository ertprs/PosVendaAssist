<?php

namespace action;

class IsSetFilter{

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
		}
		return true;
	}
	
}