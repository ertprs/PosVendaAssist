<?php

namespace model;

class DefaultModel extends Model{

	private $modelName;

	public function __construct($modelName,$pdo=null){
		$this->modelName = $modelName;
		parent::__construct($pdo);
	}


	public function getModelName(){
		return $this->modelName;
	}

}