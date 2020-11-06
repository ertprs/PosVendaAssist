<?php

namespace action;

use util\SQLHelper;
use action\Action;

class ModelAction implements Action{

	const INSERT = 'I';
	const UPDATE = 'U';
	const DELETE = 'D';

	private $model;
	private $method;
	private $element;
	private $elementId;
	private $sql;


	public function __construct($model,$method,$element=array(),$elementId=array()){
		$this->model = $model;
		if(!in_array($method,array(INSERT,UPDATE,DELETE)))
			throw new \Exception('Invalid Method ('.$method.')');
		$this->method = $method;
		$this->element = $element;
		$this->elementId = $elementId;
	}

	public function setElement($element){
		$this->element = $element;
	}

	public function setElementId($elementId){
		$this->elementId = $elementId;
	}

	public function run(){
		switch($this->method){
			case INSERT:
				return $this->insert();
			case UPDATE:
				return $this->update();
			case DELETE:
				return $this->delete();
		}
	}

	private function insert(){
		$this->model->insert($this->element);
	}

	private function delete(){
		$this->model->delete($this->elementId);
	}

	private function update(){
		$this->model->update($this->element,$this->elementId);
	}

}