<?php

namespace action;

class ActionHolder{

	private $filter;
	private $action;

	public function __construct($action,$filter = null){
		$this->action = $action;
		$this->filter = $filter;
	}

	public function __invoke($event){
		$filter = $this->filter;
		$action = $this->action;
		$ok = true;
		if(!empty($filter) && is_callable($filter)){
			$ok = $filter($event);
		}
		if(!$ok)
			return;
		if(!empty($action) && is_callable($action)){
			$action($event);
		}
	}

}