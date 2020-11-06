<?php

namespace model;

use \Exception;
use action\Action;
use util\NameHelper;
use model\DefaultModel;
use rules\exceptions\ValidateException;
use rules\exceptions\CheckException;
use exceptions\MultipleException;

class ModelHolder{

	private static $instances = array();
	private $model;

	private static function getModel($modelName){
		if(isset(ModelHolder::$instances[$modelName]))
			return ModelHolder::$instances[$modelName];
		$className = 'model\\'.$modelName;
		if(class_exists($className)){
			ModelHolder::$instances[$modelName] = new $className();
		}
		else{
			ModelHolder::$instances[$modelName] = new DefaultModel($modelName);
		}
		return ModelHolder::$instances[$modelName];
	}

	public static function init($modelName){
		$model = ModelHolder::getModel($modelName);
		return new ModelHolder($model);
	}

	private function __construct($model){
		$this->model = $model;
	}

	public function insert($element,$idColumn=null){
		$validateException = new ValidateException($element);
		try{
			$this->model->begin();
			try{
				$this->model->fireBeforeInsert($element);		
			}
			catch(MultipleException $ex){
				$validateException->catchMultipleException($ex);
			}
			try{
				$this->model->check($element);	
			}
			catch(MultipleException $ex){
				$validateException->catchMultipleException($ex);	
			}
			$validateException->throwIfNotEmpty();
			$result = $this->model->insert($element,$idColumn);
			$this->model->fireAfterInsert($element,$result);
			$this->model->commit();
			return $result;
		}
		catch(Exception $ex){
			$this->model->rollback();
			throw $ex;
		}
	}

	public function delete($elementId){
		try{
			$this->model->begin();
			$this->model->fireBeforeDelete($elementId);
			$result = $this->model->delete($elementId);
			$this->model->fireAfterDelete($elementId,$result);
			$this->model->commit();
			return $result;
		}
		catch(Exception $ex){
			$this->model->rollback();
			throw $ex;
		}
	}

	public function update($element,$elementId){
		$validateException = new ValidateException($element);
		try{
			$this->model->begin();
			try{
				$this->model->fireBeforeUpdate($element,$elementId);
			}
			catch(MultipleException $ex){
				$validateException->catchMultipleException($ex);
			}
			try{
				$this->model->check($element);
			}
			catch(MultipleException $ex){
				$validateException->catchMultipleException($ex);
			}
			$validateException->throwIfNotEmpty();
			$result = $this->model->update($element,$elementId);
			$this->model->fireAfterUpdate($element,$elementId,$result);
			$this->model->commit();
			return $result;
		}
		catch(Exception $ex){
			$this->model->rollback();
			throw $ex;
		}
	}

	public function __call($method,$params){
		return $this->model->fireMethod($method,$params);
	}
}
