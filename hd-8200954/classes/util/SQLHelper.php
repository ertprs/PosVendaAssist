<?php

namespace util;

class SQLHelper{

	protected $pdo;

	public function __construct($pdo){
		$this->pdo = $pdo;
	}

	private function prepareValue($value){
		if(is_bool($value)){
			return $value?'t':'f';
		}
		if(is_array($value)){
			return json_encode($value);
		}
		return $value;
	}

	private function toTableName($model){
		if(is_string($model))
			return NameHelper::toTableName($model);
		if(is_a($model,'model\\Model'))
			return NameHelper::toTableName($model->getModelName());
		$class = get_class($model);
		$explodeClass = explode('\\',$class);
		return NameHelper::toTableName(end($explodeClass));
	}

	public function makeInsertStatement($model,$element,$returning=null){
		if(!is_array($element))
			$element = ArrayHelper::toArray($element);
		$table = $this->toTableName($model);
		$keys = array_keys($element);
		$columns = array();
		$params = array();
		foreach ($keys as $key) {
			$columns[] = NameHelper::toColumnName($key);
			$params[] = ':'.$key;
		}
		$return = '';
		if(!empty($returning))
			$return = 'RETURNING '.$returning;
		$sql = 'INSERT INTO '.$table.'('.implode(',',$columns).') VALUES ('.implode(',',$params).') '.$return.';';
		$stmt = $this->pdo->prepare($sql);
		foreach($element as $key => $value){
			$stmt->bindValue(':'.$key,$this->prepareValue($value));
		}
		return $stmt;
	}


	public function makeDeleteStatement($model,$elementId = array()){
		if(!is_array($elementId))
			$elementId = ArrayHelper::toArray($elementId);
		$table = $this->toTableName($model);
		$keys = array_keys($elementId);
		$where = array();
		foreach($keys as $key){
			$where[] = NameHelper::toColumnName($key).'=:'.$key;
		}
		if(empty($where))
			$where = ';';
		else
			$where = ' WHERE '.implode(' AND ',$where).';';
		$sql = 'DELETE FROM '.$table.$where;
		$stmt = $this->pdo->prepare($sql);
		foreach ($elementId as $key => $value) {
			$stmt->bindValue(':'.$key,$this->prepareValue($value));
		}
		return $stmt;
	}

	public function makeUpdateStatement($model,$element,$elementId){
		if(!is_array($element))
			$element = ArrayHelper::toArray($element);
		if(!is_array($elementId))
			$elementId = ArrayHelper::toArray($elementId);
		$table = $this->toTableName($model);
		$set = array();
		foreach(array_keys($element) as $key){
			$set[] = NameHelper::toColumnName($key).'=:'.$key;
		}
		$where = array();
		foreach(array_keys($elementId) as $key){
			$where[] = NameHelper::toColumnName($key).'=:_'.$key;
		}
		if(empty($where))
			$where = ';';
		else
			$where = ' WHERE '.implode(',',$where).';';
		$sql = 'UPDATE '.$table.' SET '.implode(',',$set).$where;
		$stmt = $this->pdo->prepare($sql);
		foreach($element as $key => $value){
			$stmt->bindValue(':'.$key,$this->prepareValue($value));
		}
		foreach ($elementId as $key => $value) {
			$value = (string)$value;
			$stmt->bindValue(':_'.$key,$this->prepareValue($value));
		}
		return $stmt;
	}

	public function makeSelectStatement($model,$elementId){
		if(!is_array($elementId))
			$elementId = ArrayHelper::toArray($elementId);
		$table = $this->toTableName($model);
		$keys = array_keys($elementId);
		$where = array();
		foreach($keys as $key){
			$where[] = NameHelper::toColumnName($key).'=:'.$key;
		}
		if(empty($where))
			$where = ';';
		else
			$where = ' WHERE '.implode(' AND ',$where).';';
		$sql = 'SELECT * FROM '.$table.$where;
		$stmt = $this->pdo->prepare($sql);
		foreach ($elementId as $key => $value) {
			$stmt->bindValue(':'.$key,$this->prepareValue($value));
		}
		return $stmt;
	}

	public function makeFieldStatement($model,$field,$elementId){
		$field = NameHelper::toColumnName($field);
		return $this->makeFindStatement($model,$elementId,array($field),1);
	}

	public function makeFindStatement($model,$filter=array(),$fields = array('*'),$limit=null){
		$table = $this->toTableName($model);
		$values = array();
		$where = array();
		foreach ($filter as $key => $value) {
			$column = NameHelper::toColumnName($key);
			if(!is_array($value)){
				$where[] = $column.' = :'.$key;
				$values[':'.$key] = $value;
				continue;
			}
			$in = array();
			foreach ($value as $index => $inValue) {
				$k = ':'.$key.'_'.$index;
				$in[] = $k;
				$values[$k] = $inValue;
			}
			$where[] = $column.' IN ('.implode(',',$in).')';
		}
		if(empty($where))
			$where = '';
		else
			$where = ' WHERE '.implode(' AND ',$where).'';
		if(empty($limit))
			$sqlLimit = ';';
		else if(is_array($limit)){
			$sqlLimit = 'LIMIT :limit OFFSET :offset';
			$values[':limit'] = $limit[0];
			$values[':offset'] = $limit[1];
		}
		else{
			$sqlLimit = ' LIMIT :limit;';
			$values[':limit'] = $limit;
		}


		$sql = 'SELECT '.implode(',',$fields).' FROM '.$table.$where.$sqlLimit;
		$stmt = $this->pdo->prepare($sql);
		foreach ($values as $key => $value) {
			$stmt->bindValue($key,$this->prepareValue($value));
		}
		return $stmt;
	}

}
